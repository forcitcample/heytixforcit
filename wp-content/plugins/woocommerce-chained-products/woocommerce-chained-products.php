<?php
/*
Plugin Name: WooCommerce Chained Products
Plugin URI: http://www.storeapps.org/
Description: Easily create product bundles and combo packs and boost your sales.
Version: 2.2.3
Author: Store Apps
Author URI: http://www.storeapps.org/
Copyright (c) 2012, 2013, 2014, 2015 Store Apps All rights reserved.
*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once 'woo-includes/woo-functions.php';

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), 'cc6e246e495745db10f9f7fddc5aa907', '18687' );

/**
 * Registers a plugin function to be run when the plugin is activated or deactivated.
 */
register_activation_hook ( __FILE__, 'chained_product_activate' );
register_deactivation_hook ( __FILE__, 'chained_product_deactivate' );

/**
 * Function for database updation on deactivation of plugin
 * 
 * @global wpdb $wpdb WordPress Database Object
 * @global int $blog_id
 * @global SA_WC_Chained_Products $wc_chained_products Main instance of Chained Products
 */
function chained_product_deactivate() {
	
	global $wpdb, $blog_id, $wc_chained_products;
	
	//For multisite table prefix
	if ( is_multisite() ) {
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}", 0 );
	} else {
		$blog_ids = array( $blog_id );
	}
	
	foreach ( $blog_ids as $blog_id ) {

		$wpdb_obj       = clone $wpdb;
		$wpdb->blogid   = $blog_id;
		$wpdb->set_prefix( $wpdb->base_prefix );
							   
		$cp_results	= $wpdb->get_results( "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_chained_product_detail'", "ARRAY_A" );
		
		if( !empty( $cp_results ) ) {

			foreach ( $cp_results as $value ) {

				$cp_ids[] = $wc_chained_products->get_parent( $value['post_id'] ); 
			}

			$cp_results = array_unique( $cp_ids );
			$sc_results = $wpdb->get_results( "SELECT ID, post_content FROM {$wpdb->prefix}posts WHERE post_content LIKE '%[chained_products%]%'", "ARRAY_A" );
			$post_ids	= array_intersect( $cp_results, array_map( 'current', $sc_results) );
		
			foreach ( $post_ids as $post_id ) {
				
				foreach ( $sc_results as $post_data ) {
				
					if( $post_data['ID'] == $post_id ) {

						$shortcode_start = strpos( $post_data['post_content'], '[chained_products' );               
						if( $shortcode_start === false ) break;
						
						$shortcode_end = strpos( $post_data['post_content'], "]", $shortcode_start );
						if( $shortcode_end === false ) break;

						$shortcode_length	= $shortcode_end - $shortcode_start + 1;
						$shortcode_string	= substr( $post_data['post_content'], $shortcode_start, $shortcode_length );

						$shortcode[$post_id]['shortcode'] 	= $shortcode_string;
						$shortcode[$post_id]['content'] 	= str_replace( $shortcode_string, "", $post_data['post_content'] );

						break;

					}

				}

			}

			$cp_query_case = array();
			$sc_query_case = array();

			foreach( $shortcode as $id => $meta_value ){
				
				$cp_query_case[] = "WHEN " . $id  . " THEN '" . $wpdb->_real_escape( $meta_value['content'] ) . "'";
			  
			}

			$cp_query = " UPDATE {$wpdb->prefix}posts  
						SET post_content = CASE ID ". implode( "\n", $cp_query_case ) ." 
						END
						WHERE ID IN (". implode( ",", $post_ids ) .")                                                                      
						";

			$wpdb->query( $cp_query );
		  
			$sc_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";

						foreach ( $shortcode as $key => $value ) {
							$meta_value = addslashes( $value['shortcode'] );
							$sql_query_sel[] = "SELECT $key, '_chained_product_shortcode', '$meta_value'";
						}

			$sc_query.= implode(" UNION ALL ", $sql_query_sel);

			$wpdb->query( $sc_query );

		} //end if

		$wpdb = clone $wpdb_obj;

	} //end foreach

}

/**
 * Function for database updation on activation of plugin
 * 
 * @global wpdb $wpdb WordPress Database Object
 * @global int $blog_id
 */
function chained_product_activate() {

	global $wpdb, $blog_id;

	//For multisite table prefix
	if ( is_multisite() ) {
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}", 0 );
	} else {
		$blog_ids = array( $blog_id );
	}

	foreach ( $blog_ids as $blog_id ) {

		$wpdb_obj = clone $wpdb;
		$wpdb->blogid = $blog_id;
		$wpdb->set_prefix( $wpdb->base_prefix );
		
		if ( get_option( '_current_chained_product_db_version' ) === false ) {

			database_update_for_1_3();		
		} 

		if( get_option( '_current_chained_product_db_version' ) == "1.3" ) {
			
			database_update_for_1_3_8();
		}
		
		if( get_option( '_current_chained_product_db_version' ) == "1.3.8" ) {
			
			database_update_for_1_4();
		}

		if( get_option( '_current_chained_product_db_version' ) == "1.4" ) {
			
			database_update_after_1_3_8();
		}

		update_option( '_current_chained_product_db_version', "1.4" );

		$wpdb = clone $wpdb_obj;

	}

}

/**
 * Database updation after version 1.3 for quantity bundle feature
 * 
 * @global wpdb $wpdb WordPress Database Object
 * @global SA_WC_Chained_Products $wc_chained_products Main instance of Chained Products
 */
