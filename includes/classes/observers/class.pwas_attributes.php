<?php

/**
 * Description of class.pwas_attributes: This class is used to support processing the attributes.php file related to Stock By Attributes.  This way reduces the modifications of the includes/modules/attributes.php file to nothing in Zen Cart 1.5.7 and above.
 *
 * @property array() $_productI This is the specific product that is being worked on in the order file.
 * @property integer $_i This is the identifier of which product is being worked on in the order file
 * @property array $_stock_info This contains information related to the SBA table associated with the product being worked on in the order file.
 * @property double $_attribute_stock_left This is the a referenced value that relates to the SBA tracked quantity that remain.
 * @property array $_stock_values The results of querying on the database for the stock remaining and other associated information.
 * @author mc12345678
 *
 * Stock by Attributes 5.0.0  22-02-02 mc12345678
 */
if (!defined('PWA_DISPLAY_CUSTOMID')) {
  define('PWA_DISPLAY_CUSTOMID', 'rightstock'); // 'leftall' (first in text), 'leftstock' (to the left of the stock quantity), 'rightstock' (default - to the right of the stock before other text), 'rightall' (furthest right item), '' (don't display customid regardless of admin setting to display)
}

class attributes_products_with_attributes_stock extends base {

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
  
  private $products_options_names_fields;
  
//  private $_attrib_grid;
  
  private $_noread_done = false;
  
  private $_moveSelectedAttribute;
  
  private $_options_menu_images;
  
  private $_products_options_fields;

  // Value of the customid to add to text, primarily for the SBA Select List (Dropdown) Basic type of dropdown
  private $customid;

  // boolean to identify whether to add the customid or not, primarily for the SBA Select List (Dropdown) Basic type of dropdown
  private $try_customid;
  
  private $attributeDetailsArrayforJson;
  
  private $data_properties;
  
  private $field_disabled;
  
  /*
   * This is the observer for the includes/modules/YOUR_TEMPLATE/attributes.php file to support Stock By Attributes when the product is being displayed. This observer is intended to support using the existing ZC 1.5.7+ version of the file without edits so that an additional copy in `YOUR_TEMPLATE` is not necessary.
   */
  function __construct() {
    
    $attachNotifier = array();
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_START_OPTION';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_START_OPTIONS_LOOP';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_SALE_MAKER_DISPLAY_PRICE_PERCENTAGE'; // Added by SBA for pre-ZC 1.5.7
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_SALEMAKER_DISPLAY_PRICE_PERCENTAGE';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_ORIGINAL_PRICE';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_ATTRIB_SELECTED'; // Added by SBA for pre-ZC 1.5.7
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_RADIO_SELECTED'; // Added for ZC 1.5.7
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_CHECKBOX_SELECTED'; // Added for ZC 1.5.7
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_DEFAULT_SWITCH';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_OPTION_BUILT';
  
    $this->attach($this, $attachNotifier);

    $this->_products_options_names_current = 0; // Initialize this variable to 0.
  }  
  
