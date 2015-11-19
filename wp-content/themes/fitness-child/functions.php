<?php

add_action('init', 'remove_x_frame_options');
function remove_x_frame_options() {
    // removing this so we can embed checkout in a facebook tab.
    remove_action( 'template_redirect', 'wc_send_frame_options_header' );
}

if(!function_exists('mysql_real_escape_string')) {
    function mysql_real_escape_string($string) {
        return \mysqli::real_escape_string($string);
    }
} else {
    error_log('Using internal PHP mysql_real_escape_string function, UserPro will cease to work.  Please disable the mysql extension in PHP.');
}

/**
 * Creating Matter Functions for HeyTix Child-Theme
 *
 */
include_once get_stylesheet_directory() . '/inc/events-metabox-extended.php';
include_once get_stylesheet_directory() . '/inc/shortcode-social-connect.php';
include_once get_stylesheet_directory() . '/inc/guest-list-ticket.php';
add_action('template_redirect', 'ht_tribe_events_integration', 20);
function ht_tribe_events_integration(){
    remove_action( 'wpv_inside_main', 'wpv_tribe_media' );
}

add_filter('tribe_datetime_format', 'ht_date_foramt');
function ht_date_foramt($format){
    return str_replace(' at ', ' \a\t ', $format);
}

add_action( 'wp_enqueue_scripts', 'enqueue_child_theme_styles', PHP_INT_MAX);
function enqueue_child_theme_styles() {
    wp_enqueue_style( 'font-awesome', get_stylesheet_directory_uri().'/font-awesome-4.3.0/css/font-awesome.min.css', array(),'4.3.0');
    wp_enqueue_style( 'parent-style', get_template_directory_uri().'/style.css' );
    wp_enqueue_style( 'child-style', get_stylesheet_uri(), array('parent-style')  );
}

// Add Service Fee to each ticket
function ht_wooticket_service_fee($wc_cart) {
    //Ticket product category ID
    $ticket_cat = 7;
    //Service Fee
    $service_fees = 0;
    $service_fee_per_ticket = 3.95;
    //Itereate through each item
    foreach ($wc_cart->cart_contents as $product ) {
        // Get the the product categories for the product
        $product_cats = get_the_terms( $product['product_id'], 'product_cat' );
        //Because a product can have multiple categories, we need to iterate through the list of the products category for a match
        if(has_term($ticket_cat, 'product_cat', $product['product_id'])){
            $service_fees += $service_fee_per_ticket * $product['quantity'];
        }
    }
    //If there is any service fees for tickets then add it to cart.
    if($service_fees){
        $wc_cart->add_fee('Service Fee', $service_fees, false, '');
    }
}
add_action( 'woocommerce_cart_calculate_fees', 'ht_wooticket_service_fee' );

