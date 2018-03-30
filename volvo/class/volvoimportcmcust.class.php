<?php
/* Volvo
 * Copyright (C) 2015       Florian Henry		<florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 * \file volvo/class/volvoimportcmcust.class.php
 * \ingroup volvo
 * \brief File to load import files with XSLX format
 */
require_once 'volvoimport.class.php';

/**
 * Class to import consogazoil CSV specific files
 */
class VolvoImportCMCust extends VolvoImport
{
	public $lines = array();
	protected $db;
	public $error;
	public $errors = array();
	protected $filesource;
	public $objWorksheet;
	public $sheetArray = array();
	public $columnArray = array();
	protected $objPHPExcel;
	protected $startcell;
	protected $maxcol;
	protected $maxrow;
	public $columnData;
	protected $tempTable;
	public $targetInfoArray = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db
	 */
	function __construct($db) {
		global $conf, $langs;

		$langs->load('volvo@volvo');
		$langs->load('companies');

		$this->db = $db;

		$this->targetInfoArray[] = array(
				'column' => 'nom',
				'type' => 'text',
				'columntrans' => $langs->trans('Name'),
				'table' => MAIN_DB_PREFIX . 'societe',
				'tabletrans' => $langs->trans('Societe'),
				'filecolumntitle' => 'Nom',
				'editable' => 0
		);
		$this->targetInfoArray[] = array(
				'column' => 'fax',
				'type' => 'text',
				'columntrans' => $langs->trans('Fax'),
				'table' => MAIN_DB_PREFIX . 'societe',
				'tabletrans' => $langs->trans('Societe'),
				'filecolumntitle' => 'Fax',
				'editable' => 0
		);
		$this->targetInfoArray[] = array(
				'column' => 'code_client',
				'type' => 'text',
				'columntrans' => $langs->trans('VolvoCDB'),
				'table' => MAIN_DB_PREFIX . 'societe',
				'tabletrans' => $langs->trans('Societe'),
				'filecolumntitle' => 'No de client CDB',
				'editable' => 0,
				'isunique' => 1,
				'noinsert' => 1
		);
		$this->targetInfoArray[] = array(
				'column' => 'ape',
				'type' => 'text',
				'columntrans' => $langs->trans('ProfId3FR'),
				'table' => MAIN_DB_PREFIX . 'societe',
				'tabletrans' => $langs->trans('Societe'),
				'filecolumntitle' => 'Numéro  Parma',
				'editable' => 0
		);
		$this->targetInfoArray[] = array(
				'column' => 'siret',
				'type' => 'text',
				'columntrans' => $langs->trans('ProfId2FR'),
				'table' => MAIN_DB_PREFIX . 'societe',
				'tabletrans' => $langs->trans('Societe'),
				'filecolumntitle' => 'Numero de Siret',
				'editable' => 0
		);
		$this->targetInfoArray[] = array(
				'column' => 'siren',
				'type' => 'text',
				'columntrans' => $langs->trans('ProfId1FR'),
				'table' => MAIN_DB_PREFIX . 'societe',
				'tabletrans' => $langs->trans('Societe'),
				'filecolumntitle' => 'SIREN',
				'editable' => 0
		);
		$this->targetInfoArray[] = array(
				'column' => 'tva_intra',
				'type' => 'text',
				'columntrans' => $langs->trans('VATIntraShort'),
				'table' => MAIN_DB_PREFIX . 'societe',
				'tabletrans' => $langs->trans('Societe'),
				'filecolumntitle' => 'Numéro TVA',
				'editable' => 0
		);
		$this->targetInfoArray[] = array(
				'column' => 'phone',
				'type' => 'text',
				'columntrans' => $langs->trans('Phones'),
				'table' => MAIN_DB_PREFIX . 'societe',
				'tabletrans' => $langs->trans('Societe'),
				'filecolumntitle' => 'Téléphone',
				'editable' => 0
		);
		$this->targetInfoArray[] = array(
				'column' => 'email',
				'type' => 'text',
				'columntrans' => $langs->trans('Email'),
				'table' => MAIN_DB_PREFIX . 'societe',
				'tabletrans' => $langs->trans('Societe'),
				'filecolumntitle' => 'Email professionnel',
				'editable' => 0
		);
		$this->targetInfoArray[] = array(
				'column' => 'address',
				'type' => 'address',
				'columntrans' => $langs->trans('Address'),
				'table' => MAIN_DB_PREFIX . 'societe',
				'tabletrans' => $langs->trans('Societe'),
				'filecolumntitle' => 'Adresse',
				'forcedestcolumn' => 'address',
				'forcesrccolumn' => 'realaddress',
				'tmpcolumnname' => 'realaddress',
				'editable' => 0
		);
		$this->targetInfoArray[] = array(
				'column' => 'address2',
				'type' => 'text',
				'columntrans' => $langs->trans('Address'),
				'table' => MAIN_DB_PREFIX . 'societe',
				'tabletrans' => $langs->trans('Societe'),
				'filecolumntitle' => 'Adresse2',
				'forcedestcolumn' => 'address',
				'forcesrccolumn' => 'realaddress',
				'editable' => 0,
				'noinsert' => 1
		);
		$this->targetInfoArray[] = array(
				'column' => 'address3',
				'type' => 'text',
				'columntrans' => $langs->trans('Address'),
				'table' => MAIN_DB_PREFIX . 'societe',
				'tabletrans' => $langs->trans('Societe'),
				'filecolumntitle' => 'Adresse3',
				'forcedestcolumn' => 'address',
				'forcesrccolumn' => 'realaddress',
				'editable' => 0,
				'noinsert' => 1
		);
		$this->targetInfoArray[] = array(
				'column' => 'zip',
				'type' => 'text',
				'columntrans' => $langs->trans('Zip'),
				'table' => MAIN_DB_PREFIX . 'societe',
				'tabletrans' => $langs->trans('Societe'),
				'filecolumntitle' => 'Code postal',
				'editable' => 0
		);
		$this->targetInfoArray[] = array(
				'column' => 'town',
				'type' => 'text',
				'columntrans' => $langs->trans('Town'),
				'table' => MAIN_DB_PREFIX . 'societe',
				'tabletrans' => $langs->trans('Societe'),
				'filecolumntitle' => 'Ville',
				'editable' => 0
		);
		$this->targetInfoArray[] = array(
				'column' => 'fk_pays',
				'type' => 'text',
				'columntrans' => $langs->trans('Country'),
				'table' => MAIN_DB_PREFIX . 'societe',
				'tabletrans' => $langs->trans('Societe'),
				'dict' => 'country',
				'dictmatch' => 'label',
				'filecolumntitle' => 'Pays',
				'editable' => 1
		);
		/*$this->targetInfoArray[] = array(
		 'column' => 'note_private',
		 'type' => 'text',
		 'columntrans' => $langs->trans('Note'),
		 'table' => MAIN_DB_PREFIX . 'societe',
		 'tabletrans' => $langs->trans('Societe'),
		 'filecolumntitle' => 'Statut Client',
		 'editable' => 0
		 );*/
	}

