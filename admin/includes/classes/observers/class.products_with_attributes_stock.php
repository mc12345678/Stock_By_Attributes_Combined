<?php

/**************
 *
 *
 * Updated 15-11-14 mc12345678
 */

class products_with_attributes_stock_admin extends base {

  //
  private $_customid = array();
  private $_productI;  
/*  private $_productI;
  
  private $_i;

  private $_stock_info = array();
  
  private $_attribute_stock_left;

  private $_stock_values;*/
  
  /*
   * This is the observer for the admin side of SBA currently covering admin/includes/functions/general.php file to support Stock By Attributes when the order is being processed at the end of the purchase.
   */
  function __construct() {

    $attachNotifier = array();
    $attachNotifier[] = 'NOTIFIER_ADMIN_ZEN_REMOVE_PRODUCT';
    $attachNotifier[] = 'NOTIFIER_ADMIN_ZEN_REMOVE_ORDER';
    $attachNotifier[] = 'NOTIFIER_ADMIN_ZEN_DELETE_PRODUCTS_ATTRIBUTES';
    $attachNotifier[] = 'NOTIFY_PACKINGSLIP_INLOOP';
    $attachNotifier[] = 'NOTIFY_PACKINGSLIP_IN_ATTRIB_LOOP';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTE_CONTROLLER_DELETE_ATTRIBUTE';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTE_CONTROLLER_DELETE_ALL';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTE_CONTROLLER_DELETE_OPTION_NAME_VALUES';
    $attachNotifier[] = 'NOTIFY_ADMIN_PRODUCT_COPY_TO_ATTRIBUTES';
//    $attachNotifier[] = 'NOTIFY_MODULES_COPY_TO_CONFIRM_ATTRIBUTES';
    $attachNotifier[] = 'OPTIONS_NAME_MANAGER_DELETE_OPTION';
    $attachNotifier[] = 'OPTIONS_NAME_MANAGER_UPDATE_OPTIONS_VALUES_DELETE';
    $attachNotifier[] = 'OPTIONS_VALUES_MANAGER_DELETE_VALUE';
    $attachNotifier[] = 'OPTIONS_VALUES_MANAGER_DELETE_VALUES_OF_OPTIONNAME';
    $attachNotifier[] = 'ORDER_QUERY_ADMIN_COMPLETE';
    $attachNotifier[] = 'EDIT_ORDERS_ADD_PRODUCT_STOCK_DECREMENT'; // Need to code for
    $attachNotifier[] = 'EDIT_ORDERS_ADD_PRODUCT';
    $attachNotifier[] = 'EDIT_ORDERS_REMOVE_PRODUCT_STOCK_DECREMENT'; // Need to code for
    $attachNotifier[] = 'EDIT_ORDERS_REMOVE_PRODUCT';
    $attachNotifier[] = 'NOTIFY_EO_GET_PRODUCTS_STOCK'; // Need to code for

    $this->attach($this, $attachNotifier); 
  }  

