CheckinApp.Views.EventReport = Backbone.View.extend({
    template: _.template($('#eventreport').html()),

    initialize: function(options) {
        this.page = options.back;
        this.modalWindow = this.$el.parent().parent().parent().parent();
        this.listenTo(this.collection, 'sync', this.render);
        this.collection.fetch();
        this.bind('ok', this.okClicked);
        this.bind('cancel', this.cancelClicked);
    },

    render: function() {
        this.$el.html(this.template({data: this.collection.toJSON()}));
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