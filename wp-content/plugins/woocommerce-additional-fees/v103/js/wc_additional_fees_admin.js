
jQuery(function() {
	
		
	jQuery('#wc_add_fees_product_container a').on('click', function(){
			var container = jQuery(this).closest('#wc_add_fees_product_container');
			var href = jQuery(this).attr('href');
			container.find('a').removeClass('current');
			jQuery(this).addClass('current');
			container.find('.section').hide();
			container.find(href).show();
			return false;
	});
	
	jQuery('#wc_add_fees_settings_container a').on('click', function(){
			var container = jQuery(this).closest('#wc_add_fees_settings_container');
			var href = jQuery(this).attr('href');
			container.find('a').removeClass('current');
			jQuery(this).addClass('current');
			container.find('.section').hide();
			container.find(href).show();
			return false;
	});
	
	jQuery('#wc_add_fees_product_container a:eq(1)').trigger('click');
	jQuery('#wc_add_fees_settings_container a:eq(1)').trigger('click');
});

