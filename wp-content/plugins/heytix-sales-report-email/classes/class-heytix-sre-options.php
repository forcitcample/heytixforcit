<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Heytix_SRE_Options {

	const OPTION_NAME = 'woocommerce_sales_report_email_settings';

	/**
	 * Get the options
	 *
	 * @since  1.0.0
	 * @access private
	 * @static
	 *
	 * @return array
	 */
	private static function get_options() {
		return wp_parse_args( get_option( self::OPTION_NAME, array() ), array( 'enabled' => 'yes', 'venues' => 'yes', 'recipients' => '', 'interval' => 'daily' ) );
	}

	/**
	 * Check if Sales Report Email extension is enabled
	 *
	 * @since  1.0.0
	 * @access public
	 * @static
	 *
	 * @return mixed|void
	 */
	public static function is_enabled() {

		// Get the SRE options
		$sre_options = self::get_options();

		/**
		 * Filter: 'wc_sales_report_email_enabled' - Allow altering if sales report email is enabled
		 *
		 * @api bool $enabled Enabled state
		 */

		return apply_filters( 'wc_sales_report_email_enabled', ( ( 'yes' == $sre_options['enabled'] ) ? true : false ) );
	}

	/**
	 * Check if Sales Report Emails are also sent to venues
	 *
	 * @since  1.0.0
	 * @access public
	 * @static
	 *
	 * @return mixed|void
	 */
	public static function is_sending_to_venues() {

		// Get the SRE options
		$sre_options = self::get_options();

		/**
		 * Filter: 'wc_sales_report_email_send_to_venues' - Allow altering if reports are sent to venues
		 *
		 * @api bool $enabled Enabled state
		 */

		return apply_filters( 'wc_sales_report_email_send_to_venues', ( ( 'yes' == $sre_options['venues'] ) ? true : false ) );
	}
	/**
	 * Get the sales report recipients
	 *
	 * @access public
	 * @since  1.0.0
	 * @static
	 *
	 * @return String
	 */
	public static function get_recipients() {

		// Get the SRE options
		$sre_options = self::get_options();

		/**
		 * Filter: 'heytix_sales_report_email_recipients' - Allow altering sales report email recipients
		 *
		 * @api string $recipients The recipients
		 */

		return apply_filters( 'heytix_sales_report_email_recipients', $sre_options['recipients'] );
	}

	/**
	 * Get the interval option
	 *
	 * @access public
	 * @since  1.0.0
	 * @static
	 *
	 * @return string
	 */
	public static function get_interval() {

		// Get the SRE options
		$sre_options = self::get_options();

		/**
		 * Filter: 'heytix_sales_report_email_interval' - Allow altering sales report email interval
		 *
		 * @api string $interval The interval, possible values: daily, weekly, monthly. Default: daily.
		 */

		return apply_filters( 'heytix_sales_report_email_interval', $sre_options['interval'] );
	}

}