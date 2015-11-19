<?php

class WCCH_Show_History {

	/**
	 * Fire up the engines.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'admin_init', array( $this, 'register_metaboxes' ) );
		add_filter( 'woocommerce_shop_order_search_fields', array( $this, 'search_customer_history' ) );
		add_action( 'woocommerce_email_order_meta', array( $this, 'email_customer_history' ), 10, 2 );

	}

	/**
	 * Register "Customer Browsing History" metabox for Order posts.
	 *
	 * @since 1.0.0
	 */
	public function register_metaboxes() {
		add_meta_box( 'woocommerce-customer-browsing-history', __( 'Customer Browsing History', 'woocommerce-customer-history' ), array( $this, 'render_browsing_history' ), 'shop_order', 'normal', 'default' );
		add_meta_box( 'woocommerce-customer-purchase-history', __( 'Customer Purchase History', 'woocommerce-customer-history' ), array( $this, 'render_purchase_history' ), 'shop_order', 'normal', 'default' );
	} /* register_metaboxes() */

	/**
	 * Output browsing history for metabox and email.
	 *
	 * @since 1.0.0
	 *
	 * @param object $order Order post object.
	 */
	public function render_browsing_history( $order = 0 ) {

		// If we don't have an actual order object, bail now
		if ( ! is_object( $order ) )
			return false;

		// Grab browsing history
		$browsing_history = get_post_meta( $order->ID, '_user_history', true );

		// Initialize output
		$output = '';

		// Explain this table
		$output .= sprintf( '<p>%s</p>', __( 'Below is every page the customer visited, in order, prior to completing this transaction.', 'woocommerce-customer-history' ) );

		// Output user's history (if collected)
		if ( is_array( $browsing_history ) ) {

			// Strip off the referring URL
			$referrer = array_shift( $browsing_history );

			// Output the referrer
			$output .= '<p>';
			$output .= sprintf( __( '<strong>Referrer:</strong> %s', 'woocommerce-customer-history' ), preg_replace( '/(Referrer:\s)?(http.+)?/', '<a href="$2" target="_blank">$2</a>', $referrer['url'] ) );
			$output .= '</p>';

			// If referrer was a search engine, output the query string the user used
			$search_history = rzen_wcch_get_users_search_query( $referrer['url'] );
			if ( $search_history ) {
				$output .= '<p>' . sprintf( __( 'Original search query: %s', 'woocommerce-customer-history' ), '<strong><mark>' . $search_history . '</mark></strong>' ) . '</p>';
			}

			// Output full browsing history
			$output .= '<table style="width:100%; border:1px solid #eee;" cellpadding="0" cellspacing="0" border="0">';
			$output .= '<tr>';
			$output .= '<th style="background:#333; color:#fff; text-align:left; padding:10px;">' . __( 'URL', 'woocommerce-customer-history' ) . '</th>';
			$output .= '<th style="background:#333; color:#fff; text-align:left; padding:10px;">' . __( 'Timestamp', 'woocommerce-customer-history' ) . '</th>';
			$output .= '<th style="background:#333; color:#fff; text-align:right; padding:10px;">' . __( 'Time on Page', 'woocommerce-customer-history' ) . '</th>';
			$output .= '<th style="background:#333; color:#fff; text-align:right; padding:10px;">' . __( 'Total', 'woocommerce-customer-history' ) . '</th>';
			$output .= '</tr>';

			foreach ( $browsing_history as $key => $history ) {

				// Don't output the very last item.
				// This is always the internal 'Order Complete' item.
				if ( end( $browsing_history ) == $history )
					continue;

				$alt = $key % 2 ? ' style="background: #f7f7f7;"' : '';
				$output .= '<tr' . $alt . '>';
				$output .= '<td style="text-align:left; padding:10px;">' . ( $key + 1 ) . '. <a href="' . esc_url( $history['url'] ) . '" target="_blank">' . esc_url( $history['url'] ) . '</a></td>';
				if ( $history['time'] ) {
					$output .= '<td style="text-align:left; padding:10px;">' . date( 'Y/m/d \&\n\d\a\s\h\; h:i:sa', ( $history['time'] + get_option( 'gmt_offset' ) * 3600 ) ) . '</td>';
				} else {
					$output .= '<td style="text-align:left; padding:10px;">' . __( 'N/A', 'woocommerce-customer-history' ) . '</td>';
				}
				$next = isset( $browsing_history[ $key + 1 ] ) ? $browsing_history[ $key + 1 ] : end( $browsing_history );
				$output .= '<td style="text-align:right; padding:10px;">' . rzen_wcch_calculate_elapsed_time( $history['time'], $next['time'] ) . '</td>';
				$output .= '<td style="text-align:right; padding:10px;">' . rzen_wcch_calculate_elapsed_time( $referrer['time'], $next['time'] ) . '</td>';
				$output .= '</tr>';
			}
			$output .= '</table>';

			// Output total elapsed time
			$final_entry = end( $browsing_history );
			$output .= '<p>';
			$output .= sprintf( __( '<strong>Total Time Elapsed:</strong> %s', 'woocommerce-customer-history' ), rzen_wcch_calculate_elapsed_time( $referrer['time'], $final_entry['time'] ) );
			$output .= '</p>';

		// Otherwise, output that no history was collected
		} else {
			$output .= '<p><em>' . __( 'No page history collected.', 'woocommerce-customer-history' ) . '</em></p>';
		}

		// Echo our output
		echo $output;

	} /* render_browsing_history() */

