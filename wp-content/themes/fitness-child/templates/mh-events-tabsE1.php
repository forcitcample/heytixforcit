<div class="ht-event-tabs">


    <?php
    $event_id = get_the_ID();
    $terms = wp_get_post_terms($event_id, 'tribe_events_cat', $args );
    //echo '<pre>';print_r($terms);
    $venueid=$terms['0']->term_taxonomy_id;

    global $wpdb;
    $results = $wpdb->get_results( "select post_id from $wpdb->postmeta where meta_value = '".$venueid."'");
    foreach($results as $result)
    { 
        $postids[]=$result->post_id;
    }
    // $productids= substr($postids,0,-1);
    //$pro='array('.$productids.')';
      //array( 2, 5, 12, 14, 20 )
    
    $mh_tickets_enabled = get_post_meta($event_id, 'htmh_event_ticket_enabled', true);
    $mh_guest_list_enabled = get_post_meta($event_id, 'htmh_event_guest_list_enabled', true);
    $mh_get_tables_enabled = get_post_meta($event_id, 'htmh_event_bottle_service_enabled', true);
    $selectedproduct= wp_specialchars( get_post_meta($event_id, 'productwithevent', true ), 1 ); 
    
    
    // Get Ticket Form sc
    $ticket_form_sc = '';
    if ( $mh_tickets_enabled && class_exists( 'TribeWooTickets' ) ) {
        ob_start();
		$ticket_form = TribeWooTickets::get_instance();
		$ticket_form->front_end_tickets_form();
	        $ticket_form_sc = ob_get_clean();
	        if($ticket_form_sc){
	            					$tiecket_form_sc = '[tab title="Get Tickets" class="ht-event-tab-ticket"]'. $ticket_form_sc .'[/tab]';
	       					  }
    }
   
    
    // Get Guest List sc
    $guest_list_sc = '';
    if($mh_guest_list_enabled){
        $guest_list_sc = '[tab title="Guest List" class="ht-event-tab-guest-list"][gravityform id="8" name="Guest List" title="false" description="false"][/tab]';
    }
    // Get Table sc
    $table_form_sc = '';
    if(!empty($postids)){
    // $args = array( 'post_type' => 'product','p' => $selectedproduct, );
            $args = array( 'post_type' => 'product','post__in' =>$postids);
            $loop = new WP_Query( $args );
			//echo '<pre>';print_r($loop);die;
            while ( $loop->have_posts() ) : $loop->the_post();
			
         $product_image = wp_get_attachment_url(get_post_thumbnail_id($post->ID) );
         //echo '$feat_image';

        $formbody.='<form class="product" method="post" enctype="multipart/form-data"><div class="productheader">
  <div class="producttitle">'.$loop->post->post_title.'</div>
  <div class="quantity buttons_added productq"><label class="qlabel">Quantity</label>
  <select name="quantity" value="1" title="Qty" class="input-text qty text qbox"><option value="0">0</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option></select>
  </div>
</div>


<div class="productfooter">
 
 <div class="product_description">
 <img src="'.$product_image.'" class="pro_img"/>
 <span class="price"><sup>$</sup>'.$price = get_post_meta($loop->post->ID, '_regular_price', true).'</span>
 </div>
 
  <div class="product_description_1">
 <span class="productcontent">'.$loop->post->post_content.'</span>
 <button class="single_add_to_cart_button button alt" type="submit">Add to cart</button>
 
 </div>
 </div>

<input type="hidden" name="add-to-cart" value="'.$loop->post->ID.'">
</form>';
// $raw.= 'Retail Price: $'.$price = get_post_meta($loop->post->ID, '_regular_price', true).'<br/><br/>';
		
		
           endwhile; 
           wp_reset_query();
          
          //  $raw.='<h3 class="tribe-events-tickets-title">'.$loop->post->post_title.'</h3>';
           // $raw.= 'Retail Price: $'.$price = get_post_meta($loop->post->ID, '_regular_price', true).'<br/><br/>';
    if($mh_get_tables_enabled){
        $table_form_sc = '[tab title="Bottle Service" class="ht-event-tab-guest-list"]'.$formbody.'[/tab]';
    }
    	  
    	} else
    	{
    	$table_form_sc = '[tab title="Bottle Service" class="ht-event-tab-guest-list"] [gravityform id="6" name="Bottle Service Request" title="false" description="false"][/tab]';

    	}
    
    $tabs_sc = '[tabs style="clean" delay="0" vertical="false" left_color="" right_color="" nav_color=""]' . $tiecket_form_sc . $guest_list_sc . $table_form_sc . '[/tabs]';
    
    
    echo do_shortcode($tabs_sc);
    ?>
</div>