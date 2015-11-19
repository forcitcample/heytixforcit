<?php
return array(
	'name' => __('Progress Indicator', 'fitness-wellness') ,
	'desc' => __('You can choose from % indicator or animated number.' , 'fitness-wellness'),
	'icon' => array(
		'char' => WPV_Editor::get_icon('meter-medium'),
		'size' => '26px',
		'lheight' => '39px',
		'family' => 'vamtam-editor-icomoon',
	),
	'value' => 'wpv_progress',
	'controls' => 'size name clone edit delete',
	'options' => array(
		array(
			'name' => __('Type', 'fitness-wellness'),
			'id' => 'type',
			'type' => 'select',
			'default' => 'percentage',
			'options' => array(
				'percentage' => __('Percentage', 'fitness-wellness'),
				'number' => __('Number', 'fitness-wellness'),
			),
			'field_filter' => 'fpis',
		),

		array(
			'name' => __('Percentage', 'fitness-wellness') ,
			'id' => 'percentage',
			'default' => 0,
			'type' => 'range',
			'min' => 0,
			'max' => 100,
			'unit' => '%',
			'class' => 'fpis fpis-percentage',
		) ,

		array(
			'name' => __('Value', 'fitness-wellness') ,
			'id' => 'value',
			'default' => 0,
			'type' => 'range',
			'min' => 0,
			'max' => 100000,
			'class' => 'fpis fpis-number',
		) ,

		array(
			'name' => __('Track Color', 'fitness-wellness') ,
			'id' => 'bar_color',
			'default' => 'accent1',
			'type' => 'color',
			'class' => 'fpis fpis-percentage',
		) ,

		array(
			'name' => __('Bar Color', 'fitness-wellness') ,
			'id' => 'track_color',
			'default' => 'accent7',
			'type' => 'color',
			'class' => 'fpis fpis-percentage',
		) ,

		array(
			'name' => __('Value Color', 'fitness-wellness') ,
			'id' => 'value_color',
			'default' => 'accent2',
			'type' => 'color',
		) ,

		) ,


);
