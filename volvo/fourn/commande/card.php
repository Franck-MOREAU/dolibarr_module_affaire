<?php
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric	Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2016 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2015 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2011-2015 Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2014      Ion Agorria          <ion@agorria.com>
 *
 * This	program	is free	software; you can redistribute it and/or modify
 * it under	the	terms of the GNU General Public	License	as published by
 * the Free	Software Foundation; either	version	2 of the License, or
 * (at your	option)	any	later version.
 *
 * This	program	is distributed in the hope that	it will	be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A	PARTICULAR PURPOSE.	 See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file		htdocs/fourn/commande/card.php
 *	\ingroup	supplier, order
 *	\brief		Card supplier order
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formorder.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_order/modules_commandefournisseur.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
if (! empty($conf->supplier_proposal->enabled))
	require DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';
if (!empty($conf->produit->enabled))
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
if (!empty($conf->projet->enabled)) {
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}


$langs->load('admin');
$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('propal');
$langs->load('supplier_proposal');
$langs->load('deliveries');
$langs->load('products');
$langs->load('stocks');
if (!empty($conf->incoterm->enabled)) $langs->load('incoterm');

$id 			= GETPOST('id','int');
$ref 			= GETPOST('ref','alpha');
$action 		= GETPOST('action','alpha');
$confirm		= GETPOST('confirm','alpha');
$comclientid 	= GETPOST('comid','int');
$socid			= GETPOST('socid','int');
$projectid		= GETPOST('projectid','int');
$cancel         = GETPOST('cancel','alpha');
$lineid         = GETPOST('lineid', 'int');

$lineid = GETPOST('lineid', 'int');
$origin = GETPOST('origin', 'alpha');
$originid = (GETPOST('originid', 'int') ? GETPOST('originid', 'int') : GETPOST('origin_id', 'int')); // For backward compatibility

