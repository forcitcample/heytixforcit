CheckinApp.Collections.Events = CheckinApp.Collections.Searchable.extend({
    model: CheckinApp.Models.Event,
    event_id: 'current',
    url: function() { return CheckinApp.base_url + "/api/events/uid/" + this.user_id; },
    comparator: function(event) {
        return event.get('venue_name');
    },
    initialize: function(models, data) {
        this.user_id = data.user_id;
    }
});