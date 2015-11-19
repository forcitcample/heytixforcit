<?php

/**
 * Class FUE_Reports
 */
class FUE_Reports {

    /**
     * Hook in the methods
     */
    public function __construct() {
        add_action('fue_menu', array($this, 'menu'), 20);

        // scripts
        add_action( 'admin_enqueue_scripts', array($this, 'admin_scripts') );
        add_action( 'admin_enqueue_scripts', array($this, 'user_profile_script') );

        // email open tracking
        add_action( 'init', array($this, 'pixel_tracker') );

        // email tracking
        add_filter('query_vars', array($this, 'query_vars'));
        add_action('template_redirect', array($this, 'link_clicked'));

        // sending
        add_filter('fue_email_message', array($this, 'inject_pixel_tracker'), 10, 3);

        // daily summary content
        add_filter( 'fue_adhoc_email_message', array($this, 'generate_daily_summary_html'), 10, 2 );

        add_action( 'fue_email_order_sent', array($this, 'register_action_scheduler_for_daily_summary') );

    }

    /**
     * Register the menu entry and the submenu page
     */
    public function menu() {
        add_submenu_page( 'followup-emails', __('Reports', 'follow_up_emails'), __('Reports', 'follow_up_emails'), 'manage_follow_up_emails', 'followup-emails-reports', 'FUE_Reports::settings_main' );
    }

    /**
     * Charting scripts
     */
    public function admin_scripts() {
        if ( (empty($_GET['page']) || $_GET['page'] != 'followup-emails-reports') ) {
            return;
        }

        wp_enqueue_script( 'jsapi', '//www.google.com/jsapi' );
        wp_enqueue_script( 'fue-reports', FUE_TEMPLATES_URL .'/js/reports.js', array('jsapi'), FUE_VERSION );
    }

    /**
     * Add a link to the User Reports from the User Edit screen using JS
     */
    public function user_profile_script() {
        $screen = get_current_screen();

        if ( !in_array( $screen->id, array('user-edit', 'profile') ) ) {
            return;
        }

        $user_id = !empty($_GET['user_id']) ? absint($_GET['user_id']) : 0;

        if ( !$user_id ) {
            $user_id = get_current_user_id();
        }

        $user = new WP_User( $user_id );

        wp_enqueue_script( 'fue-user-report-link', FUE_TEMPLATES_URL .'/js/user-reports-link.js', array('jquery'), FUE_VERSION );
        wp_localize_script( 'fue-user-report-link', 'FUE_USER_REPORT', array(
            'options_title' => __('Personal Options'),
            'reports_title' => __('Email Reports', 'follow_up_emails'),
            'reports_link'  => 'admin.php?page=followup-emails-reports&tab=reportuser_view&email='. urlencode($user->user_email) .'&user_id='. $user->ID
        ) );
    }

    /**
     * Register the query parameters to track clicks from an email link
     *
     * @param array $vars
     * @return array
     */
    function query_vars($vars) {
        $vars[] = 'sfn_trk';
        $vars[] = 'sfn_data';

        return $vars;
    }

    /**
     * Record the link click and redirect to the final destination.
     * Only log unique clicks per customer per email.
     */
    function link_clicked() {
        if ( intval(get_query_var('sfn_trk')) == 1 ) {

            $payload = get_query_var('sfn_payload');

            if ( empty( $payload ) ) {
                $payload = get_query_var('sfn_data');
            }

            if ( empty( $payload ) ) {
                return;
            }

            $payload = base64_decode( $payload );
            $payload = str_replace( '&amp;', '&', $payload );

            $parsed = array();
            parse_str($payload, $parsed);

            if ( ! is_array($parsed) || count($parsed) < 3 ) return;

            $log_data = array(
                'event'     => 'click',
                'queue_id'  => isset($parsed['oid']) ? $parsed['oid'] : 0,
                'email_id'  => $parsed['eid'],
                'user_id'   => isset($parsed['user_id']) ? $parsed['user_id'] : 0,
                'user_email'=> $parsed['user_email'],
                'target_url'=> $parsed['next']
            );
            $tracker = new FUE_Report_Email_Tracking( Follow_Up_Emails::instance() );
            $tracker->log_event( 'click', $log_data );

            // mark email as 'opened'
            unset($log_data['target_url']);
            $tracker->log_event( 'open', $log_data );

            $next = add_query_arg( array(
                'fueid' => $parsed['eid'],
                'qid'   => $parsed['oid']
            ), $parsed['next'] );
            wp_redirect( $next );
            exit;
        }
    }

