<?php

/**
 * Autoloader array for products with attributes stock (SBA) functionality. Makes sure that products with attributes stock is instantiated at the
 * right point of the Zen Cart initsystem.
 * 
 * @package     products_with_attributes_stock
 * @author      mc12345678 
 * @copyright   Copyright 2013-2017 mc12345678
 * @copyright   Copyright 2003-2007 Zen Cart Development Team
 * @copyright   Portions Copyright 2003 osCommerce
 * @link        http://www.zen-cart.com/
 * @license     http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version     $Id: config.products_with_attributes_stock.php xxxx 2017-04-10 20:31:10Z mc12345678 $
 */

 $autoLoadConfig[0][] = array(
  'autoType' => 'class',
  'loadFile' => 'observers/class.products_with_attributes_stock.php',
  'classPath'=>DIR_WS_CLASSES
  );
 $autoLoadConfig[199][] = array(
  'autoType' => 'classInstantiate',
  'className' => 'products_with_attributes_stock_admin',
  'objectName' => 'products_with_attributes_stock_admin_observe'
  );
 $autoLoadConfig[0][] = array(
   'autoType' => 'class',
   'loadFile' => 'products_with_attributes_stock.php',
   'classPath'=> DIR_WS_CLASSES
 );
 $autoLoadConfig[199][] = array(
   'autoType' => 'classInstantiate',
   'className' => 'products_with_attributes_stock',
   'objectName' => 'products_with_attributes_stock_class'
 );
 $autoLoadConfig[0][] = array(
   'autoType' => 'class',
   'loadFile' => 'class.products_with_attributes_class_stock.php'
 );
 $autoLoadConfig[199][] = array(
  'autoType' => 'classInstantiate',
  'className' => 'products_with_attributes_class_stock',
  'objectName' => 'pwas_class2',
  'checkInstantiated' => true,
  'classSession'=>true
  ); 
 $autoLoadConfig[200][] = array(
  'autoType' => 'objectMethod',
  'objectName' => 'pwas_class2',
  'methodName' => '__construct'
  );
  $autoLoadConfig[95][] = array(
    'autoType'=>'init_script',
    'loadFile'=>'init_sba_copy_to_confirm_messagestack.php'
  );

  $autoLoadConfig[199][] = array(
    'autoType'=>'init_script',
    'loadFile'=>'init_sba_copy_to_confirm.php'
  );

 