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
 * Stock by Attributes 1.5.4  15-11-14 mc12345678
 */
if (!defined('PWA_DISPLAY_CUSTOMID')) {
  define('PWA_DISPLAY_CUSTOMID', 'rightstock'); // 'leftall' (first in text), 'leftstock' (to the left of the stock quantity), 'rightstock' (default - to the right of the stock before other text), 'rightall' (furthest right item), '' (don't display customid regardless of admin setting to display)
}

class products_with_attributes_stock extends base {

  //
  private $_productI;
  
  private $_i;

  private $_stock_info = array();
  
  private $_attribute_stock_left;

  private $_stock_values;
  
  private $_isSBA = false;
  
  private $_orderIsSBA = false;
  
  private $_products_options_names_count;

  private $_products_options_names_current;
  
//  private $_attrib_grid;
  
  private $_noread_done = false;
  
  private $_moveSelectedAttribute;
  
  private $_options_menu_images;
  
  private $_products_options_fields;

  // Value of the customid to add to text, primarily for the SBA Select List (Dropdown) Basic type of dropdown
  private $customid;

  // boolean to identify whether to add the customid or not, primarily for the SBA Select List (Dropdown) Basic type of dropdown
  private $try_customid;
  
  
  /*
   * This is the observer for the includes/classes/order.php file to support Stock By Attributes when the order is being processed at the end of the purchase.
   */
  function __construct() {
    
    $attachNotifier = array();
    $attachNotifier[] = 'ZEN_GET_PRODUCTS_STOCK';
    $attachNotifier[] = 'NOTIFY_ORDER_AFTER_QUERY';
    $attachNotifier[] = 'NOTIFY_ORDER_CART_ADD_PRODUCT_LIST';
    $attachNotifier[] = 'NOTIFY_ORDER_CART_ADD_ATTRIBUTE_LIST';
    $attachNotifier[] = 'NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM';
    $attachNotifier[] = 'NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT';
    $attachNotifier[] = 'NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_BEGIN';
    $attachNotifier[] = 'NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_END';
    $attachNotifier[] = 'NOTIFY_ORDER_DURING_CREATE_ADDED_PRODUCT_LINE_ITEM';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_START_OPTION';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_START_OPTIONS_LOOP';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_SALE_MAKER_DISPLAY_PRICE_PERCENTAGE';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_ORIGINAL_PRICE';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_ATTRIB_SELECTED';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_DEFAULT_SWITCH';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_OPTION_BUILT';
    $attachNotifier[] = 'NOTIFY_HEADER_END_ACCOUNT_HISTORY_INFO';
    $attachNotifier[] = 'NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION';
    $attachNotifier[] = 'NOTIFY_HEADER_END_SHOPPING_CART';
    $attachNotifier[] = 'NOTIFY_HEADER_START_CHECKOUT_SHIPPING';
    $attachNotifier[] = 'NOTIFY_HEADER_START_CHECKOUT_CONFIRMATION';

  
    $this->attach($this, $attachNotifier);

    $this->_products_options_names_current = 0; // Initialize this variable to 0.
  }  
  
  /* ZC 1.5.6: ZEN_GET_PRODUCTS_STOCK
      $GLOBALS['zco_notifier']->notify(
        'ZEN_GET_PRODUCTS_STOCK',
        $products_id,
        $products_quantity,
        $quantity_handled
    );
  
  */
  function updateZenGetProductsStock(&$callingClass, $notifier, $products_id, &$products_quantity, &$quantity_handled) {

    // Check if SBA has been initiated, if not then errors will be thrown by some of
    //   the remaining code.  Exit gracefully from this code to not have it process anything.
    if (empty($_SESSION['pwas_class2'])) return;

    // There is no appropriate product to handle.
    if (isset($products_id) && (int)$products_id <= 0) return false;

    // Check if SBA tracked, if not, then no need to process further.
    if (!$_SESSION['pwas_class2']->zen_product_is_sba($products_id)) return false;

    $backtrace = debug_backtrace();
    $current_level = -1;
    $args = array();
    $attributes = array();

    // Note that if the $products_id is formatted using the hashed attributes and there are no text attributes then the
    //  full attribute designation could be determined "applicable" to this product by comparing all of the possible hashes
    //  against the provided selection to determine what selections had been made without pulling the individual attribute selection.
    //  though it does offer the opportunity to identify if the products_id is properly generated through comparison and would
    //  identify if the cart session or the page html had been modified.
    
    
    foreach ($backtrace as $level => $data) {
      if ($data['function'] === 'zen_get_products_stock') {
        $current_level = $level;
        $args['zen_get_products_stock'] = $data['args'];
        $attributes = isset($args['zen_get_products_stock'][1]) ? $args['zen_get_products_stock'][1] : null;
        $dupTest = isset($args['zen_get_products_stock'][2]) ? $args['zen_get_products_stock'][2] : null;
        if (!empty($this->from)) {
          $this->setCheckStockParams($this->from);
        
          $attributes = !isset($attributes) ? $this->attributes : $attributes;
        }


        continue;
      } // EOF IF zen_get_products_stock

      if ($data['function'] === 'zen_check_stock' && $current_level != -1 && $level > $current_level) {
        $current_level = $level;
        $args['zen_check_stock'] = $data['args'];
        $attributes = isset($args['zen_check_stock'][2]) ? $args['zen_check_stock'][2] : null;
        $from = isset($this->from) ? $this->from : (isset($args['zen_check_stock'][3]) ? $args['zen_check_stock'][3] : 'products');
        if (!empty($this->from) /*&& $data['function'] === 'zen_check_stock' && $current_level != -1*/ /*&& $level == $current_level+1*/) {
          $this->setCheckStockParams($this->from);
        
          $attributes = $this->attributes;
        }
      } // EOF IF zen_check_stock && $current_level != -1 && $level > $current_level
    } // EOF foreach backtrace
    
    // Somehow ended up in this function but applicable sub-routines were not detected
    if ($current_level === -1) {
      return false;
    }
    
    // Product were passed but $attributes were not.  
    if (empty($attributes) || !is_array($attributes)) {
      //For products without associated attributes, get product level stock quantity
//  DON'T HANDLE
      return false;
    }
    
    // below function/call was written in ZC 1.5.1, 1.5.3, and 1.5.4 to support broadly addressing attributes and for
    //   some reason in ZC 1.5.5, the call was omitted/skipped.
    $products_quantity =
        $_SESSION['pwas_class2']->zen_get_sba_attribute_info($products_id, $attributes, 'products', (isset($dupTest) && $dupTest == 'true' ? 'dupTest' : 'stock'));
    $quantity_handled = true;
    return false;


//    $products_quantity = null;
//    $quantity_handled = true;
  }
  
  /**
   * NOTIFY_ZEN_HAS_PRODUCT_ATTRIBUTES_VALUES
      ZC: 1.56 inserted. Used to identify if attributes affect product's price.
        $value_to_return = '';
        $GLOBALS['zco_notifier']->notify('NOTIFY_ZEN_HAS_PRODUCT_ATTRIBUTES_VALUES', $products_id, $value_to_return);
        if ($value_to_return !== '') {
          return $value_to_return;
        }
   **/
  function updateNotifyZenHasProductAttributesValues(&$callingClass, $notifier, $products_id, &$value_to_return) {
    
    // Saves a database lookup that has or should have already been done.
    if (isset($this->_isSBA) && $this->_isSBA || !isset($this->_isSBA) && $_SESSION['pwas_class2']->zen_product_is_sba($_GET['products_id'])) {
//      $value_to_return = true;
    }
  }
  /*
   * NOTIFY_ATTRIBUTES_MODULE_START_OPTION
   * Added by SBA for ZC 1.5.0 through 1.5.4.
   * ZC 1.5.5, ZC 1.5.6,
   * ZC 1.5.7: $zco_notifier->notify('NOTIFY_ATTRIBUTES_MODULE_START_OPTION', $products_options_names->fields);
   */
   function updateNotifyAttributesModuleStartOption(&$callingClass, $notifier, $products_options_names_fields) {
     global $db, $sql, /*$options_menu_images, $moveSelectedAttribute, */
        $products_options_array, /*$options_attributes_image,*/
        /*$products_options_names, *//*$products_options_names_count,*/
       /*$stock,*/ $is_SBA_product, $order_by, $products_options; //, $pwas_class;
     
     $this->_options_menu_images = array();
     $this->_moveSelectedAttribute = false;
     if (!isset($products_options_array)) {
       $products_options_array = array();
     }
//     $options_attributes_image = array();
     // Could do the calculation here the first time set a variable above as part of the class and then reuse that... instead of the modification to the attributes file...
     if (!zen_not_null($this->_products_options_names_count)) {
       $this->_products_options_names_count = $GLOBALS['products_options_names']->RecordCount();
     }
//     $products_options_names_count = $products_options_names->RecordCount();
     $this->_isSBA = false;
     
     if ($_SESSION['pwas_class2']->zen_product_is_sba($_GET['products_id'])) {
       $this->_isSBA = true;
     }
     
//     $stock->_isSBA = $this->_isSBA;
     $is_SBA_product = $this->_isSBA;
     
     if (!$this->_isSBA) {
      return;
     }

      $products_options_type = $products_options_names_fields['products_options_type'];
       // Want to do a SQL statement to see the quantity of non-READONLY attributes.  If there is only one non-READONLY attribute, then
       //   do additional SQL to add the "missing" attributes that would get displayed.  But, do not have the "main" sql modified otherwise
       //   the display will get all wonky (multiple listings where not desired).  Will need to modify the SQL result for each result applicable to the
       //   one option_id assuming it is not READONLY.
       // Understand that already cycling through the product options, therefore if there are multiple options, the current option is not readonly
       //   and there is only one non-readonly attribute, then that is when the "new" sql needs to be activated to populate the current option...
       $process_this = false;
       if (!$this->_noread_done && $products_options_type != PRODUCTS_OPTIONS_TYPE_READONLY && $this->_products_options_names_count > 1) {
         $sql_noread = "SELECT count(distinct products_options_id) AS total
           FROM " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib where patrib.products_id = :products_id:
           AND patrib.options_id = popt.products_options_id
           AND popt.products_options_type != " . PRODUCTS_OPTIONS_TYPE_READONLY . "
           AND popt.language_id = :languages_id:";
         $sql_noread = $db->bindVars($sql_noread, ':products_id:', $_GET['products_id'], 'integer');
         $sql_noread = $db->bindVars($sql_noread, ':languages_id:', $_SESSION['languages_id'], 'integer');
         $noread = $db->Execute($sql_noread);
         $process_this = true;
         $this->_noread_done = true;
       }
         
       $sql = "select distinct pov.products_options_values_id,
                        pov.products_options_values_name,
                        pa.*, p.products_quantity,
                      " . (($this->_products_options_names_count <= 1 || ($process_this == true && isset($noread) && $noread->fields['total'] == 1))? " pas.stock_id as pasid, pas.quantity as pasqty, pas.sort,  pas.customid, pas.title, pas.product_attribute_combo, pas.stock_attributes, " : "") . " pas.products_id

                from      " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                left join " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov on (pa.options_values_id = pov.products_options_values_id)
                left join " . TABLE_PRODUCTS . " p on (pa.products_id = p.products_id)
                
                left join " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " pas on
                (p.products_id = pas.products_id and FIND_IN_SET(pa.products_attributes_id, pas.stock_attributes) > 0 )
            where pa.products_id = :products_id:
            and       pa.options_id = :options_id:
            and       pov.language_id = :languages_id: " .
            (
              (
                (
                  $this->_products_options_names_count <= 1
                  || ($process_this == true && isset($noread) && $noread->fields['total'] == 1)
                )
                && defined('SBA_SHOW_OUT_OF_STOCK_ATTR_ON_PRODUCT_INFO') && SBA_SHOW_OUT_OF_STOCK_ATTR_ON_PRODUCT_INFO == '0'
                && (!defined('PRODINFO_ATTRIBUTE_DYNAMIC_STATUS') || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS != '1' && PRODINFO_ATTRIBUTE_DYNAMIC_STATUS != '3'))
                && (!defined('PRODUCTS_OPTIONS_TYPE_GRID') || $products_options_type != PRODUCTS_OPTIONS_TYPE_GRID)
                && (!defined('PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID') || $products_options_type != PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID)
              ) 
               ? " AND (pas.quantity > '0' OR (pas.quantity IS NULL AND pa.attributes_display_only = '1')) "
               : ""
            ) .
            /* && $products_options_name->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_READONLY  */
            $order_by;
              
