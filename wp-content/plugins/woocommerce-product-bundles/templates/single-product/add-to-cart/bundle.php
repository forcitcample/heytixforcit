<?php
/**
 * Product bundle add to cart template.
 *
 * @version 4.8.8
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $woocommerce, $product, $woocommerce_bundles;

do_action( 'woocommerce_before_add_to_cart_form' ); ?>

<form method="post" enctype="multipart/form-data" class="bundle_form" ><?php

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
							'bundle_fields_prefix' => ''
						), false, $woocommerce_bundles->woo_bundles_plugin_path() . '/templates/' );
					}

					if ( $bundled_product->product_type === 'simple' || $bundled_product->product_type === 'subscription' ) {

						// Simple Product template
						wc_get_template( 'single-product/bundled-product-simple.php', array(
							'bundled_product'      => $bundled_product,
							'bundled_item'         => $bundled_item,
							'bundle'               => $product,
							'bundle_fields_prefix' => '',
							'availability'         => $availability
						), false, $woocommerce_bundles->woo_bundles_plugin_path() . '/templates/' );

					} elseif ( $bundled_product->product_type === 'variable' ) {

						// Variable Product template
						wc_get_template( 'single-product/bundled-product-variable.php', array(
							'bundled_product'                     => $bundled_product,
							'bundled_item'                        => $bundled_item,
							'bundle'                              => $product,
							'bundle_fields_prefix'                => '',
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

		?><div class="cart bundle_data bundle_data_<?php echo $product->id; ?>" data-button_behaviour="<?php echo esc_attr( apply_filters( 'woocommerce_bundles_button_behaviour', 'new', $product ) ); ?>" data-bundle_price_data="<?php echo esc_attr( json_encode( $bundle_price_data ) ); ?>" data-bundle-id="<?php echo $product->id; ?>"><?php

			do_action( 'woocommerce_before_add_to_cart_button' );

			?><div class="bundle_wrap" style="<?php echo apply_filters( 'woocommerce_bundles_button_behaviour', 'new', $product ) == 'new' ? '' : 'display:none'; ?>">
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

							?><input type="hidden" name="bundle_variation_id_<?php echo $bundled_item_id; ?>" class="bundle_variation_id_<?php echo $bundled_item_id; ?>" value="" /><?php

							foreach ( $attributes[ $bundled_item_id ] as $name => $options ) { ?>
								<input type="hidden" name="bundle_attribute_<?php echo sanitize_title( $name ); ?>_<?php echo $bundled_item_id; ?>" class="bundle_attribute_<?php echo sanitize_title( $name ); ?>_<?php echo $bundled_item_id; ?>" value=""><?php
							}
						}
					}

					do_action( 'woocommerce_bundles_add_to_cart_button' );

				?></div>
				<input type="hidden" name="add-to-cart" value="<?php echo $product->id; ?>" />
			</div>

			<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

		</div><?php

	} else {
		?><div class="bundle_unavailable"><?php
			echo __( 'This product is temporarily unavailable.', 'woocommerce-product-bundles' );
		?></div><?php
	}

?></form>

<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>
