<?php
/**
 * drag&drop slider editor
 */

wp_localize_script('wpv_admin', 'sliderL10n', array(
	'slides' => __('Slides', 'fitness-wellness'),
	'html_slide' => __('New HTML slide', 'fitness-wellness'),
	'image_slide' => __('New image slide', 'fitness-wellness'),
	'delete_slider' => __('Delete slider', 'fitness-wellness'),
	'delete_slider_confirm' => __('Are you sure you want to delete this slider? This will also permanently delete its slides', 'fitness-wellness'),
	'delete_slide_confirm' => __('Do you want to delete this slide permanently?', 'fitness-wellness'),
	'please_add_slides' => __('Please add some slides', 'fitness-wellness'),
) );
?>

<div class="wpv-config-row slider-editor clearfix <?php echo $class ?>">
	<div class="rtitle">
		<?php if(!empty($name)): ?>
			<h4><?php echo $name?></h4>
		<?php endif ?>
	
		<?php wpv_description('slider-editor', $desc) ?>
	</div>
	
	<div class="rcontent">
		<input type="text" class="regular-text new-slider-name" />
		<input type="button" class="button add-new-slider" value="<?php _e('Add slider', 'fitness-wellness') ?>" />

		<div class="sliders">
		</div>
	</div>
</div>
