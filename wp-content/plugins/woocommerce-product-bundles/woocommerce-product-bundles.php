<?php
/*
* Plugin Name: WooCommerce Product Bundles
* Plugin URI: http://www.woothemes.com/products/product-bundles/
* Description: WooCommerce extension for creating simple product bundles, kits and assemblies.
* Version: 4.9.5
* Author: WooThemes
* Author URI: http://woothemes.com/
* Developer: SomewhereWarm
* Developer URI: http://somewherewarm.net/
*
* Text Domain: woocommerce-product-bundles
* Domain Path: /languages/
*
* Requires at least: 3.8
* Tested up to: 4.2
*
* Copyright: © 2009-2015 WooThemes.
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), 'fbca839929aaddc78797a5b511c14da9', '18716' );

// Check if WooCommerce is active
if ( ! is_woocommerce_active() ) {
	return;
}

/**
 * # Product Bundles
 *
 * This extension implements bundling functionalities by utilizing a container product (the "bundle" type) that triggers the addition of other products to the cart.
 * The extension does its own validation on the container product in order to ensure that all "bundled products" can be added to the cart.
 * Bundled products are added on the woocommerce_add_to_cart hook after adding the main container item.
 * Using a main container item makes it possible to define pricing properties and/or physical properties that replace the pricing and/or physical properties of the bundled products. This is useful when the bundle has a new static price and/or new shipping properties.
 * Depending on the chosen pricing / shipping mode, the container item OR the bundled products are marked as virtual, or are assigned a zero price in the cart.
 * To avoid confusion with zero prices in the front end, the extension filters the displayed price strings, cart item meta and markup classes in order to give the impression of a bundling relationship between the container item and the 'children' items.
 *
 * @class 	WC_Bundles
 * @version 4.9.5
 */

class WC_Bundles {

	public $version  = '4.9.5';
	public $required = '2.1.0';

	public $admin;
	public $helpers;
	public $cart;
	public $order;
	public $display;
	public $compatibility;

	public function __construct() {

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	function woo_bundles_plugin_url() {
		return plugins_url( basename( plugin_dir_path(__FILE__) ), basename( __FILE__ ) );
	}

	function woo_bundles_plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	function plugins_loaded() {

		global $woocommerce;

		// WC 2 check
		if ( version_compare( $woocommerce->version, $this->required ) < 0 ) {
			add_action( 'admin_notices', array( $this, 'admin_notice' ) );
			return false;
		}

		// Class containing core compatibility functions and filters
		require_once( 'includes/class-wc-pb-core-compatibility.php' );

		// Functions for back-compat
		include( 'includes/wc-pb-functions.php' );

		// Class containing helper functions and filters
		require_once( 'includes/class-wc-pb-helpers.php' );
		$this->helpers = new WC_PB_Helpers();

		// Class containing extenstions compatibility functions and filters
		require_once( 'includes/class-wc-pb-compatibility.php' );
		$this->compatibility = new WC_PB_Compatibility();

		// WC_Bundled_Item and WC_Product_Bundle classes
		require_once( 'includes/class-wc-bundled-item.php' );
		require_once( 'includes/class-wc-product-bundle.php' );

		require_once( 'includes/class-wc-pb-stock-manager.php' );

		// Admin functions and meta-boxes
		if ( is_admin() ) {
			$this->admin_includes();
		}

		// Cart-related bundle functions and filters
		require_once( 'includes/class-wc-pb-cart.php' );
		$this->cart = new WC_PB_Cart();

		// Order-related bundle functions and filters
		require_once( 'includes/class-wc-pb-order.php' );
		$this->order = new WC_PB_Order();

		// Front-end filters
		require_once( 'includes/class-wc-pb-display.php' );
		$this->display = new WC_PB_Display();

	}

	/**
	 * Loads the Admin & AJAX filters / hooks.
	 * @return void
	 */
	function admin_includes() {

		require_once( 'includes/admin/class-wc-pb-admin.php' );
		$this->admin = new WC_PB_Admin();
	}

	/**
	 * Display a warning message if WC version check fails.
	 * @return void
	 */
	function admin_notice() {

	    echo '<div class="error"><p>' . sprintf( __( 'WooCommerce Product Bundles requires at least WooCommerce %s in order to function. Please upgrade WooCommerce.', 'woocommerce-product-bundles' ), $this->required ) . '</p></div>';
	}

	/**
	 * Load textdomain.
	 * @return void
	 */
	function init() {

		load_plugin_textdomain( 'woocommerce-product-bundles', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Update or create 'bundle' product type on activation as required.
	 * @return void
	 */
	public function activate() {

			global $wpdb;

			$version = get_option( 'woocommerce_product_bundles_version', false );

			if ( $version == false ) {

				// Update or create 'bundle' product type on activation as required

				$bundle_type_exists = false;

				$product_type_terms = get_terms( 'product_type', array( 'hide_empty' => false ) );

				foreach ( $product_type_terms as $product_type_term ) {

					if ( $product_type_term->name === 'bundle' ) {
						$bundle_type_exists = true;
					}
				}

				if ( ! $bundle_type_exists ) {

					// Check for existing 'bundle' slug and if it exists, modify it
					if ( $existing_term_id = term_exists( 'bundle' ) ) {
						$wpdb->update( $wpdb->terms, array( 'slug' => 'bundle-b' ), array( 'term_id' => $existing_term_id ) );
					}

					wp_insert_term( 'bundle', 'product_type' );
				}

				add_option( 'woocommerce_product_bundles_version', $this->version );

				// Update from previous versions

				// delete old option
				delete_option( 'woocommerce_product_bundles_active' );

				// delete old transients
				$wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_wc_bundled_item_%') OR `option_name` LIKE ('_transient_timeout_wc_bundled_item_%')" );

			} elseif ( version_compare( $version, $this->version, '<' ) ) {

				update_option( 'woocommerce_product_bundles_version', $this->version );
			}

		}

	/**
	 * Deactivate extension.
	 * @return void
	 */
	public function deactivate() {

		delete_option( 'woocommerce_product_bundles_version' );
	}
}

$GLOBALS[ 'woocommerce_bundles' ] = new WC_Bundles();
