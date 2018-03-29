<?php
/* Volvo
 * Copyright (C) 2014-2015 Florian HENRY <florian.henry@open-concept.pro>
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

require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
dol_include_once('/affaires/class/affaires.class.php');
dol_include_once('/affaires/class/html.formaffairesproduct.class.php');
dol_include_once('/affaires/volvo/class/commandevolvo.class.php');

$langs->load('orders');
$langs->load('companies');
$langs->load('products');
$langs->load('volvo@volvo');

if (empty($user->rights->fournisseur->commande->creer) && empty($user->rights->affaires->volvo->update_cost)) {
	accessforbidden();
}

$orderid = GETPOST('orderid', 'int');
$action = GETPOST('action', 'alpha');
$idlines = GETPOST('idlines', 'alpha');

$error = 0;

$order = new Commande($db);
$product = new Product($db);
$formAffaireProduct = new FormAffairesProduct($db);

$result = $order->fetch($orderid);
if ($result < 0) {
	setEventMessages($order->error, null, 'errors');
}

$linedisplay_array = array();
if (is_array($order->lines) && count($order->lines)) {
	foreach ( $order->lines as $line ) {

		if ($line->product_type == 9 && $line->qty == 1) {

			if (count($linedisplay_array) > 0) {
				// Test if previous element is also an Sub Total
				$lastelement = end($linedisplay_array);
				if ($lastelement->product_type == 9) {
					// in this case remove last element because no need to display empty subtotal lines
					array_pop($linedisplay_array);
				}
			}
			$linedisplay_array[$line->id] = $line;
		} elseif ($line->product_type != 9) {
			if (! empty($line->fk_product)) {
				$prod = new Product($db);
				$result = $prod->fetch($line->fk_product);
				if ($result < 0) {
					setEventMessages(null, array(
							'Error Fetch Product'
					), 'errors');
				} elseif (! empty($prod->array_options['options_supplierorderable'])) {
					// Do not include product that cannot be updated
					$linedisplay_array[$line->id] = $line;
				}
			}
		}
	}
	$lastelement = end($linedisplay_array);
	if ($lastelement->product_type == 9) {
		// in this case remove last element because no need to display empty subtotal lines
		array_pop($linedisplay_array);
	}
}

top_htmlhead('', '');

if ($action == 'createsupplerorder') {
	$price_qty_array = array();
	$errors = array();
	$lineupdate_array = explode(',', $idlines);
	foreach ( $order->lines as $line ) {

		if (in_array($line->id, $lineupdate_array)) {
			$priceid = GETPOST('fournprice_' . $line->id);
			if (! empty($priceid)) {

				$result = $line->fetch_optionals($line->id);
				if ($result < 0) {
					$error ++;
					$errors = array_merge($errors, $line->errors);
				}
				if (! empty($line->array_options['options_fk_supplierorderlineid'])) {
					$ordersupplierlineid = $line->array_options['options_fk_supplierorderlineid'];
					$cmdsupdet = new CommandeFournisseurLigne($db);
					$result = $cmdsupdet->fetch($line->array_options['options_fk_supplierorderlineid']);
					if ($result < 0) {
						setEventMessages(null, array(
								'Error Fetch line fourn'
						), 'errors');
					}
					$suplierorderid=$cmdsupdet->fk_commande;

				} else {
					$suplierorderid=0;
					$ordersupplierlineid = 0;
				}

				$price_qty_array[$priceid] = array(
						'qty' => $line->qty,
						'desc' => $line->desc,
						'px' => $line->pa_ht,
						'lineid' => $line->id,
						'supplierorderlineid' => $ordersupplierlineid,
						'suplierorderid'=>$suplierorderid
				);
			}
		}
	}
	if (count($price_qty_array) > 0) {
		$cmdv = new CommandeVolvo($db);
		$result = $cmdv->createSupplierOrder($user, $price_qty_array, $order->id);
		if ($result < 0) {
			$error ++;
			$errors = array_merge($errors, $cmdv->errors);
		}
	}

	if (! empty($error)) {
		setEventMessages(null, $errors, 'errors');
	} /*else {
	   top_htmlhead('', '');
	   print '<script type="text/javascript">' . "\n";
	   print '	$(document).ready(function () {' . "\n";
	   print '	window.parent.$(\'#popSupplierOrder\').dialog(\'close\');' . "\n";
	   print '	window.parent.$(\'#popSupplierOrder\').remove();' . "\n";
	   print '});' . "\n";
	   print '</script>' . "\n";
	   llxFooter();
	   exit;
	 }*/
}

print '<form name="createsupplerorder" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="orderid" value="' . $orderid . '">';
print '<input type="hidden" name="action" value="createsupplerorder">';

print '<table class="border" width="100%">';
print '<tr class="liste_titre">';
print '<th align="center">' . $langs->trans('Description') . '</th>';
print '<th align="center">' . $langs->trans('PriceUHT') . '</th>';
print '<th align="center">' . $langs->trans('Qty') . '</th>';
print '<th align="center">' . $langs->trans('SupplierPrice') . '</th>';
print '<th align="center">' . $langs->trans('SupplierOrder') . '</th>';
print '</tr>';

if (is_array($order->lines) && count($order->lines)) {
	$lineupdate_array = array();
	foreach ( $order->lines as $line ) {
		$line->fetch_optionals();

		if ($line->product_type != 9) {
			$cmdsup = new CommandeFournisseur($db);
			$cmdsupdet = new CommandeFournisseurLigne($db);
			$url_op = '';
			if (! empty($line->array_options['options_fk_supplierorderlineid'])) {
				$result = $cmdsupdet->fetch($line->array_options['options_fk_supplierorderlineid']);
				if ($result < 0) {
					setEventMessages(null, array(
							'Error Fetch line fourn'
					), 'errors');
				}
				$result = $cmdsup->fetch($cmdsupdet->fk_commande);
				if ($result < 0) {
					setEventMessages(null, array(
							'Error Fetch commande fourn:'.$cmdsup->error
					), 'errors');
				}
				$url_op = $cmdsup->getNomUrl();
			}

			if (array_key_exists($line->id, $linedisplay_array)) {
				$lineupdate_array[] = $line->id;

				$line->fetch_optionals($line->id, $extralabelslines);

				print '<tr class="oddeven">';

				if (! empty($line->fk_product)) {
					$productdesc = $line->product_ref . ' - ' . $line->product_label;
				} else {
					$productdesc = $line->label . ' ' . $line->description;
				}

				print '<td align="center">' . $productdesc . '</td>';
				print '<td align="center">' . price($line->total_ht) . '</td>';
				print '<td align="center">' . $line->qty . '</td>';
				print '<td align="center">' . (empty($url_op) ? $formAffaireProduct->selectFournPrice('fournprice_' . $line->id, '', $line->fk_product, 1, 1) : '') . '</td>';
				print '<td align="center">' . $url_op . '</td>';
				print '</tr>';
			}
		} elseif ($line->qty == 1 && array_key_exists($line->id, $linedisplay_array)) {
			print '<tr style="background-color:#adadcf;">';
			print '<td style="font-weight:bold;   font-style: italic;" colspan="6">' . $line->description . '</td>';
			print '</tr>';
		}
	}
}
print '</table>';
print '<div class="tabsAction">';
print '<input type="hidden" name="idlines" value="' . implode(',', $lineupdate_array) . '"/>';
print '<input type="submit" align="center" class="button" value="' . $langs->trans('Save') . '" name="save" id="save"/>';
print '</div>';
print '</form>';

llxFooter();
$db->close();