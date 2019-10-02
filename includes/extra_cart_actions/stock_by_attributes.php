<?php
/*
 * Stock by Attributes 1.5.4 2016-01-02 mc12345678
 */

$renumber = true;

if (!defined('STOCK_SBA_CHECKOUT_SBA_ONLY'))
{
  define('STOCK_SBA_CHECKOUT_SBA_ONLY', 'true');
}

//What about: 'multiple_products_add_product' (Needs to be addressed though don't see at the moment why since generally unable to select multiple products each with attributes, perhaps something to consider for later, but let's get serious here at the moment as there are more routine actions to be handled properly first.), 'update_product' (Needs to be addressed), or 'cart' (does a notify action, so may need to address?)actions?
if (isset($_GET['action']) && $_GET['action'] == 'update_product') {
  if ($_SESSION['cart']->display_debug_messages) $messageStack->add_session('header', 'FUNCTION ' . __FUNCTION__, 'caution');

  $productIsSBA = array();

  // Goal of this first set of code is to create a "medium" where SBA product with text like attributes can be compared.
  //   A couple of things though.  
  //   * Need to look at the Posted quantity as it compares to the cart
  //       quantity.  
  //     Any quantity that has been increased is under suspicion.  If two or more of the
  //   same product were increased and total was in excess, then probably should reset all of them.
  //   * As going through the product, if a product's attributes are "text like" then:
  //     1. If the products_id for that non-text like product does not exist add the products_id 
  //        to the list and add the quantity of that product to the overall for that products_id.
  //     2. This additional products_id could/should be a separate variable for quantities only.
  //     3. 
  //     example POST data available on update:
  /*
  post_info (array)  

    securityToken (string) => HIDDEN as should never post
    cart_quantity (array)  
        0 (string) => 6
        1 (string) => 5
        2 (string) => 4
    products_id (array)  
        0 (string) => 1073:d03135f8216dddbdd820de243693c9de
        1 (string) => 1073:b4919d80280b711fc9eb17d7e4a1d6dc
        2 (string) => 1073:e1caedf3208ffc17906cc2d9aa396cb4
    id (array)  
        1073:d03135f8216dddbdd820de243693c9de (array)  
            txt_10 (string) => test
            9 (string) => 78
        1073:b4919d80280b711fc9eb17d7e4a1d6dc (array)  
            txt_10 (string) => test2
            9 (string) => 78
        1073:e1caedf3208ffc17906cc2d9aa396cb4 (array)  
            9 (string) => 78

  */
  $productHasText = array();
  $sba_add_prods = array();
  $sba_add_prods_old = array();
  $sba_add_prods_attribs = array();
  $sba_add_prods_cart_quantity = array(); // Quantity of the product already in the cart at this time.
  $sba_add_prods_quantity = array(); // Quantity summary of product in the cart to identify total at each product.

  if (empty($_POST['products_id'])) {
     $_POST['products_id'] = array();
  }

  for ($i=0, $n=count($_POST['products_id']); $i<$n; $i++) {
    $productIsSBA[$i] = $_SESSION['pwas_class2']->zen_product_is_sba(zen_get_prid($_POST['products_id'][$i]), true);
    
    if ($productIsSBA[$i]) {

      $attributes2 = array();

      $attributes2 = $_SESSION['pwas_class2']->zen_sba_attribs_no_text($_POST['products_id'][$i], $_POST['id'][$_POST['products_id'][$i]], 'products', 'update');
      
//      $_SESSION['attribs2_' . $_POST['products_id'][$i]] = $attributes2;
//      $_SESSION['attribs3_' . $_POST['products_id'][$i]] = $_POST['id'][$_POST['products_id'][$i]];
      $product_id = zen_get_uprid((int)$_POST['products_id'][$i], $attributes2);
//      $_SESSION['prod_id2_' . $_POST['products_id'][$i]] = $product_id;

      
      if (!in_array($product_id, $sba_add_prods)) {
        $sba_add_prods[] = $product_id;
        $sba_add_prods_cart_quantity[] = $_SESSION['cart']->contents[$_POST['products_id'][$i]]['qty'];
        $sba_add_prods_attribs[$product_id] = $attributes2;
      } else {
        $pos = array_search($product_id, $sba_add_prods);
//        $pos = ($pos === false) ? 0 : $pos;
        $sba_add_prods_cart_quantity[$pos] = $sba_add_prods_cart_quantity[$pos] + $_SESSION['cart']->contents[$_POST['products_id'][$i]]['qty'];
      }

      // Capture only those prids that have been modified to support this functionality.
      //   Can be used later to test if the key exists, then to use/modify the above data.
      if ($_POST['products_id'][$i] != $product_id) {
//      if ($attributes2 != $_POST['id'][$_POST['products_id'][$i]]) {//$_POST['products_id'][$i] != $product_id) {
        $sba_add_prods_old[$_POST['products_id'][$i]] = $product_id;
      }
    
    }
  }

//  $_SESSION['productIsSba'] = $productIsSBA;
//  $_SESSION['productHasText'] = $productHasText;
/*  $_SESSION['sba_add_prods'] = $sba_add_prods;
  $_SESSION['sba_add_prods_old'] = $sba_add_prods_old;
  $_SESSION['sba_add_prods_attribs'] = $sba_add_prods_attribs;
  $_SESSION['sba_add_prods_cart_quantity'] = $sba_add_prods_cart_quantity; // Quantity of the product already in the cart at this time.*/
  
  

  for ($i=0, $n=count($_POST['products_id']); $i<$n; $i++) {
/*    if (!zen_product_is_sba($_POST['products_id'][$i])) {
      // If the product is not SBA tracked then allow the cart's actions to
      // handle the remaining items and not be bothered by this code. :)
      //  Although, it may still be best to not skip this entire thing
      //  because of use of other Dynamic Dropdown options. So this is 
      //  now commented out until further testing. mc12345678 1/1/2016
      continue;
    }*/ 
    $adjust_max= 'false';
    if ($_POST['cart_quantity'][$i] == '') {
      $_POST['cart_quantity'][$i] = 0;
    }
    if (!is_numeric($_POST['cart_quantity'][$i]) || $_POST['cart_quantity'][$i] < 0) {
      // adjust quantity when not a value
      $chk_link = '<a href="' . zen_href_link(zen_get_info_page($_POST['products_id'][$i]), 'cPath=' . (zen_get_generated_category_path_rev(zen_get_products_category_id($_POST['products_id'][$i]))) . '&products_id=' . $_POST['products_id'][$i]) . '">' . zen_get_products_name($_POST['products_id'][$i]) . '</a>';
      $messageStack->add_session('header', ERROR_CORRECTIONS_HEADING . ERROR_PRODUCT_QUANTITY_UNITS_SHOPPING_CART . $chk_link . ' ' . PRODUCTS_ORDER_QTY_TEXT . zen_output_string_protected($_POST['cart_quantity'][$i]), 'caution');
      $_POST['cart_quantity'][$i] = 0;
      continue;
    }
    if ( in_array($_POST['products_id'][$i], (isset($_POST['cart_delete']) && is_array($_POST['cart_delete']) ? $_POST['cart_delete'] : array())) or $_POST['cart_quantity'][$i]==0) {
      $_SESSION['cart']->remove($_POST['products_id'][$i]);
    } else {
      if ((PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
        /* Breakdown the attributes into individual attributes to then be able to 
         * feed them into the applicable section(s).
         * 
         */
      }
      $add_max = zen_get_products_quantity_order_max($_POST['products_id'][$i]); // maximum allowed

      if ((PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
        /* Breakdown the attributes into individual attributes to then be able to 
         * feed them into the applicable section(s).
         * 
         */
      }
//      $_SESSION['verify_attributes'] = $_POST['id'][$_POST['products_id'][$i]];
      $attributes = (isset($_POST['id'][$_POST['products_id'][$i]]) && is_array($_POST['id'][$_POST['products_id'][$i]])) ? $_POST['id'][$_POST['products_id'][$i]] : '';
//      $productIsSBA[$i] = zen_product_is_sba(zen_get_prid($_POST['products_id'][$i]));
      if (!$productIsSBA[$i]) {
        if ((PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
        /* Breakdown the attributes into individual attributes to then be able to 
         * feed them into the applicable section(s).
         * 
         */
        }
        if ($productIsSBA[$i] && array_key_exists($_POST['products_id'][$i], $sba_add_prods_old)) {
//          $_SESSION['cart_qty_1'] = 
          $pos = array_search($sba_add_prods_old[$_POST['products_id'][$i]], $sba_add_prods);
          $cart_qty = $sba_add_prods_cart_quantity[$pos];
//          $_SESSION['cart_qty_1_' . $_POST['products_id'][$i]] = $cart_qty;
        } else {
          $cart_qty = $_SESSION['cart']->in_cart_mixed($_POST['products_id'][$i]); // total currently in cart
        }
      } else {
        if ((PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
          /* Breakdown the attributes into individual attributes to then be able to 
           * feed them into the applicable section(s).
           * 
           */
        }
        if ($productIsSBA[$i] && array_key_exists($_POST['products_id'][$i], $sba_add_prods_old)) {
          $pos = array_search($sba_add_prods_old[$_POST['products_id'][$i]], $sba_add_prods);
          $cart_qty = $sba_add_prods_cart_quantity[$pos];
//          $_SESSION['cart_qty_2_' . $_POST['products_id'][$i]] = $cart_qty;
        } else {
          $cart_qty = $_SESSION['cart']->in_cart_mixed($_POST['products_id'][$i]);
//          $_SESSION['cart_qty_incart_2_' . $_POST['products_id'][$i]] = $cart_qty;
//          unset($_SESSION['cart_qty_incart_2_' . $_POST['products_id'][$i]]);// = $sba_add_prods_old;
        }
      }
      if ($_SESSION['cart']->display_debug_messages) $messageStack->add_session('header', 'FUNCTION ' . __FUNCTION__ . ' Products_id: ' . $_POST['products_id'][$i] . ' cart_qty: ' . $cart_qty . ' <br>', 'caution');
      $new_qty = $_POST['cart_quantity'][$i]; // new quantity
      if ($productIsSBA[$i] && array_key_exists($_POST['products_id'][$i], $sba_add_prods_old)) {
        $pos = array_search($sba_add_prods_old[$_POST['products_id'][$i]], $sba_add_prods);
        $sba_add_prods_quantity[$pos] = $sba_add_prods_quantity[$pos] + $_POST['cart_quantity'][$i];
//        $new_qty = $sba_add_prods_quantity[$pos];
//        $_SESSION['cart_qty_4.1_' . $_POST['products_id'][$i]] = $sba_add_prods_quantity;
//        $_SESSION['cart_qty_4_' . $_POST['products_id'][$i]] = $new_qty;
      }
      
      if ($productIsSBA[$i] && array_key_exists($_POST['products_id'][$i], $sba_add_prods_old)) {
        $pos = array_search($sba_add_prods_old[$_POST['products_id'][$i]], $sba_add_prods);
//        $sba_add_prods_quantity[$pos] = $sba_add_prods_quantity[$pos] + $_POST['cart_quantity'][$i];
        $current_qty = $sba_add_prods_quantity[$pos];
//        $_SESSION['cart_qty_3_' . $_POST['products_id'][$i]] = $current_qty;
//        unset($_SESSION['cart_qty_3_' . $_POST['products_id'][$i]]); // = $cart_qty;
      } else {
        $current_qty = $_SESSION['cart']->get_quantity($_POST['products_id'][$i]); // how many currently in cart for attribute
      }
      if ($productIsSBA[$i] && (PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
        /* Breakdown the attributes into individual attributes to then be able to 
         * feed them into the applicable section(s).
         * 
         */
      }
// status of if product is tracked as mixed product or not
      $chk_mixed = zen_get_products_quantity_mixed($_POST['products_id'][$i]); // use mixed

        if ($productIsSBA[$i] && (PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
          /* Breakdown the attributes into individual attributes to then be able to 
           * feed them into the applicable section(s).
           * 
           */
        }
      $new_qty = $_SESSION['cart']->adjust_quantity($new_qty, $_POST['products_id'][$i], 'shopping_cart');
//          $_SESSION['new_qty_i_'.$i] = $new_qty;
// bof: adjust new quantity to be same as current in stock
// Mine          $chk_current_qty = zen_get_products_stock($_POST['products_id'][$i]);
//          if (!$productIsSBA[$i]) {
//            $chk_current_qty = zen_get_products_stock($_POST['products_id'][$i]);
//          } else {
        if ($productIsSBA[$i] && (PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
          /* Breakdown the attributes into individual attributes to then be able to 
           * feed them into the applicable section(s).
           * 
           */
        }
      $chk_current_qty_individual = zen_get_products_stock($_POST['products_id'][$i], $attributes);
      $chk_current_qty = zen_get_products_stock($_POST['products_id'][$i], ($chk_mixed) ? NULL : $attributes);
//          }
//          $_SESSION['qty_chk_current_qty_i_'.$i] = $chk_current_qty;
/*          if ($i >= 1) {
            $_SESSION['sba_add_prods_old_'.$i] = $sba_add_prods_old[$_POST['products_id'][$i]];
          }*/
      if ($productIsSBA[$i] && array_key_exists($_POST['products_id'][$i], $sba_add_prods_old)) {
        $pos = array_search($sba_add_prods_old[$_POST['products_id'][$i]], $sba_add_prods);
//        $sba_add_prods_quantity[$pos] = $sba_add_prods_quantity[$pos] + $_POST['cart_quantity'][$i];
        $temp_new_qty = $new_qty;
        $new_qty = $sba_add_prods_quantity[$pos];
        for ($j=0, $m=$i; $j < $m; $j++) {
          if (array_key_exists($_POST['products_id'][$j], $sba_add_prods_old)) {
            if ($sba_add_prods_old[$_POST['products_id'][$i]] == $sba_add_prods_old[$_POST['products_id'][$j]]) {
              $chk_current_qty = $chk_current_qty - $_POST['cart_quantity'][$j];
              $chk_current_qty_individual = $chk_current_qty_individual - $_POST['cart_quantity'][$j];
              $new_qty = $new_qty - $_POST['cart_quantity'][$j];
            }
          }
        }
//        $chk_current_qty = $sba_add_prods_quantity[$pos] - $_POST['cart_quantity'][$i]
      }

      $chk_current_qty_total = false;

      if (defined('STOCK_ALL_CHECKOUT') && STOCK_ALL_CHECKOUT == 'false' && $chk_mixed == false) { // Would say that it would be on this line to add an admin switch for controlling total product quantity versus variant.
        $chk_current_qty_total = zen_get_products_stock($_POST['products_id'][$i]);
      }
      // Check to see if the quantity rules of the individual stock quantities will take the product out-of-stock
        if (STOCK_ALLOW_CHECKOUT == 'false' && ($new_qty > $chk_current_qty || $new_qty > $chk_current_qty_individual || ($chk_current_qty_total !== false && $new_qty > $chk_current_qty_total))) {
          
          // if the individual product quantity is limiting then use the individual product (attribute) quantity.
          if ($chk_mixed == true && $new_qty > $chk_current_qty_individual && $chk_current_qty_individual < $chk_current_qty) {
            $new_qty = $chk_current_qty_individual;
          } elseif ($chk_mixed == true && $new_qty > $chk_current_qty_individual) { // Continuation of above, but recognizing that the mixed qty
            $new_qty = $chk_current_qty;
          } elseif ($chk_mixed == true && $new_qty > $chk_current_qty) {
            $new_qty = $chk_current_qty;
          } elseif ($chk_mixed == false && $new_qty > $chk_current_qty_individual && ($chk_current_qty_total !== false ? $chk_current_qty_individual < $chk_current_qty_total : true)) {
            $new_qty = $chk_current_qty_individual; // This value will represent the maximum allowed by this product.
                                         // This does not take into account if the total quantity of product is
                                         // less than the quantity available for a variant. :/ This may require
                                         // an additional admin switch to address.
          } elseif ($chk_mixed == false && $new_qty > $chk_current_qty_individual) {
            $new_qty = $chk_current_qty_total;
          } elseif ($chk_mixed == false && $new_qty > ($chk_current_qty_total !== false ? $chk_current_qty_total : $new_qty)) {
            $new_qty = $chk_current_qty;
          }
          //$new_qty = (min($chk_current_qty, $chk_current_qty_mixed) < 0) ? max($chk_current_qty, $chk_current_qty_mixed) : min($chk_current_qty, $chk_current_qty_mixed);
            $messageStack->add_session('shopping_cart', ($_SESSION['cart']->display_debug_messages ? 'FUNCTION ' . __FUNCTION__ . ': ' : '') . WARNING_PRODUCT_QUANTITY_ADJUSTED . zen_get_products_name($_POST['products_id'][$i]), 'caution');
        }
      if ($productIsSBA[$i] && array_key_exists($_POST['products_id'][$i], $sba_add_prods_old)) {
        $pos = array_search($sba_add_prods_old[$_POST['products_id'][$i]], $sba_add_prods);
//        $sba_add_prods_quantity[$pos] = $sba_add_prods_quantity[$pos] + $_POST['cart_quantity'][$i];
        if (STOCK_ALLOW_CHECKOUT == 'false' && ($pos === false || ($sba_add_prods_quantity[$pos] <= $chk_current_qty))) {
          $new_qty = $temp_new_qty;
        }
//        $_SESSION['cart_qty_10_' . $_POST['products_id'][$i]] = $new_qty;
      }
//      $_SESSION['add_max_'.$i] = $add_max;
//      $_SESSION['adjust_max_'.$i] = $adjust_max;

        if ($productIsSBA[$i] && (PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
          /* Breakdown the attributes into individual attributes to then be able to 
           * feed them into the applicable section(s).
           * 
           */
        }
      $attributes = (isset($_POST['id'][$_POST['products_id'][$i]]) && is_array($_POST['id'][$_POST['products_id'][$i]])) ? $_POST['id'][$_POST['products_id'][$i]] : '';

// eof: adjust new quantity to be same as current in stock
      if (($add_max == 1 and $cart_qty == 1) && $new_qty != $cart_qty) {
        // do not add
        $adjust_max= 'true';
      } else {
      if ($add_max != 0) {
// bof: adjust new quantity to be same as current in stock
//        $_SESSION['new_qty_b4_start_' . $i] = $new_qty;
//        $_SESSION['chk_current_qty_' . $i] = $chk_current_qty;
          if (false && STOCK_ALLOW_CHECKOUT == 'false' && ($new_qty + $cart_qty > $chk_current_qty)) {
              $adjust_new_qty = 'true';
              $alter_qty = $chk_current_qty - $cart_qty;
              $new_qty = ($alter_qty > 0 ? $alter_qty : 0);
              $messageStack->add_session('shopping_cart', ($_SESSION['cart']->display_debug_messages ? 'FUNCTION ' . __FUNCTION__ . ': ' : '') . WARNING_PRODUCT_QUANTITY_ADJUSTED . zen_get_products_name($_POST['products_id'][$i]), 'caution');
          }
// eof: adjust new quantity to be same as current in stock
//        $_SESSION['alter_qty_start_' . $i] = $alter_qty;
//        $_SESSION['adjust_new_qty_start_' . $i] = $adjust_new_qty;
//        $_SESSION['adjust_max_start_' . $i] = $adjust_max;
//        $_SESSION['requested_qty_start_' . $i] = $requested_qty;
//        $_SESSION['new_qty_start_' . $i] = $new_qty;
//        $_SESSION['cart_qty_start_' . $i] = $cart_qty;
//        $_SESSION['current_qty_start_' . $i] = $current_qty;
//        $_SESSION['add_max_start_' . $i] = $add_max;
//        $_SESSION['chk_mixed_start_' . $i] = ($chk_mixed) ? 'true' : 'false';
        // adjust quantity if needed
      switch (true) {
        case ($new_qty == $current_qty): // no change
          $adjust_max= 'false';
          $new_qty = $current_qty;
          break;
        case ($new_qty > $add_max && $chk_mixed == false):
          $adjust_max= 'true';
          $requested_qty = $new_qty;
          $new_qty = $add_max ;
          break;
        case (($add_max - $cart_qty + $new_qty >= $add_max) && $new_qty > $add_max && $chk_mixed == true):
          $adjust_max= 'true';
          $requested_qty = $new_qty;
          $new_qty = $current_qty;
          break;
        case (($cart_qty + $new_qty - $current_qty > $add_max) && $chk_mixed == true):
          $adjust_max= 'true';
          $requested_qty = $new_qty;
          $new_qty = $current_qty;
          break;
        default:
          $adjust_max= 'false';
        }
        
//        $_SESSION['adjust_max_end_' . $i] = $adjust_max;
//        $_SESSION['requested_qty_end_' . $i] = $requested_qty;
//        $_SESSION['new_qty_end_' . $i] = $new_qty;
//        $_SESSION['cart_qty_end_' . $i] = $cart_qty;
//        $_SESSION['current_qty_end_' . $i] = $current_qty;
//        $_SESSION['add_max_end_' . $i] = $add_max;
//        $_SESSION['chk_mixed_end_' . $i] = ($chk_mixed) ? 'true' : 'false';
        
// bof: notify about adjustment to new quantity to be same as current in stock or maximum to add
        if ($adjust_max == 'true') {
          $messageStack->add_session('shopping_cart', ($_SESSION['cart']->display_debug_messages ? 'FUNCTION ' . __FUNCTION__ . ': ' : '') . WARNING_PRODUCT_QUANTITY_ADJUSTED . zen_get_products_name($_POST['products_id'][$i]), 'caution');
        }
// eof: notify about adjustment to new quantity to be same as current in stock or maximum to add
        
        if ($productIsSBA[$i] && (PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
          /* Breakdown the attributes into individual attributes to then be able to 
           * feed them into the applicable section(s).
           * 
           */
        }
        $attributes = (isset($_POST['id'][$_POST['products_id'][$i]]) && is_array($_POST['id'][$_POST['products_id'][$i]])) ? $_POST['id'][$_POST['products_id'][$i]] : '';
        $_SESSION['cart']->add_cart($_POST['products_id'][$i], $new_qty, $attributes, false);
      } else {
        // adjust minimum and units
        $attributes = (isset($_POST['id'][$_POST['products_id'][$i]]) && is_array($_POST['id'][$_POST['products_id'][$i]])) ? $_POST['id'][$_POST['products_id'][$i]] : '';
        $_SESSION['cart']->add_cart($_POST['products_id'][$i], $new_qty, $attributes, false);
      }
      }
      if ($adjust_max == 'true') {
        if ($_SESSION['cart']->display_debug_messages) $messageStack->add_session('header', 'FUNCTION ' . __FUNCTION__ . '<br>' . ERROR_MAXIMUM_QTY . zen_get_products_name($_POST['products_id'][$i]) . '<br>requested_qty: ' . $requested_qty . ' current_qty: ' . $current_qty , 'caution');
        $messageStack->add_session('shopping_cart', ERROR_MAXIMUM_QTY . zen_get_products_name($_POST['products_id'][$i]), 'caution');
      } else {
// display message if all is good and not on shopping_cart page
        if ((DISPLAY_CART == 'false' && $_GET['main_page'] != FILENAME_SHOPPING_CART) && $messageStack->size('shopping_cart') == 0) {
          $messageStack->add_session('header', ($_SESSION['cart']->display_debug_messages ? 'FUNCTION ' . __FUNCTION__ . ': ' : '') . SUCCESS_ADDED_TO_CART_PRODUCTS, 'success');
        } else {
          if ($_GET['main_page'] != FILENAME_SHOPPING_CART) {
            zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
          }
        }
      }
    }

  }
  zen_redirect(zen_href_link($goto, zen_get_all_get_params($parameters)));
}

if (isset($_GET['action']) && $_GET['action'] == 'add_product') {
  if ($_SESSION['cart']->display_debug_messages) $messageStack->add_session('header', 'A: FUNCTION ' . __FUNCTION__, 'caution');
  // Here can add product attributes grid check and prepare to iterate through the "products" that have been added.
  // if is a multi-product add, then capture/process the necessary data to be able to assign each $_POST['products_id'], $_POST['id'],
  // and $_POST['cart_quantity'].  $_POST['products_id'] is expected to be relatively the same for each product (ie. the product's number only).
  // the 'id' is expected to be all of the attributes associated with the product and 'cart_quantity' will end up being the total 
  // quantity of a product, ie. carts_quantity times the number entered in the individual field box.  This way the quantity box will
  // have a default value if not shown, but if shown then multiples of the selected number of "groups" will be added to the cart.
  //  In this section, want to also be sure to add/maintain the 'id's in the order that would be expected without this additional
  //  feature so that all future manipulations work out correctly.
  /* Test to see if is a grid related submission/product*/
  /* Do additional prestage work for grid related submission/product*/
  if (
  defined('STOCK_SBA_CHECKOUT_SBA_ONLY') && STOCK_SBA_CHECKOUT_SBA_ONLY == 'true' && !empty($_SESSION['pwas_class2'])
      ? $_SESSION['pwas_class2']->zen_product_is_sba(zen_get_prid($_POST['products_id']))
      : true)
  {
    $grid_prod_id = array();
    $grid_id = array();
    $prod_qty = array();
    $grid_add_number = 0;
  }

  if (isset($_POST['product_id']) 
       && is_array($_POST['product_id']) 
       && $_SESSION['pwas_class2']->zen_product_is_sba(zen_get_prid($_POST['products_id'])) 
      ) {

        // product is tracked by SBA and has grid layout.
        foreach($_POST['product_id'] as $prid => $qty) {
            $products_id = zen_get_prid($prid);
            //$file = $_FILES;
            //$file = $file;
            
            $option_ref = array();

            if (!is_numeric($qty) || $qty < 0) {
                // adjust quantity when not a value
        //$_SESSION['non_sub_qty_'.$prid] = $qty;
                $chk_link = '<a href="' . zen_href_link(zen_get_info_page($products_id), 'cPath=' . (zen_get_generated_category_path_rev(zen_get_products_category_id($products_id))) . '&products_id=' . $products_id) . '">' . zen_get_products_name($products_id) . '</a>';
                $messageStack->add_session('header', ERROR_CORRECTIONS_HEADING . ERROR_PRODUCT_QUANTITY_UNITS_SHOPPING_CART . $chk_link . ' ' . PRODUCTS_ORDER_QTY_TEXT . zen_output_string_protected($qty), 'caution');
                $qty = 0;
            }

            if (isset($_POST['id']) && is_array($_POST['id'])) { // This is to fix/setup attribs if needed.
                foreach($_POST['id'] as $option => $option_value) {
                    $_POST['attribs'][$prid][$option] = $option_value;
                }
            }

            if (isset($_GET['number_of_uploads']) && $_GET['number_of_uploads'] > 0) {
                for ($iFile = 1, $nFile = $_GET['number_of_uploads']; $iFile <= $nFile; $iFile++) {
                    if (zen_not_null($_FILES['id']['tmp_name'][TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $iFile]]) and ($_FILES['id']['tmp_name'][TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $iFile]] != 'none')) {
                        $_POST['attribs'][$prid][TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $iFile]] = $iFile . ". ";
                    } else { // No file uploaded -- use previous value
                        $_POST['attribs'][$prid][TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $iFile]] = $_POST[TEXT_PREFIX . UPLOAD_PREFIX . $iFile];
                    }
                }
            }

            foreach($_POST['attribs'][$prid] as $option_id => $value_id) {
                if (substr($option_id, 0, strlen(TEXT_PREFIX)) == TEXT_PREFIX) {
                    $option_ref[substr($option_id, strlen(TEXT_PREFIX))] = $option_id;
                    $option_id = substr($option_id, strlen(TEXT_PREFIX));
                } elseif (substr($option_id, 0, strlen(FILE_PREFIX)) == FILE_PREFIX) {
                    $option_ref[substr($option_id, strlen(FILE_PREFIX))] = $option_id;
                    $option_id = substr($option_id, strlen(FILE_PREFIX));
                } else {
                    $option_ref[$option_id] = $option_id;
                }
                $check_attrib = $db->Execute(  "select pov.products_options_values_name from " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov " .
                                                "where pa.options_values_id = pov.products_options_values_id " .
                                                "and pa.options_id = '".(int)$option_id . "' " .
                                                "and pa.products_id = '".(int)$products_id ."' " .
                                                "and pov.language_id = '".(int)$_SESSION['languages_id']."'");
                if ($check_attrib->RecordCount() <= 1 && $check_attrib->fields['products_options_values_name'] == '') {
                    unset($_POST['attribs'][$prid][$option_id]);  // Not sure why it matters if the value has a name or not. mc12345678
                }
            }
      
            if (!is_numeric($_POST['cart_quantity']) || $_POST['cart_quantity'] < 0) {
              // adjust quantity when not a value
              $chk_link = '<a href="' . zen_href_link(zen_get_info_page($products_id), 'cPath=' . (zen_get_generated_category_path_rev(zen_get_products_category_id($products_id))) . '&products_id=' . $products_id) . '">' . zen_get_products_name($products_id) . '</a>';
              $messageStack->add_session('header', ERROR_CORRECTIONS_HEADING . ERROR_PRODUCT_QUANTITY_UNITS_SHOPPING_CART . $chk_link . ' ' . PRODUCTS_ORDER_QTY_TEXT . zen_output_string_protected($_POST['cart_quantity']), 'caution');
              $_POST['cart_quantity'] = 0;
            }
            if (is_numeric($qty) && zen_not_null($qty) && $qty > 0) {
                reset($_POST['attribs'][$prid]);
        // End result on the side with grid is to set $_POST['id'] = $_POST['attribs'][$prid]
        // and then move to the next item.
                if (PRODUCTS_OPTIONS_SORT_ORDER == '0') {
                    $options_order_by= ' order by LPAD(popt.products_options_sort_order,11,"0"), popt.products_options_name';
                } else {
                    $options_order_by= ' order by popt.products_options_name';
                }

  //get the option/attribute list
                $sql = "select distinct popt.products_options_id, popt.products_options_name, popt.products_options_sort_order,
                              popt.products_options_type, popt.products_options_length, popt.products_options_comment,
                              popt.products_options_size,
                              popt.products_options_images_per_row,
                              popt.products_options_images_style,
                              popt.products_options_rows
              from        " . TABLE_PRODUCTS_OPTIONS . " popt
              left join " . TABLE_PRODUCTS_ATTRIBUTES . " patrib ON (patrib.options_id = popt.products_options_id)
              where patrib.products_id= :products_id:
              and popt.language_id = :languages_id: " .
              $options_order_by;

                $sql = $db->bindVars($sql, ':products_id:', $prid, 'integer');
                $sql = $db->bindVars($sql, ':languages_id:', $_SESSION['languages_id'], 'integer');
                $products_options_sequence = $db->Execute($sql);

                $grid_id2 = array();
                while (!$products_options_sequence->EOF) {
                    $grid_id2[$option_ref[$products_options_sequence->fields['products_options_id']]] = $_POST['attribs'][$prid][$option_ref[$products_options_sequence->fields['products_options_id']]];
                    $products_options_sequence->MoveNext();
                }

                $grid_id[] = $grid_id2;
                $prod_qty[] = $qty * $_POST['cart_quantity'];
                $grid_prod_id[] = $products_id;
                $grid_add_number++;
            }  // EOF if is numeric
//            $_SESSION['file_' . $prid] = $file;
            //$_FILES = $file;
        } // EOF foreach product_id

//        $_SESSION['in_loop_grid_id'] = $grid_id;
//        unset($_SESSION['in_loop_grid_id']);
        if (empty($grid_id) || empty($prod_qty) || empty($grid_prod_id)) {
          $grid_prod_id[0] = null;
          $prod_qty[0] = 0;
          $grid_add_number = 0;
        }
  }

  elseif (
  defined('STOCK_SBA_CHECKOUT_SBA_ONLY') && STOCK_SBA_CHECKOUT_SBA_ONLY == 'true' 
          ? $_SESSION['pwas_class2']->zen_product_is_sba(zen_get_prid($_POST['products_id'])) 
          : true) 
  {
    if (isset($_POST['product_id']) && is_array($_POST['product_id'])) {
    // Product has grid layout but is not tracked by SBA.
      $grid_prod_id[0] = null;
      $prod_qty[0] = 0;
      $grid_add_number = 0;
      $_POST['products_id'] = 0;
      $_POST['id'] = 0;
      $_POST['cart_quantity'] = 0;
    } else {
      // Product does not have grid, could be SBA, doesn't have to be.
      $grid_prod_id[] = $_POST['products_id'];
      $grid_id[] = isset($_POST['id']) ? $_POST['id'] : 0;
      $prod_qty[] = $_POST['cart_quantity'];
      $grid_add_number = 1;
    }
  }
  
  if (defined('STOCK_SBA_CHECKOUT_SBA_ONLY') && STOCK_SBA_CHECKOUT_SBA_ONLY == 'true' 
       ? $_SESSION['pwas_class2']->zen_product_is_sba(zen_get_prid($_POST['products_id']))
       : true) {

    if (count($grid_id) < 1) {
      // no grid item, so make the first data record be null.
      $grid_id[0] = null;
//      $grid_add_number = 1;
    }
    if (count($grid_id) == 1 && is_null($grid_id[0]) && (!isset($_GET['number_of_uploads']) || $_GET['number_of_uploads'] == '0')) {
      $grid_add_number = 0;
    }
//        $grid_add_number = 1;

    if (count($prod_qty) < 1) {
      $prod_qty[0] = 0;
      $grid_add_number = 0;
    }

    if (count($grid_prod_id) < 1) {
      $grid_prod_id[0] = null;
      $grid_add_number = 0;
    }
    $grid_loop = 0;
//    $_SESSION['before_grid_loop_grid_add_number'] = $grid_add_number;
//    $_SESSION['before_grid_loop_grid_prod_id'] = $grid_prod_id;
//    $_SESSION['before_grid_loop_grid_id'] = $grid_id;
//    $_SESSION['before_grid_loop_grid_cart_qty'] = $prod_qty;
//    $_SESSION['before_grid_loop'] = $_POST;
/*    unset($_SESSION['before_grid_loop_grid_add_number']);
    unset($_SESSION['before_grid_loop_grid_prod_id']);
    unset($_SESSION['before_grid_loop_grid_id']);
    unset($_SESSION['before_grid_loop_grid_cart_qty']);
    unset($_SESSION['before_grid_loop']);*/
/*    if (isset($_SESSION['file_located'])) {
      unset($_SESSION['file_located']);
    }*/
    if (!(isset($fileVar) && is_array($fileVar)) || isset($fileVar['file_located']) || array_key_exists('file_located', $fileVar)) {
      $fileVar = array();
    }
    
/*    if (isset($fileVar) && is_array($fileVar) && array_key_exists('file_located', $fileVar)) {
      unset($fileVar['file_located']);
      $fileVar = array();
    } elseif (!(isset($fileVar) && is_array($fileVar))) {
      $fileVar = array();
    }*/
//    $_SESSION['post2'] = $_POST;
    while ($grid_loop++ <= $grid_add_number) {
      $_POST['products_id'] = $grid_prod_id[$grid_loop - 1];
      $_POST['id'] = $grid_id[$grid_loop - 1];
      $_POST['cart_quantity'] = $prod_qty[$grid_loop - 1];
      if (isset($_POST['products_id'] ) && is_numeric ( $_POST['products_id'])) {
//Loop for each product in the cart
        if ($_SESSION['cart']->display_debug_messages) $messageStack->add_session('header', 'A2: FUNCTION ' . __FUNCTION__, 'caution');
        $the_list = '';
        $adjust_max= 'false';
        if (isset($_POST['id']) && is_array($_POST['id'])) {
          if ((PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
          /* Breakdown the attributes into individual attributes to then be able to 
           * feed them into the applicable section(s).
           * 
           */
          }
          foreach ($_POST['id'] as $key => $value) {
            $check = zen_get_attributes_valid($_POST['products_id'], $key, $value);
            if ($check == false) {
              $the_list .= TEXT_ERROR_OPTION_FOR . '<span class="alertBlack">' . zen_options_name($key) . '</span>' . TEXT_INVALID_SELECTION . '<span class="alertBlack">' . ($value == (int)PRODUCTS_OPTIONS_VALUES_TEXT_ID ? TEXT_INVALID_USER_INPUT : zen_values_name($value)) . '</span>' . '<br />';
            }
          }
        }
        if (!is_numeric($_POST['cart_quantity']) || $_POST['cart_quantity'] < 0) {
          // adjust quantity when not a value
          $chk_link = '<a href="' . zen_href_link(zen_get_info_page($_POST['products_id']), 'cPath=' . (zen_get_generated_category_path_rev(zen_get_products_category_id($_POST['products_id']))) . '&products_id=' . $_POST['products_id']) . '">' . zen_get_products_name($_POST['products_id']) . '</a>';
          $messageStack->add_session('header', ERROR_CORRECTIONS_HEADING . ERROR_PRODUCT_QUANTITY_UNITS_SHOPPING_CART . $chk_link . ' ' . PRODUCTS_ORDER_QTY_TEXT . zen_output_string_protected($_POST['cart_quantity']), 'caution');
          $_POST['cart_quantity'] = 0;
        }
        $attr_list = array();
        $attr_dash = array();
        $attr_id = array();
        $attr_val = array();
//    $_SESSION['before_dropdown'] = $_POST['id'];
//    unset($_SESSION['before_dropdown']);
        if ((PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2')) {
          /*single dropdown as multiple*/
          $attr_list = explode(',',$_POST['attrcomb']);
          foreach ($attr_list as $attr_item) {
            list($attr_id, $attr_val) = explode('-',$attr_item);
            if (zen_not_null($attr_id) && zen_not_null($attr_val)) {
              $_POST['id'][$attr_id] = $attr_val;
            }
          }
          /* Breakdown the attributes into individual attributes to then be able to 
           * feed them into the applicable section(s).
           * 
           */
        }
//    $_SESSION['before_file'] = $_POST['id'];
//    unset($_SESSION['before_file']);
        $attributes = (isset($_POST['id']) && zen_not_null($_POST['id'])  ? $_POST['id']  : null );
        // to address product with maleable attributes where the attribute 
        // is not stock dependent (text field) product_id needs to reflect 
        // the appropriate designation as built using the appropriate $attributes.
        //  This would take a refactoring of entered text as if it was absent. mc12345678 01-02-2016

        // Need to get the file related information into the $attributes related data.
        if (isset($_GET['number_of_uploads']) && $_GET['number_of_uploads'] > 0) {
          if (is_array($fileVar) && array_key_exists('file_located', $fileVar) && $fileVar['file_located']) {
            for ($iFile = 1, $nFile = $_GET['number_of_uploads']; $iFile <= $nFile; $iFile++) {
              //rename($_SESSION['file_location_' . $iFile] . '.baksba', $_SESSION['file_location_' . $iFile]);
            }
          }
          /**
           * Need the upload class for attribute type that allows user uploads.
           *
           */
          //include(DIR_WS_CLASSES . 'upload.php');
          for ($iFile = 1, $nFile = $_GET['number_of_uploads']; $iFile <= $nFile; $iFile++) {
            if (zen_not_null($_FILES['id']['tmp_name'][TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $iFile]]) and ($_FILES['id']['tmp_name'][TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $iFile]] != 'none')) {

             /* if ($grid_loop < $grid_add_number) {
                // copy the file to another location
                // Set a flag that will indicate that it should be copied back
                // Earlier need to copy the file back to this location if the flag is set.
                // copy();
                copy($_FILES['id']['tmp_name'][TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $iFile]], $_FILES['id']['tmp_name'][TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $iFile]] . '.baksba');

                $_SESSION['file_located'] = true;
                $_SESSION['file_location_' . $iFile] = $_FILES['id']['tmp_name'][TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $iFile]];
              } else {
                // Clear flag
                unset($_SESSION['file_located']);
//                unset($_SESSION['file_location_' . $iFile]);
              }
              /*$products_options_file = new upload('id');
              $products_options_file->set_destination(DIR_FS_UPLOADS);
              $products_options_file->set_output_messages('session');
              if ($products_options_file->parse(TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $iFile])) {
                //$products_image_extension = substr($products_options_file->filename, strrpos($products_options_file->filename, '.'));
                *//*if ($_SESSION['customer_id']) {
                  $db->Execute("insert into " . TABLE_FILES_UPLOADED . " (sesskey, customers_id, files_uploaded_name) values('" . zen_session_id() . "', '" . $_SESSION['customer_id'] . "', '" . zen_db_input($products_options_file->filename) . "')");
                } else {
                  $db->Execute("insert into " . TABLE_FILES_UPLOADED . " (sesskey, files_uploaded_name) values('" . zen_session_id() . "', '" . zen_db_input($products_options_file->filename) . "')");
                }
                $insert_id = $db->Insert_ID();*/
                //  The line below did have $insert_id in place of $iFile on the right side; however, with removal of the
                //   storage information, that number has been "lost".
                $attributes[TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $iFile]] = $iFile . ". " /*. $products_options_file->filename*/;
                //$products_options_file->set_filename("$insert_id" . $products_image_extension);
                /*if (!($products_options_file->save())) { 
                  break;
                }*/
              /*} else {
                break;
              }*/
            } else { // No file uploaded -- use previous value
              $attributes[TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $iFile]] = $_POST[TEXT_PREFIX . UPLOAD_PREFIX . $iFile];
            }
          }
        }
//$_SESSION['after_file'] = $attributes;
//unset($_SESSION['after_file']);

        //$attributes2 is to be a "text free" set of attributes.
        $product_id = zen_get_uprid($_POST['products_id'], $attributes);
        $attributes2 = array();

        if ($_SESSION['pwas_class2']->zen_product_is_sba($_POST['products_id'])
            ) {
              $attributes2 = $_SESSION['pwas_class2']->zen_sba_attribs_no_text($_POST['products_id'], isset($attributes) && is_array($attributes) ? $attributes : array(), 'products', 'add');
          $product_id = zen_get_uprid($_POST['products_id'], $attributes2);
        }

        $add_max = zen_get_products_quantity_order_max($_POST['products_id']);
        // to address product with maleable attributes where the attribute 
        // is not stock dependent cart_qty needs to reflect the appropriate number.  mc12345678 01-02-2016
        if ($_SESSION['pwas_class2']->zen_product_is_sba($_POST['products_id'])) {
          $backup = array();
          $backup = $_SESSION['cart']->contents;
          reset($backup);
      
          $addProdIDs = $_SESSION['pwas_class2']->zen_get_sba_attribute_info($product_id, $attributes2, 'products', 'ids');
          
          $attributes_values = array();
          $contents_key = array();
//$_SESSION['backup'] = $backup;
          foreach ($backup as $prod_id => $prod_attrib) {

            if ((int)$_POST['products_id'] != (int)$prod_id) {
              continue;
            }
        
            if (array_key_exists('attributes_values', $_SESSION['cart']->contents[$prod_id])) {
//              $attributes_values[] = array($prod_id=>$_SESSION['cart']->contents[$prod_id]['attributes_values']);
//              unset($_SESSION['cart']->contents[$prod_id]['attributes_values']);
            } else {
              $_SESSION['cart']->contents[$prod_id]['attributes'] = $_SESSION['pwas_class2']->zen_sba_attribs_no_text($_POST['products_id'], isset($_SESSION['cart']->contents[$prod_id]['attributes']) ? $_SESSION['cart']->contents[$prod_id]['attributes'] : array(), 'products', 'addNoText');
            }
/*        $_SESSION['in_backup_' . $prod_id] = array('attributes' => $_SESSION['cart']->contents[$prod_id]['attributes'],
                                                  'addProdIDs' => $addProdIDs,
                                                  'attrib_info' => $_SESSION['pwas_class2']->zen_get_sba_attribute_info($product_id, $_SESSION['cart']->contents[$prod_id]['attributes'], 'products', 'ids'));*/
                                                   
            if ($addProdIDs != ($_SESSION['pwas_class2']->zen_get_sba_attribute_info($product_id, $_SESSION['cart']->contents[$prod_id]['attributes'], 'products', 'ids'))) {
//                                     $_SESSION['in_backup_addProdIDs_' . $prod_id] = 'Skipped';
              continue;
            }
//            $_SESSION['cart']->contents[$prod_id]['attributes'] = zen_sba_attribs_no_text($_POST['products_id'], $_SESSION['cart']->contents[$prod_id]['attributes']);
            $product_id = zen_get_uprid((int)$prod_id, $_SESSION['cart']->contents[$prod_id]['attributes']);
//$_SESSION['in_backup_product_id_' . $prod_id] = $product_id;
            $add_val = 0;
            if (isset($_SESSION['cart']->contents[$product_id]) || array_key_exists($product_id, $_SESSION['cart']->contents)) {
              $add_val = $_SESSION['cart']->contents[$product_id]['qty'];
            }
            $_SESSION['cart']->contents[$product_id] = $_SESSION['cart']->contents[$prod_id];
            
            $add_to = 0;
            
            if ($_SESSION['pwas_class2']->zen_sba_has_text_field($addProdIDs)) {
              $add_to = $_SESSION['cart']->contents[$product_id]['qty'];
//              $_SESSION['cart']->contents[$product_id]['qty'] +=  $add_val;
//            } else {
//              $_SESSION['cart']->contents[$product_id]['qty'] = $add_val;
            }
            
            $_SESSION['cart']->contents[$product_id]['qty'] =  $add_val + $add_to;
            
            $contents_key[] = array($prod_id => $_SESSION['cart']->contents[$prod_id]);
          }
        }

//      $_SESSION['pre_cart'] = $_SESSION['cart'];
//      $_SESSION['pre_product'] = $product_id;
        $cart_qty = $_SESSION['cart']->get_quantity($product_id);
//        $cart_qty = $_SESSION['cart']->in_cart_mixed($product_id);
//      $_SESSION['add_cart_qty'] = $cart_qty;

        if ($_SESSION['pwas_class2']->zen_product_is_sba($_POST['products_id'])) {
          unset($_SESSION['cart']->contents[$product_id]);
          $_SESSION['cart']->contents = $backup;
          unset($backup);
          $product_id = zen_get_uprid($_POST['products_id'], $attributes);
/*          foreach ($contents_key as $num_pos=>$key) {
          $key2 = key($key);
          if (array_key_exists('qty', $contents_key[$num_pos][$key2])) {
            $_SESSION['cart']->contents[$key2]['qty'] = $contents_key[$num_pos][$key2]['qty'];
          } else {
            $_SESSION['cart']->contents[$key2]['qty'] = 0;
          }
          if (array_key_exists($key2, $attributes_values[$num_pos])) {
            $_SESSION['cart']->contents[$key2]['attributes_values'] = $attributes_values[$num_pos][$key2];
          }
          if (array_key_exists('attributes', $contents_key[$num_pos][$key2])) {
            $_SESSION['cart']->contents[$key2]['attributes'] = $contents_key[$num_pos][$key2]['attributes'];
          }
        }*/
        }
//      unset($_SESSION['cart_after2']); //  = $_SESSION['cart'];
//      $_SESSION['sba_cart_after2'] = $_SESSION['sba_cart'];
//      unset($_SESSION['sba_cart_after2']); //  = $backup;
        if ($_SESSION['cart']->display_debug_messages) $messageStack->add_session('header', 'B: FUNCTION ' . __FUNCTION__ . ' Products_id: ' . $_POST['products_id'] . ' cart_qty: ' . $cart_qty . ' $_POST[cart_quantity]: ' . $_POST['cart_quantity'] . ' <br>', 'caution');

//Check if item is an SBA tracked item, if so, then perform analysis of whether to add or not.
        if ($_SESSION['pwas_class2']->zen_product_is_sba($_POST['products_id'])) {
//Looks like $_SESSION['cart']->in_cart_mixed($prodId) could be used here to pull the attribute related product information to verify same product is being added to cart... This also may help in the shopping_cart routine added for SBA as all SBA products will have this modifier.
//      $cart_qty = 0;

          $new_qty = $_POST['cart_quantity']; //Number of items being added (Known to be SBA tracked already)
          $new_qty = $_SESSION['cart']->adjust_quantity($new_qty, $_POST['products_id'], 'shopping_cart');

// bof: adjust new quantity to be same as current in stock
          $chk_current_qty = zen_get_products_stock($product_id, $attributes);
/*          $_SESSION['add_chk_current_qty'] = array('chk_current_qty'=>$chk_current_qty,
                                                  'new_qty'=>$new_qty,
                                                  'attributes'=>$attributes,
                                                  'cart_qty'=>$cart_qty);*/
          $_SESSION['cart']->flag_duplicate_msgs_set = FALSE;
          
//          $productAttrAreSBA = zen_get_sba_stock_attribute_id($product_id, $attributes, 'products');
          $productAttrAreSBA = $_SESSION['pwas_class2']->zen_get_sba_attribute_info($product_id, isset($attributes) && is_array($attributes) ? $attributes : array(), 'products', 'ids');
                
          if ($productAttrAreSBA === false) {
            $the_list .= PWA_COMBO_OUT_OF_STOCK . "<br />";
            foreach ($_POST['id'] as $key2 => $value2) {
              $the_list .= TEXT_ERROR_OPTION_FOR . '<span class="alertBlack">' . zen_options_name($key2) . '</span>' . TEXT_INVALID_SELECTION . '<span class="alertBlack">' . ($value == (int)PRODUCTS_OPTIONS_VALUES_TEXT_ID ? TEXT_INVALID_USER_INPUT : zen_values_name($value2)) . '</span>' . '<br />';
            }
          }
      
          if (STOCK_ALLOW_CHECKOUT == 'false' && ($cart_qty + $new_qty > $chk_current_qty)) {
            $new_qty = $chk_current_qty;
            $messageStack->add_session('shopping_cart', ($_SESSION['cart']->display_debug_messages ? 'C: FUNCTION ' . __FUNCTION__ . ': ' : '') . WARNING_PRODUCT_QUANTITY_ADJUSTED . zen_get_products_name($_POST['products_id']), 'caution');
            $_SESSION['cart']->flag_duplicate_msgs_set = TRUE;

            if ($chk_current_qty <= 0) {
              $the_list .= PWA_COMBO_OUT_OF_STOCK . "<br />";
              foreach ($_POST['id'] as $key2 => $value2) {
                $the_list .= TEXT_ERROR_OPTION_FOR . '<span class="alertBlack">' . zen_options_name($key2) . '</span>' . TEXT_INVALID_SELECTION . '<span class="alertBlack">' . ($value == (int)PRODUCTS_OPTIONS_VALUES_TEXT_ID ? TEXT_INVALID_USER_INPUT : zen_values_name($value2)) . '</span>' . '<br />';
              }
            }
          }
          
  // eof: adjust new quantity to be same as current in stock
          if (($add_max == 1 and $cart_qty == 1)) {
            // do not add
            $new_qty = 0;
            $adjust_max= 'true';
          } else {
// bof: adjust new quantity to be same as current in stock
            if (STOCK_ALLOW_CHECKOUT == 'false' && ($new_qty + $cart_qty > $chk_current_qty)) {
              $adjust_new_qty = 'true';
              $alter_qty = $chk_current_qty - $cart_qty;
              $new_qty = ($alter_qty > 0 ? $alter_qty : 0);
              if (!$_SESSION['cart']->flag_duplicate_msgs_set) {
                $messageStack->add_session('shopping_cart', ($_SESSION['cart']->display_debug_messages ? 'D: FUNCTION ' . __FUNCTION__ . ': ' : '') . WARNING_PRODUCT_QUANTITY_ADJUSTED . zen_get_products_name($_POST['products_id']), 'caution');
              }
            }
// eof: adjust new quantity to be same as current in stock
        // adjust quantity if needed
            if (($new_qty + $cart_qty > $add_max) and $add_max != 0) {
              $adjust_max= 'true';
              $new_qty = $add_max - $cart_qty;
            }
          }
          
          if ((zen_get_products_quantity_order_max($_POST['products_id']) == 1 and $_SESSION['cart']->in_cart_mixed($_POST['products_id']) == 1)) {

            // do not add
          } else {
            // process normally
            // bof: set error message
            if ($the_list != '') {
              $messageStack->add('product_info', ERROR_CORRECTIONS_HEADING . $the_list, 'caution');
            } else {
              // process normally
              // iii 030813 added: File uploading: save uploaded files with unique file names
              $real_ids = isset($_POST['id']) ? $_POST['id'] : "";
              if (isset($_GET['number_of_uploads']) && $_GET['number_of_uploads'] > 0) {
                /**
                 * Need the upload class for attribute type that allows user uploads.
                 *
                 */
                include_once(DIR_WS_CLASSES . 'upload.php');
//                $_SESSION['files_'.$grid_loop] = $_FILES;
                for ($i = 1, $n = $_GET['number_of_uploads']; $i <= $n; $i++) {
                  if (zen_not_null($_FILES['id']['tmp_name'][TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $i]]) and ($_FILES['id']['tmp_name'][TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $i]] != 'none')) {
                    if ($grid_loop == 1) {
                      $products_options_file = new upload('id');
                      $products_options_file->set_destination(DIR_FS_UPLOADS);
                      $products_options_file->set_output_messages('session');
                    }
                    if ($grid_loop > 1 || $products_options_file->parse(TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $i])) {
                      $products_image_extension = substr($products_options_file->filename, strrpos($products_options_file->filename, '.'));
                      if ($grid_loop == 1 || $renumber) {
                        if (!empty($_SESSION['customer_id'])) {
                          $db->Execute("insert into " . TABLE_FILES_UPLOADED . " (sesskey, customers_id, files_uploaded_name) values('" . zen_session_id() . "', '" . $_SESSION['customer_id'] . "', '" . zen_db_input($products_options_file->filename) . "')");
                        } else {
                          $db->Execute("insert into " . TABLE_FILES_UPLOADED . " (sesskey, files_uploaded_name) values('" . zen_session_id() . "', '" . zen_db_input($products_options_file->filename) . "')");
                        }
                        $insert_id = $db->Insert_ID();
                      }
                      if ($grid_loop < $grid_add_number) {
                // copy the file to another location
                // Set a flag that will indicate that it should be copied back
                // Earlier need to copy the file back to this location if the flag is set.
                // copy();
//                        copy($_FILES['id']['tmp_name'][TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $iFile]], $_FILES['id']['tmp_name'][TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $iFile]] . '.baksba');

                        if ($grid_loop == 1) {
                          $fileVar[$grid_loop][$i]['id'] = $insert_id;
//                          $fileVar[$grid_loop][$i]['location'] = $products_options_file;
                          $fileVar[$grid_loop][$i]['name'] = $products_options_file->filename;
                          $fileVar[$grid_loop][$i]['destination'] = $products_options_file->destination;
                          $fileVar[$grid_loop][$i]['extension'] = $products_image_extension;
                          $products_options_file->filename = $fileVar[$grid_loop][$i]['name'];
                        } else {
                          $fileVar[$grid_loop][$i]['id'] = $fileVar[$grid_loop - 1][$i]['id'];
                          if ($renumber) {
                            $fileVar[$grid_loop][$i]['id'] = $insert_id;
                          }
//                          $fileVar[$grid_loop][$i]['location'] = $fileVar[$grid_loop - 1][$i]['location'];
                          $fileVar[$grid_loop][$i]['name'] = $fileVar[$grid_loop - 1][$i]['name'];
                          $fileVar[$grid_loop][$i]['destination'] = $fileVar[$grid_loop - 1][$i]['destination'];
                          $fileVar[$grid_loop][$i]['extension'] = $fileVar[$grid_loop - 1][$i]['extension'];
//                          $products_options_file = $fileVar[$grid_loop][$i]['location'];
                          $products_options_file->filename = $fileVar[$grid_loop][$i]['name'];
                          $products_image_extension = $fileVar[$grid_loop][$i]['extension'];
                          if (!$renumber) {
                            $insert_id = $fileVar[$grid_loop][$i]['id'];
                          }
//                          $products_options_file->set_filename("$insert_id" . $_SESSION['file_location_name_' . $grid_loop - 1 . '_' . $i]);
//                          $products_options_file->filename = $_SESSION['file_location_name_' . $grid_loop - 1 . '_' . $i];
                        }
                        $fileVar['file_located'] = true;
//                        $_SESSION['file_located'] = true;
                      } else {
                // Clear flag
                        if ($grid_loop == 1) {
//                          $_SESSION['file_location_' . $grid_loop . '_' . $i] = $_FILES['id']['tmp_name'][TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $i]];
//                          $_SESSION['file_location_' . $grid_loop . '_' . $i] = $products_options_file;
                        } else {
                          $fileVar[$grid_loop][$i]['id'] = $fileVar[$grid_loop - 1][$i]['id'];
                          if ($renumber) {
                            $fileVar[$grid_loop][$i]['id'] = $insert_id;
                          }
//                          $fileVar[$grid_loop][$i]['location'] = $fileVar[$grid_loop - 1][$i]['location'];
                          $fileVar[$grid_loop][$i]['name'] = $fileVar[$grid_loop - 1][$i]['name'];
                          $fileVar[$grid_loop][$i]['destination'] = $fileVar[$grid_loop - 1][$i]['destination'];
                          $fileVar[$grid_loop][$i]['extension'] = $fileVar[$grid_loop - 1][$i]['extension'];
                          $products_options_file->filename = $fileVar[$grid_loop][$i]['name'];
                          $products_image_extension = $fileVar[$grid_loop][$i]['extension'];
                          if (!$renumber) {
                            $insert_id = $fileVar[$grid_loop][$i]['id'];
                          }
                          //unset($fileVar['file_located']);
                          //if ($i == $n) {
                          //  unset($fileVar);
                          //}
                        }
                      }
                      $real_ids[TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $i]] = $insert_id . ". " . $products_options_file->filename;
                      $products_options_file->set_filename("$insert_id" . $products_image_extension);
                      if ($grid_loop == 1 && !($products_options_file->save())) {
                        $fileVar[$grid_loop][$i]['failed'] = true;
                        break;
                      } else {
                        if ($grid_loop == 1) {
                          $fileVar[$grid_loop][$i]['location'] = $products_options_file;
                        } elseif ($grid_loop > 1 && array_key_exists('failed', $fileVar[$grid_loop - 1][$i]) && $fileVar[$grid_loop - 1][$i]['failed']) {
                          $fileVar[$grid_loop][$i]['failed'] = $fileVar[$grid_loop - 1][$i]['failed'];
                          break;
                        } elseif ($grid_loop > 1 && !array_key_exists('failed', $fileVar[$grid_loop - 1][$i])) {
                          $fileVar[$grid_loop][$i]['location'] = $products_options_file;
                          copy($fileVar[$grid_loop - 1][$i]['destination'] . $fileVar[$grid_loop - 1][$i]['id'] . $fileVar[$grid_loop - 1][$i]['extension'], $fileVar[$grid_loop][$i]['destination'] . $fileVar[$grid_loop][$i]['id'] . $fileVar[$grid_loop][$i]['extension']);
                        }
                      }
                    } else {
                      break;
                    }
                  } else { // No file uploaded -- use previous value
                    $real_ids[TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $i]] = $_POST[TEXT_PREFIX . UPLOAD_PREFIX . $i];
                  }
                }
              }

              $_SESSION['cart']->add_cart($_POST['products_id'], $_SESSION['cart']->get_quantity(zen_get_uprid($_POST['products_id'], $real_ids))+($new_qty), $real_ids);
              // iii 030813 end of changes.
            } // eof: set error message
          } // eof: quantity maximum = 1

          if ($adjust_max == 'true') {
            $messageStack->add_session('shopping_cart', ERROR_MAXIMUM_QTY . zen_get_products_name($_POST['products_id']), 'caution');
            if ($_SESSION['cart']->display_debug_messages) $messageStack->add_session('header', 'E: FUNCTION ' . __FUNCTION__ . '<br>' . ERROR_MAXIMUM_QTY . zen_get_products_name($_POST['products_id']), 'caution');
          }
    
          // Want to bypass this entire section if not done with addressing all of the products, though also may need to pull out some of
          //  the actions so that all products are addressed, but basically do not want to redirect away from this operation until the
          //  last object has been addressed.  Maybe just need to if around the redirects and leave the add_session information
          if ($the_list == '') {
            // no errors
  // display message if all is good and not on shopping_cart page
            if (DISPLAY_CART == 'false' && $_GET['main_page'] != FILENAME_SHOPPING_CART && $messageStack->size('shopping_cart') == 0) {
              $messageStack->add_session('header', ($_SESSION['cart']->display_debug_messages ? 'FUNCTION ' . __FUNCTION__ . ': ' : '') . SUCCESS_ADDED_TO_CART_PRODUCT, 'success');
              if ($grid_loop >= $grid_add_number) {
                zen_redirect(zen_href_link($goto, zen_get_all_get_params($parameters)));
              }
            } else {
              if ($grid_loop >= $grid_add_number) {
                zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
              }
            }
          } else {
            // errors found with attributes - perhaps display an additional message here, using an observer class to add to the messageStack
            $_SESSION['cart']->notify('NOTIFIER_CART_OPTIONAL_ATTRIBUTE_ERROR_MESSAGE_HOOK', $_POST, $the_list);
            $_GET['action'] = '';
          }
        }
      }
    } // EOF while(grid_loop++ <= $grid_add_number
  } 
}
