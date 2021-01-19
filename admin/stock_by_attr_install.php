<?php
/**
 * @package stock_by_attribute (products_with_attributes_stock)
 * @copyright Copyright 2003-2014 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id:  $
 *
 *  Stock by Attribute Installation File
 *  -- Use at your own risk!
 *  -- Backup the databases prior to using this MOD or making any changes.
 *
 *  Created for Stock by Attributes Current version: mc12345678 15-09-18
**/

require('includes/application_top.php');//Provides most of the page display admin menu

$SBAversion = 'SBA Version 1.5.4';
$ZCversion = 'Zen Cart Version ' . PROJECT_VERSION_MAJOR.'.'.PROJECT_VERSION_MINOR;

$version_check_index=true;//used in admin/includes/header.php

// Check for language in use
$languages = zen_get_languages();
$languages_array = array();
$languages_selected = DEFAULT_LANGUAGE;
for ($i = 0, $n = count($languages); $i < $n; $i++) {
  $languages_array[] = array('id' => $languages[$i]['code'],
      'text' => $languages[$i]['name']);
  if ($languages[$i]['directory'] == $_SESSION['language']) {
    $languages_selected = $languages[$i]['code'];
  }
}

// Check if session has timed out
if (!isset($_SESSION['admin_id'])) zen_redirect(zen_href_link(FILENAME_LOGIN));


//get the user selected action
if( isset($_GET['selectSBAinstall']) ){
  $action = addslashes(trim($_GET['selectSBAinstall']));
}
else{
  $action = null;
}

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<meta name="robots" content="noindex, nofollow" />
<script language="JavaScript" src="includes/menu.js" type="text/JavaScript"></script>
<link href="includes/stylesheet.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS" />
<script type="text/javascript">
  <!--
  function init()
  {
    cssjsmenu('navbar');
    if (document.getElementById)
    {
      var kill = document.getElementById('hoverJS');
      kill.disabled = true;
    }
  }
  // -->
</script>
</head>
<body onLoad="init()">
<!-- header //-->
<?php
require(DIR_WS_INCLUDES . 'header.php');

//create result message stack
//array_push($resultMmessage, $some_data);
$resultMmessage = array();

//flag to check for failure
$failed = null;

//Check for obsolete files from previous version
function checkSBAobsoleteFiles(){
  global $resultMmessage, $failed, $template_dir;

  // Attempt to find obsolete files from older versions
  $files = array(
    DIR_FS_ADMIN . DIR_WS_INCLUDES . 'extra_datafiles/products_with_attributes_stock_database_tables.php',
    DIR_FS_ADMIN . DIR_WS_INCLUDES . 'extra_datafiles/products_with_attributes_stock_filenames.php',

    DIR_FS_ADMIN . 'ajax/jquery.form.js',
    DIR_FS_ADMIN . 'ajax/jquery-1.10.2.min.js',
    DIR_FS_ADMIN . 'ajax/products_with_attributes_stock_ajax.js',

    DIR_FS_ADMIN . 'ajax/jquery.js',

//    DIR_FS_ADMIN . DIR_WS_FUNCTIONS . 'extra_functions/reg_ddsba.php',

    DIR_FS_CATALOG . 'ajax/jquery.form.js',
    DIR_FS_CATALOG . 'ajax/jquery.js',
    DIR_FS_CATALOG . 'ajax/products_with_attributes_stock_ajax.js'
  );

  if (PROJECT_VERSION_MAJOR > '1' || PROJECT_VERSION_MINOR >= '5.5') {
    $files_merge = array(DIR_FS_CATALOG_MODULES . $template_dir . '/pages/checkout_success/header_php_sba.php');
    $files = array_merge($files, $files_merge);
  }

  foreach($files as $file) {
    //report failure if file still exists
    if(file_exists($file)) {
      array_push($resultMmessage, 'File needs to be removed: <b>' . $file . '</b>' );
      $failed = true;
    }
  }

  return;
}

function removeDynDropdownsConfiguration() {
  global $db, $resultMmessage;

  /*
  DELETE FROM configuration  WHERE  configuration_key = 'PRODINFO_ATTRIBUTE_PLUGIN_SINGLE';
  DELETE FROM configuration  WHERE  configuration_key = 'PRODINFO_ATTRIBUTE_PLUGIN_MULTI';
  DELETE FROM configuration  WHERE  configuration_key = 'PRODINFO_ATTRIBUTE_SHOW_OUT_OF_STOCK';
  DELETE FROM configuration  WHERE  configuration_key = 'PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK';
  DELETE FROM configuration  WHERE  configuration_key = 'PRODINFO_ATTRIBUTE_OUT_OF_STOCK_MSGLINE';
  DELETE FROM configuration  WHERE  configuration_key = 'PRODINFO_ATTRIBUTE_NO_ADD_OUT_OF_STOCK';
  DELETE FROM configuration  WHERE  configuration_key = 'STOCK_SET_SBA_NUMRECORDS';//Not yet used
  */
  $msg = '';
  array_push($resultMmessage, '<br /><b>Clean-Up</b>, Removing from configuration: ');

  $delete_confs = array(
    'PRODINFO_ATTRIBUTE_PLUGIN_SINGLE',
    'PRODINFO_ATTRIBUTE_PLUGIN_MULTI',
    'PRODINFO_ATTRIBUTE_SHOW_OUT_OF_STOCK',
    'PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK',
    'PRODINFO_ATTRIBUTE_OUT_OF_STOCK_MSGLINE',
    'PRODINFO_ATTRIBUTE_NO_ADD_OUT_OF_STOCK',
    'STOCK_SET_SBA_NUMRECORDS',
    'PRODINFO_ATTRIBUTE_DYNAMIC_STATUS',
    'SBA_ZC_DEFAULT',
    'PRODINFO_ATTRIBUTE_POPUP_OUT_OF_STOCK',
    'PRODINFO_ATTRIBUTE_DYNAMIC_STOCK_READ_ONLY',
  );

  foreach ($delete_confs as $delete_conf) {
    $msg = '';

    $prev_val = zen_get_configuration_key_value($delete_conf);

    $sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = :delete_conf:";
    $sql = $db->bindVars($sql, ':delete_conf:', $delete_conf, 'stringIgnoreNull');
    $db->Execute($sql);
    if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
      $msg = ' Error Message: ' . $db->error_message;
    }
    array_push($resultMmessage, '&bull; Deleted ' . $delete_conf . '  ' . 'Prev val: ' . $prev_val . '  ' . $msg);
  }

        zen_record_admin_activity('Deleted Dynamic Dropdowns from database via SBA install.', 'warning');

  return;

}


//Clean-up remove existing entries prior to adding new
function removeSBAconfiguration(){
  global $db, $resultMmessage;

  /*
  DELETE FROM configuration  WHERE  configuration_key = 'STOCK_SHOW_IMAGE';
  DELETE FROM configuration  WHERE  configuration_key = 'STOCK_SHOW_LOW_IN_CART';
  DELETE FROM configuration  WHERE  configuration_key = 'STOCK_SHOW_ATTRIB_LEVEL_STOCK';
  DELETE FROM configuration  WHERE  configuration_key = 'STOCK_SHOW_ORIGINAL_PRICE_STRUCK';
  DELETE FROM configuration  WHERE  configuration_key = 'STOCK_SET_SBA_SEARCHBOX';
  DELETE FROM configuration  WHERE  configuration_key = 'STOCK_SET_SBA_NUMRECORDS';//Not yet used
  DELETE FROM configuration  WHERE  configuration_key = 'STOCK_SBA_SEARCHLIST';
  DELETE FROM configuration  WHERE  configuration_key = 'STOCK_SBA_DISPLAY_CUSTOMID';
  DELETE FROM configuration  WHERE  configuration_key = 'SBA_SHOW_IMAGE_ON_PRODUCT_INFO';

  These are for the added Option Value Name selection:
  DELETE FROM configuration  WHERE  configuration_key = 'PRODUCTS_OPTIONS_TYPE_SELECT_SBA';
  DELETE FROM products_options_types WHERE products_options_types_name = 'SBA Select List (Dropdown) Basic';
  */
  $msg = '';
  array_push($resultMmessage, '<br /><b>Clean-Up</b>, Removing from configuration: ');

  $delete_confs = array(
    'STOCK_SHOW_IMAGE',
    'STOCK_SHOW_LOW_IN_CART',
    'STOCK_SHOW_ATTRIB_LEVEL_STOCK',
    'STOCK_SHOW_ORIGINAL_PRICE_STRUCK',
    'STOCK_SET_SBA_SEARCHBOX',
    'STOCK_SBA_SEARCHLIST',
    'STOCK_SBA_DISPLAY_CUSTOMID',
    'SBA_SHOW_IMAGE_ON_PRODUCT_INFO',
    'PRODUCTS_OPTIONS_TYPE_SELECT_SBA',
    'ATTRIBUTES_SBA_DISPLAY_CUSTOMID',
    'SBA_SHOW_OUT_OF_STOCK_ATTR_ON_PRODUCT_INFO',
    'STOCK_SBA_CUSTOM_FOR_MODEL',
  );

  foreach ($delete_confs as $delete_conf) {
    $msg = '';

    $prev_val = zen_get_configuration_key_value($delete_conf);

    $sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = :delete_conf:";
    $sql = $db->bindVars($sql, ':delete_conf:', $delete_conf, 'stringIgnoreNull');
    $db->Execute($sql);
    if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
      $msg = ' Error Message: ' . $db->error_message;
    }
    array_push($resultMmessage, '&bull; Deleted ' . $delete_conf . '  ' . 'Prev val: ' . $prev_val . '  ' . $msg);
  }

//   $sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'STOCK_SET_SBA_NUMRECORDS'";
//   $db->Execute($sql);
//   array_push($resultMmessage, '&bull; Deleted STOCK_SET_SBA_NUMRECORDS  ' . $msg);

  //DELETE FROM `products_options_types`
  array_push($resultMmessage, '<br /><b>Clean-Up</b>, Removing from products_options_types: ');

  // Reassign products options that are assigned to the SBA Select List (Dropdown) Basic group to be assigned to a standard Dropdown.

  $products_options_types_name = 'SBA Select List (Dropdown) Basic';

  $msg = '';
  $sql = "SELECT products_options_types_id FROM " . TABLE_PRODUCTS_OPTIONS_TYPES . " WHERE products_options_types_name = :products_options_types_name:";
  $sql = $db->bindVars($sql, ':products_options_types_name:', $products_options_types_name, 'string');

  $result = $db->Execute($sql);
  array_push($resultMmessage, '&bull; Moving option types from ' . $products_options_types_name . ' to an equivalent type to prepare for removal.');

  if ($result->RecordCount() > 0) {
    $sql = "UPDATE " . TABLE_PRODUCTS_OPTIONS . " SET products_options_type = :products_options_type_new: WHERE products_options_type = :products_options_type_old:";
    $sql = $db->bindVars($sql, ':products_options_type_old:', $result->fields['products_options_types_id'], 'integer');
    $sql = $db->bindVars($sql, ':products_options_type_new:', (defined('PRODUCTS_OPTIONS_TYPE_SELECT') ? PRODUCTS_OPTIONS_TYPE_SELECT : 0), 'integer');

    $sql = $db->Execute($sql);
    if ((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)) {
      $msg = ' Error Message: ' . $db->error_message;
    }
    array_push($resultMmessage, '&bull; Moved option types from ' . $products_options_types_name . ' to an equivalent type.' . $msg);
  }

  $msg = '';
  $sql = "DELETE IGNORE FROM `".TABLE_PRODUCTS_OPTIONS_TYPES."` WHERE `products_options_types_name` = :products_options_types_name:";
  $sql = $db->bindVars($sql, ':products_options_types_name:', $products_options_types_name, 'string');

  $db->Execute($sql);
  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
  }
  array_push($resultMmessage, '&bull; Deleted products_options_types_name  ' . $msg);

  zen_record_admin_activity('Deleted SBA settings from the database via the install file.', 'warning');

  return;
}

function removeDynDropdownsAdminPages(){
  global $db, $resultMmessage;

  $msg = '';
  $pages = 'configDynamicDropdownSBA';

  array_push($resultMmessage, '<br /><b>Clean-Up</b>, Removing Dynamic Dropdowns from admin_pages: ');

  if (function_exists('zen_deregister_admin_pages'))
  {
    zen_deregister_admin_pages($pages);
  } else
  {
    $sql = "DELETE FROM `".TABLE_ADMIN_PAGES."` WHERE page_key = :page_key:";
    $sql = $db->bindVars($sql, ':page_key:', $pages[0], 'string');
    $db->Execute($sql);
    zen_record_admin_activity('Delete admin pages for page keys: ' . print_r($pages, true), 'warning');
  }
  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
  }
  array_push($resultMmessage, '&bull; Deleted configDynamicDropdownSBA ' . $msg);

  /*
   DELETE FROM admin_pages  WHERE  page_key = 'productsWithAttributesStockSetup';
  */
  $msg = '';
  $sql = "DELETE FROM ".TABLE_CONFIGURATION_GROUP." WHERE configuration_group_title='Dynamic Drop Downs'";

  $db->Execute($sql);
  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
  }
  array_push($resultMmessage, '&bull; Deleted Dynamic Drop Downs from configuration_group ' . $msg);
  zen_record_admin_activity('Deleted dynamic Drop Downs from the configuration group via install file.', 'warning');

}


//Clean-up remove existing entries prior to adding new
function removeSBAadminPages(){
  global $db, $resultMmessage;

  /*
   DELETE FROM admin_pages  WHERE  page_key = 'productsWithAttributesStock';
  */
  $msg = '';
  array_push($resultMmessage, '<br /><b>Clean-Up</b>, Removing from admin_pages: ');

  $pages = array('productsWithAttributesStock', 'productsWithAttributesStockSetup', 'productsWithAttributesStockAjax');

  if (function_exists('zen_deregister_admin_pages'))
  {
    zen_deregister_admin_pages($pages);
    foreach ($pages as $key=>$page)
    {
      array_push($resultMmessage, '&bull; Deleted ' . $pages[$key]);
    }
  } else
  {
    foreach ($pages as $key=>$page) {
      $sql = "DELETE FROM `".TABLE_ADMIN_PAGES."` WHERE page_key = :page_key:";
      $sql = $db->bindVars($sql, ':page_key:', $pages[$key], 'string');
      $db->Execute($sql);
      zen_record_admin_activity('Deleted admin pages for page keys: ' . print_r($pages[$key], true), 'warning');
      $msg = '';
      if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
        $msg = ' Error Message: ' . $db->error_message;
      }
      array_push($resultMmessage, '&bull; Deleted ' . $pages[$key] . ' ' . $msg);
    }
  }

  return;
}

//Clean-up Drop table products_with_attributes_stock
function dropSBATable(){
  global $db, $resultMmessage;

  /*
   * DROP TABLE IF EXISTS 'products_with_attributes_stock';
   */
  array_push($resultMmessage, '<br /><b>Clean-Up</b>, Removing Table products_with_attributes_stock: ');

  $msg = '';
  $sql = "DROP TABLE IF EXISTS ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK;

  $db->Execute($sql);
  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
  }
  array_push($resultMmessage, '&bull; Deleted table products_with_attributes_stock ' . $msg);
  zen_record_admin_activity('Deleted the products_with_attributes_stock table from the install file.', 'warning');

  return;
}

function dropSBANonStockTable() {
  global $db, $resultMmessage;

  /*
   * DROP TABLE IF EXISTS 'products_with_attributes_stock_attributes_non_stock';
   */
  array_push($resultMmessage, '<br /><b>Clean-Up</b>, Removing Table products_with_attributes_stock_attributes_non_stock: ');

  $msg = '';
  $sql = "DROP TABLE IF EXISTS ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK_ATTRIBUTES_NON_STOCK;

  $db->Execute($sql);
  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
  }
  array_push($resultMmessage, '&bull; Deleted table products_with_attributes_stock_attributes_non_stock ' . $msg);
  zen_record_admin_activity('Deleted the products_with_attributes_stock_attributes_non_stock table from the install file.', 'warning');

  return;
}

