<?php
/**
 * WPV Widget Importer
 *
 * @package wpv
 * @subpackage Widget Importer
 */

if ( ! defined( 'WP_LOAD_IMPORTERS' ) )
	return;

// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( ! class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) )
		require $class_wp_importer;
}

/**
 * WordPress Importer class for managing the import process of a WXR file
 *
 * @package wpv
 * @subpackage Importer
 */
if ( class_exists( 'WP_Importer' ) ) {
class WPV_RevSlider_Import extends WP_Importer {
	private $dir;

	public function __construct() {
		$this->dir = WPV_SAMPLES_DIR . 'revslider';
	}

	/**
	 * Registered callback function for the WordPress Importer
	 *
	 * Manages the three separate stages of the WXR import process
	 */
	public function dispatch() {
		$this->header();

		check_admin_referer( 'wpv-import-revslider' );

		set_time_limit( 0 );
		$this->import( );

		$this->footer();
	}

	/**
	 * The main controller for the actual import stage.
	 */
	public function import() {
		add_filter( 'http_request_timeout', array( $this, 'bump_request_timeout' ) );

		$this->import_start();

		wp_suspend_cache_invalidation( true );

		$this->import_sliders();

		wp_suspend_cache_invalidation( false );

		$this->import_end();
	}

	private function import_sliders() {
		$dir = opendir( $this->dir );

		ob_start();

		while ( $file = readdir( $dir ) ) {
			if ( $file != '.' && $file != '..' && preg_match( '/\.zip$/', $file ) ) {
				$filepath = $this->dir . '/' . $file;

				if ( ! isset( $_FILES["import_file"] ) ) {
					$_FILES["import_file"] = array();
				}

				$_FILES["import_file"]["tmp_name"] = $filepath;

				$slider = new RevSlider();
				$response = $slider->importSliderFromPost();
			}
		}

		ob_end_clean();
	}

	private function import_start() {
		if ( ! is_dir( $this->dir ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'wordpress-importer' ) . '</strong><br />'; // xss ok
			echo __( 'The file does not exist, please try again.', 'wordpress-importer' ) . '</p>'; // xss ok
			$this->footer();
			die();
		}

		do_action( 'import_start' );
	}

	/**
	 * Performs post-import cleanup of files and the cache
	 */
	private function import_end() {
		echo '<p>' . __( 'All done.', 'wordpress-importer' ) . ' <a href="' . admin_url() . '">' . __( 'Have fun!', 'wordpress-importer' ) . '</a>' . '</p>'; // xss ok

		$redirect = admin_url( 'admin.php?page=wpv_import' );

		echo "
			<script>
				/*<![CDATA[*/
				setTimeout( function() {
					window.location = '$redirect';
				}, 3000 );
				/*]]>*/
			</script>
"; // xss ok

		do_action( 'import_end' );
	}

	// Display import page title
	private function header() {
		echo '<div class="wrap">';
		echo '<h2>' . __( 'Import Slider Revolution', 'wordpress-importer' ) . '</h2>'; // xss ok
	}

	// Close div.wrap
	private function footer() {
		echo '</div>';
	}

	/**
	 * Added to http_request_timeout filter to force timeout at 120 seconds during import
	 * @return int 120
	 */
	public function bump_request_timeout( $imp ) {
		return 120;
	}
}

} // class_exists( 'WP_Importer' )

function wpv_revslider_importer_init() {
	$GLOBALS['wpv_revslider_import'] = new WPV_RevSlider_Import();
	register_importer( 'wpv_revslider', 'Vamtam Slider Revolution Import', sprintf( __( 'Import Slider Revolution sliders from Vamtam themes, not to be used as a stand-alone product.', 'fitness-wellness' ), THEME_NAME ), array( $GLOBALS['wpv_revslider_import'], 'dispatch' ) );
}
add_action( 'admin_init', 'wpv_revslider_importer_init' );
