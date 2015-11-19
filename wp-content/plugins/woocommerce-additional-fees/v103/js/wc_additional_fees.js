/* 
 * Hooks into payment methods radio buttons
 * 
 * depends on woocommerce.js
 */
jQuery(function() {

	var pay_order_page = jQuery('#add_fee_info_pay');

	if(pay_order_page.length !== 0)
	{
			//	bugfix in WC core - ensure, that the payment gateway for the order is selected - not the first one as in core
		var gateway = pay_order_page.attr('add_fee_paymethod');
		var gateway_sel = jQuery('#payment').find("input[name='payment_method']:checked").attr('value');
	
		if(gateway !== gateway_sel)
		{
			jQuery(".payment_methods input[name='payment_method'][value='"+gateway+"']").attr("checked",true);
		}
	}
	
	var checkout = jQuery('body').find('form.checkout');
	
		//	pay for order page - remove not selected radio buttons
	if(checkout.length === 0)
	{
		jQuery('body').find('form#order_review .payment_methods li').each(function (){
			var radio = jQuery(this).find('input.input-radio');
			if((radio.length > 0) && (!radio.is(':checked'))){
				jQuery(this).remove();
			}
		});
	}
	
	jQuery('.payment_methods input.input-radio').live('change', function()
		{
				//	trigger only on checkout page - not on pay for order. Bug fixewd with version 2.0.0
			if(checkout.length > 0)
			{
				jQuery('body').trigger('update_checkout');
			}
		});
		
});