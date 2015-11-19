CheckinApp.Models.Checkedin = Backbone.Model.extend({
    idAttribute: 'event_id',
    defaults: {
        tickets: '',
        guestlist: ''
    },
    url: function() {
        return CheckinApp.base_url + "/api/events/" + this.event_id + "/checkedin/counts";
    },
    initialize: function(options) {
        this.event_id = options.event_id;
    }
});