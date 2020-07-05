<?php
/**
 * @package admin/includes/classes
 * products_with_attributes_stock.php
 * @copyright Copyright 2003-2014 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id:  $
 *
 * Stock by Attributes 1.5.4   15-11-14 mc12345678
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

class products_with_attributes_stock extends base
  {  
  private $stringTypeToIgnoreNull;
  
  function __construct() {
    if (!isset($GLOBALS['db'])) return;
    $test_text = 'This is NULL';
    $test_text_result = $GLOBALS['db']->bindVars(':test_text:', ':test_text:', $test_text, 'string');
    $this->stringTypeToIgnoreNull = (($test_text_result == 'null') ? 'stringIgnoreNull' : 'string');
  }
  
  // Function to account for issue identified with earlier Zen Cart versions use of the 'float' conversion.
  //  It has been found that in earlier versions (i.e. 1.5.4), if $quantity were non-zero and passed to bindVars that it would return a 0.
  function query_insert_float($query, $replaceField, $quantity) {
    global $db;
    
    //  It does not matter if a zero is passed to this function as it will still process as a 0 in either set of code.
    if ($quantity == 0 || $db->bindVars(':test:', ':test:', $quantity, 'float') != 0) {
      $query = $db->bindVars($query, $replaceField, $quantity, 'float');
    } else {
      if (function_exists('convertToFloat')) {
        $quantity = convertToFloat($quantity);
      } else {
        // Come here if the bindVars of a non-zero value as a float returns a 0 for potential alternate processing to a float.
        //  Code adapted from Zen Cart 1.5.6 admin/includes/modules/update_product.php
        $tempval = $quantity;
        if ($tempval === null) {
          $quantity = 0;
        }
        if ($quantity !== 0) {
          $tempval = preg_replace('/[^0-9,\.\-]/', '', $quantity);
          // do a non-strict compare here:
          if ($tempval == 0) {
            $quantity = 0;
          }
        }

        if ($tempval != 0) {
          $quantity = (float)$tempval;
        }
        unset($tempval);
      }
      // Go ahead and process as a string as the value has been prepared to be a float and known that 'float' conversion will not work.
      $query = $db->bindVars($query, $replaceField, $quantity, 'noquotestring');
    }
    
    return $query;
  }
  
    function get_products_attributes($products_id, $languageId=1)
    {
      global $db;
      // Added the following to query "and pa.attributes_display_only != 1" This removed display only attributes from the stock selection.
      // Added the following to query "AND po.products_options_type != ' . PRODUCTS_OPTIONS_TYPE_READONLY so that would ignore READONLY attributes.

      $attributes_array = array();
      
      //LPAD - Return the string argument, left-padded with the specified string 
      //example: LPAD(po.products_options_sort_order,11,"0") the field is 11 digits, and is left padded with 0
      if (PRODUCTS_OPTIONS_SORT_ORDER == '0') {
        $options_order_by= ' ORDER BY LPAD(popt.products_options_sort_order,11,"0"), popt.products_options_name';
      } else {
        $options_order_by= ' ORDER BY popt.products_options_name';
      }

      //get the option/attribute list
      $sql = "SELECT distinct popt.products_options_id, popt.products_options_name, popt.products_options_sort_order,
                              popt.products_options_type
              FROM        " . TABLE_PRODUCTS_OPTIONS . " popt
              LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " patrib ON (patrib.options_id = popt.products_options_id)
              WHERE patrib.products_id= :products_id:
              AND popt.language_id = :languages_id: " .
              $options_order_by;

      $sql = $db->bindVars($sql, ':products_id:', $products_id, 'integer');
      $sql = $db->bindVars($sql, ':languages_id:', $languageId, 'integer');

      $attributes = $db->Execute($sql);
      
      if ($attributes->RecordCount() == 0) {
        return false;
      }
      
      if (PRODUCTS_OPTIONS_SORT_BY_PRICE == '1') {
        $order_by = ' ORDER BY LPAD(pa.products_options_sort_order,11,"0"), pov.products_options_values_name';
      } else {
        $order_by = ' ORDER BY LPAD(pa.products_options_sort_order,11,"0"), pa.options_values_price';
      }
      $products_options_array = array();
      
      while (!$attributes->EOF) {
        
        $sql = "SELECT    pov.products_options_values_id,
                      pov.products_options_values_name,
                      pa.*
            FROM      " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov
            WHERE     pa.products_id = " . (int)$products_id . "
            AND       pa.options_id = " . (int)$attributes->fields['products_options_id'] . "
            AND       pa.options_values_id = pov.products_options_values_id
            AND       pov.language_id = " . (int)$languageId . " " .
              $order_by;

        $attributes_array_ans= $db->Execute($sql);

        //loop for each option/attribute listed

        while (!$attributes_array_ans->EOF) {
          $attributes_array[$attributes->fields['products_options_name']][] =
            array('id' => $attributes_array_ans->fields['products_attributes_id'],
                'text' => $attributes_array_ans->fields['products_options_values_name']
                      . ' (' . $attributes_array_ans->fields['price_prefix']
                    . '$'.zen_round($attributes_array_ans->fields['options_values_price'],2) . ')',
                'display_only' => $attributes_array_ans->fields['attributes_display_only'],
                );
          
          $attributes_array_ans->MoveNext();
        }
        $attributes->MoveNext();
      }
  
      return $attributes_array;
  
    }
  
    function update_parent_products_stock($products_id)
    {
      global $db;

      $query = 'SELECT sum(quantity) AS quantity, products_id FROM ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' WHERE products_id = :products_id:';
      $query = $db->bindVars($query, ':products_id:', $products_id, 'integer');
      $quantity = $db->Execute($query);

      $query = 'UPDATE ' . TABLE_PRODUCTS . ' SET products_quantity=:quantity: WHERE products_id=:products_id:';
      $query = $db->bindVars($query, ':products_id:', zen_get_prid($products_id), 'integer');

      // Tests are this: If the the item was found in the SBA table then update with those results.
      // Else pull the value from the current stock quantity  and if the "switch" has not been
      //  turned off, the value will stay the same otherwise, it would be set to zero.
      if ($quantity->RecordCount() > 0 && $quantity->fields['products_id'] == zen_get_prid($products_id)) {
        $query = $this->query_insert_float($query, ':quantity:', $quantity->fields['quantity']);
      } else {
        // Should add a switch to allow not resetting the quantity to zero when synchronizing quantities... This doesn't entirely make sense that because the product is not listed in the SBA table, that it should be zero'd out...
        $query2 = "SELECT p.products_quantity AS quantity FROM :table: p WHERE products_id=:products_id:";
        $query2 = $db->bindVars($query2, ':table:', TABLE_PRODUCTS, 'passthru');
        $query2 = $db->bindVars($query2, ':products_id:', zen_get_prid($products_id), 'integer');
        $quantity_orig = $db->Execute($query2);
        
        $quantity_val = 0;
        if ($quantity_orig->RecordCount() > 0 && true /* This is where a switch could be introduced to allow setting to 0 when synchronizing with the SBA table. But as long as true, and the item is not tracked by SBA, then there is no change in the quantity.  header message probably should also appear.. */) {
          $quantity_val = $quantity_orig->fields['quantity'];
        }
        
        $query = $this->query_insert_float($query, ':quantity:', $quantity_val);
      }

      $db->Execute($query);
    }
    
    // Technically the below update of all, could call the update of one... There doesn't
    //  seem to be a way to do the update in any more of a faster way than to address each product.
    function update_all_parent_products_stock() {
      global $db;
      $products_array = $this->get_products_with_attributes();
      foreach ($products_array as $products_id) {
        $query = 'SELECT sum(quantity) AS quantity, products_id FROM ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' WHERE products_id = :products_id:';
        $query = $db->bindVars($query, ':products_id:', zen_get_prid($products_id), 'integer');
        $quantity = $db->Execute($query);

        $query = 'UPDATE ' . TABLE_PRODUCTS . ' SET  products_quantity=:quantity: WHERE products_id=:products_id:';
        $query = $db->bindVars($query, ':products_id:', zen_get_prid($products_id), 'integer');
        // Tests are this: If the the item was found in the SBA table then update with those results.
        // Else pull the value from the current stock quantity  and if the "switch" has not been
        //  turned off, the value will stay the same otherwise, it would be set to zero.
        if ($quantity->RecordCount() > 0 && $quantity->fields['products_id'] == zen_get_prid($products_id)) {
          $query = $this->query_insert_float($query, ':quantity:', $quantity->fields['quantity']);
        } else {
          // Should add a switch to allow not resetting the quantity to zero when synchronizing quantities... This doesn't entirely make sense that because the product is not listed in the SBA table, that it should be zero'd out...
          $query2 = "SELECT p.products_quantity AS quantity FROM :table: p WHERE products_id=:products_id:";
          $query2 = $db->bindVars($query2, ':table:', TABLE_PRODUCTS, 'passthru');
          $query2 = $db->bindVars($query2, ':products_id:', zen_get_prid($products_id), 'integer');
          $quantity_orig = $db->Execute($query2);
          if ($quantity_orig->RecordCount() > 0 && true /* This is where a switch could be introduced to allow setting to 0 when synchronizing with the SBA table. But as long as true, and the item is not tracked by SBA, then there is no change in the quantity.  header message probably should also appear.. */) {
            $query = $this->query_insert_float($query, ':quantity:', $quantity_orig->fields['quantity']);
          } else {
            $query = $this->query_insert_float($query, ':quantity:', 0);
          }
        }
        
        $db->Execute($query);
      }
    }
    
    // returns an array of product ids which contain attributes
    function get_products_with_attributes() {
      global $db;
      if (!empty($_SESSION['languages_id'])) {
        $language_id = (int)$_SESSION['languages_id'];
      } else {
        $language_id=1;
      }
      $query = 'SELECT DISTINCT pa.products_id, pd.products_name, p.products_quantity, p.products_model, p.products_image
                FROM ' . TABLE_PRODUCTS_ATTRIBUTES . ' pa
                LEFT JOIN ' . TABLE_PRODUCTS_DESCRIPTION . ' pd ON (pa.products_id = pd.products_id)
                LEFT JOIN ' . TABLE_PRODUCTS . ' p ON (pa.products_id = p.products_id)
                WHERE pd.language_id = ' . (int)$language_id . '
                ORDER BY pd.products_name';
      $products = $db->Execute($query);
      while (!$products->EOF) {
        $products_array[] = (int)$products->fields['products_id'];
        $products->MoveNext();
      }
      return $products_array;
    }
  
  
    function get_attributes_name($attribute_id, $languageId = 1) {
      global $db;

      $query = 'SELECT pa.products_attributes_id, po.products_options_name, pov.products_options_values_name
             FROM ' . TABLE_PRODUCTS_ATTRIBUTES . ' pa
             LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS . ' po ON (pa.options_id = po.products_options_id)
             LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS_VALUES . ' pov ON (pa.options_values_id = pov.products_options_values_id)
             WHERE pa.products_attributes_id = ' . (int)$attribute_id . '
                   AND po.language_id = ' . (int)$languageId . '
                   AND po.language_id = pov.language_id';
              
      $attributes = $db->Execute($query);

      if ($attributes->RecordCount() == 0) {
        return false;
      }

      return array(
                   'option' => $attributes->fields['products_options_name'],
                   'value' => $attributes->fields['products_options_values_name'],
                   );
    }
        
        
