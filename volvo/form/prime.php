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

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/volvo/class/lead.extend.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';

$title = 'Suivis des affaires en cours';

$search_commercial = GETPOST("search_commercial", 'int');

// Security check
if (! $user->rights->volvo->prime_read)
	accessforbidden();

$search_commercial_disabled = 0;
if (empty($user->rights->volvo->stat_all)){
	$search_commercial = $user->id;
	$search_commercial_disabled = 1;
}


$form = new Form($db);
$object = new Leadext($db);
$formother = new FormOther($db);

llxHeader('', $title);

// Count total nb of records
$nbtotalofrecords = 0;

$sql = "SELECT rowid, cmdnotok.fk_commande ";
$sql.= "FROM llx_commande as c ";
$sql.= "LEFT JOIN ";
$sql.= "(SELECT det.fk_commande ";
$sql.= "FROM llx_product_extrafields as pef ";
$sql.= "INNER JOIN llx_commandedet as det ON det.fk_product = pef.fk_object ";
$sql.= "LEFT JOIN llx_commandedet_extrafields as detef ON detef.fk_object = det.rowid ";
$sql.= "WHERE (notupdatecost IS NULL OR notupdatecost=0) ";
$sql.= "AND (detef.fk_supplier IS NULL OR detef.fk_supplier =0) ";
$sql.= "GROUP BY det.fk_commande) AS cmdnotok ";
$sql.= "ON c.rowid = cmdnotok.fk_commande ";
$sql.= "INNER JOIN llx_actioncomm as ac ON c.rowid = ac.fk_element AND ac.elementtype = 'order' AND  ac.label LIKE '%Commande V% classée Payée%' ";
$sql.= "HAVING cmdnotok.fk_commande IS NULL";
$sql.= "HAVING cmdnotok.fk_commande IS NULL";
$resql = $db->query($sql);

if ($resql != - 1) {
	$num = $resql;
	$nbtotalofrecords = $db->num_rows($resql);
  	print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $option, '', '', '', $num, $nbtotalofrecords);
  	print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="search_form">' . "\n";

  	print '<td align="center">' . $form->select_dolusers($search_commercial,'search_commercial',0,array(),$search_commercial_disabled) . '</td>';

  	if (! empty($sortfield))
  		print '<input type="hidden" name="sortfield" value="' . $sortfield . '"/>';
  	if (! empty($sortorder))
  		print '<input type="hidden" name="sortorder" value="' . $sortorder . '"/>';
  	if (! empty($page))
  		print '<input type="hidden" name="page" value="' . $page . '"/>';
  	if (! empty($viewtype))
  		print '<input type="hidden" name="viewtype" value="' . $viewtype . '"/>';
  	if (! empty($socid))
  		print '<input type="hidden" name="socid" value="' . $socid . '"/>';

 	print '<div align="left"><input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
 	print '&nbsp;<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '"></div>';

 	print '<table class="noborder" width="100%">';

 	print '<tr class="liste_titre">';
 	print '<th rowspan="2">Vendeur</th>';
 	print '<th rowspan="2">Client</th>';
 	print '<th rowspan="2">Affaire</th>';
 	print '<th rowspan="2">Commande</th>';
 	print '<th rowspan="2">Chassis</th>';
 	print '<th rowspan="2">Date de facture</th>';
 	print '<th rowspan="2">Marge réelle a date</th>';
 	print '<th colspan="7">Prime a date</th>';
 	print '<th rowspan="2">prime déja versée</th>';
 	print '<th rowspan="2">Ecart</th>';
 	print "</tr>";
 	print '<tr class="liste_titre">';
 	print '<th>Prime de base</th>';
 	print '<th>Prime vcm</th>';
 	print '<th>Prime pack connecté</th>';
 	print '<th>Prime Nouveau client</th>';
 	print '<th>Prime délai cash</th>';
 	print '<th>Prime divers</th>';
 	print '</tr>';

//  	foreach ($object->business as $line) {
// 		;$var = ! $var;

//  		$comm = New User($db);
//  		$comm->fetch($line->commercial);

//  		$comfourn = new CommandeFournisseur($db);
//  		$result=$comfourn->fetch($line->fournid);

//  		$comcli = New Commande($db);
//  		$comcli->fetch($line->com);

//  		$lead = new Lead($db);
//  		$lead->fetch($line->lead);

//  		$soc = New Societe($db);
//  		$soc->fetch($line->societe);

//  		$om_label = '<a href="'.DOL_URL_ROOT.'/fourn/commande/card.php?id=';
//  		$om_label.= $comfourn->id .'">';
//  		if (!empty($line->numom)){
//  			$om_label.= $line->numom;
//  		}else{
//  			$om_label.= $comfourn->ref;
//  		}
//  		$om_label.= '</a>';

//  		print '<tr ' . $bc[$var] . '>';

//  		print '<td>'. $comm->getNomUrl(0) .'</td>';
//  		print '<td align="center">' . $om_label . '</td>';
//  		print '<td align="center">' . $comcli->getNomUrl(0) . '</td>';
//  		print '<td align="center">'. $lead->getNomUrl(0) .'</td>';
//  		print '<td>'.$soc->getNomUrl(0,'',33) . '</td>';
//  		print '<td align="center">' . $line->vin . '</td>';
//  		print '<td align="center">' . $line->immat . '</td>';

//  		print '<td align="center">' . dol_print_date($line->dt_env_usi,'day') . '</td>';
//  		print '<td align="center">' . dol_print_date($line->dt_blockupdate,'day') . '</td>';
//  		print '<td align="center">' . dol_print_date($line->dt_liv_cons,'day') . '</td>';
//  		print '<td align="center">' . dol_print_date($line->dt_recep,'day') . '</td>';

//  		print '<td align="center">' . dol_print_date($line->dt_valid_ana,'day') . '</td>';
//  		print '<td align="center">' . dol_print_date($line->dt_liv_dem_cli,'day') . '</td>';
//  		print '<td align="center">' . dol_print_date($line->dt_liv_cli,'day') . '</td>';
//  		print '<td align="center">' . dol_print_date($line->dt_fac,'day') . '</td>';
//  		print '<td align="center">' . dol_print_date($line->dt_pay,'day') . '</td>';

//  		if(!empty($line->dt_recep)){
//  			$text = $line->delai_cash . ' Jour(s)';
//  		}else{
//  			$text ='';
//  		}
//  		print '<td align="center">' . $text . '</td>';

//  		if(!empty($line->dt_liv_dem_cli) && !empty($line->dt_liv_cons)){
//  			$text = $line->delaiprep . ' Jour(s)';
//  		}else{
//  			$text ='';
//  		}
//  		print '<td align="center">' . $text . '</td>';

//  		if(!empty($line->dt_liv_cons) && !empty($line->dt_recep)){
//  			$text = $line->retard_recept . ' Jour(s)';
//  		}else{
//  			$text ='';
//  		}
//  		print '<td align="center">' . $text . '</td>';

//  		if(!empty($line->dt_liv_dem_cli) && !empty($line->dt_liv_cli)){
//  			$text = $line->retard_liv . ' Jour(s)';
//  		}else{
//  			$text ='';
//  		}
//  		print '<td align="center">' . $text . '</td>';

//  		print "</tr>\n";

//  		$i ++;
//  	}

	print "</table>";

	}



llxFooter();
$db->close();
