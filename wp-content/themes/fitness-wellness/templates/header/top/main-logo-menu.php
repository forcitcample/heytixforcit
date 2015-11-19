<div class="<?php if(!wpv_get_option('full-width-header')) echo 'limit-wrapper' ?>">
	<div class="header-contents">
		<div class="first-row">
			<?php get_template_part('templates/header/top/logo') ?>
		</div>

		<div class="second-row <?php if(wpv_get_option('enable-header-search')) echo 'has-search' ?>">
			<div id="menus">
				<?php get_template_part('templates/header/top/main-menu') ?>
			</div>
		</div>

		<?php do_action('wpv_header_cart') ?>

		<?php if(wpv_get_option('enable-header-search')): ?>
			<div class="search-wrapper">
				<?php get_template_part('templates/header/top/search-button') ?>
			</div>
		<?php endif ?>

		<?php
			$phone_num = wpv_get_option('phone-num-top');
			if ( ! empty( $phone_num ) ):
		?>
			<div id="phone-num"><div><?php echo do_shortcode( $phone_num ) ?></div></div>
		<?php endif ?>
	</div>
</div>