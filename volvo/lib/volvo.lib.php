<?php
function commande_prepare_head_custom(Commande $object)
{
	global $db, $langs, $conf, $user;
	if (! empty($conf->expedition->enabled)) $langs->load("sendings");
	$langs->load("orders");

	$h = 0;
	$head = array();

	if (! empty($conf->commande->enabled) && $user->rights->commande->lire)
	{
		$head[$h][0] = DOL_URL_ROOT.'/commande/card.php?id='.$object->id;
		$head[$h][1] = $langs->trans("OrderCard");
		$head[$h][2] = 'order';
		$h++;
	}

	$ok = volvo_vcm_ok($object);
	$img =img_picto('','on.png@affaires');
	if($ok>1 ||$ok<0) $img = img_picto('','off.png@affaires');
	$head[$h][0] = dol_buildpath('/affaires/volvo/commande/vcm.php',2).'?id='.$object->id;
	$head[$h][1] = 'VCM' . ' <span class="badge">'.$img .'</span>' ;
	$head[$h][2] = 'vcm';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->commande->dir_output . "/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview\.png)$'));
	$nbLinks=Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath('/affaires/volvo/commande/document.php',2).'?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles+$nbLinks) > 0) $head[$h][1].= ' <span class="badge">'.($nbFiles+$nbLinks).'</span>';
	$head[$h][2] = 'documents';
	$h++;

	//complete_head_from_modules($conf,$langs,$object,$head,$h,'order');

	$head[$h][0] = dol_buildpath('/affaires/volvo/commande/info.php',2).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf,$langs,$object,$head,$h,'order','remove');

	return $head;
}

function volvo_vcm_ok($object) {
	global $conf,$user;
	$res =  1;
	if(empty($object->array_options['options_vcm_site'])) $res = 2;
	if(empty($object->array_options['options_vcm_dt_dem'])) $res =  3;
	if(empty($object->array_options['options_vcm_duree'])) $res =  4;
	if(empty($object->array_options['options_vcm_km'])) $res =  5;
	if(empty($object->array_options['options_vcm_ptra'])) $res =  6;
	if(empty($object->array_options['options_vcm_chant']) && empty($object->array_options['options_vcm_50km'])
			&& empty($object->array_options['options_vcm_ld']) && empty($object->array_options['options_vcm_ville'])) $res =  7;
			if(empty($object->array_options['options_vcm_zone'])) $res =  8;
			if(empty($object->array_options['options_vcm_typ_trans'])) $res =  9;
			if(empty($object->array_options['options_vcm_roul'])) $res =  10;
			if(empty($object->array_options['options_vcm_topo'])) $res =  11;
			if(!empty($object->array_options['options_vcm_pto']) && empty($object->array_options['options_vcm_pto_nbh'])) $res =  12;
			if(!empty($object->array_options['options_vcm_frigo']) &&
					(!empty($object->array_options['options_vcm_blue']) || !empty($object->array_options['options_vcm_silver'])
							|| !empty($object->array_options['options_vcm_silverp']) || !empty($object->array_options['options_vcm_gold']))){
								if(empty($object->array_options['options_vcm_marque'])) $res =  13;
								if(empty($object->array_options['options_vcm_model'])) $res =  14;
								if(empty($object->array_options['options_vcm_fonct'])) $res =  15;
								if(empty($object->array_options['options_vcm_frigo_nbh'])) $res =  16;
			}

			if(($user->admin || $user->rights->volvo->update_cost || $conf->global->VOLVO_VCM_OBLIG == 0) && $res>1) return -1*$res;
			if($res == 1) return $res;
			if($res > 1) return $res;
}

