<?php
/**
 * Single portfolio item used in a loop
 *
 * @package wpv
 * @subpackage fitness-wellness
 */
list($terms_slug, $terms_name) = wpv_get_portfolio_terms();

$show_overlay = false;

$item_class = array();

$item_class[] = $title === 'below' ? 'has-title' : 'no-title';
$item_class[] = $desc ? 'has-description' : 'no-description';
$item_class[] = $scrollable ? '' : "grid-1-$column";
$item_class[] = empty($moreText) ? 'no-button' : 'has-button';
$item_class[] = 'state-closed';
?>
<li data-id="<?php the_id()?>" data-type="<?php echo implode(' ', $terms_slug)?>" class="<?php echo implode(' ', $item_class); ?>">
	<div class="portfolio-item-wrapper">
		<?php
			$gallery = $lightbox = $href = '';
			extract(wpv_get_portfolio_options($group, $rel_group));

			$video_url = ($type === 'video' and !empty($href)) ? $href : '';

			if(empty($href))
				$href = get_permalink();

			if($fancy_page || $type === 'html' || (!empty($video_url) && has_post_thumbnail())) {
				$lightbox = '';
				$href = ($type=='link' ? $href : get_permalink());
			}

			if($fancy_page || $scrollable) {
				$gallery = '';
			}

			$suffix = $sortable === 'masonry' ? 'masonry' : 'loop';
		?>
		<div class="portfolio-image">
			<div class="thumbnail" style="max-height:<?php echo $size[1]?>px">
				<?php
					if(!empty($gallery)):
						echo do_shortcode($gallery);
					elseif(!empty($video_url) && !has_post_thumbnail()):
						global $wp_embed;
						echo $wp_embed->run_shortcode('[embed]'.$video_url.'[/embed]');
					elseif(has_post_thumbnail()):
				?>
						<?php
							the_post_thumbnail( "portfolio-{$suffix}-".$column );
							$show_overlay = true;
						?>
				<?php endif ?>
			</div><!-- / .thumbnail -->
		</div>

		<?php if($title === 'below' || $desc): ?>
			<div class="portfolio_details project-info-pad folio <?php if($show_overlay) echo 'has-overlay' ?>">
				<?php if($title === 'below'): ?>
					<h3 class="title">
						<?php if($show_overlay): ?>
							<?php the_title()?>
						<?php else: ?>
							<a href="<?php the_permalink() ?>"><?php the_title()?></a>
						<?php endif ?>
					</h3>
				<?php endif ?>
				<?php if($desc):?>
					<div class="excerpt"><?php the_excerpt() ?></div>
				<?php endif ?>
			</div>
		<?php endif ?>

		<?php if($show_overlay): ?>
			<span class="thumbnail-overlay">
				<a href="<?php echo esc_attr( $href ) ?>" class="meta <?php echo esc_attr( $lightbox ) ?>" <?php echo $rel ?>></a>
			</span>
		<?php endif ?>
	</div>
</li>