//Check if the cart contains one of the particualar products
function ht_woocart_has_conditional_product($proudct_ids=array()){
    if(empty($proudct_ids))
        return false;
    //Itereate trhour each item ins cart
    foreach (WC()->cart->get_cart() as $product ) {
        if(isset($product['product_id']) && in_array($product['product_id'], $proudct_ids) ){
            return true;
        }
    }
    return false;
}
//Add Conditinal fields to selected product
function ht_woo_conditinal_fields(){
    error_log('adding host drop down');
    //Check if conditinal products are in cart
    if(!ht_woocart_has_conditional_product(array(12406, 14602, 17997))){
        return '';
    }
    //Get woo checkout object
    $checkout = WC_Checkout::instance();
    $host_args = array(
        'type'              => 'select',
        'label'             => 'Host',
        'placeholder'       => 'Select Your Host',
        'required'          => true,
        'class'             => array('form-row', 'form-row-first'),
        'options'           => array(
            0                   => 'Select Your Host',
            'Aaron Magnifico'   => 'Aaron Magnifico',
            'Adrian Hardy'      => 'Adrian Hardy',
            'David Mai'         => 'David Mai',
            'Dan Spadaro'       => 'Dan Spadaro',
            'Hunter Gambale'    => 'Hunter Gambale',
            'Joe Crea'          => 'Joe Crea',
            'Jorge Vergara'     => 'Jorge Vergara',
            'Ryan Featherman'   => 'Ryan Featherman',
            'Marielle Roselli'  => 'Marielle Roselli',
            'Manny Romano'      => 'Manny Romano',
            'Zach Seidman'      => 'Zach Seidman'
        )
    );
    $group_size_args = array(
        'type'              => 'select',
        'label'             => 'Group Size',
        'placeholder'       => 'Select Group Size',
        'required'          => true,
        'class'             => array('form-row', 'form-row-last'),
        'options'           => array('Select Group Size','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20')
    );
    ?>
    <div class="woocommerce-conditional-fields">
        <?php woocommerce_form_field('htcf_host', $host_args, $checkout->get_value( 'htcf_host' )) ?>
        <?php woocommerce_form_field('htcf_group_size', $group_size_args, $checkout->get_value( 'htcf_host' )) ?>
        <div class="clear"></div>
    </div>
<?php
}
add_action('woocommerce_checkout_billing', 'ht_woo_conditinal_fields', 30);

//Validate conditinal data
function ht_woo_validate_condiation_fields($posted_data){
    //Check if conditinal products are in cart
    if(!ht_woocart_has_conditional_product(array(12406))){
        return '';
    }
    //Check if host is selected
    if(empty($_POST['htcf_host'])){
        wc_add_notice( '<strong>Host</strong> ' . __( 'is a required field.', 'woocommerce' ), 'error' );
    }
    //Check if group size is selected
    if(empty($_POST['htcf_group_size'])){
        wc_add_notice( '<strong>Group Size</strong> ' . __( 'is a required field.', 'woocommerce' ), 'error' );
    }
}
add_action('woocommerce_after_checkout_validation', 'ht_woo_validate_condiation_fields');

//Save conditional fields value to order data
function ht_woo_save_conditional_fields_value($order_id){
    //Check if conditinal products are in cart
    if(!ht_woocart_has_conditional_product(array(12406))){
        return '';
    }
    update_post_meta($order_id, '_htcf_has_conditional_fields', true);
    //save host
    if(!empty($_POST['htcf_host'])){
        update_post_meta( $order_id,'htcf_host',  $_POST['htcf_host']);
    }else{
        delete_post_meta($order_id, 'htcf_host');
    }
    //save group size
    if(!empty($_POST['htcf_group_size'])){
        update_post_meta( $order_id,'htcf_group_size',  $_POST['htcf_group_size']);
    }else{
        delete_post_meta($order_id, 'htcf_group_size');
    }
}
add_action('woocommerce_checkout_order_processed', 'ht_woo_save_conditional_fields_value');

//Add conditional fields to order page
function ht_woo_conditional_fields_order_page($order){
    $has_cdf = get_post_meta($order->id, '_htcf_has_conditional_fields', true);
    if(!$has_cdf){
        return;
    }
    $htcf_host          = get_post_meta($order->id, 'htcf_host', true);
    $htcf_group_size    = get_post_meta($order->id, 'htcf_group_size', true);
    ?>
    <table class="conditional-fields">
        <tr><th>Host</th><td><?php echo $htcf_host; ?></td></tr>
        <tr><th>Group Size</th><td><?php echo $htcf_group_size; ?></td></tr>
    </table>

<?php
}
add_action( 'woocommerce_order_details_after_order_table', 'ht_woo_conditional_fields_order_page' );

