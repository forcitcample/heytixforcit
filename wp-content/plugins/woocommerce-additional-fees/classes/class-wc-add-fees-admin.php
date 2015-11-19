<?php
/**
 * Description of WC_Add_Fees_Admin
 *
 * @author Schoenmann Guenter
 * @version 1.0.0.0
 */
if ( ! defined( 'ABSPATH' ) )  {  exit;  }   // Exit if accessed directly

class WC_Add_Fees_Admin
{
	const TABID = 'wc_additional_fees';

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
	 * @var WC_Addons_Add_Fees
	 */
	public $woo_addons;

	/**
	 * Current Product-ID postmeta array
	 *
	 * @var array
	 */
	protected $addfee_postmeta_product;


	public function __construct()
	{
		$this->options = array();		//	will be set after WC is init
		$this->addfee_postmeta_product = array();
		$this->woo_addons = new WC_Addons_Add_Fees();

		$this->fields = array();
		$this->current_tab = '';
		$this->settings_tabs = '';

		add_action( 'admin_init', array( $this, 'handler_wp_admin_init' ) );
		add_action( 'admin_print_styles', array( $this, 'handler_wp_admin_print_styles' ) );
		add_filter( 'plugin_action_links_' . WC_Add_Fees::$plugin_base_name, array( $this, 'handler_wp_plugin_action_links' ) );
		add_action( 'add_meta_boxes', array( $this, 'handler_wp_add_meta_boxes' ), 29 );

		add_action( 'woocommerce_additional_fees_init', array( $this, 'handler_wc_add_fees_init' ), 10 );

			//	attach to WooCommerce settings page and order page hooks
		if( is_admin() )
		{
			$this->attach_to_wc_settingspage();
			$this->attach_to_wc_productpage();
			$this->attach_to_wc_shop_orderpage();
		}

	}

	public function __destruct()
	{
		unset( $this->options );
		unset( $this->addfee_postmeta_product );
		unset( $this->fields );
		unset( $this->woo_addons );
	}

	/**
	 * Update after WC and main object had been completely initialised
	 *
	 */
	public function handler_wc_add_fees_init() 
	{
		$this->options = WC_Add_Fees::instance()->options;

		$key_session = self::KEY_SESSION;

		if ( isset( WC()->session->$key_session ) ) 
		{
			$this->woo_addons = unserialize( WC()->session->$key_session );
			unset( WC()->session->$key_session );
		}

		$this->woo_addons->attach_fields();
	}

