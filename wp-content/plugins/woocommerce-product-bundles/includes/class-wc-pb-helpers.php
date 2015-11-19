<?php
/**
 * Product Bundle Helper Functions.
 *
 * @class 	WC_PB_Helpers
 * @version 4.9.5
 * @since   4.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_PB_Helpers {

	public $wc_option_calculate_taxes;
	public $wc_option_tax_display_shop;
	public $wc_option_prices_include_tax;

	private $variations_cache = array();

	function __construct() {

		global $woocommerce;

		$this->wc_option_calculate_taxes 	= get_option( 'woocommerce_calc_taxes' );
		$this->wc_option_tax_display_shop 	= get_option( 'woocommerce_tax_display_shop' );
		$this->wc_option_prices_include_tax = get_option( 'woocommerce_prices_include_tax' );
	}

	/**
	 * Use it to avoid repeated get_child calls for the same variation.
	 *
	 * @param  int                   $variation_id
	 * @param  WC_Product_Variable   $product
	 * @return WC_Product_Variation
	 */
	function get_variation( $variation_id, $product ) {

		if ( isset( $this->variations_cache[ $variation_id ] ) )
			return $this->variations_cache[ $variation_id ];

		$variation = $product->get_child( $variation_id, array(
			'parent_id' => $product->id,
			'parent' 	=> $product
		) );

		$this->variations_cache[ $variation_id ] = $variation;

		return $variation;
	}

	/**
	 * Bundled product availability that takes quantity into account.
	 *
	 * @param  WC_Product   $product    the product
	 * @param  int          $quantity   the quantity
	 * @return array                    availability data
	 */
	function get_bundled_product_availability( $product, $quantity ) {

		$availability = $class = '';

		if ( $product->managing_stock() ) {

			if ( $product->is_in_stock() && $product->get_total_stock() > get_option( 'woocommerce_notify_no_stock_amount' ) && $product->get_total_stock() >= $quantity ) {

				switch ( get_option( 'woocommerce_stock_format' ) ) {

					case 'no_amount' :
						$availability = __( 'In stock', 'woocommerce' );
					break;

					case 'low_amount' :
						if ( $product->get_total_stock() <= get_option( 'woocommerce_notify_low_stock_amount' ) ) {
							$availability = sprintf( __( 'Only %s left in stock', 'woocommerce' ), $product->get_total_stock() );

							if ( $product->backorders_allowed() && $product->backorders_require_notification() ) {
								$availability .= ' ' . __( '(can be backordered)', 'woocommerce' );
							}
						} else {
							$availability = __( 'In stock', 'woocommerce' );
						}
					break;

					default :
						$availability = sprintf( __( '%s in stock', 'woocommerce' ), $product->get_total_stock() );

						if ( $product->backorders_allowed() && $product->backorders_require_notification() ) {
							$availability .= ' ' . __( '(can be backordered)', 'woocommerce' );
						}
					break;
				}

				$class = 'in-stock';

			} elseif ( $product->backorders_allowed() && $product->backorders_require_notification() ) {

				if ( $product->get_total_stock() >= $quantity || get_option( 'woocommerce_stock_format' ) == 'no_amount' )
					$availability = __( 'Available on backorder', 'woocommerce' );
				else
					$availability = __( 'Available on backorder', 'woocommerce' ) . ' ' . sprintf( __( '(only %s left in stock)', 'woocommerce-product-bundles' ), $product->get_total_stock() );

				$class = 'available-on-backorder';

			} elseif ( $product->backorders_allowed() ) {

				$availability = __( 'In stock', 'woocommerce' );
				$class        = 'in-stock';

			} else {

				if ( $product->is_in_stock() && $product->get_total_stock() > get_option( 'woocommerce_notify_no_stock_amount' ) ) {

					if ( get_option( 'woocommerce_stock_format' ) == 'no_amount' )
						$availability = __( 'Insufficient stock', 'woocommerce-product-bundles' );
					else
						$availability = __( 'Insufficient stock', 'woocommerce-product-bundles' ) . ' ' . sprintf( __( '(only %s left in stock)', 'woocommerce-product-bundles' ), $product->get_total_stock() );

					$class = 'out-of-stock';

				} else {

					$availability = __( 'Out of stock', 'woocommerce' );
					$class        = 'out-of-stock';
				}
			}

		} elseif ( ! $product->is_in_stock() ) {

			$availability = __( 'Out of stock', 'woocommerce' );
			$class        = 'out-of-stock';
		}

		_deprecated_function( 'get_bundled_product_availability', '4.8.8', 'WC_Bundled_Item::get_availability()' );
		return apply_filters( 'woocommerce_get_bundled_product_availability', array( 'availability' => $availability, 'class' => $class ), $product );
	}

	/**
	 * Updates post_meta v1 storage scheme (scattered post_meta) to v2 (serialized post_meta)
	 * @param  int    $bundle_id     bundle product_id
	 * @return void
	 */
	function serialize_bundle_meta( $bundle_id ) {

		global $wpdb;

		$bundled_item_ids 	= maybe_unserialize( get_post_meta( $bundle_id, '_bundled_ids', true ) );
		$default_attributes = maybe_unserialize( get_post_meta( $bundle_id, '_bundle_defaults', true ) );
		$allowed_variations = maybe_unserialize( get_post_meta( $bundle_id, '_allowed_variations', true ) );

		$bundle_data = array();

		foreach ( $bundled_item_ids as $bundled_item_id ) {

			$bundle_data[ $bundled_item_id ] = array();

			$filtered 			= get_post_meta( $bundle_id, 'filter_variations_' . $bundled_item_id, true );
			$o_defaults			= get_post_meta( $bundle_id, 'override_defaults_' . $bundled_item_id, true );
			$hide_thumbnail		= get_post_meta( $bundle_id, 'hide_thumbnail_' . $bundled_item_id, true );
			$item_o_title 		= get_post_meta( $bundle_id, 'override_title_' . $bundled_item_id, true );
			$item_title 		= get_post_meta( $bundle_id, 'product_title_' . $bundled_item_id, true );
			$item_o_desc 		= get_post_meta( $bundle_id, 'override_description_' . $bundled_item_id, true );
			$item_desc			= get_post_meta( $bundle_id, 'product_description_' . $bundled_item_id, true );
			$item_qty			= get_post_meta( $bundle_id, 'bundle_quantity_' . $bundled_item_id, true );
			$discount			= get_post_meta( $bundle_id, 'bundle_discount_' . $bundled_item_id, true );
			$visibility			= get_post_meta( $bundle_id, 'visibility_' . $bundled_item_id, true );

			$sep = explode( '_', $bundled_item_id );

			$bundle_data[ $bundled_item_id ][ 'product_id' ] 				= $sep[0];


			$bundle_data[ $bundled_item_id ][ 'filter_variations' ] 		= $filtered == 'yes' ? 'yes' : 'no';

			if ( isset( $allowed_variations[ $bundled_item_id ] ) )
				$bundle_data[ $bundled_item_id ][ 'allowed_variations' ] 	= $allowed_variations[ $bundled_item_id ];


			$bundle_data[ $bundled_item_id ][ 'override_defaults' ] 		= $o_defaults == 'yes' ? 'yes' : 'no';

			if ( isset( $default_attributes[ $bundled_item_id ] ) )
				$bundle_data[ $bundled_item_id ][ 'bundle_defaults' ] 		= $default_attributes[ $bundled_item_id ];


			$bundle_data[ $bundled_item_id ][ 'hide_thumbnail' ] 			= $hide_thumbnail == 'yes' ? 'yes' : 'no';


			$bundle_data[ $bundled_item_id ][ 'override_title' ] 			= $item_o_title == 'yes' ? 'yes' : 'no';

			if ( $item_o_title == 'yes' )
				$bundle_data[ $bundled_item_id ][ 'product_title' ] 		= $item_title;


			$bundle_data[ $bundled_item_id ][ 'override_description' ] 		= $item_o_desc == 'yes' ? 'yes' : 'no';

			if ( $item_o_desc == 'yes' )
				$bundle_data[ $bundled_item_id ][ 'product_description' ] 	= $item_desc;


			$bundle_data[ $bundled_item_id ][ 'bundle_quantity' ] 			= $item_qty;
			$bundle_data[ $bundled_item_id ][ 'bundle_discount' ] 			= $discount;

			$bundle_data[ $bundled_item_id ][ 'visibility' ] 				= $visibility == 'hidden' ? 'hidden' : 'visible';

			$bundle_data[ $bundled_item_id ][ 'hide_filtered_variations' ] 	= 'no';
		}

		update_post_meta( $bundle_id, '_bundle_data', $bundle_data );

		$wpdb->query( $wpdb->prepare( "DELETE FROM `$wpdb->postmeta` WHERE `post_id` LIKE %s AND (
			`meta_key` LIKE %s OR
			`meta_key` LIKE %s OR
			`meta_key` LIKE %s OR
			`meta_key` LIKE %s OR
			`meta_key` LIKE %s OR
			`meta_key` LIKE %s OR
			`meta_key` LIKE %s OR
			`meta_key` LIKE %s OR
			`meta_key` LIKE %s OR
			`meta_key` LIKE %s OR
			`meta_key` LIKE %s OR
			`meta_key` LIKE ('_bundled_ids') OR
			`meta_key` LIKE ('_bundle_defaults') OR
			`meta_key` LIKE ('_allowed_variations')
		)", $bundle_id, 'filter_variations_%', 'override_defaults_%', 'bundle_quantity_%', 'bundle_discount_%', 'hide_thumbnail_%', 'override_title_%', 'product_title_%', 'override_description_%', 'product_description_%', 'hide_filtered_variations_%', 'visibility_%' ) );

		return $bundle_data;
	}

	/**
	 * Calculates bundled product prices incl. or excl. tax depending on the 'woocommerce_tax_display_shop' setting.
	 *
	 * @param  WC_Product   $product    the product
	 * @param  double       $price      the product price
	 * @return double                   modified product price incl. or excl. tax
	 */
	function get_product_price_incl_or_excl_tax( $product, $price ) {

		if ( $price == 0 ) {
			return $price;
		}

		if ( $this->wc_option_tax_display_shop == 'excl' ) {
			$product_price = $product->get_price_excluding_tax( 1, $price );
		} else {
			$product_price = $product->get_price_including_tax( 1, $price );
		}

		return $product_price;
	}

	/**
	 * Loads variation ids for a given variable product.
	 *
	 * @param  int    $item_id
	 * @return array
	 */
	public function get_product_variations( $item_id ) {

		$transient_name = 'wc_product_children_ids_' . $item_id;

        if ( false === ( $variations = get_transient( $transient_name ) ) ) {

			$args = array(
				'post_type'   => 'product_variation',
				'post_status' => array( 'publish' ),
				'numberposts' => -1,
				'orderby'     => 'menu_order',
				'order'       => 'asc',
				'post_parent' => $item_id,
				'fields'      => 'ids'
			);

			$variations = get_posts( $args );
		}

		return $variations;
	}

	/**
	 * Return a formatted product title based on variation id.
	 *
	 * @param  int    $item_id
	 * @return string
	 */
	public function get_product_variation_title( $variation_id ) {

		$variation = WC_PB_Core_Compatibility::wc_get_product( $variation_id );

		if ( ! $variation )
			return false;

		$description = wc_get_formatted_variation( $variation->get_variation_attributes(), true );

		$title = $variation->get_title();
		$sku   = $variation->get_sku();

		if ( $sku ) {
			$sku = sprintf( __( '(SKU: %s)', 'woocommerce-product-bundles' ), $sku );
		}

		return $this->format_product_title( $title, $sku, $description );
	}

	/**
	 * Return a formatted product title based on id.
	 *
	 * @param  int    $product_id
	 * @return string
	 */
	public function get_product_title( $product_id, $suffix = '' ) {

		$title = get_the_title( $product_id );

		if ( $suffix ) {
			$title = sprintf( _x( '%1$s %2$s', 'product title followed by suffix', 'woocommerce-product-bundles' ), $title, $suffix );
		}

		$sku = get_post_meta( $product_id, '_sku', true );

		if ( ! $title ) {
			return false;
		}

		if ( $sku ) {
			$sku = sprintf( __( '(SKU: %s)', 'woocommerce-product-bundles' ), $sku );
		} else {
			$sku = '';
		}

		return $this->format_product_title( $title, $sku );
	}

	/**
	 * Format a product title.
	 *
	 * @param  string $title
	 * @param  string $sku
	 * @param  string $meta
	 * @return string
	 */
	public function format_product_title( $title, $sku = '', $meta = '' ) {

		if ( $sku && $meta )
			$title = sprintf( _x( '%1$s &mdash; %2$s %3$s', 'product title followed by sku and meta', 'woocommerce-product-bundles' ), $title, $meta, $sku );
		elseif ( $sku )
			$title = sprintf( _x( '%1$s %2$s', 'product title followed by sku', 'woocommerce-product-bundles' ), $title, $sku );
		elseif ( $meta )
			$title = sprintf( _x( '%1$s &mdash; %2$s', 'product title followed by meta', 'woocommerce-product-bundles' ), $title, $meta );

		return $title;
	}

	/**
	 * Format a product title incl qty, price and suffix.
	 *
	 * @param  string $title
	 * @param  string $qty
	 * @param  string $price
	 * @param  string $suffix
	 * @return string
	 */
	public static function format_product_shop_title( $title, $qty = '', $price = '', $suffix = '' ) {

		$quantity_string = '';
		$price_string    = '';
		$suffix_string   = '';

		if ( $qty ) {
			$quantity_string = sprintf( _x( ' &times; %s', 'qty string', 'woocommerce-product-bundles' ), $qty );
		}

		if ( $price ) {
			$price_string = sprintf( _x( ' &ndash; %s', 'price suffix', 'woocommerce-product-bundles' ), $price );
		}

		if ( $suffix ) {
			$suffix_string = sprintf( _x( ' &ndash; %s', 'suffix', 'woocommerce-product-bundles' ), $suffix );
		}

		$title_string = sprintf( _x( '%1$s%2$s%3$s%4$s', 'title, quantity, price, suffix', 'woocommerce-product-bundles' ), $title, $quantity_string, $price_string, $suffix_string );

		return $title_string;
	}
}
