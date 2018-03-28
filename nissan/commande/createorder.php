<?php
$res = @include '../../main.inc.php'; // For root directory
if (! $res)
	$res = @include '../../../main.inc.php'; // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

dol_include_once('/affaires/class/html.formaffaires.class.php');
dol_include_once('/affaires/class/affaires.class.php');

$form = new Form($db);
$formvolvo = new FormAffaires($db);
$object = new Affaires($db);
$objectdet = new Affaires_det($db);

$langs->load('orders');

$vehid = GETPOST('vehid', 'int');
$action = GETPOST('action', 'alpha');

if ($vehid > 0) {
	$ret = $objectdet->fetch($vehid);
	if ($ret < 0)
		setEventMessages(null, $object->errors, 'errors');

		$ret = $object->fetch($objectdet->fk_affaires);
		if ($ret < 0)
			setEventMessages(null, $object->errors, 'errors');
			$object->fetch_thirdparty();
} else {
	exit('need vehid');
}

if ($action == 'creatorder') {

	$objectdet->prixvente = GETPOST('prixvente','int');
	$objectdet->commission = GETPOST('commission', 'int');
	$objectdet->datelivprev = dol_mktime(0, 0, 0, GETPOST('datelivprev_month', 'int'), GETPOST('datelivprev_day', 'int'), GETPOST('datelivprev_year', 'int'));
	$objectdet->interne = GETPOST('interne', 'array');
	$objectdet->externe = GETPOST('externe', 'array');
	$objectdet->divers = GETPOST('divers', 'array');
	$objectdet->obligatoire = json_decode(GETPOST('obligatoire'), true);
	$res = $objectdet->createcmd();
	if ($res<0){
		setEventMessages(null,$objectdet->errors,'errors');
		var_dump($objectdet->errors,$res);
		exit;
	} else {
		top_htmlhead('', '');
		print '<script type="text/javascript">' . "\n";
		print '	$(document).ready(function () {' . "\n";
		print '	window.parent.$(\'#popCreateOrder\').dialog(\'close\');' . "\n";
		print '	window.parent.$(\'#popCreateOrder\').remove();' . "\n";
		print '});' . "\n";
		print '</script>' . "\n";
		llxFooter();
		exit();
	}

}

$sql0 = "SELECT DISTINCT p.rowid, p.label FROM " . MAIN_DB_PREFIX . "product as p INNER JOIN " . MAIN_DB_PREFIX . "categorie_product as c ON p.rowid = c.fk_product ";
$sql0 .= "WHERE c.fk_categorie = " . $conf->global->VOLVO_OBLIGATOIRE . " AND p.tosell = 1";

$resql = $db->query($sql0);
$obligatoire = array();
if ($resql) {
	while ( $obj = $db->fetch_object($resql) ) {
		$obligatoire[] = $obj->rowid;
	}
} else {
	setEventMessage($db->lasterror, 'errors');
}

$sql1 = "SELECT c.rowid, c.label ";
$sql1.= "FROM " . MAIN_DB_PREFIX . "categorie AS c ";
$sql1.= "WHERE fk_parent = ". $conf->global->VOLVO_INTERNE;
$sql1.= " ORDER BY c.label";
$resql = $db->query($sql1);
$interne = array();
if ($resql) {
	while ( $obj = $db->fetch_object($resql) ) {
		$sql10 = "SELECT DISTINCT p.rowid, p.label, ";
		$sql10.= "MAX(IF(c.fk_categorie=" . $obj->rowid .",1,0)) AS CATEG, ";
		$sql10.= "MAX(IF(c.fk_categorie=" . $conf->global->VOLVO_OBLIGATOIRE .",1,0)) AS CATEG_EXC ";
		$sql10.= "FROM " . MAIN_DB_PREFIX . "product as p INNER JOIN " . MAIN_DB_PREFIX . "categorie_product as c ON p.rowid = c.fk_product ";
		$sql10.= "WHERE p.tosell = 1 ";
		$sql10.= "GROUP BY p.rowid ";
		$sql10.= "HAVING CATEG = 1 AND CATEG_EXC !=1 ";
		$sql10.= "ORDER BY p.label";

		$resql2 = $db->query($sql10);
		if ($resql2) {
			$list=array();
			while ( $obj2 = $db->fetch_object($resql2) ) {
				$list[$obj2->rowid] = $obj2->label;
			}
			$interne[$obj->label] = $list;
		} else {
			setEventMessage($db->lasterror, 'errors');
		}
	}
} else {
	setEventMessage($db->lasterror, 'errors');
}

