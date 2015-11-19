<?php

return array(
	"name" => __("Icon", 'fitness-wellness') ,
	"value" => "icon",
	"options" => array(
		array(
			'name' => __('Name', 'fitness-wellness') ,
			'id' => 'name',
			'default' => '',
			'type' => 'icons',
		) ,
		array(
			"name" => __("Color (optional)", 'fitness-wellness') ,
			"id" => "color",
			"default" => "",
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
			'name' => __('Size', 'fitness-wellness'),
			'id' => 'size',
			'type' => 'range',
			'default' => 16,
			'min' => 8,
			'max' => 100,
		),
		array(
			"name" => __("Style", 'fitness-wellness') ,
			"id" => "style",
			"default" => '',
			"prompt" => __('Default', 'fitness-wellness'),
			"options" => array(
				'inverted-colors' => __('Invert colors', 'fitness-wellness'),
				'box' => __('Box', 'fitness-wellness'),
			) ,
			"type" => "select",
		) ,
	)
);