//Clean-up Drop table products_with_attributes_stock
function dropSBAOrdersTable(){
  global $db, $resultMmessage;

  /*
   * DROP TABLE IF EXISTS 'orders_products_attributes_stock';
   */
  array_push($resultMmessage, '<br />Clean-Up, Removing Table orders_products_attributes_stock: ');

  $sql = "DROP TABLE IF EXISTS ".TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK;
  $db->Execute($sql);
  array_push($resultMmessage, 'Deleted table orders_products_attributes_stock.' );
  zen_record_admin_activity('Deleted the SBA table orders_products_attributes_stock from install file.', 'warning');

  return;
}

//Add this script to the configuration menu
function insertSBAconfigurationMenu(){
  global $db, $resultMmessage;

  array_push($resultMmessage, '<br /><b>Adding</b> to admin_pages: ');

  // get current max sort number used, then add 1 to it.
  // this will place the new entry 'productsWithAttributesStock' at the bottom of the list
  $sql = "SELECT MAX(ap.sort_order) as sort_order_max
       FROM " . TABLE_ADMIN_PAGES . " ap
       WHERE ap.menu_key = 'configuration'";
  $result = $db->Execute($sql);
  $result = $result->fields['sort_order_max'] + 1;
  if (function_exists('zen_register_admin_page')) {
    zen_register_admin_page('productsWithAttributesStockSetup', 'BOX_CONFIGURATION_PRODUCTS_WITH_ATTRIBUTES_STOCK_SETUP', 'FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK_SETUP', '', 'configuration', 'Y', $result);
    $result = $result + 1; // provide an increased sort order for the next non-displayed menu.
    zen_register_admin_page('productsWithAttributesStockAjax', 'BOX_CONFIGURATION_PRODUCTS_WITH_ATTRIBUTES_STOCK_AJAX', 'FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK_AJAX', '', 'catalog', 'N', $result);
  } else {
    $sql = "INSERT INTO `".TABLE_ADMIN_PAGES."` (page_key, language_key, main_page, page_params, menu_key, display_on_menu, sort_order)
          VALUES
          ('productsWithAttributesStockSetup', 'BOX_CONFIGURATION_PRODUCTS_WITH_ATTRIBUTES_STOCK_SETUP', 'FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK_SETUP', '', 'configuration', 'Y', :sort_order:)";
    $sql = $db->bindVars($sql, ':sort_order:', $result, 'integer');
    $db->Execute($sql);
    $result = $result + 1; // provide an increased sort order for the next non-displayed menu.
    $sql = "INSERT INTO `".TABLE_ADMIN_PAGES."` (page_key, language_key, main_page, page_params, menu_key, display_on_menu, sort_order)
          VALUES
          ('productsWithAttributesStockAjax', 'BOX_CONFIGURATION_PRODUCTS_WITH_ATTRIBUTES_STOCK_AJAX', 'FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK_AJAX', '', 'catalog', 'N', :sort_order:)";
    $sql = $db->bindVars($sql, ':sort_order:', $result, 'integer');
    $db->Execute($sql);
  }
  $msg = '';
  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
  }
  array_push($resultMmessage, '&bull; Inserted into admin_pages productsWithAttributesStockSetup. ' . $msg);

  return;
}

function insertDynDropdownsConfigurationMenu(){
  global $db, $resultMmessage;

  array_push($resultMmessage, '<br /><b>Adding</b> to admin_pages: ');

  //get current max sort number used, then add 1 to it.
  //this will place the new entry 'productsWithAttributesStock' at the bottom of the list
  $sql = "SELECT configuration_group_id" /*, MAX(configuration_group_id) as last_configuration_group_id*/ . " FROM ".TABLE_CONFIGURATION_GROUP." WHERE configuration_group_title='Dynamic Drop Downs' LIMIT 1";
  $result = $db->Execute($sql);
  $configuration_id = isset($result->fields['configuration_group_id']) ? $result->fields['configuration_group_id'] : 0;
  if($configuration_id=='' || $configuration_id == '0') {
    $sql = "SELECT `AUTO_INCREMENT`
FROM  INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = '". DB_DATABASE . "'
AND   TABLE_NAME   = '".TABLE_CONFIGURATION_GROUP."'";
    $configuration_id = $db->Execute($sql);
    $configuration_id = $configuration_id->fields['AUTO_INCREMENT'];
    //$configuration_id++;
    //$configuration_id = $result->fields['last_configuration_group_id'] + 1;

    $sql = "INSERT INTO `".TABLE_CONFIGURATION_GROUP."` (configuration_group_id, configuration_group_title, configuration_group_description, sort_order, visible)
      VALUES
      (:configuration_group_id:, 'Dynamic Drop Downs', 'Dynamic Drop Downs configuration options', 8, 1)";
    $sql = $db->bindVars($sql, ':configuration_group_id:', $configuration_id, 'integer');
    $result = $db->Execute($sql);
    $msg = '';
    if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
      $msg = ' Error Message: ' . $db->error_message;
    }
    zen_record_admin_activity('Inserted dynamic Drop Downs into the configuration group via install file.', 'warning');
    array_push($resultMmessage, '&bull; Inserted into configuration_group Dynamic Drop Downs. ' . $msg);
  }

/*  $sql = "SELECT ap.sort_order
      FROM ".TABLE_ADMIN_PAGES." ap
      WHERE ap.menu_key = 'configuration'
      order by ap.sort_order desc limit 1";
  $result = $db->Execute($sql);
  $result = $result->fields['sort_order'] + 1;*/

  $sql = "INSERT INTO `".TABLE_ADMIN_PAGES."` (page_key, language_key, main_page, page_params, menu_key, display_on_menu, sort_order)
      VALUES
      ('configDynamicDropdownSBA', 'BOX_CONFIGURATION_DYNAMIC_DROPDOWNS', 'FILENAME_CONFIGURATION', 'gID=:configuration_id:', 'configuration', 'Y', 8)";
  $sql = $db->bindVars($sql, ':configuration_id:', $configuration_id, 'integer');
  $db->Execute($sql);
  $msg = '';
  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
  }
  array_push($resultMmessage, '&bull; Inserted into admin_pages configDynamicDropdownSBA. ' . $msg);

  return;
}

//Add required entry into the admin_pages table
function insertSBAadminPages(){
  global $db, $resultMmessage;
  $msg = '';
  array_push($resultMmessage, '<br /><b>Adding</b> to admin_pages: ');

  //get current max sort number used, then add 1 to it.
  //this will place the new entry 'productsWithAttributesStock' at the bottom of the list
  $sql = "SELECT ap.sort_order + 1 as next_sort_order
      FROM ".TABLE_ADMIN_PAGES." ap
       WHERE ap.menu_key = 'catalog'
      order by ap.sort_order desc limit 1";
  $result = $db->Execute($sql);
  $result = $result->fields['next_sort_order'];

  $sql = "INSERT INTO `".TABLE_ADMIN_PAGES."` (page_key, language_key, main_page, page_params, menu_key, display_on_menu, sort_order)
      VALUES
      ('productsWithAttributesStock', 'BOX_CATALOG_PRODUCTS_WITH_ATTRIBUTES_STOCK', 'FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK', '', 'catalog', 'Y', ".$result.")";
  $db->Execute($sql);
  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
  }
  array_push($resultMmessage, '&bull; Inserted into admin_pages productsWithAttributesStock. ' . $msg);

  return;
}


