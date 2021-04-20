<?php

/**
 * Autoloader array for products with attributes stock (SBA) functionality. Makes sure that products with attributes stock is instantiated at the
 * right point of the Zen Cart initsystem.
 * 
 * @package     products_with_attributes_stock
 * @author      mc12345678 
 * @copyright   Copyright 2008-2021 mc12345678
 * @copyright   Copyright 2003-2007 Zen Cart Development Team
 * @copyright   Portions Copyright 2003 osCommerce
 * @link        http://www.zen-cart.com/
 * @license     http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version     $Id: config.pwas_customid.php xxxx Generated 2021-04-19 mc12345678 $
 *
 * Stock by Attributes 2021-04-19
 */

 $autoLoadConfig[0][] = array(
  'autoType' => 'class',
  'loadFile' => 'observers/class.pwas_customid.php'
  );
// Be sure that observer is available before first time notifier needs to be observed.
 $autoLoadConfig[181][] = array(
  'autoType' => 'classInstantiate',
  'className' => 'pwas_customid',
  'objectName' => 'pwas_customid_observer'
  );
