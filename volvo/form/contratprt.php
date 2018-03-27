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

$table->title = "portefeuille de contrats";
$table->default_sortfield = 'vcmincmd.immat';
$table->default_sortorder = "DESC";
$table->export_name = 'prt_contrat_new';
$table->context = 'contractprt';
$table->search_button = 1;
$table->remove_filter_button = 1;
$table->export_button = 1;
$table->select_fields_button = 1;
$table->mode = 'sql_methode';
$table->limit = $conf->liste_limit;
$table->filter_clause = 'WHERE';
$table->filter_mode = 'AND ';
$table->filter_line = 1;

$field= new Dyntable_fields($db);
$field->name='vcmincmd.ref';
$field->label = 'N° de Commande';
$field->checked = 1;
$field->sub_title = 0;
$field->field = 'vcmincmd.ref';
$field->align = 'left';
$field->alias = 'cmd';
$field->post_traitement = array('link', '/volvo/commande/card.php','?id=','commande');
$tools=array();
$tool = new Dyntable_tools($db);
$tool->type = 'text';
$tool->title = '';
$tool->html_name = 'search_cmd';
$tool->filter = 'vcmincmd.ref';
$tool->size = 7;
$tools['1'] = $tool;
$field->filter = $tools;
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='chassis';
$field->label = 'Châssis';
$field->checked = 1;
$field->sub_title = 0;
$field->field = 'vcmincmd.vin';
$field->align = 'center';
$field->alias = 'chassis';
$field->post_traitement = array('substr', -7,2000);
$tools=array();
$tool = new Dyntable_tools($db);
$tool->type = 'text';
$tool->title = '';
$tool->html_name = 'search_vin';
$tool->filter = 'vcmincmd.vin';
$tool->size = 7;
$tools['1'] = $tool;
$field->filter = $tools;
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='immat';
$field->label = 'Immatriculation';
$field->checked = 1;
$field->sub_title = 0;
$field->field = 'vcmincmd.immat';
$field->align = 'center';
$field->alias = 'immat';
$field->post_traitement = array('none');
$tools=array();
$tool = new Dyntable_tools($db);
$tool->type = 'text';
$tool->title = '';
$tool->html_name = 'search_immat';
$tool->filter = 'vcmincmd.immat';
$tool->size = 7;
$tools['1'] = $tool;
$field->filter = $tools;
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='client';
$field->label = 'Client';
$field->checked = 1;
$field->sub_title = 0;
$field->field = 'soc.nom';
$field->align = 'center';
$field->alias = 'client';
$field->post_traitement = array('link', '/societe/soc.php','?socid=','societe');
$tools=array();
$tool = new Dyntable_tools($db);
$tool->type = 'text';
$tool->title = '';
$tool->html_name = 'search_client';
$tool->filter = 'soc.nom';
$tool->size = 26;
$tools['1'] = $tool;
$field->filter = $tools;
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='dt_liv';
$field->label = 'Date de livraison prévue';
$field->checked = 1;
$field->sub_title = 0;
$field->field = 'vcmincmd.date_livraison';
$field->align = 'center';
$field->alias = 'date_livraison';
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
$field->name='type';
$field->label = 'Type CT';
$field->checked = 1;
$field->sub_title = 0;
$field->field = "produit";
$field->align = 'left';
$field->alias = 'type';
$field->post_traitement = array('none');
$tools=array();
$tool = new Dyntable_tools($db);
$tool->type = 'select_array';
$tool->array = array(20=>'Pack Protection Cinématique Connecté',26=>'Pack Protection Véhicule Connecté',27=>'Pack Prévention',28=>'Contrat entretien BLUE',29=>'Contrat Entretien SILVER',30=>'Contrat entretien SILVER +',31=>'Contrat Entretien GOLD',40=>'Gold Specifique');
$tool->title = '';
$tool->html_name = 'search_type';
$tool->filter = 'vcmincmd.prodid';
$tool->use_empty = 1;
$tools['1'] = $tool;
$field->filter = $tools;
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='commande';
$field->enabled = false;
$field->alias = 'commande';
$field->field = 'vcmincmd.cmdid';
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='societe';
$field->enabled = false;
$field->alias = 'socid';
$field->field = 'soc.rowid';
$table->arrayfields[$field->name] = $field;

$table->sql_from.= "(SELECT RIGHT(cmdef.vin,7) as vin, cmdp.rowid as prodid, cmdp.ref as product, cmd.ref as ref,cmd.rowid as cmdid, cmdef.immat as immat, cmd.fk_soc as client, cmd.date_livraison as date_livraison, cmdp.label as produit ";
$table->sql_from.= "FROM " . MAIN_DB_PREFIX . "commande AS cmd ";
$table->sql_from.= "LEFT JOIN " . MAIN_DB_PREFIX . "commande_extrafields AS cmdef ON cmd.rowid = cmdef.fk_object ";
$table->sql_from.= "LEFT JOIN " . MAIN_DB_PREFIX . "commandedet AS cmddet on cmd.rowid = cmddet.fk_commande ";
$table->sql_from.= "LEFT JOIN " . MAIN_DB_PREFIX . "product as cmdp ON cmdp.rowid = cmddet.fk_product ";
$table->sql_from.= "WHERE cmdp.ref  IN ('GOLD','SILVER','SILVER+','BLUE','PPC','PREV','PVC') ";
$table->sql_from.= "AND cmdef.vin IS NOT NULL) AS vcmincmd ";

$table->sql_from.= "LEFT JOIN ";

$table->sql_from.= "(SELECT ct.ref_supplier as vin, ctp.ref as product ";
$table->sql_from.= "FROM " . MAIN_DB_PREFIX . "contrat AS ct ";
$table->sql_from.= "LEFT JOIN " . MAIN_DB_PREFIX . "contratdet AS ctdet on ct.rowid = ctdet.fk_contrat ";
$table->sql_from.= "LEFT JOIN " . MAIN_DB_PREFIX . "product as ctp ON ctp.rowid = ctdet.fk_product ";
$table->sql_from.= "WHERE ctp.ref IN ('GOLD','SILVER','SILVER+','BLUE','PPC','PREV','PVC')) AS vcminct ";

$table->sql_from.= "ON vcmincmd.vin = vcminct.vin AND vcmincmd.product = vcminct.product ";
$table->sql_from.= "INNER JOIN llx_societe as soc on soc.rowid = vcmincmd.client ";

$table->sql_where = 'vcminct.vin IS NULL ';
$table->sql_filter_action = array();
$table->sql_filter_action[] = array('keys'=>array('soc.nom','vcmincmd.ref','vcmincmd.vin','vcmincmd.immat'), 'action' =>"#KEY# LIKE '%#VALUE#%'");
$table->sql_filter_action[] = array('keys'=>array('vcmincmd.prodid'), 'action' =>"#KEY# = #VALUE#");
$table->sql_filter_action[] = array('keys'=>array('MONTH_IN'), 'action' =>"date_format(vcmincmd.date_livraison, '%m') = '#VALUE#'");
$table->sql_filter_action[] = array('keys'=>array('YEAR_IN'), 'action' =>"date_format(vcmincmd.date_livraison, '%Y') = '#VALUE#'");


/*
 * View
 */
$table->post();

$table->data_array();

$table->header();

$table->draw_tool_bar();

$table->draw_table_head();

$table->draw_data_table();

$table->end_table();
