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

    <table class="form-table">
        <tbody>
        <tr valign="top">
            <td colspan="2">
                <strong><?php _e('Download Backup of', 'follow_up_emails'); ?></strong>
                <br/>
                <a class="button" href="<?php echo wp_nonce_url('admin-post.php?action=fue_backup_emails', 'fue_backup'); ?>"><?php _e('Follow-Up Emails', 'follow_up_emails'); ?></a>
                <a class="button" href="<?php echo wp_nonce_url('admin-post.php?action=fue_backup_settings', 'fue_backup'); ?>"><?php _e('Settings', 'follow_up_emails'); ?></a>
            </td>
        </tr>
        <tr valign="top">
            <td colspan="2">
                <strong><?php _e('Restore Backup of', 'follow_up_emails'); ?></strong>
                <table class="form-table">
                    <tbody>
                    <tr valign="top">
                        <th><label for="restore_emails"><?php _e('Follow-up Emails', 'follow_up_emails'); ?></label></th>
                        <td><input type="file" name="emails_file" /></td>
                    </tr>
                    <tr valign="top">
                        <th><label for="restore_settings"><?php _e('Settings', 'follow_up_emails'); ?></label></th>
                        <td><input type="file" name="settings_file" /></td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        </tbody>
    </table>

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
    <p><strong><?php _e('The Action Scheduler, by default, logs completed actions to the comments for debugging purposes. Some users have inquired, but this is not a bug. You can turn off, and/or delete the actions log with the settings below.', 'follow_up_emails'); ?></strong></p>
    <p>
        <input type="checkbox" name="action_scheduler_disable_logging" id="action_scheduler_disable_logging" value="1" <?php checked( 1, $disable_logging ); ?> />
        <label for="action_scheduler_disable_logging"><?php _e( 'Disable email logging', 'follow_up_emails' ) ?></label>
    </p>

    <p>
        <input type="checkbox" name="action_scheduler_delete_logs" id="action_scheduler_delete_logs" value="1" />
        <label for="action_scheduler_delete_logs"><?php _e( 'Delete existing logs', 'follow_up_emails' ) ?></label>
    </p>

	<!-- Future location of reporting data improvement settings -->

    <?php do_action( 'fue_settings_system' ); ?>

    <p class="submit">
        <input type="hidden" name="action" value="sfn_followup_save_settings" />
        <input type="hidden" name="section" value="<?php echo $tab; ?>" />
        <input type="submit" name="save" value="<?php _e('Save Settings', 'follow_up_emails'); ?>" class="button-primary" />
    </p>

</form>