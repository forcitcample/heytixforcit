<?php
/**
 * Vamtam Post Options
 *
 * @package wpv
 * @subpackage fitness-wellness
 */

return array(

array(
	'name' => __('General', 'fitness-wellness'),
	'type' => 'separator',
),

array(
	"name" => __("Cite", 'fitness-wellness') ,
	"id" => "testimonial-author",
	"default" => "",
	"type" => "text",
) ,

array(
	"name" => __("Link", 'fitness-wellness') ,
	"id" => "testimonial-link",
	"default" => "",
	"type" => "text",
) ,

);
