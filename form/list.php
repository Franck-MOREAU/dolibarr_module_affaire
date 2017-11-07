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
 * \file lead/lead/list.php
 * \ingroup lead
 * \brief list of lead
 */
$res = @include '../../main.inc.php'; // For root directory
if (! $res)
	$res = @include '../../../main.inc.php'; // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once '../class/lead.class.php';
require_once '../lib/lead.lib.php';
require_once '../class/html.formlead.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/volvo/class/reprise.class.php';
require_once DOL_DOCUMENT_ROOT . '/volvo/class/lead.extend.class.php';

global $user;

$reprise = new Reprise($db);

// Security check
if (! $user->rights->lead->read)
	accessforbidden();

$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');
$do_action = GETPOST('do_action','int');


//Socid is fill when come from thirdparty tabs
$socid=GETPOST('socid','int');

//view type is special predefined filter
$viewtype=GETPOST('viewtype','alpha');

// Search criteria
$search_ref = GETPOST("search_ref");
$search_soc = GETPOST("search_soc");
$search_status = GETPOST('search_status');
if ($search_status == - 1)
	$search_status = 0;
$search_type = GETPOST('search_type');
if ($search_type == - 1)
	$search_type = 0;
$search_eftype = GETPOST('search_eftype');
if ($search_eftype == - 1)
	$search_eftype = 0;
$search_carrosserie = GETPOST('search_carrosserie');
if ($search_carrosserie == - 1)
	$search_carrosserie = 0;
$search_commercial = GETPOST('search_commercial');
if ($search_commercial == - 1)
	$search_commercial = '';

$search_month = GETPOST('search_month', 'aplha');
$search_year = GETPOST('search_year', 'int');


$link_element = GETPOST("link_element");
if (! empty($link_element)) {
	$action = 'link_element';
}

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x")) {
	$search_ref = '';
	$search_commercial = '';
	$search_soc = '';
	$search_status = '';
	$search_type = '';
	$search_eftype = '';
	$search_carrosserie = '';
	$search_month = '';
	$search_year = '';
}

if($do_action > 0){
	$act_type = GETPOST('action_'.$do_action,'int');
	if(isset($act_type)){
		if($act_type==1){
			header('Location: ' . DOL_URL_ROOT . '/custom/lead/lead/card.php?id=' . $do_action . '&action=edit');
			exit;
		}elseif($act_type == 2){
			$lead = new Leadext($db);
			$lead->fetch($do_action);
			$lead->fk_c_status = 6;
			$lead->update($user);
		}elseif($act_type == 3){
			$lead = new Leadext($db);
			$lead->fetch($do_action);
			$lead->fk_c_status = 7;
			$lead->update($user);
		}elseif($act_type == 4){
			$lead = new Leadext($db);
			$lead->fetch($do_action);
			$lead->fk_c_status = 11;
			$lead->update($user);
		}elseif($act_type == 5){
			$lead = new Leadext($db);
			$lead->fetch($do_action);
			$lead->fk_c_status = 5;
			$lead->update($user);
		}elseif($act_type == 6){
			$lead = new Leadext($db);
			$lead->fetch($do_action);
			$lead->array_options["options_chaude"] = 1;
			$lead->update($user);
		}elseif($act_type == 7){
			$lead = new Leadext($db);
			$lead->fetch($do_action);
			$lead->array_options["options_chaude"] = 0;
			$lead->update($user);
		}elseif($act_type == 8){
			$lead = new Leadext($db);
			$lead->fetch($do_action);
			$lead->array_options["options_new"] = 1;
			$lead->update($user);
		}elseif($act_type == 9){
			$lead = new Leadext($db);
			$lead->fetch($do_action);
			$lead->array_options["options_new"] = 0;
			$lead->update($user);
		}
	}
}

$search_commercial_disabled = 0;
if (empty($user->rights->volvo->stat_all)){
	$search_commercial = $user->id;
	$selected_commercial = $user->id;
}

$user_included=array();
$sqlusers = "SELECT fk_user FROM " . MAIN_DB_PREFIX . "usergroup_user WHERE fk_usergroup = 1";
$resqlusers  = $db->query($sqlusers);
if($resqlusers){
	while ($users = $db->fetch_object($resqlusers)){
		$user_included[] = $users->fk_user;
	}
}

