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
require_once '../../class/html.formaffairesproduct.class.php';

if (empty($user->rights->fournisseur->facture->creer) && empty($user->rights->affaires->volvo->update_cost)) {
	accessforbidden();
}

$form = new Form($db);
$object = new AffairesFactureFourn($db);
$formAffaireProduct = new FormAffairesProduct($db);

$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;

$action = GETPOST('action', 'alpha');

// Initialize technical object to manage context to save list fields
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'facturefournassist';

// Socid is fill when come from thirdparty tabs
$search_socid = GETPOST('search_socid', 'int');

$ref_fact_fourn = GETPOST('ref_fact_fourn', 'san_alpha');
$date_fact_fourn = dol_mktime(0, 0, 0, GETPOST('date_fact_fournmonth', 'int'), GETPOST('date_fact_fournday', 'int'), GETPOST('date_fact_fournyear', 'int'));

/*
 * Actions
 */
if ($action == 'createsupplierinvoice') {
	if (empty($ref_fact_fourn)) {
		setEventMessage('Veuillez saisir une référence de facture fournisseur','errors');
		$error++;
	}
	if (empty($date_fact_fourn)) {
		setEventMessage('Veuillez saisir une date de facture fournisseur','errors');
		$error++;
	}
	$orderlineid_array=array();
	$orderlineid_solde_array=array();
	$orderlineid_amount_array=array();
	foreach($_POST as $key=>$value) {
		if (strpos($key,'lineid_')===0) {
			$orderlineid_array[]=$value;
			$orderlineid_amount_array[$value]=price2num(GETPOST('lineidamount_'.$value));
			if (GETPOST('solde_lineid_'.$value)) {
				$orderlineid_solde_array[$value]=GETPOST('solde_lineid_'.$value);
			}
		}
	}
	if (count($orderlineid_array)==0){
		setEventMessage('Veuillez cocher des lignes pour la création de facture','errors');
		$error++;
	}

	if (empty($error)) {
		$result=$object->createFactureFourn($search_socid,$ref_fact_fourn,$date_fact_fourn,$orderlineid_array,$orderlineid_solde_array,$orderlineid_amount_array);
		if ($result<0) {
			setEventMessages(null, $object->errors,'errors');
		} else {
			//header('Location: ' . dol_buildpath('/fourn/facture/card.php',2) . '?facid='.$result);
			//exit;
		}
	}
}

$arrayfields = array(
		'ce.vin' => array(
				'label' => $langs->trans("vin"),
				'checked' => 1,
				'displayfield' => 'vin',
				'sortfield' => 'ce.vin',
				'searchinputname' => 'search_vin',
				'searchinputtype' => 'alpha'
		),
		'p.rowid' => array(
				'label' => $langs->trans("Produit"),
				'checked' => 1,
				'displayfield' => 'p_url',
				'sortfield' => 'p.ref',
				'searchinputname' => 'search_productref',
				'searchinputtype' => 'alpha'
		),
		'cdet.description' => array(
				'label' => $langs->trans("Commentaire"),
				'checked' => 1,
				'displayfield' => 'cdet.description',
				'sortfield' => 'cdet.description',
				'searchinputname' => 'search_linecomment',
				'searchinputtype' => 'alpha'
		),
		'cdet.qty' => array(
				'label' => $langs->trans("Qty"),
				'checked' => 1,
				'displayfield' => 'qty',
				'sortfield' => 'cdet.qty',
				'searchinputname' => 'search_lineqty',
				'displayeval' => 'price',
				'searchinputtype' => 'int'
		),
		'cdet.subprice' => array(
				'label' => $langs->trans("PU H.T."),
				'checked' => 1,
				'displayfield' => 'subprice',
				'sortfield' => 'cdet.subprice',
				'searchinputname' => 'search_linesubprice',
				'searchinputtype' => 'int',
				'moredisplayvalue' => $langs->getCurrencySymbol($conf->currency),
				'displayeval' => 'price'
		),
		'cdet.total_ht' => array(
				'label' => $langs->trans("Total H.T."),
				'checked' => 1,
				'displayfield' => 'total_ht',
				'sortfield' => 'cdet.subprice',
				'searchinputname' => 'search_linetotal_ht',
				'searchinputtype' => 'int',
				'search' => '<div id="totaltotal_ht"></div>',
				'moredisplayvalue' => $langs->getCurrencySymbol($conf->currency),
				'sumvar' => 1,
				'displayeval' => 'price'
		),
		'cdete.solde' => array(
				'label' => $langs->trans("Soldée"),
				'checked' => 1,
				'displayfield' => 'solde_checkbox',
				'sortfield' => 'cdete.solde',
				'searchinputname' => 'search_linesolde',
				'searchinputtype' => 'int'
		)
);

