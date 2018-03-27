<?php
/*
 * Copyright (C) 2014 Florian HENRY <florian.henry@open-concept.pro>
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

if (! $user->rights->volvo->activite)
	accessforbidden();

require_once DOL_DOCUMENT_ROOT . '/volvo/class/table_template.class.php';

$table = new Dyntable($db);

$table->title = 'Suivis d\'activité VN volvo - detail';
$table->default_sortfield = 'dt_sortie';
$table->export_name = 'suivi_activité-detail_new';
$table->context = 'suivi_activite_detail';
$table->search_button = '';
$table->remove_filter_button = '';
$table->export_button = 1;
$table->select_fields_button = 1;
$table->mode = 'function_methode';
$table->include = '/volvo/lib/volvo.lib.php';
$table->function = 'stat_sell_ref';
$table->limit = 0;
$table->param0 = 'filter';
$table->total_line = 'Total';

$field= new Dyntable_fields($db);
$field->name='dossier';
$field->label = 'Dossier';
$field->alias = 'ref';
$field->checked = 1;
$field->total = 'name';
$field->sub_title = 0;
$field->align = 'center';
$field->post_traitement = array('link', '/commande/card.php','?id=','id');
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='vin';
$field->label = 'Chassis';
$field->alias = 'vin';
$field->checked = 1;
$field->total = 'none';
$field->sub_title = 0;
$field->align = 'center';
$field->post_traitement = array('substr', -7,2000);
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='socname';
$field->label = 'Société';
$field->alias = 'socname';
$field->checked = 1;
$field->total = 'none';
$field->sub_title = 0;
$field->align = 'center';
$field->post_traitement = array('link', '/societe/soc.php','?socid=','socid');
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='ca_total';
$field->label = 'C.A. Total HT';
$field->alias = 'catotalht';
$field->checked = 1;
$field->total = 'value';
$field->sub_title = 0;
$field->unit = '€';
$field->align = 'center';
$field->post_traitement = array('price', 0);
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='ca_volvo';
$field->label = 'C.A. Fac. Volvo';
$field->alias = 'cavolvo';
$field->checked = 1;
$field->total = 'value';
$field->sub_title = 0;
$field->unit = '€';
$field->align = 'center';
$field->post_traitement = array('price', '2');
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='nb_trt';
$field->label = 'Nb Tracteurs';
$field->alias = 'nbtracteur';
$field->checked = 1;
$field->total = 'value';
$field->sub_title = 0;
$field->align = 'center';
$field->post_traitement = array('num', 0);
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='nb_port';
$field->label = 'Nb Porteurs';
$field->alias = 'nbporteur';
$field->total = 'value';
$field->checked = 1;
$field->sub_title = 0;
$field->align = 'center';
$field->post_traitement = array('num', 0);
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='vcm';
$field->label = 'VCM';
$field->alias = 'vcm';
$field->checked = 1;
$field->total = 'value';
$field->sub_title = 1;
$field->align = 'center';
$field->post_traitement = array('num', '0');
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='dfol';
$field->label = 'DFOL';
$field->alias = 'dfol';
$field->checked = 1;
$field->total = 'value';
$field->sub_title = 1;
$field->align = 'center';
$field->post_traitement = array('num', '0');
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='dded';
$field->label = 'DDED';
$field->alias = 'dded';
$field->checked = 1;
$field->total = 'value';
$field->sub_title = 1;
$field->align = 'center';
$field->post_traitement = array('num', '0');
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='mem';
$field->label = 'MEM';
$field->alias = 'mem';
$field->checked = 1;
$field->total = 'value';
$field->sub_title = 1;
$field->align = 'center';
$field->post_traitement = array('num', '0');
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='vfs';
$field->label = 'VFS';
$field->alias = 'vfs';
$field->checked = 1;
$field->total = 'value';
$field->sub_title = 1;
$field->align = 'center';
$field->post_traitement = array('num', 0);
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='lixbail';
$field->label = 'Lixbail';
$field->alias = 'lixbail';
$field->checked = 1;
$field->total = 'value';
$field->sub_title = 1;
$field->align = 'center';
$field->post_traitement = array('num', 0);
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='m_tot';
$field->label = 'Marge totale';
$field->alias = 'margetheo';
$field->checked = 1;
$field->total = 'value';
$field->sub_title = 0;
$field->unit = '€';
$field->align = 'center';
$field->post_traitement = array('price', '2');
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='m_tot_r';
$field->label = 'Marge Totale Réélle';
$field->alias = 'margereal';
$field->checked = 1;
$field->total = 'value';
$field->sub_title = 0;
$field->unit = '€';
$field->align = 'center';
$field->post_traitement = array('price', '2');
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='m_moy_e';
$field->label = 'Marge - Ecart';
$field->type = 'calc';
$field->formule = '(#margereal#-#margetheo#)';
$field->checked = 1;
$field->total = 'calc';
$field->sub_title = 0;
$field->unit = '€';
$field->align = 'center';
$field->post_traitement = array('price', '2');
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='id';
$field->enabled = false;
$field->alias = 'id';
$table->arrayfields[$field->name] = $field;

$field= new Dyntable_fields($db);
$field->name='socid';
$field->enabled = false;
$field->alias = 'socid';
$table->arrayfields[$field->name] = $field;

$tools =array();

$tool = new Dyntable_tools($db);
$tool->type = 'button';
$tool->title = 'Retour au suvi d\'activité ';
$tool->link = '/volvo/business/resume.php?ret=1';
$tools['0'] = $tool;

$tool = new Dyntable_tools($db);
$tool->type = 'hidden';
$tool->html_name = 'year';
$tool->filter = 'year';
$tool->default = dol_print_date(dol_now(),'%Y');
$tools['1'] = $tool;

$tool = new Dyntable_tools($db);
$tool->type = 'hidden';
$tool->html_name = 'search_commercial';
$tool->filter = 'search_commercial';
$tool->see_all = $user->rights->volvo->stat_all;
$tool->default = $user->id;
$tools['2'] = $tool;

$tool = new Dyntable_tools($db);
$tool->type = 'hidden';
$tool->html_name = 'search_periode';
$tool->filter = 'search_periode';
$tools['3'] = $tool;

$tool = new Dyntable_tools($db);
$tool->type = 'hidden';
$tool->html_name = 'month';
$tool->filter = 'month';
$tools['4'] = $tool;

$table->extra_tools =$tools;

$table->sub_title = array(1=>'Soft Offers');

$table->post();

$table->data_array();

$table->header();

$table->draw_tool_bar();

$table->draw_table_head();

$table->draw_data_table();

$table->end_table();