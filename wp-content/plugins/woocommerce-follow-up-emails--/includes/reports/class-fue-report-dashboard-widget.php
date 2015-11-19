<?php

/**
 * Class FUE_Report_Dashboard_Widget
 */
class FUE_Report_Dashboard_Widget {

    public static function display() {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        $stats = array(
            'total_emails_sent'         => 0,
            'emails_sent_today'         => 0,
            'emails_scheduled_total'    => 0
        );

        $today  = date( 'Y-m-d', current_time('timestamp') );
        $from   = $today .' 00:00:00';
        $to     = $today .' 23:59:59';

        $stats['total_emails_sent'] = FUE_Reports::count_emails_sent();

        $stats['emails_sent_today'] = FUE_Reports::count_emails_sent( array( $from, $to ) );

        $stats['emails_scheduled_total'] = $wpdb->get_var(
            "SELECT COUNT(*)
            FROM {$wpdb->prefix}followup_email_orders o, {$wpdb->posts} p
            WHERE o.is_sent = 0
            AND o.email_id = p.ID"
        );

        include FUE_TEMPLATES_DIR .'/dashboard-widget.php';
    }

}
