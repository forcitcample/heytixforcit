/**
 * mlpushmenu.js v1.0.0
 * http://www.codrops.com
 *
 * Licensed under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 *
 * Copyright 2013, Codrops
 * http://www.codrops.com
 */
( function( window, $, undefined ) {
	'use strict';

	if(!Modernizr.csstransforms3d && !Modernizr.csstransforms) return;

	// taken from https://github.com/inuyaksa/jquery.nicescroll/blob/master/jquery.nicescroll.js
	function hasParent( e, id ) {
		if (!e) return false;
		var el = e.target||e.srcElement||e||false;
		while (el && el.id !== id) {
			el = el.parentNode||false;
		}
		return (el!==false);
	}

	// returns the depth of the element "e" relative to element with id=id
	// for this calculation only parents with classname = waypoint are considered
	function getLevelDepth( e, id, waypoint, cnt ) {
		cnt = cnt || 0;
		if ( e.id.indexOf( id ) >= 0 ) return cnt;
		if( $(e).hasClass(waypoint) ) {
			++cnt;
		}
		return e.parentNode && getLevelDepth( e.parentNode, id, waypoint, cnt );
	}

	function mlPushMenu( el, trigger, options ) {
		/*jshint validthis:true */
		var self = this;
		this.el = el;
		this.$el = $( el );
		this.trigger = trigger;
		this.options = $.extend( this.defaults, options );
		if( Modernizr.csstransforms3d || Modernizr.csstransforms ) {
			this._init();
		}
	}

	function translate(X) {
		return Modernizr.csstransforms3d ? 'translate3d(' + X + ',0,0)' : 'translateX('+X+')';
	}

	mlPushMenu.prototype = {
		defaults : {
			// classname for the element (if any) that when clicked closes the current level
			backClass : 'mp-back',
			duration: .7
		},
		_init : function() {
			// if menu is open or not
			this.open = false;
			// level depth
			this.level = 0;
			// the moving wrapper
			this.wrapper = $( '#mp-pusher' );
			// the mp-level elements
			this.levels = Array.prototype.slice.call( this.el.querySelectorAll( 'div.mp-level' ) );
			this.$levels = $( this.levels );
			// save the depth of each of these mp-level elements
			var self = this;
			this.levels.forEach( function( el ) { el.setAttribute( 'data-level', getLevelDepth( el, self.el.id, 'mp-level' ) ); } );
			// the menu items
			this.menuItems = Array.prototype.slice.call( this.el.querySelectorAll( 'li' ) );
			// if type == "cover" these will serve as hooks to move back to the previous level
			this.levelBack = Array.prototype.slice.call( this.el.querySelectorAll( '.' + this.options.backClass ) );
			// event type (if mobile use touch events)
			this.eventtype = 'click';

			this._initInteractions();
		},
		_initEvents : function() {
			var self = this;

			// open (or close) the menu
			this.trigger.bind( 'touchstart.openpushmenu, click.openpushmenu', function( ev ) {
				ev.stopPropagation();
				ev.preventDefault();

				if( self.open ) {
					self._resetMenu();
				} else {
					self._openMenu();
					// the menu should close if clicking somewhere on the body (excluding clicks on the menu)
					$( window ).bind( 'touchstart.closepushmenu click.closepushmenu', function( ev ) {
						if( self.open && !hasParent( ev.target, self.el.id ) ) {
							ev.preventDefault();
							self._resetMenu();
							$( window ).unbind( 'touchstart.closepushmenu click.closepushmenu' );
						}
					} );
				}
			} );
		},
		_initInteractions: function() {
			var self = this;

			window.vamtampmgs = window.GreenSockGlobals = {};
			window._gsQueue = window._gsDefine = null;

			Modernizr.load( [ {    load: [
				window.WpvPushMenu.jspath + 'gsap/Draggable.min.js',
				window.WpvPushMenu.jspath + 'gsap/ThrowPropsPlugin.min.js',
				window.WpvPushMenu.jspath + 'gsap/TweenLite.min.js',
				window.WpvPushMenu.jspath + 'gsap/CSSPlugin.min.js',
			    ],
			    complete: function () {
					window.GreenSockGlobals = window._gsQueue = window._gsDefine = null;

			        scripts_loaded();
			    }
			} ] );

			var scripts_loaded = function() {
				var win = $( window );

				var first_time = true;
				var logo_wrapper = $( 'header.main-header .logo-wrapper' );

				self.$el.css( {
					height: 'auto',
					bottom: 0
				} );

				var resize_callback = function() {
					if ( win.width() > window.WpvPushMenu.limit ) {
						self._resetMenu( true );
					}
				};

				var reposition_trigger = function() {
					if ( self.trigger.is( ':visible' ) && ! self.open ) {
						var wrapper_offset = self.wrapper.offset().top;

						self.$el.css( 'top', wrapper_offset );

						self.trigger.css( {
							top: logo_wrapper.offset().top + logo_wrapper.outerHeight() / 2 - wrapper_offset,
							left: '100%'
						} );

						if ( first_time ) {
							first_time = false;
							self.trigger.appendTo( self.$el )
							vamtampmgs.TweenLite.to( self.trigger, .5, { autoAlpha: 1, ease: vamtampmgs.Power4.easeOut } );
						}
					}
				}

				win.bind( 'resize touchmove', _.debounce( resize_callback, 100 ) );
				win.resize( _.debounce( reposition_trigger, 1000 ) );
				resize_callback();
				reposition_trigger();

				self._initEvents();

				self.draggable = vamtampmgs.Draggable.create( self.$el.find( '> ul > li > .mp-level' ), {
					type: "scrollTop",
					edgeResistance: 0.7,
					throwProps: true,
					dragClickables: true,
					onClick: function( e ) {
						var el = $( e.target );

						// back button
						if ( el.hasClass( 'mp-back' ) ) {
							e.preventDefault();
							e.stopPropagation();

							self.level = $(el).closest('.mp-level').data('level') - 1;
							if(self.level === 0) {
								self._resetMenu();
							} else {
								self._closeMenu();
							}

							return;
						}

						// regular menu items
						var next_level = el.find( '+ .mp-level' );
						if( next_level.length ) {
							e.preventDefault();
							e.stopPropagation();
							el.closest('.mp-level').addClass('mp-level-overlay');
							self._openMenu( next_level );
						} else {
							var href = el.attr( 'href' );

							if ( href ) {
								try {
									var target = String( el.attr( 'target' ) || 'self' ).replace( /^_/, '' );
									if ( target === 'blank' || target === 'new' ) {
										window.open( href );
									} else {
										window[target].location = href;
									}
								} catch (ex) {
									console.log( e );
								}
								self._resetMenu();
							}
						}
					}
				} );

				self.$el.find( '> ul > li > .mp-level' ).css( 'overflow-y', 'hidden' );
			};
		},
		_openMenu : function( subLevel ) {
			// increment level depth
			++this.level;

			document.getElementsByTagName("html")[0].style.overflow = 'hidden';

			if ( 'draggable' in this ) {
				this.draggable[0].scrollProxy.scrollTop( 0 );
			}

			// move the main wrapper
			var levelFactor = ( this.level - 1 ) * this.options.levelSpacing,
				translateVal = this.el.offsetWidth;


			// add class mp-pushed to main wrapper if opening the first time
			if( this.level === 1 ) {
				this.wrapper.addClass('mp-pushed');
				vamtampmgs.TweenLite.to( this.$el, this.options.duration, { x: 270, ease: vamtampmgs.Power3.easeOut } );
				this.open = true;
			} else if ( subLevel ) {
				// check that the number of show/hides corresponds with the one in _closeMenu
				subLevel.closest( 'li' ).siblings().hide();
				subLevel.prev().hide();
				this.$levels.not( subLevel ).find( '> .mp-level-header, > div > .mp-level-header' ).hide();
				subLevel.find( '> .mp-level-header, > div > .mp-level-header' ).show();
			}

			// add class mp-level-open to the opening level element
			$(subLevel || this.levels[0]).addClass('mp-level-open');
		},
		// close the menu
		_resetMenu : function(avoidresize) {
			// remove class mp-pushed from main wrapper
			this.wrapper.removeClass('mp-pushed');
			vamtampmgs.TweenLite.to( this.$el, this.options.duration, { x: 0, ease: vamtampmgs.Power2.easeOut } );
			document.getElementsByTagName("html")[0].style.overflow = '';
			this.open = false;

			// _closeMenu always closes the currently active level
			// we must run it multiple times to account for nested submenus
			// stop when the root level becomes active
			while ( this.level-- >= 0 ) {
				this._closeMenu();
			}

			this.level = 0;

			if(!avoidresize) {
				$(window).resize();

				setTimeout(function() {
					$(window).resize();
				}, 500);
			}
		},
		// removes classes mp-level-open from closing levels
		_closeMenu : function() {
			var self = this;

			this.$levels.each( function( i, level ) {
				level = $( level );
				if( level.data('level') >= self.level + 1 ) {
					level.removeClass('mp-level-open mp-level-overlay');
				} else if( Number( level.data('level') ) === self.level ) {
					level.removeClass('mp-level-overlay');

					// check that the number of show/hides corresponds with the one in _closeMenu
					self.$levels.not( level ).find( '> .mp-level-header, > div > .mp-level-header' ).hide();
					level.find( '> .mp-level-header, > div > .mp-level-header' ).show();
					level.prev().show();
					level.find( 'li, li > a' ).show();
				}
			} );
		}
	};

	// add to global namespace
	window.MlPushMenu = mlPushMenu;

} )( window, jQuery );