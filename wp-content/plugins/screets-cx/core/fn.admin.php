<?php
/**
 * SCREETS © 2014
 *
 * Administration functions
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */

global $wp_version;

add_action( 'admin_menu', 'cx_admin_menu', 9 );
add_action( 'admin_head', 'cx_admin_head' );
add_filter( 'manage_cx_visitor_posts_columns', 'cx_visitors_custom_cols', 10 );  
add_action( 'manage_cx_visitor_posts_custom_column', 'cx_visitors_cols_content', 10, 2 );
add_filter( 'manage_cx_offline_msg_posts_columns', 'cx_offline_msg_custom_cols', 10 );  
add_action( 'manage_cx_offline_msg_posts_custom_column', 'cx_offline_msg_cols_content', 10, 2 );
add_action( 'add_meta_boxes', 'cx_add_meta_boxes' );
add_action( 'save_post', 'cx_save_post', 0 );
add_filter( 'post_row_actions','cx_row_actions', 10, 2 );

// Show new style admin icon
if( ( version_compare( $wp_version, '3.8', '>=' ) ) )
	add_action( 'admin_head', 'cx_admin_menu_ico' );

/**
 * Setup the Admin menu in WordPress
 *
 * @return void
 */
function cx_admin_menu() {
	global $wp_version, $CX;

	// Add old style custom icon for before WP 3.8
	$old_ico = ( version_compare( $wp_version, '3.8', '<' ) ) ? CX_URL . '/assets/img/cx-ico-16.png' : '';

	/**
	 * Menu for Admins
	 */
	if( current_user_can( 'manage_options' ) ) {
		add_menu_page(
			$CX->meta['Name'], 
			'Chat X', 
			'manage_options', 
			'chat_x', 
			'cx_console_template', 
			$old_ico,
			'50.9874'
		);
		

	/**
	 * Menu for Operators
	 */
	} else if( current_user_can( 'answer_visitors' ) ) {
		add_menu_page(
			$CX->meta['Name'], 
			'Chat X',
			'cx_op',
			'chat_x', 
			'cx_console_template', 
			$old_ico,
			'50.9874'
		);
	}

	/**
	 * Add submenus
	 */

	// Chat Logs
	add_submenu_page(
		'chat_x',
		__( 'Chat Logs', 'cx' ), 
		__( 'Chat Logs', 'cx' ), 
		'manage_options',
		'cx_chat_logs', // Menu slug
		'cx_render_chat_logs'

	);

	// Offline Messages
	add_submenu_page(
		'chat_x',
		__( 'Offline messages', 'cx' ), 
		__( 'Offline messages', 'cx' ), 
		'manage_options',
		'edit.php?post_type=cx_offline_msg'
	);

	/*add_submenu_page(
		'chat_x',
		__( 'Predefined messages', 'cx' ), 
		__( 'Predefined messages', 'cx' ), 
		'manage_options',
		'edit.php?post_type=cx_predefined_msg'
	);*/

	// Remove publish box some post types
	remove_meta_box( 'submitdiv', 'cx_offline_msg', 'side' );

}

/**
 * Admin header
 *
 * @return void
*/
function cx_admin_head() { ?>
	<style type="text/css">
		
		<?php 

		$post_type = ( !empty( $_GET['post_type'] ) ) ? $_GET['post_type'] : get_post_type();

		// Edit some custom post types
		switch( $post_type ) {
			case 'cx_visitor':
			case 'cx_offline_msg': ?>
			
			.subsubsub .publish,
			.add-new-h2 { display: none; }
		
		<?php
			break;
		}

		?>
	</style>
<?php }

/**
 * Add custom icon
 *
 * @return void
*/
function cx_admin_menu_ico() { ?>
	<style>
	#adminmenu .toplevel_page_chat_x div.wp-menu-image:before {
	  content: "\f130";
	}
	</style>
<?php }

/**
 * Create / Update operator role
 *
 * @return void
*/
function cx_update_op_role( $role, $additional = false ) {
	
	if( !$additional ) {

		// First clean rol
		remove_role( 'cx_op' );

		// Create operator role
		$op_role = add_role( 'cx_op', __( 'CX Operator', 'cx' ) );

		// Add common operator capability
		$op_role->add_cap( 'answer_visitors' );
		
	} else
		$op_role = get_role( 'cx_op' );
	
	switch( $role ) {

		/**
		 * N/A
		 */
		case 'none':
			
			$op_role->add_cap( 'read' );

			break;
		
		/**
		 * Other roles
		 */
		default:

			// Get editor role
			$r = get_role( $role );

			// Add editor caps to chat operator
			foreach( $r->capabilities as $custom_role => $v ) {
				$op_role->add_cap( $custom_role );
			}

			
	}
}


