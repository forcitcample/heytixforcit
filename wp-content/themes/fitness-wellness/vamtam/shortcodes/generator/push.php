<?php
return array(
	'name' => __('Vertical Blank Space', 'fitness-wellness') ,
	'value' => 'push',
	'options' => array(
		array(
			"name" => __("Height", 'fitness-wellness') ,
			"id" => "h",
			"default" => 30,
			'min' => -200,
			'max' => 200,
			"type" => "range",
		) ,
	) ,
);
