CheckinApp.Views.Reporting = Backbone.View.extend({
    template: _.template($('#reporting').html()),
    el: ".content",
    events: {
        "click .download_csv": "downloadCSV",
        "click .show_venue": "showVenueReport",
        "click .show_event": "showEventReport",
        "click .sort_event_data": "sortEventData",
        "click .sort_venue_data": "sortVenueData"
    },

    initialize: function() {
        var self = this;
        var event_id = CheckinApp.getSession().getUser().getCurrentEventId();
        this.current_view = 'event';
        this.months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        this.selected_month = null;
        this.events_data = [];
        this.report_data = [];
        this.setCurrentView('event');
        this.getEventsByMonth();
    },

    showVenueReport: function () {
        this.setCurrentView('venue');
        $('.show_venue').hide();
        $('.show_event').show();
        $('.report_container').empty();
        if ($("#event_selector")[0]['value'] !== 'default') {
            var event_id = $("#event_selector")[0]['value'];
            this.getVenueNameByEventId(event_id);
        }
    },

    showEventReport: function () {
        this.setCurrentView('event');
        CheckinApp.getVent().trigger('reporting:setHeader',  {type: 'reporting', current_view: 'event'});
        $('.show_venue').show();
        $('.show_event').hide();
        $('.report_container').empty();
        if ($("#event_selector")[0]['value'] !== 'default') {
            this.getEventData($("#event_selector")[0]['value']);
        }
    },

    setCurrentView: function (current_view) {
        this.current_view = current_view;
        if (this.current_view == 'event') {
            this.current_template = _.template($('#event_data').html());
            this.current_header_template = _.template($('#event_report_header').html());
            this.current_body_template = _.template($('#event_report_body').html());
            this.current_table = 'event_report';
            this.current_container = 'report-event-wrapper';
        } else {
            this.current_template = _.template($('#venue_data').html());
            this.current_header_template = _.template($('#venue_report_header').html());
            this.current_body_template = _.template($('#venue_report_body').html());
            this.current_table = 'venue_report';
            this.current_container = 'report-venue-wrapper';
        }
    },

    downloadCSV: function (e) {
        var content = null;
        content = $('#' + this.current_table).table2CSV({delivery:'value'});
        var aLink = document.createElement('a');
        var evt = document.createEvent("HTMLEvents");
        evt.initEvent("click");
        aLink.download = this.fileNameCSV();
        aLink.href = 'data:text/csv;charset=utf8,' + encodeURIComponent(content);
        aLink.dispatchEvent(evt);
    },

    fileNameCSV: function () {
        if ($("#event_selector")[0]['value'] !== 'default') {
            var event_id = $("#event_selector")[0]['value'];
            var file_name = '';
            file_name = (this.current_event.get("name").toLowerCase() + '_' + this.current_event.get("start_date")).replace(/\s+/g, '_') + '.csv';
            return file_name;
        }
    },

    sortVenueData: function (e) {
        var sort_by_param = $(e.currentTarget).attr('data-param');
        var sort_order = $(e.currentTarget).attr('data-order');
        this.report_data.models = this.report_data.sortBy(function(collection_item) {
            if (typeof collection_item.get(sort_by_param) === 'string') {
                return sort_order == 1 ? collection_item.get(sort_by_param) : collection_item.get(sort_by_param).charCodeAt() * -1;
            } else {
                return sort_order == 1 ? collection_item.get(sort_by_param) : -collection_item.get(sort_by_param);
            }
        });
        CheckinApp.getVent().once('renderingFinished', function () {
            $('.' + e.currentTarget.className.split(" ")[0]).filter('.' + e.currentTarget.className.split(" ")[1]).attr('data-order', -1 * sort_order);
        });
        this.renderReportData();
    },

    sortEventData: function (e) {
        var sort_by_param = $(e.currentTarget).attr('data-param');
        var sort_order = $(e.currentTarget).attr('data-order');

        this.report_data = _.sortBy(this.report_data, function(collection_item) {
            if (typeof collection_item[sort_by_param] === 'string') {
                return sort_order == 1 ? collection_item[sort_by_param] : collection_item[sort_by_param].charCodeAt() * -1;
            }
            else {
                return sort_order == 1 ? collection_item[sort_by_param] : -collection_item[sort_by_param];
            }
        });
        CheckinApp.getVent().once('renderingFinished', function () {
            $('.' + e.currentTarget.className.split(" ")[0]).filter('.' + e.currentTarget.className.split(" ")[1]).attr('data-order', -1 * sort_order);
        });
        this.renderReportData();
    },

    getVenueNameByEventId: function(event_id) {
        this.updateCookie(event_id);
        this.getVenueData(this.current_event.get("venue_name"));
        CheckinApp.getVent().trigger('reporting:setHeader',  {type: 'reporting', current_view: 'venue'});
    },

    monthIsChanged: function () {
        var self = this;
        $("#month_selector").change(function (e) {
            self.getEventsByMonth(e.currentTarget.value);
        });
    },

    eventIsChanged: function () {
        var self = this;
        $("#event_selector").change(function (e) {
            self.updateCookie(e.currentTarget.value);
            if (self.current_view === 'venue') {
                self.getVenueNameByEventId(e.currentTarget.value);
                CheckinApp.getVent().trigger('reporting:setHeader',  {type: 'reporting', current_view: 'venue'});
            } else {
                self.getEventData(e.currentTarget.value);
            }
        });
    },

    updateCookie: function (event_id) {
        var self = this;
        this.events_data.forEach(function(event) {
            if (event.id == event_id) {
                self.current_event = event;
                CheckinApp.getSession().getUser().setCurrentEventId(event.get('id'));
                CheckinApp.getSession().getUser().setCurrentEvent(event.get('name'));
                CheckinApp.getSession().getUser().setCurrentVenueName(event.get("venue_name"));
                CheckinApp.getSession().getUser().setCurrentVenueLogo(event.get("venue_logo"));
                CheckinApp.updateCookie();
            }
        });
    },

    getEventData: function (event_id) {
        var self = this;
        this.report_data = new CheckinApp.Collections.EventReport([], {"event_id": event_id});
        this.report_data.fetch().success(function () {
            self.report_data = self.report_data.models[0].get('lines');
            self.renderReportData();
        });
    },

    getVenueData: function (venue_name) {
        var self = this;
        this.report_data = new CheckinApp.Collections.VenueReport([], {"venue_name": venue_name});
        this.report_data.fetch().success(function () {
            self.renderReportData();
        });
    },

    getEventsByMonth: function(month) {
        var self = this;
        this.selected_month = month || this.getCurrentMonth();
        this.events_data = [];
        var event_collection = new CheckinApp.Collections.Events([], {"user_id": CheckinApp.getSession().getUser().getId()});
        event_collection.fetch()
            .success(function () {
                event_collection.each(function (event) {
                    var event_date = new Date(event.get('start_date').replace(' ', 'T'));
                    var event_month = event_date.getMonth();
                    if (event_month == self.selected_month) {
                        self.events_data.push(event);
                    }
                });
                self.renderSelects();
            });
    },


    getCurrentMonth: function () {
        var now = new Date();
        return current_month = now.getMonth();
    },

    wideringOnScroll: function () {
        var className = 'report-' + this.current_view + '-wrapper';
        if (document.getElementsByClassName(className)[0].scrollHeight - document.getElementsByClassName(className)[0].offsetHeight) {
            $('.' + className).css('width', '102%');
            $('.' + this.current_view + '-table').css('padding-right', '40px');
        }
    },

    renderSelects: function() {
        $(this.el).html(this.template({months: this.months, events: this.events_data}));
        $("#month_selector").val(this.selected_month);
        $("#event_selector").val('default');
        if (this.current_view === 'event') {
            $('.show_venue').show();
            $('.show_event').hide();
        }
        else {
            $('.show_venue').hide();
            $('.show_event').show();
        }
        this.monthIsChanged();
        this.eventIsChanged();
    },

    renderReportData: function () {
        $('.instruction').show();
        $('.report_container').html(this.current_template);
        $('.' + this.current_container).html(this.current_body_template({data: this.report_data}));
        $('.header_container').html(this.current_header_template);
        CheckinApp.getVent().trigger('renderingFinished');
        this.wideringOnScroll();
    },

    render: function() {
        this.$el.html(this.template({months: this.months, events: this.events_data}));
        var bodyEl = $('body');
        var navigation = bodyEl.find('.navig');
        $('.checkin_tabs').hide();
        $('.reporting_tabs').show();
        this.renderSelects();
        return this;
    }

});