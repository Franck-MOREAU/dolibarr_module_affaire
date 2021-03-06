<?php
/* Copyright (C) 2003-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Marc Barilley / Ocebo	<marc@ocebo.com>
 * Copyright (C) 2005-2015	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2010-2013	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2011-2016	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2013	Christophe Battarel		<christophe.battarel@altairis.fr>
 * Copyright (C) 2012		Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2012       Cedric Salvador      	<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry		<jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file htdocs/commande/card.php
 * \ingroup commande
 * \brief Page to show customer order
 */

require '../../../main.inc.php';
dol_include_once('/core/class/html.formfile.class.php');
dol_include_once('/core/class/html.formorder.class.php');
dol_include_once('/core/class/html.formmargin.class.php');
dol_include_once('/core/modules/commande/modules_commande.php');
dol_include_once('/affaires/volvo/class/commandevolvo.class.php');
dol_include_once('/comm/action/class/actioncomm.class.php');
dol_include_once('/affaires/volvo/lib/volvo.lib.php');
dol_include_once('/core/lib/functions2.lib.php');
dol_include_once('/core/class/extrafields.class.php');
dol_include_once('/core/class/doleditor.class.php');
dol_include_once('/affaires/class/html.formaffairesproduct.class.php');

$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('propal');
$langs->load('deliveries');
$langs->load('sendings');
$langs->load('products');
$langs->load('volvo@volvo');

