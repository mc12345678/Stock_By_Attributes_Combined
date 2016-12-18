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
  
  
  /*
   * This is the observer for the includes/classes/order.php file to support Stock By Attributes when the order is being processed at the end of the purchase.
   */
  function __construct() {
    
    $attachNotifier = array();
    $attachNotifier[] = 'NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM';
    $attachNotifier[] = 'NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT';
    $attachNotifier[] = 'NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_BEGIN';
    $attachNotifier[] = 'NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_END';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_START_OPTION';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_START_OPTIONS_LOOP';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_SALE_MAKER_DISPLAY_PRICE_PERCENTAGE';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_ORIGINAL_PRICE';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_ATTRIB_SELECTED';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_DEFAULT_SWITCH';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_OPTION_BUILT';
    $attachNotifier[] = 'NOTIFY_HEADER_END_SHOPPING_CART';
    $attachNotifier[] = 'NOTIFY_HEADER_START_CHECKOUT_SHIPPING';

  
    $this->attach($this, $attachNotifier);

    $this->_products_options_names_current = 0; // Initialize this variable to 0.
  }  
  
  /*
   * NOTIFY_ATTRIBUTES_MODULE_START_OPTION
   */
   function updateNotifyAttributesModuleStartOption(&$callingClass, $notifier, $paramsArray) {
     global $db, $sql, /*$options_menu_images, $moveSelectedAttribute, */
        $products_options_array, $options_attributes_image,
        $products_options_names, /*$products_options_names_count,*/
       /*$stock,*/ $is_SBA_product, $order_by, $products_options; //, $pwas_class;
     
     $this->_options_menu_images = array();
     $this->_moveSelectedAttribute = false;
     $products_options_array = array();
     $options_attributes_image = array();
     // Could do the calculation here the first time set a variable above as part of the class and then reuse that... instead of the modification to the attributes file...
     if (!zen_not_null($this->_products_options_names_count)) {
       $this->_products_options_names_count = $products_options_names->RecordCount();
     }
//     $products_options_names_count = $products_options_names->RecordCount();

     if ($_SESSION['pwas_class2']->zen_product_is_sba($_GET['products_id'])) {
       $this->_isSBA = true;
     } else {
       $this->_isSBA = false;
     }
     
//     $stock->_isSBA = $this->_isSBA;
     $is_SBA_product = $this->_isSBA;
     
     if ($this->_isSBA) {
       // Want to do a SQL statement to see the quantity of non-READONLY attributes.  If there is only one non-READONLY attribute, then
       //   do additional SQL to add the "missing" attributes that would get displayed.  But, do not have the "main" sql modified otherwise
       //   the display will get all wonky (multiple listings where not desired).  Will need to modify the SQL result for each result applicable to the
       //   one option_id assuming it is not READONLY.
       // Understand that already cycling through the product options, therefore if there are multiple options, the current option is not readonly
       //   and there is only one non-readonly attribute, then that is when the "new" sql needs to be activated to populate the current option...
       $process_this = false;
       if (!$this->_noread_done && $products_options_names->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_READONLY && $products_options_names->RecordCount() > 1) {
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
            ((($this->_products_options_names_count <= 1 || ($process_this == true && isset($noread) && $noread->fields['total'] == 1)) && defined('SBA_SHOW_OUT_OF_STOCK_ATTR_ON_PRODUCT_INFO') && SBA_SHOW_OUT_OF_STOCK_ATTR_ON_PRODUCT_INFO == '0' && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS !=1 && PRODINFO_ATTRIBUTE_DYNAMIC_STATUS !=3) && (defined('PRODUCTS_OPTIONS_TYPE_GRID') ? $products_options_name->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_GRID : true) && (defined('PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID') ? $products_options_name->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID : true)) ? " AND (pas.quantity > '0' OR (pwas.quantity IS NULL AND pa.attributes_display_only = '1')) ": "" ) .
            /* && $products_options_name->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_READONLY  */
            $order_by;
              
       $sql = $db->bindVars($sql, ':products_id:', $_GET['products_id'], 'integer');
       $sql = $db->bindVars($sql, ':options_id:', $products_options_names->fields['products_options_id'], 'integer');
       $sql = $db->bindVars($sql, ':languages_id:', $_SESSION['languages_id'], 'integer');

       $products_options = $db->Execute($sql);
     }
   }

  /*
   * 'NOTIFY_ATTRIBUTES_MODULE_START_OPTIONS_LOOP'
   */
  function updateNotifyAttributesModuleStartOptionsLoop(&$callingClass, $notifier, $i, &$products_options_fields){
    global $db, /*$options_menu_images, */$products_options_array, $products_options_names,
           $PWA_STOCK_QTY;

    $this->_products_options_names_current++;
    
    if ($this->_isSBA && (in_array($products_options_names->fields['products_options_type'], array(PRODUCTS_OPTIONS_TYPE_SELECT_SBA, PRODUCTS_OPTIONS_TYPE_RADIO, PRODUCTS_OPTIONS_TYPE_FILE, PRODUCTS_OPTIONS_TYPE_TEXT, PRODUCTS_OPTIONS_TYPE_SELECT)) || ((PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '0' && $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_SELECT_SBA) || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2' && $products_options_names->RecordCount() > 1) || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '3' && $products_options_names->RecordCount() == 1)))) {  // Perhaps only certain features need to be bypassed, but for now all mc12345678
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
      if ($products_options_fields['pasqty'] < 1 && STOCK_CHECK == 'true' && $products_options_fields['pasid'] > 0) {
        //test, only applicable to products with-out the display-only attribute set
        if ($products_options_DISPLAYONLY->fields['attributes_display_only'] < 1) {
          $products_options_fields['products_options_values_name'] = $products_options_fields['products_options_values_name'] . PWA_OUT_OF_STOCK;
          $products_options_array[sizeof($products_options_array)-1] = array('id' =>
              $products_options_fields['products_options_values_id'],
              'text' => $products_options_fields['products_options_values_name']);
        }
      }

      //Add qty to atributes based on STOCK_SHOW_ATTRIB_LEVEL_STOCK setting
      //Only add to Radio, Checkbox, and selection lists
      //PRODUCTS_OPTIONS_TYPE_RADIO PRODUCTS_OPTIONS_TYPE_CHECKBOX
      //Exclude the following:
      //PRODUCTS_OPTIONS_TYPE_TEXT PRODUCTS_OPTIONS_TYPE_FILE PRODUCTS_OPTIONS_TYPE_READONLY
      //PRODUCTS_OPTIONS_TYPE_SELECT_SBA
      $PWA_STOCK_QTY = null; //initialize variable
      if ($products_options_names->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_TEXT) {
        if ($products_options_names->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_FILE) {
          if ($products_options_names->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_READONLY) {
            /*if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_SELECT_SBA)*/ {

              if (STOCK_SHOW_ATTRIB_LEVEL_STOCK == 'true' && $products_options_fields['pasqty'] > 0) {
                //test, only applicable to products with-out the display-only attribute set
                if ($products_options_DISPLAYONLY->fields['attributes_display_only'] < 1) {
                  $PWA_STOCK_QTY = PWA_STOCK_QTY . $products_options_fields['pasqty'] . ' ';
                  //show custom ID if flag set to true
                  if (!empty($products_options_fields['customid']) && (!defined('ATTRIBUTES_SBA_DISPLAY_CUSTOMID') || (STOCK_SBA_DISPLAY_CUSTOMID == 'true' && ATTRIBUTES_SBA_DISPLAY_CUSTOMID == '1') || ATTRIBUTES_SBA_DISPLAY_CUSTOMID == '2')) {
                    $PWA_STOCK_QTY .= ' (' . $products_options_fields['customid'] . ') ';
                  }
                }
              } elseif (STOCK_SHOW_ATTRIB_LEVEL_STOCK == 'true' && $products_options_fields['pasqty'] < 1 && $products_options_fields['pasid'] < 1) {
                //test, only applicable to products with-out the display-only attribute set
                if ($products_options_DISPLAYONLY->fields['attributes_display_only'] < 1) {
                  //use the qty from the product, unless it is 0, then set to out of stock.
                  if ($this->_products_options_names_count <= 1) {
                    if ($products_options_fields['products_quantity'] > 0) {
                      $PWA_STOCK_QTY = PWA_STOCK_QTY . $products_options_fields['products_quantity'] . ' ';
                    } else {
                      $products_options_fields['products_options_values_name'] = $products_options_fields['products_options_values_name'] . PWA_OUT_OF_STOCK;
                      $products_options_array[sizeof($products_options_array)-1] = array('id' =>
                          $products_options_fields['products_options_values_id'],
                          'text' => $products_options_fields['products_options_values_name']);

                    }
                  }

                  //show custom ID if flag set to true
                  if (!empty($products_options_fields['customid']) && (!defined('ATTRIBUTES_SBA_DISPLAY_CUSTOMID') || (STOCK_SBA_DISPLAY_CUSTOMID == 'true' && ATTRIBUTES_SBA_DISPLAY_CUSTOMID == '1') || ATTRIBUTES_SBA_DISPLAY_CUSTOMID == '2')) {
                    $PWA_STOCK_QTY .= ' (' . $products_options_fields['customid'] . ') ';
                  }
                }
              } elseif (!empty($products_options_fields['customid']) && (!defined('ATTRIBUTES_SBA_DISPLAY_CUSTOMID') || (STOCK_SBA_DISPLAY_CUSTOMID == 'true' && ATTRIBUTES_SBA_DISPLAY_CUSTOMID == '1') || ATTRIBUTES_SBA_DISPLAY_CUSTOMID == '2')) {
                //show custom ID if flag set to true
                //test, only applicable to products with-out the display-only attribute set
                if ($products_options_DISPLAYONLY->fields['attributes_display_only'] < 1) {
                  $PWA_STOCK_QTY .= ' (' . $products_options_fields['customid'] . ') ';
                }
              }
            }
          }
        }
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
        if ($picture->EOF || $picture->RecordCount() == 0) {
          $this->_options_menu_images['product_image'] = '';
        } else {
          $this->_options_menu_images['product_image'] = DIR_WS_IMAGES . $picture->fields['products_image'];
        }
      }
      // END "Stock by Attributes" SBA
    } // End if _isSBA

    $this->_products_options_fields = $products_options_fields;

    if ($this->_products_options_names_current == 1) {
      global $currencies;

      $show_attribute_stock_left = true;
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
    global $db, $products_options, $products_options_names, $currencies, $new_attributes_price, $product_info, $products_options_display_price, $PWA_STOCK_QTY;
    
    if ($this->_isSBA && ( $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_SELECT_SBA || ((PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '0' && $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_SELECT_SBA) || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2' && $products_options_names->RecordCount() > 1) || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '3' && $products_options_names->RecordCount() == 1)))) {  // Perhaps only certain features need to be bypassed, but for now all mc12345678
      // START "Stock by Attributes" SBA added original price for display, and some formatting
      $originalpricedisplaytext = null;
      if (STOCK_SHOW_ORIGINAL_PRICE_STRUCK == 'true' && !(zen_get_attributes_price_final($products_options->fields["products_attributes_id"], 1, '', 'false') == $new_attributes_price || (zen_get_attributes_price_final($products_options->fields["products_attributes_id"], 1, '', 'false') == -$new_attributes_price && ((int)($products_options->fields['price_prefix'] . "1") * $products_options->fields['options_values_price']) < 0)) ) {
        //Original price struck through
        if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_RADIO || $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_CHECKBOX) {
          //use this if a PRODUCTS_OPTIONS_TYPE_RADIO or PRODUCTS_OPTIONS_TYPE_CHECKBOX
          //class="normalprice" can be used in a CSS file to control the text properties, not compatable with selection lists
          $originalpricedisplaytext = ATTRIBUTES_PRICE_DELIMITER_PREFIX . '<span class="normalprice">' . $products_options->fields['price_prefix'] . $currencies->display_price(zen_get_attributes_price_final($products_options->fields["products_attributes_id"], 1, '', 'false'), zen_get_tax_rate($product_info->fields['products_tax_class_id'])) . '</span>' . ATTRIBUTES_PRICE_DELIMITER_SUFFIX;
        } else {
          //need to remove the <span> tag for selection lists and text boxes
          $originalpricedisplaytext = ATTRIBUTES_PRICE_DELIMITER_PREFIX . $products_options->fields['price_prefix'] . $currencies->display_price(abs(zen_get_attributes_price_final($products_options->fields["products_attributes_id"], 1, '', 'false')), zen_get_tax_rate($product_info->fields['products_tax_class_id'])) . ATTRIBUTES_PRICE_DELIMITER_SUFFIX;
        }
      }

      $products_options_display_price .= $originalpricedisplaytext . $PWA_STOCK_QTY;
      // END "Stock by Attributes" SBA
    }
  }
  
   /*
    * NOTIFY_ATTRIBUTES_MODULE_ATTRIB_SELECTED
    */
  function updateNotifyAttributesModuleAttribSelected(&$callingClass, $notifier, $paramsArray){
    global $products_options_names, $products_options, $selected_attribute, /*$moveSelectedAttribute,*/ $disablebackorder;
    
//       if ($this->_isSBA && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2' && $products_options_names->RecordCount() > 1) || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '3' && $products_options_names->RecordCount() == 1))) {  // Perhaps only certain features need to be bypassed, but for now all mc12345678
    $disablebackorder = null;
    if (!$this->_isSBA || ($this->_isSBA && PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '0' && $products_options_names->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_SELECT_SBA) || ($this->_isSBA && PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2' && $products_options_names->RecordCount() == 1 && $products_options_names->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_SELECT_SBA) || ($this->_isSBA && PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '3' && $products_options_names->RecordCount() > 1)) {
      return;
    }

    //move default selected attribute if attribute is out of stock and check out is not allowed
    if ($this->_moveSelectedAttribute == true && (STOCK_ALLOW_CHECKOUT == 'false' && $products_options->fields['pasqty'] > 0)) {
      $selected_attribute = true;
      $this->_moveSelectedAttribute = false;
    }

    //disable radio and disable default selected
    if ((STOCK_ALLOW_CHECKOUT == 'false' && $products_options->fields['pasqty'] <= 0 && !empty($products_options->fields['pasid']) )
    || ( STOCK_ALLOW_CHECKOUT == 'false' && $products_options->fields['products_quantity'] <= 0 && empty($products_options->fields['pasid']) )
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
          if ($_POST['id'] != '') {
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
            $options_name[] = '<label class="attribsSelect" for="' . 'attrib-' . $products_options_names_fields['products_options_id'] . '">' . $products_options_names_fields ['products_options_name'] . '</label>';
          }
        
        // START "Stock by Attributes" SBA
        $disablebackorder = array();

        //disable default selected if out of stock
        $products_opt = $products_options;
        $products_opt->Move(0);
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
        while (!$products_opt->EOF) {
          if ($prevent_checkout && $products_opt->fields['pasqty'] <= 0 && ($products_opt->fields['attributes_display_only'] && $products_opt->fields['attributes_default'])) {
            $disablebackorder[] = NULL;
          } elseif ($prevent_checkout && $products_opt->fields['pasqty'] <= 0 && /*$products_opt->fields['products_options_values_id'] != $selected_attribute &&*/ $products_opt->fields['attributes_display_only'] && !$products_opt->fields['attributes_default']) { // If the first item is set as disabled and there is no default, then this could cause the product to be incorrectly added to the cart.
            $disablebackorder[] = ' disabled="disabled" ';
          } elseif ($prevent_checkout && $products_opt->fields['pasqty'] <= 0 && $products_opt->fields['attributes_default']) { // If the first item is set as disabled and there is no default, then this could cause the product to be incorrectly added to the cart.
            $disablebackorder[] = NULL;
          } elseif ($prevent_checkout && $products_opt->fields['pasqty'] <= 0) {
            $disablebackorder[] = ' disabled="disabled" ';
          } else {  
            $disablebackorder[] = null;
          }
          $products_opt->MoveNext();
        }
        unset($products_opt);
        unset($prevent_checkout);
        
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
    if (defined('SBA_SHOW_IMAGE_ON_PRODUCT_INFO') && SBA_SHOW_IMAGE_ON_PRODUCT_INFO === '2') {
      $options_attributes_image = array();
    }
  }
   
  /*
   * Function that is activated when NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT is encountered as a notifier.
   */
  //NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT //Line 716
  function updateNotifyOrderProcessingStockDecrementInit(&$callingClass, $notifier, $paramsArray, & $productI, & $i) {
    //global $pwas_class;

    $this->_i = $i;
    $this->_productI = $productI;
    $this->_orderIsSBA = $_SESSION['pwas_class2']->zen_product_is_sba($this->_productI['id']);
    
    if ($this->_orderIsSBA /*&& zen_product_is_sba($this->_productI['id'])*/) { // Only take SBA action on SBA tracked product mc12345678 12-18-2015
      $this->_stock_info = $_SESSION['pwas_class2']->zen_get_sba_stock_attribute_info(zen_get_prid($this->_productI['id']), $this->_productI['attributes'], 'order'); // Sorted comma separated list of the attribute_id.

      // START "Stock by Attributes"
      $attributeList = null;
      $customid = null;
      if(isset($this->_productI['attributes']) and sizeof($this->_productI['attributes']) >0){
        foreach($this->_productI['attributes'] as $attributes){
          $attributeList[] = $attributes['value_id'];
        }
        $customid = $_SESSION['pwas_class2']->zen_get_customid($this->_productI['id'],$attributeList); // Expects that customid would be from a combination product, not individual attributes on a single product.  Should return an array if the values are individual or a single value if all attributes equal a single product.
        $productI['customid'] = $customid;
        $this->_productI['customid'] = $customid;
//      $productI['model'] = (zen_not_null($customid) ? $customid : $productI['model']);
        $this->_productI['model'] = $productI['model'];
      }
    }
    // END "Stock by Attributes"
  }

  /*
   * Function that is activated when NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_BEGIN is encountered as a notifier.
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
      if(isset($this->_productI['attributes']) and sizeof($this->_productI['attributes']) > 0){
        // Need to identify which records in the PWAS table need to be updated to remove stock from
          // them.  Ie. provide a list of attributes and get a list of stock_ids from pwas.
          // Then process that list of stock_ids to decrement based on their impact on stock.  This
          // all should be a consistent application.
        // mc12345678 Identify a list of attributes associated with the product
        $stock_attributes_search = $_SESSION['pwas_class2']->zen_get_sba_stock_attribute(zen_get_prid($this->_productI['id']), $this->_productI['attributes'], 'order');
        $stock_attributes_search_new = $_SESSION['pwas_class2']->zen_get_sba_attribute_info($this->_productI['id'], $this->_productI['attributes'], 'order', 'ids');
          if (isset($stock_attributes_search_new) && $stock_attributes_search_new === false) {
              
          } elseif (isset($stock_attributes_search_new) && is_array($stock_attributes_search_new) && count($stock_attributes_search_new) == 0) {
              
          } elseif (isset($stock_attributes_search_new) && $stock_attributes_search_new && count($stock_attributes_search_new) > 0) {
              foreach ($stock_attributes_search_new as $stock_id) {
                  // @todo: address in PWAS table whether particular variant should be altered with stock quantities.
                  $get_quantity_query = 'SELECT quantity from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id=' . zen_get_prid($this->_productI['id']) . ' and stock_id=' . (int)$stock_id;
                  $attribute_stock_available = $db->Execute($get_quantity_query, false, false, 0, true);
                  if (true) { // Goal here is to identify if the particular attribute/stock item should be affected by a stock change.  If it is not, then this should be false or not performed.
                      $attribute_stock_left_test = $attribute_stock_available->fields['quantity'] - $this->_productI['qty'];
                      $attribute_update_query = 'UPDATE ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' SET quantity="'.$attribute_stock_left_test.'" where products_id=' . zen_get_prid($this->_productI['id']) . ' and stock_id=' . (int)$stock_id;
                      $db->Execute($attribute_update_query, false, false, 0, true);
                      if ($attribute_stock_left_test < $attribute_stock_left) {
                          $this->_attribute_stock_left = min($attribute_stock_left_test, $this->_attribute_stock_left);
                          $attribute_stock_left = $this->_attribute_stock_left;
                      }
                  }
              }
          }
          
/*        $get_quantity_query = 'select quantity from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id="' . zen_get_prid($this->_productI['id']) . '" and stock_attributes="' . $stock_attributes_search . '"';
        $get_quantity = $_SESSION['pwas_class2']->zen_get_sba_attribute_info($this->_productI['id'], $this->_productI['attributes'], 'products', 'stock');
  
        // mc12345678 Identify the stock available from SBA.
        $attribute_stock_available = $db->Execute($get_quantity_query, false, false, 0, true);  
        // mc12345678 Identify the stock remaining for the overall stock by removing the number of the current product from the number available for the attributes_id. 
        $attribute_stock_left = *//*$attribute_stock_available->fields['quantity']*//* $get_quantity - $this->_productI['qty'];
  
        // mc12345678 Update the SBA table to reflect the stock remaining based on the above.
        $attribute_update_query = 'update ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' set quantity='.$attribute_stock_left.' where products_id="' . zen_get_prid($this->_productI['id']) . '" and stock_attributes="' . $stock_attributes_search . '"';
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
     * @param $callingClass
     * @param $notifier
     * @param $paramsArray
     */
    function updateNotifyOrderProcessingStockDecrementEnd(&$callingClass, $notifier, $paramsArray) {
    //Need to modify the email that is going out regarding low-stock.
    //paramsArray is $i at time of development.
    if ($this->_orderIsSBA /*zen_product_is_sba($this->_productI['id'])*/) { // Only take SBA action on SBA tracked product mc12345678 12-18-2015
      if (/*$callingClass->email_low_stock == '' && */$callingClass->doStockDecrement && $this->_stock_values->RecordCount() > 0 && $this->_attribute_stock_left <= STOCK_REORDER_LEVEL) {
        // kuroi: trigger and details for attribute low stock email
        $callingClass->email_low_stock .=  'ID# ' . zen_get_prid($this->_productI['id']) . ', model# ' . $this->_productI['model'] . ', customid ' . $this->_productI['customid'] . ', name ' . $this->_productI['name'] . ', ';
        foreach($this->_productI['attributes'] as $attributes){
          $callingClass->email_low_stock .= $attributes['option'] . ': ' . $attributes['value'] . ', ';
        }
        $callingClass->email_low_stock .= 'Stock: ' . $this->_attribute_stock_left . "\n\n";
      // kuroi: End Stock by Attribute additions
      }
    }
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
    function updateNotifyOrderDuringCreateAddedAttributeLineItem(&$callingClass, $notifier, $paramsArray, $opa_insert_id = NULL) {
    /* First check to see if SBA is installed and if it is then look to see if a value is 
     *  supplied in the stock_id parameter (which should only be populated when a SBA tracked
     *  item is in the order */
//      $_SESSION['paramsArray'] = $paramsArray;
    if ($this->_orderIsSBA && defined('TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK') && zen_not_null($this->_stock_info['stock_id'])) {  
      //Need to validate that order had attributes in it.  If so, then were they tracked by SBA and then add to appropriate table.
/*          `orders_products_attributes_stock_id` INT(11) NOT NULL auto_increment, 
  `orders_products_attributes_id` INT(11) NOT NULL default '0',
  `orders_id` INT(11) NOT NULL default '0', 
  `orders_products_id` INT(11) NOT NULL default '0', 
  `stock_id` INT(11) NOT NULL default '0', 
  `stock_attribute` VARCHAR(255) NULL DEFAULT NULL, 
  `products_prid` TINYTEXT NOT NULL, */
            $sql_data_array = array('orders_products_attributes_id' =>$paramsArray['orders_products_attributes_id'],
                            'orders_id' =>$paramsArray['orders_id'], 
                            'orders_products_id' =>$paramsArray['orders_products_id'], 
                            'stock_id' => $this->_stock_info['stock_id'], 
                            'stock_attribute' => $this->_stock_info['stock_attribute'], 
                            'customid' => $this->_productI['customid'],
                            'products_prid' =>$paramsArray['products_prid']);
    zen_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK, $sql_data_array); //inserts data into the TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK table.

    }
  } //endif NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM - mc12345678

  // NOTIFY_HEADER_END_SHOPPING_CART
    /**
     * @param $callingClass
     * @param $notifier
     * @param $paramsArray
     */
    function updateNotifyHeaderEndShoppingCart(&$callingClass, $notifier, $paramsArray) {
    global $productArray, $flagAnyOutOfStock, $db;
    
    $products = $_SESSION['cart']->get_products();
    
    for ($i = 0, $n = sizeof($productArray); $i < $n; $i++) {
      if (isset($productArray[$i]['attributes']) && is_array($productArray[$i]['attributes']) && sizeof($productArray[$i]['attributes']) > 0 && $_SESSION['pwas_class2']->zen_product_is_sba($productArray[$i]['id'])) {
        $productArray[$i]['attributeImage'] = array();

        if (STOCK_CHECK == 'true') {
          $SBAqtyAvailable = zen_get_products_stock($productArray[$i]['id'], $products[$i]['attributes']); // Quantity of product available with the selected attribute(s).
          $totalQtyAvailable = zen_get_products_stock($productArray[$i]['id']); // Total quantity of product available if all attribute optioned product were added to the cart.
          if ($SBAqtyAvailable - $products[$i]['quantity'] < 0 || $totalQtyAvailable - $_SESSION['cart']->in_cart_mixed($productArray[$i]['id']) < 0) {
            $productArray[$i]['flagStockCheck'] = '<span class="markProductOutOfStock">' . STOCK_MARK_PRODUCT_OUT_OF_STOCK . '</span>';
            $flagAnyOutOfStock = true;
          }
        }
        
        // Ensure that additional stock fields are added at least for SBA product.  If needs to be for all product, then 
        //  This information should be moved outside of the above if statement.  Did not carry over: $products_options_type
        //  nor $productsQty = 0; $productsQty = 0 was previously used to identify "duplicates" and is not needed.
        //  $products_options_type is not yet used for anything else, but was perhaps to address something specific in future
        //  coding.  It will remain off of here for now.
        $productArray[$i]['customid'] = (STOCK_SBA_DISPLAY_CUSTOMID == 'true') ? zen_get_customid($productArray[$i]['id'], $products[$i]['attributes']) : null;
        $productArray[$i]['stockAvailable'] = null;
        $productArray[$i]['lowproductstock'] = false;
        
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

        //LPAD - Return the string argument, left-padded with the specified string
        //example: LPAD(po.products_options_sort_order,11,"0") the field is 11 digits, and is left padded with 0
        if (PRODUCTS_OPTIONS_SORT_ORDER=='0') {
          $options_order_by= ' order by LPAD(popt.products_options_sort_order,11,"0"), popt.products_options_name';
        } else {
          $options_order_by= ' order by popt.products_options_name';
        }

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

        while (!$products_options_names->EOF) {
          $sql = "select distinct pa.attributes_image
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
        if (sizeof($productArray[$i]['attributeImage']) > 0) {
          $productArray[$i]['productsImage'] = (IMAGE_SHOPPING_CART_STATUS == 1 ? zen_image(DIR_WS_IMAGES . $productArray[$i]['attributeImage'][sizeof($productArray[$i]['attributeImage']) - 1], $productArray[$i]['productsName'], IMAGE_SHOPPING_CART_WIDTH, IMAGE_SHOPPING_CART_HEIGHT) : '');
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
        if (sizeof($productArray[$i]['attributeImage']) > 0) {
          $productArray[$i]['productsImage'] = (IMAGE_SHOPPING_CART_STATUS == 1 ? zen_image(DIR_WS_IMAGES . $productArray[$i]['attributeImage'][sizeof($productArray[$i]['attributeImage']) - 1], $productArray[$i]['productsName'], IMAGE_SHOPPING_CART_WIDTH, IMAGE_SHOPPING_CART_HEIGHT) : '');
        }
        unset($productArray[$i]['attributeImage']); */
      }
    }
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
          for ($i = 0, $n = sizeof($products); $i < $n; $i++) {
            unset($attributes);
            if (isset($products[$i]) && is_array($products[$i]) && array_key_exists('attributes', $products[$i]) && isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
              if (zen_product_is_sba($products[$i]['id'])) {
                $attributes = $products[$i]['attributes'];
              } else {
                $attributes = null;
              }
            } else {
              $attributes = null;
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
      global $products_options_fields;
      $this->updateNotifyAttributesModuleStartOptionsLoop($callingClass, $notifier, $paramsArray, $products_options_fields);
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
    if ($notifier == 'NOTIFY_ORDER_PROCESSING_ATTRIBUTES_BEGIN') {
      
//      $stock_attribute = zen_get_sba_stock_attribute(zen_get_prid($this->products[$i]['id']), $this->products[$i]['attributes'], 'order');
//      $stock_id = zen_get_sba_stock_attribute_id(zen_get_prid($this->products[$i]['id']), $this->products[$i]['attributes'], 'order'); //true; // Need to use the $stock_attribute/attributes to obtain the attribute id.
    }

    if ($notifier == 'NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_BEGIN'){
      global $attribute_stock_left;

    /*
     * Function that is activated when NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT is encountered as a notifier.
     */
    //NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT //Line 716
  //  function updateNotifyOrderProcessingStockDecrementInit(&$callingClass, $notifier, $paramsArray, & $productI, & $i) {
      $i = $paramsArray['i'];
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
      $this->updateNotifyOrderProcessingStockDecrementEnd($callingClass, $notifier, $paramsArray);
    }
    
    if ($notifier == 'NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM') {
      $this->updateNotifyOrderDuringCreateAddedAttributeLineItem($callingClass, $notifier, $paramsArray, $paramsArray['orders_products_attributes_id']);
    } //endif NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM - mc12345678
    
    if ($notifier == 'NOTIFY_HEADER_START_CHECKOUT_SHIPPING') {
      $this->updateNotifyHeaderStartCheckoutShipping($callingClass, $notifier, $paramsArray);
    } //endif NOTIFY_HEADER_START_CHECKOUT_SHIPPING
  } //end update function - mc12345678
} //end class - mc12345678

