<?php
/**
 * shopping_cart header_php.php
 *
 * @package page
 * @copyright Copyright 2003-2013 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version GIT: $Id: Author: ajeh  Wed Nov 6 14:38:22 2013 -0500 Modified in v1.5.2 $
 * 
 * Updated for Stock by Attributes 1.5.3.1
 */

// This should be first line of the script:
$zco_notifier->notify('NOTIFY_HEADER_START_SHOPPING_CART');

require(DIR_WS_MODULES . zen_get_module_directory('require_languages.php'));
$breadcrumb->add(NAVBAR_TITLE);

// Validate Cart for checkout
$_SESSION['valid_to_checkout'] = true;
$_SESSION['cart_errors'] = '';
$_SESSION['cart']->get_products(true);

// used to display invalid cart issues when checkout is selected that validated cart and returned to cart due to errors
if (isset($_SESSION['valid_to_checkout']) && $_SESSION['valid_to_checkout'] == false) {
  $messageStack->add('shopping_cart', ERROR_CART_UPDATE . $_SESSION['cart_errors'] , 'caution');
}

// build shipping with Tare included
$shipping_weight = $_SESSION['cart']->show_weight();
/*
  $shipping_weight = 0;
  require(DIR_WS_CLASSES . 'order.php');
  $order = new order;
  require_once('includes/classes/http_client.php'); // shipping in basket
  $total_weight = $_SESSION['cart']->show_weight();
  $total_count = $_SESSION['cart']->count_contents();
  require(DIR_WS_CLASSES . 'shipping.php');
  $shipping_modules = new shipping;
  $quotes = $shipping_modules->quote();
*/
$totalsDisplay = '';
switch (true) {
  case (SHOW_TOTALS_IN_CART == '1'):
  $totalsDisplay = TEXT_TOTAL_ITEMS . $_SESSION['cart']->count_contents() . TEXT_TOTAL_WEIGHT . $shipping_weight . TEXT_PRODUCT_WEIGHT_UNIT . TEXT_TOTAL_AMOUNT . $currencies->format($_SESSION['cart']->show_total());
  break;
  case (SHOW_TOTALS_IN_CART == '2'):
  $totalsDisplay = TEXT_TOTAL_ITEMS . $_SESSION['cart']->count_contents() . ($shipping_weight > 0 ? TEXT_TOTAL_WEIGHT . $shipping_weight . TEXT_PRODUCT_WEIGHT_UNIT : '') . TEXT_TOTAL_AMOUNT . $currencies->format($_SESSION['cart']->show_total());
  break;
  case (SHOW_TOTALS_IN_CART == '3'):
  $totalsDisplay = TEXT_TOTAL_ITEMS . $_SESSION['cart']->count_contents() . TEXT_TOTAL_AMOUNT . $currencies->format($_SESSION['cart']->show_total());
  break;
}

// testing/debugging
//  require(DIR_WS_MODULES . 'debug_blocks/shopping_cart_contents.php');

$flagHasCartContents = ($_SESSION['cart']->count_contents() > 0);
$cartShowTotal = $currencies->format($_SESSION['cart']->show_total());