  /*
   * NOTIFY_ATTRIBUTES_MODULE_START_OPTION
   * Added by SBA for ZC 1.5.0 through 1.5.4.
   * ZC 1.5.5, ZC 1.5.6,
   * ZC 1.5.7: $zco_notifier->notify('NOTIFY_ATTRIBUTES_MODULE_START_OPTION', $products_options_names->fields);
   */
   function updateNotifyAttributesModuleStartOption(&$callingClass, $notifier, $products_options_names_fields) {
     if (empty($_SESSION['pwas_class2'])) return;

     global $db, $sql,
     $products_options_array,
     $is_SBA_product, $order_by, $products_options;
     
     $this->_options_menu_images = array();
     $this->_moveSelectedAttribute = false;
     if (!isset($products_options_array)) {
       $products_options_array = array();
     }
//     $options_attributes_image = array();
     // Could do the calculation here the first time set a variable above as part of the class and then reuse that... instead of the modification to the attributes file...
     if (empty($this->_products_options_names_count)) {
       $this->_products_options_names_count = $GLOBALS['products_options_names']->RecordCount();
     }

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
   * Added by SBA for ZC 1.5.0 through 1.5.4.
   * ZC 1.5.5 &
   * ZC 1.5.6: $zco_notifier->notify('NOTIFY_ATTRIBUTES_MODULE_START_OPTIONS_LOOP', $i++, $products_options->fields);
   * ZC 1.5.7: $zco_notifier->notify('NOTIFY_ATTRIBUTES_MODULE_START_OPTIONS_LOOP', $i++, $products_options->fields, $products_options_names->fields, $data_properties, $field_disabled, $attributeDetailsArrayForJson);
   */
  function updateNotifyAttributesModuleStartOptionsLoop(&$callingClass, $notifier, $i, &$products_options_fields, &$products_options_names_fields, &$data_properties, &$field_disabled, &$attributeDetailsArrayforJson) {
    global $db, $products_options_array,
           $PWA_STOCK_QTY;
    if (is_null($products_options_names_fields)) {
      $products_options_names_fields = $GLOBALS['products_options_names']->fields;
    }

    if (is_null($field_disabled)) {
      global $disablebackorder;
      $disablebackorder = '';
    }

    $this->_products_options_names_current++;
    
    $this->attributeDetailsArrayforJson = $attributeDetailsArrayforJson;
    
    $type_array = array();
    if (defined('PRODUCTS_OPTIONS_TYPE_SELECT_SBA')) {
      $type_array = array(PRODUCTS_OPTIONS_TYPE_SELECT_SBA);
    }

    $type_array = array_merge($type_array, array(
      PRODUCTS_OPTIONS_TYPE_RADIO,
      PRODUCTS_OPTIONS_TYPE_CHECKBOX,
      PRODUCTS_OPTIONS_TYPE_FILE,
      PRODUCTS_OPTIONS_TYPE_TEXT,
      PRODUCTS_OPTIONS_TYPE_SELECT,
    ));

    $products_options_type = $products_options_names_fields['products_options_type'];

    $this->for_attributes =  (
      in_array($products_options_type, $type_array)
      || (
        (
          PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '0'
          && $products_options_type == PRODUCTS_OPTIONS_TYPE_SELECT_SBA
        )
        || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1'
        || (
          PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2'
          && $this->_products_options_names_count > 1
        )
        || (
          PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '3'
          && $this->_products_options_names_count == 1
        )
      )
    );

    if ($this->_isSBA && $this->for_attributes) {  // Perhaps only certain features need to be bypassed, but for now all mc12345678
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
      if ((!isset($products_options_fields['pasqty']) || $products_options_fields['pasqty'] <= 0) && STOCK_CHECK == 'true' && isset($products_options_fields['pasid']) && $products_options_fields['pasid'] > 0) {
        //test, only applicable to products with-out the display-only attribute set
        if (empty($products_options_DISPLAYONLY->fields['attributes_display_only'])) {
          $products_options_fields['products_options_values_name'] = $products_options_fields['products_options_values_name'] . PWA_OUT_OF_STOCK;
          // $i in includes/modules/YOUR_TEMPLATE/attributes.php is post incremented ($i++) meaning it is sent here with the 
          //   current value, but upon return will be incremented by 1.  Therefore within this function it should be considered as 
          //   the value that attributes.php was seeing just before the notifier.
          $products_options_array[$i] = array_merge(
            $products_options_array[$i],
            array(
              'id' => $products_options_fields['products_options_values_id'],
              'text' => $products_options_fields['products_options_values_name'],
            )
          ); // 2017-07-13 mc12345678 added array_merge to preserve other modifications that may have been made for this array instead of replacing them in their entirety and possibly losing the other data.
        }
      }

      $show_custom_id_flag = isset($products_options_fields['customid'])
        && zen_not_null($products_options_fields['customid'])
        && (
          !defined('ATTRIBUTES_SBA_DISPLAY_CUSTOMID')
          || (
            STOCK_SBA_DISPLAY_CUSTOMID == 'true'
            && ATTRIBUTES_SBA_DISPLAY_CUSTOMID == '1'
          )
          || ATTRIBUTES_SBA_DISPLAY_CUSTOMID == '2'
          || (
            ATTRIBUTES_SBA_DISPLAY_CUSTOMID == '3'
            && $products_options_names_count > 1
          )
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
      if ($products_options_type != PRODUCTS_OPTIONS_TYPE_TEXT) {
        if ($products_options_type != PRODUCTS_OPTIONS_TYPE_FILE) {
          if ($products_options_type != PRODUCTS_OPTIONS_TYPE_READONLY) {
            /*if ($products_options_type == PRODUCTS_OPTIONS_TYPE_SELECT_SBA)*/ {

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
              } elseif (STOCK_SHOW_ATTRIB_LEVEL_STOCK == 'true' && (empty($products_options_fields['pasqty']) || ($products_options_fields['pasqty'] < 0)) && empty($products_options_fields['pasid'])) {
                //test, only applicable to products with-out the display-only attribute set
                if (empty($products_options_DISPLAYONLY->fields['attributes_display_only'])) {
                  //use the qty from the product, unless it is 0, then set to out of stock.
                  if (empty($this->_products_options_names_count)) {
                    if ($products_options_fields['products_quantity'] > 0) {
                      $PWA_STOCK_QTY = PWA_STOCK_QTY . $products_options_fields['products_quantity'] . ' ';
                    } else {
                      $products_options_fields['products_options_values_name'] = $products_options_fields['products_options_values_name'] . PWA_OUT_OF_STOCK;
                      $products_options_array[$i] = array_merge(
                        $products_options_array[$i],
                        array(
                          'id' =>$products_options_fields['products_options_values_id'],
                          'text' => $products_options_fields['products_options_values_name'],
                        )
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
      $picture = $db->Execute('SELECT p.products_image FROM ' . TABLE_PRODUCTS . ' p WHERE products_id = ' . (int)$_GET['products_id']);

      $this->_options_menu_images['product_image'] = '';
      if ($picture->RecordCount() > 0) {
        $this->_options_menu_images['product_image'] .= DIR_WS_IMAGES . $picture->fields['products_image'];
      }
      // END "Stock by Attributes" SBA
    } // End if _isSBA

    $this->_products_options_fields = $products_options_fields;
    $this->products_options_names_fields = $products_options_names_fields;
    $this->data_properties = $data_properties;
    
    if ($this->_isSBA
      && !(
        (
          PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '0'
          && (
            $products_options_type != PRODUCTS_OPTIONS_TYPE_SELECT_SBA
            && $products_options_type != PRODUCTS_OPTIONS_TYPE_RADIO
          )
        )
        || (
          PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2'
          && $this->_products_options_names_count == 1
          && (
            $products_options_type != PRODUCTS_OPTIONS_TYPE_SELECT_SBA
            && $products_options_type != PRODUCTS_OPTIONS_TYPE_RADIO
          )
        )
        || (
          PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '3'
          && $this->_products_options_names_count > 1
        )
      )
    ) {
      //disable radio buttons and sba select
      if (
        (
          STOCK_ALLOW_CHECKOUT == 'false'
          && (
            empty($products_options_fields['pasqty'])
            || $products_options_fields['pasqty'] < 0
          )
          && !empty($products_options_fields['pasid'])
        )
        || (
          STOCK_ALLOW_CHECKOUT == 'false'
          && (
            empty($products_options_fields['products_quantity'])
            || $products_options_fields['products_quantity'] < 0
          )
          && empty($products_options_fields['pasid'])
        )
      ) { //|| $products_options_READONLY->fields['attributes_display_only'] == 1
        $field_disabled = $disablebackorder = ' disabled="disabled" ';
      }
    }
    
    $this->field_disabled = $field_disabled;
  }

  /*
   * NOTIFY_ATTRIBUTES_MODULE_SALE_MAKER_DISPLAY_PRICE_PERCENTAGE
   * ZC 1.5.x:  $zco_notifier->notify('NOTIFY_ATTRIBUTES_MODULE_SALE_MAKER_DISPLAY_PRICE_PERCENTAGE');
   * ZC 1.5.7:  $zco_notifier->notify('NOTIFY_ATTRIBUTES_MODULE_SALEMAKER_DISPLAY_PRICE_PERCENTAGE', $products_options->fields, $product_info->fields, $products_options_display_price, $data_properties);
   */
  function updateNotifyAttributesModuleSaleMakerDisplayPricePercentage(&$callingClass, $notifier, $products_options_fields, &$product_info_fields, &$products_options_display_price, &$data_properties) {

    // attribute types to which this applies
    $group_price_modified = array(
      PRODUCTS_OPTIONS_TYPE_RADIO,
      PRODUCTS_OPTIONS_TYPE_CHECKBOX,
    );
    
    // Perform an early escape if there is nothing to be done here.
    if (!in_array($this->products_options_names_fields['products_options_type'], $group_price_modified)) {
      return;
    }

    global $currencies;
    
    // Backwards compatibility with attributes.php file
    if (is_null($products_options_display_price)) {
      global $products_options_display_price;
    }
    
    // Backwards compatibility with attributes.php file
    if (empty($products_options_fields)) {
      $products_options_fields = $GLOBALS['products_options']->fields;
    }
    
    // Backwards compatibility with attributes.php file
    if (is_null($product_info_fields)) {
      $product_info_fields = $GLOBALS['product_info']->fields;
    }
    
    //use this if a PRODUCTS_OPTIONS_TYPE_RADIO or PRODUCTS_OPTIONS_TYPE_CHECKBOX
    //class="productSpecialPrice" can be used in a CSS file to control the text properties, not compatable with selection lists
    $products_options_display_price = ATTRIBUTES_PRICE_DELIMITER_PREFIX . '<span class="productSpecialPrice">' . $products_options_fields['price_prefix'] . $currencies->display_price($GLOBALS['new_attributes_price'], zen_get_tax_rate($product_info_fields['products_tax_class_id'])) . '</span>' . ATTRIBUTES_PRICE_DELIMITER_SUFFIX;

  }

   /*
    * NOTIFY_ATTRIBUTES_MODULE_ORIGINAL_PRICE
    * SBA added: $zco_notifier->notify('NOTIFY_ATTRIBUTES_MODULE_ORIGINAL_PRICE');
    * As of ZC 1.5.7:
    * $zco_notifier->notify('NOTIFY_ATTRIBUTES_MODULE_ORIGINAL_PRICE', $products_options->fields, $products_options_array, $products_options_display_price, $data_properties);
    */
  function updateNotifyAttributesModuleOriginalPrice(&$callingClass, $notifier, $products_options_fields, &$products_options_array, &$products_options_display_price, &$data_properties) {

    if (empty($_SESSION['pwas_class2'])) return;

    // If not even an SBA selection, then don't do anything with it.
    if (!$this->_isSBA) {
      return;
    }

    if (!$this->for_attributes) {
      return;
    }

    global $currencies, $new_attributes_price, $product_info, $PWA_STOCK_QTY;

    if (is_null($products_options_display_price)) {
      global $products_options_display_price;
    }
    if (empty($products_options_fields)) {
      $products_options_fields = $GLOBALS['products_options']->fields;
      $products_options_array = null; // @TODO: Set this properly for further use if needed.  Not needed in this section for time being.
    }
    
    
    // attribute types to which this applies
    $group_price_modified = array(
      PRODUCTS_OPTIONS_TYPE_RADIO,
      PRODUCTS_OPTIONS_TYPE_CHECKBOX,
    );
    
    $products_options_type = 0;
    
    if (isset($GLOBALS['inputFieldId']) && isset($this->attributeDetailsArrayForJson[$GLOBALS['inputFieldId']]['products_options_type'])) {
      $products_options_type = $this->attributeDetailsArrayForJson[$GLOBALS['inputFieldId']]['products_options_type'];
    }
    
    if (empty($this->attributeDetailsArrayForJson)) {
      $products_options_type = $this->products_options_names_fields['products_options_type'];
    }
    
    
    // Perhaps only certain features need to be bypassed, but for now all mc12345678
    // START "Stock by Attributes" SBA added original price for display, and some formatting
    $originalpricedisplaytext = '';

    if (!empty($_SESSION['pwas_class2']->zgapf)) {
        // Use the latest function for determining the attribute's final price
        //   This requires/uses 4 parameters to internally determine the price of the attribute
      global $products_price_is_priced_by_attributes; // This likely could be looked up again instead of consistently pulled from the existing condition.
      $attributes_price_final = zen_get_attributes_price_final($products_options_fields['products_attributes_id'], 1, '', 'false', $products_price_is_priced_by_attributes);
    } else {
        // This is the old method of performing attribute price determination which
        //   has been found to be prone to discrepancies when sales, specials 
        //   and/or priced-by-attributes are involved
      $attributes_price_final = zen_get_attributes_price_final($products_options_fields['products_attributes_id'], 1, '', 'false');
      $attributes_price_final = zen_get_discount_calc((int)$_GET['products_id'], true, $attributes_price_final);
    }
//      if (STOCK_SHOW_ORIGINAL_PRICE_STRUCK == 'true' && !($attributes_price_final == $new_attributes_price || ($attributes_price_final == -$new_attributes_price && ((int)($products_options->fields['price_prefix'] . "1") * $products_options->fields['options_values_price']) < 0)) ) {
    if (STOCK_SHOW_ORIGINAL_PRICE_STRUCK == 'true' && !($products_options_fields['attributes_display_only'] && $products_options_fields['attributes_default'] && !$products_options_fields['products_options_sort_order']) && ($new_attributes_price != $products_options_fields['options_values_price']) && (($attributes_price_final == $new_attributes_price) || (($attributes_price_final == -$new_attributes_price) && ((int)($products_options_fields['price_prefix'] . "1") * $products_options_fields['options_values_price']) < 0)) ) {
      //Original price struck through
      if (in_array($products_options_type, $group_price_modified)) {
        //use this if a PRODUCTS_OPTIONS_TYPE_RADIO or PRODUCTS_OPTIONS_TYPE_CHECKBOX
        //class="normalprice" can be used in a CSS file to control the text properties, not compatable with selection lists
//        $originalpricedisplaytext = ATTRIBUTES_PRICE_DELIMITER_PREFIX . '<span class="normalprice">' . $products_options_fields['price_prefix'] . $currencies->display_price($attributes_price_final, zen_get_tax_rate($product_info->fields['products_tax_class_id'])) . '</span>' . ATTRIBUTES_PRICE_DELIMITER_SUFFIX;
        $originalpricedisplaytext = ATTRIBUTES_PRICE_DELIMITER_PREFIX . '<span class="normalprice">' . $products_options_fields['price_prefix'] . $currencies->display_price($products_options_fields['options_values_price'], zen_get_tax_rate($product_info->fields['products_tax_class_id'])) . '</span>' . ATTRIBUTES_PRICE_DELIMITER_SUFFIX;
      } else {
        //need to remove the <span> tag for selection lists and text boxes
//        $originalpricedisplaytext = ATTRIBUTES_PRICE_DELIMITER_PREFIX . $products_options_fields['price_prefix'] . $currencies->display_price(abs($attributes_price_final), zen_get_tax_rate($product_info->fields['products_tax_class_id'])) . ATTRIBUTES_PRICE_DELIMITER_SUFFIX;
        $originalpricedisplaytext = ATTRIBUTES_PRICE_DELIMITER_PREFIX . $products_options_fields['price_prefix'] . $currencies->display_price(abs($products_options_fields['options_values_price']), zen_get_tax_rate($product_info->fields['products_tax_class_id'])) . ATTRIBUTES_PRICE_DELIMITER_SUFFIX;
      }
    }

    if ($this->try_customid && PWA_DISPLAY_CUSTOMID == 'rightall') {
      $products_options_display_price .= $originalpricedisplaytext . $this->customid;
    }
    // END "Stock by Attributes" SBA
  }
  
   /*
    * NOTIFY_ATTRIBUTES_MODULE_ATTRIB_SELECTED
    * ZC 1.5.x: $zco_notifier->notify('NOTIFY_ATTRIBUTES_MODULE_ATTRIB_SELECTED');
    * ZC 1.5.7: Called something else, one for radio, one for checkboxes
    * Disable code has been moved up in processing
    */
  function updateNotifyAttributesModuleAttribSelected(&$callingClass, $notifier, $paramsArray) {

//       if ($this->_isSBA && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2' && $products_options_names->RecordCount() > 1) || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '3' && $products_options_names->RecordCount() == 1))) {  // Perhaps only certain features need to be bypassed, but for now all mc12345678
    if ($this->dontProcess()) {
      return;
    }

    global $products_options, $selected_attribute;
    
    //move default selected attribute if attribute is out of stock and check out is not allowed
    if ($this->_moveSelectedAttribute == true && (STOCK_ALLOW_CHECKOUT == 'false' && $products_options->fields['pasqty'] > 0)) {
      $selected_attribute = true;
      $this->_moveSelectedAttribute = false;
    }

    //disable radio and disable default selected
    if ((STOCK_ALLOW_CHECKOUT == 'false' && (empty($products_options->fields['pasqty']) || $products_options->fields['pasqty'] < 0) && !empty($products_options->fields['pasid']) )
    || ( STOCK_ALLOW_CHECKOUT == 'false' && (empty($products_options->fields['products_quantity']) || $products_options->fields['products_quantity'] < 0) && empty($products_options->fields['pasid']) )
    ) {//|| $products_options_READONLY->fields['attributes_display_only'] == 1
      if ($selected_attribute == true) {
        $selected_attribute = false;
        $this->_moveSelectedAttribute = true;
      }
    }
    // END "Stock by Attributes" SBA
     
  }

  /**
   *  ZC 1.5.7:
   *  $zco_notifier->notify('NOTIFY_ATTRIBUTES_MODULE_RADIO_SELECTED', $products_options->fields, $data_properties);
   *
   **/
  function updateNotifyAttributesModuleRadioSelected(&$callingClass, $notifier, $products_options_fields, &$data_properties) {
    
    if ($this->dontProcess()) {
      return;
    }

    global $selected_attribute;

    //move default selected attribute if attribute is out of stock and check out is not allowed
    if ($this->_moveSelectedAttribute == true && (STOCK_ALLOW_CHECKOUT == 'false' && $products_options_fields['pasqty'] > 0)) {
      $selected_attribute = true;
      $this->_moveSelectedAttribute = false;
    }
    //disable radio and disable default selected
    if (STOCK_ALLOW_CHECKOUT == 'false' && ( ((empty($products_options_fields['pasqty']) || $products_options_fields['pasqty'] <= 0) && !empty($products_options_fields['pasid']) )
    || ((empty($products_options_fields['products_quantity']) || $products_options_fields['products_quantity'] <= 0) && empty($products_options_fields['pasid'])) )
    ) {//|| $products_options_READONLY->fields['attributes_display_only'] == 1
      if ($selected_attribute == true) {
        $selected_attribute = false;
        $this->_moveSelectedAttribute = true;
      }
    }
    // END "Stock by Attributes" SBA
     
  }

  /**
   *
   * $zco_notifier->notify('NOTIFY_ATTRIBUTES_MODULE_CHECKBOX_SELECTED', $products_options->fields, $data_properties);
   **/
  function updateNotifyAttributesModuleCheckboxSelected(&$callingClass, $notifier, $products_options_fields, &$data_properties) {
    
//       if ($this->_isSBA && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2' && $products_options_names->RecordCount() > 1) || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '3' && $products_options_names->RecordCount() == 1))) {  // Perhaps only certain features need to be bypassed, but for now all mc12345678
    if ($this->dontProcess()) {
      return;
    }
    global $selected_attribute;
    
    //move default selected attribute if attribute is out of stock and check out is not allowed
    if ($this->_moveSelectedAttribute == true && (STOCK_ALLOW_CHECKOUT == 'false' && $products_options_fields['pasqty'] > 0)) {
      $selected_attribute = true;
      $this->_moveSelectedAttribute = false;
    }

    //disable radio and disable default selected
    if (STOCK_ALLOW_CHECKOUT == 'false' && ((empty($products_options_fields['pasqty']) || $products_options_fields['pasqty'] < 0) && !empty($products_options_fields['pasid']) )
    || ((empty($products_options_fields['products_quantity']) || $products_options_fields['products_quantity'] < 0) && empty($products_options_fields['pasid']) )
    ) {//|| $products_options_READONLY->fields['attributes_display_only'] == 1
      if ($selected_attribute == true) {
        $selected_attribute = false;
        $this->_moveSelectedAttribute = true;
      }
    }
    // END "Stock by Attributes" SBA
     
  }


  /*
   * 'NOTIFY_ATTRIBUTES_MODULE_DEFAULT_SWITCH';
   */
  function updateNotifyAttributesModuleDefaultSwitch(&$callingClass, $notifier, $products_options_names_fields, &$options_name, &$options_menu, &$options_comment, &$options_comment_position, &$options_html_id) {

    if (empty($_SESSION['pwas_class2'])) return;

          switch (true) {
      case ($products_options_names_fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_SELECT_SBA): // SBA Select List (Dropdown) Basic
        global $selected_attribute, $show_attributes_qty_prices_icon, $products_options_array, $disablebackorder/*, $options_menu_images*/, $products_options;
        
        // normal dropdown "SELECT LIST" menu display
        $prod_id = $_GET['products_id'];
        if (isset($_SESSION['cart']->contents[$prod_id]['attributes'][$products_options_names_fields['products_options_id']])) {
          $selected_attribute = $_SESSION['cart']->contents[$prod_id]['attributes'][$products_options_names_fields['products_options_id']];
        } else {
          // use customer-selected values
          if (!empty($_POST['id']) && is_array($_POST['id'])) {
            foreach ($_POST['id'] as $key => $value) {
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
          //   This only really works for single option name product or if each option name/option value is split out. For combined attributes, this
          //   does not properly identify the presence/existence of the variant.  That aspect probably needs to be controlled from the front end of
          //   the store via javascript/jQuery and/or upon selection of the combination without the extra screen modification.
          $isDefined = !empty($_SESSION['pwas_class2']->zen_get_sba_attribute_info($prod_id, array($products_opt->fields['options_id'] => $products_opt->fields['options_values_id']), 'product', 'ids'));
          // If the item is display only then disable it from selection. display_only with default is handled above.
          if ($products_opt->fields['attributes_display_only'] && !$products_opt->fields['attributes_default']) {
            $disablebackorder[] = ' disabled="disabled" ';
          } elseif ($isDefined && $products_opt->fields['attributes_default']) {
            // If the option name/option value combination has its own stock_id and it is/was a default, then because there is insufficient stock, disable it.
            //   Also, because it was set as a default, then this selects/suggests another default be selected so that at least one is chosen.
            $disablebackorder[] = ' disabled="disabled" ';
            $move2next = true;
          } elseif ($isDefined) {
            // By this point, it is not display only, it is not a default, it is identified as a known variant, it is out of stock and not
            //   permitted to be added to the cart, so disable it.
            $disablebackorder[] = ' disabled="disabled" ';
          } elseif (zen_get_products_stock(array('products_id' => $prod_id, 'attributes' => array($products_opt->fields['options_id'] => $products_opt->fields['options_values_id']))) <= 0) {
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
  
  function catalogCustomID(&$productArray, &$customid) {
    if (!isset($customid) || !is_array($customid)) {
      $customid = array();
    }
    
    foreach ($productArray as $i => &$productsI) {
      $customid[$i] = '';
      
      if (!isset($productsI['customid']) || !is_array($productsI['customid']) || count($productsI['customid']) == 0) {
        continue;
      }

      // Add what is specifically the customid if it is desired.
      if (STOCK_SBA_DISPLAY_CUSTOMID == 'true') {
        // assign the customid to this array if it is different than the model.
        $customid[$i] .= (zen_not_null($productsI['customid']['value']) && $productsI['model'] != $productsI['customid']['value'] 
                ? '<br />(' . $productsI['customid']['value'] . ') '
                : '');
      }
      // Add products_model designation regardless the desire to show customid
      $customid[$i] .= (zen_not_null($productsI['model'])
            ? '<br />' . PWA_CUSTOMID_NAME . '(' . $productsI['model'] . ')'
            : '');

      // if there is information to be shown from either, then append it with a prefixing space.
      if (zen_not_null($customid[$i])) {
        $productArray[$i]['name'] .= ' ' . $customid[$i];
      }

      // If there is no desire for the customid, then don't need the remaining content below.
      if (STOCK_SBA_DISPLAY_CUSTOMID == 'false') {
        continue;
      }

      // Below only impacts/addresses product that have a customid marked as multi so that the individual customid info
      // Can be displayed adjacent to the associated attribute.
      if ($productsI['customid']['type'] !== 'multi' && !($productsI['customid']['type'] === 'single' && count($productsI['attributes']) == 1)) {
        continue;
      }

      // If there are no attributes, then the foreach below it should not be performed.
      if (!isset($productsI['attributes']) || !is_array($productsI['attributes'])) {
        continue;
      }

      // Loop through each attribute to add some form of customid to the attributes array, either blank or additional text.
      foreach ($productsI['attributes'] as $attrkey => &$attrval) {
        // Skip "adding" the customid if it doesn't exist or if it does it basically has no text.
        if (!isset($attrval['customid']) || !zen_not_null($attrval['customid'])) {
          // Ensures that variable is initiated to minimize need to check for presence of the key.
          $attrval['customid'] = '';
          continue;
        }

        $attrval['customid'] = '(' . $attrval['customid'] . ')';
//        $attrval['option'] = $attrval['customid'] . ' ' . $attrval['option']; // Place customid before the option name
        $attrval['value'] .= /*PHP_EOL*/ ' - ' . $attrval['customid']; // Place the customid after the attribute value.
      }
      unset($attrval); // Prevent overwriting old data because modifying a referenced value.
    }
    unset($productsI); // Prevent overwriting old data because modifying a referenced value.
  }

  function dontProcess() {
      return (
          !$this->_isSBA 
              || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '0' && $this->products_options_names_fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_SELECT_SBA) 
              || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2' && $this->_products_options_names_count == 1 && $this->products_options_names_fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_SELECT_SBA) 
              || (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '3' && $this->_products_options_names_count > 1)
      );
  }

  
  /*
   * Generic function that is activated when any notifier identified in the observer is called but is not found in one of the above previous specific update functions is encountered as a notifier.
   */
  function update(&$callingClass, $notifier, $paramsArray) {
  //global $db;
    if ($notifier == 'NOTIFY_ATTRIBUTES_MODULE_SALE_MAKER_DISPLAY_PRICE_PERCENTAGE' || $notifier == 'NOTIFY_ATTRIBUTES_MODULE_SALEMAKER_DISPLAY_PRICE_PERCENTAGE') {
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
    
  } //end update function - mc12345678
} //end class - mc12345678
