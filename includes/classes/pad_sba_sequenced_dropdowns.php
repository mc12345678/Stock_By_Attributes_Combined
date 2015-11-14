<?php

/*
      QT Pro Version 4.1
  
      pad_sequenced_dropdowns.php
  
      Contribution extension to:
        osCommerce, Open Source E-Commerce Solutions
        http://www.oscommerce.com
     
      Copyright (c) 2004, 2005 Ralph Day
      Released under the GNU General Public License
  
      Based on prior works released under the GNU General Public License:
        QT Pro prior versions
          Ralph Day, October 2004
          Tom Wojcik aka TomThumb 2004/07/03 based on work by Michael Coffman aka coffman
          FREEZEHELL - 08/11/2003 freezehell@hotmail.com Copyright (c) 2003 IBWO
          Joseph Shain, January 2003
        osCommerce MS2
          Copyright (c) 2003 osCommerce
          
      Modifications made:
          11/2004 - Created
          12/2004 - Fix _draw_dropdown_sequence_js to prevent js error when all attribute combinations
                    are out of stock
          03/2005 - Remove '&' for pass by reference from parameters to call of
                    _build_attributes_combinations.  Only needed on method definition and causes
                    error messages on some php versions/configurations
  08/17/2015 - mc12345678: Remade to offer ZC tags around the attributes.
  11/14/2015   mc12345678 Reworked output of javascript to attempt to force sorted order.
*******************************************************************************************
  
      QT Pro Product Attributes Display Plugin
  
      pad_sequenced_dropdowns.php - Display stocked product attributes first as one dropdown for each attribute
                                    with Javascript to force user to select attributes in sequence so only
                                    in-stock combinations are seen.
  
      Class Name: pad_sba_sequenced_dropdowns
  
      This class generates the HTML to display product attributes.  First, product attributes that
      stock is tracked for are displayed, each attribute in its own dropdown list with Javascript to
      force user to select attributes in sequence so only in-stock combinations are seen.  Then
      attributes that stock is not tracked for are displayed, each attribute in its own dropdown list.
  
      Methods overidden or added:
  
        _draw_stocked_attributes            draw attributes that stock is tracked for
        _draw_dropdown_sequence_js          draw Javascript to force the attributes to be selected in
                                            sequence
        _SetConfigurationProperties         set local properties
                                            
*/
require_once(DIR_WS_CLASSES . 'pad_multiple_dropdowns.php');

class pad_sba_sequenced_dropdowns extends pad_multiple_dropdowns {


/*
    Method: _draw_stocked_attributes
  
    draw dropdown lists for attributes that stock is tracked for

  
    Parameters:
  
      none
  
    Returns:
  
      string:         HTML to display dropdown lists for attributes that stock is tracked for
  
*/

