<?php
/**
 * init_sba_copy_to_confirm_messagestack - Needed to sustain the ZC default messageStack information when adding additional messageStack data.
 *
 * @package Stock By Attributes
 * @copyright Copyright 2003-2017 Zen Cart Development Team
 * @copyright Copyright 2017 mc12345678
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Author: mc12345678 Tue Apr 10 18:26:42 2016 +0000 Modified in ZC v1.5.5e $
 */

  // The goal of this code is to capture the SESSION data associated with the messageToStack ($messageStack) so that when the page redirect occurs,
  //  the "dangling" messageStack information is not lost to the user.  This captures the existing SESSION data 5 steps (Load Point 95) before the 
  //  loading of the messageStack which occurs at load point 100. Location chosen close to the point of pushing the data, but after the SESSIONS were
  //  re-initiated.  As written, there appears to be a possibility that an additional message could be sent to the stack that after these load points
  //  that wouldn't get captured.  Looking into this possibility/how best to overcome with little waste.
  // Ultimately what this does is prevent any edits to applicable files so that this plugin can be as much of a true plugin as possible and not an edit.
if (defined('FILENAME_ATTRIBUTES_CONTROLLER') && $_SERVER['SCRIPT_NAME'] == DIR_WS_ADMIN . (!strstr(FILENAME_ATTRIBUTES_CONTROLLER, '.php') ? FILENAME_ATTRIBUTES_CONTROLLER . '.php' : FILENAME_ATTRIBUTES_CONTROLLER) && (isset($_SESSION['sba_update_attributes_copy_to_product']) || isset($_SESSION['sba_update_attributes_copy_to_category']))) {
  if (isset($_SESSION['sba_update_attributes_copy_to_category'])) {
    $_SESSION['sba_update_attributes_copy_to_category'] = array_merge(
            $_SESSION['sba_update_attributes_copy_to_category'], 
            array('messageToStack' => $_SESSION['messageToStack'])
          );
  } else {
    $_SESSION['sba_update_attributes_copy_to_product'] = array_merge(
              $_SESSION['sba_update_attributes_copy_to_product'], 
              array('messageToStack' => $_SESSION['messageToStack'])
            );
  }
}

if (defined('FILENAME_CATEGORIES') && $_SERVER['SCRIPT_NAME'] == DIR_WS_ADMIN . (!strstr(FILENAME_CATEGORIES, '.php') ? FILENAME_CATEGORIES . '.php' : FILENAME_CATEGORIES) && (isset($_SESSION['sba_update_attributes_copy_to_product']) || isset($_SESSION['sba_update_attributes_copy_to_category']) || isset($_SESSION['sba_copy_to_confirm']))) {
  if (isset($_SESSION['sba_update_attributes_copy_to_category'])) {
    $_SESSION['sba_update_attributes_copy_to_category'] = array_merge(
            $_SESSION['sba_update_attributes_copy_to_category'], 
            array('messageToStack' => $_SESSION['messageToStack'])
          );
  } elseif (isset($_SESSION['sba_copy_to_confirm'])) {
    $_SESSION['sba_copy_to_confirm'] = array_merge(
            $_SESSION['sba_copy_to_confirm'], 
            array('messageToStack' => $_SESSION['messageToStack'])
          );
  } else {
    $_SESSION['sba_update_attributes_copy_to_product'] = array_merge(
              $_SESSION['sba_update_attributes_copy_to_product'], 
              array('messageToStack' => $_SESSION['messageToStack'])
            );
  }
}

if (defined('FILENAME_PRODUCT') && $_SERVER['SCRIPT_NAME'] == DIR_WS_ADMIN . (!strstr(FILENAME_PRODUCT, '.php') ? FILENAME_PRODUCT . '.php' : FILENAME_PRODUCT) && isset($_SESSION['sba_copy_to_confirm'])) {
  if (isset($_SESSION['sba_copy_to_confirm'])) {
    $_SESSION['sba_copy_to_confirm'] = array_merge(
            $_SESSION['sba_copy_to_confirm'], 
            array('messageToStack' => $_SESSION['messageToStack'])
          );
  }
}