<?php
$res = @include '../../main.inc.php'; // For root directory
if (! $res)
	$res = @include '../../../main.inc.php'; // For "custom" directory
if (! $res)
	die("Include of main fails");

dol_include_once('/commande/class/commande.class.php');
dol_include_once('/user/class/user.class.php');
dol_include_once('/product/class/product.class.php');
dol_include_once('/core/class/html.form.class.php');
dol_include_once('/core/lib/files.lib.php');
dol_include_once('/affaires/volvo/class/volvoimportfdd.class.php');

dol_include_once('/affaires/class/html.formaffaires.class.php');
dol_include_once('/affaires/class/affaires.class.php');

ini_set('memory_limit', '-1');

$langs->load('orders');
$langs->load("exports");
$langs->load("errors");
$langs->load('affaires@affaires');

$vehid = GETPOST('vehid', 'int');

$action = GETPOST('action', 'alpha');
$datatoimport = GETPOST('datatoimport');
$step = GETPOST('step', 'int');
$action = GETPOST('action', 'alpha');

$todo = GETPOST('todo', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$urlfile = GETPOST('urlfile');
$filetoimport = GETPOST('filetoimport');

$importobject = new VolvoImportfdd($db);
$form = new Form($db);
$html_volvo = new FormAffaires($db);
$object = new Affaires($db);
$objectdet = new Affaires_det($db);

// Load object

if ($vehid > 0) {
	$ret = $objectdet->fetch($vehid);
	if ($ret < 0)
		setEventMessages(null, $object->errors, 'errors');

	$ret = $object->fetch($objectdet->fk_affaires);
	if ($ret < 0)
		setEventMessages(null, $object->errors, 'errors');
	$object->fetch_thirdparty();
} else {
	exit('need vehid');
}

$dir = $conf->affaires->dir_output . '/volvo/import/fdd';

if ($step == 6) {

	global $conf;
	require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
	require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
	require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

	$targetInfoArray = json_decode(GETPOST('targetInfoArray'), true);
	$interne = GETPOST('interne', 'array');
	Foreach ( $interne as $key => $values ) {
		if ($values['npt'] != 'on') {
			$values['id'] = GETPOST('interne_product' . $key);
			$interne[$key] = $values;
		} else {
			unset($interne[$key]);
		}
	}

	$externe = GETPOST('externe', 'array');
	Foreach ( $externe as $key => $values ) {
		if ($values['npt'] != 'on') {
			$values['id'] = GETPOST('externe_product' . $key);
			$externe[$key] = $values;
		} else {
			unset($externe[$key]);
		}
	}

	$sql0 = "SELECT DISTINCT p.rowid, p.label FROM " . MAIN_DB_PREFIX . "product as p INNER JOIN " . MAIN_DB_PREFIX . "categorie_product as c ON p.rowid = c.fk_product ";
	$sql0 .= "WHERE c.fk_categorie = " . $conf->global->VOLVO_OBLIGATOIRE . " AND p.tosell = 1";

	$resql = $db->query($sql0);
	$obligatoire = array();
	if ($resql) {
		while ( $obj = $db->fetch_object($resql) ) {
			$obligatoire[] = $obj->rowid;
		}
	} else {
		setEventMessage($db->lasterror, 'errors');
	}

	$user = new user($db);
	$product = new product($db);

	$user->fetch($object->fk_user_resp);

	$cmd = new Commande($db);
	$cmd->socid = $object->thirdparty->id;
	$cmd->date = dol_now();
	$cmd->ref_client = $object->ref;
	$cmd->date_livraison = dol_mktime(0, 0, 0, GETPOST('datelivprev_month', 'int'), GETPOST('datelivprev_day', 'int'), GETPOST('datelivprev_year', 'int'));
	$cmd->array_options['options_vnac'] = $targetInfoArray['VNC']['value'];
	$cmd->array_options['options_ctm'] = $object->fk_ctm;
	if (! empty($cmd->array_options['options_ctm'])) {
		dol_include_once('/societe/class/societe.class.php');
		$socctm = new Societe($db);
		$socctm->fetch($cmd->array_options['options_ctm']);
		$cmd->note_public = 'Contremarque: ' . $socctm->name . "\n";
	}
	if ($objectdet->fk_genre == 1) {
		$cmd->cond_reglement_id = 11;
	} elseif ($objectdet->fk_genre == 2) {
		$cmd->cond_reglement_id = 9;
	} else {
		$cmd->cond_reglement_id = 10;
	}
	$rang = 1;
	$line = new OrderLine($db);
	$line->subprice = $targetInfoArray['VNAC']['value'];
	$line->qty = 1;
	$line->tva_tx = 0;
	$line->fk_product = 1;
	$line->pa_ht = $targetInfoArray['VNAC']['value'];
	$line->rang = $rang;
	$line->desc = $targetInfoArray['modele']['value'];
	$rang ++;
	$cmd->lines[] = $line;

	if (! empty($targetInfoArray['flotte']['value'])) {
		$line = new OrderLine($db);
		$line->subprice = $targetInfoArray['flotte']['value'];
		$line->qty = 1;
		$line->tva_tx = 0;
		$line->fk_product = 136;
		$line->pa_ht = $targetInfoArray['flotte']['value'];
		$line->rang = $rang;
		$rang ++;
		$cmd->lines[] = $line;
	}

	if (count($obligatoire) > 0) {
		foreach ( $obligatoire as $art ) {
			$product->fetch($art);
			if ($product->ref == 'TPNEU') {
				$line = new OrderLine($db);
				$line->subprice = $targetInfoArray['pneu']['value'];
				$line->qty = 1;
				$line->tva_tx = 0;
				$line->fk_product = $product->id;
				$line->pa_ht = $targetInfoArray['pneu']['value'];
				$line->rang = $rang;
				$rang ++;
				$cmd->lines[] = $line;
			} elseif ($product->ref == 'LIVUSI') {
				$line = new OrderLine($db);
				$line->subprice = $targetInfoArray['transport']['value'];
				$line->qty = 1;
				$line->tva_tx = 0;
				$line->fk_product = $product->id;
				$line->pa_ht = $targetInfoArray['transport']['value'];
				$line->rang = $rang;
				$rang ++;
				$cmd->lines[] = $line;
			} else {
				$line = new OrderLine($db);
				$line->subprice = $product->price;
				$line->qty = 1;
				$line->tva_tx = 0;
				$line->fk_product = $product->id;
				$line->pa_ht = $product->cost_price;
				$line->rang = $rang;
				$rang ++;
				$cmd->lines[] = $line;
			}
		}
	}

	$line = new OrderLine($db);
	$line->desc = 'Sous-Total Véhicule';
	$line->subprice = 0;
	$line->qty = 99;
	$line->product_type = 9;
	$line->special_code = 104777;
	$line->rang = $rang;
	$rang ++;
	$cmd->lines[] = $line;

	$line = new OrderLine($db);
	$line->desc = 'Travaux Interne';
	$line->subprice = 0;
	$line->qty = 1;
	$line->product_type = 9;
	$line->special_code = 104777;
	$line->rang = $rang;
	$rang ++;
	$cmd->lines[] = $line;
	foreach ( $interne as $key => $values ) {
		$line = new OrderLine($db);
		$line->subprice = $values['price'];
		$line->qty = 1;
		$line->tva_tx = 0;
		$line->fk_product = $values['id'];
		$line->pa_ht = $values['pa'];
		$line->desc = $values['com'];
		$line->rang = $rang;
		$rang ++;
		$cmd->lines[] = $line;
	}
	$line = new OrderLine($db);
	$line->desc = 'Sous-Total Travaux Interne';
	$line->subprice = 0;
	$line->qty = 99;
	$line->product_type = 9;
	$line->special_code = 104777;
	$line->rang = $rang;
	$rang ++;
	$cmd->lines[] = $line;

	$line = new OrderLine($db);
	$line->desc = 'Travaux Externe';
	$line->subprice = 0;
	$line->qty = 1;
	$line->product_type = 9;
	$line->special_code = 104777;
	$line->rang = $rang;
	$rang ++;
	$cmd->lines[] = $line;
	foreach ( $externe as $key => $values ) {
		$line = new OrderLine($db);
		$line->subprice = $values['price'];
		$line->qty = 1;
		$line->tva_tx = 0;
		$line->fk_product = $values['id'];
		$line->pa_ht = $values['pa'];
		$line->desc = $values['com'];
		$line->rang = $rang;
		$rang ++;
		$cmd->lines[] = $line;
	}
	$line = new OrderLine($db);
	$line->desc = 'Sous-Total Travaux Externe';
	$line->subprice = 0;
	$line->qty = 99;
	$line->product_type = 9;
	$line->special_code = 104777;
	$line->rang = $rang;
	$rang ++;
	$cmd->lines[] = $line;

	if ($targetInfoArray['surres']['value'] > 0) {
		$line = new OrderLine($db);
		$line->desc = 'Reprise VO';
		$line->subprice = 0;
		$line->qty = 1;
		$line->product_type = 9;
		$line->special_code = 104777;
		$line->rang = $rang;
		$rang ++;
		$cmd->lines[] = $line;

		$line = new OrderLine($db);
		$line->subprice = $targetInfoArray['surres']['value'];
		$line->qty = 1;
		$line->tva_tx = 0;
		$line->fk_product = $conf->global->VOLVO_SURES;
		$line->pa_ht = $targetInfoArray['surres']['value'];
		$line->rang = $rang;
		$rang ++;
		$cmd->lines[] = $line;

		$line = new OrderLine($db);
		$line->desc = 'Sous-Total Reprise VO';
		$line->subprice = 0;
		$line->qty = 99;
		$line->product_type = 9;
		$line->special_code = 104777;
		$line->rang = $rang;
		$rang ++;
		$cmd->lines[] = $line;
	}

	$line = new OrderLine($db);
	$line->desc = 'Divers';
	$line->subprice = 0;
	$line->qty = 1;
	$line->product_type = 9;
	$line->special_code = 104777;
	$line->rang = $rang;
	$rang ++;
	$cmd->lines[] = $line;

	if (! empty($targetInfoArray['VCM_label']['value']) || $targetInfoArray['VCM_label'] != 'Aucune') {

		$res = $product->fetch('', '', $targetInfoArray['VCM_label']['value']);
		if ($res > 0) {
			$line = new OrderLine($db);
			$line->subprice = $targetInfoArray['VCM']['value'];
			$line->qty = 1;
			$line->tva_tx = 0;
			$line->fk_product = $product->id;
			$line->pa_ht = $targetInfoArray['VCM']['value'];
			$line->rang = $rang;
			$rang ++;
			$cmd->lines[] = $line;
		}
	}

	$line = new OrderLine($db);
	$line->desc = 'Sous-Total Divers';
	$line->subprice = 0;
	$line->qty = 99;
	$line->product_type = 9;
	$line->special_code = 104777;
	$line->rang = $rang;
	$rang ++;
	$cmd->lines[] = $line;

	$line = new OrderLine($db);
	$line->desc = 'Commission Volvo';
	$line->subprice = 0;
	$line->qty = 1;
	$line->product_type = 9;
	$line->special_code = 104777;
	$line->rang = $rang;
	$rang ++;
	$cmd->lines[] = $line;

	$line = new OrderLine($db);
	$line->subprice = $targetInfoArray['comission']['value'];
	$line->qty = 1;
	$line->tva_tx = 0;
	$line->fk_product = $conf->global->VOLVO_COM;
	$line->pa_ht = 0;
	$line->rang = $rang;
	$rang ++;
	$cmd->lines[] = $line;

	$line = new OrderLine($db);
	$line->desc = 'Sous-Total Commission Volvo';
	$line->subprice = 0;
	$line->qty = 99;
	$line->product_type = 9;
	$line->special_code = 104777;
	$line->rang = $rang;
	$rang ++;
	$cmd->lines[] = $line;
	//var_dump($cmd);
	//exit;
	$idcommande = $cmd->create($user);
	if ($idcommande < 0) {
		setEventMessages(null, array(
				$cmd->error
		), 'errors');
	} else {

		$result = $objectdet->add_object_linked("commande", $idcommande);
		if ($result == 0) {
			setEventMessages(null, array(
					$objectdet->error
			), 'errors');
		}

		$objectdet->fk_commande = $idcommande;
		$res = $objectdet->update($user);
		if ($res < 0) {
			setEventMessages(null, $objectdet->errors, 'errors');
		}

		if ($res < 0) {
			setEventMessage($objectdet->errors, 'errors');
		} else {
			top_htmlhead('', '');
			print '<script type="text/javascript">' . "\n";
			print '	$(document).ready(function () {' . "\n";
			print '	window.parent.$(\'#popCreateOrder\').dialog(\'close\');' . "\n";
			print '	window.parent.$(\'#popCreateOrder\').remove();' . "\n";
			print '});' . "\n";
			print '</script>' . "\n";
			llxFooter();
			exit();
		}
	}
}

if ($step == 2 && $action == 'sendit') {

	if (GETPOST('sendit') && ! empty($conf->global->MAIN_UPLOAD_DOC)) {
		$nowyearmonth = dol_print_date(dol_now(), '%Y%m%d%H%M%S');

		$fullpath = $dir . "/" . $nowyearmonth . '-' . $_FILES['userfile']['name'];
		if (dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $fullpath, 1) > 0) {
			dol_syslog("File " . $fullpath . " was added for import", LOG_DEBUG);
		} else {
			$langs->load("errors");
			setEventMessage($langs->trans("Missingfile"), 'errors');
			setEventMessage($langs->trans("ErrorFailedToSaveFile"), 'errors');
		}
	}
}