function verifyProductOptionsTypes(){
  global $db, $resultMmessage;

  array_push($resultMmessage, 'Updating PRODUCTS_OPTIONS_TYPE_SELECT, UPLOAD_PREFIX and TEXT_PREFIX');

  $db->Execute("UPDATE " . TABLE_CONFIGURATION . " set configuration_group_id = 6 where configuration_key in
  ('PRODUCTS_OPTIONS_TYPE_SELECT', 'UPLOAD_PREFIX', 'TEXT_PREFIX');");
  $db->Execute("INSERT IGNORE INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('Product option type Select', 'PRODUCTS_OPTIONS_TYPE_SELECT', '0', 'The number representing the Select type of product option.', 6, NULL, now(), now(), NULL, NULL);
");
  $db->Execute("INSERT IGNORE INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('Upload prefix', 'UPLOAD_PREFIX', 'upload_', 'Prefix used to differentiate between upload options and other options', 6, NULL, now(), now(), NULL, NULL);
");
  $db->Execute("INSERT IGNORE INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('Text prefix', 'TEXT_PREFIX', 'txt_', 'Prefix used to differentiate between text option values and other option values', 6, NULL, now(), now(), NULL, NULL);");

  array_push($resultMmessage, 'Updated PRODUCTS_OPTIONS_TYPE_SELECT, UPLOAD_PREFIX and TEXT_PREFIX');
  zen_record_admin_activity('Updated PRODUCTS_OPTIONS_TYPE_SELECT, UPLOAD_PREFIX and TEXT_PREFIX via SBA install file
  .', 'warning');
}

//Add required entries into the products_options_types table
function insertSBAproductsOptionsTypes(){
  global $db, $resultMmessage, $failed;
  $msg = '';

  array_push($resultMmessage, '<br /><b>Verifiying</b> products_options_types: ');

  if (defined('PRODUCTS_OPTIONS_TYPE_SELECT_SBA')) {
    array_push($resultMmessage, '&bull; Configuration contains "Selection list product option type (SBA)" no
    action necessary. ' . $msg);
    return;
  }

  $products_options_types_name = 'SBA Select List (Dropdown) Basic';

  $sql = "SELECT products_options_types_id FROM " . TABLE_PRODUCTS_OPTIONS_TYPES . "
  WHERE products_options_types_name = :products_options_types_name:";
  $sql = $db->bindVars($sql, ':products_options_types_name:', $products_options_types_name, 'string');
  $result = $db->Execute($sql, false, false, 0, true);

  if (!$result->EOF && $result->RecordCount() >= 1) {
    array_push($resultMmessage, '<br /><b>Obtaining</b> current products_options_types: ');
    $resultGID = $result->fields['products_options_types_id'];
  } else {
    array_push($resultMmessage, '<br /><b>Finding</b> highest products_options_types value: ');
    //get current max sort number used, then add 1 to it.
    //this will place the new entries at the bottom of the list
    $sql = "SELECT products_options_types_id, products_options_types_name
    FROM " . TABLE_PRODUCTS_OPTIONS_TYPES . "
    ORDER BY products_options_types_id DESC LIMIT 1";
    $result = $db->Execute($sql, false, false, 0, true);
    $resultGID = $result->fields['products_options_types_id'] + 1;

    array_push($resultMmessage, '<br /><b>Adding</b> to products_options_types: ');
    $sql = "INSERT INTO " . TABLE_PRODUCTS_OPTIONS_TYPES . " (products_options_types_id,
    products_options_types_name)
    VALUES (:resultGID:, :products_options_types_name:)";
    $sql = $db->bindVars($sql, ':resultGID:', $resultGID, 'integer');
    $sql = $db->bindVars($sql, ':products_options_types_name:', $products_options_types_name, 'string');

    $db->Execute($sql, false, false, 0, true);

    if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
      $msg = ' Error Message: ' . $db->error_message;
      $failed = true;
    }
    array_push($resultMmessage, '&bull; Inserted into products_options_types "' . $products_options_types_name . '". ' . $msg);

  }

  array_push($resultMmessage, '<br /><b>Adding</b> to configuration: ');

  $msg = '';
  $sql = "INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,
  configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
  VALUES
  ('Selection list product option type (SBA)', 'PRODUCTS_OPTIONS_TYPE_SELECT_SBA', :products_options_types_id:,
   'Numeric value of the :products_options_types_name:',
   6, 0, NOW(), NULL, NULL)";
  $sql = $db->bindVars($sql, ':products_options_types_id:', $resultGID, 'integer');
  $sql = $db->bindVars($sql, ':products_options_types_name:', $products_options_types_name, 'noquotestring');

  $db->Execute($sql, false, false, 0, true);

  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
    array_push($resultMmessage, '&bull; Error inserting into configuration "Selection list product option type (SBA)" .
    ' . $msg);
    return;
  }
  define('PRODUCTS_OPTIONS_TYPE_SELECT_SBA', $resultGID);

  array_push($resultMmessage, '&bull; Inserted into configuration "Selection list product option type (SBA)" .
  ' . $msg);

  return;
}

//Add required entries into the configuration table
function insertSBAconfiguration(){
  global $db, $resultMmessage, $failed;

  array_push($resultMmessage, '<br /><b>Adding</b> to configuration (SBA option switches): ');

  //get current max sort number used, then add 1 to it.
  //this will place the new entries at the bottom of the list
  $sql ="SELECT c.sort_order + 1 as next_sort_order
      FROM ".TABLE_CONFIGURATION." c
      WHERE c.configuration_group_id = 9
      order by c.sort_order desc limit 1";
  $result = $db->Execute($sql);
  $result = $result->fields['next_sort_order'];

  $sql = "INSERT INTO `".TABLE_CONFIGURATION."` (configuration_title, configuration_key, configuration_value,
         configuration_description, configuration_group_id, sort_order,
         date_added, use_function, set_function)

         VALUES
        ('SBA Show Available Stock Level in Cart (when less than order)', 'STOCK_SHOW_LOW_IN_CART', 'true',
          'When customer places more items in cart than are available, show the available stock on the shopping cart page:',
          9,".$result.",now(),NULL,'zen_cfg_select_option(array(\'true\', \'false\'),'),

          ('SBA Display Images in Admin', 'STOCK_SHOW_IMAGE', 'true',
          'Display image thumbnails on Products With Attributes Stock page? (warning, setting this to true can severely slow the loading of this page):',
          9,".$result.",now(),NULL,'zen_cfg_select_option(array(\'true\', \'false\'),'),

        ('SBA Show Stock Level on Product Info Page', 'STOCK_SHOW_ATTRIB_LEVEL_STOCK', 'true',
          'Show the available stock with each attribute on product info page:',
          9,".$result.",now(),NULL,'zen_cfg_select_option(array(\'true\', \'false\'),'),

        ('SBA Original Price Struck Through', 'STOCK_SHOW_ORIGINAL_PRICE_STRUCK', 'true',
          'Show the original price (struck through) on product info page with attribute:',
          9,".$result.",now(),NULL,'zen_cfg_select_option(array(\'true\', \'false\'),'),

      ('SBA Display Search Box Only', 'STOCK_SET_SBA_SEARCHBOX', 'false',
      'Show Search box only (no records):',
      9,".$result.",now(),null,'zen_cfg_select_option(array(\'true\', \'false\'),'),

      ('SBA Display Search List Box', 'STOCK_SBA_SEARCHLIST', 'true',
      'Show the Search List box At the top of the page:',
      9,".$result.",now(),null,'zen_cfg_select_option(array(\'true\', \'false\'),'),

      ('SBA Display Custom ID', 'STOCK_SBA_DISPLAY_CUSTOMID', 'true',
      'Display the Custom ID value in history, checkout, and order forms:',
      9,".$result.",now(),null,'zen_cfg_select_option(array(\'true\', \'false\'),'),

      ('SBA Display Attributes Images', 'SBA_SHOW_IMAGE_ON_PRODUCT_INFO', '1',
    'Allow swap of the attribute image with the main image or prevent the display of the Attribute Image (and allow swap) on the product information page:<br /><br /> Default: 1 (Swap and display)<br />0 - No swap, display image ZC default<br />1 - Swap and display attr img<br />2 - Swap, but hide attribute image<br />3 - Swap, but hide only *non*-SBA product images<br />4 - Swap, but hide *all* attribute images',
      9,".$result.",now(),null,'zen_cfg_select_option(array(\'0\', \'1\', \'2\', \'3\', \'4\'),'),

      ('SBA Display Non-DD Out-of-Stock Attributes', 'SBA_SHOW_OUT_OF_STOCK_ATTR_ON_PRODUCT_INFO', '1',
    'Allow display of attributes when using the SBA Select List (Dropdown) Basic Option Name type that are out-of-stock and are not managed by Dynamic Dropdowns.<br /><br /> Default: 1 (Show out-of-stock attributes)<br />0 - Hide out-of-stock attributes<br />1 - Show out-of-stock attributes',
      9,".$result.",now(),null,'zen_cfg_select_option(array(\'0\', \'1\'),'),
      ('SBA CustomID replaces products_model', 'STOCK_SBA_CUSTOM_FOR_MODEL', '1',
    'In review and display of order history related information, how should the products_model of the product be treated related to the SBA customid?<br /><br /> Default: false (Only show the product\'s assigned products_model)<br />1 - Substitute the product\'s products_model with the customid when the customid is not empty or blank<br />2 - Always substitute the product\'s products_model with the customid<br />3 - Substitute the product\'s products_model with the customid when the products_model is blank or empty',
      9,".$result.",now(),null,'zen_cfg_select_option(array(\'false\', \'1\', \'2\', \'3\'),'),";

  $sql2 ="SELECT c.sort_order + 1 as next_sort_order
      FROM ".TABLE_CONFIGURATION." c
      WHERE c.configuration_group_id = 13
      order by c.sort_order desc limit 1";
  $result = $db->Execute($sql2);
  $result = $result->fields['next_sort_order'];

  $sql .=" ('SBA Display CustomID in Attribute Dropdowns', 'ATTRIBUTES_SBA_DISPLAY_CUSTOMID', '2',
  'Display the CustomID in the Attribute Dropdown list(s) for the customer to see while selecting an option.<br /><br /> 0 - Hide the Custom ID<br /> 1 - Display the Custom ID depending on the setting for display throughout<br /> 2 - Always display the Custom ID (default)<br /> 3 - Always display the Custom ID except at single attributes<br />',
  13,".$result.",now(),null,'zen_cfg_select_drop_down(array(array(\'id\'=>\'0\', \'text\'=>\'Off\'), array(\'id\'=>\'1\', \'text''=>\'On Pending SBA Stock\'), array(\'id\'=>\'2\', \'text''=>\'Always On\'), array(\'id\'=>\'3\', \'text''=>\'Off for Single Attributes\'), ),');";

  /* save for next version when pagination is implemented
     *
         ('SBA Number of Records to Displayed', 'STOCK_SET_SBA_NUMRECORDS', '25',
        'Number of records to show on page:',
        9,".$result.",now(),now(),null,null),
     */

  $result = $db->Execute($sql);


  $msg = '';
  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
    return;
  }
  $list = '&bull; STOCK_SHOW_IMAGE<br />
          &bull; STOCK_SHOW_LOW_IN_CART<br />
          &bull; STOCK_SHOW_ATTRIB_LEVEL_STOCK<br />
          &bull; STOCK_SHOW_ORIGINAL_PRICE_STRUCK<br />
        &bull; STOCK_SET_SBA_SEARCHBOX<br />
          &bull; STOCK_SBA_SEARCHLIST<br />
          &bull; STOCK_SBA_DISPLAY_CUSTOMID<br />
          &bull; SBA_SHOW_IMAGE_ON_PRODUCT_INFO';
  array_push($resultMmessage, 'Inserted into configuration: <br />' . $list);

  return;
}


function insertDynDropdownsConfiguration(){
  global $db, $resultMmessage, $failed;

  array_push($resultMmessage, '<br /><b>Adding</b> to configuration (Dynamic Dropdowns option switches): ');

  //get current max sort number used, then add 1 to it.
  //this will place the new entries at the bottom of the list

  $sql = "SELECT configuration_group_id FROM ".TABLE_CONFIGURATION_GROUP." WHERE configuration_group_title='Dynamic Drop Downs' LIMIT 1";
  $result = $db->Execute($sql);
  $configuration_id = $result->fields['configuration_group_id'];


  $sql ="SELECT c.sort_order + 1 as next_sort_order
      FROM ".TABLE_CONFIGURATION." c
      WHERE c.configuration_group_id = :configuration_id:
      order by c.sort_order desc limit 1";
  $sql = $db->bindVars($sql, ':configuration_id:', $configuration_id, 'integer');
  $result_result = $db->Execute($sql);
  $result = 1;
  if (!$result_result->EOF) {
    $result = $result_result->fields['next_sort_order'];
  }
  unset($result_result);

  $sql = "INSERT INTO `".TABLE_CONFIGURATION."` (configuration_title, configuration_key, configuration_value,
         configuration_description, configuration_group_id, sort_order,
         date_added, use_function, set_function)
         VALUES
        ('Enable Dynamic Dropdowns', 'PRODINFO_ATTRIBUTE_DYNAMIC_STATUS', '2', 'Selects status of using this portion of the SBA plugin (Dynamic Dropdowns).', :configuration_id:, 10, now(), NULL, 'zen_cfg_select_drop_down(array(array(\'id\'=>\'0\', \'text\'=>\'Off\'), array(\'id\'=>\'1\', \'text''=>\'On for All SBA Tracked\'), array(\'id\'=>\'2\', \'text''=>\'On for Multi-Attribute Only\'), array(\'id\'=>\'3\', \'text''=>\'On for Single-Attribute Only\'), ),'),
        ('Product Info Single Attribute Display Plugin', 'PRODINFO_ATTRIBUTE_PLUGIN_SINGLE', 'multiple_dropdowns', 'The plugin used for displaying attributes on the product information page.', :configuration_id:, 20, now(), NULL, 'zen_cfg_select_option(array(\'single_radioset\', \'single_dropdown\',\'multiple_dropdowns\',\'sequenced_dropdowns\',\'sba_sequenced_dropdowns\'),'),
          ('Product Info Multiple Attribute Display Plugin', 'PRODINFO_ATTRIBUTE_PLUGIN_MULTI', 'sba_sequenced_dropdowns', 'The plugin used for displaying attributes on the product information page.', :configuration_id:, 30, now(), NULL, 'zen_cfg_select_option(array(\'single_radioset\', \'single_dropdown\',\'multiple_dropdowns\',\'sequenced_dropdowns\',\'sba_sequenced_dropdowns\'),'),
    ('Use ZC default HTML Attribute Tags', 'SBA_ZC_DEFAULT', 'false', 'Controls whether to use ZC HTML tags around attributes or to use the Dynamic Dropdown Version of the tags to support modifications made by others over the years but also compatibility with other ZC plugins.<br /><br />Options:<br />true <br />false (Default).', :configuration_id:, 40, now(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
      ('Show Out of Stock Attributes', 'PRODINFO_ATTRIBUTE_SHOW_OUT_OF_STOCK', 'True', 'Controls the display of out of stock attributes.', :configuration_id:, 50, now(), NULL, 'zen_cfg_select_option(array(\'True\', \'False\'),'),
    ('Mark Out of Stock Attributes', 'PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK', 'Right', 'Controls how out of stock attributes are marked as out of stock.', :configuration_id:, 60, now(), NULL, 'zen_cfg_select_option(array(\'None\', \'Right\', \'Left\'),'),
      ('Display Out of Stock Message Line', 'PRODINFO_ATTRIBUTE_OUT_OF_STOCK_MSGLINE', 'True', 'Controls the display of a message line indicating an out of stock attributes is selected.', :configuration_id:, 70, now(), NULL, 'zen_cfg_select_option(array(\'True\', \'False\'),'),
      ('Prevent Adding Out of Stock to Cart', 'PRODINFO_ATTRIBUTE_NO_ADD_OUT_OF_STOCK', 'True', 'Prevents adding an out of stock attribute combination to the cart.', :configuration_id:, 80, now(), NULL, 'zen_cfg_select_option(array(\'True\', \'False\'),'),
      ('SBA Number of Records to Displayed', 'STOCK_SET_SBA_NUMRECORDS', '25',
        'Number of records to show on page:',
        :configuration_id:, 60, now(), NULL, NULL),
    ('Display Javascript Popup for Out-of-Stock Selection', 'PRODINFO_ATTRIBUTE_POPUP_OUT_OF_STOCK', 'True', 'Controls whether to display or not the message for when a products attribute is out-of-stock.', :configuration_id:, 90, now(), NULL, 'zen_cfg_select_option(array(\'True\', \'False\'),'),
    ('Count Read Only as Stock', 'PRODINFO_ATTRIBUTE_DYNAMIC_STOCK_READ_ONLY', 'false', 'Controls whether read only attributes should be controlled as stock (true) or ignored (default:false)', :configuration_id:, 100, now(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')
    ;";
  $sql = $db->bindVars($sql, ':configuration_id:', $configuration_id, 'integer');
  $db->Execute($sql);

  $list = '&bull; PRODINFO_ATTRIBUTE_PLUGIN_SINGLE<br />
          &bull; PRODINFO_ATTRIBUTE_PLUGIN_MULTI<br />
          &bull; PRODINFO_ATTRIBUTE_SHOW_OUT_OF_STOCK<br />
          &bull; PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK<br />
          &bull; PRODINFO_ATTRIBUTE_OUT_OF_STOCK_MSGLINE<br />
          &bull; PRODINFO_ATTRIBUTE_NO_ADD_OUT_OF_STOCK<br />
          &bull; STOCK_SET_SBA_NUMRECORDS';
  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    array_push($resultMmessage, 'Did not insert into configuration: <br />' . $list);
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
    return;
  }

  array_push($resultMmessage, 'Inserted into configuration: <br />' . $list);
  zen_record_admin_activity('Inserted dynamic Drop Downs configuration options via install file.', 'warning');
}

function addSBANonStockTable() {
  global $db, $resultMmessage, $failed;

  if(checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK_ATTRIBUTES_NON_STOCK)) {
    return;
  }

  $msg = '';
  $result = $db->Execute("CREATE TABLE IF NOT EXISTS " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK_ATTRIBUTES_NON_STOCK . " (
    attribute_type varchar(64) NOT NULL default '',
    attribute_type_source_id int(11) NOT NULL default '0',
    attribute_type_id int(11) NOT NULL default '0',
    language_id int(11) NOT NULL default '0',
    PRIMARY KEY (attribute_type,attribute_type_source_id,attribute_type_id,language_id),
    KEY idx_attribute_type_id (attribute_type,attribute_type_id,language_id)
  ) ENGINE=MyISAM;");

  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
    array_push($resultMmessage, '<br /><b>Error Adding New Table</b> products_with_attributes_stock_attributes_non_stock. ' . $msg);
    return;
  }

  $sql_insert = "INSERT IGNORE INTO " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK_ATTRIBUTES_NON_STOCK . " (attribute_type, attribute_type_source_id, attribute_type_id, language_id) VALUES (:attribute_type:, :attribute_type_source_id:, :attribute_type_id:, :language_id:)";
  $sql_insert = $db->bindVars($sql_insert, ':attribute_type:', 'ignoredkeepempty', 'string');
  $sql_insert = $db->bindVars($sql_insert, ':attribute_type_source_id:', 0, 'integer');
  $sql_insert = $db->bindVars($sql_insert, ':attribute_type_id:', 0, 'integer');
  $sql_insert = $db->bindVars($sql_insert, ':language_id:', 1, 'integer');

  $result = $db->Execute($sql_insert);

  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
  }

  array_push($resultMmessage, '<br /><b>Added New Table</b> products_with_attributes_stock_attributes_non_stock. ' . $msg);
}

//Add new table products_with_attributes_stock
function addSBAtable(){
  global $db, $resultMmessage, $failed;

  /* //table expanded to support customid

    //table expanded to support customid, product_attribute_combo, title
    CREATE TABLE IF NOT EXISTS `products_with_attributes_stock` (
      `stock_id` int(11) NOT NULL AUTO_INCREMENT,
      `products_id` int(11) NOT NULL,
      `product_attribute_combo` varchar(255) DEFAULT NULL,
      `stock_attributes` varchar(255) NOT NULL,
      `quantity` float NOT NULL DEFAULT '0',
      `sort` int(11) NOT NULL DEFAULT '0',
      `customid` varchar(255) DEFAULT NULL,
      `title` varchar(50) DEFAULT NULL,
      PRIMARY KEY (`stock_id`),
      UNIQUE KEY `idx_products_id_stock_attributes` (`products_id`,`stock_attributes`),
      UNIQUE KEY `idx_products_id_attributes_id` (`product_attribute_combo`),
      UNIQUE KEY `idx_customid` (`customid`)
    );

    //TODO: New planned fields
     * `parentid` int(11) NOT NULL DEFAULT '0',
     * `siblingid` varchar(255) DEFAULT NULL,
     * `childid` varchar(255) DEFAULT NULL,
  */

  //Add Table for products_with_attributes_stock
  //New version of table with UNIQUE INDEX
  //check if the required tables if not already present
  if(!checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK)) {

    $msg = '';
    $result = $db->Execute("CREATE TABLE IF NOT EXISTS `".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK."` (
      `stock_id` int(11) NOT NULL AUTO_INCREMENT,
      `products_id` int(11) NOT NULL,
      `product_attribute_combo` varchar(255) DEFAULT NULL,
      `stock_attributes` varchar(255) NOT NULL,
      `quantity` float NOT NULL DEFAULT '0',
      `sort` int(11) NOT NULL DEFAULT '0',
      `customid` varchar(255) DEFAULT NULL,
      `title` varchar(100) DEFAULT NULL,
      PRIMARY KEY (`stock_id`)
      );"); 
    // Remove database assignment of unique keys that can and/or are managed by the code instead of the database.
    /*,
      UNIQUE KEY `idx_products_id_stock_attributes` (`products_id`,`stock_attributes`),
      UNIQUE KEY `idx_products_id_attributes_id` (`product_attribute_combo`),
      UNIQUE KEY `idx_customid` (`customid`)
    );");*/

    if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
      $msg = ' Error Message: ' . $db->error_message;
      $failed = true;
    }
    array_push($resultMmessage, '<br /><b>Added New Table</b> products_with_attributes_stock. ' . $msg);
  }
  else{
    //Alter / upgrade existing database table
    alterSBAtableSort();//Call function to Alter table products_with_attributes_stock sort field
    alterSBAtableCustomid();//Call function to Alter table products_with_attributes_stock to add customid
    alterSBAtableUniqueIndex();//Call function to Alter table products_with_attributes_stock UNIQUE INDEX
    alterSBAtableTitle();//call function to add new table field title
    alterSBAtableUniqueCombo();//call function to add new table field product_attribute_combo


    //TODO: New Fields, needs addition planning
    //alterSBAtableParentid();//call function to add new table field parentid
    //alterSBAtableSiblingid();//call function to add new table field siblingid
    //alterSBAtableChildid();//call function to add new table field childid
  }

  if (!checkSBAtable(TABLE_PRODUCTS_OPTIONS,'products_options_track_stock')) {
    alterProductOptions();
  }



  $msg = '';
  /*
   * CREATE TABLE orders_products_attributes_stock (
   * orders_products_attributes_stock_id int(11) NOT NULL auto_increment,
   * orders_products_attributes_id int(11) NOT NULL default '0',
   * orders_id int(11) NOT NULL default '0',
   * orders_products_id int(11) NOT NULL default '0',
   * stock_id int(11) NOT NULL default '0',
   * stock_attribute VARCHAR(255) NULL DEFAULT NULL,
   * customid varchar(255) DEFAULT NULL,
   * products_prid tinytext NOT NULL,
   * PRIMARY KEY (orders_products_attributes_stock_id),
   * KEY idx_orders_id_prod_id_zen (orders_id,orders_products_id),
   * KEY idx_orders_stock_id_stock_id (orders_products_attributes_stock_id,stock_id) )
   */

  if(checkSBAtable(TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK)) {
    return;
  }

  $result = $db->Execute("CREATE TABLE IF NOT EXISTS `".TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK."` (
  `orders_products_attributes_stock_id` INT(11) NOT NULL auto_increment,
  `orders_products_attributes_id` INT(11) NOT NULL default '0',
  `orders_id` INT(11) NOT NULL default '0',
  `orders_products_id` INT(11) NOT NULL default '0',
  `stock_id` INT(11) NOT NULL default '0',
  `stock_attribute` VARCHAR(255) NULL DEFAULT NULL,
  `customid` varchar(255) DEFAULT NULL,
  `products_prid` TINYTEXT NOT NULL,
  PRIMARY KEY (`orders_products_attributes_stock_id`),
  KEY idx_orders_id_prod_id_zen (`orders_id`,`orders_products_id`),
  KEY idx_orders_stock_id_stock_id (`orders_products_attributes_stock_id`,`stock_id`)
  )");

  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
  }
  array_push($resultMmessage, '<br />Added Table orders_products_with_attributes_stock: ' . $msg);
    //Alter / upgrade existing database table THIS NEEDS TO BE DEVELOPED
