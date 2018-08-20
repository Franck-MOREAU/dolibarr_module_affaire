<?php
/* Volvo
 * Copyright (C) 2014-2015 Florian HENRY <florian.henry@open-concept.pro>
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

dol_include_once('/affaires/class/html.formaffaires.class.php');
dol_include_once('/core/class/html.formother.class.php');

global $db, $user;

$form = new Form($db);
$formother = new FormOther($db);
$formAffaires = new FormAffaires($db);

$contextpage = GETPOST('contextpage', 'alpha');
$action = GETPOST('action', 'alpha');


$arrayfields= $_SESSION[$contextpage . '_arrayfields'];
$arrayfields = json_decode($arrayfields,true);

if ($action == 'createfilter') {
	$filter=array();
	foreach ($arrayfields as $field) {
		if($field['checked']==1 && !empty($field['search'])){
			switch($field['type']){
				case 'user':
					$val = GETPOST($field['post']);
					if($val<0) $val =0;
					if(!empty($val)){
						$filter[$field['post']] = array('val'=>$val,'sql'=>$field['search'][0]."='".$val ."'");
					}
					break;

				case 'text':
					$val = GETPOST('search_' . $field['post']);
					if(!empty($val)){
						$filter[$field['post']] = array('val'=>$val,'sql'=>$field['search'][0] . " LIKE '%" . $val . "%'");
					}
					break;

				case 'predifined':
					$val = GETPOST($field['post']);
					if(!empty($val)){
						$filter[$field['post']] = array('val'=>1,'sql'=>$field['search'][0]);
					}
					break;

				case 'soc':
					$val = GETPOST('search_' . $field['post']);
					if($val<0) $val =0;
					$val1 = GETPOST('search_like_' . $field['post']);
					$val2 = GETPOST('search_socctm_' . $field['post']);
					if($val2<0) $val2 =0;
					if(!empty($val)){
						$filter[$field['post']] = array('val'=>$val,'sql'=>$field['search'][0] ."='" .$val."'");
					}elseif(!empty($val1)){
						$filter[$field['post']. '_like'] = array('val'=>$val1,'sql'=>$field['search'][1] . " LIKE '%" . $val1 . "%'");
					}elseif(!empty($val2)){
						$filter[$field['post']. '_socctm'] = array('val'=>$val2,'sql'=>"'" . $val2 . "' IN " . $field['search'][2]);
					}
					break;

				case 'ctm':
					$val = GETPOST('search_' . $field['post']);
					if($val<0) $val =0;
					$val1 = GETPOST('search_like_' . $field['post']);
					$val2 = GETPOST($field['post']. '_isnull');

					if(!empty($val)){
						$filter[$field['post']] = array('val'=>$val,'sql'=> $field['search'][0] . "='" . $val."'");
					}elseif(!empty($val1)){
						$filter[$field['post']. '_like'] = array('val'=>$val1,'sql'=>$field['search'][1] . " LIKE '%" . $val1 . "%'");
					}elseif(!empty($val2)){
						$filter[$field['post']. '_isnull'] = array('val'=>1,'sql'=> $field['search'][2] . ' IS NULL');
					}
					break;

				case 'date':
					$val=dol_mktime ( 0, 0, 0, GETPOST ( $field['post']. '_since_month' ), GETPOST ( $field['post']. '_since_day' ), GETPOST ( $field['post']. '_since_year' ) );
					if($val<0) $val =0;
					$val1=dol_mktime ( 0, 0, 0, GETPOST ( $field['post']. '_to_month' ), GETPOST ( $field['post']. '_to_day' ), GETPOST ( $field['post']. '_to_year' ) );
					if($val1<0) $val1=0;
					$val2=GETPOST($field['post']. '_isnull');

					if(!empty($val) && !empty($val1)){
						$filter[$field['post'].'_between'] = array('val'=>array($val, $val1),'sql'=>$field['search'][0] . " BETWEEN '" . $db->idate($val) . "' AND '" . $db->idate($val1)."'");
					}elseif(!empty($val) && empty($val1)){
						$filter[$field['post'].'_since'] = array('val'=>$val,'sql'=>$field['search'][0] . " >= '" . $db->idate($val) ."'");
					}elseif(empty($val) && !empty($val1)){
						$filter[$field['post'].'_to'] =  array('val'=>$val1,'sql'=>$field['search'][0] . " <= '" . $db->idate($val1) ."'");;
					}elseif(!empty($val2)){
						$filter[$field['post']. '_isnull'] =  array('val'=>1,'sql'=>$field['search'][0] . ' IS NULL ');
					}
					break;

				case 'num':
					$val = GETPOST('search_inf_' . $field['post']);
					$val1 = GETPOST('search_sup_' . $field['post']);
					$val2 = GETPOST($field['post']. '_isnull');

					if(!empty($val) && !empty($val1)){
						$filter[$field['post'].'_between'] = array('val'=>array($val,$val1),'sql'=>$field['search'][0] . " BETWEEN '" . $val . "' AND '" . $val1 ."'");
					}elseif(!empty($val) && empty($val1)){
						$filter[$field['post'].'_inf'] = array('val'=>$val,'sql'=>$field['search'][0] . " >= '" . $val ."'");
					}elseif(empty($val) && !empty($val1)){
						$filter[$field['post'].'_sup'] = array('val'=>$val1,'sql'=>$field['search'][0] . " <= '" . $val1 ."'");
					}elseif(!empty($val2)){
						$filter[$field['post']. '_isnull'] = array('val'=>1,'sql'=>$field['search'][0] . ' IS NULL ');
					}
					break;

				case 'list':
					$val = GETPOST('search_' . $field['post']);
					if($val<0) $val =0;

					if(!empty($val)) $filter[$field['post']] = array('val'=>$val,'sql'=>$field['search'][0] . '=' . $val);
					break;
			}
		}
	}

	$_SESSION[$contextpage . '_filter']=json_encode($filter);
	unset($_POST['action']);
 	top_htmlhead('', '');
//  print_r($filter);
	print '<script type="text/javascript">' . "\n";
	print '	$(document).ready(function () {' . "\n";
	print '	window.parent.$(\'#popfilter\').dialog(\'close\');' . "\n";
	print '	window.parent.$(\'#popfilter\').remove();' . "\n";
	print '});' . "\n";
	print '</script>' . "\n";
 	llxFooter();
	exit();
}

$filter = $_SESSION[$contextpage . '_filter'];
$filter = json_decode($filter,true);

top_htmlhead('', '');

print_fiche_titre("Création d'un filtre", '', img_picto('','list','',0,1), 1);

print '<form name="createfilter" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="createfilter">';
print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

print '<table class="tagtable liste">' . "\n";
print '<tr class="liste_titre">';
print '<th class="liste_titre">Champ</th>';
print '<th class="liste_titre">Filtre 1</th>';
print '<th class="liste_titre">Filtre 2</th>';
print '<th class="liste_titre">Filtre 3</th>';
print '</tr>';

foreach ($arrayfields as $field) {
	if($field['checked']==1 && !empty($field['search'])){
		print '<tr>';
		switch($field['type']){
			case 'user':
				unset($val);
				$val =$filter[$field['post']]['val'];
				print '<td>' . $field['label'] . '</td>';
				if(!empty($user->rights->affaires->all)){
					print '<td> est: ' . $formAffaires->select_salesmans($val,  $field['post'], 'Commerciaux', 1) . '</td>';
				}else{
					print '<td> est: ' . $user->firstname . ' ' . $user->lastname . '</td>';
				}
				print '<td></td>';
				print '<td></td>';
				break;

			case 'text':
				unset($val);
				$val = $filter[$field['post']]['val'];

				print '<td>' . $field['label'] . '</td>';
				print '<td> contient: <input type="text" class="flat" name="search_'. $field['post'] . '" value="' . $val . '" size="20"></td>';
				print '<td></td>';
				print '<td></td>';
				break;

			case 'predifined':
				unset($val);
				$val = $filter[$field['post']]['val'];

				print '<td>' . $field['label'] . '</td>';
				print '<td> <input name="' . $field['post'] . '" type="checkbox" value="1"'. (!empty($val)?' checked':'') . '></td>';
				print '<td></td>';
				print '<td></td>';
				break;

			case 'soc':
				unset($val, $val1,$val2);
				$val = $filter[$field['post']]['val'];
				$val1 = $filter[$field['post'].'_like']['val'];
				$val2 = $filter[$field['post'] . '_socctm']['val'];

				print '<td>' . $field['label'] . '</td>';
				print '<td> est: ' .$form->select_company ( $val, 'search_' . $field['post'], 's.client = 1 OR s.client = 3', 'SelectThirdParty' ) . '</td>';
				print '<td> contient: <input type="text" class="flat" name="search_like_'. $field['post'] . '" value="' . $val1 . '" size="20"></td>';
				print '<td> client ou contremarque est: ' .$form->select_company ( $val2, 'search_socctm_' . $field['post'], 's.client = 1 OR s.client = 3', 'SelectThirdParty' ) . '</td>';
				break;

			case 'ctm':
				unset($val, $val1,$val2);
				$val = $filter[$field['post']]['val'];
				$val1 = $filter[$field['post'] . '_like']['val'];
				$val2 = $filter[$field['post'] . '_isnull']['val'];

				print '<td>' . $field['label'] . '</td>';
				print '<td> est: ' .$form->select_company ( $val, 'search_' . $field['post'], 's.client = 1 OR s.client = 3', 'SelectThirdParty' ) . '</td>';
				print '<td> contient: <input type="text" class="flat" name="search_like_'. $field['post'] . '" value="' . $val1 . '" size="20"></td>';
				print '<td> Est null ? <input name="' . $field['post'] . '_isnull" type="checkbox" value="1"'. (!empty($val2)?' checked':'') . '></td>';
				break;

			case 'date':
				unset($val, $val1,$val2);
				if(!empty($filter[$field['post'] . '_between'])){
					$val = $filter[$field['post'].'_between']['val'][0];
					$val1 = $filter[$field['post'].'_between']['val'][1];
				}else{
					$val = $filter[$field['post'] . '_since']['val'];
					$val1 = $filter[$field['post'] . '_to']['val'];
				}
				if($val<=0) $val = -1;
				if($val1<=0) $val1 = -1;
				$val2 = $filter[$field['post'] . '_isnull']['val'];

				print '<td>' . $field['label'] . '</td>';
				print '<td> depuis le: ' . $form->select_date ( $val , $field['post']. '_since_', '', '', '', "filter",1,1,1). '</td>';
				print '<td> jusqu\'au: ' . $form->select_date ($val1, $field['post']. '_to_', '', '', '', "filter",1,1,1). '</td>';
				print '<td> Est null ? <input name="' . $field['post'] . '_isnull" type="checkbox" value="1"'. (!empty($val2)?' checked':'') . '></td>';
				break;

			case 'num':
				unset($val, $val1,$val2);
				if(!empty($filter[$field['post'] . '_between'])){
					$val = $filter[$field['post'].'_between']['val'][0];
					$val1 = $filter[$field['post'].'_between']['val'][1];
				}else{
					$val = $filter[$field['post'] . '_inf']['val'];
					$val1 = $filter[$field['post'] . '_sup']['val'];
				}
				$val2 = $filter[$field['post'] . '_isnull']['val'];

				print '<td>' . $field['label'] . '</td>';
				print '<td> supérieur ou egal a: <input type="text" class="flat" name="search_inf_'. $field['post'] . '" value="' . $val . '" size="8"></td>';
				print '<td> inferieur ou egal a: <input type="text" class="flat" name="search_sup_'. $field['post'] . '" value="' . $val1 . '" size="8"></td>';
				print '<td> Est null ? <input name="' . $field['post'] . '_isnull" type="checkbox" value="1"'. (!empty($val2)?' checked':'') . '></td>';
				break;

			case 'list':
				unset($val);
				$val =$filter[$field['post']]['val'];

				print '<td>' . $field['label'] . '</td>';
				print '<td> est: ' . $form->selectarray('search_' . $field['post'], $field['data'],$val,1,0) . '</td>';
				print '<td></td>';
				print '<td></td>';
				break;

		}
		print '</tr>';
	}
}

Print '</table>';

print '<div class="tabsAction">';
print '<input type="submit" align="center" class="button" value="Filtrer" name="save" id="save"/>';
print '</div>';
print '</form>';

llxFooter();
$db->close();