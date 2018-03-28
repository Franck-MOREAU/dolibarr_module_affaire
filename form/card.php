<?php
/* Lead
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
$res = @include '../../main.inc.php'; // For root directory
if (! $res)
	$res = @include '../../../main.inc.php'; // For "custom" directory
if (! $res)
	die("Include of main fails");

dol_include_once('affaires/class/affaires.class.php');
dol_include_once('/core/class/doleditor.class.php');
dol_include_once('/user/class/user.class.php');
dol_include_once('/affaires/class/html.formaffaires.class.php');




if (! empty($conf->commande->enabled))
	dol_include_once('/commande/class/commande.class.php');

	// Security check
if (! $user->rights->affaires->read)
	accessforbidden();

$langs->load('affaires@affaires');
if (! empty($conf->commande->enabled))
	$langs->load('order');

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$confirm = GETPOST('confirm', 'alpha');
$fk_soc= GETPOST('fk_soc','int');
$fk_user_resp = GETPOST('fk_user_resp','int');

$fk_ctm = GETPOST('fk_ctm','int');
$fk_cv = GETPOST('fk_type','int');
$year = GETPOST('year','int');
$description= GETPOST('description','none');
$vehid=GETPOST('vehid','int');

$object = new Affaires($db);
$objectdet = new Affaires_det($db);
$formAffaires = new FormAffaires($db);


// Load object
if ($id > 0) {
	$ret = $object->fetch($id);
	if ($ret < 0) setEventMessages(null, $object->errors, 'errors');
}

if ($vehid > 0) {
	$ret = $objectdet->fetch($vehid);
	if ($ret < 0) setEventMessages(null, $objectdet->errors, 'errors');
}

/*
 * Actions
 */

if($action=="add"){

	$object->fk_soc = $fk_soc;
	$object->fk_user_resp = $fk_user_resp;
	$object->fk_ctm = $fk_ctm;
	$object->fk_c_type = $fk_cv;
	$object->year = $year;
	$object->description = $description;
	$object->ref = $object->getNextNumRef();

	$res = $object->create($user);
	if($res <0){
		setEventMessages(null, $object->errors, 'errors');
		$action='create';
	}else{
		$id = $res;
		$ret = $object->fetch($id);
		$action ='';
		if ($ret < 0) setEventMessages(null, $object->errors, 'errors');
	}
}

if($action=="update"){

	//$object = new Affaires($db);
	$object->fk_ctm = $fk_ctm;
	$object->year = $year;
	$object->description = $description;
	$res = $object->update($user);
	if($res <0){
		setEventMessages(null, $object->errors, 'errors');
		$action = 'edit';
	}else{
		$ret = $object->fetch($id);
		$action ='';
		if ($ret < 0) setEventMessages(null, $object->errors, 'errors');
	}
}

if($action=="confirm_deletevehid" && $confirm=='yes' && $user->admin){

	$res = $objectdet->delete($user);
	if($res <0){
		setEventMessages(null, $objectdet->errors, 'errors');
		$action = 'edit';
	}else{
		$ret = $object->fetch($id);
		$action ='';
		if ($ret < 0) setEventMessages(null, $object->errors, 'errors');
	}
}

if($action=="confirm_changestatus" && $user->rights->affaires->write){

	$objectdet->fk_status = GETPOST('status','int');
	$res = $objectdet->update($user);
	if($res <0){
		setEventMessages(null, $objectdet->errors, 'errors');
		$action = '';
	}else{
		$ret = $object->fetch($id);
		$action ='';
		if ($ret < 0) setEventMessages(null, $object->errors, 'errors');
		if ($objectdet->fk_status==7) {
			$action = 'update_motif';
		}
	}
}

if($action=="confirm_changemotif" && $user->rights->affaires->write){

	$objectdet->fk_motifs_array=array();
	foreach($objectdet->motifs_dict as $keymotif=>$valmotif) {
		$ismotif=GETPOST('motif_'.$keymotif);
		if (!empty($ismotif)) {
			$objectdet->fk_motifs_array[]=$keymotif;
		}
	}
	$objectdet->fk_marque_trt=GETPOST('marque_trt','int');
	$res = $objectdet->update($user);
	if($res <0){
		setEventMessages(null, $objectdet->errors, 'errors');
		$action = '';
	}else{
		header('Location: ' . dol_buildpath('/affaires/form/card.php',2) . '?id='.$object->id);
		exit;
	}
}


/*
 * View
 */

llxHeader('', 'Affaires');

$form = new Form($db);
$now = dol_now();

