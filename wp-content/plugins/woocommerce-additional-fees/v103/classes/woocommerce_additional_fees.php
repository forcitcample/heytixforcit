<?php
/**
 * Description of woocommerce_additional_fees
 *
 * Call init_values before using any members and properties of this class to connect to woocommerce data !!!
 *
 * Changings in class-wc-cart.php
 * =============================
 * 1467 $tax_rates = $this->tax->get_rates($fee->tax_class);
 *
 * Tax Rate 'Reduced Rate' is not recognized (is SQL queried by 'reduced-rate'
 *
 * ----------------------------------------------------------------------
 *
 *
 * @author Schoenmann Guenter
 * @version 1.0.0.0
 */
class woocommerce_additional_fees
{
	const VERSION = '1.0.0';
	const TEXT_DOMAIN = 'woocommerce_additional_fees';

	const OPTIONNAME = 'woocommerce_additional_fees';
	const KEY_POSTMETA_PRODUCT = '_woocommerce_add_fees_product';

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
	const OPT_KEY_MAX_VALUE = 'maxvalue';
	const OPT_KEY_OUTPUT = 'outputtext';

	const VAL_FIXED = 'fixed_value';
	const VAL_ADD_PERCENT = 'add_percent';
	const VAL_INCLUDE_PERCENT = 'include_percent';

	const VAL_TAX_NONE = 'tax_none';
	const VAL_TAX_STANDARD = 'Standard';		//	woocommerce default



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


	public function __construct()
	{
		if(!isset(self::$show_activation))
		{
			self::$show_activation = true;
		}

		if(!isset(self::$show_uninstall))
		{
			self::$show_uninstall = true;
		}

		$this->options = self::get_options_default();

		$this->payment_gateway_key = '';
		$this->payment_gateway_option = array();
		$this->tax_classes = array();
		$this->gateways = array();
		self::$value_type_to_add = array();
		$this->request_data_loaded = false;

		add_action('init', array(&$this, 'handler_wp_load_textdomains'));
		add_action('init', array(&$this, 'handler_wp_register_scripts'));
		add_action('wp_print_styles', array(&$this, 'handler_wp_print_styles'));
		add_action('woocommerce_init', array(&$this, 'handler_wc_init'));

		if($this->options[self::OPT_ENABLE_ALL])
			$this->attach_to_hooks();
	}

	public function __destruct()
	{
		unset($this->options);
		unset($this->payment_gateway_key);
		unset($this->payment_gateway_option);
		unset($this->tax_classes);
		unset($this->gateways);

	}

	/**
	 * Localisation
	 **/
	public function handler_wp_load_textdomains()
	{
		$language_path = self::$plugin_path.'languages'.DIRECTORY_SEPARATOR;
		load_plugin_textdomain(self::TEXT_DOMAIN, false, $language_path);
	}

	/**
	 *
	 */
	public function handler_wp_register_scripts()
	{
		wp_register_script('wc_additional_fees_script', self::$plugin_url . 'v103/js/wc_additional_fees.js', array('woocommerce'));
	}

	/**
	 *
	 */
	public function handler_wp_print_styles()
	{
		wp_enqueue_script('wc_additional_fees_script');
	}

	/**
	 * Attach objects to WooCommerce Data
	 */
	public function handler_wc_init()
	{
		$this->init_values();
	}


	/**
	 * Attach class to hooks
	 */
	protected function attach_to_hooks()
	{
		/**
		 *  Needed to properly set selected payment gateway radiobox on form-pay page for the order
		 * (wc-core only selects default gateway) - added due to backward compatibility
		 * 
		 * includes/shortcodes/class-WC-Shortcode-Checkout
		 * do_action( 'before_woocommerce_pay' );
		 */
		add_action('before_woocommerce_pay', array($this, 'handler_wc_before_pay'), 10);
		
		/**
		 * Attach to add fees applied to single products
		 *
		 * classes/class-wc-cart
		 * do_action( 'woocommerce_before_calculate_totals', $this );
		 */
		add_action('woocommerce_before_calculate_totals', array(&$this, 'handler_wc_before_calculate_totals'), 10, 1);

		/**
		 * Attach to add fees applied to total cart
		 *
		 * classes/class-wc-cart
		 * do_action( 'woocommerce_calculate_totals', $this );
		 */
		add_action('woocommerce_calculate_totals', array(&$this, 'handler_wc_calculate_totals'), 500, 1);
	}

