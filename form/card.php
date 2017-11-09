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

	dol_fiche_head('', '', 'Affaire ' . $object->ref , 0, dol_buildpath('/affaires/img/object_affaires.png', 1), 1);

	print '<form name="editlead" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="update">';

	print '</form>';
} else {
	/*
	 * Show object in view mode
	 */
	dol_fiche_head();
	print_fiche_titre('Affaire - ' . $object->ref , '', dol_buildpath('/affaires/img/object_affaires.png', 1), 1);
	
	// Confirm form
	$formconfirm = '';
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('LeadDelete'), $langs->trans('LeadConfirmDelete'), 'confirm_delete', '', 0, 1);
	}
		

	$linkback = '<a href="' . dol_buildpath('/affaires/form/list.php', 1) . '">' . $langs->trans("BackToList") . '</a>';

	if ($formconfirm) {
		print $formconfirm;
	}

	print '<table class="border" width="100%">';
	
	print '<tr>';
	print '<td width="50%">';
	print $langs->trans("affnum").': ' . $object->ref;
	print '</td>';
	print '<td width="50%">';
	$user = new User($db);
	$user->fetch($object->fk_user_resp);
	print $langs->trans("userresp").': '.$user->getNomUrl(1);
	print '</td>';
	print '</tr>';
	
	print '<tr>';
	print '<td width="50%">';
	print $langs->trans("client").': ' . $object->thirdparty->getNomUrl(1);
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
	
	// Delete
	if ($user->rights->lead->write) {
		print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit">' . $langs->trans("Modifier") . "</a></div>\n";
		print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=clone">' . $langs->trans("Clone") . "</a></div>\n";
		print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=create_relance">' . $langs->trans("CreateRelance") . "</a></div>\n";
		if ($object->status[7] == $langs->trans('LeadStatus_LOST') && $object->fk_c_status != 7) {
			print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=close">' . $langs->trans("LeadLost") . "</a></div>\n";
		}
	} else {
		print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("anoughPermissions")) . '">' . $langs->trans("Edit") . "</a></div>";
		print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("anoughPermissions")) . '">' . $langs->trans("Clone") . "</a></div>";
		// print '<div class="inline-block divButAction"><font class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans("LeadLost") . "</font></div>";
	}

	// Delete
	if ($user->rights->lead->delete) {
		print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete">' . $langs->trans("Delete") . "</a></div>\n";
	} else {
		print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("anoughPermissions")) . '">' . $langs->trans("Delete") . "</a></div>";
	}
	print '</div>';
}
dol_fiche_end();
llxFooter();
$db->close();
