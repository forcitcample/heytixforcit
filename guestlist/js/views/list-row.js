CheckinApp.Views.ListRow = Backbone.View.extend({
    template: null,
    events: {
        "click": "processClickEvent",
        "click .ticket_row": "processCheckinClickEvent",
        "click .event_row": "processSelectClickEvent",
        "click .fb_thumb": "showModal",
    },

    initialize: function(data) {
        this.model = data.model;
        this.template = _.template($(data.list_template).html());
        this.route = data.route;
        this.collection_name = data.collection_name;
        CheckinApp.getVent().on('connection:changed', this.connectionChanged, this);
        this.listenTo(this.model, 'change', this.render);
        this.listenTo(this.model, 'destroy', this.remove);

        this.model.attributes.thumbnail = this.model.getThumbnail();
    },

    showModal: function (e) {
        var btn_text = $(e.currentTarget).parent().parent().parent().find('a:last span').text().toLowerCase();

        if(btn_text == 'select') return this.processClickEvent(e);

        e.stopImmediatePropagation();

        var template = _.template('\
    <div class="modal-dialog" ><div class="modal-content">\
    <% if (title) { %>\
      <div class="modal-header" >\
        <% if (allowCancel) { %>\
          <a class="close">&times;</a>\
        <% } %>\
        <h4>{{title}}</h4>\
      </div>\
    <% } %>\
    <div class="modal-body">{{content}}</div>\
    <% if (showFooter) { %>\
      <div class="modal-footer">\
        <% if (allowCancel) { %>\
          <% if (cancelText) { %>\
            <a href="#" class="btn cancel">{{cancelText}}</a>\
          <% } %>\
        <% } %>\
        <a href="#" class="btn ok btn-primary">{{okText}}</a>\
      </div>\
    <% } %>\
    </div></div>\
  ');
        var view = new CheckinApp.Views.ModalWindow({model: this.model});
        var modal = new Backbone.BootstrapModal({
            template: template,
            showFooter: false,
            content: view,
            animate: true,
            customSettings: true
        });
        modal.open();
    },

    render: function() {
        (this.model instanceof CheckinApp.Models.Event) ? this.renderEventRow() : this.renderTicketRow();
        this.delegateEvents();
        return this;
    },

    renderEventRow: function() {
        this.$el.html(this.template(this.model.attributes));
        if (this.route === 'sales') {
            this.$el.find('.right_select').hide();
            this.$el.find('.up_select').hide();
            this.$el.find('.listrow_select').hide();
        } else {
            this.$el.find('.up_select').hide();
            this.$el.find('.down_select').hide();
        }
    },

    renderTicketRow: function() {
        if (this.model.get('checked_in') == 1) {
            $(this.el).addClass('chk_undo_list');
        } else {
            $(this.el).removeClass('chk_undo_list');
        }
        this.model.attributes.ticket_type = this.model.get('ticket_type').toUpperCase();
        this.$el.html(this.template(this.model.attributes));
        if (this.collection_name === 'ticketlist') {
            this.$el.find('.ticket_name').show();
            this.$el.find('.guestlist_name').hide();
        } else {
            this.$el.find('.ticket_name').hide();
            this.$el.find('.guestlist_name').show();
        }
    },

    /**
     * The designer used <a href> links for the clickable buttons, since we made the entire row
     * clickable we need to intercept the clicks on the <a href> links to prevent the browser
     * from completing the default navigation request via the click.
     */
    processLinkClickEvent: function(e) {
        e.preventDefault();
    },

    connectionChanged: function(data) {
        switch(data.status) {
            case 'up':
                this.model.set('thumbnail', this.model.getThumbnail());
                break;
            case 'down':
                this.model.set('thumbnail', this.model.get('default_thumbnail'));
                break;
        }
        this.render();
    },

    changeCounts: function(checked_in) {
        var div = $('.events_count').find('#count_checked');
        var result = parseInt(div.text(), 10);
        (checked_in == 0) ? result++ : result--;
        div.text(result);
    },

    seperateThousands: function (el) {
        var interval_value = el / 1000;
        if (interval_value > 1) {
            var int_part = parseInt(interval_value);
            var fract_part = Math.round((el - parseInt(interval_value) * 1000) * 100) / 100;
            fract_part = this.leaveTwoSignsAfterComma(fract_part);
            while (fract_part.toString().length < 3) {
                fract_part = '0' + fract_part;
            }
            return int_part + ',' + fract_part;
        } else {
            return el;
        }
    },

    leaveTwoSignsAfterComma: function (el) {
        el = Math.round(el * 100) / 100;
        if (el.toString().indexOf('.') == -1) {
            return el + '.00';
        }
        var arr = el.toString().split('.');
        if (arr[1].length == 1) {
            return el + '0';
        }
        return el;
    },

    toggleTicketSales: function (id) {
        var self = this;
        if ($('#' + id + '.sales_info').css('display') === 'none') {
            $('#' + id + '.ticket_sales_data').empty();
            var report_data = new CheckinApp.Collections.EventReport([], {"event_id": id});
            report_data.fetch().success(function () {
                var totals = report_data.models[0].get('totals');
                var total_sold = null;
                var total_revenue = null;
                for (var ticket in totals) {
                    totals[ticket].revenue = totals[ticket].cost * totals[ticket].sold;

                    total_sold += totals[ticket].sold;
                    total_revenue += totals[ticket].revenue;

                    totals[ticket].cost = self.leaveTwoSignsAfterComma(totals[ticket].cost);
                    totals[ticket].revenue = self.leaveTwoSignsAfterComma(totals[ticket].revenue);

                    for (var property in totals[ticket]) {
                        totals[ticket][property] = self.seperateThousands(totals[ticket][property]);
                    }

                    totals[ticket].revenue = self.seperateThousands(totals[ticket].revenue);
                }

                total_sold = self.seperateThousands(total_sold);
                total_revenue = self.leaveTwoSignsAfterComma(total_revenue);
                total_revenue = self.seperateThousands(total_revenue);

                $('#' + id + '.ticket_sales_data').html(_.template($('#tickets_sale').html())({data: totals, total_sold: total_sold, total_revenue: total_revenue}));
                $('#' + id + '.sales_info').show();
                self.$el.find('.down_select').hide();
                self.$el.find('.up_select').show();
            });
        }
        else {
            $('#' + id + '.sales_info').hide();
            this.$el.find('.down_select').show();
            this.$el.find('.up_select').hide();
        }
    },

    processCheckinClickEvent: function (e) {
        e.preventDefault();
        var self = this;
        var checked_in = this.model.get('checked_in');
        this.model.save({
            "checked_in": (checked_in == 0) ? "1" : "0"
        }).success(function () {
            self.changeCounts(checked_in);
        });
    },

    processSelectClickEvent: function (e) {
        e.preventDefault();
        var event_id = $(e.currentTarget).find('a:last').data('event');
        if (this.route === 'sales') {
            return this.toggleTicketSales(event_id);
        }

        // show ticket list
        CheckinApp.getSession().getUser().setCurrentVenueName(this.model.get("venue_name"));
        CheckinApp.getSession().getUser().setCurrentVenueLogo(this.model.get("venue_logo"));
        CheckinApp.getSession().getUser().setCurrentEventId(event_id);
        CheckinApp.getSession().getUser().setCurrentEvent($(e.currentTarget).find('.txt_chk_text').text());
        CheckinApp.updateCookie();
        Backbone.history.navigate('guestlist', true);
    }
});
