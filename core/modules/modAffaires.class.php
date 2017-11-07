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
 * \defgroup	lead	Lead module
 * \brief		Lead module descriptor.
 * \file		core/modules/modLead.class.php
 * \ingroup	lead
 * \brief		Description and activation file for module Lead
 */
include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";

/**
 * Description and activation class for module Lead
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
		$this->picto = 'affaires@affaires'; // mypicto@lead
		                            // Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		                            // for default path (eg: /lead/core/xxxxx) (0=disable, 1=enable)
		                            // for specific path of parts (eg: /lead/core/modules/barcode)
		                            // for specific css file (eg: /lead/css/lead.css.php)
		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory
			// 'triggers' => 1,
			// Set this to 1 if module has its own login method directory
			// 'login' => 0,
			// Set this to 1 if module has its own substitution function file
			// 'substitutions' => 0,
			// Set this to 1 if module has its own menus handler directory
			// 'menus' => 0,
			// Set this to 1 if module has its own barcode directory
			// 'barcode' => 0,
			// Set this to 1 if module has its own models directory
			'models' => 1,
		// Set this to relative path of css if module has its own css file
		// 'css' => '/lead/css/mycss.css.php',
		// Set here all hooks context managed by module
			'hooks' => array('commonobject','searchform'),
		// Set here all workflow context managed by module
		// 'workflow' => array('order' => array('WORKFLOW_ORDER_AUTOCREATE_INVOICE'))
				);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/lead/temp");
		$this->dirs = array(
			'/affaires',
			'/affaires/stats'
		);

		// Config pages. Put here list of php pages
		// stored into lead/admin directory, used to setup module.
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
				'Numbering lead rule',
				0,
				'current',
				1
			),
			
		);

		// Array to add new pages in new tabs
		// Example:
		//$this->tabs = array(
			//'thirdparty:+tabLead:Module103111Name:lead@lead:$user->rights->lead->read && ($object->client > 0 || $soc->client > 0):/lead/lead/list.php?socid=__ID__',
			//'invoice:+tabAgefodd:AgfMenuSess:agefodd@agefodd:/lead/lead/list.php?search_invoiceid=__ID__',
			//'propal:+tabAgefodd:AgfMenuSess:agefodd@agefodd:/lead/lead/list.php?search_propalid=__ID__',
		// // To add a new tab identified by code tabname1
		// 'objecttype:+tabname1:Title1:langfile@lead:$user->rights->lead->read:/lead/mynewtab1.php?id=__ID__',
		// // To add another new tab identified by code tabname2
		// 'objecttype:+tabname2:Title2:langfile@lead:$user->rights->othermodule->read:/lead/mynewtab2.php?id=__ID__',
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
			'langs' => 'lead@lead',
			'tabname' => array(
				MAIN_DB_PREFIX . "c_affaires_status",
				MAIN_DB_PREFIX . "c_affaires_type",
				MAIN_DB_PREFIX . "c_affaires_gamme",
				MAIN_DB_PREFIX . "c_affaires_silouhette",
				MAIN_DB_PREFIX . "c_affaires_genre",
				MAIN_DB_PREFIX . "c_affaires_carrosserie",
				MAIN_DB_PREFIX . "c_affaires_marques",
				MAIN_DB_PREFIX . "c_affaires_motif_perte_lead"
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
				'SELECT f.rowid as rowid, f.motif as nom, f.active FROM ' . MAIN_DB_PREFIX . 'c_affaires_motif_perte_lead as f'
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
		// Example:

		//$this->boxes[$r][1] = "box_lead_current@lead";
		//$r ++;
		//$this->boxes[$r][1] = "box_lead_late@lead";
		/*
		 * $this->boxes[$r][1] = "myboxb.php"; $r++;
		 */

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

		// $r++;
		// Main menu entries
		$this->menus = array(); // List of menus to add
		$r = 0;

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
				'titre' => 'Affaires chaude',
				'url' => '/affaires/form/list.php?viewtype=hot',
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
				'url' => '/affaires/form/lead_portfolio.php',
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
			'leftmenu' => 'mylead',
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
				'fk_menu' => 'fk_mainmenu=affaires,fk_leftmenu=mylead',
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
				'fk_menu' => 'fk_mainmenu=affaires,fk_leftmenu=mylead',
				'type' => 'left',
				'titre' => 'mes affaires Chaudes',
				'url' => '/affaires/form/list.php?viewtype=myhot',
				'langs' => 'affaires@affaires',
				'position' => 100+$r,
				'enabled' => '$user->rights->affaires->read',
				'perms' => '$user->rights->affaires->read',
				'target' => '',
				'user' => 0
		);
		$r ++;

		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=affaires,fk_leftmenu=mylead',
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
				'fk_menu' => 'fk_mainmenu=affaires,fk_leftmenu=mylead',
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
				'fk_menu' => 'fk_mainmenu=affaires,fk_leftmenu=mylead',
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
		$sql = array();

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
	 * and create data commands must be stored in directory /lead/sql/
	 * This function is called by this->init
	 *
	 * @return int if KO, >0 if OK
	 */
	private function loadTables()
	{
		return $this->_load_tables('/affaires/sql/');
	}
}
