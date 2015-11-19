<?php
/**
 * List View Nav Template
 * This file loads the list view navigation.
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/list/nav.php
 *
 * @package TribeEventsCalendar
 * @since  3.0
 * @author Modern Tribe Inc.
 *
 */
global $wp_query;

if ( !defined('ABSPATH') ) { die('-1'); } ?>

<h3 class="tribe-events-visuallyhidden"><?php _e( 'Events List Navigation', 'tribe-events-calendar' ) ?></h3>

<div class="wp-pagenavi">
	<?php
		global $wp_query;

		$total_pages = (int)$wp_query->max_num_pages;

		if($total_pages > 1) {
			$current_page = max(1, get_query_var('paged'));
			echo '<span class="pages">'.sprintf(__('Page %d of %d', 'fitness-wellness'), $current_page, $total_pages).'</span>';
		}
	?>
	<div class="tribe-events-sub-nav">
		<ul>
			<?php if ( tribe_has_previous_event() ) : ?>
			<li class="<?php echo tribe_left_navigation_classes(); ?>">
				<a href="<?php echo tribe_get_previous_events_link() ?>" rel="prev"><?php _e( '<span>&laquo;</span> Previous Events', 'tribe-events-calendar' ) ?></a>
			</li><!-- .tribe-events-nav-left -->
			<?php endif; ?>

			<!-- Right Navigation -->
			<?php if ( tribe_has_next_event() ) : ?>
			<li class="<?php echo tribe_right_navigation_classes(); ?>">
				<a href="<?php echo tribe_get_next_events_link() ?>" rel="next"><?php _e( 'Next Events <span>&raquo;</span>', 'tribe-events-calendar' ) ?></a>
			</li><!-- .tribe-events-nav-right -->
			<?php endif; ?>
		</ul>
	</div>
</div>