$filter = array();
if (! empty($search_ref)) {
	$filter['t.ref'] = $search_ref;
	$option .= '&search_ref=' . $search_ref;
}
if (! empty($search_commercial)) {
	$filter['t.fk_user_resp'] = $search_commercial;
	$option .= '&search_commercial=' . $search_commercial;
}
if (! empty($search_soc)) {
	$filter['so.nom'] = $search_soc;
	$option .= '&search_soc=' . $search_soc;
}
if (! empty($search_status)) {
	$filter['t.fk_c_status'] = $search_status;
	$option .= '&search_status=' . $search_status;
}
if (! empty($search_type)) {
	$filter['t.fk_c_type'] = $search_type;
	$option .= '&search_type=' . $search_type;
}
if (! empty($search_eftype)) {
	$filter['leadextra.gamme'] = $search_eftype;
	$option .= '&search_eftype=' . $search_eftype;
}
if (! empty($search_carrosserie)) {
	$filter['leadextra.carroserie'] = $search_carrosserie;
	$option .= '&search_carrosserie=' . $search_carrosserie;
}
if (! empty($search_month)) {
	$filter['MONTH(t.datec)'] = $search_month;
	$option .= '&search_month=' . $search_month;
}
if (! empty($search_year)) {
	$filter['YEAR(t.datec)'] = $search_year;
	$option .= '&search_year=' . $search_year;
}

if (!empty($viewtype)) {
	if ($viewtype=='current') {
		$filter['t.fk_c_status !IN'] = '6,7,11';
	}
	if ($viewtype=='lost') {
		$filter['t.fk_c_status !IN'] = '6,5,11';
	}
	if ($viewtype=='cancel') {
		$filter['t.fk_c_status !IN'] = '6,5,7';
	}
	if ($viewtype=='won') {
		$filter['t.fk_c_status !IN'] = '5,7,11';
	}
	if ($viewtype=='hot') {
		$filter['leadextra.chaude'] = '1';
		$filter['t.fk_c_status !IN'] = '6,7,11';
	}

	if ($viewtype=='my') {
		$filter['t.fk_user_resp'] = $user->id;
	}
	if ($viewtype=='mycurrent') {
		$filter['t.fk_user_resp'] = $user->id;
		$filter['t.fk_c_status !IN'] = '6,7,11';
	}
	if ($viewtype=='mylost') {
		$filter['t.fk_user_resp'] = $user->id;
		$filter['t.fk_c_status !IN'] = '6,5,11';
	}
	if ($viewtype=='mycancel') {
		$filter['t.fk_user_resp'] = $user->id;
		$filter['t.fk_c_status !IN'] = '6,5,7';
	}
	if ($viewtype=='mywon') {
		$filter['t.fk_user_resp'] = $user->id;
		$filter['t.fk_c_status !IN'] = '5,7,11';
	}
	if ($viewtype=='late') {
		$filter['t.fk_c_status !IN'] = '6,7,11';
		$filter['t.date_closure<'] = dol_now();
	}
	if ($viewtype=='myhot') {
		$filter['t.fk_user_resp'] = $user->id;
		$filter['leadextra.chaude'] = '1';
		$filter['t.fk_c_status !IN'] = '6,7,11';
	}
	$option .= '&viewtype=' . $viewtype;
}


if ($page == - 1) {
	$page = 0;
}

$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$form = new Form($db);
$formlead = new FormLead($db);
$object = new Leadext($db);
$formother = new FormOther($db);

if (empty($sortorder))
	$sortorder = "DESC";
if (empty($sortfield))
	$sortfield = "t.datec";

$title = $langs->trans('LeadList');

llxHeader('', $title);

if (!empty($socid)) {
	require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	$soc = new Societe($db);
	$soc->fetch($socid);
	$head = societe_prepare_head($soc);

	dol_fiche_head($head, 'tabLead', $langs->trans("Module103111Name"),0,dol_buildpath('/lead/img/object_lead.png', 1),1);
}

// Count total nb of records
$nbtotalofrecords = 0;

if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$nbtotalofrecords = $object->fetch_all($sortorder, $sortfield, 0, 0, $filter);
}
$resql = $object->fetch_all($sortorder, $sortfield, $conf->liste_limit, $offset, $filter);

