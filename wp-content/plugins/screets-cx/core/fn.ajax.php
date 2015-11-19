<?php
/**
 * SCREETS © 2014
 *
 * AJAX functions
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */


/**
 * Ajax Callback
 *
 * @access public
 * @return void
 */
function cx_ajax_callback() {
	
	// Response var
	$r = array();
	
	try {

		// Handling the supported actions:
		switch( $_GET['mode'] ) {
			
			// Save transcript
			case 'save_transcript': $r = cx_save_transcript( $_POST ); break;
			
			// Notify visitor
			case 'notify': $r = cx_ajax_notify_op( $_POST ); break;

			// Send offline form
			case 'offline_form': $r = cx_ajax_offline_form( $_REQUEST ); break;

			case 'get_token': $r = cx_ajax_get_token(); break;
			case 'create_db': $r = cx_ajax_create_db(); break;
			case 'update_security': $r = cx_ajax_update_security(); break;
			case 'check_sessions': $r = cx_ajax_check_sessions(); break;
			case 'clean_sessions': $r = cx_ajax_clean_sessions(); break;
			case 'clean_data': $r = cx_ajax_clean_data(); break;

			default:
				throw new Exception( 'Wrong action: ' . @$_REQUEST['mode'] );
		}
	
	} catch ( Exception $e ) {
		
		$r['err_code'] = $e->getCode();
		$r['error'] = $e->getMessage();
		
	}

	// Response output
	header( "Content-Type: application/json" );

	echo json_encode( $r );

	exit;
	
}


/**
 * Get token
 *
 * @return array
 */
function cx_ajax_get_token() {
	global $CX;

	$token = $CX->auth();

	return array( 'token' => $token );
}



/**
 * Clean realtime data
 *
 * @return array
 */
function cx_ajax_clean_data() {

	cx_cleanup_firebase_db();

	return array( 'success' => 1 );
}


/**
 * Notify operators
 *
 * @return array
 */
function cx_ajax_notify_op( $visitor ) {
	
	global $CX;

	// Get administrators
	$admins = get_users( array( 
		'role' => 'administrator',
		'fields' => array( 'user_email' )
	));

	// Get operators 
	$OPs = get_users( array( 
		'role' => 'cx_op', 
		'fields' => array( 'user_email' )
	));

	if( !empty( $OPs ) )
		$all_ops = array_merge( (array) $OPs, (array) $admins );
	else
		$all_ops = $admins;


	// Prepare email
	$title = '[' . $CX->opts['site_name'] . '] ' . __( 'New visitor is online ', 'cx' ); 
	$msg = '<p><b>' . sprintf( __( '%s is online', 'cx' ), "\"$visitor[name]\"" ) . '.</b> ' . __( 'Please check chat console', 'cx' ) . ': </p><a href="' . admin_url( 'admin.php?page=chat_x' ) .'">' . admin_url( 'admin.php?page=chat_x' ) .'</a>';

	$headers = array();
	$headers[] = 'MIME-Version: 1.0';
	$headers[] = 'content-type: text/html';
	$headers[] = 'charset=utf-8';

	// Send email to operators
	foreach( $all_ops as $op ) {
		wp_mail( $op->user_email, $title, $msg, $headers );
	}


	return array( 'status' => 1, 'ops' => $all_ops );

}

/**
 * Create visitor if possible
 *
 * @return array
 */
/*function cx_ajax_create_visitor() {
	global $CX, $wpdb;

	if( !is_admin() ) {

		$visitor_id = $CX->session->get( 'visitor_id' );

		// Create new visitor
		if( empty( $visitor_id ) ) {
			
			// Total visitors
			$total_visitors = $wpdb->get_var( 'SELECT count(*) FROM ' . CX_PX . 'visitors' );

			// Insert current visitor to DB if possible
			if( $total_visitors <= 5 ) {

				$visitor_id = $wpdb->insert( CX_PX . 'visitors', array(
						'ip' => ip2long( cx_ip_address() ),
						'active_page' => cx_current_page_url(),
						'last_seen' => time()
					), 
					array( '%d', '%s', '%d' )
				);

				$CX->session->set( 'visitor_id', $visitor_id );

			}

		// Update visitor information
		} else {

			$visitor_updated = $wpdb->update( CX_PX . 'visitors', array( 
				'active_page' => $CX->current_page,
				'last_seen' => time()
			), array( 'visitor_id' => $visitor_id ) );

			// If visitor not exists in DB anymore, remove from session too
			if( !$visitor_updated ) {
				$CX->session->set( 'visitor_id', null );
			}
		}


	}

	return array( 'visitor_id' => 0 );
}*/

/**
 * Check sessions
 *
 * @return array
 */
function cx_ajax_check_sessions() {
	global $CX;


	if( $CX->session->get( 'CX_opt_test' ) )
		return array( 'success' => 1 );
	else
		return array( 'success' => 0 );
}


