<?php

/**
 * View for Localization tab
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

                <?php settings_fields('rp_wcdpd_opt_group_localization'); ?>
                <input type="hidden" name="rp_wcdpd_options[current_tab]" value="<?php echo $current_tab; ?>" />

                <h3><?php _e('Labels', 'rp_wcdpd'); ?></h3>

                <table class="form-table"><tbody>

                    <!-- Quantity discounts -->
                    <tr valign="top">
                        <th scope="row"><?php _e('Quantity discounts', 'rp_wcdpd'); ?></th>
                        <td>
                            <input type="text" id="rp_wcdpd_localization_quantity_discounts" name="rp_wcdpd_options[localization][quantity_discounts]" class="rp_wcdpd_field rp_wcdpd_localization_quantity_discounts_field" value="<?php echo $this->opt['localization']['quantity_discounts']; ?>">
                        </td>
                    </tr>

                    <!-- Special offers -->
                    <tr valign="top">
                        <th scope="row"><?php _e('Special offers', 'rp_wcdpd'); ?></th>
                        <td>
                            <input type="text" id="rp_wcdpd_localization_special_offers" name="rp_wcdpd_options[localization][special_offers]" class="rp_wcdpd_field rp_wcdpd_localization_special_offers_field" value="<?php echo $this->opt['localization']['special_offers']; ?>">
                        </td>
                    </tr>

                    <!-- Quantity -->
                    <tr valign="top">
                        <th scope="row"><?php _e('Quantity', 'rp_wcdpd'); ?></th>
                        <td>
                            <input type="text" id="rp_wcdpd_localization_quantity" name="rp_wcdpd_options[localization][quantity]" class="rp_wcdpd_field rp_wcdpd_localization_quantity_field" value="<?php echo $this->opt['localization']['quantity']; ?>">
                        </td>
                    </tr>

                    <!-- Price -->
                    <tr valign="top">
                        <th scope="row"><?php _e('Price', 'rp_wcdpd'); ?></th>
                        <td>
                            <input type="text" id="rp_wcdpd_localization_price" name="rp_wcdpd_options[localization][price]" class="rp_wcdpd_field rp_wcdpd_localization_price_field" value="<?php echo $this->opt['localization']['price']; ?>">
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