$flagAnyOutOfStock = false;//initialize flag state
$products = $_SESSION['cart']->get_products();
for ($i=0, $n=sizeof($products); $i<$n; $i++) {
  if (($i/2) == floor($i/2)) {
    $rowClass="rowEven";
  } else {
    $rowClass="rowOdd";
  }
  switch (true) {
    case (SHOW_SHOPPING_CART_DELETE == 1):
    $buttonDelete = true;
    $checkBoxDelete = false;
    break;
    case (SHOW_SHOPPING_CART_DELETE == 2):
    $buttonDelete = false;
    $checkBoxDelete = true;
    break;
    default:
    $buttonDelete = true;
    $checkBoxDelete = true;
    break;
    $cur_row++;
  } // end switch
  $attributeHiddenField = "";
  $attrArray = false;
  $productsName = $products[$i]['name'];
  // Push all attributes information in an array
  if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
    if (PRODUCTS_OPTIONS_SORT_ORDER=='0') {
    	//LPAD - Return the string argument, left-padded with the specified string
    	//example: LPAD(popt.products_options_sort_order,11,"0") the field is 11 digits, and is left padded with 0
      $options_order_by= ' ORDER BY LPAD(popt.products_options_sort_order,11,"0");';
    } else {
      $options_order_by= ' ORDER BY popt.products_options_name;';
    }

    // START "Stock by Attributes"
    $products_options_type = null;
    foreach ($products[$i]['attributes'] as $option => $value) {

    	$attributes = "SELECT popt.products_options_name, popt.products_options_type,
    						  poval.products_options_values_name, 
							  pa.options_values_price, pa.price_prefix

                     	FROM  	  " . TABLE_PRODUCTS_OPTIONS        . " popt 
						LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES     . " pa    ON (pa.options_id = popt.products_options_id)
						LEFT JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval ON (pa.options_values_id = poval.products_options_values_id)
    			
    					WHERE pa.products_id       = :productsID
		                  AND pa.options_id        = :optionsID
	    				  AND pa.options_values_id = :optionsValuesID
	    				  AND popt.language_id     = :languageID
	                      AND poval.language_id    = :languageID " . $options_order_by;

    	//Bind variables to query
      	$attributes = $db->bindVars($attributes, ':productsID', $products[$i]['id'], 'integer');
      	$attributes = $db->bindVars($attributes, ':optionsID', $option, 'integer');
      	$attributes = $db->bindVars($attributes, ':optionsValuesID', $value, 'integer');
      	$attributes = $db->bindVars($attributes, ':languageID', $_SESSION['languages_id'], 'integer');
      	$attributes_values = $db->Execute($attributes);
      
      //clr 030714 determine if attribute is a text attribute and assign to $attr_value temporarily
      if ($value == PRODUCTS_OPTIONS_VALUES_TEXT_ID) {
        $attributeHiddenField .= zen_draw_hidden_field('id[' . $products[$i]['id'] . '][' . TEXT_PREFIX . $option . ']',  $products[$i]['attributes_values'][$option]);
        $attr_value = htmlspecialchars($products[$i]['attributes_values'][$option], ENT_COMPAT, CHARSET, TRUE);
      } else {
        $attributeHiddenField .= zen_draw_hidden_field('id[' . $products[$i]['id'] . '][' . $option . ']', $value);
        $attr_value = $attributes_values->fields['products_options_values_name'];
      }

      //Build array to be used in shopping cart
      $attrArray[$option]['products_options_name'] = $attributes_values->fields['products_options_name'];
      $attrArray[$option]['options_values_id'] = $value;
      $attrArray[$option]['products_options_values_name'] = $attr_value;
      $attrArray[$option]['options_values_price'] = $attributes_values->fields['options_values_price'];
      $attrArray[$option]['price_prefix'] = $attributes_values->fields['price_prefix'];

      //Test to exclude specified attributes from the checks that follow
      if( $attributes_values->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_SELECT_SBA ){  
      	// remove the elements who's values are equal to PRODUCTS_OPTIONS_TYPE_SELECT_SBA
      	$products[$i]['attributes'] = array_diff( $products[$i]['attributes'], array($value) );
      }
      
  	}//end foreach [attributes]
  	
	    //Clear variables for each loop
		$flagStockCheck = null;   
		$stockAvailable = null;
		$lowproductstock = false;
		$customid = null;
		unset($attributes);
		$productsQty = 0;

		// Added to allow individual stock of different attributes
		if( is_array($products[$i]['attributes']) ){
			$attributes = $products[$i]['attributes'];
		}
		
		if ( STOCK_CHECK == 'true' ) {

			if($attributes){
				$flagStockCheck = zen_check_stock($products[$i]['id'], $products[$i]['quantity'],$attributes);
				
				//check for product used multiple time in cart with different attributes
				//test for total qty availability for each combination
				if( cartProductCount($products[$i]['id']) > 1 ){
					//Build array for use below
					$duplicatesCOMPARE[$i] = array( 1 => $products[$i]['attributes'], 2 =>$products[$i]['id'], 3 => $products[$i]['quantity'] );

					//used to find unique entries, keep for reference and tests
// 					foreach($products[$i] as $row){
// 						foreach($row as $val){
// 							//if the value exists and it isn't already in the dupcliates array
// 							//Only add unique values to the array
// 							if(in_array($val, $exists) && !in_array($val, $duplicates)){
// 								$duplicates[] = $val;
// 								$duplicatesTMP[] = array( 1 => $val, 2 =>$products[$i]['id'], 3 => $products[$i]['quantity'] );
// 							}
// 							else{
// 								//cummulatively build the array to test against with each product attribut.
// 								$exists[] = $val;
// 								$existsTMP[] = array( 1 => $val, 2 =>$products[$i]['id'], 3 => $products[$i]['quantity'] );
// 							}
// 						}
// 					}
					
					//The following is an attempt to account for duplicate entries of attributes on different products
					//These attributes are expected to be limited in qty
					//Skips products that have specific attributes combination qty
					//This should only affect single attribute entries per product
					foreach($duplicatesCOMPARE as $dupCOMPARE){
						foreach($dupCOMPARE[1] as $dupC){
							foreach($products[$i]['attributes'] as $pAttr){
// 								echo 'dupC: ' . $dupC . ' ' . $dupCOMPARE[3] . '<br />';
// 								echo 'pAttr: ' . $pAttr . ' ' . $products[$i]['quantity'] . '<br />';
// 								echo 'Prod ID: ' . $products[$i]['id'] . ' ' . $dupCOMPARE[2] . '<br /><br />';

								if( ($pAttr === $dupC) && ($products[$i]['id'] != $dupCOMPARE[2]) ){
// 									echo 'Product: ' . $products[$i]['id'] . '<br />';
// 									echo 'Requested: ' . $products[$i]['quantity'] . '<br />';
// 									echo zen_get_products_stock($products[$i]['id'], $attributes, 'true') . ' TEST<br />';
									if( zen_get_products_stock($products[$i]['id'], $attributes, 'true') != 'true' ){
										$productsQty = ($productsQty + $dupCOMPARE[3] + $products[$i]['quantity']);
// 										echo 'Qty Requested: ' . $productsQty . '<br />';
// 										echo 'Available qty: ' . zen_get_products_stock($products[$i]['id'], $attributes) . '<br />';
// 										echo zen_get_products_stock($products[$i]['id'], $attributes, 'true');			
										$flagStockCheck = zen_check_stock($products[$i]['id'], $productsQty, $attributes);
// 										echo 'Flag: ' . $flagStockCheck . '<br /><br /><br />';
										break 3;//this will break three time, to move out of the three loops
									}
								}
							}
						}
					}
				}
			}
						
			if (zen_not_null($flagStockCheck)){
				$flagStockCheck = '<span class="markProductOutOfStock">' . $flagStockCheck . '</span>';
				$flagAnyOutOfStock = true;
			  	$stockAvailable = zen_get_products_stock($products[$i]['id'], $attributes);	
			  	if($stockAvailable > 0 && ($products[$i]['quantity'] > $stockAvailable)){
			  		$lowproductstock = true;
			  	}	
			}
			if(empty($stockAvailable)){
				$stockAvailable = 0;
			}
			
		}
				
			//Set Custom ID variable.
			if( STOCK_SBA_DISPLAY_CUSTOMID == 'true'){
				$customid = zen_get_customid($products[$i]['id'], $attributes);
			}
			
  } //end IF attributes push to array
  else{
  	//Section for products without attributes
  	//Clear variables for each loop
  	$flagStockCheck = null;
  	$stockAvailable = null;
  	$lowproductstock = false;
  	$customid = null;

  	$stockAvailable = zen_get_products_stock($products[$i]['id']);//get available stock
  	//run stock check on products without atribbutes when STOCK_CHECK flag is set
  	if ( STOCK_CHECK == 'true' ) {
  		
  		$flagStockCheck = zen_check_stock($products[$i]['id'], $products[$i]['quantity']);
  		
  		if (zen_not_null($flagStockCheck)) {
  			$flagStockCheck = '<span class="markProductOutOfStock">' . $flagStockCheck . '</span>';
  			$flagAnyOutOfStock = true;
  			if($stockAvailable > 0 && ($products[$i]['quantity'] > $stockAvailable)){
  				$lowproductstock = true;
  			}
  		}
  		if(empty($stockAvailable)){
  			$stockAvailable = 0;
  		}
  	}
  	
  	//Set Custom ID variable.
  	if( STOCK_SBA_DISPLAY_CUSTOMID == 'true'){
  		$customid = zen_get_customid($products[$i]['id']);
  	}
  	
  }
  // END "Stock by Attributes"

  $linkProductsImage = zen_href_link(zen_get_info_page($products[$i]['id']), 'products_id=' . $products[$i]['id']);
  $linkProductsName = zen_href_link(zen_get_info_page($products[$i]['id']), 'products_id=' . $products[$i]['id']);
  $productsImage = (IMAGE_SHOPPING_CART_STATUS == 1 ? zen_image(DIR_WS_IMAGES . $products[$i]['image'], $products[$i]['name'], IMAGE_SHOPPING_CART_WIDTH, IMAGE_SHOPPING_CART_HEIGHT) : '');
  $show_products_quantity_max = zen_get_products_quantity_order_max($products[$i]['id']);
  $showFixedQuantity = (($show_products_quantity_max == 1 or zen_get_products_qty_box_status($products[$i]['id']) == 0) ? true : false);

