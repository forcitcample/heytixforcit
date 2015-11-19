<?php
/**
 * Description of woocommerce_additional_fees_admin
 *
 * @author Schoenmann Guenter
 * @version 1.0.0.0
 */
class woocommerce_additional_fees_admin
{
	const TABID = 'ips_wc_additional_fees';

	const KEY_SESSION = 'wc_additional_fees_session';

	const AJAX_NONCE = 'wc_additional_fees_nonce';
	const AJAX_JS_VAR = 'wc_additional_fees_var';
	const AJAX_JS_TRANSLATE = 'wc_additional_fees_translate';

	/**
	 * WooCommerce Variables
	 *
	 * @var
	 */
	public $settings_tabs;
	public $current_tab;
	public $fields;

	/**
	 *
	 * @var array
	 */
	protected $options;

	/**
	 *
	 * @var woocommerce_addons_add_fees
	 */
	public $woo_addons;

	/**
	 * Current Product-ID postmeta array
	 *
	 * @var array
	 */
	protected $addfee_postmeta_product;

	/**
	 * Pointer to global object
	 *
	 * @var $woocommerce_additional_fees
	 */
	public $woocommerce_additional_fees;


	public function __construct()
	{
		$this->options = woocommerce_additional_fees::get_options_default();
		$this->addfee_postmeta_product = array();
		$this->woo_addons = new woocommerce_addons_add_fees();
		$this->woocommerce_additional_fees = null;

		$this->fields = array();
		$this->current_tab = '';
		$this->settings_tabs = '';

		add_action('admin_init', array(&$this, 'handler_wp_admin_init'));
		add_action('admin_print_styles', array(&$this, 'handler_wp_admin_print_styles'));

		add_action('woocommerce_additional_fees_init', array(&$this, 'handler_wc_add_fees_init'));


			//	attach to WooCommerce settings page
		if(is_admin())
		{
			$this->attach_to_wc_settingspage();
			$this->attach_to_wc_productpage();
		}

	}

	public function __destruct()
	{
		unset($this->options);
		unset($this->addfee_postmeta_product);
		unset($this->fields);
		unset($this->woo_addons);
		unset($this->woocommerce_additional_fees);
	}

	/**
	 * Update after main object had been completely initialised
	 *
	 * @param woocommerce_additional_fees $object
	 */
	public function handler_wc_add_fees_init( $object ) {
		global $woocommerce;

		$this->woocommerce_additional_fees = $object;
		$this->options = $object->options;

		$key_session = self::KEY_SESSION;

		if ( isset( $woocommerce->session->$key_session ) ) {
			$this->woo_addons = unserialize( $woocommerce->session->$key_session );
			unset( $woocommerce->session->$key_session );
		}

		$this->woo_addons->attach_fields();
	}

	/**
	 * Attaches to WooCommerce Settings page handlers
	 */
	protected function attach_to_wc_settingspage()
	{
		$this->current_tab = ( isset($_GET['tab'] ) ) ? $_GET['tab'] : 'general';

		//	Add all tabs required
		$this->settings_tabs = array(
			self::TABID => __( 'Additional Fees', woocommerce_additional_fees::TEXT_DOMAIN)
		);

			// Load in the new settings tabs and attach handler.
		add_action( 'woocommerce_settings_tabs', array( &$this, 'handler_wc_add_settings_tab' ), 10 );

			// Run these actions when generating the settings tabs.
		foreach ( $this->settings_tabs as $name => $label ) {
			add_action( 'woocommerce_settings_tabs_' . $name, array( &$this, 'handler_wc_get_settings_tab' ), 10 );
			add_action( 'woocommerce_update_options_' . $name, array( &$this, 'handler_wc_save_settings_tab' ), 10 );
		}

			//	add fields to tab on admin page
		add_action( 'woocommerce_additional_fee_settings', array( &$this, 'handler_wc_add_settings_fields' ), 10 );
	}

	/**
	 * Attaches to single product page handlers to build input fields and save the
	 * settings for a product
	 *
	 */
	protected function attach_to_wc_productpage()
	{
		/**
		 * Output tab for our panel
		 *
		 * admin/post-types/writepanels/writepanel-product_data.php  (89)
		 * do_action( 'woocommerce_product_write_panel_tabs' );
		 */
		add_action('woocommerce_product_write_panel_tabs', array(&$this, 'handler_wc_product_write_panel_tabs'), 10);


		/**
		 * Output inputfields and content of our tab
		 *
		 * admin/post-types/writepanels/writepanel-product_data.php  (618)
		 * do_action( 'woocommerce_product_write_panels' );
		 */
		add_action('woocommerce_product_write_panels', array(&$this, 'handler_wc_product_write_panel'), 10);

		/**
		 * All product data already had been saved - save our post meta data
		 *
		 * admin/post-types/writepanels/writepanels-init.php  (127)
		 * do_action( 'woocommerce_process_' . $post->post_type . '_meta', $post_id, $post );
		 */
		add_action('woocommerce_process_product_meta', array(&$this, 'handler_wc_save_metabox_product'), 10, 2);


	}

