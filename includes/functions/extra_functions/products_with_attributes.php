<?php
/**
 * @package includes/functions/extra_functions
 * products_with_attributes.php
 *
 * @package functions/extra_functions
 * @copyright Copyright 2003-2014 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id:  $
 * 
 * Stock by Attributes 1.5.4 :  15-11-14 mc12345678
 */

//test for multiple entry of same product in customer's shopping cart
//This does not yet account for multiple quantity of the same product (2 of a specific attribute type, but instead 2 different types of attributes.)
function cartProductCount($products_id){
  
  global $db;
  $products_id = zen_get_prid($products_id);

  $query = 'select products_id
                    from ' . TABLE_CUSTOMERS_BASKET . '
                    where products_id like ":products_id::%" and customers_basket_id = :cust_bask_id:';
  $query = $db->bindVars($query, ':products_id:', $products_id, 'integer');
  $query = $db->bindVars($query, ':cust_bask_id:', $_SESSION['cart']->cartID, 'integer');
          
  $productCount = $db->Execute($query);
            
  return $productCount->RecordCount();
}

/*  Update for Stock by Attributes
 *  Output a form pull down menu
 *  Pulls values from a passed array, with the indicated option pre-selected
 *  
 * This is a copy of the default function from "html_output.php", this version has been extended to support additional parameters.
 * These updates could be rolled back into the core, but to avoid unexpected issues at this time it is separate.
 * HTML-generating functions used with products_with_attributes
 *
 * Use Jquery to change image 'SBA_ProductImage' on selection change
 */
  function zen_draw_pull_down_menu_SBAmod($name, $values, $default = '', $parameters = '', $required = false, $disable = null, $options_menu_images = null) {
		
  	global $template_dir;
  	$tmp_attribID = trim($name, 'id[]');//used to get the select ID reference to be used in jquery
  	$field = /*'<script ' . *//*src="'.DIR_WS_TEMPLATES . $template_dir . '/jscript/jquery-1.10.2.min.js"*//* '></script> */
			  '<script type="text/javascript">
	  			$(function(){
					$("#attrib-'.$tmp_attribID.'").on("click", function(){
						$("#SBA_ProductImage").attr("src", $(this).find(":selected").attr("data-src"));
					});
				});
			</script>';
  					
  	$field .= '<select name="' . zen_output_string($name) . '" onclick=""';

    if (zen_not_null($parameters)) {$field .= ' ' . $parameters;}

    $field .= '>' . "\n";

    if (empty($default) && isset($GLOBALS[$name]) && is_string($GLOBALS[$name]) ) {$default = stripslashes($GLOBALS[$name]);}

    for ($i=0, $n=sizeof($values); $i<$n; $i++) {
      $field .= '  <option value="' . zen_output_string($values[$i]['id']) . '"';
      if ($default == $values[$i]['id']) {
        $field .= ' selected="selected"';
      }
      
      //"Stock by Attributes" // Need to determine this being disabled by a 
      // numerical method rather than a text possessing method.  If PWA_OUTOF_STOCK is not present then the item may not be disabled... :/
      if( $disable && strpos($values[$i]['text'], trim(PWA_OUT_OF_STOCK)) ){
        $field .= $disable;
      }
      //add image link if available
      if( !empty($options_menu_images[$i]['src']) ){
        $field .= ' data-src="' . $options_menu_images[$i]['src'] . '"';
      }
      
      //close tag and display text
//      $field .= '>' . zen_output_string($values[$i]['text'], array('"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;')) . '</option>' . "\n";
      $field .= '>' . zen_output_string_protected($values[$i]['text']) . '</option>' . "\n";
    }
    
    $field .= '</select>' . "\n";

    if ($required == true) $field .= TEXT_FIELD_REQUIRED;

    return $field;
  }

  /* ********************************************************************* */
  /*  Ported from rhuseby: (my_stock_id MOD) and modified for SBA customid */
  /*  Added function to support attribute specific part numbers            */
  /* ********************************************************************* */
  function zen_get_customid($products_id, $attributes = null) {
    global $db;
    $customid_model_query = null;
    $customid_query = null;
    $products_id = zen_get_prid($products_id);
  $customid = null;
  
    // check if there are attributes for this product
   $stock_has_attributes_query = 'select products_attributes_id 
                        from '.TABLE_PRODUCTS_ATTRIBUTES.' 
                        where products_id = :products_id:';
  $stock_has_attributes_query = $db->bindVars($stock_has_attributes_query, ':products_id:', $products_id, 'integer');
  $stock_has_attributes = $db->Execute($stock_has_attributes_query);

    if ( $stock_has_attributes->RecordCount() < 1 ) {
      
        //if no attributes return products_model
      $no_attribute_stock_query = 'select products_model 
                      from '.TABLE_PRODUCTS.' 
                      where products_id = :products_id:';
    $no_attribute_stock_query = $db->bindVars($no_attribute_stock_query, ':products_id:', $products_id, 'integer');
      $customid = $db->Execute($no_attribute_stock_query);
      return $customid->fields['products_model'];
    } 
    else {
      
      if(is_array($attributes) and sizeof($attributes) > 0){
        // check if attribute stock values have been set for the product
        // if there are will we continue, otherwise we'll use product level data
      $attribute_stock = $db->Execute("select stock_id 
                          from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " 
                          where products_id = " . (int)$products_id . ";");
    
        //Why not left join this below query into the above or why even have a separate/second query? Especially seeing that $attributes_stock is never used in the below results...  
        if ($attribute_stock->RecordCount() > 0) {
          // search for details for the particular attributes combination
            $first_search = 'where options_values_id in ("'.implode('","',$attributes).'")';
          
          // obtain the attribute ids
          $query = 'select products_attributes_id 
              from '.TABLE_PRODUCTS_ATTRIBUTES.' 
                  '.$first_search.' 
                  and products_id='.$products_id.' 
                  order by products_attributes_id;';
          $attributes_new = $db->Execute($query);
          
          while(!$attributes_new->EOF){
            $stock_attributes[] = $attributes_new->fields['products_attributes_id'];
            $attributes_new->MoveNext();
          }

        $stock_attributes = implode(',',$stock_attributes);
        }
        
        //Get product model
        $customid_model_query = 'select products_model 
                        from '.TABLE_PRODUCTS.' 
                        where products_id = '. (int)$products_id . ';';

        //Get custom id as products_model
        $customid_query = 'select customid as products_model
                    from '.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.' 
                    where products_id = :products_id: 
                    and stock_attributes in ( ":stock_attributes:");'; 
        $customid_query = $db->bindVars($customid_query, ':products_id:', $products_id, 'integer');
        $customid_query = $db->bindVars($customid_query, ':stock_attributes:', $stock_attributes, 'passthru');
      $customid = $db->Execute($customid_query); //moved to inside this loop as for some reason it has made
      // a difference in the code where there would be an error with it below...
      }
      
      
      if($customid->fields['products_model']){
      
        //Test to see if a custom ID exists
        //if there are custom IDs with the attribute, then return them.
          $multiplecid = null;
          while(!$customid->EOF){
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
      return;//nothing to return, should never reach this return
    }
  }//end of function

  /*
   * Function to return the desired stock_attribute field for use with the SBA table products_with_attributes_stock.
   * 
   * @access  public
   * @param   integer   $products_id      The product id of the product on which to obtain the stock attribute.
   * @param   array     $attribute_list   The attribute array of the product identified in products_id
   * @param   string    $from             The source of the attribute list as created differently in order class than shopping_cart class.
   * @returns string    $stock_attributes A comma separated (if >1 attribute) string of the products_attributes_id sorted by products_attributes_id. This is the current set of information stored in the SBA table for stock_attributes.
   */
  function zen_get_sba_stock_attribute($products_id, $attribute_list = array(), $from = 'order'){
  global $db;

    $temp_attributes = array();
    $specAttributes = array();
    $stock_attributes_list = array();
    $stock_attributes = '';
    $multi = (sizeof($attribute_list) > 1 ? true : false);
    
    if (isset($attribute_list) && is_array($attribute_list) && (($k = sizeof($attribute_list)) > 0)) {
      if ($from == 'order') {
        for ($j = 0; $j < $k; $j++) {
          if (true) { // mc12345678 Here is one place where verification can be performed as to whether a particular attribute should be added.  This is probably the best place to do the review because all aspects of the attribute are available.
            $temp_attributes[$attribute_list[$j]['option_id']] = $attribute_list[$j]['value_id'];
          }
        }
        $attribute_list = $temp_attributes;
      } 
      
      $stock_attributes_list = zen_get_sba_attribute_ids($products_id, $attribute_list, $from);
      
      
//      $_SESSION['attributes_list_'. (int)$products_id] = $stock_attributes_list;
          
      $stock_attributes = implode(',',$stock_attributes_list);
    }
    
    return $stock_attributes;
  }

  /*
   * Function to return the stock_attribute_id field from the SBA table products_with_attributes_stock. Makes a call to zen_get_sba_stock_attribute in order to identify data to help with this search.
   * 
   * @access  public
   * @param   integer   $products_id      The product id of the product on which to obtain the stock attribute.
   * @param   array     $attribute_list   The attribute array of the product identified in products_id
   * @returns array     stock_id          The value of the unique id in the SBA table products_with_attributes_stock
   */
  function zen_get_sba_attribute_ids($products_id, $attribute_list = array(), $from = 'order'){
    global $db;

    $temp_attributes = array();
    $specAttributes = array();
    $stock_attributes_list = array();
    $stock_attributes = '';
    $multi = (sizeof($attribute_list) > 1 ? true : false);

    $products_id = zen_get_prid($products_id);

//    $stock_attribute = zen_get_sba_stock_attribute($products_id, $attribute_list, $from);

    // If the product is not tracked by SBA, then return null.
    if (!zen_product_is_sba($products_id)) {
      return NULL;
    }

   // Need to evaluate if product information provided is SBA tracked in case the page is posted with incorrect attributes as a separate check.
    if (isset($attribute_list) && is_array($attribute_list) && sizeof($attribute_list) > 0) {
      // Should check against the product designation, what option names should be expected.
      // Then compare the provided option names against the expected list.
      // For those option names that were not provided evaluate if their omission
      //  is considered acceptable (option name is a text field but nothing was provided)
      // With the option names "complete" check against the SBA table as a unit.
      // If not found as a unit, then check against individual pieces.


      //For products with associated attributes, to do the following:
      //  1. Check if the attribute has been added to the SBA Stock Page.
      //  2. Check if the attribute(s) are listed in seperate rows or are combined into a single row.
      // mc12345678 - The following seems like it could be compressed more/do less searches.  Now that this seems to work, there is some code that can be compressed.

      // check if any attribute stock values have been set for the product in the SBA table, if not do the else part

        // prepare to search for details for the particular attribute combination passed as a parameter
//      if (zen_product_is_sba($products_id)) {

        // prepare to search for details for the particular attribute combination passed as a parameter


        if (defined('TEXT_PREFIX')) {
          $text_prefix = TEXT_PREFIX;
        } else {
          $text_prefix = 'txt_';
        }

        if (defined('UPLOAD_PREFIX')) {
          $file_prefix = UPLOAD_PREFIX;
        } else {
          $file_prefix = 'upload_';
        }

        foreach($attribute_list as $optid => $optvalid) {
          if (preg_match('/'.$text_prefix.'/', $optid)) {
            if (defined('PRODUCTS_OPTIONS_VALUE_TEXT_ID')) {
              $text_value = PRODUCTS_OPTIONS_VALUE_TEXT_ID;
            } else {
              $text_value = '0';
            }
            $specAttributes[str_replace($text_prefix,'',$optid)] = $text_value;
          } elseif (preg_match('/'.$file_prefix.'/', $optid)) {
            if (defined('PRODUCTS_OPTIONS_VALUE_TEXT_ID')) {
              $file_value = PRODUCTS_OPTIONS_VALUE_TEXT_ID;
            } else {
              $file_value = '0';
            }
            $specAttributes[str_replace($file_prefix,'',$optid)] = $file_value;
          } elseif ($optvalid == 0) {
            $specAttributes[$optid] = $optvalid;
          } elseif (true) { // mc12345678 Here is one place where verification can be performed as to whether a particular attribute should be added.  This is probably the best place to do the review because all aspects of the attribute are available.
            $temp_attributes[] = $optvalid;
          }
        }

        if ($multi) {
          $first_search = 'where options_values_id in (' . implode(',', $temp_attributes) . ')';  // This helps make a list of items where the options_values_id is compared to each individual attribute ("x","y","z")
        } else {
          $first_search = 'where options_values_id = ' . implode(',', $temp_attributes);  // This helps make a list of items where the options_values_id is compared to each individual attribute ("x","y","z")
          if (implode(',', $temp_attributes) == "") {
            $first_search = 'where options_values_id = 0';  // This helps make a list of items where the options_values_id is compared to each individual attribute ("x","y","z")
          }
//            $first_search = 'where options_values_id = "' . $attribute . '"';
        }

        // obtain the attribute ids
        $query = 'select products_attributes_id 
                  from ' . TABLE_PRODUCTS_ATTRIBUTES . ' 
                  :first_search: 
                  and products_id = :products_id: 
                  :specAttributes:
                  order by products_attributes_id';

        $query = $db->bindVars($query, ':first_search:', $first_search, 'passthru');

      if (sizeof($specAttributes) > 0) {
        $specAttrib_query = '';
        foreach ($specAttributes as $optid=>$optvalid) {
          $specAttrib_query .= ' OR (options_id = :optid: AND options_values_id = :optvalid: AND products_id = :products_id:) ';
          $specAttrib_query = $db->bindVars($specAttrib_query, ':optid:' , $optid, 'integer');
          $specAttrib_query = $db->bindVars($specAttrib_query, ':optvalid:' , $optvalid, 'integer');
        }
        $query = $db->bindVars($query, ':specAttributes:', $specAttrib_query, 'noquotestring');
      } else {
        $query = $db->bindVars($query, ':specAttributes:', '', 'noquotestring');
      }
      $query = $db->bindVars($query, ':products_id:', $products_id, 'integer');

      $attributes_new = $db->Execute($query);

      while (!$attributes_new->EOF) {
        if (true) { // mc12345678 Here is one place where verification can be performed as to whether a particular attribute should be added.
          $stock_attributes_list[] = $attributes_new->fields['products_attributes_id'];
        }
        $attributes_new->MoveNext();
      }
      
      if (sizeof(stock_attributes_list) > 0) {
        return $stock_attributes_list;
      } else {
        return false;
      }
    }
  }

  /*
   * Function to return the stock_attribute_id field from the SBA table products_with_attributes_stock. Makes a call to zen_get_sba_stock_attribute in order to identify data to help with this search.
   * 
   * @access  public
   * @param   integer   $products_id      The product id of the product on which to obtain the stock attribute.
   * @param   array     $attribute_list   The attribute array of the product identified in products_id
   * @returns integer   stock_id          The value of the unique id in the SBA table products_with_attributes_stock
   */
  function zen_get_sba_stock_attribute_id($products_id, $attribute_list = array(), $from = 'order'){
    global $db;

    /*if (isset($attribute_list) && (($k = sizeof($attribute_list)) > 0)) {*/

/*
  Discussion:
  This section of code should return the one or more stock_ids that encompass
  all of the provided attributes one time only and only those attributes 
  (or if not to be considered a stock related item then can be duplicated).
  By doing so, one can identify that the attributes provided are captured
  and valid for a checkout or other next action.
  
  So to accomplish this: Need to find the Union of variants where those that 
  are stock bearing do not intersect and where all of the attributes are present.
  May be best to identify all of the variants that contain at least the 
  attribute(s) provided.  This can be done currently with 
  zen_get_sba_ids_from_attribute($attribute_id, $products_id, stock_attribute_unique)
  
  
  The union of these should be at least all attributes, if
  that is not true, then an attribute is provided that is not tracked.
  After have a list of all associated variants, need to find the one or
  combination of them that provides a "complete" combination.


Approach(es) on this... 

Of the attributes provided, determine the number of those attributes that are
  stock based.  With the set of proposed attributes for the product (being
  stock based) test against the proposed attributes against the stored list 
  to see if the current selection is in the stored list.  If it is, then done.
  If the 


*/
/*      $stock_attribute = zen_get_sba_stock_attribute($products_id, $attribute_list, $from);
      $query = 'select stock_id from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . 
                ' where `stock_attributes` = :stock_attribute: and products_id = :products_id:';
      $query = $db->bindVars($query, ':stock_attribute:', $stock_attribute, 'string');
      $query = $db->bindVars($query, ':products_id:', $products_id, 'integer');

      $stock_id = $db->Execute($query);

      if ($stock_id->RecordCount() > 1) {
        echo 'This is an error situation, as only one record should be returned.  More than one stock id was returned which should not be possible.';
      } else {
        return $stock_id->fields['stock_id'];
      }
    }
    
    return;*/

    $temp_attributes = array();
    $specAttributes = array();
    $stock_attributes_list = array();
    $stock_attributes = '';
//    $multi = (sizeof($attribute_list) > 1 ? true : false);

    $products_id = zen_get_prid($products_id);

    $stock_attribute = zen_get_sba_stock_attribute($products_id, $attribute_list, $from);
    if (!zen_product_is_sba($products_id)) {
      return NULL;
    }

    $stock_attributes_list = zen_get_sba_attribute_ids($products_id, $attribute_list, $from);
    // Summary of the above, if the product is not tracked by SBA, then return null.

   // Need to evaluate if product is SBA tracked in case the page is posted without the attributes as a separate check.

      if (sizeof($stock_attributes_list) == 1) {
        //         echo '<br />Single Attribute <br />';
        $stock_attributes = $stock_attributes_list[0];
        // create the query to find single attribute stock
        $stock_query = 'select stock_id, quantity as products_quantity from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id = :products_id: and stock_attributes like :stock_attributes:';
        $stock_query = $db->bindVars($stock_query, ':products_id:', $products_id, 'integer');
        $stock_query = $db->bindVars($stock_query, ':stock_attributes:', $stock_attributes, 'passthru');
        $stock_values = $db->Execute($stock_query);
        // return the stock qty for the attribute
        if (!$stock_values->EOF) {
          return array($stock_values->fields['stock_id']);
        } else {
          return false;
        }
      } elseif (sizeof($stock_attributes_list) > 1) {
        //       echo '<br />Multiple attributes <br />';
        $stockResult = null;
        //This part checks for "attribute combinations" in the SBA table. (Multiple attributes per Stock ID Row, Multiple Attribute types in stock_attributes Field  i.e, 123,321,234)
        $stock_attributes = implode(',', $stock_attributes_list);
        // create the query to find attribute stock
        $stock_query = 'select stock_id, quantity as products_quantity from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id = :products_id: and stock_attributes like :TMPstock_attributes:';
        $stock_query = $db->bindVars($stock_query, ':products_id:', $products_id, 'integer');
        $stock_query = $db->bindVars($stock_query, ':TMPstock_attributes:', $stock_attributes, 'string');
        // get the stock value for the combination
        $stock_values = $db->Execute($stock_query);
        $stockResult = $stock_values->fields['stock_id'];

        if (!$stock_values->EOF && $stock_values->RecordCount() == 1) {
          //return the stock for "attribute combinations"
          return array($stockResult);
        } else {
          //This part is for attributes that are all listed separately in the SBA table for the product

          $stockResult = null;
          $returnedStock = null;
          $i = 0;
          
          $stockResultArray = array();
          $notAccounted = false;
          
          foreach ($stock_attributes_list as $eachAttribute) {
            // create the query to find attribute stock
            //echo '<br />Multiple Attributes selected (one attribute type per product)<br />';
            $stock_query = 'select stock_id, quantity as products_quantity from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id = :products_id: and stock_attributes like :eachAttribute:';
            $stock_query = $db->bindVars($stock_query, ':products_id:', $products_id, 'integer');
            $stock_query = $db->bindVars($stock_query, ':eachAttribute:', $eachAttribute, 'passthru');

            // get the stock value for the combination
            $stock_values = $db->Execute($stock_query);
            $stockResult = $stock_values->fields['products_quantity'];
            $stockResultArray[] = $stock_values->fields['stock_id'];

            if ($stock_values->EOF) {
              $notAccounted = true;
            }

            //special test to account for qty when all attributes are listed seperetly
            if (!zen_not_null($returnedStock) && $i == 0) {
              //set initial value
              if ($stock_values->EOF) {
                $returnedStock = 0;
              } else {
                $returnedStock = $stockResult;
              }
            } elseif ($returnedStock > $stockResult) {
              //update for each attribute, if qty is lower than the previous one
              $returnedStock = $stockResult;
            } // end if first stock item of attribute
            $i++;
          } // end for each attribute.

          /*foreach ($stockResultArray as $stockResult) {
            if (!zen_not_null($stockResult)) {
              $stockResultArray = false;
              continue;
            }
          }*/

          if ($notAccounted) {
            return false;
          } else {
            return $stockResultArray;
          }
        }
      
    }

    return;
    
  }
  
  function zen_sba_has_text_field($sba_table_ids = array()){
    global $db;
    
    if (!isset($sba_table_ids) || isset($sba_table_ids) && !is_array($sba_table_ids) && $sba_table_ids === false) {
      return false;
    }
    
    if (isset($sba_table_ids) && !is_array($sba_table_ids)) {
      $sba_table_ids = array($sba_table_ids);
    }
    
    if (!isset($sba_table_ids) || !is_array($sba_table_ids) || sizeof($sba_table_ids) < 1 || !zen_not_null($sba_table_ids)) {
      return false;
    }
    
    $attrib_sql = 'select stock_attributes, products_id from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' pwas where stock_id in (:stock_id:)';
    $attrib_sql = $db->bindVars($attrib_sql, ':stock_id:', implode(',',$sba_table_ids), 'passthru');

    $attrib_ids = $db->Execute($attrib_sql);

    while (!$attrib_ids->EOF){
      $attrib_type_sql = "select distinct popt.products_options_type, popt.products_options_id
              from     " . TABLE_PRODUCTS_ATTRIBUTES . " patrib 
              left join    " . TABLE_PRODUCTS_OPTIONS . " popt ON (popt.products_options_id = patrib.options_id) 
              where patrib.products_attributes_id in (:check_attribs:)
              and (popt.products_options_type = :txt_type: OR popt.products_options_type = :file_type:)
              and popt.language_id = :languages_id: ";

      $attrib_type_sql = $db->bindVars($attrib_type_sql, ':check_attribs:', $attrib_ids->fields['stock_attributes'], 'passthru');
      $attrib_type_sql = $db->bindVars($attrib_type_sql, ':txt_type:', PRODUCTS_OPTIONS_TYPE_TEXT, 'integer');
      $attrib_type_sql = $db->bindVars($attrib_type_sql, ':file_type:', PRODUCTS_OPTIONS_TYPE_FILE, 'integer');
      $attrib_type_sql = $db->bindVars($attrib_type_sql, ':languages_id:', $_SESSION['languages_id'], 'integer');
      
      $attrib_type = $db->Execute($attrib_type_sql);
      
      while (!$attrib_type->EOF) {
        if ($attrib_type->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_TEXT || $attrib_type->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_FILE) {
          return true;
        }
      }
      
      $attrib_ids->MoveNext();
    }

    return false;
  }


  
  function zen_sba_attribs_no_text($products_id, $attribute_list, $from = 'products', $how = 'add') {
    global $db;

    $specAttributes = array();
    $temp_attributes = array();
    $stock_attributes_list = array();
    $stock_attributes = '';
    $compArray = array();
    
    $pro_id = $products_id;
    
    $products_id = zen_get_prid($products_id);

    $stock_attribute = zen_get_sba_stock_attribute($products_id, $attribute_list, $from); // Gets an imploded list of $attribute_list

    // Thinking either don't want to test the attributes here yet unless before this makes sure that the attributes are properly added for a product that only has text attributes and nothing entered.
    //   
    if (!zen_product_is_sba($products_id)) {
      return $attribute_list;
    } 
    
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

    $sql = $db->bindVars($sql, ':products_id:', $products_id, 'integer');
    $sql = $db->bindVars($sql, ':languages_id:', $_SESSION['languages_id'], 'integer');
    $products_options_names = $db->Execute($sql);
    
    if ($products_options_names->EOF) {
      $_SESSION['sba_extra_functions_error'] = 'SBA product can not have any attributes';
      //There's an issue because this shouldn't be possible
    } else {
      if (isset($_SESSION['sba_extra_functions_error'])) {
//        unset($_SESSION['sba_extra_functions_error']);
      }
    }

    if (defined('TEXT_PREFIX')) {
      $text_prefix = constant('TEXT_PREFIX');
    } else {
      $text_prefix = 'txt_';
    }

    if (defined('UPLOAD_PREFIX')) {
      $file_prefix = UPLOAD_PREFIX;
    } else {
      $file_prefix = 'upload_';
    }

    if (defined('PRODUCTS_OPTIONS_VALUE_TEXT_ID')) {
      $text_value = PRODUCTS_OPTIONS_VALUE_TEXT_ID;
    } else {
      $text_value = '0';
    }

    if (defined('PRODUCTS_OPTIONS_VALUE_TEXT_ID')) {
      $file_value = PRODUCTS_OPTIONS_VALUE_TEXT_ID;
    } else {
      $file_value = '0';
    }

    if (isset($attribute_list) && is_array($attribute_list) && sizeof($attribute_list) > 0) {
      $compArray = $attribute_list;
    }

    foreach($compArray as $optid => $optvalid) {
      if (preg_match('/'.$text_prefix.'/', $optid)) {
        $specAttributes[str_replace($text_prefix,'',$optid)] = $text_value;
      } elseif (preg_match('/'.$file_prefix.'/', $optid)) {
        $specAttributes[str_replace($file_prefix,'',$optid)] = $file_value;
      } elseif ($optvalid == 0) {
        $specAttributes[$optid] = $optvalid;
      } elseif (true) { // mc12345678 Here is one place where verification can be performed as to whether a particular attribute should be added.  This is probably the best place to do the review because all aspects of the attribute are available.
        $specAttributes[$optid] = $optvalid;
      }
    }
//              $_SESSION['specAttribs'] = $specAttributes;
//              $_SESSION['compArray'] = $compArray;
                
//      $_SESSION['prod_options_names'] = $products_options_names;
      $lastIndex = 0;
    while (!$products_options_names->EOF) {
//      $_SESSION['prod_optins_names'] = $products_options_names;
//                if (!isset($specAttributes[$products_options_names->fields['products_options_id']])) {
      if (!array_key_exists($products_options_names->fields['products_options_id'], $specAttributes)) {
//                    $_SESSION['key_not_exist']++;
//                    $_SESSION['key_not_exist_spec_' . $_SESSION['key_not_exist']] = $products_options_names->fields['products_options_id'];
//                    $_SESSION['key_not_exist_spec_type_' . $_SESSION['key_not_exist']] = $products_options_names->fields['products_options_type'];
//                    $_SESSION['key_not_exist_spec_type_text_' . $_SESSION['key_not_exist']] = PRODUCTS_OPTIONS_TYPE_TEXT;
                   // option name of product is not provided
        if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_TEXT || $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_FILE) {
                     // May need to check/verify that is okay to be empty.
                     //   For now... Just accept it as empty.
          if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_TEXT) {
            if ($how == 'add') {
//              $_SESSION['compArray_before_'.$products_id] = $compArray;
              $compArray = ($products_options_names->cursor != 0 ? array_slice($compArray, $lastIndex, $products_options_names->cursor, true) : array()) +
              array(($text_prefix . $products_options_names->fields['products_options_id']) => $text_value) + 
              array_slice($compArray, $products_options_names->cursor, sizeof($compArray) - $products_options_names->cursor, true) ;
 //            }
//              $_SESSION['compArray_after_'.$products_id] = $compArray;
            } elseif ($how == 'addNoText') {
              $compArray = ($products_options_names->cursor != 0 ? array_slice($compArray, $lastIndex, $products_options_names->cursor, true) : array()) +
              array((/*$text_prefix .*/ $products_options_names->fields['products_options_id']) => $text_value) + 
              array_slice($compArray, $products_options_names->cursor, sizeof($compArray) - $products_options_names->cursor, true);
            } else {
              $compArray = ($products_options_names->cursor != 0 ? array_slice($compArray, $lastIndex, $products_options_names->cursor, true) : array()) +
               array((/*$text_prefix .*/ $products_options_names->fields['products_options_id']) => $text_value) + 
               array_slice($compArray, $products_options_names->cursor, sizeof($compArray) - $products_options_names->cursor, true);
            }
            $lastIndex = $products_options_names->cursor + 1;
//                       $_SESSION['key_not_exist_add_' . $_SESSION['key_not_exist']] = $text_prefix . $products_options_names->fields['products_options_id'];
//                       $_SESSION['key_not_exist_added_' . $_SESSION['key_not_exist']] = $compArray[$text_prefix . $products_options_names->fields['products_options_id']];
          }
          if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_FILE) {
            if ($how == 'add') {
//              $_SESSION['compArray_before_'.$products_id] = $compArray;
              $compArray = ($products_options_names->cursor != 0 ? array_slice($compArray, $lastIndex, $products_options_names->cursor, true) : array()) +
              array(($file_prefix . $products_options_names->fields['products_options_id']) => $file_value) + 
              array_slice($compArray, $products_options_names->cursor, sizeof($compArray) - $products_options_names->cursor, true) ;
 //            }
//              $_SESSION['compArray_after_'.$products_id] = $compArray;
            } elseif ($how == 'addNoText') {
              $compArray = ($products_options_names->cursor != 0 ? array_slice($compArray, $lastIndex, $products_options_names->cursor, true) : array()) +
              array((/*$file_prefix .*/ $products_options_names->fields['products_options_id']) => $file_value) + 
              array_slice($compArray, $products_options_names->cursor, sizeof($compArray) - $products_options_names->cursor, true);
            } else {
             $compArray = ($products_options_names->cursor != 0 ? array_slice($compArray, $lastIndex, $products_options_names->cursor, true) : array()) +
               array((/*$file_prefix .*/ $products_options_names->fields['products_options_id']) => $file_value) + 
               array_slice($compArray, $products_options_names->cursor, sizeof($compArray) - $products_options_names->cursor, true);
            }
            $lastIndex = $products_options_names->cursor + 1;
//            $compArray[/*$file_prefix . */$products_options_names->fields['products_options_id']] = $file_value;
//            $compArray[$products_options_names->fields['products_options_id']] = $file_value;
          }
        } else {
          // Potential problem as option name expected is not
          //  one that is a text field and was not assigned to the
          //  product. mc12345678 01-02-2016
//                    $_SESSION['key_exist']++;
//                    $_SESSION['key_exist_spec_' . $_SESSION['key_exist']] = $products_options_names->fields['products_options_id'];
        }
      } elseif ($how != 'add' /*&& $how != 'addNoText'*/) {
        if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_TEXT || $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_FILE) {
          // May need to check/verify that is okay to be empty.
          //   For now... Just accept it as empty.
          if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_TEXT) {
//            $compArray[$text_prefix . $products_options_names->fields['products_options_id']] = $text_value;
//            $compArray[$products_options_names->fields['products_options_id']] = $text_value;
            unset($compArray[$text_prefix . $products_options_names->fields['products_options_id']]);

            $compArray = ($products_options_names->cursor != 0 ? array_slice($compArray, $lastIndex, $products_options_names->cursor, true) : array()) +
            array((/*$text_prefix .*/ $products_options_names->fields['products_options_id']) => $text_value) + 
            array_slice($compArray, $products_options_names->cursor, sizeof($compArray) - $products_options_names->cursor, true);

            $lastIndex = $products_options_names->cursor + 1;
//            $_SESSION['key_not_exist_add_' . $_SESSION['key_not_exist']] = $text_prefix . $products_options_names->fields['products_options_id'];
//            $_SESSION['key_not_exist_added_' . $_SESSION['key_not_exist']] = $compArray[$text_prefix . $products_options_names->fields['products_options_id']];
          }
          if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_FILE) {
            unset($compArray[$file_prefix . $products_options_names->fields['products_options_id']]);
//            $compArray[$file_prefix . $products_options_names->fields['products_options_id']] = $file_value;
//            $compArray[$products_options_names->fields['products_options_id']] = $file_value;
            $compArray = ($products_options_names->cursor != 0 ? array_slice($compArray, $lastIndex, $products_options_names->cursor, true) : array()) +
            array((/*$file_prefix .*/ $products_options_names->fields['products_options_id']) => $file_value) + 
            array_slice($compArray, $products_options_names->cursor, sizeof($compArray) - $products_options_names->cursor, true);

            $lastIndex = $products_options_names->cursor + 1;
          }
        }
      }

      $products_options_names->MoveNext();
    }              
