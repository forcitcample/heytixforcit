<li id="comment-<?php comment_ID() ?>" <?php comment_class( 'grid-1-3' . ( $args['has_children'] ? 'has-children' : '' ) ) ?>>
	<div class="sep-text centered">
		<span class="sep-text-before"><div class="sep-text-line"></div></span>
		<div class="content">
			<?php echo wpv_shortcode_icon( array( 'name' => 'theme-quote' ) ) ?>
		</div>
		<span class="sep-text-after"><div class="sep-text-line"></div></span>
	</div>
	<div class="comment-inner">
		<header class="comment-header">
			<h3 class="comment-author-link"><?php comment_author_link(); ?></h3>
			<?php
				if ( ( ! isset( $args['reply_allowed'] ) || $args['reply_allowed'] ) && ( $args['type'] == 'all' || get_comment_type() == 'comment' )  ) :
					comment_reply_link( array_merge( $args, array(
					'reply_text' => __( 'Reply','shape' ),
					'login_text' => __( 'Log in to reply.','shape' ),
					'depth' => $depth,
					'before' => '<h6 class="comment-reply-link">',
					'after' => '</h6>'
					) ) );
				endif;
			?>
		</header>
		<?php comment_text() ?>
		<footer class="comment-footer">
			&mdash;<br>
			<h6 title="<?php comment_time(); ?>" class="comment-time"><?php comment_date(); ?></h6>
			<?php edit_comment_link( sprintf( '[%s]', __( 'Edit', 'fitness-wellness' ) ) ) ?>
			<?php if ( $comment->comment_approved == '0' ) _e( "\t\t\t\t\t<span class='unapproved'>Your comment is awaiting moderation.</span>\n", 'shape' ) ?>
		</footer>
	</div>