//PDF
$hidedetails = (GETPOST('hidedetails','int') ? GETPOST('hidedetails','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc 	 = (GETPOST('hidedesc','int') ? GETPOST('hidedesc','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ?  1 : 0));
$hideref 	 = (GETPOST('hideref','int') ? GETPOST('hideref','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

$datelivraison=dol_mktime(GETPOST('liv_hour','int'), GETPOST('liv_min','int'), GETPOST('liv_sec','int'), GETPOST('liv_month','int'), GETPOST('liv_day','int'),GETPOST('liv_year','int'));

$mode = GETPOST('mode','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, '', 'commande');

$object = new CommandeFournisseur($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
if ($id > 0 || ! empty($ref))
{
	$ret = $object->fetch($id, $ref);
	if ($ret < 0) dol_print_error($db,$object->error);
	$ret = $object->fetch_thirdparty();
	if ($ret < 0) dol_print_error($db,$object->error);
}
else if (! empty($socid) && $socid > 0)
{
	$fourn = new Fournisseur($db);
	$ret=$fourn->fetch($socid);
	if ($ret < 0) dol_print_error($db,$object->error);
	$object->socid = $fourn->id;
	$ret = $object->fetch_thirdparty();
	if ($ret < 0) dol_print_error($db,$object->error);
}

$permissionnote=$user->rights->fournisseur->commande->creer;	// Used by the include of actions_setnotes.inc.php
$permissiondellink=$user->rights->fournisseur->commande->creer;	// Used by the include of actions_dellink.inc.php
$permissiontoedit=$user->rights->fournisseur->commande->creer;	// Used by the include of actions_lineupdown.inc.php


/*
 * Actions
 */

$parameters=array('socid'=>$socid);

if ($cancel) $action='';

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not include_once
include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once
include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';	// Must be include, not include_once

if($action == 'confirm_sendedi' && $confirm	== 'yes' &&	$user->rights->fournisseur->commande->commander){
	dol_include_once('/volvo/class/commande.trans.class.php');
	$trans = new CommandeTrans($db);
	$res = $trans->create($object);
	setEventMessage($trans->msg);
}

if($action == 'confirm_canceledi' && $confirm	== 'yes' &&	$user->rights->fournisseur->commande->commander){
	dol_include_once('/volvo/class/commande.trans.class.php');
	$trans = new CommandeTrans($db);
	$res = $trans->cancel($object);
	setEventMessage($trans->msg);
}

if($action == 'confirm_updateedi' && $confirm	== 'yes' &&	$user->rights->fournisseur->commande->commander){
	dol_include_once('/volvo/class/commande.trans.class.php');
	$trans = new CommandeTrans($db);
	$res = $trans->update($object);
	setEventMessage($trans->msg);
}


if ($action == 'setref_supplier' && $user->rights->fournisseur->commande->creer){
    $result=$object->setValueFrom('ref_supplier',GETPOST('ref_supplier','alpha'));
	if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	else $object->ref_supplier = GETPOST('ref_supplier','alpha');	// The setValueFrom does not set new property of object
}


// date of delivery
if ($action == 'setdate_livraison' && $user->rights->fournisseur->commande->creer){
	$result=$object->set_date_livraison($user,$datelivraison);
	if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
}

if ($action == 'reopen'){
	if (in_array($object->statut, array(1, 2, 3, 4, 5, 6, 7, 9))){
        if ($object->statut == 1) $newstatus=0;	// Validated->Draft
        else if ($object->statut == 2) $newstatus=0;	// Approved->Draft
        else if ($object->statut == 3) $newstatus=2;	// Ordered->Approved
        else if ($object->statut == 4) $newstatus=3;
        else if ($object->statut == 5) $newstatus=4;  // Received partially
        else if ($object->statut == 6) $newstatus=2;	// Canceled->Approved
        else if ($object->statut == 7) $newstatus=3;	// Canceled->Process running
        else if ($object->statut == 9) $newstatus=1;	// Refused->Validated
        else $newstatus = 2;

        $db->begin();

        $result = $object->setStatus($user, $newstatus);
        if ($result > 0){
	            // Currently the "Re-open" also remove the billed flag because there is no button "Set unpaid" yet.
	        $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande_fournisseur';
    	   	$sql.= ' SET billed = 0';
       		$sql.= ' WHERE rowid = '.$object->id;

        	$resql=$db->query($sql);

            if ($newstatus == 0){
	        	$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande_fournisseur';
        		$sql.= ' SET fk_user_approve = null, fk_user_approve2 = null, date_approve = null, date_approve2 = null';
        		$sql.= ' WHERE rowid = '.$object->id;

        		$resql=$db->query($sql);
        	}

       		$db->commit();

            header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
            exit;
        }else{
			$db->rollback();

            setEventMessages($object->error, $object->errors, 'errors');
        }
    }
}

/*
 * Classify supplier order as billed
 */
if ($action == 'classifybilled' && $user->rights->fournisseur->commande->creer){
	$ret=$object->classifyBilled($user);
	if ($ret < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// Add a product line
if ($action == 'addline' && $user->rights->fournisseur->commande->creer){
    $langs->load('errors');
    $error = 0;

	// Set if we used free entry or predefined product
	$predef='';
	$product_desc=(GETPOST('dp_desc')?GETPOST('dp_desc'):'');
	$date_start=dol_mktime(GETPOST('date_start'.$predef.'hour'), GETPOST('date_start'.$predef.'min'), GETPOST('date_start' . $predef . 'sec'), GETPOST('date_start'.$predef.'month'), GETPOST('date_start'.$predef.'day'), GETPOST('date_start'.$predef.'year'));
	$date_end=dol_mktime(GETPOST('date_end'.$predef.'hour'), GETPOST('date_end'.$predef.'min'), GETPOST('date_end' . $predef . 'sec'), GETPOST('date_end'.$predef.'month'), GETPOST('date_end'.$predef.'day'), GETPOST('date_end'.$predef.'year'));
	if (GETPOST('prod_entry_mode') == 'free'){
		$idprod=0;
		$price_ht = GETPOST('price_ht');
		$tva_tx = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
	}else{
		$idprod=GETPOST('idprod', 'int');
		$price_ht = '';
		$tva_tx = '';
	}

	$qty = GETPOST('qty'.$predef);
	$remise_percent=GETPOST('remise_percent'.$predef);

    // Extrafields
    $extrafieldsline = new ExtraFields($db);
    $extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
    $array_options = $extrafieldsline->getOptionalsFromPost($extralabelsline, $predef);
    // Unset extrafield
    if (is_array($extralabelsline)) {
    	// Get extra fields
    	foreach ($extralabelsline as $key => $value) {
    		unset($_POST["options_" . $key]);
    	}
    }

    if (GETPOST('prod_entry_mode')=='free' && GETPOST('price_ht') < 0 && $qty < 0) {
	        setEventMessages($langs->trans('ErrorBothFieldCantBeNegative', $langs->transnoentitiesnoconv('UnitPrice'), $langs->transnoentitiesnoconv('Qty')), null, 'errors');
	        $error++;
    }
    if (GETPOST('prod_entry_mode')=='free'  && ! GETPOST('idprodfournprice') && GETPOST('type') < 0) {
        setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type')), null, 'errors');
        $error++;
    }
    if (GETPOST('prod_entry_mode')=='free' && GETPOST('price_ht')==='' && GETPOST('price_ttc')==='') {
        setEventMessages($langs->trans($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('UnitPrice'))), null, 'errors');
        $error++;
    }
    if (GETPOST('prod_entry_mode')=='free' && ! GETPOST('dp_desc')){
	       setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Description')), null, 'errors');
	        $error++;
    }
    if (! GETPOST('qty')){
       setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
        $error++;
    }

    // Ecrase $pu par celui	du produit
    // Ecrase $desc	par	celui du produit
    // Ecrase $txtva  par celui du produit
    if ((GETPOST('prod_entry_mode') != 'free') && empty($error)){
    	$productsupplier = new ProductFournisseur($db);

    	if (empty($conf->global->SUPPLIER_ORDER_WITH_NOPRICEDEFINED)){
			$idprod=0;
			if (GETPOST('idprodfournprice') == -1 || GETPOST('idprodfournprice') == '') $idprod=-99;	// Same behaviour than with combolist. When not select idprodfournprice is now -99 (to avoid conflict with next action that may return -1, -2, ...)
		}

    	if (GETPOST('idprodfournprice') > 0){
    		$idprod=$productsupplier->get_buyprice(GETPOST('idprodfournprice'), $qty);    // Just to see if a price exists for the quantity. Not used to found vat.
    	}

    	if ($idprod > 0){
    		$res=$productsupplier->fetch($idprod);
    		$label = $productsupplier->label;
    		$desc = $productsupplier->description;
    		if (trim($product_desc) != trim($desc)) $desc = dol_concatdesc($desc, $product_desc);
    		$type = $productsupplier->type;
    		$tva_tx	= get_default_tva($object->thirdparty, $mysoc, $productsupplier->id, GETPOST('idprodfournprice'));
    		$tva_npr = get_default_npr($object->thirdparty, $mysoc, $productsupplier->id, GETPOST('idprodfournprice'));
			if (empty($tva_tx)) $tva_npr=0;
    		$localtax1_tx= get_localtax($tva_tx, 1, $mysoc, $object->thirdparty, $tva_npr);
    		$localtax2_tx= get_localtax($tva_tx, 2, $mysoc, $object->thirdparty, $tva_npr);

    		$result=$object->addline(
    			$desc,
    			$productsupplier->fourn_pu,
    			$qty,
    			$tva_tx,
    			$localtax1_tx,
    			$localtax2_tx,
	    		$productsupplier->id,
	    		GETPOST('idprodfournprice'),
	    		$productsupplier->fourn_ref,
	    		$remise_percent,
	    		'HT',
	    		$pu_ttc,
	    		$type,
	    		$tva_npr,
	    		'',
	    		$date_start,
	    		$date_end,
	    		$array_options,
			    $productsupplier->fk_unit
	    	);
    	}
    	if ($idprod == -99 || $idprod == 0){
   			// Product not selected
   			$error++;
   			$langs->load("errors");
   			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ProductOrService")).' '.$langs->trans("or").' '.$langs->trans("NoPriceDefinedForThisSupplier"), null, 'errors');
    	}
    	if ($idprod == -1){
	   		// Quantity too low
	   		$error++;
	   		$langs->load("errors");
	   		setEventMessages($langs->trans("ErrorQtyTooLowForThisSupplier"), null, 'errors');
	   	}
    }else if((GETPOST('price_ht')!=='' || GETPOST('price_ttc')!=='') && empty($error)){
		$pu_ht = price2num($price_ht, 'MU');
		$pu_ttc = price2num(GETPOST('price_ttc'), 'MU');
		$tva_npr = (preg_match('/\*/', $tva_tx) ? 1 : 0);
		$tva_tx = str_replace('*', '', $tva_tx);
		$label = (GETPOST('product_label') ? GETPOST('product_label') : '');
		$desc = $product_desc;
		$type = GETPOST('type');
		$fk_unit= GETPOST('units', 'alpha');
    	$tva_tx = price2num($tva_tx);	// When vat is text input field

    	// Local Taxes
    	$localtax1_tx= get_localtax($tva_tx, 1, $mysoc, $object->thirdparty);
    	$localtax2_tx= get_localtax($tva_tx, 2, $mysoc, $object->thirdparty);

    	if (GETPOST('price_ht')!==''){
    		$price_base_type = 'HT';
    		$ht = price2num(GETPOST('price_ht'));
    		$ttc = 0;
    	}else{
    		$ttc = price2num(GETPOST('price_ttc'));
    		$ht = $ttc / (1 + ($tva_tx / 100));
    		$price_base_type = 'HT';
    	}

		$result=$object->addline($desc, $ht, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, 0, 0, '', $remise_percent, $price_base_type, $ttc, $type,'','', $date_start, $date_end, $array_options, $fk_unit);
	}

    //print "xx".$tva_tx; exit;
    if (! $error && $result > 0) {
    	$ret=$object->fetch($object->id);    // Reload to get new records

        // Define output language
    	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)){
    		$outputlangs = $langs;
    		$newlang = '';
    		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
    		if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
    		if (! empty($newlang)) {
    			$outputlangs = new Translate("", $conf);
    			$outputlangs->setDefaultLang($newlang);
    		}
    		$model=$object->modelpdf;
    		$ret = $object->fetch($id); // Reload to get new records

    		$result=$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
    		if ($result < 0) dol_print_error($db,$result);
    	}

		unset($_POST ['prod_entry_mode']);
    	unset($_POST['qty']);
    	unset($_POST['type']);
    	unset($_POST['remise_percent']);
    	unset($_POST['pu']);
    	unset($_POST['price_ht']);
		unset($_POST['multicurrency_price_ht']);
    	unset($_POST['price_ttc']);
    	unset($_POST['tva_tx']);
    	unset($_POST['label']);
    	unset($localtax1_tx);
    	unset($localtax2_tx);
		unset($_POST['np_marginRate']);
		unset($_POST['np_markRate']);
    	unset($_POST['dp_desc']);
		unset($_POST['idprodfournprice']);
    	unset($_POST['date_starthour']);
    	unset($_POST['date_startmin']);
    	unset($_POST['date_startsec']);
    	unset($_POST['date_startday']);
    	unset($_POST['date_startmonth']);
    	unset($_POST['date_startyear']);
    	unset($_POST['date_endhour']);
    	unset($_POST['date_endmin']);
    	unset($_POST['date_endsec']);
    	unset($_POST['date_endday']);
    	unset($_POST['date_endmonth']);
    	unset($_POST['date_endyear']);
    }else{
       setEventMessages($object->error, $object->errors, 'errors');
    }
}

/*
 *	Updating a line in the order
 */
if ($action == 'updateline' && $user->rights->fournisseur->commande->creer &&	! GETPOST('cancel')){
	$tva_tx = GETPOST('tva_tx');
	if (GETPOST('price_ht') != ''){
   		$price_base_type = 'HT';
   		$ht = price2num(GETPOST('price_ht'));
   	}else{
   		$ttc = price2num(GETPOST('price_ttc'));
   		$ht = $ttc / (1 + ($tva_tx / 100));
   		$price_base_type = 'HT';
   	}

	if ($lineid){
        $line = new CommandeFournisseurLigne($db);
        $res = $line->fetch($lineid);
        if (!$res) dol_print_error($db);
    }

    $date_start=dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
    $date_end=dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));

    $localtax1_tx=get_localtax($tva_tx,1,$mysoc,$object->thirdparty);
    $localtax2_tx=get_localtax($tva_tx,2,$mysoc,$object->thirdparty);

	// Extrafields Lines
	$extrafieldsline = new ExtraFields($db);
	$extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
	$array_options = $extrafieldsline->getOptionalsFromPost($extralabelsline);
	// Unset extrafield POST Data
	if (is_array($extralabelsline)) {
		foreach ($extralabelsline as $key => $value) {
			unset($_POST["options_" . $key]);
		}
	}
	$desc = GETPOST('product_desc','alpha');
	$result	= $object->updateline(
	$lineid,
	$desc,
	$ht,
	$_POST['qty'],
	$_POST['remise_percent'],
	$tva_tx,
	$localtax1_tx,
	$localtax2_tx,
	$price_base_type,
	0,
	isset($_POST["type"])?$_POST["type"]:$line->product_type,
	false,
	$date_start,
	$date_end,
	$array_options,
	$_POST['units']
	);
	unset($_POST['qty']);
	unset($_POST['type']);
    unset($_POST['idprodfournprice']);
    unset($_POST['remmise_percent']);
    unset($_POST['dp_desc']);
    unset($_POST['np_desc']);
    unset($_POST['pu']);
    unset($_POST['tva_tx']);
    unset($_POST['date_start']);
    unset($_POST['date_end']);
	unset($_POST['units']);
    unset($localtax1_tx);
    unset($localtax2_tx);
	unset($_POST['date_starthour']);
	unset($_POST['date_startmin']);
	unset($_POST['date_startsec']);
	unset($_POST['date_startday']);
	unset($_POST['date_startmonth']);
	unset($_POST['date_startyear']);
	unset($_POST['date_endhour']);
	unset($_POST['date_endmin']);
	unset($_POST['date_endsec']);
	unset($_POST['date_endday']);
	unset($_POST['date_endmonth']);
	unset($_POST['date_endyear']);

    if ($result	>= 0){
        // Define output language
    	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)){
    		$outputlangs = $langs;
    		$newlang = '';
    		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
    		if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
    		if (! empty($newlang)) {
    			$outputlangs = new Translate("", $conf);
    			$outputlangs->setDefaultLang($newlang);
    		}
    		$model=$object->modelpdf;
    		$ret = $object->fetch($id); // Reload to get new records

    		$result=$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
    		if ($result < 0) dol_print_error($db,$result);
    	}
    }else{
        dol_print_error($db,$object->error);
        exit;
    }
}

