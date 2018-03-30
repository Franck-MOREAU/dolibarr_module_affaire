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


print '<!-- BEGIN PHP PERSONAL TEMPLATE objectline_view.tpl.php for order-->';
if($line->rang%2==0){
	$bc = 'class="drag drop pair"';
}else{
	$bc = 'class="drag drop impair"';
}



print '<tr id="row-' . $line->id . '" ' . $bc . '>';
// article
print '<td class="linecoldescription" style="border-bottom-style: none; border-top: 1px solid black;" colspan="2">';
print '<div id="line_' . $line->id . '"></div>';
$format = $conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE ? 'dayhour' : 'day';
print $form->textwithtooltip ( $text, $description, 3, '', '', $i, 0, (! empty ( $line->fk_parent_line ) ? img_picto ( '', 'rightarrow' ) : '') );

// Add description in form
if (! empty ( $conf->global->PRODUIT_DESC_IN_FORM )) {
	print (! empty ( $line->description ) && $line->description != $line->product_label) ? '<br>' . dol_htmlentitiesbr ( $line->description ) : '';
}

print '</td>';

//quantité
print '<td align="right" class="linecolqty nowrap" style="border-bottom-style: none; border-top: 1px solid black;">';
if ((($line->info_bits & 2) != 2) && $line->special_code != 3) {
	print $line->qty;
} else {
	print '&nbsp;';
}
print '</td>';

// prix unitaire
print '<td align="right" class="linecoluht nowrap"	style="border-bottom-style: none; border-top: 1px solid black;">';
print price ( $line->subprice );
print '</td>';

//prix achat
print '<td align="right" class="linecolmargin1 nowrap margininfos"	style="border-bottom-style: none; border-top: 1px solid black;">';
print price ( $line->pa_ht );
print '</td>';

// prix réel
print '<td align="right" class="linecoluht nowrap" style="border-bottom-style: none; border-top: 1px solid black;">';
// TODO recuperer le montant des factures fournisseurs
//print price ( $line->array_options ["options_buyingprice_real"] );
print '</td>';

// prix réel
print '<td align="right" class="linecoluht nowrap" style="border-bottom-style: none; border-top: 1px solid black;">';
// TODO recuperer le montant des factures fournisseurs
//print price ( $line->array_options ["options_buyingprice_real"] );
print '</td>';

$soltrs1 = prepare_array ( 'VOLVO_VCM_LIST', 'array' );
$soltrs2 = prepare_array ( 'VOLVO_PACK_LIST', 'array' );
$soltrs = array_merge ( $soltrs1, $soltrs2 );

if ($this->statut == 0 && $object_rights->creer) {
	// editer
	print '<td class="linecoledit" align="center" style="border-bottom-style: none; border-top: 1px solid black;">';
	if (empty ( $disableedit )) {
		print '<a href="' . $_SERVER ["PHP_SELF"] . '?id=' . $this->id . '&amp;action=editline&amp;lineid=' . $line->id . '#line_' . $line->id . '">' . img_edit () . '</a>';
	}
	print '</td>';
	print '<td class="linecoldelete" align="center" style="border-bottom-style: none; border-top: 1px solid black;">';
	print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $this->id . '&amp;action=ask_deleteline&amp;lineid=' . $line->id . '">' . img_delete() . '</a>';
	print '</td>';

	print '<td class="linecolmove tdlineupdown" align="center" style="border-bottom-style: none; border-top: 1px solid black;">';
	if ($i > 0) {
		print '<a class="lineupdown" href="' . $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=up&amp;rowid='.$line->id .'">' . img_up('default',0,'imgupforline') .'</a>';
	}
	if ($i < $num-1) {
		print '<a class="lineupdown" href="' . $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=down&amp;rowid='.$line->id .'">' . img_down('default',0,'imgupforline') .'</a>';
	}
	print '</td>';

} elseif (($this->statut > 0 && ($object_rights->creer)) && (in_array ( $line->product_ref, $soltrs ))) {
	// supprimer
	print '<td class="linecoledit" align="center" style="border-bottom-style: none; border-top: 1px solid black;">';
	if (!$line->total_ht==0) {
		print '<a href="' . $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=editline&amp;lineid='.$line->id.'#line_'.$line->id. '">' . img_edit() . '</a>';
	}
	print '</td>';
	print '<td class="linecoldelete" align="center" style="border-bottom-style: none; border-top: 1px solid black;">';
	if ($line->total_ht == 0) {
		print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $this->id . '&amp;action=ask_deleteline&amp;lineid=' . $line->id . '">' . img_delete() . '</a>';
	}
	print '</td>';
	print '<td align="center" class="linecolmove tdlineupdown" style="border-bottom-style: none; border-top: 1px solid black;">';

	print '</td>';

} else {
	print '<td colspan="3" style="border-bottom-style: none; border-top: 1px solid black;">';
	print '</td></tr>';
}

if(!empty($line->desc)){
	print '<tr id="row-'.$line->id.'" '.$bc.'>';
	print '<td colspan="10" style="border-style: none"><b><span style="text-decoration: underline;">Commentaire:</span></b>' . $line->desc . '</td></tr>';
}
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
$cmd_fourn_line = New CommandeFournisseurLigne($line->db);
if($line->array_options['options_fk_supplierorderlineid']>0){
	$res_line = $cmd_fourn_line->fetch($line->array_options['options_fk_supplierorderlineid']);
}else{
	$res_line=0;
}
if($res_line>0){
	dol_include_once('/fourn/class/fournisseur.commande.class.php');
	$cmd_fourn = new CommandeFournisseur($line->db);
	$res = $cmd_fourn->fetch($cmd_fourn_line->fk_commande);
	if($res>0){
		$ret = $cmd_fourn->getLibStatut(3);
		$ret.= ' '. $langs->trans("SupplierOrder") . ' ';
		$ret.= $cmd_fourn->getNomUrl(1) . ' ';
		$ret.= dol_print_date($cmd_fourn->date,'day') . ' ';
		$solde = $cmd_fourn_line->array_options['options_solde'];
		dol_include_once('/affaires/class/affaires.class.php');
		$affaires_det = New Affaires_det($line->db);
		$solde_amount = $affaires_det->getSumFactFournLn($line->id,0);
		if(!empty($solde)){
			$txt = 'Soldé';
			$img = img_picto($txt, 'statut4');
		}elseif(empty($solde) && !($solde_amount==0 || $solde_amount==-99999)){
			$txt = 'Partiellement soldé';
			$img = img_picto($txt, 'statut3');
			$txt2 = ', Montant enregistré: ' . price($solde_amount) . ' €';
		}else{
			$txt = 'non soldé';
			$img = img_picto($txt, 'statut0');
		}

		$ret.= '  -  ' . $img . ' Factures fournisseur: ' . $txt . ' ' . $txt2;

	}else{
		$ret = '';
	}
}

if(!empty($ret)){
	print '<tr id="row-'.$line->id.'" '.$bc.'>';
	print '<td colspan="10" style="border-style: none">' . $ret . '</td></tr>';
}

print '<!-- END PHP TEMPLATE objectline_view.tpl.php for order -->';
