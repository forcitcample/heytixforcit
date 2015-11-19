<?php

/**
 * Theme options / General / General Settings
 *
 * @package wpv
 * @subpackage fitness-wellness
 */

return array(
array(
	'name' => __('General Settings', 'fitness-wellness'),
	'type' => 'start'
),

array(
	'name' => __('Header Logo Type', 'fitness-wellness'),
	'id'   => 'header-logo-type',
	'type' => 'select',
	'options' => array(
		'image'      => __( 'Image', 'fitness-wellness' ),
		'site-title' => __( 'Site Title', 'fitness-wellness' ),
	),
	'static'       => true,
	'field_filter' => 'fblogo',
),

array(
	'name'   => __('Custom Logo Picture', 'fitness-wellness'),
	'desc'   => __('Please Put a logo which exactly twice the width and height of the space that you want the logo to occupy. The real image size is used for retina displays.', 'fitness-wellness'),
	'id'     => 'custom-header-logo',
	'type'   => 'upload',
	'static' => true,
	'class'  => 'fblogo fblogo-image',
),

array(
	'name'   => __('Alternative Logo', 'fitness-wellness'),
	'desc'   => __('This logo is used when you are using the transparent sticky header. It must be the same size as the main logo.', 'fitness-wellness'),
	'id'     => 'custom-header-logo-transparent',
	'type'   => 'upload',
	'static' => true,
	'class'  => 'fblogo fblogo-image',
),

array(
	'name'   => __('First Left Name', 'fitness-wellness'),
	'id'     => 'header-name-left-top',
	'type'   => 'text',
	'static' => true,
	'class'  => 'fblogo fblogo-names',
),

array(
	'name'   => __('Last Left Name', 'fitness-wellness'),
	'id'     => 'header-name-left-bottom',
	'type'   => 'text',
	'static' => true,
	'class'  => 'fblogo fblogo-names',
),

array(
	'name'   => __('First Right Name', 'fitness-wellness'),
	'id'     => 'header-name-right-top',
	'type'   => 'text',
	'static' => true,
	'class'  => 'fblogo fblogo-names',
),

array(
	'name'   => __('Last Right Name', 'fitness-wellness'),
	'id'     => 'header-name-right-bottom',
	'type'   => 'text',
	'static' => true,
	'class'  => 'fblogo fblogo-names',
),

array(
	'name'   => __('Splash Screen Logo', 'fitness-wellness'),
	'id'     => 'splash-screen-logo',
	'type'   => 'upload',
	'static' => true,
),

array(
	'name'   => __('Favicon', 'fitness-wellness'),
	'desc'   => __('Upload your custom "favicon" which is visible in browser favourites and tabs. (Must be .png or .ico file - preferably 16px by 16px ). Leave blank if none required.', 'fitness-wellness'),
	'id'     => 'favicon_url',
	'type'   => 'upload',
	'static' => true,
),

array(
	'name'   => __('Google Maps API Key', 'fitness-wellness'),
	'desc'   => __("Only required if you have more than 2500 map loads per day. Paste your Google Maps API Key here. If you don't have one, please sign up for a <a href='https://developers.google.com/maps/documentation/javascript/tutorial#api_key'>Google Maps API key</a>.", 'fitness-wellness'),
	'id'     => 'gmap_api_key',
	'type'   => 'text',
	'static' => true,
	'class'  => 'hidden',

),

array(
	'name'   => __('Google Analytics Key', 'fitness-wellness'),
	'desc'   => __("Paste your key here. It should be something like UA-XXXXX-X. We're using the faster asynchronous loader, so you don't need to worry about speed.", 'fitness-wellness'),
	'id'     => 'analytics_key',
	'type'   => 'text',
	'static' => true,
),

array(
	'name' => __('"Scroll to Top" Button', 'fitness-wellness'),
	'desc' => __('It is found in the bottom right side. It is sole purpose is help the user scroll a long page quickly to the top.', 'fitness-wellness'),
	'id'   => 'show_scroll_to_top',
	'type' => 'toggle',
),

array(
	'name'    => __('Feedback Button', 'fitness-wellness'),
	'desc'    => __('It is found on the right hand side of your website. You can chose from a "link" or a slide out form(widget area).The slide out form is configured as a standard widget. You can use the same form you are using for your "contact us" page.', 'fitness-wellness'),
	'id'      => 'feedback-type',
	'type'    => 'select',
	'options' => array(
		'none'    => __('None', 'fitness-wellness'),
		'link'    => __('Link', 'fitness-wellness'),
		'sidebar' => __('Slide out widget area', 'fitness-wellness'),
	),
),

array(
	'name' => __('Feedback Button Link', 'fitness-wellness'),
	'desc' => __('If you have chosen a "link" in the option above, place the link of the button here, usually to your contact us page.', 'fitness-wellness'),
	'id'   => 'feedback-link',
	'type' => 'text',
),

array(
	'name'   => __('Share Icons', 'fitness-wellness'),
	'desc'   => __('Select the social media you want enabled and for which parts of the website', 'fitness-wellness'),
	'type'   => 'social',
	'static' => true,
),

array(
	'name'   => __('Custom JavaScript', 'fitness-wellness'),
	'desc'   => __('If the hundreds of options in the Theme Options Panel are not enough and you need customisation that is outside of the scope of the Theme Option Panel please place your javascript in this field. The contents of this field are placed near the <strong>&lt;/body&gt;</strong> tag, which improves the load times of the page.', 'fitness-wellness'),
	'id'     => 'custom_js',
	'type'   => 'textarea',
	'rows'   => 15,
	'static' => true,
),

array(
	'name'  => __('Custom CSS', 'fitness-wellness'),
	'desc'  => __('If the hundreds of options in the Theme Options Panel are not enough and you need customisation that is outside of the scope of the Theme Options Panel please place your CSS in this field.', 'fitness-wellness'),
	'id'    => 'custom_css',
	'type'  => 'textarea',
	'rows'  => 15,
	'class' => 'top-desc',
),

array(
	'type' => 'end'
)
);