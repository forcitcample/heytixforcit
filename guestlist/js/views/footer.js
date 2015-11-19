CheckinApp.Views.Footer = Backbone.View.extend({
    template: _.template($('#footer').html()),

    render: function() {
        this.$el.html(this.template);
    }
});