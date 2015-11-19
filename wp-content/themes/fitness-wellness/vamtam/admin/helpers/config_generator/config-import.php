<div class="wpv-config-row clearfix">
	<div class="rtitle">
		<h4><?php echo $name ?></h4>
		
		<?php wpv_description('import-skin', $desc) ?>
	</div>
	
	<div class="rcontent">
		<select id="import-config-available" class="static">
			<option value=""><?php _e('Available skins', 'fitness-wellness')?></option>
		</select>
		<input type="button" id="import-config" class="button static" value="<?php echo $name ?>" />
		<input type="button" id="delete-config" class="button static" value="<?php _e('Delete', 'fitness-wellness') ?>" />
		<span class="spinner" style="float:none"></span>
	</div>
</div>