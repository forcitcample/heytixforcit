<?php
/*
 * Plugin Name: WooCommerce Order Barcodes
 * Version: 1.2.0
 * Plugin URI: http://www.woothemes.com/products/woocommerce-order-barcodes/
 * Description: Generates unique barcodes for your orders - perfect for e-tickets, packing slips, reservations and a variety of other uses.
 * Author: Hugh Lashbrooke
 * Author URI: http://www.hughlashbrooke.com/
 * Requires at least: 4.0
 * Tested up to: 4.1.1
 *
 * @package WordPress
 * @author Hugh Lashbrooke
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '889835bb29ee3400923653e1e44a3779', '391708' );

if ( is_woocommerce_active() ) {

	// Include plugin class files
	require_once( 'includes/class-woocommerce-order-barcodes.php' );
	require_once( 'includes/class-woocommerce-order-barcodes-settings.php' );

	// Include plugin functions file
	require_once( 'includes/woocommerce-order-barcodes-functions.php' );

	/**
	 * Returns the main instance of WooCommerce_Order_Barcodes to prevent the need to use globals.
	 *
	 * @since  1.0.0
	 * @return object WooCommerce_Order_Barcodes instance
	 */
	function WC_Order_Barcodes () {
		$instance = WooCommerce_Order_Barcodes::instance( __FILE__, '1.2.0' );
		if( is_null( $instance->settings ) ) {
			$instance->settings = WooCommerce_Order_Barcodes_Settings::instance( $instance );
		}
		return $instance;
	}

	// Initialise plugin
	WC_Order_Barcodes();
}