  function _draw_stocked_attributes() {
    global $db, $options_name;

    $out = '';
    $out2 = '';
    $outendecode = '<script type="text/javascript">var htmlEnDeCode = (function() {
    var charToEntityRegex,
        entityToCharRegex,
        charToEntity,
        entityToChar;

    function resetCharacterEntities() {
        charToEntity = {};
        entityToChar = {};
        // add the default set
        addCharacterEntities({
            \'&amp;\'     :   \'&\',
            \'&gt;\'      :   \'>\',
            \'&lt;\'      :   \'<\',
            \'&quot;\'    :   \'"\',
            \'&#39;\'     :   "\'"
        });
    }

    function addCharacterEntities(newEntities) {
        var charKeys = [],
            entityKeys = [],
            key, echar;
        for (key in newEntities) {
            echar = newEntities[key];
            entityToChar[key] = echar;
            charToEntity[echar] = key;
            charKeys.push(echar);
            entityKeys.push(key);
        }
        charToEntityRegex = new RegExp(\'(\' + charKeys.join(\'|\') + \')\', \'g\');
        entityToCharRegex = new RegExp(\'(\' + entityKeys.join(\'|\') + \'|&#[0-9]{1,5};\' + \')\', \'g\');
    }

    function htmlEncode(value){
        var htmlEncodeReplaceFn = function(match, capture) {
            return charToEntity[capture];
        };

        return (!value) ? value : String(value).replace(charToEntityRegex, htmlEncodeReplaceFn);
    }

    function htmlDecode(value) {
        var htmlDecodeReplaceFn = function(match, capture) {
            return (capture in entityToChar) ? entityToChar[capture] : String.fromCharCode(parseInt(capture.substr(2), 10));
        };

        return (!value) ? value : String(value).replace(entityToCharRegex, htmlDecodeReplaceFn);
    }

    resetCharacterEntities();

    return {
        htmlEncode: htmlEncode,
        htmlDecode: htmlDecode
    };
})();</script>';
    $out .= "\n" . $outendecode;
    $out2 .= "\n" . $outendecode;
    $attributes = array();

    $attributes = $this->_build_attributes_array(true, true);
    if (sizeof($attributes) <= 1) {
      return parent::_draw_stocked_attributes();
    }

    /* for ($o=0; $o<=sizeof($attributes); $o++) */ {
      $o = 0;
      // Check stock
//var_dump($attributes[0]);
      $s = sizeof($attributes[$o]['ovals']);
      for ($a = 0; $a < $s; $a++) {

// mc12345678 NEED TO PERFORM ABOVE QUERY BASED OFF OF THE INFORMATION IN $attributes[0]['ovals'] to pull only the data associated with the one attribute in the first selection... Needs to be clear enough that the sequence of the data searched for identifies the appropriate attribute.  Also need to make sure that the subsequent data forced to display below actually pulls the out of stock information associated with the sub (sub-sub(sub-sub-sub)) attribute.

        $attribute_stock_query = "select sum(pwas.quantity) as quantity from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " pwas where pwas.products_id = :products_id: AND pwas.quantity >= 0 AND pwas.stock_attributes like (SELECT products_attributes_id from " . TABLE_PRODUCTS_ATTRIBUTES . " WHERE products_id = :products_id: and options_values_id = :options_values_id:) OR pwas.stock_attributes like CONCAT((SELECT products_attributes_id from " . TABLE_PRODUCTS_ATTRIBUTES . " WHERE products_id = :products_id: and options_values_id = :options_values_id:),',%') or pwas.stock_attributes like CONCAT('%,',(SELECT products_attributes_id from " . TABLE_PRODUCTS_ATTRIBUTES . " WHERE products_id = :products_id: and options_values_id = :options_values_id:),',%') or pwas.stock_attributes like CONCAT('%,',(SELECT products_attributes_id from " . TABLE_PRODUCTS_ATTRIBUTES . " WHERE products_id = :products_id: and options_values_id = :options_values_id:))";
        $attribute_stock_query = $db->bindVars($attribute_stock_query, ':products_id:', $this->products_id, 'integer');
        $attribute_stock_query = $db->bindVars($attribute_stock_query, ':options_values_id:', $attributes[$o]['ovals'][$a]['id'], 'integer');


        $attribute_stock = $db->Execute($attribute_stock_query);
//echo 'Attrib stock_' . $a . ' is: ' . $attribute_stock->RecordCount();
        $out_of_stock = (($attribute_stock->fields['quantity']) == 0);  // This looks at all variants indicating 0 or no variant being present.  Need to modify to look at the quantity for each variant... So look at the quantity of each and if that quantity is zero then, that line needs to be modified...
        if ($out_of_stock && ($this->show_out_of_stock == 'True')) {
          switch ($this->mark_out_of_stock) {
            case 'Left': $attributes[$o]['ovals'][$a]['text'] = TEXT_OUT_OF_STOCK . ' - ' . zen_output_string_protected($attributes[$o]['ovals'][$a]['text']);
              break;
            case 'Right': $attributes[$o]['ovals'][$a]['text'] =zen_output_string_protected($attributes[$o]['ovals'][$a]['text']) . ' - ' . TEXT_OUT_OF_STOCK;
              break;
          } //end switch
        } //end if
        elseif ($out_of_stock && ($this->show_out_of_stock != 'True')) {
          unset($attributes[$o]['ovals'][$a]);
        } //end elseif
//$attribute_stock->MoveNext();
      } // end for $a
    } // end for $o      
    if (sizeof($attributes[0]['ovals']) == 0) {
      // NEED TO DISPLAY A MESSAGE OR ADD SOMETHING TO THE LIST AS THERE
      //  IS NO PRODUCT TO DISPLAY (ALL OUT OF ORDER) SO NEED TO DO WHAT
      //  NEEDS TO BE DONE IN THAT CONDITION. 
    }
    // Draw first option dropdown with all values
    // Need to consider if the option name is read only ('products_options_type' == PRODUCTS_OPTIONS_TYPE_READONLY ).  If it is, then simply display it and do not make it "selectable"
    // May want something similar for display only attributes, where the information is displayed but not selectable (grayed out)
    //   See the example for single attributes using the SBA dropdown list for consistent formatting.
    //  Also could consider applying other option name choosing styles here, but need to modify the follow on selectors so that
    //   it is clear what action(s) need to be taken to select the applicable product.  Ideally, this will be something modified
    //   after other functionality is confirmed considering the above "issues".  Perhaps to do this, would want to incorporate things
    //   into attributes.php file or other html drawing to minimize the additional logic and changes.  That said, if incorporated
    //   into base logic, then users are more forced to use this method over alternative methods/have to incorporate all the ons and offs
    //   for this method throughout.
    //  May need to modify the array for the attributes in order to accomodate identification.
    //  Need to add the display of other information such as the attribute image.
    if (PRODUCTS_OPTIONS_SORT_ORDER == '0') {
      $options_order_by = ' order by LPAD(popt.products_options_sort_order,11,"0")';
    } else {
      $options_order_by = ' order by popt.products_options_name';
    }

    $sql = "select distinct popt.products_options_comment  
              from        " . TABLE_PRODUCTS_OPTIONS . " popt
              left join " . TABLE_PRODUCTS_ATTRIBUTES . " pa ON (pa.options_id = popt.products_options_id)
              where           pa.products_id = :products_id:              
              and             popt.language_id = :languages_id: 
			  and             popt.products_options_id = :products_options_id: " .
            $options_order_by;
    $sql = $db->bindVars($sql, ':products_id:', $this->products_id, 'integer');
    $sql = $db->bindVars($sql, ':languages_id:', $_SESSION['languages_id'], 'integer');

    $sql2 = $db->bindVars($sql, ':products_options_id:', $attributes[0]['oid'], 'integer');


    $products_options_names = $db->Execute($sql2);

    $options_comment[] = $products_options_names->fields['products_options_comment'];

    $options_comment_position[] = ($products_options_names->fields['products_options_comment_position'] == '1' ? '1' : '0');
    if ($options_comment[0] != '' and $options_comment_position[0] == '0') {
      $out.='<tr><td class="attributesComments">' . /* '<h3 class="attributesComments">' . */ $options_comment[0] . '</td></tr>' /* . '</h3>' */;
      $out2.='<h3 class="attributesComments">' . $options_comment[0] . '</h3>';
    }
    if ($attributes[0]['otype'] == PRODUCTS_OPTIONS_TYPE_READONLY) {
      // Need to load all readonly option values for this option name that are 
      //  associated with this product.
      $out.='<tr><td align="right" class="main"><b>' . $attributes[0]['oname'] . ":</b></td><td class=\"main\"><input type = \"hidden\" name = \"id[" . $attributes[0]['oid'] . "]\"" . " value=\"" . stripslashes($attributes[0]['ovals'][0]['id']) . "\" />" . $attributes[0]['ovals'][0]['text'] . "</td></tr>\n";
      $out2.='<div class="wrapperAttribsOptions">';
    } else {
      $out.='<tr><td align="right" class="main"><b>' . $attributes[0]['oname'] . ":</b></td><td class=\"main\">" . zen_draw_pull_down_menu('id[' . $attributes[0]['oid'] . ']', array_merge(array(array('id' => 0, 'text' => TEXT_SEQUENCED_FIRST . $attributes[0]['oname'])), $attributes[0]['ovals']), $attributes[0]['default'], "onchange=\"i" . $attributes[0]['oid'] . "(this.form);\"") . "</td></tr>\n";
      $out2.='<div class="wrapperAttribsOptions">';
      $out2.='<h4 class="optionName back">';
      $out2.= $options_name[0];
      $out2.='</h4>';
      $out2.='<div class="back">';
      $out2.="\n";
      $out2.=zen_draw_pull_down_menu('id[' . $attributes[0]['oid'] . ']', array_merge(array(array('id' => 0, 'text' => TEXT_SEQUENCED_FIRST . $attributes[0]['oname'])), $attributes[0]['ovals']), $attributes[0]['default'], "id=\"" . "attrib-" . $attributes[0]['oid'] . "\" onchange=\"i" . $attributes[0]['oid'] . "(this.form);\"");
      $out2.='</div>';
      $out2.='<br class="clearBoth" />';
      $out2.='</div>';
    }
    if ($options_comment[0] != '' and
            $options_comment_position[0] == '1') {
      $out.='<div class="ProductInfoComments">' . $options_comment[0] . '</div>';
      $out2.='<div class="ProductInfoComments">'.$options_comment[0].'</div>';
    }

    // Draw second to next to last option dropdowns - no values, with onchange
    for ($o = 1; $o < sizeof($attributes) - 1; $o++) {
      // Need to consider if the option name is read only.  If it is, then simply display it and do not make it "selectable"
      //  May need to modify the array for the attributes in order to accomodate identification.
      $sql2 = $db->bindVars($sql, ':products_options_id:', $attributes[$o]['oid'], 'integer');


      $products_options_names = $db->Execute($sql2);

      $options_comment[] = $products_options_names->fields['products_options_comment'];

      $options_comment_position[] = ($products_options_names->fields['products_options_comment_position'] == '1' ? '1' : '0');
      if ($options_comment[$o] != '' and $options_comment_position[$o] == '0') {
        $out.='<tr><td class="attributesComments">' . /* '<h3 class="attributesComments">' . */ $options_comment[$o] . '</td></tr>' /* . '</h3>' */;
        $out2.='<h3 class="attributesComments">' . $options_comment[0] . '</h3>';
      } // END h3_option_comment
      if ($attributes[$o]['otype'] == PRODUCTS_OPTIONS_TYPE_READONLY) {
        // Need to load all readonly option values for this option name that are 
        //  associated with this product.
        $out.='<tr><td align="right" class="main"><b>' . $attributes[$o]['oname'] . ":</b></td><td class=\"main\"><input type = \"hidden\" name = \"id[" . $attributes[$o]['oid'] . "]\"" . " value=\"" . stripslashes($attributes[$o]['ovals'][0]['id']) . "\" />" . $attributes[$o]['ovals'][0]['text'] . "</td></tr>\n";
        $out2.='<div class="wrapperAttribsOptions">';
      } else {
        $out.='<tr><td align="right" class="main"><b>' . $attributes[$o]['oname'] . ":</b></td><td class=\"main\">" . zen_draw_pull_down_menu('id[' . $attributes[$o]['oid'] . ']', array(array('id' => 0, 'text' => TEXT_SEQUENCED_NEXT . $attributes[$o]['oname'])), '', "onchange=\"i" . $attributes[$o]['oid'] . "(this.form);\"") . "</td></tr>\n";
        $out2.='<div class="wrapperAttribsOptions">';
        $out2.='<h4 class="optionName back">';
        $out2.= $options_name[$o];
        $out2.='</h4>';
        $out2.='<div class="back">';
        $out2.="\n";
        $out2.=zen_draw_pull_down_menu('id[' . $attributes[$o]['oid'] . ']', array(array('id' => 0, 'text' => TEXT_SEQUENCED_NEXT . $attributes[$o]['oname'])), '', "id=\"" . "attrib-" . $attributes[$o]['oid'] . "\" onchange=\"i" . $attributes[$o]['oid'] . "(this.form);\"");
        $out2.='</div>';
        $out2.='<br class="clearBoth" />';
        $out2.='</div>';
      }
      if ($options_comment[$o] != '' and
              $options_comment_position[$o] == '1') {
        $out.='<div class="ProductInfoComments">' . $options_comment[$o] . '</div>';
        $out2.='<div class="ProductInfoComments">' . $options_comment[$o] . '</div>';
      }
    } // end for $o       
    // Draw last option dropdown - no values, no onchange      
    // Need to consider if the option name is read only.  If it is, then simply display it and do not make it "selectable"
    //  May need to modify the array for the attributes in order to accomodate identification.
    $sql2 = $db->bindVars($sql, ':products_options_id:', $attributes[$o]['oid'], 'integer');

    $products_options_names = $db->Execute($sql2);

    $options_comment[] = $products_options_names->fields['products_options_comment'];

    $options_comment_position[] = ($products_options_names->fields['products_options_comment_position'] == '1' ? '1' : '0');
    if ($options_comment[$o] != '' and $options_comment_position[$o] == '0') {
      $out.='<tr><td class="attributesComments">' . $options_comment[$o] . '</td></tr>';
      $out2.='<h3 class="attributesComments">'. $options_comment[$o]. '</h3>';
    } // END h3_option_comment
    if ($attributes[$o]['otype'] == PRODUCTS_OPTIONS_TYPE_READONLY) {
      // Need to load all readonly option values for this option name that are 
      //  associated with this product.
      $out.='<tr><td align="right" class="main"><b>' . $attributes[$o]['oname'] . ":</b></td><td class=\"main\"><input type = \"hidden\" name = \"id[" . $attributes[$o]['oid'] . "]\"" . " value=\"" . stripslashes($attributes[$o]['ovals'][0]['id']) . "\" />" . $attributes[$o]['ovals'][0]['text'] . "</td></tr>\n";
      $out2.='<div class="wrapperAttribsOptions">';
    } else {
      $out.='<tr><td align="right" class="main"><b>' . $attributes[$o]['oname'] . ":</b></td><td class=\"main\">" . zen_draw_pull_down_menu('id[' . $attributes[$o]['oid'] . ']', array(array('id' => 0, 'text' => TEXT_SEQUENCED_NEXT . $attributes[$o]['oname'])), '', "onchange=\"i" . $attributes[$o]['oid'] . "(this.form);\"") . "</td></tr>\n";
      $out2.='<div class="wrapperAttribsOptions">';
      $out2.='<h4 class="optionName back">';
      $out2.= $options_name[$o];
      $out2.='</h4>';
      $out2.='<div class="back">';
      $out2.="\n";
      $out2.=zen_draw_pull_down_menu('id[' . $attributes[$o]['oid'] . ']', array(array('id' => 0, 'text' => TEXT_SEQUENCED_NEXT . $attributes[$o]['oname'])), '', "id=\"" . "attrib-" . $attributes[$o]['oid'] . "\" onchange=\"i" . $attributes[$o]['oid'] . "(this.form);\"");
      $out2.='</div>';
      $out2.='<br class="clearBoth" />';
      $out2.='</div>';
    }
    if ($options_comment[$o] != '' and
            $options_comment_position[$o] == '1') {
      $out.='<div class="ProductInfoComments">' . $options_comment[$o] . '</div>';
      $out2.='<div class="ProductInfoComments">' . $options_comment[$o] . '</div>';
    }

//      $out.=$this->_draw_out_of_stock_message_js($attributes);
    $out.=$this->_draw_dropdown_sequence_js($attributes);
    $out2.=$this->_draw_dropdown_sequence_js($attributes);

    return (SBA_ZC_DEFAULT === 'true' ? $out2 : $out);
    } // end if size attributes


/*
    Method: _draw_nonstocked_attributes
  
    Draws the product attributes that stock is not tracked for.
    Intended for class internal use only.
  
    Attributes that stock is not tracked for are drawn with one dropdown list per attribute.
  
    Parameters:
  
      none
  
    Returns:
  
      string:       HTML for displaying the product attributes that stock is not tracked for
  
*/
    function _draw_nonstocked_attributes() {
      $out='';
      $out2='';
      $nonstocked_attributes = $this->_build_attributes_array(false, true);
      foreach($nonstocked_attributes as $nonstocked)
      {
        $out.='<tr><td align="right" class=main>';
        $out2.='<h4 class="optionName back">';
        $out.='<b>'.$nonstocked['oname'].":</b>";
        $out2.='<b>'.$nonstocked['oname'].":</b>";
        $out.="</td><td class=main>";
        $out2.='</h4><div class="back">';
        $out2.="\n";
        $out.=zen_draw_pull_down_menu('id['.$nonstocked['oid'].']',$nonstocked['ovals'],$nonstocked['default']);
        $out2.=zen_draw_pull_down_menu('id['.$nonstocked['oid'].']',$nonstocked['ovals'],$nonstocked['default'], "id=\"" . "attrib-" . $nonstocked['oid'] . "\" onchange=\"i" . $nonstocked['oid'] . "(this.form);\"");
        $out.="</td></tr>";
        $out2.='</div><br class="clearBoth" /></div>';
        $out.="\n";
      }
      return (SBA_ZC_DEFAULT === 'true' ? $out2 : $out);
    }

// end if size attributes

