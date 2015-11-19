<?php
return array(
	'name' => __('Drop Cap', 'fitness-wellness') ,
	'value' => 'dropcap',
	'options' => array(
		array(
			'name' => __('Type', 'fitness-wellness') ,
			'id' => 'type',
			'default' => '1',
			'type' => 'select',
			'options' => array(
				'1' => __('Type 1', 'fitness-wellness'),
				'2' => __('Type 2', 'fitness-wellness'),
			),
		) ,
		array(
			'name' => __('Text', 'fitness-wellness') ,
			'id' => 'text',
			'default' => '',
			'type' => 'text',
		) ,
	) ,
);