// Remove a product line
if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->fournisseur->commande->creer){
	$result = $object->deleteline($lineid);
	if ($result > 0){
		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id'))
		$newlang = GETPOST('lang_id');
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
		$newlang = $object->thirdparty->default_lang;
		if (! empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
			$ret = $object->fetch($object->id); // Reload to get new records
			$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
		}

		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}else{
		setEventMessages($object->error, $object->errors, 'errors');
		/* Fix bug 1485 : Reset action to avoid asking again confirmation on failure */
		$action='';
	}
}

// Validate
if ($action == 'confirm_valid' && $confirm == 'yes' && ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fournisseur->commande->creer))
	    || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fournisseur->supplier_order_advance->validate)))){

	$object->date_commande=dol_now();
    $result = $object->valid($user);
    if ($result	>= 0){
        // Define output language
    	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)){
    		$outputlangs = $langs;
    		$newlang = '';
    		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
    		if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
    		if (! empty($newlang)) {
	   			$outputlangs = new Translate("", $conf);
   				$outputlangs->setDefaultLang($newlang);
   			}
	   		$model=$object->modelpdf;
   			$ret = $object->fetch($id); // Reload to get new records

	   		$result=$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
	   		if ($result < 0) dol_print_error($db,$result);
   		}
	}else{
        setEventMessages($object->error, $object->errors, 'errors');
    }
    // If we have permission, and if we don't need to provide the idwarehouse, we go directly on approved step
    if (empty($conf->global->SUPPLIER_ORDER_NO_DIRECT_APPROVE) && $user->rights->fournisseur->commande->approuver && ! (! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER) && $object->hasProductsOrServices(1))){
        $action='confirm_approve';	// can make standard or first level approval also if permission is set
    }
}

if (($action == 'confirm_approve' || $action == 'confirm_approve2') && $confirm == 'yes' && $user->rights->fournisseur->commande->approuver){
    $idwarehouse=GETPOST('idwarehouse', 'int');

    $qualified_for_stock_change=0;
	if (empty($conf->global->STOCK_SUPPORTS_SERVICES)){
	   	$qualified_for_stock_change=$object->hasProductsOrServices(2);
	}else{
	   	$qualified_for_stock_change=$object->hasProductsOrServices(1);
	}

    // Check parameters
    if (! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER) && $qualified_for_stock_change){
        if (! $idwarehouse || $idwarehouse == -1){
            $error++;
            setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
            $action='';
        }
    }

    if (! $error){
        $result	= $object->approve($user, $idwarehouse, ($action=='confirm_approve2'?1:0));
        if ($result > 0){
            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                $outputlangs = $langs;
                $newlang = '';
                if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
                if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
                if (! empty($newlang)) {
                    $outputlangs = new Translate("", $conf);
                    $outputlangs->setDefaultLang($newlang);
                }
	            $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
            }
            header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
            exit;
        }else{
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }
}

if ($action == 'confirm_commande' && $confirm	== 'yes' &&	$user->rights->fournisseur->commande->commander){
	if($_REQUEST["methode"] == 'OrderByEDI'){
		$object->send_edi;
	}
    $result = $object->commande($user, $_REQUEST["datecommande"],	$_REQUEST["methode"], $_REQUEST['comment']);
    if ($result > 0) {
        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
            $outputlangs = $langs;
            $newlang = '';
            if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
            if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
            if (! empty($newlang)) {
                $outputlangs = new Translate("", $conf);
                $outputlangs->setDefaultLang($newlang);
            }
	        $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
        }
        $action = '';
	}else{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}


