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
 * \file		lib/affaires.lib.php
 * \ingroup	affaires
 * \brief		This file is an example module library
 * Put some comments here
 */
function affairesAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("affaires@affaires");
	$langs->load("admin");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/affaires/admin/admin_affaires.php", 1);
	$head[$h][1] = $langs->trans("SettingsAffaires");
	$head[$h][2] = 'settings';
	$h ++;

	$head[$h][0] = dol_buildpath("/affaires/admin/affaires_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'attributes';
	$h ++;

	$head[$h][0] = dol_buildpath("/affaires/admin/admin_volvo.php", 1);
	$head[$h][1] = $langs->trans("Admin_volvo");
	$head[$h][2] = 'admin_volvo';
	$h ++;

	$head[$h][0] = dol_buildpath("/affaires/admin/volvo_analyse.php", 1);
	$head[$h][1] = $langs->trans("param_pdf_analyse");
	$head[$h][2] = 'analyse';
	$h ++;

	$head[$h][0] = dol_buildpath("/affaires/admin/volvo_analyselg.php", 1);
	$head[$h][1] = $langs->trans("param_pdf_analyse_lg");
	$head[$h][2] = 'analyse_lg';
	$h ++;

	$head[$h][0] = dol_buildpath("/affaires/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h ++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array(
	// 'entity:+tabname:Title:@affaires:/affaires/mypage.php?id=__ID__'
	// ); // to add new tab
	// $this->tabs = array(
	// 'entity:-tabname:Title:@affaires:/affaires/mypage.php?id=__ID__'
	// ); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'affaires_admin');

	return $head;
}

/**
 * Prepare page head
 *
 * @param Affaires $object The affaires
 *
 * @return array Header contents (tabs)
 */
function affaires_prepare_head($object)
{
	global $langs, $conf;

	$langs->load("affaires@affaires");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/affaires/affaires/card.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("AffairesAffaires");
	$head[$h][2] = 'card';
	$h ++;

	$head[$h][0] = dol_buildpath("/affaires/affaires/contact.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("Contacts");
	$head[$h][2] = 'contact';
	$h ++;

	$head[$h][0] = dol_buildpath("/affaires/affaires/document.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("Documents");
	$head[$h][2] = 'documents';
	$h ++;

	if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
	{
		$nbNote = 0;
		if(!empty($object->note_private)) $nbNote++;
		if(!empty($object->note_public)) $nbNote++;
		$head[$h][0] = dol_buildpath("/affaires/affaires/note.php", 1) . '?id=' . $object->id;
		$head[$h][1] = $langs->trans('Notes');
		if($nbNote > 0) $head[$h][1].= ' ('.$nbNote.')';
		$head[$h][2] = 'note';
		$h++;
	}

	$head[$h][0] = dol_buildpath("/affaires/affaires/info.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h ++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array(
	// 'entity:+tabname:Title:@affaires:/affaires/mypage.php?id=__ID__'
	// ); // to add new tab
	// $this->tabs = array(
	// 'entity:-tabname:Title:@affaires:/affaires/mypage.php?id=__ID__'
	// ); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'affaires');

	return $head;
}

/**
 * Prepare head for statistics page
 *
 * @return array Header contents (tabs)
 */
function affaires_stats_prepare_head()
{
	global $langs, $conf;

	$langs->load("affaires@affaires");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/affaires/index.php", 1);
	$head[$h][1] = $langs->trans("AffairesStats");
	$head[$h][2] = 'stat';
	$h ++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array(
	// 'entity:+tabname:Title:@affaires:/affaires/mypage.php?id=__ID__'
	// ); // to add new tab
	// $this->tabs = array(
	// 'entity:-tabname:Title:@affaires:/affaires/mypage.php?id=__ID__'
	// ); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'affaires_stats');

	return $head;
}

function getcmd_status($year, $type=0, $commercial=0,$soc=0){

	global $db;
	dol_include_once('/affaires/volvo/class/commandevolvo.class.php');

	$sql = "SELECT ";
	$sql.= "MIN(c.fk_statut) as solde, ";
	$sql.= "MIN(c.facture) as billed ";

	$sql.= "FROM llx_affaires as a ";
	$sql.= "INNER JOIN llx_affaires_det as d ON a.rowid = d.fk_affaires ";
	$sql.= "INNER JOIN llx_commande as c ON c.rowid = d.fk_commande ";

	if(!empty($year)){
		$sql.= "WHERE a.year = " . $year . " ";
		$sql.= "AND c.fk_statut <> -1 ";

		if(!empty($type)){
			$sql.= " AND a.fk_c_type = " . $type . " ";

			if(!empty($commercial)){
				$sql.= " AND a.fk_user_resp = " . $commercial. " ";

				if(!empty($soc)){
					$sql.= " AND c.fk_soc = " . $soc. " ";
				}
			}
		}
	}else{
		$sql.= "WHERE a.year = -1 ";
	}

		$resql = $db->query($sql);
	if($resql){
		$obj = $db->fetch_object($resql);
		$status = $obj->solde;
		$billed = $obj->billed;
	}else{
		$status = -10;
	}

	$commande = new CommandeVolvo($db);
	$result = $commande->LibStatut($status, $billed, 3);

	return $result;

}

