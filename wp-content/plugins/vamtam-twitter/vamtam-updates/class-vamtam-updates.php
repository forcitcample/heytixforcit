<?php

class Vamtam_Updates {
	private $options;
	private $api_url;

	public function __construct( $options ) {
		$this->options = $options;

		$this->api_url = isset( $options['api_url'] ) ? $options['api_url'] : 'http://updates.api.vamtam.com/0/envato/check';

		// delete_site_transient( 'update_plugins' );
		add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'check' ) );
	}

	public function check( $updates ) {
		global $wp_version;

		if ( !isset( $updates->checked ) || !isset($updates->checked[$this->options['main_file']]) )
			return $updates;

		$raw_response = wp_remote_post( $this->api_url, array(
				'body' => array(
					'slug' => $this->options['slug'],
					'main_file' => $this->options['main_file'],
					'version' => $updates->checked[$this->options['main_file']],
					'purchase_key' => isset( $this->options['key'] ) ? $this->options['key'] : apply_filters( 'wpv_purchase_code', '' )
				),
				'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url(),
			) );

		if ( is_wp_error( $raw_response ) || 200 != wp_remote_retrieve_response_code( $raw_response ) )
			return $updates;

		$response = json_decode( wp_remote_retrieve_body( $raw_response ), true );
		foreach ( $response['plugins'] as &$plugin ) {
			$plugin = (object) $plugin;
		}
		unset( $plugin );

		$updates->response = array_merge( $updates->response, $response['plugins'] );

		return $updates;
	}
}
