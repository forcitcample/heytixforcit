<?php
/**
 * Extends the base class with functions required for this plugin.
 *
 * @author Guenter Schoenmann
 */
if ( ! defined( 'ABSPATH' ) )  {  exit;  }   // Exit if accessed directly

/**
 * Handles recalculation of a saved order
 * 
 * 
 */
class WC_Order_Add_Fees extends WC_Order
{
	/**
	 * WC option variable for precision 
	 * @var int
	 */
	public $dp;
	
	/**
	 * WC option where to round tax
	 * 
	 * @var boolean
	 */
	public $round_at_subtotal;
	
	
	public function __construct( $id = '' ) 
	{
		parent::__construct( $id);
		
		$this->dp                = (int) get_option( 'woocommerce_price_num_decimals' );
		$this->round_at_subtotal = get_option( 'woocommerce_tax_round_at_subtotal' ) == 'yes';
	}
	
	/**
	 * Adds the given array of fees (structure must be equivalent to the fees given in cart)
	 * 
	 * @param array $cart_fees structure as defined in WC-Cart
	 */
	public function add_new_fees( array &$cart_fees )
	{
		if( version_compare( WC()->version, '2.2.0', '<' ) )
		{
			$this->add_new_fees_V21( $cart_fees );
			return;
		}
		
		foreach ( $cart_fees as $fee_key => $fee ) 
		{
			$item_id = $this->add_fee($fee);
			
				// Allow plugins to add order item meta
			do_action( 'woocommerce_add_order_fee_meta', $this->id, $item_id, $fee, $fee_key );
		}
	}
	
	/**
	 * For backwards compatibility only
	 * 
	 * @param array $cart_fees
	 */
	public function add_new_fees_V21( array &$cart_fees )
	{
		foreach ( $cart_fees as $fee_key => $fee ) 
		{
			$item_id = wc_add_order_item( $this->id, array(
												'order_item_name' 		=> $fee->name,
												'order_item_type' 		=> 'fee'
											) );

		 	if ( $fee->taxable )
			{
				
				wc_add_order_item_meta( $item_id, '_tax_class', $fee->tax_class );
				
				if( version_compare( WC()->version, '2.2.0', '>=' ) )
				{
					wc_add_order_item_meta( $item_id, '_line_tax_data', array( 'total' => $fee->tax_data ) );
				}			
			}
		 	else
			{
		 		wc_add_order_item_meta( $item_id, '_tax_class', '0' );
				if( version_compare( WC()->version, '2.2.0', '>=' ) )
				{
					wc_add_order_item_meta( $item_id, '_line_tax_data', '' );
				}
			}

		 	wc_add_order_item_meta( $item_id, '_line_total', wc_format_decimal( $fee->amount ) );
			wc_add_order_item_meta( $item_id, '_line_tax', wc_format_decimal( $fee->tax ) );
			
			// Allow plugins to add order item meta
			do_action( 'woocommerce_add_order_fee_meta', $this->id, $item_id, $fee, $fee_key );
		}
	}
	
	/**
	 * 
	 * @return string The payment gateway key for this order
	 */
	public function &get_payment_method()
	{
		$key = get_post_meta( $this->id, '_payment_method', true );
		return $key;
	}

	/**
	 * Updates the payment info for this order
	 * 
	 * @param type $payment_gateway_key
	 * @param type $payment_gateway_title
	 */
	public function update_payment_method( $payment_gateway_key, $payment_gateway_title )
	{
		update_post_meta( $this->id, '_payment_method', $payment_gateway_key );
		update_post_meta( $this->id, '_payment_method_title', $payment_gateway_title );
	}
	
	/**
	 * Recalculates the total values of the complete saved order. Does not recalculate the tax values of the entries.
	 *		- tax totals are recalculated, deleted and new inserted
	 *		- totals are recalculated
	 * 
	 */
	public function recalc_totals()
	{
		if( version_compare( WC()->version, '2.2.0', '<' ) )
		{
			$this->recalc_totals_V21();
			return;
		}
		
		return $this->calculate_totals();
	}
	

