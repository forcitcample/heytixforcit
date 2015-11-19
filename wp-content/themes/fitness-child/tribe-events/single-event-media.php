<?php

global $post;

wp_reset_query();
$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full' );
?>

<div class="wpv-tribe-single-media <?php if($image === false) echo 'no-image' ?>" <?php if($image): ?>style="background-image: url('<?php echo esc_attr($image[0]) ?>')"<?php endif ?>>
	<div class="limit-wrapper">
		<div class="wpv-article-paddings-x">
			<div class="wpv-single-event-schedule">

				<?php
					$start = strtotime($post->EventStartDate);
					$end = strtotime($post->EventEndDate);

					$day = date('d', $start);
					$month = date_i18n('M', $start);

					$stime = date(get_option('time_format'), $start);
					$etime = date(get_option('time_format'), $end);
				?>
				<div class="wpv-single-event-schedule-block date-price">
					<div class="date">
						<div class="date-inner">
							<div class="day"><?php echo $day ?></div>
							<div class="month"><?php echo $month ?></div>
						</div>
					</div>
					<?php if ( tribe_get_cost() ) :  ?>
						<div class="price">
							<?php echo tribe_get_cost( null, true ); ?>
						</div>
					<?php endif ?>
				</div>
				<div class="wpv-single-event-schedule-block time">
					<div>
						<?php echo wpv_shortcode_icon( array( 'name' => 'theme-clock' ) ) ?>
						<div class="text"><?php echo $stime ?>&mdash;<?php echo $etime ?></div>
					</div>
				</div>

				<div class="wpv-single-event-schedule-block address">
					<div>
						<?php echo wpv_shortcode_icon( array( 'name' => 'theme-pointer' ) ) ?>
						<div class="text"><?php
								if( class_exists( 'Tribe_Events_Pro_Single_Venue_Template' ) ) {
									tribe_get_venue_link( $post->ID, true );
								} else {
									echo tribe_get_venue( $post->ID );
								}
							?>
							<br>
							<?php echo TribeEvents::instance()->fullAddress(); ?>
						</div>
					</div>
				</div>
				
				<div class="wpv-single-event-schedule-block">				
				<div class="buy_now"><a href="#ticket_buy" class="buy_tix">Buy Tickets</a></div>
				</div>
				
			</div>
		</div>
	</div>
</div>