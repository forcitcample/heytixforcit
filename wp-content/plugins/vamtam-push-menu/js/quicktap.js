/**
 * @see http://stackoverflow.com/a/21015636/635882
 */
(function($) {
	'use strict';

	if(Modernizr.touch) {
		var startTime = null,
			startTouch = null,
			isActive = false,
			scrolled = false;

		/* Constructor */
		window.WPVQuickTap = function(context) {
			var self = this;

			context.on("touchstart", function(evt) {
				startTime = evt.timeStamp;
				startTouch = evt.originalEvent.touches.item(0);
				isActive = true;
				scrolled = false;
			});

			context.on("touchend", function(evt) {
				// Get the distance between the initial touch and the point where the touch stopped.
				var duration = evt.timeStamp - startTime,
					movement = self.getMovement(startTouch, evt.originalEvent['changedTouches'].item(0)),
					isTap = !scrolled && movement < 5 && duration < 200;

				if (isTap) {
					$(evt.target).trigger('wpvQuickTap', evt);

					evt.preventDefault();
				}
			});

			context.on('scroll mousemove touchmove', function(evt) {
				if ((evt.type === "scroll" || evt.type === 'mousemove' || evt.type === 'touchmove') && isActive && !scrolled) {
					scrolled = true;
				}
			});
		};

		/* Calculate the movement during the touch event(s)*/
		WPVQuickTap.prototype.getMovement = function(s, e) {
			if (!s || !e) return 0;
			var dx = e.screenX - s.screenX,
				dy = e.screenY - s.screenY;
			return Math.sqrt((dx * dx) + (dy * dy));
		};
	}

})(jQuery);