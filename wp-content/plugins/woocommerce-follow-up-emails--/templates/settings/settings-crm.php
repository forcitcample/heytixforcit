<form action="admin-post.php" method="post" enctype="multipart/form-data">

    <h3><?php _e('Daily Emails Summary', 'follow_up_emails'); ?></h3>

    <table class="form-table">
        <tbody>
        <tr valign="top">
            <th><label for="daily_emails"><?php _e('Email Address(es)', 'follow_up_emails'); ?></label></th>
            <td>
                <input type="text" name="daily_emails" id="daily_emails" value="<?php echo esc_attr( get_option('fue_daily_emails', '') ); ?>" />
                <span class="description"><?php _e('comma separated', 'follow_up_emails'); ?></span>
            </td>
        </tr>
        <tr valign="top">
            <th><label for="daily_emails_time_hour"><?php _e('Preferred Time', 'follow_up_emails'); ?></label></th>
            <td>
                <?php
                $time   = get_option('fue_daily_emails_time', '12:00 AM');
                $parts  = explode(':', $time);
                $parts2 = explode(' ', $parts[1]);
                $hour   = $parts[0];
                $minute = $parts2[0];
                $ampm   = $parts2[1];
                ?>
                <select name="daily_emails_time_hour" id="daily_emails_time_hour">
                    <?php
                    for ($x = 1; $x <= 12; $x++):
                        $val = ($x >= 10) ? $x : '0'.$x;
                    ?>
                        <option value="<?php echo $val; ?>" <?php selected($hour, $val); ?>><?php echo $val; ?></option>
                    <?php endfor; ?>
                </select>

                <select name="daily_emails_time_minute" id="daily_emails_time_minute">
                    <?php
                    for ($x = 0; $x <= 55; $x+=15):
                        $val = ($x >= 10) ? $x : '0'. $x;
                    ?>
                        <option value="<?php echo $val; ?>" <?php selected($minute, $val); ?>><?php echo $val; ?></option>
                    <?php endfor; ?>
                </select>

                <select name="daily_emails_time_ampm" id="daily_emails_time_ampm">
                    <option value="AM" <?php selected($ampm, 'AM'); ?>>AM</option>
                    <option value="PM" <?php selected($ampm, 'PM'); ?>>PM</option>
                </select>
            </td>
        </tr>
        </tbody>
    </table>

    <h3><?php _e('Email Settings', 'follow_up_emails'); ?></h3>

    <table class="form-table">
        <tbody>
        <tr valign="top">
            <th><label for="bcc"><?php _e('BCC', 'follow_up_emails'); ?></label></th>
            <td>
                <input type="text" name="bcc" id="bcc" value="<?php echo esc_attr( $bcc ); ?>" />
                <img class="help_tip" title="<?php _e('All emails will be blind carbon copied to this address', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL .'/images/help.png'; ?>" width="16" height="16" style="float:none;" />
            </td>
        </tr>
        <tr valign="top">
            <th><label for="from_name"><?php _e('From/Reply-To Name', 'follow_up_emails'); ?></label></th>
            <td>
                <input type="text" name="from_name" id="from_name" value="<?php echo esc_attr( $from_name ); ?>" />
                <img class="help_tip" title="<?php _e('The name that your emails will come from and replied to', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL .'/images/help.png'; ?>" width="16" height="16" style="float:none;" />
            </td>
        </tr>
        <tr valign="top">
            <th><label for="from_email"><?php _e('From/Reply-To Email', 'follow_up_emails'); ?></label></th>
            <td>
                <input type="text" name="from_email" id="from_email" value="<?php echo esc_attr( $from ); ?>" />
                <img class="help_tip" title="<?php _e('The email address that your emails will come from and replied to', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL .'/images/help.png'; ?>" width="16" height="16" style="float:none;" />
            </td>
        </tr>
        </tbody>
    </table>

    <?php do_action( 'fue_settings_crm' ); ?>

    <p class="submit">
        <input type="hidden" name="action" value="fue_followup_save_settings" />
        <input type="hidden" name="section" value="<?php echo $tab; ?>" />
        <input type="submit" name="save" value="<?php _e('Save Settings', 'follow_up_emails'); ?>" class="button-primary" />
    </p>

</form>