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
		function get_products_attributes($products_id, $languageId=1)
		{
			global $db;
			// Added the following to query "and pa.attributes_display_only != 1" This removed display only attributes from the stock selection.
      // Added the following to query "AND po.products_options_type != ' . PRODUCTS_OPTIONS_TYPE_READONLY so that would ignore READONLY attributes.

      $attributes_array = array();
      
      //LPAD - Return the string argument, left-padded with the specified string 
      //example: LPAD(po.products_options_sort_order,11,"0") the field is 11 digits, and is left padded with 0
      if (PRODUCTS_OPTIONS_SORT_ORDER=='0') {
        $options_order_by= ' order by LPAD(popt.products_options_sort_order,11,"0"), popt.products_options_name';
      } else {
        $options_order_by= ' order by popt.products_options_name';
      }

      //get the option/attribute list
      $sql = "select distinct popt.products_options_id, popt.products_options_name, popt.products_options_sort_order,
                              popt.products_options_type 
              from        " . TABLE_PRODUCTS_OPTIONS . " popt
              left join " . TABLE_PRODUCTS_ATTRIBUTES . " patrib ON (patrib.options_id = popt.products_options_id)
              where patrib.products_id= :products_id:
              and popt.language_id = :languages_id: " .
              $options_order_by;

      $sql = $db->bindVars($sql, ':products_id:', $products_id, 'integer');
      $sql = $db->bindVars($sql, ':languages_id:', $languageId, 'integer');

      $attributes = $db->Execute($sql);
      
      if($attributes->RecordCount() > 0)
      {
      
        if ( PRODUCTS_OPTIONS_SORT_BY_PRICE =='1' ) {
          $order_by= ' order by LPAD(pa.products_options_sort_order,11,"0"), pov.products_options_values_name';
        } else {
          $order_by= ' order by LPAD(pa.products_options_sort_order,11,"0"), pa.options_values_price';
        }
        $products_options_array = array();
      
        while(!$attributes->EOF)
        {
        
          $sql = "select    pov.products_options_values_id,
                        pov.products_options_values_name,
                        pa.*
              from      " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov
              where     pa.products_id = '" . (int)$products_id . "'
              and       pa.options_id = '" . (int)$attributes->fields['products_options_id'] . "'
              and       pa.options_values_id = pov.products_options_values_id
              and       pov.language_id = '" . (int)$languageId . "' " .
                $order_by;

          $attributes_array_ans= $db->Execute($sql);

          //loop for each option/attribute listed

          while (!$attributes_array_ans->EOF) {
            $attributes_array[$attributes->fields['products_options_name']][] =
              array('id' => $attributes_array_ans->fields['products_attributes_id'],
                  'text' => $attributes_array_ans->fields['products_options_values_name']
                        . ' (' . $attributes_array_ans->fields['price_prefix']
                      . '$'.zen_round($attributes_array_ans->fields['options_values_price'],2) . ')' );
          
            $attributes_array_ans->MoveNext();
          }
          $attributes->MoveNext();
        }
  
        return $attributes_array;
  
      }
      else
      {
        return false;
      }
    }
  
    function update_parent_products_stock($products_id)
    {
      global $db;

			$query = 'select sum(quantity) as quantity, products_id from '.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.' where products_id = :products_id:';
      $query = $db->bindVars($query, ':products_id:', $products_id, 'integer');
			$quantity = $db->Execute($query);

      $query = 'update '.TABLE_PRODUCTS.' set products_quantity=:quantity: where products_id=:products_id:';
      $query = $db->bindVars($query, ':products_id:', zen_get_prid($products_id), 'integer');

      // Tests are this: If the the item was found in the SBA table then update with those results.
      // Else pull the value from the current stock quantity  and if the "switch" has not been
      //  turned off, the value will stay the same otherwise, it would be set to zero.
      if ($quantity->RecordCount() > 0 && $quantity->fields['products_id'] == zen_get_prid($products_id)) {
        $query = $db->bindVars($query, ':quantity:', $quantity->fields['quantity'], 'float');
      } else {
        // Should add a switch to allow not resetting the quantity to zero when synchronizing quantities... This doesn't entirely make sense that because the product is not listed in the SBA table, that it should be zero'd out...
        $query2 = "select p.products_quantity as quantity from :table: p where products_id=:products_id:";
        $query2 = $db->bindVars($query2, ':table:', TABLE_PRODUCTS, 'passthru');
        $query2 = $db->bindVars($query2, ':products_id:', zen_get_prid($products_id), 'integer');
        $quantity_orig = $db->Execute($query2);
        
        if ($quantity_orig->RecordCount() > 0 && true /* This is where a switch could be introduced to allow setting to 0 when synchronizing with the SBA table. But as long as true, and the item is not tracked by SBA, then there is no change in the quantity.  header message probably should also appear.. */) {
          $query = $db->bindVars($query, ':quantity:', $quantity_orig->fields['quantity'], 'float');
        } else {
          $query = $db->bindVars($query, ':quantity:', 0, 'float');
        }
      }

      $db->Execute($query);
    }
    
    // Technically the below update of all, could call the update of one... There doesn't
    //  seem to be a way to do the update in any more of a faster way than to address each product.
    function update_all_parent_products_stock() {
      global $db;
      $products_array = $this->get_products_with_attributes();
      foreach ($products_array as $products_id) {
        $query = 'select sum(quantity) as quantity, products_id from '.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.' where products_id = :products_id:';
        $query = $db->bindVars($query, ':products_id:', zen_get_prid($products_id), 'integer');
        $quantity = $db->Execute($query);

        $query = 'update '.TABLE_PRODUCTS.' set  products_quantity=:quantity: where products_id=:products_id:';
        $query = $db->bindVars($query, ':products_id:', zen_get_prid($products_id), 'integer');
        // Tests are this: If the the item was found in the SBA table then update with those results.
        // Else pull the value from the current stock quantity  and if the "switch" has not been
        //  turned off, the value will stay the same otherwise, it would be set to zero.
        if ($quantity->RecordCount() > 0 && $quantity->fields['products_id'] == zen_get_prid($products_id)) {
          $query = $db->bindVars($query, ':quantity:', $quantity->fields['quantity'], 'float');
        } else {
          // Should add a switch to allow not resetting the quantity to zero when synchronizing quantities... This doesn't entirely make sense that because the product is not listed in the SBA table, that it should be zero'd out...
          $query2 = "select p.products_quantity as quantity from :table: p where products_id=:products_id:";
          $query2 = $db->bindVars($query2, ':table:', TABLE_PRODUCTS, 'passthru');
          $query2 = $db->bindVars($query2, ':products_id:', zen_get_prid($products_id), 'integer');
          $quantity_orig = $db->Execute($query2);
          if ($quantity_orig->RecordCount() > 0 && true /* This is where a switch could be introduced to allow setting to 0 when synchronizing with the SBA table. But as long as true, and the item is not tracked by SBA, then there is no change in the quantity.  header message probably should also appear.. */) {
            $query = $db->bindVars($query, ':quantity:', $quantity_orig->fields['quantity'], 'float');
          } else {
            $query = $db->bindVars($query, ':quantity:', 0, 'float');
          }
        }
        
        $db->Execute($query);
      }
    }
    
    // returns an array of product ids which contain attributes
    function get_products_with_attributes() {
      global $db;
      if(isset($_SESSION['languages_id'])){ $language_id = (int)$_SESSION['languages_id'];} else { $language_id=1;}
      $query = 'SELECT DISTINCT pa.products_id, d.products_name, p.products_quantity, p.products_model, p.products_image
                FROM '.TABLE_PRODUCTS_ATTRIBUTES.' pa
                left join '.TABLE_PRODUCTS_DESCRIPTION.' d on (pa.products_id = d.products_id)
                left join '.TABLE_PRODUCTS.' p on (pa.products_id = p.products_id)
                WHERE d.language_id='.$language_id.' 
                ORDER BY d.products_name ';
      $products = $db->Execute($query);
      while(!$products->EOF){
        $products_array[] = $products->fields['products_id'];
        $products->MoveNext();
      }
      return $products_array;
    }
	
	
		function get_attributes_name($attribute_id, $languageId=1)
		{
			global $db;

			$query = 'select pa.products_attributes_id, po.products_options_name, pov.products_options_values_name
			 			from '.TABLE_PRODUCTS_ATTRIBUTES.' pa
			 			left join '.TABLE_PRODUCTS_OPTIONS.' po on (pa.options_id = po.products_options_id)
			 			left join '.TABLE_PRODUCTS_OPTIONS_VALUES.' pov on (pa.options_values_id = pov.products_options_values_id)
			 			where pa.products_attributes_id = "'.$attribute_id.'"
							AND po.language_id = "'.$languageId.'"
							and po.language_id = pov.language_id';
							
			$attributes = $db->Execute($query);
			if(!$attributes->EOF)
			{		
				$attributes_output = array('option' => $attributes->fields['products_options_name'],
										   'value' => $attributes->fields['products_options_values_name']);
				return $attributes_output;
			}
			else
			{
				return false;
			}
		}
        
        
