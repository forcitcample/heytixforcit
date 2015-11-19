<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * FUE_AJAX
 *
 * AJAX Event Handler
 */
class FUE_AJAX {

    /**
     * Hook in methods
     */
    public static function init() {

        // fue_EVENT => nopriv
        $ajax_events = array(
            'send_test_email'           => false,
            'clone_email'               => false,
            'get_post_custom_fields'    => false,
            'search_for_email'          => false,
            'find_similar_emails'       => false,
            'toggle_email_status'       => false,
            'toggle_queue_status'       => false,
            'archive_email'             => false,
            'unarchive_email'           => false,
            'update_email'              => false,
            'update_email_type'         => false,
            'get_email_variables_list'  => false,
            'get_email_details_html'    => false,
            'get_email_test_html'       => false,

            // send manual emails
            'send_manual_emails'    => false,

            // daily summary posts
            'count_daily_summary_posts' => false,
            'delete_daily_summary'      => false,

            // conversion to action-scheduler
            'scheduler_count_import_rows'      => false,
            'scheduler_do_import'              => false,
            'scheduler_import_start'           => false,
            'scheduler_import_complete'        => false,

            // woocommerce
            'wc_json_search_subscription_products'  => false,
            'wc_product_has_children'               => false,
            'wc_order_import'                       => false,
            'wc_disable_order_scan'                 => false,

            // sensei
            'sensei_search_courses'         => false,
            'sensei_search_lessons'         => false,
            'sensei_search_quizzes'         => false,
            'sensei_search_questions'       => false,

            // wootickets
            'wc_json_search_ticket_products'    => false,
        );

        foreach ( $ajax_events as $ajax_event => $nopriv ) {
            add_action( 'wp_ajax_fue_' . $ajax_event, array( __CLASS__, $ajax_event ) );

            if ( $nopriv ) {
                add_action( 'wp_ajax_nopriv_fue_' . $ajax_event, array( __CLASS__, $ajax_event ) );
            }
        }

    }

    /**
     * AJAX send test email
     */
    public static function send_test_email() {
        $_POST = array_map('stripslashes_deep', $_POST);

        $id         = absint( $_POST['id'] );
        $recipient  = sanitize_email( $_POST['email'] );
        $email      = new FUE_Email( $id );
        $subject    = (isset($_POST['subject'])) ? $_POST['subject'] : $email->subject;
        $message    = $_POST['message'];
        $order_id   = (isset($_POST['order_id'])) ? $_POST['order_id'] : '';
        $product_id = (isset($_POST['product_id'])) ? $_POST['product_id'] : '';

        $email_data = array(
            'test'          => true,
            'username'      => 'jdoe',
            'first_name'    => 'John',
            'last_name'     => 'Doe',
            'cname'         => 'John Doe',
            'user_id'       => '0',
            'order_id'      => $order_id,
            'product_id'    => $product_id,
            'email_to'      => $recipient,
            'tracking_code' => $email->tracking_code,
            'store_url'     => home_url(),
            'store_url_secure' => home_url( null, 'https' ),
            'store_name'    => get_bloginfo('name'),
            'unsubscribe'   => fue_get_unsubscribe_url(),
            'subject'       => $subject,
            'message'       => $message,
            'meta'          => array()
        );

        Follow_Up_Emails::instance()->mailer->send_test_email( $email_data, $email );
    }

    /**
     * AJAX handler for cloning an email
     */
    public static function clone_email() {
        $id     = $_POST['id'];
        $name   = $_POST['name'];

        $new_email_id = fue_clone_email($id, $name);

        // set status to inactive
        $email = new FUE_Email( $new_email_id );
        $email->update_status( FUE_Email::STATUS_INACTIVE );

        if (! is_wp_error($new_email_id)) {
            $resp = array(
                'status'    => 'OK',
                'id'        => $new_email_id,
                'url'       => 'post.php?post='. $new_email_id .'&action=edit'
            );
        } else {
            $resp = array(
                'status'    => 'ERROR',
                'message'   => $new_email_id->get_error_message()
            );
        }

        die(json_encode($resp));
    }

