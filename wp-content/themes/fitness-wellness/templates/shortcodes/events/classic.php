<?php
	$tag = (int)$count == 1 ? 'span' : 'div';
?>
<?php foreach($events as $event): ?>
	<div class="classic-event-wrapper">
		<div class="row">
			<div class="grid-1-3">
				<?php if ( ! empty( $lead_text ) ): ?>
					<h4 class="lead-wrapper"><?php echo wpv_shortcode_icon( array( 'name' => 'calendar2' ) ) // xss ok ?> <span class="lead"><?php echo $lead_text //xss ok ?></span></h4>
				<?php endif ?>
				<h3 class="event-title"><a href="<?php tribe_event_link( $event ) ?>" title="<?php esc_attr_e( 'Read More', 'fitness-wellness' ) ?>"><?php echo $event->post_title // xss ok ?></a></h3>
			</div>
			<div class="grid-2-3">
				<span class="wpv-countdown single-event style-<?php echo esc_attr( $style ) ?> layout-<?php echo esc_attr( $layout ) ?>" data-until="<?php echo esc_attr( strtotime( tribe_get_start_date( $event, false, 'Y-m-d H:i:s' ) . ' UTC' ) - ( (int)get_option( 'gmt_offset' ) * 3600 ) ) ?>" data-done="<?php echo esc_attr( $ongoing ) ?>">
					<span class="wpvc-days"><span class="value">&ndash;</span> <span class="word"><?php _e( 'Days', 'fitness-wellness' ) ?></span></span>
					<span class="wpvc-hours"><span class="value">&ndash;</span> <span class="word"><?php _e( 'Hours', 'fitness-wellness' ) ?></span></span>
					<div class="split"></div>
					<span class="wpvc-minutes"><span class="value">&ndash;</span> <span class="word"><?php _e( 'Minutes', 'fitness-wellness' ) ?></span></span>
					<span class="wpvc-seconds"><span class="value">&ndash;</span> <span class="word"><?php _e( 'Seconds', 'fitness-wellness' ) ?></span></span>
				</span>
				<div class="split"></div>
				<a href="<?php tribe_event_link( $event ) ?>" title="<?php esc_attr( $read_more_text ) ?>" class="vamtam-button button accent1 hover-accent1"><span class="btext"><?php echo $read_more_text // xss ok ?></span></a>
				<?php if ( ! empty( $view_all_text ) && ! empty( $view_all_link ) ): ?>
					<span class="view-all-wrapper">
						<?php _e( 'or', 'fitness-wellness' ); ?>
						<a href="<?php echo $view_all_link // xss ok ?>" title="<?php esc_attr( $view_all_text ) ?>" class="view-all-link"><?php echo $view_all_text // xss ok ?></a>
					</span>
				<?php endif ?>
			</div>
		</div>
	</div>
<?php endforeach; ?>