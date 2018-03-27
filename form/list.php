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
 * \file affaire/form/list.php
 * \ingroup affaire
 * \brief list of affaire
 */
$res = @include '../../main.inc.php'; // For root directory
if (! $res)
	$res = @include '../../../main.inc.php'; // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once '../class/affaires.class.php';
require_once '../lib/affaires.lib.php';
require_once '../class/html.formaffaires.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

// Security check
if (! $user->rights->affaires->read)
	accessforbidden();

$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$do_action = GETPOST('do_action', 'int');

// Initialize technical object to manage context to save list fields
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'affaireslist';

// Socid is fill when come from thirdparty tabs
$socid = GETPOST('socid', 'int');

// view type is special predefined filter
$viewtype = GETPOST('viewtype', 'alpha');

// Search criteria
$search_ref = GETPOST("search_ref");
$search_soc = GETPOST("search_soc");
$search_ctm = GETPOST("search_ctm");
$search_status = GETPOST('search_status');
if ($search_status == - 1) {
	$search_status = 0;
}
$search_type = GETPOST('search_type');
if ($search_type == - 1) {
	$search_type = 0;
}
$search_commercial = GETPOST('search_commercial');
if ($search_commercial == - 1) {
	$search_commercial = '';
}
$search_year = GETPOST('search_year', 'int');
if ($search_year == - 1) {
	$search_year = '';
}
$search_gamme = GETPOST('search_gamme', 'int');
if ($search_gamme == - 1) {
	$search_gamme = '';
}
$search_genre = GETPOST('search_genre', 'int');
if ($search_genre == - 1) {
	$search_genre = '';
}
$search_silhouette = GETPOST('search_silhouette', 'int');
if ($search_silhouette == - 1) {
	$search_silhouette = '';
}
$search_carrosserie = GETPOST('search_carrosserie', 'int');
if ($search_carrosserie == - 1) {
	$search_carrosserie = '';
}
$search_spec = GETPOST('search_spec', 'san_alpha');
if ($search_spec == - 1) {
	$search_spec = '';
}
$search_cv_type = GETPOST('search_cv_type', 'int');
if ($search_cv_type == - 1) {
	$search_cv_type = '';
}

$link_element = GETPOST("link_element");
if (! empty($link_element)) {
	$action = 'link_element';
}

// Do we click on purge search criteria ?
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$search_ref = '';
	$search_commercial = '';
	$search_soc = '';
	$search_ctm = '';
	$search_status = '';
	$search_type = '';
	$search_year = '';
	$search_gamme = '';
	$search_genre = '';
	$search_silhouette = '';
	$search_carrosserie = '';
	$search_spec = '';
	$search_cv_type = '';
}

$search_commercial_disabled = 0;
if (empty($user->rights->affaires->all)) {
	$search_commercial = $user->id;
	$selected_commercial = $user->id;
}

$object = new Affaires($db);
$objectdet = new Affaires_det($db);
$form = new Form($db);
$formother = new FormOther($db);
$formAffaires = new FormAffaires($db);

