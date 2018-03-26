<?php
/* 
 * Copyright (C) 2014-2016 Florian HENRY <florian.henry@atm-consulting.fr>
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
 * \file htdocs/comm/propal/contact.php
 * \ingroup propal
 * \brief Onglet de gestion des contacts de propal
 */
$res = @include '../../main.inc.php'; // For root directory
if (! $res)
	$res = @include '../../../main.inc.php'; // For "custom" directory
if (! $res)
	die("Include of main fails");
require_once '../class/affaires.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once '../lib/affaires.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once '../class/html.formaffaires.class.php';

$langs->load("facture");
$langs->load("affaires@affaires");
$langs->load("orders");
$langs->load("sendings");
$langs->load("companies");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$lineid = GETPOST('lineid', 'int');
$action = GETPOST('action', 'alpha');

// Security check
if (! $user->rights->affaires->read)
	accessforbidden();

$object = new Affaires($db);

// Load object
if ($id > 0) {
	$ret = $object->fetch($id);
	if ($ret == 0) {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorRecordNotFound'), null, 'errors');
		$error ++;
	} else if ($ret < 0) {
		setEventMessages(null, $object->errors, 'errors');
		$error ++;
	}
}
if (! $error) {
	$object->fetch_thirdparty();
} else {
	header('Location: list.php');
	exit();
}

/*
 * Ajout d'un nouveau contact
 */

if ($action == 'addcontact' && $user->rights->affaires->write) {
	if ($object->id > 0) {
		$contactid = (GETPOST('userid', 'int') ? GETPOST('userid', 'int') : GETPOST('contactid', 'int'));
		$result = $object->add_contact($contactid, $_POST["type"], $_POST["source"]);
	}
	
	if ($result >= 0) {
		header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id);
		exit();
	} else {
		if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
		} else {
			setEventMessages(null, $object->errors, 'errors');
		}
	}
} 

// Bascule du statut d'un contact
else if ($action == 'swapstatut' && $user->rights->affaires->write) {
	if ($object->id > 0) {
		$result = $object->swapContactStatus(GETPOST('ligne'));
	}
} 

// Efface un contact
else if ($action == 'deletecontact' && $user->rights->affaires->write) {
	$result = $object->delete_contact($lineid);
	
	if ($result >= 0) {
		header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id);
		exit();
	} else {
		dol_print_error($db);
	}
}

/*
 * View
 */

llxHeader('', $langs->trans('AffairesContact'));

$form = new Form($db);
$formcompany = new FormCompany($db);
$formother = new FormOther($db);
$formaffaires = new FormAffaires($db);

if ($object->id > 0) {
	$head = affaires_prepare_head($object);
	dol_fiche_head($head, 'contact', $langs->trans("AffairesContact"), 0, 'contact');
	
	/*
	 * Affaires synthese pour rappel
	 */
	print '<table class="border" width="100%">';
	
	$linkback = '<a href="list.php">' . $langs->trans("BackToList") . '</a>';
	
	// Ref
	print '<tr><td width="25%">' . $langs->trans('Ref') . '</td><td colspan="3">';
	print $formaffaires->showrefnav($object, 'id', $linkback, 1, 'rowid', 'ref', '');
	print '</td></tr>';
	
	print '<tr>';
	print '<td width="20%">';
	print $langs->trans('AffairesCommercial');
	print '</td>';
	print '<td>';
	$userstatic = new User($db);
	$result = $userstatic->fetch($object->fk_user_resp);
	if ($result < 0) {
		setEventMessages(null, $userstatic->errors, 'errors');
	}
	print $userstatic->getFullName($langs);
	print '</td>';
	print '</tr>';
	
	print '<tr>';
	print '<td>';
	print $langs->trans('Company');
	print '</td>';
	print '<td>';
	print $object->thirdparty->getNomUrl();
	print '</td>';
	print '</tr>';
	
	print '<tr>';
	print '<td>';
	print $langs->trans('AffairesStatus');
	print '</td>';
	print '<td>';
	print $object->status_label;
	print '</td>';
	print '</tr>';
	
	print '<tr>';
	print '<td>';
	print $langs->trans('AffairesType');
	print '</td>';
	print '<td>';
	print $object->type_label;
	print '</td>';
	print '</tr>';
	
	print "</table>";
	
	print '</div>';
	
	print '<br>';
	
	$res = @include '../tpl/contacts.tpl.php';
}

dol_fiche_end();

llxFooter();

$db->close();
