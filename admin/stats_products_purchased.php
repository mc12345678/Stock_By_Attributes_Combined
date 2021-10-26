<?php
/**
 * @package admin
 * @copyright Copyright 2003-2016 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Author: DrByte  Sat Oct 17 21:23:07 2015 -0400 Modified in v1.5.5 $
 */

  require('includes/application_top.php');

  $products_filter = (isset($_GET['products_filter']) ? $_GET['products_filter'] : $products_filter);
  $products_filter = str_replace(' ', ',', $products_filter);
  $products_filter = str_replace(',,', ',', $products_filter);
  $products_filter_name_model = (isset($_GET['products_filter_name_model']) ? $_GET['products_filter_name_model'] : $products_filter_name_model);
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" media="print" href="includes/stylesheet_print.css">
<link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
<script language="javascript" src="includes/menu.js"></script>
<script language="javascript" src="includes/general.js"></script>
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
<body onload="init()">
<!-- header //-->
<div class="header-area">
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
</div>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
<!-- body_text //-->

<!-- BOF FGB Attribute Sales Report -->



<table border="0" width="80%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="80%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo "Best Attributes Purchased"; ?></td>
            <td class="pageHeading" align="right"><?php echo zen_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
            <td class="smallText" align="right">
<?php
// field for user to enter two order numbers to search between
    echo zen_draw_form('search', FILENAME_STATS_PRODUCTS_PURCHASED, '', 'get', '', true);
    
    $pulldown_array = array();
	$pulldown_array[] = array('id' => '0', 'text' => 'Previous 2 months');
	$pulldown_array[] = array('id' => '1', 'text' => 'Previous 6 months');
	$pulldown_array[] = array('id' => '2', 'text' => 'Previous 12 months');
	
    
    echo "Select Timeframe for Report:" . ' ' . zen_draw_pull_down_menu('pulldown', $pulldown_array) . zen_hide_session_id();
    echo '<br/ >' . "Enter one product ID number:". ' ' . zen_draw_input_field('product_id') . zen_hide_session_id();
	?><input type="submit"><?php
	echo '</form>';


?>
            </td>
          </tr>
        </table></td>
      </tr>
      

      
<?php

  echo $_GET["order_numbers"]; ?><br><?php
  echo 'Sales of product ';
  echo $_GET["product_id"];

  // create orders_id_array to hold all orders_products_id arrays 
  $orders_id_array = array();
  $our_product_number = 7;
  $our_beginning_order_number = 26400;
  $our_ending_order_number = 60000;
  
  // if the user has entered and submitted some order numbers, use those
  // This has been turned off
  if (!(is_null($_GET["order_numbers"]))) {
    $order_numbers = explode(",", $_GET["order_numbers"]);
    $our_beginning_order_number = $order_numbers[0];
    $our_ending_order_number = $order_numbers[1];
  }

  // if the user has entered and submitted an item number, use that
  if (!(is_null($_GET["product_id"]))) {
    $our_product_number = $_GET["product_id"];
  }
  
  // Calculate the date two months in the past
  $past_date = date('Y-m-d', mktime(0, 0, 0, date("m") -2 , date("d"), date("Y")));
  
  // set the date range to reflect the selection in the dropdown menu
  switch ($_GET["pulldown"]) {
    case "2":
      echo ' in the past 12 ';
      $past_date = date('Y-m-d', mktime(0, 0, 0, date("m") -12 , date("d"), date("Y")));
      break;
    case "1":
      echo ' in the past 6 ';
      $past_date = date('Y-m-d', mktime(0, 0, 0, date("m") -6 , date("d"), date("Y")));
      break;
    case "0":
      echo ' in the past 2 ';
      $past_date = date('Y-m-d', mktime(0, 0, 0, date("m") -2 , date("d"), date("Y")));
      break;
  }
  echo 'months:';  ?><br><?php
  
  // Find an order number that matches that date 
  $orders_date_query_raw=
  "select o.orders_id 
  from ".TABLE_ORDERS." o 
  where o.date_purchased LIKE '%$past_date%' ";
    
  // fire off this query
  $orders_date = $db->Execute($orders_date_query_raw);

  // Set the beginning of the range to the order number from 2 months ago
  $our_beginning_order_number = ($orders_date->fields['orders_id']);
    
  // building query from orders_products table for product quantity, id, name, and orders_products_id
  // need to add user input fields for order range and product id
  $attributes_query_raw=
  "select op.products_quantity, op.orders_products_id, op.products_name, op.products_id
  from ".TABLE_ORDERS_PRODUCTS." op
  where (op.orders_id BETWEEN $our_beginning_order_number AND $our_ending_order_number) AND op.products_id = $our_product_number";
  
  // fire off this query
  $attributes = $db->Execute($attributes_query_raw);

  // loop though all returned records
  while(!$attributes->EOF) {
    
	// create orders_products_id_array to hold the fields for each purchase
    $orders_products_id_array = array();
    
    // push these onto our orders_id array
    array_push($orders_products_id_array, $attributes->fields['products_id'],
                                 $attributes->fields['orders_products_id'],
                                 $attributes->fields['products_quantity'],
                                 $attributes->fields['products_name']);
    
    // grab orders_products_id to use when filtering variants query
    $var_opi = $attributes->fields['orders_products_id'];
  
    // building query from orders_products_attributes table for product options values and orders_products_id
    $variants_query_raw=
    "select products_options_values, orders_products_id, products_options_values_id
    from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . "
    where orders_products_id=$var_opi";

    // fire off this query
    $variants = $db->Execute($variants_query_raw);
  
    // loop though all returned records
    while(!$variants->EOF) {
      
      // push the attribute ID to the end of our orders_id array
      $orders_products_id_array[] = $variants->fields['products_options_values_id'];

      // push this attribute to the end of our orders_id array
      $orders_products_id_array[] = $variants->fields['products_options_values'];
      
      // move on to the next attribute option value if there is one
      $variants->MoveNext();
    } // while loop completed; orders_products_id array completed
    
    // push this completed orders_products_id array onto the larger orders_id array
    $orders_id_array[] = $orders_products_id_array;
    
  // move on to the next orders_products_id if there is one  
  $attributes->MoveNext();
  } // while loop completed; orders_id array completed

