<?php
/**
 * Class for managing the Shopping Cart via SBA
 *
 * @copyright Copyright 2003-2023 Zen Cart Development Team
 * @copyright Copyright 2023 mc12345678 of mc12345678.com
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: mc12345678 2023 Jan 17 Modified just for v1.5.8 because of protected members$
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

class sbaShoppingCart extends shoppingCart
{
   /**
    * Capture the variable of the shoppingCart class
    * @var shoppingCart
    */
    protected $cart;

    /**
     * overall content type of shopping cart
     * @var string
     */
//    protected $content_type;
    public function getContentType() {
        return $this->cart->content_type;
    }
    /**
     * number of free shipping items in cart
     * @var float|int
     */
//    protected $free_shipping_item;
    public function getFreeShippingItem() {
        return $this->cart->free_shipping_item;
    }
    /**
     * total weight of free shipping items in cart
     * @var float|int
     */
//    protected $free_shipping_weight;
    public function getFreeShippingWeight() {
        return $this->cart->free_shipping_weight;
    }
    /**
     * total price of free shipping items in cart
     * @var float|int
     */
//    protected $free_shipping_price;
    public function getFreeShippingPrice() {
        return $this->cart->free_shipping_price;
    }
    /**
     * total downloads in cart
     * @var float|int
     */
//    protected $download_count;
    public function getDownloadCount() {
        return $this->cart->download_count;
    }
    /**
     * shopping cart total price before Specials, Sales and Discounts
     * @var float|int
     */
//    protected $total_before_discounts;
    public function getTotalBeforeDiscounts() {
        return $this->cart->total_before_discounts;
    }
    /**
     * set to TRUE to see debug messages for developer use when troubleshooting add/update cart
     * Then, Logout/Login to reset cart for change
     * @var boolean
     */
//    protected $display_debug_messages = false;
    public function getDisplayDebugMessages() {
        return $this->cart->display_debug_messages;
    }
    public function setDisplayDebugMessages($setTrue = false) {
        $this->cart->display_debug_messages = $setTrue === true;
    }
//    protected $flag_duplicate_msgs_set = false;
    public function getFlagDuplicateMsgsSet() {
        return $this->cart->flag_duplicate_msgs_set;
    }
    public function setFlagDuplicateMsgsSet($setTrue = false) {
        $this->cart->flag_duplicate_msgs_set = $setTrue === true;
    }
    /**
     * array of flag to indicate if quantity ordered is outside product min/max order values
     * @var array
     */
//    protected $flag_duplicate_quantity_msgs_set = [];
    public function getFlagDuplicateQuantityMsgsSet() {
        return $this->cart->flag_duplicate_quantity_msgs_set;
    }
    public function addFlagDuplicateQuantityMsgsSet($newMsg) {
        if (empty($this->cart->flag_duplicate_quantity_msgs_set) || !is_array($this->cart->flag_duplicate_quantity_msgs_set)) {
            $this->cart->flag_duplicate_quantity_msgs_set = array();
        }

        $this->cart->flag_duplicate_quantity_msgs_set[] = $newMsg;
    }

    public function __construct(&$sC)
    {
        if (!is_a($sC, 'shoppingCart', false)) {
            return;
        }
        $this->cart = $sC;
    }
}
