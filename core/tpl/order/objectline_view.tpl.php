<?php
/* Copyright (C) 2010-2013	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
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
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $element     (used to test $user->rights->$element->creer)
 * $permtoedit  (used to replace test $user->rights->$element->creer)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $usemargins (0 to disable all margins columns, 1 to show according to margin setup)
 * $object_rights->creer initialized from = $object->getRights()
 * $disableedit, $disablemove, $disableremove
 *
 * $type, $text, $description, $line
 */

// Protection to avoid direct call of template
if (empty($object) || ! is_object($object)) {
	print "Error, template page can't be called as URL";
	exit();
}

global $forceall, $senderissupplier, $inputalsopricewithtax, $outputalsopricetotalwithtax;

$usemargins = 0;
if (! empty($conf->margin->enabled) && ! empty($object->element) && in_array($object->element, array(
		'facture',
		'propal',
		'commande'
)))
	$usemargins = 1;

if (empty($dateSelector))
	$dateSelector = 0;
if (empty($forceall))
	$forceall = 0;
if (empty($senderissupplier))
	$senderissupplier = 0;
if (empty($inputalsopricewithtax))
	$inputalsopricewithtax = 0;
if (empty($outputalsopricetotalwithtax))
	$outputalsopricetotalwithtax = 0;

$coldisplay = 0;
print '<!-- BEGIN PHP PERSONAL TEMPLATE objectline_view.tpl.php for order-->';
print '<tr id="row-' . $line->id . '" ' . $bcdd [$var] . '>';
print '<td class="linecoldescription" style="border-bottom-style: none" colspan="2">';
$coldisplay++;
print '<div id="line_' . $line->id . '"></div>';
$format = $conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE ? 'dayhour' : 'day';
print $form->textwithtooltip ( $text, $description, 3, '', '', $i, 0, (! empty ( $line->fk_parent_line ) ? img_picto ( '', 'rightarrow' ) : '') );

// Show range
print get_date_range ( $line->date_start, $line->date_end, $format );

// Add description in form
if (! empty ( $conf->global->PRODUIT_DESC_IN_FORM )) {
	print (! empty ( $line->description ) && $line->description != $line->product_label) ? '<br>' . dol_htmlentitiesbr ( $line->description ) : '';
}


print '</td><td align="right" class="linecolqty nowrap" style="border-bottom-style: none">';
$coldisplay ++;
if ((($line->info_bits & 2) != 2) && $line->special_code != 3) {
	print $line->qty;
} else {
	print '&nbsp;';
}
print '</td>';

print '<td align="right" class="linecoluht nowrap"	style="border-bottom-style: none">';
$coldisplay ++;
print price ( $line->subprice );
print '</td>';

print '<td align="right" class="linecolmargin1 nowrap margininfos"	style="border-bottom-style: none">';
$coldisplay ++;
print price ( $line->pa_ht );
print '</td>';

print '<td align="right" class="linecoluht nowrap" style="border-bottom-style: none">';
$coldisplay ++;
// TODO recuperer le montant des factures fournisseurs
//print price ( $line->array_options ["options_buyingprice_real"] );
print '</td>';

// TODO détecter factures fournisseur
if (empty ( $line->array_options ["options_fk_supplier"] )) {
	print '<td align="right" class="liencolht nowrap" style="border-bottom-style: none">';
	$coldisplay ++;
	print price ( $line->total_ht - ($line->qty * $line->pa_ht) );
	print '</td>';
} else {
	print '<td align="right" class="liencolht nowrap" style="border-bottom-style: none">';
	$coldisplay ++;
	print price ( $line->total_ht - $line->array_options ["options_buyingprice_real"] );
	print '</td>';
}

$soltrs1 = prepare_array ( 'VOLVO_VCM_LIST', 'array' );
$soltrs2 = prepare_array ( 'VOLVO_PACK_LIST', 'array' );
$soltrs = array_merge ( $soltrs1, $soltrs2 );