	/**
	 * Attaches to WooCommerce Settings page handlers
	 */
	protected function attach_to_wc_settingspage()
	{
		$this->current_tab = ( isset( $_GET['tab'] ) ) ? $_GET['tab'] : 'general';

		//	Add all tabs required
		$this->settings_tabs = array(
			self::TABID => __( 'Additional Fees', WC_Add_Fees::TEXT_DOMAIN )
		);

			// Load in the new settings tabs and attach handler.
		add_action( 'woocommerce_settings_tabs', array( $this, 'handler_wc_add_settings_tab' ), 10 );

			// Run these actions when generating the settings tabs.
		foreach ( $this->settings_tabs as $name => $label ) 
		{
			add_action( 'woocommerce_settings_tabs_' . $name, array( $this, 'handler_wc_get_settings_tab' ), 10 );
			add_action( 'woocommerce_update_options_' . $name, array( $this, 'handler_wc_save_settings_tab' ), 10 );
		}

			//	add fields to tab on admin page
		add_action( 'woocommerce_additional_fee_settings', array( $this, 'handler_wc_add_settings_fields' ), 10 );
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
		add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'handler_wc_product_write_panel_tabs' ), 10 );


		/**
		 * Output inputfields and content of our tab
		 *
		 * admin/post-types/writepanels/writepanel-product_data.php  (618)
		 * do_action( 'woocommerce_product_write_panels' );
		 */
		add_action( 'woocommerce_product_write_panels', array( $this, 'handler_wc_product_write_panel' ), 10 );

		/**
		 * All product data already had been saved - save our post meta data
		 *
		 * admin/post-types/writepanels/writepanels-init.php  (127)
		 * do_action( 'woocommerce_process_' . $post->post_type . '_meta', $post_id, $post );
		 */
		add_action( 'woocommerce_process_product_meta', array( $this, 'handler_wc_save_metabox_product' ), 10, 2 );


	}
	
	/**
	 * Attach to shop order page, where we have option fields and a recalc button (metabox order total)
	 * 
	 */
	protected function attach_to_wc_shop_orderpage()
	{
		/**
		 * Called when the metaboxes for the order are saved. Needed to save the checkboxes for calculation of
		 * additional fees for the order and calculate the fees for the order. We depend on WC to save all data,  
		 * therefore we take a high priority
		 * 
		 * includes/admin/post-types/class-wc-admin-meta-boxes.php
		 * do_action( 'woocommerce_process_' . $post->post_type . '_meta', $post_id, $post );
		 */
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'handler_wc_save_metabox_shop_order' ), 5000, 2 );
	}

	/**
	 * Add all tabbed sections
	 */
	public function handler_wc_add_settings_tab()
	{
		foreach ( $this->settings_tabs as $name => $label )
		{
			$class = 'nav-tab';
			if( $this->current_tab == $name ) 
			{
				$class .= ' nav-tab-active';
			}
			echo '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . $name ) . '" class="' . $class . '">' . $label . '</a>';
		}
	}

	/**
	 * Called when viewing our custom settings tab(s) . One function for all tabs.
	 */
	public function handler_wc_get_settings_tab()
	{
		global $woocommerce_settings;

			// Determine the current tab in effect.
		$this->current_tab = $this->get_tab_in_view( current_filter(), 'woocommerce_settings_tabs_' );

			// Hook onto this from another function to keep things clean.
		do_action( 'woocommerce_additional_fee_settings' );

			// Display settings for this tab (make sure to add the settings to the tab) .
		woocommerce_admin_fields( $woocommerce_settings[$this->current_tab] );
	}

	/**
	 * Add settings fields for each tab.
	 */
	public function handler_wc_add_settings_fields()
	{
		global $woocommerce_settings;

		// Load the prepared form fields.
		$panel = new WC_Add_Fees_Panel_Admin( $this->options, $this->woo_addons );
		$inputfields = $panel->get_form_fields_settings();

		$this->fields[$this->current_tab] = apply_filters( 'woocommerce_additional_fees_fields', $inputfields );
		if ( is_array( $this->fields ) )
		{
			foreach ( $this->fields as $k => $v )
			{
				$woocommerce_settings[ $k ] = $v;
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
		$str = '<li class="add_fees_tab advanced_options"><a href="#add_fees_product_data">' . __( 'Additional Fees', WC_Add_Fees::TEXT_DOMAIN ) . '</a></li>';
		echo $str;
	}

	/**
	 * Output inputfields and content of our tab on product page
	 */
	public function handler_wc_product_write_panel()
	{
		global $post;

		$post_meta = WC_Add_Fees::get_post_meta_product_default( $post->ID );

            	// Load the form fields.
		$panel = new WC_Add_Fees_Panel_Admin( $this->options, $this->woo_addons );
		$panel->echo_form_fields_product( $post_meta );

		return;

	}

	/**
	 * All product data already had been saved by WooCommerce - save our post meta data now
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 */
	public function handler_wc_save_metabox_product( $post_id, WP_Post $post ) 
	{
		// Load the prepared form fields.
		$panel = new WC_Add_Fees_Panel_Admin( $this->options, $this->woo_addons );
		$this->options = $panel->save_options_product( $post_id );

		$key_session = self::KEY_SESSION;

		//	save to session
		if ( $this->woo_addons->count_errors() > 0 ) 
		{
			WC()->session->$key_session = serialize( $this->woo_addons );
		}
	}
	
	/**
	 * Get the options for this order - called when save order button is clicked
	 * 
	 * @param int $order_id
	 * @param WP_Post $post
	 */
	public function handler_wc_save_metabox_shop_order( $order_id, WP_Post $post )
	{	
		$recalc = isset( $_REQUEST['_add_fee_recalc'] ) ? $_REQUEST['_add_fee_recalc'] : 'no';
		$recalc_save = isset( $_REQUEST['_add_fee_recalc_save'] ) ? $_REQUEST['_add_fee_recalc_save'] : 'no';
		$fixed_gateway = isset( $_REQUEST['_add_fee_fixed_gateway'] ) ? $_REQUEST['_add_fee_fixed_gateway'] : 'no';
		
		$pm = WC_Add_Fees::get_post_meta_order_default( $order_id );
		
		$pm[WC_Add_Fees::OPT_ENABLE_RECALC] = $recalc;
		$pm[WC_Add_Fees::OPT_ENABLE_RECALC_SAVE_ORDER] = $recalc_save;
		$pm[WC_Add_Fees::OPT_FIXED_GATEWAY] = $fixed_gateway;
		update_post_meta( $order_id, WC_Add_Fees::KEY_POSTMETA_ORDER, $pm );

		if( $recalc_save != 'yes' ) 
		{
			return;
		}
		
		$order = new WC_Order_Add_Fees( $order_id );
		$payment_gateway_key = $order->get_payment_method();
		
		if ( ! WC()->customer instanceof WC_Customer )
		{
			if( version_compare( WC()->version, '2.2.0', '<' ) )
			{
				WC()->customer = new WC_Customer();
			}
			else
			{
				WC()->customer = new WC_Customer_Add_Fees();
			}
		}
		
		global $wp_query;
		$wp_query->set( 'order-pay', $order_id );
		
		WC_Add_Fees::instance()->load_request_data( $payment_gateway_key );
		WC_Add_Fees::instance()->calculate_gateway_fees_order( $order_id, $order, true );
		
		return;
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
	protected function save_all_options_settings() 
	{
		// Load the prepared form fields.
		$panel = new WC_Add_Fees_Panel_Admin( $this->options, $this->woo_addons );
		$this->options = $panel->save_options_settings();

		$key_session = self::KEY_SESSION;

		// save to session
		if( $this->woo_addons->count_errors() > 0 ) {
			WC()->session->$key_session = serialize( $this->woo_addons );
		}
	}

	/**
	 * Registers scripts from framework for admin page only
	 *
	 * @return type
	 */
	public function handler_wp_admin_init()
	{
		wp_register_style( 'woocommerce_additional_fees_admin_css', WC_Add_Fees::$plugin_url . 'css/wc_additional_fees_admin.css' );
		wp_register_script( 'woocommerce_additional_fees_admin_script', WC_Add_Fees::$plugin_url . 'js/wc_additional_fees_admin.js', array( 'jquery' ) );
	}

	/**
	 * Add all styles to admin page
	 */
	public function handler_wp_admin_print_styles()
	{
		wp_enqueue_style( 'woocommerce_additional_fees_admin_css' );
		wp_enqueue_script( 'woocommerce_additional_fees_admin_script' );

		$var = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			self::AJAX_NONCE => wp_create_nonce( self::AJAX_NONCE ),
			'alert_ajax_error' => __( 'An internal server error occured in processing a request. Please try again or contact us. Thank you. ', WC_Add_Fees::TEXT_DOMAIN )
			);

		wp_localize_script( 'woocommerce_additional_fees_admin_script', self::AJAX_JS_VAR, $var );

	}
	
	/**
	 * Show action links on the plugin screen
	 *
	 * @param mixed $links
	 * @return array
	 */
	public function handler_wp_plugin_action_links( array $links )
	{
		return array_merge( 
				array(
					'<a href="'.add_query_arg( 'tab', self::TABID, admin_url( 'admin.php?page=wc-settings' ) ) . '" class="" title="' . __( 'Goto settingspage of this plugin', WC_Add_Fees::TEXT_DOMAIN ) . '">' . __( 'Settings', WC_Add_Fees::TEXT_DOMAIN ) . '</a>'
					),
				$links
				  );
	}

	/**
	 * Adds a metabox below WC 'Action' metabox
	 */
	public function handler_wp_add_meta_boxes()
	{
		add_meta_box( 'woocommerce-order-additional-fees', __( 'Additional Fees', WC_Add_Fees::TEXT_DOMAIN ), array( $this, 'handler_wp_metabox_order_output' ), 'shop_order', 'side', 'default' );
	}

	/**
	 * Outputs the metabox with the checkboxes
	 * 
	 * Since 2.2.0 refunds are allowed -> if refunds for a payment gateway, then a recalculation is not possible, because we
	 * need to delete the fee lines. We loose context to refund entries. Disallow recalc.
	 * @param WP_Post $order
	 * @param array $metabox
	 */
	public function handler_wp_metabox_order_output( WP_Post $order, array $metabox )
	{
		$pm = WC_Add_Fees::get_post_meta_order_default( $order->ID );
		
		$checked_recalc = $pm[ WC_Add_Fees::OPT_ENABLE_RECALC ] == 'yes' ? ' checked="checked" ' : '';
		$checked_recalc_save_order = $pm[ WC_Add_Fees::OPT_ENABLE_RECALC_SAVE_ORDER ] == 'yes' ? ' checked="checked" ' : '';
		$checked_fixed_gateway = $pm[ WC_Add_Fees::OPT_FIXED_GATEWAY ] == 'yes' ? ' checked="checked" ' : '';
		$readonly = '';
		$alert = '';
		
		if( version_compare( WC()->version, '2.2.0', '>=' ) )
		{
			$wc_order = new WC_Order( $order->ID );
			$fees = $pm[WC_Add_Fees::OPT_KEY_FEE_ITEMS];
			
			$total = 0;
			foreach ( $fees as $item_key => $fee ) 
			{
				if( $fee->source == WC_Add_Fees::OPTIONNAME )
				{
					$total += $wc_order->get_total_refunded_for_item( $item_key, 'fee' );
				}
			}
				//	fees have a refund -> disable output fields
			if( $total > 0 )
			{
				
				$checked_recalc = '';
				$checked_recalc_save_order = '';
				$checked_fixed_gateway = ' checked="checked" ';
				$readonly = ' disabled="disabled" ';
				$alert = __( 'Recalculation of fees is disabled due to refunds on additional fees !', WC_Add_Fees::TEXT_DOMAIN );
			}
		}
		
		$style = 'style="display: block; float: left;"';
		
		echo '<div class="totals_group">';
		if( ! empty( $alert ) )
		{
			echo	'<p style="color:red; font-weight:bold;">' . $alert . '</p>';
		}
		echo	'<p>';
		echo		'<input type="checkbox" id="_add_fee_recalc" name="_add_fee_recalc" value="yes" ' . $style . $checked_recalc . $readonly . '/>';
		echo					__( 'Allow calculation of fees', WC_Add_Fees::TEXT_DOMAIN ) . ' <span class="tips" data-tip="' . __( 'If checked, additional fees will be calculated on the pay-for-order page, if the payment gateway is changed. ', WC_Add_Fees::TEXT_DOMAIN ) . '">[?]</span>';
		echo	'</p>';
		echo	'<p>';
		echo		'<input type="checkbox" id="_add_fee_recalc_save" name="_add_fee_recalc_save" value="yes" ' . $style . $checked_recalc_save_order . $readonly. '/>';
		echo					__( 'Calculate fees on saving/updating order', WC_Add_Fees::TEXT_DOMAIN ) . ' <span class="tips" data-tip="' . __( 'If checked, additional fees will be calculated, when the order is saved. The state of the checkbox above will be ignored. ', WC_Add_Fees::TEXT_DOMAIN ) . '">[?]</span>';
		echo	'</p>';
		echo	'<p>';
		echo		'<input type="checkbox" id="_add_fee_fixed_gateway" name="_add_fee_fixed_gateway" value="yes" ' . $style . $checked_fixed_gateway . $readonly . '/>';
		echo					__( 'Do not allow to change gateway', WC_Add_Fees::TEXT_DOMAIN ) . ' <span class="tips" data-tip="' . __( 'If checked, the customer cannot choose a different payment gateway on the pay-for-order page. ', WC_Add_Fees::TEXT_DOMAIN ) . '">[?]</span>';
		echo	'</p>';
		echo '</div>';
	}

}