// Affect search value
foreach ( $arrayfields as $keyf => $dataf ) {
	${$dataf['searchinputname']} = GETPOST($dataf['searchinputname'], $dataf['searchinputtype']);
	if (${$dataf['searchinputname']} == - 1) {
		${$dataf['searchinputname']} = 0;
	}
}

// Do we click on purge search criteria ?
if (GETPOST ( 'button_removefilter_x', 'alpha' ) || GETPOST ( 'button_removefilter.x', 'alpha' ) || GETPOST ( 'button_removefilter', 'alpha' )) {
	foreach ( $arrayfields as $keyf => $dataf ) {
		${$dataf ['searchinputname']} = '';
		$search_socid = '';
	}
}

// Affect search input
$arrayfields['ce.vin']['search'] = '<input type="text" class="flat" name="search_vin" value="' . $search_vin . '" size="10">';
$arrayfields['p.rowid']['search'] = '<input type="text" class="flat" name="search_productref" value="' . $search_ctm . '" size="10">';
$arrayfields['cdet.description']['search'] = '<input type="text" class="flat" name="search_linecomment" value="' . $search_linecomment . '" size="10">';
$arrayfields['cdet.qty']['search'] = '<input type="text" class="flat" name="search_lineqty" value="' . $search_lineqty . '" size="4">';
$arrayfields['cdet.subprice']['search'] = '<input type="text" class="flat" name="search_linesubprice" value="' . $search_linesubprice . '" size="4">';

// Selection of new fields
include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

// Define filter SQL array for fetch_ all request and option for param in url
$option = '';
$filter = array();
$filter['cdete.solde'] = ' IS NULL ';
$filter['c.fk_statut IN'] = '1,2,3,4';
foreach ( $arrayfields as $keyf => $dataf ) {
	if (! empty(${$dataf['searchinputname']})) {
		$filter[$keyf] = ${$dataf['searchinputname']};
		$option .= '&' . $dataf['searchinputname'] . '=' . ${$dataf['searchinputname']};
	}
}
if (! empty($search_socid)) {
	$filter['c.fk_soc'] = $search_socid;
	$option .= '&search_socid=' . $search_socid;
}

if (! empty($limit)) {
	$option .= '&limit=' . $limit;
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
	$sortfield = "cdet.rowid";
}

$title = $langs->trans('Create de factures fournisseurs');

llxHeader('', $title);

$moreforfilter = '';

$moreforfilter .= '<div class="divsearchfield">';
$moreforfilter .= $langs->trans('Fournisseur') . ': ';
$moreforfilter .= $form->select_thirdparty_list($search_socid, 'search_socid', 's.fournisseur>0', $langs->trans('None'));
$moreforfilter .= '</div>';

