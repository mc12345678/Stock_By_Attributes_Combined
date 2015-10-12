<?php
/**
 * @package admin
 * @copyright Copyright 2003-2014 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: products_with_attributes_stock.php  $
 *
 * Updated for Stock by Attributes SBA 1.5.4 mc12345678 15-08-17
 */

$SBAversion = 'Version 1.5.4';
//add required referenced files
require('includes/application_top.php');
require(DIR_WS_CLASSES . 'currencies.php');
require(DIR_WS_CLASSES . 'products_with_attributes_stock.php');

//new object from class
$stock = new products_with_attributes_stock;

//set language
if (isset($_SESSION['languages_id'])) {
  $language_id = $_SESSION['languages_id'];
} else {

  $languages = zen_get_languages();
  $languages_array = array();
  $languages_selected = DEFAULT_LANGUAGE;
  for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
    $languages_array[] = array('id' => $languages[$i]['code'],
      'text' => $languages[$i]['name']);
    if ($languages[$i]['directory'] == $_SESSION['language']) {
      $languages_selected = $languages[$i]['code'];
    }
  }
  $language_id = $languages_selected;
}

//action
if (isset($_GET['action']) && $_GET['action']) {
  $action = addslashes(trim($_GET['action']));
} else {
  $action = null;
}