//Display conditional fields to admin order edit page
function ht_woo_conditional_fields_admin_order_page($order){
    $has_cdf = get_post_meta($order->id, '_htcf_has_conditional_fields', true);
    if(!$has_cdf){
        return;
    }
    $htcf_host          = get_post_meta($order->id, 'htcf_host', true);
    $htcf_group_size    = get_post_meta($order->id, 'htcf_group_size', true);
    ?>
    <p class="form-field form-field-wide"><strong>Host:</strong> <?php echo $htcf_host; ?></p>
    <p class="form-field form-field-wide"><strong>Group Size:</strong> <?php echo $htcf_group_size; ?></p>
<?php
}
add_action( 'woocommerce_admin_order_data_after_order_details', 'ht_woo_conditional_fields_admin_order_page', 10, 1 );

//Add conditional fields to order email
function ht_woo_conditional_fields_to_eamil($metas){
    $metas['Host'] = 'htcf_host';
    $metas['Group Size'] = 'htcf_group_size';
    return $metas;
}
add_filter( 'woocommerce_email_order_meta_keys', 'ht_woo_conditional_fields_to_eamil', 20 );



add_theme_support( 'post-thumbnails', array( 'tribe_events' ) );
add_filter( 'tribe_events_admin_show_cost_field', '__return_true', 20 );

function my_wootickets_tribe_get_cost( $cost, $postId, $withCurrencySymbol ) {
    if ( empty($cost) && class_exists('TribeWooTickets') ) {
        // see if the event has tickets associated with it
        $wootickets = TribeWooTickets::get_instance();
        $ticket_ids = $wootickets->get_Tickets_ids( $postId );
        if ( empty($ticket_ids) ) {
            return '';
        }

        // see if any tickets remain, and what price range they have
        $max_price = 0;
        $min_price = 0;
        $sold_out = TRUE;
        foreach ( $ticket_ids as $ticket_id ) {
            $ticket = $wootickets->get_ticket($postId, $ticket_id);
            if ( $ticket->stock ) {
                $sold_out = FALSE;
                $price = $ticket->price;
                if ( $price > $max_price ) {
                    $max_price = $price;
                }
                if ( empty($min_price) || $price < $min_price ) {
                    $min_price = $price;
                }
            }
        }
        if ( $sold_out ) { // all of the tickets are sold out
            return __('Sold Out');
        }
        if ( empty($max_price) ) { // none of the tickets costs anything
            return __('Free');
        }

        // make a string showing the price (or range, if applicable)
        $currency = tribe_get_option( 'defaultCurrencySymbol', '$' );
        if ( empty($min_price) || $min_price == $max_price ) {
            return $currency . $max_price;
        }
        return $currency . $min_price . ' - ' . $currency . $max_price;
    }
    return $cost; // return the default, if nothing above returned
}

add_filter( 'tribe_get_cost', 'my_wootickets_tribe_get_cost', 10, 3 );

add_action('after_setup_theme', 'remove_admin_bar');

function remove_admin_bar() {
    if (!current_user_can('administrator') && !is_admin()) {
        show_admin_bar(false);
    }
}
/**
 * Tribe Event: Single Event Template redising
 */

add_action( 'tribe_events_single_event_before_the_content', 'htmh_remove_integration' );
remove_action( 'tribe_events_after_loop', 'wootix_list_view_ticket_form' );

add_action('tribe_events_single_meta_before', 'htmh_secured_with_gmo');
add_action('tribe_events_single_meta_before', 'htmh_events_featured_image');
add_action( 'tribe_events_single_event_after_the_meta', 'ht_wootix_remove_form', 1 );
//add_action( 'tribe_events_single_event_before_the_content', 'wootix_list_view_ticket_form' );
add_action( 'tribe_events_single_event_after_the_content', 'htmh_events_tabs_content' );


function htmh_events_tabs_content(){
    include get_stylesheet_directory() . '/templates/mh-events-tabs.php';
}

function htmh_remove_integration(){
    //remove_action( 'tribe_events_single_event_meta_primary_section_end', 'wpv_tribe_single_gmap', 5 );
}

