<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Make sure there's no other class with the same name loaded
if (!class_exists('RP_WCDPD_Pricing')) {

    /**
     * Item Pricing adjustment calculation class
     * 
     * @class RP_WCDPD_Pricing
     * @package WooCommerce Dynamic Pricing And Discounts
     * @author RightPress
     */
    class RP_WCDPD_Pricing
    {

        /**
         * Class constructor
         * 
         * @access public
         * @param array $cart_contents
         * @param array $opt
         * @return void
         */
        public function __construct($cart_contents, $opt)
        {
            $this->items = $cart_contents;
            $this->settings = $opt['settings'];
            $this->pricing_settings = $opt['pricing'];
            $this->rules = $this->validate_rules($this->pricing_settings['sets']);

            // This array holds calculated adjustments to be applied per rule
            $this->apply = array(
                'global'    => array(),
            );

            // Track which rules have been applied
            $this->applied = array();

            // Track usage of special offer adjusted product amount by rule when adjusting other products than matched
            $this->special_offer_usage = array();

            // Validate and process all rules
            $this->prepare();
        }

        /**
         * Prepare (check conditions etc) rules to be applied
         * 
         * @access public
         * @return array
         */
        public function prepare()
        {
            /**
             * Check exclude rules and make a list of cart items to exclude
             */
            foreach ($this->pricing_settings['sets'] as $rule_key => $rule) {
                foreach ($this->items as $cart_item_key => $cart_item) {
                    if ($rule['method'] == 'exclude' && $this->exclude_rule_valid_for_cart_item($rule, $cart_item)) {
                        unset($this->items[$cart_item_key]);
                    }
                }
            }

            /**
             *  Leave only those rules which conditions are matched
             */
            $rules = array();

            foreach ($this->rules as $rule_key => $rule) {
                if ($this->rule_conditions_match($rule)) {
                    $rules[$rule_key] = $rule;
                }
            }

            /**
             * Iterate over all rules and determine which products can be adjusted by each rule (still do not touch cart items)
             */
            foreach ($rules as $rule_key => $rule) {

                // Get quantities
                $quantities = $this->count_in_cart($rule);

                /**
                 *  Quantity - Matched
                 */
                if ($rule['method'] == 'quantity' && $rule['quantity_products_to_adjust'] == 'matched' && !in_array($rule['quantities_based_on'], array('cumulative_categories', 'cumulative_all'))) {

                    // Iterate over quantities and get all adjustments that are defined in pricing table
                    $to_adjust = array();

                    foreach ($quantities as $quantity_key => $quantity) {
                        foreach ($rule['pricing'] as $row_key => $row) {
                            if (!isset($row['added']) && $row['value'] > 0 && $quantity >= $row['min'] && $quantity <= $row['max']) {
                                $to_adjust[$quantity_key] = array(
                                    'type'  => $row['type'],
                                    'value' => $row['value'],
                                );
                            }
                        }
                    }

                    // Push rule to be applied
                    if (!empty($to_adjust)) {
                        $this->apply['global'][$rule_key] = array(
                            'method'        => 'quantity',
                            'target'        => $rule['quantity_products_to_adjust'],
                            'based_on'      => $rule['quantities_based_on'],
                            'if_matched'    => $rule['if_matched'],
                            'adjustment'    => $to_adjust,
                        );
                    }

                }

                /**
                 *  Quantity - Other OR Matched but cumulative counts
                 */
                else if ($rule['method'] == 'quantity' && ($rule['quantity_products_to_adjust'] != 'matched' || in_array($rule['quantities_based_on'], array('cumulative_categories', 'cumulative_all')))) {

                    // Iterate over quantities and select max quantity that exists in pricing table
                    $current_adjustment = array('max' => 0, 'adjustment' => array());

                    foreach ($quantities as $quantity) {
                        foreach ($rule['pricing'] as $row_key => $row) {
                            if (!isset($row['added']) && $row['value'] > 0 && $quantity >= $row['min'] && $quantity <= $row['max']) {
                                if ($quantity > $current_adjustment['max']) {
                                    $current_adjustment = array(
                                        'max'           => $quantity,
                                        'adjustment'    => array(
                                            'type'  => $row['type'],
                                            'value' => $row['value'],
                                        ),
                                    );
                                }
                            }
                        }
                    }

                    $current_adjustment = $current_adjustment['adjustment'];

                    // Push rule to be applied
                    if (!empty($current_adjustment)) {
                        $this->apply['global'][$rule_key] = array(
                            'method'        => 'quantity',
                            'target'        => $rule['quantity_products_to_adjust'],
                            'if_matched'    => $rule['if_matched'],
                            'adjustment'    => $current_adjustment,
                        );

                        if (in_array($rule['quantities_based_on'], array('cumulative_categories', 'cumulative_all')) && $rule['quantity_products_to_adjust'] == 'matched') {
                            $this->apply['global'][$rule_key]['selection_method'] = $rule['selection_method'];
                            $this->apply['global'][$rule_key]['target_list'] = (in_array($rule['selection_method'], array('categories_include', 'categories_exclude')) ? $rule['categories'] : $rule['products']);
                        }
                        else {
                            $this->apply['global'][$rule_key]['target_type'] = $rule['quantity_products_to_adjust'];
                            $this->apply['global'][$rule_key]['target_list'] = (($rule['quantity_products_to_adjust'] == 'other_categories') ? $rule['quantity_categories'] : $rule['quantity_products']);
                        }
                    }

                }

                /**
                 *  Special - Matched
                 */
                else if ($rule['method'] == 'special' && $rule['special_products_to_adjust'] == 'matched' && !in_array($rule['quantities_based_on'], array('cumulative_categories', 'cumulative_all'))) {

                    $amounts_to_adjust = array();

                    // Track each matched item separately
                    foreach ($quantities as $quantity_key => $quantity) {

                        // Get max amount to adjust
                        $amount_to_adjust = 0;

                        $i = $rule['special_purchase'];
                        $stop_on_next = false;

                        // Iterate until we run out of items
                        while (($quantity - $i) > 0 && !$stop_on_next) {
                            $current_count_to_adjust = (($quantity - $i) < $rule['special_adjust']) ? ($quantity - $i) : $rule['special_adjust'];
                            $amount_to_adjust += $current_count_to_adjust;
                            $i += ($rule['special_purchase'] + $current_count_to_adjust);
                            $stop_on_next = $rule['special_repeat'] ? false : true;
                        }

                        $amounts_to_adjust[$quantity_key] = $amount_to_adjust;
                    }

                    // Push rule to be applied
                    $this->apply['global'][$rule_key] = array(
                        'method'        => 'special',
                        'target'        => $rule['special_products_to_adjust'],
                        'limit'         => $amounts_to_adjust,
                        'based_on'      => $rule['quantities_based_on'],
                        'if_matched'    => $rule['if_matched'],
                        'adjustment'    => array(
                            'type'  => $rule['special_type'],
                            'value' => $rule['special_value'],
                        ),
                    );

                }

                /**
                 *  Special - Other OR Matched but cumulative counts
                 */
                else if ($rule['method'] == 'special' && ($rule['special_products_to_adjust'] != 'matched' || in_array($rule['quantities_based_on'], array('cumulative_categories', 'cumulative_all')))) {

                    // Select max quantity
                    $quantity = max($quantities);

                    // Get max amount to adjust
                    $amount_to_adjust = 0;

                    $i = $rule['special_purchase'] + ($rule['special_products_to_adjust'] == 'matched' ? $rule['special_adjust'] : 0);
                    $stop_on_next = false;

                    while (($quantity - $i) >= 0 && !$stop_on_next) {
                        $amount_to_adjust += $rule['special_adjust'];
                        $i += $rule['special_purchase'] + ($rule['special_products_to_adjust'] == 'matched' ? $rule['special_adjust'] : 0);
                        $stop_on_next = $rule['special_repeat'] ? false : true;
                    }

                    // Push rule to be applied
                    $this->apply['global'][$rule_key] = array(
                        'method'        => 'special',
                        'target'        => $rule['special_products_to_adjust'],
                        'limit'         => $amount_to_adjust,
                        'if_matched'    => $rule['if_matched'],
                        'adjustment'    => array(
                            'type'  => $rule['special_type'],
                            'value' => $rule['special_value'],
                        ),
                    );

                    if (in_array($rule['quantities_based_on'], array('cumulative_categories', 'cumulative_all')) && $rule['special_products_to_adjust'] == 'matched') {
                        $this->apply['global'][$rule_key]['selection_method'] = $rule['selection_method'];
                        $this->apply['global'][$rule_key]['target_list'] = (in_array($rule['selection_method'], array('categories_include', 'categories_exclude')) ? $rule['categories'] : $rule['products']);
                    }
                    else {
                        $this->apply['global'][$rule_key]['target_type'] = $rule['special_products_to_adjust'];
                        $this->apply['global'][$rule_key]['target_list'] = (($rule['special_products_to_adjust'] == 'other_categories') ? $rule['special_categories'] : $rule['special_products']);
                    }
                }

            }

            /**
             * Remove rules that cannot be applied to current cart
             */
            foreach ($this->apply['global'] as $rule_key => $apply_configuration) {
                $can_be_applied = false;

                foreach ($this->items as $cart_item_key => $cart_item) {
                    if ($this->apply_rule_to_item($rule_key, $apply_configuration, $cart_item_key, $cart_item, true) !== false) {
                        $can_be_applied = true;
                        break;
                    }
                }

                if (!$can_be_applied) {
                    unset($this->apply['global'][$rule_key]);
                }
            }

            /**
             * Maybe leave the first rule that can be effectively applied because of "Apply first matched rule" setting
             */
            if ($this->pricing_settings['apply_multiple'] == 'first') {
                foreach ($this->apply['global'] as $rule_key => $apply_configuration) {
                    $this->apply['global'] = array($rule_key => $apply_configuration);
                    break;
                }
            }

            /**
             * Maybe filter out some rules because of "If conditions are matched" setting
             */
            foreach ($this->apply['global'] as $rule_key => $apply_configuration) {
                if ($apply_configuration['if_matched'] == 'this') {
                    $this->apply['global'] = array($rule_key => $apply_configuration);
                    break;
                }
            }
        }

        /**
         * Get calculated pricing adjustments for requested cart line item
         * 
         * @access public
         * @param string $cart_item_key
         * @return array
         */
        public function get($cart_item_key)
        {
            if (!isset($this->items[$cart_item_key])) {
                return false;
            }

            $price = $this->items[$cart_item_key]['data']->price;
            $original_price = $price;

            if (in_array($this->pricing_settings['apply_multiple'], array('all', 'first'))) {
                foreach ($this->apply['global'] as $rule_key => $apply) {
                    if ($deduction = $this->apply_rule_to_item($rule_key, $apply, $cart_item_key, $this->items[$cart_item_key], false, $price)) {

                        if ($apply['if_matched'] == 'other' && isset($this->applied) && isset($this->applied['global'])) {
                            if (count($this->applied['global']) > 1 || !isset($this->applied['global'][$rule_key])) {
                                continue;
                            }
                        }

                        $this->applied['global'][$rule_key] = 1;
                        $price = $price - $deduction;
                    }
                }
            }
            else if ($this->pricing_settings['apply_multiple'] == 'biggest') {

                $price_deductions = array();

                foreach ($this->apply['global'] as $rule_key => $apply) {

                    if ($apply['if_matched'] == 'other' && isset($this->applied) && isset($this->applied['global'])) {
                        if (count($this->applied['global']) > 1 || !isset($this->applied['global'][$rule_key])) {
                            continue;
                        }
                    }

                    if ($deduction = $this->apply_rule_to_item($rule_key, $apply, $cart_item_key, $this->items[$cart_item_key], false)) {
                        $price_deductions[$rule_key] = $deduction;
                    }
                }

                if (!empty($price_deductions)) {
                    $max_deduction = max($price_deductions);
                    $rule_key = array_search($max_deduction, $price_deductions);
                    $this->applied['global'][$rule_key] = 1;
                    $price = $price - $max_deduction;
                }

            }

            // Make sure price is not negative
            $price = ($price < 0) ? 0 : $price;

            if ($price != $original_price) {
                return array(
                    'price' => $price,
                    'log'   => array(),
                );
            }
            else {
                return false;
            }

        }

        /**
         * Check if exclude rule is valid and its conditions matches current item
         * 
         * @access public
         * @param array $rule
         * @param array $cart_item
         * @return bool
         */
        public function exclude_rule_valid_for_cart_item($rule, $cart_item)
        {
            // Valid from
            if (isset($rule['valid_from']) && !empty($rule['valid_from']) && (strtotime($rule['valid_from'] . ' 00:00:00') > time())) {
                return false;
            }

            // Valid until
            if (isset($rule['valid_until']) && !empty($rule['valid_until']) && (strtotime($rule['valid_until'] . ' 23:59:59') < time())) {
                return false;
            }

            // Apply to
            if ($rule['selection_method'] == 'categories_include' && count(array_intersect($this->get_cart_item_categories($cart_item), $rule['categories'])) == 0) {
                return false;
            }
            else if ($rule['selection_method'] == 'categories_exclude' && count(array_intersect($this->get_cart_item_categories($cart_item), $rule['categories'])) > 0) {
                return false;
            }
            else if ($rule['selection_method'] == 'products_include' && !in_array($cart_item['data']->id, $rule['products'])) {
                return false;
            }
            else if ($rule['selection_method'] == 'products_exclude' && in_array($cart_item['data']->id, $rule['products'])) {
                return false;
            }

            // Customers
            if ($rule['user_method'] == 'roles_include') {
                if (count(array_intersect(RP_WCDPD::current_user_roles(), $rule['roles'])) < 1) {
                    return false;
                }
            }
            if ($rule['user_method'] == 'roles_exclude') {
                if (count(array_intersect(RP_WCDPD::current_user_roles(), $rule['roles'])) > 0) {
                    return false;
                }
            }
            if ($rule['user_method'] == 'capabilities_include') {
                if (count(array_intersect(RP_WCDPD::current_user_capabilities(), $rule['capabilities'])) < 1) {
                    return false;
                }
            }
            if ($rule['user_method'] == 'capabilities_exclude') {
                if (count(array_intersect(RP_WCDPD::current_user_capabilities(), $rule['capabilities'])) > 0) {
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
         * Apply selected rule to selected cart item
         * 
         * @access public
         * @param int $rule_key
         * @param array $apply
         * @param string $cart_item_key
         * @param array $cart_item
         * @param bool $is_test
         * @param float $price
         * @return mixed
         */
        public function apply_rule_to_item($rule_key, $apply, $cart_item_key, $cart_item, $is_test = false, $price = null)
        {
            // Determine price to use
            if ($price === null) {
                $price = $cart_item['data']->price;
            }

            // Quantity - Matched
            if ($apply['method'] == 'quantity' && (!isset($apply['target_list']) || empty($apply['target_list'])) && $apply['target'] == 'matched' && in_array($apply['based_on'], array('exclusive_product', 'exclusive_variation', 'exclusive_configuration'))) {
                $id = $this->get_quantity_identifier($apply['based_on'], $cart_item_key, $cart_item);
                if (isset($apply['adjustment'][$id])) {
                    return self::apply_adjustment($price, $apply['adjustment'][$id]);
                }
            }

            // Quantity - Matched Cumulative - All
            else if ($apply['method'] == 'quantity' && isset($apply['selection_method']) && $apply['target'] == 'matched' && $apply['selection_method'] == 'all') {
                return self::apply_adjustment($price, $apply['adjustment']);
            }

            // Quantity - Matched Cumulative - Specific Products / Categories
            else if ($apply['method'] == 'quantity' && isset($apply['selection_method']) && $apply['target'] == 'matched') {
                if ($apply['selection_method'] == 'categories_include') {
                    if (count(array_intersect($this->get_cart_item_categories($cart_item), $apply['target_list'])) > 0) {
                        return self::apply_adjustment($price, $apply['adjustment']);
                    }
                }
                else if ($apply['selection_method'] == 'categories_exclude') {
                    if (count(array_intersect($this->get_cart_item_categories($cart_item), $apply['target_list'])) == 0) {
                        return self::apply_adjustment($price, $apply['adjustment']);
                    }
                }
                else if ($apply['selection_method'] == 'products_include') {
                    if (in_array($cart_item['data']->id, $apply['target_list'])) {
                        return self::apply_adjustment($price, $apply['adjustment']);
                    }
                }
                else if ($apply['selection_method'] == 'products_exclude') {
                    if (!in_array($cart_item['data']->id, $apply['target_list'])) {
                        return self::apply_adjustment($price, $apply['adjustment']);
                    }
                }
            }

            // Quantity - Other
            else if ($apply['method'] == 'quantity' && isset($apply['target_type'])) {
                if ($apply['target_type'] == 'other_categories') {
                    if (count(array_intersect($this->get_cart_item_categories($cart_item), $apply['target_list'])) > 0) {
                        return self::apply_adjustment($price, $apply['adjustment']);
                    }
                }
                else if ($apply['target_type'] == 'other_products') {
                    if (in_array($cart_item['data']->id, $apply['target_list'])) {
                        return self::apply_adjustment($price, $apply['adjustment']);
                    }
                }
            }

            // Special - Matched
            else if ($apply['method'] == 'special' && (!isset($apply['target_list']) || empty($apply['target_list'])) && $apply['target'] == 'matched' && in_array($apply['based_on'], array('exclusive_product', 'exclusive_variation', 'exclusive_configuration'))) {
                $id = $this->get_quantity_identifier($apply['based_on'], $cart_item_key, $cart_item);
                if (isset($apply['limit'][$id])) {

                    if (!$is_test) {

                        // Track usage
                        if (!isset($this->special_offer_usage[$rule_key][$id])) {
                            $this->special_offer_usage[$rule_key][$id] = $apply['limit'][$id];
                        }

                        // Process all count units
                        $all_adjustments = 0;
                        $item_quantity = $cart_item['quantity'];

                        while ($this->special_offer_usage[$rule_key][$id] > 0 && $item_quantity > 0) {
                            $all_adjustments += self::apply_adjustment($price, $apply['adjustment']);
                            $this->special_offer_usage[$rule_key][$id]--;
                            $item_quantity--;
                        }

                        return ($all_adjustments / $cart_item['quantity']);

                    }
                    else {
                        return self::apply_adjustment($price, $apply['adjustment']);
                    }
                }
            }

            // Special - Matched Cumulative - Specific Products / Categories
            else if ($apply['method'] == 'special' && isset($apply['selection_method']) && $apply['target'] == 'matched') {
                $proceed = false;

                if ($apply['selection_method'] == 'all') {
                    $proceed = true;
                }
                else if ($apply['selection_method'] == 'categories_include') {
                    if (count(array_intersect($this->get_cart_item_categories($cart_item), $apply['target_list'])) > 0) {
                        $proceed = true;
                    }
                }
                else if ($apply['selection_method'] == 'categories_exclude') {
                    if (count(array_intersect($this->get_cart_item_categories($cart_item), $apply['target_list'])) == 0) {
                        $proceed = true;
                    }
                }
                else if ($apply['selection_method'] == 'products_include') {
                    if (in_array($cart_item['data']->id, $apply['target_list'])) {
                        $proceed = true;
                    }
                }
                else if ($apply['selection_method'] == 'products_exclude') {
                    if (!in_array($cart_item['data']->id, $apply['target_list'])) {
                        $proceed = true;
                    }
                }

                if ($proceed) {

                    if (!$is_test) {

                        // Track usage
                        if (!isset($this->special_offer_usage[$rule_key])) {
                            $this->special_offer_usage[$rule_key] = $apply['limit'];
                        }

                        // Process all count units
                        $all_adjustments = 0;
                        $item_quantity = $cart_item['quantity'];

                        while ($this->special_offer_usage[$rule_key] > 0 && $item_quantity > 0) {
                            $all_adjustments += self::apply_adjustment($price, $apply['adjustment']);
                            $this->special_offer_usage[$rule_key]--;
                            $item_quantity--;
                        }

                        return ($all_adjustments / $cart_item['quantity']);

                    }
                    else {
                        return self::apply_adjustment($price, $apply['adjustment']);
                    }

                }
            }

            // Special - Other
            else if ($apply['method'] == 'special' && isset($apply['target_type'])) {
                $proceed = false;

                if ($apply['target_type'] == 'other_categories') {
                    if (count(array_intersect($this->get_cart_item_categories($cart_item), $apply['target_list'])) > 0) {
                        $proceed = true;
                    }
                }
                else if ($apply['target_type'] == 'other_products') {
                    if (in_array($cart_item['data']->id, $apply['target_list'])) {
                        $proceed = true;
                    }
                }

                if ($proceed) {

                    if (!$is_test) {

                        // Track usage
                        if (!isset($this->special_offer_usage[$rule_key])) {
                            $this->special_offer_usage[$rule_key] = $apply['limit'];
                        }

                        // Process all count units
                        $all_adjustments = 0;
                        $item_quantity = $cart_item['quantity'];

                        while ($this->special_offer_usage[$rule_key] > 0 && $item_quantity > 0) {
                            $all_adjustments += self::apply_adjustment($price, $apply['adjustment']);
                            $this->special_offer_usage[$rule_key]--;
                            $item_quantity--;
                        }

                        return ($all_adjustments / $cart_item['quantity']);

                    }
                    else {
                        return self::apply_adjustment($price, $apply['adjustment']);
                    }

                }
            }

            return false;
        }

        /**
         * Actually modify provided price
         * 
         * @access public
         * @param float $price
         * @param array $adjustment
         * @return float
         */
        public static function apply_adjustment($price, $adjustment)
        {
            $price = ($price < 0) ? 0 : $price;

            if ($adjustment['type'] == 'percentage') {
                $discount = $price * ($adjustment['value'] / 100);
            }
            else if ($adjustment['type'] == 'price') {
                $discount = $adjustment['value'];
            }
            else if ($adjustment['type'] == 'fixed') {
                $discount = $price - $adjustment['value'];
            }

            // Round immediately after adjustment to avoid wrong totals (rounding half down because this is a discount, not the final amount)
            // Revert to round($discount, 2, PHP_ROUND_HALF_DOWN) after PHP 5.2 usage drops to a minimum
            $discount = ceil($discount * pow(10, get_option('woocommerce_price_num_decimals')) - 0.5) * pow(10, -((int) get_option('woocommerce_price_num_decimals')));

            return ($discount < 0) ? 0 : $discount;
        }

        /**
         * Validate pricing rules
         * 
         * @access public
         * @param array $pricing_rules
         * @return array
         */
        public function validate_rules($pricing_rules)
        {
            $rules = array();

            foreach ($pricing_rules as $rule_key => $rule) {
                if ($validated_rule = self::validate_rule($rule)) {
                    $rules[$rule_key] = $validated_rule;
                }
            }

            return $rules;
        }

        /**
         * Validate, sanitize and return single pricing rule
         * 
         * @access public
         * @param array $pricing_rule
         * @return mixed
         */
        public static function validate_rule($pricing_rule)
        {
            // Method
            if (!isset($pricing_rule['method']) || !in_array($pricing_rule['method'], array('quantity', 'special'))) {
                return false;
            }

            // Quantities based on
            if (!isset($pricing_rule['quantities_based_on']) || !in_array($pricing_rule['quantities_based_on'], array('exclusive_product', 'exclusive_variation', 'exclusive_configuration', 'cumulative_categories', 'cumulative_all'))) {
                return false;
            }

            // If conditions are matched
            if (!isset($pricing_rule['if_matched']) || !in_array($pricing_rule['if_matched'], array('all', 'this', 'other'))) {
                return false;
            }

            // Valid from
            if (isset($pricing_rule['valid_from']) && !empty($pricing_rule['valid_from']) && (strtotime($pricing_rule['valid_from'] . ' 00:00:00') > time())) {
                return false;
            }

            // Valid until
            if (isset($pricing_rule['valid_until']) && !empty($pricing_rule['valid_until']) && (strtotime($pricing_rule['valid_until'] . ' 23:59:59') < time())) {
                return false;
            }

            // "Apply to" and related lists of items
            if (!isset($pricing_rule['selection_method']) || !in_array($pricing_rule['selection_method'], array('all', 'categories_include', 'categories_exclude', 'products_include', 'products_exclude'))) {
                return false;
            }
            else if (in_array($pricing_rule['selection_method'], array('categories_include', 'categories_exclude')) && (!is_array($pricing_rule['categories']) || empty($pricing_rule['categories']))) {
                return false;
            }
            else if (in_array($pricing_rule['selection_method'], array('products_include', 'products_exclude')) && (!is_array($pricing_rule['products']) || empty($pricing_rule['products']))) {
                return false;
            }

            // "Customers" and related lists of items
            if (!isset($pricing_rule['user_method']) || !in_array($pricing_rule['user_method'], array('all', 'roles_include', 'roles_exclude', 'capabilities_include', 'capabilities_exclude', 'users_include', 'users_exclude'))) {
                return false;
            }
            else if (in_array($pricing_rule['user_method'], array('roles_include', 'roles_exclude')) && (!is_array($pricing_rule['roles']) || empty($pricing_rule['roles']))) {
                return false;
            }
            else if (in_array($pricing_rule['user_method'], array('capabilities_include', 'capabilities_exclude')) && (!is_array($pricing_rule['capabilities']) || empty($pricing_rule['capabilities']))) {
                return false;
            }
            else if (in_array($pricing_rule['user_method'], array('users_include', 'users_exclude')) && (!is_array($pricing_rule['users']) || empty($pricing_rule['users']))) {
                return false;
            }

            // QUANTITY DISCOUNT
            if ($pricing_rule['method'] == 'quantity') {

                // Validate and normalize pricing table
                if (!($pricing_rule['pricing'] = RP_WCDPD::normalize_quantity_pricing_table($pricing_rule['pricing']))) {
                    return false;
                }

                // Products to adjust
                if (!isset($pricing_rule['quantity_products_to_adjust']) || !in_array($pricing_rule['quantity_products_to_adjust'], array('matched', 'other_categories', 'other_products'))) {
                    return false;
                }
                else if (($pricing_rule['quantity_products_to_adjust'] == 'other_categories') && (!is_array($pricing_rule['quantity_categories']) || empty($pricing_rule['quantity_categories']))) {
                    return false;
                }
                else if (($pricing_rule['quantity_products_to_adjust'] == 'other_products') && (!is_array($pricing_rule['quantity_products']) || empty($pricing_rule['quantity_products']))) {
                    return false;
                }

            }

            // SPECIAL OFFER
            else {

                // Amount to purchase
                if (!is_numeric($pricing_rule['special_purchase']) || $pricing_rule['special_purchase'] < 1) {
                    return false;
                }

                // Products to adjust
                if (!isset($pricing_rule['special_products_to_adjust']) || !in_array($pricing_rule['special_products_to_adjust'], array('matched', 'other_categories', 'other_products'))) {
                    return false;
                }
                else if (($pricing_rule['special_products_to_adjust'] == 'other_categories') && (!is_array($pricing_rule['special_categories']) || empty($pricing_rule['special_categories']))) {
                    return false;
                }
                else if (($pricing_rule['special_products_to_adjust'] == 'other_products') && (!is_array($pricing_rule['special_products']) || empty($pricing_rule['special_products']))) {
                    return false;
                }

                // Amount to adjust
                if (!is_numeric($pricing_rule['special_adjust']) || $pricing_rule['special_adjust'] < 1) {
                    return false;
                }

                // Adjustment type
                if (!isset($pricing_rule['special_type']) || !in_array($pricing_rule['special_type'], array('percentage', 'price', 'fixed'))) {
                    return false;
                }

                // Adjustment value
                if (!is_numeric($pricing_rule['special_value'])) {
                    return false;
                }
                else if ($pricing_rule['special_type'] == 'percentage' && ($pricing_rule['special_value'] < 0 || $pricing_rule['special_value'] > 100)) {
                    return false;
                }
                else if (in_array($pricing_rule['special_type'], array('price', 'fixed')) && $pricing_rule['special_value'] < 0) {
                    return false;
                }

                // Repeat
                if (!isset($pricing_rule['special_repeat']) || !in_array($pricing_rule['special_repeat'], array(0, 1))) {
                    return false;
                }

            }

            return $pricing_rule;
        }

        /**
         * Check if conditions of a rule are matched
         * 
         * @access public
         * @param array $rule
         * @return bool
         */
        public function rule_conditions_match($rule)
        {
           // Customers
            if ($rule['user_method'] == 'roles_include') {
                if (count(array_intersect(RP_WCDPD::current_user_roles(), $rule['roles'])) < 1) {
                    return false;
                }
            }
            if ($rule['user_method'] == 'roles_exclude') {
                if (count(array_intersect(RP_WCDPD::current_user_roles(), $rule['roles'])) > 0) {
                    return false;
                }
            }
            if ($rule['user_method'] == 'capabilities_include') {
                if (count(array_intersect(RP_WCDPD::current_user_capabilities(), $rule['capabilities'])) < 1) {
                    return false;
                }
            }
            if ($rule['user_method'] == 'capabilities_exclude') {
                if (count(array_intersect(RP_WCDPD::current_user_capabilities(), $rule['capabilities'])) > 0) {
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

            // Get counts of items/products/categories depending on "Quantities based on" setting
            $quantities = $this->count_in_cart($rule);

            // Check if quantity discount quantities are matched
            if ($rule['method'] == 'quantity') {

                $is_ok = false;

                foreach ($quantities as $quantity_key => $quantity) {
                    foreach ($rule['pricing'] as $row_key => $row) {
                        if (!isset($row['added']) && $quantity >= $row['min'] && $quantity <= $row['max']) {
                            $is_ok = true;
                            break;
                        }
                    }
                }

                if (!$is_ok) {
                    return false;
                }
            }

            // Check if special offer quantities are matched
            else {
                $is_ok = false;

                foreach ($quantities as $quantity_key => $quantity) {
                    if ($quantity >= $rule['special_purchase']) {
                        $is_ok = true;
                        break;
                    }
                }

                if (!$is_ok) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Count in cart
         * 
         * @access public
         * @param array $rule
         * @return int
         */
        public function count_in_cart($rule)
        {
            // Generate list of allowed cart items
            $allowed_cart_items = array();

            // Iterate over all cart items and select those that match conditions
            foreach ($this->items as $cart_item_key => $cart_item) {
                if ($rule['selection_method'] == 'all') {
                    $allowed_cart_items[$cart_item_key] = $cart_item;
                }
                else if ($rule['selection_method'] == 'categories_include') {
                    $categories = $this->get_cart_item_categories($cart_item);

                    if (count(array_intersect($categories, $rule['categories'])) > 0) {
                        $allowed_cart_items[$cart_item_key] = $cart_item;
                    }
                }
                else if ($rule['selection_method'] == 'categories_exclude') {
                    $categories = $this->get_cart_item_categories($cart_item);

                    if (count(array_intersect($categories, $rule['categories'])) == 0) {
                        $allowed_cart_items[$cart_item_key] = $cart_item;
                    }
                }
                else if ($rule['selection_method'] == 'products_include') {
                    if (in_array($cart_item['data']->id, $rule['products'])) {
                        $allowed_cart_items[$cart_item_key] = $cart_item;
                    }
                }
                else if ($rule['selection_method'] == 'products_exclude') {
                    if (!in_array($cart_item['data']->id, $rule['products'])) {
                        $allowed_cart_items[$cart_item_key] = $cart_item;
                    }
                }
            }

            // Store counts
            $counts = array();

            // Proceed depending on context ("Quantities based on")
            switch ($rule['quantities_based_on']) {

                /**
                 *  Exclusive - Product
                 */
                case 'exclusive_product':

                    foreach ($allowed_cart_items as $cart_item_key => $cart_item) {
                        if (isset($counts[$cart_item['data']->id])) {
                            $counts[$cart_item['data']->id] += $cart_item['quantity'];
                        }
                        else {
                            $counts[$cart_item['data']->id] = $cart_item['quantity'];
                        }
                    }

                    break;

                /**
                 *  Exclusive - Variation
                 */
                case 'exclusive_variation':

                    foreach ($allowed_cart_items as $cart_item_key => $cart_item) {
                        if (isset($cart_item['variation_id']) && !empty($cart_item['variation_id'])) {
                            if (isset($counts[$cart_item['variation_id']])) {
                                $counts[$cart_item['variation_id']] += $cart_item['quantity'];
                            }
                            else {
                                $counts[$cart_item['variation_id']] = $cart_item['quantity'];
                            }
                        }
                        else {
                            if (isset($counts[$cart_item['data']->id])) {
                                $counts[$cart_item['data']->id] += $cart_item['quantity'];
                            }
                            else {
                                $counts[$cart_item['data']->id] = $cart_item['quantity'];
                            }
                        }
                    }

                    break;

                /**
                 *  Exclusive - Line Item
                 */
                case 'exclusive_configuration':

                    foreach ($allowed_cart_items as $cart_item_key => $cart_item) {
                        if (isset($counts[$cart_item_key])) {
                            $counts[$cart_item_key] += $cart_item['quantity'];
                        }
                        else {
                            $counts[$cart_item_key] = $cart_item['quantity'];
                        }
                    }

                    break;

                /**
                 *  Cumulative - By Category
                 */
                case 'cumulative_categories':

                    foreach ($allowed_cart_items as $cart_item_key => $cart_item) {
                        $categories = $this->get_cart_item_categories($cart_item);

                        foreach ($categories as $category_id) {
                            if (isset($counts[$category_id])) {
                                $counts[$category_id] += $cart_item['quantity'];
                            }
                            else {
                                $counts[$category_id] = $cart_item['quantity'];
                            }
                        }
                    }

                    break;

                /**
                 *  Cumulative - All
                 */
                case 'cumulative_all':

                    foreach ($allowed_cart_items as $cart_item_key => $cart_item) {
                        if (isset($counts['all'])) {
                            $counts['all'] += $cart_item['quantity'];
                        }
                        else {
                            $counts['all'] = $cart_item['quantity'];
                        }
                    }

                    break;

                default:
                    break;
            }

            return $counts;
        }

        /**
         * Get cart item categories
         * 
         * @access public
         * @param array $cart_item
         * @return array
         */
        public function get_cart_item_categories($cart_item)
        {
            $categories = array();
            $current_categories = wp_get_post_terms($cart_item['data']->id, 'product_cat');

            foreach ($current_categories as $category) {
                $categories[] = $category->term_id;
            }

            return $categories;
        }

        /**
         * Get correct quantity identifier key
         * 
         * @access public
         * @param string $based_on
         * @param string $cart_item_key
         * @param array $cart_item
         * @return mixed
         */
        public function get_quantity_identifier($based_on, $cart_item_key, $cart_item)
        {
            $id = false;

            switch ($based_on) {
                case 'exclusive_product':
                    $id = $cart_item['data']->id;
                    break;
                case 'exclusive_variation':
                    $id = (isset($cart_item['variation_id']) && !empty($cart_item['variation_id'])) ? $cart_item['variation_id'] : $cart_item['data']->id;
                    break;
                case 'exclusive_configuration':
                    $id = $cart_item_key;
                    break;
                default:
                    break;
            }

            return $id;
        }

    }
}