// funtion to sort on the first attribute (e.g. tip size)
function cmp_first_attribute($a, $b) {
    if ($a[5] == $b[5]) {
      return strcmp($a[7], $b[7]);
    }
    return strcmp($a[5], $b[5]);
}

// function to sort on the second attribute (e.g. color)
function cmp_second_attribute($a, $b) {
    return strcmp($a[7], $b[7]);
}

// function to sort on the quantity
function cmp_quantity($a, $b) {
  if ($a[2] == $b[2]) {
    return 0;
  }
    return ($a[2] > $b[2]) ? -1 : 1;
}

// funtion to make a cleaner print_r
function print_r2($val){
        echo '<pre>';
        print_r($val);
        echo  '</pre>';
}

// sort the orders_id array so that all the matching products are adjacent
usort($orders_id_array, "cmp_first_attribute");

// we should check if this index exists before sorting, this might break on single attribute products
//usort($orders_id_array, "cmp_second_attribute");

// create new array to hold summed quantitys of attributes purchased
$summed_quantities = array();

// create tokens to hold our summed array index and product attribute options while traversing orders_id array
$summed_quantities_index = -1;
$product_name_token = array(4=>'',5=>'');

// create loop that pulls each unique combination of attributes out of orders_id array` and summs their quantities
foreach ($orders_id_array as $orders_id_line) {
  
  // if we are starting on a new set of attribtues, set the name token and incriment the summed array index
  if (($orders_id_line[5] != $product_name_token[5]) || ($orders_id_line[7] != $product_name_token[7])) {  
    $product_name_token[5] = $orders_id_line[5];
    $product_name_token[7] = $orders_id_line[7];
    $summed_quantities_index++;

	// since we are on a new set of attributes, populate the next array in summmed_quantities with 
	// our new info, and set quantity to zero
    $summed_quantities[$summed_quantities_index] = $orders_id_line;
    $summed_quantities[$summed_quantities_index][2] = 0;
  }

  // if this is another instance of the same set of attributes we were already working with,
  // add the quanitity of this new instance to our running total
  $summed_quantities[$summed_quantities_index][2] = $summed_quantities[$summed_quantities_index][2] + $orders_id_line[2];
}

// we use this counter to know which sub-array we are in, it is incrimented at the end of the foreach
$i = 0;
$quantities_result; 

