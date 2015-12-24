<?php
/*
 * Stock by Attributes 1.5.4 15-11-14 mc12345678
 */

//What about: 'multiple_products_add_product' (Needs to be addressed though don't see at the moment why since generally unable to select multiple products each with attributes, perhaps something to consider for later, but let's get serious here at the moment as there are more routine actions to be handled properly first.), 'update_product' (Needs to be addressed), or 'cart' (does a notify action, so may need to address?)actions?
if (isset($_GET['action']) && $_GET['action'] == 'update_product') {
  if ($_SESSION['cart']->display_debug_messages) $messageStack->add_session('header', 'FUNCTION ' . __FUNCTION__, 'caution');

  $productIsSBA = array();
  for ($i=0, $n=sizeof($_POST['products_id']); $i<$n; $i++) {
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
    if ( in_array($_POST['products_id'][$i], (is_array($_POST['cart_delete']) ? $_POST['cart_delete'] : array())) or $_POST['cart_quantity'][$i]==0) {
      $_SESSION['cart']->remove($_POST['products_id'][$i]);
    } else {
      if((PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
        /* Breakdown the attributes into individual attributes to then be able to 
         * feed them into the applicable section(s).
         * 
         */
      }
      $add_max = zen_get_products_quantity_order_max($_POST['products_id'][$i]); // maximum allowed
      $query = 'select stock_id from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK .  ' where products_id = :products_id:';
      $query = $db->bindVars($query, ':products_id:', zen_get_prid($_POST['products_id'][$i]), 'integer');
      $stock_id = $db->Execute($query, false, false, 0, true);

      if((PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
        /* Breakdown the attributes into individual attributes to then be able to 
         * feed them into the applicable section(s).
         * 
         */
      }
      $attributes = ($_POST['id'][$_POST['products_id'][$i]]) ? $_POST['id'][$_POST['products_id'][$i]] : null;
      if ($stock_id->RecordCount()) {
        $productIsSBA[$i] = true;
      } else {
        $productIsSBA[$i] = false;
      }
      if (!$productIsSBA[$i]) {
        if((PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
        /* Breakdown the attributes into individual attributes to then be able to 
         * feed them into the applicable section(s).
         * 
         */
        }
        $cart_qty = $_SESSION['cart']->in_cart_mixed($_POST['products_id'][$i]); // total currently in cart
      } else {
        if((PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
          /* Breakdown the attributes into individual attributes to then be able to 
           * feed them into the applicable section(s).
           * 
           */
        }
        $cart_qty = $_SESSION['cart']->in_cart_mixed($_POST['products_id'][$i]);
      }
      if ($_SESSION['cart']->display_debug_messages) $messageStack->add_session('header', 'FUNCTION ' . __FUNCTION__ . ' Products_id: ' . $_POST['products_id'][$i] . ' cart_qty: ' . $cart_qty . ' <br>', 'caution');
      $new_qty = $_POST['cart_quantity'][$i]; // new quantity
      $current_qty = $_SESSION['cart']->get_quantity($_POST['products_id'][$i]); // how many currently in cart for attribute
        if($productIsSBA[$i] && (PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
          /* Breakdown the attributes into individual attributes to then be able to 
           * feed them into the applicable section(s).
           * 
           */
        }
// how many currently in cart for attribute
      $chk_mixed = zen_get_products_quantity_mixed($_POST['products_id'][$i]); // use mixed

        if($productIsSBA[$i] && (PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
          /* Breakdown the attributes into individual attributes to then be able to 
           * feed them into the applicable section(s).
           * 
           */
        }
      $new_qty = $_SESSION['cart']->adjust_quantity($new_qty, $_POST['products_id'][$i], 'shopping_cart');
// bof: adjust new quantity to be same as current in stock
// Mine          $chk_current_qty = zen_get_products_stock($_POST['products_id'][$i]);
//          if (!$productIsSBA[$i]) {
//            $chk_current_qty = zen_get_products_stock($_POST['products_id'][$i]);
//          } else {
        if($productIsSBA[$i] && (PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
          /* Breakdown the attributes into individual attributes to then be able to 
           * feed them into the applicable section(s).
           * 
           */
        }
          $chk_current_qty = zen_get_products_stock($_POST['products_id'][$i], $attributes);
//          }
//          $_SESSION['qty_chk_current_qty'] = $chk_current_qty;
        if (STOCK_ALLOW_CHECKOUT == 'false' && ($new_qty > $chk_current_qty)) {
            $new_qty = $chk_current_qty;
            $messageStack->add_session('shopping_cart', ($_SESSION['cart']->display_debug_messages ? 'FUNCTION ' . __FUNCTION__ . ': ' : '') . WARNING_PRODUCT_QUANTITY_ADJUSTED . zen_get_products_name($_POST['products_id'][$i]), 'caution');
        }

        if($productIsSBA[$i] && (PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
          /* Breakdown the attributes into individual attributes to then be able to 
           * feed them into the applicable section(s).
           * 
           */
        }
      $attributes = ($_POST['id'][$_POST['products_id'][$i]]) ? $_POST['id'][$_POST['products_id'][$i]] : '';

// eof: adjust new quantity to be same as current in stock
      if (($add_max == 1 and $cart_qty == 1) && $new_qty != $cart_qty) {
        // do not add
        $adjust_max= 'true';
      } else {
      if ($add_max != 0) {
// bof: adjust new quantity to be same as current in stock
          if (STOCK_ALLOW_CHECKOUT == 'false' && ($new_qty + $cart_qty > $chk_current_qty)) {
              $adjust_new_qty = 'true';
              $alter_qty = $chk_current_qty - $cart_qty;
              $new_qty = ($alter_qty > 0 ? $alter_qty : 0);
              $messageStack->add_session('shopping_cart', ($_SESSION['cart']->display_debug_messages ? 'FUNCTION ' . __FUNCTION__ . ': ' : '') . WARNING_PRODUCT_QUANTITY_ADJUSTED . zen_get_products_name($_POST['products_id'][$i]), 'caution');
          }
// eof: adjust new quantity to be same as current in stock
        // adjust quantity if needed
      switch (true) {
        case ($new_qty == $current_qty): // no change
          $adjust_max= 'false';
          $new_qty = $current_qty;
          break;
        case ($new_qty > $add_max && $chk_mixed == false):
          $adjust_max= 'true';
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
        if($productIsSBA[$i] && (PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
          /* Breakdown the attributes into individual attributes to then be able to 
           * feed them into the applicable section(s).
           * 
           */
        }
        $attributes = ($_POST['id'][$_POST['products_id'][$i]]) ? $_POST['id'][$_POST['products_id'][$i]] : '';
        $_SESSION['cart']->add_cart($_POST['products_id'][$i], $new_qty, $attributes, false);
      } else {
        // adjust minimum and units
        $attributes = ($_POST['id'][$_POST['products_id'][$i]]) ? $_POST['id'][$_POST['products_id'][$i]] : '';
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
  if (isset($_POST['products_id'] ) && is_numeric ( $_POST['products_id'])) {
//Loop for each product in the cart
    if ($_SESSION['cart']->display_debug_messages) $messageStack->add_session('header', 'A2: FUNCTION ' . __FUNCTION__, 'caution');
    $the_list = '';
    $adjust_max= 'false';
    if (isset($_POST['id'])) {
      if($productIsSBA[$i] && (PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2') /*single dropdown as multiple*/) {
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
    if((PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_dropdown' || PRODINFO_ATTRIBUTE_PLUGIN_MULTI == 'single_radioset') && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2')) {
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
    $attributes = (isset($_POST['id']) && zen_not_null($_POST['id'])  ? $_POST['id']  : null );
    $product_id = zen_get_uprid($_POST['products_id'], $attributes);

    $add_max = zen_get_products_quantity_order_max($_POST['products_id']);
    $cart_qty = $_SESSION['cart']->get_quantity($product_id);
    
    if ($_SESSION['cart']->display_debug_messages) $messageStack->add_session('header', 'B: FUNCTION ' . __FUNCTION__ . ' Products_id: ' . $_POST['products_id'] . ' cart_qty: ' . $cart_qty . ' $_POST[cart_quantity]: ' . $_POST['cart_quantity'] . ' <br>', 'caution');

    $query = 'SELECT * 
                    FROM information_schema.tables
                    WHERE table_schema = :your_db: 
                    AND table_name = :table_name:
                    LIMIT 1;';
    $query = $db->bindVars($query, ':your_db:', DB_DATABASE, 'string');
    $query = $db->bindVars($query, ':table_name:', TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'string');
  $stock_id = $db->Execute($query, false, false, 0, true);
  if ($stock_id->RecordCount() > 0) {
      
      $query = 'select stock_id from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK .  ' where products_id = :products_id:';
      $query = $db->bindVars($query, ':products_id:',  $_POST['products_id'], 'integer');
      $stock_id = $db->Execute($query, false, false, 0, true);
//  $_SESSION['stock_idquery'] = $stock_id->RecordCount();
//Check if item is an SBA tracked item, if so, then perform analysis of whether to add or not.
    }
    if ($stock_id->RecordCount() > 0) {
//Looks like $_SESSION['cart']->in_cart_mixed($prodId) could be used here to pull the attribute related product information to verify same product is being added to cart... This also may help in the shopping_cart routine added for SBA as all SBA products will have this modifier.
//      $cart_qty = 0;
      $new_qty = $_POST['cart_quantity']; //Number of items being added (Known to be SBA tracked already)
      $new_qty = $_SESSION['cart']->adjust_quantity($new_qty, $_POST['products_id'], 'header');

// bof: adjust new quantity to be same as current in stock
      $chk_current_qty = zen_get_products_stock($product_id, $attributes);
      $_SESSION['cart']->flag_duplicate_msgs_set = FALSE;
      
      $productAttrAreSBA = zen_get_sba_stock_attribute_id($product_id, $attributes, 'products');
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
            include(DIR_WS_CLASSES . 'upload.php');
            for ($i = 1, $n = $_GET['number_of_uploads']; $i <= $n; $i++) {
              if (zen_not_null($_FILES['id']['tmp_name'][TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $i]]) and ($_FILES['id']['tmp_name'][TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $i]] != 'none')) {
                $products_options_file = new upload('id');
                $products_options_file->set_destination(DIR_FS_UPLOADS);
                $products_options_file->set_output_messages('session');
                if ($products_options_file->parse(TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $i])) {
                  $products_image_extension = substr($products_options_file->filename, strrpos($products_options_file->filename, '.'));
                  if ($_SESSION['customer_id']) {
                    $db->Execute("insert into " . TABLE_FILES_UPLOADED . " (sesskey, customers_id, files_uploaded_name) values('" . zen_session_id() . "', '" . $_SESSION['customer_id'] . "', '" . zen_db_input($products_options_file->filename) . "')");
                  } else {
                    $db->Execute("insert into " . TABLE_FILES_UPLOADED . " (sesskey, files_uploaded_name) values('" . zen_session_id() . "', '" . zen_db_input($products_options_file->filename) . "')");
                  }
                  $insert_id = $db->Insert_ID();
                  $real_ids[TEXT_PREFIX . $_POST[UPLOAD_PREFIX . $i]] = $insert_id . ". " . $products_options_file->filename;
                  $products_options_file->set_filename("$insert_id" . $products_image_extension);
                  if (!($products_options_file->save())) {
                    break;
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
    
      if ($the_list == '') {
        // no errors
  // display message if all is good and not on shopping_cart page
        if (DISPLAY_CART == 'false' && $_GET['main_page'] != FILENAME_SHOPPING_CART && $messageStack->size('shopping_cart') == 0) {
          $messageStack->add_session('header', ($_SESSION['cart']->display_debug_messages ? 'FUNCTION ' . __FUNCTION__ . ': ' : '') . SUCCESS_ADDED_TO_CART_PRODUCT, 'success');
          zen_redirect(zen_href_link($goto, zen_get_all_get_params($parameters)));
        } else {
          zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
        }
      } else {
        // errors found with attributes - perhaps display an additional message here, using an observer class to add to the messageStack
        $_SESSION['cart']->notify('NOTIFIER_CART_OPTIONAL_ATTRIBUTE_ERROR_MESSAGE_HOOK', $_POST, $the_list);
        $_GET['action'] = '';
      }
    }
  }
}
