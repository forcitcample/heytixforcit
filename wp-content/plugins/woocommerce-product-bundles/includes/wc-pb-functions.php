<?php
/**
 * Product Bundles < 4.8.0 Compatibility Functions
 * @version 4.8.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wc_bundles_attribute_label( $arg ) {

	return wc_attribute_label( $arg );
}

function wc_bundles_attribute_order_by( $arg ) {

	return wc_attribute_orderby( $arg );
}

function wc_bundles_get_template( $file, $data, $empty, $path ) {

	return wc_get_template( $file, $data, $empty, $path );
}

function wc_bundles_get_product_terms( $product_id, $attribute_name, $args ) {

	if ( WC_PB_Core_Compatibility::is_wc_version_gte_2_3() ) {

		return wc_get_product_terms( $product_id, $attribute_name, $args );

	} else {

		$orderby = wc_attribute_orderby( sanitize_title( $attribute_name ) );

		switch ( $orderby ) {
			case 'name' :
				$args = array( 'orderby' => 'name', 'hide_empty' => false, 'menu_order' => false );
			break;
			case 'id' :
				$args = array( 'orderby' => 'id', 'order' => 'ASC', 'menu_order' => false );
			break;
			case 'menu_order' :
				$args = array( 'menu_order' => 'ASC' );
			break;
		}

		$terms = get_terms( sanitize_title( $attribute_name ), $args );

		return $terms;
	}
}

function wc_bundles_get_price_decimals() {
	return absint( get_option( 'woocommerce_price_num_decimals', 2 ) );
}
