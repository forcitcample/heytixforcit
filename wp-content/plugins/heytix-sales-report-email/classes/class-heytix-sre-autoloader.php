<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Heytix_SRE_Autoloader {

	private $path;
	private $prefix = 'heytix-sre-';

	/**
	 * The Constructor, sets the path of the class directory.
	 *
	 * @param $path
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function __construct( $path ) {
		$this->path = $path;
	}


	/**
	 * Autoloader load method. Load the class.
	 *
	 * @param $class_name
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function load( $class_name ) {

		// Only autoload Heytix Sales Report Email classes
		if ( 0 === strpos( $class_name, 'Heytix_SRE' ) ) {

			// String to lower
			$class_name = strtolower( $class_name );

			// Format file name
			$file_name = 'class-' . $this->prefix . str_ireplace( '_', '-', str_ireplace( 'Heytix_SRE_', '', $class_name ) ) . '.php';

			// Setup the file path
			$file_path = $this->path;

			// Check if class is a report row
			if ( strpos( $class_name, 'heytix_sre_row' ) === 0 ) {
				$file_path .= 'rows/';
			}

			// Append file name to clas path
			$file_path .= $file_name;

			// Check & load file
			if ( file_exists( $file_path ) ) {
				require_once( $file_path );
			}

		}

	}

}