// Delete file
if ($action == 'confirm_deletefile' && $confirm == 'yes') {
	$langs->load("other");
	$file = $dir . '/' . $urlfile; // Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
	$ret = dol_delete_file($file);
	if ($ret) {
		setEventMessage($langs->trans("FileWasRemoved", $urlfile));
	} else {
		setEventMessage($langs->trans("ErrorFailToDeleteFile", $urlfile), 'errors');
	}
	Header('Location: ' . $_SERVER["PHP_SELF"] . '?step=1&vehid='.$objectdet->id);
	exit();
}

if ($step == 3 && $action == 'choosetabs') {

	$error = 0;
	$model = GETPOST('model', 'int');
	$tab_to_treat = GETPOST('tab_to_treat', 'alpha');

	$importobject->initFile($dir . '/' . $filetoimport, 'om');
	$importobject->model = $model;

	$result = $importobject->loadFile();
	if ($result < 0) {
		setEventMessages(null, $importobject->errors, 'errors');
		$error ++;
	}

	if (empty($error)) {
		$result = $importobject->setActivWorksheet($tab_to_treat);
		if ($result < O) {
			setEventMessages(null, $importobject->errors, 'errors');
			$error ++;
		}
	}
	if (empty($error)) {
		$result = $importobject->loadData();
		if ($result < O) {
			setEventMessages(null, $importobject->errors, 'errors');
			$error ++;
		}
	}

	if (empty($error)) {
		$step = '4';
		$action = 'viewtempdata';
	} else {
		$action = 'choosetabs';
	}
}

