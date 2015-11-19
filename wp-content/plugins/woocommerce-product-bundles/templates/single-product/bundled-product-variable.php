<?php
/**
 * Variable Bundled Product Template.
 *
 * @version 4.9.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $woocommerce_bundles;

?><div class="cart bundled_item_cart_content" data-title="<?php echo esc_attr( $bundled_item->get_raw_title() ); ?>" style="<?php echo $bundled_item->is_optional() && ! $bundled_item->is_optional_checked() ? 'display:none;' : ''; ?>" data-optional="<?php echo $bundled_item->is_optional() ? true : false; ?>" data-type="<?php echo $bundled_product->product_type; ?>" data-product_variations="<?php echo esc_attr( json_encode( $bundled_product_variations ) ); ?>" data-bundled_item_id="<?php echo $bundled_item->item_id; ?>" data-product_id="<?php echo $bundle->id . str_replace( '_', '', $bundled_item->item_id ); ?>" data-bundle_id="<?php echo $bundle->id; ?>">
	<table class="variations" cellspacing="0">
		<tbody><?php

		$loop = 0;

		foreach ( $bundled_product_attributes as $name => $options ) {

			$loop++;

			?><tr class="attribute-options" data-attribute_label="<?php echo wc_attribute_label( $name ); ?>">
				<td class="label">
					<label for="<?php echo sanitize_title( $name ) . '_' . $bundled_item->item_id; ?>"><?php echo wc_attribute_label( $name ); ?> <abbr class="required" title="required">*</abbr></label>
				</td>
				<td class="value">
					<select id="<?php echo esc_attr( sanitize_title( $name ) . '_' . $bundled_item->item_id ); ?>" name="attribute_<?php echo sanitize_title( $name ); ?>">
						<option value=""><?php echo __( 'Choose an option', 'woocommerce' ) ?>&hellip;</option><?php

						if ( is_array( $options ) ) {

							if ( isset( $_REQUEST[ $bundle_fields_prefix . 'bundle_attribute_' . sanitize_title( $name ) . '_' . $bundled_item->item_id ] ) ) {
								$selected_value = $_REQUEST[ $bundle_fields_prefix . 'bundle_attribute_' . sanitize_title( $name ) . '_' . $bundled_item->item_id ];
							} elseif ( isset( $bundled_product_selected_attributes[ sanitize_title( $name ) ] ) ) {
								$selected_value = $bundled_product_selected_attributes[ sanitize_title( $name ) ];
							} else {
								$selected_value = '';
							}

							// Placeholder: Do not show filtered-out (disabled) options

							if ( taxonomy_exists( $name ) ) {

								$terms = wc_bundles_get_product_terms( $bundled_product->id, $name, array( 'fields' => 'all' ) );

								foreach ( $terms as $term ) {

									if ( ! in_array( $term->slug, $options ) ) {
										continue;
									}

									echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $selected_value ), sanitize_title( $term->slug ), false ) . '>' . apply_filters( 'woocommerce_variation_option_name', $term->name ) . '</option>';
								}

							} else {

								foreach ( $options as $option ) {
									echo '<option value="' . esc_attr( sanitize_title( $option ) ) . '" ' . selected( sanitize_title( $selected_value ), sanitize_title( $option ), false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</option>';
								}
							}
						}

					?></select><?php

					if ( sizeof( $bundled_product_attributes ) == $loop ) {
						echo '<a class="reset_variations" href="#reset_' . $bundled_item->item_id . '">' . __( 'Clear selection', 'woocommerce' ) . '</a>';
					}

				?></td>
			</tr><?php
		}

		?></tbody>
	</table><?php

	// Compatibility with plugins that normally hook to woocommerce_before_add_to_cart_button
	do_action( 'woocommerce_bundled_product_add_to_cart', $bundled_product->id, $bundled_item );

	?><div class="single_variation_wrap bundled_item_wrap" style="display:none;">
		<div class="single_variation bundled_item_cart_details"></div>
		<div class="variations_button bundled_item_button">
			<input type="hidden" name="variation_id" value="" /><?php

			wc_get_template( 'single-product/bundled-item-quantity.php', array(
					'bundled_item'         => $bundled_item,
					'bundle_fields_prefix' => $bundle_fields_prefix
				), false, $woocommerce_bundles->woo_bundles_plugin_path() . '/templates/'
			);

		?></div>
	</div>
</div>
