<?php
return array(
	'name' => __('Tabs', 'fitness-wellness') ,
	'desc' => __('Change to vertical or horizontal tabs from the element option panel.  Add an icon by clicking on the "pencil" icon next to the pane title. Adding tabs, changing the name of the tab and adding content into the tabs is done when the tab element is toggled.' , 'fitness-wellness'),
	'icon' => array(
		'char' => WPV_Editor::get_icon('storage1'),
		'size' => '30px',
		'lheight' => '45px',
		'family' => 'vamtam-editor-icomoon',
	),
	'value' => 'tabs',
	'controls' => 'size name clone edit delete always-expanded',
	'callbacks' => array(
		'init' => 'init-tabs',
		'generated-shortcode' => 'generate-tabs',
	),
	'options' => array(

		array(
			'name' => __('Layout', 'fitness-wellness') ,
			"id" => "layout",
			"default" => 'horizontal',
			"type" => "radio",
			'options' => array(
				'horizontal' => __('Horizontal', 'fitness-wellness'),
				'vertical' => __('Vertical', 'fitness-wellness'),
			),
			'field_filter' => 'fts',
		) ,
		array(
			'name' => __('Navigation Color', 'fitness-wellness') ,
			'id' => 'nav_color',
			'type' => 'color',
			'default' => 'accent2',
		) ,
		array(
			'name' => __('Navigation Background', 'fitness-wellness') ,
			'id' => 'left_color',
			'type' => 'color',
			'default' => 'accent8',
		) ,
		array(
			'name' => __('Content Background', 'fitness-wellness') ,
			'id' => 'right_color',
			'type' => 'color',
			'default' => 'accent1',
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
				'single' => __('Title with divider next to it.', 'fitness-wellness'),
				'double' => __('Title with divider below', 'fitness-wellness'),
				'no-divider' => __('No Divider', 'fitness-wellness'),
			),
			'class' => 'fts fts-horizontal',
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
	) ,
);
