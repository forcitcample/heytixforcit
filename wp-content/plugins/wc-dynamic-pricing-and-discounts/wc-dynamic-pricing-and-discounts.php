<?php

/**
 * Plugin Name: WooCommerce Dynamic Pricing & Discounts
 * Plugin URI: http://www.rightpress.net/woocommerce-dynamic-pricing-and-discounts
 * Description: Control your WooCommerce product pricing and cart discounts.
 * Version: 1.0.18
 * Author: RightPress
 * Author URI: http://www.rightpress.net
 * Requires at least: 3.5
 * Tested up to: 3.9
 *
 * Text Domain: rp_wcdpd
 * Domain Path: /languages
 *
 * @package WooCommerce Dynamic Pricing And Discounts
 * @category Core
 * @author RightPress
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define Constants
define('RP_WCDPD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('RP_WCDPD_PLUGIN_URL', plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__)));
define('RP_WCDPD_VERSION', '1.0.18');
define('RP_WCDPD_OPTIONS_VERSION', '1');

if (!class_exists('RP_WCDPD')) {

    /**
     * Main plugin class
     *
     * @package WooCommerce Dynamic Pricing Pro
     * @author RightPress
     */
    class RP_WCDPD
    {
        private static $instance = false;
        public $discounts_applied = false;

        /**
         * Singleton control
         */
        public static function get_instance()
        {
            if (!self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Class constructor
         *
         * @access public
         * @return void
         */
        public function __construct()
        {
            // Load translation
            load_plugin_textdomain('rp_wcdpd', false, dirname(plugin_basename(__FILE__)) . '/languages/');

            // Activation hook
            register_activation_hook(__FILE__, array($this, 'activate'));

            // Initialize plugin configuration
            $this->plugin_config_init();

            // Load plugin settings, pricing and discount setup
            $this->opt = $this->get_options();

            // Set up settings page
            if (is_admin() && !defined('DOING_AJAX')) {

                // Additional Plugins page links
                add_filter('plugin_action_links_'.plugin_basename(__FILE__), array($this, 'plugins_page_links'));

                // Add settings page menu link
                add_action('admin_menu', array($this, 'add_admin_menu'));
                add_action('admin_init', array($this, 'plugin_options_setup'));

                // Load scripts/styles conditionally
                if (preg_match('/page=wc_pricing_and_discounts/i', $_SERVER['QUERY_STRING'])) {
                    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts_and_styles'));
                }
            }

            // Load frontend scripts/styles and hook into WooCommerce
            else {

                // Enqueue scripts and styles
                add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts_and_styles'));

                // Apply discounts - Add To Cart
                if ((!empty($_REQUEST['add-to-cart']) && is_numeric($_REQUEST['add-to-cart'])) || (isset($_REQUEST['action']) && $_REQUEST['action'] == 'woocommerce_add_to_cart')) {
                    add_action('woocommerce_add_to_cart', array($this, 'apply_discounts'), 19);
                }
                // Apply discounts - Cart Update
                else if (!empty($_POST['apply_coupon']) || !empty($_POST['update_cart']) || !empty($_POST['proceed'])) {
                    add_action('woocommerce_before_cart_item_quantity_zero', array($this, 'apply_discounts'), 100);
                    add_action('woocommerce_after_cart_item_quantity_update', array($this, 'apply_discounts'), 100);
                    // The following line takes care of other cart updates (e.g. apply coupon), however, this is only available from WC 2.2
                    add_action('woocommerce_update_cart_action_cart_updated', array($this, 'apply_discounts'));
                }
                // Apply discounts - All other cases
                else {
                    add_action('woocommerce_cart_loaded_from_session', array($this, 'apply_discounts'), 100);
                }

                // Remove discounts after they are no longer valid
                add_action('woocommerce_check_cart_items', array($this, 'remove_cart_discount'), 1);

                // Change prices on product pages (cosmetic change)
                add_filter('woocommerce_price_html', array($this, 'replace_visible_prices'), 10, 2);
                add_filter('woocommerce_variable_price_html', array($this, 'replace_visible_prices'), 10, 2);
                add_filter('woocommerce_grouped_price_html', array($this, 'replace_visible_prices'), 10, 2);
                add_filter('woocommerce_sale_price_html', array($this, 'replace_visible_prices'), 10, 2);
                add_filter('woocommerce_empty_price_html', array($this, 'replace_visible_prices'), 10, 2);
                add_filter('woocommerce_variation_price_html', array($this, 'replace_visible_prices'), 10, 2);
                add_filter('woocommerce_variation_sale_price_html', array($this, 'replace_visible_prices'), 10, 2);

                // Display pricing table or anchor to open pricing table as modal
                if (isset($this->opt['settings']['display_position']) && !empty($this->opt['settings']['display_position'])) {
                    add_action($this->opt['settings']['display_position'], array($this, 'product_page_pricing_table'));
                }

            }

            // Some hooks need to be attached after init is triggered
            add_action('init', array($this, 'on_init'));
        }

        /**
         * Function hooked to init
         *
         * @access public
         * @return void
         */
        public function on_init()
        {
            if (is_admin() && !defined('DOING_AJAX')) {



            }
            else {

                // Change prices in cart (cosmetic change)
                $cart_price_hook = $this->wc_version_gte('2.1') ? 'woocommerce_cart_item_price' : 'woocommerce_cart_item_price_html';
                add_filter($cart_price_hook, array($this, 'replace_visible_prices_cart'), 100, 3);

            }
        }

        /**
         * Replace original prices with discounted product prices in output html
         * This only makes sense when a basic quantity pricing table is used (with no further conditions)
         *
         * @access public
         * @param string $product_price
         * @param object $product
         * @return string
         */
        public function replace_visible_prices($product_price, $product)
        {
            return $product_price;
        }

        /**
         * Replace original prices with discounted item prices in cart html
         *
         * @access public
         * @param string $item_price
         * @param array $cart_item
         * @param string $cart_item_key
         * @return string
         */
        public function replace_visible_prices_cart($item_price, $cart_item, $cart_item_key)
        {
            if (!isset($cart_item['rp_wcdpd'])) {
                return $item_price;
            }

            // Get price to display
            $price = get_option('woocommerce_tax_display_cart') == 'excl' ? $cart_item['data']->get_price_excluding_tax() : $cart_item['data']->get_price_including_tax();

            // Format price to display
            $price_to_display = $this->wc_version_gte('2.1') ? wc_price($price) : woocommerce_price($price);
            $original_price_to_display = $this->wc_version_gte('2.1') ? wc_price($cart_item['rp_wcdpd']['original_price']) : woocommerce_price($cart_item['rp_wcdpd']['original_price']);

            $item_price = '<span class="rp_wcdpd_cart_price"><del>' . $original_price_to_display . '</del> <ins>' . $price_to_display . '</ins></span>';

            return $item_price;
        }

        /**
         * Apply discounts to cart
         *
         * @access public
         * @return void
         */
        public function apply_discounts()
        {
            global $woocommerce;

            // Already applied and not Ajax request?
            if (($this->discounts_applied && current_filter() != 'woocommerce_ajax_added_to_cart') || empty($woocommerce->cart->cart_contents)) {
                return;
            }

            // Load required classes
            require_once RP_WCDPD_PLUGIN_PATH . 'includes/classes/Pricing.php';
            require_once RP_WCDPD_PLUGIN_PATH . 'includes/classes/Discounts.php';

            // Sort cart by price ascending
            $cart_contents = $this->sort_cart_by_price($woocommerce->cart->cart_contents, 'asc');

            // Process item pricing rules
            $this->pricing = new RP_WCDPD_Pricing($cart_contents, $this->opt);

            foreach ($cart_contents as $cart_item_key => $cart_item) {
                if ($adjustment = $this->pricing->get($cart_item_key)) {
                    $this->apply_pricing_adjustment($cart_item_key, $adjustment);
                }
                else if (isset($woocommerce->cart->cart_contents[$cart_item_key]['rp_wcdpd'])) {
                    unset($woocommerce->cart->cart_contents[$cart_item_key]['rp_wcdpd']);
                }
            }

            // Process cart discount rules
            $this->discounts = new RP_WCDPD_Discounts($this->opt, $this->pricing);

            // Apply cart discounts (if any)
            if ($this->cart_discount_to_apply = $this->discounts->get()) {
                add_filter('woocommerce_get_shop_coupon_data', array($this, 'maybe_add_virtual_coupon'), 10, 2);
                add_action('woocommerce_after_calculate_totals', array($this, 'apply_fake_coupon'));
            }

            // Recalculate totals for mini cart (since totals are only updated when loading cart from session which runs before adding a new product to cart)
            if (current_filter() == 'woocommerce_ajax_added_to_cart' || defined('DOING_AJAX')) {
                $woocommerce->cart->calculate_totals();
            }

            $this->discounts_applied = true;
        }

        /**
         * Remove no longer valid cart discounts from cart
         *
         * @access public
         * @return void
         */
        public function remove_cart_discount()
        {
            global $woocommerce;

            // Iterate over applied coupons and check each of them
            foreach ($woocommerce->cart->applied_coupons as $code) {

                // Check if coupon code matches our fake coupon code
                if ($this->get_fake_coupon_code() === $code) {

                    // Get coupon
                    $coupon = new WC_Coupon($code);

                    // Remove coupon if it no longer exists
                    if (!$coupon->is_valid()) {

                        // Remove the coupon
                        add_filter('woocommerce_coupons_enabled', array($this, 'woocommerce_enable_coupons'));
                        $this->remove_woocommerce_coupon($code);
                        remove_filter('woocommerce_coupons_enabled', array($this, 'woocommerce_enable_coupons'));
                    }
                }
            }
        }

        /**
         * Temporary enable coupons to remove any when needed
         *
         * @access public
         * @return string
         */
        public function woocommerce_enable_coupons()
        {
            return 'yes';
        }

        /**
         * Remove single coupon by name
         * Support for pre-2.1 WooCommerce
         *
         * @access public
         * @param string $coupon
         * @return void
         */
        public function remove_woocommerce_coupon($coupon)
        {
            global $woocommerce;

            if (self::wc_version_gte('2.1')) {
                $woocommerce->cart->remove_coupon($coupon);
                WC()->session->set('refresh_totals', true);
            }
            else {

                $position = array_search($coupon, $woocommerce->cart->applied_coupons);

                if ($position !== false) {
                    unset($woocommerce->cart->applied_coupons[$position]);
                }

                WC()->session->set('applied_coupons', $woocommerce->cart->applied_coupons);
            }

            // Flag totals for refresh
            WC()->session->set('refresh_totals', true);
        }

        /**
         * Apply fake coupon to cart
         *
         * @access public
         * @return void
         */
        public function apply_fake_coupon()
        {
            global $woocommerce;

            $coupon_code = $this->get_fake_coupon_code();
            $the_coupon = new WC_Coupon($coupon_code);

            if ($the_coupon->is_valid() && !$woocommerce->cart->has_discount($coupon_code)) {

                // Do not apply coupon with individual use coupon already applied
                if ($woocommerce->cart->applied_coupons) {
                    foreach ($woocommerce->cart->applied_coupons as $code) {
                        $coupon = new WC_Coupon($code);

                        if ($coupon->individual_use == 'yes') {
                            return false;
                        }
                    }
                }

                // Add coupon
                $woocommerce->cart->applied_coupons[] = $coupon_code;
                do_action('woocommerce_applied_coupon', $coupon_code);

                return true;
            }
        }

        /**
         * Get fake coupon code
         *
         * @access public
         * @return string
         */
        public function get_fake_coupon_code()
        {
            return apply_filters('woocommerce_coupon_code', $this->opt['settings']['cart_discount_title']);
        }

        /**
         * Maybe add virtual coupon for cart discounts
         *
         * @access public
         * @param bool $unknown_param
         * @param string $coupon_code
         * @return mixed
         */
        public function maybe_add_virtual_coupon($unknown_param, $coupon_code)
        {
            if ($coupon_code == $this->get_fake_coupon_code()) {
                $coupon = array(
                    'id'                            => 2147483647,
                    'type'                          => 'fixed_cart',
                    'amount'                        => $this->cart_discount_to_apply['discount'],
                    'individual_use'                => 'no',
                    'product_ids'                   => array(),
                    'exclude_product_ids'           => array(),
                    'usage_limit'                   => '',
                    'usage_limit_per_user'          => '',
                    'limit_usage_to_x_items'        => '',
                    'usage_count'                   => '',
                    'expiry_date'                   => '',
                    'apply_before_tax'              => 'yes',
                    'free_shipping'                 => 'no',
                    'product_categories'            => array(),
                    'exclude_product_categories'    => array(),
                    'exclude_sale_items'            => 'no',
                    'minimum_amount'                => '',
                    'maximum_amount'                => '',
                    'customer_email'                => '',
                );

                return $coupon;
            }
        }

        /**
         * Actually apply calculated pricing adjustment
         *
         * @access public
         * @param string $cart_item_key
         * @param array $adjustment
         * @return void
         */
        public function apply_pricing_adjustment($cart_item_key, $adjustment)
        {
            global $woocommerce;

            // Make sure item exists in cart
            if (!isset($woocommerce->cart->cart_contents[$cart_item_key])) {
                return;
            }

            // Log changes
            $woocommerce->cart->cart_contents[$cart_item_key]['rp_wcdpd'] = array(
                'original_price'    => get_option('woocommerce_tax_display_cart') == 'excl' ? $woocommerce->cart->cart_contents[$cart_item_key]['data']->get_price_excluding_tax() : $woocommerce->cart->cart_contents[$cart_item_key]['data']->get_price_including_tax(),
                'log'               => $adjustment['log'],
            );

            // Actually adjust price in cart
            $woocommerce->cart->cart_contents[$cart_item_key]['data']->price = $adjustment['price'];
        }

        /**
         * Sort cart by price
         *
         * @access public
         * @param array $cart
         * @param string $order
         * @return array
         */
        public function sort_cart_by_price($cart, $order)
        {
            $cart_sorted = array();

            foreach ($cart as $cart_item_key => $cart_item) {
                $cart_sorted[$cart_item_key] = $cart_item;
            }

            uasort($cart_sorted, array($this, 'sort_cart_by_price_method_' . $order));

            return $cart_sorted;
        }

        /**
         * Sort cart by price uasort collable - ascending
         *
         * @access public
         * @param mixed $first
         * @param mixed $second
         * @return bool
         */
        public function sort_cart_by_price_method_asc($first, $second)
        {
            if ($first['data']->get_price() == $second['data']->get_price()) {
                return 0;
            }
            return ($first['data']->get_price() < $second['data']->get_price()) ? -1 : 1;
        }

        /**
         * Sort cart by price uasort collable - descending
         *
         * @access public
         * @param mixed $first
         * @param mixed $second
         * @return bool
         */
        public function sort_cart_by_price_method_desc($first, $second)
        {
            if ($first['data']->get_price() == $second['data']->get_price()) {
                return 0;
            }
            return ($first['data']->get_price() > $second['data']->get_price()) ? -1 : 1;
        }

        /**
         * Sort pricing table - ascending
         *
         * @access public
         * @param mixed $first
         * @param mixed $second
         * @return bool
         */
        public static function sort_pricing_table_method_asc($first, $second)
        {
            return ($first['min'] < $second['min']) ? -1 : 1;
        }

        /**
         * Normalize quantity pricing table (add 1-X row etc)
         *
         * @access public
         * @param array $original_table
         * @return bool
         */
        public static function normalize_quantity_pricing_table($original_table)
        {
            if (empty($original_table) || !is_array($original_table)) {
                return false;
            }

            $table = array();

            // Track ranges to make sure we don't have overlaps
            $used_ranges = array();

            // Iterate over original elements
            foreach ($original_table as $current_row) {

                $row = $current_row;

                // Min quantity
                if (!is_numeric($row['min']) || ($row['min'] < 0)) {
                    if ($row['min'] == '*') {
                        $row['min'] = 1;
                    }
                    else {
                        return false;
                    }
                }

                // Max quantity
                if (!is_numeric($row['max']) || ($row['max'] < 0)) {
                    if ($row['max'] == '*') {
                        $row['max'] = defined(PHP_INT_MAX) ? PHP_INT_MAX : 2147483647;
                    }
                    else {
                        return false;
                    }
                }

                // Min must be smaller than max
                if ($row['min'] > $row['max']) {
                    return false;
                }

                // Range must not overlap with existing ranges
                foreach ($used_ranges as $range) {
                    if ($row['min'] == $range['min']) {
                        return false;
                    }
                    else if ($row['min'] < $range['min']) {
                        if ($row['max'] >= $range['min']) {
                            return false;
                        }
                    }
                    else if ($row['min'] > $range['min']) {
                        if ($row['min'] <= $range['max'] || $row['max'] <= $range['max']) {
                            return false;
                        }
                    }
                }

                $used_ranges[] = array('min' => $row['min'], 'max' => $row['max']);

                // Adjustment type
                if (!isset($row['type']) || !in_array($row['type'], array('percentage', 'price', 'fixed'))) {
                    return false;
                }

                // Value
                if (!is_numeric($row['value'])) {
                    return false;
                }
                else if ($row['type'] == 'percentage' && ($row['value'] < 0 || $row['value'] > 100)) {
                    return false;
                }
                else if (in_array($row['type'], array('price', 'fixed')) && $row['value'] < 0) {
                    return false;
                }

                $table[] = $row;
            }

            if (empty($table)) {
                return false;
            }

            // Sort table ascending
            uasort($table, array('self', 'sort_pricing_table_method_asc'));

            // Check/fix min
            $first_row_values = array_values($table);
            $first_row = array_shift($first_row_values);

            if ($first_row['min'] > 1) {
                array_unshift($table, array(
                    'min'   => 1,
                    'max'   => ($first_row['min'] - 1),
                    'type'  => 'percentage',
                    'value' => 0,
                    'added' => true
                ));
            }

            // Check/fix max
            $last_row_values = array_values($table);
            $last_row = array_pop($last_row_values);

            if ($last_row['max'] < (defined(PHP_INT_MAX) ? PHP_INT_MAX : 2147483647)) {
                array_push($table, array(
                    'min'   => ($last_row['max'] + 1),
                    'max'   => (defined(PHP_INT_MAX) ? PHP_INT_MAX : 2147483647),
                    'type'  => 'percentage',
                    'value' => 0,
                    'added' => true
                ));
            }

            return $table;
        }

        /**
         * Maybe display pricing table or anchor to display pricing table in modal
         *
         * @access public
         * @return void
         */
        public function product_page_pricing_table()
        {
            if ($this->opt['settings']['display_table'] == 'hide' && (!isset($this->opt['settings']['display_offers']) || $this->opt['settings']['display_offers'] == 'hide')) {
                return;
            }

            global $product;

            if (!$product) {
                return;
            }

            // Load required classes
            require_once RP_WCDPD_PLUGIN_PATH . 'includes/classes/Pricing.php';

            $selected_rule = null;

            // Iterate over pricing rules and use the first one that has this product in conditions (or does not have if condition "not in list")
            if (isset($this->opt['pricing']['sets']) && count($this->opt['pricing']['sets'])) {
                foreach ($this->opt['pricing']['sets'] as $rule_key => $rule) {

                    if ($rule['method'] == 'quantity' && $validated_rule = RP_WCDPD_Pricing::validate_rule($rule)) {
                        if ($validated_rule['selection_method'] == 'all' && $this->user_matches_rule($validated_rule)) {
                            $selected_rule = $validated_rule;
                            break;
                        }
                        if ($validated_rule['selection_method'] == 'categories_include' && count(array_intersect($this->get_product_categories($product->id), $validated_rule['categories'])) > 0 && $this->user_matches_rule($validated_rule)) {
                            $selected_rule = $validated_rule;
                            break;
                        }
                        if ($validated_rule['selection_method'] == 'categories_exclude' && count(array_intersect($this->get_product_categories($product->id), $validated_rule['categories'])) == 0 && $this->user_matches_rule($validated_rule)) {
                            $selected_rule = $validated_rule;
                            break;
                        }
                        if ($validated_rule['selection_method'] == 'products_include' && in_array($product->id, $validated_rule['products']) && $this->user_matches_rule($validated_rule)) {
                            $selected_rule = $validated_rule;
                            break;
                        }
                        if ($validated_rule['selection_method'] == 'products_exclude' && !in_array($product->id, $validated_rule['products']) && $this->user_matches_rule($validated_rule)) {
                            $selected_rule = $validated_rule;
                            break;
                        }
                    }
                }
            }

            if (is_array($selected_rule)) {

                // Quantity
                if ($selected_rule['method'] == 'quantity' && in_array($this->opt['settings']['display_table'], array('modal', 'inline')) && isset($selected_rule['pricing'])) {

                    if ($product->product_type == 'variable') {
                        $product_variations = $product->get_available_variations();
                    }

                    // For variable products only - check if prices differ for different variations
                    $multiprice_variable_product = false;

                    if ($product->product_type == 'variable' && !empty($product_variations)) {
                        $last_product_variation = array_slice($product_variations, -1);
                        $last_product_variation_object = new WC_Product_Variable($last_product_variation[0]['variation_id']);
                        $last_product_variation_price = $last_product_variation_object->get_price();

                        foreach ($product_variations as $variation) {
                            $variation_object = new WC_Product_Variable($variation['variation_id']);

                            if ($variation_object->get_price() != $last_product_variation_price) {
                                $multiprice_variable_product = true;
                            }
                        }
                    }

                    if ($multiprice_variable_product) {
                        $variation_table_data = array();

                        foreach ($product_variations as $variation) {
                            $variation_product = new WC_Product_Variation($variation['variation_id']);
                            $variation_table_data[$variation['variation_id']] = $this->pricing_table_calculate_adjusted_prices($selected_rule['pricing'], $variation_product->get_price());
                        }

                        require_once RP_WCDPD_PLUGIN_PATH . 'includes/views/frontend/table-variable.php';
                    }
                    else {
                        if ($product->product_type == 'variable' && !empty($product_variations)) {
                            $variation_product = new WC_Product_Variation($last_product_variation[0]['variation_id']);
                            $table_data = $this->pricing_table_calculate_adjusted_prices($selected_rule['pricing'], $variation_product->get_price());
                        }
                        else {
                            $table_data = $this->pricing_table_calculate_adjusted_prices($selected_rule['pricing'], $product->get_price());
                        }

                        require_once RP_WCDPD_PLUGIN_PATH . 'includes/views/frontend/table-' . $this->opt['settings']['display_table'] . '-' . $this->opt['settings']['pricing_table_style'] . '.php';
                    }

                }

            }
        }

        /**
         * Check if user matches rule requirements
         *
         * @access public
         * @param array $rule
         * @return bool
         */
        public function user_matches_rule($rule)
        {
            if ($rule['user_method'] == 'roles_include') {
                if (count(array_intersect(self::current_user_roles(), $rule['roles'])) < 1) {
                    return false;
                }
            }
            if ($rule['user_method'] == 'roles_exclude') {
                if (count(array_intersect(self::current_user_roles(), $rule['roles'])) > 0) {
                    return false;
                }
            }
            if ($rule['user_method'] == 'capabilities_include') {
                if (count(array_intersect(self::current_user_capabilities(), $rule['capabilities'])) < 1) {
                    return false;
                }
            }
            if ($rule['user_method'] == 'capabilities_exclude') {
                if (count(array_intersect(self::current_user_capabilities(), $rule['capabilities'])) > 0) {
                    return false;
                }
            }
            if ($rule['user_method'] == 'users_include') {
                if (!in_array(get_current_user_id(), $rule['users'])) {
                    return false;
                }
            }
            if ($rule['user_method'] == 'users_exclude') {
                if (in_array(get_current_user_id(), $rule['users'])) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Calculate prices to display for pricing tables
         *
         * @access public
         * @param array $table_data
         * @param float $original_price
         * @return void
         */
        public function pricing_table_calculate_adjusted_prices($table_data, $original_price)
        {
            foreach ($table_data as $row_key => $row) {
                $current_adjusted_price = $original_price - RP_WCDPD_Pricing::apply_adjustment($original_price, array('type' => $row['type'], 'value' => $row['value']));
                $current_adjusted_price = ($current_adjusted_price < 0) ? 0 : $current_adjusted_price;
                $table_data[$row_key]['display_price'] = $this->format_price($current_adjusted_price);
            }

            return $table_data;
        }

        /**
         * Format price to WooCommerce standards
         *
         * @access public
         * @param float $price
         * @return string
         */
        public function format_price($price)
        {
            $num_decimals = absint(get_option('woocommerce_price_num_decimals'));
            $currency_symbol = get_woocommerce_currency_symbol();
            $decimal_sep = wp_specialchars_decode(stripslashes(get_option('woocommerce_price_decimal_sep')), ENT_QUOTES);
            $thousands_sep = wp_specialchars_decode(stripslashes(get_option('woocommerce_price_thousand_sep')), ENT_QUOTES);

            $price = number_format($price, $num_decimals, $decimal_sep, $thousands_sep);

            if (apply_filters('woocommerce_price_trim_zeros', false) && $num_decimals > 0) {
                $price = preg_replace('/' . preg_quote(get_option('woocommerce_price_decimal_sep' ), '/') . '0++$/', '', $price);
            }

            return sprintf(get_woocommerce_price_format(), $currency_symbol, $price);
        }

        /**
         * WordPress activation hook
         *
         * @access public
         * @return void
         */
        public function activate()
        {
            if (!get_option('rp_wcdpd_options')) {
                add_option('rp_wcdpd_options', array(RP_WCDPD_OPTIONS_VERSION => $this->default_options));
            }
        }

        /**
         * Get some preconfigured values (usually constants that do not change)
         *
         * @access public
         * @return void
         */
        public function plugin_config_init()
        {
            // Define settings page structure
            $this->settings_page_tabs = array(
                'pricing' => array(
                    'title' => __('Pricing Rules', 'rp_wcdpd'),
                    'icon'  => 'tags'
                ),
                'discounts' => array(
                    'title' => __('Cart Discounts', 'rp_wcdpd'),
                    'icon'  => 'shopping-cart'
                ),
                'settings' => array(
                    'title' => __('Settings', 'rp_wcdpd'),
                    'icon'  => 'cogs'
                ),
                'localization' => array(
                    'title' => __('Localization', 'rp_wcdpd'),
                    'icon'  => 'font'
                )
            );

            // Define default options (until real options are saved)
            $this->default_options = array(
                'pricing' => array(
                    'apply_multiple' => 'first',
                    'sets' => array(
                        1 => array(
                            'description'                   => '',
                            'method'                        => 'quantity',
                            'quantities_based_on'           => 'exclusive_product',
                            'if_matched'                    => 'all',
                            'valid_from'                    => '',
                            'valid_until'                   => '',
                            'selection_method'              => 'all',
                            'categories'                    => array(),
                            'products'                      => array(),
                            'user_method'                   => 'all',
                            'roles'                         => array(),
                            'capabilities'                  => array(),
                            'users'                         => array(),
                            'pricing' => array(
                                1 => array(
                                    'min'       => '',
                                    'max'       => '',
                                    'type'      => 'percentage',
                                    'value'     => '',
                                )
                            ),
                            'quantity_products_to_adjust'   => 'matched',
                            'quantity_categories'           => array(),
                            'quantity_products'             => array(),
                            'special_purchase'              => '',
                            'special_products_to_adjust'    => 'matched',
                            'special_categories'            => array(),
                            'special_products'              => array(),
                            'special_adjust'                => '',
                            'special_type'                  => 'percentage',
                            'special_value'                 => '',
                            'special_repeat'                => 0,
                        )
                    ),
                ),
                'discounts' => array(
                    'apply_multiple' => 'first',
                    'sets' => array(
                        1 => array(
                            'description'                   => '',
                            'valid_from'                    => '',
                            'valid_until'                   => '',
                            'only_if_pricing_not_adjusted'  => 0,
                            'conditions' => array(
                                1 => array(
                                    'key'                   => 'subtotal_bottom',
                                    'value'                 => '',
                                    'products'              => array(),
                                    'categories'            => array(),
                                    'users'                 => array(),
                                    'roles'                 => array(),
                                    'capabilities'          => array(),
                                    'shipping_countries'    => array(),
                                )
                            ),
                            'type'                          => 'percentage',
                            'value'                         => '',
                        )
                    ),
                ),
                'settings' => array(
                    'cart_discount_title'           => 'DISCOUNT',
                    'display_table'                 => 'hide',
                    'pricing_table_style'           => 'horizontal',
                    'display_position'              => 'woocommerce_before_add_to_cart_form',
                ),
                'localization' => array(
                    'quantity'  => __('Quantity', 'rp_wcdpd'),
                    'price'     => __('Price', 'rp_wcdpd'),
                    'quantity_discounts'  => __('Quantity discounts', 'rp_wcdpd'),
                    'special_offers'     => __('Special offers', 'rp_wcdpd'),
                )
            );

            // Properties to pass to Javascript
            $this->to_javascript = array(
                'labels' => array(
                    'pricing_rule'      => __('Pricing Rule #', 'rp_wcdpd'),
                    'discounts_rule'    => __('Discount Rule #', 'rp_wcdpd'),
                ),
                'conditional_fields' => array(
                    '.rp_wcdpd_selection_method_field' => array(
                        'all' => array(
                            'show'      => array(),
                            'hide'      => array('.rp_wcdpd_categories_field', '.rp_wcdpd_products_field'),
                        ),
                        'categories_include' => array(
                            'show'      => array('.rp_wcdpd_categories_field'),
                            'hide'      => array('.rp_wcdpd_products_field'),
                        ),
                        'categories_exclude' => array(
                            'show'      => array('.rp_wcdpd_categories_field'),
                            'hide'      => array('.rp_wcdpd_products_field'),
                        ),
                        'products_include' => array(
                            /*'values'    => array('products_include', 'products_exclude'),*/
                            'show'      => array('.rp_wcdpd_products_field'),
                            'hide'      => array('.rp_wcdpd_categories_field'),
                        ),
                        'products_exclude' => array(
                            'show'      => array('.rp_wcdpd_products_field'),
                            'hide'      => array('.rp_wcdpd_categories_field'),
                        ),
                    ),
                    '.rp_wcdpd_user_method_field' => array(
                        'all' => array(
                            'show'      => array(),
                            'hide'      => array('.rp_wcdpd_roles_field', '.rp_wcdpd_users_field', '.rp_wcdpd_capabilities_field'),
                        ),
                        'roles_include' => array(
                            'show'      => array('.rp_wcdpd_roles_field'),
                            'hide'      => array('.rp_wcdpd_users_field', '.rp_wcdpd_capabilities_field'),
                        ),
                        'roles_exclude' => array(
                            'show'      => array('.rp_wcdpd_roles_field'),
                            'hide'      => array('.rp_wcdpd_users_field', '.rp_wcdpd_capabilities_field'),
                        ),
                        'capabilities_include' => array(
                            'show'      => array('.rp_wcdpd_capabilities_field'),
                            'hide'      => array('.rp_wcdpd_users_field', '.rp_wcdpd_roles_field'),
                        ),
                        'capabilities_exclude' => array(
                            'show'      => array('.rp_wcdpd_capabilities_field'),
                            'hide'      => array('.rp_wcdpd_users_field', '.rp_wcdpd_roles_field'),
                        ),
                        'users_include' => array(
                            'show'      => array('.rp_wcdpd_users_field'),
                            'hide'      => array('.rp_wcdpd_roles_field', '.rp_wcdpd_capabilities_field'),
                        ),
                        'users_exclude' => array(
                            'show'      => array('.rp_wcdpd_users_field'),
                            'hide'      => array('.rp_wcdpd_roles_field', '.rp_wcdpd_capabilities_field'),
                        ),
                    ),
                    '.rp_wcdpd_quantity_products_to_adjust_field' => array(
                        'matched' => array(
                            'show'      => array(),
                            'hide'      => array('.rp_wcdpd_quantity_categories_field', '.rp_wcdpd_quantity_products_field'),
                        ),
                        'other_categories' => array(
                            'show'      => array('.rp_wcdpd_quantity_categories_field'),
                            'hide'      => array('.rp_wcdpd_quantity_products_field'),
                        ),
                        'other_products' => array(
                            'show'      => array('.rp_wcdpd_quantity_products_field'),
                            'hide'      => array('.rp_wcdpd_quantity_categories_field'),
                        ),
                    ),
                    '.rp_wcdpd_special_products_to_adjust_field' => array(
                        'matched' => array(
                            'show'      => array(),
                            'hide'      => array('.rp_wcdpd_special_categories_field', '.rp_wcdpd_special_products_field'),
                        ),
                        'other_categories' => array(
                            'show'      => array('.rp_wcdpd_special_categories_field'),
                            'hide'      => array('.rp_wcdpd_special_products_field'),
                        ),
                        'other_products' => array(
                            'show'      => array('.rp_wcdpd_special_products_field'),
                            'hide'      => array('.rp_wcdpd_special_categories_field'),
                        ),
                    ),
                    '.rp_wcdpd_conditions_key_field' => array(
                        'subtotal_bottom' => array(
                            'show'      => array('.rp_wcdpd_conditions_value_field'),
                            'hide'      => array('.rp_wcdpd_conditions_products_field', '.rp_wcdpd_conditions_categories_field', '.rp_wcdpd_conditions_shipping_countries_field', '.rp_wcdpd_conditions_users_field', '.rp_wcdpd_conditions_roles_field', '.rp_wcdpd_conditions_capabilities_field'),
                        ),
                        'subtotal_top' => array(
                            'show'      => array('.rp_wcdpd_conditions_value_field'),
                            'hide'      => array('.rp_wcdpd_conditions_products_field', '.rp_wcdpd_conditions_categories_field', '.rp_wcdpd_conditions_shipping_countries_field', '.rp_wcdpd_conditions_users_field', '.rp_wcdpd_conditions_roles_field', '.rp_wcdpd_conditions_capabilities_field'),
                        ),
                        'item_count_bottom' => array(
                            'show'      => array('.rp_wcdpd_conditions_value_field'),
                            'hide'      => array('.rp_wcdpd_conditions_products_field', '.rp_wcdpd_conditions_categories_field', '.rp_wcdpd_conditions_shipping_countries_field', '.rp_wcdpd_conditions_users_field', '.rp_wcdpd_conditions_roles_field', '.rp_wcdpd_conditions_capabilities_field'),
                        ),
                        'item_count_top' => array(
                            'show'      => array('.rp_wcdpd_conditions_value_field'),
                            'hide'      => array('.rp_wcdpd_conditions_products_field', '.rp_wcdpd_conditions_categories_field', '.rp_wcdpd_conditions_shipping_countries_field', '.rp_wcdpd_conditions_users_field', '.rp_wcdpd_conditions_roles_field', '.rp_wcdpd_conditions_capabilities_field'),
                        ),
                        'quantity_bottom' => array(
                            'show'      => array('.rp_wcdpd_conditions_value_field'),
                            'hide'      => array('.rp_wcdpd_conditions_products_field', '.rp_wcdpd_conditions_categories_field', '.rp_wcdpd_conditions_shipping_countries_field', '.rp_wcdpd_conditions_users_field', '.rp_wcdpd_conditions_roles_field', '.rp_wcdpd_conditions_capabilities_field'),
                        ),
                        'quantity_top' => array(
                            'show'      => array('.rp_wcdpd_conditions_value_field'),
                            'hide'      => array('.rp_wcdpd_conditions_products_field', '.rp_wcdpd_conditions_categories_field', '.rp_wcdpd_conditions_shipping_countries_field', '.rp_wcdpd_conditions_users_field', '.rp_wcdpd_conditions_roles_field', '.rp_wcdpd_conditions_capabilities_field'),
                        ),
                        'history_count' => array(
                            'show'      => array('.rp_wcdpd_conditions_value_field'),
                            'hide'      => array('.rp_wcdpd_conditions_products_field', '.rp_wcdpd_conditions_categories_field', '.rp_wcdpd_conditions_shipping_countries_field', '.rp_wcdpd_conditions_users_field', '.rp_wcdpd_conditions_roles_field', '.rp_wcdpd_conditions_capabilities_field'),
                        ),
                        'history_amount' => array(
                            'show'      => array('.rp_wcdpd_conditions_value_field'),
                            'hide'      => array('.rp_wcdpd_conditions_products_field', '.rp_wcdpd_conditions_categories_field', '.rp_wcdpd_conditions_shipping_countries_field', '.rp_wcdpd_conditions_users_field', '.rp_wcdpd_conditions_roles_field', '.rp_wcdpd_conditions_capabilities_field'),
                        ),
                        'products' => array(
                            'show'      => array('.rp_wcdpd_conditions_products_field'),
                            'hide'      => array('.rp_wcdpd_conditions_value_field', '.rp_wcdpd_conditions_categories_field', '.rp_wcdpd_conditions_shipping_countries_field', '.rp_wcdpd_conditions_users_field', '.rp_wcdpd_conditions_roles_field', '.rp_wcdpd_conditions_capabilities_field'),
                        ),
                        'products_not' => array(
                            'show'      => array('.rp_wcdpd_conditions_products_field'),
                            'hide'      => array('.rp_wcdpd_conditions_value_field', '.rp_wcdpd_conditions_categories_field', '.rp_wcdpd_conditions_shipping_countries_field', '.rp_wcdpd_conditions_users_field', '.rp_wcdpd_conditions_roles_field', '.rp_wcdpd_conditions_capabilities_field'),
                        ),
                        'categories' => array(
                            'show'      => array('.rp_wcdpd_conditions_categories_field'),
                            'hide'      => array('.rp_wcdpd_conditions_value_field', '.rp_wcdpd_conditions_products_field', '.rp_wcdpd_conditions_shipping_countries_field', '.rp_wcdpd_conditions_users_field', '.rp_wcdpd_conditions_roles_field', '.rp_wcdpd_conditions_capabilities_field'),
                        ),
                        'categories_not' => array(
                            'show'      => array('.rp_wcdpd_conditions_categories_field'),
                            'hide'      => array('.rp_wcdpd_conditions_value_field', '.rp_wcdpd_conditions_products_field', '.rp_wcdpd_conditions_shipping_countries_field', '.rp_wcdpd_conditions_users_field', '.rp_wcdpd_conditions_roles_field', '.rp_wcdpd_conditions_capabilities_field'),
                        ),
                        'users' => array(
                            'show'      => array('.rp_wcdpd_conditions_users_field'),
                            'hide'      => array('.rp_wcdpd_conditions_value_field', '.rp_wcdpd_conditions_products_field', '.rp_wcdpd_conditions_categories_field', '.rp_wcdpd_conditions_shipping_countries_field', '.rp_wcdpd_conditions_roles_field', '.rp_wcdpd_conditions_capabilities_field'),
                        ),
                        'roles' => array(
                            'show'      => array('.rp_wcdpd_conditions_roles_field'),
                            'hide'      => array('.rp_wcdpd_conditions_value_field', '.rp_wcdpd_conditions_products_field', '.rp_wcdpd_conditions_categories_field', '.rp_wcdpd_conditions_shipping_countries_field', '.rp_wcdpd_conditions_users_field', '.rp_wcdpd_conditions_capabilities_field'),
                        ),
                        'capabilities' => array(
                            'show'      => array('.rp_wcdpd_conditions_capabilities_field'),
                            'hide'      => array('.rp_wcdpd_conditions_value_field', '.rp_wcdpd_conditions_products_field', '.rp_wcdpd_conditions_categories_field', '.rp_wcdpd_conditions_shipping_countries_field', '.rp_wcdpd_conditions_users_field', '.rp_wcdpd_conditions_roles_field'),
                        ),
                        'shipping_countries' => array(
                            'show'      => array('.rp_wcdpd_conditions_shipping_countries_field'),
                            'hide'      => array('.rp_wcdpd_conditions_value_field', '.rp_wcdpd_conditions_products_field', '.rp_wcdpd_conditions_categories_field', '.rp_wcdpd_conditions_users_field', '.rp_wcdpd_conditions_roles_field', '.rp_wcdpd_conditions_capabilities_field'),
                        ),
                    ),
                ),
                'hints' => array(
                    'pricing' => array(
                        // Top right field
                        //'rp_wcdpd_apply_multiple_field'         => __('', 'rp_wcdpd'),
                        // General settings
                        'rp_wcdpd_description_field'            => __('This will only be used for your own reference.', 'rp_wcdpd'),
                        'rp_wcdpd_method_field'                 => __('All three pricing rule methods are completely different:<br /><br /><i>Quantity discount</i> is used to set up tiered discounts that depend on quantity of specific product purchased.<br /><br /><i>Special offer</i> is used to set up <i>buy two get one free</i> and similar scenarios.<br /><br /><i>Exclude matched items</i> is used to exclude specific products or categories from other, usually more general rules (e.g. exlude specific product from a category rule).', 'rp_wcdpd'),
                        'rp_wcdpd_quantities_based_on_field'    => __('Use cumulative methods when you wish to set up scenarios like <i>get 10% discount on Product X when you buy any 10 items from Category Y</i>.', 'rp_wcdpd'),
                        //'rp_wcdpd_if_matched_field'             => __('', 'rp_wcdpd'),
                        //'rp_wcdpd_pricing_valid_from_field'     => __('', 'rp_wcdpd'),
                        // Conditions
                        //'rp_wcdpd_selection_method_field'       => __('', 'rp_wcdpd'),
                        //'rp_wcdpd_categories_field'             => __('', 'rp_wcdpd'),
                        //'rp_wcdpd_products_field'               => __('', 'rp_wcdpd'),
                        //'rp_wcdpd_user_method_field'            => __('', 'rp_wcdpd'),
                        //'rp_wcdpd_roles_field'                  => __('', 'rp_wcdpd'),
                        //'rp_wcdpd_capabilities_field'           => __('', 'rp_wcdpd'),
                        //'rp_wcdpd_users_field'                  => __('', 'rp_wcdpd'),
                        // Quantity
                        //'rp_wcdpd_sets_quantity'                        => __('', 'rp_wcdpd'),
                        'rp_wcdpd_quantity_products_to_adjust_field'    => __('Please note that if you select other cart items to adjust, quantities of matched items (not items to adjust) will be used to determine pricing tier. Biggest quantity of all quantities will be used.', 'rp_wcdpd'),
                        //'rp_wcdpd_quantity_categories_field'            => __('', 'rp_wcdpd'),
                        //'rp_wcdpd_quantity_products_field'              => __('', 'rp_wcdpd'),
                        // Special Offer
                        //'rp_wcdpd_sets_special'                         => __('', 'rp_wcdpd'),
                        //'rp_wcdpd_special_purchase_field'               => __('', 'rp_wcdpd'),
                        'rp_wcdpd_special_products_to_adjust_field'     => __('Please note that if you select other cart items to adjust, quantities of matched items (not items to adjust) will be taken into account. Biggest quantity of all quantities will be used.', 'rp_wcdpd'),
                        //'rp_wcdpd_special_categories_field'             => __('', 'rp_wcdpd'),
                        //'rp_wcdpd_special_products_field'               => __('', 'rp_wcdpd'),
                        //'rp_wcdpd_special_adjust_field'                 => __('', 'rp_wcdpd'),
                        //'rp_wcdpd_special_type_field'                   => __('', 'rp_wcdpd'),
                        //'rp_wcdpd_special_value_field'                  => __('', 'rp_wcdpd'),
                        'rp_wcdpd_special_repeat_field'                 => __('Whether to apply the same rule multiple times (if quantities allow) or not.<br /><br />For example, if you set up <i>buy 2 get 1 free</i> rule and leave <i>Repeat</i> unchecked, then 10 products at full price will earn only 1 free product.<br /><br />On the other hand, if you set <i>Repeat</i> to active, 10 products at full price will earn 5 free products.', 'rp_wcdpd'),
                    ),
                    'discounts' => array(
                        // Top right field
                        //'rp_wcdpd_apply_multiple_field' => __('', 'rp_wcdpd'),
                        // General settings
                        'rp_wcdpd_description_field' => __('This will only be used for your own reference.', 'rp_wcdpd'),
                        //'rp_wcdpd_discounts_valid_from_field' => __('', 'rp_wcdpd'),
                        'rp_wcdpd_discounts_only_if_pricing_not_adjusted_field' => __('If checked, this cart discount will not be applied if cart contains at least one item which price was adjusted by pricing rules.', 'rp_wcdpd'),
                        // Conditions
                        //'rp_wcdpd_sets_section_discounts_conditions' => __('', 'rp_wcdpd'),
                        // Discount
                        //'rp_wcdpd_discounts_type_field' => __('', 'rp_wcdpd'),
                        //'rp_wcdpd_discounts_value_field' => __('', 'rp_wcdpd'),
                    ),
                    'settings' => array(
                        // General settings
                        'rp_wcdpd_settings_cart_discount_title_field' => __('On newer versions of WooCommerce, this will appear on the totals block as a discount title (similar to coupon code).', 'rp_wcdpd'),
                        // Quantity pricing table
                        'rp_wcdpd_settings_display_table_field' => __('Whether or not to display a pricing table on products that have quantity discount rule configured for them.<br /><br />This setting also allows you to choose whether table should be displayed inline or opened in a modal.<br /><br />It is important to understand that if you have any other rules and/or conditions in addition to the very basic <i>Quantity discount</i> table, the final price may differ from what is displayed in the pricing table. It is your own responsibility to determine whether displaying a quantity pricing table on a product page for marketing purposes will work fine in your unique setup.', 'rp_wcdpd'),
                        //'rp_wcdpd_settings_pricing_table_style_field' => __('', 'rp_wcdpd'),
                        'rp_wcdpd_settings_display_position_field' => __('Choose position on the product page where pricing table (or link to open a modal) should appear. Not all of positions will work with all themes.', 'rp_wcdpd'),
                    ),
                ),
            );
        }

        /**
         * Get options saved to database or default options if no options saved
         *
         * @access public
         * @return array
         */
        public function get_options()
        {
            // Get options from database
            $saved_options = get_option('rp_wcdpd_options', array());

            // Get current version (for major updates in future)
            if (!empty($saved_options)) {
                if (isset($saved_options[RP_WCDPD_OPTIONS_VERSION])) {
                    $saved_options = $saved_options[RP_WCDPD_OPTIONS_VERSION];
                }
                else {
                    // Migrate options here if needed...
                }
            }

            // Merge with default options
            foreach (array('pricing', 'discounts', 'settings', 'localization') as $tab) {
                if (isset($saved_options[$tab])) {
                    $options[$tab] = array_merge($this->default_options[$tab], $saved_options[$tab]);
                }
                else {
                    $options[$tab] = $this->default_options[$tab];
                }
            }

            return $options;
        }

        /**
         * Check if current user can manage plugin options
         *
         * @access public
         * @return bool
         */
        public function user_can_manage()
        {
            $user_role = @array_shift(self::current_user_roles());
            return in_array($user_role, array('administrator', 'shop_manager')) ? true : false;
        }

        /**
         * Get list of roles assigned to current user
         *
         * @access public
         * @return array
         */
        public static function current_user_roles()
        {
            global $current_user;
            get_currentuserinfo();
            return $current_user->roles;
        }

        /**
         * Get list of capabilities assigned to current user
         *
         * @access public
         * @return array
         */
        public static function current_user_capabilities()
        {
            // Groups plugin active?
            if (class_exists('Groups_User') && class_exists('Groups_Wordpress')) {
                $groups_user = new Groups_User(get_current_user_id());

                if ($groups_user) {
                    return $groups_user->capabilities_deep;
                }
                else {
                    return array();
                }
            }

            // Get regular WP capabilities
            else {

                global $current_user;
                get_currentuserinfo();
                $all_current_user_capabilities = $current_user->allcaps;
                $current_user_capabilities = array();

                if (is_array($all_current_user_capabilities)) {
                    foreach ($all_current_user_capabilities as $capability => $status) {
                        if ($status) {
                            $current_user_capabilities[] = $capability;
                        }
                    }
                }

                return $current_user_capabilities;
            }
        }

        /**
         * Add link to admin page under Woocommerce menu
         *
         * @access public
         * @return void
         */
        public function add_admin_menu()
        {
            if (!$this->user_can_manage()) {
                return;
            }

            global $submenu;

            if (isset($submenu['woocommerce'])) {
                add_submenu_page(
                    'woocommerce',
                    __('Pricing & Discounts', 'woo_pdf'),
                    __('Pricing & Discounts', 'woo_pdf'),
                    'edit_posts',
                    'wc_pricing_and_discounts',
                    array($this, 'set_up_admin_page')
                );
            }
        }

        /**
         * Set up admin page
         *
         * @access public
         * @return void
         */
        public function set_up_admin_page()
        {
            $current_tab = $this->get_current_settings_tab();

            // Print notices
            settings_errors('rp_wcdpd');

            // Load lists for selection
            if (in_array($current_tab, array('pricing', 'discounts'))) {
                $all_products = $this->get_all_product_list();
                $all_categories = $this->get_all_category_list();
                $all_countries = $this->get_all_country_list();
                $all_roles = $this->get_all_role_list();
                $all_capabilities = $this->get_all_capability_list();
                $all_users = $this->get_all_user_list();
            }

            // Print header (tabs)
            require_once RP_WCDPD_PLUGIN_PATH . 'includes/views/admin/header.php';

            // Print settings page content
            require_once RP_WCDPD_PLUGIN_PATH . 'includes/views/admin/' . $current_tab . '.php';

            // Print footer
            require_once RP_WCDPD_PLUGIN_PATH . 'includes/views/admin/footer.php';

            // Pass some variables to Javascript
            require_once RP_WCDPD_PLUGIN_PATH . 'includes/views/admin/javascript.php';
        }

        /**
         * Set up plugin options validation etc
         *
         * @access public
         * @return void
         */
        public function plugin_options_setup()
        {
            if (!$this->user_can_manage()) {
                return;
            }

            foreach(array('pricing', 'discounts', 'settings', 'localization') as $tab) {
                register_setting(
                    'rp_wcdpd_opt_group_' . $tab,
                    'rp_wcdpd_options',
                    array($this, 'options_validate')
                );
            }
        }

        /**
         * Validate options
         *
         * @access public
         * @param array $input
         * @return array
         */
        public function options_validate($input)
        {
            // Get current settings
            $output = $this->opt;

            // Track errors
            $error = null;

            // Do we know which tab this is?
            if (isset($input['current_tab']) && isset($this->default_options[$input['current_tab']])) {

                // Validate fields of this tab
                $validation_results = $this->options_validate_fields($input['current_tab'], $input[$input['current_tab']], $output[$input['current_tab']]);

                // Save to output or set error
                if (is_array($validation_results)) {
                    $output[$input['current_tab']] = $validation_results;
                }
                else if (is_string($validation_results)) {
                    $error = $validation_results;
                }
                else {
                    $error = __('Something went wrong. Please try again.', 'rp_wcdpd');
                }
            }
            else {
                $error = __('Something went wrong. Please try again.', 'rp_wcdpd');
            }

            // Display errors or success message
            if ($error === null) {
                add_settings_error(
                    'rp_wcdpd',
                    'updated',
                    'Your settings have been saved.',
                    'updated'
                );
            }
            else {
                add_settings_error(
                    'rp_wcdpd',
                    'rp_wcdpd_error',
                    $error
                );
            }

            return array(RP_WCDPD_OPTIONS_VERSION => $output);
        }

        /**
         * Validate option fields
         *
         * @access public
         * @param string $context
         * @param array $input
         * @param array $output
         * @return array
         */
        public function options_validate_fields($context, $input, $output)
        {
            $error = null;

            if ($context == 'pricing') {

                // Check if basic data structure is ok
                if (!isset($input['apply_multiple']) || !isset($input['sets']) || !is_array($input['sets']) || empty($input['sets'])) {
                    return __('Invalid data structure.', 'rp_wcdpd');
                }

                // Apply rule
                $output['apply_multiple'] = in_array($input['apply_multiple'], array('first', 'all', 'biggest')) ? $input['apply_multiple'] : 'first';

                // Iterate over sets
                $output['sets'] = array();
                $current_set_key = 1;

                foreach ($input['sets'] as $set_key => $set) {

                    $opt = array();

                    // Rule description
                    $opt['description'] = (isset($set['description']) && is_string($set['description']) && !empty($set['description'])) ? $set['description'] : '';

                    // Method
                    $opt['method'] = (isset($set['method']) && in_array($set['method'], array('quantity', 'special', 'exclude'))) ? $set['method'] : 'quantity';

                    // Quantities based on
                    $opt['quantities_based_on'] = (isset($set['quantities_based_on']) && in_array($set['quantities_based_on'], array('exclusive_product', 'exclusive_variation', 'exclusive_configuration', 'cumulative_categories', 'cumulative_all'))) ? $set['quantities_based_on'] : 'exclusive_product';

                    // If conditions are matched
                    $opt['if_matched'] = (isset($set['if_matched']) && in_array($set['if_matched'], array('all', 'this', 'other'))) ? $set['if_matched'] : 'all';

                    // Valid from
                    $opt['valid_from'] = (isset($set['valid_from']) && strtotime($set['valid_from'])) ? $set['valid_from'] : '';

                    // Valid until
                    $opt['valid_until'] = (isset($set['valid_until']) && strtotime($set['valid_until'])) ? $set['valid_until'] : '';

                    // Apply to
                    $opt['selection_method'] = (isset($set['selection_method']) && in_array($set['selection_method'], array('all', 'categories_include', 'categories_exclude', 'products_include', 'products_exclude'))) ? $set['selection_method'] : 'all';

                    // Category list (Apply to)
                    if (isset($set['categories']) && is_array($set['categories']) && !empty($set['categories'])) {
                        $opt['categories'] = $set['categories'];
                    }
                    else {
                        $opt['categories'] = array();
                    }

                    // Product list (Apply to)
                    if (isset($set['products']) && is_array($set['products']) && !empty($set['products'])) {
                        $opt['products'] = $set['products'];
                    }
                    else {
                        $opt['products'] = array();
                    }

                    // Customers
                    $opt['user_method'] = (isset($set['user_method']) && in_array($set['user_method'], array('all', 'roles_include', 'roles_exclude', 'capabilities_include', 'capabilities_exclude', 'users_include', 'users_exclude'))) ? $set['user_method'] : 'all';

                    // Role list (Customers)
                    if (isset($set['roles']) && is_array($set['roles']) && !empty($set['roles'])) {
                        $opt['roles'] = $set['roles'];
                    }
                    else {
                        $opt['roles'] = array();
                    }

                    // Capability list (Customers)
                    if (isset($set['capabilities']) && is_array($set['capabilities']) && !empty($set['capabilities'])) {
                        $opt['capabilities'] = $set['capabilities'];
                    }
                    else {
                        $opt['capabilities'] = array();
                    }

                    // Customer list (Customers)
                    if (isset($set['users']) && is_array($set['users']) && !empty($set['users'])) {
                        $opt['users'] = $set['users'];
                    }
                    else {
                        $opt['users'] = array();
                    }

                    // (Quantity Discount) Products to adjust
                    $opt['quantity_products_to_adjust'] = (isset($set['quantity_products_to_adjust']) && in_array($set['quantity_products_to_adjust'], array('matched', 'other_categories', 'other_products'))) ? $set['quantity_products_to_adjust'] : 'matched';

                    // (Quantity Discount) Category list (Products to adjust)
                    if (isset($set['quantity_categories']) && is_array($set['quantity_categories']) && !empty($set['quantity_categories'])) {
                        $opt['quantity_categories'] = $set['quantity_categories'];
                    }
                    else {
                        $opt['quantity_categories'] = array();
                    }

                    // (Quantity Discount) Product list (Products to adjust)
                    if (isset($set['quantity_products']) && is_array($set['quantity_products']) && !empty($set['quantity_products'])) {
                        $opt['quantity_products'] = $set['quantity_products'];
                    }
                    else {
                        $opt['quantity_products'] = array();
                    }

                    // (Special Offer) Amount to purchase
                    if (isset($set['special_purchase']) && $set['special_purchase'] != '') {
                        $opt['special_purchase'] = (int) preg_replace('/[^0-9]/', '', $set['special_purchase']);
                    }
                    else {
                        $opt['special_purchase'] = '';
                    }

                    // (Special Offer) Products to adjust
                    $opt['special_products_to_adjust'] = (isset($set['special_products_to_adjust']) && in_array($set['special_products_to_adjust'], array('matched', 'other_categories', 'other_products'))) ? $set['special_products_to_adjust'] : 'matched';

                    // (Special Offer) Category list (Products to adjust)
                    if (isset($set['special_categories']) && is_array($set['special_categories']) && !empty($set['special_categories'])) {
                        $opt['special_categories'] = $set['special_categories'];
                    }
                    else {
                        $opt['special_categories'] = array();
                    }

                    // (Special Offer) Product list (Products to adjust)
                    if (isset($set['special_products']) && is_array($set['special_products']) && !empty($set['special_products'])) {
                        $opt['special_products'] = $set['special_products'];
                    }
                    else {
                        $opt['special_products'] = array();
                    }

                    // (Special Offer) Amount to adjust
                    if (isset($set['special_adjust']) && $set['special_adjust'] != '') {
                        $opt['special_adjust'] = (int) preg_replace('/[^0-9]/', '', $set['special_adjust']);
                    }
                    else {
                        $opt['special_adjust'] = '';
                    }

                    // (Special Offer) Adjustment type
                    $opt['special_type'] = (isset($set['special_type']) && in_array($set['special_type'], array('percentage', 'price', 'fixed'))) ? $set['special_type'] : 'percentage';

                    // (Special Offer) Adjustment value
                    if (isset($set['special_value']) && $set['special_value'] != '' && !preg_match('/,/', $set['special_value'])) {
                        $opt['special_value'] = (float) preg_replace('/[^0-9\.]/', '', $set['special_value']);
                    }
                    else {
                        $opt['special_value'] = '';
                    }

                    // (Special Offer) Repeat
                    $opt['special_repeat'] = (isset($set['special_repeat']) && $set['special_repeat']) ? 1 : 0;

                    // (Quantity Discount) PRICING TABLE
                    $is_ok = true;

                    if (isset($set['pricing']) && is_array($set['pricing']) && !empty($set['pricing'])) {
                        $rows = array();
                        $current_row_key = 1;

                        foreach ($set['pricing'] as $pricing_key => $pricing) {
                            $row = array();

                            // Min quantity
                            if (isset($pricing['min']) && !in_array($pricing['min'], array('', '*'))) {
                                $row['min'] = (int) preg_replace('/[^0-9]/', '', $pricing['min']);
                            }
                            else if ($pricing['min'] == '*') {
                                $row['min'] = '*';
                            }
                            else {
                                $row['min'] = '';
                            }

                            // Max quantity
                            if (isset($pricing['max']) && !in_array($pricing['max'], array('', '*'))) {
                                $row['max'] = (int) preg_replace('/[^0-9]/', '', $pricing['max']);
                            }
                            else if ($pricing['max'] == '*') {
                                $row['max'] = '*';
                            }
                            else {
                                $row['max'] = '';
                            }

                            // Adjustment type
                            $row['type'] = (isset($pricing['type']) && in_array($pricing['type'], array('percentage', 'price', 'fixed'))) ? $pricing['type'] : 'percentage';

                            // Value
                            if (isset($pricing['value']) && $pricing['value'] != '' && !preg_match('/,/', $pricing['value'])) {
                                $row['value'] = (float) preg_replace('/[^0-9\.]/', '', $pricing['value']);
                            }
                            else {
                                $row['value'] = '';
                            }

                            $rows[$current_row_key] = $row;
                            $current_row_key++;
                        }

                        $opt['pricing'] = $rows;
                    }
                    else {
                        $opt['pricing'] = array(
                            1 => array(
                                'min'       => '',
                                'max'       => '',
                                'type'      => '',
                                'value'     => '',
                            )
                        );
                    }

                    if (!$is_ok) {
                        $error = __('Pricing rule configuration did not pass validation. Reverted to previous state.', 'rp_wcdpd');
                        break;
                    }

                    // Save to main output array
                    $output['sets'][$current_set_key] = $opt;
                    $current_set_key++;
                }

            }
            else if ($context == 'discounts') {

                // Check if basic data structure is ok
                if (!isset($input['apply_multiple']) || !isset($input['sets']) || !is_array($input['sets']) || empty($input['sets'])) {
                    return __('Invalid data structure.', 'rp_wcdpd');
                }

                // Apply rule
                $output['apply_multiple'] = in_array($input['apply_multiple'], array('first', 'all', 'biggest')) ? $input['apply_multiple'] : 'first';

                // Iterate over sets
                $output['sets'] = array();
                $current_set_key = 1;

                foreach ($input['sets'] as $set_key => $set) {

                    $opt = array();

                    // Rule description
                    $opt['description'] = (isset($set['description']) && is_string($set['description']) && !empty($set['description'])) ? $set['description'] : '';

                    // Valid from
                    $opt['valid_from'] = (isset($set['valid_from']) && strtotime($set['valid_from'])) ? $set['valid_from'] : '';

                    // Valid until
                    $opt['valid_until'] = (isset($set['valid_until']) && strtotime($set['valid_until'])) ? $set['valid_until'] : '';

                    // Only if pricing not adjusted
                    $opt['only_if_pricing_not_adjusted'] = (isset($set['only_if_pricing_not_adjusted']) && $set['only_if_pricing_not_adjusted']) ? 1 : 0;

                    // Discount type
                    $opt['type'] = (isset($set['type']) && in_array($set['type'], array('percentage', 'price'))) ? $set['type'] : 'percentage';

                    // Value
                    if (isset($set['value']) && $set['value'] != '' && !preg_match('/,/', $set['value'])) {
                        $opt['value'] = (float) preg_replace('/[^0-9\.]/', '', $set['value']);
                    }
                    else {
                        $opt['value'] = '';
                    }

                    // CONDITIONS TABLE
                    $is_ok = true;

                    if (isset($set['conditions']) && is_array($set['conditions']) && !empty($set['conditions'])) {
                        $rows = array();
                        $current_row_key = 1;

                        foreach ($set['conditions'] as $condition_key => $condition) {
                            $row = array();

                            // Field
                            $row['key'] = (isset($condition['key']) && in_array($condition['key'], array('subtotal_bottom', 'subtotal_top', 'products', 'products_not', 'categories', 'categories_not', 'item_count_bottom', 'item_count_top', 'quantity_bottom', 'quantity_top', 'users', 'roles', 'capabilities', 'history_count', 'history_amount', 'shipping_countries'))) ? $condition['key'] : 'subtotal_bottom';

                            // Value
                            if (isset($condition['value']) && $condition['value'] != '' && !preg_match('/,/', $condition['value'])) {
                                $row['value'] = (float) preg_replace('/[^0-9\.]/', '', $condition['value']);
                            }
                            else {
                                $row['value'] = '';
                            }

                            // Products
                            if (isset($condition['products']) && is_array($condition['products']) && !empty($condition['products'])) {
                                $row['products'] = $condition['products'];
                            }
                            else {
                                $row['products'] = array();
                            }

                            // Categories
                            if (isset($condition['categories']) && is_array($condition['categories']) && !empty($condition['categories'])) {
                                $row['categories'] = $condition['categories'];
                            }
                            else {
                                $row['categories'] = array();
                            }

                            // Users
                            if (isset($condition['users']) && is_array($condition['users']) && !empty($condition['users'])) {
                                $row['users'] = $condition['users'];
                            }
                            else {
                                $row['users'] = array();
                            }

                            // Roles
                            if (isset($condition['roles']) && is_array($condition['roles']) && !empty($condition['roles'])) {
                                $row['roles'] = $condition['roles'];
                            }
                            else {
                                $row['roles'] = array();
                            }

                            // Capabilities
                            if (isset($condition['capabilities']) && is_array($condition['capabilities']) && !empty($condition['capabilities'])) {
                                $row['capabilities'] = $condition['capabilities'];
                            }
                            else {
                                $row['capabilities'] = array();
                            }

                            // Shipping countries
                            if (isset($condition['shipping_countries']) && is_array($condition['shipping_countries']) && !empty($condition['shipping_countries'])) {
                                $row['shipping_countries'] = $condition['shipping_countries'];
                            }
                            else {
                                $row['shipping_countries'] = array();
                            }

                            $rows[$current_row_key] = $row;
                            $current_row_key++;
                        }

                        $opt['conditions'] = $rows;
                    }
                    else {
                        $opt['conditions'] = array(
                            1 => array(
                                'key'                   => '',
                                'value'                 => '',
                                'products'              => array(),
                                'categories'            => array(),
                                'users'                 => array(),
                                'roles'                 => array(),
                                'capabilities'          => array(),
                                'shipping_countries'    => array(),
                            )
                        );
                    }

                    if (!$is_ok) {
                        $error = __('Pricing rule configuration did not pass validation. Reverted to previous state.', 'rp_wcdpd');
                        break;
                    }

                    // Save to main output array
                    $output['sets'][$current_set_key] = $opt;
                    $current_set_key++;
                }

            }
            else if ($context == 'settings') {

                // Cart discount title
                $output['cart_discount_title'] = (isset($input['cart_discount_title']) && !empty($input['cart_discount_title'])) ? $input['cart_discount_title'] : 'DISCOUNT';

                // Display pricing table
                $output['display_table'] = (isset($input['display_table']) && in_array($input['display_table'], array('hide', 'modal', 'inline'))) ? $input['display_table'] : 'hide';

                // Display special offers
                $output['display_offers'] = (isset($input['display_offers']) && in_array($input['display_offers'], array('hide', 'modal', 'inline'))) ? $input['display_offers'] : 'hide';

                // Display position
                $output['display_position'] = (isset($input['display_position']) && in_array($input['display_position'], array('woocommerce_before_add_to_cart_form', 'woocommerce_after_add_to_cart_form', 'woocommerce_single_product_summary', 'woocommerce_after_single_product_summary', 'woocommerce_product_meta_end', 'woocommerce_after_main_content'))) ? $input['display_position'] : 'woocommerce_before_add_to_cart_form';

                // Color scheme
                $output['pricing_table_style'] = (isset($input['pricing_table_style']) && in_array($input['pricing_table_style'], array('horizontal', 'vertical'))) ? $input['pricing_table_style'] : 'horizontal';
            }
            else if ($context == 'localization') {

                // Quantity discounts
                $output['quantity_discounts'] = (isset($input['quantity_discounts']) && is_string($input['quantity_discounts']) && !empty($input['quantity_discounts'])) ? $input['quantity_discounts'] : '';

                // Special offers
                $output['special_offers'] = (isset($input['special_offers']) && is_string($input['special_offers']) && !empty($input['special_offers'])) ? $input['special_offers'] : '';

                // Quantity
                $output['quantity'] = (isset($input['quantity']) && is_string($input['quantity']) && !empty($input['quantity'])) ? $input['quantity'] : '';

                // Price
                $output['price'] = (isset($input['price']) && is_string($input['price']) && !empty($input['price'])) ? $input['price'] : '';
            }

            return $error ? $error : $output;
        }

        /**
         * Get current settings page tab
         *
         * @access public
         * @return string
         */
        public function get_current_settings_tab()
        {
            // Check if requested settings page tab exists
            if (isset($_GET['tab']) && in_array($_GET['tab'], array_merge(array_keys($this->settings_page_tabs), array('help')))) {
                return $_GET['tab'];
            }
            else {
                $keys = array_keys($this->settings_page_tabs);
                return $keys[0];
            }
        }

        /**
         * Add settings link on plugins page
         *
         * @access public
         * @param array $links
         * @return void
         */
        public function plugins_page_links($links)
        {
            $settings_link = '<a href="http://support.rightpress.net/" target="_blank">'.__('Support', 'rp_wcdpd').'</a>';
            array_unshift($links, $settings_link);
            $settings_link = '<a href="admin.php?page=wc_pricing_and_discounts">'.__('Settings', 'rp_wcdpd').'</a>';
            array_unshift($links, $settings_link);
            return $links;
        }

        /**
         * Load scripts and styles required for admin
         *
         * @access public
         * @return void
         */
        public function enqueue_admin_scripts_and_styles()
        {
            // Our own scripts and styles
            wp_register_script('rp-wcdpd-admin-scripts', RP_WCDPD_PLUGIN_URL . '/assets/js/scripts-admin.js', array('jquery'), RP_WCDPD_VERSION);
            wp_register_style('rp-wcdpd-admin-styles', RP_WCDPD_PLUGIN_URL . '/assets/css/style-admin.css', array(), RP_WCDPD_VERSION);

            // Custom jQuery UI styles
            wp_register_style('rp-wcdpd-jquery-ui-styles', RP_WCDPD_PLUGIN_URL . '/assets/css/jquery-ui.css', array(), RP_WCDPD_VERSION);

            // Chosen scripts and styles (advanced form fields)
            wp_register_script('rp-wcdpd-jquery-chosen', RP_WCDPD_PLUGIN_URL . '/assets/js/chosen.jquery.js', array('jquery'), '1.0.0');
            wp_register_style('rp-wcdpd-jquery-chosen-css', RP_WCDPD_PLUGIN_URL . '/assets/css/chosen.min.css', array(), RP_WCDPD_VERSION);

            // Font awesome (icons)
            wp_register_style('rp-wcdpd-font-awesome', RP_WCDPD_PLUGIN_URL . '/assets/font-awesome/css/font-awesome.min.css', array(), '4.0.3');

            // Scripts
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-accordion');
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-tooltip');
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_script('rp-wcdpd-jquery-chosen');
            wp_enqueue_script('rp-wcdpd-admin-scripts');

            // Styles
            wp_enqueue_style('rp-wcdpd-font-awesome');
            wp_enqueue_style('rp-wcdpd-jquery-ui-styles');
            wp_enqueue_style('rp-wcdpd-jquery-chosen-css');
            wp_enqueue_style('rp-wcdpd-font-awesome');
            wp_enqueue_style('rp-wcdpd-admin-styles');
        }

        /**
         * Load scripts and styles required for frontend
         *
         * @access public
         * @return void
         */
        public function enqueue_frontend_scripts_and_styles()
        {
            // Our own scripts and styles
            wp_register_script('rp-wcdpd-frontend-scripts', RP_WCDPD_PLUGIN_URL . '/assets/js/scripts-frontend.js', array('jquery'), RP_WCDPD_VERSION);
            wp_register_style('rp-wcdpd-frontend-styles', RP_WCDPD_PLUGIN_URL . '/assets/css/style-frontend.css', array(), RP_WCDPD_VERSION);

            // Scripts
            wp_enqueue_script('jquery');
            wp_enqueue_script('rp-wcdpd-frontend-scripts');

            // Styles
            wp_enqueue_style('rp-wcdpd-frontend-styles');
        }

        /**
         * Get all product list for select field
         *
         * @access public
         * @return array
         */
        public function get_all_product_list()
        {
            $result = array('' => '');

            $posts_raw = get_posts(array(
                'posts_per_page'    => -1,
                'post_type'         => 'product',
                'post_status'       => array('publish', 'pending', 'draft', 'future', 'private', 'inherit'),
                'fields'            => 'ids',
            ));

            foreach ($posts_raw as $post_id) {
                $result[$post_id] = '#' . $post_id . ' ' . get_the_title($post_id);
            }

            return $result;
        }

        /**
         * Get all category list for select field
         *
         * @access public
         * @return array
         */
        public function get_all_category_list()
        {
            $result = array('' => '');

            $post_categories_raw = get_terms(array('product_cat'), array('hide_empty' => 0));
            $post_categories_raw_count = count($post_categories_raw);

            foreach ($post_categories_raw as $post_cat_key => $post_cat) {
                $category_name = $post_cat->name;

                if ($post_cat->parent) {
                    $parent_id = $post_cat->parent;
                    $has_parent = true;

                    // Make sure we don't have an infinite loop here (happens with some kind of "ghost" categories)
                    $found = false;
                    $i = 0;

                    while ($has_parent && ($i < $post_categories_raw_count || $found)) {

                        // Reset each time
                        $found = false;
                        $i = 0;

                        foreach ($post_categories_raw as $parent_post_cat_key => $parent_post_cat) {

                            $i++;

                            if ($parent_post_cat->term_id == $parent_id) {
                                $category_name = $parent_post_cat->name . ' &rarr; ' . $category_name;
                                $found = true;

                                if ($parent_post_cat->parent) {
                                    $parent_id = $parent_post_cat->parent;
                                }
                                else {
                                    $has_parent = false;
                                }

                                break;
                            }
                        }
                    }
                }

                $result[$post_cat->term_id] = $category_name;
            }

            return $result;
        }

        /**
         * Get all country list for select field
         *
         * @access public
         * @return array
         */
        public function get_all_country_list()
        {
            $countries = new WC_Countries();

            if ($countries && is_array($countries->countries)) {
                return array_merge(array('' => ''), $countries->countries);
            }
            else {
                return array('' => '');
            }
        }

        /**
         * Get all user role list for select field
         *
         * @access public
         * @return array
         */
        public function get_all_role_list()
        {
            global $wp_roles;

            if (!isset($wp_roles)) {
                $wp_roles = new WP_Roles();
            }

            return array_merge(array('' => ''), $wp_roles->get_names());
        }

        /**
         * Get all capability list for select field
         *
         * @access public
         * @return array
         */
        public function get_all_capability_list()
        {
            $capabilities = array();

            // Groups plugin active?
            if (class_exists('Groups_User') && class_exists('Groups_Wordpress') && function_exists('_groups_get_tablename')) {

                global $wpdb;
                $capability_table = _groups_get_tablename('capability');
                $all_capabilities = $wpdb->get_results('SELECT capability FROM ' . $capability_table);

                if ($all_capabilities) {
                    foreach ($all_capabilities as $capability) {
                        $capabilities[$capability->capability] = $capability->capability;
                    }
                }
            }

            // Get standard WP capabilities
            else {
                global $wp_roles;

                if (!isset($wp_roles)) {
                    get_role('administrator');
                }

                $roles = $wp_roles->roles;

                if (is_array($roles)) {
                    foreach ($roles as $rolename => $atts) {
                        if (isset($atts['capabilities']) && is_array($atts['capabilities'])) {
                            foreach ($atts['capabilities'] as $capability => $value) {
                                if (!in_array($capability, $capabilities)) {
                                    $capabilities[$capability] = $capability;
                                }
                            }
                        }
                    }
                }
            }

            return apply_filters('rp_wcdpd_capability_list', array_merge(array('' => ''), $capabilities));
        }

        /**
         * Get all user list for select field
         *
         * @access public
         * @return array
         */
        public function get_all_user_list()
        {
            $result = array('' => '');

            foreach(get_users() as $user) {
                $result[$user->ID] = '#' . $user->ID . ' ' . $user->user_email;
            }

            return $result;
        }

        /**
         * Get product categories
         *
         * @access public
         * @param array $product_id
         * @return array
         */
        public function get_product_categories($product_id)
        {
            $categories = array();
            $current_categories = wp_get_post_terms($product_id, 'product_cat');

            foreach ($current_categories as $category) {
                $categories[] = $category->term_id;
            }

            return $categories;
        }

        /**
         * Check WooCommerce version
         *
         * @access public
         * @param string $version
         * @return bool
         */
        public function wc_version_gte($version)
        {
            if (defined('WC_VERSION') && WC_VERSION) {
                return version_compare(WC_VERSION, $version, '>=');
            }
            else if (defined('WOOCOMMERCE_VERSION') && WOOCOMMERCE_VERSION) {
                return version_compare(WOOCOMMERCE_VERSION, $version, '>=');
            }
            else {
                return false;
            }
        }

    }

    $GLOBALS['RP_WCDPD'] = RP_WCDPD::get_instance();

}