<?php
/**
 * SCREETS © 2014
 *
 * Plugin options
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */


global $wp_roles;

// Get default WP user roles
if ( ! isset( $wp_roles ) ) $wp_roles = new WP_Roles();
	
$role_names = $wp_roles->get_names();

$default_roles = array( 'none' => __( 'N/A', 'cx' ) );
$add_roles = array( 'none' => __( 'N/A', 'cx' ) );

foreach ( $role_names as $v => $name ) {
	
	switch( $v ) {
		case 'cx_op':
			break;

		// Default roles
		case 'administrator':
		case 'editor':
		case 'author':
		case 'contributor':
		case 'subscriber':
			$default_roles[ $v ] = $name;
			break;

		// Additional roles
		default:
			$add_roles[ $v ] = $name;

	}
	
}

// Get operators
$cx_op_data = get_users( 'role=cx_op' );

// echo '<pre>'; print_r( $cx_op_data ); echo '</pre>';

if( !empty( $cx_op_data ) ) {

	$cx_ops = '<table class="cx-table"><thead><tr>'
					. '<th>Avatar</th>'
					. '<th>'. __( 'Operator Name', 'cx' ) . '</th>'
					. '<th>'. __( 'Username', 'cx' ) . '</th>'
					. '<th>'. __( 'Email', 'cx' ) . '</th>'
					. '</tr></thead>'
					. '<tbody class="the-list">';

	foreach( $cx_op_data as $op ) {
		
		// Get operator name
		$op_name = get_user_meta( $op->data->ID, 'cx_op_name', true );

		// Table row
		$cx_ops .= '<tr><td><a href="' . admin_url( 'user-edit.php?user_id=' . $op->data->ID ) . '">' . get_avatar( $op->data->user_email, 32 ) .'</a></td><td><strong><a href="' . admin_url( 'user-edit.php?user_id=' . $op->data->ID ) . '">' . ( !empty( $op_name ) ? $op_name : $op->data->display_name )  . '</a></strong></td><td>' . $op->data->display_name . '</td><td>' . $op->data->user_email . '</td></tr>';
	}

	$cx_ops .= '</tbody></table>';

} else {

	$cx_ops = '<small>' . __( 'No operators found', 'cx' ) . '.</small>';

}

/**
 * Plugin options set
 *
 * DO NOT CHANGE DEFAULT OPTIONS.
 * INSTEAD, USE ADMIN PANEL.
 */
