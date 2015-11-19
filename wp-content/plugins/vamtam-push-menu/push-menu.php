<?php

/*
Plugin Name: Vamtam Push Menu
Description: Implements the "Navigation Drawer" UI pattern from Android 4.x
Version: 2.0.7
Author: Vamtam
Author URI: http://vamtam.com
*/

$file = __FILE__;
if ( isset( $mu_plugin ) ) {
	$file = $mu_plugin;
}
if ( isset( $network_plugin ) ) {
	$file = $network_plugin;
}
if ( isset( $plugin ) ) {
	$file = $plugin;
}

$GLOBALS['WpvPushMenuPath'] = trailingslashit( plugin_dir_url($file) );
$GLOBALS['WpvPushMenuVersion'] = '2.0.7';

class WpvPushMenu {
	private $menu_name = 'wpv-push-menu';
	private $backup_menu_name = 'menu-header';

	public static $instance;

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_footer', array( $this, 'wp_footer' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		if ( ! class_exists( 'Vamtam_Updates_2' ) ) {
			require 'vamtam-updates/class-vamtam-updates.php';
		}

		new Vamtam_Updates_2( __FILE__ );
	}

	public function init() {
		register_nav_menus( array(
			$this->menu_name => __( 'Push Menu', 'vamtam-push-menu' ),
		) );

		$domain = 'vamtam-push-menu';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );
	}

	public function wp_footer() {
		$templates = array( 'menu-item', 'menu-root' );
		foreach ( $templates as $name ) {
			echo "<script id='wpvpm-$name' type='text/html'>";
			echo file_get_contents('templates/'.$name.'.php', true);
			echo '</script>';
		}
	}

	public function enqueue_scripts() {
		$main_js = ( WP_DEBUG || ( defined( 'WPV_SCRIPT_DEBUG' ) && WPV_SCRIPT_DEBUG ) ) ? 'push-menu.js' : 'push-menu.min.js';
		wp_enqueue_script( 'vamtam-push-menu', $GLOBALS['WpvPushMenuPath'] . 'js/dist/' . $main_js, array( 'jquery', 'backbone', 'underscore' ), $GLOBALS['WpvPushMenuVersion'] , true );


		$this->load_menu();
	}

	private function load_menu() {
		if ( !has_nav_menu( $this->menu_name ) )
			$this->menu_name = $this->backup_menu_name;

		if ( has_nav_menu( $this->menu_name ) && $locations = get_nav_menu_locations() ) {
			$menu = wp_get_nav_menu_object( $locations[ $this->menu_name ] );

			if ( $menu && ! is_wp_error( $menu ) && !isset( $menu_items ) )
				$menu_items = wp_get_nav_menu_items( $menu->term_id, array( 'update_post_term_cache' => false ) );

			// Set up the $menu_item variables
			_wp_menu_item_classes_by_context( $menu_items );

			$sorted_menu_items = array();
			foreach ( (array) $menu_items as $key => $menu_item )
				$sorted_menu_items[$menu_item->menu_order] = $menu_item;

			unset( $menu_items );

			$menu_order = $menu_children = $menu_items_by_id = array();

			foreach ( $sorted_menu_items as $key => $menu_item ) {
				$menu_items_by_id[$menu_item->ID] = new wpv_push_menu_item( $menu_item );
				$menu_items_by_id[(int)$menu_item->menu_item_parent]->children[] = &$menu_items_by_id[$menu_item->ID];
				if ( !(int)$menu_item->menu_item_parent )
					$menu_order[] = &$menu_items_by_id[$menu_item->ID];;
			}

			unset( $sorted_menu_items );

			$root_item = new wpv_push_menu_item();
			$root_item->title = __( 'Menu', 'vamtam-push-menu' );
			$root_item->type = 'root';
			$root_item->children = $menu_order;

			wp_localize_script( 'vamtam-push-menu', 'WpvPushMenu', array(
				'items'  => $this->fix_tree( $root_item ),
				'back'   => __( 'Back', 'vamtam-push-menu' ),
				'jspath' => trailingslashit( plugins_url( 'js', __FILE__ ) ),
				'limit'  => wpv_get_option( 'mobile-top-bar-resolution' ),
			) );

			unset( $menu_items_by_id, $menu_children, $menu_order );
		}
	}

	private function fix_tree( $tree ) {
		if ( count( $tree->children ) === 0 )
			return $tree;

		$new_tree = new wpv_push_menu_level( $tree );

		if ( $tree->type !== 'root' ) {
			if ( !empty( $tree->url ) )
				$new_tree->children[] = new wpv_push_menu_item( $tree );
		} else {
			$new_tree->type = 'root';
		}

		foreach ( $tree->children as $child ) {
			$new_tree->children[] = $this->fix_tree( $child );
		}

		if ( $new_tree->type === 'root' ) {
			$languages = $this->get_wpml_translations();
			if ( ! empty( $languages ) ) {
				$new_tree->children[] = $languages;
			}
		}

		unset( $tree );

		return $new_tree;
	}

	private function get_wpml_translations() {
		if ( ! function_exists( 'icl_get_languages' ) || in_array( 'polylang/polylang.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			return false;
		}

		$languages = icl_get_languages();

		if ( count( $languages ) <= 1 ) {
			return false;
		}

		$main = new wpv_push_menu_item;

		foreach ( $languages as $slug => $props ) {
			if ( (int)$props['active'] ) {
				$main->url        = $props['url'];
				$main->title      = $props['native_name'];
				$main->attr_title = esc_attr( $props['native_name'] );
				$main->classes    = array(
					'menu-item',
				);
			} else {
				$tmp_lang = new wpv_push_menu_item();

				$tmp_lang->url        = $props['url'];
				$tmp_lang->title      = $props['native_name'] . '('. $props['translated_name'] .')';
				$tmp_lang->attr_title = esc_attr( $props['native_name'] . '('. $props['translated_name'] .')' );
				$tmp_lang->classes    = array(
					'menu-item',
				);

				$main->children[] = $tmp_lang;
			}
		}

		return $main;
	}
}

class wpv_push_menu_item {
	public function __construct( $menu_item = null ) {
		$copy = array(
			'url',
			'title',
			'attr_title',
			'description',
			'classes',
		);

		foreach ( $copy as $prop )
			$this->$prop = isset( $menu_item->$prop ) ? $menu_item->$prop : '';

		$this->type = 'item';
		$this->children = array();

		if ( ! is_array( $this->classes ) ) {
			$this->classes = array( $this->classes );
		}
	}
}

class wpv_push_menu_level {
	public function __construct( $menu_item ) {
		$copy = array(
			'title',
			'description',
		);

		foreach ( $copy as $prop )
			$this->$prop = $menu_item->$prop;

		$this->type = 'item';
		$this->children = array();
	}
}

WpvPushMenu::get_instance();
