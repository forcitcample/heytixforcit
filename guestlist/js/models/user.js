CheckinApp.Models.User = Backbone.Model.extend({
    defaults: {
        id: null,
        username: '',
        firstname: '',
        lastname: '',
        current_event_id: 0,
        current_event: '',
        current_venue_name: '',
        current_venue_logo: '',
        brand_name: 'Clientele Connect',
        brand: '',
        role: ''
    },

    initialize: function(data) {

    },

    getId: function() {
        return this.attributes.id;
    },

    setId: function(id) {
        this.set('id', id);
        return this;
    },

    getUsername: function() {
        return this.get('username');
    },

    setUsername: function(username) {
        this.set('username', username);
        CheckinApp.getVent().trigger('user:username:changed', {username: this.getUsername()});
        return this;
    },

    getFirstName: function() {
        return this.get('firstname');
    },

    setFirstName: function(firstname) {
        this.set('firstname', firstname);
        return this;
    },

    getLastName: function() {
        return this.get('lastname');
    },

    setLastName: function(lastname) {
        this.set('lastname', lastname);
        return this;
    },

    getCurrentEventId: function() {
        return this.get('current_event_id');
    },

    setCurrentEventId: function(event_id) {
        this.set('current_event_id', event_id);
        CheckinApp.getVent().trigger('user:current_event_id:changed', {current_event_id: this.getCurrentEventId()});
        return this;
    },

    getCurrentEvent: function() {
        return this.get('current_event');
    },

    setCurrentEvent: function(event_name) {
        this.set('current_event', event_name);
        CheckinApp.getVent().trigger('user:current_event:changed', {current_event: this.getCurrentEvent()});
        return this;
    },

    getCurrentVenueName: function() {
        return this.get('current_venue_name');
    },

    setCurrentVenueName: function(venue_name) {
        this.set('current_venue_name', venue_name);
        var current_venue_name = (this.getCurrentVenueName() != undefined && this.getCurrentVenueName() != '') ? this.getCurrentVenueName() : this.getCurrentBrandName();
        CheckinApp
            .getVent()
            .trigger('user:current_venue_name:changed', {current_venue_name: current_venue_name});
        return this;
    },

    getCurrentVenueLogo: function() {
        return this.get('current_venue_logo');
    },

    setCurrentVenueLogo: function(venue_logo) {
        this.set('current_venue_logo', venue_logo);
        var current_venue_logo = (this.getCurrentVenueLogo() != undefined && this.getCurrentVenueLogo() != '') ? this.getCurrentVenueLogo() : this.getCurrentVenueName();
        CheckinApp.getVent().trigger('user:current_venue_logo:changed', {current_venue_logo: current_venue_logo});
        return this;
    },

    getCurrentBrandName: function() {
        return this.get('brand_name');
    },

    setCurrentBrandName: function(brand_name) {
        this.set('brand_name', brand_name);
        return this;
    },

    getCurrentBrandLogo: function() {
        return this.get('brand');
    },

    setCurrentBrandLogo: function(brand) {
        this.set('brand', brand);
        var current_brand_logo = (this.getCurrentBrandLogo() != undefined && this.getCurrentBrandLogo() != '') ? this.getCurrentBrandLogo() : this.getCurrentBrandName();
        CheckinApp.getVent().trigger('user:current_venue_logo:changed', {current_venue_logo: current_brand_logo});
        return this;
    },

    getRole: function() {
        return this.get('role');
    },

    setRole: function(role) {
        this.set('role', role);
        CheckinApp.getVent().trigger('user:role:changed', {role: this.getRole()});
        return this;
    }
});