	/**
	 * Gets the options for this plugin and returns an array filled with all needed values initialised
	 *
	 * @return array
	 */
	static public function &get_options_default()
	{
		global $woocommerce_additional_fees;

		$default = array(
			woocommerce_additional_fees::OPT_VERSION => woocommerce_additional_fees::VERSION,
			woocommerce_additional_fees::OPT_DEL_ON_DEACTIVATE => false,
			woocommerce_additional_fees::OPT_DEL_ON_UNINSTALL => true,
			woocommerce_additional_fees::OPT_ENABLE_ALL => true,
			woocommerce_additional_fees::OPT_ENABLE_PROD_FEES => true,
			woocommerce_additional_fees::OPT_GATEWAY_PREFIX => array()
			);

		if(isset($woocommerce_additional_fees) && (count($woocommerce_additional_fees->gateways) > 0))
		{
			foreach ($woocommerce_additional_fees->gateways as $key => $gateway)
			{
				$option_gateway = array();
				$go = self::get_option_gateway_default($option_gateway, $gateway->title);
				$default[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key] = $go;
			}
		}

		$options = get_option(self::OPTIONNAME, array());

		$go = array();
		if(isset($options[self::OPT_GATEWAY_PREFIX]))
		{
			$go = $options[self::OPT_GATEWAY_PREFIX];
		}
		$new_go = wp_parse_args($go, $default[self::OPT_GATEWAY_PREFIX]);
		$new_options = wp_parse_args($options, $default);
		$new_options[self::OPT_GATEWAY_PREFIX] = $new_go;

		$old_opt = serialize($options);
		$new_opt = serialize($new_options);

		if(version_compare($new_options[self::OPT_VERSION], self::VERSION, '!=') || ($old_opt != $new_opt))
		{
			$new_options[self::OPT_VERSION] = self::VERSION;
			update_option(woocommerce_additional_fees::OPTIONNAME, $new_options);
		}

		return $new_options;
	}

