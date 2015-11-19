<?php
return 	array(
	'name' => __('Team Member', 'fitness-wellness'),
	'icon' => array(
		'char'    => WPV_Editor::get_icon('profile'),
		'size'    => '26px',
		'lheight' => '39px',
		'family'  => 'vamtam-editor-icomoon',
	),
	'value'    => 'team_member',
	'controls' => 'size name clone edit delete',
	'options'  => array(

		array(
			'name'    => __('Name', 'fitness-wellness'),
			'id'      => 'name',
			'default' => 'Nikolay Yordanov',
			'type'    => 'text',
			'holder'  => 'h5',
		),
		array(
			'name'    => __('Position', 'fitness-wellness'),
			'id'      => 'position',
			'default' => 'Web Developer',
			'type'    => 'text'
		),
		array(
			'name'    => __('Link', 'fitness-wellness'),
			'id'      => 'url',
			'default' => '/',
			'type'    => 'text'
		),
		array(
			'name'    => __('Email', 'fitness-wellness'),
			'id'      => 'email',
			'default' => 'support@vamtam.com',
			'type'    => 'text'
		),
		array(
			'name'    => __('Phone', 'fitness-wellness'),
			'id'      => 'phone',
			'default' => '+448786562223',
			'type'    => 'text'
		),
		array(
			'name'    => __('Picture', 'fitness-wellness'),
			'id'      => 'picture',
			'default' => 'http://makalu.vamtam.com/wp-content/uploads/2013/03/people4.png',
			'type'    => 'upload'
		),

		array(
			'name'    => __('Biography', 'fitness-wellness') ,
			'id'      => 'html-content',
			'default' => __('This is Photoshop’s version of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet. Aenean sollicitudin, lorem quis bibendum auctor, nisi elit consequat ipsum, nec sagittis sem nibh id elit. Duis sed odio sit amet nibh vulputate cursus a sit amet mauris. Morbi accumsan ipsum velit. Nam nec tellus a odio tincidunt auctor a ornare odio. Sed non mauris vitae erat consequat auctor eu in elit.', 'fitness-wellness'),
			'type'    => 'editor',
			'holder'  => 'textarea',
		) ,

		array(
			'name'    => __('Google+', 'fitness-wellness'),
			'id'      => 'googleplus',
			'default' => '/',
			'type'    => 'text'
		),
		array(
			'name'    => __('LinkedIn', 'fitness-wellness'),
			'id'      => 'linkedin',
			'default' => '',
			'type'    => 'text'
		),
		array(
			'name'    => __('Facebook', 'fitness-wellness'),
			'id'      => 'facebook',
			'default' => '/',
			'type'    => 'text'
		),
		array(
			'name'    => __('Twitter', 'fitness-wellness'),
			'id'      => 'twitter',
			'default' => '/',
			'type'    => 'text'
		),
		array(
			'name'    => __('YouTube', 'fitness-wellness'),
			'id'      => 'youtube',
			'default' => '/',
			'type'    => 'text'
		),
		array(
			'name'    => __('Pinterest', 'fitness-wellness'),
			'id'      => 'pinterest',
			'default' => '/',
			'type'    => 'text'
		),
		array(
			'name'    => __('LastFM', 'fitness-wellness'),
			'id'      => 'lastfm',
			'default' => '/',
			'type'    => 'text'
		),
		array(
			'name'    => __('Instagram', 'fitness-wellness'),
			'id'      => 'instagram',
			'default' => '/',
			'type'    => 'text'
		),
		array(
			'name'    => __('Dribble', 'fitness-wellness'),
			'id'      => 'dribble',
			'default' => '/',
			'type'    => 'text'
		),
		array(
			'name'    => __('Vimeo', 'fitness-wellness'),
			'id'      => 'vimeo',
			'default' => '/',
			'type'    => 'text'
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
	),
);
