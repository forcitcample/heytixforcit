<section class="wpv-tribe-vertical-events">
	<header class="top-title"><?php _e( 'What\'s Next', 'fitness-wellness' ) ?></header>
	<?php foreach($events as $event): ?>
		<div class="wpv-event-row style-<?php echo esc_attr( $style ) ?> layout-<?php echo esc_attr( $layout ) ?>">
			<?php
				$start = strtotime($event->EventStartDate);
				$day = date('d', $start);
				$month = date_i18n('M', $start);
			?>
			<a href="<?php tribe_event_link($event) ?>" title="<?php esc_attr_e('Read More', 'fitness-wellness') ?>">
				<div class="date cell">
					<div class="day"><?php echo $day // xss ok ?></div>
					<div class="month"><?php echo $month // xss ok ?></div>
				</div>
				<h5 class="title cell"><?php echo $event->post_title // xss ok ?></h5>
				<div class="price cell"><?php echo tribe_get_cost( $event, true ); // xss ok ?></div>
			</a>
		</div>
	<?php endforeach; ?>
	<header class="view-all">
		<a href="<?php echo tribe_get_events_link() ?>" title="<?php esc_attr_e( 'Upcoming Events', 'fitness-wellness' ); ?>"><?php _e( 'View Full Timetable and Book &rarr;', 'fitness-wellness' ); ?></a>
	</header>
</section>