if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->fournisseur->commande->supprimer){
	$result=$object->delete($user);
	if ($result > 0){
		header("Location: ".DOL_URL_ROOT.'/fourn/commande/list.php');
		exit;
	}else{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// Action clone object
if ($action == 'confirm_clone' && $confirm == 'yes' && $user->rights->fournisseur->commande->creer)	{
	if (1==0 && ! GETPOST('clone_content') && ! GETPOST('clone_receivers'))	{
		setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
	}else{
		if ($object->id > 0){
			$result=$object->createFromClone();
			if ($result > 0){
				header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
				exit;
			}else{
	            setEventMessages($object->error, $object->errors, 'errors');
				$action='';
			}
		}
	}
}

// Set status of reception (complete, partial, ...)
if ($action == 'livraison')	{
	if (GETPOST("type") != ''){
        $date_liv = dol_mktime(GETPOST('rehour'),GETPOST('remin'),GETPOST('resec'),GETPOST("remonth"),GETPOST("reday"),GETPOST("reyear"));
        $result = $object->Livraison($user, $date_liv, GETPOST("type"), GETPOST("comment"));
        if ($result > 0){
            $langs->load("deliveries");
            setEventMessages($langs->trans("DeliveryStateSaved"), null);
            $action = '';
		}else if($result == -3){
           setEventMessages($object->error, $object->errors, 'errors');
        }else{
           setEventMessages($object->error, $object->errors, 'errors');
        }
    }else{
	    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Delivery")), null, 'errors');
	}
}

if ($action == 'builddoc' && $user->rights->fournisseur->commande->creer){
	// Save last template used to generate document
    if (GETPOST('model')) $object->setDocModel($user, GETPOST('model','alpha'));
    $outputlangs = $langs;
	if (GETPOST('lang_id')){
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang(GETPOST('lang_id'));
    }
    $result= $object->generateDocument($object->modelpdf,$outputlangs, $hidedetails, $hidedesc, $hideref);
    if ($result	<= 0) {
        setEventMessages($object->error, $object->errors, 'errors');
        $action='';
    }
}

// Delete file in doc form
if ($action == 'remove_file' && $object->id > 0 && $user->rights->fournisseur->commande->creer){
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    $langs->load("other");
    $upload_dir =	$conf->fournisseur->commande->dir_output;
    $file =	$upload_dir	. '/' .	GETPOST('file');
    $ret=dol_delete_file($file,0,0,0,$object);
    if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
    else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
}

if ($action == 'update_extras'){

	if (isset($_POST['options_vin'])){
		$vin = GETPOST('options_vin','alpha');
	} else {
		$vin = $object->array_options['options_vin'];
	}
	if (isset($_POST['options_immat'])){
		$immat  = GETPOST('options_immat','alpha');
	} else {
		$immat = $object->array_options['options_immat'];
	}
	if (isset($_POST['options_numom'])){
		$numom  = GETPOST('options_numom','alpha');
	} else {
		$numom = $object->array_options['options_numom'];
	}
	if (isset($_POST['options_ctm'])){
		$ctm  = GETPOST('options_ctm','alpha');
	} else {
		$ctm = $object->array_options['options_ctm'];
	}
	if ($object->array_options['options_vin'] != $vin || $object->array_options['options_immat'] != $immat || $object->array_options['options_numom'] != $numom || $object->array_options['options_ctm'] != $ctm) {
		dol_include_once('/volvo/lib/volvo.lib.php');
		Update_vh_info_from_suporder($object->id,$vin , $immat,$numom,$ctm,$note,1,0);
	}

	// Fill array 'array_options' with data from add form
	$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);
	$ret = $extrafields->setOptionalsFromPost($extralabels,$object,GETPOST('attribute'));
	if ($ret < 0) $error++;

	if (! $error){
		$result = $object->insertExtraFields();
	}else{
		$action = 'edit_extras';
	}
}

include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';


/*
 * Create an order
 */
if ($action == 'add' && $user->rights->fournisseur->commande->creer){
 	$error=0;

    if ($socid <1){
	    setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentities('Supplier')), null, 'errors');
    	$action='create';
    	$error++;
    }

    if (! $error){
        $db->begin();

        // Creation commande
        $object->ref_supplier  	= GETPOST('refsupplier');
        $object->socid         	= $socid;
		$object->cond_reglement_id = GETPOST('cond_reglement_id');
        $object->mode_reglement_id = GETPOST('mode_reglement_id');
        $object->fk_account        = GETPOST('fk_account', 'int');
        $object->note_private	= GETPOST('note_private');
        $object->note_public   	= GETPOST('note_public');
		$object->date_livraison = $datelivraison;
		$object->fk_incoterms = GETPOST('incoterm_id', 'int');
		$object->location_incoterms = GETPOST('location_incoterms', 'alpha');
		$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
		$object->multicurrency_tx = GETPOST('originmulticurrency_tx', 'int');
		$object->fk_project       = GETPOST('projectid');
		// Fill array 'array_options' with data from add form
       	if (! $error){
			$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
			if ($ret < 0) $error++;
       	}

      	if (! $error){
		// If creation from another object of another module (Example: origin=propal, originid=1)
			if (! empty($origin) && ! empty($originid))	{
				if ($origin == 'order' || $origin == 'commande'){
	                $element = $subelement = 'commande';
                }else{
					$element = 'supplier_proposal';
					$subelement = 'supplier_proposal';
				}

				$object->origin = $origin;
				$object->origin_id = $originid;

				// Possibility to add external linked objects with hooks
				$object->linked_objects [$object->origin] = $object->origin_id;
				$other_linked_objects = GETPOST('other_linked_objects', 'array');
				if (! empty($other_linked_objects)) {
					$object->linked_objects = array_merge($object->linked_objects, $other_linked_objects);
				}

				$id = $object->create($user);
				if ($id > 0){
					dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');

					$classname = 'SupplierProposal';
					$srcobject = new $classname($db);

					dol_syslog("Try to find source object origin=" . $object->origin . " originid=" . $object->origin_id . " to add lines");
					$result = $srcobject->fetch($object->origin_id);
					if ($result > 0){
						$object->set_date_livraison($user, $srcobject->date_livraison);
						$object->set_id_projet($user, $srcobject->fk_project);

						$lines = $srcobject->lines;
						if (empty($lines) && method_exists($srcobject, 'fetch_lines')){
							$srcobject->fetch_lines();
							$lines = $srcobject->lines;
						}

						$fk_parent_line = 0;
						$num = count($lines);

						$productsupplier = new ProductFournisseur($db);

						for($i = 0; $i < $num; $i ++){
							if (empty($lines[$i]->subprice) || $lines[$i]->qty <= 0) continue;
							$label = (! empty($lines[$i]->label) ? $lines[$i]->label : '');
							$desc = (! empty($lines[$i]->desc) ? $lines[$i]->desc : $lines[$i]->libelle);
							$product_type = (! empty($lines[$i]->product_type) ? $lines[$i]->product_type : 0);

							// Reset fk_parent_line for no child products and special product
							if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
							$fk_parent_line = 0;
						}

						// Extrafields
						if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && method_exists($lines[$i], 'fetch_optionals')){
							$lines[$i]->fetch_optionals($lines[$i]->rowid);
							$array_option = $lines[$i]->array_options;
						}

						$result = $productsupplier->find_min_price_product_fournisseur($lines[$i]->fk_product, $lines[$i]->qty);
						if ($result>=0){
						    $tva_tx = $lines[$i]->tva_tx;
						    if ($origin=="commande"){
			                    $soc=new societe($db);
			                    $soc->fetch($socid);
			                    $tva_tx=get_default_tva($soc, $mysoc, $lines[$i]->fk_product, $productsupplier->product_fourn_price_id);
						    }

							$result = $object->addline(
							$desc,
							$lines[$i]->subprice,
							$lines[$i]->qty,
							$tva_tx,
							$lines[$i]->localtax1_tx,
							$lines[$i]->localtax2_tx,
							$lines[$i]->fk_product > 0 ? $lines[$i]->fk_product : 0,
							$productsupplier->product_fourn_price_id,
							$productsupplier->ref_supplier,
							$lines[$i]->remise_percent,
							'HT',
							0,
							$lines[$i]->product_type,
							'',
							'',
							null,
							null,
							array(),
							$lines[$i]->fk_unit
							);
						}

						if ($result < 0) {
							$error++;
							break;
						}

						// Defined the new fk_parent_line
						if ($result > 0 && $lines[$i]->product_type == 9) {
							$fk_parent_line = $result;
						}
					}
					// Hooks
					$parameters = array('objFrom' => $srcobject);
				} else {
	        		setEventMessages($srcobject->error, $srcobject->errors, 'errors');
					$error ++;
				}
			} else {
	        	setEventMessages($object->error, $object->errors, 'errors');
				$error ++;
			}
		}else{
	   		$id = $object->create($user);
        	if ($id < 0){
        		$error++;
	        	setEventMessages($object->error, $object->errors, 'errors');
        	}
		}
	}

	if ($error){
		$langs->load("errors");
	    $db->rollback();
	    $action='create';
	    $_GET['socid']=$_POST['socid'];
	}else{
        $db->commit();
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
        exit;
    }
}
}

/*
 * Send mail
 */

