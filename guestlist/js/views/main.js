CheckinApp.Views.Main = Backbone.View.extend({
    template: _.template($('#main').html()),
    el: '.main',
    events: {
        "click .navg_ul li a": "changeTab",
        "click .bar_toggle": "toggleMenu"
    },

    initialize: function(options) {
		CheckinApp.getVent().on('main:renderListView', this.renderListView, this);
        CheckinApp.getVent().on('reporting:setHeader', function (data) {
            this.setHeader(this.getHeaderView(data));
            this.assign('.header', this.getHeader());
        }, this);
    },

    render: function() {
        $(this.el).html(this.template(CheckinApp.getSession().getUser().attributes));
        this.assign('.header', this.getHeader());
        this.assign('.content', this.getContent());
        this.assign('.footer', this.getFooter());
        return this;
    },
	
    getHeaderView: function(data) {
        var current_name,
            current_logo,
            current_report = {},
            user = CheckinApp.getSession().getUser();

        if(data.type == 'event' || data.type == 'menu' || (data.type == 'reporting' && data.current_view == 'event')) {
            current_name = user.getCurrentVenueName();
            current_logo = user.getCurrentBrandLogo();
            current_report.name = "VENUE REPORT";
            current_report.link = 'venuereport/' + current_name;
        } else {
            current_report.name = "EVENT REPORT";
            current_report.link = 'eventreport/' + user.getCurrentEventId();
            current_name = user.getCurrentVenueName();
            current_logo = user.getCurrentVenueLogo();
        }

        var model = new CheckinApp.Models.Header({
            name: current_name,
            logo: current_logo,
            report: current_report,
            username: user.getUsername()
        });

        return new CheckinApp.Views.Header({model: model});
    },

    getContentView: function(data) {

        /**
         * zombie view fix;
         */
        if (this.currentView) {
            this.currentView.showAllEvents ? this.showAllEvents = true : this.showAllEvents = false;
            this.currentView.close();
        }

        if (data.type === 'menu') {
            this.currentView = new CheckinApp.Views.Menu(data);
            return this.currentView;
        }

        if (data.type === 'reporting') {
            this.currentView = new CheckinApp.Views.Reporting();
            return this.currentView;
        }

        var current_event_id = CheckinApp.getSession().getUser().getCurrentEventId();
        var event_id = (current_event_id != 0) ? current_event_id : null;
        var list_template = '',
            list_title = '',
            collection_name = '',
            route = '',
            collection = null,
            eventsCount = null;

        if(data.type == 'event') {
            collection = new CheckinApp.Collections.Events([], {"user_id": CheckinApp.getSession().getUser().getId()});
            list_template = '#list_row_event';
            list_title = 'Todays Events';
            route = data.route;
        } else {
            collection = new CheckinApp.Collections.Tickets({"event_id": event_id, collection_name: data.collection_name});
            list_template = '#list_row_ticket';
            list_title = CheckinApp.getSession().getUser().getCurrentEvent();
            eventsCount = new CheckinApp.Models.Checkedin({"event_id": event_id});
        }

        var model = new CheckinApp.Models.ListView({
            event_id: event_id,
            list_title: list_title,
            list_template: list_template,
            list_type: data.type,
            collection_name: data.collection_name
        });

        this.currentView = new CheckinApp.Views.ListView({"model": model, collection: collection, events_count: eventsCount, showAllEvents: this.showAllEvents, route: route});

        return this.currentView
    },

    getFooterView: function(data) {
        return new CheckinApp.Views.Footer(data);
    },

    getHeader: function() {
        return this.header;
    },

    setHeader: function(header) {
        this.header = header;
        return this;
    },

    getContent: function() {
        return this.content;
    },

    setContent: function(content) {
        this.content = content;
        return this;
    },

    getFooter: function() {
        return this.footer;
    },

    setFooter: function(footer) {
        this.footer = footer;
        return this;
    },

    assign: function (selector, view) {
        var selectors;

        if (_.isObject(selector)) {
            selectors = selector;
        }
        else {
            selectors = {};
            selectors[selector] = view;
        }

        if (!selectors) return;

        _.each(selectors, function (view, selector) {
            view.setElement(this.$(selector)).render();
        }, this);
    },

    logout: function(e) {
        e.preventDefault();

        CheckinApp.getSession()
            .setUser(null)
            .setAuthenticated(false);

        $.removeCookie('CheckinApp');
        Backbone.history.navigate('login', true);
    },

    toggleMenu: function(e) {
        e.preventDefault();
        $(".navg_ul").slideToggle();
    },

    changeTab: function(e) {
        var el = $(e.currentTarget);
        el.parent().parent().find('a').each(function() {
            $(this).removeClass('active');
        });
        el.addClass('active');
    },

    activateTab: function(value) {
        $(".navg_ul a").each(function() {
            if($(this).attr('href') == value) {
                $(this).addClass('active');
            }  else {
                $(this).removeClass('active');
            }
        });
    },

    viewEvents: function(e) {
        e.preventDefault();
        CheckinApp.getSession().getUser().setCurrentVenueLogo();
        Backbone.history.navigate("events", true);
    },

    renderListView: function(data) {
        this
            .setHeader(this.getHeaderView(data))
            .setContent(this.getContentView(data))
            .setFooter(this.getFooterView())
            .render()
            .activateTab(data.tab_hash);
    }
});