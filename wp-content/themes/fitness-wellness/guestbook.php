<?php
/**
 * Single page template
 *
 * Template Name: Guestbook
 *
 * @package wpv
 * @subpackage the-wedding-day
 */

get_header();

?>

<?php if ( have_posts() ) : the_post(); ?>

<div class="pane main-pane">
	<div class="row">
		<div class="page-outer-wrapper">
			<div class="clearfix page-wrapper">
				<?php WpvTemplates::left_sidebar() ?>

				<article id="post-<?php the_ID(); ?>" <?php post_class(WpvTemplates::get_layout()); ?>>
					<?php
					global $wpv_has_header_sidebars;
					if( $wpv_has_header_sidebars) {
						WpvTemplates::header_sidebars();
					}
					?>

					<?php comments_template( '/comments-guestbook.php', true ); ?>

					<div class="page-content">
						<?php the_content(); ?>
						<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'fitness-wellness' ), 'after' => '</div>' ) ); ?>
						<?php WpvTemplates::share('page') ?>
					</div>
				</article>

				<?php WpvTemplates::right_sidebar() ?>
			</div>
		</div>
	</div>
</div>

<?php endif;

get_footer();
