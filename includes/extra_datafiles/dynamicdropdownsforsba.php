<?php

if (!function_exists('zen_define_default')) {
    if (!defined('TABLE_PRODUCTS_STOCK')) {
      define('TABLE_PRODUCTS_STOCK', DB_PREFIX . 'products_with_attributes_stock');
    }
    return;
}

zen_define_default('TABLE_PRODUCTS_STOCK', DB_PREFIX . 'products_with_attributes_stock');

