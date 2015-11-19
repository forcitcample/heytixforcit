<?php
	// No-js message
?>
<div class="error cx-opts-notification hide-if-js">
	<p><?php echo $notifications['js']; ?> <a href="http://enable-javascript.com/" target="_blank"><?php _e( 'Instructions', $this->textdomain ); ?></a>.</p>
</div>
<?php
	switch( @$_GET['message'] ) {
		
		// 
		// Options reseted
		// 
		case 1: ?>
			<div class="updated cx-opts-notification">
				<p><?php echo $notifications['reseted']; ?>
					<small class="hide-if-no-js">
						<?php _e( 'Click to close', $this->textdomain ); ?>
					</small>
				</p>
			</div>
			<?php break;

		// 
		// Options not reseted
		// 
		case 2: ?>
			<div class="error cx-opts-notification">
				<p><?php echo $notifications['not-reseted']; ?><small class="hide-if-no-js"><?php _e( 'Click to close', $this->textdomain ); ?></small></p>
			</div>
			<?php break;

		// 
		// Saved
		// 
		case 3: ?>
			<div class="updated cx-opts-notification">
				<p><?php echo $notifications['saved']; ?><small class="hide-if-no-js"><?php _e( 'Click to close', $this->textdomain ); ?></small></p>
			</div>
			<?php break;

		// 
		// No changes
		// 
		case 4: ?>
			<div class="error cx-opts-notification">
				<p><?php echo $notifications['not-saved']; ?><small class="hide-if-no-js"><?php _e( 'Click to close', $this->textdomain ); ?></small></p>
			</div>
		<?php break;
	}
?>