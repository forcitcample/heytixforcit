CheckinApp.Views.SafariFixer = Backbone.View.extend({
    template: _.template($('#safari_fixer').html()),
    el: '.main',
	events: {
		"click #go_to_login": "goToLogin"
	},
	
    initialize: function(options) {
		this.render();
    },

    render: function() {
        $(this.el).html(this.template);
        return this;
    },
	
	goToLogin: function() {
		Backbone.history.loadUrl();
		this.navigate('login', true);
	}
});