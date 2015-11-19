<?php

/**
 * Slogan shortcode options
 *
 * @package wpv
 * @subpackage editor
 */

return array(
	'name' => __('Call Out Box', 'fitness-wellness') ,
	'desc' => __('You can place the call out box into а column - color box elemnent in order to have background color.' , 'fitness-wellness'),
	'icon' => array(
		'char' => WPV_Editor::get_icon('font-size'),
		'size' => '30px',
		'lheight' => '45px',
		'family' => 'vamtam-editor-icomoon',
	),
	'value' => 'slogan',
	'controls' => 'size name clone edit delete handle',
	'options' => array(
		array(
			'name' => __('Content', 'fitness-wellness') ,
			'id' => 'html-content',
			'default' => __('<h1>You can place your call out box text here</h1>', 'fitness-wellness'),
			'type' => 'editor',
			'holder' => 'textarea',
		) ,
		array(
			'name' => __('Button Text', 'fitness-wellness') ,
			'id' => 'button_text',
			'default' => 'Button Text',
			'type' => 'text'
		) ,
		array(
			'name' => __('Button Link', 'fitness-wellness') ,
			'id' => 'link',
			'default' => '',
			'type' => 'text'
		) ,
		array(
			'name' => __('Button Icon', 'fitness-wellness') ,
			'id' => 'button_icon',
			'default' => 'cart',
			'type' => 'icons',
		) ,
		array(
			'name' => __('Button Icon Style', 'fitness-wellness'),
			'type' => 'select-row',
			'selects' => array(
				'button_icon_color' => array(
					'desc' => __('Color:', 'fitness-wellness'),
					"default" => "accent 1",
					"prompt" => '',
					"options" => array(
						'accent1' => __('Accent 1', 'fitness-wellness'),
						'accent2' => __('Accent 2', 'fitness-wellness'),
						'accent3' => __('Accent 3', 'fitness-wellness'),
						'accent4' => __('Accent 4', 'fitness-wellness'),
						'accent5' => __('Accent 5', 'fitness-wellness'),
						'accent6' => __('Accent 6', 'fitness-wellness'),
						'accent7' => __('Accent 7', 'fitness-wellness'),
						'accent8' => __('Accent 8', 'fitness-wellness'),
					) ,
				),
				'button_icon_placement' => array(
					'desc' => __('Placement:', 'fitness-wellness'),
					"default" => 'left',
					"options" => array(
						'left' => __('Left', 'fitness-wellness'),
						'right' => __('Right', 'fitness-wellness'),
					) ,
				),
				),
		),
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
