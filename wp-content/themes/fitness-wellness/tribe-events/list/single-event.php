<?php
/**
 * List View Single Event
 * This file contains one event in the list view
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/list/single-event.php
 *
 * @package TribeEventsCalendar
 * @since  3.0
 * @author Modern Tribe Inc.
 *
 */

if ( !defined('ABSPATH') ) { die('-1'); }

global $post;

?>

<div class="small-event-header clearfix">
	<div class="tribe-events-event-meta-wrapper">
		<?php do_action( 'tribe_events_before_the_meta' ); ?>
			<div class="tribe-events-event-meta">
				<?php
					$start = strtotime($post->EventStartDate);
					$end = strtotime($post->EventEndDate);
					$day = date('d', $start);
					$month = date_i18n('M', $start);

					$stime = date(get_option('time_format'), $start);
					$etime = date(get_option('time_format'), $end);
				?>
				<a href="<?php tribe_event_link($post) ?>" title="<?php esc_attr_e('Read More', 'fitness-wellness') ?>">
					<div class="thumbnail">
						<?php echo get_the_post_thumbnail( $post->ID, 'portfolio-masonry-3' ); // xss ok ?>
						<div class="date">
							<div class="date-inner">
								<div class="day"><?php echo $day ?></div>
								<div class="month"><?php echo $month ?></div>
							</div>
						</div>
					</div>
				</a>
			</div><!-- .tribe-events-event-meta -->
		<?php do_action( 'tribe_events_after_the_meta' ); ?>
	</div>
</div>

<div class="tribe-events-event-details tribe-clearfix">

	<?php do_action( 'tribe_events_before_the_event_title' ); ?>
	<h4 class="tribe-events-list-event-title entry-title summary">
		<a class="url" href="<?php echo tribe_get_event_link() ?>" title="<?php the_title() ?>" rel="bookmark">
			<?php the_title() ?>
		</a>
	</h4>
	<?php do_action( 'tribe_events_after_the_event_title' ); ?>

	<div class="when-where">
		<div>
			<?php echo wpv_shortcode_icon( array( 'name' => 'theme-clock' ) ) ?>
			<?php if ( ! tribe_event_is_all_day( $post->ID ) ): ?>
				<?php echo $stime ?> <?php if ( $stime !== $etime ) echo '&mdash; ' . $etime ?>
			<?php else: ?>
				<?php _e( 'All Day', 'fitness-wellness' ) ?>
			<?php endif ?>
		</div>
		<div><?php echo wpv_shortcode_icon( array( 'name' => 'theme-pointer' ) ) ?> <?php
			if( class_exists( 'Tribe__Events__Pro__Templates__Single_Venue' ) ) {
				tribe_get_venue_link( $post->ID, true );
			} else {
				echo tribe_get_venue( $post->ID );
			}
		?>
		</div>
	</div>

	<!-- Event Content -->
	<?php do_action( 'tribe_events_before_the_content' ); ?>
	<div class="tribe-events-list-photo-description tribe-events-content entry-summary description">
		<?php the_excerpt(); ?>
	</div>
	<?php do_action( 'tribe_events_after_the_content' ) ?>

</div><!-- /.tribe-events-event-details -->
