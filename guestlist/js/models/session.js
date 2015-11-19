CheckinApp.Models.Session = Backbone.Model.extend({
    defaults:{
        authenticated: false,
        authorization_token: '',
        user: null
    },

    initialize: function(options) {

    },

    getUser: function() {
        return this.get('user');
    },

    setUser: function(user) {
        this.set('user', user);
        return this;
    },

    getAuthorizationToken: function() {
        return this.get('authorization_token');
    },

    setAuthorizationToken: function(token) {
        this.set('authorization_token', token);
        return this;
    },

    isAuthenticated: function() {
        return this.get('authenticated');
    },

    setAuthenticated: function(authenticated) {
        this.set('authenticated', authenticated);
        return this;
    }
});