//case selection 'add', 'edit', 'confirm', 'execute', 'delete_all', 'delete', 'resync', 'resync_all', 'auto_sort'
switch ($action) {
  case 'add':
    if (isset($_GET['products_id']) and is_numeric((int) $_GET['products_id'])) {
      $products_id = (int) $_GET['products_id'];
    }
    if (isset($_POST['products_id']) and is_numeric((int) $_POST['products_id'])) {
      $products_id = (int) $_POST['products_id'];
    }

    if (isset($products_id)) {

      if (zen_products_id_valid($products_id)) {

        $product_name = zen_get_products_name($products_id);
        $product_attributes = $stock->get_products_attributes($products_id, $language_id);

        $hidden_form .= zen_draw_hidden_field('products_id', $products_id) . "\n";
      } else {
        zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, zen_get_all_get_params(array('action')), $request_type));
      }
    } else {

      $query = 'SELECT DISTINCT
                        pa.products_id, d.products_name
                      FROM ' . TABLE_PRODUCTS_ATTRIBUTES . ' pa
                          left join ' . TABLE_PRODUCTS_DESCRIPTION . ' d on (pa.products_id = d.products_id)
                      WHERE d.language_id=' . $language_id . ' 
                      order by d.products_name';

      $products = $db->execute($query);

      while (!$products->EOF) {
        $products_array_list[] = array(
          'id' => $products->fields['products_id'],
          'text' => $products->fields['products_name']
        );
        $products->MoveNext();
      }
    }
    break;

  case 'edit':
    $hidden_form = '';
    if (isset($_GET['products_id']) and is_numeric((int) $_GET['products_id'])) {
      $products_id = $_GET['products_id'];
    }

    if (isset($_GET['attributes'])) {
      $attributes = $_GET['attributes'];
    }

    if (isset($products_id) and isset($attributes)) {
      $attributes = explode(',', $attributes);
      foreach ($attributes as $attribute_id) {
        $hidden_form .= zen_draw_hidden_field('attributes[]', $attribute_id) . "\n";
        $attributes_list[] = $stock->get_attributes_name($attribute_id, $language_id);
      }
      $hidden_form .= zen_draw_hidden_field('products_id', $products_id) . "\n";
    } else {
      zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, zen_get_all_get_params(array('action')), $request_type));
    }
    break;

  case 'confirm':
    if (isset($_POST['products_id']) && is_numeric((int) $_POST['products_id'])) {

      if (!isset($_POST['quantity']) || !is_numeric($_POST['quantity'])) {
        $messageStack->add_session("Missing Quantity!", 'failure');
        zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'updateReturnedPID=' . $_POST['products_id'], $request_type));
      }

      $products_id = $_POST['products_id'];
      $product_name = zen_get_products_name($products_id);

      $customid = trim($_POST['customid']);
      $skuTitle = trim($_POST['skuTitle']);

      if (is_numeric($_POST['quantity'])) {
        $quantity = $db->getBindVarValue($_POST['quantity'], 'float');
      }

      $attributes = $_POST['attributes'];

      foreach ($attributes as $attribute_id) {
        $hidden_form .= zen_draw_hidden_field('attributes[]', $attribute_id) . "\n";
        $attributes_list[] = $stock->get_attributes_name($attribute_id, $_SESSION['languages_id']);
      }
      $hidden_form .= zen_draw_hidden_field('products_id', $products_id) . "\n";
      $hidden_form .= zen_draw_hidden_field('quantity', $quantity) . "\n";
      //These are used in the GET thus it must match the same name used in the $_GET[''] calls
      $s_mack_noconfirm .= "products_id=" . $products_id . "&amp;"; //s_mack:noconfirm
      $s_mack_noconfirm .= "quantity=" . $quantity . "&amp;"; //s_mack:noconfirm
      $s_mack_noconfirm .= "customid=" . $customid . "&amp;"; //s_mack:noconfirm
      $s_mack_noconfirm .= "skuTitle=" . $skuTitle . "&amp;"; //s_mack:noconfirm

      sort($attributes);
      $stock_attributes = implode(',', $attributes);

      $s_mack_noconfirm .='attributes=' . $stock_attributes . '&amp;'; //kuroi: to pass string not array

      $query = 'select * 
            from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' 
            where products_id = ' . $products_id . ' 
            and stock_attributes = "' . $stock_attributes . '"';
      $stock_check = $db->Execute($query);

      if (!$stock_check->EOF) {
        $hidden_form .= zen_draw_hidden_field('add_edit', 'edit');
        $hidden_form .= zen_draw_hidden_field('stock_id', $stock_check->fields['stock_id']);
        $s_mack_noconfirm .="stock_id=" . $stock_check->fields['stock_id'] . "&amp;"; //s_mack:noconfirm
        $s_mack_noconfirm .="add_edit=edit&amp;"; //s_mack:noconfirm
        $add_edit = 'edit';
      } else {
        $hidden_form .= zen_draw_hidden_field('add_edit', 'add') . "\n";
        $s_mack_noconfirm .="add_edit=add&amp;"; //s_mack:noconfirm
      }
    } else {
      zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, zen_get_all_get_params(array('action')), $request_type));
    }
    zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, $s_mack_noconfirm . "action=execute", $request_type)); //s_mack:noconfirm
    break;

  case 'execute':

    $attributes = $_POST['attributes'];
    if ($_GET['attributes']) {
      $attributes = $_GET['attributes'];
    } //s_mack:noconfirm

    $products_id = doubleval($_POST['products_id']);
    if ($_GET['products_id']) {
      $products_id = doubleval($_GET['products_id']);
    } //s_mack:noconfirm

    $customid = addslashes(trim($_POST['customid']));
    if (isset($_GET['customid']) && $_GET['customid']) {
      $customid = addslashes(trim($_GET['customid']));
    } //s_mack:noconfirm

    $skuTitle = addslashes(trim($_POST['skuTitle']));
    if ($_GET['skuTitle']) {
      $skuTitle = addslashes(trim($_GET['skuTitle']));
    }

    //$quantity = $_GET['quantity']; //s_mack:noconfirm
    if (isset($_GET['quantity']) && $_GET['quantity']) {
      $quantity = doubleval($_GET['quantity']);
    } //s_mack:noconfirm
    //if invalid entry return to product
    if (!is_numeric((int) $products_id) || is_null($products_id)) {
      $messageStack->add_session("Missing or bad products_id!", 'failure');
      zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'updateReturnedPID=' . $products_id, $request_type));
    } elseif (!is_numeric((int) $quantity) || is_null($quantity) && $quantity != 0) {
      $messageStack->add_session("Missing or bad Quantity!", 'failure');
      zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'updateReturnedPID=' . $products_id, $request_type));
    } elseif (is_null($attributes) || str_replace(',', null, $attributes) == null) {
      $messageStack->add_session("Missing Attribute Selection!", 'failure');
      zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'updateReturnedPID=' . $products_id, $request_type));
    }

    /*
      Updated by PotteryHouse
      originally by michael mcinally <mcinallym@picassofish.com>
      Allow inserting "ALL" attributes at once
     */
    if (($_POST['add_edit'] == 'add') || ($_GET['add_edit'] == 'add')) { //s_mack:noconfirm
      $attributes = ltrim($attributes, ','); //remove extra comma seperators

      if (preg_match("/\|/", $attributes) && preg_match("/\;/", $attributes)) {
        $saveResult = null;
        $messageStack->add_session("Do NOT mix 'All - Attributes' and 'All - Attributes - Combo'", 'failure');
      } elseif (preg_match("/\|/", $attributes)) {
        //explode array on ,
        $arrTemp = preg_split("/\,/", $attributes);
        $arrMain = array();
        $intCount = 0;

        for ($i = 0; $i < sizeof($arrTemp); $i++) {
          //explode array on |
          $arrTemp1 = preg_split("/\|/", $arrTemp[$i]);
          $arrMain[] = $arrTemp1;

          if ($intCount) {
            $intCount = $intCount * sizeof($arrTemp1);
          } else {
            $intCount = sizeof($arrTemp1);
          }
        }
        $intVars = sizeof($arrMain);
        $arrNew = array();

        if ($intVars >= 1) {
          $a = 0;
          while ($a <= $intVars) {
            //adds each attribute (no combinations)
            for ($i = 0; $i < sizeof($arrMain[$a]); $i++) {
              $arrNew[] = array($arrMain[$a][$i]);
            }
            $a++;
          }

          //loop through the list of variables / attributes
          //add each one to the database
          for ($i = 0; $i < sizeof($arrNew); $i++) {
            //used to add multi attribute combinations at one time
            $strAttributes = implode(",", $arrNew[$i]);
            $productAttributeCombo = $products_id . '-' . str_replace(',', '-', $strAttributes);
            $saveResult = $stock->insertNewAttribQty($products_id, $productAttributeCombo, $strAttributes, $quantity); //can not include the $customid since it must be unique
          }
        }
      } elseif (preg_match("/\;/", $attributes)) {
        //explode array on ,
        $arrTemp = preg_split("/\,/", $attributes);
        $arrMain = array();
        $intCount = 0;

        for ($i = 0; $i < sizeof($arrTemp); $i++) {
          //explode array on ;
          $arrTemp1 = preg_split("/\;/", $arrTemp[$i]);
          $arrMain[] = $arrTemp1;

          if ($intCount) {
            $intCount = $intCount * sizeof($arrTemp1);
          } else {
            $intCount = sizeof($arrTemp1);
          }
        }
        $intVars = sizeof($arrMain);
        $arrNew = array();

        $arrNew = return_attribute_combinations($arrMain, $intVars);

        /*
          if ($intVars >= 1) {
          //adds attribute combinations
          // there are X variables / attributes
          // so, you need that many arrays
          // then, you have to loop through EACH ONE
          // if it is the LAST variable / attribute
          // you need to add that variable / attribute VALUE
          // and ALL PREVIOUS VALUES to the multi-dimensional array
          // below supports up to 5 variables / attributes
          // to add more, just copy and paste into the last for loop and go up from $n is the last one
          for ($i = 0;$i < sizeof($arrMain[0]);$i++) {
          if ($intVars >= 2) {
          for ($j = 0;$j < sizeof($arrMain[1]);$j++) {
          if ($intVars >= 3) {
          for ($k = 0;$k < sizeof($arrMain[2]);$k++) {
          if ($intVars >= 4) {
          for ($l = 0;$l < sizeof($arrMain[3]);$l++) {
          if ($intVars >= 5) {
          for ($m = 0;$m < sizeof($arrMain[4]);$m++) {
          if ($intVars >= 6) {
          for ($n = 0;$n < sizeof($arrMain[5]);$n++) {
          if ($intVars >= 7){
          for ($o = 0; $o < sizeof($arrMain[6]); $o++) {
          $arrNew[] = array($arrMain[0][$i], $arrMain[1][$j], $arrMain[2][$k], $arrMain[3][$l], $arrMain[4][$m], $arrMain[5][$n], $arrMain[6][$o]);
          }
          } else {
          $arrNew[] = array($arrMain[0][$i], $arrMain[1][$j], $arrMain[2][$k], $arrMain[3][$l], $arrMain[4][$m], $arrMain[5][$n]);
          }
          }
          } else {
          $arrNew[] = array($arrMain[0][$i], $arrMain[1][$j], $arrMain[2][$k], $arrMain[3][$l], $arrMain[4][$m]);
          }
          }
          } else {
          $arrNew[] = array($arrMain[0][$i], $arrMain[1][$j], $arrMain[2][$k], $arrMain[3][$l]);
          }
          }
          } else {
          $arrNew[] = array($arrMain[0][$i], $arrMain[1][$j], $arrMain[2][$k]);
          }
          }
          } else {
          $arrNew[] = array($arrMain[0][$i], $arrMain[1][$j]);
          }
          }
          } else {
          $arrNew[] = array($arrMain[0][$i]);
          }
          }

          } */

        //loop through the list of variables / attributes
        //add each one to the database
        for ($i = 0; $i < sizeof($arrNew); $i++) {
          //used to add multi attribute combinations at one time
          sort($arrNew[$i]); // Ensures that values are in order prior to imploding
          $strAttributes = implode(",", $arrNew[$i]);
          $productAttributeCombo = $products_id . '-' . str_replace(',', '-', $strAttributes);
          $saveResult = $stock->insertNewAttribQty($products_id, $productAttributeCombo, $strAttributes, $quantity); //can not include the $customid since it must be unique
        }
      } else {
        //used for adding one attribute or atribute combination at a time
        $strAttributes = ltrim($attributes, ","); //remove extra , if present
        $strAttributes = rtrim($strAttributes, ","); //remove extra , if present
        $productAttributeCombo = $products_id . '-' . str_replace(',', '-', $strAttributes);
        $saveResult = $stock->insertNewAttribQty($products_id, $productAttributeCombo, $strAttributes, $quantity, $customid, $skuTitle);
      }
    } elseif (($_POST['add_edit'] == 'edit') || ($_GET['add_edit'] == 'edit')) { //s_mack:noconfirm
      $stock_id = $_POST['stock_id']; //s_mack:noconfirm
      if ($_GET['stock_id']) {
        $stock_id = $_GET['stock_id'];
      } //s_mack:noconfirm
      if (!is_numeric((int) $stock_id)) { //s_mack:noconfirm
        zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, zen_get_all_get_params(array('action')), $request_type));
      }
      //update existing records
      $saveResult = $stock->updateAttribQty($stock_id, $quantity);
      //Only updates custom id if a value is provided, will not set to null
      if (!empty($customid)) {
        $saveResult = $stock->updateCustomIDAttrib($stock_id, $customid);
      }
      //Only updates sku title if a value is provided, will not set to null
      if (!empty($skuTitle)) {
        $saveResult = $stock->updateTitleAttrib($stock_id, $skuTitle);
      }
    }

    if ($saveResult == 1) {
      //Use the button 'Sync Quantities' when needed, or uncomment the line below if you want it done automatically.
      //$stock->update_parent_products_stock($products_id);//keep this line as option, but I think this should not be done automatically.
      $messageStack->add_session("Product successfully updated", 'success');
    } else {
      $messageStack->add_session("Product $products_id update failed: $saveResult", 'failure');
    }

    zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'updateReturnedPID=' . $products_id, $request_type));

    break;

  case 'delete_all':
    if (isset($_POST['confirm'])) {
      // delete item
      if ($_POST['confirm'] == TEXT_YES) {
        $query = 'delete from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id= :products_id:';
        $query = $db->bindVars($query, ':products_id:', $_POST['products_id'], 'integer');
        $db->Execute($query);
        $query_result = $db->Execute("SELECT ROW_COUNT() as rows;");
        //Use the button 'Sync Quantities' when needed, or uncomment the line below if you want it done automatically.
        //$stock->update_parent_products_stock((int)$_POST['products_id']);//keep this line as option, but I think this should not be done automatically.
        $messageStack->add_session(($query_result->fields['rows'] > 1 ? sprintf(PWA_DELETED_VARIANT_ALL, $query_result->fields['rows']) : PWA_DELETED_VARIANT), 'failure');
        zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'updateReturnedPID=' . $_POST['products_id'], $request_type));
      } else {
        zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'updateReturnedPID=' . $_POST['products_id'], $request_type));
      }
    }
    break;

  case 'delete':
    if (isset($_POST['confirm'])) {
      // delete item
      if ($_POST['confirm'] == TEXT_YES) {
        $query = 'delete from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id="' . $_POST['products_id'] . '" and stock_attributes="' . $_POST['attributes'] . '" limit 1';
        $db->Execute($query);
        //Use the button 'Sync Quantities' when needed, or uncomment the line below if you want it done automatically.
        //$stock->update_parent_products_stock((int)$_POST['products_id']);//keep this line as option, but I think this should not be done automatically.
        $messageStack->add_session(PWA_DELETED_VARIANT, 'failure');
        zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'updateReturnedPID=' . $_POST['products_id'], $request_type));
      } else {
        zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'updateReturnedPID=' . $_POST['products_id'], $request_type));
      }
    }
    break;

  case 'resync':
    if (is_numeric((int) $_GET['products_id'])) {

      $stock->update_parent_products_stock((int) $_GET['products_id']);
      $messageStack->add_session('Parent Product Quantity Updated', 'success');
      zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'updateReturnedPID=' . $_GET['products_id'], $request_type));
    } else {
      zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, '', $request_type));
    }
    break;

  case 'resync_all':
    $stock->update_all_parent_products_stock();
    $messageStack->add_session('Parent Product Quantities Updated', 'success');
    zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, '', $request_type));
    break;

  case 'auto_sort':
    // get all attributes
    $sql = $db->Execute("SELECT stock_id, stock_attributes FROM " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " ORDER BY stock_id ASC;");
    $count = $sql->RecordCount(); // mc12345678 why not use $sql->RecordCount()? If doesn't return correct value, then above SQL needs to be called to include a cache "reset".
    $array_sorted_array = array();
    $skip_update = false;
    while (!$sql->EOF) {
      // get the attributes for sort to get the sort order
      $sort_query = "SELECT DISTINCT pa.products_attributes_id, pov.products_options_values_sort_order as sort
             FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
             LEFT JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov on (pov.products_options_values_id = pa.options_values_id)
			 LEFT JOIN " . TABLE_PRODUCTS_OPTIONS . " po on (po.products_options_id = pa.options_id) 
             WHERE pa.products_attributes_id in (" . $sql->fields['stock_attributes'] . ")
             ORDER BY po.products_options_sort_order ASC;"; // pov.products_options_values_sort_order ASC";
      $sort = $db->Execute($sort_query);
      if ($sort->RecordCount() > 1) {
        $skip_update = true;
        $array_temp_sorted_array = array();
        while (!$sort->EOF) {
          $array_temp_sorted_array[$sort->fields['products_attributes_id']] = $sort->fields['sort'];
          $sort->MoveNext();
        }
        $array_sorted_array[$sort->RecordCount()][] = array('stock_id' => $sql->fields['stock_id'], 'sort_order' => $array_temp_sorted_array);
      } else {
        $sort_val = $sort->fields['sort'];
        // update sort in db
        $db->Execute("UPDATE " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " set sort = '" . $sort_val . "' WHERE stock_id = '" . $sql->fields['stock_id'] . "' LIMIT 1;");
      }
      $sql->MoveNext();
    }
    if ($skip_update === true) {
      ksort($array_sorted_array); // Sort the array by size of sub-arrays.
      foreach ($array_sorted_array as &$part_array) {
        $t = array();
        $name = array();
        foreach ($part_array as &$sorter) {
          $num_elem = 0;
          foreach ($sorter['sort_order'] as $key => $val) {
            $t[$num_elem][] = $val;
            $num_elem++;
          }
          $name[] = $sorter['stock_id'];
        }
		unset($sorter);

        $param = array();
		for ($i=0; isset($t[$i]); $i++) {
		  $param[] = &$t[$i];
		  $param[] = SORT_ASC;
		  $param[] = SORT_NUMERIC;
		}
		if(isset($param) && sizeof($param) > 0) {
	      $param[] = &$name;
          call_user_func_array('array_multisort', $param);
		}
        //array_multisort($t[0],$t[1],..$t[n],$name); // Need to figure out how to get these sub-arrays populated.
        // Do update to table using $sort_order variable, increment $sort_order after each update, keep on moving...
        $icount = 0;
        foreach ($name as $value) {
          $db->Execute("UPDATE " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " set sort = '" . $icount * 10 . "' WHERE stock_id = '" . $value . "' LIMIT 1;");
          $icount++;
        }
		unset($value);
      }
      unset($part_array);
    }
    $messageStack->add_session($count . ' stock attributes updated for sort by primary attribute sort order', 'success');
    zen_redirect(zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, '', $request_type));
    break;

  default:
    // Show a list of the products
    break;
}

