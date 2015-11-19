<?php

/**
 * Expandable services shortcode options
 *
 * @package wpv
 * @subpackage editor
 */

return array(
	'name' => __('Expandable Box ', 'fitness-wellness') ,
	'desc' => __('You have open and closed states of the box and you can set diffrenet content and background of each state.' , 'fitness-wellness'),
	'icon' => array(
		'char' => WPV_Editor::get_icon('expand1'),
		'size' => '26px',
		'lheight' => '39px',
		'family' => 'vamtam-editor-icomoon',
	),
	'value' => 'services_expandable',
	'controls' => 'size name clone edit delete',
	'callbacks' => array(
		'init' => 'init-expandable-services',
		'generated-shortcode' => 'generate-expandable-services',
	),
	'options' => array(
		array(
			'name' => __('Closed Background', 'fitness-wellness') ,
			'type' => 'background',
			'id'   => 'background',
			'only' => 'color,image,repeat,size',
			'sep'  => '_',
		) ,

		array(
			'name'    => __('Expanded Background', 'fitness-wellness') ,
			'type'    => 'color',
			'id'      => 'hover_background',
			'default' => 'accent1',
		) ,

		array(
			'name'    => __('Closed state image', 'fitness-wellness') ,
			'id'      => 'image',
			'default' => '',
			'type'    => 'upload'
		) ,

		array(
			'name'    => __('Closed state icon', 'fitness-wellness') ,
			'desc'    => __('The icon will not be visable if you have an image in the option above.', 'fitness-wellness'),
			'id'      => 'icon',
			'default' => '',
			'type'    => 'icons',
		) ,
		array(
			"name"    => __("Icon Color", 'fitness-wellness') ,
			"id"      => "icon_color",
			"default" => 'accent6',
			"type"    => "color",
		) ,
		array(
			'name'    => __('Icon Size', 'fitness-wellness'),
			'id'      => 'icon_size',
			'type'    => 'range',
			'default' => 62,
			'min'     => 8,
			'max'     => 100,
		),

		array(
			'name'    => __('Title', 'fitness-wellness') ,
			'type'    => 'text',
			'id'      => 'title',
			'default' => '',
		) ,

		array(
			'name'    => __('Closed state text', 'fitness-wellness') ,
			'id'      => 'closed',
			'default' => __('Proin gravida nibh vel velit auctor aliquet. Aenean sollicitudin, lorem quis bibendum auctor, nisi elit consequat ipsum, nec sagittis sem nibh id elit. Duis sed odio sit amet nibh vulputate cursus a sit amet mauris. Morbi accumsan ipsum velit. Nam nec tellus a odio tincidunt auctor a ornare odio. Sed non mauris vitae erat consequat auctor eu in elit.', 'fitness-wellness'),
			'type'    => 'textarea',
			'class'   => 'noattr',
		) ,

        array(
			'name'    => __('Expanded state', 'fitness-wellness') ,
			'id'      => 'html-content',
			'default' => '[split]',
			'type'    => 'editor',
			'holder'  => 'textarea',
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
