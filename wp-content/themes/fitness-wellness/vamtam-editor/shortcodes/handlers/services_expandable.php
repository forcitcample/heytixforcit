<?php

/**
 * Expandable services shortcode handler
 *
 * @package wpv
 */

class WPV_Expandable {
	/**
	 * Register the shortcodes
	 */
	public function __construct() {
		add_shortcode('services_expandable', array(__CLASS__, 'shortcode'));
	}

	/**
 	 * Expandable services shortcode callback
	 *
	 * @param  array  $atts    shortcode attributes
	 * @param  string $content shortcode content
	 * @param  string $code    shortcode name
	 * @return string          output html
	 */
	public static function shortcode($atts, $content = null, $code) {
		extract( shortcode_atts( array(
			'image'                 => '',
			'icon'                  => 'apple',
			'icon_color'            => 'accent6',
			'icon_size'             => 62,
			'class'                 => '',
			'background_attachment' => 'scroll',
			'background_color'      => 'accent1',
			'background_image'      => '',
			'background_position'   => '',
			'background_repeat'     => '',
			'background_size'       => '',
			'hover_background'      => 'accent2',
			'title'                 => '',
		), $atts, 'services_expandable' ) );

		if(empty($content)) {
			$content = '[split]';
		}

		$before = '';
		$content = explode('[split]', $content, 2);
		if(count($content) > 1)
			$before = array_shift($content);
		$content = implode(' ', $content);

		ob_start();

		include(locate_template('templates/shortcodes/services_expandable.php'));

		return ob_get_clean();
	}
}

new WPV_Expandable;