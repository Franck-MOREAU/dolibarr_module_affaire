<?php
/*
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

/**
 * \defgroup	affaires	Affaires module
 * \brief		Affaires module descriptor.
 * \file		core/modules/modAffaires.class.php
 * \ingroup	affaires
 * \brief		Description and activation file for module Affaires
 */
include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";

/**
 * Description and activation class for module Affaires
 */
class modAffaires extends DolibarrModules
{

	/**
	 * Constructor.
	 * Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		// Id for module (must be unique).
		// Use a free id here
		// (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 101751;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'affaires';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "other";
		// Module label (no space allowed)
		// used if translation string 'ModuleXXXName' not found
		// (where XXX is value of numeric property 'numero' of module)
		$this->name = 'Affaires';
		// Module description
		// used if translation string 'ModuleXXXDesc' not found
		// (where XXX is value of numeric property 'numero' of module)
		$this->description = "Module de suivis des affaires";
		// Possible values for version are: 'development', 'experimental' or version
		$this->version = '1.0';
		// Key used in llx_const table to save module status enabled/disabled
		// (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_AFFAIRES';
		// Where to store the module in setup page
		// (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png
		// use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png
		// use this->picto='pictovalue@module'
		$this->picto = 'affaires@affaires'; // mypicto@affaires
		                            // Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		                            // for default path (eg: /affaires/core/xxxxx) (0=disable, 1=enable)
		                            // for specific path of parts (eg: /affaires/core/modules/barcode)
		                            // for specific css file (eg: /affaires/css/affaires.css.php)
		$this->module_parts = array(
			// Set this to 1 if module has its own login method directory
			// 'login' => 0,
			// Set this to 1 if module has its own substitution function file
			// 'substitutions' => 0,
			// Set this to 1 if module has its own menus handler directory
			// 'menus' => 0,
			// Set this to 1 if module has its own barcode directory
			// 'barcode' => 0,
			// Set this to 1 if module has its own models directory
			'triggers' => 1,
			'models' => 1,
			'tpl' => 1,
			'js'=>'/affaires/volvo/js/jquery.flot.orderBars.js',
		// Set this to relative path of css if module has its own css file
		// 'css' => '/affaires/css/mycss.css.php',
		// Set here all hooks context managed by module
			'hooks' => array('commonobject','searchform','ordercard','ordersuppliercard','thirdpartycard'),
		// Set here all workflow context managed by module
		// 'workflow' => array('order' => array('WORKFLOW_ORDER_AUTOCREATE_INVOICE'))
				);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/affaires/temp");
		$this->dirs = array(
			'/affaires',
			'/affaires/stats',
			'/affaires/volvo',
			'/affaires/volvo/import',
			'/affaires/volvo/import/fdd',
			'/affaires/volvo/import/immat',
			'/affaires/volvo/modelpdf',

		);

		// Config pages. Put here list of php pages
		// stored into affaires/admin directory, used to setup module.
		$this->config_page_url = array(
			"admin_affaires.php@affaires"
		);

		// Dependencies
		// List of modules id that must be enabled if this module is enabled
		$this->depends = array();
		// List of modules id to disable if this one is disabled
		$this->requiredby = array();
		// Minimum version of PHP required by module
		$this->phpmin = array(
			5,
			3
		);
		// Minimum version of Dolibarr required by module
		$this->need_dolibarr_version = array(
			6,
			0
		);
		$this->langfiles = array(
			"affaires@affaires"
		);

		$this->const = array(
			0 => array(
				'AFFAIRES_ADDON',
				'chaine',
				'mod_affaires_simple',
				'Numbering affaires rule',
				0,
				'current',
				1
			),
			1 => array(
				'MAIN_CAN_HIDE_EXTRAFIELDS',
				'chaine',
				'1',
				'can hiden extrafiled',
				0,
				'current',
				1
			),
			2 => array(
				'COMMANDE_ADDON_PDF',
				'chaine',
				'analysevolvo',
				'',
				1,
				'current',
				1
			),
			3 => array(
				'COMMANDE_ADDON_PDF_2',
				'chaine',
				'analysevolvolg',
				'',
				1,
				'current',
				1
			),
			4 => array(
				'VOLVO_VCM_LIST',
				'chaine',
				'GOLD,GOLDS,SILVER,SILVER+,BLUE',
				'Liste des articles Contrat de maintenance',
				0,
				'current',
				1
			),
			5 => array(
				'VOLVO_PACK_LIST',
				'chaine',
				'PPC,PCC,PVC',
				'Liste des articles Pack Véhicules',
				0,
				'current',
				1
			),
			6 => array(
				'VOLVO_LOCK_DELAI',
				'chaine',
				'6',
				'',
				0,
				'current',
				1
			),
			7 => array(
				'VOLVO_TRUCK',
				'chaine',
				'1',
				'',
				0,
				'current',
				1
			),
			8 => array(
				'VOLVO_SURES',
				'chaine',
				'16',
				'',
				0,
				'current',
				1
			),
			9 => array(
				'VOLVO_COM',
				'chaine',
				'13',
				'',
				0,
				'current',
				1
			),
			10 => array(
				'VOLVO_FORFAIT_LIV',
				'chaine',
				'10',
				'',
				0,
				'current',
				1
			),
			11 => array(
				'VOLVO_OBLIGATOIRE',
				'chaine',
				'5',
				'',
				0,
				'current',
				1
			),
			12 => array(
				'VOLVO_INTERNE',
				'chaine',
				'2',
				'',
				0,
				'current',
				1
			),
			13 => array(
				'VOLVO_EXTERNE',
				'chaine',
				'3',
				'',
				0,
				'current',
				1
			),
			14 => array(
				'VOLVO_DIVERS',
				'chaine',
				'4',
				'',
				0,
				'current',
				1
			),
			15 => array(
				'VOLVO_SOLTRS',
				'chaine',
				'13',
				'',
				0,
				'current',
				1
			),
			16 => array(
				'VOLVO_ANALYSE_X',
				'chaine',
				'8,29.5,55,77,100,129,154,178',
				'',
				0,
				'current',
				1
			),
			17 => array(
				'VOLVO_ANALYSE_Z',
				'chaine',
				'20.5,24.5,21,22,28,24,23,25.5',
				'',
				0,
				'current',
				1
			),
			18 => array(
				'VOLVO_ANALYSE_Y_ENTETE',
				'chaine',
				'17.5,23.5,29.5,35.5,42,48,54,60.5,72.5',
				'',
				0,
				'current',
				1
			),
			19 => array(
				'VOLVO_ANALYSE_Y_INTERNE_NB',
				'chaine',
				'10',
				'',
				0,
				'current',
				1
			),
			20 => array(
				'VOLVO_ANALYSE_Y_INTERNE_OFFSET',
				'chaine',
				'84.5',
				'',
				0,
				'current',
				1
			),
			21 => array(
				'VOLVO_ANALYSE_Y_INTERNE_PAS',
				'chaine',
				'4.8',
				'',
				0,
				'current',
				1
			),
			22 => array(
				'VOLVO_ANALYSE_Y_EXTERNE_NB',
				'chaine',
				'6',
				'',
				0,
				'current',
				1
			),
			23 => array(
				'VOLVO_ANALYSE_Y_EXTERNE_OFFSET',
				'chaine',
				'139.5',
				'',
				0,
				'current',
				1
			),
			24 => array(
				'VOLVO_ANALYSE_Y_EXTERNE_PAS',
				'chaine',
				'4.8',
				'',
				0,
				'current',
				1
			),
			25 => array(
				'VOLVO_ANALYSE_Y_DIVERS_NB',
				'chaine',
				'5',
				'',
				0,
				'current',
				1
			),
			26 => array(
				'VOLVO_ANALYSE_Y_DIVERS_OFFSET',
				'chaine',
				'185.5',
				'',
				0,
				'current',
				1
			),
			27 => array(
				'VOLVO_ANALYSE_Y_DIVERS_PAS',
				'chaine',
				'4.8',
				'',
				0,
				'current',
				1
			),
			28 => array(
				'VOLVO_ANALYSE_Y_VO_NB',
				'chaine',
				'2',
				'',
				0,
				'current',
				1
			),
			29 => array(
				'VOLVO_ANALYSE_Y_VO_OFFSET',
				'chaine',
				'167.5',
				'',
				0,
				'current',
				1
			),
			30 => array(
				'VOLVO_ANALYSE_Y_VO_PAS',
				'chaine',
				'5',
				'',
				0,
				'current',
				1
			),
			31 => array(
				'VOLVO_ANALYSE_Y_PIED',
				'chaine',
				'210.5,217,223,227.5,232.2,236.5,241,245.5,254.5',
				'',
				0,
				'current',
				1
			),
			32 => array(
				'VOLVO_ANALYSELG_X',
				'chaine',
				'6,28.5,53,74.5,98,127,154,178.5',
				'',
				0,
				'current',
				1
			),
			33 => array(
				'VOLVO_ANALYSELG_Z',
				'chaine',
				'21.5,23.5,20.5,22.5,28,26,23.5,25.5',
				'',
				0,
				'current',
				1
			),
			34 => array(
				'VOLVO_ANALYSELG_Y_ENTETE',
				'chaine',
				'157,21.7,27.7,33.7,40.7,55.7,61.7,67.7,73.7,86.7',
				'',
				0,
				'current',
				1
			),
			35 => array(
				'VOLVO_ANALYSELG_Y_INTERNE_NB',
				'chaine',
				'31',
				'',
				0,
				'current',
				1
			),
			36 => array(
				'VOLVO_ANALYSELG_Y_INTERNE_OFFSET',
				'chaine',
				'101.5',
				'',
				0,
				'current',
				1
			),
			37 => array(
				'VOLVO_ANALYSELG_Y_INTERNE_PAS',
				'chaine',
				'6.05',
				'',
				0,
				'current',
				1
			),
			38 => array(
				'VOLVO_ANALYSELG_Y_EXTERNE_NB',
				'chaine',
				'10',
				'',
				0,
				'current',
				1
			),
			39 => array(
				'VOLVO_ANALYSELG_Y_EXTERNE_OFFSET',
				'chaine',
				'12.3',
				'',
				0,
				'current',
				1
			),
			40 => array(
				'VOLVO_ANALYSELG_Y_EXTERNE_PAS',
				'chaine',
				'6.05',
				'',
				0,
				'current',
				1
			),
			41 => array(
				'VOLVO_ANALYSELG_Y_DIVERS_NB',
				'chaine',
				'16',
				'',
				0,
				'current',
				1
			),
			42 => array(
				'VOLVO_ANALYSELG_Y_DIVERS_OFFSET',
				'chaine',
				'80.5',
				'',
				0,
				'current',
				1
			),
			43 => array(
				'VOLVO_ANALYSELG_Y_DIVERS_PAS',
				'chaine',
				'6.05',
				'',
				0,
				'current',
				1
			),
			44 => array(
				'VOLVO_ANALYSELG_Y_VO_NB',
				'chaine',
				'2',
				'',
				0,
				'current',
				1
			),
			45 => array(
				'VOLVO_ANALYSELG_Y_VO_OFFSET',
				'chaine',
				'186',
				'',
				0,
				'current',
				1
			),
			46 => array(
				'VOLVO_ANALYSELG_Y_VO_PAS',
				'chaine',
				'6.05',
				'',
				0,
				'current',
				1
			),
			47 => array(
				'VOLVO_VCM_OBLIG',
				'chaine',
				'1',
				'',
				0,
				'current',
				1
			),
			48 => array(
				'VOLVO_ANALYSELG_Y_PIED',
				'chaine',
				'198.5,205.5,212.5,219,225,231,237,243,254.5',
				'',
				0,
				'current',
				1
			),
		);

		// Array to add new pages in new tabs
		// Example:
		//$this->tabs = array(
			//'thirdparty:+tabAffaires:Module103111Name:affaires@affaires:$user->rights->affaires->read && ($object->client > 0 || $soc->client > 0):/affaires/affaires/list.php?socid=__ID__',
			//'invoice:+tabAgefodd:AgfMenuSess:agefodd@agefodd:/affaires/affaires/list.php?search_invoiceid=__ID__',
			//'propal:+tabAgefodd:AgfMenuSess:agefodd@agefodd:/affaires/affaires/list.php?search_propalid=__ID__',
		// // To add a new tab identified by code tabname1
		// 'objecttype:+tabname1:Title1:langfile@affaires:$user->rights->affaires->read:/affaires/mynewtab1.php?id=__ID__',
		// // To add another new tab identified by code tabname2
		// 'objecttype:+tabname2:Title2:langfile@affaires:$user->rights->othermodule->read:/affaires/mynewtab2.php?id=__ID__',
		// // To remove an existing tab identified by code tabname
		// 'objecttype:-tabname'
		//		);
		// where objecttype can be
		// 'thirdparty' to add a tab in third party view
		// 'intervention' to add a tab in intervention view
		// 'order_supplier' to add a tab in supplier order view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'invoice' to add a tab in customer invoice view
		// 'order' to add a tab in customer order view
		// 'product' to add a tab in product view
		// 'stock' to add a tab in stock view
		// 'propal' to add a tab in propal view
		// 'member' to add a tab in fundation member view
		// 'contract' to add a tab in contract view
		// 'user' to add a tab in user view
		// 'group' to add a tab in group view
		// 'contact' to add a tab in contact view
		// 'categories_x' to add a tab in category view
		// (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// Dictionnaries

		if (! isset($conf->affaires->enabled)) {
			$conf->affaires = (object) array();
			$conf->affaires->enabled = 0;
		}

		$this->dictionnaries = array(
			'langs' => 'affaires@affaires',
			'tabname' => array(
				MAIN_DB_PREFIX . "c_affaires_status",
				MAIN_DB_PREFIX . "c_affaires_type",
				MAIN_DB_PREFIX . "c_affaires_gamme",
				MAIN_DB_PREFIX . "c_affaires_silouhette",
				MAIN_DB_PREFIX . "c_affaires_genre",
				MAIN_DB_PREFIX . "c_affaires_carrosserie",
				MAIN_DB_PREFIX . "c_affaires_marques",
				MAIN_DB_PREFIX . "c_affaires_motif_perte_affaires"
			),
			'tablib' => array(
				"Affaires -- status",
				"Affaires -- type",
				"Affaires -- Gammes de véhicules",
				"Affaires -- Géométries d'essieux",
				"Affaires -- Genres de véhicules",
				"Affaires -- Carrosseries",
				"Affaires -- Marques de véhicules",
				"Affaires -- Motifs de perte d'affaires"
			),
			'tabsql' => array(
				'SELECT f.rowid as rowid, f.code, f.label, f.active FROM ' . MAIN_DB_PREFIX . 'c_affaires_status as f',
				'SELECT f.rowid as rowid, f.code, f.label, f.active FROM ' . MAIN_DB_PREFIX . 'c_affaires_type as f',
				'SELECT f.rowid as rowid, f.gamme as nom, f.cv as canal, f.active FROM ' . MAIN_DB_PREFIX . 'c_affaires_gamme as f',
				'SELECT f.rowid as rowid, f.silouhette as nom, f.cv as canal, f.rep as reprise, f.active FROM ' . MAIN_DB_PREFIX . 'c_affaires_silouhette as f',
				'SELECT f.rowid as rowid, f.genre as nom, f.rep as reprise, f.cv as canal, f.del_rg as delais, f.labelexcel, f.active FROM ' . MAIN_DB_PREFIX . 'c_affaires_genre as f',
				'SELECT f.rowid as rowid, f.carrosserie as nom, f.labelexcel, f.active FROM ' . MAIN_DB_PREFIX . 'c_affaires_carrosserie as f',
				'SELECT f.rowid as rowid, f.marque as nom, f.labelexcel, f.active  FROM ' . MAIN_DB_PREFIX . 'c_affaires_marques as f',
				'SELECT f.rowid as rowid, f.motif as nom, f.active FROM ' . MAIN_DB_PREFIX . 'c_affaires_motif_perte_affaires as f'
			),
			'tabsqlsort' => array(
				'rowid ASC',
				'rowid ASC',
				'rowid ASC',
				'rowid ASC',
				'rowid ASC',
				'rowid ASC',
				'rowid ASC',
				'rowid ASC'
			),
			'tabfield' => array(
				"code,label",
				"code,label",
				"nom,canal",
				"nom,canal,reprise",
				"nom,canal,reprise,delais,labelexcel",
				"nom",
				"nom,labelexcel",
				"nom"
			),
			'tabfieldvalue' => array(
					"code,label",
					"code,label",
					"nom,canal",
					"nom,canal,reprise",
					"nom,canal,reprise,delais,labelexcel",
					"nom",
					"nom,labelexcel",
					"nom"
			),
			'tabfieldinsert' => array(
				"code,label",
				"code,label",
				"gamme,cv",
				"silouhette,cv,rep",
				"genre,cv,rep,del_rg,labelexcel",
				"carrosserie,labelexcel",
				"marque,labelexcel",
				"motif"
			),
			'tabrowid' => array(
					"rowid",
					"rowid",
					"rowid",
					"rowid",
					"rowid",
					"rowid",
					"rowid",
					"rowid"
			),
			'tabcond' => array(
				'$conf->affaires->enabled',
				'$conf->affaires->enabled',
				'$conf->affaires->enabled',
				'$conf->affaires->enabled',
				'$conf->affaires->enabled',
				'$conf->affaires->enabled',
				'$conf->affaires->enabled',
				'$conf->affaires->enabled'
			)
		);

		// Boxes
		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
		$this->boxes = array(); // Boxes list
		$r = 0;

		$this->boxes[$r][1] = "box_pdmsoltrs_indiv@affaires";
		$r ++;

		$this->boxes[$r][1] = "box_pdmsoltrs_global@affaires";
		$r ++;

		$this->boxes[$r][1] = "box_delaicash_indiv@affaires";
		$r ++;

		$this->boxes[$r][1] = "box_delaicash_global@affaires";
		$r ++;


		// Permissions
		$this->rights = array(); // Permission array used by this module
		$r = 0;
		$this->rights[$r][0] = 1017511;
		$this->rights[$r][1] = 'Voir les affaires';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'read';
		$r ++;

		$this->rights[$r][0] = 1017512;
		$this->rights[$r][1] = 'Modifier les affaires';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'write';
		$r ++;

		$this->rights[$r][0] = 1017513;
		$this->rights[$r][1] = 'Supprimer les affaires';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'delete';
		$r ++;

		$this->rights[$r][0] = 1017514;
		$this->rights[$r][1] = 'Etendre a toutes les affaires';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'all';
		$r ++;

		$this->rights[$r][0] = 1017515;
		$this->rights[$r][1] = 'Modifier les prix de reviens';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'volvo';
		$this->rights[$r][5] = 'update_cost';
		$r ++;

		$this->rights[$r][0] = 1017516;
		$this->rights[$r][1] = 'Volvo - Consultation stat Vente exterieur';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'volvo';
		$this->rights[$r][5] = 'stat_ext';
		$r ++;

		$this->rights[$r][0] = 1017517;
		$this->rights[$r][1] = 'Volvo - Consulter suivi Business';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'volvo';
		$this->rights[$r][5] = 'business';
		$r ++;

		$this->rights[$r][0] = 1017518;
		$this->rights[$r][1] = 'Volvo - Consulter Suivi délai cash';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'volvo';
		$this->rights[$r][5] = 'delai_cash';
		$r ++;

		$this->rights[$r][0] = 1017519;
		$this->rights[$r][1] = 'Volvo - Consulter Suvi d\'activité';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'volvo';
		$this->rights[$r][5] = 'activite';
		$r ++;

		$this->rights[$r][0] = 1017520;
		$this->rights[$r][1] = 'Volvo - Consulter Affaires chaudes';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'volvo';
		$this->rights[$r][5] = 'chaudes';
		$r ++;

		$this->rights[$r][0] = 1017521;
		$this->rights[$r][1] = 'Volvo - Consulter liste des contrats';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'volvo';
		$this->rights[$r][5] = 'contrat';
		$r ++;

		$this->rights[$r][0] = 1017522;
		$this->rights[$r][1] = 'Volvo - Consulter tableau de bord solutions transports';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'volvo';
		$this->rights[$r][5] = 'soltrs';
		$r ++;

		$this->rights[$r][0] = 1017523;
		$this->rights[$r][1] = 'Volvo - Import des données OM';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'volvo';
		$this->rights[$r][5] = 'om';
		$r ++;

		$this->rights[$r][0] = 1017524;
		$this->rights[$r][1] = 'Volvo - Consulter le portefeuille de commande';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'volvo';
		$this->rights[$r][5] = 'port';
		$r ++;


		// Main menu entries
		$this->menus = array(); // List of menus to add
		$r = 0;

		// menu Affaires

		$this->menu[$r] = array(
			'fk_menu' => 0,
			'type' => 'top',
			'titre' => 'Affaires',
			'mainmenu' => 'affaires',
			'leftmenu' => '0',
			'url' => '/affaires/index.php',
			'langs' => 'affaires@affaires',
			'position' => 100,
			'enabled' => '$user->rights->affaires->read',
			'perms' => '$user->rights->affaires->read',
			'target' => '',
			'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=affaires',
			'type' => 'left',
			'titre' => 'Affaires',
			'leftmenu' => 'affaires',
			'url' => '/affaires/form/list.php',
			'langs' => 'affaires@affaires',
			'position' => 100+$r,
			'enabled' => '$user->rights->affaires->all',
			'perms' => '$user->rights->affaires->all',
			'target' => '',
			'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=affaires,fk_leftmenu=affaires',
			'type' => 'left',
			'titre' => 'Affaires en cours',
			'url' => '/affaires/form/list.php?viewtype=current',
			'langs' => 'affaires@affaires',
			'position' => 100+$r,
			'enabled' => '$user->rights->affaires->all',
			'perms' => '$user->rights->affaires->all',
			'target' => '',
			'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=affaires,fk_leftmenu=affaires',
				'type' => 'left',
				'titre' => 'Affaires traitées',
				'url' => '/affaires/form/list.php?viewtype=won',
				'langs' => 'affaires@affaires',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->all',
				'perms' => '$user->rights->affaires->all',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=affaires,fk_leftmenu=affaires',
				'type' => 'left',
				'titre' => 'Affaires sans suite',
				'url' => '/affaires/form/list.php?viewtype=cancel',
				'langs' => 'affaires@affaires',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->all',
				'perms' => '$user->rights->affaires->all',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=affaires,fk_leftmenu=affaires',
				'type' => 'left',
				'titre' => 'Affaires perdues',
				'url' => '/affaires/form/list.php?viewtype=lost',
				'langs' => 'affaires@affaires',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->all',
				'perms' => '$user->rights->affaires->all',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=affaires',
				'type' => 'left',
				'titre' => 'Portefeuille',
				'leftmenu' => 'my',
				'url' => '/affaires/form/affaires_portfolio.php',
				'langs' => 'affaires@affaires',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->read',
				'perms' => '$user->rights->affaires->read',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=affaires,fk_leftmenu=my',
				'type' => 'left',
				'titre' => 'Nouvelle affaire',
				'url' => '/affaires/form/card.php?action=create',
				'langs' => 'affaires@affaires',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->write',
				'perms' => '$user->rights->affaires->write',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=affaires,fk_leftmenu=my',
			'type' => 'left',
			'titre' => 'Mes affaires',
			'leftmenu' => 'myaffaires',
			'url' => '/affaires/form/list.php?viewtype=my',
			'langs' => 'affaires@affaires',
			'position' => 100+$r,
			'enabled' => '$user->rights->affaires->read',
			'perms' => '$user->rights->affaires->read',
			'target' => '',
			'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=affaires,fk_leftmenu=myaffaires',
				'type' => 'left',
				'titre' => 'mes affaires en cours',
				'url' => '/affaires/form/list.php?viewtype=mycurrent',
				'langs' => 'affaires@affaires',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->read',
				'perms' => '$user->rights->affaires->read',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=affaires,fk_leftmenu=myaffaires',
				'type' => 'left',
				'titre' => 'mes affaires traitées',
				'url' => '/affaires/form/list.php?viewtype=mywon',
				'langs' => 'affaires@affaires',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->read',
				'perms' => '$user->rights->affaires->read',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=affaires,fk_leftmenu=myaffaires',
				'type' => 'left',
				'titre' => 'mes affaires sans suite',
				'url' => '/affaires/form/list.php?viewtype=mycancel',
				'langs' => 'affaires@affaires',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->read',
				'perms' => '$user->rights->affaires->read',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=affaires,fk_leftmenu=myaffaires',
				'type' => 'left',
				'titre' => 'mes affaires perdues',
				'url' => '/affaires/form/list.php?viewtype=mylost',
				'langs' => 'affaires@affaires',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->read',
				'perms' => '$user->rights->affaires->read',
				'target' => '',
				'user' => 0
		);
		$r ++;

		// menu Volvo

		$this->menu[$r] = array(
				'fk_menu' => 0,
				'type' => 'top',
				'titre' => 'Volvo',
				'mainmenu' => 'volvo',
				'url' => '/affaires/volvo/index.php',
				'langs' => '',
				'position' => 100,
				'enabled' => '1',
				'perms' => '1',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=volvo',
				'type' => 'left',
				'titre' => 'Imports',
				'mainmenu' => 'volvo',
				'leftmenu' => 'imports',
				'url' => '/affaires/volvo/import/index.php',
				'langs' => '',
				'position' => 100+$r,
				'enabled' => '1',
				'perms' => '1',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=volvo,fk_leftmenu=imports',
				'type' => 'left',
				'titre' => 'Import Immat',
				'mainmenu' => 'volvo',
				'leftmenu' => 'immat',
				'url' => '/affaires/volvo/import/import_immat.php?step=1',
				'langs' => '',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->volvo->immat',
				'perms' => '$user->rights->affaires->volvo->immat',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=volvo,fk_leftmenu=imports',
				'type' => 'left',
				'titre' => 'Import OM',
				'mainmenu' => 'volvo',
				'leftmenu' => 'om',
				'url' => '/affaires/volvo/import/import_om.php?step=1',
				'langs' => '',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->volvo->om',
				'perms' => '$user->rights->affaires->volvo->om',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=volvo',
				'type' => 'left',
				'titre' => 'etats',
				'mainmenu' => 'volvo',
				'leftmenu' => 'etats',
				'url' => '/affaires/volvo/business/list.php?search_run=1',
				'langs' => 'lead@lead',
				'position' => 100+$r,
				'enabled' => '1',
				'perms' => '$user->rights->affaires->volvo->business',
				'target' => '',
				'user' => 0
		);
		$r ++;


		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=volvo,fk_leftmenu=etats',
				'type' => 'left',
				'titre' => 'Suivis Business',
				'mainmenu' => 'volvo',
				'leftmenu' => 'business',
				'url' => '/affaires/volvo/form/list.php?search_run=1',
				'langs' => 'lead@lead',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->volvo->business',
				'perms' => '$user->rights->affaires->volvo->business',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=volvo,fk_leftmenu=etats',
				'type' => 'left',
				'titre' => 'Suivi Délai Cash',
				'mainmenu' => 'volvo',
				'leftmenu' => 'cash',
				'url' => '/affaires/volvo/form/delaicash.php?search_run=1',
				'langs' => 'lead@lead',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->volvo->delai_cash',
				'perms' => '$user->rights->affaires->volvo->delai_cash',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=volvo,fk_leftmenu=etats',
				'type' => 'left',
				'titre' => 'Suivi d\'activité',
				'mainmenu' => 'volvo',
				'leftmenu' => 'resume',
				'url' => '/affaires/volvo/form/resume.php',
				'langs' => 'lead@lead',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->volvo->activite',
				'perms' => '$user->rights->affaires->volvo->activite',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=volvo,fk_leftmenu=etats',
				'type' => 'left',
				'titre' => 'Portefeuille cmd',
				'mainmenu' => 'volvo',
				'leftmenu' => 'portefeuille',
				'url' => '/affaires/volvo/form/portefeuille.php',
				'langs' => 'lead@lead',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->volvo->port',
				'perms' => '$user->rights->affaires->volvo->port',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=volvo,fk_leftmenu=etats',
				'type' => 'left',
				'titre' => 'Affaires chaudes',
				'mainmenu' => 'volvo',
				'leftmenu' => 'chaudes',
				'url' => '/mydoliboard/mydoliboard.php?idboard=5',
				'langs' => 'lead@lead',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->volvo->chaudes',
				'perms' => '$user->rights->affaires->volvo->chaudes',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=volvo',
				'type' => 'left',
				'titre' => 'Sol. TRS',
				'mainmenu' => 'volvo',
				'leftmenu' => 'soltrs',
				'url' => '/mydoliboard/mydoliboard.php?idboard=6',
				'langs' => 'lead@lead',
				'position' => 100+$r,
				'enabled' => '1',
				'perms' => '1',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=volvo,fk_leftmenu=soltrs',
				'type' => 'left',
				'titre' => 'Tableau de bord Sol. Trs.',
				'mainmenu' => 'volvo',
				'leftmenu' => 'tdbsoltrs',
				'url' => '/mydoliboard/mydoliboard.php?idboard=6',
				'langs' => 'lead@lead',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->volvo->soltrs',
				'perms' => '$user->rights->affaires->volvo->soltrs',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=volvo,fk_leftmenu=soltrs',
				'type' => 'left',
				'titre' => 'Liste des contrats',
				'mainmenu' => 'volvo',
				'leftmenu' => 'contrat',
				'url' => '/affaires/volvo/form/listcontrat.php',
				'langs' => 'lead@lead',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->volvo->contrat',
				'perms' => '$user->rights->affaires->volvo->contrat',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=volvo,fk_leftmenu=soltrs',
				'type' => 'left',
				'titre' => 'portefeuille contrats',
				'mainmenu' => 'volvo',
				'leftmenu' => 'contratport',
				'url' => '/affaires/volvo/form/contratprt.php',
				'langs' => 'lead@lead',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->volvo->contrat',
				'perms' => '$user->rights->affaires->volvo->contrat',
				'target' => '',
				'user' => 0
		);
		$r ++;

	}

	/**
	 * Function called when module is enabled.
	 * The init function add constants, boxes, permissions and menus
	 * (defined in constructor) into Dolibarr database.
	 * It also creates data directories
	 *
	 * @param string $options Enabling module ('', 'noboxes')
	 * @return int if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf;

		$sql = array();

		dol_include_once('/core/class/extrafields.class.php');
		$extrafields=new ExtraFields($this->db);

		//extrafields societe
		$res = $extrafields->addExtraField('canton', 'Canton', 'varchar', 0, 255, 'societe',0, 0,'', array('options'=>''),0,1,3);
		$res = $extrafields->addExtraField('debranch', 'Client Débranché', 'boolean', 1, '', 'societe',0, 0,'', array('options'=>''),1,1,1);

		//extrafields contacts
		$res = $extrafields->addExtraField('codecm', 'Code Contact CM', 'varchar', 0, 255, 'socpeople',0, 0,'', array('options'=>''),0,1,3);

		//extrafields Commande
		$res = $extrafields->addExtraField('numom', 'Numéro D\'O.M.', 'varchar', 0, 10, 'commande',0, 0,'', array('options'=>''),1,1,1);
		$res = $extrafields->addExtraField('ctm', 'Contremarque', 'sellist', 1, 10, 'commande',0, 0,'', array('options'=>array('societe:nom:rowid::status=1 and client=1'=>null)),1,1,1);
		$res = $extrafields->addExtraField('dt_invoice', 'Date de facturation', 'date', 2, '', 'commande',0, 0,'', array('options'=>''),0,1,1);
		$res = $extrafields->addExtraField('vnac', 'VNC', 'price', 3, '', 'commande',0, 0,'', array('options'=>''),0,1,1);
		$res = $extrafields->addExtraField('vcm_km', 'Kilometrage annuel:', 'double', 4,'24,8','commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vin', 'VIN', 'varchar', 5, 18, 'commande',0, 0,'', array('options'=>''),1,1,1);
		$res = $extrafields->addExtraField('immat', 'Immat', 'varchar', 6, 10, 'commande',0, 0,'', array('options'=>''),1,1,1);
		$res = $extrafields->addExtraField('vcm_deja', 'Client déja sous contrat de service Volvo ?', 'boolean', 7 , '', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_deport', 'Véhicule Déporté ?', 'boolean', 8 , '', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_site', 'Point de service Volvo Trucks', 'sellist', 9 , '', 'commande',0, 0,'', array('options'=>array('c_volvo_sites:nom:rowid::active=1'=>null)),1,1,3);
		$res = $extrafields->addExtraField('vcm_atel', 'Client Ayant:', 'checkbox', 10 , '', 'commande',0, 0,'', array('options'=>array(1=>'Atelier Mécanique',2=>'Atelier Carrosserie',3=>'Sans Atelier')),1,1,3);
		$res = $extrafields->addExtraField('vcm_maint', 'Interventions réalisées:', 'checkbox', 11 , '', 'commande',0, 0,'', array('options'=>array(1=>'Entretien et Maintenance Légère',2=>'Maintenance Lourde')),1,1,3);
		$res = $extrafields->addExtraField('vcm_trf_gds', 'Transfert du calendrier vers GDS ?', 'boolean', 12 , '', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_trf_dfol', 'Transfert du calendrier vers Dynafleet ?', 'boolean', 13 , '', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_dt_dem', 'Date éventuelle de démarrage du contrat:', 'date', 14 , '', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_ppc', 'ppc', 'boolean', 15 , '', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_pc', 'pc', 'boolean', 16 , '', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_pvc', 'pvc', 'boolean', 17 , '', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_blue', 'blue', 'boolean', 18 , '', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_silver', 'silver', 'boolean', 19 , '', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_silverp', 'silver+', 'boolean', 20 , '', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_gold', 'gold', 'boolean', 21 , '', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_duree', 'Durée du contrat:', 'checkbox', 22 , '', 'commande',0, 0,'', array('options'=>array(1=>'24 Mois',2=>'36 Mois',3=>'48 Mois',4=>'60 Mois',5=>'72 Mois',6=>'84 Mois',7=>'96 Mois')),1,1,3);
		$res = $extrafields->addExtraField('vcm_conso', 'Consommation estimée du véhicule:', 'double', 23 , '24,8', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_km_dep', 'Kilometrage de départ:', 'int', 24 , '10', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_ptra', 'Poids total roulant constaté:', 'int', 25 , '10', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_pto', 'Type de prise de force:', 'checkbox', 26 , '', 'commande',0, 0,'', array('options'=>array(1=>'Moteur',2=>'Boite de vitesse')),1,1,3);
		$res = $extrafields->addExtraField('vcm_pto_nbh', 'Nombre d\'heure annuel de prise de force:', 'int', 27 , '10', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_pto_hdep', 'Heures PTO de départ:', 'int', 28 , '10', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_hydro', 'Equipement Hydraulique:', 'checkbox', 29 , '', 'commande',0, 0,'', array('options'=>array(1=>'Hydraulique VOAC monté sur chaine de montage',2=>'Autre type monté chez le réparateur agréé Volvo trucks')),1,1,3);
		$res = $extrafields->addExtraField('vcm_carr', 'carrosserie et équipements:', 'text', 30 , '2000', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_amp_heb', 'Amplitude Hebdomadaire d\'utilisation du véhicule :', 'checkbox', 31 , '', 'commande',0, 0,'', array('options'=>array(1=>'5jours/semaine',2=>'6jours/semaine',3=>'7jours/semaine')),1,1,3);
		$res = $extrafields->addExtraField('vcm_amp_jour', 'Amplitude journalière d\'utilisation du véhicule:', 'checkbox', 32 , '', 'commande',0, 0,'', array('options'=>array(1=>'8 heures/jour maximum',2=>'> à 8 heures /jour',3=>'> à 16 Heure /jour')),1,1,3);
		$res = $extrafields->addExtraField('vcm_nbpos', 'Nombre de position de livraison ou chargement journalier:', 'int', 33 , '10', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_sais', 'Véhicule ayant une activité saisonnière ?', 'boolean', 34 , '', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_chant', 'Superstructure typique pour les chantiers ?', 'boolean', 35 , '', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_ville', 'Le véhicule circule t il principalement en ville ?', 'boolean', 36 , '', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_50km', 'La distance moyenne entre le chargement et le déchargement est-elle inférieure à 50 km ?', 'boolean', 37 , '', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_ld', 'Le kilométrage annuel est il égal ou supérieur à 100 000 kms ?', 'boolean', 38 , '', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_zone', 'Zone géographique', 'select', 39 , '', 'commande',0, 0,'', array('options'=>array(1=>'Communauté Européenne',2=>'Autre')),1,1,3);
		$res = $extrafields->addExtraField('vcm_typ_trans', 'Type de transport', 'select', 40 , '', 'commande',0, 0,'', array('options'=>array(1=>'Transport traditionnel',2=>'Transport frigorifique',3=>'Container (caisse amovible)',4=>'Benne PTO < 1 H par jour',5=>'Benne PTO > 1 H par jour',
		6=>'Benne à ordures ménagères',7=>'Bras de relevage (ampliroll)',8=>'Bras de relevage (ampliroll) + Grue',9=>'Citerne PTO < 1 H par jour',10=>',Citerne PTO > 1 H par jour',
		11 => 'Collecte laitière', 12=>'Transport d\'Animaux Vivants',13=>'Convoi Exceptionnel',14=>'Porte Engins',15=>'Plateau + Grue',16=>'Transport de voitures',17=>'Dépanneuse (porte voitures)',
		18=>'Véhicule de Remorquage ( poids lourds)',19=>'Grumier PTO < 2h/jour', 20 =>'Grumier PTO > 2H/jour',21=>'Hydrocureuse',22=>'Malaxeur',23=>'Balayeuse')),1,1,3);
		$res = $extrafields->addExtraField('vcm_roul', 'Conditions de roulage', 'select', 41 , '', 'commande',0, 0,'', array('options'=>array(1=>'Lisse',2=>'Difficile',3=>'Très Difficile')),1,1,3);
		$res = $extrafields->addExtraField('vcm_topo', 'Topographie', 'select', 42 , '', 'commande',0, 0,'', array('options'=>array(1=>'Lisse',2=>'Difficile',3=>'Très Difficile')),1,1,3);
		$res = $extrafields->addExtraField('vcm_pack', 'pack vcm', 'checkbox', 43 , '', 'commande',0, 0,'', array('options'=>array(1=>'Assistance remorquage VAS',2=>'Pack Sécurité Vision',3=>'Pack Appoint')),1,1,3);
		$res = $extrafields->addExtraField('vcm_option', 'option VCM', 'checkbox', 44 , '', 'commande',0, 0,'', array('options'=>array(1=>'Traitement Protéactive GO',2=>'Maintenance des Pneumatiques',3=>'Maintenance de la sellette',4=>'Réparation de la prise de force PTO',5=>'Remplacement annuel du filtre à air du moteur')),1,1,3);
		$res = $extrafields->addExtraField('vcm_sup', 'Entretiens sup', 'checkbox', 45 , '', 'commande',0, 0,'', array('options'=>array(1=>'Entretien et Réparation RETARDER',2=>'Entretien et Réparation Embrayage Dual Clutch',3=>'Entretien et Réparation Essieu X-Track',4=>'Entretien et Réparation Crochet de remorquage',
		5=>'Entretien et Réparation Equipement Hydraulique',6=>'Entretien et Réparation Hayon 1500kg',7=>'Entretien et Réparation Hayon 2500kg',8=>'Entretien et Réparation Bras de relevage',
		9=>'Entretien et Réparation Grue',10=>'Entretien et Réparation Chauffage de nuit')),1,1,3);
		$res = $extrafields->addExtraField('vcm_legal', 'legal', 'checkbox', 46 , '', 'commande',0, 0,'', array('options'=>array(1=>'Passage annuel au Contrôle technique (Porteur)',2=>'Passage annuel au Contrôle technique (Tracteur)',
		3=>'Passage au Contrôle technique (Matières Dangereuses)',4=>'Attestation de conformité annuelle du limiteur de vitesse',5=>'Attestation tous les deux ans de conformité du chrono tachygraphe',
		6=>'Attestation de conformité annuelle de l\'extincteur',7=>'Attestation de conformité semestrielle du hayon',8=>'Attestation de conformité semestrielle du bras de relevage',8=>'Attestation de conformité semestrielle de la grue')),1,1,3);
		$res = $extrafields->addExtraField('vcm_frigo', 'Maintenance du groupe Frigorifique ?', 'boolean', 47 , '', 'commande',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('vcm_marque', 'Marque du groupe froid', 'select', 48 , '', 'commande',0, 0,'', array('options'=>array(1=>'Carrier',2=>'Thermoking')),1,1,3);
		$res = $extrafields->addExtraField('vcm_model', 'Modèle du groupe', 'varchar', 49, 255, 'commande',0, 0,'', array('options'=>''),1,1,1);
		$res = $extrafields->addExtraField('vcm_fonct', 'Fonctionnement du groupe', 'select', 50 , '', 'commande',0, 0,'', array('options'=>array(1=>'Diesel',2=>'Diesel + Electrique')),1,1,3);
		$res = $extrafields->addExtraField('vcm_frigo_nbh', 'Nombre d\'heure de fonctionnement:', 'int', 51 , '10', 'commande',0, 0,'', array('options'=>''),1,1,3);

		//extrafields contrat
		$res = $extrafields->addExtraField('dt_env_cli', 'Date d\'envoi Au client', 'date', 0 , '', 'contrat',0, 0,'', array('options'=>''),1,1,1);
		$res = $extrafields->addExtraField('dt_ret_cli', 'Date de Retour Client', 'date', 1 , '', 'contrat',0, 0,'', array('options'=>''),1,1,1);
		$res = $extrafields->addExtraField('dt_sig_the', 'Date De Signature Théobald', 'date', 2 , '', 'contrat',0, 0,'', array('options'=>''),1,1,1);
		$res = $extrafields->addExtraField('dt_env_vtf', 'Date d\'envoi a VTF', 'date', 3 , '', 'contrat',0, 0,'', array('options'=>''),1,1,1);
		$res = $extrafields->addExtraField('dt_ret_vtf', 'Date de retour VTF', 'date', 4 , '', 'contrat',0, 0,'', array('options'=>''),1,1,1);
		$res = $extrafields->addExtraField('dt_trait', 'Date Traitement', 'date', 5 , '', 'contrat',0, 0,'', array('options'=>''),1,1,1);
		$res = $extrafields->addExtraField('dt_enr', 'Date d\'enregistrement', 'date', 6 , '', 'contrat',0, 0,'', array('options'=>''),1,1,1);

		//extrafields commande fournisseur
		$res = $extrafields->addExtraField('dt_liv_maj', 'Date de livraison mise a jour', 'date', 0, '', 'commande_fournisseur',0, 0,'', array('options'=>''),1,1,1);
		$res = $extrafields->addExtraField('numom', 'Numéro D\'O.M.', 'varchar', 1, 10, 'commande_fournisseur',0, 0,'', array('options'=>''),1,1,1);
		$res = $extrafields->addExtraField('ctm', 'Contremarque', 'sellist', 2, 10, 'commande_fournisseur',0, 0,'', array('options'=>array('societe:nom:rowid::status=1 and client=1'=>null)),1,1,1);
		$res = $extrafields->addExtraField('dt_blockupdate', 'Date blocage modification', 'date', 3, '', 'commande_fournisseur',0, 0,'', array('options'=>''),1,1,1);
		$res = $extrafields->addExtraField('dt_lim_annul', 'Date limite d\'annulation', 'date', 4, '', 'commande_fournisseur',0, 0,'', array('options'=>''),1,1,1);
		$res = $extrafields->addExtraField('vin', 'VIN', 'varchar', 5, 18, 'commande_fournisseur',0, 0,'', array('options'=>''),1,1,1);
		$res = $extrafields->addExtraField('immat', 'Immat', 'varchar', 6, 10, 'commande_fournisseur',0, 0,'', array('options'=>''),1,1,1);

		//extrafields produits
		$res = $extrafields->addExtraField('notupdatecost', 'Cout non modifiable (MAJ prix commande)', 'boolean', 0, '', 'product',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('notupdatecostreal', 'Pas de maj du prix de reviens automatique (MAJ prix commande)', 'boolean', 1, '', 'product',0, 0,'', array('options'=>''),1,1,3);
		$res = $extrafields->addExtraField('supplierorderable', 'Disponible pour la commande fournisseur', 'boolean', 2, '', 'product',0, 0,'', array('options'=>''),1,1,3);

		//extrafields agenda
		$res = $extrafields->addExtraField('affaire', 'Affaire', 'sellist', 0, '', 'actioncomm',0, 0,'', array('options'=>array('lead:ref|ref_int:rowid:socid|fk_soc:fk_c_status IN (4,8,9,12)'=>null)),1,1,1);


		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$result=dol_copy(dol_buildpath('/volvo/core/doctemplate/ANALYSE CDE VOLVO.pdf'),DOL_DATA_ROOT.'/volvo/modelpdf/ficheanalyse.pdf',0,0);
		$result=dol_copy(dol_buildpath('/volvo/core/doctemplate/ANALYSE CDE VOLVO LG.pdf'),DOL_DATA_ROOT.'/volvo/modelpdf/ficheanalyselg.pdf',0,0);
		$result=dol_copy(dol_buildpath('/volvo/core/doctemplate/VCM.pdf'),DOL_DATA_ROOT.'/volvo/modelpdf/vcm.pdf',0,0);


		$res = dolibarr_del_const($this->db,'MAIN_AGENDA_ACTIONAUTO_ORDER_VALIDATE',$conf->entity);
		$res = dolibarr_del_const($this->db,'MAIN_AGENDA_ACTIONAUTO_ORDER_CLASSIFY_BILLED',$conf->entity);

		$result = $this->loadTables();

		return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param string $options Enabling module ('', 'noboxes')
	 * @return int if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}

	/**
	 * Create tables, keys and data required by module
	 * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * and create data commands must be stored in directory /affaires/sql/
	 * This function is called by this->init
	 *
	 * @return int if KO, >0 if OK
	 */
	private function loadTables()
	{
		return $this->_load_tables('/affaires/sql/');
	}
}