    /**
     * AJAX handler for getting all custom fields for a particular post.
     *
     * The resulting data is JSON-encoded before being sent to the browser
     */
    public static function get_post_custom_fields() {
        $id     = isset($_POST['id']) ? $_POST['id'] : 0;
        $meta   = get_post_custom($id);
        die(json_encode($meta));
    }

    /**
     * AJAX handler for searching for existing email addresses
     *
     * This method looks for partial user_email and/or display_name matches,
     * as well as partial first_name and last_name matches. The results are
     * formatted as an array of unique customer keys with values being formed as:
     *
     *     first_name last_name <user_email>
     *
     * The resulting array is then JSON-encoded before it is sent back
     *
     */
    public static function search_for_email() {
        global $wpdb;
        $term       = stripslashes($_GET['term']);
        $results    = array();
        $all_emails = array();

        // Registered users
        $email_term = $term .'%';
        $name_term  = '%'. $term .'%';

        $email_results = $wpdb->get_results( $wpdb->prepare("
            SELECT DISTINCT u.ID, u.display_name, u.user_email
            FROM {$wpdb->prefix}users u
            WHERE (
                `user_email` LIKE %s OR display_name LIKE %s
            )
            ", $email_term, $name_term) );

        if ( $email_results ) {
            foreach ( $email_results as $result ) {
                $all_emails[] = $result->user_email;

                $first_name = get_user_meta( $result->ID, 'billing_first_name', true );
                $last_name  = get_user_meta( $result->ID, 'billing_last_name', true );

                if ( empty($first_name) && empty($last_name) ) {
                    $first_name = $result->display_name;
                }

                $key = $result->ID .'|'. $result->user_email .'|'. $first_name .' '. $last_name;

                $results[$key] = $first_name .' '. $last_name .' &lt;'. $result->user_email .'&gt;';
            }
        }

        // Full name (First Last format)
        $name_results = $wpdb->get_results("
            SELECT DISTINCT m1.user_id, u.user_email, m1.meta_value AS first_name, m2.meta_value AS last_name
            FROM {$wpdb->prefix}users u, {$wpdb->prefix}usermeta m1, {$wpdb->prefix}usermeta m2
            WHERE u.ID = m1.user_id
            AND m1.user_id = m2.user_id
            AND m1.meta_key =  'first_name'
            AND m2.meta_key =  'last_name'
            AND CONCAT_WS(  ' ', m1.meta_value, m2.meta_value ) LIKE  '%{$term}%'
        ");

        if ( $name_results ) {
            foreach ( $name_results as $result ) {
                if ( in_array($result->user_email, $all_emails) ) continue;

                $all_emails[] = $result->user_email;

                $key = $result->user_id .'|'. $result->user_email .'|'. $result->first_name .' '. $result->last_name;

                $results[$key] = $result->first_name .' '. $result->last_name .' &lt;'. $result->user_email .'&gt;';
            }
        }

        $results = apply_filters( 'fue_email_query', $results, $term, $all_emails );

        die(json_encode($results));
    }

    /**
     * Looks for duplicate and similar emails based on different parameters.
     *
     * An email is considered to be a duplicate when the duration, interval type,
     * interval period, always send setting, and email type are exactly the same.
     * A similar email will have the same properties as the duplicate email except
     * for the interval period. Uses @see FUE_Email::has_duplicate_email() and
     * @see FUE_Email::has_similar_email()
     *
     */
    public static function find_similar_emails() {
        $id             = isset($_POST['id']) ? $_POST['id'] : false;
        $type           = $_POST['type'];
        $interval       = (int)$_POST['interval'];
        $duration       = $_POST['interval_duration'];
        $interval_type  = $_POST['interval_type'];
        $product        = (isset($_POST['product_id'])) ? $_POST['product_id'] : 0;
        $category       = (isset($_POST['category_id'])) ? $_POST['category_id'] : 0;
        $always_send    = (isset($_POST['always_send'])) ? $_POST['always_send'] : 0;

        // skip manual emails
        if ( $type == 'manual' )
            die('');

        if ( $id ) {
            $email = new FUE_Email( $id );
        } else {
            $email = new FUE_Email();
        }

        $email->type                = $type;
        $email->interval_num        = $interval;
        $email->interval_duration   = $duration;
        $email->interval_type       = $interval_type;
        $email->always_send         = $always_send;
        $email->product_id          = $product;
        $email->category_id         = $category;

        if ( $email->has_duplicate_email() )
            die("DUPE");

        if ( $email->has_similar_email() )
            die("SIMILAR");

    }

    /**
     * AJAX handler for toggling and email's status
     */
    public static function toggle_email_status() {
        $id     = $_POST['id'];
        $email  = new FUE_Email( $id );
        $status = $email->status;
        $resp   = array('ack' => 'OK');

        if ($status == FUE_Email::STATUS_INACTIVE || $status == FUE_Email::STATUS_ARCHIVED) {
            // activate
            $email->update_status( FUE_Email::STATUS_ACTIVE );
            $resp['new_status'] = __('Active', 'follow_up_emails');
            $resp['new_action'] = __('Deactivate', 'follow_up_emails');
        } else {
            // deactivate
            $email->update_status( FUE_Email::STATUS_INACTIVE );
            $resp['new_status'] = __('Inactive', 'follow_up_emails');
            $resp['new_action'] = __('Activate', 'follow_up_emails');
        }

        die(json_encode($resp));
    }

    /**
     * AJAX handler for toggling an email queue's status
     */
    public static function toggle_queue_status() {
        global $wpdb;
        $id     = $_POST['id'];
        $status = $wpdb->get_var( $wpdb->prepare("SELECT status FROM {$wpdb->prefix}followup_email_orders WHERE id = %d", $id) );
        $resp   = array('ack' => 'OK');

        if ($status == 0) {
            // activate
            $wpdb->update($wpdb->prefix .'followup_email_orders', array('status' => 1), array('id' => $id));

            // re-create the task
            $param = array(
                'email_order_id'    => $id
            );
            $send_time = $wpdb->get_var( $wpdb->prepare("SELECT send_on FROM {$wpdb->prefix}followup_email_orders WHERE id = %d", $id) );
            wc_schedule_single_action( $send_time, 'sfn_followup_emails', $param, 'fue' );

            $resp['new_status'] = __('Queued', 'follow_up_emails');
            $resp['new_action'] = __('Do not send', 'follow_up_emails');
        } else {
            // deactivate
            $wpdb->update($wpdb->prefix .'followup_email_orders', array('status' => 0), array('id' => $id));

            // if using action-scheduler, delete the task
            $param = array(
                'email_order_id'    => $id
            );
            wc_unschedule_action( 'sfn_followup_emails',  $param, 'fue' );

            $resp['new_status'] = __('Suspended', 'follow_up_emails');
            $resp['new_action'] = __('Re-enable', 'follow_up_emails');
        }

        die(json_encode($resp));
    }

    /**
     * AJAX handler for archiving an email
     */
    public static function archive_email() {
        $id     = $_POST['id'];
        $email  = new FUE_Email( $id );
        $resp   = array('ack' => 'OK');
        $type   = $email->get_type();

        // deactivate
        $email->update_status( FUE_Email::STATUS_ARCHIVED );

        $resp['status_html'] = __('Archived', 'follow_up_emails') .'<br/><small><a href="#" class="unarchive" data-id="'. $id .'" data-key="'. $type .'">'. __('Activate', 'follow_up_emails') .'</a></small>';

        die(json_encode($resp));
    }

    /**
     * AJAX handler for unarchiving an email
     */
    public static function unarchive_email() {

        $id     = $_POST['id'];
        $email  = new FUE_Email( $id );
        $resp   = array('ack' => 'OK');

        // activate
        $email->update_status( FUE_Email::STATUS_ACTIVE );

        $resp['status_html'] = __('Active', 'follow_up_emails') .'<br/><small><a href="#" class="toggle-activation" data-id="'. $id .'">'. __('Deactivate', 'follow_up_emails') .'</a></small>
        |
        <small><a href="#" class="archive-email" data-id="'. $id .'" data-key="'. $email->get_type() .'">'. __('Archive', 'follow_up_emails') .'</a></small>';


        die(json_encode($resp));
    }

    /**
     * Action that fires when the email updated from the email form
     */
    public static function update_email() {
        $id     = absint($_POST['id']);
        $email  = new FUE_Email( $id );

        if ( $email->exists() ) {
            $args = array(
                'ID'    => $id
            );

            if ( isset( $_POST['product_id'] ) ) {
                $args['product_id'] = $_POST['product_id'];
            }

            if ( isset( $_POST['category_id'] ) ) {
                $args['category_id'] = $_POST['category_id'];
            }

            if ( isset( $_POST['meta'] ) ) {
                $args['meta'] = $_POST['meta'];
            }

            if ( isset( $_POST['template'] ) ) {
                $args['template'] = $_POST['template'];
            }

            fue_update_email( $args );
        }

        $updated_email = new FUE_Email( $id );

        if ( $updated_email->product_id > 0 ) {
            $updated_email->has_variations = (!empty($updated_email->product_id) && FUE_Addon_Woocommerce::product_has_children($updated_email->product_id)) ? true : false;
        }

        self::send_response( array('status' => 'success', 'email' => $updated_email ) );

    }

    /**
     * Action that fires when the email type is changed in the email form
     */
    public static function update_email_type() {
        $id     = absint($_POST['id']);
        $email  = new FUE_Email( $id );

        if ( $email->exists() ) {
            $args = array(
                'ID'    => $id,
                'type'  => $_POST['type']
            );
            fue_update_email( $args );
        }

        self::send_response( array('status' => 'success') );

    }

    /**
     * Refresh the email variables list based on the email type
     */
    public static function get_email_variables_list() {
        $id     = absint( $_GET['id'] );
        $email  = new FUE_Email( $id );

        ob_start();
        include FUE_TEMPLATES_DIR .'/meta-boxes/email-variables.php';
        $html = ob_get_clean();

        self::send_response( array(
            'status'    => 'success',
            'html'      => $html
        ) );
    }

    /**
     * Load the HTML for the Email Details metabox
     */
    public static function get_email_details_html() {
        $id     = absint( $_GET['id'] );
        $email  = new FUE_Email( $id );

        ob_start();
        include FUE_TEMPLATES_DIR .'/meta-boxes/email-details.php';
        $html = ob_get_clean();

        self::send_response( array(
            'status'    => 'success',
            'html'      => $html
        ) );
    }

    /**
     * Load the HTML for the Email Test metabox
     */
    public static function get_email_test_html() {
        $id     = absint( $_GET['id'] );
        $post   = get_post( $id );

        ob_start();
        FUE_Meta_Boxes::email_test_view( $post );
        $html = ob_get_clean();

        self::send_response( array(
            'status'    => 'success',
            'html'      => $html
        ) );
    }

    /**
     * Send manual emails in batches
     */
    public static function send_manual_emails() {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        if ( empty( $_POST['cmd'] ) ) {
            self::send_response( array( 'error' => 'CMD is missing' ) );
        }

        $cmd    = $_POST['cmd'];
        $key    = !empty($_POST['key']) ? $_POST['key'] : '';
        $data   = get_transient( 'fue_manual_email_'. $key );

        if ( !$data ) {
            self::send_response( array( 'error' => 'Data could not be loaded' ) );
        }

        if ( $cmd == 'start' ) {

            self::send_response( array( 'total_emails' => count( $data['recipients'] ) ) );

        } else {

            $recipients = $data['recipients'];

            // the number of emails to send in this batch
            $length     = round( count($recipients) * .10 );

            if ( $length < 10 ) {
                $length = 10;
            } elseif ( $length > 50 ) {
                $length = 50;
            }

            $recipients_part    = array_splice( $recipients, 0, $length );
            $args               = $data;
            $args['recipients'] = $recipients_part;

            $queue_ids = FUE_Sending_Scheduler::queue_manual_emails( $args );
            $send_data = array();

            foreach ( $queue_ids as $queue_id ) {
                $queue_item     = new FUE_Sending_Queue_Item( $queue_id );
                $sending_result = Follow_Up_Emails::instance()->mailer->send_queue_item( $queue_item );

                if ( is_wp_error( $sending_result ) ) {
                    $send_data[] = array(
                        'status'    => 'error',
                        'email'     => $queue_item->user_email,
                        'error'     => $sending_result->get_error_message()
                    );
                } else {
                    $send_data[] = array(
                        'status'    => 'success',
                        'email'     => $queue_item->user_email
                    );
                }
            }

            $status = count($recipients) > 0 ? 'partial' : 'completed';

            if ( $status == 'completed' ) {
                delete_transient( 'fue_manual_email_'. $key );
            } else {
                // save the modified data
                $data['recipients'] = $recipients;
                set_transient( 'fue_manual_email_'. $key, $data, 86400 );
            }

            self::send_response( array(
                'status'    => $status,
                'data'      => $send_data
            ) );

        }

    }

    /**
     * Count the number of daily summary posts
     */
    public static function count_daily_summary_posts( $return = false ) {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_title = 'fue_send_summary'");

        if ( !$return ) {
            self::send_response( array(
                'status'    => 'success',
                'count'     => $count
            ) );
        }

        return $count;

    }

    /**
     * Delete daily summary posts
     */
    public static function delete_daily_summary() {
        // suppress errors
        ob_start();

        $count = self::count_daily_summary_posts( true );

        if ( $count == 0 ) {
            self::send_response( array(
                'status'    => 'success',
                'count'     => $count
            ) );
        }

        // figure out the number of posts to delete depending on the total rows found
        // 10% of the total rows, min of 50 and max of 100 rows per run
        $limit = round($count * .10);

        if ( $limit > 100 ) {
            $limit = 100;
        }

        if ( $limit < 50 ) {
            $limit = 50;
        }

        $wpdb = Follow_Up_Emails::instance()->wpdb;

        // Delete action scheduler data
        $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_title = 'fue_send_summary' LIMIT 0,$limit" );
        $wpdb->query( "DELETE FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE wp.ID IS NULL;" );
        ob_clean();

        self::count_daily_summary_posts();
    }

    /**
     * Count the number of rows to be imported into Action Scheduler.
     * Only loads orders that have not been sent yet.
     */
    public static function scheduler_count_import_rows() {
        global $wpdb;

        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}followup_email_orders WHERE is_sent = 0");

        die( json_encode(array('total' => $count)) );
    }

    /**
     * AJAX handler to start email import
     */
    public static function scheduler_do_import() {
        $next = $_POST['next'];

        $next   = FUE_Sending_Scheduler::action_scheduler_import($next, 50);
        $usage  = memory_get_usage(true);
        $limit  = ini_get('memory_limit');

        if ($usage < 1024)
            $usage = $usage." bytes";
        elseif ($usage < 1048576)
            $usage = round($usage/1024,2)." kilobytes";
        else
            $usage = round($usage/1048576,2)." megabytes";

        die( json_encode(array('next' => $next, 'usage' => $usage, 'limit' => $limit)) );
    }

    /**
     * AJAX handler to start import process
     */
    public static function scheduler_import_start() {
        // disable email sending for a maximum of 1 hour
        // while importing all records
        set_transient( 'fue_importing', true, 3600 );
    }

    /**
     * AJAX handler to complete the importing process
     */
    public static function scheduler_import_complete() {
        global $wpdb;

        // use the action scheduler system
        update_option( 'fue_scheduling_system', 'action-scheduler' );

        // convert all scheduled events to use action-scheduler
        wp_clear_scheduled_hook('sfn_followup_emails');
        wp_clear_scheduled_hook('fue_send_summary');
        wp_clear_scheduled_hook('sfn_optimize_tables');

        // done importing
        delete_transient( 'fue_importing' );
    }

    /**
     * Search for products and return a JSON-encoded string of results
     * using $_GET['term'] as the search term
     */
    public static function wc_json_search_subscription_products() {
        check_ajax_referer( 'search-products', 'security' );

        header( 'Content-Type: application/json; charset=utf-8' );

        $term       = (string) wc_clean( stripslashes( $_GET['term'] ) );
        $post_types = array('product', 'product_variation');

        if (empty($term)) die();

        if ( is_numeric( $term ) ) {

            $args = array(
                'post_type'			=> $post_types,
                'post_status'	 	=> 'publish',
                'posts_per_page' 	=> -1,
                'post__in' 			=> array(0, $term),
                'fields'			=> 'ids'
            );

            $args2 = array(
                'post_type'			=> $post_types,
                'post_status'	 	=> 'publish',
                'posts_per_page' 	=> -1,
                'post_parent' 		=> $term,
                'fields'			=> 'ids'
            );

            $args3 = array(
                'post_type'			=> $post_types,
                'post_status' 		=> 'publish',
                'posts_per_page' 	=> -1,
                'meta_query' 		=> array(
                    array(
                        'key' 	=> '_sku',
                        'value' => $term,
                        'compare' => 'LIKE'
                    )
                ),
                'fields'			=> 'ids'
            );

            $posts = array_unique(array_merge( get_posts( $args ), get_posts( $args2 ), get_posts( $args3 ) ));

        } else {

            $args = array(
                'post_type'			=> $post_types,
                'post_status' 		=> 'publish',
                'posts_per_page' 	=> -1,
                's' 				=> $term,
                'fields'			=> 'ids'
            );

            $args2 = array(
                'post_type'			=> $post_types,
                'post_status' 		=> 'publish',
                'posts_per_page' 	=> -1,
                'meta_query' 		=> array(
                    array(
                        'key' 	=> '_sku',
                        'value' => $term,
                        'compare' => 'LIKE'
                    )
                ),
                'fields'			=> 'ids'
            );

            $posts = array_unique(array_merge( get_posts( $args ), get_posts( $args2 ) ));

        }

        $found_products = array();

        if ( $posts ) foreach ( $posts as $post ) {

            $product = get_product( $post );

            if ( WC_Subscriptions_Product::is_subscription( $product ) )
                $found_products[ $post ] = $product->get_formatted_name();

        }

        $found_products = apply_filters( 'woocommerce_json_search_found_products', $found_products );

        echo json_encode( $found_products );

        die();
    }

    /**
     * AJAX - check if the given product has any children (variation product)
     */
    public static function wc_product_has_children() {
        $id = $_REQUEST['product_id'];

        if ( FUE_Addon_Woocommerce::product_has_children($id) ) {
            echo 1;
        } else {
            echo 0;
        }
        exit;
    }

    /**
     * AJAX sensei_search_courses() function using $_GET['term'] for the search term
     */
    public static function sensei_search_courses() {
        ob_start();

        check_ajax_referer( 'search-courses', 'security' );

        $term = (string) sanitize_text_field( stripslashes( $_GET['term'] ) );

        if ( empty( $term ) ) {
            die();
        }

        $args = array(
            'post_type'      => 'course',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            's'              => $term,
            'fields'         => 'ids'
        );

        $posts = get_posts( $args );

        $found_products = array();

        if ( $posts ) {
            foreach ( $posts as $post ) {
                $found_products[ $post ] = get_the_title( $post );
            }
        }

        wp_send_json( $found_products );
    }

    /**
     * AJAX sensei_search_lessons() function using $_GET['term'] for the search term
     */
    public static function sensei_search_lessons() {
        ob_start();

        check_ajax_referer( 'search-lessons', 'security' );

        $term = (string) sanitize_text_field( stripslashes( $_GET['term'] ) );

        if ( empty( $term ) ) {
            die();
        }

        $args = array(
            'post_type'      => 'lesson',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            's'              => $term,
            'fields'         => 'ids'
        );

        $posts = get_posts( $args );

        $found_products = array();

        if ( $posts ) {
            foreach ( $posts as $post ) {
                $found_products[ $post ] = get_the_title( $post );
            }
        }

        wp_send_json( $found_products );
    }

    /**
     * AJAX sensei_search_quizzes() function using $_GET['term'] for the search term
     */
    public static function sensei_search_quizzes() {
        ob_start();

        check_ajax_referer( 'search-quizzes', 'security' );

        $term = (string) sanitize_text_field( stripslashes( $_GET['term'] ) );

        if ( empty( $term ) ) {
            die();
        }

        $args = array(
            'post_type'      => 'quiz',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            's'              => $term,
            'fields'         => 'ids'
        );

        $posts = get_posts( $args );

        $found_products = array();

        if ( $posts ) {
            foreach ( $posts as $post ) {
                $found_products[ $post ] = get_the_title( $post );
            }
        }

        wp_send_json( $found_products );
    }

    /**
     * AJAX sensei_search_questions() function using $_GET['term'] for the search term
     */
    public static function sensei_search_questions() {
        ob_start();

        check_ajax_referer( 'search-questions', 'security' );

        $term = (string) sanitize_text_field( stripslashes( $_GET['term'] ) );

        if ( empty( $term ) ) {
            die();
        }

        $args = array(
            'post_type'      => 'question',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            's'              => $term,
            'fields'         => 'ids'
        );

        $posts = get_posts( $args );

        $found_products = array();

        if ( $posts ) {
            foreach ( $posts as $post ) {
                $found_products[ $post ] = get_the_title( $post );
            }
        }

        wp_send_json( $found_products );
    }

    /**
     * Search for products and echo results as JSON. Uses $_GET['term'] as the search term.
     */
    public static function wc_json_search_ticket_products() {

        check_ajax_referer( 'search-products', 'security' );

        header( 'Content-Type: application/json; charset=utf-8' );

        $term       = (string) wc_clean( stripslashes( $_GET['term'] ) );
        $post_types = array('product', 'product_variation');

        if (empty($term)) die();

        if ( is_numeric( $term ) ) {

            $args = array(
                'post_type'			=> $post_types,
                'post_status'	 	=> 'publish',
                'posts_per_page' 	=> -1,
                'post__in' 			=> array(0, $term),
                'fields'			=> 'ids'
            );

            $args2 = array(
                'post_type'			=> $post_types,
                'post_status'	 	=> 'publish',
                'posts_per_page' 	=> -1,
                'post_parent' 		=> $term,
                'fields'			=> 'ids'
            );

            $args3 = array(
                'post_type'			=> $post_types,
                'post_status' 		=> 'publish',
                'posts_per_page' 	=> -1,
                'meta_query' 		=> array(
                    array(
                        'key' 	=> '_sku',
                        'value' => $term,
                        'compare' => 'LIKE'
                    )
                ),
                'fields'			=> 'ids'
            );

            $posts = array_unique(array_merge( get_posts( $args ), get_posts( $args2 ), get_posts( $args3 ) ));

        } else {

            $args = array(
                'post_type'			=> $post_types,
                'post_status' 		=> 'publish',
                'posts_per_page' 	=> -1,
                's' 				=> $term,
                'fields'			=> 'ids'
            );

            $args2 = array(
                'post_type'			=> $post_types,
                'post_status' 		=> 'publish',
                'posts_per_page' 	=> -1,
                'meta_query' 		=> array(
                    array(
                        'key' 	=> '_sku',
                        'value' => $term,
                        'compare' => 'LIKE'
                    )
                ),
                'fields'			=> 'ids'
            );

            $posts = array_unique(array_merge( get_posts( $args ), get_posts( $args2 ) ));

        }

        $found_products = array();

        if ( $posts ) foreach ( $posts as $post ) {

            $event_id = get_post_meta( $post, '_tribe_wooticket_for_event', true );

            if ( $event_id && $event_id > 0 ) {
                $product = get_product( $post );
                $found_products[ $post ] = $product->get_formatted_name();
            }


        }

        $found_products = apply_filters( 'woocommerce_json_search_found_products', $found_products );

        echo json_encode( $found_products );

        die();
    }

    /**
     * Order importer for WooCommerce. Process 10 orders at a time
     */
    public static function wc_order_import() {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        if ( empty( $_POST['cmd'] ) ) {
            self::send_response( array( 'error' => 'CMD is missing' ) );
        }

        $cmd        = $_POST['cmd'];
        $email_id   = !empty($_POST['email_id']) ? $_POST['email_id'] : '';

        if ( $email_id ) {

            $fue_wc = Follow_Up_Emails::instance()->fue_wc;
            if ( $cmd == 'start' ) {
                if ( is_array( $email_id ) ) {

                    $total_orders = 0;
                    foreach ( $email_id as $id ) {
                        $email          = new FUE_Email( $id );
                        $email_orders   = $fue_wc->count_orders_for_email( $email );
                        $total_orders   += $email_orders;

                        if ( $email_orders == 0 ) {
                            delete_post_meta( $id, '_import_order_flag' );
                        }
                    }

                    self::send_response( array('total_orders' => $total_orders) );

                } else {

                    $email  = new FUE_Email( $email_id );
                    $total_orders = $fue_wc->count_orders_for_email( $email );

                    if ( $total_orders == 0 ) {
                        delete_post_meta( $email_id, '_import_order_flag' );
                    }

                    self::send_response( array('total_orders' => $total_orders) );

                }

            } else {

                $email      = new FUE_Email( $email_id );
                $results    = $fue_wc->import_orders_for_email( $email, 10 );

                if ( $results['status'] == 'completed' ) {
                    delete_post_meta( $email_id, '_import_order_flag' );
                }

                self::send_response( array(
                    'status' => ($results['status'] == 'running') ? 'partial' : 'completed',
                    'import_data' => $results['imported'],
                    'remaining_orders' => $results['remaining_orders']
                ) );

            }

        } else {

            if ( $cmd == 'start' ) {
                $tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}followup_order_items'");

                if ( empty($tables) ) {
                    self::send_response( array( 'error' => 'Database tables are not installed. Please deactivate then reactivate Follow Up Emails') );
                }

                if ( !get_option('fue_orders_imported', false) && !get_transient( 'fue_importing_orders') ) {
                    // First run of the import script. Clear existing data for a fresh start
                    $wpdb->query("DELETE FROM {$wpdb->prefix}followup_order_items");
                    $wpdb->query("DELETE FROM {$wpdb->prefix}followup_customers");
                    $wpdb->query("DELETE FROM {$wpdb->prefix}followup_order_categories");
                    $wpdb->query("DELETE FROM {$wpdb->prefix}followup_customer_orders");
                }

                set_transient( 'fue_importing_orders', true, 86400 );

                $sql = "SELECT COUNT( DISTINCT p.id )
                FROM {$wpdb->posts} p, {$wpdb->postmeta} pm
                WHERE p.ID = pm.post_id
                AND p.post_type = 'shop_order'
                AND (SELECT COUNT(*) FROM {$wpdb->postmeta} pm2 WHERE p.ID = pm2.post_id AND pm2.meta_key = '_fue_recorded') = 0";

                $total_orders = $wpdb->get_var( $sql );

                if ( $total_orders == 0 ) {
                    update_option( 'fue_orders_imported', true );
                    delete_transient('fue_importing_orders');
                }

                self::send_response( array('total_orders' => $total_orders) );
            } else {
                $sql = "SELECT DISTINCT p.ID
                FROM {$wpdb->posts} p, {$wpdb->postmeta} pm
                WHERE p.ID = pm.post_id
                AND p.post_type = 'shop_order'
                AND (SELECT COUNT(*) FROM {$wpdb->postmeta} pm2 WHERE p.ID = pm2.post_id AND pm2.meta_key = '_fue_recorded') = 0
                LIMIT 1";

                $results = $wpdb->get_results( $sql );

                if ( count($results) == 0 ) {
                    update_option( 'fue_orders_imported', true );
                    delete_transient('fue_importing_orders');

                    self::send_response( array('status' => 'completed') );
                } else {
                    $imported = array();
                    foreach ( $results as $row ) {
                        $order = WC_FUE_Compatibility::wc_get_order( $row->ID );
                        FUE_Addon_Woocommerce::record_order( $order );
                        $imported[] = array(
                            'id'        => $row->ID,
                            'status'    => 'success'
                        );
                    }

                    self::send_response( array('status' => 'partial', 'import_data' => $imported) );
                }
            }

        }


    }

    /**
     * Set a flag in the DB to stop displaying the Scan Orders prompt
     */
    public static function wc_disable_order_scan() {
        update_option( 'fue_disable_order_scan', true );
        self::send_response( array(
            'status' => 'success'
        ) );
    }

    /**
     * JSON-encode and output the provided array
     * @param array $array
     */
    private static function send_response( $array ) {
        die( json_encode( $array ) );
    }

}

FUE_AJAX::init();