<?php
/**
 * Holds the functions needed for backward compatibility for activation/deactivation/uninstall 
 */
if ( ! defined( 'ABSPATH' ) ) {  exit;  } // Exit if accessed directly

global $wc_add_fees_globals;

if( ! function_exists( 'handler_woocommerce_additional_fees_activate' ) )
{
	function handler_woocommerce_additional_fees_activate()
	{
		global $wc_add_fees_globals;
		
		wc_add_fees_load_plugin_version();
		$wc_add_fees_globals['activation_object']->on_activate();
	}
}

if( ! function_exists( 'handler_woocommerce_additional_fees_deactivate' ) )
{
	function handler_woocommerce_additional_fees_deactivate()
	{
		global $wc_add_fees_globals;
		
		wc_add_fees_load_plugin_version();
		$wc_add_fees_globals['activation_object']->on_deactivate();
	}
}

if( ! function_exists( 'handler_woocommerce_additional_fees_uninstall' ) )
{
	function handler_woocommerce_additional_fees_uninstall()
	{
		global $wc_add_fees_globals;
		
		wc_add_fees_load_plugin_version();
		$wc_add_fees_globals['activation_object']->on_uninstall();
	}
}

/**
 * To ensure backwards compatibility with WC we have to decide, which version of our plugin to activate
 * As WP does not call 'plugins_loaded' hook on activation, we have to implement it this way 
 */
if( $wc_add_fees_globals['activation_hook'] )
{
	/**
	 * Register activation, deactivation, uninstall hooks
	 * ==================================================
	 *
	 * See Documentation for WP 3.3.1
	 */

	register_activation_hook( $wc_add_fees_globals['plugin_file'], 'handler_woocommerce_additional_fees_activate' );
	register_deactivation_hook( $wc_add_fees_globals['plugin_file'], 'handler_woocommerce_additional_fees_deactivate' );
	register_uninstall_hook( $wc_add_fees_globals['plugin_file'], 'handler_woocommerce_additional_fees_uninstall' );
	
}