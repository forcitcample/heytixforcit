PM.Models.Root = Backbone.Model.extend({
	defaults: {
		title: '',
		description: '',
		type: 'root',
		children: []
	},
	initialize: function() {
		this.children = new PM.Collections.Item();
	}
});

PM.Models.Item = Backbone.Model.extend({
	defaults: {
		url: '',
		title: '',
		attr_title: '',
		description: '',
		classes: [],
		type: 'root',
		children: []
	}
});