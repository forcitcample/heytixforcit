CheckinApp.Collections.Tickets = CheckinApp.Collections.Searchable.extend({
    model: CheckinApp.Models.Ticket,
    event_id: 'current',
    url: function() {
        return CheckinApp.base_url + "/api/events/" + this.event_id + "/" + this.collection_name;
    },

    initialize: function(data) {
        this.event_id = data.event_id;
        this.collection_name = data.collection_name;
    },

    comparator: function(ticket) {
        return ticket.get('checked_in');
    }
});