/**
 * WooCommerce Dynamic Pricing & Discounts Plugin Frontend JavaScript
 */
jQuery(function() {

    /**
     * MODAL
     */
    jQuery('.rp_wcdpd_product_page_modal_link span').click(function() {

        if(!jQuery('#rp_wcdpd_modal_overlay').length) {
            jQuery('body').append('<div id="rp_wcdpd_modal_overlay" class="rp_wcdpd_modal_overlay"></div>');
        }

        jQuery('#rp_wcdpd_modal_overlay').click(function() {
            jQuery('#rp_wcdpd_modal_overlay').fadeOut();
            jQuery('.rp_wcdpd_modal').fadeOut();
        });

        var pricing_table = jQuery('.rp_wcdpd_modal');
        jQuery('#rp_wcdpd_modal_overlay').fadeIn();
        pricing_table.css('top', '50%').css('left', '50%').css('margin-top', -pricing_table.outerHeight()/2).css('margin-left', -pricing_table.outerWidth()/2).fadeIn();

        return false;
    });

    /**
     * VARIABLE PRODUCT PRICING TABLE
     */
    function rp_wcdpd_switch_variable_pricing_tables(element_id) {
        jQuery('.rp_wcdpd_pricing_table_variation').hide();
        jQuery(element_id).show();
    }

    if (jQuery('.rp_wcdpd_pricing_table_variation').length) {
        jQuery('input:hidden[name="variation_id"]').each(function() {
            rp_wcdpd_switch_variable_pricing_tables('#rp_wcdpd_pricing_table_variation_' + jQuery(this).val());

            jQuery(this).change(function() {
                rp_wcdpd_switch_variable_pricing_tables('#rp_wcdpd_pricing_table_variation_' + jQuery(this).val());
            });
        });
    }

});