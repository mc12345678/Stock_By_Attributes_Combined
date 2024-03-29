<?php
/**
 * @package admin
 * @copyright Copyright 2003-2014 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: products_with_attributes_stock.php  $
 * 
 * Stock by Attributes 1.5.4 mc12345678 15-08-16
 */
 
define('HEADING_TITLE', 'Stock By Attributes');

define('PWA_DELETE_VARIANT_CONFIRMATION', 'Etes-vous s&ucirc;r que vous voulez supprimer cette variante de produit?');
define('PWA_DELETE_VARIANTS_CONFIRMATION', 'Are you sure you want to delete each variant of this product?');

define('PWA_PRODUCT_ID', 'Identit&eacute; du Produit');
define('PWA_PRODUCT_NAME', 'Nom du Produit');
define('PWA_PRODUCT_IMAGE', 'Image');
define('PWA_PRODUCT_MODEL', 'Mod&egrave;le du Produit');
define('PWA_QUANTITY', 'Quantit&eacute;: ');
define('PWA_QUANTITY_FOR_ALL_VARIANTS', 'Quantit&eacute; pour toutes les Variantes');
define('PWA_ADD_QUANTITY', 'Ajouter Quantit&eacute; pour cette Variante du Produit');
define('PWA_SYNC_QUANTITY', 'Synchronisation de la Quantiti&eacute;');
define('PWA_TEXT_SEARCH', 'Search: ');
if (!defined('TEXT_IMAGE_NONEXISTENT')) {
  define('TEXT_IMAGE_NONEXISTENT', 'Aucune image disponible');
}

define('PWA_EDIT_QUANTITY', 'Editer la Quantit&eacute;');
define('PWA_DELETE_VARIANT', 'Effacer la Variante');
define('PWA_DELETE_VARIANT_ALL', 'Delete All Variants');
define('PWA_DELETED_VARIANT', 'Product Variant was deleted');
define('PWA_DELETED_VARIANT_ALL', 'All %1$d Product Variants were deleted');

define('PWA_STOCK_ID', 'Identit&eacute; du Stock');
define('PWA_VARIANT', 'Variante');
define('PWA_QUANTITY_IN_STOCK', 'Quantit&eacute; en Stock');

define('PWA_SORT_ORDER', 'Sort Order');
define('PWA_CUSTOM_ID', 'Custom ID');
define('PWA_CUSTOMID_NAME', ' Item # ');
define('PWA_SKU_TITLE', 'Description');
define('PWA_PAC', 'Unique Combo');

define('PWA_EDIT', 'Editer');
define('PWA_DELETE', 'Supprimer');
define('PWA_SUBMIT','Soumettre');

define('PWA_QUANTITY_MISSING', 'Missing Quantity!');
define('PWA_PRODUCTS_ID_BAD', 'Missing or bad products_id!');
define('PWA_QUANTITY_BAD', 'Missing or bad Quantity!');
define('PWA_ATTRIBUTE_MISSING', 'Missing Attribute Selection!');
define('PWA_MIX_ERROR_ALL_COMBO', 'Do NOT mix \'All - Attributes\' and \'All - Attributes - Combo\'');
define('PWA_NO_CHANGES', 'No changes made.');
define('PWA_UPDATE_SUCCESS', 'Product successfully updated');
define('PWA_UPDATE_FAILURE', 'Product %1$d update failed: %2$s');
define('PWA_PARENT_QUANTITY_UPDATE_SUCCESS', 'Parent Product Quantity Updated');
define('PWA_PARENT_QUANTITIES_UPDATE_SUCCESS', 'Parent Product Quantities Updated');
define('PWA_SORT_UPDATE_SUCCESS', '%1$d stock attributes updated for sort by primary attribute sort order');
define('PWA_ADJUST_QUANTITY_SUCCESS', 'Product %1$d with stock_id %2$d found on search \'%3$s\' has been updated by %4$f to a value of %5$f.');
define('PWA_ADJUST_QUANTITY_NONE_FOUND', 'No product were found using search content of \'%1$s\' to adjust quantity by %2$f.');
define('PWA_ADJUST_QUANTITY_MULTIPLE_NOT_SUPPORTED_YET', 'Multiple product were found using the search criteria \'%1$s\' to try to adjust by %2$f; however, this is not yet supported. Try a search that provides a single variant.');
define('PWA_BUTTON_SEARCH', 'Search');
define('PWA_BUTTON_ADJUST', 'Adjust');
define('PWA_EXCESS_PRODUCT', 'PRODUCT TRACKED AS Stock With Attributes BUT DO NOT CURRENTLY HAVE ATTRIBUTES');