//    alterSBAtableSort();//Call function to Alter table products_with_attributes_stock sort field
//    alterSBAtableCustomid();//Call function to Alter table products_with_attributes_stock to add customid
//    alterSBAtableUniqueIndex();//Call function to Alter table products_with_attributes_stock UNIQUE INDEX
  return;
}

//Test that the table is already present, and that it does not already have the parentid field
//Upgrade existing table with parentid field
function alterSBAtableParentid(){
  global $db, $resultMmessage, $failed, $sniffer;

  if(!checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK) ) {
    return;
  }

/*    $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = '" . DB_DATABASE . "'
              AND TABLE_NAME = '". TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "'
              AND COLUMN_NAME = 'parentid';";
    $result = $db->Execute($sql);

    $num_rows = null;
    while (!$result->EOF) {
      if( $result->fields['COLUMN_NAME'] ){
        $num_rows = 1;
      }
      $result->MoveNext();
    }*/

  $field_exists = $sniffer->field_exists(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'parentid');

  if($field_exists){
    return;
  }
  //ADD COLUMN `parentid`
  $db->Execute("ALTER TABLE `" . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "`
              ADD COLUMN `parentid` int(11) NOT NULL DEFAULT '0' AFTER `title`;");

  if( (isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error) ){
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
    array_push($resultMmessage, '<b>Failure</b> while Adding parentid field to table products_with_attributes_stock. ' . $msg);
    return;
  }

  array_push($resultMmessage, '<b>Added</b> parentid field to table products_with_attributes_stock. ');

  return;
}

//Test that the table is already present, and that it does not already have the title field
//Upgrade existing table with title field
function alterSBAtableTitle(){
  global $db, $resultMmessage, $failed, $sniffer;

  if(!checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK) ) {
    return;
  }

/*    $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = '" . DB_DATABASE . "'
              AND TABLE_NAME = '". TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "'
              AND COLUMN_NAME = 'title';";
    $result = $db->Execute($sql);

    $num_rows = null;
    while (!$result->EOF) {
      if( $result->fields['COLUMN_NAME'] ){
        $num_rows = 1;
      }
      $result->MoveNext();
    }*/

  $field_exists = $sniffer->field_exists(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'title');

  if($field_exists){
    return;
  }

  //ADD COLUMN `title`
  $db->Execute("ALTER TABLE `" . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "`
              ADD COLUMN `title` varchar(100) DEFAULT NULL AFTER `customid`;");

  if( (isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error) ){
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
    array_push($resultMmessage, '<b>Failure</b> while Adding title field to table products_with_attributes_stock. ' . $msg);
    return;
  }

  array_push($resultMmessage, '<b>Added</b> title field to table products_with_attributes_stock. ');

  return;
}

//Test that the table is already present, and that it does not already have the product_attribute_combo field
//Upgrade existing table with product_attribute_combo field
function alterSBAtableUniqueCombo(){
  global $db, $resultMmessage, $failed, $sniffer;

  if(!checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK) ) {
    return;
  }

/*    $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = '" . DB_DATABASE . "'
              AND TABLE_NAME = '". TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "'
              AND COLUMN_NAME = 'product_attribute_combo';";
    $result = $db->Execute($sql);

    $num_rows = null;
    while (!$result->EOF) {
      if( $result->fields['COLUMN_NAME'] ){
        $num_rows = 1;
      }
      $result->MoveNext();
    }*/

  $field_exists = $sniffer->field_exists(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'product_attribute_combo');

  if($field_exists){
    return;
  }

  //ADD COLUMN `product_attribute_combo`
  $db->Execute("ALTER TABLE `" . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "`
              ADD COLUMN `product_attribute_combo` varchar(255) DEFAULT NULL AFTER `products_id`;");

  if( (isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error) ){
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
    array_push($resultMmessage, '<b>Failure</b> while Adding product_attribute_combo field to table products_with_attributes_stock. ' . $msg);
  }
  else{
    //ADD UNIQUE INDEX idx_products_id_attributes_id
    $db->Execute("ALTER TABLE " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "
                ADD UNIQUE INDEX idx_products_id_attributes_id (`product_attribute_combo`);");
  }

  if( !(isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error) ){
    array_push($resultMmessage, '<b>Added</b> product_attribute_combo field to table products_with_attributes_stock. ');
    return;
  }

  $msg = ' Error Message: ' . $db->error_message;
  array_push($resultMmessage, '<b>Failure</b> while Adding UNIQUE INDEX idx_products_id_attributes_id to table products_with_attributes_stock. ' . $msg);
  $failed = true;

  return;
}

//Test that the table is already present, and that it does not already have the customid field
//Upgrade existing table with customid field
function alterSBAtableCustomid(){
  global $db, $resultMmessage, $failed, $sniffer;

  if(!checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK) ) {
    return;
  }

/*    $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = '" . DB_DATABASE . "'
              AND TABLE_NAME = '". TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "'
              AND COLUMN_NAME = 'customid';";
    $result = $db->Execute($sql);

    $num_rows = null;
    while (!$result->EOF) {
      if( $result->fields['COLUMN_NAME'] ){
        $num_rows = 1;
        break; // mc12345678 does not appear to be a need to continue looping if entered this if.
      }
      $result->MoveNext();
    }*/

  $field_exists = $sniffer->field_exists(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'customid');

  if($field_exists) {
    return;
  }

  //ADD COLUMN `customid`
  $db->Execute("ALTER TABLE `" . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "`
              ADD COLUMN `customid` VARCHAR(255) NULL DEFAULT NULL AFTER `sort`;");

  if( (isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error) ){
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
    array_push($resultMmessage, '<b>Failure</b> while Adding customid field to table products_with_attributes_stock. ' . $msg);
  }
  else{
    //ADD UNIQUE INDEX idx_customid
    $db->Execute("ALTER TABLE " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "
                ADD UNIQUE INDEX idx_customid (`customid`);");
  }

  if( !((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)) ){
    array_push($resultMmessage, '<b>Added</b> customid field to table products_with_attributes_stock. ');
    return;
  }
  $msg = ' Error Message: ' . $db->error_message;
  array_push($resultMmessage, '<b>Failure</b> while Adding UNIQUE INDEX idx_customid to table products_with_attributes_stock. ' . $msg);
  $failed = true;


  return;
}

//Test that the table is already present, and that it does not already have the UNIQUE INDEX
//Upgrade existing table with UNIQUE INDEX
function alterSBAtableUniqueIndex(){
  global $db, $resultMmessage, $failed, $sniffer;

/*  $sql = "SELECT * FROM information_schema.statistics
      WHERE table_schema = '".DB_DATABASE."'
      AND table_name = '". TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "'
      AND column_name = 'products_id'";
  $result = $db->Execute($sql);

  $num_rows = null;
  while (!$result->EOF) {
    if( $result->fields['COLUMN_NAME'] ){
      $num_rows = 1;
      break; // mc12345678 does not appear to be a need to continue looping if entered this if.
    }
    $result->MoveNext();
  }*/

  $field_exists = $sniffer->field_exists(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'products_id');

  // Don't try to add the index if the products_id field is already present.
  if($field_exists) {
    return;
  }

  //test for records that are not unique before adding UNIQUE INDEX
  $sql = "SELECT pas.stock_id, COUNT(pas.stock_id) AS stockCount
      FROM " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " pas
      GROUP BY pas.products_id, pas.stock_attributes
      HAVING stockCount > 1";
  $result = $db->Execute($sql);

  while (!$result->EOF) {
    if (!$result->fields['stockCount']) {
      $result->MoveNext();
      continue;
    }

    $failed = true;
    array_push($resultMmessage, 'FAILURE: Can not add UNIQUE INDEX (products_id, stock_attributes) to the products_with_attributes_stock table, there are records that are not unique!');
    return; // No need to continue in loop as have met a failing condition.
  }

  $msg = '';
  $sql = "ALTER TABLE " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " ADD UNIQUE INDEX idx_products_id_stock_attributes (`products_id`, `stock_attributes`);"; //If this is going to be different than the previous version, then there should be part of the upgrade process that removes the old version(s).
  $db->Execute($sql);
  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
  }
  array_push($resultMmessage, '<b>Altered Table</b> products_with_attributes_stock to add UNIQUE INDEX idx_products_id_stock_attributes (products_id, stock_attributes). ' . $msg);

  return;
}

function alterProductOptions(){


/*  ALTER TABLE products_options
  ADD products_options_track_stock tinyint(4) default '1' not null
  AFTER products_options_name;*/
  global $db, $resultMmessage, $failed, $sniffer;

/*  $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA = '".DB_DATABASE."'
            AND TABLE_NAME = '". TABLE_PRODUCTS_OPTIONS . "'
            AND COLUMN_NAME = 'products_options_track_stock';";
  $result = $db->Execute($sql);

   $num_rows = null;
   while (!$result->EOF) {
     if( $result->fields['COLUMN_NAME'] ){
       $num_rows = 1;
       break;
     }
     $result->MoveNext();
   }*/

  $field_exists = $sniffer->field_exists(TABLE_PRODUCTS_OPTIONS, 'products_options_track_stock');

  if(!$field_exists){
    $msg = '';
    $sql = "ALTER TABLE " . TABLE_PRODUCTS_OPTIONS." ADD products_options_track_stock tinyint(4) DEFAULT '1' NOT NULL AFTER `products_options_name`";
    $db->Execute($sql);
    if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
      $msg = ' Error Message: ' . $db->error_message;
      $failed = true;
    }
    array_push($resultMmessage, '<b>Altered Table</b> products_options to add products_options_track_stock. ' . $msg);
    return;
  }
  $sql = "SELECT column_default
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = '".DB_DATABASE."'
        AND TABLE_NAME = '".TABLE_PRODUCTS_OPTIONS."'
        AND COLUMN_NAME = 'products_options_track_stock'";
  $result = $db->Execute($sql);

  if (!$result->EOF || isset($result->fields['column_default'])) {
    return;
  }

  $msg = '';
  $sql = "ALTER TABLE " . TABLE_PRODUCTS_OPTIONS." CHANGE COLUMN `products_options_track_stock` `products_options_track_stock` tinyINT(4) NOT NULL DEFAULT '1' AFTER `products_options_name`;";
  $db->Execute($sql);

  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
  }

  array_push($resultMmessage, '<br /><b>Altered Table</b> products_options to add DEFAULT value of 1. ' . $msg);

  return;

}

//Test that the table is already present, and that it does not already have "sort INT NOT NULL"
//Upgrade existing table with "sort INT NOT NULL"
function alterSBAtableSort(){
  global $db, $resultMmessage, $failed, $sniffer;

/*  $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA = '".DB_DATABASE."'
            AND TABLE_NAME = '". TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "'
            AND COLUMN_NAME = 'sort';";
  $result = $db->Execute($sql);

   $num_rows = null;
   while (!$result->EOF) {
     if( $result->fields['COLUMN_NAME'] ){
       $num_rows = 1;
       break;
     }
     $result->MoveNext();
   }*/

  $msg = '';
  $field_exists = $sniffer->field_exists(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'sort');

  if(!$field_exists){
    $sql = "ALTER TABLE " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." ADD sort INT(11) NOT NULL DEFAULT 0 AFTER `quantity`";
    $db->Execute($sql);
    if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
      $msg = ' Error Message: ' . $db->error_message;
      $failed = true;
    }
    array_push($resultMmessage, '<b>Altered Table</b> products_with_attributes_stock to add sort. ' . $msg);
    return;
  }

  $sql = "SELECT column_default
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = '".DB_DATABASE."'
        AND TABLE_NAME = '".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK."'
        AND COLUMN_NAME = 'sort'";
  $result_query = $db->Execute($sql);

  if (!$result_query->EOF || isset($result_query->fields['column_default'])) {
    return;
  }
//  $result = isset($result_query->fields['column_default']) ? $result_query->fields['column_default'] : null;

//  if( $result_query->EOF || null === $result){
  $sql = "ALTER TABLE " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." CHANGE COLUMN `sort` `sort` INT(11) NOT NULL DEFAULT 0 AFTER `quantity`;";
  $db->Execute($sql);

  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
  }

  array_push($resultMmessage, '<br /><b>Altered Table</b> products_with_attributes_stock to add DEFAULT value of 0. ' . $msg);
//  }

  return;
}

//Empty TRUNCATE the Product Attribute Stock Table
//Only needed it user wants to start over in the process of configuring the table without having to un-install the mod
function truncateProductAttributeStockTable(){
  //TRUNCATE `products_with_attributes_stock`;
  global $db, $resultMmessage, $failed;

  if(!checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, null, false)) {
    return;
  }

  $sql = "TRUNCATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.";";

  $db->Execute($sql);
  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
  }
  array_push($resultMmessage, 'Empty '.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.' table ' . $msg);

  return;
}

function convertDropdownsToSBA()
{
  global $db, $resultMmessage, $failed;

  $msg = '';

  if (!defined('PRODUCTS_OPTIONS_TYPE_SELECT_SBA')) {
    $msg = ' Error Message: PRODUCTS_OPTIONS_TYPE_SELECT_SBA not defined.';
    $failed = true;
    array_push($resultMmessage, 'product_attribute_combo field updated ' . $msg);
    return;
  }

  $sql = "UPDATE " . TABLE_PRODUCTS_OPTIONS . " SET `products_options_type` = :products_options_type_select_sba:
          WHERE `products_options_type` = :products_options_type_select:";

  $sql = $db->bindVars($sql, ':products_options_type_select_sba:', PRODUCTS_OPTIONS_TYPE_SELECT_SBA, 'integer');
  $sql = $db->bindVars($sql, ':products_options_type_select:', PRODUCTS_OPTIONS_TYPE_SELECT, 'integer');

  $db->Execute($sql);
  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
  }

  array_push($resultMmessage, 'product_attribute_combo field updated ' . $msg);

}