// Query products_attributes_stock table for quantities of these combos
foreach ($summed_quantities as $summed_quantities_variety) {

  // We can only make this query if the product has at least one attribute
  if (isset($summed_quantities_variety[6]) || isset($summed_quantities_variety[4])) {
    // Use the product id and product options values id we got from the orders_products_attributes table to find the products_attributes_id of the first attribute
    $products_attributes_id_query_raw=
    "select pai.products_attributes_id
    from " . TABLE_PRODUCTS_ATTRIBUTES . " pai
    where pai.options_values_id = $summed_quantities_variety[4] AND pai.products_id = $summed_quantities_variety[0]";

    // fire off the query
    $attributes_component1 = $db->Execute($products_attributes_id_query_raw);
  }
  
  // If there are no attributes
  if (!isset($summed_quantities_variety[6]) && !isset($summed_quantities_variety[4])) {
  
    // need to get the simple quanity in the inventory here $our_product_number
        
    $quantities_query_raw=
	"select q.products_quantity
	from " . TABLE_PRODUCTS . " q
	where q.products_id = $our_product_number";
	
	$quantities_result1 = $db->Execute($quantities_query_raw);

    // add the quantity in stock to the array
  	$quantities_result = $quantities_result1->fields['products_quantity'] ;
  
  }
  // If there is no second attribute
  else if (!isset($summed_quantities_variety[6])) {

    // put the attribute ID we got in a variable
    $product_attributes_id = $attributes_component1->fields['products_attributes_id'];
    
    $quantities_query_raw=
	"select q.quantity
	from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " q
	where q.products_id = $summed_quantities_variety[0] AND q.stock_attributes LIKE $product_attributes_id";
	
	$quantities_result1 = $db->Execute($quantities_query_raw);

    // add the quantity in stock to the array
  	$quantities_result = $quantities_result1->fields['quantity'] ;
  	
  }
  else {
  
    // Use the product id and product options values id we got from the orders_products_attributes table to find the products_attributes_id of the second attribute
    $products_attributes_id_query_raw=
    "select pai.products_attributes_id
    from " . TABLE_PRODUCTS_ATTRIBUTES . " pai
    where pai.options_values_id = $summed_quantities_variety[6] AND pai.products_id = $summed_quantities_variety[0]";

    // fire off the query  
    $attributes_component2 = $db->Execute($products_attributes_id_query_raw);

	// the attribute IDs can be in either order in the database, so we have to build the combo both ways (e.g. 917,123 and 123,917)
	$attributes_combo1 = $attributes_component2->fields['products_attributes_id'] . "," . $attributes_component1->fields['products_attributes_id'];
	$attributes_combo2 = $attributes_component1->fields['products_attributes_id'] . "," . $attributes_component2->fields['products_attributes_id'];
	
	// try to grab the quantity with one attributes combo order
	$quantities_query_raw=
	"select q.quantity
	from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " q
	where q.products_id = $summed_quantities_variety[0] AND q.stock_attributes LIKE '$attributes_combo1'";
	
	$quantities_result1 = $db->Execute($quantities_query_raw);
	
	// try to grab the quantity with the other attributes combo order
	$quantities_query_raw=
	"select q.quantity
	from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " q
	where q.products_id = $summed_quantities_variety[0] AND q.stock_attributes LIKE '$attributes_combo2'";
	
	$quantities_result2 = $db->Execute($quantities_query_raw);
	
	// since only one of the results will have returned a number, and the other will be empty, we can add them to get the one result
	$quantities_result = $quantities_result2->fields['quantity'] + $quantities_result1->fields['quantity'] ;
	
  }

  // add the quantity in stock to the end of our big summed_quantities array
    $summed_quantities[$i][8] = $quantities_result; 
  
  // Figure out if the amount in stock is less than the amount sold during the time period
  if ($quantities_result <= $summed_quantities_variety[2]){
    // if we are in the danger zone, add yes to the end of the array
    $summed_quantities[$i][9] = 'Yes'; 
    
  }

  // incriment the counter since we are moving on to the next sub array
  $i++;

} // end of the foreach loop

// sort our summed list in descending order by quantity
usort($summed_quantities, "cmp_quantity");

// somehow zero attribute products are all messed up
// they just end up with some gibberish in index 1 and 2 so...
// instead of figuing that out I will just start over if that is the case