    /**
     * Record as an "email open" event when the pixel tracker
     * included in every sent email is loaded. Only the first
     * email open will be logged for each email/customer.
     */
    public function pixel_tracker() {
        if ( isset($_GET['fuepx']) && $_GET['fuepx'] == 1 ) {
            if (!isset($_GET['data'])||empty($_GET['data'])) return;

            header("Content-Type: image/gif");

            $data   = base64_decode($_GET['data']);
            $parsed = array();
            parse_str($data, $parsed);

            // log this
            $log_data = array(
                'event'     => 'open',
                'queue_id'  => isset($parsed['oid']) ? $parsed['oid'] : 0,
                'email_id'  => absint($parsed['eid']),
                'user_id'   => isset($parsed['user_id']) ? $parsed['user_id'] : 0,
                'user_email'=> $parsed['user_email'],
                'target_url'=> ''
            );

            $tracker = new FUE_Report_Email_Tracking( Follow_Up_Emails::instance() );
            $tracker->log_event( 'open', $log_data );

        }
    }

    /**
     * Inject the pixel tracker image to the email body before it is sent
     *
     * @param string    $message
     * @param FUE_Email $email
     * @param object    $email_order
     *
     * @return string The modified email body
     */
    public function inject_pixel_tracker( $message, $email, $email_order ) {
        global $wpdb;

        $user_id = 0;
        if ( $email_order->order_id != 0 ) {
            // order
            $order = WC_FUE_Compatibility::wc_get_order($email_order->order_id);

            if ( isset($order->user_id) && $order->user_id > 0 ) {
                $user_id    = $order->user_id;
                $wp_user    = new WP_User( $order->user_id );
                $email_to   = $wp_user->user_email;
            } else {
                $email_to   = $order->billing_email;
            }
        } else {
            $order      = false;
            $wp_user    = new WP_User( $email_order->user_id );
            $user_id    = $email_order->user_id;
            $email_to   = $wp_user->user_email;
        }

        $qstring    = base64_encode('oid='. $email_order->id .'&eid='. $email->id .'&user_email='. $email_to .'&user_id='. $user_id);
        $px_url     = add_query_arg('fuepx', 1, add_query_arg('data', $qstring, site_url()));
        $message    .= '<img src="'. $px_url .'" height="1" width="1" />';

        return $message;
    }

    /**
     * Set the daily summary content before sending the adhoc email
     *
     * @param string $message
     * @param FUE_Sending_Queue_Item $queue
     *
     * @return string
     */
    public function generate_daily_summary_html( $message, $queue ) {
        if ( isset( $queue->meta['daily_summary'] ) ) {
            $message = self::get_summary_email_html();
        }

        return $message;
    }

    /**
     * Fired everytime an email is sent, check to make sure that there
     * is a scheduled daily summary email in the queue.
     */
    public function register_action_scheduler_for_daily_summary() {
        $items = Follow_Up_Emails::instance()->scheduler->get_items(array(
            'is_sent'       => 0,
            'email_trigger' => 'Daily Summary'
        ));

        if ( empty( $items ) ) {
            // there are no unsent daily_summary emails in the queue
            FUE_Sending_Scheduler::queue_daily_summary_email();
        } else {
            $scheduled = false;

            foreach ( $items as $item ) {
                $param      = array('email_order_id' => $item->id);
                $actions    = wc_get_scheduled_actions(array(
                    'hook'      => 'sfn_followup_emails',
                    'args'      => $param,
                    'status'    => ActionScheduler_Store::STATUS_PENDING
                ));

                if ( !empty( $actions ) ) {
                    $scheduled = true;
                    break;
                }
            }

            if ( !$scheduled ) {
                FUE_Sending_Scheduler::queue_daily_summary_email();
            }
        }
    }

    /**
     * Insert a log entry into the database
     * @param int       $email_id
     * @param int       $email_order_id
     * @param int       $user_id
     * @param string    $name
     * @param string    $cname
     * @param string    $mail_to
     * @param int       $order_id
     * @param int       $product_id
     * @param int       string $trigger
     */
    public static function email_log($email_id, $email_order_id, $user_id, $name, $cname, $mail_to, $order_id, $product_id, $trigger = '') {

        $log = array(
            'email_id'      => $email_id,
            'email_order_id'=> $email_order_id,
            'user_id'       => $user_id,
            'email_name'    => $name,
            'customer_name' => $cname,
            'email_address' => $mail_to,
            'date_sent'     => current_time('mysql'),
            'order_id'      => $order_id,
            'product_id'    => $product_id,
            'email_trigger' => $trigger
        );

        self::email_log_array( $log );
    }

    /**
     * Save a log into the database.
     *
     * Accepts an array of values unlike @see FUE_Reports::email_log()
     *
     * @param array $log
     */
    public static function email_log_array( $log ) {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        $wpdb->insert(
            $wpdb->prefix .'followup_email_logs',
            $log
        );
    }

    /**
     * Controller for the admin interface
     */
    public static function settings_main() {
        $tab = (isset($_GET['tab'])) ? $_GET['tab'] : 'reports';

        self::reports_header($tab);

        if ($tab == 'reports') {
            FUE_Reports::reports_html();
        } elseif ($tab == 'reportview') {
            FUE_Reports::reportview_html();
        } elseif ($tab == 'reportuser_view') {
            echo FUE_Reports::user_view_html();
        } elseif ($tab == 'emailopen_view') {
            echo FUE_Reports::email_open_html($_GET['eid'], $_GET['ename']);
        } elseif ($tab == 'linkclick_view') {
            echo FUE_Reports::link_click_html($_GET['eid'], $_GET['ename']);
        } elseif ($tab == 'dbg_queue') {
            FUE_Reports::queue();
        }

        self::reports_footer();

    }