function convertSBAToSBA()
{
  global $db, $resultMmessage, $failed;

  $msg = '';

  if (!defined('PRODUCTS_OPTIONS_TYPE_SELECT_SBA')) {
    $msg = ' Error Message: PRODUCTS_OPTIONS_TYPE_SELECT_SBA not defined.';
    $failed = true;

    array_push($resultMmessage, 'product_attribute_combo field updated ' . $msg);
    return;
  }

  $results_track = array(); // Array to track what has been identified.

  // Need to identify which option values are listed in the SBA table and then update them if they are a dropdown select.
  $sql = 'SELECT stock_attributes FROM ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' WHERE stock_attributes != \'\'';

  $results = $db->Execute($sql);

  while (!$results->EOF)
  {
    $results_array = explode(',', $results->fields['stock_attributes']);

    // Need one or more checks before using the results_array
    foreach ($results_array as $key=> $value)
    {
      $products_options_id_sql = 'SELECT options_id FROM ' . TABLE_PRODUCTS_ATTRIBUTES . ' WHERE products_attributes_id = :products_attributes_id:';
      $products_options_id_sql = $db->bindVars($products_options_id_sql, ':products_attributes_id:', $value, 'integer');

      if (method_exists($db, 'ExecuteNoCache')) {
        $products_options_id = $db->ExecuteNoCache($products_options_id_sql);
      } else {
        $products_options_id = $db->Execute($products_options_id_sql, false, false, 0, true);
      }

      $product_type_sql = 'SELECT products_options_type FROM ' . TABLE_PRODUCTS_OPTIONS . ' WHERE products_options_id = :products_options_id:';
      $product_type_sql = $db->bindVars($product_type_sql, ':products_options_id:', $products_options_id->fields['options_id'], 'integer');

      if (method_exists($db, 'ExecuteNoCache')) {
        $product_type = $db->ExecuteNoCache($product_type_sql);
      } else {
        $product_type = $db->Execute($product_type_sql, false, false, 0, true);
      }

      // Since converting select type to SBA select, don't do anything to the list unless it is a select.
      if ($product_type->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_SELECT) {
        continue;
      }

      if (!isset($results_track[$products_options_id->fields['options_id']])) {
        $results_track[$products_options_id->fields['options_id']] = $products_options_id->fields['options_id'];
        // Do update here? or wait till later?
      }
    }
    unset($results_array);

    $results->MoveNext();
  }

  unset($results);

  sort($results_track); // This will sequence the option_ids so that the "completion" point is better understood.

  foreach ($results_track as $result_key => $result)
  {
    $sql = "UPDATE " . TABLE_PRODUCTS_OPTIONS . " po SET po.products_options_type = :products_options_type:
            WHERE `products_options_id` = :products_options_id:";

    $sql = $db->bindVars($sql, ':products_options_type:', PRODUCTS_OPTIONS_TYPE_SELECT_SBA, 'integer');
    $sql = $db->bindVars($sql, ':products_options_id:', $result, 'integer');

    $db->Execute($sql);

    if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
      $msg = ' Error Message: ' . $db->error_message;
      $failed = true;

      break;
    }
  }
  unset($results_track);

  array_push($resultMmessage, 'product_attribute_combo field updated ' . $msg);
}

function convertNonSBAToDropdown()
{
  global $db, $resultMmessage, $failed;

  $msg = '';

  if (!defined('PRODUCTS_OPTIONS_TYPE_SELECT_SBA')) {
    $msg = ' Error Message: PRODUCTS_OPTIONS_TYPE_SELECT_SBA not defined.';
    $failed = true;

    array_push($resultMmessage, 'product_attribute_combo field updated ' . $msg);
    return;
  }

  $results_track = array(); // Array to track what has been identified.

  // Need to identify which option values are listed in the SBA table and then update them if they are a dropdown select.
  $sql = 'SELECT stock_attributes FROM ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' WHERE stock_attributes != \'\'';

  if (method_exists($db, 'ExecuteNoCache')) {
    $results = $db->ExecuteNoCache($sql);
  } else {
    $results = $db->Execute($sql, false, false, 0, true);
  }
//    $results = $db->Execute($sql);

  while (!$results->EOF)
  {
    $results_array = explode(',', $results->fields['stock_attributes']);

    // Need one or more checks before using the results_array
    foreach ($results_array as $key=> $value)
    {
      $products_options_id_sql = 'SELECT options_id FROM ' . TABLE_PRODUCTS_ATTRIBUTES . ' WHERE products_attributes_id = :products_attributes_id:';
      $products_options_id_sql = $db->bindVars($products_options_id_sql, ':products_attributes_id:', $value, 'integer');

      if (method_exists($db, 'ExecuteNoCache')) {
        $products_options_id = $db->ExecuteNoCache($products_options_id_sql);
      } else {
        $products_options_id = $db->Execute($products_options_id_sql, false, false, 0, true);
      }

      $product_type_sql = 'SELECT products_options_type FROM ' . TABLE_PRODUCTS_OPTIONS . ' WHERE products_options_id = :products_options_id:';
      $product_type_sql = $db->bindVars($product_type_sql, ':products_options_id:', $products_options_id->fields['options_id'], 'integer');

      if (method_exists($db, 'ExecuteNoCache')) {
        $product_type = $db->ExecuteNoCache($product_type_sql);
      } else {
        $product_type = $db->Execute($product_type_sql, false, false, 0, true);
      }

      // If the option type isn't the SBA Select item, then no work could need to be done so continue searching.
      if ($product_type->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_SELECT_SBA) {
        continue;
      }

      if (empty($results_track) || !isset($results_track[$products_options_id->fields['options_id']])) {
        $results_track[$products_options_id->fields['options_id']] = $products_options_id->fields['options_id']; // This value holds all of the SBA product that have an options_id assigned to the SBA Select
        // Do update here? or wait till later?
      }
    }
    unset($results_array);

    $results->MoveNext();
  }

  unset($results);

  // Need to pull all of the option_ids that are assigned to the SBA select type to be able to cross them off of the previously discovered list.

  $sql = 'SELECT products_options_id FROM ' . TABLE_PRODUCTS_OPTIONS . ' WHERE products_options_type = :products_options_type:';
  $sql = $db->bindVars($sql, ':products_options_type:', PRODUCTS_OPTIONS_TYPE_SELECT_SBA, 'integer');

  if (method_exists($db, 'ExecuteNoCache')) {
    $sba_select_options = $db->ExecuteNoCache($sql);
  } else {
    $sba_select_options = $db->Execute($sql, false, false, 0, true);
  }

  // Remove from the list of SBA identified SBA select options and add to the list those identified but not associated with an SBA product.
  while (!$sba_select_options->EOF) {
    if (array_key_exists($sba_select_options->fields['products_options_id'], $results_track)) {
      unset($results_track[$sba_select_options->fields['products_options_id']]);
    } else {
      $results_track[$sba_select_options->fields['products_options_id']] = $sba_select_options->fields['products_options_id'];
    }

    $sba_select_options->MoveNext();
  }

  foreach ($results_track as $result_key => $result)
  {
    $sql = "UPDATE " . TABLE_PRODUCTS_OPTIONS . " po SET po.products_options_type = :products_options_type:
            WHERE `products_options_id` = :products_options_id:";

    $sql = $db->bindVars($sql, ':products_options_type:', PRODUCTS_OPTIONS_TYPE_SELECT, 'integer');
    $sql = $db->bindVars($sql, ':products_options_id:', $result, 'integer');

    $db->Execute($sql);

    if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
      $msg = ' Error Message: ' . $db->error_message;
      $failed = true;

      break;
    }
  }
  unset($results_track);

  array_push($resultMmessage, 'product_attribute_combo field updated ' . $msg);
}

//Used to fill the new field product_attribute_combo (Unique Combo)
function updateProductAttributeCombo(){
  /*
  UPDATE `znc_products_with_attributes_stock` SET `product_attribute_combo` =
  replace(
  (SELECT CONCAT(znc_products_with_attributes_stock.products_id,'-',znc_products_with_attributes_stock.stock_attributes))
  , ',','-')
   */
  global $db, $resultMmessage, $failed;

  if(!checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, null, false)) {
    return;
  }

  $msg = '';
  $sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET `product_attribute_combo` =
      replace(
      (SELECT CONCAT(".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.".products_id,'-',".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.".stock_attributes))
      , ',','-');";

  $db->Execute($sql);
  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
  }
  array_push($resultMmessage, 'product_attribute_combo field updated ' . $msg);

  return;
}

//Install Optional SQL
// Default version.
// This will only add the product attributes that are NOT display-only AND are NOT the new "SBA" selections
function installOptionalSQL1(){
  /*
  INSERT INTO products_with_attributes_stock (products_id, stock_attributes, quantity)

    SELECT p.products_id, pa.products_attributes_id, p.products_quantity
    FROM products p
      LEFT JOIN products_attributes pa ON (p.products_id = pa.products_id)
      LEFT JOIN products_options_values pv ON (pa.options_values_id = pv.products_options_values_id)
      LEFT JOIN products_options_values_to_products_options povpo ON (pv.products_options_values_id = povpo.products_options_values_id)
      LEFT JOIN products_options po ON(povpo.products_options_id = po.products_options_id)
      LEFT JOIN products_options_types pot ON (po.products_options_type = pot.products_options_types_id)

    WHERE pa.products_attributes_id is not null
      AND pa.options_values_id > 0
      AND pa.attributes_display_only = 0
      AND pot.products_options_types_name NOT LIKE "SBA%"
    ORDER BY p.products_id, pa.products_attributes_id

    ON DUPLICATE KEY UPDATE
      `products_id` = products_with_attributes_stock.products_id;
   */

  global $db, $resultMmessage, $failed;
  //use 'p.products_quantity' to get the quantity from the product table
  //Use any value you require if you want to set all attribute variants to a specific number such as 0
  //example: $insertQtyValue = 0;
  $msg = '';
  $insertQtyValue = 'p.products_quantity';

  //check if the required tables is present
  if(!checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, null, false)) {
    array_push($resultMmessage, 'Optional SQL file result: Did NOT run, table does not exist.');
    $failed = true;
    return;
  }

  $sql = "INSERT INTO ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." (products_id, stock_attributes, quantity)

      SELECT p.products_id, pa.products_attributes_id, $insertQtyValue

      FROM ".TABLE_PRODUCTS." p
        LEFT JOIN ".TABLE_PRODUCTS_ATTRIBUTES." pa ON (p.products_id = pa.products_id)
        LEFT JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES." pv ON (pa.options_values_id = pv.products_options_values_id)
        LEFT JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS." povpo ON (pv.products_options_values_id = povpo.products_options_values_id)
        LEFT JOIN ".TABLE_PRODUCTS_OPTIONS." po ON(povpo.products_options_id = po.products_options_id)
        LEFT JOIN ".TABLE_PRODUCTS_OPTIONS_TYPES." pot ON (po.products_options_type = pot.products_options_types_id)

      WHERE pa.products_attributes_id is not null
        AND pa.options_values_id > 0
        AND pa.attributes_display_only = 0
        AND pot.products_options_types_name NOT LIKE 'SBA%'
      ORDER BY p.products_id, pa.products_attributes_id

      ON DUPLICATE KEY UPDATE
        `products_id` = ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.".products_id;";

  $db->Execute($sql);
  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
  }
  zen_record_admin_activity('Inserted SBA optional SQL 1 via the install file.', 'warning');
  array_push($resultMmessage, 'Optional SQL file complete. ' . $msg);

  return;
}

//Install Optional SQL
//This will add all the products attributes
function installOptionalSQL2(){
  /*
  INSERT INTO products_with_attributes_stock (products_id, stock_attributes, quantity)

    SELECT p.products_id, pa.products_attributes_id, p.products_quantity
    FROM products p
      LEFT JOIN products_attributes pa ON (p.products_id = pa.products_id)
      LEFT JOIN products_options_values pv ON (pa.options_values_id = pv.products_options_values_id)
    WHERE pa.products_attributes_id is not null
      AND pa.options_values_id > 0
    ORDER BY p.products_id, pa.products_attributes_id

    ON DUPLICATE KEY UPDATE
      `products_id` = products_with_attributes_stock.products_id;
   */

  global $db, $resultMmessage, $failed;
  //use 'p.products_quantity' to get the quantity from the product table
  //Use any value you require if you want to set all attribute variants to a specific number such as 0
  //example: $insertQtyValue = 0;
  $insertQtyValue = 'p.products_quantity';

  $msg = '';

  //check if the required table is present
  if(!checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, null, false)) {
    array_push($resultMmessage, 'Optional SQL file result: Did NOT run, table does not exit.');
    $failed = true;
    return;
  }

  $sql = "INSERT INTO ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." (products_id, stock_attributes, quantity)

  SELECT p.products_id, pa.products_attributes_id, $insertQtyValue

  FROM ".TABLE_PRODUCTS." p

    LEFT JOIN ".TABLE_PRODUCTS_ATTRIBUTES." pa ON (p.products_id = pa.products_id)
    LEFT JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES." pv ON (pa.options_values_id = pv.products_options_values_id)

  WHERE pa.products_attributes_id is not null
    AND pa.options_values_id > 0

  ORDER BY p.products_id, pa.products_attributes_id

  ON DUPLICATE KEY UPDATE
    `products_id` = ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.".products_id;";

  $db->Execute($sql);
  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
  $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
  }
  array_push($resultMmessage, 'Optional SQL file complete. ' . $msg);
  zen_record_admin_activity('Inserted SBA optional SQL 2 via the install file.', 'warning');

  return;
}

//Install Optional SQL
//This will add only the display-only product attributes
function installOptionalSQL3(){
  /*
  INSERT INTO products_with_attributes_stock (products_id, stock_attributes, quantity)

  SELECT p.products_id, pa.products_attributes_id, p.products_quantity
  FROM products p
    LEFT JOIN products_attributes pa ON (p.products_id = pa.products_id)
    LEFT JOIN products_options_values pv ON (pa.options_values_id = pv.products_options_values_id)
  WHERE pa.products_attributes_id is not null
    AND pa.options_values_id > 0
    AND pa.attributes_display_only = 1
  ORDER BY p.products_id, pa.products_attributes_id

  ON DUPLICATE KEY UPDATE
  `products_id` = products_with_attributes_stock.products_id;
  */

  global $db, $resultMmessage, $failed;
  //use 'p.products_quantity' to get the quantity from the product table
  //Use any value you require if you want to set all attribute variants to a specific number such as 0
  //example: $insertQtyValue = 0;
  $insertQtyValue = 'p.products_quantity';

  $msg = '';

  //check if the required table is present
  if(!checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, null, false)) {
    array_push($resultMmessage, 'Optional SQL file result: Did NOT run, table does not exit.');
    $failed = true;
    return;
  }

  $sql = "INSERT INTO ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." (products_id, stock_attributes, quantity)

  SELECT p.products_id, pa.products_attributes_id, $insertQtyValue

  FROM ".TABLE_PRODUCTS." p
      LEFT JOIN ".TABLE_PRODUCTS_ATTRIBUTES." pa ON (p.products_id = pa.products_id)
      LEFT JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES." pv ON (pa.options_values_id = pv.products_options_values_id)

    WHERE pa.products_attributes_id is not null
      AND pa.options_values_id > 0
      AND pa.attributes_display_only = 1

    ORDER BY p.products_id, pa.products_attributes_id

    ON DUPLICATE KEY UPDATE
      `products_id` = ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.".products_id;";

  $db->Execute($sql);
  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
  }
  array_push($resultMmessage, 'Optional SQL file complete. ' . $msg);
  zen_record_admin_activity('Inserted SBA optional SQL 3 via the install file.', 'warning');

  return;
}

//Install Optional SQL
// This will only add the product attributes that are NOT display-only
function installOptionalSQL4(){
  /*
  INSERT INTO products_with_attributes_stock (products_id, stock_attributes, quantity)

    SELECT p.products_id, pa.products_attributes_id, p.products_quantity
    FROM products p
      LEFT JOIN products_attributes pa ON (p.products_id = pa.products_id)
      LEFT JOIN products_options_values pv ON (pa.options_values_id = pv.products_options_values_id)
    WHERE pa.products_attributes_id is not null
      AND pa.options_values_id > 0
      AND pa.attributes_display_only = 0
    ORDER BY p.products_id, pa.products_attributes_id

  ON DUPLICATE KEY UPDATE
    `products_id` = products_with_attributes_stock.products_id;
  */

  global $db, $resultMmessage, $failed;
  //use 'p.products_quantity' to get the quantity from the product table
  //Use any value you require if you want to set all attribute variants to a specific number such as 0
  //example: $insertQtyValue = 0;
  $insertQtyValue = 'p.products_quantity';

  $msg = '';

  //check if the required tables is present
  if(!checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, null, false)) {
    array_push($resultMmessage, 'Optional SQL file result: Did NOT run, table does not exit.');
    $failed = true;
    return;
  }

  $sql = "INSERT INTO ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." (products_id, stock_attributes, quantity)

  SELECT p.products_id, pa.products_attributes_id, $insertQtyValue

  FROM ".TABLE_PRODUCTS." p
        LEFT JOIN ".TABLE_PRODUCTS_ATTRIBUTES." pa ON (p.products_id = pa.products_id)
        LEFT JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES." pv ON (pa.options_values_id = pv.products_options_values_id)

      WHERE pa.products_attributes_id is not null
        AND pa.options_values_id > 0
        AND pa.attributes_display_only = 0
      ORDER BY p.products_id, pa.products_attributes_id

      ON DUPLICATE KEY UPDATE
        `products_id` = ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.".products_id;";

  $db->Execute($sql);
  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
  }
  zen_record_admin_activity('Inserted SBA optional SQL 4 via the install file.', 'warning');
  array_push($resultMmessage, 'Optional SQL file complete. ' . $msg);

  return;
}

