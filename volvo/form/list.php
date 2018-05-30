<?php
/*
 * Copyright (C) 2014 Florian HENRY <florian.henry@open-concept.pro>
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

dol_include_once('/affaires/volvo/class/volvo_report.class.php');
dol_include_once('/core/class/html.formother.class.php');

$object = New volvo_report($db);

// Security check
if (! $user->rights->affaires->volvo->business)
	accessforbidden();

$form = new Form($db);
$formother = new FormOther($db);


$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'affaireslist';
$hookmanager->initHooks(array($contextpage));

$arrayfields = array(
		'commercial' => array(
				'label' => "Commercial",
				'checked' => 1,
				'displayfield' => 'commercial',
				'sortfield'=>"CONCAT(user.firstname, ' ' , user.lastname)"
		),
		'numom' => array(
				'label' => "N° O.M.",
				'checked' => 1,
				'displayfield' => 'numom',
				'sortfield'=>'cmd_ef.numom'
		),
		'cmd_ref' => array(
				'label' => "Dossier",
				'checked' => 1,
				'displayfield' => 'cmd_ref',
				'sortfield'=>'cmd.ref'
		),
		'af_ref' => array(
				'label' => "Affaire",
				'checked' => 1,
				'displayfield' => 'af_ref',
				'sortfield'=>'af.ref'
		),
		'soc.nom' => array(
				'label' => "client",
				'checked' => 1,
				'displayfield' => 'soc_url',
				'sortfield'=>'soc.nom'
		),
		'ctm.nom' => array(
				'label' => "contremarque",
				'checked' => 1,
				'displayfield' => 'ctm_url',
				'sortfield'=>'ctm.nom'
		),
		'chassis' => array(
				'label' => "N° de Chassis",
				'checked' => 1,
				'displayfield' => 'chassis',
				'sortfield'=>'RIGHT(cmd_ef.vin,7)'
		),
		'cmd_ef.immat' => array(
				'label' => "Immat",
				'checked' => 1,
				'displayfield' => 'immat',
				'sortfield'=>'cmd_ef.immat'
		),
		'cmdf.date_commande' => array(
				'label' => "Date Envoi Cmd Usine",
				'checked' => 1,
				'displayfield' => 'cmd_env_usi',
				'sortfield'=>'cmdf.date_commande',
				'type' => 'date'
		),
		'cmd_ef.dt_blockupdate' => array(
				'label' => "Date de bloc. Modif.",
				'checked' => 1,
				'displayfield' => 'dt_block_update',
				'sortfield'=>'cmd_ef.dt_blockupdate',
				'type' => 'date'
		),
		'cmdf.date_livraison' => array(
				'label' => "Date de livraison prévue",
				'checked' => 1,
				'displayfield' => 'dt_liv_prev',
				'sortfield'=>'cmdf.date_livraison',
				'type' => 'date'
		),
		'cmd_ef.dt_liv_maj' => array(
				'label' => "Date de livraison MAJ",
				'checked' => 1,
				'displayfield' => 'dt_liv_maj',
				'sortfield'=>'cmd_ef.dt_liv_maj',
				'type' => 'date'
		),
		'cmd_ef.dt_lim_annul' => array(
				'label' => "Date limite Anul.",
				'checked' => 1,
				'displayfield' => 'dt_lim_annul',
				'sortfield'=>'cmd_ef.dt_lim_annul',
				'type' => 'date'
		),
		'event1.datep' => array(
				'label' => "Date de livraison réele",
				'checked' => 1,
				'displayfield' => 'dt_liv_usi_reel',
				'sortfield'=>'event1.datep',
				'type' => 'date'
		),
		'cmd.date_valid' => array(
				'label' => "Validation fiche analyse",
				'checked' => 1,
				'displayfield' => 'date_valid',
				'sortfield'=>'cmd.date_valid',
				'type' => 'date'
		),
		'cmd.date_livraison' => array(
				'label' => "Date de livraison demandée",
				'checked' => 1,
				'displayfield' => 'dt_prev_liv_cli',
				'sortfield'=>'cmd.date_livraison',
				'type' => 'date'
		),
		'event2.datep' => array(
				'label' => "Date de livraison réelle",
				'checked' => 1,
				'displayfield' => 'dt_liv_cli_reel',
				'sortfield'=>'event2.datep',
				'type' => 'date'
		),
		'cmd_ef.dt_invoice' => array(
				'label' => "Date de facturation",
				'checked' => 1,
				'displayfield' => 'dt_facture',
				'sortfield'=>'cmd_ef.dt_invoice',
				'type' => 'date'
		),
		'event3.datep' => array(
				'label' => "Date de paiement",
				'checked' => 1,
				'displayfield' => 'dt_pay',
				'sortfield'=>'event3.datep',
				'type' => 'date'
		),
		'delai_cash' => array(
				'label' => "Délai Cash",
				'checked' => 1,
				'displayfield' => 'delai_cash',
				'sortfield'=>'DATEDIFF(IFNULL(event3.datep,CURDATE()),event1.datep)',
				'moredisplayvalue'=>' Jour(s)'
		),
		'retard_liv_usi' => array(
				'label' => "Retard liv. Usine",
				'checked' => 1,
				'displayfield' => 'retard_liv_usi',
				'sortfield'=>'DATEDIFF(event1.datep,cmdf.date_livraison)',
				'moredisplayvalue'=>' Jour(s)'
		),
		'retard_liv_cli' => array(
				'label' => "Retard liv. Client",
				'checked' => 1,
				'displayfield' => 'retard_liv_cli',
				'sortfield'=>'DATEDIFF(event2.datep,cmd.date_livraison)',
				'moredisplayvalue'=>' Jour(s)'
		),

);


$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
if (empty($reshook))
{
	include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';
}

$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (empty($sortorder))
	$sortorder = "DESC";
	if (empty($sortfield))
		$sortfield = "cmd_ef.numom";

// View
$title = 'Suivi des affaires en cours';
$filter=array();
llxHeader('', $title);

$nbtotalofrecords = 0;

if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$nbtotalofrecords = $object->fetch_All_folow($sortorder, $sortfield, 0, 0, $filter);
}
$resql = $object->fetch_All_folow($sortorder, $sortfield, $limit, $offset, $filter);
$moreforfilter = '';

$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

if ($resql != - 1) {
	$num = $resql;

	print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="searchFormList" id="searchFormList">' . "\n";
	print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $option, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'object_iron02@affaires', 0, '', '', $limit);
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';

	if (! empty($sortfield)) {
		print '<input type="hidden" name="sortfield" value="' . $sortfield . '"/>';
	}
	if (! empty($sortorder)) {
		print '<input type="hidden" name="sortorder" value="' . $sortorder . '"/>';
	}
	if (! empty($page)) {
		print '<input type="hidden" name="page" value="' . $page . '"/>';
	}
	if (! empty($viewtype)) {
		print '<input type="hidden" name="viewtype" value="' . $viewtype . '"/>';
	}
	if (! empty($socid)) {
		print '<input type="hidden" name="socid" value="' . $socid . '"/>';
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	// This also change content of $arrayfields
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

	// Fields title
	print '<tr class="liste_titre">';
	foreach ( $arrayfields as $key => $val ) {
		if (! empty($val['checked'])) {
			print_liste_field_titre($val['label'], $_SERVER["PHP_SELF"], $val['sortfield'], '', $option, '', $sortfield, $sortorder);
		}
	}
	$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
	$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;


	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', $option, 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	print '</tr>' . "\n";

	foreach ( $object->lines as $line ) {
		print '<tr class="oddeven">';

		foreach ( $arrayfields as $key => $val ) {
			if (! empty($val['checked'])) {
				print '<td>';
				if (!empty($val['displayfield'])) {
					if(empty($val['type'])){
						print  $line->{$val['displayfield']};
					}else{
						switch($val['type']){
							case 'date':
								print dol_print_date($line->{$val['displayfield']},'day');
								break;

							case 'price':
								print price($line->{$val['displayfield']});
								break;
						}
					}

					if (array_key_exists('sumvar', $val) && !empty($val['sumvar'])) {
						${$key}+=$line->{$val['displayfield']};
					}
				} elseif (!empty($val['evaldisplayfield'])) {
					print call_user_func_array(array($object, $val['evaldisplayfield']),array($line->id));

					if (array_key_exists('sumvar', $val) && !empty($val['sumvar'])) {
						${$key}+=call_user_func_array(array($object, $val['evaldisplayfield']),array($line->id));
					}
				}
				if (array_key_exists('moredisplayvalue', $val) && !empty($val['moredisplayvalue']) && !empty($line->{$val['displayfield']})) {
					print $val['moredisplayvalue'];
				}
				print '</td>';

			}
		}

		print '<td></td>';
		print "</tr>\n";

		$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
		$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
	}

	$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$object);
	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print "</table>";
	print '</div>';

	print '</form>';


} else {
	setEventMessages(null, $objectdet->errors, 'errors');
}

dol_fiche_end();
llxFooter();
$db->close();

