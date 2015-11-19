<?php
/**
 * SCREETS © 2014
 *
 * Installation functions
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */




/**
 * Check if CX installed correctly
 *
 * @return void
 */
function cx_check_setup() {
	global $CX, $wpdb;

	$opts = $CX->opts;


	// Get last version
	$last_version = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'cx_version' LIMIT 1" );

	// First check if the plugin upgraded successfully!
	if( $last_version != CX_VERSION ) {

		require_once CX_PATH . '/core/fn.upgrade.php';

		// Run upgrade!
		cx_upgrade( null );

	}

	$CX->admin_notices['tabs'] = array(
		'general' => 0,
		'appearance' => 0,
		'forms' => 0,
		'offline' => 0,
		'users' => 0,
		'advanced' => 0,
		'help' => 0
	);

	// Hide wpdb errors temporarly
	$wpdb->hide_errors();

	// Check if databases installed correctly
	$db_chat_logs = $wpdb->query( 'SELECT 1 FROM `' . CX_PX . 'chat_logs`' );
	$db_conversations = $wpdb->query( 'SELECT 1 FROM `' . CX_PX . 'conversations`' );
	$db_users = $wpdb->query( 'SELECT 1 FROM `' . CX_PX . 'users`' );

	// Continue to show wpdb errors
	$wpdb->show_errors();

	// Now check CX databases
	if( $db_chat_logs === false || $db_conversations === false || $db_users === false ) {

		$CX->admin_notices['tabs']['advanced'] += 1;
		$CX->admin_notices['no_db_tables'] = true;

	}

	// Firebase application URL is empty?
	if( empty( $opts['app_url'] ) ) {

		$CX->admin_notices['tabs']['advanced'] += 1;
		$CX->admin_notices['fields']['app_url'] = __( 'This field is required', 'cx' );

	}

	// Firebase application URL is empty?
	if( empty( $opts['app_token'] ) ) {

		$CX->admin_notices['tabs']['advanced'] += 1;
		$CX->admin_notices['fields']['app_token'] = __( 'This field is required', 'cx' );

	}

	// Offline email is empty
	if( empty( $opts['admin_emails'] ) ) {
		
		$CX->admin_notices['tabs']['offline'] += 1;
		$CX->admin_notices['fields']['admin_emails'] = __( 'This field is required', 'cx' );

	}


	// Site name is empty
	if( empty( $opts['site_name'] ) ) {
		
		$CX->admin_notices['tabs']['offline'] += 1;
		$CX->admin_notices['fields']['site_name'] = __( 'This field is required', 'cx' );

	}


	// Site URL is empty
	if( empty( $opts['site_url'] ) ) {
		
		$CX->admin_notices['tabs']['offline'] += 1;
		$CX->admin_notices['fields']['site_url'] = __( 'This field is required', 'cx' );

	}


	// Email footer should be reviewed
	if( strpos( @$opts['contact_footer'], 'yourdomain' ) !== false ) {
		
		$CX->admin_notices['tabs']['offline'] += 1;
		$CX->admin_notices['fields']['contact_footer'] = __( 'Please review this field', 'cx' );

	}


	// Guest prefix
	if( @$opts['guest_prefix'] == '0' ) {
		
		$CX->admin_notices['tabs']['users'] += 1;
		$CX->admin_notices['fields']['guest_prefix'] = __( 'Please review this field', 'cx' );

	}

	//
	// Update security rules for Firebase
	//
	if( !empty( $opts['app_url'] ) && !empty( $opts['app_token'] ) ) {
		
		// Update security rules if necessary!
		$last_update = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'cx_security_last_update' LIMIT 1" );
		
		// If last update version is lower than current version,
		// update security rules
		if(  empty( $last_update ) || version_compare( CX_VERSION, $last_update, '>' ) ) {

			$r = cx_update_security_rules();

			if( !empty( $r->status ) ) {
				if( $r->status == 'ok' )
					update_option( 'cx_security_last_update', CX_VERSION );
			} else
				$CX->admin_notices['tabs']['advanced'] += 1;

		}


	}


}