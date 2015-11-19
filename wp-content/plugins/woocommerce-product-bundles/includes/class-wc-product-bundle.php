<?php
/**
 * Product Bundle Class.
 * Extends the WC_Product class to calculate html price strings and availability by taking into account the prices and availability of all bundled products.
 *
 * @class 	WC_Product_Bundle
 * @version 4.9.5
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Product_Bundle extends WC_Product {

	public $bundle_data;

	private $bundled_items;
	private $is_synced;

	public $min_price;
	public $max_price;

	public $min_bundle_price;
	public $max_bundle_price;
	public $min_bundle_regular_price;
	public $max_bundle_regular_price;

	public $min_bundle_price_excl_tax;
	public $min_bundle_price_incl_tax;

	public $per_product_pricing_active;
	public $per_product_shipping_active;

	private $all_items_purchasable;
	private $all_items_sold_individually;
	private $all_items_in_stock;
	private $has_items_on_backorder;
	private $on_sale;

	private $bundle_price_data;

	private $contains_nyp;
	private $is_nyp;

	private $contains_sub;
	private $sub_id;

	private $contains_optional;
	private $contains_min_max_quantities;

	private $has_item_with_variables;
	private $all_items_visible;


	function __construct( $bundle ) {

		global $woocommerce_bundles;

		$this->product_type = 'bundle';

		parent::__construct( $bundle );

		$this->bundle_data = get_post_meta( $this->id, '_bundle_data', true );

		$bundled_item_ids = get_post_meta( $this->id, '_bundled_ids', true );

		// Update from 3.X
		if ( empty( $this->bundle_data ) && ! empty( $bundled_item_ids ) ) {
			$this->bundle_data = $woocommerce_bundles->helpers->serialize_bundle_meta( $this->id );
		}

		$this->contains_nyp                = false;
		$this->is_nyp                      = false;

		$this->contains_sub                = false;

		$this->contains_optional           = false;
		$this->contains_min_max_quantities = false;

		$this->on_sale                     = false;

		$this->has_item_with_variables     = false;
		$this->all_items_visible           = true;

		$this->all_items_sold_individually = true;
		$this->all_items_in_stock          = true;
		$this->all_items_purchasable       = true;
		$this->has_items_on_backorder      = false;

		$this->per_product_pricing_active  = ( get_post_meta( $this->id, '_per_product_pricing_active', true ) == 'yes' ) ? true : false;
		$this->per_product_shipping_active = ( get_post_meta( $this->id, '_per_product_shipping_active', true ) == 'yes' ) ? true : false;

		$this->min_price = get_post_meta( $this->id, '_min_bundle_price', true );
		$this->max_price = get_post_meta( $this->id, '_max_bundle_price', true );

		if ( $this->is_priced_per_product() ) {
			$this->price = 0;
		}

		// NYP
		if ( $woocommerce_bundles->compatibility->is_nyp( $this ) ) {
			$this->is_nyp = true;
		}

		$this->is_synced = false;
	}

	/**
	 * Load bundled items.
	 *
	 * @return void
	 * @since  4.7.0
	 */
	private function load_bundled_items() {

		global $woocommerce_bundles;

		foreach ( $this->bundle_data as $bundled_item_id => $bundled_item_data ) {

			$bundled_item = new WC_Bundled_Item( $bundled_item_id, $this );

			if ( $bundled_item->exists() ) {
				$this->bundled_items[ $bundled_item_id ] = $bundled_item;
			}
		}
	}

	/**
	 * Calculates min and max prices and availability status based on the bundled product data.
	 * Takes into account any defined variation filters.
	 *
	 * @return void
	 * @since  4.2.0
	 */
	public function sync_bundle() {

		global $woocommerce_bundles;

		if ( ! empty( $this->bundle_data ) )
			$this->load_bundled_items();

		$this->min_bundle_price          = '';
		$this->max_bundle_price          = '';
		$this->min_bundle_regular_price  = '';
		$this->max_bundle_regular_price  = '';

		$this->min_bundle_price_excl_tax = '';
		$this->min_bundle_price_incl_tax = '';

		if ( empty( $this->bundled_items ) ) {
			return;
		}

		foreach ( $this->bundled_items as $bundled_item ) {

			if ( ! $bundled_item->is_sold_individually() ) {
				$this->all_items_sold_individually = false;
			}

			if ( $bundled_item->is_optional() ) {
				$this->contains_optional = true;
			}

			if ( $bundled_item->get_quantity( 'max' ) > $bundled_item->get_quantity() ) {
				$this->contains_min_max_quantities = true;
			}

			if ( $bundled_item->is_out_of_stock() && ! $bundled_item->is_optional() ) {
				$this->all_items_in_stock = false;
			}

			if ( $bundled_item->is_on_backorder() && ! $bundled_item->is_optional() ) {
				$this->has_items_on_backorder = true;
			}

			if ( ! $bundled_item->is_purchasable() && ! $bundled_item->is_optional() && $bundled_item->get_quantity( 'min' ) !== 0 ) {
				$this->all_items_purchasable = false;
			}

			if ( $bundled_item->is_on_sale() ) {
				$this->on_sale = true;
			}

			if ( $bundled_item->is_nyp() ) {
				$this->contains_nyp = true;
			}

			if ( $bundled_item->is_sub() ) {
				$this->contains_sub = true;
				$this->sub_id       = $bundled_item->item_id;
			}

			// Significant cost due to get_product_addons - skip this in the admin area since has_item_with_variables is only used to modify add to cart button behaviour

			if ( ! is_admin() && ! $bundled_item->is_optional() && $bundled_item->has_variables() ) {
				$this->has_item_with_variables = true;
			}

			if ( ! $bundled_item->is_visible() ) {
				$this->all_items_visible = false;
			}

			// Sync prices
			if ( $this->is_priced_per_product() ) {

				$bundled_item_qty_min = $bundled_item->is_optional() ? 0 : $bundled_item->get_quantity();
				$bundled_item_qty_max = $bundled_item->get_quantity( 'max' );

				$this->min_bundle_price          = $this->min_bundle_price + $bundled_item_qty_min * $bundled_item->min_price;
				$this->min_bundle_regular_price  = $this->min_bundle_regular_price + $bundled_item_qty_min * $bundled_item->min_regular_price;

				$this->max_bundle_price          = $this->max_bundle_price + $bundled_item_qty_max * $bundled_item->max_price;
				$this->max_bundle_regular_price  = $this->max_bundle_regular_price + $bundled_item_qty_max * $bundled_item->max_regular_price;


				$this->min_bundle_price_excl_tax = $this->min_bundle_price_excl_tax + $bundled_item_qty_min * $bundled_item->min_price_excl_tax;
				$this->min_bundle_price_incl_tax = $this->min_bundle_price_incl_tax + $bundled_item_qty_min * $bundled_item->min_price_incl_tax;
			}
		}

		if ( $this->is_priced_per_product() ) {

			if ( $this->contains_nyp ) {
				$this->max_bundle_price = '';
			}

		} else {

			if ( $this->is_nyp() ) {

				$this->min_bundle_price = get_post_meta( $this->id, '_min_price', true );
				$this->max_bundle_price = '';

			} else {

				$this->min_bundle_price = $this->max_bundle_price = $this->price;
			}
		}

		if ( apply_filters( 'woocommerce_bundles_update_price_meta', true, $this ) ) {

			$product_price = get_post_meta( $this->id, '_price', true );

			if ( $this->min_price != $this->min_bundle_price ) {
				update_post_meta( $this->id, '_min_bundle_price', $this->min_bundle_price );
			}

			if ( $this->max_price != $this->max_bundle_price ) {
				update_post_meta( $this->id, '_max_bundle_price', $this->max_bundle_price );
			}

			if ( ! is_admin() && $product_price != $this->min_bundle_price ) {
				update_post_meta( $this->id, '_price', $this->min_bundle_price );
			}
		}

		$this->is_synced = true;
	}

	/**
	 * Indicates if the bundle has been synced and all bundled contents loaded.
	 *
	 * @return boolean
	 */
	public function is_synced() {

		return $this->is_synced;
	}

	/**
	 * Stores bundle pricing strategy data that is passed to JS.
	 *
	 * @return void
	 * @since  4.7.0
	 */
	private function load_price_data() {

		global $woocommerce_bundles;

		$this->bundle_price_data = array();

		$this->bundle_price_data[ 'per_product_pricing' ]  = $this->is_priced_per_product();
		$this->bundle_price_data[ 'bundle_is_composited' ] = apply_filters( 'woocommerce_bundle_is_composited', false, $this );
		$this->bundle_price_data[ 'show_free_string' ]     = $this->is_priced_per_product() ? apply_filters( 'woocommerce_bundle_show_free_string', false, $this ) : true;
		$this->bundle_price_data[ 'prices' ]               = array();
		$this->bundle_price_data[ 'regular_prices' ]       = array();
		$this->bundle_price_data[ 'total' ]                = $this->get_price() === '' ? '' : $woocommerce_bundles->helpers->get_product_price_incl_or_excl_tax( $this, $this->get_price() );
		$this->bundle_price_data[ 'regular_total' ]        = $woocommerce_bundles->helpers->get_product_price_incl_or_excl_tax( $this, $this->get_regular_price() );

		if ( empty( $this->bundled_items ) ) {
			return;
		}

		foreach ( $this->bundled_items as $bundled_item ) {

			if ( ! $bundled_item->is_purchasable() ) {
				continue;
			}

			$this->bundle_price_data[ 'prices' ][ $bundled_item->item_id ]         = $bundled_item->min_price;
			$this->bundle_price_data[ 'regular_prices' ][ $bundled_item->item_id ] = $bundled_item->min_regular_price;
		}

		if ( $this->is_priced_per_product() && $this->contains_sub ) {

			add_filter( 'woocommerce_get_bundle_price_html', array( $this, 'remove_bundle_price_up_front_part' ), 100, 2 );
			$this->bundle_price_data[ 'price_string' ] = $this->get_price_html();
			remove_filter( 'woocommerce_get_bundle_price_html', array( $this, 'remove_bundle_price_up_front_part' ), 100, 2 );

		} else {

			$this->bundle_price_data[ 'price_string' ] = '%s';
		}
	}

	/**
	 * Gets price data array. Contains localized strings and price data passed to JS.
	 *
	 * @return array localized strings and price data passed to JS
	 */
	public function get_bundle_price_data() {

		if ( ! $this->is_synced() ) {
			$this->sync_bundle();
		}

		$this->load_price_data();

		return $this->bundle_price_data;
	}

	/**
	 * Bundle is a NYP product.
	 *
	 * @return boolean
	 */
	public function is_nyp() {

		return $this->is_nyp;
	}

	/**
	 * Bundle contains NYP products.
	 *
	 * @return boolean
	 */
	public function contains_nyp() {

		if ( ! $this->is_synced() ) {
			$this->sync_bundle();
		}

		return $this->contains_nyp;
	}

	/**
	 * Bundle contains optional items.
	 *
	 * @return boolean
	 */
	public function contains_optional() {

		if ( ! $this->is_synced() ) {
			$this->sync_bundle();
		}

		return $this->contains_optional;
	}

	/**
	 * Bundle is priced per product.
	 * @return boolean
	 */
	public function is_priced_per_product() {

		$is_ppp = false;

		if ( $this->per_product_pricing_active ) {
			$is_ppp = true;
		}

		return apply_filters( 'woocommerce_bundle_is_priced_per_product', $is_ppp, $this );
	}

	/**
	 * Bundle is shipped per product.
	 * @return boolean
	 */
	public function is_shipped_per_product() {

		$is_spp = false;

		if ( $this->per_product_shipping_active ) {
			$is_spp = true;
		}

		return apply_filters( 'woocommerce_bundle_is_shipped_per_product', $is_spp, $this );
	}

	/**
	 * Gets the attributes of all variable bundled items.
	 *
	 * @return array attributes array
	 */
	public function get_bundle_variation_attributes() {

		if ( ! $this->is_synced() ) {
			$this->sync_bundle();
		}

		if ( empty( $this->bundled_items ) ) {
			return array();
		}

		$bundle_attributes = array();

		foreach ( $this->bundled_items as $bundled_item ) {
			$bundle_attributes[ $bundled_item->item_id ] = $bundled_item->get_product_variation_attributes();
		}

		return $bundle_attributes;
	}

	/**
	 * Gets default (overriden) selections for variable product attributes.
	 *
	 * @return array default attribute selections.
	 */
	public function get_selected_bundle_variation_attributes() {

		if ( ! $this->is_synced() ) {
			$this->sync_bundle();
		}

		if ( empty( $this->bundled_items ) ) {
			return array();
		}

		$seleted_bundle_attributes = array();

		foreach ( $this->bundled_items as $bundled_item ) {
			$seleted_bundle_attributes[ $bundled_item->item_id ] = $bundled_item->get_selected_product_variation_attributes();
		}

		return $seleted_bundle_attributes;
	}

	/**
	 * Gets product variation data which is passed to JS.
	 *
	 * @return array variation data array
	 */
	public function get_available_bundle_variations() {

		if ( ! $this->is_synced() ) {
			$this->sync_bundle();
		}

		if ( empty( $this->bundled_items ) ) {
			return array();
		}

		$bundle_variations = array();

		foreach ( $this->bundled_items as $bundled_item )
			$bundle_variations[ $bundled_item->item_id ] = $bundled_item->get_product_variations();

		return $bundle_variations;
	}

	/**
	 * Gets all bundled items.
	 *
	 * @return array  of WC_Bundled_Item objects
	 */
	public function get_bundled_items() {

		if ( ! $this->is_synced() ) {
			$this->sync_bundle();
		}

		if ( ! empty( $this->bundled_items ) ) {
			return $this->bundled_items;
		}

		return false;
	}

	/**
	 * Checks if a specific bundled item exists.
	 *
	 * @param  $bundled_item_id
	 * @return boolean
	 */
	public function has_bundled_item( $bundled_item_id ) {

		if ( ! empty( $this->bundle_data ) && isset( $this->bundle_data[ $bundled_item_id ] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Gets a specific bundled item.
	 *
	 * @param  $bundled_item_id
	 * @return WC_Bundled_Item
	 */
	public function get_bundled_item( $bundled_item_id ) {

		if ( ! empty( $this->bundle_data ) && isset( $this->bundle_data[ $bundled_item_id ] ) ) {
			if ( isset( $this->bundled_items[ $bundled_item_id ] ) ) {
				return $this->bundled_items[ $bundled_item_id ];
			} else {
				return new WC_Bundled_Item( $bundled_item_id, $this );
			}
		}

		return false;
	}

	/**
	 * In per-product pricing mode, get_price() normally returns zero, since the container item does not have a price of its own.
	 *
	 * @return 	double
	 */
	public function get_price() {

		if ( $this->is_priced_per_product() ) {
			return apply_filters( 'woocommerce_bundle_get_price', (double) $this->price, $this );
		} else {
			return parent::get_price();
		}
	}

	/**
	 * In per-product pricing mode, get_regular_price() normally returns zero, since the container item does not have a price of its own.
	 *
	 * @return 	double
	 */
	public function get_regular_price() {

		if ( $this->is_priced_per_product() ) {
			return ( double ) 0;
		} else {
			return parent::get_regular_price();
		}
	}

	/**
	 * Getter for min_bundle_price.
	 *
	 * @return double
	 */
	public function get_min_bundle_price() {

		if ( $this->is_priced_per_product() ) {

			if ( ! $this->is_synced() ) {
				$this->sync_bundle();
			}

			$price = $this->min_bundle_price;

		} else {

			$price = parent::get_price();
		}

		return $price;

	}

	/**
	 * Getter for min_bundle_regular_price.
	 *
	 * @return double
	 */
	public function get_min_bundle_regular_price() {

		if ( $this->is_priced_per_product() ) {

			if ( ! $this->is_synced() ) {
				$this->sync_bundle();
			}

			$price = $this->min_bundle_regular_price;

		} else {

			$price = parent::get_regular_price();
		}

		return $price;

	}

	/**
	 * Getter for max_bundle_price.
	 *
	 * @return double
	 */
	public function get_max_bundle_price() {

		if ( $this->is_priced_per_product() ) {

			if ( ! $this->is_synced() ) {
				$this->sync_bundle();
			}

			$price = $this->max_bundle_price;

		} else {

			$price = parent::get_price();
		}

		return $price;
	}

	/**
	 * Getter for max_bundle_regular_price.
	 *
	 * @return double
	 */
	public function get_max_bundle_regular_price() {

		if ( $this->is_priced_per_product() ) {

			if ( ! $this->is_synced() ) {
				$this->sync_bundle();
			}

			$price = $this->max_bundle_regular_price;

		} else {

			$price = parent::get_regular_price();
		}

		return $price;
	}

	/**
	 * Getter for min_bundle_price_incl_tax.
	 *
	 * @return double
	 */
	public function get_min_bundle_price_incl_tax() {

		if ( $this->is_priced_per_product() ) {

			if ( ! $this->is_synced() ) {
				$this->sync_bundle();
			}

			$price = $this->min_bundle_price_incl_tax;

		} else {

			$price = parent::get_price_including_tax( 1, parent::get_price() );
		}

		return $price;
	}

	/**
	 * Getter for min_bundle_price_excl_tax.
	 *
	 * @return double
	 */
	public function get_min_bundle_price_excl_tax() {

		if ( $this->is_priced_per_product() ) {

			if ( ! $this->is_synced() ) {
				$this->sync_bundle();
			}

			$price = $this->min_bundle_price_incl_tax;

		} else {

			$price = parent::get_price_excluding_tax( 1, parent::get_price() );
		}

		return $price;
	}

	/**
	 * Prices incl. or excl. tax are calculated based on the bundled products prices, so get_price_suffix() must be overridden to return the correct field in per-product pricing mode.
	 *
	 * @return 	string    modified price html suffix
	 */
	public function get_price_suffix() {

	 	global $woocommerce_bundles;

		if ( $this->is_priced_per_product() ) {

			$price_display_suffix = get_option( 'woocommerce_price_display_suffix' );

			if ( $price_display_suffix ) {
				$price_display_suffix = ' <small class="woocommerce-price-suffix">' . $price_display_suffix . '</small>';

				$find = array(
					'{price_including_tax}',
					'{price_excluding_tax}'
				);

				$replace = array(
					wc_price( $this->min_bundle_price_incl_tax ),
					wc_price( $this->min_bundle_price_excl_tax ),
				);

				$price_display_suffix = str_replace( $find, $replace, $price_display_suffix );
			}

			return apply_filters( 'woocommerce_get_price_suffix', $price_display_suffix, $this );

		} else {

			return parent::get_price_suffix();
		}

	}

	/**
	 * Prepares the "up front" price html part for use by JS by replacing it with a %s.
	 *
	 * @return string
	 */
	public function remove_bundle_price_up_front_part( $price, $product ) {
		if ( $product->id == $this->id ) {
			return '%s';
		}

		return $price;
	}

	/**
	 * Makes a subscription product temporarily appear as simple to isolate the recurring price html string.
	 *
	 * @param  array                   $include
	 * @param  WC_Product_Subscription $product
	 * @return array
	 */
	public function isolate_recurring_price_html( $include ) {

		$include[ 'subscription_period' ] = false;
		$include[ 'subscription_length' ] = false;
		$include[ 'sign_up_fee' ]         = false;
		$include[ 'trial_length' ]        = false;

		return $include;
	}

	/**
	 * Apply subscriptions-related suffix.
	 *
	 * @return string
	 */
	public function apply_subs_price_html( $price ) {

		if ( ! empty( $this->bundled_items ) ) {

			$subs_details            = array();
			$non_optional_subs_exist = false;

			foreach ( $this->bundled_items as $bundled_item_id => $bundled_item ) {

				if ( $bundled_item->is_sub() ) {

					$bundled_item->add_price_filters();

					add_filter( 'woocommerce_subscriptions_product_price_string_inclusions', array( $this, 'isolate_recurring_price_html' ), 100 );
					$sub_isolated_price = str_replace( '<span class="subscription-details"></span>', '', $bundled_item->product->get_price_html() );
					remove_filter( 'woocommerce_subscriptions_product_price_string_inclusions', array( $this, 'isolate_recurring_price_html' ), 100 );

					$sub_price_html = WC_Subscriptions_Product::get_price_string( $bundled_item->product, array( 'price' => $sub_isolated_price, 'sign_up_fee' => false, 'subscription_length' => false, 'trial_length' => false ) );

					if ( $bundled_item->is_optional() ) {
						$style = 'style="display:none"';
					} else {
						$non_optional_subs_exist = true;
						$style = '';
					}

					if ( count( $subs_details ) > 0 ) {
						$plus = '<span class="plus"> + </span>';
					} else {
						$plus = '';
					}

					$subs_details[] = '<span class="bundled_sub_price_html bundled_sub_price_html_' . $bundled_item_id . '"' . $style . '>' . $plus . $sub_price_html . '</span>';

					$bundled_item->remove_price_filters();
				}
			}

			if ( sizeof( $subs_details ) ) {

				$subs_details_html = implode( '', $subs_details );

				if ( $this->min_bundle_regular_price != 0 ) {
					$price = sprintf( _x( '%1$s<span class="bundled_subscriptions_price_html" %2$s> now,</br>then %3$s</span>', 'subscription price html suffix', 'woocommerce-product-bundles' ), $price, $non_optional_subs_exist ? '' : 'style="display:none"', $subs_details_html );
				} else {
					$price = '<span class="bundled_subscriptions_price_html">' . $subs_details_html . '</span>';
				}
			}
		}

		return $price;
	}

	/**
	 * Returns range style html price string without min and max.
	 *
	 * @param  mixed    $price    default price
	 * @return string             overridden html price string (old style)
	 */
	public function get_price_html( $price = '' ) {

		global $woocommerce_bundles;

		if ( ! $this->is_purchasable() ) {
			return apply_filters( 'woocommerce_bundle_empty_price_html', '', $this );
		}

		if ( $this->is_priced_per_product() ) {

			if ( ! $this->is_synced() ) {
				$this->sync_bundle();
			}

			if ( apply_filters( 'woocommerce_bundle_force_old_style_price_html', false, $this ) || apply_filters( 'woocommerce_bundle_show_free_string', false, $this ) ) {

				$price = $this->get_old_style_price_html();

			} else {

				// Get the price
				if ( $this->min_bundle_price === '' ) {

					$price = apply_filters( 'woocommerce_bundle_empty_price_html', '', $this );

				} else {

					// Main price
					$prices = array( $this->min_bundle_price, $this->max_bundle_price );

					if ( $this->contains_nyp || $this->contains_optional || $this->contains_min_max_quantities ) {
						$price = wc_price( $prices[0] );
					} else {
						$price = $prices[0] !== $prices[1] ? sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'woocommerce' ), wc_price( $prices[0] ), wc_price( $prices[1] ) ) : wc_price( $prices[0] );
					}

					// Sale
					$prices = array( $this->min_bundle_regular_price, $this->max_bundle_regular_price );

					if ( $this->contains_nyp || $this->contains_optional || $this->contains_min_max_quantities ) {
						$saleprice = wc_price( $prices[0] );
					} else {
						sort( $prices );
						$saleprice = $prices[0] !== $prices[1] ? sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'woocommerce' ), wc_price( $prices[0] ), wc_price( $prices[1] ) ) : wc_price( $prices[0] );
					}

					if ( $price !== $saleprice ) {
						$price = apply_filters( 'woocommerce_bundle_sale_price_html', $this->contains_nyp || $this->contains_optional || $this->contains_min_max_quantities ? sprintf( _x( '%1$s%2$s', 'Price range: from', 'woocommerce-product-bundles' ), $this->get_price_html_from_text(), $this->get_price_html_from_to( $saleprice, $price ) . $this->get_price_suffix() ) : $this->get_price_html_from_to( $saleprice, $price ) . $this->get_price_suffix(), $this );
					} else {
						$price = apply_filters( 'woocommerce_bundle_price_html', $this->contains_nyp || $this->contains_optional || $this->contains_min_max_quantities ? sprintf( _x( '%1$s%2$s', 'Price range: from', 'woocommerce-product-bundles' ), $this->get_price_html_from_text(), $price . $this->get_price_suffix() ) : $price . $this->get_price_suffix(), $this );
					}
				}

			}

			$price = apply_filters( 'woocommerce_get_bundle_price_html', $price, $this );

			if ( $this->contains_sub ) {
				$price = $this->apply_subs_price_html( $price );
			}

			return apply_filters( 'woocommerce_get_price_html', $price, $this );

		} else {

			return parent::get_price_html();
		}
	}

	/**
	 * Returns old-style html price string with "from" string.
	 *
	 * @param  mixed    $price  default price
	 * @return string           overridden html price string (old style)
	 */
	private function get_old_style_price_html( $price = '' ) {

		// Get the price
		if ( $this->min_bundle_price > 0 ) {

			if ( $this->is_on_sale() && $this->min_bundle_regular_price !== $this->min_bundle_price ) {

				if ( ! $this->min_bundle_price || $this->min_bundle_price !== $this->max_bundle_price || $this->contains_nyp || $this->contains_optional ) {
					$price .= $this->get_price_html_from_text();
				}

				$price .= $this->get_price_html_from_to( $this->min_bundle_regular_price, $this->min_bundle_price ) . $this->get_price_suffix();

				$price = apply_filters( 'woocommerce_bundle_sale_price_html', $price, $this );

			} else {

				if ( ! $this->min_bundle_price || $this->min_bundle_price !== $this->max_bundle_price || $this->contains_nyp || $this->contains_optional ) {
					$price .= $this->get_price_html_from_text();
				}

				$price .= wc_price( $this->min_bundle_price ) . $this->get_price_suffix();

				$price = apply_filters( 'woocommerce_bundle_price_html', $price, $this );

			}

		} elseif ( $this->min_bundle_price === '' ) {

			$price = apply_filters( 'woocommerce_bundle_empty_price_html', '', $this );

		} elseif ( $this->min_bundle_price == 0 ) {

			if ($this->is_on_sale() && isset( $this->min_bundle_regular_price ) && $this->min_bundle_regular_price !== $this->min_bundle_price ) {

				if ( ! $this->min_bundle_price || $this->min_bundle_price !== $this->max_bundle_price || $this->contains_nyp || $this->contains_optional ) {
					$price .= $this->get_price_html_from_text();
				}

				$price .= $this->get_price_html_from_to( $this->min_bundle_regular_price, __( 'Free!', 'woocommerce' ) );

				$price = apply_filters('woocommerce_bundle_free_sale_price_html', $price, $this);

			} else {

				if ( ! $this->min_bundle_price || $this->min_bundle_price !== $this->max_bundle_price || $this->contains_nyp || $this->contains_optional ) {
					$price .= $this->get_price_html_from_text();
				}

				$price .= __( 'Free!', 'woocommerce' );

				$price = apply_filters( 'woocommerce_bundle_free_price_html', $price, $this );

			}
		}

		return $price;
	}

	/**
	 * True if the bundle contains a sub.
	 *
	 * @return boolean
	 */
	public function contains_sub() {

		if ( ! $this->is_synced() ) {
			$this->sync_bundle();
		}

		return $this->contains_sub;
	}

	/**
	 * True if all bundled items are in stock in the desired quantities.
	 *
	 * @return boolean  true if all in stock
	 */
	public function all_items_in_stock() {

		if ( ! $this->is_synced() ) {
			$this->sync_bundle();
		}

		return $this->all_items_in_stock;
	}

	/**
	 * Override on_sale status of product bundles. If a bundled item is on sale or has a discount applied, then the bundle appears as on sale.
	 *
	 * @return 	boolean    sale status of bundle
	 */
	public function is_on_sale() {

		$is_on_sale = false;

		if ( $this->is_priced_per_product() ) {

			if ( ! $this->is_synced() ) {
				$this->sync_bundle();
			}

			if ( $this->on_sale ) {
				$is_on_sale = true;
			}

		} else {

			if ( $this->sale_price && $this->sale_price == $this->price ) {
				$is_on_sale = true;
			}
		}

		return apply_filters( 'woocommerce_bundle_is_on_sale', $is_on_sale, $this );
	}

	/**
	 * A bundle is sold individually if it is marked as an "individually-sold" product, or if all bundled items are sold individually.
	 *
	 * @return 	boolean    sold individually status
	 */
	public function is_sold_individually() {

		if ( ! $this->is_synced() ) {
			$this->sync_bundle();
		}

		return parent::is_sold_individually() || $this->all_items_sold_individually;
	}

	/**
	 * A bundle is purchasable if it contains (purchasable) bundled items.
	 *
	 * @return boolean
	 */
	public function is_purchasable() {

		if ( ! $this->is_synced() ) {
			$this->sync_bundle();
		}

		$purchasable = true;

		// Products must exist of course
		if ( ! $this->exists() ) {
			$purchasable = false;

		// When priced statically a price needs to be set
		} elseif ( $this->is_priced_per_product() == false && $this->get_price() === '' ) {
			$purchasable = false;

		// Check the product is published
		} elseif ( $this->post->post_status !== 'publish' && ! current_user_can( 'edit_post', $this->id ) ) {
			$purchasable = false;

		// check if the product contains anything
		} elseif ( false === $this->get_bundled_items() ) {
			$purchasable = false;

		// check if all non-optional contents are purchasable
		} elseif ( false === $this->all_items_purchasable ) {
			$purchasable = false;
		}

		return apply_filters( 'woocommerce_is_purchasable', $purchasable, $this );
	}

	/**
	 * A bundle appears "on backorder" if the container is on backorder, or if a bundled item is on backorder (and requires notification).
	 *
	 * @return 	boolean    true if on backorder
	 */
	public function is_on_backorder( $qty_in_cart = 0 ) {

		if ( ! $this->is_synced() ) {
			$this->sync_bundle();
		}

		return parent::is_on_backorder() || $this->has_items_on_backorder;
	}

	/**
	 * A bundle on backorder requires notification if the container is defined like this, or a bundled item is on backorder and requires notification.
	 *
	 * @return 	boolean    true if backorders require notification or if has items on backorder
	 */
	public function backorders_require_notification() {

		if ( ! $this->is_synced() ) {
			$this->sync_bundle();
		}

		return parent::backorders_require_notification() || $this->has_items_on_backorder;
	}

	/**
	 * Availability of bundle based on bundle stock and stock of bundled items.
	 * In the admin area, the availability of a bundle is based on the stock of the container, to allow shop admins to restrict sales of a bundle regardless of the bundled items' availability.
	 * On the front end, customers need to see whether they can purchase the item or not, so the availability is based on the stock of the container item and the stock of the bundled items.
	 * If any bundled item is available on backorder or out of stock, the container item inherits this status and is no longer listed as "in stock" on the front end.
	 *
	 * @return 	array    availability data array
	 */
	public function get_availability() {

		$backend_availability_data = parent::get_availability();

		if ( ! is_admin() ) {

			if ( ! $this->is_synced() ) {
				$this->sync_bundle();
			}

			$availability = $class = '';

			if ( ! $this->all_items_in_stock() ) {

				$availability = __( 'Insufficient stock', 'woocommerce-product-bundles' );
				$class        = 'out-of-stock';

			} elseif ( $this->has_items_on_backorder ) {

				$availability = __( 'Available on backorder', 'woocommerce' );
				$class        = 'available-on-backorder';
			}

			if ( $backend_availability_data[ 'class' ] == 'out-of-stock' || $backend_availability_data[ 'class' ] == 'available-on-backorder' ) {
				return $backend_availability_data;
			} elseif ( $class == 'out-of-stock' || $class == 'available-on-backorder' ) {
				return array( 'availability' => $availability, 'class' => $class );
			}
		}

		return $backend_availability_data;
	}

	/**
	 * Returns whether or not the bundle has any attributes set. Takes into account the attributes of all bundled products.
	 *
	 * @return 	boolean		true if the bundle has any attributes of its own, or if any of the bundled items has attributes
	 */
	public function has_attributes() {

		// check bundle for attributes
		if ( sizeof( $this->get_attributes() ) > 0 ) {

			foreach ( $this->get_attributes() as $attribute ) {

				if ( isset( $attribute[ 'is_visible' ] ) && $attribute[ 'is_visible' ] ) {
					return true;
				}
			}
		}

		// Check all bundled items for attributes
		$bundled_items = $this->get_bundled_items();

		if ( ! empty( $bundled_items ) && apply_filters( 'woocommerce_bundle_show_bundled_product_attributes', true, $this ) ) {

			foreach ( $bundled_items as $bundled_item ) {

				$bundled_product = $bundled_item->product;

				if ( sizeof( $bundled_product->get_attributes() ) > 0 ) {

					foreach ( $bundled_product->get_attributes() as $attribute ) {

						if ( isset( $attribute[ 'is_visible' ] ) && $attribute[ 'is_visible' ] ) {
							return true;
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * Lists a table of attributes for the bundle page.
	 *
	 * @return 	void
	 */
	public function list_attributes() {

		// show attributes attached to the bundle only
		wc_get_template( 'single-product/product-attributes.php', array(
			'product' => $this
		), '', '' );

		$bundled_items = $this->get_bundled_items();

		if ( ! empty( $bundled_items ) && apply_filters( 'woocommerce_bundle_show_bundled_product_attributes', true, $this ) ) {

			foreach ( $bundled_items as $bundled_item ) {

				$bundled_product = $bundled_item->product;

				if ( ! $this->is_shipped_per_product() ) {
					$bundled_product->length = $bundled_product->width = $bundled_product->height = $bundled_product->weight = '';
				}

				if ( $bundled_product->has_attributes() ) {

					echo '<h3>' . $bundled_item->get_title() . '</h3>';

					// Filter bundled item attributes based on active variation filters
					add_filter( 'woocommerce_attribute',  array( $this, 'bundled_item_attribute' ), 10, 3 );

					$this->listing_attributes_of = $bundled_item->item_id;

					wc_get_template( 'single-product/product-attributes.php', array(
						'product' => $bundled_product
					), '', '' );

					$this->listing_attributes_of = '';

					remove_filter( 'woocommerce_attribute',  array( $this, 'bundled_item_attribute' ), 10, 3 );
				}
			}
		}
	}

	/**
	 * Hide attributes if they correspond to filtered-out variations.
	 *
	 * @param  string   $output     original output
	 * @param  array    $attribute  attribute data
	 * @param  array    $values     attribute values
	 * @return string               modified output
	 */
	public function bundled_item_attribute( $output, $attribute, $values ) {

		if ( $attribute[ 'is_variation' ] ) {

			$variation_attribute_values = array();

			$bundled_item            = $this->get_bundled_item( $this->listing_attributes_of );
			$bundled_item_variations = $bundled_item->get_product_variations();

			if ( empty( $bundled_item_variations ) ) {
				return $output;
			}

			$attribute_key = 'attribute_' . sanitize_title( $attribute[ 'name' ] );

			// Find active attribute values from the bundled item variation data
			foreach ( $bundled_item_variations as $variation_data ) {
				if ( isset( $variation_data[ 'attributes' ][ $attribute_key ] ) ) {
					$variation_attribute_values[] = $variation_data[ 'attributes' ][ $attribute_key ];
					$variation_attribute_values = array_unique( $variation_attribute_values );
				}
			}

			if ( ! empty( $variation_attribute_values ) && in_array( '', $variation_attribute_values ) ) {
				return $output;
			}

			$attribute_name = $attribute[ 'name' ];

			$filtered_values = array();

			if ( $attribute[ 'is_taxonomy' ] ) {

				$product_terms = wc_bundles_get_product_terms( $bundled_item->product_id, $attribute_name, array( 'fields' => 'all' ) );

				foreach ( $product_terms as $product_term ) {
					if ( in_array( $product_term->slug, $variation_attribute_values ) ) {
						$filtered_values[] = $product_term->name;
					}
				}

				return wpautop( wptexturize( implode( ', ', $filtered_values ) ) );

			} else {

				foreach ( $values as $value ) {
					if ( in_array( sanitize_title( $value ), $variation_attribute_values ) ) {
						$filtered_values[] = $value;
					}
				}

				return wpautop( wptexturize( implode( ', ', $filtered_values ) ) );
			}
		}

		return $output;
	}

	/**
	 * Get the add to url used mainly in loops.
	 *
	 * @return 	string
	 */
	public function add_to_cart_url() {

		$url = esc_url( $this->is_purchasable() && $this->is_in_stock() && $this->all_items_in_stock() && ! $this->has_variables() ? remove_query_arg( 'added-to-cart', add_query_arg( 'add-to-cart', $this->id ) ) : get_permalink( $this->id ) );

		return apply_filters( 'bundle_add_to_cart_url', $url, $this );
	}

	/**
	 * Get the add to cart button text.
	 *
	 * @return 	string
	 */
	public function add_to_cart_text() {

		$text = __( 'Read more', 'woocommerce' );

		if ( $this->is_purchasable() && $this->is_in_stock() && $this->all_items_in_stock() ) {

			if ( $this->has_variables() ) {

				if ( $this->all_items_visible ) {
					$text =  __( 'Select options', 'woocommerce' );
				} else {
					$text =  __( 'View contents', 'woocommerce' );
				}

			} else {
				$text =  __( 'Add to cart', 'woocommerce' );
			}
		}

		return apply_filters( 'bundle_add_to_cart_text', $text, $this );
	}

	/**
	 * A bundle has variables to configure if: ( is nyp ) or ( has required addons ) or ( has items with variables ).
	 *
	 * @return boolean  true if it needs configuration before adding to cart
	 */
	public function has_variables() {

		global $woocommerce_bundles;

		if ( ! $this->is_synced() ) {
			$this->sync_bundle();
		}

		if ( $this->is_nyp || $woocommerce_bundles->compatibility->has_required_addons( $this->id ) || $this->has_item_with_variables ) {
			return true;
		}

		return false;
	}
}