  /*
    Method: _draw_dropdown_sequence_js

    draw Javascript to display out of stock message for out of stock attribute combinations


    Parameters:
  
      $attributes     array   Array of attributes for the product.  Format is as returned by
                              _build_attributes_array.
  
    Returns:
  
      string:         Javascript to force user to select stocked dropdowns in sequence
  
*/
  function _draw_dropdown_sequence_js($attributes) {
    $out = '';
    $outArrayList = array();
    $combinations = array();
    $selected_combination = 0;
    $this->_build_attributes_combinations($attributes, true, 'None', $combinations, $selected_combination); // Used to identify all possible combinations as provided in SBA.

    $this->_build_attributes_combinations($attributes, false, 'None', $combinations2, $selected_combination); // This is used to identify what is out of stock by comparison with the above.
// SBA_ZC_DEFAULT
    if (SBA_ZC_DEFAULT !== 'true') {
      $out.="<tr><td>&nbsp;</td><td>";
    }
    $out.="<span id=\"oosmsg\" class=\"errorBox\"></span>";
    if (SBA_ZC_DEFAULT !== 'true') {
      $out.="</td></tr>\n";
      $out.="<tr><td colspan=\"2\">&nbsp;";
    }
    $out.="\n";

    $out.="<script type=\"text/javascript\" language=\"javascript\"><!--\n";
    // build javascript array of in stock combinations of the form
    // {optval1:{optval2:{optval3:1,optval3:1}, optval2:{optval3:1}}, optval1:{optval2:{optval3:1}}};
    $out.="  var stk=" . $this->_draw_js_stock_array($combinations) . ";\n";
    $out.="  var stk2=" . $this->_draw_js_stock_array($combinations2) . ";\n";
    // Going to want to add a third stk tracking quantity to account for the availability of entered variants.
    //   Ie. if a variant doesn't exist in the SBA table, then values associated with the sub-selection should not be displayed.
    //      or if displayed should be selectable to display.
    // js arrays of possible option values/text for dropdowns
    // do all but the first attribute (its dropdown never changes)
    for ($curattr = 1; $curattr < sizeof($attributes); $curattr++) {
      $attr = $attributes[$curattr];
      $out.="  var txt" . $attr['oid'] . "={";
      foreach ($attr['ovals'] as $oval) {
        $out.="\"_" . $oval['id'] . "\"" . ":\"" . zen_output_string_protected($oval['text']) . "\",";
      }
      $out = substr($out, 0, strlen($out) - 1) . "};";
      $out.="\n";
    }

    // js functions to set next dropdown options when a dropdown selection is made
    // do all but last attribute (nothing needs to happen when it changes except additional validation action to improve the customer experience)
    for ($curattr = 0; $curattr < sizeof($attributes); $curattr++) {
      for ($nextattr = $curattr + 1; $nextattr < sizeof($attributes); $nextattr++) {
        if ($attributes[$nextattr]['otype'] != PRODUCTS_OPTIONS_TYPE_READONLY) {

          break 1;
        }
      }

      $attr = $attributes[$curattr];
      $out.="  function i" . $attr['oid'] . "(frm) {\n";
      if ($curattr < sizeof($attributes) - 1) {
        if ( PRODINFO_ATTRIBUTE_POPUP_OUT_OF_STOCK != 'False') {
          $out.="    var displayshown = false;\n";// . ( PRODINFO_ATTRIBUTE_POPUP_OUT_OF_STOCK == 'False' ? "true" : "false") . ";\n"; //Allow control of the alert to provide it one time only.
        }
    		$out.="    var opt;\n";
        $out.="    var span=document.getElementById(\"oosmsg\");\n";
        $out.="    while (span.childNodes[0]) {\n";
        $out.="      span.removeChild(span.childNodes[0]);\n";
        $out.="    }\n";
        $i = key($attributes);
        for ($i = $curattr + 1; $i < sizeof($attributes); $i++) {
          if ($attributes[$i]['otype'] == PRODUCTS_OPTIONS_TYPE_READONLY) {
            // Do not reset the form that is applicable to readonly attributes.
            // 
            // This is however a great point to perform other determinations
            //   to support downstream processing as $nextattr is known.
            //     Either it is equal to the sizeof($attributes) or some value
            //     less than it.  If it is equal then the most recent currattr
            //     that was not a readonly attribute was the last to 
            //     be selectable. If it is less than it, then there is yet a
            //     currattr that is selectable and possible to contain further
            //     data.
            //     If == then really somewhere towards the end of this section
            //     should simply post stock availability based on the hard 
            //     data of the reamining readonly attributes and post the
            //     remaining readonly attributes.
            //     If less than need to have the remaining readonly attributes
            //     displayed, then allow the selection of the remaining
            //     attributes with the next attribute to be evaluated for 
            //     showing the final amount of product available.
            //     
            //     possibly exit the determination of further dropdowns.
            // Do nothing..
          } else {
            $out.="    frm['id[" . $attributes[$i]['oid'] . "]'].length=1;\n";
          }
        }
//         $out.="    stkmsg(frm);\n";
        //Loop on all selections available if all stock were included.
        $out.="    for (opt in stk";
        $outArray = '';
        for ($i = 0; $i <= $curattr; $i++) {
          $outArray.="['_'+frm['id[" . $attributes[$i]['oid'] . "]'].value]";
        }
        $outArrayList[$curattr] = $outArray;
        $out.=$outArray;
        $out.=") {\n";
        //The following checks to verify that the option exists in the list
        //  Without looking at the sub-selection yet.  Is necessary on 
        //  a product with two or more attributes, where any attribute is
        //  already exhausted before the last selectable attribute.
        $out.="      if (";
        for ($i = 0; $i<=$curattr; $i++) {
          $out.="typeof stk2" . $outArrayList[$i] . " != \"undefined\"";
          if ($i<$curattr){
            $out.=" && ";
          }
        }
        $out.=") {\n";
        $out.="        if (typeof stk2";
        $out.=$outArray;
        $out.="[opt] != \"undefined\") {\n";
        //  Add the product to the next selectable list item as it is in stock.
        $out.="          frm['id[" . $attributes[$nextattr]['oid'] . "]'].options[frm['id[" . $attributes[$nextattr]['oid'] . "]'].length]=new Option(htmlEnDeCode.htmlDecode(txt" . $attributes[$nextattr]['oid'] . "[opt])";
        if ($curattr == sizeof($attributes) - 2) {
          if (STOCK_SHOW_ATTRIB_LEVEL_STOCK == 'true') {
            $out.=" + '" . PWA_STOCK_QTY . "' + stk2";
            $out.=$outArray;
            $out.="[opt]";
          }
        }
        $out.=",opt.substring(1));\n";
        $out.="        } else {\n";
        if (PRODINFO_ATTRIBUTE_SHOW_OUT_OF_STOCK == 'True') {
          //  Add the product to the next selectable list item and identify its out-of-stock status as controlled by the admin panel.  
          $out.="          frm['id[" . $attributes[$nextattr]['oid'] . "]'].options[frm['id[" . $attributes[$nextattr]['oid'] . "]'].length]=new Option(htmlEnDeCode.htmlDecode(";
          if (PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK == 'None') {
            $out.="txt" . $attributes[$nextattr]['oid'] . "[opt]";
          } elseif (PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK == 'Left') {
            $out.="'" . PWA_OUT_OF_STOCK . "' + txt" . $attributes[$nextattr]['oid'] . "[opt]";
          } elseif (PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK == 'Right') {
            $out.="txt" . $attributes[$nextattr]['oid'] . "[opt] + '" . PWA_OUT_OF_STOCK . "'";
          }
          $out.="),opt.substring(1));\n";
		  if ((STOCK_ALLOW_CHECKOUT == 'false' && ($curattr == sizeof($attributes) - 2)) || PRODINFO_ATTRIBUTE_NO_ADD_OUT_OF_STOCK == 'True') {
            $out.="          frm['id[" . $attributes[$nextattr]['oid'] . "]'].options[frm['id[" . $attributes[$nextattr]['oid'] . "]'].length-1].disabled = true;\n";
		  }
        }
        $out.="        }\n";
        if ($this->out_of_stock_msgline == 'True') {
          $out.="        stkmsg(frm);\n";
        }
        $out.="      } else {\n";
        if (PRODINFO_ATTRIBUTE_SHOW_OUT_OF_STOCK == 'True') {
          //  Add the product to the next selectable list item and identify its out-of-stock status as controlled by the admin panel.  
          $out.="        frm['id[" . $attributes[$nextattr]['oid'] . "]'].options[frm['id[" . $attributes[$nextattr]['oid'] . "]'].length]=new Option(htmlEnDeCode.htmlDecode(";
          if (PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK == 'None') {
            $out.="txt" . $attributes[$nextattr]['oid'] . "[opt]";
          } elseif (PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK == 'Left') {
            $out.="'" . PWA_OUT_OF_STOCK . "' + txt" . $attributes[$nextattr]['oid'] . "[opt]";
          } elseif (PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK == 'Right') {
            $out.="txt" . $attributes[$nextattr]['oid'] . "[opt] + '" . PWA_OUT_OF_STOCK . "'";
          }
          $out.="),opt.substring(1));\n";
          if ((STOCK_ALLOW_CHECKOUT == 'false' && ($curattr == sizeof($attributes) - 2)) || PRODINFO_ATTRIBUTE_NO_ADD_OUT_OF_STOCK == 'True') {
            $out.="        frm['id[" . $attributes[$nextattr]['oid'] . "]'].options[frm['id[" . $attributes[$nextattr]['oid'] . "]'].length-1].disabled = true;\n";
          }
          if ($this->out_of_stock_msgline == 'True') {
            $out.="        stkmsg(frm);\n";
          }
          if (PRODINFO_ATTRIBUTE_POPUP_OUT_OF_STOCK != 'False') {
            $out.="        if (displayshown != true) {\n";
            $out.="          alert('" . TEXT_JAVA_ALL_SELECT_OUT . "');\n";
//        $out.="          stkmsg(frm);\n";
            $out.="          displayshown=true;\n";
            $out.="        }\n";
          }
        }
        $out.="      }\n";
        $out.="    }\n";
      } else {
        if ($this->out_of_stock_msgline == 'True') {
          $out.="      stkmsg(frm);\n";
        }
        $out.="    if (!chkstk(frm)" . ( PRODINFO_ATTRIBUTE_POPUP_OUT_OF_STOCK == 'False' ? " && false" : "" ) . ") {\n";
//          $out.="      stkmsg(frm);\n";
        $out.="      alert('" . TEXT_JAVA_ONE_SELECT_OUT . "');\n";
        $out.="    } \n"; 
      }
      $out.="  }\n";
    }

    // js to initialize dropdowns to defaults if product id contains attributes (i.e. clicked through to product page from cart)
    $out.="  i" . $attributes[0]['oid'] . "(document.cart_quantity);\n";
    for ($o = 1; $o < sizeof($attributes) - 1; $o++) {
      if ($attributes[$o]['default'] != '') {
        $out.="  document.cart_quantity['id[" . $attributes[$o]['oid'] . "]'].value=" . $attributes[$o]['default'] . ";\n";
        $out.="  i" . $attributes[$o]['oid'] . "(document.cart_quantity);\n";
      } else {
        break;
      }
    }
    if (($o == sizeof($attributes) - 1) && ($attributes[$o]['default'] != '')) {
      $out.="  document.cart_quantity['id[" . $attributes[$o]['oid'] . "]'].value=" . $attributes[$o]['default'] . ";\n";
    }

    // js to not allow add to cart if selections not made
    $out.="  function chksel(form) {\n";
    $out.="    var ok=true;\n";
    foreach ($attributes as $attr) {
      $out.="    if (form['id[" . $attr['oid'] . "]'].value==0) ok=false;\n";
    }
    $out.="    if (!ok) {\n";
    $out.="      alert('" . TEXT_SELECT_OPTIONS . "');\n";
    $out.="      form.action = '';\n";
    $out.="      return false;\n";
    $out.="    } else {\n";
    $out.=" "; //Need to check stock somewhere in this, perhaps some help from other code?
    $out.="      return true;\n";
    $out.="    }\n";
    $out.="  }\n";
    $out.="  document.cart_quantity.onsubmit=function () {chksel(this)};\n";


    $out.="  function chkstk(frm) {\n";

    // build javascript array of in stock combinations
    $out.="    var stk3=" . $this->_draw_js_stock_array($combinations2) . ";\n";
    $out.="    var instk=false;\n";

    // build javascript if statement to test level by level for existence  
    // Check if every menu selection is back to the baseline.
    $out.="    " . str_repeat("  ", 0);
    $out.="if (frm['id[" . $attributes[0]['oid'] . "]'].value == 0";
    for ($i = 1; $i < sizeof($attributes); $i++) {
      $out.=" && frm['id[" . $attributes[$i]['oid'] . "]'].value == 0";
    }
    $out.=") {\n";
    $out.="    " . str_repeat("  ", 1);
    $out.="instk = true;\n";
    $out.="    " . str_repeat("  ", 0);
    $out.="}\n";
    // Begin the cycle 
    for ($j = 0; $j < sizeof($attributes); $j++) {
      //Check if the menu selection is the default selection in the menu
      $out.="    " . str_repeat("  ", $j);
      $out.="if (frm['id[" . $attributes[$j]['oid'] . "]'].value == 0) {\n";
      $out.="    " . str_repeat("  ", $j + 1);
      $out.="return true;\n";
      $out.="    " . str_repeat("  ", $j);
      $out.="}\n";
      $out.="    " . str_repeat("  ", $j);
      // Check if the option is defined/has stock.
      $out.="if (typeof stk3";
      for ($k = 0; $k <= $j; $k++) {
        $out.="[" . "'_'+"  . "frm['id[" . $attributes[$k]['oid'] . "]'].value]";
      }
      $out.=" == \"undefined\") {\n";
      $out.="    " . str_repeat("  ", $j + 1);
      $out.="return false;\n";
      $out.="    " . str_repeat("  ", $j);
      $out.="}\n";
      // If the above have not caused a response, then it is safe to move.
      if ($j == sizeof($attributes) - 1) {
        $out.="    " . str_repeat("  ", $j + 1);
        $out.="return true;\n";
      }
    }
/*	  $out.="if (frm['id[".$attributes[0]['oid']."]'] == 0) {\n";
      for ($i=0; $i<sizeof($attributes); $i++) {
        $out.="    " . str_repeat("  ",$i);
//Starts the checks of stock quantity.
//        $out.='if (stk3';


        $out.='if (stk3';
        for ($j=0; $j<=$i; $j++) {
          $out.="[frm['id[".$attributes[$j]['oid']."]'].value]";
        }
        $out.=") {\n";
        $out.="     " . str_repeat("  ",sizeof($attributes)) . "if(frm['id[".$attributes[$j-1]['oid']."]'].value != 0" . /*" && frm['id[".$attributes[sizeof($attributes)-1]['oid']."]'].value != 0" .*//* ") {\n";
		$out.="     " . str_repeat("  ",sizeof($attributes)+1) . "instk=true;\n";
		$out.="     " . str_repeat("  ",sizeof($attributes)) . "}\n";
      }
//      $out.="    " . str_repeat("  ",sizeof($attributes)) . "instk=true;\n";
      for ($i=sizeof($attributes)-1; $i>0; $i--) {
        $out.="    " . str_repeat("  ",$i) . "}\n";
      }
      $out.="    }\n";
      $out.="}\n";
        for ($j=sizeof($attributes)-1; $j>=0; $j--) {
          $out.="    " . str_repeat("  ",0);
          $out.='if (';
          $out.="frm['id[".$attributes[$j]['oid']."]'].value == 0 && !instk";
		  for ($i=$j-1; $i>=0; $i--) {
		    $out.=" && frm['id[".$attributes[$i]['oid']."]'].value != 0";
			//$out.=" && chkstk()";
		  }
		  $out.=") {\n";
          $out.="    " . str_repeat("  ",1);
		  $out.="return true;\n";
          $out.="    " . str_repeat("  ",0);
          $out.="}\n";
		}*/
/*	  $out.="    if (" . ( instk = false && true ? "" : "") . ") {\n";
	  $out.="      \n";
	  $out.="    }\n";*/
//      $out.="    return instk;\n";
    $out.="  }\n";

    if ($this->out_of_stock_msgline == 'True') {
      // set/reset out of stock message based on selection
      $out.="  function stkmsg(frm) {\n";
      $out.="    var instk=chkstk(frm);\n";
      $out.="    var span=document.getElementById(\"oosmsg\");\n";
      $out.="    while (span.childNodes[0])\n";
      $out.="      span.removeChild(span.childNodes[0]);\n";
      $out.="    if (!instk) {\n";
      $out.="      span.appendChild(document.createTextNode(\"" . TEXT_OUT_OF_STOCK_MESSAGE . "\"));\n";
//        $out.="      alert('" . TEXT_JAVA_ONE_SELECT_OUT . "');\n";
      $out.="    } else {\n";
      $out.="      span.appendChild(document.createTextNode(\" \"));\n";
      $out.="    }\n";
      $out.="  }\n";
      //initialize out of stock message
//          $out.="  stkmsg(document.cart_quantity);\n";
    }
    $out.="//--></script>\n";
    $out.="\n";
    if (SBA_ZC_DEFAULT !== 'true') {
      $out.="</td></tr>\n"; // Removed extra: </td></tr>
    }

    return $out;
  }

