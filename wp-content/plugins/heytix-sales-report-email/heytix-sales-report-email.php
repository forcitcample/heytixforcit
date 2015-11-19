<?php
/*
	Plugin Name: Heytix Sales Report Email
	Description: Daily Sales Report Email
	Version: 1.0.0
	Author: Heytix
	Author URI: http://www.heytix.com/
	 
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	 
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	 
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Heytix_Sales_Report_Email {

	/**
	 * Get the plugin file
	 *
	 * @access public
	 * @static
	 * @since  1.0.0
	 * @return String
	 */
	public static function get_plugin_file() {
		return __FILE__;
	}

	/**
	 * A static method that will setup the autoloader
	 *
	 * @access private
	 * @since  1.0.0
	 */
	private static function setup_autoloader() {
		require_once( plugin_dir_path( self::get_plugin_file() ) . '/classes/class-heytix-sre-autoloader.php' );
		$autoloader = new Heytix_SRE_Autoloader( plugin_dir_path( self::get_plugin_file() ) . 'classes/' );
		spl_autoload_register( array( $autoloader, 'load' ) );
	}

	/**
	 * This method will be run on plugin activation
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public static function activation() {

		// Setup the autoloader
		self::setup_autoloader();

		// Setup Cron
		$cron_manager = new Heytix_SRE_Cron_Manager();
		$cron_manager->setup_cron();

	}

	/**
	 * This method wil run on plugin deactivation
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public static function deactivation() {

		// Setup the autoloader
		self::setup_autoloader();

		// Remove Cron
		$cron_manager = new Heytix_SRE_Cron_Manager();
		$cron_manager->remove_cron();

	}

	/**
	 * Constructor
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function __construct() {

		// Check if WC is activated
		if ( $this->is_wc_active() ) {
			$this->init();
		}

	}

	/**
	 * Check if WooCommerce is active
	 *
	 * @access private
	 * @since  1.0.0
	 * @return bool
	 */
	private function is_wc_active() {

		$is_active = WC_Dependencies::woocommerce_active_check();

		// Do the WC active check
		if ( $is_active !== WC_Dependencies::woocommerce_active_check() ) {
			add_action( 'admin_notices', array( $this, 'notice_activate_wc' ) );
		}

		return $is_active;
	}

	/**
	 * Initialize the plugin
	 *
	 * @access private
	 * @since  1.0.0
	 */
	private function init() {

		// Load plugin textdomain
		load_plugin_textdomain( 'heytix-sales-report-email', false, plugin_dir_path( self::get_plugin_file() ) . 'languages/' );

		// Setup the autoloader
		self::setup_autoloader();

		// Only load in admin
		if ( is_admin() ) {

			// Setup the settings
			$settings = new Heytix_SRE_Settings();
			$settings->setup();

		}

		// Cron hook
		add_action( Heytix_SRE_Cron_Manager::CRON_HOOK, array( $this, 'cron_email_callback' ) );

	}

	/**
	 * Method triggered on Cron run.
	 * This method will create a WC_SRE_Sales_Report_Email object and call trigger method.
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function cron_email_callback() {
		error_log('Cron Called...');

		include_once( WC()->plugin_path() . '/includes/emails/class-wc-email.php' );
		include_once( WC()->plugin_path() . '/includes/libraries/class-emogrifier.php' );

		// This will be a WP Cron action
		$email = new Heytix_SRE_Sales_Report_Email();
		$email->trigger();
	}

	/**
	 * Display the WC
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function notice_activate_wc() {
		?>
		<div class="error">
			<p><?php printf( __( 'Please install and activate %sWooCommerce%s for Heytix Sales Report Email to work!', 'heytix-sales-report-email' ), '<a href="' . admin_url( 'plugin-install.php?tab=search&s=WooCommerce&plugin-search-input=Search+Plugins' ) . '">', '</a>' ); ?></p>
		</div>
		<?php
	}

}

function __heytix_sales_report_email_main() {
	new Heytix_Sales_Report_Email();
}

// Create object - Plugin init
add_action( 'plugins_loaded', '__heytix_sales_report_email_main' );

// Activation hook
register_activation_hook( __FILE__, array('Heytix_Sales_Report_Email', 'activation' ) );

// Deactivation hook
register_deactivation_hook( __FILE__, array('Heytix_Sales_Report_Email', 'deactivation' ) );
