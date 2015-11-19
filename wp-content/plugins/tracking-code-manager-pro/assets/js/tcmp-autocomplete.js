jQuery(function() {
    var url = TCMAutocomplete.url + "?action=tcmp_search";
    /*
    jQuery("[name=optionPosts]").autocomplete({
        source: url
        , delay: 200
        , minLength: 3
    });
    */

    //var options={multiple:true, multipleSep: ","};
    //jQuery("[name=optionPosts]").suggest(url, options);

    jQuery(".tcmp-hideShow").click(function() {
        tcmp_hideShow(this);
    });
    jQuery(".tcmp-hideShow").each(function() {
        tcmp_hideShow(this);
    });

    //mostra o nasconde un div collegato ad una checkbox
    function tcmp_hideShow(v) {
        var $source=jQuery(v);
        if($source.attr('tcmp-hideIfTrue') && $source.attr('tcmp-hideShow')) {
            var $destination=jQuery('[name='+$source.attr('tcmp-hideShow')+']');
            if($destination.length==0) {
                $destination=jQuery('#'+$source.attr('tcmp-hideShow'));
            }
            if($destination.length>0) {
                var isChecked=$source.is(":checked");
                var hideIfTrue=($source.attr('tcmp-hideIfTrue').toLowerCase()=='true');

                if(isChecked) {
                    if(hideIfTrue) {
                        $destination.hide();
                    } else {
                        $destination.show();
                    }
                } else {
                    if(hideIfTrue) {
                        $destination.show();
                    } else {
                        $destination.hide();
                    }
                }
            }
        }
    }
});