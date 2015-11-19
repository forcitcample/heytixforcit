<?php
/**
 * Plugin Name: WooCommerce Product Finder
 * Description: An advanced search for WooCommerce that helps your customers find your products more easily.
 * Version: 1.2.0
 * Author: WooThemes
 * Author URI: http://www.woothemes.com
 * Requires at least: 3.3
 * Tested up to: 3.8.1
 *
 *	Copyright: © 2009-2011 WooThemes.
 *	License: GNU General Public License v3.0
 *	License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), 'bc4e288ac15205345ce9c506126b3f75', '163906' );

if ( is_woocommerce_active() ) {

	if( ! function_exists( 'woocommerce_product_finder_scripts' ) ) {
		function woocommerce_product_finder_scripts() {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// Load Javascript
			wp_register_script( 'wc_product_finder' , plugins_url( 'assets/js/scripts' . $suffix . '.js' , __FILE__ ) , array( 'jquery' ) );
			wp_enqueue_script( 'wc_product_finder' );

			// Localise Javascript
			wp_localize_script( 'wc_product_finder', 'wc_product_finder_data', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

			// Load CSS
			wp_register_style( 'wc_product_finder' , plugins_url( 'assets/css/style.css' , __FILE__ ) );
			wp_enqueue_style( 'wc_product_finder' );

		}
	}
	add_action( 'wp_enqueue_scripts' , 'woocommerce_product_finder_scripts' );

	if( ! function_exists( 'woocommerce_product_finder_init' ) ) {
		/**
		 * Load plugin localisation.
		 * @since  1.2.0
		 * @return void
		 */
		function woocommerce_product_finder_init() {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-product-finder' );
			$dir    = trailingslashit( WP_LANG_DIR );

			load_textdomain( 'woocommerce-product-finder', $dir . 'woocommerce-product-finder/woocommerce-product-finder-' . $locale . '.mo' );
			load_plugin_textdomain( 'woocommerce-product-finder', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		} // End woocommerce_product_finder_init()
	}
	add_action( 'init', 'woocommerce_product_finder_init' );

	require( 'classes/class-woocommerce-product-finder.php' );
	require( 'woocommerce-product-finder-functions.php' );
	require( 'classes/class-woocommerce-product-finder-widget.php' );

	if( is_admin() ) {
		require( 'classes/class-woocommerce-product-finder-admin.php' );
	}

}