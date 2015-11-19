<?php

/**
 * View for Cart Discounts tab
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="rp_wcdpd_wrapper">
    <div class="rp_wcdpd_container">
        <div class="rp_wcdpd_left">
            <form method="post" action="options.php" enctype="multipart/form-data">

                <?php settings_fields('rp_wcdpd_opt_group_discounts'); ?>
                <input type="hidden" name="rp_wcdpd_options[current_tab]" value="<?php echo $current_tab; ?>" />

                <h3><?php _e('Manage Cart Discounts', 'rp_wcdpd'); ?></h3>

                <div class="rp_wcdpd_settings_right">
                    <select id="rp_wcdpd_apply_multiple" name="rp_wcdpd_options[discounts][apply_multiple]" class="rp_wcdpd_apply_multiple_field">
                        <option value="first" <?php echo ($this->opt['discounts']['apply_multiple'] == 'first' ? 'selected="selected"' : ''); ?>><?php _e('Apply first matched rule', 'rp_wcdpd'); ?></option>
                        <option value="all" <?php echo ($this->opt['discounts']['apply_multiple'] == 'all' ? 'selected="selected"' : ''); ?>><?php _e('Apply all matched rules', 'rp_wcdpd'); ?></option>
                        <option value="biggest" <?php echo ($this->opt['discounts']['apply_multiple'] == 'biggest' ? 'selected="selected"' : ''); ?>><?php _e('Apply biggest discount', 'rp_wcdpd'); ?></option>
                    </select>
                </div>
                <div style="clear: both;"></div>

                <div class="rp_wcdpd_sets">
                    <div id="rp_wcdpd_set_list">

                    <?php foreach ($this->opt['discounts']['sets'] as $rule_key => $rule): ?>

                        <div id="rp_wcdpd_set_<?php echo $rule_key; ?>">
                            <h4 class="rp_wcdpd_sets_handle"><span class="rp_wcdpd_sets_title" id="rp_wcdpd_sets_title_<?php echo $rule_key; ?>"><?php _e('Discount Rule #', 'rp_wcdpd'); ?><?php echo $rule_key; ?></span>&nbsp;<span class="rp_wcdpd_sets_title_name"><?php echo (!empty($rule['title'])) ? '- ' . $rule['title'] : ''; ?></span><span class="rp_wcdpd_sets_remove" id="rp_wcdpd_sets_remove_<?php echo $rule_key; ?>" title="<?php _e('Remove', 'rp_wcdpd'); ?>"><i class="fa fa-times"></i></span></h4>
                            <div style="clear:both;">

                                <div class="rp_wcdpd_sets_section"><?php _e('General Settings', 'rp_wcdpd'); ?></div>
                                <table class="form-table"><tbody>

                                    <!-- Rule description -->
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Rule description', 'rp_wcdpd'); ?></th>
                                        <td>
                                            <input type="text" id="rp_wcdpd_discounts_description_<?php echo $rule_key; ?>" name="rp_wcdpd_options[discounts][sets][<?php echo $rule_key; ?>][description]" class="rp_wcdpd_field rp_wcdpd_description_field" value="<?php echo $rule['description']; ?>" placeholder="<?php _e('e.g. additional 5% discount for frequent buyers', 'rp_wcdpd'); ?>">
                                        </td>
                                    </tr>

                                    <!-- Valid from/until -->
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Valid from/until', 'rp_wcdpd'); ?></th>
                                        <td>
                                            <input type="text" id="rp_wcdpd_discounts_valid_from_<?php echo $rule_key; ?>" name="rp_wcdpd_options[discounts][sets][<?php echo $rule_key; ?>][valid_from]" value="<?php echo $rule['valid_from']; ?>" class="rp_wcdpd_date_field rp_wcdpd_discounts_valid_from_field" placeholder="<?php _e('Select date from...', 'rp_wcdpd'); ?>">
                                            <input type="text" id="rp_wcdpd_discounts_valid_until_<?php echo $rule_key; ?>" name="rp_wcdpd_options[discounts][sets][<?php echo $rule_key; ?>][valid_until]" value="<?php echo $rule['valid_until']; ?>" class="rp_wcdpd_date_field rp_wcdpd_discounts_valid_until_field" placeholder="<?php _e('Select date until...', 'rp_wcdpd'); ?>">
                                        </td>
                                    </tr>

                                    <!-- Only apply if no Pricing Rules were applied -->
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Only if pricing not adjusted', 'rp_wcdpd'); ?></th>
                                        <td>
                                            <input type="checkbox" id="rp_wcdpd_discounts_only_if_pricing_not_adjusted_<?php echo $rule_key; ?>" name="rp_wcdpd_options[discounts][sets][<?php echo $rule_key; ?>][only_if_pricing_not_adjusted]" class="rp_wcdpd_field rp_wcdpd_discounts_only_if_pricing_not_adjusted_field" <?php echo ($rule['only_if_pricing_not_adjusted'] ? 'checked="checked"' : ''); ?>>
                                        </td>
                                    </tr>

                                </tbody></table>

                                <div class="rp_wcdpd_sets_section rp_wcdpd_sets_section_discounts_conditions"><?php _e('Conditions', 'rp_wcdpd'); ?></div>

                                <!-- Rules table -->
                                <table id="rp_wcdpd_items_table_<?php echo $rule_key; ?>" class="form-table rp_wcdpd_items_table rp_wcdpd_conditions_table">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Field', 'rp_wcdpd'); ?></th>
                                            <th><?php _e('Value', 'rp_wcdpd'); ?></th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        <?php foreach($rule['conditions'] as $condition_key => $condition): ?>

                                            <tr class="rp_wcdpd_items_row" id="rp_wcdpd_items_row_<?php echo $rule_key; ?>_<?php echo $condition_key; ?>">
                                                <td class="rp_wcdpd_conditions_column">
                                                    <select id="rp_wcdpd_discounts_conditions_key_<?php echo $rule_key; ?>_<?php echo $condition_key; ?>" name="rp_wcdpd_options[discounts][sets][<?php echo $rule_key; ?>][conditions][<?php echo $condition_key; ?>][key]" class="rp_wcdpd_conditions_field_key rp_wcdpd_conditions_key_field">
                                                        <optgroup label="<?php _e('Cart Subtotal', 'rp_wcdpd'); ?>">
                                                            <option value="subtotal_bottom" <?php echo ($condition['key'] == 'subtotal_bottom' ? 'selected="selected"' : ''); ?>><?php _e('Subtotal at least', 'rp_wcdpd'); ?></option>
                                                            <option value="subtotal_top" <?php echo ($condition['key'] == 'subtotal_top' ? 'selected="selected"' : ''); ?>><?php _e('Subtotal less than', 'rp_wcdpd'); ?></option>
                                                        </optgroup>
                                                        <optgroup label="<?php _e('Cart Item Count', 'rp_wcdpd'); ?>">
                                                            <option value="item_count_bottom" <?php echo ($condition['key'] == 'item_count_bottom' ? 'selected="selected"' : ''); ?>><?php _e('Count of cart items at least', 'rp_wcdpd'); ?></option>
                                                            <option value="item_count_top" <?php echo ($condition['key'] == 'item_count_top' ? 'selected="selected"' : ''); ?>><?php _e('Count of cart items less than', 'rp_wcdpd'); ?></option>
                                                        </optgroup>
                                                        <optgroup label="<?php _e('Quantity Sum', 'rp_wcdpd'); ?>">
                                                            <option value="quantity_bottom" <?php echo ($condition['key'] == 'quantity_bottom' ? 'selected="selected"' : ''); ?>><?php _e('Sum of item quantities at least', 'rp_wcdpd'); ?></option>
                                                            <option value="quantity_top" <?php echo ($condition['key'] == 'quantity_top' ? 'selected="selected"' : ''); ?>><?php _e('Sum of item quantities less than', 'rp_wcdpd'); ?></option>
                                                        </optgroup>
                                                        <optgroup label="<?php _e('Products In Cart', 'rp_wcdpd'); ?>">
                                                            <option value="products" <?php echo ($condition['key'] == 'products' ? 'selected="selected"' : ''); ?>><?php _e('At least one product in cart', 'rp_wcdpd'); ?></option>
                                                            <option value="products_not" <?php echo ($condition['key'] == 'products_not' ? 'selected="selected"' : ''); ?>><?php _e('None of selected products in cart', 'rp_wcdpd'); ?></option>
                                                        </optgroup>
                                                        <optgroup label="<?php _e('Categories In Cart', 'rp_wcdpd'); ?>">
                                                            <option value="categories" <?php echo ($condition['key'] == 'categories' ? 'selected="selected"' : ''); ?>><?php _e('At least one category in cart', 'rp_wcdpd'); ?></option>
                                                            <option value="categories_not" <?php echo ($condition['key'] == 'categories_not' ? 'selected="selected"' : ''); ?>><?php _e('None of selected categories in cart', 'rp_wcdpd'); ?></option>
                                                        </optgroup>
                                                        <optgroup label="<?php _e('Customer Details (must be logged in)', 'rp_wcdpd'); ?>">
                                                            <option value="users" <?php echo ($condition['key'] == 'users' ? 'selected="selected"' : ''); ?>><?php _e('User in list', 'rp_wcdpd'); ?></option>
                                                            <option value="roles" <?php echo ($condition['key'] == 'roles' ? 'selected="selected"' : ''); ?>><?php _e('User role in list', 'rp_wcdpd'); ?></option>
                                                            <option value="capabilities" <?php echo ($condition['key'] == 'capabilities' ? 'selected="selected"' : ''); ?>><?php _e('User capability in list', 'rp_wcdpd'); ?></option>
                                                            <option value="history_count" <?php echo ($condition['key'] == 'history_count' ? 'selected="selected"' : ''); ?>><?php _e('Order count to date at least', 'rp_wcdpd'); ?></option>
                                                            <option value="history_amount" <?php echo ($condition['key'] == 'history_amount' ? 'selected="selected"' : ''); ?>><?php _e('Amount spent to date at least', 'rp_wcdpd'); ?></option>
                                                            <option value="shipping_countries" <?php echo ($condition['key'] == 'shipping_countries' ? 'selected="selected"' : ''); ?>><?php _e('Shipping country in list', 'rp_wcdpd'); ?></option>
                                                        </optgroup>
                                                    </select>
                                                </td>
                                                <td class="rp_wcdpd_conditions_column">
                                                    <input type="text" id="rp_wcdpd_discounts_conditions_value_<?php echo $rule_key; ?>_<?php echo $condition_key; ?>" name="rp_wcdpd_options[discounts][sets][<?php echo $rule_key; ?>][conditions][<?php echo $condition_key; ?>][value]" value="<?php echo $condition['value']; ?>" class="rp_wcdpd_conditions_field_value rp_wcdpd_conditions_value_field" placeholder="<?php _e('e.g. 50', 'rp_wcdpd'); ?>">
                                                    <select multiple id="rp_wcdpd_discounts_products_<?php echo $rule_key; ?>_<?php echo $condition_key; ?>" name="rp_wcdpd_options[discounts][sets][<?php echo $rule_key; ?>][conditions][<?php echo $condition_key; ?>][products][]" class="rp_wcdpd_conditions_field_value rp_wcdpd_conditions_products_field">
                                                        <?php foreach($all_products as $product_key => $product): ?>
                                                            <option value="<?php echo $product_key; ?>" <?php echo (in_array($product_key, $condition['products']) ? 'selected="selected"' : ''); ?>><?php echo $product; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <select multiple id="rp_wcdpd_discounts_categories_<?php echo $rule_key; ?>_<?php echo $condition_key; ?>" name="rp_wcdpd_options[discounts][sets][<?php echo $rule_key; ?>][conditions][<?php echo $condition_key; ?>][categories][]" class="rp_wcdpd_conditions_field_value rp_wcdpd_conditions_categories_field">
                                                        <?php foreach($all_categories as $category_key => $category): ?>
                                                            <option value="<?php echo $category_key; ?>" <?php echo (in_array($category_key, $condition['categories']) ? 'selected="selected"' : ''); ?>><?php echo $category; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <select multiple id="rp_wcdpd_discounts_shipping_countries_<?php echo $rule_key; ?>_<?php echo $condition_key; ?>" name="rp_wcdpd_options[discounts][sets][<?php echo $rule_key; ?>][conditions][<?php echo $condition_key; ?>][shipping_countries][]" class="rp_wcdpd_conditions_field_value rp_wcdpd_conditions_shipping_countries_field">
                                                        <?php foreach($all_countries as $country_key => $country): ?>
                                                            <option value="<?php echo $country_key; ?>" <?php echo (in_array($country_key, $condition['shipping_countries']) ? 'selected="selected"' : ''); ?>><?php echo $country; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <select multiple id="rp_wcdpd_discounts_users_<?php echo $rule_key; ?>_<?php echo $condition_key; ?>" name="rp_wcdpd_options[discounts][sets][<?php echo $rule_key; ?>][conditions][<?php echo $condition_key; ?>][users][]" class="rp_wcdpd_conditions_field_value rp_wcdpd_conditions_users_field">
                                                        <?php foreach($all_users as $user_key => $user): ?>
                                                            <option value="<?php echo $user_key; ?>" <?php echo (in_array($user_key, $condition['users']) ? 'selected="selected"' : ''); ?>><?php echo $user; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <select multiple id="rp_wcdpd_discounts_roles_<?php echo $rule_key; ?>_<?php echo $condition_key; ?>" name="rp_wcdpd_options[discounts][sets][<?php echo $rule_key; ?>][conditions][<?php echo $condition_key; ?>][roles][]" class="rp_wcdpd_conditions_field_value rp_wcdpd_conditions_roles_field">
                                                        <?php foreach($all_roles as $role_key => $role): ?>
                                                            <option value="<?php echo $role_key; ?>" <?php echo (in_array($role_key, $condition['roles']) ? 'selected="selected"' : ''); ?>><?php echo $role; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <select multiple id="rp_wcdpd_discounts_capabilities_<?php echo $rule_key; ?>_<?php echo $condition_key; ?>" name="rp_wcdpd_options[discounts][sets][<?php echo $rule_key; ?>][conditions][<?php echo $condition_key; ?>][capabilities][]" class="rp_wcdpd_conditions_field_value rp_wcdpd_conditions_capabilities_field">
                                                        <?php foreach($all_capabilities as $capability_key => $capability): ?>
                                                            <option value="<?php echo $capability_key; ?>" <?php echo (in_array($capability_key, $condition['capabilities']) ? 'selected="selected"' : ''); ?>><?php echo $capability; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <button type="button" class="rp_wcdpd_remove_field"><i class="fa fa-times"></i></button>
                                                </td>
                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td>
                                                <button type="button" name="rp_wcdpd_add_items_row" id="rp_wcdpd_add_items_row_<?php echo $rule_key; ?>" class="button button-primary rp_wcdpd_add_items_row" value="<?php _e('Add Condition', 'rp_wcdpd'); ?>"><i class="fa fa-plus">&nbsp;&nbsp; <?php _e('Add Condition', 'rp_wcdpd'); ?></i></button>
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>

                                <div class="rp_wcdpd_sets_section"><?php _e('Discount', 'rp_wcdpd'); ?></div>
                                <table class="form-table"><tbody>

                                    <!-- Discount type -->
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Discount type', 'rp_wcdpd'); ?></th>
                                        <td>
                                            <select id="rp_wcdpd_discounts_type_<?php echo $rule_key; ?>" name="rp_wcdpd_options[discounts][sets][<?php echo $rule_key; ?>][type]" class="rp_wcdpd_field rp_wcdpd_discounts_type_field">
                                                <option value="percentage" <?php echo ($rule['type'] == 'percentage' ? 'selected="selected"' : ''); ?>><?php _e('Percentage discount', 'rp_wcdpd'); ?></option>
                                                <option value="price" <?php echo ($rule['type'] == 'price' ? 'selected="selected"' : ''); ?>><?php _e('Price discount', 'rp_wcdpd'); ?></option>
                                            </select>
                                        </td>
                                    </tr>

                                    <!-- Value -->
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Value', 'rp_wcdpd'); ?></th>
                                        <td>
                                            <input type="text" id="rp_wcdpd_discounts_value_<?php echo $rule_key; ?>" name="rp_wcdpd_options[discounts][sets][<?php echo $rule_key; ?>][value]" class="rp_wcdpd_field rp_wcdpd_discounts_value_field" value="<?php echo $rule['value']; ?>" placeholder="<?php _e('e.g. 5.00', 'rp_wcdpd'); ?>">
                                        </td>
                                    </tr>

                                </tbody></table>

                            </div>
                            <div style="clear: both;"></div>
                        </div>

                    <?php endforeach; ?>

                    </div>
                    <div>
                        <button type="button" name="rp_wcdpd_add_set" id="rp_wcdpd_add_set" class="button button-primary" value="<?php _e('Add Discount', 'rp_wcdpd'); ?>"><i class="fa fa-plus">&nbsp;&nbsp;<?php _e('Add Discount', 'rp_wcdpd'); ?></i></button>
                        <div style="clear: both;"></div>
                    </div>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <div style="clear: both;"></div>
    </div>
</div>