/**
 * @desc displays the filtered product-rows
 * 
 * Passed Options
 * $SearchBoxOnly
 * $ReturnedPage
 * $NumberRecordsShown
 */
    function displayFilteredRows($SearchBoxOnly = null, $NumberRecordsShown = null, $ReturnedProductID = null) {
        global $db, $sniffer, $languages;
      
        if (isset($_SESSION['languages_id']) && $_SESSION['languages_id'] > 0) {
          $language_id = (int)$_SESSION['languages_id'];
        } else { 
          $language_id = 1;
        }
        $s = '';
        $w = '';
        if (isset($_GET['search']) && $_GET['search']) { // mc12345678 Why was $_GET['search'] omitted?
            $s = zen_db_input($_GET['search']);
           //$w = "(p.products_id = '$s' OR d.products_name LIKE '%$s%' OR p.products_model LIKE '%$s%') AND  " ;//original version of search
            //$w = "( p.products_id = '$s' OR d.products_name LIKE '%$s%' OR p.products_model LIKE '$s%' ) AND  " ;//changed search to products_model 'startes with'.
           //$w = "( p.products_id = '$s' OR d.products_name LIKE '%$s%' ) AND  " ;//removed products_model from search
            $w = " AND ( p.products_id = '$s' 
                        OR d.products_name LIKE '%$s%' 
                        OR p.products_model LIKE '%$s%' 
                        OR p.products_id 
                IN (SELECT products_id 
                      FROM " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK. " pwas 
                      WHERE pwas.customid 
                        LIKE '%$s%')
                        ) "; //changed search to products_model 'starts with'.
        }

        //Show last edited record or Limit number of records displayed on page
        $SearchRange = null;
        if (isset($ReturnedProductID) && !isset($_GET['search'])) {
          $ReturnedProductID = zen_db_input($ReturnedProductID);
          //$w = "( p.products_id = '$ReturnedProductID' ) AND  " ;//sets returned record to display
          $w = " AND ( p.products_id = '$ReturnedProductID' ) " ;//sets returned record to display
          if (!isset($_GET['products_filter']) || ($_GET['products_filter'] != '' && $_GET['products_filter'] <= 0)) {
            $SearchRange = "LIMIT 1";//show only selected record
          }
        } /*elseif ( $ReturnedProductID != null && isset($_GET['search'])) {
            $ReturnedProductID = zen_db_input($ReturnedProductID);
          $NumberRecordsShown = zen_db_input($NumberRecordsShown);
        }*/
        elseif ($NumberRecordsShown > 0 && $SearchBoxOnly == 'false') {
          $NumberRecordsShown = zen_db_input($NumberRecordsShown);
          $SearchRange = " LIMIT $NumberRecordsShown";//sets start record and total number of records to display
        }
        elseif ($SearchBoxOnly == 'true' && !isset($_GET['search'])) {
             $SearchRange = "LIMIT 0";//hides all records
        }

        $retArr = array();
/*        $query_products =    'SELECT distinct pa.products_id, pd.products_name, p.products_quantity,
            p.products_model, p.products_image, p.products_type, p.master_categories_id
            
            FROM ' . TABLE_PRODUCTS_ATTRIBUTES . ' pa
            LEFT JOIN ' . TABLE_PRODUCTS_DESCRIPTION . ' pd ON (pa.products_id = pd.products_id)
            LEFT JOIN ' . TABLE_PRODUCTS . ' p ON (pa.products_id = p.products_id)
            
            WHERE pd.language_id='.$language_id.'
            ' . $w . '
            ORDER BY pd.products_name
            ' . $SearchRange.'';*/
        if (isset($_GET['page']) && ($_GET['page'] > 1)) $rows = STOCK_SET_SBA_NUMRECORDS * ((int)$_GET['page'] - 1);

        if (isset($_GET['search_order_by'])) {
          $search_order_by = zen_db_prepare_input($_GET['search_order_by']);
        } else {
          $search_order_by = 'products_model';
        }

        if (!$sniffer->field_exists(TABLE_PRODUCTS, $search_order_by)) {
          if (!$sniffer->field_exists(TABLE_PRODUCTS_DESCRIPTION, $search_order_by)) {
            $search_order_by = 'products_model';
          }
        }
        if ($sniffer->field_exists(TABLE_PRODUCTS, $search_order_by)) {
          $search_order_by = 'p.' . $search_order_by;
        }
        if ($sniffer->field_exists(TABLE_PRODUCTS_DESCRIPTION, $search_order_by)) {
          $search_order_by = 'pd.' . $search_order_by;
        }

        $query_products =    "SELECT DISTINCT pa.products_id, pd.products_name, p.products_quantity, p.products_model, p.products_image, p.products_type, p.master_categories_id, " . $search_order_by . " FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS . " p WHERE pd.language_id=" . (int)$language_id . " AND pa.products_id = pd.products_id AND pa.products_id = p.products_id " . $w . " ORDER BY " . $search_order_by . " " . $SearchRange;

        if (!isset($_GET['seachPID']) && !isset($_GET['pwas-search-button']) && !isset($_GET['updateReturnedPID'])) {
          $products_split = new splitPageResults($_GET['page'], STOCK_SET_SBA_NUMRECORDS, $query_products, $products_query_numrows);
        } 
        $products = $db->Execute($query_products);

        $html = '';
        if (!isset($_GET['seachPID']) && !isset($_GET['pwas-search-button']) && !isset($_GET['updateReturnedPID'])) {
          $html .= '<table border="0" width="100%" cellspacing="0" cellpadding="2" class="pageResults">';
          $html .= '<tr>';
          $html .= '<td class="smallText" valign="top">'; 
          $html .= $products_split->display_count($products_query_numrows, STOCK_SET_SBA_NUMRECORDS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_PRODUCTS); 
          $html .= '</td>';
          $html .= '<td class="smallText" align="right">';
          $html .= $products_split->display_links($products_query_numrows, STOCK_SET_SBA_NUMRECORDS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']);
          $html .= '</td>';
          $html .= '</tr>';
          $html .= '</table>';
        }
        $html .= zen_draw_form('stock_update', FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK . '_ajax', 'save=1&amp;pid=' . $ReturnedProductID . (!empty($_GET['page']) ? '&amp;page=' . $_GET['page'] : ''), 'post');
        $html .= zen_draw_hidden_field('save', '1');
        $html .= zen_draw_hidden_field('pid', $ReturnedProductID);
        $html .= zen_image_submit('button_save.gif', IMAGE_SAVE) . ' Hint: To quickly edit click in the "Quantity in Stock" field.';
        $html .= '<br/>';
        $html .= '
    <table id="mainProductTable"> 
    <tr>
      <th class="thProdId">' . PWA_PRODUCT_ID . '</th>
      <th class="thProdName">' . PWA_PRODUCT_NAME . '</th>';
    
        if (STOCK_SHOW_IMAGE == 'true') {$html .= '<th class="thProdImage">' . PWA_PRODUCT_IMAGE . '</th>';}   

        $html .= '<th class="thProdModel">' . PWA_PRODUCT_MODEL . '</th>            
              <th class="thProdQty">' . PWA_QUANTITY_FOR_ALL_VARIANTS . '</th>
              <th class="thProdAdd">' . PWA_ADD_QUANTITY . '</th> 
              <th class="thProdSync">' . PWA_SYNC_QUANTITY . '</th>
              </tr>';
        
        while (!$products->EOF) { 

          // SUB
          $query = 'SELECT * FROM ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' WHERE products_id=' . (int)$products->fields['products_id'] . '
                    ORDER BY SORT ASC;';

          $attribute_products = $db->Execute($query);

          $query = 'SELECT SUM(quantity) AS total_quantity
                    FROM ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' 
                    WHERE products_id=' . (int)$products->fields['products_id'];

          $attribute_quantity = $db->Execute($query);

          $synchronized = null;

          if ($_SESSION['pwas_class2']->zen_product_is_sba($products->fields['products_id'])) {
            if ($products->fields['products_quantity'] > $attribute_quantity->fields['total_quantity']) {
              $synchronized = '<br/> Prod Qty > Attrib Qty ';
            } elseif ($products->fields['products_quantity'] != $attribute_quantity->fields['total_quantity']) {
              $synchronized = '<br/> Prod Qty < Attrib Qty ';
            }
          }

          $html .= '<tr>' . "\n";
          $html .= '<td colspan="7">' . "\n";
          $html .= '<div class="productGroup">' . "\n";
          $html .= '<table>' . "\n";
            $html .= '<tr class="productRow">' . "\n";
            $html .= '<td class="tdProdId">' . $products->fields['products_id'] . '</td>';
            $html .= '<td class="tdProdName">' . $products->fields['products_name'] . '</td>';
            
            if (STOCK_SHOW_IMAGE == 'true') {$html .= '<td class="tdProdImage">' . zen_info_image(zen_output_string($products->fields['products_image']), zen_output_string($products->fields['products_name']), "60", "60") . '</td>';}
            
            //product.php? page=1 & product_type=1 & cPath=13 & pID=1042 & action=new_product
            //$html .= '<td class="tdProdModel">' . $products->fields['products_model'] . ' </td>';
            $html .= '<td class="tdProdModel">' . $products->fields['products_model'] . '<br /><a href="' . zen_href_link(FILENAME_PRODUCT, "page=1&amp;product_type=" . $products->fields['products_type'] . "&amp;cPath=" . $products->fields['master_categories_id'] . "&amp;pID=" . $products->fields['products_id'] . "&amp;action=new_product", 'NONSSL').'">Link</a><br /><br /><a href="' . zen_href_link(FILENAME_ATTRIBUTES_CONTROLLER, "products_filter=&amp;products_filter=" . $products->fields['products_id'] . "&amp;current_category_id=" . $products->fields['master_categories_id'], 'NONSSL') . '">' . BOX_CATALOG_CATEGORIES_ATTRIBUTES_CONTROLLER . '</a></td>';
            $html .= '<td class="tdProdQty">' . $products->fields['products_quantity'] . $synchronized . '</td>';
            $html .= '<td class="tdProdAdd"><a href="' . zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, "action=add&amp;products_id=" . $products->fields['products_id'] . '&amp;search_order_by=' . $search_order_by, 'NONSSL') . '">' . PWA_ADD_QUANTITY . '</a><br /><br /><a href="' . zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, "action=delete_all&amp;products_id=" . $products->fields['products_id'] . '&amp;search_order_by=' . $search_order_by, 'NONSSL').'">' . PWA_DELETE_VARIANT_ALL .'</a></td>';
            $html .= '<td class="tdProdSync"><a href="' . zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, "action=resync&amp;products_id=".$products->fields['products_id'] . '&amp;search_order_by=' . $search_order_by, 'NONSSL').'">' . PWA_SYNC_QUANTITY . '</a></td>';
            $html .= '</tr>' . "\n";
            $html .= '</table>' . "\n";
            
          // SUB            
