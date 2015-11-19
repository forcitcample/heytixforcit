(function($, undefined) {
	"use strict";

	$.rawContentHandler(function(context) {

		var whitespace = 30;
		var defaults = {
			pager: false,
			controls: true,
			minSlides: 1,
			maxSlides: 10,
			slideMargin: whitespace,
			infiniteLoop: false,
			hideControlOnEnd: true
		};

		var container = $('.portfolios.scroll-x, .loop-wrapper.scroll-x, .woocommerce-scrollable.scroll-x', context || document);

		container.find("img.lazy").not(".jail-started, .loaded").addClass("jail-started").jail({
			speed : 1000,
			event : false
		});

		var scrollable_reduce_column_count = function( columns ) {
			if ( ! $( 'body' ).hasClass( 'responsive-layout' ) )
				return columns;

			var win_width = $( window ).width();

			if ( win_width <= 958)
				return Math.min( columns, 2 );

			if ( win_width > 958 && win_width < 1280 )
				return Math.min( columns, 3 );

			return columns;
		};

		var calcSlideWidth = function(maxSlides) {
			var columns = scrollable_reduce_column_count(maxSlides);

			return ( this.closest('.scrollable-wrapper').width() - whitespace * ( columns - 1 ) ) / columns;
		};

		var reloadSlider = function(el, maxSlides, slideWidth) {
			if( ! el || ! el.data('bxslider') || ! el.data('scrollable-loaded') )
				return;

			el.data('scrollable-loaded', false);

			var newSlideWidth = calcSlideWidth.call(el, maxSlides);

			if(newSlideWidth !== slideWidth) {
				slideWidth = newSlideWidth;

				el.data('bxslider').reloadSlider($.extend(defaults, {
					slideWidth: newSlideWidth
				}));
			}

			return slideWidth;
		};

		container.each(function() {
			var el = $('> ul', this),
				maxSlides = parseInt(el.data('columns'), 10),
				slideWidth = calcSlideWidth.call(el, maxSlides);

			el.data('bxslider', el.bxSlider($.extend(defaults, {
				slideWidth: slideWidth,
				onSliderLoad: function() {
					el.data('scrollable-loaded', true);

					if(el.data('wpv-loaded-once')) return;

					el.data('wpv-loaded-once', true);

					el.imagesLoaded(function() {
						if ( 'redrawSlider' in el.data('bxslider') ) {
							el.data('bxslider').redrawSlider();
						}
					});

					setTimeout(function() {
						$(window).smartresize(function() {
							slideWidth = reloadSlider(el, maxSlides, slideWidth);
						});
					}, 1500);

					el.bind('vamtam-video-resized', function() {
						if ( 'redrawSlider' in el.data('bxslider') ) {
							el.data('bxslider').redrawSlider();
						}
					});
				}
			})));
		});
	});
})(jQuery);