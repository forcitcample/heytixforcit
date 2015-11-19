<?php
/**
 * WC Core fix: WC requires session since 2.2.
 * 
 * Create default customer data like in version 2.1.12
 *
 * @author Guenter Schoenmann
 */
if ( ! defined( 'ABSPATH' ) )  {  exit;  }   // Exit if accessed directly

class WC_Customer_Add_Fees extends WC_Customer
{
	public function __construct() 
	{
		if( empty( WC()->session ) ) 
		{
			$default = apply_filters( 'woocommerce_customer_default_location', get_option( 'woocommerce_default_country' ) );

        	if ( strstr( $default, ':' ) ) 
			{
        		list( $country, $state ) = explode( ':', $default );
        	} 
			else 
			{
        		$country = $default;
        		$state   = '';
        	}

			$this->_data = array(
				'country' 				=> esc_html( $country ),
				'state' 				=> '',
				'postcode' 				=> '',
				'city'					=> '',
				'address' 				=> '',
				'address_2' 			=> '',
				'shipping_country' 		=> esc_html( $country ),
				'shipping_state' 		=> '',
				'shipping_postcode' 	=> '',
				'shipping_city'			=> '',
				'shipping_address'		=> '',
				'shipping_address_2'	=> '',
				'is_vat_exempt' 		=> false,
				'calculated_shipping'	=> false
			);
		} 
		else 
		{
			parent::__construct();
		}
	}
}
