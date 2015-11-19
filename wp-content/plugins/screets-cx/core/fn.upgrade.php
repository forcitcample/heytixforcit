<?php
/**
 * SCREETS © 2014
 *
 * Upgrade functions
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */

/**
 * Upgrade the plugin
 *
 * @return void
 */
function cx_upgrade( $license = null ) {
	
	global $wpdb;

	require_once CX_PATH . '/core/fn.setup.php'; // We need some functions inside

	// Check API
	cx_api( @$license, true );

	// Get last version
	$last_version = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'cx_version' LIMIT 1" );

	/**
	 * Base installation / upgrading
	 */

	// Drop old CX tables before 1.1
	$wpdb->query( "DROP TABLE IF EXISTS `" . CX_PX . "chat_lines`;" );
	$wpdb->query( "DROP TABLE IF EXISTS `" . CX_PX . "online`;" );
	$wpdb->query( "DROP TABLE IF EXISTS `" . CX_PX . "conversations`;" );
	$wpdb->query( "DROP TABLE IF EXISTS `" . CX_PX . "blocked_ips`;" );

	// Chat logs table
	$wpdb->query( "CREATE TABLE IF NOT EXISTS `" . CX_PX . "chat_logs` (
		  `msg_id` varchar(30) NOT NULL DEFAULT '',
		  `cnv_id` varchar(30) NOT NULL,
		  `user_id` varchar(30) NOT NULL DEFAULT '',
		  `name` varchar(32) DEFAULT NULL,
		  `gravatar` char(32) DEFAULT NULL,
		  `msg` text NOT NULL,
		  `time` bigint(13) unsigned NOT NULL,
		  UNIQUE KEY `msg_id` (`msg_id`)
		) DEFAULT CHARSET=utf8;" );

	// Conversation table
	$wpdb->query( "CREATE TABLE IF NOT EXISTS `" . CX_PX . "conversations` (
		  `cnv_id` varchar(30) NOT NULL DEFAULT '',
		  `user_id` varchar(30) NOT NULL DEFAULT '',
		  `created_at` bigint(13) unsigned NOT NULL,
		  UNIQUE KEY `cnv_id` (`cnv_id`),
		  KEY `created_at` (`created_at`)
		) DEFAULT CHARSET=utf8;" );

	// Users table			
	$wpdb->query( "CREATE TABLE IF NOT EXISTS `" . CX_PX . "users` (
		  `user_id` varchar(30) NOT NULL DEFAULT '',
		  `type` varchar(12) NOT NULL DEFAULT '',
		  `name` varchar(32) DEFAULT NULL,
		  `ip` int(11) unsigned DEFAULT NULL,
		  `email` varchar(90) DEFAULT NULL,
		  `last_online` bigint(13) unsigned DEFAULT NULL,
		  UNIQUE KEY `user_id` (`user_id`)
		) DEFAULT CHARSET=utf8;" );
		
	// Get options
	$cx_opts = maybe_unserialize( $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = '" . CX_SLUG . "-opts' LIMIT 1" ) );

	//
	// After CX 1.1
	//
	if( !empty( $last_version ) ) {

		// Before 1.3
		if( version_compare( $last_version, '1.3', '<' ) ) {

			// Clean old sessions from DB
			$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_wp_session%'" );

		}

		// CX 1.1.2 and later and before 1.3
		if( version_compare( $last_version, '1.1.2', '>=' ) && version_compare( $last_version, '1.3', '<' ) ) {

			// Update time fields
			$wpdb->query( "ALTER TABLE " . CX_PX . "chat_logs CHANGE `time` `time` BIGINT(13)  UNSIGNED  NOT NULL" );
			$wpdb->query( "ALTER TABLE " . CX_PX . "conversations CHANGE `created_at` `created_at` BIGINT(13)  UNSIGNED  NOT NULL" );
			$wpdb->query( "ALTER TABLE " . CX_PX . "users CHANGE `last_online` `last_online` BIGINT(13)  UNSIGNED  NOT NULL" );

		}

	}


	// Update current version now
	update_option( 'cx_version', CX_VERSION );

}