/*          $query = 'SELECT * FROM ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' WHERE products_id="' . $products->fields['products_id'] . '"
                    ORDER BY sort ASC;';

          $attribute_products = $db->Execute($query);*/
          if ($attribute_products->RecordCount() > 0) {

              $html .= '<table class="stockAttributesTable">';
              $html .= '<tr>';
              $html .= '<th class="stockAttributesHeadingStockId">' . PWA_STOCK_ID.'</th>
                    <th class="stockAttributesHeadingComboId" title="This number is the Product ID and related Attributes (Unique Combo).">'.PWA_PAC.'</th>
                    <th class="stockAttributesHeadingVariant">' . PWA_VARIANT .'</th>
                    <th class="stockAttributesHeadingQuantity">' . PWA_QUANTITY_IN_STOCK . '</th>
                    <th class="stockAttributesHeadingSort">' . PWA_SORT_ORDER . '</th>
                    <th class="stockAttributesHeadingCustomid" title="The Custom ID MUST be Unique, no duplicates allowed!">'.PWA_CUSTOM_ID.'</th>
                    <th class="stockAttributesHeadingSKUTitleId">' . PWA_SKU_TITLE . '</th>
                    <th class="stockAttributesHeadingEdit">' . PWA_EDIT . '</th>
                    <th class="stockAttributesHeadingDelete">' . PWA_DELETE . '</th>';
              $html .= '</tr>';

              while (!$attribute_products->EOF) {
                
                  $html .= '<tr id="sid-' . $attribute_products->fields['stock_id'] . '">';
                  $html .= '<td class="stockAttributesCellStockId">' . "\n";
                  $html .= $attribute_products->fields['stock_id'];
                  $html .= '</td>' . "\n";
                  $html .= '<td>' . $attribute_products->fields['product_attribute_combo'] . '</td>' . "\n";
                  $html .= '<td class="stockAttributesCellVariant">' . "\n";
                 
                  if (PRODUCTS_OPTIONS_SORT_ORDER == '0') {
                    $options_order_by= ' ORDER BY LPAD(po.products_options_sort_order,11,"0"), po.products_options_name';
                  } else {
                    $options_order_by= ' ORDER BY po.products_options_name';
                  }

                  $sort2_query = "SELECT DISTINCT pa.products_attributes_id, po.products_options_sort_order, po.products_options_name
                         , po.language_id
                         FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                   LEFT JOIN " . TABLE_PRODUCTS_OPTIONS . " po on (po.products_options_id = pa.options_id) 
                         WHERE pa.products_attributes_id in (" . $attribute_products->fields['stock_attributes'] . ")
                         GROUP BY po.language_id, pa.products_attributes_id
                         " . $options_order_by; 
                  $sort_class = $db->Execute($sort2_query);
                  $array_temp_sorted_array = array();
                  $attributes_of_stock = array();
                  while (!$sort_class->EOF) {
                    $attributes_of_stock[] = array(
                                              'products_attributes_id' => $sort_class->fields['products_attributes_id'],
                                              'language_id' => $sort_class->fields['language_id'],
                                              );
                    $sort_class->MoveNext();
                  }

                  $attributes_output = array();
                  foreach ($attributes_of_stock as $attri_id)
                  {
                      $stock_attribute = $this->get_attributes_name($attri_id['products_attributes_id'], $attri_id['language_id']/*$_SESSION['languages_id']*/);
                      if ($stock_attribute === false) continue; // If the products_attributes_id is not found in the selected language then move on.
                      if ($stock_attribute['option'] == '' && $stock_attribute['value'] == '') {
                        // delete stock attribute
                        $db->Execute("DELETE FROM " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " WHERE stock_id = " . $attribute_products->fields['stock_id'] . " LIMIT 1;");
                      } else { 
                        foreach ($languages as $lang) {
                          if ($lang['id'] == $attri_id['language_id']) {
                            break;
                          }
                        }
                        $attributes_output[] = $lang['code'] . ':' . '<strong>' . $stock_attribute['option'] . ':</strong> ' . $stock_attribute['value'] . '<br />';
                      }
                  }
//                  sort($attributes_output);
                  $html .= implode("\n", $attributes_output);

                  $html .= '</td>' . "\n";
                  $html .= '<td class="stockAttributesCellQuantity editthis" id="stockid-quantity-' . $attribute_products->fields['stock_id'] . '">' . $attribute_products->fields['quantity'] . '</td>' . "\n";
                  $html .= '<td class="stockAttributesCellSort editthis" id="stockid-sort-' . $attribute_products->fields['stock_id'] . '">' . $attribute_products->fields['sort'] . '</td>' . "\n";
                  $html .= '<td class="stockAttributesCellCustomid editthis" id="stockid-customid-' . $attribute_products->fields['stock_id'] . '">' . $attribute_products->fields['customid'] . '</td>' . "\n";
                  $html .= '<td class="stockAttributesCellTitle" id="stockid-title-' . $attribute_products->fields['stock_id'] . '">' . $attribute_products->fields['title'] . '</td>' . "\n";
                  $html .= '<td class="stockAttributesCellEdit">' . "\n";
                  $html .= '<a href="' . zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, "action=edit&amp;products_id=" . $products->fields['products_id'] . '&amp;attributes=' . $attribute_products->fields['stock_attributes'] . '&amp;q=' . $attribute_products->fields['quantity'] . '&amp;search_order_by=' . $search_order_by, 'NONSSL') . '">' . PWA_EDIT_QUANTITY . '</a>'; //s_mack:prefill_quantity
                  $html .= '</td>' . "\n";
                  $html .= '<td class="stockAttributesCellDelete">' . "\n";
                  $html .= '<a href="' . zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, "action=delete&amp;products_id=" . $products->fields['products_id'] . '&amp;attributes=' . $attribute_products->fields['stock_attributes'] . '&amp;search_order_by=' . $search_order_by, 'NONSSL').'">' . PWA_DELETE_VARIANT . '</a>';
                  $html .= '</td>' . "\n";
                  $html .= '</tr>' . "\n";
                 

                  $attribute_products->MoveNext();
              }
              $html .= '</table>';
          }
          $html .= '</div>' . "\n";
          $products->MoveNext();
      }
      $html .= '</table>' . "\n";
      $html .= zen_image_submit('button_save.gif', IMAGE_SAVE);
      $html .= '</form>' . "\n";
        if (!isset($_GET['seachPID']) && !isset($_GET['pwas-search-button']) && !isset($_GET['updateReturnedPID'])) {
      $html .= '<table border="0" width="100%" cellspacing="0" cellpadding="2" class="pageResults">';
      $html .= '<tr>';
      $html .= '<td class="smallText" valign="top">';
      $html .= $products_split->display_count($products_query_numrows, STOCK_SET_SBA_NUMRECORDS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_PRODUCTS); 
      $html .= '</td>';
      $html .= '<td class="smallText" align="right">';
      $html .= $products_split->display_links($products_query_numrows, STOCK_SET_SBA_NUMRECORDS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']);
      $html .= '</td>';
      $html .= '</tr>';
      $html .= '</table>';
        }

      return $html;
    }

