<?php
/**
 * Post metadata template
 *
 * @package  wpv
 */
?>
<div class="post-meta">
	<nav class="clearfix">
		<?php if(wpv_get_optionb('show-post-author')): ?>
			<div class="author vcard"><span class="icon theme"><?php wpv_icon('ink-tool');?></span><span class="fn"><?php the_author_posts_link()?></span></div>
		<?php endif ?>

		<?php if(!post_password_required()): ?>
			<?php if(wpv_get_optionb('meta_comment_count') && comments_open()): ?>
				<div class="comment-count">
					<a href="<?php comments_link() ?>" class="icon theme"><?php wpv_icon('comments')?></a><?php comments_popup_link(__('0 <span class="comment-word">Comments</span>', 'fitness-wellness'), __('1 <span class="comment-word">Comment</span>', 'fitness-wellness'), __('% <span class="comment-word">Comments</span>', 'fitness-wellness')); ?>
				</div>
			<?php endif; ?>

			<?php if(wpv_get_optionb('meta_posted_in')): ?>
				<div><span class="icon"><?php wpv_icon('folder1'); ?></span><span class="visuallyhidden"><?php _e('Category', 'fitness-wellness') ?></span><?php the_category(', '); ?></div>
				<?php
				// Tags
				the_tags('<div class="the-tags"><span class="icon">' . wpv_get_icon('tag') . '</span><span class="visuallyhidden">'.__('Tags', 'fitness-wellness').'</span>', ', ', '</div>')?>
			<?php endif ?>
		<?php endif ?>

		<?php if (!is_single()): ?>
			<div class="blog-buttons">
				<?php edit_post_link('<span class="icon">' . wpv_get_icon('pencil') . '</span><span>'. __('Edit', 'fitness-wellness') .'</span>') ?>
			</div>
		<?php endif ?>

		<?php if(function_exists('wpv_li_love_link')): ?>
			<?php echo wpv_li_love_link('<span class="visuallyhidden">'.__('Love it', 'fitness-wellness').'</span>',
			                            '<span class="visuallyhidden">'.__('You have loved this.', 'fitness-wellness').'</span>'); ?>
		<?php endif ?>
	</nav>
</div>