if (! empty($conf->margin->enabled))
	$langs->load('margins');

	$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('orderid', 'int'));
	$ref = GETPOST('ref', 'alpha');
	$socid = GETPOST('socid', 'int');
	$action = GETPOST('action', 'alpha');
	$confirm = GETPOST('confirm', 'alpha');
	$lineid = GETPOST('lineid', 'int');
	$origin = GETPOST('origin', 'alpha');
	$originid = (GETPOST('originid', 'int') ? GETPOST('originid', 'int') : GETPOST('origin_id', 'int')); // For backward compatibility
	$prodentrymode = GETPOST('prod_entry_mode');
	$idprod=GETPOST('idprod', 'int');
	// PDF
	$hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
	$hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0));
	$hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

	// Security check
	if (! empty($user->societe_id))
		$socid = $user->societe_id;
		$result = restrictedArea($user, 'commande', $id);

		$object = new CommandeVolvo($db);
		$extrafields = new ExtraFields($db);

		// fetch optionals attributes and labels
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

		// Load object
		include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once

		// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
		$hookmanager->initHooks(array('ordercard','globalcard'));

		$permissionnote = $user->rights->commande->creer; 		// Used by the include of actions_setnotes.inc.php
		$permissiondellink = $user->rights->commande->creer; 	// Used by the include of actions_dellink.inc.php
		$permissionedit = $user->rights->commande->creer; 		// Used by the include of actions_lineupdown.inc.php

		/*
		 * Actions
		 */

		$parameters = array('socid' => $socid);
		//Hook Do action
		if ($cancel) $action='';

		include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; 	// Must be include, not include_once
		include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once
		include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';	// Must be include, not include_once



		// Action Recalc
		if($action=='recalc') {
			$object->updatevhpriceandvnc($object->id,GETPOST('prixtot','int'));
			$action = '';
			header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
		}

		// if($action=='create_contrat') {
		// 	dol_include_once('/contrat/class/contrat.class.php');
		// 	$ct = New Contrat($db);
		// 	$object->fetch_lines();
		// 	$soltrs1 = $leadext->prepare_array('VOLVO_VCM_LIST','array');
		// 	$soltrs2 = $leadext->prepare_array('VOLVO_PACK_LIST','array');
		// 	$soltrs = array_merge($soltrs1,$soltrs2);
		// 	$action = '';
		// 	foreach ($object->lines as $ligne){
		// 		if(in_array($ligne->product_ref, $soltrs)){
		// 			$ct->ref = '(PROV)' .$object->ref . '-' . $ligne->id;
		// 			$ct->ref_customer = $object->array_options['options_immat'];
		// 			$ct->ref_supplier = substr($object->array_options['options_vin'], -7);
		// 			$ct->socid = $object->socid;
		// 			$ct->commercial_signature_id = $user->id;
		// 			$ct->commercial_suivi_id = $user->id;
		// 			$ct->date_contrat = dol_now();
		// 			$ct->create($user);
		// 			$ct->add_object_linked('commande',$object->id);
		// 			$ct->addline('', $ligne->pa_ht, $ligne->qty, $ligne->tva_tx, 0, 0, $ligne->fk_product, 0, '', '');
		// 		}
		// 	}
		// 	header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
		// }

		// Action clone object
		if ($action == 'confirm_clone' && $confirm == 'yes' && $user->rights->commande->creer) {
			$sql = "SELECT fk_target FROM " . MAIN_DB_PREFIX . "element_element WHERE sourcetype = 'commande' AND targettype = 'affaires_det' AND fk_source = " . $object->id;
			$res = $db->query ( $sql );
			if ($res) {
				$obj = $db->fetch_object ( $res );

				dol_include_once('/affaires/class/affaires.class.php');
				$affaires = new Affaires_det($db);
				$res = $affaires->fetch($obj->fk_target);
				if($res>0){
					$res = $affaires->createFromClone();
					if($res<0){
						$error++;
					}else{
						$affnum=$res;
					}
				}else{
					$error++;
				}
			}

			if ($object->id > 0 && empty($error)) {
				// Because createFromClone modifies the object, we must clone it so that we can restore it later
				$orig = clone $object;
				$result = $object->createFromClone();
				if ($result > 0) {
					$object->ref_client = $orig->ref_client;
					unset($object->user_valid);
					$result=$object->update ( $user );
					if ($result<0) {
						setEventMessages(null,$object->errors,'errors');
					}
					$affaires->add_object_linked('commande',$object->id);
					$affaires->fk_commande = $object->id;
					$affaires->update($user);
					header ( "Location: " . $_SERVER ['PHP_SELF'] . '?id=' . $object->id );
					exit ();
				} else {
					setEventMessages ( $object->error, $object->errors, 'errors' );
					$object = $orig;
					$action = '';
				}
			}
		}

		if ($action == 'confirm_payed' && $confirm == 'yes') {
			$ok = 0;
			$ok = $object->find_dt_cmd('dt_pay');
			$action='';
			if(empty($ok)){
				dol_include_once('/volvo/class/commandevolvo.class.php');
				$ordervolvo=new CommandeVolvo($db);
				$ordervolvo->fetch($object->id);
				$ordervolvo->date_payed=dol_mktime(0, 0, 0, GETPOST('date_payedmonth'), GETPOST('date_payedday'), GETPOST('date_payedyear'));
				$result=$ordervolvo->setpayed($user);
				if ($result<0) {
					setEventMessages(null, $ordervolvo->errors,'errors');
				}
			}


		}

		if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->commande->supprimer){
			// Remove order
			$result = $object->delete($user);
			if ($result > 0){
				header("Location: ".DOL_URL_ROOT.'/commande/index.php');
				exit;
			}else{
				setEventMessages($object->error, $object->errors, 'errors');
			}

		}

		if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->commande->creer){
			// Remove a product line
			$px_org = $object->total_ht;
			$soltrs1 = prepare_array('VOLVO_VCM_LIST','array');
			$soltrs2 = prepare_array('VOLVO_PACK_LIST','array');
			$soltrs = array_merge($soltrs1,$soltrs2);
			if(in_array($object->lines[$lineid]->product_ref, $soltrs)){
				$oldstatus = $object->statut;
				$object->statut = 0;
				$object->update($user);
			}
			$result = $object->deleteline($lineid);

			if ($result > 0){
				// Define output language
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id');
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang = $object->thirdparty->default_lang;
				if (! empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
					$ret = $object->fetch($object->id); // Reload to get new records
					$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}
				if(in_array($object->lines[$lineid]->product_ref, $soltrs)){
					$object->statut = $oldstatus;
					$object->update($user);
				}
				header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=recalc&prixtot='.$px_org); // Pour reaffichage de la fiche en cours d'edition
				exit();
			}else{
				setEventMessages($object->error, $object->errors, 'errors');
			}

		}

		if ($action == 'add' && $user->rights->commande->creer){
			// Add order
			$datecommande = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
			$datelivraison = dol_mktime(12, 0, 0, GETPOST('liv_month'), GETPOST('liv_day'), GETPOST('liv_year'));

			if ($datecommande == '') {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Date')), null, 'errors');
				$action = 'create';
				$error++;
			}

			if ($socid < 1) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Customer")), null, 'errors');
				$action = 'create';
				$error++;
			}

			if (! $error) {
				$object->socid = $socid;
				$object->fetch_thirdparty();
				$db->begin();

				$object->date_commande = $datecommande;
				$object->note_private = GETPOST('note_private');
				$object->note_public = GETPOST('note_public');
				$object->source = GETPOST('source_id');
				$object->ref_client = GETPOST('ref_client');
				$object->modelpdf = GETPOST('model');
				$object->cond_reglement_id = GETPOST('cond_reglement_id');
				$object->mode_reglement_id = GETPOST('mode_reglement_id');
				$object->fk_account = GETPOST('fk_account', 'int');
				$object->availability_id = GETPOST('availability_id');
				$object->demand_reason_id = GETPOST('demand_reason_id');
				$object->date_livraison = $datelivraison;
				$object->contactid = GETPOST('contactid');
				// Fill array 'array_options' with data from add form
				if (! $error){
					$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
					if ($ret < 0) $error++;
				}

				// End of object creation, we show it
				if ($object_id > 0 && ! $error)	{
					$db->commit();
					header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object_id);
					exit();
				} else {
					$db->rollback();
					$action = 'create';
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}

		}

		if ($action == 'confirm_billed' && $confirm == 'yes' && $user->rights->commande->creer){
			$ok = 0;
			$ok = $object->find_dt_cmd('dt_bill');
			$action='';
			if(empty($ok)){
				dol_include_once('/volvo/class/commandevolvo.class.php');
				$ordervolvo=new CommandeVolvo($db);
				$ordervolvo->fetch($object->id);
				$ordervolvo->date_billed=dol_mktime(0, 0, 0, GETPOST('date_billedmonth'), GETPOST('date_billedday'), GETPOST('date_billedyear'));
				$result=$ordervolvo->classifyBilled($user);
				if ($result<0) {
					setEventMessages(null, $ordervolvo->errors,'errors');
				}
			}
		}

		if ($action == 'classifyunbilled' && $user->rights->commande->creer){
			$ok = 0;
			$ok = $object->find_dt_cmd('dt_bill');
			if(!empty($ok)){
				$ret=$object->classifyUnBilled();
				if ($ret < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}

		}

		if ($action == 'setdate' && $user->rights->commande->creer) {
			$date = dol_mktime(0, 0, 0, GETPOST('order_month'), GETPOST('order_day'), GETPOST('order_year'));
			$result = $object->set_date($user, $date);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}

		}

		if ($action == 'setdate_livraison' && $user->rights->commande->creer) {
			$datelivraison = dol_mktime(0, 0, 0, GETPOST('liv_month'), GETPOST('liv_day'), GETPOST('liv_year'));
			$result = $object->set_date_livraison($user, $datelivraison);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}

		}

		if ($action == 'setdemandreason' && $user->rights->commande->creer) {
			$result = $object->demand_reason(GETPOST('demand_reason_id'));
			if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');

		}

		if ($action == 'setconditions' && $user->rights->commande->creer) {
			$result = $object->setPaymentTerms(GETPOST('cond_reglement_id', 'int'));
			if ($result < 0) {
				dol_print_error($db, $object->error);
			} else {
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
					// Define output language
					$outputlangs = $langs;
					$newlang = GETPOST('lang_id', 'alpha');
					if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang = $object->thirdparty->default_lang;
					if (! empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$ret = $object->fetch($object->id); // Reload to get new records
					$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}
			}

		}

		if ($action == 'addline' && $user->rights->commande->creer && !empty($idprod)) {
			// Add a new line
			$langs->load('errors');
			$error = 0;
			$px_org=$object->total_ht;
			// Set if we used free entry or predefined product
			$predef='';
			$product_desc=(GETPOST('dp_desc')?GETPOST('dp_desc'):'');
			$price_ht = GETPOST('price_ht');
			if (GETPOST('prod_entry_mode') == 'free') {
				$idprod=0;
				$tva_tx = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
			}else{
				$idprod=GETPOST('idprod', 'int');
				$tva_tx = '';
			}
			$qty = GETPOST('qty' . $predef);
			$remise_percent = GETPOST('remise_percent' . $predef);

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

			if (empty($idprod) && ($price_ht < 0) && ($qty < 0)) {
				setEventMessages($langs->trans('ErrorBothFieldCantBeNegative', $langs->transnoentitiesnoconv('UnitPriceHT'), $langs->transnoentitiesnoconv('Qty')), null, 'errors');
				$error++;
			}
			if (GETPOST('prod_entry_mode') == 'free' && empty($idprod) && GETPOST('type') < 0) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type')), null, 'errors');
				$error++;
			}
			if (GETPOST('prod_entry_mode') == 'free' && empty($idprod) && (! ($price_ht >= 0) || $price_ht == '')){
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("UnitPriceHT")), null, 'errors');
				$error++;
			}
			if ($qty == '') {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
				$error++;
			}
			if (GETPOST('prod_entry_mode') == 'free' && empty($idprod) && empty($product_desc)) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Description')), null, 'errors');
				$error++;
			}

			if (! $error && ($qty >= 0) && (! empty($product_desc) || ! empty($idprod))) {
				// Clean parameters
				$date_start=dol_mktime(GETPOST('date_start'.$predef.'hour'), GETPOST('date_start'.$predef.'min'), GETPOST('date_start'.$predef.'sec'), GETPOST('date_start'.$predef.'month'), GETPOST('date_start'.$predef.'day'), GETPOST('date_start'.$predef.'year'));
				$date_end=dol_mktime(GETPOST('date_end'.$predef.'hour'), GETPOST('date_end'.$predef.'min'), GETPOST('date_end'.$predef.'sec'), GETPOST('date_end'.$predef.'month'), GETPOST('date_end'.$predef.'day'), GETPOST('date_end'.$predef.'year'));
				$price_base_type = (GETPOST('price_base_type', 'alpha')?GETPOST('price_base_type', 'alpha'):'HT');
				if (! empty($idprod)) {
					$prod = new Product($db);
					$prod->fetch($idprod);

					$label = ((GETPOST('product_label') && GETPOST('product_label') != $prod->label) ? GETPOST('product_label') : '');

					// Update if prices fields are defined
					$tva_tx = get_default_tva($mysoc, $object->thirdparty, $prod->id);
					$tva_npr = get_default_npr($mysoc, $object->thirdparty, $prod->id);
					if (empty($tva_tx)) $tva_npr=0;
					$pu_ht = $prod->price;
					$pu_ttc = $prod->price_ttc;
					$price_min = $prod->price_min;
					$price_base_type = $prod->price_base_type;

					// multiprix
					if (! empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($object->thirdparty->price_level)) {
						$pu_ht = $prod->multiprices[$object->thirdparty->price_level];
						$pu_ttc = $prod->multiprices_ttc[$object->thirdparty->price_level];
						$price_min = $prod->multiprices_min[$object->thirdparty->price_level];
						$price_base_type = $prod->multiprices_base_type[$object->thirdparty->price_level];
						if (! empty($conf->global->PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL)) {
							if (isset($prod->multiprices_tva_tx[$object->thirdparty->price_level])) $tva_tx=$prod->multiprices_tva_tx[$object->thirdparty->price_level];
							if (isset($prod->multiprices_recuperableonly[$object->thirdparty->price_level])) $tva_npr=$prod->multiprices_recuperableonly[$object->thirdparty->price_level];
						}
					}elseif (! empty($conf->global->PRODUIT_CUSTOMER_PRICES)){
						require_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';

						$prodcustprice = new Productcustomerprice($db);

						$filter = array('t.fk_product' => $prod->id,'t.fk_soc' => $object->thirdparty->id);

						$result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
						if ($result >= 0){
							if (count($prodcustprice->lines) > 0){
								$pu_ht = price($prodcustprice->lines [0]->price);
								$pu_ttc = price($prodcustprice->lines [0]->price_ttc);
								$price_base_type = $prodcustprice->lines [0]->price_base_type;
								$prod->tva_tx = $prodcustprice->lines [0]->tva_tx;
							}
						}else{
							setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
						}
					}

					// if price ht is forced (ie: calculated by margin rate and cost price)
					if (! empty($price_ht)) {
						$pu_ht = price2num($price_ht, 'MU');
						$pu_ttc = price2num($pu_ht * (1 + ($tva_tx / 100)), 'MU');
					}elseif ($tva_tx != $prod->tva_tx) {
						// On reevalue prix selon taux tva car taux tva transaction peut etre different
						// de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).

						if ($price_base_type != 'HT') {
							$pu_ht = price2num($pu_ttc / (1 + ($tva_tx / 100)), 'MU');
						} else {
							$pu_ttc = price2num($pu_ht * (1 + ($tva_tx / 100)), 'MU');
						}
					}

					$desc = '';

					// Define output language
					if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
						$outputlangs = $langs;
						$newlang = '';
						if (empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id');
						if (empty($newlang)) $newlang = $object->thirdparty->default_lang;
						if (! empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
						}

						$desc = (! empty($prod->multilangs [$outputlangs->defaultlang] ["description"])) ? $prod->multilangs [$outputlangs->defaultlang] ["description"] : $prod->description;
					} else {
						$desc = $prod->description;
					}

					$desc = dol_concatdesc($desc, $product_desc);
					// Add custom code and origin country into description
					if (empty($conf->global->MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE) && (! empty($prod->customcode) || ! empty($prod->country_code))) {
						$tmptxt = '(';
						if (! empty($prod->customcode))	$tmptxt .= $langs->transnoentitiesnoconv("CustomCode") . ': ' . $prod->customcode;
						if (! empty($prod->customcode) && ! empty($prod->country_code)) $tmptxt .= ' - ';
						if (! empty($prod->country_code)) $tmptxt .= $langs->transnoentitiesnoconv("CountryOrigin") . ': ' . getCountry($prod->country_code, 0, $db, $langs, 0);
						$tmptxt .= ')';
						$desc = dol_concatdesc($desc, $tmptxt);
					}

					$type = $prod->type;
					$fk_unit = $prod->fk_unit;
				} else {
					$pu_ht = price2num($price_ht, 'MU');
					$pu_ttc = price2num(GETPOST('price_ttc'), 'MU');
					$tva_npr = (preg_match('/\*/', $tva_tx) ? 1 : 0);
					$tva_tx = str_replace('*', '', $tva_tx);
					$label = (GETPOST('product_label') ? GETPOST('product_label') : '');
					$desc = $product_desc;
					$type = GETPOST('type');
					$fk_unit=GETPOST('units', 'alpha');
				}
				// Margin
				$fournprice = price2num(GETPOST('fournprice' . $predef) ? GETPOST('fournprice' . $predef) : '');
				$buyingprice = price2num(GETPOST('buying_price' . $predef) != '' ? GETPOST('buying_price' . $predef) : '');    // If buying_price is '0', we muste keep this value

				// Local Taxes
				$localtax1_tx = get_localtax($tva_tx, 1, $object->thirdparty);
				$localtax2_tx = get_localtax($tva_tx, 2, $object->thirdparty);
				$desc = dol_htmlcleanlastbr($desc);

				$info_bits = 0;
				if ($tva_npr) $info_bits |= 0x01;
				if (! empty($price_min) && (price2num($pu_ht) * (1 - price2num($remise_percent) / 100) < price2num($price_min))) {
					$mesg = $langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, - 1, $conf->currency));
					setEventMessages($mesg, null, 'errors');
				} else {
					// Insert line
					$result = $object->addline($desc, $pu_ht, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, $idprod, $remise_percent, $info_bits, 0, $price_base_type, $pu_ttc, $date_start, $date_end, $type, - 1, 0, GETPOST('fk_parent_line'), $fournprice, $buyingprice, $label, $array_options, $fk_unit);

					if ($result > 0) {
						$ret = $object->fetch($object->id); // Reload to get new records
						if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
							// Define output language
							$outputlangs = $langs;
							$newlang = GETPOST('lang_id', 'alpha');
							if (! empty($conf->global->MAIN_MULTILANGS) && empty($newlang)) $newlang = $object->thirdparty->default_lang;
							if (! empty($newlang)) {
								$outputlangs = new Translate("", $conf);
								$outputlangs->setDefaultLang($newlang);
							}

							$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
						}

						unset($_POST['prod_entry_mode']);

						unset($_POST['qty']);
						unset($_POST['type']);
						unset($_POST['remise_percent']);
						unset($_POST['price_ht']);
						unset($_POST['multicurrency_price_ht']);
						unset($_POST['price_ttc']);
						unset($_POST['tva_tx']);
						unset($_POST['product_ref']);
						unset($_POST['product_label']);
						unset($_POST['product_desc']);
						unset($_POST['fournprice']);
						unset($_POST['buying_price']);
						unset($_POST['np_marginRate']);
						unset($_POST['np_markRate']);
						unset($_POST['dp_desc']);
						unset($_POST['idprod']);
						unset($_POST['units']);
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
					} else {
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}
			}
			header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=recalc&prixtot='.$px_org); // Pour reaffichage de la fiche en cours d'edition
			exit();
		}

		/*
		 *  Update a line
		 */
		if ($action == 'updateline' && $user->rights->commande->creer && GETPOST('save')) {
			$px_org=$object->total_ht;
			$object->fetch_lines();
			// Clean parameters
			$date_start='';
			$date_end='';
			$date_start=dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
			$date_end=dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
			$description=dol_htmlcleanlastbr(GETPOST('product_desc'));
			$pu_ht=GETPOST('price_ht');
			$vat_rate=(GETPOST('tva_tx')?GETPOST('tva_tx'):0);

			// Define info_bits
			$info_bits = 0;
			if (preg_match('/\*/', $vat_rate)) $info_bits |= 0x01;
			// Define vat_rate
			$vat_rate = str_replace('*', '', $vat_rate);
			$localtax1_rate = get_localtax($vat_rate, 1, $object->thirdparty, $mysoc);
			$localtax2_rate = get_localtax($vat_rate, 2, $object->thirdparty, $mysoc);
			// Add buying price
			$fournprice = price2num(GETPOST('fournprice') ? GETPOST('fournprice') : '');
			$buyingprice = price2num(GETPOST('buying_price') != '' ? GETPOST('buying_price') : '');    // If buying_price is '0', we muste keep this value
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

			// Define special_code for special lines
			$special_code=GETPOST('special_code');
			if (! GETPOST('qty')) $special_code=3;
			// Check minimum price
			$productid = GETPOST('productid', 'int');
			if (! empty($productid)) {
				$product = new Product($db);
				$product->fetch($productid);
				$type = $product->type;
				$price_min = $product->price_min;
				if (! empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($object->thirdparty->price_level)) $price_min = $product->multiprices_min [$object->thirdparty->price_level];
				$label = ((GETPOST('update_label') && GETPOST('product_label')) ? GETPOST('product_label') : '');
				if ($price_min && (price2num($pu_ht) * (1 - price2num(GETPOST('remise_percent')) / 100) < price2num($price_min))) {
					setEventMessages($langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, - 1, $conf->currency)), null, 'errors');
					$error++;
				}
			} else {
				$type = GETPOST('type');
				$label = (GETPOST('product_label') ? GETPOST('product_label') : '');
				// Check parameters
				if (GETPOST('type') < 0) {
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
					$error++;
				}
			}

			if (! $error) {
				$result = $object->updateline(GETPOST('lineid'), $description, $pu_ht, GETPOST('qty'), GETPOST('remise_percent'), $vat_rate, $localtax1_rate, $localtax2_rate, 'HT', $info_bits, $date_start, $date_end, $type, GETPOST('fk_parent_line'), 0, $fournprice, $buyingprice, $label, $special_code, $array_options, GETPOST('units'));
				if ($result >= 0) {
					if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
						// Define output language
						$outputlangs = $langs;
						$newlang = '';
						if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id');
						if ($conf->global->MAIN_MULTILANGS && empty($newlang)) 	$newlang = $object->thirdparty->default_lang;
						if (! empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
						}

						$ret = $object->fetch($object->id); // Reload to get new records
						$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
					}

					unset($_POST['qty']);
					unset($_POST['type']);
					unset($_POST['productid']);
					unset($_POST['remise_percent']);
					unset($_POST['price_ht']);
					unset($_POST['multicurrency_price_ht']);
					unset($_POST['price_ttc']);
					unset($_POST['tva_tx']);
					unset($_POST['product_ref']);
					unset($_POST['product_label']);
					unset($_POST['product_desc']);
					unset($_POST['fournprice']);
					unset($_POST['buying_price']);
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

				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}

			header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=recalc&prixtot='.$px_org); // Pour reaffichage de la fiche en cours d'edition
			exit();

		}

		if ($action == 'updateline' && $user->rights->commande->creer && GETPOST('cancel') == $langs->trans('Cancel')) {
			header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id); // Pour reaffichage de la fiche en cours d'edition
			exit();

		}

		if ($action == 'confirm_validate' && $confirm == 'yes' && ((empty ( $conf->global->MAIN_USE_ADVANCED_PERMS ) && ! empty ( $user->rights->commande->creer )) || (! empty ( $conf->global->MAIN_USE_ADVANCED_PERMS ) && ! empty ( $user->rights->commande->order_advance->validate )))) {

			$result = $object->valid ( $user, $idwarehouse );
			if ($result >= 0) {
				// Define output language
				if (empty ( $conf->global->MAIN_DISABLE_PDF_AUTOUPDATE )) {
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty ( $newlang ) && GETPOST ( 'lang_id' ))
						$newlang = GETPOST ( 'lang_id', 'alpha' );
						if ($conf->global->MAIN_MULTILANGS && empty ( $newlang ))
							$newlang = $object->thirdparty->default_lang;
							if (! empty ( $newlang )) {
								$outputlangs = new Translate ( "", $conf );
								$outputlangs->setDefaultLang ( $newlang );
							}
							$model = $object->modelpdf;
							$ret = $object->fetch ( $id ); // Reload to get new records
							$object->generateDocument ( $model, $outputlangs, $hidedetails, $hidedesc, $hideref );
				}
			} else {
				setEventMessages ( $object->error, $object->errors, 'errors' );
			}
		}

		if ($action == 'confirm_modif' && $user->rights->commande->creer) {
			$result = $object->set_draft ( $user, $idwarehouse );
			if ($result >= 0) {
				// Define output language
				if (empty ( $conf->global->MAIN_DISABLE_PDF_AUTOUPDATE )) {
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty ( $newlang ) && GETPOST ( 'lang_id' ))
						$newlang = GETPOST ( 'lang_id', 'alpha' );
						if ($conf->global->MAIN_MULTILANGS && empty ( $newlang ))
							$newlang = $object->thirdparty->default_lang;
							if (! empty ( $newlang )) {
								$outputlangs = new Translate ( "", $conf );
								$outputlangs->setDefaultLang ( $newlang );
							}
							$model = $object->modelpdf;
							$ret = $object->fetch ( $id ); // Reload to get new records
							$object->generateDocument ( $model, $outputlangs, $hidedetails, $hidedesc, $hideref );
				}
			}

		}

		if ($action == 'confirm_shipped' && $confirm == 'yes' && $user->rights->commande->cloturer) {
			$ok =0;
			$ok = $object->find_dt_cmd('dt_ship');
			$action='';
			if(empty($ok)){
				dol_include_once('/volvo/class/commandevolvo.class.php');
				$ordervolvo=new CommandeVolvo($db);
				$ordervolvo->fetch($object->id);
				$ordervolvo->date_cloture=dol_mktime(0, 0, 0, GETPOST('date_cloturemonth'), GETPOST('date_clotureday'), GETPOST('date_clotureyear'));
				$result=$ordervolvo->cloture($user);
				if ($result<0) {
					setEventMessages(null, $ordervolvo->errors,'errors');
				}
			}
		}

		if ($action == 'confirm_cancel' && $confirm == 'yes' && ((empty ( $conf->global->MAIN_USE_ADVANCED_PERMS ) && ! empty ( $user->rights->commande->creer )) || (! empty ( $conf->global->MAIN_USE_ADVANCED_PERMS ) && ! empty ( $user->rights->commande->order_advance->validate )))) {
			$result = $object->cancel ( $idwarehouse );

			if ($result < 0) {
				setEventMessages ( $object->error, $object->errors, 'errors' );
			}
		}


		if ($action == 'builddoc'){
			// Save last template used to generate document
			if (GETPOST('model')) $object->setDocModel($user, GETPOST('model', 'alpha'));
			if (GETPOST('fk_bank')) { // this field may come from an external module
				$object->fk_bank = GETPOST('fk_bank');
			} else {
				$object->fk_bank = $object->fk_account;
			}

			// Define output language
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))	$newlang = $_REQUEST['lang_id'];
			if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang = $object->thirdparty->default_lang;
			if (! empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}
			$result = $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result <= 0){
				setEventMessages($object->error, $object->errors, 'errors');
				$action='';
			}
		}

		// Remove file in doc form
		if ($action == 'remove_file') {
			if ($object->id > 0){
				require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
				$langs->load("other");
				$upload_dir = $conf->commande->dir_output;
				$file = $upload_dir . '/' . GETPOST('file');
				$ret = dol_delete_file($file, 0, 0, 0, $object);
				if ($ret) {
					setEventMessages($langs->trans("FileWasRemoved", GETPOST('file')), null, 'mesgs');
				}else{
					setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), null, 'errors');
					$action = '';
				}
			}
		}

		if ($action == 'update_extras')	{
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
				$numom  = GETPOST('options_numom');
			} else {
				$numom = $object->array_options['options_numom'];
			}
			if (isset($_POST['options_ctm'])){
				$ctm  = GETPOST('options_ctm','alpha');
			} else {
				$ctm = $object->array_options['options_ctm'];
			}

			if ($object->array_options['options_vin'] != $vin || $object->array_options['options_immat'] != $immat|| $object->array_options['options_numom'] != $numom || $object->array_options['options_ctm'] != $ctm) {
				if(!empty($object->array_options['options_ctm'])){
					dol_include_once('/societe/class/societe.class.php');
					$socctm = New Societe($db);
					$socctm->fetch($object->array_options['options_ctm']);
					$note = 'Client: ' . $object->thirdparty->name . "\n";
					$note.= 'Contremarque: ' . $socctm->name . "\n";
					$note.= 'N° de Chassis :' . $vin . "\n";
					$note.= 'Immatriculation :' . $immat . "\n";
					$note.= 'Date de Livraison :' . dol_print_date($object->date_livraison, 'daytext');
				} else {
					$note = 'Client: ' . $object->thirdparty->name . "\n";
					$note.= 'N° de Chassis :' . $vin . "\n";
					$note.= 'Immatriculation :' . $immat . "\n";
					$note.= 'Date de Livraison :' . dol_print_date($object->date_livraison, 'daytext');
				}
				$object->update_note($note,'_public');
				dol_include_once('/volvo/lib/volvo.lib.php');
				Update_vh_info_from_custorder($object->id,$vin , $immat,$numom,$ctm,$note,1,$oject->id);
			}

			// Fill array 'array_options' with data from update form
			$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
			$ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute'));
			if ($ret < 0) $error++;

			if (! $error){
				// Actions on extra fields (by external module or standard code)
				// TODO le hook fait double emploi avec le trigger !!
				$hookmanager->initHooks(array('orderdao'));
				$parameters = array('id' => $object->id);
				$reshook = $hookmanager->executeHooks('insertExtraFields', $parameters, $object, $action); // Note that $action and $object may have been modified by
				// some hooks
				if (empty($reshook)) {
					$result = $object->insertExtraFields();
					if ($result < 0) {
						$error++;
					}
				} else if ($reshook < 0)
					$error++;
			}

			if ($error) $action = 'edit_extras';
		}

		include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';


		/*
		 * Send mail
		 */

		// Actions to send emails
		$actiontypecode='AC_COM';
		$trigger_name='ORDER_SENTBYMAIL';
		$paramname='id';
		$mode='emailfromorder';
		include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
		if (! $error && ! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && $user->rights->commande->creer){
			if ($action == 'addcontact'){
				if ($object->id > 0) {
					$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
					$result = $object->add_contact($contactid, GETPOST('type'), GETPOST('source'));
				}

				if ($result >= 0) {
					header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id);
					exit();
				} else {
					if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
						$langs->load("errors");
						setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
					} else {
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}
			}

			// bascule du statut d'un contact
			else if ($action == 'swapstatut'){
				if ($object->id > 0) {
					$result = $object->swapContactStatus(GETPOST('ligne'));
				} else {
					dol_print_error($db);
				}
			}

			// Efface un contact
			else if ($action == 'deletecontact'){
				$result = $object->delete_contact($lineid);
				if ($result >= 0) {
					header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id);
					exit();
				} else {
					dol_print_error($db);
				}
			}
		}

		/*
		 *	View
		 */

		llxHeader('', $langs->trans('Order'), 'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes');

		$form = new Form($db);
		$formfile = new FormFile($db);
		$formorder = new FormOrder($db);
		$formmargin = new FormMargin($db);


		/**
		 * *******************************************************************
		 *
		 * Mode creation
		 *
		 * *******************************************************************
		 */
		if ($action == 'create' && $user->rights->commande->creer)
		{
			print load_fiche_titre($langs->trans('CreateOrder'),'','title_commercial.png');

			$soc = new Societe($db);
			if ($socid > 0)
				$res = $soc->fetch($socid);

				$projectid = 0;
				$remise_absolue = 0;

				$currency_code = $conf->currency;
				$cond_reglement_id = $soc->cond_reglement_id;
				$mode_reglement_id = $soc->mode_reglement_id;
				$fk_account = $soc->fk_account;
				$availability_id = $soc->availability_id;
				$demand_reason_id = $soc->demand_reason_id;
				$remise_percent = $soc->remise_percent;
				$remise_absolue = 0;
				$dateorder = empty ( $conf->global->MAIN_AUTOFILL_DATE_ORDER ) ? - 1 : '';

				$note_private = $object->getDefaultCreateValueFor ( 'note_private' );
				$note_public = $object->getDefaultCreateValueFor ( 'note_public' );

				$absolute_discount=$soc->getAvailableDiscounts();

				$nbrow = 10;

				print '<form name="crea_commande" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
				print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
				print '<input type="hidden" name="action" value="add">';
				print '<input type="hidden" name="socid" value="' . $soc->id . '">' . "\n";
				print '<input type="hidden" name="remise_percent" value="' . $soc->remise_percent . '">';
				print '<input type="hidden" name="origin" value="' . $origin . '">';
				print '<input type="hidden" name="originid" value="' . $originid . '">';

				dol_fiche_head('');

				print '<table class="border" width="100%">';

				// Reference
				print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans('Ref') . '</td><td colspan="2">' . $langs->trans("Draft") . '</td></tr>';

				// Reference client
				print '<tr><td>' . $langs->trans('RefCustomer') . '</td><td colspan="2">';
				print '<input type="text" name="ref_client" value="'.GETPOST('ref_client').'"></td>';
				print '</tr>';

				// Client
				print '<tr>';
				print '<td class="fieldrequired">' . $langs->trans('Customer') . '</td>';
				if ($socid > 0) {
					print '<td colspan="2">';
					print $soc->getNomUrl(1);
					print '<input type="hidden" name="socid" value="' . $soc->id . '">';
					print '</td>';
				} else {
					print '<td colspan="2">';
					print $form->select_company('', 'socid', 's.client = 1 OR s.client = 3', 'SelectThirdParty');
					// reload page to retrieve customer informations
					if (!empty($conf->global->RELOAD_PAGE_ON_CUSTOMER_CHANGE))
					{
						print '<script type="text/javascript">
			$(document).ready(function() {
				$("#socid").change(function() {
					var socid = $(this).val();
					// reload page
					window.location.href = "'.$_SERVER["PHP_SELF"].'?action=create&socid="+socid+"&ref_client="+$("input[name=ref_client]").val();
				});
			});
			</script>';
					}
					print '</td>';
				}
				print '</tr>' . "\n";

				// Date
				print '<tr><td class="fieldrequired">' . $langs->trans('Date') . '</td><td colspan="2">';
				$form->select_date('', 're', '', '', '', "crea_commande", 1, 1);			// Always autofill date with current date
				print '</td></tr>';

				// Delivery date planed
				print "<tr><td>".$langs->trans("DateDeliveryPlanned").'</td><td colspan="2">';
				if (empty($datedelivery))
				{
					if (! empty($conf->global->DATE_LIVRAISON_WEEK_DELAY)) $datedelivery = time() + ((7*$conf->global->DATE_LIVRAISON_WEEK_DELAY) * 24 * 60 * 60);
					else $datedelivery=empty($conf->global->MAIN_AUTOFILL_DATE_DELIVERY)?-1:'';
				}
				$form->select_date($datedelivery, 'liv_', '', '', '', "crea_commande", 1, 1);
				print "</td></tr>";

				// Conditions de reglement
				print '<tr><td class="nowrap">' . $langs->trans('PaymentConditionsShort') . '</td><td colspan="2">';
				$form->select_conditions_paiements($cond_reglement_id, 'cond_reglement_id', - 1, 1);
				print '</td></tr>';

				// Mode de reglement
				print '<tr><td>' . $langs->trans('PaymentMode') . '</td><td colspan="2">';
				$form->select_types_paiements($mode_reglement_id, 'mode_reglement_id');
				print '</td></tr>';

				// Bank Account
				if (! empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_ORDER) && ! empty($conf->banque->enabled))
				{
					print '<tr><td>' . $langs->trans('BankAccount') . '</td><td colspan="2">';
					$form->select_comptes($fk_account, 'fk_account', 0, '', 1);
					print '</td></tr>';
				}

				// Delivery delay
				print '<tr class="fielddeliverydelay"><td>' . $langs->trans('AvailabilityPeriod') . '</td><td colspan="2">';
				$form->selectAvailabilityDelay($availability_id, 'availability_id', '', 1);
				print '</td></tr>';

				// What trigger creation
				print '<tr><td>' . $langs->trans('Lieu de Livraison') . '</td><td colspan="2">';
				$form->selectInputReason($demand_reason_id, 'demand_reason_id', '', 1);
				print '</td></tr>';

				// Other attributes
				$parameters = array('objectsrc' => $objectsrc, 'colspan' => ' colspan="3"', 'socid'=>$socid);
				//hook formobject option

				// Template to use by default
				print '<tr><td>' . $langs->trans('Model') . '</td>';
				print '<td colspan="2">';
				include_once DOL_DOCUMENT_ROOT . '/core/modules/commande/modules_commande.php';
				$liste = ModelePDFCommandes::liste_modeles($db);
				print $form->selectarray('model', $liste, $conf->global->COMMANDE_ADDON_PDF);
				print "</td></tr>";

				// Note public
				print '<tr>';
				print '<td class="border" valign="top">' . $langs->trans('NotePublic') . '</td>';
				print '<td valign="top" colspan="2">';

				$doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
				print $doleditor->Create(1);
				print '</td></tr>';

				// Note private
				if (empty($user->societe_id)) {
					print '<tr>';
					print '<td class="border" valign="top">' . $langs->trans('NotePrivate') . '</td>';
					print '<td valign="top" colspan="2">';

					$doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
					print $doleditor->Create(1);
					// print '<textarea name="note" wrap="soft" cols="70" rows="'.ROWS_3.'">'.$note_private.'</textarea>';
					print '</td></tr>';
				}
				if (! empty ( $conf->global->PRODUCT_SHOW_WHEN_CREATE )) {
					/*
					 * Services/produits predefinis
					 */
					$NBLINES = 8;

					print '<tr><td colspan="3">';

					print '<table class="noborder">';
					print '<tr><td>' . $langs->trans ( 'ProductsAndServices' ) . '</td>';
					print '<td>' . $langs->trans ( 'Qty' ) . '</td>';
					print '<td>' . $langs->trans ( 'ReductionShort' ) . '</td>';
					print '</tr>';
					for($i = 1; $i <= $NBLINES; $i ++) {
						print '<tr><td>';
						// multiprix
						if (! empty ( $conf->global->PRODUIT_MULTIPRICES ))
							print $form->select_produits ( '', 'idprod' . $i, '', $conf->product->limit_size, $soc->price_level );
							else
								print $form->select_produits ( '', 'idprod' . $i, '', $conf->product->limit_size );
								print '</td>';
								print '<td><input type="text" size="3" name="qty' . $i . '" value="1"></td>';
								print '<td><input type="text" size="3" name="remise_percent' . $i . '" value="' . $soc->remise_percent . '">%</td></tr>';
					}

					print '</table>';
					print '</td></tr>';
				}


				print '</table>';

				dol_fiche_end();

				// Button "Create Draft"
				print '<div class="center">';
				print '<input type="submit" class="button" name="bouton" value="' . $langs->trans('CreateDraft') . '">';
				print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				print '<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
				print '</div>';

				print '</form>';


		} else {
			/* *************************************************************************** */
			/*                                                                             */
			/* Mode vue et edition                                                         */
			/*                                                                             */
			/* *************************************************************************** */
			$now = dol_now();

			if ($object->id > 0) {

				dol_include_once('/volvo/class/commandevolvo.class.php');
				$commandevolvo= new CommandeVolvo($db);

				$result=$commandevolvo->getCostPriceReal($object->id, 'real');
				if ($result<0){
					setEventMessage($commandevolvo->error,'errors');
				} else {
					$total_real_paht=$commandevolvo->total_real_paht;
				}

				$html = '<tr class="impair">';

				$html .= '<td>Marge rééle </td>';
				$html .= '<td align="right">' . price(round($object->total_ht,2)) . '</td>';
				$html .= '<td align="right">' . price(round($total_real_paht,2)) . '</td>';
				$html .= '<td align="right">' . price(round($object->total_ht - $total_real_paht,2)) . '</td>';

				if (! empty($conf->global->DISPLAY_MARGIN_RATES)) {
					$html .= '<td align="right"></td>';
				}

				if (! empty($conf->global->DISPLAY_MARK_RATES)) {
					$html .= '<td align="right"></td>';
				}

				$out = '<script type="text/javascript">' . "\n";
				$out .= '  	$(document).ready(function() {' . "\n";
				$out .= '	  		$tr = $(\'' . $html . '\');' . "\n";
				$out .= '	  		$(\'div.fiche table.margintable\').last().append($tr);' . "\n";
				$out .= '  	});' . "\n";
				$out .= '</script>' . "\n";
				print $out;

				$product_static = new Product($db);

				$soc = new Societe($db);
				$soc->fetch($object->socid);

				$author = new User($db);
				$author->fetch($object->user_author_id);

				$res = $object->fetch_optionals($object->id, $extralabels);

				$head = commande_prepare_head_custom($object);
				dol_fiche_head($head, 'order', $langs->trans("CustomerOrder"), 0, 'order');

				$formconfirm = '';

				/*
				 * Confirmation de la suppression de la commande
				 */
				if ($action == 'delete') {
					$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteOrder'), $langs->trans('ConfirmDeleteOrder'), 'confirm_delete', '', 0, 1);
				}

				/*
				 * Confirmation de la validation
				 */
				if ($action == 'validate')
				{
					// on verifie si l'objet est en numerotation provisoire
					$ref = substr($object->ref, 1, 4);
					if ($ref == 'PROV') {
						$numref = $object->getNextNumRef($soc);
					} else {
						$numref = $object->ref;
					}

					$text = $langs->trans('ConfirmValidateOrder', $numref);
					if (! empty($conf->notification->enabled))
					{
						require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
						$notify = new Notify($db);
						$text .= '<br>';
						$text .= $notify->confirmMessage('ORDER_VALIDATE', $object->socid, $object);
					}

					$formquestion=array();
					$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ValidateOrder'), $text, 'confirm_validate', $formquestion, 0, 1, 220);
				}

				// Confirm back to draft status
				if ($action == 'modif')
				{
					$text=$langs->trans('ConfirmUnvalidateOrder',$object->ref);
					$formquestion=array();
					$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('UnvalidateOrder'), $text, 'confirm_modif', $formquestion, "yes", 1, 220);
				}

				/*
				 * Confirmation de la cloture
				 */
				$ok =0;
				$ok = $object->find_dt_cmd('dt_ship');
				if ($action == 'shipped' && empty($ok)) {
					$form = new Form($db);
					$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CloseOrder'), $langs->trans('ConfirmCloseOrder'), 'confirm_shipped', array(array(
							'type' => 'date',
							'name' => 'date_cloture',
							'label'=>$langs->trans('DateClosed')
					)), '', 1);
				}

				$ok =0;
				$ok=$object->find_dt_cmd('dt_bill');
				if ($action == 'classifybilled'&& empty($ok)) {
					$form = new Form($db);
					$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('BilledOrder'), $langs->trans('ConfirmBilledOrder'), 'confirm_billed', array(array(
							'type' => 'date',
							'name' => 'date_billed',
							'label'=>$langs->trans('DateBilled')
					)), '', 1);
				}

				$ok =0;
				$ok=$object->find_dt_cmd('dt_pay');
				if ($action == 'setpayed'&& empty($ok)) {
					$form = new Form($db);
					$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ClassifyPayed'), $langs->trans('ConfirmPayedOrder'), 'confirm_payed', array(array(
							'type' => 'date',
							'name' => 'date_payed',
							'label'=>$langs->trans('DatePayed')
					)), '', 1);
				}

				if ($action == 'update_pv') {
					$form = new Form($db);
					$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, 'Modifier prix de vente', '', 'recalc', array(array(
							'type' => 'text',
							'name' => 'prixtot',
							'label'=>'Nouveau prix de vente ?'
					)), '', 1);
				}

				// Confirmation to delete line

				if ($action == 'ask_deleteline'){
					$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 0, 1);
				}

				// Clone confirmation
				if ($action == 'clone') {
					$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CloneOrder'), $langs->trans('ConfirmCloneOrder', $object->ref), 'confirm_clone', '', 'yes', 1);
				}

				if ($action == 'cancel')
				{
					$text=$langs->trans('ConfirmCancelOrder',$object->ref);
					$formquestion=array();
					$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('Cancel'), $text, 'confirm_cancel', $formquestion, 0, 1);
				}

				// Print form confirm
				print $formconfirm;

				/*
				 *   Commande
				 */
				$nbrow = 9;
				$res = $object->fetch_optionals($object->id, $extralabels);
				print '<table class="border" width="100%">';

				$linkback = '<a href="' . DOL_URL_ROOT . '/commande/list.php' . (! empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

				// Ref
				print '<tr class="liste_titre">';


				print '<th Colspan="4" align ="center">  <strong style="font-size: 25px;">Dossier Commercial N° : ' . $object->ref . ' du: ';
				print  dol_print_date($object->date, 'daytext');
				if ($object->hasDelay() && empty($object->date_livraison)) {
					print ' '.img_picto($langs->trans("Late").' : '.$object->showDelay(), "warning");
				}
				print '</Strong></th></tr>';


				print '<tr style="height:25px">';

				// Numero affaire
				print '<td><table width="100%" class="nobordernopadding"><tr><td align ="lefr">' . "Numéro d'affaire: ";
				print $object->ref_client ."</td></tr></table></td>";

				// Numéro D'OM
				$key = 'numom';
				$label = $extrafields->attribute_label[$key];
				include dol_buildpath('/affaires/tpl/extra_inline.php');


				// Third party
				print '<td><table width="100%" class="nobordernopadding"><tr><td align ="left">' . "Client: ";
				print $soc->getNomUrl(1, 'compta') ."</td></tr></table></td>";

				$key = 'ctm';
				$label = $extrafields->attribute_label[$key];
				include dol_buildpath('/affaires/tpl/extra_inline.php');

				print '</tr>';
				print '<tr  style="height:25px">';

				print '<td><table width="100%" class="nobordernopadding"><tr><td align ="lefr">' . "Date de facture: ";
				print dol_print_date($object->array_options['options_dt_invoice'], 'daytext') ."</td></tr></table></td>";

				// Terms of payment
				print '<td><table width="100%" class="nobordernopadding"><tr><td align ="left">';
				print $langs->trans('PaymentConditionsShort') . ': ';
				if ($action == 'editconditions') {
					$form->form_conditions_reglement($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->cond_reglement_id, 'cond_reglement_id', 1);
				} else {
					$form->form_conditions_reglement($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->cond_reglement_id, 'none', 1);
				}
				print '</td>';
				if ($action != 'editconditions' && $object->statut == 0) print '<td align="center"><a href="' . $_SERVER["PHP_SELF"] . '?action=editconditions&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetConditions'), 1) . '</a></td>';
				print'</tr></table>';
				print '</td>';

				// Date livraison
				print '<td height="10"><table width="100%" class="nobordernopadding"><tr><td align ="left">';
				print $langs->trans('DateDeliveryPlanned') . ': ';
				if ($action == 'editdate_livraison') {
					print '<form name="setdate_livraison" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
					print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
					print '<input type="hidden" name="action" value="setdate_livraison">';
					$form->select_date($object->date_livraison ? $object->date_livraison : - 1, 'liv_', '', '', '', "setdate_livraison");
					print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
					print '</form>';
				} else {
					print dol_print_date($object->date_livraison, 'daytext');
					if ($object->hasDelay() && ! empty($object->date_livraison)) {
						print ' '.img_picto($langs->trans("Late").' : '.$object->showDelay(), "warning");
					}
				}
				print '</td>';
				if ($action != 'editdate_livraison' && $object->statut == 0) print '<td align="center"><a href="' . $_SERVER["PHP_SELF"] . '?action=editdate_livraison&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetDeliveryDate'), 1) . '</a></td>';
				print'</tr></table>';
				print '</td>';

				// Lieu de livraison
				print '<td><table width="100%" class="nobordernopadding"><tr><td align ="left">';
				print $langs->trans('Lieu de Livraison') .': ';
				if ($action == 'editdemandreason') {
					$form->formInputReason($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->demand_reason_id, 'demand_reason_id', 1);
				} else {
					$form->formInputReason($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->demand_reason_id, 'none');
				}
				print '</td>';
				if ($action != 'editdemandreason' && $object->statut == 0) print '<td align="center"><a href="' . $_SERVER["PHP_SELF"] . '?action=editdemandreason&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetDemandReason'), 1) . '</a></td>';
				print'</tr></table>';
				print '</td>';

				print '</tr>';

				$rowspan = 3;
				print '<tr style="height:25px">';
				$key = 'vin';
				$label = $extrafields->attribute_label[$key];
				include dol_buildpath('/affaires/tpl/extra_inline.php');

				$key = 'immat';
				$label = $extrafields->attribute_label[$key];
				include dol_buildpath('/affaires/tpl/extra_inline.php');

				// Margin Infos
				if (! empty($conf->margin->enabled)) {
					print '<td valign="top" width="50%" colspan="2" rowspan="' . $rowspan . '">';
					$formmargin->displayMarginInfos($object);
					print '</td>';
				} else
					print '<td width="50%" colspan="2" rowspan="' . $rowspan . '"></td>';

					print '</tr>';

					print '<tr style="height:25px">';
					$key = 'vnac';
					$label = $extrafields->attribute_label[$key];
					include dol_buildpath('/affaires/tpl/extra_inline.php');

					print '<td>Délai Cash: ' . $object->get_cash() . ' Jour(s)</td>';
					print '</tr>';

					// Total HT
					print '<tr style="height:25px"><td>' . $langs->trans('AmountHT') . ': ';
					print price($object->total_ht, 1, '', 1, - 1, - 1, $conf->currency) . '</td>';

					print '<td><table width="100%" class="nobordernopadding"><tr><td align ="left">';
					print $object->getLibStatut(4) ."</td></tr></table></td>";
					print '</tr>';

					print '</table>';

					$parameters['showblocbydefault'] = true;
					if (! empty($conf->global->MAIN_DISABLE_NOTES_TAB)) {
						$blocname = 'notes';
						$title = $langs->trans('Notes');
						include DOL_DOCUMENT_ROOT . '/core/tpl/bloc_showhide.tpl.php';
					}

					/*
					 * Lines
					 */
					$result = $object->getLinesArray();

					print '	<form name="addproduct" id="addproduct" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . (($action != 'editline') ? '#add' : '#line_' . GETPOST('lineid')) . '" method="POST">
		<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline') . '">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="id" value="' . $object->id . '">
		';

					if (! empty($conf->use_javascript_ajax) && $object->statut == Commande::STATUS_DRAFT) {
						include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
					}

					print '<table id="tablelines" class="noborder noshadow" width="100%">';

					// Show object lines
					if (! empty($object->lines))
						$ret = $object->printObjectLines_perso($action, $mysoc, $soc, $lineid, 1);

						$numlines = count($object->lines);

						/*
						 * Form to add new line
						 */
						if ($object->statut == Commande::STATUS_DRAFT && $user->rights->commande->creer)
						{
							if ($action != 'editline')
							{
								$var = true;

								// Add free products/services
								$object->formAddObjectLine(1, $mysoc, $soc);

								$parameters = array();
								$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
							}
						}
						print '</table>';

						print "</form>\n";

						dol_fiche_end();

						/*
						 * Boutons actions
						 */
						if ($action != 'presend' && $action != 'editline') {
							print '<div class="tabsAction">';

							$parameters = array();
							$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
							// modified by hook
							if (empty($reshook)) {

								// Send
								$langs->load("volvo@volvo");
								$dt_ship = $object->find_dt_cmd('dt_ship');
								if(!empty($dt_ship)){
									$blocdate = new DateTime($db->idate($dt_ship));
								}else{
									$blocdate = new DateTime($db->idate(time()));
								}
								$interval = new DateInterval('P' . $conf->global->VOLVO_LOCK_DELAI . 'M');
								$blocdate->add($interval);

								if ($blocdate->getTimestamp()<=time()){
									print '<div class="inline-block divButAction">Commande verrouillée depuis le; '. $blocdate->format('d/m/Y') . '</div>';
								}

								if ($object->statut > Commande::STATUS_DRAFT) {
									if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->commande->order_advance->send)) {
										print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=presend&amp;mode=init">' . $langs->trans('SendByMail') . '</a></div>';
									} else
										print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">' . $langs->trans('SendByMail') . '</a></div>';
								}

								if ($user->rights->affaires->volvo->update_cost && $object->statut!=0 && $blocdate->getTimestamp()>=time()) {

									$out = '<script type="text/javascript">' . "\n";
									$out .= '  	$(document).ready(function() {' . "\n";
									$out .= '		$b = $(\'<a href="javascript:popSupplierOrder()" class="butAction">' . $langs->trans('CreateSupplierOrder') . '</a>\');' . "\n";
									$out .= '		$c = $(\'<a href="javascript:popAddProducts()" class="butAction">Ajouter un produit</a>\');' . "\n";
									$out .= '  		$(\'div.fiche div.tabsAction\').first().append($b);' . "\n";
									$out .= '  		$(\'div.fiche div.tabsAction\').first().append($c);' . "\n";
									$out .= '  	});' . "\n";
									$out .= '' . "\n";
									$out .= '' . "\n";
									$out .= '  	function popSupplierOrder() {' . "\n";
									$out .= '  		$divsupplier = $(\'<div id="popSupplierOrder"><iframe width="100%" height="100%" frameborder="0" src="' . dol_buildpath('/volvo/orders/createsupplierorder.php',2).'?orderid=' . $object->id . '"></iframe></div>\');' . "\n";
									$out .= '' . "\n";
									$out .= '  		$divsupplier.dialog({' . "\n";
									$out .= '  			modal:true' . "\n";
									$out .= '  			,width:"90%"' . "\n";
									$out .= '  			,height:$(window).height() - 150' . "\n";
									$out .= '  			,close:function() {document.location.reload(true);}' . "\n";
									$out .= '  		});' . "\n";
									$out .= '' . "\n";
									$out .= '  	}' . "\n";
									$out .= '  	function popAddProducts() {' . "\n";
									$out .= '  		$divsupplier = $(\'<div id="popAddProducts"><iframe width="100%" height="100%" frameborder="0" src="' . dol_buildpath('/affaires/form/addproduct.php',2).'?orderid=' . $object->id . '"></iframe></div>\');' . "\n";
									$out .= '' . "\n";
									$out .= '  		$divsupplier.dialog({' . "\n";
									$out .= '  			modal:true' . "\n";
									$out .= '  			,width:500' . "\n";
									$out .= '  			,height:200' . "\n";
									$out .= '  			,close:function() {document.location.reload(true);}' . "\n";
									$out .= '  		});' . "\n";
									$out .= '' . "\n";
									$out .= '  	}' . "\n";
									$out .= '</script>';
									print $out;
								}
								if ($object->statut == Commande::STATUS_DRAFT && $user->rights->volvo->update_cost) {
									print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=update_pv">Modifier le Prix de vente</a></div>';
								}

								// Set to shipped
								$ok =0;
								$ok=$object->find_dt_cmd('dt_ship');
								if ($object->statut > Commande::STATUS_DRAFT && $user->rights->commande->cloturer && empty($ok)) {
									print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=shipped">' . $langs->trans('ClassifyShipped') . '</a></div>';
								}

								// Create bill and Classify billed
								// Note: Even if module invoice is not enabled, we should be able to use button "Classified billed"
								if ($object->statut > Commande::STATUS_DRAFT && ! $object->billed) {
									if ($user->rights->commande->creer && $object->statut >= Commande::STATUS_VALIDATED && empty($conf->global->WORKFLOW_DISABLE_CLASSIFY_BILLED_FROM_ORDER) && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)) {
										print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=classifybilled">' . $langs->trans("ClassifyBilled") . '</a></div>';
									}
								}
								if ($object->statut > Commande::STATUS_DRAFT && $object->billed) {
									if ($user->rights->commande->creer && $object->statut >= Commande::STATUS_VALIDATED && empty($conf->global->WORKFLOW_DISABLE_CLASSIFY_BILLED_FROM_ORDER) && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)) {
										print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=classifyunbilled">' . $langs->trans("ClassifyUnBilled") . '</a></div>';
									}
								}
								// Set to payed
								$ok =0;
								$ok=$object->find_dt_cmd('dt_pay');
								if ($object->statut > Commande::STATUS_DRAFT && $user->rights->commande->cloturer && empty($ok)) {
									print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=setpayed"> Classée Payée</a></div>';
								}


								// Valid
								$ok = volvo_vcm_ok($object);
								if ($object->statut == Commande::STATUS_DRAFT && $object->total_ttc >= 0 && $numlines > 0 && ($ok==1||$ok<0) &&
										((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->creer))
												|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->order_advance->validate)))
										)
								{
									print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=validate">' . $langs->trans('Validate') . '</a></div>';
								}
								// Edit
								if ($object->statut == Commande::STATUS_VALIDATED && $user->rights->commande->creer && $blocdate->getTimestamp()>=time()) {
									print '<div class="inline-block divButAction"><a class="butAction" href="card.php?id=' . $object->id . '&amp;action=modif">' . $langs->trans('Modify') . '</a></div>';
								}

								// Create contract
								// 				$ok = 0;

								// 				//$ok = $leadext->contrat_needed($object->id);
								// 				if ($conf->contrat->enabled && $ok>0 && ($object->statut == Commande::STATUS_VALIDATED || $object->statut == Commande::STATUS_ACCEPTED || $object->statut == Commande::STATUS_CLOSED)) {
								// 				    $langs->load("contracts");

								// 				    if ($user->rights->contrat->creer) {
								// 				        print '<div class="inline-block divButAction"><a class="butAction" href="card.php?id=' . $object->id . '&amp;action=create_contrat">Créer Contrat</a></div>';
								// 				    }
								// 				}

								// Clone
								if ($user->rights->commande->creer) {
									print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;socid=' . $object->socid . '&amp;action=clone&amp;object=order">' . $langs->trans("ToClone") . '</a></div>';
								}

								// Delete order
								if ($user->rights->commande->supprimer && $blocdate->getTimestamp()>=time()) {
									print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=delete">' . $langs->trans('Delete') . '</a></div>';
								}

								// Cancel order
								if ($object->statut == Commande::STATUS_VALIDATED &&
										((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->cloturer))
												|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->order_advance->annuler)))
										)
								{
									print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=cancel">' . $langs->trans('Cancel') . '</a></div>';
								}


						}
						print '</div>';
			}

			// Select mail models is same action as presend
			if (GETPOST('modelselected')) {
				$action = 'presend';
			}

			if ($action != 'presend')
			{
				print '<div class="fichecenter"><div class="fichehalfleft">';

				// Documents
				$comref = dol_sanitizeFileName($object->ref);
				$file = $conf->commande->dir_output . '/' . $comref . '/' . $comref . '.pdf';
				$relativepath = $comref . '/' . $comref . '.pdf';
				$filedir = $conf->commande->dir_output . '/' . $comref;
				$urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
				$genallowed = $user->rights->commande->creer;
				$delallowed = $user->rights->commande->creer;
				$somethingshown = $formfile->show_documents('commande', $comref, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang);

				// Linked object block
				$somethingshown = $form->showLinkedObjectBlock($object);

				print '</div><div class="fichehalfright"><div class="ficheaddleft">';

				// List of actions on element
				include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
				$formactions = new FormActions($db);
				$somethingshown = $formactions->showactions($object, 'order', $socid);

				print '</div></div></div>';
			}

			/*
			 * Action presend
			 */
			if ($action == 'presend')
			{
				$object->fetch_projet();

				$ref = dol_sanitizeFileName($object->ref);
				include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
				$fileparams = dol_most_recent_file($conf->commande->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
				$file = $fileparams['fullname'];

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
						if (! $file || ! is_readable($file)) {
							$result = $object->generateDocument(GETPOST('model') ? GETPOST('model') : $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
							if ($result <= 0) {
								dol_print_error($db, $object->error, $object->errors);
								exit();
							}
							$fileparams = dol_most_recent_file($conf->commande->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
							$file = $fileparams['fullname'];
						}

						print '<div class="clearboth"></div>';
						print '<br>';
						print load_fiche_titre($langs->trans('SendOrderByMail'));

						dol_fiche_head('');

						// Cree l'objet formulaire mail
						include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
						$formmail = new FormMail($db);
						$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
						$formmail->fromtype = 'user';
						$formmail->fromid = $user->id;
						$formmail->fromname = $user->getFullName($langs);
						$formmail->frommail = $user->email;
						$formmail->trackid='ord'.$object->id;
						if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
						{
							include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
							$formmail->frommail=dolAddEmailTrackId($formmail->frommail, 'ord'.$object->id);
						}
						$formmail->withfrom = 1;
						$liste = array();
						foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key => $value)
							$liste [$key] = $value;
							$formmail->withto = GETPOST('sendto') ? GETPOST('sendto') : $liste;
							$formmail->withtocc = $liste;
							$formmail->withtoccc = $conf->global->MAIN_EMAIL_USECCC;
							$topic = 'Commande ' . $object->ref;
							if(!empty($object->array_options['options_ctm'])){
								$idsoc = $object->array_options['options_ctm'];
							}else{
								$idsoc = $object->socid;
							}
							$soc = new societe($db);
							$soc->fetch($idsoc);
							$topic .=' - client:' . $soc->name;
							if(!empty($object->array_options['options_vin'])){
								$topic .=' - Chassis:' . substr($object->array_options['options_vin'], -7);
							}
							if(!empty($object->array_options['options_immat'])){
								$topic .=' - Immat:' . $object->array_options['options_immat'];
							}


							$formmail->withtopic = $topic;

							$formmail->withfile = 2;
							$formmail->withbody = 1;
							$formmail->withdeliveryreceipt = 1;
							$formmail->withcancel = 1;
							// Tableau des substitutions
							$formmail->setSubstitFromObject($object);
							$formmail->substit ['__ORDERREF__'] = $object->ref;

							$custcontact = '';
							$contactarr = array();
							$contactarr = $object->liste_contact(- 1, 'external');

							if (is_array($contactarr) && count($contactarr) > 0)
							{
								foreach ($contactarr as $contact)
								{
									if ($contact['libelle'] == $langs->trans('TypeContact_commande_external_CUSTOMER')) {	// TODO Use code and not label
										$contactstatic = new Contact($db);
										$contactstatic->fetch($contact ['id']);
										$custcontact = $contactstatic->getFullName($langs, 1);
									}
								}

								if (! empty($custcontact)) {
									$formmail->substit['__CONTACTCIVNAME__'] = $custcontact;
								}
							}

							// Tableau des parametres complementaires
							$formmail->param['action'] = 'send';
							$formmail->param['models'] = 'order_send';
							$formmail->param['models_id']=GETPOST('modelmailselected','int');
							$formmail->param['orderid'] = $object->id;
							$formmail->param['returnurl'] = $_SERVER["PHP_SELF"] . '?id=' . $object->id;

							// Init list of files
							if (GETPOST("mode") == 'init') {
								$formmail->clear_attached_files();
								$comref = dol_sanitizeFileName($object->ref);
								$files = dol_dir_list($conf->commande->dir_output . '/' . $comref);
								foreach($files as $linked){
									$formmail->add_attached_files($linked['fullname'], basename($linked['fullname']), dol_mimetype($linked['fullname']));
								}


							}

							// Show form
							print $formmail->get_form();

							dol_fiche_end();
			}
		}
}

llxFooter();
$db->close();