//Used with jquery to edit qty on stock page and to save
function saveAttrib() {

  global $db;
//  $stock = $products_with_attributes_stock_class; // Should replace all cases of $stock with the class variable name.
    $i = 0;

    foreach ($_POST as $key => $value) {
      $matches = array();
      
      if (preg_match('/stockid-(.*?)-(.*)/', $key, $matches)) {
        // $matches[1] is expected to be the pwas database table field to be updated
        // $matches[2] is expected to be the pwas stock_id to be updated

        $tabledata = '';
        $stock_id = null;
        
        $tabledata = $matches[1];
        $stock_id = $matches[2];
        
        switch ($tabledata) {
          case 'quantity':
          case 'sort':
//            $value = (float)$value; // Get a float value
            $value = $this->query_insert_float(':quantity:', ':quantity:', $value);
            break;
          case 'customid':
          case 'title':
            if ($db->getBindVarValue('NULL', 'string') === 'null' && $value !== 'NULL') {
              if (empty($value)) {
                $value = 'NULL';
              }
              $value = $db->getBindVarValue($value, 'string');
            } else {
              $value = $db->prepare_input($value); // Maybe if numeric bind to float, else bind to string.
              $value = $this->nullDataEntry($value); // Get the value or string of entered text, if there is nothing then be able to store a null value that is not the text 'null'.
              if (empty($value)) {
                $value = 'null';
              }
            }  
            break;
          default:
            next;
            break;
        }

        if (isset($stock_id) && (int)$stock_id > 0) {
          $sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET :field: = $value WHERE stock_id = :stock_id: LIMIT 1";
          $sql = $db->bindVars($sql, ':field:', $tabledata, 'noquotestring');
          $sql = $db->bindVars($sql, ':stock_id:', $stock_id, 'integer');
          $db->execute($sql);
          $i++;
        }
      }
      
/*      $id1 = intval(str_replace('stockid1-', '', $key));//title
      $id2 = intval(str_replace('stockid2-', '', $key));//quantity
      $id3 = intval(str_replace('stockid3-', '', $key));//sort
      $id4 = intval(str_replace('stockid4-', '', $key));//customid  */

/*        if ($id1 > 0) {
          $value = $this->nullDataEntry($value);
          if (empty($value) || is_null($value)) {$value = 'null';}
           $sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET title = $value WHERE stock_id = " .$id1. " LIMIT 1";
           $db->execute($sql);
          $i++;
        }*/
/*        if ($id2 > 0) {
          $value = doubleval($value);
          if (empty($value) || is_null($value)) {$value = 0;}
      $sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET quantity = $value WHERE stock_id = " .$id2. " LIMIT 1";
            $db->execute($sql);
            $i++;
        }      
        if ($id3 > 0) {
          $value = doubleval($value);
          if (empty($value) || is_null($value)) {$value = 0;}
          $sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET sort = $value WHERE stock_id = " .$id3. " LIMIT 1";
          $db->execute($sql);
          $i++;
        }
        if ($id4 > 0) {
          $value = addslashes($value);
          $value = $this->nullDataEntry($value);
          if (empty($value) || is_null($value)) {$value = 'null';}
          $sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET customid = $value WHERE stock_id = " .$id4. " LIMIT 1";
          $db->execute($sql);
          $i++;
        }*/
    }
    unset ($key, $value);
    $html = print_r($_POST, true);
    $html = "$i DS SAVED";
    return $html;  
}

//Update attribute qty
function updateAttribQty($stock_id = null, $quantity = null) {
  global $db;
  $result = null;

  if (empty($quantity)) {
    $quantity = 0;
  }
  
  if (!empty($stock_id) && is_numeric($stock_id) && is_numeric($quantity)) {
      $query = 'UPDATE `' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . '` SET quantity=:quantity: WHERE stock_id=:stock_id: LIMIT 1';
      $query = $this->query_insert_float($query, ':quantity:', $quantity);
      $query = $db->bindVars($query, ':stock_id:', $stock_id, 'integer');
      $result = $db->execute($query);
  }

  return $result;
}

