<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *      \file       htdocs/user/hierarchy.php
 *      \ingroup    user
 *      \brief      Page of hierarchy view of user module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';
dol_include_once('/fourn/class/fournisseur.commande.class.php');

global $db;

if (! $user->rights->user->user->lire && ! $user->admin)
	accessforbidden();

$langs->load("users");
$langs->load("companies");
$cf = New CommandeFournisseur($db);

// Security check (for external users)
$socid=0;
if ($user->societe_id > 0)
	$socid = $user->societe_id;

/*
 * View
 */

$form = new Form($db);

$arrayofjs=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.js', '/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js');
$arrayofcss=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.css');

llxHeader('','Vue hiérarchique des affaires','','',0,0,$arrayofjs,$arrayofcss);

print load_fiche_titre('Vue hiérarchique des affaires', '');

$data[0]=array('rowid'=>0,'fk_menu'=>-1,'title'=>'racine','mainmenu'=>'','leftmenu'=>'','fk_mainmenu'=>'','fk_leftmenu'=>'');
$i = 1;

$sql = "SELECT ";
$sql.= "a.year, ";
$sql.= "a.fk_c_type, ";
$sql.= "CONCAT(a.year,'_',a.fk_c_type) AS L2KEY, ";
$sql.= "type.label as cv, ";
$sql.= "CONCAT(u.firstname,' ',u.lastname) as commercial, ";
$sql.= "u.rowid as uid, ";
$sql.= "CONCAT(a.year, '_',a.fk_c_type,'_',a.fk_user_resp) as L3KEY, ";
$sql.= "s.nom as soc, ";
$sql.= "s.rowid as socid, ";
$sql.= "CONCAT(a.year, '_',a.fk_c_type,'_',a.fk_user_resp,'_',s.rowid) as L4KEY, ";
$sql.= "CONCAT(c.ref,' ',g.genre, ' ',ga.gamme,' ',sh.silhouette) as cmdlabel, ";
$sql.= "c.rowid as cmdid, ";
$sql.= "CONCAT(a.year, '_',a.fk_c_type,'_',a.fk_user_resp,'_',a.rowid,'_',d.rowid) as L5KEY, ";
$sql.= "CONCAT(cf.ref,' ',s2.nom) as cflabel, ";
$sql.= "cf.rowid as cfid, ";
$sql.= "CONCAT(a.year, '_',a.fk_c_type,'_',a.fk_user_resp,'_',a.rowid,'_',d.rowid,'_',cf.rowid) as L6KEY, ";
$sql.= "CONCAT(fac.ref_supplier,' ',s3.nom) as faclabel, ";
$sql.= "fac.rowid as facid, ";
$sql.= "CONCAT(a.year, '_',a.fk_c_type,'_',a.fk_user_resp,'_',a.rowid,'_',d.rowid,'_',cf.rowid,'_',fac.rowid) as L7KEY, ";
$sql.= "MIN(cf.fk_statut) as solde ";

$sql.= "FROM llx_affaires as a ";
$sql.= "INNER JOIN llx_affaires_det as d ON a.rowid = d.fk_affaires ";
$sql.= "INNER JOIN llx_c_affaires_genre as g on g.rowid = d.fk_genre ";
$sql.= "INNER JOIN llx_c_affaires_gamme as ga on ga.rowid= d.fk_gamme ";
$sql.= "INNER JOIN llx_c_affaires_silhouette as sh on sh.rowid = d.fk_silhouette ";
$sql.= "INNER JOIN llx_commande as c ON c.rowid = d.fk_commande ";
$sql.= "INNER JOIN llx_commandedet as cdet on c.rowid = cdet.fk_commande ";
$sql.= "INNER JOIN llx_user as u on u.rowid = a.fk_user_resp ";
$sql.= "INNER JOIN llx_c_affaires_type as type on type.rowid = a.fk_c_type ";
$sql.= "INNER JOIN llx_societe as s on s.rowid = a.fk_soc ";
$sql.= "LEFT JOIN llx_commandedet_extrafields as cdetef on cdetef.fk_object = cdet.rowid ";
$sql.= "LEFT JOIN llx_commande_fournisseurdet as cfdet on cfdet.rowid = cdetef.fk_supplierorderlineid ";
$sql.= "LEFT JOIN llx_commande_fournisseur as cf ON cf.rowid = cfdet.fk_commande ";
$sql.= "LEFT JOIN llx_societe as s2 on s2.rowid = cf.fk_soc ";
$sql.= "LEFT JOIN llx_commande_fournisseurdet_extrafields as cfdetef on cfdet.rowid = cfdetef.fk_object ";
$sql.= "LEFT JOIN llx_facture_fourn_det_extrafields as facdetef on facdetef.fk_supplierorderlineid = cfdet.rowid ";
$sql.= "LEFT JOIN llx_facture_fourn_det as facdet on facdet.rowid = facdetef.fk_object ";
$sql.= "LEFT JOIN llx_facture_fourn as fac on fac.rowid = facdet.fk_facture_fourn ";
$sql.= "LEFT JOIN llx_societe as s3 on s3.rowid = fac.fk_soc ";

