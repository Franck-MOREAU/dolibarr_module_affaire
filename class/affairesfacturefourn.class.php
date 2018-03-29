<?php


class AffairesFactureFourn
{
	public $db; // !< To store db handler
	public $error; // !< To return error code (or message)
	public $errors = array (); // !< To return several error codes (or messages)

	public $lines = array ();

	function __construct($db) {
		$this->db = $db;
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param string $sortorder order
	 * @param string $sortfield field
	 * @param int $limit page
	 * @param int $offset Offset results
	 * @param array $filter output
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_supplierorderline($sortorder, $sortfield, $limit, $offset, $filter = array()) {
		global $langs;
		$sql = "SELECT";
		$sql .= " cdet.rowid";
		$sql .= " ,cdet.fk_product";
		$sql .= " ,cdete.solde";
		$sql .= " ,cdet.qty"
		$sql .= " ,cdet.qty"

		$sql .= " FROM " . MAIN_DB_PREFIX ."commande_fournisseurdet as cdet";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX ."commande_fournisseur as c ON c.rowid=cdet.fk_commande";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX ."commande_fournisseurdet_extrafields as cdete ON cdete.fk_object=cdet.rowid";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX ."product as p ON p.rowid=cdet.fk_product";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX ."facture_fourn_det_extrafields as fdete ON fdete.fk_supplierorderlineid=cdet.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX ."facture_fourn_det as fdet ON fdet.rowid=fdete.fk_object";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX ."facture_fourn as f ON fdet.fk_facture_fourn=f.rowid";
		$sql .= " WHERE 1";

		if (is_array($filter)) {
			foreach ( $filter as $key => $value ) {
				//TODO deal with filter
				/*if (($key == 't.fk_c_status')) {
							$sql .= ' AND ' . $key . ' = ' . $value;
						} elseif ($key == 't.fk_c_status !IN') {
							$sql .= ' AND t.fk_c_status NOT IN (' . $value . ')';
						} elseif ($key == 't.rowid !IN') {
							$sql .= ' AND t.rowid NOT IN (' . $value . ')';
						} else {
							$sql .= ' AND ' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
						}*/
			}
		}

		if (! empty($sortfield)) {
			$sql .= " ORDER BY " . $sortfield . ' ' . $sortorder;
		}

		if (! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this) . "::fetch_all sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->lines = array ();

			$num = $this->db->num_rows($resql);

			while ( $obj = $this->db->fetch_object($resql) ) {

				$line = new stdClass;
				$line->id = $obj->rowid;
				$line->ref = $obj->ref;
				$line->fk_user_resp = $obj->fk_user_resp;
				$line->fk_soc = $obj->fk_soc;
				$line->fk_ctm = $obj->fk_ctm;
				$line->fk_c_type = $obj->fk_c_type;
				$line->year = $obj->year;
				$line->description = $obj->description;
				$line->fk_user_author = $obj->fk_user_author;
				$line->datec = $this->db->jdate($obj->datec);
				$line->fk_user_mod = $obj->fk_user_mod;
				$line->tms = $this->db->jdate($obj->tms);
				$line->type_label = $this->type[$line->fk_c_type];
				$line->fetch_thirdparty($this->fk_soc);
				if($line->fk_ctm >0) $line->contremarque = $line->fetchObjectFrom('soci�t�', 'rowid', $line->ctm);

				if (empty($nodetail)) {
					// loading affaires lines into affaires_det array of object
					$det = New Affaires_det($this->db);
					$det->fetch_all('ASC','rowid',0,0,array('fk_affaires'=>$this->id));
					foreach ($det->lines as $line_det){
						$line->affaires_det[$line_det->id]=$line_det;
					}
				}
				$this->lines[] = $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_all " . $this->error, LOG_ERR);
			return - 1;
		}
	}
}
