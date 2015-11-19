<?php
/**
 *
 *
 * @author Schoenmann Guenter
 */
if ( ! defined( 'ABSPATH' ) ) {  exit;  }   // Exit if accessed directly

class WC_Add_Fees_Panel_Admin
{
		//	ID/name of elements
	const ID_DEL_ON_DEACTIVATE = 'wc_add_fees_del_on_deactivate';
	const ID_DEL_ON_UNINSTALL = 'wc_add_fees_del_on_uninstall';
	const ID_ENABLE_ADD_FEES = 'wc_add_fees_enable';
	const ID_ENABLE_ADD_FEES_PROD = 'wc_add_fees_prod_enable';

	const PREFIX_GATEWAY_DIV = 'gateway_div_';
	const PREFIX_SECTION_LINKS = 'wc_add_fees_';

	/**
	 *
	 * @var array
	 */
	public $options;

	/**
	 * Inputarray for settingspage
	 *
	 * @var array
	 */
	public $fields;

	/**
	 *
	 * @var WC_Addons_Add_Fees
	 */
	public $woo_addons;

	/**
	 * 
	 * @param array $options
	 * @param WC_Addons_Add_Fees $woo_addons
	 */
	public function __construct( array $options, WC_Addons_Add_Fees $woo_addons )
	{
		$this->options = $options;
		$this->fields = array();
		$this->woo_addons = $woo_addons;
	}

	public function __destruct()
	{
		unset( $this->options );
		unset( $this->fields );
		unset( $this->woo_addons );
	}

	/**
	 * Returns the HTML output for the single product tab section
	 *
	 * @param array $postmeta
	 * @return string
	*/
	public function echo_form_fields_product( array $postmeta )
	{
	    echo '<div id="add_fees_product_data" class="panel woocommerce_options_panel">';

	    $err_cnt = $this->woo_addons->count_errors();
	    if( $err_cnt > 0)
	    {
			$msg = sprintf( __( '%1$d Error(s) have been found and were reset to original value. Please check your entries. ', WC_Add_Fees::TEXT_DOMAIN ), $err_cnt );
			$element = array(
						'type' => WC_Addons_Add_Fees::TAG_COMPLETE,
						'tag' => 'div',
						'class' => 'error add_fees_error',
						'innerhtml' => $msg
					);
			$this->woo_addons->echo_html_string( $element );
	    }


	    $this->fields = array();
	    echo '<div id="wc_add_fees_product_container" class="add_fees_link_section">';

	    $this->get_link_list_fields( false );
	    foreach ( $this->fields  as &$element )
	    {
			$this->woo_addons->echo_html_string( $element );
	    }
	    unset ( $element );

	    $element = array(
					'type' => WC_Addons_Add_Fees::TAG_OPEN,
					'tag' => 'div',
					'id' => self::PREFIX_SECTION_LINKS. 'Basic_Settings',
					'class' => 'section'
				);
		$this->woo_addons->echo_html_string( $element );

		$chk = array(
			'id' => self::ID_ENABLE_ADD_FEES,
			'wrapper_class' => '',
			'label' => __( 'Activate for this product:', WC_Add_Fees::TEXT_DOMAIN ),
			'value' => $postmeta[WC_Add_Fees::OPT_ENABLE_PROD],
			'description' => __( 'Check to activate additional fees for this product. You must also enable additional fees for all products in &quot;Basic Settings&quot; in WooCommerce Settings Page. If unchecked, all gateway settings for this product are ignored. ', WC_Add_Fees::TEXT_DOMAIN )
			);
		woocommerce_wp_checkbox( $chk );
		echo '</div>';		//	id="....Basic_Settings"

		foreach( WC_Add_Fees::instance()->gateways as $k => &$value )
		{
			$this->echo_form_fields_product_gateways( $k, $value, $postmeta );
		}
		unset( $value );

	    echo '</div>';		//	id="wc_add_fees_product_container"
	    echo '</div>';		//	id="add_fees_product_data"
	    return;
	}

