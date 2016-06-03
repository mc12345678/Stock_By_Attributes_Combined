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
    $attachNotifier[] = 'OPTIONS_NAME_MANAGER_DELETE_OPTION';
    $attachNotifier[] = 'OPTIONS_NAME_MANAGER_UPDATE_OPTIONS_VALUES_DELETE';
    $attachNotifier[] = 'OPTIONS_VALUES_MANAGER_DELETE_VALUE';
    $attachNotifier[] = 'OPTIONS_VALUES_MANAGER_DELETE_VALUES_OF_OPTIONNAME';
    //$attachNotifier[] = 'ORDER_QUERY_ADMIN_COMPLETE';  // Not ready to implement yet. 2016-06-01

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
      $customid = $products_with_attributes_with_stock_class->zen_get_customid($order->products[$i]['id'],$slipInLoopAttribs);
      //only display custom ID if exists
      if( !empty($customid) ){
        //add name prefix (this is set in the admin language file)
        $customid = PWA_CUSTOMID_NAME . $customid;
      }
    }
  }
    
  // NOTIFY_ATTRIBUTE_CONTROLLER_DELETE_ATTRIBUTE
  function updateNotifyAttributeControllerDeleteAttribute(&$callingClass, $notifier, $paramsArray, &$attribute_id) {
    global $db;
    
    $stock_ids = zen_get_sba_ids_from_attribute($attribute_id);

    if (sizeof($stock_ids) > 0) {
      $db->Execute("delete from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " 
           where stock_id in (" . implode(',', $stock_ids) . ")");
    }

  }
  
  // NOTIFY_ATTRIBUTE_CONTROLLER_DELETE_ALL', array('pID' => $_POST['products_filter']));
  function updateNotifyAttributeControllerDeleteAll(&$callingClass, $notifier, $paramsArray) {
    // , array('pID' => $_POST['products_filter']));
    $pID = $paramsArray['pID'];
    
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
    
      if(sizeof($stock_ids) > 0) {
        $delete_attributes_stock_options_id_values = $db->Execute("delete from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " where products_id='" . $pID . "' and stock_id in (" . implode(',', $stock_ids) . ")");
      }
    }

  }
  
  // OPTIONS_NAME_MANAGER_DELETE_OPTION', array('option_id' => $option_id, 'options_values_id' => (int)$remove_option_values->fields['products_options_values_id']));
  function updateOptionsNameManagerDeleteOption(&$callingClass, $notifier, $paramsArray) {
    //array('option_id' => $option_id, 'options_values_id' => (int)$remove_option_values->fields['products_options_values_id']));
    $option_id = $paramsArray['option_id'];
    $options_values_id = $paramsArray['options_values_id'];
    
    $remove_attributes_query = $db->Execute("select products_attributes_id from " . TABLE_PRODUCTS_ATTRIBUTES . " where options_id = " . (int)$option_id . " and options_values_id = " . (int)$options_values_id);

    while (!$remove_attributes_query->EOF) {
      $remove_attributes_list[] = $remove_attributes_query->fields['products_attributes_id'];
      $remove_attributes_query->MoveNext();
    }

    $stock_ids = zen_get_sba_ids_from_attribute($remove_attributes_list);

    if (sizeof($stock_ids) > 0) {
      $db->Execute("delete from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "
                    where stock_id in (" . implode(',', $stock_ids) . ")");
    }
    
    
  }
  
  // OPTIONS_NAME_MANAGER_UPDATE_OPTIONS_VALUES_DELETE', array('products_id' => $all_update_products->fields['products_id'], 'options_id' => $all_options_values->fields['products_options_id'], 'options_values_id' => $all_options_values->fields['products_options_values_id']));
  function updateOptionsNameManagerUpdateOptionsValuesDelete(&$callingClass, $notifier, $paramsArray) {
  // ', array('products_id' => $all_update_products->fields['products_id'], 'options_id' => $all_options_values->fields['products_options_id'], 'options_values_id' => $all_options_values->fields['products_options_values_id']));
    global $db;
    
    $products_id = $paramsArray['products_id'];
    $options_id = $paramsArray['options_id'];
    $options_values_id = $paramsArray['options_values_id'];
    
    $check_all_options_values = $db->Execute("select products_attributes_id from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_id='" . (int)$products_id . "' and options_id='" . (int)$options_id . "' and options_values_id='" . (int)$options_values_id . "'");

    $stock_ids = zen_get_sba_ids_from_attribute($check_all_options_values->fields['products_attributes_id']);
    if (sizeof($stock_ids) > 0) {
      $db->Execute("delete from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "
                    where stock_id in (" . implode(',', $stock_ids) . ")");
    }

  }
  
  // OPTIONS_VALUES_MANAGER_DELETE_VALUE', array('value_id' => $value_id));
  function updateOptionsValuesManagerDeleteValue(&$callingClass, $notifier, $paramsArray) {
  // ', array('value_id' => $value_id));
    $value_id = $paramsArray['value_id'];
    
    $remove_attributes_query = $db->Execute("select products_id, products_attributes_id, options_id, options_values_id from " . TABLE_PRODUCTS_ATTRIBUTES . " where options_values_id ='" . (int)$value_id . "'");
    if ($remove_attributes_query->RecordCount() > 0) {
      // clean all tables of option value
      while (!$remove_attributes_query->EOF) {
        $stock_ids = zen_get_sba_ids_from_attribute($remove_attributes_query->fields['products_attributes_id']);
        
        if (sizeof($stock_ids) > 0) {
          $db->Execute("delete from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "
                        where stock_id in (" . implode(',', $stock_ids) . ")");
        }
      }
    }

  }
  
  // OPTIONS_VALUES_MANAGER_DELETE_VALUES_OF_OPTIONNAME', array('current_products_id' => $current_products_id, 'remove_ids' => $remove_downloads_ids, 'options_id'=>$options_id_from, 'options_values_id'=>$options_values_values_id_from));
  function updateOptionsValuesManagerDeleteValuesOfOptionname(&$callingClass, $notifier, $paramsArray) {
    // ', array('current_products_id' => $current_products_id, 'remove_ids' => $remove_downloads_ids, 'options_id'=>$options_id_from, 'options_values_id'=>$options_values_values_id_from));
    $remove_ids = $paramsArray['remove_ids'];
    
    $stock_ids = zen_get_sba_ids_from_attribute($remove_ids);

    if (sizeof($stock_ids) > 0) {
      $db->Execute("delete from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "
                    where stock_id in (" . implode(',', $stock_ids) . ")");
    }

  }

  // ORDER_QUERY_ADMIN_COMPLETE
  function updateOrderQueryAdminComplete(&$orderClass, $notifier, $paramsArray) {
    global $db, $products_with_attributes_stock_class;
    
    $order_id = $paramsArray['orders_id'];
    
    //$orders_products_sba = $db->Execute("select orders_products_attributes_stock_id, orders_products_attributes_id, orders_products_id, stock_id, stock_attribute, customid, products_prid from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK . " where orders_id = " . (int)$order_id );
    
    $orders_products = $db->Execute("select orders_products_id, products_id
                                     from " . TABLE_ORDERS_PRODUCTS . "
                                     where orders_id = " . (int)$order_id . "
                                     order by orders_products_id");
    $index = 0;
    //$subindex = 0;
    $customid = array();
    
    while (!$orders_products->EOF) {                                     
    // Loop through each product in the order
      $product = $orderClass->products[$index];
    
      // If the product has attributes, then need to see what was logged into the orders_products_attributes_stock table.  
      //    If nothing then is a product that has attributes, but was not tracked by SBA. 
      //    If something, then retrieve the desired data (customid)
      if (array_key_exists('attributes', $product) && sizeof($product['attributes']) > 0) {
        $orders_products_sba_customid = $db->Execute("select orders_products_attributes_stock_id, orders_products_attributes_id, stock_id, stock_attribute, customid, products_prid from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK . " where orders_id = " . (int)$order_id . " and orders_products_id = " . (int)$orders_products->fields['orders_products_id'] . " order by orders_products_attributes_stock_id");
      
        // If the product was tracked by SBA then perform desired work.
        if ($orders_products_sba_customid->RecordCount() > 0) {

          while (!$orders_products_sba_customid->EOF) {
            // provide the "list" of customid's such that only the populated customid's are provided (zen_not_null)
            if (zen_not_null($orders_products_sba_customid->fields['customid'])) {
              $customid[] = $orders_products_sba_customid->fields['customid'];
            } 
          }
          if (sizeof($customid) > 0) {
            // Combine the various customids to apply to the ordered product information.
            // Default method is to combine with a comma between each value when multiple exist.
            //   If every customid that is and is not present is to be concatenated then above need to add all to the array
            //    not just those that have data.
            $orderClass->products[$index]['customid'] = implode(", ", $customid);
          } // EOF if sizeof
        } // EOF if orders_products_sba_customid->RecordCount() > 0
      } // EOF array check if attributes are involved.
      $index++;
      $orders_products->MoveNext();
    } // EOF while loop on products
  }
  
    
//  notify('NOTIFY_PACKINGSLIP_IN_ATTRIB_LOOP', array('i'=>$i, 'j'=>$j, 'productsI'=>$order->products[$i], 'prod_img'=>$prod_img), $order->products[$i], $prod_img);

  function update(&$callingClass, $notifier, $paramsArray) {
    global $db;
    
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
      $this->updateNotifierAdminZenRemoveProduct($callingClass, $notifier, $paramsArray, $product_id);
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

	} //end update function - mc12345678
} // EOF Class