function volvo_vcm_motif($code) {
	global $conf,$user;
	if($code<0) $code =-1*$code;

	$motif = 'Saisie Valide';


	switch($code){
		case 2:
			$motif = 'Point de service absent ou non valide';
			break;
		case 3:
			$motif = 'Date de début absente ou non valide';
			break;
		case 4:
			$motif = 'Durée absente ou non valide';
			break;
		case 5:
			$motif = 'Kilométrage annuel absent ou non valide';
			break;
		case 6:
			$motif = 'poid total roulant constaté absent ou non valide';
			break;
		case 7:
			$motif = 'paramètres de calcul du cycle de transport absent ou non valide';
			break;
		case 8:
			$motif = 'zone géographique absente ou non valide';
			break;
		case 9:
			$motif = 'type de transport absent ou non valide';
			break;
		case 10:
			$motif = 'condition de roulage absente ou non valide';
			break;
		case 11:
			$motif = 'topographie absente ou non valide';
			break;
		case 12:
			$motif = "PTO selectionnée, mais nombre d'heures annuelle d'utilisation absente ou non valide";
			break;
		case 13:
			$motif = 'Entretien groupe frigo sélectionné, mais marque du groupe absente ou non valide';
			break;
		case 14:
			$motif = 'Entretien groupe frigo sélectionné, mais Modèle du groupe absente ou non valide';
			break;
		case 15:
			$motif = 'Entretien groupe frigo sélectionné, mais mode de fonctionnement du groupe absente ou non valide';
			break;
		case 16:
			$motif = 'Entretien groupe frigo sélectionné, mais durée annuelle de fonctionnement absente ou non valide';
			break;
	}

	return $motif;
}

