<?php
/**
 * Description of WC_Add_Fees
 *
 * Call init_values before using any members and properties of this class to connect to woocommerce data !!!
 *
 * @author Schoenmann Guenter
 * @version 2.2.6
 */
if ( ! defined( 'ABSPATH' ) )  {  exit;  }   // Exit if accessed directly

class WC_Add_Fees
{
	const VERSION = '2.2.6';
	const TEXT_DOMAIN = 'woocommerce_additional_fees';

	const OPTIONNAME = 'woocommerce_additional_fees';
	const KEY_POSTMETA_PRODUCT = '_woocommerce_add_fees_product';
	const KEY_POSTMETA_ORDER = '_woocommerce_add_fees_order';

	const OPT_VERSION = 'version';
	const OPT_DEL_ON_DEACTIVATE = 'delete_on_deactivate';
	const OPT_DEL_ON_UNINSTALL = 'delete_on_uninstall';
	const OPT_ENABLE_ALL = 'enable_all';
	const OPT_ENABLE_PROD_FEES = 'enable_prod_fees';
	const OPT_ENABLE_PROD = 'enable_prod';
	const OPT_GATEWAY_PREFIX = 'gateways';			//	Main option entry => gateway key => .....

	const OPT_KEY_ENABLE = 'enable';
	const OPT_KEY_TAXCLASS = 'taxclass';
	const OPT_KEY_ADD_VALUE_TYPE = 'addvaluetype';
	const OPT_KEY_VALUE_TO_ADD = 'addvalue';
	const OPT_KEY_VALUE_TO_ADD_FIXED = 'addvalue_fix';
	const OPT_KEY_MAX_VALUE = 'maxvalue';
	const OPT_KEY_OUTPUT = 'outputtext';
	
	const OPT_ENABLE_RECALC = 'recalc_fee';
	const OPT_ENABLE_RECALC_SAVE_ORDER = 'recalc_fee_save_order';
	const OPT_FIXED_GATEWAY = 'fixed_gateway';
	const OPT_KEY_FEE_ITEMS = 'fee_items';

	const VAL_FIXED = 'fixed_value';
	const VAL_ADD_PERCENT = 'add_percent';
	const VAL_INCLUDE_PERCENT = 'include_percent';

	const VAL_TAX_NONE = 'tax_none';
	const VAL_TAX_STANDARD = 'Standard';		//	woocommerce default

	const AJAX_NONCE = 'add_fee_nonce';
	const AJAX_JS_VAR = 'add_fee_vars';

	/**
	 * @var WC_Add_Fees The single instance of the class
	 * @since 2.2
	 */
	static public $_instance = null;

	/**
	 * key => value for selectbox for type of additional fees
	 *
	 * @var array
	 */
	static public $value_type_to_add;

	/**
	 * If true, deactivation checkbox is shown
	 *
	 * @var bool
	 */
	static public $show_activation;

	/**
	 * If true, uninstall checkbox is shown
	 *
	 * @var bool
	 */
	static public $show_uninstall;

	/**
	 *
	 * @var string
	 */
	static public $plugin_url;

	/**
	 *
	 * @var string
	 */
	static public $plugin_path;
	
	/**
	 *
	 * @var string 
	 */
	static public $plugin_base_name;

	/**
	 * All available tax classes
	 *
	 * @var array string
	 */
	public $tax_classes;

	/**
	 * All available gateways
	 *
	 * @var array WC_Payment_Gateways
	 */
	public $gateways;

	/**
	 * Current requested gateway key
	 *
	 * @var string
	 */
	public $payment_gateway_key;
	
	/**
	 * Default gateway, if no gateway selected or invalid
	 * 
	 * @var string 
	 */
	public $default_payment_gateway_key;
	
	/**
	 * Option additional fee for selected $payment_gateway_key
	 *
	 * @var array
	 */
	public $payment_gateway_option;

	/**
	 * Option array for plugin
	 *
	 * @var array
	 */
	public $options;

	/**
	 * Set to true, if form request data was loaded already and members are initialied
	 *
	 * @var bool
	 */
	protected $request_data_loaded;

	/**
	 * WC option variable for precision 
	 * @var int
	 */
	public $dp;
	
	/**
	 * WC option where to round tax
	 * 
	 * @var boolean
	 */
	public $round_at_subtotal;
	
	/**
	 * All plugins cause errors using function payment_gateways->get_available_payment_gateways()
	 * Set this array in the constructor
	 * 
	 * @var array
	 */
	public $gateway_bugfix_array;
	/**
	 * Set to true, if payment gateways have to be loaded directly due to errors i third party plugins
	 * 
	 * @var boolean
	 */
	public $gateway_bugfix;