/**
 * Render Chat Console Template
 *
 * @access public
 * @return void
*/
function cx_console_template() {
	
	require CX_PATH . '/core/admin/chat_console.php';
	
}


/**
 * Add custom columns to visitors list
 *
 * @access public
 * @return array
 */
function cx_visitors_custom_cols( $defaults ) {

	// Remove columns
	unset( $defaults['title'] );
	unset( $defaults['date'] );

	// Add columns
	$defaults['_title'] = __( 'Title', 'cx' );
	$defaults['email'] = __( 'Email', 'cx' );
	$defaults['phone'] = __( 'Phone', 'cx' );
	$defaults['details'] = __( 'Details', 'cx' );
	$defaults['last_login'] = __( 'Last Login', 'cx' );


	return $defaults;

}

/**
 * Custom columns content of visitors
 *
 * @access public
 * @return void
 */
function cx_visitors_cols_content( $col, $post_ID ) {

	switch ( $col ) {

		case '_title':

			echo '<strong><a class="row-title" href="' . get_permalink() . '">' . get_the_title() . '</a></strong>';

			echo "<div class='row-actions'><span class='trash'><a class='submitdelete' title='" . esc_attr( __( 'Delete this item permanently' ) ) . "' href='" . get_delete_post_link( $post_ID, '', true ) . "'>" . __( 'Delete Permanently', 'cx' ) . "</a></span></div>";

			break;

		case 'email':
			$email = get_post_meta( $post_ID, 'Email', 1 );
			echo '<a href="mailto:' . $email . '">' . $email . '</a>';
			break;


		case 'phone':
			echo get_post_meta( $post_ID, 'Phone', 1 );
			break;

			
		case 'details':
			echo get_post_meta( $post_ID, 'OS', 1 ) . ', ';
			echo get_post_meta( $post_ID, 'Browser', 1 ) . '<br>';

			echo '<strong>IP:</strong> ' . get_post_meta( $post_ID, 'IP Address', 1 );
			break;
		
		case 'last_login':
			echo get_the_modified_date() . ' ' . get_the_modified_time();
			break;
	} 

}

/**
 * Add custom columns to offline messages list
 *
 * @access public
 * @return array
 */
function cx_offline_msg_custom_cols( $defaults ) {

	unset( $defaults['date'] );

	// Add columns
	$defaults['sender'] = __( 'Sender', 'cx' );
	$defaults['receiver'] = __( 'Receiver', 'cx' );
	$defaults['_date'] = __( 'Date', 'cx' );

	return $defaults;
}


/**
 * Custom columns content of offline messages
 *
 * @access public
 * @return void
 */
function cx_offline_msg_cols_content( $col, $post_ID ) {

	switch ( $col ) {
		case '_date':
			echo human_time_diff( get_the_time( 'U' ) , current_time( 'timestamp' ) );
			break;

		case 'sender':
			echo get_post_meta( $post_ID, 'email', 1 );
			break;

		case 'receiver':
			echo get_post_meta( $post_ID, 'to', 1 );
			break;

	} 

}

/**
 * Add meta boxes
 *
 * @return void
 */
function cx_add_meta_boxes() {
	
	//
	// Chat options meta box
	// 
	$screens = array( 'post', 'page' );

	// Get all custom post types (only public post types)
	$custom_post_types = get_post_types( array( '_builtin' => false, 'public' => true ) );

	// Merge all post types
	$screens = array_merge( $screens, $custom_post_types );
	
	foreach ($screens as $screen) {
		add_meta_box(
			'cx_opts',
			__( 'Chat Options', 'cx' ),
			'cx_render_opts_meta',
			$screen,
			'side'
		);
	}

	// 
	// Visitors
	// 
	add_meta_box(
		'cx_visitor',
		__( 'Visitor Details', 'cx' ),
		'cx_render_visitor',
		'cx_visitor',
		'normal',
		'high'
	);

	// 
	// Offline messages
	// 
	add_meta_box(
		'cx_offl_msg', 
		__( 'Offline Message', 'cx' ),
		'cx_render_offline_msg',
		'cx_offline_msg',
		'normal',
		'high'
	);

	add_meta_box(
		'cx_offl_msg_details', 
		__( 'Message Details', 'cx' ),
		'cx_render_offline_msg_details',
		'cx_offline_msg',
		'side'
	);
	
	add_meta_box(
		'cx_offl_msg_usr', 
		__( 'Sender', 'cx' ),
		'cx_render_offline_msg_sender',
		'cx_offline_msg',
		'side'
	);

}

