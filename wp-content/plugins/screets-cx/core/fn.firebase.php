<?php
/**
 * SCREETS © 2014
 *
 * Firebase functions
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */

/**
 * Get total size of realtime data
 *
 * @return int bytes
 */
function cx_get_total_size_data() {

	global $CX;

	if( empty( $CX->opts['app_url'] ) || empty( $CX->opts['app_token'] ) )
		return 0;

	require_once CX_PATH . '/core/lib/firebaseLib.php';

	// Default path
	$path = 'https://' . $CX->opts['app_url'] . '.firebaseio.com/';

	// Connect to auth rules
	$firebase = new Firebase( $path, $CX->opts['app_token'] );

	// Retrive messages
	return strlen(  $firebase->get( '/' ) );

}

/**
 * Clean up firebase database
 *
 * @return void
 */
function cx_cleanup_firebase_db() {

	global $CX, $wpdb;

	require_once CX_PATH . '/core/lib/firebaseLib.php';

	// Default path
	$path = 'https://' . $CX->opts['app_url'] . '.firebaseio.com/';

	// Connect to auth rules
	$firebase = new Firebase( $path, $CX->opts['app_token'] );

	// Retrive messages
	$msgs = json_decode( $firebase->get( '/messages' ) );

	if( $msgs ) {
		foreach( $msgs as $msg_id => $msg ) {

			// Create user if not exists
			if( $wpdb->get_var( 'SELECT COUNT(*) FROM ' . CX_PX . "users WHERE user_id = '$msg->user_id' LIMIT 1" ) == 0 ) {

				// Get user from firebase
				$_user = json_decode( $firebase->get( '/users/' . $msg->user_id ) );

				// Create user
				$wpdb->replace( CX_PX . 'users', array(
					'user_id' => $msg->user_id,
					'type' => $_user->type,
					'name' => $_user->name,
					'ip' => ip2long( $_user->ip ),
					'email' => $_user->email
				));

			}

			// Create new conversation if not exists
			if( $wpdb->get_var( 'SELECT COUNT(*) FROM ' . CX_PX . "conversations WHERE cnv_id = '$msg->cnv_id' LIMIT 1" ) == 0 ) {

				// Get conversation from firebase
				$_cnv = json_decode( $firebase->get( '/conversations/' . $msg->cnv_id ) );

				// Create conversation
				$wpdb->replace( CX_PX . 'conversations', array(
					'cnv_id' => $msg->cnv_id,
					'user_id' => $_cnv->user_id,
					'created_at' => $_cnv->created_at
				));

			}

			// Add message into DB
			$wpdb->insert( CX_PX . 'chat_logs', array(
				'msg_id' => $msg_id,
				'cnv_id' => $msg->cnv_id,
				'user_id' => $msg->user_id,
				'name' => $msg->name,
				'gravatar' => $msg->gravatar,
				'msg' => $msg->msg,
				'time' => $msg->time
			));
		}

	}

	// Clean all data in Firabase
	$firebase->delete( '/users' );
	$firebase->delete( '/messages' );
	$firebase->delete( '/conversations' );

}

/**
 * Update security rules
 *
 * @return void
 */
function cx_update_security_rules() {

	global $CX;

	require_once CX_PATH . '/core/lib/firebaseLib.php';

	// Get security rules
	$rules_json = file_get_contents( CX_PATH . '/rules.json' );

	// Default path
	$path = 'https://' . $CX->opts['app_url'] . '.firebaseio.com/';

	// Connect to auth rules
	$firebase = new Firebase( $path, $CX->opts['app_token'] );

	// Update rules
	return json_decode( $firebase->set( '/.settings/rules', $rules_json ) );

}