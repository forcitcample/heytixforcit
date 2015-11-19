CheckinApp.Views.Login = Backbone.View.extend({
    template: _.template($('#login').html()),
    el: '.main',

    events: {
        "click .sign_in_btn": "process",
        "keyup": "processKey"
    },

    initialize: function(data) {

    },

    render: function() {
        $(this.el).html(this.template);

        return this;
    },

    process: function(e) {
        e.preventDefault(); // Don't let this button submit the form
        var formValues = {
            user: $('#user').val(),
            pwd: $('#pwd').val()
        };

        $.ajax({
            url: CheckinApp.base_url + "/api/login/",
            type:'POST',
            dataType:"json",
            data: formValues,
            success:function (data) {
                if(data.authenticated == true) {
                    if ( ! data.user.role ) {
                        $('#invalid_role').show();
                        return false;
                    }
                    data.user.role = data.user.role.toLowerCase();
                    CheckinApp.getSession().setUser(new CheckinApp.Models.User(data.user));
                    CheckinApp.getSession().setAuthenticated(true);
                    CheckinApp.getSession().setAuthorizationToken(data.authorization_token);
                    $.cookie('CheckinApp', JSON.stringify(CheckinApp.getSession()), { expires: 7 });
                    if (data.user.role === 'venue manager') {
                        Backbone.history.navigate("#", true);
                    }
                    if (data.user.role === 'venue checkin') {
                        Backbone.history.navigate("eventlist", true);
                    }
                } else {
                    $('#invalid_credentials').show();
                }
            }
        });
    },

    processKey: function(e) {
        if(e.which === 13) {
            $(this.el).find('.sign_in_btn').click();
        }
    }
});