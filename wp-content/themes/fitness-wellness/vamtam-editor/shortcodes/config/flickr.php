<?php
return array(
	'name' => __('Flickr', 'fitness-wellness') ,
	'desc' => __('This element is usefull if you have a Flickr account. Use <a href="http://idgettr.com/" target="_blank">idGettr</a> if you don\'t know your ID.<br/><br/>.' , 'fitness-wellness'),
	'icon' => array(
		'char' => WPV_Editor::get_icon('flickr'),
		'size' => '30px',
		'lheight' => '45px',
		'family' => 'vamtam-editor-icomoon',
	),
	'value' => 'flickr',
	'controls' => 'size name clone edit delete',
	'options' => array(

		array(
			'name' => __('Flickr ID', 'fitness-wellness'),
			'desc' => __('Use <a href="http://idgettr.com/" target="_blank">idGettr</a> if you don\'t know your ID.<br/><br/>', 'fitness-wellness'),
			'id' => 'id',
			'default' => '',
			'type' => 'text'
		),
		
		array(
			'name' => __('Type', 'fitness-wellness'),
			'id' => 'type',
			'default' => 'page',
			'options' => array(
				'user' => __('User', 'fitness-wellness'),
				'group' => __('Group', 'fitness-wellness'),
			),
			'type' => 'select',
		),
		
		array(
			'name' => __('Count', 'fitness-wellness'),
			'desc' => '',
			'id' => 'count',
			'default' => 4,
			'min' => 0,
			'max' => 20,
			'type' => 'range'
		),
		array(
			'name' => __('Display', 'fitness-wellness'),
			'id' => 'display',
			'default' => 'latest',
			'options' => array(
				'latest' => __('Latest', 'fitness-wellness'),
				'random' => __('Random', 'fitness-wellness'),
			),
			'type' => 'select',
		),
		
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
	

	) ,
);
