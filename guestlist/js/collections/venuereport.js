CheckinApp.Collections.VenueReport = Backbone.Collection.extend({
    model: CheckinApp.Models.VenueReport,

    url: function() {
        return CheckinApp.base_url + "/api/report/venue/" + this.venue_name;
    },

    initialize: function(models, options) {
        this.venue_name = options.venue_name;
    },
    parse: function (resp, options) {
        var self = this;
        return _.map(_.keys(resp), function (key) {
            var ticketsPerEvent = resp[key]["ticket-count"] / resp[key]["event-count"],
                guestlistTicketsPerEvent = resp[key]["gl-ticket-count"] / resp[key]["event-count"];
            return _.extend( {}, resp[key], {
                id: key,
                tickets_per_event: self.roundResult(ticketsPerEvent, 2),
                guestlist_tickets_per_event: self.roundResult(guestlistTicketsPerEvent, 2)
            });
        });
    },
    roundResult: function(num, dec) {
        return Math.round(num * Math.pow(10, dec)) / Math.pow(10, dec);
    }
});