$sql.= "GROUP BY ";
$sql.= "a.year, ";
$sql.= "a.fk_c_type, ";
$sql.= "type.label, ";
$sql.= "u.rowid, ";
$sql.= "s.rowid, ";
$sql.= "c.rowid, ";
$sql.= "d.rowid, ";
$sql.= "cf.rowid, ";
$sql.= "fac.rowid ";

$sql.= "ORDER BY a.year,type.label, commercial, s.nom ";



// print $sql;
// exit;

$resql=$db->query($sql);
$result=array();
$arrayresult = $db->fetch_array($resql);
while ($arrayresult = $db->fetch_array($resql)){
	$result[] = $arrayresult;
}
$year = array();
$year = array_unique(array_column($result, 'year'));


foreach ($year as $y){
	$tableau = '<table class="nobordernopadding centpercent"><tr><td class="usertdenabled">';
	$tableau.= $y;
	$tableau.= '</td><td align="right" class="usertdenabled">';
	$tableau.= $cf->LibStatut($result[$key]['solde'],3);
	$tableau.= '</td></tr></table>';
	$data[$i] = array('rowid'=>$y,'fk_menu'=>0,'statut'=>1,'entry'=>$tableau);
	$i++;
}

$tab = array();
$tab = array_unique(array_column($result, 'L2KEY'));


foreach ($tab as $key=>$val){
	$tableau = '<table class="nobordernopadding centpercent"><tr><td class="usertdenabled">';
	$tableau.= $result[$key]['cv'];
	$tableau.= '</td><td align="right" class="usertdenabled">';
	$tableau.= $cf->LibStatut($result[$key]['solde'],3);
	$tableau.= '</td></tr></table>';
	$data[$i]= array('rowid'=>$val,'fk_menu'=>$result[$key]['year'],'statut'=>1,'entry'=>$tableau);
	$i++;
}


$tab = array();
$tab = array_unique(array_column($result, 'L3KEY'));


foreach ($tab as $key=>$val){
	$tableau = '<table class="nobordernopadding centpercent"><tr><td class="usertdenabled">';
	$tableau.= '<a href="' . dol_buildpath('user/card.php',2) . '?id=' .$result[$key]['uid'] .'">' . $result[$key]['commercial'] .'</a>';
	$tableau.= '</td><td align="right" class="usertdenabled">';
	$tableau.= $cf->LibStatut($result[$key]['solde'],3);
	$tableau.= '</td></tr></table>';

	$data[$i]= array('rowid'=>$val,'fk_menu'=>$result[$key]['L2KEY'],'statut'=>1,'entry'=> $tableau);
	$i++;
}

$tab = array();
$tab = array_unique(array_column($result, 'L4KEY'));

foreach ($tab as $key=>$val){
	$tableau = '<table class="nobordernopadding centpercent"><tr><td class="usertdenabled">';
	$tableau.= '<a href="' . dol_buildpath('/societe/card.php',2) . '?socid=' .$result[$key]['socid'] .'">' . $result[$key]['soc'].'</a>';
	$tableau.= '</td><td align="right" class="usertdenabled">';
	$tableau.= $cf->LibStatut($result[$key]['solde'],3);
	$tableau.= '</td></tr></table>';

	$data[$i]= array('rowid'=>$val,'fk_menu'=>$result[$key]['L3KEY'],'statut'=>1,'entry'=>$tableau);
	$i++;
}

