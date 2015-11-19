/*!
 * Screets Chat X plugin (for 1.8/9+)
 * Author: @screetscom
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */

;(function ( $, window, document, undefined ) {
	
	var CX = "cx",

		// The name of using in .data()
		dataPlugin = "plugin_" + CX,

		// Default options
		defaults = {
			app_id 				: "", 			// App ID

			render 				: true, 		// Render chatbox UI?
			display_login 		: true, 		// Display login form?
			disable_on_mobile 	: false, 		// Disable on mobile devices

			notify_by_email 	: false,

			btn_view 			: {				// Button appearance
				show_title: true,
				show_icon: false,
				show_arrow: false
			},

			users_list_id 		: "#CX_users", 	// List users in HTML element or leave it blank if no list

			gravity				: "n",			// nw, n, ne, w, e, sw, s, se
			popup_width			: 240, 			// px 	
			btn_width			: 180, 			// px
			anim				: "bounceInUp",
			speed_up			: false,		// Speed Up animation
			delay				: 0, 			// milliseconds
			radius				: "5px",   		// Default radius
			trim_radius 		: false, 		// Remove radius? h: Header, f: Footer
			offset_x			: "40px", 
			offset_y			: "0",

			company_avatar   	: '', 
			avatar_size 		: 40,			// px
			reply_pos 			: "top", 		// top, bottom

			guest_prefix 		: "Guest-", 	// If there is no way to give user a name, then guest prefix + id will be used

			offline_redirect 	: "", 			// Redirect offline button to existing page (full URL)

			user_info 			: { 			// Default user info used when related field isn't sent by login/contact form
				id 				: null,
				name 			: null,
				email 			: null,
				phone 			: null
			},
			
			debug				: false,		// Debugging mode
			
			offline_form		: {
				name: {
					title 	: "Your name",
					type 	: "text",
					req 	: true,
					val 	: ""
				},
				email: {
					title 	: "E-mail",
					type 	: "email",
					req 	: true,
					val 	: ""
				},
				question: {
					title 	: "Got a question?",
					type	: "textarea",
					req 	: true,
					val 	: ""
				}
			},

			login_form 			: {
				name: {
					title 	: "Your name",
					type 	: "text",
					req 	: false,
					val 	: ""
				},
				email: {
					title 	: "E-mail",
					type 	: "email",
					req 	: true,
					val 	: ""
				}
			},

			colors				: {
				primary : "#e54440",
				link 	: "#459ac4"
			},

			msg					: { 
				online: "Talk to us",
				offline: "Contact us",
				prechat_msg: "Questions? We're here. Send us a message!",
				welc_msg: "Questions, issues or concerns? I'd love to help you!",
				start_chat: "Start Chat",
				offline_body: "Sorry! We're not around right now. Leave a message using the form below and we'll get back to you, asap.",
				reply_ph: "Type here and hit enter to chat",
				send_btn: "Send",
				no_op: "No operators online",
				no_msg: "No messages found",
				sending: "Sending",
				connecting: "Connecting",
				writing: "%s is writing",
				please_wait: "Please wait",
				chat_online: "Chat Online",
				chat_offline: "Chat Offline",
				optional: "Optional",
				your_msg: "Your message",
				end_chat: "End chat",
				conn_err: "Connecting error!",
				you: "You",
				online_btn: "Online",
				offline_btn: "Offline",
				field_empty: "Please fill out all required fields",
				invalid_email: "E-mail is invalid",
				op_not_allowed: "Operators do not chat from here, only visitors. If you want to test chat box, you will want to use two different browsers or computers",
				months: [
					"January", "February", "March", "April", "May", "June",
					"July", "August", "September", "October", "November", "December"
				],
				months_short: [
					"Jan", "Feb", "Mar", "Apr", "May", "Jun",
					"Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
				],
				time: {
					suffix: "ago",
			        seconds: "less than a minute",
			        minute: "about a minute",
			        minutes: "%d minutes",
			        hour: "about an hour",
			        hours: "about %d hours",
			        day: "a day",
			        days: "%d days",
			        month: "about a month",
			        months: "%d months",
			        year: "about a year",
			        years: "%d years"
				}
			},

			// Events
			before_load 		: $.noop, // Called before starting to load content
			after_load	 		: $.noop, // Called after plugin/content is loaded
			// before_connect		: $.noop, // Before trying to connect to chat
			on_connect			: $.noop, // Called when Firebase connection succeeded
			on_disconnect		: $.noop, // Called when Firebase connection failed
			auth				: $.noop, // Called when user authenticated in Firebase, but not logged in yet
			auth_error 			: $.noop, // Called when error occurred while user authenticated
			logged_in 			: $.noop, // Called when user logged in successfully
			logged_out 			: $.noop, // Called when user logged out
			offline 			: $.noop, // Current user is offline now!
			new_msg				: $.noop, // New message received in any conversation
			user_online			: $.noop, // New user is online
			user_offline		: $.noop, // A user appeared offline
			user_created		: $.noop, // New user created
			user_failed			: $.noop, // Failed to create new user
			cnv_msgs_loaded		: $.noop  // Current conversation messages loaded afte calling reload_cnv() function
		};

	// The Plugin constructor
	function Plugin() {
		
		/*
		 * Plugin instantiation
		 *
		 * You already can access element here
		 * using this.el
		 */
		 this.opts = $.extend( {}, defaults );

	}
	
	Plugin.prototype = {
	
		init : function ( opts ) {

			// Extend opts ( http://api.jquery.com/jQuery.extend/ )
			$.extend( this.opts, opts );

			// Data holds variables to use in plugin
			this.data = {
				ref : null, 				// Firebase chat reference
				auth : null, 				// Firebase auth reference

				mode : "offline", 			// Current mode
				logged : false, 			// Logged in?

				active_user_id : 0,

				is_mobile : false,

				anim_delay : 0, 			// Animation delay

				primary_fg : null, 			// Primary foreground
				primary_hover : null, 		// Primary hover color
				link_hover : null, 			// Link hover color
				v_pos : null, 				// top, bottom
				h_pos : null, 				// left, right

				radius : null,
				radius_f : null,
				radius_h : null,

				box_id 				: 0, 	// For multi boxes
				popup_status 		: "close", // Popup status: open, close

				user 				: {}, 	// User data
				current_form 		: {}, 	// Current form data

				online_ops 			: {}, 	// Online operators list

				entity_map 			: {
					"&": "&amp;",
					"<": "&lt;",
					">": "&gt;",
					"\"": "&quot;",
					"'": "&#39;",
					"/": "&#x2F;"
				}
			};

			// Common objects
			this.objs = {
				btn				: null,
				popup 			: null,
				popup_header 	: null,
				cnv 	 		: null
			};

			// Callback: Before load
			if( false === this.trigger( "before_load" ) )
				return;

			var self = this;


			// Is mobile?
			if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test( navigator.userAgent ) ) {
				this.data.is_mobile = true;
			}

			// Set delay of timeout according to speed if there is animation
			if( this.opts.anim )
				this.data.anim_delay = this.opts.speed_up ? 400 : 1000;

			// Get application token
			this.post( 'get_token', {}, function( r ) {
				if( !r.error ) {
					self.data.auth_token = r.token;

					self.log(r.token);

					self.run(); // Wait for token and then build UI

					if( cx.is_op && !cx.is_front_end )
						self.auth();
				}
			});

			

		},

		run : function() {

			// Render chat button
			this.render_btn();

			// Render popup
			this.render_popup();

			// Shortcode: Open chatbox
			$('.cx-open-chatbox').click( function(e) {
				
				self.opts.render = 1;
				
				self.objs.btn.trigger('click');

				return false;
			});

			// Check desktop notifications
			this.check_ntf();

		},

		/**
		 * Authentication
		 */
		auth : function( callback ) {

			var self = this;

			// Check if app id provided
			if( !this.opts.app_id ) {
				
				console.error( "App ID isn't provided" );

				return;
			}

			// Create app references
			this.data.ref = new Firebase( "https://" + this.opts.app_id + ".firebaseIO.com" );
			this.data.ref_conn = new Firebase( 'http://' + self.opts.app_id + '.firebaseIO.com/.info/connected' );
			this.data.ref_cnv = new Firebase( 'http://' + this.opts.app_id + '.firebaseIO.com/conversations' );
			this.data.ref_msgs = new Firebase( 'http://' + this.opts.app_id + '.firebaseIO.com/messages' );
			this.data.ref_users = new Firebase( 'http://' + this.opts.app_id + '.firebaseIO.com/users' );
			
			// Check if Firebase connected or lost
			this.data.ref.child( '.info/connected' ).on( 'value', function( snap ) {
				
				if ( snap.val() === true ) {
					
					/* we're connected! */
					self.log( "Firebase connnected" );

					// Callback: Connected
					self.trigger( 'on_connect' )

				} else {
					/* we're disconnected! */
					self.log( "Firebase disconnected" );

					// Callback: Connected
					self.trigger( 'on_disconnect' )
				}

			});

			// Log in
			if( this.opts.display_login )
				this.login( false, true, callback ); // Render button here
			else
				this.login( true, true, callback ); // Render button here


			// Callback: After plugin load
			this.trigger( 'after_load' );

		},

		/**
		 * Login
		 */
		login : function( new_user, render, callback ) {

			var self = this;

			// Check user connection
			this.manage_conn();

			// Create new user now or it is just page refresh to check authentication status
			this.data._new_user = new_user;

			// Authenticate user
			this.data.auth = this.data.ref.auth( this.data.auth_token, function( error ) {
				
				// An error occurred while attempting login
				if( error ) {

					console.error( error.code, error.message );

					// Callback: Authentication error
					self.trigger( 'auth_error', error );

					// Display error
					self.display_ntf( self.opts.conn_err, 'error' );
				
				// Authentication is succeed
				} else {

					// Callback: Authenticated in Firebase, but not logged in yet
					self.trigger( 'auth' );

					// Now logged in
					self.data.logged = true;

					// Get operators and check current user
					self.data.ref_users.once( 'value', function( snap ) {

						var users = snap.val(),
							i = 0,
							title;

						if( users !== null ) {

							var total_user = Object.keys( users ).length;

							$.each( users, function( user_id, user ) {


								// Increase index
								i = i + 1;

								if( user ) {

									// If operator is online, save in operators list
									if( user.type == 'operator' && user.status === 'online' ) {
										
										// Increase total number of operetors
										self.data.online_ops[user.id] = user;

									}

								}

								if( i === total_user ) { // Last index in the while

									// Offline mode
									if( !self.total_online_ops() ) { // Is there any online operator?
										
										// Update title
										title = self.opts.msg.offline;

										// Show offline form
										self.show_offline();
									
									// Online mode
									} else {

										// Login mode
										if( self.opts.display_login ){
										
											// Update title
											title = self.opts.msg.online;

											// Show login form
											self.show_login();
										
										// Online mode
										} else {
											
											// Update title
											title = self.opts.msg.online;

											// Show conversation
											self.show_cnv( true );

										}
									}

									// Get user from Firebase
									self.check_user( self.opts.user_info.id );

								}

							});

						} else {

							// Update title
							title = self.opts.msg.offline;
							
							// Show offline form
							self.show_offline();

							// Get user from Firebase
							self.check_user( self.opts.user_info.id );

						}

						if( callback )
							callback();


					});

				}

			});

		},

		/**
		 * Logout from Firebase
		 */
		logout : function( logout_msg ) {

			var self = this;

			if( this.data.user.id ) {

				// Save transcript and delete data from Firebase
				this.save_user_data( this.data.user.cnv_id, true, function() {

					// Don't listen current user
					self.data.ref_user.off();

					// Don't listen users
					self.data.ref_users.off();

					// Don't listen message anymore
					self.data.ref_msgs.off();

					// Log user out from Firebase
					self.data.ref.unauth();

					// Be offline
					self.be_offline();

					// Callback: Logged out
					self.trigger( 'logged_out', logout_msg );
					
				});

			}

			

			// Display offline form
			this.minimize();



		},

		/**
		 * Just be offline, don't logout completely
		 */
		be_offline : function() {

			// Set mode
			this.data.mode = 'offline';

			if( this.data.ref_user ) {
				
				// Set status offline in Firebase
				this.data.ref_user.child( 'status' ).set( 'offline' );

				// Set last online
				this.data.ref_user.child( 'last_online' ).set( Firebase.ServerValue.TIMESTAMP );
				
			}

			 // Force user to be offline
			this.check_mode( true );

			// Callback: Current user is offline now
			this.trigger( 'offline' );

		},


		/**
		 * Check user if exists in Firebase
		 */
		check_user : function( user_id ) {

			var self = this;

			// User reference
			this.data.ref_user = this.data.ref_users.child( user_id );

			// Get user
			this.data.ref_user.once( 'value', function( snap ) {

				var user_data = snap.val();

				// User data must always be object 
				if( !user_data )
					user_data = {};

				// Get user now
				self.get_user( user_id, user_data );



			});

			// Check current user connectivity
			this.data.ref_user.on( 'child_removed', function( snap ) {

				var user = snap.val();

				if( !user )
					return;

				if( self.data.mode === 'online' && !user.status ) 
					self.logout();

			});
		},

		/**
		 * Get user from Firebase. If not exists, create new one
		 */
		get_user : function( user_id, user_data, callback ) {

			var self = this;

			// Get current user data
			if( user_data.id ) {

				// Get user data
				this.data.user = user_data;

				// Update current mode in Firebase
				this.data.ref_user.child('status').set('online');

				// Update other user data
				this.data.ref_user.child('ip').set( cx.ip );
				this.data.ref_user.child('current_page').set( cx.current_page );
				
				// Also update basic user information in any case
				if( cx.user_name && !cx.is_front_end ) {
					this.data.ref_user.child('name').set( cx.user_name );
					this.data.ref_user.child('email').set( cx.user_email );
					this.data.ref_user.child('gravatar').set( cx.user_email_hash );
				}

				// Show conversation
				if( this.total_online_ops() )
					this.show_cnv();
				else
					this.show_offline();

				// Check user connection
				this.manage_conn();

				// Callback: Logged in successfully
				self.trigger( 'logged_in', this.data.user );
				
				// Now listen users activity
				self.listen_users();

				if( callback )
					callback();


			// Create new user
			} else if( this.data._new_user === true ) {

					// Create new conversation
					var cnv = this.data.ref_cnv.push({
							user_id 	: user_id,
							created_at 	: Firebase.ServerValue.TIMESTAMP
						}),

						// Prepare user data
						data = {
							id 				: user_id,
							cnv_id 			: cnv.name(),
							ip 				: cx.ip,
							is_mobile 		: this.data.is_mobile,
							current_page 	: cx.current_page,
							type 			: cx.is_op ? 'operator' : 'visitor',
							status 			: 'online' // Connection status
						};

				// Merge with default user data
				for ( var d in this.opts.user_info ) { data[d] = this.opts.user_info[d]; }
				

				// Merge with login form data
				for ( var d in this.data.current_form ) { data[d] = this.data.current_form[d]; }

				// Name field is empty? Find a name for user
				if( !data.name ) {

					// Use email localdomain part
					if( data.email ) {
						data.name = data.email.substring( 0, data.email.indexOf( '@' ) );

					// Give user a random name
					} else {
						data.name = this.opts.guest_prefix + this.random_id( 1000, 5000 );

					}
				}

				// Update user data
				this.data.user = data;

				// Notify operators for new user
				if( cx.is_front_end && self.opts.notify_by_email ) {
					self.post( 'notify', self.data.user, function(r) {
						self.log('Notify operators?', r);
					} );
				}

				// Create user in Firebase
				this.data.ref_user.set( data, function( error ) {

					if( !error ) {

						// Show conversation
						self.show_cnv();
						
						// Callback: New user created
						self.trigger( 'user_created', self.data.user );

						// Callback: Logged in successfully
						self.trigger( 'logged_in', self.data.user );

						// Check this new user connection again
						self.manage_conn();

						// Now listen users activity
						self.listen_users();

					} else {

						// Callback: Failed to create new user
						self.trigger( 'user_failed', error );

					}

					if( callback )
						callback();

				});


			} else {
				
				// Now listen users activity
				self.listen_users();

			}

		},

		/**
		 * Get a user data
		 */
		get_user_data : function( user_id, callback ) {

			this.data.ref_users.child( user_id ).once( 'value', function( snap ) {

				var user = snap.val();

				// Just run callback
				callback( user );

			});
		},

		/**
		 * Manage connections
		 */
		manage_conn : function() {

			var self = this;

			if( !this.data.ref_user )
				return;


			// Manage connections
			this.data.ref_conn.on( 'value', function( snap ) {

				// User is connected (or re-connected)!
				// and things happen here that should happen only if online (or on reconnect)
				if( snap.val() === true ) {

					// Add this device to user's connections list
					var conn = self.data.ref_user.child('connections').push( true );

					// When user disconnect, remove this device
					conn.onDisconnect().remove();

					// Set online
					self.data.ref_user.child( 'status' ).set( 'online' );

					// Update user connection status when disconnect
					self.data.ref_user.child( 'status' ).onDisconnect().set( 'offline' );

					// Update last time user was seen online when disconnect
					self.data.ref_user.child( 'last_online' ).onDisconnect().set( Firebase.ServerValue.TIMESTAMP );

					// Remove user typing list on disconnect
					self.data.ref_cnv.child( self.data.user.cnv_id +  '/typing/' + self.data.user.id ).onDisconnect().remove();
					
				}

			});

		},
		
		/**
		 * Create new message
		 */
		push_msg : function( msg ) {

			// Push message to Firebase
			this.data.ref_msgs.push({
				user_id		: this.data.user.id,
				user_type	: this.data.user.type,
				cnv_id		: this.data.user.cnv_id,
				name 		: this.data.user.name || this.data.user.email,
				gravatar 	: this.data.user.gravatar,
				msg 		: msg,
				time 		: Firebase.ServerValue.TIMESTAMP
			});

		},

		// Listen new messages only, 
		// not older message loaded at first page refresh
		listen_new_msgs : function( msg_id ) {

			var self = this,
				ref_msgs = !msg_id ? self.data.ref_msgs : self.data.ref_msgs.startAt( null, msg_id ),
					first = true;

			// Don't ignore first message when you check all messages
			if( !msg_id )
				first = false;

			ref_msgs.on( 'child_added', function( new_snap ) {

				var new_msg = new_snap.val(),
					new_msg_id = new_snap.name();

				// Include message id
				new_msg.id = new_msg_id;

				// Update current conversation (front-end only)
				if( cx.is_front_end && self.data.user.cnv_id == new_msg.cnv_id ) {
					
					// Ignore first message
					if( !first )
						self.add_msg( new_msg );

				}

				// Show popup when new message arrived!
				if( !first )
					self.show_popup();

				// Callback: New message arrived
				self.trigger( 'new_msg', new_msg );

				// Not first message anymore
				first = false; 

			});

		},

		/**
		 * Listen message
		 */
		listen_msgs : function() {

			var self = this;
			
			// Clear previous listen
			this.data.ref_msgs.off();

			// Get current messages
			this.data.ref_msgs.once( 'value', function( snap ) {

				var msgs = snap.val(),
					total_msgs = msgs ? Object.keys( msgs ).length : 0,
					i = 1;


				// Load old messages after page refresh
				if( msgs ) {

					$.each( msgs, function( msg_id, msg) {
						 
						// Update current conversation (front-end only)
						if( cx.is_front_end && self.data.user.cnv_id == msg.cnv_id) {

							// Include msg id
							msg.id = msg_id;

							// Add message
							self.add_msg( msg );

						}

						// First load
						msg.first_load = true;

						// Callback: New message arrived at initial state
						self.trigger( 'new_msg', msg );

						// Last msg id
						if( total_msgs == i ) {
							
							// Listen new messages
							self.listen_new_msgs( msg_id );

						}

						// Increase index
						i = i + 1;
					});

				} else {

					self.listen_new_msgs();

				}

			});

		},

		/**
		 * Read current conversation messages and update cnv area (reload messages)
		 * It is good to use when user open empty conversation box on user interface
		 * and show up old messages
		 */
		reload_cnv : function( cnv_id ) {

			var self = this;

			// Get current conversation messages
			this.data.ref_msgs.once( 'value', function( snap ) {

				var now = new Date(),
					all_msgs = snap.val(),
					total_msgs = all_msgs ? Object.keys( all_msgs ).length : 0,
					total_user_msgs = 0,
					i = 1;

				if( all_msgs ) {

					$.each( all_msgs, function( msg_id, msg ) {

						if( msg.cnv_id == cnv_id ) {

							// This message from chat history
							msg.old_msg = true;
							
							// Callback: New message arrived
							self.trigger( 'new_msg', msg );

							// Increase total number of user messages
							total_user_msgs = total_user_msgs + 1;

						}

						if( total_msgs == i ) { // Last index
							
							// Callback: All conversation messages loaded
							self.trigger( 'cnv_msgs_loaded', total_user_msgs );
						}

						// Increase index
						i = i + 1;

					});

				} else { // No message

					// Callback: All conversation messages loaded
					self.trigger( 'cnv_msgs_loaded', 0 );

				}


			});

		},

		/**
		 * Chatbox allowed to show up?
		 */
		allow_chatbox : function() {

			// Check render and mobile devices options
			// Also check "hide when all operators offline" option
			if( !this.opts.render || ( this.opts.disable_on_mobile && this.data.is_mobile ) )
				return false;

			return true;

		},

		
		/**
		 * Render button before showing up
		 */
		render_btn : function() {

			var self = this;

			if( !cx.is_front_end || this.data.embed ) return;

			// Find position
			this.data.v_pos = this.opts.gravity.charAt(0) == 'n' ? 'top' : 'bottom';
			this.data.h_pos = this.opts.gravity.charAt(1) == 'e' ? 'right' : 'left';

			// Find secondary colors
			this.data.primary_fg = this.use_white( this.opts.colors.primary ) ? '#ffffff' : '#444444';
			this.data.link_fg = this.use_white( this.opts.colors.link ) ? '#ffffff' : '#444444';
			this.data.primary_hover = this.shade_color( this.opts.colors.primary, 7 );
			this.data.link_hover = this.shade_color( this.opts.colors.link, 7 );

			// Calculate popup radius
			switch( this.opts.trim_radius ) {
				case 'h':
					this.data.radius = this.data.radius_f = '0 0 ' + this.opts.radius + ' ' + this.opts.radius;
						this.data.radius_h = 0;
					break;

				case 'f':
					this.data.radius = this.data.radius_h = this.opts.radius + ' ' + this.opts.radius + ' 0 0';
						this.data.radius_f = 0;
					break;

				default:
					this.data.radius = this.opts.radius;
					this.data.radius_h = this.opts.radius +  ' ' + this.opts.radius + ' 0 0';
					this.data.radius_f = '0 0 ' + this.opts.radius;
			}

			var btn_class = ( this.opts.btn_view.show_icon ) ? '' : 'cx-no-ico';

			// Button class
			if( !this.opts.btn_view.show_title )
				btn_class = btn_class + ' cx-no-title';

			// Render button
			this.el.html( this.render( 'btn', {
				box_id 			: this.data.box_id,
				title 			: this.opts.msg.online,
				class 			: btn_class,
				display_title 	: ( this.opts.btn_view.show_title ) ? 'block' : 'none', // Show title
				display_ico 	: ( this.opts.btn_view.show_icon ) ? 'block' : 'none', // Show icon
				display_arr 	: ( this.opts.btn_view.show_arrow ) ? 'block' : 'none', // Show arrow
				color 			: this.data.primary_fg,
				width 			: this.opts.btn_width || '',
				bg_color 		: this.opts.colors.primary,
				h_pos 			: this.data.h_pos,
				v_pos 			: this.data.v_pos,
				radius 			: this.data.radius,
				offset_x		: this.opts.offset_x,
				offset_y 		: this.opts.offset_y,
				direction 		: ( this.opts.gravity.charAt(0) == 'n' ) ? 'down' : 'up'
			} ) );

			this.objs.btn = $( '#CX_btn_' +  this.data.box_id );

			// Chat button hover
			this.objs.btn.hover(
				function() {
					$(this).css('background-color', self.data.primary_hover );
				},
				function() {
					$(this).css('background-color', self.opts.colors.primary );
				}
			);

			// Manage button
			this.objs.btn.click( function() {

				var obj_btn = $(this),
					obj_btn_title = $(this).find('.cx-title');

				// Update button title
				obj_btn_title.html( self.opts.msg.please_wait + '...' );

				// Hide button
				obj_btn.hide();

				// Show popup
				self.show_popup();

				self.auth( function() {

					// Update title
					obj_btn_title.html( self.opts.msg.online );

					// Redirect offline button if necessary
					if( self.opts.offline_redirect.length > 0 && self.data.mode === 'offline') {

						window.location.href = self.opts.offline_redirect;

						return;
					}

				});
				
				
			});


			setTimeout( function() {
				self.show_btn();
			}, this.opts.delay );

		},

		/**
		 * Render popup
		 */
		render_popup : function() {

			var self = this;

			if( !cx.is_front_end ) return;

			this.el.append( this.render( 'popup', {
				box_id 		: this.data.box_id,
				title 		: this.opts.msg.connecting + '...',
				class 		: 'connecting',
				h_pos 		: this.data.h_pos,
				v_pos 		: this.data.v_pos,
				offset_x 	: this.opts.offset_x,
				offset_y 	: this.opts.offset_y,
				color 		: this.data.primary_fg,
				width 		: this.opts.popup_width,
				bg_color 	: this.opts.colors.primary,
				body_class 	: 'cx-form cx-offline-form',
				direction 	: ( this.opts.gravity.charAt(0) == 'n' ) ? 'up' : 'down', // Reverse direction
				radius 		: this.data.radius,
				radius_h 	: this.data.radius_h,
				radius_f 	: this.data.radius_f,
				body 		: this.render( 'connecting', {
					lead: this.opts.msg.connecting + '...'
				} )
			}));

			/*var mode_data = {
				box_id 		: this.data.box_id,
				lead 		: this.opts.msg.offline_body,
				form 		: this.get_form( this.opts.offline_form ),
				btn 		: this.opts.msg.send_btn,
				btn_color 	: this.data.link_fg,
				btn_bg 		: this.opts.colors.link
			};

			var body_class = 'cx-form cx-offline-form',
				body_template = 'offline';


			// Create popup
			this.el.append( this.render( 'popup', {
				box_id 		: this.data.box_id,
				title 		: this.opts.msg.connecting + '...',
				class 		: mode_class,
				h_pos 		: this.data.h_pos,
				v_pos 		: this.data.v_pos,
				offset_x 	: this.opts.offset_x,
				offset_y 	: this.opts.offset_y,
				color 		: this.data.primary_fg,
				width 		: this.opts.popup_width,
				bg_color 	: this.opts.colors.primary,
				body_class 	: body_class,
				direction 	: ( this.opts.gravity.charAt(0) == 'n' ) ? 'up' : 'down', // Reverse direction
				radius 		: this.data.radius,
				radius_h 	: this.data.radius_h,
				radius_f 	: this.data.radius_f,
				body 		: this.render( body_template, mode_data )
			} ) );*/

			this.objs.popup = $( '#CX_popup_' +  this.data.box_id );
			this.objs.popup_header = $( '#CX_popup_header_' + this.data.box_id );
			this.objs.popup_body = $( '#CX_popup_body_' + this.data.box_id );
		
			// Send button hover
			$(document).on( 'hover', '#CX_send_btn_' +  this.data.box_id,
				function() {
					$(this).css('background-color', self.data.link_hover );
				},
				function() {
					$(this).css('background-color', self.opts.colors.link );
				}
			);

			// Manage popup header
			this.objs.popup_header.click( function() {

				// Just be offline, don't logout completely
				self.be_offline();

				// Minimize popup
				self.minimize();

			});


			// Set height of chat popup
			$(window).resize(function() {

				var w = window,
					d = document,
					e = d.documentElement,
					g = d.getElementsByTagName('body')[0],
					x = w.innerWidth || e.clientWidth || g.clientWidth,
					y = w.innerHeight|| e.clientHeight|| g.clientHeight,
					pop_h_y = self.objs.popup_header.innerHeight(), // Popup header height
					pop_b = parseInt( self.objs.popup.css( 'bottom' ), 10 ); // Popup bottom

				// Set max height
				var default_y = ( self.data.mode === 'online' ) ? 370 : 450,
					max_y = ( default_y < y ) ? default_y : y - pop_h_y - pop_b;

				self.objs.popup_body.css( 'max-height', max_y );


			}).trigger('resize');

			
		},
		
		/**
		 * Show button
		 */
		show_btn : function( title ) {

			if( cx.is_front_end ) {

				var self = this;

				// Allow displaying?
				if( !this.allow_chatbox() )
					return;

				// Just show btn
				this.objs.btn.show();

				// Update title
				this.objs.btn.find( '.cx-title' ).html( title );

				// Show and animate
				this.animate( this.objs.btn, this.opts.anim );

			}

		},

		/**
		 * Show popup
		 */
		show_popup : function() {

			// Don't re-open popup
			if( this.data.popup_status == 'open' || !cx.is_front_end ) return;

			var self = this;

			// Set cookie
			this.cookie( 'cx_widget_status', 'open' );

			// Display popup
			this.objs.popup.show();

			// Show popup with animation
			this.animate( this.objs.popup, this.opts.anim );

			// Focus on first field in the form
			setTimeout( function() {

				switch( self.data.mode ) {

					// Online mode
					case 'online':
						
						// Focus reply box
						$( '#CX_cnv_reply_' + self.data.box_id ).focus();

						// Scroll down conversation if necessary
						if( self.opts.reply_pos == 'bottom' ) {
							self.objs.cnv.scrollTop(10000);
						}

						break;

					// Offline or login mode
					case 'offline':
					case 'login':

						// Focus first input in the form
						$( '#CX_popup_form_' + self.data.box_id + ' .cx-line:first-child input').focus();
						
						break;
				}


				// Update popup status
				self.data.popup_status = 'open';

			}, this.data.anim_delay );

		},

		/**
		 * Minimize popup
		 */
		minimize : function() {

			var ico = this.objs.btn.find( '.cx-ico-chat' );

			// Set cookie
			this.cookie( 'cx_widget_status', 'minimized' );

			// Update popup status
			this.data.popup_status = 'close';

			// Hide popup
			if( this.objs.popup )
				this.objs.popup.hide();


			this.objs.btn.show();

			// Hide text and arrow on the button
			// this.objs.btn.show().width('').find( '.cx-ico-arrow-down, .cx-ico-arrow-up, .cx-title' ).hide();

			// Show chat icon
			// ico.show();

			// Display button
			this.animate( this.objs.btn, this.opts.anim );

		},

		/**
		 * Show conversation in chat box
		 */
		show_cnv : function( no_anim ) {

			var self = this;

			// Update mode
			this.data.mode = 'online';

			if( cx.is_front_end ) {

				// Allow displaying?
				if( !this.allow_chatbox() )
					return;

				// Update popup header
				this.objs.popup_header.find('.cx-title').html( this.opts.msg.online );

				// Update popup wrapper
				this.objs.popup.parent().removeClass().addClass( 'cx-online cx-reply-' + this.opts.reply_pos );

				// Render popup body
				this.objs.popup_body
						 .removeClass()
						 .addClass('cx-body cx-online cx-reply-' + this.opts.reply_pos)
						 .empty()
						 .html( this.render('online-' + this.opts.reply_pos, {
						 	box_id 		: this.data.box_id,
							reply_ph 	: this.opts.msg.reply_ph,
							welc 		: this.opts.msg.welc_msg,
							end_chat 	: this.opts.msg.end_chat
						 }
				));

				this.objs.cnv = $( '#CX_cnv_' + this.data.box_id );

				// Autosize and focus reply box
				if( !no_anim ) {

					$( '#CX_cnv_reply_' + self.data.box_id ).focus().autosize( { append: '' } ).trigger( 'autosize.resize' );
				
				} else {

					setTimeout( function() {

						$( '#CX_cnv_reply_' + self.data.box_id ).focus().autosize( { append: '' } ).trigger( 'autosize.resize' );

					}, this.data.anim_delay);

				}

				// Resize window to ensure chat box is responsive
				$(window).trigger( 'resize' );

				// Listen messages
				this.listen_msgs();

				// Logout (End chat)
				$( '#CX_tool_end_chat' ).click( function() {

					self.logout();

					return;

				});

				// Manage reply box
				this.manage_reply_box();

			}

		},

		/**
		 * Manage reply box
		 */
		manage_reply_box : function( last_cnv_id ) {
			
			var self = this,
				writing = false,
				obj_reply = $( '#CX_cnv_reply_' + this.data.box_id ),
				
				/**
				 * Delay for a specified time
				 */
				fn_delay = ( function(){
				
					var timer = 0;

					return function(callback, ms){
						clearTimeout (timer);
						timer = setTimeout(callback, ms);
					};

				} )();

			// First clean typing list in any case!
			this.data.ref_cnv.child( this.data.user.cnv_id +  '/typing' ).remove();

			// Manage reply box
			obj_reply.keydown( function(e) {

				// When clicks ENTER key (but not shift + ENTER )
				if ( e.keyCode === 13 && !e.shiftKey ) {
					
					e.preventDefault();

					var msg = $(this).val();

					if( msg ) {

						// Clean reply box
						$(this).val('').trigger( 'autosize.resize' );

						// Send message to Firebase
						self.push_msg( msg );

						// User isn't typing anymore
						self.data.ref_cnv.child( self.data.user.cnv_id +  '/typing/' + self.data.user.id ).remove();

					}

				// Usual writing..
				} else {

					// Check if current user (operator & visitor) is typing...
					if( !writing ) {

						// Don't listen some keys
						switch( e.keyCode ) {
							case 17: // ctrl
							case 18: // alt
							case 16: // shift
							case 9: // tab
							case 8: // backspace
							case 224: // cmd (firefox)
							case 17:  // cmd (opera)
							case 91:  // cmd (safari/chrome) Left Apple
							case 93:  // cmd (safari/chrome) Right Apple
								return;
						}
						
						// Add user typing list in current conversation
						self.data.ref_cnv.child( self.data.user.cnv_id + '/typing/' + self.data.user.id ).set( self.data.user.name );

						// User is writing now
						writing = true;

					}

					// Remove user from typing list after the user has stopped typing 
					// for a specified amount of time
					fn_delay( function() {

						// User isn't typing anymore
						self.data.ref_cnv.child( self.data.user.cnv_id +  '/typing/' + self.data.user.id ).remove();

						// User isn't writing anymore
						writing = false;
						
					}, 1300 );

				}



			});

			// Stop listen last conversation
			if( last_cnv_id ) {
				this.data.ref_cnv.child( last_cnv_id + '/typing' ).off();
			}

			// Check if a user is typing in current conversation...
			this.data.ref_cnv.child( this.data.user.cnv_id + '/typing' ).on( 'value', function( snap ) {

				var i = 0,
					users = snap.val(),
					total_users = ( users ) ? Object.keys( users ).length : 0;

				if( !users ) {
					self.clean_ntf();

					return;
				}

				$.each( users, function( user_id, user_name ) {
					 
					 // Hmm.. someone else writing
					if( user_id && user_id !== self.data.user.id ) {

						// Show notification
						self.display_ntf( self.opts.msg.writing.replace( /%s/i, user_name ), 'typing' );

						return; // Don't check other writers
					}

					if( total_users === i ) { // Last index
						self.clean_ntf();
					}

					i = i + 1; // Increase index

				});
			});

			if( cx.is_front_end ) { // Additional functions for front-end chat box
				
				// Focus on reply box when user click around it
				this.objs.popup.find('.cx-cnv-reply').click( function() {
					obj_reply.focus();
				});

			}

		},

		/**
		 * Show login form in chat box
		 */
		show_login : function( login_lead_msg, minimize ) {

			var self = this;

			if( cx.is_front_end ) {

				// Allow displaying?
				if( !this.allow_chatbox() )
					return;
				
				// Is it possible to show up login form?
				if( this.opts.display_login && this.total_online_ops() && this.objs.popup ) {
					// Update mode
					this.data.mode = 'login';

					// Update popup header
					this.objs.popup_header.find('.cx-title').html( this.opts.msg.online );

					// Update popup wrapper
					this.objs.popup.parent().removeClass().addClass( 'cx-login' );

					// Render popup body
					this.objs.popup_body
							 .removeClass()
							 .addClass('cx-body cx-form cx-login-form')
							 .empty()
							 .html( this.render('login', {
							 	box_id 		: this.data.box_id,
								lead 		: login_lead_msg || this.opts.msg.prechat_msg,
								form 		: this.get_form( this.opts.login_form ),
								btn 		: this.opts.msg.start_chat,
								btn_color 	: this.data.link_fg,
								btn_bg 		: this.opts.colors.link
							 }));

					// Resize window to ensure chat box is responsive
					$(window).trigger( 'resize' );

					// Login button hover
					$( '#CX_login_btn_' +  this.data.box_id ).hover(
						function() {
							$(this).css('background-color', self.data.link_hover );
						},
						function() {
							$(this).css('background-color', self.opts.colors.link );
						}
					);

					// Send login form
					$( '#CX_login_btn_' +  this.data.box_id ).click( function() {

						self.send_login_form();

					});

					// If user click enter in login form, 
					// send login form
					$( '#CX_popup_form_' + this.data.box_id ).keydown( function( e ) {

						// When clicks ENTER key (but not shift + ENTER )
						if ( e.keyCode == 13 && !e.shiftKey ) {
							e.preventDefault();

							self.send_login_form();
						}

					});


				// Login can't be shown up right now,
				// So show current mode
				} else {

					if( self.data.mode === 'online' )
						this.show_cnv();
					else
						this.show_offline();

				}

				// Minimize?
				if( minimize )
					this.minimize();

			}

		},

		/**
		 * Send login form 
		 */
		send_login_form : function() {

			var self = this;

			// Display "Connecting" message
			this.display_ntf( this.opts.msg.connecting + '...', 'sending' );

			// Get login form data
			var form_data = $( '#CX_popup_form_' + this.data.box_id ).serializeArray(),
				form_length = form_data.length - 1;

			// Validate login form
			$.each( form_data, function( i, f ) {

				// Update current form data
				self.data.current_form[f.name] = f.value;
				
				// Required?
				if( self.opts.login_form[f.name].req ) {

					// Is empty?
					if( !f.value ) {
						self.display_ntf( self.opts.msg.field_empty, 'error' );

						return false;
					}

					// Is valid email?
					if( self.opts.login_form[f.name].type === 'email' ) {

						// Invalid email!
						if( !self.validate_email( f.value ) ) {

							self.display_ntf( self.opts.msg.invalid_email, 'error' );

							return false;

						} else {

							// Create gravatar from email and add current form data
							self.data.current_form.gravatar = self.md5( f.value );

						}
					}

				}

				// Log user in now (form is valid)
				if( i === form_length ) {
					self.login( true );
				}

			});

			return;

		},

		/**
		 * Show connecting popup
		 */
		show_connecting : function() {

			var self = this;

			// Turn back to "connecting" popup
			this.objs.popup_body.html( self.render( 'connecting', {
				lead: this.opts.msg.connecting + '...'
			}));

		},


		/**
		 * Show offline popup
		 */
		show_offline : function() {

			var self = this,
				working = false;

			// Update mode
			this.data.mode = 'offline';

			if( cx.is_front_end ) {

				// Allow displaying?
				if( !this.allow_chatbox() )
					return;

				// Update popup header
				this.objs.popup_header.find('.cx-title').html( this.opts.msg.offline );

				// Update popup wrapper
				this.objs.popup.parent().removeClass().addClass( 'cx-offline' );

				// Render popup body
				this.objs.popup_body
						 .removeClass()
						 .addClass( 'cx-body cx-form cx-offline-form' )
						 .empty()
						 .html( this.render( 'offline', {
						 	box_id 		: this.data.box_id,
							lead 		: this.opts.msg.offline_body,
							form 		: this.get_form( this.opts.offline_form ),
							btn 		: this.opts.msg.send_btn,
							btn_color 	: this.data.link_fg,
							btn_bg 		: this.opts.colors.link
						 }));

				// Resize window to ensure chat box is responsive
				$(window).trigger( 'resize' );

				// Send offline form
				$('#CX_send_btn_' + this.data.box_id ).click( function(e) {

					// Don't allow to send form twice!
					if( working ) return false;

					working = true;

					// Display "sending" message
					self.display_ntf( self.opts.msg.sending + '...', 'sending' );

					self.post( 'offline_form', $('#CX_popup_form_' + self.data.box_id ).serialize(), function( r ) {

						working = false;

						if( r.error ) {

							// Display error message
							self.display_ntf( r.error, 'error' );

						// Successfully sent!
						} else {

							// Display message
							self.display_ntf( r.msg, 'success' );

							setTimeout( function() {

								// Clean display message
								self.clean_ntf();

								// Minimize popup
								self.minimize();

							}, 2000 );
						}

					} );

					return false;
				});

			}

		},

		/**
		 * Total number of online operators
		 */
		total_online_ops : function() {

			if( this.data.online_ops )
				return Object.keys(this.data.online_ops).length;
			else
				return 0;

		},

		/**
		 * Change mode if necessary!
		 */
		check_mode : function( force_offline ) {

			if( cx.is_front_end ) {

				var last_mode = this.data.mode;

				this.log( 'mode checking - online ops:', last_mode, this.data.online_ops );
				this.log( 'Any OPERATOR?', this.total_online_ops()  );

				if( force_offline ) {

					// Show offline
					this.show_connecting();

					// Update mode
					this.data.mode = 'offline';

				// No operators online!
				} else  if( !this.total_online_ops() ) {

					switch( last_mode ) { // Last mode

						// Visitor is trying to login
						case 'login':

							// Show offline
							this.show_offline();

						break;

						// Visitor is in conversation
						case 'online':

							// Disable reply box
							if( this.opts.display_login ) {

								$('#CX_cnv_reply_' + this.data.box_id).addClass('cx-disabled')
																	  .attr( 'disabled', 'disabled' );

								// No operators online!
								this.display_ntf( this.opts.msg.no_op + '!', 'error' );
							
							// If no login form, show user contact form
							} else {

								// Show offline
								this.show_offline();

							}


						break;
					}

					// Update mode
					this.data.mode = 'offline';


				// Some operator(s) online now!
				} else {

					// If last mode was online, 
					// re-activate reply box and clean notifications
					if( last_mode === 'offline' ) {

						// Disable reply box
						$('#CX_cnv_reply_' + this.data.box_id).removeClass('cx-disabled')
															  .removeAttr( 'disabled' );

						// Clean notification
						this.clean_ntf();

					}

					// Update mode
					this.data.mode = ( this.opts.display_login && last_mode != 'online' ) ? 'login' : 'online';

				}

				this.log( 'mode changed - online ops:', this.data.mode, this.data.online_ops );

			}
		},


		/**
		 * Add message into conversation
		 */
		add_msg : function( msg, last_user_id, last_msg_id ) {

			var now = new Date(),
				d = new Date( msg.time ), // Chat message date
				t = d.getHours() + ':' + ( d.getMinutes() < 10 ? '0' : '' ) + d.getMinutes(), // Chat message time
				msg_content = this.sanitize_msg( msg.msg ),
				
				// Set message time either time or short date like '21 May'
				msg_time = ( d.toDateString() == now.toDateString() ) ? t : d.getUTCDate() + ' ' + this.opts.msg.months_short[ d.getUTCMonth() ] + ', ' + t,

				avatar_type = ( msg.user_type === 'operator' ) ? true : false;

				// Render chat line
				chat_line = this.render( 'chat_line', {
					msg_id 		: msg.id,
					time 		: msg_time,
					date 		: d.getUTCDate() + ' ' + this.opts.msg.months[ d.getUTCMonth() ] + ' ' + d.getUTCFullYear() + ' ' + t,
					color 		: 'transparent',
					avatar 		: '<img src="' + this.gravatar( msg.gravatar, this.opts.avatar_size, avatar_type ) + '" />',
					name 		: msg.name,
					msg 		: msg_content,
					class 		: ( msg.user_id == this.data.user.id ) ? ' cx-you' : ''
				});

			// Hide welcome message
			if( cx.is_front_end )
				this.objs.cnv.find( '.cx-welc' ).hide();

			// Add message as current users chat line
			if( last_user_id == msg.user_id && this.opts.reply_pos == 'bottom' ) {

				$( '#CX_msg_' + last_msg_id + ' .cx-cnv-msg-detail' ).append( '<span class="cx-cnv-xtra-msg cx-bottom">' + msg_content + '</span>' ).scrollTop(10000);

			// Insert message line into current conversation
			} else if( this.objs.cnv ) {

				// Find direction of message
				// Also use top side in chat console
				if( this.opts.reply_pos == 'top' || !cx.is_front_end ) {

					this.objs.cnv.prepend( chat_line );

				} else {

					this.objs.cnv.append( chat_line ).scrollTop(10000);

				}
			}

		},

		/**
		 * Add message into conversation
		 */
		sanitize_msg : function( str ) {

			var msg, pattern_url, pattern_pseudo_url, pattern_email;

		    //URLs starting with http://, https://, or ftp://
		    pattern_url = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
		    msg = str.replace(pattern_url, '<a href="$1" target="_blank">$1</a>');

		    //URLs starting with "www." (without // before it, or it'd re-link the ones done above).
		    pattern_pseudo_url = /(^|[^\/])(www\.[\S]+(\b|$))/gim;
		    msg = msg.replace(pattern_pseudo_url, '$1<a href="http://$2" target="_blank">$2</a>');

		    //Change email addresses to mailto:: links.
		    pattern_email = /(([a-zA-Z0-9\-\_\.])+@[a-zA-Z\_]+?(\.[a-zA-Z]{2,6})+)/gim;
		    msg = msg.replace(pattern_email, '<a href="mailto:$1">$1</a>');

		    return msg;


		},

		// Update user info in Firebase
		update_user : function( user, prev_id ) {

			if( user ) {
				
				// User is not ready for adding
				// Wait for all information added into Firebase
				if( !user.id ) {
					this.log( 'no user id' );
					return;
					
				}
			}

			if( user ) {

				if( user.cnv_id ) {

					this.log( 'user updated', user );

					// Add user item into the list
					this.add_user_item( user );

					if( user.type === 'operator' ) { // Don't repeat same changes triggered more than once

						// Increase total operator number
						if( user.status === 'online' ) {
							this.data.online_ops[user.id] = user;
						
						// Decrease total number of operator
						} else {
							delete this.data.online_ops[user.id];
						}

						this.log( 'total ops updated', this.data.online_ops );

					}

					// Change mode if necessary!
					this.check_mode();

					// Callback: New user is online!
					if( !prev_id )
						this.trigger( 'user_online', user );

					// Update user active page url
					if( !cx.is_front_end && this.data.active_user_id === user.id )
						$( '#CX_active_page' ).attr( 'href', user.current_page ).find('span').html( user.current_page );

				// Remove user. It is trash! Because it doesn't have cnv_id
				} else {
					
					// Save user data, and then delete from Firebase
					this.clean_user_data( user.id );
				
				}
			}

			// Update last changed id
			this.data.last_changed_id = prev_id;

		},

		// Add user into the list
		add_user_item : function( user ) {
			
			var self = this;

			// If no list or user_id, 
			// don't try to add user into the list
			// also delete from the list
			if( !user.id || !this.data.user_list )
				return;

			var last_online = ( user.status === 'offline' ) ? ' &bull; <span class="cx-last-online" data-time="'+ user.last_online+ '">' + this.timeago( user.last_online ) + '</span>' : '',
				default_icons = '<span class="cx-ico cx-ico-' + user.status + '"></span><span class="cx-ico cx-ico-' + user.type + '"></span>';

			// First remove user item from the list if exists
			$( '#CX_ls_usr_' + user.id ).remove();

			// Don't show user on the list when user has no connection (not on the browser)
			/*if( !user.connections )
				return;*/

			// Mobile connecting?
			if( user.is_mobile ) {
				default_icons = default_icons + ' <span class="cx-ico cx-ico-mobile"></span>';
			}


			var avatar_type = ( user.type === 'operator' ) ? true : false,
			
				data = {
					id 			: user.id,
					class 		: 'cx-user-' + user.status + ' cx-user-' + user.type,
					icons 		: default_icons,
					color 		: user.color || 'transparent',
					username	: user.name || user.email || 'N/A',
					avatar  	: '<img src="' + this.gravatar( user.gravatar, this.opts.avatar_size, avatar_type ) + '" />',
					cnv_id 		: user.cnv_id,
					meta 		: user.type + last_online
				};

			// Render user item
			this.data.user_list.append( this.render( 'user_item', data ) );

		},

		// Remove user from the list
		remove_user_item : function( user_id ) {

			if( !this.data.user_list ) return;

			// Just remove from the list
			$( '#CX_ls_usr_' + user_id ).remove();

		},

		// Listen users
		listen_new_users : function( callback ) {

			var self = this;

			// Add users
			this.data.ref_users.on( 'value', function( snap ) {
				
				// Clear list now
				$( '#CX_users > ul' ).empty();

				var users = snap.val();

				$.each( users, function( user_id, user ) {
					
					self.update_user( user );

				});

			});
			
		},

		/**
		 * Get users
		 */
		listen_users : function() {

			var self = this;

			this.data.last_changed_id = null;

			// Prepare user list
			if( this.opts.users_list_id ) {

				// Clean list if already exists
				$( this.opts.users_list_id + ' > ul' ).remove();

				// Add ul list
				$( this.opts.users_list_id ).append( '<ul></ul>' );

				// Select list
				this.data.user_list = $( this.opts.users_list_id + ' > ul' );

			}


			// Listen users once in the beginning of page load
			this.data.ref_users.once( 'value', function( snap ) {

				var users = snap.val(),
					i = 0;

				if( users !== null ) {

					var total_user = Object.keys( users ).length;

					// Reset total ops
					self.data.online_ops = {};

					$.each( users, function( user_id, user ) {

						// Increase index
						i = i + 1;

						if( user ) {

							if( user.type === 'operator' ) {

								// Check operator connection
								if( user.status === 'online' ) {
									self.data.online_ops[user.id] = user;
								} else
									delete self.data.online_ops[user.id];

								self.log( 'total ops updated', self.data.online_ops );

							}

							// Add user item into the list
							self.add_user_item( user );

						}

						if( i === total_user ) { // Last index in the while
							
							// Change mode if necessary!
							self.check_mode();

							// Listen new users
							self.listen_new_users();

						}

					});

				}

			});

			
		},


		/**
		 * Save user data into DB
		 */
		save_user_data : function( cnv_id, delete_from_app, callback ) {
			
			var self = this,
				r = null; // Response

			// First get conversation data
			this.data.ref_cnv.child( cnv_id ).once( 'value', function( snap_cnv ) {

				var cnv = snap_cnv.val();

				if( !cnv )
					return;

				// Get user id
				var user_id = cnv.user_id;

				// Get user data
				self.data.ref_users.child( user_id ).once( 'value', function( snap_user ) {

					var user_data = snap_user.val();

					// Include conversation created time into user data
					user_data.cnv_time = cnv.created_at;

					// Get users messages from Firebase
					self.data.ref_msgs.once( 'value', function( snap_msgs ) {

						var msgs = snap_msgs.val(),
							total_msgs = msgs ? Object.keys( msgs ).length : 0,
							i = 0,
							msgs_data = {};

						if( msgs ) {

							$.each( msgs, function( msg_id, msg ) {
								 
								 // Increase index
								 i = i + 1;

								 if( msg.cnv_id === cnv_id ) {
								 	
								 	// Add user message into data
								 	msgs_data[msg_id] = msg;

								 	// Delete msg from app if requested
								 	if( delete_from_app )
								 		self.data.ref_msgs.child( msg_id ).remove();

								 }

								 if( total_msgs === i ) { // Last index

								 	// Add all user message into user data
								 	user_data.msgs = msgs_data;

									self.post( 'save_transcript', user_data, function( r ) {

										if( callback )
											callback( r ); // Trigger callback

									});
								 	
								 }


							});

						// No message for checking...
						} else if( callback ) {
							callback( {} ); // Response is null here
						}

						if( delete_from_app ) {

							// Delete user from Firebase
							self.data.ref_users.child( user_id ).remove();
							
							// Delete conversation from app if requested
							self.data.ref_cnv.child( cnv_id ).remove();
						}

					
					});

				});


			});

			
			
		},

		/**
		 * Clean user data from Firebase
		 */
		clean_user_data : function( user_id ) {

			var self = this,
				ref_user = this.data.ref_users.child( user_id );

			// Remove user from users list
			ref_user.once( 'value', function( snap ) {
				
				var user = snap.val();

				// Remove user reference
				ref_user.remove();

				// Clean user conversation
				if( user.cnv_id ) {
					self.ref_cnv.child( user.cnv_id );
				}

				// Remove user messages
				self.data.ref_msgs.once( 'value', function( msg_snap ) {

					var msgs = msg_snap.val();

					if( msgs ) {
						$.each( msgs, function( msg_id, msg ) {
							 
							 if( msg.user_id === user_id ) {
							 	self.data.ref_msgs.child( msg_id ).remove();
							 }

						});
					}

				});

			});

		},

		/**
		 * Get form
		 */
		 get_form : function( fields ) {

			var self = this;
			var r = '';

			// While fields
			$.each( fields, function(k, v) {
				var f_p = {
					id 			: k + '_' + self.data.box_id,
					name		: k,
					title 		: v.title,
					ph 			: v.title,
					type 		: v.type
				};

				// Is required?
				if( v.req == true ) {
					f_p.after_label = ' <span class="cx-req">*</span>';

				// Add "optional" text to placeholder
				} else {
					f_p.ph = f_p.ph + ' (' + self.opts.msg.optional + ')';
				}

				// Add user info
				if( cx.user_email ) {

					switch( k ) {
						case 'name': 
							f_p.val = cx.user_name;
							break;

						case 'email':
							f_p.val = cx.user_email;
							break;
					}

				}


				// Select type
				switch( v.type ) {
					case 'text':
					case 'email':
					case 'number':
					case 'color':
					case 'date':
					case 'datetime':
					case 'datetime-local':
					case 'tel':
					case 'time':
					case 'url':
					case 'week':
					case 'search':
						var _type = 'input';
						break;

					default: var _type = v.type;
				}

				r += self.render('form_' + _type, f_p);
			});

			return r;

		 },

		/**
		 * Update user geo info
		 */
		update_geo : function() {

			var self = this;

			$.getJSON( "http://ip-api.com/json/?callback=?", function( loc ) {
				
				// Update user data
				self.data.user.country = loc.country;
				self.data.user.country_code = loc.countryCode;
				self.data.user.city = loc.city;
				self.data.user.IP = loc.query;

				// Update user in Firebase database
				self.data.ref_user.update({
					country: loc.country,
					country_code: loc.countryCode,
					city: loc.city,
					IP: loc.query
				});
				
			});


		},

		/**
		 * Random ID
		 */
		random_id : function( min, max ) {

			return Math.floor( Math.random() * ( max - min + 1 ) ) + min;

		},

		/**
		 * Create or read cookie
		 */
		cookie : function( name, value, days ) {

			// Create new cookie
			if( value || days === -1 ) {

				if (days) {
					var date = new Date();
					date.setTime( date.getTime() + ( days * 24 * 60 * 60 * 1000 ) );
					var expires = '; expires=' + date.toGMTString();

				} else 
					var expires = '';
				

				document.cookie = name + '=' + value + expires + '; path=/';

			// Read cookie
			} else {

				var name_eq = name + "=";
				var ca = document.cookie.split(';');

				for(var i=0;i < ca.length;i++) {
					var c = ca[i];
					while (c.charAt(0)==' ') c = c.substring(1,c.length);
					if (c.indexOf(name_eq) === 0) return c.substring(name_eq.length,c.length);
				}

				return null;
			}

		},

		/**
		 * Remove cookie
		 */
		remove_cookie : function( name ) {
			this.cookie( name, '', -1 );
		},

		/**
		 * Check if browser supports notifications
		 */
		check_ntf : function() {

			// No notification support and don't show it on front end
			if( !( "Notification" in window ) || cx.is_front_end ) { 
				return;

			// Otherwise, we need to ask the user for permission
			// Note, Chrome does not implement the permission static property
			// So we have to check for NOT 'denied' instead of 'default'
			} else if ( Notification.permission !== 'denied' ) {
				Notification.requestPermission(function (permission) {
				
					// Whatever the user answers, we make sure we store the information
					if (!('permission' in Notification)) {
						Notification.permission = permission;
					}

				});
			}

		},

		/**
		 * Dekstop Notifications
		 */
		notify : function( title, msg, callback, tag ) {

			// No notification support and don't show it on front end
			if( !Notification || cx.is_front_end ) 
				return;

			// Check if browser supports notifications
			// And don't notify in front-end
			if ( ! ( "Notification" in window ) || cx.is_front_end ) {
				return;

			// Display notification if possible!
			} else if ( Notification.permission === "granted" ) {

				// If it's okay let's create a notification
    			var notification = new Notification( title, {
    				body: msg,
    				icon: cx.plugin_url + '/assets/img/cx-ico-32.png',
    				tag: tag
    			});

    			if( callback )
    				notification.onclick = function() { callback(); };
    			else 
    				notification.close();

    			// Hide notification after for a while
				setTimeout( function() {
					notification.close();
				}, 4000 );

			// Otherwise, we need to ask the user for permission
			// Note, Chrome does not implement the permission static property
			// So we have to check for NOT 'denied' instead of 'default'
			} else if ( Notification.permission !== 'denied' ) {
				Notification.requestPermission(function (permission) {
				
					// Whatever the user answers, we make sure we store the information
					if (!('permission' in Notification)) {
						Notification.permission = permission;
					}

					// If the user is okay, let's create a notification
					if (permission === "granted") {
						
						// If it's okay let's create a notification
		    			var notification = new Notification( title, {
		    				body: msg
		    			});

		    			if( callback )
		    				notification.onclick = function() { callback(); };
		    			else 
		    				notification.close();

		    			// Hide notification after for a while
						setTimeout( function() {
							notification.close();
						}, 4000 );

					}

				});
			}




/*


			// No notification support and don't show it on front end
			if( !window.Notification || cx.is_front_end ) 
				return;

			var have_permission = window.Notification.checkPermission();

			// Display notification
			if ( have_permission === 0 ) {

				// 0 is PERMISSION_ALLOWED
				var notification = window.Notification.createNotification(
					cx.plugin_url + '/assets/img/cx-ico-32.png',
					title,
					msg
				);

				if( callback )
					notification.onclick = callback;
				else
					notification.close();


				notification.show();

				// Hide notification after for a while
				setTimeout( function() {
					notification.close();
				}, 4000);

			// Request permission
			} else {
				window.Notification.requestPermission();
			}*/


		},

		/**
		 * Time template
		 */
		time : function( t, n ) {

			return this.opts.msg.time[t] && this.opts.msg.time[t].replace( /%d/i, Math.abs( Math.round( n ) ) );

		},

		/**
		 * Time ago function
		 */
		timeago : function( time ) {

			if ( !time )
	            return '';

	        /*time = time.replace(/\.\d+/, ""); // remove milliseconds
	        time = time.replace(/-/, "/").replace(/-/, "/");
	        time = time.replace(/T/, " ").replace(/Z/, " UTC");
	        time = time.replace(/([\+\-]\d\d)\:?(\d\d)/, " $1$2"); // -04:00 -> -0400
	        time = new Date( time * 1000 || time );*/

	        var now = new Date(),
	        	seconds = ( ( now.getTime() - time ) * 0.001 ) >> 0,
	        	minutes = seconds / 60,
	        	hours = minutes / 60,
	        	days = hours / 24,
	        	years = days / 365;

	        return (
	                seconds < 45 && this.time( 'seconds', seconds ) ||
	                seconds < 90 && this.time( 'minute', 1 ) ||
	                minutes < 45 && this.time( 'minutes', minutes ) ||
	                minutes < 90 && this.time( 'hour', 1 ) ||
	                hours < 24 && this.time( 'hours', hours ) ||
	                hours < 42 && this.time( 'day', 1 ) ||
	                days < 30 && this.time( 'days', days ) ||
	                days < 45 && this.time( 'month', 1 ) ||
	                days < 365 && this.time( 'months', days / 30 ) ||
	                years < 1.5 && this.time( 'year', 1 ) ||
	                this.time( 'years', years )
	                ) + ' ' + this.opts.msg.time.suffix;

		},

		/**
		 * Render
		 */
		render : function( template, p ) {

			var arr = [];
			
			switch( template ) {

				/**
				 * Chat Button
				 */
				case 'btn':

					arr = [
						'<div id="CX_btn_', p.box_id, '" style="display: none;color:',p.color,
						'; background-color:',p.bg_color,';width:',p.width,
						'px; ',p.h_pos,':', p.offset_x,';',
						'; border-radius: ',p.radius,';', p.v_pos,
						':', p.offset_y, ';" class="cx-chat-btn cx-online-btn ', p.class,'"><div class="cx-ico cx-ico-chat" style="display:',p.display_ico,'"></div><div class="cx-ico cx-ico-arrow-',
							p.direction,'" style="display:',p.display_arr,';"></div><div class="cx-title" style="display:', p.display_title,';">',
							p.title,'</div></div>'
					];

				break;

				/**
				 * Popup
				 */
				case 'popup':
					arr = [
						'<div id="CX_popup_', p.box_id, '" data-id="',p.box_id,'" class="cx-widget" style="display: none; width:',p.width,'px; ',p.h_pos,':', p.offset_x,';',p.v_pos,':', p.offset_y,';border-radius:' + p.radius + ';"><div class="cx-',p.class,'"><div id="CX_popup_header_', p.box_id, '" class="cx-header" style="border-radius:' + p.radius_h + ';background-color:',p.bg_color,'; color:',p.color,';"><div class="cx-title">',p.title,'</div><div class="cx-ico cx-ico-arrow-', p.direction, '"></div></div><div id="CX_popup_body_',p.box_id,'" class="cx-body ',p.body_class,'" style="border-radius:' + p.radius_f + ';">',p.body,'</div></div>'
					];
				break;

				/**
				 * Notification popup
				 */
				case 'ntf':

					arr = [
						'<div class="cx-body cx-ntf-msg"><div class="cx-lead">', p.lead, '</div></div>'
					];

					break;

				/**
				 * Connecting popup
				 */
				case 'connecting':

					arr = [
						'<div class="cx-body"><div class="cx-ntf cx-sending cx-conn">', p.lead, '</div></div>'
					];

					break;

				/**
				 * Login popup
				 */
				case 'login':
					arr = [
						'<div class="cx-lead">',p.lead,'</div><form id="CX_popup_form_', p.box_id, '" action="">',p.form,'<div class="cx-send"><div id="CX_popup_ntf_', p.box_id, '" class="cx-ntf"></div><a href="javascript:void(0)" id="CX_login_btn_', p.box_id, '" class="cx-form-btn" style="color:',p.btn_color,';background-color:',p.btn_bg,';" >',p.btn,'</a></div></form>'
					];
				break;

				/**
				 * Online (conversation) popup - Reply box on the top
				 */
				case 'online-top':
					arr = [
						'<div class="cx-cnv-reply"><div class="cx-cnv-input"><textarea id="CX_cnv_reply_', p.box_id,'" name="msg" class="cx-reply-input" placeholder="',p.reply_ph,'"></textarea></div></div><div id="CX_popup_ntf_', p.box_id, '"></div><div class="cx-cnv" id="CX_cnv_', p.box_id, '"><div class="cx-welc">',p.welc,'</div></div><div class="cx-tools"><a id="CX_tool_end_chat" href="javascript:void(0)">',p.end_chat,'</a></div>'
					];
				break;

				/**
				 * Online (conversation) popup - Reply box on the bottom
				 */
				case 'online-bottom':
					arr = [
						'<div id="CX_popup_ntf_', p.box_id, '"></div><div class="cx-cnv" id="CX_cnv_', p.box_id, '"><div class="cx-welc">',p.welc,'</div></div><div class="cx-tools"><a id="CX_tool_end_chat" href="javascript:void(0)">',p.end_chat,'</a></div><div class="cx-cnv-reply"><div class="cx-cnv-input"><textarea id="CX_cnv_reply_', p.box_id,'" name="msg" class="cx-reply-input" placeholder="',p.reply_ph,'"></textarea></div></div>'
					];
				break;

				/**
				 * Basic conversation popup
				 */
				 case 'online-basic':
					arr = [
						'<div class="cx-cnv-reply"><div class="cx-cnv-input"><textarea name="msg" class="cx-reply-input" id="CX_cnv_reply_', p.box_id, '" placeholder="',p.reply_ph,'"></textarea></div></div><div id="CX_popup_ntf_', p.box_id, '"></div><div id="CX_cnv_',p.box_id,'" class="cx-cnv-wrap"><div id="CX_load_msg_',p.box_id,'" class="cx-load-msg">',p.load_msg,'</div></div><div id="CX_cnv_user_info_',p.box_id,'" class="cx-user-meta"></div>'
					];

					break;

				/**
				 * Offline (contact form) popup
				 */
				case 'offline':
					arr = [
						'<div class="cx-lead">',p.lead,'</div><form id="CX_popup_form_', p.box_id, '" action="">',p.form,'<div class="cx-send"><div id="CX_popup_ntf_', p.box_id, '" class="cx-ntf"></div><a href="javascript:void(0)" id="CX_send_btn_', p.box_id, '" class="cx-form-btn" style="color:',p.btn_color,';background-color:',p.btn_bg,';" >',p.btn,'</a></div></form>'
					];

				break;

				/**
				 * User item
				 */
				case 'user_item':
					arr = [
						'<li id="CX_ls_usr_', p.id, '" data-id="',p.id,'" data-cnv-id="', p.cnv_id, '" data-name="', p.username, '" data-count="0" class="', p.class, '"><div class="cx-avatar">',p.avatar,'</div><div class="cx-username">',p.username, ' <span class="cx-count"></span>', p.icons,'</div><div class="cx-meta">',p.meta,'</div></li>'
					];
				break;

				/**
				 * Chat lines
				 */
				case 'chat_line':
					arr = [
						'<div id="CX_msg_',p.msg_id,'" class="cx-cnv-line ',p.class,'"><div class="cx-avatar cx-img" style="background-color:',p.color,'">',p.avatar,'</div><div class="cx-cnv-msg"><div title="',p.date,'" class="cx-cnv-time">',p.time,'</div><div class="cx-cnv-author">',p.name,':</div> <span class="cx-cnv-msg-detail">',p.msg,'</span></div></div><div class="cx-clear"></div>'
					];
				break;
				
				/**
				 * Input field (text, email, number etc.)
				 */
				case 'form_input':

					arr = [
						'<div class="cx-line"><label for="CX_field_',p.id,
						'"><span class="cx-title">',p.title,'</span> ',p.after_label,
						':</label><input type="',p.type,'" name="',p.name,'" id="CX_field_',p.id,
						'" placeholder="',p.ph,'" class="cx-field" value="',p.val,'"></div>'
					];
					
				break;

				/**
				 * Textarea field
				 */
				case 'form_textarea':

					arr = [
						'<div class="cx-line"><label for="CX_field_',p.id,
						'"><span class="cx-title">',p.title,'</span> ', p.after_label,
						':</label><textarea id="CX_field_',p.id,'" name="',p.name,
						'" placeholder="',p.ph,'" class="cx-field">',p.val,'</textarea></div>'
					];
					
				break;


			}

			// A single array join is faster than
			// multiple concatenations
			return arr.join('');

		},

		/**
		 * Trigger
		 */
		 trigger : function( event, p ) {
			
			var ret = this.opts[event].call(this, p);

			if( ret === false )
				return false;

		 },

		/** 
		 * Display notification
		 */
		display_ntf : function( ntf, type ) {

			$( '#CX_popup_ntf_' + this.data.box_id ).removeClass().addClass('cx-ntf cx-' + type).html( ntf ).fadeIn(300);



		},

		/** 
		 * Clean notification
		 */
		clean_ntf : function() {

			$( '#CX_popup_ntf_' + this.data.box_id ).html('').hide();

		},

		/** 
		 * Shade color
		 * original code: Pimp Trizkit (http://stackoverflow.com/a/13542669/272478)
		 */
		shade_color : function( color, percent ) {
			var num = parseInt(color.slice(1),16), 
				amt = Math.round(2.55 * percent), 
				R = (num >> 16) + amt, 
				B = (num >> 8 & 0x00FF) + amt, 
				G = (num & 0x0000FF) + amt;
			
			return "#" + (0x1000000 + (R<255?R<1?0:R:255)*0x10000 + (B<255?B<1?0:B:255)*0x100 + (G<255?G<1?0:G:255)).toString(16).slice(1);
		},

		/** 
		 * Check if foreground color should be white?
		 * original code: Alnitak (http://stackoverflow.com/a/12043228/272478)
		 */
		use_white : function( c ) {
			var c = c.substring(1);      // strip #
			var rgb = parseInt(c, 16);   // convert rrggbb to decimal
			var r = (rgb >> 16) & 0xff;  // extract red
			var g = (rgb >>  8) & 0xff;  // extract green
			var b = (rgb >>  0) & 0xff;  // extract blue

			var luma = 0.2126 * r + 0.7152 * g + 0.0722 * b; // per ITU-R BT.709

			if ( luma < 180 )
				return true; // use white
			
			return false; // use black
		},

		/**
		 * Escape HTML string
		 */
		escape_html : function ( str ) {

			var map = this.data.entity_map;

			return String( str ).replace(/[&<>"'\/]/g, function (s) {
				return map[s];
			});
			
		},
		/** 
		 * Play sound
		 */
		play_sound : function ( sound_name ) {
			
			// Add source into <audio> tag
			function add_source(e, path) {
				$('<source>').attr('src', path).appendTo(e);
			}

			var audio = $('<audio />', {
				autoPlay : 'autoplay'
			});

			add_source( audio, cx.plugin_url + '/assets/sounds/' + sound_name + '.mp3' );
			add_source( audio, cx.plugin_url + '/assets/sounds/' + sound_name + '.ogg' );
			add_source( audio, cx.plugin_url + '/assets/sounds/' + sound_name + '.wav' );

			audio.appendTo('body');
		},

		/** 
		 * Animate
		 */
		animate : function( obj, anim ) {

			// Resize window to ensure chat box is responsive
			$(window).trigger('resize');

			var direction = ( this.opts.gravity.charAt(0) == 'n' ) ? 'Down' : 'Up';

			// Speed Up animation?
			if( this.opts.speed_up ) {
				obj.addClass( 'cx-' + anim + direction + ' cx-anim' );

			// Normal
			} else
				obj.addClass( 'cx-' + anim + direction + ' cx-anim cx-hinge' );

			// Remove CSS animation
			setTimeout( function() {
				obj.removeClass('cx-anim cx-hinge cx-' + anim + direction);

			}, this.data.anim_delay);
		},

		/**
		 * Custom POST wrapper
		 */
		post : function ( mode, data, callback ) {

			var self = this;

			$.post( cx.ajax_url + '?action=cx_ajax_callback&mode=' + mode, data, callback, 'json' )
			.fail(function (jqXHR) {
				
				// Log error
				console.log(mode, ': ', jqXHR);
				
				return false;

			});

		},

		/**
		 * Create browser log
		 */
		log : function( msg, data ) {

			if( this.opts.debug ) {
				console.log( msg, data );
			}
		},

		/**
		 * Validate email
		 */
		validate_email : function( email ) {
			var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
			return re.test( email );
		},

		/**
		 * MD5 hash (http://www.webtoolkit.info/javascript-md5.html)
		 */
		md5 : function(e) {
			function h(a,b){var c,d,e,f,g;e=a&2147483648;f=b&2147483648;c=a&1073741824;d=b&1073741824;g=(a&1073741823)+(b&1073741823);return c&d?g^2147483648^e^f:c|d?g&1073741824?g^3221225472^e^f:g^1073741824^e^f:g^e^f}function k(a,b,c,d,e,f,g){a=h(a,h(h(b&c|~b&d,e),g));return h(a<<f|a>>>32-f,b)}function l(a,b,c,d,e,f,g){a=h(a,h(h(b&d|c&~d,e),g));return h(a<<f|a>>>32-f,b)}function m(a,b,d,c,e,f,g){a=h(a,h(h(b^d^c,e),g));return h(a<<f|a>>>32-f,b)}function n(a,b,d,c,e,f,g){a=h(a,h(h(d^(b|~c),
e),g));return h(a<<f|a>>>32-f,b)}function p(a){var b="",d="",c;for(c=0;3>=c;c++)d=a>>>8*c&255,d="0"+d.toString(16),b+=d.substr(d.length-2,2);return b}var f=[],q,r,s,t,a,b,c,d;e=function(a){a=a.replace(/\r\n/g,"\n");for(var b="",d=0;d<a.length;d++){var c=a.charCodeAt(d);128>c?b+=String.fromCharCode(c):(127<c&&2048>c?b+=String.fromCharCode(c>>6|192):(b+=String.fromCharCode(c>>12|224),b+=String.fromCharCode(c>>6&63|128)),b+=String.fromCharCode(c&63|128))}return b}(e);f=function(b){var a,c=b.length;a=
c+8;for(var d=16*((a-a%64)/64+1),e=Array(d-1),f=0,g=0;g<c;)a=(g-g%4)/4,f=g%4*8,e[a]|=b.charCodeAt(g)<<f,g++;a=(g-g%4)/4;e[a]|=128<<g%4*8;e[d-2]=c<<3;e[d-1]=c>>>29;return e}(e);a=1732584193;b=4023233417;c=2562383102;d=271733878;for(e=0;e<f.length;e+=16)q=a,r=b,s=c,t=d,a=k(a,b,c,d,f[e+0],7,3614090360),d=k(d,a,b,c,f[e+1],12,3905402710),c=k(c,d,a,b,f[e+2],17,606105819),b=k(b,c,d,a,f[e+3],22,3250441966),a=k(a,b,c,d,f[e+4],7,4118548399),d=k(d,a,b,c,f[e+5],12,1200080426),c=k(c,d,a,b,f[e+6],17,2821735955),
b=k(b,c,d,a,f[e+7],22,4249261313),a=k(a,b,c,d,f[e+8],7,1770035416),d=k(d,a,b,c,f[e+9],12,2336552879),c=k(c,d,a,b,f[e+10],17,4294925233),b=k(b,c,d,a,f[e+11],22,2304563134),a=k(a,b,c,d,f[e+12],7,1804603682),d=k(d,a,b,c,f[e+13],12,4254626195),c=k(c,d,a,b,f[e+14],17,2792965006),b=k(b,c,d,a,f[e+15],22,1236535329),a=l(a,b,c,d,f[e+1],5,4129170786),d=l(d,a,b,c,f[e+6],9,3225465664),c=l(c,d,a,b,f[e+11],14,643717713),b=l(b,c,d,a,f[e+0],20,3921069994),a=l(a,b,c,d,f[e+5],5,3593408605),d=l(d,a,b,c,f[e+10],9,38016083),
c=l(c,d,a,b,f[e+15],14,3634488961),b=l(b,c,d,a,f[e+4],20,3889429448),a=l(a,b,c,d,f[e+9],5,568446438),d=l(d,a,b,c,f[e+14],9,3275163606),c=l(c,d,a,b,f[e+3],14,4107603335),b=l(b,c,d,a,f[e+8],20,1163531501),a=l(a,b,c,d,f[e+13],5,2850285829),d=l(d,a,b,c,f[e+2],9,4243563512),c=l(c,d,a,b,f[e+7],14,1735328473),b=l(b,c,d,a,f[e+12],20,2368359562),a=m(a,b,c,d,f[e+5],4,4294588738),d=m(d,a,b,c,f[e+8],11,2272392833),c=m(c,d,a,b,f[e+11],16,1839030562),b=m(b,c,d,a,f[e+14],23,4259657740),a=m(a,b,c,d,f[e+1],4,2763975236),
d=m(d,a,b,c,f[e+4],11,1272893353),c=m(c,d,a,b,f[e+7],16,4139469664),b=m(b,c,d,a,f[e+10],23,3200236656),a=m(a,b,c,d,f[e+13],4,681279174),d=m(d,a,b,c,f[e+0],11,3936430074),c=m(c,d,a,b,f[e+3],16,3572445317),b=m(b,c,d,a,f[e+6],23,76029189),a=m(a,b,c,d,f[e+9],4,3654602809),d=m(d,a,b,c,f[e+12],11,3873151461),c=m(c,d,a,b,f[e+15],16,530742520),b=m(b,c,d,a,f[e+2],23,3299628645),a=n(a,b,c,d,f[e+0],6,4096336452),d=n(d,a,b,c,f[e+7],10,1126891415),c=n(c,d,a,b,f[e+14],15,2878612391),b=n(b,c,d,a,f[e+5],21,4237533241),
a=n(a,b,c,d,f[e+12],6,1700485571),d=n(d,a,b,c,f[e+3],10,2399980690),c=n(c,d,a,b,f[e+10],15,4293915773),b=n(b,c,d,a,f[e+1],21,2240044497),a=n(a,b,c,d,f[e+8],6,1873313359),d=n(d,a,b,c,f[e+15],10,4264355552),c=n(c,d,a,b,f[e+6],15,2734768916),b=n(b,c,d,a,f[e+13],21,1309151649),a=n(a,b,c,d,f[e+4],6,4149444226),d=n(d,a,b,c,f[e+11],10,3174756917),c=n(c,d,a,b,f[e+2],15,718787259),b=n(b,c,d,a,f[e+9],21,3951481745),a=h(a,q),b=h(b,r),c=h(c,s),d=h(d,t);return(p(a)+p(b)+p(c)+p(d)).toLowerCase()
		},

		/**
		 * Gravatar
		 */
		gravatar : function( email_hash, size, use_company_avatar ) {
			
			var size = size || 80,
				default_avatar = ( use_company_avatar ) ? this.opts.company_avatar : cx.plugin_url + '/assets/img/default-avatar.png';
		 	
			return 'https://www.gravatar.com/avatar/' + email_hash + '.jpg?s=' + size + '&d=' + default_avatar;
		},

		/**
		 * Create new uniqe id
		 */
		uniqid : function( prefix, more_entropy ) {
		
			if (typeof prefix === 'undefined') {
				prefix = '';
			}

			var retId;
			var formatSeed = function(seed, reqWidth) {
				seed = parseInt(seed, 10)
				.toString(16); // to hex str
				if (reqWidth < seed.length) { // so long we split
				return seed.slice(seed.length - reqWidth);
				}
				if (reqWidth > seed.length) { // so short we pad
				return Array(1 + (reqWidth - seed.length))
				.join('0') + seed;
				}
				return seed;
			};

			// BEGIN REDUNDANT
			if (!this.php_js) {
				this.php_js = {};
			}
			// END REDUNDANT
			if (!this.php_js.uniqidSeed) { // init seed with big random int
				this.php_js.uniqidSeed = Math.floor(Math.random() * 0x75bcd15);
			}

			this.php_js.uniqidSeed++;

			retId = prefix; // start with prefix, add current milliseconds hex string
			retId += formatSeed(parseInt(new Date()
			.getTime() / 1000, 10), 8);
			retId += formatSeed(this.php_js.uniqidSeed, 5); // add seed hex string
			if (more_entropy) {
				// for more entropy we add a float lower to 10
				retId += (Math.random() * 10)
				.toFixed(8)
				.toString();
			}

			return retId;
		}

	};

	/*
	 * Plugin wrapper, preventing against multiple instantiations and
	 * allowing any public function to be called via the jQuery plugin,
	 * e.g. $(el).CX('functionName', arg1, arg2, ...)
	 */
	$.fn[CX] = function ( arg ) {

		var args, instance;
		
		// only allow the plugin to be instantiated once
		if (!( this.data( dataPlugin ) instanceof Plugin )) {

			// if no instance, create one
			this.data( dataPlugin, new Plugin( this ) );
		}
		
		instance = this.data( dataPlugin );
		
		/*
		 * because this boilerplate support multiple elements
		 * using same Plugin instance, so element should set here
		 */
		instance.el = this;

		// Is the first parameter an object (arg), or was omitted,
		// call Plugin.init( arg )
		if (typeof arg === 'undefined' || typeof arg === 'object') {
			
			if ( typeof instance['init'] === 'function' ) {
				instance.init( arg );
			}
			
		// checks that the requested public method exists
		} else if ( typeof arg === 'string' && typeof instance[arg] === 'function' ) {
		
			// copy arguments & remove function name
			args = Array.prototype.slice.call( arguments, 1 );
			
			// call the method
			return instance[arg].apply( instance, args );
			
		} else {
		
			$.error('Method ' + arg + ' does not exist on jQuery.' + CX);
			
		}
	};

}(jQuery, window, document));