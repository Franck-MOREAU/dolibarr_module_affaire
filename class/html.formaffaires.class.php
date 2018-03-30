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
 * \file affaires/class/html.fromaffaires.class.php
 * \ingroup affaires
 * \brief File of class with all html predefined components
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

class FormAffaires extends Form
{
	public $db;
	public $error;
	public $num;

	/**
	 * Build Select List of element associable to a businesscase
	 *
	 * @param string $tablename To parse
	 * @param Affaires $affaires The affaires
	 * @param string $htmlname Name of the component
	 *
	 * @return string HTML select list of element
	 */
	public function select_element($tablename, $affaires, $htmlname = 'elementselect') {
		global $langs, $conf;

		switch ($tablename) {
			case "facture" :
				$sql = "SELECT rowid, facnumber as ref, total as total_ht, date_valid as date_element";
				break;
			case "contrat" :
				$sql = "SELECT rowid, ref as ref, 0 as total_ht, date_contrat as date_element";
				break;
			case "commande" :
				$sql = "SELECT rowid, ref as ref, total_ht as total_ht, date_commande as date_element";
				break;
			default :
				$sql = "SELECT rowid, ref, total_ht, datep as date_element";
				break;
		}

		$sql .= " FROM " . MAIN_DB_PREFIX . $tablename;
		// TODO Fix sourcetype can be different from tablename (exemple project/projet)
		$sqlwhere = array();
		// if ($tablename!='contrat' || empty($conf->global->AFFAIRES_ALLOW_MULIPLE_AFFAIRES_ON_CONTRACT)) {
		$sql_inner = '  rowid NOT IN (SELECT fk_source FROM ' . MAIN_DB_PREFIX . 'element_element WHERE targettype=\'' . $this->db->escape($affaires->element) . '\'';
		$sql_inner .= ' AND sourcetype=\'' . $this->db->escape($tablename) . '\')';
		$sqlwhere[] = $sql_inner;
		// }

		// Manage filter
		$sqlwhere[] = ' fk_soc=' . $this->db->escape($affaires->fk_soc);
		$sqlwhere[] = ' entity IN (' . getEntity($tablename, 1) . ')';

		if (count($sqlwhere) > 0) {
			$sql .= ' WHERE ' . implode(' AND ', $sqlwhere);
		}
		$sql .= $this->db->order('ref', 'DESC');

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num > 0) {
				$sellist = '<select class="flat" name="' . $htmlname . '">';
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);
					$sellist .= '<option value="' . $obj->rowid . '">' . $obj->ref . ' (' . dol_print_date($this->db->jdate($obj->date_element), 'daytextshort') . ')';
					$sellist .= (empty($obj->total_ht) ? '' : '-' . price($obj->total_ht) . $langs->getCurrencySymbol($conf->currency)) . '</option>';
					$i ++;
				}
				$sellist .= '</select>';
			}
			return $sellist;
		}
		$this->db->free($resql);

		return null;
	}

	/**
	 * Return a HTML area with the reference of object and a navigation bar for a business object
	 * To add a particular filter on select, you must set $object->next_prev_filter to SQL criteria.
	 *
	 * @param object $object Show
	 * @param string $paramid ID off parameter to use to name the id into the URL link
	 * @param string $morehtml HTML content to output just before the nav bar
	 * @param int $shownav Condition (navigation is shown if value is 1)
	 * @param string $fieldid ID du champ en base a utiliser pour select next et previous
	 * @param string $fieldref Ref du champ objet ref (object->ref) a utiliser pour select next et previous
	 * @param string $morehtmlref HTML supplementaire a afficher apres ref
	 * @param string $moreparam Param to add in nav link url.
	 *
	 * @return string Portion HTML avec ref + boutons nav
	 */
	public function showrefnav($object, $paramid, $morehtml = '', $shownav = 1, $fieldid = 'rowid', $fieldref = 'ref', $morehtmlref = '', $moreparam = '', $nodbprefix = 0, $morehtmlleft = '', $morehtmlstatus = '', $morehtmlright = '') {
		global $langs, $conf;

		$ret = '';
		if (empty($fieldid))
			$fieldid = 'rowid';
		if (empty($fieldref))
			$fieldref = 'ref';

		// print "paramid=$paramid,morehtml=$morehtml,shownav=$shownav,$fieldid,$fieldref,$morehtmlref,$moreparam";
		$object->load_previous_next_ref_custom((isset($object->next_prev_filter) ? $object->next_prev_filter : ''), $fieldid);
		$previous_ref = $object->ref_previous ? '<a data-role="button" data-icon="arrow-l" data-iconpos="left" href="' . $_SERVER["PHP_SELF"] . '?' . $paramid . '=' . urlencode($object->ref_previous) . $moreparam . '">' . (empty($conf->dol_use_jmobile) ? img_picto($langs->trans("Previous"),
				'previous.png') : '&nbsp;') . '</a>' : '';
		$next_ref = $object->ref_next ? '<a data-role="button" data-icon="arrow-r" data-iconpos="right" href="' . $_SERVER["PHP_SELF"] . '?' . $paramid . '=' . urlencode($object->ref_next) . $moreparam . '">' . (empty($conf->dol_use_jmobile) ? img_picto($langs->trans("Next"), 'next.png') : '&nbsp;') . '</a>' : '';

		// print "xx".$previous_ref."x".$next_ref;
		if ($previous_ref || $next_ref || $morehtml) {
			$ret .= '<table class="nobordernopadding" width="100%"><tr class="nobordernopadding"><td class="nobordernopadding">';
		}

		$ret .= $object->$fieldref;
		if ($morehtmlref) {
			$ret .= ' ' . $morehtmlref;
		}

		if ($morehtml) {
			$ret .= '</td><td class="nobordernopadding" align="right">' . $morehtml;
		}
		if ($shownav && ($previous_ref || $next_ref)) {
			$ret .= '</td><td class="nobordernopadding" align="center" width="20">' . $previous_ref . '</td>';
			$ret .= '<td class="nobordernopadding" align="center" width="20">' . $next_ref;
		}
		if ($previous_ref || $next_ref || $morehtml) {
			$ret .= '</td></tr></table>';
		}
		return $ret;
	}

	/**
	 * Return combo list of differents status
	 *
	 * @param string $selected Value
	 * @param string $htmlname Name of the component
	 * @param int $showempty Row
	 *
	 * @return string HTML select
	 */
	public function select_affaires_status($selected = '', $htmlname = 'affairesstatus', $showempty = 1) {
		require_once 'affaires.class.php';
		$affaires = new Affaires($this->db);

		return $this->selectarray($htmlname, $affaires->status, $selected, $showempty);
	}

	/**
	 * Return combo list of differents type
	 *
	 * @param string $selected Value
	 * @param string $htmlname Name of the component
	 * @param int $showempty Row
	 *
	 * @return string HTML select
	 */
	public function select_affaires_type($selected = '', $htmlname = 'affairestype', $showempty = 1) {
		require_once 'affaires.class.php';
		$affaires = new Affaires($this->db);

		return $this->selectarray($htmlname, $affaires->type, $selected, $showempty);
	}

	/**
	 * Return combo list of differents type
	 *
	 * @param string $selected Value
	 * @param string $htmlname Name of the component
	 * @param int $showempty Row
	 * @param string $type dictname
	 *
	 * @return string HTML select
	 */
	public function select_affairesdet_fromdict($selected = '', $htmlname = 'dict_name', $showempty = 1, $type = '', $filter = array()) {
		if (empty($type)) {
			$type = $htmlname;
		}
		require_once 'affaires.class.php';
		$affaires = new Affaires_det($this->db);
		if (count($filter) > 0) {
			foreach ( $affaires->$htmlname as $key => $obj ) {
				foreach ( $filter as $keyfilter => $valfilter ) {
					if (property_exists($obj, $keyfilter)) {
						if ($obj->$keyfilter !== $valfilter) {
							unset($affaires->$type[$key]);
						}
					}
				}
			}
		}
		// var_dump($type,$affaires->$type);
		return $this->selectarray($htmlname, $affaires->$type, $selected, $showempty);
	}

	/**
	 * Return combo list of differents type
	 *
	 * @param string $selected Value
	 * @param string $htmlname Name of the component
	 * @param int $showempty Row
	 * @param string $type dictname
	 *
	 * @return string HTML select
	 */
	public function select_affairesdet_motifs($selected = array(), $htmlname = 'motifs', $filter = array()) {
		if (empty($type)) {
			$type = $htmlname;
		}
		require_once 'affaires.class.php';
		$affaires = new Affaires_det($this->db);
		if (count($filter) > 0) {
			foreach ( $affaires->motifs_dict as $key => $obj ) {
				foreach ( $filter as $keyfilter => $valfilter ) {
					if (property_exists($obj, $keyfilter)) {
						if ($obj->$keyfilter !== $valfilter) {
							unset($affaires->motifs_dict[$key]);
						}
					}
				}
			}
		}

		return $this->multiselectarray($htmlname, $affaires->motifs_dict, $selected, 0, 0, '', 0, '100%', '', '');
	}

	/**
	 * Return combo list of differents type
	 *
	 * @param string $selected Value
	 * @param string $htmlname Name of the component
	 * @param int $showempty Row
	 * @param array $filter Filter results
	 *
	 * @return string HTML select
	 */
	public function select_affaires($selected = '', $htmlname = 'affairesid', $showempty = 1, $filter = array()) {
		$affaires_array = array();
		require_once 'affaires.class.php';

		$affaires = new Affaires($this->db);

		$result = $affaires->fetch_all('DESC', 't.ref', 0, 0, $filter);
		if ($result < 0) {
			setEventMessages(null, $affaires->errors, 'errors');
		}
		foreach ( $affaires->lines as $line ) {
			$affaires_array[$line->id] = $line->ref . '-' . $line->ref_int . ' (' . $line->status_label . '-' . $line->type_label . ')';
		}
		if (count($affaires_array) > 0) {
			return $this->selectarray($htmlname, $affaires_array, $selected, $showempty);
		}
		return null;
	}

	/**
	 *
	 * @param number $selected
	 * @param string $htmlname
	 * @param string $filterbygroup
	 */
	public function select_salesmans($selected = 0, $htmlname = 'fk_user_resp', $filterbygroup = 'Commerciaux', $showempty = 1) {
		require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

		$includeuserlist = array();
		$usergroup = new UserGroup($this->db);
		$result = $usergroup->fetch('', $filterbygroup);

		if ($result < 0) {
			setEventMessages(null, $usergroup->errors, 'errors');
		}

		$includeuserlisttmp = $usergroup->listUsersForGroup();

		if (is_array($includeuserlisttmp) && count($includeuserlisttmp) > 0) {
			foreach ( $includeuserlisttmp as $usertmp ) {
				$includeuserlist[] = $usertmp->id;
			}
		}

		return $this->select_dolusers($selected, 'fk_user_resp', $showempty, array(), 0, $includeuserlist, '', 0, 0, 0, '', 0, '', '', 1);
	}

	/**
	 *
	 * @param string $file
	 * @return string
	 */
	public function select_tabs($filesource, $htmlname = '', $selectlabel = '', $outputformat = 'html', $outputlabel = 'code') {
		global $langs;

		require_once '../class/volvoimport.class.php';

		$object = new VolvoImport($this->db);
		$object->initFile($filesource, 'port');
		$result = $object->loadFile();
		if ($result < 0) {
			setEventMessages(null, $object->errors, 'errors');
		}

		if (is_array($object->sheetArray) && count($object->sheetArray) > 0) {
			if ($outputformat == 'html') {
				$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			}
			foreach ( $object->sheetArray as $key => $sheet ) {
				if (! empty($selectlabel) && $selectlabel == $sheet) {
					$out .= '<option value="' . $key . '" selected="selected">' . $sheet . '</option>';
				} else {
					$out .= '<option value="' . $key . '">' . $sheet . '</option>';
				}
			}
			if ($outputformat == 'html') {
				$out .= '</select>';
			}
		}

		return $out;
	}
	public function select_model($htmlname = '', $selectlabel = '') {
		$sql = 'SELECT rowid, modele FROM ' . MAIN_DB_PREFIX . 'volvo_modele_fdd WHERE active = 1';
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $object = $this->db->fetch_object($resql) ) {
				$arrayresult[$object->rowid] = $object->modele;
			}
		}
		if (is_array($arrayresult)) {
			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			foreach ( $arrayresult as $key => $label ) {
				if (! empty($selectlabel) && $selectlabel == $key) {
					$out .= '<option value="' . $key . '" selected="selected">' . $label . '</option>';
				} else {
					$out .= '<option value="' . $key . '">' . $label . '</option>';
				}
			}
			$out .= '</select>';
		}
		return $out;
	}

	/**
	 * Show a combo list with contracts qualified for a third party
	 *
	 * @param int $socid Id third party (-1=all, 0=only contracts not linked to a third party, id=contracts not linked or linked to third party id)
	 * @param int $selected Id contract preselected
	 * @param string $htmlname Nom de la zone html
	 * @param int $maxlength Maximum length of label
	 * @param int $showempty Show empty line
	 * @return int Nbr of project if OK, <0 if KO
	 */
	public function select_withcheckbox($htmlname = '', $values = array(), $selectedvalues = array(), $moreparam = '') {
		$nb = ceil(count($values)/35);

		$out = '<div align="left"><table class="nobordernopadding"><tr>';
		$i = 0;
		foreach ( $values as $key => $label ) {
			$out .= '<td><input class="flat" type="checkbox" align="left" name="' . $htmlname . '[]" ' . ($moreparam ? $moreparam : '');
			$out .= ' value="' . $key . '"';
			if (in_array($key, $selectedvalues)) {
				$out .= 'checked';
			}
			$out .= '/>' . $label . '</td>';
			$i++;
			if ($i == $nb){
				$out .= '</tr><tr>';
				$i = 0;
			}
		}

		$out .= '</tr></table></div>';

		return $out;
	}

	public function select_withcheckbox_flat($htmlname = '', $values = array(), $selectedvalues = array(), $moreparam = '') {
		$out = '<div align="left"><table class="nobordernopadding"><tr>';
		$i = 0;
		foreach ( $values as $key => $label ) {
			$out .= '<td><input class="flat" type="checkbox" align="left" name="' . $htmlname . '[]" ' . ($moreparam ? $moreparam : '');
			$out .= ' value="' . $key . '"';
			if (in_array($key, $selectedvalues)) {
				$out .= 'checked';
			}
			$out .= '/>' . $label . '</td>';
		}

		$out .= '</tr></table></div>';

		return $out;
	}

	public function select_src_column($key, $columndef = array(), $srccolumn = array()) {
		global $langs;
		$out .= '<select id="volvocol_' . $key . '" class="flat" name="volvocol_' . $key . '">';
		$out .= '<option value=-1"></option>';
		foreach ( $srccolumn as $key => $column ) {
			if ($columndef['filecolumntitle'] == $column['label'] || $columndef['forcetmpcolumnname'] == $column['name']) {
				$out .= '<option value="' . $column['name'] . '" selected="selected">' . $column['label'] . '</option>';
			} else {
				$out .= '<option value="' . $column['name'] . '">' . $column['label'] . '</option>';
			}
		}

		$out .= '</select>';

		return $out;
	}
}