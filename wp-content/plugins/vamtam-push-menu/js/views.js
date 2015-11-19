PM.Views.Root = Backbone.View.extend({
	el: $('.main-container'),
	initialize: function() {
		this.template = $('#wpvpm-menu-root').html();
		_.bindAll(this, 'render');
		this.listenTo( this.model, 'change', this.render );
		this.render();
	},
	render: function() {
		var content = new PM.Views.Item({
			model: new PM.Models.Item(this.model.toJSON())
		}).render();

		var scroller = $('<div></div>').addClass('mp-scroller').attr('id', 'mp-scroller'),
			pusher = $('<div></div>').addClass('mp-pusher').attr('id', 'mp-pusher');

		$(this.el)
		.wrap(pusher)
		.before( _.template(this.template)({
			content: content
		}) );

		$(this.el).wrap(scroller);
	}
});

PM.Views.Item = Backbone.View.extend({
	initialize: function() {
		this.template = $('#wpvpm-menu-item').html();
		_.bindAll(this, 'render');
	},
	render: function() {
		var content = '';

		_(this.model.get('children')).each(function(child) {
			var child_view = new PM.Views.Item({
				model: new PM.Models.Item(child)
			});

			content += child_view.render();
		});

		return _.template(this.template)(
			_.extend(this.model.toJSON(), {
				content: content
			})
		);
	}
});

$(function() {
	if('WpvPushMenu' in window && WpvPushMenu.items) {
		new PM.Views.Root({
			model: new PM.Models.Root(WpvPushMenu.items)
		});

		var trigger = $( '#mp-menu-trigger' );

		if(trigger) {
			new MlPushMenu( document.getElementById( 'mp-menu' ), trigger, {} );

			if('WPVQuickTap' in window) {
				// new WPVQuickTap( $('#mp-menu .mp-back, #mp-menu .has-children, #mp-menu-trigger') );
			}
		}
	}
});
