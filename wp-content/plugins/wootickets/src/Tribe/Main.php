<?php

if ( class_exists( 'Tribe__Events__Tickets__Woo__Main' ) || ! class_exists( 'Tribe__Events__Tickets__Tickets' ) )
	return;

class Tribe__Events__Tickets__Woo__Main extends Tribe__Events__Tickets__Tickets {

	/**
	 * Name of the CPT that holds Attendees (tickets holders)
	 * @var string
	 */
	public $attendee_object = 'tribe_wooticket';

	/**
	 * Meta key that relates Products and Events
	 * @var string
	 */
	public $event_key = '_tribe_wooticket_for_event';

	/**
	 * Meta key that stores if an attendee has checked in to an event
	 * @var string
	 */
	public $checkin_key = '_tribe_wooticket_checkedin';

	/**
	 * Meta key that relates Attendees and Products
	 * @var string
	 */
	public $atendee_product_key = '_tribe_wooticket_product';

	/**
	 * Meta key that relates Attendees and Orders
	 * @var string
	 */
	public $atendee_order_key = '_tribe_wooticket_order';

	/**
	 * Meta key that relates Attendees and Events
	 * @var string
	 */
	public $atendee_event_key = '_tribe_wooticket_event';

	/**
	 * Meta key that holds the security code that's printed in the tickets
	 * @var string
	 */
	public $security_code = '_tribe_wooticket_security_code';

	/**
	 * Meta key that holds if an order has tickets (for performance)
	 * @var string
	 */
	public $order_has_tickets = '_tribe_has_tickets';

	/**
	 * Meta key that holds the name of a ticket to be used in reports if the Product is deleted
	 * @var string
	 */
	public $deleted_product = '_tribe_deleted_product_name';

	/**
	 * Holds an instance of the Tribe__Events__Tickets__Woo__Email class
	 * @var TribeWooTicketsEmail
	 */
	private $mailer = null;

	/**
	 * Instance of this class for use as singleton
	 */
	private static $instance;

	/**
	 * Current version of this plugin
	 */
	const VERSION = '3.9.3';

	/**
	 * Min required The Events Calendar version
	 */
	const REQUIRED_TEC_VERSION = '3.9.2';

	/**
	 * Min required WooCommerce version
	 */
	const REQUIRED_WC_VERSION = '2.1';


	/**
	 * Creates the instance of the class
	 *
	 * @static
	 * @return void
	 */
	public static function init() {
		self::$instance = self::get_instance();
	}