$arrayfields = array(
		't.ref' => array(
				'label' => $langs->trans("affnum"),
				'checked' => 1,
				'search' => '<input class="flat" type="text" size="6" name="search_ref" value="' . $search_ref . '">',
				'displayfield' => 'ref_url',
				'sortfield'=>'t.ref'
		),
		't.fk_c_type' => array(
				'label' => $langs->trans("cv"),
				'checked' => 1,
				'search' => $form->selectarray('search_cv_type', $object->type, $object->fk_c_type, 1),
				'displayfield' => 'cv_type_label',
				'sortfield'=>'cv.label'
		),
		't.fk_user_resp' => array(
				'label' => $langs->trans("userresp"),
				'checked' => 1,
				'search' => $formAffaires->select_salesmans(empty($search_commercial) ? $user->id : $search_commercial, 'search_commercial', 'Commerciaux', 1),
				'displayfield' => 'usrname',
				'sortfield'=>'usr.lastname'
		),
		'soc.nom' => array(
				'label' => $langs->trans("client"),
				'checked' => 1,
				'search' => '<input type="text" class="flat" name="search_soc" value="' . $search_soc . '" size="20">',
				'displayfield' => 'soc_url',
				'sortfield'=>'soc.nom'
		),
		'ctm.nom' => array(
				'label' => $langs->trans("ctm"),
				'checked' => 1,
				'search' => '<input type="text" class="flat" name="search_ctm" value="' . $search_ctm . '" size="20">',
				'displayfield' => 'ctm_url',
				'sortfield'=>'ctm.nom'
		),
		't.year' => array(
				'label' => $langs->trans("Year"),
				'checked' => 1,
				'search' => $formother->selectyear($search_year ? $search_year : - 1, 'search_year', 1, 20, 5),
				'displayfield' => 'year',
				'sortfield'=>'t.year'
		),
		'det.fk_genre' => array(
				'label' => $langs->trans("genre"),
				'checked' => 1,
				'search' => $formAffaires->select_affairesdet_fromdict($search_genre, 'search_genre', 1, 'genre_dict'),
				'displayfield' => 'genre_label',
				'sortfield'=>'genre.genre'
		),
		'det.fk_gamme' => array(
				'label' => $langs->trans("gamme"),
				'checked' => 1,
				'search' => $formAffaires->select_affairesdet_fromdict($search_gamme, 'search_gamme', 1, 'gamme_dict'),
				'displayfield' => 'gamme_label',
				'sortfield'=>'gamme.gamme'
		),
		'det.fk_silhouette' => array(
				'label' => $langs->trans("Silhouette"),
				'checked' => 1,
				'search' => $formAffaires->select_affairesdet_fromdict($search_silhouette, 'search_silhouette', 1, 'silhouette_dict'),
				'displayfield' => 'silhouette_label',
				'sortfield'=>'silhouette.silhouette'

		),
		'det.fk_carrosserie' => array(
				'label' => $langs->trans("Carroserie"),
				'checked' => 1,
				'search' => $formAffaires->select_affairesdet_fromdict($search_carrosserie, 'search_carrosserie', 1, 'carrosserie_dict'),
				'displayfield' => 'carrosserie_label',
				'sortfield'=>'carrosserie.carrosserie'
		),
		'det.fk_status' => array(
				'label' => $langs->trans("Status"),
				'checked' => 1,
				'search' => $formAffaires->select_affairesdet_fromdict($search_status, 'search_status', 1, 'status'),
				'displayfield' => 'status_label',
				'sortfield'=>'status.label'
		),
		'det.spec' => array(
				'label' => $langs->trans("Spec"),
				'checked' => 1,
				'search' => '<input type="text" name="spec" id="search_spec" value="' . $search_spec . '"/>',
				'displayfield' => 'spec',
				'sortfield'=>'det.spec'
		),
		'amountorder' => array(
				'label' => $langs->trans("Montant des commandes"),
				'checked' => 1,
				'search' => '<div id="totalamountorder"></div>',
				'displayfield' => '',
				'sortfield'=>'',
				'evaldisplayfield' => 'getAmountOrder',
				'moredisplayvalue'=>$langs->getCurrencySymbol($conf->currency),
				'sumvar'=>1
		),
		'margedate' => array(
				'label' => $langs->trans("Marge a date"),
				'checked' => 1,
				'search' => '<div id="totalmargedate"></div>',
				'displayfield' => '',
				'sortfield'=>'',
				'evaldisplayfield' => 'getMarginDate',
				'moredisplayvalue'=>$langs->getCurrencySymbol($conf->currency),
				'sumvar'=>1
		),
		'margedatereel' => array(
				'label' => $langs->trans("Marge a date réelle"),
				'checked' => 1,
				'search' => '<div id="totalmargedatereel"></div>',
				'displayfield' => '',
				'sortfield'=>'',
				'evaldisplayfield' => 'getMarginReelDate',
				'moredisplayvalue'=>$langs->getCurrencySymbol($conf->currency),
				'sumvar'=>1
		),

);
// Selection of new fields
include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

