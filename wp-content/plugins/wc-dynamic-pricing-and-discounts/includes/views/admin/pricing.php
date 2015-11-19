<?php

/**
 * View for Pricing Rules tab
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

                <?php settings_fields('rp_wcdpd_opt_group_pricing'); ?>
                <input type="hidden" name="rp_wcdpd_options[current_tab]" value="<?php echo $current_tab; ?>" />

                <h3><?php _e('Manage Pricing Rules', 'rp_wcdpd'); ?></h3>

                <div class="rp_wcdpd_settings_right">
                    <select id="rp_wcdpd_apply_multiple" name="rp_wcdpd_options[pricing][apply_multiple]" class="rp_wcdpd_apply_multiple_field">
                        <option value="first" <?php echo ($this->opt['pricing']['apply_multiple'] == 'first' ? 'selected="selected"' : ''); ?>><?php _e('Apply first matched rule', 'rp_wcdpd'); ?></option>
                        <option value="all" <?php echo ($this->opt['pricing']['apply_multiple'] == 'all' ? 'selected="selected"' : ''); ?>><?php _e('Apply all matched rules', 'rp_wcdpd'); ?></option>
                        <option value="biggest" <?php echo ($this->opt['pricing']['apply_multiple'] == 'biggest' ? 'selected="selected"' : ''); ?>><?php _e('Apply biggest discount', 'rp_wcdpd'); ?></option>
                    </select>
                </div>
                <div style="clear: both;"></div>

                <div class="rp_wcdpd_sets">
                    <div id="rp_wcdpd_set_list">

                    <?php foreach ($this->opt['pricing']['sets'] as $rule_key => $rule): ?>

                        <div id="rp_wcdpd_set_<?php echo $rule_key; ?>">
                            <h4 class="rp_wcdpd_sets_handle"><span class="rp_wcdpd_sets_title" id="rp_wcdpd_sets_title_<?php echo $rule_key; ?>"><?php _e('Pricing Rule #', 'rp_wcdpd'); ?><?php echo $rule_key; ?></span>&nbsp;<span class="rp_wcdpd_sets_title_name"><?php echo (!empty($rule['title'])) ? '- ' . $rule['title'] : ''; ?></span><span class="rp_wcdpd_sets_remove" id="rp_wcdpd_sets_remove_<?php echo $rule_key; ?>" title="<?php _e('Remove', 'rp_wcdpd'); ?>"><i class="fa fa-times"></i></span></h4>
                            <div style="clear:both;">

                                <div class="rp_wcdpd_sets_section"><?php _e('General Settings', 'rp_wcdpd'); ?></div>
                                <table class="form-table"><tbody>

                                    <!-- Rule description -->
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Rule description', 'rp_wcdpd'); ?></th>
                                        <td>
                                            <input type="text" id="rp_wcdpd_pricing_description_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][description]" class="rp_wcdpd_field rp_wcdpd_description_field" value="<?php echo $rule['description']; ?>" placeholder="<?php _e('e.g. wholesale pricing for VIP customers', 'rp_wcdpd'); ?>">
                                        </td>
                                    </tr>

                                    <!-- Pricing Method -->
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Method', 'rp_wcdpd'); ?></th>
                                        <td>
                                            <select id="rp_wcdpd_pricing_method_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][method]" class="rp_wcdpd_field rp_wcdpd_method_field">
                                                <option value="quantity" <?php echo ($rule['method'] == 'quantity' ? 'selected="selected"' : ''); ?>><?php _e('Quantity discount', 'rp_wcdpd'); ?></option>
                                                <option value="special" <?php echo ($rule['method'] == 'special' ? 'selected="selected"' : ''); ?>><?php _e('Special offer', 'rp_wcdpd'); ?></option>
                                                <option value="exclude" <?php echo ($rule['method'] == 'exclude' ? 'selected="selected"' : ''); ?>><?php _e('Exclude matched items', 'rp_wcdpd'); ?></option>
                                            </select>
                                        </td>
                                    </tr>

                                    <!-- Quantities based on -->
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Quantities based on', 'rp_wcdpd'); ?></th>
                                        <td>
                                            <select id="rp_wcdpd_pricing_quantities_based_on_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][quantities_based_on]" class="rp_wcdpd_field rp_wcdpd_quantities_based_on_field">
                                                <optgroup label="<?php _e('Exclusive', 'rp_wcdpd'); ?>">
                                                    <option value="exclusive_product" <?php echo ($rule['quantities_based_on'] == 'exclusive_product' ? 'selected="selected"' : ''); ?>><?php _e('Quantities of each product individually', 'rp_wcdpd'); ?></option>
                                                    <option value="exclusive_variation" <?php echo ($rule['quantities_based_on'] == 'exclusive_variation' ? 'selected="selected"' : ''); ?>><?php _e('Quantities of each variation individually', 'rp_wcdpd'); ?></option>
                                                    <option value="exclusive_configuration" <?php echo ($rule['quantities_based_on'] == 'exclusive_configuration' ? 'selected="selected"' : ''); ?>><?php _e('Quantities of each cart line item individually', 'rp_wcdpd'); ?></option>
                                                </optgroup>
                                                <optgroup label="<?php _e('Cumulative', 'rp_wcdpd'); ?>">
                                                    <option value="cumulative_categories" <?php echo ($rule['quantities_based_on'] == 'cumulative_categories' ? 'selected="selected"' : ''); ?>><?php _e('Quantities of all selected products split by category', 'rp_wcdpd'); ?></option>
                                                    <option value="cumulative_all" <?php echo ($rule['quantities_based_on'] == 'cumulative_all' ? 'selected="selected"' : ''); ?>><?php _e('Quantities of all selected products summed up', 'rp_wcdpd'); ?></option>
                                                </optgroup>
                                            </select>
                                        </td>
                                    </tr>

                                    <!-- If conditions are matched -->
                                    <tr valign="top">
                                        <th scope="row"><?php _e('If conditions are matched', 'rp_wcdpd'); ?></th>
                                        <td>
                                            <select id="rp_wcdpd_pricing_if_matched_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][if_matched]" class="rp_wcdpd_field rp_wcdpd_if_matched_field">
                                                <option value="all" <?php echo ($rule['if_matched'] == 'all' ? 'selected="selected"' : ''); ?>><?php _e('Apply with other matched rules', 'rp_wcdpd'); ?></option>
                                                <option value="this" <?php echo ($rule['if_matched'] == 'this' ? 'selected="selected"' : ''); ?>><?php _e('Apply only this rule (disregard other rules)', 'rp_wcdpd'); ?></option>
                                                <option value="other" <?php echo ($rule['if_matched'] == 'other' ? 'selected="selected"' : ''); ?>><?php _e('Apply only if no other rules are matched', 'rp_wcdpd'); ?></option>
                                            </select>
                                        </td>
                                    </tr>

                                    <!-- Valid from/until -->
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Valid from/until', 'rp_wcdpd'); ?></th>
                                        <td>
                                            <input type="text" id="rp_wcdpd_pricing_valid_from_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][valid_from]" value="<?php echo $rule['valid_from']; ?>" class="rp_wcdpd_date_field rp_wcdpd_pricing_valid_from_field" placeholder="<?php _e('Select date from...', 'rp_wcdpd'); ?>">
                                            <input type="text" id="rp_wcdpd_pricing_valid_until_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][valid_until]" value="<?php echo $rule['valid_until']; ?>" class="rp_wcdpd_date_field rp_wcdpd_pricing_valid_until_field" placeholder="<?php _e('Select date until...', 'rp_wcdpd'); ?>">
                                        </td>
                                    </tr>

                                </tbody></table>

                                <div class="rp_wcdpd_sets_section"><?php _e('Conditions', 'rp_wcdpd'); ?></div>
                                <table class="form-table"><tbody>

                                    <!-- Apply to -->
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Apply to', 'rp_wcdpd'); ?></th>
                                        <td>
                                            <select id="rp_wcdpd_pricing_selection_method_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][selection_method]" class="rp_wcdpd_field rp_wcdpd_selection_method_field">
                                                <optgroup label="<?php _e('All products', 'rp_wcdpd'); ?>">
                                                    <option value="all" <?php echo ($rule['selection_method'] == 'all' ? 'selected="selected"' : ''); ?>><?php _e('All products', 'rp_wcdpd'); ?></option>
                                                </optgroup>
                                                <optgroup label="<?php _e('Specific categories', 'rp_wcdpd'); ?>">
                                                    <option value="categories_include" <?php echo ($rule['selection_method'] == 'categories_include' ? 'selected="selected"' : ''); ?>><?php _e('Categories in list', 'rp_wcdpd'); ?></option>
                                                    <option value="categories_exclude" <?php echo ($rule['selection_method'] == 'categories_exclude' ? 'selected="selected"' : ''); ?>><?php _e('Categories not in list', 'rp_wcdpd'); ?></option>
                                                </optgroup>
                                                <optgroup label="<?php _e('Specific products', 'rp_wcdpd'); ?>">
                                                    <option value="products_include" <?php echo ($rule['selection_method'] == 'products_include' ? 'selected="selected"' : ''); ?>><?php _e('Products in list', 'rp_wcdpd'); ?></option>
                                                    <option value="products_exclude" <?php echo ($rule['selection_method'] == 'products_exclude' ? 'selected="selected"' : ''); ?>><?php _e('Products not in list', 'rp_wcdpd'); ?></option>
                                                </optgroup>
                                            </select>
                                        </td>
                                    </tr>

                                    <!-- Category list -->
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Category list', 'rp_wcdpd'); ?></th>
                                        <td>
                                            <select multiple id="rp_wcdpd_pricing_categories_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][categories][]" class="rp_wcdpd_field rp_wcdpd_categories_field">
                                                <?php foreach($all_categories as $category_key => $category): ?>
                                                    <option value="<?php echo $category_key; ?>" <?php echo (in_array($category_key, $rule['categories']) ? 'selected="selected"' : ''); ?>><?php echo $category; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>

                                    <!-- Product list -->
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Product list', 'rp_wcdpd'); ?></th>
                                        <td>
                                            <select multiple id="rp_wcdpd_pricing_products_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][products][]" class="rp_wcdpd_field rp_wcdpd_products_field">
                                                <?php foreach($all_products as $product_key => $product): ?>
                                                    <option value="<?php echo $product_key; ?>" <?php echo (in_array($product_key, $rule['products']) ? 'selected="selected"' : ''); ?>><?php echo $product; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>

                                    <!-- Customers -->
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Customers', 'rp_wcdpd'); ?></th>
                                        <td>
                                            <select id="rp_wcdpd_pricing_user_method_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][user_method]" class="rp_wcdpd_field rp_wcdpd_user_method_field">
                                                <optgroup label="<?php _e('All customers', 'rp_wcdpd'); ?>">
                                                    <option value="all" <?php echo ($rule['user_method'] == 'all' ? 'selected="selected"' : ''); ?>><?php _e('All customers', 'rp_wcdpd'); ?></option>
                                                </optgroup>
                                                <optgroup label="<?php _e('Specific roles', 'rp_wcdpd'); ?>">
                                                    <option value="roles_include" <?php echo ($rule['user_method'] == 'roles_include' ? 'selected="selected"' : ''); ?>><?php _e('Roles in list', 'rp_wcdpd'); ?></option>
                                                    <option value="roles_exclude" <?php echo ($rule['user_method'] == 'roles_exclude' ? 'selected="selected"' : ''); ?>><?php _e('Roles not in list', 'rp_wcdpd'); ?></option>
                                                </optgroup>
                                                <optgroup label="<?php _e('Specific capabilities', 'rp_wcdpd'); ?>">
                                                    <option value="capabilities_include" <?php echo ($rule['user_method'] == 'capabilities_include' ? 'selected="selected"' : ''); ?>><?php _e('Capabilities in list', 'rp_wcdpd'); ?></option>
                                                    <option value="capabilities_exclude" <?php echo ($rule['user_method'] == 'capabilities_exclude' ? 'selected="selected"' : ''); ?>><?php _e('Capabilities not in list', 'rp_wcdpd'); ?></option>
                                                </optgroup>
                                                <optgroup label="<?php _e('Specific customers', 'rp_wcdpd'); ?>">
                                                    <option value="users_include" <?php echo ($rule['user_method'] == 'users_include' ? 'selected="selected"' : ''); ?>><?php _e('Customers in list', 'rp_wcdpd'); ?></option>
                                                    <option value="users_exclude" <?php echo ($rule['user_method'] == 'users_exclude' ? 'selected="selected"' : ''); ?>><?php _e('Customers not in list', 'rp_wcdpd'); ?></option>
                                                </optgroup>
                                            </select>
                                        </td>
                                    </tr>

                                    <!-- Role list -->
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Role list', 'rp_wcdpd'); ?></th>
                                        <td>
                                            <select multiple id="rp_wcdpd_pricing_roles_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][roles][]" class="rp_wcdpd_field rp_wcdpd_roles_field">
                                                <?php foreach($all_roles as $role_key => $role): ?>
                                                    <option value="<?php echo $role_key; ?>" <?php echo (in_array($role_key, $rule['roles']) ? 'selected="selected"' : ''); ?>><?php echo $role; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>

                                    <!-- Capability list -->
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Capability list', 'rp_wcdpd'); ?></th>
                                        <td>
                                            <select multiple id="rp_wcdpd_pricing_capabilities_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][capabilities][]" class="rp_wcdpd_field rp_wcdpd_capabilities_field">
                                                <?php foreach($all_capabilities as $capability_key => $capability): ?>
                                                    <option value="<?php echo $capability_key; ?>" <?php echo (in_array($capability_key, $rule['capabilities']) ? 'selected="selected"' : ''); ?>><?php echo $capability; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>

                                    <!-- Customer list -->
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Customer list', 'rp_wcdpd'); ?></th>
                                        <td>
                                            <select multiple id="rp_wcdpd_pricing_users_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][users][]" class="rp_wcdpd_field rp_wcdpd_users_field">
                                                <?php foreach($all_users as $user_key => $user): ?>
                                                    <option value="<?php echo $user_key; ?>" <?php echo (in_array($user_key, $rule['users']) ? 'selected="selected"' : ''); ?>><?php echo $user; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>

                                </tbody></table>

                                <div class="rp_wcdpd_sets_quantity">

                                    <div class="rp_wcdpd_sets_section"><?php _e('Quantity Discount', 'rp_wcdpd'); ?></div>

                                        <!-- Pricing table -->
                                        <table id="rp_wcdpd_items_table_<?php echo $rule_key; ?>" class="form-table rp_wcdpd_items_table rp_wcdpd_pricing_table">
                                            <thead>
                                                <tr>
                                                    <th><?php _e('Min quantity', 'rp_wcdpd'); ?></th>
                                                    <th><?php _e('Max quantity', 'rp_wcdpd'); ?></th>
                                                    <th><?php _e('Adjustment type', 'rp_wcdpd'); ?></th>
                                                    <th><?php _e('Value', 'rp_wcdpd'); ?></th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>

                                                <?php foreach($rule['pricing'] as $pricing_key => $pricing): ?>

                                                    <tr class="rp_wcdpd_items_row" id="rp_wcdpd_items_row_<?php echo $rule_key; ?>_<?php echo $pricing_key; ?>">
                                                        <td class="rp_wcdpd_pricing_column">
                                                            <input type="text" id="rp_wcdpd_pricing_quantity_pricing_min_<?php echo $rule_key; ?>_<?php echo $pricing_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][pricing][<?php echo $pricing_key; ?>][min]" value="<?php echo $pricing['min']; ?>" class="rp_wcdpd_pricing_field rp_wcdpd_pricing_min_field" placeholder="<?php _e('e.g. 5', 'rp_wcdpd'); ?>">
                                                        </td>
                                                        <td class="rp_wcdpd_pricing_column">
                                                            <input type="text" id="rp_wcdpd_pricing_quantity_pricing_max_<?php echo $rule_key; ?>_<?php echo $pricing_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][pricing][<?php echo $pricing_key; ?>][max]" value="<?php echo $pricing['max']; ?>" class="rp_wcdpd_pricing_field rp_wcdpd_pricing_max_field" placeholder="<?php _e('e.g. 9', 'rp_wcdpd'); ?>">
                                                        </td>
                                                        <td class="rp_wcdpd_pricing_column_wider">
                                                            <select id="rp_wcdpd_pricing_quantity_pricing_type_<?php echo $rule_key; ?>_<?php echo $pricing_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][pricing][<?php echo $pricing_key; ?>][type]" class="rp_wcdpd_pricing_field rp_wcdpd_pricing_type_field">
                                                                <option value="percentage" <?php echo ($pricing['type'] == 'percentage' ? 'selected="selected"' : ''); ?>><?php _e('Percentage discount', 'rp_wcdpd'); ?></option>
                                                                <option value="price" <?php echo ($pricing['type'] == 'price' ? 'selected="selected"' : ''); ?>><?php _e('Price discount', 'rp_wcdpd'); ?></option>
                                                                <option value="fixed" <?php echo ($pricing['type'] == 'fixed' ? 'selected="selected"' : ''); ?>><?php _e('Fixed price', 'rp_wcdpd'); ?></option>
                                                            </select>
                                                        </td>
                                                        <td class="rp_wcdpd_pricing_column">
                                                            <input type="text" id="rp_wcdpd_pricing_quantity_pricing_value_<?php echo $rule_key; ?>_<?php echo $pricing_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][pricing][<?php echo $pricing_key; ?>][value]" value="<?php echo $pricing['value']; ?>" class="rp_wcdpd_pricing_field rp_wcdpd_pricing_value_field" placeholder="<?php _e('e.g. 15.00', 'rp_wcdpd'); ?>">
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
                                                        <button type="button" name="rp_wcdpd_add_items_row" id="rp_wcdpd_add_items_row_<?php echo $rule_key; ?>" class="button button-primary rp_wcdpd_add_items_row" value="<?php _e('Add Row', 'rp_wcdpd'); ?>"><i class="fa fa-plus">&nbsp;&nbsp; <?php _e('Add Row', 'rp_wcdpd'); ?></i></button>
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>

                                        <table class="form-table"><tbody>

                                            <!-- Products to adjust -->
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Products to adjust', 'rp_wcdpd'); ?></th>
                                                <td>
                                                    <select id="rp_wcdpd_pricing_quantity_products_to_adjust_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][quantity_products_to_adjust]" class="rp_wcdpd_field rp_wcdpd_quantity_products_to_adjust_field">
                                                        <optgroup label="<?php _e('Same products', 'rp_wcdpd'); ?>">
                                                            <option value="matched" <?php echo ($rule['quantity_products_to_adjust'] == 'matched' ? 'selected="selected"' : ''); ?>><?php _e('Same products (selected above)', 'rp_wcdpd'); ?></option>
                                                        </optgroup>
                                                        <optgroup label="<?php _e('Other products', 'rp_wcdpd'); ?>">
                                                            <option value="other_categories" <?php echo ($rule['quantity_products_to_adjust'] == 'other_categories' ? 'selected="selected"' : ''); ?>><?php _e('Specific categories', 'rp_wcdpd'); ?></option>
                                                            <option value="other_products" <?php echo ($rule['quantity_products_to_adjust'] == 'other_products' ? 'selected="selected"' : ''); ?>><?php _e('Specific products', 'rp_wcdpd'); ?></option>
                                                        </optgroup>
                                                    </select>
                                                </td>
                                            </tr>

                                            <!-- Category list -->
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Category list', 'rp_wcdpd'); ?></th>
                                                <td>
                                                    <select multiple id="rp_wcdpd_pricing_quantity_categories_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][quantity_categories][]" class="rp_wcdpd_field rp_wcdpd_quantity_categories_field">
                                                        <?php foreach($all_categories as $category_key => $category): ?>
                                                            <option value="<?php echo $category_key; ?>" <?php echo (in_array($category_key, $rule['quantity_categories']) ? 'selected="selected"' : ''); ?>><?php echo $category; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                            </tr>

                                            <!-- Product list -->
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Product list', 'rp_wcdpd'); ?></th>
                                                <td>
                                                    <select multiple id="rp_wcdpd_pricing_quantity_products_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][quantity_products][]" class="rp_wcdpd_field rp_wcdpd_quantity_products_field">
                                                        <?php foreach($all_products as $product_key => $product): ?>
                                                            <option value="<?php echo $product_key; ?>" <?php echo (in_array($product_key, $rule['quantity_products']) ? 'selected="selected"' : ''); ?>><?php echo $product; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                            </tr>

                                        </tbody></table>

                                </div>

                                <div class="rp_wcdpd_sets_special">

                                    <div class="rp_wcdpd_sets_section"><?php _e('Special Offer', 'rp_wcdpd'); ?></div>
                                    <table class="form-table"><tbody>

                                        <!-- Amount to purchase -->
                                        <tr valign="top">
                                            <th scope="row"><?php _e('Amount to purchase', 'rp_wcdpd'); ?></th>
                                            <td>
                                                <input type="text" id="rp_wcdpd_pricing_special_purchase_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][special_purchase]" class="rp_wcdpd_field rp_wcdpd_special_purchase_field" value="<?php echo $rule['special_purchase']; ?>" placeholder="<?php _e('e.g. 2', 'rp_wcdpd'); ?>">
                                            </td>
                                        </tr>

                                        <!-- Products to adjust -->
                                        <tr valign="top">
                                            <th scope="row"><?php _e('Products to adjust', 'rp_wcdpd'); ?></th>
                                            <td>
                                                <select id="rp_wcdpd_pricing_special_products_to_adjust_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][special_products_to_adjust]" class="rp_wcdpd_field rp_wcdpd_special_products_to_adjust_field">
                                                    <optgroup label="<?php _e('Same products', 'rp_wcdpd'); ?>">
                                                        <option value="matched" <?php echo ($rule['special_products_to_adjust'] == 'matched' ? 'selected="selected"' : ''); ?>><?php _e('Same products (selected above)', 'rp_wcdpd'); ?></option>
                                                    </optgroup>
                                                    <optgroup label="<?php _e('Other products', 'rp_wcdpd'); ?>">
                                                        <option value="other_categories" <?php echo ($rule['special_products_to_adjust'] == 'other_categories' ? 'selected="selected"' : ''); ?>><?php _e('Specific categories', 'rp_wcdpd'); ?></option>
                                                        <option value="other_products" <?php echo ($rule['special_products_to_adjust'] == 'other_products' ? 'selected="selected"' : ''); ?>><?php _e('Specific products', 'rp_wcdpd'); ?></option>
                                                    </optgroup>
                                                </select>
                                            </td>
                                        </tr>

                                        <!-- Category list -->
                                        <tr valign="top">
                                            <th scope="row"><?php _e('Category list', 'rp_wcdpd'); ?></th>
                                            <td>
                                                <select multiple id="rp_wcdpd_pricing_special_categories_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][special_categories][]" class="rp_wcdpd_field rp_wcdpd_special_categories_field">
                                                    <?php foreach($all_categories as $category_key => $category): ?>
                                                        <option value="<?php echo $category_key; ?>" <?php echo (in_array($category_key, $rule['special_categories']) ? 'selected="selected"' : ''); ?>><?php echo $category; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>

                                        <!-- Product list -->
                                        <tr valign="top">
                                            <th scope="row"><?php _e('Product list', 'rp_wcdpd'); ?></th>
                                            <td>
                                                <select multiple id="rp_wcdpd_pricing_special_products_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][special_products][]" class="rp_wcdpd_field rp_wcdpd_special_products_field">
                                                    <?php foreach($all_products as $product_key => $product): ?>
                                                        <option value="<?php echo $product_key; ?>" <?php echo (in_array($product_key, $rule['special_products']) ? 'selected="selected"' : ''); ?>><?php echo $product; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>

                                        <!-- Amount to adjust -->
                                        <tr valign="top">
                                            <th scope="row"><?php _e('Amount to adjust', 'rp_wcdpd'); ?></th>
                                            <td>
                                                <input type="text" id="rp_wcdpd_pricing_special_adjust_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][special_adjust]" class="rp_wcdpd_field rp_wcdpd_special_adjust_field" value="<?php echo $rule['special_adjust']; ?>" placeholder="<?php _e('e.g. 1', 'rp_wcdpd'); ?>">
                                            </td>
                                        </tr>

                                        <!-- Adjustment type -->
                                        <tr valign="top">
                                            <th scope="row"><?php _e('Adjustment type', 'rp_wcdpd'); ?></th>
                                            <td>
                                                <select id="rp_wcdpd_pricing_special_type_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][special_type]" class="rp_wcdpd_field rp_wcdpd_special_type_field">
                                                    <option value="percentage" <?php echo ($rule['special_type'] == 'percentage' ? 'selected="selected"' : ''); ?>><?php _e('Percentage discount', 'rp_wcdpd'); ?></option>
                                                    <option value="price" <?php echo ($rule['special_type'] == 'price' ? 'selected="selected"' : ''); ?>><?php _e('Price discount', 'rp_wcdpd'); ?></option>
                                                    <option value="fixed" <?php echo ($rule['special_type'] == 'fixed' ? 'selected="selected"' : ''); ?>><?php _e('Fixed price', 'rp_wcdpd'); ?></option>
                                                </select>
                                            </td>
                                        </tr>

                                        <!-- Adjustment value -->
                                        <tr valign="top">
                                            <th scope="row"><?php _e('Adjustment value', 'rp_wcdpd'); ?></th>
                                            <td>
                                                <input type="text" id="rp_wcdpd_pricing_special_value_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][special_value]" class="rp_wcdpd_field rp_wcdpd_special_value_field" value="<?php echo $rule['special_value']; ?>" placeholder="<?php _e('e.g. 15.00', 'rp_wcdpd'); ?>">
                                            </td>
                                        </tr>

                                        <!-- Repeat -->
                                        <tr valign="top">
                                            <th scope="row"><?php _e('Repeat', 'rp_wcdpd'); ?></th>
                                            <td>
                                                <input type="checkbox" id="rp_wcdpd_pricing_special_repeat_<?php echo $rule_key; ?>" name="rp_wcdpd_options[pricing][sets][<?php echo $rule_key; ?>][special_repeat]" class="rp_wcdpd_field rp_wcdpd_special_repeat_field" <?php echo ($rule['special_repeat'] ? 'checked="checked"' : ''); ?>>
                                            </td>
                                        </tr>

                                    </tbody></table>

                                </div>

                            </div>
                            <div style="clear: both;"></div>
                        </div>

                    <?php endforeach; ?>

                    </div>
                    <div>
                        <button type="button" name="rp_wcdpd_add_set" id="rp_wcdpd_add_set" class="button button-primary" value="<?php _e('Add Rule', 'rp_wcdpd'); ?>"><i class="fa fa-plus">&nbsp;&nbsp;<?php _e('Add Rule', 'rp_wcdpd'); ?></i></button>
                        <div style="clear: both;"></div>
                    </div>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <div style="clear: both;"></div>
    </div>
</div>