	/**
	 * Returns the HTML output for the settings tab section
	 *
	 * @return string
	 */
	public function &get_form_fields_settings()
	{
			//	surrounding container
		$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_OPEN,
			'tag' => 'div',
			'id' => 'wc_add_fees_settings_container',
			'class' => 'subsubsub_section'
			);

		$err_cnt = $this->woo_addons->count_errors();
		if( $err_cnt > 0 )
		{
			$msg = sprintf( __( '%1$d Error(s) have been found and were reset to original value. Please check your entries. ', WC_Add_Fees::TEXT_DOMAIN ), $err_cnt );
			$this->fields[] = array(
				'type' => WC_Addons_Add_Fees::TAG_COMPLETE,
				'tag' => 'div',
				'class' => 'error add_fees_error',
				'innerhtml' => $msg
			);
		}

		$this->get_link_list_fields( false );
		$this->get_basic_setting_fields();
		foreach( WC_Add_Fees::instance()->gateways as $k => &$value )
		{
			$this->get_gateway_inputfields( $k, $value );
		}

		//	surrounding container end
		$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_CLOSE,
			'tag' => 'div'
			);

		return $this->fields;
	}

	/**
	 * Adds the gateway link list to $this->fields[]
	 *
	 * @param bool $standard_class
	 */
	protected function get_link_list_fields( $standard_class = true )
	{
		$nr_gateways = count( WC_Add_Fees::instance()->gateways );
		$seperator = '|';

		$cl = $standard_class ? 'subsubsub' : 'add_fees_ul';
		$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_OPEN,
			'tag' => 'ul',
			'class' => $cl
			);

		$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_OPEN,
			'tag' => 'li',
			'class' => 'add_fees_basic_link'
			);

		$s = '';
		if( $nr_gateways > 0 )
		{
			$s = ' ' . $seperator;
		}

		$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_COMPLETE,
			'tag' => 'a',
			'class' => 'current',
			'href' => '#' .self::PREFIX_SECTION_LINKS. 'Basic_Settings',
			'innerhtml' => __( 'Basic Settings', WC_Add_Fees::TEXT_DOMAIN ) . $s
			);

		$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_CLOSE,
			'tag' => 'li'
			);

		$count = 1;
		foreach( WC_Add_Fees::instance()->gateways as $k => &$gateway )
		{
			$s = '';
			if( property_exists( $gateway, 'method_title' ) && ! empty( $gateway->method_title ) )
			{
				$s = $gateway->method_title;
			}
			else if( method_exists( $gateway, 'get_title' ) )
			{
				$s = $gateway->get_title();
			}

			if( $s != $gateway->title ) 
			{
				$s .= ' ( ' . $gateway->title . ' )';
			}
			
			if( $count < $nr_gateways )
			{
				$s .= ' ' . $seperator;
			}

			$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_OPEN,
			'tag' => 'li'
			);

			$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_COMPLETE,
			'tag' => 'a',
			'href' => '#' .self::PREFIX_SECTION_LINKS.sanitize_title(str_replace( '%', '', $gateway->id) ),
			'innerhtml' => $s
			);

			$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_CLOSE,
			'tag' => 'li'
			);
			$count++;
		}
		unset ( $gateway );

		$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_CLOSE,
			'tag' => 'ul'
			);

		$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_STANDALONE,
			'tag' => 'br',
			'class' => 'clear'
			);
	}

	/**
	 *
	 */
	protected function get_basic_setting_fields()
	{
		$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_OPEN,
			'tag' => 'div',
			'id' => self::PREFIX_SECTION_LINKS. 'Basic_Settings',
			'class' => 'section'
			);

		$this->fields[] = array(
			'type' => 'title',
			'id' => 'ips_basic_settings',
			'title' => __( 'Basic settings for WooCommerce additional fees plugin', WC_Add_Fees::TEXT_DOMAIN )
			);

		if(WC_Add_Fees::$show_activation)
		{
			$checked = $this->options[WC_Add_Fees::OPT_DEL_ON_DEACTIVATE] ? 'yes' : 'no';
			$this->fields[] = array(
				'type' => 'checkbox',
				'id' => self::ID_DEL_ON_DEACTIVATE,
				'default' => $checked,
				'desc' => __( 'Delete options on deactivate', WC_Add_Fees::TEXT_DOMAIN ),
				'desc_tip' => __( 'Check to delete options on deactivate. Does not remove any fees in existing orders. ', WC_Add_Fees::TEXT_DOMAIN )
					);
		}

		if(WC_Add_Fees::$show_uninstall)
		{
			$checked = $this->options[WC_Add_Fees::OPT_DEL_ON_UNINSTALL] ? 'yes' : 'no'; 
			$this->fields[] = array(
				'type' => 'checkbox',
				'id' => self::ID_DEL_ON_UNINSTALL,
				'default' => $checked,
				'desc' => __( 'Delete options on uninstall', WC_Add_Fees::TEXT_DOMAIN ),
				'desc_tip' => __( 'Check to delete options on uninstall. Does not remove any fees in existing orders. ', WC_Add_Fees::TEXT_DOMAIN )
					);
		}

		/*
		$checked = 'no';
		if( $this->options[woocommerce_additional_fees::OPT_ENABLE_ALL] )
		{
			$checked = 'yes';
		}
		$this->fields[] = array(
			'type' => 'checkbox',
			'id' => self::ID_ENABLE_ADD_FEES,
			'default' => $checked,
			'desc' => __( 'Enable additional fees calculation for the shop', woocommerce_additional_fees::TEXT_DOMAIN ),
			'desc_tip' => __( 'Check to enable additional fees calculation for the shop. If not checked, no additional fees will be applied. All other settings are ignored. ', woocommerce_additional_fees::TEXT_DOMAIN )
				);
		*/

		$checked = $this->options[WC_Add_Fees::OPT_ENABLE_PROD_FEES] ? 'yes' : 'no';
		$this->fields[] = array(
			'type' => 'checkbox',
			'id' => self::ID_ENABLE_ADD_FEES_PROD,
			'default' => $checked,
			'desc' => __( 'Enable additional fees calculation for products', WC_Add_Fees::TEXT_DOMAIN ),
			'desc_tip' => __( 'Check to enable additional fees calculation for products in the shop. If not checked, all settings on product level will be ignored. ', WC_Add_Fees::TEXT_DOMAIN )
				);

		$this->fields[] = array(
			'type' => 'sectionend'
			);

		$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_CLOSE,
			'tag' => 'div'
			);
	}

	/**
	 * Initialise gateway inputfields to be able to load them from request
	 * Namefields are appended with '___$key'
	 */
	public function get_gateway_inputfields( $key, $gateway )
	{
		$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_OPEN,
			'tag' => 'div',
			'id' => self::PREFIX_SECTION_LINKS.sanitize_title(str_replace( '%', '', $gateway->id) ),
			'class' => 'section'
			);

		$s = __( 'Additional Fee Settings for Payment Gateway:', WC_Add_Fees::TEXT_DOMAIN );
		$s .= ' ' . $gateway->title . ' [' . $gateway->id . ']';

		$this->fields[] = array(
			'type' => 'title',
			'id' => 'ips_gateway_settings',
			'title' => $s
				);

		$t1 = __( 'Status: ', WC_Add_Fees::TEXT_DOMAIN );
		$yes = __( 'Payment gateway is enabled. ', WC_Add_Fees::TEXT_DOMAIN );
		$no = __( 'Payment gateway is disabled', WC_Add_Fees::TEXT_DOMAIN );

		$t2 = ( $gateway->enabled == 'yes' ) ? $yes : $no;
		if( $gateway->enabled == 'yes' )
		{
			$attr = array(
				'style' => 'color: #00CC00; font-weight:bold; margin-left: 240px;'
				);
		}
		else
		{
			$attr = array(
				'style' => 'color: #FF0000; font-weight:bold; margin-left: 240px;'
				);
		}

		$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_COMPLETE,
			'tag' => 'span',
			'innerhtml' => $t1. $t2,
			'attributes' => $attr
			);
		
		$checked = $this->options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_ENABLE] ? 'yes' : 'no';
		$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_ENABLE);
		$this->fields[] = array(
			'type' => 'checkbox',
			'id' => $name,
			'default' => $checked,
			'desc' => __( 'Enable additional fees on total cart value for this gateway', WC_Add_Fees::TEXT_DOMAIN ),
			'desc_tip' => __( 'Check to enable additional fees to be added based on the total cart value for this gateway. The fee is applied to overall cart value also including all fees added on product level. ', WC_Add_Fees::TEXT_DOMAIN )
				);

		$default = $this->options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_OUTPUT];
		$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_OUTPUT );
		$this->fields[] = array(
			'type' => 'text',
			'id' => $name,
			'title' => __( 'Output text:', WC_Add_Fees::TEXT_DOMAIN ),
			'default' => $default,
			'desc' => __( 'Enter Text for Fee to display as explanation to Fee', WC_Add_Fees::TEXT_DOMAIN ),
			'desc_tip' => true,
			'css' => "width:300px;"
					);


		$default = $this->options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_TAXCLASS];
		if( empty( $default ) )
		{
			$default = WC_Add_Fees::VAL_TAX_STANDARD;
		}
		$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_TAXCLASS );
		$this->fields[] = array(
			'type' => 'select',
			'id' => $name,
			'title' => __( 'Tax Class:', WC_Add_Fees::TEXT_DOMAIN ),
			'default' => $default,
			'options' => WC_Add_Fees::instance()->tax_classes,
			'desc' => __( 'Select the tax class for the additional fee', WC_Add_Fees::TEXT_DOMAIN ),
			'desc_tip' => true
			);


		$default = $this->options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_ADD_VALUE_TYPE];
		if( empty( $default ) )
		{
			$default = WC_Add_Fees::VAL_INCLUDE_PERCENT;
		}
		$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_ADD_VALUE_TYPE );
		$this->fields[] = array(
			'type' => 'select',
			'id' => $name,
			'title' => __( 'Type of additional fee:', WC_Add_Fees::TEXT_DOMAIN ),
			'default' => $default,
			'options' => WC_Add_Fees::$value_type_to_add,
			'desc' => __( 'Select the type of value for the additional fee', WC_Add_Fees::TEXT_DOMAIN ),
			'desc_tip' => true
			);

		$default = $this->options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_VALUE_TO_ADD];
		$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_VALUE_TO_ADD );
		$this->fields[] = array(
			'type' => 'number',
			'custom_attributes' => array( 'step'=>'any' ),
			'id' => $name,
			'title' => __( 'Value to add:', WC_Add_Fees::TEXT_DOMAIN ),
			'default' => $default,
			'desc' => __( 'Enter the value to add to total amount, In case of \'Fixed amount\' enter the value according to your settings in \'Prices entered with/without tax\'.', WC_Add_Fees::TEXT_DOMAIN ),
			'desc_tip' => true
			);

		$err = $this->woo_addons->get_error_message( $name );
		if( is_array( $err ) )
		{
			$this->add_error( $err );
		}
		
		$default = $this->options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_VALUE_TO_ADD_FIXED];
		$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_VALUE_TO_ADD_FIXED );
		$this->fields[] = array(
			'type' => 'number',
			'custom_attributes' => array( 'step'=>'any' ),
			'id' => $name,
			'title' => __( 'Fixed Value to add:', WC_Add_Fees::TEXT_DOMAIN ),
			'default' => $default,
			'desc' => __( 'Enter a fixed value to be added in addition to the calculated fees above (e.g. a fixed transaction fee charged by the credit card company). Enter the value according to your settings in \'Prices entered with/without tax\'.', WC_Add_Fees::TEXT_DOMAIN ),
			'desc_tip' => true
			);

		$err = $this->woo_addons->get_error_message( $name );
		if( is_array( $err ) )
		{
			$this->add_error( $err );
		}

		$default = $this->options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_MAX_VALUE];
		$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_MAX_VALUE );
		$this->fields[] = array(
			'type' => 'number',
			'custom_attributes' => array( 'step'=>'any' ),
			'id' => $name,
			'title' => __( 'Maximum cart value for adding fee:', WC_Add_Fees::TEXT_DOMAIN ),
			'default' => $default,
			'desc' => __( 'Ignore additional fee, when total amount exceeds value. Leave 0 for unlimited amount. Enter the value according to your settings in \'Prices entered with/without tax\'.', WC_Add_Fees::TEXT_DOMAIN ),
			'desc_tip' => true
			);

		$err = $this->woo_addons->get_error_message( $name );
		if( is_array( $err ) )
		{
			$this->add_error( $err );
		}

		$this->fields[] = array(
			'type' => 'sectionend'
			);

		$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_CLOSE,
			'tag' => 'div'
			);
	}

	/**
	 * Initialise gateway inputfields to be able to load them from request
	 * Namefields are appended with '___$key'
	 *
	 * @param string $key
	 * @param object $gateway
	 * @param array $postmeta
	 */
	protected function echo_form_fields_product_gateways( $key, $gateway, array $postmeta )
	{
		$element = array(
			'type' => WC_Addons_Add_Fees::TAG_OPEN,
			'tag' => 'div',
			'id' => self::PREFIX_SECTION_LINKS.sanitize_title( $gateway->id),
			'class' => 'section'
			);

		$this->woo_addons->echo_html_string( $element );

		$s = __( 'Additional Fee Settings for Payment Gateway:', WC_Add_Fees::TEXT_DOMAIN );
		$s .= ' ' . $gateway->title . ' [' . $gateway->id . ']';

		echo '<h4>' . esc_html( $s ) . '</h4>';


		$t1 = __( 'Status: ', WC_Add_Fees::TEXT_DOMAIN );
		$yes = __( 'Payment gateway is enabled. ', WC_Add_Fees::TEXT_DOMAIN );
		$no = __( 'Payment gateway is disabled', WC_Add_Fees::TEXT_DOMAIN );

		$t2 = ( $gateway->enabled == 'yes' ) ? $yes : $no;
		if( $gateway->enabled == 'yes' )
		{
			$attr = array(
				'style' => 'color: #00CC00; font-weight:bold; margin-left: 240px;'
				);
		}
		else
		{
			$attr = array(
				'style' => 'color: #FF0000; font-weight:bold; margin-left: 240px;'
				);
		}

		$element = array(
			'type' => WC_Addons_Add_Fees::TAG_COMPLETE,
			'tag' => 'span',
			'innerhtml' => $t1 . $t2,
			'attributes' => $attr
			);

		$this->woo_addons->echo_html_string( $element );

		$checked = $postmeta[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_ENABLE];
		if( $checked != 'yes' )
		{
			$checked = 'no';
		}
		$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_ENABLE );

		$element = array(
			'id' => $name,
			'wrapper_class' => '',
			'label' =>  __( 'Enable additional fees:', WC_Add_Fees::TEXT_DOMAIN ),
			'value' => $checked,
			'description' => __( 'Check to enable Additional fees calculation for this product and gateway. Additional fees are added based on the value of this product. ', WC_Add_Fees::TEXT_DOMAIN )
			);
		woocommerce_wp_checkbox( $element );

		$default = $postmeta[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_OUTPUT];
		$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_OUTPUT );
		$element = array(
			'id' => $name,
			'wrapper_class' => '',
			'label' =>  __( 'Output text:', WC_Add_Fees::TEXT_DOMAIN ),
			'value' => $default,
			'description' => __( 'Enter Text for Fee to display as explanation to Fee', WC_Add_Fees::TEXT_DOMAIN ),
			'desc_tip' => true,
			'custom_attributes' => array(
						'style' => 'width:300px;'
					)
			);

		woocommerce_wp_text_input( $element );

		$default = $postmeta[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_TAXCLASS];
		if( empty( $default ) )
		{
			$default = WC_Add_Fees::VAL_TAX_STANDARD;
		}
		$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_TAXCLASS );

		$wrapp_cls = ( get_option( 'woocommerce_calc_taxes' ) == 'no' ) ? 'hide_tax' : '';

		$element = array(
			'id' => $name,
			'wrapper_class' => $wrapp_cls,
			'label' =>  __( 'Tax Class:', WC_Add_Fees::TEXT_DOMAIN ),
			'value' => $default,
			'options' => WC_Add_Fees::instance()->tax_classes,
			'description' => __( 'Select the tax class for the additional fee', WC_Add_Fees::TEXT_DOMAIN ),
			'desc_tip' => true,
			);

		woocommerce_wp_select( $element );

		$default = $postmeta[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_ADD_VALUE_TYPE];
		if( empty( $default ) )
		{
			$default = WC_Add_Fees::VAL_INCLUDE_PERCENT;
		}
		$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_ADD_VALUE_TYPE );

		$element = array(
			'id' => $name,
			'wrapper_class' => '',
			'label' =>  __( 'Type of additional fee:', WC_Add_Fees::TEXT_DOMAIN ),
			'value' => $default,
			'options' => WC_Add_Fees::$value_type_to_add,
			'description' => __( 'Select the type of value for the additional fee', WC_Add_Fees::TEXT_DOMAIN ),
			'desc_tip' => true,
			);

		woocommerce_wp_select( $element );

		$default = $postmeta[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_VALUE_TO_ADD];
		$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_VALUE_TO_ADD );

		$element = array(
			'id' => $name,
			'wrapper_class' => '',
			'label' =>  __( 'Value to add:', WC_Add_Fees::TEXT_DOMAIN ),
			'value' => $default,
			'description' => __( 'Enter the value to add to total amount. In case of \'Fixed amount\' enter the value according to your settings in \'Prices entered with/without tax\' . ', WC_Add_Fees::TEXT_DOMAIN ),
			'desc_tip' => true
			);

		woocommerce_wp_text_input( $element );

		$err = $this->woo_addons->get_error_message( $name );
		if( is_array( $err ) )
		{
			$this->echo_error_product( $err );
		}

		$default = $postmeta[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_MAX_VALUE];
		$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_MAX_VALUE );

		$element = array(
			'id' => $name,
			'wrapper_class' => '',
			'label' =>  __( 'Maximum value:', WC_Add_Fees::TEXT_DOMAIN ),
			'value' => $default,
			'description' => __( 'Ignore additional fee, when total amount sold of this product exceeds this maximum value. Leave 0 for unlimited amount. ', WC_Add_Fees::TEXT_DOMAIN ),
			'desc_tip' => true
			);

		woocommerce_wp_text_input( $element );

		$err = $this->woo_addons->get_error_message( $name );
		if( is_array( $err ) )
		{
			$this->echo_error_product( $err );
		}

		echo '</div>';

		return;

	}

	/**
	 * Retrieves our options and saves to DB.
	 *
	 * If Errors occur, values are reset to valid values.
	 *
	 * @param int $post_id
	 */
	public function save_options_product( $post_id )
	{
		$post_meta = WC_Add_Fees::get_post_meta_product_default( $post_id );

		$products = array( $post_id );

		$is_variation_product = isset( $_REQUEST['variable_post_id'] );
		if( $is_variation_product )
		{
			$prods = $_REQUEST['variable_post_id'];
			foreach ( $prods as $prod )
			{
				$products[] = intval( $prod );
			}
		}

		$post_meta[WC_Add_Fees::OPT_ENABLE_PROD] = isset( $_REQUEST[self::ID_ENABLE_ADD_FEES] ) ? 'yes' : 'no';

		foreach( WC_Add_Fees::instance()->gateways as $key => &$gateway )
		{
			$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_ENABLE );
			$checked = isset( $_REQUEST[ $name ] ) ? 'yes' : 'no';
			$post_meta[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_ENABLE] = $checked;

			$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_OUTPUT );
			$value = isset( $_REQUEST[ $name ] ) ? $_REQUEST[ $name ] : '';
			$post_meta[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_OUTPUT] = $value;

			$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_TAXCLASS );
			$value = isset( $_REQUEST[ $name ] ) ? $_REQUEST[ $name ] : WC_Add_Fees::VAL_TAX_STANDARD;
			$post_meta[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_TAXCLASS] = $value;

			$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_ADD_VALUE_TYPE );
			$value = isset( $_REQUEST[ $name ] ) ? $_REQUEST[ $name ] : WC_Add_Fees::VAL_ADD_PERCENT;
			$post_meta[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_ADD_VALUE_TYPE] = $value;

			$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_VALUE_TO_ADD );
			$value = isset( $_REQUEST[ $name ] ) ? $_REQUEST[ $name ] : 0;
			if( $this->check_numeric( $value, $name ) && $this->check_add_value_include( $value, $name, $this->options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_ADD_VALUE_TYPE] ) )
			{
				$post_meta[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_VALUE_TO_ADD] = $value;
			}

			$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_MAX_VALUE );
			$value = isset( $_REQUEST[ $name ] ) ? $_REQUEST[ $name ] : 0;
			if( $this->check_numeric( $value, $name ) )
			{
				$post_meta[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_MAX_VALUE] = $value;
			}
		}

		$keys_remove = array();
		foreach ( $post_meta[WC_Add_Fees::OPT_GATEWAY_PREFIX] as $key => $value )
		{
			if( ! array_key_exists( $key, WC_Add_Fees::instance()->gateways ) )
			{
				$keys_remove[] = $key;
			}
		}

		foreach ( $keys_remove as $key )
		{
			unset ( $post_meta[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ] );
		}

			//	save postmeta for all prducts
		foreach ( $products as $prod_id )
		{
			update_post_meta( $prod_id, WC_Add_Fees::KEY_POSTMETA_PRODUCT, $post_meta );
		}

		return $post_meta;
	}

	/**
	 * Retrieves our options and saves to DB.
	 *
	 * If Errors occur, values are reset to valid values.
	 */
	public function save_options_settings()
	{
		//$checked = isset( $_REQUEST[self::ID_DEL_ON_DEACTIVATE] );
		//$this->options[woocommerce_additional_fees::OPT_DEL_ON_DEACTIVATE] = $checked;

		$checked = isset( $_REQUEST[self::ID_DEL_ON_UNINSTALL] );
		$this->options[WC_Add_Fees::OPT_DEL_ON_UNINSTALL] = $checked;

		//$checked = isset( $_REQUEST[self::ID_ENABLE_ADD_FEES] );
		//$this->options[woocommerce_additional_fees::OPT_ENABLE_ALL] = $checked;

		$checked = isset( $_REQUEST[self::ID_ENABLE_ADD_FEES_PROD] );
		$this->options[WC_Add_Fees::OPT_ENABLE_PROD_FEES] = $checked;

		foreach( WC_Add_Fees::instance()->gateways as $key => &$gateway )
		{
			$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_ENABLE );
			$checked = isset( $_REQUEST[ $name ] );
			$this->options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_ENABLE] = $checked;

			$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_OUTPUT );
			$value = isset( $_REQUEST[ $name ] ) ? stripslashes( $_REQUEST[ $name ] ) : '';
			$this->options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_OUTPUT] = $value;

			$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_TAXCLASS );
			$value = isset( $_REQUEST[ $name ] ) ? stripslashes( $_REQUEST[ $name ] ) : WC_Add_Fees::VAL_TAX_STANDARD;
			$this->options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_TAXCLASS] = $value;

			$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_ADD_VALUE_TYPE );
			$value = isset( $_REQUEST[ $name ] ) ? stripslashes( $_REQUEST[ $name ] ) : WC_Add_Fees::VAL_ADD_PERCENT;
			$this->options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_ADD_VALUE_TYPE] = $value;

			$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_VALUE_TO_ADD );
			$value = isset( $_REQUEST[ $name ] ) ? stripslashes( $_REQUEST[ $name ] ) : 0;
			if( $this->check_numeric( $value, $name ) && $this->check_add_value_include( $value, $name, $this->options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_ADD_VALUE_TYPE] ) )
			{
				$this->options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_VALUE_TO_ADD] = $value;
			}

			$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_VALUE_TO_ADD_FIXED );
			$value = isset( $_REQUEST[ $name ] ) ? stripslashes( $_REQUEST[ $name ] ) : 0;
			if( $this->check_numeric( $value, $name ) )
			{
				$this->options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_VALUE_TO_ADD_FIXED] = $value;
			}
			
			$name = $this->create_unique_html_name( $key, WC_Add_Fees::OPT_KEY_MAX_VALUE );
			$value = isset( $_REQUEST[ $name ] ) ? stripslashes( $_REQUEST[ $name ] ) : 0;
			if( $this->check_numeric( $value, $name ) )
			{
				$this->options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ][WC_Add_Fees::OPT_KEY_MAX_VALUE] = $value;
			}
		}

		$keys_remove = array();
		foreach ( $this->options[WC_Add_Fees::OPT_GATEWAY_PREFIX] as $key => $value )
		{
			if( ! array_key_exists( $key, WC_Add_Fees::instance()->gateways ) )
			{
				$keys_remove[] = $key;
			}
		}

		foreach ( $keys_remove as $key )
		{
			unset ( $this->options[WC_Add_Fees::OPT_GATEWAY_PREFIX][ $key ] );
		}

		update_option( WC_Add_Fees::OPTIONNAME, $this->options );

		return $this->options;
	}

	protected function echo_error_product(array $err )
	{
		echo '<p class="wc_addfees_product_error">';
		echo '<span style = "font-weight:bold; color:#FF0000; margin-left: 150px;">' .esc_attr( $err['message'] ) . '</span>';
		echo '</p>';
	}


	protected function add_error( array $err )
	{
		$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_OPEN,
			'tag' => 'tr',
			'attributes' => array( 'valign' => "top")
			);

		$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_COMPLETE,
			'tag' => 'th',
			'class' => 'titledesc',
			'attributes' => array( 'scope' => "row" )
			);

			//	currently only errors
		$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_OPEN,
			'tag' => 'td',
			'class' => ''
			);

		$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_COMPLETE,
			'tag' => 'span',
			'innerhtml' => $err['message'],
			'attributes' => array( 'style' => "font-weight:bold; color:#FF0000;" )
			);

		$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_CLOSE,
			'tag' => 'td'
			);

		$this->fields[] = array(
			'type' => WC_Addons_Add_Fees::TAG_CLOSE,
			'tag' => 'tr'
			);
	}

	/**
	 *
	 * @param string $value
	 * @param string $field_id
	 */
	protected function check_numeric( &$value, $field_id )
	{
		$value = trim( $value );
		$msg = '';

		if( is_numeric( $value ) )
		{
			try
			{
				$i = (int) $value;
				$i = (double) $value;
				if( $value >= 0 )
				{
					return true;
				}
			}
			catch ( Exception $e )
			{

			}
		}

		$msg = __( 'You must enter a positive number or Zero as number. For decimal point enter ".". ', WC_Add_Fees::TEXT_DOMAIN );
		$this->woo_addons->add_field_error_message( $field_id, $msg );
		return false;
	}

	/**
	 * checks, if the value given is reasonable for VAL_INCLUDE_PERCENT

	 * @param string $value
	 * @param string $field_id
	 * @param string $type_to_add
	 */
	protected function check_add_value_include( &$value, $field_id, $type_to_add )
	{
		if( $type_to_add != WC_Add_Fees::VAL_INCLUDE_PERCENT )
		{
			return true;
		}

		if( $value <= 90 )
		{
			return true;
		}

		$msg = __( '[Value to add] must be less than 90%, when additional fee is included in total amount (Total amount including fee is 100%) . ', WC_Add_Fees::TEXT_DOMAIN );
		$this->woo_addons->add_field_error_message( $field_id, $msg);
		return false;
	}


	/**
	 *
	 * @param string $key
	 * @param string $id
	 * @return string
	 */
	protected function create_unique_html_name( $key, $id)
	{
		return $id . '___' . $key;
	}
}