//  $showFixedQuantityAmount = $products[$i]['quantity'] . zen_draw_hidden_field('products_id[]', $products[$i]['id']) . zen_draw_hidden_field('cart_quantity[]', 1);
//  $showFixedQuantityAmount = $products[$i]['quantity'] . zen_draw_hidden_field('cart_quantity[]', 1);
  $showFixedQuantityAmount = $products[$i]['quantity'] . zen_draw_hidden_field('cart_quantity[]', $products[$i]['quantity']);
  
  $showMinUnits = zen_get_products_quantity_min_units_display($products[$i]['id']);
  $quantityField = zen_draw_input_field('cart_quantity[]', $products[$i]['quantity'], 'size="4"');
  $ppe = $products[$i]['final_price'];
  $ppe = zen_round(zen_add_tax($ppe, zen_get_tax_rate($products[$i]['tax_class_id'])), $currencies->get_decimal_places($_SESSION['currency']));
  $ppt = $ppe * $products[$i]['quantity'];
  $productsPriceEach = $currencies->format($ppe) . ($products[$i]['onetime_charges'] != 0 ? '<br />' . $currencies->display_price($products[$i]['onetime_charges'], zen_get_tax_rate($products[$i]['tax_class_id']), 1) : '');
  $productsPriceTotal = $currencies->format($ppt) . ($products[$i]['onetime_charges'] != 0 ? '<br />' . $currencies->display_price($products[$i]['onetime_charges'], zen_get_tax_rate($products[$i]['tax_class_id']), 1) : '');
  $buttonUpdate = ((SHOW_SHOPPING_CART_UPDATE == 1 or SHOW_SHOPPING_CART_UPDATE == 3) ? zen_image_submit(ICON_IMAGE_UPDATE, ICON_UPDATE_ALT) : '') . zen_draw_hidden_field('products_id[]', $products[$i]['id']);

