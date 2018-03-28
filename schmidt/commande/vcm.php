<?php
$res = @include '../../main.inc.php'; // For root directory
if (! $res) $res = @include '../../../main.inc.php'; // For "custom" directory
if (! $res) die("Include of main fails");

//require_once DOL_DOCUMENT_ROOT . '/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
dol_include_once('/affaires/volvo/lib/volvo.lib.php');

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$form = new Form($db);
$object = new Commande($db);
if(!empty($id)) $object->fetch($id);
$extrafields = new ExtraFields($db);

$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
$res = $object->fetch_optionals($object->id, $extralabels);
if ($action == 'update_extras')	{
	$ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute'));
	if ($ret < 0) $error++;
	if ($error){
		$action = 'edit_extras';
	}else{
		$result = $object->insertExtraFields();
	}
}



llxHeader('', 'VCM');
$head = commande_prepare_head($object);
dol_fiche_head($head, 'vcm', $langs->trans("CustomerOrder"), 0, 'order');

$code = volvo_vcm_ok($object);
$motif = volvo_vcm_motif($code);

print load_fiche_titre('Cotation VCM - ' . $motif ,'',dol_buildpath('/volvo/img/object_iron02.png', 1),1);
print '<table class="border" width="100%">';
print '<tr class="liste_titre"><td align="center">Entretien et Maintenance du véhicule</td></tr>';
print '<tr><td>';
print '<table class="nobordernopadding" width="100%">';
print '<tr>';
// deja client
print '<td>';
print print_extra('vcm_deja','yesno',$action,$extrafields,$object,1);
print '</td>';
// vh deporté
print '<td>' . print_extra('vcm_deport','yesno',$action,$extrafields,$object,1) . '</td>';
//point de services
$key = 'vcm_site';
$align_button='left';
$label = $extrafields->attribute_label[$key];
include dol_buildpath('/affaires/tpl/extra_inline.php');
print '</tr><tr style="height:5px"><td colspan="4" style="height:5px"></td></tr>';

//print '<tr>';
// atelier
//print '<td>' . print_extra('vcm_atel','chkbox',$action,$extrafields,$object,1) . '</td>';
// type maintenance
//print '<td colspan="2">' . print_extra('vcm_maint','chkbox',$action,$extrafields,$object,1) . '</td>';
//print '</tr><tr style="height:5px"><td colspan="4" style="height:5px"></td></tr>';

print '<tr>';
// transfert vers gds
print '<td colspan="2">' . print_extra('vcm_trf_gds', 'bool', $action, $extrafields, $object,1) . '</td>';
// transfert vers DFOL
print '<td colspan="2">' . print_extra('vcm_trf_dfol', 'bool', $action, $extrafields, $object,1) . '</td>';
print '</tr><tr style="height:5px"><td colspan="4" style="height:5px"></td></tr>';
print '</table>';
print'</td></tr>';

print '<tr class="liste_titre"><td align="center">Termes de la Solution de Maintenance</td></tr>';

