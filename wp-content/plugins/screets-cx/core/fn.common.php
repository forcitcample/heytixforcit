<?php
/**
 * SCREETS © 2014
 *
 * Initialization functions
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */

/**
 * Toolbar menu
 *
 * @return void
 */
function cx_toolbar( $wp_toolbar ) {
	global $CX;

	if( !defined( 'CX_OP' ) )
		return;

	/** 
	 * Messages
	 */
	// $wp_toolbar->add_node( array(
	// 	'id'     => 'cx_toolbar_msg',
	// 	'title'  => '',
	// 	'parent' => 'top-secondary',
	// 	'href'   => admin_url( 'admin.php?page=chat_x' ),
	// 	'meta'   => array( 'class' => '', 'html' => '<div class="cx-count">0</div>' )
	// ) );

	/** 
	 * Chat status
	 */
	$wp_toolbar->add_node( array(
		'id'     => 'cx_toolbar_status',
		'title'  => 'Chat X',
		'parent' => 'top-secondary',
		'href'   => admin_url('admin.php?page=chat_x'),
		'meta'   => array( 'class' => 'cx-toolbar-connecting', 'html' => '<div class="cx-count">0</div>' )
	) );

	// Chat Console
	$wp_toolbar->add_node( array(
		'id'		=> 'cx_toolbar_console',
		'title'		=> __( 'Chat Console', 'cx' ),
		'href'		=> admin_url('admin.php?page=chat_x'),
		'parent'	=> 'cx_toolbar_status'
	));

	// Chat Options
	if( current_user_can( 'manage_options' ) ) {

		$wp_toolbar->add_node( array(
			'id'		=> 'cx_toolbar_opts',
			'title'		=> __( 'Settings', 'cx' ),
			'href'		=> admin_url('admin.php?page=' . sanitize_key( $CX->meta['Name'] ) ),
			'parent'	=> 'cx_toolbar_status'
		));

	}
	
	// Prepare toolbar for offline mode at first sight
	$wp_toolbar->add_node( array(
		'id'		=> 'cx_toolbar_go_online',
		'title'		=> __( 'Connect', 'cx' ),
		'href'		=> 'javascript:void(0)',
		'parent'	=> 'cx_toolbar_status',
		'meta'   => array( 'class' => 'cx-toolbar-connect' )
	));
	
}

/**
 * Get operator name
 *
 * @access public
 * @return string Operator name of user
 */
function cx_get_operator_name( $user_id = null ) {
	
	if( empty( $user_id) )
		$user_id = get_current_user_id();
	
	// Get operator name
	$op_name = get_user_meta( $user_id, 'cx_op_name', true );
	
	// Op name isn't defined yet, create new one for user
	if( empty( $op_name ) ) {
		
		global $current_user;
		
		// Get currently logged user info
		get_currentuserinfo();
		
		$op_name = $current_user->display_name;
		
		// Update user meta as well (for later usage)
		update_user_meta( $user_id, 'cx_op_name', $op_name );
	}
	
	return $op_name;
}


/**
 * Check if name is available
 *
 * @access public
 * @return bool True if name is available
 */
 
function cx_name_is_available( $name ) {
	global $wpdb;
	
	// Get all operator names
	$op_names = $wpdb->get_col( 'SELECT meta_value FROM ' . $wpdb->usermeta . ' WHERE meta_key = "cx_op_name" AND meta_value != ""');
	
	if( in_array( $name, $op_names ) )
		return false;
	
	// Check if online users have same name
	$check_online_users = $wpdb->get_var(
		$wpdb->prepare(
			'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'chat_online
			 WHERE `name` = %s LIMIT 1',
			$name
		)
	);
	
	if( $check_online_users > 0 )
		return false;
	
	return true;
							  
}

/**
 * Log out specific user or destroy current session
 *
 * @return array
 */
