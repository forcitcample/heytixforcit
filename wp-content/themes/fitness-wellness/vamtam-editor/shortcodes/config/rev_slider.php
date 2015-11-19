<?php
return array(
	'name' => __('Revolution Slider', 'fitness-wellness') ,
	'desc' => __('Please note that the theme uses Revolution Slider and its option panel is found in the WordPress navigation menu on the left. This element inserts already created slider into the page/post body.
	If you need to activate the slider in the Header, then you will need the option - "Page Slider" found below the editor. ' , 'fitness-wellness'),
	'icon' => array(
		'char' => WPV_Editor::get_icon('images'),
		'size' => '26px',
		'lheight' => '39px',
		'family' => 'vamtam-editor-icomoon',

	),
	'value' => 'rev_slider',
	'controls' => 'size name clone edit delete',
	'options' => array(
		array(
			'name' => __('Slider', 'fitness-wellness') ,
			'id' => 'alias',
			'default' => '',
			'options' => WpvTemplates::get_rev_sliders(''),
			'type' => 'select',
		) ,
	) ,
);