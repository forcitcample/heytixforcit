/**
 * WooCommerce Dynamic Pricing & Discounts Plugin Admin JavaScript
 */
jQuery(document).ready(function() {

/**************************************************************************
 *********************  INITIAL PAGE SETUP  *******************************
 **************************************************************************/

    /**
     * Determine which settings page this is
     */
    var rp_wcdpd_tab = null;
    jQuery.each({'pricing': 'tab=pricing', 'discounts': 'tab=discounts', 'settings': 'tab=settings', 'localization': 'tab=localization'}, function(index, value) {
        if (document.URL.indexOf(value) !== -1) {
            rp_wcdpd_tab = index;
        }
    });

    if (rp_wcdpd_tab === null) {
        if (document.URL.indexOf('page=wc_pricing_and_discounts') !== -1 && document.URL.indexOf('tab=') === -1) {
            rp_wcdpd_tab = 'pricing';
        }
    }

    /**
     * Set up accordion (rule sets)
     */
    var show_first_accordion_element = (jQuery('#rp_wcdpd_set_list').children().length < 2) ? 0 : false;

    jQuery('#rp_wcdpd_set_list').accordion({
        header: '> div > h4',
        heightStyle: 'content',
        collapsible: true,
        active: show_first_accordion_element
    }).sortable({
        handle: 'h4',
        stop: function(event, ui) {
            rp_wcdpd_regenerate_accordion_handle_titles(rp_wcdpd_tab);
        }
    });

    /**
     * Date picker
     */
    jQuery('.rp_wcdpd_date_field').datepicker({
        dateFormat : 'yy-mm-dd'
    });

    /**
     * Initial conditional field setup (hide fields that we don't need)
     */
    rp_wcdpd_handle_conditional_fields('#rp_wcdpd_set_list');

    /**
     * Handle conditional sections (show/hide)
     */
    jQuery('.rp_wcdpd_method_field').each(function() {
        rp_wcdpd_hide_show_sections(jQuery(this));
    });
    jQuery('.rp_wcdpd_method_field').change(function() {
        rp_wcdpd_hide_show_sections(jQuery(this));
    });

    /**
     * Dynamicaly change set title on the accordion handle
     */
    jQuery('.rp_wcdpd_description_field').each(function() {
        var replacement = jQuery(this).val() !== '' ? '- ' + jQuery(this).val() : '';
        jQuery(this).parent().parent().parent().parent().parent().parent().find('.rp_wcdpd_sets_title_name').html(replacement);
        jQuery(this).on('keyup change', function() {
            var replacement = jQuery(this).val() !== '' ? '- ' + jQuery(this).val() : '';
            jQuery(this).parent().parent().parent().parent().parent().parent().find('.rp_wcdpd_sets_title_name').html(replacement);
        });
    });


/**************************************************************************
 *********************  ACCORDION CONTROL  ********************************
 **************************************************************************/

    /**
     * Regenerate accordion handle titles (change numbers after reorder)
     */
    function rp_wcdpd_regenerate_accordion_handle_titles(context)
    {
        var fake_id = 1;

        jQuery('#rp_wcdpd_set_list').children().each(function() {
            jQuery(this).find('.rp_wcdpd_sets_title').html(rp_wcdpd_vars['labels'][context+'_rule'] + '' + fake_id);
            fake_id++;
        });
    }


/**************************************************************************
 *******************  CONDITIONAL ELEMENT CONTROL  ************************
 **************************************************************************/

    /**
     * Handle conditional fields (show/hide)
     */
    function rp_wcdpd_handle_conditional_fields(context) {
        jQuery.each(rp_wcdpd_vars['conditional_fields'], function(cond_index, cond_value) {
            if (jQuery(context).find(cond_index).length > 0) {
                jQuery(context).find(cond_index).each(function() {
                    rp_wcdpd_hide_show_fields(jQuery(this), cond_value[jQuery(this).val()].hide, cond_value[jQuery(this).val()].show);
                    jQuery(this).change(function() {
                        rp_wcdpd_hide_show_fields(jQuery(this), cond_value[jQuery(this).val()].hide, cond_value[jQuery(this).val()].show);
                    });
                });
            }
            /*if (jQuery(context).find(cond_index).length > 0) {
                rp_wcdpd_hide_show_fields(jQuery(context).find(cond_index).first(), cond_value[jQuery(context).find(cond_index).first().val()].hide, cond_value[jQuery(context).find(cond_index).first().val()].show);
                jQuery(context).find(cond_index).first().change(function() {
                    rp_wcdpd_hide_show_fields(jQuery(this), cond_value[jQuery(this).val()].hide, cond_value[jQuery(this).val()].show);
                });
            }*/
        });
    }

    /**
     * Hide or show conditional fields
     */
    function rp_wcdpd_hide_show_fields(field, hide, show) {
        jQuery.each(hide, function(index, value) {
            if (rp_wcdpd_tab === 'pricing') {
                var field_to_change = field.parent().parent().parent().find(value);
            }
            else {
                var field_to_change = field.parent().parent().find(value);
            }

            field_to_change.each(function() {
                if (jQuery(this).is('select')) {
                    jQuery(this).find('option:selected').prop('selected', false);
                }
                else {
                    jQuery(this).val('');
                }
            });

            if (rp_wcdpd_tab === 'pricing') {
                if (field_to_change.is('select[multiple]') && jQuery('#' + field_to_change.prop('id') + '_chosen').length > 0) {
                    field_to_change.chosen('destroy');
                }
                field_to_change.parent().parent().hide();
            }
            else {
                if (field_to_change.is('select[multiple]')) {
                    if (jQuery('#' + field_to_change.prop('id') + '_chosen').length > 0) {
                        field_to_change.chosen('destroy');
                        field_to_change.hide();
                    }
                    else {
                        field_to_change.hide();
                    }
                }
                else {
                    field_to_change.hide();
                }
            }
        });

        jQuery.each(show, function(index, value) {
            if (rp_wcdpd_tab === 'pricing') {
                var field_to_change = field.parent().parent().parent().find(value);
            }
            else {
                var field_to_change = field.parent().parent().find(value);
            }

            if (rp_wcdpd_tab === 'pricing') {
                if (field_to_change.is('select[multiple]') && jQuery('#' + field_to_change.prop('id') + '_chosen').length === 0) {
                    field_to_change.chosen({
                        width: (rp_wcdpd_tab === 'pricing' ? '400px' : '295px')
                    });
                }
                field_to_change.parent().parent().show();
            }
            else {
                if (field_to_change.is('select[multiple]')) {
                    if (jQuery('#' + field_to_change.prop('id') + '_chosen').length === 0) {
                        field_to_change.show();
                        field_to_change.chosen({
                            width: (rp_wcdpd_tab === 'pricing' ? '400px' : '295px')
                        });
                    }
                }
                else {
                    field_to_change.show();
                }
            }
        });

    }

    /**
     * Hide or show conditional sections
     */
    function rp_wcdpd_hide_show_sections(field) {
        if (field.val() === 'quantity') {
            field.parent().parent().parent().parent().parent().find('.rp_wcdpd_sets_special').each(function() {
                rp_wcdpd_reset_all_fields(jQuery(this));
                jQuery('.rp_wcdpd_special_products_to_adjust_field').trigger('change');
                jQuery(this).hide();
            });
            field.parent().parent().parent().parent().parent().find('.rp_wcdpd_sets_quantity').show();
            field.parent().parent().parent().find('.rp_wcdpd_quantities_based_on_field').parent().parent().show();
            field.parent().parent().parent().find('.rp_wcdpd_if_matched_field').parent().parent().show();
        }
        else if (field.val() === 'special') {
            field.parent().parent().parent().parent().parent().find('.rp_wcdpd_sets_quantity').each(function() {
                rp_wcdpd_reset_all_fields(jQuery(this));
                jQuery('.rp_wcdpd_quantity_products_to_adjust_field').trigger('change');
                rp_wcdpd_reset_table(jQuery(this).parent().parent());
                jQuery(this).hide();
            });
            field.parent().parent().parent().parent().parent().find('.rp_wcdpd_sets_special').show();
            field.parent().parent().parent().find('.rp_wcdpd_quantities_based_on_field').parent().parent().show();
            field.parent().parent().parent().find('.rp_wcdpd_if_matched_field').parent().parent().show();
        }
        else if (field.val() === 'exclude') {
            field.parent().parent().parent().parent().parent().find('.rp_wcdpd_sets_quantity').each(function() {
                rp_wcdpd_reset_all_fields(jQuery(this));
                jQuery('.rp_wcdpd_quantity_products_to_adjust_field').trigger('change');
                rp_wcdpd_reset_table(jQuery(this).parent().parent());
                jQuery(this).hide();
            });
            field.parent().parent().parent().parent().parent().find('.rp_wcdpd_sets_special').each(function() {
                rp_wcdpd_reset_all_fields(jQuery(this));
                jQuery('.rp_wcdpd_special_products_to_adjust_field').trigger('change');
                jQuery(this).hide();
            });
            field.parent().parent().parent().find('.rp_wcdpd_quantities_based_on_field').parent().parent().hide();
            field.parent().parent().parent().find('.rp_wcdpd_if_matched_field').parent().parent().hide();
        }
    }


/**************************************************************************
 *************************  RESETS  ***************************************
 **************************************************************************/

    /**
     * Reset fields of all types within parent
     */
    function rp_wcdpd_reset_all_fields(parent) {
        parent.find('select').find('option').prop('selected', false);
        parent.find('select').not('[multiple]').prop('selectedIndex', 0);
        parent.find(':checkbox').prop('checked', false);
        parent.find('input').val('');
    }

    /**
     * Reset set
     */
    function rp_wcdpd_reset_set(set) {
        rp_wcdpd_reset_all_fields(set);
        set.find('.rp_wcdpd_sets_title_name').html('');

        // Show fields in case Exclude was chosen under Method
        set.find('.rp_wcdpd_quantities_based_on_field').parent().parent().show();
        set.find('.rp_wcdpd_if_matched_field').parent().parent().show();

        if (rp_wcdpd_tab === 'pricing') {
            rp_wcdpd_handle_conditional_fields('#' + set.prop('id'));
            rp_wcdpd_reset_table(set);
            set.find('.rp_wcdpd_sets_quantity').show();
            set.find('.rp_wcdpd_sets_special').hide();
        }
        else {
            rp_wcdpd_reset_table(set);
        }
    }

    /**
     * Reset pricing or discount conditions table
     */
    function rp_wcdpd_reset_table(set) {
        set.find('.rp_wcdpd_items_table tbody').children().not(':first').remove();

        if (rp_wcdpd_tab === 'discounts') {
            set.find('.rp_wcdpd_items_table tbody').children().each(function() {
                rp_wcdpd_reset_table_row(jQuery(this));
            });
        }

        // Get ID of the set and last field id
        var id_array = rp_wcdpd_parse_id(set.find('.rp_wcdpd_items_table tbody tr:last').prop('id'));
        var current_set_id = id_array[0];
        var current_row_id = id_array[1];

        rp_wcdpd_replace_field_ids_and_names(jQuery('#rp_wcdpd_set_list').children().last(), current_set_id, current_set_id, current_row_id, 1);
    }

    /**
     * Reset single discounts conditions row
     */
    function rp_wcdpd_reset_table_row(row) {
        row.find('input').show();

        if (rp_wcdpd_tab === 'discounts') {
            row.find('select[multiple]').each(function() {
                if (jQuery('#' + jQuery(this).prop('id') + '_chosen').length > 0) {
                    jQuery(this).chosen('destroy');
                    jQuery(this).hide();
                }
                else {
                    jQuery(this).hide();
                }
            });
        }

        rp_wcdpd_reset_all_fields(row);
    }

    /**
     * Fix field ids and names when inserting new set or row
     */
    function rp_wcdpd_replace_field_ids_and_names(parent, current_set_id, new_set_id, current_row_id, new_row_id) {

        // Parent element itself
        rp_wcdpd_replace_field_id_and_name(parent, current_set_id, new_set_id, current_row_id, new_row_id);

        // Various other elements
        parent.find('input, select, .rp_wcdpd_sets_title, .rp_wcdpd_sets_remove, .rp_wcdpd_items_table, .rp_wcdpd_items_row').each(function() {
            rp_wcdpd_replace_field_id_and_name(jQuery(this), current_set_id, new_set_id, current_row_id, new_row_id);
        });

    }

    /**
     * Fix field id and name on a single field
     */
    function rp_wcdpd_replace_field_id_and_name(field, current_set_id, new_set_id, current_row_id, new_row_id) {

        var element_has_name = (typeof field.prop('name') !== 'undefined' && field.prop('name') !== false) ? true : false;

        // If we have both set and row ids
        if (field.prop('id').indexOf('_' + current_set_id + '_') !== -1) {

            // New field ID
            var new_field_id = field.prop('id').replace(new RegExp('_' + current_set_id + '_'), '_' + new_set_id + '_');
            new_field_id = new_field_id.replace(new RegExp('_' + current_row_id + '$'), '_' + new_row_id);

            // New field name (warning: do not use "global" regex match here as set id can match row id
            if (element_has_name) {
                var new_field_name = field.prop('name').replace(new RegExp('\\[' + current_set_id + '\\]'), '%%%%%%');
                new_field_name = new_field_name.replace(new RegExp('\\[' + current_row_id + '\\]'), '[' + new_row_id + ']');
                new_field_name = new_field_name.replace(new RegExp('%%%%%%'), '[' + new_set_id + ']');
            }
        }

        // If we only have set id
        else {

            // New field ID
            var new_field_id = field.prop('id').replace(new RegExp('_' + current_set_id + '$'), '_' + new_set_id);

            // New field name
            if (element_has_name) {
                var new_field_name = field.prop('name').replace(new RegExp('\\[' + current_set_id + '\\]'), '[' + new_set_id + ']');
            }
        }

        // Replace id
        field.prop('id', new_field_id);

        // Maybe replace name
        if (element_has_name) {
            field.prop('name', new_field_name);
        }
    }


/**************************************************************************
 *************************  SET CONTROL  **********************************
 **************************************************************************/

    /**
     * Add new pricing/discount set
     */
    jQuery('#rp_wcdpd_add_set').click(function() {

        // Select last set
        var last_set = jQuery(this).parent().parent().find('#rp_wcdpd_set_list').children().last();

        // Remove all chosen fields
        var chosen_removed_from = [];

        last_set.find('select[multiple]').each(function() {
            if (jQuery('#' + jQuery(this).prop('id') + '_chosen').length > 0) {
                jQuery(this).chosen('destroy');
                chosen_removed_from.push(jQuery(this).prop('id'));
            }
        });

        // Clone and insert after last rule
        var new_set = last_set.clone(true);
        last_set.after(new_set);

        new_set = jQuery(this).parent().parent().find('#rp_wcdpd_set_list').children().last();

        // Remove datepicker
        new_set.find('.rp_wcdpd_date_field').datepicker('destroy');

        // Get current set id
        var current_set_id = parseInt(jQuery('#rp_wcdpd_set_list').children().last().prop('id').replace('rp_wcdpd_set_', ''));

        // Get next set id
        var next_set_id = 1;

        jQuery('#rp_wcdpd_set_list').children().each(function() {
            var current_id_found = parseInt(jQuery(this).prop('id').replace('rp_wcdpd_set_', ''));
            next_set_id = (current_id_found > next_set_id) ? current_id_found : next_set_id;
        });

        next_set_id++;

        // Fix element ids and names
        rp_wcdpd_replace_field_ids_and_names(new_set, current_set_id, next_set_id, 1, 1);

        // Reset set
        rp_wcdpd_reset_set(jQuery('#rp_wcdpd_set_list').children().last());

        // Add datepicker
        new_set.find('.rp_wcdpd_date_field').datepicker({
            dateFormat : 'yy-mm-dd'
        });

        // Make multiselect fields on previous set chosen
        jQuery.each(chosen_removed_from, function(index, value) {
            jQuery('#' + value).chosen({
                width: (rp_wcdpd_tab === 'pricing' ? '400px' : '295px')
            });
        });

        // Update accordion
        jQuery('#rp_wcdpd_set_list').accordion('refresh');
        var $accordion = jQuery("#rp_wcdpd_set_list").accordion();
        var last_accordion_element = $accordion.find('h4').length;
        $accordion.accordion('option', 'active', (last_accordion_element - 1));
        rp_wcdpd_regenerate_accordion_handle_titles(rp_wcdpd_tab);

    });

    /**
     * Remove pricing/discount set or reset the last one
     */
    jQuery('.rp_wcdpd_sets_remove').click(function() {
        if (jQuery(this).parent().parent().parent().children().length > 1) {
            jQuery(this).parent().parent().remove();
            rp_wcdpd_regenerate_accordion_handle_titles(rp_wcdpd_tab);
        }
        else {
            rp_wcdpd_reset_set(jQuery(this).parent().parent());
        }
    });


/**************************************************************************
 *************  PRICING AND CONDITIONS TABLE CONTROL  *********************
 **************************************************************************/

    /**
     * Add new pricing/conditions table row
     */
    jQuery('.rp_wcdpd_add_items_row').click(function() {
        var current_table = jQuery(this).parent().parent().parent().parent();
        var last_row = current_table.find('tbody>tr:last');

        // Get ID of the set and last field id
        var id_array = rp_wcdpd_parse_id(last_row.prop('id'));
        var current_set_id = id_array[0];
        var current_row_id = id_array[1];

        // Destroy any chosen fields
        last_row.find('select[multiple]').each(function() {
            if (jQuery('#' + jQuery(this).prop('id') + '_chosen').length > 0) {
                jQuery(this).chosen('destroy');
            }
        });

        // Clone row and insert after the last one
        last_row.after(last_row.clone(true));

        current_table.find('tbody>tr:last').each(function() {

            // Reset selected fields
            rp_wcdpd_reset_table_row(jQuery(this));

            // Replace ids and names
            rp_wcdpd_replace_field_ids_and_names(jQuery(this), current_set_id, current_set_id, current_row_id, (parseInt(current_row_id) + 1));

        });

        // Create chosen fields that were destroyed
        last_row.find('select[multiple]:visible').each(function() {
            jQuery(this).chosen({
                width: (rp_wcdpd_tab === 'pricing' ? '400px' : '295px')
            });
        });
    });

    /**
     * Delete pricing/conditions table row
     */
    jQuery('.rp_wcdpd_remove_field').click(function() {
        if (jQuery(this).parent().parent().parent().children().length > 1) {
            jQuery(this).parent().parent().remove();
        }
        else {
            rp_wcdpd_reset_table_row(jQuery(this).parent().parent());
        }
    });


/**************************************************************************
 **************************  VARIOUS  *************************************
 **************************************************************************/

    /**
     * Parse set and table row id from row id
     */
    function rp_wcdpd_parse_id(full_id) {
        return full_id.replace('rp_wcdpd_items_row_', '').split('_');
    }

    /**
     * Set up forms page hints
     */
    if (typeof rp_wcdpd_vars['hints'] !== 'undefined' && typeof rp_wcdpd_vars['hints'][rp_wcdpd_tab] !== 'undefined') {
        jQuery.each(rp_wcdpd_vars['hints'][rp_wcdpd_tab], function(index, value) {
            jQuery('form').find('.' + index).each(function() {
                if (index == 'rp_wcdpd_apply_multiple_field') {
                    jQuery(this).parent().prepend('<div class="rp_wcdpd_tip rp_wcdpd_tip_right" title="' + value + '"><i class="fa fa-question"></div>');
                }
                else if (index == 'rp_wcdpd_sets_quantity' || index == 'rp_wcdpd_sets_special') {
                    jQuery(this).find('.rp_wcdpd_sets_section').first().append('<span class="rp_wcdpd_tip rp_wcdpd_tip_section" title="' + value + '"><i class="fa fa-question"></span>');
                }
                else if (index == 'rp_wcdpd_sets_section_discounts_conditions') {
                    jQuery(this).append('<span class="rp_wcdpd_tip rp_wcdpd_tip_section" title="' + value + '"><i class="fa fa-question"></span>');
                }
                else {
                    jQuery(this).parent().parent().find('th').each(function() {
                        if (jQuery(this).find('.rp_wcdpd_tip').length === 0) {
                            jQuery(this).append('<div class="rp_wcdpd_tip" title="' + value + '"><i class="fa fa-question"></div>');
                        }
                    });
                }
            });
        });
    }
    jQuery.widget('ui.tooltip', jQuery.ui.tooltip, {
        options: {
            content: function() {
                return jQuery(this).prop('title');
            }
        }
    });
    jQuery('.rp_wcdpd_tip, .rp_wcdpd_tip_left').tooltip();

    /*function chimpy_forms_page_hints()
    {
        if (typeof chimpy_forms_hints !== 'undefined') {
            jQuery.each(chimpy_forms_hints, function(index, value) {
                jQuery('form').find('.' + index).each(function() {
                    jQuery(this).parent().parent().find('th').each(function() {
                        if (jQuery(this).find('.chimpy_tip').length === 0) {
                            jQuery(this).append('<div class="chimpy_tip" title="' + value + '"><i class="fa fa-question"></div>');
                        }
                    });
                });
            });
        }
        jQuery.widget('ui.tooltip', jQuery.ui.tooltip, {
            options: {
                content: function() {
                    return jQuery(this).prop('title');
                }
            }
        });
        jQuery('.chimpy_tip').tooltip();
    }*/

});