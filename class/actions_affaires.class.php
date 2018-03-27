<?php
/* Copyright (C) 2015		Florian HENRY	<florian.henry@atm-consulting.fr>
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
 * \file htdocs/affaires/class/actions_affaires.class.php
 * \ingroup affaires
 * \brief Fichier de la classe des actions/hooks des affaires
 */
class ActionsAffaires // extends CommonObject
{

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param string[] $parameters meta datas of the hook (context, etc...)
	 * @param Affaires $object the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param string $action current action (if set). Generally create or edit or null
	 * @return int Hook status
	 */
	function showLinkedObjectBlock($parameters, $object, $action) {
		global $conf, $langs, $db;

		require_once 'affaires.class.php';

		$affaires = new Affaires($db);

		$authorized_object = array ();
		foreach ( $affaires->listofreferent as $referent ) {
			$authorized_object[] = $referent['table'];
		}

		if (is_object($object) && in_array($object->table_element, $authorized_object)) {
			$langs->load("affaires@affaires");
			require_once 'html.formaffaires.class.php';

			$formaffaires = new FormAffaires($db);

			$ret = $affaires->fetchAffairesLink(($object->rowid ? $id = $object->rowid : $object->id), $object->table_element);
			if ($ret < 0) {
				setEventMessages(null, $affaires->errors, 'errors');
			}
			// Build exlcude already linked affaires
			$array_exclude_affaires = array ();
			foreach ( $affaires->doclines as $line ) {
				$array_exclude_affaires[] = $line->id;
			}

			print '<br>';
			print_fiche_titre($langs->trans('Affaires'));
			if (count($affaires->doclines) == 0 || ($object->table_element=='contrat' && !empty($conf->global->AFFAIRES_ALLOW_MULIPLE_AFFAIRES_ON_CONTRACT))) {
				print '<form action="' . dol_buildpath("/affaires/affaires/manage_link.php", 1) . '" method="POST">';
				print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
				print '<input type="hidden" name="redirect" value="http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '">';
				print '<input type="hidden" name="tablename" value="' . $object->table_element . '">';
				print '<input type="hidden" name="elementselect" value="' . ($object->rowid ? $object->rowid : $object->id) . '">';
				print '<input type="hidden" name="action" value="link">';
			}
			print "<table class='noborder allwidth'>";
			print "<tr class='liste_titre'>";
			print "<td>" . $langs->trans('AffairesLink') . "</td>";
			print "</tr>";
			$filter = array (
					'so.rowid' => ($object->fk_soc ? $object->fk_soc : $object->socid)
			);
			if (count($array_exclude_affaires) > 0) {
				$filter['t.rowid !IN'] = implode($array_exclude_affaires, ',');
			}
			$selectList = $formaffaires->select_affaires('', 'affairesid', 1, $filter);
			if (! empty($selectList) && (count($affaires->doclines) == 0  || ($object->table_element=='contrat' && !empty($conf->global->AFFAIRES_ALLOW_MULIPLE_AFFAIRES_ON_CONTRACT)))) {
				print '<tr>';
				print '<td>';
				print $selectList;
				print "<input type=submit name=join value=" . $langs->trans("Link") . ">";
				print '</td>';
				print '</tr>';
			}

			foreach ( $affaires->doclines as $line ) {
				print '<tr><td>';
				print $line->getNomUrl(1).'-'.dol_trunc($line->description).' ('.$line->status_label.' - '.$line->type_label.')';
				print '<a href="' . dol_buildpath("/affaires/affaires/manage_link.php", 1) . '?action=unlink&sourceid=' . ($object->rowid ? $object->rowid : $object->id);
				print '&sourcetype=' . $object->table_element;
				print '&affairesid=' . $line->id;
				print '&redirect=' . urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
				print '">' . img_picto($langs->trans('AffairesUnlinkDoc'), 'unlink.png@affaires') . '</a>';
				print '</td>';
				print '</tr>';
			}
			print "</table>";
			if (count($affaires->doclines) == 0  || ($object->table_element=='contrat' && !empty($conf->global->AFFAIRES_ALLOW_MULIPLE_AFFAIRES_ON_CONTRACT))) {
				print "</form>";
			}
		}

		// Always OK
		return 0;
	}

