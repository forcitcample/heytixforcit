CheckinApp.Models.Event = Backbone.Model.extend({
    defaults:{
        "id": null,
        "venue_name": null,
        "venue_logo": null,
        "default_thumbnail": "images/img_chk.png",
        "name": null
    },

    getThumbnail: function() {
        return (this.get('venue_logo') == null || this.get('venue_logo') == '') ? this.get('default_thumbnail') : this.get('venue_logo');
    }
});