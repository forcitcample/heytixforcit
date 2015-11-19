<p class="form-field hideable subscription_product_tr">
    <?php
    $product_id     = (!empty($email->product_id)) ? $email->product_id : '';
    $product_name   = '';

    if ( !empty( $product_id ) ) {
        $product = WC_FUE_Compatibility::wc_get_product( $product_id );

        if ( $product ) {
            $product_name   = wp_kses_post( $product->get_formatted_name() );
        }
    }

    ?>
    <input
        type="hidden"
        id="subscription_product_id"
        name="subscription_product_id"
        class="ajax_select2_products_and_variations"
        data-placeholder="<?php _e('All subscription products&hellip;', 'woocommerce'); ?>"
        data-action="fue_wc_json_search_subscription_products"
        data-allow_clear="true"
        value="<?php echo $product_id ?>"
        data-selected="<?php echo esc_attr( $product_name ); ?>"
    >
</p>
<?php
$display        = 'display: none;';
$has_variations = (!empty($email->product_id) && FUE_Addon_Woocommerce::product_has_children($email->product_id)) ? true : false;

if ($has_variations) $display = 'display: inline-block;';
?>
<p class="form-field product_include_variations" style="<?php echo $display; ?>">
    <input type="checkbox" name="meta[include_variations]" id="include_variations" value="yes" <?php if (isset($email->meta['include_variations']) && $email->meta['include_variations'] == 'yes') echo 'checked'; ?> />
    <label for="include_variations" class="inline"><?php _e('Include variations', 'follow_up_emails'); ?></label>
</p>
