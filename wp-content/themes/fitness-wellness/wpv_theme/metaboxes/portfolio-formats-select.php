<?php
/**
 * Vamtam Portfolio Format Selector
 *
 * @package wpv
 * @subpackage fitness-wellness
 */

return array(

array(
	'name' => __('Portfolio Format', 'fitness-wellness'),
	'type' => 'separator'
),

array(
	'name' => __('Portfolio Data Type', 'fitness-wellness'),
	'desc' => __('Image - uses the featured image (default)<br />
				  Gallery - use the featured image as a title image but show additional images too<br />
				  Video/Link - uses the "portfolio data url" setting<br />
				  Document - acts like a normal post<br />
				  HTML - overrides the image with arbitrary HTML when displaying a single portfolio page. Does not work with the ajax portfolio.
				', 'fitness-wellness'),
	'id' => 'portfolio_type',
	'type' => 'radio',
	'options' => array(
		'image' => __('Image', 'fitness-wellness'),
		'gallery' => __('Gallery', 'fitness-wellness'),
		'video' => __('Video', 'fitness-wellness'),
		'link' => __('Link', 'fitness-wellness'),
		'document' => __('Document', 'fitness-wellness'),
		'html' => __('HTML', 'fitness-wellness'),
	),
	'default' => 'image',
),

);