if ($action == 'create' && $user->rights->affaires->write) {
	dol_include_once('/core/class/html.formother.class.php');
	$formother = new FormOther($db);

	dol_fiche_head('', '', 'Nouvelle Affaire ' , 0, dol_buildpath('/affaires/img/object_affaires.png', 1), 1);
	print_fiche_titre($langs->trans("affaire") , '', dol_buildpath('/affaires/img/object_affaires.png', 1), 1);

	print '<form name="createlead" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="add">';

	print '<table class="border" width="100%">';
	print '<tr>';
	print '<td width="35%">';
	print $langs->trans("affnum").': Nouvelle Affaire';
	print '</td>';
	print '<td width="65%">';
	print $langs->trans("client").': ' . $form->select_thirdparty_list($object->fk_soc, 'fk_soc', 's.client>0', 1);
	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td>';
	print $langs->trans("userresp").': '. $formAffaires->select_salesmans( empty($object->fk_user_resp) ? $user->id : $object->fk_user_resp,'fk_user_resp','Commerciaux',0);
	print '</td>';
	print '<td>';
	print $langs->trans("ctm").': '. $form->select_thirdparty_list($object->fk_ctm, 'fk_ctm', 's.client>0', 1);
	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td>';
	print $langs->trans("cv").': ' . $form->selectarray('fk_type', $object->type,$object->fk_c_type);
	print '</td>';
	print '<td>';
	print $langs->trans("year").': ';
	$formother->select_year(dol_print_date(dol_now(),'%Y'),'year',0);
	print '</td>';
	print '</tr>';

	$note_public = $object->description;
	$doleditor = new DolEditor('description',$object->description, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');

	print '<tr>';
	print '<td colspan="2">';
	print $doleditor->Create(1);
	print '</td>';
	print '</tr>';

	print '</table>';

	print '<div class="center">';
	print '<input type="submit" class="button" name="bouton" value="' . $langs->trans('Create') . '">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" name="cancel" value="' . $langs->trans("Cancel") . '" onclick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';
}

elseif ($action == 'edit') {
	dol_include_once('/core/class/html.formother.class.php');
	$formother = new FormOther($db);

	dol_fiche_head('', '', 'Affaire ' . $object->ref , 0, dol_buildpath('/affaires/img/object_affaires.png', 1), 1);
	print_fiche_titre($langs->trans("affaire") . ' - ' . $object->ref , '', dol_buildpath('/affaires/img/object_affaires.png', 1), 1);

	print '<form name="editlead" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="border" width="100%">';
	print '<tr>';
	print '<td width="35%">';
	print $langs->trans("affnum").': ' . $object->ref;
	print '</td>';
	print '<td width="65%">';
	print $langs->trans("client").': ' . $object->thirdparty->getNomUrl(1);
	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td>';
	$user_resp = new User($db);
	$user_resp->fetch($object->fk_user_resp);
	print $langs->trans("userresp").': '.$user_resp->getNomUrl(1);
	print '</td>';
	print '<td>';
	print $langs->trans("ctm").': '. $form->select_thirdparty_list($object->fk_ctm, 'fk_ctm', 's.client>0', 1);
	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td>';
	print $langs->trans("cv").': ' . $object->type_label;
	print '</td>';
	print '<td>';
	print $langs->trans("year").': ';
	$formother->select_year($object->year,'year',0);
	print '</td>';
	print '</tr>';

	$doleditor = new DolEditor('description',$object->description, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');

	print '<tr>';
	print '<td colspan="2">';
	print $doleditor->Create(1);
	print '</td>';
	print '</tr>';

	print '</table>';

	print '<div class="center">';
	print '<input type="submit" class="button" name="bouton" value="' . $langs->trans('Save') . '">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" name="cancel" value="' . $langs->trans("Cancel") . '" onclick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';

} else {
	// Confirm form
	$formconfirm = '';
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('LeadDelete'), $langs->trans('LeadConfirmDelete'), 'confirm_delete', '', 0, 1);
	}
	if ($action == 'deleteveh') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id.'&vehid='.$objectdet->id, $langs->trans('ConfrimDeleteVeh'), $langs->trans('ConfrimDeleteVeh'), 'confirm_deletevehid', '', 0, 1);
	}
	if ($action == 'classveh') {
		$formquestion=array();
		$formquestion[]=array(
				'type' => 'other',
				'name' => 'status',
				'label' => 'Statut',
				'value' => $formAffaires->select_affairesdet_fromdict($objectdet->fk_status,'status',0)
		);
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id.'&vehid='.$objectdet->id, $langs->trans('ChangeStatus'), '', 'confirm_changestatus', $formquestion, 0, 1);
	}
	if ($action == 'update_motif') {
		$formquestion=array(
				'text' => $langs->trans("ConfirmClone"),
		);
		foreach($objectdet->motifs_dict as $keymotif=>$valmotif) {
			$formquestion[]=array('type' => 'checkbox', 'name' => 'motif_'.$keymotif,   'label' => $valmotif,   'value' => (in_array($keymotif,$objectdet->fk_motifs_array)));
		}
		$formquestion[]=array(
				'type' => 'other',
				'name' => 'marque_trt',
				'label' => 'Marque Traité',
				'value' => $formAffaires->select_affairesdet_fromdict($objectdet->fk_marque_trt,'marque_trt',0,'marque_trt_dict')
		);

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id.'&vehid='.$objectdet->id, $langs->trans('ChangeMotif'), '', 'confirm_changemotif', $formquestion, 0, 1, 600);
	}
	if ($formconfirm) {
		print $formconfirm;
	}

	/*
	 * Show object in view mode
	 */
	dol_fiche_head();
	print_fiche_titre($langs->trans("affaire") . ' - ' . $object->ref , '', dol_buildpath('/affaires/img/object_affaires.png', 1), 1);

	print '<table class="border" width="100%">';
	print '<tr>';
	print '<td width="35%">';
	print $langs->trans("affnum").': ' . $object->ref;
	print '</td>';
	print '<td width="65%">';
	$user_resp = new User($db);
	$user_resp->fetch($object->fk_user_resp);
	print $langs->trans("client").': ' . $object->thirdparty->getNomUrl(1);
	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td>';
	print $langs->trans("userresp").': '.$user_resp->getNomUrl(1);
	print '</td>';
	print '<td>';
	if($object->fk_ctm>0){
		print $langs->trans("ctm").': '.$object->contremarque->getNomUrl(1);
	}
	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td>';
	print $langs->trans("cv").': ' . $object->type_label;
	print '</td>';
	print '<td>';
	print $langs->trans("year").': '.$object->year;
	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td valign="top" colspan="2">';
	print $langs->trans("Description") . '</br>';
	print $object->description;
	print '</td>';
	print '</tr>';

	print '</table>';

	/*
	 * Barre d'actions
	 */

	// Edit
	print '<div class="tabsAction">';
	if ($user->rights->affaires->write) {
		print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit">' . $langs->trans("Modifier") . "</a></div>\n";
	}
	// Delete
	if ($user->rights->affaires->delete) {
		print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete">' . $langs->trans("Delete") . "</a></div>\n";
	}
	print '</div>';
	?>
	<script type="text/javascript">
		function popCreateAffaireDet(vehid) {
			$div = $('<div id="popCreateAffaireDet"><iframe width="100%" height="100%" frameborder="0" src="<?php echo dol_buildpath('/affaires/form/createdet.php?affaireid='.$object->id,1) ?>&vehid='+vehid+'"></iframe></div>');
			$div.dialog({
				modal:true
				,width:"90%"
				,height:$(window).height() - 200
				,close:function() {document.location.href='<?php echo dol_buildpath('/affaires/form/card.php',2).'?id='.$object->id;?>';}
			});
		}
		function popCreateOrder(vehid) {
			$div = $('<div id="popCreateOrder"><iframe width="100%" height="100%" frameborder="0" src="<?php echo dol_buildpath('/affaires/volvo/commande/createorderfromfdd.php',2)?>?vehid='+vehid+'&step=1"></iframe></div>');
			$div.dialog({
				modal:true
				,width:"90%"
						,height:$(window).height() - 200
						,close:function() {document.location.href='<?php echo dol_buildpath('/affaires/form/card.php',2).'?id='.$object->id;?>';}
			});
		}
	</script>
	<?php
}
dol_fiche_end();

dol_fiche_head();
$head = '<div style="display:inline-block">' . $langs->trans("vhlist") . ' - ' . $object->ref . '</div>';
$head.= '<div style="display:inline-block;float:right" class="divButAction"><a href="javascript:popCreateAffaireDet()" class="butAction">Ajouter un véhicule</a></div>';
print_fiche_titre( $head , '', dol_buildpath('/affaires/img/object_affaires.png', 1), 1);
print '<table class="border" width="100%">';
//var_dump($object);

foreach ($object->affaires_det as $vehicule){
	print '<tr>';
	print '<td width="100%">';
	print $vehicule->vh_tile(0);
	print '</td>';
	print '</tr>';
}
print '</table>';

dol_fiche_end();

llxFooter();
$db->close();
