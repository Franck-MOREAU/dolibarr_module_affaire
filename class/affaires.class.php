<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
class Affaires extends CommonObject
{
	public $db; // !< To store db handler
	public $error; // !< To return error code (or message)
	public $errors = array(); // !< To return several error codes (or messages)
	public $element = 'affaires'; // !< Id that identify managed objects
	public $table_element = 'affaires';
	public $fields = array(
			'rowid' => array(
					'type' => 'integer',
					'label' => 'TechnicalID',
					'enabled' => 1,
					'visible' => - 2,
					'notnull' => 1,
					'index' => 1,
					'position' => 1,
					'comment' => 'Id'
			),
			'ref' => array(
					'type' => 'varchar(128)',
					'label' => 'Ref',
					'enabled' => 1,
					'visible' => 1,
					'notnull' => 1,
					'showoncombobox' => 1,
					'index' => 1,
					'position' => 10,
					'searchall' => 1,
					'comment' => 'Reference of object'
			),
			'fk_user_resp' => array(
					'type' => 'integer:User:user/class/user.class.php',
					'label' => 'userresp',
					'visible' => 1,
					'enabled' => 1,
					'position' => 50,
					'notnull' => - 1,
					'index' => 1
			),
			'fk_soc' => array(
					'type' => 'integer:Societe:societe/class/societe.class.php',
					'label' => 'ThirdParty',
					'visible' => 1,
					'enabled' => 1,
					'position' => 50,
					'notnull' => - 1,
					'index' => 1,
					'searchall' => 1,
					'help' => "LinkToThirparty"
			),
			'fk_ctm' => array(
					'type' => 'integer:Societe:societe/class/societe.class.php',
					'label' => 'ctm',
					'visible' => 1,
					'enabled' => 1,
					'position' => 50,
					'notnull' => - 1,
					'index' => 1
			),
			'fk_c_type' => array(
					'type' => 'integer',
					'label' => 'cv',
					'visible' => 1,
					'enabled' => 1,
					'position' => 50,
					'notnull' => - 1,
					'index' => 1
			),
			'year' => array(
					'type' => 'integer',
					'label' => 'year',
					'visible' => 1,
					'enabled' => 1,
					'position' => 50,
					'notnull' => - 1,
					'index' => 1
			),
			'description' => array(
					'type' => 'varchar(500)',
					'label' => 'description',
					'visible' => 1,
					'enabled' => 1,
					'position' => 50,
					'notnull' => - 1,
					'index' => 1
			),
			'datec' => array(
					'type' => 'datetime',
					'label' => 'DateCreation',
					'visible' => - 2,
					'enabled' => 1,
					'position' => 500,
					'notnull' => 1
			),
			'tms' => array(
					'type' => 'timestamp',
					'label' => 'DateModification',
					'visible' => - 2,
					'enabled' => 1,
					'position' => 501,
					'notnull' => 1
			),
			'fk_user_author' => array(
					'type' => 'integer',
					'label' => 'UserAuthor',
					'visible' => - 2,
					'enabled' => 1,
					'position' => 510,
					'notnull' => 1
			),
			'fk_user_mod' => array(
					'type' => 'integer',
					'label' => 'UserModif',
					'visible' => - 2,
					'enabled' => 1,
					'position' => 511,
					'notnull' => - 1
			)
	);
	public $id;
	public $ref;
	public $fk_user_resp;
	public $fk_soc;
	public $fk_ctm;
	public $fk_c_type;
	public $type_label;
	public $year;
	public $description;
	public $fk_user_author;
	public $datec;
	public $fk_user_mod;
	public $tms;
	public $lines = array();
	public $type = array();
	public $affaires_det = array();
	public $contremarque;
	function __construct($db) {
		$this->db = $db;

		$result_type = $this->loadType();

		return ($result_type);
	}
	private function loadType() {
		global $langs;

		$sql = "SELECT rowid, code, label FROM " . MAIN_DB_PREFIX . "c_affaires_type  WHERE active=1";
		dol_syslog(get_class($this) . "::_load_type sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $obj = $this->db->fetch_object($resql) ) {
				$label = $obj->label;
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
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('AffairesRef'));
		}
		if (empty($this->fk_user_resp)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('AffairesCommercial'));
		}
		if (empty($this->fk_soc)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Customer'));
		}
		if (empty($this->fk_c_type)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('AffairesType'));
		}
		if (empty($this->year)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Affairesyear'));
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
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "affaires");

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
	function fetch($id, $nodetail = 0) {
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
				if ($this->fk_ctm > 0) {
					require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
					$soc = new Societe($this->db);
					$soc->fetch($this->fk_ctm);
					$this->contremarque = $soc;
				}
				if (empty($nodetail)) {
					// loading affaires lines into affaires_det array of object
					$det = new Affaires_det($this->db);
					$det->fetch_all('ASC', 'fk_status, fk_commande', 0, 0, array(
							'det.fk_affaires' => $this->id
					));
					$this->affaires_det = array();
					foreach ( $det->lines as $line ) {
						$this->affaires_det[$line->id] = $line;
					}
				}
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error[] = "Error " . $this->db->lasterror();
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
	function fetch_all($sortorder, $sortfield, $limit, $offset, $filter = array(), $nodetail = 0) {
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
				if (!empty($value)) {
					if (($key == 't.fk_c_status') || ($key == 't.rowid') || ($key == 't.fk_soc') || ($key == 't.fk_ctm') || ($key == 't.fk_c_type') || ($key == 't.fk_user_resp') || ($key == 't.year')) {
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
			$this->lines = array();

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
				if ($line->fk_ctm > 0)
					$line->contremarque = $line->fetchObjectFrom('societe', 'rowid', $line->ctm);

				if (empty($nodetail)) {
					// loading affaires lines into affaires_det array of object
					$det = new Affaires_det($this->db);
					$det->fetch_all('ASC', 'rowid', 0, 0, array(
							'det.fk_affaires' => $line->id
					));
					foreach ( $det->lines as $line_det ) {
						$line->affaires_det[$line_det->id] = $line_det;
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
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('AffairesRef'));
		}
		if (empty($this->fk_user_resp)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('AffairesCommercial'));
		}
		if (empty($this->fk_soc)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Customer'));
		}
		if (empty($this->fk_c_type)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('AffairesType'));
		}
		if (empty($this->year)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Affairesyear'));
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
		// if (! $error) {
		// foreach ($this->affaires_det as $affaires_det){
		// $res = $affaires_det->update($user);
		// if($res <1){
		// foreach ($affaires_det->errors as $det_error){
		// $this->errors[] = $det_error;
		// $error ++;
		// }
		// }
		// }
		// }

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
			foreach ( $this->affaires_det as $affaire_det ) {
				$res = $affaire_det->delete($user);
			}
			if ($res < 1) {
				$error ++;
				foreach ( $affaire_det->errors as $det_error ) {
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

		if ($withpicto == 0) {
			$result .= $this->ref . $lienfin;
		} elseif ($withpicto == 1) {
			$result .= img_object($label, $picto) . $this->ref . $lienfin;
		} else {
			$result .= $this->ref . img_object($label, $picto) . $lienfin;
		}
		return $result;
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen() {
		$this->initAsSpecimenCommon();
	}

	/**
	 * Returns the reference to the following non used Proposal used depending on the active numbering module
	 * defined into PROPALE_ADDON
	 *
	 * @return string Reference libre pour la propale
	 */
	function getNextNumRef() {
		global $conf, $langs;
		$langs->load("propal");

		if (! empty($conf->global->AFFAIRES_ADDON)) {
			$mybool = false;

			$file = $conf->global->AFFAIRES_ADDON . ".php";
			$classname = $conf->global->AFFAIRES_ADDON;

			// Include file with class
			$dir = dol_buildpath('/affaires/core/modules/affaires/');

			// Load file with numbering class (if found)
			$mybool |= @include_once $dir . $file;

			if (! $mybool) {
				dol_print_error('', "Failed to include file " . $file);
				return '';
			}

			$obj = new $classname();
			$numref = "";
			$numref = $obj->getNextValue($this);

			if ($numref != "") {
				return $numref;
			} else {
				$this->error = $obj->error;
				// dol_print_error($db,"Propale::getNextNumRef ".$obj->error);
				return "";
			}
		} else {
			$langs->load("errors");
			print $langs->trans("Error") . " " . $langs->trans("ErrorModuleSetupNotComplete");
			return "";
		}
	}
	public function copyExtrafieldsValuesFromObjToObjLinked(CommonObject $srcobj, &$objtupdated = array()) {
		require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
		$extra = new ExtraFields($this->db);

		$src_array_options = array();
		$src_array_options = $extra->fetch_name_optionals_label($srcobj->table_element);

		$srcobj->fetch_optionals();

		$this->db->begin();
		if (is_array($src_array_options) && count($src_array_options) > 0) {
			$srcobj->fetchObjectLinked();
			if (is_array($srcobj->linkedObjects) && count($srcobj->linkedObjects) > 0) {
				foreach ( $srcobj->linkedObjects as $objectype => $objArray ) {
					if (is_array($objArray) && count($objArray) > 0) {
						foreach ( $objArray as $destobjid => $destobj ) {
							if (! empty($destobj->table_element)) {
								if (! array_key_exists($destobj->table_element, $objtupdated) || ! array_key_exists($destobj->id, $objtupdated[$destobj->table_element])) {
									$dest_array_options = array();
									$dest_array_options = $extra->fetch_name_optionals_label($destobj->table_element);
									if (is_array($dest_array_options) && count($dest_array_options) > 0) {
										$final_array = array_intersect_assoc($src_array_options, $dest_array_options);

										if (is_array($final_array) && count($final_array) > 0) {
											$destobj->fetch_optionals();
											foreach ( $final_array as $colname => $colabel ) {
												$destobj->array_options['options_' . $colname] = $srcobj->array_options['options_' . $colname];
												$result = $destobj->insertExtraFields();
												if ($result < 0) {
													$this->errors[] = $destobj->error;
													$error ++;
												}
											}
											$objtupdated[$destobj->table_element][$destobj->id] = '';
											$result = $this->copyExtrafieldsValuesFromObjToObjLinked($destobj, $objtupdated);
										}
									}
								}
							}
						}
					}
				}
			}
		}
		if (! $error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return - 1;
		}
	}
}
class Affaires_det extends CommonObject
{
	public $db; // !< To store db handler
	public $error; // !< To return error code (or message)
	public $errors = array(); // !< To return several error codes (or messages)
	public $element = 'affaires_det'; // !< Id that identify managed objects
	public $table_element = 'affaires_det'; // !< Name of table without prefix where object is stored
	public $id;
	public $fk_affaires;
	public $fk_gamme;
	public $gamme_label;
	public $gamme = array();
	public $gamme_dict = array();
	public $status = array();
	public $status_dict = array();
	public $fk_silhouette;
	public $silhouette_label;
	public $silhouette = array();
	public $silhouette_dict = array();
	public $fk_genre;
	public $genre_label;
	public $genre = array();
	public $genre_dict = array();
	public $fk_carrosserie;
	public $carrosserie_label;
	public $carrosserie = array();
	public $carrosserie_dict = array();
	public $fk_status;
	public $status_label;
	public $fk_marque_trt;
	public $marque_trt_label;
	public $marque_trt = array();
	public $marque_trt_dict = array();
	public $fk_motifs;
	public $fk_motifs_array = array();
	public $motifs = array();
	public $motifs_dict = array();
	public $spec;
	public $fk_commande;
	public $fk_user_author;
	public $datec;
	public $fk_user_mod;
	public $tms;
	public $lines = array();
	public $soc_url = '';
	public $ctm_url = '';
	public $ref_url = '';
	public $year;
	public $usrname = '';
	public $cv_type_label = '';
	public $listofreferent = array();
	function __construct($db) {
		global $conf;

		$this->db = $db;

		$result_status = $this->loadStatus();
		$result_gamme = $this->loadGamme();
		$result_genre = $this->loadGenre();
		$result_carrosserie = $this->loadCarrosserie();
		$result_silhouette = $this->loadSilhouette();
		$result_marques = $this->loadMarques();
		$result_motifs = $this->loadMotifs();

		if (! empty($conf->contrat->enabled)) {
			$this->listofreferent['contract'] = array(
					'title' => "Contrat",
					'class' => 'Contrat',
					'table' => 'contrat',
					'test' => $conf->contrat->enabled && $user->rights->contrat->lire
			);
		}
		if (! empty($conf->commande->enabled)) {
			$this->listofreferent['orders'] = array(
					'title' => "Commande",
					'class' => 'Commande',
					'table' => 'commande',
					'test' => $conf->commande->enabled && $user->rights->commande->lire
			);
		}

		return ($result_status && $result_carrosserie && $result_gamme && $result_genre && $result_marques && $result_motifs && $result_silhouette);
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

				$label = $langs->trans('AffairesStatus_' . $obj->code);
				if ($label == 'AffairesStatus_' . $obj->code) {
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
				$this->gamme_dict[$obj->rowid] = $obj->gamme;
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
				$this->silhouette_dict[$obj->rowid] = $obj->silhouette;
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
				$this->genre_dict[$obj->rowid] = $obj->genre;
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
				$this->carrosserie_dict[$obj->rowid] = $obj->carrosserie;
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
				$this->marque_trt_dict[$obj->rowid] = $obj->marque;
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
		$sql = "SELECT rowid, motif, active FROM " . MAIN_DB_PREFIX . "c_affaires_motif_perte_affaires WHERE active=1";
		dol_syslog(get_class($this) . "::_load_marques sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $obj = $this->db->fetch_object($resql) ) {
				$this->motifs[$obj->rowid] = $obj;
				$this->motifs_dict[$obj->rowid] = $obj->motif;
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
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('AffairesRefInt'));
		}

		if (empty($this->fk_gamme)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Gamme'));
		}

		if (empty($this->fk_genre)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Genre'));
		}

		if (empty($this->fk_silhouette)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Silhouette'));
		}

		if (empty($this->fk_carrosserie)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Carrosserie'));
		}

		if (empty($this->fk_status)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Status'));
		}
		if (is_array($this->fk_motifs_array) && count($this->fk_motifs_array) > 0) {
			$this->fk_motifs = implode(',', $this->fk_motifs_array);
		}

		if (! $error) {
			// Insert request
			$sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . "(";

			$sql .= "fk_affaires,";
			$sql .= "fk_gamme,";
			$sql .= "fk_silhouette,";
			$sql .= "fk_genre,";
			$sql .= "fk_carrosserie,";
			$sql .= "fk_status,";
			$sql .= "fk_marque_trt,";
			$sql .= "fk_motifs,";
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
			$sql .= " " . (empty($this->fk_marque_trt) ? 'NULL' : "'" . $this->db->escape($this->fk_marque_trt) . "'") . ",";
			$sql .= " " . (empty($this->fk_motifs) ? 'NULL' : "'" . $this->db->escape($this->fk_motifs) . "'") . ",";
			$sql .= " " . (empty($this->fk_commande) ? 'NULL' : "'" . $this->db->escape($this->fk_commande) . "'") . ",";
			$sql .= " " . (empty($this->spec) ? 'NULL' : "'" . $this->db->escape($this->spec) . "'") . ",";
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
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

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
		} else {
			dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
			return - 1;
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
		$sql .= " t.fk_motifs,";
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
				if (! empty($this->fk_motifs)) {
					$this->fk_motifs_array = explode(',', $obj->fk_motifs);
				}
				$this->spec = $obj->spec;
				$this->fk_commande = $obj->fk_commande;
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate($obj->datec);
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->tms = $this->db->jdate($obj->tms);

				$this->gamme_label = $this->gamme[$this->fk_gamme]->gamme;
				$this->silhouette_label = $this->silhouette[$this->fk_silhouette]->silhouette;
				$this->genre_label = $this->genre[$this->fk_genre]->genre;
				$this->carrosserie_label = $this->carrosserie[$this->fk_carrosserie]->carrosserie;
				$this->status_label = $this->status[$this->fk_c_status];
				$this->marque_trt_label = $this->marque_trt[$this->fk_marque_trt]->marque;

				$this->soc_url = '';
				if (! empty($obj->socid)) {
					$socstatic = new Societe($this->db);
					$socstatic->fetch($obj->socid);
					$this->soc_url = $socstatic->getNomUrl();
				}
				$this->ctm_url = '';
				if (! empty($obj->ctmid)) {
					$socstatic = new Societe($this->db);
					$socstatic->fetch($obj->ctmid);
					$this->ctm_url = $socstatic->getNomUrl();
				}

				$affstatic = new Affaires($this->db);
				$affstatic->fetch($obj->fk_affaires, 1);
				$this->ref_url = $affstatic->getNomUrl();
				$this->cv_type_label = $affstatic->type_label;

				$this->year = $affstatic->year;
				$this->usrname = $obj->usrname;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->errors[] = "Error " . $this->db->lasterror();
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
		require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

		global $langs;
		$sql = "SELECT DISTINCT ";
		$sql .= " det.rowid,";
		$sql .= " det.fk_affaires,";
		$sql .= " det.fk_gamme,";
		$sql .= " det.fk_silhouette,";
		$sql .= " det.fk_genre,";
		$sql .= " det.fk_carrosserie,";
		$sql .= " det.fk_status,";
		$sql .= " det.fk_marque_trt,";
		$sql .= " det.fk_motifs,";
		$sql .= " det.spec,";
		$sql .= " det.fk_commande,";
		$sql .= " det.fk_user_author,";
		$sql .= " det.datec,";
		$sql .= " det.fk_user_mod,";
		$sql .= " det.tms,";
		$sql .= " CONCAT(usr.lastname,' ',usr.firstname) as usrname,";
		$sql .= " soc.rowid as socid,";
		$sql .= " ctm.rowid as ctmid,";
		$sql .= " t.year";

		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as det";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'affaires as t ON t.rowid=det.fk_affaires';
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'societe as soc ON soc.rowid=t.fk_soc';
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . 'societe as ctm ON ctm.rowid=t.fk_ctm';
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'user as usr ON usr.rowid=t.fk_user_resp';
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'c_affaires_type as cv ON cv.rowid=t.fk_c_type';
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'c_affaires_genre as genre ON genre.rowid=det.fk_genre';
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'c_affaires_gamme as gamme ON gamme.rowid=det.fk_gamme';
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'c_affaires_silhouette as silhouette ON silhouette.rowid=det.fk_silhouette';
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'c_affaires_carrosserie as carrosserie ON carrosserie.rowid=det.fk_carrosserie';
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'c_affaires_status as status ON status.rowid=det.fk_status';
		$sql .= " WHERE 1";

		if (is_array($filter)) {
			foreach ( $filter as $key => $value ) {
				if (! empty($value)) {
					if (($key == 'det.fk_affaires') || ($key == 'det.rowid') || ($key == 'det.fk_gamme') || ($key == 'det.fk_silhouette') || ($key == 'det.fk_genre') || ($key == 'det.fk_carrosserie') || ($key == 't.fk_status') || ($key == 'det.fk_marque_trt') || ($key == 'det.fk_commande') || $key == 't.fk_c_type' || $key == 't.year') {
						$sql .= ' AND ' . $key . ' = ' . $value;
					} elseif ($key == 'det.fk_status !IN') {
						$sql .= ' AND det.fk_status NOT IN (' . $value . ')';
					} elseif ($key == 'det.rowid !IN') {
						$sql .= ' AND det.rowid NOT IN (' . $value . ')';
					} else {
						$sql .= ' AND ' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
					}
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
					if (! empty($this->fk_motifs)) {
						$this->fk_motifs_array = explode(',', $obj->fk_motifs);
					}
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
					$line->status_label = $line->status[$line->fk_status];

					$line->soc_url = '';
					if (! empty($obj->socid)) {
						$socstatic = new Societe($this->db);
						$socstatic->fetch($obj->socid);
						$line->soc_url = $socstatic->getNomUrl();
					}
					$line->ctm_url = '';
					if (! empty($obj->ctmid)) {
						$socstatic = new Societe($this->db);
						$socstatic->fetch($obj->ctmid);
						$line->ctm_url = $socstatic->getNomUrl();
					}

					$affstatic = new Affaires($this->db);
					$affstatic->fetch($obj->fk_affaires, 1);
					$line->ref_url = $affstatic->getNomUrl();
					$line->cv_type_label = $affstatic->type_label;

					$line->year = $affstatic->year;
					$line->usrname = $obj->usrname;

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
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('AffairesRefInt'));
		}

		if (empty($this->fk_gamme)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Gamme'));
		}

		if (empty($this->fk_genre)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Genre'));
		}

		if (empty($this->fk_silhouette)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Silhouette'));
		}

		if (empty($this->fk_carrosserie)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Carrosserie'));
		}

		if (empty($this->fk_status)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Status'));
		}

		$objstatic = new self($this->db);
		$objstatic->fetch($this->id);
		if (! empty($objstatic->fk_commande)) {
			$error ++;
			$this->errors[] = "un chassis commandé ne peut etre modifié";
		}
		unset($objstatic);

		if (is_array($this->fk_motifs_array) && count($this->fk_motifs_array) > 0) {
			$this->fk_motifs = implode(',', $this->fk_motifs_array);
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
			$sql .= " fk_marque_trt=" . (! empty($this->fk_marque_trt) ? "'" . $this->db->escape($this->fk_marque_trt) . "'" : "null") . ",";
			$sql .= " fk_motifs=" . (! empty($this->fk_motifs) ? "'" . $this->db->escape($this->fk_motifs) . "'" : "null") . ",";
			$sql .= " fk_commande=" . (! empty($this->fk_commande) ? "'" . $this->db->escape($this->fk_commande) . "'" : "null") . ",";
			$sql .= " spec=" . (! empty($this->spec) ? "'" . $this->db->escape($this->spec) . "'" : "null") . ",";
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

		if (! empty($this->fk_commande)) {
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
	public function vh_tile($whithcustomerdetails = 0, $withlinktoaffaire = 0) {
		global $user;

		if ($this->fk_genre == 1) {
			$img = img_picto('porteur', 'porteur.png@affaires');
		} elseif ($this->fk_genre == 2) {
			$img = img_picto('porteur', 'tracteur.png@affaires');
		}
		if ($this->fk_status == 5) {
			$color = '#56ff56';
			$color2 = '#00ff00';
		} elseif ($this->fk_status == 7) {
			$color = '#ff5656';
			$color2 = '#ff0000';
		} elseif ($this->fk_status == 11) {
			$color = '#ffaa56';
			$color2 = '#ff7f00';
		} elseif ($this->fk_status == 6 && $this->fk_commande > 0) {
			$color = '#aad4ff';
			$color2 = '#56aaff';
		} elseif ($this->fk_status == 6 && $this->fk_commande < 1) {
			$color = '#aa56ff';
			$color2 = '#7f00ff';
		} else {
			$color = '#cccccc';
			$color2 = '#b2b2b2';
		}

		$return = '<div id="vh_' . $this->id . '" style="background:' . $color . '; ';
		$return .= ' background: -webkit-gradient(linear, left top, left bottom, from(' . $color . '), to(' . $color2 . ')); ';
		$return .= ' border-radius:6px; margin-bottom: 3px; width:100%; height:23px; padding-left:10px; padding-top:5px">';

		// Info veh
		$return .= '<div style="display: inline-block; ">';
		if (! empty($withlinktoaffaire)) {
			$objectstaaff = new Affaires($this->db);
			$ret = $objectstaaff->fetch($this->fk_affaires, 1);
			if ($ret < 0) {
				setEventMessage(null, $object->errors, 'errors');
			} else {
				$return .= $objectstaaff->getNomUrl() . ' - ';
			}
		}
		// var_dump($this->silhouette_label);
		$return .= $img . ' ' . $this->gamme_label . ' - ' . $this->silhouette_label . ' - ' . $this->carrosserie_label;
		if ($this->fk_status == 6) {
			$return .= ' - Spécification: ' . $this->spec;
			if ($this->fk_commande > 0) {
				dol_include_once('/affaires/volvo/class/commandevolvo.class.php');
				$cmd = new CommandeVolvo($this->db);
				$cmd->fetch($this->fk_commande);
				$return .= ' - Commande: ' . $cmd->getNomUrl(0) . ' du ' . dol_print_date($cmd->date, 'day') . ' - ' . $cmd->LibStatut($cmd->statut, $cmd->billed, 2);
			}
		}
		$return .= '</div>';

		// Button
		$return .= '<div style="display: inline-block; float:right;">';
		if ($user->rights->affaires->write && ! ($this->fk_status == 6 && $this->fk_commande > 0)) {
			$return .= '<a href="' . dol_buildpath('/affaires/form/card.php', 2) . '?id=' . $this->fk_affaires . '&vehid=' . $this->id . '&action=classveh" style="color:black"><i class="fa fa-money paddingright"></i></a>';
		}
		if ($user->rights->affaires->write && $this->fk_status == 6 && $this->fk_commande < 1) {
			$return .= '<a href="javascript:popCreateOrder(' . $this->id . ')" style="color:black"><i class="fa fa-truck paddingright"></i></a>';
		}
		if ($user->rights->affaires->write && ! ($this->fk_status == 6 && $this->fk_commande > 0)) {
			$return .= '<a href="javascript:popCreateAffaireDet(' . $this->id . ')" style="color:black"><i class="fa fa-pencil-square paddingright"></i></a>';
		}
		if ($user->admin && ! ($this->fk_status == 6 && $this->fk_commande > 0)) {
			$return .= '<a href="' . dol_buildpath('/affaires/form/card.php', 2) . '?id=' . $this->fk_affaires . '&vehid=' . $this->id . '&action=deleteveh" style="color:black"><i class="fa fa-trash paddingright"></i></a>';
		}
		$return .= '</div>';

		$return .= '</div>';

		// $return = var_dump($this);

		return $return;
	}

	/**
	 *
	 * @param int $vehid
	 * @return number
	 */
	public function getAmountOrder($vehid = 0) {
		$staticself = new self($this->db);
		$staticself->fetch($vehid);
		if ($staticself->fk_commande > 0) {
			dol_include_once('/affaires/volvo/class/commandevolvo.class.php');
			$cmd = new CommandeVolvo($this->db);
			$cmd->fetch($staticself->fk_commande);
			return $cmd->total_ht;
		} else {
			return 0;
		}
	}
	public function getReglementid() {
		if ($this->fk_genre == 1) {
			return 11;
		} elseif ($this->fk_genre == 2) {
			return 9;
		} else {
			return 10;
		}
	}

	/**
	 *
	 * @param int $vehid
	 * @return number
	 */
	public function getMarginReelDate($vehid = 0) {
		$asssts = new self($this->db);
		$asssts->fetch($vehid);
		if (! empty($asssts->fk_commande)) {
			return $asssts->getSumFactFourn($asssts->fk_commande, 1);
		} else {
			return 0;
		}
	}

	/**
	 *
	 * @param int $vehid
	 * @return number
	 */
	public function getMarginDate($vehid = 0) {
		$asssts = new self($this->db);
		$asssts->fetch($vehid);
		if (! empty($asssts->fk_commande)) {
			return $asssts->getSumFactFourn($asssts->fk_commande, 0);
		} else {
			return 0;
		}
	}
	public function getSumFactFournLn($orderlineid = 0, $solde = 0) {
		if (empty($orderlineid)) {
			$this->errors[] = get_class($this) . '::' . __METHOD__ . ' Missing $orderlineid';
			$error ++;
		}

		$sumtotalht = 0;

		$sql = 'SELECT SUM(fd.total_ht) as sumtotalht';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'commande as c';
		$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'commandedet as d ON d.fk_commande=c.rowid AND d.rowid=' . $orderlineid;
		$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'commandedet_extrafields as de ON de.fk_object=d.rowid';
		$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseurdet as cfd ON de.fk_supplierorderlineid=cfd.rowid';
		if (! empty($solde)) {
			$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseurdet_extrafields as cfde ON cfde.fk_object=cfd.rowid';
			$sql .= ' AND cfde.solde=' . $solde;
		}
		$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'facture_fourn_det_extrafields as fde ON fde.fk_supplierorderlineid=cfd.rowid';
		$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'facture_fourn_det as fd ON fd.rowid=fde.fk_object';
		$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'facture_fourn as ff ON ff.rowid=fd.fk_facture_fourn';

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->lines = array();
			$num = $this->db->num_rows($resql);
			if ($num > 0) {
				$obj = $this->db->fetch_object($resql);
				$sumtotalht = $obj->sumtotalht;
			}
		} else {
			$error ++;
			$this->errors[] = $this->db->lasterror;
		}

		if (! $error) {
			return $sumtotalht;
		} else {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::" . __METHOD__ . ' ' . $errmsg, LOG_ERR);
			}
			return - 99999;
		}
	}
	public function getSumFactFourn($orderid = 0, $solde = 0) {
		if (empty($orderid)) {
			$this->errors[] = get_class($this) . '::' . __METHOD__ . ' Missing $$orderid';
			$error ++;
		}

		$sumtotalht = 0;

		$sql = 'SELECT DISTINCT d.rowid ';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'commande as c';
		$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'commandedet as d ON d.fk_commande=c.rowid AND c.rowid=' . $orderid;

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->lines = array();
			$num = $this->db->num_rows($resql);
			if ($num > 0) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$result = $this->getSumFactFournLn($obj->rowid, $solde);
					if ($result === - 99999) {
						return $result;
					} else {
						$sumtotalht += $result;
					}
				}
			}
		} else {
			$error ++;
			$this->errors[] = $this->db->lasterror;
		}

		if (! $error) {
			return $sumtotalht;
		} else {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::" . __METHOD__ . ' ' . $errmsg, LOG_ERR);
			}
			return - 1 * $error;
		}
	}
	public function createcmd() {
		global $conf;

		dol_include_once('/affaires/volvo/class/commandevolvo.class.php');
		require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
		require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

		$object = new Affaires($this->db);
		$ret = $object->fetch($this->fk_affaires);
		if ($ret < 0) {
			$this->errors = $object->errors;
			return - 1;
		}
		$object->fetch_thirdparty();

		$user = new user($this->db);
		$product = new product($this->db);

		$user->fetch($object->fk_user_resp);

		$cmd = new CommandeVolvo($this->db);
		$cmd->socid = $object->thirdparty->id;
		$cmd->date = dol_now();
		$cmd->ref_client = $object->ref;
		$cmd->date_livraison = $this->datelivprev;
		$cmd->array_options['options_vnac'] = 0;
		$cmd->array_options['options_ctm'] = $object->fk_ctm;
		if (! empty($cmd->array_options['options_ctm'])) {
			dol_include_once('/societe/class/societe.class.php');
			$socctm = new Societe($this->db);
			$socctm->fetch($cmd->array_options['options_ctm']);
			$cmd->note_public = 'Contremarque: ' . $socctm->name . "\n";
		}
		$cmd->cond_reglement_id = $this->getReglementid();
		$rang = 1;
		$gamme = $this->gamme_label;
		$produit = new Product($this->db);
		$produit->fetch('', $gamme);

		$line = new OrderLine($db);
		$line->subprice = 0;
		$line->qty = 1;
		$line->tva_tx = 0;
		$line->fk_product = $produit->id;
		$line->pa_ht = 0;
		$line->rang = $rang;
		$rang ++;
		$cmd->lines[] = $line;

		if (count($this->obligatoire) > 0) {
			foreach ( $this->obligatoire as $art ) {
				$product->fetch($art);
				$line = new OrderLine($db);
				$line->subprice = $product->price;
				$line->qty = 1;
				$line->tva_tx = 0;
				$line->fk_product = $product->id;
				$line->pa_ht = $product->cost_price;
				$line->rang = $rang;
				$rang ++;
				$cmd->lines[] = $line;
			}
		}

		$line = new OrderLine($db);
		$line->desc = 'Sous-Total Véhicule';
		$line->subprice = 0;
		$line->qty = 99;
		$line->product_type = 9;
		$line->special_code = 104777;
		$line->rang = $rang;
		$rang ++;
		$cmd->lines[] = $line;

		$line = new OrderLine($db);
		$line->desc = 'Travaux Interne';
		$line->subprice = 0;
		$line->qty = 1;
		$line->product_type = 9;
		$line->special_code = 104777;
		$line->rang = $rang;
		$rang ++;
		$cmd->lines[] = $line;

		if (count($this->interne) > 0) {
			foreach ( $this->interne as $art ) {
				$product->fetch($art);
				$line = new OrderLine($db);
				$line->subprice = $product->price;
				$line->qty = 1;
				$line->tva_tx = 0;
				$line->fk_product = $product->id;
				$line->pa_ht = $product->cost_price;
				$line->rang = $rang;
				$rang ++;
				$cmd->lines[] = $line;
			}
		}

		$line = new OrderLine($db);
		$line->desc = 'Sous-Total Travaux Interne';
		$line->subprice = 0;
		$line->qty = 99;
		$line->product_type = 9;
		$line->special_code = 104777;
		$line->rang = $rang;
		$rang ++;
		$cmd->lines[] = $line;

		if (count($this->externe) > 0) {
			$line = new OrderLine($db);
			$line->desc = 'Travaux Externe';
			$line->subprice = 0;
			$line->qty = 1;
			$line->product_type = 9;
			$line->special_code = 104777;
			$line->rang = $rang;
			$rang ++;
			$cmd->lines[] = $line;

			foreach ( $this->externe as $art ) {
				$product->fetch($art);
				$line = new OrderLine($db);
				$line->subprice = $product->price;
				$line->qty = 1;
				$line->tva_tx = 0;
				$line->fk_product = $product->id;
				$line->pa_ht = $product->cost_price;
				$line->rang = $rang;
				$rang ++;
				$cmd->lines[] = $line;
			}
			$line = new OrderLine($db);
			$line->desc = 'Sous-Total Travaux Externe';
			$line->subprice = 0;
			$line->qty = 99;
			$line->product_type = 9;
			$line->special_code = 104777;
			$line->rang = $rang;
			$rang ++;
			$cmd->lines[] = $line;
		}

		if (count($this->divers) > 0) {
			$line = new OrderLine($db);
			$line->desc = 'Divers';
			$line->subprice = 0;
			$line->qty = 1;
			$line->product_type = 9;
			$line->special_code = 104777;
			$line->rang = $rang;
			$rang ++;
			$cmd->lines[] = $line;

			foreach ( $this->divers as $art ) {
				$product->fetch($art);
				$line = new OrderLine($db);
				$line->subprice = $product->price;
				$line->qty = 1;
				$line->tva_tx = 0;
				$line->fk_product = $product->id;
				$line->pa_ht = $product->cost_price;
				$line->rang = $rang;
				$rang ++;
				$cmd->lines[] = $line;
			}
			$line = new OrderLine($db);
			$line->desc = 'Sous-Total Divers';
			$line->subprice = 0;
			$line->qty = 99;
			$line->product_type = 9;
			$line->special_code = 104777;
			$line->rang = $rang;
			$rang ++;
			$cmd->lines[] = $line;
		}

		$line = new OrderLine($db);
		$line->desc = 'Commission Volvo';
		$line->subprice = 0;
		$line->qty = 1;
		$line->product_type = 9;
		$line->special_code = 104777;
		$line->rang = $rang;
		$rang ++;
		$cmd->lines[] = $line;

		$line = new OrderLine($db);
		$line->subprice = $this->commission;
		$line->qty = 1;
		$line->tva_tx = 0;
		$line->fk_product = $conf->global->VOLVO_COM;
		$line->pa_ht = 0;
		$line->rang = $rang;
		$rang ++;
		$cmd->lines[] = $line;

		$line = new OrderLine($db);
		$line->desc = 'Sous-Total Commission Volvo';
		$line->subprice = 0;
		$line->qty = 99;
		$line->product_type = 9;
		$line->special_code = 104777;
		$line->rang = $rang;
		$rang ++;
		$cmd->lines[] = $line;

		$this->db->begin();
		$idcommande = $cmd->create($user);
		if ($idcommande < 0) {
			array_push($this->errors, $cmd->error);
			$this->db->rollback();
			return - 1;
		} else {

			$result = $cmd->updatevhpriceandvnc($this->prixvente);
			if ($result < 0) {
				array_push($this->errors, $cmd->error);
				$this->db->rollback();
				return - 1;
			}
			$result = $this->add_object_linked("commande", $cmd->id);
			if ($result == 0) {
				$this->db->rollback();
				return - 3;
			}

			$this->fk_commande = $cmd->id;
			$res = $this->update($user);
			if ($res < 0) {
				$this->db->rollback();
				return - 4;
			}
		}
		$this->db->commit();
		return $cmd->id;
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $id ID
	 * @param string $tablename Name of the table
	 *
	 * @return int if KO, >0 if OK
	 */
	public function fetchAffairesDetLink($id, $tablename) {
		global $langs;

		$this->doclines = array();

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.fk_source,";
		$sql .= " t.sourcetype,";
		$sql .= " t.fk_target,";
		$sql .= " t.targettype";
		$sql .= " FROM " . MAIN_DB_PREFIX . "element_element as t";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . $this->table_element . " as l ON l.rowid=t.fk_target";
		$sql .= " WHERE t.fk_source = " . $id;
		$sql .= " AND t.targettype='affaires_det'";
		if (! empty($tablename)) {
			$sql .= " AND t.sourcetype='" . $tablename . "'";
		}

		dol_syslog(get_class($this) . "::fetchDocumentLink sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $obj = $this->db->fetch_object($resql) ) {
				$line = new self($this->db);
				$line->fetch($obj->fk_target);
				$this->doclines[] = $line;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetchDocumentLink " . $this->error, LOG_ERR);

			return - 1;
		}
	}

	/**
	 * Load an object from its id and create a new one in database
	 *
	 * @param int $socid Id of thirdparty
	 * @return int New id of clone
	 */
	function createFromClone() {
		global $user, $hookmanager;

		$error = 0;

		$this->context['createfromclone'] = 'createfromclone';

		$this->db->begin();

		// Load source object
		$objFrom = clone $this;

		$this->id = 0;

		// Clear fields
		$this->fk_user_author = $user->id;
		$this->datec = dol_now();
		$this->fk_user_mod = $user->id;
		unset($this->fk_commande);

		// Create clone
		$result = $this->create($user);
		if ($result < 0)
			$error ++;

		// End
		if (! $error) {
			unset($this->context['createfromclone']);
			$this->db->commit();
			return $this->id;
		} else {
			$this->db->rollback();
			return - 1;
		}
	}
}
