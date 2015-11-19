<?php
/**
 * Review Comments Template
 *
 * Closing li is left out on purpose!
 *
 * @author 		Vamtam
 * @package 	wpv
 * @subpackage  fitness-wellness
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $post;
$rating = esc_attr( get_comment_meta( $GLOBALS['comment']->comment_ID, 'rating', true ) );
?>
<li itemprop="reviews" itemscope itemtype="http://schema.org/Review" <?php comment_class(); ?> id="li-comment-<?php comment_ID() ?>">

	<div id="comment-<?php comment_ID(); ?>" class="comment_container">

		<div class="comment-author">
			<?php echo get_avatar( $GLOBALS['comment'], 60 ); ?>
		</div>
		<div class="comment-content">
			<div class="comment-meta">
				<h5 class="comment-author-link"><?php comment_author() ?></h5>
				<?php
						if ( get_option('woocommerce_review_rating_verification_label') == 'yes' )
							if ( true||woocommerce_customer_bought_product( $GLOBALS['comment']->comment_author_email, $GLOBALS['comment']->user_id, $post->ID ) )
								echo '<em class="verified">(' . __( 'verified owner', 'woocommerce' ) . ')</em> ';

					?>
				<h6 title="<?php echo get_comment_date('c'); ?>" class="comment-time"><?php echo get_comment_date(__( get_option('date_format'), 'woocommerce' )); ?></h6>
				<?php edit_comment_link(sprintf('[%s]', __('Edit', 'fitness-wellness'))) ?>
				<?php if ( get_option('woocommerce_enable_review_rating') == 'yes' ) : ?>
					<div itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating" class="star-rating" title="<?php echo sprintf(__( 'Rated %d out of 5', 'woocommerce' ), $rating) ?>">
						<span style="width:<?php echo ( intval( get_comment_meta( $GLOBALS['comment']->comment_ID, 'rating', true ) ) / 5 ) * 100; ?>%"><strong itemprop="ratingValue"><?php echo intval( get_comment_meta( $GLOBALS['comment']->comment_ID, 'rating', true ) ); ?></strong> <?php _e( 'out of 5', 'woocommerce' ); ?></span>
					</div>

				<?php endif; ?>
			</div>
			<?php if($GLOBALS['comment']->comment_approved == '0'): ?>
				<p class="meta"><em><?php _e( 'Your comment is awaiting approval', 'woocommerce' ); ?></em></p>
			<?php endif ?>
			<?php comment_text() ?>
		</div>
		<div class="clearfix"></div>
	</div>