function wootix_list_view_ticket_form() {
    if ( ! class_exists( 'TribeWooTickets' ) ) return;
    $ticket_form = array( TribeWooTickets::get_instance(), 'front_end_tickets_form' );
    add_action( 'tribe_events_single_event_after_the_content', $ticket_form );
}
function ht_wootix_remove_form(){
    if ( ! class_exists( 'TribeWooTickets' ) ) return;
    $ticket_form = array( TribeWooTickets::get_instance(), 'front_end_tickets_form' );
    remove_action( 'tribe_events_single_event_after_the_meta', $ticket_form, 5 );
}

function htmh_secured_with_gmo(){
    global $post;
    $mh_tickets_enabled = get_post_meta($post->ID, 'htmh_event_ticket_enabled', true);
    if(!$mh_tickets_enabled){
        return '';
    }
    // see if the event has tickets associated with it
    $wootickets = TribeWooTickets::get_instance();
    $ticket_ids = $wootickets->get_Tickets_ids( $post->ID );
    if ( empty($ticket_ids) ) {
        return '';
    }
    ?>
    <div class="htmh-globalsign-seal-code-wrap">
        <div style="margin-bottom:15px;text-align: center;">Your Purchase is Secured With:</div>
        <!--- DO NOT EDIT - GlobalSign SSL Site Seal Code - DO NOT EDIT --->
        <table title="CLICK TO VERIFY: This site uses a GlobalSign SSL Certificate to secure your personal information." border="0" width="125" cellspacing="0" cellpadding="0">
            <tbody>
            <tr>
                <td style="text-align: center;">
                    <span id="ss_img_wrapper_gmogs_image_125-50_en_dblue">
                        <a title="GlobalSign Site Seal" href="https://www.globalsign.com/" target="_blank" rel="nofollow">
                            <img id="ss_img" src="//seal.globalsign.com/SiteSeal/images/gs_noscript_125-50_en.gif" alt="SSL" border="0" />
                        </a></span>
                    <script src="//seal.globalsign.com/SiteSeal/gmogs_image_125-50_en_dblue.js" type="text/javascript"></script>
                </td>
            </tr>
            </tbody>
        </table>
        <!--- DO NOT EDIT - GlobalSign SSL Site Seal Code - DO NOT EDIT --->
    </div>
<?php
}

function htmh_events_featured_image(){
    global $post;
    wp_reset_query();
    $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full' );
    if(!$image) return '';
    ?>
    <div class="htmh-event-featured-image">
        <img alt="" src="<?php echo esc_url($image[0]); ?>"/>
    </div>
<?php
}


// AddShoppers Social Analytics Code
function addshoppers_social_analytics() {
    ?>
    <script type="text/javascript">
        var js = document.createElement('script'); js.type = 'text/javascript'; js.async = true; js.id = 'AddShoppers';
        js.src = ('https:' == document.location.protocol ? 'https://shop.pe/widget/' : 'http://cdn.shop.pe/widget/') + 'widget_async.js#54612c13a3876476bdd1445c';
        document.getElementsByTagName("head")[0].appendChild(js);
    </script>
<?php
}


add_action('wp_footer', 'addshoppers_social_analytics');

// AddShoppers ROI Tracking Code
add_action( 'woocommerce_thankyou', 'addshoppers_roi_tracking' );
function addshoppers_roi_tracking( $order_id ) {
    $order = new WC_Order( $order_id );
    ?>
    <script type="text/javascript">
        AddShoppersConversion = {
            order_id: <?php echo $order_id; ?>,
            value: <?php echo $order->get_total(); ?>
        };
        var js = document.createElement('script'); js.type = 'text/javascript'; js.async = true; js.id = 'AddShoppers';
        js.src = ('https:' == document.location.protocol ? 'https://shop.pe/widget/' : 'http://cdn.shop.pe/widget/') + 'widget_async.js#54612c13a3876476bdd1445c';
        document.getElementsByTagName("head")[0].appendChild(js);
    </script>

<?php
}


