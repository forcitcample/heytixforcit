<?php
return array(
	'name'    => __('Buttons', 'fitness-wellness') ,
	'value'   => 'button',
	'options' => array(
		array(
			'name'    => __('Text', 'fitness-wellness') ,
			'id'      => 'text',
			'default' => '',
			'type'    => 'text',
		) ,
		array(
			'name'    => __('Style', 'fitness-wellness') ,
			'id'      => 'style',
			'default' => 'filled-small',
			'type'    => 'select',
			'options' => array(
				'filled'         => __('Filled', 'fitness-wellness'),
				'filled-small'   => __('Filled, small', 'fitness-wellness'),
				'border'         => __('Border', 'fitness-wellness'),
				'border-slanted' => __('Border Slanted', 'fitness-wellness'),
			),
		) ,
		array(
			'name'    => __('Font size', 'fitness-wellness') ,
			'id'      => 'font',
			'default' => 24,
			'type'    => 'range',
			'min'     => 10,
			'max'     => 64,
		) ,
		array(
			'name'    => __('Background', 'fitness-wellness') ,
			'id'      => 'bgColor',
			'default' => 'accent1',
			'type'    => 'select',
			'options' => array(
				'accent1' => __('Accent 1', 'fitness-wellness'),
				'accent2' => __('Accent 2', 'fitness-wellness'),
				'accent3' => __('Accent 3', 'fitness-wellness'),
				'accent4' => __('Accent 4', 'fitness-wellness'),
				'accent5' => __('Accent 5', 'fitness-wellness'),
				'accent6' => __('Accent 6', 'fitness-wellness'),
				'accent7' => __('Accent 7', 'fitness-wellness'),
				'accent8' => __('Accent 8', 'fitness-wellness'),
			),
		) ,
		array(
			'name'    => __('Hover Background', 'fitness-wellness') ,
			'id'      => 'hover_color',
			'default' => 'accent1',
			'type'    => 'select',
			'options' => array(
				'accent1' => __('Accent 1', 'fitness-wellness'),
				'accent2' => __('Accent 2', 'fitness-wellness'),
				'accent3' => __('Accent 3', 'fitness-wellness'),
				'accent4' => __('Accent 4', 'fitness-wellness'),
				'accent5' => __('Accent 5', 'fitness-wellness'),
				'accent6' => __('Accent 6', 'fitness-wellness'),
				'accent7' => __('Accent 7', 'fitness-wellness'),
				'accent8' => __('Accent 8', 'fitness-wellness'),
			),
		) ,
		array(
			'name'    => __('Alignment', 'fitness-wellness') ,
			'id'      => 'align',
			'default' => '',
			'prompt'  => '',
			'type'    => 'select',
			'options' => array(
				'left'   => __('Left', 'fitness-wellness') ,
				'right'  => __('Right', 'fitness-wellness') ,
				'center' => __('Center', 'fitness-wellness') ,
			) ,
		) ,
		array(
			'name'    => __('Link', 'fitness-wellness') ,
			'id'      => 'link',
			'default' => '',
			'type'    => 'text',
		) ,
		array(
			'name'    => __('Link Target', 'fitness-wellness') ,
			'id'      => 'linkTarget',
			'default' => '_self',
			'type'    => 'select',
			'options' => array(
				'_blank' => __('Load in a new window', 'fitness-wellness') ,
				'_self'  => __('Load in the same frame as it was clicked', 'fitness-wellness') ,
			) ,
		) ,
		array(
			'name'    => __('Icon', 'fitness-wellness') ,
			'id'      => 'icon',
			'default' => '',
			'type'    => 'icons',
		) ,
		array(
			'name'    => __('Icon Style', 'fitness-wellness'),
			'type'    => 'select-row',
			'selects' => array(
				'icon_color' => array(
					'desc'    => __('Color:', 'fitness-wellness'),
					"default" => "",
					"prompt"  => '',
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
				'icon_placement' => array(
					'desc'    => __('Placement:', 'fitness-wellness'),
					"default" => 'left',
					"options" => array(
						'left'  => __('Left', 'fitness-wellness'),
						'right' => __('Right', 'fitness-wellness'),
					) ,
				),
			),
		),

		array(
			'name'    => __('ID', 'fitness-wellness') ,
			'desc'    => __('ID attribute added to the button element.', 'fitness-wellness'),
			'id'      => 'id',
			'default' => '',
			'type'    => 'text',
		) ,
		array(
			'name'    => __('Class', 'fitness-wellness') ,
			'desc'    => __('Class attribute added to the button element.', 'fitness-wellness'),
			'id'      => 'class',
			'default' => '',
			'type'    => 'text',
		) ,
	) ,
);
