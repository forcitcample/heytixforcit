<?php do_action( 'woocommerce_before_shop_loop_item' ); ?>

<a href="<?php the_permalink(); ?>">

	<?php
		/**
		 * woocommerce_before_shop_loop_item_title hook
		 *
		 * @hooked woocommerce_show_product_loop_sale_flash - 10
		 * @hooked woocommerce_template_loop_product_thumbnail - 10
		 */
		do_action( 'woocommerce_before_shop_loop_item_title' );
	?>

	<h3><?php the_title(); ?></h3>

	<?php
		/**
		 * woocommerce_after_shop_loop_item_title hook
		 *
		 * @hooked woocommerce_template_loop_price - 10
		 */
		do_action( 'woocommerce_after_shop_loop_item_title' );
	?>

</a>

<?php do_action( 'woocommerce_after_shop_loop_item' ); ?>