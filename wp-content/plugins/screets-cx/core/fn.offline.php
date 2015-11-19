<?php
/**
 * SCREETS © 2014
 *
 * Offline functions
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */


/**
 * Add offline message
 *
 * @param string $msg_content
 * @param array $f  Message custom fields
 * @param string $ip_addr  User IP address
 * @return int $msg_id
 */
function cx_add_offline_msg( $msg_content, $f, $ip_addr ) {

	if( !empty( $f['name'] ) )
		$title = $f['name'];

	elseif( !empty( $f['email'] ) )
		$title = $f['email'];
	
	elseif( !empty( $f['phone'] ) )
		$title = $f['phone'];

	else
		$title = $ip_addr;

	// Prepare post data
	$data = array(
		'post_type' 	=> 'cx_offline_msg',
		'post_title'	=> $title,
		'post_content' 	=> $msg_content,
		'post_status'	=> 'publish'
	);

	// Add offline message
	$msg_id = wp_insert_post( $data );
	
	
	// Include IP address to fields
	$f['ip_addr'] = $ip_addr;

	// Add / update message meta
	foreach( $f as $k => $v ) {
		if( !empty( $v ) )
			add_post_meta( $msg_id, $k, $v, true ) || update_post_meta( $msg_id, $k, $v );
	}

	return $msg_id;

}


/**
 * Get email header
 *
 * @return	string
 */
function cx_offline_email_head() {
	global $CX;

	// Header
	return '<table width="100%" cellspacing="0" cellpadding="0" style="color:#ffffff;font-family:Arial,sans-serif;font-size:14px;background-color:'.$CX->opts['primary_color'].';">'
    	   .'<tr>'
    	   .'<td valign="bottom" style="font-size:15px;font-weight:bold;padding:10px;">' . $CX->opts['site_name'] . '</td>'
    	   .'<td align="right" style="padding:10px;"><a href="http://'. $CX->opts['site_url'] .'" style="color:#ffffff;text-decoration:none;">'. $CX->opts['site_url'] .'</a></td>'
    	   .'</tr></table>';

}

/**
 * Get email footer
 *
 * @return	string
 */
function cx_offline_email_foot() {
	global $CX;

	return '<div style="font-size:13px;padding:30px;border-top:1px solid #ddd;margin-top:30px;">'. htmlspecialchars_decode( $CX->opts['contact_footer'] ) . '</div>';

}

/**
 * Send offline message
 *
 * @return	bool
 */
function cx_send_offline_msg( $to, $site_email, $data ) {
	global $CX;
	
	$f = array();	
	$usr = new CX_User;
	$opts = $CX->opts;
	$ip_addr = cx_ip_address();

	//
	// Email template
	//
	$msg = $html = '<div style="color:#222222;font-family:Arial,sans-serif;font-size:14px;">';

	$msg .= cx_offline_email_head();

   	// Wrapper
   	$msg .= '<div style="border-width:0 1px 1px 1px; border-style: solid; border-color: #ddd;">';

   	// Lead message
   	$msg .= '<div style="font-size:15px;padding:30px;line-height:20px;">'
   		   . htmlspecialchars_decode( $opts['contact_header'] ) . '</div>';

   	// Form details
   	$msg .= '<div style="font-size:15px;padding:0 30px 15px 30px;line-height:20px;">';


   	// 
   	// Message content
   	// 

   	// Name field
   	if( !empty( $data['name'] ) ) {
   		$msg .= '<strong>' . __( 'Name', 'cx' ) . '</strong>: ' . $data['name'] . '<br />';
   	}

   	// Email field
   	if( !empty( $data['name'] ) ) {
   		$msg .= '<strong>' . __( 'E-mail', 'cx' ) . '</strong>: <a href="'.$data['email'].'">' . $data['email'] . '</a><br />';
   	}

   	// Phone field
   	if( !empty( $data['phone'] ) ) {
   		$msg .= '<strong>' . __( 'Phone', 'cx' ) . '</strong>: ' . $data['phone'] . '<br />';
   	}

   	// Message field
	$msg .= '<strong>' . __( 'Message', 'cx' ) . '</strong>: <br>' . str_replace( "\n",'<br />', htmlspecialchars( stripslashes( $data['msg'] ) ) ) . '<br />';

   	// User additional message
	$msg .= '<div style="font-size:11px;padding:15px 0;">' . __( 'User information', 'cx' ) . ': <br>' .
			 	$ip_addr . ' - ' .
			 	$usr->info( 'os' ) . ', ' . $usr->info( 'browser' ) . ' ' . $usr->info( 'version' ) . '<br>' .
			 	$_SERVER['HTTP_REFERER'] . '<br>' .

			 '</div>';

   	$msg .= '</div>'; // form details


   	// Footer
	$msg .= cx_offline_email_foot();
		

	$msg .= '</div>'; // wrapper
	$msg .= '</div>';

	// Set subject
	$subject = '[' . $opts['site_name'] . '] ' . __( 'New offline message', 'cx' );

	/**
	 * Send email to admin emails
	 */
	$headers = array();
	$headers[] = 'MIME-Version: 1.0';
	$headers[] = 'content-type: text/html';
	$headers[] = 'charset=utf-8';

	// From visitor if email given by visitor
	if( !empty( $data['email'] ) ) {
		$_name = ( !empty( $data['name'] ) ) ? $data['name'] : $opts['site_name'];

		$headers[] = 'From: [' . $_name . '] <' . $data['email'] . '>';

	// From operator if no email given by visitor
	} else {
		$headers[] = 'From: [' . $opts['site_name'] . '] <' . $site_email . '>';
	}

	//
	// Send email to admins
	// 
	if( !wp_mail( $to, $subject, $msg, $headers ) ) {
		$f['status'] = 'failed';
	} else
		$f['status'] = 'succeed';

	//
	// Add offline message
	// 
	$f['name'] = $data['name'];
	$f['email'] = $data['email'];
	$f['os'] = $usr->info('os');
	$f['browser'] = $usr->info('browser');
	$f['version'] = $usr->info('version');
	$f['site_email'] = $site_email;
	$f['to'] = $to;

	cx_add_offline_msg( $data['msg'], $f, $ip_addr );


	// If email sent failed, return false
	if( $f['status'] == 'failed')
		return false;

	/**
	 * Send copy to  the visitor
	 */
	if( !empty( $data['email'] ) and !empty( $opts['contact_email_to_visitor'] ) ) {
		
		// Set subject
		$subject = '[' . $opts['site_name'] . '] ' . __( 'We received your message', 'cx' );

		$headers = array();
		$headers[] = 'MIME-Version: 1.0';
		$headers[] = 'content-type: text/html';
		$headers[] = 'charset=utf-8';

		$to = $data['email'];

		// Send email to the visitor
		wp_mail( $to, $subject, $msg, $headers );

	}

	return true;
}