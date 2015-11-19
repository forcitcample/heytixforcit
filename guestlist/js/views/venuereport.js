CheckinApp.Views.VenueReport = Backbone.View.extend({
    template: _.template($('#venuereport').html()),

    initialize: function(options) {
        this.page = options.back;
        this.modalWindow = this.$el.parent().parent().parent().parent();
        this.listenTo(this.collection, 'sync', this.render);
        this.venue_name = options.venue_name;
        this.collection.fetch();
        this.bind('ok', this.okClicked);
        this.bind('cancel', this.cancelClicked);
    },

    render: function() {
        this.$el.html(this.template({data: this.collection.toJSON(), name: this.venue_name}));
        return this;
    },

    okClicked: function() {
        this.modalWindow.modal('hide');
        app.navigate(this.page);
    },

    cancelClicked: function() {
        this.modalWindow.modal('hide');
        app.navigate(this.page);
    }
});