<?php
/**
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2020 May 20 Modified in v1.5.7 $
 * 
 * Stock by Attributes 1.5.4 2020-07-03 mc12345678 @ mc12345678.com
 */
require('includes/application_top.php');

require(DIR_WS_CLASSES . 'currencies.php');
$currencies = new currencies();

$oID = zen_db_prepare_input($_GET['oID']);

include DIR_FS_CATALOG . DIR_WS_CLASSES . 'order.php';
$order = new order($oID);
$show_including_tax = (DISPLAY_PRICE_WITH_TAX == 'true'); 

// prepare order-status pulldown list
$orders_statuses = array();
$orders_status_array = array();
$orders_status = $db->Execute("SELECT orders_status_id, orders_status_name
                               FROM " . TABLE_ORDERS_STATUS . "
                               WHERE language_id = " . (int)$_SESSION['languages_id']);
foreach ($orders_status as $order_status) {
  $orders_statuses[] = array(
    'id' => $order_status['orders_status_id'],
    'text' => $order_status['orders_status_name'] . ' [' . $order_status['orders_status_id'] . ']');
  $orders_status_array[$order_status['orders_status_id']] = $order_status['orders_status_name'];
}

$show_customer = false;
if ($order->billing['name'] != $order->delivery['name']) {
  $show_customer = true;
}
if ($order->billing['street_address'] != $order->delivery['street_address']) {
  $show_customer = true;
}
?>
<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
  <head>
    <meta charset="<?php echo CHARSET; ?>">
    <title><?php echo TITLE; ?></title>
    <link rel="stylesheet" href="includes/stylesheet.css">
    <script>
      function couponpopupWindow(url) { /* just a stub for coupon output that might fire it */ }
    </script>
  </head>
  <body>
    <div class="container">
      <!-- body_text //-->
      <table class="table">
        <tr>
          <td class="pageHeading"><?php echo nl2br(STORE_NAME_ADDRESS); ?></td>
          <td class="pageHeading" align="right"><?php echo zen_image(DIR_WS_IMAGES . HEADER_LOGO_IMAGE, HEADER_ALT_TEXT); ?></td>
        </tr>
      </table>
      <div><?php echo zen_draw_separator(); ?></div>
      <table class="table">
          <?php
          if ($show_customer == true) {
            ?>
          <tr>
            <td class="main" colspan="2"><b><?php echo ENTRY_CUSTOMER; ?></b></td>
          </tr>
          <tr>
            <td class="main" colspan="2"><?php echo zen_address_format($order->customer['format_id'], $order->customer, 1, '', '<br>'); ?></td>
          </tr>
        <?php } ?>
        <tr>
          <td style="border: none">
            <table>
              <tr>
                <td class="main"><b><?php echo ENTRY_SOLD_TO; ?></b></td>
              </tr>
              <tr>
                <td class="main"><?php echo zen_address_format($order->billing['format_id'], $order->billing, 1, '', '<br>'); ?></td>
              </tr>
              <tr>
                <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
              </tr>
              <tr>
                <td class="main">
                    <?php echo ENTRY_TELEPHONE_NUMBER . ' ' . $order->customer['telephone']; ?>
                </td>
              </tr>
              <tr>
                <td class="main"><?php echo '<a href="mailto:' . $order->customer['email_address'] . '">' . $order->customer['email_address'] . '</a>'; ?></td>
              </tr>
            </table>
          </td>
          <td style="border: none">
            <table>
              <tr>
                <td class="main"><b><?php echo ENTRY_SHIP_TO; ?></b></td>
              </tr>
              <tr>
                <td class="main"><?php echo zen_address_format($order->delivery['format_id'], $order->delivery, 1, '', '<br>'); ?></td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
      <table>
        <tr>
          <td class="main"><strong><?php echo ENTRY_ORDER_ID; ?></strong></td>
          <td class="main"><?php echo $oID; ?></td>
        </tr>
        <tr>
          <td class="main"><strong><?php echo ENTRY_DATE_PURCHASED; ?></strong></td>
          <td class="main"><?php echo zen_date_long($order->info['date_purchased']); ?></td>
        </tr>
        <tr>
          <td class="main"><strong><?php echo ENTRY_PAYMENT_METHOD; ?></strong></td>
          <td class="main"><?php echo $order->info['payment_method']; ?></td>
        </tr>
      </table>
      <div><?php echo zen_draw_separator('pixel_trans.gif', '', '10'); ?></div>
      <table class="table table-striped">
        <thead>
          <tr class="dataTableHeadingRow">
            <th class="dataTableHeadingContent" colspan="2"><?php echo TABLE_HEADING_PRODUCTS; ?></th>
            <th class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?></th>
            <th class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_TAX; ?></th>
            <th class="dataTableHeadingContent text-right"><?php echo ($show_including_tax) ? TABLE_HEADING_PRICE_EXCLUDING_TAX : TABLE_HEADING_PRICE; ?></th>
<?php if ($show_including_tax)  { ?>
            <th class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_PRICE_INCLUDING_TAX; ?></th>
<?php } ?>
            <th class="dataTableHeadingContent text-right"><?php echo ($show_including_tax) ? TABLE_HEADING_TOTAL_EXCLUDING_TAX : TABLE_HEADING_TOTAL; ?></th>
<?php if ($show_including_tax)  { ?>
            <th class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_TOTAL_INCLUDING_TAX; ?></th>
<?php } ?>
          </tr>
        </thead>
        <tbody>
            <?php

  // START "Stock by Attributes" sba - Change 1 of 3
  //include language file
  include(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . 'products_with_attributes_stock.php');
  //new object from class
  //require_once(DIR_WS_CLASSES . 'products_with_attributes_stock.php');
  //$stock = new products_with_attributes_stock;
  // END "Stock by Attributes" sba - Change 1 of 3

            $decimals = $currencies->get_decimal_places($order->info['currency']);
            for ($i = 0, $n = sizeof($order->products); $i < $n; $i++) {
              if (DISPLAY_PRICE_WITH_TAX_ADMIN == 'true') {
                $priceIncTax = $currencies->format(zen_round(zen_add_tax($order->products[$i]['final_price'], $order->products[$i]['tax']), $decimals) * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']);
              } else {
                $priceIncTax = $currencies->format(zen_add_tax($order->products[$i]['final_price'], $order->products[$i]['tax']) * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']);
              }
              ?>
            <tr class="dataTableRow">
              <td class="dataTableContent text-right">
                <?php echo $order->products[$i]['qty']; ?>&nbsp;x
              </td>
              <td class="dataTableContent"><?php echo $order->products[$i]['name']; ?>
                  <?php
                  if (isset($order->products[$i]['attributes']) && (($k = sizeof($order->products[$i]['attributes'])) > 0)) {
                    ?>
                  <ul>
                      <?php
                      for ($j = 0; $j < $k; $j++) {
                        // BOF "Stock by Attributes" add custom ID to display sba - Change 2 of 3
                        $customid = null;
                        //test if this is to be displayed
                        if( defined('STOCK_SBA_DISPLAY_CUSTOMID') && STOCK_SBA_DISPLAY_CUSTOMID == 'true'){
                          $attributes = array();
                          //create array for use in zen_get_customid
                          $attributes[] = $order->products[$i]['attributes'][$j]['value_id'];//value_id option_id
                          //get custom ID
                          $customid = ' ' . $products_with_attributes_stock_class->zen_get_customid($order->products[$i]['id'],$attributes) . ' ';
                          //only display custom ID if exists
                          if( !empty($customid) ){
                            //add name prefix (this is set in the admin language file)
                            $customid = PWA_CUSTOMID_NAME . $customid;
                          }
                        }
                        // EOF "Stock by Attributes" add custom ID to display sba - Change 2 of 3
                        ?>
                      <li>
                        <small>
                          <i>
                          <?php echo $order->products[$i]['attributes'][$j]['option'] . ': ' . nl2br(zen_output_string_protected($order->products[$i]['attributes'][$j]['value'])); ?>
                          <?php
                                //"Stock by Attributes" add custom ID to display sba - Change 3 of 3
                                echo ( defined('STOCK_SBA_DISPLAY_CUSTOMID') && STOCK_SBA_DISPLAY_CUSTOMID == 'true' ? ( $customid != '' ? ' (' . $customid . ') ' : '') : '' );
                                // END "Stock by Attributes" sba - Change 3 of 3
                                if ($order->products[$i]['attributes'][$j]['price'] != '0') {
                                  echo ' (' . $order->products[$i]['attributes'][$j]['prefix'] . $currencies->format($order->products[$i]['attributes'][$j]['price'] * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']) . ')';
                                }
                                if ($order->products[$i]['attributes'][$j]['product_attribute_is_free'] == '1' and $order->products[$i]['product_is_free'] == '1') {
                                  echo TEXT_INFO_ATTRIBUTE_FREE;
                                }
                          ?>
                          </i>
                        </small>
                      </li>
                      <?php
                    }
                    ?>
                  </ul>
                  <?php
                }
                ?>
              </td>
              <td class="dataTableContent">
                <?php echo $order->products[$i]['model']; ?>
              </td>
              <td class="dataTableContent text-right">
                <?php echo zen_display_tax_value($order->products[$i]['tax']); ?>%
              </td>
              <td class="dataTableContent text-right">
                <strong><?php echo $currencies->format($order->products[$i]['final_price'], true, $order->info['currency'], $order->info['currency_value']) . ($order->products[$i]['onetime_charges'] != 0 ? '<br />' . $currencies->format($order->products[$i]['onetime_charges'], true, $order->info['currency'], $order->info['currency_value']) : ''); ?></strong>
              </td>
<?php if ($show_including_tax)  { ?>
              <td class="dataTableContent text-right">
                <strong><?php echo $currencies->format(zen_add_tax($order->products[$i]['final_price'], $order->products[$i]['tax']), true, $order->info['currency'], $order->info['currency_value']) . ($order->products[$i]['onetime_charges'] != 0 ? '<br />' . $currencies->format(zen_add_tax($order->products[$i]['onetime_charges'], $order->products[$i]['tax']), true, $order->info['currency'], $order->info['currency_value']) : ''); ?></strong>
              </td>
<?php } ?>
              <td class="dataTableContent text-right">
                <strong><?php echo $currencies->format(zen_round($order->products[$i]['final_price'], $decimals) * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']) . ($order->products[$i]['onetime_charges'] != 0 ? '<br />' . $currencies->format($order->products[$i]['onetime_charges'], true, $order->info['currency'], $order->info['currency_value']) : ''); ?></strong>
              </td>
<?php if ($show_including_tax)  { ?>
              <td class="dataTableContent text-right" valign="top">
                <strong>
                  <?php echo $priceIncTax; ?>
                  <?php if ($order->products[$i]['onetime_charges'] != 0) {
                      echo '<br />' . $currencies->format(zen_add_tax($order->products[$i]['onetime_charges'], $order->products[$i]['tax']), true, $order->info['currency'], $order->info['currency_value']);
                  }
                  ?>
                </strong>
              </td>
<?php } ?>
            </tr>
            <?php
          }
          ?>
        </tbody>
      </table>
      <table class="table">
          <?php
          for ($i = 0, $n = sizeof($order->totals); $i < $n; $i++) {
            ?>
          <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td class="text-right <?php echo str_replace('_', '-', $order->totals[$i]['class']); ?>-Text"><?php echo $order->totals[$i]['title']; ?></td>
            <td class="text-right <?php echo str_replace('_', '-', $order->totals[$i]['class']); ?>-Amount"><?php echo $order->totals[$i]['text']; ?></td>
          </tr>
          <?php
        }
        ?>
      </table>
      <?php if (ORDER_COMMENTS_INVOICE > 0) { ?>
        <table class="table table-condensed" style="width:25%;">
          <thead>
            <tr>
              <th class="text-center"><strong><?php echo TABLE_HEADING_DATE_ADDED; ?></strong></th>
              <th class="text-center"><strong><?php echo TABLE_HEADING_STATUS; ?></strong></th>
              <th class="text-center"><strong><?php echo TABLE_HEADING_COMMENTS; ?></strong></th>
            </tr>
          </thead>
          <tbody>
              <?php
              $orders_history = $db->Execute("SELECT orders_status_id, date_added, customer_notified, comments
                                            FROM " . TABLE_ORDERS_STATUS_HISTORY . "
                                            WHERE orders_id = " . zen_db_input($oID) . "
                                            AND customer_notified >= 0
                                            ORDER BY date_added");

              if ($orders_history->RecordCount() > 0) {
                $count_comments = 0;
                foreach ($orders_history as $order_history) {
                  $count_comments++;
                  ?>
                <tr>
                  <td class="text-center"><?php echo zen_datetime_short($order_history['date_added']); ?></td>
                  <td><?php echo $orders_status_array[$order_history['orders_status_id']]; ?></td>
                  <td><?php echo ($order_history['comments'] == '' ? TEXT_NONE : nl2br(zen_db_output($order_history['comments']))); ?>&nbsp;</td>
                </tr>
                <?php
                if (ORDER_COMMENTS_INVOICE == 1 && $count_comments >= 1) {
                  break;
                }
              }
            } else {
              ?>
              <tr>
                <td colspan="3"><?php echo TEXT_NO_ORDER_HISTORY; ?></td>
              </tr>
              <?php
            }
            ?>
          </tbody>
        </table>
      <?php } // order comments ?>
    </div>

    <!-- body_text_eof //-->
  </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
