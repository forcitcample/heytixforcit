(function($, undefined) {
	"use strict";

	$(function() {

		$.rawContentHandler(function() {
			if ($.fn.tabs) {
				$('.wpv-tabs', this).each(function() {
					$(this).tabs({
						activate: function(event, ui) {
							var hash = ui.newTab.context.hash;
							var element = $(hash);
							element.attr('id', '');
							window.location.hash = hash;
							element.attr('id', hash.replace('#', ''));
						},
						heightStyle: 'content'
					});
				});
			}

			if ($.fn.accordion) {
				$('.wpv-accordion', this).accordion({
					heightStyle: 'content'
				}).each(function() {
					if ($(this).attr('data-collapsible') === 'true') $(this).accordion('option', 'collapsible', true).accordion('option', 'active', false);
				});
			}
		});

	});

})(jQuery);