    /**
     * Reports Overview admin interface
     */
    static function reports_html() {
        global $wpdb;

        $email_reports  = FUE_Reports::get_reports(array('type' => 'emails'));
        $user_reports   = FUE_Reports::get_reports(array('type' => 'users'));
        $exclude_reports= FUE_Reports::get_reports(array('type' => 'excludes'));

        $emails_block   = '';
        $users_block    = '';

        $total_sent         = self::count_emails_sent();
        $total_opened       = self::count_opened_emails();
        $total_clicks       = self::count_total_email_clicks();
        $total_unsubscribes = self::count_total_unsubscribes();
        $open_pct           = 0;
        $click_pct          = 0;

        if ( $total_opened > 0 ) {
            $open_pct   = round( ($total_opened / $total_sent) * 100 );
        }

        if ( $total_clicks > 0 ) {
            $click_pct  = round( ($total_clicks / $total_sent) * 100 );
        }

        $cols = array(
            __('Email', 'follow_up_emails'),
            __('Sent', 'follow_up_emails'),
            __('Opens', 'follow_up_emails'),
            __('Clicks', 'follow_up_emails')
        );

        $clicks_emails  = self::get_top_emails_by('click', 5);
        $opens_emails   = self::get_top_emails_by('open', 5);
        $ctor_emails    = self::get_top_emails_by('ctor', 5);

        $clicks_data = array(
            array(
                __('Email', 'follow_up_emails'),
                __('Sent', 'follow_up_emails'),
                __('Opens', 'follow_up_emails'),
                __('Clicks', 'follow_up_emails')
            )
        );

        $opens_data = array(
            array(
                __('Email', 'follow_up_emails'),
                __('Sent', 'follow_up_emails'),
                __('Opens', 'follow_up_emails'),
                __('Clicks', 'follow_up_emails')
            )
        );

        $ctor_data = array(
            array(
                __('Email', 'follow_up_emails'),
                __('Opens', 'follow_up_emails'),
                __('Clicks', 'follow_up_emails'),
                __('Click Rate (%)', 'follow_up_emails')
            )
        );

        if ( $clicks_emails ) {
            $clicks_data = array_merge( $clicks_data, $clicks_emails );
        }

        if ( $opens_emails ) {
            $opens_data = array_merge( $opens_data, $opens_emails );
        }

        if ( $ctor_emails ) {
            $ctor_data = array_merge( $ctor_data, $ctor_emails );
        }

        include FUE_TEMPLATES_DIR .'/reports/overview.php';
        return;

    }

    /**
     * View Report for a specific FUE_Email
     */
    public static function reportview_html() {
        $id         = urldecode($_GET['eid']);
        $reports    = FUE_Reports::get_reports(array('id' => $id, 'type' => 'emails'));

        include FUE_TEMPLATES_DIR .'/reports/report_view.php';
        return;
    }

    /**
     * View report for a customer
     */
    public static function user_view_html() {
        global $wpdb;

        $email      = $_GET['email'];
        $reports    = FUE_Reports::get_reports(array('email' => $email, 'type' => 'users'));

        $user_id    = (!empty($_GET['user_id'])) ? $_GET['user_id'] : 0;

        if ( $user_id == 0 ) {
            $fue_user   = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_customers WHERE email_address = %s", $email) );

            if ( $fue_user ) {
                $user_id = $fue_user->user_id;
            }
        }

        $user_email = get_user_meta( $user_id, 'billing_email', true );
        $sql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_email_excludes WHERE email = %s", $user_email );
        $excludes   = $wpdb->get_results( $sql );
        
        $queue = $wpdb->get_results( $wpdb->prepare("SELECT DISTINCT * FROM {$wpdb->prefix}followup_email_orders WHERE is_sent = 0 AND user_email = %s ORDER BY send_on ASC", $email) );

        include FUE_TEMPLATES_DIR .'/reports/user_view.php';
        return;
    }

    /**
     * Report for "opened" emails
     * @param $id
     * @param $name
     */
    public static function email_open_html($id, $name) {
        global $wpdb;

        $reports = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_email_tracking WHERE `event_type` = 'open' AND `email_id` = %d ORDER BY `date_added` DESC", $id) );

        include FUE_TEMPLATES_DIR .'/reports/email_open.php';
        return;
    }

    /**
     * Report for clicked email links
     *
     * @param $id
     * @param $name
     */
    public static function link_click_html($id, $name) {
        global $wpdb;

        $reports = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_email_tracking WHERE `event_type` = 'click' AND `email_id` = %d ORDER BY `date_added` DESC", $id) );

        include FUE_TEMPLATES_DIR .'/reports/link_click.php';
        return;
    }

