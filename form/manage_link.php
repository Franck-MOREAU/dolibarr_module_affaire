<?php
/* Copyright (C) 2015-2016		Florian HENRY	<florian.henry@atm-consulting.fr>
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
 */

/**
 * \file affaires/affaires/manage_link.php
 * \ingroup affaires
 * \brief affaires manage link
 */
$res = @include '../../main.inc.php'; // For root directory
if (! $res)
	$res = @include '../../../main.inc.php'; // For "custom" directory
if (! $res)
	die("Include of main fails");

dol_include_once('/affaires/class/affaires.class.php');

$object = new Affaires($db);

/*
 * Actions
 */

$tablename = GETPOST("tablename");
$affairesid = GETPOST("affairesid");
$elementselectid = GETPOST("elementselect");
$redirect = GETPOST('redirect', 'alpha');
$action=GETPOST('action');

if (empty($affairesid) || $affairesid==-1) {
	setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Affaires")), null, 'errors');
	$error ++;
}

if (! $error) {
	$result = $object->fetch($affairesid);
	if ($result < 0) {
		setEventMessages(null, $object->errors, 'errors');
		$error ++;
	}
}
if (! $error) {
	if ($action == 'link') {
		
		$result = $object->add_object_linked($tablename, $elementselectid);
		if ($result < 0) {
			setEventMessages(null, $object->errors, 'errors');
		}
	}
	if ($action == 'unlink') {
		$sourceid = GETPOST('sourceid');
		$sourcetype = GETPOST('sourcetype');
		
		$result = $object->deleteObjectLinked($sourceid, $sourcetype);
		if ($result < 0) {
			setEventMessages(null, $object->errors, 'errors');
		}
	}
}

header("Location:" . $redirect);
exit();