	/**
	 * a unique product line counter to make each line unique 
	 * http://www.woothemes.com/products/gravity-forms-add-ons/ allows the same product in different lines (not the WC standard behaviour)
	 * 
	 * @var int
	 */
	private $prod_fee_cnt;
	/**
	 * Main wc_email_att Instance
	 *
	 * Ensures only one instance of wc_email_att is loaded or can be loaded.
	 *
	 * @return WC_Add_Fees - Main instance
	 */
	public static function instance() 
	{
		if ( is_null( self::$_instance ) ) 
		{
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.2
	 */
	public function __clone() 
	{
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', WC_Add_Fees::TEXT_DOMAIN ), '2.2' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.2
	 */
	public function __wakeup() 
	{
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', WC_Add_Fees::TEXT_DOMAIN ), '2.2' );
	}
	
	public function __construct()
	{
		spl_autoload_register( 'WC_Add_Fees::autoload' );
		
		if( ! isset( self::$show_activation ) )
		{
			self::$show_activation = true;
		}

		if( ! isset( self::$show_uninstall ) )
		{
			self::$show_uninstall = true;
		}
		
		if( ! isset( self::$plugin_path ) )
		{
			self::$plugin_path = '';
		}
		
		if( ! isset( self::$plugin_url ) )
		{
			self::$plugin_url = '';
		}
		
		if( ! isset( self::$plugin_base_name ) )
		{
			self::$plugin_base_name = '';
		}
		
		$this->options = self::get_options_default();

		$this->payment_gateway_key = '';
		$this->default_payment_gateway_key = '';
		$this->payment_gateway_option = array();
		$this->tax_classes = array();
		$this->gateways = array();
		self::$value_type_to_add = array();
		$this->request_data_loaded = false;
		
		$this->dp                = (int) get_option( 'woocommerce_price_num_decimals' );
		$this->round_at_subtotal = get_option( 'woocommerce_tax_round_at_subtotal' ) == 'yes';

			//	add all plugins that produce an error on payment_gateways->get_available_payment_gateways()
		$this->gateway_bugfix_array = array(
					'woocommerce-account-funds/woocommerce-account-funds.php'
				);
		$this->gateway_bugfix = false;
		$this->prod_fee_cnt = 0;
		
		if( is_admin() )
		{
			new WC_Add_Fees_Admin();
		}
		
		add_action( 'init', array( $this, 'handler_wp_load_textdomains' ), 1 );
		add_action( 'init', array( $this, 'handler_wp_init' ), 1 );
		add_action( 'init', array( $this, 'handler_wp_register_scripts' ), 10 );
		
		add_action( 'wp_print_styles', array( $this, 'handler_wp_print_styles' ), 10 );
		add_action( 'woocommerce_init', array( $this, 'handler_wc_init' ), 500 );

		if( $this->options[self::OPT_ENABLE_ALL] )
		{
			$this->attach_to_woocommerce();
		}
		
		add_action( 'wp_ajax_nopriv_add_fee_calc_fee_pay_order', array( $this, 'handler_ajax_calc_fee_pay_order' ) );
		add_action( 'wp_ajax_add_fee_calc_fee_pay_order', array( $this, 'handler_ajax_calc_fee_pay_order' ) );
		
		add_action( 'wp_ajax_woocommerce_remove_order_item', array( $this, 'handler_ajax_wc_remove_order_item' ), 1 );
	}

	public function __destruct()
	{
		unset( $this->options );
		unset( $this->payment_gateway_key );
		unset( $this->payment_gateway_option );
		unset( $this->tax_classes );
		unset( $this->gateways );
		unset( $this->gateway_bugfix_array );

	}
	
	/**
	 * This function is called by the parser when it finds a class, that is not loaded already.
	 * Needed, because WC Classes might be loaded after our plugin.
	 *
	 * @param string $class_name		classname to load rendered by php-parser
	 */
	static public function autoload( $class_name )
	{
		$filename = str_replace( '_', '-', strtolower( $class_name ) );
		
		switch ( $class_name )
		{
			case 'WC_Fee_Add_Fees':
			case 'WC_Order_Add_Fees':
			case 'WC_Customer_Add_Fees':
			case 'WC_Addons_Add_Fees':
			case 'WC_Add_Fees_Admin':
			case 'WC_Add_Fees_Activation':
				include_once self::$plugin_path.'classes/class-' . $filename . '.php';
				break;
			case 'WC_Add_Fees_Panel_Admin':
				include_once self::$plugin_path.'classes/panels/class-' . $filename . '.php';
				break;
			default:
				break;
		}
	}
	
	/**
	 * Override plugin uri with filters hooked by other plugins
	 */
	public function handler_wp_init()
	{
		self::$plugin_url = trailingslashit( plugins_url( '', plugin_basename( dirname( __FILE__ ) ) ) );
	}
	
	/**
	 * Localisation
	 **/
	public function handler_wp_load_textdomains()
	{
		$pos = strrpos( self::$plugin_base_name, '/' );
		if( $pos === false )
		{
			$pos = strrpos( self::$plugin_base_name, '\\' );
		}
		
		$language_path = ( $pos === false ) ? 'languages' : trailingslashit ( substr( self::$plugin_base_name, 0, $pos + 1 ) ) . 'languages';		
		load_plugin_textdomain( self::TEXT_DOMAIN, false, $language_path );
	}

	/**
	 *
	 */
	public function handler_wp_register_scripts()
	{
		wp_register_script( 'wc_additional_fees_script', self::$plugin_url . 'js/wc_additional_fees.js', array( 'woocommerce' ) );
	}

	/**
	 *
	 */
	public function handler_wp_print_styles()
	{
		$var = array( 
			'add_fee_ajaxurl' => admin_url( 'admin-ajax.php' ),
			self::AJAX_NONCE => wp_create_nonce( self::AJAX_NONCE ),
			'alert_ajax_error' => __( 'An internal server error occured in processing a request. Please try again or contact us. Thank you. ', self::TEXT_DOMAIN )
			);		
		
		wp_enqueue_script( 'wc_additional_fees_script' );
		wp_localize_script( 'wc_additional_fees_script', self::AJAX_JS_VAR, $var );
		
	}

	/**
	 * Possible bugfix - status of post is sometimes reset to old style without wc- when recalc of order
	 * Reupdates the status to new value
	 * 
	 * @param int $post_ID
	 * @param WP_Post $post
	 * @param bool $update
	 */
	public function handler_wp_save_post_shop_order( $post_ID, WP_Post $post, $update )
	{
		global $wpdb;
		
		$arr_stat = array_keys( wc_get_order_statuses() );
		
		if( in_array( $post->post_status, $arr_stat) ) 
		{
			return;
		}
		
		$new_stat = 'wc-' . $post->post_status;
		
			//	skip not registered status
		if( ! in_array( $new_stat, $arr_stat) ) 
		{
			return;
		}
		
		$wpdb->update( $wpdb->posts, 
						array( 'post_status' => $new_stat), 
						array( 'ID' => $post_ID ) 
					);
	}
	
	/**
	 * Attach objects to WooCommerce Data
	 */
	public function handler_wc_init()
	{
		$this->init_values();
	}


	/**
	 * Attach class to WooCommerce hooks
	 */
	protected function attach_to_woocommerce()
	{
		/**
		 * Attach to add fees applied to single products (works only when a cart is existong)
		 *
		 * classes/class-wc-cart
		 * do_action( 'woocommerce_before_calculate_totals', $this );
		 */
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'handler_wc_cart_calculate_fees' ), 500, 1 );

		/**
		 * Attach to add fees applied to total cart (works only when a cart is existong)
		 *
		 * classes/class-wc-cart
		 * previous do_action( 'woocommerce_calculate_totals', $this );
		 * now changed to do apply_filter( 'woocommerce_calculated_total', $total); because of compatibility issues with subscription plugin
		 */
		add_filter( 'woocommerce_calculated_total', array( $this, 'handler_wc_calculate_totals' ), 500, 1 );
		
		/**
		 *  Needed to properly set selected payment gateway radiobox on form-pay page for the order
		 * (wc-core only selects default gateway)
		 * 
		 * includes/shortcodes/class-WC-Shortcode-Checkout
		 * do_action( 'before_woocommerce_pay' );
		 */
		add_action( 'before_woocommerce_pay', array( $this, 'handler_wc_before_pay' ), 500 );
		
		/**
		 * Order items are deleted - Removes the information about our fees
		 * 
		 * includes/class-wc-checkout.php
		 * do_action( 'woocommerce_resume_order', $order_id );
		 */
		add_action( 'woocommerce_resume_order', array( $this, 'handler_wc_resume_order' ), 500, 1 );
		
		/**
		 * Saves the type of fee for each fee in a post meta to be able to recognize
		 * fees from our plugin from other fees on recalculation of fees in the order
		 * initiated from the admin page or from the pay for order page.
		 * (e.g. manually added fees on admin page, ..) 
		 * 
		 * includes/class-wc-checkout.php
		 * do_action( 'woocommerce_add_order_fee_meta', $order_id, $item_id, $fee, $fee_key );
		 */
		add_action( 'woocommerce_add_order_fee_meta', array( $this, 'handler_wc_add_order_fee_meta' ), 500, 4 );
		
		
		/**
		 * Possible bugfix - status of post is sometimes reset to old style without wc- when recalc of order
		 * 
		 * do_action( "save_post_{$post->post_type}", $post_ID, $post, $update );
		 */
		if(version_compare(WC()->version, '2.2.0', '>=' ) )
		{
			add_action( 'save_post_shop_order', array( $this, 'handler_wp_save_post_shop_order' ), 5000, 3 );
		}
	}

	/**
	 * Gets the options for this plugin and returns an array filled with all needed values initialised
	 *
	 * @return array
	 */
	static public function &get_options_default()
	{
		$default = array(
			WC_Add_Fees::OPT_VERSION => WC_Add_Fees::VERSION,
			WC_Add_Fees::OPT_DEL_ON_DEACTIVATE => false,
			WC_Add_Fees::OPT_DEL_ON_UNINSTALL => true,
			WC_Add_Fees::OPT_ENABLE_ALL => true,
			WC_Add_Fees::OPT_ENABLE_PROD_FEES => true,
			WC_Add_Fees::OPT_GATEWAY_PREFIX => array()
			);

		if( isset( self::$_instance) && ( count( WC_Add_Fees::instance()->gateways) > 0 ) )
		{
			foreach ( WC_Add_Fees::instance()->gateways as $key => $gateway )
			{
				$option_gateway = array();
				$go = self::get_option_gateway_default( $option_gateway, $gateway->title );
				$default[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ] = $go;
			}
		}

		$options = get_option( self::OPTIONNAME, array() );

		$go = array();
		if( isset( $options[self::OPT_GATEWAY_PREFIX] ) )
		{
			$go = $options[self::OPT_GATEWAY_PREFIX];
		}
		
		$new_go = array();
		foreach ( $default[self::OPT_GATEWAY_PREFIX] as $gateway_key => $value ) 
		{
			$new_go[$gateway_key] = isset( $go[ $gateway_key ] ) ? wp_parse_args( $go[ $gateway_key ], $value ) : $value;
		}
		foreach ( $go as $gateway_key => $value ) 
		{
			if( ! isset( $new_go[ $gateway_key ] ) )
			{
				$new_go[ $gateway_key ] = $value;
			}
		}
		
		$new_options = wp_parse_args( $options, $default );
		$new_options[self::OPT_GATEWAY_PREFIX] = $new_go;

		$old_opt = serialize( $options );
		$new_opt = serialize( $new_options );

		if(version_compare( $new_options[self::OPT_VERSION], self::VERSION, '!=' ) || ( $old_opt != $new_opt ) )
		{
			$new_options[self::OPT_VERSION] = self::VERSION;
			update_option( WC_Add_Fees::OPTIONNAME, $new_options );
		}

		return $new_options;
	}
	



	/**
	 * Gets the post meta for this product and returns an array filled with all needed values initialised
	 *
	 * @return array
	 */
	static public function &get_post_meta_product_default( $post_id )
	{
		$default = array(
			WC_Add_Fees::OPT_ENABLE_PROD => 'yes',
			WC_Add_Fees::OPT_GATEWAY_PREFIX => array()
			);

		if( isset( self::$_instance ) && ( count( WC_Add_Fees::instance()->gateways ) > 0) )
		{
			foreach ( WC_Add_Fees::instance()->gateways as $key => $gateway )
			{
				$option_gateway = array();
				$go = self::get_option_gateway_default( $option_gateway, $gateway->title, true );
				$default[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ] = $go;
			}
		}

		$pm = get_post_meta( $post_id, self::KEY_POSTMETA_PRODUCT, true );

		$g_pm = array();
		if( isset( $pm[self::OPT_GATEWAY_PREFIX] ) )
		{
			$g_pm = $pm[self::OPT_GATEWAY_PREFIX];
		}

		$new_g_pm = wp_parse_args( $g_pm, $default[self::OPT_GATEWAY_PREFIX] );
		$new_pm = wp_parse_args( $pm, $default );
		$new_pm[self::OPT_GATEWAY_PREFIX] = $new_g_pm;

		$old_opt = serialize( $pm );
		$new_opt = serialize( $new_pm );

		if( $old_opt != $new_opt )
		{
			update_post_meta( $post_id, self::KEY_POSTMETA_PRODUCT, $new_pm );
		}

		return $new_pm;
	}
	
	/**
	 * Gets the post meta for this order and returns an array filled with all needed values initialised
	 * 
	 * OPT_KEY_FEE_ITEMS array:   (order item #) => wc_calc_add_fee
	 *
	 * @return array
	 */
	static public function &get_post_meta_order_default( $post_id )
	{
		$default = array(
			WC_Add_Fees::OPT_ENABLE_RECALC => 'yes',
			WC_Add_Fees::OPT_ENABLE_RECALC_SAVE_ORDER => 'yes',
			WC_Add_Fees::OPT_FIXED_GATEWAY => 'no',
			WC_Add_Fees::OPT_KEY_FEE_ITEMS => array()
			);
		
		$pm = get_post_meta( $post_id, self::KEY_POSTMETA_ORDER, true );
		
		$new_pm = wp_parse_args( $pm, $default );
		
		$old_opt = serialize( $pm );
		$new_opt = serialize( $new_pm );
		
		if( $old_opt != $new_opt )
		{
			update_post_meta( $post_id, self::KEY_POSTMETA_ORDER, $new_pm );
		}

		return $new_pm;
	}

	/**
	 * Returns the initialized option array
	 *
	 * @param array $option_gateway
	 * @param string $gateway_name
	 * @param bool $for_postmeta
	 * @return array
	 */
	static public function &get_option_gateway_default( array $option_gateway, $gateway_name = '', $for_postmeta = false )
	{
		$text = __( 'Additional Fee', WC_Add_Fees::TEXT_DOMAIN );
		if( is_string( $gateway_name ) && ! empty( $gateway_name ) )
		{
			$text = __( 'Fee for ', WC_Add_Fees::TEXT_DOMAIN ) . $gateway_name;
		}
		$text .= ':';

		$enable = ( $for_postmeta ) ?  'no' : false;
		$default = array(
			WC_Add_Fees::OPT_KEY_ENABLE => $enable,
			WC_Add_Fees::OPT_KEY_OUTPUT => $text,
			WC_Add_Fees::OPT_KEY_TAXCLASS => WC_Add_Fees::VAL_TAX_STANDARD,
			WC_Add_Fees::OPT_KEY_ADD_VALUE_TYPE => WC_Add_Fees::VAL_ADD_PERCENT,
			WC_Add_Fees::OPT_KEY_VALUE_TO_ADD => 0,
			WC_Add_Fees::OPT_KEY_VALUE_TO_ADD_FIXED => 0,
			WC_Add_Fees::OPT_KEY_MAX_VALUE => 0
			);

		$new_options = shortcode_atts( $default, $option_gateway );
		return $new_options;
	}

	/**
	 * Called before starting calculating fees. All additional fees for products are added to additional fees of cart
	 *
	 * @param WC_Cart $obj_wc_cart
	 */
	public function handler_wc_cart_calculate_fees( WC_Cart $obj_wc_cart )
	{
		//	ignore cart
		if ( ! is_checkout() && ! defined( 'WOOCOMMERCE_CHECKOUT' ) )
		{
			return;
		}

		//	skip, if all disabled
		if( ! $this->options[self::OPT_ENABLE_ALL] )
		{
			return;
		}
		
		if( ! $this->request_data_loaded )	
		{
			$this->load_request_data();
		}

			//	loop through each product and add fee for each item in cart - takes care of cupons
		if ( sizeof( $obj_wc_cart->cart_contents ) > 0 )
		{
			foreach ( $obj_wc_cart->cart_contents as $cart_item_key => $values )
			{
				$_product = $values['data'];
				if( ! ( $_product instanceof WC_Product) ) 
				{
					continue;
				}
				
				$total_excl = $values['line_total'];
				$tax = $values['line_tax'];
				$total_incl = $total_excl + $tax;
				
				$cart_tax = ( version_compare ( WC()->version, '2.3', '>=' ) ) ? new WC_Tax() : $obj_wc_cart->tax;
				$fees_calc = $this->calculate_gateway_fee_product( $_product, $cart_tax, $obj_wc_cart->prices_include_tax, $total_excl, $total_incl, $values['quantity'] );

				if( ! empty( $fees_calc ) )
				{
					$this->add_fee_to_cart( $fees_calc, $obj_wc_cart );
				}	
			}
		}
		
	}


	/**
	 * Called before calculating final totals. As we need the complete calculated values of
	 * the cart we have to alter the tax values.
	 *
	 * previous @param WC_Cart $obj_wc_cart
	 * now @param float $total 
	 * @return float
	 */
	public function handler_wc_calculate_totals( $total )
	{
		//	ignore cart
		if ( ! is_checkout() && ! defined( 'WOOCOMMERCE_CHECKOUT' ) )
		{
			return $total;
		}

		//	skip, if all disabled
		if( ! $this->options[self::OPT_ENABLE_ALL] )
		{
			return $total;
		}

		$obj_wc_cart = WC()->cart;

		if( ! $this->request_data_loaded)	
		{
			$this->load_request_data();
		}
		
		// Grand Total as calculated by WC - other plugins may change total value at this point:
		// 
		//	Discounted product prices, discounted tax, shipping cost + tax, and any discounts to be added after tax (e.g. store credit)
		
		$cart_discount_total = version_compare( WC()->version, '2.3', '>=') ? 0 : $obj_wc_cart->discount_total;
		$total_incl_tax = max( 0, round( $obj_wc_cart->cart_contents_total + $obj_wc_cart->tax_total + $obj_wc_cart->shipping_tax_total + $obj_wc_cart->shipping_total - $cart_discount_total + $obj_wc_cart->fee_total, $obj_wc_cart->dp ) );

		//	tax_total includes tax of fees but not shipping tax, therefore add it
		$total_tax = round( $obj_wc_cart->tax_total + $obj_wc_cart->shipping_tax_total, $obj_wc_cart->dp );
		$total_excl_tax = round( $total_incl_tax - $total_tax, $obj_wc_cart->dp );
		
		$cart_tax = ( version_compare ( WC()->version, '2.3', '>=' ) ) ? new WC_Tax() : $obj_wc_cart->tax;
		$fee_total = $this->calculate_gateway_fee_total( $cart_tax, $obj_wc_cart->prices_include_tax, $total_excl_tax, $total_incl_tax );
		if( ! isset( $fee_total ) )
		{
			return $total;
		}
		
		$this->add_fee_to_cart( $fee_total, $obj_wc_cart );
		
		$obj_wc_cart->fee_total += $fee_total->amount_no_tax;
		$fee_sum_tax = 0.0;
		
		if( $fee_total->taxable )
		{
			if( isset( $fee_total->tax_amount ) )
			{
				$obj_wc_cart->tax_total += $fee_total->tax_amount;
				$fee_sum_tax += $fee_total->tax_amount;
			}

			$taxes = isset( $fee_total->taxes) ? $fee_total->taxes : array();

					// Tax rows - merge the totals we just got
			foreach ( array_keys( $obj_wc_cart->taxes + $taxes ) as $key ) 
			{
				$obj_wc_cart->taxes[ $key ] = ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 ) + ( isset( $obj_wc_cart->taxes[ $key ] ) ? $obj_wc_cart->taxes[ $key ] : 0 );
			}
		}
		
		$total += $fee_total->amount_no_tax + $fee_sum_tax;
		
		return $total;	
	}
	
