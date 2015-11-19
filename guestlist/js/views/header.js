CheckinApp.Views.Header = Backbone.View.extend({
    template: _.template($('#header').html()),
    events: {
        "click .log_out a": "logout",
        "click .events a, .logo": "viewEvents"
    },

    initialize: function(data) {
        this.model = data.model;
        this.listenTo(this.model, 'change', this.render);
        CheckinApp.getVent().on('user:username:changed', this.usernameChanged, this);
        CheckinApp.getVent().on('user:current_venue_name:changed', this.venueNameChanged, this);
        CheckinApp.getVent().on('user:current_venue_logo:changed', this.venueLogoChanged, this);
        if(this.model.attributes.report.name == 'VENUE REPORT') {
            CheckinApp.getVent().on('venuereport:renderVenueList', this.renderVenueList, this)
        }
    },

    render: function() {
        this.$el.html(this.template(this.model.attributes));
        return this;
    },

    renderVenueList: function(collection) {
        collection = _.groupBy(collection.toJSON(), 'venue_name');
        this.model.set('eventsList', collection);
    },

    viewEvents: function(e) {
        e.preventDefault();
        Backbone.history.navigate("#/eventlist", true);
    },
    logout: function(e) {
        e.preventDefault();
        CheckinApp.clearSession();
        Backbone.history.navigate('#login', true);
    },
    usernameChanged: function(options) {
        this.model.set('username', options.username);
    },
    venueNameChanged: function(options) {
        this.model.set('venue_name', options.current_venue_name);
    },
    venueLogoChanged: function(options) {
        this.model.set('venue_logo', options.current_venue_logo);
    }
});