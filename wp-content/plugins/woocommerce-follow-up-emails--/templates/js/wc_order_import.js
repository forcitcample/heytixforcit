jQuery(document).ready(function($) {
    var progressbar     = $( "#progressbar" ),
        progressLabel   = $( ".progress-label"),
        total_orders    = 0,
        total_processed = 0,
        xhr             = null,
        _test           = false;


    progressbar.progressbar({
        value: false,
        change: function() {
            progressLabel.text( progressbar.progressbar( "value" ) + "%" );
        },
        complete: function() {
            progressLabel.text( "Importing complete!" );
            importing_complete();
        }
    });

    function update_progressbar( value ) {
        progressbar.progressbar( "value", Math.ceil(value) );
    }

    var params = {
        "action": "fue_wc_order_import",
        "cmd": "start",
        "woo_nonce": "",
        "email_id": email_id,
        "test": _test
    };

    $.post(
        ajaxurl,
        params,
        function( resp ) {
            resp = $.parseJSON(resp);

            if (! resp ) {
                alert("There was an error executing the request. Please try again later.");
            } else {
                total_orders = resp.total_orders;
                $("#total-orders-label").html("Total Orders: "+ resp.total_orders);

                if ( total_orders == 0 ) {
                    alert("There are no orders to import");
                    update_progressbar(100);
                    importing_complete();
                } else {
                    // initiate the import and the order_import() will call itself until the import is done
                    update_progressbar(0);
                    order_import( email_id );
                }

            }

        }
    );

    function order_import( email_id ) {
        if ( email_id instanceof Array ) {
            var id = email_id[0];

            var params = {
                "action": "fue_wc_order_import",
                "woo_nonce": "",
                "cmd": "continue",
                "email_id": id,
                "test": _test
            };

            xhr = $.post( ajaxurl, params, function( resp ) {
                resp = $.parseJSON(resp);

                if ( resp.status == 'partial' ) {
                    log_import_data( resp.import_data );

                    // update the progress bar and execute again
                    var num_processed = resp.import_data.length;

                    total_processed = total_processed + num_processed;
                    var progress_value = ( total_processed / total_orders ) * 100;
                    update_progressbar( progress_value );

                    order_import( email_id );
                } else if ( resp.status == 'completed' ) {
                    if ( resp.import_data ) {
                        log_import_data( resp.import_data );
                    }

                    // move on to the next email
                    id = email_id.shift();

                    if ( typeof id == 'undefined' ) {
                        // display the success message
                        update_progressbar( 100 );
                    } else {
                        var msg = '<p class="success"><span class="dashicons dashicons-yes"></span> Finished importing orders for Email #'+ id +'!</p>';

                        $("#log").append(msg);

                        var height = $("#log")[0].scrollHeight;
                        $("#log").scrollTop(height);
                        
                        // run the importer using the next email's ID
                        order_import( email_id );
                    }
                }
            });
        } else {
            var params = {
                "action": "fue_wc_order_import",
                "woo_nonce": "",
                "cmd": "continue",
                "email_id": email_id,
                "test": _test
            };

            xhr = $.post( ajaxurl, params, function( resp ) {
                resp = $.parseJSON(resp);

                if ( resp.status == 'partial' ) {
                    log_import_data( resp.import_data );

                    // update the progress bar and execute again
                    var num_processed = resp.import_data.length;

                    total_processed = total_processed + num_processed;
                    var progress_value = ( total_processed / total_orders ) * 100;
                    update_progressbar( progress_value );

                    order_import( email_id );
                } else if ( resp.status == 'completed' ) {
                    // display the success message
                    update_progressbar( 100 );
                }
            });
        }


    }

    function log_import_data( data ) {
        for ( var x = 0; x < data.length; x++ ) {
            var row;
            var id = data[x].id;

            if ( data[x].status == 'success' ) {
                row = '<p class="success"><span class="dashicons dashicons-yes"></span> Order #'+ id +' imported</p>';
            } else {
                row = '<p class="failure"><span class="dashicons dashicons-no"></span> Order #'+ id +' - ' + data[x].reason +'</p>';
            }

            $("#log").append(row);

            var height = $("#log")[0].scrollHeight;
            $("#log").scrollTop(height);

        }
    }

    function importing_complete() {
        if ( $("#log").find("a.return_link").length == 0 ) {
            $("#log").append('<div class="updated"><p>All done! <a href="#" class="return_link">Go back</a></p></div>');
            var height = $("#log")[0].scrollHeight;
            $("#log").scrollTop(height);
            $(".return_link").attr("href", return_url);
        }
    }
});
