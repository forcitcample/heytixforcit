<?php
	/**
	 * Actual, visible header. Includes the logo, menu, etc.
	 * @package wpv
	 */

	$layout = wpv_get_option( 'header-layout' );

	if(is_page_template('page-blank.php')) return;
?>
<div class="fixed-header-box">
	<header class="main-header layout-<?php echo $layout // xss ok ?> <?php if ( $layout === 'logo-menu' ) echo 'header-content-wrapper' ?> ">
		<?php get_template_part('templates/header/top/nav') ?>
		<?php get_template_part('templates/header/top/main', wpv_get_option('header-layout')) ?>
	</header>

	<?php do_action('wpv_header_box'); ?>
</div><!-- / .fixed-header-box -->
<div class="shadow-bottom"></div>