<?php


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
      $order = $db->Execute("select products_id, products_quantity
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
                      and stock_attributes in (select stock_attribute from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK . " where orders_id = '" . (int)$order_id . "' and products_prid = '" . (int)$order->fields['products_prid'] . "')"
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

  function update(&$callingClass, $notifier, $paramsArray) {
    global $db;
    
    // Duplicate of updateNotifierAdminZenDeleteProductsAttributes
    if ($notifier == 'NOTIFIER_ADMIN_ZEN_DELETE_PRODUCTS_ATTRIBUTES '){
      //admin/includes/functions/general.php
      $delete_product_id = $paramsArray['delete_product_id'];
      updateNotifierAdminZenDeleteProductsAttributes($callingClass, $notifier, $paramsArray, $delete_product_id);
      /* START STOCK BY ATTRIBUTES */
//      $db->Execute("delete from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " where products_id = '" . (int)$delete_product_id . "'");
      /* END STOCK BY ATTRIBUTES */
    }
    
    // Duplicate of updateNotifierAdminZenRemoveOrder
    if ($notifier == 'NOTIFIER_ADMIN_ZEN_REMOVE_ORDER'){  
      //admin/includes/functions/general.php
      $restock = $paramsArray['restock'];
      $order_id = $paramsArray['order_id'];
      
      updateNotifierAdminZenRemoveOrder($callingClass, $notifier, $paramsArray, $order_id, $restock);
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
      updateNotifierAdminZenRemoveProduct($callingClass, $notifier, $paramsArray, $product_id);
/*      $db->Execute("delete from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "
                  where products_id = '" . (int)$product_id . "'");*/
    }
	} //end update function - mc12345678
}