if (!isset($summed_quantities[0][0])) {

  // initialize summed quantities array
  $summed_quantities = array();
  
  // We already know our product number, so we can put that in
  $summed_quantities[0][0] = $our_product_number;
  
  // We correctly calculated the stock in inventory, so put that in
  $summed_quantities[0][8] = $quantities_result;


  // grab all orders with our zero attribute product and note quantities
  $zero_attributes_query_raw=
    "select op.products_quantity, op.products_name
    from " . TABLE_ORDERS_PRODUCTS . " op
    where (orders_id BETWEEN $our_beginning_order_number AND $our_ending_order_number) AND products_id = $our_product_number";


  // fire off this query
  $zeroAttributes = $db->Execute($zero_attributes_query_raw);
  
  // loop through the results and tabulate the quantity
  while(!$zeroAttributes->EOF){
  
    // add to the running total
    $summed_quantities[0][2] =  $summed_quantities[0][2] + $zeroAttributes->fields['products_quantity'];
    $zeroAttributes->MoveNext();
  }

  // put the product name in there
  $summed_quantities[0][3] = $zeroAttributes->fields['products_name'];

// see if stock is low
if ($summed_quantities[0][8] <= $summed_quantities[0][2]){
    // if we are in the danger zone, add yes to the end of the array
    $summed_quantities[0][9] = 'Yes';   
  }

}


// build table header row for Best Attributes Table
?>
      <tr>
        <td><table border="0" width="90%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="80%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo "Product Name"; ?></td>
                <td class="dataTableHeadingContent"><?php echo "Attribute 1"; ?></td>
                <td class="dataTableHeadingContent"><?php echo "Attribute 2"; ?></td>
                <td class="dataTableHeadingContent"><?php echo "Sold"; ?></td>
                <td class="dataTableHeadingContent"><?php echo "Stock"; ?></td>
                <td class="dataTableHeadingContent"><?php echo "Low"; ?></td>
              </tr>
<?php
  if (isset($_GET['page']) && ($_GET['page'] > 1)) $rows = $_GET['page'] * MAX_DISPLAY_SEARCH_RESULTS_REPORTS - MAX_DISPLAY_SEARCH_RESULTS_REPORTS;

  $rows = 0;
  foreach ($summed_quantities as $summed_quantity) {
    $rows++;

    if (strlen($rows) < 2) {
      $rows = '0' . $rows;
    }?>    
	<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
	  <td class="dataTableContent"><?php echo $summed_quantity[3]; ?>&nbsp;&nbsp;</td>
	  <td class="dataTableContent"><?php echo $summed_quantity[5]; ?></td>
	  <td class="dataTableContent"><?php echo $summed_quantity[7]; ?></td>
	  <td class="dataTableContent"><?php echo $summed_quantity[2]; ?></td>
	  <td class="dataTableContent"><?php echo $summed_quantity[8]; ?></td>
	  <td class="dataTableContent"><?php echo $summed_quantity[9]; ?></td>
	</tr><?php             
}
?>
            </table></td>
          </tr>
          <tr>
            <td colspan="3"><table border="0" width="80%" cellspacing="0" cellpadding="2">
              <tr>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></td>
      

<!-- EOF FGB Attribute Sales Report -->      



    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo zen_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
            <td class="smallText" align="right">
<?php
// show reset search
    echo zen_draw_form('search', FILENAME_STATS_PRODUCTS_PURCHASED, '', 'get', '', true);
    echo HEADING_TITLE_SEARCH_DETAIL_REPORTS . ' ' . zen_draw_input_field('products_filter') . zen_hide_session_id();
    if (isset($products_filter) && zen_not_null($products_filter)) {
      $products_filter = preg_replace('/[^0-9,]/', '', $products_filter);
      $products_filter = zen_db_input(zen_db_prepare_input($products_filter));
      echo '<br/ >' . TEXT_INFO_SEARCH_DETAIL_FILTER . $products_filter;
    }
    if (isset($products_filter) && zen_not_null($products_filter)) {
      echo '<br/ >' . '<a href="' . zen_href_link(FILENAME_STATS_PRODUCTS_PURCHASED, '', 'NONSSL') . '">' . zen_image_button('button_reset.gif', IMAGE_RESET) . '</a>&nbsp;&nbsp;';
    }
    echo '</form>';

