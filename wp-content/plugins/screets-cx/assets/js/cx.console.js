/*!
 * Screets Chat X Console
 * Author: @screetscom
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial software,  only  users  who have purchased a valid
 * license  and  accept to the terms of the  License Agreement can install
 * and use this program.
 */

(function ($) {
	
	$(document).ready(function() {

		var last_cnv_id = null,
			last_user_id = null,
			last_msg_id = null,
			ls_ntf = $('#CX_ls_ntf'),
			conn_btn = $('#CX_connect'),
			checked_user_ids = [];

		// Show welcome popup
		fn_show_welcome_popup = function() {

			// Use default popup content
			$('#CX_popup_cnv').removeClass()
							  .addClass( 'cx-popup-content cx-welcome' )
							  .empty();

		}

		/**
		 * Use CX plugin
		 */
		$('body').cx({
			app_id 			: cx.app_id,
			user_info 		: {
				id 		: cx.user_id,
				name 	: cx.user_name,
				email 	: cx.user_email
			},
			render 			: false,
			company_avatar 	: cx.company_avatar,
			debug 			: true,

			// Before load
			before_load: function() {

				var self = this;

				// Update login form data
				this.data.current_form = {
					name 		: cx.user_name,
					email 		: cx.user_email,
					gravatar 	: cx.user_email_hash
				}

				conn_btn.click(function(e) {
					e.preventDefault();
					
					// Display "Connecting" message
					ls_ntf.show().html( self.opts.msg.connecting + '...' );

					// If already connected, don't try it again
					if( !$(this).data( 'logged' ) )
						self.login( true );
					
					else if( $(this).data( 'status', 'online' ) ) {
						self.be_offline();
					} 
					
				});
			},


			// Current user is offline
			offline : function() {

				// Play sound
				this.play_sound( 'cx-disconnected' );

				// Display "Connected" message
				ls_ntf.html( "You're offline!" );

				// Show offline button
				conn_btn.html( '<span class="cx-ico cx-ico-online" style="color:#e54045;"></span> ' + this.opts.msg.offline_btn ).data('logged', 0).data('status', 'offline').addClass('cx-offline').removeClass('cx-online button-disabled');

				// Update console class
				$('#CX_console').removeClass( 'cx-online' ).addClass( 'cx-offline' );

			},

			// Authentication error
			auth_error : function( error ) {

				// Enable button
				conn_btn.removeClass('button-disabled');
				
				// Display error
				ls_ntf.hide().html( error.message ).fadeIn(200);

			},

			// Authenticated in Firebase, not logged in yet
			auth : function() {
				
				// Display "Connected" message
				ls_ntf.html( cx.msgs.you_offline );

				// Show connect button
				conn_btn.html( cx.msgs.connect ).data('logged', 0).addClass('cx-offline').removeClass('cx-online button-disabled');

			},

			// Logged in successfully
			logged_in: function( user ) {

				// Play sound
				this.play_sound( 'cx-connected' );

				// Listen messages
				this.listen_msgs();

				// Enable button
				conn_btn.removeClass('button-disabled');
				
				// Hide notification on users list
				ls_ntf.hide().empty();

				// Update online button
				conn_btn.html( '<span class="cx-ico cx-ico-online" style="color:#33cc33"></span> ' + this.opts.msg.online_btn ).data('logged', 1).data('status', 'online').addClass('cx-online').removeClass('cx-offline button-disabled');

				// Update console class
				$('#CX_console').addClass( 'cx-online' ).removeClass( 'cx-offline' );

			},

			// Logged out
			logged_out: function( logout_msg ) {

				// Play sound
				this.play_sound( 'cx-disconnected' );
				
				// Display "Connected" message
				ls_ntf.html( "Logged out!" );

				// Show connect button
				conn_btn.html( cx.msgs.connect ).data('logged', 0).data('status', 'offline').addClass('cx-offline').removeClass('cx-online button-disabled');

				// Show welcome popup
				fn_show_welcome_popup();

				// Update console class
				$('#CX_console').removeClass( 'cx-online' ).addClass( 'cx-offline' );

			},

			// New user is online now
			user_online : function( user ) {

				if( $.inArray( user.id, checked_user_ids ) && user.id != this.data.user.id ) { // If operator didn't logged out itself

					// Play sound
					this.play_sound( 'cx-online' );

					// Notify user
					this.notify( cx.msgs.new_user_online, user.name + ' (' + user.type + ')', null, 'user_online' );

					// Add user in checked users
					checked_user_ids.push( user.id );

				}


			},

			// A user appeared offline
			user_offline : function( user ) {

				// Play sound if operator didn't logged out itself
				if( user.id != this.data.user.id )
					this.play_sound( 'cx-offline' );

			},

			// New message sent to any online user
			new_msg : function( msg ) {

				var obj_user = $( '#CX_ls_usr_' + msg.user_id ),
					obj_count = obj_user.find( '.cx-count' ),
					total_msg = parseInt( obj_user.data( 'count' ) );

				// Update total msg if it isn't old message and not user's own message
				if( !msg.old_msg && !msg.first_load && msg.user_id != this.data.user.id ) {

					total_msg = total_msg + 1;

					// Update user item in the list
					obj_user.addClass( 'cx-new-msg' ).data( 'count', total_msg );

					// Update count
					obj_count.html( '(' + total_msg + ')' );

					// Play sound
					this.play_sound( 'cx-new-msg' );

					// Notify user
					this.notify( cx.msgs.new_msg, msg.name + ': ' + msg.msg, null, 'new_msg' );

				}

				// Update current conversation area
				if( this.data.user.cnv_id == msg.cnv_id ) {

					// Remove notification
					$( '#CX_load_msg_0' ).remove();
					
					// Render message
					this.add_msg( msg, last_user_id, last_msg_id );

					// Update last user id
					last_user_id = msg.user_id;

					// Update last message id
					if( last_user_id != msg.user_id || !last_msg_id )
						last_msg_id = msg.msg_id;

				}

			},

			// Conversation messages loaded
			cnv_msgs_loaded : function( total_msgs ) {
				
				if( !total_msgs )
					$( '#CX_load_msg_0' ).html( this.opts.msg.no_msg + '.' ); // No messages found
				else
					$( '#CX_load_msg_0' ).empty(); // Hide load msg in anyway

			},

			// After load
			after_load : function() {

				var self = this,
					working = false;

				// Autosize reply input when focussed
				$(document).on('focus', '#CX_cnv_reply_0', function() {
					$(this).autosize({
						append: ''
					});
				});

				/**
				 * When click user on the users list
				 */
				$( document ).on( 'click', '#CX_users li', function() {

					var obj_user = $(this);

					// Get user data
					self.get_user_data( $(this).data( 'id' ), function( user ) {

						// Deactivate last active user
						if( self.data.active_user_id )
							$( '#CX_ls_usr_' + self.data.active_user_id ).removeClass( 'cx-active' );

						// Clean highlights and count
						obj_user.addClass( 'cx-active' ).removeClass( 'cx-new-msg' ).data( 'count', 0 ).find( '.cx-count' ).empty();

						// Update current conversation
						$( '#CX_tab_username' ).html( obj_user.data( 'name' ) );

						// Popup params
						var popup_data = {
							box_id 		: 0,
							reply_ph 	: self.opts.msg.reply_ph,
							load_msg 	: self.opts.msg.please_wait + '...'
						};

						// Show user popup
						$('#CX_popup_cnv').removeClass('cx-welcome').html( self.render( 'online-basic', popup_data ) );

						// Autosize reply input
						$('#CX_cnv_reply_0').focus();

						// Prepare user info sidebar
						if( obj_user.data( 'id' ) === self.data.user.id ) { // User opened itself conversation

							// Don't show meta tools
							var user_html = '<ul><li><strong>IP</strong> <span class="cx-user-meta-ip">' + user.ip + '</span></li><li><strong>User Info</strong><span class="cx-user-meta-email"><a href="mailto:' + user.email + '">' + user.email + '</a></span></li></ul>';
						
						} else {
							
							var user_html = '<div class="cx-user-meta-tools"><button id="CX_save" data-cnv-id="' + obj_user.data( 'cnv-id' ) + '" class="button button-small cx-ico cx-ico-save"></button> <button id="CX_end_chat" data-cnv-id="' + obj_user.data( 'cnv-id' ) + '" class="button button-small cx-tooltip">' + self.opts.msg.end_chat + '<span>' + cx.msgs.save_end_chat + '</span></button><br /><small id="CX_user_meta_ntf"></small></div> <small class="description" style="display: block; color:#999; margin-bottom: 15px;">' + cx.msgs.save_note + '</small><ul><li><strong>IP</strong> <span class="cx-user-meta-ip">' + user.ip + '</span></li><li><strong>User Info</strong><span class="cx-user-meta-email"><a href="mailto:' + user.email + '">' + user.email + '</a></span></li></ul>';
						}

						// Update additional info
						if( user.current_page )
							user_html = user_html + '<div id="CX_popup_info_0"><strong><i class="cx-ico-preview"></i> Active Page:</strong><a id="CX_active_page" href="' + user.current_page + '" target="_blank"><span>' + user.current_page + '</span></a></div>';

						// Update user info
						$('#CX_cnv_user_info_0').html( user_html );


						// Set conversation area
						self.objs.cnv = $( '#CX_cnv_' + self.data.box_id );

						// Update current conversation id
						// Now operator's current conversation is the same with the user operator talks
						self.data.user.cnv_id = obj_user.data('cnv-id');

						// Set last active user
						self.data.active_user_id = obj_user.data('id');


						// Reload conversation
						self.reload_cnv( obj_user.data('cnv-id') );

						// Manage reply box
						self.manage_reply_box( last_cnv_id );

						// Update last conversation id
						last_cnv_id = obj_user.data('cnv-id');


						// Resize window
						$(window).trigger('resize');
						
					});

				});


				/**
				 * End chat
				 */
				$(document).on( 'click', '#CX_save, #CX_end_chat', function(e) {

					var btn = $(this),
						ntf = $('#CX_user_meta_ntf'),
						delete_from_app = $(this).attr('id') === 'CX_end_chat' ? true : false;

					// Don't allow more than one clicks
					if( working ) {
						ntf.html( self.opts.msg.please_wait + '...' ); // Waiting for the next request
						return;
					}

					working = true;

					// Disable button
					$(this).addClass( 'button-disabled' );

					// Display saving notification on user metabar
					ntf.html( cx.msgs.saving + '...' );

					// Save user data
					self.save_user_data( $(this).data( 'cnv-id' ), delete_from_app, function( r ) {

						working = false; // Not working anymore

						// Reactivate button
						btn.removeClass( 'button-disabled' );

						// Update notification
						if( r.error )
							ntf.html( r.error );
						else
							ntf.html( r.msg ); // Successfully saved!

						// Clean notification after a while
						setTimeout( function() {

							ntf.fadeOut(2000);

						}, 1000 );

						// Show welcome popup if user session ended
						if( delete_from_app ) {

							setTimeout( function() {

								fn_show_welcome_popup();

							}, 3000 );

						}

					});

				});

				// Remove active user highlight when visitor already mouseover on the conversation
				$('#CX_popup_content').mouseover( function() {

					// Remove highlight and reset count
					$('#CX_ls_usr_' + self.data.active_user_id ).removeClass( 'cx-new-msg' )
													  .data( 'count', 0 ) // Reset count
													  .find( '.cx-count' ).empty();

					
				});


				// Update last online times every minute
				setInterval( function() {

					$( '.cx-last-online' ).each( function( i ) {

						$(this).html( self.timeago( $(this).data( 'time' ) ) );

					});

				}, 60000 );

				window.onbeforeunload = function (e) {
					var e = e || window.event;

					//IE & Firefox
					if (e) {
						e.returnValue = cx.msgs.ntf_close_console;
					}

					// For Safari
					return cx.msgs.ntf_close_console;
				};

			}

		});


		// Align layout
		$(window).resize(function() {

			var win_h = $(window).height();
			var popup_h = win_h - 103;

			// Set tab content heights
			$('#CX_wall .cx-popup-content').height( popup_h );

			// Set conversation popup height
			$('#CX_cnv_0').height( popup_h - 91 );
			
			// Set user meta height
			$('#CX_cnv_user_info_0').height( popup_h - 77 );

			// Set max-height of users list
			$('#CX_users').css( 'max-height', win_h - 201 );

			// Current tab id
			var tab_id = $('#CX_tabs .cx-active a').attr('href');

			// Reply input height
			var reply_h = $(tab_id).find('.cx-cnv-reply').height() + 50;

			// Set height of popups
			$('.cx-cnv').height( popup_h - reply_h );

		}).trigger('resize');



	});

} (window.jQuery || window.Zepto));