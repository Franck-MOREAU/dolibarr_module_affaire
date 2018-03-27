<?php
/* Copyright (C) 2012-2014 Charles-François BENKE <charles.fr@benke.fr>
 * Copyright (C) 2015      Frederic France        <frederic.france@free.fr>
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
 *  \file       htdocs/core/boxes/box_task.php
 *  \ingroup    Projet
 *  \brief      Module to Task activity of the current year
 */

include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");

/**
 * Class to manage the box to show last task
 */
class box_delaicash_global extends ModeleBoxes
{
    var $boxcode="delai_gloabl";
    var $boximg="iron02@volvo";
    var $boxlabel;
    //var $depends = array("projet");
    var $db;
    var $param;

    var $info_box_head = array();
    var $info_box_contents = array();

    /**
     *  Constructor
     *
     *  @param  DoliDB  $db         Database handler
     *  @param  string  $param      More parameters
     */
    function __construct($db,$param='')
    {
        global $langs;
        $langs->load("boxes");
        $this->boxlabel="Commande non payées- Global";
        $this->db = $db;
    }

	/**
	 *  Load data for box to show them later
	 *
	 *  @param  int     $max        Maximum number of records to load
	 *  @return void
	 */
	function loadBox()
	{
	global $user,$db;
		dol_include_once('/volvo/class/lead.extend.class.php');
		$lead = new Leadext($db);


		$this->info_box_head = array('text' => 'Commandes non payées', 'limit'=> 50);

		$i = 0;

		$this->info_box_contents[$i][0] = array(
				'tr' => 'class="liste_titre"',
 				'td' => 'align="leftr" class="liste_titre"',
				'text' => 'Commande',
		);

		$this->info_box_contents[$i][1] = array(
				'td' => 'align="left" class="liste_titre"',
				'text' => 'client',
		);

		$this->info_box_contents[$i][2] = array(
				'td' => 'align="center" class="liste_titre"',
				'text' => 'Date limite de reglement',
		);

		$this->info_box_contents[$i][3] = array(
				'td' => 'align="center" class="liste_titre"',
				'text' => 'Jours restants',
		);

		$i++;
		if($user->rights->volvo->stat_all){
			$lead->fetchdelaicash('ASC','diff_cash',10,0,array('dt_pay_isnull'=>1,));

			foreach ($lead->business AS $line){
				if($line->diff_cash < 0){
					$img = img_picto('délai dépassé','statut8');
				}elseif($line->diff_cash>=0 && $line->diff_cash <8){
					$img = img_picto('délai proche','statut1');
				}else{
					$img= img_picto('délai ok','statut4');
				}

				$this->info_box_contents[$i][0] = array(
						'td' => 'align="left"',
						'text' => $img . ' - ' . $line->comref,
						'url' => DOL_URL_ROOT . "/commande/card.php?id=" . $line->lead
				);

				$this->info_box_contents[$i][1] = array(
						'td' => 'align="left"',
						'text' => $line->socnom,
						'url' => DOL_URL_ROOT . "/societe/soc.php?socid=" . $line->societe
				);

				$this->info_box_contents[$i][2] = array(
						'td' => 'align="center"',
						'text' => dol_print_date($line->date_lim_reg,'day'),
				);

				$this->info_box_contents[$i][3] = array(
						'td' => 'align="center"',
						'text' => $line->diff_cash. ' Jours',
				);

				$i++;
			}
		}else{
			$this->info_box_contents[$i][0] = array(
					'td' => 'align="center" colspan="4"',
					'text' => 'Droit utilisateur insufisants pour lire le contenu de cette box',
			);
		}
	}
	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *	@return	void
	 */
	function showBox($head = null, $contents = null, $nooutput = 0)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}
}
