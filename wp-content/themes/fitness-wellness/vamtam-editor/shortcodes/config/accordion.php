<?php
return array(
	"name" => __("Accordion", 'fitness-wellness'),
	'desc' => __('Adding panes, changing the name of the pane and adding content into the panes is done when the accordion element is toggled.' , 'fitness-wellness'),
	'icon' => array(
		'char' => WPV_Editor::get_icon('menu1'),
		'size' => '30px',
		'lheight' => '45px',
		'family' => 'vamtam-editor-icomoon',
	),
	"value" => "accordion",
	'controls' => 'size name clone edit delete always-expanded',
	'callbacks' => array(
		'init' => 'init-accordion',
		'generated-shortcode' => 'generate-accordion',
	),
	"options" => array(

		array(
			'name' => __('Allow All Panes to be Closed', 'fitness-wellness') ,
			'desc' => __('If enabled, the accordion will load with collapsed panes. Clicking on the title of the currently active pane will close it. Clicking on the title of an inactive pane will change the active pane.', 'fitness-wellness'),
			'id' => 'collapsible',
			'default' => true,
			'type' => 'toggle'
		) ,

		array(
			'name' => __('Pane Background', 'fitness-wellness') ,
			'id' => 'closed_bg',
			'default' => 'accent1',
			'type' => 'color'
		) ,

		array(
			'name' => __('Title Color', 'fitness-wellness') ,
			'id' => 'title_color',
			'default' => 'accent8',
			'type' => 'color'
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