global $template_dir;
?>
<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
    <title><?php echo TITLE; ?></title>
    <link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
    <link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
    <link rel="stylesheet" type="text/css" href="includes/products_with_attributes_stock_ajax.css">
<?php if (file_exists(DIR_FS_CATALOG_TEMPLATES . 'template_default/jscript/jquery.min.js')) { ?>
      <script type="text/javascript" src="<?php echo ($page_type == 'NONSSL' ? HTTP_CATALOG_SERVER . DIR_WS_CATALOG : ( ENABLE_SSL_ADMIN == 'true' ? HTTPS_CATALOG_SERVER . DIR_WS_HTTPS_CATALOG : HTTP_CATALOG_SERVER . DIR_WS_CATALOG ) ) . DIR_WS_TEMPLATES . 'template_default'; ?>/jscript/jquery.min.js"></script>
<?php } else { ?>
      <script type="text/javascript" src="<?php echo ($page_type == 'NONSSL' ? HTTP_CATALOG_SERVER . DIR_WS_CATALOG : ( ENABLE_SSL_ADMIN == 'true' ? HTTPS_CATALOG_SERVER . DIR_WS_HTTPS_CATALOG : HTTP_CATALOG_SERVER . DIR_WS_CATALOG ) ) . DIR_WS_TEMPLATES . $template_dir; ?>/jscript/jquery-1.10.2.min.js"></script>
<?php } ?>
    <script type="text/javascript" src="<?php echo ($page_type == 'NONSSL' ? HTTP_CATALOG_SERVER . DIR_WS_CATALOG : ( ENABLE_SSL_ADMIN == 'true' ? HTTPS_CATALOG_SERVER . DIR_WS_HTTPS_CATALOG : HTTP_CATALOG_SERVER . DIR_WS_CATALOG ) ) . DIR_WS_TEMPLATES . $template_dir; ?>/jscript/jquery.form.js"></script>
    <script type="text/javascript" src="products_with_attributes_stock_ajax.js"></script>
    <script type="text/javascript" src="includes/menu.js"></script>
    <script type="text/javascript" src="includes/general.js"></script>
    <script type="text/javascript">
	 <!--
    function init()
	 {
		 cssjsmenu('navbar');
		 if (document.getElementById)
		 {
			 var kill = document.getElementById('hoverJS');
			 kill.disabled = true;
		 }
	 }
	 // -->
    </script>
  </head>
  <body onLoad="init()">
    <!-- header //-->
