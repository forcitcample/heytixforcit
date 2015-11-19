CheckinApp.Models.VenueReport = Backbone.Model.extend({
    defaults:{
        "gl-ticket-count": 0,
        "ticket-count": 0,
        "event-count": 0,
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