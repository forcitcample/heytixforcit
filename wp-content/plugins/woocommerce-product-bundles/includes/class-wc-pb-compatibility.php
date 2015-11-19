<?php
/**
 * Functions related to extension cross-compatibility.
 *
 * @class    WC_PB_Compatibility
 * @version  4.9.2
 * @since    4.6.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_PB_Compatibility {

	private $addons_prefix = '';
	private $nyp_prefix    = '';
	private $bundle_prefix = '';

	private $compat_product         = '';
	private $compat_bundled_product = '';

	private $allow_subs = false;

	public $stock_data;

	function __construct() {

		// Support for Product Addons
		add_action( 'woocommerce_bundled_product_add_to_cart', array( $this, 'addons_support' ), 10, 2 );
		add_filter( 'product_addons_field_prefix', array( $this, 'addons_cart_prefix' ), 10, 2 );

		add_filter( 'woocommerce_addons_price_for_display_product', array( $this, 'addons_price_for_display_product' ) );

		// Support for NYP
		add_action( 'woocommerce_bundled_product_add_to_cart', array( $this, 'nyp_price_input_support' ), 9, 2 );
		add_filter( 'nyp_field_prefix', array( $this, 'nyp_cart_prefix' ), 10, 2 );

		// Validate add to cart NYP and Addons
		add_filter( 'woocommerce_bundled_item_add_to_cart_validation', array( $this, 'validate_bundled_item_nyp_and_addons' ), 10, 5 );

		// Add addons identifier to bundled item stamp
		add_filter( 'woocommerce_bundled_item_cart_item_identifier', array( $this, 'bundled_item_addons_stamp' ), 10, 2 );

		// Add NYP identifier to bundled item stamp
		add_filter( 'woocommerce_bundled_item_cart_item_identifier', array( $this, 'bundled_item_nyp_stamp' ), 10, 2 );

		// Points and Rewards support
		if ( class_exists( 'WC_Points_Rewards_Product' ) ) {

			// Points earned for per-product priced bundles
			add_filter( 'woocommerce_points_earned_for_cart_item', array( $this, 'points_earned_for_bundled_cart_item' ), 10, 3 );
			add_filter( 'woocommerce_points_earned_for_order_item', array( $this, 'points_earned_for_bundled_order_item' ), 10, 5 );

			// Change earn points message for per-product-priced bundles
			add_filter( 'wc_points_rewards_single_product_message', array( $this, 'points_rewards_bundle_message' ), 10, 2 );

			// Remove PnR message from variations
			add_action( 'woocommerce_before_init_bundled_item', array( $this, 'points_rewards_remove_price_html_messages' ) );
			add_action( 'woocommerce_after_init_bundled_item', array( $this, 'points_rewards_restore_price_html_messages' ) );
		}

		// Pre-orders support
		add_filter( 'wc_pre_orders_cart_item_meta', array( $this, 'remove_bundled_pre_orders_cart_item_meta' ), 10, 2 );
		add_filter( 'wc_pre_orders_order_item_meta', array( $this, 'remove_bundled_pre_orders_order_item_meta' ), 10, 3 );

		// Composites support
		if ( class_exists( 'WC_Composite_Products' ) ) {

			// Show bundle type products using the bundle-product.php composited product template -- CP v2.5
			add_action( 'woocommerce_composite_show_product_type_custom', array( $this, 'composite_show_product_bundle' ), 10, 4 );

			// Show bundle type products using the bundle-product.php composited product template -- CP v3.0
			add_action( 'woocommerce_composite_show_custom_product_type', array( $this, 'composite_show_product_bundle_v3' ), 10, 3 );

			// Validate bundle type component selections
			add_filter( 'woocommerce_composite_component_add_to_cart_validation', array( $this, 'composite_validate_bundle_data' ), 10, 6 );

			// Add bundle identifier to composited item stamp
			add_filter( 'woocommerce_composite_component_cart_item_identifier', array( $this, 'composite_bundle_cart_item_stamp' ), 10, 2 );

			// Apply component prefix to bundle input fields
			add_filter( 'woocommerce_product_bundle_field_prefix', array( $this, 'bundle_field_prefix' ), 10, 2 );

			// Hook into composited product add-to-cart action to add bundled items since 'woocommerce-add-to-cart' action cannot be used recursively
			add_action( 'woocommerce_composited_add_to_cart', array( $this, 'add_bundle_to_cart' ), 10, 6 );

			// Link bundled cart/order items with composite
			add_filter( 'woocommerce_cart_item_is_child_of_composite', array( $this, 'bundled_cart_item_is_child_of_composite' ), 10, 5 );
			add_filter( 'woocommerce_order_item_is_child_of_composite', array( $this, 'bundled_order_item_is_child_of_composite' ), 10, 4 );

			// Tweak bundle container items appearance in various templates
			add_filter( 'woocommerce_cart_item_name', array( $this, 'composited_bundle_in_cart_item_title' ), 9, 3 );
			add_filter( 'woocommerce_cart_item_quantity', array( $this, 'composited_bundle_in_cart_item_quantity' ), 11, 2 );
			add_filter( 'woocommerce_composited_cart_item_quantity_html', array( $this, 'composited_bundle_checkout_item_quantity' ), 10, 2 );
			add_filter( 'woocommerce_order_item_visible', array( $this, 'composited_bundle_order_item_visible' ), 10, 2 );
			add_filter( 'woocommerce_order_item_name', array( $this, 'composited_bundle_order_table_item_title' ), 9, 2 );
			add_filter( 'woocommerce_composited_order_item_quantity_html', array( $this, 'composited_bundle_order_table_item_quantity' ), 11, 2 );
		}

		// OPC support
		add_action( 'wcopc_bundle_add_to_cart', array( $this, 'opc_single_add_to_cart_bundle' ) );
		add_filter( 'wcopc_allow_cart_item_modification', array( $this, 'opc_disallow_bundled_cart_item_modification' ), 10, 4 );

		// Cost of Goods support
		add_filter( 'wc_cost_of_goods_save_checkout_order_item_meta_item_cost', array( $this, 'cost_of_goods_checkout_order_bundled_item_cost' ), 10, 3 );
		add_filter( 'wc_cost_of_goods_save_checkout_order_meta_item_cost', array( $this, 'cost_of_goods_checkout_order_bundled_item_cost' ), 10, 3 );
		add_filter( 'wc_cost_of_goods_set_order_item_cost_meta_item_cost', array( $this, 'cost_of_goods_set_order_item_bundled_item_cost' ), 10, 3 );

		// Shipstation compatibility
		add_filter( 'woocommerce_get_product_from_item', array( $this, 'get_product_from_item' ), 10, 3 );
		add_filter( 'woocommerce_order_amount_item_total', array( $this, 'order_amount_bundle_total' ), 10, 5 );
		add_filter( 'woocommerce_order_get_items', array( $this, 'order_add_bundled_meta' ), 10, 2 );
	}

	/**
	 * Edit composited bundle container cart title.
	 *
	 * @param  string   $content
	 * @param  array    $cart_item_values
	 * @param  string   $cart_item_key
	 * @return string
	 */
	public function composited_bundle_in_cart_item_title( $content, $cart_item_values, $cart_item_key ) {

		if ( isset( $cart_item_values[ 'bundled_items' ] ) && ! empty( $cart_item_values[ 'composite_parent' ] ) ) {

			if ( ! empty( $cart_item_values[ 'stamp' ] ) ) {

				if ( empty( $cart_item_values[ 'bundled_items' ] ) && $cart_item_values[ 'data' ]->get_price() == 0  ) {
					$content = __( 'None', 'woocommerce-product-bundles' );
				} elseif ( apply_filters( 'woocommerce_composited_bundle_container_cart_item_hide_title', false, $cart_item_values, $cart_item_key ) ) {
					$content = '';
				}
			}
		}

		return $content;
	}

	/**
	 * Edit composited bundle container cart qty.
	 *
	 * @param  int      $quantity
	 * @param  string   $cart_item_key
	 * @return int
	 */
	function composited_bundle_in_cart_item_quantity( $quantity, $cart_item_key ) {

		if ( isset( WC()->cart->cart_contents[ $cart_item_key ] ) ) {
			 $cart_item_values = WC()->cart->cart_contents[ $cart_item_key ];

			if ( isset( $cart_item_values[ 'bundled_items' ] ) && ! empty( $cart_item_values[ 'composite_parent' ] ) ) {

				if ( ! empty( $cart_item_values[ 'stamp' ] ) ) {

					if ( empty( $cart_item_values[ 'bundled_items' ] ) && $cart_item_values[ 'data' ]->get_price() == 0  ) {
						$quantity = '';
					} elseif ( apply_filters( 'woocommerce_composited_bundle_container_cart_item_hide_title', false, $cart_item_values, $cart_item_key ) ) {
						$quantity = '';
					}
				}
			}
		}

		return $quantity;
	}

	/**
	 * Edit composited bundle container cart qty.
	 *
	 * @param  int      $quantity
	 * @param  string   $cart_item_values
	 * @param  string   $cart_item_key
	 * @return int
	 */
	function composited_bundle_checkout_item_quantity( $quantity, $cart_item_values, $cart_item_key = false ) {

		if ( isset( $cart_item_values[ 'bundled_items' ] ) && ! empty( $cart_item_values[ 'composite_parent' ] ) ) {

			if ( ! empty( $cart_item_values[ 'stamp' ] ) ) {

				if ( empty( $cart_item_values[ 'bundled_items' ] ) && $cart_item_values[ 'data' ]->get_price() == 0  ) {
					$quantity = '';
				} elseif ( apply_filters( 'woocommerce_composited_bundle_container_cart_item_hide_title', false, $cart_item_values, $cart_item_key ) ) {
					$quantity = '';
				}
			}
		}

		return $quantity;
	}

	/**
	 * Visibility of composited bundle container in orders.
	 * Hide containers without children and a zero price (all optional).
	 *
	 * @param  boolean $visible
	 * @param  array   $order_item
	 * @return boolean
	 */
	public function composited_bundle_order_item_visible( $visible, $order_item ) {

		if ( isset( $order_item[ 'bundled_items' ] ) && ! empty( $order_item[ 'composite_parent' ] ) ) {

			$bundled_items = maybe_unserialize( $order_item[ 'bundled_items' ] );

			if ( empty( $bundled_items ) && $order_item[ 'line_subtotal' ] == 0  ) {
				$visible = false;
			}
		}

		return $visible;
	}

	/**
	 * Edit composited bundle container order item title.
	 *
	 * @param  string   $content
	 * @param  array 	$order_item
	 * @return string
	 */
	public function composited_bundle_order_table_item_title( $content, $order_item ) {

		if ( isset( $order_item[ 'bundled_items' ] ) && ! empty( $order_item[ 'composite_parent' ] ) ) {

			if ( ! empty( $order_item[ 'stamp' ] ) ) {

				$bundled_items = maybe_unserialize( $order_item[ 'bundled_items' ] );

				if ( empty( $bundled_items ) && $order_item[ 'line_subtotal' ] == 0  ) {
					$content = __( 'None', 'woocommerce-product-bundles' );
				} elseif ( apply_filters( 'woocommerce_composited_bundle_container_order_item_hide_title', false, $order_item ) ) {
					$content = '';
				}
			}
		}

		return $content;
	}

	/**
	 * Edit composited bundle container order item qty.
	 *
	 * @param  string   $content
	 * @param  array 	$order_item
	 * @return string
	 */
	public function composited_bundle_order_table_item_quantity( $quantity, $order_item ) {

		if ( isset( $order_item[ 'bundled_items' ] ) && ! empty( $order_item[ 'composite_parent' ] ) ) {

			if ( ! empty( $order_item[ 'stamp' ] ) ) {

				$bundled_items = maybe_unserialize( $order_item[ 'bundled_items' ] );

				if ( empty( $bundled_items ) && $order_item[ 'line_subtotal' ] == 0  ) {
					$quantity = '';
				} elseif ( apply_filters( 'woocommerce_composited_bundle_container_order_item_hide_title', false, $order_item ) ) {
					$quantity = '';
				}
			}
		}

		return $quantity;
	}

	/**
	 * Shipstation compatibility:
	 *
	 * When returning a single container item, add bundled items as metadata.
	 *
	 * @param  array    $items
	 * @param  WC_Order $order
	 * @return array
	 */
	function order_add_bundled_meta( $items, $order ) {

		global $wp;

		if ( isset( $wp->query_vars[ 'wc-api' ] ) && $wp->query_vars[ 'wc-api' ] === 'wc_shipstation' ) {

			foreach ( $items as $item_id => $item ) {

				if ( isset( $item[ 'bundled_items' ] ) && isset( $item[ 'bundle_cart_key' ] ) && isset( $item[ 'per_product_shipping' ] ) && $item[ 'per_product_shipping' ] === 'no' ) {

					$bundle_key = $item[ 'bundle_cart_key' ];

					$meta_key   = __( 'Contents', 'woocommerce-product-bundles' );
					$meta_value = '';

					foreach ( $items as $child_item ) {

						if ( isset( $child_item[ 'bundled_by' ] ) && $child_item[ 'bundled_by' ] === $bundle_key ) {

							$child = $order->get_product_from_item( $child_item );

							if ( $child && $sku = $child->get_sku() ) {
								$sku .= ' &ndash; ';
							} else {
								$sku = '#' . ( isset( $child->variation_id ) ? $child->variation_id : $child->id ) . ' &ndash; ';
							}

							$meta_value .= $sku . $child_item[ 'name' ];

							if ( ! empty( $child_item[ 'item_meta' ][ __( 'Part of', 'woocommerce-product-bundles' ) ] ) ) {
								unset( $child_item[ 'item_meta' ][ __( 'Part of', 'woocommerce-product-bundles' ) ] );
							}

							$item_meta      = new WC_Order_Item_Meta( $child_item[ 'item_meta' ] );
							$formatted_meta = $item_meta->display( true, true, '_', ', ' );

							if ( $formatted_meta ) {

								$meta_value .= ' (' . $formatted_meta . ')';
							}

							$meta_value .= ' &times; ' . $child_item[ 'qty' ] . ', ';
						}
					}

					$items[ $item_id ][ 'item_meta' ][ $meta_key ] = rtrim( $meta_value, ', ' );
				}
			}
		}

		return $items;
	}

	/**
	 * Shipstation compatibility:
	 *
	 * Ensure that non-virtual containers/children, which are shipped, have a valid price that can be used for insurance calculations.
	 *
	 * Note: If you charge a static price for the bundle but ship bundled items individually, the only working solution is to spread the total value among the bundled items.
	 *
	 * @param  double   $price
	 * @param  WC_Order $order
	 * @param  array    $item
	 * @param  boolean  $inc_tax
	 * @param  boolean  $round
	 * @return double
	 */
	function order_amount_bundle_total( $price, $order, $item, $inc_tax, $round ) {

		global $wp, $woocommerce_bundles;

		if ( isset( $wp->query_vars[ 'wc-api' ] ) && $wp->query_vars[ 'wc-api' ] === 'wc_shipstation' ) {

			if ( isset( $item[ 'bundled_items' ] ) && isset( $item[ 'bundle_cart_key' ] ) && isset( $item[ 'bundled_shipping' ] ) && $item[ 'bundled_shipping' ] === 'yes' ) {

				$bundle_key   = $item[ 'bundle_cart_key' ];
				$bundle_qty   = $item[ 'qty' ];
				$bundle_value = $price;

				foreach ( $order->get_items( 'line_item' ) as $order_item ) {

					if ( isset( $order_item[ 'bundled_by' ] ) && $order_item[ 'bundled_by' ] === $bundle_key ) {
						$bundle_value += $order->get_line_total( $order_item, $inc_tax, $round ) / $bundle_qty;
					}
				}

				$price = $round ? round( $bundle_value, 2 ) : $bundle_value;

			} elseif ( isset( $item[ 'bundled_by' ] ) && isset( $item[ 'bundle_cart_key' ] ) && isset( $item[ 'bundled_shipping' ] ) && $item[ 'bundled_shipping' ] === 'yes' ) {

				$parent = $woocommerce_bundles->order->get_bundled_order_item_container( $item, $order );

				if ( $parent && isset( $parent[ 'per_product_shipping' ] ) && $parent[ 'per_product_shipping' ] === 'yes' && isset( $parent[ 'per_product_pricing' ] ) && $parent[ 'per_product_pricing' ] === 'no' && isset( $parent[ 'bundle_cart_key' ] ) ) {

					$bundle_value = $order->get_line_total( $parent, $inc_tax, $round );
					$bundle_key   = $parent[ 'bundle_cart_key' ];
					$child_count  = 0;

					foreach ( $order->get_items( 'line_item' ) as $child_item ) {

						if ( isset( $child_item[ 'bundled_by' ] ) && $child_item[ 'bundled_by' ] === $bundle_key ) {

							$bundle_value += $order->get_line_total( $child_item, $inc_tax, $round );
							$child_count  += $child_item[ 'qty' ];
						}
					}

					$price = $round ? round( $bundle_value / $child_count, 2 ) : $bundle_value / $child_count;
				}
			}
		}

		return $price;
	}

	/**
	 * Shipstation compatibility:
	 *
	 * Restore virtual status and weights.
	 *
	 * @param  WC_Product $product
	 * @param  array      $item
	 * @param  WC_Order   $order
	 * @return WC_Product
	 */
	function get_product_from_item( $product, $item, $order ) {

		global $wp;

		if ( isset( $wp->query_vars[ 'wc-api' ] ) && $wp->query_vars[ 'wc-api' ] === 'wc_shipstation' ) {

			if ( isset( $item[ 'stamp' ] ) && isset( $item[ 'bundled_shipping' ] ) ) {

				if ( $item[ 'bundled_shipping' ] === 'yes' ) {

					if ( isset( $item[ 'bundled_weight' ] ) ) {
						$product->weight = $item[ 'bundled_weight' ];
					}

				} else {

					$product->virtual = 'yes';
				}
			}
		}

		return $product;
	}

	/**
	 * Cost of goods compatibility: Zero order item cost for bundled products that belong to statically priced bundles.
	 *
	 * @param  double $cost
	 * @param  array  $values
	 * @param  string $cart_item_key
	 * @return double
	 */
	function cost_of_goods_checkout_order_bundled_item_cost( $cost, $values, $cart_item_key ) {

		if ( ! empty( $values[ 'bundled_by' ] ) ) {

			$cart_contents   = WC()->cart->get_cart();
			$bundle_cart_key = $values[ 'bundled_by' ];

			if ( isset( $cart_contents[ $bundle_cart_key ] ) ) {
				if ( ! $cart_contents[ $bundle_cart_key ][ 'data' ]->is_priced_per_product() ) {
					return 0;
				}
			}

		} elseif ( ! empty( $values[ 'bundled_items' ] ) ) {
			if ( $values[ 'data' ]->is_priced_per_product() ) {
				return 0;
			}
		}

		return $cost;
	}

	/**
	 * Cost of goods compatibility: Zero order item cost for bundled products that belong to statically priced bundles.
	 *
	 * @param  double   $cost
	 * @param  array    $item
	 * @param  WC_Order $order
	 * @return double
	 */
	function cost_of_goods_set_order_item_bundled_item_cost( $cost, $item, $order ) {

		global $woocommerce_bundles;

		if ( ! empty( $item[ 'bundled_by' ] ) ) {

			// find bundle parent
			$parent_item = $woocommerce_bundles->order->get_bundled_order_item_container( $item, $order );

			$per_product_pricing = ! empty( $parent_item ) && isset( $parent_item[ 'per_product_pricing' ] ) ? $parent_item[ 'per_product_pricing' ] : get_post_meta( $parent_item[ 'product_id' ], '_per_product_pricing_active', true );

			if ( $per_product_pricing === 'no' ) {
				return 0;
			}

		} elseif ( ! isset( $item[ 'bundled_by' ] ) && isset( $item[ 'stamp' ] ) ) {

			$per_product_pricing = isset( $item[ 'per_product_pricing' ] ) ? $item[ 'per_product_pricing' ] : get_post_meta( $item[ 'product_id' ], '_per_product_pricing_active', true );

			if ( $per_product_pricing === 'yes' ) {
				return 0;
			}
		}

		return $cost;
	}

	/**
	 * OPC Single-product bundle-type add-to-cart template
	 *
	 * @param  int  $opc_post_id
	 * @return void
	 */
	function opc_single_add_to_cart_bundle( $opc_post_id ) {

		global $woocommerce_bundles, $product;

		// Enqueue script
		wp_enqueue_script( 'wc-add-to-cart-bundle' );
		wp_enqueue_style( 'wc-bundle-css' );

		if ( $product->is_purchasable() ) {

			ob_start();

			wc_get_template( 'single-product/add-to-cart/bundle.php', array(
				'available_variations' 		=> $product->get_available_bundle_variations(),
				'attributes'   				=> $product->get_bundle_variation_attributes(),
				'selected_attributes' 		=> $product->get_selected_bundle_variation_attributes(),
				'bundle_price_data' 		=> $product->get_bundle_price_data(),
				'bundled_items' 			=> $product->get_bundled_items()
			), false, $woocommerce_bundles->woo_bundles_plugin_path() . '/templates/' );

			echo str_replace( array( '<form method="post" enctype="multipart/form-data"', '</form>' ), array( '<div', '</div>' ), ob_get_clean() );
		}
	}

	/**
	 * Prevent OPC from managing bundled items.
	 *
	 * @param  bool   $allow
	 * @param  array  $cart_item
	 * @param  string $cart_item_key
	 * @param  string $opc_id
	 * @return bool
	 */
	function opc_disallow_bundled_cart_item_modification( $allow, $cart_item, $cart_item_key, $opc_id ) {

		if ( ! empty( $cart_item[ 'bundled_by' ] ) ) {
			return false;
		}

		return $allow;
	}

	/**
	 * Filter the product which add-ons prices are displayed for.
	 *
	 * @param  WC_Product  $product
	 * @return WC_Product
	 */
	function addons_price_for_display_product( $product ) {

		if ( ! empty( $this->compat_bundled_product ) )
			return $this->compat_bundled_product;

		return $product;
	}

	/**
	 * Used to link bundled order items with the composite container product.
	 *
	 * @param  boolean  $is_child
	 * @param  array    $order_item
	 * @param  array    $composite_item
	 * @param  WC_Order $order
	 * @return boolean
	 */
	function bundled_order_item_is_child_of_composite( $is_child, $order_item, $composite_item, $order ) {

		global $woocommerce_bundles;

		if ( ! empty( $order_item[ 'bundled_by' ] ) ) {

			$parent = $woocommerce_bundles->order->get_bundled_order_item_container( $order_item, $order );

			if ( $parent && isset( $parent[ 'composite_parent' ] ) && $parent[ 'composite_parent' ] == $composite_item[ 'composite_cart_key' ] )
				return true;
		}

		return $is_child;
	}

	/**
	 * Used to link bundled cart items with the composite container product.
	 *
	 * @param  boolean  $is_child
	 * @param  string   $cart_item_key
	 * @param  array    $cart_item_data
	 * @param  string   $composite_key
	 * @param  array    $composite_data
	 * @return boolean
	 */
	function bundled_cart_item_is_child_of_composite( $is_child, $cart_item_key, $cart_item_data, $composite_key, $composite_data ) {

		if ( ! empty( $cart_item_data[ 'bundled_by' ] ) ) {

			$parent_key = $cart_item_data[ 'bundled_by' ];

			if ( isset( WC()->cart->cart_contents[ $parent_key ] ) ) {

				$parent = WC()->cart->cart_contents[ $parent_key ];

				if ( isset( $parent[ 'composite_parent' ] ) && $parent[ 'composite_parent' ] == $composite_key ) {
					return true;
				}
			}
		}

		return $is_child;
	}

	/**
	 * Hook into 'woocommerce_composited_add_to_cart' to trigger 'woo_bundles_add_bundle_to_cart'.
	 *
	 * @param string  $cart_item_key
	 * @param int     $product_id
	 * @param int     $quantity
	 * @param int     $variation_id
	 * @param array   $variation
	 * @param array   $cart_item_data
	 */
	function add_bundle_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {

		global $woocommerce_bundles;

		$woocommerce_bundles->cart->woo_bundles_add_bundle_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data );
	}

	/**
	 * Hook into 'woocommerce_composite_show_product_type_custom' to show bundle type product content.
	 *
	 * @param  WC_Product  $product
	 * @param  string      $component_id
	 * @param  int         $composite_id
	 * @param  array       $component_data
	 * @return void
	 */
	function composite_show_product_bundle( $product, $component_id, $composite_id, $component_data ) {

		global $woocommerce_bundles;

		if ( $product->product_type == 'bundle' ) {

			if ( $product->contains_sub() ) {

				echo __( 'Sorry, this item cannot be purchased at the moment.', 'woocommerce-product-bundles' );
				return false;
			}

			$this->compat_product = $product;

			$this->bundle_prefix = $component_id;

			$quantity_min = $component_data[ 'quantity_min' ];
			$quantity_max = $component_data[ 'quantity_max' ];

			if ( $product->sold_individually == 'yes' ) {
	 			$quantity_max = 1;
	 			$quantity_min = min( $quantity_min, 1 );
	 		}

			$hide_product_title 		= isset( $component_data[ 'hide_product_title' ] ) ? $component_data[ 'hide_product_title' ] : 'no';
			$hide_product_description 	= isset( $component_data[ 'hide_product_description' ] ) ? $component_data[ 'hide_product_description' ] : 'no';
			$hide_product_thumbnail 	= isset( $component_data[ 'hide_product_thumbnail' ] ) ? $component_data[ 'hide_product_thumbnail' ] : 'no';

			if ( $product->is_purchasable() )
				wc_get_template( 'composited-product/bundle-product.php', array(
					'product'                  => $product,
					'composite_id'             => $composite_id,
					'quantity_min'             => $quantity_min,
					'quantity_max'             => $quantity_max,
					'hide_product_title'       => $hide_product_title,
					'hide_product_description' => $hide_product_description,
					'hide_product_thumbnail'   => $hide_product_thumbnail,
					'available_variations'     => $product->get_available_bundle_variations(),
					'attributes'               => $product->get_bundle_variation_attributes(),
					'selected_attributes'      => $product->get_selected_bundle_variation_attributes(),
					'custom_data'              => array(),
					'bundle_price_data'        => $product->get_bundle_price_data(),
					'bundled_items'            => $product->get_bundled_items(),
					'component_id'             => $component_id,
					'show_selection_ui'        => false
				), false, $woocommerce_bundles->woo_bundles_plugin_path() . '/templates/' );

			$this->compat_product = $this->bundle_prefix = '';

		}
	}

	/**
	 * Hook into 'woocommerce_composite_show_product_type_custom' to show bundle type product content.
	 *
	 * @param  WC_Product  $product
	 * @param  string      $component_id
	 * @param  WC_Product  $composite
	 * @return void
	 */
	function composite_show_product_bundle_v3( $product, $component_id, $composite ) {

		global $woocommerce_bundles;

		if ( $product->product_type == 'bundle' ) {

			if ( $product->contains_sub() ) {

				echo __( 'Sorry, this item cannot be purchased at the moment.', 'woocommerce-product-bundles' );
				return false;
			}

			$this->compat_product = $product;
			$this->bundle_prefix  = $component_id;

			$component_data = $composite->get_component_data( $component_id );

			$quantity_min = $component_data[ 'quantity_min' ];
			$quantity_max = $component_data[ 'quantity_max' ];

			if ( $product->sold_individually == 'yes' ) {
	 			$quantity_max = 1;
	 			$quantity_min = min( $quantity_min, 1 );
	 		}

	 		$custom_data = apply_filters( 'woocommerce_composited_product_custom_data', array(), $product, $component_id, $component_data, $composite );

			$hide_product_title 		= isset( $component_data[ 'hide_product_title' ] ) ? $component_data[ 'hide_product_title' ] : 'no';
			$hide_product_description 	= isset( $component_data[ 'hide_product_description' ] ) ? $component_data[ 'hide_product_description' ] : 'no';
			$hide_product_thumbnail 	= isset( $component_data[ 'hide_product_thumbnail' ] ) ? $component_data[ 'hide_product_thumbnail' ] : 'no';

			if ( $product->is_purchasable() )
				wc_get_template( 'composited-product/bundle-product.php', array(
					'product'                  => $product,
					'composite_id'             => $composite->id,
					'quantity_min'             => $quantity_min,
					'quantity_max'             => $quantity_max,
					'hide_product_title'       => $hide_product_title,
					'hide_product_description' => $hide_product_description,
					'hide_product_thumbnail'   => $hide_product_thumbnail,
					'available_variations'     => $product->get_available_bundle_variations(),
					'attributes'               => $product->get_bundle_variation_attributes(),
					'selected_attributes'      => $product->get_selected_bundle_variation_attributes(),
					'custom_data'              => $custom_data,
					'bundle_price_data'        => $product->get_bundle_price_data(),
					'bundled_items'            => $product->get_bundled_items(),
					'component_id'             => $component_id,
					'show_selection_ui'        => $composite->is_component_static( $component_id ) === false,
					'composite_product'        => $composite
				), false, $woocommerce_bundles->woo_bundles_plugin_path() . '/templates/' );

			$this->compat_product = $this->bundle_prefix = '';

		}
	}

	/**
	 * Hook into 'woocommerce_composite_component_cart_item_identifier' to add stamp data for bundles.
	 *
	 * @param  array  $composited_item_identifier
	 * @param  string $composited_item_id
	 * @return array
	 */
	function composite_bundle_cart_item_stamp( $composited_item_identifier, $composited_item_id ) {

		global $woocommerce_bundles;

		if ( isset( $composited_item_identifier[ 'type' ] ) && $composited_item_identifier[ 'type' ] == 'bundle' ) {

			$this->bundle_prefix = $composited_item_id;

			$bundle_cart_data = $woocommerce_bundles->cart->woo_bundles_add_cart_item_data( array(), $composited_item_identifier[ 'product_id' ] );

			$composited_item_identifier[ 'stamp' ] = $bundle_cart_data[ 'stamp' ];

			$this->bundle_prefix = '';
		}

		return $composited_item_identifier;
	}

	/**
	 * Bundles with subscriptions can't be composited.
	 *
	 * @param  boolean     $passed
	 * @param  WC_Product  $bundle
	 * @return boolean
	 */
	function disallow_bundled_item_subs( $passed, $bundle ) {

		if ( $bundle->contains_sub() ) {

			wc_add_notice( sprintf( __( 'The configuration you have selected cannot be added to the cart. &quot;%s&quot; cannot be purchased.', 'woocommerce-product-bundles' ), $bundle->get_title() ), 'error' );
			return false;
		}

		return $passed;
	}

	/**
	 * Hook into 'woocommerce_composite_component_add_to_cart_validation' to validate composited bundles.
	 *
	 * @param  boolean  $result
	 * @param  int      $composite_id
	 * @param  string   $component_id
	 * @param  int      $bundle_id
	 * @param  int      $quantity
	 * @return boolean
	 */
	function composite_validate_bundle_data( $result, $composite_id, $component_id, $bundle_id, $quantity, $cart_item_data ) {

		global $woocommerce_bundles;

		// Get product type
		$terms 			= get_the_terms( $bundle_id, 'product_type' );
		$product_type 	= ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';

		if ( $product_type == 'bundle' ) {

			// Present only when re-ordering
			if ( isset( $cart_item_data[ 'composite_data' ][ $component_id ][ 'stamp' ] ) )
				$cart_item_data [ 'stamp' ] = $cart_item_data[ 'composite_data' ][ $component_id ][ 'stamp' ];

			$this->bundle_prefix = $component_id;

			add_filter( 'woocommerce_bundle_before_validation', array( $this, 'disallow_bundled_item_subs' ), 10, 2 );

			$result = $woocommerce_bundles->cart->woo_bundles_validation( true, $bundle_id, $quantity, '', array(), $cart_item_data );

			$this->bundle_prefix = '';

			remove_filter( 'woocommerce_bundle_before_validation', array( $this, 'disallow_bundled_item_subs' ), 10, 2 );

			// Add filter to return stock manager items from bundle
			if ( class_exists( 'WC_CP_Stock_Manager' ) ) {
				add_filter( 'woocommerce_composite_component_associated_stock', array( $this, 'associated_bundle_stock' ), 10, 5 );
			}
		}

		return $result;
	}

	/**
	 * Hook into 'woocommerce_composite_component_associated_stock' to append bundled items to the composite stock data object.
	 *
	 * @param  WC_PB_Stock_Manager   $items
	 * @param  int                   $composite_id
	 * @param  string                $component_id
	 * @param  int                   $bundled_product_id
	 * @param  int                   $quantity
	 * @return WC_PB_Stock_Manager
	 */
	function associated_bundle_stock( $items, $composite_id, $component_id, $bundled_product_id, $quantity ) {

		if ( ! empty( $this->stock_data ) ) {

			$items = $this->stock_data;

			$this->stock_data = '';
			remove_filter( 'woocommerce_composite_component_associated_stock', array( $this, 'associated_bundle_stock' ), 10, 5 );
		}

		return $items;
	}

	/**
	 * Sets a prefix for unique bundles.
	 *
	 * @param  string 	$prefix
	 * @param  int 		$product_id
	 * @return string
	 */
	function bundle_field_prefix( $prefix, $product_id ) {

		if ( ! empty( $this->bundle_prefix ) )
			return 'component_' . $this->bundle_prefix . '_';

		return $prefix;
	}

	/**
	 * Remove bundled cart item meta "Available On" text.
	 *
	 * @param  array  $pre_order_meta
	 * @param  array  $cart_item_data
	 * @return array
	 */
	function remove_bundled_pre_orders_cart_item_meta( $pre_order_meta, $cart_item_data ) {

		if ( isset( $cart_item_data[ 'bundled_by' ] ) )
			$pre_order_meta = array();

		return $pre_order_meta;
	}

	/**
	 * Remove bundled order item meta "Available On" text.
	 *
	 * @param  array    $pre_order_meta
	 * @param  array    $order_item
	 * @param  WC_Order $order
	 * @return array
	 */
	function remove_bundled_pre_orders_order_item_meta( $pre_order_meta, $order_item, $order ) {

		if ( isset( $order_item[ 'bundled_by' ] ) )
			$pre_order_meta = array();

		return $pre_order_meta;
	}

	/**
	 * Filter option_wc_points_rewards_single_product_message in order to force 'WC_Points_Rewards_Product::render_variation_message' to display nothing.
	 *
	 * @param  WC_Bundled_Item  $bundled_item
	 * @return void
	 */
	function points_rewards_remove_price_html_messages( $bundled_item ) {
		add_filter( 'option_wc_points_rewards_single_product_message', array( $this, 'return_empty_message' ) );
	}

	/**
	 * Restore option_wc_points_rewards_single_product_message. Forced in order to force 'WC_Points_Rewards_Product::render_variation_message' to display nothing.
	 *
	 * @param  WC_Bundled_Item  $bundled_item
	 * @return void
	 */
	function points_rewards_restore_price_html_messages( $bundled_item ) {
		remove_filter( 'option_wc_points_rewards_single_product_message', array( $this, 'return_empty_message' ) );
	}

	/**
	 * @see points_rewards_remove_price_html_messages
	 * @param  string  $message
	 * @return void
	 */
	function return_empty_message( $message ) {
		return false;
	}

	/**
	 * Points and Rewards single product message for per-product priced Bundles.
	 *
	 * @param  string                    $message
	 * @param  WC_Points_Rewards_Product $points_n_rewards
	 * @return string
	 */
	function points_rewards_bundle_message( $message, $points_n_rewards ) {

		global $product;

		if ( $product->product_type == 'bundle' ) {

			if ( ! $product->is_priced_per_product() )
				return $message;

			// Will calculate points based on min_bundle_price
			$bundle_points = WC_Points_Rewards_Product::get_points_earned_for_product_purchase( $product );

			$message = $points_n_rewards->create_at_least_message_to_product_summary( $bundle_points );

		}

		return $message;
	}

	/**
	 * Return zero points for bundled cart items if container item has product level points.
	 *
	 * @param  int        $points
	 * @param  string     $item_key
	 * @param  array      $item
	 * @param  WC_Order   $order
	 * @return int
	 */
	function points_earned_for_bundled_order_item( $points, $product, $item_key, $item, $order ) {

		if ( isset( $item[ 'bundled_by' ] ) ) {

			// find container item
			foreach ( $order->get_items() as $order_item ) {

				$is_parent = ( isset( $order_item[ 'bundle_cart_key' ] ) && $item[ 'bundled_by' ] == $order_item[ 'bundle_cart_key' ] ) ? true : false;

				if ( $is_parent ) {

					$parent_item 		= $order_item;
					$bundle_product_id 	= $parent_item[ 'product_id' ];

					// check if earned points are set at product-level
					$bundle_points = get_post_meta( $bundle_product_id, '_wc_points_earned', true );

					$per_product_priced_bundle = isset( $parent_item[ 'per_product_pricing' ] ) ? $parent_item[ 'per_product_pricing' ] : get_post_meta( $bundle_product_id, '_per_product_pricing_active', true );

					if ( ! empty( $bundle_points ) || $per_product_priced_bundle !== 'yes' )
						$points = 0;
					else
						$points = WC_Points_Rewards_Manager::calculate_points( $product->get_price() );

					break;
				}

			}

		}

		return $points;
	}

	/**
	 * Return zero points for bundled cart items if container item has product level points.
	 *
	 * @param  int     $points
	 * @param  string  $cart_item_key
	 * @param  array   $cart_item_values
	 * @return int
	 */
	function points_earned_for_bundled_cart_item( $points, $cart_item_key, $cart_item_values ) {

		if ( isset( $cart_item_values[ 'bundled_by' ] ) ) {

			$cart_contents = WC()->cart->get_cart();

			$bundle_cart_id 	= $cart_item_values[ 'bundled_by' ];
			$bundle 			= $cart_contents[ $bundle_cart_id ][ 'data' ];

			// check if earned points are set at product-level
			$bundle_points = WC_Points_Rewards_Product::get_product_points( $bundle );

			$per_product_priced_bundle = $bundle->is_priced_per_product();

			$has_bundle_points = is_numeric( $bundle_points ) ? true : false;

			if ( $has_bundle_points || $per_product_priced_bundle == false  )
				$points = 0;
			else
				$points = WC_Points_Rewards_Manager::calculate_points( $cart_item_values[ 'data' ]->get_price() );

		}

		return $points;
	}

	/**
	 * Runs before adding a bundled item to the cart.
	 *
	 * @param  int                $product_id
	 * @param  int                $quantity
	 * @param  int                $variation_id
	 * @param  array              $variations
	 * @param  array              $bundled_item_cart_data
	 * @return void
	 */
	function after_bundled_add_to_cart( $product_id, $quantity, $variation_id, $variations, $bundled_item_cart_data ) {

		global $Product_Addon_Cart;

		// Reset addons and nyp prefix
		$this->addons_prefix = $this->nyp_prefix = '';

		if ( ! empty ( $Product_Addon_Cart ) )
			add_filter( 'woocommerce_add_cart_item_data', array( $Product_Addon_Cart, 'add_cart_item_data' ), 10, 2 );

		// Similarly with NYP
		if ( function_exists( 'WC_Name_Your_Price' ) )
			add_filter( 'woocommerce_add_cart_item_data', array( WC_Name_Your_Price()->cart, 'add_cart_item_data' ), 5, 3 );
	}

	/**
	 * Runs after adding a bundled item to the cart.
	 *
	 * @param  int                $product_id
	 * @param  int                $quantity
	 * @param  int                $variation_id
	 * @param  array              $variations
	 * @param  array              $bundled_item_cart_data
	 * @return void
	 */
	function before_bundled_add_to_cart( $product_id, $quantity, $variation_id, $variations, $bundled_item_cart_data ) {

		global $Product_Addon_Cart;

		// Set addons and nyp prefixes
		$this->addons_prefix = $this->nyp_prefix = $bundled_item_cart_data[ 'bundled_item_id' ];

		// Add-ons cart item data is already stored in the composite_data array, so we can grab it from there instead of allowing Addons to re-add it
		// Not doing so results in issues with file upload validation

		if ( ! empty ( $Product_Addon_Cart ) )
			remove_filter( 'woocommerce_add_cart_item_data', array( $Product_Addon_Cart, 'add_cart_item_data' ), 10, 2 );

		// Similarly with NYP
		if ( function_exists( 'WC_Name_Your_Price' ) )
			remove_filter( 'woocommerce_add_cart_item_data', array( WC_Name_Your_Price()->cart, 'add_cart_item_data' ), 5, 3 );
	}

	/**
	 * Retrieve child cart item data from the parent cart item data array, if necessary.
	 *
	 * @param  array  $bundled_item_cart_data
	 * @param  array  $cart_item_data
	 * @return array
	 */
	function get_bundled_cart_item_data_from_parent( $bundled_item_cart_data, $cart_item_data ) {

		// Add-ons cart item data is already stored in the composite_data array, so we can grab it from there instead of allowing Addons to re-add it

		if ( isset( $bundled_item_cart_data[ 'bundled_item_id' ] ) && isset( $cart_item_data[ 'stamp' ][ $bundled_item_cart_data[ 'bundled_item_id' ] ][ 'addons' ] ) )
			$bundled_item_cart_data[ 'addons' ] = $cart_item_data[ 'stamp' ][ $bundled_item_cart_data[ 'bundled_item_id' ] ][ 'addons' ];

		// Similarly with NYP

		if ( isset( $bundled_item_cart_data[ 'bundled_item_id' ] ) && isset( $cart_item_data[ 'stamp' ][ $bundled_item_cart_data[ 'bundled_item_id' ] ][ 'nyp' ] ) )
			$bundled_item_cart_data[ 'nyp' ] = $cart_item_data[ 'stamp' ][ $bundled_item_cart_data[ 'bundled_item_id' ] ][ 'nyp' ];

		return $bundled_item_cart_data;
	}

	/**
	 * Add addons identifier to bundled item stamp, in order to generate new cart ids for bundles with different addons configurations.
	 *
	 * @param  array  $bundled_item_stamp
	 * @param  string $bundled_item_id
	 * @return array
	 */
	function bundled_item_addons_stamp( $bundled_item_stamp, $bundled_item_id ) {

		global $Product_Addon_Cart;

		// Store bundled item addons add-ons config in stamp to avoid generating the same bundle cart id
		if ( ! empty( $Product_Addon_Cart ) ) {

			$addon_data = array();

			// Set addons prefix
			$this->addons_prefix = $bundled_item_id;

			$bundled_product_id = $bundled_item_stamp[ 'product_id' ];

			$addon_data = $Product_Addon_Cart->add_cart_item_data( $addon_data, $bundled_product_id );

			// Reset addons prefix
			$this->addons_prefix = '';

			if ( ! empty( $addon_data[ 'addons' ] ) )
				$bundled_item_stamp[ 'addons' ] = $addon_data[ 'addons' ];
		}

		return $bundled_item_stamp;
	}

	/**
	 * Add nyp identifier to bundled item stamp, in order to generate new cart ids for bundles with different nyp configurations.
	 *
	 * @param  array  $bundled_item_stamp
	 * @param  string $bundled_item_id
	 * @return array
	 */
	function bundled_item_nyp_stamp( $bundled_item_stamp, $bundled_item_id ) {

		if ( function_exists( 'WC_Name_Your_Price' ) ) {

			$nyp_data = array();

			// Set nyp prefix
			$this->nyp_prefix = $bundled_item_id;

			$bundled_product_id = $bundled_item_stamp[ 'product_id' ];

			$nyp_data = WC_Name_Your_Price()->cart->add_cart_item_data( $nyp_data, $bundled_product_id, '' );

			// Reset nyp prefix
			$this->nyp_prefix = '';

			if ( ! empty( $nyp_data[ 'nyp' ] ) )
				$bundled_item_stamp[ 'nyp' ] = $nyp_data[ 'nyp' ];
		}

		return $bundled_item_stamp;
	}

	/**
	 * Validate bundled item NYP and Addons.
	 *
	 * @param  bool   $add
	 * @param  int    $product_id
	 * @param  int    $quantity
	 * @return bool
	 */
	function validate_bundled_item_nyp_and_addons( $add, $bundle, $bundled_item, $quantity, $variation_id ) {

		// Ordering again? When ordering again, do not revalidate addons & nyp
		$order_again = isset( $_GET[ 'order_again' ] ) && isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( $_GET[ '_wpnonce' ], 'woocommerce-order_again' );

		if ( $order_again  )
			return $add;

		$bundled_item_id = $bundled_item->item_id;
		$product_id      = $bundled_item->product_id;

		// Validate add-ons
		global $Product_Addon_Cart;

		if ( ! empty( $Product_Addon_Cart ) ) {

			$this->addons_prefix = $bundled_item_id;

			if ( ! $Product_Addon_Cart->validate_add_cart_item( true, $product_id, $quantity ) )
				return false;

			$this->addons_prefix = '';
		}

		// Validate nyp
		if ( $this->bundle_prefix ) {
			$has_parent_priced_statically = get_post_meta( $this->bundle_prefix, '_per_product_pricing_bto', true ) == 'yes' ? false : true;
		} else {
			$has_parent_priced_statically = false;
		}

		if ( $bundled_item->is_priced_per_product() && ( ! $has_parent_priced_statically ) && function_exists( 'WC_Name_Your_Price' ) ) {

			$this->nyp_prefix = $bundled_item_id;

			if ( ! WC_Name_Your_Price()->cart->validate_add_cart_item( true, $product_id, $quantity ) )
				return false;

			$this->nyp_prefix = '';
		}

		return $add;
	}

	/**
	 * Support for bundled item addons.
	 *
	 * @param  int               $product_id    the product id
	 * @param  WC_Bundled_Item   $item          the bundled item
	 * @return void
	 */
	function addons_support( $product_id, $item ) {

		global $Product_Addon_Display;

		if ( ! empty( $Product_Addon_Display ) ) {

			$this->addons_prefix          = $item->item_id;
			$this->compat_bundled_product = $item->product;

			$Product_Addon_Display->display( $product_id, false );

			$this->addons_prefix = $this->compat_bundled_product = '';
		}
	}

	/**
	 * Sets a unique prefix for unique add-ons. The prefix is set and re-set globally before validating and adding to cart.
	 *
	 * @param  string   $prefix         unique prefix
	 * @param  int      $product_id     the product id
	 * @return string                   a unique prefix
	 */
	function addons_cart_prefix( $prefix, $product_id ) {

		if ( ! empty( $this->addons_prefix ) )
			$prefix = $this->addons_prefix . '-';

		if ( ! empty( $this->bundle_prefix ) )
			$prefix = $this->bundle_prefix . '-' . $this->addons_prefix . '-';

		return $prefix;
	}

	/**
	 * Support for bundled item NYP.
	 *
	 * @param  int               $product_id     the product id
	 * @param  WC_Bundled_Item   $item           the bundled item
	 * @return void
	 */
	function nyp_price_input_support( $product_id, $item ) {

		global $product;

		$the_product = ! empty( $this->compat_product ) ? $this->compat_product : $product;

		if ( $the_product->product_type == 'bundle' && $the_product->is_priced_per_product() == false )
			return;

		if ( function_exists( 'WC_Name_Your_Price' ) && $item->product->product_type == 'simple' ) {

			$this->nyp_prefix = $item->item_id;

			WC_Name_Your_Price()->display->display_price_input( $product_id, $this->nyp_cart_prefix( false, $product_id ) );

			$this->nyp_prefix = '';
		}


	}

	/**
	 * Sets a unique prefix for unique NYP products. The prefix is set and re-set globally before validating and adding to cart.
	 *
	 * @param  string   $prefix         unique prefix
	 * @param  int      $product_id     the product id
	 * @return string                   a unique prefix
	 */
	function nyp_cart_prefix( $prefix, $product_id ) {

		if ( ! empty( $this->nyp_prefix ) )
			$prefix = '-' . $this->nyp_prefix;

		if ( ! empty( $this->bundle_prefix ) )
			$prefix = '-' . $this->nyp_prefix . '-' . $this->bundle_prefix;

		return $prefix;
	}

	/**
	 * Tells if a product is a Name Your Price product, provided that the extension is installed.
	 *
	 * @param  mixed    $product_id   product or id to check
	 * @return boolean                true if NYP exists and product is a NYP
	 */
	function is_nyp( $product_id ) {

		if ( ! class_exists( 'WC_Name_Your_Price_Helpers' ) )
			return false;

		if ( WC_Name_Your_Price_Helpers::is_nyp( $product_id ) )
			return true;

		return false;
	}

	/**
	 * Tells if a product is a subscription, provided that Subs is installed.
	 *
	 * @param  mixed    $product_id   product or id to check
	 * @return boolean                true if Subs exists and product is a Sub
	 */
	function is_subscription( $product_id ) {

		if ( ! class_exists( 'WC_Subscriptions' ) )
			return false;

		$is_subscription = false;

		if ( is_object( $product_id ) )
			$product_id = $product_id->id;

		$post_type = get_post_type( $product_id );

		if ( in_array( $post_type, array( 'product' ) ) ) {

			$product = WC_Subscriptions::get_product( $product_id );

			if ( $product->is_type( array( 'subscription' ) ) )
				$is_subscription = true;

		}

		return apply_filters( 'woocommerce_is_subscription', $is_subscription, $product_id );
	}

	/**
	 * Tells if an order item is a subscription, provided that Subs is installed.
	 *
	 * @param  mixed      $order   order to check
	 * @param  WC_Prder   $order   item to check
	 * @return boolean             true if Subs exists and item is a Sub
	 */
	function is_item_subscription( $order, $item ) {

		if ( ! class_exists( 'WC_Subscriptions_Order' ) )
			return false;

		return WC_Subscriptions_Order::is_item_subscription( $order, $item );
	}

	/**
	 * Checks if a product has any required addons.
	 *
	 * @param  int       $product_id   id of product to check
	 * @return boolean                 result
	 */
	function has_required_addons( $product_id ) {

		if ( ! function_exists( 'get_product_addons' ) )
			return false;

		$addons = get_product_addons( $product_id );

		if ( $addons && ! empty( $addons ) ) {
			foreach ( $addons as $addon ) {
				if ( '1' == $addon[ 'required' ] ) {
					return true;
				}
			}
		}

		return false;
	}

}
