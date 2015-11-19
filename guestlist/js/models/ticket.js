CheckinApp.Models.Ticket = Backbone.Model.extend({
    defaults:{
        "id": null,
        "event_id": null,
        "post_id": null,
        "photo_url": null,
        "default_thumbnail": "images/img_chk.png",
        "name": null,
        "firstname": null,
        "lastname": null,
    },

    getThumbnail: function() {
        return (this.get('photo_url') == null) ? this.get('default_thumbnail') : this.get('photo_url');
    }
});