print '<tr><td>';
print '<table class="border" width="100%">';
print '<tr>';
// date debut souhaitée
print '<td rowspan="2" valign="top" align="center">' . print_extra('vcm_dt_dem', 'date', $action, $extrafields, $object,1) . '</td>';
print '<td bgcolor="56aaff" class="liste_titre" align="center">Prévention</td>';
print '<td bgcolor="ff7f00" class="liste_titre" colspan=2 align="center">Entretien</td>';
print '<td bgcolor="#00ff00" class="liste_titre" colspan=2 align="center">Protection</td>';
print '<td class="liste_titre" rowspan="2"></td>';
// entete tableau offre VCM
print '<tr>';
print '<td bgcolor="56aaff" class="liste_titre" align="center">Suivi et diag</td>';
print '<td bgcolor="ff7f00" class="liste_titre" align="center">Entretien</br>périodique</td>';
print '<td bgcolor="ff7f00" class="liste_titre" align="center">' ."Pieces d'usures" . '</td>';
print '<td bgcolor="#00ff00" class="liste_titre" align="center">Chaîne</br>cinématique</br>+ echappement</td>';
print '<td bgcolor="#00ff00" class="liste_titre" align="center">Chaîne</br>chassîs Cabine</td>';
print '</tr>';
// pack prévention connecté
print '<tr style="height:5px"><td colspan="7" style="height:5px"></td></tr>';
print '<tr bgcolor="aaffd4">';
print '<td class="liste_titre">Prévention Connecté</td>';
print '<td align="center"> Inclus </td>';
print '<td align="center"></td>';
print '<td align="center"></td>';
print '<td align="center"></td>';
print '<td align="center"></td>';
print '<td>' . print_extra('vcm_ppc', 'bool', $action, $extrafields, $object,0) . '</td>';
print '</tr>';
// pack protection cinématique
print '<tr bgcolor="aaffd4">';
print '<td class="liste_titre">Protection Cinématique</td>';
print '<td align="center"></td>';
print '<td align="center"></td>';
print '<td align="center"></td>';
print '<td align="center"> Inclus </td>';
print '<td align="center"></td>';
print '<td>' . print_extra('vcm_pc', 'bool', $action, $extrafields, $object,0) . '</td>';
print '</tr>';
// pack protection cinématique connecté
print '<tr bgcolor="aaffd4">';
print '<td class="liste_titre">Protection Cinématique Connecté</td>';
print '<td align="center"> Inclus </td>';
print '<td align="center"></td>';
print '<td align="center"></td>';
print '<td align="center"> Inclus </td>';
print '<td align="center"></td>';
print '<td>' . print_extra('vcm_pcc', 'bool', $action, $extrafields, $object,0) . '</td>';
print '</tr>';
// pack protection véhicule
print '<tr bgcolor="aaffd4">';
print '<td class="liste_titre">Protection Véhicule Connecté</td>';
print '<td align="center"> Inclus </td>';
print '<td align="center"></td>';
print '<td align="center"></td>';
print '<td align="center"> Inclus </td>';
print '<td align="center">Inclus</td>';
print '<td>' . print_extra('vcm_pvc', 'bool', $action, $extrafields, $object,0) . '</td>';
print '</tr><tr style="height:5px"><td colspan="7" style="height:5px"></td></tr>';
// blue
print '<tr bgcolor="aad4ff">';
print '<td class="liste_titre">Blue Connecté</td>';
print '<td align="center">Inclus</td>';
print '<td align="center">Inclus</td>';
print '<td align="center"></td>';
print '<td align="center"></td>';
print '<td align="center"></td>';
print '<td>' . print_extra('vcm_blue', 'bool', $action, $extrafields, $object,0) . '</td>';
print '</tr>';
// silver
print '<tr bgcolor="cccccc">';
print '<td class="liste_titre">Silver Connecté</td>';
print '<td align="center">Inclus</td>';
print '<td align="center">Inclus</td>';
print '<td align="center"></td>';
print '<td align="center">Inclus</td>';
print '<td align="center"></td>';
print '<td>' . print_extra('vcm_silver', 'bool', $action, $extrafields, $object,0) . '</td>';
print '</tr>';
//silver+
print '<tr bgcolor="b2b2b2">';
print '<td class="liste_titre">Silver+ Connecté</td>';
print '<td align="center">Inclus</td>';
print '<td align="center">Inclus</td>';
print '<td align="center">Inclus</td>';
print '<td align="center">Inclus</td>';
print '<td align="center"></td>';
print '<td>' . print_extra('vcm_silverp', 'bool', $action, $extrafields, $object,0) . '</td>';
print '</tr>';
//gold
print '<tr bgcolor="ffffaa">';
print '<td class="liste_titre">Gold Connecté</td>';
print '<td align="center">Inclus</td>';
print '<td align="center">Inclus</td>';
print '<td align="center">Inclus</td>';
print '<td align="center">Inclus</td>';
print '<td align="center">Inclus</td>';
print '<td>' . print_extra('vcm_gold', 'bool', $action, $extrafields, $object,0) . '</td>';
print '</tr>';
print '</table>';

print '<table class="nobordernopadding" width="100%"><tr style="height:5px"><td colspan="2" style="height:5px"></td></tr></table>';
// date de demmarrage
print '<table class="nobordernopadding" width="100%">';
print '<tr>';
Print '<td>' . print_extra('vcm_duree', 'chkbox', $action, $extrafields, $object,1) . '</td>';
print '</tr>';
print '</table>';

print '<table class="nobordernopadding" width="100%"><tr style="height:5px"><td colspan="2" style="height:5px"></td></tr></table>';