//New attribute qty insert
//The on duplicate updates an existing record instead of adding a new one
function insertNewAttribQty($products_id = null, $productAttributeCombo = null, $strAttributes = null, $quantity = 0, $customid = null, $skuTitle = null) {
  global $db;
  //$stock = $products_with_attributes_stock_class; // Should replace all instance of $stock with the class variable.
  $productAttributeCombo = $this->nullDataEntry($productAttributeCombo);//sets proper quoting for input
  $skuTitle = $GLOBALS['db']->prepare_input($skuTitle);
  $skuTitle = $this->nullDataEntry($skuTitle);//sets proper quoting for input
  //$customid = addslashes($customid);
  //$customid = $this->nullDataEntry($customid);//sets proper quoting for input
  $strAttributes = $this->nullDataEntry($strAttributes);//sets proper quoting for input
  $result = null;
  
  //Set quantity to 0 if not valid input
  if (!(isset($quantity) && is_numeric($quantity))) {
    $quantity = 0;
  }
  
    // General logic for this section/area:
    /* 
    Ultimate Goal is to either insert a unique record or update an existing unique record.
    Uniqueness was previously identified by maintaining a primary key and three unique keys for the database table; however,
      it has been identified that some database engines do not like operating with the chosen keys (take too much data), 
      therefore, use of php code to work with the data is the route to go if this level of uniqueness is to be maintained.
    Currently there are then four keys of concern.  The first is an auto increment number that is assigned when a record
      is inserted.  This record is primarily for retrieval and reference by other tables as necessary, though deletion of
      a variant and adding it back again will provide a new number even if the newly contained data is the same as the
      old data that was deleted.
    The second "key" is a combination of the products_id field and the stock_attributes field or variant associated with the entry.
    The third "key" is also a combination of the products_id and the stock_attributes but all of that is actually combined
      together into the contents of the field rather than being two independent fields within a record.
    The fourth "key" is a customid that to date has been required to be unique compared other customids stored in the table
      or alternatively null and therefore all variants can have a null value for a customid.
      
    So, the approach considered is to first attempt to see if any of the keys described above (where data has been provided)
      is in the table.  This means that the primary key is typically not provided for inspection.
    If none of the key data is found in the table, then the provided information is new and can be added as a new record.
    If any of the data is found, then the next question is if the provided data uniquely identifies an existing item or if any
      of the other keys can be found to match 2 or more entries.  (ie. customid matches one item, but
      products_id/stock_attributes matches a different one and if further maintained the third key matches yet another record.)
      This is done in such a way that the variant itself is the main goal with the customid being a piece of additional
      information for the variant.  Otherwise, one might think that given a customid that is found to exist that the 
      other data about the variant needs to be modified.  That is not the case in this consideration.  The variant identifies
      the customid not the customid identifying the variant.  
    */
  if (isset($products_id) && is_numeric($products_id) && isset($strAttributes) && is_numeric($quantity)) {
      // Evaluate entry as compared to the desired uniqueness of data in the table.
      /* PRIMARY KEY (`stock_id`),
      UNIQUE KEY `idx_products_id_stock_attributes` (`products_id`,`stock_attributes`),
      UNIQUE KEY `idx_products_id_attributes_id` (`product_attribute_combo`),
      UNIQUE KEY `idx_customid` (`customid`)*/

      if (!isset($productAttributeCombo) || trim($productAttributeCombo) == '' || trim($productAttributeCombo) == "''" || $productAttributeCombo === 'null') {
          $productAttributeCombo = null;
      }
      if (!isset($customid) || trim($customid) == '' || trim($customid) == "''" || $customid === 'null') {
          $customid = null;
      }
      $customid = $this->nullDataEntry($customid);

      // query for any duplicate records based on input where the input has a match to a non-empty key.
      $query = "SELECT count(*) AS total FROM " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " WHERE (products_id = :products_id: AND stock_attributes = :stock_attributes:) " . (isset($productAttributeCombo) ? "OR product_attribute_combo = :product_attribute_combo: " : "") . (isset($customid) /* @TODO if customid is not required to be unique then and with a false */ ? "OR customid = :customid:" : "");
      $query = $db->bindVars($query, ':products_id:', $products_id, 'integer');
      $query = $db->bindVars($query, ':stock_attributes:', $strAttributes, 'passthru');
      $query = $db->bindVars($query, ':product_attribute_combo:', $productAttributeCombo, 'passthru');
      if (!isset($customid)) {
        $query = $db->bindVars($query, ':customid:', 'null', 'noquotestring');
      } else {
        $query = $db->bindVars($query, ':customid:', $customid, 'passthru'); // @TODO Need to also consider ignore NULL style.
      }
      
      $result_keys = $db->Execute($query);
      $insert_result = false;
      $update_result = false;
      
      // No duplication of desired key(s) of table. @TODO: May want to move this down after other reviews so that insertion
      //   code is written one time and use some sort of flag to skip the code that follows the if.
      if ($result_keys->fields['total'] == 0) {
          $insert_result = true;
          
          // Because no duplicates, information is considered new and is to be inserted.
          $query = "INSERT INTO " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " (`products_id`, `product_attribute_combo`, `stock_attributes`, `quantity`, `customid`, `title`) 
           values (:products_id:, :product_attribute_combo:, :stock_attributes:, :quantity:, :customid:, :title:)";
          $query = $db->bindVars($query, ':products_id:', $products_id, 'integer');
          if (!isset($productAttributeCombo)) {
            $query = $db->bindVars($query, ':product_attribute_combo:', 'null', 'noquotestring');
          } else {
            $query = $db->bindVars($query, ':product_attribute_combo:', $productAttributeCombo, 'passthru'); // @TODO Need to also consider ignore NULL style.
          }
          $query = $db->bindVars($query, ':stock_attributes:', $strAttributes, 'passthru');
          $query = $this->query_insert_float($query, ':quantity:', $quantity);
          if (!isset($customid)) {
            $query = $db->bindVars($query, ':customid:', 'null', 'noquotestring');
          } else {
            $query = $db->bindVars($query, ':customid:', $customid, 'passthru'); // @TODO Need to also consider ignore NULL style.
          }
          if (!isset($skuTitle) || trim($skuTitle) == '' || trim($skuTitle) == "''" || $skuTitle === 'null') {
            $query = $db->bindVars($query, ':title:', 'null', 'noquotestring');
          } else {
            $query = $db->bindVars($query, ':title:', $skuTitle, 'passthru'); // @TODO Need to also consider ignore NULL style.
          }
      
          $result = $result_final = $db->Execute($query);
          
      } elseif ($insert_result === false) {
          // A record has been found to match the provided data, now to identify how to proceed with the given data.
          //$result_multiple = null; // Establish base/known value for comparison/review.
          
          // Determine if insertion would fail because of duplicate customid only.
          if (isset($customid)) {
              $query = "SELECT count(*) as total FROM "  . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " WHERE `customid` = :customid:";
              $query = $db->bindVars($query, ':customid:', $customid, 'passthru'); // @TODO Need to also consider ignore NULL style.
              
              $result_customid = $db->Execute($query);
              
              // Found customid in the database; however, need to identify if it belongs to the current data or to some other record. 
             // This check, with the addition of an additional flag, allows the possibility of having a duplicate customid.
              if ($result_customid->fields['total'] > 0) {
                // Identify if records exist that match everything except the customid.
                $query = "SELECT count(*) as total FROM "  . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " WHERE ((products_id = :products_id: AND stock_attributes = :stock_attributes:) " . (isset($productAttributeCombo) ? "OR product_attribute_combo = :product_attribute_combo: " : "") . ") AND `customid` != :customid:";
                  // If find a record, then the customid was assigned to some other product or the two parts of the where
                  //   statement do not uniquely identify a single stock_id.
                $query = $db->bindVars($query, ':products_id:', $products_id, 'integer');
                $query = $db->bindVars($query, ':stock_attributes:', $strAttributes, 'passthru');
                if (!isset($productAttributeCombo)) {
                  $query = $db->bindVars($query, ':product_attribute_combo:', 'null', 'noquotestring');
                } else {
                  $query = $db->bindVars($query, ':product_attribute_combo:', $productAttributeCombo, 'passthru'); // @TODO Need to also consider ignore NULL style.
                }
                $query = $db->bindVars($query, ':customid:', $customid, 'passthru'); // @TODO Need to also consider ignore NULL style.

                $result_customid_unique = $db->Execute($query);
                // If a record is found, then the customid is already used elsewhere and should not be updated.
                // If a record is not found, then the customid is already assigned to this variant and everything else should
                //   be updated.

                if ($result_customid_unique->fields['total'] == 0) {
                    $update_result = true;
                }
                /*if (!$result2->EOF) {
                    
                }*/

                //$result->MoveNext();
              }
          } else {
              // Some level of duplicate exists, not sure if just one record or multiple records.  If one record then
              //   easy, just update that one record, if there are multiple records then the database already has some
              //   level of duplicate key that needs to be addressed as it has not been prevented previously.
              if ($result_keys->fields['total'] == 1) {
                  $update_result = true;
              } else {
                  // Need to handle the duplicate keys issue.
              }
          }
          
          // If no $customid clash (non-empty customid) or if the $customid is empty then update.
          if ($update_result || (isset($result_customid) && $result_customid->fields['total'] == 0) /*!isset($result) || $result->fields['total'] == 0*/) {
              $query = "UPDATE " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " set `quantity` = :quantity:, `customid` = :customid:, `title` = :title: WHERE `products_id` = :products_id: AND `stock_attributes` = :stock_attributes:";
              $query = $this->query_insert_float($query, ':quantity:', $quantity);
              if (!isset($customid)) {
                $query = $db->bindVars($query, ':customid:', 'null', 'noquotestring');
              } else {
                $query = $db->bindVars($query, ':customid:', $customid, 'passthru'); // @TODO Need to also consider ignore NULL style.
              }
              if (!isset($skuTitle) || trim($skuTitle) == '' || trim($skuTitle) == "''" || $skuTitle === 'null') {
                $query = $db->bindVars($query, ':title:', 'null', 'noquotestring');
              } else {
                $query = $db->bindVars($query, ':title:', $skuTitle, 'passthru'); // @TODO Need to also consider ignore NULL style.
              }
              $query = $db->bindVars($query, ':products_id:', $products_id, 'integer');
              $query = $db->bindVars($query, ':stock_attributes:', $strAttributes, 'passthru');

              $result = $db->Execute($query);
          } else {
              // There is a conflict in the customid with the customid being required to be unique.
          }

      }
      // Above replaces this query to provide improved support because some databases do not support the long
      //  key(s) initially implemented.
/*     $query = "insert into ". TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK ." (`products_id`, `product_attribute_combo`, `stock_attributes`, `quantity`, `customid`, `title`) 
           values ($products_id, $productAttributeCombo, $strAttributes, $quantity, $customid, $skuTitle)
               ON DUPLICATE KEY UPDATE 
               `quantity` = $quantity,
               `customid` =  $customid,
               `title` = $skuTitle";
     $result = $db->execute($query);*/
  }
  
  return $result;
}

//IN-WORK New attribute qty insert NEEDS MORE THOUGHT
//NEED one qty for multiple attributes, this does not accomplish this.
//The on duplicate updates an existing record instead of adding a new one
function insertTablePASR($products_id = null, $strAttributes = null, $quantity = null, $customid = null) {
  
  global $db;
  // $stock = $products_with_attributes_stock_class;
  $customid = addslashes($customid);
  $customid = $this->nullDataEntry($customid);//sets proper quoting for input
  $strAttributes = $this->nullDataEntry($strAttributes);//sets proper quoting for input

  //INSERT INTO `znc_products_attributes_stock_relationship` (`products_id`, `products_attributes_id`, `products_attributes_stock_id`) VALUES (226, 1121, 37);
  
  //Table PASR (Inset and get $pasrid for next query)
  if (is_numeric($products_id) && isset($strAttributes)) {
    
    //Get the last records ID
    $query = "select pas.products_attributes_stock_id
          from  ". TABLE_PRODUCTS_ATTRIBUTES_STOCK ."  pas
          order by pas.products_attributes_stock_id desc
          limit 1;";
    $result = $db->execute($query);
    $pasid = $result->fields['products_attributes_stock_id'];
    $pasid = ($pasid +1);//increment to next value
    $pasid = $this->nullDataEntry($pasid);//sets proper quoting for input
    
    $query = "insert into ". TABLE_PRODUCTS_ATTRIBUTES_STOCK_RELATIONSHIP ." (`products_id`,`products_attributes_id`, `products_attributes_stock_id`)
          values ($products_id, $strAttributes, $pasid)
          ON DUPLICATE KEY UPDATE
          `products_id` = $products_id,
          `products_attributes_id` =  $strAttributes;";
    $result = $db->execute($query);
  
    if ($result == 'true') {
      //Get the last records ID
      $query = "select pasr.products_attributes_stock_relationship_id
            from ". TABLE_PRODUCTS_ATTRIBUTES_STOCK_RELATIONSHIP ." pasr
            order by pasr.products_attributes_stock_relationship_id desc
            limit 1;";
      $result = $db->execute($query);
      $pasrid = $result->fields['products_attributes_stock_relationship_id'];
    }
  }
  
  //Table PAS
  if (is_numeric($quantity) && is_numeric($pasrid)) {
    
    $query = "insert into ". TABLE_PRODUCTS_ATTRIBUTES_STOCK ." (`quantity`,`customid`)
          values ($quantity, $customid)
          ON DUPLICATE KEY UPDATE
          `quantity` = $quantity,
          `customid` =  $customid;";
    $result = $db->execute($query);
    
//     //Get the last records ID
//     $query = "select pas.products_attributes_stock_id
//           from  ". TABLE_PRODUCTS_ATTRIBUTES_STOCK ."  pas
//           order by pas.products_attributes_stock_id desc
//           limit 1;";
//     $result = $db->execute($query);
//     $pasid = $result->fields['products_attributes_stock_id'];
    
//     $pasid = $stock->nullDataEntry($pasid);//sets proper quoting for input
    
    //UPDATE `znc_products_attributes_stock_relationship` SET `products_attributes_stock_id`=26 WHERE  `products_attributes_stock_relationship_id`=27 LIMIT 1;
//     $query = "UPDATE ". TABLE_PRODUCTS_ATTRIBUTES_STOCK_RELATIONSHIP ." SET `products_attributes_stock_id` = $pasid
//             where `products_attributes_stock_relationship_id` = $pasrid
//             LIMIT 1;";
//     $result = $db->execute($query);
    
  }
  else {
    //PANIC we had an error!!!
    exit;
  }

   return $result;
}