       $sql = $db->bindVars($sql, ':products_id:', $_GET['products_id'], 'integer');
       $sql = $db->bindVars($sql, ':options_id:', $products_options_names_fields['products_options_id'], 'integer');
       $sql = $db->bindVars($sql, ':languages_id:', $_SESSION['languages_id'], 'integer');

       $products_options = $db->Execute($sql);
   }

  /*
   * 'NOTIFY_ATTRIBUTES_MODULE_START_OPTIONS_LOOP'
   */
  function updateNotifyAttributesModuleStartOptionsLoop(&$callingClass, $notifier, $i, &$products_options_fields){
    global $db, /*$options_menu_images, */$products_options_array, $products_options_names,
           $PWA_STOCK_QTY;

    $this->_products_options_names_current++;
    
    if ($this->_isSBA && (in_array($products_options_names->fields['products_options_type'], array(PRODUCTS_OPTIONS_TYPE_SELECT_SBA, PRODUCTS_OPTIONS_TYPE_RADIO, PRODUCTS_OPTIONS_TYPE_CHECKBOX, PRODUCTS_OPTIONS_TYPE_FILE, PRODUCTS_OPTIONS_TYPE_TEXT, PRODUCTS_OPTIONS_TYPE_SELECT)) || ((PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '0' && $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_SELECT_SBA) || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2' && $products_options_names->RecordCount() > 1) || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '3' && $products_options_names->RecordCount() == 1)))) {  // Perhaps only certain features need to be bypassed, but for now all mc12345678
      // START "Stock by Attributes"  SBA
      //used to find if an attribute is display-only
      $sqlDO = "select pa.attributes_display_only
                    from " . TABLE_PRODUCTS_OPTIONS . " po
                    left join " . TABLE_PRODUCTS_ATTRIBUTES . " pa on (pa.options_id = po.products_options_id)
                    where pa.products_id=:products_id:
                     and pa.products_attributes_id = :products_attributes_id: ";
      $sqlDO = $db->bindVars($sqlDO, ':products_id:', $_GET['products_id'], 'integer');
      $sqlDO = $db->bindVars($sqlDO, ':products_attributes_id:', $products_options_fields['products_attributes_id'], 'integer');
      $products_options_DISPLAYONLY = $db->Execute($sqlDO);

      //echo 'ID: ' . $products_options_fields["products_attributes_id"] . ' Stock ID: ' . $products_options_fields['pasid'] . ' QTY: ' . $products_options_fields['pasqty'] . ' Custom ID: ' . $products_options_fields['customid'] . '<br />';//debug line
      //add out of stock text based on qty
      if ((!isset($products_options_fields['pasqty']) || $products_options_fields['pasqty'] < 1) && STOCK_CHECK == 'true' && isset($products_options_fields['pasid']) && $products_options_fields['pasid'] > 0) {
        //test, only applicable to products with-out the display-only attribute set
        if (empty($products_options_DISPLAYONLY->fields['attributes_display_only'])) {
          $products_options_fields['products_options_values_name'] = $products_options_fields['products_options_values_name'] . PWA_OUT_OF_STOCK;
          // $i in includes/modules/YOUR_TEMPLATE/attributes.php is post incremented ($i++) meaning it is sent here with the 
          //   current value, but upon return will be incremented by 1.  Therefore within this function it should be considered as 
          //   the value that attributes.php was seeing just before the notifier.
          $products_options_array[$i] = array_merge($products_options_array[$i], array('id' =>
              $products_options_fields['products_options_values_id'],
              'text' => $products_options_fields['products_options_values_name'])); // 2017-07-13 mc12345678 added array_merge to preserve other modifications that may have been made for this array instead of replacing them in their entirety and possibly losing the other data.
        }
      }

      $show_custom_id_flag = isset($products_options_fields['customid'])
          && zen_not_null($products_options_fields['customid'])
          && (
              !defined('ATTRIBUTES_SBA_DISPLAY_CUSTOMID')
              || (STOCK_SBA_DISPLAY_CUSTOMID == 'true' && ATTRIBUTES_SBA_DISPLAY_CUSTOMID == '1')
              || ATTRIBUTES_SBA_DISPLAY_CUSTOMID == '2'
              || (ATTRIBUTES_SBA_DISPLAY_CUSTOMID == '3' && $products_options_names->RecordCount() > 1)
             );
      //Add qty to atributes based on STOCK_SHOW_ATTRIB_LEVEL_STOCK setting
      //Only add to Radio, Checkbox, and selection lists
      //PRODUCTS_OPTIONS_TYPE_RADIO PRODUCTS_OPTIONS_TYPE_CHECKBOX
      //Exclude the following:
      //PRODUCTS_OPTIONS_TYPE_TEXT PRODUCTS_OPTIONS_TYPE_FILE PRODUCTS_OPTIONS_TYPE_READONLY
      //PRODUCTS_OPTIONS_TYPE_SELECT_SBA
      $PWA_STOCK_QTY = ''; //initialize variable
      $this->customid = '';
      $this->try_customid = false;
      if ($products_options_names->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_TEXT) {
        if ($products_options_names->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_FILE) {
          if ($products_options_names->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_READONLY) {
            /*if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_SELECT_SBA)*/ {

              if (STOCK_SHOW_ATTRIB_LEVEL_STOCK == 'true' && isset($products_options_fields['pasqty']) && $products_options_fields['pasqty'] > 0) {
                //test, only applicable to products with-out the display-only attribute set
                if (empty($products_options_DISPLAYONLY->fields['attributes_display_only'])) {
                  $PWA_STOCK_QTY = PWA_STOCK_QTY . $products_options_fields['pasqty'] . ' ';
                  //show custom ID if flag set to true
                  if ($show_custom_id_flag && zen_not_null($products_options_fields['customid'])) {
                    $this->customid = PWA_CUSTOMID_LEFT . $products_options_fields['customid'] . PWA_CUSTOMID_RIGHT;
                    $this->try_customid = true;
                  }
                }
              } elseif (STOCK_SHOW_ATTRIB_LEVEL_STOCK == 'true' && (!isset($products_options_fields['pasqty']) || ($products_options_fields['pasqty'] < 1)) && empty($products_options_fields['pasid'])) {
                //test, only applicable to products with-out the display-only attribute set
                if (empty($products_options_DISPLAYONLY->fields['attributes_display_only'])) {
                  //use the qty from the product, unless it is 0, then set to out of stock.
                  if (!isset($this->_products_options_names_count) || ($this->_products_options_names_count <= 1)) {
                    if ($products_options_fields['products_quantity'] > 0) {
                      $PWA_STOCK_QTY = PWA_STOCK_QTY . $products_options_fields['products_quantity'] . ' ';
                    } else {
                      $products_options_fields['products_options_values_name'] = $products_options_fields['products_options_values_name'] . PWA_OUT_OF_STOCK;
                      $products_options_array[$i] = array_merge($products_options_array[$i], array('id' =>
/*                      $products_options_array[count($products_options_array)-1] = array('id' =>*/
                          $products_options_fields['products_options_values_id'],
                          'text' => $products_options_fields['products_options_values_name'])
                          );

                    }
                  }

                  //show custom ID if flag set to true
                  if ($show_custom_id_flag && zen_not_null($products_options_fields['customid'])) {
                    $this->customid = PWA_CUSTOMID_LEFT . $products_options_fields['customid'] . PWA_CUSTOMID_RIGHT;
                    $this->try_customid = true;
                  }
                }
              } elseif ($show_custom_id_flag) {
                //show custom ID if flag set to true
                //test, only applicable to products with-out the display-only attribute set
                if ($products_options_DISPLAYONLY->fields['attributes_display_only'] < 1) {
                  $this->customid = PWA_CUSTOMID_LEFT . $products_options_fields['customid'] . PWA_CUSTOMID_RIGHT;
                  $this->try_customid = true;
                }
              }
            }
          }
        }
      }

      if ($this->try_customid) {
        if (PWA_DISPLAY_CUSTOMID == 'rightstock') {
          $PWA_STOCK_QTY .= $this->customid;
        } else if (PWA_DISPLAY_CUSTOMID == 'leftstock') {
          // Add customid to beginning of option value's above stock text.
          $PWA_STOCK_QTY = $this->customid . $PWA_STOCK_QTY;
        }
      }

      // Add the stock quantity text to the end of the existing products_options text.
      $products_options_array[$i]['text'] .= $PWA_STOCK_QTY;
      if ($this->try_customid && PWA_DISPLAY_CUSTOMID == 'leftall') {
        // Add customid to beginning of option value's text.
        $products_options_array[$i]['text'] = $this->customid . $products_options_array[$i]['text'];
      }

      //create image array for use in select list to rotate visable image on select.  Applicable only to
      // product that is stocked by attribute. To apply to other product then must move this down outside
      // of the end of the End if _isSBA section
      if (!empty($products_options_fields['attributes_image'])) {
        $this->_options_menu_images[] = array('id' => $products_options_fields['products_options_values_id'],
            'src' => DIR_WS_IMAGES . $products_options_fields['attributes_image']);
      } else {
        $this->_options_menu_images[] = array('id' => $products_options_fields['products_options_values_id']);
      }
      // Assign the $options_menu_images to either blank (if there is no image for the product) or to the assigned image if there is one.
      //if ($this->_products_options_names_current == 1)
      {
        $picture = $db->Execute('SELECT p.products_image FROM ' . TABLE_PRODUCTS . ' p WHERE products_id = ' . (int)$_GET['products_id']);

        $this->_options_menu_images['product_image'] = '';
        if ($picture->RecordCount() > 0) {
          $this->_options_menu_images['product_image'] .= DIR_WS_IMAGES . $picture->fields['products_image'];
        }
      }
      // END "Stock by Attributes" SBA
    } // End if _isSBA

    $this->_products_options_fields = $products_options_fields;

    if ($this->_products_options_names_current == 1) {
      // global $currencies;

      //$show_attribute_stock_left = true;
    }
  }

  /*
   * NOTIFY_ATTRIBUTES_MODULE_SALE_MAKER_DISPLAY_PRICE_PERCENTAGE
   */
  function updateNotifyAttributesModuleSaleMakerDisplayPricePercentage(&$callingClass, $notifier, $paramsArray){
    global $products_options_names, $products_options_display_price, $products_options, $currencies, $new_attributes_price, $product_info;
    
    if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_RADIO || $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_CHECKBOX) {
      //use this if a PRODUCTS_OPTIONS_TYPE_RADIO or PRODUCTS_OPTIONS_TYPE_CHECKBOX
      //class="productSpecialPrice" can be used in a CSS file to control the text properties, not compatable with selection lists
      $products_options_display_price = ATTRIBUTES_PRICE_DELIMITER_PREFIX . '<span class="productSpecialPrice">' . $products_options->fields['price_prefix'] . $currencies->display_price($new_attributes_price, zen_get_tax_rate($product_info->fields['products_tax_class_id'])) . '</span>' . ATTRIBUTES_PRICE_DELIMITER_SUFFIX;
    }

  }

   /*
    * NOTIFY_ATTRIBUTES_MODULE_ORIGINAL_PRICE
    */
  function updateNotifyAttributesModuleOriginalPrice(&$callingClass, $notifier, $paramsArray){
    global /*$db, */$products_options, $products_options_names, $currencies, $new_attributes_price, $product_info, $products_options_display_price, $PWA_STOCK_QTY;
    
    // If not even an SBA selection, then don't do anything with it.
    if (!$this->_isSBA) {
      return;
    }
    
    if (
          (
            in_array($products_options_names->fields['products_options_type'], array(PRODUCTS_OPTIONS_TYPE_SELECT_SBA, PRODUCTS_OPTIONS_TYPE_RADIO, PRODUCTS_OPTIONS_TYPE_CHECKBOX, PRODUCTS_OPTIONS_TYPE_FILE, PRODUCTS_OPTIONS_TYPE_TEXT, PRODUCTS_OPTIONS_TYPE_SELECT)) 
            || (
                (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '0' 
                  && $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_SELECT_SBA
                ) 
                || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' 
                || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2' && $products_options_names->RecordCount() > 1) 
                || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '3' && $products_options_names->RecordCount() == 1)
               )
            )
        ) {  // Perhaps only certain features need to be bypassed, but for now all mc12345678
      // START "Stock by Attributes" SBA added original price for display, and some formatting
      $originalpricedisplaytext = null;

      if (!empty($_SESSION['pwas_class2']->zgapf)) {
        // Use the latest function for determining the attribute's final price
        //   This requires/uses 4 parameters to internally determine the price of the attribute
        global $products_price_is_priced_by_attributes;
        $attributes_price_final = zen_get_attributes_price_final($products_options->fields['products_attributes_id'], 1, '', 'false', $products_price_is_priced_by_attributes);
      } else {
        // This is the old method of performing attribute price determination which
        //   has been found to be prone to discrepancies when sales, specials 
        //   and/or priced-by-attributes are involved
        $attributes_price_final = zen_get_attributes_price_final($products_options->fields['products_attributes_id'], 1, '', 'false');
        $attributes_price_final = zen_get_discount_calc((int)$_GET['products_id'], true, $attributes_price_final);
      }
//      if (STOCK_SHOW_ORIGINAL_PRICE_STRUCK == 'true' && !($attributes_price_final == $new_attributes_price || ($attributes_price_final == -$new_attributes_price && ((int)($products_options->fields['price_prefix'] . "1") * $products_options->fields['options_values_price']) < 0)) ) {
      if (STOCK_SHOW_ORIGINAL_PRICE_STRUCK == 'true' && !($products_options->fields['attributes_display_only'] && $products_options->fields['attributes_default'] && !$products_options->fields['products_options_sort_order']) && ($new_attributes_price != $products_options->fields['options_values_price']) && (($attributes_price_final == $new_attributes_price) || (($attributes_price_final == -$new_attributes_price) && ((int)($products_options->fields['price_prefix'] . "1") * $products_options->fields['options_values_price']) < 0)) ) {
        //Original price struck through
        if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_RADIO || $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_CHECKBOX) {
          //use this if a PRODUCTS_OPTIONS_TYPE_RADIO or PRODUCTS_OPTIONS_TYPE_CHECKBOX
          //class="normalprice" can be used in a CSS file to control the text properties, not compatable with selection lists
          $originalpricedisplaytext = ATTRIBUTES_PRICE_DELIMITER_PREFIX . '<span class="normalprice">' . $products_options->fields['price_prefix'] . $currencies->display_price($products_options->fields['options_values_price'], zen_get_tax_rate($product_info->fields['products_tax_class_id'])) . '</span>' . ATTRIBUTES_PRICE_DELIMITER_SUFFIX;
        } else {
          //need to remove the <span> tag for selection lists and text boxes
          $originalpricedisplaytext = ATTRIBUTES_PRICE_DELIMITER_PREFIX . $products_options->fields['price_prefix'] . $currencies->display_price(abs($products_options->fields['options_values_price']), zen_get_tax_rate($product_info->fields['products_tax_class_id'])) . ATTRIBUTES_PRICE_DELIMITER_SUFFIX;
        }
      }

      if ($this->try_customid && PWA_DISPLAY_CUSTOMID == 'rightall') {
        $products_options_display_price .= $originalpricedisplaytext . $this->customid;
      }
      // END "Stock by Attributes" SBA
    }
  }
  
   /*
    * NOTIFY_ATTRIBUTES_MODULE_ATTRIB_SELECTED
    */
  function updateNotifyAttributesModuleAttribSelected(&$callingClass, $notifier, $paramsArray){
    global /*$products_options_names, */$products_options, $selected_attribute, /*$moveSelectedAttribute,*/ $disablebackorder;
    
//       if ($this->_isSBA && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2' && $products_options_names->RecordCount() > 1) || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '3' && $products_options_names->RecordCount() == 1))) {  // Perhaps only certain features need to be bypassed, but for now all mc12345678
    $disablebackorder = null;
    if (!$this->_isSBA || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '0' && $this->products_options_names_fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_SELECT_SBA) || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2' && $this->_products_options_names_count == 1 && $this->products_options_names_fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_SELECT_SBA) || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '3' && $this->_products_options_names_count > 1)) {
      return;
    }

    //move default selected attribute if attribute is out of stock and check out is not allowed
    if ($this->_moveSelectedAttribute == true && (STOCK_ALLOW_CHECKOUT == 'false' && $products_options->fields['pasqty'] > 0)) {
      $selected_attribute = true;
      $this->_moveSelectedAttribute = false;
    }

    //disable radio and disable default selected
    if ((STOCK_ALLOW_CHECKOUT == 'false' && (empty($products_options->fields['pasqty']) || $products_options->fields['pasqty'] <= 0) && !empty($products_options->fields['pasid']) )
    || ( STOCK_ALLOW_CHECKOUT == 'false' && (empty($products_options->fields['products_quantity']) || $products_options->fields['products_quantity'] <= 0) && empty($products_options->fields['pasid']) )
    ) {//|| $products_options_READONLY->fields['attributes_display_only'] == 1
      if ($selected_attribute == true) {
        $selected_attribute = false;
        $this->_moveSelectedAttribute = true;
      }
      $disablebackorder = ' disabled="disabled" ';
    }
    // END "Stock by Attributes" SBA
     
  }

  /*
   * 'NOTIFY_ATTRIBUTES_MODULE_DEFAULT_SWITCH';
   */
  function updateNotifyAttributesModuleDefaultSwitch(&$callingClass, $notifier, $products_options_names_fields, &$options_name, &$options_menu, &$options_comment, &$options_comment_position, &$options_html_id){

          switch (true) {
      case ($products_options_names_fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_SELECT_SBA): // SBA Select List (Dropdown) Basic
        global $selected_attribute, $show_attributes_qty_prices_icon, $products_options_array, $disablebackorder/*, $options_menu_images*/, $products_options;
        
        // normal dropdown "SELECT LIST" menu display
        $prod_id = $_GET['products_id'];
        if (isset($_SESSION['cart']->contents[$prod_id]['attributes'][$products_options_names_fields['products_options_id']])) {
          $selected_attribute = $_SESSION['cart']->contents[$prod_id]['attributes'][$products_options_names_fields['products_options_id']];
        } else {
          // use customer-selected values
          if (!empty($_POST['id'])) {
            reset($_POST['id']);
            foreach($_POST['id'] as $key => $value) {
              if ($key == $products_options_names_fields['products_options_id']) {
                $selected_attribute = $value;
                break;
              }
            }
          } else {
            // use default selected set above
          }
        }
        
          if ($show_attributes_qty_prices_icon == 'true') {
            $options_name[] = ATTRIBUTES_QTY_PRICE_SYMBOL.$products_options_names_fields['products_options_name'];
          } else {
            $options_name[] = '<label class="attribsSelect" for="' . 'attrib-' . $products_options_names_fields["products_options_id"] . '">' . $products_options_names_fields["products_options_name"] . '</label>';
          }
        
        // START "Stock by Attributes" SBA
        $disablebackorder = array();

        //disable default selected if out of stock
        $products_opt = $products_options;
        if (method_exists($products_opt, 'rewind')) {
          $products_opt->rewind();
        } else {
          $products_opt->Move(0);
          $products_opt->MoveNext();
        }
//        $products_opt->Move(0);
              // $products_opt->MoveNext(); // Start off at the first record, need to address based on ZC version.
        $prevent_checkout = defined('STOCK_ALLOW_CHECKOUT') && STOCK_ALLOW_CHECKOUT == 'false' && array_key_exists('pasqty', $products_opt->fields);

        // Consideration needs to be provided to the above $selected_attribute with respect to
        // the application of disabled.
        // Problem is this: if the $selected_attribute (typically would be the default attribute) is to
        // be marked as disabled, then if no other selection is made when a dropdown is used then, the
        // attribute identification information is not passed along and then the attribute processing
              // has difficulty handling this situation.  One fix would be to address that; however,
              // the other is not to place the system into this condition.
              
              // So, aspects of consideration:
              //  A single attribute should be presented as a radio button (already incorporated)
              
              //  if there is more than one attribute then things to consider: if the first attribute is default
              //   and disabled, then try to bump to the next attribute.
              //  if the last attribute is default and disabled, then try to bump to the first/next
              //  if all are disabled, then what is to be addressed? Should it be programatic or
        $move2next = false;
        while (!$products_opt->EOF) {
          // Early escape if there is no reason to specifically disable the option.
          if (!$prevent_checkout
            || $products_opt->fields['pasqty'] > 0
            || $products_opt->fields['attributes_display_only'] && $products_opt->fields['attributes_default']) {

            $disablebackorder[] = '';
            // If previous selected default was invalid, then because this option is "selectable", make it the new default.
            if ($move2next) {
              $selected_attribute = $products_opt->fields['options_values_id'];
              $move2next = false;
            }
            $products_opt->MoveNext();
            continue;
          }

          // Identify if the given option name/option value combination is defined as a specific variant for this specific selection.
          //   This only really works for single optin name product or if each option name/option value is split out. For combined attributes, this
          //   does not properly identify the presence/existence of the variant.  That aspect probably needs to be controlled from the front end of
          //   the store via javascript/jQuery and/or upon selection of the combination without the extra screen modification.
          $isDefined = !empty($_SESSION['pwas_class2']->zen_get_sba_attribute_info($prod_id, array($products_opt->fields['options_id'] => $products_opt->fields['options_values_id']) /*$products_options_array*/, 'product', 'ids'));
          // If the item is display only then disable it from selection. display_only with default is handled above.
          if ($products_opt->fields['attributes_display_only'] && !$products_opt->fields['attributes_default']) {
            $disablebackorder[] = ' disabled="disabled" ';
          } elseif ($isDefined && $products_opt->fields['attributes_default']) {
            // If the option name/option value combination has its own stock_id and it is/was a default, then because there is insufficient stock, disable it.
            //   Also, because it was set as a default, then this selects/suggests another default be selected so that at least one is chosen.
            $disablebackorder[] = ' disabled="disabled" '; //' disabled="disabled" ';
            $move2next = true;
          } elseif ($isDefined) {
            // By this point, it is not display only, it is not a default, it is identified as a known variant, it is out of stock and not
            //   permitted to be added to the cart, so disable it.
            $disablebackorder[] = ' disabled="disabled" ';
          } elseif (zen_get_products_stock($products_opt->fields['products_id']) <= 0) {
            // Basically the expectation is that this options_id/options value doesn't have a variant defined and the total quantity of product is <=0
            //   then disable the option as a general rule
            $disablebackorder[] = ' disabled="disabled" ';
          } else {
            // There doesn't appear to be any remaining reason to disable the variant, so allow it to be added and go ahead and suggest this variant as the new default.
            $disablebackorder[] = '';
            if ($move2next) {
              $selected_attribute = $products_opt->fields['options_values_id'];
              $move2next = false;
            }
          }
          $products_opt->MoveNext();
        }
        unset($products_opt);
        unset($prevent_checkout);
        // If have exited the above loop and still need to resolve to the next option value, attempt to find the next available option value.
        if($move2next) {
          $sba_counter = 0;
          foreach ($products_options_array as $prod_key => $prod_val) {
            // The $disablebackorder array is 0 based and is expected to be one for one to $products_options_array at least in sequence, though not
            //   necessarily in number.  If the current options are not disabled, then allow it to become the new default.
            if (empty($disablebackorder[$sba_counter])) {
              $selected_attribute = $prod_val['id'];
              $move2next = false;
              break; // Don't try to process any further items in array.
            }
            $sba_counter++;
          }

          // try to find another solution that is currently available, if none then set to the first one as an "possibility"?
          if ($move2next) {
            $selected_attribute = $products_options_array[0]['id'];
            $move2next = false;
          }
        }
        
          //var_dump($products_options_array); //Debug Line
          $options_html_id[] = 'drp-attrib-' . $products_options_names_fields['products_options_id'];
          // added new image rotate ability ($options_menu_images);
          $options_menu[] = $_SESSION['pwas_class2']->zen_draw_pull_down_menu_SBAmod('id[' . $products_options_names_fields['products_options_id'] . ']', $products_options_array,  $selected_attribute, 'id="' . 'attrib-' . $products_options_names_fields['products_options_id'] . '"' . ' class="sbaselectlist"', false, $disablebackorder, $this->_options_menu_images) .  "\n";
        // END "Stock by Attributes" SBA
        
        $options_comment[] = $products_options_names_fields['products_options_comment'];
        $options_comment_position[] = ($products_options_names_fields['products_options_comment_position'] == '1' ? '1' : '0');
        break;
      default:
        break;
    }
  }

