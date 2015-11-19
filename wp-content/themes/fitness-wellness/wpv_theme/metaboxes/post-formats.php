<?php
/**
 * Vamtam Post Format Options
 *
 * @package wpv
 * @subpackage fitness-wellness
 */

return array(

array(
	'name' => __('Standard', 'fitness-wellness'),
	'type' => 'separator',
	'tab_class' => 'wpv-post-format-0',
),

array(
	'name' => __('How do I use standard post format?', 'fitness-wellness'),
	'desc' => __('Just use the editor below.', 'fitness-wellness'),
	'type' => 'info',
	'visible' => true,
),

// --

array(
	'name' => __('Aside', 'fitness-wellness'),
	'type' => 'separator',
	'tab_class' => 'wpv-post-format-aside',
),

array(
	'name' => __('How do I use aside post format?', 'fitness-wellness'),
	'desc' => __('Just use the editor below. The post title will not be shown publicly.', 'fitness-wellness'),
	'type' => 'info',
	'visible' => true,
),

// --

array(
	'name' => __('Link', 'fitness-wellness'),
	'type' => 'separator',
	'tab_class' => 'wpv-post-format-link',
),

array(
	'name' => __('How do I use link post format?', 'fitness-wellness'),
	'desc' => __('Use the editor below for the post body, put the link in the option below.', 'fitness-wellness'),
	'type' => 'info',
	'visible' => true,
),

array(
	'name' => __('Link', 'fitness-wellness'),
	'id' => 'wpv-post-format-link',
	'type' => 'text',
),

// --

array(
	'name' => __('Image', 'fitness-wellness'),
	'type' => 'separator',
	'tab_class' => 'wpv-post-format-image',
),

array(
	'name' => __('How do I use image post format?', 'fitness-wellness'),
	'desc' => __('Use the standard Featured Image option.', 'fitness-wellness'),
	'type' => 'info',
	'visible' => true,
),

// --

array(
	'name' => __('Video', 'fitness-wellness'),
	'type' => 'separator',
	'tab_class' => 'wpv-post-format-video',
),

array(
	'name' => __('How do I use video post format?', 'fitness-wellness'),
	'desc' => __('Put the url of the video below. You must use an oEmbed provider supported by WordPress or a file supported by the [video] shortcode which comes with WordPress.', 'fitness-wellness'),
	'type' => 'info',
	'visible' => true,
),

array(
	'name' => __('Link', 'fitness-wellness'),
	'id' => 'wpv-post-format-video-link',
	'type' => 'text',
),

// --

array(
	'name' => __('Audio', 'fitness-wellness'),
	'type' => 'separator',
	'tab_class' => 'wpv-post-format-audio',
),

array(
	'name' => __('How do I use auido post format?', 'fitness-wellness'),
	'desc' => __('Put the url of the audio below. You must use an oEmbed provider supported by WordPress or a file supported by the [audio] shortcode which comes with WordPress.', 'fitness-wellness'),
	'type' => 'info',
	'visible' => true,
),

array(
	'name' => __('Link', 'fitness-wellness'),
	'id' => 'wpv-post-format-audio-link',
	'type' => 'text',
),

// --

array(
	'name' => __('Quote', 'fitness-wellness'),
	'type' => 'separator',
	'tab_class' => 'wpv-post-format-quote',
),

array(
	'name' => __('How do I use quote post format?', 'fitness-wellness'),
	'desc' => __('Simply fill in author and link fields', 'fitness-wellness'),
	'type' => 'info',
	'visible' => true,
),

array(
	'name' => __('Author', 'fitness-wellness'),
	'id' => 'wpv-post-format-quote-author',
	'type' => 'text',
),

array(
	'name' => __('Link', 'fitness-wellness'),
	'id' => 'wpv-post-format-quote-link',
	'type' => 'text',
),

// --

array(
	'name' => __('Gallery', 'fitness-wellness'),
	'type' => 'separator',
	'tab_class' => 'wpv-post-format-gallery',
),

array(
	'name' => __('How do I use gallery post format?', 'fitness-wellness'),
	'desc' => __('Use the "Add Media" in a text/image block element to create a gallery.This button is also found in the top left side of the visual and text editors.', 'fitness-wellness'),
	'type' => 'info',
	'visible' => true,
),

// --

array(
	'name' => __('Status', 'fitness-wellness'),
	'type' => 'separator',
	'tab_class' => 'wpv-post-format-status',
),

array(
	'name' => __('How do I use this post format?', 'fitness-wellness'),
	'desc' => __('...', 'fitness-wellness'),
	'type' => 'info',
	'visible' => true,
),

);
