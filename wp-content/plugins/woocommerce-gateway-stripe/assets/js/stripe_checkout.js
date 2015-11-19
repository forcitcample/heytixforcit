jQuery( function() {

	var stripe_submit = false;

	jQuery( 'form.checkout' ).on( 'checkout_place_order_stripe', function() {
		return stripeFormHandler();
    });

    jQuery( 'form#order_review' ).submit( function() {
		return stripeFormHandler();
    });

	function stripeFormHandler() {
		if ( stripe_submit ) {
			stripe_submit = false;
			return true;
		}

		if ( ! jQuery( '#payment_method_stripe' ).is( ':checked' ) ) {
			return true;
		}

		if ( jQuery( 'input[name=stripe_card_id]' ).length > 0 && jQuery( 'input[name=stripe_card_id]:checked' ).val() !== 'new' ) {
			return true;
		}

		if ( jQuery( 'input#terms' ).size() === 1 && jQuery( 'input#terms:checked' ).size() === 0 ) {
			alert( wc_stripe_params.i18n_terms );

			return false;
		}

		if ( jQuery( '#createaccount' ).is( ':checked' ) && jQuery( '#account_password' ).length && jQuery( '#account_password' ).val() === '' ) {
			alert( wc_stripe_params.i18n_required_fields );

			return false;
		}
		
		// check to see if we need to validate shipping address
		if ( jQuery( '#ship-to-different-address-checkbox' ).is( ':checked' ) ) {
			$required_inputs = jQuery( '.woocommerce-billing-fields .validate-required, .woocommerce-shipping-fields .validate-required' );
		} else {
			$required_inputs = jQuery( '.woocommerce-billing-fields .validate-required' );
		}

		if ( $required_inputs.size() ) {
			var required_error = false;
			
			$required_inputs.each( function() {
				if ( jQuery( this ).find( 'input.input-text, select' ).not( jQuery( '#account_password' ) ).val() === '' ) {
					required_error = true;
				}
			});

			if ( required_error ) {
				alert( wc_stripe_params.i18n_required_fields );
				return false;
			}
		}

		var $form            = jQuery( 'form.checkout, form#order_review' ),
			$stripe_new_card = jQuery( '.stripe_new_card' ),
			token            = $form.find( 'input.stripe_token' );

		token.val( '' );

		var token_action = function( res ) {
			$form.find( 'input.stripe_token' ).remove();
			$form.append( '<input type="hidden" class="stripe_token" name="stripe_token" value="' + res.id + '"/>' );
			stripe_submit = true;
			$form.submit();
		};

		StripeCheckout.open({
			key:         wc_stripe_params.key,
			address:     false,
			amount:      $stripe_new_card.data( 'amount' ),
			name:        $stripe_new_card.data( 'name' ),
			description: $stripe_new_card.data( 'description' ),
			panelLabel:  $stripe_new_card.data( 'label' ),
			currency:    $stripe_new_card.data( 'currency' ),
			image:       $stripe_new_card.data( 'image' ),
			bitcoin:     $stripe_new_card.data( 'bitcoin' ),
			refund_mispayments: true, // for bitcoin payments let Stripe handle refunds if too little is paid
			email: 		 jQuery( '#billing_email' ).val(),
			token:       token_action
		});

		return false;
	}
});