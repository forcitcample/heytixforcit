<?php

class Vamtam_Updates_2 {
	private $slug;
	private $main_file;
	private $full_path;

	private $api_url;

	public function __construct( $file ) {
		$this->slug      = basename( dirname( $file ) );
		$this->main_file = trailingslashit( $this->slug ) . basename( $file );
		$this->full_path = $file;

		$this->api_url = 'http://updates.api.vamtam.com/0/envato/check';

		// delete_site_transient( 'update_plugins' );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check' ) );
		add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );
	}

	public function check( $updates ) {
		$response = $this->api_request();

		if ( false === $response ) {
			return $updates;
		}

		if ( ! isset( $updates->response ) ) {
			$updates->response = array();
		}

		$updates->response = array_merge( $updates->response, $response );

		// Small trick to ensure the updates get shown in the network admin
		if( is_multisite() && ! is_main_site() ) {
			global $current_site;

			switch_to_blog( $current_site->blog_id );
			set_site_transient( 'update_plugins', $updates );
			restore_current_blog();
		}

		return $updates;
	}

	public function plugins_api( $data, $action = '', $args = null ) {
		if ( 'plugin_information' !== $action ) {
			return $data;
		}

		if ( ! isset( $args->slug ) || ( $args->slug !== $this->slug ) ) {
			return $data;
		}

		$data = new stdClass;

		return $data;
	}

	private function api_request() {
		global $wp_version;

		$update_cache = get_site_transient( 'update_plugins' );

		$plugin_data = get_plugin_data( $this->full_path );

		$raw_response = wp_remote_post( $this->api_url, array(
			'body' => array(
				'slug' => $this->slug,
				'main_file' => $this->main_file,
				'version' => $plugin_data[ 'Version' ],
				'purchase_key' => apply_filters( 'wpv_purchase_code', '' )
			),
			'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url(),
		) );

		if ( is_wp_error( $raw_response ) || 200 !== wp_remote_retrieve_response_code( $raw_response ) ) {
			return false;
		}

		$response = json_decode( wp_remote_retrieve_body( $raw_response ), true );
		foreach ( $response['plugins'] as &$plugin ) {
			$plugin = (object) $plugin;
		}
		unset( $plugin );

		return $response['plugins'];
	}
}
