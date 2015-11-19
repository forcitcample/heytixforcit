<?php
/*
Plugin Name: WooCommerce Additional Fees
Plugin URI: https://www.woothemes.com/products/payment-gateway-based-fees/ 
Description: This <a href="http://www.inoplugs.com" target="_blank">Additional Fees Plugin</a> adds additional fees to an order automatically depending on the payment gateway. The fees are calculated on product level and/or on total cart value on checkout page, pay-for-order page and admin order page. The admin can change the amount added in the backend and can also disable calculation of fees for a specific order for the pay-for-order page.<br /> Email to <a href="mailto:support@inoplugs.com">support@inoplugs.com</a> with any questions.
Version: 2.2.6
Author: Schoenmann Guenter / InoPlugs
Author URI: http://inoplugs.com
Text Domain: woocommerce_additional_fees
*/

/*  Copyright 2013-2014  Inoplugs  (email : support@inoplugs.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/
if ( ! defined( 'ABSPATH' ) ) {   exit;  } // Exit if accessed directly
// 
// Required functions
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

// Plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), 'c634a2d133341d02fd2cbe7ee00e7fbe', '272217' );

/**
 * Check for activation, .... to speed up loading
 */

//	backward compatibility for Version 1.0.3 only
global $ips_are_activation_hooks;
$ips_are_activation_hooks = false;


global $wc_add_fees_globals;

$wc_add_fees_globals = array(
					'plugin_file'		=> __FILE__,
					'plugin_path'		=> str_replace(basename( __FILE__), '', __FILE__ ),
					'plugin_base_name'	=> plugin_basename( __FILE__ ),
					'plugin_url'		=> trailingslashit(plugins_url( '', str_replace(basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) ) ),
					'activation_hook'	=> false,
					'activation_object' => null
				);

if( is_admin() )
{
	$action = isset( $_REQUEST['action']) ? $_REQUEST['action'] : '';

	switch ( $action)
	{
		case 'activate':
		case 'deactivate':
			if( isset( $_REQUEST['plugin'] ) && ( $_REQUEST['plugin'] == $wc_add_fees_globals['plugin_base_name'] ) )
			{
				$wc_add_fees_globals['activation_hook'] = true;
				$ips_are_activation_hooks = true;
			}
			break;
		case 'activate-selected':
		case 'deactivate-selected':
		case 'delete-selected':
			if( isset( $_REQUEST['checked'] ) && is_array ( $_REQUEST['checked'] ) && in_array( $wc_add_fees_globals['plugin_base_name'], $_REQUEST['checked'] ) )
			{
				$wc_add_fees_globals['activation_hook'] = true;
				$ips_are_activation_hooks = true;
			}
			break;
		default:
			$wc_add_fees_globals['activation_hook'] = false;
			$ips_are_activation_hooks = false;
			break;
	}
}

if( ! function_exists( 'wc_add_fees_load_plugin_version' ) )
{
	function wc_add_fees_load_plugin_version()
	{
		global $wc_add_fees_globals;
		
		$version = 'old';
		
		if ( ! ( function_exists( 'WC' ) || $wc_add_fees_globals['activation_hook'] ) )		//	up to 2.0.20 only WC does not exist
		{
			require_once $wc_add_fees_globals['plugin_path'] . 'woocommerce_additional_fees_loader_v103.php';
		}
		else
		{
						//	if WC not active, by default use latest version for activation hooks
			$version = ( function_exists( 'WC' ) ) ? WC()->version : '2.1.6';
			if( version_compare( $version, '2.1.6', '<' ) )
			{
				require_once $wc_add_fees_globals['plugin_path'] . 'woocommerce_additional_fees_loader_v103.php';
			}
			else
			{
				require_once $wc_add_fees_globals['plugin_path'] . 'woocommerce_additional_fees_loader.php';
				$version = 'new';
			}
		}
		
		if( is_admin() && $wc_add_fees_globals['activation_hook'] )
		{
				//	backwards compatibility
//			if( class_exists( 'woocommerce_additional_fees_activation' ) )
			if($version == 'old' )
			{
				$wc_add_fees_globals['activation_object'] = new woocommerce_additional_fees_activation();
			}
			else
			{
				$wc_add_fees_globals['activation_object'] = new WC_Add_Fees_Activation();
			}
		}
	}
}

if( ! function_exists( 'woocomm_add_fee_check_woocomm_is_loaded' ) )
{
	/**
	 * if WooCommerce was not loaded = disabled, we have to load our plugin for activationhooks
	 */
	function woocomm_add_fee_check_woocomm_is_loaded()
	{
		if( class_exists( 'WooCommerce' ) )
		{
			return;
		}
		wc_add_fees_load_plugin_version();
	}
}

/**
 * To ensure backwards compatibility with WC we have to decide, which version of our plugin to activate
 * As WP does not call 'plugins_loaded' hook on activation, we have to implement it this way 
 */
if( $wc_add_fees_globals['activation_hook'] )
{
	require_once $wc_add_fees_globals['plugin_path'] . 'woocommerce_additional_fees_activation.php';
}
else 
{
	/**
	 * We need this class to decide, if we have to fallback to older versions for backup compatibility
	 */
	if ( class_exists( 'WooCommerce' ) ) 
	{
		wc_add_fees_load_plugin_version();
	}
	else
	{
		add_action( 'before_woocommerce_init', 'wc_add_fees_load_plugin_version' );
	}
}
