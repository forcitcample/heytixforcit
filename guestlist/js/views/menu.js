CheckinApp.Views.Menu = Backbone.View.extend({
    template: _.template($('#menu').html()),
    initialize: function(options) {
        this.title = options.title;
    },

    render: function(options) {
        var bodyEl = $('body');
        var navigation = bodyEl.find('.navig');
        navigation.remove();
        $(this.el).html(this.template({menu_title: this.title}));
        return this;
    }

});