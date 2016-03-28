<?php

// $_SESSION['post_info_sba_grid'] = $_POST;

switch($_GET['action']) {
  case 'add_product':
  if (is_array($_POST['product_id']) && (function_exists('zen_product_is_sba') ? !zen_product_is_sba($_POST['products_id']) : true)) {
//    $_attribs = $_POST['attribs'];
    foreach($_POST['product_id'] as $prid => $qty) {
      $products_id = zen_get_prid($prid);
//      $_POST['attribs'] = $_attribs;
      if (is_array($_POST['id'])) {
        foreach($_POST['id'] as $option => $option_value) {
          $_POST['attribs'][$prid][$option] = $option_value;
        }
      }
      foreach($_POST['attribs'][$prid] as $option_id => $value_id) {
        if (substr($option_id, 0, strlen(TEXT_PREFIX)) == TEXT_PREFIX) {
          $option_id = substr($option_id, strlen(TEXT_PREFIX));
        }
        $check_attrib = $db->Execute(  "select pov.products_options_values_name from " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov " . 
                      "where pa.options_values_id = pov.products_options_values_id " .
                    "and pa.options_id = '".(int)$option_id . "' " . 
                    "and pa.products_id = '".(int)$products_id ."' " . 
                    "and pov.language_id = '".(int)$_SESSION['languages_id']."'");
//        $_SESSION['check_' . $prid . '_' . $option_id . '_' . $value_id] = $check_attrib;
        if ($check_attrib->RecordCount() <= 1 && $check_attrib->fields['products_options_values_name'] == '') {
          unset($_POST['attribs'][$prid][$option_id]);
        }
//        $_SESSION['check_' . $prid . '_' . $option_id . '_' . $value_id . '_post'] = $_POST['attribs'];
      }
      if (is_numeric($qty) && zen_not_null($qty) && $qty > 0) {
        reset($_POST['attribs'][$prid]);
//        $_SESSION['check_' . $prid . '_' . $option_id . '_' . $value_id] = $check_attrib;
//        $in_cart_qty = $_SESSION['cart']->get_quantity(zen_get_uprid($_POST['products_id'], $real_ids))+($new_qty);
        $_SESSION['cart']->add_cart($products_id, $_SESSION['cart']->get_quantity(zen_get_uprid($_POST['products_id'], $_POST['attribs'][$prid]))+($qty), $_POST['attribs'][$prid]);
      }
    }
    //$_POST['cart_quantity'] = 0;
    unset($_POST['products_id']);
  $messageStack->reset();
  unset($_SESSION['cart_errors']);
  $_SESSION['cart']->get_products(false);  //Update all prices now we have added everything
  }
  //$_SESSION['post_info_sba_grid_after'] = $_POST;
  break;
}

