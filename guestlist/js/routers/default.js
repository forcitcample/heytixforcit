CheckinApp.Routers.Default = Backbone.Router.extend({
    view: null,

    public_routes: ['login'],

    routes:{
        "":"menu",
        "eventlist":"eventlist",
        "login": "login",
        "guestlist": "guestlist",
        "ticketlist":"ticketlist",
        "managerslist":"managerslist",
        "events": "eventlist",
        "reporting": "reporting",
        "sales": "sales",
        "organize(/)(:action)": "displayOrganize",
        "eventreport(/)(:event_id)": "eventreport",
        "venuereport(/)(:venue_name)": "venuereport"
    },

    initialize: function (options) {
        this.view = options.view;
        Backbone.history.start();
    },
    menu: function () {
        CheckinApp.getVent().trigger('main:renderListView', {title: 'MENU', type: 'menu'});
    },
    guestlist: function () {
        this.history = 'guestlist';
        CheckinApp.getVent().trigger('main:renderListView', {title: 'Guest List', type: 'guest', tab_hash: '#guestlist', collection_name: 'guestlist'});
    },
    ticketlist: function () {
        this.history = 'ticketlist';
        CheckinApp.getVent().trigger('main:renderListView', {title: 'Ticket List', type: 'ticket', tab_hash: '#ticketlist', collection_name: 'ticketlist'});
    },
    managerslist: function () {
        CheckinApp.getVent().trigger('main:renderListView', {title: 'Managers List', type: 'ticket', tab_hash: '#managerslist'});
    },
    eventlist: function () {
        this.history = 'eventlist';
        CheckinApp.getVent().trigger('main:renderListView', {title: 'Todays Events', type: 'event', collection_name: 'event', route: 'eventlist'});
    },
    reporting: function () {
        this.history = 'reporting';
        CheckinApp.getVent().trigger('main:renderListView', {title: 'REPORTING', type: 'reporting', current_view: 'event', tab_hash: '#reporting'});
    },
    sales: function () {
        this.history = 'sales';
        CheckinApp.getVent().trigger('main:renderListView', {title: 'Todays Events', type: 'event', tab_hash: '#sales', collection_name: 'event', route: 'sales'});
    },
    eventreport: function(eventId) {
        this.navigate('eventreport', {trigger: false});
        var collection = new CheckinApp.Collections.EventReport([], {"event_id": eventId});
        var view = new CheckinApp.Views.EventReport({collection: collection, back: this.history});

        var modal = new Backbone.BootstrapModal({
            content: view,
            animate: true
        });
        modal.open();
    },
    venuereport: function(venueName) {
        this.navigate('venuereport', {trigger: false});
        var collection = new CheckinApp.Collections.VenueReport([], {"venue_name": venueName});
        var view = new CheckinApp.Views.VenueReport({collection: collection, venue_name: venueName, back: this.history});

        var modal = new Backbone.BootstrapModal({
            content: view,
            animate: true
        });
        modal.open();
    },

    login: function() {
        var view = new CheckinApp.Views.Login({});
        view.render();
    },

	isLocalStorageNameSupported: function() {
		var testKey = 'test', storage = window.localStorage;
		try 
		{
			storage.setItem(testKey, '1');
			storage.removeItem(testKey);
			return true;
		} 
		catch (error) 
		{
			return false;
		}
	},
	
    before: function (route, params) {
        if($.cookie('CheckinApp') && CheckinApp.getSession().isAuthenticated() == false) {
            CheckinApp.setSessionFromCookie(JSON.parse($.cookie('CheckinApp')));
        }
		
		if ( ! this.isLocalStorageNameSupported()) {
			new CheckinApp.Views.SafariFixer();
			return false;
		} 
		
        var hasAccess = CheckinApp.getSession().isAuthenticated(); // If cookie exists they are logged in..

        if (!hasAccess) {
            this.navigate('login', true);
        } else {
            if (route == 'login') {
                if (CheckinApp.getSession().getUser().get('role') === 'venue manager') {
                    this.navigate('', true);
                    return false;
                }
                if (CheckinApp.getSession().getUser().get('role') === 'venue checkin') {
                    Backbone.history.navigate('eventlist', true);
                    return false;
                }
            }
            if ( route == '' || route == 'reporting' || route == 'sales') {
                if (CheckinApp.getSession().getUser().get('role') !== 'venue manager') {
                    Backbone.history.navigate("eventlist", true);
                    return false;
                }
            }
        }
        if((_.contains(this.public_routes, route) === false)) {
            return hasAccess; //return true if you want to proceed to routes else return false
        }
    },

    after: function(route, params) {
        if(route == 'logout') return false;
        else {
            CheckinApp.updateCookie();
            return true;
        }
    }
});
