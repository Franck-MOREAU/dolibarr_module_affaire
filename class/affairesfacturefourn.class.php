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
		$sql = "SELECT DISTINCT ";
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
				if ($key == 'cdet.rowid' || $key == 'cdet.fk_product' || $key == 'c.fk_soc') {
					$sql .= ' AND ' . $key . ' = ' . $value;
				} elseif ($key == 'cdet.qty' || $key == 'cdet.total_ht') {
					$sql .= ' AND ' . $key . ' = \'' . $this->db->escape($value) . '\'';
				} elseif ($key == 'cdete.solde') {
					$sql .= ' AND ' . $key . $value;
				} elseif ($key == 'c.fk_statut IN') {
					$sql .= ' AND ' . $key . ' (' . $value . ')';
				} else {
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

					$line->id = $line->rowid;
					$line->p_url = '';
					if (! empty($line->fk_product)) {
						$objstatic = new Product($this->db);
						$objstatic->fetch($line->fk_product);
						$line->p_url = $objstatic->getNomUrl() . ' - ' . $objstatic->label;
					}
					$line->solde_checkbox = '';
					if (! empty($line->solde)) {
						$line->solde_checkbox = img_picto('', 'switch_on', ' id="swith_' . $line->id . '" data-src="' . $line->id . '" ');
						$line->solde_checkbox .= '<input type="checkbox" name="solde_lineid_' . $line->id . '" id="solde_lineid_' . $line->id . '" value="' . $line->id . '" checked style="display:none">';
					} else {
						$line->solde_checkbox = img_picto('', 'switch_off', ' id="swith_' . $line->id . '" data-src="' . $line->id . '" ');
						$line->solde_checkbox .= '<input type="checkbox" name="solde_lineid_' . $line->id . '" id="solde_lineid_' . $line->id . '" value="' . $line->id . '" style="display:none">';
					}

					$this->lines[] = $line;
				}
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . ' ' . $this->db->lasterror(), LOG_ERR);
			return - 1;
		}
	}

	/**
	 *
	 * @param int $socid
	 * @param string $ref
	 * @param int $date
	 * @param array $linesid
	 * @param array $solde
	 * @param array $amount
	 * @return number
	 */
	public function createFactureFourn($socid, $ref, $date, $linesid = array(), $solde = array(), $amount = array()) {
		global $conf, $user;

		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
		require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/lib/price.lib.php';

		if (count($linesid) == 0) {
			$this->errors[] = 'Missing line id array';
			$error ++;
		}
		if (count($amount) == 0) {
			$this->errors[] = 'Missing amount array';
			$error ++;
		}
		if (empty($socid)) {
			$this->errors[] = 'Missing $socid';
			$error ++;
		}
		if (empty($ref)) {
			$this->errors[] = 'Ref fourn';
			$error ++;
		}

		$this->db->begin();
		if (empty($error)) {

			// Build an array for link supplier invoice to order invoice
			$orderid = array();
			$lineorderidinfo = array();
			foreach ( $linesid as $key => $val ) {
				$sql = 'SELECT fk_commande,description,tva_tx,info_bits,fk_product,qty,product_type FROM ' . MAIN_DB_PREFIX . 'commande_fournisseurdet WHERE rowid=' . $val;
				$resql = $this->db->query($sql);
				if (! $resql) {
					$this->errors[] = "Error " . $this->db->lasterror();
				} else {
					$num = $this->db->num_rows($resql);
					if ($num > 0) {
						while ( $obj = $this->db->fetch_object($resql) ) {
							$orderid[$obj->fk_commande] = $obj->fk_commande;
							$lineorderidinfo[$val] = array(
									'desc' => $obj->description,
									'tva' => $obj->tva_tx,
									'bits' => $obj->info_bits,
									'fk_product' => $obj->fk_product,
									'qty' => $obj->qty,
									'type' => $obj->product_type
							);
						}
					}
				}
			}

			$factsup = new FactureFournisseur($this->db);
			$factsup->socid = $socid;
			$factsup->ref_supplier = $ref;
			$factsup->date = $date;

			$factsup->linked_objects["order_supplier"] = $orderid;

			$invoiceid = $factsup->create($user);
			if ($invoiceid < 0) {
				$this->errors[] = $factsup->error;
				$error ++;
			}

			$factsup->fetch_thirdparty($socid);
		}

		if (empty($error)) {
			foreach ( $linesid as $key => $val ) {

				$line = new SupplierInvoiceLine($this->db);
				$line->fk_facture_fourn = $invoiceid;
				$line->description = $lineorderidinfo[$val]['description'];
				$line->subprice = $amount[$val] / $lineorderidinfo[$val]['qty'];
				$line->tva_tx = $lineorderidinfo[$val]['tva'];
				$line->localtax1_tx = 0;
				$line->localtax2_tx = 0;
				$line->qty = $lineorderidinfo[$val]['qty'];
				$line->fk_product = $lineorderidinfo[$val]['fk_product'];
				$line->info_bits = $lineorderidinfo[$val]['bits'];
				$line->product_type = $lineorderidinfo[$val]['type'];

				$tabprice = calcul_price_total($line->qty, $line->subprice, 0, $line->tva_tx, 0, 0, 0, 'HT', $line->info_bits, $line->product_type, $factsup->thirdparty, array(), 100, 0, 0);
				$line->total_ht = $tabprice[0];
				$line->total_tva = $tabprice[1];
				$line->total_ttc = $tabprice[2];

				$line->array_options['options_fk_supplierorderlineid'] = $val;

				$result = $line->insert();
				if ($result < 0) {
					$this->errors[] = $line->error;
					$error ++;
				}
			}
		}

		// Update supplier oerder line with status solde
		if (empty($error)) {
			foreach ( $solde as $key => $val ) {
				$orderline = new CommandeFournisseurLigne($this->db);
				$result = $orderline->fetch($val);
				if ($result < 0) {
					$this->errors[] = $orderline->error;
					$error ++;
				} else {
					$orderline->array_options['options_solde'] = 1;
					$result = $orderline->update();
					if ($result < 0) {
						$this->errors[] = $orderline->error;
						$error ++;
					}
				}
			}
		}

		if (empty($error)) {
			$result = $factsup->validate($user);
			if ($result < 0) {
				$this->errors[] = $factsup->error;
				$error ++;
			}
		}

		if (! $error) {
			$this->db->commit();
			return $invoiceid;
		} else {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::" . __METHOD__ . ' ' . $errmsg, LOG_ERR);
			}
			$this->db->rollback();
			return - 1 * $error;
		}
	}
}