function cx_logout( $user_id = null ) {
	global $wpdb, $CX;

	// Remove specific user from DB
	if( !empty( $user_id ) ) {
		$wpdb->delete( CX_PX . 'online', array( 'user_id' => $user_id ), array( '%d' ) );
	
	/*// Remove current user and destroy session
	} elseif( !empty( $sess_user['online_id'] ) ) {
		$user_id = $sess_user['online_id'];

		// Remove user from DB
		$wpdb->delete( CX_PX . 'online', array( 'user_id' => $user_id ), array( '%d' ) );

		// Destroy session
		cx_destroy_session();

		// We should know user disconnected by clicking logout!
		$sess_user = array( 'user_disconnected' => true );*/
		
	// Clean session of current user (not logged in chat as user)!
	} else {
		cx_destroy_session();

		// We should know user disconnected by clicking logout!
		$sess_user = array( 'user_disconnected' => true );
	}

	// Update user in session
	$CX->session->set( 'user_data', $sess_user );

}

/**
 * Destroy Session
 *
 * @return void
 */
function cx_destroy_session() {
	
	global $CX;

	if( CX_PHP_SESSIONS ) {
		
		$CX->session->set( 'user_data', NULL );	

		session_destroy();

	} else {

		// Destroy session
		wp_session_unset();
		
		// Clean expired sessions from DB
		wp_session_cleanup();

		// Reassign WP Session
		$CX->session = WP_Session::get_instance();
		
	}

}


/**
 * Get current page URL
 *
 * @return string URL
 */
function cx_current_page_url() {
	
	$page_URL = 'http';
	
	if ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' )
		$page_URL .= "s";
		
	$page_URL .= '://';
	
	if ( @$_SERVER['SERVER_PORT'] != '80' )
		$page_URL .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] .$_SERVER['REQUEST_URI'];
	else
		$page_URL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
 
	return $page_URL;

}

/**
 * Print jquery plugin options in recursively
 *
 * @return void
 */
/**
 * Print custom options
 */
function cx_print_custom_opts( $opts, $property = null ) {
	$total_opts = count( $opts );
	
	if( $property )
		echo $property . ": {\n\t\t\t\t";

	$i = 1;
	foreach( $opts as $k => $v ) {
		
		$comma = ( $i < $total_opts ) ? ",\n\t\t\t" : "\n";

		// 
		// Print single line option
		// 
		if( !is_array( $v ) or !empty( $v['_FUNC_'] ) ) {
			
			// It is a callback / function?
			if( is_array( $v) and !empty( $v['_FUNC_'] ) ) {
				$val = $v['_FUNC_'];

			} else {

				// Sanitize value
				$val = ( is_int( $v ) or is_numeric( $v ) ) ? $v : "'$v'";

			}
			
			// Print option
			echo $k . ': ' . $val . $comma;

		// 
		// Print array option
		// 
		} else {
			
			cx_print_custom_opts( $v, $k );

		}
		
		$i++;
	}

	if( $property )
		echo "},\n\t\t\t";

}

/**
 * Get jquery plugin options
 *
 * @return array
 */
