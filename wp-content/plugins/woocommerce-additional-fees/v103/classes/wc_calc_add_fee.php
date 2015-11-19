<?php
/**
 * This class stores information about the calculation of an additional fee line.
 * This can be used to reconstruct the calculation and show information about it to the user.
 *
 * @author Guenter
 */
class WC_calc_add_fee
{
	const VAL_MANUAL_ADD_FEE = 'manual';
	const VAL_TOTAL_CART_ADD_FEE = 'total_cart';
	const VAL_PRODUCT_ADD_FEE = 'product';

	/**
	 * Enumeration to specify type of fee
	 *
	 * @var string
	 */
	public $type;

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
		$this->type = self::VAL_MANUAL_ADD_FEE;
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
		unset ($this->gateway_option);
		unset ($this->taxes);
	}

}

?>