Function print_extra($key,$type,$action,$extrafields,$object,$label=1,$lenght = 10,$unit=''){
	global $db;

	$out = '<div style="display: inline" align ="left">';

	if($label==1)$out.= $extrafields->attribute_label[$key];

	if($type=='yesno'){
		require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
		$form = new Form($db);
		if ($action == 'edit_extra' && GETPOST('attribute') == $key) {
			$out.= '<form enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . '" method="post" name="formextra">';
			$out.= '<input type="hidden" name="action" value="update_extras">';
			$out.= '<input type="hidden" name="attribute" value="'. $key .'">';
			$out.= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
			$out.= '<input type="hidden" name="id" value="' . $object->id . '">';
			$out.= $form->selectyesno('options_'.$key,$object->array_options['options_'.$key],1);
			$out.= '<input type="submit" class="button" value="Modifier">';
			$out.= '</form>';
		} else {
			$out.= '<span style="margin-left: 1em;">';
			$out.= yn($object->array_options['options_'.$key]);
			$out.= '</span><span style="margin-left: 1em;"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_extra&attribute=' .$key . '&id=' . $object->id . '">' . img_edit('') . '</a></span>';
		}
	}

	if($type=='chkbox'){
		dol_include_once('/affaires/class/html.formaffaires.class.php');
		$form = new FormAffaires($db);
		$list = $extrafields->attribute_param[$key]['options'];
		$selected = explode(',', $object->array_options['options_'.$key]);
		if ($action == 'edit_extra' && GETPOST('attribute') == $key) {
			$out.= '<form enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . '" method="post" name="formextra">';
			$out.= '<input type="hidden" name="action" value="update_extras">';
			$out.= '<input type="hidden" name="attribute" value="'. $key .'">';
			$out.= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
			$out.= '<input type="hidden" name="id" value="' . $object->id . '">';
			$out.= $form->select_withcheckbox_flat('options_'.$key,$list,$selected);
			$out.= '<input type="submit" class="button" value="Modifier">';
			$out.= '</form>';
		} else {
			foreach ($list as $cle => $value){
				if(in_array($cle, $selected)) $out.= '<span style="margin-left: 1em;">' . show_picto(1) . ' ' . $value .'</span>';
				else $out.= '<span style="margin-left: 1em;">' .show_picto(0) . ' ' . $value.'</span>';
			}
			$out.= '<span style="margin-left: 1em;"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_extra&attribute=' .$key . '&id=' . $object->id . '">' . img_edit('') . '</a></span>';
		}
	}

	if($type=='bool'){
		if ($object->array_options['options_'.$key] == 0) {
			$out.= '<span style="margin-left: 1em;">'.'<a href="' . $_SERVER["PHP_SELF"] . '?action=update_extras&options_' .$key. '=1&attribute=' .$key . '&id=' . $object->id . '">';
			$out.= img_picto('non','switch_off');
			$out.= '</a></span>';
		} else {
			$out.= '<span style="margin-left: 1em;">'.'<a href="' . $_SERVER["PHP_SELF"] . '?action=update_extras&options_' .$key. '=0&attribute=' .$key . '&id=' . $object->id . '">';
			$out.= img_picto('Oui','switch_on');
			$out.= '</a></span>';
		}
	}

	if($type=='date'){
		require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
		$form = new Form($db);
		if ($action == 'edit_extra' && GETPOST('attribute') == $key) {
			$out.= '<form enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . '" method="post" name="formextra">';
			$out.= '<input type="hidden" name="action" value="update_extras">';
			$out.= '<input type="hidden" name="attribute" value="'. $key .'">';
			$out.= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
			$out.= '<input type="hidden" name="id" value="' . $object->id . '">';
			$out.= $form->select_date($db->jdate($object->array_options['options_'.$key]),'options_'.$key,0,0,1,'',1,1,1);
			$out.= '<input type="submit" class="button" value="Modifier">';
			$out.= '</form>';
		} else {
			$out.= '<span style="margin-left: 1em;">';
			$out.= dol_print_date($object->array_options['options_'.$key],'daytextshort').'</span>';
			$out.= '<span style="margin-left: 1em;"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_extra&attribute=' .$key . '&id=' . $object->id . '">' . img_edit('') . '</a></span>';
		}
	}

	if($type=='text'){
		if ($action == 'edit_extra' && GETPOST('attribute') == $key) {
			$out.= '<form enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . '" method="post" name="formextra">';
			$out.= '<input type="hidden" name="action" value="update_extras">';
			$out.= '<input type="hidden" name="attribute" value="'. $key .'">';
			$out.= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
			$out.= '<input type="hidden" name="id" value="' . $object->id . '">';
			$out.= '<input type="text" name="options_' . $key . '" size="' . $lenght . '" value="' . $object->array_options['options_'.$key] . '"/>'. ' ' . $unit;
			$out.= '<input type="submit" class="button" value="Modifier">';
			$out.= '</form>';
		} else {
			$out.= '<span style="margin-left: 1em;">';
			$out.= $object->array_options['options_'.$key] . ' ' . $unit;
			$out.= '</span>';
			$out.= '<span style="margin-left: 1em;"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_extra&attribute=' .$key . '&id=' . $object->id . '">' . img_edit('') . '</a></span>';
		}
	}

	if($type=='num'){
		if ($action == 'edit_extra' && GETPOST('attribute') == $key) {
			$out.= '<form enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . '" method="post" name="formextra">';
			$out.= '<input type="hidden" name="action" value="update_extras">';
			$out.= '<input type="hidden" name="attribute" value="'. $key .'">';
			$out.= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
			$out.= '<input type="hidden" name="id" value="' . $object->id . '">';
			$out.= '<input type="text" name="options_' . $key . '" size="' . $lenght . '" value="' . price($object->array_options['options_'.$key]) . '"/>'. ' ' . $unit;
			$out.= '<input type="submit" class="button" value="Modifier">';
			$out.= '</form>';
		} else {
			$out.= '<span style="margin-left: 1em;">';
			$out.= price($object->array_options['options_'.$key]). ' ' . $unit;
			$out.= '</span>';
			$out.= '<span style="margin-left: 1em;"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_extra&attribute=' .$key . '&id=' . $object->id . '">' . img_edit('') . '</a></span>';
		}
	}

	if($type=='textlong'){
		if ($action == 'edit_extra' && GETPOST('attribute') == $key) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
			$doleditor=new DolEditor('options_'.$key,$object->array_options['options_'.$key]);
			$out.= '<form enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . '" method="post" name="formextra">';
			$out.= '<input type="hidden" name="action" value="update_extras">';
			$out.= '<input type="hidden" name="attribute" value="'. $key .'">';
			$out.= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
			$out.= '<input type="hidden" name="id" value="' . $object->id . '">';
			$out.= $doleditor->Create(1);
			$out.= '<input type="submit" class="button" value="Modifier">';
			$out.= '</form>';
		} else {
			$out.= '<span style="margin-left: 1em;">';
			$out.= dol_htmlentitiesbr($object->array_options['options_'.$key]);
			$out.= '</span>';
			$out.= '<span style="margin-left: 1em;"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_extra&attribute=' .$key . '&id=' . $object->id . '">' . img_edit('') . '</a></span>';
		}
	}

	if($type=='chkboxvert'){
		require_once DOL_DOCUMENT_ROOT . '/volvo/class/html.formvolvo.class.php';
		$form = new FormVolvo($db);
		$list = $extrafields->attribute_param[$key]['options'];
		$selected = explode(',', $object->array_options['options_'.$key]);
		$out.= '<table class="nobordernopadding" width="100%"><tr><td>';
		if ($action == 'edit_extra' && GETPOST('attribute') == $key) {
			$out.= '<form enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . '" method="post" name="formextra">';
			$out.= '<input type="hidden" name="action" value="update_extras">';
			$out.= '<input type="hidden" name="attribute" value="'. $key .'">';
			$out.= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
			$out.= '<input type="hidden" name="id" value="' . $object->id . '">';
			$out.= $form->select_withcheckbox('options_'.$key,$list,$selected);
			$out.= '<input type="submit" class="button" value="Modifier">';
			$out.= '</form>';
		} else {
			foreach ($list as $cle => $value){
				if(in_array($cle, $selected)) $out.= '<span style="margin-left: 1em;">' . show_picto(1) . ' ' . $value .'</span></br>';
				else $out.= '<span style="margin-left: 1em;">' .show_picto(0) . ' ' . $value.'</span></br>';
			}
			$out = substr($out, 0,-5);
			$out.= '</td><td>';
			$out.= '<span style="margin-left: 1em;"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_extra&attribute=' .$key . '&id=' . $object->id . '">' . img_edit('') . '</a></span>';
			$out.='</td></tr></table>';
		}
	}

	$out.= '</div>';


	return $out;
}

