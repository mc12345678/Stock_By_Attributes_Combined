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

$SBAversion = 'SBA Version 1.5.4';
$ZCversion = 'Zen Cart Version 1.5.4';

$version_check_index=true;//used in admin/includes/header.php
require('includes/application_top.php');//Provides most of the page display admin menu

// Check for language in use
$languages = zen_get_languages();
$languages_array = array();
$languages_selected = DEFAULT_LANGUAGE;
for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
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
	global $resultMmessage, $failed;
	
	// Attempt to find obsolete files from older versions
	$files = array(		
		DIR_FS_ADMIN . DIR_WS_INCLUDES . 'extra_datafiles/products_with_attributes_stock_database_tables.php',
		DIR_FS_ADMIN . DIR_WS_INCLUDES . 'extra_datafiles/products_with_attributes_stock_filenames.php',

		DIR_FS_ADMIN . 'ajax/jquery.form.js',
		DIR_FS_ADMIN . 'ajax/jquery-1.10.2.min.js',
		DIR_FS_ADMIN . 'ajax/products_with_attributes_stock_ajax.js',		

		DIR_FS_ADMIN . 'ajax/jquery.js',
			
		DIR_FS_CATALOG . 'ajax/jquery.form.js',
		DIR_FS_CATALOG . 'ajax/jquery.js',
		DIR_FS_CATALOG . 'ajax/products_with_attributes_stock_ajax.js'
	);
	
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
	$msg = null;
	array_push($resultMmessage, '<br /><b>Clean-Up</b>, Removing from configuration: ');

	$sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'PRODINFO_ATTRIBUTE_PLUGIN_SINGLE'";
	$db->Execute($sql);
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted PRODINFO_ATTRIBUTE_PLUGIN_SINGLE  ' . $msg);
	
	$sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'PRODINFO_ATTRIBUTE_PLUGIN_MULTI'";
	$db->Execute($sql);
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted PRODINFO_ATTRIBUTE_PLUGIN_MULTI  ' . $msg);
	
	$sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'PRODINFO_ATTRIBUTE_SHOW_OUT_OF_STOCK'";
	$db->Execute($sql);
	if($db->error){	
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted PRODINFO_ATTRIBUTE_SHOW_OUT_OF_STOCK  ' . $msg);
	
	$sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK'";
	$db->Execute($sql);
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK  ' . $msg);
	
	$sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'PRODINFO_ATTRIBUTE_OUT_OF_STOCK_MSGLINE'";
	$db->Execute($sql);
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted PRODINFO_ATTRIBUTE_OUT_OF_STOCK_MSGLINE  ' . $msg);
	
	$sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'PRODINFO_ATTRIBUTE_NO_ADD_OUT_OF_STOCK'";
	$db->Execute($sql);
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted PRODiNFO_ATTRIBUTE_NO_ADD_OUT_OF_STOCK  ' . $msg);
	
	$sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'STOCK_SET_SBA_NUMRECORDS'";
	$db->Execute($sql);
	if($db->error){	
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted STOCK_SET_SBA_NUMRECORDS  ' . $msg);

        $sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'PRODINFO_ATTRIBUTE_DYNAMIC_STATUS'";
        $db->Execute($sql);
        if($db->error){
          $msg = ' Error Message: ' . $db->error;
        }
        array_push($resultMmessage, '&bull; Deleted PRODINFO_ATTRIBUTE_DYNAMIC_STATUS ' . $msg);
        
        $sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'SBA_ZC_DEFAULT'";
        $db->Execute($sql);
        if($db->error){
          $msg = ' Error Message: ' . $db->error;
        }
        array_push($resultMmessage, '&bull; Deleted SBA_ZC_DEFAULT ' . $msg);
        
        $sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'PRODINFO_ATTRIBUTE_POPUP_OUT_OF_STOCK'";
        $db->Execute($sql);
        if($db->error){
          $msg = ' Error Message: ' . $db->error;
        }
        array_push($resultMmessage, '&bull; Deleted PRODINFO_ATTRIBUTE_POPUP_OUT_OF_STOCK ' . $msg);
/*	$sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'SBA_SHOW_IMAGE_ON_PRODUCT_INFO'";
	$db->Execute($sql);
	if($db->error){	
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted SBA_SHOW_IMAGE_ON_PRODUCT_INFO  ' . $msg);
	
	$sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'PRODUCTS_OPTIONS_TYPE_SELECT_SBA'";
	$db->Execute($sql);
	if($db->error){		
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted PRODUCTS_OPTIONS_TYPE_SELECT_SBA  ' . $msg);
	
	//DELETE FROM `products_options_types` 
	array_push($resultMmessage, '<br /><b>Clean-Up</b>, Removing from products_options_types: ');
	
	$sql = "DELETE IGNORE FROM `".TABLE_PRODUCTS_OPTIONS_TYPES."` WHERE `products_options_types_name` = 'SBA Select List (Dropdown) Basic'";
	$db->Execute($sql);
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted products_options_types_name  ' . $msg);
	*/
  
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
	$msg = null;
	array_push($resultMmessage, '<br /><b>Clean-Up</b>, Removing from configuration: ');

	$sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'STOCK_SHOW_IMAGE'";
	$db->Execute($sql);
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted STOCK_SHOW_IMAGE  ' . $msg);
	
	$sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'STOCK_SHOW_LOW_IN_CART'";
	$db->Execute($sql);
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted STOCK_SHOW_LOW_IN_CART  ' . $msg);
	
	$sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'STOCK_SHOW_ATTRIB_LEVEL_STOCK'";
	$db->Execute($sql);
	if($db->error){	
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted STOCK_SHOW_ATTRIB_LEVEL_STOCK  ' . $msg);
	
	$sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'STOCK_SHOW_ORIGINAL_PRICE_STRUCK'";
	$db->Execute($sql);
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted STOCK_SHOW_ORIGINAL_PRICE_STRUCK  ' . $msg);
	
	$sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'STOCK_SET_SBA_SEARCHBOX'";
	$db->Execute($sql);
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted STOCK_SET_SBA_SEARCHBOX  ' . $msg);
	
// 	$sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'STOCK_SET_SBA_NUMRECORDS'";
// 	$db->Execute($sql);
// 	array_push($resultMmessage, '&bull; Deleted STOCK_SET_SBA_NUMRECORDS  ' . $msg);
	
	$sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'STOCK_SBA_SEARCHLIST'";
	$db->Execute($sql);
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted STOCK_SBA_SEARCHLIST  ' . $msg);
	
	$sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'STOCK_SBA_DISPLAY_CUSTOMID'";
	$db->Execute($sql);
	if($db->error){	
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted STOCK_SBA_DISPLAY_CUSTOMID  ' . $msg);
	
	$sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'SBA_SHOW_IMAGE_ON_PRODUCT_INFO'";
	$db->Execute($sql);
	if($db->error){	
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted SBA_SHOW_IMAGE_ON_PRODUCT_INFO  ' . $msg);
	
	$sql = "DELETE IGNORE FROM `".TABLE_CONFIGURATION."` WHERE `configuration_key` = 'PRODUCTS_OPTIONS_TYPE_SELECT_SBA'";
	$db->Execute($sql);
	if($db->error){		
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted PRODUCTS_OPTIONS_TYPE_SELECT_SBA  ' . $msg);
	
	//DELETE FROM `products_options_types` 
	array_push($resultMmessage, '<br /><b>Clean-Up</b>, Removing from products_options_types: ');
	
	$sql = "DELETE IGNORE FROM `".TABLE_PRODUCTS_OPTIONS_TYPES."` WHERE `products_options_types_name` = 'SBA Select List (Dropdown) Basic'";
	$db->Execute($sql);
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted products_options_types_name  ' . $msg);
	
	return;
}

function removeDynDropdownsAdminPages(){
	global $db, $resultMmessage;

	$msg = null;
	array_push($resultMmessage, '<br /><b>Clean-Up</b>, Removing Dynamic Dropdowns from admin_pages: ');

  $sql = "DELETE FROM `".TABLE_ADMIN_PAGES."` WHERE page_key = 'configDynamicDropdownSBA'";
	$db->Execute($sql);
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted configDynamicDropdownSBA ' . $msg);

	/*
	 DELETE FROM admin_pages  WHERE  page_key = 'productsWithAttributesStockSetup';
	*/
  $sql = "DELETE FROM ".TABLE_CONFIGURATION_GROUP." WHERE configuration_group_title='Dynamic Drop Downs'";
  
	$db->Execute($sql);
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted Dynamic Drop Downs from configuration_group ' . $msg);

	  
}


//Clean-up remove existing entries prior to adding new
function removeSBAadminPages(){
	global $db, $resultMmessage;
	
	/*
	 DELETE FROM admin_pages  WHERE  page_key = 'productsWithAttributesStock';
	*/
	$msg = null;
	array_push($resultMmessage, '<br /><b>Clean-Up</b>, Removing from admin_pages: ');
	
	$sql = "DELETE FROM`".TABLE_ADMIN_PAGES."` WHERE page_key = 'productsWithAttributesStock'";
	$db->Execute($sql);
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted productsWithAttributesStock ' . $msg);

	/*
	 DELETE FROM admin_pages  WHERE  page_key = 'productsWithAttributesStockSetup';
	*/
	
	$sql = "DELETE FROM`".TABLE_ADMIN_PAGES."` WHERE page_key = 'productsWithAttributesStockSetup'";
	$db->Execute($sql);
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted productsWithAttributesStockSetup ' . $msg);
	
	return;
}

//Clean-up Drop table products_with_attributes_stock
function dropSBATable(){
	global $db, $resultMmessage;
	
	/*
	 * DROP TABLE IF EXISTS 'products_with_attributes_stock';
	 */
	array_push($resultMmessage, '<br /><b>Clean-Up</b>, Removing Table products_with_attributes_stock: ');
	
	$sql = "DROP TABLE IF EXISTS ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK;

	$db->Execute($sql);
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Deleted table products_with_attributes_stock ' . $msg);
	
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
	
	return;	
}

//Add this script to the configuration menu
function insertSBAconfigurationMenu(){
	global $db, $resultMmessage;
	
	array_push($resultMmessage, '<br /><b>Adding</b> to admin_pages: ');
	
	//get current max sort number used, then add 1 to it.
	//this will place the new entry 'productsWithAttributesStock' at the bottom of the list
	$sql = "SELECT ap.sort_order
			FROM ".TABLE_ADMIN_PAGES." ap
			WHERE ap.menu_key = 'configuration'
			order by ap.sort_order desc limit 1";
	$result = $db->Execute($sql);
	$result = $result->fields['sort_order'] + 1;
	
	$sql = "INSERT INTO `".TABLE_ADMIN_PAGES."` (page_key, language_key, main_page, page_params, menu_key, display_on_menu, sort_order) 
			VALUES 
			('productsWithAttributesStockSetup', 'BOX_CONFIGURATION_PRODUCTS_WITH_ATTRIBUTES_STOCK_SETUP', 'FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK_SETUP', '', 'configuration', 'Y', ".$result.")";
	$db->Execute($sql);
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Inserted into admin_pages productsWithAttributesStockSetup. ' . $msg);
	
	return;
}

function insertDynDropdownsConfigurationMenu(){
	global $db, $resultMmessage;
	
	array_push($resultMmessage, '<br /><b>Adding</b> to admin_pages: ');
	
	//get current max sort number used, then add 1 to it.
	//this will place the new entry 'productsWithAttributesStock' at the bottom of the list
  $sql = "SELECT configuration_group_id, MAX(configuration_group_id) as last_configuration_group_id FROM ".TABLE_CONFIGURATION_GROUP." WHERE configuration_group_title='Dynamic Drop Downs' LIMIT 1";
  $result = $db->Execute($sql);
  $configuration_id = $result->fields['configuration_group_id'];
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
  	if($db->error){
    	$msg = ' Error Message: ' . $db->error;
    }
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
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Inserted into admin_pages configDynamicDropdownSBA. ' . $msg);
	
	return;
}

//Add required entry into the admin_pages table
function insertSBAadminPages(){
	global $db, $resultMmessage;
	$msg = null;
	array_push($resultMmessage, '<br /><b>Adding</b> to admin_pages: ');
	
	//get current max sort number used, then add 1 to it.
	//this will place the new entry 'productsWithAttributesStock' at the bottom of the list
	$sql = "SELECT ap.sort_order
			FROM ".TABLE_ADMIN_PAGES." ap
	 		WHERE ap.menu_key = 'catalog'
			order by ap.sort_order desc limit 1";
	$result = $db->Execute($sql);
	$result = $result->fields['sort_order'] + 1;

	$sql = "INSERT INTO `".TABLE_ADMIN_PAGES."` (page_key, language_key, main_page, page_params, menu_key, display_on_menu, sort_order)
			VALUES
			('productsWithAttributesStock', 'BOX_CATALOG_PRODUCTS_WITH_ATTRIBUTES_STOCK', 'FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK', '', 'catalog', 'Y', ".$result.")";
	$db->Execute($sql);
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
	}
	array_push($resultMmessage, '&bull; Inserted into admin_pages productsWithAttributesStock. ' . $msg);

	return;
}


//Add required entries into the products_options_types table
function insertSBAproductsOptionsTypes(){
	global $db, $resultMmessage, $failed;
	$msg = null;
	array_push($resultMmessage, '<br /><b>Adding</b> to products_options_types: ');
	
	//get current max sort number used, then add 1 to it.
	//this will place the new entries at the bottom of the list
	$sql = "SELECT pot.products_options_types_id, products_options_types_name
			FROM ".TABLE_PRODUCTS_OPTIONS_TYPES." pot	
			order by pot.products_options_types_id desc limit 1";
	$result = $db->Execute($sql);
	$resultGID = $result->fields['products_options_types_id'] + 1;

	$sql = "INSERT INTO ".TABLE_PRODUCTS_OPTIONS_TYPES." (`products_options_types_id`, `products_options_types_name`) 
			VALUES (".$resultGID.", 'SBA Select List (Dropdown) Basic');";

	$result = $db->Execute($sql);
	
	if($db->error){
		$msg = ' Error Message: ' . $db->error;
		$failed = true;
	}
	array_push($resultMmessage, '&bull; Inserted into products_options_types "SBA Select List (Dropdown) Basic". ' . $msg);
	
	//error test, and prevent a duplicate entry
	if( $failed != true && $result->fields['products_options_types_name'] !=  'Selection list product option type (SBA)' ){

		array_push($resultMmessage, '<br /><b>Adding</b> to configuration: ');
		
		$sql = "INSERT INTO ".TABLE_CONFIGURATION." (configuration_title, configuration_key, configuration_value,
		configuration_description, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function)
			
		VALUES
		('Selection list product option type (SBA)', 'PRODUCTS_OPTIONS_TYPE_SELECT_SBA', ".$resultGID.", 
		 'Numeric value of the radio button product option type',
		 '6', 0, now(), now(), NULL, NULL);";
		
		$db->Execute($sql);
		
		if($db->error){
			$msg = ' Error Message: ' . $db->error;
			$failed = true;
		}
		array_push($resultMmessage, '&bull; Inserted PRODUCTS_OPTIONS_TYPE_SELECT_SBA  ' . $msg);

	}
	
	return;
}

//Add required entries into the configuration table
function insertSBAconfiguration(){
	global $db, $resultMmessage, $failed;
	
	array_push($resultMmessage, '<br /><b>Adding</b> to configuration (SBA option switches): ');
	
	//get current max sort number used, then add 1 to it.
	//this will place the new entries at the bottom of the list
	$sql ="SELECT c.sort_order
			FROM ".TABLE_CONFIGURATION." c
			WHERE c.configuration_group_id = 9
			order by c.sort_order desc limit 1";
	$result = $db->Execute($sql);
	$result = $result->fields['sort_order'] + 1;
	
	$sql = "INSERT INTO `".TABLE_CONFIGURATION."` (configuration_title, configuration_key, configuration_value, 
	       configuration_description, configuration_group_id, sort_order, 
	       last_modified, date_added, use_function, set_function) 
		
	       VALUES 
		    ('SBA Show Available Stock Level in Cart (when less than order)', 'STOCK_SHOW_LOW_IN_CART', 'true', 
	        'When customer places more items in cart than are available, show the available stock on the shopping cart page:',
	        9,".$result.",now(),now(),NULL,'zen_cfg_select_option(array(\'true\', \'false\'),'),
	
	        ('SBA Display Images in Admin', 'STOCK_SHOW_IMAGE', 'true', 
	        'Display image thumbnails on Products With Attributes Stock page? (warning, setting this to true can severely slow the loading of this page):',
	        9,".$result.",now(),now(),NULL,'zen_cfg_select_option(array(\'true\', \'false\'),'),
		
		    ('SBA Show Stock Level on Product Info Page', 'STOCK_SHOW_ATTRIB_LEVEL_STOCK', 'true', 
	        'Show the available stock with each attribute on product info page:',
	        9,".$result.",now(),now(),NULL,'zen_cfg_select_option(array(\'true\', \'false\'),'),
	
		    ('SBA Original Price Struck Through', 'STOCK_SHOW_ORIGINAL_PRICE_STRUCK', 'true', 
	        'Show the original price (struck through) on product info page with attribute:',
	        9,".$result.",now(),now(),NULL,'zen_cfg_select_option(array(\'true\', \'false\'),'),
	
			('SBA Display Search Box Only', 'STOCK_SET_SBA_SEARCHBOX', 'false', 
			'Show Search box only (no records):',
			9,".$result.",now(),now(),null,'zen_cfg_select_option(array(\'true\', \'false\'),'),
	
			('SBA Display Search List Box', 'STOCK_SBA_SEARCHLIST', 'true', 
			'Show the Search List box At the top of the page:',
			9,".$result.",now(),now(),null,'zen_cfg_select_option(array(\'true\', \'false\'),'),

			('SBA Display Custom ID', 'STOCK_SBA_DISPLAY_CUSTOMID', 'true', 
			'Display the Custom ID value in history, checkout, and order forms:',
			9,".$result.",now(),now(),null,'zen_cfg_select_option(array(\'true\', \'false\'),'),
			
			('SBA Display Attributes Images', 'SBA_SHOW_IMAGE_ON_PRODUCT_INFO', 'false', 
			'Display the Attribute Image on the product information page:',
			9,".$result.",now(),now(),null,'zen_cfg_select_option(array(\'true\', \'false\'),');";
		
		/* save for next version when pagination is implemented
		 * 
		 		('SBA Number of Records to Displayed', 'STOCK_SET_SBA_NUMRECORDS', '25', 
				'Number of records to show on page:',
				9,".$result.",now(),now(),null,null),
		 */
		
		$result = $db->Execute($sql);
		
		
		if($db->error){		
			$msg = ' Error Message: ' . $db->error;
			$failed = true;
		}
		else{
			$list = '&bull; STOCK_SHOW_IMAGE<br />
	        		&bull; STOCK_SHOW_LOW_IN_CART<br />
	        		&bull; STOCK_SHOW_ATTRIB_LEVEL_STOCK<br />
	        		&bull; STOCK_SHOW_ORIGINAL_PRICE_STRUCK<br />
				    &bull; STOCK_SET_SBA_SEARCHBOX<br />
	        		&bull; STOCK_SBA_SEARCHLIST<br /> 
	        		&bull; STOCK_SBA_DISPLAY_CUSTOMID<br /> 
	        		&bull; SBA_SHOW_IMAGE_ON_PRODUCT_INFO';
			array_push($resultMmessage, 'Inserted into configuration: <br />' . $list);
		}
	
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
  
  
  $sql ="SELECT c.sort_order
			FROM ".TABLE_CONFIGURATION." c
			WHERE c.configuration_group_id = :configuration_id:
			order by c.sort_order desc limit 1";
  $sql = $db->bindVars($sql, ':configuration_id:', $configuration_id, 'integer');
	$result = $db->Execute($sql);
	$result = $result->fields['sort_order'] + 1;

	$sql = "INSERT INTO `".TABLE_CONFIGURATION."` (configuration_title, configuration_key, configuration_value, 
	       configuration_description, configuration_group_id, sort_order, 
	       date_added, use_function, set_function) 
	       VALUES 
		    ('Enable Dynamic Dropdowns', 'PRODINFO_ATTRIBUTE_DYNAMIC_STATUS', '2', 'Selects status of using this portion of the SBA plugin (Dynamic Dropdowns).', :configuration_id:, 10, now(), NULL, 'zen_cfg_select_drop_down(array(array(\'id\'=>\'0\', \'text\'=>\'Off\'), array(\'id\'=>\'1\', \'text''=>\'On for All SBA Tracked\'), array(\'id\'=>\'2\', \'text''=>\'On for Multi-Attribute Only\'), array(\'id\'=>\'3\', \'text''=>\'On for Single-Attribute Only\'), ),'),
        ('Product Info Single Attribute Display Plugin', 'PRODINFO_ATTRIBUTE_PLUGIN_SINGLE', 'multiple_dropdowns', 'The plugin used for displaying attributes on the product information page.', :configuration_id:, 20, now(), NULL, 'zen_cfg_select_option(array(\'single_radioset\', \'single_dropdown\',\'multiple_dropdowns\',\'sequenced_dropdowns\',\'sba_sequenced_dropdowns\'),'),
	        ('Product Info Multiple Attribute Display Plugin', 'PRODINFO_ATTRIBUTE_PLUGIN_MULTI', 'sba_sequenced_dropdowns', 'The plugin used for displaying attributes on the product information page.', :configuration_id:, 30, now(), NULL, 'zen_cfg_select_option(array(\'single_radioset\', \'single_dropdown\',\'multiple_dropdowns\',\'sequenced_dropdowns\',\'sba_sequenced_dropdowns\'),'),
    ('Use ZC default HTML Attribute Tags', 'SBA_ZC_DEFAULT', 'true', 'Controls whether to use ZC HTML tags around attributes or to use the Dynamic Dropdown Version of the tags to support modifications made by others over the years but also compatibility with other ZC plugins.<br /><br />Options:<br />true (Default)<br />false.', :configuration_id:, 40, now(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
      ('Show Out of Stock Attributes', 'PRODINFO_ATTRIBUTE_SHOW_OUT_OF_STOCK', 'True', 'Controls the display of out of stock attributes.', :configuration_id:, 50, now(), NULL, 'zen_cfg_select_option(array(\'True\', \'False\'),'),
    ('Mark Out of Stock Attributes', 'PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK', 'Right', 'Controls how out of stock attributes are marked as out of stock.', :configuration_id:, 60, now(), NULL, 'zen_cfg_select_option(array(\'None\', \'Right\', \'Left\'),'),
      ('Display Out of Stock Message Line', 'PRODINFO_ATTRIBUTE_OUT_OF_STOCK_MSGLINE', 'True', 'Controls the display of a message line indicating an out of stock attributes is selected.', :configuration_id:, 70, now(), NULL, 'zen_cfg_select_option(array(\'True\', \'False\'),'),
			('Prevent Adding Out of Stock to Cart', 'PRODINFO_ATTRIBUTE_NO_ADD_OUT_OF_STOCK', 'True', 'Prevents adding an out of stock attribute combination to the cart.', :configuration_id:, 80, now(), NULL, 'zen_cfg_select_option(array(\'True\', \'False\'),'),
      ('SBA Number of Records to Displayed', 'STOCK_SET_SBA_NUMRECORDS', '25', 
				'Number of records to show on page:',
				:configuration_id:, 60, now(), NULL, NULL),
	  ('Display Javascript Popup for Out-of-Stock Selection', 'PRODINFO_ATTRIBUTE_POPUP_OUT_OF_STOCK', 'True', 'Controls whether to display or not the message for when a products attribute is out-of-stock.', :configuration_id:, 90, now(), NULL, 'zen_cfg_select_option(array(\'True\', \'False\'),')
    ;";
  $sql = $db->bindVars($sql, ':configuration_id:', $configuration_id, 'integer');
  $db->Execute($sql);

		if($db->error){		
			$msg = ' Error Message: ' . $db->error;
			$failed = true;
		}
		else{
			$list = '&bull; PRODINFO_ATTRIBUTE_PLUGIN_SINGLE<br />
              &bull; PRODINFO_ATTRIBUTE_PLUGIN_MULTI<br />
              &bull; PRODINFO_ATTRIBUTE_SHOW_OUT_OF_STOCK<br />
              &bull; PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK<br />
              &bull; PRODINFO_ATTRIBUTE_OUT_OF_STOCK_MSGLINE<br />
              &bull; PRODINFO_ATTRIBUTE_NO_ADD_OUT_OF_STOCK<br /> 
              &bull; STOCK_SET_SBA_NUMRECORDS';
			array_push($resultMmessage, 'Inserted into configuration: <br />' . $list);
		}
  
  
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

		$result = $db->Execute("CREATE TABLE IF NOT EXISTS `".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK."` (
		  `stock_id` int(11) NOT NULL AUTO_INCREMENT,
		  `products_id` int(11) NOT NULL,
		  `product_attribute_combo` varchar(255) DEFAULT NULL,
		  `stock_attributes` varchar(255) NOT NULL,
		  `quantity` float NOT NULL DEFAULT '0',
		  `sort` int(11) NOT NULL DEFAULT '0',
		  `customid` varchar(255) DEFAULT NULL,
		  `title` varchar(100) DEFAULT NULL,
		  PRIMARY KEY (`stock_id`),
		  UNIQUE KEY `idx_products_id_stock_attributes` (`products_id`,`stock_attributes`),
		  UNIQUE KEY `idx_products_id_attributes_id` (`product_attribute_combo`),
		  UNIQUE KEY `idx_customid` (`customid`)
		);");

		if($db->error){		
			$msg = ' Error Message: ' . $db->error;
			$failed = true;
		}
		array_push($resultMmessage, '<br /><b>Added New Table</b> products_with_attributes_stock. ' . $msg);
	}
	else{
		//Alter / upgrade existing database table
		alterSBAtabeSort();//Call function to Alter table products_with_attributes_stock sort field
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
  
  if(!checkSBAtable(TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK)) {
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
	
		if($db->error){		
			$msg = ' Error Message: ' . $db->error;
			$failed = true;
		}
		array_push($resultMmessage, '<br />Added Table orders_products_with_attributes_stock: ' . $msg);
	}
	else{
		//Alter / upgrade existing database table THIS NEEDS TO BE DEVELOPED
//		alterSBAtabeSort();//Call function to Alter table products_with_attributes_stock sort field
//		alterSBAtableCustomid();//Call function to Alter table products_with_attributes_stock to add customid
//		alterSBAtableUniqueIndex();//Call function to Alter table products_with_attributes_stock UNIQUE INDEX
	}
	return;
}

//Test that the table is already present, and that it does not already have the parentid field
//Upgrade existing table with parentid field
function alterSBAtableParentid(){
	global $db, $resultMmessage, $failed;

	if( checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK) ) {

		$sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
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
		}
			
		if(empty($num_rows)){
			//ADD COLUMN `parentid`
			$db->Execute("ALTER TABLE `" . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "`
									ADD COLUMN `parentid` int(11) NOT NULL DEFAULT '0' AFTER `title`;");

			if( $db->error ){
				$msg = ' Error Message: ' . $db->error;
				$failed = true;
				array_push($resultMmessage, '<b>Failure</b> while Adding parentid field to table products_with_attributes_stock. ' . $msg);
			}
			else{
				array_push($resultMmessage, '<b>Added</b> parentid field to table products_with_attributes_stock. ');
			}
				
		}
	}
	return;
}

//Test that the table is already present, and that it does not already have the title field
//Upgrade existing table with title field
function alterSBAtableTitle(){
	global $db, $resultMmessage, $failed;

	if( checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK) ) {

		$sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
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
		}
			
		if(empty($num_rows)){
			//ADD COLUMN `title`
			$db->Execute("ALTER TABLE `" . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "`
									ADD COLUMN `title` varchar(100) DEFAULT NULL AFTER `customid`;");
				
			if( $db->error ){
				$msg = ' Error Message: ' . $db->error;
				$failed = true;
				array_push($resultMmessage, '<b>Failure</b> while Adding title field to table products_with_attributes_stock. ' . $msg);
			}
			else{
				array_push($resultMmessage, '<b>Added</b> title field to table products_with_attributes_stock. ');
			}
							
		}
	}
	return;
}

//Test that the table is already present, and that it does not already have the product_attribute_combo field
//Upgrade existing table with product_attribute_combo field
function alterSBAtableUniqueCombo(){
	global $db, $resultMmessage, $failed;

	if( checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK) ) {

		$sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
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
		}
			
		if(empty($num_rows)){
			//ADD COLUMN `product_attribute_combo`
			$db->Execute("ALTER TABLE `" . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "`
									ADD COLUMN `product_attribute_combo` varchar(255) DEFAULT NULL AFTER `products_id`;");
				
			if( $db->error ){
				$msg = ' Error Message: ' . $db->error;
				$failed = true;
				array_push($resultMmessage, '<b>Failure</b> while Adding product_attribute_combo field to table products_with_attributes_stock. ' . $msg);
			}
			else{
				//ADD UNIQUE INDEX idx_products_id_attributes_id
				$db->Execute("ALTER TABLE " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "
										ADD UNIQUE INDEX idx_products_id_attributes_id (`product_attribute_combo`);");
			}

			if( !$db->error ){
				array_push($resultMmessage, '<b>Added</b> product_attribute_combo field to table products_with_attributes_stock. ');
			}
			else{
				$msg = ' Error Message: ' . $db->error;
				array_push($resultMmessage, '<b>Failure</b> while Adding UNIQUE INDEX idx_products_id_attributes_id to table products_with_attributes_stock. ' . $msg);
				$failed = true;
			}
				
		}
	}
	return;
}

//Test that the table is already present, and that it does not already have the customid field
//Upgrade existing table with customid field
function alterSBAtableCustomid(){
	global $db, $resultMmessage, $failed;
	
	if( checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK) ) {
	
		$sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
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
		}
			
		if(empty($num_rows)){
			//ADD COLUMN `customid`
			$db->Execute("ALTER TABLE `" . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . "`
									ADD COLUMN `customid` VARCHAR(255) NULL DEFAULT NULL AFTER `sort`;");
			
			if( $db->error ){			
				$msg = ' Error Message: ' . $db->error;
				$failed = true;
				array_push($resultMmessage, '<b>Failure</b> while Adding customid field to table products_with_attributes_stock. ' . $msg);
			}
			else{
				//ADD UNIQUE INDEX idx_customid
				$db->Execute("ALTER TABLE " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " 
										ADD UNIQUE INDEX idx_customid (`customid`);");
			}

			if( !$db->error ){
				array_push($resultMmessage, '<b>Added</b> customid field to table products_with_attributes_stock. ');
			}
			else{
				$msg = ' Error Message: ' . $db->error;
				array_push($resultMmessage, '<b>Failure</b> while Adding UNIQUE INDEX idx_customid to table products_with_attributes_stock. ' . $msg);
				$failed = true;
			}
			
		}
	}
	return;
}

//Test that the table is already present, and that it does not already have the UNIQUE INDEX
//Upgrade existing table with UNIQUE INDEX
function alterSBAtableUniqueIndex(){
	global $db, $resultMmessage, $failed;
	
	$sql = "SELECT * FROM information_schema.statistics
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
	}
	
	if(empty($num_rows)){
		//test for records that are not unique before adding UNIQUE INDEX
		$sql = "SELECT pas.stock_id, COUNT(pas.stock_id) AS stockCount
				FROM " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " pas
				GROUP BY pas.products_id, pas.stock_attributes
				HAVING stockCount > 1";
		$result = $db->Execute($sql);
		
		$num_rows = null;
		while (!$result->EOF) {
			if($result->fields['stockCount']){
				$num_rows = 1;
				$failed = true;
				array_push($resultMmessage, 'FAILURE: Can not add UNIQUE INDEX (products_id, stock_attributes) to the products_with_attributes_stock table, there are records that are not unique!');
				break; // No need to continue in loop as have met a failing condition.
			}	
			$result->MoveNext();
		}
		$num_rows = rtrim($num_rows, ', ');
		if(empty($num_rows)){
			$sql = "ALTER TABLE " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " ADD UNIQUE INDEX idx_products_id_stock_attributes (`products_id`, `stock_attributes`);"; //If this is going to be different than the previous version, then there should be part of the upgrade process that removes the old version(s).
			$db->Execute($sql);
			if($db->error){				
				$msg = ' Error Message: ' . $db->error;
				$failed = true;
			}
			array_push($resultMmessage, '<b>Altered Table</b> products_with_attributes_stock to add UNIQUE INDEX idx_products_id_stock_attributes (products_id, stock_attributes). ' . $msg);
		}
	}
	
	return;
}

