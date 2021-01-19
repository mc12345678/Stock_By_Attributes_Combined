<?php
/**
 * @package products_with_attributes_stock
 * @copyright Copyright 2003-2016 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * $Id: init_eo_sba.php xxxx 2016-11-14 20:31:10Z mc12345678 $
 */


if (defined('FILENAME_EDIT_ORDERS') && $_SERVER['SCRIPT_NAME'] == DIR_WS_ADMIN . (!strstr(FILENAME_EDIT_ORDERS, '.php') ? FILENAME_EDIT_ORDERS . '.php' : FILENAME_EDIT_ORDERS)) {
//echo "made it all the way here";
/* ?><script type="text/javascript">
//var test = function(){ alert('Howdy');};
</script><?php */
if(!function_exists('zen_html_quotes')) {
  function zen_html_quotes($string) {
    if(function_exists('zen_db_output')) {
      return zen_db_output($string);
    }
    return htmlspecialchars($string, ENT_COMPAT, CHARSET, TRUE);
  }
}
  
  // Problem with "removing" the $_POST in the following update_order, is that the order of product is modified.
  if (isset($_GET['action']) && $_GET['action'] == 'update_order') {
    //$_SESSION['edit_u'] = $_POST;

    if (!isset($_SESSION['language'])) {
      $_SESSION['language'] = 'english';
    }
    if (file_exists(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $template_dir . 'edit_orders_sba.php')) {
      require_once(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $template_dir . 'edit_orders_sba.php');
    } else {
      require_once(DIR_WS_LANGUAGES . $_SESSION['language'] . '/edit_orders_sba.php');
    }
    
       $oID = zen_db_prepare_input((isset($_GET['oID']) && (int)$_GET['oID'] > 0 ? (int)$_GET['oID'] : 0));

       // Load the order details.
    $index = 0;    
    $orders_products_query = "select orders_products_id, products_id, products_name,
                                 products_model, products_price, products_tax,
                                 products_quantity, final_price,
                                 onetime_charges,
                                 products_priced_by_attribute, product_is_free, products_discount_type,
                                 products_discount_type_from
                                  from " . TABLE_ORDERS_PRODUCTS . "
                                  where orders_id = '" . (int)$oID . "'
                                  order by orders_products_id";
    $orders_products = $db->Execute($orders_products_query);
    unset($orders_products_query);

    $orders_products_id_mapping = eo_get_orders_products_id_mappings((int)$oID);

    $order = array();
    while (!$orders_products->EOF) {
      $order[$index] = array('id' => $orders_products->fields['products_id']);
      
      // Line 965
      $orders_products_id = $orders_products_id_mapping[$index];

      // Line 984
      $selected_attributes_id_mapping = eo_get_orders_products_options_id_mappings($oID, $orders_products_id);
      $attrs = eo_get_product_attributes_options($order[$index]['id']);
      $optionID = array_keys($attrs);
      for($j=0; $j<count($attrs); $j++)
      {
          if (empty($selected_attributes_id_mapping)) {
              continue;
          }
          
          $optionInfo = $attrs[(int)$optionID[$j]];
          $orders_products_attributes_id = $selected_attributes_id_mapping[(int)$optionID[$j]];

          if (!defined('PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID')) define('PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID', -1);

          switch($optionInfo['type']) {
              case PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID:
              case PRODUCTS_OPTIONS_TYPE_RADIO:
              case PRODUCTS_OPTIONS_TYPE_SELECT:
              case PRODUCTS_OPTIONS_TYPE_SELECT_SBA:
              case PRODUCTS_OPTIONS_TYPE_GRID:

                  $selected_attribute = null;
                  foreach($optionInfo['options'] as $attributeId => $attributeValue) {
                      if(isset($orders_products_attributes_id[0]) && eo_is_selected_product_attribute_id($orders_products_attributes_id[0], $attributeId)) {
                          $selected_attribute = $attributeId;
                      }
                  }


                  $order[$index]['attrs'][(int)$optionID[$j]]['value'] = zen_html_quotes($selected_attribute);
                  $order[$index]['attrs'][(int)$optionID[$j]]['type'] = (int)$optionInfo['type'];

                  break;
              case PRODUCTS_OPTIONS_TYPE_CHECKBOX:
                  foreach($optionInfo['options'] as $attributeId => $attributeValue) {
                      for($k=0, $s = count($orders_products_attributes_id);$k<$s;$k++) {
                          if(eo_is_selected_product_attribute_id($orders_products_attributes_id[$k], $attributeId)) {
                              $order[$index]['attrs'][(int)$optionID[$j]]['value'][$attributeId] = zen_html_quotes($attributeId);
                              $order[$index]['attrs'][(int)$optionID[$j]]['type'] = (int)$optionInfo['type'];
                          }
                      }
                  }
                  $order[$index]['attrs'][(int)$optionID[$j]]['type'] = (int)$optionInfo['type'];
                  unset($k,$s,$attributeId, $attributeValue);
                  break;
              case PRODUCTS_OPTIONS_TYPE_TEXT:
                  $text = eo_get_selected_product_attribute_value_by_id($orders_products_attributes_id[0], array_shift(array_keys($optionInfo['options'])));

                  if($text === null) {
                      $text = '';
                  }
                  $text = zen_html_quotes($text);

                  $order[$index]['attrs'][(int)$optionID[$j]]['value'] = zen_html_quotes($text);
                  $order[$index]['attrs'][(int)$optionID[$j]]['type'] = (int)$optionInfo['type'];
                  unset($text);

                  break;
              case PRODUCTS_OPTIONS_TYPE_FILE:
                  $value = eo_get_selected_product_attribute_value_by_id($orders_products_attributes_id[0], array_shift(array_keys($optionInfo['options'])));
                  if(zen_not_null($value)) {
                      $order[$index]['attrs'][(int)$optionID[$j]]['value'] = zen_html_quotes($value);
                  }
                  $order[$index]['attrs'][(int)$optionID[$j]]['type'] = (int)$optionInfo['type'];

                  unset($value);
                  break;
              case PRODUCTS_OPTIONS_TYPE_READONLY:
              default:
                  $optionValue = array_shift($optionInfo['options']);

                  $order[$index]['attrs'][(int)$optionID[$j]]['value'] = zen_html_quotes($optionValue);
                  $order[$index]['attrs'][(int)$optionID[$j]]['type'] = (int)$optionInfo['type'];

                  break;
          }
      }
      
      unset($optionID); unset($optionInfo);

      $index++;
      $orders_products->MoveNext();
    }
    unset($orders_products);
    unset($index);
    unset($attrs);
    unset($subindex);
    
    
       if(array_key_exists('update_products', $_POST)) {
            $_POST['update_products'] = zen_db_prepare_input($_POST['update_products']);

            foreach($_POST['update_products'] as $orders_products_id => $product_update) {
                $rowID = -1;
                $orders_products_id_mapping = eo_get_orders_products_id_mappings((int)$oID);

                for($i=0; $i<count($orders_products_id_mapping); $i++) {
                    if($orders_products_id == $orders_products_id_mapping[$i]) {
                        $rowID = $i;
                        break;
                    }
                }
                unset($orders_products_id_mapping); unset($i);

                // Only update if there is an existing item in the order
                if($rowID >= 0) {

                    if (!isset($order[$rowID]['attrs']) || !array_key_exists('attrs', $order[$rowID])) continue;

                    // Grab the old product + attributes
                    $old_product = $order[$rowID];
                    $old_attrs = $order[$rowID]['attrs'];
                    $old_eo_attrs = array();
                  
                  // Capture the old attribute information so that it can be applied
                  //   when/if the product needs to be stricken from the update list so that 
                  //   the sequence remains the same.

                    if ((isset($product_update['attr']) || array_key_exists('attr', $product_update)) && $product_update['qty'] > 0) {

                        // Retrieve the information for the new product
                        $product_options = $product_update['attr'];
                        unset($product_update['attr']);
                        $product_id = $old_product['id'];
                      
                        // Do not process this product further in this routine because the product is not tracked by SBA.
                        if (!$_SESSION['pwas_class2']->zen_product_is_sba($product_id)) continue;
  
                        // Handle attributes
                        if(!empty($product_options) && is_array($product_options))
                        {
                          $retval = array();
                          $retval['attributes'] = array();

                          include_once(DIR_WS_CLASSES . 'attributes.php');
                          $attrs = new attributes();

                          foreach($product_options as $option_id => $details) {

                              $attr = array();
                              switch($details['type']) {
                                  case PRODUCTS_OPTIONS_TYPE_TEXT:
                                  case PRODUCTS_OPTIONS_TYPE_FILE:
                                      $attr['option_id'] = $option_id;
                                      $attr['value'] = $details['value'];
                                      if($attr['value'] == '') continue 2;

                                      // There should only be one text per name.....
                                      $get_attr_id = $attrs->get_attributes_by_option($product_id, $option_id);
                                      if(count($get_attr_id) == 1) $details['value'] = $get_attr_id[0]['products_attributes_id'];
                                      unset($get_attr_id);
                                      break;
                                  case PRODUCTS_OPTIONS_TYPE_CHECKBOX:
                                      if(!array_key_exists('value', $details)) continue 2;
                                      $tmp_id = array_shift($details['value']);
                                      foreach($details['value'] as $attribute_id) {
                                          // We only get here if more than one checkbox per
                                          // option was selected.
                                          $tmp = $attrs->get_attribute_by_id($attribute_id, 'order');
                                          $retval['attributes'][] = $tmp;
                  
                                          // Handle pricing
                                          $prices = eo_get_product_attribute_prices(
                                              $attribute_id, $tmp['value'], $product_qty
                                          );
                                          unset($tmp);
                                      }
                                      $details['value'] = $tmp_id;
                                      $attr = $attrs->get_attribute_by_id($details['value'], 'order');
                                      unset($attribute_id); unset($attribute_value); unset($tmp_id);
                                      break;
                                  default:
                                      $attr = $attrs->get_attribute_by_id($details['value'], 'order');
                              }
                              $retval['attributes'][] = $attr;

                          }
                          unset($attr, $option_id, $details);
                        }
                    
                        $exists = $_SESSION['pwas_class2']->zen_get_sba_attribute_info($product_id, $retval['attributes'], 'order', 'ids');

                        if (empty($exists)) { // Converted from !zen_not_null
//                          unset($_POST['update_products'][$orders_products_id]);
                            $_POST['update_products'][$orders_products_id]['attr'] = $old_attrs;
/*

attr (array)  
30 ml
    11 (array)  
        value (string) => 2152
        type (integer) => 0
    14 (array)  
        value (string) => 2149
        type (integer) => 0
    15 (array)  
        value (string) => 2155
        type (integer) => 0


attr (array)  
15 ml
    11 (array)  
        value (string) => 2151
        type (integer) => 0
    14 (array)  
        value (string) => 2149
        type (integer) => 0
    15 (array)  
        value (string) => 2155
        type (integer) => 0


*/
//                          $_SESSION['delete_' . $orders_products_id] = $_POST['update_products'][$orders_products_id];

                            // @todo : get a new/updated message for this condition.
                            $opt_name = array();
                            $opt_name_value = array();
                            $opt_comb = array();
        
                            foreach ($retval['attributes'] as $key => $value) {
                                $opt_name[] = zen_options_name($value['option_id']);
                                $opt_name_value[] = $value['value'];
                                $opt_comb[] = '<b>' . $opt_name[count($opt_name) - 1] . '</b>: <i>' . $opt_name_value[count($opt_name_value) - 1] . '</i>';
                            }
                            unset($key);
                            unset($value);
        
                            $attr_list = '<br />' . implode('<br />', $opt_comb) . '<br />';

                            $messageStack->add_session(sprintf(WARNING_ORDER_UPDATE_VARIANT_DOES_NOT_EXIST_SBA, $_POST['update_products'][$orders_products_id]['name'], $attr_list, $rowID + 1, $_POST['update_products'][$orders_products_id]['qty']), 'warning');
                        }
                        unset($exists);
                        unset($product_options);

                    }  
                }
            }
       }
//       $_SESSION['edit_u_a'] = $_POST;

  }
  
  
  if (isset($_GET['action']) && $_GET['action'] == 'add_prdct') {

    if (!isset($_SESSION['language'])) {
      $_SESSION['language'] = 'english';
    }
    if (file_exists(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $template_dir_select . 'edit_orders_sba.php')) {
      require_once(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $template_dir_select . 'edit_orders_sba.php');
    } else {
      require_once(DIR_WS_LANGUAGES . $_SESSION['language'] . '/edit_orders_sba.php');
    }
    
    if (isset($_POST['step']) && $_POST['step'] == '2') {
/*

edit_a (array)
securityToken (string) => removed
add_product_categories_id (string) => 108
search (string) =>
step (string) => 2

*/
    }
    
/*
edit_a (array)
securityToken (string) => removed
add_product_products_id (string) => 338
add_product_categories_id (string) => 108
search (string) =>
step (string) => 3
*/

    if (isset($_POST['step']) && $_POST['step'] >= 4) {

      $product_options = $_POST['id'];
      $product_id = (int)$_POST['add_product_products_id'];
    }

    if (isset($_POST['step']) && $_POST['step'] >= 4 && $_SESSION['pwas_class2']->zen_product_is_sba($product_id)) {
    
    // Handle attributes
      if(!empty($product_options) && is_array($product_options))
      {
        $retval = array();
        $retval['attributes'] = array();

        include_once(DIR_WS_CLASSES . 'attributes.php');
        $attrs = new attributes();

        foreach($product_options as $option_id => $details) {

            $attr = array();
            switch($details['type']) {
                case PRODUCTS_OPTIONS_TYPE_TEXT:
                case PRODUCTS_OPTIONS_TYPE_FILE:
                    $attr['option_id'] = $option_id;
                    $attr['value'] = $details['value'];
                    if($attr['value'] == '') continue 2;

                    // There should only be one text per name.....
                    $get_attr_id = $attrs->get_attributes_by_option($product_id, $option_id);
                    if(count($get_attr_id) == 1) $details['value'] = $get_attr_id[0]['products_attributes_id'];
                    unset($get_attr_id);
                    break;
                case PRODUCTS_OPTIONS_TYPE_CHECKBOX:
                    if(!array_key_exists('value', $details)) continue 2;
                    $tmp_id = array_shift($details['value']);
                    foreach($details['value'] as $attribute_id) {
                        // We only get here if more than one checkbox per
                        // option was selected.
                        $tmp = $attrs->get_attribute_by_id($attribute_id, 'order');
                        $retval['attributes'][] = $tmp;

                        // Handle pricing
                        $prices = eo_get_product_attribute_prices(
                            $attribute_id, $tmp['value'], $product_qty
                        );
                        unset($tmp);
                    }
                    $details['value'] = $tmp_id;
                    $attr = $attrs->get_attribute_by_id($details['value'], 'order');
                    unset($attribute_id); unset($attribute_value); unset($tmp_id);
                    break;
                default:
                    $attr = $attrs->get_attribute_by_id($details['value'], 'order');
            }
            $retval['attributes'][] = $attr;
        }
        unset($attr, $option_id, $details);
      }

      // Verify combination exists.
      $exists = $_SESSION['pwas_class2']->zen_get_sba_attribute_info($product_id, $retval['attributes'], 'order', 'ids');
      
// @todo : get a new/updated message for this condition.
        $opt_name = array();
        $opt_name_value = array();
        $opt_comb = array();
        
        foreach ($retval['attributes'] as $key => $value) {
            $opt_name[] = zen_options_name($value['option_id']);
            $opt_name_value[] = $value['value'];
            $opt_comb[] = '<b>' . $opt_name[count($opt_name) - 1] . '</b>: <i>' . $opt_name_value[count($opt_name_value) - 1] . '</i>';
        }
        unset($key); unset($value);
        
        $attr_list = '<br />' . implode('<br />', $opt_comb) . '<br />';
        unset($opt_comb); unset($opt_name); unset($opt_name_value);
      
      // Check to see if the combination exists, if it does not then return to the selection to modify it.. 
      if (!zen_not_null($exists)) {
        $_POST['step'] = '3';

        $messageStack->add(sprintf(WARNING_ORDER_ADD_VARIANT_DOES_NOT_EXIST_SBA, zen_get_products_name($_POST['add_product_products_id']), $attr_list), 'warning');
      }
      
      // Check to see if there is any stock available/allowed to over sell?
      $quantity_avail = $_SESSION['pwas_class2']->zen_get_sba_attribute_info($product_id, $retval['attributes'], 'order', 'stock');

      // This will prevent editing an order to permit stock to go negative if the store is set to prevent going negative.
      if (zen_not_null($exists) && ($quantity_avail <= 0 || isset($_POST['add_product_quantity']) && $quantity_avail - (float)$_POST['add_product_quantity'] < 0) && STOCK_ALLOW_CHECKOUT == 'false') {
        $messageStack->add(sprintf(WARNING_ORDER_ADD_VARIANT_OUT_OF_STOCK_SBA, zen_get_products_name($_POST['add_product_products_id']), $attr_list, $quantity_avail), 'warning');

        $_POST['step'] = $_POST['step'] - 1;
      }
      unset($exists);
      unset($attr_list);
      unset($retval);
      unset($quantity_avail);
    }

/*
edit_a (array)
securityToken (string) => removed
id (array)
11 (array)
value (string) => 2151
type (integer) => 0
14 (array)
value (string) => 2148
type (integer) => 0
15 (array)
value (string) => 2155
type (integer) => 0
add_product_categories_id (string) => 108
add_product_products_id (string) => 338
search (string) =>
step (string) => 4  
*/


/*
edit_a (array)
securityToken (string) => removed
add_product_quantity (string) => 1
applyspecialstoprice (string) => on
id (array)
11 (array)
value (string) => 2152
type (integer) => 0
14 (array)
value (string) => 2149
type (integer) => 0
15 (array)
value (string) => 2150
type (integer) => 0
add_product_categories_id (string) => 108
add_product_products_id (string) => 338
step (string) => 5
*/
/*    if ($_POST['step'] >= 5) {
      $_SESSION['get-em'] = $_GET;
    }*/
//    $_SESSION['edit_a'] = $_POST;
  }
  
}
