<style>
    ul.reports-stats li {
        float: left;
        width: 170px;
        font-size: 11px;
        padding: 5px 0;
        margin: 0 10px;
        /*border: 1px solid #999;*/
        min-height: 45px;
        background: #fff;
        /*border-radius: 5px;*/
    }
    ul.reports-stats li span.dashicons {
        float: left;
        display: block;
        width: 40px;
        font-size: 36px;
    }
    ul.reports-stats li strong {
        display: inline-block;
        font-size: 15px;
        margin-top: 10px;
    }
    ul.reports-stats li strong span.meta {
        font-weight: normal;
        font-size: 12px;
    }

    h2.nav-tab-wrapper{
        margin: 25px 0;
    }

    div.chart_container {
        /*border: 1px solid #999;*/
        background: #fff;
        padding: 10px;
        /*border-radius: 10px;
        -moz-border-radius: 10px;
        -webkit-border-radius: 10px;*/
    }
    div.chart_container h3 {
        text-align: center;
        color: #d0d0d0;
    }
</style>

<ul class="reports-stats">
    <li>
        <span class="dashicons dashicons-email-alt"></span>
        <strong><?php echo number_format($total_sent); ?></strong>
        <?php _e('Emails Sent', 'follow_up_emails'); ?>
    </li>
    <li>
        <span class="dashicons dashicons-visibility"></span>
        <strong><?php echo $open_pct; ?>% <span class="meta">(<?php echo number_format($total_opened); ?>)</span></strong>
        <?php _e('Opened', 'follow_up_emails'); ?>
    </li>
    <li>
        <span class="dashicons dashicons-admin-links"></span>
        <strong><?php echo $click_pct; ?>% <span class="meta">(<?php echo number_format($total_clicks); ?>)</span></strong>
        <?php _e('Clicks', 'follow_up_emails'); ?>
    </li>
    <li>
        <span class="dashicons dashicons-welcome-comments"></span>
        <strong><?php echo number_format($total_unsubscribes); ?></strong>
        <?php _e('Unsubscribes', 'follow_up_emails'); ?>
    </li>
</ul>
<div class="clear"></div>

<h2 class="nav-tab-wrapper woo-nav-tab-wrapper reports-overview-tabs">
    <a href="#opens" class="nav-tab nav-tab-active"><?php _e('Top Emails by Opens', 'follow_up_emails'); ?></a>
    <a href="#clicks" class="nav-tab"><?php _e('Top Emails by Clicks', 'follow_up_emails'); ?></a>
    <a href="#ctor" class="nav-tab"><?php _e('Top Emails by CTOR', 'follow_up_emails'); ?></a>
</h2>

<div class="chart_sections">
    <div class="chart_section" id="opens">
        <div id="opens_chart" class="chart_container"><h3>No data</h3></div>
    </div>
    <div class="chart_section" id="clicks">
        <div id="clicks_chart" class="chart_container"><h3>No data</h3></div>
    </div>
    <div class="chart_section" id="ctor">
        <div id="ctor_chart" class="chart_container"><h3>No data</h3></div>
    </div>
</div>

<script>
var clicks_json = <?php echo json_encode($clicks_data); ?>;
var opens_json = <?php echo json_encode($opens_data); ?>;
var ctor_json = <?php echo json_encode($ctor_data); ?>;

