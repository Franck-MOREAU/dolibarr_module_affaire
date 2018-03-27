<?php
/* Volvo
 * Copyright (C) 2015  Florian HENRY <florian.henry@open-concept.pro>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once '../class/volvoimportfdd.class.php';
require_once '../class/html.formvolvo.class.php';

if (! $user->rights->volvo->om)
	accessforbidden();

$langs->load("exports");
$langs->load("errors");
$langs->load('volvo@volvo');

$datatoimport = GETPOST('datatoimport');
$step = GETPOST('step', 'int');
$action = GETPOST('action', 'alpha');
$todo = GETPOST('todo', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$urlfile = GETPOST('urlfile');
$filetoimport = GETPOST('filetoimport');

$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;

$importobject = new VolvoImportfdd($db);

$dir = $conf->volvo->dir_output . '/import/fdd';

if($todo == 'set_numon'){
	foreach ( $_POST as $key => $data ) {
		if (strpos($key, 'cmd_line_') !== false) {
			if ($data != - 1) {
				$cmdarray[$data] = str_replace('cmd_line_', '', $key);
			}
		}
	}
	var_dump($cmdarray);
	foreach ($cmdarray as $key=>$value){
		$importobject->setnumom($key,$value);
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
	if ($ret)
		setEventMessage($langs->trans("FileWasRemoved", $urlfile));
	else
		setEventMessage($langs->trans("ErrorFailToDeleteFile", $urlfile), 'errors');
	Header('Location: ' . $_SERVER["PHP_SELF"] . '?step=1');
	exit();
}

if ($step == 3 && $action == 'choosetabs') {

	$error = 0;

	$tab_to_treat = GETPOST('tab_to_treat', 'alpha');
	$startcell = GETPOST('startcell', 'alpha');

	$importobject->initFile($dir . '/' . $filetoimport, 'om');

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
// 	if (empty($error)) {
// 		$result = $importobject->checkTabAndCell($tab_to_treat, $startcell);
// 		if ($result < O) {
// 			setEventMessages(null, $importobject->errors, 'errors');
// 			$error ++;
// 		}
// 	}

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



$title = $langs->trans('VolvoImport') . '- Import OM';

llxHeader('', $title);

dol_fiche_head($head, 'business', $title, 0, 'iron02@volvo');

$form = new Form($db);
$html_volvo = new FormVolvo($db);

if ($step == 1 || $step == 2) {

	/*
	 * Confirm delete file
	 */
	if ($action == 'delete') {
		$ret = $form->form_confirm($_SERVER["PHP_SELF"] . '?urlfile=' . urlencode(GETPOST('urlfile')) . $param, $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
	}

	print '<form name="userfile" action="' . $_SERVER["PHP_SELF"] . '" enctype="multipart/form-data" METHOD="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="max_file_size" value="' . $conf->maxfilesize . '">';
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
			$urlsource = $_SERVER["PHP_SELF"] . '?step=' . $step . $param . '&filetoimport=' . urlencode($filetoimport);
			$relativepath = $file;
			$var = ! $var;
			print '<tr ' . $bc[$var] . '>';
			print '<td width="16">' . img_mime($file) . '</td>';
			print '<td>';
			print '<a href="' . DOL_URL_ROOT . '/document.php?modulepart=' . $modulepart . '&file=' . urlencode('import/immat/' . $relativepath) . $param . '" target="_blank">';
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
				print '<a href="' . $_SERVER['PHP_SELF'] . '?action=delete&step=2' . $param . '&urlfile=' . urlencode($relativepath);
				print '">' . img_delete() . '</a>';
			}
			print '</td>';
			// Action button
			print '<td align="right">';
			print '<a href="' . $_SERVER['PHP_SELF'] . '?step=3' . $param . '&filetoimport=' . urlencode($relativepath) . '">' . img_picto($langs->trans("NewImport"), 'filenew') . '</a>';
			print '</td>';
			print '</tr>';
		}
	}

	print '</table></form>';
}