	/**
	 * For backwards compatibility only
	 * 
	 */
	public function recalc_totals_V21()
	{
		global $wpdb;
		
		// Set customer location to order location
		if ( $this->billing_country )
		{
			WC()->customer->set_country( $this->billing_country );
		}
		if ( $this->billing_state )
		{
			WC()->customer->set_state( $this->billing_state );
		}
		if ( $this->billing_postcode )
		{
			WC()->customer->set_postcode( $this->billing_postcode );
		}

			//	get info for taxes 
		$country = WC()->countries->get_base_country();
		$state = isset( WC()->countries->get_base_stat ) ? WC()->countries->get_base_state : '';
		$postcode = '';
		$city = '';
		
		$shipping_address = $this->get_shipping_address();
		$billing_address = $this->get_billing_address ();
		
		if( ! empty( $shipping_address ) )
		{
			$country = $this->shipping_country;
			$state = $this->shipping_state;
			$postcode = $this->shipping_postcode;
			$city = $this->shipping_city;
		}
		else if( ! empty( $billing_address ) )
		{
			$country = $this->billing_country;
			$state = $this->billing_state;
			$postcode = $this->billing_postcode;
			$city = $this->billing_city;
		}
		
		$line_item_total = $line_item_subtotal = (float) 0.0;
		$line_item_total_tax_sum = $line_item_subtotal_tax_sum = (float) 0.0;
		$line_item_total_tax = $line_item_subtotal_tax = array();
		
		$shipping_total = (float) 0.0;
		$order_discounts_before_tax = $order_discount_after_tax = (float) 0.0;
		$cart_discounts_before_tax = $cart_discount_after_tax = (float) 0.0;
		
		$shipping_tax = $discount_tax = array();
		$taxes = $shipping_taxes = array();
		
			//	Get all defined linetypes for this order
		$line_types_obj = $wpdb->get_results( $wpdb->prepare( 
					"SELECT DISTINCT order_item_type FROM {$wpdb->prefix}woocommerce_order_items 
					WHERE order_id = %d", $this->id
				), OBJECT );
		
		$line_types = array();			
		foreach ( $line_types_obj as $line_type ) 
		{
			$line_types[] = $line_type->order_item_type;
		}			
		
		$tax = new WC_Tax();		
			
		$items = $this->get_items( $line_types );
		foreach ( $items as $item_key => $item ) 
		{
			switch(strtolower( $item['type'] ) )
			{
				case 'line_item':				
					$line_item_subtotal += $item['line_subtotal'];
					$line_item_total += $item['line_total'];
					$line_item_subtotal_tax_sum += $item['line_subtotal_tax'];
					$line_item_total_tax_sum += $item['line_tax'];
					$line_tax = $item['line_tax'];
					break;
				case 'fee':
					$line_item_subtotal += $item['line_total'];		// '_order_discount' = $line_item_total - $line_item_subtotal  !!!!!
					$line_item_total += $item['line_total'];
					$line_tax = $item['line_tax'];
					break;
				case 'shipping':
					$shipping_total += $item['cost'];
					break;
				case 'tax':
					if( ! empty( $item['shipping_tax_amount'] ) )		//	values are not stored with shipment
					{
						$shipping_tax[ $item['rate_id'] ] = $item['shipping_tax_amount'];
					}
					break;
				case 'coupon':
					$coupon = new WC_Coupon( $item['name'] );
					if( in_array( $coupon->type, array( 'percent_product', 'fixed_product' ) ) )
					{
						if( $coupon->apply_before_tax() )
						{
							$order_discounts_before_tax += $item['discount_amount'];
						}
						else
						{
							$order_discount_after_tax += $item['discount_amount'];
						}
					}
					else
					{
						if( $coupon->apply_before_tax() )
						{
							$cart_discounts_before_tax += $item['discount_amount'];
						}
						else
						{
							$cart_discount_after_tax += $item['discount_amount'];
						}
					}
					break;
				default:
					break;
			}
			
				//	'line_item' and 'fee'
			if( isset( $item['tax_class'] ) )
			{
				$tax_rates = $tax->find_rates( array(
							'country' 	=> $country,
							'state' 	=> $state,
							'postcode' 	=> $postcode,
							'city'		=> $city,
							'tax_class' => $item['tax_class']
						) );
				
				$key = '';
				if(count( $tax_rates) > 0 )
				{
					$keys = array_keys( $tax_rates );
					$key = $keys[0];
				}

				$line_item_total_tax[$key] = $line_tax;
			}
				// Sum the taxes
			foreach ( array_keys( $taxes + $line_item_total_tax ) as $key )
			{
				 $taxes[ $key ] = ( isset( $line_item_total_tax[ $key ] ) ? $line_item_total_tax[ $key ] : 0 ) + ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 );
			}
			
			foreach ( array_keys( $shipping_taxes + $shipping_tax ) as $key )
			{
				 $shipping_taxes[ $key ] = ( isset( $shipping_tax[ $key ] ) ? $shipping_tax[ $key ] : 0 ) + ( isset( $shipping_taxes[ $key ] ) ? $shipping_taxes[ $key ] : 0 );
			}
			
			$line_item_total_tax = $shipping_tax = array();
		}
			
