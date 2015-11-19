<?php if(isset($post_data['media'])): ?>
	<div class="thumbnail">
		<?php if( has_post_format( 'image' ) ): ?>
			<a href="<?php the_permalink() ?>" title="<?php the_title_attribute()?>">
				<?php echo $post_data['media']; ?>
				<?php echo wpv_shortcode_icon( array( 'name' => 'theme-circle-post' ) ) ?>
			</a>
		<?php else: ?>
			<?php echo $post_data['media']; ?>
		<?php endif ?>
	</div>
<?php endif; ?>

<?php if($show_content): ?>
	<div class="post-content-wrapper">
		<div class="post-actions-wrapper clearfix">
			<div class="post-date">
				<?php the_time(get_option('date_format')); ?>
			</div>
			<?php if ( ! post_password_required() ): ?>
				<?php if ( wpv_get_optionb( 'meta_comment_count' ) && comments_open() ): ?>
					<div class="comment-count">
						<?php
							$comment_icon = '<span class="icon">' . wpv_get_icon( 'bubble5' ) . '</span>';
							comments_popup_link(
								$comment_icon . __( '0 <span class="comment-word visuallyhidden">Comments</span>', 'fitness-wellness' ),
								$comment_icon . __( '1 <span class="comment-word visuallyhidden">Comment</span>', 'fitness-wellness' ),
								$comment_icon . __( '% <span class="comment-word visuallyhidden">Comments</span>', 'fitness-wellness' )
							);
						?>
					</div>
				<?php endif; ?>

				<?php edit_post_link( '<span class="icon">' . wpv_get_icon( 'pencil' ) . '</span><span class="visuallyhidden">'. __( 'Edit', 'fitness-wellness' ) .'</span>' ) ?>
			<?php endif ?>
		</div>

		<?php include locate_template('templates/post/header.php'); ?>

		<div class="post-content-outer">
			<?php echo $post_data['content']; ?>
		</div>

		<?php if(wpv_get_optionb('meta_posted_in')): ?>
			<div class="post-content-meta">
				<div>
					<?php _e('Posted in: ', 'fitness-wellness') ?> <?php the_category(', '); ?>
				</div>
				<?php the_tags('<div class="the-tags">'.__('Tags: ', 'fitness-wellness'), ', ', '</div>') ?>
			</div>
		<?php endif ?>
	</div>
<?php endif; ?>