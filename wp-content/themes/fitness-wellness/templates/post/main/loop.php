<div class="post-row">
	<?php include(locate_template('templates/post/main/loop-date.php')); ?>
	<div class="post-row-center">
		<?php if (isset($post_data['media'])):?>
			<div class="post-media">
				<div class='media-inner'>
					<?php if ( in_array( $post_data['format'], array( 'image', 'standard' ) ) ): ?>
						<a href="<?php the_permalink() ?>" title="<?php the_title_attribute()?>">
					<?php endif ?>

					<?php echo $post_data['media']; ?>

					<?php if ( in_array( $post_data['format'], array( 'image', 'standard' ) ) ): ?>
						</a>
					<?php endif ?>
				</div>
			</div>
		<?php endif; ?>
		<div class="post-content-outer">
			<?php
				include(locate_template('templates/post/header-large.php'));
				include(locate_template('templates/post/content.php'));
				include(locate_template('templates/post/meta-loop.php'));
			?>
		</div>
	</div>
</div>