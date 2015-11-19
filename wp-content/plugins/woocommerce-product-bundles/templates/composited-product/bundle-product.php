<?php
/**
 * Composited Product Bundle Template.
 *
 * @version  4.8.8
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $woocommerce, $woocommerce_bundles, $woocommerce_composite_products;

// Current selection title
if ( $hide_product_title !== 'yes' ) {

	if ( $show_selection_ui ) {
		?><p class="component_section_title">
			<label class="selected_option_label"><?php echo __( 'Your selection:', 'woocommerce-composite-products' ); ?>
			</label>
		</p><?php
	}

	wc_composite_get_template( 'composited-product/title.php', array(
		'title'      => $product->get_title(),
		'product_id' => $product->id,
		'quantity'   => $quantity_min == $quantity_max && $quantity_min > 1 && $product->sold_individually !== 'yes' ? $quantity_min : ''
	), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
}

// Clear current selection
if ( $show_selection_ui ) {
	?><p class="component_section_title clear_component_options_wrapper">
		<a class="clear_component_options" href="#clear_component"><?php
			echo __( 'Clear selection', 'woocommerce-composite-products' );
		?></a>
	</p><?php
}

if ( $hide_product_thumbnail !== 'yes' ) {
	wc_composite_get_template( 'composited-product/image.php', array(
		'product_id' => $product->id
	), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
}

?><div class="details component_data" data-component_set="" data-price="0" data-regular_price="0" data-product_type="bundle" data-custom="<?php echo esc_attr( json_encode( $custom_data ) ); ?>"><?php

	if ( $hide_product_description !== 'yes' ) {
		wc_composite_get_template( 'composited-product/excerpt.php', array(
			'product_description' => $product->post->post_excerpt,
			'product_id'          => $product->id
		), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
	}

	foreach ( $bundled_items as $bundled_item ) {

		$bundled_product = $bundled_item->product;

		?><div class="bundled_product bundled_product_summary product <?php echo $bundled_item->get_classes(); ?>" style="<?php echo ( ! $bundled_item->is_visible() ? 'display:none;' : '' ); ?>" ><?php

			// Title template
			wc_get_template( 'single-product/bundled-item-title.php', array(
				'quantity'     => $bundled_item->get_quantity(),
				'title'        => $bundled_item->get_title(),
				'optional'     => $bundled_item->is_optional(),
				'bundled_item' => $bundled_item,
			), false, $woocommerce_bundles->woo_bundles_plugin_path() . '/templates/' );


			if ( $bundled_item->is_visible() ) {

				// Image template
				if ( $bundled_item->is_thumbnail_visible() ) {
					wc_get_template( 'single-product/bundled-item-image.php', array( 'post_id' => $bundled_product->id ), false, $woocommerce_bundles->woo_bundles_plugin_path() . '/templates/' );
				}
			}

			?><div class="details"><?php

				// Description template
				wc_get_template( 'single-product/bundled-item-description.php', array(
					'description' => $bundled_item->get_description()
				), false, $woocommerce_bundles->woo_bundles_plugin_path() . '/templates/' );

				if ( $bundled_item->is_purchasable() ) {

					$availability = $bundled_item->get_availability();

					$bundled_item->add_price_filters();

					if ( $bundled_item->is_optional() ) {

						// Optional checkbox template
						wc_get_template( 'single-product/bundled-item-optional.php', array(
							'quantity'             => $bundled_item->get_quantity(),
							'bundled_item'         => $bundled_item,
							'bundle_fields_prefix' => 'component_' . $component_id . '_'
						), false, $woocommerce_bundles->woo_bundles_plugin_path() . '/templates/' );
					}

					if ( $bundled_product->product_type === 'simple' ) {

						// Simple Product template
						wc_get_template( 'single-product/bundled-product-simple.php', array(
							'bundled_product'      => $bundled_product,
							'bundled_item'         => $bundled_item,
							'bundle'               => $product,
							'bundle_fields_prefix' => 'component_' . $component_id . '_',
							'availability'         => $availability
						), false, $woocommerce_bundles->woo_bundles_plugin_path() . '/templates/' );

					} elseif ( $bundled_product->product_type === 'variable' ) {

						// Variable Product template
						wc_get_template( 'single-product/bundled-product-variable.php', array(
							'bundled_product'                     => $bundled_product,
							'bundled_item'                        => $bundled_item,
							'bundle'                              => $product,
							'bundle_fields_prefix'                => 'component_' . $component_id . '_',
							'availability'                        => $availability,
							'bundled_product_attributes'          => $attributes[ $bundled_item->item_id ],
							'bundled_product_variations'          => $available_variations[ $bundled_item->item_id ],
							'bundled_product_selected_attributes' => $selected_attributes[ $bundled_item->item_id ]
						), false, $woocommerce_bundles->woo_bundles_plugin_path() . '/templates/' );
					}

					$bundled_item->remove_price_filters();

				} else {
					echo __( 'Sorry, this item is not available at the moment.', 'woocommerce-product-bundles' );
				}

			?></div>
		</div><?php
	}

	if ( $product->is_purchasable() ) {

		?><div class="cart bundle_data bundle_data_<?php echo $product->id; ?>" data-button_behaviour="<?php echo esc_attr( apply_filters( 'woocommerce_bundles_button_behaviour', 'old', $product ) ); ?>" data-bundle_price_data="<?php echo esc_attr( json_encode( $bundle_price_data ) ); ?>" data-bundle-id="<?php echo $product->id; ?>"><?php

			// Add-ons
			do_action( 'woocommerce_composite_product_add_to_cart', $product->id, $component_id, $product );

			?><div class="bundle_wrap component_wrap" style="<?php echo apply_filters( 'woocommerce_bundles_button_behaviour', 'old', $product ) == 'new' ? '' : 'display:none'; ?>">
				<div class="bundle_price"></div><?php

				// Bundle Availability
				$availability = $product->get_availability();

				if ( $availability[ 'availability' ] ) {
					echo apply_filters( 'woocommerce_stock_html', '<p class="stock ' . $availability[ 'class' ] . '">' . $availability[ 'availability' ] . '</p>', $availability[ 'availability' ] );
				}

				?><div class="bundle_button"><?php

					foreach ( $bundled_items as $bundled_item_id => $bundled_item ) {

						$bundled_item_id = $bundled_item->item_id;
						$bundled_product = $bundled_item->product;

						if ( $bundled_product->product_type === 'variable' ) {

							?><input type="hidden" name="component_<?php echo $component_id; ?>_bundle_variation_id_<?php echo $bundled_item_id; ?>" class="bundle_variation_id_<?php echo $bundled_item_id; ?>" value="" /><?php

							foreach ( $attributes[ $bundled_item_id ] as $name => $options ) { ?>
								<input type="hidden" name="component_<?php echo $component_id; ?>_bundle_attribute_<?php echo sanitize_title( $name ); ?>_<?php echo $bundled_item_id; ?>" class="bundle_attribute_<?php echo sanitize_title( $name ); ?>_<?php echo $bundled_item_id; ?>" value=""><?php
							}
						}
					}

					wc_composite_get_template( 'composited-product/quantity.php', array(
						'quantity_min'      => $quantity_min,
						'quantity_max'      => $quantity_max,
						'component_id'      => $component_id,
						'product'           => $product,
						'composite_product' => $composite_product
					), '', $woocommerce_composite_products->plugin_path() . '/templates/' );

				?></div>
			</div>
		</div><?php
	}
?></div>
