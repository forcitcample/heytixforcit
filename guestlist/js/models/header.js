CheckinApp.Models.Header = Backbone.Model.extend({
    defaults:{
        "name": '',
        "logo": '',
        "username": '',
        "event_id": '',
        "venue_name": '',
        "eventsList": ''
    },
    initialize: function(options) {
        this.set('name', options.name || '');
        this.set('logo', options.logo || '');
        this.set('username', options.username || '');
        this.set('event_id', options.event_id || '');
        this.set('venue_name', options.venue_name || '');
        this.set('eventsList', options.eventsList || '');
    }
});