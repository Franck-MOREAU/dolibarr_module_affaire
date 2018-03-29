<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015-2016	Marcos García		<marcosgdf@gmail.com>
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
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $senderissupplier (0 by default, 1 or 2 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 */

// Protection to avoid direct call of template
if (empty($object) || ! is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}


$usemargins=0;
if (! empty($conf->margin->enabled) && ! empty($object->element) && in_array($object->element,array('facture','propal','commande')))
{
	$usemargins=1;
}

if (! isset($dateSelector)) global $dateSelector;	// Take global var only if not already defined into function calling (for example formAddObjectLine)
global $forceall, $forcetoshowtitlelines, $senderissupplier, $inputalsopricewithtax;

if (! isset($dateSelector)) $dateSelector=1;    // For backward compatibility
elseif (empty($dateSelector)) $dateSelector=0;
if (empty($forceall)) $forceall=0;
if (empty($senderissupplier)) $senderissupplier=0;
if (empty($inputalsopricewithtax)) $inputalsopricewithtax=0;


// Define colspan for button Add
$colspan = 8;

// Lines for extrafield
$objectline = new OrderLine($this->db);

print '<!-- BEGIN PHP TEMPLATE objectline_create.tpl.php for order-->';

print '<tr class="liste_titre' . (($nolinesbefore || $object->element == 'contrat') ? '' : ' liste_titre_add_') . 'nodrag nodrop">';
print '<td class="linecoldescription minwidth500imp">';
print '<div id="add"></div><span class="hideonsmartphone">' . $langs->trans ( 'AddNewLine' ) . '</span>';
print '</td>';
print '<td class="linecoluht" align="right"><span id="title_up_ht">' . $langs->trans ( 'PriceUHT' ) . '</span></td>';
if (! empty ( $inputalsopricewithtax )) {
	print '<td class="linecoluttc" align="right"><span id="title_up_ttc">' . $langs->trans ( 'PriceUTTC' ) . '</span></td>';
}
print '<td class="linecolqty" align="right">' . $langs->trans ( 'Qty' ) . '</td>';
if ($conf->global->PRODUCT_USE_UNITS) {
	print '<td class="linecoluseunit" align="left">';
	print '<span id="title_units">';
	print $langs->trans ( 'Unit' );
	print '</span></td>';
}

if (! empty ( $usemargins )) {
	if (! empty ( $user->rights->margins->creer )) {
		print '<td align="right" class="margininfos linecolmargin1" colspan="2">';
	} else
		$colspan ++;
	if ($conf->global->MARGIN_TYPE == "1")
		echo $langs->trans ( 'BuyingPrice' );
	else
		echo $langs->trans ( 'CostPrice' );
	print '</td>';
}
print '<td class="linecoledit" colspan="' . $colspan . '">&nbsp;</td>';
print '</tr>';

print '<tr class="pair nodrag nodrop nohoverpair' . ($nolinesbefore || $object->element=='contrat')?'':' liste_titre_create' .'">';
$coldisplay=0;
print '<td class="nobottom linecoldescription minwidth500imp">';
$forceall=1;	// We always force all type for free lines (module product or service means we use predefined product or service)

// Predefined product/service
if (! empty($conf->product->enabled) || ! empty($conf->service->enabled)){
	$formvolvo = new FormAffairesProduct($db);
	if ($forceall >= 0) print '<br>';
	print '<span class="prod_entry_mode_predef">';
	print '<label for="prod_entry_mode_predef">';
	print '<input type="radio" class="prod_entry_mode_predef" name="prod_entry_mode" id="prod_entry_mode_predef" value="predef" checked> ';
	print $langs->trans('PredefinedProductsAndServicesToSell');
	print '</label>';
	print ' ';
	$filtertype='';
	$formvolvo->select_produits(GETPOST('idprod'), 'idprod', $filtertype, $conf->product->limit_size, $buyer->price_level, 1, 2, '', 1, array(), $buyer->id, '1', 0, 'maxwidth300', 0, '', GETPOST('combinations', 'array'),$conf->global->VOLVO_CAT_PROD);
	print '<input type="hidden" name="pbq" id="pbq" value="">';
	print '</span>';
}
print '</td>';

print '<td class="nobottom linecoluht" align="right">';
print '<input type="text" size="5" name="price_ht" id="price_ht" class="flat right" value="' . (isset($_POST["price_ht"])?GETPOST("price_ht",'alpha',2):'') . '">';
print '</td>';

print '<td class="nobottom linecolqty" align="right">';
print '<input type="text" size="2" name="qty" id="qty" class="flat right" value="' . (isset($_POST["qty"])?GETPOST("qty",'alpha',2):1) . '">';
print '</td>';

if($conf->global->PRODUCT_USE_UNITS) {
	print '<td class="nobottom linecoluseunit" align="left">';
	print $form->selectUnits($line->fk_unit, "units");
	print '</td>';
}

if (! empty($usemargins)){
	if (!empty($user->rights->margins->creer)) {
		print '<td align="right" class="nobottom margininfos linecolmargin">';
		print '<select id="fournprice_predef" name="fournprice_predef" class="flat"></select>';
		print '</td>';
		print '<td align="right" class="nobottom margininfos linecolmargin">';
		print '<input type="text" size="5" style="display:none" id="buying_price" name="buying_price" class="flat right" value="' . (isset($_POST["buying_price"])?GETPOST("buying_price",'alpha',2):'') .'">';
		print '</td>';
		$coldisplay++;
	}
}
print '<td class="nobottom linecoledit" align="right" valign="middle" colspan="' . $colspan . '">';
print '<input type="submit" class="button" value="' . $langs->trans('Add') .'" name="addline" id="addline">';
print '</td>';
print '</tr>';
print '<tr class="pair nodrag nodrop nohoverpair' . ($nolinesbefore || $object->element=='contrat')?'':' liste_titre_create' .'">';
print '<td class="nobottom linecoldescription minwidth500imp" align="left" colspan="20">';
print 'Commentaire: <input type="text" size="150" name="dp_desc" id="dp_desc" class="flat left" value="' . (isset($_POST["dp_desc"])?GETPOST("dp_desc",'alpha',2):'') . '">';
print '</td>';
print '</tr>';

?>

<script type="text/javascript">



/* JQuery for product free or predefined select */
jQuery(document).ready(function() {
	$("#prod_entry_mode_predef").on( "click", function() {
		console.log("click prod_entry_mode_predef");
		setforpredef();
		jQuery('#trlinefordates').show();
	});

	/* When changing predefined product, we reload list of supplier prices required for margin combo */
	$("#idprod, #idprodfournprice").change(function()
	{
		console.log("#idprod, #idprodfournprice change triggered");

		setforpredef();		// TODO Keep vat combo visible and set it to first entry into list that match result of get_default_tva

		jQuery('#trlinefordates').show();

		<?php
		if (! empty($usemargins) && $user->rights->margins->creer)
		{
			$langs->load('stocks');
			?>

    		/* Code for margin */
      		$("#fournprice_predef").find("option").remove();
    		$("#fournprice_predef").hide();
    		$("#buying_price").val("").show();
    		/* Call post to load content of combo list fournprice_predef */
      		$.post('<?php echo DOL_URL_ROOT; ?>/fourn/ajax/getSupplierPrices.php?bestpricefirst=1', { 'idprod': $(this).val() }, function(data) {
    	    	if (data && data.length > 0)
    	    	{
        	  		var options = '';
        	  		var defaultkey = '';
        	  		var defaultprice = '';
    	      		var bestpricefound = 0;

    	      		var bestpriceid = 0; var bestpricevalue = 0;
    	      		var pmppriceid = 0; var pmppricevalue = 0;
    	      		var costpriceid = 0; var costpricevalue = 0;

    				/* setup of margin calculation */
    	      		var defaultbuyprice = '<?php
    	      		if (isset($conf->global->MARGIN_TYPE))
    	      		{
    	      		    if ($conf->global->MARGIN_TYPE == '1')   print 'bestsupplierprice';
    	      		    if ($conf->global->MARGIN_TYPE == 'pmp') print 'pmp';
    	      		    if ($conf->global->MARGIN_TYPE == 'costprice') print 'costprice';
    	      		} ?>';
    	      		console.log("we will set the field for margin. defaultbuyprice="+defaultbuyprice);

    	      		var i = 0;
    	      		$(data).each(function() {
    	      			if (this.id != 'pmpprice' && this.id != 'costprice')
    		      		{
    		        		i++;
                            this.price = parseFloat(this.price);
    			      		if (bestpricefound == 0 && this.price > 0) { defaultkey = this.id; defaultprice = this.price; bestpriceid = this.id; bestpricevalue = this.price; bestpricefound=1; }	// bestpricefound is used to take the first price > 0
    		      		}
    	      			if (this.id == 'pmpprice')
    	      			{
    	      				// If margin is calculated on PMP, we set it by defaut (but only if value is not 0)
    			      		//console.log("id="+this.id+"-price="+this.price);
    			      		if ('pmp' == defaultbuyprice || 'costprice' == defaultbuyprice)
    			      		{
    			      			if (this.price > 0) {
    				      			defaultkey = this.id; defaultprice = this.price; pmppriceid = this.id; pmppricevalue = this.price;
    			      				//console.log("pmppricevalue="+pmppricevalue);
    			      			}
    			      		}
    	      			}
    	      			if (this.id == 'costprice')
    	      			{
    	      				// If margin is calculated on Cost price, we set it by defaut (but only if value is not 0)
    			      		//console.log("id="+this.id+"-price="+this.price+"-pmppricevalue="+pmppricevalue);
    			      		if ('costprice' == defaultbuyprice)
    			      		{
    		      				if (this.price > 0) { defaultkey = this.id; defaultprice = this.price; costpriceid = this.id; costpricevalue = this.price; }
    		      				else if (pmppricevalue > 0) { defaultkey = pmppriceid; defaultprice = pmppricevalue; }
    			      		}
    	      			}
    	        		options += '<option value="'+this.id+'" price="'+this.price+'">'+this.label+'</option>';
    	      		});
    	      		options += '<option value="inputprice" price="'+defaultprice+'"><?php echo $langs->trans("InputPrice"); ?></option>';

    	      		console.log("finally selected defaultkey="+defaultkey+" defaultprice="+defaultprice);

    	      		$("#fournprice_predef").html(options).show();
    	      		if (defaultkey != '')
    				{
    		      		$("#fournprice_predef").val(defaultkey);
    		      	}

    	      		/* At loading, no product are yet selected, so we hide field of buying_price */
    	      		$("#buying_price").hide();

    	      		/* Define default price at loading */
    	      		var defaultprice = $("#fournprice_predef").find('option:selected').attr("price");
    			    $("#buying_price").val(defaultprice);

    	      		$("#fournprice_predef").change(function() {
    		      		console.log("change on fournprice_predef");
    	      			/* Hide field buying_price according to choice into list (if 'inputprice' or not) */
    					var linevalue=$(this).find('option:selected').val();
    	        		var pricevalue = $(this).find('option:selected').attr("price");
    	        		if (linevalue != 'inputprice' && linevalue != 'pmpprice') {
    	          			$("#buying_price").val(pricevalue).hide();	/* We set value then hide field */
    	        		}
    	        		if (linevalue == 'inputprice') {
    		          		$('#buying_price').show();
    	        		}
    	        		if (linevalue == 'pmpprice') {
    	        			$("#buying_price").val(pricevalue);
    		          		$('#buying_price').hide();
    	        		}
    				});
    	    	}
    	  	},
    	  	'json');

  		<?php
        }
        ?>

        /* To process customer price per quantity */
        var pbq = $('option:selected', this).attr('data-pbq');
        var pbqqty = $('option:selected', this).attr('data-pbqqty');
        var pbqpercent = $('option:selected', this).attr('data-pbqpercent');
        if (jQuery('#idprod').val() > 0 && typeof pbq !== "undefined")
        {
            console.log("We choose a price by quanty price_by_qty id = "+pbq+" price_by_qty qty = "+pbqqty+" price_by_qty percent = "+pbqpercent);
            jQuery("#pbq").val(pbq);
            if (jQuery("#qty").val() < pbqqty)
            {
                    jQuery("#qty").val(pbqqty);
            }
            if (jQuery("#remise_percent").val() < pbqpercent)
            {
                    jQuery("#remise_percent").val(pbqpercent);
            }
        }
        else
        {
            jQuery("#pbq").val('');
        }

  		/* To set focus */
  		if (jQuery('#idprod').val() > 0 || jQuery('#idprodfournprice').val() > 0)
  	  	{
			/* focus work on a standard textarea but not if field was replaced with CKEDITOR */
			jQuery('#dp_desc').focus();
			/* focus if CKEDITOR */
			if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined")
			{
				var editor = CKEDITOR.instances['dp_desc'];
   				if (editor) { editor.focus(); }
			}
  	  	}
	});

	<?php if (GETPOST('prod_entry_mode') == 'predef') { // When we submit with a predef product and it fails we must start with predef ?>
		setforpredef();
	<?php } ?>

});

/* Function to set fields from choice */

function setforpredef() {
	console.log("Call setforpredef. We hide some fields");
	jQuery("#select_type").val(-1);

	jQuery("#prod_entry_mode_free").prop('checked',false).change();
	jQuery("#prod_entry_mode_predef").prop('checked',true).change();
	jQuery("#buying_price").show();
	jQuery("#units, #title_units").hide();
}

</script>

<!-- END PHP TEMPLATE objectline_create.tpl.php -->
