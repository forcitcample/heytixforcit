<?php 

require_once(dirname(dirname(dirname(dirname( __FILE__ )))).'/wp-load.php');

global $wpdb;  

$fname = $_REQUEST['fname'];

$lname = $_REQUEST['lname'];

$email = $_REQUEST['email'];

$ga_tickets = $_REQUEST['ga_tickets'];

$vip_tickets = $_REQUEST['vip_tickets'];

$all_tickets = $_REQUEST['all_tickets'];

$t_tickets = $ga_tickets + $vip_tickets + $all_tickets;

$event_id = $_REQUEST['event_id'];

$user_id = $_REQUEST['user_id'];

$ticket_id = $_REQUEST['ticket_id'];

$table_name = "wp_mng_free_tickets";

$query = "INSERT INTO $table_name (event_id, manager_id, ticket_id, fname, lname, email, ga_tickets, vip_tickets, all_tickets, total_tickets)VALUES('$event_id', '$user_id', '$ticket_id', '$fname', '$lname', '$email', '$ga_tickets', '$vip_tickets', '$all_tickets', '$t_tickets')";

$queryE = $wpdb->query($query);

if($queryE ){

	echo 'Added Sucessfully';

} else {

	echo 'Failed';
}



$address = array(
            'first_name' => $fname,
            'last_name'  => $lname,
            'company'    => 'FCT',
            'email'      => $email,
            'phone'      => '777-777-777-777',
            'address_1'  => '31 Main Street',
            'city'       => 'Mohali',
            'country'    => 'IN'
        );
$_pf = new WC_Product_Factory();  
        $order = wc_create_order();
        $order->add_product( $_pf->get_product( '15342' ), $ga_tickets); //(get_product with id and next is for quantity)
        $order->set_address( $address, 'billing' );
        $order->set_address( $address, 'shipping' );
        //$order->add_coupon('Fresher','10','2'); // accepted param $couponcode, $couponamount,$coupon_tax
        $order->calculate_totals();
        $id = $order->id;


        $order = wc_get_order($id);

$items = array_values($order->get_items());


$has_paid_tickets = false;
foreach($items as $item) {
    echo '<pre>'; print_r($item); echo '</pre>';
    if($item['line_total'] > 0) {
        $has_paid_tickets = true;
    }
}


// $woo_tickets = TribeWooTickets::get_instance();
// $ticket_ids  = $woo_tickets->get_tickets_ids( $event_id );
///echo WC_Order_Barcodes()->display_barcode(15306).'????';
///$tickets = Tribe__Events__Tickets__Tickets::get_event_tickets( $event_id );
//foreach ( $ticket_ids as $ticket_id ) {
	//echo '<pre>'; print_r($ticket_id); echo '</pre>';
    //printf( '<h3>%s</h3>', get_the_title( $ticket_id ) );

//}


?>