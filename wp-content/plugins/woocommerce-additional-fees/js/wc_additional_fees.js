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
		
		var fixed_gateway = pay_order_page.attr('add_fee_fixed_gateway');
		if(fixed_gateway === 'yes')
		{
			jQuery('body').find('form#order_review .payment_methods li').each(function (){
				var radio = jQuery(this).find('input.input-radio');
				if((radio.length > 0) && (!radio.is(':checked'))){
					jQuery(this).remove();
				}
				return;
			});
		}

		jQuery('.payment_methods input.input-radio').on('change', function()
			{
				jQuery('#addfeeerror').remove();
				
				// Block write panel
				jQuery('.woocommerce').block({message: null, overlayCSS: {background: '#fff url(' + wc_checkout_params.ajax_loader_url + ') no-repeat center', backgroundSize: '16px 16px', opacity: 0.6}});
					  				
				var senddata = {
					action: pay_order_page.attr('add_fee_action'),
					add_fee_order: pay_order_page.attr('add_fee_order'),
					add_fee_pay: pay_order_page.attr('add_fee_pay'),
					add_fee_paymethod: jQuery(this).attr('value'),
					add_fee_key: pay_order_page.attr('add_fee_key'),
					add_fee_nonce: add_fee_vars.add_fee_nonce
				};
				
				jQuery.ajax({
					type: "POST",
					url: add_fee_vars.add_fee_ajaxurl,
					dataType: 'json',
					cache: false,
					data: senddata,
					success	: function(response, textStatus, jqXHR) {	
							if(response.success){
								if(response.recalc) {
									jQuery('.shop_table').replaceWith(response.message);
									jQuery('#payment .form-row').show();
								}
							}
							else {
								jQuery('#payment .form-row').hide();
								alert(response.alert);
								jQuery('#order_review').before(response.message);
							};
						},
					error: function(testObj) {
							jQuery('#payment .form-row').hide();
							alert(add_fee_vars.alert_ajax_error);
						},
					complete: function(test) {
							jQuery('.woocommerce').unblock();
						}
				});
				
			});
			
		return false;
	}
	
	
	

		//	standard checkout page
	jQuery('.woocommerce').on('change', '.payment_methods .input-radio', function()
		{
			jQuery('body').trigger('update_checkout');
		});
	return;

		
	
	
	
		
});