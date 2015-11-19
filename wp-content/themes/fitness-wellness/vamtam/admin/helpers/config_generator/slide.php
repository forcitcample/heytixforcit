<?php
/**
 * used in slider shortcode generator
 */

$slide_id = $id;
 
?>

<div class="wpv-config-row slide-config">
	
	<label>
		<?php _e('Slide type', 'fitness-wellness') ?>
		<select name="slide-<?php echo $slide_id ?>-type" id="slide-<?php echo $slide_id ?>-type" class="slide-type">
			<option value="image"><?php _e('Image', 'fitness-wellness')?></option>
			<option value="html"><?php _e('HTML', 'fitness-wellness')?></option>
		</select>
	</label>
	
	<div class="slide-types">
		<div class="image">
			<?php
				$id = $slide_id.'_image';
				$default = '';
				
				include 'upload-basic.php';
			?>
		</div>
		<div class="html">
			<textarea rows="5" name="<?php echo $slide_id ?>_html" id="<?php echo $slide_id ?>_html"></textarea>
		</div>
	</div>
	
</div>
