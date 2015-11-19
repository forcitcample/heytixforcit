jQuery(document).ready(function($) {

    if ( typeof FUE == 'undefined' ) {
        return;
    }

    $('#queue-filter select#dropdown_customers').css('width', '250px').ajaxChosen({
        method: 'GET',
        url: ajaxurl,
        dataType: 'json',
        afterTypeDelay: 350,
        minTermLength: 1,
        data: {
            action: 'woocommerce_json_search_customers',
            security: FUE.search_customers_nonce
        }
    }, function (data) {

        var terms = {};

        $.each(data, function (i, val) {
            terms[i] = val;
        });

        return terms;
    });

    $('#queue-filter select#dropdown_products_and_variations').ajaxChosen({
        method: 'GET',
        url: ajaxurl,
        dataType: 'json',
        afterTypeDelay: 350,
        data: {
            action: 'woocommerce_json_search_products_and_variations',
            security: FUE.nonce
        }
    }, function (data) {

        var terms = {};

        $.each(data, function (i, val) {
            terms[i] = val;
        });

        return terms;
    });
} );