//IN-WORK New attribute qty insert NEEDS MORE THOUGHT
//products_attributes_stock INWORK New attribute qty insert NEEDS MORE THOUGHT
//NEED one qty for multiple attributes, this does not accomplish this.
//The on duplicate updates an existing record instead of adding a new one
function insertTablePAS($products_id = null, $quantity = null, $customid = null) {

  global $db;
  //$stock = $products_with_attributes_stock_class;
  $customid = $GLOBALS['db']->prepare_input($customid);
  $customid = $this->nullDataEntry($customid);//sets proper quoting for input

//   INSERT INTO `znc_products_attributes_stock` (`products_id`, `quantity`, `customid`) VALUES (226, 636, 'test37');
  
  //Table PASR (Inset and get $pasrid for next query)
  if (is_numeric($products_id)) {

    $query = "insert into ". TABLE_PRODUCTS_ATTRIBUTES_STOCK ." (`products_id`,`quantity`,`customid`)
          values ($products_id, $quantity, $customid)
          ON DUPLICATE KEY UPDATE
          `products_id` = $products_id,
          `quantity` = $quantity,
          `customid` =  $customid;";
    $result = $db->execute($query);

  }
  else {
    //PANIC we had an error!!!
    exit('PANIC we had an error!!!');
  }

  return $result;
}
//ABOVE IN-WORK New attribute qty insert NEEDS MORE THOUGHT

//Update Custom ID of Attribute using the StockID as a key
function updateCustomIDAttrib($stockid = null, $customid = null) {
  //global $db;
  //$stock = $products_with_attributes_stock_class;
  $customid = $GLOBALS['db']->prepare_input($customid);
  $customid = $this->nullDataEntry($customid);//sets proper quoting for input

  if ($customid && is_numeric($stockid) ) {
    $query = 'update ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' set customid = ' . $customid . ' where stock_id = ' . $stockid . ' limit 1';
    $result = $GLOBALS['db']->execute($query);
  }

  return $result;
}

//Update  sku Title of Attribute using the StockID as a key
function updateTitleAttrib($stockid = null, $skuTitle = null) {
//  global $db;
  //$stock = $products_with_attributes_stock_class;
  $skuTitle = $GLOBALS['db']->prepare_input($skuTitle);
  $skuTitle = $this->nullDataEntry($skuTitle);//sets proper quoting for input

  if (isset($skuTitle) && $skuTitle && is_numeric($stockid) ) {
    $query = 'UPDATE ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' SET title = ' . $skuTitle . ' WHERE stock_id = ' . $stockid . ' LIMIT 1';
    $result = $GLOBALS['db']->execute($query);
  }

  return $result;
}

//************************* Select list Function *************************//
//need to update to allow passing the data for $Item "$Item = 'ID:&nbsp;&nbsp;' . $result->fields["products_id"];"
//used to get a rows id number based on the table and column
//$Table = the table in the database to use.
//$Field = the field name from the table to use that has primary key or other uniqueness.
//The $Field is used as the default 'name' for the post event.
//$current = the current value in the database for this item.
//$providedQuery = This is a provided query that overrides the default $Table and $Field,
//note the $Field input (field name) is required to get returned data if name is not set or if there is not a $providedQuery.
function selectItemID($Table, $Field, $current = null, $providedQuery = null, $name= null, $id = null, $class = null, $style = null, $onChange = null) {

  global $db;
  
  if (empty($name)) {
    //use the $Field as the select NAME if no $name is provided
    $name = zen_db_input($Field);
  }
  if (empty($id)) {
    //use the $Field as the select ID if no $id is provided
    $id = zen_db_input($Field);
  }

  if (isset($providedQuery) && $providedQuery) {
    $query = $providedQuery;//provided from calling object
  }
  else{
    $Table = zen_db_input($Table);
    $Field = zen_db_input($Field);
     $query = "SELECT * FROM $Table ORDER BY $Field ASC";
  }

  if (isset($onChange) && $onChange) {
    $onChange = "onchange=\"selectItem()\"";
  }
    
  $class = zen_db_input($class);
  
  $Output = "<SELECT class='".$class."' id='".$id."' name='".$name."' $onChange >";//create selection list
    $Output .= '<option value="" ' . $style . '>Please Select a Search Item Below...</option>';//adds blank entry as first item in list

  /* Fields that may be of use in returned set
  ["products_id"]
  ["products_name"]
  ["products_quantity"]
  ["products_model"]
  ["products_image"]
   */
    $i = 1;
  $result = $db->Execute($query);
     while (!$result->EOF) {

       //set each row background color
       if ($i == 1) {
         $style = 'style="background-color:silver;"';
         $i = 0;
       }
       else{
         $style = null;//'style="background-color:blue;"';
         $i = 1;
       }
       
        $rowID = $result->fields["products_id"];
        $Item = 'ID:&nbsp;&nbsp;' . $result->fields["products_id"];
        $Item .= '&nbsp;&nbsp;Model:&nbsp;&nbsp;' . $result->fields["products_model"];
        $Item .= '&nbsp;&nbsp;Name:&nbsp;&nbsp;' . $result->fields["products_name"];
            
    if (($Item == $current AND $current != NULL) || ($rowID == $current AND $current != NULL)) {
        $Output .= '<option selected="selected" $style value="' . $rowID . '">' . $Item . '</option>';
      }
      else{
        $Output .= '<option ' . $style . ' value="' . $rowID . '">' . $Item . '</option>';
      }

    $result->MoveNext();
  }

  $Output .= "</select>";

  return $Output;
}

