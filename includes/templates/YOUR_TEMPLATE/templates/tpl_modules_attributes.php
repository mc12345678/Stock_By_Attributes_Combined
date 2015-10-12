<?php
/**
 * Module Template
 *
 * Template used to render attribute display/input fields
 *
 * @package templateSystem
 * @copyright Copyright 2003-2005 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: tpl_modules_attributes.php 3208 2006-03-19 16:48:57Z birdbrain $
 * Modified to support products with attributes that are not tracked by SBA.
 * 
 * Stock by Attributes 1.5.4 : mc12345678 15-08-17
 */
?>
<div id="productAttributes">
     <?php
    if ($is_SBA_product /*$stock->_isSBA*/ && defined('TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK') && defined('PRODINFO_ATTRIBUTE_DYNAMIC_STATUS') && PRODINFO_ATTRIBUTE_DYNAMIC_STATUS != '0') {
      if (!defined('SBA_ZC_DEFAULT')) {
        define('SBA_ZC_DEFAULT','true'); // sets to use the ZC method of HTML tags around attributes.
      }
      $inSBA_query = "select products_id from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " where products_id = :products_id:";
      $inSBA_query = $db->bindVars($inSBA_query, ':products_id:', $_GET['products_id'], 'integer');

      $inSBA = $db->Execute($inSBA_query); // Determine that product is tracked by SBA
     } else { 
       $inSBA = new queryFactoryResult;
       $inSBA->EOF = true;
     }

     if ($zv_display_select_option > 0) {
       $products_attributes_query = "select count(distinct products_options_id) as total from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib where patrib.products_id=:products_id: and patrib.options_id = popt.products_options_id and popt.language_id = :languages_id:";
       $products_attributes_query = $db->bindVars($products_attributes_query, ':products_id:', $_GET['products_id'], 'integer');
       $products_attributes_query = $db->bindVars($products_attributes_query, ':languages_id:', $_SESSION['languages_id'], 'integer');
       $products_attributes = $db->Execute($products_attributes_query);
       if ((((defined('PRODINFO_ATTRIBUTE_PLUGIN_MULTI') && ($products_attributes->fields['total'] > 1) && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2')) ? file_exists(DIR_WS_CLASSES . 'pad_' . PRODINFO_ATTRIBUTE_PLUGIN_MULTI . '.php') : ((defined('PRODINFO_ATTRIBUTE_PLUGIN_SINGLE') && ($products_attributes->fields['total'] == 1) && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '3')) ? file_exists(DIR_WS_CLASSES . 'pad_' . PRODINFO_ATTRIBUTE_PLUGIN_SINGLE . '.php') : false )) && /*class_exists('pad_base') && */defined('TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK') && !$inSBA->EOF)) {
         //$products_attributes = $db->Execute("select count(distinct products_options_id) as total from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib where patrib.products_id='" . (int) $_GET['products_id'] . "' and patrib.options_id = popt.products_options_id and popt.language_id = " . (int) $_SESSION['languages_id'] . "");


         if ($products_attributes->fields['total'] > 1) {

           $products_id = (preg_match("/^\d{1,10}(\{\d{1,10}\}\d{1,10})*$/", $_GET['products_id']) ? $_GET['products_id'] : (int) $_GET['products_id']);
           require(DIR_WS_CLASSES . 'pad_' . PRODINFO_ATTRIBUTE_PLUGIN_MULTI . '.php');
           $class = 'pad_' . PRODINFO_ATTRIBUTE_PLUGIN_MULTI;

           $pad = new $class($products_id);

           echo $pad->draw();
         } /* END SBA Multi */ elseif ($products_attributes->fields['total'] > 0) {
           $products_id = (preg_match("/^\d{1,10}(\{\d{1,10}\}\d{1,10})*$/", $_GET['products_id']) ? $_GET['products_id'] : (int) $_GET['products_id']);
           require(DIR_WS_CLASSES . 'pad_' . PRODINFO_ATTRIBUTE_PLUGIN_SINGLE . '.php');
           $class = 'pad_' . PRODINFO_ATTRIBUTE_PLUGIN_SINGLE;
           $pad = new $class($products_id);
           echo $pad->draw();
         } //End SBA SINGLE
       } //END SBA Specific
       else {
         $inSBA = new queryFactoryResult;
         $inSBA->EOF = true;
         ?>
         <h3 id="attribsOptionsText"><?php echo TEXT_PRODUCT_OPTIONS; ?>         </h3>
<?php } // END NON-SBA SPECIFIC: show please select unless all are readonly ?>

<?php
     } // End display info 
     ?>
     <?php
     if ($inSBA->EOF && $inSBA->RecordCount() < 1) {
       for ($I = 0; $I < sizeof($options_name); $I++) {
         ?>
         <?php
         if ($options_comment[$I] != '' and $options_comment_position[$I] == '0') {
           ?>
           <h3 class="attributesComments"><?php echo $options_comment[$I]; ?></h3>
                <?php
         } // END h3_option_comment
         ?>

         <div class="wrapperAttribsOptions">
              <h4 class="optionName back"><?php echo $options_name[$I]; ?></h4>
              <div class="back"><?php echo "\n" . $options_menu[$I]; ?></div>
              <br class="clearBoth" />
         </div>


         <?php
         if ($options_comment[$I] != '' and
                 $options_comment_position[$I] == '1') {
           ?>
           <div class="ProductInfoComments"><?php echo $options_comment[$I]; ?></div>
<?php } // END if Div_options_Comment    
?>
         <?php
       } // End FOR options_name 
       ?>
       <?php
       // This displays ALL images regardless of attribute stock levels. Comment-out the "echo" if you want to skip images.
       for ($i = 0; $i < sizeof($options_name); $i++) {
         if ($options_attributes_image[$i] != '') {
           ?>
           <?php echo $options_attributes_image[$i]; ?>
           <?php
         } // End If(attributes images)
       } // End For Options_name
       ?>
       <br class="clearBoth" />
       <?php
     }
     ?>


     <?php
     if ($show_onetime_charges_description == 'true') {
       ?>
       <div class="wrapperAttribsOneTime"><?php echo TEXT_ONETIME_CHARGE_SYMBOL . TEXT_ONETIME_CHARGE_DESCRIPTION; ?></div>
     <?php } ?>


     <?php
     if ($show_attributes_qty_prices_description == 'true') {
       ?>
       <div class="wrapperAttribsQtyPrices"><?php echo zen_image(DIR_WS_TEMPLATE_ICONS . 'icon_status_green.gif', TEXT_ATTRIBUTES_QTY_PRICE_HELP_LINK, 10, 10) . '&nbsp;' . '<a href="javascript:popupWindowPrice(\'' . zen_href_link(FILENAME_POPUP_ATTRIBUTES_QTY_PRICES, 'products_id=' . $_GET['products_id'] . '&products_tax_class_id=' . $products_tax_class_id) . '\')">' . TEXT_ATTRIBUTES_QTY_PRICE_HELP_LINK . '</a>'; ?></div>
     <?php } ?>
</div>