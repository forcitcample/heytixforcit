<?php

/**
 * Plugin Name: Vamtam Twitter
 * Plugin URI: http://vamtam.com
 * Description: Basic Twitter API Wrapper
 * Version: 1.0.2
 * Author: Vamtam
 * Author URI: http://vamtam.com
 */

class Vamtam_Twitter {
	const TRANSIENT_EXPIRATION = 900;

	public function __construct() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 20 );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );

		if ( !class_exists( 'Vamtam_Updates' ) )
			require 'vamtam-updates/class-vamtam-updates.php';

		$plugin_slug = basename( dirname( __FILE__ ) );
		$plugin_file = basename( __FILE__ );

		new Vamtam_Updates( array(
			'slug' => $plugin_slug,
			'main_file' => $plugin_slug . '/' . $plugin_file,
		) );

		include 'lib/tlc-transients/tlc-transients.php';
	}

	public static function default_options() {
		$defaults = array(
			'oauth_access_token'  => '',
			'oauth_access_token_secret'  => '',
			'consumer_key'  => '',
			'consumer_secret'  => '',
		);

		return apply_filters( 'vamtam_twitter_default_options', $defaults );
	}

	public static function get_options() {
		return apply_filters( 'vamtam_twitter_options', wp_parse_args( get_option( 'vamtam_twitter_options' ), self::default_options() ) );
	}

	public static function admin_init() {
		add_settings_section(
			'general_settings_section',
			__( 'Twitter API', 'wpv' ),
			array( __CLASS__, 'general_options_description' ),
			'vamtam_twitter_options'
		);

		add_settings_field(
			'consumer_key',
			__( 'Application API Key', 'wpv' ),
			array( __CLASS__, 'textfield' ),
			'vamtam_twitter_options',
			'general_settings_section',
			array(
				'',
				'vamtam_twitter_options',
				'consumer_key',
			)
		);

		add_settings_field(
			'consumer_secret',
			__( 'Application API Secret', 'wpv' ),
			array( __CLASS__, 'textfield' ),
			'vamtam_twitter_options',
			'general_settings_section',
			array(
				'',
				'vamtam_twitter_options',
				'consumer_secret',
			)
		);

		add_settings_field(
			'oauth_access_token',
			__( 'oAuth Access Token', 'wpv' ),
			array( __CLASS__, 'textfield' ),
			'vamtam_twitter_options',
			'general_settings_section',
			array(
				'',
				'vamtam_twitter_options',
				'oauth_access_token',
			)
		);

		add_settings_field(
			'oauth_access_token_secret',
			__( 'oAuth Access Token Secret', 'wpv' ),
			array( __CLASS__, 'textfield' ),
			'vamtam_twitter_options',
			'general_settings_section',
			array(
				'',
				'vamtam_twitter_options',
				'oauth_access_token_secret',
			)
		);

		register_setting(
			'vamtam_twitter_options',
			'vamtam_twitter_options'
		);
	}

	public static function textfield($args) {
		$options = get_option( $args[1] );

		$html = '<input type="text" id="' . esc_attr( $args[2] ) . '" name="' . esc_attr( $args[1] . '[' . $args[2] . ']' ) . '" value="' . esc_attr( $options[ $args[2] ] ) . '" size="64"/>';

		$html .= '<label for="' . esc_attr( $args[2] ) . '">&nbsp;'  . $args[0] . '</label>';

		echo $html;
	}

	public static function general_options_description() {
		_e('generate an API key, etc', 'wpv');
	}

	public static function admin_menu() {
		global $menu;

		foreach ( $menu as $menu_item ) {
			if ( $menu_item[2] === 'wpv_general' ) {
				add_submenu_page( 'wpv_general', __('Twitter', 'wpv'), __('Twitter', 'wpv'), 'edit_theme_options', 'vamtam_twitter', array( __CLASS__, 'plugin_page' ) );
				return;
			}
		}
	}

	public static function plugin_page() {
		require 'templates/settings.php';
	}

	public static function transient_callback( $url, $getfield, $requestMethod ) {
		if ( ! class_exists( 'TwitterAPIExchange' ) ) {
			require_once 'lib/TwitterAPIExchange.php';
		}

		$twitter   = new TwitterAPIExchange( self::get_options() );
		$response  = $twitter->setGetfield($getfield)
		             ->buildOauth($url, $requestMethod)
		             ->performRequest();

		$cached = json_decode( $response );

		return $cached;
	}

	public static function user_timeline( $user = 'twitter', $limit = 5 ) {
		$transient = 'vtut-'.md5($user.$limit);

		$response = tlc_transient( $transient )
		    ->updates_with(
		    	array( __CLASS__, 'transient_callback' ),
		    	array(
		    		( in_array( 'https', stream_get_wrappers() ) ? 'https' : 'http' ) . '://api.twitter.com/1.1/statuses/user_timeline.json',
		    		'?count='.$limit.'&screen_name='.urlencode($user),
		    		'GET',
		    	)
		    )
		    ->expires_in( self::TRANSIENT_EXPIRATION )
		    ->get();

		$response = maybe_unserialize( $response );

		if ( ! is_array( $response ) ) {
			return array();
		}

		return $response;
	}

	public static function search( $search_string = '', $limit = 5 ) {
		$transient = 'vts-'.md5( json_encode( $search_string ) . $limit );

		$response = tlc_transient( $transient )
		    ->updates_with(
		    	array( __CLASS__, 'transient_callback' ),
		    	array(
		    		'https://api.twitter.com/1.1/search/tweets.json',
		    		'?count='.$limit.'&q='.urlencode($search_string),
		    		'GET',
		    	)
		    )
		    ->expires_in( self::TRANSIENT_EXPIRATION )
		    ->get();

		$response = maybe_unserialize( $response );

		if ( ! isset( $response->statuses ) || ! is_array( $response->statuses ) ) {
			return array();
		}

		return $response->statuses;
	}
}

new Vamtam_Twitter;