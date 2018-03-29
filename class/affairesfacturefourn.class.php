<?php
class AffairesFactureFourn
{
	public $db; // !< To store db handler
	public $error; // !< To return error code (or message)
	public $errors = array(); // !< To return several error codes (or messages)
	public $lines = array();
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
		$sql .= " ,cdet.description";
		$sql .= " ,cdet.fk_product";
		$sql .= " ,cdete.solde";
		$sql .= " ,cdet.qty";
		$sql .= " ,cdet.subprice";
		$sql .= " ,cdet.total_ht";
		$sql .= " ,cdet.total_ttc";
		$sql .= " ,cdet.total_ttc";
		$sql .= " ,ce.vin";

		$sql .= " FROM " . MAIN_DB_PREFIX . "commande_fournisseurdet as cdet";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "commande_fournisseur as c ON c.rowid=cdet.fk_commande";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "commande_fournisseur_extrafields as ce ON c.rowid=ce.fk_object";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande_fournisseurdet_extrafields as cdete ON cdete.fk_object=cdet.rowid";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid=cdet.fk_product";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facture_fourn_det_extrafields as fdete ON fdete.fk_supplierorderlineid=cdet.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facture_fourn_det as fdet ON fdet.rowid=fdete.fk_object";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facture_fourn as f ON fdet.fk_facture_fourn=f.rowid";
		$sql .= " WHERE 1";

		if (is_array($filter)) {
			foreach ( $filter as $key => $value ) {
				if ($key == 'cdet.rowid' || $key == 'cdet.fk_product' || $key == 'c.fk_soc' ) {
					$sql .= ' AND ' . $key . ' = ' . $value;
				} elseif ($key == 'cdet.qty' || $key == 'cdet.total_ht') {
					$sql .= ' AND ' . $key . ' = \'' . $this->db->escape($value) . '\'';
				} elseif ($key == 'cdete.solde') {
					$sql .= ' AND ' . $key . $value;
				}else {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}

		if (! empty($sortfield)) {
			$sql .= " ORDER BY " . $sortfield . ' ' . $sortorder;
		}

		if (! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->lines = array();

			$num = $this->db->num_rows($resql);
			if ($num > 0) {

				require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

				while ( $obj = $this->db->fetch_object($resql) ) {
					$line = new stdClass();
					foreach ( $obj as $key => $val ) {
						$line->$key = $val;
					}

					$line->id=$line->rowid;
					$line->p_url = '';
					if (! empty($line->fk_product)) {
						$objstatic = new Product($this->db);
						$objstatic->fetch($line->fk_product);
						$line->p_url = $objstatic->getNomUrl() . ' - ' . $objstatic->label;
					}
					$line->solde_checkbox = '';
					if (! empty($line->solde)) {
						$line->solde_checkbox = img_picto('', 'switch_on', ' id="swith_'.$line->id.'" data-src="'.$line->id.'" ');
						$line->solde_checkbox .= '<input type="checkbox" name="solde_lineid_'.$line->id.'" id="solde_lineid_'.$line->id.'" value="'.$line->id.'" checked style="display:none">';
					} else {
						$line->solde_checkbox = img_picto('', 'switch_off', ' id="swith_'.$line->id.'" data-src="'.$line->id.'" ');
						$line->solde_checkbox .= '<input type="checkbox" name="solde_lineid_'.$line->id.'" id="solde_lineid_'.$line->id.'" value="'.$line->id.'" style="display:none">';
					}

					$this->lines[] = $line;
				}
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_all " . $this->error, LOG_ERR);
			return - 1;
		}
	}
}