if ($resql != - 1) {
	$num = $resql;

	print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $option, $sortfield, $sortorder, '', $num, $nbtotalofrecords);



	print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="search_form">' . "\n";

	if (! empty($sortfield))
		print '<input type="hidden" name="sortfield" value="' . $sortfield . '"/>';
	if (! empty($sortorder))
		print '<input type="hidden" name="sortorder" value="' . $sortorder . '"/>';
	if (! empty($page))
		print '<input type="hidden" name="page" value="' . $page . '"/>';
	if (! empty($viewtype))
		print '<input type="hidden" name="viewtype" value="' . $viewtype . '"/>';
	if (! empty($socid))
		print '<input type="hidden" name="socid" value="' . $socid . '"/>';

	$moreforfilter = $langs->trans('Period') . '(' . $langs->trans("LeadDateDebut") . ')' . ': ';
	$moreforfilter .= $langs->trans('Month') . ':<input class="flat" type="text" size="4" name="search_month" value="' . $search_month . '">';
	$moreforfilter .= $langs->trans('Year') . ':' . $formother->selectyear($search_year ? $search_year : - 1, 'search_year', 1, 20, 5);

	if ($moreforfilter) {
		print '<div class="liste_titre">';
		print $moreforfilter;
		print '</div>';
	}

	$i = 0;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" align="center">Action</td>';
	print_liste_field_titre($langs->trans("Ref"), $_SERVEUR['PHP_SELF'], "t.ref", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("LeadCommercial"), $_SERVEUR['PHP_SELF'], "usr.lastname", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Customer"), $_SERVEUR['PHP_SELF'], "so.nom", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("LeadStatus"), $_SERVEUR['PHP_SELF'], "leadsta.label", "", $option, '', $sortfield, $sortorder);
	print '<th>Nb Annoncé</th>';
	print_liste_field_titre('Canal de vente', $_SERVEUR['PHP_SELF'], "leadtype.label", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre('Type', $_SERVEUR['PHP_SELF'], "leadextra.gamme", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre('Carrosserie', $_SERVEUR['PHP_SELF'], "leadextra.carroserie", "", $option, '', $sortfield, $sortorder);
	print '<th>Nb commandé</th>';
	print '<th>Montant annoncé</th>';
	print '<th>Montant des commandes</th>';
	print '<th>Marge a date</th>';
	print '<th>Marge a date réelle</th>';

	print "</tr>\n";

	print '<tr class="liste_titre">';

	// edit button
	print '<td class="liste_titre" align="center"><input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
	print '</td>';

	print '<td><input type="text" class="flat" name="search_ref" value="' . $search_ref . '" size="5"></td>';

	print '<td class="liste_titre">';
	print  $form->select_dolusers($search_commercial,'search_commercial',1,array(),$search_commercial_disabled,$user_included);
	print '</td>';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_soc" value="' . $search_soc . '" size="20">';
	print '</td>';

	print '<td class="liste_titre">';
	print $formlead->select_lead_status($search_status, 'search_status', 1);
	print '</td>';

	// Nb commandé
	print '<td id="totalcmd" align="right"></td>';

	print '<td class="liste_titre">';
	print $formlead->select_lead_type($search_type, 'search_type', 1);
	print '</td>';


	print '<td class="liste_titre">';
	print $form->selectarray('search_eftype',$reprise->gamme,$search_eftype,1);
	print '</td>';

	print '<td class="liste_titre">';
	print $form->selectarray('search_carrosserie',$reprise->carrosserie_dict,$search_carrosserie,1);
	print '</td>';

	// Nb commandé
	print '<td id="totalcmd" align="right"></td>';

	// amount guess
	print '<td id="totalamountguess" align="right"></td>';
	// amount real
	print '<td id="totalamountreal" align="right"></td>';

	print '<td id="totalmargin" align="right"></td>';

	print '<td id="totalmarginreal" align="right"></td>';

	print "</tr>\n";


	$var = true;
	$totalamountguess = 0;
	$totalamountreal = 0;

	foreach ($object->lines as $line) {
		$lead = New Leadext($db);
		$lead->fetch($line->id);
		if ($lead->array_options["options_chaude"] && $lead->fk_c_status == 5) {
			$chaude = '<img src="' . DOL_URL_ROOT . '/theme/eldy/img/recent.png">';
		}else{
			$chaude ='';
		}
		if ($lead->array_options["options_new"]) {
			$new = '<img src="' . DOL_URL_ROOT . '/theme/eldy/img/high.png">';
		}else{
			$new = '<img src="' . DOL_URL_ROOT . '/theme/eldy/img/object_company.png">';
		}

		$list = '<select class="flat" id="action_' . $line->id . '" name="action_' . $line->id . '" style="width: 75px;">';
    	$list.= '<option value="1" selected>Editer</option>';
    	if($lead->status_label !='Traitée') $list.= '<option value="2">traitée</option>';
    	if($lead->status_label !='Perdu' && $lead->getnbchassisreal() ==0) $list.= '<option value="3">perdue</option>';
    	if($lead->status_label !='Sans suite' && $lead->getnbchassisreal() ==0)	$list.= '<option value="4">s. suite</option>';
    	if($lead->status_label !='En cours' && $lead->getnbchassisreal() == 0) $list.= '<option value="5">En cours</option>';
    	if(empty($lead->array_options["options_chaude"]) && $lead->status_label =='En cours') $list.= '<option value="6">Chaude</option>';
    	if(!empty($lead->array_options["options_chaude"]) && $lead->status_label =='En cours') 	$list.= '<option value="7">Froide</option>';
    	if(empty($lead->array_options["options_new"])) $list.= '<option value="8">Nouveau C</option>';
    	if(!empty($lead->array_options["options_new"])) 	$list.= '<option value="9">Déja C</option>';
    	$list.= '</select>';
        $list.= '<button type="submit" name="do_action" value="' . $line->id . '"><img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/tick.png">';


		/**
		 * @var Lead $line
		 */

		// Affichage tableau des lead
		$var = ! $var;
		print '<tr ' . $bc[$var] . '>';

		print '<td align="center" style="white-space:nowrap">' . $list . '</td>';

		// Ref
		print '<td><a href="card.php?id=' . $line->id . '">' . $line->ref . '</a>';
		if ($line->fk_c_status!=6) {
			$result=$line->isObjectSignedExists();
			if ($result<0) {
				setEventMessages($line->error, null, 'errors');
			}elseif ($result>0) {
				print img_warning($langs->trans('LeadObjectWindExists'));
			}
		}
		print '</td>';

		// Commercial
		print '<td>';
		if (! empty($line->fk_user_resp)) {
			$userstatic = new User($db);
			$userstatic->fetch($line->fk_user_resp);
			if (! empty($userstatic->id)) {
				print $userstatic->getFullName($langs);
			}
		}
		print '</td>';

		// Societe
		print '<td>';
		if (! empty($line->fk_soc) && $line->fk_soc != - 1) {
			$soc = new Societe($db);
			$soc->fetch($line->fk_soc);
			print $new . ' ';
			print $soc->getNomURL(0);
		} else {
			print '&nbsp;';
		}
		print '</td>';

		// Status
		print '<td>' . $chaude . ' ' .$line->status_label . '</td>';

		// Tnb chassis annoncé
		print '<td>' . $line->array_options['options_nbchassis'] . '</td>';

		// canal de vente
		print '<td>' . $line->type_label . '</td>';

		// gamme
		print '<td>' . $reprise->gamme[$lead->array_options['options_gamme']] . '</td>';

		// carrosserie
		print '<td>' . $reprise->carrosserie_dict[$lead->array_options['options_carroserie']] . '</td>';

		//nb chassis reel
		print '<td>' . $lead->getnbchassisreal() . '</td>';

		// Amount prosp
		print '<td align="right">' . price($line->amount_prosp) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td>';
		$totalamountguess += $line->amount_prosp;

		// Amount real
		$amount = $lead->getRealAmount2();
		print '<td  align="right">' . price($amount) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td>';
		$totalamountreal += $amount;

		// MArgin
		$amount = $lead->getmargin('theo');
		print '<td  align="right">' . price($amount) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td>';
		$totalmargin += $amount;

		// Margin real
		$amount = $lead->getmargin('real');
		print '<td  align="right">' . price($amount) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td>';
		$totalmarginreal += $amount;

		print "</tr>\n";

		$i ++;
	}

	print "</table>";

	print '</form>';

	print '<script type="text/javascript" language="javascript">' . "\n";
	print '$(document).ready(function() {
					$("#totalamountguess").append("' . price($totalamountguess) . $langs->getCurrencySymbol($conf->currency) . '");
					$("#totalamountreal").append("' . price($totalamountreal) . $langs->getCurrencySymbol($conf->currency) . '");
					$("#totalmargin").append("' . price($totalmargin) . $langs->getCurrencySymbol($conf->currency) . '");
					$("#totalmarginreal").append("' . price($totalmarginreal) . $langs->getCurrencySymbol($conf->currency) . '");
			});';
	print "\n" . '</script>' . "\n";
} else {
	setEventMessages(null, $object->errors, 'errors');
}

dol_fiche_end();
llxFooter();
$db->close();
