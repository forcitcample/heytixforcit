<?php

class WPV_Twitter {
	public function __construct() {
		add_shortcode( 'wpv_twitter', array( __CLASS__, 'shortcode' ) );
	}

	public static function shortcode( $atts, $content = null, $code ) {
		extract( shortcode_atts( array(
			'type' => 'user',
			'param' => '',
			'limit' => 5,
		 ), $atts ) );

		if ( ! class_exists( 'Vamtam_Twitter' ) ) {
			return '';
		}

		if ( $type === 'user' ) {
			$results = Vamtam_Twitter::user_timeline( $param, $limit );
		} else if ( $type === 'search' ) {
			$results = Vamtam_Twitter::search( $param, $limit );
		}

		ob_start();

		include locate_template( "templates/shortcodes/twitter.php" );

		return ob_get_clean();
	}

	/**
	 * @see http://stackoverflow.com/a/11929224/635882
	 */

	public static function format_tweet( &$tweet ) {
		if ( ! empty( $tweet->entities ) ) {

			$replace_index = array();
			$append        = array();
			$text          = $tweet->text;

			foreach ( $tweet->entities as $area => $items ) {
				$prefix  = false;
				$display = false;

				switch ( $area ) {
					case 'hashtags':
						$find   = 'text';
						$prefix = '#';
						$url    = 'https://twitter.com/search/?src=hash&q=%23';
						break;
					case 'user_mentions':
						$find   = 'screen_name';
						$prefix = '@';
						$url    = 'https://twitter.com/';
						break;
					case 'media':
						$display = 'media_url_https';
						$href    = 'media_url_https';
						$size    = 'small';
						break;
					case 'urls':
						$find    = 'url';
						$display = 'display_url';
						$url     = "expanded_url";
						break;
					default: break;
				}

				foreach ( $items as $item ) {
					if ( $area == 'media' ) {
						// We can display images at the end of the tweet but sizing needs to added all the way to the top.
						// $append[$item->$display] = "<img src=\"{$item->$href}:$size\" />";
					} else {
						$msg     = $display ? $prefix.$item->$display : $prefix.$item->$find;
						$replace = $prefix.$item->$find;
						$href    = isset( $item->$url ) ? $item->$url : $url;

						if ( ! ( strpos( $href, 'http' ) === 0 ) ) {
							$href = "http://".$href;
						}

						if ( $prefix ) {
							$href .= $item->$find;
						}

						$with = "<a href=\"$href\">$msg</a>";
						$replace_index[$replace] = $with;
					}
				}
			}

			foreach ( $replace_index as $replace => $with ) {
				$tweet->text = str_replace( $replace, $with, $tweet->text );
			}

			foreach ( $append as $add ) {
				$tweet->text .= $add;
			}
		}
	}
}

new WPV_Twitter;
