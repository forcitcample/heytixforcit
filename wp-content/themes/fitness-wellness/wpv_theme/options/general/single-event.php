<?php

/**
 * Theme options / General / Single Event
 *
 * @package wpv
 * @subpackage church-event
 */

return array(

array(
	'name' => __('Tribe Events', 'fitness-wellness'),
	'type' => 'start',
),

array(
	'name' => __('Single Event', 'fitness-wellness'),
	'type' => 'separator'
),

array(
	'name' => __('Footer Content', 'fitness-wellness'),
	'id'   => 'events-after-sidebars-2-content',
	'type' => 'textarea',
),

array(
	'name' => __('Footer Background', 'fitness-wellness'),
	'id'   => 'events-after-sidebars-2-background',
	'type' => 'background',
),

array(
	'name' => __('After Details Content', 'fitness-wellness'),
	'id'   => 'events-after-details-content',
	'type' => 'textarea',
),

array(
	'name' => __('Listing', 'fitness-wellness'),
	'type' => 'separator'
),

array(
	'name' => __('Title Background', 'fitness-wellness'),
	'id'   => 'events-listing-title-background',
	'type' => 'background',
	'only' => 'color,image,repeat,position,size',
),

	array(
		'type' => 'end'
	),
);