<table class="eventtable ticket_list eventForm">
	<?php
	global $wpdb;
	$provider = null;
	$count    = 0;
	global $post;
	if ( $post ) {
		$post_id = get_the_ID();
	} else {
		$post_id = $_POST['post_ID'];
	}

	$modules = Tribe__Events__Tickets__Tickets::modules();

	foreach ( $tickets as $ticket ) {

		$controls     = array();
		$provider     = $ticket->provider_class;
		$provider_obj = call_user_func( array( $provider, 'get_instance' ) );


		$controls[] = sprintf( "<span><a href='#' attr-provider='%s' attr-ticket-id='%s' id='ticket_edit_%s' class='ticket_edit'>" . __( 'Edit', 'tribe-events-calendar' ) . '</a></span>', $ticket->provider_class, $ticket->ID, $ticket->ID );
		$controls[] = sprintf( "<span><a href='#' attr-provider='%s' attr-ticket-id='%s' id='ticket_delete_%s' class='ticket_delete'>" . __( 'Delete', 'tribe-events-calendar' ) . '</a></span>', $ticket->provider_class, $ticket->ID, $ticket->ID );
		if ( $ticket->admin_link ) {
			$controls[] = sprintf( "<span><a href='%s'>" . __( 'Edit in %s', 'tribe-events-calendar' ) . '</a></span>', esc_url( $ticket->admin_link ), $modules[ $ticket->provider_class ] );
		}
		if ( $ticket->frontend_link && get_post_status( $post_id ) == 'publish' ) {
			$controls[] = sprintf( "<span><a href='%s'>" . __( 'View', 'tribe-events-calendar' ) . '</a></span>', esc_url( $ticket->frontend_link ) );
		}

		$report = $provider_obj->get_ticket_reports_link( $post_id, $ticket->ID );
		if ( $report ) {
			$controls[] = $report;
		}

		if ( ( $ticket->provider_class !== $provider ) || $count == 0 ) :
			?>
			<td colspan="4" class="titlewrap">
				<h4 class="tribe_sectionheader"><?php echo esc_html( $modules[ $ticket->provider_class ] ); ?>
					<?php echo $provider_obj->get_event_reports_link( $post_id ); ?>
					<small>&nbsp;|&nbsp;</small>
					<?php echo sprintf( "<small><a title='" . esc_attr__( 'See who purchased tickets to this event', 'tribe-events-calendar' ) . "' href='%s'>%s</a></small>", esc_url( admin_url( sprintf( 'edit.php?post_type=%s&page=%s&event_id=%d', Tribe__Events__Main::POSTTYPE, Tribe__Events__Tickets__Tickets_Pro::$attendees_slug, $post_id ) ) ), __( "Attendees", 'tribe-events-calendar' ) ); ?>
				</h4>
			</td>
		<?php endif; ?>
		<tr>
			<td>
				<p class="ticket_name"><?php
					echo sprintf( "<a href='#' attr-provider='%s' attr-ticket-id='%s' class='ticket_edit'>%s</a></span>", esc_attr( $ticket->provider_class ), esc_attr( $ticket->ID ), esc_html( $ticket->name ) );
					?></p>

				<div class="ticket_controls">
					<?php echo join( ' | ', $controls ); ?>
				</div>

			</td>

			<td valign="top">
				<?php echo $provider_obj->get_price_html( $ticket->ID ); ?>
			</td>

			<td nowrap="nowrap">
			<?php
			$stock = $ticket->stock;
			$table_name = 'wp_mng_free_tickets';
			$query = $wpdb->get_results("SELECT * FROM $table_name where event_id = $post_id");
			$tname = $ticket->name; 
			//echo "<pre>"; print_r($query);
			if(!empty($query)) {

            foreach ($query as $result) {

            		$array[]=$result->total_tickets;        
        	  }
			if($tname == 'General Admission') {

			$sold  = ! empty ( $ticket->qty_sold+array_sum($array) ) ?  $ticket->qty_sold + array_sum($array) : 0;

			} else { $sold  = ! empty ( $ticket->qty_sold ) ? $ticket->qty_sold: 0; }


			} else { $sold  = ! empty ( $ticket->qty_sold ) ? $ticket->qty_sold: 0; }

			unset($array);

			if ( empty( $stock ) && $stock !== 0 ) : ?>
				<?php echo sprintf( __( 'Sold %d', 'tribe-events-calendar' ), esc_html( $sold ) ); ?>
			<?php else : ?>
				<?php echo sprintf( __( 'Sold %d of %d', 'tribe-events-calendar' ), esc_html( $sold ), esc_html( $sold + $stock ) ); ?>
			<?php endif; ?>
			</td>
			<td width="40%" valign="top">
				<?php echo esc_html( $ticket->description ); ?>
			</td>
		</tr>
		<?php
		$count ++;
	} ?>
</table>
