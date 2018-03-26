<?php
$res = @include '../main.inc.php'; // For root directory
if (! $res)
	$res = @include '../../main.inc.php'; // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
dol_include_once('/affaires/class/html.formaffaires.class.php');
dol_include_once('/affaires/class/affaires.class.php');


$form = new Form($db);
$formAffaires = new FormAffaires($db);

$langs->load('orders');

$affaireid = GETPOST('affaireid', 'int');
$vehid = GETPOST('vehid', 'int');
$action = GETPOST('action', 'alpha');

$gamme=GETPOST('gamme','int');
$silhouette=GETPOST('silhouette','int');
$genre=GETPOST('genre','int');
$carrosserie=GETPOST('carrosserie','int');
$marque_trt=GETPOST('marque_trt','int');
$status=GETPOST('status','int');
$motifs=GETPOST('motifs','array');

$spec=GETPOST('spec','san_alpha');

$object = new Affaires($db);
$objectdet = new Affaires_det($db);

// Load object
if ($affaireid > 0) {
	$ret = $object->fetch($affaireid);
	if ($ret < 0) setEventMessages(null, $object->errors, 'errors');
}


if ($action == 'createdet' || $action == 'editdet' ) {

	$objectdet->fk_affaires = $object->id;
	$objectdet->fk_gamme = $gamme;
	$objectdet->fk_genre = $genre;
	$objectdet->fk_silhouette = $silhouette;
	$objectdet->fk_carrosserie = $carrosserie;
	$objectdet->fk_status = $status;
	$objectdet->fk_marque_trt = $marque_trt;
	$objectdet->spec = $spec;
	if ($action=='editdet') {
		$objectdet->id=$vehid;
		$objectdet->fk_motifs_array = $motifs;
		$res = $objectdet->update($user);
	} else {
		$res = $objectdet->create($user);
	}

	if ($res<0){
		setEventMessages(null,$objectdet->errors,'errors');
	} else {
		top_htmlhead('', '');
		print '<script type="text/javascript">'."\n";
		print '	$(document).ready(function () {'."\n";
		print '	window.parent.$(\'#popCreateAffaireDet\').dialog(\'close\');'."\n";
		print '	window.parent.$(\'#popCreateAffaireDet\').remove();'."\n";
		print '});'."\n";
		print '</script>'."\n";
		llxFooter();
		exit;
	}
}

if ($vehid > 0) {
	$ret = $objectdet->fetch($vehid);

	$gamme=$objectdet->fk_gamme;
	$silhouette=$objectdet->fk_silhouette;
	$genre=$objectdet->fk_genre;
	$carrosserie=$objectdet->fk_carrosserie;
	$marque_trt=$objectdet->marque_trt;
	$status=$objectdet->fk_status;
	$motifs=$objectdet->fk_motifs_array;
	$spec=$objectdet->spec;

	if ($ret < 0) setEventMessages(null, $objectdet->errors, 'errors');
}

top_htmlhead('', '');

print '<form name="createorder" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="affaireid" value="' . $affaireid . '">';
print '<input type="hidden" name="vehid" value="' . $vehid . '">';
print '<input type="hidden" name="action" value="'.(empty($vehid)?'createdet':'editdet').'">';

print '<table class="border" width="100%">';
print '<tr class="liste_titre">';
print '<th align="center" colspan="8">' . "Ajout d'un véhicule</th>";
print '</tr>';
print '<tr class="oddeven">';
print '<td align="center">' . $langs->trans('Gamme') . '</td>';
print '<td align="center">' . $langs->trans('Silhouette') .'</td>';
print '<td align="center">' . $langs->trans('Genre'). '</td>';
print '<td align="center">' . $langs->trans('Carroserie'). '</td>';
print '<td align="center">' . $langs->trans('Marque'). '</td>';
print '<td align="center">' . $langs->trans('Status'). '</td>';
print '<td align="center">' . $langs->trans('Spec'). '</td>';
if (!empty($vehid)) {
	print '<td align="center">' . $langs->trans('MotifPerte'). '</td>';
}
print '</tr>';
print '<tr class="oddeven">';
print '<td align="center">' . $formAffaires->select_affairesdet_fromdict($gamme,'gamme',0,'gamme_dict',array('cv'=>$object->fk_c_type)) . '</td>';
print '<td align="center">' . $formAffaires->select_affairesdet_fromdict($silhouette,'silhouette',0,'silhouette_dict',array('cv'=>$object->fk_c_type)) .'</td>';
print '<td align="center">' . $formAffaires->select_affairesdet_fromdict($genre,'genre',0,'genre_dict',array('cv'=>$object->fk_c_type)). '</td>';
print '<td align="center">' . $formAffaires->select_affairesdet_fromdict($carrosserie,'carrosserie',0,'carrosserie_dict'). '</td>';
print '<td align="center">' . $formAffaires->select_affairesdet_fromdict($marque_trt,'marque_trt',0,'marque_trt_dict'). '</td>';
print '<td align="center">' . $formAffaires->select_affairesdet_fromdict($status,'status',0). '</td>';
print '<td align="center">' . '<input type="text" name="spec" id="spec" value="'.$spec.'"/>'. '</td>';
if (!empty($vehid)) {
	print '<td align="center">' . $formAffaires->select_affairesdet_motifs($motifs,'motifs'). '</td>';
}
print '</tr>';
print '</table>';

print '<div class="tabsAction">';

print '<input type="submit" align="center" class="button" value="' . (empty($vehid)?$langs->trans('Add'):$langs->trans('Save')) . '" name="save" id="save"/>';
print '</div>';
print '</form>';

llxFooter();
$db->close();