$cx_opts_set = array(
	
	/** 
	 * Welcome Tab
	 */
	/*array( 'name' => __( 'Dashboard', 'cx' ), 'type' => 'opentab' ),
	array( 'type' => 'dashboard' ),
	array( 'type' => 'closetab', 'actions' => false ),*/

	/**
	 * General Options
	 */
	array(
		'name' => __( 'General', 'cx' ),
		'id' => 'general',
		'type' => 'opentab'
	),

	// Header: Display
	array(
		'name' => __( 'Display', 'cx' ),
		'type' => 'title'
	),

	// Display chat box
	array(
		'name' => __( 'Display chat box', 'cx' ),
		'desc' => sprintf( __( "<a href='%s' target='_blank'>wp_footer()</a> function has to be located in your theme", 'cx' ), 'http://codex.wordpress.org/Function_Reference/wp_footer' ) ,
		'options' => array(
			'display_chatbox' => __( 'Display chat box', 'cx' ),
			// 'hide_chatbox_when_offline' => __( 'Hide when all operators offline', 'cx' ),
			'disable_on_mobile' => __( 'Disable on mobile devices', 'cx' ),
			'woocomerce_pages' => __( 'Display in WooCommerce pages', 'cx' ) . ' <small style="color: #999;">(' . __( 'Product pages, cart and checkout', 'cx' ) . ')</small>'
		),
		'std' => array( 
			'display_chatbox' => 'on', 
			// 'hide_chatbox_when_offline' => '', 
			'disable_on_mobile' => '',
			'woocomerce_pages' => ''
		),
		'id' => 'display-chatbox-group',
		'type' => 'checkbox-group'
	),

	// Homepage display options
	array(
		'name' => __( 'Display on homepage?', 'cx' ),
		'desc' => '',
		'options' => array(
			'show' => __( 'Show', 'cx' ),
			'hide' => __( 'Hide', 'cx' )
		),
		'std' => array( 
			'show' => 'on', 
			'hide' => ''
		),
		'id' => 'display-home',
		'type' => 'radio'
	),

	// Display badge
	// array(
	// 	'name' => __( 'Display badge', 'cx' ),
	// 	'desc' => '' ,
	// 	'options' => array(
	// 		'show_badge' => __( 'Show badge on startup automatically', 'cx' )
	// 	),
	// 	'std' => array( 
	// 		'show_badge' => ''
	// 	),
	// 	'id' => 'display-badge-group',
	// 	'type' => 'checkbox-group'
	// ),


	// Auto initiate chat
	/*array(
		'name' => __( 'Auto-initiate chat after', 'cx' ),
		'desc' => __( '0 (zero) for disabling auto-initiate', 'cx' ),
		'std' => 0,
		'min' => 0,
		'max' => 600,
		'units' => __( 'second(s)', 'cx' ),
		'id' => 'autoinitiate_sec',
		'type' => 'number'
	),
*/	

	// Header: Notifications
	array(
		'name' => __( 'Notifications', 'cx' ),
		'type' => 'title'
	),

	// Notify operators
	array(
		'name' => __( 'Notify operators by email', 'cx' ),
		'desc' => '',
		'options' => array(
			'new-user' => __( 'When visitor logs into chat', 'cx' )
		),
		'std' => array( 
			'new-user' => '' 
		),
		'id' => 'ntf-email-group',
		'type' => 'checkbox-group'
	),


	// Header: License
	array(
		'name' => __( 'License', 'cx' ),
		'type' => 'title'
	),

	// Screets API
	array(
		'name' => '<div class="cx-license-img"></div>' . __( 'Purchase Code', 'cx' ),
		'desc' => '<small>' . sprintf( __( 'You can find your <strong>purchase code</strong> under <a href="%s" target="_blank">Downloads</a> tab in CodeCanyon. <br />Just click "Download" button next to the the plugin.', 'cx' ), 'http://www.codecanyon.com/downloads' ) . '</small>' ,
		'std' => '',
		'id' => 'license_key',
		'type' => 'license'
	),


	// Upgrading?
	array(
		'name' => __( 'Upgrade', 'cx' ),
		'label' => 'Upgrading from <em>WordPress Live Chat</em> plugin?',
		'desc' => '',
		'id' => 'lc_upgrading',
		'type' => 'checkbox'
	),


	// LC Purchase Code
	array(
		'name' => __( 'Purchase Code', 'cx' ) . '<br><small>(Live Chat plugin)</small>',
		'desc' => '<small>' . __( 'Please enter your Live Chat plugin purchase code here', 'cx' ) . '</small>',
		'placeholder' => 'Live Chat plugin purchase code' ,
		'id' => 'lc_license_key',
		'type' => 'text'
	),


	array( 'type' => 'closetab' ),


	/**
	 * Appearance
	 */
	array(
		'name' => __( 'Appearance', 'cx' ),
		'id' => 'appearance',
		'type' => 'opentab'
	),

	// Header
	array(
		'name' => __( 'Chat Window', 'cx' ),
		'type' => 'title'
	),

	// Skin
	array(
		'name' => __( 'Skin', 'cx' ),
		'desc' => '',
		'options' => array(
			'basic' => '<img src="' . CX_URL . '/assets/img/cx-skin-basic.png" style="vertical-align:text-top"> &nbsp; ',
			'fixed' => '<img src="' . CX_URL . '/assets/img/cx-skin-fixed.png"  style="vertical-align:text-top">'
		),
		'std' => 'basic',
		'id' => 'base_skin',
		'type' => 'radio'
	),

	// Primary Color
	array(
		'name' => __( 'Primary Color', 'cx' ),
		'desc' => __( 'Also using for colorizing offline emails', 'cx' ),
		'std' => '#e54440',
		'id' => 'primary_color',
		'type' => 'color'
	),

	// Link Color
	array(
		'name' => __( 'Link Color', 'cx' ),
		'desc' => '',
		'std' => '#3894db',
		'id' => 'link_color',
		'type' => 'color'
	),

	// Widget size
	array(
		'name' => __( 'Widget size', 'cx' ),
		'desc' => '',
		'std' => 245,
		'min' => 170,
		'max' => 400,
		'units' => 'px',
		'id' => 'widget_width',
		'type' => 'number'
	),

	// Widget Position
	array(
		'name' => __( 'Widget Position', 'cx' ),
		'desc' => __( 'Which side do you want to display chat box', 'cx' ),
		'options' => array(
			'top-left' => __( 'top-left', 'cx' ) . '<br>',
			'top-right' => __( 'top-right', 'cx' ) . '<br>',
			'bottom-left' => __( 'bottom-left', 'cx' ) . '<br>',
			'bottom-right' => __( 'bottom-right', 'cx' ),
		),
		'std' => 'bottom-right',
		'id' => 'widget_position',
		'type' => 'radio'
	),

	// Animation
	array(
		'name' => __( 'Animation', 'cx' ),
		'desc' => '',
		'options' => array(
			'' => __( 'N/A', 'cx' ),
			'bounceIn' => 'Bounce In',
			'fadeIn' => 'Fade In'
		),
		'std' => 'bounceInUp',
		'id' => 'anim',
		'btn' => __( 'Animate', 'cx'),
		'type' => 'select'
	),

	// Make anim faster
	array(
		'name' => '',
		'desc' => '' ,
		'options' => array(
			'hinge' => __( 'Speed up animation', 'cx' )
		),
		'std' => array( 
			'hinge' => ''
		),
		'id' => 'anim-group',
		'type' => 'checkbox-group'
	),

	// Offset
	array(
		'name' => __( 'Offset', 'cx' ),
		'desc' => '',
		'std' => array( 30, 'px' ),
		'min' => 0,
		'max' => 720,
		'units' => array( 'px', '%' ),
		'id' => 'tab_offset',
		'type' => 'size'
	),

	// Delay
	array(
		'name' => __( 'Delay', 'cx' ),
		'desc' => __( '1000 ms = 1 second', 'cx' ),
		'std' => 0,
		'min' => 0,
		'max' => 10000000,
		'units' => __( 'milliseconds', 'cx' ),
		'id' => 'delay',
		'type' => 'number'
	),

	// Radius
	array(
		'name' => __( 'Radius', 'cx' ),
		'desc' => '',
		'std' => array( 5, 'px' ),
		'min' => 0,
		'max' => 50,
		'units' => array( 'px', 'em' ),
		'id' => 'radius',
		'type' => 'size'
	),

	// Reply Box Position
	array(
		'name' => __( 'Reply Box Position', 'cx' ),
		'desc' => '',
		'options' => array(
			'top' => __( 'Top', 'cx' ) . ' &nbsp; ',
			'bottom' => __( 'Bottom', 'cx' )
		),
		'std' => 'top',
		'id' => 'reply_pos',
		'type' => 'radio'
	),

	// Operator's default avatar
	array(
		'name' => __( "Company Avatar", 'cx' ),
		'desc' => __( "Default operators' avatar", 'cx' ),
		'type' => 'upload',
		'id' => 'default_avatar',
		'avatar' => CX_URL . '/assets/img/default-avatar.png'
	),
	
	// Avatar Size
	array(
		'name' => __( 'Avatar Size', 'cx' ),
		'desc' => '',
		'std' => 30,
		'min' => 20,
		'max' => 500,
		'units' => 'px',
		'id' => 'avatar_size',
		'type' => 'number'
	),

	
	// Avatar Radius
	array(
		'name' => __( 'Avatar Radius', 'cx' ),
		'desc' => __( 'If avatar size and radius values are the same, avatar will be circle', 'cx' ),
		'std' => 30,
		'min' => 0,
		'max' => 100,
		'units' => 'px',
		'id' => 'avatar_radius',
		'type' => 'number'
	),

	// Header: Chat Button Appearance
	array(
		'name' => __( 'Chat Button', 'cx' ),
		'type' => 'title'
	),

	// Appearance
	array(
		'name' => __( 'Appearance', 'cx' ),
		'desc' => '' ,
		'options' => array(
			'show_title' => __( 'Show title', 'cx' ),
			'show_icon' => __( 'Show icon', 'cx' ),
			'show_arrow' => __( 'Show arrow', 'cx' )
		),
		'std' => array( 
			'show_title' => 'on',
			'show_icon' => '',
			'show_arrow' => 'on'
		),
		'id' => 'chat-btn-group',
		'type' => 'checkbox-group'
	),

	// Button Size
	array(
		'name' => __( 'Button Size', 'cx' ),
		'desc' => __( '0 (zero): No fixed size', 'cx' ),
		'std' => 0,
		'min' => 0,
		'max' => 800,
		'units' => 'px',
		'id' => 'btn_width',
		'type' => 'number'
	),

	// Button title
	array(
		'name' => __( 'Button title', 'cx' ),
		'desc' => '',
		'std' => __( 'Chat with us', 'cx' ),
		'id' => 'when_online',
		'translate' => true,
		'type' => 'text'
	),


	// Header: Messages
	array(
		'name' => __( 'Messages', 'cx' ),
		'type' => 'title'
	),

	// Offline title
	array(
		'name' => __( 'Offline form title', 'cx' ),
		'desc' => '',
		'std' => __( 'Contact us', 'cx' ),
		'id' => 'when_offline',
		'translate' => true,
		'type' => 'text'
	),

	// Reply message placeholder
	array(
		'name' => __( 'Reply field label', 'cx' ),
		'desc' => '',
		'std' => __( 'Type here and hit enter to chat', 'cx' ),
		'id' => 'popup_reply_ph',
		'translate' => true,
		'type' => 'text'
	),

	// Offline body
	array(
		'name' => __( 'Offline body', 'cx' ),
		'desc' => __( 'HTML tags are allowed', 'cx' ),
		'std' => __( "Sorry! We aren't around right now. Leave a message and we'll get back to you, asap.", 'cx' ),
		'id' => 'offline_body',
		'html' => true,
		'type' => 'textarea',
		'translate' => true,
		'rows' => 2
	),

	// Name field label
	array(
		'name' => __( 'Name field label', 'cx' ),
		'desc' => '',
		'std' => __( 'Your name', 'cx' ),
		'id' => 'f_name_label',
		'translate' => true,
		'type' => 'text'
	),

	// Email field label
	array(
		'name' => __( 'Email field label', 'cx' ),
		'desc' => '',
		'std' => __( 'E-mail', 'cx' ),
		'id' => 'f_email_label',
		'translate' => true,
		'type' => 'text'
	),

	// Phone field label
	array(
		'name' => __( 'Phone field label', 'cx' ),
		'desc' => '',
		'std' => __( 'Phone', 'cx' ),
		'id' => 'f_phone_label',
		'translate' => true,
		'type' => 'text'
	),

	// Message field label
	array(
		'name' => __( 'Message field label', 'cx' ),
		'desc' => '',
		'std' => __( 'How can we help you?', 'cx' ),
		'id' => 'f_msg_label',
		'translate' => true,
		'type' => 'text'
	),

	// Send button
	array(
		'name' => __( 'Send button', 'cx' ),
		'desc' => '',
		'std' => __( 'Send', 'cx' ),
		'id' => 'f_send_btn',
		'translate' => true,
		'type' => 'text'
	),

	// Theme Preview
	array(
		'type' => 'preview',
		'reply_ph' => __( 'Type here and hit enter to chat', 'cx' )
	),

	array( 'type' => 'closetab' ),

	/**
	 * Forms
	 */

	array(
		'name' => __( 'Forms', 'cx' ),
		'id' => 'forms',
		'type' => 'opentab'
	),

	

	// Header: Login form
	array(
		'name' => __( 'Login form', 'cx' ),
		'type' => 'title'
	),

	// Display login form
	/*array(
		'name' => __( 'Display login form', 'cx' ),
		'desc' => __( 'If you hide display form, conversation box will appear automatically', 'cx' ),
		'std' => 'on',
		'id' => 'display_login',
		'label'	=>  '',
		'type' => 'checkbox'
	),
*/
	// Prechat welcome message
	array(
		'name' => __( 'Prechat message', 'cx' ),
		'desc' => '',
		'std' => __( "Questions? We're here. Send us a message!", 'cx' ),
		'id' => 'prechat_msg',
		'html' => true,
		'type' => 'textarea',
		'translate' => true,
		'rows' => 2
	),

	// Welcome message
	array(
		'name' => __( 'Welcome message', 'cx' ),
		'desc' => '',
		'std' => __( "Questions, issues or concerns? I'd love to help you!", 'cx' ),
		'id' => 'welc_msg',
		'html' => true,
		'type' => 'textarea',
		'translate' => true,
		'rows' => 2
	),

	// Prechat message when operator ends visitor chat
	/*array(
		'name' => __( 'Prechat message when operator ends visitor chat', 'cx' ),
		'desc' => __( 'HTML tags are allowed', 'cx' ),
		'std' => __( 'Your chat session has been ended. If you have further questions or concerns, please feel free to login chat.', 'cx' ),
		'id' => 'prechat_msg_ended_chat',
		'html' => true,
		'type' => 'textarea',
		'translate' => true,
		'rows' => 2
	),*/

	// Name field (login form)
	array(
		'name' => __( 'Name field', 'cx' ),
		'desc' => '',
		'options' => array(
			'hidden' => __( 'Hidden', 'cx' ),
			'optional' => __( 'Optional', 'cx' ),
			'req' => __( 'Required', 'cx' ),
		),
		'std' => 'optional',
		'id' => 'fl_name',
		'type' => 'select'
	),

	// Email field (login form)
	array(
		'name' => __( 'Email field', 'cx' ),
		'desc' => '',
		'options' => array(
			'hidden' => __( 'Hidden', 'cx' ),
			'optional' => __( 'Optional', 'cx' ),
			'req' => __( 'Required', 'cx' ),
		),
		'std' => 'req',
		'id' => 'fl_email',
		'type' => 'select'
	),

	// Header: Contact form
	array(
		'name' => __( 'Contact form', 'cx' ),
		'type' => 'title'
	),

	// Name field (contact form)
	array(
		'name' => __( 'Name field', 'cx' ),
		'desc' => '',
		'options' => array(
			'hidden' => __( 'Hidden', 'cx' ),
			'optional' => __( 'Optional', 'cx' ),
			'req' => __( 'Required', 'cx' ),
		),
		'std' => 'req',
		'id' => 'f_name',
		'type' => 'select'
	),

	// Email field (contact form)
	array(
		'name' => __( 'Email field', 'cx' ),
		'desc' => '',
		'options' => array(
			'hidden' => __( 'Hidden', 'cx' ),
			'optional' => __( 'Optional', 'cx' ),
			'req' => __( 'Required', 'cx' ),
		),
		'std' => 'req',
		'id' => 'f_email',
		'type' => 'select'
	),

	// Phone field (contact form)
	array(
		'name' => __( 'Phone field', 'cx' ),
		'desc' => '',
		'options' => array(
			'hidden' => __( 'Hidden', 'cx' ),
			'optional' => __( 'Optional', 'cx' ),
			'req' => __( 'Required', 'cx' ),
		),
		'std' => 'hidden',
		'id' => 'f_phone',
		'type' => 'select'
	),


	array( 'type' => 'closetab' ),


	/**
	 * Offline
	 */

	array(
		'name' => __( 'Offline', 'cx' ),
		'id' => 'offline',
		'type' => 'opentab'
	),

	// Header: Offline messages
	array(
		'name' => __( 'Offline messages', 'cx' ),
		'type' => 'title'
	),

	// Email
	array(
		'name' => __( 'Where should offline messages go?', 'cx' ),
		'desc' => '<small>' . __( 'If you need SMTP configuration, you will want to use WP SMTP plugin', 'cx' ) . '.<br/>' . __( "Separate email addresses with comma ','", 'cx' ) . '</small>',
		'placeholder' => __( "Separate email addresses with comma ','", 'cx' ),
		'std' => '',
		'id' => 'admin_emails',
		'type' => 'text'
	),

	// Redirect contact button to that URL
	array(
		'name' => __( 'Redirect offline button to an URL', 'cx' ),
		'desc' => __( 'If you have already contact system, you can link button to an existing page when chat is offline', 'cx' ),
		'placeholder' => 'https://',
		'id' => 'offline_redirect_url',
		'type' => 'text'
	),


	// Site Name
	array(
		'name' => __( 'Site Name', 'cx' ),
		'desc' => '',
		'placeholder' => __( 'Site Name', 'cx' ),
		'id' => 'site_name',
		'type' => 'text'
	),

	// Site URL
	array(
		'name' => __( 'Site URL', 'cx' ),
		'desc' => '',
		'placeholder' => 'http://www.yourdomain.com',
		'id' => 'site_url',
		'type' => 'text'
	),

	// E-mail header
	array(
		'name' => __( 'E-mail header', 'cx' ),
		'desc' => __( 'HTML tags are allowed', 'cx' ),
		'std' => __( "We received your message and will respond within one business day, likely much sooner. We will be happy to provide you with a detailed answer as quickly as we can.", 'cx' ),
		'id' => 'contact_header',
		'html' => true,
		'type' => 'textarea',
		'translate' => true,
		'rows' => 5
	),

	// E-mail footer
	array(
		'name' => __( 'E-mail footer', 'cx' ),
		'desc' => __( 'HTML tags are allowed', 'cx' ),
		'std' => __( "Regards,\n\nYour Company\n<a href='http://www.yourdomain.com'>www.yourdomain.com</a>", 'cx' ),
		'id' => 'contact_footer',
		'html' => true,
		'type' => 'textarea',
		'translate' => true,
		'rows' => 5
	),

	// Send email to visitor as well
	array(
		'name' => __( 'Send a copy to visitor as well', 'cx' ),
		'desc' => '',
		'std' => '',
		'id' => 'contact_email_to_visitor',
		'label'	=>  '',
		'type' => 'checkbox'
	),
	
	array( 'type' => 'closetab' ),

	/**
	 * Users
	 */
	array(
		'name' => __( 'Users', 'cx' ),
		'id' => 'users',
		'type' => 'opentab'
	),

	// Header: Operators and Visitors
	array(
		'name' =>  __( 'Operators & Visitors', 'cx' ),
		'type' => 'title'
	),

	// Operators default role
	array(
		'name' => __( 'Default role of operators', 'cx' ),
		'desc' => __( "Operators have the same capability with this role. It does NOT mean this role will act like operator" , 'cx' ),
		'options' => $default_roles,
		'std' => 'editor',
		'id' => 'op_role',
		'type' => 'select'
	),

	// Operators additional role
	array(
		'name' => __( 'Additional role of operators', 'cx' ),
		'desc' => __( "Additional role from your active WP plugins" , 'cx' ),
		'options' => $add_roles,
		'std' => 'editor',
		'id' => 'op_add_role',
		'type' => 'select'
	),

	// Allowed visitors
	/*array(
		'name' => __( 'Allowed online visitors at a time', 'cx' ),
		'desc' => __( '0 (zero) for unlimited chats', 'cx' ). '<br>' . __( 'Other visitors are allowed to fill out the contact form instead of connecting to chat', 'cx' ),
		'std' => 5,
		'min' => 0,
		'max' => 100,
		'units' => __( 'visitor(s)', 'cx' ),
		'id' => 'allowed_online_visitors',
		'type' => 'number'
	),*/


	// Guest Prefix
	array(
		'name' => __( 'Guest Prefix', 'cx' ),
		'desc' => __( 'For instance, you can write <em>Guest-</em>. Then guest name looks like <em>Guest-1234</em>', 'cx' ),
		'placeholder' => __( 'Guest Prefix', 'cx' ),
		'suffix' => ' + ID',
		'id' => 'guest_prefix',
		'std' => __( 'Guest', 'cx' ) . '-',
		'type' => 'text2'
	),

	// Header: Operators List
	array(
		'name' =>  __( 'Operators List', 'cx' ),
		'type' => 'title'
	),

	// Operators list
	array(
		'html' => '<p><a href="' . admin_url( 'user-new.php' ) . '" class="button">' . __( 'Create Operator', 'cx' ) . '</a> &nbsp; ' . __( "Note that administrators can also answer visitors like operators", 'cx' ) .'.</p><br>' . $cx_ops,
		'type' => 'html'
	),

	array( 'type' => 'closetab' ),
	

	/**
	 * Advanced
	 */

	array(
		'name' => __( 'Advanced', 'cx' ),
		'id' => 'advanced',
		'type' => 'opentab'
	),

	// Header: Firebase Application
	array(
		'name' =>  __( 'Realtime App Platform', 'cx' ),
		'type' => 'title'
	),

	// 
	array(
		'html' =>'<img src="' . CX_URL . '/assets/img/firebase-logo.png" style="display: inline-block;"> ',
		'type' => 'html'
	),


	// Application ID
	array(
		'name' => __( 'App URL', 'cx' ),
		'desc' => __( 'URL of the application', 'cx' ) . '. ' . sprintf( __( '<a href="%s" target="_blank">Create free %s account</a>', 'cx' ),  'https://www.firebase.com/signup/', 'Firebase' ),
		'placeholder' => __( 'App URL', 'cx' ),
		'prefix' => 'https://',
		'suffix' => '.firebaseIO.com',
		'id' => 'app_url',
		'type' => 'text2'
	),


	// Application Token
	array(
		'name' => __( 'App Token', 'cx' ),
		'desc' => __( 'The token can be found under "Secrets" menu in your Firebase dashboard', 'cx' ),
		'placeholder' => __( 'App Token', 'cx' ),
		'id' => 'app_token',
		'type' => 'text'
	),
	
	// Header: Performance
	array(
		'name' => __( 'Performance', 'cx' ),
		'type' => 'title'
	),

	// Realtime data
	array(
		'type' => 'check_data'
	),

	// Check sessions
	array(
		'type' => 'check_session'
	),

	// Compress
	array(
		'name' => __( 'Compress', 'cx' ),
		'desc' => __( 'Improve performance by using compressed CSS / JS files of current template.', 'cx' ),
		'options' => array(
			'compress_js' => __( 'Use compiled JavaScript file', 'cx' ),
			'compress_css' => __( 'Use compressed CSS file', 'cx' ),
			'disable_css' => __( 'Disable CSS file', 'cx' )
		),
		'std' => array( 'compress_js' => 'on', 'compress_css' => 'on', 'disable_css' => '' ),
		'id' => 'compress-group',
		'type' => 'checkbox-group'
	),

	// Faster AJAX
	array(
		'name' => __( 'Faster AJAX', 'cx' ),
		'desc' => __( "This method isn't supported by some PHP/Apache server installations.<br>If contact form sends email after activating this method, you can use it safely.", 'cx' ),
		'label' => __( 'Use faster AJAX for the plugin instead of default WordPress AJAX', 'cx' ),
		'std' => '',
		'id' => 'faster_ajax',
		'type' => 'checkbox'
	),

	// Header: Advanced
	array(
		'name' => __( 'Advanced', 'cx' ),
		'type' => 'title'
	),


	// Debug
	array(
		'name' => __( 'Debug', 'cx' ),
		'desc' => '',
		'label' => __( 'Activate browser console debug', 'cx' ),
		'std' => '',
		'id' => 'debug',
		'type' => 'checkbox'
	),

	// Custom CSS
	array(
		'name' => __( 'Custom CSS', 'cx' ),
		'desc' => '',
		'std' => '',
		'rows' => 5,
		'id' => 'custom_css',
		'type' => 'code'
	),

	// Database tables
	array(
		'type' => 'check_db'
	),

	// Security status
	array(
		'type' => 'check_security'
	),

	// Cross Domain
	/*array(
		'name' => __( 'Cross Domain', 'cx' ),
		'desc' => __( 'If your site homepage is different from WP installation directory or running on a <strong>sub-domain</strong>, you need to cross domain boundaries', 'cx' ),
		'label' => __( 'Bypass cross domain limitations', 'cx' ),
		'std' => '',
		'id' => 'cross_domain',
		'type' => 'checkbox'
	),*/

	// Proxy IPs
	array(
		'name' => __( 'Reverse Proxy IPs', 'cx' ),
		'desc' => __( "If your server is behind a reverse proxy, you must whitelist the proxy IP addresses from which WordPress should trust the HTTP_X_FORWARDED_FOR header in order to properly identify the visitor's IP address. Comma-delimited, e.g. '10.0.1.200,10.0.1.201'", 'cx' ),
		'std' => '',
		'id' => 'proxy_ips',
		'type' => 'text'
	),

	array( 'type' => 'closetab' ),

	/**
	 * Help
	 */

	array(
		'name' => __( 'Help', 'cx' ),
		'id' => 'help',
		'type' => 'opentab'
	),

	array(
		'type' => 'help'
	),

	array( 'type' => 'closetab' )

); 