function show_picto($value) {
	if ($value == 1) {
		return img_picto('non', 'statut6');
	} else {
		return img_picto('non', 'statut0');
	}
}

function show_picto_pdf($value) {
	if ($value == 1) {
		return dol_buildpath('/theme/eldy/img/statut6.png');
	} else {
		return dol_buildpath('/theme/eldy/img/statut0.png');
	}
}

Function prepare_array($var,$mode){
	global $conf;

	if($mode == 'sql'){
		$outtemp = explode(',',$conf->global->$var);
		foreach ($outtemp as $value){
			$out.= "'" . $value ."',";
		}
		$out = substr($out, 0,-1);
	}elseif($mode ='array'){
		$out = explode(',',$conf->global->$var);
	}
	return $out;

}

function categchild($categ, $mode){
	if(is_array($categ)){
		$cat=$categ;
	}else{
		$cat=array($categ);
	}

	$retour = categ_child($cat);
	while (is_array($retour)&&count($retour)>0){
		$cat = array_merge($cat,$retour);
		$retour = categ_child($retour);
	}

	if($mode=='sql'){
		$txt = implode(',', $cat);
		return $txt;
	}else{
		return $cat;
	}
}

function categ_child($categ){
	global $db;

	dol_include_once('/categories/class/categorie.class.php');
	$categorie = new Categorie($db);
	$result = array();
	foreach ($categ as $cat){
		$res = $categorie->fetch($cat);
		if($res <0) exit;
		$ret= $categorie->get_filles();
		foreach ($ret as $res){
			$result[] = $res->id;
		}
	}
	return $result;
}

function product_all_categ($id,$mode){
	global $db;
	dol_include_once('/categories/class/categorie.class.php');
	$categorie = new Categorie($db);
	$categ = $categorie->getListForItem($id,'product');
	$result=array();
	if (is_array ( $categ ) && count ( $categ ) > 0) {
		foreach ( $categ as $cat ) {
			if (count ( $cat ) > 0) {
				$ret = array ();
				$ret = categparent ( $cat ['id'], 'array' );
			}
			$result = array_merge ( $result, $ret );
		}
	}

	if($mode=='sql'){
		$txt = implode(',', $result);
		return $txt;
	}else{
		return $result;
	}
}

