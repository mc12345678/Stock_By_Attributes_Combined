<?php

/**
 * Autoloader array for products with attributes stock (SBA) functionality. Makes sure that products with attributes stock is instantiated at the
 * right point of the Zen Cart initsystem.
 * 
 * @package     products_with_attributes_stock
 * @author      mc12345678 
 * @copyright   Copyright 2008-2016 mc12345678
 * @copyright   Copyright 2003-2007 Zen Cart Development Team
 * @copyright   Portions Copyright 2003 osCommerce
 * @link        http://www.zen-cart.com/
 * @license     http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version     $Id: config.products_with_attributes_stock.php xxxx Modified 2019-09-26 mc12345678 $
 *
 * Stock by Attributes 1.5.5
 */

 $autoLoadConfig[0][] = array(
  'autoType' => 'class',
  'loadFile' => 'class.products_with_attributes_class_stock.php'
  );
/* $autoLoadConfig[199][] = array(
  'autoType' => 'classInstantiate',
  'className' => 'products_with_attributes_class_stock',
  'objectName' => 'pwas_class'
  );*/
// Does it need to load as early as 78? Works there, but shouldn't it be 199?
// Load the class at the same point (or before) as the observer which uses it. 
 $autoLoadConfig[135][] = array(
  'autoType' => 'classInstantiate',
  'className' => 'products_with_attributes_class_stock',
  'objectName' => 'pwas_class2',
  'checkInstantiated' => true,
  'classSession'=>true
  );
// Perform cleanup before real use.
 $autoLoadConfig[135][] = array(
  'autoType' => 'objectMethod',
  'objectName' => 'pwas_class2',
  'methodName' => '__construct'
  );
 $autoLoadConfig[0][] = array(
  'autoType' => 'class',
  'loadFile' => 'observers/class.products_with_attributes_stock.php'
  );
// Be sure that observer is available before first time notifier needs to be observed.
 $autoLoadConfig[135][] = array(
  'autoType' => 'classInstantiate',
  'className' => 'products_with_attributes_stock',
  'objectName' => 'products_with_attributes_stock_observe'
  );
