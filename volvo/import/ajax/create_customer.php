<?php
/* Copyright (C) 2015 Florian Henry  <florian.henry@open-concept.pro>
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
 */

/**
 * \file volvo/import/ajax/update_temp_table.php
 * \brief File to set action on temp tables
 */

// if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
// if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU','1');
// if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))
	define('NOREQUIREAJAX', '1');
	// if (! defined('NOREQUIRESOC')) define('NOREQUIRESOC','1');
	// if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');

$res = @include ("../../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

//dol_include_once('/volvo/class/volvoimport.class.php');


$rowid = GETPOST('rowid', 'int');
$tablename = GETPOST('tablename', 'alpha');
$colpatronyme = GETPOST('colpatronyme');
$coladdr = GETPOST('coladdr');
$colzip = GETPOST('colzip');
$coltown = GETPOST('coltown');

// Ajout directives pour resoudre bug IE
// header('Cache-Control: Public, must-revalidate');
// header('Pragma: public');

top_httphead();

// print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

if (! empty($rowid) && !empty($tablename)) {
	$error = 0;


	//$import = new VolvoImport($db);

	$sql='INSERT INTO '.MAIN_DB_PREFIX.'societe(entity,nom,name_alias,address,zip,town,fk_pays,client,fk_user_creat,fk_stcomm,fournisseur)';
	$sql.=' SELECT 1,'.$colpatronyme.','.$colpatronyme.','.$coladdr.','.$colzip.','.$coltown.',1,1,'.$user->id.',0,0';
	$sql.=' FROM '.$tablename;
	$sql.=' WHERE rowid='.$rowid;


	$db->begin();

	dol_syslog("ajax:create_csutomer:update", LOG_DEBUG);
	$resql = $db->query($sql);
	if (! $resql) {
		$error ++;
		dol_syslog('ajax:'.__FILE__.':ERROR=' . $db->lasterror(), LOG_ERR);
		//setEventMessages($db->lasterror(),null, 'errors');
	}

	// Commit or rollback
	if ($error) {
		$db->rollback();
		print $db->lasterror();
	} else {
		$db->commit();
		print 1;
	}
}