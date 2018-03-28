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

	/**
	 *  Return list of products for customer in Ajax if Ajax activated or go to select_produits_list
	 *
	 *  @param		int			$selected				Preselected products
	 *  @param		string		$htmlname				Name of HTML select field (must be unique in page)
	 *  @param		int			$filtertype				Filter on product type (''=nofilter, 0=product, 1=service)
	 *  @param		int			$limit					Limit on number of returned lines
	 *  @param		int			$price_level			Level of price to show
	 *  @param		int			$status					-1=Return all products, 0=Products not on sell, 1=Products on sell
	 *  @param		int			$finished				2=all, 1=finished, 0=raw material
	 *  @param		string		$selected_input_value	Value of preselected input text (for use with ajax)
	 *  @param		int			$hidelabel				Hide label (0=no, 1=yes, 2=show search icon (before) and placeholder, 3 search icon after)
	 *  @param		array		$ajaxoptions			Options for ajax_autocompleter
	 *  @param      int			$socid					Thirdparty Id (to get also price dedicated to this customer)
	 *  @param		string		$showempty				'' to not show empty line. Translation key to show an empty line. '1' show empty line with no text.
	 * 	@param		int			$forcecombo				Force to use combo box
	 *  @param      string      $morecss                Add more css on select
	 *  @param      int         $hidepriceinlabel       1=Hide prices in label
	 *  @param      string      $warehouseStatus        warehouse status filter, following comma separated filter options can be used
	 *										            'warehouseopen' = select products from open warehouses,
	 *										            'warehouseclosed' = select products from closed warehouses,
	 *										            'warehouseinternal' = select products from warehouses for internal correct/transfer only
	 *  @param array 	$selected_combinations Selected combinations. Format: array([attrid] => attrval, [...])
	 *  @param string 	$filterbycat 			1,2,3 ids of categorie to filter product
	 *  @return		void
	 */
	function select_produits($selected='', $htmlname='productid', $filtertype='', $limit=20, $price_level=0, $status=1, $finished=2, $selected_input_value='', $hidelabel=0, $ajaxoptions=array(), $socid=0, $showempty='1', $forcecombo=0, $morecss='', $hidepriceinlabel=0, $warehouseStatus='', $selected_combinations = array(), $filterbycat='')
	{
		global $langs,$conf;

		$price_level = (! empty($price_level) ? $price_level : 0);

		if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->PRODUIT_USE_SEARCH_TO_SELECT))
		{
			$placeholder='';

			if ($selected && empty($selected_input_value))
			{
				require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
				$producttmpselect = new Product($this->db);
				$producttmpselect->fetch($selected);
				$selected_input_value=$producttmpselect->ref;
				unset($producttmpselect);
			}
			// mode=1 means customers products
			$urloption='htmlname='.$htmlname.'&outjson=1&price_level='.$price_level.'&type='.$filtertype.'&mode=1&status='.$status.'&finished='.$finished.'&hidepriceinlabel='.$hidepriceinlabel.'&warehousestatus='.$warehouseStatus,'&$filterbycat='. $filterbycat:;
			//Price by customer
			if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES) && !empty($socid)) {
				$urloption.='&socid='.$socid;
			}
			print ajax_autocompleter($selected, $htmlname, dol_buildpath('/affaires/ajax/products.php'), $urloption, $conf->global->PRODUIT_USE_SEARCH_TO_SELECT, 0, $ajaxoptions);

			if (!empty($conf->variants->enabled)) {
				?>
				<script>

					selected = <?php echo json_encode($selected_combinations) ?>;
					combvalues = {};

					jQuery(document).ready(function () {

						jQuery("input[name='prod_entry_mode']").change(function () {
							if (jQuery(this).val() == 'free') {
								jQuery('div#attributes_box').empty();
							}
						});

						jQuery("input#<?php echo $htmlname ?>").change(function () {

							if (!jQuery(this).val()) {
								jQuery('div#attributes_box').empty();
								return;
							}

							jQuery.getJSON("<?php echo dol_buildpath('/variants/ajax/getCombinations.php', 2) ?>", {
								id: jQuery(this).val()
							}, function (data) {
								jQuery('div#attributes_box').empty();

								jQuery.each(data, function (key, val) {

									combvalues[val.id] = val.values;

									var span = jQuery(document.createElement('div')).css({
										'display': 'table-row'
									});

									span.append(
										jQuery(document.createElement('div')).text(val.label).css({
											'font-weight': 'bold',
											'display': 'table-cell',
											'text-align': 'right'
										})
									);

									var html = jQuery(document.createElement('select')).attr('name', 'combinations[' + val.id + ']').css({
										'margin-left': '15px',
										'white-space': 'pre'
									}).append(
										jQuery(document.createElement('option')).val('')
									);

									jQuery.each(combvalues[val.id], function (key, val) {
										var tag = jQuery(document.createElement('option')).val(val.id).html(val.value);

										if (selected[val.fk_product_attribute] == val.id) {
											tag.attr('selected', 'selected');
										}

										html.append(tag);
									});

									span.append(html);
									jQuery('div#attributes_box').append(span);
								});
							})
						});

						<?php if ($selected): ?>
						jQuery("input#<?php echo $htmlname ?>").change();
						<?php endif ?>
					});
				</script>
                <?php
			}
			if (empty($hidelabel)) print $langs->trans("RefOrLabel").' : ';
			else if ($hidelabel > 1) {
				$placeholder=' placeholder="'.$langs->trans("RefOrLabel").'"';
				if ($hidelabel == 2) {
					print img_picto($langs->trans("Search"), 'search');
				}
			}
			print '<input type="text" class="minwidth100" name="search_'.$htmlname.'" id="search_'.$htmlname.'" value="'.$selected_input_value.'"'.$placeholder.' '.(!empty($conf->global->PRODUCT_SEARCH_AUTOFOCUS) ? 'autofocus' : '').' />';
			if ($hidelabel == 3) {
				print img_picto($langs->trans("Search"), 'search');
			}
		}
		else
		{
			print $this->select_produits_list($selected,$htmlname,$filtertype,$limit,$price_level,'',$status,$finished,0,$socid,$showempty,$forcecombo,$morecss,$hidepriceinlabel, $warehouseStatus, $filterbycat);
		}
	}

	/**
	 *	Return list of products for a customer
	 *
	 *	@param      int		$selected           Preselected product
	 *	@param      string	$htmlname           Name of select html
	 *  @param		string	$filtertype         Filter on product type (''=nofilter, 0=product, 1=service)
	 *	@param      int		$limit              Limit on number of returned lines
	 *	@param      int		$price_level        Level of price to show
	 * 	@param      string	$filterkey          Filter on product
	 *	@param		int		$status             -1=Return all products, 0=Products not on sell, 1=Products on sell
	 *  @param      int		$finished           Filter on finished field: 2=No filter
	 *  @param      int		$outputmode         0=HTML select string, 1=Array
	 *  @param      int		$socid     		    Thirdparty Id (to get also price dedicated to this customer)
	 *  @param		string	$showempty		    '' to not show empty line. Translation key to show an empty line. '1' show empty line with no text.
	 * 	@param		int		$forcecombo		    Force to use combo box
	 *  @param      string  $morecss            Add more css on select
	 *  @param      int     $hidepriceinlabel   1=Hide prices in label
	 *  @param      string  $warehouseStatus    warehouse status filter, following comma separated filter options can be used
	 *										    'warehouseopen' = select products from open warehouses,
	 *										    'warehouseclosed' = select products from closed warehouses,
	 *										    'warehouseinternal' = select products from warehouses for internal correct/transfer only
	 *  @param 		string 	$filterbycat 			1,2,3 ids of categorie to filter product
	 *  @return     array    				    Array of keys for json
	 */
	function select_produits_list($selected='',$htmlname='productid',$filtertype='',$limit=20,$price_level=0,$filterkey='',$status=1,$finished=2,$outputmode=0,$socid=0,$showempty='1',$forcecombo=0,$morecss='',$hidepriceinlabel=0, $warehouseStatus='', $filterbycat='')
	{
		global $langs,$conf,$user,$db;

		$out='';
		$outarray=array();

		$warehouseStatusArray = array();
		if (! empty($warehouseStatus))
		{
			require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
			if (preg_match('/warehouseclosed/', $warehouseStatus))
			{
				$warehouseStatusArray[] = Entrepot::STATUS_CLOSED;
			}
			if (preg_match('/warehouseopen/', $warehouseStatus))
			{
				$warehouseStatusArray[] = Entrepot::STATUS_OPEN_ALL;
			}
			if (preg_match('/warehouseinternal/', $warehouseStatus))
			{
				$warehouseStatusArray[] = Entrepot::STATUS_OPEN_INTERNAL;
			}
		}

		$selectFields = " p.rowid, p.label, p.ref, p.description, p.barcode, p.fk_product_type, p.price, p.price_ttc, p.price_base_type, p.tva_tx, p.duration, p.fk_price_expression";
		(count($warehouseStatusArray)) ? $selectFieldsGrouped = ", sum(ps.reel) as stock" : $selectFieldsGrouped = ", p.stock";

		$sql = "SELECT ";
		$sql.= $selectFields . $selectFieldsGrouped;
		//Price by customer
		if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES) && !empty($socid))
		{
			$sql.=', pcp.rowid as idprodcustprice, pcp.price as custprice, pcp.price_ttc as custprice_ttc,';
			$sql.=' pcp.price_base_type as custprice_base_type, pcp.tva_tx as custtva_tx';
			$selectFields.= ", idprodcustprice, custprice, custprice_ttc, custprice_base_type, custtva_tx";
		}

		// Multilang : we add translation
		if (! empty($conf->global->MAIN_MULTILANGS))
		{
			$sql.= ", pl.label as label_translated";
			$selectFields.= ", label_translated";
		}
		// Price by quantity
		if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY))
		{
			$sql.= ", (SELECT pp.rowid FROM ".MAIN_DB_PREFIX."product_price as pp WHERE pp.fk_product = p.rowid";
			if ($price_level >= 1 && !empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES)) $sql.= " AND price_level=".$price_level;
			$sql.= " ORDER BY date_price";
			$sql.= " DESC LIMIT 1) as price_rowid";
			$sql.= ", (SELECT pp.price_by_qty FROM ".MAIN_DB_PREFIX."product_price as pp WHERE pp.fk_product = p.rowid";	// price_by_qty is 1 if some prices by qty exists in subtable
			if ($price_level >= 1 && !empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES)) $sql.= " AND price_level=".$price_level;
			$sql.= " ORDER BY date_price";
			$sql.= " DESC LIMIT 1) as price_by_qty";
			$selectFields.= ", price_rowid, price_by_qty";
		}
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
		if (!empty($filterbycat)) {
			$sql.=" INNER JOIN ".MAIN_DB_PREFIX.'categorie_product as catprod ON p.rowid=catprod.fk_product AND catprod.fk_categorie IN ('.$filterbycat.')';
		}
		if (count($warehouseStatusArray))
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps on ps.fk_product = p.rowid";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as e on ps.fk_entrepot = e.rowid";
		}

		//Price by customer
		if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES) && !empty($socid)) {
			$sql.=" LEFT JOIN  ".MAIN_DB_PREFIX."product_customer_price as pcp ON pcp.fk_soc=".$socid." AND pcp.fk_product=p.rowid";
		}
		// Multilang : we add translation
		if (! empty($conf->global->MAIN_MULTILANGS))
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_lang as pl ON pl.fk_product = p.rowid AND pl.lang='". $langs->getDefaultLang() ."'";
		}

		if (!empty($conf->global->PRODUIT_ATTRIBUTES_HIDECHILD)) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_attribute_combination pac ON pac.fk_product_child = p.rowid";
		}

		$sql.= ' WHERE p.entity IN ('.getEntity('product').')';
		if (count($warehouseStatusArray))
		{
			$sql.= ' AND (p.fk_product_type = 1 OR e.statut IN ('.$this->db->escape(implode(',',$warehouseStatusArray)).'))';
		}

		if (!empty($conf->global->PRODUIT_ATTRIBUTES_HIDECHILD)) {
			$sql .= " AND pac.rowid IS NULL";
		}

		if ($finished == 0)
		{
			$sql.= " AND p.finished = ".$finished;
		}
		elseif ($finished == 1)
		{
			$sql.= " AND p.finished = ".$finished;
			if ($status >= 0)  $sql.= " AND p.tosell = ".$status;
		}
		elseif ($status >= 0)
		{
			$sql.= " AND p.tosell = ".$status;
		}
		if (strval($filtertype) != '') {
			$sql.=" AND p.fk_product_type=".$filtertype;
		}



		// Add criteria on ref/label
		if ($filterkey != '')
		{
			$sql.=' AND (';
			$prefix=empty($conf->global->PRODUCT_DONOTSEARCH_ANYWHERE)?'%':'';	// Can use index if PRODUCT_DONOTSEARCH_ANYWHERE is on
			// For natural search
			$scrit = explode(' ', $filterkey);
			$i=0;
			if (count($scrit) > 1) $sql.="(";
			foreach ($scrit as $crit)
			{
				if ($i > 0) $sql.=" AND ";
				$sql.="(p.ref LIKE '".$db->escape($prefix.$crit)."%' OR p.label LIKE '".$db->escape($prefix.$crit)."%'";
				if (! empty($conf->global->MAIN_MULTILANGS)) $sql.=" OR pl.label LIKE '".$db->escape($prefix.$crit)."%'";
				$sql.=")";
				$i++;
			}
			if (count($scrit) > 1) $sql.=")";
		  	if (! empty($conf->barcode->enabled)) $sql.= " OR p.barcode LIKE '".$db->escape($prefix.$filterkey)."%'";
			$sql.=')';
		}
		if (count($warehouseStatusArray))
		{
			$sql.= ' GROUP BY'.$selectFields;
		}
		$sql.= $db->order("p.ref");
		$sql.= $db->plimit($limit, 0);

		// Build output string
		dol_syslog(get_class($this)."::select_produits_list search product", LOG_DEBUG);
		$result=$this->db->query($sql);
		if ($result)
		{
			require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
			require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';
			$num = $this->db->num_rows($result);

			$events=null;

			if (! $forcecombo)
			{
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out .= ajax_combobox($htmlname, $events, $conf->global->PRODUIT_USE_SEARCH_TO_SELECT);
			}

			$out.='<select class="flat'.($morecss?' '.$morecss:'').'" name="'.$htmlname.'" id="'.$htmlname.'">';

			$textifempty='';
			// Do not use textifempty = ' ' or '&nbsp;' here, or search on key will search on ' key'.
			//if (! empty($conf->use_javascript_ajax) || $forcecombo) $textifempty='';
			if (! empty($conf->global->PRODUIT_USE_SEARCH_TO_SELECT))
			{
				if ($showempty && ! is_numeric($showempty)) $textifempty=$langs->trans($showempty);
				else $textifempty.=$langs->trans("All");
			}
			if ($showempty) $out.='<option value="0" selected>'.$textifempty.'</option>';

			$i = 0;
			while ($num && $i < $num)
			{
				$opt = '';
				$optJson = array();
				$objp = $this->db->fetch_object($result);

				if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY) && !empty($objp->price_by_qty) && $objp->price_by_qty == 1)
				{ // Price by quantity will return many prices for the same product
					$sql = "SELECT rowid, quantity, price, unitprice, remise_percent, remise, price_base_type";
					$sql.= " FROM ".MAIN_DB_PREFIX."product_price_by_qty";
					$sql.= " WHERE fk_product_price=".$objp->price_rowid;
					$sql.= " ORDER BY quantity ASC";

					dol_syslog(get_class($this)."::select_produits_list search price by qty", LOG_DEBUG);
					$result2 = $this->db->query($sql);
					if ($result2)
					{
						$nb_prices = $this->db->num_rows($result2);
						$j = 0;
						while ($nb_prices && $j < $nb_prices) {
							$objp2 = $this->db->fetch_object($result2);

							$objp->price_by_qty_rowid = $objp2->rowid;
							$objp->price_by_qty_price_base_type = $objp2->price_base_type;
							$objp->price_by_qty_quantity = $objp2->quantity;
							$objp->price_by_qty_unitprice = $objp2->unitprice;
							$objp->price_by_qty_remise_percent = $objp2->remise_percent;
							// For backward compatibility
							$objp->quantity = $objp2->quantity;
							$objp->price = $objp2->price;
							$objp->unitprice = $objp2->unitprice;
							$objp->remise_percent = $objp2->remise_percent;
							$objp->remise = $objp2->remise;

							$this->constructProductListOption($objp, $opt, $optJson, 0, $selected, $hidepriceinlabel);

							$j++;

							// Add new entry
							// "key" value of json key array is used by jQuery automatically as selected value
							// "label" value of json key array is used by jQuery automatically as text for combo box
							$out.=$opt;
							array_push($outarray, $optJson);
						}
					}
				}
				else
				{
					if (!empty($conf->dynamicprices->enabled) && !empty($objp->fk_price_expression)) {
						$price_product = new Product($this->db);
						$price_product->fetch($objp->rowid, '', '', 1);
						$priceparser = new PriceParser($this->db);
						$price_result = $priceparser->parseProduct($price_product);
						if ($price_result >= 0) {
							$objp->price = $price_result;
							$objp->unitprice = $price_result;
							//Calculate the VAT
							$objp->price_ttc = price2num($objp->price) * (1 + ($objp->tva_tx / 100));
							$objp->price_ttc = price2num($objp->price_ttc,'MU');
						}
					}
					$this->constructProductListOption($objp, $opt, $optJson, $price_level, $selected, $hidepriceinlabel);
					// Add new entry
					// "key" value of json key array is used by jQuery automatically as selected value
					// "label" value of json key array is used by jQuery automatically as text for combo box
					$out.=$opt;
					array_push($outarray, $optJson);
				}

				$i++;
			}

			$out.='</select>';

			$this->db->free($result);

			if (empty($outputmode)) return $out;
			return $outarray;
		}
		else
		{
			dol_print_error($db);
		}
	}
}