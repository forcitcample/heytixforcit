<?php
/**
 *
 *
 * @author Schoenmann Guenter
 */
class wc_panel_admin
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
	 * Pointer to global object
	 *
	 * @var $woocommerce_additional_fees
	 */
	public $woocommerce_additional_fees;


	/**
	 *
	 * @var woocommerce_addons_add_fees
	 */
	public $woo_addons;


	public function __construct(array $options, woocommerce_additional_fees $woocommerce_additional_fees, woocommerce_addons_add_fees $woo_addons)
	{
		$this->options = $options;
		$this->fields = array();
		$this->woocommerce_additional_fees = $woocommerce_additional_fees;
		$this->woo_addons = $woo_addons;
	}

	public function __destruct()
	{
		unset($this->options);
		unset($this->fields);
		unset($this->woocommerce_additional_fees);
		unset($this->woo_addons);
	}

	/**
	* Returns the HTML output for the single product tab section
	*
	* @return string
	*/
	public function echo_form_fields_product(array $postmeta)
	{
	    echo '<div id="add_fees_product_data" class="panel woocommerce_options_panel">';

	    $err_cnt = $this->woo_addons->count_errors();
	    if($err_cnt > 0)
	    {
			$msg = sprintf( __( '%1$d Error(s) have been found and were reset to original value. Please check your entries.', woocommerce_additional_fees::TEXT_DOMAIN ), $err_cnt );
			$element = array(
						'type' => woocommerce_addons_add_fees::TAG_COMPLETE,
						'tag' => 'div',
						'class' => 'error',
						'innerhtml' => $msg
					);
			$this->woo_addons->echo_html_string($element);
	    }


	    $this->fields = array();
	    echo '<div id="wc_add_fees_product_container" class="add_fees_link_section">';

	    $this->get_link_list_fields(false);
	    foreach ($this->fields  as &$element)
	    {
			$this->woo_addons->echo_html_string($element);
	    }
	    unset ($element);

	    $element = array(
					'type' => woocommerce_addons_add_fees::TAG_OPEN,
					'tag' => 'div',
					'id' => self::PREFIX_SECTION_LINKS.'Basic_Settings',
					'class' => 'section'
				);
		$this->woo_addons->echo_html_string($element);

		$chk = array(
			'id' => self::ID_ENABLE_ADD_FEES,
			'wrapper_class' => '',
			'label' => __('Activate for this product:', woocommerce_additional_fees::TEXT_DOMAIN),
			'value' => $postmeta[woocommerce_additional_fees::OPT_ENABLE_PROD],
			'description' => __('Check to activate additional fees for this product. You must also enable additional fees for all products in &quot;Basic Settings&quot; in WooCommerce Settings Page. If unchecked, all gateway settings for this product are ignored.', woocommerce_additional_fees::TEXT_DOMAIN)
			);
		woocommerce_wp_checkbox($chk);
		echo '</div>';		//	id="....Basic_Settings"

		foreach($this->woocommerce_additional_fees->gateways as $k => &$value)
		{
			$this->echo_form_fields_product_gateways($k, $value, $postmeta);
		}
		unset($value);

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
			'type' => woocommerce_addons_add_fees::TAG_OPEN,
			'tag' => 'div',
			'id' => 'wc_add_fees_settings_container',
			'class' => 'subsubsub_section'
			);

		$err_cnt = $this->woo_addons->count_errors();
		if($err_cnt > 0)
		{
			$msg = sprintf( __( '%1$d Error(s) have been found and were reset to original value. Please check your entries.', woocommerce_additional_fees::TEXT_DOMAIN ), $err_cnt );
			$this->fields[] = array(
				'type' => woocommerce_addons_add_fees::TAG_COMPLETE,
				'tag' => 'div',
				'class' => 'error',
				'innerhtml' => $msg
			);
		}

		$this->get_link_list_fields(false);
		$this->get_basic_setting_fields();
		foreach($this->woocommerce_additional_fees->gateways as $k => &$value)
		{
			$this->get_gateway_inputfields($k, $value);
		}

		//	surrounding container end
		$this->fields[] = array(
			'type' => woocommerce_addons_add_fees::TAG_CLOSE,
			'tag' => 'div'
			);

		return $this->fields;
	}

	/**
	 * Adds the gateway link list to $this->fields[]
	 *
	 * @param bool $standard_class
	 */
	protected function get_link_list_fields($standard_class = true)
	{
		$nr_gateways = count($this->woocommerce_additional_fees->gateways);
		$seperator = '|';

		$standard_class ? $cl = 'subsubsub' : $cl = 'add_fees_ul';
		$this->fields[] = array(
			'type' => woocommerce_addons_add_fees::TAG_OPEN,
			'tag' => 'ul',
			'class' => $cl
			);

		$this->fields[] = array(
			'type' => woocommerce_addons_add_fees::TAG_OPEN,
			'tag' => 'li',
			'class' => 'add_fees_basic_link'
			);

		$s = '';
		if($nr_gateways > 0)
		{
			$s = ' '.$seperator;
		}

		$this->fields[] = array(
			'type' => woocommerce_addons_add_fees::TAG_COMPLETE,
			'tag' => 'a',
			'class' => 'current',
			'href' => '#'.self::PREFIX_SECTION_LINKS.'Basic_Settings',
			'innerhtml' => __('Basic Settings', woocommerce_additional_fees::TEXT_DOMAIN).$s
			);

		$this->fields[] = array(
			'type' => woocommerce_addons_add_fees::TAG_CLOSE,
			'tag' => 'li'
			);

		$count = 1;
		foreach($this->woocommerce_additional_fees->gateways as $k => &$gateway)
		{
			$s = $gateway->title;
			if($count < $nr_gateways)
			{
				$s .= ' '.$seperator;
			}

			$this->fields[] = array(
			'type' => woocommerce_addons_add_fees::TAG_OPEN,
			'tag' => 'li'
			);

			$this->fields[] = array(
			'type' => woocommerce_addons_add_fees::TAG_COMPLETE,
			'tag' => 'a',
			'href' => '#'.self::PREFIX_SECTION_LINKS.sanitize_title(str_replace('%', '', $gateway->title)),
			'innerhtml' => $s
			);

			$this->fields[] = array(
			'type' => woocommerce_addons_add_fees::TAG_CLOSE,
			'tag' => 'li'
			);
			$count++;
		}
		unset ($gateway);

		$this->fields[] = array(
			'type' => woocommerce_addons_add_fees::TAG_CLOSE,
			'tag' => 'ul'
			);

		$this->fields[] = array(
			'type' => woocommerce_addons_add_fees::TAG_STANDALONE,
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
			'type' => woocommerce_addons_add_fees::TAG_OPEN,
			'tag' => 'div',
			'id' => self::PREFIX_SECTION_LINKS.'Basic_Settings',
			'class' => 'section'
			);

		$this->fields[] = array(
			'type' => 'title',
			'id' => 'ips_basic_settings',
			'title' => __('Basic settings for WooCommerce additional fees plugin', woocommerce_additional_fees::TEXT_DOMAIN)
			);

		if(woocommerce_additional_fees::$show_activation)
		{
			$checked = 'no';
			if($this->options[woocommerce_additional_fees::OPT_DEL_ON_DEACTIVATE])
			{
				$checked = 'yes';
			}
			$this->fields[] = array(
				'type' => 'checkbox',
				'id' => self::ID_DEL_ON_DEACTIVATE,
				'default' => $checked,
				'desc' => __('Delete options on deactivate', woocommerce_additional_fees::TEXT_DOMAIN),
				'desc_tip' => __('Check to delete options on deactivate. Does not remove any fees in existing orders.', woocommerce_additional_fees::TEXT_DOMAIN)
					);
		}

		if(woocommerce_additional_fees::$show_uninstall)
		{
			$checked = 'no';
			if($this->options[woocommerce_additional_fees::OPT_DEL_ON_UNINSTALL])
			{
				$checked = 'yes';
			}
			$this->fields[] = array(
				'type' => 'checkbox',
				'id' => self::ID_DEL_ON_UNINSTALL,
				'default' => $checked,
				'desc' => __('Delete options on uninstall', woocommerce_additional_fees::TEXT_DOMAIN),
				'desc_tip' => __('Check to delete options on uninstall. Does not remove any fees in existing orders.', woocommerce_additional_fees::TEXT_DOMAIN)
					);
		}

		/*
		$checked = 'no';
		if($this->options[woocommerce_additional_fees::OPT_ENABLE_ALL])
		{
			$checked = 'yes';
		}
		$this->fields[] = array(
			'type' => 'checkbox',
			'id' => self::ID_ENABLE_ADD_FEES,
			'default' => $checked,
			'desc' => __('Enable additional fees calculation for the shop', woocommerce_additional_fees::TEXT_DOMAIN),
			'desc_tip' => __('Check to enable additional fees calculation for the shop. If not checked, no additional fees will be applied. All other settings are ignored.', woocommerce_additional_fees::TEXT_DOMAIN)
				);
		*/

		$checked = 'no';
		if($this->options[woocommerce_additional_fees::OPT_ENABLE_PROD_FEES])
		{
			$checked = 'yes';
		}
		$this->fields[] = array(
			'type' => 'checkbox',
			'id' => self::ID_ENABLE_ADD_FEES_PROD,
			'default' => $checked,
			'desc' => __('Enable additional fees calculation for products', woocommerce_additional_fees::TEXT_DOMAIN),
			'desc_tip' => __('Check to enable additional fees calculation for products in the shop. If not checked, all settings on product level will be ignored.', woocommerce_additional_fees::TEXT_DOMAIN)
				);

		$this->fields[] = array(
			'type' => 'sectionend'
			);

		$this->fields[] = array(
			'type' => woocommerce_addons_add_fees::TAG_CLOSE,
			'tag' => 'div'
			);
	}

	/**
	 * Initialise gateway inputfields to be able to load them from request
	 * Namefields are appended with '___$key'
	 */
	public function get_gateway_inputfields($key, $gateway)
	{
		$this->fields[] = array(
			'type' => woocommerce_addons_add_fees::TAG_OPEN,
			'tag' => 'div',
			'id' => self::PREFIX_SECTION_LINKS.sanitize_title(str_replace('%', '', $gateway->title)),
			'class' => 'section'
			);


		$s = __('Additional Fee Settings for Payment Gateway:', woocommerce_additional_fees::TEXT_DOMAIN);
		$s .= ' '.$gateway->title.' ['.$gateway->id.']';

		$this->fields[] = array(
			'type' => 'title',
			'id' => 'ips_gateway_settings',
			'title' => $s
				);

		$t1 = __('Status: ', woocommerce_additional_fees::TEXT_DOMAIN);
		$yes = __('Payment gateway is enabled.', woocommerce_additional_fees::TEXT_DOMAIN);
		$no = __('Payment gateway is disabled', woocommerce_additional_fees::TEXT_DOMAIN);

		($gateway->enabled == 'yes') ? $t2 = $yes : $t2 = $no;
		if($gateway->enabled == 'yes')
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
			'type' => woocommerce_addons_add_fees::TAG_COMPLETE,
			'tag' => 'span',
			'innerhtml' => $t1.$t2,
			'attributes' => $attr
			);


		$checked = 'no';
		if($this->options[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_ENABLE])
		{
			$checked = 'yes';
		}
		$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_ENABLE);

		$this->fields[] = array(
			'type' => 'checkbox',
			'id' => $name,
			'default' => $checked,
			'desc' => __('Enable additional fees on total cart value for this gateway', woocommerce_additional_fees::TEXT_DOMAIN),
			'desc_tip' => __('Check to enable additional fees to be added based on the total cart value for this gateway. The fee is applied to overall cart value also including all fees added on product level.', woocommerce_additional_fees::TEXT_DOMAIN)
				);

		$default = $this->options[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_OUTPUT];
		$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_OUTPUT);
		$this->fields[] = array(
			'type' => 'text',
			'id' => $name,
			'title' => __('Output text:', woocommerce_additional_fees::TEXT_DOMAIN),
			'default' => $default,
			'desc' => __('Enter Text for Fee to display as explanation to Fee', woocommerce_additional_fees::TEXT_DOMAIN),
			'desc_tip' => true,
			'css' => "width:300px;"
					);


		$default = $this->options[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_TAXCLASS];
		if(empty($default))
		{
			$default = woocommerce_additional_fees::VAL_TAX_STANDARD;
		}
		$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_TAXCLASS);
		$this->fields[] = array(
			'type' => 'select',
			'id' => $name,
			'title' => __('Tax Class:', woocommerce_additional_fees::TEXT_DOMAIN),
			'default' => $default,
			'options' => $this->woocommerce_additional_fees->tax_classes,
			'desc' => __('Select the tax class for the additional fee', woocommerce_additional_fees::TEXT_DOMAIN),
			'desc_tip' => true
			);


		$default = $this->options[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_ADD_VALUE_TYPE];
		if(empty($default))
		{
			$default = woocommerce_additional_fees::VAL_INCLUDE_PERCENT;
		}
		$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_ADD_VALUE_TYPE);
		$this->fields[] = array(
			'type' => 'select',
			'id' => $name,
			'title' => __('Type of additional fee:', woocommerce_additional_fees::TEXT_DOMAIN),
			'default' => $default,
			'options' => woocommerce_additional_fees::$value_type_to_add,
			'desc' => __('Select the type of value for the additional fee', woocommerce_additional_fees::TEXT_DOMAIN),
			'desc_tip' => true
			);

		$default = $this->options[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_VALUE_TO_ADD];
		$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_VALUE_TO_ADD);
		$this->fields[] = array(
			'type' => 'number',
			'custom_attributes' => array('step'=>'any'),
			'id' => $name,
			'title' => __('Value to add:', woocommerce_additional_fees::TEXT_DOMAIN),
			'default' => $default,
			'desc' => __('Enter the value to add to total amount, In case of \'Fixed amount\' enter the value according to your settings in \'Prices entered with/without tax\'.', woocommerce_additional_fees::TEXT_DOMAIN),
			'desc_tip' => true
			);

		$err = $this->woo_addons->get_error_message($name);
		if(is_array($err))
		{
			$this->add_error($err);
		}

		$default = $this->options[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_MAX_VALUE];
		$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_MAX_VALUE);
		$this->fields[] = array(
			'type' => 'number',
			'custom_attributes' => array('step'=>'any'),
			'id' => $name,
			'title' => __('Maximum cart value for adding fee:', woocommerce_additional_fees::TEXT_DOMAIN),
			'default' => $default,
			'desc' => __('Ignore additional fee, when total amount exceeds value. Leave 0 for unlimited amount.', woocommerce_additional_fees::TEXT_DOMAIN),
			'desc_tip' => true
			);

		$err = $this->woo_addons->get_error_message($name);
		if(is_array($err))
		{
			$this->add_error($err);
		}

		$this->fields[] = array(
			'type' => 'sectionend'
			);

		$this->fields[] = array(
			'type' => woocommerce_addons_add_fees::TAG_CLOSE,
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
	protected function echo_form_fields_product_gateways($key, $gateway, array $postmeta)
	{
		$element = array(
			'type' => woocommerce_addons_add_fees::TAG_OPEN,
			'tag' => 'div',
			'id' => self::PREFIX_SECTION_LINKS.sanitize_title($gateway->title),
			'class' => 'section'
			);

		$this->woo_addons->echo_html_string($element);

		$s = __('Additional Fee Settings for Payment Gateway:', woocommerce_additional_fees::TEXT_DOMAIN);
		$s .= ' '.$gateway->title.' ['.$gateway->id.']';

		echo '<h4>' . esc_html( $s ) . '</h4>';


		$t1 = __('Status: ', woocommerce_additional_fees::TEXT_DOMAIN);
		$yes = __('Payment gateway is enabled.', woocommerce_additional_fees::TEXT_DOMAIN);
		$no = __('Payment gateway is disabled', woocommerce_additional_fees::TEXT_DOMAIN);

		($gateway->enabled == 'yes') ? $t2 = $yes : $t2 = $no;
		if($gateway->enabled == 'yes')
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
			'type' => woocommerce_addons_add_fees::TAG_COMPLETE,
			'tag' => 'span',
			'innerhtml' => $t1.$t2,
			'attributes' => $attr
			);

		$this->woo_addons->echo_html_string($element);

		$checked = $postmeta[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_ENABLE];
		if($checked != 'yes')
		{
			$checked = 'no';
		}
		$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_ENABLE);

		$element = array(
			'id' => $name,
			'wrapper_class' => '',
			'label' =>  __('Enable additional fees:', woocommerce_additional_fees::TEXT_DOMAIN),
			'value' => $checked,
			'description' => __('Check to enable Additional fees calculation for this product and gateway. Additional fees are added based on the value of this product.', woocommerce_additional_fees::TEXT_DOMAIN)
			);
		woocommerce_wp_checkbox($element);

		$default = $postmeta[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_OUTPUT];
		$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_OUTPUT);
		$element = array(
			'id' => $name,
			'wrapper_class' => '',
			'label' =>  __('Output text:', woocommerce_additional_fees::TEXT_DOMAIN),
			'value' => $default,
			'description' => __('Enter Text for Fee to display as explanation to Fee', woocommerce_additional_fees::TEXT_DOMAIN),
			'desc_tip' => true,
			'custom_attributes' => array(
						'style' => 'width:300px;'
					)
			);

		woocommerce_wp_text_input($element);

		$default = $postmeta[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_TAXCLASS];
		if(empty($default))
		{
			$default = woocommerce_additional_fees::VAL_TAX_STANDARD;
		}
		$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_TAXCLASS);

		$wrapp_cls = (get_option( 'woocommerce_calc_taxes' ) == 'no') ? 'hide_tax' : '';

		$element = array(
			'id' => $name,
			'wrapper_class' => $wrapp_cls,
			'label' =>  __('Tax Class:', woocommerce_additional_fees::TEXT_DOMAIN),
			'value' => $default,
			'options' => $this->woocommerce_additional_fees->tax_classes,
			'description' => __('Select the tax class for the additional fee', woocommerce_additional_fees::TEXT_DOMAIN),
			'desc_tip' => true,
			);

		woocommerce_wp_select($element);


		$default = $postmeta[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_ADD_VALUE_TYPE];
		if(empty($default))
		{
			$default = woocommerce_additional_fees::VAL_INCLUDE_PERCENT;
		}
		$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_ADD_VALUE_TYPE);

		$element = array(
			'id' => $name,
			'wrapper_class' => '',
			'label' =>  __('Type of additional fee:', woocommerce_additional_fees::TEXT_DOMAIN),
			'value' => $default,
			'options' => woocommerce_additional_fees::$value_type_to_add,
			'description' => __('Select the type of value for the additional fee', woocommerce_additional_fees::TEXT_DOMAIN),
			'desc_tip' => true,
			);

		woocommerce_wp_select($element);

		$default = $postmeta[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_VALUE_TO_ADD];
		$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_VALUE_TO_ADD);

		$element = array(
			'id' => $name,
			'wrapper_class' => '',
			'label' =>  __('Value to add:', woocommerce_additional_fees::TEXT_DOMAIN),
			'value' => $default,
			'description' => __('Enter the value to add to total amount. In case of \'Fixed amount\' enter the value according to your settings in \'Prices entered with/without tax\'.', woocommerce_additional_fees::TEXT_DOMAIN),
			'desc_tip' => true
			);

		woocommerce_wp_text_input($element);

		$err = $this->woo_addons->get_error_message($name);
		if(is_array($err))
		{
			$this->echo_error_product($err);
		}

		$default = $postmeta[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_MAX_VALUE];
		$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_MAX_VALUE);

		$element = array(
			'id' => $name,
			'wrapper_class' => '',
			'label' =>  __('Maximum value:', woocommerce_additional_fees::TEXT_DOMAIN),
			'value' => $default,
			'description' => __('Ignore additional fee, when total amount sold of this product exceeds this maximum value. Leave 0 for unlimited amount.', woocommerce_additional_fees::TEXT_DOMAIN),
			'desc_tip' => true
			);

		woocommerce_wp_text_input($element);

		$err = $this->woo_addons->get_error_message($name);
		if(is_array($err))
		{
			$this->echo_error_product($err);
		}

		echo '</div>';

		return;

	}

	/**
	 * Retrieves our options and saves to DB.
	 *
	 * If Errors occur, values are reset to valid values.
	 *
	 * @param array $post_meta
	 */
	public function save_options_product($post_id)
	{
		$post_meta = woocommerce_additional_fees::get_post_meta_product_default($post_id);

		$products = array($post_id);

		$is_variation_product = isset($_REQUEST['variable_post_id']);
		if($is_variation_product)
		{
			$prods = $_REQUEST['variable_post_id'];
			foreach ($prods as $prod)
			{
				$products[] = intval($prod);
			}
		}

		$checked = isset($_REQUEST[self::ID_ENABLE_ADD_FEES]);
		$checked ? $chk = 'yes' : $chk = 'no';
		$post_meta[woocommerce_additional_fees::OPT_ENABLE_PROD] = $chk;

		foreach($this->woocommerce_additional_fees->gateways as $key => &$gateway)
		{
			$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_ENABLE);
			$checked = isset($_REQUEST[$name]);
			$checked ? $chk = 'yes' : $chk = 'no';
			$post_meta[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_ENABLE] = $chk;

			$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_OUTPUT);
			isset($_REQUEST[$name]) ? $value = $_REQUEST[$name] : $value = '';
			$post_meta[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_OUTPUT] = $value;

			$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_TAXCLASS);
			isset($_REQUEST[$name]) ? $value = $_REQUEST[$name] : $value = woocommerce_additional_fees::VAL_TAX_STANDARD;
			$post_meta[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_TAXCLASS] = $value;

			$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_ADD_VALUE_TYPE);
			isset($_REQUEST[$name]) ? $value = $_REQUEST[$name] : $value = woocommerce_additional_fees::VAL_ADD_PERCENT;
			$post_meta[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_ADD_VALUE_TYPE] = $value;

			$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_VALUE_TO_ADD);
			isset($_REQUEST[$name]) ? $value = $_REQUEST[$name] : $value = 0;
			if($this->check_numeric($value, $name) && $this->check_add_value_include($value, $name, $this->options[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_ADD_VALUE_TYPE]))
			{
				$post_meta[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_VALUE_TO_ADD] = $value;
			}

			$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_MAX_VALUE);
			isset($_REQUEST[$name]) ? $value = $_REQUEST[$name] : $value = 0;
			if($this->check_numeric($value, $name))
			{
				$post_meta[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_MAX_VALUE] = $value;
			}
		}

		$keys_remove = array();
		foreach ($post_meta[woocommerce_additional_fees::OPT_GATEWAY_PREFIX] as $key => $value)
		{
			if(!array_key_exists($key, $this->woocommerce_additional_fees->gateways))
			{
				$keys_remove[] = $key;
			}
		}

		foreach ($keys_remove as $key)
		{
			unset ($post_meta[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key]);
		}

			//	save postmeta for all prducts
		foreach ($products as $prod_id)
		{
			update_post_meta($prod_id, woocommerce_additional_fees::KEY_POSTMETA_PRODUCT, $post_meta);
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
		//$checked = isset($_REQUEST[self::ID_DEL_ON_DEACTIVATE]);
		//$this->options[woocommerce_additional_fees::OPT_DEL_ON_DEACTIVATE] = $checked;

		$checked = isset($_REQUEST[self::ID_DEL_ON_UNINSTALL]);
		$this->options[woocommerce_additional_fees::OPT_DEL_ON_UNINSTALL] = $checked;

		//$checked = isset($_REQUEST[self::ID_ENABLE_ADD_FEES]);
		//$this->options[woocommerce_additional_fees::OPT_ENABLE_ALL] = $checked;

		$checked = isset($_REQUEST[self::ID_ENABLE_ADD_FEES_PROD]);
		$this->options[woocommerce_additional_fees::OPT_ENABLE_PROD_FEES] = $checked;

		foreach($this->woocommerce_additional_fees->gateways as $key => &$gateway)
		{
			$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_ENABLE);
			$checked = isset($_REQUEST[$name]);
			$this->options[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_ENABLE] = $checked;

			$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_OUTPUT);
			isset($_REQUEST[$name]) ? $value = stripslashes($_REQUEST[$name]) : $value = '';
			$this->options[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_OUTPUT] = $value;

			$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_TAXCLASS);
			isset($_REQUEST[$name]) ? $value = stripslashes($_REQUEST[$name]) : $value = woocommerce_additional_fees::VAL_TAX_STANDARD;
			$this->options[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_TAXCLASS] = $value;

			$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_ADD_VALUE_TYPE);
			isset($_REQUEST[$name]) ? $value = stripslashes($_REQUEST[$name]) : $value = woocommerce_additional_fees::VAL_ADD_PERCENT;
			$this->options[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_ADD_VALUE_TYPE] = $value;

			$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_VALUE_TO_ADD);
			isset($_REQUEST[$name]) ? $value = stripslashes($_REQUEST[$name]) : $value = 0;
			if($this->check_numeric($value, $name) && $this->check_add_value_include($value, $name, $this->options[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_ADD_VALUE_TYPE]))
			{
				$this->options[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_VALUE_TO_ADD] = $value;
			}

			$name = $this->create_unique_html_name($key, woocommerce_additional_fees::OPT_KEY_MAX_VALUE);
			isset($_REQUEST[$name]) ? $value = stripslashes($_REQUEST[$name]) : $value = 0;
			if($this->check_numeric($value, $name))
			{
				$this->options[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key][woocommerce_additional_fees::OPT_KEY_MAX_VALUE] = $value;
			}
		}

		$keys_remove = array();
		foreach ($this->options[woocommerce_additional_fees::OPT_GATEWAY_PREFIX] as $key => $value)
		{
			if(!array_key_exists($key, $this->woocommerce_additional_fees->gateways))
			{
				$keys_remove[] = $key;
			}
		}

		foreach ($keys_remove as $key)
		{
			unset ($this->options[woocommerce_additional_fees::OPT_GATEWAY_PREFIX][$key]);
		}

		update_option(woocommerce_additional_fees::OPTIONNAME, $this->options);

		return $this->options;
	}

	protected function echo_error_product(array $err)
	{
		echo '<p class="wc_addfees_product_error">';
		echo '<span style = "font-weight:bold; color:#FF0000; margin-left: 150px;">'.esc_attr( $err['message'] ).'</span>';
		echo '</p>';
	}


	protected function add_error(array $err)
	{
		$this->fields[] = array(
			'type' => woocommerce_addons_add_fees::TAG_OPEN,
			'tag' => 'tr',
			'attributes' => array('valign' => "top")
			);

		$this->fields[] = array(
			'type' => woocommerce_addons_add_fees::TAG_COMPLETE,
			'tag' => 'th',
			'class' => 'titledesc',
			'attributes' => array('scope' => "row")
			);

			//	currently only errors
		$this->fields[] = array(
			'type' => woocommerce_addons_add_fees::TAG_OPEN,
			'tag' => 'td',
			'class' => ''
			);

		$this->fields[] = array(
			'type' => woocommerce_addons_add_fees::TAG_COMPLETE,
			'tag' => 'span',
			'innerhtml' => $err['message'],
			'attributes' => array('style' => "font-weight:bold; color:#FF0000;")
			);

		$this->fields[] = array(
			'type' => woocommerce_addons_add_fees::TAG_CLOSE,
			'tag' => 'td'
			);

		$this->fields[] = array(
			'type' => woocommerce_addons_add_fees::TAG_CLOSE,
			'tag' => 'tr'
			);
	}

	/**
	 *
	 * @param string $value
	 * @param string $field_id
	 */
	protected function check_numeric(&$value, $field_id)
	{
		$value = trim($value);
		$msg = '';

		if(is_numeric($value))
		{
			try
			{
				$i = (int) $value;
				$i = (double)$value;
				if($value >= 0)
					return true;
			}
			catch (Exception $e)
			{

			}
		}

		$msg = __('You must enter a positive number or Zero as number. For decimal point enter ".".', woocommerce_additional_fees::TEXT_DOMAIN);
		$this->woo_addons->add_field_error_message($field_id, $msg);
		return false;
	}

	/**
	 *
	 * @param string $value
	 * @param string $field_id
	 * @param string $type_to_add
	 */
	protected function check_add_value_include(&$value, $field_id, $type_to_add)
	{
		if($type_to_add != woocommerce_additional_fees::VAL_INCLUDE_PERCENT)
		{
			return true;
		}

		if($value <= 90)
		{
			return true;
		}

		$msg = __('[Value to add] must be less than 90%, when additional fee is included in total amount (Total amount including fee is 100%).', woocommerce_additional_fees::TEXT_DOMAIN);
		$this->woo_addons->add_field_error_message($field_id, $msg);
		return false;
	}


	/**
	 *
	 * @param string $key
	 * @param string $id
	 * @return string
	 */
	protected function create_unique_html_name($key, $id)
	{
		return $id.'___'.$key;
	}
}

?>
