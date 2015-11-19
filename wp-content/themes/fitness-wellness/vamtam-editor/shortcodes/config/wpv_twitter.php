<?php
return array(
	"name" => __("Twitter Timeline", 'fitness-wellness'),
	'icon' => array(
		'char' => WPV_Editor::get_icon('twitter'),
		'size' => '26px',
		'lheight' => '39px',
		'family' => 'vamtam-editor-icomoon',
	),
	"value" => "wpv_tribe_events",
	'controls' => 'size name clone edit delete',
	"options" => array(

		array(
			'name' => __('Type', 'fitness-wellness') ,
			'id' => 'type',
			'default' => 'user',
			'type' => 'select',
			'options' => array(
				'user' => __('Single user', 'fitness-wellness'),
				'search' => __('Search results ', 'fitness-wellness'),
			),
		) ,

		array(
			'name' => __('Username or Search Terms', 'fitness-wellness') ,
			'id' => 'param',
			'default' => '',
			'type' => 'text',
		) ,

		array(
			'name' => __('Number of Tweets', 'fitness-wellness') ,
			'id' => 'limit',
			'default' => 5,
			'type' => 'range',
			'min' => 1,
			'max' => 20,
		) ,

		array(
			'name' => __('Title (optional)', 'fitness-wellness') ,
			'desc' => __('The title is placed just above the element.', 'fitness-wellness'),
			'id' => 'column_title',
			'default' => '',
			'type' => 'text'
		) ,
		array(
			'name' => __('Title Type (optional)', 'fitness-wellness') ,
			'id' => 'column_title_type',
			'default' => 'single',
			'type' => 'select',
			'options' => array(
				'single' => __('Title with divider next to it', 'fitness-wellness'),
				'double' => __('Title with divider under it ', 'fitness-wellness'),
				'no-divider' => __('No Divider', 'fitness-wellness'),
			),
		) ,
		array(
			'name'    => __('Element Animation (optional)', 'fitness-wellness') ,
			'id'      => 'column_animation',
			'default' => 'none',
			'type'    => 'select',
			'options' => array(
				'none'        => __('No animation', 'fitness-wellness'),
				'from-left'   => __('Appear from left', 'fitness-wellness'),
				'from-right'  => __('Appear from right', 'fitness-wellness'),
				'from-top'    => __('Appear from top', 'fitness-wellness'),
				'from-bottom' => __('Appear from bottom', 'fitness-wellness'),
				'fade-in'     => __('Fade in', 'fitness-wellness'),
				'zoom-in'     => __('Zoom in', 'fitness-wellness'),
			),
		) ,
	),
);
