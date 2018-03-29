<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
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
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $seller, $buyer
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 */


$usemargins=0;
if (! empty($conf->margin->enabled) && ! empty($object->element) && in_array($object->element,array('facture','propal','commande'))) $usemargins=1;

global $forceall, $senderissupplier, $inputalsopricewithtax;
if (empty($dateSelector)) $dateSelector=0;
if (empty($forceall)) $forceall=0;
if (empty($senderissupplier)) $senderissupplier=0;
if (empty($inputalsopricewithtax)) $inputalsopricewithtax=0;


// Define colspan for button Add
$colspan = 3;	// Col total ht + col edit + col delete
if (! empty($inputalsopricewithtax)) $colspan++;	// We add 1 if col total ttc
if (in_array($object->element,array('propal','supplier_proposal','facture','invoice','commande','order','order_supplier','invoice_supplier'))) $colspan++;	// With this, there is a column move button


print '<!-- BEGIN PHP TEMPLATE objectline_edit.tpl.php for order-->';


if (empty($object) || ! is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}


$usemargins=0;
if (! empty($conf->margin->enabled) && ! empty($object->element) && in_array($object->element,array('facture','propal','commande')))
{
	$usemargins=1;
}

if (! isset($dateSelector)) global $dateSelector;	// Take global var only if not already defined into function calling (for example formAddObjectLine)
global $forceall, $forcetoshowtitlelines, $senderissupplier, $inputalsopricewithtax;

if (! isset($dateSelector)) $dateSelector=1;    // For backward compatibility
elseif (empty($dateSelector)) $dateSelector=0;
if (empty($forceall)) $forceall=0;
if (empty($senderissupplier)) $senderissupplier=0;
if (empty($inputalsopricewithtax)) $inputalsopricewithtax=0;


// Define colspan for button Add
$colspan = 8;

// Lines for extrafield
$objectline = new OrderLine($this->db);

print '<!-- BEGIN PHP TEMPLATE objectline_create.tpl.php for order-->';

print '<tr class="liste_titre' . (($nolinesbefore || $object->element == 'contrat') ? '' : ' liste_titre_add_') . 'nodrag nodrop">';
print '<td class="linecoldescription minwidth500imp">';
print '<div id="add"></div><span class="hideonsmartphone">' . $langs->trans ( 'AddNewLine' ) . '</span>';
print '</td>';
print '<td class="linecoluht" align="right"><span id="title_up_ht">' . $langs->trans ( 'PriceUHT' ) . '</span></td>';
if (! empty ( $inputalsopricewithtax )) {
	print '<td class="linecoluttc" align="right"><span id="title_up_ttc">' . $langs->trans ( 'PriceUTTC' ) . '</span></td>';
}
print '<td class="linecolqty" align="right">' . $langs->trans ( 'Qty' ) . '</td>';
if ($conf->global->PRODUCT_USE_UNITS) {
	print '<td class="linecoluseunit" align="left">';
	print '<span id="title_units">';
	print $langs->trans ( 'Unit' );
	print '</span></td>';
}

if (! empty ( $usemargins )) {
	if (! empty ( $user->rights->margins->creer )) {
		print '<td align="right" class="margininfos linecolmargin1">';
	} else
		$colspan ++;
		if ($conf->global->MARGIN_TYPE == "1")
			print $langs->trans ( 'BuyingPrice' );
		else
			print $langs->trans ( 'CostPrice' );
		print '</td>';
}
print '<td class="linecoledit" colspan="' . $colspan . '">&nbsp;</td>';
print '</tr>';

print '<tr class="pair nodrag nodrop nohoverpair' . ($nolinesbefore || $object->element=='contrat')?'':' liste_titre_create' .'">';
$coldisplay=0;
print '<td class="nobottom linecoldescription minwidth500imp">';
print '<a href="' . DOL_URL_ROOT.'/product/card.php?id='.$line->fk_product . '">';
if ($line->product_type==1) echo img_object($langs->trans('ShowService'),'service');
else print img_object($langs->trans('ShowProduct'),'product');
print ' '.$line->ref;
print '</a>' .  ' - '.nl2br($line->product_label);
print '</td>';

print '<td class="nobottom linecoluht" align="right">';
print '<input type="text" size="5" name="price_ht" id="price_ht" class="flat right" value="' . $line->subprice . '">';
print '</td>';

print '<td class="nobottom linecolqty" align="right">';
print '<input type="text" size="2" name="qty" id="qty" class="flat right" value="' . $line->qty . '">';
print '</td>';

if($conf->global->PRODUCT_USE_UNITS) {
	print '<td class="nobottom linecoluseunit" align="left">';
	print $form->selectUnits($line->fk_unit, "units");
	print '</td>';
}

if (! empty($usemargins)){
	if (!empty($user->rights->margins->creer)) {
		print '<td align="right" class="nobottom margininfos linecolmargin">';
		print '<input type="text" size="5" id="buying_price" name="buying_price" class="flat right" value="' . $line->pa_ht .'">';
		print '</td>';
		$coldisplay++;
	}
}
print '<td class="nobottom linecoledit" align="right" valign="middle" colspan="' . $colspan . '">';
print '<input type="submit" class="button" value="' . $langs->trans('Add') .'" name="addline" id="addline">';
print '</td>';
print '</tr>';
print '<tr class="pair nodrag nodrop nohoverpair' . ($nolinesbefore || $object->element=='contrat')?'':' liste_titre_create' .'">';
print '<td class="nobottom linecoldescription minwidth500imp" align="left" colspan="20">';
print 'Commentaire: <input type="text" size="150" name="dp_desc" id="dp_desc" class="flat left" value="' . $line->description . '">';
print '</td>';
print '</tr>';
?>

<!-- END PHP TEMPLATE objectline_edit.tpl.php for order -->
