<?php
/*
      QT Pro Version 4.1
  
      pad_multiple_dropdowns.php
  
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
          12/2004 - Fix _draw_out_of_stock_message_js to add semicolon to end of js stock array
          03/2005 - Remove '&' for pass by reference from parameters to call of
                    _build_attributes_combinations.  Only needed on method definition and causes
                    error messages on some php versions/configurations
  
*******************************************************************************************
  
      QT Pro Product Attributes Display Plugin
  
      pad_multiple_dropdowns.php - Display stocked product attributes first as one dropdown for each attribute.
  
      Class Name: pad_multiple_dropdowns
  
      This class generates the HTML to display product attributes.  First, product attributes that
      stock is tracked for are displayed, each attribute in its own dropdown list.  Then attributes that
      stock is not tracked for are displayed, each attribute in its own dropdown list.
      
      Methods overidden or added:
  
        _draw_stocked_attributes            draw attributes that stock is tracked for
        _draw_out_of_stock_message_js       draw Javascript to display out of stock message for out of
                                            stock attribute combinations
*/
  require_once(DIR_WS_CLASSES . 'pad_base.php');

  class pad_multiple_dropdowns extends pad_base {


/*
    Method: _draw_stocked_attributes
  
    draw dropdown lists for attributes that stock is tracked for

  
    Parameters:
  
      none
  
    Returns:
  
      string:         HTML to display dropdown lists for attributes that stock is tracked for

mc12345678 PROBLEM WITH THIS CLASS IS THAT THE ATTRIBUTES ARRAY CREATED AND POPULATED ENDS UP OVERWRITING ITSELF I THINK... AT LEAST IT APPEARS TO DO SOME ODD THINGS.  ESPECIALLY WITH THE LEFT JOIN OF THE ATTRIBUTES TABLE. THE SEQUENCED DROPDOWN DOES NOT APPEAR TO EXHIBIT THIS ISSUE... 

ALSO ($this->show_out_of_stock != 'True'))  LOOKS WRONG... THOUGHT IT SHOULD BE WITHOUT THE SINGLE QUOTE AROUND True. 

THIS ALSO DOESN'T WORK BECAUSE OF THE COMPARISON(S) BEING PERFORMED... BECAUSE THE STOCK BY ATTRIBUTES TABLE DOES NOT UNIQUELY IDENTIFY AN ATTRIBUTE TO A STOCK TRACKED ITEM, THE COMPARISONS IN THE SQL DO NOT COME OUT CORRECTLY... YET AGAIN A REASON WHY THE ATTRIBUTE INFORMATION SHOULD EXIST IN ITS OWN TABLE, THOUGH ALSO NOT SURE THAT THE ISSUE WOULD BE RESOLVED THERE.  REGARDLESS, THE SQL FOR THIS VERSION OF "MULTIPLE" ATTRIBUTES SHOULD BE MODIFIED TO PULL INFORMATION LIKE IT DOES IN THE SEQUENCED DROPDOWNS, SO THAT EACH FIELD CAN BE INDEPENDENTLY UPDATED INSTEAD OF HAVING TO WAIT FOR THE NEXT ATTRIBUTE TO BE DISPLAYED.  SEEMS RATHER SIMPLE IN A SENSE.
  
*/
   function _draw_stocked_attributes() {
      global $db;
      
      $out='';
      
	//The true/false below has to do with information in the 
	//	products_options table being tracked or not tracked.
      $attributes = $this->_build_attributes_array(true, false);
      if (sizeof($attributes)>0) {
        for($o=0; $o<sizeof($attributes); $o++) {
          $s=sizeof($attributes[$o]['ovals']);
          for ($a=0; $a<$s; $a++) {
               $attribute_stock_query_query = "SELECT quantity FROM " .  TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " AS a LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " AS b ON (b.options_id = :oid: AND b.options_values_id = :ovals:) WHERE a.products_id = :products_id: AND a.stock_attributes = b.products_attributes_id AND a.quantity > 0 order by b.products_options_sort_order";

               $attribute_stock_query_query = $db->bindVars($attribute_stock_query_query, ':oid:', $attributes[$o]['oid'], 'integer');
               $attribute_stock_query_query = $db->bindVars($attribute_stock_query_query, ':ovals:', $attributes[$o]['ovals'][$a]['id'], 'integer');
               $attribute_stock_query_query = $db->bindVars($attribute_stock_query_query, ':products_id:', $this->products_id, 'integer');

               $attribute_stock_query = $db->Execute($attribute_stock_query_query, false, false, 0, true);
      
            $out_of_stock=(($attribute_stock_query->RecordCount())==0);
     
            if ($out_of_stock && ($this->show_out_of_stock == 'True')) {
              switch ($this->mark_out_of_stock) {
                case 'Left':   $attributes[$o]['ovals'][$a]['text']=TEXT_OUT_OF_STOCK.' - '.$attributes[$o]['ovals'][$a]['text'];
                               break;
                case 'Right':  $attributes[$o]['ovals'][$a]['text'].=' - '.TEXT_OUT_OF_STOCK;
                               break;
              } // end switch
            } // end if
            elseif ($out_of_stock && ($this->show_out_of_stock != 'True')) {
              unset($attributes[$o]['ovals'][$a]);
            } // end elseif
          } // end for loop $a < $s
           $out.='<tr><td align="right" class="main"><b>'.$attributes[0]['oname'].":</b></td><td class=\"main\">".zen_draw_pull_down_menu('id['.$attributes[0]['oid'].']',array_merge(array(array('id'=>0, 'text'=>'Select '.$attributes[0]['oname'])), $attributes[0]['ovals']),$attributes[0]['default'], "onchange=\"i".$attributes[0]['oid']."(this.form);\"")."</td></tr>\n";
        } // end for $o       
        $out.=$this->_draw_out_of_stock_message_js($attributes);
        
        return $out;
      } // end if size attributes
    } // end function

/*
    Method: _draw_out_of_stock_message_js
  
    draw Javascript to display out of stock message for out of stock attribute combinations

  
    Parameters:
  
      $attributes     array   Array of attributes for the product.  Format is as returned by
                              _build_attributes_array.
  
    Returns:
  
      string:         Javascript to display out of stock message for out of stock attribute combinations
  
*/
    function _draw_out_of_stock_message_js($attributes) {
      $out='';
      
      $out.="<tr><td>&nbsp;</td><td><span id=\"oosmsg\" class=\"errorBox\"></span>\n";
  
      if (($this->out_of_stock_msgline == 'True' | $this->no_add_out_of_stock == 'True')) {
        $out.="<script type=\"text/javascript\" language=\"javascript\"><!--\n";
        $combinations = array();
        $selected_combination = 0;
        $this->_build_attributes_combinations($attributes, false, 'None', $combinations, $selected_combination);
        
        $out.="  function chkstk(frm) {\n";
      
        // build javascript array of in stock combinations
        $out.="    var stk=".$this->_draw_js_stock_array($combinations).";\n";
        $out.="    var instk=false;\n";
      
        // build javascript if statement to test level by level for existence  
        $out.='    ';
        for ($i=0; $i<sizeof($attributes); $i++) {
          $out.='if (stk';
          for ($j=0; $j<=$i; $j++) { //Isn't this missing a repeat of stk or something??? Otherwise looks like: if (stk[frm['id[5]'].value][frm['id[7]'].value]) instk=true; //when there are two oids of 5 and 7.
            $out.="[frm['id[".$attributes[$j]['oid']."]'].value]";
/*            if($j<($i-1)) {
              $out.=", stk";
            }*/
          }
          $out.=') ';
        }
        
        $out.="instk=true;\n";
        $out.="  return instk;\n";
        $out.="  }\n";

        if ($this->out_of_stock_msgline == 'True') {
          // set/reset out of stock message based on selection
          $out.="  function stkmsg(frm) {\n";
          $out.="    var instk=chkstk(frm);\n";
          $out.="    var span=document.getElementById(\"oosmsg\");\n";
          $out.="    while (span.childNodes[0])\n";
          $out.="      span.removeChild(span.childNodes[0]);\n";
          $out.="    if (!instk)\n";
          $out.="      span.appendChild(document.createTextNode(\"".TEXT_OUT_OF_STOCK_MESSAGE."\"));\n";
          $out.="    else\n";
          $out.="      span.appendChild(document.createTextNode(\" \"));\n";
          $out.="  }\n";
          //initialize out of stock message
          $out.="  stkmsg(document.cart_quantity);\n";
        }
      
        if ($this->no_add_out_of_stock == 'True') {
          // js to not allow add to cart if selection is out of stock
          $out.="  function chksel() {\n";
          $out.="    var instk=chkstk(document.cart_quantity);\n";
          $out.="    if (!instk) alert('".TEXT_OUT_OF_STOCK_MESSAGE."');\n";
          $out.="    return instk;\n";
          $out.="  }\n";
          $out.="  document.cart_quantity.onsubmit=function () {chksel()};\n";
        }
        $out.="//--></script>\n";
      }
      $out.="</td></tr>\n";
      
      return $out;
    }

  }
?>