/* Remove Checkout Fields */
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

function custom_override_checkout_fields( $fields ) {
    unset($fields['billing']['billing_company']);
    unset($fields['order']['order_comments']);
    return $fields;
}
/* Remove Checkout Fields */

/*
* Auto Complete all WooCommerce orders.
* Add to theme functions.php file
*/

add_action( 'woocommerce_thankyou', 'custom_woocommerce_auto_complete_order' );
function custom_woocommerce_auto_complete_order( $order_id ) {
    global $woocommerce;

    if ( !$order_id )
        return;
    $order = new WC_Order( $order_id );
    $order->update_status( 'completed' );
}

add_filter('body_class', 'ht_order_complete_body_class');
function ht_order_complete_body_class($classes){
    global $wp;
    if(is_checkout() && isset( $wp->query_vars['order-received'] )){
        $classes[] = 'ht-order-complete-page';
    }
    return $classes;
}

add_action('wp_footer', 'ht_checkout_script', 20);
function ht_checkout_script(){
    if(!is_checkout())
        return '';
    ?>
    <script type="text/javascript">
        (function($){
            $(document).ready(function(){
                var $con = $('.woocommerce-checkout .checkout'),
                    $box = $('.col-2', $con),
                    conTop,wtop, m;
                $( window ).scroll(function(){
                    if($(window).width()<=768){
                        return false;
                    }

                    setTimeout(function(){
                        conTop = $con.offset();
                        conTop = conTop.top;
                        wtop = $(window).scrollTop();
                        m = wtop - conTop;
                        m = m + "px";
                        if(wtop>conTop){
                            if($(window).height()>$box.height()){
                                $box.animate({'margin-top':  m}, 50);
                            }
                        }else{
                            $box.animate({'margin-top':  0}, 50);
                        }
                    }, 50);
                });
            });
        })(jQuery);
    </script>
<?php
}
/* *****************************  Code written by ravi Pratap Singh  ******************************************** */
add_action( 'admin_menu', 'my_create_post_meta_box' );
add_action( 'save_post', 'my_save_post_meta_box', 10, 2 );

function my_create_post_meta_box() {
    add_meta_box( 'my-meta-box', 'Product associated with below venue', 'my_post_meta_box', 'product', 'normal', 'high' );
}

function my_post_meta_box( $object, $box ) { ?>
    <?php  $selectedproduct= wp_specialchars( get_post_meta( $object->ID, 'productwithvenue', true ), 1 ); ?>
    <p>
        <label for="product_meta_box_post_type">Select Venue: </label>
        <select name='productwithvenue' id='product_meta_box_post_type'>
            <option>Select Venue want to this Product</option>
            <?php  $terms = get_terms( 'tribe_events_cat' );
            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
                foreach ( $terms as $term ) {
                    ?>
                    <option value="<?php echo $term->term_id; ?>" <?php if($term->term_id==$selectedproduct) { ?> selected="selected" <?php } ?>><?php echo $term->name; ?></option>
                <?php  }} ?>
        </select>
        <!--<input type="hidden" name="my_meta_box_nonce" value="<?php //echo wp_create_nonce( plugin_basename( __FILE__ ) ); ?>" />-->
    </p>

<?php }

function my_save_post_meta_box( $post_id, $post ) {


    if ( !current_user_can( 'edit_post', $post_id ) )
        return $post_id;

    $meta_value = get_post_meta( $post_id, 'productwithvenue', true );
    $new_meta_value = stripslashes( $_POST['productwithvenue'] );

    if ( $new_meta_value && '' == $meta_value )
        add_post_meta( $post_id, 'productwithvenue', $new_meta_value, true );

    elseif ( $new_meta_value != $meta_value )
        update_post_meta( $post_id, 'productwithvenue', $new_meta_value );

    elseif ( '' == $new_meta_value && $meta_value )
        delete_post_meta( $post_id, 'productwithvenue', $meta_value );
}

