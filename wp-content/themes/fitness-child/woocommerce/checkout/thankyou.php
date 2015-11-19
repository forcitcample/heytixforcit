<?php
/**
 * Thankyou page
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( $order ) : ?>

	<?php if ( $order->has_status( 'failed' ) ) : ?>

		<p><?php _e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction.', 'woocommerce' ); ?></p>

		<p><?php
			if ( is_user_logged_in() )
				_e( 'Please attempt your purchase again or go to your account page.', 'woocommerce' );
			else
				_e( 'Please attempt your purchase again.', 'woocommerce' );
			?></p>

		<p>
			<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php _e( 'Pay', 'woocommerce' ) ?></a>
			<?php if ( is_user_logged_in() ) : ?>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php _e( 'My Account', 'woocommerce' ); ?></a>
			<?php endif; ?>
		</p>

	<?php else : ?>
		<?php
		// we need to get some details about the event based upon the first item in the order items.
		$items = $order->get_items();
		reset($items);
		$item = $items[key($items)];

		$product_id = $item['product_id'];
		$event_id = get_post_meta($product_id, '_tribe_wooticket_for_event', true);
		$event = get_post($event_id);
		$title = urlencode($event->post_title);
		$message = urlencode("I am going to see ".$event->post_title.", you should too, get your ticket now and join me for a fun night out!");
		$url = urlencode(get_site_url().'/event/'.$event->post_name.'/');

		if (has_post_thumbnail( $event_id ) ) {
			$image = wp_get_attachment_image_src( get_post_thumbnail_id( $event_id ), 'single-post-thumbnail' );
			$image = $image[0];
		}
		?>

		<p class="share_p"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', __( 'Thank you. Your order has been received.', 'woocommerce' ), $order ); ?></p>

		<ul class="order_details">
			<li class="order">
				<?php _e( 'Order Number:', 'woocommerce' ); ?>
				<strong><?php echo $order->get_order_number(); ?></strong>
			</li>
			<li class="date">
				<?php _e( 'Date:', 'woocommerce' ); ?>
				<strong><?php echo date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) ); ?></strong>
			</li>
			<li class="total">
				<?php _e( 'Total:', 'woocommerce' ); ?>
				<strong><?php echo $order->get_formatted_order_total(); ?></strong>
			</li>
			<?php if ( $order->payment_method_title ) : ?>
				<li class="method">
					<?php _e( 'Payment Method:', 'woocommerce' ); ?>
					<strong><?php echo $order->payment_method_title; ?></strong>
				</li>
			<?php endif; ?>
		</ul>
		<div class="clear"></div>
		

			<script>
function fbShare(url, title, descr, image, winWidth, winHeight) {
    var winTop = (screen.height / 2) - (winHeight / 2);
    var winLeft = (screen.width / 2) - (winWidth / 2);
    window.open('http://www.facebook.com/sharer.php?s=100&p[title]=' + title + '&p[summary]=' + descr + '&p[url]=' + url + '&p[images][0]=' + image, 'sharer', 'top=' + winTop + ',left=' + winLeft + ',toolbar=0,status=0,width=' + winWidth + ',height=' + winHeight);
}

function twitterShare(message, url, winWidth, winHeight) {
    var winTop = (screen.height / 2) - (winHeight / 2);
    var winLeft = (screen.width / 2) - (winWidth / 2);
    window.open('https://twitter.com/share?url=' + url + '&text=' + message + '&hashtags=heytix', 'sharer', 'top=' + winTop + ',left=' + winLeft + ',toolbar=0,status=0,location=0,width=' + winWidth + ',height=' + winHeight);
}

function emailShare(sender, event_id, winWidth, winHeight) {
    var winTop = (screen.height / 2) - (winHeight / 2);
    var winLeft = (screen.width / 2) - (winWidth / 2);
    window.open('<?php echo get_site_url();?>/email-a-friend.php?sender=' + sender + '&event_id=' + event_id, 'sharer', 'top=' + winTop + ',left=' + winLeft + ',toolbar=0,status=0,location=0,width=' + winWidth + ',height=' + winHeight);
}
</script>

<div class="checkout-social-buttons">
<h2 class="checkout_titles">Share with Friends</h2>
<p class="share_p">Make sure they get their tickets too</p>
<a class="facebook-btn social_btns fb_check" href="javascript:fbShare('<?php echo $url; ?>', '<?php echo $title; ?>', '<?php echo $message; ?>', '<?php echo $image; ?>', 520, 350)"><i class="fa fa-facebook"></i><span class="check_txt">Facebook</span></a>
<a href="javascript:twitterShare('<?php echo $message; ?>', '<?php echo $url; ?>', 520, 350)" class="social_btns tw_check"><i class="fa fa-twitter"></i><span class="check_txt">Tweet</span></a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>
<a class="facebook-btn social_btns em_check" href="javascript:emailShare('<?php echo urlencode($order->billing_first_name.' '.$order->billing_last_name);?>', '<?php echo $event_id; ?>', 520, 485)"><i class="fa fa-envelope"></i><span class="check_txt">Email</span></a>

</div>
<div style="clear: both;"></div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_thankyou_' . $order->payment_method, $order->id ); ?>
	<?php do_action( 'woocommerce_thankyou', $order->id ); ?>
	

<?php else : ?>

	<p><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', __( 'Thank you. Your order has been received.', 'woocommerce' ), null ); ?></p>

<?php endif; ?>