top_htmlhead('', '');
$var = ! $var;

if ($step == 1 || $step == 2) {

	/*
	 * Confirm delete file
	 */
	if ($action == 'delete') {
		$ret = $form->formconfirm($_SERVER["PHP_SELF"] . '?vehid='.$objectdet->id.'&urlfile=' . urlencode(GETPOST('urlfile')), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
		print $ret;
	}
	print_fiche_titre('Selection de la FDD a importer');
	print '<form name="userfile" action="' . $_SERVER["PHP_SELF"] . '" enctype="multipart/form-data" METHOD="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="max_file_size" value="' . $conf->maxfilesize . '">';
	print '<input type="hidden" name="vehid" value="' . $objectdet->id . '">';
	print '<input type="hidden" value="2" name="step">';
	print '<input type="hidden" value="sendit" name="action">';
	print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

	$filetoimport = '';
	$var = true;

	print '<tr><td colspan="6">' . $langs->trans("ChooseFileToImport", img_picto('', 'filenew')) . '</td></tr>';
	print '<tr><td colspan="6">' . $langs->trans("VolvoSampleFile") . ': <a href="sample/immat.xlsx">' . img_picto('', 'file') . '</a></td></tr>';

	print '<tr class="liste_titre"><td colspan="6">' . $langs->trans("FileWithDataToImport") . '</td></tr>';

	// Input file name box
	$var = false;
	print '<tr ' . $bc[$var] . '><td colspan="6">';
	print '<input type="file"   name="userfile" size="20" maxlength="80"> &nbsp; &nbsp; ';
	print '<input type="submit" class="button" value="' . $langs->trans("AddFile") . '" name="sendit">';

	print "</tr>\n";

	// Search available imports
	$filearray = dol_dir_list($dir, 'files', 0, '', '', 'name', SORT_DESC);
	if (count($filearray) > 0) {
		// Search available files to import
		$i = 0;
		foreach ( $filearray as $key => $val ) {
			$file = $val['name'];

			// readdir return value in ISO and we want UTF8 in memory
			if (! utf8_check($file))
				$file = utf8_encode($file);

			if (preg_match('/^\./', $file))
				continue;

			$modulepart = 'volvo';
			$urlsource = $_SERVER["PHP_SELF"] . '?step=' . $step . '&filetoimport=' . urlencode($filetoimport);
			$relativepath = $file;
			$var = ! $var;
			print '<tr ' . $bc[$var] . '>';
			print '<td width="16">' . img_mime($file) . '</td>';
			print '<td>';
			print '<a href="' . DOL_URL_ROOT . '/document.php?modulepart=' . $modulepart . '&file=' . urlencode('import/immat/' . $relativepath) . '" target="_blank">';
			print $file;
			print '</a>';
			print '</td>';
			// Affiche taille fichier
			print '<td align="right">' . dol_print_size(dol_filesize($dir . '/' . $file)) . '</td>';
			// Affiche date fichier
			print '<td align="right">' . dol_print_date(dol_filemtime($dir . '/' . $file), 'dayhour') . '</td>';
			// Del button
			print '<td align="right">';
			if ($user->admin) {
				print '<a href="' . $_SERVER['PHP_SELF'] . '?action=delete&vehid='.$objectdet->id.'&step=2' . '&urlfile=' . urlencode($relativepath);
				print '">' . img_delete() . '</a>';
			}
			print '</td>';
			// Action button
			print '<td align="right">';
			print '<a href="' . $_SERVER['PHP_SELF'] . '?step=3&filetoimport=' . urlencode($relativepath) . '&vehid=' . $objectdet->id . '">' . img_picto($langs->trans("NewImport"), 'filenew') . '</a>';
			print '</td>';
			print '</tr>';
		}
	}

	print '</table></form>';
}

if ($step == 3) {

	print_fiche_titre("Selectiond du modele de FDD et de l'onglet contenant les donnés");

	print '<b>' . $langs->trans("VolvoChooseExcelTabs") . '</b>';

	print '<form name="userfile" action="' . $_SERVER["PHP_SELF"] . '" METHOD="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" value="3" name="step">';
	print '<input type="hidden" name="vehid" value="' . $objectdet->id . '">';
	print '<input type="hidden" value="' . $filetoimport . '" name="filetoimport">';
	print '<input type="hidden" value="choosetabs" name="action">';
	print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

	print '<table width="100%" cellspacing="0" cellpadding="4" class="border">';

	print '<tr>';
	print '<td class="fieldrequired">' . $langs->trans('VolvoTabsAvailable') . '</td>';
	print '<td>' . $html_volvo->select_model('model', empty($tab_to_treat) ? 'A1' : $model) . '</td>';
	print '</tr>';

	print '<tr>';
	print '<td class="fieldrequired">' . $langs->trans('VolvoTabsAvailable') . '</td>';
	print '<td>' . $html_volvo->select_tabs($dir . '/' . $filetoimport, 'tab_to_treat', $tab_to_treat) . '</td>';
	print '</tr>';

	print '</table>';

	print '<table witdh="100%"><tr>';
	print '<td style="text-align:center"><input type="submit" class="button" value="' . $langs->trans("VolvoStartLoadFile") . '" name="sendit"></td>';
	print '</tr></table>';
	print '</form>';
}

if ($step == 4) {

	print_fiche_titre("Revue et validation des données importée");

	print '<form name="createorder" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="vehid" value="' . $objectdet->id . '">';
	print '<input type="hidden" value="5" name="step">';
	print '<input type="hidden" name="targetInfoArray" value="' . htmlspecialchars(json_encode($importobject->targetInfoArray)) . '">';
	print '<table class="border" width="100%">';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" colspan="2">Données générale</td>';
	print '<td class="liste_titre" colspan="2">Données financières</td>';
	print '<td class="liste_titre" colspan="2">Provisions</td>';
	print '</tr>';
	print '<tr>';
	print '<td>Client</td>';
	print '<td>' . $importobject->targetInfoArray['client']['value'] . '</td>';
	print '<td>Prix de vente</td>';
	print '<td>' . $importobject->targetInfoArray['price']['value'] . '</td>';
	print '<td>' . $importobject->targetInfoArray['prov1_label']['value'] . '</td>';
	print '<td>' . $importobject->targetInfoArray['prov1']['value'] . '</td>';
	print '</tr>';
	print '<tr>';
	print '<td>N° CDB</td>';
	print '<td>' . $importobject->targetInfoArray['cdb']['value'] . '</td>';
	print '<td>VNC</td>';
	print '<td>' . $importobject->targetInfoArray['VNC']['value'] . '</td>';
	print '<td>' . $importobject->targetInfoArray['prov2_label']['value'] . '</td>';
	print '<td>' . $importobject->targetInfoArray['prov2']['value'] . '</td>';
	print '</tr>';
	print '<tr>';
	print '<td>Spec.</td>';
	print '<td>' . $importobject->targetInfoArray['vss']['value'] . '</td>';
	print '<td>Commission</td>';
	print '<td>' . $importobject->targetInfoArray['comission']['value'] . '</td>';
	print '<td>' . $importobject->targetInfoArray['prov3_label']['value'] . '</td>';
	print '<td>' . $importobject->targetInfoArray['prov3']['value'] . '</td>';
	print '</tr>';
	print '<tr>';
	print '<td>Modele</td>';
	print '<td>' . $importobject->targetInfoArray['modele']['value'] . '</td>';
	print '<td>Commission flotte coordonnée</td>';
	print '<td>' . $importobject->targetInfoArray['flotte']['value'] . '</td>';
	print '<td>' . $importobject->targetInfoArray['prov4_label']['value'] . '</td>';
	print '<td>' . $importobject->targetInfoArray['prov4']['value'] . '</td>';
	print '</tr>';
	print '<tr>';
	print '<td></td>';
	print '<td></td>';
	print '<td>VNAC</td>';
	print '<td>' . $importobject->targetInfoArray['VNAC']['value'] . '</td>';
	print '<td>' . $importobject->targetInfoArray['prov5_label']['value'] . '</td>';
	print '<td>' . $importobject->targetInfoArray['prov5']['value'] . '</td>';
	print '</tr>';
	print '<tr>';
	print '<td>Transport</td>';
	print '<td>' . $importobject->targetInfoArray['transport']['value'] . '</td>';
	print '<td>Taxe Pneumatiques</td>';
	print '<td>' . $importobject->targetInfoArray['pneu']['value'] . '</td>';
	print '<td>' . $importobject->targetInfoArray['VCM_label']['value'] . '</td>';
	print '<td>' . $importobject->targetInfoArray['VCM']['value'] . '</td>';
	print '</tr>';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" colspan="6">Surrestimation VO</td>';
	print '</tr>';
	print '<tr>';
	print '<td>Offre VO Volvo</td>';
	print '<td>' . $importobject->targetInfoArray['rachat']['value'] . '</td>';
	print '<td>Valeur rachat VO</td>';
	print '<td>' . $importobject->targetInfoArray['rachat']['value'] . '</td>';
	print '<td>surrestimation</td>';
	print '<td>' . $importobject->targetInfoArray['surres']['value'] . '</td>';
	print '</tr>';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" colspan="2">Travaux Internes</td>';
	print '<td class="liste_titre" colspan="2">Travaux externes</td>';
	print '<td class="liste_titre" colspan="2">Couts Locaux</td>';
	print '</tr>';
	for($i = 1; $i <= 8; $i ++) {
		print '<tr>';
		print '<td>' . $importobject->targetInfoArray['interne' . $i . '_label']['value'] . '</td>';
		print '<td>' . $importobject->targetInfoArray['interne' . $i]['value'] . '</td>';
		print '<td>' . $importobject->targetInfoArray['externe' . $i . '_label']['value'] . '</td>';
		print '<td>' . $importobject->targetInfoArray['externe' . $i]['value'] . '</td>';
		print '<td>' . $importobject->targetInfoArray['local' . $i . '_label']['value'] . '</td>';
		print '<td>' . $importobject->targetInfoArray['local' . $i]['value'] . '</td>';
		print '</tr>';
	}
	print '</table>';
	print '<div class="tabsAction">';
	print '<input type="submit" align="center" class="button" value="Continuer" name="save" id="save"/>';
	print '</div>';
	print '</form>';
}
if ($step == 5) {

	print_fiche_titre("Mise en correspondance des données et Articles Dolibarr");

	$targetInfoArray = json_decode(GETPOST('targetInfoArray'), true);
	// var_dump($targetInfoArray);
	print '<form name="createorder" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="vehid" value="' . $objectdet->id . '">';
	print '<input type="hidden" value="6" name="step">';
	print '<input type="hidden" name="targetInfoArray" value="' . htmlspecialchars(json_encode($targetInfoArray)) . '">';
	print '<table class="border" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="6" align="center"> Création de la commande</td>';
	print '</tr>';
	print '<tr>';
	print '<td colspan="6">';
	print '<table width="100%" class="nobordernopadding">';
	print '<tr ' . $bc[$var] . '>';
	print '<td align="center">' . $langs->trans('Date de livraison souhaitée') . ': ' . $form->select_date('', 'datelivprev_', 0, 0, 1, '', 1, 1, 1, 0, '', '', '') . '</td>';
	print '</tr>';
	print '</table>';
	print '</tr>';
	print '<tr class="liste_titre">';
	print '<td colspan="6" align="center">Liste des Articles trouvés dans la FDD</td>';
	print '</tr>';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">Ne pas traiter</td>';
	print '<td class="liste_titre">Article</td>';
	print '<td class="liste_titre">Désignation FDD</td>';
	print '<td class="liste_titre">Prix de vente</td>';
	print '<td class="liste_titre">Prix d\'achat</td>';
	print '<td class="liste_titre">Commentaire</td>';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" colspan="6" align="center"> Travaux Interne </td>';
	print '</tr>';
	for($i = 1; $i <= 6; $i ++) {
		if (! empty($targetInfoArray['interne' . $i . '_label']['value']) && ! empty($targetInfoArray['interne' . $i]['value'])) {
			print '<tr>';
			print '<td><input class="flat" type="checkbox" align="left" name="interne[' . $i . '][npt]"/>';
			print '<td>';
			$form->select_produits(0, 'interne_product' . $i, '', '', '', 1, 2, '', 0, array(), '');
			print '</td>';
			print '<td>' . $targetInfoArray['interne' . $i . '_label']['value'] . '</td>';
			print '<td>' . price($targetInfoArray['interne' . $i]['value']) . ' €   <input type="hidden" name="interne[' . $i . '][price]" value="' . $targetInfoArray['interne' . $i]['value'] . '"></td>';
			print '<td><input type="text" name="interne[' . $i . '][pa]" size="7" value="' . price($targetInfoArray['interne' . $i]['value']) . '"/> €</td>';
			print '<td><input type="text" name="interne[' . $i . '][com]" size="20" value="' . $targetInfoArray['interne' . $i . '_label']['value'] . '"/></td>';
			print '</tr>';
		}
	}
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" colspan="6" align="center"> Travaux Externe </td>';
	print '</tr>';
	for($i = 1; $i <= 3; $i ++) {
		if (! empty($targetInfoArray['externe' . $i . '_label']['value']) && ! empty($targetInfoArray['externe' . $i]['value'])) {
			print '<tr>';
			print '<td><input class="flat" type="checkbox" align="left" name="externe[' . $i . '][npt]"/>';
			print '<td>';
			$form->select_produits(0, "externe_product" . $i, '', '', '', 1, 2, '', 0, array(), '');
			print '</td>';
			print '<td>' . $targetInfoArray['externe' . $i . '_label']['value'] . '</td>';
			print '<td>' . price($targetInfoArray['externe' . $i]['value']) . ' €   <input type="hidden" name="externe[' . $i . '][price]" value="' . $targetInfoArray['externe' . $i]['value'] . '"></td>';
			print '<td><input type="text" name="externe[' . $i . '][pa]" size="7" value="' . price($targetInfoArray['externe' . $i]['value']) . '"/> €</td>';
			print '<td><input type="text" name="externe[' . $i . '][comm]" size="20" value="' . $targetInfoArray['externe' . $i . '_label']['value'] . '"/></td>';
			print '</tr>';
		}
	}
	for($i = 7; $i <= 8; $i ++) {
		if (! empty($targetInfoArray['externe' . $i . '_label']['value']) && ! empty($targetInfoArray['externe' . $i]['value'])) {
			print '<tr>';
			print '<td><input class="flat" type="checkbox" align="left" name="externe[' . $i . '][npt]"/>';
			print '<td>';
			$form->select_produits(0, "externe_product" . $i, '', '', '', 1, 2, '', 0, array(), '');
			print '</td>';
			print '<td>' . $targetInfoArray['externe' . $i . '_label']['value'] . '</td>';
			print '<td>' . price($targetInfoArray['externe' . $i]['value']) . ' €   <input type="hidden" name="externe[' . $i . '][price]" value="' . $targetInfoArray['externe' . $i]['value'] . '"></td>';
			print '<td><input type="text" name="externe[' . $i . '][pa]" size="7" value="' . price($targetInfoArray['externe' . $i]['value']) . '"/> €</td>';
			print '<td><input type="text" name="externe[' . $i . '][com]" size="20" value="' . $targetInfoArray['externe' . $i . '_label']['value'] . '"/></td>';
			print '</tr>';
		}
	}
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" colspan="6" align="center"> Couts Locaux </td>';
	print '</tr>';
	for($i = 1; $i <= 8; $i ++) {
		if (! empty($targetInfoArray['local' . $i . '_label']['value']) && ! empty($targetInfoArray['local' . $i]['value'])) {
			$pos = $i + 6;
			print '<tr>';
			print '<td><input class="flat" type="checkbox" align="left" name="interne[' . $pos . '][npt]"/>';
			print '<td>';
			$form->select_produits(0, "interne_product" . $pos, '', '', '', 1, 2, '', 0, array(), '');
			print '</td>';
			print '<td>' . $targetInfoArray['local' . $i . '_label']['value'] . '</td>';
			print '<td>' . price($targetInfoArray['local' . $i]['value']) . ' €   <input type="hidden" name="interne[' . $pos . '][price]" value="' . $targetInfoArray['local' . $i]['value'] . '"></td>';
			print '<td><input type="text" name="interne[' . $pos . '][pa]" size="7" value="' . price($targetInfoArray['local' . $i]['value']) . '"/> €</td>';
			print '<td><input type="text" name="interne[' . $pos . '][com]" size="20" value="' . $targetInfoArray['local' . $i . '_label']['value'] . '"/></td>';
			print '</tr>';
		}
	}
	print '</table>';
	print '<div class="tabsAction">';
	print '<input type="submit" align="center" class="button" value="Créer la commande" name="save" id="save"/>';
	print '</div>';
	print '</form>';
}
// if ($step==6){
// print_fiche_titre("Résultat");

// var_dump($_POST);

// }

?>
<script type="text/javascript" language="javascript">
function visibilite(thingId) {
	var targetElement;
	targetElement = document.getElementById(thingId) ;
	if (targetElement.style.display == "none") {
		targetElement.style.display = "" ;
	} else {
		targetElement.style.display = "none" ;
	}
}
</script>
<?php
llxFooter();
$db->close();
?>

