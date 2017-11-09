<?php

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

class Affaires extends CommonObject
{
	var $db; // !< To store db handler
	var $error; // !< To return error code (or message)
	var $errors = array (); // !< To return several error codes (or messages)
	var $element = 'affaires'; // !< Id that identify managed objects
	var $table_element = 'affaires';
	
	var $id;
	var $ref;
	var $fk_user_resp;
	var $fk_soc;
	var $fk_ctm;
	var $fk_c_type;
	var $type_label;
	var $year;
	var $description;
	var $fk_user_author;
	var $datec;
	var $fk_user_mod;
	var $tms;
	var $lines = array ();
	var $type = array ();
	var $affaires_det = array();
	var $contremarque;
		
	function __construct($db) {
		
		$this->db = $db;
		
		$result_type = $this->loadType();
				
		return ($result_type);
		
	}
	
	private function loadType() {
		global $langs;
		
		$sql = "SELECT rowid, code, label FROM " . MAIN_DB_PREFIX . "c_lead_type  WHERE active=1";
		dol_syslog(get_class($this) . "::_load_type sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $obj = $this->db->fetch_object($resql) ) {
				$label = $langs->trans('LeadType_' . $obj->code);
				if ($label == 'LeadType_' . $obj->code) {
					$label = $obj->label;
				}
				
				$this->type[$obj->rowid] = $label;
			}
			$this->db->free($resql);
			
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::_load_type " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Create object into database
	 *
	 * @param User $user that creates
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, Id of created object if OK
	 */
	function create($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;
		
		// Clean parameters
		
		if (isset($this->ref))
			$this->ref = trim($this->ref);
		if (isset($this->fk_user_resp))
			$this->fk_user_resp = trim($this->fk_user_resp);
		if (isset($this->fk_soc))
			$this->fk_soc = trim($this->fk_soc);
		if (isset($this->fk_ctm))
			$this->fk_ctm = trim($this->fk_ctm);
		if (isset($this->fk_c_type))
			$this->fk_c_type = trim($this->fk_c_type);
		if (isset($this->year))
			$this->year = trim($this->year);
		if (isset($this->description))
			$this->description = trim($this->description);
				
		
// Check parameters
// Put here code to add control on parameters values
		if (empty($this->ref)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadRef'));
		}
		if (empty($this->fk_user_resp)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadCommercial'));
		}
		if (empty($this->fk_soc)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Customer'));
		}
		if (empty($this->fk_c_type)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadType'));
		}
		if (empty($this->year)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Leadyear'));
		}
			
		if (! $error) {
			// Insert request
			$sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . "(";
			
			$sql .= "ref,";
			$sql .= "fk_user_resp,";
			$sql .= "fk_soc,";
			$sql .= "fk_ctm,";
			$sql .= "fk_c_type,";
			$sql .= "year,";
			$sql .= "description,";
			$sql .= "fk_user_author,";
			$sql .= "datec,";
			$sql .= "fk_user_mod,";
			$sql .= "tms";
			
			$sql .= ") VALUES (";
			
			$sql .= " " . (! isset($this->ref) ? 'NULL' : "'" . $this->db->escape($this->ref) . "'") . ",";
			$sql .= " " . (! isset($this->fk_user_resp) ? 'NULL' : "'" . $this->fk_user_resp . "'") . ",";
			$sql .= " " . (! isset($this->fk_soc) ? 'NULL' : "'" . $this->fk_soc . "'") . ",";
			$sql .= " " . (! isset($this->fk_ctm) ? 'NULL' : "'" . $this->fk_ctm . "'") . ",";
			$sql .= " " . (! isset($this->fk_c_type) ? 'NULL' : "'" . $this->fk_c_type . "'") . ",";
			$sql .= " " . (! isset($this->year) ? 'NULL' : "'" . $this->year . "'") . ",";
			$sql .= " " . (empty($this->description) ? 'NULL' : "'" . $this->db->escape($this->description) . "'") . ",";
			$sql .= " " . $user->id . ",";
			$sql .= " '" . $this->db->idate(dol_now()) . "',";
			$sql .= " " . $user->id . ",";
			$sql .= " '" . $this->db->idate(dol_now()) . "'";
			$sql .= ")";
			
			$this->db->begin();
			
			dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}
		
		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "lead");
			
			if (! $notrigger) {
				// // Call triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface = new Interfaces($this->db);
				$result = $interface->run_triggers('AFFAIRES_CREATE', $this, $user, $langs, $conf);
				if ($result < 0) {
					$error ++;
					$this->errors = $interface->errors;
				}
				// // End call triggers
			}
		}
		
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}
			
	/**
	 * Load object in memory from the database
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch($id) {
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.ref,";
		$sql .= " t.fk_user_resp,";
		$sql .= " t.fk_soc,";
		$sql .= " t.fk_ctm,";
		$sql .= " t.fk_c_type,";
		$sql .= " t.year,";
		$sql .= " t.description,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";
		
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
		$sql .= " WHERE t.rowid = " . $id;
		
		dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
	
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->ref = $obj->ref;
				$this->fk_user_resp = $obj->fk_user_resp;
				$this->fk_soc = $obj->fk_soc;
				$this->fk_ctm = $obj->fk_ctm;
				$this->fk_c_type = $obj->fk_c_type;
				$this->year = $obj->year;
				$this->description = $obj->description;
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate($obj->datec);
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->tms = $this->db->jdate($obj->tms);
				$this->type_label = $this->type[$this->fk_c_type];
				$this->fetch_thirdparty($this->fk_soc);
				if($this->fk_ctm >0){
					require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
					$soc = new Societe($this->db);
					$soc->fetch($this->fk_ctm);
					$this->contremarque = $soc;
				}
				
				// loading affaires lines into affaires_det array of object
				$det = New Affaires_det($this->db);
				$det->fetch_all('ASC','rowid',0,0,array('fk_affaires'=>$this->id));
				foreach ($det->lines as $line){
					$this->affaires_det[$line->id]=$line;
				}
			}
			$this->db->free($resql);
			
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
			return - 1;
		}
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
	function fetch_all($sortorder, $sortfield, $limit, $offset, $filter = array()) {
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.ref,";
		$sql .= " t.fk_user_resp,";
		$sql .= " t.fk_soc,";
		$sql .= " t.fk_ctm,";
		$sql .= " t.fk_c_type,";
		$sql .= " t.year,";
		$sql .= " t.description,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";
		
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
		$sql .= " WHERE 1";
		
		if (is_array($filter)) {
			foreach ( $filter as $key => $value ) {
				if (($key == 't.fk_c_status') || ($key == 't.rowid') || ($key == 't.fk_soc') || ($key == 't.fk_ctm') ||($key == 't.fk_c_type') || ($key == 't.fk_user_resp')
						|| ($key == 't.year')) {
							$sql .= ' AND ' . $key . ' = ' . $value;
						} elseif ($key == 't.fk_c_status !IN') {
							$sql .= ' AND t.fk_c_status NOT IN (' . $value . ')';
						} elseif ($key == 't.rowid !IN') {
							$sql .= ' AND t.rowid NOT IN (' . $value . ')';
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
		
		dol_syslog(get_class($this) . "::fetch_all sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		
		if ($resql) {
			$this->lines = array ();
			
			$num = $this->db->num_rows($resql);
			
			while ( $obj = $this->db->fetch_object($resql) ) {
				
				$line = new Affaires($this->db);
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
				if($line->fk_ctm >0) $line->contremarque = $line->fetchObjectFrom('société', 'rowid', $line->ctm);
				
				// loading affaires lines into affaires_det array of object
				$det = New Affaires_det($this->db);
				$det->fetchall('ASC','rowid',0,0,array('fk_affaires'=>$this->id));
				foreach ($det->lines as $line_det){
					$line->affaires_det[$line_det->id]=$line_det;
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
	
	
	/**
	 * Update object into database
	 *
	 * @param User $user that modifies
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function update($user = null, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;
		
		// Clean parameters
		
		if (isset($this->ref))
			$this->ref = trim($this->ref);
		if (isset($this->fk_user_resp))
			$this->fk_user_resp = trim($this->fk_user_resp);
		if (isset($this->fk_soc))
			$this->fk_soc = trim($this->fk_soc);
		if (isset($this->fk_ctm))
			$this->fk_ctm = trim($this->fk_ctm);
		if (isset($this->fk_c_type))
			$this->fk_c_type = trim($this->fk_c_type);
		if (isset($this->year))
			$this->year = trim($this->year);
		if (isset($this->description))
			$this->description = trim($this->description);
												
												
		// Check parameters
		// Put here code to add control on parameters values
		if (empty($this->ref)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadRef'));
		}
		if (empty($this->fk_user_resp)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadCommercial'));
		}
		if (empty($this->fk_soc)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Customer'));
		}
		if (empty($this->fk_c_type)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadType'));
		}
		if (empty($this->year)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Leadyear'));
		}
		
		if (! $error) {
			// Update request
			$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET";
			
			$sql .= " ref=" . (isset($this->ref) ? "'" . $this->db->escape($this->ref) . "'" : "null") . ",";
			$sql .= " fk_user_resp=" . (isset($this->fk_user_resp) ? $this->fk_user_resp : "null") . ",";
			$sql .= " fk_soc=" . (isset($this->fk_soc) ? $this->fk_soc : "null") . ",";
			$sql .= " fk_ctm=" . (isset($this->fk_ctm) ? $this->fk_ctm : "null") . ",";
			$sql .= " fk_c_type=" . (isset($this->fk_c_type) ? $this->fk_c_type : "null") . ",";
			$sql .= " year=" . (isset($this->year) ? "'" . $this->year . "'" : "null") . ",";
			$sql .= " description=" . (! empty($this->description) ? "'" . $this->db->escape($this->description) . "'" : "null") . ",";
			$sql .= " fk_user_mod=" . $user->id . ",";
			$sql .= " tms='" . $this->db->idate(dol_now()) . "'";
			
			$sql .= " WHERE rowid=" . $this->id;
			
			$this->db->begin();
			
			dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}
		
		if (! $error) {
			if (! $notrigger) {
						
				// // Call triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface = new Interfaces($this->db);
				$result = $interface->run_triggers('AFFAIRES_MODIFY', $this, $user, $langs, $conf);
				if ($result < 0) {
					$error ++;
					$this->errors = $interface->errors;
				}
				// // End call triggers
			}
		}
		if (! $error) {
			foreach ($this->affaires_det as $affaires_det){
				$res = $affaires_det->update($user);
				if($res <1){
					foreach ($affaires_det->errors as $det_error){
						$this->errors[] = $det_error;
						$error ++;
					}
				}
			}
		}
		
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}
	
	/**
	 * Delete object in database
	 *
	 * @param User $user that deletes
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;
		
		$this->db->begin();
		
		if (! $error) {
			if (! $notrigger) {
								
				// // Call triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface = new Interfaces($this->db);
				$result = $interface->run_triggers('AFFAIRES_DELETE', $this, $user, $langs, $conf);
				if ($result < 0) {
					$error ++;
					$this->errors = $interface->errors;
				}
				// // End call triggers
			}
		}
		
		if (! $error) {
			foreach($this->affaires_det as $affaire_det){
				$res = $affaire_det->delete($user);
			}
			if ($res< 1) {
				$error ++;
				foreach ($affaire_det->errors as $det_error){
					$this->errors[] = $det_error;
				}	
			}
		}
		
		if (! $error) {
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element;
			$sql .= " WHERE rowid=" . $this->id;
			
			dol_syslog(get_class($this) . "::delete sql=" . $sql);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}
		
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}
	
	public function getNomUrl($withpicto = 0) {
		global $langs;
		
		$result = '';
		
		$lien = '<a href="' . dol_buildpath('affaires/form/card.php', 1) . '?id=' . $this->id . '">';
		$lienfin = '</a>';
		
		$picto = 'affaires';
		$label = $this->ref;
		
		$result = $lien;
		
		if ($withpicto == 0){
			$result .= $this->ref . $lienfin;
		}elseif($withpicto == 1){
			$result .= img_object($label, $picto) . $this->ref . $lienfin;
		}else{
			$result .= $this->ref . img_object($label, $picto) . $lienfin;
		}
		return $result;
	}
	
}

class Affaires_det extends CommonObject
{
	var $db; // !< To store db handler
	var $error; // !< To return error code (or message)
	var $errors = array (); // !< To return several error codes (or messages)
	var $element = 'affaires_det'; // !< Id that identify managed objects
	var $table_element = 'affaires_det'; // !< Name of table without prefix where object is stored
	
	var $id;
	var $fk_affaires;
	var $fk_gamme;
	var $gamme_label;
	var $gamme = array();
	var $fk_silhouette;
	var $silhouette_label;
	var $silhouette = array();
	var $fk_genre;
	var $genre_label;
	var $genre = array();
	var $fk_carrosserie;
	var $carrosserie_label;
	var $carrosserie = array();
	var $fk_status;
	var $status_label;
	var $fk_marque_trt;
	var $marque_trt_label;
	var $marque_trt= array();
	var $fk_motifs;
	var $motifs= array();
	var $spec;
	var $fk_commande;
	var $fk_user_author;
	var $datec;
	var $fk_user_mod;
	var $tms;
	var $lines = array ();
	
	function __construct($db) {
		
		$this->db = $db;
		
		$result_status = $this->loadStatus();
		$result_gamme = $this->loadGamme();
		$result_genre = $this->loadGenre();
		$result_carrosserie = $this->loadCarrosserie();
		$result_silhouette = $this->loadSilhouette();
		$result_marques = $this->loadMarques();
		$result_motifs = $this->loadMotifs();
		
		return ($result_status&&$result_carrosserie&&$result_gamme&&$result_genre&&$result_marques&&$result_motifs&&$result_silhouette);
	}
	
	/**
	 * Load status array
	 */
	private function loadStatus() {
		global $langs;
		
		$sql = "SELECT rowid, code, label, active FROM " . MAIN_DB_PREFIX . "c_affaires_status WHERE active=1";
		dol_syslog(get_class($this) . "::_load_status sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $obj = $this->db->fetch_object($resql) ) {
				
				$label = $langs->trans('LeadStatus_' . $obj->code);
				if ($label == 'LeadStatus_' . $obj->code) {
					$label = $obj->label;
				}
				
				$this->status[$obj->rowid] = $label;
			}
			$this->db->free($resql);
			
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::_load_status " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Load gamme array
	 */
	private function loadGamme() {
		
		$sql = "SELECT rowid, gamme, cv, active FROM " . MAIN_DB_PREFIX . "c_affaires_gamme WHERE active=1";
		dol_syslog(get_class($this) . "::_load_gamme sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $obj = $this->db->fetch_object($resql) ) {
				$this->gamme[$obj->rowid] = $obj;
			}
			$this->db->free($resql);
			
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::_load_gamme " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Load silhouette array
	 */
	private function loadSilhouette() {
				
		$sql = "SELECT rowid, silhouette, cv, active FROM " . MAIN_DB_PREFIX . "c_affaires_silhouette WHERE active=1";
		dol_syslog(get_class($this) . "::_load_silhouette sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $obj = $this->db->fetch_object($resql) ) {
				$this->silhouette[$obj->rowid] = $obj;
			}
			$this->db->free($resql);
			
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::_load_silhouette " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Load genre array
	 */
	private function loadGenre() {
		
		$sql = "SELECT rowid, genre, cv, active FROM " . MAIN_DB_PREFIX . "c_affaires_genre WHERE active=1";
		dol_syslog(get_class($this) . "::_load_genre sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $obj = $this->db->fetch_object($resql) ) {
				$this->genre[$obj->rowid] = $obj;
			}
			$this->db->free($resql);
			
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::_load_genre " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Load carrosserie array
	 */
	private function loadCarrosserie() {
		
		$sql = "SELECT rowid, carrosserie, active FROM " . MAIN_DB_PREFIX . "c_affaires_carrosserie WHERE active=1";
		dol_syslog(get_class($this) . "::_load_carrosserie sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $obj = $this->db->fetch_object($resql) ) {
				$this->carrosserie[$obj->rowid] = $obj;
			}
			$this->db->free($resql);
			
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::_load_carrosserie " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Load marque_trt array
	 */
	private function loadMarques() {
		
		$sql = "SELECT rowid, marque, active FROM " . MAIN_DB_PREFIX . "c_affaires_marques WHERE active=1";
		dol_syslog(get_class($this) . "::_load_marques sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $obj = $this->db->fetch_object($resql) ) {
				$this->marque_trt[$obj->rowid] = $obj;
			}
			$this->db->free($resql);
			
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::_load_marque " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Load motifs array
	 */
	private function loadMotifs() {
		
		$sql = "SELECT rowid, motif, active FROM " . MAIN_DB_PREFIX . "c_affaires_motif_perte_lead WHERE active=1";
		dol_syslog(get_class($this) . "::_load_marques sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $obj = $this->db->fetch_object($resql) ) {
				$this->motifs[$obj->rowid] = $obj;
			}
			$this->db->free($resql);
			
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::_load_motif " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/* Create object into database
	*
	* @param User $user that creates
	* @param int $notrigger triggers after, 1=disable triggers
	* @return int <0 if KO, Id of created object if OK
	*/
	function create($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;
	
		if (isset($this->fk_affaires))
			$this->fk_affaires = trim($this->fk_affaires);
		if (isset($this->fk_game))
			$this->fk_gamme = trim($this->fk_gamme);
		if (isset($this->fk_silhouette))
			$this->fk_silhouette = trim($this->fk_silhouette);
		if (isset($this->fk_genre))
			$this->fk_genre = trim($this->fk_genre);
		if (isset($this->fk_carrosserie))
			$this->fk_carrosserie = trim($this->fk_carrosserie);
		if (isset($this->fk_status))
			$this->fk_status = trim($this->fk_status);
		if (isset($this->fk_marque_trt))
			$this->fk_marque_trt = trim($this->fk_marque_trt);
		if (isset($this->fk_motifs))
			$this->fk_motifs = trim($this->fk_motifs);
		if (isset($this->spec))
			$this->spec = trim($this->spec);
		if (isset($this->fk_commande))
			$this->fk_commande = trim($this->fk_commande);
		
		
			// Check parameters
			// Put here code to add control on parameters values
			
		if (empty($this->fk_affaires)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadRefInt'));
		}
		
		if (empty($this->fk_gamme)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadRefInt'));
		}
		
		if (empty($this->fk_genre)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadRefInt'));
		}
		
		if (empty($this->fk_silhouette)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadRefInt'));
		}
		
		if (empty($this->fk_carrosserie)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadRefInt'));
		}
		
		if (empty($this->fk_status)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadRefInt'));
		}
		
		if (! $error) {
			// Insert request
			$sql = "INSERT INTO " . MAIN_DB_PREFIX . "lead(";
			
			$sql .= "fk_affaires,";
			$sql .= "fk_gamme,";
			$sql .= "fk_silhouette,";
			$sql .= "fk_genre,";
			$sql .= "fk_carrosserie,";
			$sql .= "fk_status,";
			$sql .= "fk_marque_trt,";
			$sql .= "fk_motif,";
			$sql .= "fk_commande,";
			$sql .= "spec,";
			$sql .= "fk_user_author,";
			$sql .= "datec,";
			$sql .= "fk_user_mod,";
			$sql .= "tms";
			
			$sql .= ") VALUES (";
			$sql .= " " . (! isset($this->fk_affaires) ? 'NULL' : "'" . $this->db->escape($this->fk_affaires) . "'") . ",";
			$sql .= " " . (! isset($this->fk_gamme) ? 'NULL' : "'" . $this->db->escape($this->fk_gamme) . "'") . ",";
			$sql .= " " . (! isset($this->fk_silhouette) ? 'NULL' : "'" . $this->db->escape($this->fk_silhouette) . "'") . ",";
			$sql .= " " . (! isset($this->fk_genre) ? 'NULL' : "'" . $this->db->escape($this->fk_genre) . "'") . ",";
			$sql .= " " . (! isset($this->fk_carrosserie) ? 'NULL' : "'" . $this->db->escape($this->fk_carrosserie) . "'") . ",";
			$sql .= " " . (! isset($this->fk_status) ? 'NULL' : "'" . $this->db->escape($this->fk_status) . "'") . ",";
			$sql .= " " . (! isset($this->fk_marque_trt) ? 'NULL' : "'" . $this->db->escape($this->fk_marque_trt) . "'") . ",";
			$sql .= " " . (! isset($this->fk_motifs) ? 'NULL' : "'" . $this->db->escape($this->fk_motifs) . "'") . ",";
			$sql .= " " . (! isset($this->fk_commande) ? 'NULL' : "'" . $this->db->escape($this->fk_commande) . "'") . ",";
			$sql .= " " . (! isset($this->spec) ? 'NULL' : "'" . $this->db->escape($this->spec) . "'") . ",";
			$sql .= " " . $user->id . ",";
			$sql .= " '" . $this->db->idate(dol_now()) . "',";
			$sql .= " " . $user->id . ",";
			$sql .= " '" . $this->db->idate(dol_now()) . "'";
			
			$sql .= ")";
			
			$this->db->begin();
			
			dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
			
			if (! $error) {
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "lead");
				
				if (! $notrigger) {
					// Uncomment this and change MYOBJECT to your own tag if you
					// want this action calls a trigger.
					
					// // Call triggers
					include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
					$interface = new Interfaces($this->db);
					$result = $interface->run_triggers('AFFAIRES_DET_CREATE', $this, $user, $langs, $conf);
					if ($result < 0) {
						$error ++;
						$this->errors = $interface->errors;
					}
					// // End call triggers
				}
			}
			
			// Commit or rollback
			if ($error) {
				foreach ( $this->errors as $errmsg ) {
					dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
				}
				$this->db->rollback();
				return - 1 * $error;
			} else {
				$this->db->commit();
				return $this->id;
			}
		}
	}
		
	/**
	 * Load object in memory from the database
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch($id) {
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.fk_affaires,";
		$sql .= " t.fk_gamme,";
		$sql .= " t.fk_silhouette,";
		$sql .= " t.fk_genre,";
		$sql .= " t.fk_carrosserie,";
		$sql .= " t.fk_status,";
		$sql .= " t.fk_marque_trt,";
		$sql .= " t.fk_motif,";
		$sql .= " t.spec,";
		$sql .= " t.fk_commande,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";
		
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
		$sql .= " WHERE t.rowid = " . $id;
			
		dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				
				$this->id = $obj->rowid;
				$this->fk_affaires = $obj->fk_affaires;
				$this->fk_gamme = $obj->fk_gamme;
				$this->fk_silhouette = $obj->fk_silhouette;
				$this->fk_genre = $obj->fk_genre;
				$this->fk_carrosserie = $obj->fk_carrosserie;
				$this->fk_status = $obj->fk_status;
				$this->fk_marque_trt = $obj->fk_marque_trt;
				$this->fk_motifs = $obj->fk_motifs;
				$this->spec = $obj->spec;
				$this->fk_commande = $obj->fk_commande;
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate($obj->datec);
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->tms = $this->db->jdate($obj->tms);
				
				$this->gamme_label = $this->gamme[$this->fk_gamme]->gamme;
				$this->silhouette_label = $this->silhouette[$this->fk_silhouette]->silouhette;
				$this->genre_label = $this->genre[$this->fk_genre]->genre;
				$this->carrosserie_label = $this->carrosserie[$this->fk_carrosserie]->carrosserie;
				$this->status_label = $this->status[$this->fk_c_status];
				$this->marque_trt_label = $this->marque_trt[$this->fk_marque_trt]->marque;
			}
			$this->db->free($resql);
			
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
			return - 1;
		}
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
	function fetch_all($sortorder, $sortfield, $limit, $offset, $filter = array()) {
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.fk_affaires,";
		$sql .= " t.fk_gamme,";
		$sql .= " t.fk_silhouette,";
		$sql .= " t.fk_genre,";
		$sql .= " t.fk_carrosserie,";
		$sql .= " t.fk_status,";
		$sql .= " t.fk_marque_trt,";
		$sql .= " t.fk_motifs,";
		$sql .= " t.spec,";
		$sql .= " t.fk_commande,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";
		
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
		$sql .= " WHERE 1";
		
		if (is_array($filter)) {
			foreach ( $filter as $key => $value ) {
				if (($key == 't.fk_affaires') || ($key == 't.rowid') || ($key == 't.fk_gamme') || ($key == 't.fk_silhouette') || ($key == 't.fk_genre')
				|| ($key == 't.fk_carrosserie') || ($key == 't.fk_status')|| ($key == 't.fk_marque_trt') || ($key == 't.fk_commande')) {
					$sql .= ' AND ' . $key . ' = ' . $value;
				} elseif ($key == 't.fk_status !IN') {
					$sql .= ' AND t.fk_status NOT IN (' . $value . ')';
				} elseif ($key == 't.rowid !IN') {
					$sql .= ' AND t.rowid NOT IN (' . $value . ')';
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
			
		dol_syslog(get_class($this) . "::fetch_all sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows($resql);
			while ( $obj = $this->db->fetch_object($resql) ) {
				$line = new Affaires_det($this->db);
				$line->id = $obj->rowid;
				$line->fk_affaires = $obj->fk_affaires;
				$line->fk_gamme = $obj->fk_gamme;
				$line->fk_silhouette = $obj->fk_silhouette;
				$line->fk_genre = $obj->fk_genre;
				$line->fk_carrosserie = $obj->fk_carrosserie;
				$line->fk_status = $obj->fk_status;
				$line->fk_marque_trt = $obj->fk_marque_trt;
				$line->fk_motifs = $obj->fk_motifs;
				$line->fk_commande = $obj->fk_commande;
				$line->spec = $obj->spec;
				$line->fk_user_author = $obj->fk_user_author;
				$line->datec = $this->db->jdate($obj->datec);
				$line->fk_user_mod = $obj->fk_user_mod;
				$line->tms = $this->db->jdate($obj->tms);
				
				$line->gamme_label = $line->gamme[$line->fk_gamme]->gamme;
				$line->silhouette_label = $line->silhouette[$line->fk_silhouette]->silhouette;
				$line->genre_label = $line->genre[$line->fk_genre]->genre;
				$line->carrosserie_label = $line->carrosserie[$line->fk_carrosserie]->carrosserie;
				$line->status_label = $line->status[$line->fk_c_status];
				$line->marque_trt_label = $line->marque_trt[$line->fk_marque_trt]->marque;
									
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
		
	/**
	 * Update object into database
	 *
	 * @param User $user that modifies
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function update($user = null, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;
	
		if (isset($this->fk_affaires))
			$this->fk_affaires = trim($this->fk_affaires);
		if (isset($this->fk_game))
			$this->fk_gamme = trim($this->fk_gamme);
		if (isset($this->fk_silhouette))
			$this->fk_silhouette = trim($this->fk_silhouette);
		if (isset($this->fk_genre))
			$this->fk_genre = trim($this->fk_genre);
		if (isset($this->fk_carrosserie))
			$this->fk_carrosserie = trim($this->fk_carrosserie);
		if (isset($this->fk_status))
			$this->fk_status = trim($this->fk_status);
		if (isset($this->fk_marque_trt))
			$this->fk_marque_trt = trim($this->fk_marque_trt);
		if (isset($this->fk_motifs))
			$this->fk_motifs = trim($this->fk_motifs);
		if (isset($this->fk_commande))
			$this->fk_commande = trim($this->fk_commande);
		if (isset($this->spec))
			$this->spec = trim($this->spec);
		
			
		// Check parameters
		// Put here code to add control on parameters values
			
		if (empty($this->fk_affaires)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadRefInt'));
		}
		
		if (empty($this->fk_gamme)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadRefInt'));
		}
			
		if (empty($this->fk_genre)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadRefInt'));
		}
			
		if (empty($this->fk_silhouette)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadRefInt'));
		}
		
		if (empty($this->fk_carrosserie)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadRefInt'));
		}
			
		if (empty($this->fk_status)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadRefInt'));
		}
			
		if (empty($this->fk_user_author)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadRefInt'));
		}
	
		if (empty($this->datec)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadRefInt'));
		}
			
		if (!empty($this->fk_commande)) {
			$error ++;
			$this->errors[] = "un chassis commandé ne peut etre modifié";
		}
		
		if (! $error) {
			// Update request
			$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET";
			
			$sql .= " fk_affaires=" . (isset($this->fk_affaires) ? "'" . $this->db->escape($this->fk_affaires) . "'" : "null") . ",";
			$sql .= " fk_gamme=" . (isset($this->fk_gamme) ? "'" . $this->db->escape($this->fk_gamme) . "'" : "null") . ",";
			$sql .= " fk_silhouette=" . (isset($this->fk_silhouette) ? "'" . $this->db->escape($this->fk_silhouette) . "'" : "null") . ",";
			$sql .= " fk_genre=" . (isset($this->fk_genre) ? "'" . $this->db->escape($this->fk_genre) . "'" : "null") . ",";
			$sql .= " fk_carrosserie=" . (isset($this->fk_carrosserie) ? "'" . $this->db->escape($this->fk_carrosserie) . "'" : "null") . ",";
			$sql .= " fk_status=" . (isset($this->fk_status) ? "'" . $this->db->escape($this->fk_status) . "'" : "null") . ",";
			$sql .= " fk_marque_trt=" . (isset($this->fk_marque_trt) ? "'" . $this->db->escape($this->fk_marque_trt) . "'" : "null") . ",";
			$sql .= " fk_motifs=" . (isset($this->fk_motifs) ? "'" . $this->db->escape($this->fk_motifs) . "'" : "null") . ",";
			$sql .= " fk_commmande=" . (isset($this->fk_commande) ? "'" . $this->db->escape($this->fk_commande) . "'" : "null") . ",";
			$sql .= " spec=" . (isset($this->spec) ? "'" . $this->db->escape($this->spec) . "'" : "null") . ",";
			$sql .= " fk_user_mod=" . $user->id . ",";
			$sql .= " tms='" . $this->db->idate(dol_now()) . "'";
				
			$sql .= " WHERE rowid=" . $this->id;
		
			$this->db->begin();
			
			dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}
			
		if (! $error) {
			if (! $notrigger) {
				// // Call triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface = new Interfaces($this->db);
				$result = $interface->run_triggers('AFFAIRES_DET_MODIFY', $this, $user, $langs, $conf);
				if ($result < 0) {
					$error ++;
					$this->errors = $interface->errors;
				}
				// // End call triggers
			}
		}
			
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
			
	}
		
	/**
	 * Delete object in database
	 *
	 * @param User $user that deletes
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;
		
		$this->db->begin();
			
		if (!empty($this->fk_commande)) {
			$error ++;
			$this->errors[] = "un chassis commandé ne peut etre supprimé";
		}
			
		if (! $error) {
			if (! $notrigger) {
				// // Call triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface = new Interfaces($this->db);
				$result = $interface->run_triggers('AFFAIRES_DET_DELETE', $this, $user, $langs, $conf);
				if ($result < 0) {
					$error ++;
					$this->errors = $interface->errors;
				}
				// // End call triggers
			}
		}
			
		if (! $error) {
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element;
			$sql .= " WHERE rowid=" . $this->id;
			
			dol_syslog(get_class($this) . "::delete sql=" . $sql);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}
			
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}
	
	function vh_tile($whithcustomerdetails=0){
		
		if($this->fk_genre==1){
			$img = img_picto('porteur', 'porteur.png.png@affaires');
		}elseif($this->fk_genre==2){
			$img = img_picto('porteur', 'tracteur.png@affaires');
		}
		if($this->fk_status == 5){
			$color = '#56ff56';
			$color2= '#00ff00';
		}elseif($this->fk_status== 7){
			$color = '#ff5656';
			$color2= '#ff0000';
		}elseif($this->fk_status== 11){
			$color = '#ffaa56';
			$color2= '#ff7f00';
		}elseif($this->fk_status== 6 && $this->fk_commande>0){
			$color = '#aad4ff';
			$color2= '#56aaff';
		}elseif($this->fk_status== 6 && $this->fk_commande<1){
			$color = '#aa56ff';
			$color2= '#7f00ff';
		}else{
			$color = '#cccccc';
			$color2= '#b2b2b2';
		}
 		$return = '<div align="left" draggable="true"; ondragstart="drag(event);" id="'. $line->id . '" style="background:' . $color .'; background: -webkit-gradient(linear, left top, left bottom, from('.$color.'), to('.$color2.')) ';
 		$return.= ';border-radius:6px; margin-bottom: 3px; width:100%; height:23px; padding-left:10px; padding-top:5px">';
 		$return.= $img . ' ' . $this->gamme[$this->fk_gamme]->gamme . ' - ' . $this->silhouette_label . ' - ' . $this->carrosserie_label;
 		if($this->fk_status==6){
 			$return.= ' - SpÃ©cification: ' . $this->spec;
 			if($this->fk_commande > 0){
 				dol_include_once('/affaires/class/commandevolvo.class.php');
 				$cmd = new CommandeVolvo($this->db);
 				$cmd->fetch($this->fk_commande);
 				$return.= ' - Commande: ' . $cmd->getNomUrl(1) . ' du ' . dol_print_date($cmd->date,'day') . ' - ' . $cmd->LibStatut($cmd->statut, $cmd->billed, 2);
 			}
 		}
 		$return.= '</div>';
		
//		$return = var_dump($this);
		
		return $return;
	}
}
