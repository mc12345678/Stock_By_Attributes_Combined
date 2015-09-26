<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce                                       |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 The zen-cart developers                           |
// |                                                                      |
// | http://www.zen-cart.com/index.php                                    |
// |                                                                      |
// | Portions Copyright (c) 2003 osCommerce                               |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.zen-cart.com/license/2_0.txt.                             |
// | If you did not receive a copy of the zen-cart license and are unable |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@zen-cart.com so we can mail you a copy immediately.          |
// +----------------------------------------------------------------------+
//  $Id: products_with_attributes_stock.php xxxx 2015-05-24 22:05:35Z mc12345678 $
//
 
  /* START STOCK BY ATTRIBUTES */ //This can be placed outside of the general.php file and should be...
function return_attribute_combinations($arrMain, $intVars, $currentLoop = array(), $currentIntVar = 0) {
  $arrNew = array();

  for ($currentLoop[$currentIntVar] = 0; $currentLoop[$currentIntVar] < sizeof($arrMain[$currentIntVar]); $currentLoop[$currentIntVar]++) {
    if ($intVars == $currentIntVar + 1) {
      $arrNew2 = array();
      for ($i = 0; $i<$intVars;$i++) {
        $arrNew2[] = $arrMain[$i][$currentLoop[$i]];  // This is a place where an evaluation could be made to do something unique with a single attribute that is to be assigned to a sba variant as this assigment is for one of the attributes to be combined for one record. If the goal would be not to add anything having this one attribute, then could call continue 2 to escape this for loop and move on to the next outer for loop.  If just want to not add the one attribute to the combination then place the above assignment so that it is bypassed when not to be added. 
      }
      if (zen_not_null($arrNew2) && sizeof($arrNew2) > 0) { // This is a place where an evaluation could be made to do something unique with a sba variant as this assigment is one of all attributes combined for one record.  //Still something about this test doesn't seem quite right, but it's the concept that is important, as long as there is something to evaluate/assign that is not nothing, then do the assignment.
        $arrNew[] = $arrNew2;
      }
    } else {
      $arrNew = array_merge($arrNew, return_attribute_combinations($arrMain, $intVars, $currentLoop, $currentIntVar + 1));
    }
  }

  return $arrNew;
}
  /* END STOCK BY ATTRIBUTES */

  //This function should be moved to its own file location rather than general.php so that the changes made to this file can be minimized to simply adding notifiers...
  function zen_get_sba_ids_from_attribute($products_attributes_id = array()){
    global $db;
    
    if (!is_array($products_attributes_id)){
      $products_attributes_id = array($products_attributes_id);
    }
    $products_stock_attributes = $db->Execute("select stock_id, stock_attributes from " . 
                                              TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK);
//    while (!$products_stock_attributes->EOF) {
//      $products_stock_attributes_list[] = $products_stock_attributes->fields['stock_attributes'];
//    }
    $stock_id_list = array();
    /* The below "search" is one reason that the original tables for SBA should be better refined
     * and not use comma separated items in a field...
     */
    while (!$products_stock_attributes->EOF) {
      //$stock_attrib_list = array();
      $stock_attrib_list = explode(',', $products_stock_attributes->fields['stock_attributes']);

      foreach($stock_attrib_list as $stock_attrib){
        if (in_array($stock_attrib, $products_attributes_id)) {
          $stock_id_list[] = $products_stock_attributes->fields['stock_id'];
          continue;
        }
      }
      
      $products_stock_attributes->MoveNext();
    }
    return $stock_id_list;
  }  
