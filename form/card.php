<?php
/* Lead
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
$res = @include '../../main.inc.php'; // For root directory
if (! $res)
	$res = @include '../../../main.inc.php'; // For "custom" directory
if (! $res)
	die("Include of main fails");

dol_include_once('affaires/class/affaires.class.php');
dol_include_once('/core/class/doleditor.class.php');
dol_include_once('/user/class/user.class.php');
dol_include_once('/user/class/usergroup.class.php');


if (! empty($conf->commande->enabled))
	dol_include_once('/commande/class/commande.class.php');

	// Security check
if (! $user->rights->affaires->read)
	accessforbidden();

$langs->load('affaires@affaires');
if (! empty($conf->commande->enabled))
	$langs->load('order');

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$confirm = GETPOST('confirm', 'alpha');

$object = new Affaires($db);

// Load object
if ($id > 0) {
	$ret = $object->fetch($id);
	if ($ret < 0) setEventMessages(null, $object->errors, 'errors');
}

$includeuserlist = array();
$usergroup = new UserGroup($db);
$result = $usergroup->fetch('','Commerciaux');

if ($result < 0)
	setEventMessages(null, $usergroup->errors, 'errors');

$includeuserlisttmp = $usergroup->listUsersForGroup();

if (is_array($includeuserlisttmp) && count($includeuserlisttmp) > 0) {
	foreach ( $includeuserlisttmp as $usertmp ) {
		$includeuserlist[] = $usertmp->id;
	}
}

/*
 * Actions
 */




/*
 * View
 */

llxHeader('', 'Affaires');

$form = new Form($db);
$now = dol_now();

if ($action == 'create' && $user->rights->lead->write) {

}

elseif ($action == 'edit') {
	dol_include_once('/core/class/html.formother.class.php');
	$formother = new FormOther($db);
	
	dol_fiche_head('', '', 'Affaire ' . $object->ref , 0, dol_buildpath('/affaires/img/object_affaires.png', 1), 1);
	print_fiche_titre($langs->trans("affaire") . ' - ' . $object->ref , '', dol_buildpath('/affaires/img/object_affaires.png', 1), 1);

	print '<form name="editlead" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="update">';
	
	print '<table class="border" width="100%">';
	print '<tr>';
	print '<td width="50%">';
	print $langs->trans("affnum").': ' . $object->ref;
	print '</td>';
	print '<td width="50%">';
	print $langs->trans("client").': ' . $form->select_thirdparty_list($object->fk_soc, 'fk_soc', 's.client>0', 0);
	print '</td>';
	print '</tr>';
	
	print '<tr>';
	print '<td width="50%">';
	print $langs->trans("userresp").': '. $form->select_dolusers(empty($object->fk_user_resp) ? $user->id : $object->fk_user_resp, 'userid', 0, array(), 0, $includeuserlist, '', 0, 0, 0, '', 0, '', '', 1);
	print '</td>';
	print '<td width="50%">';
	print $langs->trans("ctm").': '. $form->select_thirdparty_list($object->fk_ctm, 'fk_ctm', 's.client>0', 0);;
	print '</td>';
	print '</tr>';
	
	print '<tr>';
	print '<td width="50%">';
	print $langs->trans("cv").': ' . $form->selectarray('fk_type', $object->type,$object->fk_c_type);
	print '</td>';
	print '<td width="50%">';
	print $langs->trans("year").': ';
	$formother->select_year($object->year,'year',0);
	print '</td>';
	print '</tr>';
	
	print '</table>';
	
	
	print '</form>';
} else {
	// Confirm form
	$formconfirm = '';
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('LeadDelete'), $langs->trans('LeadConfirmDelete'), 'confirm_delete', '', 0, 1);
	}
	
	if ($formconfirm) {
		print $formconfirm;
	}

	/*
	 * Show object in view mode
	 */
	dol_fiche_head();
	print_fiche_titre($langs->trans("affaire") . ' - ' . $object->ref , '', dol_buildpath('/affaires/img/object_affaires.png', 1), 1);
	
	print '<table class="border" width="100%">';
	print '<tr>';
	print '<td width="50%">';
	print $langs->trans("affnum").': ' . $object->ref;
	print '</td>';
	print '<td width="50%">';
	$user_resp = new User($db);
	$user_resp->fetch($object->fk_user_resp);
	print $langs->trans("client").': ' . $object->thirdparty->getNomUrl(1);
	print '</td>';
	print '</tr>';
	
	print '<tr>';
	print '<td width="50%">';
	print $langs->trans("userresp").': '.$user_resp->getNomUrl(1);
	print '</td>';
	print '<td width="50%">';
	if($object->fk_ctm>0){
		print $langs->trans("ctm").': '.$object->contremarque->getNomUrl(1);
	}
	print '</td>';
	print '</tr>';
	
	print '<tr>';
	print '<td width="50%">';
	print $langs->trans("cv").': ' . $object->type_label;
	print '</td>';
	print '<td width="50%">';
	print $langs->trans("year").': '.$object->year;
	print '</td>';
	print '</tr>';
	
	print '</table>';
	
	/*
	 * Barre d'actions
	 */
	
	print '<div class="tabsAction">';
	// Edit
	if ($user->rights->affaires->write) {
		print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit">' . $langs->trans("Modifier") . "</a></div>\n";
	}
	// Delete
	if ($user->rights->affaires->delete) {
		print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete">' . $langs->trans("Delete") . "</a></div>\n";
	}
	print '</div>';
		
}
dol_fiche_head();
print_fiche_titre($langs->trans("vhlist") . ' - ' . $object->ref , '', dol_buildpath('/affaires/img/object_affaires.png', 1), 1);
print '<table class="border" width="100%">';
//var_dump($object);

foreach ($object->affaires_det as $vehicule){
	print '<tr>';
	print '<td width="100%">';
	print $vehicule->vh_tile(0);
	print '</td>';
	print '</tr>';
}
print '</table>';

dol_fiche_end();


dol_fiche_end();
llxFooter();
$db->close();
