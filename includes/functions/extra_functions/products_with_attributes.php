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
 * Stock by Attributes 1.5.4 : mc12345678 15-08-22
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

/*	Update for Stock by Attributes
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
   * Function to return the stock_attribute field from the SBA table products_with_attributes_stock.
   * 
	 * @access  public
   * @param   integer   $products_id      The product id of the product on which to obtain the stock attribute.
	 * @param   array     $attribute_list   The attribute array of the product identified in products_id
   * @returns string    $stock_attributes A comma separated (if >1 attribute) string of the products_attributes_id sorted by products_attributes_id. This is the current set of information stored in the SBA table for stock_attributes.
	 */
  function zen_get_sba_stock_attribute($products_id, $attribute_list = array()){
	global $db;

    $attributes = array();
    $stock_attributes_list = array();
    if (isset($attribute_list) && (($k = sizeof($attribute_list)) > 0)) {
      for ($j = 0; $j < $k; $j++) {
        if (true) { // mc12345678 Here is one place where verification can be performed as to whether a particular attribute should be added.  This is probably the best place to do the review because all aspects of the attribute are available.
          $attributes[] = $attribute_list[$j]['value_id'];
        }
      }
      
  		// obtain the attribute ids
  		$query = 'select products_attributes_id 
        from '.TABLE_PRODUCTS_ATTRIBUTES.' 
        	where options_values_id in ("'.implode('","',$attributes).'") 
      		and products_id='.(int)$products_id.' 
  				order by products_attributes_id';
      $attributes_new = $db->Execute($query);
  				
  		while(!$attributes_new->EOF){
        if (true) { // mc12345678 Here is one place where verification can be performed as to whether a particular attribute should be added.
          $stock_attributes_list[] = $attributes_new->fields['products_attributes_id'];
        }
  			$attributes_new->MoveNext();
  		}
      //sort($stock_attributes_list); //Unnecessary as the query is already sorted by the value.
          
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
   * @returns integer   stock_id          The value of the unique id in the SBA table products_with_attributes_stock
	 */
  function zen_get_sba_stock_attribute_id($products_id, $attribute_list = array()){
    global $db;

    if (isset($attribute_list) && (($k = sizeof($attribute_list)) > 0)) {
      $stock_attribute = zen_get_sba_stock_attribute($products_id, $attribute_list);
      $query = 'select stock_id from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . 
                ' where `stock_attributes` = "' . $stock_attribute . '" and products_id = ' . (int)$products_id;

  		$stock_id = $db->Execute($query);

      if (zen_not_null($stock_id) && sizeof($stock_id) > 1) {
        echo 'This is an error situation, as only one record should be returned.  More than one stock id was returned which should not be possible.';
      } else {
        return $stock_id->fields['stock_id'];
      }
    }
  }
  
  /*
   * Function to return the information related to the SBA tracked stock based on receiving the product id and the attributes associated with the product.
   * 
	 * @access  public
   * @param   integer   $products_id      The product id of the product on which to obtain the stock attribute.
	 * @param   array     $attribute_list   The attribute array of the product identified in products_id
   * @returns array   $attribute_info     The values to be collected/used by the calling function.  This includes the stock_attribute and the stock_id both information to be contained in the SBA table.
	 */
  function zen_get_sba_stock_attribute_info($products_id, $attribute_list = array()){
    global $db;
    
    $attribute_info = array();

    if (isset($attribute_list) && (($k = sizeof($attribute_list)) > 0)) {
      $attribute_info['stock_attribute'] = zen_get_sba_stock_attribute($products_id, $attribute_list);
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
  function zen_get_sba_ids_from_attribute($products_attributes_id = array()){
    global $db;
    
    if (!is_array($products_attributes_id)){
      $products_attributes_id = array($products_attributes_id);
    }
    $products_stock_attributes = $db->Execute("select stock_id, stock_attributes from " . 
                                              TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK);
    $stock_id_list = array();
    /* The below "search" is one reason that the original tables for SBA should be better refined
     * and not use comma separated items in a field...
     */
    while (!$products_stock_attributes->EOF) {
      $stock_attrib_list = array();
      $stock_attrib_list = explode(',', $products_stock_attributes->fields['stock_attributes']);

      foreach($stock_attrib_list as $stock_attrib){
        if (in_array($stock_attrib, $products_attributes_id)) {
          $stock_id_list[] = $products_stock_attributes->fields['stock_id'];
          continue;
        }
      }
      
      $products_stock_attributes->MoveNext;
    }
    return $stock_id_list;
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
 
    if (sizeof($SBA_installed) > 0 && !$SBA_installed->EOF) {
      $isSBA_query = 'SELECT stock_id FROM ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id = :products_id:;';
      $isSBA_query = $db->bindVars($isSBA_query, ':products_id:', $product_id, 'integer');
      $isSBA = $db->Execute($isSBA_query);
    
      if ($isSBA->RecordCount() > 0) {
        return true;
      } else {
        return false;
      }
    }

    return false;
  }  