CheckinApp.Views.ModalWindow = Backbone.View.extend({
    tagName: 'div',
    template: _.template($('#ytyty').html()),
    events: {
        "click": "closeModal",
        "click a": "processClickEvent"
    },
    render: function() {
        this.$el.html(this.template(this.model.toJSON()));
        return this;
    },
    closeModal: function() {
        this.$el.parent().parent().parent().parent().modal('hide');
    },
    processClickEvent: function(e) {
        e.preventDefault();

        this.model.save({
            "checked_in": (this.model.get('checked_in') == 0) ? "1" : "0"
        });
        this.$el.parent().parent().parent().parent().modal('hide');
    }
});