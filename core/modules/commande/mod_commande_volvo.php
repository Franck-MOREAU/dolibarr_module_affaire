<?php
/* Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *  \file       htdocs/core/modules/commande/mod_commande_marbre.php
 *  \ingroup    commande
 *  \brief      File of class to manage customer order numbering rules Marbre
 */
require_once DOL_DOCUMENT_ROOT .'/core/modules/commande/modules_commande.php';

/**
 *	Class to manage customer order numbering rules Marbre
 */
class mod_commande_volvo extends ModeleNumRefCommandes
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $prefix='';
	var $error='';
	var $nom='Volvo';


    /**
     *  Return description of numbering module
     *
     *  @return     string      Text with description
     */
    function info()
    {
    	global $langs;
      	return "Module de numérotation spécifique Volvo Théobald";
    }


	/**
	 *  Renvoi un exemple de numerotation
	 *
	 *  @return     string      Example
	 */
	function getExample()
	{
		return "V026/16";
	}


	/**
	 *  Test si les numeros deje en vigueur dans la base ne provoquent pas de
	 *  de conflits qui empechera cette numerotation de fonctionner.
	 *
	 *  @return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		global $conf,$langs,$db;

		$coyymm=''; $max='';

		$sql = "SELECT MAX(CAST(SUBSTRING(ref, 2, 3) AS SIGNED)) as max";
		$sql.= " FROM ".MAIN_DB_PREFIX."commande";
		$sql.= " WHERE ref REGEXP '^[NSV][0-9][0-9][0-9][/][0-9][0-9]$'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
			if ($row) { $coyymm = substr($row[0],0,6); $max=$row[0]; }
		}
		if ($coyymm && ! preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i',$coyymm))
		{
			$langs->load("errors");
			$this->error=$langs->trans('ErrorNumRefModel', $max);
			return false;
		}

		return true;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param	Societe		$objsoc     Object thirdparty
	 *  @param  Object		$object		Object we need next value for
	 *  @return string      			Value if KO, <0 if KO
	 */
	function getNextValue($objsoc,$object)
	{
		global $db,$conf;
		dol_include_once('/affaires/class/affaires.class.php');
		$objectdet = new Affaires_det($db);
		$objectaff = new Affaires($db);
		$cnl = 'X';
		$date=$object->date;
		$yy = strftime("%y",$date);

		$sql = "SELECT MAX(fk_target) as max ";
		$sql.= "FROM " .MAIN_DB_PREFIX."element_element ";
		$sql.= "WHERE sourcetype = 'commande' AND targettype = 'affaires_det' AND fk_source = " . $object->id;

		$resql=$db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if($obj) $objectdet->fetch($obj->max);
			if($objectdet->fk_affaires) $objectaff->fetch($objectdet->fk_affaires);
		}

		if ($objectaff->id > 0)
		{
			$cnl = strtoupper($objectaff->type_label[0]);
		}

		// D'abord on recupere la valeur max
		$sql = "SELECT MAX(CAST(SUBSTRING(ref, 2, 3) AS SIGNED)) as max";
		$sql.= " FROM ".MAIN_DB_PREFIX."commande";
		$sql.= " WHERE ref REGEXP '^[" . $cnl . "][0-9][0-9][0-9].*[" . $yy . "]$'";
		$sql.= " AND entity = ".$conf->entity;

		dol_syslog(__METHOD__,LOG_DEBUG);
		$resql=$db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj) $max = intval($obj->max);
			else $max=0;
		}
		else
		{
			dol_syslog("mod_commande_marbre::getNextValue", LOG_DEBUG);
			return -1;
		}
		$num = sprintf("%03s",$max+1);
		dol_syslog(__METHOD__." return ".$cnl.$num."/".$yy);
// 		return $lead->id;
		return $cnl.$num."/".$yy;
	}


	/**
	 *  Return next free value
	 *
	 *  @param	Societe		$objsoc     Object third party
	 * 	@param	string		$objforref	Object for number to search
	 *  @return string      			Next free value
	 */
	function commande_get_num($objsoc,$objforref)
	{
		return $this->getNextValue($objsoc,$objforref);
	}

}
