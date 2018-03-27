<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015	   Claudio Aschieri		<c.aschieri@19.coop>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2016      Ferran Marcet        <fmarcet@2byte.es>
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

/**
 *       \file       htdocs/contrat/list.php
 *       \ingroup    contrat
 *       \brief      Page liste des contrats
 */


$res = @include '../../main.inc.php'; // For root directory
if (! $res)
	$res = @include '../../../main.inc.php'; // For "custom" directory
if (! $res)
	die("Include of main fails");

// Security check
if (! $user->rights->volvo->contrat)
	accessforbidden();

require_once DOL_DOCUMENT_ROOT . '/volvo/class/table_template.class.php';
require_once (DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");

$staticcontrat=new Contrat($db);
$staticcontratligne=new ContratLigne($db);
$table = New Dyntable($db);

$langs->load("contracts");
$langs->load("products");
$langs->load("companies");
$langs->load("compta");

$action = GETPOST('action');
$element = GETPOST('element');
$id=GETPOST('id');

$table->title = $langs->trans("ListOfContracts");
$table->default_sortfield = 'c.ref';
$table->export_name = 'liste_contrat_new';
$table->context = 'contractlist';
$table->search_button = 1;
$table->remove_filter_button = 1;
$table->export_button = 1;
$table->select_fields_button = 1;
$table->mode = 'sql_methode';
$table->limit = $conf->liste_limit;
$table->filter_clause = 'HAVING';
$table->filter_mode = 'AND ';
$table->filter_line = 1;

$field= new Dyntable_fields($db);
$field->name='ref';
$field->label = 'N° de Contrat';
$field->checked = 1;
$field->sub_title = 0;
$field->field = 'c.ref';
$field->group =1;
$field->align = 'left';
$field->alias = 'ref';
$field->post_traitement = array('link', '/contrat/card.php','?id=','cid');
$tools=array();
$tool = new Dyntable_tools($db);
$tool->type = 'text';
$tool->title = '';
$tool->html_name = 'search_ref';
$tool->filter = 'c.ref';
$tool->size = 7;
$tools['1'] = $tool;
$field->filter = $tools;
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='immat';
$field->label = 'Immat';
$field->checked = 1;
$field->sub_title = 0;
$field->field = 'c.ref_customer';
$field->group =1;
$field->align = 'center';
$field->alias = 'ref_customer';
$field->post_traitement = array('none');
$tools=array();
$tool = new Dyntable_tools($db);
$tool->type = 'text';
$tool->title = '';
$tool->html_name = 'search_immat';
$tool->filter = 'c.ref_customer';
$tool->size = 4;
$tools['1'] = $tool;
$field->filter = $tools;
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='vin';
$field->label = 'N° de Chassis';
$field->checked = 1;
$field->sub_title = 0;
$field->field = 'c.ref_supplier';
$field->group =1;
$field->align = 'center';
$field->alias = 'ref_supplier';
$field->post_traitement = array('none');
$tools=array();
$tool = new Dyntable_tools($db);
$tool->type = 'text';
$tool->title = '';
$tool->html_name = 'search_vin';
$tool->filter = 'c.ref_supplier';
$tool->size = 4;
$tools['1'] = $tool;
$field->filter = $tools;
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='client';
$field->label = 'Client';
$field->checked = 1;
$field->sub_title = 0;
$field->field = 's.nom';
$field->group =1;
$field->align = 'center';
$field->alias = 'name';
$field->post_traitement = array('link', '/societe/soc.php','?socid=','societe');
$tools=array();
$tool = new Dyntable_tools($db);
$tool->type = 'text';
$tool->title = '';
$tool->html_name = 'search_client';
$tool->filter = 's.nom';
$tool->size = 26;
$tools['1'] = $tool;
$field->filter = $tools;
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='comm';
$field->label = 'Commercial';
$field->checked = 1;
$field->sub_title = 0;
$field->field = "CONCAT(u.firstname,'',u.lastname)";
$field->align = 'left';
$field->alias = 'comm';
$field->post_traitement = array('link', '/user/card.php','?id=','commercial');
$tools=array();
$tool = new Dyntable_tools($db);
$tool->type = 'select_user';
$tool->title = '';
$tool->html_name = 'search_commercial';
$tool->filter = 'u.rowid';
$tool->use_empty = 1;
$tool->see_all = $user->rights->volvo->stat_all;
$tool->default = $user->id;
$tool->limit_to_group = '1';
$tools['1'] = $tool;
$field->filter = $tools;
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='button';
$field->label = '';
$field->type = 'button';
$field->checked = 1;
$field->sub_title = 0;
$field->align = 'center';
$field->href = $_SERVER['PHP_SELF'] . '?id=#cid#&action=set_date&element=#action#';
$field->img = img_picto('Statut Suivant', 'calendar');
$field->right = $user->rights->contrat->creer;
$field->post_traitement = array('none');
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='etat';
$field->label = 'État';
$field->checked = 1;
$field->sub_title = 0;
$field->group =1;
$field->field = "IF(ef.dt_env_cli IS NULL,'En attente envoi Client',IF(dt_ret_cli IS NULL,'chez le Client',IF(dt_sig_the IS NULL,'En cours de signature Théobald',IF(dt_env_vtf IS NULL,'En attente envoi VTF',IF(dt_enr IS NULL,'En cours d\'enregistrment VTF',CONCAT('Enregistré',IF(dt_ret_vtf IS NULL, ' En attente retour VTF',IF(dt_trait IS NULL,' recu a traiter',''))))))))";
$field->align = 'left';
$field->alias = 'statut';
$field->post_traitement = array('none');
$tools=array();
$tool = new Dyntable_tools($db);
$tool->type = 'select_array';
$tool->array = array(1=>'En attente envoi Client',2=>'chez le Client',3=>'En cours de signature Théobald',4=>'En attente envoi VTF',5=>'En cours d\'enregistrment VTF',6=>'Enregistré En attente retour VTF',7=>'Enregistré recu a traiter',8=>'Enregistré');
$tool->title = '';
$tool->html_name = 'search_statut';
$tool->filter = 'fk_statut';
$tool->use_empty = 1;
$tools['1'] = $tool;
$field->filter = $tools;
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='dt_enr';
$field->label = 'Date d\'enr.';
$field->checked = 1;
$field->sub_title = 0;
$field->field = 'ef.dt_enr';
$field->align = 'center';
$field->alias = 'dt_enr';
$field->group =1;
$field->post_traitement = array('date', 'day');
$tools=array();
$tool = new Dyntable_tools($db);
$tool->type = 'text';
$tool->title = '';
$tool->html_name = 'month';
$tool->filter = 'MONTH_IN';
$tool->size = 1;
$tools['1'] = $tool;
$tool = new Dyntable_tools($db);
$tool->type = 'select_year';
$tool->title = '';
$tool->html_name = 'year';
$tool->filter = 'YEAR_IN';
$tool->use_empty = 1;
$tool->min_year = 10;
$tool->max_year = 0;
$tools['2'] = $tool;
$field->filter = $tools;
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='nb_initial';
$field->label = img_picto('Inactif', 'statut1');
$field->checked = 1;
$field->sub_title = 0;
$field->field = 'SUM('.$db->ifsql("cd.statut=0",1,0).')';
$field->align = 'center';
$field->alias = 'nb_initial';
$field->post_traitement = array('none');
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='nb_running';
$field->label = img_picto('En service, non expiré', 'statut4');
$field->checked = 1;
$field->sub_title = 0;
$field->field = 'SUM('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite >= '".$db->idate($now)."')",1,0).')';
$field->align = 'center';
$field->alias = 'nb_running';
$field->post_traitement = array('none');
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='nb_expired';
$field->label = img_picto('En service, expiré', 'statut3');
$field->checked = 1;
$field->sub_title = 0;
$field->field = 'SUM('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NOT NULL AND cd.date_fin_validite < '".$db->idate($now)."')",1,0).')';
$field->align = 'center';
$field->alias = 'nb_expired';
$field->post_traitement = array('none');
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='nb_closed';
$field->label = img_picto('Fermé', 'statut6');
$field->checked = 1;
$field->sub_title = 0;
$field->field = 'SUM('.$db->ifsql("cd.statut=5",1,0).')';
$field->align = 'center';
$field->alias = 'nb_closed';
$field->post_traitement = array('none');
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='cid';
$field->enabled = false;
$field->alias = 'cid';
$field->field = 'c.rowid';
$field->group =1;
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='fk_statut';
$field->enabled = false;
$field->alias = 'fk_statut';
$field->field = 'IF(ef.dt_env_cli IS NULL,1,IF(dt_ret_cli IS NULL,2,IF(dt_sig_the IS NULL,3,IF(dt_env_vtf IS NULL,4,IF(dt_enr IS NULL,5,IF(dt_ret_vtf IS NULL, 6,IF(dt_trait IS NULL,7,8))))))) ';
$field->group =1;
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='action';
$field->enabled = false;
$field->alias = 'action';
$field->field = "IF(ef.dt_env_cli IS NULL,'dt_env_cli',IF(dt_ret_cli IS NULL,'dt_ret_cli',IF(dt_sig_the IS NULL,'dt_sig_the',IF(dt_env_vtf IS NULL,'dt_env_vtf',IF(dt_enr IS NULL,'dt_enr',IF(dt_ret_vtf IS NULL,'dt_ret_vtf',IF(dt_trait IS NULL,'dt_trait','none'))))))) ";
$field->group =1;
$table->arrayfields[$field->name] = $field;


