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
  function products_with_attributes_stock_admin() {
//		global $zco_notifier;
    
    $attachNotifier = array();
    $attachNotifier[] = 'NOTIFIER_ADMIN_ZEN_REMOVE_PRODUCT';
    $attachNotifier[] = 'NOTIFIER_ADMIN_ZEN_REMOVE_ORDER';
    $attachNotifier[] = 'NOTIFIER_ADMIN_ZEN_DELETE_PRODUCTS_ATTRIBUTES';
    $attachNotifier[] = 'NOTIFY_PACKINGSLIP_INLOOP';
    $attachNotifier[] = 'NOTIFY_PACKINGSLIP_IN_ATTRIB_LOOP';

//    $zco_notifier->attach($this, $attachNotifier); 
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
      /* 	if (!zen_not_null($productsI)) {
        $productsI = $paramsArray['productsI'];
        } */

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
    global $customid, $stock, $order, $slipInLoopAttribs;
    
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
      $customid = $stock->zen_get_customid($order->products[$i]['id'],$slipInLoopAttribs);
      //only display custom ID if exists
      if( !empty($customid) ){
        //add name prefix (this is set in the admin language file)
        $customid = PWA_CUSTOMID_NAME . $customid;
      }
    }
  }
    
//  notify('NOTIFY_PACKINGSLIP_IN_ATTRIB_LOOP', array('i'=>$i, 'j'=>$j, 'productsI'=>$order->products[$i], 'prod_img'=>$prod_img), $order->products[$i], $prod_img);

  function update(&$callingClass, $notifier, $paramsArray) {
    global $db;
    
    // Duplicate of updateNotifierAdminZenDeleteProductsAttributes
    if ($notifier == 'NOTIFIER_ADMIN_ZEN_DELETE_PRODUCTS_ATTRIBUTES '){
      //admin/includes/functions/general.php
      $delete_product_id = $paramsArray['delete_product_id'];
      $this->updateNotifierAdminZenDeleteProductsAttributes($callingClass, $notifier, $paramsArray, $delete_product_id);
      /* START STOCK BY ATTRIBUTES */
//      $db->Execute("delete from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " where products_id = '" . (int)$delete_product_id . "'");
      /* END STOCK BY ATTRIBUTES */
    }
    
    // Duplicate of updateNotifierAdminZenRemoveOrder
    if ($notifier == 'NOTIFIER_ADMIN_ZEN_REMOVE_ORDER'){  
      //admin/includes/functions/general.php
      $restock = $paramsArray['restock'];
      $order_id = $paramsArray['order_id'];
      
      $this->updateNotifierAdminZenRemoveOrder($callingClass, $notifier, $paramsArray, $order_id, $restock);
/*      if ($restock == 'on') {
        $order = $db->Execute("select products_id, products_quantity
                               from " . TABLE_ORDERS_PRODUCTS . "
                               where orders_id = '" . (int)$order_id . "'");

        while (!$order->EOF) {
        */ // START SBA //restored db //mc12345678 update the SBA quantities based on order delete.

/*
 * Need to take the data collected above, (products_id to find the matching order record 
 * (attributes table that is left joined by the sba table and values not equal to null. 
 * Records that match are ones on which quantities can be affected.
 */

/*          $db->Execute("update " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "
                        set quantity = quantity + " . $order->fields['products_quantity'] . "
                        where products_id = '" . (int)$order->fields['products_id'] . "'
                        and stock_attributes in (select stock_attribute from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK . " where orders_id = '" . (int)$order_id . "' and products_prid = '" . (int)$order->fields['products_prid'] . "')"
                );
        // End SBA modification.

          $order->MoveNext();
        }
      }
*/
/* START STOCK BY ATTRIBUTES */
/*      $db->Execute("delete from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK . "
                  where orders_id = '" . (int)$order_id . "'");*/
/* END STOCK BY ATTRIBUTES */
    }
  
    // Duplicate of updateNotifierAdminZenRemoveProduct
    if ($notifier == 'NOTIFIER_ADMIN_ZEN_REMOVE_PRODUCT'){
      //admin/includes/functions/general.php
      $product_id = $paramsArray['product_id']; //=>$product_id
      $this->updateNotifierAdminZenRemoveProduct($callingClass, $notifier, $paramsArray, $product_id);
/*      $db->Execute("delete from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "
                  where products_id = '" . (int)$product_id . "'");*/
    }
	} //end update function - mc12345678
} // EOF Class