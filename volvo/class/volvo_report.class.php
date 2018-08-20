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
		dol_syslog(__METHOD__, LOG_DEBUG);
		global $conf;

		$this->lines = array();

		$sql = "SELECT ";
		$sql.= "CONCAT(user.firstname, ' ' , user.lastname) as commercial, ";
		$sql.= "user.rowid as uid, ";
		$sql.= "cmd_ef.numom as numom, ";
		$sql.= "cmd.ref as cmd_ref, ";
		$sql.= "cmd.rowid as cmdid, ";
		$sql.= "af.ref as af_ref, ";
		$sql.= "af.rowid as afid, ";
		$sql.= "soc.nom as soc, ";
		$sql.= "soc.rowid as socid, ";
		$sql.= "ctm.nom as ctm, ";
		$sql.= "ctm.rowid as ctmid, ";
		$sql.= "genre.genre as genre, ";
		$sql.= "genre.rowid as genreid, ";
		$sql.= "gamme.gamme as gamme, ";
		$sql.= "gamme.rowid as gammeid, ";
		$sql.= "sil.silhouette as sil, ";
		$sql.= "sil.rowid as silid, ";
		$sql.= "car.carrosserie as car, ";
		$sql.= "car.rowid as carid, ";
		$sql.= "RIGHT(cmd_ef.vin,7) as chassis, ";
		$sql.= "cmd_ef.immat as immat, ";
		$sql.= "cmdf.rowid as cmdfid, ";
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
		$sql.= "INNER JOIN " . MAIN_DB_PREFIX . "c_affaires_genre as genre on genre.rowid = af_det.fk_genre ";
		$sql.= "INNER JOIN " . MAIN_DB_PREFIX . "c_affaires_gamme as gamme on gamme.rowid = af_det.fk_gamme ";
		$sql.= "INNER JOIN " . MAIN_DB_PREFIX . "c_affaires_silhouette as sil on sil.rowid = af_det.fk_silhouette ";
		$sql.= "INNER JOIN " . MAIN_DB_PREFIX . "c_affaires_carrosserie as car on car.rowid = af_det.fk_carrosserie ";
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
				$sqlwhere[] = $value['sql'];
			}
		}

		if (count($sqlwhere) > 0) {
			$sql .= ' WHERE ' . implode(' ' . $filtermode . ' ', $sqlwhere);
		}

		if (! empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit+1, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->sql = $sql;
			while ( $obj = $this->db->fetch_object($resql) ) {
				$obj->soc_url = '';
				if (! empty($obj->socid)) {
					dol_include_once('/societe/class/societe.class.php');
					$socstatic = new Societe($this->db);
					$socstatic->fetch($obj->socid);
					$obj->soc_url = $socstatic->getNomUrl();
				}
				$obj->ctm_url = '';
				if (! empty($obj->ctmid)) {
					dol_include_once('/societe/class/societe.class.php');
					$socstatic = new Societe($this->db);
					$socstatic->fetch($obj->ctmid);
					$obj->ctm_url = $socstatic->getNomUrl();
				}
				if (! empty($obj->cmdfid)) {
					$url = '<a href="' . DOL_URL_ROOT.'/fourn/commande/card.php?id=' . $obj->cmdfid .'">' . $obj->numom .'</a>';
					$obj->cmdf_url = $url;
				}
				if (! empty($obj->cmdid)) {
					dol_include_once('/affaires/volvo/class/commandevolvo.class.php');
					$cmdstatic = new CommandeVolvo($this->db);
					$cmdstatic->fetch($obj->cmdid);
					$obj->cmd_url = $cmdstatic->getNomUrl();
				}
				if (! empty($obj->afid)) {
					dol_include_once('/affaires/class/affaires.class.php');
					$afstatic = new Affaires($this->db);
					$afstatic->fetch($obj->afid);
					$obj->af_url = $afstatic->getNomUrl();
				}


				$this->lines[] = $obj;
			}
			$this->db->free($resql);
			dol_syslog(__METHOD__ . ' ' . $sql, LOG_ERR);
			return $sql;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			$this->errors[] = 'Error ' . $sql;
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return $sql;
		}
	}

	public function fetch_All_folow_count($filter = array(), $filtermode = 'AND') {
		dol_syslog(__METHOD__, LOG_DEBUG);
		global $conf;

		$sql = "SELECT ";
		$sql.= "cmd.rowid as cmdid ";
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
				$sqlwhere[] = $value['sql'];
			}
		}

		if (count($sqlwhere) > 0) {
			$sql .= ' WHERE ' . implode(' ' . $filtermode . ' ', $sqlwhere);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
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

}