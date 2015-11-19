<?php
/**
 * Plugin Name: WooCommerce Customer History
 * Plugin URI: http://woothemes.com/products/woocommerce-customer-history
 * Description: Track and store customer browsing history with their order.
 * Version: 1.1.1
 * Author: Brian Richards
 * Author URI: http://rzen.net
 * License: GPL2
 * Text Domain: woocommerce-customer-history
 * Domain Path: /languages
 */

/*
Copyright 2013 rzen Media, LLC (email : brian@rzen.net)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

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
woothemes_queue_update( plugin_basename( __FILE__ ), 'afcaee0cdf9817db5528d7c24b1427d4', '455753' );

/**
 * Main plugin instantiation class.
 *
 * @since 1.0.0
 */
class WooCommerce_Customer_History {

	/**
	 * Fire up the engines.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Define plugin constants
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugin_dir_url( __FILE__ );

		// Basic setup
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );
		add_action( 'plugins_loaded', array( $this, 'i18n' ) );
		add_action( 'plugins_loaded', array( $this, 'includes' ) );

	}

	/**
	 * Load localization.
	 *
	 * @since 1.0.0
	 */
	public function i18n() {
		load_plugin_textdomain( 'woocommerce-customer-history', false, $this->directory_path . '/languages/' );
	} /* i18n() */

	/**
	 * Include file dependencies.
	 *
	 * @since 1.0.0
	 */
	public function includes() {
		if ( $this->meets_requirements() ) {
			require_once( $this->directory_path . '/includes/utilities.php' );
			require_once( $this->directory_path . '/includes/class-wcch-cookie-helper.php' );
			require_once( $this->directory_path . '/includes/track-history.php' );
			require_once( $this->directory_path . '/includes/show-history.php' );
			require_once( $this->directory_path . '/includes/settings.php' );
		}
	} /* includes() */

	/**
	 * Check if all requirements are met.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if requirements are met, otherwise false.
	 */
	private function meets_requirements() {
		return ( class_exists( 'WooCommerce' ) && version_compare( WC()->version, '2.1.0', '>=' ) );
	} /* meets_requirements() */

	/**
	 * Output error message and disable plugin if requirements are not met.
	 *
	 * This fires on admin_notices.
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {

		if ( ! $this->meets_requirements() ) {
			// Display our error
			echo '<div id="message" class="error">';
			echo '<p>' . sprintf( __( 'WooCommerce Customer History requires WooCommerce 2.1.0 or greater and has been <a href="%s">deactivated</a>. Please install, activate or update WooCommerce and then reactivate this plugin.', 'woocommerce-customer-history' ), admin_url( 'plugins.php' ) ) . '</p>';
			echo '</div>';

			// Deactivate our plugin
			deactivate_plugins( $this->basename );
		}

	} /* maybe_disable_plugin() */

}
$GLOBALS['woocommerce_customer_history'] = new WooCommerce_Customer_History;