  /*
   * Function that is activated when NOTIFIER_ADMIN_ZEN_REMOVE_PRODUCT is encountered as a notifier.
   */
  // NOTIFIER_ADMIN_ZEN_REMOVE_PRODUCT  //admin/includes/functions/general.php
  function updateNotifierAdminZenRemoveProduct(&$callingClass, $notifier, $paramsArray, & $product_id, & $ptc) {
    global $db;
    $db->Execute("delete from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "
                  where products_id = '" . (int)$product_id . "'");
  }
  
  //NOTIFIER_ADMIN_ZEN_REMOVE_ORDER //admin/includes/functions/general.php
  function updateNotifierAdminZenRemoveOrder(&$callingClass, $notifier, $paramsArray, & $order_id, & $restock) {
    global $db;
    if ($restock == 'on') {
      $order = $db->Execute("select products_id, products_quantity, products_prid
                             from " . TABLE_ORDERS_PRODUCTS . "
                             where orders_id = '" . (int)$order_id . "'");

      while (!$order->EOF) {
        // START SBA //restored db //mc12345678 update the SBA quantities based on order delete.

/*
 * Need to take the data collected above, (products_id to find the matching order record 
 * (attributes table that is left joined by the sba table and values not equal to null. 
 * Records that match are ones on which quantities can be affected.
 */

        $db->Execute("update " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "
                      set quantity = quantity + " . $order->fields['products_quantity'] . "
                      where products_id = '" . (int)$order->fields['products_id'] . "'
                      and stock_attributes in (select stock_attribute from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK . " where orders_id = '" . (int)$order_id . "' and products_prid = '" . $order->fields['products_prid'] . "')"
                );
        // End SBA modification.

        $order->MoveNext();
      }
    }

/* START STOCK BY ATTRIBUTES */
    $db->Execute("delete from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK . "
                  where orders_id = '" . (int)$order_id . "'");
/* END STOCK BY ATTRIBUTES */
    
  }
  
  // NOTIFIER_ADMIN_ZEN_DELETE_PRODUCTS_ATTRIBUTES //admin/includes/functions/general.php
  function updateNotifierAdminZenDeleteProductsAttributes (&$callingClass, $notifier, $paramsArray, & $delete_product_id){
    global $db;
    /* START STOCK BY ATTRIBUTES */
    $db->Execute("delete from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " where products_id = '" . (int)$delete_product_id . "'");
    /* END STOCK BY ATTRIBUTES */

  }
  
    /*
   * Function that is activated when NOTIFY_PACKINGSLIP_INLOOP is encountered as a notifier.
   */
  // NOTIFY_PACKINGSLIP_INLOOP  //admin/packingslip.php
  function updateNotifyPackingSlipInloop(&$callingClass, $notifier, $paramsArray = array(), & $prod_img = '') {
    global $db, $customid, $orders, $order, $slipInLoopAttribs;
    if( STOCK_SBA_DISPLAY_CUSTOMID == 'true'){

      $customid = array();
      $customid_data = array();
      $slipInLoopAttribs = array();
      
      $i = $paramsArray['i'];

      // Goal is to retrieve the customID for the product from the order history.    
      //Have the order ID, the products_id and the list of attributes to tie back to the customid logged (if the product is/was in SBA, otherwise, just return the model number.
      $customid_query = "Select opas.customid, opa.products_options_values_id as povid from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK . " opas, " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " opa, " . TABLE_ORDERS_PRODUCTS . " op WHERE opa.orders_id = opas.orders_id AND opa.orders_products_id = opas.orders_products_id AND opa.orders_products_attributes_id = opas.orders_products_attributes_id AND op.orders_products_id = opa.orders_products_id AND opas.orders_id = :orders_id: AND op.products_id = :products_id:";
      $customid_query = $db->bindVars($customid_query, ':orders_id:', $orders->fields['orders_id'], 'integer');
      $customid_query = $db->bindVars($customid_query, ':products_id:', $order->products[$i]['id'], 'integer');
      $customid_data = $db->Execute($customid_query);

      while (!$customid_data->EOF) {

        $customid[$customid_data->fields['povid']] = PWA_CUSTOMID_NAME . $customid_data->fields['customid'];
        $customid_data->MoveNext();
      }
    }
  }

  // NOTIFY_PACKINGSLIP_IN_ATTRIB_LOOP
  function updateNotifyPackingslipInAttribLoop(&$callingClass, $notifier, $paramsArray = array(), &$prod_img = NULL) { 
    global $customid, $products_with_attributes_stock_class, $order, $slipInLoopAttribs;
    
    $i = $paramsArray['i'];
    $j = $paramsArray['j'];
    $prod_img = $paramsArray['prod_img'];
    
    $customid = null;
      //test if this is to be displayed
    if( STOCK_SBA_DISPLAY_CUSTOMID == 'true'){
//      $attributes = array(); // mc12345678 moved into if statement otherwise doesn't apply in code.
      //create array for use in zen_get_customid
      $slipInLoopAttribs[] = $order->products[$i]['attributes'][$j]['value_id'];
      //get custom ID
      $customid = $products_with_attributes_stock_class->zen_get_customid($order->products[$i]['id'],$slipInLoopAttribs);
      //only display custom ID if exists
      if( !empty($customid) ){
        //add name prefix (this is set in the admin language file)
        $customid = PWA_CUSTOMID_NAME . $customid;
      }
    }
  }

  // NOTIFY_ADMIN_PRODUCT_COPY_TO_ATTRIBUTES
  function updateNotifyAdminProductCopyToAttributes(&$callingClass, $notifier, $pInfo, &$contents) {
    // Obtain the last row within the array that contains the "divider" then replace with the below code and then add the divider
    //  back so that all attribute action is in one area instead of "separate" areas.
    global $products_with_attributes_stock_class; //, $pInfo;
    
    if (file_exists(DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/product_sba.php')) {
      include DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/product_sba.php'; 
    } else {
      include DIR_WS_LANGUAGES . 'english' . '/modules/product_sba.php';
    }
    
    if ($products_with_attributes_stock_class->zen_product_is_sba($pInfo->products_id)){
      $last_content = array();
      // Remove last item from the $contents array (assumes that the divider line has been added, value of 1 represents how many to remove)
      for ($i = 0; $i < 1; $i++) {
        $last_content[] = array_pop($contents);
      }
      //$last_content = $contents[count($contents) - 2];
      //$contents[count($contents) - 2] = array('text' => '<br />' . TEXT_COPY_SBA_ATTRIBUTES . '<br />' . zen_draw_radio_field('copy_sba_attributes', 'copy_sba_attributes_yes', true) . ' ' . TEXT_COPY_SBA_ATTRIBUTES_YES . '<br />' . zen_draw_radio_field('copy_sba_attributes', 'copy_sba_attributes_no') . ' ' . TEXT_COPY_SBA_ATTRIBUTES_NO);
      //$contents[] = $last_content;
      $contents[] = array('text' => '<br />' . TEXT_COPY_SBA_ATTRIBUTES . '<br />' . zen_draw_radio_field('copy_sba_attributes', 'copy_sba_attributes_yes', true) . ' ' . TEXT_COPY_SBA_ATTRIBUTES_YES . '<br />' . zen_draw_radio_field('copy_sba_attributes', 'copy_sba_attributes_no') . ' ' . TEXT_COPY_SBA_ATTRIBUTES_NO);
      // Re-add the removed $contents item(s).
      while (!empty($last_content)) {
        $contents[] = array_pop($last_content);
      }
    }
  }
  
  // NOTIFY_ATTRIBUTE_CONTROLLER_DELETE_ATTRIBUTE
  function updateNotifyAttributeControllerDeleteAttribute(&$callingClass, $notifier, $paramsArray, &$attribute_id) {
    global $db;
    
    $stock_ids = zen_get_sba_ids_from_attribute($attribute_id);

    if (!empty($stock_ids) && is_array($stock_ids)) {
      $db->Execute("delete from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " 
           where stock_id in (" . implode(',', $stock_ids) . ")");
    }

  }
  
  // NOTIFY_ATTRIBUTE_CONTROLLER_DELETE_ALL', array('pID' => $_POST['products_filter']));
  function updateNotifyAttributeControllerDeleteAll(&$callingClass, $notifier, $paramsArray) {
    // , array('pID' => $_POST['products_filter']));
    
    global $db;
    
    $pID = $paramsArray['pID'];

    $db->Execute("DELETE IGNORE FROM " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " 
         WHERE products_id = " . (int)$pID);
    
  }
  
  // 'NOTIFY_ATTRIBUTE_CONTROLLER_DELETE_OPTION_NAME_VALUES', array('pID' => $_POST['products_filter'], 'options_id' => $_POST['products_options_id_all']));
  function updateNotifyAttributeControllerDeleteOptionNameValues(&$callingClass, $notifier, $paramsArray) {
    //  array('pID' => $_POST['products_filter'], 'options_id' => $_POST['products_options_id_all'])

    global $db;
    
    $pID = $paramsArray['pID'];
    $options_id = $paramsArray['options_id'];
    
    $delete_attributes_options_id = $db->Execute("select * from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_id='" . $pID . "' and options_id='" . $options_id . "'");

    while (!$delete_attributes_options_id->EOF) {
      $stock_ids = zen_get_sba_ids_from_attribute($delete_attributes_options_id->fields['products_attributes_id']);
    
    if (!empty($stock_ids) && is_array($stock_ids)) {
        $delete_attributes_stock_options_id_values = $db->Execute("delete from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " where products_id='" . $pID . "' and stock_id in (" . implode(',', $stock_ids) . ")");
      }
      $delete_attributes_options_id->MoveNext();
    }

  }

  // NOTIFY_MODULES_COPY_TO_CONFIRM_ATTRIBUTES
  function updateNotifyModulesCopyToConfirmAttributes(&$callingClass, $notifier, $paramsArray) {

/*    if ( $_POST['copy_sba_attributes']=='copy_sba_attributes_yes' and $_POST['copy_as'] == 'duplicate' ) {
      global $products_with_attributes_stock_class;

      $products_id_from = $paramsArray['products_id_from'];
      $products_id_to = $paramsArray['products_id_to'];

      $products_with_attributes_stock_class->zen_copy_sba_products_attributes($products_id_from, $products_id_to);
    }*/
  }
  
  // OPTIONS_NAME_MANAGER_DELETE_OPTION', array('option_id' => $option_id, 'options_values_id' => (int)$remove_option_values->fields['products_options_values_id']));
  function updateOptionsNameManagerDeleteOption(&$callingClass, $notifier, $paramsArray) {
    //array('option_id' => $option_id, 'options_values_id' => (int)$remove_option_values->fields['products_options_values_id']));
    
    global $db;
    
    $option_id = $paramsArray['option_id'];
    $options_values_id = $paramsArray['options_values_id'];
    
    $remove_attributes_query = $db->Execute("select products_attributes_id from " . TABLE_PRODUCTS_ATTRIBUTES . " where options_id = " . (int)$option_id . " and options_values_id = " . (int)$options_values_id);
    unset($option_id);
    unset($options_values_id);

    while (!$remove_attributes_query->EOF) {
      $remove_attributes_list[] = $remove_attributes_query->fields['products_attributes_id'];
      $remove_attributes_query->MoveNext();
    }
    unset($remove_attributes_query);

    $stock_ids = zen_get_sba_ids_from_attribute($remove_attributes_list);
    unset($remove_attributes_list);

    if (!empty($stock_ids) && is_array($stock_ids)) {
      $db->Execute("delete from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "
                    where stock_id in (" . implode(',', $stock_ids) . ")");
    }
    unset($stock_ids);
    
    
  }
  
  // OPTIONS_NAME_MANAGER_UPDATE_OPTIONS_VALUES_DELETE', array('products_id' => $all_update_products->fields['products_id'], 'options_id' => $all_options_values->fields['products_options_id'], 'options_values_id' => $all_options_values->fields['products_options_values_id']));
  function updateOptionsNameManagerUpdateOptionsValuesDelete(&$callingClass, $notifier, $paramsArray) {
  // ', array('products_id' => $all_update_products->fields['products_id'], 'options_id' => $all_options_values->fields['products_options_id'], 'options_values_id' => $all_options_values->fields['products_options_values_id']));
    global $db;
    
    $products_id = $paramsArray['products_id'];
    $options_id = $paramsArray['options_id'];
    $options_values_id = $paramsArray['options_values_id'];
    
    $check_all_options_values = $db->Execute("select products_attributes_id from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_id='" . (int)$products_id . "' and options_id='" . (int)$options_id . "' and options_values_id='" . (int)$options_values_id . "'");
    unset($products_id);
    unset($options_id);
    unset($options_values_id);

    $stock_ids = zen_get_sba_ids_from_attribute($check_all_options_values->fields['products_attributes_id']);
    unset($check_all_options_values);
    if (!empty($stock_ids) && is_array($stock_ids)) {
      $db->Execute("delete from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "
                    where stock_id in (" . implode(',', $stock_ids) . ")");
    }

    unset($stock_ids);

  }
  
  // OPTIONS_VALUES_MANAGER_DELETE_VALUE', array('value_id' => $value_id));
  function updateOptionsValuesManagerDeleteValue(&$callingClass, $notifier, $paramsArray) {
  // ', array('value_id' => $value_id));
  
    global $db;
    
    $value_id = $paramsArray['value_id'];
    
    $remove_attributes_query = $db->Execute("select products_id, products_attributes_id, options_id, options_values_id from " . TABLE_PRODUCTS_ATTRIBUTES . " where options_values_id ='" . (int)$value_id . "'");
    unset($value_id);
    
    if ($remove_attributes_query->RecordCount() > 0) {
      // clean all tables of option value
      while (!$remove_attributes_query->EOF) {
        $stock_ids = zen_get_sba_ids_from_attribute($remove_attributes_query->fields['products_attributes_id']);
        
        if (!empty($stock_ids) && is_array($stock_ids)) {
          $db->Execute("delete from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "
                        where stock_id in (" . implode(',', $stock_ids) . ")");
        }

        unset($stock_ids);
        $remove_attributes_query->MoveNext();
      }
    }
    unset($remove_attributes_query);

  }
  
  // OPTIONS_VALUES_MANAGER_DELETE_VALUES_OF_OPTIONNAME', array('current_products_id' => $current_products_id, 'remove_ids' => $remove_downloads_ids, 'options_id'=>$options_id_from, 'options_values_id'=>$options_values_values_id_from));
  function updateOptionsValuesManagerDeleteValuesOfOptionname(&$callingClass, $notifier, $paramsArray) {
    // ', array('current_products_id' => $current_products_id, 'remove_ids' => $remove_downloads_ids, 'options_id'=>$options_id_from, 'options_values_id'=>$options_values_values_id_from));
    
    global $db;
    
    $remove_ids = $paramsArray['remove_ids'];
    
    $stock_ids = zen_get_sba_ids_from_attribute($remove_ids);
    unset($remove_ids);

    if (!empty($stock_ids) && is_array($stock_ids)) {
      $db->Execute("delete from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "
                    where stock_id in (" . implode(',', $stock_ids) . ")");
    }
    unset($stock_ids);
  }

  // ORDER_QUERY_ADMIN_COMPLETE
  function updateOrderQueryAdminComplete(&$orderClass, $notifier, $paramsArray) {
    global $db;
    
    $order_id = $paramsArray['orders_id'];
    
    //$orders_products_sba = $db->Execute("select orders_products_attributes_stock_id, orders_products_attributes_id, orders_products_id, stock_id, stock_attribute, customid, products_prid from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK . " where orders_id = " . (int)$order_id );
    
    $orders_products = $db->Execute("select orders_products_id, products_id
                                     from " . TABLE_ORDERS_PRODUCTS . "
                                     where orders_id = " . (int)$order_id . "
                                     order by orders_products_id");
    $index = 0;
    //$subindex = 0;
    
    while (!$orders_products->EOF) {                                     
    // Loop through each product in the order
      $product = $orderClass->products[$index];
      $customid_txt = '';
      $custom_multi = 'none';

      // If the product has attributes, then need to see what was logged into the orders_products_attributes_stock table.  
      //    If nothing then is a product that has attributes, but was not tracked by SBA. 
      //    If something, then retrieve the desired data (customid)
      if (!empty($product) && is_array($product) && array_key_exists('attributes', $product) && !empty($product['attributes']) && is_array($product['attributes'])) {
        $orders_products_sba_customid = $db->Execute("select 
                           opas.orders_products_attributes_stock_id, opas.orders_products_attributes_id, 
                           opas.stock_id, opas.stock_attribute, opas.customid, opas.products_prid, 
                           opa.products_options_id, opa.products_options_values_id
                           FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK . " opas LEFT JOIN " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " opa 
                             ON (opas.orders_products_attributes_id = opa.orders_products_attributes_id)
                           WHERE  
                             opas.orders_id = " . (int)$order_id . " 
                             AND opas.orders_products_id = " . (int)$orders_products->fields['orders_products_id'] . " 
                           ORDER BY opas.orders_products_attributes_stock_id");
      
        // If the product was tracked by SBA then perform desired work.
        if ($orders_products_sba_customid->RecordCount() > 0) {

          $customid = array();

          while (!$orders_products_sba_customid->EOF) {
            // provide the "list" of customid's such that only the unique populated customid's are provided (zen_not_null) and not previously accounted
            if (zen_not_null($orders_products_sba_customid->fields['customid']) || in_array($orders_products_sba_customid->fields['customid'], array('null', 'NULL', 0, ), true)) {
                if (!(in_array($orders_products_sba_customid->fields['customid'], $customid))) {
                  $customid[] = $orders_products_sba_customid->fields['customid'];
                  $custom_multi = 'multi';
                }
            } 
            // I don't like this next method to find the attributes, but am having difficulty doing anything else because of the way that attributes are
            //  "tagged" to the product.  There is no "guaranteed" location other than trying to find the option/value pair and equate it back to the
            //  order data. :/
              
            // Goal of this routine is to provide the individual customid for the specific attribute to be able to capture each individual customid for the
            //   attribute and to then also be able to capture the "total" customid for the product.
            foreach ($orderClass->products[$index]['attributes'] as $key => $value) {
              if ($value['option_id'] == $orders_products_sba_customid->fields['products_options_id']
                  && $value['value_id'] == $orders_products_sba_customid->fields['products_options_values_id']) {
                $orderClass->products[$index]['attributes'][$key]['customid'] = $orders_products_sba_customid->fields['customid'];
                break;
              }
            }

            $orders_products_sba_customid->MoveNext();
          }
          unset($orders_products_sba_customid);
          
          if (!empty($customid)) {
            // Combine the various customids to apply to the ordered product information.
            // Default method is to combine with a comma between each value when multiple exist.
            //   If every customid that is and is not present is to be concatenated then above need to add all to the array
            //    not just those that have data.
            $customid_txt = implode(", ", $customid);
            if (count($customid) == 1) {
              $custom_multi = 'single';
            }
          } // EOF if !empty
        } // EOF if orders_products_sba_customid->RecordCount() > 0
      } // EOF array check if attributes are involved.

      $orderClass->products[$index]['customid'] = array('type' => $custom_multi, 
                                                        'value' => $customid_txt,
                                                        );

      unset($product);

      $index++;
      $orders_products->MoveNext();
    } // EOF while loop on products
    unset($customid);
    unset($index);
    unset($order_id);
    unset($orders_products);
  }
  
//    $zco_notifier->notify ('EDIT_ORDERS_ADD_PRODUCT_STOCK_DECREMENT', array ( 'order_id' => $order_id, 'product' => $product ), $doStockDecrement);


//    $zco_notifier->notify ('EDIT_ORDERS_ADD_PRODUCT', array ( 'order_id' => (int)$order_id, 'orders_products_id' => $order_products_id, 'product' => $product ));
    function updateEditOrdersAddProduct(&$callingClass, $notifier, $paramsArray) {
        global $db, $order, $eo;

        $order_id = $paramsArray['order_id'];
        $orders_products_id = $paramsArray['orders_products_id'];
        $product = $paramsArray['product'];
/*
    These are the notifiers that are "duplicated" here to accomplish the same task as happens when 
      processing an order.
    
    $attachNotifier[] = 'NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM';
    $attachNotifier[] = 'NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT';
    $attachNotifier[] = 'NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_BEGIN';
    $attachNotifier[] = 'NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_END';

*/

//  function updateNotifyOrderProcessingStockDecrementInit(&$callingClass, $notifier, $paramsArray, & $productI, & $i) {
    //global $pwas_class;

//    $this->_i = $i;
      //$_productI = $product;
        $_orderIsSBA = $_SESSION['pwas_class2']->zen_product_is_sba($product['id']);
    
        if ($_orderIsSBA) { // Only take SBA action on SBA tracked product mc12345678 12-18-2015
            $_stock_info = $_SESSION['pwas_class2']->zen_get_sba_stock_attribute_info(zen_get_prid($product['id']), $product['attributes'], 'order'); // Sorted comma separated list of the attribute_id.

      // START "Stock by Attributes"
            $attributeList = null;
            $customid = null;
            if(!empty($product['attributes']) && is_array($product['attributes'])){
                foreach($product['attributes'] as $attributes){
                    $attributeList[] = $attributes['value_id'];
                }
                unset($attributes);
                $customid = $_SESSION['pwas_class2']->zen_get_customid($product['id'],$attributeList); // Expects that customid would be from a combination product, not individual attributes on a single product.  Should return an array if the values are individual or a single value if all attributes equal a single product.
                $product['customid'] = $customid;
//              $product['customid'] = $customid;
                unset($customid);
//      $productI['model'] = (zen_not_null($customid) ? $customid : $productI['model']);
//              $product['model'] = $product['model'];
            }
            $eo->eoLog(PHP_EOL . "admin-observer-pwas:" . PHP_EOL . "attributeList:" . PHP_EOL . var_export($attributeList,true) . PHP_EOL);
        }
        // END "Stock by Attributes"


//      $_stock_values = $product;

        if ($_orderIsSBA && isset($product) && is_array($product)) {
            // kuroi: Begin Stock by Attributes additions
            // added to update quantities of products with attributes
            // $stock_attributes_search = array();
            $attribute_stock_left = STOCK_REORDER_LEVEL + 1;  // kuroi: prevent false low stock triggers
            $_attribute_stock_left = $attribute_stock_left;

            // mc12345678 If the has attibutes then perform the following work.
            if(!empty($product['attributes']) && is_array($product['attributes'])){
                // Need to identify which records in the PWAS table need to be updated to remove stock from
                // them.  Ie. provide a list of attributes and get a list of stock_ids from pwas.
                // Then process that list of stock_ids to decrement based on their impact on stock.  This
                // all should be a consistent application.
                // mc12345678 Identify a list of attributes associated with the product
        
                $stock_attributes_search = $_SESSION['pwas_class2']->zen_get_sba_stock_attribute(zen_get_prid($product['id']), $product['attributes'], 'order');
        
                $eo->eoLog (PHP_EOL . "admin-observer-pwas:" . PHP_EOL . "stock_attributes_search:" . PHP_EOL . var_export($stock_attributes_search,true) . PHP_EOL);

                $stock_attributes_search_new = $_SESSION['pwas_class2']->zen_get_sba_attribute_info(zen_get_prid($product['id']), $product['attributes'], 'order', 'ids');

                $eo->eoLog (PHP_EOL . "admin-observer-pwas:" . PHP_EOL . "stock_attributes_search_new:" . PHP_EOL . var_export($stock_attributes_search_new,true) . PHP_EOL);

                if (isset($stock_attributes_search_new) && $stock_attributes_search_new === false) {
        
                } elseif (isset($stock_attributes_search_new) && (!zen_not_null($stock_attributes_search_new) || is_array($stock_attributes_search_new) && count($stock_attributes_search_new) == 0)) {
                } elseif (isset($stock_attributes_search_new) && (zen_not_null($stock_attributes_search_new) || (is_array($stock_attributes_search_new) && count($stock_attributes_search_new) > 0))) {
                    foreach ($stock_attributes_search_new as $stock_id) {
                        // @todo: address in PWAS table whether particular variant should be altered with stock quantities.
                        $get_quantity_query = 'SELECT quantity from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id=' . zen_get_prid($product['id']) . ' and stock_id=' . (int)$stock_id;
                        $attribute_stock_available = $db->Execute($get_quantity_query, false, false, 0, true);
                        if (true) { // Goal here is to identify if the particular attribute/stock item should be affected by a stock change.  If it is not, then this should be false or not performed.
                            $attribute_stock_left_test = $attribute_stock_available->fields['quantity'] - $product['qty'];
                            $attribute_update_query = 'UPDATE ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' SET quantity="'.$attribute_stock_left_test.'" where products_id=' . zen_get_prid($product['id']) . ' and stock_id=' . (int)$stock_id;
                            $db->Execute($attribute_update_query, false, false, 0, true);
                            unset($attribute_update_query);
                    
                            if ($attribute_stock_left_test < $attribute_stock_left) {
                                $_attribute_stock_left = min($attribute_stock_left_test, $_attribute_stock_left);
                                $attribute_stock_left = $_attribute_stock_left;
                            }
                        }
                    }
                    unset($stock_id, $attribute_stock_available, $attribute_stock_left_test);
                }
                unset($stock_attributes_search_new);
            }
            $attribute_stock_left = $_attribute_stock_left;
        }
        $eo->eoLog (PHP_EOL . "admin-observer-pwas:" . PHP_EOL . "stock_left:" . PHP_EOL . (isset($attribute_stock_left) ? var_export($attribute_stock_left,true) : 'no-data') . PHP_EOL);

//    function updateNotifyOrderProcessingStockDecrementEnd(&$callingClass, $notifier, $paramsArray) {
        //Need to modify the email that is going out regarding low-stock.
        //paramsArray is $i at time of development.
        if ($_orderIsSBA /*zen_product_is_sba($this->_productI['id'])*/) { // Only take SBA action on SBA tracked product mc12345678 12-18-2015
            if (/*$callingClass->email_low_stock == '' && */$callingClass->doStockDecrement &&  isset($product) && is_array($product) && $_attribute_stock_left <= STOCK_REORDER_LEVEL) {
                // kuroi: trigger and details for attribute low stock email
                $callingClass->email_low_stock .=  'ID# ' . zen_get_prid($product['id']) . ', model# ' . $product['model'] . ', customid ' . $product['customid'] . ', name ' . $product['name'] . ', ';
                foreach($product['attributes'] as $attributes){
                    $callingClass->email_low_stock .= $attributes['option'] . ': ' . $attributes['value'] . ', ';
                }
                unset($attributes);
        
                $callingClass->email_low_stock .= 'Stock: ' . $_attribute_stock_left . "\n\n";


                $messageStack->add_session(WARNING_ORDER_QTY_OVER_MAX, 'warning');

                // kuroi: End Stock by Attribute additions
            }
        }





        // NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM
        /* First check to see if SBA is installed and if it is then look to see if a value is 
         *  supplied in the stock_id parameter (which should only be populated when a SBA tracked
         *  item is in the order */
        if ($_orderIsSBA && defined('TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK') && zen_not_null($_stock_info['stock_id'])) {  
            //Need to validate that order had attributes in it.  If so, then were they tracked by SBA and then add to appropriate table.
/*          `orders_products_attributes_stock_id` INT(11) NOT NULL auto_increment, 
  `orders_products_attributes_id` INT(11) NOT NULL default '0',
  `orders_id` INT(11) NOT NULL default '0', 
  `orders_products_id` INT(11) NOT NULL default '0', 
  `stock_id` INT(11) NOT NULL default '0', 
  `stock_attribute` VARCHAR(255) NULL DEFAULT NULL, 
  `products_prid` TINYTEXT NOT NULL, */
            //$new_attrs = array();
//            $new_attrs = $_productI['attributes'];
            $new_attrs = $product['attributes'];
            $order_product_attribute_id = array();
            foreach ($new_attrs as $key=>$attr) {
                unset($new_attrs[$key]['value']);
                $orders_products_attribute_id_query =
            "SELECT orders_products_attributes_id FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " opa WHERE orders_id = "
                . $order_id . " AND orders_products_id = " . $orders_products_id . " AND products_options_id = " . $attr['option_id']
                . " AND products_options_values_id = " . $attr['value_id'];
                $orders_products_attribute_id = $db->Execute($orders_products_attribute_id_query, false, false, 0, true);

                while (!$orders_products_attribute_id->EOF) {
                    $order_product_attribute_id[] = $orders_products_attribute_id->fields['orders_products_attributes_id'];
                    $orders_products_attribute_id->MoveNext();  
                }
                unset($orders_products_attribute_id);
            }
            unset($key);
            unset($attr);
        
//   @TODO XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
   // NEED TO LOOKUP 'stock_id', 'stock_attribute' and 'customid' which should be created above as part of _stock_info and _productI
            foreach ($order_product_attribute_id as $key=>$opai) {
                $sql_data_array = array('orders_products_attributes_id' =>$opai,
                                        'orders_id' =>$order_id, 
                                        'orders_products_id' =>$orders_products_id, 
                                        'stock_id' => $_stock_info['stock_id'], 
                                        'stock_attribute' => $_stock_info['stock_attribute'], 
                                        'customid' => $product['customid'],
                                        'products_prid' =>zen_get_uprid($product['id'], $new_attrs[$key]));
                zen_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK, $sql_data_array); //inserts data into the TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK table.
            }
            unset($key);
            unset($opai);
            unset($order_product_attribute_id);
            unset($new_attrs);
            unset($sql_data_array);

        }
        unset($_orderIsSBA);
    }

//      'EDIT_ORDERS_REMOVE_PRODUCT_STOCK_DECREMENT'
//      $zco_notifier->notify ('EDIT_ORDERS_REMOVE_PRODUCT_STOCK_DECREMENT', array ( 'order_id' => $order_id, 'orders_products_id' => $orders_products_id ), $doStockDecrement);
//    function updateEditOrdersRemoveProductStockDecrement()

//    $zco_notifier->notify ('EDIT_ORDERS_REMOVE_PRODUCT', array ( 'order_id' => (int)$order_id, 'orders_products_id' => (int)$orders_products_id ));
    function updateEditOrdersRemoveProduct(&$callingClass, $notifier, $paramsArray) {
        global $db;

        $order_id = (int)$paramsArray['order_id'];
        $orders_products_id = (int)$paramsArray['orders_products_id'];

    
        $order = $db->Execute("select products_id, products_quantity, products_prid
                               from " . TABLE_ORDERS_PRODUCTS . "
                               where orders_id = " . (int)$order_id . "
                               AND orders_products_id = ". (int)$orders_products_id, false, false, 0, true);

        $_orderIsSBA = $_SESSION['pwas_class2']->zen_product_is_sba($order->fields['products_id']);

        while (!$order->EOF) {
      // START SBA //restored db //mc12345678 update the SBA quantities based on order delete.
/*
 * Need to take the data collected above, (products_id) to find the matching order record 
 * (attributes table that is left joined by the sba table and values not equal to null. 
 * Records that match are ones on which quantities can be affected.
 */
            $_orderIsSBA = $_SESSION['pwas_class2']->zen_product_is_sba($order->fields['products_id']);
      
            if ($_orderIsSBA) {
                $order_product_attributes_id_query = "SELECT orders_products_attributes_id, products_options_id, 
                                        products_options_values_id FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " opa 
                                 WHERE orders_id = " . (int)$order_id . " 
                                 AND orders_products_id = " . (int)$orders_products_id;
                $order_product_attributes_id = $db->Execute($order_product_attributes_id_query, false, false, 0, true);
                unset($order_product_attributes_id_query);

                $attr = array();
          
                while (!$order_product_attributes_id->EOF) {
                    $attr[] = array('option_id' =>$order_product_attributes_id->fields['products_options_id'],
                                    'value_id' => $order_product_attributes_id->fields['products_options_values_id']);

                    $order_product_attributes_id->MoveNext();        
                }
                unset($order_product_attributes_id);
      
                $stockids = $_SESSION['pwas_class2']->zen_get_sba_attribute_info($order->fields['products_id'], $attr, 'order', 'ids');
                unset($attr);
      
                if (isset($stockids) && $stockids === false) {
                    /* There is no stock associated with this sequence so ignore it. */
                } elseif (isset($stockids) && (!zen_not_null($stockids) || is_array($stockids) && count($stockids) == 0)) {
                    /*  The ids came back as null or as an empty array. Nothing to be done. */
                } elseif (isset($stockids) && (zen_not_null($stockids) || (is_array($stockids) && count($stockids) > 0))) {
                    foreach ($stockids as $stock_id) {
                    // @todo: address in PWAS table whether particular variant should be altered with stock quantities.
                        $get_quantity_query = 'SELECT quantity from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id=' . zen_get_prid($order->fields['products_id']) . ' and stock_id=' . (int)$stock_id;
                        $attribute_stock_available = $db->Execute($get_quantity_query, false, false, 0, true);
                        unset($get_quantity_query);
                      
                        if (true) { // Goal here is to identify if the particular attribute/stock item should be affected by a stock change.  If it is not, then this should be false or not performed.
                            $attribute_stock_left_test = $attribute_stock_available->fields['quantity'] + $order->fields['products_quantity'];
                            $attribute_update_query = 'UPDATE ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' SET quantity=' . $attribute_stock_left_test . ' where products_id=' . zen_get_prid($order->fields['products_id']) . ' and stock_id=' . (int)$stock_id;
                            $db->Execute($attribute_update_query, false, false, 0, true);
                            unset($attribute_update_query);
                            unset($attribute_stock_left_test);
                        }
                        unset($attribute_stock_available);
                    }
                    unset($stock_id);
                }
                unset($stockids);

            // End SBA modification.
            }
            $order->MoveNext();
        }
        unset($order);

        if ($_orderIsSBA) {
/* START STOCK BY ATTRIBUTES */
            $db->Execute("delete from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK . "
                          where orders_id = '" . (int)$order_id . "'
                          AND orders_products_id = " . (int)$orders_products_id);
/* END STOCK BY ATTRIBUTES */
        }
  
        unset($_orderIsSBA);
        unset($order_id);
        unset($orders_products_id);
    }
    
//  notify('NOTIFY_PACKINGSLIP_IN_ATTRIB_LOOP', array('i'=>$i, 'j'=>$j, 'productsI'=>$order->products[$i], 'prod_img'=>$prod_img), $order->products[$i], $prod_img);

//    $this->notify('NOTIFY_EO_GET_PRODUCTS_STOCK', $products_id, $stock_quantity, $stock_handled);
  // function updateNotifyEOGetProductsStock

  function update(&$callingClass, $notifier, $paramsArray) {

    // Duplicate of updateNotifierAdminZenDeleteProductsAttributes
    if ($notifier == 'NOTIFIER_ADMIN_ZEN_DELETE_PRODUCTS_ATTRIBUTES '){
      //admin/includes/functions/general.php
      $delete_product_id = $paramsArray['delete_product_id'];
      $this->updateNotifierAdminZenDeleteProductsAttributes($callingClass, $notifier, $paramsArray, $delete_product_id);
    }
    
    // Duplicate of updateNotifierAdminZenRemoveOrder
    if ($notifier == 'NOTIFIER_ADMIN_ZEN_REMOVE_ORDER'){  
      //admin/includes/functions/general.php
      $restock = $paramsArray['restock'];
      $order_id = $paramsArray['order_id'];
      
      $this->updateNotifierAdminZenRemoveOrder($callingClass, $notifier, $paramsArray, $order_id, $restock);
    }
  
    // Duplicate of updateNotifierAdminZenRemoveProduct
    if ($notifier == 'NOTIFIER_ADMIN_ZEN_REMOVE_PRODUCT'){
      //admin/includes/functions/general.php
      $product_id = $paramsArray['product_id']; //=>$product_id
      $ptc = $paramsArray['ptc'];
      $this->updateNotifierAdminZenRemoveProduct($callingClass, $notifier, $paramsArray, $product_id, $ptc);
    }

    if ($notifier == 'NOTIFY_ADMIN_PRODUCT_COPY_TO_ATTRIBUTES'){
      global $contents;
      $this->updateNotifyAdminProductCopyToAttributes($callingClass, $notifier, $paramsArray, $contents);
    }
    
    if ($notifier == 'NOTIFY_ATTRIBUTE_CONTROLLER_DELETE_ATTRIBUTE'){
      $attribute_id = $paramsArray['attribute_id'];
      $this->updateNotifyAttributeControllerDeleteAttribute($callingClass, $notifier, $paramsArray, $attribute_id);
    }
    
    if ($notifier == 'NOTIFY_ATTRIBUTE_CONTROLLER_DELETE_ALL') {
      $this->updateNotifyAttributeControllerDeleteAll($callingClass, $notifier, $paramsArray);
    }
    
    if ($notifier == 'NOTIFY_ATTRIBUTE_CONTROLLER_DELETE_OPTION_NAME_VALUES') {
    //, array('pID' => $_POST['products_filter'], 'options_id' => $_POST['products_options_id_all']));
      $this->updateNotifyAttributeControllerDeleteOptionNameValues($callingClass, $notifier, $paramsArray);
    }

    if ($notifier == 'NOTIFY_MODULES_COPY_TO_CONFIRM_ATTRIBUTES') {
      $this->updateNotifyModulesCopyToConfirmAttributes($callingClass, $notifier, $paramsArray);
    }
    
    if ($notifier == 'OPTIONS_NAME_MANAGER_DELETE_OPTION') {
    //, array('option_id' => $option_id, 'options_values_id' => (int)$remove_option_values->fields['products_options_values_id']));
      $this->updateOptionsNameManagerDeleteOption($callingClass, $notifier, $paramsArray);
    }
    
    if ($notifier == 'OPTIONS_NAME_MANAGER_UPDATE_OPTIONS_VALUES_DELETE') {
    // , array('products_id' => $all_update_products->fields['products_id'], 'options_id' => $all_options_values->fields['products_options_id'], 'options_values_id' => $all_options_values->fields['products_options_values_id']));
      $this->updateOptionsNameManagerUpdateOptionsValuesDelete($callingClass, $notifier, $paramsArray);
    }
    
    if ($notifier == 'OPTIONS_VALUES_MANAGER_DELETE_VALUES_OF_OPTIONNAME') {
      //, array('current_products_id' => $current_products_id, 'remove_ids' => $remove_downloads_ids, 'options_id'=>$options_id_from, 'options_values_id'=>$options_values_values_id_from));
      $this->updateOptionsValuesManagerDeleteValuesOfOptionname($callingClass, $notifier, $paramsArray);
    }
    
    if ($notifier == 'ORDER_QUERY_ADMIN_COMPLETE') {
      $this->updateOrderQueryAdminComplete($callingClass, $notifier, $paramsArray);
    }
    
    if ($notifier == 'EDIT_ORDERS_REMOVE_PRODUCT') {
      $this->updateEditOrdersRemoveProduct($callingClass, $notifier, $paramsArray);
    }

    if ($notifier == 'EDIT_ORDERS_ADD_PRODUCT') {
//    $zco_notifier->notify ('EDIT_ORDERS_ADD_PRODUCT', array ( 'order_id' => (int)$order_id, 'orders_products_id' => $order_products_id, 'product' => $product ));
      $this->updateEditOrdersAddProduct($callingClass, $notifier, $paramsArray);
    }

  } //end update function - mc12345678
} // EOF Class
