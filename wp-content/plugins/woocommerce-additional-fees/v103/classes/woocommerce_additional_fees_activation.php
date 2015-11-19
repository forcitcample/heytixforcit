<?php
/**
 * Handles activation and deactivation of this plugin
 *
 * Always load this class to ensure a fallback to default option values
 *
 * @author Schoenmann Guenter
 * @version 1.0.0.0
 */
class woocommerce_additional_fees_activation
{

	/**
	 * Holds the options for this plugin
	 *
	 * @var array
	 */
	var $options;

	public function __construct()
	{
		$this->options = array();
	}

	public function __destruct()
	{
		unset ($this->options);
	}

	/**
	 * Called, when Plugin activated.
	 *
	 * Creates or updates the options to latest version
	 */
	public function on_activate()
	{
		$this->options = woocommerce_additional_fees::get_options_default();

			//	ensure to save options first time after activation
		update_option(woocommerce_additional_fees::OPTIONNAME, $this->options);
	}

	/**
	 * Checks for OPT_DEL_ON_DEACTIVATE -> removes option
	 */
	public function on_deactivate()
	{
		$this->options = woocommerce_additional_fees::get_options_default();

		//	fallback only
		if(empty($this->options))
		{
			return;
		}

		//	fallback - Delete only, if exists
		if($this->options[woocommerce_additional_fees::OPT_DEL_ON_DEACTIVATE])
		{
			delete_option(woocommerce_additional_fees::OPTIONNAME);
		}

	}


	/**
	 * Checks for OPT_DEL_ON_UNINSTALL -> removes option
	 *
	 */
	public function on_uninstall()
	{
			//	don't use get_options_default(), because it might have been deleted on deactivate
		$this->options = get_option(woocommerce_additional_fees::OPTIONNAME, array());

		//	already deleted on deactivation
		if(empty($this->options))
		{
			return;
		}

		//	fallback - Delete in any case to clean up
		if((!isset($this->options[woocommerce_additional_fees::OPT_DEL_ON_UNINSTALL])) || $this->options[woocommerce_additional_fees::OPT_DEL_ON_UNINSTALL])
		{
			delete_option(woocommerce_additional_fees::OPTIONNAME);
		}
	}

}

?>