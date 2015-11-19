( function( $, undefined ) {
	'use strict';

	var win = $( window ),
		elem_factor = 0.75,
		win_factor = 0.7,
		win_height = 0;

	var explorer = /MSIE (\d+)/.exec( navigator.userAgent ),
		mobileSafari = navigator.userAgent.match( /(iPod|iPhone|iPad)/ ) && navigator.userAgent.match( /AppleWebKit/ );

	win.resize(function() {
		win_height = win.height();

		if (
			( explorer && parseInt( explorer[1], 10 ) === 8 ) ||
			mobileSafari ||
			$.WPV.MEDIA.layout['layout-below-max']
		) {
			$( '.wpv-grid.animated-active' ).removeClass( 'animated-active' ).addClass( 'animated-suspended' );
		} else {
			$( '.wpv-grid.animated-suspended' ).removeClass( 'animated-suspended' ).addClass( 'animated-active' );
		}
	}).resize();

	win.bind( 'scroll touchmove load', function() {
		var win_height = win.height(),
			all_visible = $( window ).scrollTop() + win_height,
			reduced_win_height = win_factor*win_height;

		$( '.wpv-grid.animated-active:not(.animation-ended)' ).each( function() {
			var precision = Math.max( 100, Math.min( reduced_win_height, elem_factor * $( this ).outerHeight() ) );
			var fix = $( this ).hasClass( 'animation-zoom-in' ) ? $( this ).height() / 2 : 0;

			if ( all_visible - precision > $( this ).offset().top - fix || mobileSafari ) {
				var el = $( this );

				el.addClass( 'animation-ended' );
			} else {
				return false;
			}
		} );
	} ).scroll();
} )( jQuery );