/**
 * Render visitors metabox
 *
 * @return void
 */
function cx_render_visitor( $post ) {

	// Use nonce for verification
	wp_nonce_field( plugin_basename( __FILE__ ), 'cx_nonce' ); 

	// Get user details
	$title = get_post_meta( $post->ID, 'Title', 1 );
	$email = get_post_meta( $post->ID, 'Email', 1 );
	$ip_addr = get_post_meta( $post->ID, 'IP Address', 1 );

	?>
	
	<p>
		<label><?php _e( 'Full name', 'cx' ); ?>:</label><br>
		<input type="text" name="cx_title" id="cx_visitor_title" value="<?php echo $title; ?>">
	</p>

	<p>
		<label><?php _e( 'E-mail', 'cx' ); ?>:</label><br>
		<input type="text" name="cx_email" id="" value="<?php echo $email; ?>">
	</p>


	<script>
		(function ($) { $(document).ready(function () {
			
			// Hide publish box details
			$('#misc-publishing-actions').hide();

		}); } (window.jQuery || window.Zepto));
	</script>	
	
<?php
}

/**
 * Render offline messages metabox
 *
 * @return void
 */
function cx_render_offline_msg( $post ) {

	

	?>

	<div class="cx-offline-msg-wrap">
		<div class="cx-offline-msg-content">
			<?php echo wpautop( $post->post_content ); ?>
		</div>

	</div>
<?php

	
}

/**
 * Render offline messages sender metabox
 *
 * @return void
 */
function cx_render_offline_msg_sender( $post ) { 

	// Specific user info
	$name = get_post_meta( $post->ID, 'name', 1 );
	$email = get_post_meta( $post->ID, 'email', 1 );
	$phone = get_post_meta( $post->ID, 'phone', 1 );
	

	?>

	<ul>
		<?php 
		if( !empty( $name ) )
			echo '<li><strong>' . __('Name', 'cx' ) . '</strong>: ' . $name . '</li>';

		if( !empty( $email ) )
			echo '<li><strong>' . __('Email', 'cx' ) . '</strong>: <a href="mailto:' . $email . '">' . $email . '</a></li>';

		if( !empty( $phone ) )
			echo '<li><strong>' . __('Phone', 'cx' ) . '</strong>: ' . $phone . '</li>';
		?>

		<li><strong><?php _e( 'IP Address', 'cx' ); ?>:</strong> <?php echo get_post_meta( $post->ID, 'ip_addr', 1 ); ?></li>
		<li><strong><?php _e( 'Browser', 'cx' ); ?>:</strong> <?php echo get_post_meta( $post->ID, 'browser', 1 ) . ' ' . get_post_meta( $post->ID, 'version', 1 ); ?></li>
		<li><strong><?php _e( 'OS', 'cx' ); ?>:</strong> <?php echo get_post_meta( $post->ID, 'os', 1 ); ?></li>
		
		

	</ul>
	
<?php 
	
}

/**
 * Render offline messages details metabox
 *
 * @return void
 */
function cx_render_offline_msg_details( $post ) {

	// Email sent to offline emails ?
	$status = get_post_meta( $post->ID, 'status', 1 );
	$receiver = get_post_meta( $post->ID, 'to', 1 );

	?>

	<ul>
		<li><strong><?php _e( 'Date', 'cx' ); ?>:</strong> <?php echo get_the_date() . ' ' . get_the_time(); ?></li>
		<li><strong><?php _e( 'Receiver', 'cx' ); ?>:</strong> <?php echo $receiver; ?></li>
	</ul>

	<?php

	if( $status == 'failed' )
		echo '<p style="color:red">' . __( 'Message has NOT been sent to offline emails!', 'cx' ) . '</p>';
	else
		echo '<p style="color:green">' . __( 'Message has been received to offline emails successfully.', 'cx' ) . '</p>';
}

/**
 * Render chat options metabox
 *
 * @return void
 */