	/**
	 * Called before pay for order form is created.
	 * 
	 * Fixes bug from WC Core, that payment gateway is set to default gateway and not to order gateway (by js code)
	 * Saves order ID to allow recalculating of fees when payment gateway changes via ajax
	 * 
	 */
	public function handler_wc_before_pay()
	{
		global $wp;
		
		if( empty( $wp->query_vars['order-pay'] ) ) 
		{
			return;
		}
		
		//	ignore cart
		if ( ! is_checkout() && ! defined( 'WOOCOMMERCE_CHECKOUT' ) )
		{
			return;
		}

		//	skip, if all disabled
		if( ! $this->options[self::OPT_ENABLE_ALL] )
		{
			return;
		}

		if( ! $this->request_data_loaded)	
		{
			$this->load_request_data();
		}
				
		$order_id = absint( $wp->query_vars['order-pay'] );
		
		// Pay for existing order only
		if ( ! ( isset( $_REQUEST['pay_for_order'] ) && isset( $_REQUEST['key'] ) && $order_id ) ) 
		{
			return;
		}
		
		$order = new WC_Order( $order_id );
		
		$payment_method = ! empty( $order->payment_method ) ? $order->payment_method : $this->default_payment_gateway_key;
		$pm = WC_Add_Fees::get_post_meta_order_default( $order_id );
		
		$pay_for_order = $_REQUEST['pay_for_order'];
		$key = $_REQUEST['key'];
		
		$info = 'id="add_fee_info_pay" ';
		$info .= 'add_fee_action="add_fee_calc_fee_pay_order" ';
		$info .= 'add_fee_order="' . $order_id . '" ';
		$info .= 'add_fee_pay="' . $pay_for_order . '" ';
		$info .= 'add_fee_paymethod="' . $payment_method . '" ';
		$info .= 'add_fee_key="' . $key . '" ';
		$info .= 'add_fee_fixed_gateway="' . $pm[WC_Add_Fees::OPT_FIXED_GATEWAY] . '" ';
		
		echo '<div ';
			echo $info;
		echo ' style="display: none;">';
		echo '</div>';
		return;
	}
	
