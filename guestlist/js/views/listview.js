CheckinApp.Views.ListView = Backbone.View.extend({

    list_element: '.chk_list_main',
    collection: null,
    base_collection: null,

    template: _.template($('#listview').html()),

    events: {
        "input #search": "processSearch",
        "click button.close-icon": "reset",
        "click #view_all_events": "viewAllEvents",
        "click #view_current_events": "viewCurrentEvents"
    },

    "processSearch": function(e) {
        var search_string = $(e.currentTarget).val().toLowerCase();
        this.collection.sort();
        var param = ['name'];
        if (this.model.get('list_type') === 'ticket') {
            param = ['name', 'id'];
        }
        var filtered_events = this.collection.search(search_string, param, this.showAllEvents, this.model.get('list_type'));
        var results = [];
        filtered_events.each(function(model) {
            var item_row = new CheckinApp.Views.ListRow({"model": model, list_template: this.model.get('list_template'), route: this.route});
            results.push(item_row.render().el);
        }, this);
        if (results.length == 0) {
            $(this.list_element).html($('#search_no_results').html());
        } else {
            $(this.list_element).html(results);
        }
        return this;
    },

    initialize: function(options) {
        var self = this;
        this.model = options.model;
        this.events_count = options.events_count;
        this.showAllEvents = options.showAllEvents;
        this.route = options.route;
        this.time = new Date();
        if (this.model.get('list_type') == 'ticket' && this.model.get('event_id') == null) {
            Backbone.history.navigate("#", true);
        }

        this.collection = options.collection;

        CheckinApp.getVent().on('event_id:changed', this.eventIdChanged, this);
        CheckinApp.getVent().on('current_event:changed', this.eventNameChanged, this);

        this.listenTo(this.collection, 'sync', function(model, resp) {
            if (resp) {
                this.add_all();
                this.countEvents();
            }
        });

        if (this.events_count) {
            this.events_count.fetch();
            this.listenTo(this.events_count, 'change', this.countEvents);
        }

        this.listenTo(this.model, 'change', this.render);

        this.collection.fetch().success(function () {
            if (self.model.get('list_type') === 'ticket' || self.model.get('list_type') === 'guest') {
                window.pubnub.subscribe({
                    channel: 'event_' + self.model.get('event_id') + '_' + self.model.get('list_type') + 'list',
                    callback: function(message) {
                        self.fetchCurrentCollection();
                    }
                });
            }
        });

    },

    render: function() {
        var bodyEl = $('body');
        var logo = bodyEl.find('logo');
        var navigation = bodyEl.find('.navig');
        if (this.model.get('list_type') === 'event' && this.route === 'eventlist') {
            navigation.remove();
            logo.text(CheckinApp.getSession().getUser().getCurrentBrandName());
        }

        $(this.el).html(this.template(this.model.attributes));
        if (this.model.get('list_type') === 'event') {
            $('.all_events').show();
        } else {
            $('.all_events').hide();
        }

        if (this.route === 'sales') {
            $('.checkin_tabs').hide();
            $('.reporting_tabs').show();
        } else {
            $('.checkin_tabs').show();
            $('.reporting_tabs').hide();
        }

        return this;
    },

    eventNameChanged: function(options) {
        this.model.set('event_name', options.current_event);
    },

    eventIdChanged: function(options) {
        this.model.set('event_id', options.event_id);
    },

    add_list_row: function(model) {
        var view = new CheckinApp.Views.ListRow({"model": model, list_template: this.model.get('list_template'), route: this.route, collection_name: this.model.get('collection_name')});
        var el = view.render().el;
        $(this.list_element).append(el);
    },

    add_count: function() {
        var total,
            el = $('.events_count');
        if (Backbone.history.fragment === 'guestlist') {
            total = this.events_count.attributes.guestlist;
        } else if (Backbone.history.fragment === 'ticketlist') {
            total = this.events_count.attributes.tickets;
        } else {
            return el.empty();
        }
        return el.empty().append('<span id="count_checked">'+ total + '</span>' + ' of ' + '<span id="count_tottal">' + this.collection.length + '</span>');
    },

    fetchCurrentCollection: function() {
        var time = parseInt(this.time.getTime() / 1000);
        var tickets = new CheckinApp.Collections.Tickets({"event_id": this.model.attributes.event_id, collection_name: this.model.get('collection_name')}),
            self = this;
        tickets
            .fetch({if_modified: true, time: time})
            .success(function() {
                if (tickets.length > 0) {
                    self.collection.add(tickets.models, {merge: true});
                    self.countEvents();
                    self.time = new Date();
                }
            });
    },

    countEvents: function() {
		var self = this;
        if (this.events_count) {
            this.events_count
                .fetch()
                .success(function() {
                    self.add_count();
                });
        }
    },

    add_all: function() {
        if ($('#search').length) {
            var search_string = $('#search').val().toLowerCase();
            if (search_string != '') {
                return false;
            }

        }
        $(this.list_element).empty();
        if (this.collection.length === 0) {
            return $(this.list_element).html($('#search_no_results').html());
        }
        if ( ! this.showAllEvents && this.model.get('list_type') === 'event') {
            return this.viewCurrentEvents();
        }
        this.viewAllEvents();
        CheckinApp.getVent().trigger('venuereport:renderVenueList', this.collection);
    },

    viewCurrentEvents: function() {
        if (this.model.get('list_type') === 'event') {
            this.showAllEvents = false;
            $('#view_all_events').show();
            $('#view_current_events').hide();
        }
        $(this.list_element).empty();
        if (this.collection.length === 0) {
            return $(this.list_element).html($('#search_no_results').html());
        }
        this.collection.each(function(event){
            var eventIsActual = this.collection.checkEventIsActual(event);
            if (eventIsActual) {
                this.add_list_row(event);
            }
        }, this);
    },

    viewAllEvents: function() {
        if (this.model.get('list_type') === 'event') {
            this.showAllEvents = true;
            $('#view_all_events').hide();
            $('#view_current_events').show();
        }

        $(this.list_element).empty();
        if (this.collection.length === 0) {
            return $(this.list_element).html($('#search_no_results').html());
        }
        this.collection.each(this.add_list_row, this);
    },

    reset: function() {
        this.render();
        this.add_all();
        this.countEvents();
    }

});