print '<table class="nobordernopadding" width="100%">';
print '<tr>';
if($object->array_options['options_vcm_blue'] == 1 ||$object->array_options['options_vcm_silver'] == 1 ||
		$object->array_options['options_vcm_silverp'] == 1 ||$object->array_options['options_vcm_gold'] == 1){
	// consommation estimée
	Print '<td bgcolor="ffaaaa">' . print_extra('vcm_conso', 'num', $action, $extrafields, $object,1,5,'l/100km') . '</td>';
}else{
	print '<td  align="Left"></td>';
}
// Kilométrage annuel
print '<td  align="Left">' . print_extra('vcm_km', 'num', $action, $extrafields, $object,1,7,'km/an') . '</td>';
// kilometre départ
print '<td  align="Left">' . print_extra('vcm_km_dep', 'num', $action, $extrafields, $object,1,7,'km') . '</td>';
// ptra
print '<td  align="Left">' . print_extra('vcm_ptra', 'num', $action, $extrafields, $object,1,5,'Tonnes') . '</td>';
print '</tr>';
print '</table>';

print '<table class="nobordernopadding" width="100%"><tr style="height:5px"><td colspan="2" style="height:5px"></td></tr></table>';

print '<table class="nobordernopadding" width="100%">';
print '<tr>';
//type de prise de force
Print '<td align="left">'. print_extra('vcm_pto','chkbox',$action,$extrafields,$object,1).'</td>';
//nb heure pto
if(!empty($object->array_options['options_vcm_pto'])){
	print '<td  align="Left">'.  print_extra('vcm_pto_nbh','num',$action,$extrafields,$object,1,5,'H/an').'</td>';
}else{
	print '<td  align="Left"></td>';
}
// nb heure pto depart
if(!empty($object->array_options['options_vcm_pto'])){
	print '<td  align="Left">' . print_extra('vcm_pto_hdep','num',$action,$extrafields,$object,1,5,'H') . '</td>';
}else{
	print '<td  align="Left"></td>';
}
print '</tr>';
print '</table>';

print '<table class="nobordernopadding" width="100%"><tr style="height:5px"><td colspan="2" style="height:5px"></td></tr></table>';

print '<table class="nobordernopadding" width="100%">';
if(!empty($object->array_options['options_vcm_pto'])){
	print '<tr>';
	//hydraulique
	Print '<td align="left">' . print_extra('vcm_hydro','chkbox',$action,$extrafields,$object,1) . '</td>';
	print '</tr>';
}
print '<tr><td>' . print_extra('vcm_carr', 'textlong', $action, $extrafields, $object) . '</td></tr>';
print '</table>';

print '<table class="nobordernopadding" width="100%"><tr style="height:5px"><td colspan="2" style="height:5px"></td></tr></table>';
print'</td></tr>';
print '<tr class="liste_titre"><td align="center">Utilisation du Véhicule</td></tr>';

print '<tr><td>';
print '<table class="nobordernopadding" width="100%">';
//print '<tr>';
//amplitude hebdo
//Print '<td align="left" Colspan="2">' . print_extra('vcm_amp_heb','chkbox',$action,$extrafields,$object,1) . '</td>';
//print '</tr><tr style="height:5px"><td colspan="2" style="height:5px"></td></tr>';

//print '<tr>';
//amplitude journaliere
//Print '<td align="left" Colspan="2">' . print_extra('vcm_amp_jour','chkbox',$action,$extrafields,$object,1) . '</td>';
//print '</tr><tr style="height:5px"><td colspan="2" style="height:5px"></td></tr>';

print '<tr>';
//nb position/jour
//Print '<td align="left">' . print_extra('vcm_nbpos','num',$action,$extrafields,$object,1,4,'Positions / Jour') . '</td>';
//activité saisoniere
Print '<td align="left">'.print_extra('vcm_sais', 'bool', $action, $extrafields, $object,1) . '</td>';
print '<td></td>';
print '</tr><tr style="height:5px"><td colspan="2" style="height:5px"></td></tr>';

print '<tr>';
//structure chantier
Print '<td align="left">'.print_extra('vcm_chant', 'bool', $action, $extrafields, $object,1) .'</td>';
// activité urbaine
Print '<td align="left">'.print_extra('vcm_ville', 'bool', $action, $extrafields, $object,1) . '</td>';
print '</tr><tr style="height:5px"><td colspan="2" style="height:5px"></td></tr>';