function cx_get_jquery_plug_opts() {
	global $CX, $wp_query;
	
	$login_form = array();
	$offline_form = array();
	$after_open = array();
	$new_msg = array();

	// Get options
	$opts = $CX->opts;

	// Find position
	list( $v_align, $h_align ) = explode( '-', $opts['widget_position'] );

	// Find gravity
	switch( $v_align ) {
		case 'top': 
			$gravity = ( $h_align == 'right' ) ? 'ne' : 'nw';
			break;

		case 'bottom': 
			$gravity = ( $h_align == 'right' ) ? 'se' : 'sw';
			break;
	}

	// LOGIN FORM: Name field
	if( $opts['fl_name'] != 'hidden' ) {
		$req = ( $opts['fl_name'] == 'req' ) ? true : false;
		$login_form['name'] = array(
			'title'	=> $opts['f_name_label'],
			'type'	=> 'text',
			'req'	=> $req
		);
	}

	// LOGIN FORM: Email field
	if( $opts['fl_email'] != 'hidden' ) {
		$req = ( $opts['fl_email'] == 'req' ) ? true : false;
		$login_form['email'] = array(
			'title'	=> $opts['f_email_label'],
			'type'	=> 'email',
			'req'	=> $req
		);
	}

	// OFFLINE FORM: Name field
	if( $opts['f_name'] != 'hidden' ) {
		$req = ( $opts['f_name'] == 'req' ) ? true : false;
		$offline_form['name'] = array(
			'title'	=> $opts['f_name_label'],
			'type'	=> 'text',
			'req'	=> $req
		);
	}

	// OFFLINE FORM: Email field
	if( $opts['f_email'] != 'hidden' ) {
		$req = ( $opts['f_email'] == 'req' ) ? true : false;
		$offline_form['email'] = array(
			'title'	=> $opts['f_email_label'],
			'type'	=> 'email',
			'req'	=> $req
		);
	}

	// OFFLINE FORM: Phone field
	if( $opts['f_phone'] != 'hidden' ) {
		$req = ( $opts['f_phone'] == 'req' ) ? true : false;
		$offline_form['phone'] = array(
			'title'	=> $opts['f_phone_label'],
			'type'	=> 'tel',
			'req'	=> $req
		);
	}

	// OFFLINE FORM: Message field
	$offline_form['msg'] = array(
		'title'	=> $opts['f_msg_label'],
		'type'	=> 'textarea',
		'req'	=> true
	);

	// Prepare callbacks
	$after_load = array(
		'_FUNC_' => 'function() {
			
		 }'
	 );

	$new_msg = array(
		'_FUNC_' => 'function() {
		}'
	);

	// Default radius
	$radius = $opts['radius'][0] . $opts['radius'][1];

	// Calculate offset
	$offset = ( !empty( $opts['tab_offset'][0] ) ) ? $opts['tab_offset'][0] . $opts['tab_offset'][1] : 0;

	switch( $opts['base_skin'] ) {

		case 'basic':

			$offset_x = $offset;
			$offset_y = '15px';
			$trim_radius = false; // Don't remove radius from popup

			break;

		case 'fixed':

			$offset_x = $offset;
			$offset_y = 0;

			if( $v_align == 'top' ) {
				$trim_radius = 'h'; // Remove radius from top-side of popup
			} else {
				$trim_radius = 'f'; // Remove radius from bottom-side of popup
			}
			break;

	}

	// Get Application ID and secure token
	$app_id = !empty( $opts['app_url'] ) ? $opts['app_url'] : null;
	$user_info = null;

	// Display chat box?
	$display_chatbox = ( !empty( $opts['display-chatbox-group']['display_chatbox'] ) ) ? 1 : 0;

	// Render chat box?
	$render = true;

	// Check homepage
	if( !$wp_query->is_posts_page && ( is_home() || is_front_page() ) ) {

		if( @$opts['display-home'] == 'show' )
			$render = true;

		elseif (!$display_chatbox || @$opts['display-home'] == 'hide' )
			$render = false;

	// Render if allowed on single
	} elseif( is_single() || is_page() ) {
		
		// Check Woocommerce pages
		if( defined( 'CX_WC_INSTALLED' ) ) {

			if( cx_is_woocommerce() && !empty( $opts['display-chatbox-group']['woocomerce_pages'] ) ) {
				$render = true;
				$_stop_checking = true;
			}

		}

		// Continue checking...
		if( empty( $_stop_checking ) ) {

			global $wp_query;

			// Get post
			$post = $wp_query->post;

			// Display option
			$s_display = get_post_meta( $post->ID, 'cx_display', true ); // null, "show" or "hide"

			if( $s_display == 'show' ) // Force to show up
				$render = true;

			elseif( !$display_chatbox || $s_display == 'hide' )
				$render = false;

		}
	
	} else {

		$render = $display_chatbox;
	}
	
	if( !empty( $CX->user ) ) {

		// Add 'usr-' prefix, because user_id must be string
		$xtra_prefix = ( is_user_logged_in() && !defined( 'CX_OP' ) ) ? 'usr-' : '';

		// Get user prefix
		$user_prefix = ( defined( 'CX_OP' ) && is_admin() ) ? 'op-' : '';


		$user_info = array(
			'id' => $xtra_prefix . $user_prefix . $CX->user->ID,
			'name' => $CX->user->display_name,
			'email' => $CX->user->user_email,
			'gravatar' => ( !empty( $CX->user->user_email ) ) ? md5( $CX->user->user_email ) : null
		);
	}

	/**
	 * Get plugin options
	 */
	return apply_filters( 'cx_plugin_opts', array(
			'app_id'				=> $app_id,
			'render'				=> $render,
			'display_login'			=> 1, //( !empty( $opts['display_login'] ) ) ? 1 : 0,

			'notify_by_email'		=> ( !empty( $opts['ntf-email-group']['new-user'] ) ) ? 1 : 0,

			// 'hide_if_no_op'			=> ( !empty( $opts['display-chatbox-group']['hide_chatbox_when_offline'] ) ) ? 1 : 0,
			'disable_on_mobile'		=> ( !empty( $opts['display-chatbox-group']['disable_on_mobile'] ) ) ? 1 : 0,
			'users_list_id'			=> '',
			// 'show_badge'			=> ( !empty( $opts['display-badge-group']['show_badge'] ) ) ? 1 : 0,
			'btn_view' 				=> array(
				'show_title' => ( !empty( $opts['chat-btn-group']['show_title'] ) ) ? 1 : 0,
				'show_icon' => ( !empty( $opts['chat-btn-group']['show_icon'] ) ) ? 1 : 0,
				'show_arrow' => ( !empty( $opts['chat-btn-group']['show_arrow'] ) ) ? 1 : 0
			),
			'gravity'				=> $gravity,
			'popup_width'			=> $opts['widget_width'],
			'btn_width'				=> $opts['btn_width'],
			'offset_x'				=> $offset_x,
			'offset_y'				=> $offset_y,
			'delay'					=> (int) $opts['delay'],
			'radius'				=> $radius,
			'trim_radius'			=> $trim_radius,
			'anim'					=> $opts['anim'],
			'speed_up'				=> ( !empty ( $opts['anim-group']['hinge'] ) ) ? 1 : 0,
			'reply_pos'				=> $opts['reply_pos'],
			'debug'					=> ( $opts['debug'] == 'on') ? 1 : 0,
			'offline_form'			=> $offline_form,
			'offline_redirect'	 	=> $opts['offline_redirect_url'],
			'guest_prefix'	 		=> $opts['guest_prefix'],
			'login_form'			=> $login_form, 

			'company_avatar' 		=> ( !empty( $opts['default_avatar'] ) ) ? $opts['default_avatar'] : CX_URL . '/assets/img/default-avatar.png',
			'avatar_size' 			=> $opts['avatar_size'],

			'user_info' 			=> $user_info,
			'colors'				=> array(
				'primary' => $opts['primary_color'],
				'link' => $opts['link_color']

			),
			'msg'					=> array(
				'online' => cx_sanitize( $opts['when_online'] ), 
				'offline' => cx_sanitize( $opts['when_offline'] ),
				'prechat_msg' => cx_sanitize( $opts['prechat_msg'], true ),
				'welc_msg' => cx_sanitize( $opts['welc_msg'], true ),
				'waiting' => cx_sanitize( __( 'Waiting', 'cx' ) ),
				'start_chat' => cx_sanitize( __( 'Start Chat', 'cx' ) ),

				'offline_body' => cx_sanitize( $opts['offline_body'], true ),
				'reply_ph' => cx_sanitize( $opts['popup_reply_ph'] ),
				'send_btn' => cx_sanitize( $opts['f_send_btn'] ),
				'no_op' => cx_sanitize( __( 'No operators online', 'cx' ) ),
				'no_msg' => cx_sanitize( __( 'No messages found', 'cx' ) ),
				'sending' => cx_sanitize( __( 'Sending', 'cx' ) ),
				'connecting' => cx_sanitize( __( 'Connecting', 'cx' ) ),
				'writing' => cx_sanitize( __( '%s is writing', 'cx' ) ),
				'please_wait' => cx_sanitize( __( 'Please wait', 'cx' ) ),
				'chat_online' => cx_sanitize( __( 'Chat Online', 'cx' ) ),
				'chat_offline' => cx_sanitize( __( 'Chat Offline', 'cx' ) ),
				'optional' => cx_sanitize( __( 'Optional', 'cx' ) ),
				'your_msg' => cx_sanitize( __( 'Your message', 'cx' ) ),
				'end_chat' => cx_sanitize( __( 'End chat', 'cx' ) ),
				'conn_err' => cx_sanitize( __( 'Connecting error!', 'cx' ) ),
				'field_empty' => cx_sanitize( __( 'Please fill out all required fields', 'cx' ) ),
				'invalid_email' => cx_sanitize( __( 'E-mail is invalid', 'cx' ) ),
				'you' => cx_sanitize( __( 'You', 'cx' ) ),
				'online_btn' => cx_sanitize( __( 'Online', 'cx' ) ),
				'offline_btn' => cx_sanitize( __( 'Offline', 'cx' ) ),
				'op_not_allowed' => cx_sanitize( __( 'Operators do not chat from here, only visitors. If you want to test chat box, you will want to use two different browsers or computers', 'cx' ) ),

				'months' => array(
					__( 'January', 'cx' ),
					__( 'February', 'cx' ),
					__( 'March', 'cx' ),
					__( 'April', 'cx' ),
					_x( 'May', 'Plural form of May', 'cx' ),
					__( 'June', 'cx' ),
					__( 'July', 'cx' ),
					__( 'August', 'cx' ),
					__( 'September', 'cx' ),
					__( 'October', 'cx' ),
					__( 'November', 'cx' ),
					__( 'December', 'cx' )
				),
				'months_short' => array(
					__( 'Jan', 'cx' ),
					__( 'Feb', 'cx' ),
					__( 'Mar', 'cx' ),
					__( 'Apr', 'cx' ),
					_x( 'May', 'Short version of May', 'cx' ),
					__( 'Jun', 'cx' ),
					__( 'Jul', 'cx' ),
					__( 'Aug', 'cx' ),
					__( 'Sep', 'cx' ),
					__( 'Oct', 'cx' ),
					__( 'Nov', 'cx' ),
					__( 'Dec', 'cx' )
				),
				'time' => array(
					'suffix' => cx_sanitize( __( 'ago', 'cx' ) ),
					'seconds' => cx_sanitize( __( 'less than a minute', 'cx' ) ),
					'minute' => cx_sanitize( __( 'about a minute', 'cx' ) ),
					'minutes' => cx_sanitize( __( '%d minutes', 'cx' ) ),
					'hour' => cx_sanitize( __( 'about an hour', 'cx' ) ),
					'hours' => cx_sanitize( __( 'about %d hours', 'cx' ) ),
					'day' => cx_sanitize( __( 'a day', 'cx' ) ),
					'days' => cx_sanitize( __( '%d days', 'cx' ) ),
					'month' => cx_sanitize( __( 'about a month', 'cx' ) ),
					'months' => cx_sanitize( __( '%d months', 'cx' ) ),
					'year' => cx_sanitize( __( 'about a year', 'cx' ) ),
					'years' => cx_sanitize( __( '%d years', 'cx'  ) )
				)
			),
			'after_load'		 => $after_load,
			'new_msg' 			=> $new_msg
		)
	);
}