	/**
	 * Add all tabbed sections
	 */
	public function handler_wc_add_settings_tab()
	{
		foreach ( $this->settings_tabs as $name => $label )
		{
			$class = 'nav-tab';
			if( $this->current_tab == $name ) $class .= ' nav-tab-active';
			if ( version_compare( WC_VERSION, '2.2.0', '<' ) ) 
			{
				echo '<a href="' . admin_url( 'admin.php?page=woocommerce&tab=' . $name ) . '" class="' . $class . '">' . $label . '</a>';
			}
			else
			{
				echo '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . $name ) . '" class="' . $class . '">' . $label . '</a>';
			}
		}
	}

	/**
	 * Called when viewing our custom settings tab(s). One function for all tabs.
	 */
	public function handler_wc_get_settings_tab()
	{
		global $woocommerce_settings;

			// Determine the current tab in effect.
		$this->current_tab = $this->get_tab_in_view( current_filter(), 'woocommerce_settings_tabs_' );

			// Hook onto this from another function to keep things clean.
		do_action( 'woocommerce_additional_fee_settings' );

			// Display settings for this tab (make sure to add the settings to the tab).
		woocommerce_admin_fields( $woocommerce_settings[$this->current_tab] );
	}

	/**
	 * Add settings fields for each tab.
	 */
	public function handler_wc_add_settings_fields()
	{
		global $woocommerce_settings;

		// Load the prepared form fields.
		$panel = new wc_panel_admin($this->options, $this->woocommerce_additional_fees, $this->woo_addons);
		$inputfields = $panel->get_form_fields_settings();

		$this->fields[$this->current_tab] = apply_filters('woocommerce_additional_fees_fields', $inputfields);
		if ( is_array( $this->fields ) )
		{
			foreach ( $this->fields as $k => $v )
			{
				$woocommerce_settings[$k] = $v;
			}
		}
	}

	/**
	 * Woocommerce saves settings in a single field in the database for each option.
	 * This does not apply for this plugin, we use our own structure and also handle
	 * initialising of form with stored values.
	 *
	 * We ignore woocommere options handling
	 */
	public function handler_wc_save_settings_tab()
	{
//		global $woocommerce_settings;

		// Make sure our settings fields are recognised.
//		$this->add_settings_fields();

//		$current_tab = $this->get_tab_in_view( current_filter(), 'woocommerce_update_options_' );
//		woocommerce_update_options( $woocommerce_settings[$current_tab] );

		//	save all data to own option
		$this->save_all_options_settings();
	}

	/**
	 * Output tab for our panel on product page.
	 */
	public function handler_wc_product_write_panel_tabs()
	{
		$str = '<li class="add_fees_tab advanced_options"><a href="#add_fees_product_data">'.__( 'Additional Fees', woocommerce_additional_fees::TEXT_DOMAIN ).'</a></li>';
		echo $str;
	}

	/**
	 * Output inputfields and content of our tab on product page
	 */
	public function handler_wc_product_write_panel()
	{
		global $post;

		$post_meta = woocommerce_additional_fees::get_post_meta_product_default($post->ID);

            	// Load the form fields.
		$panel = new wc_panel_admin($this->options, $this->woocommerce_additional_fees, $this->woo_addons);
		$panel->echo_form_fields_product($post_meta);

		return;

	}

	/**
	 * All product data already had been saved by WooCommerce - save our post meta data now
	 *
	 * @param int $post_id
	 * @param object $post
	 */
	public function handler_wc_save_metabox_product( $post_id, $post ) {
		global $woocommerce;

		// Load the prepared form fields.
		$panel = new wc_panel_admin( $this->options, $this->woocommerce_additional_fees, $this->woo_addons );
		$this->options = $panel->save_options_product( $post_id );

		$key_session = self::KEY_SESSION;

		//	save to session
		if ( $this->woo_addons->count_errors() > 0 ) {
			$woocommerce->session->$key_session = serialize( $this->woo_addons );
		}
	}

		/**
	 * Get the tab current in view/processing.
	 *
	 * @param string $current_filter
	 * @param string $filter_base
	 */
	protected function get_tab_in_view ( $current_filter, $filter_base )
	{
		return str_replace( $filter_base, '', $current_filter );
	}

	/**
	 * Saves the options in own option entry
	 */
	protected function save_all_options_settings() {
		global $woocommerce;

		// Load the prepared form fields.
		$panel = new wc_panel_admin( $this->options, $this->woocommerce_additional_fees, $this->woo_addons );
		$this->options = $panel->save_options_settings();

		$key_session = self::KEY_SESSION;

		// save to session
		if( $this->woo_addons->count_errors() > 0 ) {
			$woocommerce->session->$key_session = serialize( $this->woo_addons );
		}
	}



	/**
	 * Registers scripts from framework for admin page only
	 *
	 * @return type
	 */
	public function handler_wp_admin_init()
	{
		wp_register_style('woocommerce_additional_fees_admin_css', woocommerce_additional_fees::$plugin_url . 'v103/css/wc_additional_fees_admin.css');
		wp_register_script('woocommerce_additional_fees_admin_script', woocommerce_additional_fees::$plugin_url.'v103/js/wc_additional_fees_admin.js', array('jquery'));
	}

	/**
	 * Add all styles to admin page
	 */
	public function handler_wp_admin_print_styles()
	{

		wp_enqueue_style('woocommerce_additional_fees_admin_css');
		wp_enqueue_script('woocommerce_additional_fees_admin_script');

		$var = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			self::AJAX_NONCE => wp_create_nonce( self::AJAX_NONCE )
			);

		wp_localize_script( 'woocommerce_additional_fees_admin_script', self::AJAX_JS_VAR, $var);

	}



}

?>