function database_update_for_1_3() {

	global $wpdb, $wc_chained_products;

	$old_results = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '_chained_product_ids'", "ARRAY_A" );

	if ( !empty( $old_results ) ) {

		foreach ( $old_results as $result ) {

			$chained_product_detail = array();

			foreach ( unserialize( $result['meta_value'] ) as $id ) {

				$product_title = $wc_chained_products->get_product_title( $id );

				if ( empty( $product_title ) ) continue;

				$chained_product_detail[$id] = array( 'unit' => 1,
														'product_name' => $product_title
													);

			}

			if ( empty( $chained_product_detail ) ) continue;

			//For variable product - update all variation according to parent product
			$variable_product = $wpdb->get_results( "SELECT ID FROM {$wpdb->prefix}posts WHERE post_parent = " . $result['post_id'] . " ;", 'ARRAY_A' );

			if ( empty( $variable_product ) ) {
				update_post_meta( $result['post_id'], '_chained_product_detail', $chained_product_detail );
			} else {
				foreach ( $variable_product as $value ) {
					update_post_meta( $value['ID'], '_chained_product_detail', $chained_product_detail );
				}
			}

		}

	}

	update_option( '_current_chained_product_db_version', '1.3' );

}

/**
 * Database updation to include shortcode in post_content when activated
 * 
 * @global wpdb $wpdb WordPress Database Object
 * @global SA_WC_Chained_Products $wc_chained_products Main instance of Chained Products
 */
function database_update_for_1_3_8() {
	
	global $wpdb, $wc_chained_products;

	$results 	= $wpdb->get_results( "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_chained_product_detail'", "ARRAY_A" );
	$post_ids	= array_map( 'current', $results );
	
	if( empty( $post_ids ) )
		return;

	foreach ( $post_ids as $post_id ) {

		$cp_ids[] = $wc_chained_products->get_parent( $post_id ); 
	}

	$post_ids 	= implode( ",", array_unique( $cp_ids) );

	$shortcode  = '<h3>' . __( 'Included Products', 'wc-chained-products' ) . '</h3><br />';
	$shortcode .= __( 'When you order this product, you get all the following products for free!!', 'wc-chained-products' );
	$shortcode .= '[chained_products]';

	$wpdb->query( "UPDATE {$wpdb->prefix}posts 
					SET post_content = concat( post_content , '$shortcode') 
					WHERE ID IN( $post_ids )"
				);

}

/**
 * Database updation to restore shortcode after version 1.3.8
 * 
 * @global wpdb $wpdb WordPress Database Object
 * @global SA_WC_Chained_Products $wc_chained_products Main instance of Chained Products
 */
function database_update_after_1_3_8() {

	global $wpdb, $wc_chained_products;

	$cp_results	= $wpdb->get_results( "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_chained_product_detail'", "ARRAY_A" );

	if( empty( $cp_results ) )
		return;
	
	foreach ( $cp_results as $value ) {

		$cp_ids[] = $wc_chained_products->get_parent( $value['post_id'] ); 
	}

	if ( !( is_array( $cp_ids ) && count( $cp_ids ) > 0 ) ) return;

	$cp_results = array_unique( $cp_ids );
	$sc_results	= $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '_chained_product_shortcode'", "ARRAY_A" );
	$post_ids	= array_intersect( $cp_results, array_map( 'current', $sc_results) );

	if( !empty( $post_ids ) ) {

		foreach ( $post_ids as $post_id ) {
			
			foreach ( $sc_results as $result ) {
				
				if( $result['post_id'] == $post_id ) {

					$shortcode[$post_id] = $result['meta_value'];				
					break;

				}

			}

		}

		$query_case = array();

		foreach( $shortcode as $id => $meta_value ){
			
			$query_case[] 	= "WHEN " . $id  . " THEN CONCAT( post_content, '" . $wpdb->_real_escape( $meta_value ) . "')";
		  
		}

		$shortcode_query = " UPDATE {$wpdb->prefix}posts  
							SET post_content = CASE ID ". implode( "\n", $query_case ) ." 
							END 
							WHERE ID IN ( ". implode( ",", $post_ids ) ." )	                                               
							";

		$wpdb->query( $shortcode_query );

	}	
	
	$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = '_chained_product_shortcode'" );

}

/**
 * Add chained product's parent's information in order containing chained products
 * 
 * @global wpdb $wpdb WordPress Database Object
 * @global SA_WC_Chained_Products $wc_chained_products Main instance of Chained Products
 */
