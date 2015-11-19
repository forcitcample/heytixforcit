CheckinApp.Collections.Searchable = Backbone.Collection.extend({

    url: '',

    search: function(letters, field, showAllEvents, type){
        var self = this;
        if (letters == "" && showAllEvents) {
            return this;
        }
        return _(
            this.filter(function(data) {
                var eventIsActual = true;
                if ( ! showAllEvents && type === 'event') {
                    eventIsActual = self.checkEventIsActual(data);
                }
                var concur = false;
                if (eventIsActual) {
                    _.each(field, function(property){
                        if (data.get(property).toLowerCase().indexOf(letters) > -1) {
                            concur = true;
                            return false;
                        }
                    });
                }
                return concur;
            })
        );
    },

    checkEventIsActual: function(event){
        var now = new Date();
        var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        var value_of_today = today.valueOf();
        var event_date = new Date(event.get('start_date').replace(' ', 'T'));
        var event_day = new Date(event_date.getFullYear(), event_date.getMonth(), event_date.getDate());
        var value_of_event_day =  event_day.valueOf();
        return value_of_event_day >= value_of_today;
    }
});