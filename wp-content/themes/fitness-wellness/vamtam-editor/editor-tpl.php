<div id="wpv-editor-shortcodes" class="clearfix">
	<ul>
		<?php echo $this->complex_elements() ?>
	</ul>
</div>

<div class="metabox-editor-content">
	<div id="visual_editor_edit_form"></div>
	<div id="visual_editor_content" class="wpv_main_sortable inner-sortable main_wrapper clearfix"></div>
</div>

<?php $status = get_post_meta($post->ID, '_wpv_ed_js_status', true) ?>
<input type="hidden" id="wpv_ed_js_status" name="_wpv_ed_js_status" value="<?php echo empty($status) ? 'true' : $status ?>" />
