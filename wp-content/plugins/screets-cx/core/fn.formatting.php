<?php
/**
 * SCREETS © 2014
 *
 * Formatting functions
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */

/**
 * Fetch from array
 *
 * This is a helper function to retrieve values from global arrays
 *
 * @param	array
 * @param	string
 * @param	bool
 * @return	string
 */
function cx_fetch_from_array( &$array, $index = '' ) {
	if ( !isset( $array[$index] ) ) {
		return FALSE;
	}

	return $array[$index];
}

/**
 * Sanitize username
 *
 * @access public
 * @return string Sanitized username
 */
function cx_sanitize_username( $username ) {
	
	return substr( trim( $username ), 0, 32);
	
}

/**
 * Make URLs into links 
 *
 * @access public
 * @return string Edited string
 */
 
function cx_make_url_to_link( $string ){

	// Make sure there is an http:// on all URLs
	$string = preg_replace( "/([^\w\/])(www\.[a-z0-9\-]+\.[a-z0-9\-]+)/i", "$1http://$2", $string );
	
	// Make all URLs links
	$string = preg_replace( "/([\w]+:\/\/[\w-?&;#~=\.\/\@]+[\w\/])/i","<a target=\"_blank\" href=\"$1\">$1</a>", $string );
	
	// Make all emails hot links
	$string = preg_replace( "/([\w-?&;#~=\.\/]+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,3}|[0-9]{1,3})(\]?))/i", "<a href=\"mailto:$1\">$1</a>", $string );

	return $string;
	
}