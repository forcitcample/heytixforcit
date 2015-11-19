<?php
/**
 * Header template
 *
 * @package wpv
 * @subpackage fitness-wellness
 */

?><!DOCTYPE html>
<!--[if IE]><![endif]-->
<!--[if IE 8 ]> <html <?php language_attributes(); ?> class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]> <html <?php language_attributes(); ?> class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html <?php language_attributes(); ?> class="no-ie no-js"> <!--<![endif]-->

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title><?php wp_title() ?></title>
	<link rel="profile" href="http://gmpg.org/xfn/11" />
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
	<meta property="og:type" content="heytix_social:product" />
	<link rel="shortcut icon" type="image/x-icon" href="<?php wpvge('favicon_url')?>"/>
	<?php wp_head(); ?>
</head>
<body <?php body_class('layout-'.WpvTemplates::get_layout()); ?>>
<script type="text/javascript">
    function inIframe () {
        try {
            return window.self !== window.top;
        } catch (e) {
            return true;
        }
    }
    jQuery(document).ready(function() {
        if (inIframe() == false) {
            jQuery('.wpv-grid #tribe-events').show();
            jQuery('.fixed-header-box').show();
            jQuery('.header-middle').show();
            jQuery('.main-footer').show();
            jQuery('.copyrights').show();
        } else {
            <?php if(is_order_received_page()) : ?>
            jQuery('.checkout-logo').show();
            <?php endif; ?>
        }
    });
</script>
	<?php
	// Fake ID to prevent Events Tab scroll jump
	if(  tribe_is_event() ){
		?>
		<script type="text/javascript">
			document.write('<span id="'+ location.hash.replace('#', '') +'" class="htgl-fake-tab-jump" style="height:0;visibility: hidden;"></span>');
			jQuery(document).ready(function(){
				jQuery('body').on('click', function(){
					jQuery('.htgl-fake-tab-jump').remove();
				});
			});
		</script>
		<?php
	}
	?>

	<span id="top" class="mk"></span>
	<?php do_action('wpv_body') ?>

	<div id="page" class="main-container">
		<?php include(locate_template('templates/header/top.php'));?>
		<?php do_action('wpv_after_top_header') ?>

		<div class="boxed-layout">
			<div class="pane-wrapper clearfix">
				<?php include(locate_template('templates/header/middle.php'));?>
				<div id="main-content">
					<?php 
                                        if(ht_is_guest_lsit_page()){
                                            include(locate_template('templates/guestlist/sub-header.php'));
                                        }else{
                                            include(locate_template('templates/header/sub-header.php'));
                                        }
                                        ?>
                                        <div id="main" role="main" class="wpv-main layout-<?php echo WpvTemplates::get_layout() ?>">
						<?php do_action('wpv_inside_main') ?>
						<div class="limit-wrapper">