// show reset search
    echo zen_draw_form('search', FILENAME_STATS_PRODUCTS_PURCHASED, '', 'get', '', true);
    echo '<br/ >' . HEADING_TITLE_SEARCH_DETAIL_REPORTS_NAME_MODEL . ' ' . zen_draw_input_field('products_filter_name_model') . zen_hide_session_id();
    if (isset($products_filter_name_model) && zen_not_null($products_filter_name_model)) {
      $products_filter_name_model = zen_db_input(zen_db_prepare_input($products_filter_name_model));
      echo '<br/ >' . TEXT_INFO_SEARCH_DETAIL_FILTER . zen_db_prepare_input($products_filter_name_model);
    }
    if (isset($products_filter_name_model) && zen_not_null($products_filter_name_model)) {
      echo '<br/ >' . '<a href="' . zen_href_link(FILENAME_STATS_PRODUCTS_PURCHASED, '', 'NONSSL') . '">' . zen_image_button('button_reset.gif', IMAGE_RESET) . '</a>&nbsp;&nbsp;';
    }
    echo '</form>';
?>
            </td>
          </tr>
        </table></td>
      </tr>
<?php
if ($products_filter > 0 or $products_filter_name_model != '') {
  if ($products_filter > 0) {
    // by products_id
    $chk_orders_products_query = "SELECT o.customers_id, op.orders_id, op.products_id, op.products_quantity, op.products_name, op.products_model,
                                  o.customers_name, o.customers_company, o.customers_email_address, o.date_purchased
                                  FROM " . TABLE_ORDERS . " o, " . TABLE_ORDERS_PRODUCTS . " op
                                  WHERE op.products_id in (" . $products_filter . ")
                                  and op.orders_id = o.orders_id
                                  ORDER by op.products_id, o.date_purchased DESC";
  } else {
    // by products name or model
    $chk_orders_products_query = "SELECT o.customers_id, op.orders_id, op.products_id, op.products_quantity, op.products_name, op.products_model,
                                  o.customers_name, o.customers_company, o.customers_email_address, o.date_purchased
                                  FROM " . TABLE_ORDERS . " o, " . TABLE_ORDERS_PRODUCTS . " op
                                  WHERE ((op.products_model LIKE '%" . $products_filter_name_model . "%')
                                  or (op.products_name LIKE '%" . $products_filter_name_model . "%'))
                                  and op.orders_id = o.orders_id
                                  ORDER by op.products_id, o.date_purchased DESC";
}
  $chk_orders_products_query_numrows='';
  $chk_orders_products_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS_REPORTS, $chk_orders_products_query, $chk_orders_products_query_numrows);

  $rows = 0;
  $chk_orders_products = $db->Execute($chk_orders_products_query);
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CUSTOMERS_ID; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_ORDERS_ID; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_ORDERS_DATE_PURCHASED; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CUSTOMERS_INFO; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_PRODUCTS_QUANTITY; ?>&nbsp;</td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_PRODUCTS_NAME; ?>&nbsp;</td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?>&nbsp;</td>
              </tr>

<?php
  if ($chk_orders_products->EOF) {
?>
              <tr class="dataTableRowSelectedBot">
                <td colspan="7" class="dataTableContent" align="center"><?php echo NONE; ?></td>
              </tr>
<?php } ?>
<?php
  while (!$chk_orders_products->EOF) {
    $rows++;

    if (strlen($rows) < 2) {
      $rows = '0' . $rows;
    }
    if ($products_filter != '') {
    // products_id
      $cPath = zen_get_product_path($products_filter);
    } else {
    // products_name or products_model
      $cPath = zen_get_product_path($chk_orders_products->fields['products_id']);
    }
?>
              <tr class="dataTableRow">
                <td class="dataTableContent"><?php echo '<a href="' . zen_href_link(FILENAME_CUSTOMERS, zen_get_all_get_params(array('cID', 'action', 'page', 'products_filter')) . 'cID=' . $chk_orders_products->fields['customers_id'] . '&action=edit', 'NONSSL') . '">' . $chk_orders_products->fields['customers_id'] . '</a>'; ?></td>
                <td class="dataTableContent"><?php echo '<a href="' . zen_href_link(FILENAME_ORDERS, zen_get_all_get_params(array('oID', 'action', 'page', 'products_filter')) . 'oID=' . $chk_orders_products->fields['orders_id'] . '&action=edit', 'NONSSL') . '">' . $chk_orders_products->fields['orders_id'] . '</a>'; ?></td>
                <td class="dataTableContent"><?php echo zen_date_short($chk_orders_products->fields['date_purchased']); ?></td>
                <td class="dataTableContent"><?php echo $chk_orders_products->fields['customers_name'] . ($chk_orders_products->fields['customers_company'] !='' ? '<br />' . $chk_orders_products->fields['customers_company'] : '') . '<br />' . $chk_orders_products->fields['customers_email_address']; ?></td>
                <td class="dataTableContent" align="center"><?php echo $chk_orders_products->fields['products_quantity']; ?>&nbsp;</td>
                <td class="dataTableContent" align="center"><?php echo '<a href="' . zen_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $products_filter) . '">' . $chk_orders_products->fields['products_name'] . '</a>'; ?>&nbsp;</td>
                <td class="dataTableContent" align="center"><?php echo $chk_orders_products->fields['products_model']; ?>&nbsp;</td>

              </tr>