/**
 * Clean sessions
 *
 * @return array
 */
function cx_ajax_clean_sessions() {
	global $wpdb;

	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_wp_session%'" );

	return array( 'redirect' => admin_url( 'admin.php?page=' . CX_SLUG . '&msg=10' ) );

}


/**
 * Update security
 *
 * @return array
 */
function cx_ajax_update_security() {

	cx_update_security_rules();

	if( !empty( $r->status ) ) {
		if( $r->status == 'ok' ) {
			
			update_option( 'cx_security_last_update', CX_VERSION );
			
			return array( 'redirect' => admin_url( 'admin.php?page=' . CX_SLUG ) );
		}
	}

	// Security isn't safe!
	delete_option( 'cx_security_last_update' );

	return array( 'redirect' => admin_url( 'admin.php?page=' . CX_SLUG ) );
	
}


/**
 * Create DB
 *
 * @return array
 */
function cx_ajax_create_db() {

	require_once CX_PATH . '/core/fn.upgrade.php';

	// Upgrade CX
	cx_upgrade( null );

	return array( 'redirect' => admin_url( 'admin.php?page=' . CX_SLUG ) );
	
}

/**
 * Save chat transcripts
 *
 * @return array
 */
function cx_save_transcript( $data ) {
	global $wpdb;

	// Create user if not exists
	$user_data = array(
		'user_id' => $data['id'],
		'type' => $data['type'],
		'name' => @$data['name'],
		'ip' => sprintf( '%u', ip2long( $data['ip'] ) ), // Support 32bit systems as well not to show up negative val.
		'email' => @$data['email'],
		'last_online' => @$data['last_online'] || 0
	);

	$wpdb->replace( CX_PX . 'users', $user_data, array( '%s', '%s', '%s', '%d', '%s' ) );


	// Prepare conversation data
	$cnv_data = array(
		'cnv_id' => $data['cnv_id'],
		'user_id' => $data['id'],
		'created_at' => $data['cnv_time']
	);

	// Create conversation if not exists
	$wpdb->replace( CX_PX . 'conversations', $cnv_data, array( '%s', '%s', '%d' ) );

	// Insert message into DB
	if( !empty( $data['msgs'] ) ) {

		foreach ( $data['msgs'] as $msg_id => $msg ) {
			
			// Prepare data
			$msg_data = array(
				'msg_id' => $msg_id,
				'cnv_id' => $msg['cnv_id'],
				'user_id' => $msg['user_id'],
				'name' => $msg['name'],
				'gravatar' => $msg['gravatar'],
				'msg' => $msg['msg'],
				'time' => $msg['time']
			);

			// Insert message
			$wpdb->replace( CX_PX . 'chat_logs', $msg_data, array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' ) );
		
		}

	}

	return array( 'msg' => __( 'Successfully saved!', 'cx' ) );
}

/**
 * Send offline form
 *
 * @return array
 */
function cx_ajax_offline_form( $data ) {
	global $CX;
	
	$r = array(); // Response

	// Get options
	$opts = $CX->opts;

	// Validate name field
	if( $opts['f_name'] == 'req' ) {

		if( empty( $data['name'] ) )
			throw new Exception( __( 'Please fill out all required fields', 'cx' ), 10 );
	}

	// Validate email field
	if( $opts['f_email'] == 'req' ) {

		if( empty( $data['email'] ) )
			throw new Exception( __( 'Please fill out all required fields', 'cx' ), 20 );

		if( !is_email( $data['email'] ) )
			throw new Exception( __( 'E-mail is invalid', 'cx' ), 30 );

	}

	// Validate phone field
	if( $opts['f_phone'] == 'req' ) {
		
		if( empty( $data['phone'] ) )
			throw new Exception( __( 'Please fill out all required fields', 'cx' ), 40 );

	}

	// Validate message field
	if( empty( $data['msg'] ) )
		throw new Exception( __( 'Please fill out all required fields', 'cx' ), 50 );

	// Offline emails
	if( empty( $opts['admin_emails'] ) ) {
		
		// Warn admin
		if( defined( 'CX_OP' ) ) {
			throw new Exception( __( 'Admin', 'cx' ) . ': ' .__( 'Offline messages in chat options are not set correctly!', 'cx' ), 1360 );
		
		// Warn visitor about the same issue in different way
		} else {
			throw new Exception( __( 'Something went wrong. Please try again', 'cx' ), 60 );
		}

	} else {
		$emails = explode( ',' , $opts['admin_emails'] );

		// Include admin emails to receivers
		$to = $opts['admin_emails'];

		// First email is used for site email
		$site_email = array_shift( $emails );

	}
		
	// Send offline message now
	if( !cx_send_offline_msg( $to, $site_email, $data ) )
		throw new Exception( __( 'Something went wrong. Please try again', 'cx' ), 70 );

	// Successfully sent!
	$r['msg'] = __( 'Successfully sent! Thank you', 'cx' );

	return $r;

}