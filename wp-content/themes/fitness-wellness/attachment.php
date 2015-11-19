<?php
/**
 * Attachment template
 *
 * @package wpv
 * @subpackage fitness-wellness
 */

$wpv_title = __('Attachment', 'fitness-wellness');

get_header();
?>

<?php if ( have_posts() ): the_post(); ?>
	<div class="row page-wrapper">
		<?php WpvTemplates::left_sidebar() ?>

		<article id="post-<?php the_ID(); ?>" <?php post_class(WpvTemplates::get_layout()); ?>>
			<?php
			global $wpv_has_header_sidebars;
			if( $wpv_has_header_sidebars) {
				WpvTemplates::header_sidebars();
			}
			?>
			<div class="page-content">
				<?php rewind_posts() ?>

				<div class="entry-attachment">
					<?php if ( wp_attachment_is_image() ) :
						$attachments = array_values( get_children( array( 'post_parent' => $post->post_parent, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC', 'orderby' => 'menu_order ID' ) ) );
						foreach ( $attachments as $k => $attachment ) {
							if ( $attachment->ID == $post->ID )
								break;
						}
						$k++;
						// If there is more than 1 image attachment in a gallery
						if ( count( $attachments ) > 1 ) {
							if ( isset( $attachments[ $k ] ) )
								// get the URL of the next image attachment
								$next_attachment_url = get_attachment_link( $attachments[ $k ]->ID );
							else
								// or get the URL of the first image attachment
								$next_attachment_url = get_attachment_link( $attachments[ 0 ]->ID );
						} else {
							// or, if there's only 1 image attachment, get the URL of the image
							$next_attachment_url = wp_get_attachment_url();
						}
					?>
						<p class="attachment"><a href="<?php echo $next_attachment_url; ?>" title="<?php echo esc_attr( get_the_title() ); ?>" rel="attachment" class="thumbnail"><?php
							$attachment_size = apply_filters( 'wpv_attachment_size', 900 );
							echo wp_get_attachment_image( $post->ID, array( $attachment_size, 9999 ) ); // filterable image width with, essentially, no limit for image height.
						?></a></p>

						<div id="nav-below" class="navigation">
							<div class="nav-previous"><?php previous_image_link( false ); ?></div>
							<div class="nav-next"><?php next_image_link( false ); ?></div>
						</div><!-- #nav-below -->
					<?php else : ?>
						<a href="<?php echo wp_get_attachment_url(); ?>" title="<?php echo esc_attr( get_the_title() ); ?>" rel="attachment"><?php echo basename( get_permalink() ); ?></a>
					<?php endif; ?>
				</div><!-- .entry-attachment -->

				<div class="entry-caption">
					<?php if ( !empty( $post->post_excerpt ) ) the_excerpt(); ?>
					<?php
						if ( wp_attachment_is_image() ) {
							$metadata = wp_get_attachment_metadata();
							printf( __( 'Original size is %s pixels', 'fitness-wellness'),
								sprintf( '<a href="%1$s" title="%2$s">%3$s &times; %4$s</a>',
									wp_get_attachment_url(),
									esc_attr( __('Link to full-size image', 'fitness-wellness') ),
									$metadata['width'],
									$metadata['height']
								)
							);
						}
					?>
				</div>

				<?php the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'fitness-wellness' ) ); ?>

			</div>
		</article>

		<?php WpvTemplates::right_sidebar() ?>
	</div>
<?php endif ?>

<?php get_footer(); ?>