//NULL entry for database
function nullDataEntry($fieldtoNULL) {

  //Need to test for absolute 0 (===), else compare will convert $fieldtoNULL to a number (null) and evauluate as a null 
  //This is due to PHP string to number compare "feature"
  if (!empty($fieldtoNULL) || $fieldtoNULL === 0) {
    if ((is_numeric($fieldtoNULL) && ($fieldtoNULL > 0 && strpos($fieldtoNULL, '0') !== 0 || $fieldtoNULL < 0 && strpos($fieldtoNULL, '0') !== 1)) || $fieldtoNULL === 0) {
      $output = $fieldtoNULL;//returns number without quotes
    }
    else{
      $output = "'".$fieldtoNULL."'";//encases the string in quotes
    }
  }
  else{
    $output = 'null';
  }

  return $output;
}

  /* ********************************************************************* */
  /*  Ported from rhuseby: (my_stock_id MOD) and modified for SBA customid */
  /*  Added function to support attribute specific part numbers            */
  /* ********************************************************************* */
  /* 
   * @return the customid from the live database
   * @todo develop a customid return function to retrieve information from the sba orders table.
   * @todo notification message if no attributes are found for a product identified as SBA tracked.
   */
  function zen_get_customid($products_id, $attributes = null) {
    global $db;
    $customid_model_query = null;
    $customid_query = null;
    $products_id = zen_get_prid($products_id);
    $stock_attributes = array();

    // This function probably could be factored down a bit to provide better clarity of what is going on and offer options at 
    //   the end of what to return.  For example, determine the standard products_model response, then the various other
    //   customid combinations available.  Could do this with an array, one key for each type, to then return the result(s) as 
    //   desired to be used on the receiving end.

    if (!$this->zen_product_is_sba($products_id)) {
      $no_attribute_stock_query = 'SELECT products_model
                                   FROM ' . TABLE_PRODUCTS . '
                                   WHERE products_id = :products_id:';
      $no_attribute_stock_query = $db->bindVars($no_attribute_stock_query, ':products_id:', $products_id, 'integer');
      $customid = $db->Execute($no_attribute_stock_query, false, false, 0, true);
      
      return $customid->fields['products_model'];
    }
    
    // check if there are attributes for this product. (Doesn't check against option names nor option values)
     $stock_has_attributes_query = 'SELECT products_attributes_id 
                                   FROM ' . TABLE_PRODUCTS_ATTRIBUTES . ' 
                                   WHERE products_id = :products_id:';
    $stock_has_attributes_query = $db->bindVars($stock_has_attributes_query, ':products_id:', $products_id, 'integer');
    $stock_has_attributes = $db->Execute($stock_has_attributes_query, false, false, 0, true);

    if ($stock_has_attributes->EOF || $stock_has_attributes->RecordCount() == 0 ) {
      
      //if no attributes return products_model.  This ought to not be possible to exist because the SBA table is populated
      //  and code is in place to delete SBA table entries when such attribute data is deleted from within ZC. (Doesn't prevent
      //  an outside source from making modifications such as a selective restore or import/export of the SBA table.)
      // Perhaps a notification message should be shown on the admin if this condition is met.
      $no_attribute_stock_query = 'SELECT products_model 
                                   FROM ' . TABLE_PRODUCTS . ' 
                                   WHERE products_id = :products_id:';
      $no_attribute_stock_query = $db->bindVars($no_attribute_stock_query, ':products_id:', $products_id, 'integer');
      $customid = $db->Execute($no_attribute_stock_query, false, false, 0, true);
      return $customid->fields['products_model'];
    } 
    else {
      // Product is a SBA tracked product and the product has attributes.  Check to see if the product has any customid's assigned.
      $stock_has_customid_query = 'SELECT COUNT(customid) as total
                                   FROM ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . '
                                   WHERE products_id = :products_id:
                                   LIMIT 1';
      $stock_has_customid_query = $db->bindVars($stock_has_customid_query, ':products_id:', $products_id, 'integer');
      $stock_has_customid = $db->Execute($stock_has_customid_query);
      
      // If the product does not have any customid's provide the products_model.
      if ($stock_has_customid->fields['total'] == 0) {
        $stock_no_customid_query = 'SELECT products_model
                                    FROM ' . TABLE_PRODUCTS . '
                                    WHERE products_id = :products_id:';
        $stock_no_customid_query = $db->bindVars($stock_no_customid_query, ':products_id:', $products_id, 'integer');
        $customid = $db->Execute($stock_no_customid_query);
        
        return $customid->fields['products_model'];
      }
      
      if (!empty($attributes) && is_array($attributes)) {
        // check if attribute stock values have been set for the product
        // if there are will we continue, otherwise we'll use product level data
        $attribute_stock = $db->Execute("SELECT stock_id
                                         FROM " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " 
                                         WHERE products_id = " . (int)$products_id . ";");
    
        if ($attribute_stock->RecordCount() > 0) {
          // search for details for the particular attributes combination
          $first_search = ' WHERE options_values_id IN ('.implode(',',zen_db_prepare_input($attributes)).') ';
          
          // obtain the attribute ids
          $query = 'SELECT products_attributes_id
              FROM ' . TABLE_PRODUCTS_ATTRIBUTES . '
                  :first_search:
                  and products_id = :products_id: 
                  order by products_attributes_id;';
          $query = $db->bindVars($query, ':first_search:', $first_search, 'noquotestring');
          $query = $db->bindVars($query, ':products_id:', $products_id, 'integer');
          $attributes_new = $db->Execute($query);
          
          while (!$attributes_new->EOF) {
            $stock_attributes[] = $attributes_new->fields['products_attributes_id'];
            $attributes_new->MoveNext();
          }

          $stock_attributes_comb = implode(',',$stock_attributes);
        }
        
        //Get product model
        $customid_model_query = 'SELECT products_model
                        FROM ' . TABLE_PRODUCTS . '
                        WHERE products_id = ' . (int)$products_id . ';';

        //Get custom id as products_model considering that all of the attributes as a group define the customid.
        $customid_query = 'SELECT customid AS products_model
                    FROM ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . '
                    WHERE products_id = :products_id:
                    AND stock_attributes IN (:stock_attributes:)';
        $customid_query = $db->bindVars($customid_query, ':products_id:', $products_id, 'integer');
        $customid_query = $db->bindVars($customid_query, ':stock_attributes:', $stock_attributes_comb, 'string');
        $customid = $db->Execute($customid_query); //moved to inside this loop as for some reason it has made
        
        if ($attribute_stock->RecordCount() > 0 && $customid->RecordCount() == 0 && zen_not_null($stock_attributes_comb)) { // if a customid does not exist for the combination of attributes then perhaps the attributes are individually listed.
          $customid_query = 'SELECT customid AS products_model
                    FROM ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . '
                    WHERE products_id = :products_id:
                    AND stock_attributes IN (:stock_attributes:)';
          $customid_query = $db->bindVars($customid_query, ':products_id:', $products_id, 'integer');
          $customid_query = $db->bindVars($customid_query, ':stock_attributes:', $stock_attributes_comb, 'passthru');
          $customid = $db->Execute($customid_query); //moved to inside this loop as for some reason it has made
        }
      }// If array
      
//      $customid = $db->Execute($customid_query);
      if ($customid->RecordCount() > 0 && $customid->fields['products_model']) {
      
        //Test to see if a custom ID exists
        //if there are custom IDs with the attribute, then return them.
        $multiplecid = null;
          //mc12345678: Alternative to the below would be to build an array of "products_model" then implode
          //  the array on ', '... Both methods would require stepping through each of the
          //  returned values to build the desired structure.  The below does all of the
          //  implode in one swoop, though does not account for some of the individual items having
          //  a customid while others do not and thus could get x, y, , w, , z as an example.
          //  Either this is something identified up front, is prevented from happening at all, or
          //  is controllable through some form of "switch".  The maximum flexibility of this is
          //  covered by adding an if statement to the below, otherwise if going to build an array
          //  to then be imploded, separate action would need to take place to eliminate the "blanks".
          
          // With zen_not_null statement below, only existing customids will be comma separated.  Here is a possible
          // Switch per "product" or store that allows the customid to be merged or presented in parts.
        while (!$customid->EOF && zen_not_null($customid->fields['products_model'])) {
          $multiplecid .= $customid->fields['products_model'] . ', ';
          $customid->MoveNext();
        }
        $multiplecid = rtrim($multiplecid, ', ');
          
          //return result for display
        return $multiplecid;
      
      }
      else{
        $customid = null;
        //This is used as a fall-back when custom ID is set to be displayed but no attribute is available.
        //Get product model
        $customid_model_query = 'select products_model
                        from '.TABLE_PRODUCTS.'
                        where products_id = :products_id:';
        $customid_model_query = $db->bindVars($customid_model_query, ':products_id:', $products_id, 'integer');                
        
        $customid = $db->Execute($customid_model_query);
        //return result for display
        return $customid->fields['products_model'];
      }
      return null;//nothing to return, should never reach this return
    }
  }//end of function
  
  function zen_copy_sba_products_attributes($products_id_from, $products_id_to) {

    global $db;
    global $messageStack;
    global $copy_attributes_delete_first, $copy_attributes_duplicates_skipped, $copy_attributes_duplicates_overwrite, $copy_attributes_include_downloads, $copy_attributes_include_filename;
    global $zco_notifier;
    global $products_with_attributes_stock_admin_observe;
  
    if (file_exists(DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/product_sba.php')) {
      include_once(DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/product_sba.php'); 
    }


// Check for errors in copy request
    if ( (!zen_has_product_attributes($products_id_from, 'false') or !zen_products_id_valid($products_id_to)) or $products_id_to == $products_id_from or !$this->zen_product_is_sba($products_id_from) ) {
      if ($products_id_to == $products_id_from) {
        // same products_id
        $messageStack->add_session('<b>WARNING: Cannot copy from Product ID #' . $products_id_from . ' to Product ID # ' . $products_id_to . ' ... No copy was made' . '</b>', 'caution');
      } else {
        if (!zen_has_product_attributes($products_id_from, 'false')) {
          // no attributes found to copy
          $messageStack->add_session('<b>WARNING: No Attributes to copy from Product ID #' . $products_id_from . ' for: ' . zen_get_products_name($products_id_from) . ' ... No copy was made' . '</b>', 'caution');
        } else {
          if (!$this->zen_product_is_sba($products_id_from)) {
            $messageStack->add_session('<b>WARNING: No Stock By Attributes to copy from Product ID #' . $products_id_from . ' for: ' . zen_get_products_name($products_id_from) . ' ... No SBA attributes copy was made' . '</b>', 'caution');
          } else {
            // invalid products_id
            $messageStack->add_session('<b>WARNING: There is no Product ID #' . $products_id_to . ' ... No copy was made' . '</b>', 'caution');
          }
        }
      }
    } else {
// FIX HERE - remove once working

// check if product already has attributes
      $check_attributes = zen_has_product_attributes($products_id_to, 'false');

      if ($copy_attributes_delete_first=='1' and $check_attributes == true) {
// die('DELETE FIRST - Copying from ' . $products_id_from . ' to ' . $products_id_to . ' Do I delete first? ' . $copy_attributes_delete_first);
        // delete all attributes first from products_id_to

//        $zco_notifier->notify('NOTIFY_ATTRIBUTE_CONTROLLER_DELETE_ALL', array('pID' => $_POST['products_filter']));
        $products_with_attributes_stock_admin_observe->updateNotifyAttributeControllerDeleteAll($this, 'NOTIFY_ATTRIBUTE_CONTROLLER_DELETE_ALL', array('pID' => $products_id_to));

//        $db->Execute("delete from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " where products_id = '" . (int)$products_id_to . "'");

      }

// get attributes to copy from
      $products_copy_from = $db->Execute("select * from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " where products_id='" . (int)$products_id_from . "'" . " order by stock_id");

      while ( !$products_copy_from->EOF ) {
// This must match the structure of your products_attributes table

        $update_attribute = false;
        $add_attribute = true;

        $attributes_copy_from_ids = array_map('trim', explode(',', $products_copy_from->fields['stock_attributes']));
        $attributes_copy_from_ids_data = array();
        
        // Generate array of attributes from the SBA data record
        foreach ($attributes_copy_from_ids as $key => $value) {
          $attributes_copy_from_ids_data_query = $db->Execute("SELECT options_id, options_values_id FROM " . TABLE_PRODUCTS_ATTRIBUTES . " WHERE products_attributes_id = " . (int)$value, false, false, 0, true);

          if (array_key_exists($attributes_copy_from_ids_data_query->fields['options_id'], $attributes_copy_from_ids_data)) {
            // Only handles a depth of one for the array, it does not handle an array of arrays of arrays or a third level of arrays.  Not currently
            //  as of ZC 1.5.5 considered a core feature of ZC, although some plugins might work that deep or deeper.
            if (is_array($attributes_copy_from_ids_data[$attributes_copy_from_ids_data_query->fields['options_id']]) 
                && !empty($attributes_copy_from_ids_data[$attributes_copy_from_ids_data_query->fields['options_id']])) {
                  // Array of arrays already exists and has at least one element associated with it.
              $attributes_copy_from_ids_data[$attributes_copy_from_ids_data_query->fields['options_id']][] = $attributes_copy_from_ids_data_query->fields['options_values_id'];
            } else {
              // Value was previously found/identified; however, now this should be identified as an array of arrays, so transition to that setup.
              $attributes_copy_from_ids_data[$attributes_copy_from_ids_data_query->fields['options_id']] = array($attributes_copy_from_ids_data[$attributes_copy_from_ids_data_query->fields['options_id']], $attributes_copy_from_ids_data_query->fields['options_values_id']);
            }  
          } else {
            $attributes_copy_from_ids_data[$attributes_copy_from_ids_data_query->fields['options_id']] = $attributes_copy_from_ids_data_query->fields['options_values_id'];
          }
        }
        unset($attributes_copy_from_ids_data_query);
        unset($attributes_copy_from_ids);
        unset($key);
        unset($value);

        $check_duplicate = $_SESSION['pwas_class2']->zen_get_sba_stock_attribute_id($products_id_to, $attributes_copy_from_ids_data, 'products');
        
        
        
        /* 
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

      */
        if ($check_attributes == true) {
          if (!isset($check_duplicate) || $check_duplicate === false) {
            $update_attribute = false;
            $add_attribute = true;
          } else {
            if (!isset($check_duplicate) || $check_duplicate === false) {
              $update_attribute = false;
              $add_attribute = true;
            } else {
              $update_attribute = true;
              $add_attribute = false;
            }
          }
        } else {
          $update_attribute = false;
          $add_attribute = true;
        }

// die('UPDATE/IGNORE - Checking Copying from ' . $products_id_from . ' to ' . $products_id_to . ' Do I delete first? ' . ($copy_attributes_delete_first == '1' ? TEXT_YES : TEXT_NO) . ' Do I add? ' . ($add_attribute == true ? TEXT_YES : TEXT_NO) . ' Do I Update? ' . ($update_attribute == true ? TEXT_YES : TEXT_NO) . ' Do I skip it? ' . ($copy_attributes_duplicates_skipped=='1' ? TEXT_YES : TEXT_NO) . ' Found attributes in From: ' . $check_duplicate);

       if ($copy_attributes_duplicates_skipped == '1' and $check_duplicate !== false and $check_duplicate !== NULL) {
          // skip it
            $messageStack->add_session(TEXT_ATTRIBUTE_COPY_SKIPPING . $products_copy_from->fields['products_attributes_id'] . ' for Products ID#' . $products_id_to, 'caution');
        } else {
          $products_attributes_id_to = $_SESSION['pwas_class2']->zen_get_sba_stock_attribute($products_id_to, $attributes_copy_from_ids_data, 'products', true);
          $products_attributes_combo = $_SESSION['pwas_class2']->zen_get_sba_attribute_ids($products_id_to, $attributes_copy_from_ids_data, 'products', true);

          if ($add_attribute == true) {
            // New attribute - insert it
            $db->Execute("INSERT INTO " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " (products_id, product_attribute_combo, stock_attributes, quantity, sort, title)
                          VALUES (" . (int)$products_id_to . ",
            '" . (int)$products_id_to . '-' . implode('-', $products_attributes_combo) . "',
            '" . $products_attributes_id_to . "',
            '" . (float)$products_copy_from->fields['quantity'] . "',
            '" . $products_copy_from->fields['sort'] . "',
            '" . $products_copy_from->fields['title'] . "')");

            $messageStack->add_session(TEXT_SBA_ATTRIBUTE_COPY_INSERTING . $products_copy_from->fields['stock_id'] . ' for Products ID#' . $products_id_to, 'caution');
          }
          if ($update_attribute == true) {
            // Update attribute - Just attribute settings not ids
            $db->Execute("UPDATE " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " SET
            product_attribute_combo='" . (int)$products_id_to . '-' . implode('-', $products_attributes_combo) . "',
            quantity='" . (float)$products_copy_from->fields['quantity'] . "',
            sort='" . $products_copy_from->fields['sort'] . "',
            title='" . $products_copy_from->fields['title'] . "'"
             . " WHERE products_id=" . (int)$products_id_to . " AND stock_attributes= '" . $products_attributes_id_to . "'");
//             . " WHERE products_id='" . $products_id_to . "'" . " AND options_id= '" . $products_copy_from->fields['options_id'] . "' AND options_values_id='" . $products_copy_from->fields['options_values_id'] . "' AND attributes_image='" . $products_copy_from->fields['attributes_image'] . "' AND attributes_price_base_included='" . $products_copy_from->fields['attributes_price_base_included'] .  "'");

            $messageStack->add_session(TEXT_SBA_ATTRIBUTE_COPY_UPDATING . $products_copy_from->fields['stock_id'] . ' for Products ID#' . $products_id_to, 'caution');
          }
        }

        $products_copy_from->MoveNext();
      } // end of products with sba attributes while loop

       // reset products_price_sorter for searches etc.
       zen_update_products_price_sorter($products_id_to);
    } // end of no attributes or other errors
  } // eof: zen_copy_sba_products_attributes function
  
////
// Return a product ID with attributes as provided on the store front
    function zen_sba_get_uprid($prid, $params) {
      if ( (is_array($params)) && (!strstr($prid, ':')) ) {
        $uprid = $prid;
        foreach($params as $option => $value) {
          if (is_array($value)) {
            foreach($value as $opt => $val) {
              $uprid = $uprid . '{' . $option . '}' . trim($opt);
            }
          } else {
          //CLR 030714 Add processing around $value. This is needed for text attributes.
              $uprid = $uprid . '{' . $option . '}' . trim($value);
          }
        }      //CLR 030228 Add else stmt to process product ids passed in by other routines.
        $md_uprid = '';
  
        $md_uprid = md5($uprid);
        return $prid . ':' . $md_uprid;
      } else {
        return $prid;
      }
      
    }

function convertDropdownsToSBA()
{
  global $db, $resultMmessage, $failed;

  if (defined('PRODUCTS_OPTIONS_TYPE_SELECT_SBA')) {
    $sql = "UPDATE " . TABLE_PRODUCTS_OPTIONS . " SET `products_options_type` = :products_options_type_select_sba:
            WHERE `products_options_type` = :products_options_type_select:";

    $sql = $db->bindVars($sql, ':products_options_type_select_sba:', PRODUCTS_OPTIONS_TYPE_SELECT_SBA, 'integer');
    $sql = $db->bindVars($sql, ':products_options_type_select:', PRODUCTS_OPTIONS_TYPE_SELECT, 'integer');

    $db->Execute($sql);
    if ($db->error) {
      $msg = ' Error Message: ' . $db->error;
      $failed = true;
    }
  } else {
    $msg = ' Error Message: PRODUCTS_OPTIONS_TYPE_SELECT_SBA not defined.';
    $failed = true;
  }

  if (isset($resultMmessage)) {
    array_push($resultMmessage, 'product_attribute_combo field updated ' . $msg);
  }

}

function convertSBAToSBA()
{
  global $db, $resultMmessage, $failed;

  if (defined('PRODUCTS_OPTIONS_TYPE_SELECT_SBA')) {

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

      if ($db->error) {
        $msg = ' Error Message: ' . $db->error;
        $failed = true;

        break;
      }
    }
    unset($results_track);

  } else {
    $msg = ' Error Message: PRODUCTS_OPTIONS_TYPE_SELECT_SBA not defined.';
    $failed = true;
  }

  if (isset($resultMmessage)) {
    array_push($resultMmessage, 'product_attribute_combo field updated ' . $msg);
  }
}

function convertNonSBAToDropdown()
{
  global $db, $resultMmessage, $failed;

  if (defined('PRODUCTS_OPTIONS_TYPE_SELECT_SBA')) {

    $results_track = array(); // Array to track what has been identified.

    // Need to identify which option values are listed in the SBA table and then update them if they are a dropdown select.
    $sql = 'SELECT stock_attributes FROM ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' WHERE stock_attributes != \'\'';

    if (method_exists($db, 'ExecuteNoCache')) {
      $results = $db->ExecuteNoCache($sql);
    } else {
      $results = $db->Execute($sql, false, false, 0, true);
    }

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

    //sort($results_track); // This will sequence the option_ids so that the "completion" point is better understood.

    foreach ($results_track as $result_key => $result)
    {
      $sql = "UPDATE " . TABLE_PRODUCTS_OPTIONS . " po SET po.products_options_type = :products_options_type:
              WHERE `products_options_id` = :products_options_id:";

      $sql = $db->bindVars($sql, ':products_options_type:', PRODUCTS_OPTIONS_TYPE_SELECT, 'integer');
      $sql = $db->bindVars($sql, ':products_options_id:', $result, 'integer');

      $db->Execute($sql);

      if ($db->error) {
        $msg = ' Error Message: ' . $db->error;
        $failed = true;

        break;
      }
    }
    unset($results_track);

  } else {
    $msg = ' Error Message: PRODUCTS_OPTIONS_TYPE_SELECT_SBA not defined.';
    $failed = true;
  }

  if (isset($resultMmessage)) {
    array_push($resultMmessage, 'product_attribute_combo field updated ' . $msg);
  }
}

    
  function zen_product_is_sba($product_id) {
    global $db;
    
    if (!isset($product_id) && !is_numeric(zen_get_prid($product_id))) {
      return null;
    }
    
    $inSBA_query = 'SELECT * 
                    FROM information_schema.tables
                    WHERE table_schema = :your_db:
                    AND table_name = :table_name:
                    LIMIT 1;';
    $inSBA_query = $db->bindVars($inSBA_query, ':your_db:', DB_DATABASE, 'string');
    $inSBA_query = $db->bindVars($inSBA_query, ':table_name:', TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'string');
    $SBA_installed = $db->Execute($inSBA_query, false, false, 0, true);
    
    if (!$SBA_installed->EOF && $SBA_installed->RecordCount() > 0) {
      $isSBA_query = 'SELECT COUNT(stock_id) as total 
                      FROM ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' 
                      WHERE products_id = :products_id: 
                      LIMIT 1;';
      $isSBA_query = $db->bindVars($isSBA_query, ':products_id:', $product_id, 'integer');
      $isSBA = $db->Execute($isSBA_query);
      
      if ($isSBA->fields['total'] > 0) {
        return true;
      } else {
        return false;
      }
    }
    
    return false;
  }
  
}//end of class
