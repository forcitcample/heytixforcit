<?php
/*
Plugin Name: The Events Calendar: WooCommerce Tickets
Description: The Events Calendar: WooCommerce Tickets allows you to sell tickets to events through WooCommerce
Version: 3.10
Author: Modern Tribe, Inc.
Author URI: http://m.tri.be/28
License: GPLv2 or later
Text Domain: tribe-wootickets
Domain Path: /lang/
 */

/*
 Copyright 2010-2012 by Modern Tribe Inc and the contributors

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

if ( ! defined( 'ABSPATH' ) ) die( '-1' );

define( 'EVENTS_TICKETS_WOO_DIR', dirname( __FILE__ ) );

// DEPRECATED: TRIBE_WOOTICKETS_DIR is deprecated and will be removed in a future release
define( 'TRIBE_WOOTICKETS_DIR', dirname( __FILE__ ) );

add_action( 'plugins_loaded', 'wootickets_init' );

/**
 * Load WooCommerce Tickets classes and verify if the min required conditions are met.
 *
 * If they are, it instantiates the Tribe__Events__Tickets__Woo__Main singleton.
 * If they are not, it uses the admin_notices hook with tribe_wootickets_show_fail_message
 *
 */
function wootickets_init() {
	tribe_init_woo_tickets_autoloading();

	if ( ! wootickets_should_run() || ! class_exists( 'Tribe__Events__Tickets__Tickets' ) ) {
		$langpath = trailingslashit( basename( dirname( __FILE__ ) ) ) . 'lang/';
		load_plugin_textdomain( 'tribe-wootickets', false, $langpath );
		add_action( 'admin_notices', 'tribe_wootickets_show_fail_message' );

		return;
	}

	new Tribe__Events__Tickets__Woo__PUE( __FILE__ );
	Tribe__Events__Tickets__Woo__Main::init();
}

/**
 * Requires the autoloader class from the main plugin class and sets up
 * autoloading.
 */
function tribe_init_woo_tickets_autoloading() {
	if ( ! class_exists( 'Tribe__Events__Autoloader' ) ) {
		return;
	}

	$autoloader = Tribe__Events__Autoloader::instance();

	$autoloader->register_prefix( 'Tribe__Events__Tickets__Woo__', dirname( __FILE__ ) . '/src/Tribe' );

	// deprecated classes are registered in a class to path fashion
	foreach ( glob( dirname( __FILE__ ) . '/src/deprecated/*.php' ) as $file ) {
		$class_name = str_replace( '.php', '', basename( $file ) );
		$autoloader->register_class( $class_name, $file );
	}
	$autoloader->register_autoloader();
}

/**
 * Whether the current version is incompatible with the installed and active WooCommerce
 * @return bool
 */
function is_incompatible_woocommerce_installed() {
	if ( ! class_exists( 'Woocommerce' ) )
		return true;

	if ( ! class_exists( 'Tribe__Events__Tickets__Woo__Main' ) )
		return true;

	global $woocommerce;
	if ( ! version_compare( $woocommerce->version, Tribe__Events__Tickets__Woo__Main::REQUIRED_WC_VERSION, '>=' ) )
		return true;

	return false;
}

/**
 * Whether the current version is incompatible with the installed and active The Events Calendar
 * @return bool
 */
function tribe_wootickets_is_incompatible_events_core_installed () {
	if ( ! class_exists( 'Tribe__Events__Tickets__Tickets' ) ) {
		return true;
	}

	if ( ! class_exists( 'Tribe__Events__Tickets__Woo__Main' ) ) {
		return true;
	}

	if ( ! version_compare( Tribe__Events__Main::VERSION, Tribe__Events__Tickets__Woo__Main::REQUIRED_TEC_VERSION, '>=' ) ) {
		return true;
	}

	return false;
}


/**
 * Verifies if the min required conditions are met.
 * @return bool
 */
function wootickets_should_run() {

	if ( tribe_wootickets_is_incompatible_events_core_installed() )
		return false;

	if ( tribe_wootickets_is_incompatible_events_core_installed() )
		return false;

	return true;
}


/**
 * Shows an admin_notices message explaining why it couldn't be activated.
 */
function tribe_wootickets_show_fail_message() {
	if ( ! current_user_can( 'activate_plugins' ) )
		return;

	$url_tec = add_query_arg(
		array( 'tab'       => 'plugin-information',
			'plugin'    => 'the-events-calendar',
			'TB_iframe' => 'true' ), admin_url( 'plugin-install.php' ) );

	$url_woocommerce = add_query_arg(
		array( 'tab'       => 'plugin-information',
			'plugin'    => 'woocommerce',
			'TB_iframe' => 'true' ), admin_url( 'plugin-install.php' ) );

	$title_tec         = __( 'The Events Calendar', 'tribe-wootickets' );
	$title_woocommerce = __( 'WooCommerce', 'tribe-wootickets' );

	echo '<div class="error"><p>';

	if ( tribe_wootickets_is_incompatible_events_core_installed() ) {
		printf( __( 'To begin using WooTickets, please install and activate the latest version of <a href="%s" class="thickbox" title="%s">%s</a>.', 'tribe-wootickets' ), esc_url( $url_tec ), $title_tec, $title_tec );
	} elseif ( is_incompatible_woocommerce_installed() ) {
		printf( __( 'To begin using WooTickets, please install and activate the latest version of <a href="%s" class="thickbox" title="%s">%s</a>.', 'tribe-wootickets' ), esc_url( $url_woocommerce ), $title_woocommerce, $title_woocommerce );
	}

	echo '</p></div>';
}
