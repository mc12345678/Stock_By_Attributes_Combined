<?php

/**
 * Autoloader array for products with attributes stock (SBA) functionality. Makes sure that products with attributes stock related controls are instantiated at the
 * right point of the Zen Cart initsystem.
 * 
 * @package     products_with_attributes_stock
 * @author      mc12345678 
 * @copyright   Copyright 2008-2016 mc12345678
 * @copyright   Copyright 2003-2007 Zen Cart Development Team
 * @copyright   Portions Copyright 2003 osCommerce
 * @link        http://www.zen-cart.com/
 * @license     http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version     $Id: config.eo_sba.php xxxx 2016-11-14 20:31:10Z mc12345678 $
 *
 * Stock by Attributes 1.5.4
 */

/* $autoLoadConfig[0][] = array(
  'autoType' => 'class',
  'loadFile' => 'class.products_with_attributes_shopping_cart.php'
  );*/
  /*$autoLoadConfig[79][] = array(
    'autoType'=>'classInstantiate',
    'className'=>'products_with_attributes_shopping_cart',
    'objectName'=>'cart',
    'checkInstantiated'=>false,
    'classSession'=>true
  );*/ 
  $autoLoadConfig[180][] = array(
    'autoType'=>'init_script',
    'loadFile'=>'init_eo_sba.php'
/*    'loadFile'=>DIR_FS_ADMIN . DIR_WS_INCLUDES . 'edit_orders_sba.php'
    'className'=>'products_with_attributes_shopping_cart',
    'objectName'=>'cart',
    'checkInstantiated'=>false,
    'classSession'=>true*/
  ); 
