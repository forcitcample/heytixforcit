<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Tribe__Events__Tickets__Woo__Email extends WC_Email {

	public $email_type;
	public $enabled;

	function __construct() {

		$this->id          = 'wootickets';
		$this->title       = __( 'Tickets', 'tribe-wootickets' );
		$this->description = __( 'Email the user will receive after a completed order with the tickets he purchased.', 'tribe-wootickets' );

		$this->subject = __( 'Your tickets from {sitename}', 'tribe-wootickets' );


		// Triggers for this email
		add_action( 'wootickets-send-tickets-email', array( $this, 'trigger' ) );


		// Call parent constuctor
		parent::__construct();

		$this->enabled = apply_filters( 'wootickets-tickets-email-enabled', 'yes' );
		$this->email_type = 'html';

	}


	function trigger( $order_id ) {

		if ( $order_id ) {
			$this->object    = new WC_Order( $order_id );
			$this->recipient = $this->object->billing_email;

			$this->find[]    = '{sitename}';
			$this->replace[] = get_option( 'blogname' );

		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() )
			return;

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * get_subject function.
	 *
	 * @access public
	 * @return string
	 */
	function get_subject() {
		return apply_filters( 'wootickets_ticket_email_subject', $this->format_string( $this->subject ), $this->object );
	}


	function get_content_html() {

		$wootickets = Tribe__Events__Tickets__Woo__Main::get_instance();

		$args = array( 'post_type'      => $wootickets->attendee_object,
					   'meta_key'       => $wootickets->atendee_order_key,
					   'meta_value'     => $this->object->id,
					   'posts_per_page' => - 1 );

		$query = new WP_Query( $args );

		$attendees = array();

		foreach ( $query->posts as $post ) {

			$attendees[] = array( 'event_id'      => get_post_meta( $post->ID, $wootickets->atendee_event_key, true ),
			                      'ticket_name'   => get_post( get_post_meta( $post->ID, $wootickets->atendee_product_key, true ) )->post_title,
			                      'holder_name'   => get_post_meta( $this->object->id, '_billing_first_name', true ) . ' ' . get_post_meta( $this->object->id, '_billing_last_name', true ),
			                      'order_id'      => $this->object->id,
			                      'ticket_id'     => $post->ID,
			                      'security_code' => get_post_meta( $post->ID, $wootickets->security_code, true )
			);
		}

		return Tribe__Events__Tickets__Woo__Main::get_instance()->generate_tickets_email_content( $attendees );
	}


	/**
	 * Initialise Settings Form Fields
	 *
	 * @access public
	 * @return void
	 */
	function init_form_fields() {
		$this->form_fields = array(
			'subject' => array(
				'title'            => __( 'Subject', 'woocommerce' ),
				'type'             => 'text',
				'description'      => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->subject ),
				'placeholder'      => '',
				'default'          => ''
			),
		);
	}
}