CheckinApp = {
    "vent": _.extend({}, Backbone.Events),
    "session": null,
    "Models": {},
    "Collections": {},
    "Views": {},
    "Routers": {},
    'base_path': "/guestlist/checkin",
    "base_url": window.location.protocol + "//" + window.location.hostname + "/guestlist/checkin",
    "default_event_id": 28,

    getSession: function() {
        return this.session;
    },

    setSession: function(session) {
        this.session = session;
        return this;
    },

    getVent: function() {
        return this.vent;
    },

    clearSession: function() {
        this.getSession().setAuthenticated(false).setAuthorizationToken(null).setUser({});
        $.removeCookie('CheckinApp');
        return this;
    },

    setSessionFromCookie: function(cookie) {
        this.getSession()
            .setAuthenticated(cookie.authenticated)
            .setAuthorizationToken(cookie.authorization_token)
            .getUser()
            .setId(cookie.user.id)
            .setUsername(cookie.user.username)
            .setFirstName(cookie.user.firstname)
            .setLastName(cookie.user.lastname)
            .setCurrentBrandName(cookie.user.brand_name || cookie.user.current_venue_name)
            .setCurrentBrandLogo(cookie.user.brand || cookie.user.current_venue_logo)
            .setCurrentEventId(cookie.user.current_event_id)
            .setCurrentEvent(cookie.user.current_event)
            .setCurrentVenueName(cookie.user.current_venue_name)
            .setCurrentVenueLogo(cookie.user.current_venue_logo)
            .setRole(cookie.user.role);
    },

    updateCookie: function() {
        if($.cookie('CheckinApp') && this.getSession().isAuthenticated() != false) {
            $.cookie('CheckinApp', JSON.stringify(CheckinApp.getSession()), { expires: 7 });
        }
    },

    zombieViewFix: function() {
        Backbone.View.prototype.close = function() {
            if (this.onClose) {
                this.onClose();
            }
            this.remove();
        };
    },

    run: function() {
        this.zombieViewFix();

        Offline.options.checkOnLoad = false;
        Offline.options.interceptRequests = true;
        Offline.options.requests = true;
        Offline.options.checks = {xhr: {url: CheckinApp.base_path + '/api/online'}};
        Offline.on("down", function() {
            CheckinApp.getVent().trigger('connection:changed', {status: "down"});
        }, this);

        Offline.on("up", function() {
            CheckinApp.getVent().trigger('connection:changed', {status: "up"});
        }, this);

        CheckinApp
            .setSession(new CheckinApp.Models.Session())
            .getSession()
            .setUser(new CheckinApp.Models.User());
        return new CheckinApp.Routers.Default({'view': new CheckinApp.Views.Main()});
    }
};