function database_update_for_1_4() {

	global $wpdb, $wc_chained_products;

	$cp_results 	= $wpdb->get_results( "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_chained_product_detail'", "ARRAY_A" );
	$product_ids 	= array_map( 'current', $cp_results );
	$inserted 		= array();

	$order_items = $wpdb->get_results( "SELECT order_id, meta_value, order_items.order_item_id
										FROM {$wpdb->prefix}woocommerce_order_items AS order_items
										JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_itemmeta
										WHERE order_items.order_item_id = order_itemmeta.order_item_id
										AND meta_key IN ('_product_id', '_variation_id' )  
										AND meta_value", "ARRAY_A" 
										);

	if( empty( $order_items ) )
		return;

	foreach ( $order_items as $value )
		$order_unique_products[ $value['order_id'] ][ $value['order_item_id'] ] = $value['meta_value'];

	foreach ( $product_ids as $chained_parent_id ) {
		
		$chained_product_detail = $wc_chained_products->get_all_chained_product_details( $chained_parent_id );
		$chained_product_ids    = is_array( $chained_product_detail ) ? array_keys( $chained_product_detail ) : array();

		if( empty( $chained_product_ids ) )
			continue;

		$orders_contains_parent_product = array();
		foreach ( $order_unique_products as $order_id => $value ) {

			if( array_search( $chained_parent_id, $value ) !== false )
				$orders_contains_parent_product[] = $order_id; 
			
		}

		if( empty( $orders_contains_parent_product ) )
			continue;

		foreach ( $orders_contains_parent_product as $order_id ) {
			
			foreach ( $chained_product_ids as $chained_product_id ) {

				$order_item_id = array_search( $chained_product_id, $order_unique_products[$order_id] );

				if( empty( $order_item_id ) || array_search( $order_item_id, $inserted ) !== false )
					continue;

				$inserted[] = $order_item_id;

				$cp_meta_value = $wpdb->get_var( "SELECT meta_id
										FROM {$wpdb->prefix}woocommerce_order_itemmeta
										WHERE meta_key = '_chained_product_of'
										AND order_item_id = '$order_item_id'" );

				if( ! empty( $cp_meta_value ) )
					continue;

				$wpdb->query( "INSERT INTO {$wpdb->prefix}woocommerce_order_itemmeta
								VALUES ( NULL ,  '$order_item_id',  '_chained_product_of',  '$chained_parent_id')
								");

			}

		}

	}

	update_option( '_current_chained_product_db_version', '1.4' );
	
}

if ( is_woocommerce_active() ) {

	/**
	 * Localisation
	 **/
	load_plugin_textdomain( 'wc-chained-products', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		
	include_once 'classes/class-wc-compatibility.php';
	require 'classes/sa_wc_chained_products.class.php';

	global $wc_chained_products;
	$wc_chained_products = new SA_WC_Chained_Products();

	// Filter for validating cart based on availability of chained products
	add_filter( 'woocommerce_add_to_cart_validation', 'woocommerce_chained_add_to_cart_validation', 10, 3 );
	add_filter( 'woocommerce_update_cart_validation', 'woocommerce_chained_update_cart_validation', 10, 4 );

	// Action to add or remove actions & filter specific to chained products
	add_action( 'add_chained_products_actions_filters', 'add_chained_products_actions_filters' );
	add_action( 'remove_chained_products_actions_filters', 'remove_chained_products_actions_filters' );

	// Action for checking cart items including Chained products
	add_action( 'woocommerce_check_cart_items', 'woocommerce_chained_check_cart_items' );

	// Filter to hide "Add to cart" button if chained products are out of stock
	add_filter( 'woocommerce_get_availability', 'woocommerce_get_chained_products_availability', 10, 2 );

	// Action to add chained product to cart
	add_action( 'woocommerce_add_to_cart', 'add_chained_products_to_cart', 10, 6 );

	// Action for updating chained product quantity in cart
	add_action( 'woocommerce_after_cart_item_quantity_update', 'update_chained_product_quantity_in_cart', 1, 2 );
	add_action( 'woocommerce_before_cart_item_quantity_zero', 'update_chained_product_quantity_in_cart', 1, 2 );
	add_action( 'woocommerce_cart_updated', 'validate_and_update_chained_product_quantity_in_cart' );

	// Don't allow chained products to be removed or change quantity
	add_filter( 'woocommerce_cart_item_remove_link', 'chained_cart_item_remove_link', 10, 2 );
	add_filter( 'woocommerce_cart_item_quantity', 'chained_cart_item_quantity', 10, 2 );

	// Filter for getting cart item from session
	add_filter( 'woocommerce_get_cart_item_from_session', 'get_chained_cart_item_from_session', 10, 2 );

	// Filters for manage stock availability and max value of input args
	add_filter( 'woocommerce_get_availability', 'validate_stock_availability_of_chained_products', 10, 2 );
	add_filter( 'woocommerce_quantity_input_max', 'validate_stock_availability_of_chained_products', 10, 2 );
	add_filter( 'woocommerce_cart_item_data_max', 'validate_stock_availability_of_chained_products', 10, 2 );
	add_filter( 'woocommerce_quantity_input_args', 'validate_stock_availability_of_chained_products', 10, 2 );

	// Action for removing price of chained products before calculating totals
	add_action( 'woocommerce_before_calculate_totals', 'woocommerce_before_chained_calculate_totals' );

	// Chained product list on shop page
	add_action( 'woocommerce_before_add_to_cart_button', 'woocommerce_chained_products_for_variable_product' );
	add_action( 'wp_ajax_nopriv_get_chained_products_html_view', 'get_chained_products_html_view' );
	add_action( 'wp_ajax_get_chained_products_html_view', 'get_chained_products_html_view' );

	// Register Chained Products Shortcode
	add_action( 'init', 'register_chained_products_shortcodes' );

	/**
	 * Function for display chained products list for variable products 
	 * 
	 * @global object $woocommerce WooCommerce's main instance
	 * @global WC_Product $product WooCommerce product's instance
	 */
	function woocommerce_chained_products_for_variable_product() {

		global $woocommerce, $product;

		$is_chained_product_parent = false;
		if( !empty( $product->children ) ) {

			foreach ( $product->children as $chained_parent_id ) {
				
				$product_detail = get_post_meta( $chained_parent_id, '_chained_product_detail', true );
				if ( ! empty( $product_detail ) ) {
					$is_chained_product_parent = true;
					break;
				}

			}

		}

		if ( ! ( $product->product_type == 'simple' || $product->product_type == 'variable' ) || ( $product->product_type == 'variable' && !$is_chained_product_parent ) ) {
			return;
		}

		$js = " var variable_id = '';
				apply_css_property();
				if( jQuery('input[name=variation_id]').length > 0 ) {
					display_chained_products_in_description_tab();
				}

				jQuery('input[name=variation_id]').on('change', function() {
					
					display_chained_products_in_description_tab();

				});

				function display_chained_products_in_description_tab() {
					
					setTimeout( function() {
						if( variable_id == jQuery('input[name=variation_id]').val() ) {
							return;
						}
						variable_id 			= jQuery('input[name=variation_id]').val();
						var original_stock      = jQuery( 'div.single_variation p.stock' ).text();
						var form_data           = new Object;   
						form_data.variable_id   = variable_id;
						form_data.price         = jQuery( '#show_price' ).val();
						form_data.quantity      = jQuery( '#show_quantity' ).val();
						form_data.style         = jQuery( '#select_style' ).val();

						if( variable_id == undefined || variable_id == '' ) {
							jQuery( '.tab-included-products' ).html( '' );
							return;
						 } 

						jQuery( '.tab-included-products' ).html('<img src = \'". Chained_Products_WC_Compatibility::global_wc()->plugin_url(). "/assets/images/ajax-loader.gif\' />');
						jQuery( 'span.price, div.single_variation p.stock' ).css( 'visibility', 'hidden' );
						jQuery.ajax({     
							url: '". admin_url( 'admin-ajax.php' ). "',                   
							type: 'POST',
							data: {
								form_value: form_data,
								action: 'get_chained_products_html_view'
							},
							dataType: 'html',
							success:function( result ) {
									if( result ) {                          
											jQuery( '.tab-included-products' ).html( result ); 
											apply_css_property();
											
											if( result.lastIndexOf( '<stock' ) == -1 || result.lastIndexOf( '</stock>' ) == -1 ) {

												jQuery( 'div.single_variation p.stock' ).text( original_stock );

											} else {

												var max_quantity = result.substring( result.lastIndexOf( '<stock' ) + 30, result.lastIndexOf( '</stock>' ) );
												jQuery( 'div.single_variation p.stock' ).text( max_quantity + ' in stock' );                                                    
												jQuery( 'input[name=quantity]' ).attr( 'max', max_quantity );
												jQuery( 'input[name=quantity]' ).attr( 'data-max', max_quantity );

											}

									} else {	 

											jQuery( '.tab-included-products' ).html( '' );                                           
											jQuery( 'div.single_variation p.stock' ).text( original_stock );
									}                                 
								jQuery( 'span.price, div.single_variation p.stock' ).css( 'visibility', 'visible' );
							}
						});
						
					}, 0 ); //end setTimeout  
				}

				function apply_css_property() {

					jQuery( '.tab-included-products' ).find( 'ul.products li' ).addClass( 'product' ).css( 'border-bottom', 'initial' );
					jQuery( '.tab-included-products' ).find( 'h3' ).css( {'line-height': '1.64', 'text-transform': 'initial', 'letter-spacing': 'initial'} );
					jQuery( '.tab-included-products' ).find( 'ul.products li.product a span.onsale' ).css( 'display' , 'none' );
				}
			";

		Chained_Products_WC_Compatibility::enqueue_js( $js );
		
	}

	/**
	 * Function to add actions & filters specific to Chained Products
	 */
	function add_chained_products_actions_filters() {

		add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_after_shop_loop_chained_item' );
		add_filter( 'woocommerce_product_is_visible', 'woocommerce_chained_product_is_visible', 20, 2 );

	}

	/**
	 * Function to remove action & filters specific to Chained products
	 */
	function remove_chained_products_actions_filters() {

		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_after_shop_loop_chained_item' );
		remove_filter( 'woocommerce_product_is_visible', 'woocommerce_chained_product_is_visible', 20, 2 );

	}

	/**
	 * Function to show chained products which are only searchable
	 * 
	 * @global SA_WC_Chained_Products $wc_chained_products Main instance of Chained Products
	 * @param boolean $visible
	 * @param int $product_id
	 * @return boolean
	 */
	function woocommerce_chained_product_is_visible( $visible, $product_id ) {
		global $wc_chained_products;

		$product = Chained_Products_WC_Compatibility::get_product( $product_id );

		$parent_product_id  = $wc_chained_products->get_parent( $product_id );
		$is_chained_product = $wc_chained_products->is_chained_product( $parent_product_id );

		if ( $is_chained_product && ( $product->visibility == 'search' || $product->visibility == 'hidden' ) ) {
			return true;
		}
		return $visible;
	}

	/**
	 * Function for removing price of chained products before calculating totals
	 * 
	 * @param WC_Cart $cart_object
	 */
	function woocommerce_before_chained_calculate_totals( $cart_object ) {
		foreach ( $cart_object->cart_contents as $value ) {
			if ( isset( $value['chained_item_of'] ) && $value['chained_item_of'] != '' ) {
				$value['data']->price = 0;
			}
		}
	}

	/**
	 * Function for making chained product's price to zero
	 * 
	 * @param array $cart_item
	 * @param array $values
	 * @return array $cart_item
	 */
	function get_chained_cart_item_from_session( $cart_item, $values ) {
		if ( isset( $values['chained_item_of'] ) ) {
			$cart_item['chained_item_of'] = $values['chained_item_of'];
			$cart_item['data']->price = 0;
		}
		return $cart_item;
	}

	/**
	 * Function to validate & update chained product's qty in cart
	 */
	function validate_and_update_chained_product_quantity_in_cart() {

		$cart_contents = Chained_Products_WC_Compatibility::global_wc()->cart->cart_contents;

		//code for undo link
		if ( ! empty( $_GET['undo_item'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'woocommerce-cart' ) ) {

			$cart_item_key = $_GET['undo_item'];
			$product_id = $cart_contents [$cart_item_key]['product_id'];
			$variation_id = $cart_contents [$cart_item_key]['variation_id'];
			$quantity = $cart_contents [$cart_item_key]['quantity'];
			add_chained_products_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, array(), array() );
		}

		$cart_contents_modified = Chained_Products_WC_Compatibility::global_wc()->cart->cart_contents;
		
		foreach ( $cart_contents_modified as $key => $value ) {
		
			if ( isset( $value['chained_item_of'] ) && !isset( $cart_contents[ $value['chained_item_of'] ] ) ) {
				Chained_Products_WC_Compatibility::global_wc()->cart->set_quantity( $key, 0 );	
			}
				
		}
	}

	/**
	 * Function for updating chained product quantity in cart
	 * 
	 * @global SA_WC_Chained_Products $wc_chained_products Main instance of Chained Products
	 * @param string $cart_item_key
	 * @param int $quantity
	 */
	function update_chained_product_quantity_in_cart( $cart_item_key, $quantity = 0 ) {
		global $wc_chained_products;
		$cart_contents = Chained_Products_WC_Compatibility::global_wc()->cart->cart_contents;

		if ( isset( $cart_contents[ $cart_item_key ] ) && ! empty( $cart_contents[ $cart_item_key ] ) ) {

			if ( $cart_contents[ $cart_item_key ]['data'] instanceof WC_Product_Variation ) {
				$product_id = $cart_contents[ $cart_item_key ]['variation_id'];
			} else {
				$product_id = $cart_contents[ $cart_item_key ]['product_id'];
			}

			$quantity = ( $quantity <= 0 ) ? 0 : $cart_contents[ $cart_item_key ]['quantity'];

			foreach ( $cart_contents as $key => $value ) {
				if ( isset( $value['chained_item_of'] ) && $cart_item_key == $value['chained_item_of'] ) {

					if ( $cart_contents[ $key ]['data'] instanceof WC_Product_Variation ) {
						$parent_product_id  = $cart_contents[ $key ]['variation_id'];
					} else {
						$parent_product_id  = $cart_contents[ $key ]['product_id'];
					}
					$bundle_product_data    = $wc_chained_products->get_all_chained_product_details( $product_id );
					$chained_product_qty    = $bundle_product_data[$parent_product_id]['unit'] * $quantity;
					Chained_Products_WC_Compatibility::global_wc()->cart->set_quantity( $key, $chained_product_qty );
				}
			}
		}
	}

	/**
	 * Function for keeping chained products quantity same as parent product
	 * 
	 * @param int $quantity
	 * @param string $cart_item_key
	 * @return int $quantity
	 */
	function chained_cart_item_quantity( $quantity, $cart_item_key ) {
		
		if ( isset ( Chained_Products_WC_Compatibility::global_wc()->cart->cart_contents[ $cart_item_key ]['chained_item_of'] ) )
			return '<div class="quantity buttons_added">'. Chained_Products_WC_Compatibility::global_wc()->cart->cart_contents[ $cart_item_key ]['quantity'] .'</div>';
		return $quantity;
	}

	/**
	 * Function for removing delete link for chained products
	 * 
	 * @param string $link
	 * @param string $cart_item_key
	 * @return string $link
	 */
	function chained_cart_item_remove_link( $link, $cart_item_key ) {
		
		if ( isset ( Chained_Products_WC_Compatibility::global_wc()->cart->cart_contents[ $cart_item_key ]['chained_item_of'] ) )
			return '';
		return $link;
	}

	/**
	 * Function to add chained product to cart
	 * 
	 * @global SA_WC_Chained_Products $wc_chained_products Main instance of Chained Products
	 * @param string $cart_item_key
	 * @param int $product_id
	 * @param int $quantity
	 * @param int $variation_id
	 * @param array $variation
	 * @param array $cart_item_data
	 */
	function add_chained_products_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		global $wc_chained_products;

		$product_id             = empty( $variation_id ) ? $product_id : $variation_id;
		$chained_products_detail= $wc_chained_products->get_all_chained_product_details($product_id);

		if ( $chained_products_detail ) {

			remove_action( 'woocommerce_add_to_cart', 'add_chained_products_to_cart', 10, 6 );

			$validation_result  = are_chained_products_available( $product_id, $quantity );
			if ( $validation_result != null ) {
				return;
			}
			foreach ( $chained_products_detail as $chained_products_id => $chained_products_data ) {

				if ( wp_get_post_parent_id( $chained_products_id ) > 0 ) {
					$_product               = new WC_Product_Variation( $chained_products_id );
					$chained_variation_id   = $_product->variation_id;
					$chained_variation_data = $_product->variation_data;
				} else {
					$_product = Chained_Products_WC_Compatibility::get_product( $chained_products_id );
					$chained_variation_id = '';
					$chained_variation_data = '';
				}

				$args               = array( 'chained_item_of' => $cart_item_key );
				$chained_parent_id  = $wc_chained_products->get_parent( $chained_products_id );
				$cart_id            = Chained_Products_WC_Compatibility::global_wc()->cart->generate_cart_id( $chained_parent_id, $chained_variation_id, $chained_variation_data, $args );
				$key                = Chained_Products_WC_Compatibility::global_wc()->cart->find_product_in_cart( $cart_id );

				if ( empty( $key ) ) {
					Chained_Products_WC_Compatibility::global_wc()->cart->add_to_cart( $chained_parent_id, $quantity * $chained_products_data['unit'], $chained_variation_id, $chained_variation_data, $args );
				}
			}

			add_action( 'woocommerce_add_to_cart', 'add_chained_products_to_cart', 10, 6 );
		}
	}

	/**
	 * Function to hide "Add to cart" button if chained products are out of stock
	 * 
	 * @param boolean $availability
	 * @param WC_Product $_product
	 * @return boolean $availability
	 */
	function woocommerce_get_chained_products_availability( $availability, $_product ) {

		$product_id         = $_product instanceof WC_Product_Variation ? $_product->variation_id : $_product->id;
		$validation_result  = are_chained_products_available( $product_id );

		if ( $validation_result != null ) {
			$_product->manage_stock = 'no';
			$_product->stock_status = 'outofstock';
			$chained_availability = array();
			$chained_availability['availability'] = __( 'Out of stock', 'woocommerce' ) . ': ' . implode( ', ', $validation_result['product_titles'] ) . __( ' doesn\'t have sufficient quantity in stock.', 'woocommerce' );
			$chained_availability['class'] = 'out-of-stock';
			return $chained_availability;
		}
		return $availability;
	}

	/**
	 * Function to display available variation below Product's name on shop front
	 * 
	 * @global WC_Product $product
	 * @global array $variation_titles
	 * @global int $chained_parent_id
	 * @global SA_WC_Chained_Products $wc_chained_products Main instance of Chained Products
	 * @global array $chained_product_detail
	 * @global array $shortcode_attributes
	 */
	function woocommerce_after_shop_loop_chained_item() {
		global $product, $variation_titles, $chained_parent_id, $wc_chained_products, $chained_product_detail, $shortcode_attributes;

		if ( isset( $variation_titles[$product->id] ) ) {

			$chained_product_detail = isset( $chained_product_detail ) ? $chained_product_detail : $wc_chained_products->get_all_chained_product_details( $chained_parent_id );

			foreach ( $variation_titles[$product->id] as $product_id => $variation_data ) {
				
				echo $variation_data;

				if( isset( $shortcode_attributes['quantity'] ) && $shortcode_attributes['quantity'] == "yes" ) {
					echo ' ( &times; ' . $chained_product_detail[$product_id]['unit'] . ' )<br />';	
				}
				
			}
		}
	}

	/**
	 * Function set the max value of quantity input box based on stock availability of chained products
	 * 
	 * @global object $post
	 * @global SA_WC_Chained_Products $wc_chained_products Main instance of Chained Products
	 * @param int $stock
	 * @param WC_Product $_product
	 * @return int $stock
	 */
	function validate_stock_availability_of_chained_products( $stock, $_product = null ) {
		global $post, $wc_chained_products;

		$post_id = isset( $_product ) ? ( $_product instanceof WC_Product_Variation ? $_product->variation_id : $_product->id ) : $post->ID;
		$chained_product_instance = $wc_chained_products->get_product_instance( $post_id );

		if ( ( get_option( 'woocommerce_manage_stock' ) == 'yes' ) && ( get_post_meta( $post_id, '_chained_product_manage_stock', true ) == 'yes' ) && ( $chained_product_instance->is_in_stock() ) ) {
			$max_quantity = $chained_product_instance->get_stock_quantity();
			
			if( ! empty( $max_quantity ) ) {
				for ( $max_count = 1; $max_count < $max_quantity; $max_count++ ) {
					$validation_result = are_chained_products_available( $post_id, $max_count );
					if ( $validation_result != null ) {
						if ( isset( $stock['max_value']) ) {
							$stock['max_value'] = $max_count-1;
						} elseif ( isset ( $stock['availability']) ) {
							$stock['availability'] = ( $max_count-1 )." in stock";
						} else {
							$stock = $max_count-1;
						}
						return $stock;
					}
				}
			}
		}
		return $stock;
	}

	/**
	 * Function to display price of the chained products on shop page
	 * 
	 * @global WC_Product $product
	 * @global int $chained_parent_id
	 * @global SA_WC_Chained_Products $wc_chained_products Main instance of Chained Products
	 * @global array $shortcode_attributes
	 * @global array $chained_product_detail
	 */
	function woocommerce_template_chained_loop_quantity_and_price() {
		global $product, $chained_parent_id, $wc_chained_products, $shortcode_attributes, $chained_product_detail;
		
		if( $product->is_type( 'simple' ) && isset( $shortcode_attributes['quantity'] ) && $shortcode_attributes['quantity'] == 'yes' ) {
					
			$chained_product_detail = isset( $chained_product_detail ) ? $chained_product_detail : $wc_chained_products->get_all_chained_product_details( $chained_parent_id );
			echo ' ( &times; '. $chained_product_detail[$product->id]['unit'] . ' )<br />';                        
		}
		
		if( isset( $shortcode_attributes['price'] ) && $shortcode_attributes['price'] == 'yes' ) {                    
				
			$price = ''; 
			$price .= $product->get_price_html_from_text();
			$price .= $product->get_price_html_from_to( woocommerce_price( $product->get_price() ), '' );
			$price_html = apply_filters( 'woocommerce_free_price_html', $price, $product );
			echo '<span class="price">' . $price_html . '</span>';                        
		}
	}

	/**
	 * Function to check whether store has sufficient quantity of chained products
	 * 
	 * @global SA_WC_Chained_Products $wc_chained_products Main instance of Chained Products
	 * @global array $chained_product_detail
	 * @param int $product_id
	 * @param int $main_product_quantity
	 * @return mixed
	 */
	function are_chained_products_available( $product_id, $main_product_quantity = 1 ) {
		global $wc_chained_products, $chained_product_detail;

		if ( ( get_option( 'woocommerce_manage_stock' ) == 'yes' ) && ( get_post_meta( $product_id, '_chained_product_manage_stock', true ) == 'yes' ) ) {

			$chained_product_detail = isset( $chained_product_detail ) ? $chained_product_detail : $wc_chained_products->get_all_chained_product_details( $product_id );
			$chained_product_ids    = ( is_array( $chained_product_detail ) ) ? array_keys( $chained_product_detail ) : null;

			if ( $chained_product_ids != null ) {
				$validation_result = array();
				$product_titles = array();
				$chained_add_to_cart = 'yes';

				foreach ( $chained_product_ids as $chained_product_id ) {

					$chained_product_instance = $wc_chained_products->get_product_instance( $chained_product_id );

					if ( ! $chained_product_instance->is_in_stock() ||
							( $chained_product_instance->managing_stock() &&
							! $chained_product_instance->is_downloadable() &&
							! $chained_product_instance->is_virtual() &&
							$chained_product_instance->get_stock_quantity() < ( $main_product_quantity * $chained_product_detail[$chained_product_id]['unit'] )
							) 
					) {

						$product_titles[]       = '"' . $wc_chained_products->get_product_title( $chained_product_id ) . '"';
						$chained_add_to_cart    = 'no';
					}
				}
				if ( $chained_add_to_cart == 'no' ) {
					$validation_result['product_titles']            = $product_titles;
					$validation_result['chained_cart_validated']    = $chained_add_to_cart;
					return $validation_result;
				}
			}
		}
		return null;
	}

	/**
	 * Function to validate Add to cart based on stock quantity of chained products
	 * 
	 * @global object $woocommerce - Main instance of WooCommerce
	 * @global SA_WC_Chained_Products $wc_chained_products Main instance of Chained Products
	 * @param boolean $add_to_cart
	 * @param int $product_id
	 * @param int $main_product_quantity
	 * @return boolean
	 */
	function woocommerce_chained_add_to_cart_validation( $add_to_cart, $product_id, $main_product_quantity ) {
		global $woocommerce, $wc_chained_products;

		if ( isset( $_GET['order_again'] ) && is_user_logged_in() && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'woocommerce-order_again' ) ) {

			$order = Chained_Products_WC_Compatibility::get_order( absint( $_GET['order_again'] ) );
			foreach ( $order->get_items() as $item ) {
							
				if( $item['product_id'] == $product_id && isset( $item['chained_product_of'] ) ) {					
					return false;
				}
			}		
			return $add_to_cart;
		}

		$product_id = ( isset( $_REQUEST['variation_id'] ) && $_REQUEST['variation_id'] > 0 ) ? $_REQUEST['variation_id'] : $product_id;
		$validation_result = are_chained_products_available( $product_id, $main_product_quantity );
		if ( $validation_result != null ) {
			if ( Chained_Products_WC_Compatibility::is_wc_22() || Chained_Products_WC_Compatibility::is_wc_21() ) {
				wc_add_notice( sprintf(__('Can not add %1s to cart as %2s doesn\'t have sufficient quantity in stock.', 'wc-chained-products'), $wc_chained_products->get_product_title( $product_id ), implode( ', ', $validation_result['product_titles'] ) ), 'error' );
			} else {
				$woocommerce->add_error( sprintf(__('Can not add %1s to cart as %2s doesn\'t have sufficient quantity in stock.', 'wc-chained-products'), $wc_chained_products->get_product_title( $product_id ), implode( ', ', $validation_result['product_titles'] ) ) );
			}
			return false;
		}
		return $add_to_cart;
	}

	/**
	 * Function to validate updation of cart based on stock quantity of chained products
	 * 
	 * @global object $woocommerce - Main instance of WooCommerce
	 * @global SA_WC_Chained_Products $wc_chained_products Main instance of Chained Products
	 * @param boolean $update_cart
	 * @param string $cart_item_key
	 * @param array $cart_item
	 * @param int $main_product_quantity
	 * @return boolean $update_cart
	 */
	function woocommerce_chained_update_cart_validation( $update_cart, $cart_item_key, $cart_item, $main_product_quantity ) {
		global $woocommerce, $wc_chained_products;
		$product_id = ( isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] > 0 ) ? $cart_item['variation_id'] : $cart_item['product_id'];
		$validation_result = are_chained_products_available( $product_id, $main_product_quantity );
		if ( $validation_result != null ) {
			if ( Chained_Products_WC_Compatibility::is_wc_22() || Chained_Products_WC_Compatibility::is_wc_21() ) {
				wc_add_notice( sprintf(__('Can not increase quantity of %1s because %2s doesn\'t have sufficient quantity in stock.', 'wc-chained-products'), $wc_chained_products->get_product_title( $product_id ), implode( ', ', $validation_result['product_titles'] ) ), 'error' );
			} else {
				$woocommerce->add_error( sprintf(__('Can not increase quantity of %1s because %2s doesn\'t have sufficient quantity in stock.', 'wc-chained-products'), $wc_chained_products->get_product_title( $product_id ), implode( ', ', $validation_result['product_titles'] ) ) );
			}
			return false;
		}
		return $update_cart;
	}

	/**
	 * Function to validate cart when it is loaded
	 * 
	 * @global object $woocommerce - Main instance of WooCommerce
	 * @global SA_WC_Chained_Products $wc_chained_products Main instance of Chained Products
	 */
	function woocommerce_chained_check_cart_items() {
		global $woocommerce, $wc_chained_products;
		$message = array();

		$cart = Chained_Products_WC_Compatibility::global_wc()->cart;
		if ($cart instanceof WC_Cart ) {
			$cart_page_id = woocommerce_get_page_id('cart');
			foreach ( $cart->cart_contents as $cart_item_key => $cart_item_value ) {

				if ( isset( $cart_item_value['chained_item_of'] ) )
					continue;

				$product_id = ( isset( $cart_item_value['variation_id'] ) && $cart_item_value['variation_id'] > 0 ) ? $cart_item_value['variation_id'] : $cart_item_value['product_id'];
				$validation_result = are_chained_products_available( $product_id, $cart_item_value['quantity'] );

				if ( $validation_result != null ) {
					$message[] = sprintf(__('Can not add %1s to cart as %2s doesn\'t have sufficient quantity in stock.', 'wc-chained-products'), $wc_chained_products->get_product_title( $cart_item_value['product_id'] ), implode( ', ', $validation_result['product_titles'] ) );
					$cart->set_quantity( $cart_item_key, 0 );
					if ( $cart_page_id ) {
						wp_safe_redirect( apply_filters( 'woocommerce_get_cart_url', get_permalink( $cart_page_id ) ) );
					}
				}
			}
			if ( count( $message ) > 0 ) {
				if ( Chained_Products_WC_Compatibility::is_wc_22() || Chained_Products_WC_Compatibility::is_wc_21() ) {
					wc_add_notice( sprintf(__(implode( '. ', $message ), 'wc-chained-products') ), 'message' );
				} else {
					$woocommerce->add_message( sprintf(__(implode( '. ', $message ), 'wc-chained-products') ) );
				}
			}
		}
	}

	/**
	 * Function for adding Chained Products Shortcode
	 */
	function register_chained_products_shortcodes() {

		add_shortcode( 'chained_products', 'get_chained_products_html_view' );            
	}

	/**
	 * Function for Shortcode with included chained product detail and for Ajax response of chained product details in json encoded format 
	 * 
	 * @global object $post
	 * @global array $variation_titles
	 * @global int $chained_parent_id
	 * @global array $shortcode_attributes
	 * @global SA_WC_Chained_Products $wc_chained_products Main instance of Chained Products
	 * @param array $chained_attributes
	 * @return string $chained_product_content
	 */
	function get_chained_products_html_view( $chained_attributes ) {

		global $post, $variation_titles, $chained_parent_id, $shortcode_attributes, $wc_chained_products; 

		$chained_product_content = "";

		if( isset( $_POST['form_value']['variable_id'] ) && $_POST['form_value']['variable_id'] != null ) {

			$chained_parent_id 		= $_POST['form_value']['variable_id'];
			$shortcode_attributes 	= $_POST['form_value']; 

		} else {

			$chained_parent_id 					= $post->ID;                 
			$parent_product 					= Chained_Products_WC_Compatibility::get_product( $chained_parent_id );
			$shortcode_attributes['price']		= isset( $chained_attributes['price'] ) ? $chained_attributes['price'] : 'yes';
			$shortcode_attributes['quantity']	= isset( $chained_attributes['quantity'] ) ? $chained_attributes['quantity'] : 'yes';
			$shortcode_attributes['style'] 		= isset( $chained_attributes['style'] ) ? $chained_attributes['style'] : 'grid';

			$chained_product_content .= '<input type = "hidden" id = "show_price" value = "'. $shortcode_attributes['price'] .'"/>';
			$chained_product_content .= '<input type = "hidden" id = "show_quantity" value = "'. $shortcode_attributes['quantity'] .'"/>';
			$chained_product_content .= '<input type = "hidden" id = "select_style" value = "'. $shortcode_attributes['style'] .'"/>';            
			$chained_product_content .= '<div class = "tab-included-products">';
			$chained_product_content .= ( $parent_product->product_type == 'variable' ) ? '</div>' : '';

		}

		$total_chained_details  = $wc_chained_products->get_all_chained_product_details( $chained_parent_id );
		$chained_product_ids    = is_array( $total_chained_details ) ? array_keys( $total_chained_details ) : null;

		if ( $chained_product_ids ) {

			$chained_product_instance = $wc_chained_products->get_product_instance( $chained_parent_id );
			if ( ( get_option( 'woocommerce_manage_stock' ) == 'yes' ) && ( get_post_meta( $chained_parent_id, '_chained_product_manage_stock', true ) == 'yes' ) && ( $chained_product_instance->is_in_stock() ) ) {

				$max_quantity = $chained_product_instance->get_stock_quantity();

				if( ! empty( $max_quantity ) ) {

					for( $max_count = 1; $max_count < $max_quantity; $max_count++ ) {

						$validation_result = are_chained_products_available( $chained_parent_id, $max_count );  

						if ( $validation_result != null ) {                        
								break;
						}                    

					}

				}       
		 
				$chained_product_content .= empty ( $max_quantity ) ? '' : '<stock style = "display:none">'. ( $max_count-1 ) .'</stock>';
				
			}

			// For list/grid view of included product
			if( isset( $shortcode_attributes['style'] ) && $shortcode_attributes['style'] == 'list' ) {

				$chained_product_content .= "<ul>";

				foreach ( $total_chained_details as $id => $product_data ) {

					$product = Chained_Products_WC_Compatibility::get_product( $id );

					$price = $product->get_price_html_from_to( woocommerce_price( $product->get_price() ), '' );
					$price_html = apply_filters( 'woocommerce_free_price_html', $price, $product );

					$product_id = ( $product instanceof WC_Product_Simple ) ? $product->id : $product->parent->id;
					
					$chained_product_content .= "<li><a href='" . get_permalink( $product_id ) . "' style='text-decoration: none;'>" . $product_data['product_name'];
					$chained_product_content .= ( isset( $shortcode_attributes['quantity'] ) && $shortcode_attributes['quantity'] == 'yes' ) ? " ( &times; ". $product_data['unit'] . " )" : "";
					$chained_product_content .= ( isset( $shortcode_attributes['price'] ) && $shortcode_attributes['price'] == 'yes' ) ? " <span class='price'>". $price_html ."</span>" : "";
					$chained_product_content .= "</a></li>";

				}

				$chained_product_content .= "</ul>";

			} elseif( isset( $shortcode_attributes['style'] ) && $shortcode_attributes['style'] == 'grid' ) {

				$atts = array();
				$product_ids = array();
				$variation_titles = array();

				foreach ( $chained_product_ids as $chained_product_id ) {

					$parent_id = wp_get_post_parent_id ( $chained_product_id );

					if ( $parent_id > 0 ) {
						$product_ids[] = $parent_id;
						$_product = new WC_Product_Variation( $chained_product_id );

						if ( isset( $_product->variation_data ) ) {
							$variation_titles[$parent_id][$chained_product_id] = ' ( ' . woocommerce_get_formatted_variation( $_product->variation_data, true ) . ' )';
						}

					} else {
						$product_ids[] = $chained_product_id;
					}

				}                       

				$atts['ids'] = implode( ',', $product_ids );                                  

				if ( empty( $atts ) ) return;

				$orderby_value = apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );

				// Get order + orderby args from string
				$orderby_value = explode( '-', $orderby_value );
				$orderby       = esc_attr( $orderby_value[0] );
				$order         = ! empty( $orderby_value[1] ) ? $orderby_value[1] : 'asc';

				extract( shortcode_atts( array( 'orderby'   => strtolower( $orderby ),
												'order'     => strtoupper( $order )
												),
										$atts ) );

				$args = array( 'post_type'	=> array( 'product' ),
								'orderby'       => $orderby,
								'order'         => $order,
								'posts_per_page'=> -1
								);

				if( isset( $atts['ids'] ) ){
						$ids = explode( ',', $atts['ids'] );
						$ids = array_map( 'trim', $ids );
						$args['post__in'] = $ids;
				}                                       

				ob_start();

				remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
				remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
				
				// For adding all visibility related actions & filters that are specific to Chained Products
				do_action( 'add_chained_products_actions_filters' );
				add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_chained_loop_quantity_and_price', 10, 1 );                      

				if ( version_compare( WOOCOMMERCE_VERSION, '1.6', '<' ) ) {

					query_posts( $args );
					woocommerce_get_template_part( 'loop', 'shop' );			// Depricated since version 1.6

				} else {

					$products = new WP_Query( $args ); 

					if ( $products->have_posts() ) {           

						while ( $products->have_posts() ) {                                                       
								 $products->the_post();
								 woocommerce_get_template_part( 'content', 'product' ); 
						}

						$chained_product_content .= '<ul class="products">'. ob_get_clean() .'</ul>';
																	 
					}

				}

				remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_chained_loop_quantity_and_price', 10 );
				
				// For removing all visibility related actions & filters that are specific to Chained Products
				do_action( 'remove_chained_products_actions_filters' );
				add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
				add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

				wp_reset_query();

			}	

		}

		// To prevent return 0 by wordpress ajax response
		if( isset( $_POST['form_value']['variable_id'] ) && $_POST['form_value']['variable_id'] != null ) { 

			echo $chained_product_content;
			exit();

		}
		$chained_product_content .= ( $parent_product->product_type == 'simple' ) ? '</div>' : '';
		return $chained_product_content;

	}// function end

}
?>