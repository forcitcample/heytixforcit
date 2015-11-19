<?php
/**
 * Sales Report Email Plan
 *
 * @author        WooThemes
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

echo $email_heading . "\n\n";

echo __( 'Hi there. Please find your %s sales report below.', 'heytix-sales-report-email' ) . "\r\n\r\n";

foreach ( $rows as $row ) {
	echo $row->get_label() . ': ' . $row->get_value() . "\r\n\r\n";
}

echo "\r\n\r\n";
if(count($events) > 0) {
	foreach($events as $event) {
		echo $event['name'] . "\r\n";
		foreach($event['tickets'] as $name => $total) {
			echo "\t".$total.'x '.$name."\r\n";
		}

	}
}
echo "\n****************************************************\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );