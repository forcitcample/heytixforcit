<?php
return array(
	'name' => __('Service Box', 'fitness-wellness') ,
	'desc' => __('Please note that the service box may not work properly in one half to full width layouts.' , 'fitness-wellness'),
	'icon' => array(
		'char' => WPV_Editor::get_icon('cog1'),
		'size' => '30px',
		'lheight' => '45px',
		'family' => 'vamtam-editor-icomoon',
	),
	'value' => 'services',
	'controls' => 'size name clone edit delete',
	'options' => array(
		array(
			'name' => __('Style', 'fitness-wellness') ,
			'id' => 'fullimage',
			'default' => 'false',
			'type' => 'select',
			'options' => array(
				'false' => __('Style big icon with zoom out', 'fitness-wellness'),
				'true' => __('Style standard with an image or an icon ', 'fitness-wellness'),
			),
			'field_filter' => 'fbs',
		) ,

		array(
			'name' => __('Icon', 'fitness-wellness') ,
			'desc' => __('This option overrides the "Image" option.', 'fitness-wellness'),
			'id' => 'icon',
			'default' => 'apple',
			'type' => 'icons',
		) ,
		array(
			"name" => __("Icon Color", 'fitness-wellness') ,
			"id" => "icon_color",
			"default" => 'accent6',
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
			"type" => "select",
		) ,
		array(
			'name' => __('Icon Size', 'fitness-wellness'),
			'id' => 'icon_size',
			'type' => 'range',
			'default' => 62,
			'min' => 8,
			'max' => 100,
			'class' => 'fbs fbs-true',
		),
		array(
			'name' => __('Icon Background', 'fitness-wellness'),
			'id' => 'background',
			'default' => 'accent1',
			'type' => 'color',
			'class' => 'fbs fbs-false',
		),

		array(
			'name' => __('Image', 'fitness-wellness') ,
			'desc' => __('This option can be overridden by the "Icon" option.', 'fitness-wellness'),
			'id' => 'image',
			'default' => '',
			'type' => 'upload',
		) ,

		array(
			'name' => __('Title', 'fitness-wellness') ,
			'id' => 'title',
			'default' => 'This is a title',
			'type' => 'text',
		) ,

		array(
			'name' => __('Description', 'fitness-wellness') ,
			'id' => 'html-content',
			'default' => 'This is Photoshop’s version of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet.
Aenean sollicitudin, lorem quis bibendum auctor, nisi elit consequat ipsum, nec sagittis sem nibh id elit.

Duis sed odio sit amet nibh vulputate cursus a sit amet mauris. Morbi accumsan ipsum velit. Nam nec tellus a odio tincidunt auctor a ornare odio. Sed non mauris vitae erat consequat auctor eu in elit.',
			'type' => 'editor',
			'holder' => 'textarea',
		) ,

		array(
			'name' => __('Text Alignment', 'fitness-wellness') ,
			'id' => 'text_align',
			'default' => 'justify',
			'type' => 'select',
			'options' => array(
				'justify' => 'justify',
				'left' => 'left',
				'center' => 'center',
				'right' => 'right',
			)
		) ,
		array(
			'name' => __('Link', 'fitness-wellness') ,
			'id' => 'button_link',
			'default' => '/',
			'type' => 'text'
		) ,

		array(
			'name' => __('Button Text', 'fitness-wellness') ,
			'id' => 'button_text',
			'default' => 'learn more',
			'type' => 'text'
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