function alterProductOptions(){
  
  
/*  ALTER TABLE products_options
  ADD products_options_track_stock tinyint(4) default '1' not null
  AFTER products_options_name;*/
	global $db, $resultMmessage, $failed;	
	
	$sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
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
 	}

	if(empty($num_rows)){
		$sql = "ALTER TABLE " . TABLE_PRODUCTS_OPTIONS." ADD products_options_track_stock tinyint(4) DEFAULT '1' NOT NULL AFTER `products_options_name`";
		$db->Execute($sql);
		if($db->error){	
			$msg = ' Error Message: ' . $db->error;
			$failed = true;
		}
		array_push($resultMmessage, '<b>Altered Table</b> products_options to add products_options_track_stock. ' . $msg);
	}
	else{
		$sql = "SELECT column_default 
				FROM INFORMATION_SCHEMA.COLUMNS 
				WHERE TABLE_SCHEMA = '".DB_DATABASE."'
					AND TABLE_NAME = '".TABLE_PRODUCTS_OPTIONS."'
					AND COLUMN_NAME = 'products_optinos_track_stock'";
		$result = $db->Execute($sql);
		$result = $result->fields['column_default'];
		
		if( $result === null ){
			$sql = "ALTER TABLE " . TABLE_PRODUCTS_OPTIONS." CHANGE COLUMN `products_options_track_stock` `products_options_track_stock` tinyINT(4) NOT NULL DEFAULT '1' AFTER `products_options_name`;";
			$db->Execute($sql);
			
			if($db->error){				
				$msg = ' Error Message: ' . $db->error;
				$failed = true;
			}
		
			array_push($resultMmessage, '<br /><b>Altered Table</b> products_options to add DEFAULT value of 1. ' . $msg);
		}
	}
	return;

}