print '<tr>';
//activité régionnale
Print '<td align="left">'.print_extra('vcm_50km', 'bool', $action, $extrafields, $object,1) .'</td>';
//longue distance
Print '<td align="left">'.print_extra('vcm_ld', 'bool', $action, $extrafields, $object,1) . '</td>';
print '</tr><tr style="height:5px"><td colspan="2" style="height:5px"></td></tr>';

print '<tr>';
//calcul du cycle de transport
$cycle='';
if($object->array_options['options_vcm_ld']==1) $cycle='Longue Distance';
if($object->array_options['options_vcm_50km']==1) $cycle='Distribution Régionnale';
if($object->array_options['options_vcm_ville']==1) $cycle='Distribution Urbaine';
if($object->array_options['options_vcm_chant']==1) $cycle='Construction';

Print '<td align="left" Colspan="2">'."Cycle de transport:" . $cycle . '</td>';
print '</tr><tr style="height:5px"><td colspan="2" style="height:5px"></td></tr>';

print '<tr>';
//zone géographique
$key = 'vcm_zone';
$align_button='left';
$label = $extrafields->attribute_label[$key];
include dol_buildpath('/affaires/tpl/extra_inline.php');
//type de transport
$key = 'vcm_typ_trans';
$align_button='left';
$label = $extrafields->attribute_label[$key];
include dol_buildpath('/affaires/tpl/extra_inline.php');
print '</tr><tr style="height:5px"><td colspan="2" style="height:5px"></td></tr>';

print '<tr>';
//conditions de roulage
$key = 'vcm_roul';
$align_button='left';
$label = $extrafields->attribute_label[$key];
include dol_buildpath('/affaires/tpl/extra_inline.php');
//topographie
$key = 'vcm_topo';
$align_button='left';
$label = $extrafields->attribute_label[$key];
include dol_buildpath('/affaires/tpl/extra_inline.php');
print '</tr><tr style="height:5px"><td colspan="2" style="height:5px"></td></tr>';
print '</table>';
print '</br>';
print '</td></tr>';

if($object->array_options['options_vcm_blue'] == 1 ||$object->array_options['options_vcm_silver'] == 1 ||
	$object->array_options['options_vcm_silverp'] == 1 ||$object->array_options['options_vcm_gold'] == 1){
	print '<tr class="liste_titre"><td align="center">Options</td></tr>';
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%">';

	print '<tr>';
	print '<td>';
	print print_extra('vcm_pack', 'chkboxvert', $action, $extrafields, $object,0);
	print '</br>';
	print print_extra('vcm_option', 'chkboxvert', $action, $extrafields, $object,0);
	print '</td>';

	print '<td>';
	print print_extra('vcm_sup', 'chkboxvert', $action, $extrafields, $object,0);
	print '</td>';

	print '<td>';
	print print_extra('vcm_legal', 'chkboxvert', $action, $extrafields, $object,0);
	print '</td>';
	print '</tr>';
	print '</table>';
	print '</td></tr>';

	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%">';
	print '<tr>';
	print '<td>';
	print print_extra('vcm_frigo', 'bool', $action, $extrafields, $object);
	print '</td>';
	if($object->array_options['options_vcm_frigo']==1){
		$key = 'vcm_marque';
		$align_button='left';
		$label = $extrafields->attribute_label[$key];
		include dol_buildpath('/affaires/tpl/extra_inline.php');
	}else{
		print '<td></td>';
	}
	print '</tr><tr>';
	print '<td>';
	if($object->array_options['options_vcm_frigo']==1) print print_extra('vcm_model', 'text', $action, $extrafields, $object,1,15);
	print '</td>';
	if($object->array_options['options_vcm_frigo']==1){
		$key = 'vcm_fonct';
		$align_button='left';
		$label = $extrafields->attribute_label[$key];
		include dol_buildpath('/affaires/tpl/extra_inline.php');
	}else{
		print '<td></td>';
	}
	print '</tr>';
	print '</tr><tr style="height:5px"><td colspan="2" style="height:5px"></td></tr>';
	print '<tr>';
	print '<td colspan="2">';
	if($object->array_options['options_vcm_frigo']==1) print print_extra('vcm_frigo_nbh', 'num', $action, $extrafields, $object,1,6,'Heures/an');
	print '</td>';
	print '</tr>';
	print '</table>';
	print '</td></tr>';
}
print '</table>';








dol_fiche_end();

?>