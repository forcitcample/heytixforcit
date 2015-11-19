<?php
/**
 * Theme options / General / Media
 *
 * @package wpv
 * @subpackage fitness-wellness
 */

return array(

array(
	'name' => __('Media', 'fitness-wellness'),
	'type' => 'start',

),

array(
	'name' => __('How do I use these options?', 'fitness-wellness'),
	'desc' => sprintf(__('These options control the size of some of the images used by the theme. <br><br> <strong>Changes are not immediate</strong><br><br> If you have changed any of these options, please use the <a href="%s" title="Regenerate thumbnails" target="_blank">Regenerate thumbnails</a> plugin in order to update your images.', 'fitness-wellness'), 'http://wordpress.org/extend/plugins/regenerate-thumbnails/'),
	'type' => 'info',
	'class' => "important",
),

array(
	'name' => __('Portfolio', 'fitness-wellness'),
	'type' => 'separator',
),

array(
	'name' => __('Portfolio Listing Featured Images Width-to-Height Ratio', 'fitness-wellness'),
	'desc' => __('You can set it to 0 to disable this and the option below  and show the real size of the images.', 'fitness-wellness'),
	'id' => 'portfolio-loop-images-wth',
	'type' => 'range',
	'min' => 0,
	'max' => 3,
	'step' => 0.05,
),



array(
	'name' => __('Single Portfolio Page Featured Image Width-to-Height Ratio', 'fitness-wellness'),
	'desc' => __('You can set it to 0 to disable this and the option below  and show the real size of the images.', 'fitness-wellness'),
	'id' => 'single-portfolio-images-wth',
	'type' => 'range',
	'min' => 0,
	'max' => 3,
	'step' => 0.05,
),




array(
	'name' => __('Blog', 'fitness-wellness'),
	'type' => 'separator',
),

array(
	'name' => __('Blog Listing Image Width-to-Height Ratio', 'fitness-wellness'),
	'desc' => __('You can set it to 0 to disable this and the option below  and show the real size of the images.', 'fitness-wellness'),
	'id' => 'post-loop-images-wth',
	'type' => 'range',
	'min' => 0,
	'max' => 3,
	'step' => 0.05,
),





array(
	'name' => __('Blog Single Post Image Width-to-Height Ratio', 'fitness-wellness'),
	'desc' => __('You can set it to 0 to disable this and the option below  and show the real size of the images.', 'fitness-wellness'),
	'id' => 'single-post-images-wth',
	'type' => 'range',
	'min' => 0,
	'max' => 3,
	'step' => 0.05,
),


array(
	'name' => __('Blog Small Featured Image Width-to-Height Ratio', 'fitness-wellness'),
	'desc' => __('This image is used in News Style Blog in columns. You can set it to 0 to disable this and the option below  and show the real size of the images.', 'fitness-wellness'),
	'id' => 'post-small-images-wth',
	'type' => 'range',
	'min' => 0,
	'max' => 3,
	'step' => 0.05,
),


	array(
		'type' => 'end'
	),
);
