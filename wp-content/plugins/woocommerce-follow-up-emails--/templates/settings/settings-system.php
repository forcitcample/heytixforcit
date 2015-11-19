<style type="text/css">
    .red-pill {
        font-size: 10px;
        font-family: Verdana, Tahoma, Arial;
        font-weight: bold;
        display: inline-block;
        margin-left: 5px;
        background: #f00;
        color: #fff;
        padding: 0px 8px;
        border-radius: 20px;
        vertical-align: super;
    }
</style>
<form action="admin-post.php" method="post" enctype="multipart/form-data">

    <h3><?php _e('Backup &amp; Restore', 'follow_up_emails'); ?></h3>

    <p>
        <?php _e('Backup your emails using the WordPress import and export functionality.', 'follow_up_emails' ); ?>
        <br />
        <a href="<?php echo admin_url('import.php?import=wordpress'); ?>"><?php _e('Import', 'follow_up_email'); ?></a> |
        <a href="<?php echo admin_url('export.php'); ?>"><?php _e('Export', 'follow_up_emails'); ?></a>
    </p>

    <table class="form-table">
        <tbody>
        <tr valign="top">
            <td colspan="2">
                <a class="button" href="<?php echo wp_nonce_url('admin-post.php?action=fue_backup_settings', 'fue_backup'); ?>"><?php _e('Download a Backup of the Settings', 'follow_up_emails'); ?></a>
            </td>
        </tr>
        <tr valign="top">
            <td colspan="2">
                <strong><?php _e('Restore Backup', 'follow_up_emails'); ?></strong>
                <table class="form-table">
                    <tbody>
                    <tr valign="top">
                        <td width="200"><label for="emails_file"><?php _e('Emails CSV from pre-4.0 installs only', 'follow_up_emails'); ?></label></td>
                        <td><input type="file" name="emails_file" id="emails_file" /></td>
                    </tr>
                    <tr valign="top">
                        <td><label for="settings_file"><?php _e('Settings CSV from all versions', 'follow_up_emails'); ?></label></td>
                        <td><input type="file" name="settings_file" id="settings_file" /></td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        </tbody>
    </table>

    <h3><?php _e('Follow-Up API [beta]', 'follow_up_emails'); ?></h3>

    <p>
        <input type="checkbox" name="api_enabled" id="api_enabled" value="yes" <?php checked( 'yes', $api_enabled ); ?> />
        <label for="api_enabled"><?php _e('Enable the Follow-up REST API <a href="admin.php?page=followup-emails-settings&tab=documentation">Learn More</a>', 'follow_up_emails'); ?></label>
    </p>

    <h3><?php _e('Manual Emails Sending Schedule', 'follow_up_emails'); ?></h3>
    <p><strong><?php _e('Sending manual emails at to large numbers of recipients could cause mail server issues with your host. For example, Gmail limits you to 500 sends per day to limit spam. <a href="http://www.75nineteen.com/how-many-emails-can-i-send-at-once-with-follow-up-emails/">Read here for more</a>.', 'follow_up_emails'); ?></strong></p>

    <p>
        <input type="checkbox" name="email_batch_enabled" value="1" <?php checked( 1, $email_batches ); ?> />
        <?php
        printf(
            __('Send manual emails in batches of %s emails every %s minutes'),
            '<input type="text" name="emails_per_batch" value="'. $emails_per_batch .'" size="3" />',
            '<input type="text" name="email_batch_interval" value="'. $email_batch_interval .'" size="2" />'
        );
        ?>
    </p>

    <h3><?php _e('Action Scheduler Logging', 'follow_up_emails'); ?></h3>
    <p><strong><?php _e('The Action Scheduler, by default, logs completed actions to the comments for debugging purposes. Some users have inquired, but this is not a bug. You can turn off, and/or delete the actions log with the settings below. <a href="admin.php?page=followup-emails-settings&tab=documentation">Learn More</a>', 'follow_up_emails'); ?></strong></p>
    <p>
        <input type="checkbox" name="action_scheduler_disable_logging" id="action_scheduler_disable_logging" value="1" <?php checked( 1, $disable_logging ); ?> />
        <label for="action_scheduler_disable_logging"><?php _e( 'Disable email logging', 'follow_up_emails' ) ?></label>
    </p>

    <p>
        <input type="checkbox" name="action_scheduler_delete_logs" id="action_scheduler_delete_logs" value="1" />
        <label for="action_scheduler_delete_logs"><?php _e( 'Delete existing logs', 'follow_up_emails' ) ?></label>
    </p>

    <h3><?php _e('Remove Old Daily Summary Data', 'follow_up_emails'); ?></h3>

    <p>
        <input class="button clean_daily_summary" type="button" value="<?php _e('Delete Old Summary Data', 'follow_up_emails'); ?>" />

        <span id="clean_daily_summary_status" style="display: none;">
            <img id="clean_daily_summary_loader" src="<?php echo FUE_TEMPLATES_URL .'/images/ajax-loader.gif'; ?>" />
            <span id="clean_daily_summary_message"><?php _e('Please wait...', 'follow_up_emails'); ?></span>
        </span>
    </p>
    <?php
    $js = '
    var fue_summary_posts = 0;
    var fue_summary_deleted = 0;
    $(".clean_daily_summary").click(function() {
        $(this).attr("disabled", true);
        $("#clean_daily_summary_status").show();

        fue_init_delete_daily_summary();
    });

    function fue_init_delete_daily_summary() {
        $.post(ajaxurl, {action: "fue_count_daily_summary_posts"}, function(resp) {
            resp = $.parseJSON(resp);

            fue_summary_posts = resp.count;
            fue_delete_daily_summary();
        });
    }

    function fue_delete_daily_summary() {
        $.post(ajaxurl, {action: "fue_delete_daily_summary"}, function(resp) {
            resp = $.parseJSON(resp);

            if ( resp.count && resp.count > 0 ) {
                var remaining = resp.count;
                fue_summary_deleted = fue_summary_posts - remaining;
                percent = Math.round( (fue_summary_deleted / fue_summary_posts) * 100 );
                $("#clean_daily_summary_message").html("Please wait... ("+ percent +"%)");

                fue_delete_daily_summary();
            } else {
                // done
                $("#clean_daily_summary_message").html("Completed!");
                $("#clean_daily_summary_loader").hide();
            }
        });
    }
    ';

    if ( function_exists('wc_enqueue_js') ) {
        wc_enqueue_js( $js );
    } else {
        WC()->add_inline_js( $js );
    }
    ?>

	<!-- Future location of reporting data improvement settings -->

    <?php do_action( 'fue_settings_system' ); ?>

    <p class="submit">
        <input type="hidden" name="action" value="fue_followup_save_settings" />
        <input type="hidden" name="section" value="<?php echo $tab; ?>" />
        <input type="submit" name="save" value="<?php _e('Save Settings', 'follow_up_emails'); ?>" class="button-primary" />
    </p>

</form>