//    $_SESSION['specAttribs2'] = $specAttributes;
//    $_SESSION['compArray2'.$pro_id] = $compArray;
//    $_SESSION['attrib_list2'.$pro_id] = $attribute_list;

    if (sizeof($compArray) > 0) {
//      $attribute_list = $compArray; // + $attribute_list /*+ $compArray*/;
      if ($how == 'add' && $how != 'addNoText') {
        if (isset($attribute_list) && is_array($attribute_list) && sizeof($attribute_list) > 0) {
          $attribute_list = $attribute_list + $compArray;
        } else {
          $attribute_list = $compArray;
        }
      } elseif ($how == 'update') {
        $attribute_list = $compArray;
      } else {
        if (isset($attribute_list) && is_array($attribute_list) && sizeof($attribute_list) > 0) {
          $attribute_list = $compArray + $attribute_list;
        } else {
          $attribute_list = $compArray;
        }
      }
    }

    return $attribute_list;
    
  }
  
  /*
   * Function to return the stock_attribute_id field from the SBA table products_with_attributes_stock. Makes a call to zen_get_sba_stock_attribute in order to identify data to help with this search.
   * 
   * @access  public
   * @param   integer   $products_id      The product id of the product on which to obtain the stock attribute.
   * @param   array     $attribute_list   The attribute array of the product identified in products_id
   * @returns integer   stock_id          The value of the unique id in the SBA table products_with_attributes_stock
   */
  
  function zen_get_sba_attribute_info($products_id, $attribute_list = array(), $from = 'order', $datatype = 'ids'){
    global $db;

    /*if (isset($attribute_list) && (($k = sizeof($attribute_list)) > 0)) {*/

/*
  Discussion:
  This section of code should return the one or more stock_ids that encompass
  all of the provided attributes one time only and only those attributes 
  (or if not to be considered a stock related item then can be duplicated).
  By doing so, one can identify that the attributes provided are captured
  and valid for a checkout or other next action.
  
  So to accomplish this: Need to find the Union of variants where those that 
  are stock bearing do not intersect and where all of the attributes are present.
  May be best to identify all of the variants that contain at least the 
  attribute(s) provided.  This can be done currently with 
  zen_get_sba_ids_from_attribute($attribute_id, $products_id, stock_attribute_unique)
  
  
  The union of these should be at least all attributes, if
  that is not true, then an attribute is provided that is not tracked.
  After have a list of all associated variants, need to find the one or
  combination of them that provides a "complete" combination.


Approach(es) on this... 

Of the attributes provided, determine the number of those attributes that are
  stock based.  With the set of proposed attributes for the product (being
  stock based) test against the proposed attributes against the stored list 
  to see if the current selection is in the stored list.  If it is, then done.
  If the 


*/
/*      $stock_attribute = zen_get_sba_stock_attribute($products_id, $attribute_list, $from);
      $query = 'select stock_id from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . 
                ' where `stock_attributes` = :stock_attribute: and products_id = :products_id:';
      $query = $db->bindVars($query, ':stock_attribute:', $stock_attribute, 'string');
      $query = $db->bindVars($query, ':products_id:', $products_id, 'integer');

      $stock_id = $db->Execute($query);

      if ($stock_id->RecordCount() > 1) {
        echo 'This is an error situation, as only one record should be returned.  More than one stock id was returned which should not be possible.';
      } else {
        return $stock_id->fields['stock_id'];
      }
    }
    
    return;*/




            // Known: product is tracked by SBA, product is reported as 
            //  having one attribute, though if there is one or more 
            //  attributes that can be blank (text/upload file) then this
            //  is the same "result" if there is one other non-text field
            //  or one text field is filled and the other not.
            
            //  Need: identify if this product/combination can exist in the
            //   above known state.  In essence to repeat the check process
            //   of the add-to-cart, but not needing to ensure that the 
            //   contents match the required attribute marker as product 
            //   could not make it to the cart if that were allowed.
            
            //  So.. Could pull all of the attributes for this product,
            //  remove the attribute currently known, 
            //  Could pull all of the variants that have this attribute, 
            //  review all of the other attributes to see if they could be
            //  zeroable.. if so, then return the quantity(ies) 



    $specAttributes = array();
    $temp_attributes = array();
    $stock_attributes_list = array();
    $stock_attributes = '';
    $compArray = array();
  //    $multi = (sizeof($attribute_list) > 1 ? true : false);

    $products_id = zen_get_prid($products_id);

    // Thinking either don't want to test the attributes here yet unless before this makes sure that the attributes are properly added for a product that only has text attributes and nothing entered.
    //   
    if ($datatype == 'stock' && !zen_product_is_sba($products_id)) {
      //Used with products that have attributes But the attribute is not listed in the SBA Stock table.
      //Get product level stock quantity
      $stock_query = "select products_quantity from " . TABLE_PRODUCTS . " where products_id = :products_id:";
      $stock_query = $db->bindVars($stock_query, ':products_id:', $products_id, 'integer');
      $stock_values = $db->Execute($stock_query);
      return $stock_values->fields['products_quantity'];
    } elseif (!zen_product_is_sba($products_id)) {
      return NULL;
    } 
    
    $stock_attribute = zen_get_sba_stock_attribute($products_id, $attribute_list, $from); // Gets an imploded list of $attribute_list

    // NEED TO ENSURE ATTRIBUTES ARE PROPERLY/FULLY PROVIDED REGARDING "TEXT" OR OTHER ZC DELETES ATTRIBUTES.
    
    // KNOWN product is tracked by SBA.
    //       some form of attributes has been provided whether it is null
    //         or an array of items.
    //       At some point in future it may be acceptable for various attributes to
    //         be missing that are or are not directly related to those in the 
    //         ZC attributes table(s).
    //       The extra_cart_actions provides all attributes pre-filtered, so
    //         that function should accept/deny adding a product to cart, this
    //         smaller/new code section described in this thought process
    //         needs to verify that it is okay to keep it in the cart after
    //         it was properly added.
    //
    // POSSIBLES: list of provided attributes has nothing to do with the product
    //            attributes provided are less than total number expected for product
    //
    // TRYING TO FIX:
    //      * product has attributes expected but because of text fields being blank
    //          the field did not make it to this function (zen_get_products_stock)
    //      *
    // HOW TO FIX:
    //      * pull all option names from a standard attribute query.
    //      * pull the option name groups of the provided option data (key of key value pair)
    //      * Removing the key from the standard query leaves only those
    //      *   that were not passed.  If any of those are looked up to be
    //      *   a text field then add them into the finals list, but if any
    //      *   that remain are anything else, then have an unresolved issue.
    
    if (PRODUCTS_OPTIONS_SORT_ORDER=='0') {
      $options_order_by= ' order by LPAD(popt.products_options_sort_order,11,"0")';
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

    $sql = $db->bindVars($sql, ':products_id:', $products_id, 'integer');
    $sql = $db->bindVars($sql, ':languages_id:', $_SESSION['languages_id'], 'integer');
    $products_options_names = $db->Execute($sql);
    
    if ($products_options_names->EOF) {
      $_SESSION['sba_extra_functions_error'] = 'SBA product can not have any attributes';
      //There's an issue because this shouldn't be possible
    } else {
      if (isset($_SESSION['sba_extra_functions_error'])) {
        unset($_SESSION['sba_extra_functions_error']);
      }
    }

    if (defined('TEXT_PREFIX')) {
      $text_prefix = TEXT_PREFIX;
    } else {
      $text_prefix = 'txt_';
    }

    if (defined('UPLOAD_PREFIX')) {
      $file_prefix = UPLOAD_PREFIX;
    } else {
      $file_prefix = 'upload_';
    }

    if (defined('PRODUCTS_OPTIONS_VALUE_TEXT_ID')) {
      $text_value = PRODUCTS_OPTIONS_VALUE_TEXT_ID;
    } else {
      $text_value = '0';
    }

    if (defined('PRODUCTS_OPTIONS_VALUE_TEXT_ID')) {
      $file_value = PRODUCTS_OPTIONS_VALUE_TEXT_ID;
    } else {
      $file_value = '0';
    }

    if (isset($attribute_list) && is_array($attribute_list) && sizeof($attribute_list) > 0) {
      $compArray = $attribute_list;
    }

    foreach($compArray as $optid => $optvalid) {
      if (preg_match('/'.$text_prefix.'/', $optid)) {
        $specAttributes[str_replace($text_prefix,'',$optid)] = $text_value;
      } elseif (preg_match('/'.$file_prefix.'/', $optid)) {
        $specAttributes[str_replace($file_prefix,'',$optid)] = $file_value;
      } elseif ($optvalid == 0) {
        $specAttributes[$optid] = $optvalid;
      } elseif (true) { // mc12345678 Here is one place where verification can be performed as to whether a particular attribute should be added.  This is probably the best place to do the review because all aspects of the attribute are available.
        $specAttributes[$optid] = $optvalid;
      }
    }
//              $_SESSION['specAttribs'] = $specAttributes;
//              $_SESSION['compArray'] = $compArray;
                
    while (!$products_options_names->EOF) {
//                if (!isset($specAttributes[$products_options_names->fields['products_options_id']])) {
      if (!array_key_exists($products_options_names->fields['products_options_id'], $specAttributes)) {
//                    $_SESSION['key_not_exist']++;
//                    $_SESSION['key_not_exist_spec_' . $_SESSION['key_not_exist']] = $products_options_names->fields['products_options_id'];
//                    $_SESSION['key_not_exist_spec_type_' . $_SESSION['key_not_exist']] = $products_options_names->fields['products_options_type'];
//                    $_SESSION['key_not_exist_spec_type_text_' . $_SESSION['key_not_exist']] = PRODUCTS_OPTIONS_TYPE_TEXT;
                   // option name of product is not provided
        if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_TEXT || $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_FILE) {
                     // May need to check/verify that is okay to be empty.
                     //   For now... Just accept it as empty.
          if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_TEXT) {
            $compArray[$text_prefix . $products_options_names->fields['products_options_id']] = $text_value;
//                       $compArray[$products_options_names->fields['products_options_id']] = $text_value;
//                       $_SESSION['key_not_exist_add_' . $_SESSION['key_not_exist']] = $text_prefix . $products_options_names->fields['products_options_id'];
//                       $_SESSION['key_not_exist_added_' . $_SESSION['key_not_exist']] = $compArray[$text_prefix . $products_options_names->fields['products_options_id']];
          }
          if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_FILE) {
            $compArray[$file_prefix . $products_options_names->fields['products_options_id']] = $file_value;
          }
        } else {
          // Potential problem as option name expected is not
          //  one that is a text field and was not assigned to the
          //  product. mc12345678 01-02-2016
//                    $_SESSION['key_exist']++;
//                    $_SESSION['key_exist_spec_' . $_SESSION['key_exist']] = $products_options_names->fields['products_options_id'];
        }
      } /*else {
                   if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_TEXT || $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_FILE) {
                     // May need to check/verify that is okay to be empty.
                     //   For now... Just accept it as empty.
                     if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_TEXT) {
                       $compArray[$text_prefix . $products_options_names->fields['products_options_id']] = $text_value;
//                       $compArray[$products_options_names->fields['products_options_id']] = $text_value;
//                       $_SESSION['key_not_exist_add_' . $_SESSION['key_not_exist']] = $text_prefix . $products_options_names->fields['products_options_id'];
//                       $_SESSION['key_not_exist_added_' . $_SESSION['key_not_exist']] = $compArray[$text_prefix . $products_options_names->fields['products_options_id']];
                     }
                     if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_FILE) {
                       $compArray[$file_prefix . $products_options_names->fields['products_options_id']] = $file_value;
                     }
                   }
                }*/

      $products_options_names->MoveNext();
    }              
