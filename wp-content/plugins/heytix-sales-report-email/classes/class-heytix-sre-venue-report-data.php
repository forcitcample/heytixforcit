<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Heytix_SRE_Venue_Report_Data {
	private $start_date;
	private $end_date;
	private $date_range;
	private $data;
	/**
	 * The constructor
	 *
	 * @param $date_range
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function __construct(Heytix_SRE_Date_Range $date_range ) {
		$this->date_range = $date_range;
		$this->start_date = (int) $this->get_date_range()->get_start_date()->format( 'U' );
		$this->end_date = (int) $this->get_date_range()->get_end_date()->format( 'U' );
		$this->init();
	}

	/**
	 * @return Heytix_SRE_Date_Range;

	 */
	public function get_date_range() {
		return $this->date_range;
	}

	public function get_data() {
		return $this->data;
	}

	/**
	 * Initialize the data
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function init()
	{
		global $wpdb;

		$querystr = "
    		SELECT $wpdb->posts.ID as id
			FROM $wpdb->posts
			WHERE $wpdb->posts.post_date >= '" . date('Y-m-d', $this->start_date) . "' AND $wpdb->posts.post_date < '" . date('Y-m-d', strtotime('+1 DAY', $this->end_date)) . "'
			AND post_type='tribe_wooticket'";

		$tickets = $wpdb->get_results($querystr, OBJECT);
		if (count($tickets) > 0) {
			foreach ($tickets as $t) {
				$ticket = get_post($t->id);
				$product_id = get_post_meta($t->id, '_tribe_wooticket_product', true);
				$sku = get_post_meta($product_id, '_sku', true);

				// skip guestlist tickets
				if(strpos(strtolower($sku), 'gl-') !== false) continue;

				$info = explode(' | ', $ticket->post_title);

				$event_id = get_post_meta($t->id, '_tribe_wooticket_event', true);
				$venue_id = get_post_meta($event_id, '_EventVenueID', true);
				$artist = get_post_meta($event_id, 'htmh_event_mobile_artist_name', true);
				$venue_name = get_post_meta($event_id, 'htmh_event_mobile_venue_name', true);
				$ticket_price = get_post_meta($product_id, '_price', true);
				$event = get_post($event_id);

				$this->data[$venue_id][$event_id]['venue_name'] = $venue_name;
				$this->data[$venue_id][$event_id]['artist'] = trim($artist);
				$this->data[$venue_id][$event_id]['name'] = trim($event->post_title);
				$this->data[$venue_id][$event_id]['date'] = trim($event->post_date);
				$this->data[$venue_id][$event_id]['ticket_type'] = trim($info[1]);
				$this->data[$venue_id][$event_id]['total_tickets'] += trim($info[2]);

				$this->data[$venue_id][$event_id]['revenue'] += trim($ticket_price);
			}
			error_log(count($this->data));
		} else {
			error_log('no tickets found');
		}
	}

}