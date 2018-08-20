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
dol_include_once('/affaires/class/html.formaffaires.class.php');
dol_include_once('/affaires/class/affaires.class.php');

$object = New volvo_report($db);

// Security check
if (! $user->rights->affaires->volvo->business)
	accessforbidden();

$affaire = new Affaires_det($db);
$form = new Form($db);
$formother = new FormOther($db);
$formAffaires = new FormAffaires($db);

$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'businesslist';
$search_run= GETPOST('search_run');
$hookmanager->initHooks(array($contextpage));


$_SESSION[$contextpage . '_arrayfields']= '';

$arrayfields = array(
		'run' => array(
				'label' => "Selection uniquement sur les affaires en cours ? ",
				'enabled' => 0,
				'checked' => 1,
				'search' => array('(event3.datep IS NULL OR cmd_ef.dt_invoice IS NULL OR event2.datep IS NULL)'),
				'type' => 'predifined',
				'post' => 'search_run'
		),

		'commercial' => array(
				'label' => "Commercial",
				'checked' => 1,
				'displayfield' => 'commercial',
				'search' => array('user.rowid'),
				'type' => 'user',
				'post' => 'fk_user_resp'
		),
		'numom' => array(
				'label' => "N° O.M.",
				'checked' => 1,
				'displayfield' => 'cmdf_url',
				'search' => array('cmd_ef.numom'),
				'sortfield'=>'cmd_ef.numom',
				'type' => 'text',
				'post' => 'numom'
		),
		'cmd_ref' => array(
				'label' => "Dossier",
				'checked' => 1,
				'displayfield' => 'cmd_url',
				'search' => array('cmd.ref'),
				'sortfield'=>'cmd.ref',
				'type' => 'text',
				'post' => 'dossier'
		),
		'af_ref' => array(
				'label' => "Affaire",
				'checked' => 1,
				'displayfield' => 'af_url',
				'search' => array('af.ref'),
				'sortfield'=>'af.ref',
				'type' => 'text',
				'post' => 'affaire'
		),
		'soc.nom' => array(
				'label' => "client",
				'checked' => 1,
				'displayfield' => 'soc_url',
				'search' => array('soc.rowid','soc.nom','(soc.rowid,ctm.rowid)'),
				'sortfield'=>'soc.nom',
				'type' => 'soc',
				'post' => 'soc'
		),
		'ctm.nom' => array(
				'label' => "contremarque",
				'checked' => 1,
				'displayfield' => 'ctm_url',
				'search' => array('ctm.rowid','ctm.nom','ctm.rowid'),
				'sortfield'=>'ctm.nom',
				'type' => 'ctm',
				'post' => 'ctm'
		),
		'genre.genre' => array(
				'label' => "Genre",
				'checked' => 1,
				'displayfield' => 'genre',
				'search' => array('af_det.fk_genre'),
				'sortfield'=>'genre.genre',
				'type' => 'list',
				'post' => 'genre',
				'data' => $affaire->genre_dict
		),
		'gamme.gamme' => array(
				'label' => "Gamme",
				'checked' => 1,
				'displayfield' => 'gamme',
				'search' => array('af_det.fk_gamme'),
				'sortfield'=>'gamme.gamme',
				'type' => 'list',
				'post' => 'gamme',
				'data' => $affaire->gamme_dict
		),
		'sil.silhouette' => array(
				'label' => "Silhouette",
				'checked' => 1,
				'displayfield' => 'sil',
				'search' => array('af_det.fk_silhouette'),
				'sortfield'=>'sil.silhouette',
				'type' => 'list',
				'post' => 'sil',
				'data' => $affaire->silhouette_dict
		),
		'car.carrosserie' => array(
				'label' => "Carrosserie",
				'checked' => 1,
				'displayfield' => 'car',
				'search' => array('af_det.fk_carrosserie'),
				'sortfield'=>'car.carrosserie',
				'type' => 'list',
				'post' => 'car',
				'data' => $affaire->carrosserie_dict
		),
		'chassis' => array(
				'label' => "N° de Chassis",
				'checked' => 1,
				'displayfield' => 'chassis',
				'search' => array('cmd_ef.vin'),
				'sortfield'=>'RIGHT(cmd_ef.vin,7)',
				'type' => 'text',
				'post' => 'chassis'
		),
		'cmd_ef.immat' => array(
				'label' => "Immat",
				'checked' => 1,
				'displayfield' => 'immat',
				'search' => array('cmd_ef.immat'),
				'sortfield'=>'cmd_ef.immat',
				'type' => 'text',
				'post' => 'immat'
		),
		'cmdf.date_commande' => array(
				'label' => "Date Envoi Cmd Usine",
				'checked' => 1,
				'displayfield' => 'cmd_env_usi',
				'sortfield'=>'cmdf.date_commande',
				'search' => array('cmdf.date_commande'),
				'type' => 'date',
				'post' => 'cmd_env_usi'
		),
		'cmd_ef.dt_blockupdate' => array(
				'label' => "Date de bloc. Modif.",
				'checked' => 1,
				'displayfield' => 'dt_block_update',
				'sortfield'=>'cmd_ef.dt_blockupdate',
				'search' => array('cmd_ef.dt_blockupdate'),
				'type' => 'date',
				'post'=>'dt_block_update'
		),
		'cmdf.date_livraison' => array(
				'label' => "Date de livraison prévue",
				'checked' => 1,
				'displayfield' => 'dt_liv_prev',
				'sortfield'=>'cmdf.date_livraison',
				'search' => array('cmdf.date_livraison'),
				'type' => 'date',
				'post'=>'dt_liv_prev'
		),
		'cmd_ef.dt_liv_maj' => array(
				'label' => "Date de livraison MAJ",
				'checked' => 1,
				'displayfield' => 'dt_liv_maj',
				'sortfield'=>'cmd_ef.dt_liv_maj',
				'search' => array('cmd_ef.dt_liv_maj'),
				'type' => 'date',
				'post'=>'dt_liv_maj'
		),
		'cmd_ef.dt_lim_annul' => array(
				'label' => "Date limite Anul.",
				'checked' => 1,
				'displayfield' => 'dt_lim_annul',
				'sortfield'=>'cmd_ef.dt_lim_annul',
				'search' => array('cmd_ef.dt_lim_annul'),
				'type' => 'date',
				'post'=>'dt_lim_annul'
		),
		'event1.datep' => array(
				'label' => "Date de livraison réele",
				'checked' => 1,
				'displayfield' => 'dt_liv_usi_reel',
				'sortfield'=>'event1.datep',
				'search' => array('event1.datep'),
				'type' => 'date',
				'post'=>'dt_liv_usi_reel'
		),
		'cmd.date_valid' => array(
				'label' => "Validation fiche analyse",
				'checked' => 1,
				'displayfield' => 'date_valid',
				'sortfield'=>'cmd.date_valid',
				'search' => array('cmd.date_valid'),
				'type' => 'date',
				'post'=>'date_valid'
		),
		'cmd.date_livraison' => array(
				'label' => "Date de livraison demandée",
				'checked' => 1,
				'displayfield' => 'dt_prev_liv_cli',
				'sortfield'=>'cmd.date_livraison',
				'search' => array('cmd.date_livraison'),
				'type' => 'date',
				'post'=>'dt_prev_liv_cli'
		),
		'event2.datep' => array(
				'label' => "Date de livraison réelle",
				'checked' => 1,
				'displayfield' => 'dt_liv_cli_reel',
				'sortfield'=>'event2.datep',
				'search' =>array('event2.datep'),
				'type' => 'date',
				'post'=>'dt_liv_cli_reel'
		),
		'cmd_ef.dt_invoice' => array(
				'label' => "Date de facturation",
				'checked' => 1,
				'displayfield' => 'dt_facture',
				'sortfield'=>'cmd_ef.dt_invoice',
				'search' => array('cmd_ef.dt_invoice'),
				'type' => 'date',
				'post'=>'dt_facture'
		),
		'event3.datep' => array(
				'label' => "Date de paiement",
				'checked' => 1,
				'displayfield' => 'dt_pay',
				'sortfield'=>'event3.datep',
				'search' => array('event3.datep'),
				'type' => 'date',
				'post'=>'dt_pay'
		),
		'delai_cash' => array(
				'label' => "Délai Cash",
				'checked' => 1,
				'displayfield' => 'delai_cash',
				'sortfield'=>'DATEDIFF(IFNULL(event3.datep,CURDATE()),event1.datep)',
				'search' => array('DATEDIFF(IFNULL(event3.datep,CURDATE()),event1.datep)'),
				'moredisplayvalue'=>' Jour(s)',
				'type' => 'num',
				'post'=>'delai_cash'
		),
		'retard_liv_usi' => array(
				'label' => "Retard liv. Usine",
				'checked' => 1,
				'displayfield' => 'retard_liv_usi',
				'sortfield'=>'DATEDIFF(event1.datep,cmdf.date_livraison)',
				'search' => array('DATEDIFF(event1.datep,cmdf.date_livraison)'),
				'moredisplayvalue'=>' Jour(s)',
				'type' => 'num',
				'post'=>'retard_liv_usi'
		),
		'retard_liv_cli' => array(
				'label' => "Retard liv. Client",
				'checked' => 1,
				'displayfield' => 'retard_liv_cli',
				'sortfield'=>'DATEDIFF(event2.datep,cmd.date_livraison)',
				'search' => array('DATEDIFF(event2.datep,cmd.date_livraison)'),
				'moredisplayvalue'=>' Jour(s)',
				'type' => 'num',
				'post'=>'retard_liv_cli'
		),

);
$_SESSION[$contextpage . '_arrayfields']= json_encode($arrayfields);

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
	$sortorder = "ASC";
