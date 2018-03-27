<?php
if ($action == 'edit_extras') {
	$value = (isset ( $_POST ["options_" . $key] ) ? $_POST ["options_" . $key] : $object->array_options ["options_" . $key]);
} else {
	$value = $object->array_options ["options_" . $key];
}
if ($extrafields->attribute_type [$key] == 'separate') {
	print $extrafields->showSeparator ( $key );
} else {
	if (! empty ( $extrafields->attribute_hidden [$key] ))
		print '<td height="100">';
	else
		print '<td>';

	$permok = false;
	$keyforperm = $object->element;
	if ($object->element == 'fichinter')
		$keyforperm = 'ficheinter';
	if (isset ( $user->rights->$keyforperm ))
		$permok = $user->rights->$keyforperm->creer || $user->rights->$keyforperm->create || $user->rights->$keyforperm->write;
	if ($object->element == 'order_supplier')
		$permok = $user->rights->fournisseur->commande->creer;
	if ($object->element == 'invoice_supplier')
		$permok = $user->rights->fournisseur->facture->creer;
	if ($object->element == 'shipping')
		$permok = $user->rights->expedition->creer;
	if ($object->element == 'delivery')
		$permok = $user->rights->expedition->livraison->creer;

	$html_id = ! empty ( $object->id ) ? $object->element . '_extras_' . $key . '_' . $object->id : '';

	print '<table width="100%" class="nobordernopadding"><tr><td align ="left" id="' . $html_id . '" class="' . $object->element . '_extras_' . $key . '">';

	print $langs->trans ( $label ) . ': ';

	// Convert date into timestamp format
	if (in_array ( $extrafields->attribute_type [$key], array (
			'date',
			'datetime'
	) )) {
		$value = isset ( $_POST ["options_" . $key] ) ? dol_mktime ( $_POST ["options_" . $key . "hour"], $_POST ["options_" . $key . "min"], 0, $_POST ["options_" . $key . "month"], $_POST ["options_" . $key . "day"], $_POST ["options_" . $key . "year"] ) : $db->jdate ( $object->array_options ['options_' . $key] );
	}

	if ($action == 'edit_extras' && $permok && GETPOST ( 'attribute' ) == $key) {
		print '<form enctype="multipart/form-data" action="' . $_SERVER ["PHP_SELF"] . '" method="post" name="formextra">';
		print '<input type="hidden" name="action" value="update_extras">';
		print '<input type="hidden" name="attribute" value="' . $key . '">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="id" value="' . $object->id . '">';

		print $extrafields->showInputField ( $key, $value, '', '', '', 0, $object->id );

		print '<input type="submit" class="button" value="' . $langs->trans ( 'Modify' ) . '">';

		print '</form>';
	} else {
		print $extrafields->showOutputField ( $key, $value );
	}
	print '</td>';
	if (empty ( $align_button ))
		$align_button = 'right';
	if (($object->statut == 0 || $extrafields->attribute_alwayseditable [$key]) && $permok && ($action != 'edit_extras' || GETPOST ( 'attribute' ) != $key))
		print '<td align="' . $align_button . '"><a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $object->id . '&action=edit_extras&attribute=' . $key . '">' . img_edit () . '</a></td>';

	print '</tr></table>';
	print '</td>' . "\n";
}