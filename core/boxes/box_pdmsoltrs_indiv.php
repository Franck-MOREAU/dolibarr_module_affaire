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
class box_pdmsoltrs_indiv extends ModeleBoxes
{
    var $boxcode="pdm_soltrs_indiv";
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
        $this->boxlabel="Mix produit solutions Transports Individuel";
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
		global $user;
		dol_include_once('/mydoliboard/class/mydoliboard.class.php');
		$_POST['Année'] = dol_print_date(dol_now(),'%Y');
 		$_POST['Commercial'] = $user->id;
		$board= new Mydoliboard($this->db);
		$board->fetch(7);
		$this->info_box_head = array('text' => 'Mix Produit Solutions Transport Individuel', 'limit'=> 50);
		$this->info_box_contents[0][0] = array(
			'td' => 'align="center" width="100%"',
			'textnoformat' => $board->gengraph("B", 2,'',1,550)
		);
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
