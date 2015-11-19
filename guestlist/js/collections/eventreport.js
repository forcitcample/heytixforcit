CheckinApp.Collections.EventReport = Backbone.Collection.extend({
    model: CheckinApp.Models.EventReport,

    url: function() {
        return CheckinApp.base_url + "/api/report/event/" + this.event_id;
    },


    comparator: function(event_report_A, event_report_B) {
        if (event_report_A.get('check_in') > event_report_B.get('check_in')) {
            return -1;
        }
        if (event_report_A.get('check_in') < event_report_B.get('check_in')) {
            return 1;
        }
        return 0;
    },

    initialize: function(models, options) {
        this.event_id = options.event_id;
    }
});