if (class_exists('MultiPostThumbnails')) {
    new MultiPostThumbnails(array(
            'label' => 'Company Image',
            'id' => 'feature-image-company',
            'post_type' => 'tribe_venue'
        )
    );
    new MultiPostThumbnails(array(
            'label' => 'Venue Image',
            'id' => 'feature-image-venue',
            'post_type' => 'tribe_venue'
        )
    );
}


/** * Set cart item quantity action *
 * Only works for simple products (with integer IDs, no colors etc) *
 * @access public
 * @return void */
function woocommerce_set_cart_qty_action() {
    global $woocommerce;
    foreach ($_REQUEST as $key => $quantity) {
        // only allow integer quantities
        if (! is_numeric($quantity)) continue;

        // attempt to extract product ID from query string key
        $update_directive_bits = preg_split('/^set-cart-qty_/', $key);
        if (count($update_directive_bits) >= 2 and is_numeric($update_directive_bits[1])) {
            $product_id = (int) $update_directive_bits[1];
            $cart_id = $woocommerce->cart->generate_cart_id($product_id);
            // See if this product and its options is already in the cart
            $cart_item_key = $woocommerce->cart->find_product_in_cart( $cart_id );
            // If cart_item_key is set, the item is already in the cart

            if ( $cart_item_key ) {
                $woocommerce->cart->set_quantity($cart_item_key, $quantity);
            } else {
                // Add the product to the cart
                $woocommerce->cart->add_to_cart($product_id, $quantity);
            }
        }
    }
}
add_action('init', 'woocommerce_set_cart_qty_action' );

/* -----------------Start add field code in user profile-------------*/
function fb_add_custom_user_profile_fields( $user ) {
   $user->roles[0];
   global $current_user;
   $user_roles = $current_user->roles;
   $user_role = array_shift($user_roles);
    
   //if($user_role != 'administrator')
      //return false;

?>
 <div style="width: 100%; clear: both; float: left; margin: 19px 0px;">
    
    
    
            
        <?php
                    
            $args = array(
                'post_type' => 'tribe_venue',
             );

                $obituary_query = new WP_Query($args);

                 $getcalvenue =  esc_attr( get_the_author_meta( 'calvenue', $user->ID ) );
				
               
			   
                if($getcalvenue && $getcalvenue != ''){
                   $getcalvenue = explode(",", $getcalvenue);
                    
                } else {
                    $getcalvenue = array();
                } 
                if($getcalvenue || $user_role == 'administrator'){ ?>
                <div style="width:20%; float:left; font-weight: bold;">
        <label for="address"><?php _e('Event Calender Venue', 'your_textdomain'); ?></label>
    </div><div  style="width:60%; float:left;">
               <?php } while ($obituary_query->have_posts()) : $obituary_query->the_post();
                       
                $calvenu = get_the_title(); 
				$calvenuID = get_the_ID();
			
                        
                if($getcalvenue || $user_role == 'administrator'){        
        ?>
      
	
                <div style="width:150px;float:left;height:40px">
                
                        <?php if($getcalvenue && $getcalvenue != ''){ ?>
						<input type="checkbox" value="<?php echo $calvenuID; ?>" name="calvenue[]"  <?php if($getcalvenue == in_array($calvenuID,$getcalvenue))
						{ echo "checked" ; }  ?> style="float: left; width: 20px;"/> 
				<?php }else {?>	
						<input type="checkbox" value="<?php echo $calvenuID; ?>" name="calvenue[]"   style="float: left; width: 20px;"/> 
				<?php } ?>	
                
                    <div style="margin: -7px 1px; float: left; width: 120px;"> 
                            <?php echo $calvenu; ?> 
                    </div><br/>           
                </div>   
          <?php   }else{
			
			
			}            
        
            endwhile;

            // Reset Post Data
            wp_reset_postdata();
    ?>
      
    </div>   
</div>
    
<?php }

