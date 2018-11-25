<?php
/**
 * Simple install script for Dynamic Dropdowns For SBA
 * All this does is add an admin menu option for DD-SBA configuration choices
 */

if (! defined('IS_ADMIN_FLAG')) {
  die('Illegal Access');
}

if (!defined('BOX_CONFIGURATION_DYNAMIC_DROPDOWNS')) define('BOX_CONFIGURATION_DYNAMIC_DROPDOWNS', 'Dynamic Drop Downs');

/*if (function_exists('zen_register_admin_page')) {
  if (! zen_page_key_exists('configDynamicDropdownSBA')) {
    zen_register_admin_page('configDynamicDropdownSBA', 'BOX_CONFIGURATION_DYNAMIC_DROPDOWNS', 'FILENAME_CONFIGURATION', 'gID=888001', 'configuration', 'Y', 8);
  }
}*/