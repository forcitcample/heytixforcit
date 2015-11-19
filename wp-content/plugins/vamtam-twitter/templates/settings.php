<div class="wrap">
	<h2><?php _e( 'Vamtam Twitter', 'wpv' ); ?></h2>

	<form method="post" action="options.php">
	<?php
		settings_fields( 'vamtam_twitter_options' );
		do_settings_sections( 'vamtam_twitter_options' );

		submit_button();
	?>
	</form>
</div>