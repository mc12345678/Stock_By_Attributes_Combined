<?php
/**
 * @package products_with_attributes_stock
 * @copyright Copyright 2003-2016 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * $Id: init_sba_copy_to_confirm.php xxxx 2016-11-14 20:31:10Z mc12345678 $
 */


if (defined('FILENAME_CATEGORIES') && $_SERVER['SCRIPT_NAME'] == DIR_WS_ADMIN . (!strstr(FILENAME_CATEGORIES, '.php') ? FILENAME_CATEGORIES . '.php' : FILENAME_CATEGORIES) && isset($_SESSION['sba_copy_to_confirm'])) {

  if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'english';
  }
  if (file_exists(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $template_dir_select . 'modules/product_sba.php')) {
    require_once(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $template_dir_select . 'modules/product_sba.php');
  } else {
    require_once(DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/product_sba.php');
  }

  // admin/attributes_controller: to_product

  if (isset($_SESSION['sba_copy_to_confirm'])) {
    foreach ($_SESSION['sba_copy_to_confirm'] as $key => $value) {
      if ($key != 'messageToStack') {
        ${$key} = $value;
      } else {
        if (isset($_SESSION[$key]) && is_array($_SESSION[$key])) {
          $_SESSION[$key] = array_merge($_SESSION[$key], $value);
        } else {
          $_SESSION[$key] = $value;
        }
      }
    }
    unset($key);
    unset($value);
    unset($_SESSION['sba_copy_to_confirm']);

    if ($copy_sba_attributes == 'copy_sba_attributes_yes' and $copy_as == 'duplicate') {
      $products_id_to = (int)$_GET['pID'];

      $products_with_attributes_stock_class->zen_copy_sba_products_attributes($products_id_from, $products_id_to);
    }
  } // EOF action is update_attributes "related"

  zen_redirect(zen_href_link(basename($PHP_SELF), zen_get_all_get_params(), 'SSL'));
//  zen_redirect($_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
} // EOF of the action for categories file.


if (defined('FILENAME_PRODUCT') && $_SERVER['SCRIPT_NAME'] == DIR_WS_ADMIN . (!strstr(FILENAME_PRODUCT, '.php') ? FILENAME_PRODUCT . '.php' : FILENAME_PRODUCT)) {

  // Problem with "removing" the $_POST in the following update_order, is that the order of product is modified.
  if (isset($_GET['action']) && $_GET['action'] == 'copy_to_confirm') {
    //$_SESSION['edit_u'] = $_POST;

// This one will collect the "from" here, but the "to" on the other end when dealing with the session data as $_GET['pID']

    if ($_SESSION['pwas_class2']->zen_product_is_sba($_POST['products_id'])) {
      if ($_GET['action'] == 'copy_to_confirm') {
        $_SESSION['sba_copy_to_confirm'] =
          array(
            'copy_attributes_delete_first' => ($_POST['copy_attributes'] == 'copy_attributes_delete' ? '1' : '0'),
            'copy_attributes_duplicates_skipped' => ($_POST['copy_attributes'] == 'copy_attributes_ignore' ? '1' : '0'),
            'copy_attributes_duplicates_overwrite' => ($_POST['copy_attributes'] == 'copy_attributes_update' ? '1' : '0'),
            'copy_sba_attributes' => $_POST['copy_sba_attributes']/*=='copy_sba_attributes_yes'*/,
            'copy_as' => $_POST['copy_as'],
//             and $_POST['copy_as'] == 'duplicate'
            'products_id_from' => $_POST['products_id'],
          );
      }
    } // EOF Product is SBA
  } // EOF action is update_attributes "related"
} // EOF of the action for categories file.