	/**
	 * Gets the post meta for this product and returns an array filled with all needed values initialised
	 *
	 * @return array
	 */
	static public function &get_post_meta_product_default($post_id)
	{
		global $woocommerce_additional_fees;

		$default = array(
			woocommerce_additional_fees::OPT_ENABLE_PROD => 'no',
			woocommerce_additional_fees::OPT_GATEWAY_PREFIX => array()
			);

		if(isset($woocommerce_additional_fees) && (count($woocommerce_additional_fees->gateways) > 0))
		{
			foreach ($woocommerce_additional_fees->gateways as $key => $gateway)
			{
				$option_gateway = array();
				$go = self::get_option_gateway_default($option_gateway, $gateway->title, true);
				$default[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key] = $go;
			}
		}

		$pm = get_post_meta($post_id, self::KEY_POSTMETA_PRODUCT, true);

		$g_pm = array();
		if(isset($pm[self::OPT_GATEWAY_PREFIX]))
		{
			$g_pm = $pm[self::OPT_GATEWAY_PREFIX];
		}

		$new_g_pm = wp_parse_args($g_pm, $default[self::OPT_GATEWAY_PREFIX]);
		$new_pm = wp_parse_args($pm, $default);
		$new_pm[self::OPT_GATEWAY_PREFIX] = $new_g_pm;

		$old_opt = serialize($pm);
		$new_opt = serialize($new_pm);

		if($old_opt != $new_opt)
		{
			update_post_meta($post_id, self::KEY_POSTMETA_PRODUCT, $new_pm);
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
	static public function &get_option_gateway_default(array $option_gateway, $gateway_name = '', $for_postmeta = false)
	{
		$text = __('Additional Fee', woocommerce_additional_fees::TEXT_DOMAIN);
		if(is_string($gateway_name) && !empty($gateway_name))
		{
			$text = __('Fee for ', woocommerce_additional_fees::TEXT_DOMAIN).$gateway_name;
		}
		$text .= ':';

		($for_postmeta) ? $enable = 'no' : $enable = false;
		$default = array(
			woocommerce_additional_fees::OPT_KEY_ENABLE => $enable,
			woocommerce_additional_fees::OPT_KEY_OUTPUT => $text,
			woocommerce_additional_fees::OPT_KEY_TAXCLASS => woocommerce_additional_fees::VAL_TAX_STANDARD,
			woocommerce_additional_fees::OPT_KEY_ADD_VALUE_TYPE => woocommerce_additional_fees::VAL_ADD_PERCENT,
			woocommerce_additional_fees::OPT_KEY_VALUE_TO_ADD => 0,
			woocommerce_additional_fees::OPT_KEY_MAX_VALUE => 0
			);

		$new_options = shortcode_atts($default, $option_gateway);
		return $new_options;
	}

	/**
	 * Called before starting calculating totals. All additional fees for products are added to additional fees of cart
	 *
	 * @param WC_Cart $obj_wc_cart
	 */
	public function handler_wc_before_calculate_totals(WC_Cart $obj_wc_cart)
	{
		//	ignore cart
		if (!is_checkout() && !defined('WOOCOMMERCE_CHECKOUT'))
			return;

		//	skip, if all disabled globally
		if(!$this->options[self::OPT_ENABLE_ALL])
			return;

		if(!$this->options[self::OPT_ENABLE_PROD_FEES])
		{
			return;
		}

		if(!$this->request_data_loaded)	$this->load_request_data();


			//	loop through each product and add fee for each item in cart
		if ( sizeof( $obj_wc_cart->cart_contents ) > 0 )
		{
			foreach ($obj_wc_cart->cart_contents as $cart_item_key => $values )
			{
				$_product = $values['data'];
				if(! ($_product instanceof WC_Product)) continue;

						//
				$pm_product = self::get_post_meta_product_default($_product->id);
				
				//remove single product check - option doesn't exist 
				//if($pm_product[self::OPT_ENABLE_PROD] != 'yes') continue;

				if(!empty($this->payment_gateway_key) && isset($pm_product[self::OPT_GATEWAY_PREFIX][$this->payment_gateway_key]))
				{
					$gateway = $pm_product[self::OPT_GATEWAY_PREFIX][$this->payment_gateway_key];
				}
				else
				{
					$gateway = array();
				}

				$gateway = self::get_option_gateway_default($gateway, $this->payment_gateway_key, true);

				if($gateway[self::OPT_KEY_ENABLE] != 'yes') continue;

				$total = $_product->get_price_excluding_tax() * $values['quantity'];

				$maxval = 0.0;
				if(isset($gateway[self::OPT_KEY_MAX_VALUE]))
					$maxval = $gateway[self::OPT_KEY_MAX_VALUE];

				if(!is_numeric($maxval))
					$maxval = 0.0;
				else
					$maxval = (float)$maxval;

				if(!empty($maxval))
				{
					$check_total = ($obj_wc_cart->prices_include_tax) ? $_product->get_price_including_tax() * $values['quantity'] : $total;

					if($check_total >= $maxval)
						continue;
				}

				$fees_calc = $this->calculate_fees($obj_wc_cart, $total, $gateway, $values['quantity']);

				$fees_calc->type = WC_calc_add_fee::VAL_PRODUCT_ADD_FEE;
				$fees_calc->gateway_key = $this->payment_gateway_key;
				$fees_calc->gateway_title = $this->gateways[$this->payment_gateway_key]->title;
				$fees_calc->gateway_option = $gateway;
				$fees_calc->product_desc = $_product->get_title();

				$this->add_fee_to_cart($fees_calc, $obj_wc_cart);
			}
		}
	}


	/**
	 * Called before calculating final totals. As we need the complete calculated values of
	 * the cart we have to alter the tax values.
	 *
	 * @param WC_Cart $obj_wc_cart
	 */
	public function handler_wc_calculate_totals(WC_Cart $obj_wc_cart)
	{
		global $woocommerce;

		//	ignore cart
		if (!is_checkout() && !defined('WOOCOMMERCE_CHECKOUT'))
			return;

		//	skip, if all disabled
		if(!$this->options[self::OPT_ENABLE_ALL])
			return;

		if(!$this->request_data_loaded)	$this->load_request_data();

			//	if add fees for gateway is disabled
		if(!$this->payment_gateway_option[self::OPT_KEY_ENABLE])
		{
			return;
		}

		$fee_total = $this->calculate_gateway_fees_total_cart($obj_wc_cart);
		if(! isset($fee_total))
		{
			return;
		}

		$obj_wc_cart->fee_total += $fee_total->amount_no_tax;
		if($fee_total->taxable)
		{
			if(isset($fee_total->tax_amount))
				$obj_wc_cart->tax_total += $fee_total->tax_amount;

			isset($fee_total->taxes) ? $taxes = $fee_total->taxes : $taxes = array();

					// Tax rows - merge the totals we just got
			foreach ( array_keys( $obj_wc_cart->taxes + $taxes ) as $key ) {
				$obj_wc_cart->taxes[ $key ] = ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 ) + ( isset( $obj_wc_cart->taxes[ $key ] ) ? $obj_wc_cart->taxes[ $key ] : 0 );
			}
		}
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
//		if(!isset($_REQUEST['order-pay'])) return;
		
		//	ignore cart
		if (!is_checkout() && !defined('WOOCOMMERCE_CHECKOUT'))
			return;

		//	skip, if all disabled
		if(!$this->options[self::OPT_ENABLE_ALL])
			return;

		if(!$this->request_data_loaded)	$this->load_request_data();
			
		$order_id = 0;
		if(isset($_REQUEST[ 'order_id' ]))	//	< version 2.1.0
		{
			$order_id = absint($_REQUEST[ 'order_id' ]);
					// Pay for existing order only
			if (! ( isset( $_REQUEST['pay_for_order'] ) && isset( $_REQUEST['order'] ) && isset( $_REQUEST['order_id'] ) ))	return;
		}
		else if (isset($_REQUEST[ 'order-pay' ]))
		{
			$order_id = absint($_REQUEST['order-pay']);
					// Pay for existing order only
			if (! ( isset( $_REQUEST['pay_for_order'] ) && isset( $_REQUEST['key'] ) && $order_id )) return;
		}
		else
		{
			return;
		}
		
		$order = new WC_Order( $order_id );
		
		$payment_method = ! empty( $order->payment_method ) ? $order->payment_method : $this->default_payment_gateway_key;
		
//		$pay_for_order = $_REQUEST['pay_for_order'];
//		$key = $_REQUEST['key'];
		
		$info = 'id="add_fee_info_pay" ';
//		$info .= 'add_fee_action="add_fee_calc_fee_pay_order" ';
//		$info .= 'add_fee_order="'.$order_id.'" ';
//		$info .= 'add_fee_pay="'.$pay_for_order.'" ';
		$info .= 'add_fee_paymethod="'.$payment_method.'" ';
//		$info .= 'add_fee_key="'.$key.'" ';
		
		echo '<div ';
			echo $info;
		echo ' style="display: none;">';
		echo '</div>';
		return;
	}

	/**
	 * Calculates the fee for a given value. Takes care of tax calculation.
	 *
	 * @param WC_Cart $obj_wc_cart
	 * @param float $value
	 * @param array $gateway
	 * @param int $quantity
	 * @return WC_calc_add_fee
	 */
	protected function &calculate_fees(WC_Cart $obj_wc_cart, $value, array $gateway, $quantity = 1)
	{
		global $woocommerce;

			//	get tax rates
		$taxclass = ($gateway[self::OPT_KEY_TAXCLASS] == self::VAL_TAX_STANDARD) ? '' : $gateway[self::OPT_KEY_TAXCLASS];
		$tax_rates = $obj_wc_cart->tax->get_rates($taxclass);

		$no_tax = false;
		if(($gateway[self::OPT_KEY_TAXCLASS] == self::VAL_TAX_NONE) || $woocommerce->customer->is_vat_exempt() || (get_option( 'woocommerce_calc_taxes' ) == 'no'))
		{
			$no_tax = true;
		}

		$tax_included = true;

		$add_fee = (float)$gateway[self::OPT_KEY_VALUE_TO_ADD];

		switch ($gateway[self::OPT_KEY_ADD_VALUE_TYPE])
		{
			case self::VAL_FIXED:
				$add_fee *= $quantity;
				$tax_included = $obj_wc_cart->prices_include_tax;
				break;
			case self::VAL_INCLUDE_PERCENT:
				if(! $no_tax)
				{			//	include tax in percents to add
					$add_fee_taxs = $obj_wc_cart->tax->calc_tax( $add_fee, $tax_rates, false );
					$add_fee += $obj_wc_cart->tax->get_tax_total( $add_fee_taxs );
				}
				$add_fee = (($value * 100.0) / (100.0 - $add_fee)) - $value;
				$tax_included = false;
				break;
			case self::VAL_ADD_PERCENT:
				$add_fee = ($value * $add_fee) / 100.0;
				$tax_included = false;
				break;
			default:
				$add_fee = 0.0;
				break;
		}
		$add_fee = round($add_fee, 2);

			//	calculate tax amount - for saving taxes object
		$taxes = $obj_wc_cart->tax->calc_tax( $add_fee, $tax_rates, $tax_included );
		$tax_amount = $obj_wc_cart->tax->get_tax_total( $taxes );

			//	reset tax amount to our custom settings
		if ($no_tax)
		{
			$tax_amount = 0.0;
		}
		$tax_amount = round($tax_amount, 2);

			//	calculate add_fee with and without tax
		switch ($gateway[self::OPT_KEY_ADD_VALUE_TYPE])
		{
			case self::VAL_FIXED:
			case self::VAL_ADD_PERCENT:
			case self::VAL_INCLUDE_PERCENT:
				if($tax_included)
				{
					$fee_tax = $add_fee;
					$fee_no_tax = $fee_tax - $tax_amount;
				}
				else
				{
					$fee_no_tax = $add_fee;
					$fee_tax = $fee_no_tax + $tax_amount;
				}
				break;
//			case self::VAL_INCLUDE_PERCENT:
//				$fee_tax = $add_fee;
//				($no_tax) ? $fee_no_tax = $fee_tax : $fee_no_tax = $fee_tax - $tax_amount;
//				break;
			default:
				$fee_no_tax = $fee_tax = 0.0;
				break;
		}

		$calc_fee = new WC_calc_add_fee();
		$calc_fee->amount_no_tax = round($fee_no_tax, 2);
		$calc_fee->amount_incl_tax = round($fee_tax, 2);
		$calc_fee->tax_amount = round($tax_amount, 2);
		$calc_fee->taxable = (!$no_tax);
		$calc_fee->taxes = $taxes;

		return $calc_fee;
	}

	/**
	 * Adds the fee to the cart fee array and also stores the additional information there.
	 *
	 * @param array $fee
	 * @param WC_Cart $obj_wc_cart
	 */
	protected function add_fee_to_cart(WC_calc_add_fee &$fee, WC_Cart $obj_wc_cart)
	{
			//	add fee
		$name = $fee->gateway_option[woocommerce_additional_fees::OPT_KEY_OUTPUT];
		$amount = $fee->amount_no_tax;
		$taxable = $fee->taxable;
		$fee->gateway_option[woocommerce_additional_fees::OPT_KEY_TAXCLASS] == self::VAL_TAX_STANDARD ?  $tax_class = '' : $tax_class = $fee->gateway_option[woocommerce_additional_fees::OPT_KEY_TAXCLASS];

		$obj_wc_cart->add_fee($name, $amount, $taxable, $tax_class);
		$fee_cart = &$obj_wc_cart->fees[count($obj_wc_cart->fees) - 1];
		$fee_cart->tax = $fee->tax_amount;

				//	save source information for a possible chance to display later (maybe in order) to reconstruct calculation
		$fee_cart->data_source = $fee;
	}


	/**
	 * Initialise values that need translation and WooCommerce
	 *
	 */
	public function init_values()
	{
		if(!isset(self::$value_type_to_add) || empty(self::$value_type_to_add))
		{
			self::$value_type_to_add = array(
				self::VAL_FIXED => __('Fixed amount', woocommerce_additional_fees::TEXT_DOMAIN),
				self::VAL_ADD_PERCENT => __('add % to total amount', woocommerce_additional_fees::TEXT_DOMAIN),
				self::VAL_INCLUDE_PERCENT => __('include % in total amount', woocommerce_additional_fees::TEXT_DOMAIN)
				);
		}

		if(empty($this->gateways))
		{
			global $woocommerce;
			$objgateways = $woocommerce->payment_gateways;
			$this->gateways = $objgateways->payment_gateways();
					//	set default gateway
			reset($this->gateways);
			$first = current($this->gateways);
			$this->payment_gateway_key = $first->id;
		}

		if(empty($this->tax_classes))
		{
			$tax_classes = array_filter(array_map('trim', explode("\n", get_option('woocommerce_tax_classes'))));
			$this->tax_classes = array();

			$this->tax_classes[self::VAL_TAX_NONE] = __('No Tax required', woocommerce_additional_fees::TEXT_DOMAIN);
			$this->tax_classes[self::VAL_TAX_STANDARD] = __('Standard', woocommerce_additional_fees::TEXT_DOMAIN);
			if ($tax_classes)
			{
				foreach ($tax_classes as $class)
				{
//					$this->tax_classes[sanitize_title($class)] = $class;
					$this->tax_classes[$class] = $class;
				}
			}
		}

		$this->options = self::get_options_default();

			//	allow other classes to access new wc data
		do_action('woocommerce_additional_fees_init', $this);
	}


	/**
	 * Loads the Request and Session data, initialises the gateway and
	 * implements a fallback for option array.
	 */
	protected function load_request_data()
	{
		global $woocommerce;

		$this->init_values();

        $posted_payment_gateway = $woocommerce->session->chosen_payment_method;
        $available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();

        if (!empty($available_gateways))
        {
            if (isset($posted_payment_gateway) && isset($available_gateways[$posted_payment_gateway]))
            {
                $this->payment_gateway_key = $available_gateways[$posted_payment_gateway]->id;
            }
            elseif(isset($available_gateways[get_option('woocommerce_default_gateway')]))
            {
                $this->payment_gateway_key = $available_gateways[get_option('woocommerce_default_gateway')]->id;
            }
            else
            {
                $this->payment_gateway_key = current($available_gateways)->id;
            }
        }

		if(!empty($this->payment_gateway_key) && isset($this->options[self::OPT_GATEWAY_PREFIX][$this->payment_gateway_key]))
		{
			$payment_gateway_option = $this->options[self::OPT_GATEWAY_PREFIX][$this->payment_gateway_key];
		}
		else
		{
			$payment_gateway_option = array();
		}

		$this->payment_gateway_option = self::get_option_gateway_default($payment_gateway_option);

		$dif = array_diff($this->payment_gateway_option, $payment_gateway_option);
		if(!empty($dif))
		{				//	save option
			$this->options[self::OPT_GATEWAY_PREFIX][$this->payment_gateway_key] = $this->payment_gateway_option;
			@update_option(self::OPTIONNAME, $this->options);
		}

		$this->request_data_loaded = true;
	}

	/**
	 * Calculates the gateway fees for the total cart (value and tax) and modifies the values in the cart
	 *
	 * @param WC_Cart $obj_wc_cart
	 */
	protected function calculate_gateway_fees_total_cart(WC_Cart &$obj_wc_cart)
	{
		global $woocommerce;
		
			//   discount_total is exclusive tax ?????
//		$total = $obj_wc_cart->cart_contents_total + $obj_wc_cart->tax_total + $obj_wc_cart->shipping_tax_total + $obj_wc_cart->shipping_total - $obj_wc_cart->discount_total + $obj_wc_cart->fee_total;

			//	total excluding tax
		$total_excl_tax = $obj_wc_cart->subtotal_ex_tax + $obj_wc_cart->shipping_total + $obj_wc_cart->fee_total - $obj_wc_cart->discount_total;
		
			//	tax_total includes tax of fees but not shipping tax, therefore add it
		$total_tax = $obj_wc_cart->tax_total + $obj_wc_cart->shipping_tax_total;
		
		$total_incl_tax = $total_excl_tax + $total_tax;
		
		
		$maxval = 0.0;
		if(isset($this->payment_gateway_option[self::OPT_KEY_MAX_VALUE]))
			$maxval = $this->payment_gateway_option[self::OPT_KEY_MAX_VALUE];

		if(!is_numeric($maxval))
			$maxval = 0.0;
		else
			$maxval = (float)$maxval;

		if(!empty($maxval))
		{
			$check_total = ($obj_wc_cart->prices_include_tax) ? $total_incl_tax : $total_excl_tax;
			
			if($check_total >= $maxval)
				return;
		}


		$fees_calc = $this->calculate_fees($obj_wc_cart, $total_excl_tax, $this->payment_gateway_option);
		$fees_calc->type = WC_calc_add_fee::VAL_TOTAL_CART_ADD_FEE;
		$fees_calc->gateway_key = $this->payment_gateway_key;
		$fees_calc->gateway_title = $this->gateways[$this->payment_gateway_key]->title;
		$fees_calc->gateway_option = $this->payment_gateway_option;


		$this->add_fee_to_cart($fees_calc, $obj_wc_cart);

		return $fees_calc;
	}



}


?>