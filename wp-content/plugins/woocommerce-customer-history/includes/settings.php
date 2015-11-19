<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WCCH_Settings extends WC_Integration {

	public function __construct() {
		$this->id = 'wcch';
		$this->method_title = __( 'Customer History', 'woocommerce-customer-history' );
		$this->method_description = __( 'Automatically tracks customer browsing history prior to purchase.', 'woocommerce-customer-history' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		add_action( 'woocommerce_update_options_integration_wcch', array( $this, 'process_admin_options') );
	}

		/**
	 * Initialise Settings Form Fields
	 *
	 * @access public
	 * @return void
	 */
	function init_form_fields() {

		$this->form_fields = array(
			'wcch_admin_email_enabled' => array(
				'label' 			=> __( 'Include customer history in admin email notifications.', 'wcch' ),
				'type' 				=> 'checkbox',
				'default' 			=> 'no'
			)
		);

    } // End init_form_fields()

}

function wcch_register_woocommerce_integration( $integrations ) {
	$integrations[] = 'WCCH_Settings';
	return $integrations;
}
add_filter( 'woocommerce_integrations', 'wcch_register_woocommerce_integration' );
