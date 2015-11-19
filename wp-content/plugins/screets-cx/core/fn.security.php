<?php
/**
 * SCREETS © 2014
 *
 * Security functions
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */

/**
 * Fetch an item from the SERVER array
 *
 * @param	string
 * @param	bool
 * @return	string
 */
function cx_server( $index = '' ) {
	return cx_fetch_from_array( $_SERVER, $index );
}

/**
 * User Agent
 *
 * @return	string
 */
function cx_user_agent() {
	$user_agent = (!isset($_SERVER['HTTP_USER_AGENT'])) ? FALSE : $_SERVER['HTTP_USER_AGENT'];

	return $user_agent;

}

/**
 * Fetch the IP Address
 *
 * @return	string
 */
function cx_ip_address() {
	global $CX;
	
	$proxy_ips = ( !empty( $CX->opts['proxy_ips'] ) ) ? $CX->opts['proxy_ips'] : null;

	if ( $proxy_ips != '' && cx_server('HTTP_X_FORWARDED_FOR') && cx_server('REMOTE_ADDR')) {
		$proxies = preg_split( '/[\s,]/', $proxy_ips , -1, PREG_SPLIT_NO_EMPTY );
		$proxies = is_array( $proxies ) ? $proxies : array( $proxies );

		$ip_addr = in_array($_SERVER['REMOTE_ADDR'], $proxies) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
	}
	elseif ( cx_server( 'REMOTE_ADDR' ) AND cx_server( 'HTTP_CLIENT_IP' ) ) {
		$ip_addr = $_SERVER['HTTP_CLIENT_IP'];
	}
	elseif ( cx_server( 'REMOTE_ADDR' ) ) {
		$ip_addr = $_SERVER['REMOTE_ADDR'];
	}
	elseif ( cx_server( 'HTTP_CLIENT_IP' ) ) {
		$ip_addr = $_SERVER['HTTP_CLIENT_IP'];
	}
	elseif ( cx_server( 'HTTP_X_FORWARDED_FOR' ) ) {
		$ip_addr = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}

	if ( $ip_addr === FALSE ) {
		$ip_addr = '0.0.0.0';

		return $ip_addr;
	}

	if ( strpos( $ip_addr, ',' ) !== FALSE ) {
		$x = explode( ',', $ip_addr );
		$ip_addr = trim( end( $x ) );
	}

	if ( !cx_valid_ip( $ip_addr ) ) {
		$ip_addr = '0.0.0.0';
	}

	return $ip_addr;

}

/**
 * Validate IP Address
 *
 * @param	string
 * @param	string	ipv4 or ipv6
 * @return	bool
 */
function cx_valid_ip( $ip, $which = '' ) {

	$which = strtolower( $which );

	// First check if filter_var is available
	if ( is_callable( 'filter_var' ) ) {
		switch ($which) {
		case 'ipv4':
			$flag = FILTER_FLAG_IPV4;
			break;

		case 'ipv6':
			$flag = FILTER_FLAG_IPV6;
			break;

		default:
			$flag = '';
			break;
		}
		return (bool) filter_var( $ip, FILTER_VALIDATE_IP, $flag );
	}

	if ( $which !== 'ipv6' && $which !== 'ipv4' ) {
		if ( strpos($ip, ':') !== FALSE) {
			$which = 'ipv6';
		}
		elseif (strpos($ip, '.') !== FALSE) {
			$which = 'ipv4';
		}
		else {
			return FALSE;
		}
	}
	return call_user_func( 'cx_valid_' . $which, $ip );
}


/**
 * Validate IPv4 Address
 *
 * Updated version suggested by Geert De Deckere
 *
 * @param	string
 * @return	bool
 */
function cx_valid_ipv4( $ip ) {
	
	$ip_segments = explode( '.', $ip );

	// Always 4 segments needed
	if ( count($ip_segments) !== 4 ) {
		return FALSE;
	}
	// IP can not start with 0
	if ( $ip_segments[0][0] == '0' ) {
		return FALSE;
	}

	// Check each segment
	foreach( $ip_segments as $segment ) {
		// IP segments must be digits and can not be
		// longer than 3 digits or greater then 255
		if ( $segment == '' OR preg_match( "/[^0-9]/", $segment ) OR $segment > 255 OR strlen( $segment ) > 3 ) {
			return FALSE;
		}
	}
	return TRUE;
}


/**
 * Validate IPv6 Address
 *
 * @param	string
 * @return	bool
 */
function cx_valid_ipv6( $str ) {

	// 8 groups, separated by :
	// 0-ffff per group
	// one set of consecutive 0 groups can be collapsed to ::
	$groups = 8;
	$collapsed = FALSE;
	$chunks = array_filter(preg_split('/(:{1,2})/', $str, NULL, PREG_SPLIT_DELIM_CAPTURE));

	// Rule out easy nonsense
	if (current($chunks) == ':' OR end($chunks) == ':') {
		return FALSE;
	}

	// PHP supports IPv4-mapped IPv6 addresses, so we'll expect those as well
	if (strpos(end($chunks) , '.') !== FALSE) {
		$ipv4 = array_pop($chunks);
		if (!cx_valid_ipv4($ipv4)) {
			return FALSE;
		}
		$groups--;
	}

	while ($seg = array_pop($chunks)) {
		if ($seg[0] == ':') {
			if (--$groups == 0) {
				return FALSE; // too many groups
			}
			if (strlen($seg) > 2) {
				return FALSE; // long separator
			}
			if ($seg == '::') {
				if ($collapsed) {
					return FALSE; // multiple collapsed
				}
				$collapsed = TRUE;
			}
		}
		elseif (preg_match("/[^0-9a-f]/i", $seg) OR strlen($seg) > 4) {
			return FALSE; // invalid segment
		}
	}

	return $collapsed OR $groups == 1;
}

/**
 * Check if IP is blocked?
 *
 * @return bool true if ip address blocked!
 */
function cx_ip_blocked( $ip ) {
	global $wpdb;

	// Convert IP to int
	$ip = ip2long( $ip );

	// Prepare sql
	$sql = $wpdb->prepare( 
				'SELECT COUNT(*) FROM ' . CX_PX . 'blocked_ips ' 
				.' WHERE ip_address = %d LIMIT 1',
				$ip
			);

	// Check if IP address in block list
	if( $wpdb->get_var( $sql ) )
		return true;

	return false;
}

