<?php
/**
 * shopping_cart header_php.php
 *
 * @package page
 * @copyright Copyright 2003-2016 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Author: DrByte  Fri Dec 4 16:31:15 2015 -0500 Modified in v1.5.5 $
 * 
 * Stock by Attributes 1.5.4
 */

// This should be first line of the script:
$zco_notifier->notify('NOTIFY_HEADER_START_SHOPPING_CART');

require(DIR_WS_MODULES . zen_get_module_directory('require_languages.php'));
$breadcrumb->add(NAVBAR_TITLE);
if (isset($_GET['jscript']) && $_GET['jscript'] == 'no') {
  $messageStack->add('shopping_cart', PAYMENT_JAVASCRIPT_DISABLED, 'error');
}
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
  $flagStockCheck = '';
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
  $inSBA = false;
  if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
    if (PRODUCTS_OPTIONS_SORT_ORDER=='0') {
      //LPAD - Return the string argument, left-padded with the specified string
      //example: LPAD(popt.products_options_sort_order,11,"0") the field is 11 digits, and is left padded with 0
      $options_order_by= ' ORDER BY LPAD(popt.products_options_sort_order,11,"0")';
    } else {
      $options_order_by= ' ORDER BY popt.products_options_name';
    }

    // START "Stock by Attributes"
    // Added to allow individual stock of different attributes
    /*$inSBA_query = 'SELECT * 
                    FROM information_schema.tables
                    WHERE table_schema = :your_db: 
                    AND table_name = :table_name:
                    LIMIT 1;';
    $inSBA_query = $db->bindVars($inSBA_query, ':your_db:', DB_DATABASE, 'string');
    $inSBA_query = $db->bindVars($inSBA_query, ':table_name:', TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'string');
    $inSBA_result = $db->Execute($inSBA_query, false, false, 0, true);
    if (sizeof($inSBA_result) > 0 and !$inSBA_result->EOF) {
      $inSBA_query = "select stock_id from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " where products_id = :productsid:";
      $inSBA_query = $db->bindVars($inSBA_query, ':productsid:', $products[$i]['id'], 'integer');
      $inSBA_result = $db->Execute($inSBA_query);
    }

    $inSBA = (sizeof($inSBA_result) > 0 && !$inSBA_result->EOF);*/
   /* $inSBA = isset($_SESSION['pwas_class2'])
    && method_exists($_SESSION['pwas_class2'], 'zen_product_is_sba')
    && is_callable(array($_SESSION['pwas_class2'], 'zen_product_is_sba'))
        ? $_SESSION['pwas_class2']->zen_product_is_sba(zen_get_prid($products[$i]['id']))
        : function_exists('zen_product_is_sba') && zen_product_is_sba($products[$i]['id']);
    $products_options_type = null;*/
    // End of "Stock by Attributes"
    foreach ($products[$i]['attributes'] as $option => $value) {

      $attributes = "SELECT popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix
                      , popt.products_options_type 
                     FROM " . TABLE_PRODUCTS_OPTIONS . " popt 
                     LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa ON (pa.options_id = popt.products_options_id)
                     LEFT JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval ON (pa.options_values_id = poval.products_options_values_id)
                     WHERE pa.products_id = :productsID
                     AND pa.options_id = :optionsID
                     AND pa.options_values_id = :optionsValuesID
                     AND popt.language_id = :languageID
                     AND poval.language_id = :languageID " . $options_order_by;

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
    } //end foreach [attributes]
  } // end if attributes exist
    
      //Clear variables for each loop
//  $flagStockCheck = null;   
/*  $stockAvailable = null;
  $lowproductstock = false;
  $customid = null;
  //unset($attributes); //Unnecessary because reeassigned below.
  $productsQty = 0;
  // Stock Check
  if ($inSBA) {
    $attributes = $products[$i]['attributes']; 
  }  else {
    $attributes = null; //Force normal operation if the product is not monitored by SBA.
  }*/
  if (STOCK_CHECK == 'true') {
//    $flagStockCheck = zen_check_stock($products[$i]['id'], $products[$i]['quantity'], $attributes);
    $qtyAvailable = zen_get_products_stock($products[$i]['id']/*, $attributes*/);
    // Problem here is that $qtyAvailable defined above, is how many of a selection exists... If SBA, then $qtyAvailable are the quantity of attributes.
    //  if not SBA then total quantity of product regardless of attributes. Below, $qtyAvailable (single variable) is used to identify whether the total amount
    //  of the current attribute selection exceeds those available or it is used to indicate that of the total quantity of product whether the mixture exceeds
    //  the allowed quantity. :/  Lose/lose situation.  So somewhat need an additional variable or an additional check. :/
      // mc12345678 this section is as if SBA is not installed/involved.  Normal response after the expectation to check stock is included. After this section should go straight to the next default ZC action.
      // bof: extra check on stock for mixed YES
    if ($qtyAvailable - $products[$i]['quantity'] < 0 || $qtyAvailable - $_SESSION['cart']->in_cart_mixed($products[$i]['id']) < 0) {
      $flagStockCheck = '<span class="markProductOutOfStock">' . STOCK_MARK_PRODUCT_OUT_OF_STOCK . '</span>';
      $flagAnyOutOfStock = true;
    }

  } // End of STOCK_CHECK == 'true'

  //Set Custom ID variable. //Indepdendent of Stock_check.
/*  if(STOCK_SBA_DISPLAY_CUSTOMID == 'true'){
    $customid = (isset($_SESSION['pwas_class2'])
      && method_exists($_SESSION['pwas_class2'], 'zen_get_customid')
      && is_callable(array($_SESSION['pwas_class2'], 'zen_get_customid'))
          ? $_SESSION['pwas_class2']->zen_get_customid($products[$i]['id'], $attributes)
          : function_exists('zen_get_customid') && zen_get_customid($products[$i]['id'], $attributes));
  }*/

  $linkProductsImage = zen_href_link(zen_get_info_page($products[$i]['id']), 'products_id=' . $products[$i]['id']);
  $linkProductsName = zen_href_link(zen_get_info_page($products[$i]['id']), 'products_id=' . $products[$i]['id']);
  $productsImage = (IMAGE_SHOPPING_CART_STATUS == 1 ? zen_image(DIR_WS_IMAGES . $products[$i]['image'], $products[$i]['name'], IMAGE_SHOPPING_CART_WIDTH, IMAGE_SHOPPING_CART_HEIGHT) : '');
  $show_products_quantity_max = zen_get_products_quantity_order_max($products[$i]['id']);
  $showFixedQuantity = (($show_products_quantity_max == 1 or zen_get_products_qty_box_status($products[$i]['id']) == 0) ? true : false);
//  $showFixedQuantityAmount = $products[$i]['quantity'] . zen_draw_hidden_field('products_id[]', $products[$i]['id']) . zen_draw_hidden_field('cart_quantity[]', 1);
//  $showFixedQuantityAmount = $products[$i]['quantity'] . zen_draw_hidden_field('cart_quantity[]', 1);
  $showFixedQuantityAmount = $products[$i]['quantity'] . zen_draw_hidden_field('cart_quantity[]', $products[$i]['quantity']);
  $showMinUnits = zen_get_products_quantity_min_units_display($products[$i]['id']);
  $quantityField = zen_draw_input_field('cart_quantity[]', $products[$i]['quantity'], 'size="4" class="cart_input_'.$products[$i]['id'].'"');
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
                            /*'stockAvailable'=>$stockAvailable,
                            'lowproductstock'=>$lowproductstock,
                            'customid'=>$customid,*/
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
?>