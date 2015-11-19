/*!
 * Screets Chat X Options
 * Author: @screetscom
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */

jQuery(document).ready(function($) {

	// First check if settings reseted correctly! 
	// If not, reset it manually!
	_check = $('input:radio[name=widget_position]:checked').val();
	if( _check === undefined ) {
		window.location.replace( $('.cx-opts-reset').attr('href') );
	}
	// Tooltip
	$('a[rel=tipsy]').tipsy({fade: false, gravity: 's'});

	// Autosize
	$('.cx-autosize').autosize({
		append: ''
	});

	/**
	 * Preview
	 */
	var widget_position,
		base_anim,

		cx_widget = $('.cx-widget'),
		cx_header = $('.cx-header'),
		cx_body = $('.cx-body'),
		cx_btn = $('.cx-chat-btn'),
		cx_form_button = $('.cx-form-btn'),
		cx_online_button = $('.cx-online-btn'),
		cx_offline_button = $('.cx-offline-btn'),
		cx_online = $('.cx-online'),
		cx_offline = $('.cx-offline'),
		cx_popup_reply = $('.cx-reply-input'),
		
		cx_btn_title = $('.cx-chat-btn .cx-title'),
		cx_btn_ico = $('.cx-chat-btn .cx-ico-chat'),
		cx_btn_arrow = $('.cx-chat-btn ._ico-arrow'),

		cx_f_name = $('#CX_offline_row_name'),
		cx_f_email = $('#CX_offline_row_email'),
		cx_f_phone = $('#CX_offline_row_phone'),
		cx_f_msg = $('#CX_offline_row_msg'),
		cx_send_btn = $('#CX_offline_send');
	
		last_preview_mode = 'online_button';


	// Check if we should use white color?
	function cx_use_white(c) {
			c = c.substring(1),      // strip #
			rgb = parseInt(c, 16),   // convert rrggbb to decimal
			r = (rgb >> 16) & 0xff,  // extract red
			g = (rgb >>  8) & 0xff,  // extract green
			b = (rgb >>  0) & 0xff,  // extract blue

			luma = 0.2126 * r + 0.7152 * g + 0.0722 * b; // per ITU-R BT.709

		if ( luma < 180 )
		    return true; // use white
		
		return false; // use black
	}

	// Update preview
	function cx_update_preview() {

		if( _check === undefined ) return;

		var	base_skin = $('input:radio[name=base_skin]:checked').val(),
			widget_size = $('#cx-opts-field-widget_width').val(),
			primary_color = $('#cx-opts-field-primary_color').val(),
			link_color = $('#cx-opts-field-link_color').val(),
			btn_width = $('#cx-opts-field-btn_width').val(),
			when_online = $('#cx-opts-field-when_online').val(),
			when_offline = $('#cx-opts-field-when_offline').val(),
			offline_body = $('#cx-opts-field-offline_body').val(),
			default_radius = $('#cx-opts-field-radius').val() + $('#cx-opts-field-2-radius option:selected').val(),
			reply_pos = $('input:radio[name=reply_pos]:checked').val(),
			popup_reply_ph = $('#cx-opts-field-popup_reply_ph').val(),
			f_name_label = $('#cx-opts-field-f_name_label').val(),
			f_email_label = $('#cx-opts-field-f_email_label').val(),
			f_phone_label = $('#cx-opts-field-f_phone_label').val(),
			f_msg_label = $('#cx-opts-field-f_msg_label').val(),
			send_btn = $('#cx-opts-field-f_send_btn').val(),
			sel_name = $('#cx-opts-field-f_name option:selected').val(),
			sel_email = $('#cx-opts-field-f_email option:selected').val(),
			sel_phone = $('#cx-opts-field-f_phone option:selected').val(),
			comp_avatar = $('#cx-opts-field-default_avatar').val(),
			avatar_size = $('#cx-opts-field-avatar_size').val(),
			avatar_radius = $('#cx-opts-field-avatar_radius').val();
			

		// Update widget position
		widget_position = $('input:radio[name=widget_position]:checked').val().split('-');

		// Default Radius styles
		var radius_bottom = '0 0 ' + default_radius + ' ' + default_radius,
			radius_top = default_radius + ' ' + default_radius + ' 0 0';

		// Update border-radius for fixed skin
		if( base_skin == 'fixed' ) {

			if( widget_position[0] == 'top' ) {
				var radius = radius_bottom;
				radius_top = 0;
			} else {
				var radius = radius_top;
				radius_bottom = 0;
			}
		
		} else {
			var radius = default_radius;
		}

		// Get header foreground
		if( cx_use_white(primary_color) )
			var title_color = '#ffffff';
		else
			var title_color = '#444444';

		// Update standart header
		cx_header.css('color', title_color)
				 .css('background-color', primary_color)
				 .css('border-radius', radius_top);

		// Add reply box class
		cx_online.removeClass('cx-reply-top cx-reply-bottom').addClass('cx-reply-' + reply_pos);

		// First hide reply box
		$('.cx-cnv-reply, .cx-cnv').hide();

		// Display reply box
		$('#CX_reply_' + reply_pos + ', #CX_cnv_' + reply_pos).show();

		// Update body and widget
		cx_body.css('border-radius', radius_bottom);

		// Update chat button width
		cx_btn.css('color', title_color)
				 .css('background-color', primary_color)
				 .css('border-radius', radius);

		// Set button arrow position
		if( widget_position[0] == 'top' )
			cx_btn_arrow.addClass('cx-ico-arrow-down').removeClass('cx-ico-arrow-up');
		else
			cx_btn_arrow.addClass('cx-ico-arrow-up').removeClass('cx-ico-arrow-down');

		// Show button title
		if( $('#cx-opts-field-chat-btn-group-show_title').prop('checked') ) {
			cx_btn_title.show();
			cx_btn.removeClass('cx-no-title');

		} else {
			cx_btn_title.hide();
			cx_btn.addClass('cx-no-title');
		}
		
		// Show button icon
		if( $('#cx-opts-field-chat-btn-group-show_icon').prop('checked') ) {
			cx_btn_ico.show();
			cx_btn.removeClass('cx-no-ico');
		} else {
			cx_btn_ico.hide();
			cx_btn.addClass('cx-no-ico');
		}

		// Show button arrow
		if( $('#cx-opts-field-chat-btn-group-show_arrow').prop('checked') )
			cx_btn_arrow.show();
		else
			cx_btn_arrow.hide();

		// Update popup reply input
		cx_popup_reply.attr('placeholder', popup_reply_ph );

		// Get form send btn foreground
		if( cx_use_white(link_color) )
			var send_btn_fg = '#ffffff';
		else
			var send_btn_fg = '#444444';

		// Update form buttons
		cx_form_button.css('color', send_btn_fg)
					  .css('background-color', link_color);

		// Update window titles
		cx_online.find('.cx-header > .cx-title').html(when_online);
		cx_offline.find('.cx-header > .cx-title').html(when_offline);

		// Update offline body
		cx_offline.find('.cx-offline-form .cx-lead').html(offline_body);

		// Update offline form
		cx_f_name.find('.cx-title').html( f_name_label );
		cx_f_name.find('input').attr( 'placeholder', f_name_label );
		if( sel_name == 'hidden' ) cx_f_name.hide();

		cx_f_email.find('.cx-title').html( f_email_label );
		cx_f_email.find('input').attr( 'placeholder', f_email_label );
		if( sel_email == 'hidden' ) cx_f_email.hide();

		cx_f_phone.find('.cx-title').html( f_phone_label );
		cx_f_phone.find('input').attr( 'placeholder', f_phone_label );
		if( sel_phone == 'hidden' ) cx_f_phone.hide();

		cx_f_msg.find('.cx-title').html( f_msg_label );
		cx_f_msg.find('textarea').attr( 'placeholder', f_msg_label );

		cx_send_btn.html( send_btn );

		// Update button sizes
		if( btn_width > 0 ) {
			cx_online_button.width( btn_width );
			cx_offline_button.width( btn_width );
		} else {
			cx_online_button.width('');
			cx_offline_button.width('');
		}

		
		// Update chat button online title
		cx_online_button.find('.cx-title').html( when_online );

		// Update chat button offline title
		cx_offline_button.find('.cx-title').html( when_offline );

		// Update widget wrapper
		cx_widget.width( widget_size )
				 .css( 'border-radius', default_radius );


		// Company avatar
		if( comp_avatar.length )
			$( '.cx-company-avatar' ).attr( 'src', comp_avatar );

		// Change avatar size
		$('.cx-cnv .cx-avatar, .cx-cnv .cx-avatar.cx-img img').css( 'width', parseInt( avatar_size ) );
		$('.cx-cnv-line:not(.cx-you) .cx-cnv-msg').css( 'margin-left', parseInt(avatar_size)+10 );

		// Change avatar radius
		$('.cx-cnv .cx-avatar.cx-img img').css( 'border-radius', avatar_radius + 'px' );

	}

	// Upgrading from LC
	$('#cx-opts-field-lc_upgrading').on( 'click mouseenter', function() {
		var checked = $('#cx-opts-field-lc_upgrading:checked').val();

		if( checked )
			$('#cx_opt_row_lc_license_key').css('visibility', 'visible');
		else
			$('#cx_opt_row_lc_license_key').css('visibility', 'hidden');

	}).trigger('mouseenter');

	// Animate widget
	$('#cx-opts-field-anim').change(function() {
		// Update base animation
		base_anim = $(this).find('option:selected').val();

		cx_animate();
	});

	// Animate button
	$('#cx-opts-btn-anim').click(function(e) {
		e.preventDefault();
		$('#cx-opts-field-anim').trigger('change');
	});

	// Animate current widget
	function cx_animate() {

		var direction,
			pos = widget_position[0];

		var speed_up = $('#cx-opts-field-anim-group-hinge').prop('checked');

		switch( last_preview_mode ) {
			case 'online_button': 
				var obj = cx_online_button;
				break;

			case 'offline_button': 
				var obj = cx_offline_button;
				break;

			case 'online': 
			case 'offline': 
				var obj = cx_widget;
				break;
		}

		// Up or Down?
		switch(base_anim) {
			case 'bounceIn':
			case 'fadeIn':
				if( pos == 'top' ) 
					direction = 'Down';
				else
					direction = 'Up';
			break;
		}

		if( speed_up )
			obj.addClass('cx-' + base_anim + direction + ' cx-anim');
		else
			obj.addClass('cx-' + base_anim + direction + ' cx-anim cx-hinge');

		// Remove CSS animation
		setTimeout( function() {
			obj.removeClass('cx-anim cx-hinge cx-' + base_anim + direction);
		}, 1500);

	}

	// Update changes
	$('#cx-opts-options-form input, #cx-opts-options-form textarea').on('change keyup blur', function() {
		cx_update_preview();
	});


	// Focus on reply area
	$('.cx-cnv-input').click(function() {
		$(this).find('.cx-reply-input').focus().autosize( { append: '' } );
	});

	// Clean reply box when click enter
	cx_popup_reply.keydown( function( e ) {

		if ( e.keyCode == 13 && !e.shiftKey ) {

			e.preventDefault();

			$(this).val('').trigger('autosize.resize');
		}

	});


	/**
	 * Change preview (WP 3.8+)
	 */
	$('.cx-preview-icons li').click(function() {

		var mode = $(this).data('id');

		// Hide last preview mode
		$('#CX_preview_' + last_preview_mode).removeClass('_visible');

		// Show up current mode
		$('#CX_preview_' + mode).addClass('_visible');

		// Deactivate last icon
		$('#ico_' + last_preview_mode ).removeClass('active');
		
		// Activate icon
		$(this).addClass('active');

		// Update title
		$('#cx_preview_title').html( $(this).attr('title') + ':');

		// Autosize
		if( mode === 'online' )
			$('.cx-reply-input').focus().autosize( { append: '' } ).trigger('autosize.resize');

		// Update last preview mode
		last_preview_mode = mode;

	});

	/**
	 * Change preview (old WP versions)
	 */
	$('.cx-preview').change(function() {

		var mode = $(this).find(':selected').val();
		
		// Hide last preview mode
		$('#CX_preview_' + last_preview_mode).removeClass('_visible');

		// Show up current mode
		$('#CX_preview_' + mode).addClass('_visible');

		// Update last preview mode
		last_preview_mode = mode;
	});


	cx_update_preview();


	// ########## Tabs ##########

	// Nav tab click
	$('#cx-opts-tabs span').click(function(event) {
		// Hide tips
		$('.cx-opts-spin, .cx-opts-success-tip').hide();
		// Remove active class from all tabs
		$('#cx-opts-tabs span').removeClass('nav-tab-active');
		// Hide all panes
		$('.cx-opts-pane').hide();
		// Add active class to current tab
		$(this).addClass('nav-tab-active');
		// Show current pane
		$('.cx-opts-pane:eq(' + $(this).index() + ')').show();
		// Save tab to cookies
		sunriseCreateCookie( pagenow + '_last_tab', $(this).index(), 365 );
	});

	// Auto-open tab by link with hash
	if ( sunriseStrpos( document.location.hash, '#tab-' ) !== false )
		$('#cx-opts-tabs span:eq(' + document.location.hash.replace('#tab-','') + ')').trigger('click');
	// Auto-open tab by cookies
	else if ( sunriseReadCookie( pagenow + '_last_tab' ) != null )
		$('#cx-opts-tabs span:eq(' + sunriseReadCookie( pagenow + '_last_tab' ) + ')').trigger('click');
	// Open first tab by default
	else
		$('#cx-opts-tabs span:eq(0)').trigger('click');
	
	// Check validation
	if( $('#cx-opts-field-license_key').data('valid') === 'valid' ) {
		$('#cx-opts-tabs span').css( 'visibility', 'visible' );
	} else {
		$( '#cx-opts-tabs span:not(:first-child)' ).remove();
		$( '#cx-opts-tabs span:first-child').css('visibility', 'visible').trigger('click'); 
	}

	var working = false,
		btn_text = '';

	/**
	 * Create databases
	 */
	$('#CX_create_db').click(function(e) {
		e.preventDefault();

		var btn = $(this);

		if( working ) return;

		$(this).html( 'Please wait...' );

		$.post( cx.ajax_url + '?action=cx_ajax_callback&mode=create_db', null, function( r ) {

			// Redirect if possible
			if( r.redirect )
				window.location.replace( r.redirect );



		}, 'json' )
		.fail(function (jqXHR) {
			
			// Log error
			console.log(jqXHR);
			
			return false;

		});

	});

	/**
	 * Check security
	 */
	 $('#CX_upd_security').click(function(e) {
		e.preventDefault();

		var btn = $(this);

		if( working ) return;

		$(this).html( 'Please wait...' );

		$.post( cx.ajax_url + '?action=cx_ajax_callback&mode=update_security', null, function( r ) {

			// Redirect if possible
			if( r.redirect )
				window.location.replace( r.redirect );



		}, 'json' )
		.fail(function (jqXHR) {
			
			// Log error
			console.log(jqXHR);
			
			return false;

		});

	});

	/**
	 * Clean realtime data
	 */
	 $('#CX_clean_data').click( function(e) {

	 	e.preventDefault();

		var btn = $(this);

		if( working ) return;

		$(this).html( 'Please wait! This may take awhile...' );

		$.post( cx.ajax_url + '?action=cx_ajax_callback&mode=clean_data', null, function( r ) {

			if( r.success === 1 ) {

				btn.css('color', 'green').html('Cleaned!');

			} else {
				btn.css('color', 'red').html("Something went wrong!");
			}



		}, 'json' )
		.fail(function (jqXHR) {
			
			// Log error
			console.log(jqXHR);
			
			return false;

		});

	 });

	/**
	 * Check if php sessions work
	 */
	 $('#CX_check_sessions').click( function(e) {
		
		e.preventDefault();

		var btn = $(this);

		if( working ) return;

		$(this).html( 'Please wait...' );

		$.post( cx.ajax_url + '?action=cx_ajax_callback&mode=check_sessions', null, function( r ) {

			if( r.success === 1 ) {

				btn.css('color', 'green').html('<span style="color:green">Works!</span>');

			} else {
				btn.css('color', 'green').html("<span style='color:red'>Doesn't work</span>");
				$('#cx_session_ntf').html( "Add the code below into your <strong>wp-config.php</strong> file: <br /><pre>define( 'CX_PHP_SESSIONS', false );</pre> " );
			}



		}, 'json' )
		.fail(function (jqXHR) {
			
			// Log error
			console.log(jqXHR);
			
			return false;

		});

	});

	/**
	 * Clean sessions
	 */
	 $('#CX_clean_sessions').click( function(e) {
		
		e.preventDefault();

		var btn = $(this);

		if( working ) return;

		$(this).html( 'Please wait...' );

		$.post( cx.ajax_url + '?action=cx_ajax_callback&mode=clean_sessions', null, function( r ) {

			// Redirect if possible
			if( r.redirect ) {

				btn.css('color', 'green').html('Cleared successfully!');

				// window.location.replace( r.redirect );

			} else
				btn.html( r.error );



		}, 'json' )
		.fail(function (jqXHR) {
			
			// Log error
			console.log(jqXHR);
			
			return false;

		});

	});


	


	// ########## Ajaxed form ##########

	$('#cx-opts-options-form').ajaxForm({
		beforeSubmit: function() {
			$('.cx-opts-success-tip').hide();
			$('.cx-opts-spin').fadeIn(200);
			$('.cx-opts-submit').attr('disabled', true);
		},
		success: function() {
			$('.cx-opts-spin').hide();
			$('.cx-opts-success-tip').show();
			setTimeout(function() {
				$('.cx-opts-success-tip').fadeOut(200);
			}, 2000);
			$('.cx-opts-submit').attr('disabled', false);

			// Refresh page
			location.reload();
		}
	});


	// ########## Reset settings confirmation ##########

	$('.cx-opts-reset').click(function() {
		if (!confirm($(this).attr('title')))
			return false;
		else
			return true;
	});


	// ########## Notifications ##########

	$('.cx-opts-notification').css({
		cursor: 'pointer'
	}).on('click', function(event) {
		$(this).fadeOut(100, function() {
			$(this).remove();
		});
	});


	// ########## Triggables ##########

	// Select
	$('tr[data-trigger-type="select"] select').each(function(i) {

		var // Input data
		name = $(this).attr('name'),
		index = $(this).find(':selected').index();

		//alert( name + ' - ' + index );

		// Hide all related triggables
		$('tr.cx-opts-triggable[data-triggable^="' + name + '="]').hide();

		// Show selected triggable
		$('tr.cx-opts-triggable[data-triggable="' + name + '=' + index + '"]').show();

		$(this).change(function() {

			index = $(this).find(':selected').index();

			// Hide all related triggables
			$('tr.cx-opts-triggable[data-triggable^="' + name + '="]').hide();

			// Show selected triggable
			$('tr.cx-opts-triggable[data-triggable="' + name + '=' + index + '"]').show();
		});
	});

	// Radio
	$('tr[data-trigger-type="radio"] .cx-opts-radio-group').each(function(i) {

		var // Input data
		name = $(this).find(':checked').attr('name'),
		index = $(this).find(':checked').parent('label').parent('div').index();

		// Hide all related triggables
		$('tr.cx-opts-triggable[data-triggable^="' + name + '="]').hide();

		// Show selected triggable
		$('tr.cx-opts-triggable[data-triggable="' + name + '=' + index + '"]').show();

		$(this).find('input:radio').each(function(i2) {

			$(this).change(function() {

				alert();

				// Hide all related triggables
				$('tr.cx-opts-triggable[data-triggable^="' + name + '="]').hide();

				// Show selected triggable
				$('tr.cx-opts-triggable[data-triggable="' + name + '=' + i2 + '"]').show();
			});
		});
	});


	// ########## Clickouts ##########

	$(document).on('click', function(event) {
		if ( $('.cx-opts-prevent-clickout:hover').length == 0 )
			$('.cx-opts-clickout').hide();
	});


	// ########## Upload buttons ##########

	$('.cx-opts-upload-button').click(function(event) {

		// Define upload field
		window.sunrise_current_upload = $(this).attr('rel');

		// Show thickbox with uploader
		tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');

		// Prevent click
		event.preventDefault();
	});

	window.send_to_editor = function(html) {

		var url;

		if ( jQuery(html).filter('img:first').length > 0 )
			url = jQuery(html).filter('img:first').attr('src');
		else
			url = jQuery(html).filter('a:first').attr('href');

		// Update upload textfield value
		$('#cx-opts-field-' + window.sunrise_current_upload).val(url);

		// Hide thickbox
		tb_remove();
	}


	// ########## Color picker ##########

	$('.cx-opts-color-picker-preview').each(function(index) {
		$(this).farbtastic('.cx-opts-color-picker-value:eq(' + index + ')');
		$('.cx-opts-color-picker-value:eq(' + index + ')').focus(function(event) {
			$('.cx-opts-color-picker-preview').hide();
			$('.cx-opts-color-picker-preview:eq(' + index + ')').show();
		});
	});


	// Check if Woocommerce installed
	if( !cx_opts.wc_installed ) {
		$('#cx-opts-field-display-chatbox-group-woocomerce_pages')
			.attr("disabled", true)
			.parent()
			.css( 'color', '#ccc' );
	}
	
});


// ########## Cookie utilities ##########

function sunriseCreateCookie(name,value,days){
	if(days){
		var date=new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires="; expires="+date.toGMTString()
	}else var expires="";
	document.cookie=name+"="+value+expires+"; path=/"
}
function sunriseReadCookie(name){
	var nameEQ=name+"=";
	var ca=document.cookie.split(';');
	for(var i=0;i<ca.length;i++){
		var c=ca[i];
		while(c.charAt(0)==' ')c=c.substring(1,c.length);
		if(c.indexOf(nameEQ)==0)return c.substring(nameEQ.length,c.length)
	}
	return null
}


// ########## Strpos tool ##########

function sunriseStrpos( haystack, needle, offset) {
	var i = haystack.indexOf( needle, offset );
	return i >= 0 ? i : false;
}