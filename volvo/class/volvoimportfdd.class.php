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
class VolvoImportfdd extends VolvoImport
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

	}

	/**
	 *
	 * @return number
	 */
	public function loadData() {
		$error = 0;

		$this->objWorksheet->setCellValue('A1','=VLOOKUP(Combo!R75+1,Combo!P6:R42,3,FALSE))');

		$sql = 'SELECT name, cell, type, oblig FROM ' . MAIN_DB_PREFIX . 'volvo_modele_fdd_det WHERE fk_modele_fdd = ' . $this->model;

		$resql = $this->db->query($sql);
		if($resql){
			while($object = $this->db->fetch_object($resql)){
				$this->targetInfoArray[$object->name] = array(
						'cell' => $object->cell,
						'type' => $object->type,
						'oblig' => $object->oblig
				);
			}

		}


		dol_syslog(get_class($this) . '::' . __METHOD__, LOG_DEBUG);

		foreach ($this->targetInfoArray as $key => $info){
			if($info['type'] == 'calc'){
				$this->targetInfoArray[$key]['value']= trim($this->objWorksheet->getCell($info['cell'])->getOldCalculatedValue());
			}elseif($info['type'] == 'val'){
				$this->targetInfoArray[$key]['value']= trim($this->objWorksheet->getCell($info['cell'])->getValue());
			}elseif($info['type'] == 'calcr'){
				$this->targetInfoArray[$key]['value']= trim($this->objWorksheet->getCell('A1')->getCalculatedValue());
			}
		}

		foreach ($this->targetInfoArray as $key => $info){
			if($info['oblig']== true and empty($info['value'])){
				$this->errors[] = $key . ' must not be empty';
				$error ++;
			}

		}

		if (empty($error)) {
			return 1;
		} else {
			return - 1 * $error;
		}

	}



}