(function($, undefined) {
	"use strict";

	if(!Modernizr.csstransforms3d && !Modernizr.csstransforms) return;

	var PM = {
		Models: {},
		Collections: {},
		Views: {}
	};
