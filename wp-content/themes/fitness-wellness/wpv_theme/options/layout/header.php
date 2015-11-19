<?php

/**
 * Theme options / Layout / Header
 *
 * @package wpv
 * @subpackage fitness-wellness
 */

return array(
array(
	'name' => __('Header', 'fitness-wellness'),
	'type' => 'start',
),

array(
	'name' => __('Header Layout', 'fitness-wellness'),
	'desc' => __('Please note that the theme uses Revolution Slider and its option panel is found in the WordPress navigation menu on the left', 'fitness-wellness'),
	'type' => 'info',
),

array(
	'name'        => __('Header Layout', 'fitness-wellness'),
	'type'        => 'autofill',
	'class'       => 'no-box ' . ( wpv_get_option( 'header-logo-type' ) === 'names' ? 'hidden' : ''),
	'option_sets' => array(
		array(
			'name'   => __('One row, left logo, menu on the right', 'fitness-wellness'),
			'image'  => WPV_ADMIN_ASSETS_URI . 'images/header-layout-1.png',
			'values' => array(
				'header-layout' => 'logo-menu',
			),
		),
		array(
			'name'   => __('Two rows; left-aligned logo on top, right-aligned text and search', 'fitness-wellness'),
			'image'  => WPV_ADMIN_ASSETS_URI . 'images/header-layout-2.png',
			'values' => array(
				'header-layout' => 'logo-text-menu',
			),
		),
		array(
			'name'   => __('Two rows; centered logo on top', 'fitness-wellness'),
			'image'  => WPV_ADMIN_ASSETS_URI . 'images/header-layout-3.png',
			'values' => array(
				'header-layout' => 'standard',
			),
		),
	),
),


array(
	'name' => __('Header Height', 'fitness-wellness'),
	'desc' => __('This is the area above the slider.', 'fitness-wellness'),
	'id'   => 'header-height',
	'type' => 'range',
	'min'  => 30,
	'max'  => 300,
	'unit' => 'px',
),
array(
	'name' => __('Sticky Header', 'fitness-wellness'),
	'desc' => __('This option is switched off automatically for mobile devices because the animation is not well supported by the majority of the mobile devices.', 'fitness-wellness'),
	'id'   => 'sticky-header',
	'type' => 'toggle',
),


array(
	'name' => __('Enable Header Search', 'fitness-wellness'),
	'id'   => 'enable-header-search',
	'type' => 'toggle',
),

array(
	'name'  => __('Full Width Header', 'fitness-wellness'),
	'id'    => 'full-width-header',
	'type'  => 'toggle',
	'class' => 'fhl fhl-logo-menu',
),

array(
	'name'    => __('Top Bar Layout', 'fitness-wellness'),
	'id'      => 'top-bar-layout',
	'type'    => 'select',
	'options' => array(
		''            => __('Disabled', 'fitness-wellness'),
		'menu-social' => __('Left: Menu, Right: Social Icons', 'fitness-wellness'),
		'social-menu' => __('Left: Social Icons, Right: Menu', 'fitness-wellness'),
		'text-menu'   => __('Left: Text, Right: Menu', 'fitness-wellness'),
		'menu-text'   => __('Left: Menu, Right: Text', 'fitness-wellness'),
		'social-text' => __('Left: Social Icons, Right: Text', 'fitness-wellness'),
		'text-social' => __('Left: Text, Right: Social Icons', 'fitness-wellness'),
		'fulltext'    => __('Text only', 'fitness-wellness'),
	),
	'field_filter' => 'ftbl',
),

array(
	'name'  => __('Top Bar Text', 'fitness-wellness'),
	'desc'  => __('You can place plain text, HTML and shortcodes.', 'fitness-wellness'),
	'id'    => 'top-bar-text',
	'type'  => 'textarea',
	'class' => 'ftbl ftbl-menu-text ftbl-text-menu ftbl-social-text ftbl-text-social ftbl-fulltext',
),

array(
	'name'  => __('Top Bar Social Text Lead', 'fitness-wellness'),
	'id'    => 'top-bar-social-lead',
	'type'  => 'text',
	'class' => 'ftbl ftbl-menu-social ftbl-social-menu ftbl-social-text ftbl-text-social slim',
),

array(
	'name'  => __('Top Bar Facebook Link', 'fitness-wellness'),
	'id'    => 'top-bar-social-fb',
	'type'  => 'text',
	'class' => 'ftbl ftbl-menu-social ftbl-social-menu ftbl-social-text ftbl-text-social slim',
),

array(
	'name'  => __('Top Bar Twitter Link', 'fitness-wellness'),
	'id'    => 'top-bar-social-twitter',
	'type'  => 'text',
	'class' => 'ftbl ftbl-menu-social ftbl-social-menu ftbl-social-text ftbl-text-social slim',
),

array(
	'name'  => __('Top Bar LinkedIn Link', 'fitness-wellness'),
	'id'    => 'top-bar-social-linkedin',
	'type'  => 'text',
	'class' => 'ftbl ftbl-menu-social ftbl-social-menu ftbl-social-text ftbl-text-social slim',
),

array(
	'name'  => __('Top Bar Google+ Link', 'fitness-wellness'),
	'id'    => 'top-bar-social-gplus',
	'type'  => 'text',
	'class' => 'ftbl ftbl-menu-social ftbl-social-menu ftbl-social-text ftbl-text-social slim',
),

array(
	'name'  => __('Top Bar Flickr Link', 'fitness-wellness'),
	'id'    => 'top-bar-social-flickr',
	'type'  => 'text',
	'class' => 'ftbl ftbl-menu-social ftbl-social-menu ftbl-social-text ftbl-text-social slim',
),

array(
	'name'  => __('Top Bar Pinterest Link', 'fitness-wellness'),
	'id'    => 'top-bar-social-pinterest',
	'type'  => 'text',
	'class' => 'ftbl ftbl-menu-social ftbl-social-menu ftbl-social-text ftbl-text-social slim',
),

array(
	'name'  => __('Top Bar Dribbble Link', 'fitness-wellness'),
	'id'    => 'top-bar-social-dribbble',
	'type'  => 'text',
	'class' => 'ftbl ftbl-menu-social ftbl-social-menu ftbl-social-text ftbl-text-social slim',
),

array(
	'name'  => __('Top Bar Instagram Link', 'fitness-wellness'),
	'id'    => 'top-bar-social-instagram',
	'type'  => 'text',
	'class' => 'ftbl ftbl-menu-social ftbl-social-menu ftbl-social-text ftbl-text-social slim',
),

array(
	'name'  => __('Top Bar YouTube Link', 'fitness-wellness'),
	'id'    => 'top-bar-social-youtube',
	'type'  => 'text',
	'class' => 'ftbl ftbl-menu-social ftbl-social-menu ftbl-social-text ftbl-text-social slim',
),

array(
	'name'  => __('Top Bar Vimeo Link', 'fitness-wellness'),
	'id'    => 'top-bar-social-vimeo',
	'type'  => 'text',
	'class' => 'ftbl ftbl-menu-social ftbl-social-menu ftbl-social-text ftbl-text-social slim',
),

array(
	'name'    => __('Header Layout', 'fitness-wellness'), // dummy option, do not remove
	'id'      => 'header-layout',
	'type'    => 'select',
	'class'   => 'hidden',
	'options' => array(
		'standard'       => __('Two rows; centered logo on top', 'fitness-wellness'),
		'logo-menu'      => __('One row, left logo, menu on the right', 'fitness-wellness'),
		'logo-text-menu' => __('Two rows; left-aligned logo on top, right-aligned text and search', 'fitness-wellness'),
	),
	'field_filter' => 'fhl',
),

array(
	'name'   => __('Header Text Area', 'fitness-wellness'),
	'desc'   => __('You can place text/HTML or any shortcode in this field. The text will appear in the header on the left hand side.', 'fitness-wellness'),
	'id'     => 'phone-num-top',
	'type'   => 'textarea',
	'static' => true,
),

array(
	'name' => __('Mobile Header', 'fitness-wellness'),
	'type' => 'separator',
),

array(
	'name'   => __('Enable Below', 'fitness-wellness'),
	'id'     => 'mobile-top-bar-resolution',
	'type'   => 'range',
	'min'    => 320,
	'max'    => 1920,
	'static' => true,
),

array(
	'name'   => __('Enable Search in Logo Bar', 'fitness-wellness'),
	'id'     => 'mobile-search-in-header',
	'type'   => 'toggle',
	'static' => true,
),

array(
	'name'   => __('Mobile Top Bar', 'fitness-wellness'),
	'id'     => 'mobile-top-bar',
	'type'   => 'textarea',
	'static' => true,
),

	array(
		'type' => 'end'
	),

);