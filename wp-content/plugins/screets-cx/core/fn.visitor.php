<?php
/**
 * SCREETS © 2014
 *
 * Visitor functions
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */

/**
 * Insert/update visitor
 *
 * @param array $user User data (`Title` is required)
 * @return int $visitor_id or 0 if error occurred 
 */
function cx_insert_visitor( $visitor ) {
	global $wpdb;

	// `Title` is required
	if( empty( $visitor['Title'] ) )
		return 0;


	if( !empty( $visitor['ID'] ) ) {

		$visitor_id = $visitor['ID'];

	} else {

		// Find visitor by IP
		if( !empty( $visitor['IP Address'] ) ) {
			$visitor_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta}
						  WHERE meta_key = 'IP Address' AND meta_value = '%s'
						  LIMIT 1", 
						  $visitor['IP Address']
			) );

		}

		// Find visitor by email
		if( !empty( $visitor['Email'] ) && empty( $visitor_id ) ) {
			$visitor_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta}
						  WHERE meta_key = 'Email' AND meta_value = '%s'
						  LIMIT 1", 
						  $visitor['Email']
			) );

		}

	}

	// Create new visitor
	$data = array(
		'post_type' 	=> 'cx_visitor',
		'post_title'	=> $visitor['Title'],
		'post_status'	=> 'publish'
	);

	// Insert visitor ID
	if( !empty( $visitor_id ) )
		$data['ID'] = $visitor_id;

	// Clean visitor data
	unset( $visitor['Title'] );

	// Create visitor
	if( !$visitor_id = wp_insert_post( $data ) )
		return 0;

	// Add / update visitor meta
	foreach( $visitor as $k => $v ) {
		if( !empty( $v ) )
			add_post_meta( $visitor_id, $k, $v, true ) || update_post_meta( $visitor_id, $k, $v );
	}


	return $visitor_id;

}