$filter = array();
if (! empty($search_ref)) {
	$filter['t.ref'] = $search_ref;
	$option .= '&search_ref=' . $search_ref;
}
if (! empty($search_cv_typef)) {
	$filter['t.fk_c_type'] = $search_cv_typef;
	$option .= '&search_cv_type=' . $search_cv_typef;
}
if (! empty($search_commercial)) {
	$filter['t.fk_user_resp'] = $search_commercial;
	$option .= '&search_commercial=' . $search_commercial;
}
if (! empty($search_soc)) {
	$filter['soc.nom'] = $search_soc;
	$option .= '&search_soc=' . $search_soc;
}
if (! empty($search_ctm)) {
	$filter['ctm.nom'] = $search_ctm;
	$option .= '&search_ctm=' . $search_ctm;
}
if (! empty($search_status)) {
	$filter['det.fk_status'] = $search_status;
	$option .= '&search_status=' . $search_status;
}
if (! empty($search_type)) {
	$filter['t.fk_c_type'] = $search_type;
	$option .= '&search_type=' . $search_type;
}
if (! empty($search_gamme)) {
	$filter['det.fk_gamme'] = $search_gamme;
	$option .= '&search_gamme=' . $search_gamme;
}
if (! empty($search_genre)) {
	$filter['det.fk_genre'] = $search_genre;
	$option .= '&search_genre=' . $search_genre;
}
if (! empty($search_silhouette)) {
	$filter['det.fk_silhouette'] = $search_silhouette;
	$option .= '&search_silhouette=' . $search_silhouette;
}
if (! empty($search_carrosserie)) {
	$filter['det.fk_carrosserie'] = $search_carrosserie;
	$option .= '&search_carrosserie=' . $search_carrosserie;
}
if (! empty($search_spec)) {
	$filter['det.spec'] = $search_spec;
	$option .= '&search_spec=' . $search_spec;
}
if (! empty($search_year)) {
	$filter['t.year'] = $search_year;
	$option .= '&search_year=' . $search_year;
}

if (! empty($viewtype)) {
	if ($viewtype == 'current') {
		$filter['det.fk_status !IN'] = '6,7,11';
	}
	if ($viewtype == 'lost') {
		$filter['det.fk_status !IN'] = '6,5,11';
	}
	if ($viewtype == 'cancel') {
		$filter['det.fk_status !IN'] = '6,5,7';
	}
	if ($viewtype == 'won') {
		$filter['det.fk_status !IN'] = '5,7,11';
	}
	if ($viewtype == 'hot') {
		$filter['det.fk_status !IN'] = '6,7,11';
	}
	if ($viewtype == 'my') {
		$filter['t.fk_user_resp'] = $user->id;
	}
	if ($viewtype == 'mycurrent') {
		$filter['t.fk_user_resp'] = $user->id;
		$filter['det.fk_status !IN'] = '6,7,11';
	}
	if ($viewtype == 'mylost') {
		$filter['t.fk_user_resp'] = $user->id;
		$filter['det.fk_status !IN'] = '6,5,11';
	}
	if ($viewtype == 'mycancel') {
		$filter['t.fk_user_resp'] = $user->id;
		$filter['det.fk_status !IN'] = '6,5,7';
	}
	if ($viewtype == 'mywon') {
		$filter['t.fk_user_resp'] = $user->id;
		$filter['det.fk_status !IN'] = '5,7,11';
	}
	if ($viewtype == 'late') {
		$filter['det.fk_status !IN'] = '6,7,11';
	}
	if ($viewtype == 'myhot') {
		$filter['t.fk_user_resp'] = $user->id;
		$filter['det.fk_status !IN'] = '6,7,11';
	}
	$option .= '&viewtype=' . $viewtype;
}

if (empty($page) || $page == - 1) {
	$page = 0;
}

$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (empty($sortorder))
	$sortorder = "DESC";
if (empty($sortfield))
	$sortfield = "t.datec";

$title = $langs->trans('AffairesList');

llxHeader('', $title);

if (! empty($socid)) {
	require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
	$soc = new Societe($db);
	$soc->fetch($socid);
	$head = societe_prepare_head($soc);

	dol_fiche_head($head, 'tabAffaire', $langs->trans("Module103111Name"), 0, dol_buildpath('/affaires/img/object_affaires.png', 1), 1);
}

// Count total nb of records
$nbtotalofrecords = 0;

if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$nbtotalofrecords = $objectdet->fetch_all($sortorder, $sortfield, 0, 0, $filter);
}
$resql = $objectdet->fetch_all($sortorder, $sortfield, $limit, $offset, $filter);