if (defined('FILENAME_CATEGORIES') && $_SERVER['SCRIPT_NAME'] == DIR_WS_ADMIN . (!strstr(FILENAME_CATEGORIES, '.php') ? FILENAME_CATEGORIES . '.php' : FILENAME_CATEGORIES) && (isset($_SESSION['sba_update_attributes_copy_to_category']) || isset($_SESSION['sba_update_attributes_copy_to_product']))) {

  if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'english';
  }
  if (file_exists(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $template_dir_select . 'modules/product_sba.php')) {
    require_once(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $template_dir_select . 'modules/product_sba.php');
  } else {
    require_once(DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/product_sba.php');
  }

  // admin/attributes_controller: to_product

  if (isset($_SESSION['sba_update_attributes_copy_to_category'])) {
    foreach ($_SESSION['sba_update_attributes_copy_to_category'] as $key => $value) {
      if ($key != 'messageToStack') {
        ${$key} = $value;
      } else {
        if (isset($_SESSION[$key]) && is_array($_SESSION[$key])) {
          $_SESSION[$key] = array_merge($_SESSION[$key], $value);
        } else {
          $_SESSION[$key] = $value;
        }
      }
    }
    unset($key);
    unset($value);
    unset($_SESSION['sba_update_attributes_copy_to_category']);

    $copy_to_category = $db->Execute("select products_id from " . TABLE_PRODUCTS_TO_CATEGORIES . " where categories_id='" . (int)$categories_update_id. "'");
    while (!$copy_to_category->EOF) {
      $products_with_attributes_stock_class->zen_copy_sba_products_attributes($products_id, $copy_to_category->fields['products_id']);

      $copy_to_category->MoveNext();
    }
  } else {
    foreach ($_SESSION['sba_update_attributes_copy_to_product'] as $key => $value) {
      if ($key != 'messageToStack') {
        ${$key} = $value;
      } else {
        if (isset($_SESSION[$key]) && is_array($_SESSION[$key])) {
          $_SESSION[$key] = array_merge($_SESSION[$key], $value);
        } else {
          $_SESSION[$key] = $value;
        }
      }
    }
    unset($key);
    unset($value);
    unset($_SESSION['sba_update_attributes_copy_to_product']);

    $products_with_attributes_stock_class->zen_copy_sba_products_attributes($products_id, $products_update_id);
  } // EOF action is update_attributes "related"

  zen_redirect(zen_href_link(basename($PHP_SELF), zen_get_all_get_params(), 'SSL'));
//  zen_redirect($_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
} // EOF of the action for categories file.

if (defined('FILENAME_CATEGORIES') && $_SERVER['SCRIPT_NAME'] == DIR_WS_ADMIN . (!strstr(FILENAME_CATEGORIES, '.php') ? FILENAME_CATEGORIES . '.php' : FILENAME_CATEGORIES)) {

  // Problem with "removing" the $_POST in the following update_order, is that the order of product is modified.
  if (isset($_GET['action']) && ($_GET['action'] == 'update_attributes_copy_to_product' || $_GET['action'] == 'update_attributes_copy_to_category')) {
    //$_SESSION['edit_u'] = $_POST;

    if ($_SESSION['pwas_class2']->zen_product_is_sba($_POST['products_id'])) {
      if ($_GET['action'] == 'update_attributes_copy_to_category') {
        $_SESSION['sba_update_attributes_copy_to_category'] =
          array(
            'copy_attributes_delete_first' => ($_POST['copy_attributes'] == 'copy_attributes_delete' ? '1' : '0'),
            'copy_attributes_duplicates_skipped' => ($_POST['copy_attributes'] == 'copy_attributes_ignore' ? '1' : '0'),
            'copy_attributes_duplicates_overwrite' => ($_POST['copy_attributes'] == 'copy_attributes_update' ? '1' : '0'),
            'categories_update_id' => (int)$_POST['categories_update_id'],
            'products_id' => $_POST['products_id'],
          );
      } else {
        $_SESSION['sba_update_attributes_copy_to_product'] =
          array(
            'copy_attributes_delete_first' => ($_POST['copy_attributes'] == 'copy_attributes_delete' ? '1' : '0'),
            'copy_attributes_duplicates_skipped' => ($_POST['copy_attributes'] == 'copy_attributes_ignore' ? '1' : '0'),
            'copy_attributes_duplicates_overwrite' => ($_POST['copy_attributes'] == 'copy_attributes_update' ? '1' : '0'),
            'products_id' => $_POST['products_id'],
            'products_update_id' => $_POST['products_update_id'],
          );
      }
    } // EOF Product is SBA
  } // EOF action is update_attributes "related"
} // EOF of the action for categories file.