//  $productsPriceEach = $currencies->display_price($products[$i]['final_price'], zen_get_tax_rate($products[$i]['tax_class_id']), 1) . ($products[$i]['onetime_charges'] != 0 ? '<br />' . $currencies->display_price($products[$i]['onetime_charges'], zen_get_tax_rate($products[$i]['tax_class_id']), 1) : '');
//  $productsPriceTotal = $currencies->display_price($products[$i]['final_price'], zen_get_tax_rate($products[$i]['tax_class_id']), $products[$i]['quantity']) . ($products[$i]['onetime_charges'] != 0 ? '<br />' . $currencies->display_price($products[$i]['onetime_charges'], zen_get_tax_rate($products[$i]['tax_class_id']), 1) : '');
//  $productsPriceTotal = $currencies->display_price($products[$i]['final_price'], zen_get_tax_rate($products[$i]['tax_class_id']), $products[$i]['quantity']) . ($products[$i]['onetime_charges'] != 0 ? '<br />' . $currencies->display_price($products[$i]['onetime_charges'], zen_get_tax_rate($products[$i]['tax_class_id']), 1) : '');
//  echo  $currencies->rateAdjusted($tmp);

  //This array is used in the tpl_shopping_cart_default.php
  $productArray[$i] = array('attributeHiddenField'=>$attributeHiddenField,
                            'flagStockCheck'=>$flagStockCheck,
                            'flagShowFixedQuantity'=>$showFixedQuantity,
                            'linkProductsImage'=>$linkProductsImage,
                            'linkProductsName'=>$linkProductsName,
                            'stockAvailable'=>$stockAvailable,
							'lowproductstock'=>$lowproductstock,
							'customid'=>$customid,
                            'productsImage'=>$productsImage,
                            'productsName'=>$productsName,
                            'showFixedQuantity'=>$showFixedQuantity,
                            'showFixedQuantityAmount'=>$showFixedQuantityAmount,
                            'showMinUnits'=>$showMinUnits,
                            'quantityField'=>$quantityField,
                            'buttonUpdate'=>$buttonUpdate,
                            'productsPrice'=>$productsPriceTotal,
                            'productsPriceEach'=>$productsPriceEach,
                            'rowClass'=>$rowClass,
                            'buttonDelete'=>$buttonDelete,
                            'checkBoxDelete'=>$checkBoxDelete,
                            'id'=>$products[$i]['id'],
                            'attributes'=>$attrArray);
} // end FOR loop

// This should be last line of the script:
$zco_notifier->notify('NOTIFY_HEADER_END_SHOPPING_CART');