if (empty($sortfield))
	$sortfield = "cmdf.date_livraison";



$filter = $_SESSION[$contextpage . '_filter'];
$filter = json_decode($filter,true);

// if(empty($filter)){
// 	if($search_run==1){
// 		$filter['search_run']=array('val'=>1,'sql'=>'(event3.datep IS NULL OR cmd_ef.dt_invoice IS NULL OR event2.datep IS NULL)');
// 	}else{
// 		$filter = array();
// 	}
// }

if(empty($user->rights->affaires->all)){
	$filter['fk_user_resp']=array('val'=>$user->id,'$sql'=>'user.rowid');
}


// View
$title = 'Suivi des affaires en cours';

llxHeader('', $title);

$nbtotalofrecords = 0;

if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$nbtotalofrecords = $object->fetch_All_folow_count($filter);
}

$resql = $object->fetch_All_folow($sortorder, $sortfield, $limit, $offset, $filter);
$moreforfilter = '';

// var_dump($resql);

$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

if ($resql != - 1) {
	$num = $resql;

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	// This also change content of $arrayfields
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);
	$nbfiltre = count($filter);
	if($nbfiltre>0) $filtretxt = ' (' . $nbfiltre . ')';
	$morehtmlcenter = $selectedfields . ' <a href="javascript:popfilter()" class="butAction">Filtres' . $filtretxt . '</a>';

	print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="searchFormList" id="searchFormList">' . "\n";
	print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $option, $sortfield, $sortorder, $morehtmlcenter, $num, $nbtotalofrecords, 'object_iron02@affaires', 0, '', '', $limit);
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
	print '</tr>' . "\n";

	foreach ( $object->lines as $line ) {
		print '<tr class="oddeven">';

		foreach ( $arrayfields as $key => $val ) {
			if (! empty($val['checked'])) {
				print '<td style="white-space:nowrap;">';
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

							default:
								print  $line->{$val['displayfield']};
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

$_SESSION[$contextpage . '_filter']= json_encode($filter);

$out = '<script type="text/javascript">' . "\n";
$out .= '  	function popfilter() {' . "\n";
$out .= '  		$divfilter = $(\'<div id="popfilter"><iframe width="100%" height="100%" frameborder="0" src="' . dol_buildpath ( '/affaires/volvo/form/reportfilter.php', 2 ) .'?contextpage=' . $contextpage .'"></iframe></div>\');' . "\n";
$out .= '' . "\n";
$out .= '  		$divfilter.dialog({' . "\n";
$out .= '  			modal:true' . "\n";
$out .= '  			,width:"90%"' . "\n";
$out .= '  			,height:$(window).height() - 150' . "\n";
$out .= '  			,close:function() {document.location.reload(true);}' . "\n";
$out .= '  		});' . "\n";
$out .= '' . "\n";
$out .= '  	}' . "\n";
$out .= '</script>';
print $out;

dol_fiche_end();
llxFooter();
$db->close();



