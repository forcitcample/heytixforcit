<?php
/*
Plugin Name: WooCommerce Donations Plugin
Plugin URI: http://www.pidhasome.com/albdesign/plugins/
Description: This plugin adds a donation field on the cart page.
Version: 1.5
Author: Albdesign
Author URI: http://www.pidhasome.com/albdesign/plugins
*/



//load translatable files
add_action('plugins_loaded', 'albdesign_wc_donations_language');
function albdesign_wc_donations_language() {
	load_plugin_textdomain( 'albdesign-wc-donations', false, dirname( plugin_basename( __FILE__ ) ) . '/language/' );
}





	add_action('admin_menu', 'register_woocommerce_donation_submenu');

	function register_woocommerce_donation_submenu() {
		add_submenu_page( 'woocommerce', 'Donations', 'Donations', 'manage_options', 'donnation-settings-page', 'woocommerce_donation_submenu_callback' ); 
	}

	function woocommerce_donation_submenu_callback() {
    echo '<h3>Donation settings page</h3>';
	
	
	//Create new product STARTS
	if(isset($_POST['woocommerce_donations_add_new_product_form'])){
	
			if($_POST['woocommerce_donations_new_product_title']!=""){
				$new_product_title = $_POST['woocommerce_donations_new_product_title'];
			}
		
						$add_new_donation_product_array = array(

								  'post_title'     => $new_product_title ,
								  'post_status'    => 'publish' , 
								  'post_type'      => 'product'  

								);  
						$id_of_new_donation_product = wp_insert_post($add_new_donation_product_array);

						
						
						update_post_meta($id_of_new_donation_product , '_visibility','hidden');		
						update_post_meta($id_of_new_donation_product , '_sku','checkout-donation-product');		
						update_post_meta($id_of_new_donation_product , '_tax_class','zero-rate');		
						update_post_meta($id_of_new_donation_product , '_tax_status','none');		
						update_post_meta($id_of_new_donation_product , '_sold_individually','yes');		
	
	}
	//Create new product ENDS
	
	
	if(isset($_POST['woocommerce_donations_select_product_form'])){
	
		if ( !isset($_POST['woocommerce_donations_select_product_nonce_field']) || !wp_verify_nonce($_POST['woocommerce_donations_select_product_nonce_field'],'woocommerce_donations_select_product_nonce') )
		{
		   print 'Sorry, your nonce did not verify.';
		   exit;
		}
		else
		{
			//PROCESS FORM DATA 
			
			//selected an existing product
			if($_POST['woocommerce_donations_select_product_id']!=""){
				
				//save selected product ID
				$donation_product_new_option_value = $_POST['woocommerce_donations_select_product_id'] ;

				if ( get_option( 'woocommerce_donations_product_id' ) !== false ) {
					update_option( 'woocommerce_donations_product_id', $donation_product_new_option_value );
				} else {
					// there is still no options on the database
					add_option( 'woocommerce_donations_product_id', $donation_product_new_option_value, null, 'no' );
				}

			}
		}
		
		
	}

	?>
	
	<form  action="" method="post">
	<table class="form-table">
		<tbody>

	
	<tr valign="top">
		<th scope="row" class="titledesc"><label for="woocommerce_donations_select_product_id">Donation product</label></th>
		<td class="forminp">
			Select existing product 
			<select name="woocommerce_donations_select_product_id" id="woocommerce_donations_select_product_id" style="" class="select email_type">
			<option value=""></option>
			
			<?php
			
			//read existing products that fullfill our needs
            $query_existing_hidden_products = new WP_Query( array( 
            'posts_per_page' => 5,
            'post_type'      => array( 'product' ),
            'meta_query' => array(
                            array(
                                'key' => '_visibility',
                                'value' => array( 'catalog', 'visible' ),
                                'compare' => 'NOT IN'
            ))));	
	
	
	
	while ( $query_existing_hidden_products->have_posts() ) {
	
		$query_existing_hidden_products->the_post(); ?>
		
		<option value="<?php echo get_the_ID(); ?>" <?php if ( get_option('woocommerce_donations_product_id' )  == get_the_ID() ){ echo 'selected="selected"'; } ?>> <?php echo get_the_title() ;?> </option>
		
	<?php
	}
	wp_reset_postdata();
	?>
			</select>

			<p class="description">A hidden, non taxable,not shippable product  needs to exists in woocommerce before using donations</p>
		</td>
	</tr>			
				
					
	</tbody>
	</table>		
		<p class="submit">
			<input type="hidden" name="woocommerce_donations_select_product_form">
				<?php wp_nonce_field('woocommerce_donations_select_product_nonce','woocommerce_donations_select_product_nonce_field'); ?>
			<input name="save" class="button-primary" type="submit" value="Save changes">        			        
		</p>
	</form>	
	

	<table class="form-table">
		<tbody>

	
	<tr valign="top">
		<th scope="row" class="titledesc"><label for="woocommerce_donations_select_product_id">New Donation product</label></th>
		<td class="forminp">
			<form  action="" method="post">
				  New product title :   <input name="woocommerce_donations_new_product_title" class="text" type="text" >
				<input name="woocommerce_donations_add_new_product_form" class="button" type="submit" value="Create Product">
			
				<p class="description">A hidden, non taxable,not shippable product will be created and you can select it on the "Donation Product" above afterward. <br> Keep in mind that the new product title will be visible on the cart, the checkout page and invoice so name it something like "DONATIONS" . </p>
			</form>
		</td>
	</tr>			
				
					
	</tbody>
	</table>		
	
		
	<?php
	
	} //woocommerce_donation_submenu_callback




	//current product ID 
	if ( get_option('woocommerce_donations_product_id' ) !== false ) {
	
		//defines the ID of the product to be used as donation
		define('DONATE_PRODUCT_ID', get_option( 'woocommerce_donations_product_id' )); 
	}


 
