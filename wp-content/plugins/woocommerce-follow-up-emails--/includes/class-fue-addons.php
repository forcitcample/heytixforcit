<?php

/**
 * Class FUE_Addons
 */
class FUE_Addons {

    const ADDONS_JSON_URL       = 'http://www.75nineteen.com/add-ons.json';
    const TEMPLATES_JSON_URL    = 'http://www.75nineteen.com/templates.json';

    private $addons     = array();
    private $templates  = array();
    private $fue;

    /**
     * Class constructor
     * @param Follow_Up_Emails $fue
     */
    public function __construct( Follow_Up_Emails $fue ) {
        $this->fue = $fue;

        $this->addons       = json_decode( $this->get_addons_json() );
        $this->templates    = json_decode( $this->get_templates_json() );

    }

    /**
     * Get the available add-ons
     * @return array
     */
    public function get_addons() {
        return apply_filters( 'fue_addons', $this->addons );
    }

    /**
     * Get the available templates
     * @return array
     */
    public function get_templates() {
        return apply_filters( 'fue_templates', $this->templates );
    }

    /**
     * Get the JSON of add-ons meta from the cache or pull from the server
     * if no cached data exists
     *
     * @return string|WP_Error JSON-encoded string or a WP_Error object if an error occured
     */
    private function get_addons_json() {
        $addons = get_transient( 'fue_addons_json' );

        if ( !$addons ) {
            $addons = $this->get_json_from_server( self::ADDONS_JSON_URL );

            if ( !is_wp_error( $addons ) ) {
                // store in cache for 1 day
                set_transient( 'fue_addons_json', $addons, 86400 );
            }
        }

        return $addons;
    }

    /**
     * Get the JSON of templates meta from the cache or pull from the server
     * if no cached data exists
     *
     * @return string|WP_Error JSON-encoded string or a WP_Error object if an error occured
     */
    private function get_templates_json() {
        $templates = get_transient( 'fue_templates_json' );

        if ( !$templates ) {
            $templates = $this->get_json_from_server( self::TEMPLATES_JSON_URL );

            if ( !is_wp_error( $templates ) ) {
                // store in cache for 1 day
                set_transient( 'fue_templates_json', $templates, 86400 );
            }
        }

        return $templates;
    }

    /**
     * Download the JSON file and return the contents
     *
     * @param string $json_url
     * @return string|WP_Error
     */
    private function get_json_from_server( $json_url ) {
        $response = wp_remote_get( $json_url );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return $response['body'];
    }

}