//              $_SESSION['specAttribs2'] = $specAttributes;
//              $_SESSION['compArray2'] = $compArray;

    if (sizeof($compArray) > 0) {
      $attribute_list = /*$compArray +*/ $attribute_list + $compArray;
    }
//              $_SESSION['compArray3'] = $attribute_list;
//              $_SESSION['compArray2'] = $compArray;
    
/*    if ($datatype == 'products' && (!zen_product_is_sba($products_id) || !isset($attribute_list) || !is_array($attribute_list) || !(($k = sizeof($attribute_list)) > 0))) {
      //Used with products that have attributes But the attribute is not listed in the SBA Stock table.
      //Get product level stock quantity
      $stock_query = "select products_quantity from " . TABLE_PRODUCTS . " where products_id = :products_id:";
      $stock_query = $db->bindVars($stock_query, ':products_id:', $products_id, 'integer');
      $stock_values = $db->Execute($stock_query);
      return $stock_values->fields['products_quantity'];
    } elseif (!zen_product_is_sba($products_id)) {
      $return NULL;
    } */

    $stock_attributes_list = zen_get_sba_attribute_ids($products_id, $attribute_list, $from);
    // Summary of the above, if the product is not tracked by SBA, then return null.
    // Need to evaluate if product is SBA tracked in case the page is posted without the attributes as a separate check.

    if (sizeof($stock_attributes_list) == 1 && $datatype != 'dupTest') {
      //         echo '<br />Single Attribute <br />';
      $stock_attributes = $stock_attributes_list[0];
      // create the query to find single attribute stock
      $stock_query = 'select stock_id, quantity as products_quantity from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id = :products_id: and stock_attributes like :stock_attributes:';
      $stock_query = $db->bindVars($stock_query, ':products_id:', $products_id, 'integer');
      $stock_query = $db->bindVars($stock_query, ':stock_attributes:', $stock_attributes, 'passthru');
      $stock_values = $db->Execute($stock_query);
      // return the stock qty for the attribute
      if (!$stock_values->EOF) {
        switch ($datatype) {
          case 'stock':
            return $stock_values->fields['products_quantity'];
            break;
          case 'ids':
          default:
            return array($stock_values->fields['stock_id']);
        }
      } else {
        // Known: product is tracked by SBA, product is reported as 
        //  having one attribute, though if there is one or more 
        //  attributes that can be blank (text/upload file) then this
        //  is the same "result" if there is one other non-text field
        //  or one text field is filled and the other not.
        //  Need: identify if this product/combination can exist in the
        //   above known state.  In essence to repeat the check process
        //   of the add-to-cart, but not needing to ensure that the 
        //   contents match the required attribute marker as product 
        //   could not make it to the cart if that were allowed.
        //  So.. Could pull all of the attributes for this product,
        //  remove the attribute currently known, 
        //  Could pull all of the variants that have this attribute, 
        //  review all of the other attributes to see if they could be
        //  zeroable.. if so, then return the quantity(ies) 

        return false;
      }
    } elseif (sizeof($stock_attributes_list) > 1) {
      // mc12345678 multiple attributes are associated with the product
      //   question is how these relate to the SBA variant.
      //       echo '<br />Multiple attributes <br />';
      $stockResult = null;
      //This part checks for "attribute combinations" in the SBA table. (Multiple attributes per Stock ID Row, Multiple Attribute types in stock_attributes Field  i.e, 123,321,234)
      $stock_attributes = implode(',', $stock_attributes_list);
      // create the query to find attribute stock
      $stock_query = 'select stock_id, quantity as products_quantity from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id = :products_id: and stock_attributes like :TMPstock_attributes:';
      $stock_query = $db->bindVars($stock_query, ':products_id:', $products_id, 'integer');
      $stock_query = $db->bindVars($stock_query, ':TMPstock_attributes:', $stock_attributes, 'string');
      // get the stock value for the combination
      $stock_values = $db->Execute($stock_query);
      switch ($datatype) {
        case 'stock':
          $stockResult = $stock_values->fields['products_quantity'];
          break;
        case 'dupTest':
          $stockResult = $stock_values->fields['products_quantity'];
          if ($stockResult > 0) {
            return 'true';
          }
          return 'false';
          break;
        case 'ids':
        default:
          $stockResult = $stock_values->fields['stock_id'];
      }

      if (!$stock_values->EOF && $stock_values->RecordCount() == 1) {
        //return the stock for "attribute combinations"
  //          $_SESSION['One_record_1_'. $products_id] = $stockResult;
        switch ($datatype) {
          case 'stock':
            return $stockResult;
            break;
          case 'ids':
          default:
            return array($stockResult);
        }
      } else {
        //This part is for attributes that are all listed separately in the SBA table for the product

        $stockResult = null;
        $returnedStock = null;
        $i = 0;

        $stockResultArray = array();
        $notAccounted = false;

        foreach ($stock_attributes_list as $eachAttribute) {
          // create the query to find attribute stock
          //echo '<br />Multiple Attributes selected (one attribute type per product)<br />';
          $stock_query = 'select stock_id, quantity as products_quantity from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id = :products_id: and stock_attributes like :eachAttribute:';
          $stock_query = $db->bindVars($stock_query, ':products_id:', $products_id, 'integer');
          $stock_query = $db->bindVars($stock_query, ':eachAttribute:', $eachAttribute, 'passthru');

          // get the stock value for the combination
          $stock_values = $db->Execute($stock_query);
          $stockResult = $stock_values->fields['products_quantity'];
          $stockResultArray[] = $stock_values->fields['stock_id'];

          if ($stock_values->EOF) {
  //              $_SESSION['not_account']++;
            $notAccounted = true;
  //              $_SESSION['stockResultN' . $_SESSION['not_account']] = $stockResult;
  //              $_SESSION['stockvaluesN' . $_SESSION['not_account']] = $stock_values;
  //              $_SESSION['curr_account' . ($_SESSION['not_account'] + $_SESSION['accounted'])] = 'n';
  //              $_SESSION['stock_quer' . ($_SESSION['not_account'] + $_SESSION['accounted'])] = $stock_query;
          } /* for testing */ else {
  //              $_SESSION['accounted']++;
  //              $_SESSION['stockResult' . $_SESSION['accounted']] = $stockResult;
  //              $_SESSION['stockvalues' . $_SESSION['accounted']] = $stock_values;
  //              $_SESSION['curr_account' . ($_SESSION['not_account'] + $_SESSION['accounted'])] = 'a';
  //              $_SESSION['stock_quer' . ($_SESSION['not_account'] + $_SESSION['accounted'])] = $stock_query;
          }
          //special test to account for qty when all attributes are listed seperetly
          if (!zen_not_null($returnedStock) && $i == 0) {
            //set initial value
            if ($stock_values->EOF) {
              $returnedStock = 0;
            } else {
              $returnedStock = $stockResult;
            }
          } elseif ($returnedStock > $stockResult) {
            //update for each attribute, if qty is lower than the previous one
            $returnedStock = $stockResult;
          } // end if first stock item of attribute
          $i++;
        } // end for each attribute.

        /* foreach ($stockResultArray as $stockResult) {
          if (!zen_not_null($stockResult)) {
          $stockResultArray = false;
          continue;
          }
          } */

        if ($notAccounted) {
          return false;
        } else {
          switch ($datatype) {
            case 'stock':
              return $returnedStock;
              break;
            case 'ids':
            default:
              return $stockResultArray;
          }
          return $stockResultArray;
        }
      }
    }

    return;
  }

  /*
   * Function to return the information related to the SBA tracked stock based on receiving the product id and the attributes associated with the product.
   * 
   * @access  public
   * @param   integer   $products_id      The product id of the product on which to obtain the stock attribute.
   * @param   array     $attribute_list   The attribute array of the product identified in products_id
   * @returns array   $attribute_info     The values to be collected/used by the calling function.  This includes the stock_attribute and the stock_id both information to be contained in the SBA table.
   */
  function zen_get_sba_stock_attribute_info($products_id, $attribute_list = array(), $from = 'order'){
    global $db;
    
    $attribute_info = array();

    if (isset($attribute_list) && (($k = sizeof($attribute_list)) > 0)) {
      $attribute_info['stock_attribute'] = zen_get_sba_stock_attribute($products_id, $attribute_list, $from);
      $query = 'select stock_id from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . 
                ' where `stock_attributes` = "' . $attribute_info['stock_attribute'] . '" and products_id = ' . (int)$products_id;

      $stock_id = $db->Execute($query);

      if (zen_not_null($stock_id) && sizeof($stock_id) > 1) {
        echo 'This is an error situation, as only one record should be returned.  More than one stock id was returned which should not be possible.';
      } else {
        $attribute_info['stock_id'] = $stock_id->fields['stock_id'];
        return $attribute_info;
      }
    }
  }  
  
  /**
  *  Uses a single attribute to identify all of the SBA variants 
  *    that contain that one attribute.
  *
  **/
  function zen_get_sba_ids_from_attribute($products_attributes_id = array(), $products_id = NULL, $stock_attribute_unique = false){
    global $db;
    
    if (!is_array($products_attributes_id)){
      $products_attributes_id = array($products_attributes_id);
    }
    $products_stock_attributes = $db->Execute("select stock_id, stock_attributes from " . 
                                              TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . (zen_not_null($products_id) ? " where products_id in (" . implode(',', (int)$products_id) . ")" : "" ));
    $stock_id_list = array();
    /* The below "search" is one reason that the original tables for SBA should be better refined
     * and not use comma separated items in a field...
     */
    while (!$products_stock_attributes->EOF) {
      $stock_attrib_list = array();
      $stock_attrib_list = explode(',', $products_stock_attributes->fields['stock_attributes']);

/*
*     Proposed code to possibly increase speed/optimize operation.
*     Vision is that duration of the below "search" will be dependent on the
*     number of attributes provided to be a part of the search rather than.
*     constantly going through every attribute of the variant
      // Makes values into keys.
      //  The values of this are all unique numeric greater than 0 (non-null) by 
      //  design of the database and use of attribute_id.
      //  Therefore all values of this array represent unique keys and can
      //  be used as such.
      $stock_attrib_list = array_flip($stock_attrib_list);

      $loopC = 0;
      //  Loop on the provided array of attributes
      foreach ($products_attributes_id as $products_attribute_id) {
        //isset is permitted here, because a value of 0 for an array still 
        //  represents that the item is set and none of the values of this
        //  array is equal to null, therefore can still use isset.
        if (isset($stock_attrib_list[$products_attribute_id])) {
          // This treats the search as an or search.  There is no
          //   requirement that all of the products_attribute_ids are used.
          $stock_id_list[$products_stock_attributes->fields['stock_id']] = true;
          $loopC++;
          //  This is provided to force going to the next loop instead of
          //    performing any other operation after the if statement as the
          //    goal has been achieved.  It is unnecessary if nothing is 
          //    performed after the if is complete and before the next iteration
          //    of the for loop.
          continue;
        }
      }
      
      // If need to return a stock id that contains all of the attributes
      if ($loopC == sizeof($products_attributes_id)) {
        // Do Nothing as the variant contains all of the desired attributes.
      } elseif ($stock_attribute_unique == true) {
        unset($stock_id_list[$products_stock_attributes->fields['stock_id']]);
      }
      
      */

      foreach($stock_attrib_list as $stock_attrib){
        if (in_array($stock_attrib, $products_attributes_id)) {
          $stock_id_list[] = $products_stock_attributes->fields['stock_id'];
          continue;
        }
      }
      
      $products_stock_attributes->MoveNext;
    }
    
    // If use the above revised array set, then need to reverse the array
    // and maintain uniqueness of the values:
    //  $stock_id_list = array_keys($stock_id_list);
    return $stock_id_list;
  }

  function zen_sba_dd_allowed($products_options_names, $data_type = 'attributes') {
//  if (!is_array($products_options_names)) $products_options_names = array($products_options_names);

    // mc12345678 Below is a list of product types that are currently not supported
    //  by dynamic dropdowns and therefore should not be displayed with dropdowns 
    //  until the option type is properly worked around and supported in the dropdowns.
    $special = array(PRODUCTS_OPTIONS_TYPE_TEXT, PRODUCTS_OPTIONS_TYPE_FILE, PRODUCTS_OPTIONS_TYPE_READONLY, PRODUCTS_OPTIONS_TYPE_CHECKBOX, PRODUCTS_OPTIONS_TYPE_GRID);

    // This is the default "reason" for using this code, and will handle 
    //   the data that is default provided ($products_options_names from: 
    //   includes/modules/YOUR_TEMPLATE/attributes.php
    if ($data_type == 'attributes') {
      $opt_array = array();
      // If there is at least one option name then perform the testing.
      if ($products_options_names->RecordCount() > 0) { 
        $products_options_names->Move(0);
//        $products_options_names->MoveNext();
        while (!$products_options_names->EOF) {
          if (in_array($products_options_names->fields['products_options_type'], $special)) {
            return false; // mc12345678 Found that current option type is not supported by Dynamic Dropdowns
          }
          $products_options_names->MoveNext();
        }
      } else {
        return false;  // There are no option names therefore there is no DD.
      }
    }

    return true;  // Default to trying to use the dynamic dropdown option if
                  //  not specifically excluded above.

/*  if (sizeof($attributes) > 0) {
      $sql = "select products_options_type, products_options_id from " . TABLE_PRODUCTS_OPTIONS . " where products_options_id in (:products_options_ids:)";
      $sql = $db->bindVars($sql, ':products_options_ids:', implode(',', $opt_array), 'noquotestring');
      $has_special = $db->Execute($sql);

      while (!$has_special->EOF) {
        if (in_array($has_special->fields['products_options_type'], $special)) {
          return false;
        }
        $has_special->MoveNext();
      }
    } else {
      return false;
    }*/
  }

  // function zen_is_SBA was removed as it was a duplicate of zen_product_is_sba.  If code
  // has been written to use that function, please consider using zen_product_is_sba instead.
  
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
      $isSBA_query = 'SELECT stock_id FROM ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id = :products_id:;';
      $isSBA_query = $db->bindVars($isSBA_query, ':products_id:', $product_id, 'integer');
      $isSBA = $db->Execute($isSBA_query);
    
      if (!$isSBA->EOF && $isSBA->RecordCount() > 0) {
        return true;
      } else {
        return false;
      }
    }

    return false;
  }  
