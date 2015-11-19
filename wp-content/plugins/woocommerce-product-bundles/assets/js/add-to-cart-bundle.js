/* jshint -W069 */
/* jshint -W041 */
jQuery( document ).ready( function($) {

	$( 'body' ).on( 'quick-view-displayed', function() {

		$( '.bundle_form .bundle_data' ).each( function() {
			$(this).wc_pb_bundle_form();
		} );
	} );

	$.fn.wc_pb_bundle_form = function() {

		// Listeners

		$( '.bundled_product' )

			/**
			 * Update totals upon changing quantities
			 */
			.on( 'change', 'input.bundled_qty', function( event ) {

				var form = $(this).closest( '.bundle_form' );
				var min  = parseFloat( $(this).attr( 'min' ) );
				var max  = parseFloat( $(this).attr( 'max' ) );

				if ( min >= 0 && parseFloat( $(this).val() ) < min ) {
					$(this).val( min );
				}

				if ( max > 0 && parseFloat( $(this).val() ) > max ) {
					$(this).val( max );
				}

				wc_pb_update_bundle( form );
			} )

			.on( 'change', '.bundled_product_optional_checkbox input', function( event ) {

				var form = $(this).closest( '.bundle_form' );

				if ( $(this).is( ':checked' ) ) {

					$(this).closest( '.bundled_product' ).find( '.bundled_item_optional_content, .bundled_item_cart_content' ).slideDown( 200 );
					$(this).closest( '.bundled_product' ).find( '.cart' ).data( 'optional_status', true );

					// Allow variations script to flip images in bundled_product_images div
					$(this).closest( '.bundled_product' ).find( '.variations_form .variations select' ).trigger( 'change' );

				} else {

					$(this).closest( '.bundled_product' ).find( '.bundled_item_optional_content, .bundled_item_cart_content' ).slideUp( 200 );
					$(this).closest( '.bundled_product' ).find( '.cart' ).data( 'optional_status', false );

					// Reset image in bundled_product_images div
					$(this).closest( '.bundled_product' ).find( '.bundled_product_images' ).addClass( 'images' );
					$(this).closest( '.bundled_product' ).find( '.variations_form' ).trigger( 'reset_image' );
					$(this).closest( '.bundled_product' ).find( '.bundled_product_images' ).removeClass( 'images' );
				}

				wc_pb_update_bundle( form );

				event.stopPropagation();
			} )

			.on( 'found_variation', function( event, variation ) {

				var variations        = $(this).find( '.variations_form' );
				var form              = variations.closest( '.bundle_form' );
				var bundled_item_id   = variations.attr( 'data-bundled_item_id' );

				if ( typeof bundled_item_id === 'undefined' ) {
					bundled_item_id = variations.attr( 'data-bundled-item-id' );
				}

				var bundle_price_data = form.find( '.bundle_data' ).data( 'bundle_price_data' );

				if ( bundle_price_data[ 'per_product_pricing' ] == true ) {
					// put variation price in price table
					bundle_price_data[ 'prices' ][ bundled_item_id ]         = variation.price;
					bundle_price_data[ 'regular_prices' ][ bundled_item_id ] = variation.regular_price;
				}

				form.find( '.bundle_data .bundle_wrap' ).find( 'input.bundle_variation_id_' + bundled_item_id ).val( variation.variation_id ).change();

				for ( var attribute in variation.attributes ) {
					var escaped_attr = attribute.replace( /%/g,'\\%' );
					form.find( '.bundle_data .bundle_wrap' ).find( 'input.bundle_' + escaped_attr + '_' + bundled_item_id ).val( variations.find( 'select[name="' + escaped_attr + '"]' ).val() );
				}

				// Remove .images class from bundled_product_images div in order to avoid styling issues
				$(this).find( '.bundled_product_images' ).removeClass( 'images' );

				wc_pb_update_bundle( form );

				event.stopPropagation();
			} )

			.on( 'reset_image', function( event ) {

				// Remove .images class from bundled_product_images div in order to avoid styling issues
				$(this).find( '.bundled_product_images' ).removeClass( 'images' );

			} )

			.on( 'woocommerce-product-addons-update', function( event ) {

				event.stopPropagation();
			} )

			.on( 'woocommerce_variation_select_focusin', function( event ) {

				event.stopPropagation();
			} )

			.on( 'woocommerce_variation_select_change', function( event ) {

				var cart       = $(this).find( '.cart' );
				var variations = $(this).find( '.variations_form' );
				var form       = variations.closest( '.bundle_form' );

				// Add .images class to bundled_product_images div ( required by the variations script to flip images )
				if ( cart.data( 'optional' ) == false || cart.data( 'optional_status' ) == true ) {
					$(this).find( '.bundled_product_images' ).addClass( 'images' );
				}

				$(this).find( '.variations .attribute-options select' ).each( function() {
					if ( $(this).val() === '' ) {
						variations.find( '.bundled_item_wrap .single_variation' ).html( '' );
						wc_pb_update_bundle( form );
						return false;
					}
				} );

				event.stopPropagation();
			} );


		$( '.bundled_product .cart' )

			.on( 'woocommerce-product-addons-update', function( event ) {

				var item = $(this).closest( '.cart' );
				var form = item.closest( '.bundle_form' );

				wc_pb_update_bundle( form, true );

				event.stopPropagation();
			} )

			.on( 'woocommerce-nyp-updated-item', function( event ) {

				var item    = $(this);
				var form    = item.closest( '.bundle_form' );
				var item_id = item.attr( 'data-bundled_item_id' );
				var nyp     = item.find( '.nyp' );

				if ( typeof item_id === 'undefined' ) {
					item_id = item.attr( 'data-bundled-item-id' );
				}

				if ( nyp.is( ':visible' ) ) {

					var bundle_price_data = form.find( '.bundle_data' ).data( 'bundle_price_data' );

					bundle_price_data[ 'prices' ][ item_id ] = nyp.data( 'price' );

					wc_pb_update_bundle( form );
				}

				event.stopPropagation();
			} );


		$( '.bundle_data' )

			.on( 'woocommerce-nyp-updated-item', function( event ) {

				var item = $(this);
				var form = item.closest( '.bundle_form' );
				var nyp  = item.find( '.nyp' );

				if ( nyp.is( ':visible' ) ) {

					var bundle_price_data = form.find( '.bundle_data' ).data( 'bundle_price_data' );

					bundle_price_data[ 'total' ] = nyp.data( 'price' );

					wc_pb_update_bundle( form );
				}

				event.stopPropagation();
			} )

			.on( 'change', '.bundle_button input.qty', function( event ) {

				var field = $(this);
				var form  = field.closest( '.bundle_form' );

				wc_pb_update_bundle( form );
			} );

		/**
		 * Initial states and loading
		 */

		// Add-ons support: move totals

		var wc_pb_addons_totals = $(this).find( '#product-addons-total' );

		$(this).find( '.bundle_price' ).after( wc_pb_addons_totals );

		if ( $(this).find( '.bundle_wrap p.stock' ).length > 0 ) {
			this.data( 'stock_status', $(this).find( '.bundle_wrap p.stock' ).clone().wrap( '<p>' ).parent().html() );
		}

		// Init variations JS and addons
		$(this).parent().find( '.bundled_product' ).each( function() {

			var cart = $(this).find( '.cart' );

			$(this).find( '.bundled_product_optional_checkbox input' ).change();

			if ( cart.data( 'type' ) == 'variable' && ! cart.hasClass( 'variations_form' ) ) {

				// Initialize variations script
				cart.addClass( 'variations_form' ).wc_variation_form();
				cart.find( '.variations select' ).change();
			}
		} );

		wc_pb_update_bundle( $(this).closest( '.bundle_form' ) );

	};

	/**
	 * Schedules an update of the bundle totals
	 * Uses a dumb scheduler to avoid queueing multiple calls of wc_pb_update_bundle_task - the "scheduler" simply introduces a 50msec execution delay during which all update requests are dropped
	 */
	function wc_pb_update_bundle( form, update_only ) {

		var bundle_data = form.find( '.bundle_data' );

		// Dumb task scheduler
		if ( bundle_data.data( 'update_lock' ) === true ) {
			return false;
		}

		bundle_data.data( 'update_lock', true );

		window.setTimeout( function() {

			wc_pb_update_bundle_task( form, update_only );
			bundle_data.data( 'update_lock', false );

		}, 50 );

	}

	function wc_pb_update_bundle_task( form, update_only ) {

		if ( typeof update_only === 'undefined' ) {
			update_only = false;
		}

		var all_set = true;

		var addons_prices           = {};
		var bundled_item_quantities = {};

		var bundle_price_data = form.find( '.bundle_data' ).data( 'bundle_price_data' );

		// Reset bundle stock status
		if ( typeof( form.find( '.bundle_data' ).data( 'stock_status' ) ) !== 'undefined' ) {
			form.find( '.bundle_data .bundle_wrap p.stock' ).replaceWith( form.find( '.bundle_data' ).data( 'stock_status' ) );
		} else {
			form.find( '.bundle_data .bundle_wrap p.stock' ).remove();
		}

		// Validate bundle

		form.find( '.bundled_product .cart' ).each( function() {

			var cart            = $(this);
			var bundled_item_id = cart.attr( 'data-bundled_item_id' );
			var item_quantity   = cart.find( 'input.qty' ).val();

			if ( typeof bundled_item_id === 'undefined' ) {
				bundled_item_id = cart.attr( 'data-bundled-item-id' );
			}

			// Set quantity based on optional flag
			if ( cart.data( 'optional' ) == true && cart.data( 'optional_status' ) == false ) {
				bundled_item_quantities[ bundled_item_id ] = 0;
			} else {
				bundled_item_quantities[ bundled_item_id ] = item_quantity;
			}

			// Check variable products
			if ( cart.data( 'type' ) == 'variable' && cart.find( '.bundled_item_wrap input[name="variation_id"]' ).val() == '' ) {

				form.find( '.bundle_data .bundle_wrap' ).find( 'input.bundle_variation_id_' + bundled_item_id ).val( '' );

				bundle_price_data[ 'prices' ][ bundled_item_id ]         = 0;
				bundle_price_data[ 'regular_prices' ][ bundled_item_id ] = 0;

				if ( cart.data( 'optional' ) == false || cart.data( 'optional_status' ) == true ) {
					all_set = false;
					return false;
				}
			}

		} );

		if ( all_set ) {

			var bundle_quantity = parseInt( form.find( '.bundle_data .bundle_button input.qty' ).val() );

			form.find( '.bundled_product .cart' ).each( function() {

				var item    = $(this);
				var item_id = item.attr( 'data-bundled_item_id' );

				if ( typeof item_id === 'undefined' ) {
					item_id = item.attr( 'data-bundled-item-id' );
				}

				// Save addons prices
				addons_prices[ item_id ] = 0;

				item.find( '.addon' ).each(function() {
					var addon_cost = 0;

					if ( $(this).is('.addon-custom-price') ) {
						addon_cost = $(this).val();
					} else if ( $(this).is('.addon-input_multiplier') ) {
						if( isNaN( $(this).val() ) || $(this).val() == '' ) { // Number inputs return blank when invalid
							$(this).val('');
							$(this).closest('p').find('.addon-alert').show();
						} else {
							if( $(this).val() != '' ){
								$(this).val( Math.ceil( $(this).val() ) );
							}
							$(this).closest('p').find('.addon-alert').hide();
						}
						addon_cost = $(this).data('price') * $(this).val();
					} else if ( $(this).is('.addon-checkbox, .addon-radio') ) {
						if ( $(this).is(':checked') )
							addon_cost = $(this).data('price');
					} else if ( $(this).is('.addon-select') ) {
						if ( $(this).val() )
							addon_cost = $(this).find('option:selected').data('price');
					} else {
						if ( $(this).val() )
							addon_cost = $(this).data('price');
					}

					if ( ! addon_cost )
						addon_cost = 0;

					addons_prices[ item_id ] = parseFloat( addons_prices[ item_id ] ) + parseFloat( addon_cost );

				} );

				// Store quantity for easy access by 3rd parties
				$(this).data( 'quantity', bundled_item_quantities[ item_id ] );
			} );

			// Unavailable when priced statically and price is undefined
			if ( ( bundle_price_data[ 'per_product_pricing' ] === false ) && ( bundle_price_data[ 'total' ] === '' ) ) {

				wc_pb_hide_bundle( form, wc_bundle_params.i18n_unavailable_text );
				return;
			}

			if ( bundle_price_data[ 'per_product_pricing' ] == true ) {

				bundle_price_data[ 'total' ]         = 0;
				bundle_price_data[ 'regular_total' ] = 0;

				for ( var item_id_ppp in bundle_price_data[ 'prices' ] ) {

					bundle_price_data[ 'total' ]         += ( parseFloat( bundle_price_data[ 'prices' ][ item_id_ppp ] ) + parseFloat( addons_prices[ item_id_ppp ] ) ) * bundled_item_quantities[ item_id_ppp ];
					bundle_price_data[ 'regular_total' ] += ( parseFloat( bundle_price_data[ 'regular_prices' ][ item_id_ppp ] ) + parseFloat( addons_prices[ item_id_ppp ] ) ) * bundled_item_quantities[ item_id_ppp ];
				}

			} else {

				bundle_price_data[ 'total_backup' ]         = parseFloat( bundle_price_data[ 'total' ] );
				bundle_price_data[ 'regular_total_backup' ] = parseFloat( bundle_price_data[ 'regular_total' ] );

				for ( var item_id_sp in addons_prices ) {

					bundle_price_data[ 'total' ]         += parseFloat( addons_prices[ item_id_sp ] ) * bundled_item_quantities[ item_id_sp ];
					bundle_price_data[ 'regular_total' ] += parseFloat( addons_prices[ item_id_sp ] ) * bundled_item_quantities[ item_id_sp ];
				}
			}

			var bundle_addon = form.find( '.bundle_data #product-addons-total' );

			if ( bundle_addon.length > 0 ) {
				bundle_addon.data( 'price', bundle_price_data[ 'total' ] );
				form.find( '.bundle_data' ).trigger( 'woocommerce-product-addons-update' );
			}

			var per_product_priced_composite = false;

			if ( bundle_price_data[ 'bundle_is_composited' ] === true ) {

				var composite_price_data     = form.closest( '.composite_form' ).find( '.composite_data' ).data( 'price_data' );
				per_product_priced_composite = composite_price_data[ 'per_product_pricing' ];

				wc_bundle_params.i18n_total = wc_bundle_params.i18n_subtotal;
			}


			if ( bundle_price_data[ 'per_product_pricing' ] === true || ( bundle_price_data[ 'bundle_is_composited' ] === true && per_product_priced_composite === true ) ) {

				if ( bundle_price_data[ 'total' ] == 0 && bundle_price_data[ 'show_free_string' ] == true ) {

					form.find( '.bundle_data .bundle_price' ).html( '<p class="price"><span class="total">' + wc_bundle_params.i18n_total + '</span>' + wc_bundle_params.i18n_free + '</p>' );

				} else {

					var sales_price_format   = wc_pb_woocommerce_number_format( wc_pb_number_format( bundle_quantity * bundle_price_data[ 'total' ] ) );
					var regular_price_format = wc_pb_woocommerce_number_format( wc_pb_number_format( bundle_quantity * bundle_price_data[ 'regular_total' ] ) );

					if ( bundle_price_data[ 'regular_total' ] > bundle_price_data[ 'total' ] ) {
						form.find( '.bundle_data .bundle_price' ).html( '<p class="price">' + bundle_price_data[ 'price_string' ].replace( '%s', '<span class="total">' + wc_bundle_params.i18n_total + '</span><del>' + regular_price_format + '</del> <ins>' + sales_price_format + '</ins>' ) + '</p>' );
					} else {
						form.find( '.bundle_data .bundle_price' ).html( '<p class="price">' + bundle_price_data[ 'price_string' ].replace( '%s', '<span class="total">' + wc_bundle_params.i18n_total + '</span>' + sales_price_format ) + '</p>' );
					}

					// Display recurring price html data for optional bundled subs
					var bundled_subs = form.find( '.bundled_product .cart[data-type="subscription"]' );

					if ( bundled_subs.length > 0 ) {
						bundled_subs.each( function() {

							var cart            = $(this);
							var bundled_item_id = cart.attr( 'data-bundled_item_id' );

							if ( typeof bundled_item_id === 'undefined' ) {
								bundled_item_id = cart.attr( 'data-bundled-item-id' );
							}

							if ( bundled_item_quantities[ bundled_item_id ] > 0 ) {
								form.find( '.bundle_data .bundle_price .bundled_subscriptions_price_html' ).show();
								form.find( '.bundle_data .bundle_price .bundled_sub_price_html_' + bundled_item_id ).show().addClass( 'visible' );
							}
						} );

						form.find( '.bundle_data .bundle_price .bundled_subscriptions_price_html .bundled_sub_price_html.visible' ).first().find( '.plus' ).hide();
					}
				}

			} else {
				form.find( '.bundle_data .bundle_price' ).html( '' );
			}

			// set bundle stock status as out of stock if any selected variation is out of stock
			form.find( '.bundled_product .cart' ).each( function() {

				if ( $(this).data( 'optional' ) == true && $(this).data( 'optional_status' ) == false ) {
					return true;
				}

				var $item_stock_p = $(this).find( 'p.stock' );

				if ( $item_stock_p.hasClass( 'out-of-stock' ) ) {

					if ( form.find( '.bundle_data .bundle_wrap p.stock' ).length > 0 ) {
						form.find( '.bundle_data .bundle_wrap p.stock' ).replaceWith( $item_stock_p.clone().html( wc_bundle_params.i18n_partially_out_of_stock ) );
					} else {
						form.find( '.bundle_data .bundle_wrap .bundle_price' ).after( $item_stock_p.clone().html( wc_bundle_params.i18n_partially_out_of_stock ) );
					}
				}

				if ( $item_stock_p.hasClass( 'available-on-backorder' ) && ! form.find( '.bundle_data .bundle_wrap p.stock' ).hasClass( 'out-of-stock' ) ) {

					if ( form.find( '.bundle_data .bundle_wrap p.stock' ).length > 0 ) {
						form.find( '.bundle_data .bundle_wrap p.stock' ).replaceWith( $item_stock_p.clone().html( wc_bundle_params.i18n_partially_on_backorder ) );
					} else {
						form.find( '.bundle_data .bundle_wrap .bundle_price' ).after( $item_stock_p.clone().html( wc_bundle_params.i18n_partially_on_backorder ) );
					}
				}

			} );

			if ( form.find( '.bundle_data .bundle_wrap p.stock' ).hasClass( 'out-of-stock' ) ) {
				form.find( '.bundle_data .bundle_button button' ).prop( 'disabled', true ).addClass( 'disabled' );
			} else {
				form.find( '.bundle_data .bundle_button button' ).prop( 'disabled', false ).removeClass( 'disabled' );
			}

			// Show price and add-to-cart button

			var button_behaviour = form.find( '.bundle_data' ).data( 'button_behaviour' );

			if ( button_behaviour !== 'new' ) {
				form.find( '.bundle_data .bundle_wrap' ).slideDown( 200 );
			}

			form.find( '.bundle_data .bundle_wrap' ).trigger( 'woocommerce-product-bundle-show' );

			// Composite product compatibility - Save price
			form.find( '.component_data' ).data( 'price', bundle_price_data[ 'total' ] );
			form.find( '.component_data' ).data( 'regular_price', bundle_price_data[ 'regular_total' ] );

			// Composite product compatibility - Save state
			form.find( '.component_data' ).data( 'component_set', true );

			// Restore initial values
			bundle_price_data[ 'total' ]         = bundle_price_data[ 'total_backup' ];
			bundle_price_data[ 'regular_total' ] = bundle_price_data[ 'regular_total_backup' ];

		} else {

			if ( ! update_only ) {
				wc_pb_hide_bundle( form );
			}
		}

		form.find( '.bundle_data .bundle_wrap' ).trigger( 'woocommerce-composited-product-update' );
	}

	function wc_pb_hide_bundle( form, hide_message ) {

		if ( typeof hide_message == 'undefined' ) {
			hide_message = wc_bundle_params.i18n_select_options;
		}

		// Composite products compatibility
		form.find( '.component_data' ).data( 'component_set', false );

		var button_behaviour = form.find( '.bundle_data' ).data( 'button_behaviour' );

		if ( button_behaviour == 'new' ) {

			form.find( '.bundle_data .bundle_price' ).html( hide_message );
			form.find( '.bundle_data .bundle_button button' ).prop( 'disabled', true ).addClass( 'disabled' );

		} else {

			form.find( '.bundle_data .bundle_wrap' ).slideUp( 200 );
		}

		form.find( '.bundle_data .bundle_wrap' ).trigger( 'woocommerce-product-bundle-hide' );
	}

	/**
	 * Helper functions for variations
	 */

	function wc_pb_attempt_show_bundle( form, update_only ) {
		wc_pb_update_bundle( form, update_only );
	}

	function wc_pb_woocommerce_number_format( price ) {

		var remove     = wc_bundle_params.currency_format_decimal_sep;
		var position   = wc_bundle_params.currency_position;
		var symbol     = wc_bundle_params.currency_symbol;
		var trim_zeros = wc_bundle_params.currency_format_trim_zeros;
		var decimals   = wc_bundle_params.currency_format_num_decimals;

		if ( trim_zeros == 'yes' && decimals > 0 ) {
			for (var i = 0; i < decimals; i++) { remove = remove + '0'; }
			price = price.replace( remove, '' );
		}

		var price_format = '';

		if ( position == 'left' )
			price_format = '<span class="amount">' + symbol + price + '</span>';
		else if ( position == 'right' )
			price_format = '<span class="amount">' + price + symbol +  '</span>';
		else if ( position == 'left_space' )
			price_format = '<span class="amount">' + symbol + ' ' + price + '</span>';
		else if ( position == 'right_space' )
			price_format = '<span class="amount">' + price + ' ' + symbol +  '</span>';

		return price_format;
	}

	function wc_pb_number_format( number ) {

		var decimals      = wc_bundle_params.currency_format_num_decimals;
		var decimal_sep   = wc_bundle_params.currency_format_decimal_sep;
		var thousands_sep = wc_bundle_params.currency_format_thousand_sep;

	    var n = number, c = isNaN(decimals = Math.abs(decimals)) ? 2 : decimals;
	    var d = decimal_sep == undefined ? ',' : decimal_sep;
	    var t = thousands_sep == undefined ? '.' : thousands_sep, s = n < 0 ? '-' : '';
	    var i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + '', j = (j = i.length) > 3 ? j % 3 : 0;

	    return s + (j ? i.substr(0, j) + t : '') + i.substr(j).replace(/(\d{3})(?=\d)/g, '$1' + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : '');
	}


	$( '.bundle_form .bundle_data' ).each( function() {

		$(this).wc_pb_bundle_form();

	} );

} );
