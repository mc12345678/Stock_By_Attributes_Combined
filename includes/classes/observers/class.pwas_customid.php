<?php

/**
 * Description of class.pwas_customid: This class is used to support order information related to Stock By Attributes.  This way reduces the modifications of the includes/classes/order.php file to nearly nothing.
 *
 * @author mc12345678
 *
 * Stock by Attributes 2021-04-19 mc12345678
 */

class pwas_customid extends base {

  /*
   * This is the observer for the includes/classes/order.php file to support Stock By Attributes when the order is being processed at the end of the purchase.
   */
  function __construct() {

    $attachNotifier = array();
    $attachNotifier[] = 'NOTIFY_HEADER_END_ACCOUNT_HISTORY_INFO';
    $attachNotifier[] = 'NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION';
    $attachNotifier[] = 'NOTIFY_HEADER_END_CHECKOUT_SUCCESS';
    $this->attach($this, $attachNotifier);
  }

  /*
   * @TODO: Move to separate file for independent implementation and/or use as desired.
   * $zco_notifier->notify('NOTIFY_HEADER_END_ACCOUNT_HISTORY_INFO');
   */
  function updateNotifyHeaderEndAccountHistoryInfo(&$callingClass, $notifier, $paramsArray) {
    global $order, $customid, $products_with_attributes_stock_observe;

    $products_with_attributes_stock_observe->catalogCustomID($order, $customid);
  }

  /*
   * $zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION');
   * NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION
   */
  function updateNotifyHeaderEndCheckoutConfirmation(&$callingClass, $notifier, $paramsArray) {
    global $order, $customid, $products_with_attributes_stock_observe;

    $products_with_attributes_stock_observe->catalogCustomID($order, $customid);
  }

  // NOTIFY_HEADER_END_CHECKOUT_SUCCESS
  function updateNotifyHeaderEndCheckoutSuccess(&$callingClass, $notifier, $paramsArray) {
    global $order;

    if (isset($order) && is_object($order)) {
      $this->updateNotifyHeaderEndCheckoutConfirmation($callingClass, $notifier, $paramsArray);
    }
  }

  /*
   * Generic function that is activated when any notifier identified in the observer is called but is not found in one of the above previous specific update functions is encountered as a notifier.
   */
  function update(&$callingClass, $notifier, $paramsArray) {
    /**
     * ZC 1.5.1: $zco_notifier->notify('NOTIFY_HEADER_END_ACCOUNT_HISTORY');
     **/
    if ($notifier == 'NOTIFY_HEADER_END_ACCOUNT_HISTORY_INFO') {
      $this->updateNotifyHeaderEndAccountHistoryInfo($callingClass, $notifier, $paramsArray);
    }

    /**
     * ZC 1.5.1: $zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION');
     * ZC 1.5.1: $zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_SUCCESS');
     **/
    if ($notifier == 'NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION' || $notifier == 'NOTIFY_HEADER_END_CHECKOUT_SUCCESS') {
      $this->updateNotifyHeaderEndCheckoutConfirmation($callingClass, $notifier, $paramsArray);
    }
  }

}
