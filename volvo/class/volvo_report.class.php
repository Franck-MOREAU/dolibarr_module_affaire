<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
class volvo_report extends CommonObject
{
	public $db; // !< To store db handler
	public $error; // !< To return error code (or message)
	public $errors = array(); // !< To return several error codes (or messages)
	public $lines = array();
	public $sql;

	function __construct($db) {
		$this->db = $db;

	}

	public function fetch_All_folow($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND') {
		dol_include_once('/societe/class/societe.class.php');

		dol_syslog(__METHOD__, LOG_DEBUG);
		global $conf;

		$sql = "SELECT ";
		$sql.= "CONCAT(user.firstname, ' ' , user.lastname) as commercial, ";
		$sql.= "cmd_ef.numom as numom, ";
		$sql.= "cmd.ref as cmd_ref, ";
		$sql.= "af.ref as af_ref, ";
		$sql.= "soc.nom as soc, ";
		$sql.= "soc.rowid as socid, ";
		$sql.= "ctm.nom as ctm, ";
		$sql.= "ctm.rowid as ctmid, ";
		$sql.= "RIGHT(cmd_ef.vin,7) as chassis, ";
		$sql.= "cmd_ef.immat as immat, ";
		$sql.= "cmdf.date_commande as cmd_env_usi, ";
		$sql.= "cmd_ef.dt_blockupdate as dt_block_update, ";
		$sql.= "cmdf.date_livraison as dt_liv_prev, ";
		$sql.= "cmd_ef.dt_liv_maj as dt_liv_maj, ";
		$sql.= "cmd_ef.dt_lim_annul as dt_lim_annul, ";
		$sql.= "event1.datep as dt_liv_usi_reel, ";
		$sql.= "cmd.date_valid as date_valid, ";
		$sql.= "cmd.date_livraison as dt_prev_liv_cli, ";
		$sql.= "event2.datep as dt_liv_cli_reel, ";
		$sql.= "cmd_ef.dt_invoice as dt_facture, ";
		$sql.= "event3.datep as dt_pay, ";
		$sql.= "DATEDIFF(IFNULL(event3.datep,CURDATE()),event1.datep) AS delai_cash, ";
		$sql.= "DATEDIFF(event1.datep,cmdf.date_livraison)  as retard_liv_usi, ";
		$sql.= "DATEDIFF(event2.datep,cmd.date_livraison) as retard_liv_cli ";

		$sql.= "FROM llx_affaires_det as af_det ";
		$sql.= "INNER JOIN " . MAIN_DB_PREFIX . "affaires as af on af.rowid = af_det.fk_affaires ";
		$sql.= "INNER JOIN " . MAIN_DB_PREFIX . "user as user on af.fk_user_resp = user.rowid ";
		$sql.= "INNER JOIN " . MAIN_DB_PREFIX . "commande as cmd on cmd.rowid = af_det.fk_commande ";
		$sql.= "LEFT JOIN " . MAIN_DB_PREFIX . "commande_extrafields as cmd_ef on cmd_ef.fk_object = cmd.rowid ";
		$sql.= "INNER JOIN " . MAIN_DB_PREFIX . "societe as soc on cmd.fk_soc = soc.rowid ";
		$sql.= "LEFT JOIN " . MAIN_DB_PREFIX . "societe as ctm on ctm.rowid = cmd_ef.ctm ";
		$sql.= "LEFT JOIN " . MAIN_DB_PREFIX . "element_element as el on el.fk_source = cmd.rowid and el.sourcetype = 'commande' and el.targettype = 'order_supplier' ";
		$sql.= "INNER JOIN " . MAIN_DB_PREFIX . "commande_fournisseur as cmdf on cmdf.rowid = el.fk_target and cmdf.fk_soc = 32553 ";
		$sql.= "LEFT JOIN " . MAIN_DB_PREFIX . "actioncomm as event1 on event1.fk_element = cmdf.rowid AND event1.elementtype = 'order_supplier' AND event1.label LIKE 'Commande fournisseur VTFRA%reçue%' ";
		$sql.= "LEFT JOIN " . MAIN_DB_PREFIX . "actioncomm as event2 on event2.fk_element = cmd.rowid AND event2.elementtype = 'order ' AND event2.label LIKE 'Commande%classée Livrée%' ";
		$sql.= "LEFT JOIN " . MAIN_DB_PREFIX . "actioncomm as event3 on event3.fk_element = cmd.rowid AND event3.elementtype = 'order ' AND event3.label LIKE 'Commande%classée Payée%' ";

		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ( $filter as $key => $value ) {
				if (($key == 'com.date_valid') || ($key == 'com.date_livraison')|| ($key == 'event4.datep')|| ($key == 'event3.datep')|| ($key == 'event5.datep')|| ($key == 'cf.date_commande')|| ($key == 'event6.datep')|| ($key == 'ef.dt_blockupdate')|| ($key == 'cf.date_livraison')){
					$sqlwhere[] = $key . ' BETWEEN ' . $value;
				}elseif(($key== 'lead.fk_user_resp')||($key== 'com.rowid')) {
					$sqlwhere[] = $key . ' = ' . $value;
				}elseif(($key== 'delaiprep')||($key=='retard_recept')||($key=='retard_liv')||($key=='delai_cash')) {
					$sqlwhere[] = $key . ' BETWEEN ' . $value;
				}elseif(($key== 'search_run')) {
					$sqlwhere[] = '(event5.datep IS NULL OR event3.datep IS NULL OR event4.datep IS NULL)';
				}elseif(($key== 'MONTH_IN')) {
					$sqlwhere[] = 'MONTH(dt_sortie) IN (' . $value . ')';
				}elseif(($key== 'YEAR_IN')) {
					$sqlwhere[] = 'YEAR(dt_sortie) IN (' . $value . ')';
				}elseif(($key== 'PORT')) {
					$sqlwhere[] = '(cf.fk_statut>0 AND event3.datep IS NULL)';
				}else {
					$sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}

		if (count($sqlwhere) > 0) {
			$sql .= ' HAVING ' . implode(' ' . $filtermode . ' ', $sqlwhere);
		}

		if (! empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit+1, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$this->sql = $sql;
			while ( $obj = $this->db->fetch_object($resql) ) {
				$obj->soc_url = '';
				if (! empty($obj->socid)) {
					$socstatic = new Societe($this->db);
					$socstatic->fetch($obj->socid);
					$obj->soc_url = $socstatic->getNomUrl();
				}
				$obj->ctm_url = '';
				if (! empty($obj->ctmid)) {
					$socstatic = new Societe($this->db);
					$socstatic->fetch($obj->ctmid);
					$obj->ctm_url = $socstatic->getNomUrl();
				}


				$this->lines[] = $obj;
			}
			$this->db->free($resql);
			dol_syslog(__METHOD__ . ' ' . $sql, LOG_ERR);
			return $num;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			$this->errors[] = 'Error ' . $sql;
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return $sql;
		}
	}
	function print_date($date){
		return dol_print_date($date);
	}

}