if ($this->statut == 0 && $object_rights->creer) {
	print '<td class="linecoledit" align="center" style="border-bottom-style: none">';
	$coldisplay ++;
	if (($line->info_bits & 2) == 2 || ! empty ( $disableedit )) {
	} else {
		print '<a href="' . $_SERVER ["PHP_SELF"] . '?id=' . $this->id . '&amp;action=editline&amp;lineid=' . $line->id . '#line_' . $line->id . '">' . img_edit () . '</a>';
	}
	print '</td>';
	print '<td class="linecoldelete" align="center" style="border-bottom-style: none">';
	$coldisplay ++;
	if ((($this->situation_counter == 1 || ! $this->situation_cycle_ref) && empty ( $disableremove ))) {
		print '<a href="' . $_SERVER ["PHP_SELF"] . '?id=' . $this->id . '&amp;action=ask_deleteline&amp;lineid=' . $line->id . '">' . img_delete () . '</a>';
	}
	print '</td>';
	if ($num > 1 && empty ( $conf->browser->phone ) && ($this->situation_counter == 1 || ! $this->situation_cycle_ref) && empty ( $disablemove )) {
		print '<td align="center" class="linecolmove tdlineupdown" 	style="border-bottom-style: none">';
		$coldisplay ++;
		if ($i > 0) {
			print '<a class="lineupdown" href="' . $_SERVER ["PHP_SELF"] . '?id=' . $this->id . '&amp;action=up&amp;rowid=' . $line->id . '">' . img_up ( 'default', 0, 'imgupforline' ) . '</a>';
		}
		if ($i < $num - 1) {
			print '<a class="lineupdown" href="' . $_SERVER ["PHP_SELF"] . '?id=' . $this->id . '&amp;action=down&amp;rowid=' . $line->id . '">' . img_down ( 'default', 0, 'imgdownforline' ) . '</a>';
		}
		print '</td>';
	} else {
		print '<td align="center"' . ((empty ( $conf->browser->phone ) && empty ( $disablemove )) ? ' class="linecolmove tdlineupdown"' : ' class="linecolmove"') . ' style="border-bottom-style: none">';
		$coldisplay ++;
		print '</td>';
	}
} elseif (($this->statut > 0 && ($object_rights->creer)) && (in_array ( $line->product_ref, $soltrs ))) {
	print '<td class="linecoledit" align="center" style="border-bottom-style: none">';
	$coldisplay++;
	if ($line->total_ht==0) {

	} else {
		print '<a href="' . $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=editline&amp;lineid='.$line->id.'#line_'.$line->id. '">' . img_edit() . '</a>';
	}
	print '</td>';
	print '<td class="linecoldelete" align="center" style="border-bottom-style: none">';
	$coldisplay++;
	if ($line->total_ht == 0) {
		print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $this->id . '&amp;action=ask_deleteline&amp;lineid=' . $line->id . '">' . img_delete() . '</a>';
	}
	print '</td>';
	print '<td></td>';

} else {
	print '<td colspan="3" style="border-bottom-style: none">';
	$coldisplay=$coldisplay+3;
	print '</td>';
}

if(!empty($line->desc)){
	print '<tr id="row-'.$line->id.'" '.$bcdd[$var].'>';
	print '<td colspan="10" style="border-style: none"><b><span style="text-decoration: underline;">Commentaire:</span></b>' . $line->desc . '</td></tr>';
}

// Line extrafield
if (! empty($line->array_options["options_fk_supplier"]) || ! empty($line->array_options["options_fk_supplier"])) {
	print '<tr id="row-'.$line->id.'" '.$bcdd[$var] .'>';
	print '<td><b><span style="text-decoration: underline;">facture de:</span></b>' . $extrafieldsline->showOutputField("fk_supplier",$line->array_options["options_fk_supplier"]) .'</td>';
	print '<td><b><span style="text-decoration: underline;">Reçue le:</span></b>' . $extrafieldsline->showOutputField("dt_invoice",$line->array_options["options_dt_invoice"]) . '</td>';
	print '<td colspan="8"></td>';
	print '</tr>';
}

print '<!-- END PHP TEMPLATE objectline_view.tpl.php for order -->';
