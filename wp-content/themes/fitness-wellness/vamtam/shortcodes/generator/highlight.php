<?php

return array(
	"name" => __("Highlight", 'fitness-wellness') ,
	"value" => "highlight",
	"options" => array(
		array(
			"name" => __("Type", 'fitness-wellness') ,
			"id" => "type",
			"default" => '',
			"options" => array(
				"light" => __("light", 'fitness-wellness') ,
				"dark" => __("dark", 'fitness-wellness') ,
			) ,
			"type" => "select",
		) ,
		array(
			"name" => __("Content", 'fitness-wellness') ,
			"id" => "content",
			"default" => "",
			"type" => "textarea"
		) ,
	)
);