<?php
/**
 * Description of class.products_with_attributes_stock: This class is used to support order information related to Stock By Attributes.  This way reduces the modifications of the includes/classes/order.php file to nearly nothing.
 *
 * @property array() $_productI This is the specific product that is being worked on in the order file.
 * @property integer $_i This is the identifier of which product is being worked on in the order file
 * @property array $_stock_info This contains information related to the SBA table associated with the product being worked on in the order file.
 * @property double $_attribute_stock_left This is the a referenced value that relates to the SBA tracked quantity that remain.
 * @property array $_stock_values The results of querying on the database for the stock remaining and other associated information.
 * @author mc12345678
 *
 * Stock by Attributes 2021-04-19 mc12345678
 */

class pwas_imageswap extends base {
  function __construct() {
    
    $attachNotifier = array();
    $attachNotifier[] = 'NOTIFY_MODULES_MAIN_PRODUCT_IMAGE_START';
    $attachNotifier[] = 'NOTIFY_OPTIMIZE_IMAGE';

    $this->attach($this, $attachNotifier);
  }

  // NOTIFY_MODULES_MAIN_PRODUCT_IMAGE_START
  function updateNotifyModulesMainProductImageStart(&$callingClass, $notifier) {
    if (empty($this->MainImage)) {
      $this->MainImage = true;
    }
  }
  
  // NOTIFY_OPTIMIZE_IMAGE
  function updateNotifyOptimizeImage(&$callingClass, $notifier, $template_dir, &$src, &$alt, &$width, &$height, &$parameters) {

    if (empty($this->MainImage)) {
      return;
    }

    unset($this->MainImage);

    if (empty($_GET['products_id'])) {
      return;
    }
    $products_id = (int)$_GET['products_id'];

    if (!$_SESSION['pwas_class2']->zen_product_is_sba($products_id)) {
      return;
    }

    $parameters .= ' id="SBA_ProductImage" ';
  }
} //end class - mc12345678