function cx_render_opts_meta( $post ) {
	
	// Get fields
	$f_customize = get_post_meta( $post->ID, 'cx_opt_customize', true ); 
	$f_display = get_post_meta( $post->ID, 'cx_display', true ); 
	$f_autoinit_sec = get_post_meta( $post->ID, 'cx_autoinit_sec', true ); 

	// Use nonce for verification
	wp_nonce_field( plugin_basename( __FILE__ ), 'cx_nonce' );
	
	// Display chat box?
	echo '<strong>' . __( 'Display chat box', 'cx' ) . ':</strong><br>';
	echo '<label><input type="radio" name="cx_display" id="cx_display_default" value="" ' . checked( $f_display, '', false ) . ' /> ' . __( 'Default', 'cx' ) . '</label> ';
	echo '<label><input type="radio" name="cx_display" id="cx_display_show" value="show" ' . checked( $f_display, 'show', false ) . ' /> ' . __( 'Show', 'cx' ) . '</label> ';
	echo '<label><input type="radio" name="cx_display" id="cx_display_hide" value="hide" ' . checked( $f_display, 'hide', false ) . ' /> ' . __( 'Hide', 'cx' ) . '</label><br><br>';

	// Auto-initiate chat ?>
	
	<!-- <strong><?php _e( 'Auto-initiate chat after', CX_PX ); ?></strong><br>
	<input type="text" name="cx_autoinit_sec" id="cx_autoinit_sec"  value="<?php echo ( !empty( $f_autoinit_sec ) ) ? $f_autoinit_sec : 0; ?>" size="4"> 
		<span style="color:gray"><?php _e( 'second(s)', CX_PX ); ?></span><br>
		<small style="color:gray"><?php _e( 'Set 0 (zero) to use default value in chat settings', CX_PX ); ?></small><br><br>
 -->
	<?php
	// Customize chat box
	/*echo '<label>';
	
	echo '<input type="checkbox" name="cx_opt_customize" id="cx_opt_customize" ' . checked( $f_show_chatbox, 'on', false ) . ' value="on" /> ' . __( 'Customize chat box', 'sc_chat' );
	 
	echo '</label>';*/

}

/**
 * Row actions for all post types
 *
 * @return array
 */
function cx_row_actions( $actions, $post ) {

	switch( $post->post_type ) {
		case 'cx_visitor':
		/*	unset( $actions['inline hide-if-no-js'] );
			unset( $actions['edit'] );
			unset( $actions['trash'] );

			// Add new actions
			// $actions['block'] = "<a class='submitdelete' title='" . esc_attr( __( 'Block this user from chatting with operators', 'cx' ) ) . "' href='" . admin_url( 'edit.php?post_type=cx_visitor&ID=' . $post->ID . '&action=block' ) . "'>" . __( 'Block', 'cx' ) . "</a>";
			$actions['trash'] = "<a class='submitdelete' title='" . esc_attr( __( 'Delete this item permanently' ) ) . "' href='" . get_delete_post_link( $post->ID, '', true ) . "'>" . __( 'Delete Permanently', 'cx' ) . "</a>";
*/
		break;

	}

	return $actions;
}

/**
 * Save post data
 *
 * @return void
 */
function cx_save_post( $post_ID ) {

	$post_type = get_post_type();

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
		return;
	
	// Check nonce
	$_nonce = ( !empty( $_POST['cx_nonce'] ) ) ? $_POST['cx_nonce'] : '';

	if ( !wp_verify_nonce( $_nonce, plugin_basename( __FILE__ ) ) )
		return;

	// Check permissions
	switch( $post_type ) {
		
		case 'page':
			if( !current_user_can( 'edit_page', $post_ID ) ) 
				return;

		case 'post':
			if( !current_user_can( 'edit_post', $post_ID ) )
				return;
	}

	// Save form data
	/*switch ( $post_type ) {
		case 'post':
		case 'page':
			
			

			break;

		
	}*/

	// Update options
	add_post_meta( $post_ID, 'cx_display', $_POST['cx_display'], true ) or update_post_meta( $post_ID, 'cx_display', $_POST['cx_display'] );
	// add_post_meta( $post_ID, 'cx_autoinit_sec', $_POST['cx_autoinit_sec'], true ) or update_post_meta( $post_ID, 'cx_autoinit_sec', $_POST['cx_autoinit_sec'] );
}

/** 
 * Send a POST requst using cURL 
 * @param string $url to request 
 * @param array $post values to send 
 * @param array $options for cURL 
 * @return string 
 */ 
function cx_curl_post( $url, array $post = NULL, array $options = array() ) { 
	
	$defaults = array( 
		CURLOPT_POST => 1, 
		CURLOPT_HEADER => 0, 
		CURLOPT_URL => $url, 
		CURLOPT_FRESH_CONNECT => 1, 
		CURLOPT_RETURNTRANSFER => 1, 
		CURLOPT_FORBID_REUSE => 1, 
		CURLOPT_TIMEOUT => 7,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_FAILONERROR => true,
		CURLOPT_SSL_VERIFYPEER => 2,
		CURLOPT_USERAGENT => "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1",
		CURLOPT_POSTFIELDS => http_build_query( $post, '', '&' )
	); 

	$ch = curl_init(); 
	curl_setopt_array($ch, ( $options + $defaults )); 
	
	if( ! $result = curl_exec( $ch ) )  { 
		$result['error'] = curl_error( $ch );
		$result['error_no'] = curl_errno( $ch );
		trigger_error( curl_error( $ch ) ); 
	}

	curl_close($ch);

	return $result; 

} 

