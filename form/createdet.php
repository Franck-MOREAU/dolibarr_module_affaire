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
$action = GETPOST('action', 'alpha');

$gamme=GETPOST('gamme','int');
$silhouette=GETPOST('silhouette','int');
$genre=GETPOST('genre','int');
$carrosserie=GETPOST('carrosserie','int');
$marque_trt=GETPOST('marque_trt','int');
$status=GETPOST('status','int');
$motifs=GETPOST('motifs','int');
$spec=GETPOST('spec','san_alpha');

$object = new Affaires($db);

// Load object
if ($id > 0) {
	$ret = $object->fetch($affaireid);
	if ($ret < 0) setEventMessages(null, $object->errors, 'errors');
}

if ($action == 'createdet') {

	$objectdet = new Affaires_det($db);
	$objectdet->fk_affaires = $object->id;
	$objectdet->fk_game = $gamme;
	$objectdet->fk_silhouette = $silhouette;
	$objectdet->fk_carrosserie = $carrosserie;
	$objectdet->fk_status = $status;
	$objectdet->fk_marque_trt = $marque_trt;
	$objectdet->fk_motifs = $motifs;
	$objectdet->spec = $spec;

	$res = $lead->create($user);
	if ($res<0){
		setEventMessage($lead->errors,'errors');
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

print '<form name="createorder" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="leadid" value="' . $leadid . '">';
print '<input type="hidden" name="action" value="createdet">';

print '<table class="border" width="100%">';
print '<tr class="liste_titre">';
print '<th align="center" colspan="7">' . "Ajout d'un véhicule</th>";
print '</tr>';
print '<tr class="oddeven">';
print '<td align="center">' . $langs->trans('Gamme') . '</td>';
print '<td align="center">' . $langs->trans('Silhouette') .'</td>';
print '<td align="center">' . $langs->trans('Genre'). '</td>';
print '<td align="center">' . $langs->trans('Carroserie'). '</td>';
print '<td align="center">' . $langs->trans('Marque'). '</td>';
print '<td align="center">' . $langs->trans('Status'). '</td>';
print '<td align="center">' . $langs->trans('Spec'). '</td>';
print '<td align="center">' . $langs->trans('MotifPerte'). '</td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td align="center">' . $formAffaires->select_affaires_fromdict($gamme,'gamme',0) . '</td>';
print '<td align="center">' . $formAffaires->select_affaires_fromdict($silhouette,'silhouette',0) .'</td>';
print '<td align="center">' . $formAffaires->select_affaires_fromdict($genre,'genre',0). '</td>';
print '<td align="center">' . $formAffaires->select_affaires_fromdict($carrosserie,'carrosserie',0). '</td>';
print '<td align="center">' . $formAffaires->select_affaires_fromdict($marque_trt,'marque_trt',0). '</td>';
print '<td align="center">' . $formAffaires->select_affaires_fromdict($status,'status',0). '</td>';
print '<td align="center">' . '<input type="text" name="spec" id="spec" value="'.$spec.'"/>'. '</td>';
print '<td align="center">' . $formAffaires->select_affaires_fromdict($motifs,'motifs',1). '</td>';
print '</tr>';
print '</table>';

print '<div class="tabsAction">';
print '<input type="submit" align="center" class="button" value="' . $langs->trans('Save') . '" name="save" id="save"/>';
print '</div>';
print '</form>';

llxFooter();
$db->close();