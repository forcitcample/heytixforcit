<?php
return array(
	'name' => __('Text Divider', 'fitness-wellness') ,
	'icon' => array(
		'char' => WPV_Editor::get_icon('minus'),
		'size' => '30px',
		'lheight' => '45px',
		'family' => 'vamtam-editor-icomoon',
	),
	'value' => 'text_divider',
	'controls' => 'name clone edit delete',
	'options' => array(
		array(
			'name' => __('Type', 'fitness-wellness') ,
			'id' => 'type',
			'default' => 'single',
			'options' => array(
				'single' => __('Title in the middle', 'fitness-wellness') ,
				'double' => __('Title above divider', 'fitness-wellness') ,
			) ,
			'type' => 'select',
			'class' => 'add-to-container',
			'field_filter' => 'ftds',
		) ,
		array(
			'name' => __('Text', 'fitness-wellness') ,
			'id' => 'html-content',
			'default' => __('Text Divider', 'fitness-wellness'),
			'type' => 'editor',
			'class' => 'ftds ftds-single ftds-double',
		) ,
	) ,
);