/**
 * Decode string
 *
 * @return void
 */
function cx_ ( $str ) {
	return base64_decode( $str );
}

/**
 * Sanitize string
 *
 * @return string
 */
function cx_sanitize( $str, $html = false ) {

	if( $html )
		return html_entity_decode( addslashes( $str ) );
	else
		return addslashes( $str );
}

/**
 * Get random color
 *
 * @return void
 */
function cx_rand_color() {

	$colors = array(
		'#aef386',
		'#73bffc',
		'#fc7985',
		'#c77d98',
		'#eb4932',
		'#c9da4d',
		'#F79B57',
		'#296DDE',
		'#1EA061',
		'#9fde75',
		'#9B3950'
	);

	$c = array_rand( $colors );

	return $colors[$c];
}

/**
 * Returns true if on a page which uses WooCommerce templates 
 * ( cart and checkout are standard pages with shortcodes and which are also included )
 *
 * @access public
 * @return bool
 */
function cx_is_woocommerce () {
	if( function_exists ( "is_woocommerce" ) && is_woocommerce() ) {
			return true;
	}

	$woocommerce_keys = array ( "woocommerce_shop_page_id" ,
									"woocommerce_terms_page_id" ,
									"woocommerce_cart_page_id" ,
									"woocommerce_checkout_page_id" ,
									"woocommerce_pay_page_id" ,
									"woocommerce_thanks_page_id" ,
									"woocommerce_myaccount_page_id" ,
									"woocommerce_edit_address_page_id" ,
									"woocommerce_view_order_page_id" ,
									"woocommerce_change_password_page_id" ,
									"woocommerce_logout_page_id" ,
									"woocommerce_lost_password_page_id" );

	foreach ( $woocommerce_keys as $wc_page_id ) {
		if ( get_the_ID () == get_option ( $wc_page_id , 0 ) ) {
				return true ;
		}
	}

	return false;
}

?>
