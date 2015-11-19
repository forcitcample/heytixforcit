/*
 * Store a version of Backbone.sync to call from the
 * modified version we create
 */
var _nativeSync = Backbone.sync;

Backbone.sync = function (method, model, options) {
    /*
     * The jQuery `ajax` method includes a 'headers' option
     * which lets you set any headers you like
     */
    if(options.if_modified) {
        var beforeSend = options.beforeSend;
        options.beforeSend = function(xhr) {
            xhr.setRequestHeader('When-Modified-After', options.time);
            if (beforeSend) return beforeSend.apply(this, arguments);
        };
    }


    if(CheckinApp.getSession().isAuthenticated() !== false) {
        /*
         * Set the 'Authorization' header and get the access
         * token from the `auth` module
         */
        options.headers = {
            'Authorization': 'Token ' + CheckinApp.getSession().getAuthorizationToken()
        }

    }

    /*
     * Call the stored original Backbone.sync method with
     * extra headers argument added
     */
    return _nativeSync(method, model, options);
};