// Check data file look like
if ($step == 3) {

	print_fiche_titre($langs->trans("InformationOnSourceFile") . ' : ' . $filetoimport);

	print '<b>' . $langs->trans("VolvoChooseExcelTabs") . '</b>';

	print '<form name="userfile" action="' . $_SERVER["PHP_SELF"] . '" METHOD="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" value="3" name="step">';
	print '<input type="hidden" value="' . $filetoimport . '" name="filetoimport">';
	print '<input type="hidden" value="choosetabs" name="action">';
	print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

	print '<table width="100%" cellspacing="0" cellpadding="4" class="border">';
	print '<tr>';

	print '<td class="fieldrequired">' . $langs->trans('VolvoTabsAvailable') . '</td>';
	print '<td>' . $html_volvo->select_tabs($dir . '/' . $filetoimport, 'tab_to_treat', empty($tab_to_treat) ? 'A1' : $tab_to_treat) . '</td>';
	print '</tr>';
	print '<tr>';

	print '<td class="fieldrequired">' . $langs->trans('VolvoStartSelectCells', 'A1') . '</td>';
	print '<td><input type="text" size="4" class="flat" name="startcell" id="startcell" value="' . (empty($startcell) ? 'A1' : $startcell) . '"/></td>';
	print '</tr>';
	print '</table>';

	print '<table witdh="100%"><tr>';
	print '<td style="text-align:center"><input type="submit" class="button" value="' . $langs->trans("VolvoStartLoadFile") . '" name="sendit"></td>';
	print '</tr></table>';
	print '</form>';

	dol_fiche_end();
}

if ($step == 4 && $action == 'viewtempdata') {

	print_fiche_titre($langs->trans("InformationOnSourceFile") . ' : ' . $filetoimport);

	print '<table width="100%" cellspacing="0" cellpadding="4" class="border">';
	print '<tr class="liste_titre">';
	foreach ( $importobject->columnArray as $column ) {
		print '<td>' . $column['label'] . '</td>';
	}
	print '</tr>';

	$result = $importobject->fetchAllTempTable('', '', 10);
	if ($result < 0) {
		setEventMessages(null, $importobject->errors, 'errors');
	} else {
		foreach ( $importobject->lines as $line ) {
			print '<tr>';
			foreach ( $line as $key => $data )
				if ($key != 'rowid') {
					print '<td>' . $data . '</td>';
				}
			print '</tr>';
		}
	}

	print '</table>';

	dol_fiche_end();

	// Select colmun affactation
	print_fiche_titre($langs->trans("VolvoMatchData"));
	print '<form name="userfile" action="' . $_SERVER["PHP_SELF"] . '" METHOD="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" value="5" name="step">';
	print '<input type="hidden" value="' . $filetoimport . '" name="filetoimport">';
	print '<input type="hidden" value="checkdata" name="action">';
	print '<input type="hidden" value="' . dol_htmlentities(json_encode($importobject->columnArray), ENT_COMPAT) . '" name="columnArray">';
	print '<table cellspacing="0" cellpadding="4" class="border">';
	$var = true;
	$i = 0;
	foreach ( $importobject->targetInfoArray as $key => $column ) {

		if ($i % 3 == 0) {
			$var = ! $var;
			// print 'erzear='.$i.'<BR>';
			// print 'erzer='.$i % 3;
			print '<tr ' . $bc[$var] . '>';
		}

		if (array_key_exists('column', $column) && ! array_key_exists('unselectable', $column)) {
			print '<td>';
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			print '</td>';

			print '<td class="fieldrequired">';
			print $column['columntrans'] . '(' . $column['tabletrans'] . ')';
			print '</td>';
			print '<td>' . $html_volvo->select_src_column($key, $column, $importobject->columnArray) . '</td>';

			$i ++;
		}
		if ($i % 3 == 0) {
			print '</tr>';
		}
	}

	if ($i % 3 != 0) {
		print '</tr>';
	}

	print '</table>';

	print '<table witdh="100%"><tr>';
	print '<td style="text-align:center"><input type="submit" class="button" value="' . $langs->trans("VolvoNextStep") . '" name="checkdata"></td>';
	print '</tr></table>';
	print '</form>';
}



llxFooter();
$db->close();