    /**
     * Send a summary of emails sent within a defined time period
     */
    public static function send_summary() {
        // send the email
        $subject    = __('Follow-up emails summary', 'follow_up_emails');
        $recipient  = self::get_summary_recipient_emails();
        $body       = self::get_summary_email_html();

        FUE_Sending_Mailer::mail($recipient, $subject, $body);

        update_option( 'fue_last_summary', current_time( 'timestamp' ) );
        update_option( 'fue_next_summary', current_time( 'timestamp' ) + 86400 );
    }

    /**
     * Get the recipient email for the summary emails. Defaults to the admin email if not set
     *
     * @since 4.1.4
     * @return string
     */
    public static function get_summary_recipient_emails() {
        $recipient  = get_option('fue_daily_emails', false);

        if (! $recipient) {
            $recipient = get_bloginfo('admin_email');
        }

        return $recipient;
    }

    /**
     * Get the HTML table for the daily summary email
     *
     * @since 4.1.4
     * @return string
     */
    public static function get_summary_email_html() {
        global $wpdb;

        $last_send      = get_option('fue_last_summary', 0);
        $next_send      = get_option('fue_next_summary', 0);
        $now            = current_time('timestamp');
        $reports        = '';

        $sfn_reports = $wpdb->get_results( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_email_orders` WHERE `is_sent` = 1 AND `date_sent` >= %s", date('Y-m-d H:i:s', $last_send)) );

        if ( empty($sfn_reports) ) {
            return '';
        }

        foreach ( $sfn_reports as $report ) {
            $product_str    = 'n/a';
            $order_str      = 'n/a';
            $coupon_str     = '-';
            $order          = false;
            $email_name     = '-';

            if ( $report->email_id ) {
                $email      = new FUE_Email( $report->email_id );
                $email_name = $email->name;
            }

            if ( $report->product_id != 0 ) {
                $product_str = '<a href="'. get_permalink($report->product_id) .'">'. get_the_title($report->product_id) .'</a>';
            }

            if (! empty($report->coupon_name) && ! empty($report->coupon_code )) {
                $coupon_str = $report->coupon_name .' ('. $report->coupon_code .')';
            }

            $email_address = $report->user_email;

            $email_address  = apply_filters( 'fue_report_email_address', $email_address, $report );
            $order_str      = apply_filters( 'fue_report_order_str', '', $report );

            $reports .= '
            <tr>
                <td style="font-size: 11px; text-align:left; vertical-align:middle; border: 1px solid #eee;">'. $email_name .'</td>
                <td style="font-size: 11px; text-align:left; vertical-align:middle; border: 1px solid #eee;">'. $email_address .'</td>
                <td style="font-size: 11px; text-align:left; vertical-align:middle; border: 1px solid #eee;">'. $product_str .'</td>
                <td style="font-size: 11px; text-align:left; vertical-align:middle; border: 1px solid #eee;">'. $order_str .'</td>
                <td style="font-size: 11px; text-align:left; vertical-align:middle; border: 1px solid #eee;">'. $report->email_trigger.'</td>
                <td style="font-size: 11px; text-align:left; vertical-align:middle; border: 1px solid #eee;">'. $coupon_str .'</td>
            </tr>';
        }

        $body = '<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
                    <thead>
                        <tr>
                            <th scope="col" style="text-align:left; border: 1px solid #eee;">'. __('Email Name', 'follow_up_emails') .'</th>
                            <th scope="col" style="text-align:left; border: 1px solid #eee;">'. __('Email Address', 'follow_up_emails') .'</th>
                            <th scope="col" style="text-align:left; border: 1px solid #eee;">'. __('Product', 'follow_up_emails') .'</th>
                            <th scope="col" style="text-align:left; border: 1px solid #eee;">'. __('Order', 'follow_up_emails') .'</th>
                            <th scope="col" style="text-align:left; border: 1px solid #eee;">'. __('Trigger', 'follow_up_emails') .'</th>
                            <th scope="col" style="text-align:left; border: 1px solid #eee;">'. __('Sent Coupon', 'follow_up_emails') .'</th>
                        </tr>
                    </thead>
                    <tbody>
                        '. $reports .'
                    </tbody>
                </table>';

        return $body;
    }

    /**
     * Get reports based on the passed $args array
     *
     * $args can contain any of the following keys:
     *      id      - Retrieve the reports for the given email ID
     *      email   - Email address to search against
     *      type    - report type (emails, users, coupons or excludes)
     *      sort    - array (sort and sortby)
     *
     * @param array $args
     * @return array
     */
    public static function get_reports( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'id'        => '',
            'email'     => '',
            'type'      => 'emails',
            'sort'      => array(),
            'page'      => 1,
            'limit'     => -1
        );
        $args       = array_merge($defaults, $args);
        $limit_sql  = '';

        if ( $args['limit'] > 0 ) {
            $start      = ($args['page'] * $args['limit']) - $args['limit'];
            $limit_sql = "LIMIT {$start},{$args['limit']}";
        }

        if ( $args['type'] == 'emails' ) {
            if ( empty($args['id']) ) {
                $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM  `{$wpdb->prefix}followup_email_logs` GROUP BY email_id ORDER BY date_sent DESC";
            } else {
                $sql = $wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM `{$wpdb->prefix}followup_email_logs` WHERE `email_id` = %d ORDER BY `date_sent` DESC", $args['id']);
            }
        } elseif ( $args['type'] == 'users' ) {
            $sortby = 'date_sent';
            $sort   = 'desc';

            if ( !empty($args['sort']) ) {
                $sortby = $args['sort']['sortby'];
                $sort   = $args['sort']['sort'];
            }

            if ( empty($args['email']) ) {
                $sql = "SELECT SQL_CALC_FOUND_ROWS customer_name, email_address, user_id FROM `{$wpdb->prefix}followup_email_logs` GROUP BY email_address ORDER BY $sortby $sort";
            } else {
                $sql = $wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM `{$wpdb->prefix}followup_email_logs` WHERE `email_address` = %s ORDER BY $sortby $sort", $args['email']);
            }
        } elseif ( $args['type'] == 'coupons' ) {
            $sortby = 'date_sent';
            $sort   = 'desc';
            if ( !empty($args['sort']) ) {
                $sortby = $args['sort']['sortby'];
                $sort   = $args['sort']['sort'];
            }

            if ( empty($args['id']) ) {
                $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM  `{$wpdb->prefix}followup_coupon_logs` ORDER BY $sortby $sort";
            } else {
                $sql = $wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM `{$wpdb->prefix}followup_coupon_logs` WHERE `coupon_id` = %d ORDER BY $sortby $sort", $args['id']);
            }
        } elseif ( $args['type'] == 'excludes' ) {
            if ( empty($args['id']) ) {
                $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM `{$wpdb->prefix}followup_email_excludes` ORDER BY `date_added` DESC";
            } else {
                $sql = $wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM `{$wpdb->prefix}followup_email_excludes` WHERE `email_id` = %d ORDER BY `date_added` DESC", $args['id']);
            }
        }

        $sql .= " ". $limit_sql;

        return $wpdb->get_results( $sql );
    }

    /**
     * Display a table that mirrors the followup_email_orders table for debugging purposes
     */
    public static function queue() {
        global $wpdb;

        $items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_email_orders ORDER BY id DESC", ARRAY_A);

        ?>
        <p>
            <code>Server Time: <?php echo date('F d, Y h:i:s A', current_time('timestamp')); ?></code><br/>
            <code>GMT: <?php echo gmdate('F d, Y h:i:s A'); ?></code>
        </p>

        <?php if (! $items ): ?>
            <p><?php _e('No items in the queue', 'follow_up_emails'); ?></p>
        <?php
        else:
            $heading = array_keys($items[0]);
            ?>
            <table class="wp-list-table widefat fixed posts">
                <thead>
                <tr>
                    <?php
                    foreach ( $heading as $key ):
                        $label = $key;
                        ?>
                        <th scope="col" id="<?php echo $key; ?>" class="manage-column column-<?php echo $key; ?>" style=""><?php echo $label; ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody id="the_list">
                <?php foreach ( $items as $item ): ?>
                    <tr>
                        <?php
                        foreach ($heading as $key):
                            $value = $item[$key];

                            if ( $key == 'send_on' ) $value = date('F d h:i a', $item[$key]);

                            if ( $key == 'meta' && !empty($value) ) {
                                $value = '<a class="button" href="#" onclick="javascript: jQuery(\'#meta_'. $item['id'] .'\').slideToggle(); return false;">Show Value</a>';
                            }
                            ?>
                            <td><?php echo $value; ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr id="meta_<?php echo $item['id']; ?>" style="display: none;">
                        <td colspan="<?php echo count($heading); ?>">
                            <?php
                            $meta = maybe_unserialize( $item['meta'] );
                            $value = '';
                            if ( is_array($meta)) foreach ( $meta as $meta_key => $meta_value ) {
                                if ( is_array($meta_value) ) {
                                    $value .= '<b>'. $meta_key .'</b>: <pre>'. print_r($meta_value, true).'</pre><br/>';
                                } else {
                                    $value .= '<b>'. $meta_key .'</b>: '. $meta_value.'<br/>';
                                }
                            }
                            echo $value;
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php
    }

    public static function reports_header($tab) {
        ?>
        <div class="wrap">
        <div class="icon32"><img src="<?php echo FUE_TEMPLATES_URL .'/images/send_mail.png'; ?>" /></div>
        <h2>
        <?php _e('Follow-Up Emails &raquo; Email Reports', 'follow_up_emails'); ?>
        </h2><?php

        if ( isset($_GET['cleared']) ) {
            echo '<div class="updated"><p>'. __('The selected reports have been deleted', 'follow_up_emails') .'</p></div>';
        }
    }

    public static function reports_footer() {
        ?>
        </div>
    <?php
    }

    /**
     * Clear all stored reports
     *
     * @param array $data
     * @return bool
     */
    public static function reset($data) {
        global $wpdb;

        if ( $data['type'] == 'emails' && $data['emails_action'] == 'trash' ) {
            $email_ids_str = implode(',', $data['email_id']);

            foreach ( $data['email_id'] as $email_id ) {
                fue_update_email( array(
                    'id'            => $email_id,
                    'usage_count'   => 0
                ) );
            }

            $wpdb->query("DELETE FROM {$wpdb->prefix}followup_email_logs WHERE email_id IN ($email_ids_str)");
            $wpdb->query("DELETE FROM {$wpdb->prefix}followup_email_tracking WHERE email_id IN ($email_ids_str)");
        } elseif ( $data['type'] == 'users' && $data['users_action'] == 'trash' ) {
            $emails_str = '';

            foreach ( $data['user_email'] as $email ) {
                $emails_str .= "'$email',";
            }
            $emails_str = rtrim($emails_str, ',');

            $wpdb->query("DELETE FROM {$wpdb->prefix}followup_email_logs WHERE email_address IN ($emails_str)");
        }

        do_action( 'fue_reports_reset', $data );

        return true;
    }

    /**
     * Regularly send anonymous usage data
     */
    public static function send_usage_data() {
        global $wpdb;

        if ( self::is_sending_usage_data_disabled() )
            return;

        $site_id = self::get_site_id();

        $wp_version = get_bloginfo('version');

        $site_data  = array(
            'site_id'       => $site_id,
            'fue_version'   => FUE_VERSION,
            'wp_version'    => $wp_version,
            'scheduler'     => Follow_Up_Emails::$scheduling_system
        );

        if ( Follow_Up_Emails::$is_woocommerce ) {
            $wc_version = (defined('WC_VERSION')) ? WC_VERSION : WOOCOMMERCE_VERSION;
            $site_data['wc_version'] = $wc_version;
        }

        self::api_call( 'register_site', $site_data );

        self::import_usage_data();

        // number of emails sent since the last run
        $last_check = get_option( 'fue_usage_last_report', 0 );

        $sent_data  = array();

        $sql = $wpdb->prepare(
            "SELECT email_id
            FROM {$wpdb->prefix}followup_email_orders eo
            WHERE eo.is_sent = 1
            AND eo.date_sent >= %s",
            date('Y-m-d H:i:s', $last_check)
        );
        $sent_emails = $wpdb->get_col( $sql );

        foreach ( $sent_emails as $sent_email_id ) {

            $type = fue_get_email_type( $sent_email_id );

            if ( isset($sent_data[$type]) ) {
                $sent_data[$type]++;
            } else {
                $sent_data[$type] = 1;
            }
        }

        if ( empty($sent_data) )
            return;

        $token      = self::get_auth_token();
        $resp       = self::api_call( 'register_usage', array('site_id' => $site_id, 'data' => $sent_data), $token );

        if ( isset($resp->code) && $resp->code == 401 ) {
            // try to get a new token
            $token      = self::get_auth_token(true);
            $resp       = self::api_call( 'register_usage', array('site_id' => $site_id, 'data' => $sent_data), $token );
        }

        update_option( 'fue_usage_last_report', current_time( 'timestamp' ) );
    }

    /**
     * Import all previous usage data that were not sent yet
     */
    public static function import_usage_data() {
        global $wpdb;

        if ( self::is_sending_usage_data_disabled() )
            return;

        $imported   = get_option('fue_imported_previous_usage', false);

        if ( $imported )
            return;

        $emails = $wpdb->get_results("
            SELECT eo.email_id, eo.date_sent
            FROM {$wpdb->prefix}followup_email_orders eo
            WHERE eo.is_sent = 1");

        $sent_data = array();
        foreach ( $emails as $email ) {
            $type = fue_get_email_type( $email->email_id );
            $date = date('Y-m-d', strtotime($email->date_sent));

            if ( isset($sent_data[$date][$type]) ) {
                $sent_data[$date][$type]++;
            } else {
                $sent_data[$date][$type] = 1;
            }
        }

        if ( empty($sent_data) )
            return;

        $site_id    = self::get_site_id();
        $date       = date('Y-m-d');
        $token      = self::get_auth_token();
        $resp       = self::api_call( 'register_usage', array('site_id' => $site_id, 'dated' => 1, 'data' => $sent_data), $token );

        if ( isset($resp->code) && $resp->code == 401 ) {
            // try to get a new token
            $token      = self::get_auth_token(true);
            $resp       = self::api_call( 'register_usage', array('site_id' => $site_id, 'dated' => 1, 'data' => $sent_data), $token );
        }

        update_option( 'fue_usage_last_report', current_time( 'timestamp' ) );
        update_option('fue_imported_previous_usage', true);
    }

    /**
     * Get the number of sent emails in the given range. If $range is empty, it will
     * return the total number of sent emails for all time
     *
     * @param array $range
     * @param int $email_id
     * @param string $email_address
     * @return int
     */
    public static function count_emails_sent( $range = array(), $email_id = null, $email_address = '' ) {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}followup_email_logs WHERE 1=1";

        if ( !empty( $range ) ) {
            $sql .= " AND date_sent BETWEEN '{$range[0]}' AND '{$range[1]}'";
        }

        if ( !is_null( $email_id ) ) {
            $sql .= " AND email_id = ". esc_sql($email_id);
        }

        if ( !empty( $email_address ) && is_email( $email_address ) ) {
            $sql .= " AND email_address = '". esc_sql($email_address) ."'";
        }

        $result = $wpdb->get_var( $sql );

        return $result;

    }

    /**
     * Count the number of emails that have been opened/read using the pixel tracker
     * @param array $args
     * @return int
     */
    public static function count_opened_emails( $args = array() ) {
        $default = array(
            'select_clause' => 'DISTINCT email_order_id',
            'event_type'    => 'open'
        );
        $args = wp_parse_args( $args, $default );

        $events = self::get_tracking_events( $args );

        return count( $events );
    }

    /**
     * Count the number of times links inside emails have been clicked
     * @param array $args
     * @return int
     */
    public static function count_total_email_clicks( $args = array() ) {
        $default = array(
            'event_type' => 'click'
        );
        $args = wp_parse_args( $args, $default );
        $events = self::get_tracking_events( $args );

        return count( $events );
    }

    /**
     * Get the number of users who have unsubscribed from receiving emails
     */
    public static function count_total_unsubscribes() {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        $count = $wpdb->get_var("SELECT COUNT( DISTINCT email ) FROM {$wpdb->prefix}followup_email_excludes");

        return $count;
    }

    /**
     * Get tracking events filtered by $args
     *
     * @param array $args
     * @return array
     */
    public static function get_tracking_events( $args ) {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        $default = array(
            'select_clause'     => 'SQL_CALC_FOUND_ROWS *',
            'event_type'        => '',
            'email_order_id'    => 0,
            'email_id'          => 0,
            'user_id'           => 0,
            'user_email'        => '',
            'orderby'           => 'date_added',
            'sort'              => 'DESC',
            'range'             => array(),
            'page'              => 1,
            'limit'          => 0
        );
        $args = wp_parse_args( $args, $default );

        $sql    = "SELECT {$args['select_clause']} FROM {$wpdb->prefix}followup_email_tracking WHERE 1=1";
        $params = array();

        if ( !empty( $args['event_type'] ) ) {
            $sql        .= " AND event_type = %s";
            $params[]   = $args['event_type'];
        }

        if ( !empty( $args['email_order_id'] ) ) {
            $sql        .= " AND email_order_id = %d";
            $params[]   = $args['email_order_id'];
        }

        if ( !empty( $args['email_id'] ) ) {
            $sql        .= " AND email_id = %d";
            $params[]   = $args['email_id'];
        }

        if ( !empty( $args['user_id'] ) ) {
            $sql        .= " AND user_id = %d";
            $params[]   = $args['user_id'];
        }

        if ( !empty( $args['user_email'] ) ) {
            $sql        .= " AND user_email = %s";
            $params[]   = $args['user_email'];
        }

        if ( $args['range'] ) {
            $sql        .= " AND date_added BETWEEN %s AND %s";
            $params[]   = $args['range']['from'];
            $params[]   = $args['range']['to'];
        }

        $sql .= " ORDER BY {$args['orderby']} {$args['sort']}";

        if ( $args['limit'] > 0 ) {
            $start  = ($args['page'] * $args['limit']) - $args['limit'];
            $sql    .= " LIMIT {$start}, {$args['limit']}";
        }

        return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
    }

    /**
     * Count the number of sends an email has logged
     *
     * @param int $email_id
     * @return int
     */
    public static function count_email_sends( $email_id ) {
        global $wpdb;

        return $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*)
            FROM {$wpdb->prefix}followup_email_logs
            WHERE email_id = %d",
            $email_id
        ) );
    }

    /**
     * Count the number of times an event has occured in the given email ID
     *
     * @param int $email_id
     * @param string $event click or open
     * @return int
     */
    public static function count_event_occurences( $email_id, $event ) {
        global $wpdb;

        return $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*)
            FROM {$wpdb->prefix}followup_email_tracking
            WHERE email_id = %d
            AND event_type = %s",
            $email_id,
            $event
        ) );
    }

    /**
     * Get the top performing emails based on the $event selected.
     *
     * @param string $event 'open', 'click' or 'ctor' (click to open rate)
     * @param int $length Number of emails to return
     * @return array
     */
    public static function get_top_emails_by( $event, $length = 5 ) {
        $wpdb   = Follow_Up_Emails::instance()->wpdb;
        $top    = array();

        // look for stored reports
        if ( !defined('FUE_DEBUG') ) {
            $stored_emails = get_transient( 'fue_top_emails_'. $event );

            if ( $stored_emails ) {
                return $stored_emails;
            }
        }

        if ( $event == 'click' || $event == 'open' ) {
            $emails = $wpdb->get_results(
                "SELECT DISTINCT email_id, COUNT(email_id) AS occurence
                FROM {$wpdb->prefix}followup_email_tracking
                WHERE event_type = '". esc_sql($event) ."'
                GROUP BY email_id
                ORDER BY occurence DESC"
            );

            foreach ( $emails as $row ) {
                $email_id = $row->email_id;
                $email_name = $wpdb->get_var( $wpdb->prepare(
                    "SELECT email_name
                    FROM {$wpdb->prefix}followup_email_logs
                    WHERE email_id = %d LIMIT 1",
                    $email_id
                ) );

                if ( $event == 'open' ) {
                    $item = array(
                        $email_name,
                        absint( self::count_emails_sent( array(), $email_id ) ), // sent
                        absint($row->occurence), // opens
                        absint( self::count_total_email_clicks( array( 'email_id' => $email_id ) ) ) // clicks
                    );
                } else {
                    $item = array(
                        $email_name,
                        absint( self::count_emails_sent( array(), $email_id ) ), // sent
                        absint( self::count_opened_emails( array( 'email_id' => $email_id ) ) ), // opens
                        absint($row->occurence) // clicks
                    );
                }



                $top[] = $item;

                if ( count( $top ) >= $length ) {
                    break;
                }
            }
        } elseif ( $event == 'ctor' ) {
            $clicks = $wpdb->get_results(
                "SELECT email_id, COUNT(email_id) AS occurence
                FROM {$wpdb->prefix}followup_email_tracking
                WHERE event_type = 'click'
                GROUP BY email_id
                ORDER BY occurence DESC
                LIMIT 100",
                ARRAY_A
            );
            $opens = $wpdb->get_results(
                "SELECT email_id, COUNT(email_id) AS occurence
                FROM {$wpdb->prefix}followup_email_tracking
                WHERE event_type = 'open'
                GROUP BY email_id
                ORDER BY occurence DESC
                LIMIT 100",
                ARRAY_A
            );

            $matched = array();

            foreach ( $clicks as $click ) {
                foreach ( $opens as $open ) {
                    if ( $click['email_id'] == $open['email_id'] ) {
                        $matched[] = array(
                            'email_id'  => $click['email_id'],
                            'clicks'    => $click['occurence'],
                            'opens'     => $open['occurence'],
                            'ctor'      => round( $click['occurence'] / $open['occurence'] * 100, 1 )
                        );
                        continue 2;
                    }
                }
            }

            if ( !empty( $matched ) ) {
                $ctor = array();
                foreach ( $matched as $key => $match ) {
                    $ctor[ $key ] = $match['ctor'];
                }

                array_multisort( $ctor, SORT_DESC, $matched );

                foreach ( $matched as $match ) {
                    $email = new FUE_Email( $match['email_id'] );

                    $item = array(
                        $email->name,
                        absint( $match['opens'] ), // opens
                        absint( $match['clicks'] ), // clicks
                        absint( $match['ctor'] ) // ctor
                    );

                    $top[] = $item;

                    if ( count( $top ) >= $length ) {
                        break;
                    }

                }

            }
        }

        if ( !defined('FUE_DEBUG') ) {
            // store for 10 minutes
            set_transient( 'fue_top_emails_'. $event, $top, 600 );
        }

        return $top;
    }

    /**
     * Check if sending of usage data is disabled for this site
     *
     * @return bool
     */
    private static function is_sending_usage_data_disabled() {
        $disabled = get_option('fue_disable_usage_data', false);

        if ( $disabled == 1 )
            return true;

        return false;
    }

    /**
     * Get this site's unique ID. The ID is simply an MD5 hash of its URL
     *
     * @return string
     */
    private static function get_site_id() {
        $site_id = get_option( 'fue_site_id', false );

        if (! $site_id ) {
            $site_id = md5(get_bloginfo('url'));

            update_option( 'fue_site_id', $site_id );
        }

        return $site_id;
    }

    /**
     * Get the stored auth token, or a new one from the server if no auth token exists
     *
     * @param bool $new Pass true to force the fetching of a new auth token
     * @return string
     */
    private static function get_auth_token($new = false) {
        $token = false;

        if (! $new )
            $token = get_option( 'fue_auth_token', false );

        if (! $token ) {
            $resp = self::api_call( 'get_token', array('site_id' => self::get_site_id()) );

            if ( isset($resp->token) ) {
                $token = $resp->token;
                update_option( 'fue_auth_token', $token );
            }

        }

        return $token;
    }

    /**
     * Perform an API call to the server
     *
     * @param $action
     * @param $data
     * @param bool $token
     *
     * @return mixed
     */
    private static function api_call( $action, $data, $token = false ) {

        if ( $token )
            $data['_token'] = $token;

        $body       = json_encode($data);
        $key        = $GLOBALS['fue_key'];

        $resp = wp_remote_post( $key, array( 'body' => array('action' => $action, 'data' => $body) ) );

        if ( is_wp_error($resp) ) {
            $response = array(
                'error' => $resp->get_error_message()
            );
            return $response;
        } else {
            return json_decode( $resp['body'] );
        }

    }

}

$GLOBALS['fue_reports'] = new FUE_Reports();
