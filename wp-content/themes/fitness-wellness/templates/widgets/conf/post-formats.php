<p>
	<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'fitness-wellness'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
</p>

<p>
	<label for="<?php echo $this->get_field_id('tooltip'); ?>"><?php _e('Tooltip:', 'fitness-wellness'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('tooltip'); ?>" name="<?php echo $this->get_field_name('tooltip'); ?>" type="text" value="<?php echo $tooltip; ?>" />
</p>
