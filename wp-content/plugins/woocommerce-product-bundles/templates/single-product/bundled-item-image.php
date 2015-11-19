<?php
/**
 * Bundled Product Image
 * @version 4.8.8
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( has_post_thumbnail( $post_id ) ) {

	?><div class="bundled_product_images"><?php

			$image_title = esc_attr( get_the_title( get_post_thumbnail_id( $post_id ) ) );
			$image_link  = wp_get_attachment_url( get_post_thumbnail_id( $post_id ) );
			$image       = get_the_post_thumbnail( $post_id, apply_filters( 'bundled_product_large_thumbnail_size', 'shop_thumbnail' ), array(
				'title' => $image_title
				) );

			echo apply_filters( 'woocommerce_bundled_product_image_html', sprintf( '<a href="%s" class="bundled_product_image zoom" title="%s" data-rel="prettyPhoto">%s</a>', $image_link, $image_title, $image ), $post_id );

	?></div><?php
}