function fb_save_custom_user_profile_fields( $user_id ) {
    
    if ( !current_user_can( 'edit_user', $user_id ) )
        return FALSE;
        $scalvenue = $_POST['calvenue'];
    if($scalvenue){
         $savecalvenue= implode("," ,$scalvenue);
    }
    
    update_user_meta( $user_id, 'calvenue', $savecalvenue );
}

   add_action( 'show_user_profile', 'fb_add_custom_user_profile_fields' , 1 , 4 );
   add_action( 'edit_user_profile', 'fb_add_custom_user_profile_fields' , 1 , 4);

   add_action( 'personal_options_update', 'fb_save_custom_user_profile_fields', 1 , 4 );
   add_action( 'edit_user_profile_update', 'fb_save_custom_user_profile_fields', 1 , 4 );
/* -----------------end add field code in user profile-------------*/

remove_action('woocommerce_email_after_order_table',  array(TribeWooTickets::get_instance(), 'add_tickets_msg_to_email'), 10, 2 );

/*
function my_additional_schedules($schedules) {
        // interval in seconds
        $schedules['every2min'] = array('interval' => 2*60, 'display' => 'Every two minutes');
        return $schedules;
}
add_filter('cron_schedules', 'my_additional_schedules');
*/


/**
 * PUBNUB INTEGRATION
 *
 * This should eventually be moved to a plugin which has an admin UI for the pubnub settings
 *
 */

require_once(ABSPATH.'includes/pubnub/composer/lib/autoloader.php');
define('PUBNUB_PUBLISH_KEY', 'pub-c-e13cb106-3ad5-4dbf-bf78-8c24fae70280');
define('PUBNUB_SUBSCRIBE_KEY', 'sub-c-a007ef80-0f9c-11e5-91a9-0619f8945a4f');
use Pubnub\Pubnub;

$pubnub = new Pubnub(PUBNUB_PUBLISH_KEY, PUBNUB_SUBSCRIBE_KEY);



// FB Pixel Tracking Code
add_action( 'woocommerce_thankyou', 'fb_pixeltracking' );

function fb_pixeltracking( $order_id ) {
    // only run this code if we are in production
    if(substr(get_site_url(), 0, 11) != 'http://www.') return;

    $order = new WC_Order( $order_id );
    $order_total = $order->get_total();
    ?>
    <!-- Start FB Tracking - Replace XXXXXXXXXXXXXX with your tracking ID to track orders with values-->

    <!-- Facebook Conversion Code for Sales from Facebook Ads -->
    <!-- Facebook Conversion Code for Order Placed -->
    <script>(function() {
            var _fbq = window._fbq || (window._fbq = []);
            if (!_fbq.loaded) {
                var fbds = document.createElement('script');
                fbds.async = true;
                fbds.src = '//connect.facebook.net/en_US/fbds.js';
                var s = document.getElementsByTagName('script')[0];
                s.parentNode.insertBefore(fbds, s);
                _fbq.loaded = true;
            }
        })();
        window._fbq = window._fbq || [];
        window._fbq.push(['track', '6029791945431', {'value':'<?php echo $order_total ?>','currency':'USD'}]);
    </script>
    <noscript>
        <img height="1" width="1" alt="" style="display:none" src="https://www.facebook.com/tr?ev=6029791945431&amp;cd[value]=<?php echo $order_total ?>&amp;cd[currency]=USD&amp;noscript=1" />
    </noscript>
    <!-- END FB Tracking -->

    <?php
}

// Add a custom user role
// add_role( 'venue-staff', __('Venue Staff' ), array('read' => true));
$result = add_role( 'venue-manager', __('Venue Manager' ), array('read' => true));

/*
 * Custom Cart Product Image For Tickets
 */
add_action( 'init', 'custom_fix_thumbnail' );

