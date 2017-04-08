<?php
/**
 * @package attrib for ajax
 * @copyright Copyright 2006 rainer langheiter, http://edv.langheiter.com
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: products_with_attributes_stock_ajax.php 389 2008-11-14 16:02:14Z hugo13 $
 * 
 * Stock by Attributes 1.5.2
 */
 
require('includes/application_top.php');
require(DIR_WS_CLASSES . 'currencies.php');
  //require(DIR_WS_CLASSES . 'products_with_attributes_stock.php'); 
  // Attempt to load the admin's specific language version of the file, but if unable to find it then default to english instead of displaying constants.
  if (file_exists(DIR_WS_LANGUAGES . $_SESSION['language'] . '/products_with_attributes_stock.php')) { 
    include(DIR_WS_LANGUAGES . $_SESSION['language'] . '/products_with_attributes_stock.php');
  } else {
    include(DIR_WS_LANGUAGES . 'english/products_with_attributes_stock.php');
  }

//$stock = new products_with_attributes_stock;
$stock = $products_with_attributes_stock_class;

    if( isset($_GET['save']) && $_GET['save'] == 1 ){
    if (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) {
      $parameters = 'page=' . (int)$_GET['page'];
    } else {
      $parameters = '';
    }
        $x = $stock->saveAttrib();//This does not seem to have a purpose, need to look closer.
    if( isset($_GET['pid']) && is_numeric($_GET['pid']) && $_GET['pid'] > 0 ){
      zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'updateReturnedPID=' . (int)$_GET['pid'] . '&amp;' . $parameters, 'NONSSL'));
    }
    else{
       zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, $parameters, 'NONSSL'));
    }
    } else {
        $x = $stock->displayFilteredRows();
        print_r($x);
    }

/* Keep for reference only
function saveAttrib(){
    global $db;
    foreach ($_POST as $key => $value) {
        $id = intval(str_replace('stockid-', '', $key));
        if($id > 0){
            $sql = "UPDATE products_with_attributes_stock SET quantity = '$value' WHERE products_with_attributes_stock.stock_id =$id LIMIT 1";
            $db->execute($sql);
        }
    
    }
    return 'OK';  
}
*/
//eof
