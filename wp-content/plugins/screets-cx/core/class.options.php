<?php
/**
 * SCREETS © 2014
 *
 * Options class
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */

// Check that class doesn't exists
if ( !class_exists( 'CX_options' ) ) {

/**
 * Options Class
 *
 * @author  Original author: Vladimir Anokhin <ano.vladimir@gmail.com>
 * @link    http://gndev.info/sunrise/
 */
class CX_options {

	/** @var string Plugin control panel URL */
	var $admin_url;

	/** @var string Plugin option name. This option contains all plugin settings */
	var $option;

	/** @var array Set of fields for options page */
	var $options;

	/** @var string Options page config */
	var $settings;

	function __construct( $file ) {
		global $CX;
		
		// Define properties
		$this->name = $CX->meta['Name'];
		$this->basename = plugin_basename( $file );
		$this->textdomain = $CX->meta['TextDomain'];
		$this->url = CX_URL;
		$this->option = CX_SLUG . '-opts';
		$this->views = CX_PATH . '/core/options/views/';
		$this->assets = 'assets';

	}

	function debug() {
		die( '<pre>' . print_r( $this, true ) . '</pre>' );
	}

	/**
	 * Conditional tag to check there is settings page
	 */
	function is_settings() {
		return is_admin() && @$_GET['page'] == CX_SLUG;
	}

	/**
	 * Register assets
	 */
	function register_assets() {
		wp_register_style( 
			'cx-opts', 
			$this->assets( 'css', 'cx.options.css' ), 
			false, 
			CX_VERSION, 
			'all' 
		);

		wp_register_style( 
			'cx-basic', 
			$this->assets( 'css', 'cx.basic.css' ), 
			false, 
			CX_VERSION, 
			'all' 
		);

		wp_register_script( 
			'jquery-autosize', 
			$this->assets( 'js', 'lib/jquery.autosize.min.js' ), 
			array( 'jquery' ), 
			'1.7.6',
			false 
		);

		wp_register_script( 
			'jquery-tipsy', 
			$this->assets( 'js', 'lib/jquery.tipsy.min.js' ), 
			array( 'jquery' ), 
			'1.0', 
			false 
		);

		wp_register_script( 
			'jquery-form', 
			$this->assets( 'js', 'lib/jquery.form.min.js' ), 
			array( 'jquery' ), 
			CX_VERSION, 
			false 
		);

		wp_register_script( 
			'cx-opts-js', 
			$this->assets( 'js', 'cx.options.js' ), 
			array( 'jquery-form' ), 
			CX_VERSION,
			false 
		);

		$js_vars = array(
			'wc_installed' => defined( 'CX_WC_INSTALLED' ) ? true : false
		);
		wp_localize_script( 'cx-opts-js', 'cx_opts', $js_vars );

	}

	/**
	 * Enqueue assets
	 */
	function enqueue_assets() {
		if ( !$this->is_settings() ) return;

		foreach ( array( 'thickbox', 'farbtastic', 'cx-opts', 'cx-basic' ) as $style )
			wp_enqueue_style( $style );
		
		foreach ( array( 'jquery', 'media-upload', 'thickbox', 'farbtastic', 'jquery-form', 'jquery-autosize', 'jquery-tipsy',
		                 'cx-opts-js' ) as $script )
			wp_enqueue_script( $script );
		
	}

	/**
	 * Helper function to get assets url by type
	 */
	function assets( $type = 'css', $file = 'cx.options.css' ) {
		return implode( '/', array_filter( array( trim( $this->url, '/' ), trim( $this->assets, '/' ),
		                                          trim( $type, '/' ), trim( $file, '/' ) ) ) );
	}

	/**
	 * Set plugin settings to default
	 */
	function default_settings( $manual = false ) {

		// Settings page is created
		if ( $manual || !get_option( $this->option ) ) {

			// Create array with default options
			$defaults = array();

			// Loop through available options
			foreach ( (array) $this->options as $value ) {
				if( isset( $value['std'] ) )
					$defaults[ $value['id'] ] = $value['std'];
			}

			// Insert default options
			update_option( $this->option, $defaults );

			return $defaults;
		}
	}


	/**
	 * Get single option value
	 *
	 * @param mixed $option Option ID to return. If false, all options will be returned
	 *
	 * @return mixed $option Returns option by specified key
	 */
	function get_option( $option = false ) {

		// Get options from database
		$options = get_option( $this->option );

		// Check option is specified
		$value = ( !empty( $options[$option] ) ) ? $options[$option] : false;

		// Return result
		return ( is_array( $value ) ) ? array_filter( $value, 'esc_attr' ) : esc_attr( stripslashes( $value ) );
	}

	/**
	 * Get all options
	 *
	 * @return array Returns options
	 */
	function get_options() {

		$opts = array();

		// Get all options
		$all_opts = get_option( $this->option );

		// First load default settings
		if( empty( $all_opts ) ) {
			$all_opts = $this->default_settings();
		}

		foreach( $all_opts as $k => $v ) {
			if( !empty( $k ) )
				$opts[$k] = ( is_array( $v ) ) ? array_filter( $v, 'esc_attr' ) : esc_attr( stripslashes( $v ) );
		}

		return $opts;
		
	}

	/**
	 * Update single option value
	 *
	 * @param mixed $key   Option ID to update
	 * @param mixed $value New value
	 *
	 * @return mixed $option Returns option by specified key
	 */
	function update_option( $key = false, $value = false ) {

		// Prepare variables
		$settings = get_option( $this->option );
		$new_settings = array();

		// Prepare data
		foreach ( $settings as $id => $val ) 
			$new_settings[$id] = ( $id == $key ) ? $value : $val;

		// Update option and return operation result
		return update_option( $this->option, $new_settings );
	}

	/**
	 * Action to save/reset options
	 */
	function manage_options() {

		// Check this is settings page
		if ( !$this->is_settings() or empty( $_REQUEST['action'] ) ) return;

		// ACTION: RESET
		if ( $_GET['action'] == 'reset' ) {

			// Remove lc
			delete_option( 'cx_c9f1a6384b1c466d4612f513bd8e13ea' );
			delete_option( 'cx_error' );

			// Prepare variables
			$new_options = array();

			// Prepare data
			foreach ( $this->options as $value )
				@$new_options[$value['id']] = $value['std'];

			// Save new options
			if ( update_option( $this->option, $new_options ) ) {
				
				// Redirect
				wp_redirect( $this->admin_url . '&message=1' );
				exit;

			}

			// Option doesn't updated
			else {
				// Redirect
				wp_redirect( $this->admin_url . '&message=2' );
				exit;
			}
		}

		// ACTION: SAVE
		elseif ( $_POST['action'] == 'save' ) {

			// Prepare vars
			$new_options = array();

			// Prepare data
			foreach ( $this->options as $value ) {

				// Check api
				if( $value['id'] == 'license_key' ) {
					
					cx_api( $_POST[ $value['id'] ], true );

				}

				// Update operator default role
				if( $value['id'] == 'op_role' ) {
					cx_update_op_role( $_POST[$value['id']] );
				}

				// Update operator additional role
				if( $value['id'] == 'op_add_role' ) {
					cx_update_op_role( $_POST[$value['id']], true );
				}
				
				if( ( is_array( $_POST[$value['id']] ) ) ) {
					$new_options[$value['id']] = $_POST[$value['id']];
				
				} else {

					$str = $_POST[$value['id']];

					// Sanitize option
					switch( $value['type'] ) {
						case 'textarea':
							if( !empty( $value['html'] ) )
								$str = str_replace("\n", '<br/>', $str );
						break;
					}

					$new_options[$value['id']] = $str;

				}
			}

			// Save new options
			if ( update_option( $this->option, $new_options ) ) {

				// Redirect
				wp_redirect( $this->admin_url . '&message=3' );
				exit;
			}

			// Options not saved
			else {
				// Redirect
				wp_redirect( $this->admin_url . '&message=4' );
				exit;
			}
		}
	}

	/**
	 * Register options page
	 *
	 * @param array $args    Options page config
	 * @param array $options Set of fields for options page
	 */
	function add_options_page( $args, $options = array() ) {
		
		$this->options = $options;
		
		// Prepare defaults
		$defaults = array( 
			'parent' 		=> 'options-general.php', 
			'menu_title' 	=> $this->name,
			'page_title' 	=> $this->name, 
			'capability' 	=> 'manage_options', 
			'link' 			=> true
		);

		// Parse args
		$this->settings = wp_parse_args( $args, $defaults );
		
		// Define admin url
		$this->admin_url = admin_url( 'admin.php?page=' . CX_SLUG );
		
		// Register and enqueue assets
		add_action( 'admin_head', array( &$this, 'register_assets' ) );
		add_action( 'admin_footer', array( &$this, 'enqueue_assets' ) );
		
		// Insert default settings if it's doesn't exists
		add_action( 'admin_init', array( &$this, 'default_settings' ) );
		
		// Manage options
		add_action( 'admin_menu', array( &$this, 'manage_options' ) );
		
		// Add settings page
		add_action( 'admin_menu', array( &$this, 'options_page' ) );

		// Add settings link to plugins dashboard
		if ( $this->settings['link'] ) add_filter( 'plugin_action_links_' . $this->basename, array( &$this,
		                                                                                            'add_settings_link' ) );
	}

	/**
	 * Register settings page
	 */
	function options_page() {
		add_submenu_page( 
			$this->settings['parent'], 
			__( $this->settings['page_title'], $this->textdomain ), 
			__( $this->settings['menu_title'], $this->textdomain ), 
			$this->settings['capability'], 
			CX_SLUG, 
			array( &$this, 'render_options_page' ) 
		);
	}

	/**
	 * Display settings page
	 */
	function render_options_page() {
		$backend_file = $this->views . 'settings.php';
		
		if ( file_exists( $backend_file ) ) 
			require_once $backend_file;
	}

	/**
	 * Add settings link to plugins dashboard
	 */
	function add_settings_link( $links ) {
		$links[] = '<a href="' . $this->admin_url . '">' . __( 'Settings', $this->textdomain ) . '</a>';
		return $links;
	}

	/**
	 * Display settings panes
	 */
	function render_panes() {
		// Get current settings
		$settings = get_option( $this->option );
		// Options loop
		foreach ( $this->options as $option ) {
			// Get option file path
			$option_file = $this->views . $option['type'] . '.php';
			// Check that file exists and include it
			if ( file_exists( $option_file ) ) include( $option_file );
			else
				trigger_error( 'Option file <strong>' . $option_file . '</strong> not found!', E_USER_NOTICE );
		}
	}

	/**
	 * Display settings tabs
	 */
	function render_tabs() {
		global $CX;

		foreach ( $this->options as $option ) {
			if ( $option['type'] == 'opentab' ) {
				$active = ( isset( $active ) ) ? ' cx-opts-tab-inactive'
					: ' nav-tab-active cx-opts-tab-active';
				$error = ( !empty( $CX->admin_notices['tabs'][ $option['id'] ] ) ) ? '<span class="cx-error-tab">' . $CX->admin_notices['tabs'][ $option['id'] ] . '</span>' : '';
				echo '<span class="nav-tab' . $active . '">' . $option['name'] . $error . '</span>';
			}
		}
	}

	/**
	 * Show notifications
	 */
	function notifications( $notifications ) {
		$file = $this->views . 'notifications.php';
		if ( file_exists( $file ) ) include $file;
	}

}

} // class_exists