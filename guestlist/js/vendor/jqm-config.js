$(document).on("mobileinit", function () {
    // Prevents all anchor click handling including the addition of active button state and alternate link bluring.
    $.mobile.linkBindingEnabled = false;
    // Disabling this will prevent jQuery Mobile from handling hash changes
    $.mobile.hashListeningEnabled = false;

    $.mobile.loader.prototype.options.text = "Loading Tickets";
    $.mobile.loader.prototype.options.textVisible = true;
    $.mobile.loader.prototype.options.textonly = true;
    $.mobile.loader.prototype.options.theme = "b";
    $.mobile.loader.prototype.options.html = "";
});