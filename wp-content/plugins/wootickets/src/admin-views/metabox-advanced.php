
<tr class="<?php $this->tr_class(); ?>">
	<td><label for="ticket_woo_stock"><?php esc_html_e( 'Stock:', 'tribe-wootickets' ); ?></label></td>
	<td>
		<input type='text' id='ticket_woo_stock' name='ticket_woo_stock' class="ticket_field" size='7'
		       value='<?php echo esc_attr( $stock ); ?>'/>
		<p class="description"><?php esc_html_e( "(Total available # of this ticket type. Once they're gone, ticket type is sold out)", 'tribe-wootickets' ); ?></p>
	</td>
</tr>

<tr class="<?php $this->tr_class(); ?>">
	<td><label for="ticket_woo_sku"><?php _e( 'SKU:', 'tribe-wootickets' ); ?></label></td>
	<td>
		<input type='text' id='ticket_woo_sku' name='ticket_woo_sku' class="ticket_field" size='7'
		       value='<?php echo esc_attr( $sku ); ?>'/>
		<p class="description"><?php esc_html_e( "(A unique identifying code for each ticket type you're selling)", 'tribe-wootickets' ); ?></p>
	</td>

</tr>
<?php
	if ( class_exists( 'Tribe__Events__Pro__Main' ) ) {
		?>
		<tr class="<?php $this->tr_class(); ?>">
			<td colspan="2" class="tribe_sectionheader updated">
				<p class="warning"><?php _e( 'Currently, WooTickets will only show up on the frontend once per full event. For PRO users this means the same ticket will appear across all events in the series. Please configure your events accordingly.', 'tribe-wootickets' ); ?></p>
			</td>
		</tr>
		<?php
	}
	?>