$sql2 = "SELECT c.rowid, c.label ";
$sql2.= "FROM " . MAIN_DB_PREFIX . "categorie AS c ";
$sql2.= "WHERE fk_parent = ". $conf->global->VOLVO_EXTERNE;
$sql2.= " ORDER BY c.label";
$resql = $db->query($sql2);
$externe = array();
if ($resql) {
	while ( $obj = $db->fetch_object($resql) ) {
		$sql20 = "SELECT DISTINCT p.rowid, p.label, ";
		$sql20.= "MAX(IF(c.fk_categorie=" . $obj->rowid .",1,0)) AS CATEG, ";
		$sql20.= "MAX(IF(c.fk_categorie=" . $conf->global->VOLVO_OBLIGATOIRE .",1,0)) AS CATEG_EXC ";
		$sql20.= "FROM " . MAIN_DB_PREFIX . "product as p INNER JOIN " . MAIN_DB_PREFIX . "categorie_product as c ON p.rowid = c.fk_product ";
		$sql20.= "WHERE p.tosell = 1 ";
		$sql20.= "GROUP BY p.rowid ";
		$sql20.= "HAVING CATEG = 1 AND CATEG_EXC !=1 ";
		$sql20.= "ORDER BY p.label";

		$resql2 = $db->query($sql20);
		if ($resql2) {
			$list=array();
			while ( $obj2 = $db->fetch_object($resql2) ) {
				$list[$obj2->rowid] = $obj2->label;
			}
			$externe[$obj->label] = $list;
		} else {
			setEventMessage($db->lasterror, 'errors');
		}
	}
} else {
	setEventMessage($db->lasterror, 'errors');
}

$sql3 = "SELECT c.rowid, c.label ";
$sql3.= "FROM " . MAIN_DB_PREFIX . "categorie AS c ";
$sql3.= "WHERE fk_parent = ". $conf->global->VOLVO_DIVERS;
$sql3.= " ORDER BY c.label";
$resql = $db->query($sql3);
$divers = array();
if ($resql) {
	while ( $obj = $db->fetch_object($resql) ) {
		$sql30 = "SELECT DISTINCT p.rowid, p.label, ";
		$sql30.= "MAX(IF(c.fk_categorie=" . $obj->rowid .",1,0)) AS CATEG, ";
		$sql30.= "MAX(IF(c.fk_categorie=" . $conf->global->VOLVO_OBLIGATOIRE .",1,0)) AS CATEG_EXC ";
		$sql30.= "FROM " . MAIN_DB_PREFIX . "product as p INNER JOIN " . MAIN_DB_PREFIX . "categorie_product as c ON p.rowid = c.fk_product ";
		$sql30.= "WHERE p.tosell = 1 ";
		$sql30.= "GROUP BY p.rowid ";
		$sql30.= "HAVING CATEG = 1 AND CATEG_EXC !=1 ";
		$sql30.= "ORDER BY p.label";

		$resql2 = $db->query($sql30);
		if ($resql2) {
			$list=array();
			while ( $obj2 = $db->fetch_object($resql2) ) {
				$list[$obj2->rowid] = $obj2->label;
			}
			$divers[$obj->label] = $list;
		} else {
			setEventMessage($db->lasterror, 'errors');
		}
	}
} else {
	setEventMessage($db->lasterror, 'errors');
}

$internesection='';
foreach ($interne as $key=>$array){
	$internesection.= '<div class="cal_event cal_event_busy" align="left" id="fixe_'. $key . '" style="background:#223458; ';
	$internesection.= 'background: -webkit-gradient(linear, left top, left bottom, from(#223458), to(#5174bc)); ';
	$internesection.= 'border-radius:6px; margin-bottom: 3px;">';
	$internesection.= '<a href="" onclick="javascript:visibilite(\'' . $key . '\'); return false;" >'. img_edit_add('+','') . '<h style="font-size: large; color: white;"><b>' . $key . ' </b></h></a>';
	$internesection.= '</div>';
	$internesection.= '<div id="' . $key . '" style="display:none;">';
	$internesection.= $formvolvo->select_withcheckbox("interne" ,$array);
	$internesection.= '</div>';

}

