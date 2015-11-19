<?php

/**
 * View for Settings tab
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

                <?php settings_fields('rp_wcdpd_opt_group_settings'); ?>
                <input type="hidden" name="rp_wcdpd_options[current_tab]" value="<?php echo $current_tab; ?>" />

                <h3><?php _e('Cart Discounts', 'rp_wcdpd'); ?></h3>

                <table class="form-table"><tbody>

                    <!-- Cart discount title -->
                    <tr valign="top">
                        <th scope="row"><?php _e('Cart discount title', 'rp_wcdpd'); ?></th>
                        <td>
                            <input type="text" id="rp_wcdpd_settings_cart_discount_title" name="rp_wcdpd_options[settings][cart_discount_title]" class="rp_wcdpd_field rp_wcdpd_settings_cart_discount_title_field" value="<?php echo $this->opt['settings']['cart_discount_title']; ?>" />
                        </td>
                    </tr>

                </tbody></table>

                <h3><?php _e('Quantity Pricing Table', 'rp_wcdpd'); ?> <small><?php _e('(experimental)', 'rp_wcdpd'); ?></small></h3>

                <table class="form-table"><tbody>

                    <!-- Quantity pricing table -->
                    <tr valign="top">
                        <th scope="row"><?php _e('Display pricing table', 'rp_wcdpd'); ?></th>
                        <td>
                            <select id="rp_wcdpd_settings_display_table" name="rp_wcdpd_options[settings][display_table]" class="rp_wcdpd_field rp_wcdpd_settings_display_table_field">
                                <option value="hide" <?php echo ($this->opt['settings']['display_table'] == 'hide' ? 'selected="selected"' : ''); ?>><?php _e('Do not display', 'rp_wcdpd'); ?></option>
                                <option value="modal" <?php echo ($this->opt['settings']['display_table'] == 'modal' ? 'selected="selected"' : ''); ?>><?php _e('Product Page - Display in a modal', 'rp_wcdpd'); ?></option>
                                <option value="inline" <?php echo ($this->opt['settings']['display_table'] == 'inline' ? 'selected="selected"' : ''); ?>><?php _e('Product Page - Display inline', 'rp_wcdpd'); ?></option>
                            </select>
                        </td>
                    </tr>

                    <!-- Special offer details -->
                    <!--<tr valign="top">
                        <th scope="row"><?php _e('Special offer details', 'rp_wcdpd'); ?></th>
                        <td>
                            <select id="rp_wcdpd_settings_display_offers" name="rp_wcdpd_options[settings][display_offers]" class="rp_wcdpd_field rp_wcdpd_settings_display_offers_field">
                                <option value="hide" <?php echo ($this->opt['settings']['display_offers'] == 'hide' ? 'selected="selected"' : ''); ?>><?php _e('Do not display', 'rp_wcdpd'); ?></option>
                                <option value="modal" <?php echo ($this->opt['settings']['display_offers'] == 'modal' ? 'selected="selected"' : ''); ?>><?php _e('Display in a modal', 'rp_wcdpd'); ?></option>
                                <option value="inline" <?php echo ($this->opt['settings']['display_offers'] == 'inline' ? 'selected="selected"' : ''); ?>><?php _e('Display inline', 'rp_wcdpd'); ?></option>
                            </select>
                        </td>
                    </tr>-->

                    <!-- Pricing table layout -->
                    <tr valign="top">
                        <th scope="row"><?php _e('Table layout', 'rp_wcdpd'); ?></th>
                        <td>
                            <select id="rp_wcdpd_settings_pricing_table_style" name="rp_wcdpd_options[settings][pricing_table_style]" class="rp_wcdpd_field rp_wcdpd_settings_pricing_table_style_field">
                                    <option value="horizontal" <?php echo ($this->opt['settings']['pricing_table_style'] == 'horizontal' ? 'selected="selected"' : ''); ?>><?php _e('Horizontal', 'rp_wcdpd'); ?></option>
                                    <option value="vertical" <?php echo ($this->opt['settings']['pricing_table_style'] == 'vertical' ? 'selected="selected"' : ''); ?>><?php _e('Vertical', 'rp_wcdpd'); ?></option>
                            </select>
                        </td>
                    </tr>

                    <!-- Display position -->
                    <tr valign="top">
                        <th scope="row"><?php _e('Display position', 'rp_wcdpd'); ?></th>
                        <td>
                            <select id="rp_wcdpd_settings_display_position" name="rp_wcdpd_options[settings][display_position]" class="rp_wcdpd_field rp_wcdpd_settings_display_position_field">
                                <option value="woocommerce_before_add_to_cart_form" <?php echo ($this->opt['settings']['display_position'] == 'woocommerce_before_add_to_cart_form' ? 'selected="selected"' : ''); ?>><?php _e('Above add to cart', 'rp_wcdpd'); ?></option>
                                <option value="woocommerce_after_add_to_cart_form" <?php echo ($this->opt['settings']['display_position'] == 'woocommerce_after_add_to_cart_form' ? 'selected="selected"' : ''); ?>><?php _e('Below add to cart', 'rp_wcdpd'); ?></option>
                                <option value="woocommerce_single_product_summary" <?php echo ($this->opt['settings']['display_position'] == 'woocommerce_single_product_summary' ? 'selected="selected"' : ''); ?>><?php _e('Above product summary', 'rp_wcdpd'); ?></option>
                                <option value="woocommerce_after_single_product_summary" <?php echo ($this->opt['settings']['display_position'] == 'woocommerce_after_single_product_summary' ? 'selected="selected"' : ''); ?>><?php _e('Below product summary', 'rp_wcdpd'); ?></option>
                                <option value="woocommerce_product_meta_end" <?php echo ($this->opt['settings']['display_position'] == 'woocommerce_product_meta_end' ? 'selected="selected"' : ''); ?>><?php _e('Below product meta', 'rp_wcdpd'); ?></option>
                                <option value="woocommerce_after_main_content" <?php echo ($this->opt['settings']['display_position'] == 'woocommerce_after_main_content' ? 'selected="selected"' : ''); ?>><?php _e('Below page content', 'rp_wcdpd'); ?></option>
                            </select>
                        </td>
                    </tr>

                </tbody></table>

                <div>
                    <?php submit_button(); ?>
                </div>

            </form>
        </div>
        <div style="clear: both;"></div>
    </div>
</div>