	/**
	 * Called, when an existing order is updated from cart. All items are deleted and
	 * later refilled. Therefore any reference to our fees must be removed and will be
	 * restored later.
	 * 
	 * @param int $order_id
	 */
	public function handler_wc_resume_order( $order_id )
	{	
		delete_post_meta( $order_id, self::KEY_POSTMETA_ORDER );
	}
	
	/**
	 * Saves the type of fee for each fee in a post meta to be able to recognize
	 * fees from our plugin from other fees on recalculation of fees in the order 
	 * initiated from the admin page or from the 'pay for order' page.
	 * (e.g. manually added fees on admin page, ..) 
	 * 
	 * @param int $order_id
	 * @param int $item_id
	 * @param stdClass $fee
	 * @param int $fee_key
	 */
	public function handler_wc_add_order_fee_meta( $order_id, $item_id, $fee, $fee_key )
	{
		//	only handle our fees
		if( empty( $fee->data_source) ) 
		{
			return;
		}
		
		if( ! $fee->data_source instanceof WC_Fee_Add_Fees ) 
		{
			return;
		}
		
		if( $fee->data_source->source != self::OPTIONNAME ) 
		{
			return;
		}
				
		$pm = self::get_post_meta_order_default( $order_id );
		
		$pm[self::OPT_KEY_FEE_ITEMS][ $item_id ] = $fee->data_source;
		update_post_meta( $order_id, self::KEY_POSTMETA_ORDER, $pm );
	}

	/**
	 * Called from the backend order page. 
	 * 
	 * If item is in our fee list, remove it
	 */
	public function handler_ajax_wc_remove_order_item()
	{
		global $wpdb;
		
		check_ajax_referer( 'order-item', 'security' );

		if( ! isset( $_POST['order_item_ids'] ) ) 
		{
			return;
		}
		
		$order_item_ids = $_POST['order_item_ids'];
		
		if( ! is_array( $order_item_ids) ) 
		{
			$order_item_ids = array( $order_item_ids );
		}
		
		if ( sizeof( $order_item_ids ) == 0 ) 
		{
			return;
		}
		
		foreach( $order_item_ids as $item_id ) 
		{
				//	to make sure, that all fees are deleted from our post meta data, even if several orders ( should not be)
			$order_id = $wpdb->get_var( $wpdb->prepare( 
					"SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items 
					WHERE order_item_id = %d", $item_id 
				) );
							
			if( is_null( $order_id) ) 
			{
				continue;
			}

			$pm = self::get_post_meta_order_default( $order_id );
		
			if( isset( $pm[self::OPT_KEY_FEE_ITEMS][ $item_id ] ) )
			{
				unset( $pm[self::OPT_KEY_FEE_ITEMS][ $item_id ] );
				update_post_meta( $order_id, self::KEY_POSTMETA_ORDER, $pm );
			}
		}
	}

