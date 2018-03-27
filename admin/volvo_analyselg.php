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

if ($action == 'setvar') {

	$listx = GETPOST('VOLVO_ANALYSELG_X', 'alpha');
	if (! empty($listx)) {
		$res = dolibarr_set_const($db, 'VOLVO_ANALYSELG_X', $listx, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}
	$listz = GETPOST('VOLVO_ANALYSELG_Z', 'alpha');
	if (! empty($listz)) {
		$res = dolibarr_set_const($db, 'VOLVO_ANALYSELG_Z', $listz, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$yentete = GETPOST('VOLVO_ANALYSELG_Y_ENTETE', 'alpha');
	$test = count(explode(',', $yentete));
	if (! empty($yentete) && $test ==10) {
		$res = dolibarr_set_const($db, 'VOLVO_ANALYSELG_Y_ENTETE', $yentete, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$internenb = GETPOST('VOLVO_ANALYSELG_Y_INTERNE_NB', 'int');
	if (! empty($internenb)) {
		$res = dolibarr_set_const($db, 'VOLVO_ANALYSELG_Y_INTERNE_NB', $internenb, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$interneoffset = GETPOST('VOLVO_ANALYSELG_Y_INTERNE_OFFSET', 'alpha');
	if (! empty($interneoffset)) {
		$res = dolibarr_set_const($db, 'VOLVO_ANALYSELG_Y_INTERNE_OFFSET', $interneoffset, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$internepas = GETPOST('VOLVO_ANALYSELG_Y_INTERNE_PAS', 'alpha');
	if (! empty($internepas)) {
		$res = dolibarr_set_const($db, 'VOLVO_ANALYSELG_Y_INTERNE_PAS', $internepas, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$externenb = GETPOST('VOLVO_ANALYSELG_Y_EXTERNE_NB', 'int');
	if (! empty($externenb)) {
		$res = dolibarr_set_const($db, 'VOLVO_ANALYSELG_Y_EXTERNE_NB', $externenb, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$externeoffset = GETPOST('VOLVO_ANALYSELG_Y_EXTERNE_OFFSET', 'alpha');
	if (! empty($externeoffset)) {
		$res = dolibarr_set_const($db, 'VOLVO_ANALYSELG_Y_EXTERNE_OFFSET', $externeoffset, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$externepas = GETPOST('VOLVO_ANALYSELG_Y_EXTERNE_PAS', 'alpha');
	if (! empty($externepas)) {
		$res = dolibarr_set_const($db, 'VOLVO_ANALYSELG_Y_EXTERNE_PAS', $externepas, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$diversnb = GETPOST('VOLVO_ANALYSELG_Y_DIVERS_NB', 'int');
	if (! empty($diversnb)) {
		$res = dolibarr_set_const($db, 'VOLVO_ANALYSELG_Y_DIVERS_NB', $diversnb, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$diversoffset = GETPOST('VOLVO_ANALYSELG_Y_DIVERS_OFFSET', 'alpha');
	if (! empty($diversoffset)) {
		$res = dolibarr_set_const($db, 'VOLVO_ANALYSELG_Y_DIVERS_OFFSET', $diversoffset, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$diverspas = GETPOST('VOLVO_ANALYSELG_Y_DIVERS_PAS', 'alpha');
	if (! empty($diverspas)) {
		$res = dolibarr_set_const($db, 'VOLVO_ANALYSELG_Y_DIVERS_PAS', $diverspas, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$vonb = GETPOST('VOLVO_ANALYSELG_Y_VO_NB', 'int');
	if (! empty($vonb)) {
		$res = dolibarr_set_const($db, 'VOLVO_ANALYSELG_Y_VO_NB', $vonb, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$vooffset = GETPOST('VOLVO_ANALYSELG_Y_VO_OFFSET', 'alpha');
	if (! empty($vooffset)) {
		$res = dolibarr_set_const($db, 'VOLVO_ANALYSELG_Y_VO_OFFSET', $vooffset, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$vopas = GETPOST('VOLVO_ANALYSELG_Y_VO_PAS', 'alpha');
	if (! empty($vopas)) {
		$res = dolibarr_set_const($db, 'VOLVO_ANALYSELG_Y_VO_PAS', $vopas, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$ypied = GETPOST('VOLVO_ANALYSELG_Y_PIED', 'alpha');
	$test = count(explode(',', $ypied));
	if (! empty($ypied) && $test ==9) {
		$res = dolibarr_set_const($db, 'VOLVO_ANALYSELG_Y_PIED', $ypied, 'chaine', 0, '', $conf->entity);
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
dol_fiche_head($head, 'analyse_lg', 'Admin Module Theobald', 0);

$form = new Form($db);

// Admin var of module
print_fiche_titre($langs->trans("Modele de document Analyse"));

print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" >';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="setvar">';

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Valeur") . '</td>';
print "</tr>\n";

// Liste des Coordonnées X du Pdf
print '<tr class="impair"><td>Colonnes</td>';
print '<td align="left">X: <input type="text" name="VOLVO_ANALYSELG_X" value="' . $conf->global->VOLVO_ANALYSELG_X . '" size="30" >';
print '  Z: <input type="text" name="VOLVO_ANALYSELG_Z" value="' . $conf->global->VOLVO_ANALYSELG_Z . '" size="30" ></td>';
print '</tr>';

// Coordonnées Y entete
print '<tr class="impair"><td>Coordonnées Y de l\'entête (Attendu 9 Valeurs)</td>';
print '<td align="left"><input type="text" name="VOLVO_ANALYSELG_Y_ENTETE" value="' . $conf->global->VOLVO_ANALYSELG_Y_ENTETE . '" size="60" ></td>';
print '</tr>';

// Coordonnées Y Travaux interne
print '<tr class="impair"><td>Coordonnées Y Travaux Interne</td>';
print '<td align="left">Nb.: <input type="text" name="VOLVO_ANALYSELG_Y_INTERNE_NB" value="' . $conf->global->VOLVO_ANALYSELG_Y_INTERNE_NB . '" size="3" >';
print '  Offset: <input type="text" name="VOLVO_ANALYSELG_Y_INTERNE_OFFSET" value="' . $conf->global->VOLVO_ANALYSELG_Y_INTERNE_OFFSET . '" size="5" >';
print '  Offset: <input type="text" name="VOLVO_ANALYSELG_Y_INTERNE_PAS" value="' . $conf->global->VOLVO_ANALYSELG_Y_INTERNE_PAS . '" size="3" >';
print '</td></tr>';

// Coordonnées Y Travaux externe
print '<tr class="impair"><td>Coordonnées Y Travaux Externe</td>';
print '<td align="left">Nb.: <input type="text" name="VOLVO_ANALYSELG_Y_EXTERNE_NB" value="' . $conf->global->VOLVO_ANALYSELG_Y_EXTERNE_NB . '" size="3" >';
print '  Offset: <input type="text" name="VOLVO_ANALYSELG_Y_EXTERNE_OFFSET" value="' . $conf->global->VOLVO_ANALYSELG_Y_EXTERNE_OFFSET . '" size="5" >';
print '  Offset: <input type="text" name="VOLVO_ANALYSELG_Y_EXTERNE_PAS" value="' . $conf->global->VOLVO_ANALYSELG_Y_EXTERNE_PAS . '" size="3" >';
print '</td></tr>';

// Coordonnées Y Travaux Divers
print '<tr class="impair"><td>Coordonnées Y Travaux Dvers</td>';
print '<td align="left">Nb.: <input type="text" name="VOLVO_ANALYSELG_Y_DIVERS_NB" value="' . $conf->global->VOLVO_ANALYSELG_Y_DIVERS_NB . '" size="3" >';
print '  Offset: <input type="text" name="VOLVO_ANALYSELG_Y_DIVERS_OFFSET" value="' . $conf->global->VOLVO_ANALYSELG_Y_DIVERS_OFFSET . '" size="5" >';
print '  Offset: <input type="text" name="VOLVO_ANALYSELG_Y_DIVERS_PAS" value="' . $conf->global->VOLVO_ANALYSELG_Y_DIVERS_PAS . '" size="3" >';
print '</td></tr>';

// Coordonnées Y VO
print '<tr class="impair"><td>Coordonnées Y Travaux Externe</td>';
print '<td align="left">Nb.: <input type="text" name="VOLVO_ANALYSELG_Y_VO_NB" value="' . $conf->global->VOLVO_ANALYSELG_Y_VO_NB . '" size="3" >';
print '  Offset: <input type="text" name="VOLVO_ANALYSELG_Y_VO_OFFSET" value="' . $conf->global->VOLVO_ANALYSELG_Y_VO_OFFSET . '" size="5" >';
print '  Offset: <input type="text" name="VOLVO_ANALYSELG_Y_VO_PAS" value="' . $conf->global->VOLVO_ANALYSELG_Y_VO_PAS . '" size="3" >';
print '</td></tr>';

// Coordonnées Y entete
print '<tr class="impair"><td>Coordonnées Y du pied de page (Attendu 9 Valeurs)</td>';
print '<td align="left"><input type="text" name="VOLVO_ANALYSELG_Y_PIED" value="' . $conf->global->VOLVO_ANALYSELG_Y_PIED . '" size="60" ></td>';
print '</tr>';

print '</table>';

print '<tr class="impair"><td colspan="2" align="right"><input type="submit" class="button" value="' . $langs->trans("Save") . '"></td>';
print '</tr>';

print '</table><br>';
print '</form>';

dol_fiche_end();

llxFooter();

$db->close();
