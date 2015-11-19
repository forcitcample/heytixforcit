<?php
/**
 * Customer completed order email
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates/Emails
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$order_id = $tickets[0]['order_id'];
$order = wc_get_order($order_id);

$items = array_values($order->get_items());

$has_paid_tickets = false;
foreach($items as $item) {
    if($item['line_total'] > 0) {
        $has_paid_tickets = true;
    }
}

if($has_paid_tickets === false) return;
?>



<?php do_action( 'woocommerce_email_header', __('Your order is complete', 'woocommerce') ); ?>

<p><?php printf( __( "Hi there. Your recent order on %s has been completed. Your order details are shown below for your reference:", 'woocommerce' ), get_option( 'blogname' ) ); ?></p>

<?php do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text ); ?>

<h2><?php printf( __( 'Order #%s', 'woocommerce' ), $order->get_order_number() ); ?></h2>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
    <thead>
    <tr>
        <th scope="col" style="text-align:left; border: 1px solid #eee; border-right: 0px;"><?php _e( 'Product', 'woocommerce' ); ?></th>
        <th scope="col" style="text-align:left; border: 1px solid #eee; border-left: 0px;">&nbsp;</th>
        <th scope="col" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Price', 'woocommerce' ); ?></th>
    </tr>
    </thead>
    <tbody>

    <?php

        // remove https from the ticket links
        $site_url = str_replace('https://', 'http://', get_site_url());

        for($x=0; $x <= count($tickets)-1; $x++) {
            // determine the line item
            $product_id = get_post_meta($tickets[$x]["ticket_id"], '_tribe_wooticket_product', true);
            foreach($items as $item) {
                if($product_id == $item['product_id']) {
                    $tickets[$x]['price'] = $item['line_subtotal'] / $item['qty'];
                }
            }
            $event = get_post($tickets[$x]["event_id"]);
            $event_name = $event->post_title;
            echo '<tr>';
            echo '<td style="text-align: left; vertical-align: middle; border: 1px solid #eee; border-right: 0px; word-wrap: break-word; padding: 12px;">' . $tickets[$x]['ticket_name'] . '<br/><span style="font-size: 10px;">' . $event_name . '</span></td>';
            echo '<td style="text-align: right; vertical-align: middle; border 1px solid #eee; border-left: 0px; border-top: 0px; padding: 12px;"><a href="' . $site_url . '/mobile_ticket/?security_code=' . $tickets[$x]['security_code'] . '" style="background:#3cb0fd; border: 3px solid #3cb0fd; color:#ffffff; width: 80px; text-align: center; display:inline-block;text-decoration:none;padding:5px 10px;font-size:12px;font-weight:600;border-radius:5px; margin-bottom: 8px;">Get Ticket</a><br/><a href="' . $site_url . '/mobile_ticket/?security_code=' . $tickets[$x]['security_code'] . '&send=1" style="background:#ffffff;color:#3cb0fd;border: 3px solid #3cb0fd;display:inline-block;text-decoration:none;padding:5px 10px;font-size:12px;font-weight:600;border-radius:5px;width:80px; text-align:center">Send Ticket</a></td>';
            echo '<td style="text-align: left; vertical-align: middle; border: 1px solid #eee; padding: 12px;"><span class="amount">' . wc_price($tickets[$x]['price']) . '</span></td>';
            echo '</tr>';
        }

    ?>

    </tbody>
    <tfoot>
    <?php
    if ( $totals = $order->get_order_item_totals() ) {
        $i = 0;
        foreach ( $totals as $total ) {
            $i++;
            ?><tr>
            <th scope="row" colspan="2" style="text-align:left; border: 1px solid #eee; <?php if ( $i == 1 ) echo 'border-top-width: 4px;'; ?>"><?php echo $total['label']; ?></th>
            <td style="text-align:left; border: 1px solid #eee; <?php if ( $i == 1 ) echo 'border-top-width: 4px;'; ?>"><?php echo $total['value']; ?></td>
            </tr><?php
        }
    }
    ?>
    </tfoot>
</table>

<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text ); ?>

<?php do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text ); ?>

<?php do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text ); ?>

<?php do_action( 'woocommerce_email_footer' ); ?>