//Test that the table is already present, and that it does not already have "sort INT NOT NULL"
//Upgrade existing table with "sort INT NOT NULL"
function alterSBAtabeSort(){
	global $db, $resultMmessage, $failed;	
	
	$sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
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
 	}

	if(empty($num_rows)){
		$sql = "ALTER TABLE " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." ADD sort INT(11) NOT NULL DEFAULT 0 AFTER `quantity`";
		$db->Execute($sql);
		if($db->error){	
			$msg = ' Error Message: ' . $db->error;
			$failed = true;
		}
		array_push($resultMmessage, '<b>Altered Table</b> products_with_attributes_stock to add sort. ' . $msg);
	}
	else{
		$sql = "SELECT column_default 
				FROM INFORMATION_SCHEMA.COLUMNS 
				WHERE TABLE_SCHEMA = '".DB_DATABASE."'
					AND TABLE_NAME = '".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK."'
					AND COLUMN_NAME = 'sort'";
		$result = $db->Execute($sql);
		$result = $result->fields['column_default'];
		
		if( $result === null ){
			$sql = "ALTER TABLE " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." CHANGE COLUMN `sort` `sort` INT(11) NOT NULL DEFAULT 0 AFTER `quantity`;";
			$db->Execute($sql);
			
			if($db->error){				
				$msg = ' Error Message: ' . $db->error;
				$failed = true;
			}
		
			array_push($resultMmessage, '<br /><b>Altered Table</b> products_with_attributes_stock to add DEFAULT value of 0. ' . $msg);
		}
	}
	return;
}