		$total_tax = array_sum( $taxes ) + array_sum( $shipping_taxes );
		
		
		if( $this->round_at_subtotal )
		{
			$line_item_total = round( $line_item_total, $this->dp );
			$shipping_total = round( $shipping_total, $this->dp );
			$total_tax = round( $total_tax, $this->dp );
			$cart_discounts_before_tax = round( $cart_discounts_before_tax, $this->dp );
			$order_discounts_before_tax = round( $order_discounts_before_tax, $this->dp );
			$order_discount_after_tax = round( $order_discount_after_tax, $this->dp );
			$cart_discount_after_tax = round( $cart_discount_after_tax, $this->dp );
		}
		
		$cart_discount = $cart_discounts_before_tax + $order_discounts_before_tax;
		$order_discount = $order_discount_after_tax + $cart_discount_after_tax;
		
		$total = round( $line_item_total + $shipping_total + $total_tax - $order_discount, $this->dp ) ;
		
			// Remove old tax rows
		$wpdb->query( $wpdb->prepare( 
				"DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta 
					WHERE order_item_id IN ( 
						SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items 
							WHERE order_id = %d AND order_item_type = 'tax' )", 
				$this->id ) );
						
		$wpdb->query( $wpdb->prepare( 
				"DELETE FROM {$wpdb->prefix}woocommerce_order_items 
					WHERE order_id = %d AND order_item_type = 'tax'", 
				$this->id) );

			
			// Get tax rates
		$rates = $wpdb->get_results( 
				"SELECT tax_rate_id, tax_rate_country, tax_rate_state, tax_rate_name, tax_rate_priority 
					FROM {$wpdb->prefix}woocommerce_tax_rates ORDER BY tax_rate_name" 
				);
		
		$tax_codes = array();

		foreach( $rates as $rate ) 
		{
			$code = array();

			$code[] = $rate->tax_rate_country;
			$code[] = $rate->tax_rate_state;
			$code[] = $rate->tax_rate_name ? sanitize_title( $rate->tax_rate_name ) : 'TAX';
			$code[] = absint( $rate->tax_rate_priority );

			$tax_codes[ $rate->tax_rate_id ] = strtoupper( implode( '-', array_filter( $code ) ) );
		}

		// Now merge to keep tax rows
		foreach ( array_keys( $taxes + $shipping_taxes ) as $key ) 
		{
		 	$item 							= array();
		 	$item['rate_id']			 	= $key;
			$item['name'] 					= isset( $tax_codes[ $key ] ) ? $tax_codes[ $key ] : '';
			$item['label'] 					= $tax->get_rate_label( $key );
			$item['compound'] 				= $tax->is_compound( $key ) ? 1 : 0;
			$item['tax_amount'] 			= wc_format_decimal( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 );
			$item['shipping_tax_amount'] 	= wc_format_decimal( isset( $shipping_taxes[ $key ] ) ? $shipping_taxes[ $key ] : 0 );

			if ( ! $item['label'] )
			{
				$item['label'] = WC()->countries->tax_or_vat();
			}

			// Add line item
		   	$item_id = wc_add_order_item( $this->id, array(
					'order_item_name' 		=> $item['name'],
					'order_item_type' 		=> 'tax'
				) );

		 	// Add line item meta
		 	if ( $item_id ) 
			{
		 		wc_add_order_item_meta( $item_id, 'rate_id', $item['rate_id'] );
		 		wc_add_order_item_meta( $item_id, 'label', $item['label'] );
			 	wc_add_order_item_meta( $item_id, 'compound', $item['compound'] );
			 	wc_add_order_item_meta( $item_id, 'tax_amount', $item['tax_amount'] );
			 	wc_add_order_item_meta( $item_id, 'shipping_tax_amount', $item['shipping_tax_amount'] );
		 	}
		}
		
		update_post_meta( $this->id, '_order_total', 		wc_format_decimal( $total) );
		update_post_meta( $this->id, '_order_tax',			wc_format_decimal( array_sum( $taxes) ) );
		update_post_meta( $this->id, '_order_shipping', 	wc_format_decimal( $shipping_total) );
		update_post_meta( $this->id, '_order_shipping_tax', wc_format_decimal( array_sum( $shipping_taxes) ) );
		update_post_meta( $this->id, '_order_discount', 	wc_format_decimal( $order_discount) );
		update_post_meta( $this->id, '_cart_discount', 		wc_format_decimal( $cart_discount) );
	}
}