function custom_fix_thumbnail() {
    add_filter('woocommerce_placeholder_img_src', 'custom_woocommerce_placeholder_img_src');

    function custom_woocommerce_placeholder_img_src( $src ) {
        $theme_directory = get_stylesheet_directory_uri();
        $src = $theme_directory . '/images/default_ticket_logo.png';

        return $src;
    }
}

add_action('woocommerce_before_cart', 'force_plugin_update_prices');

function force_plugin_update_prices() {
    global $woocommerce;
   // $GLOBALS['RP_WCDPD']->apply_discounts();
    $woocommerce->cart->calculate_totals();
}

require_once get_stylesheet_directory().'/templates/shortcodes/guestlist_signup/shortcode.php';
add_shortcode('gueslist_signup', 'guestlist_signup_shortcode');




add_action('admin_head', 'custom_v_style');

function custom_v_style() {
  echo '<style>
    
#acf_15194 {
width: 50%;
clear: both;
}
    
  </style>';
}



    function generate_tickets_custom( $order_id ) {
        // Bail if we already generated the info for this order
        $done = get_post_meta( $order_id, '_tribe_has_tickets', true );
        if ( ! empty( $done ) )
            return;

        $has_tickets = false;
        // Get the items purchased in this order

        $order       = new WC_Order( $order_id );
        $order_items = $order->get_items();

        // Bail if the order is empty
        if ( empty( $order_items ) )
            return;

        // Iterate over each product
        foreach ( (array) $order_items as $item ) {
            $product_id = isset( $item['product_id'] ) ? $item['product_id'] : $item['id'];

            // Get the event this tickets is for
            $event_id = get_post_meta( $product_id,'_tribe_wooticket_for_event', true );

            if ( ! empty( $event_id ) ) {

                $has_tickets = true;

                // Iterate over all the amount of tickets purchased (for this product)
                for ( $i = 0; $i < intval( $item['qty'] ); $i ++ ) {

                    $attendee = array( 'post_status' => 'publish',
                                       'post_title'  => $order_id . ' | ' . $item['name'] . ' | ' . ( $i + 1 ),
                                       'post_type'   => 'tribe_wooticket',
                                       'ping_status' => 'closed' );

                    // Insert individual ticket purchased
                    $attendee_id = wp_insert_post( $attendee );

                    update_post_meta( $attendee_id, $this->atendee_product_key, $product_id );
                    update_post_meta( $attendee_id, $this->atendee_order_key, $order_id );
                    update_post_meta( $attendee_id, $this->atendee_event_key, $event_id );
                    update_post_meta( $attendee_id, $this->security_code, $this->generate_security_code( $order_id, $attendee_id ) );
                }
            }
        }
        if ( $has_tickets ) {
            update_post_meta( $order_id, $this->order_has_tickets, '1' );

            // Send the email to the user
            do_action( 'wootickets-send-tickets-email', $order_id );
        }
    }


// $address = array(
//             'first_name' => 'Fresher1',
//             'last_name'  => 'StAcK OvErFloW',
//             'company'    => 'stackoverflow',
//             'email'      => 'tesdfdft@test.com',
//             'phone'      => '777-777-777-777',
//             'address_1'  => '31 Main Street',
//             'address_2'  => '', 
//             'city'       => 'Chennai',
//             'state'      => 'TN',
//             'postcode'   => '12345',
//             'country'    => 'IN'
//         );
// $_pf = new WC_Product_Factory();  
//         $order = wc_create_order();
//         $order->add_product( $_pf->get_product( '14853' ), 3 ); //(get_product with id and next is for quantity)
//         $order->set_address( $address, 'billing' );
//         $order->set_address( $address, 'shipping' );
//         //$order->add_coupon('Fresher','10','2'); // accepted param $couponcode, $couponamount,$coupon_tax
//         $order->calculate_totals();
//         echo $order->id;
      