$externesection='';
foreach ($externe as $key=>$array){
	$externesection.= '<div class="cal_event cal_event_busy" align="left" id="fixe_'. $key . '" style="background:#223458; ';
	$externesection.= 'background: -webkit-gradient(linear, left top, left bottom, from(#223458), to(#5174bc)); ';
	$externesection.= 'border-radius:6px; margin-bottom: 3px;">';
	$externesection.= '<a href="" onclick="javascript:visibilite(\'' . $key . '\'); return false;" >'. img_edit_add('+','') . '<h style="font-size: large; color: white;"><b>' . $key . ' </b></h></a>';
	$externesection.= '</div>';
	$externesection.= '<div id="' . $key . '" style="display:none;">';
	$externesection.= $formvolvo->select_withcheckbox("externe" ,$array);
	$externesection.= '</div>';

}

$diversection='';
foreach ($divers as $key=>$array){
	$diversection.= '<div class="cal_event cal_event_busy" align="left" id="fixe_'. $key . '" style="background:#223458; ';
	$diversection.= 'background: -webkit-gradient(linear, left top, left bottom, from(#223458), to(#5174bc)); ';
	$diversection.= 'border-radius:6px; margin-bottom: 3px;">';
	$diversection.= '<a href="" onclick="javascript:visibilite(\'' . $key . '\'); return false;" >'. img_edit_add('+','') . '<h style="font-size: large; color: white;"><b>' . $key . ' </b></h></a>';
	$diversection.= '</div>';
	$diversection.= '<div id="' . $key . '" style="display:none;">';
	$diversection.= $formvolvo->select_withcheckbox("divers",$array);
	$diversection.= '</div>';
}

top_htmlhead('', '');
$var = ! $var;

print '<form name="createorder" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="vehid" value="' . $objectdet->id . '">';
print '<input type="hidden" name="action" value="creatorder">';
print '<input type="hidden" name="obligatoire" value="' . htmlspecialchars(json_encode($obligatoire)) . '">';

print '<table class="border" width="100%">';
print '<tr class="liste_titre">';
print '<th align="center" colspan="3">' . "Transformation d'une affaire en commande</th>";
print '</tr>';
print '<tr ' . $bc[$var] . '>';
print '<td colspan="3">';
print '<table width="100%" class="nobordernopadding">';
print '<tr ' . $bc[$var] . '>';
print '<td align="center">' . $langs->trans('Prix de vente du véhicule') . ': <input type="text" name="prixvente" size="7" value=""/> €</td>';
print '<td align="center">' . $langs->trans('commission Dealer sur fiche de décision') . ': <input type="text" name="commission" size="7" value=""/> €</td>';
print '<td align="center">' . $langs->trans('Date de livraison souhaitée') . ': ' . $form->select_date('', 'datelivprev_', 0, 0, 1, '', 1, 1, 1, 0, '', '', '') . '</td>';
print '</tr>';
print '</table>';
print '</td>';
print '</tr>';
print '<tr class="liste_titre">';
print '<th align="center" width="33%">' . $langs->trans('Travaux internes') . '</th>';
print '<th align="center" width="33%">' . $langs->trans('Travaux externes') . '</th>';
print '<th align="center" width="33%">' . $langs->trans('Travaux divers') . '</th>';
print '</tr>';
print '<tr >';
print '<td align="left" valign="top">' . $internesection . '</td>';
print '<td align="left" valign="top">' . $externesection . '</td>';
print '<td align="left" valign="top">' . $diversection . '</td>';
print '</tr>';

print '</table>';
print '<div class="tabsAction">';
print '<input type="submit" align="center" class="button" value="' . $langs->trans('Save') . '" name="save" id="save"/>';
print '</div>';
print '</form>';


?>
<script type="text/javascript">
function visibilite(thingId) {
	var targetElement;
	targetElement = document.getElementById(thingId) ;
	if (targetElement.style.display == "none") {
		targetElement.style.display = "" ;
	} else {
		targetElement.style.display = "none" ;
	}
}
</script>
<?php
llxFooter();
$db->close();
?>