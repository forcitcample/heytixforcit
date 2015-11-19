<?php
return array(
	'name' => __('Price Box', 'fitness-wellness') ,
	'icon' => array(
		'char' => WPV_Editor::get_icon('basket1'),
		'size' => '30px',
		'lheight' => '45px',
		'family' => 'vamtam-editor-icomoon',
	),
	'value' => 'price',
	'controls' => 'size name clone edit delete',
	'options' => array(

		array(
			'name' => __('Title', 'fitness-wellness') ,
			'id' => 'title',
			'default' => __('Title', 'fitness-wellness'),
			'type' => 'text',
			'holder' => 'h5',
		) ,
		array(
			'name' => __('Price', 'fitness-wellness') ,
			'id' => 'price',
			'default' => '69',
			'type' => 'text',
		) ,
		array(
			'name' => __('Currency', 'fitness-wellness') ,
			'id' => 'currency',
			'default' => '$',
			'type' => 'text',
		) ,
		array(
			'name' => __('Duration', 'fitness-wellness') ,
			'id' => 'duration',
			'default' => 'per month',
			'type' => 'text',
		) ,
		array(
			'name' => __('Summary', 'fitness-wellness') ,
			'id' => 'summary',
			'default' => '',
			'type' => 'text',
		) ,
		array(
			'name' => __('Description', 'fitness-wellness') ,
			'id' => 'html-content',
			'default' => '<ul>
	<li>list item</li>
	<li>list item</li>
	<li>list item</li>
	<li>list item</li>
	<li>list item</li>
	<li>list item</li>
</ul>',
			'type' => 'editor',
			'holder' => 'textarea',
		) ,
		array(
			'name' => __('Button Text', 'fitness-wellness') ,
			'id' => 'button_text',
			'default' => 'Buy',
			'type' => 'text'
		) ,
		array(
			'name' => __('Button Link', 'fitness-wellness') ,
			'id' => 'button_link',
			'default' => '',
			'type' => 'text'
		) ,
		array(
			'name' => __('Featured', 'fitness-wellness') ,
			'id' => 'featured',
			'default' => 'false',
			'type' => 'toggle'
		) ,


		array(
			'name' => __('Title', 'fitness-wellness') ,
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
				'double' => __('Title with divider below', 'fitness-wellness'),
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
	) ,
);