//Install Optional SQL
// This will remove readonly product attributes from applicable products
function installOptionalSQL5(){
  /*

  SELECT p.products_id, pa.products_attributes_id, p.products_quantity
  FROM products p
    LEFT JOIN products_attributes pa ON (p.products_id = pa.products_id)
    LEFT JOIN products_options_values pv ON (pa.options_values_id = pv.products_options_values_id)
  WHERE pa.products_attributes_id is not null
    AND pa.options_values_id > 0
    AND pa.attributes_display_only = 1
  ORDER BY p.products_id, pa.products_attributes_id

  */

  global $db, $resultMmessage, $failed;
  //use 'p.products_quantity' to get the quantity from the product table
  //Use any value you require if you want to set all attribute variants to a specific number such as 0
  //example: $insertQtyValue = 0;
  $insertQtyValue = 'p.products_quantity';

  $msg = '';

  //check if the required table is present
  if(!checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, null, false)) {
    array_push($resultMmessage, 'Optional SQL file result: Did NOT run, table does not exist.');
    $failed = true;
    return;
  }

  $sql =   "SELECT p.products_id, pa.products_attributes_id

  FROM ".TABLE_PRODUCTS." p
      LEFT JOIN ".TABLE_PRODUCTS_ATTRIBUTES." pa ON (p.products_id = pa.products_id)
      LEFT JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES." pv ON (pa.options_values_id = pv.products_options_values_id)
      LEFT JOIN ".TABLE_PRODUCTS_OPTIONS." po ON (po.products_options_id = pa.options_id)

    WHERE pa.products_attributes_id is not null
      AND pa.options_values_id > 0
      AND po.products_options_type = 5

    ORDER BY p.products_id, pa.products_attributes_id";

  $prods_readonly_result = $db->Execute($sql);

  /*
  * Have a list of products with their matching readonly attribute(s)
  */

  while(!$prods_readonly_result->EOF) {
    $attribute_stock_query = "select pwas.stock_id, pwas.stock_attributes from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " pwas where pwas.products_id = :products_id: AND pwas.stock_attributes like (:products_attributes_id:) OR pwas.stock_attributes like CONCAT(:products_attributes_id:,',%') or pwas.stock_attributes like CONCAT('%,',:products_attributes_id:,',%') or pwas.stock_attributes like CONCAT('%,',:products_attributes_id:)";

    $attribute_stock_query = $db->bindVars($attribute_stock_query, ':products_id:', $prods_readonly_result->fields['products_id'], 'integer');
    $attribute_stock_query = $db->bindVars($attribute_stock_query, ':products_attributes_id:', $prods_readonly_result->fields['products_attributes_id'], 'integer');

    $attribute_stock = $db->Execute($attribute_stock_query);
    while (!$attribute_stock->EOF) {
      $attributes_id = array();
      $attributes_id = explode(',', $attribute_stock->fields['stock_attributes']);
      foreach ($attributes_id as $loc => $sing_attribute) {
        if ($sing_attribute != $prods_readonly_result->fields['products_attributes_id']) {
          continue;
        }

        unset($attributes_id[$loc]);
      }
      if (!empty($attributes_id)) {

        $sql = "SELECT pwas.stock_id FROM " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " pwas where products_id = :products_id: and stock_attributes = :stock_attributes:";
        $sql = $db->bindVars($sql, ':stock_attributes:', implode(',',$attributes_id), 'string');
        $sql = $db->bindVars($sql, ':products_id:', $prods_readonly_result->fields['products_id'], 'integer');
        $sql_result = $db->Execute($sql);

        if ($sql_result->RecordCount()) {
          $sql = "DELETE FROM " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " WHERE stock_id = :stock_id: and stock_attributes = :stock_attributes:";
        } else {
          $sql = "UPDATE " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " SET stock_attributes = :stock_attributes: where stock_id = :stock_id:";
        }

        $sql = $db->bindVars($sql, ':stock_attributes:', implode(',',$attributes_id), 'string');
      } else {
        //Apparently removed all of the data associated with this record and the record should be deleted as there is nothing remaining to track.
        $sql = "DELETE FROM " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " WHERE stock_id = :stock_id:";
        // $_SESSION['delete'.$attribute_stock->fields['stock_id']] = 'yes';
      }

      $sql = $db->bindVars($sql, ':stock_id:', $attribute_stock->fields['stock_id'], 'integer');
      $db->Execute($sql);

      $attribute_stock->MoveNext();
    } // End of PWAS loop
    $prods_readonly_result->MoveNext();
  } //End of Products loop

  if((isset($db->error_number) && $db->error_number) || (isset($db->error) && $db->error)){
    $msg = ' Error Message: ' . $db->error_message;
    $failed = true;
  }
  array_push($resultMmessage, 'Optional SQL file complete. ' . $msg);
  zen_record_admin_activity('Inserted SBA optional SQL 5 via the install file.', 'warning');

  return;
}

//test to see if database table already exists
function checkSBAtable($table = null, $field = null, $display = true) {

  global $db, $resultMmessage;
  $result = null;
  static $setTrue = false;

  $check = $db->Execute("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = '".DB_DATABASE."'
              AND TABLE_NAME = '". $table . "'
              AND COLUMN_NAME like '%".$field."%'");

  while (!$check->EOF) {
    if (empty($check->fields['COLUMN_NAME'])) {
      $check->MoveNext();
      continue;
    }

    $result .= $check->fields['COLUMN_NAME'] . ' | ';

    $check->MoveNext();
  }

  //limits the number of time this gets displayed, since it is call many times
  //This $resultMmessage is for general information only
  if($setTrue == false and $result and $display == true){
     array_push($resultMmessage, "<br />&bull; <b>$table Table Fields:</b> " . $result);
     $setTrue = true;
  }

  //if there are any fields than we assume the table already exists
  return $check->fields['COLUMN_NAME'];
}

//test for proper placement of NEW files
function checkSBAfileLocation(){
  global $db, $resultMmessage, $failed, $template_dir;
  $result = null;

  /*
  CORE
  admin/invoice.php
  admin/orders.php
  admin/packingslip.php
  admin/includes/functions/general.php
  admin/includes/classes/order.php

  admin/attributes_controller.php
  admin/options_name_manager.php
  admin/options_values_manager.php

  includes/auto_loaders/config.products_with_attributes_stock.php
  includes/classes/observers/class.products_with_attributes_stock.php
  includes/extra_cart_actions/stock_by_attributes.php
  includes/extra_datafiles/products_with_attributes_stock_database_tables.php
  includes/modules/pages/checkout_success/header_php_sba.php

  includes/classes/order.php
  includes/functions/functions_lookups.php
  includes/functions/extra_functions/products_with_attributes.php
  includes/modules/pages/checkout_shipping/header_php.php
  includes/modules/pages/shopping_cart/header_php.php

  OVERRIDE
  includes/modules/YOUR_TEMPLATE/attributes.php
  includes/templates/YOUR_TEMPLATE/templates/tpl_shopping_cart_default.php
  includes/templates/YOUR_TEMPLATE/templates/tpl_account_history_info_default.php
  includes/templates/YOUR_TEMPLATE/templates/tpl_checkout_confirmation_default.php
  */

  // Check to make sure all new files have been uploaded.
  // These are not intended to be perfect checks, just a quick 'Hey look at this!!'.
  $files = array(

    DIR_FS_CATALOG_TEMPLATES . $template_dir . '/jscript/jquery.form.js',

    DIR_FS_ADMIN . 'products_with_attributes_stock_ajax.js',
    DIR_FS_ADMIN . 'products_with_attributes_stock.php',
    DIR_FS_ADMIN . 'products_with_attributes_stock_ajax.php',

    DIR_FS_ADMIN . DIR_WS_INCLUDES . 'products_with_attributes_stock_ajax.css',
    DIR_FS_ADMIN . DIR_WS_INCLUDES . 'classes/products_with_attributes_stock.php',
    DIR_FS_ADMIN . DIR_WS_INCLUDES . 'extra_datafiles/products_with_attributes_stock.php',
    DIR_FS_ADMIN . DIR_WS_INCLUDES . 'languages/english/products_with_attributes_stock.php',
    DIR_FS_ADMIN . DIR_WS_INCLUDES . 'languages/english/extra_definitions/products_with_attributes.php',

    DIR_FS_CATALOG . DIR_WS_INCLUDES . 'auto_loaders/config.products_with_attributes_stock.php',
    DIR_FS_CATALOG . DIR_WS_INCLUDES . 'classes/observers/class.products_with_attributes_stock.php',

    DIR_FS_CATALOG . DIR_WS_INCLUDES . 'extra_cart_actions/stock_by_attributes.php',
    DIR_FS_CATALOG . DIR_WS_INCLUDES . 'extra_datafiles/products_with_attributes_stock_database_tables.php',
    DIR_FS_CATALOG . DIR_WS_INCLUDES . 'functions/extra_functions/products_with_attributes.php',
    DIR_FS_CATALOG_LANGUAGES . 'english/extra_definitions/products_with_attributes.php',

    DIR_FS_CATALOG_TEMPLATES . $template_dir . '/templates/tpl_shopping_cart_default.php',
    DIR_FS_CATALOG_TEMPLATES . $template_dir . '/templates/tpl_account_history_info_default.php',
    DIR_FS_CATALOG_TEMPLATES . $template_dir . '/templates/tpl_checkout_confirmation_default.php',

    DIR_FS_CATALOG_TEMPLATES . $template_dir . '/jscript/jquery-1.10.2.min.js',
  );

  if (PROJECT_VERSION_MAJOR <= '1' && PROJECT_VERSION_MINOR <= '5.4') {
    $files_merge = array(
      DIR_FS_CATALOG_MODULES . 'pages/checkout_success/header_php_sba.php',
    );
    $files = array_merge($files, $files_merge);
  }

  if (PROJECT_VERSION_MAJOR <= '1' && PROJECT_VERSION_MINOR <= '5.6') {
    $files_merge = array(
      DIR_FS_CATALOG_MODULES . $template_dir .'/attributes.php',
    );
    $files = array_merge($files, $files_merge);
  }

  foreach($files as $file) {
    if(!file_exists($file)) {
      $result = "File not found: <b>" . $file . '</b>';
      array_push($resultMmessage, $result);
      $failed = true;
    }
  }

  return;
}

//export table data to a comma-separated values (CSV) file
//list includes extra data fields to help user understand what each line contains
function exportSBAtableData(){
  global $db, $resultMmessage, $failed;
  $separater = ',';//set the list separation character ';' to whatever is needed.
  $SBAtableReport = DIR_FS_BACKUP . 'tableSBAdata.csv';//path 'backup/' and filename 'tableSBAdata' for export
  $returned = null;

  //Make path to Log output if it doesn't exist
  $tmpoutputpath = dirname($SBAtableReport);
  if( !is_dir( $tmpoutputpath ) ) {
    mkdir($tmpoutputpath,0755,TRUE);
  }

  $sql = "SELECT DISTINCT `stock_id`, SBA.`products_id`, p.`products_model`,
          SBA.`stock_attributes`, po.`products_options_name`,
          pov.`products_options_values_name`, pov.`products_options_values_id`, `quantity`, `sort`, `customid`, `title`
      FROM `".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK."` SBA
      LEFT JOIN `".TABLE_PRODUCTS."` p ON (SBA.`products_id` = p.`products_id`)
      LEFT JOIN `".TABLE_PRODUCTS_ATTRIBUTES."` pa ON (SBA.`stock_attributes` = pa.`products_attributes_id`)
      LEFT JOIN `".TABLE_PRODUCTS_OPTIONS."` po ON (po.`products_options_id` = pa.`options_id`)
      LEFT JOIN `".TABLE_PRODUCTS_OPTIONS_VALUES."` pov ON (pa.`options_values_id` = pov.`products_options_values_id`)
      ORDER BY SBA.`stock_id` ASC";

  if(!checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK)) {
    array_push($resultMmessage, '<b>FAILED</b> table products_with_attributes_stock not found!');
    $failed = true;
    return;
  }

  $returned = $db->Execute($sql);

  //Header row
  $result = "stock_id".$separater."products_id".$separater."products_model".$separater."stock_attributes".$separater."products_options_name".$separater."products_options_values_id".$separater."products_options_values_name".$separater."quantity".$separater."sort".$separater."customid\n";
  $replacement = array(",", ";", "'", "\"");//chars to be removed
  while(!$returned->EOF){
    //clean-up to remove character that may cause issues on import
    $returned->fields['products_model'] = str_replace($replacement, ' ', $returned->fields['products_model']);
    $returned->fields['stock_attributes'] = str_replace($replacement, ' ', $returned->fields['stock_attributes']);
    $returned->fields['products_options_name'] = str_replace($replacement, ' ', $returned->fields['products_options_name']);
    $returned->fields['products_options_values_name'] = str_replace($replacement, ' ', $returned->fields['products_options_values_name']);
    $returned->fields['customid'] = str_replace($replacement, ' ', trim($returned->fields['customid']));
    $returned->fields['products_options_values_id'] = str_replace($replacement, ' ', trim($returned->fields['products_options_values_id']));

    $result .= $returned->fields['stock_id'].$separater.$returned->fields['products_id'].$separater.'"'.$returned->fields['products_model'].'"'.$separater.'"'.$returned->fields['stock_attributes'].'"'.$separater.'"'.$returned->fields['products_options_name'].'"'.$separater.'"'.$returned->fields['products_options_values_id'].'"'.$separater.'"'.$returned->fields['products_options_values_name'].'"'.$separater.$returned->fields['quantity'].$separater.$returned->fields['sort'].$separater.'"'.$returned->fields['customid'].'"';
    $result .= "\n";
    $returned->MoveNext();
  }
  $result = rtrim($result,"\n");//remove last comma and return
  $ReportFile = file_put_contents("$SBAtableReport", "$result");//save to file
  array_push($resultMmessage, 'Exported Table data (as "'.$separater.'" separated list) for products_with_attributes_stock to: ' . $SBAtableReport);

  return;
}

