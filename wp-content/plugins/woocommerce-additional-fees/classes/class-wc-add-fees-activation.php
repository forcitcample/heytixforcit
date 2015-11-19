<?php
/**
 * Handles activation and deactivation of this plugin
 *
 * @author Schoenmann Guenter
 * @version 1.0.0.0
 */
if ( ! defined( 'ABSPATH' ) )  {  exit;  }   // Exit if accessed directly

class WC_Add_Fees_Activation
{

	/**
	 * Holds the options for this plugin
	 *
	 * @var array
	 */
	public $options;

	public function __construct()
	{
		$this->options = array();
	}

	public function __destruct()
	{
		unset ( $this->options );
	}

	/**
	 * Called, when Plugin activated.
	 *
	 * Creates or updates the options to latest version
	 */
	public function on_activate()
	{
		//	We need WC -> if WC is not active, do not allow to activate the plugin, because we cannot load the correct version (backward compatibility)
		if ( ! function_exists( 'is_plugin_active' ) ) 
		{
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
		if( ! is_plugin_active( 'woocommerce/woocommerce.php' ) )
		{
			deactivate_plugins( WC_Add_Fees::$plugin_base_name );
			wp_die( __( '<p>The plugin <strong>WooCommerce Additional Fees</strong> needs the plugin WooCommerce to be able to be activated. Please activate this plugin first. Plugin could not be activated.</p>', WC_Add_Fees::TEXT_DOMAIN ), __( 'Plugin Activation Error', WC_Add_Fees::TEXT_DOMAIN ),  array( 'response'=> 200, 'back_link' => TRUE ) );
		}
		
		$this->options = WC_Add_Fees::get_options_default();

			//	ensure to save options first time after activation
		update_option( WC_Add_Fees::OPTIONNAME, $this->options );
	}

	/**
	 * Checks for OPT_DEL_ON_DEACTIVATE -> removes option
	 */
	public function on_deactivate()
	{
		$this->options = get_option( WC_Add_Fees::OPTIONNAME, array() );

		//	fallback only
		if( empty( $this->options ) )
		{
			return;
		}

		//	fallback - default behaviour if not exist
		if( isset( $this->options[WC_Add_Fees::OPT_DEL_ON_DEACTIVATE] ) && $this->options[WC_Add_Fees::OPT_DEL_ON_DEACTIVATE] )
		{
			delete_option( WC_Add_Fees::OPTIONNAME );
		}
	}

	/**
	 * Checks for OPT_DEL_ON_UNINSTALL -> removes option
	 *
	 */
	public function on_uninstall()
	{
			//	don't use get_options_default(), because it might have been deleted on deactivate
		$this->options = get_option( WC_Add_Fees::OPTIONNAME, array() );

		//	already deleted on deactivation
		if( empty( $this->options ) )
		{
			return;
		}

		//	fallback - - default behaviour if not exist - Delete in any case to clean up database
		if(( ! isset( $this->options[WC_Add_Fees::OPT_DEL_ON_UNINSTALL] ) ) || $this->options[WC_Add_Fees::OPT_DEL_ON_UNINSTALL] )
		{
			delete_option( WC_Add_Fees::OPTIONNAME );
		}
	}
}
