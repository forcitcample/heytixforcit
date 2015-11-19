jQuery( function ( $ ) {
    jQuery("body").bind("fue_email_type_changed", function(evt, type) {
        sensei_toggle_fields( type );
    });

    jQuery("body").bind("updated_email_details", function() {
        sensei_toggle_interval_type_fields( $("#interval_type").val() );
    });

    jQuery("body").on("change", "#interval_type", function() {
        sensei_toggle_interval_type_fields( $(this).val() );
    });
} );

function sensei_toggle_interval_type_fields( type ) {
    var show = [];
    var hide = ['.sensei-courses', '.sensei-lessons', '.sensei-quizzes', '.sensei-answers'];

    switch (type) {
        case 'course_signup':
        case 'course_completed':
            show = ['.sensei-courses'];
            break;

        case 'lesson_start':
        case 'lesson_signup':
        case 'lesson_completed':
            show = ['.sensei-lessons'];
            break;

        case 'quiz_completed':
        case 'quiz_failed':
        case 'quiz_passed':
            show = ['.sensei-quizzes'];
            break;

        case 'specific_answer':
            show = ['.sensei-answers'];
            break;
    }

    for (x = 0; x < hide.length; x++) {
        jQuery(hide[x]).hide();
    }

    for (x = 0; x < show.length; x++) {
        jQuery(show[x]).show();
    }

}

function sensei_toggle_fields( type ) {
    if (type == "sensei") {
        var val  = jQuery("#interval_type").val();
        var show = ['#fue-email-sensei', '.sensei', '.var_sensei'];
        var hide = ['.interval_type_option', '.always_send_tr', '.signup_description', '.product_description_tr', '.product_tr', '.category_tr', '.use_custom_field_tr', '.custom_field_tr', '.var_item_name', '.var_item_category', '.var_item_names', '.var_item_categories', '.var_item_name', '.var_item_category', '.interval_type_after_last_purchase', '.interval_duration_date', '.var_customer', '.var_order'];

        for (x = 0; x < hide.length; x++) {
            jQuery(hide[x]).hide();
        }

        for (x = 0; x < show.length; x++) {
            jQuery(show[x]).show();
        }

        jQuery("option.interval_duration_date").attr("disabled", true);

        jQuery(".interval_duration_date").hide();
    } else {
        var hide = ['#fue-email-sensei', '.course_tr', '.sensei', '.var_sensei'];

        for (x = 0; x < hide.length; x++) {
            jQuery(hide[x]).hide();
        }
    }
}

jQuery(document).ready(function( $ ) {
    sensei_toggle_fields( jQuery("#email_type").val() );

    $(":input.sensei-search").filter(":not(.enhanced)").each( function() {
        var select2_args = {
            allowClear:  true,
            placeholder: jQuery( this ).data( 'placeholder' ),
            dropdownAutoWidth: 'true',
            minimumInputLength: jQuery( this ).data( 'minimum_input_length' ) ? jQuery( this ).data( 'minimum_input_length' ) : '3',
            escapeMarkup: function( m ) {
                return m;
            },
            ajax: {
                url:         ajaxurl,
                dataType:    'json',
                quietMillis: 250,
                data: function( term, page ) {
                    return {
                        term:     term,
                        action:   jQuery( this ).data( 'action' ),
                        security: jQuery( this ).data( 'nonce' )
                    };
                },
                results: function( data, page ) {
                    var terms = [];
                    if ( data ) {
                        jQuery.each( data, function( id, text ) {
                            terms.push( { id: id, text: text } );
                        });
                    }
                    return { results: terms };
                },
                cache: true
            }
        };

        if ( jQuery( this ).data( 'multiple' ) === true ) {
            select2_args.multiple = true;
            select2_args.initSelection = function( element, callback ) {
                var data     = jQuery.parseJSON( element.attr( 'data-selected' ) );
                var selected = [];

                jQuery( element.val().split( "," ) ).each( function( i, val ) {
                    selected.push( { id: val, text: data[ val ] } );
                });
                return callback( selected );
            };
            select2_args.formatSelection = function( data ) {
                return '<div class="selected-option" data-id="' + data.id + '">' + data.text + '</div>';
            };
        } else {
            select2_args.multiple = false;
            select2_args.initSelection = function( element, callback ) {
                var data = {id: element.val(), text: element.attr( 'data-selected' )};
                return callback( data );
            };
        }


        jQuery(this).select2(select2_args).addClass( 'enhanced' );
    } );
});