if (defined('FILENAME_ATTRIBUTES_CONTROLLER') && $_SERVER['SCRIPT_NAME'] == DIR_WS_ADMIN . (!strstr(FILENAME_ATTRIBUTES_CONTROLLER, '.php') ? FILENAME_ATTRIBUTES_CONTROLLER . '.php' : FILENAME_ATTRIBUTES_CONTROLLER) && (isset($_SESSION['sba_update_attributes_copy_to_product']) || isset($_SESSION['sba_update_attributes_copy_to_category']))) {

  if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'english';
  }
  if (file_exists(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $template_dir_select . 'modules/product_sba.php')) {
    require_once(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $template_dir_select . 'modules/product_sba.php');
  } else {
    require_once(DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/product_sba.php');
  }

  // admin/attributes_controller: to_product

  if (isset($_SESSION['sba_update_attributes_copy_to_category'])) {
    foreach ($_SESSION['sba_update_attributes_copy_to_category'] as $key => $value) {
      if ($key != 'messageToStack') {
        ${$key} = $value;
      } else {
        if (isset($_SESSION[$key]) && is_array($_SESSION[$key])) {
          $_SESSION[$key] = array_merge($_SESSION[$key], $value);
        } else {
          $_SESSION[$key] = $value;
        }
      }
    }
    unset($key);
    unset($value);
    unset($_SESSION['sba_update_attributes_copy_to_category']);

    $copy_to_category = $db->Execute("select products_id from " . TABLE_PRODUCTS_TO_CATEGORIES . " where categories_id='" . (int)$categories_update_id. "'");
    while (!$copy_to_category->EOF) {
      $products_with_attributes_stock_class->zen_copy_sba_products_attributes($products_filter, $copy_to_category->fields['products_id']);

      $copy_to_category->MoveNext();
    }
  } else {
    foreach ($_SESSION['sba_update_attributes_copy_to_product'] as $key => $value) {
      if ($key != 'messageToStack') {
        ${$key} = $value;
      } else {
        if (isset($_SESSION[$key]) && is_array($_SESSION[$key])) {
          $_SESSION[$key] = array_merge($_SESSION[$key], $value);
        } else {
          $_SESSION[$key] = $value;
        }
      }
    }
    unset($key);
    unset($value);
    unset($_SESSION['sba_update_attributes_copy_to_product']);

    $products_with_attributes_stock_class->zen_copy_sba_products_attributes($products_filter, $products_update_id);
  }

  // $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'])
  zen_redirect(zen_href_link(basename($PHP_SELF), zen_get_all_get_params(), 'SSL'));
} // EOF of the action for product file.

// Set the session variable so that when redirected back to the same code that previous action has been captured
// and this code can continue processing without having to modify the base code to accomplish the task.
if (defined('FILENAME_ATTRIBUTES_CONTROLLER') && $_SERVER['SCRIPT_NAME'] == DIR_WS_ADMIN . (!strstr(FILENAME_ATTRIBUTES_CONTROLLER, '.php') ? FILENAME_ATTRIBUTES_CONTROLLER . '.php' : FILENAME_ATTRIBUTES_CONTROLLER)) {

  // Problem with "removing" the $_POST in the following update_order, is that the order of product is modified.
  if (isset($_GET['action']) && ($_GET['action'] == 'update_attributes_copy_to_product' || $_GET['action'] == 'update_attributes_copy_to_category')) {
    //$_SESSION['edit_u'] = $_POST;

    if ($_SESSION['pwas_class2']->zen_product_is_sba($_POST['products_filter'])) {
      if ($_GET['action'] == 'update_attributes_copy_to_category') {
        $_SESSION['sba_update_attributes_copy_to_category'] =
          array(
            'copy_attributes_delete_first' => ($_POST['copy_attributes'] == 'copy_attributes_delete' ? '1' : '0'),
            'copy_attributes_duplicates_skipped' => ($_POST['copy_attributes'] == 'copy_attributes_ignore' ? '1' : '0'),
            'copy_attributes_duplicates_overwrite' => ($_POST['copy_attributes'] == 'copy_attributes_update' ? '1' : '0'),
            'categories_update_id' => (int)$_POST['categories_update_id'],
            'products_filter' => $_POST['products_filter'],
          );

      } else {
        $_SESSION['sba_update_attributes_copy_to_product'] =
          array(
            'copy_attributes_delete_first' => ($_POST['copy_attributes'] == 'copy_attributes_delete' ? '1' : '0'),
            'copy_attributes_duplicates_skipped' => ($_POST['copy_attributes'] == 'copy_attributes_ignore' ? '1' : '0'),
            'copy_attributes_duplicates_overwrite' => ($_POST['copy_attributes'] == 'copy_attributes_update' ? '1' : '0'),
            'products_filter' => $_POST['products_filter'],
            'products_update_id' => $_POST['products_update_id'],
          );
      }
    } // EOF Product is SBA
  } // EOF action is update_attributes "related"*/
} // EOF of the action for product file.