/**
 * @desc displays the filtered product-rows
 * 
 * Passed Options
 * $SearchBoxOnly
 * $ReturnedPage
 * $NumberRecordsShown
 */
function displayFilteredRows($SearchBoxOnly = null, $NumberRecordsShown = null, $ReturnedProductID = null){
        global $db;
      
        if(isset($_SESSION['languages_id'])){ $language_id = $_SESSION['languages_id'];} else { $language_id=1;}
        if( isset($_GET['search']) && $_GET['search']){ // mc12345678 Why was $_GET['search'] omitted?
            $s = zen_db_input($_GET['search']);
         	//$w = "(p.products_id = '$s' OR d.products_name LIKE '%$s%' OR p.products_model LIKE '%$s%') AND  " ;//original version of search
            //$w = "( p.products_id = '$s' OR d.products_name LIKE '%$s%' OR p.products_model LIKE '$s%' ) AND  " ;//changed search to products_model 'startes with'.
         	//$w = "( p.products_id = '$s' OR d.products_name LIKE '%$s%' ) AND  " ;//removed products_model from search
            $w = " AND ( p.products_id = '$s' OR d.products_name LIKE '%$s%' OR p.products_model LIKE '$s%' ) " ;//changed search to products_model 'startes with'.
		} else {
		    $w = ''; 
			$s = '';
		}

      	//Show last edited record or Limit number of records displayed on page
      	$SearchRange = null;
      	if( $ReturnedProductID != null && !isset($_GET['search']) ){
      		$ReturnedProductID = zen_db_input($ReturnedProductID);
      		//$w = "( p.products_id = '$ReturnedProductID' ) AND  " ;//sets returned record to display
      		$w = " AND ( p.products_id = '$ReturnedProductID' ) " ;//sets returned record to display
	      	$SearchRange = "limit 1";//show only selected record
	  	} /*elseif ( $ReturnedProductID != null && isset($_GET['search'])) {
      		$ReturnedProductID = zen_db_input($ReturnedProductID);
	  		$NumberRecordsShown = zen_db_input($NumberRecordsShown);
      }*/
	  	elseif( $NumberRecordsShown > 0 && $SearchBoxOnly == 'false' ){
	  		$NumberRecordsShown = zen_db_input($NumberRecordsShown);
			$SearchRange = " limit $NumberRecordsShown";//sets start record and total number of records to display
		}
		elseif( $SearchBoxOnly == 'true' && !isset($_GET['search']) ){
		   	$SearchRange = "limit 0";//hides all records
		}

        $retArr = array();
/*        $query_products =    'select distinct pa.products_id, d.products_name, p.products_quantity, 
						p.products_model, p.products_image, p.products_type, p.master_categories_id
						
						FROM '.TABLE_PRODUCTS_ATTRIBUTES.' pa
						left join '.TABLE_PRODUCTS_DESCRIPTION.' d on (pa.products_id = d.products_id)
						left join '.TABLE_PRODUCTS.' p on (pa.products_id = p.products_id)
						
						WHERE d.language_id='.$language_id.'
						' . $w . '
						order by d.products_name
						'.$SearchRange.'';*/
        if (isset($_GET['page']) && ($_GET['page'] > 1)) $rows = $_GET['page'] * STOCK_SET_SBA_NUMRECORDS - STOCK_SET_SBA_NUMRECORDS;

        $query_products =    "select distinct pa.products_id, d.products_name, p.products_quantity, p.products_model, p.products_image, p.products_type, p.master_categories_id FROM ".TABLE_PRODUCTS_ATTRIBUTES." pa, ".TABLE_PRODUCTS_DESCRIPTION." d, ".TABLE_PRODUCTS." p WHERE d.language_id='".$language_id."' and pa.products_id = d.products_id and pa.products_id = p.products_id " . $w . " order by d.products_name ".$SearchRange."";

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
    $html .= zen_draw_form('stock_update', FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK . '_ajax', 'save=1&amp;pid='.$ReturnedProductID.'&amp;page='.$_GET['page'], 'post');
    $html .= zen_draw_hidden_field('save', '1');
    $html .= zen_draw_hidden_field('pid', $ReturnedProductID);
    $html .= zen_image_submit('button_save.gif', IMAGE_SAVE) . ' Hint: To quickly edit click in the "Quantity in Stock" field.';
       $html .= '<br/>';
    $html .= '
    <table id="mainProductTable"> 
    <tr>
      <th class="thProdId">'.PWA_PRODUCT_ID.'</th>
      <th class="thProdName">'.PWA_PRODUCT_NAME.'</th>';
    
    if (STOCK_SHOW_IMAGE == 'true') {$html .= '<th class="thProdImage">'.PWA_PRODUCT_IMAGE.'</th>';}   

        $html .= '<th class="thProdModel">'.PWA_PRODUCT_MODEL.'</th>            
              <th class="thProdQty">'.PWA_QUANTITY_FOR_ALL_VARIANTS.'</th>
              <th class="thProdAdd">'.PWA_ADD_QUANTITY.'</th> 
              <th class="thProdSync">'.PWA_SYNC_QUANTITY.'</th>
              </tr>';
        
        while(!$products->EOF){ 
			    $html .= '<tr>'."\n";
			    $html .= '<td colspan="7">'."\n";
			    $html .= '<div class="productGroup">'."\n";
			    $html .= '<table>'. "\n";
		        $html .= '<tr class="productRow">'."\n";
		        $html .= '<td class="tdProdId">'.$products->fields['products_id'].'</td>';
		        $html .= '<td class="tdProdName">'.$products->fields['products_name'].'</td>';
		        
		        if (STOCK_SHOW_IMAGE == 'true') {$html .= '<td class="tdProdImage">'.zen_info_image(zen_output_string($products->fields['products_image']), zen_output_string($products->fields['products_name']), "60", "60").'</td>';}
		        
		        //product.php? page=1 & product_type=1 & cPath=13 & pID=1042 & action=new_product
		        //$html .= '<td class="tdProdModel">'.$products->fields['products_model'] .' </td>';
		        $html .= '<td class="tdProdModel">'.$products->fields['products_model'] . '<br /><a href="'.zen_href_link(FILENAME_PRODUCT, "page=1&amp;product_type=".$products->fields['products_type']."&amp;cPath=".$products->fields['master_categories_id']."&amp;pID=".$products->fields['products_id']."&amp;action=new_product", 'NONSSL').'">Link</a> </td>';
		        $html .= '<td class="tdProdQty">'.$products->fields['products_quantity'].'</td>';
		        $html .= '<td class="tdProdAdd"><a href="'.zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, "action=add&amp;products_id=".$products->fields['products_id'], 'NONSSL').'">' . PWA_ADD_QUANTITY . '</a><br /><br /><a href="'.zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, "action=delete_all&amp;products_id=".$products->fields['products_id'], 'NONSSL').'">'.PWA_DELETE_VARIANT_ALL.'</a></td>';
		        $html .= '<td class="tdProdSync"><a href="'.zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, "action=resync&amp;products_id=".$products->fields['products_id'], 'NONSSL').'">' . PWA_SYNC_QUANTITY . '</a></td>';
		        $html .= '</tr>'."\n";
		        $html .= '</table>'."\n";
		        
          // SUB            
          $query = 'select * from '.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.' where products_id="'.$products->fields['products_id'].'"
                    order by sort ASC;';

          $attribute_products = $db->Execute($query);
          if($attribute_products->RecordCount() > 0){

              $html .= '<table class="stockAttributesTable">';
              $html .= '<tr>';
              $html .= '<th class="stockAttributesHeadingStockId">'.PWA_STOCK_ID.'</th>
              			<th class="stockAttributesHeadingComboId" title="This number is the Product ID and related Attributes (Unique Combo).">'.PWA_PAC.'</th>
              			<th class="stockAttributesHeadingVariant">'.PWA_VARIANT.'</th>
              			<th class="stockAttributesHeadingQuantity">'.PWA_QUANTITY_IN_STOCK.'</th>
              			<th class="stockAttributesHeadingSort">'.PWA_SORT_ORDER.'</th>
              			<th class="stockAttributesHeadingCustomid" title="The Custom ID MUST be Unique, no duplicates allowed!">'.PWA_CUSTOM_ID.'</th>
              			<th class="stockAttributesHeadingSKUTitleId">'.PWA_SKU_TITLE.'</th>
              			<th class="stockAttributesHeadingEdit">'.PWA_EDIT.'</th>
              			<th class="stockAttributesHeadingDelete">'.PWA_DELETE.'</th>';
              $html .= '</tr>';

              while(!$attribute_products->EOF){
              	
                  $html .= '<tr id="sid-'. $attribute_products->fields['stock_id'] .'">';
                  $html .= '<td class="stockAttributesCellStockId">'."\n";
                  $html .= $attribute_products->fields['stock_id'];
                  $html .= '</td>'."\n";
                  $html .= '<td>' . $attribute_products->fields['product_attribute_combo'] . '</td>'."\n";
                  $html .= '<td class="stockAttributesCellVariant">'."\n";
                 
                  if (PRODUCTS_OPTIONS_SORT_ORDER == '0') {
                    $options_order_by= ' order by LPAD(po.products_options_sort_order,11,"0"), po.products_options_name';
                  } else {
                    $options_order_by= ' order by po.products_options_name';
                  }

                  $sort2_query = "SELECT DISTINCT pa.products_attributes_id 
                         FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
			             LEFT JOIN " . TABLE_PRODUCTS_OPTIONS . " po on (po.products_options_id = pa.options_id) 
                         WHERE pa.products_attributes_id in (" . $attribute_products->fields['stock_attributes'] . ")
                         " . $options_order_by; 
                  $sort_class = $db->Execute($sort2_query);
                  $array_temp_sorted_array = array();
                  $attributes_of_stock = array();
                  while (!$sort_class->EOF) {
                    $attributes_of_stock[] = $sort_class->fields['products_attributes_id'];
                    $sort_class->MoveNext();
                  }

                  $attributes_output = array();
                  foreach($attributes_of_stock as $attri_id)
                  {
                      $stock_attribute = $this->get_attributes_name($attri_id, $_SESSION['languages_id']);
                      if ($stock_attribute['option'] == '' && $stock_attribute['value'] == '') {
                        // delete stock attribute
                        $db->Execute("DELETE FROM " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " WHERE stock_id = " . $attribute_products->fields['stock_id'] . " LIMIT 1;");
                      } else { 
                        $attributes_output[] = '<strong>'.$stock_attribute['option'].':</strong> '.$stock_attribute['value'].'<br />';
                      }
                  }
//                  sort($attributes_output);
                  $html .= implode("\n",$attributes_output);

                  $html .= '</td>'."\n";
                  $html .= '<td class="stockAttributesCellQuantity editthis" id="stockid-quantity-'. $attribute_products->fields['stock_id'] .'">'.$attribute_products->fields['quantity'].'</td>'."\n";
                  $html .= '<td class="stockAttributesCellSort editthis" id="stockid-sort-'. $attribute_products->fields['stock_id'] .'">'.$attribute_products->fields['sort'].'</td>'."\n";
                  $html .= '<td class="stockAttributesCellCustomid editthis" id="stockid-customid-'. $attribute_products->fields['stock_id'] .'">'.$attribute_products->fields['customid'].'</td>'."\n";
                  $html .= '<td class="stockAttributesCellTitle" id="stockid-title-'. $attribute_products->fields['stock_id'] .'">'.$attribute_products->fields['title'].'</td>'."\n";
                  $html .= '<td class="stockAttributesCellEdit">'."\n";
                  $html .= '<a href="'.zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, "action=edit&amp;products_id=".$products->fields['products_id'].'&amp;attributes='.$attribute_products->fields['stock_attributes'].'&amp;q='.$attribute_products->fields['quantity'], 'NONSSL').'">'.PWA_EDIT_QUANTITY.'</a>'; //s_mack:prefill_quantity
                  $html .= '</td>'."\n";
                  $html .= '<td class="stockAttributesCellDelete">'."\n";
                  $html .= '<a href="'.zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, "action=delete&amp;products_id=".$products->fields['products_id'].'&amp;attributes='.$attribute_products->fields['stock_attributes'], 'NONSSL').'">'.PWA_DELETE_VARIANT.'</a>';
                  $html .= '</td>'."\n";
                  $html .= '</tr>'."\n";
                 

                  $attribute_products->MoveNext();
              }
              $html .= '</table>';
          }
          $html .= '</div>'."\n";
          $products->MoveNext();   
      }
      $html .= '</table>' . "\n";
      $html .= zen_image_submit('button_save.gif', IMAGE_SAVE);
      $html .= '</form>'."\n";
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
function saveAttrib(){

	global $db;
//	$stock = $products_with_attributes_stock_class; // Should replace all cases of $stock with the class variable name.
    $i = 0;
    foreach ($_POST as $key => $value) {
      $matches = array();
      
      if(preg_match('/stockid-(.*?)-(.*)/', $key, $matches)) {
        // $matches[1] is expected to be the pwas database table field to be updated
        // $matches[2] is expected to be the pwas stock_id to be updated

        $tabledata = '';
        $stock_id = null;
        
        $tabledata = $matches[1];
        $stock_id = $matches[2];
        
        switch ($tabledata) {
          case 'quantity':
          case 'sort':
            $value = doubleval($value); // Get a float value
            $value = $db->getBindVarValue($value, 'float');
            break;
          case 'customid':
          case 'title':
            if ($db->getBindVarValue('NULL', 'string') === 'null' && $value !== 'NULL') {
              if(empty($value) || is_null($value)){$value = 'NULL';}
              $value = $db->getBindVarValue($value, 'string');
            } else {
              $value = $db->prepare_input($value); // Maybe if numeric bind to float, else bind to string.
              $value = $this->nullDataEntry($value); // Get the value or string of entered text, if there is nothing then be able to store a null value that is not the text 'null'.
              if(empty($value) || is_null($value)){$value = 'null';}
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

/*        if($id1 > 0){
          $value = $this->nullDataEntry($value);
          if(empty($value) || is_null($value)){$value = 'null';}
           $sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET title = $value WHERE stock_id = " .$id1. " LIMIT 1";
           $db->execute($sql);
          $i++;
        }*/
/*        if($id2 > 0){
          $value = doubleval($value);
          if(empty($value) || is_null($value)){$value = 0;}
      $sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET quantity = $value WHERE stock_id = " .$id2. " LIMIT 1";
            $db->execute($sql);
            $i++;
        }      
        if($id3 > 0){
          $value = doubleval($value);
          if(empty($value) || is_null($value)){$value = 0;}
          $sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET sort = $value WHERE stock_id = " .$id3. " LIMIT 1";
          $db->execute($sql);
          $i++;
        }
        if($id4 > 0){
          $value = addslashes($value);
          $value = $this->nullDataEntry($value);
          if(empty($value) || is_null($value)){$value = 'null';}
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
function updateAttribQty($stock_id = null, $quantity = null){
	global $db;

	if(empty($quantity) || is_null($quantity)){$quantity = 0;}
	if( is_numeric($stock_id) && is_numeric($quantity) ){
      $query = 'update `'.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.'` set quantity=:quantity: where stock_id=:stock_id: limit 1';
      $query = $db->bindVars($query, ':quantity:', $quantity, 'passthru');
      $query = $db->bindVars($query, ':stock_id:', $stock_id, 'integer');
      $result = $db->execute($query);
	}

	return $result;
}

//New attribute qty insert
//The on duplicate updates an existing record instead of adding a new one
function insertNewAttribQty($products_id = null, $productAttributeCombo = null, $strAttributes = null, $quantity = 0, $customid = null, $skuTitle = null){
	global $db;
	//$stock = $products_with_attributes_stock_class; // Should replace all instance of $stock with the class variable.
	$productAttributeCombo = $this->nullDataEntry($productAttributeCombo);//sets proper quoting for input
	$skuTitle = addslashes($skuTitle);
	$skuTitle = $this->nullDataEntry($skuTitle);//sets proper quoting for input
	$customid = addslashes($customid);
	$customid = $this->nullDataEntry($customid);//sets proper quoting for input
	$strAttributes = $this->nullDataEntry($strAttributes);//sets proper quoting for input
	
	//Set quantity to 0 if not valid input
	if( !is_numeric($quantity) ){
		$quantity = 0;
	}
	
	if( is_numeric($products_id) && isset($strAttributes) && is_numeric($quantity) ){
 		$query = "insert into ". TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK ." (`products_id`, `product_attribute_combo`, `stock_attributes`, `quantity`, `customid`, `title`) 
 					values ($products_id, $productAttributeCombo, $strAttributes, $quantity, $customid, $skuTitle)
 							ON DUPLICATE KEY UPDATE 
 							`quantity` = $quantity,
					 		`customid` =  $customid,
 							`title` = $skuTitle";
 		$result = $db->execute($query);
	}
	
	return $result;
}

//IN-WORK New attribute qty insert NEEDS MORE THOUGHT
//NEED one qty for multiple attributes, this does not accomplish this.
//The on duplicate updates an existing record instead of adding a new one
function insertTablePASR($products_id = null, $strAttributes = null, $quantity = null, $customid = null){
	
	global $db;
	// $stock = $products_with_attributes_stock_class;
	$customid = addslashes($customid);
	$customid = $this->nullDataEntry($customid);//sets proper quoting for input
	$strAttributes = $this->nullDataEntry($strAttributes);//sets proper quoting for input

	//INSERT INTO `znc_products_attributes_stock_relationship` (`products_id`, `products_attributes_id`, `products_attributes_stock_id`) VALUES (226, 1121, 37);
	
	//Table PASR (Inset and get $pasrid for next query)
	if( is_numeric($products_id) && isset($strAttributes) ){
		
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
	
		if( $result == 'true' ){
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
	if( is_numeric($quantity) && is_numeric($pasrid) ){
		
		$query = "insert into ". TABLE_PRODUCTS_ATTRIBUTES_STOCK ." (`quantity`,`customid`)
					values ($quantity, $customid)
					ON DUPLICATE KEY UPDATE
					`quantity` = $quantity,
					`customid` =  $customid;";
		$result = $db->execute($query);
		
// 		//Get the last records ID
// 		$query = "select pas.products_attributes_stock_id
// 					from  ". TABLE_PRODUCTS_ATTRIBUTES_STOCK ."  pas
// 					order by pas.products_attributes_stock_id desc
// 					limit 1;";
// 		$result = $db->execute($query);
// 		$pasid = $result->fields['products_attributes_stock_id'];
		
// 		$pasid = $stock->nullDataEntry($pasid);//sets proper quoting for input
		
		//UPDATE `znc_products_attributes_stock_relationship` SET `products_attributes_stock_id`=26 WHERE  `products_attributes_stock_relationship_id`=27 LIMIT 1;
// 		$query = "UPDATE ". TABLE_PRODUCTS_ATTRIBUTES_STOCK_RELATIONSHIP ." SET `products_attributes_stock_id` = $pasid
// 						where `products_attributes_stock_relationship_id` = $pasrid
// 						LIMIT 1;";
// 		$result = $db->execute($query);
		
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
function insertTablePAS($products_id = null, $quantity = null, $customid = null){

	global $db;
	//$stock = $products_with_attributes_stock_class;
	$customid = addslashes($customid);
	$customid = $this->nullDataEntry($customid);//sets proper quoting for input

// 	INSERT INTO `znc_products_attributes_stock` (`products_id`, `quantity`, `customid`) VALUES (226, 636, 'test37');
	
	//Table PASR (Inset and get $pasrid for next query)
	if( is_numeric($products_id) ){

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
function updateCustomIDAttrib($stockid = null, $customid = null){
	global $db;
	//$stock = $products_with_attributes_stock_class;
	$customid = addslashes($customid);
	$customid = $this->nullDataEntry($customid);//sets proper quoting for input

	if( $customid && is_numeric($stockid) ){
		$query = 'update ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' set customid = ' . $customid . ' where stock_id = ' . $stockid . ' limit 1';
		$result = $db->execute($query);
	}

	return $result;
}

//Update  sku Title of Attribute using the StockID as a key
function updateTitleAttrib($stockid = null, $skuTitle = null){
	global $db;
	//$stock = $products_with_attributes_stock_class;
	$skuTitle = addslashes($skuTitle);
	$skuTitle = $this->nullDataEntry($skuTitle);//sets proper quoting for input

	if( $skuTitle && is_numeric($stockid) ){
		$query = 'update ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' set title = ' . $skuTitle . ' where stock_id = ' . $stockid . ' limit 1';
		$result = $db->execute($query);
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
function selectItemID($Table, $Field, $current = null, $providedQuery = null, $name= null, $id = null, $class = null, $style = null, $onChange = null){

	global $db;
	
	if(!$name){
		//use the $Field as the select NAME if no $name is provided
		$name = zen_db_input($Field);
	}
	if(!$id){
		//use the $Field as the select ID if no $id is provided
		$id = zen_db_input($Field);
	}

	if($providedQuery){
		$query = $providedQuery;//provided from calling object
	}
	else{
		$Table = zen_db_input($Table);
		$Field = zen_db_input($Field);
 		$query = "SELECT * FROM $Table ORDER BY $Field ASC";
	}

	if($onChange){
		$onChange = "onchange=\"selectItem()\"";
	}
		
	$class = zen_db_input($class);
	
	$Output = "<SELECT class='".$class."' id='".$id."' name='".$name."' $onChange >";//create selection list
    $Output .= "<option value='' $style>Please Select a Search Item Below...</option>";//adds blank entry as first item in list

	/* Fields that may be of use in returned set
	["products_id"]
	["products_name"]
	["products_quantity"]
	["products_model"]
	["products_image"]
	 */
    $i = 1;
	$result = $db->Execute($query);
   	while(!$result->EOF){

   		//set each row background color
   		if($i == 1){
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
            
		if ( ($Item == $current AND $current != NULL) || ($rowID == $current AND $current != NULL) ){
	    	$Output .= "<option selected='selected' $style value='".$rowID."'>$Item</option>";
	    }
	    else{
	    	$Output .= "<option $style value='".$rowID."'>$Item</option>";
	    }

		$result->MoveNext();
	}

	$Output .= "</select>";

	return $Output;
}

//NULL entry for database
function nullDataEntry($fieldtoNULL){

	//Need to test for absolute 0 (===), else compare will convert $fieldtoNULL to a number (null) and evauluate as a null 
	//This is due to PHP string to number compare "feature"
	if(!empty($fieldtoNULL) || $fieldtoNULL === 0){
		if(is_numeric($fieldtoNULL) || $fieldtoNULL === 0){
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
  		
  		if(is_array($attributes) && sizeof($attributes) > 0){
  			// check if attribute stock values have been set for the product
  			// if there are will we continue, otherwise we'll use product level data
        $attribute_stock = $db->Execute("select stock_id 
                                         FROM " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " 
                                         WHERE products_id = " . (int)$products_id . ";");
  	
  			if ($attribute_stock->RecordCount() > 0) {
  				// search for details for the particular attributes combination
          $first_search = ' WHERE options_values_id IN ('.implode(',',zen_db_prepare_input($attributes)).') ';
  				
  				// obtain the attribute ids
  				$query = 'select products_attributes_id 
  						from '.TABLE_PRODUCTS_ATTRIBUTES.' 
  								:first_search:
  								and products_id = :products_id: 
  								order by products_attributes_id;';
  				$query = $db->bindVars($query, ':first_search:', $first_search, 'noquotestring');
  				$query = $db->bindVars($query, ':products_id:', $products_id, 'integer');
  				$attributes_new = $db->Execute($query);
  				
  				while(!$attributes_new->EOF){
  					$stock_attributes[] = $attributes_new->fields['products_attributes_id'];
  					$attributes_new->MoveNext();
  				}

          $stock_attributes_comb = implode(',',$stock_attributes);
  			}
  			
  			//Get product model
  			$customid_model_query = 'select products_model 
						  					from '.TABLE_PRODUCTS.' 
						  					where products_id = '. (int)$products_id . ';';

  			//Get custom id as products_model considering that all of the attributes as a group define the customid.
  			$customid_query = 'select customid as products_model
		  							from '.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.' 
		  							where products_id = :products_id: 
		  							and stock_attributes in (:stock_attributes:)'; 
        $customid_query = $db->bindVars($customid_query, ':products_id:', $products_id, 'integer');
        $customid_query = $db->bindVars($customid_query, ':stock_attributes:', $stock_attributes_comb, 'string');
        $customid = $db->Execute($customid_query); //moved to inside this loop as for some reason it has made
        
        if ($attribute_stock->RecordCount() > 0 && $customid->RecordCount() == 0 && zen_not_null($stock_attributes_comb)){ // if a customid does not exist for the combination of attributes then perhaps the attributes are individually listed.
  			  $customid_query = 'select customid as products_model
		  							from '.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.' 
		  							where products_id = :products_id: 
		  							and stock_attributes in (:stock_attributes:)'; 
          $customid_query = $db->bindVars($customid_query, ':products_id:', $products_id, 'integer');
          $customid_query = $db->bindVars($customid_query, ':stock_attributes:', $stock_attributes_comb, 'passthru');
  		    $customid = $db->Execute($customid_query); //moved to inside this loop as for some reason it has made
        }
  		}// If array
  		
//  		$customid = $db->Execute($customid_query);
      if($customid->RecordCount() > 0 && $customid->fields['products_model']){
  		
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
	  		while(!$customid->EOF && zen_not_null($customid->fields['products_model'])){
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