<?php
require(DIR_WS_INCLUDES . 'header.php');
?>
    <!-- header_eof //-->
    <div style="padding: 20px;">

      <!-- body_text_eof //-->

    <?php
//case selection 'add', 'edit', 'delete_all', 'delete',  'confirm'
    switch ($action) {
      case 'add':
        if (isset($products_id)) {

          echo zen_draw_form('sba_post_form', FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'action=confirm', 'post', '', true) . "\n";
          echo $hidden_form;
          echo '<p><strong>' . $product_name . '</strong></p>' . "\n";

          foreach ($product_attributes as $option_name => $options) {

            //get the option/attribute list
            $sql = "select distinct po.products_options_type, po.products_options_name, pot.products_options_types_name, 
                     pa.attributes_display_only, pa.products_attributes_id
            from " . TABLE_PRODUCTS_OPTIONS . " po
              left join " . TABLE_PRODUCTS_ATTRIBUTES . " pa ON (pa.options_id = po.products_options_id)
              left join " . TABLE_PRODUCTS_OPTIONS_TYPES . " pot ON (po.products_options_type = pot.products_options_types_id)
            where pa.products_id = $products_id
              and po.products_options_name = '" . $option_name . "'     
              and po.language_id = $language_id;";
            $products_options_type = $db->Execute($sql);

            // MULTI
            $arrValues = array();
            if (is_array($options)) {
              if (sizeof($options) > 0) {
                foreach ($options as $k => $a) {
                  $arrValues[] = $a['id'];
                }
              }
            }

            array_unshift($options, array('id' => implode(";", $arrValues), 'text' => 'All - Attributes - Combo'));
            array_unshift($options, array('id' => implode("|", $arrValues), 'text' => 'All - Attributes'));
            array_unshift($options, array('id' => null, 'text' => 'N/A'));
            echo '<p><strong>' . $option_name . ': </strong>';
            echo zen_draw_pull_down_menu('attributes[]', $options) . '</p>' . "\n";
          }

          echo '<p>If using "<strong>All - Attributes - Combo</strong>" there must be TWO (or more) attribute groups selected (i.e., Color and Size).';
          echo '<hr>';

          echo 'If <strong>"ALL"</strong> is selected, the ' . PWA_SKU_TITLE . ' will not be saved.<br />' . PWA_SKU_TITLE . ' should be unique for each attribute and combination.<br />
                  <strong>' . PWA_SKU_TITLE . ':</strong> ' . zen_draw_input_field('skuTitle') . '</p>' . "\n";
          echo '<hr>';

          echo 'The ' . PWA_CUSTOM_ID . ' will not be saved if <strong>"ALL"</strong> is selected.<br />' . PWA_CUSTOM_ID . ' must be unique for each attribute / combination.<br />
                  <strong>' . PWA_CUSTOM_ID . ':</strong> ' . zen_draw_input_field('customid') . '</p>' . "\n";
          echo '<hr>';

          if (count($product_attributes) > 1) {
            $msg = 'Only add the attributes used to control ' . PWA_QUANTITY . '.<br />Leave the other attribute groups as N/A.<br />';
          }
          echo $msg . '<p><strong>' . PWA_QUANTITY . '</strong>' . zen_draw_input_field('quantity') . '</p>' . "\n";
        } else {

          echo zen_draw_form('sba_post_form', FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'action=add', 'post', '', true) . "\n";
          echo zen_draw_pull_down_menu('products_id', $products_array_list) . "\n";
        }
        ?>
          <p><input type="submit" value="<?php echo PWA_SUBMIT ?>"></p>
        </form>
          <?php
          break;

        case 'edit':
          echo zen_draw_form('sba_post_form', FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'action=confirm', 'post', '', true) . "\n";
          echo '<h3>' . zen_get_products_name($products_id) . '</h3>';

          foreach ($attributes_list as $attributes) {
            echo '<p><strong>' . $attributes['option'] . ': </strong>' . $attributes['value'] . '</p>';
          }

          echo $hidden_form;
          echo '<p><strong>Quantity: </strong>' . zen_draw_input_field('quantity', $_GET['q']) . '</p>' . "\n"; //s_mack:prefill_quantity
          ?>
        <p><input type="submit" value="<?php echo PWA_SUBMIT ?>"></p>
      </form>
          <?php
          break;

        case 'delete_all':
          if (!isset($_POST['confirm'])) {

            echo zen_draw_form('sba_post_form', FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'action=delete_all', 'post', '', true) . "\n";
            echo PWA_DELETE_VARIANTS_CONFIRMATION;
            foreach ($_GET as $key => $value) {
              echo zen_draw_hidden_field($key, $value);
            }
            ?>
        <p><input type="submit" value="<?php echo TEXT_YES ?>" name="confirm"> * <input type="submit" value="<?php echo TEXT_NO ?>" name="confirm"></p>
      </form>
      <?php
    }
    break;

          case 'delete':
          if (!isset($_POST['confirm'])) {

            echo zen_draw_form('sba_post_form', FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'action=delete', 'post', '', true) . "\n";
            echo PWA_DELETE_VARIANT_CONFIRMATION;
            foreach ($_GET as $key => $value) {
              echo zen_draw_hidden_field($key, $value);
            }
            ?>
        <p><input type="submit" value="<?php echo TEXT_YES ?>" name="confirm"> * <input type="submit" value="<?php echo TEXT_NO ?>" name="confirm"></p>
      </form>
      <?php
    }
    break;

  case 'confirm':
    echo '<h3>Confirm ' . $product_name . '</h3>';

    foreach ($attributes_list as $attributes) {
      echo '<p><strong>' . $attributes['option'] . ': </strong>' . $attributes['value'] . '</p>';
    }

    echo '<p><strong>Quantity</strong>' . $quantity . '</p>';
    echo zen_draw_form('sba_post_form', FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'action=execute', 'post', '', true) . "\n";
    echo $hidden_form;
    ?>
    <p><input type="submit" value="<?php echo PWA_SUBMIT ?>"></p>
    </form>
    <?php
    break;

  default:
    //return to page (previous edit) data
    echo '<h4>Stock By Attribute (SBA) Stock Page ' . $SBAversion . '</h4>';
    echo '<h4><a title="Shortcut to the Stock By Attributtes setup page" href="' . zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK_SETUP, '', $request_type) . '">SBA Setup Link</a></h4>';

    $seachPID = null;
    $seachBox = null;
    if (isset($_GET['updateReturnedPID']) || isset($_POST['updateReturnedPID'])) {
      $seachPID = doubleval(trim($_GET['updateReturnedPID']));
      $seachBox = doubleval(trim($_GET['updateReturnedPID']));
    } elseif (isset($_GET['search']) || isset($_POST['search'])) {
      $seachBox = (trim($_GET['search']));
      if (is_numeric($seachBox)) {
        $seachPID = doubleval(trim($_GET['search']));
      }
    } elseif (isset($_GET['seachPID'])) {
      $seachPID = doubleval(trim($_GET['seachPID']));
      $seachBox = doubleval(trim($_GET['seachPID']));
    }

    //search box displayed only option
    $SBAsearchbox = null; //initialize
    $searchList = null;
    if (STOCK_SET_SBA_SEARCHBOX == 'true') {
      $SBAsearchbox = "Search Box Only";
    }
    //elseif( STOCK_SET_SBA_NUMRECORDS > 0 && !isset($_GET['search']) ){
    //future functionality option (needs work)
    //limit number of records displayed on page at one time and allow user to select the record range
    //$SBAsearchbox = "Records Displayed: ". STOCK_SET_SBA_NUMRECORDS;
    //}

    if (STOCK_SBA_SEARCHLIST == 'true') {
      //Product Selection Listing at top of page
      $searchList = 'select distinct pa.products_id, d.products_name,
                 p.products_model
                   FROM ' . TABLE_PRODUCTS_ATTRIBUTES . ' pa
                  left join ' . TABLE_PRODUCTS_DESCRIPTION . ' d on (pa.products_id = d.products_id)
                  left join ' . TABLE_PRODUCTS . ' p on (pa.products_id = p.products_id)
                WHERE d.language_id = ' . $language_id . '
                order by products_model'; //order by may be changed to: products_id, products_model, products_name

      echo '<form method="get" action="' . zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, '', $request_type) . '" name="pwas-search">Product Selection List:';
      echo $searchList = $stock->selectItemID(TABLE_PRODUCTS_ATTRIBUTES, 'pa.products_id', $seachPID, $searchList, 'seachPID', 'seachPID', 'selectSBAlist');
      echo '<input type="submit" value="Search" name="pwas-search-button"/></form>';
    }

    echo '<div id="hugo1" style="background-color: green; padding: 2px 10px;"></div>';
    echo '<form method="get" action="' . zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, '', $request_type) . '" id="pwas-search" name="pwas-search">Search:  <input id="pwas-filter" type="text" name="search" value="' . $seachBox . '" /><input type="submit" value="Search" id="pwas-search-button" name="pwas-search-button"/></form><span style="margin-right:10px;">&nbsp;</span>';
    echo '<a href="' . zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, '', $request_type) . '">Reset</a><span style="margin-right:10px;">&nbsp;</span><a title="Sets sort value for all attributes to match value in the Option Values Manager" href="' . zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, 'action=auto_sort', $request_type) . '">Sort</a>';
    echo '<span style="margin-right:20px;color:red;">&nbsp;&nbsp;&nbsp;&nbsp;' . $SBAsearchbox . '</span>'; //set a option in configuration table
    echo '<span id="loading" style="display: none;"><img src="./images/loading.gif" alt="" /> Loading...</span><hr />';
    echo '<a class="forward" style="float:right;" href="' . zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, "action=resync_all", $request_type) . '"><strong>Sync All Quantities</strong></a><br class="clearBoth" /><hr />';
    echo '<div id="pwa-table">';
    echo $stock->displayFilteredRows(STOCK_SET_SBA_SEARCHBOX, null, $seachPID);
    echo '</div>';
    break;
}
?>
</div>
<!-- body_eof //-->
<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br />

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
