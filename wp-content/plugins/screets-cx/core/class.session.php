<?php
/**
 * WordPress Session Management
 *
 * Standardizes WordPress session data and uses either database transients or in-memory caching
 * for storing user session information.
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 *
 */


/**
 * CX_Session Class
 *
 */
class CX_Session {

	/**
	 * Holds our session data
	 *
	 * @var array
	 * @access private
	 */
	private $session = array();


	private $PHP_sessions = CX_PHP_SESSIONS;


	/**
	 * Construction
	 */
	public function __construct() {

		// Session control on user data
		add_action( 'wp_login', 'cx_destroy_session' );
		add_action( 'wp_logout', 'cx_logout' );

		// Use native PHP sessions
		if( $this->PHP_sessions ) {

			if( !session_id() )
				add_action( 'init', 'session_start', -2 );

		// Use WP Session Manager
		} else {

			// Let users change the session cookie name
			if( ! defined( 'WP_SESSION_COOKIE' ) )
				define( 'WP_SESSION_COOKIE', '_wp_session' );

			if ( ! class_exists( 'Recursive_ArrayAccess' ) ) {
				require_once( CX_PATH . '/core/lib/wp-session-manager/class-recursive-arrayaccess.php' );
			}

			// Only include the functionality if it's not pre-defined.
			if ( ! class_exists( 'WP_Session' ) ) {
				require_once( CX_PATH . '/core/lib/wp-session-manager/class-wp-session.php' );
				require_once( CX_PATH . '/core/lib/wp-session-manager/wp-session.php' );
			}

		}

		// Initialize the session
		if ( empty( $this->session ) && ! $this->PHP_sessions ) {
			add_action( 'plugins_loaded', array( $this, 'init' ), -1 );
		} else {
			add_action( 'init', array( $this, 'init' ), -1 );
		}

	}


	/**
	 * Setup the WP_Session instance
	 *
	 * @access public
	 * @return void
	 */
	public function init() {

		if( $this->PHP_sessions )
			$this->session = isset( $_SESSION['screets_cx'] ) && is_array( $_SESSION['screets_cx'] ) ? $_SESSION['screets_cx'] : array();
		else
			$this->session = WP_Session::get_instance();

		return $this->session;
	}


	/**
	 * Retrieve session ID
	 *
	 * @access public
	 * @return string Session ID
	 */
	public function get_id() {
		return $this->session->session_id;
	}


	/**
	 * Retrieve a session variable
	 *
	 * @access public
	 * @param string $key Session key
	 * @return string Session variable
	 */
	public function get( $key ) {

		return isset( $this->session[ $key ] ) ? maybe_unserialize( $this->session[ $key ] ) : false;

	}

	/**
	 * Set a session variable
	 *
	 * @param $key Session key
	 * @param $value Session variable
	 * @return mixed Session variable
	 */
	public function set( $key, $value ) {
		
		// Set value
		$this->session[ $key ] = $value;

		if( $this->PHP_sessions )
			$_SESSION['screets_cx'] = $this->session;

		return $this->session[ $key ];
	}

	
}