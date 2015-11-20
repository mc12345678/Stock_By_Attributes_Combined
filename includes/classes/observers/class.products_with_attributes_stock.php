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
  
  private $_products_options_names_count;

  
  /*
   * This is the observer for the includes/classes/order.php file to support Stock By Attributes when the order is being processed at the end of the purchase.
   */
  function products_with_attributes_stock() {
		//global $zco_notifier;
    
    $attachNotifier = array();
    $attachNotifier[] = 'NOTIFY_ORDER_DURING_CREATE_ADDED_PRODUCT_LINE_ITEM';
    $attachNotifier[] = 'NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM';
    $attachNotifier[] = 'NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT';
    $attachNotifier[] = 'NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_BEGIN';
    $attachNotifier[] = 'NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_END';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_OPTIONS_SQL';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_ATTRIB_SELECTED';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_ORIGINAL_PRICE';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULES_OPTIONS_VALUES_SET';
    $attachNotifier[] = 'NOTIFY_ATTRIBUTES_MODULE_SALE_MAKER_DISPLAY_PRICE_PERCENTAGE';
	
//		$zco_notifier->attach($this, $attachNotifier); 
		$this->attach($this, $attachNotifier); 
	}	

  /*
   * NOTIFY_ATTRIBUTES_MODULE_SALE_MAKER_DISPLAY_PRICE_PERCENTAGE
   */
  function updateNotifyAttributesModuleSaleMakerDisplayPricePercentage(&$callingClass, $notifier, $paramsArray){
    global $products_option_names, $products_options_display_price, $products_options, $currencies, $new_attributes_price, $product_info;
    
    if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_RADIO || $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_CHECKBOX) {
      //use this if a PRODUCTS_OPTIONS_TYPE_RADIO or PRODUCTS_OPTIONS_TYPE_CHECKBOX
      //class="productSpecialPrice" can be used in a CSS file to control the text properties, not compatable with selection lists
      $products_options_display_price = ATTRIBUTES_PRICE_DELIMITER_PREFIX . '<span class="productSpecialPrice">' . $products_options->fields['price_prefix'] . $currencies->display_price($new_attributes_price, zen_get_tax_rate($product_info->fields['products_tax_class_id'])) . '</span>' . ATTRIBUTES_PRICE_DELIMITER_SUFFIX;
    }

  }
  
  /*
   * NOTIFY_ATTRIBUTES_MODULE_OPTIONS_SQL
   */
   function updateNotifyAttributesModuleOptionsSQL(&$callingClass, $notifier, $paramsArray) {
     global $db, $sql, $options_menu_images, $moveSelectedAttribute, $products_options_array, $options_attributes_image, $products_options_names, $products_options_names_count, $stock, $is_SBA_product;
     
     $options_menu_images = array();
     $moveSelectedAttribute = false;
     $products_options_array = array();
     $options_attributes_image = array();
     // Could do the calculation here the first time set a variable above as part of the class and then reuse that... instead of the modification to the attributes file...
     if (!zen_not_null($this->_products_options_names_count)) {
       $this->_products_options_names_count = $products_options_names->RecordCount();
     }
//     $products_options_names_count = $products_options_names->RecordCount();

     if (zen_product_is_sba($_GET['products_id'])) {
       $this->_isSBA = true;
     } else {
       $this->_isSBA = false;
     }
     
//     $stock->_isSBA = $this->_isSBA;
     $is_SBA_product = $this->_isSBA;
     
     if ($this->_isSBA) {
       $sql = "select distinct pov.products_options_values_id,
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
     }
   }

   /*
    * NOTIFY_ATTRIBUTES_MODULES_OPTIONS_VALUES_SET
    */
   function updateNotifyAttributesModulesOptionsValuesSet (&$callingClass, $notifier, $paramsArray) {
     global $db, $options_menu_images, $products_options, $products_options_names, $PWA_STOCK_QTY;
     
     // START "Stock by Attributes"  SBA
      //used to find if an attribute is display-only
      $sqlDO = "select pa.attributes_display_only
                    from " . TABLE_PRODUCTS_OPTIONS . " po
                    left join " . TABLE_PRODUCTS_ATTRIBUTES . " pa on (pa.options_id = po.products_options_id)
                    where pa.products_id=:products_id:
                     and pa.products_attributes_id = :products_attributes_id: ";
      $sqlDO = $db->bindVars($sqlDO, ':products_id:', $_GET['products_id'], 'integer');
      $sqlDO = $db->bindVars($sqlDO, ':products_attributes_id:', $products_options->fields['products_attributes_id'], 'integer');
      $products_options_DISPLAYONLY = $db->Execute($sqlDO);

      //echo 'ID: ' . $products_options->fields["products_attributes_id"] . ' Stock ID: ' . $products_options->fields['pasid'] . ' QTY: ' . $products_options->fields['pasqty'] . ' Custom ID: ' . $products_options->fields['customid'] . '<br />';//debug line
      //add out of stock text based on qty
      if ($products_options->fields['pasqty'] < 1 && STOCK_CHECK == 'true' && $products_options->fields['pasid'] > 0) {
        //test, only applicable to products with-out the read-only attribute set
        if ($products_options_DISPLAYONLY->fields['attributes_display_only'] < 1) {
          $products_options->fields['products_options_values_name'] = $products_options->fields['products_options_values_name'] . PWA_OUT_OF_STOCK;
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
            if ($products_options_names->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_SELECT_SBA) {

              if (STOCK_SHOW_ATTRIB_LEVEL_STOCK == 'true' && $products_options->fields['pasqty'] > 0) {
                //test, only applicable to products with-out the read-only attribute set
                if ($products_options_DISPLAYONLY->fields['attributes_display_only'] < 1) {
                  $PWA_STOCK_QTY = PWA_STOCK_QTY . $products_options->fields['pasqty'] . ' ';
                  //show custom ID if flag set to true
                  if (STOCK_SBA_DISPLAY_CUSTOMID == 'true' AND ! empty($products_options->fields['customid'])) {
                    $PWA_STOCK_QTY .= ' (' . $products_options->fields['customid'] . ') ';
                  }
                }
              } elseif (STOCK_SHOW_ATTRIB_LEVEL_STOCK == 'true' && $products_options->fields['pasqty'] < 1 && $products_options->fields['pasid'] < 1) {
                //test, only applicable to products with-out the display-only attribute set
                if ($products_options_DISPLAYONLY->fields['attributes_display_only'] < 1) {
                  //use the qty from the product, unless it is 0, then set to out of stock.
                  if ($this->_products_options_names_count <= 1) {
                    if ($products_options->fields['products_quantity'] > 0) {
                      $PWA_STOCK_QTY = PWA_STOCK_QTY . $products_options->fields['products_quantity'] . ' ';
                    } else {
                      $products_options->fields['products_options_values_name'] = $products_options->fields['products_options_values_name'] . PWA_OUT_OF_STOCK;
                    }
                  }

                  //show custom ID if flag set to true
                  if (STOCK_SBA_DISPLAY_CUSTOMID == 'true' AND ! empty($products_options->fields['customid'])) {
                    $PWA_STOCK_QTY .= ' (' . $products_options->fields['customid'] . ') ';
                  }
                }
              } elseif (STOCK_SBA_DISPLAY_CUSTOMID == 'true' AND ! empty($products_options->fields['customid'])) {
                //show custom ID if flag set to true
                //test, only applicable to products with-out the read-only attribute set
                if ($products_options_DISPLAYONLY->fields['attributes_display_only'] < 1) {
                  $PWA_STOCK_QTY .= ' (' . $products_options->fields['customid'] . ') ';
                }
              }
            }
          }
        }
      }

      //create image array for use in select list to rotate visable image on select.
      if (!empty($products_options->fields['attributes_image'])) {
        $options_menu_images[] = array('id' => $products_options->fields['products_options_values_id'],
          'src' => DIR_WS_IMAGES . $products_options->fields['attributes_image']);
      } else {
        $options_menu_images[] = array('id' => $products_options->fields['products_options_values_id']);
      }
      // END "Stock by Attributes" SBA
   }

   /*
    * NOTIFY_ATTRIBUTES_MODULE_ORIGINAL_PRICE
    */
  function updateNotifyAttributesModuleOriginalPrice(&$callingClass, $notifier, $paramsArray){
    global $db, $products_options, $products_options_names, $currencies, $new_attributes_price, $product_info, $products_options_display_price, $PWA_STOCK_QTY;
    
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
   
   /*
    * NOTIFY_ATTRIBUTES_MODULE_ATTRIB_SELECTED
    */
  function updateNotifyAttributesModuleAttribSelected(&$callingClass, $notifier, $paramsArray){
    global $products_options, $selected_attribute, $moveSelectedAttribute, $disablebackorder;
    
    if (!$this->_isSBA) {
      return;
    }

    //move default selected attribute if attribute is out of stock and check out is not allowed
    if ($moveSelectedAttribute == true && (STOCK_ALLOW_CHECKOUT == 'false' && $products_options->fields['pasqty'] > 0)) {
      $selected_attribute = true;
      $moveSelectedAttribute = false;
    }
    $disablebackorder = null;
    //disable radio and disable default selected
    if ((STOCK_ALLOW_CHECKOUT == 'false' && $products_options->fields['pasqty'] <= 0 && !empty($products_options->fields['pasid']) ) 
    || ( STOCK_ALLOW_CHECKOUT == 'false' && $products_options->fields['products_quantity'] <= 0 && empty($products_options->fields['pasid']) )
    ) {//|| $products_options_READONLY->fields['attributes_display_only'] == 1
      if ($selected_attribute == true) {
        $selected_attribute = false;
        $moveSelectedAttribute = true;
      }
      $disablebackorder = ' disabled="disabled" ';
    }
    // END "Stock by Attributes" SBA
     
  }
   
  /*
   * Function that is activated when NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT is encountered as a notifier.
   */
  //NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT //Line 716
	function updateNotifyOrderProcessingStockDecrementInit(&$callingClass, $notifier, $paramsArray, & $productI, & $i) {
    $this->_i = $i;
    $this->_productI = $productI;

    $this->_stock_info = zen_get_sba_stock_attribute_info(zen_get_prid($this->_productI['id']), $this->_productI['attributes'], 'order'); // Sorted comma separated list of the attribute_id.

    // START "Stock by Attributes"
    $attributeList = null;
    $customid = null;
    if(isset($this->_productI['attributes']) and sizeof($this->_productI['attributes']) >0){
      foreach($this->_productI['attributes'] as $attributes){
        $attributeList[] = $attributes['value_id'];
      }
      $customid = zen_get_customid($this->_productI['id'],$attributeList); // Expects that customid would be from a combination product, not individual attributes on a single product.  Should return an array if the values are individual or a single value if all attributes equal a single product.
      $productI['customid'] = $customid;
      $this->_productI['customid'] = $customid;
//      $productI['model'] = (zen_not_null($customid) ? $customid : $productI['model']);
      $this->_productI['model'] = $productI['model'];
    }
    // END "Stock by Attributes"
  }

  /*
   * Function that is activated when NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_BEGIN is encountered as a notifier.
   */
  // Line 739
  function updateNotifyOrderProcessingStockDecrementBegin(&$callingClass, $notifier, $paramsArray, &$stock_values, &$attribute_stock_left){
  	global $db;

    $this->_stock_values = $stock_values;

    if ($stock_values->RecordCount() > 0) {
			// kuroi: Begin Stock by Attributes additions
			// added to update quantities of products with attributes
			$attribute_search = array();
			$attribute_stock_left = STOCK_REORDER_LEVEL + 1;  // kuroi: prevent false low stock triggers 

      // mc12345678 If the has attibutes then perform the following work.
			if(isset($this->_productI['attributes']) and sizeof($this->_productI['attributes']) >0){
        // mc12345678 Identify a list of attributes associated with the product
				$stock_attributes_search = zen_get_sba_stock_attribute(zen_get_prid($this->_productI['id']), $this->_productI['attributes'], 'order');
        
				$get_quantity_query = 'select quantity from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id="' . zen_get_prid($this->_productI['id']) . '" and stock_attributes="' . $stock_attributes_search . '"';

        // mc12345678 Identify the stock available from SBA.
  			$attribute_stock_available = $db->Execute($get_quantity_query);	
        // mc12345678 Identify the stock remaining for the overall stock by removing the number of the current product from the number available for the attributes_id. 
				$attribute_stock_left = $attribute_stock_available->fields['quantity'] - $this->_productI['qty'];
	
        // mc12345678 Update the SBA table to reflect the stock remaining based on the above.
				$attribute_update_query = 'update ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' set quantity='.$attribute_stock_left.' where products_id="' . zen_get_prid($this->_productI['id']) . '" and stock_attributes="' . $stock_attributes_search . '"';
				$db->Execute($attribute_update_query);	
        $this->_attribute_stock_left = $attribute_stock_left;
      }
    }
  }

  /*
   * Function that is activated when NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_END is encountered as a notifier.
   */
  // Line 776
  function updateNotifyOrderProcessingStockDecrementEnd(&$callingClass, $notifier, $paramsArray) {
    //Need to modify the email that is going out regarding low-stock.
    //paramsArray is $i at time of development.
    if ($callingClass->email_low_stock == '' && $callingClass->doStockDecrement && $this->_stock_values->RecordCount() > 0 && $this->_attribute_stock_left <= STOCK_REORDER_LEVEL) {
      // kuroi: trigger and details for attribute low stock email
      $callingClass->email_low_stock .=  'ID# ' . zen_get_prid($this->_productI['id']) . ', model# ' . $this->_productI['model'] . ', customid ' . $this->_productI['customid'] . ', name ' . $this->_productI['name'] . ', ';
			foreach($this->_productI['attributes'] as $attributes){
				$callingClass->email_low_stock .= $attributes['option'] . ': ' . $attributes['value'] . ', ';
			}
			$callingClass->email_low_stock .= 'Stock: ' . $this->_attribute_stock_left . "\n\n";
		// kuroi: End Stock by Attribute additions
    }
  }

  /*
   * Function that is activated when NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM is encountered as a notifier.
   */
//Line 883
  function updateNotifyOrderDuringCreateAddedAttributeLineItem(&$callingClass, $notifier, $paramsArray) {
    /* First check to see if SBA is installed and if it is then look to see if a value is 
     *  supplied in the stock_id parameter (which should only be populated when a SBA tracked
     *  item is in the order */
//      $_SESSION['paramsArray'] = $paramsArray;
    if (defined('TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK') && zen_not_null($this->_stock_info['stock_id'])) {  
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
  
  
  /*
   * Generic function that is activated when any notifier identified in the observer is called but is not found in one of the above previous specific update functions is encountered as a notifier.
   */
  function update(&$callingClass, $notifier, $paramsArray) {
	global $db;
    if ($notifier == 'NOTIFY_ATTRIBUTES_MODULE_SALE_MAKER_DISPLAY_PRICE_PERCENTAGE') {
      updateNotifyAttributesModuleSaleMakerDisplayPricePercentage($callingClass, $notifier, $paramsArray);
    }
  
    if ($notifier == 'NOTIFY_ATTRIBUTES_MODULE_OPTIONS_SQL') {
      updateNotifyAttributesModuleOptionsSQL($callingClass, $notifier, $paramsArray);
    }
    
    if ($notifier == 'NOTIFY_ATTRIBUTES_MODULES_OPTIONS_VALUES_SET') {
      updateNotifyAttributesModulesOptionsValuesSet ($callingClass, $notifier, $paramsArray);
    }
    
    if ($notifier == 'NOTIFY_ATTRIBUTES_MODULE_ORIGINAL_PRICE') {
      updateNotifyAttributesModuleOriginalPrice($callingClass, $notifier, $paramsArray);
    }
    
    if ($notifier == 'NOTIFY_ATTRIBUTES_MODULE_ATTRIB_SELECTED') {
      updateNotifyAttributesModuleAttribSelected($callingClass, $notifier, $paramsArray);
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
  //	function updateNotifyOrderProcessingStockDecrementInit(&$callingClass, $notifier, $paramsArray, & $productI, & $i) {
      $i = $paramsArray['i'];
      $productI = $callingClass->products[$i];
      $this->_stock_values = $paramsArray['stock_values'];
      $stock_values = $this->_stock_values;
      updateNotifyOrderProcessingStockDecrementInit($callingClass, $notifier, $paramsArray, $productI, $i);
      updateNotifyOrderProcessingStockDecrementBegin($callingClass, $notifier, $paramsArray, $stock_values, $attribute_stock_left);
      /*$this->_i = $i;
      $this->_productI = $productI;

      $this->_stock_info = zen_get_sba_stock_attribute_info(zen_get_prid($this->_productI['id']), $this->_productI['attributes'], 'order');

      // START "Stock by Attributes"
      $attributeList = null;
      $customid = null;
      if(isset($this->_productI['attributes']) and sizeof($this->_productI['attributes']) >0){
        foreach($this->_productI['attributes'] as $attributes){
          $attributeList[] = $attributes['value_id'];
        }
        $customid = zen_get_customid($this->_productI['id'],$attributeList);
        $productI['customid'] = $customid;
        $this->_productI['customid'] = $customid;
  //      $productI['model'] = (zen_not_null($customid) ? $customid : $productI['model']);
        $this->_productI['model'] = $productI['model'];
      }
      // END "Stock by Attributes"

      if ($this->_stock_values->RecordCount() > 0) {
        // kuroi: Begin Stock by Attributes additions
        // added to update quantities of products with attributes
        $attribute_search = array();
        $attribute_stock_left = STOCK_REORDER_LEVEL + 1;  // kuroi: prevent false low stock triggers 

        // mc12345678 If the has attibutes then perform the following work.
        if(isset($this->_productI['attributes']) and sizeof($this->_productI['attributes']) >0){
          // mc12345678 Identify a list of attributes associated with the product
          $stock_attributes_search = zen_get_sba_stock_attribute(zen_get_prid($this->_productI['id']), $this->_productI['attributes'], 'order');

          $get_quantity_query = 'select quantity from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id="' . zen_get_prid($this->_productI['id']) . '" and stock_attributes="' . $stock_attributes_search . '"';

          // mc12345678 Identify the stock available from SBA.
          $attribute_stock_available = $db->Execute($get_quantity_query);	
          // mc12345678 Identify the stock remaining for the overall stock by removing the number of the current product from the number available for the attributes_id. 
          $attribute_stock_left = $attribute_stock_available->fields['quantity'] - $this->_productI['qty'];

          // mc12345678 Update the SBA table to reflect the stock remaining based on the above.
          $attribute_update_query = 'update ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' set quantity='.$attribute_stock_left.' where products_id="' . zen_get_prid($this->_productI['id']) . '" and stock_attributes="' . $stock_attributes_search . '"';
          $db->Execute($attribute_update_query);	
          $this->_attribute_stock_left = $attribute_stock_left;
        }
      }*/
      
    }

    /*
     * Function that is activated when NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_END is encountered as a notifier.
     */
    // Line 776
    if ($notifier == 'NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_END') {
      updateNotifyOrderProcessingStockDecrementEnd($callingClass, $notifier, $paramsArray);
    //function updateNotifyOrderProcessingStockDecrementEnd(&$callingClass, $notifier, $paramsArray) {
      //Need to modify the email that is going out regarding low-stock.
      //paramsArray is $i at time of development.
/*      if ($callingClass->email_low_stock == '' && $callingClass->doStockDecrement && $this->_stock_values->RecordCount() > 0 && $this->_attribute_stock_left <= STOCK_REORDER_LEVEL) {
        // kuroi: trigger and details for attribute low stock email
        $callingClass->email_low_stock .=  'ID# ' . zen_get_prid($this->_productI['id']) . ', model# ' . $this->_productI['model'] . ', customid ' . $this->_productI['customid'] . ', name ' . $this->_productI['name'] . ', ';
        foreach($this->_productI['attributes'] as $attributes){
          $callingClass->email_low_stock .= $attributes['option'] . ': ' . $attributes['value'] . ', ';
        }
        $callingClass->email_low_stock .= 'Stock: ' . $this->_attribute_stock_left . "\n\n";
      // kuroi: End Stock by Attribute additions
      }*/
    }
    
    if ($notifier == 'NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM') {
      updateNotifyOrderDuringCreateAddedAttributeLineItem($callingClass, $notifier, $paramsArray);
    } //endif NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM - mc12345678
			/* First check to see if SBA is installed and if it is then look to see if a value is 
       *  supplied in the stock_id parameter (which should only be populated when a SBA tracked
       *  item is in the order */
//      $_SESSION['paramsArray'] = $paramsArray;
//			if (defined('TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK') && zen_not_null($this->_stock_info['stock_id'])) {  
        //Need to validate that order had attributes in it.  If so, then were they tracked by SBA and then add to appropriate table.
/*          `orders_products_attributes_stock_id` INT(11) NOT NULL auto_increment, 
    `orders_products_attributes_id` INT(11) NOT NULL default '0',
    `orders_id` INT(11) NOT NULL default '0', 
    `orders_products_id` INT(11) NOT NULL default '0', 
    `stock_id` INT(11) NOT NULL default '0', 
    `stock_attribute` VARCHAR(255) NULL DEFAULT NULL, 
    `products_prid` TINYTEXT NOT NULL, */
/*              $sql_data_array = array('orders_products_attributes_id' =>$paramsArray['orders_products_attributes_id'],
                              'orders_id' =>$paramsArray['orders_id'], 
                              'orders_products_id' =>$paramsArray['orders_products_id'], 
                              'stock_id' => $this->_stock_info['stock_id'], 
                              'stock_attribute' => $this->_stock_info['stock_attribute'], 
                              'customid' => $this->_productI['customid'],
                              'products_prid' =>$paramsArray['products_prid']);
      zen_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK, $sql_data_array); //inserts data into the TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK table.

      }
		} //endif NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM - mc12345678*/
	} //end update function - mc12345678
} //end class - mc12345678