//Imports SBA table data from a comma-separated values (CSV) file
//ONLY updates the "quantity" and "customid" fields
//tests for either a comma separated listing or a semicolon separated listing
function importSBAtableData(){
  global $db, $resultMmessage, $failed;
  require(DIR_WS_CLASSES . 'products_with_attributes_stock.php');
  $stock = new products_with_attributes_stock; //new object from class
  $separater = ','; //set the list separation character ',' to whatever is needed.
  $separater2 = ';'; //set the list separation character ';' to whatever is needed.
  $SBAtableReport = DIR_FS_BACKUP . 'tableSBAdata.csv'; //path 'backups/' and filename 'tableSBAdata' for export
  $stockResult = null;
  $qtyResult = null;
  $ReportFile = null;
  $customid = null;

  //Use these settings only if needed.
  //ini_set('memory_limit','96M'); //Increase only if you are having a memory low issue, then change back when done
  //ini_set('max_execution_time','0'); //If set to zero, no time limit is imposed, remove when done
  //ini_set('max_input_time','0'); //If set to zero, no time limit is imposed, remove when done

  if(!checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK)) {
    array_push($resultMmessage, 'FAILED table products_with_attributes_stock not found!');
    $failed = true;
    return;
  }

  $ReportFile = file($SBAtableReport);//get file data

  if (!$ReportFile){
    array_push($resultMmessage, 'Update FAILED no file found!');
    $failed = true;
    return;
  }

  /* Only update the QTY and Custom ID fields
   * checks input file data prior to loading to database, only numeric is allowed for QTY
   */
  $i = -1;//Count; adjust for skipping first line in file
  foreach ($ReportFile as $line_num => $line) {
    //skip line 0 as it is the header row
    if($line_num > 0){
      //decide what separater was used i.e., a comma or a semicolon
      //some programs save the CSV with a comma, others use a semicolon
      if( count(explode($separater, $line) ) == 10){
        $line = explode($separater, $line);
      }
      elseif( count(explode($separater2, $line) ) == 10){
        $line = explode($separater2, $line);
      }
      else{
        $line = null;
      }

      //checks done on the input data prior to loading to database
      $stockResult = doubleval(trim($line[0]));
      $qtyResult = doubleval(trim($line[7]));
      $customid = trim($line[9]);
      $customid = str_replace('"', '', $customid);
    }

    $i++;//increment count
    if(!empty($stockResult) && $qtyResult >= 0){

      $saveResult = $stock->updateAttribQty($stockResult, $qtyResult);

      if( ($saveResult != 1 && $line_num > 0) || (!is_numeric($line[0]) && $line_num > 0) || (!is_numeric($line[7]) && $line_num > 0) ){
        $failed = true;
        array_push($resultMmessage, 'FAILURE during save Qty process! stock_id: ' . $i . ' Bad Quantity value, error:' . $saveResult);//report any line error
      }
    }

    if(!empty($stockResult) && !empty($customid)){
      $saveResult = $stock->updateCustomIDAttrib($stockResult, $customid);
      //echo "Stock ID: $stockResult  Custom ID: $customid <br />";//Debug Line, comment this out to remove from displaying on web page
      if( ($saveResult != 1 && $line_num > 0) || (!is_numeric($line[0]) && $line_num > 0) ){
        $failed = true;
        array_push($resultMmessage, 'FAILURE during save Custom ID process! Record: ' . $i . ' error:' . $saveResult);//report any line error
      }
    }

  }
  array_push($resultMmessage, 'Updated '.$i.' Quantities from: ' . $SBAtableReport);
  zen_record_admin_activity('Updated ' . $i . ' Quantities of the table products_with_attributes_stock from: ' . $SBAtableReport, 'warning');

  return;
}

//Display script error results
function showScriptResult($Input = null){
  global $failed, $resultMmessage;

  if($failed == true){
    $output = "<p><h2>FAILURES:</h2>There were ERRORs Reported.<br /><b>Review results below</b>:</p>";
    $error = "Errors!";
  }
  elseif($Input == 'Full Install'){
    $output = "<p><h2>Stock by Attributes <b>DATABASE</b> component was installed.</h2></p>";
    $error = "No Error reported";
  }
  else{
    $output = "<p><h2>Script Complete.</h2></p>";
    $error = "No Error reported";
  }

  $output .= "<hr />
        <p><h3>Results from the selection $Input: ($error)</h3>
        </p><p>";

  foreach($resultMmessage as $msg){
    $output .= " $msg <br />";
  }

  $output .= "</p><hr/>";

  return $output;
}

//Display File removal notice
function removeSBAfiles(){

  $output = "<p><h3>Remove SBA files from Zen Cart</h3>
        File removal is a manual process, see lists below for files to be removed and files to be restored to an earlier state.</p>

        <p>

        <h3>Files to be removed:</h3>
          <ul>
          <li>admin/stock_by_attr_install.php</li>
          <li>admin/products_with_attributes_stock.php</li>
          <li>admin/products_with_attributes_stock_ajax.php</li>
          <li>admin/products_with_attributes_stock_ajax.js</li>
          <li>admin/includes/products_with_attributes_stock_ajax.css</li>
          <li>admin/includes/classes/products_with_attributes_stock.php</li>
          <li>admin/includes/extra_datafiles/products_with_attributes_stock.php</li>
          <li>admin/includes/languages/english/products_with_attributes_stock.php</li>
          <li>admin/includes/languages/english/extra_definitions/products_with_attributes.php</li>
          </ul>
          <ul>
            <li>includes/auto_loaders/config.products_with_attributes_stock.php</li>
          <li>includes/classes/observers/class.products_with_attributes_stock.php</li>
          <li>includes/extra_cart_actions/stock_by_attributes.php</li>
          <li>includes/modules/pages/checkout_success/header_php_sba.php</li>
          <li>includes/extra_datafiles/products_with_attributes_stock_database_tables.php</li>
          <li>includes/functions/extra_functions/products_with_attributes.php</li>
          <li>includes/languages/english/extra_definitions/products_with_attributes.php</li>
          <li>includes/templates/YOUR_TEMPLATE/jscript/jquery.form.js</li>
            <li>includes/templates/YOUR_TEMPLATE/jscript/jquery-1.10.2.min.js</li>
          </ul>
        </p>

        <p><h1>Revert CORE Files that were changed</h1>
        <h3>Core Zen Cart files need to have the SBA changes removed. Update files as applicable by removing the SBA modification.</h3>
          <ul>
            <li>admin/attributes_controller.php</li>
          <li>admin/options_name_manager.php</li>
          <li>admin/options_values_manager.php</li>
          <li>admin/invoice.php</li>
            <li>admin/orders.php</li>
          <li>admin/packingslip.php</li>
            <li>admin/includes/functions/general.php</li>
          <li>admin/includes/classes/order.php</li>
          </ul>
          <ul>
            <li>includes/classes/order.php</li>
            <li>includes/functions/functions_lookups.php</li>
          <li>includes/functions/extra_functions/products_with_attributes.php</li>
            <li>includes/modules/pages/checkout_shipping/header_php.php</li>
            <li>includes/modules/pages/shopping_cart/header_php.php</li>
          </ul>
        </p>

        <p><h1>Revert or delete the OVERRIDE File</h1>
          <h3>In addition, files may have been over-ridden, remove the SBA changes:</h3>
          <ul>
          <li>includes/modules/YOUR_TEMPLATE/attributes.php</li>
            <li>includes/templates/YOUR_TEMPLATE/templates/tpl_shopping_cart_default.php</li>
          <li>includes/templates/YOUR_TEMPLATE/templates/tpl_account_history_info_default.php</li>
          <li>includes/templates/YOUR_TEMPLATE/templates/tpl_checkout_confirmation_default.php</li>
          </ul>
        </p>";

  return $output;
}

//Display main web page with Help Information
function instructionsSBA(){

  global $ZCversion;

  $output = "<p><h2>How To Use</h2>
        " . zen_draw_form('how_to_use', FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK_SETUP, '', 'get') . "
        <ul>
          <li>To support the newly added non-stock table entry, follow the following guidance:
          <li>A single product's value can be considered non-stock by entering the products_id as the source_id, the options_values_id as the type_id and the type as PV.</li>
          <li>A single product's options can be considered non-stock by entering the products_id as the source_id, the options_id as the type_id and the type as PO.</li>
          <li>A single option value (potentially applicable to all product) can be considered non-stock by entering 0 as the source_id, the options_values_id as the type_id and the type as AV.</li>
          <li>A single option (potentially applicable to all product) can be considered non-stock by entering 0 as the source_id, the options_id as the type_id and the type as AO.</li>
          <li>The expectation (upon software completion) is that an option_value will only be defined by only one of the above rules such that once all option values for an option name has been populated, then the option name itself will be defined and the individual records removed.</li>
          <p></p>
          <li>Read the instructions located in the folder '<b>Instructions</b>'.</li>
          <li>Backup the site and database prior to using these files or making any database changes with the script.</li>
          <li>An 'Optional.sql.txt' file is available, Use at your own risk!</li>
          <li>Samples: <input type='submit' name='selectSBAinstall' value='Table' /> and <input type='submit' name='selectSBAinstall' value='Optional SQL' /> Provided for information. This link provides additional versions of the SQL script that may be of use to some users.</li>
        </ul>
        </form>
          The \"Optional SQL\" will create entries in the new products_with_attributes_stock table
          based on current products that have attributes associated to the product in the database.
        </p>

        <p>Ensure  <b>NEW</b> files have been added, and that <b>CORE</b> files have been merged correctly.<br />
        Core Files are based on a new install of $ZCversion.</p>

        <p><h1>NEW Installation File:</h1>
        The new installation file provides options for installing and removing this contribution, it will modify the \"Database\".<br />
        The script does NOT alter or install files, the process strictly makes database changes and will allow verification of proper NEW file placement.

        <h1>NEW Files:</h1>
          <ul>
            <li>admin/stock_by_attr_install.php</li>
            <li>admin/products_with_attributes_stock.php</li>
          <li>admin/products_with_attributes_stock_ajax.php</li>
          <li>admin/products_with_attributes_stock_ajax.js</li>
          <li>admin/includes/products_with_attributes_stock_ajax.css</li>
          <li>admin/includes/classes/products_with_attributes_stock.php</li>
          <li>admin/includes/extra_datafiles/products_with_attributes_stock.php</li>
          <li>admin/includes/languages/english/products_with_attributes_stock.php</li>
          <li>admin/includes/languages/english/extra_definitions/products_with_attributes.php</li>
          </ul>
          <ul>
            <li>includes/auto_loaders/config.products_with_attributes_stock.php</li>
          <li>includes/classes/observers/class.products_with_attributes_stock.php</li>
          <li>includes/extra_cart_actions/stock_by_attributes.php</li>
          <li>includes/modules/pages/checkout_success/header_php_sba.php</li>
          <li>includes/extra_datafiles/products_with_attributes_stock_database_tables.php</li>
          <li>includes/functions/extra_functions/products_with_attributes.php</li>
          <li>includes/languages/english/extra_definitions/products_with_attributes.php</li>
          <li>includes/templates/YOUR_TEMPLATE/jscript/jquery.form.js</li>
            <li>includes/templates/YOUR_TEMPLATE/jscript/jquery-1.10.2.min.js</li>
          </ul>
        </p>

        <p><h1>CORE Files: (changed/updated)</h1>
        <h3>These core Zen Cart files need to be modified for this add-on.
            If other add-ons or customizations of the cart have been done since the Zen Cart installation or upgrade, Check whether they have changed any of the following files:</h3>
          <ul>
          <li>admin/attributes_controller.php</li>
          <li>admin/options_name_manager.php</li>
          <li>admin/options_values_manager.php</li>
          <li>admin/invoice.php</li>
            <li>admin/orders.php</li>
          <li>admin/packingslip.php</li>
            <li>admin/includes/functions/general.php</li>
          <li>admin/includes/classes/order.php</li>
          </ul>
          <ul>
            <li>includes/classes/order.php</li>
            <li>includes/functions/functions_lookups.php</li>
          <li>includes/functions/extra_functions/products_with_attributes.php</li>
            <li>includes/modules/pages/checkout_shipping/header_php.php</li>
            <li>includes/modules/pages/shopping_cart/header_php.php</li>
          </ul>
        </p>

        <p><h1>OVERRIDE File: (changed/updated)</h1>
          <h3>In addition, files are over-ridden, these files should be placed into the sites template folder:</h3>
          <ul>
            <li>includes/modules/YOUR_TEMPLATE/attributes.php</li>
            <li>includes/templates/YOUR_TEMPLATE/templates/tpl_shopping_cart_default.php</li>
          <li>includes/templates/YOUR_TEMPLATE/templates/tpl_account_history_info_default.php</li>
          <li>includes/templates/YOUR_TEMPLATE/templates/tpl_checkout_confirmation_default.php</li>
          </ul>
        <h2>If any of the installed mods have changed any of the core files (or over-ridden files), then merge these new changes into the relevant core files.</h2>
        </p>";

  return $output;
}

//Display main web page with Help Information
function instructionsSelectionOptions(){

  $output = "<p>Available options in the selection box are:<br />
         <ul>
        <li>Help
          <ul>
          <li>Displays the main page, helps to explain the Script functions.</li>
          <li>No changes are made unless one of the other options are selected.</li>
          <li>Includes, a brief description of \"How To Use\".</li>
          </ul>
        </li>

        <li>Installation
          <ul>
          <li>Full/Upgrade DB Install
            <ul>
            <li>Full, makes all script changes to the database (DB) (i.e., adds new SBA table, adds entries into the Admin page, and new entries into the Configuration file).</li>
            <li>Upgrade, updates Configuration file and the SBA table as needed. If run again, it will \"Clean\" table entries and reapply the settings, it will not affect current data in the \"products_with_attributes_stock\" table.</li>
            </ul>
          </li>
          </ul>
        </li>

        <li>Removal
          <ul>
            <li>Remove All from DB - Removes above changes from the database (DB).</li>
            <li>Remove Configuration Settings - Removes the configuration settings from the database but leaves the SBA data table intact.  This supports removing all options added to the program in preparation of performing an install/upgrade to push the configuration settings back to the database.</li>
          </ul>
        </li>

        <li>Optional SQL Scripts
          <ul>
            <li>Default SQL - Only add the product attributes that are NOT read-only AND are NOT the new SBA selections.</li>
            <li>Add all Product Attributes - Add all the products attributes.</li>
            <li>Add read-only product attributes - Add only the read-only product attributes.</li>
            <li>Add product attributes that are NOT read-only - Only add the product attributes that are NOT read-only.</li>
            <li>Update Unique Combo field - Used to fill the new Unique Combo field in the PAS table, this number is the Product ID and the Attrubute ID.</li>
            <li>Remove ALL entries from the PAS Table - WARNING: This will COMPLETLY EMPTY the Product with Attribute Stock Table!</li>
          </ul>
        </li>

        <li>Tests
          <ul>
           <li>File Check - Check that NEW Files are in proper places.</li>
          </ul>
        </li>

        <li>SBA Select Basic Dropdowns
          <ul>
            <li>Convert all dropdowns to the SBA Select Basic Dropdown</li>
            <li>Convert only dropdowns that are associated with SBA to the SBA Select Basic Dropdown</li>
            <li>Convert SBA Select Basic Dropdown Option Names to standard Dropdowns for Option Names not associated with SBA</li>
          </ul>
        </li>

        <li>Export / Import
        <ul>
          <li>Export Table Data
            <ul>
            <li>Exports the products_with_attributes_stock table as a CSV file.</li>
            <li>Use with the \"Import Table Data\" option.</li>
            </ul>
          </li>
           <li>Import Table Data
            <ul>
            <li>Imports the \"Quantity\" and \"Custom ID\" fields from a CSV file.</li>
            <li>Update quantity (quantity field) in products_with_attributes_stock table.</li>
            <li>Update customid (customid field) in products_with_attributes_stock table.</li>
            <li>customid must be unique, may be alphanumeric. NO duplicates permitted.</li>
            </ul>
           </li>
        </ul>
        </li>
        </ul>
        <hr /></p>";

  return $output;
}

function displaySBAtableCreate(){

  $output = "<pre>
  -- SAMPLE table create SQL products_with_attributes_stock structure:
  -- This is provided for information only.

    CREATE TABLE IF NOT EXISTS `products_with_attributes_stock` (
      `stock_id` int(11) NOT NULL AUTO_INCREMENT,
      `products_id` int(11) NOT NULL,
      `product_attribute_combo` varchar(255) DEFAULT NULL,
      `stock_attributes` varchar(255) NOT NULL,
      `quantity` float NOT NULL DEFAULT '0',
      `sort` int(11) NOT NULL DEFAULT '0',
      `customid` varchar(255) DEFAULT NULL,
      `title` varchar(50) DEFAULT NULL,
      PRIMARY KEY (`stock_id`),
      UNIQUE KEY `idx_products_id_stock_attributes` (`products_id`,`stock_attributes`),
      UNIQUE KEY `idx_products_id_attributes_id` (`product_attribute_combo`),
      UNIQUE KEY `idx_customid` (`customid`)
    );
  </pre>";

  return $output;
}

