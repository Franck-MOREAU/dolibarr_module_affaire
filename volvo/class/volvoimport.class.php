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
 * \file volvo/class/volvoimport.class.php
 * \ingroup volvo
 * \brief File to load import files with XSLX format
 */
dol_include_once('/affaires/lib/phpoffice/phpexcel/Classes/PHPExcel.php');

/**
 * Class to import consogazoil CSV specific files
 */
class VolvoImport
{
	public $lines = array ();
	protected $db;
	public $error;
	public $errors = array ();
	protected $filesource;
	public $objWorksheet;
	public $sheetArray = array ();
	public $columnArray = array ();
	protected $objPHPExcel;
	protected $startcell;
	protected $maxcol;
	protected $maxrow;
	public $columnData;
	protected $tempTable;
	public $targetInfoArray = array ();

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
	}

	/**
	 *
	 * @param string $filesource
	 */
	public function initFile($filesource, $type) {
		global $user;
		$this->filesource = $filesource;
		$this->tempTable = MAIN_DB_PREFIX . 'volvo_tmp_' . $type . '_' . $user->id . '_' . dol_trunc($this->volvo_string_nospecial(basename($this->filesource)), 10, 'right', 'UTF-8', 1);
	}

	/**
	 *
	 * @return string
	 */
	public function gettempTable() {
		return $this->tempTable;
	}

	/**
	 *
	 * @return string
	 */
	public function settempTable($temptable) {
		$this->tempTable = $temptable;
	}

	/**
	 *
	 * @return number
	 */
	public function loadFile() {
		global $conf, $langs;

		dol_syslog(get_class($this) . '::' . __METHOD__, LOG_DEBUG);

		$error = 0;

		// Supported PHPExcel File readers to ensure we deal with a Spreadsheet.
		$supported_filereaders = array (
				'Excel2007',
				'Excel5',
				'OOCalc',
				'Excel2003XML'
		);

		if (! in_array(PHPExcel_IOFactory::identify($this->filesource), $supported_filereaders)) {
			$this->errors[] = $langs->trans('UploadFileErrorUnsupportedFormat');
			$error ++;
		}

		try {
			$this->objPHPExcel = PHPExcel_IOFactory::load($this->filesource);
		} catch ( PHPExcel_Reader_Exception $e ) {
			$this->errors[] = $e->getMessage();
			$error ++;
		}

		// var_dump($excelfd);
		if (is_object($this->objPHPExcel) && empty($error)) {
			$this->sheetArray = $this->objPHPExcel->getSheetNames();
		} else {
			$this->errors[] = $langs->trans('VolvoCannotLoadFile', $this->filesource);
			$error ++;
		}

		if (! is_array($this->sheetArray) || count($this->sheetArray) == 0) {
			$this->errors[] = $langs->trans('VolvoCannotLoadSheet', $this->filesource);
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
	 * @param int $id
	 */
	public function setActivWorksheet($id) {
		dol_syslog(get_class($this) . '::' . __METHOD__, LOG_DEBUG);

		$error = 0;

		try {
			$this->objPHPExcel->setActiveSheetIndex($id);
			$this->objWorksheet = $this->objPHPExcel->getActiveSheet();
		} catch ( Exception $e ) {
			$this->errors[] = $e->getMessage();
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
	 * @param string $tab_to_treat
	 * @param string $startcell
	 * @return number
	 */
	public function checkTabAndCell($tab_to_treat = '', $startcell = '') {
		global $conf, $langs;

		dol_syslog(get_class($this) . '::' . __METHOD__, LOG_DEBUG);

		$this->startcell = $startcell;

		$error = 0;
		if ($tab_to_treat === '') {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("VolvoTabsAvailable"));
			$error ++;
		}

		if (get_class($this->objWorksheet) != 'PHPExcel_Worksheet') {
			$this->errors[] = $langs->trans('VolvoCannotLoadSheet', $this->filesource);
			$error ++;
		}

		if (empty($error)) {

			$str = $this->objWorksheet->getCell($this->startcell)->getCalculatedValue();

			if (empty($str)) {
				$this->errors[] = $langs->trans('VolvoCellIsEmpty', $this->startcell);
				$error ++;
			}
		}

		if (empty($error)) {
			return 1;
		} else {
			return - 1 * $error;
		}
	}

	/**
	 *
	 * @param string $str
	 * @param string $newstr
	 * @param string $badcharstoreplace
	 */
	protected function volvo_string_nospecial($str, $newstr = '_', $badcharstoreplace = '') {
		dol_syslog(get_class($this) . '::' . __METHOD__, LOG_DEBUG);

		$forbidden_chars_to_replace = array (
				" ",
				"'",
				"/",
				"\\",
				":",
				"*",
				"?",
				"\"",
				"<",
				">",
				"|",
				"[",
				"]",
				",",
				";",
				"=",
				"°",
				"&",
				"-",
				".",
				"(",
				")",
				"%"
		);
		$forbidden_chars_to_remove = array ();
		if (is_array($badcharstoreplace))
			$forbidden_chars_to_replace = $badcharstoreplace;
			// $forbidden_chars_to_remove=array("(",")");

		$str = str_replace($forbidden_chars_to_replace, $newstr, str_replace($forbidden_chars_to_remove, "", $str));
		$str = str_replace('€', 'EUR', $str);
		$str = dol_string_unaccent($str);
		$str = dol_strtolower($str);

		return $str;
	}

	/**
	 *
	 * @param number $limit
	 * @return number
	 */
	public function fetchAllTempTable($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = array(), $onlymatchcolumn = false, $filtermode = 'AND') {
		$this->lines = array ();

		$sql = ' SELECT rowid,';
		foreach ( $this->columnArray as $data ) {
			$sql .= $data['name'] . ',';
		}
		// Remove last comma
		$sql = mb_substr($sql, 0, - 1);

		$sql .= ' FROM  ' . $this->tempTable;

		$sqlwhere = array ();
		if (count($filter) > 0) {
			foreach ( $filter as $key => $value ) {
				if ($key == 'integration_status' || $key == 'integration_status_module') {
					$sqlwhere[] = $key . ' IN (' . $this->db->escape($value) . ')';
				} else if ($key=='!likeintegration_action') {
					$sqlwhere[] = ' (integration_action NOT LIKE \'%' . $this->db->escape($value) . '%\' OR integration_action  IS NULL) ';
				} else {
					$sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' WHERE ' . implode(' ' . $filtermode . ' ', $sqlwhere);
		}
		if (! empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit + 1, $offset);
		}
		dol_syslog(get_class($this) . '::' . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$this->errors[] = $this->db->lasterror;
			$error ++;
		}

		if (empty($error)) {
			$num = $this->db->num_rows($resql);
			while ( $obj = $this->db->fetch_object($resql) ) {
				$this->lines[] = $obj;
			}
		}

		if (empty($error)) {
			return $num;
		} else {
			return - 1 * $error;
		}
	}

	/**
	 * Remove column from collection that is not used during import
	 *
	 * @param string $onlymatch
	 * @param array $matchColmunArray
	 */
	public function removeUnmatchColumn($forceDisplaycolumn = array(), $matchColmunArray = array(), $forceRemoveColumn = array()) {
		// Build match column name
		foreach ( $this->targetInfoArray as $key => $data ) {
			if (array_key_exists($key, $matchColmunArray))
				$mathcolumnname[] = $matchColmunArray[$key];
		}
		foreach ( $this->columnArray as $key => $col ) {
			if ((! in_array($col['name'], $mathcolumnname) && ! in_array($col['name'], $forceDisplaycolumn) || in_array($col['name'], $forceRemoveColumn))) {
				unset($this->columnArray[$key]);
			}
		}
	}

	/**
	 *
	 * @param string $type
	 * @param string $value
	 * @return string|DateTime
	 */
	public function formatSqlType($type, $value) {
		if ($type == 'text') {
			return "'" . $this->db->escape($value) . "'";
		} elseif ($type == 'date') {
			try {
				$value = new DateTime($value);
				return "'" . $this->db->idate($value->getTimestamp()) . "'";
			} catch ( Exception $e ) {
				$this->errors[] = $e->getMessage();
				return '';
			}
		} elseif ($type == 'int') {
			if ($value == '') {
				return 'NULL';
			} else {
				return $value;
			}
		}
	}

	/**
	 *
	 * @param number $id
	 * @param string $integration_comment
	 */
	public function addIntegrationComment($id = 0, $integration_comment = array(), $integration_status = 0) {
		$sql = 'SELECT integration_comment FROM ' . $this->tempTable . ' WHERE rowid=' . $id;

		dol_syslog(get_class($this) . '::' . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$this->errors[] = $this->db->lasterror;
			$error ++;
		} else {
			if ($obj = $this->db->fetch_object($resql)) {
				$current_comment = json_decode($obj->integration_comment, true);
				$current_comment[] = $integration_comment;
			}
		}

		$this->db->begin();

		$sql = 'UPDATE ' . $this->tempTable . ' SET integration_status=' . $integration_status . ', integration_comment=\'' . $this->db->escape(json_encode($current_comment)) . '\' WHERE rowid=' . $id;
		$resql = $this->db->query($sql);
		if (! $resql) {
			$this->errors[] = $this->db->lasterror;
			$error ++;
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
	 */
	public function dropTempTable() {
		if (! empty($this->tempTable)) {
			$sql = ' DROP TABLE IF EXISTS ' . $this->tempTable;

			dol_syslog(get_class($this) . '::' . __METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$this->errors[] = $this->db->lasterror;
				$error ++;
			}
		}

		if (empty($error)) {
			return 1;
		} else {
			return - 1 * $error;
		}
	}

	/**
	 *
	 * @param string $file
	 * @param string $dir
	 */
	public function extractZip($file, $dir) {
		global $langs;

		$error = 0;

		if (! is_dir($dir)) {
			$result = dol_mkdir($dir);
			if ($result <= 0) {
				$error ++;
				$this->errors[] = $langs->trans('VolvoCannotCreateTempDir');
			}
		}

		if (! class_exists('ZipArchive')) {
			$langs->load("errors");
			$this->error = $langs->trans('ErrorPHPNeedModule', 'zip');
			return - 1;
		}

		$zip = new ZipArchive();
		if ($zip->open($file) === TRUE) {
			$zip->extractTo($dir);
			$zip->close();
		} else {
			$error ++;
			$this->errors[] = $langs->trans('VolvoCannotExtractZipFile');
		}

		if (empty($error)) {
			return 1;
		} else {
			return - 1 * $error;
		}
	}

	/**
	 *
	 */
	public function loadHeaderColumn() {
		$error = 0;

		dol_syslog(get_class($this) . '::' . __METHOD__, LOG_DEBUG);

		// Build header column array
		try {
			$rowIterator = $this->objWorksheet->getRowIterator($this->objWorksheet->getCell($this->startcell)->getRow())->current();

			$cellIterator = $rowIterator->getCellIterator($this->objWorksheet->getCell($this->startcell)->getColumn());
			$cellIterator->setIterateOnlyExistingCells(false);

			foreach ( $cellIterator as $cell ) {
				$cellValue = trim($cell->getCalculatedValue());
				if (empty($cellValue)) {
					$this->maxcol = $prevcol;
					break;
				}
				$this->columnArray[$cell->getColumn()] = array (
						'name' => dol_trunc($this->volvo_string_nospecial($cellValue), 64, 'right', 'UTF-8', 1),
						'label' => $cellValue
				);
				dol_syslog(get_class($this) . '::' . __METHOD__ . ' Build columnArray=' . var_export($this->columnArray, true), LOG_DEBUG);
				$prevcol = $cell->getColumn();
			}
		} catch ( Exception $e ) {
			$this->errors[] = $e->getMessage();
			$error ++;
		}
	}

	/**
	 *
	 * @param string $dir
	 * @return number
	 */
	public function checkFilesColumn($dir) {
		global $langs;

		$error = 0;

		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

		$filetocheck = new self($this->db);

		$file_array = dol_dir_list($dir, 'files');
		if (count($file_array) > 0) {
			foreach ( $file_array as $fil ) {
				$filetocheck->filesource = $fil['fullname'];
				$filetocheck->startcell = 'A1';
				$filetocheck->loadFile();
				$filetocheck->setActivWorksheet(0);
				$filetocheck->loadHeaderColumn();
				$current_array = $filetocheck->columnArray;
				// Avoid test on first run
				if (count($old_array) > 0) {
					$diff_array = array_diff($old_array, $current_array);
					if (count($diff_array) > 0) {
						$this->errors[] = $langs->trans('VolvoHeaderFilesNoAllTheSame');
						$error ++;
					}
				}
				$old_array = $filetocheck->columnArray;
			}
		}

		if (empty($error)) {
			return 1;
		} else {
			return - 1 * $error;
		}
	}
}