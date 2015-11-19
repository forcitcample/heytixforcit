<?php

return array(
	"name" => __("Styled List", 'fitness-wellness') ,
	"value" => "list",
	"options" => array(
		array(
			'name' => __('Style', 'fitness-wellness') ,
			'id' => 'style',
			'default' => '',
			'type' => 'icons',
		) ,
		array(
			"name" => __("Color", 'fitness-wellness') ,
			"id" => "color",
			"default" => "",
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
			"name" => __("Content", 'fitness-wellness') ,
			"desc" => __("Please insert a valid HTML unordered list", 'fitness-wellness') ,
			"id" => "content",
			"default" => "<ul>
				<li>list item</li>
				<li>another item</li>
			</ul>",
			"type" => "textarea"
		) ,
	)
);