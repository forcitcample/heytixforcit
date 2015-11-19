<?php
/**
 * Plugin Name: Screets Chat X
 * Plugin URI: http://www.screets.com
 * Description: Realtime chat with your customers for sales and support easily, and beautifully.
 * Version: 1.4.2
 * Author: Screets Team
 * Author URI: http://www.screets.com
 * Requires at least: 3.8
 * Tested up to: 4.0
 *
 *
 * Text Domain: cx
 * Domain Path: /languages/
 *
 * @package CX
 * @category Core
 * @author Screets
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $wpdb;

// Define Constants
define( 'CX_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'CX_URL', plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) );
define( 'CX_NOW', current_time( 'timestamp' ) );
define( 'CX_SLUG', 'screetschatx' );
define( 'CX_PX', $wpdb->prefix . 'cx_' ); // DB Table prefix

// Add "CX_PHP_SESSIONS" constant in your wp-config.php file to change this value
if( !defined( 'CX_PHP_SESSIONS' ) )
	define( 'CX_PHP_SESSIONS', true );

if ( ! class_exists( 'CX' ) ) {

class CX {

	/**
	 * @var object
	 */
	var $option;

	/**
	 * @var array
	 */
	var $opts = array();

	/**
	 * @var object
	 */
	var $session;
	
	/**
	 * @var array
	 */
	var $user = array();
	
	/**
	 * @var array
	 */
	var $meta = array();
	
	/**
	 * @var string
	 */
	var $min_jquery = '1.8';
	
	/**
	 * @var string
	 */
	var $app_token;

	
	/**
	 * @var array
	 */
	var $admin_notices = array();

	/**
	 * Chat Constructor  
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		// Check that function get_plugin_data exists
		if ( !function_exists( 'get_plugin_data' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		// Get plugin meta
		$this->meta = get_plugin_data( __FILE__, false );

		// Define app meta constants
		define( 'CX_NAME', $this->meta['Name'] );
		define( 'CX_VERSION', $this->meta['Version'] );

		// Install plugin
		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		// Updates
		add_action( 'admin_init', array( &$this, 'admin_init' ), 5 );
		
		// Include required files
		$this->includes();

		// Setup session
		$this->session = new CX_Session();

		// Actions
		add_action( 'init', array( &$this, 'init' ), 0 );
		add_action( 'plugins_loaded', array( &$this, 'load_plugin_textdomain' ) );
		
		// Loaded action
		do_action( 'cx_loaded' );
	
	}
	
	
	/**
	 * Init Screets Chat X when WordPress Initialises
	 *
	 * @access public
	 * @return void
	 */
	function init() {

		// Before init action
		do_action( 'before_cx_init' );

		// Check if Woocommerce installed
		if( function_exists( 'is_woocommerce' ) ) {
			define( 'CX_WC_INSTALLED', true );
		}

		// Some user info
		$this->current_page = cx_current_page_url();
		$this->ip = cx_ip_address();

		// User is operator?
		if( current_user_can( 'answer_visitors' ) ) {
			define( 'CX_OP', true );
			
		// User is visitor?
		} else
			define( 'CX_VISITOR', true );


		if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
			
			add_action( 'wp_enqueue_scripts', array(&$this, 'frontend_scripts') );
			add_action( 'wp_print_scripts', array( $this, 'check_jquery' ), 25 );

		}

		// Create options object
		$this->option = new CX_options( __FILE__ );

		// Get options set
		require CX_PATH . '/core/options/options.php';

		// Create options page
		$this->option->add_options_page(
			array( 
				'menu_title' => __( 'Settings', 'cx' ),
				'parent' => 'chat_x'
			), 
			$cx_opts_set
		);

		// Get all options
		$this->opts = $this->option->get_options();

		// Ajax requests
		$ajax_prefix = ( !empty( $this->opts['faster_ajax'] ) ) ? 'cx_' : 'wp_';
		add_action( $ajax_prefix . 'ajax_cx_ajax_callback', 'cx_ajax_callback' );
		add_action( $ajax_prefix . 'ajax_nopriv_cx_ajax_callback', 'cx_ajax_callback' );

		// WPML support (updates opts)
		if( function_exists( 'icl_register_string' ) )
			$this->WPML( $cx_opts_set );

		// Save user info
		if( is_user_logged_in() ) {
			global $current_user;

			// Get currently logged user info
			get_currentuserinfo();

			$this->user = $current_user;

		// Visitor info
		} else {

			$visitor_id = $this->session->get( 'visitor_id' );

			// Create new unique ID
			if( empty( $visitor_id ) ) {
				$visitor_id = uniqid( rand(), false );

				// Save id into the session
				$this->session->set( 'visitor_id', $visitor_id );
				
			}

			$this->user = (object) array( 
				'ID' => $visitor_id,
				'display_name' => null,
				'user_email' => null
			);

		}

		// Register Visitors post type when default AJAX base is enabled
		/*if( $this->opts['app'] == 'none' ) {

			$labels = array(
				'name'                => _x( 'Visitors', 'Post Type General Name', 'cx' ),
				'singular_name'       => _x( 'Visitor', 'Post Type Singular Name', 'cx' ),
				'menu_name'           => __( 'Visitor', 'cx' ),
				'parent_item_colon'   => __( 'Parent Visitor:', 'cx' ),
				'all_items'           => __( 'All Visitors', 'cx' ),
				'view_item'           => __( 'View Visitor', 'cx' ),
				'add_new_item'        => __( 'Add New Visitor', 'cx' ),
				'add_new'             => __( 'New Visitor', 'cx' ),
				'edit_item'           => __( 'Edit Visitor', 'cx' ),
				'update_item'         => __( 'Update Visitor', 'cx' ),
				'search_items'        => __( 'Search visitors', 'cx' ),
				'not_found'           => __( 'No visitors found', 'cx' ),
				'not_found_in_trash'  => __( 'No visitors found in Trash', 'cx' ),
			);
			$args = array(
				'labels'              => $labels,
				'supports'            => array( 'title', 'custom-fields' ),
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'menu_position'       => 60,
				'menu_icon'           => '',
				'can_export'          => true,
				'has_archive'         => true,
				'exclude_from_search' => true,
				'publicly_queryable'  => true,
				'capability_type'     => 'page',
				'capabilities' 		  => array(
					// 'create_posts' => false
				)
			);
			register_post_type( 'cx_visitor', $args );

		}*/

		// Register Offline Messages post type
		$labels = array(
			'name'                => _x( 'Offline Messages', 'Post Type General Name', 'cx' ),
			'singular_name'       => _x( 'Offline Message', 'Post Type Singular Name', 'cx' ),
			'menu_name'           => __( 'Offline Message', 'cx' ),
			'parent_item_colon'   => __( 'Parent Offline Message:', 'cx' ),
			'all_items'           => __( 'All Offline Messages', 'cx' ),
			'view_item'           => __( 'View Offline Message', 'cx' ),
			'add_new_item'        => __( 'Add New Offline Message', 'cx' ),
			'add_new'             => __( 'New Offline Message', 'cx' ),
			'edit_item'           => __( 'Edit Offline Message', 'cx' ),
			'update_item'         => __( 'Update Offline Message', 'cx' ),
			'search_items'        => __( 'Search offline message', 'cx' ),
			'not_found'           => __( 'No offline message found', 'cx' ),
			'not_found_in_trash'  => __( 'No offline message found in Trash', 'cx' ),
		);

		$args = array(
			'labels'              => $labels,
			'supports'            => array( 'title' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'menu_position'       => 60,
			'menu_icon'           => '',
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'rewrite' 			  => false,
			'capability_type'     => 'page',
			'capabilities' 		  => array(
				// 'create_posts' => false
			)
		);
		register_post_type( 'cx_offline_msg', $args );

		// Predefined Messages post type
		/*$labels = array(
			'name'                => _x( 'Predifined Messages', 'Post Type General Name', 'cx' ),
			'singular_name'       => _x( 'Predefined Message', 'Post Type Singular Name', 'cx' ),
			'menu_name'           => __( 'Predefined Message', 'cx' ),
			'parent_item_colon'   => __( 'Parent Predefined Message:', 'cx' ),
			'all_items'           => __( 'All Predefined Messages', 'cx' ),
			'view_item'           => __( 'View Predefined Message', 'cx' ),
			'add_new_item'        => __( 'Add New Predefined Message', 'cx' ),
			'add_new'             => __( 'New Predefined Message', 'cx' ),
			'edit_item'           => __( 'Edit Predefined Message', 'cx' ),
			'update_item'         => __( 'Update Predefined Message', 'cx' ),
			'search_items'        => __( 'Search predefined message', 'cx' ),
			'not_found'           => __( 'No predefined message found', 'cx' ),
			'not_found_in_trash'  => __( 'No predefined message found in Trash', 'cx' ),
		);
		$args = array(
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'menu_position'       => 60,
			'menu_icon'           => '',
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'capability_type'     => 'page',
			'capabilities' 		  => array(
				// 'create_posts' => false
			)
		);
		register_post_type( 'cx_predefined_msg', $args );*/

		// Add operator name to user fields
		if( defined( 'CX_OP' ) ) {
			add_action( 'show_user_profile', array(&$this, 'xtra_profile_fields'), 10 );
			add_action( 'edit_user_profile', array(&$this, 'xtra_profile_fields'), 10 );
			add_action( 'personal_options_update', array(&$this, 'save_xtra_profile_fields') );
			add_action( 'edit_user_profile_update', array(&$this, 'save_xtra_profile_fields') );
		}
		
		// Add custom css
		if( !empty( $this->opts['custom_css'] ) || @$this->opts['avatar_size'] != 30 )
			add_action( 'wp_footer', array( &$this, 'custom_css' ) );
		
		// Render chat box
		add_action( 'wp_footer', array( &$this, 'display_chatbox') );
		
		// Shortcodes
		add_shortcode( 'cx_btn', 'cx_shortcode_open_chatbox' );
		
		// Check updates
		$this->update();

		// Initialization action
		do_action( 'cx_init' );

	}

	/**
	 * Initialization Screets Chat X for back-end
	 *
	 * @access public
	 * @return void
	 */
	function admin_init() {
		
		// Load back-end styles and scripts
		add_action( 'admin_enqueue_scripts', array( &$this, 'backend_scripts' ) );

		// Get current page
		$current_page = ( !empty( $_GET['page'] ) ) ? $_GET['page'] : null;

		// Check CX setup
		if( $current_page  == CX_SLUG or $current_page  == 'chat_x' ) {

			require CX_PATH . '/core/fn.setup.php';

			// Check CX configuration
			cx_check_setup();

			
		}

		// Settings page
		if( $current_page == CX_SLUG ) {

			$l = ( !empty( $this->opts['license_key'] ) ) ? $this->opts['license_key'] : null;

			// Check API
			cx_api( $l );

		}
		
	}
	
	/**
	 * Include required core files
	 *
	 * @access public
	 * @return void
	 */
	function includes() {
		
		// Back-end includes
		if(  is_admin())  $this->admin_includes();
		
		// Include core files
		require CX_PATH . '/core/lib/firebaseToken.php';
		require CX_PATH . '/core/class.options.php';
		require CX_PATH . '/core/class.user.php';
		require CX_PATH . '/core/class.session.php';
		require CX_PATH . '/core/fn.firebase.php';
		require CX_PATH . '/core/fn.visitor.php';
		require CX_PATH . '/core/fn.common.php';
		require CX_PATH . '/core/fn.ajax.php';
		require CX_PATH . '/core/fn.security.php';
		require CX_PATH . '/core/fn.formatting.php';
		require CX_PATH . '/core/fn.offline.php';
		require CX_PATH . '/core/fn.shortcodes.php';

	}
	
	/**
	 * Include required admin files
	 *
	 * @access public
	 * @return void
	 */
	function admin_includes() {
		
		// Include admin files
		require CX_PATH . '/core/fn.admin.php';
		require CX_PATH . '/core/class.logs.php';
		
	}
	
	/**
	 * Get Ajax URL
	 *
	 * @access public
	 * @return string
	 */
	function ajax_url() {
		
		if( !empty( $this->opts['faster_ajax'] ) )
			return str_replace( array('https:', 'http:'), '', CX_URL . '/core/cx.ajax.php' );
		else
			return str_replace( array('https:', 'http:'), '', admin_url( 'admin-ajax.php' ) );

	}
	
	/**
	 * Localization
	 *
	 * @access public
	 * @return void
	 */
	function load_plugin_textdomain() {
		
		load_plugin_textdomain( 'cx', false, CX_PATH . '/languages/' );
		
	}
	
	/**
	 * Add xtra profile fields
	 *
	 * @access public
	 * @return void
	 */
	function xtra_profile_fields( $user ) { ?>
		
		<h3><?php _e( 'Chat Options', 'cx' ); ?></h3>

		<table class="form-table">

			<tr>
				<th><?php _e( 'Operator Name', 'cx' ); ?></th>
				<td>
					<input type="text" name="cx_op_name" id="f_chat_op_name" value="<?php echo esc_attr( get_the_author_meta( 'cx_op_name', $user->ID ) ); ?>" class="regular-text" />
					<br>
					<span class="description"><?php _e( 'Refresh chat console page when you update operator name', 'cx' ); ?></span>
				</td>
			</tr>

			
		</table>

		
	<?php }
	
	
	/**
	 * Save xtra profile fields
	 *
	 * @access public
	 * @return void
	 */
	function save_xtra_profile_fields( $user_id ) {
		
		if ( !current_user_can( 'edit_user', $user_id ) )
			return false;
			
		// Op name isn't defined yet, create new one for user
		if( empty( $_POST['cx_op_name'] ) ) {
			
			$op_name = $this->user->display_name;
		
		
		// OP name
		} else
			$op_name = $_POST['cx_op_name'];
		
		
		// Update user meta now
		update_user_meta( $user_id, 'cx_op_name', $op_name );
		
	}
	
	
	/**
	 * Activate plugin
	 *
	 * @access public
	 * @return void
	 */
	public function activate() {
		global $wpdb;

		require CX_PATH . '/core/fn.upgrade.php';

		// Get license key
		$license = !empty( $this->opts['license_key'] ) ? $this->opts['license_key'] : null;
		
		// Upgrade plugin
		cx_upgrade( $license );

		// Update operator role
		cx_update_op_role( 'editor' );

		/**
		 * Administration role
		 */
		$admin_role = get_role( 'administrator' );
		$admin_role->add_cap( 'answer_visitors' ); 
		
		/**
		 * Chat Operator role
		 */
		$op_role = get_role( 'cx_op' );
		$op_role->add_cap( 'answer_visitors' );

	}

	/**
	 * The plugin requires jQuery 1.8
	 * If, by the time wp_print_scrips is called, jQuery is outdated (i.e not
	 * using the version in core) we need to deregister it and register the
	 * core version of the file.
	 *
	 * @access public
	 * @return void
	 */
	public function check_jquery() {
		global $wp_scripts;

		// Enforce minimum version of jQuery
		if ( ! empty( $wp_scripts->registered['jquery']->ver ) && ! empty( $wp_scripts->registered['jquery']->src ) && $wp_scripts->registered['jquery']->ver < '1.7' ) {
			wp_deregister_script( 'jquery' );
			wp_register_script( 'jquery', '/wp-includes/js/jquery/jquery.js', array(), $this->min_jquery );
			wp_enqueue_script( 'jquery' );
		}
	}

	/**
	 * Display chatbox
	 *
	 * @access public
	 * @return void
	 */
	function display_chatbox() {

		// Get plugin options
		$plugin_opts = cx_get_jquery_plug_opts();
		?>

		<div id="CX_chatbox"></div>

		<script type="text/javascript">
		(function ($) {

			$(document).ready(function () {
				
				var el = $('#CX_chatbox');

				/**
				 * Initialize Screets Chat plugin
				 */
				el.cx({
					<?php cx_print_custom_opts( $plugin_opts ); ?>
				});

			});
			
		} (window.jQuery || window.Zepto));

		</script>

	<?php
	}

	/**
	 * Add WPML support to the plguin
	 *
	 * @return void
	 */
	function WPML( $opts_set ) {

		foreach( $opts_set as $opt ) {
			

			if( !empty( $opt['translate'] ) ) {

				// Register strings to WPML
				icl_register_string( 'Screets CX', $opt['name'], $this->opts[ $opt['id'] ] );
				
				// Update translations in options
				$this->opts[ $opt['id'] ] = icl_t( 'Screets CX', $opt['name'], $this->opts[ $opt['id'] ] );

			}
		}
	}


	/**
	 * Add Custom CSS
	 *
	 * @access public
	 * @return void
	 */

	function custom_css() { ?>
	    
	    <style type="text/css">
	    	/* <?php echo CX_NAME; ?> custom CSS */
	    	<?php 
	    		echo $this->opts['custom_css']; 

	    		// Avatar size
	    		$avatar_size = @$this->opts['avatar_size'] || 30;
	    		$avatar_radius = @$this->opts['avatar_radius'] || 30;

	    		// Update Avatar Size?
	    		if( $avatar_size != 30 ) {
	    			echo '.cx-cnv .cx-avatar, .cx-cnv .cx-avatar.cx-img img { width: ' . $avatar_size . 'px; }'
	    				.'.cx-cnv-msg { margin-left: ' . ( $avatar_size + 10 ) . 'px; }';
	    		}

				// Update avatar radius
				if( $avatar_radius != 30 ) {
					echo '.cx-cnv .cx-avatar.cx-img img { border-radius: ' . $avatar_radius . ' px; }';
				}
	    	?>
    	</style>

	<?php
	}


	/**
	 * Front-end styles and scripts
	 *
	 * @access public
	 * @return void
	 */
	function frontend_scripts() {
		
		$suffix_css = ( !empty( $this->opts['compress-group']['compress_css'] ) ) ? '.min' : '';
		
		// Base template stylesheet
		if( empty( $this->opts['compress-group']['disable_css'] ) ) {
			wp_register_style(
				'cx-basic', 
				plugins_url( 'assets/css', __FILE__ )  . '/cx.basic' . $suffix_css . '.css'
			);
			wp_enqueue_style( 'cx-basic' );
		}

		// Use jQuery
		wp_enqueue_script( 'jquery' );

		// Application JS
		$this->load_app_js();

		// Load common admin scripts if user is admin / operator
		if( defined( 'CX_OP' ) )
			$this->common_admin_scripts();
		
		// § Plug-in
		wp_register_script(
			'jquery-autosize', 
			CX_URL . '/assets/js/lib/jquery.autosize.min.js', 
			array( 'jquery' ),
			'1.17.1'
		);
		wp_enqueue_script( 'jquery-autosize' );

	}

	
	/**
	 * Back-end styles and scripts
	 *
	 * @access public
	 * @return void
	 */
	function backend_scripts() {

		$page = '';
		$suffix_css = ( !empty( $this->opts['compress-group']['compress_css'] ) ) ? '.min' : '';

		// Get currently logged user info
		get_currentuserinfo();

		// Application JS
		$this->load_app_js();

		// Get current page
		if( !empty( $_REQUEST['page'] ) )
			$page = $_REQUEST['page'];

		// Load in chat console
		if( $page == 'chat_x'  ) {

			// Console stylesheet
			wp_register_style( 
				'cx-console', 
				CX_URL . '/assets/css/cx.console.css'
			);
			wp_enqueue_style( 'cx-console' );

			// AutoSize Plug-in
			wp_register_script( 
				'jquery-autosize', 
				CX_URL . '/assets/js/lib/jquery.autosize.min.js', 
				array( 'jquery' ),
				'1.17.1'
			);
			wp_enqueue_script( 'jquery-autosize' );

			// Tipsy Plug-in
			wp_register_script( 
				'jquery-tipsy', 
				CX_URL . '/assets/js/lib/jquery.tipsy.min.js', 
				array( 'jquery' ),
				'1.0'
			);
			wp_enqueue_script( 'jquery-tipsy' );

			// Console JS
			wp_register_script( 
				'cx-console', 
				CX_URL . '/assets/js/cx.console.js', 
				array( 'jquery' ),
				CX_VERSION
			);
			wp_enqueue_script( 'cx-console' );

		}

		// Load common admin scripts if user is admin / operator
		if( defined( 'CX_OP' ) )
			$this->common_admin_scripts();
	
	}

	/**
	 * Common styles and scripts for admin / operators
	 *
	 * @access public
	 * @return void
	 */
	function common_admin_scripts() {}

	/**
	 * Update CX and check for new updates from Screets Server
	 *
	 * @access public
	 * @return void
	 */
	function update() {
		
		require CX_PATH . '/core/update/checker.php';
		
		// Check new update from Screets Server
		$checker = PucFactory::buildUpdateChecker(
			'http://screets.com/api/wp-updater/?action=get_metadata&slug=screets-cx',
			__FILE__,
			'screets-cx'
		);

	}


	/**
	 * Authentication user
	 *
	 * @access public
	 * @return string Auth token
	 */
	public function auth() {
		global $wpdb;

		if( empty( $this->opts[ 'app_token' ] ) )
			return;

		// FireBase authentication
		$token_gen = new Services_FirebaseTokenGenerator( $this->opts[ 'app_token' ] );

		// Administrator user?
		$is_admin = ( current_user_can( 'manage_options' ) ) ? true : false;

		// Is Operator?
		$is_op = ( defined( 'CX_OP' ) ) ? true : false;

		$prefix = ( is_user_logged_in() && !defined( 'CX_OP' ) ) ? 'usr-' : '';

		// An object or array of data you wish
        // to associate with the token. It will
  		// be available as the variable "auth" in
  		// the Firebase rules engine.
		$data = array(
			'user_id' 		=> $prefix . $this->user->ID,
			'is_operator'	=> $is_op
		);

		$debug = ( !empty( $this->opts['debug'] ) ) ? true : false;

		// Options
		$opts = array(
			// Set to true if you want this 
			// token to bypass all security rules.
			'admin'	=> $is_admin,

			// Set to true if you want to enable debug 
			// output from your security rules.
			'debug'	=> $debug
                                    	

			//'expires'	=> 0,			// Set to a number (seconds
                                    	// since epoch) or a DateTime object that
                                    	// specifies the time at which the token
                                    	// should expire.

			//'notBefore'	=> null 	// Set to a number (seconds
                                    	// since epoch) or a DateTime object that
                                    	// specifies the time before which the
                                    	// should be rejected by the server.
		);

		// Create secure auth token
		return $token_gen->createToken( $data, $opts );

	}
	

	/**
	 * Load application JS
	 *
	 * @access public
	 * @return void
	 */
	function load_app_js() {

		$suffix_js = ( !empty( $this->opts['compress-group']['compress_js'] ) ) ? '.min' : '';

		// Firebase CDN
		wp_register_script( 
			'firebase', 
			CX_URL . '/assets/js/firebase.js',
			null,
			CX_VERSION
		);
		wp_enqueue_script( 'firebase' );

		/**
		 * Application JS
		 */
		wp_register_script(
			'cx-app',
			CX_URL . '/assets/js/cx.app' . $suffix_js . '.js', 
			array( 'jquery', 'firebase' ),
			CX_VERSION
		);
		wp_enqueue_script( 'cx-app' );

		$company_avatar = !empty( $this->opts['default_avatar'] ) ? $this->opts['default_avatar'] : '';

		// Custom Data
		$js_vars = array(
			'ajax_url'   		=> $this->ajax_url(),
			'plugin_url'   		=> CX_URL,
			'is_front_end' 		=> ( !is_admin() ) ? true : null,
			'is_op' 			=> ( defined( 'CX_OP' ) && is_admin() ) ? true : null,
			'is_home' 			=> ( is_home() || is_front_page() ) ? true : null,
			'current_page'		=> $this->current_page,
			'company_avatar'	=> $company_avatar,
			'ip' 				=> $this->ip
		);

		// Add user information
		if( is_user_logged_in() ) {

			// Get user prefix
			$user_prefix = ( defined( 'CX_OP' ) && is_admin() ) ? 'op-' : '';
			
			$js_vars['user_id'] = $user_prefix . $this->user->ID;
			$js_vars['user_name'] = cx_get_operator_name();
			$js_vars['user_email'] = $this->user->user_email;
			$js_vars['user_email_hash'] = md5( $this->user->user_email );

		}

		if( defined( 'CX_OP' ) ) {

			// Get Firebase application ID and token
			$js_vars['app_id'] = !empty( $this->opts['app_url'] ) ? $this->opts['app_url'] : null;

			// Get current page
			$current_page = ( !empty( $_GET['page'] ) ) ? $_GET['page'] : null;

			// Console messages
			if( $current_page == 'chat_x' ) {

				$js_vars['msgs'] = array(
					'connect' => __( 'Connect', 'cx' ),
					'disconnect' => __( 'Disconnect', 'cx' ),
					'you_offline' => __( "You're offline", 'cx' ),
					'save_note' => __( 'Chat logs saved into your own server and removed from realtime app platform after ending chat', 'cx' ),
					'save_end_chat' => __( 'Save and end chat', 'cx' ),
					'ntf_close_console' => __( 'If you leave, you will be logged out of chat. However you will be able to save conversations into your server when you come back to console!', 'cx' ),
					'new_msg' => __( 'New Message', 'cx' ),
					'new_user_online' => __( 'New User Online', 'cx' ),
					'saving' => __( 'Saving', 'cx' )
				);

			}
		}

		wp_localize_script( 'cx-app', 'cx', $js_vars );
	}
}

// Init Chat class
$GLOBALS['CX'] = new CX();

} // class_exists


// cok sevmistik, keske erimeseydik...
