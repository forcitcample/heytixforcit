<?php

class WCCH_Cookie_Helper {

	static $cookie_name = 'woocommerce_ch_hash';
	static $expiration_length = 604800; // 7 Days

	/**
	 * Get stored user hash.
	 *
	 * @since  1.0.0
	 *
	 * @return string User's unique hash.
	 */
	public static function get_cookie() {
		return isset( $_COOKIE[ self::$cookie_name ] ) && ! empty( $_COOKIE[ self::$cookie_name ] )
			? esc_attr( $_COOKIE[ self::$cookie_name ] )
			: self::set_cookie();
	}

	/**
	 * Store history data to cookie.
	 *
	 * @since 1.0.0
	 *
	 * @return string User's unique hash.
	 */
	public static function set_cookie() {
		$hash = uniqid();
		setcookie( self::$cookie_name, $hash, time() + self::$expiration_length, '/' );
		return $hash;
	}

	/**
	 * Store history data in transient.
	 *
	 * @since 1.1.0
	 */
	public static function delete_history_data() {
		self::delete_cookie();
		self::delete_transient();
	}

	/**
	 * Delete stored history data.
	 *
	 * @since 1.0.0
	 */
	public static function delete_cookie() {
		setcookie( self::$cookie_name, '', time() - HOUR_IN_SECONDS, '/' );
	}

	/**
	 * Delete history data from transient.
	 *
	 * @since 1.1.0
	 */
	public static function delete_transient() {
		$user_hash = self::get_cookie();
		delete_transient( "wcch_{$user_hash}" );
	}

	/**
	 * Store history data in transient.
	 *
	 * @since 1.1.0
	 *
	 * @param array $data User's history data.
	 */
	public static function set_transient( $data ) {
		$user_hash = self::get_cookie();
		set_transient( "wcch_{$user_hash}", json_encode( $data ), self::$expiration_length );
	}

	/**
	 * Get history data from transient.
	 *
	 * @since 1.1.0
	 *
	 * @return string User's history data.
	 */
	public static function get_transient() {
		$user_hash = self::get_cookie();
		return json_decode( get_transient( "wcch_{$user_hash}" ) );
	}

}
