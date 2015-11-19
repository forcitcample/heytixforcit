<?php
return array(
	'name' => __('Featured Products', 'fitness-wellness') ,
	'icon' => array(
		'char' => WPV_Editor::get_icon('cart1'),
		'size' => '26px',
		'lheight' => '39px',
		'family' => 'vamtam-editor-icomoon',
	),
	'value' => 'wpv_featured_products',
	'controls' => 'size name clone edit delete',
	'options' => array(
		array(
			'name' => __('Columns', 'fitness-wellness') ,
			'id' => 'columns',
			'default' => 4,
			'min' => 2,
			'max' => 4,
			'type' => 'range',
		) ,
		array(
			'name' => __('Limit', 'fitness-wellness') ,
			'desc' => __('Maximum number of products.', 'fitness-wellness') ,
			'id' => 'per_page',
			'default' => 3,
			'min' => 1,
			'max' => 50,
			'type' => 'range',
		) ,

		array(
			'name' => __('Order By', 'fitness-wellness') ,
			'id' => 'orderby',
			'default' => 'date',
			'type' => 'radio',
			'options' => array(
				'date' => __('Date', 'fitness-wellness'),
				'menu_order' => __('Menu Order', 'fitness-wellness'),
			),
		) ,

		array(
			'name' => __('Order', 'fitness-wellness') ,
			'id' => 'order',
			'default' => 'desc',
			'type' => 'radio',
			'options' => array(
				'desc' => __('Descending', 'fitness-wellness'),
				'asc' => __('Ascending', 'fitness-wellness'),
			),
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
