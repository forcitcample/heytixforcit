<?php
/*
 * Handles loading of classes and environment
 *
 * @author Schoenmann Guenter
 * @version 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) {  exit;  }   // Exit if accessed directly

global $wc_add_fees_globals;

require_once $wc_add_fees_globals['plugin_path'] . 'classes/class-wc-add-fees.php';

WC_Add_Fees::$show_activation = false;			//	true to show deactivation and uninstall checkbox
WC_Add_Fees::$show_uninstall = false;
WC_Add_Fees::$plugin_path = $wc_add_fees_globals['plugin_path'];
WC_Add_Fees::$plugin_url = $wc_add_fees_globals['plugin_url'];			//	also set in init hook to allow other plugins to change it in a filter hook
WC_Add_Fees::$plugin_base_name = $wc_add_fees_globals['plugin_base_name'];

		//	initialise, attach to hooks and to load textdomain
WC_Add_Fees::instance();