	/**
	 *
	 * @return number
	 */
	public function loadData() {
		global $langs, $user;
		$error = 0;

		$result = $this->loadFile();
		if ($result < O) {
			return - 1;
		}
		$result = $this->setActivWorksheet(0);
		if ($result < O) {
			return - 1;
		}
		$result = $this->checkTabAndCell(0, 'A1');
		if ($result < O) {
			return - 1;
		}
		$this->loadHeaderColumn();

		// $current_array = $this->columnArray;
		dol_syslog(get_class($this) . '::' . __METHOD__, LOG_DEBUG);

		$this->db->begin();

		// Delete old temp table
		$sql = 'DROP TABLE IF EXISTS ' . $this->tempTable;
		dol_syslog(get_class($this) . '::' . __METHOD__ . ' Delete old temp table', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$this->errors[] = $this->db->lasterror;
			$error ++;
		}
		// Build sql temp table
		if (empty($error)) {

			$sql = 'CREATE TABLE ' . $this->tempTable;
			$sql .= '(';
			$sql .= 'rowid integer NOT NULL auto_increment PRIMARY KEY,';
			$sql .= 'integration_status integer DEFAULT NULL,';
			$sql .= 'integration_action varchar(50) DEFAULT NULL,';
			$sql .= 'integration_comment text DEFAULT NULL,';
			$sql .= 'thirdparty_id integer DEFAULT NULL,';
			$sql .= 'realaddress varchar(255) DEFAULT NULL,';
			$sql .= 'num_cdb varchar(255) DEFAULT NULL,';
			foreach ( $this->columnArray 	as $data ) {
				$sql .= $data['name'] . ' varchar(500),';
			}
			$sql .= 'tms timestamp NOT NULL';
			$sql .= ')ENGINE=InnoDB;';

			dol_syslog(get_class($this) . '::' . __METHOD__ . ' Build sql temp table', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$this->errors[] = $this->db->lasterror;
				$error ++;
			}
		}

		// Build Data array
		if (empty($error)) {
			try {

				$rowIterator = $this->objWorksheet->getRowIterator($this->objWorksheet->getCell($this->startcell)->getRow() + 1);
				foreach ( $rowIterator as $row ) {
					$cellIterator = $row->getCellIterator($this->objWorksheet->getCell($this->startcell)->getColumn());
					$cellIterator->setIterateOnlyExistingCells(false);

					foreach ( $cellIterator as $cell ) {
						if (PHPExcel_Shared_Date::isDateTime($cell)) {
							$cellValue = $cell->getValue();
							$dateValue = PHPExcel_Shared_Date::ExcelToPHP($cellValue);
							$cellValue = date('Ymd', $dateValue);
						} else {
							$cellValue = trim($cell->getCalculatedValue());
						}
						$this->columnData[$cell->getRow()][$cell->getColumn()] = array(
								'sqlvalue' => ($cellValue == '' ? 'NULL' : '\'' . $this->db->escape($cellValue) . '\''),
								'data' => $cellValue
						);

						if ($cell->getColumn() == $this->maxcol) {
							break;
						}
					}

					// insert Data into temp table
					if (empty($error)) {

						$sqlInsert = array();

						$sql_insertheader = 'INSERT INTO ' . $this->tempTable;
						$sql_insertheader .= '(';
						foreach ( $this->columnArray as $data ) {
							$sql_insertheader .= $data['name'] . ',';
						}
						$sql_insertheader .= 'tms';
						$sql_insertheader .= ')';

						$i = 0;
						foreach ( $this->columnData as $rowindex => $datarow ) {
							$sql = $sql_insertheader . ' VALUES (';
							foreach ( $datarow as $colinex => $data ) {
								$sql .= $data['sqlvalue'] . ',';
							}
							$sql .= 'NOW())';

							$sqlInsert[] = $sql;
							$i ++;
						}

						foreach ( $sqlInsert as $sql ) {
							dol_syslog(get_class($this) . '::' . __METHOD__ . ' insert data into temp table', LOG_DEBUG);
							$resql = $this->db->query($sql);
							if (! $resql) {
								$this->errors[] = $this->db->lasterror;
								$error ++;
							}
						}
					}

					if (empty($error)) {
						$this->columnData = array();
					}
				}
			} catch ( Exception $e ) {
				$this->errors[] = $e->getMessage();
				$error ++;
			}
		}

		if (empty($error)) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return - 1 * $error;
		}
	}

	/**
	 *
	 * @param array $matchColmunArray
	 */
	public function checkData($matchColmunArray = array()) {
		global $langs;

		$error = 0;

		$sql = 'UPDATE ' . $this->tempTable . ' SET integration_status=NULL, integration_comment=\'\'';
		dol_syslog(get_class($this) . '::' . __METHOD__ . ' remove all comment', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$this->errors[] = $this->db->lasterror;
			$error ++;
		}

		// Find CDB column
		$compare_column = array();
		foreach ( $this->targetInfoArray as $key => $data ) {
			if (array_key_exists('isunique', $data) && ! empty($data['isunique']) && strpos($data['column'], 'code_client') !== false) {
				$columnTmpName = $matchColmunArray[$key];
				$colnumcdb_tmptable = $columnTmpName;
				$colnumcdb_forcetmptable = $data['tmpcolumnname'];
				$colnumcdb_desttable = $data['column'];
			}
			// Find parma column
			if ($data['column'] == 'ape') {
				if (array_key_exists($key, $matchColmunArray) && ! empty($matchColmunArray[$key])) {
					$compare_column[$data['column']] = array(
							'tmpcolname' => $matchColmunArray[$key],
							'columntrans' => $data['columntrans']
					);
				}
			}
			// Find siret column
			if ($data['column'] == 'siret') {
				if (array_key_exists($key, $matchColmunArray) && ! empty($matchColmunArray[$key])) {
					$compare_column[$data['column']] = array(
							'tmpcolname' => $matchColmunArray[$key],
							'columntrans' => $data['columntrans']
					);
					$colsiret_tmptable=$matchColmunArray[$key];
				}
			}
			// Find siren column
			if ($data['column'] == 'siren') {
				if (array_key_exists($key, $matchColmunArray) && ! empty($matchColmunArray[$key])) {
					$compare_column[$data['column']] = array(
							'tmpcolname' => $matchColmunArray[$key],
							'columntrans' => $data['columntrans']
					);
				}
			}
			// Find name column
			if ($data['column'] == 'nom') {
				if (array_key_exists($key, $matchColmunArray) && ! empty($matchColmunArray[$key])) {
					$compare_column[$data['column']] = array(
							'tmpcolname' => $matchColmunArray[$key],
							'columntrans' => $data['columntrans']
					);
				}
			}
			// Find tva_intracolumn
			if ($data['column'] == 'tva_intra') {
				if (array_key_exists($key, $matchColmunArray) && ! empty($matchColmunArray[$key])) {
					$compare_column[$data['column']] = array(
							'tmpcolname' => $matchColmunArray[$key],
							'columntrans' => $data['columntrans']
					);
				}
			}
			// Find zip
			if ($data['column'] == 'zip') {
				if (array_key_exists($key, $matchColmunArray) && ! empty($matchColmunArray[$key])) {
					$colzip_tmptable = $matchColmunArray[$key];
				}
			}
			// Find town
			if ($data['column'] == 'town') {
				if (array_key_exists($key, $matchColmunArray) && ! empty($matchColmunArray[$key])) {
					$coltown_tmptable = $matchColmunArray[$key];
				}
			}

			// Find phone
			if ($data['column'] == 'phone') {
				if (array_key_exists($key, $matchColmunArray) && ! empty($matchColmunArray[$key])) {
					$colphone_tmptable = $matchColmunArray[$key];
				}
			}
			// Find fax
			if ($data['column'] == 'fax') {
				if (array_key_exists($key, $matchColmunArray) && ! empty($matchColmunArray[$key])) {
					$colfax_tmptable = $matchColmunArray[$key];
				}
			}
		}

		// Find adress Fields
		$coladdress_tmptable = array();
		foreach ( $this->targetInfoArray as $key => $data ) {
			if (array_key_exists('column', $data) && strpos($data['column'], 'address') !== false) {
				$columnTmpName = $matchColmunArray[$key];
				if (! empty($columnTmpName)) {
					$coladdress_tmptable[$columnTmpName] = 'IFNULL(' . $columnTmpName . ',\'\')';
				}
			}
		}
		if (count($coladdress_tmptable > 0)) {
			// update adresse realfields column
			$sql = 'UPDATE ' . $this->tempTable . '  SET realaddress=TRIM(CONCAT_WS(" \n ",' . implode(',', $coladdress_tmptable) . '))';
			dol_syslog(get_class($this) . '::' . __METHOD__ . ' update realadress', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$this->errors[] = $this->db->lasterror;
				$error ++;
			}
			$sql = 'UPDATE ' . $this->tempTable . '  SET realaddress=REPLACE(realaddress, "\n  \n", \'\')';
			dol_syslog(get_class($this) . '::' . __METHOD__ . ' update realadress', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$this->errors[] = $this->db->lasterror;
				$error ++;
			}
		}

		// check too long phone
		if (! empty($colphone_tmptable)) {
			$sql = 'SELECT rowid FROM ' . $this->tempTable . ' WHERE length(' . $colphone_tmptable . ')>20';
			dol_syslog(get_class($this) . '::' . __METHOD__ . ' delete line with phone too long', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$this->errors[] = $this->db->lasterror;
				$error ++;
			} else {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$integration_comment = array(
							'column' => $colphone_tmptable,
							'color' => 'red',
							'message' => $langs->trans('VolvoFieldTooLong', $colphone_tmptable, '20'),
							'outputincell' => 1
					);
					$result = $this->addIntegrationComment($obj->rowid, $integration_comment, 0);
					if ($result < 0) {
						$error ++;
					}
				}
			}
		}

		// check too long fax
		if (! empty($colfax_tmptable)) {
			$sql = 'SELECT rowid FROM ' . $this->tempTable . ' WHERE length(' . $colfax_tmptable . ')>20';
			dol_syslog(get_class($this) . '::' . __METHOD__ . ' delete line with fax too long', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$this->errors[] = $this->db->lasterror;
				$error ++;
			} else {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$integration_comment = array(
							'column' => $colfax_tmptable,
							'color' => 'red',
							'message' => $langs->trans('VolvoFieldTooLong', $colfax_tmptable, '20'),
							'outputincell' => 1
					);
					$result = $this->addIntegrationComment($obj->rowid, $integration_comment, 0);
					if ($result < 0) {
						$error ++;
					}
				}
			}
		}

		// check too siret
		if (! empty($colsiret_tmptable)) {
			$sql = 'SELECT rowid FROM ' . $this->tempTable . ' WHERE length(' . $colsiret_tmptable . ')>14';
			dol_syslog(get_class($this) . '::' . __METHOD__ . ' delete line with fax too long', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$this->errors[] = $this->db->lasterror;
				$error ++;
			} else {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$integration_comment = array(
							'column' => $colsiret_tmptable,
							'color' => 'red',
							'message' => $langs->trans('VolvoFieldTooLong', $colsiret_tmptable, '14'),
							'outputincell' => 1
					);
					$result = $this->addIntegrationComment($obj->rowid, $integration_comment, 0);
					if ($result < 0) {
						$error ++;
					}
				}
			}
		}

		// Check if dictionnay values
		foreach ( $this->targetInfoArray as $key => $data ) {
			if (array_key_exists('dict', $data)) {
				$columnTmpName = $matchColmunArray[$key];

				$sql = 'SELECT rowid, ' . $columnTmpName . ' as datatest FROM ' . $this->tempTable;
				$sql .= ' WHERE ' . $columnTmpName . ' NOT IN ';

				$sql .= '		(SELECT DISTINCT ' . $data['dictmatch'] . ' FROM ' . MAIN_DB_PREFIX . 'c_' . $data['dict'] . ' WHERE ' . $data['dictmatch'] . ' IS NOT NULL)';
				$sql .= '		OR ' . $columnTmpName . ' IS NULL ';
				$sql .= '		OR ' . $columnTmpName . '=\'\' ';

				dol_syslog(get_class($this) . '::' . __METHOD__ . ' update dictionnary problem', LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (! $resql) {
					$this->errors[] = $this->db->lasterror;
					$error ++;
				} else {
					while ( $obj = $this->db->fetch_object($resql) ) {
						$integration_comment = array(
								'column' => $columnTmpName,
								'color' => 'red',
								'message' => $langs->trans('VolvoCkImpNoFound', $data['columntrans']),
								'outputincell' => 1
						);
						$result = $this->addIntegrationComment($obj->rowid, $integration_comment, 4);
						if ($result < 0) {
							$error ++;
						}
					}
				}
			}
		}

		// update thirdparty_id column
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'societe as src,' . $this->tempTable . ' as dest SET dest.thirdparty_id=src.rowid ';
		$sql .= ' WHERE src.code_client=dest.' . $colnumcdb_tmptable . ' AND dest.thirdparty_id IS NULL AND dest.integration_status IS NULL';
		dol_syslog(get_class($this) . '::' . __METHOD__ . ' update thirdparty_id', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$this->errors[] = $this->db->lasterror;
			$error ++;
		}

		$sql = 'UPDATE ' . $this->tempTable . ' SET integration_status=2';
		$sql .= ' WHERE thirdparty_id IS NOT NULL ';
		dol_syslog(get_class($this) . '::' . __METHOD__ . ' update duplicate information problem', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$this->errors[] = $this->db->lasterror;
			$error ++;
		}

		// for thirdparry already imported find thirdparty taht can match on nom
		// adresse (adress + zip + town)
		// SIREN
		// SIRET
		// PARMA
		// num TVA
		if (is_array($compare_column) && count($compare_column) > 0) {

			foreach ( $compare_column as $destcolname => $datacol ) {
				// Create Index societe
				$sql_index = 'ALTER TABLE ' . MAIN_DB_PREFIX . 'societe ADD INDEX idx_societetmp_' . $destcolname . '(' . $destcolname . ')';
				dol_syslog(get_class($this) . '::' . __METHOD__ . ' create index', LOG_DEBUG);
				$resqlindex = $this->db->query($sql_index);
				if (! $resqlindex) {
					// $this->errors[] = $this->db->lasterror;
					// $error ++;
				}

				// Create Index temptable
				$sql_index = 'ALTER TABLE ' . $this->tempTable . ' ADD INDEX idx_' . $this->tempTable . '_' . $datacol['tmpcolname'] . '(' . $datacol['tmpcolname'] . ')';
				dol_syslog(get_class($this) . '::' . __METHOD__ . ' create index', LOG_DEBUG);
				$resqlindex = $this->db->query($sql_index);
				if (! $resqlindex) {
					// $this->errors[] = $this->db->lasterror;
					// $error ++;
				}
			}

			// Create Index societe
			$sql_index = 'ALTER TABLE ' . MAIN_DB_PREFIX . 'societe ADD INDEX idx_societetmp_addressziptown(address,zip,town)';
			dol_syslog(get_class($this) . '::' . __METHOD__ . ' create index', LOG_DEBUG);
			$resqlindex = $this->db->query($sql_index);
			if (! $resqlindex) {
				// $this->errors[] = $this->db->lasterror;
				// $error ++;
			}

			$message = array();
			$sql = 'SELECT tmptbl.rowid as tmpid FROM ' . $this->tempTable . ' as tmptbl WHERE tmptbl.thirdparty_id IS NULL AND (integration_status NOT IN (4,0) OR integration_status IS NULL)';
			dol_syslog(get_class($this) . '::' . __METHOD__ . ' Check already imported match on other attribute than CDB', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$this->errors[] = $this->db->lasterror;
				$error ++;
			} else {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$message = array();
					foreach ( $compare_column as $destcolname => $datacol ) {

						$sqlinner = 'SELECT soc.rowid as socid FROM ' . $this->tempTable . ' as tmptbl ';
						$sqlinner .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'societe as soc ';
						$sqlinner .= ' ON soc.' . $destcolname . '=tmptbl.' . $datacol['tmpcolname'];
						$sqlinner .= ' WHERE tmptbl.rowid=' . $obj->tmpid;
						dol_syslog(get_class($this) . '::' . __METHOD__ . ' Check each attribute (' . $datacol['tmpcolname'] . ') societe.' . $destcolname, LOG_DEBUG);
						$resqlinner = $this->db->query($sqlinner);
						if (! $resqlinner) {
							$this->errors[] = $this->db->lasterror;
							$error ++;
						} else {
							while ( $objinner = $this->db->fetch_object($resqlinner) ) {
								$message[$objinner->socid][] = $langs->trans('VolvoImportDataFindEquals', $datacol['columntrans']);
							}
						}
					}

					$sqlinner = 'SELECT soc.rowid as socid FROM ' . $this->tempTable . ' as tmptbl ';
					$sqlinner .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'societe as soc ';
					$sqlinner .= ' ON soc.address=tmptbl.realaddress AND soc.zip=' . $colzip_tmptable . ' AND soc.town=' . $coltown_tmptable;
					$sqlinner .= ' WHERE tmptbl.rowid=' . $obj->tmpid;
					dol_syslog(get_class($this) . '::' . __METHOD__ . ' Check each attribute (address)', LOG_DEBUG);
					$resqlinner = $this->db->query($sqlinner);
					if (! $resqlinner) {
						$this->errors[] = $this->db->lasterror;
						$error ++;
					} else {
						while ( $objinner = $this->db->fetch_object($resqlinner) ) {
							$message[$objinner->socid][] = $langs->trans('VolvoImportDataFindEquals', $langs->trans('Address'));
						}
					}

					if (count($message) > 0) {
						$result = $this->addIntegrationComment($obj->tmpid, $message, 2);
						if ($result < 0) {
							$error ++;
						}
					}
				}

				//Find different adress
				$sqlinner = 'SELECT DISTINCT tmptbl.rowid as tmpid, soc.rowid as socid FROM ' . $this->tempTable . ' as tmptbl ';
				$sqlinner .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'societe as soc ';
				$sqlinner .= ' ON tmptbl.thirdparty_id=soc.rowid';
				$sqlinner .= ' WHERE soc.address<>tmptbl.realaddress OR soc.zip=' . $colzip_tmptable . ' OR soc.town=' . $coltown_tmptable;
				dol_syslog(get_class($this) . '::' . __METHOD__ . ' Check each attribute (address)', LOG_DEBUG);
				$resqlinner = $this->db->query($sqlinner);
				if (! $resqlinner) {
					$this->errors[] = $this->db->lasterror;
					$error ++;
				} else {
					while ( $objinner = $this->db->fetch_object($resqlinner) ) {
						$message=array();
						$message[$objinner->socid][]='Mise à jour de l\'adresse';
						$result = $this->addIntegrationComment($objinner->tmpid, $message, 2);
						if ($result < 0) {
							$error ++;
						}
						$sqlupdate ='UPDATE ' . $this->tempTable . ' SET integration_action=\''.$objinner->socid.'$updateadress\',integration_status=2 WHERE rowid='.$objinner->tmpid;
						$resqlupd = $this->db->query($sqlupdate);
						if (! $resqlupd) {
							$this->errors[] = $this->db->lasterror;
							$error ++;
						}
					}
				}

				foreach ( $compare_column as $destcolname => $datacol ) {
					// DROP Index societe
					$sql_index = 'DROP INDEX idx_societetmp_' . $destcolname . ' ON ' . MAIN_DB_PREFIX . 'societe';
					dol_syslog(get_class($this) . '::' . __METHOD__ . ' drop index', LOG_DEBUG);
					$resqlindex = $this->db->query($sql_index);
					if (! $resqlindex) {
						// $this->errors[] = $this->db->lasterror;
						// $error ++;
					}
				}

				// DROP Index societe
				$sql_index = 'DROP INDEX idx_societetmp_addressziptown ON ' . MAIN_DB_PREFIX . 'societe';
				dol_syslog(get_class($this) . '::' . __METHOD__ . ' drop index', LOG_DEBUG);
				$resqlindex = $this->db->query($sql_index);
				if (! $resqlindex) {
					// $this->errors[] = $this->db->lasterror;
					// $error ++;
				}
			}
		}

		// Remove from column Array unit 1 and unit 2 to force unitcode
		$this->columnArray[] = array(
				'name' => 'realaddress',
				'label' => $langs->trans('Address')
		);
		foreach ( $this->columnArray as $key => $value ) {
			if (array_key_exists($value['name'], $coladdress_tmptable)) {
				unset($this->columnArray[$key]);
			}
		}

		// Update intégration status OK
		$sql = 'UPDATE ' . $this->tempTable . ' SET integration_status=1 WHERE integration_status IS NULL';
		dol_syslog(get_class($this) . '::' . __METHOD__ . ' set status to 1', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$this->errors[] = $this->db->lasterror;
			$error ++;
		}

		// Set to NULL integration comment where the is no remark
		$sql = 'UPDATE ' . $this->tempTable . ' SET integration_comment=NULL WHERE integration_comment=\'\'';
		dol_syslog(get_class($this) . '::' . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$this->errors[] = $this->db->lasterror;
			$error ++;
		}

		// Remove line for already inserted vehicule without difference
		$sql = 'DELETE FROM ' . $this->tempTable . ' WHERE integration_status=2 AND integration_comment IS NULL';
		dol_syslog(get_class($this) . '::' . __METHOD__ . ' Remove line for already exists with same values', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$this->errors[] = $this->db->lasterror;
			$error ++;
		}

		if (empty($error)) {
			return 1;
		} else {
			return - 1 * $error;
		}
	}

	public function setNonImortedLineToNoImport() {
		// Remove line for already inserted vehicule without difference
		$sql = 'UPDATE ' . $this->tempTable . ' SET integration_status=0 WHERE integration_status=2 AND integration_action IS NULL';
		dol_syslog(get_class($this) . '::' . __METHOD__ . ' Remove line for already', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$this->errors[] = $this->db->lasterror;
			$error ++;
		}

		if (empty($error)) {
			return 1;
		} else {
			return - 1 * $error;
		}
	}

	/**
	 *
	 * @param array $matchColmunArray
	 * @return number
	 */
	public function importData($matchColmunArray = array()) {
		global $langs, $conf, $user;

		$error = 0;

		$now = dol_now();

		// Find cdb column
		foreach ( $this->targetInfoArray as $key => $data ) {
			if (array_key_exists('isunique', $data) && ! empty($data['isunique']) && strpos($data['column'], 'code_client') !== false) {
				$colnumcdb_tmptable = $matchColmunArray[$key];
				$colnumcdb_forcetmptable = $data['tmpcolumnname'];
				$colnumcdb_desttable = $data['column'];
			}
		}

		$this->db->begin();
		$result = $this->fetchAllTempTable('', '', 0, 0, array(
				'integration_status' => 1
		));
		if ($result < 0) {
			$error ++;
		}
		// Insert New customer
		foreach ( $this->lines as $line ) {
			$sqlcol = array();
			$sqlvalue = array();

			$cdb_val = $line->$colnumcdb_tmptable;

			// New customer always inserted as prospect
			$sqlcol[] = 'client';
			$sqlvalue[] = 2;

			$sqlcol[] = 'status';
			$sqlvalue[] = 1;

			foreach ( $this->targetInfoArray as $key => $col ) {
				if ($col['table'] == MAIN_DB_PREFIX . 'societe') {
					if (! array_key_exists('noinsert', $col)) {

						$columnTmpName = $matchColmunArray[$key];
						if (array_key_exists('tmpcolumnname', $col) && ! empty($col['tmpcolumnname'])) {
							$columnTmpName = $col['tmpcolumnname'];

						}

						//var_dump($columnTmpName);

						if (! empty($columnTmpName)) {
							$sqlcol[] = $col['column'];

							if (array_key_exists('dict', $col)) {
								// Value from dict
								$sql_dict = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'c_' . $col['dict'] . ' WHERE';

								$sql_dict .= ' ' . $col['dictmatch'] . '=' . "'" . $this->db->escape($line->$columnTmpName) . "'";

								dol_syslog(get_class($this) . '::' . __METHOD__ . ' find dict value', LOG_DEBUG);
								$resql_dict = $this->db->query($sql_dict);
								if (! $resql_dict) {
									$this->errors[] = $this->db->lasterror;
									$error ++;
								} else {
									$obj_dict = $this->db->fetch_object($resql_dict);
									$sqlvalue[] = $this->formatSqlType($col['type'], $obj_dict->rowid);
								}
							} else {
								// Simple value
								$sqlvalue[] = $this->formatSqlType($col['type'], $line->$columnTmpName);
							}
						}
					}
				}
			}


			$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'societe(' . implode(',', $sqlcol) . ',import_key) VALUES (' . implode(',', $sqlvalue) . ',\'' . $now . '\')';
			dol_syslog(get_class($this) . '::' . __METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$this->errors[] = $this->db->lasterror;
				$error ++;
			}
		}

		// Update existing societe
		$this->columnArray[]=array('name'=>'realaddress','label'=>'Adresse');
		array_unshift($this->columnArray, array(
				'name' => 'thirdparty_id'
		));

		$result = $this->fetchAllTempTable('', '', 0, 0, array(
				'integration_status' => 2
		));
		if ($result < 0) {
			$error ++;
		}
		foreach ( $this->lines as $line ) {
			$sqlcol = array();
			$sqlvalue = array();
			$sqlcoladress=array();
			$sqlvalueadress=array();

			$cdb_val = $line->$colnumcdb_tmptable;

			foreach ( $this->targetInfoArray as $key => $col ) {
				if ($col['table'] == MAIN_DB_PREFIX . 'societe') {
					if (! array_key_exists('noinsert', $col)) {

						$columnTmpName = $matchColmunArray[$key];
						$addvalongeneralarray=false;

						if (! empty($columnTmpName)) {
							if ($col['column']=='address') {
								$sqlcoladress[] = 'address';
								$columnTmpName=$col['forcesrccolumn'];
								$sqlvalueadress[] = $this->formatSqlType($col['type'], $line->$columnTmpName);
							} else if ($col['column']=='zip') {
								$sqlcoladress[] = 'zip';
								$sqlvalueadress[] = $this->formatSqlType($col['type'], $line->$columnTmpName);
							} else if ($col['column']=='town') {
								$sqlcoladress[] = 'town';
								$sqlvalueadress[] = $this->formatSqlType($col['type'], $line->$columnTmpName);
							}else {
								$sqlcol[] = $col['column'];
								$addvalongeneralarray=true;
							}
							if (array_key_exists('dict', $col)) {
								// Value from dict
								$sql_dict = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'c_' . $col['dict'] . ' WHERE';
								$sql_dict .= ' ' . $col['dictmatch'] . '=' . "'" . $this->db->escape($line->$columnTmpName) . "'";

								dol_syslog(get_class($this) . '::' . __METHOD__ . ' find dict value', LOG_DEBUG);
								$resql_dict = $this->db->query($sql_dict);
								if (! $resql_dict) {
									$this->errors[] = $this->db->lasterror;
									$error ++;
								} else {
									$obj_dict = $this->db->fetch_object($resql_dict);
									$sqlvalue[] = $this->formatSqlType($col['type'], $obj_dict->rowid);
								}
							} else if ($addvalongeneralarray) {
								//var_dump($sqlcol);
								// Simple value
								$sqlvalue[] = $this->formatSqlType($col['type'], $line->$columnTmpName);
								//var_dump($line->$columnTmpName);
							}
						}
					}
				}
			}
		//	var_dump($sqlcol);
			$sql_upd_col = array();
			foreach ( $sqlcol as $key => $colupd ) {
				// If value is empty or null into file do not overide dest table values
				if (! empty($sqlvalue[$key]) && $sqlvalue[$key] != '\'\'') {
					// var_dump($colupd,$sqlvalue[$key]);
					$sql_upd_col[] = $colupd . '=' . $sqlvalue[$key];
				}
			}

			$sql_upd_col_adress = array();
			foreach ( $sqlcoladress as $key => $colupd ) {
				// If value is empty or null into file do not overide dest table values
				if (! empty($sqlvalueadress[$key]) && $sqlvalueadress[$key] != '\'\'') {
					// var_dump($colupd,$sqlvalue[$key]);
					$sql_upd_col_adress[] = $colupd . '=' . $sqlvalueadress[$key];
				}
			}

			//var_dump($sql_upd_col);
			//exit;
			$action_array = explode('$', $line->integration_action);
			$socid = $action_array[0];
			$action = $action_array[1];
			if ($action == 'updatecustomer') {
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'societe SET ' . implode(',', $sql_upd_col) . ',import_key=\'' . $now . 'm\' WHERE rowid=' . $socid;
				dol_syslog(get_class($this) . '::updatecustomer' . __METHOD__, LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (! $resql) {
					$this->errors[] = $this->db->lasterror;
					$error ++;
				}
			} else if ($action == 'updateadress') {
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'societe SET ' . implode(',', $sql_upd_col_adress) . ',import_key=\'' . $now . 'm\' WHERE rowid=' . $socid;
				dol_syslog(get_class($this) . '::updateadress' . __METHOD__, LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (! $resql) {
					$this->errors[] = $this->db->lasterror;
					$error ++;
				}
			}
		}

		if (empty($error)) {
			$this->db->commit();
			return $now;
		} else {
			$this->db->rollback();
			return - 1 * $error;
		}
	}

	/**
	 *
	 * @param array $batch_number
	 * @param string $type
	 */
	public function getResultCnt($batch_number, $type = '') {
		if ($type == 'create' || $type == 'update') {
			$sql = 'SELECT count(rowid) as cnt FROM ' . MAIN_DB_PREFIX . 'societe WHERE import_key=\'' . $this->db->escape($batch_number) . '\'';
			dol_syslog(get_class($this) . '::' . __METHOD__ . '', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$this->errors[] = $this->db->lasterror;
				$error ++;
			} else {
				$obj = $this->db->fetch_object($resql);
				$num = $obj->cnt;
			}
		} elseif ($type == 'failed') {
			$sql = 'SELECT count(rowid) as cnt FROM ' . $this->tempTable . ' WHERE integration_status IN (0,4)';
			dol_syslog(get_class($this) . '::' . __METHOD__ . '', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$this->errors[] = $this->db->lasterror;
				$error ++;
			} else {
				$obj = $this->db->fetch_object($resql);
				$num = $obj->cnt;
			}
		}

		if (empty($error)) {
			return $num;
		} else {
			return - 1 * $error;
		}
	}

	/**
	 *
	 * @return number
	 */
	public function loadDataFromMultiple($dir, $filename) {
		global $langs, $user;
		$error = 0;

		$i = 0;
		$this->tempTable = MAIN_DB_PREFIX . 'volvo_tmp_cmcust_' . $user->id . '_' . dol_trunc($this->volvo_string_nospecial($filename), 20, 'right', 'UTF-8', 1);
		$file_array = dol_dir_list($dir, 'files');
		if (count($file_array) > 0) {
			foreach ( $file_array as $fil ) {
				$i ++;
				$this->filesource = $fil['fullname'];
				$this->startcell = 'A1';
				$this->loadFile();
				$this->setActivWorksheet(0);
				if ($i == 1) {
					$this->loadHeaderColumn();
				}
				// $current_array = $this->columnArray;

				dol_syslog(get_class($this) . '::' . __METHOD__, LOG_DEBUG);

				$this->db->begin();
				if (empty($error) && ($i == 1)) {

					// Delete old temp table
					$sql = 'DROP TABLE IF EXISTS ' . $this->tempTable;
					dol_syslog(get_class($this) . '::' . __METHOD__ . ' Delete old temp table', LOG_DEBUG);
					$resql = $this->db->query($sql);
					if (! $resql) {
						$this->errors[] = $this->db->lasterror;
						$error ++;
					}
				}
				// Build sql temp table
				if (empty($error) && ($i == 1)) {

					$sql = 'CREATE TABLE ' . $this->tempTable;
					$sql .= '(';
					$sql .= 'rowid integer NOT NULL auto_increment PRIMARY KEY,';
					$sql .= 'integration_status integer DEFAULT NULL,';
					$sql .= 'integration_action varchar(20) DEFAULT NULL,';
					$sql .= 'integration_comment text DEFAULT NULL,';
					$sql .= 'thirdparty_id integer DEFAULT NULL,';
					$sql .= 'realaddress varchar(255) DEFAULT NULL,';
					foreach ( $this->columnArray as $data ) {
						$sql .= $data['name'] . ' varchar(500),';
					}
					$sql .= 'tms timestamp NOT NULL';
					$sql .= ')ENGINE=InnoDB;';

					dol_syslog(get_class($this) . '::' . __METHOD__ . ' Build sql temp table', LOG_DEBUG);
					$resql = $this->db->query($sql);
					if (! $resql) {
						$this->errors[] = $this->db->lasterror;
						$error ++;
					}
				}

				// Build Data array
				if (empty($error)) {
					try {

						$rowIterator = $this->objWorksheet->getRowIterator($this->objWorksheet->getCell($this->startcell)->getRow() + 1);
						foreach ( $rowIterator as $row ) {
							$cellIterator = $row->getCellIterator($this->objWorksheet->getCell($this->startcell)->getColumn());
							$cellIterator->setIterateOnlyExistingCells(false);

							foreach ( $cellIterator as $cell ) {
								if (PHPExcel_Shared_Date::isDateTime($cell)) {
									$cellValue = $cell->getValue();
									$dateValue = PHPExcel_Shared_Date::ExcelToPHP($cellValue);
									$cellValue = date('Ymd', $dateValue);
								} else {
									$cellValue = trim($cell->getCalculatedValue());
								}
								$this->columnData[$cell->getRow()][$cell->getColumn()] = array(
										'sqlvalue' => ($cellValue == '' ? 'NULL' : '\'' . $this->db->escape($cellValue) . '\''),
										'data' => $cellValue
								);

								if ($cell->getColumn() == $this->maxcol) {
									break;
								}
							}

							// insert Data into temp table
							if (empty($error)) {

								$sqlInsert = array();

								$sql_insertheader = 'INSERT INTO ' . $this->tempTable;
								$sql_insertheader .= '(';
								foreach ( $this->columnArray as $data ) {
									$sql_insertheader .= $data['name'] . ',';
								}
								$sql_insertheader .= 'tms';
								$sql_insertheader .= ')';

								$i = 0;
								foreach ( $this->columnData as $rowindex => $datarow ) {
									$sql = $sql_insertheader . ' VALUES (';
									foreach ( $datarow as $colinex => $data ) {
										$sql .= $data['sqlvalue'] . ',';
									}
									$sql .= 'NOW())';

									$sqlInsert[] = $sql;
									$i ++;
								}

								foreach ( $sqlInsert as $sql ) {
									dol_syslog(get_class($this) . '::' . __METHOD__ . ' insert data into temp table', LOG_DEBUG);
									$resql = $this->db->query($sql);
									if (! $resql) {
										$this->errors[] = $this->db->lasterror;
										$error ++;
									}
								}
							}

							if (empty($error)) {
								$this->columnData = array();
							}
						}
					} catch ( Exception $e ) {
						$this->errors[] = $e->getMessage();
						$error ++;
					}
				}
			}
		}

		if (empty($error)) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return - 1 * $error;
		}
	}
}