$moreforfilter = '';
if ($resql != - 1) {
	$num = $resql;

	print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $option, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'affaires@affaires.png', 0, '', '', $limit);

	print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="searchFormList" id="searchFormList">' . "\n";
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
	if (! empty($limit)) {
		print '<input type="hidden" name="limit" value="' . $limit . '"/>';
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
	print '<tr class="liste_titre_filter">';
	// Action
	print '<td class="liste_titre"></td>';

	foreach ( $arrayfields as $key => $val ) {
		if (! empty($val['checked'])) {
			print '<td class="liste_titre">';
			print $arrayfields[$key]['search'];
			print '</td>';
		}
	}

	// Filter button
	print '<td class="liste_titre" align="middle">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
	print "</tr>\n";

	// Fields title
	print '<tr class="liste_titre">';

	// Action
	print '<th></th>';

	foreach ( $arrayfields as $key => $val ) {
		if (! empty($val['checked'])) {
			print_liste_field_titre($val['label'], $_SERVER["PHP_SELF"], $val['sortfield'], '', $option, '', $sortfield, $sortorder);
		}
	}

	// Filter button
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', $option, 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	print '</tr>' . "\n";

	$totalamountguess = 0;
	$totalamountreal = 0;

	foreach ( $objectdet->lines as $line ) {

		$list = '';
		if ($user->rights->affaires->write) {
			$list = '<select class="flat" id="action_' . $line->id . '" name="action_' . $line->id . '" style="width: 75px;">';
			$list .= '<option value="1" selected>Editer</option>';
			foreach($objectdet->status as $keystatus=>$labelstatus) {
				if ($line->fk_status != $keystatus) {
					if (($keystatus==7 || $keystatus==11) && !empty($line->fk_commande)) {
						//$list .= '<option value="'.$keystatus.'">'.$labelstatus.'</option>';
					} else {
						$list .= '<option value="'.$keystatus.'">'.$labelstatus.'</option>';
					}
				}
			}
			$list .= '</select>';
			$list.= '<a href="javascript:do_action('.$line->id.')" style="color:black"><i class="fa fa-check-square-o paddingright"></i></a>';
		}

		// Affichage tableau des lead
		print '<tr class="oddeven">';

		print '<td align="center" style="white-space:nowrap">' . $list . '</td>';

		foreach ( $arrayfields as $key => $val ) {
			if (! empty($val['checked'])) {
				print '<td>';
				if (!empty($val['displayfield'])) {
					print  $line->{$val['displayfield']};

					if (array_key_exists('sumvar', $val) && !empty($val['sumvar'])) {
						${$key}+=$line->{$val['displayfield']};
					}
				} elseif (!empty($val['evaldisplayfield'])) {
					print price(call_user_func_array(array($objectdet, $val['evaldisplayfield']),array($line->id)));

					if (array_key_exists('sumvar', $val) && !empty($val['sumvar'])) {
						${$key}+=call_user_func_array(array($objectdet, $val['evaldisplayfield']),array($line->id));
					}
				}
				if (array_key_exists('moredisplayvalue', $val) && !empty($val['moredisplayvalue'])) {
					print $val['moredisplayvalue'];
				}
				print '</td>';

			}
		}

		print '<td></td>';

		print "</tr>\n";
	}

	print "</table>";
	print '</div>';

	print '</form>';
	?>
<script type="text/javascript">

	$(document).ready(function() {
	<?php
	foreach ( $arrayfields as $key => $val ) {
		if (! empty($val['checked'])) {
			//var_dump($val);
			if (array_key_exists('sumvar', $val) && !empty($val['sumvar'])) {
		?>
				$("#total<?php echo $key?>").append("<?php echo price(${$key}) . ((array_key_exists('moredisplayvalue', $val) && !empty($val['moredisplayvalue']))?$val['moredisplayvalue']:'') ?>");
	<?php	}
		}
	}?>
	});

	function do_action(vehid) {
		val=$('#action_' +vehid).val();
		if (val==1) {
			$div = $('<div id="popCreateAffaireDet"><iframe width="100%" height="100%" frameborder="0" src="<?php echo dol_buildpath('/affaires/form/createdet.php?affaireid='.$object->id,1) ?>&vehid='+vehid+'"></iframe></div>');
			$div.dialog({
				modal:true
				,width:"90%"
						,height:$(window).height() - 200
						,close:function() {$("input[name='button_search']" ).click();}
			});
		} else {
			$.ajax({
		 		method: "POST",
		 		url: "../ajax/interface.php",
		 		data: {
		 			vehid: vehid,
		 			new_statut: val,
		 			action:'updatestatus'
		 		},
		 		success: function(data){
		 			if (data == 1){
		 				$("input[name='button_search']" ).click();
		 			} else {
		 				alert( "erreur: " + data );
		 			}
		 		},
		 	})
		}
	}
</script>
	<?php
} else {
	setEventMessages(null, $objectdet->errors, 'errors');
}

dol_fiche_end();
llxFooter();
$db->close();