//Empty TRUNCATE the Product Attribute Stock Table
//Only needed it user wants to start over in the process of configuring the table without having to un-install the mod
function truncateProductAttributeStockTable(){
	//TRUNCATE `products_with_attributes_stock`;
	global $db, $resultMmessage, $failed;
	
	if(checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, null, false)) {
		
		$sql = "TRUNCATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.";";
		
		$db->Execute($sql);
		if($db->error){
			$msg = ' Error Message: ' . $db->error;
			$failed = true;
		}
		array_push($resultMmessage, 'Empty '.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.' table ' . $msg);
	}
	
	return;
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
	
	if(checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, null, false)) {
	
		$sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET `product_attribute_combo` = 
				replace(
				(SELECT CONCAT(".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.".products_id,'-',".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.".stock_attributes))
				, ',','-');";

		$db->Execute($sql);
		if($db->error){
			$msg = ' Error Message: ' . $db->error;
			$failed = true;
		}
		array_push($resultMmessage, 'product_attribute_combo field updated ' . $msg);
	}
	
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
	$insertQtyValue = 'p.products_quantity';
	
	//check if the required tables is present
	if(checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, null, false)) {
		
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
		if($db->error){		
			$msg = ' Error Message: ' . $db->error;
			$failed = true;
		}
		array_push($resultMmessage, 'Optional SQL file complete. ' . $msg);
	}
	else{
		array_push($resultMmessage, 'Optional SQL file result: Did NOT run, table does not exist.');
		$failed = true;
	}
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

	//check if the required table is present
	if(checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, null, false)) {
		
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
		if($db->error){	
		$msg = ' Error Message: ' . $db->error;
			$failed = true;
		}
		array_push($resultMmessage, 'Optional SQL file complete. ' . $msg);
	}
	else{
	array_push($resultMmessage, 'Optional SQL file result: Did NOT run, table does not exit.');
		$failed = true;
	}
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

	//check if the required table is present
	if(checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, null, false)) {
		
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
		if($db->error){	
		$msg = ' Error Message: ' . $db->error;
		$failed = true;
		}
		array_push($resultMmessage, 'Optional SQL file complete. ' . $msg);
	}
	else{
		array_push($resultMmessage, 'Optional SQL file result: Did NOT run, table does not exit.');
		$failed = true;
		}
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

	//check if the required tables is present
	if(checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, null, false)) {

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
		if($db->error){
		$msg = ' Error Message: ' . $db->error;
			$failed = true;
		}
		array_push($resultMmessage, 'Optional SQL file complete. ' . $msg);
	}
	else{
		array_push($resultMmessage, 'Optional SQL file result: Did NOT run, table does not exit.');
		$failed = true;
		}
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

	//check if the required table is present
	if(checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, null, false)) {
		$sql = 	"SELECT p.products_id, pa.products_attributes_id

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
          if ($sing_attribute == $prods_readonly_result->fields['products_attributes_id']) {
            unset($attributes_id[$loc]);
          }
        }
        if (sizeof($attributes_id)) {

          $sql = "SELECT pwas.stock_id FROM " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " pwas where products_id = :products_id: and stock_attributes = :stock_attributes:";
          $sql = $db->bindVars($sql, ':stock_attributes:', implode(',',$attributes_id), 'string');
          $sql = $db->bindVars($sql, ':products_id:', $prods_readonly_result->fields['products_id'], 'integer');
          $sql_result = $db->Execute($sql);

          if ($sql_result->RecordCount()) {
            $sql = "DELETE FROM " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " WHERE stock_id = :stock_id: and stock_attributes = :stock_attributes:";
          } else {
            $sql = "UPDATE " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " SET stock_attributes = :stock_attributes: where stock_id = :stock_id:";
          }

          $sql = $db->bindVars($sql, ':stock_id:', $attribute_stock->fields['stock_id'], 'integer');
          $sql = $db->bindVars($sql, ':stock_attributes:', implode(',',$attributes_id), 'string');

          $db->Execute($sql);
        } else {
          //Apparently removed all of the data associated with this record and the record should be deleted as there is nothing remaining to track.
          $sql = "DELETE FROM " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " WHERE stock_id = :stock_id:";
          $_SESSION['delete'.$attribute_stock->fields['stock_id']] = 'yes';
          $sql = $db->bindVars($sql, ':stock_id:', $attribute_stock->fields['stock_id'], 'integer');

          $db->Execute($sql);
        }
      
        $attribute_stock->MoveNext();  
      } // End of PWAS loop
      $prods_readonly_result->MoveNext();
    } //End of Products loop
		
    if($db->error){	
		$msg = ' Error Message: ' . $db->error;
		$failed = true;
		}
		array_push($resultMmessage, 'Optional SQL file complete. ' . $msg);
	}
	else{
		array_push($resultMmessage, 'Optional SQL file result: Did NOT run, table does not exist.');
		$failed = true;
		}
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
		if( $check->fields['COLUMN_NAME'] ){
			$result .= $check->fields['COLUMN_NAME'] . ' | ';
		}
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
		DIR_FS_CATALOG_TEMPLATES . $template_dir . '/jscript/jquery-1.10.2.min.js',

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
		DIR_FS_CATALOG . DIR_WS_INCLUDES . 'modules/pages/checkout_success/header_php_sba.php',

		DIR_FS_CATALOG . DIR_WS_INCLUDES . 'extra_cart_actions/stock_by_attributes.php',
		DIR_FS_CATALOG . DIR_WS_INCLUDES . 'extra_datafiles/products_with_attributes_stock_database_tables.php',
		DIR_FS_CATALOG . DIR_WS_INCLUDES . 'functions/extra_functions/products_with_attributes.php',
		DIR_FS_CATALOG . DIR_WS_INCLUDES . 'languages/english/extra_definitions/products_with_attributes.php',
		DIR_FS_CATALOG . DIR_WS_INCLUDES . 'modules/' . $template_dir .'/attributes.php',
	
		DIR_FS_CATALOG_TEMPLATES . $template_dir . '/templates/tpl_shopping_cart_default.php',			
		DIR_FS_CATALOG_TEMPLATES . $template_dir . '/templates/tpl_account_history_info_default.php',
		DIR_FS_CATALOG_TEMPLATES . $template_dir . '/templates/tpl_checkout_confirmation_default.php'
	);

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
	
	if(checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK)) {
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
	}
	else{
		array_push($resultMmessage, '<b>FAILED</b> table products_with_attributes_stock not found!');
		$failed = true;
	}
	
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
	
	if(checkSBAtable(TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK)) {
		$ReportFile = file($SBAtableReport);//get file data
	}
	else{
		array_push($resultMmessage, 'FAILED table products_with_attributes_stock not found!');
		$failed = true;
	}
	
	/* Only update the QTY and Custom ID fields
	 * checks input file data prior to loading to database, only numeric is allowed for QTY
	 */	
	IF($ReportFile){
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
	}
	else{
		array_push($resultMmessage, 'Update FAILED no file found!');
		$failed = true;
	}
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
				<form method='get' action='" . zen_href_link('stock_by_attr_install', '', 'NONSSL')."' >
				<ul>
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
				<li>Help</li>
					<ul>
					<li>Displays the main page, helps to explain the Script functions.</li>
					<li>No changes are made unless one of the other options are selected.</li>
					<li>Includes, a brief description of \"How To Use\".</li>
				    </ul>
							
				<li>Installation</li>
					<ul>
					<li>Full/Upgrade DB Install</li>
						<ul>
						<li>Full, makes all script changes to the database (DB) (i.e., adds new SBA table, adds entries into the Admin page, and new entries into the Configuration file).</li>
						<li>Upgrade, updates Configuration file and the SBA table as needed. If run again, it will \"Clean\" table entries and reapply the settings, it will not affect current data in the \"products_with_attributes_stock\" table.</li>
						</ul>
				    </ul>
				
				<li>Removal</li>
					<ul>
				    <li>Remove All from DB - Removes above changes from the database (DB).</li>
				    </ul>

				<li>Optional SQL Scripts</li>
					<ul>
				    <li>Default SQL - Only add the product attributes that are NOT read-only AND are NOT the new SBA selections.</li>
				    <li>Add all Product Attributes - Add all the products attributes.</li>
				    <li>Add read-only product attributes - Add only the read-only product attributes.</li>
				    <li>Add product attributes that are NOT read-only - Only add the product attributes that are NOT read-only.</li>							
					<li>Update Unique Combo field - Used to fill the new Unique Combo field in the PAS table, this number is the Product ID and the Attrubute ID.</li>
					<li>Remove ALL entries from the PAS Table - WARNING: This will COMPLETLY EMPTY the Product with Attribute Stock Table!</li>
				    </ul>
							
				<li>Tests</li>
					<ul>
	 				<li>File Check - Check that NEW Files are in proper places.</li>
					</ul>
				
				<li>Export / Import</li>
				<ul>
					<li>Export Table Data</li>
						<ul>
						<li>Exports the products_with_attributes_stock table as a CSV file.</li>
						<li>Use with the \"Import Table Data\" option.</li>
						</ul>
	 				<li>Import Table Data</li>
						<ul>
						<li>Imports the \"Quantity\" and \"Custom ID\" fields from a CSV file.</li>
						<li>Update quantity (quantity field) in products_with_attributes_stock table.</li>
						<li>Update customid (customid field) in products_with_attributes_stock table.</li>
						<li>customid must be unique, may be alphanumeric. NO duplicates permitted.</li>
						</ul>
			    	</ul>
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
	--	No changes to table names (prefix) are required if you use the SQL Query Executor
	--	from the admin (Tools\Install SQL Patches).
	--
	--	But, If you use a third party database tool, such as HeidiSQL, then you should read the following.
	-- 		The table names will need to be changed if your database uses a prefix such as \'zen_\'.
	--		Examples: 
	--			Change: products_with_attributes_stock 
	--			To: zen_products_with_attributes_stock 
	--			Change: products 
	--			To: zen_products.
	--			Change: products_attributes 
	--			To: zen_products_attributes.
	--			Change: products_options_values 
	--			To: zen_products_options_values.

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
							
		<form method="get" action="' . zen_href_link('stock_by_attr_install', '', 'NONSSL') . '" id="SBAinstall" name="SBAinstall">
 	  	
 		Select:

 		<select id="selectSBAinstall" name="selectSBAinstall">
			<option value="">Help</option>
			
			<optgroup label="Installation">
 			<option value="installAll">Full/Upgrade DB Install</option>
						
			<optgroup label="Removal">
	 		<option value="removeAll">Remove All from DB</option>
			
			<optgroup label="Optional SQL Scripts">
	 		<option value="runOptionalSQL1" title="Only add the product attributes that are NOT display-only AND are NOT the new SBA selections">Default SQL</option>
			<option value="runOptionalSQL2" title="Add all the products attributes">Add all Product Attributes</option>
			<option value="runOptionalSQL3" title="Add only the display-only product attributes">Add display-only product attributes</option>
			<option value="runOptionalSQL4" title="Only add the product attributes that are NOT display-only">Add product attributes that are NOT display-only</option>
			<option value="runOptionalSQL5" title="Remove the product attributes that are ONLY read-only">Remove product attributes that are ONLY read-only</option>
			<option value="updatePASfieldPAC" title="Update Unique Combo field">Update Unique Combo field</option>
			<option value="truncatePAStable" title="WARNING: This will COMPLETLY EMPTY the Product with Attribute Stock Table!">Remove ALL entries from the PAS Table</option>
			
			<optgroup label="Tests">
	 		<option value="checkFiles">File Check</option>
				
			<optgroup label="Export / Import" title="Update SBA table from CSV file">
			<option value="exportTable">Export Table Data</option>
			<option value="importTable">Import Table Data</option>
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
		insertSBAconfigurationMenu();//add install script to configuration menu
		insertSBAproductsOptionsTypes();//Call function to Add New entries	
		//Test for proper New file placement
		checkSBAfileLocation();//Call to check for proper placement of New files	
		echo showScriptResult('Full Install');//show script result
	}
	elseif($action == 'removeAll'){
		//Clean-up functions to remove database entries
		removeSBAconfiguration();//Call function to Remove configuration entries
		removeSBAadminPages();//Call function to Remove Admin Pages entry
    removeDynDropdownsConfiguration();
    removeDynDropdownsAdminPages();
		dropSBATable();//Call function to remove SBA table
//		dropSBAOrdersTable(); // Not sure this should be performed, as it will remove historical data.
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
