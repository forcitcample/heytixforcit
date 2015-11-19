<?php
return array(
	"name" => __("Upcoming Events", 'fitness-wellness'),
	'icon' => array(
		'char'    => WPV_Editor::get_icon('calendar'),
		'size'    => '26px',
		'lheight' => '39px',
		'family'  => 'vamtam-editor-icomoon',
	),
	"value"    => "wpv_tribe_events",
	'controls' => 'size name clone edit delete',
	"options"  => array(

		array(
			'name'    => __('Layout', 'fitness-wellness') ,
			'id'      => 'layout',
			'default' => 'single',
			'type'    => 'select',
			'options' => array(
				'single'       => __('Single event per line', 'fitness-wellness'),
				'single-large' => __('Single event per line (large)', 'fitness-wellness'),
				'classic'      => __('Classic', 'fitness-wellness'),
				'vertical'     => __('Large vertical list', 'fitness-wellness'),
				'multiple'     => __('Multiple events per line ', 'fitness-wellness'),
			),
			'field_filter' => 'fbl',
		) ,

		array(
			'name'    => __('Style', 'fitness-wellness') ,
			'id'      => 'style',
			'default' => 'light',
			'type'    => 'select',
			'options' => array(
				'light' => __('Light Text', 'fitness-wellness'),
				'dark'  => __('Dark Text', 'fitness-wellness'),
			),
		) ,

		array(
			'name'    => __('Number of Events', 'fitness-wellness') ,
			'id'      => 'count',
			'default' => '',
			'type'    => 'range',
			'min'     => 1,
			'max'     => 30,
			'class'   => 'fbl fbl-multiple fbl-vertical',
		) ,

		array(
			'name'    => __('Number of Columns', 'fitness-wellness') ,
			'id'      => 'columns',
			'default' => 4,
			'type'    => 'range',
			'min'     => 1,
			'max'     => 4,
			'class'   => 'fbl fbl-multiple',
		) ,

		array(
			'name'    => __('Ongoing Event Text', 'fitness-wellness') ,
			'id'      => 'ongoing',
			'default' => '',
			'type'    => 'text',
			'class'   => 'fbl fbl-single',
		) ,

		array(
			'name'    => __('Categories (optional)', 'fitness-wellness') ,
			'desc'    => __('All categories will be shown if none are selected. Please note that if you do not see categories, there are none created most probably. You can use ctr + click to select multiple categories.', 'fitness-wellness') ,
			'id'      => 'cat',
			'default' => array() ,
			'target'  => 'tribe_events_category',
			'type'    => 'multiselect',
		) ,

		array(
			'name'    => __('Title (optional)', 'fitness-wellness') ,
			'desc'    => __('The title is placed just above the element.', 'fitness-wellness'),
			'id'      => 'column_title',
			'default' => '',
			'type'    => 'text'
		) ,
		array(
			'name'    => __('Title Type (optional)', 'fitness-wellness') ,
			'id'      => 'column_title_type',
			'default' => 'single',
			'type'    => 'select',
			'options' => array(
				'single'     => __('Title with divider next to it', 'fitness-wellness'),
				'double'     => __('Title with divider under it ', 'fitness-wellness'),
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