// $zco_notifier->notify('NOTIFY_ATTRIBUTES_MODULE_OPTION_BUILT', $products_options_names->fields, $options_name, $options_menu, $options_comment, $options_comment_position, $options_html_id, $options_attributes_image);
  /*
   * 'NOTIFY_ATTRIBUTES_MODULE_OPTION_BUILT'
   */
  function updateNotifyAttributesModuleOptionBuilt(&$callingClass, $notifier, $products_options_names_fields,
                                                   &$options_name, &$options_menu, &$options_comment,
                                                   &$options_comment_position, &$options_html_id,
                                                   &$options_attributes_image) {
 
    // if at the last option name, then no further processing above and want to reset the
    // counter so that on the next use on this session it is zero.
    if ($this->_products_options_names_current == $this->_products_options_names_count) {
      $this->_products_options_names_current = 0;
    }
    // reset or clear the attribute images so that they do not display adjacent/near the product
    if (defined('SBA_SHOW_IMAGE_ON_PRODUCT_INFO') && (SBA_SHOW_IMAGE_ON_PRODUCT_INFO === '2' && $this->_isSBA 
                                                      || SBA_SHOW_IMAGE_ON_PRODUCT_INFO === '3' && !$this->_isSBA
                                                      || SBA_SHOW_IMAGE_ON_PRODUCT_INFO === '4')) {
      $options_attributes_image = array();
    }
    
    // Problem with the below code moving forwards is that if the selection remains blank, then 
    //   the verification in the add-to-cart section does not seem to flag this as an issue
    //   allowing the product to be added to the cart without an attribute selected.
    // This is regardless of the radio button being preselected or not.
    // If the radio button remains selected as a default, then when attempting to add the product
    //   to the cart, the user is notified that an incorrect selection was made and to correct the selection.
    /*if ($products_options_names_fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_SELECT_SBA) {
      if ($GLOBALS['products_options']->RecordCount() == 1) {
        $disablebackorder = '';
        
        if ((STOCK_ALLOW_CHECKOUT == 'false' && $GLOBALS['products_options']->fields['pasqty'] <= 0 && !empty($GLOBALS['products_options']->fields['pasid']) )
        || ( STOCK_ALLOW_CHECKOUT == 'false' && $GLOBALS['products_options']->fields['products_quantity'] <= 0 && empty($GLOBALS['products_options']->fields['pasid']) )
        ) {//|| $products_options_READONLY->fields['attributes_display_only'] == 1
          $disablebackorder = ' disabled="disabled" ';
        }
        $old_options_menu = array_pop($options_menu);
        $options_menu[] = zen_draw_radio_field('id[' . $products_options_names_fields['products_options_id'] . ']', $GLOBALS['products_options_value_id'], empty($disablebackorder), $disablebackorder . 'id="' . 'attrib-' . $products_options_names_fields['products_options_id'] . '-' . $GLOBALS['products_options_value_id'] . '"') . '<label class="attribsRadioButton" for="' . 'attrib-' . $products_options_names_fields['products_options_id'] . '-' . $GLOBALS['products_options_value_id'] . '">' . $GLOBALS['products_options_details'] . '</label>' . "\n";
      }
    }*/
  }
   
  /*
   * Function that populates order class product data if order has been finalized.
   */
  //  $attachNotifier[] = 'NOTIFY_ORDER_AFTER_QUERY';
  //  $this->notify('NOTIFY_ORDER_AFTER_QUERY', array(), $order_id);
  function updateNotifyOrderAfterQuery(&$orderClass, $notifier, $paramsArray, &$order_id) {
    global $db;
    
//    $order_id = $paramsArray['orders_id'];
    
    //$orders_products_sba = $db->Execute("select orders_products_attributes_stock_id, orders_products_attributes_id, orders_products_id, stock_id, stock_attribute, customid, products_prid from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK . " where orders_id = " . (int)$order_id );
    
    $orders_products = $db->Execute("select orders_products_id, products_id
                                     from " . TABLE_ORDERS_PRODUCTS . "
                                     where orders_id = " . (int)$order_id . "
                                     order by orders_products_id");
    /*
    select orders_products_id, products_id
                                     from orders_products
                                     where orders_id = 28
                                     order by orders_products_id;
    Result 568, 338
           569, 41
    */
    // This gets a list of all of the products that were ordered. The first should match with an index of 0, second, etc.. 
    $index = 0;
    //$subindex = 0;
    
    while (!$orders_products->EOF) {                                     
    // Loop through each product in the order
      $product = $orderClass->products[$index];
      $customid_txt = '';
      $custom_type = 'none';
    
      // If the product has attributes, then need to see what was logged into the orders_products_attributes_stock table.  
      //    If nothing then is a product that has attributes, but was not tracked by SBA. 
      //    If something, then retrieve the desired data (customid)
      if (!empty($product) && is_array($product) && array_key_exists('attributes', $product)&& !empty($product['attributes']) && is_array($product['attributes']) ) {
        $orders_products_sba_customid = $db->Execute("select 
                           opas.orders_products_attributes_stock_id, opas.orders_products_attributes_id, 
                           opas.stock_id, opas.stock_attribute, opas.customid, opas.products_prid, 
                           opa.products_options_id, opa.products_options_values_id
                           FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK . " opas LEFT JOIN " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " opa 
                             ON (opas.orders_products_attributes_id = opa.orders_products_attributes_id)
                           WHERE  
                             opas.orders_id = " . (int)$order_id . " 
                             AND opas.orders_products_id = " . (int)$orders_products->fields['orders_products_id'] . " 
                           ORDER BY opas.orders_products_attributes_stock_id");
        
        
        //$_SESSION['admin_complete_' . $product['id']] = $orders_products_sba_customid;
        
        
        // If the product was tracked by SBA then perform desired work.
        if ($orders_products_sba_customid->RecordCount() > 0) {

          $customid = array();

          while (!$orders_products_sba_customid->EOF) {
            // provide the "list" of customid's such that only the unique populated customid's are provided (zen_not_null) and not previously accounted
            if (zen_not_null($orders_products_sba_customid->fields['customid'])) {
                if (!(in_array($orders_products_sba_customid->fields['customid'], $customid))) {
                  $customid[] = $orders_products_sba_customid->fields['customid'];
                  $custom_type = 'multi';
                }
            } 
            // I don't like this next method to find the attributes, but am having difficulty doing anything else because of the way that attributes are
            //  "tagged" to the product.  There is no "guaranteed" location other than trying to find the option/value pair and equate it back to the
            //  order data. :/
              
            // Goal of this routine is to provide the individual customid for the specific attribute to be able to capture each individual customid for the
            //   attribute and to then also be able to capture the "total" customid for the product.
            foreach ($orderClass->products[$index]['attributes'] as $key => $value) {
              if ($value['option_id'] == $orders_products_sba_customid->fields['products_options_id']
                  && $value['value_id'] == $orders_products_sba_customid->fields['products_options_values_id']) {
                $orderClass->products[$index]['attributes'][$key]['customid'] = $orders_products_sba_customid->fields['customid'];
                break;
              }
            }

            $orders_products_sba_customid->MoveNext();
          }
          unset($orders_products_sba_customid);
          
          if (!empty($customid)) {
            // Combine the various customids to apply to the ordered product information.
            // Default method is to combine with a comma between each value when multiple exist.
            //   If every customid that is and is not present is to be concatenated then above need to add all to the array
            //    not just those that have data.
            $customid_txt = implode(", ", $customid);
            if (count($customid) == 1) {
              $custom_type = 'single';
            }
          } // EOF if count
        } // EOF if orders_products_sba_customid->RecordCount() > 0
      } // EOF array check if attributes are involved.

      $orderClass->products[$index]['customid'] = array('type' => $custom_type,
                                                        'value' => $customid_txt,
                                                        );

      unset($product);

      $index++;
      $orders_products->MoveNext();
    } // EOF while loop on products
    unset($customid);
    unset($index);
//    unset($order_id);
    unset($orders_products);
  }

  /*
   *  // Notifier from ZC 1.5.5
   *  $this->notify('NOTIFY_ORDER_CART_ADD_PRODUCT_LIST', array('index'=>$index, 'products'=>$products[$i]));
   *
   * applies the customid to the order class' product(s) when function cart is called (storeside new order no orders_id).
   */
  function updateNotifyOrderCartAddProductList(&$orderClass, $notifier, $paramsArray) {
    
    if (!is_array($paramsArray) || !array_key_exists('index', $paramsArray) || !array_key_exists('products', $paramsArray)) {
      trigger_error('Array values not as expected.', E_USER_WARNING);
    }
    
    $index = $paramsArray['index'];
    $productsI = $paramsArray['products'];
    
    if (is_array($productsI) && array_key_exists('attributes', $productsI) && is_array($productsI['attributes']) && !empty($productsI['attributes'])) {
      $orderClass->products[$index]['customid']['value'] = $_SESSION['pwas_class2']->zen_get_customid($productsI['id'], $productsI['attributes']);

        $custom_multi_query = $_SESSION['pwas_class2']->zen_get_sba_attribute_info($productsI['id'], $productsI['attributes'], 'products');

        if (!isset($custom_multi_query) || $custom_multi_query === NULL || $custom_multi_query === false) {
          $custom_type = 'none';
        } elseif (is_array($custom_multi_query) && count($custom_multi_query) > 1) {
          $custom_type = 'multi';
        } else {
          $custom_type = 'single';
        }
      $orderClass->products[$index]['customid']['type'] = $custom_type;
    }
  }
  
  /*
   *NOTIFY_ORDER_CART_ADD_ATTRIBUTE_LIST
   * $this->notify('NOTIFY_ORDER_CART_ADD_ATTRIBUTE_LIST', array('index'=>$index, 'subindex'=>$subindex, 'products'=>$products[$i], 'attributes'=>$attributes));
   */
  function updateNotifyOrderCartAddAttributeList(&$orderClass, $notifier, $paramsArray) {
    $index = $paramsArray['index'];
    $subindex = $paramsArray['subindex'];
    $productsI = $paramsArray['products'];
    
    if ($orderClass->products[$index]['customid']['type']/*$productsI['customid']['type']*/ == 'multi') {
      $orderClass->products[$index]['attributes'][$subindex]['customid'] = 
          $_SESSION['pwas_class2']->zen_get_customid(
            $orderClass->products[$index]['id'],
            array($orderClass->products[$index]['attributes'][$subindex]['option_id']/*$productsI['attributes'][$subindex]['option_id']*/ 
              => $orderClass->products[$index]['attributes'][$subindex]['value_id'],
            ));
    }
  }
  /*
   * Function that is activated when NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT is encountered as a notifier.
   * Doesn't exist in ZC 1.5.1; however, operation of it is needed and can be accomplished in conjunction with NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_BEGIN.
   * ZC 1.5.3 - 1.5.4: $this->notify('NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT', array(), $this->products[$i], $i);
   * ZC 1.5.5: $this->notify('NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT', array('i'=>$i), $this->products[$i], $i);
   */
  //NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT //Line 716
  function updateNotifyOrderProcessingStockDecrementInit(&$callingClass, $notifier, $paramsArray, & $productI, & $i) {
    //global $pwas_class;

    $this->_i = $i;
    $this->_productI = $productI;
    $this->_orderIsSBA = $_SESSION['pwas_class2']->zen_product_is_sba($this->_productI['id']);
    
    if ($this->_orderIsSBA /*&& zen_product_is_sba($this->_productI['id'])*/) { // Only take SBA action on SBA tracked product mc12345678 12-18-2015
      $this->_stock_info = $_SESSION['pwas_class2']->zen_get_sba_stock_attribute_info(zen_get_prid($this->_productI['id']), $this->_productI['attributes'], 'order'); // Sorted comma separated list of the attribute_id.      // START "Stock by Attributes"

      $attributeList = null;
      $customid = null;
      if (is_array($this->_productI) && array_key_exists('attributes', $this->_productI) && is_array($this->_productI['attributes']) && !empty($this->_productI['attributes'])) {
        foreach($this->_productI['attributes'] as $attributes){
          $attributeList[] = $attributes['value_id'];
        }
        $customid = $_SESSION['pwas_class2']->zen_get_customid($this->_productI['id'],$attributeList); // Expects that customid is the string of text representing either the combination product or comma imploded customid of each individual attribute that comprises this variant, or if none is provided/available then the model is returned.
        $productI['customid']['value'] = $customid;
        $this->_productI['customid']['value'] = $customid;
//      $productI['model'] = (zen_not_null($customid) ? $customid : $productI['model']);
        // Options: products_model remains as is: false
        //          products_model is replaced by existing customid: 1
        //          products_model is always replaced by customid regardless of existence of customid: 2
        //          products_model is always used unless empty then customid: 3
        switch (true) {
          case !defined('STOCK_SBA_CUSTOM_FOR_MODEL'):
            $model = $productI['model'];
            break;
          case STOCK_SBA_CUSTOM_FOR_MODEL == '1':
            $model = (zen_not_null($customid) && strlen(trim($customid)) > 0) ? $customid : $productI['model'];
            break;
          case STOCK_SBA_CUSTOM_FOR_MODEL == '2':
            $model = $customid;
            break;
          case STOCK_SBA_CUSTOM_FOR_MODEL == '3':
            $model = (zen_not_null($productI['model']) && strlen(trim($productI['model'])) > 0) ? $productI['model'] : $customid;
            break;
          default:
            $model = $productI['model'];
        }

        $productI['model'] = $model; //(defined('STOCK_SBA_CUSTOM_FOR_MODEL') && STOCK_SBA_CUSTOM_FOR_MODEL !== 'false' && zen_not_null($customid) && strlen(trim($customid)) > 0 ? $customid : $productI['model']);
        $this->_productI['model'] = $model; //(defined('STOCK_SBA_CUSTOM_FOR_MODEL') && STOCK_SBA_CUSTOM_FOR_MODEL !== 'false' && zen_not_null($customid) && strlen(trim($customid)) > 0 ? $customid : $productI['model']);
      }

    // @TODO: work with not decreasing overall stock for items that are generally Tracked by SBA, but have non-stock selections that would not affect the quantities tracked by SBA or in some cases ZC.  
    //   This can be done by setting $callingClass->doStockDecrement as necessary to prevent decreasing the total stock item(s).
    }
    // END "Stock by Attributes"
  }

  /*
   * Function that is activated when NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_BEGIN is encountered as a notifier.
   * ZC 1.5.1(orig): $this->notify('NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_BEGIN');
   * Provided: $this->notify('NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_BEGIN', array('i'=>$i, 'stock_values'=>$stock_values));
   * ZC 1.5.3 - 1.5.5: $this->notify('NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_BEGIN', $i, $stock_values);
   */
  // Line 739
    /**
     * @param     $callingClass
     * @param     $notifier
     * @param     $paramsArray
     * @param     $stock_values
     * @param int $attribute_stock_left
     */
    function updateNotifyOrderProcessingStockDecrementBegin(&$callingClass, $notifier, $paramsArray, &$stock_values, &$attribute_stock_left = 0){
      global $db; //, $pwas_class;

      $this->_stock_values = $stock_values;

      if ($this->_orderIsSBA && $stock_values->RecordCount() > 0) {
        // kuroi: Begin Stock by Attributes additions
        // added to update quantities of products with attributes
        // $stock_attributes_search = array();
        $attribute_stock_left = STOCK_REORDER_LEVEL + 1;  // kuroi: prevent false low stock triggers
        $this->_attribute_stock_left = $attribute_stock_left;

        // mc12345678 If the has attibutes then perform the following work.
        if (is_array($this->_productI) && array_key_exists('attributes', $this->_productI) && is_array($this->_productI['attributes']) && !empty($this->_productI['attributes'])) {
          // Need to identify which records in the PWAS table need to be updated to remove stock from
          // them.  Ie. provide a list of attributes and get a list of stock_ids from pwas.
          // Then process that list of stock_ids to decrement based on their impact on stock.  This
          // all should be a consistent application.
          // mc12345678 Identify a list of attributes associated with the product

          //  mc12345678 17-07-10 the variable $stock_attributes_search is not used further down or elswhere applicable to this function, therefore should not attempt to perform the associated work
          // $stock_attributes_search = $_SESSION['pwas_class2']->zen_get_sba_stock_attribute(zen_get_prid($this->_productI['id']), $this->_productI['attributes'], 'order');
          $stock_attributes_search_new = $_SESSION['pwas_class2']->zen_get_sba_attribute_info($this->_productI['id'], $this->_productI['attributes'], 'order', 'ids');
          if (isset($stock_attributes_search_new) && $stock_attributes_search_new === false) {
              // There is one attribute, but there are no items within the PWAS table that relate to the provided attribute.
              // ie. this could be a non-stock single attribute.
              // There are multiple attributes and neither the combination of them nor if they are evaluated individually results in records found in the PWAS table and therefore the PWAS table stock is not to be affected.
              // Question here is whether the product's stock should be affected? If not, additional action needs to be taken
              //   to prevent stock reduction.
          } elseif (isset($stock_attributes_search_new) && is_array($stock_attributes_search_new) && count($stock_attributes_search_new) == 0) {
              // There are multiple attributes but somehow the returned array has been declared, but nothing assigned to it.
              
          } elseif (isset($stock_attributes_search_new) && $stock_attributes_search_new && count($stock_attributes_search_new) > 0) {

            foreach ($stock_attributes_search_new as $stock_id) {
              // @todo: address in PWAS table whether particular variant should be altered with stock quantities.
              $get_quantity_query = 'SELECT quantity from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id = ' . zen_get_prid($this->_productI['id']) . ' and stock_id = ' . (int)$stock_id;
              $attribute_stock_available = $db->Execute($get_quantity_query, false, false, 0, true);
              if (true) { // Goal here is to identify if the particular attribute/stock item should be affected by a stock change.  If it is not, then this should be false or not performed.
                $attribute_stock_left_test = $attribute_stock_available->fields['quantity'] - $this->_productI['qty'];
                $attribute_update_query = 'UPDATE ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' SET quantity = "'.$attribute_stock_left_test.'" where products_id = ' . zen_get_prid($this->_productI['id']) . ' and stock_id = ' . (int)$stock_id;
                $db->Execute($attribute_update_query, false, false, 0, true);
                if ($attribute_stock_left_test < $attribute_stock_left) {
                  $this->_attribute_stock_left = min($attribute_stock_left_test, $this->_attribute_stock_left);
                  $attribute_stock_left = $this->_attribute_stock_left;
                }
              }
            }
          }
          
/*        $get_quantity_query = 'select quantity from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id = "' . zen_get_prid($this->_productI['id']) . '" and stock_attributes = "' . $stock_attributes_search . '"';
        $get_quantity = $_SESSION['pwas_class2']->zen_get_sba_attribute_info($this->_productI['id'], $this->_productI['attributes'], 'products', 'stock');
  
        // mc12345678 Identify the stock available from SBA.
        $attribute_stock_available = $db->Execute($get_quantity_query, false, false, 0, true);  
        // mc12345678 Identify the stock remaining for the overall stock by removing the number of the current product from the number available for the attributes_id. 
        $attribute_stock_left = *//*$attribute_stock_available->fields['quantity']*//* $get_quantity - $this->_productI['qty'];
  
        // mc12345678 Update the SBA table to reflect the stock remaining based on the above.
        $attribute_update_query = 'update ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' set quantity = '.$attribute_stock_left.' where products_id = "' . zen_get_prid($this->_productI['id']) . '" and stock_attributes = "' . $stock_attributes_search . '"';
        $db->Execute($attribute_update_query, false, false, 0, true);  
        //$this->_attribute_stock_left = $attribute_stock_left;*/
        }
        $attribute_stock_left = $this->_attribute_stock_left;
      }
    }

  /*
   * Function that is activated when NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_END is encountered as a notifier.
   */
  // Line 776
    /**
     * ZC 1.5.1(orig): $this->notify('NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_END');
     *  Provided(orig).
     * ZC 1.5.3 - 1.5.5: $this->notify('NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_END', $i);
     *
     * @param $callingClass
     * @param $notifier
     * @param $paramsArray
     */
    function updateNotifyOrderProcessingStockDecrementEnd(&$callingClass, $notifier, $i) {
    //Need to modify the email that is going out regarding low-stock.
    //paramsArray is $i at time of development.
      if ($this->_orderIsSBA && STOCK_LIMITED == 'true') { // Only take SBA action on SBA tracked product mc12345678 12-18-2015 as of 07-17-2017 prevent generating information related to low-stock email when stock is not limited by ZC.
        $this->orderProcessingI = $i;
        /* Added test for $callingClass->doStockDecrement to support backwards compatibility with ZC 1.5.1 and email generation. 2017-07-12
        */
        if ((isset($callingClass->doStockDecrement) ? $callingClass->doStockDecrement : true)  && $this->_stock_values->RecordCount() > 0 && $this->_attribute_stock_left <= STOCK_REORDER_LEVEL) {
          // kuroi: trigger and details for attribute low stock email
          $callingClass->email_low_stock .=  'ID# ' . zen_get_prid($this->_productI['id']) . ', model# ' . $this->_productI['model'] . ', customid ' . $this->_productI['customid']['value'] . ', name ' . $this->_productI['name'] . ', ';
          foreach($this->_productI['attributes'] as $attributes){
            $callingClass->email_low_stock .= $attributes['option'] . ': ' . $attributes['value'] . ', ';
          }
          $callingClass->email_low_stock .= 'Stock: ' . $this->_attribute_stock_left . "\n\n";
        // kuroi: End Stock by Attribute additions
        }
      }
    }
  
  /**
   * This function was added to support ZC 1.5.1 in the event that the store is setup to to not limit stock (STOCK_LIMITED != 'true'.
   *   It serves no purpose for ZC 1.5.3 and above as an additional notifier exists in those versions to support gathering
   *   the data necessary.
   **/
  function updateNotifyOrderDuringCreateAddedProductLineItem(&$orderClass, $notifier, $paramsArray) {
    return;
  }

  /*
   * Function that is activated when NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM is encountered as a notifier.
   */
//Line 883
    /**
     * @param      $callingClass
     * @param      $notifier
     * @param      $paramsArray
     * @param null $opa_insert_id
     */
  function updateNotifyOrderDuringCreateAddedAttributeLineItem(&$orderClass, $notifier, $paramsArray, $opa_insert_id = NULL) {
    /* First check to see if SBA is installed and if it is then look to see if a value is 
     *  supplied in the stock_id parameter (which should only be populated when a SBA tracked
     *  item is in the order */
//      $_SESSION['paramsArray'] = $paramsArray;
    if ($this->_orderIsSBA && defined('TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK')) {  
      //Need to validate that order had attributes in it.  If so, then were they tracked by SBA and then add to appropriate table.

/*    `orders_products_attributes_stock_id` INT(11) NOT NULL auto_increment, 
      `orders_products_attributes_id` INT(11) NOT NULL default '0',
      `orders_id` INT(11) NOT NULL default '0', 
      `orders_products_id` INT(11) NOT NULL default '0', 
      `stock_id` INT(11) NOT NULL default '0', 
      `stock_attribute` VARCHAR(255) NULL DEFAULT NULL, 
      `products_prid` TINYTEXT NOT NULL, */

      $i = $this->orderProcessingI;

      $customid = '';
      $stock_info = array();
      
      if (zen_not_null($this->_stock_info['stock_id'])) {
        // This is an item that is uniquely identified as a single entry in the PWAS table and is either one or multiple attributes.
        $customid = $orderClass->products[$i]['customid']['value'];
        $stock_info = $this->_stock_info;
      } else {
        if ($orderClass->products[$i]['customid']['type'] == 'multi') {
          // Each attribute is uniquely identified as a record in the PWAS table and is made up of more than one attribute.
          foreach ($orderClass->products[$i]['attributes'] as $key => $value) {
            if ($value['option_id'] == $paramsArray['products_options_id'] && $value['value_id'] == $paramsArray['products_options_values_id']) {
              $customid = $orderClass->products[$i]['attributes'][$key]['customid'];
              $stock_info = $_SESSION['pwas_class2']->zen_get_sba_stock_attribute_info(zen_get_prid($orderClass->products[$i]['id']), array($value), 'order'); // Sorted comma separated list of the attribute_id.
              break;
            }
          }
        }
        // @TODO: at some point to be able to handle combinations of PWAS table items such that there are combinations of records or variants making up a single product.
      } 

      $sql_data_array = array('orders_products_attributes_id' =>$paramsArray['orders_products_attributes_id'],
                            'orders_id' =>$paramsArray['orders_id'], 
                            'orders_products_id' =>$paramsArray['orders_products_id'], 
                            'stock_id' => $stock_info['stock_id'], 
                            'stock_attribute' => $stock_info['stock_attribute'], 
                            'customid' => $customid,
                            'products_prid' =>$paramsArray['products_prid']);
      zen_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK, $sql_data_array); //inserts data into the TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK table.

    }
  } //endif NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM - mc12345678

  /*
   * $zco_notifier->notify('NOTIFY_HEADER_END_ACCOUNT_HISTORY_INFO');
   */
  function updateNotifyHeaderEndAccountHistoryInfo(&$callingClass, $notifier, $paramsArray) {
    global $order, $customid;
    if (!isset($customid) || !is_array($customid)) {
      $customid = array();
    }
    
    for ($i = 0, $n = count($order->products); $i < $n; $i++) {
      $customid[$i] = '';
      
      if (STOCK_SBA_DISPLAY_CUSTOMID == 'true') {
        $customid[$i] .= (zen_not_null($order->products[$i]['customid']['value']) 
                ? '<br />(' . $order->products[$i]['customid']['value'] . ') ' 
                : $order->products[$i]['customid']['value']);
      }
      $customid[$i] .= (zen_not_null($order->products[$i]['model'])
            ? '<br />(' . $order->products[$i]['model'] . ')'
            : '');
    }
  }

  /*
   * $zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION');
   * NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION
   */
  function updateNotifyHeaderEndCheckoutConfirmation(&$callingClass, $notifier, $paramsArray) {
    global $order, $customid;
    
    if (!isset($customid) || !is_array($customid)) {
      $customid = array();
    }
    
    foreach ($order->products as $i => $productsI) {
      if(isset($productsI['customid']['value'])) {
        $customid[$i] = '<br />(' . $productsI['customid']['value'] . ')';
      }
    }
  }

  // NOTIFY_HEADER_END_SHOPPING_CART
    /**
     * @param $callingClass
     * @param $notifier
     * @param $paramsArray
     */
    function updateNotifyHeaderEndShoppingCart(&$callingClass, $notifier, $paramsArray) {
    global $productArray, $flagAnyOutOfStock, $db;

    $flagAnyInsideOutOfStock = false;
    $flagAnyOutsideOutOfStock = false;

    $products = $_SESSION['cart']->get_products();

    if (!defined('STOCK_MARK_ALLOW_MIX_TOTAL_ALL')) define ('STOCK_MARK_ALLOW_MIX_TOTAL_ALL', 'false');

    if ( PRODUCTS_OPTIONS_SORT_BY_PRICE =='1' ) {
      $order_by= ' order by LPAD(pa.products_options_sort_order,11,"0")';
    } else {
      $order_by= ' order by LPAD(pa.products_options_sort_order,11,"0"), pa.options_values_price';
    }

    //LPAD - Return the string argument, left-padded with the specified string
    //example: LPAD(po.products_options_sort_order,11,"0") the field is 11 digits, and is left padded with 0
    if (PRODUCTS_OPTIONS_SORT_ORDER=='0') {
      $options_order_by= ' order by LPAD(popt.products_options_sort_order,11,"0"), popt.products_options_name';
    } else {
      $options_order_by= ' order by popt.products_options_name';
    }

    for ($i = 0, $n = is_array($productArray) ? count($productArray) : 0 ; $i < $n; $i++) {
      if (is_array($productArray[$i]) && (isset($productArray[$i]['attributes']) || array_key_exists('attributes', $productArray[$i])) && is_array($productArray[$i]['attributes']) && !empty($productArray[$i]['attributes']) && $_SESSION['pwas_class2']->zen_product_is_sba($productArray[$i]['id'])) {
        $productArray[$i]['attributeImage'] = array();

        if (STOCK_CHECK == 'true') {
          $SBAqtyAvailable = zen_get_products_stock($productArray[$i]['id'], $products[$i]['attributes']); // Quantity of product available with the selected attribute(s).
          $totalQtyAvailable = zen_get_products_stock($productArray[$i]['id']); // Total quantity of product available if all attribute optioned product were added to the cart.

          // Clear flag stock condition for SBA product to be controlled by SBA below
          $productArray[$i]['flagStockCheck'] = '';

          /*
          STOCK_MARK_ALLOW_MIX_TOTAL_ALL = 'true' or 'false' such that true marks all product, false just the one.
          Two options either mark all variants as out of stock or only the quantity that exceeds the variant quantity when:
            the stock is allowed to sell beyond the available quantity (STOCK_ALLOW_CHECKOUT === 'true'),
            the product is set to have Product Qty Min/Unit Mix set to true, AND
            the variant quantity in the cart exceeds total stock quantity of the product.
          */
          if ($SBAqtyAvailable - $products[$i]['quantity'] < 0 || (($totalQtyAvailable - $_SESSION['cart']->in_cart_mixed($productArray[$i]['id']) < 0) && (STOCK_MARK_ALLOW_MIX_TOTAL_ALL === 'false' ? STOCK_ALLOW_CHECKOUT !== 'true' : true))) {
            $productArray[$i]['flagStockCheck'] = '<span class="markProductOutOfStock">' . STOCK_MARK_PRODUCT_OUT_OF_STOCK . '</span>';
            $flagAnyInsideOutOfStock = true;
//            $flagAnyOutOfStock = true;
          }
          $productArray[$i]['stockAvailable'] = $SBAqtyAvailable;
        } // EOF if (STOCK_CHECK == 'true')
        
        // Ensure that additional stock fields are added at least for SBA product.  If needs to be for all product, then 
        //  This information should be moved outside of the above if statement.  Did not carry over: $products_options_type
        //  nor $productsQty = 0; $productsQty = 0 was previously used to identify "duplicates" and is not needed.
        //  $products_options_type is not yet used for anything else, but was perhaps to address something specific in future
        //  coding.  It will remain off of here for now.
        $custom_multi_query = $_SESSION['pwas_class2']->zen_get_sba_attribute_info($productArray[$i]['id'], $productArray[$i]['attributes'], 'products');
        $custom_type = 'single';

        if (!isset($custom_multi_query) || $custom_multi_query === NULL || $custom_multi_query === false) {
          $custom_type = 'none';
        } elseif (is_array($custom_multi_query) && count($custom_multi_query) > 1) {
          $custom_type = 'multi';
          foreach ($productArray[$i]['attributes'] as $key => $value) {
            $customid_new = $_SESSION['pwas_class2']->zen_get_customid($productArray[$i]['id'], $value);
            $productArray[$i]['attributes'][$key]['customid'] = ((STOCK_SBA_DISPLAY_CUSTOMID == 'true') ? $customid_new : null);
          }
        }
        $productArray[$i]['customid']['type'] = $custom_type;
        $productArray[$i]['customid']['value'] = (STOCK_SBA_DISPLAY_CUSTOMID == 'true') ? $_SESSION['pwas_class2']->zen_get_customid($productArray[$i]['id'], $products[$i]['attributes']) : null;
//        $productArray[$i]['stockAvailable'] = null;
        $upper_limit = STOCK_REORDER_LEVEL;
        $productArray[$i]['lowproductstock'] = ($productArray[$i]['stockAvailable'] < $upper_limit) ? true : false;
        
  // Need to collect all of the option ids that are associated with the
  // product, then sort them by the normal sort order in reverse.

        /*
         *        $sql = "select distinct pov.products_options_values_id,
                        pov.products_options_values_name,
                        pa.*, p.products_quantity,
                      " . ($this->_products_options_names_count <= 1 ? " pas.stock_id as pasid, pas.quantity as pasqty, pas.sort,  pas.customid, pas.title, pas.product_attribute_combo, pas.stock_attributes, " : "") . " pas.products_id

                from      " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                left join " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov on (pa.options_values_id = pov.products_options_values_id)
                left join " . TABLE_PRODUCTS . " p on (pa.products_id = p.products_id)

                left join " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " pas on
                (p.products_id = pas.products_id and FIND_IN_SET(pa.products_attributes_id, pas.stock_attributes) > 0 )
            where pa.products_id = :products_id:
            and       pa.options_id = :options_id:
            and       pov.language_id = :languages_id: " .
              $order_by;

       $sql = $db->bindVars($sql, ':products_id:', $_GET['products_id'], 'integer');
       $sql = $db->bindVars($sql, ':options_id:', $products_options_names->fields['products_options_id'], 'integer');
       $sql = $db->bindVars($sql, ':languages_id:', $_SESSION['languages_id'], 'integer');

       $products_options = $db->Execute($sql);

         */

        if (!isset($this->_isSBA[(int)$productArray[$i]['id']]['sql' . (int)$i])) {
          //get the option/attribute list
          $sql = "select distinct popt.products_options_id, popt.products_options_name, popt.products_options_sort_order,
                                popt.products_options_type, popt.products_options_length, popt.products_options_comment,
                                popt.products_options_size,
                                popt.products_options_images_per_row,
                                popt.products_options_images_style,
                                popt.products_options_rows
                from        " . TABLE_PRODUCTS_OPTIONS . " popt
                left join " . TABLE_PRODUCTS_ATTRIBUTES . " patrib ON (patrib.options_id = popt.products_options_id)
                where patrib.products_id= :products_id:
                and popt.language_id = :languages_id: " .
              $options_order_by;

          $sql = $db->bindVars($sql, ':products_id:', $productArray[$i]['id'], 'integer');
          $sql = $db->bindVars($sql, ':languages_id:', $_SESSION['languages_id'], 'integer');
          $products_options_names = $db->Execute($sql);
          $this->_isSBA[(int)$productArray[$i]['id']]['sql' . $i] = $products_options_names;
        } else {
          $products_options_names = $this->_isSBA[(int)$productArray[$i]['id']]['sql'. $i];

          if (method_exists($products_options_names, 'rewind')) {
            $products_options_names->Rewind();
          } else {
            $products_options_names->Move(0);
            $products_options_names->MoveNext();
          }
        }

        while (!$products_options_names->EOF) {
          $sql = "select distinct pa.attributes_image,
                  pa.products_options_sort_order,
                  pa.options_values_price
                  from      " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                  where     pa.products_id = :products_id:
                  and       pa.options_id = :options_id:
                  and       pa.options_values_id = :options_values_id:" .
              $order_by;

          $sql = $db->bindVars($sql, ':products_id:', $productArray[$i]['id'], 'integer');
          $sql = $db->bindVars($sql, ':options_id:', $products_options_names->fields['products_options_id'], 'integer');
          $sql = $db->bindVars($sql, ':options_values_id:', $productArray[$i]['attributes'][$products_options_names->fields['products_options_id']]['options_values_id'], 'integer');

          $attribute_image = $db->Execute($sql);

          if (!$attribute_image->EOF && $attribute_image->RecordCount() > 0 && zen_not_null($attribute_image->fields['attributes_image'])) {
            $productArray[$i]['attributeImage'][] = $attribute_image->fields['attributes_image'];
          }
          $products_options_names->MoveNext();
        }
        if (!empty($productArray[$i]['attributeImage'])) {
          $productArray[$i]['productsImage'] = (IMAGE_SHOPPING_CART_STATUS == 1 ? zen_image(DIR_WS_IMAGES . $productArray[$i]['attributeImage'][count($productArray[$i]['attributeImage']) - 1], $productArray[$i]['productsName'], IMAGE_SHOPPING_CART_WIDTH, IMAGE_SHOPPING_CART_HEIGHT) : '');
        }
        unset($productArray[$i]['attributeImage']);

  /*      foreach ($productArray[$i]['attributes'] as $opt_id=>$opt_array) {
          $sql = "select distinct pa.attributes_image
                  from      " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                  where     pa.products_id = :products_id:
                  and       pa.options_id = :options_id:
                  and       pa.options_values_id = :options_values_id:" .
              $order_by;
  
          $sql = $db->bindVars($sql, ':products_id:', $productArray[$i]['id'], 'integer');
          $sql = $db->bindVars($sql, ':options_id:', $opt_id, 'integer');
          $sql = $db->bindVars($sql, ':options_values_id:', $opt_array['options_values_id'], 'integer');

          $attribute_image = $db->Execute($sql);
          if (!$attribute_image->EOF && $attribute_image->RecordCount() > 0 && zen_not_null($attribute_image->fields['attributes_image'])) {
            $productArray[$i]['attributeImage'][] = $attribute_image->fields['attributes_image'];
          }
        }
        if (count($productArray[$i]['attributeImage']) > 0) {
          $productArray[$i]['productsImage'] = (IMAGE_SHOPPING_CART_STATUS == 1 ? zen_image(DIR_WS_IMAGES . $productArray[$i]['attributeImage'][count($productArray[$i]['attributeImage']) - 1], $productArray[$i]['productsName'], IMAGE_SHOPPING_CART_WIDTH, IMAGE_SHOPPING_CART_HEIGHT) : '');
        }
        unset($productArray[$i]['attributeImage']); */
      } else if ($productArray[$i]['flagStockCheck']) {
            $flagAnyOutsideOutOfStock = true;
      }

    } // EOF for ($i = 0, $n = count($productArray); $i < $n; $i++) {
    if (!$flagAnyInsideOutOfStock && !$flagAnyOutsideOutOfStock) {
      $flagAnyOutOfStock = '';
    }
//    $flagAnyOutOfStock = $flagAnyInsideOutOfStock || $flagAnyOutsideOutOfStock;
  }
  
  // NOTIFY_HEADER_START_CHECKOUT_SHIPPING
  function updateNotifyHeaderStartCheckoutShipping(&$callingClass, $notifier, $paramsArray) {
    // Attempt to validate that prepared to address/process SBA related information.  The initial logic here is
    // from a default ZC includes/modules/pages/checkout_shipping/header_php.php file which could otherwise be modified
    //  but instead of repeating exactly the contents of that file, it's potential redirects, etc... Just want to validate
    //  that the cart is ready to handle working with the products.
    if ($_SESSION['cart']->count_contents() > 0 && isset($_SESSION['customer_id']) && $_SESSION['customer_id'] && zen_get_customer_validate_session($_SESSION['customer_id']) != false) {
      $_SESSION['valid_to_checkout'] = true;
      $_SESSION['cart']->get_products(true);
      if ($_SESSION['valid_to_checkout']) {
        // Now we are "allowed" to process cart items and specifically to ensure that the product if SBA tracked can 
        //  move forward in the cart.
        if ((STOCK_CHECK == 'true') && (STOCK_ALLOW_CHECKOUT != 'true')) {
          $products = $_SESSION['cart']->get_products();
          for ($i = 0, $n = count($products); $i < $n; $i++) {
            unset($attributes);
            $attributes = null;

            if (isset($products[$i]) && is_array($products[$i]) && array_key_exists('attributes', $products[$i]) && isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
              if ($_SESSION['pwas_class2']->zen_product_is_sba($products[$i]['id'])) {
                $attributes = $products[$i]['attributes'];
              } 
            }
            if (zen_not_null($attributes)) {
              if (zen_check_stock($products[$i]['id'], $products[$i]['quantity'], $attributes)) {
                zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
                break;
              }
            } else {
              $qtyAvailable = zen_get_products_stock($products[$i]['id']);
              if ($qtyAvailable - $products[$i]['quantity'] < 0 || $qtyAvailable - $_SESSION['cart']->in_cart_mixed($products[$i]['id']) < 0) {
                zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
                break;
              }
            }
          }
        } // EOF stock check against total quantity.
      } // EOF valid to checkout.
    } // EOF opening validation
  } // EOF function updateNotifyHeaderStartCheckoutShipping 
  
  // NOTIFY_HEADER_START_CHECKOUT_CONFIRMATION
  function updateNotifyHeaderStartCheckoutConfirmation(&$callingClass, $notifier) {
    $this->from = 'order';
  }
  
  function setCheckStockParams($from) {
    if ($from == 'order') {
      $tmp_attrib = array();
    
      // If there is no order created then there is nothing to be done at this point.
      if (!isset($GLOBALS['order'])) return;

      // Duplicate the order information here for use/reading.
      $order = $GLOBALS['order'];

      // If there are no products in the order, then there is no stock to address.
      if (empty($order->products)) return;
      
      // Expect that the product "counter" is the variable i and is in the global space.
      if (!isset($GLOBALS['i'])) return;

      $i = $GLOBALS['i'];

      // if the product doesn't have any sub-characteristics or there are no attributes then no specific SBA stock to consider.
      if (empty($order->products[$i]) && empty($order->products[$i]['attributes'])) return;
      
      // Obtain the attributes from the specific product.
      $attributes = $order->products[$i]['attributes'];
      
      // Build the catalog side attributes from the attribute data of the order class.
      foreach ($attributes as $attrib) {
        $tmp_attrib[$attrib['option_id']] = $attrib['value_id'];
      }

      // Set the internal attributes to the temporary array that was generated.
      $this->attributes = $tmp_attrib;
    }
  }
  
  /*
   * Generic function that is activated when any notifier identified in the observer is called but is not found in one of the above previous specific update functions is encountered as a notifier.
   */
  function update(&$callingClass, $notifier, $paramsArray) {
  //global $db;
    if ($notifier == 'NOTIFY_ATTRIBUTES_MODULE_SALE_MAKER_DISPLAY_PRICE_PERCENTAGE') {
      $this->updateNotifyAttributesModuleSaleMakerDisplayPricePercentage($callingClass, $notifier, $paramsArray);
    }
  
    if ($notifier == 'NOTIFY_ATTRIBUTES_MODULE_START_OPTION') {
      $this->updateNotifyAttributesModuleStartOption($callingClass, $notifier, $paramsArray);
    }
    
    if ($notifier == 'NOTIFY_ATTRIBUTES_MODULE_ORIGINAL_PRICE') {
      $this->updateNotifyAttributesModuleOriginalPrice($callingClass, $notifier, $paramsArray);
    }
    
    if ($notifier == 'NOTIFY_ATTRIBUTES_MODULE_ATTRIB_SELECTED') {
      $this->updateNotifyAttributesModuleAttribSelected($callingClass, $notifier, $paramsArray);
    }
    
    if ($notifier == 'NOTIFY_ATTRIBUTES_MODULE_START_OPTIONS_LOOP') {
      global $products_options;
      $this->updateNotifyAttributesModuleStartOptionsLoop($callingClass, $notifier, $paramsArray, $products_options->fields);
    }
    
    if ($notifier == 'NOTIFY_ATTRIBUTES_MODULE_OPTION_BUILT') {
      global $options_name, $options_menu, $options_comment,
             $options_comment_position, $options_html_id, $options_attributes_image; 
      
      $this->updateNotifyAttributesModuleOptionBuilt($callingClass, $notifier, $paramsArray,
                                                   $options_name, $options_menu, $options_comment,
                                                   $options_comment_position, $options_html_id,
                                                   $options_attributes_image);
    }

    if ($notifier == 'NOTIFY_ATTRIBUTES_MODULE_DEFAULT_SWITCH') {
      global $options_name, $options_menu, $options_comment, $options_comment_position, $options_html_id;
      
      $this->updateNotifyAttributesModuleDefaultSwitch($callingClass, $notifier, $paramsArray, $options_name, $options_menu, $options_comment, $options_comment_position, $options_html_id);
    }
    
    if ($notifier == 'NOTIFY_ORDER_DURING_CREATE_ADDED_PRODUCT_LINE_ITEM'){
      
    }

    /**
     * Notifier is not present in ZC 1.5.1 and must be added with a modification to support operation.
     * ZC 1.5.1 (added):       $this->notify('NOTIFY_ORDER_AFTER_QUERY', $order_id);
     * ZC 1.5.3 through 1.5.5: $this->notify('NOTIFY_ORDER_AFTER_QUERY', array(), $order_id);
     * ZC 1.5.6:               $this->notify('NOTIFY_ORDER_AFTER_QUERY', IS_ADMIN_FLAG, $order_id);
     **/
    if ($notifier == 'NOTIFY_ORDER_AFTER_QUERY') {
      $this->updateNotifyOrderAfterQuery($callingClass, $notifier, array(), $paramsArray);
    }
    
    if ($notifier == 'NOTIFY_ORDER_PROCESSING_ATTRIBUTES_BEGIN') {
      
//      $stock_attribute = zen_get_sba_stock_attribute(zen_get_prid($this->products[$i]['id']), $this->products[$i]['attributes'], 'order');
//      $stock_id = zen_get_sba_stock_attribute_id(zen_get_prid($this->products[$i]['id']), $this->products[$i]['attributes'], 'order'); //true; // Need to use the $stock_attribute/attributes to obtain the attribute id.
    }

    /**
     * ZC 1.5.1: $this->notify('NOTIFY_ORDER_CART_ADD_PRODUCT_LIST', array('index'=>$index, 'products'=>$products[$i]));
     **/
    if ($notifier == 'NOTIFY_ORDER_CART_ADD_PRODUCT_LIST') {
      $this->updateNotifyOrderCartAddProductList($callingClass, $notifier, $paramsArray);
    }
    
    /**
     * ZC 1.5.1: $this->notify('NOTIFY_ORDER_CART_ADD_ATTRIBUTE_LIST', array('index'=>$index, 'subindex'=>$subindex, 'products'=>$products[$i], 'attributes'=>$attributes));
     **/
    if ($notifier == 'NOTIFY_ORDER_CART_ADD_ATTRIBUTE_LIST') {
      $this->updateNotifyOrderCartAddAttributeList($callingClass, $notifier, $paramsArray);
    }
    
    /**
     *Provided in ZC 1.5.1: $this->notify('NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_BEGIN', array('i'=>$i, 'stock_values'=>$stock_values));
     **/
    if ($notifier == 'NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_BEGIN'){
      global $attribute_stock_left;

    /*
     * Function that is activated when NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT is encountered as a notifier.
     */
    //NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT //Line 716
  //  function updateNotifyOrderProcessingStockDecrementInit(&$callingClass, $notifier, $paramsArray, & $productI, & $i) {
      $i = $paramsArray['i'];
      $this->orderProcessingI = $i;
      $productI = $callingClass->products[$i];
      $this->_stock_values = $paramsArray['stock_values'];
      $stock_values = $this->_stock_values;
      $this->updateNotifyOrderProcessingStockDecrementInit($callingClass, $notifier, $paramsArray, $productI, $i);
      $this->updateNotifyOrderProcessingStockDecrementBegin($callingClass, $notifier, $paramsArray, $stock_values, $attribute_stock_left);
    }

    /*
     * Function that is activated when NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_END is encountered as a notifier.
     */
    // Line 776
    if ($notifier == 'NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_END') {
      if (!isset($paramsArray) && isset($this->_i)) {
        $paramsArray = $this->_i;
      }
      if (isset($paramsArray)) {
        $this->updateNotifyOrderProcessingStockDecrementEnd($callingClass, $notifier, $paramsArray);
      }
    }
    
    if ($notifier == 'NOTIFY_ORDER_DURING_CREATE_ADDED_PRODUCT_LINE_ITEM') {
      if (STOCK_LIMITED != 'true') {
        if ($this->_orderIsSBA) { // Only take SBA action on SBA tracked product mc12345678 12-18-2015
          for ($i=0, $n=count($this->products); $i<$n; $i++) {
            if ($callingClass->products[$i]['id'] == $paramsArray['products_prid']) {
              break;
            }
          }
          $paramsArray = array();
          $productI = $callingClass->products[$i];
          $this->orderProcessingI = $i;
          // There are some values that are set within the below function that would be omitted in ZC 1.5.1 if 
          //   NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_BEGIN was not triggered.
          $this->updateNotifyOrderProcessingStockDecrementInit($callingClass, $notifier, $paramsArray, $productI, $i);
//        $this->updateNotifyOrderProcessingStockDecrementEnd($callingClass, $notifier, $i); 
        // (If stock isn't being limited, then there is no reason to make notification which is what is performed by the 
        //   above function). The value collected though is needed even in ZC 1.5.1 to support downstream operation.
        }
      }
    }
    
    if ($notifier == 'NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM') {
      $this->updateNotifyOrderDuringCreateAddedAttributeLineItem($callingClass, $notifier, $paramsArray, $paramsArray['orders_products_attributes_id']);
    } //endif NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM - mc12345678
    
    /**
     * ZC 1.5.1: $zco_notifier->notify('NOTIFY_HEADER_END_ACCOUNT_HISTORY');
     **/
    if ($notifier == 'NOTIFY_HEADER_END_ACCOUNT_HISTORY_INFO') {
      $this->updateNotifyHeaderEndAccountHistoryInfo($callingClass, $notifier, $paramsArray);
    }

    /**
     * ZC 1.5.1: $zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION');
     **/
    if ($notifier == 'NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION') {
      $this->updateNotifyHeaderEndCheckoutConfirmation($callingClass, $notifier, $paramsArray);
    }
    
    /**
     * ZC 1.5.1: $zco_notifier->notify('NOTIFY_HEADER_END_SHOPPING_CART');
     **/
    if ($notifier == 'NOTIFY_HEADER_END_SHOPPING_CART') {
      $this->updateNotifyHeaderEndShoppingCart($callingClass, $notifier, $paramsArray);
    }
    
    /**
     * ZC 1.5.1: $zco_notifier->notify('NOTIFY_HEADER_START_CHECKOUT_SHIPPING');
     **/
    if ($notifier == 'NOTIFY_HEADER_START_CHECKOUT_SHIPPING') {
      $this->updateNotifyHeaderStartCheckoutShipping($callingClass, $notifier, $paramsArray);
    } //endif NOTIFY_HEADER_START_CHECKOUT_SHIPPING
  } //end update function - mc12345678
} //end class - mc12345678

