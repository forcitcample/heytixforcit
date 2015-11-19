<?php global $woocommerce;
 global $wpdb;

$user_ID = get_current_user_id();

$data = $wpdb->get_row('select * from '.$wpdb->usermeta. ' where meta_key = "wp_capabilities" and user_id = '.$user_ID);
$role = unserialize($data->meta_value);
?>
<form action="<?php echo (isset($role['venue_manager']) && $role['venue_manager'] == '1')?'http://localhost/heytix/sendticket':esc_url( $woocommerce->cart->get_cart_url() ); ?>"
      class="cart" method="post"
      enctype='multipart/form-data'>
		<table width="100%" class="tribe-events-tickets">
			<?php

			$is_there_any_product         = false;
			$is_there_any_product_to_sell = false;

			ob_start();
			$guestlist_itemid = null;
			//echo "<pre>"; print_r($tickets); echo "<pre>";
			foreach ( $tickets as $ticket ) {

				global $product;

				if ( class_exists( 'WC_Product_Simple' ) ) {
					$product = new WC_Product_Simple( $ticket->ID );
				} else {
					$product = new WC_Product( $ticket->ID );
				}

				// We need to determine which product id is the free ticket.
				// we continue the loop skipping this product as we don't require
				// any UI or free tickets listing here because the Guest List Button
				// will be displayed below.  Clicking that button goes to the billing
				// page which populates with data if we have it on file.

				if($product->get_price() == '0.00' || is_null($product->get_price())) {
					$guestlist_itemid = $product->id;
					continue;
				}

				$gmt_offset = ( get_option( 'gmt_offset' ) >= '0' ) ? ' +' . get_option( 'gmt_offset' ) : " " . get_option( 'gmt_offset' );
				$gmt_offset = str_replace( array( '.25', '.5', '.75' ), array( ':15', ':30', ':45' ), $gmt_offset );

				$end_date = null;
				if ( ! empty( $ticket->end_date ) ){
					$end_date = strtotime( $ticket->end_date . $gmt_offset );
				}else{
					$end_date = strtotime( tribe_get_end_date( get_the_ID(), false, 'Y-m-d G:i' ) . $gmt_offset );
				}

				$start_date = null;
				if ( !empty( $ticket->start_date ) )
					$start_date = strtotime( $ticket->start_date . $gmt_offset );


				if ( ( !$product->is_in_stock() ) || ( empty( $start_date ) || time() > $start_date ) && (  empty( $end_date ) || time() < $end_date ) ) {

					$is_there_any_product = true;

					echo sprintf( "<input type='hidden' name='product_id[]' value='%d'>", $ticket->ID );
					echo "<input type='hidden' name='wootickets_process' value='1'>";

					echo "<tr class='has-border'>";
					
					echo "<td class='woocommerce ht_quantity'>";

                                        
					if ( $product->is_in_stock() ) {
                                            $ht_max_itereation = $product->backorders_allowed() ? 20 : $product->get_stock_quantity();
                                            echo '<select class="htmh_ticket_number" name="quantity_'. $ticket->ID .'">';
                                            for($hti=0; $hti<=20;$hti++){
                                                echo '<option value="'. $hti .'">'. $hti .'</option>';
                                            }
                                            echo '</select>';
//						woocommerce_quantity_input( array( 'input_name'  => 'quantity_' . $ticket->ID,
//						                                   'input_value' => 0,
//						                                   'min_value'   => 0,
//						                                   'max_value'   => $product->backorders_allowed() ? '' : $product->get_stock_quantity(), ) );

						$is_there_any_product_to_sell = true;

					} else {
						echo "<span class='tickets_nostock'>" . esc_html__( 'Out of stock!', 'tribe-wootickets' ) . "</span>";
					}
					echo "</td>";
					
					echo "<td class='tickets_name'>";
					echo "<span class='ticket_name'>";
					echo $ticket->name;
					echo "</span>"; 
					if($ticket->description){
                                            echo "<span class='tickets_description'>";
                                            echo $ticket->description;
                                            echo "</span>";
                                        }
					echo "</td>";

					echo "<td class='woocommerce tickets_price'>";
					echo "<span class='price'>";
					echo "<span class='amount'>";
					if(isset($role['venue_manager']) && $role['venue_manager'] == '1'){ 
						?><style type="text/css">.htmh_ticket_number { display: none;}</style><?php 

					echo "Free"; } else {

						echo $this->get_price_html( $product );
					}
					echo "</span>";
					echo "</span>";
					echo "</td>";

					echo "</tr>";


				}

			}

			$contents = ob_get_clean();

			if ( $is_there_any_product ) {
                            ?>
                            <h2 class="tribe-events-tickets-title"><?php _e( 'Available Tickets', 'tribe-wootickets' );?></h2>
                            <?php echo $contents; ?>
                            <?php if ( $is_there_any_product_to_sell ) { ?>
                            <?php if(isset($role['venue_manager']) && $role['venue_manager'] == '1'){ ?>
                                <tr>
                                    <td colspan="3" width="100%">
                                        <button type="submit" class="button vamtam-button button-border accent1 hover-accent1"><span class="btext"><?php esc_html_e( 'Send Tickets', 'tribe-wootickets' );?></span></button>
                                    </td>
                                </tr>
                             <?php } else { ?>

                             <tr>
                                    <td colspan="3" width="100%">
                                        <button type="submit" class="button vamtam-button button-border accent1 hover-accent1"><span class="btext"><?php esc_html_e( 'Add to cart', 'tribe-wootickets' );?></span></button>
                                    </td>
                                </tr>


                           <?php  } ?>   
                                <?php
                            }
			}
			?>

		</table>

</form>