// Actions to send emails
$actiontypecode='AC_SUP_ORD';
$trigger_name='ORDER_SUPPLIER_SENTBYMAIL';
$paramname='id';
$mode='emailfromsupplierorder';
include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && $user->rights->fournisseur->commande->creer){
	if ($action == 'addcontact'){
		if ($object->id > 0){
			$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
			$result = $object->add_contact($contactid, $_POST["type"], $_POST["source"]);
		}

		if ($result >= 0){
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}else{
			if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS'){
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
			}else{
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	// bascule du statut d'un contact
	else if ($action == 'swapstatut' && $object->id > 0){
		$result=$object->swapContactStatus(GETPOST('ligne'));
	}

	// Efface un contact
	else if ($action == 'deletecontact' && $object->id > 0)	{
		$result = $object->delete_contact($_GET["lineid"]);
		if ($result >= 0){
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}else{
			dol_print_error($db);
		}
	}
}



/*
 * View
 */
$help_url='EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
llxHeader('',$langs->trans("Order"),$help_url);

$form =	new	Form($db);
$formfile = new FormFile($db);
$formorder = new FormOrder($db);
$productstatic = new Product($db);


/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

$now=dol_now();
if ($action=='create')
{
	print load_fiche_titre($langs->trans('NewOrder'));

	dol_htmloutput_events();

	$societe='';
	if ($socid>0)
	{
		$societe=new Societe($db);
		$societe->fetch($socid);
	}

	if (! empty($origin) && ! empty($originid))
	{
		// Parse element/subelement (ex: project_task)
		$element = $subelement = $origin;
		if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
			$element = $regs [1];
			$subelement = $regs [2];
		}

		$element = 'supplier_proposal';
		$subelement = 'supplier_proposal';

		dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');

		$classname = 'SupplierProposal';
		$objectsrc = new $classname($db);
		$objectsrc->fetch($originid);
		if (empty($objectsrc->lines) && method_exists($objectsrc, 'fetch_lines'))
			$objectsrc->fetch_lines();
		$objectsrc->fetch_thirdparty();

		// Replicate extrafields
		$objectsrc->fetch_optionals($originid);
		$object->array_options = $objectsrc->array_options;

		$projectid = (! empty($objectsrc->fk_project) ? $objectsrc->fk_project : '');
		$ref_client = (! empty($objectsrc->ref_client) ? $objectsrc->ref_client : '');

		$soc = $objectsrc->client;
		$cond_reglement_id	= (!empty($objectsrc->cond_reglement_id)?$objectsrc->cond_reglement_id:(!empty($soc->cond_reglement_id)?$soc->cond_reglement_id:1));
		$mode_reglement_id	= (!empty($objectsrc->mode_reglement_id)?$objectsrc->mode_reglement_id:(!empty($soc->mode_reglement_id)?$soc->mode_reglement_id:0));
        $fk_account         = (! empty($objectsrc->fk_account)?$objectsrc->fk_account:(! empty($soc->fk_account)?$soc->fk_account:0));
		$availability_id	= (!empty($objectsrc->availability_id)?$objectsrc->availability_id:(!empty($soc->availability_id)?$soc->availability_id:0));
        $shipping_method_id = (! empty($objectsrc->shipping_method_id)?$objectsrc->shipping_method_id:(! empty($soc->shipping_method_id)?$soc->shipping_method_id:0));
		$demand_reason_id	= (!empty($objectsrc->demand_reason_id)?$objectsrc->demand_reason_id:(!empty($soc->demand_reason_id)?$soc->demand_reason_id:0));
		$remise_percent		= (!empty($objectsrc->remise_percent)?$objectsrc->remise_percent:(!empty($soc->remise_percent)?$soc->remise_percent:0));
		$remise_absolue		= (!empty($objectsrc->remise_absolue)?$objectsrc->remise_absolue:(!empty($soc->remise_absolue)?$soc->remise_absolue:0));
		$dateinvoice		= empty($conf->global->MAIN_AUTOFILL_DATE)?-1:'';

		$datedelivery = (! empty($objectsrc->date_livraison) ? $objectsrc->date_livraison : '');

		if (!empty($conf->multicurrency->enabled))
		{
			if (!empty($objectsrc->multicurrency_code)) $currency_code = $objectsrc->multicurrency_code;
			if (!empty($conf->global->MULTICURRENCY_USE_ORIGIN_TX) && !empty($objectsrc->multicurrency_tx))	$currency_tx = $objectsrc->multicurrency_tx;
		}

		$note_private = $object->getDefaultCreateValueFor('note_private', (! empty($objectsrc->note_private) ? $objectsrc->note_private : null));
		$note_public = $object->getDefaultCreateValueFor('note_public', (! empty($objectsrc->note_public) ? $objectsrc->note_public : null));

		// Object source contacts list
		$srccontactslist = $objectsrc->liste_contact(- 1, 'external', 1);

	}
	else
	{
		$cond_reglement_id 	= $societe->cond_reglement_supplier_id;
		$mode_reglement_id 	= $societe->mode_reglement_supplier_id;

		if (!empty($conf->multicurrency->enabled) && !empty($soc->multicurrency_code)) $currency_code = $soc->multicurrency_code;

		$note_private = $object->getDefaultCreateValueFor('note_private');
		$note_public = $object->getDefaultCreateValueFor('note_public');
	}

	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="socid" value="' . $soc->id . '">' . "\n";
	print '<input type="hidden" name="remise_percent" value="' . $soc->remise_percent . '">';
	print '<input type="hidden" name="origin" value="' . $origin . '">';
	print '<input type="hidden" name="originid" value="' . $originid . '">';
	if (!empty($currency_tx)) print '<input type="hidden" name="originmulticurrency_tx" value="' . $currency_tx . '">';

	dol_fiche_head('');

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td class="titlefieldcreate">'.$langs->trans('Ref').'</td><td>'.$langs->trans('Draft').'</td></tr>';

	// Third party
	print '<tr><td class="fieldrequired">'.$langs->trans('Supplier').'</td>';
	print '<td>';

	if ($socid > 0)
	{
		print $societe->getNomUrl(1);
		print '<input type="hidden" name="socid" value="'.$socid.'">';
	}
	else
	{
		print $form->select_company((empty($socid)?'':$socid), 'socid', 's.fournisseur = 1', 'SelectThirdParty');
	}
	print '</td>';

	// Ref supplier
	print '<tr><td>'.$langs->trans('RefSupplier').'</td><td><input name="refsupplier" type="text"></td>';
	print '</tr>';

	print '</td></tr>';

	// Planned delivery date
	print '<tr><td>';
	print $langs->trans('DateDeliveryPlanned');
	print '</td>';
	print '<td>';
	$usehourmin=0;
	if (! empty($conf->global->SUPPLIER_ORDER_USE_HOUR_FOR_DELIVERY_DATE)) $usehourmin=1;
	$form->select_date($datelivraison?$datelivraison:-1,'liv_',$usehourmin,$usehourmin,'',"set");
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('NotePublic').'</td>';
	print '<td>';
	$doleditor = new DolEditor('note_public', isset($note_public) ? $note_public : GETPOST('note_public'), '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
	print $doleditor->Create(1);
	print '</td>';
	//print '<textarea name="note_public" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea>';
	print '</tr>';

	print '<tr><td>'.$langs->trans('NotePrivate').'</td>';
	print '<td>';
	$doleditor = new DolEditor('note_private', isset($note_private) ? $note_private : GETPOST('note_private'), '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
	print $doleditor->Create(1);
	print '</td>';
	//print '<td><textarea name="note_private" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea></td>';
	print '</tr>';

	if (! empty($origin) && ! empty($originid) && is_object($objectsrc)) {

		print "\n<!-- " . $classname . " info -->";
		print "\n";
		print '<input type="hidden" name="amount"         value="' . $objectsrc->total_ht . '">' . "\n";
		print '<input type="hidden" name="total"          value="' . $objectsrc->total_ttc . '">' . "\n";
		print '<input type="hidden" name="tva"            value="' . $objectsrc->total_tva . '">' . "\n";
		print '<input type="hidden" name="origin"         value="' . $objectsrc->element . '">';
		print '<input type="hidden" name="originid"       value="' . $objectsrc->id . '">';

		$newclassname = $classname;
		print '<tr><td>' . $langs->trans($newclassname) . '</td><td colspan="2">' . $objectsrc->getNomUrl(1) . '</td></tr>';
		print '<tr><td>' . $langs->trans('TotalHT') . '</td><td colspan="2">' . price($objectsrc->total_ht) . '</td></tr>';
		print '<tr><td>' . $langs->trans('TotalVAT') . '</td><td colspan="2">' . price($objectsrc->total_tva) . "</td></tr>";
		if ($mysoc->localtax1_assuj == "1" || $objectsrc->total_localtax1 != 0) 		// Localtax1 RE
		{
			print '<tr><td>' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td><td colspan="2">' . price($objectsrc->total_localtax1) . "</td></tr>";
		}

		if ($mysoc->localtax2_assuj == "1" || $objectsrc->total_localtax2 != 0) 		// Localtax2 IRPF
		{
			print '<tr><td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td><td colspan="2">' . price($objectsrc->total_localtax2) . "</td></tr>";
		}

		print '<tr><td>' . $langs->trans('TotalTTC') . '</td><td colspan="2">' . price($objectsrc->total_ttc) . "</td></tr>";

		if (!empty($conf->multicurrency->enabled))
		{
			print '<tr><td>' . $langs->trans('MulticurrencyTotalHT') . '</td><td colspan="2">' . price($objectsrc->multicurrency_total_ht) . '</td></tr>';
			print '<tr><td>' . $langs->trans('MulticurrencyTotalVAT') . '</td><td colspan="2">' . price($objectsrc->multicurrency_total_tva) . '</td></tr>';
			print '<tr><td>' . $langs->trans('MulticurrencyTotalTTC') . '</td><td colspan="2">' . price($objectsrc->multicurrency_total_ttc) . '</td></tr>';
		}
	}

	// Other options
    $parameters=array();

	print $object->showOptionals($extrafields,'edit');

	// Bouton "Create Draft"
    print "</table>\n";

    dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateDraft').'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
	print '</div>';

	print "</form>\n";

	// Show origin lines
	if (! empty($origin) && ! empty($originid) && is_object($objectsrc))
	{
		$title = $langs->trans('ProductsAndServices');
		print load_fiche_titre($title);

		print '<table class="noborder" width="100%">';

		$objectsrc->printOriginLinesList();

		print '</table>';
	}
}
elseif (! empty($object->id))
{
    $societe = new Fournisseur($db);
    $result=$societe->fetch($object->socid);
    if ($result < 0) dol_print_error($db);

	$author	= new User($db);
	$author->fetch($object->user_author_id);

	$res=$object->fetch_optionals($object->id,$extralabels);

	$head = ordersupplier_prepare_head($object);

	$title=$langs->trans("SupplierOrder");
	dol_fiche_head($head, 'card', $title, 0, 'order');


	$formconfirm='';

	/*
	 * Confirmation de la suppression de la commande
	 */
	if ($action	== 'delete')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteOrder'), $langs->trans('ConfirmDeleteOrder'), 'confirm_delete', '', 0, 2);

	}

	// Clone confirmation
	if ($action == 'clone')
	{
		// Create an array for form
		$formquestion=array(
				//array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1)
		);
		// Paiement incomplet. On demande si motif = escompte ou autre
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans('CloneOrder'),$langs->trans('ConfirmCloneOrder',$object->ref),'confirm_clone',$formquestion,'yes',1);

	}

	/*
	 * Confirmation de la validation
	 */
	if ($action	== 'valid')
	{
		$object->date_commande=dol_now();

		// We check if number is temporary number
		if (preg_match('/^[\(]?PROV/i',$object->ref) || empty($object->ref)) // empty should not happened, but when it occurs, the test save life
		{
		    $newref = $object->getNextNumRef($object->thirdparty);
		}
		else $newref = $object->ref;

		if ($newref < 0)
		{
			setEventMessages($object->error, $object->errors, 'errors');
			$action='';
		}
		else
		{
			$text=$langs->trans('ConfirmValidateOrder',$newref);
			if (! empty($conf->notification->enabled))
			{
				require_once DOL_DOCUMENT_ROOT .'/core/class/notify.class.php';
				$notify=new	Notify($db);
				$text.='<br>';
				$text.=$notify->confirmMessage('ORDER_SUPPLIER_VALIDATE', $object->socid, $object);
			}

			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateOrder'), $text, 'confirm_valid', '', 0, 1);
		}
	}

	/*
	 * Confirm approval
	 */
	if ($action	== 'approve' || $action	== 'approve2')
	{
        $qualified_for_stock_change=0;
	    if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
	    {
	    	$qualified_for_stock_change=$object->hasProductsOrServices(2);
	    }
	    else
	    {
	    	$qualified_for_stock_change=$object->hasProductsOrServices(1);
	    }

		$formquestion=array();
		if (! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER) && $qualified_for_stock_change)
		{
			$langs->load("stocks");
			require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
			$formproduct=new FormProduct($db);
			$formquestion=array(
					//'text' => $langs->trans("ConfirmClone"),
					//array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1),
					//array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1),
					array('type' => 'other', 'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockIncrease"),   'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse'),'idwarehouse','',1))
			);
		}
		$text=$langs->trans("ConfirmApproveThisOrder",$object->ref);
		if (! empty($conf->notification->enabled))
		{
			require_once DOL_DOCUMENT_ROOT .'/core/class/notify.class.php';
			$notify=new	Notify($db);
			$text.='<br>';
			$text.=$notify->confirmMessage('ORDER_SUPPLIER_APPROVE', $object->socid, $object);
		}

		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id, $langs->trans("ApproveThisOrder"), $text, "confirm_".$action, $formquestion, 1, 1, 240);
	}

	/*
	 * Confirmation de l'envoi de la commande
	 */
	if ($action	== 'commande')
	{
		$date_com = dol_mktime(GETPOST('rehour'),GETPOST('remin'),GETPOST('resec'),GETPOST("remonth"),GETPOST("reday"),GETPOST("reyear"));
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id."&datecommande=".$date_com."&methode=".$_POST["methodecommande"]."&comment=".urlencode($_POST["comment"]), $langs->trans("MakeOrder"),$langs->trans("ConfirmMakeOrder",dol_print_date($date_com,'day')),"confirm_commande",'',0,2);

	}

	// Confirmation to delete line
	if ($action == 'ask_deleteline')
	{
		 $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 0, 1);
	}

	// Confirmation to send EDI
	if ($action == 'sendedi')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, 'Envoyer un EDI','Envoyer la commande par EDI ?', 'confirm_sendedi', '', 0, 1);
	}

	// Confirmation to update EDI
	if ($action == 'updateedi')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, 'Annule et remplace EDI','Annuler et remplacer la commande par EDI ?', 'confirm_updateedi', '', 0, 1);
	}

	// Confirmation to cancel EDI
	if ($action == 'canceledi')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, 'Annuler un EDI','Annuler la commande par EDI ?', 'confirm_canceledi', '', 0, 1);
	}

	// Print form confirm
	print $formconfirm;

	/*
	 *	Commande
	*/
	$nbrow=8;
	if (! empty($conf->projet->enabled))	$nbrow++;

	//Local taxes
	if($mysoc->localtax1_assuj=="1") $nbrow++;
	if($mysoc->localtax2_assuj=="1") $nbrow++;

	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

	// Ref
	print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td>';
	print '<td colspan="2">';
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
	print '</td>';
	print '</tr>';

	// Ref supplier
	print '<tr><td>';
	print $form->editfieldkey("RefSupplier",'ref_supplier',$object->ref_supplier,$object,$user->rights->fournisseur->commande->creer);
	print '</td><td colspan="2">';
	print $form->editfieldval("RefSupplier",'ref_supplier',$object->ref_supplier,$object,$user->rights->fournisseur->commande->creer);
	print '</td></tr>';

	// Fournisseur
	print '<tr><td>'.$langs->trans("Supplier")."</td>";
	print '<td colspan="2">'.$object->thirdparty->getNomUrl(1,'supplier').'</td>';
	print '</tr>';

	// Statut
	print '<tr>';
	print '<td>'.$langs->trans("Status").'</td>';
	print '<td colspan="2">';
	print $object->getLibStatut(4);
	print "</td></tr>";

	// Date
	if ($object->methode_commande_id > 0)
	{
		print '<tr><td>'.$langs->trans("Date").'</td><td colspan="2">';
		if ($object->date_commande)
		{
			print dol_print_date($object->date_commande,"dayhourtext")."\n";
		}
		print "</td></tr>";

		if ($object->methode_commande)
		{
			print '<tr><td>'.$langs->trans("Method").'</td><td colspan="2">'.$object->getInputMethod().'</td></tr>';
		}
	}

	// Author
	print '<tr><td>'.$langs->trans("AuthorRequest").'</td>';
	print '<td colspan="2">'.$author->getNomUrl(1).'</td>';
	print '</tr>';


	// Delivery date planed
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('DateDeliveryPlanned');
	print '</td>';
	if ($action != 'editdate_livraison') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDeliveryDate'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="2">';
	if ($action == 'editdate_livraison')
	{
		print '<form name="setdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="setdate_livraison">';
		$usehourmin=0;
		if (! empty($conf->global->SUPPLIER_ORDER_USE_HOUR_FOR_DELIVERY_DATE)) $usehourmin=1;
		$form->select_date($object->date_livraison?$object->date_livraison:-1,'liv_',$usehourmin,$usehourmin,'',"setdate_livraison");
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	}
	else
	{
		$usehourmin='day';
		if (! empty($conf->global->SUPPLIER_ORDER_USE_HOUR_FOR_DELIVERY_DATE)) $usehourmin='dayhour';
		print $object->date_livraison ? dol_print_date($object->date_livraison, $usehourmin) : '&nbsp;';
		if ($object->hasDelay() && ! empty($object->date_livraison)) {
		    print ' '.img_picto($langs->trans("Late").' : '.$object->showDelay(), "warning");
		}
	}
	print '</td></tr>';

	// Other attributes
	$cols = 3;
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	// Total
	print '<tr><td>'.$langs->trans("AmountHT").'</td>';
	print '<td colspan="2">'.price($object->total_ht,'',$langs,1,-1,-1,$conf->currency).'</td>';
	print '</tr>';

	// Total VAT
	print '<tr><td>'.$langs->trans("AmountVAT").'</td><td colspan="2">'.price($object->total_tva,'',$langs,1,-1,-1,$conf->currency).'</td>';
	print '</tr>';

	// Total TTC
	print '<tr><td>'.$langs->trans("AmountTTC").'</td><td colspan="2">'.price($object->total_ttc,'',$langs,1,-1,-1,$conf->currency).'</td>';
	print '</tr>';

	print "</table><br>";

	if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
		$blocname = 'contacts';
		$title = $langs->trans('ContactsAddresses');
		include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
	}

	if (! empty($conf->global->MAIN_DISABLE_NOTES_TAB))
	{
		$blocname = 'notes';
		$title = $langs->trans('Notes');
		include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
	}

	/*
	 * Lines
	 */
	//$result = $object->getLinesArray();


	print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline')?'#add':'#line_'.GETPOST('lineid')).'" method="POST">
	<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">
	<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline') . '">
	<input type="hidden" name="mode" value="">
	<input type="hidden" name="id" value="'.$object->id.'">
    <input type="hidden" name="socid" value="'.$societe->id.'">
	';

	if (! empty($conf->use_javascript_ajax) && $object->statut == 0) {
		include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
	}

	print '<table id="tablelines" class="noborder noshadow" width="100%">';

	// Add free products/services form
	global $forceall, $senderissupplier, $dateSelector;
	$forceall=1; $senderissupplier=1; $dateSelector=0;

	// Show object lines
	$inputalsopricewithtax=0;
	if (! empty($object->lines))
		$ret = $object->printObjectLines($action, $societe, $mysoc, $lineid, 1);

	$num = count($object->lines);

	// Form to add new line
	if ($object->statut == 0 && $user->rights->fournisseur->commande->creer)
	{
		if ($action != 'editline')
		{
			$var = true;

			// Add free products/services
			$object->formAddObjectLine(1, $societe, $mysoc);

			$parameters = array();
		}
	}
	print '</table>';

	print '</form>';

	dol_fiche_end();


	/*
	 * Action presend
	 */
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}
	if ($action == 'presend')
	{
		$ref = dol_sanitizeFileName($object->ref);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$fileparams = dol_most_recent_file($conf->fournisseur->commande->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
		$file=$fileparams['fullname'];

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
			$newlang = $_REQUEST['lang_id'];
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
			$newlang = $object->thirdparty->default_lang;

		if (!empty($newlang))
		{
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang($newlang);
			$outputlangs->load('commercial');
		}

		// Build document if it not exists
		if (! $file || ! is_readable($file))
		{
			$result= $object->generateDocument(GETPOST('model')?GETPOST('model'):$object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result <= 0)
			{
				dol_print_error($db,$result);
				exit;
			}
			$fileparams = dol_most_recent_file($conf->fournisseur->commande->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
			$file=$fileparams['fullname'];
		}

		print '<div class="clearboth"></div>';
		print '<br>';
		print load_fiche_titre($langs->trans('SendOrderByMail'));

		dol_fiche_head('');

		// Cree l'objet formulaire mail
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
		$formmail->fromtype = 'user';
		$formmail->fromid   = $user->id;
		$formmail->fromname = $user->getFullName($langs);
		$formmail->frommail = $user->email;
		$formmail->trackid='sor'.$object->id;
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
		{
			include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			$formmail->frommail=dolAddEmailTrackId($formmail->frommail, 'sor'.$object->id);
		}
		$formmail->withfrom=1;
		$liste=array();
		foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key=>$value)	$liste[$key]=$value;
		$formmail->withto=GETPOST("sendto")?GETPOST("sendto"):$liste;
		$formmail->withtocc=$liste;
		$formmail->withtoccc=(! empty($conf->global->MAIN_EMAIL_USECCC)?$conf->global->MAIN_EMAIL_USECCC:false);
		$formmail->withtopic='Commande: '.$object->ref_supplier;
		if (!empty($object->array_options['options_ctm'])) {
			dol_include_once('/societe/class/societe.class.php');
			$socctm = new Societe($db);
			$socctm->fetch($object->array_options['options_ctm']);
			$formmail->withtopic.=' | Client: '. $socctm->name. ' | ';
		}else{
			dol_include_once('/commande/class/commande.class.php');
			$socctm = new Commande($db);
			$res = $socctm->fetch($object->source);
			if($res>0) $socctm->fetch_thirdparty();
			$formmail->withtopic.=' | Client: '. $socctm->thirdparty->name . ' | ';
		}
		if (!empty($object->array_options['options_immat'])) {
			$formmail->withtopic.=' | Immat: '. $object->array_options['options_immat'];
		}
		if (!empty($object->array_options['options_vin'])) {
			$formmail->withtopic.=' | Chassis: '. substr($object->array_options['options_vin'],-7);
		}
		$formmail->withfile=2;
		$formmail->withbody=1;
		$formmail->withdeliveryreceipt=1;
		$formmail->withcancel=1;
		$object->fetch_projet();
		// Tableau des substitutions
		$formmail->setSubstitFromObject($object);
		$formmail->substit['__ORDERREF__']=$object->ref_supplier;                  	// For backward compatibility
		$formmail->substit['__ORDERSUPPLIERREF__']=$object->ref_supplier;	// For backward compatibility
		$formmail->substit['__SUPPLIERORDERREF__']=$object->ref_supplier;

		//Find the good contact adress
		$custcontact='';
		$contactarr=array();
		$contactarr=$object->liste_contact(-1,'external');

		if (is_array($contactarr) && count($contactarr)>0) {
			foreach($contactarr as $contact) {
				if ($contact['libelle']==$langs->trans('TypeContact_order_supplier_external_BILLING')) {
					require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
					$contactstatic=new Contact($db);
					$contactstatic->fetch($contact['id']);
					$custcontact=$contactstatic->getFullName($langs,1);
				}
			}

			if (!empty($custcontact)) {
				$formmail->substit['__CONTACTCIVNAME__']=$custcontact;
			}
		}

		// Tableau des parametres complementaires
		$formmail->param['action']='send';
		$formmail->param['models']='order_supplier_send';
		$formmail->param['models_id']=GETPOST('modelmailselected','int');
		$formmail->param['orderid']=$object->id;
		$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;

		// Init list of files
		if (GETPOST("mode")=='init')
		{
			$formmail->clear_attached_files();
			$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
		}

		// Show form
		print $formmail->get_form();

		dol_fiche_end();
	}

	/*
	 * Show buttons
	 */
	else
	{
		/**
		 * Boutons actions
		 */

		if ($user->societe_id == 0 && $action != 'editline' && $action != 'delete')
		{
			print '<div	class="tabsAction">';

				// Validate
				if ($object->statut == 0 && $num > 0)
				{
			        if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fournisseur->commande->creer))
			       	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fournisseur->supplier_order_advance->validate)))
					{
						$tmpbuttonlabel=$langs->trans('Validate');
						if ($user->rights->fournisseur->commande->approuver && empty($conf->global->SUPPLIER_ORDER_NO_DIRECT_APPROVE)) $tmpbuttonlabel = $langs->trans("ValidateAndApprove");

						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=valid">';
						print $tmpbuttonlabel;
						print '</a>';
					}
				}
				// Create event
				if ($conf->agenda->enabled && ! empty($conf->global->MAIN_ADD_EVENT_ON_ELEMENT_CARD)) 	// Add hidden condition because this is not a "workflow" action so should appears somewhere else on page.
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/comm/action/card.php?action=create&amp;origin=' . $object->element . '&amp;originid=' . $object->id . '&amp;socid=' . $object->socid . '">' . $langs->trans("AddAction") . '</a></div>';
				}

				// Modify
				if ($object->statut == 1)
				{
					if ($user->rights->fournisseur->commande->commander)
					{
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans("Modify").'</a>';
					}
				}

				// Approve
				if ($object->statut == 1)
				{
					if ($user->rights->fournisseur->commande->approuver)
					{
						if (! empty($conf->global->SUPPLIER_ORDER_DOUBLE_APPROVAL) && $conf->global->MAIN_FEATURES_LEVEL > 0 && $object->total_ht >= $conf->global->SUPPLIER_ORDER_DOUBLE_APPROVAL && ! empty($object->user_approve_id))
						{
							print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("FirstApprovalAlreadyDone")).'">'.$langs->trans("ApproveOrder").'</a>';
						}
						else
						{
							print '<a class="butAction"	href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=approve">'.$langs->trans("ApproveOrder").'</a>';
						}
					}
					else
					{
						print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans("ApproveOrder").'</a>';
					}
				}

				// Second approval (if option SUPPLIER_ORDER_DOUBLE_APPROVAL is set)
				if (! empty($conf->global->SUPPLIER_ORDER_DOUBLE_APPROVAL) && $conf->global->MAIN_FEATURES_LEVEL > 0 && $object->total_ht >= $conf->global->SUPPLIER_ORDER_DOUBLE_APPROVAL)
				{
					if ($object->statut == 1)
					{
						if ($user->rights->fournisseur->commande->approve2)
						{
							if (! empty($object->user_approve_id2))
							{
								print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("SecondApprovalAlreadyDone")).'">'.$langs->trans("Approve2Order").'</a>';
							}
							else
							{
								print '<a class="butAction"	href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=approve2">'.$langs->trans("Approve2Order").'</a>';
							}
						}
						else
						{
							print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans("Approve2Order").'</a>';
						}
					}
				}

				// Refuse
				if ($object->statut == 1)
				{
					if ($user->rights->fournisseur->commande->approuver || $user->rights->fournisseur->commande->approve2)
					{
						print '<a class="butAction"	href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=refuse">'.$langs->trans("RefuseOrder").'</a>';
					}
					else
					{
						print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans("RefuseOrder").'</a>';
					}
				}

				// Send
				if (in_array($object->statut, array(2, 3, 4, 5)))
				{
					if ($user->rights->fournisseur->commande->commander)
					{
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendByMail').'</a>';
					}
				}

				// Reopen
				if (in_array($object->statut, array(2)))
				{
				    $buttonshown=0;
				    if (! $buttonshown && $user->rights->fournisseur->commande->approuver)
				    {
				        if (empty($conf->global->SUPPLIER_ORDER_REOPEN_BY_APPROVER_ONLY)
				            || (! empty($conf->global->SUPPLIER_ORDER_REOPEN_BY_APPROVER_ONLY) && $user->id == $object->user_approve_id))
				        {
				            print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans("Disapprove").'</a>';
				            $buttonshown++;
				        }
				    }
				    if (! $buttonshown && $user->rights->fournisseur->commande->approve2 && ! empty($conf->global->SUPPLIER_ORDER_DOUBLE_APPROVAL))
				    {
				        if (empty($conf->global->SUPPLIER_ORDER_REOPEN_BY_APPROVER2_ONLY)
				            || (! empty($conf->global->SUPPLIER_ORDER_REOPEN_BY_APPROVER2_ONLY) && $user->id == $object->user_approve_id2))
				        {
				            print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans("Disapprove").'</a>';
				        }
				    }
				}
				if (in_array($object->statut, array(3, 4, 5, 6, 7, 9)))
				{
					if ($user->rights->fournisseur->commande->commander)
					{
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans("ReOpen").'</a>';
					}
				}

				// Ship
				if (! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER))
				{
					if (in_array($object->statut, array(3,4))) {
						if ($conf->fournisseur->enabled && $user->rights->fournisseur->commande->receptionner) {
							print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/fourn/commande/dispatch.php?id=' . $object->id . '">' . $langs->trans('OrderDispatch') . '</a></div>';
						} else {
							print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('OrderDispatch') . '</a></div>';
						}
					}
				}

				// Create bill
				if (! empty($conf->facture->enabled))
				{
					if (! empty($conf->fournisseur->enabled) && ($object->statut >= 2 && $object->billed != 1))  // 2 means accepted
					{
						if ($user->rights->fournisseur->facture->creer)
						{
							print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/facture/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("CreateBill").'</a>';
						}

						if ($user->rights->fournisseur->commande->creer && $object->statut >= 2 && !empty($object->linkedObjectsIds['invoice_supplier']))
						{
							print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=classifybilled">'.$langs->trans("ClassifyBilled").'</a>';
						}
					}

				}

				// Clone
				if ($user->rights->fournisseur->commande->creer)
				{
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;socid='.$object->socid.'&amp;action=clone&amp;object=order">'.$langs->trans("ToClone").'</a>';
				}

				// Cancel
				if ($object->statut == 2)
				{
					if ($user->rights->fournisseur->commande->commander)
					{
						print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=cancel">'.$langs->trans("CancelOrder").'</a>';
					}
				}

				// Delete
				if ($user->rights->fournisseur->commande->supprimer)
				{
					print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
				}

			print "</div>";
		}

		print "<br>";
		$object->fetch_thirdparty();
		print '<div class="fichecenter"><div class="fichehalfleft">';
		if (!empty($object->thirdparty->webservices_url)){
			dol_include_once('/volvo/class/commande.trans.class.php');
			$trans = new CommandeTrans($db);
			$res = $trans->fetch($object);

			// Bloc EDI
			print load_fiche_titre('Gestion des EDI');
			print '<table width="100%" class="nobordernopadding">';
			print '<tr class="liste_titre" >';
			print '<th class="liste_titre">Date</th>';
			print '<th class="liste_titre">Référence</th>';
			print '<th class="liste_titre">Montant</th>';
			print '<th class="liste_titre">Statut</th>';
			Print '</tr>';
			$cmdactiv =0;
			if (count($trans->cmd_found)>0){
				foreach ($trans->cmd_found as $cmd){
					print '<tr>';
					print '<td>' .dol_print_date($cmd['date'],'daytext') . '</td>';
					print '<td>' .$cmd['ref'] . '</td>';
					print '<td>' .price($cmd['total']) . ' €</td>';
					print '<td>' .$trans->getLibStatut(2,$cmd['status']) .'</td>';
					print '</tr>';
					if(in_array($cmd['status'], array(0,1,2))){
						$cmdactiv++;
						print '<tr>';
						print '<td colspan="2" align="center"><a class="button" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=updateedi">Annule et Remplace</a></td>';
						print '<td colspan="2" align="center"><a class="button" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=canceledi">Annuler Commande</a></td>';
						print '</tr>';
					}elseif($cmd['status'] == 3){
						$cmdactiv++;
					}
				}
				if(empty($cmdactiv)){
					print '<tr>';
					print '<td colspan="4" align="center"><a class="button" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=sendedi">Envoyer l\'EDI</a></td>';
					print '</tr>';
				}
			}else{
				print '<tr>';
				print '<td colspan="4" align="center"> resultat: ' .$trans->msg .'</td>';
				print '</tr>';
				if($trans->msg == 'Pas de commande transmise'){
					print '<tr>';
					print '<td colspan="4" align="center"><a class="button" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=sendedi">Envoyer l\'EDI</a></td>';
					print '</tr>';
				}
			}

			Print '</table>';

			print '</br>';
		}


		/*
		 * Documents generes
		 */
		$comfournref = dol_sanitizeFileName($object->ref);
		$file =	$conf->fournisseur->dir_output . '/commande/' . $comfournref .	'/'	. $comfournref . '.pdf';
		$relativepath =	$comfournref.'/'.$comfournref.'.pdf';
		$filedir = $conf->fournisseur->dir_output	. '/commande/' .	$comfournref;
		$urlsource=$_SERVER["PHP_SELF"]."?id=".$object->id;
		$genallowed=$user->rights->fournisseur->commande->creer;
		$delallowed=$user->rights->fournisseur->commande->supprimer;

		print $formfile->showdocuments('commande_fournisseur',$comfournref,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf,1,0,0,0,0,'','','',$object->thirdparty->default_lang);
		$somethingshown=$formfile->numoffiles;

		// Linked object block
		$somethingshown = $form->showLinkedObjectBlock($object);

		// Show links to link elements
		//$linktoelem = $form->showLinkToObjectBlock($object);
		//if ($linktoelem) print '<br>'.$linktoelem;


		print '</div><div class="fichehalfright"><div class="ficheaddleft">';


		if ($user->rights->fournisseur->commande->commander && $object->statut == 2)
		{
			/*
			 * Commander (action=commande)
			 */
			print '<!-- form to record supplier order -->'."\n";
			print '<form name="commande" action="card.php?id='.$object->id.'&amp;action=commande" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden"	name="action" value="commande">';
			print load_fiche_titre($langs->trans("ToOrder"),'','');
			print '<table class="border" width="100%">';
			//print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("ToOrder").'</td></tr>';
			print '<tr><td>'.$langs->trans("OrderDate").'</td><td>';
			$date_com = dol_mktime(0, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
			print $form->select_date($date_com,'',1,1,'',"commande",1,0,1);
			print '</td></tr>';

			print '<tr><td>'.$langs->trans("OrderMode").'</td><td>';
			$formorder->selectInputMethod(GETPOST('methodecommande'), "methodecommande", 1);
			print '</td></tr>';

			print '<tr><td>'.$langs->trans("Comment").'</td><td><input size="40" type="text" name="comment" value="'.GETPOST('comment').'"></td></tr>';
			print '<tr><td align="center" colspan="2"><input type="submit" class="button" value="'.$langs->trans("ToOrder").'"></td></tr>';
			print '</table>';
			print '</form>';
			print "<br>";
		}

		if ($user->rights->fournisseur->commande->receptionner	&& ($object->statut == 3 || $object->statut == 4))
		{
			/*
			 * Receptionner (action=livraison)
			 */
			print '<!-- form to record supplier order received -->'."\n";
			print '<form action="card.php?id='.$object->id.'" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden"	name="action" value="livraison">';
			print load_fiche_titre($langs->trans("Receive"),'','');
			print '<table class="border" width="100%">';
			//print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Receive").'</td></tr>';
			print '<tr><td>'.$langs->trans("DeliveryDate").'</td><td>';
			print $form->select_date('','',1,1,'',"commande",1,0,1);
			print "</td></tr>\n";

			print "<tr><td>".$langs->trans("Delivery")."</td><td>\n";
			$liv = array();
			$liv[''] = '&nbsp;';
			$liv['tot']	= $langs->trans("TotalWoman");
			$liv['par']	= $langs->trans("PartialWoman");
			$liv['nev']	= $langs->trans("NeverReceived");
			$liv['can']	= $langs->trans("Canceled");

			print $form->selectarray("type",$liv);

			print '</td></tr>';
			print '<tr><td>'.$langs->trans("Comment").'</td><td><input size="40" type="text" name="comment"></td></tr>';
			print '<tr><td align="center" colspan="2"><input type="submit" class="button" value="'.$langs->trans("Receive").'"></td></tr>';
			print "</table>\n";
			print "</form>\n";
			print "<br>";
		}

        // List of actions on element
        include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
        $formactions=new FormActions($db);
        $somethingshown=$formactions->showactions($object,'order_supplier',$socid,0,'listaction'.($genallowed?'largetitle':''));

		print '</div></div></div>';
	}

	print '</td></tr></table>';
}

// End of page
llxFooter();

$db->close();
