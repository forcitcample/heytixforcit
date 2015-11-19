<?php
/**
 * Theme options / Styles / Body
 *
 * @package wpv
 * @subpackage fitness-wellness
 */

return array(

array(
	'name' => __('Body', 'fitness-wellness'),
	'type' => 'start',
),

array(
	'name' => __('Where are these options used?', 'fitness-wellness'),
	'desc' => __('The page body is the area between the header and the footer. The page title, the body top widget areas and the sidebars are located here. You can change the style of these areas using the options below.', 'fitness-wellness'),
	'type' => 'info',
),

array(
	'name' => __('Backgrounds', 'fitness-wellness'),
	'type' => 'separator',
),

array(
	'name' => __('Body Background', 'fitness-wellness'),
	'desc' => __('If you want to use an image as a background, enabling the cover button will resize and crop the image so that it will always fit the browser window on any resolution. If the color opacity  is less than 1 the page background underneath will be visible.', 'fitness-wellness'),
	'id' => 'main-background',
	'type' => 'background',
	'only' => 'color,image,repeat,size,attachment'
),

array(
	'name' => __('Typography', 'fitness-wellness'),
	'type' => 'separator',
),

array(
	'name' => __('Body Font', 'fitness-wellness'),
	'desc' => __('This is the general font used in the body and the sidebars. Please note that the styles of the heading fonts are located in the general typography tab.', 'fitness-wellness'),
	'id' => 'primary-font',
	'type' => 'font',
	'min' => 1,
	'max' => 20,
	'lmin' => 1,
	'lmax' => 40,
),

array(
	'name' => __('Links', 'fitness-wellness'),
	'type' => 'color-row',
	'inputs' => array(
		'css_link_color' => array(
			'name' => __('Normal:', 'fitness-wellness'),
		),
		'css_link_visited_color' => array(
			'name' => __('Visited:', 'fitness-wellness'),
		),
		'css_link_hover_color' => array(
			'name' => __('Hover:', 'fitness-wellness'),
		),
	),
),

	array(
		'type' => 'end'
	),

);