$tab = array();
$tab = array_unique(array_column($result, 'L5KEY'));

foreach ($tab as $key=>$val){
	$tableau = '<table class="nobordernopadding centpercent"><tr><td class="usertdenabled">';
	$tableau.= '<a href="' . dol_buildpath('/affaires/volvo/commande/card.php',2) . '?id=' .$result[$key]['cmdid'] .'">' . $result[$key]['cmdlabel'].'</a>';
	$tableau.= '</td><td align="right" class="usertdenabled">';
	$tableau.= $cf->LibStatut($result[$key]['solde'],3);
	$tableau.= '</td></tr></table>';

	$data[$i]= array('rowid'=>$val,'fk_menu'=>$result[$key]['L4KEY'],'statut'=>1,'entry'=>$tableau);
	$i++;
}

$tab = array();
$tab = array_unique(array_column($result, 'L6KEY'));
unset($tab[0]);

foreach ($tab as $key=>$val){
	$tableau = '<table class="nobordernopadding centpercent"><tr><td class="usertdenabled">';
	$tableau.= '<a href="' . dol_buildpath('/affaires/volvo/fourn/commande/card.php',2) . '?id=' .$result[$key]['cfid'] .'">' . $result[$key]['cflabel'].'</a>';
	$tableau.= '</td><td align="right" class="usertdenabled">';
	$tableau.= $cf->LibStatut($result[$key]['solde'],3);
	$tableau.= '</td></tr></table>';

	$data[$i]= array('rowid'=>$val,'fk_menu'=>$result[$key]['L5KEY'],'statut'=>1,'entry'=> $tableau);
	$i++;
}

$tab = array();
$tab = array_unique(array_column($result, 'L7KEY'));
unset($tab[0]);

foreach ($tab as $key=>$val){
	$tableau = '<table class="nobordernopadding centpercent"><tr><td class="usertdenabled">';
	$tableau.= '<a href="' . dol_buildpath('/fourn/facture/card.php',2) . '?id=' .$result[$key]['facid'] .'">' . $result[$key]['faclabel'].'</a>';
	$tableau.= '</td><td align="right" class="usertdenabled">';
	$tableau.= $cf->LibStatut($result[$key]['solde'],3);
	$tableau.= '</td></tr></table>';

	$data[$i]= array('rowid'=>$val,'fk_menu'=>$result[$key]['L6KEY'],'statut'=>1,'entry'=> $tableau);
	$i++;
}



//var_dump($data);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";

$param="search_statut=".$search_statut;

print '<table class="liste nohover" width="100%">';

print '<tr class="liste_titre">';
print_liste_field_titre("HierarchicView");
print_liste_field_titre('<div id="iddivjstreecontrol"><a href="#">'.img_picto('','object_category').' '.$langs->trans("UndoExpandAll").'</a> | <a href="#">'.img_picto('','object_category-expanded').' '.$langs->trans("ExpandAll").'</a></div>',$_SERVER['PHP_SELF'],"",'',"",'align="center"');
print_liste_field_titre("Status",$_SERVER['PHP_SELF'],"",'',"",'align="right"');
print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','','','','maxwidthsearch ');
print '</tr>';


$nbofentries=(count($data) - 1);

if ($nbofentries > 0)
{
	print '<tr '.$bc[false].'><td colspan="3">';
	tree_recur($data,$data[0],0);
	print '</td>';
	print '<td></td>';
	print '</tr>';
}
else
{
	print '<tr '.$bc[true].'>';
	print '<td colspan="3">';
	print '<table class="nobordernopadding"><tr class="nobordernopadding"><td>'.img_picto_common('','treemenu/branchbottom.gif').'</td>';
	print '<td valign="middle">';
	print $langs->trans("NoCategoryYet");
	print '</td>';
	print '<td>&nbsp;</td>';
	print '</table>';
	print '</td>';
	print '<td></td>';
	print '</tr>';
}

print "</table>";
print "</form>\n";


//
/*print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery(".usertddisabled").hide();
	}
	init_myfunc();
});
</script>';
*/

llxFooter();

$db->close();
