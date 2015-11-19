<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Make sure there's no other class with the same name loaded
if (!class_exists('RP_WCDPD_Discounts')) {

    /**
     * Cart Discounts calculation class
     * 
     * @class RP_WCDPD_Discounts
     * @package WooCommerce Dynamic Pricing And Discounts
     * @author RightPress
     */
    class RP_WCDPD_Discounts
    {

        /**
         * Class constructor
         * 
         * @access public
         * @param array $opt
         * @param object $pricing
         * @return void
         */
        public function __construct($opt, $pricing)
        {
            global $woocommerce;

            $this->cart = $woocommerce->cart;
            $this->items = $woocommerce->cart->cart_contents;
            $this->cart_subtotal = $this->calculate_cart_subtotal();

            $this->settings = $opt['settings'];
            $this->discount_settings = $opt['discounts'];
            $this->rules = $this->discount_settings['sets'];

            $this->pricing = $pricing;

            // This array holds calculated discounts to be applied per rule
            $this->apply = array();

        }

        /**
         * Calculate and return acumulated discount from all matched rules
         * 
         * @access public
         * @return array
         */
        public function get()
        {
            $discount = 0;
            $subtotal = $this->cart_subtotal;

            if (in_array($this->discount_settings['apply_multiple'], array('all', 'first'))) {

                foreach ($this->rules as $rule_key => $rule) {
                    if ($this->validate_rule($subtotal, $rule)) {

                        $current_discount = $this->calculate_discount($subtotal, array('type' => $rule['type'], 'value' => $rule['value']));

                        // Stop on first matched rule if configured to do so
                        if ($this->discount_settings['apply_multiple'] == 'first') {
                            $discount += $current_discount;
                            $subtotal = (($subtotal - $discount) >= 0) ? ($subtotal - $discount) : 0;
                            break;
                        }
                        else if ($current_discount > 0) {
                            $discount += $current_discount;
                            $subtotal = (($subtotal - $discount) >= 0) ? ($subtotal - $discount) : 0;
                        }
                    }
                }

            }
            else if ($this->discount_settings['apply_multiple'] == 'biggest') {

                $discounts = array();

                foreach ($this->rules as $rule_key => $rule) {
                    if ($this->validate_rule($subtotal, $rule)) {
                        $current_discount = $this->calculate_discount($subtotal, array('type' => $rule['type'], 'value' => $rule['value']));

                        if ($current_discount > 0) {
                            $discounts[$rule_key] = $current_discount;
                        }
                    }
                }

                if (!empty($discounts)) {
                    $discount = max($discounts);
                    $subtotal = (($subtotal - $discount) >= 0) ? ($subtotal - $discount) : 0;
                    $rule_key = array_search($discount, $discounts);
                }

            }

            if ($discount > 0) {
                return array(
                    'discount'  => $discount,
                    'log'       => array(),
                );
            }
            else {
                return false;
            }

        }

        /**
         * Validate, sanitize, check conditions and return single discount rule to be applied
         * 
         * @access public
         * @param float $subtotal
         * @param array $discount_rule
         * @return mixed
         */
        public function validate_rule($subtotal, $discount_rule)
        {
            // Valid from
            if (isset($discount_rule['valid_from']) && !empty($discount_rule['valid_from']) && (strtotime($discount_rule['valid_from'] . ' 00:00:00') > time())) {
                return false;
            }

            // Valid until
            if (isset($discount_rule['valid_until']) && !empty($discount_rule['valid_until']) && (strtotime($discount_rule['valid_until'] . ' 23:59:59') < time())) {
                return false;
            }

            // Only if pricing not adjusted
            if (isset($discount_rule['only_if_pricing_not_adjusted']) && $discount_rule['only_if_pricing_not_adjusted'] && !empty($this->pricing->applied)) {
                return false;
            }

            // CONDITIONS
            foreach ($discount_rule['conditions'] as $condition_key => $condition) {

                switch ($condition['key']) {

                    /**
                     * Total at least
                     */
                    case 'subtotal_bottom':

                        if ($subtotal < $condition['value']) {
                            return false;
                        }

                        break;

                    /**
                     * Total less than
                     */
                    case 'subtotal_top':

                        if ($subtotal >= $condition['value']) {
                            return false;
                        }

                        break;

                    /**
                     * At least one product in cart
                     */
                    case 'products':

                        if (!$this->product_in_cart($condition['products'])) {
                            return false;
                        }

                        break;

                    /**
                     * None of selected products in cart
                     */
                    case 'products_not':

                        if ($this->product_in_cart($condition['products'])) {
                            return false;
                        }

                        break;

                    /**
                     * At least one category in cart
                     */
                    case 'categories':

                        if (!$this->category_in_cart($condition['categories'])) {
                            return false;
                        }

                        break;

                    /**
                     * None of selected categories in cart
                     */
                    case 'categories_not':

                        if ($this->category_in_cart($condition['categories'])) {
                            return false;
                        }

                        break;

                    /**
                     * Count of cart items at least
                     */
                    case 'item_count_bottom':

                        if (count($this->items) < $condition['value']) {
                            return false;
                        }

                        break;

                    /**
                     * Count of cart items less than
                     */
                    case 'item_count_top':

                        if (count($this->items) >= $condition['value']) {
                            return false;
                        }

                        break;

                    /**
                     * Sum of item quantities at least
                     */
                    case 'quantity_bottom':

                        if ($this->total_cart_quantity() < $condition['value']) {
                            return false;
                        }

                        break;

                    /**
                     * Sum of item quantities less than
                     */
                    case 'quantity_top':

                        if ($this->total_cart_quantity() >= $condition['value']) {
                            return false;
                        }

                        break;

                    /**
                     * Specific users
                     */
                    case 'users':

                        if (get_current_user_id() == 0 || !in_array(get_current_user_id(), $condition['users'])) {
                            return false;
                        }

                        break;

                    /**
                     * Specific user roles
                     */
                    case 'roles':

                        if (get_current_user_id() == 0 || count(array_intersect(RP_WCDPD::current_user_roles(), $condition['roles'])) == 0) {
                            return false;
                        }

                        break;

                    /**
                     * Specific user capabilities
                     */
                    case 'capabilities':

                        if (get_current_user_id() == 0 || count(array_intersect(RP_WCDPD::current_user_capabilities(), $condition['capabilities'])) == 0) {
                            return false;
                        }

                        break;

                    /**
                     * Count of orders to date
                     */
                    case 'history_count':

                        if (get_current_user_id() == 0 || $this->customer_order_count(get_current_user_id()) < $condition['value']) {
                            return false;
                        }

                        break;

                    /**
                     * Total amount of orders to date
                     */
                    case 'history_amount':

                        if (get_current_user_id() == 0 || $this->customer_value(get_current_user_id()) < $condition['value']) {
                            return false;
                        }

                        break;

                    /**
                     * Shipping country
                     */
                    case 'shipping_countries':

                        if (get_current_user_id() == 0) {
                            return false;
                        }
                        else {
                            $user_meta = get_user_meta(get_current_user_id());

                            if (!$user_meta || !isset($user_meta['shipping_country']) || empty($user_meta['shipping_country']) || !in_array($user_meta['shipping_country'][0], $condition['shipping_countries'])) {
                                return false;
                            }
                        }

                        break;

                    default:
                        break;
                }

            }

            return true;
        }

        /**
         * Get cart total amount
         * 
         * @access public
         * @return float
         */
        public function calculate_cart_subtotal()
        {
            $cart_subtotal = 0;

            // Iterate over all cart items and 
            foreach ($this->items as $cart_item_key => $cart_item) {
                $quantity = (isset($cart_item['quantity']) && $cart_item['quantity']) ? $cart_item['quantity'] : 1;
                $cart_subtotal += $cart_item['data']->get_price() * $quantity;
            }

            return (float)$cart_subtotal;
        }

        /**
         * Calculate actual discount amount
         * 
         * @access public
         * @param float $subtotal
         * @param array $adjustment
         * @return float
         */
        public function calculate_discount($subtotal, $adjustment)
        {
            $subtotal = ($subtotal < 0) ? 0 : $subtotal;

            if ($adjustment['type'] == 'percentage') {
                $discount = $subtotal * ($adjustment['value'] / 100);
            }
            else if ($adjustment['type'] == 'price') {
                $discount = $adjustment['value'];
            }

            return ($discount < 0) ? 0 : $discount;
        }

        /**
         * Check if at least one product from list exists in cart
         * 
         * @access public
         * @param array $products
         * @return bool
         */
        public function product_in_cart($products)
        {
            foreach ($this->items as $cart_item) {
                if (in_array($cart_item['data']->id, $products)) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Check if at least one category from list exists in cart
         * 
         * @access public
         * @param array $categories
         * @return bool
         */
        public function category_in_cart($categories)
        {
            foreach ($this->items as $cart_item) {
                $current_categories = wp_get_post_terms($cart_item['data']->id, 'product_cat');

                foreach ($current_categories as $current_category) {
                    if (in_array($current_category->term_id, $categories)) {
                        return true;
                    }
                }
            }

            return false;
        }

        /**
         * Sum up quantities of all cart items
         * 
         * @access public
         * @return int
         */
        public function total_cart_quantity()
        {
            if (isset($this->total_cart_quantity_cache)) {
                return $this->total_cart_quantity_cache;
            }

            $total_quantity = 0;

            foreach ($this->items as $cart_item) {
                $current_quantity = (isset($cart_item['quantity']) && $cart_item['quantity']) ? $cart_item['quantity'] : 1;
                $total_quantity += $current_quantity;
            }

            $this->total_cart_quantity_cache = $total_quantity;

            return $this->total_cart_quantity_cache;
        }

        /**
         * Get number of completed customer orders
         * 
         * @access public
         * @param int $user_id
         * @return float
         */
        public function customer_order_count($user_id)
        {
            if (isset($this->customer_order_count_cache)) {
                return $this->customer_order_count_cache;
            }

            $args = array(
                'numberposts'   => -1,
                'meta_key'      => '_customer_user',
                'meta_value'    => $user_id,
                'post_type'     => 'shop_order',
                'post_status'   => 'publish',
                'tax_query' => array(
                    array(
                        'taxonomy'  => 'shop_order_status',
                        'field'     => 'slug',
                        'terms'     => 'completed',
                    ),
                ),
            );

            $posts = get_posts($args);

            $this->customer_order_count_cache = count($posts);

            return $this->customer_order_count_cache;
        }

        /**
         * Get customer value (sum of previous order amounts)
         * 
         * @access public
         * @param int $user_id
         * @return float
         */
        public function customer_value($user_id)
        {
            if (isset($this->customer_value_cache)) {
                return $this->customer_value_cache;
            }

            $value = 0;
            $orders = array();

            $args = array(
                'numberposts'   => -1,
                'meta_key'      => '_customer_user',
                'meta_value'    => $user_id,
                'post_type'     => 'shop_order',
                'post_status'   => 'publish',
                'tax_query' => array(
                    array(
                        'taxonomy'  => 'shop_order_status',
                        'field'     => 'slug',
                        'terms'     => 'completed',
                    ),
                ),
            );

            $posts = get_posts($args);
            $orders = wp_list_pluck($posts, 'ID');

            foreach ($orders as $order_id) {
                $order = new WC_Order($order_id);

                if ($order) {
                    $value += $order->get_total();
                }
            }

            $this->customer_value_cache = $value;

            return $this->customer_value_cache;
        }

    }
}
