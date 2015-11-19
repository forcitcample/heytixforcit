<?php
/**
 * This class stores information about the calculation of an additional fee line.
 * This can be used to reconstruct the calculation and show information about it to the user.
 *
 * @author Guenter Schoenmann
 * 
 * previous class: wc_calc_add_fee
 */
if ( ! defined( 'ABSPATH' ) )  {  exit;  }   // Exit if accessed directly

class WC_Fee_Add_Fees
{
	const VAL_MANUAL_ADD_FEE = 'manual';
	const VAL_TOTAL_CART_ADD_FEE = 'total_cart';
	const VAL_PRODUCT_ADD_FEE = 'product';

	/**
	 * Needed to make fee unique, as WC core only allows fees to be added once in cart (name = fee id)
	 * 
	 * @var string
	 */
	public $id;
	
	/**
	 * Source that created the entry (can be used to seperate fees from different plugins)
	 * 
	 * @var string
	 */
	public $source;
	
	/**
	 * Enumeration to specify type of fee
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Array of corresponding item keys in the order (not used in cart) which were used for this entry
	 * 
	 * @var array 
	 */
	public $order_item_id;

	/**
	 *
	 * @var string
	 */
	public $gateway_key;

	/**
	 *
	 * @var string
	 */
	public $gateway_title;

	/**
	 * Option array with information about calculation parameters at the time of checkout (may be
	 * different from actual settings as the basis settings can be changed at any time)
	 *
	 * @var array
	 */
	public $gateway_option;

	/**
	 * Title of product post
	 *
	 * @var string
	 */
	public $product_desc;

	/**
	 *
	 * @var float
	 */
	public $amount_no_tax;

	/**
	 *
	 * @var float
	 */
	public $tax_amount;

	/**
	 *
	 * @var float
	 */
	public $amount_incl_tax;

	/**
	 * Tax array returned by WooCommerce
	 *
	 * @var array
	 */
	public $taxes;

	/**
	 *
	 * @var bool
	 */
	public $taxable;


	public function __construct()
	{
		$this->id = '';
		$this->source = '';
		$this->type = self::VAL_MANUAL_ADD_FEE;
		$this->order_item_id = array();
		$this->gateway_key = '';
		$this->gateway_title = '';
		$this->gateway_option = null;
		$this->product_desc = '';
		$this->amount_no_tax = 0.0;
		$this->amount_incl_tax = 0.0;
		$this->tax_amount = 0.0;
		$this->taxes = array();
		$this->taxable = false;
	}

	public function __destruct()
	{
		unset( $this->order_item_id );
		unset( $this->gateway_option );
		unset( $this->taxes );
	}

}