<?php
    $chk_orders_products->MoveNext();
  }
?>
            </table></td>
          </tr>
          <tr>
            <td colspan="3"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="smallText" valign="top"><?php echo $chk_orders_products_split->display_count($chk_orders_products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS_REPORTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_PRODUCTS); ?></td>
                <td class="smallText" align="right"><?php echo $chk_orders_products_split->display_links($chk_orders_products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS_REPORTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page'], zen_get_all_get_params(array('page', 'x', 'y'))); ?>&nbsp;</td>
              </tr>
            </table></td>
          </tr>

<?php
} else {
// all products by name and quantity display
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_NUMBER; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_PURCHASED; ?>&nbsp;</td>
              </tr>
<?php
  if (isset($_GET['page']) && ($_GET['page'] > 1)) $rows = $_GET['page'] * MAX_DISPLAY_SEARCH_RESULTS_REPORTS - MAX_DISPLAY_SEARCH_RESULTS_REPORTS;
// The following OLD query only considers the "products_ordered" value from the products table.
// Thus this older query is somewhat deprecated
  $products_query_raw = "SELECT p.products_id, sum(p.products_ordered) as products_ordered, pd.products_name
                         FROM " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd
                         WHERE pd.products_id = p.products_id
                         AND pd.language_id = '" . $_SESSION['languages_id']. "'
                         AND p.products_ordered > 0
                         GROUP BY p.products_id, pd.products_name
                         ORDER BY p.products_ordered DESC, pd.products_name";

// The new query uses real order info from the orders_products table, and is theoretically more accurate.
// To use this newer query, remove the "1" from the following line ($products_query_raw1 becomes $products_query_raw )
    $products_query_raw1 =
      "SELECT sum(op.products_quantity) as products_ordered, pd.products_name, op.products_id
       FROM ".TABLE_ORDERS_PRODUCTS." op
       LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd
        ON (pd.products_id = op.products_id )
       WHERE pd.language_id = '" . $_SESSION['languages_id']. "'
       GROUP BY op.products_id, pd.products_name
       ORDER BY products_ordered DESC, products_name";

  $products_query_numrows='';
  $products_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS_REPORTS, $products_query_raw, $products_query_numrows);

  $rows = 0;
  $products = $db->Execute($products_query_raw);
  while (!$products->EOF) {
    $rows++;

    if (strlen($rows) < 2) {
      $rows = '0' . $rows;
    }
    $cPath = zen_get_product_path($products->fields['products_id']);
?>
              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href='<?php echo zen_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $products->fields['products_id'] . '&page='); ?>'">
                <td class="dataTableContent" align="right"><?php echo '<a href="' . zen_href_link(FILENAME_STATS_PRODUCTS_PURCHASED, zen_get_all_get_params(array('oID', 'action', 'page', 'products_filter')) . 'products_filter=' . $products->fields['products_id']) . '">' . $products->fields['products_id'] . '</a>'; ?>&nbsp;&nbsp;</td>
                <td class="dataTableContent"><?php echo '<a href="' . zen_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $products->fields['products_id'] . '&page=') . '">' . $products->fields['products_name'] . '</a>'; ?></td>
                <td class="dataTableContent" align="center"><?php echo $products->fields['products_ordered']; ?>&nbsp;</td>
              </tr>
<?php
    $products->MoveNext();
  }
?>
            </table></td>
          </tr>
          <tr>
            <td colspan="3"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="smallText" valign="top"><?php echo $products_split->display_count($products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS_REPORTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_PRODUCTS); ?></td>
                <td class="smallText" align="right"><?php echo $products_split->display_links($products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS_REPORTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?>&nbsp;</td>
              </tr>
            </table></td>
          </tr>
<?php
} // $products_filter > 0
?>
        </table></td>
      </tr>
    </table></td>
<!-- body_text_eof //-->
  </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<div class="footer-area">
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
</div>
<!-- footer_eof //-->
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>