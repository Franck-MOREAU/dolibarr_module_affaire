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
 * \file admin/lead.php
 * \ingroup lead
 * \brief This file is an example module setup page
 * Put some comments here
 */
// Dolibarr environment
$res = @include '../../main.inc.php'; // From htdocs directory
if (! $res) {
	$res = @include '../../../main.inc.php'; // From "custom" directory
}

// Libraries
require_once '../lib/affaires.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
// Translations
// $langs->load("lead@lead");
$langs->load("admin");

// Access control
if (! $user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scandir', 'alpha');




/*
 * Actions
 */

if ($action == 'updateMask') {
	$maskconstlead = GETPOST('maskconstlead', 'alpha');
	$masklead = GETPOST('masklead', 'alpha');
	if ($maskconstlead)
		$res = dolibarr_set_const($db, $maskconstlead, $masklead, 'chaine', 0, '', $conf->entity);

	if (! $res > 0)
		$error ++;

	if (! $error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} else if ($action == 'setvar') {

	$listvcm = GETPOST('VOLVO_VCM_LIST');
	if (! empty($listvcm)) {
		$res = dolibarr_set_const($db, 'VOLVO_VCM_LIST', $listvcm, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$listpack = GETPOST('VOLVO_PACK_LIST');
	if (! empty($listpack)) {
		$res = dolibarr_set_const($db, 'VOLVO_PACK_LIST', $listpack, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$lock = GETPOST('VOLVO_LOCK_DELAI', 'int');
	if (! empty($lock)) {
		$res = dolibarr_set_const($db, 'VOLVO_LOCK_DELAI', $lock, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$truck = GETPOST('VOLVO_TRUCK', 'int');
	if (! empty($truck)) {
		$res = dolibarr_set_const($db, 'VOLVO_TRUCK', $truck, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$sures = GETPOST('VOLVO_SURES', 'int');
	if (! empty($sures)) {
		$res = dolibarr_set_const($db, 'VOLVO_SURES', $sures, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$com = GETPOST('VOLVO_COM', 'int');
	if (! empty($com)) {
		$res = dolibarr_set_const($db, 'VOLVO_COM', $com, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$forfaitliv = GETPOST('VOLVO_FORFAIT_LIV', 'int');
	if (! empty($forfaitliv)) {
		$res = dolibarr_set_const($db, 'VOLVO_FORFAIT_LIV', $forfaitliv, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$oblig = GETPOST('VOLVO_OBLIGATOIRE', 'int');
	if (! empty($oblig)) {
		$res = dolibarr_set_const($db, 'VOLVO_OBLIGATOIRE', $oblig, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$interne = GETPOST('VOLVO_INTERNE', 'int');
	if (! empty($interne)) {
		$res = dolibarr_set_const($db, 'VOLVO_INTERNE', $interne, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$externe = GETPOST('VOLVO_EXTERNE', 'int');
	if (! empty($externe)) {
		$res = dolibarr_set_const($db, 'VOLVO_EXTERNE', $externe, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$divers = GETPOST('VOLVO_DIVERS', 'int');
	if (! empty($divers)) {
		$res = dolibarr_set_const($db, 'VOLVO_DIVERS', $divers, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$soltrs = GETPOST('VOLVO_SOLTRS');
	if (! empty($soltrs)) {
		$res = dolibarr_set_const($db, 'VOLVO_SOLTRS', $soltrs, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}
	$vcmoblig = GETPOST('VOLVO_VCM_OBLIG');
	if (! empty($vcmoblig)) {
		$res = dolibarr_set_const($db, 'VOLVO_VCM_OBLIG', $vcmoblig, 'chaine', 0, '', $conf->entity);
	}

	if (! $res > 0) {
		$error ++;
	}

	if (! $error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

/*
 * View
 */
$page_name = "Administration du Module Theobald";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = affairesAdminPrepareHead();
dol_fiche_head($head, 'admin_volvo', 'Admin Module Theobald', 0);

$form = new Form($db);


// Admin var of module
print_fiche_titre($langs->trans("LeadAdmVar"));

print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" >';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="setvar">';

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td width="400px">' . $langs->trans("Valeur") . '</td>';
print "</tr>\n";

// Liste VCM
print '<tr class="pair"><td>Liste des produit contrat de maintenance</td>';
print '<td align="left">';
print '<input type="text" name="VOLVO_VCM_LIST" value="' . $conf->global->VOLVO_VCM_LIST . '" size="30" ></td>';
print '</tr>';

// Liste PAck
print '<tr class="impair"><td>Liste des produits pack protection</td>';
print '<td align="left">';
print '<input type="text" name="VOLVO_PACK_LIST" value="' . $conf->global->VOLVO_PACK_LIST . '" size="30" ></td>';
print '</tr>';

// Delai Verrouillage commande
print '<tr class="impair"><td>Délai de vérrouillage des commande apres la date de livraison en mois</td>';
print '<td align="left">';
print '<input type="text" name="VOLVO_LOCK_DELAI" value="' . $conf->global->VOLVO_LOCK_DELAI . '" size="30" ></td>';
print '</tr>';

// Article Véhicule VOLVO
print '<tr class="impair"><td>Article Véhiule Volvo</td>';
print '<td align="left">';
print $form->select_produits($conf->global->VOLVO_TRUCK,'VOLVO_TRUCK');
print '</tr>';

// Article Surestimation VO
print '<tr class="impair"><td>Article Surestimation VO</td>';
print '<td align="left">';
print $form->select_produits($conf->global->VOLVO_SURES,'VOLVO_SURES');
print '</tr>';

// Article Surestimation VO
print '<tr class="impair"><td>Article Commission DEALER</td>';
print '<td align="left">';
print $form->select_produits($conf->global->VOLVO_COM,'VOLVO_COM');
print '</tr>';

// Article Forfait Livraison
print '<tr class="impair"><td>Article Forfait Livraison</td>';
print '<td align="left">';
print $form->select_produits($conf->global->VOLVO_FORFAIT_LIV,'VOLVO_FORFAIT_LIV');
print '</tr>';

// Catégorie Travaux Obligatoire
print '<tr class="impair"><td>Catégorie pour travaux Obligatoire</td>';
print '<td align="left">';
print $form->select_all_categories(0, $conf->global->VOLVO_OBLIGATOIRE, 'VOLVO_OBLIGATOIRE', 64, 0, 0);
print '</tr>';

// Catégorie Travaux Interne
print '<tr class="impair"><td>Catégorie pour travaux Internes</td>';
print '<td align="left">';
print $form->select_all_categories(0, $conf->global->VOLVO_INTERNE, 'VOLVO_INTERNE', 64, 0, 0);
print '</tr>';

// Catégorie Travaux Externe
print '<tr class="impair"><td>Catégorie pour travaux Externes</td>';
print '<td align="left">';
print $form->select_all_categories(0, $conf->global->VOLVO_EXTERNE, 'VOLVO_EXTERNE', 64, 0, 0);
print '</tr>';

// Catégorie Travaux DIVERS
print '<tr class="impair"><td>Catégorie pour travaux Divers</td>';
print '<td align="left">';
print $form->select_all_categories(0, $conf->global->VOLVO_DIVERS, 'VOLVO_DIVERS', 64, 0, 0);
print '</tr>';

// Catégorie Solutions Transport
print '<tr class="impair"><td>Catégorie pour les solutions Transport</td>';
print '<td align="left">';
print $form->select_all_categories(0, $conf->global->VOLVO_SOLTRS, 'VOLVO_SOLTRS', 64, 0, 0);
print '</tr>';

// Catégorie Solutions Transport
print '<tr class="impair"><td>Saisie des infos VCM Obligatoire pour valider une commande</td>';
print '<td align="left">';
print $form->selectyesno('VOLVO_VCM_OBLIG', $conf->global->VOLVO_VCM_OBLIG,1,false,1);
print '</tr>';

print '</table>';

print '<tr class="impair"><td colspan="2" align="right"><input type="submit" class="button" value="' . $langs->trans("Save") . '"></td>';
print '</tr>';

print '</table><br>';
print '</form>';

print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" >';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="rem_dir">';

dol_fiche_end();

llxFooter();

$db->close();
