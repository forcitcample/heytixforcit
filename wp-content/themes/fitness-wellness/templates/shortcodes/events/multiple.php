<div class="row wpv-tribe-multiple-events style-<?php echo $style ?>">
	<?php foreach($events as $i => $event): ?>
		<?php setup_postdata( $GLOBALS['post'] =& $event ) ?>
		<div class="wpv-grid grid-1-<?php echo $columns ?>">
			<div class="event-wrapper">
				<?php
					$start = strtotime($event->EventStartDate);
					$end = strtotime($event->EventEndDate);
					$day = date('d', $start);
					$month = date_i18n('M', $start);

					$stime = date(get_option('time_format'), $start);
					$etime = date(get_option('time_format'), $end);
				?>
				<a href="<?php tribe_event_link($event) ?>" title="<?php esc_attr_e('Read More', 'fitness-wellness') ?>">
					<div class="thumbnail">
						<?php echo get_the_post_thumbnail( $event->ID, 'portfolio-masonry-' . $columns ); // xss ok ?>
						<div class="date">
							<div class="date-inner">
								<div class="day"><?php echo $day ?></div>
								<div class="month"><?php echo $month ?></div>
							</div>
						</div>
					</div>
					<h4 class="title entry-title"><?php echo $event->post_title ?></h4>
				</a>
				<div class="when-where">
					<div>
						<?php echo wpv_shortcode_icon( array( 'name' => 'theme-clock' ) ) ?>
						<?php if ( ! tribe_event_is_all_day( $event->ID ) ): ?>
							<?php echo $stime ?> <?php if ( $stime !== $etime ) echo '&mdash; ' . $etime ?>
						<?php else: ?>
							<?php _e( 'All Day', 'fitness-wellness' ) ?>
						<?php endif ?>
					</div>
					<div><?php echo wpv_shortcode_icon( array( 'name' => 'theme-pointer' ) ) ?> <?php
						if( class_exists( 'Tribe__Events__Pro__Templates__Single_Venue' ) ) {
							tribe_get_venue_link( $event->ID, true );
						} else {
							echo tribe_get_venue( $event->ID );
						}
					?>
					</div>
				</div>
				<div class="description">
					<?php the_excerpt(); ?>
				</div>
			</div>
		</div>
		<?php if($i % $columns === $columns - 1 && $i < count($events) - 1): ?>
			</div>
			<div class="row wpv-tribe-multiple-events">
		<?php endif ?>
	<?php endforeach; ?>
	<?php wp_reset_postdata(); ?>
</div>