	/**
	 * Called from pay for order page, recalculates the fees for the order, updates the order and reloads
	 * the new order data
	 * 
	 */
	public function handler_ajax_calc_fee_pay_order()
	{
		check_ajax_referer( self::AJAX_NONCE, self::AJAX_NONCE );
		
			// response output
		header( "Content-Type: application/json" );
		$response = array( self::AJAX_NONCE => wp_create_nonce( self::AJAX_NONCE ) );
		
		$response ['alert'] = __( 'An error occured in calculation of additional fees for your selected payment gateway. Kindly contact us to recheck your invoice or try to change the payment gateway. ', self::TEXT_DOMAIN );
		$response ['recalc'] = true;
		
		$error_div = '<div id="addfeeerror" style="color: red; font-size: 3em; line-height: 1.2;">';
		
		$order_id = isset( $_REQUEST['add_fee_order'] ) ? absint( $_REQUEST['add_fee_order'] ) : 0;
		$pay_for_order = $_REQUEST[ 'add_fee_pay' ];
		$order_key = $_REQUEST[ 'add_fee_key' ];
		$add_fee_new_paymethod = $_REQUEST[ 'add_fee_paymethod' ];		
		
			// Check for handle payment
		if ( ! ( isset( $_REQUEST['add_fee_pay'] ) && isset( $_REQUEST['add_fee_key'] ) && isset ( $_REQUEST[ 'add_fee_paymethod' ] ) && $order_id ) ) 
		{
			$response ['success'] = false;
			$response ['message'] = $error_div. '<div class="woocommerce-error">' . __( 'Invalid pay order parameters. ', self::TEXT_DOMAIN ) . ' <a href="' . get_permalink( wc_get_page_id( 'myaccount' ) ) . '" class="wc-forward">' . __( 'My Account', 'woocommerce' ) . '</a>' . '</div>' . '</div>';
			echo json_encode( $response );
			exit;
		}
		
		//	skip, if all disabled globally
		if( ! $this->options[self::OPT_ENABLE_ALL] )
		{
			$response ['success'] = true;
			$response ['recalc'] = false;
			echo json_encode( $response );
			exit;
		}
		
		$pm = self::get_post_meta_order_default( $order_id );
		if( $pm[WC_Add_Fees::OPT_ENABLE_RECALC] != 'yes' )
		{
			$response ['success'] = true;
			$response ['recalc'] = false;
			echo json_encode( $response );
			exit;
		}
		
		$order = new WC_Order_Add_Fees( $order_id );
		if( ! $this->request_data_loaded)	
		{
			$this->load_request_data( $add_fee_new_paymethod );
		}
		
		if( ! isset( $this->gateways[$this->payment_gateway_key] ) )
		{
			$response ['success'] = false;
			$response ['message'] = $error_div. '<div class="woocommerce-error">' . __( 'Invalid payment gateway selected - it is no longer available. ', self::TEXT_DOMAIN ) . ' <a href="' . get_permalink( wc_get_page_id( 'myaccount' ) ) . '" class="wc-forward">' . __( 'My Account', 'woocommerce' ) . '</a>' . '</div>' . '</div>';
			echo json_encode( $response );
			exit;
		}
			
		// Pay for existing order		
		if(version_compare(WC()->version, '2.2.0', '<' ) )
		{
			$valid_order_statuses = apply_filters( 'woocommerce_valid_order_statuses_for_payment', array( 'pending', 'failed' ), $order );
		}
		else
		{
			$valid_order_statuses = apply_filters( 'woocommerce_valid_order_statuses_for_payment', array( 'wc-pending', 'wc-failed' ), $order );
		}
		
		if ( ! current_user_can( 'pay_for_order', $order_id ) ) 
		{
			$response ['success'] = false;
			$response ['message'] = $error_div. '<div class="woocommerce-error">' . __( 'Invalid order. ', 'woocommerce' ) . ' <a href="' . get_permalink( wc_get_page_id( 'myaccount' ) ) . '" class="wc-forward">' . __( 'My Account', 'woocommerce' ) . '</a>' . '</div>' . '</div>';
			echo json_encode( $response );
			exit;
		}
		
		//	output order using WC default template checkout/form-pay.php
		ob_start();
		$template_loaded = true;
		
		if ( $order->id == $order_id ) 
		{
			$order_status = ( version_compare(WC()->version, '2.2.0', '<' ) ) ? $order->status : $order->post_status;
			if ( in_array( $order_status, $valid_order_statuses ) ) 
			{
				// Set customer location to order location
				if ( $order->billing_country )
				{
					WC()->customer->set_country( $order->billing_country );
				}
				if ( $order->billing_state )
				{
					WC()->customer->set_state( $order->billing_state );
				}
				if ( $order->billing_postcode )
				{
					WC()->customer->set_postcode( $order->billing_postcode );
				}

				//	maipulate order recalculating fee based on new payment gateway
				$this->calculate_gateway_fees_order( $order_id, $order );
				
				wc_get_template( 'checkout/form-pay.php', array( 'order' => $order ) );
			} 
			else 
			{
				$template_loaded = false;
				if(version_compare(WC()->version, '2.2.0', '<' ) )
				{		
					$status = get_term_by( 'slug', $order->post_status, 'shop_order_status' );
					$status = $status->name;
				}
				else
				{
					$status_defined = wc_get_order_statuses();
					$status = isset( $status_defined[$order->post_status] ) ? $status_defined[$order->post_status] : $order->post_status;
				}
				wc_add_notice( sprintf( __( 'This order&rsquo;s status is &ldquo;%s&rdquo;&mdash;it cannot be paid for. Please contact us if you need assistance. ', 'woocommerce' ), $status ), 'error' );
			}
		} 
		else 
		{
			$template_loaded = false;
			wc_add_notice( __( 'Sorry, this order is invalid and cannot be paid for. ', 'woocommerce' ), 'error' );
		}
		
		if( ! $template_loaded )
		{
			wc_print_notices();
		}
		
		$buffer = ob_get_contents();
		ob_end_clean();
		
		//	remove parts of template not needed
		if( $template_loaded )
		{
			$buffer = $this->extract_order_template( $buffer );
		}
		else
		{
			$buffer = $error_div . $buffer . '</div>';
		}
		
		$response ['success'] = $template_loaded;
		$response ['message'] = $buffer;
		
		echo json_encode( $response );
		exit;
	}

	/**
	 * Calculates the fee for a given value. Takes care of tax calculation.
	 *
	 * @param WC_Tax $obj_wc_tax
	 * @param bool $includes_tax 
	 * @param float $value
	 * @param array $gateway
	 * @param int $quantity
	 * @param array $tax_rates_base  added with 2.2 for recalculating orders
	 * @return WC_Fee_Add_Fees
	 */
	protected function &calculate_fees( WC_Tax $obj_wc_tax, $includes_tax, $value, array $gateway, $quantity = 1, $tax_rates_base = array() )
	{
			//	get tax rates
		$taxclass = ( $gateway[self::OPT_KEY_TAXCLASS] == self::VAL_TAX_STANDARD) ? '' : $gateway[self::OPT_KEY_TAXCLASS];
//		$tax_rates = $obj_wc_tax->get_rates( $taxclass );
		
		if( empty( $tax_rates_base ) )
		{
			$tax_rates = WC_Tax::get_rates( $taxclass );
		}
		else
		{
			$tax_rates_base['tax_class'] = $taxclass;
			$tax_rates = WC_Tax::find_rates( $tax_rates_base );
		}
		
		$no_tax = false;
		if( ( $gateway[self::OPT_KEY_TAXCLASS] == self::VAL_TAX_NONE) || WC()->customer->is_vat_exempt() || (get_option( 'woocommerce_calc_taxes' ) == 'no' ) )
		{
			$no_tax = true;
		}

		$tax_included = true;

		$add_fee = (float) $gateway[self::OPT_KEY_VALUE_TO_ADD];
		$add_fee_fixed = (float) $gateway[self::OPT_KEY_VALUE_TO_ADD_FIXED];
		
		$add_fee_fixed_no_tax = $add_fee_fixed;
		$add_fee_fixed_tax = $add_fee_fixed;
		$tax_amount_fixed = 0.0;
		$taxes_add_fixed = array();
		
				//	calculate $add_fee_fixed amount according to setting: Prices entered w/o tax
		if( $add_fee_fixed > 0.0 )
		{
//			$taxes_add_fixed = $obj_wc_tax->calc_tax( $add_fee_fixed, $tax_rates, $includes_tax );
//			$tax_amount_fixed = $obj_wc_tax->get_tax_total( $taxes_add_fixed );
			$taxes_add_fixed = WC_Tax::calc_tax( $add_fee_fixed, $tax_rates, $includes_tax );
			$tax_amount_fixed = WC_Tax::get_tax_total( $taxes_add_fixed );
			
			if( ! $this->round_at_subtotal )
			{
				$tax_amount_fixed = round( $tax_amount_fixed, $this->dp );
			}
			
			if( $includes_tax )
			{
				$add_fee_fixed_no_tax = $add_fee_fixed - $tax_amount_fixed;
			}
			else 
			{
				$add_fee_fixed_no_tax = $add_fee_fixed;
				
			}
				//	reset tax amount to our custom settings
			if ( $no_tax )
			{
				$tax_amount_fixed = 0.0;
			}
			$add_fee_fixed_tax = $add_fee_fixed_no_tax + $tax_amount_fixed;
		}
		else
		{
			$add_fee_fixed_no_tax = $add_fee_fixed_tax = $tax_amount_fixed = 0.0;
		}
		
		switch ( $gateway[self::OPT_KEY_ADD_VALUE_TYPE] )
		{
			case self::VAL_FIXED:
				$add_fee *= $quantity;
				$tax_included = $includes_tax;
				break;
			case self::VAL_INCLUDE_PERCENT:
				if( ! $no_tax )
				{			//	include tax in percents to add
//					$add_fee_taxs = $obj_wc_tax->calc_tax( $add_fee, $tax_rates, false );
//					$add_fee += $obj_wc_tax->get_tax_total( $add_fee_taxs );
					$add_fee_taxs = WC_Tax::calc_tax( $add_fee, $tax_rates, false );
					$add_fee += WC_Tax::get_tax_total( $add_fee_taxs );
				}
				$add_fee = ( ( $value * 100.0) / (100.0 - $add_fee) ) - $value;
				$tax_included = false;
				break;
			case self::VAL_ADD_PERCENT:
				$add_fee = ( $value * $add_fee ) / 100.0;
				$tax_included = false;
				break;
			default:
				$add_fee = 0.0;
				break;
		}
		$add_fee = round( $add_fee, $this->dp );

			//	calculate tax amount - for saving taxes object (rounding depends on $this->round_at_subtotal)
//		$taxes = $obj_wc_tax->calc_tax( $add_fee, $tax_rates, $tax_included );
		$taxes = WC_Tax::calc_tax( $add_fee, $tax_rates, $tax_included );
//		$tax_amount = $obj_wc_tax->get_tax_total( $taxes );
		$tax_amount = WC_Tax::get_tax_total( $taxes );

			//	reset tax amount to our custom settings
		if ( $no_tax )
		{
			$tax_amount = 0.0;
		}
		
		if( ! $this->round_at_subtotal )
		{
			$tax_amount = round( $tax_amount, $this->dp );
		}

			//	calculate add_fee with and without tax
		switch ( $gateway[self::OPT_KEY_ADD_VALUE_TYPE] )
		{
			case self::VAL_FIXED:
			case self::VAL_ADD_PERCENT:
			case self::VAL_INCLUDE_PERCENT:
				if( $tax_included )
				{
					$fee_tax = $add_fee + $add_fee_fixed_tax;
					$fee_no_tax = $fee_tax - $tax_amount - $tax_amount_fixed;
				}
				else
				{
					$fee_no_tax = $add_fee + $add_fee_fixed_no_tax;
					$fee_tax = $fee_no_tax + $tax_amount + $tax_amount_fixed;
				}
				$tax_amount += $tax_amount_fixed;
				
					// Tax rows - merge the totals we just got
				foreach ( array_keys( $taxes + $taxes_add_fixed ) as $key ) 
				{
					$taxes[ $key ] = ( isset( $taxes_add_fixed[ $key ] ) ? $taxes_add_fixed[ $key ] : 0 ) + ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 );
				}
				
				break;
//			case self::VAL_INCLUDE_PERCENT:
//				$fee_tax = $add_fee;
//				$fee_no_tax = ( $no_tax) ? $fee_tax : $fee_tax - $tax_amount;
//				break;
			default:
				$tax_amount = $fee_no_tax = $fee_tax = 0.0;
				$taxes = array();
				break;
		}