$field= new Dyntable_fields($db);
$field->name='societe';
$field->enabled = false;
$field->alias = 'socid';
$field->field = 's.rowid';
$field->group =1;
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='commercial';
$field->enabled = false;
$field->alias = 'commercial';
$field->field = 'u.rowid';
$field->group =1;
$table->arrayfields[$field->name] = $field;


$table->sql_from.= MAIN_DB_PREFIX . "contrat AS c ";
$table->sql_from.= "INNER JOIN " . MAIN_DB_PREFIX . "societe AS s ON s.rowid = c.fk_soc ";
$table->sql_from.= "LEFT JOIN " . MAIN_DB_PREFIX . "element_contact AS elmc on elmc.element_id = c.rowid AND elmc.fk_c_type_contact = 11 ";
$table->sql_from.= "LEFT JOIN " . MAIN_DB_PREFIX . "user AS u ON u.rowid = elmc.fk_socpeople ";
$table->sql_from.= "INNER JOIN " . MAIN_DB_PREFIX . "contratdet AS cd ON c.rowid = cd.fk_contrat ";
$table->sql_from.= "LEFT JOIN " . MAIN_DB_PREFIX . "contrat_extrafields AS ef ON c.rowid = ef.fk_object";

$table->sql_where = 'c.entity IN ('.getEntity('contract', 1).')';
$table->sql_filter_action = array();
$table->sql_filter_action[] = array('keys'=>array('s.nom','c.ref_supplier','c.ref_customer','c.ref'), 'action' =>"#KEY# LIKE '%#VALUE#%'");
$table->sql_filter_action[] = array('keys'=>array('c.rowid','s.rowid','u.rowid','fk_statut'), 'action' =>"#KEY# = #VALUE#");
$table->sql_filter_action[] = array('keys'=>array('MONTH_IN'), 'action' =>"date_format(ef.dt_enr, '%m') = '#VALUE#'");
$table->sql_filter_action[] = array('keys'=>array('YEAR_IN'), 'action' =>"date_format(ef.dt_enr, '%Y') = '#VALUE#'");


/*
 * Action
 */



if($action=='confirm_set_date' && $element !='none'){
	$contrat = New Contrat($db);
	$contrat->fetch($id);
	$contrat->array_options['options_' . $element]=dol_mktime(0, 0, 0, GETPOST('date_actionmonth'), GETPOST('date_actionday'), GETPOST('date_actionyear'));
	$contrat->insertExtraFields();
}

/*
 * View
 */
$table->post();

$table->data_array();

$table->header();

if ($action == 'set_date' && $element != 'none') {
	$option = '?id=' . $id . '&element=' . $element . '&sortfield=' . $table->sortfield . '&sortorder=' . $table->sortorder;
	$option.= '&offset=' . $table->offset . '&page=' . $table->page . $table->option;
	$form = new Form($db);
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . $option, "Valider et passer a l'étape suivante", '', 'confirm_set_date', array(array(
			'type' => 'date',
			'name' => 'date_action',
			'label'=> "date de l'action"
	)), '', 1);
}

if(!empty($formconfirm)) print $formconfirm;

$table->draw_tool_bar();

$table->draw_table_head();

$table->draw_data_table();

$table->end_table();
