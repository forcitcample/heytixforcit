// Check if a new cache is available on page load.
window.addEventListener('load', function(e) {

    window.applicationCache.addEventListener('updateready', function(e) {
        if (window.applicationCache.status == window.applicationCache.UPDATEREADY) {
            // Browser downloaded a new app cache.
            if (confirm('A new version of this site is available. Load it?')) {
                window.location.reload();
            }
        } else {
            // Manifest didn't changed. Nothing new to server.
        }
    }, false);

}, false);

$.ajaxSetup({
    statusCode: {
        401: function () {
            CheckinApp.clearSession();
            Backbone.history.navigate('#login', true);
        }		
    }
});



var app = null;

$(document).ready(function () {
    app = CheckinApp.run();
});