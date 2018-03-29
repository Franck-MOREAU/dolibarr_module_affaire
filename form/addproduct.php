<?php
$res = @include '../main.inc.php'; // For root directory
if (! $res)
	$res = @include '../../main.inc.php'; // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
dol_include_once('/affaires/class/html.formaffairesproduct.class.php');
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';


$formAffairesProduct = new FormAffairesProduct($db);
$order = new Commande($db);
$product = new Product($db);

$langs->load('orders');

$orderid = GETPOST('orderid', 'int');
$productid = GETPOST('productid', 'int');
$action = GETPOST('action', 'alpha');
$comment = GETPOST('comment','alpha');

$result = $order->fetch($orderid);
if ($result < 0) {
	setEventMessages($order->error, null, 'errors');
}

if ($action == 'addproduct') {
	$error=0;
	if (! empty($productid)) {

		$product->fetch($productid);

		// Set ORder status in memory to draft to allow use of addline
		$current_status = $order->statut;
		$order->statut = $order::STATUS_DRAFT;

		$result = $order->addline($comment, 0, 1, 0, 0, 0, $product->id, 0, 0, 0, 'HT', 0, '', '', 0, - 1, '', '', '', 0, '', $array_options);
		if ($result < 0) {
			if (! empty($order->error)) {
				setEventMessages($order->error, null, 'errors');
				$error++;
			} else {
				setEventMessages('Error on add line', null, 'errors');
				$error++;
			}
		}

		// reset again order in memory status to correct one
		$order->statut = $current_status;
	}

	if (empty($error)) {
		top_htmlhead('', '');
		print '<script type="text/javascript">'."\n";
		print '	$(document).ready(function () {'."\n";
		print '	window.parent.$(\'#popAddProducts\').dialog(\'close\');'."\n";
		print '	window.parent.$(\'#popAddProducts\').remove();'."\n";
		print '});'."\n";
		print '</script>'."\n";
		llxFooter();
		exit;
	}
}

top_htmlhead('', '',0,0,array(),array('overflow'=>'hidden'));
print '<body style="overflow:hidden">';
print '<form name="createorder" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="orderid" value="' . $orderid. '">';
print '<input type="hidden" name="action" value="addproduct">';

print '<table class="border" width="100%">';
print '<tr>';
print '<td>';
$formAffairesProduct->select_produits(0, 'productid', '', '', '', 1, 2, '', 0, array(), 0, 1, 0, '', 0, '', array(), $conf->global->VOLVO_CAT_PROD);
print '</td>';
print '</tr>';
print '<tr>';
print '<td>';
print 'Commentaires: <input type="text" name="comment" value="' . $comment . '" size="30" ></td>';
print '</td>';
print '</tr>';

print '</table>';

print '<div class="tabsAction">';

print '<input type="submit" align="center" class="button" value="' . $langs->trans('Add') . '" name="save" id="save"/>';
print '</div>';
print '</form>';
print '</body>';

//llxFooter();
$db->close();