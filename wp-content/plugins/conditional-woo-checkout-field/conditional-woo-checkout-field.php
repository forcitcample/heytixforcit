<?php
   /*
   Plugin Name: Conditional Woo Checkout Field
   Plugin URI: https://surpriseazwebservices.com/plugins/conditional-woo-checkout-field/
   Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=N2TYAV69U9CP4
   Description: Display a custom field at checkout in your WooCommerce store if a certain product is in the customer's cart.
   Version: 1.0.10
   Author: Scott DeLuzio
   Author URI: https://surpriseazwebservices.com
   License: GPL2
   Text Domain: conditional-woo-checkout-field
   */
   
	/*  Copyright 2014  Scott DeLuzio  (email : scott (at) surpriseazwebservices.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	*/
	
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'wc_fields_action_links' );

function wc_fields_action_links( $links ) {
   $links[] = '<a href="https://surpriseazwebservices.com/plugins/conditional-woo-checkout-field-pro/" target="_blank">Upgrade to PRO</a>';
   return $links;
}
/* Load Text Domain */

/*
Will be used at later time
function conditional_woo_checkout_field_plugin_init() {
	$plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain( 'conditional-woo-checkout-field', false, $plugin_dir );
}
add_action('plugins_loaded', 'conditional_woo_checkout_field_plugin_init');
*/

/* Settings Page */

// Hook for adding admin menus
add_action('admin_menu', 'oizuled_conditional_woo_checkout_fields');

// action function for above hook
function oizuled_conditional_woo_checkout_fields() {
    // Add a new submenu under WooCommerce:
    add_submenu_page( 'woocommerce', 'Conditional Field', 'Conditional Field', 'manage_options', 'wc-conditional-field', 'conditional_fields_page' );
}

add_action('admin_init', 'register_oizuled_conditional_fields_settings');
function activate_conditional_fields() {
	add_option('oizuled_conditional_fields_pid', '');
	add_option('oizuled_conditional_fields_title', '');
	add_option('oizuled_conditional_fields_type', 'text');
	add_option('oizuled_conditional_fields_label', '');
	add_option('oizuled_conditional_fields_placeholder', '');
	add_option('oizuled_conditional_fields_class', '');
	add_option('oizuled_conditional_fields_required', 'yes');
	add_option('oizuled_conditional_fields_requiredtext', '');
	add_option('oizuled_conditional_fields_options', '');
	add_option('oizuled_conditional_fields_addemail', 'yes');
	add_option('oizuled_conditional_fields_addinvoice', 'yes');
}

function deactive_conditional_fields() {
	delete_option('oizuled_conditional_fields_pid');
	delete_option('oizuled_conditional_fields_title');
	delete_option('oizuled_conditional_fields_type');
	delete_option('oizuled_conditional_fields_label');
	delete_option('oizuled_conditional_fields_placeholder');
	delete_option('oizuled_conditional_fields_class');
	delete_option('oizuled_conditional_fields_required');
	delete_option('oizuled_conditional_fields_requiredtext');
	delete_option('oizuled_conditional_fields_options');
	delete_option('oizuled_conditional_fields_addemail');
	delete_option('oizuled_conditional_fields_addinvoice');
}

register_activation_hook(__FILE__, 'activate_conditional_fields');
register_deactivation_hook(__FILE__, 'deactive_conditional_fields');

function register_oizuled_conditional_fields_settings() {
	register_setting( 'oizuled_conditional_fields_option-group', 'oizuled_conditional_fields_pid');
	register_setting( 'oizuled_conditional_fields_option-group', 'oizuled_conditional_fields_title');
	register_setting( 'oizuled_conditional_fields_option-group', 'oizuled_conditional_fields_type');
	register_setting( 'oizuled_conditional_fields_option-group', 'oizuled_conditional_fields_label');
	register_setting( 'oizuled_conditional_fields_option-group', 'oizuled_conditional_fields_placeholder');
	register_setting( 'oizuled_conditional_fields_option-group', 'oizuled_conditional_fields_class');
	register_setting( 'oizuled_conditional_fields_option-group', 'oizuled_conditional_fields_required');
	register_setting( 'oizuled_conditional_fields_option-group', 'oizuled_conditional_fields_requiredtext');
	register_setting( 'oizuled_conditional_fields_option-group', 'oizuled_conditional_fields_options');
	register_setting( 'oizuled_conditional_fields_option-group', 'oizuled_conditional_fields_addemail');
	register_setting( 'oizuled_conditional_fields_option-group', 'oizuled_conditional_fields_addinvoice');
}

/**
 * Display the page content for custom field settings
 **/
function conditional_fields_page() {
	include(WP_PLUGIN_DIR.'/conditional-woo-checkout-field/options.php');
}

