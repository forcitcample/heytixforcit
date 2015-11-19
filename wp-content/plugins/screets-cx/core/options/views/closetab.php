	</table>
	<?php if ( @$option['actions'] !== false ) { ?>
	<div class="cx-opts-actions-bar">
		<input type="submit" value="<?php _e( 'Save changes', $this->textdomain ); ?>" class="cx-opts-submit button-primary" />
		<span class="cx-opts-spin"><img src="<?php echo admin_url( 'images/wpspin_light.gif' ); ?>" alt="" /> <?php _e( 'Saving', $this->textdomain ); ?>&hellip;</span>
		<span class="cx-opts-success-tip"><img src="<?php echo $this->assets( 'img', 'success.png' ); ?>" alt="" /> <?php _e( 'Saved', $this->textdomain ); ?></span>
		<a href="<?php echo $this->admin_url; ?>&action=reset" class="cx-opts-reset button alignright" title="<?php _e( 'Reset all settings to default. Are you sure? This action cannot be undone!', $this->textdomain ); ?>"><?php _e( 'Restore default settings', $this->textdomain ); ?></a>
	</div>
	<?php } ?>
</div>