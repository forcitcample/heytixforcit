<?php
 /**
  * Plugin Name: Follow-Up Emails
  * Plugin URI: http://www.woothemes.com/products/follow-up-emails/
  * Description: Automate your marketing to drive customer engagement for WooCommerce Stores and Sensei.
  * Version: 4.1.7
  * Author: 75nineteen Media LLC
  * Author URI: http://www.75nineteen.com/woocommerce/follow-up-email-autoresponder/

  * Copyright 2015 75nineteen Media LLC.  (email : scott@75nineteen.com)
  * 
  * This program is free software: you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation, either version 3 of the License, or
  * (at your option) any later version.
  * 
  * This program is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  * GNU General Public License for more details.
  * 
  * You should have received a copy of the GNU General Public License
  * along with this program.  If not, see <http://www.gnu.org/licenses/>.
  */

/** Path and URL constants **/
define( 'FUE_VERSION', '4.1.7' );
define( 'FUE_KEY', 'aHR0cDovLzc1bmluZXRlZW4uY29tL2Z1ZS5waH' );
define( 'FUE_FILE', __FILE__ );
define( 'FUE_URL', plugins_url('', __FILE__) );
define( 'FUE_DIR', plugin_dir_path( __FILE__ ) );
define( 'FUE_INC_DIR', FUE_DIR .'includes' );
define( 'FUE_INC_URL', FUE_URL .'/includes' );
define( 'FUE_ADDONS_DIR', FUE_DIR .'/addons' );
define( 'FUE_ADDONS_URL', FUE_URL .'/addons' );
define( 'FUE_TEMPLATES_DIR', FUE_DIR .'templates' );
define( 'FUE_TEMPLATES_URL', FUE_URL .'/templates' );

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
    require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '05ece68fe94558e65278fe54d9ec84d2', '18686' );


load_plugin_textdomain( 'follow_up_emails', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

global $fue, $wpdb;
require_once FUE_INC_DIR .'/class-follow-up-emails.php';
$fue = new Follow_Up_Emails( $wpdb );