	/**
	 * addMoreActionsButtons Method Hook Call
	 *
	 * @param string[] $parameters parameters
	 * @param CommonObject $object Object to use hooks on
	 * @param string $action Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param HookManager $hookmanager class instance
	 * @return int Hook status
	 */
	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager) {
		global $langs, $conf, $user, $db ,$bc;

		$current_context = explode(':', $parameters['context']);
		if (in_array('commcard', $current_context)) {

			$langs->load("affaires@affaires");

			if ($user->rights->affaires->write) {
				$html = '<div class="inline-block divButAction"><a class="butAction" href="' . dol_buildpath('/affaires/affaires/card.php', 1) . '?action=create&socid=' . $object->id . '">' . $langs->trans('AffairesCreate') . '</a></div>';
			} else {
				$html = '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AffairesCreate') . '</a></div>';
			}

			$html = str_replace('"', '\"', $html);

			$js= '<script type="text/javascript">'."\n";
			$js.= '	$(document).ready('."\n";
			$js.= '		function () {'."\n";
			$js.= '			$(".tabsAction").append("' . $html . '");'."\n";
			$js.= '		});'."\n";
			$js.= '</script>';
			print $js;

			if ($user->rights->affaires->read) {

				require_once 'affaires.class.php';
				$affaires = new Affaires($db);

				$filter['so.rowid'] = $object->id;
				$resql = $affaires->fetch_all('DESC', 't.date_closure', 0, 0, $filter);
				if ($resql == - 1) {
					setEventMessages(null, $object->errors, 'errors');
				}

				$total_affaires = count($affaires->lines);

				// $filter['so.rowid'] = $object->id;
				$resql = $affaires->fetch_all('DESC', 't.date_closure', 4, 0, $filter);
				if ($resql == - 1) {
					setEventMessages(null, $object->errors, 'errors');
				}

				$num = count($affaires->lines);

				$html = '<table class="noborder" width="100%">';

				$html .= '<tr class="liste_titre">';
				$html .= '<td colspan="6">';
				$html .= '<table width="100%" class="nobordernopadding"><tr><td>' . $langs->trans("AffairesLastAffairesUpdated", ($num <= 4 ? $num : 4)) . '</td><td align="right"><a href="' . dol_buildpath('/affaires/affaires/list.php', 1) . '?socid=' . $object->id . '">' . $langs->trans("AffairesList") . ' (' . $total_affaires . ')</a></td>';
				$html .= '<td width="20px" align="right"><a href="' . dol_buildpath('/affaires/index.php', 1) . '">' . img_picto($langs->trans("Statistics"), 'stats') . '</a></td>';
				$html .= '</tr></table></td>';
				$html .= '</tr>';

				foreach ( $affaires->lines as $affaires_line ) {
					$var = ! $var;
					$html .='<tr '. $bc[$var].'>';
					$html .= '<td>'.$affaires_line->getNomUrl(1).'</td>';
					$html .= '<td>'.$affaires_line->ref_int.'</td>';
					$html .= '<td>'.$affaires_line->type_label.'</td>';
					$html .= '<td>'.price($affaires_line->amount_prosp) . ' ' . $langs->getCurrencySymbol($conf->currency).'</td>';
					$html .= '<td>'.dol_print_date($affaires_line->date_closure, 'daytextshort').'</td>';
					$html .= '<td>'.$affaires_line->getLibStatut(2).'</td>';
					$html .= '</tr>';
				}

				$html .= '</table>';
				$html = str_replace('"', '\"', $html);
				$js= '<script type="text/javascript">'."\n";
				$js.= '	$(document).ready('."\n";
				$js.= '		function () {'."\n";
				$js.= '			$(".ficheaddleft").append("' . $html . '");'."\n";
				$js.= '		});'."\n";
				$js.= '</script>';
				print $js;
			}
		}
		if (in_array('propalcard', $current_context)) {
			require_once 'affaires.class.php';
			$affaires = new Affaires($db);

			$ret = $affaires->fetchAffairesLink(($object->rowid ? $id = $object->rowid : $object->id), $object->table_element);
			if ($ret < 0) {
				setEventMessages(null, $affaires->errors, 'errors');
			}

			if (count($affaires->doclines) == 0) {
				$langs->load("affaires@affaires");

				if ($user->rights->affaires->write) {
					$html = '<div class="inline-block divButAction"><a class="butAction" href="' . dol_buildpath('/affaires/affaires/card.php', 1) . '?action=create&amp;socid=' . $object->socid . '&amp;amount_guess=' . $object->total_ht . '&amp;propalid=' . $object->id . '">' . $langs->trans('AffairesCreate') . '</a></div>';
				} else {
					$html = '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AffairesCreate') . '</a></div>';
				}

				$html = str_replace('"', '\"', $html);
				$js= '<script type="text/javascript">'."\n";
				$js.= '	$(document).ready('."\n";
				$js.= '		function () {'."\n";
				$js.= '			$(".tabsAction").append("' . $html . '");'."\n";
				$js.= '		});'."\n";
				$js.= '</script>';
				print $js;
			}
		}

		if (in_array('thirdpartycard', $current_context)){
			$out = '<script type="text/javascript">' . "\n";
			$out .= '  	$(document).ready(function() {' . "\n";
			$out .= '		$a = $(\'<a href="javascript:popCalendar()" class="butAction">Créer un calendrier</a>\');' . "\n";
			$out .= '		$(\'div.fiche div.tabsAction\').first().prepend($a);' . "\n";
			$out .= '  	});' . "\n";
			$out .= '' . "\n";
			$out .= '  	function popCalendar() {' . "\n";
			$out .= '  		$div = $(\'<div id="popCalendar"><iframe width="100%" height="100%" frameborder="0" src="' . dol_buildpath('/volvo/event/createcustcalendar.php?socid=' . $object->id, 1) . '"></iframe></div>\');' . "\n";
			$out .= '' . "\n";
			$out .= '  		$div.dialog({' . "\n";
			$out .= '  			modal:true' . "\n";
			$out .= '  			,width:"90%"' . "\n";
			$out .= '  			,height:$(window).height() - 150' . "\n";
			$out .= '  			,close:function() {document.location.reload(true);}' . "\n";
			$out .= '  		});' . "\n";
			$out .= '' . "\n";
			$out .= '  	}' . "\n";
			$out .= '' . "\n";
			$out .= '</script>';
			print $out;
		}

		// Always OK
		return 0;
	}

	/**
	 * addSearchEntry Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object &$object Object to use hooks on
	 * @param string &$action Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param object $hookmanager class instance
	 * @return void
	 */
	public function addSearchEntry($parameters, &$object, &$action, $hookmanager) {
		global $conf, $langs;
		$langs->load('affaires@affaires');

		$arrayresult['searchintoaffaires'] = array (
				'text' => img_object('', 'affaires@affaires') . ' ' . $langs->trans("Module103111Name"),
				'url' => dol_buildpath('/affaires/affaires/list.php', 1) . '?search_ref=' . urlencode($parameters['search_boxvalue'])
		);

		$this->results = $arrayresult;
	}

	/**
	 * doActions Method Hook Call
	 *
	 * @param string[] $parameters parameters
	 * @param CommonObject $object Object to use hooks on
	 * @param string $action Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param HookManager $hookmanager class instance
	 * @return int Hook status
	 */
	function doActions($parameters, &$object, &$action, $hookmanager) {
		global $langs, $conf, $user, $db, $bc;

		$current_context = explode(':', $parameters['context']);

		if (in_array('ordercard', $current_context)) {
		/*	$dest = dol_buildpath('/affaires/volvo/commande/card.php',2). '?id=' . $object->id;
			header("Location: ".$dest);
			exit;*/

		} elseif (in_array('ordersuppliercard', $current_context)) {
			$dest = dol_buildpath('/affaires/volvo/fourn/commande/card.php',2). '?id=' . $object->id;
			header("Location: ".$dest);
			exit;

		}

		return 0;
	}

}