		$calc_fee = new WC_Fee_Add_Fees();
		$calc_fee->amount_no_tax = $fee_no_tax;
		$calc_fee->amount_incl_tax = $fee_tax;
		$calc_fee->tax_amount = $tax_amount;
		$calc_fee->taxable = ( ! $no_tax );
		$calc_fee->taxes = $taxes;

		return $calc_fee;
	}

	/**
	 * Adds the fee to the cart fee array and also stores the additional information there.
	 * If required, also adds tax and sum values (only on cart checkout)
	 *
	 * @param array $fee
	 * @param WC_Cart $obj_wc_cart
	 */
	protected function add_fee_to_cart( WC_Fee_Add_Fees &$fee, WC_Cart $obj_wc_cart )
	{
			//	add fee
		$name = $fee->gateway_option[WC_Add_Fees::OPT_KEY_OUTPUT];
		$amount = $fee->amount_no_tax;
		$taxable = $fee->taxable;
		$tax_class = $tax_class = $fee->gateway_option[WC_Add_Fees::OPT_KEY_TAXCLASS] == self::VAL_TAX_STANDARD ?  '' : $fee->gateway_option[WC_Add_Fees::OPT_KEY_TAXCLASS];

		$obj_wc_cart->add_fee( $fee->id, $amount, $taxable, $tax_class );
		$fee_cart = &$obj_wc_cart->fees[ count( $obj_wc_cart->fees ) - 1 ];

		if( version_compare( WC()->version, '2.2.0', '>=' ) )
		{
			$fee_cart->tax_data = $fee->taxes;
		}
		$fee_cart->tax = $fee->tax_amount;
		$fee_cart->name = $name;

				//	save source information for a possible chance to display later (maybe in order) to reconstruct calculation
		$fee_cart->data_source = $fee;
	}


	/**
	 * Initialise values that need translation and WooCommerce
	 *
	 */
	public function init_values()
	{
		if( ! isset( self::$value_type_to_add) || empty( self::$value_type_to_add ) )
		{
			self::$value_type_to_add = array(
				self::VAL_FIXED => __( 'Fixed amount', WC_Add_Fees::TEXT_DOMAIN ),
				self::VAL_ADD_PERCENT => __( 'add % to total amount', WC_Add_Fees::TEXT_DOMAIN ),
				self::VAL_INCLUDE_PERCENT => __( 'include % in total amount', WC_Add_Fees::TEXT_DOMAIN )
				);
		}

		if( empty( $this->gateways ) )
		{
			$this->gateways = WC()->payment_gateways->payment_gateways();
					//	set default gateway
			if( isset( $this->gateways[ get_option( 'woocommerce_default_gateway' ) ] ) )
			{
				$default = $this->gateways[ get_option( 'woocommerce_default_gateway' ) ];
			}
			else
			{
				reset( $this->gateways );
				$default = current( $this->gateways );
			}
			
			$this->default_payment_gateway_key = $default->id;
			$this->payment_gateway_key = $this->default_payment_gateway_key;
		}

		if( empty( $this->tax_classes ) )
		{
			$tax_classes = array_filter( array_map( 'trim', explode( "\n", get_option( 'woocommerce_tax_classes' ) ) ) );
			$this->tax_classes = array();

			$this->tax_classes[self::VAL_TAX_NONE] = __( 'No Tax required', WC_Add_Fees::TEXT_DOMAIN );
			$this->tax_classes[self::VAL_TAX_STANDARD] = __( 'Standard', WC_Add_Fees::TEXT_DOMAIN );
			if ( $tax_classes )
			{
				foreach ( $tax_classes as $class )
				{
//					$this->tax_classes[ sanitize_title( $class) ] = $class;
					$this->tax_classes[ $class ] = $class;
				}
			}
		}

		$this->options = self::get_options_default();
		
			//	allow to add other plugins
		$this->gateway_bugfix_array = apply_filters( 'wc_add_fees_bugfix_array', $this->gateway_bugfix_array );
		
		foreach( $this->gateway_bugfix_array as $plugin )
		{
			if ( ! function_exists( 'is_plugin_active' ) ) 
			{
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
			if( is_plugin_active( $plugin ) )
			{
				$this->gateway_bugfix = true;
			}
		}

			//	allow other classes to access new wc data
		do_action( 'woocommerce_additional_fees_init' );
	}
	
	/**
	 * Loads the Request and Session data. If $posted_payment_gateway is set, uses this as gateway else
	 * takes session data gateway ( in cart only ) . Initialises the gateway and
	 * implements a fallback for option array.
	 * 
	 * @param string $posted_payment_gateway
	 */
	public function load_request_data( $posted_payment_gateway = '' )
	{
		$this->init_values();

		if( empty( $posted_payment_gateway ) )
		{
			$posted_payment_gateway = WC()->session->chosen_payment_method;
		}
		
		$available_gateways = array();

		
		//	Bug in WC Gateway COD -> checks for function WC_Gateway_COD->needs_shipping - does not exist in order page and pay-for order
		//	Bug in woocommerce-account-funds: get_available_payment_gateways() produces endless loop due to calculate totals
		//			if statement was removed in 2.1.5
		//			
		//	In version 2.2.2 integrated $this->gateway_bugfix_array allows to filter plugins, that do not support get_available_payment_gateways()
		//	        if statement activated again
		if( isset( WC()->cart ) && ( ! $this->gateway_bugfix ) )
		{
			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		}
		else
		{
					//	take all gateways that are enabled directly
			foreach ( WC()->payment_gateways->payment_gateways as $gateway )
			{
				if( 'yes' === $gateway->enabled )
				{
					$available_gateways[$gateway->id] = $gateway;
				}
			}
		}
		
        if ( ! empty( $available_gateways ) )
        {
            if ( isset( $posted_payment_gateway ) && isset( $available_gateways[ $posted_payment_gateway ] ) )
            {
                $this->payment_gateway_key = $available_gateways[ $posted_payment_gateway ]->id;
            }
            elseif( isset( $available_gateways[ get_option( 'woocommerce_default_gateway' ) ] ) )
            {
                $this->payment_gateway_key = $available_gateways[ get_option( 'woocommerce_default_gateway' ) ]->id;
            }
            else
            {
                $this->payment_gateway_key = current( $available_gateways )->id;
            }
        }

		if( ! empty( $this->payment_gateway_key ) && isset( $this->options[self::OPT_GATEWAY_PREFIX][ $this->payment_gateway_key ] ) )
		{
			$payment_gateway_option = $this->options[self::OPT_GATEWAY_PREFIX][ $this->payment_gateway_key ];
		}
		else
		{
			$payment_gateway_option = array();
		}

		$this->payment_gateway_option = self::get_option_gateway_default( $payment_gateway_option );

		$dif = array_diff( $this->payment_gateway_option, $payment_gateway_option );
		if( ! empty( $dif ) )
		{				//	save option
			$this->options[self::OPT_GATEWAY_PREFIX][ $this->payment_gateway_key ] = $this->payment_gateway_option;
			update_option( self::OPTIONNAME, $this->options );
		}

		$this->request_data_loaded = true;
	}
	
	/**
	 * Calculates the fee for the total
	 * 
	 * @param WC_Tax $obj_wc_tax
	 * @param boolean $includes_tax
	 * @param float $total_excl
	 * @param float $total_incl
	 * @param array $tax_rates_base   added with 2.2 for recalculation of order
	 * @return WC_Fee_Add_Fees
	 */
	protected function &calculate_gateway_fee_total ( WC_Tax &$obj_wc_tax, $includes_tax, $total_excl, $total_incl, $tax_rates_base = array() )
	{
		$fees_calc = null;
		
		//	if add fees for gateway is disabled
		if( !$this->payment_gateway_option[self::OPT_KEY_ENABLE] )
		{
			return $fees_calc;
		}
		
		$maxval = 0.0;
		if( isset( $this->payment_gateway_option[self::OPT_KEY_MAX_VALUE] ) )
		{
			$maxval = $this->payment_gateway_option[self::OPT_KEY_MAX_VALUE];
		}

		if( ! is_numeric( $maxval) )
		{
			$maxval = 0.0;
		}
		else
		{
			$maxval = (float) $maxval;
		}

		if( ! empty( $maxval) )
		{
			$check_total = ( $includes_tax) ? $total_incl : $total_excl;
			
			if( $check_total >= $maxval )
			{
				return $fees_calc;
			}
		}

		//changed with 2.1.0 - replaced $total_excl with $total_incl
		$fees_calc = $this->calculate_fees( $obj_wc_tax, $includes_tax, $total_incl, $this->payment_gateway_option, 1, $tax_rates_base );
		
		$fees_calc->id = substr( ( 'ADD_FEE_TOTAL' ), 0, 15 );		
		$fees_calc->source = self::OPTIONNAME;
		$fees_calc->type = WC_Fee_Add_Fees::VAL_TOTAL_CART_ADD_FEE;
		$fees_calc->gateway_key = $this->payment_gateway_key;
		$fees_calc->gateway_title = $this->gateways[ $this->payment_gateway_key ]->title;
		$fees_calc->gateway_option = $this->payment_gateway_option;
		
		return $fees_calc;
	}

	/**
	 * Recalculates the fees for a stored order based on the payment gateway set in local payment gateway members
	 * 
	 * @param int $order_id
	 * @param WC_Order $order
	 * @param boolean $ignore_recalc_option 
	 * @return boolean true, if recalculation was done
	 */
	public function calculate_gateway_fees_order( $order_id, WC_Order_Add_Fees &$order, $ignore_recalc_option = false )
	{
		if( version_compare( WC()->version, '2.2.0', '<' ) )
		{
			return $this->calculate_gateway_fees_order_V21( $order_id, $order, $ignore_recalc_option );
		}
		
		$pm = self::get_post_meta_order_default( $order_id );
		
		if( !$ignore_recalc_option )
		{
			if ( $pm[ WC_Add_Fees::OPT_ENABLE_RECALC ] != 'yes' ) 
			{
				return false;
			}
		}
		
		$fees = $pm[self::OPT_KEY_FEE_ITEMS];
		
		/**
		 * Since 2.2 refunds are possible. Since we delete the fee lines for recalc we loose the context to manuall added refund(s) .
		 * WC does not support 'pay for order' with refunds -> so we can skip recalc without problem.
		 * On order page we have to disable checkbox and give the admin a warning in the box
		 */
		if( version_compare( WC()->version, '2.2.0', '>=' ) )
		{
			$total = 0;
			foreach ( $fees as $item_key => $fee ) 
			{
				if( $fee->source == self::OPTIONNAME)
				{
					$total += $order->get_total_refunded_for_item( $item_key, 'fee' );
				}
			}
			
			if( $total > 0 )
			{
				return false;
			}
		}
		
		//	remove all fee entries from our plugin from order and from post meta and save
		foreach ( $fees as $item_key => $fee ) 
		{
			if( $fee->source == self::OPTIONNAME)
			{
				wc_delete_order_item( absint( $item_key ) );
				wc_delete_order_item_meta( $item_key, '_tax_class' );
				wc_delete_order_item_meta( $item_key, '_line_total' );
				wc_delete_order_item_meta( $item_key, '_line_tax' );
				wc_delete_order_item_meta( $item_key, '_line_tax_data' );
				
				unset( $pm[self::OPT_KEY_FEE_ITEMS][ $item_key ] );
			}
		}
		
		update_post_meta( $order_id, self::KEY_POSTMETA_ORDER, $pm );
		
		$items = $order->get_items();
		$tax = new WC_Tax();
		
		$taxes              = array();
		$tax_based_on       = get_option( 'woocommerce_tax_based_on' );

		if ( 'base' === $tax_based_on ) {

			$default  = get_option( 'woocommerce_default_country' );
			$postcode = '';
			$city     = '';

			if ( strstr( $default, ':' ) ) {
				list( $country, $state ) = explode( ':', $default );
			} else {
				$country = $default;
				$state   = '';
			}

		} elseif ( 'billing' === $tax_based_on ) {

			$country 	= $order->billing_country;
			$state 		= $order->billing_state;
			$postcode   = $order->billing_postcode;
			$city   	= $order->billing_city;

		} else {

			$country 	= $order->shipping_country;
			$state 		= $order->shipping_state;
			$postcode   = $order->shipping_postcode;
			$city   	= $order->shipping_city;

		}
		
		$tax_rates_base = array(
					'country'   => $country,
					'state'     => $state,
					'postcode'  => $postcode,
					'city'      => $city,
				);
		
		
		$new_fees = array();		//	save new fees temporarily to insert all fees later at once
		
		if ( sizeof( $items ) > 0  && $this->options[self::OPT_ENABLE_PROD_FEES] )
		{
			foreach ( $items as $item_key => $item ) 
			{
				$_product = $order->get_product_from_item( $item );
				if( ! $_product)	
				{	
					continue;
				}
				
				$total_excl = $item['line_total'];
				$total_incl = $item['line_total'] + $item['line_tax'];
				
				$fees_calc = $this->calculate_gateway_fee_product( $_product, $tax, $order->prices_include_tax, $total_excl, $total_incl, $item['qty'], $tax_rates_base );
				
				if( ! empty( $fees_calc) )
				{				
					$fees_calc->order_item_id[] = $item_key;
					$new_fees[] = $fees_calc;		
				}
			}
		}
		
		$cart = new WC_Cart();
		
		if(count( $new_fees ) > 0)
		{
			foreach ( $new_fees as &$fee ) 
			{
				$this->add_fee_to_cart( $fee, $cart );
			}
		
			$wc_fees = $cart->get_fees();
			$order->add_new_fees( $wc_fees );
			$new_fees = array();
		}
		
		$order->recalc_totals();
		
		$order_total = $order->get_total();
		$order_no_tax = $order_total - $order->get_total_tax();
		
		$fees_calc = $this->calculate_gateway_fee_total( $tax, $order->prices_include_tax, $order_no_tax, $order_total, $tax_rates_base );
		if( ! empty( $fees_calc ) )
		{				
			$new_fees[] = $fees_calc;
		}
		
		$cart = new WC_Cart();
		
		if(count( $new_fees ) > 0)
		{
			foreach ( $new_fees as &$fee ) 
			{
				$this->add_fee_to_cart( $fee, $cart );
			}
		
			$wc_fees = $cart->get_fees();
			$order->add_new_fees( $wc_fees );
		}
		
		$order->update_payment_method( $this->payment_gateway_key, $this->gateways[ $this->payment_gateway_key ]->title );
		$order->recalc_totals();
		
		return true;		
	}
	
	/**
	 * For backwards compatibility only
	 * 
	 */
	public function calculate_gateway_fees_order_V21( $order_id, WC_Order_Add_Fees &$order, $ignore_recalc_option = false )
	{
		global $wpdb;
		
		$pm = self::get_post_meta_order_default( $order_id );
		
		if( !$ignore_recalc_option )
		{
			if ( $pm[ WC_Add_Fees::OPT_ENABLE_RECALC ] != 'yes' ) 
			{
				return false;
			}
		}
		
		$fees = $pm[self::OPT_KEY_FEE_ITEMS];
		
		/**
		 * Since 2.2 refunds are possible. Since we delete the fee lines for recalc we loose the context to manuall added refund(s) .
		 * WC does not support 'pay for order' with refunds -> so we can skip recalc without problem.
		 * On order page we have to disable checkbox and give the admin a warning in the box
		 */
		if( version_compare( WC()->version, '2.2.0', '>=' ) )
		{
			$total = 0;
			foreach ( $fees as $item_key => $fee ) 
			{
				if( $fee->source == self::OPTIONNAME)
				{
					$total += $order->get_total_refunded_for_item( $item_key, 'fee' );
				}
			}
			
			if( $total > 0 )
			{
				return false;
			}
		}
		

		//	get totals to subtract deleted fees and add new fees to be able to calculate total order fee (or not if more than limit)
		$order_taxes = $order->get_tax_totals();
		$order_tax = (float) 0;
		foreach ( $order_taxes as $tax ) 
		{
			$order_tax += $tax->amount;
		}
		
		$order_total = $order->get_total();
		$order_no_tax = $order_total - $order_tax;
		
		//	remove fee entries from order and from post meta and save
		foreach ( $fees as $item_key => $fee ) 
		{
			if( $fee->source == self::OPTIONNAME)
			{
				//	check, if item exists in DB to correctly adjust total values
				$count = $wpdb->get_var( $wpdb->prepare( 
								"SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d", 
								$item_key 
						) );
								
				if( $count > 0 )
				{			
					$order_no_tax -= $fee->amount_no_tax;
					$order_tax -= $fee->tax_amount;
					$order_total -= $fee->amount_incl_tax;
				}
					//	try to delete in any case to clean DB if anything went wrong
				wc_delete_order_item( absint( $item_key ) );
				unset( $pm[self::OPT_KEY_FEE_ITEMS][ $item_key ] );
			}
		}
		
		update_post_meta( $order_id, self::KEY_POSTMETA_ORDER, $pm );
		
		$items = $order->get_items();
		$tax = new WC_Tax();
		$new_fees = array();		//	save new fees temporarily to insert all fees later at once
		
		if ( sizeof( $items ) > 0  && $this->options[self::OPT_ENABLE_PROD_FEES] )
		{
			foreach ( $items as $item_key => $item ) 
			{
				$_product = $order->get_product_from_item( $item );
				if( ! $_product)	
				{	
					continue;
				}
				
				$total_excl = $item['line_total'];
				$total_incl = $item['line_total'] + $item['line_tax'];
				
				$fees_calc = $this->calculate_gateway_fee_product( $_product, $tax, $order->prices_include_tax, $total_excl, $total_incl, $item['qty'] );
				
				if( ! empty( $fees_calc) )
				{				
					$fees_calc->order_item_id[] = $item_key;
					$new_fees[] = $fees_calc;
					$order_no_tax += $fees_calc->amount_no_tax;
					$order_tax += $fees_calc->tax_amount;
					$order_total += $fees_calc->amount_incl_tax;				
				}
			}
		}
		
		$fees_calc = $this->calculate_gateway_fee_total( $tax, $order->prices_include_tax, $order_no_tax, $order_total );
		if( ! empty( $fees_calc ) )
		{				
			$new_fees[] = $fees_calc;
		}
		
		$cart = new WC_Cart();
		
		if(count( $new_fees ) > 0)
		{
			foreach ( $new_fees as &$fee ) 
			{
				$this->add_fee_to_cart( $fee, $cart );
			}
		
			$wc_fees = $cart->get_fees();
			$order->add_new_fees( $wc_fees );
		}
		
		$order->update_payment_method( $this->payment_gateway_key, $this->gateways[ $this->payment_gateway_key ]->title );
		$order->recalc_totals();
		
		return true;
	}
	
		
	/**
	 * 
	 * @param WC_Product $_product
	 * @param WC_Tax $obj_wc_tax
	 * @param boolean $includes_tax
	 * @param float $total_excl
	 * @param float $total_incl
	 * @param int $quantity
	 * @param array $tax_rates_base			added with 2.2 -> for recalculating orders
	 * @return WC_Fee_Add_Fees     null, if no fee to add, else wc_calc_add_fee
	 */
	protected function &calculate_gateway_fee_product( WC_Product $_product, WC_Tax &$obj_wc_tax, $includes_tax, $total_excl, $total_incl, $quantity = 1, $tax_rates_base = array() )
	{
		$fees_calc = null;
		
		$pm_product = self::get_post_meta_product_default( $_product->id );
				
		//remove single product check - option doesn't exist any more
		//if( $pm_product[self::OPT_ENABLE_PROD] != 'yes' ) continue;

		if( ! empty( $this->payment_gateway_key ) && isset( $pm_product[self::OPT_GATEWAY_PREFIX][ $this->payment_gateway_key ] ) )
		{
			$gateway = $pm_product[self::OPT_GATEWAY_PREFIX][ $this->payment_gateway_key ];
		}
		else
		{
			$gateway = array();
		}

		$gateway = self::get_option_gateway_default( $gateway, $this->payment_gateway_key, true );

		if( $gateway[self::OPT_KEY_ENABLE] != 'yes' ) 
		{
			return $fees_calc;
		}

		$maxval = 0.0;
		if( isset( $gateway[self::OPT_KEY_MAX_VALUE] ) )
		{
			$maxval = $gateway[self::OPT_KEY_MAX_VALUE];
		}

		if( ! is_numeric( $maxval) )
		{
			$maxval = 0.0;
		}
		else
		{
			$maxval = (float) $maxval;
		}

		if( ! empty( $maxval ) )
		{
			$check_total = ( $includes_tax) ? $total_incl : $total_excl;

			if( $check_total >= $maxval)
			{
				return $fees_calc;
			}
		}

		//changed with 2.1.0 - replaced $total_excl with $total_incl
		$fees_calc = $this->calculate_fees( $obj_wc_tax, $includes_tax, $total_incl, $gateway, $quantity, $tax_rates_base );

		$fees_calc->source = self::OPTIONNAME;
		$fees_calc->type = WC_Fee_Add_Fees::VAL_PRODUCT_ADD_FEE;
		$fees_calc->id = substr(( 'ADD_FEE' . $this->prod_fee_cnt . '_' . $_product->id ), 0, 15 );
		$this->prod_fee_cnt ++;
		$fees_calc->gateway_key = $this->payment_gateway_key;
		$fees_calc->gateway_title = $this->gateways[ $this->payment_gateway_key ]->title;
		$fees_calc->gateway_option = $gateway;
		$fees_calc->product_desc = $_product->get_title();

		return $fees_calc;
	}

	/**
	 * Extracts the portion <table>....</table> from the template to be able to replace it on the
	 * pay for order page with the revised content
	 * 
	 * @param string $buffer
	 * @return string
	 */
	protected function extract_order_template( $buffer )
	{
		$start = stripos( $buffer, '<table' );
		
		if ( $start === false) 
		{
			return $buffer;
		}
		
		$new_buffer = substr( $buffer, $start );
		
		$end = stripos( $new_buffer, '</table>' );
		
		if ( $end === false )
		{
			$ret = $new_buffer;
		}
		else
		{
			$ret = substr( $new_buffer, 0, $end );
		}
		
		$ret .= '</table>';
		
		return $ret;
	}

}


