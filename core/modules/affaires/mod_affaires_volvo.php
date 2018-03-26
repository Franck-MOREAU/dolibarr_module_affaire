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
dol_include_once('/affaires/core/modules/affaires/modules_affaires.php');

/**
 *	Class to manage customer order numbering rules Marbre
 */
class mod_affaires_volvo extends ModeleNumRefAffaires
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
		return "AAA001/16";
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
		$sql.= " FROM ".MAIN_DB_PREFIX."affaires";
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
	function getNextValue($affaires)
	{
		global $db,$conf;

		$date=$affaires->year;

		$usr = new User($db);
		$usr->fetch($affaires->fk_user_resp);
		if (!empty($usr->lastname)) {
			$trig=strtoupper(str_pad($usr->lastname,3,'A'));
		} else {
			$trig=strtoupper(str_pad($usr->login,3,'A'));
		}
		$trig=substr($trig,0,3);
		// D'abord on recupere la valeur max
		$sql = "SELECT MAX(CAST(SUBSTRING(ref, 4, 3) AS SIGNED)) as max";
		$sql.= " FROM ".MAIN_DB_PREFIX."affaires";
		$sql.= " WHERE ref REGEXP '^" . $trig . "[0-9][0-9][0-9][/]" . $date . "'";
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
			dol_syslog(__METHOD__, LOG_DEBUG);
			return -1;
		}
		$num = sprintf("%03s",$max+1);
		$nextref=$trig.$num."/".$date;
		dol_syslog(__METHOD__." return ".$nextref);
		return $nextref;
	}


	/**
	 *  Return next free value
	 *
	 *  @param	Societe		$objsoc     Object third party
	 * 	@param	string		$objforref	Object for number to search
	 *  @return string      			Next free value
	 */
	function affaires_get_num($objsoc,$objforref)
	{
		return $this->getNextValue($objsoc,$objforref);
	}

}