/**
 * Check API
 *
 * @return void
 */
function cx_api( $c = null, $force = false, $recheck = false ) {
	global $CX;

	$lc = get_option( 'cx_c9f1a6384b1c466d4612f513bd8e13ea' );
	$lce = get_option( 'cx_error' );
	$_lk = !empty( $CX->opts['license_key'] ) ? $CX->opts['license_key'] : '';

	if( defined( cx_( 'Q1hfQUNUSVZBVEVfSw==' ) ) && !$force ) {

		if( empty( $lc ) ) {
			update_option( 'cx_c9f1a6384b1c466d4612f513bd8e13ea', cx_( 'QWN0aXZlIGxpY2Vuc2U=' ) . ': ' . CX_ACTIVATE_T . '<small style="display:block;max-width:500px;color:#999" class="description">'. cx_('QXV0by11cGRhdGVzIG5vdCBzdXBwb3J0ZWQuIFBsZWFzZSBrZWVwIHlvdXIgdGhlbWUgdXAtdG8tZGF0ZS4gSWYgeW91IHdhbnQsIHlvdSBjYW4gcHVyY2hhc2UgYSBDWCBsaWNlbnNlIHRvIGdldCBhdXRvLXVwZGF0ZXMgYW5kIGZyZWUgc3VwcG9ydCBmcm9tIFNjcmVldHMu').'.</small>' );
			
			return;
		}
		
	}

	if( !$recheck && $_lk == $c && !empty( $lc ) ) return; // don't recheck again
	

	if( !$force && ( !empty( $lc ) || !empty( $lce ) ) ) return;

	delete_option( 'cx_c9f1a6384b1c466d4612f513bd8e13ea' );

	if( function_exists( 'curl_init' ) ) {

		$d = cx_current_page_url();
		$data = cx_curl_post( "http://www.screets.com/api/v2/", array( 'c' => $c, 'p' => 'CX', 'd' => $d, 'v' => CX_VERSION ) );
		
		$r = explode( "\n", $data );
		$hash = array_shift( $r );
		if( $hash == 'c9f1a6384b1c466d4612f513bd8e13ea' ) update_option( 'cx_c9f1a6384b1c466d4612f513bd8e13ea', $r[0] );
		elseif( !empty( $r[0] ) ) { 
			update_option( 'cx_error', $r[0] ); delete_option( 'cx_c9f1a6384b1c466d4612f513bd8e13ea' ); 
		} else {
			// Catch error
			update_option( 'cx_error', cx_( 'Q1ggY2FuJ3QgYWN0aXZhdGUgaXRzZWxmIG5vdy4gWW91IHdpbGwgd2FudCB0byBzZW5kIGVycm9yIG1lc3NhZ2UgdG8gdXM6IDxhIGhyZWY9Im1haWx0bzpzdXBwb3J0QHNjcmVldHMuY29tIj5zdXBwb3J0QHNjcmVldHMuY29tPC9hPi4gSWYgeW91IGNyZWF0ZSB0ZW1wb3JhcnkgYWRtaW4gdXNlciwgdGhlbiB3ZSBjYW4gYWN0aXZhdGUgbGljZW5zZSBmb3IgeW91IQ==' ) . '<p><small><strong>ERROR MESSAGE:</strong> ' . print_r( $data,1) . '</small></p>' );
		}

	
	} else {
		update_option( 'cx_error', cx_( 'Y1VSTCBpcyA8c3Ryb25nPk5PVDwvc3Ryb25nPiBpbnN0YWxsZWQgaW4geW91ciBQSFAgaW5zdGFsbGF0aW9uLiBZb3Ugd2lsbCB3YW50IHRvIGZvbGxvdyA8YSBocmVmPSJodHRwOi8vc3RhY2tvdmVyZmxvdy5jb20vcXVlc3Rpb25zLzEzNDcxNDYvaG93LXRvLWVuYWJsZS1jdXJsLWluLXBocC14YW1wcCIgdGFyZ2V0PSJfYmxhbmsiPm9uZSBvZiB0aG9zZSB0dXRvcmlhbHM8L2E+IHRvIGVuYWJsZSBjVVJMIGxpYnJhcnk=' ) );
	}

}