// Count total nb of records
$nbtotalofrecords = 0;
$num = 0;
if (! empty($search_socid)) {
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
		$nbtotalofrecords = $object->fetch_supplierorderline($sortorder, $sortfield, 0, 0, $filter);
	}
	$num = $object->fetch_supplierorderline($sortorder, $sortfield, $limit, $offset, $filter);
}
if ($num >= 0) {

	print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="searchFormList" id="searchFormList">' . "\n";

	$morehtmlcenter = '<div>';
	$morehtmlcenter .= $langs->trans('Ref Facture fourn') . ':<input type="text" name="ref_fact_fourn" id="ref_fact_fourn" value="' . $ref_fact_fourn . '"/>';
	$morehtmlcenter .= '<br>' . $langs->trans('Date Facture fourn') . ':' . $form->select_date($date_fact_fourn, 'date_fact_fourn', 0, 0, 1, '', 1, 1, 1, 0, '', '', '');
	$morehtmlcenter .= '<br>' . '<input type="button" class="butAction" id="creerfact" value="Creer facture founisseur"/>';
	$morehtmlcenter .= '</div>';

	print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $option, $sortfield, $sortorder, $morehtmlcenter, $num, $nbtotalofrecords, 'affaires@affaires.png', 0, '', '', $limit);
	print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" id="action" value="">';

	if (! empty($sortfield)) {
		print '<input type="hidden" name="sortfield" value="' . $sortfield . '"/>';
	}
	if (! empty($sortorder)) {
		print '<input type="hidden" name="sortorder" value="' . $sortorder . '"/>';
	}
	if (! empty($page)) {
		print '<input type="hidden" name="page" value="' . $page . '"/>';
	}
	if (! empty($socid)) {
		print '<input type="hidden" name="socid" value="' . $socid . '"/>';
	}

	if ($moreforfilter) {
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
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
	print '<td class="liste_titre"></td>';

	// Filter button
	print '<td class="liste_titre" align="middle">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
	print "</tr>\n";

	// Fields title
	print '<tr class="liste_titre">';

	// Action
	print '<th>A facturer</th>';

	foreach ( $arrayfields as $key => $val ) {
		if (! empty($val['checked'])) {
			print_liste_field_titre($val['label'], $_SERVER["PHP_SELF"], $val['sortfield'], '', $option, '', $sortfield, $sortorder);
		}
	}

	// select column button
	print '<th>Montant facturé</th>';

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', $option, 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	print '</tr>' . "\n";
	foreach ( $object->lines as $line ) {

		// Affichage tableau des lead
		print '<tr class="oddeven">';

		print '<td align="center" style="white-space:nowrap">';
		print '<input type="checkbox" name="lineid_' . $line->id . '" id="lineid_' . $line->id . '" value="' . $line->id . '">';
		print '</td>';

		foreach ( $arrayfields as $key => $val ) {
			if (! empty($val['checked'])) {
				print '<td>';
				if (! empty($val['displayfield'])) {
					if (array_key_exists('displayeval', $val) && ! empty($val['displayeval'])) {
						print call_user_func($val['displayeval'], $line->{$val['displayfield']});
					} else {
						print $line->{$val['displayfield']};
					}

					if (array_key_exists('sumvar', $val) && ! empty($val['sumvar'])) {
						${$key} += $line->{$val['displayfield']};
					}
				} elseif (! empty($val['evaldisplayfield'])) {
					print price(call_user_func_array(array(
							$object,
							$val['evaldisplayfield']
					), array(
							$line->id
					)));

					if (array_key_exists('sumvar', $val) && ! empty($val['sumvar'])) {
						${$key} += call_user_func_array(array(
								$object,
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

		print '<td><input type="text" name="lineidamount_' . $line->id . '" id="lineidamount_' . $line->id . '" value="' . price($line->total_ht) . '"></td>';
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
			// var_dump($val);
			if (array_key_exists('sumvar', $val) && ! empty($val['sumvar'])) {
				?>
				$("#total<?php echo $val['displayfield']?>").append("<?php
				if (array_key_exists('displayeval', $val) && ! empty($val['displayeval'])) {
					print call_user_func($val['displayeval'], ${$key});
				} else {
					print ${$key};
				}
				echo ((array_key_exists('moredisplayvalue', $val) && ! empty($val['moredisplayvalue'])) ? $val['moredisplayvalue'] : '')?>");
	<?php

}
		}
	}
	?>
	});
				$('input:checkbox[name^="lineid_"]').change(
						function () {
							if ($(this).is(":checked"))
							{
								$('#solde_lineid_'+$(this).val()).prop('checked', true);
								$('#solde_lineid_'+$(this).val()).attr('checked', 'checked');
								if ($('#swith_'+$(this).val()).hasClass('fa-toggle-off')) {
									$('#swith_'+$(this).val()).switchClass('fa-toggle-off','fa-toggle-on');
									$('#swith_'+$(this).val()).prop('style','color:#227722;font-size:2em');
								}

							} else {
								$('#solde_lineid_'+$(this).val()).prop('checked', false);
								$('#solde_lineid_'+$(this).val()).removeAttr('checked');
								if ($('#swith_'+$(this).val()).hasClass('fa-toggle-on')) {
									$('#swith_'+$(this).val()).switchClass('fa-toggle-on','fa-toggle-off');
									$('#swith_'+$(this).val()).prop('style','color:#999;font-size:2em');
								}
							}
						}
					);
				$('span[id^="swith_"').click(
						function () {
							if ($('#solde_lineid_'+$(this).data("src")).is(":checked"))
							{
								$('#solde_lineid_'+$(this).data("src")).prop('checked', false);
								$('#solde_lineid_'+$(this).data("src")).removeAttr('checked');
								$(this).toggleClass('fa-toggle-off fa-toggle-on');
								$(this).prop('style','color:#999;font-size:2em');
							} else {
								$('#solde_lineid_'+$(this).data("src")).prop('checked', true);
								$('#solde_lineid_'+$(this).data("src")).attr('checked', 'checked');
								$(this).toggleClass('fa-toggle-on fa-toggle-off');
								$(this).prop('style','color:#227722;font-size:2em');
								$('#lineid_'+$(this).data("src")).prop('checked', true);
								$('#lineid_'+$(this).data("src")).attr('checked', 'checked');
							}
						}
					);
				$('#creerfact').click(function() {$('#action').val('createsupplierinvoice');$('#searchFormList').submit()});
	</script>
<?php
} else {
	setEventMessages(null, $object->errors, 'errors');
}

dol_fiche_end();
llxFooter();
$db->close();
