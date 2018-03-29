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
$res = @include '../../../main.inc.php'; // For root directory
if (! $res) {
	$res = @include '../../../../main.inc.php'; // For "custom" directory
}
if (! $res) {
	die("Include of main fails");
}

require_once '../../class/affairesfacturefourn.class.php';

if (empty($user->rights->fournisseur->facture->creer) && empty($user->rights->affaires->volvo->update_cost)) {
	accessforbidden();
}

$form = new Form($db);
$object = new AffairesFactureFourn($db);

$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;

$action = GETPOST('action', 'alpha');

// Initialize technical object to manage context to save list fields
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'affaireslist';

// Socid is fill when come from thirdparty tabs
$socid = GETPOST('socid', 'int');

/*
 * Actions
 */
if ($action == 'createsupplierinvoice') {
}

$arrayfields = array(
		't.ref' => array(
				'label' => $langs->trans("affnum"),
				'checked' => 1,
				'search' => '<input class="flat" type="text" size="6" name="search_ref" value="' . $search_ref . '">',
				'displayfield' => 'ref_url',
				'sortfield' => 't.ref',
				'searchinputname' => 'search_ref',
				'searchinputtype' => 'alpha'
		)
);

//Clean search value
foreach ( $arrayfields as $keyf => $dataf ) {
	${$dataf['searchinputname']} = GETPOST($dataf['searchinputname'], $dataf['searchinputtype']);
	if (${$dataf['searchinputname']} == - 1) {
		${$dataf['searchinputname']} = 0;
	}
}

// Do we click on purge search criteria ?
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	${$dataf['searchinputname']} = '';
}

// Selection of new fields
include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

$option = '';
$filter = array();
foreach ( $arrayfields as $keyf => $dataf ) {
	if (! empty(${$dataf['searchinputname']})) {
		$filter[$keyf] = ${$dataf['searchinputname']};
		$option .= '&' . $dataf['searchinputname'] . '=' . ${$dataf['searchinputname']};
	}
}

// Set page option
if (empty($page) || $page == - 1) {
	$page = 0;
}

$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (empty($sortorder)) {
	$sortorder = "DESC";
}
if (empty($sortfield)) {
	$sortfield = "t.datec";
}

$title = $langs->trans('Create de factures fournisseurs');

llxHeader('', $title);

$moreforfilter = '';

$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.=$langs->trans('Fournisseur').': ';
$moreforfilter.= $form->select_thirdparty_list($object->fk_soc, 'fk_soc', 's.fournisseur>0', 1);
$moreforfilter.='</div>';

// Count total nb of records
$nbtotalofrecords = 0;

if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$nbtotalofrecords = $object->fetch_supplierorderline($sortorder, $sortfield, 0, 0, $filter);
}
$resql = $object->fetch_supplierorderline($sortorder, $sortfield, $limit, $offset, $filter);

if ($resql != - 1) {
	$num = $resql;

	print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $option, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'affaires@affaires.png', 0, '', '', $limit);

	print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="searchFormList" id="searchFormList">' . "\n";
	print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
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

	foreach ( $object->lines as $line ) {

		$list = '';
		if ($user->rights->affaires->write) {
			$list = '<select class="flat" id="action_' . $line->id . '" name="action_' . $line->id . '" style="width: 75px;">';
			$list .= '<option value="1" selected>Editer</option>';
			foreach ( $objectdet->status as $keystatus => $labelstatus ) {
				if ($line->fk_status != $keystatus) {
					if (($keystatus == 7 || $keystatus == 11 || $keystatus == 5) && ! empty($line->fk_commande)) {
						// $list .= '<option value="'.$keystatus.'">'.$labelstatus.'</option>';
					} else {
						$list .= '<option value="' . $keystatus . '">' . $labelstatus . '</option>';
					}
				}
			}
			$list .= '</select>';
			$list .= '<a href="javascript:do_action(' . $line->id . ')" style="color:black"><i class="fa fa-check-square-o paddingright"></i></a>';
		}

		// Affichage tableau des lead
		print '<tr class="oddeven">';

		print '<td align="center" style="white-space:nowrap">' . $list . '</td>';

		foreach ( $arrayfields as $key => $val ) {
			if (! empty($val['checked'])) {
				print '<td>';
				if (! empty($val['displayfield'])) {
					print $line->{$val['displayfield']};

					if (array_key_exists('sumvar', $val) && ! empty($val['sumvar'])) {
						${$key} += $line->{$val['displayfield']};
					}
				} elseif (! empty($val['evaldisplayfield'])) {
					print price(call_user_func_array(array(
							$objectdet,
							$val['evaldisplayfield']
					), array(
							$line->id
					)));

					if (array_key_exists('sumvar', $val) && ! empty($val['sumvar'])) {
						${$key} += call_user_func_array(array(
								$objectdet,
								$val['evaldisplayfield']
						), array(
								$line->id
						));
					}
				}
				if (array_key_exists('moredisplayvalue', $val) && ! empty($val['moredisplayvalue'])) {
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
} else {
	setEventMessages(null, $objectdet->errors, 'errors');
}

dol_fiche_end();
llxFooter();
$db->close();
