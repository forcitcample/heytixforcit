<?php
/*
 * Handles loading of classes and environment
 *
 * @author Schoenmann Guenter
 * @version 1.0.0.0
 */
if ( ! defined( 'ABSPATH' ) )  {  exit;  }   // Exit if accessed directly

$plugin_path = str_replace(basename( __FILE__), '', __FILE__ );

require_once $plugin_path.'v103/classes/woocommerce_additional_fees.php';
require_once $plugin_path.'v103/classes/wc_calc_add_fee.php';

woocommerce_additional_fees::$show_activation = false;			//	true to show deactivation and uninstall checkbox
woocommerce_additional_fees::$show_uninstall = false;
woocommerce_additional_fees::$plugin_path = $plugin_path;
woocommerce_additional_fees::$plugin_url = WP_PLUGIN_URL . '/' . str_replace(basename( __FILE__ ), '' ,plugin_basename( __FILE__ ) );

	//	initialise, attach to hooks and to load textdomain
global $woocommerce_additional_fees, $ips_are_activation_hooks;
$woocommerce_additional_fees = new woocommerce_additional_fees();


if( is_admin() )
{
	require_once $plugin_path.'v103/classes/woocommerce_addons_add_fees.php';
	require_once $plugin_path.'v103/classes/woocommerce_additional_fees_admin.php';
	require_once $plugin_path.'v103/classes/panels/wc_panel_admin.php';

	if( $ips_are_activation_hooks )
	{
		require_once $plugin_path.'v103/classes/woocommerce_additional_fees_activation.php';
	}

	$obj = new woocommerce_additional_fees_admin();
}


/*
 * temp fix for "Pay Order" page. If the user selected a gateway on the checkout page but didn't pay for the order he can pay the pending order from the "My account" page. This
 * function makes sure that the same gateway is selected and locked - otherwise WC returns an error because required fields are missing. 
 */
add_filter( 'woocommerce_available_payment_gateways', 'woocommerce_remove_not_working_gateways', 10, 1 );
function woocommerce_remove_not_working_gateways( $gateways )
{
	global $woocommerce;

	if( $woocommerce->session->order_awaiting_payment > 0 )
	{
		$order = new WC_Order( $woocommerce->session->order_awaiting_payment );
		$fee = $order->get_fees();
		
		if( ! empty( $fee ) )
		{
			if( ! empty( $order->payment_method ) )
			{
				$gateways = array( $order->payment_method => $gateways[ $order->payment_method ] );
			}
		}
	}
 
	return $gateways;
}
