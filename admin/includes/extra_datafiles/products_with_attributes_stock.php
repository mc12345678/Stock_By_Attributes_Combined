<?php
/*
 * Stock by Attributes 1.5.4
 */
if (!defined('IS_ADMIN_FLAG')) {
  die('Illegal Access');
}
define('FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK','products_with_attributes_stock');
define('TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK', DB_PREFIX . 'products_with_attributes_stock');
define('TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK', DB_PREFIX . 'orders_products_attributes_stock');
define('TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK_ATTRIBUTES_NON_STOCK', DB_PREFIX . 'products_with_attributes_stock_attributes_non_stock');
define('FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK_SETUP','stock_by_attr_install');
define('FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK_AJAX', 'products_with_attributes_stock_ajax');
