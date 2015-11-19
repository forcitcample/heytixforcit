CheckinApp.Models.EventReport = Backbone.Model.extend({
    idAttribute: 'security',
    defaults: {
        "order_id": '',
        "order_status": '',
        "order_status_label": '',
        "order_warning": false,
        "purchaser_name": '',
        "purchaser_email": '',
        "ticket": '',
        "attendee_id": '',
        "security": '',
        "product_id": '',
        "check_in": '',
        "provider": ''
    }
});