/**
* Check to see what is in the cart
*
* @param $product_id
*
* @return bool
*/
function conditional_product_in_cart( $product_id ) {
    global $woocommerce;

    $check_in_cart = false;

    foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
        $_product = $values['data'];

        if ( $_product->id == $product_id ) {
            $check_in_cart = true;
        }       
    }
    return $check_in_cart;
}

/*
*  Set Custom Field
*  Hook into checkout form
*/
add_action( 'woocommerce_after_order_notes', 'conditional_checkout_field' );

function conditional_checkout_field( $checkout ) {

$pid = get_option('oizuled_conditional_fields_pid');

$check_in_cart = conditional_product_in_cart($pid);

if (get_option('oizuled_conditional_fields_type') == 'select') {
	$options = explode("\n", get_option('oizuled_conditional_fields_options'));
	$select = array();
	foreach ($options as $option) {
		$select[$option] = $option;
	}
}

$required = null;
if (get_option('oizuled_conditional_fields_required') == 'yes') {
	$required = true;
}
if (get_option('oizuled_conditional_fields_required') == 'no') {
	$required = false;
}
	
	// Check if the product is in the cart and show the custom fields if it is
	if ($check_in_cart == true ) {
		echo '<div id="conditional_checkout_field">';
		echo '<h3>'.get_option('oizuled_conditional_fields_title').'</h3>';
		woocommerce_form_field( 'conditional_field', array(
		'type'          => get_option('oizuled_conditional_fields_type'),
		'label'         => get_option('oizuled_conditional_fields_label'),
		'placeholder'   => (in_array(get_option('oizuled_conditional_fields_type'), array('text', 'textarea'))) ? get_option('oizuled_conditional_fields_placeholder') : null,
		'class'         => array(get_option('oizuled_conditional_fields_class')),
		'required'      => $required,
		'options'		=> (get_option('oizuled_conditional_fields_type') == 'select') ? $select : null
		), $checkout->get_value( 'conditional_field' ));

		echo '</div>';
	}
}

/**
 * Process the checkout
 */
add_action('woocommerce_checkout_process', 'conditional_checkout_field_process');
 
function conditional_checkout_field_process() {
	$pid = get_option('oizuled_conditional_fields_pid');
	$check_in_cart = conditional_product_in_cart($pid);
    // Check if the field is required and set, if not then show an error message.
    if ( $check_in_cart == true && get_option('oizuled_conditional_fields_required') == 'yes' && !$_POST['conditional_field'] ) {
			wc_add_notice( __( get_option('oizuled_conditional_fields_requiredtext') ), 'error' );
	}
}

/**
 * Update the order meta with custom field value
 */
add_action( 'woocommerce_checkout_update_order_meta', 'conditional_checkout_field_update_order_meta' );
 
function conditional_checkout_field_update_order_meta( $order_id ) {
    if ( !empty( $_POST['conditional_field'] ) ) {
        update_post_meta( $order_id, get_option('oizuled_conditional_fields_title'), sanitize_text_field( $_POST['conditional_field'] ) );
    }
}

/**
 * Add the field to order emails
 **/
add_action( 'woocommerce_email_after_order_table', 'conditional_order_meta_keys', 15, 2 );

function conditional_order_meta_keys($order) {
	if (get_option('oizuled_conditional_fields_addemail') == 'yes') {
		if (get_post_meta( $order->id, get_option('oizuled_conditional_fields_title'), true )) {
			echo '<br /><strong>' . get_option('oizuled_conditional_fields_title') . ':</strong><br />' . get_post_meta( $order->id, get_option('oizuled_conditional_fields_title'), true );
		}
	}
}

/**
 * Add the field to the order details
 **/
add_action( 'woocommerce_order_details_after_order_table', 'conditional_order_details_invoice' );
function conditional_order_details_invoice($order) {
	if (get_option('oizuled_conditional_fields_addinvoice') == 'yes') {
		echo "<p><strong>" . get_option('oizuled_conditional_fields_title') . ":</strong><br />" . get_post_meta( $order->id, get_option('oizuled_conditional_fields_title'), true ) . "</p>";
	}
}

/**
 * Display field value on the order edit page
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'name_display_admin_order_meta', 10, 1 );
 
function name_display_admin_order_meta($order){
echo '<ul>';
    if (get_post_meta( $order->id, get_option('oizuled_conditional_fields_title'), true )) {
		echo '<li><strong>' . get_option('oizuled_conditional_fields_title') . ':</strong><br />' . get_post_meta( $order->id, get_option('oizuled_conditional_fields_title'), true ) . '</li>';
	}
echo '</ul>';
}