//Optional SQL information
function displayOptionalSQL(){

$output = '<pre>
  -- SAMPLE SQL.
  -- This is provided for information only, recomended using "Run Optional SQL" from the selection list:
  -- Created for Stock by Attributes 1.5.1.2
  -- Use at your own risk!
  -- Backup your databases prior to using these files or making any changes.
  -- This SQL will create entries in the new products_with_attributes_stock table based on current
  -- products that have attributes.
  -- By default it will set each of the new entries to the quantity found in the product entry.
  -- SAMPLE ONLY, you are responsible to verify data is correct and acceptable for your site.

  -- NOTES:
  --
  --  No changes to table names (prefix) are required if you use the SQL Query Executor
  --  from the admin (Tools\Install SQL Patches).
  --
  --  But, If you use a third party database tool, such as HeidiSQL, then you should read the following.
  --     The table names will need to be changed if your database uses a prefix such as \'zen_\'.
  --    Examples:
  --      Change: products_with_attributes_stock
  --      To: zen_products_with_attributes_stock
  --      Change: products
  --      To: zen_products.
  --      Change: products_attributes
  --      To: zen_products_attributes.
  --      Change: products_options_values
  --      To: zen_products_options_values.

  -- New version of insert script to support on duplicate entries
  -- Paste the following into \'SQL Query Executor\' (Tools\Install SQL Patches).


  ------------------------------------------------------------------------------------------------------
  -- This will add all the products attributes:

  INSERT INTO products_with_attributes_stock (products_id, stock_attributes, quantity)

    SELECT p.products_id, pa.products_attributes_id, p.products_quantity
    FROM products p
      LEFT JOIN products_attributes pa ON (p.products_id = pa.products_id)
      LEFT JOIN products_options_values pv ON (pa.options_values_id = pv.products_options_values_id)
    WHERE pa.products_attributes_id is not null
      AND pa.options_values_id > 0
    ORDER BY p.products_id, pa.products_attributes_id

  ON DUPLICATE KEY UPDATE
    `products_id` = products_with_attributes_stock.products_id;


  ------------------------------------------------------------------------------------------------------
  -- This will add only the read-only product attributes:

  INSERT INTO products_with_attributes_stock (products_id, stock_attributes, quantity)

    SELECT p.products_id, pa.products_attributes_id, p.products_quantity
    FROM products p
      LEFT JOIN products_attributes pa ON (p.products_id = pa.products_id)
      LEFT JOIN products_options_values pv ON (pa.options_values_id = pv.products_options_values_id)
    WHERE pa.products_attributes_id is not null
      AND pa.options_values_id > 0
      AND pa.attributes_display_only = 1
    ORDER BY p.products_id, pa.products_attributes_id

  ON DUPLICATE KEY UPDATE
    `products_id` = products_with_attributes_stock.products_id;


  ------------------------------------------------------------------------------------------------------
  -- This will only add the product attributes that are NOT read-only:

  INSERT INTO products_with_attributes_stock (products_id, stock_attributes, quantity)

    SELECT p.products_id, pa.products_attributes_id, p.products_quantity
    FROM products p
      LEFT JOIN products_attributes pa ON (p.products_id = pa.products_id)
      LEFT JOIN products_options_values pv ON (pa.options_values_id = pv.products_options_values_id)
    WHERE pa.products_attributes_id is not null
      AND pa.options_values_id > 0
      AND pa.attributes_display_only = 0
    ORDER BY p.products_id, pa.products_attributes_id

  ON DUPLICATE KEY UPDATE
    `products_id` = products_with_attributes_stock.products_id;


  ------------------------------------------------------------------------------------------------------
  --Default version used in script.
  -- This will only add the product attributes that are NOT read-only AND are NOT the new "SBA" selections:

  INSERT INTO products_with_attributes_stock (products_id, stock_attributes, quantity)

    SELECT p.products_id, pa.products_attributes_id, p.products_quantity
    FROM products p
      LEFT JOIN products_attributes pa ON (p.products_id = pa.products_id)
      LEFT JOIN products_options_values pv ON (pa.options_values_id = pv.products_options_values_id)
      LEFT JOIN products_options_values_to_products_options povpo ON (pv.products_options_values_id = povpo.products_options_values_id)
      LEFT JOIN products_options po ON(povpo.products_options_id = po.products_options_id)
      LEFT JOIN products_options_types pot ON (po.products_options_type = pot.products_options_types_id)

    WHERE pa.products_attributes_id is not null
      AND pa.options_values_id > 0
      AND pa.attributes_display_only = 0
      AND pot.products_options_types_name NOT LIKE "SBA%"
    ORDER BY p.products_id, pa.products_attributes_id

  ON DUPLICATE KEY UPDATE
    `products_id` = products_with_attributes_stock.products_id;

  </pre>';

  return $output;
}

echo '<div id="" style="background-color: green; padding: 2px 10px;"></div>
    <br class="clearBoth" />
    <div id="divSBAinstall" style="text-align:center;font-size:15px;width:80%;">

    <p><h1>Stock By Attribute (SBA) installation script <br />' . $SBAversion . ' for ' . $ZCversion . '
      <br /><a target="blank" href="http://www.zen-cart.com/downloads.php?do=file&id=202">Zen Cart SBA Plugin</a>
      <br />
      <a target="blank" href="http://www.zen-cart.com/showthread.php?47180-Stock-by-Attribute-v4-0-addon-for-v1-3-5-1-3-9">SBA Support Thread</a>

      </h1>

       <div style="text-align:center;font-size:15px;margin-left:200px;margin-right:100px;">
       This installation <b>will</b> make changes to the <b>Database</b><br />Please <b>backup</b> the database prior to use.
      <br />Files must be manually added and where applicable merged with the existing files.<br />
      <br class="clearBoth" />
       <a title="Shortcut to the Stock By Attributtes Catalog" href="' . zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, '', 'NONSSL') . '">SBA Catalog Link</a>
      <br class="clearBoth" /><hr />

    ' . zen_draw_form("SBAinstall", FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK_SETUP, "id=\"SBAinstall\"", "get") . '

     Select:

     <select id="selectSBAinstall" name="selectSBAinstall">
      <option value="">Help</option>

      <optgroup label="Installation">
        <option value="installAll">Full/Upgrade DB Install</option>
        <option value="installNonStock">Non-Stock DB Table Install</option>
      </optgroup>

      <optgroup label="Remove Settings">
        <option value="removeSettings">Remove Configuration Settings</option>
      </optgroup>

      <optgroup label="Removal">
        <option value="removeAll">Remove All from DB</option>
      </optgroup>

      <optgroup label="Optional SQL Scripts">
        <option value="runOptionalSQL1" title="Only add the product attributes that are NOT display-only AND are NOT the new SBA selections">Default SQL</option>
        <option value="runOptionalSQL2" title="Add all the products attributes">Add all Product Attributes</option>
        <option value="runOptionalSQL3" title="Add only the display-only product attributes">Add display-only product attributes</option>
        <option value="runOptionalSQL4" title="Only add the product attributes that are NOT display-only">Add product attributes that are NOT display-only</option>
        <option value="runOptionalSQL5" title="Remove the product attributes that are ONLY read-only">Remove product attributes that are ONLY read-only</option>
        <option value="runOptionalSQL6" title="Ensure availability and operation of PRODUCTS_OPTIONS_TYPE_SELECT, UPLOAD_PREFIX, and TEXT_PREFIX or if dropdowns do not appear">Restore visibility of Dropdowns</option>
        <option value="updatePASfieldPAC" title="Update Unique Combo field">Update Unique Combo field</option>
        <option value="truncatePAStable" title="WARNING: This will COMPLETLY EMPTY the Product with Attribute Stock Table!">Remove ALL entries from the PAS Table</option>
      </optgroup>

      <optgroup label="Tests">
        <option value="checkFiles">File Check</option>
      </optgroup>
      <optgroup label="SBA Select Basic Dropdowns">
        <option value="runOptionalAllToSBA" title="Convert all dropdowns to the SBA Select Basic Dropdown to support display of additional information for Single Attribute product">Convert all dropdowns to SBA Select Basic Dropdowns</option>
        <option value="runOptionalSBAToSBA" title="Convert all dropdowns associated to existing SBA product to the SBA Select Basic Dropdown to support display of additional information for Single Attribute product">Convert all SBA dropdowns to SBA Select Basic Dropdowns</option>
        <option value="runOptionalNonSBAToDropdown" title="Convert dropdowns *NOT* associated with SBA product to the standard Dropdown">Convert unrelated SBA dropdowns to standard dropdowns</option>
      </optgroup>

      <optgroup label="Export / Import" title="Update SBA table from CSV file">
        <option value="exportTable">Export Table Data</option>
        <option value="importTable">Import Table Data</option>
      </optgroup>
     </select>

     <input type="submit" value="Run Script" id="getSBAinstallPage" name="getSBAinstallPage"/>
     </form>

     <a href="' . zen_href_link('stock_by_attr_install.php', '', 'NONSSL') . '">Reset</a>
    <br class="clearBoth" /><hr />

    </div>
     </div>

    <br class="clearBoth" /><hr />

    </p><div id="SBA-table" style="color:blue;margin-left:50px;margin-right:50px;font-size:15px;">';

  //Selection action
  if($action == 'installAll'){
    //Called functions for this installation
    //Some functions listed below need additional tests and updates, they are commented out
    //Clean-up functions to remove database entries
    checkSBAobsoleteFiles();//check for obsolete files and report them to user
    removeSBAconfiguration();//Call function to Remove configuration entries
    removeSBAadminPages();//Call function to Remove Admin Pages entry
    removeDynDropdownsConfiguration();
    removeDynDropdownsAdminPages();
    //Add new database entries
    insertSBAadminPages();//Call function to Add New Admin Pages entry
    insertSBAconfiguration();//Call function to Add New configuration entries
    insertDynDropdownsConfigurationMenu();
    insertDynDropdownsConfiguration();
    addSBAtable();//Call function to Add New table products_with_attributes_stock
    addSBANonStockTable();
    insertSBAconfigurationMenu();//add install script to configuration menu
    verifyProductOptionsTypes();// Verify that at least PRODUCTS_OPTIONS_TYPE_DROPDOWN is still in the database
    // and "tucked" away.
    insertSBAproductsOptionsTypes();//Call function to Add New entries
    //Test for proper New file placement
    checkSBAfileLocation();//Call to check for proper placement of New files
    echo showScriptResult('Full Install');//show script result
  }
  elseif ($action == 'installNonStock') {
    addSBANonStockTable();
    echo showScriptResult('Non-Stock DB Table Install');//show script result
  }
  elseif($action == 'removeSettings'){
    removeSBAconfiguration();  // Call function to remove configuration entries.
    removeSBAadminPages(); // Call function to remove admin pages entry
    removeDynDropdownsConfiguration();
    removeDynDropdownsAdminPages();
    echo removeSBAfiles(); // show instructions for file removal/reversion to previous state
    echo showScriptResult('Remove Configuration Settings');
  }
  elseif($action == 'removeAll'){
    //Clean-up functions to remove database entries
    removeSBAconfiguration();//Call function to Remove configuration entries
    removeSBAadminPages();//Call function to Remove Admin Pages entry
    removeDynDropdownsConfiguration();
    removeDynDropdownsAdminPages();
    dropSBATable();//Call function to remove SBA table
    dropSBANonStockTable(); // Call function to remove the SBA table that tracks non-stock attributes.
//    dropSBAOrdersTable(); // Not sure this should be performed, as it will remove historical data.
    echo removeSBAfiles();//show instructions for file removal/reversion to previous state
    echo showScriptResult('Remove All');//show results of table modifications
  }
  elseif($action == 'runOptionalSQL1'){
    //Default version used in script.
    //This will only add the product attributes that are NOT display-only AND are NOT the new "SBA" selections
    installOptionalSQL1();
    echo showScriptResult('Optional SQL 1');//show script result
  }
  elseif($action == 'runOptionalSQL2'){
    //This will add all the products attributes
    installOptionalSQL2();
    echo showScriptResult('Optional SQL 2');//show script result
  }
  elseif($action == 'runOptionalSQL3'){
    //This will add only the display-only product attributes
    installOptionalSQL3();
    echo showScriptResult('Optional SQL 3');//show script result
  }
  elseif($action == 'runOptionalSQL4'){
    //This will only add the product attributes that are NOT display-only
    installOptionalSQL4();
    echo showScriptResult('Optional SQL 4');//show script result
  }
  elseif($action == 'runOptionalSQL5'){
    //This will only add the product attributes that are NOT read-only
    installOptionalSQL5();
    echo showScriptResult('Optional SQL 5');//show script result
  }
  elseif($action == 'runOptionalSQL6'){
    // This will ensure that constants that formerly were stored in gID=0 are
    //  present for operation with this plugin and general site operation.
    verifyProductOptionsTypes();
    echo showScriptResult('Optional SQL 6');
  }
  elseif($action == 'runOptionalAllToSBA'){

    convertDropdownsToSBA();
    echo showScriptResult('All Dropdowns to SBA Select Basic Dropdowns');//show script result
  }
  elseif($action == 'runOptionalSBAToSBA'){

    convertSBAToSBA();
    echo showScriptResult('All SBA Dropdown Option Names to SBA Select Basic Dropdowns');//show script result
  }
  elseif($action == 'runOptionalNonSBAToDropdown'){

    convertNonSBAToDropdown();
    echo showScriptResult('All Non SBA SBA Select Basic Dropdowns to ZC Dropdowns');//show script result
  }
  elseif($action == 'updatePASfieldPAC'){
    //Updates the product_attribute_combo field
    updateProductAttributeCombo();
    echo showScriptResult('Product Attribute Combo field');//show script result
  }
  elseif($action == 'truncatePAStable'){
    //TRUNCATE the products_with_attributes_stock table
    truncateProductAttributeStockTable();
    echo showScriptResult('Product Attribute Stock Table Cleared');//show script result
  }
  elseif($action == 'checkFiles'){
    //check SBA NEW file Locations
    checkSBAobsoleteFiles();
    checkSBAfileLocation();
    echo showScriptResult('File Check');//show script result
  }
  elseif($action == 'exportTable'){
    exportSBAtableData();
    echo showScriptResult('Export Table Data');//show script result
  }
  elseif($action == 'importTable'){
    importSBAtableData();
    echo showScriptResult('Import Table Data');//show script result
  }
  elseif($action == 'Optional SQL'){
    echo displayOptionalSQL();
  }
  elseif($action == 'Table'){
    echo displaySBAtableCreate();
  }
  else{
    //display instruction screen
    echo instructionsSelectionOptions();
    echo instructionsSBA();
  }

  echo '</div><hr />';

?>

<!-- The following copyright announcement is in compliance
to section 2c of the GNU General Public License, and
thus can not be removed, or can only be modified
appropriately.

Please leave this comment intact together with the
following copyright announcement. //-->

<div class="copyrightrow"><a href="http://www.zen-cart.com" target="_blank"><img src="images/small_zen_logo.gif" alt="Zen Cart:: the art of e-commerce" border="0" /></a><br /><br />E-Commerce Engine Copyright &copy; 2003-<?php echo date('Y'); ?> <a href="http://www.zen-cart.com" target="_blank">Zen Cart&reg;</a></div><div class="warrantyrow"><br /><br />Zen Cart is derived from: Copyright &copy; 2003 osCommerce<br />This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;<br />without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE<br />and is redistributable under the <a href="http://www.zen-cart.com/license/2_0.txt" target="_blank">GNU General Public License</a><br />
</div>
</body>
</html>
<?php require('./includes/application_bottom.php'); ?>