var clicks_rendered = opens_rendered = ctor_rendered = false;
</script>
<div class="subsubsub_section">
    <ul class="subsubsub">
        <li><a href="#emails" class="current"><?php _e('Emails', 'follow_up_emails'); ?></a> | </li>
        <li><a href="#users"><?php _e('Users', 'follow_up_emails'); ?></a> | </li>
        <li><a href="#excludes"><?php _e('Opt-Outs', 'follow_up_emails'); ?></a></li>
        <?php do_action( 'fue_reports_section_list' ); ?>
    </ul>
    <br class="clear">

    <div class="section" id="emails">
        <h3><?php _e('Emails', 'follow_up_emails'); ?></h3>
        <form action="admin-post.php" method="post">
            <table class="wp-list-table widefat fixed posts">
                <thead>
                    <tr>
                        <th scope="col" id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </th>
                        <th scope="col" id="type" class="manage-column column-type" style=""><?php _e('Email Name', 'follow_up_emails'); ?></th>
                        <th scope="col" id="usage_count" class="manage-column column-usage_count" style=""><?php _e('Emails Sent', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('The number of individual emails sent using this follow-up email', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                        <th scope="col" id="opened" class="manage-column column-usage_count" style=""><?php _e('Emails Opened', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('The number of times the this specific follow-up emails has been opened', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                        <th scope="col" id="clicked" class="manage-column column-usage_count" style=""><?php _e('Links Clicked', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('The number of times links in this follow-up email have been clicked', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                    </tr>
                </thead>
                <tbody id="the_list">
                    <?php
                    if (empty($email_reports)) {
                        ?>
                        <tr scope="row">
                            <th colspan="5"><?php _e('No reports available', 'follow_up_emails'); ?></th>
                        </tr><?php
                    } else {
                        foreach ($email_reports as $report) {
                            $sent       = FUE_Reports::count_email_sends( $report->email_id );
                            $opened     = FUE_Reports::count_event_occurences( $report->email_id, 'open' );
                            $clicked    = FUE_Reports::count_event_occurences( $report->email_id, 'click' );
                            $meta       = '';

                            $email_row = new FUE_Email( $report->email_id );

                            ?><tr scope="row">
                                <th scope="row" class="check-column">
                                    <input id="cb-select-106" type="checkbox" name="email_id[]" value="<?php echo $report->email_id; ?>">
                                    <div class="locked-indicator"></div>
                                </th>
                                <td class="post-title column-title">
                                    <strong><?php echo stripslashes($report->email_name); ?></strong>
                                    <em><?php echo apply_filters( 'fue_report_email_trigger', $report->email_trigger, $email_row ); ?></em><br/>
                                    <a href="admin.php?page=followup-emails-reports&tab=reportview&eid=<?php echo urlencode($report->email_id); ?>"><?php _e('View Report', 'follow_up_emails'); ?></a>
                                </td>
                                <td><a class="row-title" href="admin.php?page=followup-emails-reports&tab=reportview&eid=<?php echo urlencode($report->email_id); ?>"><?php echo $sent; ?></a></td>
                                <td><a class="row-title" href="admin.php?page=followup-emails-reports&tab=emailopen_view&eid=<?php echo urlencode($report->email_id); ?>&ename=<?php echo urlencode($report->email_name); ?>"><?php echo $opened; ?></a></td>
                                <td><a class="row-title" href="admin.php?page=followup-emails-reports&tab=linkclick_view&eid=<?php echo urlencode($report->email_id); ?>&ename=<?php echo urlencode($report->email_name); ?>"><?php echo $clicked; ?></a></td>
                            </tr><?php
                        }
                    }
                    ?>
                </tbody>
            </table>
            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <input type="hidden" name="action" value="fue_reset_reports" />
                    <input type="hidden" name="type" value="emails" />
                    <select name="emails_action">
                        <option value="-1" selected="selected"><?php _e('Bulk Actions', 'wordpress'); ?></option>
                        <option value="trash"><?php _e('Delete Selected', 'follow_up_emails'); ?></option>
                    </select>
                    <input type="submit" name="" id="doaction2" class="button action" value="Apply">
                </div>
            </div>
        </form>
    </div>
    <div class="section" id="users">
        <h3><?php _e('Users', 'follow_up_emails'); ?></h3>

        <form action="admin-post.php" method="post">
            <table class="wp-list-table widefat fixed posts">
                <thead>
                    <tr>
                        <th scope="col" id="cb_users" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-2">Select All</label>
                            <input id="cb-select-all-2" type="checkbox">
                        </th>
                        <th scope="col" id="type" class="manage-column column-type" style=""><?php _e('Customer', 'follow_up_emails'); ?></th>
                        <th scope="col" id="usage_count" class="manage-column column-usage_count" style=""><?php _e('Emails Sent', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('The number of individual emails sent using this follow-up email', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                        <th scope="col" id="opened" class="manage-column column-usage_count" style=""><?php _e('Emails Opened', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('The number of times the this specific follow-up emails has been opened', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                        <th scope="col" id="clicked" class="manage-column column-usage_count" style=""><?php _e('Links Clicked', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('The number of times links in this follow-up email have been clicked', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                    </tr>
                </thead>
                <tbody id="the_list">
                    <?php
                    if (empty($user_reports)) {
                        ?><tr scope="row">
                            <th colspan="5"><?php _e('No reports available', 'follow_up_emails'); ?></th>
                        </tr><?php
                    } else {
                        foreach ($user_reports as $report) {
                            if ( empty($report->email_address) ) continue;

                            $name       = $report->customer_name;
                            $email_key  = sanitize_title_with_dashes( $report->email_address );
                            $sent       = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_logs` WHERE `email_address` = %s", $report->email_address) );
                            $opened     = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_tracking` WHERE `user_email` = %s AND `event_type` = 'open'", $report->email_address) );
                            $clicked    = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_tracking` WHERE `user_email` = %s AND `event_type` = 'click'", $report->email_address) );

                            if ( $report->user_id != 0 ) {
                                $wp_user = new WP_User($report->user_id);
                                $name = $wp_user->first_name .' '. $wp_user->last_name;
                            }

                            ?><tr scope="row">
                                <th scope="row" class="check-column">
                                    <input id="cb-select-<?php echo $email_key; ?>" type="checkbox" name="user_email[]" value="<?php echo $report->email_address; ?>">
                                    <div class="locked-indicator"></div>
                                </th>
                                <td class="post-title column-title">
                                    <strong><?php echo apply_filters( 'fue_report_customer_name', $name, $report ); ?></strong>
                                    <a href="admin.php?page=followup-emails-reports&tab=reportuser_view&email=<?php echo urlencode($report->email_address); ?>"><?php _e('View Report'); ?></a>
                                </td>
                                <td><?php echo esc_html($sent); ?></td>
                                <td><?php echo esc_html($opened); ?></td>
                                <td><?php echo esc_html($clicked) ?></td>
                            </tr><?php
                        }
                    }
                    ?>
                </tbody>
            </table>
            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <input type="hidden" name="action" value="fue_reset_reports" />
                    <input type="hidden" name="type" value="users" />
                    <select name="users_action">
                        <option value="-1" selected="selected"><?php _e('Bulk Actions', 'wordpress'); ?></option>
                        <option value="trash"><?php _e('Delete Selected', 'follow_up_emails'); ?></option>
                    </select>
                    <input type="submit" name="" id="doaction2" class="button action" value="Apply">
                </div>
            </div>
        </form>
    </div>

    <div class="section" id="excludes">
        <h3><?php _e('Opt-Outs', 'follow_up_emails'); ?></h3>
        <table class="wp-list-table widefat fixed posts">
            <thead>
                <tr>
                    <th scope="col" id="coupon_name" class="manage-column column-type" style=""><?php _e('Email Name', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('The name of the follow-up email that a customer has opted out of', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                    <th scope="col" id="coupon_name" class="manage-column column-type" style=""><?php _e('Email Address', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('The email address of the customer that opted out', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                    <th scope="col" id="coupon_name" class="manage-column column-type" style=""><?php _e('Date', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('The date and time that the email address was opted out this follow-up email', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                </tr>
            </thead>
            <tbody id="the_list">
                <?php
                if (empty($exclude_reports)) {
                    ?><tr scope="row">
                        <th colspan="3"><?php _e('No reports available', 'follow_up_emails'); ?></th>
                    </tr><?php
                } else {
                    $excludes_block = '';
                    foreach ($exclude_reports as $report) {
                        echo '
                        <tr scope="row">
                            <td class="post-title column-title">
                                <strong>'. stripslashes($report->email_name) .'</strong>
                            </td>
                            <td>'. esc_html($report->email) .'</td>
                            <td>'. date( get_option('date_format') .' '. get_option('time_format') , strtotime($report->date_added)) .'</td>
                        </tr>
                        ';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <?php do_action( 'fue_reports_section_div' ); ?>
</div>