	/**
	 * Get (and instantiate, if necessary) the instance of the class
	 *
	 * @static
	 * @return Tribe__Events__Tickets__Woo__Main
	 */
	public static function get_instance() {
		if ( ! is_a( self::$instance, __CLASS__ ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		load_plugin_textdomain( 'tribe-wootickets', false, dirname( dirname( dirname( plugin_basename( __FILE__ ) ) ) ) . '/lang/' );

		/* Set up some parent's vars */
		$this->pluginName = 'WooCommerce';
		$this->pluginSlug = 'wootickets';
		$this->pluginPath = trailingslashit( EVENTS_TICKETS_WOO_DIR );
		$this->pluginDir  = trailingslashit( basename( $this->pluginPath ) );
		$this->pluginUrl  = trailingslashit( plugins_url( $this->pluginDir ) );

		parent::__construct();

		$this->hooks();
	}

	/**
	 * Registers all actions/filters
	 */
	public function hooks() {
		add_action( 'wp_loaded',                            array( $this, 'process_front_end_tickets_form'  ), 50     );
		add_action( 'init',                                 array( $this, 'register_wootickets_type'        )         );
		add_action( 'add_meta_boxes',                       array( $this, 'woocommerce_meta_box'            )         );
		add_action( 'before_delete_post',                   array( $this, 'handle_delete_post'              )         );
		add_action( 'woocommerce_order_status_completed',   array( $this, 'generate_tickets'                ), 12     );
		add_action( 'woocommerce_email_after_order_table',  array( $this, 'add_tickets_msg_to_email'        ), 10, 2  );

		// Enqueue styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 11 );

		add_filter( 'post_type_link',            array( $this, 'hijack_ticket_link'             ), 10, 4  );
		add_filter( 'woocommerce_email_classes', array( $this, 'add_email_class_to_woocommerce' )         );

		add_action( 'woocommerce_resend_order_emails_available', array( $this, 'add_resend_tickets_action' ) );
	}

	/**
	 * Enqueue the plugin stylesheet(s).
	 *
	 * @author caseypicker
	 * @since 3.9
	 * @return void
	 */
	function enqueue_styles() {
		//Only enqueue wootickets styles on singular event page
		if( is_singular( Tribe__Events__Main::POSTTYPE ) ) {
			$stylesheet_url = $this->pluginUrl . 'src/resources/css/wootickets.css';

			// Get minified CSS if it exists
			$stylesheet_url = Tribe__Events__Template_Factory::getMinFile( $stylesheet_url, true );

			// apply filters
			$stylesheet_url = apply_filters( 'tribe_wootickets_stylesheet_url', $stylesheet_url );

			wp_enqueue_style( 'TribeEventsWooTickets', $stylesheet_url, array(), apply_filters( 'tribe_events_wootickets_css_version', Tribe__Events__Tickets__Woo__Main::VERSION ) );

			//Check for override stylesheet
	    	$user_stylesheet_url = Tribe__Events__Templates::locate_stylesheet( 'tribe-events/wootickets/wootickets.css' );
	    	$user_stylesheet_url = apply_filters( 'tribe_events_wootickets_stylesheet_url', $user_stylesheet_url );

	    	//If override stylesheet exists, then enqueue it
	    	if ( $user_stylesheet_url ) {
				wp_enqueue_style( 'tribe-events-wootickets-override-style', $user_stylesheet_url );
			}
		}
	}

	/**
	 * When a user deletes a ticket (product) we want to store
	 * a copy of the product name, so we can show it in the
	 * attendee list for an event.
	 *
	 * @param $post_id
	 */
	function handle_delete_post( $post_id ) {
		$post_to_delete = get_post( $post_id );

		// Bail if it's not a Product
		if ( get_post_type( $post_to_delete ) !== 'product' )
			return;

		// Bail if the product is not a Ticket
		$event = get_post_meta( $post_id, $this->event_key, true );
		if ( $event === false )
			return;

		$attendees = $this->get_attendees( $event );

		foreach ( (array) $attendees as $attendee ) {
			if ( $attendee['product_id'] == $post_id ) {
				update_post_meta( $attendee['attendee_id'], $this->deleted_product, esc_html( $post_to_delete->post_title ) );
			}
		}
	}

	/**
	 * Add a custom email handler to WooCommerce email system
	 *
	 * @param array $classes of WC_Email objects
	 *
	 * @return array of WC_Email objects
	 */
	function add_email_class_to_woocommerce( $classes ) {
		$this->mailer                    = new Tribe__Events__Tickets__Woo__Email();
		$classes['Tribe__Events__Tickets__Woo__Email'] = $this->mailer;

		return $classes;
	}

	/**
	 * Register our custom post type
	 */
	function register_wootickets_type() {
		$args = array( 'label'           => 'Tickets',
		               'public'          => false,
		               'show_ui'         => false,
		               'show_in_menu'    => false,
		               'query_var'       => false,
		               'rewrite'         => false,
		               'capability_type' => 'post',
		               'has_archive'     => false,
		               'hierarchical'    => true, );

		register_post_type( $this->attendee_object, $args );
	}

	/**
	 * Generate and store all the attendees information for a new order.
	 * @param $order_id
	 */
	public function generate_tickets( $order_id ) {
		// Bail if we already generated the info for this order
		$done = get_post_meta( $order_id, $this->order_has_tickets, true );
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
			$event_id = get_post_meta( $product_id, $this->event_key, true );

			if ( ! empty( $event_id ) ) {

				$has_tickets = true;

				// Iterate over all the amount of tickets purchased (for this product)
				for ( $i = 0; $i < intval( $item['qty'] ); $i ++ ) {

					$attendee = array( 'post_status' => 'publish',
					                   'post_title'  => $order_id . ' | ' . $item['name'] . ' | ' . ( $i + 1 ),
					                   'post_type'   => $this->attendee_object,
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

	/**
	 * Generates the validation code that will be printed in the ticket.
	 * It purpose is to be used to validate the ticket at the door of an event.
	 *
	 * @param int $order_id
	 * @param int $attendee_id
	 *
	 * @return string
	 */
	private function generate_security_code( $order_id, $attendee_id ) {
		return substr( md5( $order_id . '_' . $attendee_id ), 0, 10 );
	}

	/**
	 * Adds a message to WooCommerce's order email confirmation.
	 * @param $order
	 */
	public function add_tickets_msg_to_email( $order ) {
		$order_items = $order->get_items();

		// Bail if the order is empty
		if ( empty( $order_items ) )
			return;

		$has_tickets = false;

		// Iterate over each product
		foreach ( (array) $order_items as $item ) {

			$product_id = isset( $item['product_id'] ) ? $item['product_id'] : $item['id'];

			// Get the event this tickets is for
			$event_id = get_post_meta( $product_id, $this->event_key, true );

			if ( ! empty( $event_id ) ) {
				$has_tickets = true;
				break;
			}
		}

		if ( ! $has_tickets ) return;

		echo '<br/>' . apply_filters( "wootickets_email_message", __( "You'll receive your tickets in another email.", "tribe-wootickets" ) );
	}



	/**
	 * Saves a given ticket (WooCommerce product)
	 *
	 * @param int                     $event_id
	 * @param Tribe__Events__Tickets__Ticket_Object $ticket
	 * @param array                   $raw_data
	 *
	 * @return bool
	 */
	public function save_ticket( $event_id, $ticket, $raw_data = array() ) {
		if ( empty( $ticket->ID ) ) {
			/* Create main product post */
			$args = array( 'post_status'  => 'publish',
			               'post_type'    => 'product',
			               'post_author'  => get_current_user_id(),
			               'post_excerpt' => $ticket->description,
			               'post_title'   => $ticket->name );

			$ticket->ID = wp_insert_post( $args );
			$new_ticket = true;

			update_post_meta( $ticket->ID, '_visibility', 'hidden' );
			update_post_meta( $ticket->ID, '_tax_status', 'taxable' );
			update_post_meta( $ticket->ID, '_tax_class', '' );
			update_post_meta( $ticket->ID, '_purchase_note', '' );
			update_post_meta( $ticket->ID, '_weight', '' );
			update_post_meta( $ticket->ID, '_length', '' );
			update_post_meta( $ticket->ID, '_width', '' );
			update_post_meta( $ticket->ID, '_height', '' );
			update_post_meta( $ticket->ID, '_downloadable', 'no' );
			update_post_meta( $ticket->ID, '_virtual', 'yes' );
			update_post_meta( $ticket->ID, '_sale_price_dates_from', '' );
			update_post_meta( $ticket->ID, '_sale_price_dates_to', '' );
			update_post_meta( $ticket->ID, '_product_attributes', array() );
			update_post_meta( $ticket->ID, '_sale_price', '' );
			update_post_meta( $ticket->ID, 'total_sales', 0 );

			// Relate event <---> ticket
			add_post_meta( $ticket->ID, $this->event_key, $event_id );

		} else {
			$args = array( 'ID'           => $ticket->ID,
			               'post_excerpt' => $ticket->description,
			               'post_title'   => $ticket->name );

			$ticket->ID = wp_update_post( $args );
		}

		if ( ! $ticket->ID ) {
			return false;
		}


		update_post_meta( $ticket->ID, '_regular_price', $ticket->price );

		// Do not update _price if the ticket is on sale: the user should edit this in the WC product editor
		if ( ! wc_get_product( $ticket->ID )->is_on_sale() || isset( $new_ticket ) ) {
			update_post_meta( $ticket->ID, '_price', $ticket->price );
		}

		if ( trim( $raw_data['ticket_woo_stock'] ) !== '' ) {
			$stock = (int) $raw_data['ticket_woo_stock'];
			$status = ( 0 < $stock ) ? 'instock' : 'outofstock';

			update_post_meta( $ticket->ID, '_stock', $stock );
			update_post_meta( $ticket->ID, '_stock_status', $status );
			update_post_meta( $ticket->ID, '_backorders', 'no' );
			update_post_meta( $ticket->ID, '_manage_stock', 'yes' );
			delete_transient( 'wc_product_total_stock_' . $ticket->ID );
		} else {
			update_post_meta( $ticket->ID, '_manage_stock', 'no' );
		}

		if ( isset( $raw_data['ticket_woo_sku'] ) )
			update_post_meta( $ticket->ID, '_sku', $raw_data['ticket_woo_sku'] );

		if ( isset( $ticket->start_date ) ) {
			update_post_meta( $ticket->ID, '_ticket_start_date', $ticket->start_date );
		} else {
			delete_post_meta( $ticket->ID, '_ticket_start_date' );
		}

		if ( isset( $ticket->end_date ) ) {
			update_post_meta( $ticket->ID, '_ticket_end_date', $ticket->end_date );
		} else {
			delete_post_meta( $ticket->ID, '_ticket_end_date' );
		}

		wp_set_object_terms( $ticket->ID, 'Ticket', 'product_cat', true );

		return true;
	}

	/**
	 * Deletes a ticket
	 *
	 * @param $event_id
	 * @param $ticket_id
	 *
	 * @return bool
	 */
	public function delete_ticket( $event_id, $ticket_id ) {
		// Ensure we know the event and product IDs (the event ID may not have been passed in)
		if ( empty( $event_id ) ) $event_id = get_post_meta( $ticket_id, $this->atendee_event_key, true );
		$product_id = get_post_meta( $ticket_id, $this->atendee_product_key, true );

		// Try to kill the actual ticket/attendee post
		$delete = wp_delete_post( $ticket_id, true );
		if ( is_wp_error( $delete ) ) return false;

		// Decrement the sales figure
		$sales = (int) get_post_meta( $product_id, 'total_sales', true );
		update_post_meta( $product_id, 'total_sales', --$sales );

		do_action( 'wootickets_ticket_deleted', $ticket_id, $event_id, $product_id );
		return true;
	}

	/**
	 * Returns all the tickets for an event
	 *
	 * @param int $event_id
	 *
	 * @return array
	 */
	protected function get_tickets( $event_id ) {
		$ticket_ids = $this->get_tickets_ids( $event_id );

		if ( ! $ticket_ids )
			return array();

		$tickets = array();

		foreach ( $ticket_ids as $post ) {
			$tickets[] = $this->get_ticket( $event_id, $post );
		}

		return $tickets;
	}

	/**
	 * Replaces the link to the WC product with a link to the Event in the
	 * order confirmation page.
	 *
	 * @param $post_link
	 * @param $post
	 * @param $leavename
	 * @param $sample
	 *
	 * @return string
	 */
	public function hijack_ticket_link( $post_link, $post, $leavename, $sample ) {
		if ( $post->post_type === 'product' ) {
			$event = get_post_meta( $post->ID, $this->event_key, true );
			if ( ! empty( $event ) ) {
				$post_link = get_permalink( $event );
			}
		}

		return $post_link;
	}


	/**
	 * Shows the tickets form in the front end
	 *
	 * @param $content
	 * @return void
	 */
	public function front_end_tickets_form( $content ) {
		$post = $GLOBALS['post'];

		if ( !empty($post->post_parent) ) {
			$post = get_post($post->post_parent);
		}

		$tickets = self::get_tickets( $post->ID );

		if ( empty( $tickets ) )
			return;

		include $this->getTemplateHierarchy( 'wootickets/tickets' );
	}

	/**
	 * Grabs the submitted front end tickets form and adds the products
	 * to the cart
	 */
	public function process_front_end_tickets_form() {
		global $woocommerce;

		if ( empty( $_REQUEST['wootickets_process'] ) || intval( $_REQUEST['wootickets_process'] ) !== 1 || empty( $_POST['product_id'] ) ) {
			return;
		}

		foreach ( (array) $_POST['product_id'] as $product_id ) {
			$quantity = isset( $_POST['quantity_' . $product_id] ) ? intval( $_POST['quantity_' . $product_id] ) : 0;
			$passed_validation 	= apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );

			if ( $passed_validation && $quantity > 0 ) {
				$woocommerce->cart->add_to_cart( $product_id, $quantity );
			}
		}
	}

	/**
	 * Gets an individual ticket
	 *
	 * @param $event_id
	 * @param $ticket_id
	 *
	 * @return null|Tribe__Events__Tickets__Ticket_Object
	 */
	public function get_ticket( $event_id, $ticket_id ) {
		if ( class_exists( 'WC_Product_Simple' ) ) {
			$product = new WC_Product_Simple( $ticket_id );
		} else {
			$product = new WC_Product( $ticket_id );
		}

		if ( ! $product )
			return null;

		$return       = new Tribe__Events__Tickets__Ticket_Object();
		$product_data = $product->get_post_data();
		$qty          = get_post_meta( $ticket_id, 'total_sales', true );

		$return->description    = $product_data->post_excerpt;
		$return->frontend_link  = get_permalink( $ticket_id );
		$return->ID             = $ticket_id;
		$return->name           = $product->get_title();
		$return->price          = $product->get_price();
		$return->regular_price  = $product->get_regular_price();
		$return->on_sale        = (bool) $product->is_on_sale();
		$return->provider_class = get_class( $this );
		$return->admin_link     = admin_url( sprintf( get_post_type_object( $product_data->post_type )->_edit_link . '&action=edit', $ticket_id ) );
		$return->stock          = $product->get_stock_quantity();
		$return->start_date     = get_post_meta( $ticket_id, '_ticket_start_date', true );
		$return->end_date       = get_post_meta( $ticket_id, '_ticket_end_date', true );
		$return->qty_sold       = $qty ? $qty : 0;
		$return->qty_pending    = $qty ? $this->count_incomplete_order_items( $ticket_id ) : 0;

		return $return;
	}

	/**
	 * Determine the total number of the specified ticket contained in orders which have not
	 * progressed to a "completed" status.
	 *
	 * Essentially this returns the total quantity of tickets held within orders that are
	 * "pending", "on hold" or "processing".
	 *
	 * @param $ticket_id
	 * @return int
	 */
	protected function count_incomplete_order_items( $ticket_id ) {
		$total = 0;

		$incomplete_orders = version_compare( '2.2', WooCommerce::instance()->version, '<=' )
			? $this->get_incomplete_orders( $ticket_id ) : $this->backcompat_get_incomplete_orders( $ticket_id );

		foreach ( $incomplete_orders as $order_id ) {
			$order = new WC_Order( $order_id );

			foreach ( (array) $order->get_items() as $order_item ) {
				if ( $order_item['product_id'] == $ticket_id ) {
					$total += (int) $order_item['qty'];
				}
			}
		}

		return $total;
	}

	protected function get_incomplete_orders( $ticket_id ) {
		global $wpdb;

		$order_state_sql = '';
		$incomplete_states = $this->incomplete_order_states();

		if ( ! empty( $incomplete_states ) )
			$order_state_sql = "AND posts.post_status IN ($incomplete_states)";

		$query = "
			SELECT
			    items.order_id
			FROM
			    {$wpdb->prefix}woocommerce_order_itemmeta AS meta
			        INNER JOIN
			    {$wpdb->prefix}woocommerce_order_items AS items ON meta.order_item_id = items.order_item_id
			        INNER JOIN
			    {$wpdb->prefix}posts AS posts ON items.order_id = posts.ID
			WHERE
			    (meta_key = '_product_id'
			        AND meta_value = %d
			        $order_state_sql );
		";

		return (array) $wpdb->get_col( $wpdb->prepare( $query, $ticket_id ) );
	}

	/**
	 * Returns a comma separated list of term IDs representing incomplete order
	 * states.
	 *
	 * @return string
	 */
	protected function incomplete_order_states() {
		$considered_incomplete = (array) apply_filters( 'wootickets_incomplete_order_states', array(
			'wc-on-hold',
			'wc-pending',
			'wc-processing'
		) );

		foreach ( $considered_incomplete as &$incomplete )
			$incomplete = '"' . $incomplete . '"';

		return join( ',', $considered_incomplete );
	}

	/**
	 * Retrieves the IDs of any orders containing the specified product (ticket_id) so
	 * long as the order is considered incomplete.
	 *
	 * @deprecated remove in 4.0 (provides compatibility with pre-2.2 WC releases)
	 *
	 * @param $ticket_id
	 *
	 * @return array
	 */
	protected function backcompat_get_incomplete_orders( $ticket_id ) {
		global $wpdb;
		$total = 0;

		$incomplete_states = $this->backcompat_incomplete_order_states();
		if ( empty( $incomplete_states ) ) return array();

		$query = "
			SELECT
			    items.order_id
			FROM
			    {$wpdb->prefix}woocommerce_order_itemmeta AS meta
			        INNER JOIN
			    {$wpdb->prefix}woocommerce_order_items AS items ON meta.order_item_id = items.order_item_id
			        INNER JOIN
			    {$wpdb->prefix}term_relationships AS relationships ON items.order_id = relationships.object_id
			WHERE
			    (meta_key = '_product_id'
			        AND meta_value = %d )
			        AND (relationships.term_taxonomy_id IN ( $incomplete_states ));
		";

		return (array) $wpdb->get_col( $wpdb->prepare( $query, $ticket_id ) );
	}

	/**
	 * Returns a comma separated list of term IDs representing incomplete order
	 * states.
	 *
	 * @deprecated remove in 4.0 (provides compatibility with pre-2.2 WC releases)
	 *
	 * @return string
	 */
	protected function backcompat_incomplete_order_states() {
		$considered_incomplete = (array) apply_filters( 'wootickets_incomplete_order_states', array(
			'pending',
			'on-hold',
			'processing'
		) );

		$incomplete_states = array();

		foreach ( $considered_incomplete as $term_slug ) {
			$term = get_term_by( 'slug', $term_slug, 'shop_order_status' );
			if ( false === $term ) continue;
			$incomplete_states[] = (int) $term->term_id;
		}

		return join( ',', $incomplete_states );
	}

	/**
	 * Accepts a reference to a product (either an object or a numeric ID) and
	 * tests to see if it functions as a ticket: if so, the corresponding event
	 * object is returned. If not, boolean false is returned.
	 *
	 * @param $ticket_product
	 *
	 * @return bool|WP_Post
	 */
	public function get_event_for_ticket( $ticket_product ) {
		if ( is_object( $ticket_product ) && isset( $ticket_product->ID ) ) {
			$ticket_product = $ticket_product->ID;
		}

		if ( null === ( $product = get_post( $ticket_product ) ) ) {
			return false;
		}

		if ( '' === ( $event = get_post_meta( $ticket_product, $this->event_key, true ) ) ) {
			return false;
		}

		if ( Tribe__Events__Main::POSTTYPE === get_post_type( $event ) ) {
			return get_post( $event );
		}

		return false;
	}

	/**
	 * Get all the attendees for an event. It returns an array with the
	 * following fields:
	 *
	 *     order_id
	 *     order_status
	 *     purchaser_name
	 *     purchaser_email
	 *     ticket
	 *     attendee_id
	 *     security
	 *     product_id
	 *     check_in
	 *     provider
	 *
	 * @param $event_id
	 * @return array
	 */
	protected function get_attendees( $event_id ) {
		$attendees_query = new WP_Query( array(
			'posts_per_page' => - 1,
			'post_type'      => $this->attendee_object,
			'meta_key'       => $this->atendee_event_key,
			'meta_value'     => $event_id,
			'orderby'        => 'ID',
			'order'          => 'DESC'
		) );

		if ( ! $attendees_query->have_posts() ) return array();
		$attendees = array();

		foreach ( $attendees_query->posts as $attendee ) {
			$order_id   = get_post_meta( $attendee->ID, $this->atendee_order_key, true );
			$checkin    = get_post_meta( $attendee->ID, $this->checkin_key, true );
			$security   = get_post_meta( $attendee->ID, $this->security_code, true );
			$product_id = get_post_meta( $attendee->ID, $this->atendee_product_key, true );
			$name       = get_post_meta( $order_id, '_billing_first_name', true ) . ' ' . get_post_meta( $order_id, '_billing_last_name', true );
			$email      = get_post_meta( $order_id, '_billing_email', true );

			if ( empty( $product_id ) ) continue;

			$order_status = $this->order_status( $order_id );
			$order_status_label = __( $order_status, 'woocommerce' );
			$order_warning = false;

			// Warning flag for refunded, cancelled and failed orders
			switch ( $order_status ) {
				case 'refunded': case 'cancelled': case 'failed':
					$order_warning = true;
				break;
			}

			// Warning flag where the order post was trashed
			if ( ! empty( $order_status ) && get_post_status( $order_id ) == 'trash' ) {
				$order_status_label = sprintf( __( 'In trash (was %s)', 'tribe-wootickets' ), $order_status_label );
				$order_warning = true;
			}

			// Warning flag where the order has been completely deleted
			if ( empty( $order_status ) && ! get_post( $order_id ) ) {
				$order_status_label = __( 'Deleted', 'tribe-wootickets' );
				$order_warning = true;
			}

			$product = get_post( $product_id );
			$product_title = ( ! empty( $product ) ) ? $product->post_title : get_post_meta( $attendee->ID, $this->deleted_product, true ) . ' ' . __( '(deleted)', 'wootickets' );

			$attendees[] = array(
				'order_id'           => $order_id,
				'order_status'       => $order_status,
				'order_status_label' => $order_status_label,
				'order_warning'      => $order_warning,
				'purchaser_name'     => $name,
				'purchaser_email'    => $email,
				'ticket'             => $product_title,
				'attendee_id'        => $attendee->ID,
				'security'           => $security,
				'product_id'         => $product_id,
				'check_in'           => $checkin,
				'provider'           => __CLASS__ );
		}

		return $attendees;
	}

	/**
	 * Returns the order status.
	 *
	 * @todo remove safety check against existence of wc_get_order_status_name() in future release
	 *       (exists for backward compatibility with versions of WC below 2.2)
	 *
	 * @param $order_id
	 * @return string
	 */
	protected function order_status( $order_id ) {
		if ( ! function_exists( 'wc_get_order_status_name' ) ) return __( 'Unknown', 'tribe-wootickets' );
		return wc_get_order_status_name( get_post_status( $order_id ) );
	}

	/**
	 * Marks an attendee as checked in for an event
	 *
	 * @param $attendee_id
	 *
	 * @return bool
	 */
	public function checkin( $attendee_id ) {
		update_post_meta( $attendee_id, $this->checkin_key, 1 );
		do_action( 'wootickets_checkin', $attendee_id );
		return true;
	}

	/**
	 * Marks an attendee as not checked in for an event
	 *
	 * @param $attendee_id
	 *
	 * @return bool
	 */
	public function uncheckin( $attendee_id ) {
		delete_post_meta( $attendee_id, $this->checkin_key );
		do_action( 'wootickets_uncheckin', $attendee_id );
		return true;
	}

	/**
	 * Add the extra options in the admin's new/edit ticket metabox
	 *
	 * @param $event_id
	 * @param $ticket_id
	 * @return void
	 */
	public function do_metabox_advanced_options( $event_id, $ticket_id ) {
		$url = $stock = $sku = '';

		if ( ! empty( $ticket_id ) ) {
			$ticket = $this->get_ticket( $event_id, $ticket_id );
			if ( ! empty( $ticket ) ) {
				$stock = $ticket->stock;
				$sku   = get_post_meta( $ticket_id, '_sku', true );
			}
		}

		include $this->pluginPath . 'src/admin-views/metabox-advanced.php';
	}

	/**
	 * Links to sales report for all tickets for this event.
	 *
	 * @param $event_id
	 * @return string
	 */
	public function get_event_reports_link( $event_id ) {
		$ticket_ids = (array) $this->get_tickets_ids( $event_id );
		if ( empty( $ticket_ids ) ) return '';

		$query = array(
			'page' => 'wc-reports',
			'tab' => 'orders',
			'report' => 'sales_by_product',
			'product_ids' => $ticket_ids
		);

		$report_url = add_query_arg( $query, admin_url( 'admin.php' ) );
		return '<small> <a href="' . esc_url( $report_url ) . '">' . __( 'Event sales report', 'tribe-wootickets' ) . '</a> </small>';
	}

	/**
	 * Links to the sales report for this product.
	 *
	 * @param $event_id
	 * @param $ticket_id
	 * @return string
	 */
	public function get_ticket_reports_link( $event_id, $ticket_id ) {
		if ( empty( $ticket_id ) )
			return '';

		$query = array(
			'page' => 'wc-reports',
			'tab' => 'orders',
			'report' => 'sales_by_product',
			'product_ids' => $ticket_id
		);

		$report_url = add_query_arg( $query, admin_url( 'admin.php' ) );
		return '<span><a href="' . esc_url( $report_url ) . '">' . __( 'Report', 'tribe-wootickets' ) . '</a></span>';
	}

	/**
	 * Registers a metabox in the WooCommerce product edit screen
	 * with a link back to the product related Event.
	 *
	 */
	public function woocommerce_meta_box() {
		$event_id = get_post_meta( get_the_ID(), $this->event_key, true );

		if ( ! empty( $event_id ) )
			add_meta_box( 'wootickets-linkback', 'Event', array( $this,
				'woocommerce_meta_box_inside', ), 'product', 'normal', 'high' );
	}

	/**
	 * Contents for the metabox in the WooCommerce product edit screen
	 * with a link back to the product related Event.
	 */
	public function woocommerce_meta_box_inside() {
		$event_id = get_post_meta( get_the_ID(), $this->event_key, true );
		if ( ! empty( $event_id ) )
			echo sprintf( '%s <a href="%s">%s</a>', __( 'This is a ticket for the event:', 'tribe-wootickets' ), esc_url( get_edit_post_link( $event_id ) ), esc_html( get_the_title( $event_id ) ) );
	}

	/**
	 * Get's the WC product price html
	 *
	 * @param int|object $product
	 *
	 * @return string
	 */
	public function get_price_html( $product ) {
		if ( is_numeric( $product ) ) {
			if ( class_exists( 'WC_Product_Simple' ) ) {
				$product = new WC_Product_Simple( $product );
			} else {
				$product = new WC_Product( $product );
			}
		}

		if ( ! method_exists( $product, 'get_price_html' ) )
			return '';

		return $product->get_price_html();
	}

	public function get_tickets_ids( $event_id ) {
		if ( is_object( $event_id ) )
			$event_id = $event_id->ID;

		$query = new WP_Query( array( 'post_type'      => 'product',
		                              'meta_key'       => $this->event_key,
		                              'meta_value'     => $event_id,
		                              'meta_compare'   => '=',
		                              'posts_per_page' => - 1,
		                              'fields'         => 'ids',
		                              'post_status'    => 'publish', ) );

		return $query->posts;
	}

	/**
	 * Adds an action to resend the tickets to the customer
	 * in the WooCommerce actions dropdown, in the order edit screen.
	 *
	 * @param $emails
	 *
	 * @return array
	 */
	public function add_resend_tickets_action( $emails ) {
		$order = get_the_ID();

		if ( empty( $order ) )
			return $emails;

		$has_tickets = get_post_meta( $order, $this->order_has_tickets, true );

		if ( ! $has_tickets )
			return $emails;

		$emails[] = 'wootickets';
		return $emails;
	}
}