  /*
    Method: _draw_js_stock_array

    Draw a Javascript array containing the given attribute combinations.
    Generally used to draw array of in-stock combinations for Javascript out of stock
    validation and messaging.

    Parameters:
  
      $combinations        array   Array of combinations to build the Javascript array for.
                                   Array must be of the form returned by _build_attributes_combinations
                                   Usually this array only contains in-stock combinations.
  
    Returns:
  
      string:                 Javacript array definition.  Excludes the "var xxx=" and terminating ";".  Form is:
                              {optval1:{optval2:{optval3:1,optval3:1}, optval2:{optval3:1}}, optval1:{optval2:{optval3:1}}}
                              For example if there are 3 options and the instock value combinations are:
                                opt1   opt2   opt3
                                  1      5      4
                                  1      5      8
                                  1     10      4
                                  3      5      8
                              The string returned would be
                                {1:{5:{4:1,8:1}, 10:{4:1}}, 3:{5:{8:1}}}
  
*/
  function _draw_js_stock_array($combinations) {
    if (!((isset($combinations)) && (is_array($combinations)) && (sizeof($combinations) > 0))) {
      return '{}';
    }
    $out = '';
    foreach ($combinations[0]['comb'] as $oid => $ovid) {
      $out.='{' . '"_' . zen_output_string_protected($ovid) . '"' . ':';
      $ovids[] = $ovid;
      $opts[] = $oid;
    }
    if (STOCK_SHOW_ATTRIB_LEVEL_STOCK == 'true') {
      //Search for quantity in the SBA table... 
      $numbadd = zen_get_products_stock($_GET['products_id'], $ovids);
      if ($numbadd == 0) {
        $numbadd = '0';
      }
      $out.=$numbadd;
    } else {
      $out.='1';
    }

    for ($combindex = 1; $combindex < sizeof($combinations); $combindex++) {
      $comb = $combinations[$combindex]['comb'];
      for ($i = 0; $i < sizeof($opts) - 1; $i++) {
        if ($comb[$opts[$i]] != $combinations[$combindex - 1]['comb'][$opts[$i]]) {
          break;
        }
      }
      $out.=str_repeat('}', sizeof($opts) - 1 - $i) . ',';
      if ($i < sizeof($opts) - 1) {
        for ($j = $i; $j < sizeof($opts) - 1; $j++) {
          $out.= '"_' . zen_output_string_protected($comb[$opts[$j]]) . '"' . ':{';
        }
      }
      $out.='"_' . zen_output_string_protected($comb[$opts[sizeof($opts) - 1]]) . '"' . ':';
      if (STOCK_SHOW_ATTRIB_LEVEL_STOCK == 'true') {
        $idvals = array();
        foreach ($comb as $ids => $idvalsadd) {
          $idvals[] = $idvalsadd;
        }
        $numadd = zen_get_products_stock($_GET['products_id'], $idvals);
        if ($numadd == 0) {
          $numadd = '0';
        }
        $out.=$numadd;
      } else {
        $out.='1';
      }
    }
    $out.=str_repeat('}', sizeof($opts));

    return $out;
  }

/*
    Method: _SetConfigurationProperties
  
    Set local configuration properties
  
    Parameters:
  
      $prefix      sting     Prefix for the osCommerce DB constants
  
    Returns:
  
      nothing
  
*/
  /*  function _SetConfigurationProperties($prefix) {

    // These properties are not used directly by this class
    // They are set to match how this class displays for the case of a single
    // attribute where the parent class _draw_stocked_attributes method is called
    $this->show_out_of_stock    = 'True';
    $this->mark_out_of_stock    = 'Right';
    $this->out_of_stock_msgline = 'True';
    $this->no_add_out_of_stock  = 'False';

    } */

/*    function _draw_js_stock_array($combinations) {
      if (!((isset($combinations)) && (is_array($combinations)) && (sizeof($combinations) >= 0))){
        return '{}';
      }
      $out='';
      foreach ($combinations[0]['comb'] as $oid=>$ovid) {
        $out.='{'.$ovid.':';
        $opts[]=$oid;
      }
      $out.='1';
      
      for ($combindex=1; $combindex<sizeof($combinations); $combindex++) {
        $comb=$combinations[$combindex]['comb'];
        for ($i=0; $i<sizeof($opts)-1; $i++) {
          if ($comb[$opts[$i]]!=$combinations[$combindex-1]['comb'][$opts[$i]]) break;
        }
        $out.=str_repeat('}',sizeof($opts)-1-$i).',';
        if ($i<sizeof($opts)-1) {
          for ($j=$i; $j<sizeof($opts)-1; $j++)
            $out.=$comb[$opts[$j]].':{';
        }
        $out.=$comb[$opts[sizeof($opts)-1]].':1';
      }
      $out.=str_repeat('}',sizeof($opts));
      
      return $out;
    }*/

/*
    Method: _draw_attributes_start
  
    Draws the start of a table to wrap the product attributes display.
    Intended for class internal use only.
  
    Parameters:
  
      none
  
    Returns:
  
      string:       HTML for start of table
  
*/
    function _draw_attributes_start() {
      $out ='           <h3 id="attribsOptionsText">';
      $out.='            ';
      $out.='             <b>' . TEXT_PRODUCT_OPTIONS . '</b></h3>';
      return $out;
    }
    
/*
    Method: _draw_attributes_end
  
    Draws the end of a table to wrap the product attributes display.
    Intended for class internal use only.
  
    Parameters:
  
      none
  
    Returns:
  
      string:       HTML for end of table
  
*/
    function _draw_attributes_end() {
      return ''; //'           </div>';
    }

/*
    Method: draw
  
    Draws the product attributes.  This is the only method other than the constructor that is
    intended to be called by a user of this class.
  
    Attributes that stock is tracked for are grouped first and drawn with one dropdown list per
    attribute.  All attributes are drawn even if no stock is available for the attribute and no 
    indication is given that the attribute is out of stock.
  
    Attributes that stock is not tracked for are then drawn with one dropdown list per
    attribute.
  
    Parameters:
  
      none
  
    Returns:
  
      string:       HTML for displaying the product attributes
  
*/
    function draw() {

      if (SBA_ZC_DEFAULT === 'true') {
        $out=$this->_draw_attributes_start();

        $out.=$this->_draw_stocked_attributes();
      
        $out.=$this->_draw_nonstocked_attributes();
    
        $out.=$this->_draw_attributes_end();
      } else {
        $out=$this->_draw_table_start();

        $out.=$this->_draw_stocked_attributes();
      
        $out.=$this->_draw_nonstocked_attributes();
    
        $out.=$this->_draw_table_end();
        
      }
      return $out;
      
    }
}