	/**
	 * Output customer purchase history.
	 *
	 * @since 1.0.0
	 *
	 * @param object $order Order post object.
	 */
	public function render_purchase_history( $order = 0 ) {

		// If no order object is available, bail here
		if ( ! is_object( $order ) )
			return false;

		// Get the customer ID
		$customer_id = get_post_meta( $order->ID, '_customer_user', true );

		// If no customer ID, bail here
		if ( ! absint( $customer_id ) )
			return false;

		// Setup important variables
		$lifetime_total = 0;
		$count          = 1;
		$orders         = get_posts( array(
			'numberposts' => -1,
			'meta_key'    => '_customer_user',
			'meta_value'  => absint( $customer_id ),
			'post_type'   => 'shop_order',
			'post_status' => function_exists( 'wc_get_order_statuses' ) ? array_keys( wc_get_order_statuses() ) : array( 'publish' ),
			'order'       => 'ASC',
		) );

		// Initialize output
		$output = '';
		$output .= '<div class="products-header spacing-wrapper clearfix"></div>';
		$output .= '<div class="spacing-wrapper clearfix">';

		// Include a header
		$output .= sprintf( '<p>%s</p>', __( 'Below is every order this customer has completed, including this one (highlighted).', 'woocommerce-customer-history' ) );

		// Output purhcase history table
		$output .= '<table style="width:100%; border:1px solid #eee;" cellpadding="0" cellspacing="0" border="0">';
		$output .= '<tr>';
		$output .= '<th style="background:#333; color:#fff; text-align:left; padding:10px;">' . __( 'Order Number', 'woocommerce-customer-history' ) . '</th>';
		$output .= '<th style="background:#333; color:#fff; text-align:left; padding:10px;">' . __( 'Order Date', 'woocommerce-customer-history' ) . '</th>';
		$output .= '<th style="background:#333; color:#fff; text-align:left; padding:10px;">' . __( 'Order Status', 'woocommerce-customer-history' ) . '</th>';
		$output .= '<th style="background:#333; color:#fff; text-align: right; padding:10px;">' . __( 'Order Total', 'woocommerce-customer-history' ) . '</th>';
		$output .= '</tr>';
		if ( ! empty( $orders ) ) {
			foreach ( $orders as $key => $purchase ) {
				$purchase_order = new WC_Order( $purchase );
				$alt = $key % 2 ? ' style="background: #f7f7f7;"' : '';
				$current = $purchase_order->id == $order->ID ? ' style="background: #ffc; font-weight: bold"' : $alt;
				$output .= '<tr' . $current . '>';
				$output .= '<td style="text-align:left; padding:10px;">' . ( $key + 1 ) . '. <a href="' . admin_url( "post.php?post={$purchase_order->id}&action=edit" ) . '">' . sprintf( __( 'Order %s', 'woocommerce-customer-history' ), $purchase_order->get_order_number() ) . '</a></td>';
				$output .= '<td style="text-align:left; padding:10px;">' . date('Y-m-d \&\n\d\a\s\h\; h:ia', strtotime( $purchase_order->order_date ) ) . '</td>';
				$output .= '<td style="text-align:left; padding:10px;">' . ucfirst( $purchase_order->status ) . '</td>';
				$output .= '<td style="text-align:right; padding:10px;">' . $purchase_order->get_formatted_order_total() . '</td>';
				$output .= '</tr>';

				// If order isn't cancelled, refunded, failed or pending, include its total
				if ( in_array( $purchase->post_status, array( 'wc-completed', 'wc-processing', 'wc-on-hold' ) ) ) {
					$lifetime_total += absint( $purchase_order->order_total );
				}
			}
		}
		$output .= '</table>';

		// Output total lifetime value
		$output .= '<p>';
		$output .= sprintf( __( '<strong>Actual Lifetime Customer Value:</strong> %s', 'woocommerce-customer-history' ), '<span style="color:#7EB03B; font-size:1.2em; font-weight:bold;">' . woocommerce_price( $lifetime_total ) . '</span>' );
		$output .= '</p>';

		// Close out the container
		$output .= '</div>';

		echo $output;

	} /* render_purchase_history() */

	/**
	 * Display customer history in admin notification emails.
	 *
	 * @since 1.0.0
	 *
	 * @param object $order    Order post object.
	 * @param bool   $is_admin True for admin emails, otherwise false.
	 */
	public function email_customer_history( $order = 0, $is_admin_email = false ) {

		// Only continue if this is an admin email
		$settings = get_option( 'woocommerce_wcch_settings', null );
		if ( true === $is_admin_email && isset( $settings['wcch_admin_email_enabled'] ) && 'yes' === $settings['wcch_admin_email_enabled'] ) {
			$order->ID = $order->id; // store post ID to uppercase variable

			echo '<h2>' . __( 'Customer Browsing History', 'woocommerce-customer-history' ) . '</h2>';
			$this->render_browsing_history( $order );

			echo '<h2>' . __( 'Customer Purchase History', 'woocommerce-customer-history' ) . '</h2>';
			$this->render_purchase_history( $order );
		}

	} /* email_customer_history() */

	/**
	 * Add customer history to searchable fields.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $fields Searchable meta keys.
	 * @return array         Updated meta keys.
	 */
	public function search_customer_history( $fields ) {
		$fields[] = '_user_history';
		return $fields;
	} /* search_customer_history() */

} /* WCCH_Show_History */
return new WCCH_Show_History;
