<?php
/**
 * SCREETS © 2014
 *
 * Chat console template
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */

?>

<div id="CX_console" class="cx-console">
	
	<div id="CX_sidebar">
		<div class="cx-header">
			<?php _e( 'Users', 'cx' ); ?>
			<a href="" id="CX_connect" class="cx-connect button button-small button-disabled"><?php _e( 'Please wait', 'cx' ); ?></a>
		</div>

		<div id="CX_users">
			<div id="CX_ls_ntf" style="color:#999; text-align: center; padding: 10px;">
				<?php _e( "Please wait", 'cx' ); ?>...
			</div>
		</div>

		<div class="cx-footer">
			&copy; <?php echo date( 'Y' ); ?> Screets. Chat X
		</div>
	</div>

	<div id="CX_wall">
		<div id="CX_tabs">
			<ul>
				<li class="cx-active"><a id="CX_tab_username" href="javascript:void(0)">Screets CX</a></li>
			</ul>
		</div>
		
		<div class="cx-clear"></div>
		<div id="CX_popup_content">
			<div id="CX_popup_cnv" class="cx-popup-content cx-welcome"></div>
		</div>
</div>

<script>
	(function ($) {
		$(document).ready(function() {

			/**
			 * Set console window sizes
			 */
			$(window).resize(function() {

				var wpbody_h = $('#wpbody').height();

			}).trigger('resize');

		});
	} (window.jQuery || window.Zepto));
	
</script>