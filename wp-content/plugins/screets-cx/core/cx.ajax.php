<?php
/**
 * SCREETS © 2014
 *
 * Custom AJAX
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */

// Mimic the actuall admin-ajax
define('DOING_AJAX', true);

if ( !isset( $_GET['action'] ) )
    die( '-1' );

// Make sure you update this line 
// to the relative location of the wp-load.php
require_once '../../../../wp-load.php'; 

// Typical headers
header('Content-Type: text/html');
send_nosniff_header();

// Disable caching
header(' Cache-Control: no-cache' );
header( 'Pragma: no-cache' );


$action = esc_attr( trim( $_GET['action'] ) );

if( $action == 'cx_ajax_callback' ) {

    if( is_user_logged_in() )
        do_action( 'cx_ajax_' . $action );
    else
        do_action( 'cx_ajax_nopriv_' . $action );

} else
    die('-1');