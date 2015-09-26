<?php

/**
 * Autoloader array for products with attributes stock (SBA) functionality. Makes sure that products with attributes stock is instantiated at the
 * right point of the Zen Cart initsystem.
 * 
 * @package     products_with_attributes_stock
 * @author      mc12345678 
 * @copyright   Copyright 2008-2013 mc12345678
 * @copyright   Copyright 2003-2007 Zen Cart Development Team
 * @copyright   Portions Copyright 2003 osCommerce
 * @link        http://www.zen-cart.com/
 * @license     http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version     $Id: config.user_tracking.php xxxx 2013-06-28 20:31:10Z conor $
 *
 * Stock by Attributes 1.5.4
 */

 $autoLoadConfig[0][] = array(
	'autoType' => 'class',
	'loadFile' => 'observers/class.products_with_attributes_stock.php'
	);
 $autoLoadConfig[199][] = array(
	'autoType' => 'classInstantiate',
	'className' => 'products_with_attributes_stock',
	'objectName' => 'products_with_attributes_stock_observe'
	); 
?>