function ok_donation_exists(){
 
	global $woocommerce;
 
	if( sizeof($woocommerce->cart->get_cart()) > 0){
 
		foreach($woocommerce->cart->get_cart() as $cart_item_key => $values){
 
			$_product = $values['data'];
 
			if($_product->id == DONATE_PRODUCT_ID)
				return true;
		}
	}
	return false;
}


add_action('woocommerce_cart_contents','ok_woocommerce_after_cart_table');
function ok_woocommerce_after_cart_table(){
 
	global $woocommerce;
	$donate = isset($woocommerce->session->ok_donation) ? floatval($woocommerce->session->ok_donation) : 0;
 
	if(!ok_donation_exists()){
		unset($woocommerce->session->ok_donation);
	}
  
	if(!ok_donation_exists()){
		?>
<div style="clear: both; height: 20px; "></div>
		<tr class="donation-block">
			<td colspan="6">
			<div class="m_donate">
				<a href="http://www.nationalmssociety.org/" target="_blank" rel="nofollow"><img src="http://www.heytix.com/wp-content/uploads/2014/12/MS_Society.jpg"></a>
				<div class="donation">
					<p class="message"><strong><?php _e('Add a donation to your order','albdesign-wc-donations'); ?></strong></p>
					<form action=""method="post">
						<div class="input text">
						 	<div class="d_sign">&#36;</div>
							<div class="d_field"><input type="text" name="ok-donation" class="input-text" value="<?php echo $donate;?>"/></div>
							<div class="d_btn"><input type="submit" name="donate-btn" class="button" value="<?php _e('Add Donation','albdesign-wc-donations');?>"/></div>
						</div>
					</form>
				</div>	
					<div style="clear: both;"></div>
					
			</td>
		</tr>
		</div>
		<?php
	}
}

add_action('init','ok_process_donation');
function ok_process_donation(){
 
	global $woocommerce;
 
	$donation = isset($_POST['ok-donation']) && !empty($_POST['ok-donation']) ? floatval($_POST['ok-donation']) : false;
 
	if($donation && isset($_POST['donate-btn'])){
 
		// add item to basket
		$found = false;
 
		// add to session
		if($donation >= 0){
			$woocommerce->session->ok_donation = $donation;
 
			//check if product already in cart
			if( sizeof($woocommerce->cart->get_cart()) > 0){
 
				foreach($woocommerce->cart->get_cart() as $cart_item_key=>$values){
 
					$_product = $values['data'];
 
					if($_product->id == DONATE_PRODUCT_ID)
						$found = true;
				}
 
				// if product not found, add it
				if(!$found)
					$woocommerce->cart->add_to_cart(DONATE_PRODUCT_ID);
			}else{
				// if no products in cart, add it
				$woocommerce->cart->add_to_cart(DONATE_PRODUCT_ID);
			}
		}
	}
}

add_filter('woocommerce_get_price', 'ok_get_price',10,2);
function ok_get_price($price, $product){
 
	global $woocommerce;
 
	if($product->id == DONATE_PRODUCT_ID){
		return isset($woocommerce->session->ok_donation) ? floatval($woocommerce->session->ok_donation) : 0;
	}
	return $price;
}


add_action('woocommerce_review_order_before_payment','albdesign_donations_add_link_on_checkout');
function albdesign_donations_add_link_on_checkout(){ 
	global $woocommerce;

	
	
	//check if donation is already in cart 
	foreach($woocommerce->cart->get_cart() as $cart_item_key => $values ) {
		$_product = $values['data'];
	
		$products_ids_in_cart[$_product->id]= $_product->id;

	}

	//if no donation found on cart ... show a link on checkout page
	if(!in_array(DONATE_PRODUCT_ID,$products_ids_in_cart )){
	
?>
		<div style="margin: 0 -1px 24px 0;">
		<h3><?php _e('Add a donation to your order','albdesign-wc-donations'); ?>  </h3> 
		
		<?php printf( __( 'If you wish to add a donation you can do so on the <a href="%s"> cart page </a>', 'albdesign-wc-donations' ), $woocommerce->cart->get_cart_url() ); ?>
		</div>
<?php 
    } //end if "no donation found on cart"
} 

?>