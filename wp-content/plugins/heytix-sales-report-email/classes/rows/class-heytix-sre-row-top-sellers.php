<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Heytix_SRE_Row_Top_Sellers extends Heytix_SRE_Report_Row {

	/**
	 * The constructor
	 *
	 * @param $date_range
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function __construct( $date_range ) {
		parent::__construct( $date_range, 'top-sellers', __( 'Top Sellers', 'heytix-sales-report-email' ) );
	}

	/**
	 * Prepare the data
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function prepare() {

		// Create a Report Manager object
		$report_manager = new Heytix_SRE_Report_Manager( $this->get_date_range() );

		// Set the default order types
		$order_types = array( 'shop_order' );

		// wc_get_order_types() is a 2.2+ function
		if ( function_exists( 'wc_get_order_types' ) ) {
			$order_types = wc_get_order_types( 'order-count' );
		}

		// Get top sellers
		$top_sellers = $report_manager->get_order_report_data( array(
			'data'         => array(
				'_tribe_wooticket_for_event' => array(
					'type' 			  => 'meta',
					'name'			  => 'event_id'
				),
				'_product_id' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => '',
					'name'            => 'product_id'
				),
				'_qty'        => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => 'SUM',
					'name'            => 'order_item_qty'
				)
			),
			'order_by'     => 'order_item_qty DESC',
			'group_by'     => 'product_id',
			'limit'        => 12,
			'query_type'   => 'get_results',
			'filter_range' => true,
			'order_types'  => $order_types,
		) );

		$value = 'n/a';

		// Fill the $value var with products
		if ( count( $top_sellers ) > 0 ) {
			$value = '';
			foreach ( $top_sellers as $product ) {
				$value .= $product->order_item_qty . 'x : ' . get_the_title( $product->product_id ) . '<br/>';
			}
		}

		$this->set_value( $value );
	}

}