function categparent($categ, $mode){
	$cat=array($categ);

	$retour = categ_parent($cat);
	while (is_array($retour)&&count($retour)>0){
		$cat = array_merge($cat,$retour);
		$retour = categ_parent($retour);
	}

	if($mode=='sql'){
		$txt = implode(',', $cat);
		return $txt;
	}else{
		return $cat;
	}
}

function categ_parent($categ){
	global $db;

	dol_include_once('/categories/class/categorie.class.php');
	$categorie = new Categorie($db);
	$result = array();
	foreach ($categ as $cat){
		$res = $categorie->fetch($cat);
		if($res <0) exit;
		$ret= $categorie->get_meres();
		foreach ($ret as $res){
			$result[] = $res->id;
		}
	}
	return $result;
}

function dol_banner_tab_perso($object, $paramid, $morehtml='', $shownav=1, $fieldid='rowid', $fieldref='ref', $morehtmlref='', $moreparam='', $nodbprefix=0, $morehtmlleft='', $morehtmlstatus='', $onlybanner=0, $morehtmlright='')
{
	global $conf, $form, $user, $langs;

	$error = 0;

	$maxvisiblephotos=1;
	$showimage=1;
	$showbarcode=empty($conf->barcode->enabled)?0:($object->barcode?1:0);
	if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode=0;
	$modulepart='unknown';

	if ($object->element == 'societe')         $modulepart='societe';
	if ($object->element == 'contact')         $modulepart='contact';
	if ($object->element == 'member')          $modulepart='memberphoto';
	if ($object->element == 'user')            $modulepart='userphoto';
	if ($object->element == 'product')         $modulepart='product';

	if (class_exists("Imagick"))
	{
		if ($object->element == 'propal')            $modulepart='propal';
		if ($object->element == 'commande')          $modulepart='commande';
		if ($object->element == 'facture')           $modulepart='facture';
		if ($object->element == 'fichinter')         $modulepart='ficheinter';
		if ($object->element == 'contrat')           $modulepart='contract';
		if ($object->element == 'supplier_proposal') $modulepart='supplier_proposal';
		if ($object->element == 'order_supplier')    $modulepart='supplier_order';
		if ($object->element == 'invoice_supplier')  $modulepart='supplier_invoice';
		if ($object->element == 'expensereport')     $modulepart='expensereport';
	}

	if ($object->element == 'product')
	{
		$width=80; $cssclass='photoref';
		$showimage=$object->is_photo_available($conf->product->multidir_output[$object->entity]);
		$maxvisiblephotos=(isset($conf->global->PRODUCT_MAX_VISIBLE_PHOTO)?$conf->global->PRODUCT_MAX_VISIBLE_PHOTO:5);
		if ($conf->browser->phone) $maxvisiblephotos=1;
		if ($showimage) $morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">'.$object->show_photos($conf->product->multidir_output[$object->entity],'small',$maxvisiblephotos,0,0,0,$width,0).'</div>';
		else
		{
			if (!empty($conf->global->PRODUCT_NODISPLAYIFNOPHOTO)) {
				$nophoto='';
				$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref"></div>';
			}
			//elseif ($conf->browser->layout != 'phone') {    // Show no photo link
			$nophoto='/public/theme/common/nophoto.png';
			$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref"><img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="No photo" border="0"'.($width?' width="'.$width.'"':'').' src="'.DOL_URL_ROOT.$nophoto.'"></div>';
			//}
		}
	}
	else
	{
		if ($showimage)
		{
			if ($modulepart != 'unknown')
			{
				$phototoshow='';
				// Check if a preview file is available
				if (in_array($modulepart, array('propal', 'commande', 'facture', 'ficheinter', 'contract', 'supplier_order', 'supplier_proposal', 'supplier_invoice', 'expensereport')) && class_exists("Imagick"))
				{
					$objectref = dol_sanitizeFileName($object->ref);
					$dir_output = $conf->$modulepart->dir_output . "/";
					if (in_array($modulepart, array('invoice_supplier', 'supplier_invoice')))
					{
						$subdir = get_exdir($object->id, 2, 0, 0, $object, $modulepart).$objectref;		// the objectref dir is not include into get_exdir when used with level=2, so we add it here
					}
					else
					{
						$subdir = get_exdir($object->id, 0, 0, 0, $object, $modulepart);
					}

					$filepath = $dir_output . $subdir . "/";
					$file = $filepath . $objectref . ".pdf";
					$relativepath = $subdir.'/'.$objectref.'.pdf';

					// Define path to preview pdf file (preview precompiled "file.ext" are "file.ext_preview.png")
					$fileimage = $file.'_preview.png';              // If PDF has 1 page
					$fileimagebis = $file.'_preview-0.png';         // If PDF has more than one page
					$relativepathimage = $relativepath.'_preview.png';

					// Si fichier PDF existe
					if (file_exists($file))
					{
						$encfile = urlencode($file);
						// Conversion du PDF en image png si fichier png non existant
						if ( (! file_exists($fileimage) || (filemtime($fileimage) < filemtime($file)))
								&& (! file_exists($fileimagebis) || (filemtime($fileimagebis) < filemtime($file)))
								)
						{
							if (empty($conf->global->MAIN_DISABLE_PDF_THUMBS))		// If you experienc trouble with pdf thumb generation and imagick, you can disable here.
							{
								$ret = dol_convert_file($file, 'png', $fileimage);
								if ($ret < 0) $error++;
							}
						}

						$heightforphotref=70;
						if (! empty($conf->dol_optimize_smallscreen)) $heightforphotref=60;
						// Si fichier png PDF d'1 page trouve
						if (file_exists($fileimage))
						{
							$phototoshow = '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref">';
							$phototoshow.= '<img height="'.$heightforphotref.'" class="photo photowithmargin photowithborder" src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercu'.$modulepart.'&amp;file='.urlencode($relativepathimage).'">';
							$phototoshow.= '</div></div>';
						}
						// Si fichier png PDF de plus d'1 page trouve
						elseif (file_exists($fileimagebis))
						{
							$preview = preg_replace('/\.png/','',$relativepathimage) . "-0.png";
							$phototoshow = '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref">';
							$phototoshow.= '<img height="'.$heightforphotref.'" class="photo photowithmargin photowithborder" src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercu'.$modulepart.'&amp;file='.urlencode($preview).'"><p>';
							$phototoshow.= '</div></div>';
						}
					}
				}
				else if (! $phototoshow)
				{
					$phototoshow = $form->showphoto($modulepart,$object,0,0,0,'photoref','small',1,0,$maxvisiblephotos);
				}

				if ($phototoshow)
				{
					$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">';
					$morehtmlleft.=$phototoshow;
					$morehtmlleft.='</div>';
				}
			}

			if (! $phototoshow)      // Show No photo link (picto of pbject)
			{
				$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">';
				if ($object->element == 'action')
				{
					$width=80;
					$cssclass='photorefcenter';
					$nophoto=img_picto('', 'title_agenda', '', false, 1);
				}
				else
				{
					//$width=14;
					$cssclass='photorefcenter';
					$picto = $object->picto;
					if ($object->element == 'project' && ! $object->public) $picto = 'project'; // instead of projectpub
					$nophoto=img_picto('', 'object_'.$picto, '', false, 1);
				}
				$morehtmlleft.='<!-- No photo to show -->';
				$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="No photo" border="0"'.($width?' width="'.$width.'"':'').' src="'.$nophoto.'"></div></div>';

				$morehtmlleft.='</div>';
			}
		}
	}

	if ($showbarcode) $morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">'.$form->showbarcode($object).'</div>';

	if ($object->element == 'societe')
	{
		if (! empty($conf->use_javascript_ajax) && $user->rights->societe->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE))
		{
			$morehtmlstatus.=ajax_object_onoff($object, 'status', 'status', 'InActivity', 'ActivityCeased');
		}
		else {
			$morehtmlstatus.=$object->getLibStatut(6);
		}
	}
	elseif ($object->element == 'product')
	{
		//$morehtmlstatus.=$langs->trans("Status").' ('.$langs->trans("Sell").') ';
		if (! empty($conf->use_javascript_ajax) && $user->rights->produit->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
			$morehtmlstatus.=ajax_object_onoff($object, 'status', 'tosell', 'ProductStatusOnSell', 'ProductStatusNotOnSell');
		} else {
			$morehtmlstatus.='<span class="statusrefsell">'.$object->getLibStatut(5,0).'</span>';
		}
		$morehtmlstatus.=' &nbsp; ';
		//$morehtmlstatus.=$langs->trans("Status").' ('.$langs->trans("Buy").') ';
		if (! empty($conf->use_javascript_ajax) && $user->rights->produit->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
			$morehtmlstatus.=ajax_object_onoff($object, 'status_buy', 'tobuy', 'ProductStatusOnBuy', 'ProductStatusNotOnBuy');
		} else {
			$morehtmlstatus.='<span class="statusrefbuy">'.$object->getLibStatut(5,1).'</span>';
		}
	}
	elseif (in_array($object->element, array('facture', 'invoice', 'invoice_supplier', 'chargesociales', 'loan')))
	{
		$tmptxt=$object->getLibStatut(6, $object->totalpaye);
		if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3) || $conf->browser->layout=='phone') $tmptxt=$object->getLibStatut(5, $object->totalpaye);
		$morehtmlstatus.=$tmptxt;
	}
	elseif ($object->element == 'contrat' || $object->element == 'contract')
	{
		if ($object->statut == 0) $morehtmlstatus.=$object->getLibStatut(5);
		else $morehtmlstatus.=$object->getLibStatut(4);
	}
	elseif ($object->element == 'facturerec')
	{
		if ($object->frequency == 0) $morehtmlstatus.=$object->getLibStatut(2);
		else $morehtmlstatus.=$object->getLibStatut(5);
	}
	elseif ($object->element == 'project_task')
	{
		$object->fk_statut = 1;
		if ($object->progress > 0) $object->fk_statut = 2;
		if ($object->progress >= 100) $object->fk_statut = 3;
		$tmptxt=$object->getLibStatut(5);
		$morehtmlstatus.=$tmptxt;		// No status on task
	}
	else { // Generic case
		$tmptxt=$object->getLibStatut(6);
		if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3) || $conf->browser->layout=='phone') $tmptxt=$object->getLibStatut(5);
		$morehtmlstatus.=$tmptxt;
	}

	// Add if object was dispatched "into accountancy"
	if (! empty($conf->accounting->enabled) && in_array($object->element, array('bank', 'facture', 'invoice', 'invoice_supplier', 'expensereport')))
	{
		if (method_exists($object, 'getVentilExportCompta'))
		{
			$accounted = $object->getVentilExportCompta();
			$langs->load("accountancy");
			$morehtmlstatus.='</div><div class="statusref statusrefbis">'.($accounted > 0 ? $langs->trans("Accounted") : $langs->trans("NotYetAccounted"));
		}
	}

	// Add alias for thirdparty
	if (! empty($object->name_alias)) $morehtmlref.='<div class="refidno">'.$object->name_alias.'</div>';

	// Add label
	if ($object->element == 'product' || $object->element == 'bank_account' || $object->element == 'project_task')
	{
		if (! empty($object->label)) $morehtmlref.='<div class="refidno">'.$object->label.'</div>';
	}

	if (method_exists($object, 'getBannerAddress') && $object->element != 'product' && $object->element != 'bookmark' && $object->element != 'ecm_directories' && $object->element != 'ecm_files')
	{
		$morehtmlref.='<div class="refidno">';
		$morehtmlref.=$object->getBannerAddress('refaddress',$object);
		$morehtmlref.='</div>';
	}
	if (! empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && in_array($object->element, array('societe', 'contact', 'member', 'product')))
	{
		$morehtmlref.='<div style="clear: both;"></div><div class="refidno">';
		$morehtmlref.=$langs->trans("TechnicalID").': '.$object->id;
		$morehtmlref.='</div>';
	}

	print '<div class="'.($onlybanner?'arearefnobottom ':'arearef ').'heightref valignmiddle" width="100%">';
	print $form->showrefnav($object, $paramid, $morehtml, $shownav, $fieldid, 'none', $morehtmlref, $moreparam, $nodbprefix, $morehtmlleft, $morehtmlstatus, $morehtmlright);
	print '</div>';
	print '<div class="underrefbanner clearboth"></div>';
}