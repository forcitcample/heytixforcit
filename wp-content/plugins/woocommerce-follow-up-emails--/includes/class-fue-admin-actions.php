<?php

/**
 * FUE_Admin_Actions class
 *
 * Handles the processing of POSTed data through the admin dashboard
 */
class FUE_Admin_Actions {

    /**
     * Process request for sending a manual email
     *
     * @see FUE_Scheduler::queue_manual_emails
     */
    public static function send_manual() {
        $post = array_map('stripslashes_deep', $_POST);

        $send_type  = $post['send_type'];
        $recipients = array(); //format: array(user_id, email_address, name)

        if ( $send_type == 'email' ) {
            $key = '0|'. $post['recipient_email'] .'|';
            $recipients[$key] = array( 0, $post['recipient_email'], '' );
        } elseif ( $send_type == 'subscribers' ) {
            $subscribers = fue_get_subscribers();

            foreach ( $subscribers as $subscriber ) {
                $key = '0|'. $subscriber .'|';
                $recipients[ $key ] = array( 0, $subscriber, '' );
            }
        }

        $recipients = apply_filters( 'fue_manual_email_recipients', $recipients, $post );

        if (! empty($recipients) ) {
            $args = apply_filters( 'fue_manual_email_args', array(
                'email_id'          => $post['id'],
                'recipients'        => $recipients,
                'subject'           => $post['email_subject'],
                'message'           => $post['email_message'],
                'tracking'          => $post['tracking'],
                'schedule_email'    => (isset($post['schedule_email']) && $post['schedule_email'] == 1) ? true : false,
                'schedule_date'     => $post['sending_schedule_date'],
                'schedule_hour'     => $post['sending_schedule_hour'],
                'schedule_minute'   => $post['sending_schedule_minute'],
                'schedule_ampm'     => $post['sending_schedule_ampm'],
                'send_again'        => (isset($post['send_again']) && $post['send_again'] == 1) ? true : false,
                'interval'          => $post['interval'],
                'interval_duration' => $post['interval_duration']
            ), $post );

            // if the number of recipients exceed 50 and the email is set
            // to send immediately, use an AJAX worker to avoid timeouts
            if ( !$args['schedule_email'] && count( $recipients ) > 50 ) {
                $key = $args['email_id'] .'-'. time();
                set_transient( 'fue_manual_email_' . $key, $args, 86400 );

                wp_redirect('admin.php?page=followup-emails&tab=send_manual_emails&key='. $key);
                exit;
            } else {
                // clean up the schedule if enabled
                if ( $args['schedule_email'] ) {
                    if ( $args['schedule_hour'] < 10 ) {
                        $args['schedule_hour'] = '0'. $args['schedule_hour'];
                    }

                    if ( $args['schedule_minute'] < 10 ) {
                        $args['schedule_minute'] = '0'. $args['schedule_minute'];
                    }

                    $args['schedule_ampm'] = strtoupper( $args['schedule_ampm'] );
                }

                FUE_Sending_Scheduler::queue_manual_emails( $args );

                do_action( 'sfn_followup_emails' );
            }


        }

        wp_redirect( 'admin.php?page=followup-emails&manual_sent=1#manual_mails' );
        exit;
    }

    /**
     * Process form submission for creating a new FUE_Email
     */
    public static function process_email_form() {
        $post = array_map( 'stripslashes_deep', $_POST );

        $step   = absint( $post['step'] );
        $id     = ( isset($post['id']) ) ? $post['id'] : '';
        $data   = array();
        $new    = ( empty($id) || (isset($_POST['new']) && $_POST['new'] == 1) ) ? '&new=1' : '';

        if ( $step == 1 ) {
            $data['name']               = $post['name'];
            $data['type']               = $post['email_type'];
        } elseif ( $step == 2 ) {
            $data['always_send']        = isset($post['always_send']) ? $post['always_send'] : 0;
            $data['meta']               = $post['meta'];
            $data['subject']            = $post['email_subject'];
            $data['interval_num']       = $post['interval'];
            $data['interval_duration']  = (isset($post['interval_duration'])) ?$post['interval_duration'] : '';
            $data['interval_type']      = (isset($post['interval_type'])) ? $post['interval_type'] : '';
            $data['send_date']          = $post['send_date'];
            $data['send_date_hour']     = $post['send_date_hour'];
            $data['send_date_minute']   = $post['send_date_minute'];
            $data['tracking_on']        = isset($post['tracking_on']) ? $post['tracking_on'] : 0;
            $data['tracking_code']      = $post['tracking'];
            $data['product_id']         = isset($post['product_id']) ? $post['product_id'] : 0;
            $data['category_id']        = isset($post['category_id']) ? $post['category_id'] : 0;
        } elseif ( $step == 3 ) {
            $data['message']            = $post['email_message'];
            $data['meta']               = $post['meta'];
        }

        // Do not enable new emails until after step 3
        if ( $new  ) {
            if ( $step != 3)
                $data['status'] = FUE_Email::STATUS_INACTIVE;
            else
                $data['status'] = FUE_Email::STATUS_ACTIVE;
        }

        $data = apply_filters( 'fue_pre_save_data', $data, $post );

        if ( !empty( $id ) )
            $data['ID'] = $id;

        $id = fue_save_email( $data );

        // if quick-saving, redirect back to the list
        if ( isset($post['mode']) && $post['mode'] == 'quicksave' ) {
            wp_redirect( 'admin.php?page=followup-emails&updated=1' );
            exit;
        }

        $step++;

        $total_steps = apply_filters('fue_form_total_steps', 3);

        if ( $step > $total_steps ) {
            // process is complete
            $save_type = (empty($new)) ? 'updated' : 'created';
            wp_redirect( 'admin.php?page=followup-emails&'. $save_type .'=1' );
        } else {
            // load next step
            wp_redirect( 'admin.php?page=followup-emails-form&step='. $step .'&id='. $id . $new );
        }

        exit;
    }

    /**
     * Delete an existing Follow-Up Email
     */
    static function delete_email() {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        check_admin_referer( 'delete-email' );

        $id = absint( $_GET['id'] );

        // delete
        $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}followup_email_orders WHERE email_id = %d", $id) );

        wp_delete_post( $id, true );

        do_action('fue_email_deleted', $id);

        wp_redirect('admin.php?page=followup-emails&deleted=true');
        exit;
    }

    /**
     * Process form submission from the email list page
     */
    public static function save_list() {
        if ( !empty( $_POST['update_priorities'] ) ) {
            self::update_priorities();
            $message = __('Follow-up emails updated', 'follow_up_emails');
        } else {
            // look for bulk actions
            $types      = Follow_Up_Emails::instance()->get_email_types();
            $count      = empty( $_POST['chk_emails'] ) ? 0 : count( $_POST['chk_emails'] );
            $action     = '';
            $message    = '';

            if ( empty( $count ) ) {
                $message = __('No emails selected', 'follow_up_emails');
                wp_redirect("admin.php?page=followup-emails&tab=list&updated=1&message=". urlencode($message));
                exit;
            }

            foreach ( $types as $type ) {
                if ( !empty( $_POST['bulk_action_'. $type->id .'_active_button'] ) ) {
                    $action = $_POST['bulk_action_'. $type->id .'_active'];
                    self::execute_bulk_action( $action, $_POST['chk_emails'] );
                    break;
                } elseif ( !empty( $_POST['bulk_action_'. $type->id .'_archived_button'] ) ) {
                    $action = $_POST['bulk_action_'. $type->id .'_archived'];
                    self::execute_bulk_action( $action, $_POST['chk_emails'] );
                    break;
                }
            }

            switch ( $action ) {
                case 'activate':
                case 'unarchive':
                    $message = sprintf( _n('%d email activated', '%d emails activated', $count, 'follow_up_emails'), $count );
                    break;

                case 'deactivate':
                    $message = sprintf( _n('%d email deactivated', '%d emails deactivated', $count, 'follow_up_emails'), $count );
                    break;

                case 'archive':
                    $message = sprintf( _n('%d email archived', '%d emails archived', $count, 'follow_up_emails'), $count );
                    break;

                case 'delete':
                    $message = sprintf( _n('%d email deleted', '%d emails deleted', $count, 'follow_up_emails'), $count );
                    break;
            }
        }

        wp_redirect("admin.php?page=followup-emails&tab=list&updated=1&message=". urlencode($message));
        exit;
    }

    /**
     * Update the priorities the emails are loaded and displayed
     */
    public static function update_priorities() {
        $types = Follow_Up_Emails::get_email_types();

        foreach ( $types as $key => $type ) {
            if ( isset($_POST[$key .'_order']) && !empty($_POST[$key .'_order']) ) {
                foreach ( $_POST[$key .'_order'] as $idx => $email_id ) {
                    $priority = $idx + 1;

                    fue_save_email( array(
                        'id'        => $email_id,
                        'priority'  => $priority
                    ) );

                }
            }
        }


        if ( isset($_POST['bcc']) ) {
            update_option( 'fue_bcc_types', $_POST['bcc'] );
        }

        if ( isset($_POST['from_email']) ) {
            update_option( 'fue_from_email_types', $_POST['from_email'] );
        }

        do_action( 'fue_update_priorities', $_POST );

    }

    /**
     * Execute the requested bulk action on the selected emails
     * @param string    $action
     * @param array     $emails
     */
    public static function execute_bulk_action( $action, $emails ) {

        if ( !is_array( $emails ) || empty( $emails ) ) {
            return;
        }

        foreach ( $emails as $email_id ) {
            $email = new FUE_Email( $email_id );

            switch ($action) {

                case 'activate':
                    $email->update_status( FUE_Email::STATUS_ACTIVE );
                    break;

                case 'deactivate':
                    $email->update_status( FUE_Email::STATUS_INACTIVE );
                    break;

                case 'archive':
                    $email->update_status( FUE_Email::STATUS_ARCHIVED );
                    break;

                case 'unarchive':
                    $email->update_status( FUE_Email::STATUS_ACTIVE );
                    break;

                case 'delete':
                    wp_delete_post( $email_id, true );
                    break;

            }

        }

        do_action( 'fue_execute_bulk_action', $action, $emails );

    }

    /**
     * update_settings method
     */
    static function update_settings() {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        $section    = $_POST['section'];
        $imported   = '';

        if ( $section == 'email' ) {
            // capability
            if ( isset($_POST['roles']) ) {
                $roles      = get_editable_roles();
                $wp_roles   = new WP_Roles();

                foreach ($roles as $key => $role ) {
                    if (in_array($key, $_POST['roles'])) {
                        $wp_roles->add_cap($key, 'manage_follow_up_emails');
                    } else {
                        $wp_roles->remove_cap($key, 'manage_follow_up_emails');
                    }
                }

                // make sure the admin has this capability
                $wp_roles->add_cap('administrator', 'manage_follow_up_emails');
            }

            do_action( 'fue_settings_email_save', $_POST );

        } elseif ( $section == 'crm' ) {
            // bcc
            if ( isset($_POST['bcc']) )
                update_option('fue_bcc', $_POST['bcc']);

            // from/reply-to name
            if ( isset($_POST['from_name']) )
                update_option( 'fue_from_name', $_POST['from_name'] );

            // from/reply-to
            if ( isset($_POST['from_email']) )
                update_option( 'fue_from_email', $_POST['from_email'] );

            // daily summary emails
            if ( isset($_POST['daily_emails']) ) update_option('fue_daily_emails', $_POST['daily_emails'] );

            if ( isset($_POST['daily_emails_time_hour']) ) {
                $previous_time = get_option( 'fue_daily_emails_time', '00:00 AM' );
                $time = $_POST['daily_emails_time_hour'] .':'. $_POST['daily_emails_time_minute'] .' '. $_POST['daily_emails_time_ampm'];

                if ( $previous_time != $time ) {
                    update_option( 'fue_daily_emails_time', $time );

                    Follow_Up_Emails::instance()->scheduler->reschedule_daily_summary_email();
                }

            }

            do_action( 'fue_settings_crm_save', $_POST );
        } elseif ( $section == 'system' ) {
            // process importing request
            if ( isset($_FILES['emails_file']) && is_uploaded_file($_FILES['emails_file']['tmp_name']) ) {
                ini_set("auto_detect_line_endings", true);

                $fh = @fopen( $_FILES['emails_file']['tmp_name'], 'r' );
                $columns = array();
                $i = 0;

                while ( $row = fgetcsv($fh) ) {
                    $i++;

                    if ( $i == 1 ) {
                        foreach ( $row as $idx => $col ) {
                            $columns[$idx] = $col;
                        }

                        continue;
                    }

                    $data = array();

                    foreach ( $columns as $idx => $col ) {

                        if ( $col == 'email_type' ) {
                            $col = 'type';

                            // convert 'product' emails to 'storewide'
                            if ( in_array( $row[ $idx ], array( 'product', 'normal', 'generic' ) ) ) {
                                $row[ $idx ] = 'storewide';
                            }
                        } elseif ( $col == 'status' ) {
                            if ( $row[ $idx ] == -1 ) {
                                $row[ $idx ] = FUE_Email::STATUS_ARCHIVED;
                            } elseif ( $row[ $idx ] == 0 ) {
                                $row[ $idx ] = FUE_Email::STATUS_INACTIVE;
                            } else {
                                $row[ $idx ] = FUE_Email::STATUS_ACTIVE;
                            }
                        }

                        $data[ $col ] = $row[ $idx ];
                    }

                    fue_create_email( $data );

                }

                $imported = '&imported=1';
            }

            // restore settings file from backup
            if ( isset($_FILES['settings_file']) && is_uploaded_file($_FILES['settings_file']['tmp_name']) ) {
                ini_set("auto_detect_line_endings", true);

                $fh         = @fopen( $_FILES['settings_file']['tmp_name'], 'r' );
                $i          = 0;

                while ( $row = fgetcsv($fh) ) {
                    $i++;

                    if ( $i == 1 ) {
                        continue;
                    }

                    update_option( $row[0], $row[1] );

                }

                $imported = '&imported=1';
            }

            // api
            if ( !empty( $_POST['api_enabled'] ) ) {
                update_option( 'fue_api_enabled', 'yes' );
            } else {
                update_option( 'fue_api_enabled', 'no' );
            }

            // usage data
            if ( isset($_POST['disable_usage_data']) && $_POST['disable_usage_data'] == 1 ) {
                update_option( 'fue_disable_usage_data', 1 );
            } else {
                delete_option( 'fue_disable_usage_data' );
            }

            // email batches
            if ( isset($_POST['email_batch_enabled']) && $_POST['email_batch_enabled'] == 1 ) {
                update_option( 'fue_email_batches', 1 );
                update_option( 'fue_emails_per_batch', intval($_POST['emails_per_batch']) );
                update_option( 'fue_batch_interval', intval($_POST['email_batch_interval']) );
            } else {
                update_option( 'fue_email_batches', 0 );
            }

            // disable logging
            if ( isset($_POST['action_scheduler_disable_logging']) && $_POST['action_scheduler_disable_logging'] == 1 ) {
                update_option( 'fue_disable_action_scheduler_logging', 1 );
            } else {
                update_option( 'fue_disable_action_scheduler_logging', 0 );
            }

            // delete all action scheduler comments
            if ( isset($_POST['action_scheduler_delete_logs']) && $_POST['action_scheduler_delete_logs'] == 1 ) {
                $comment_ids = $wpdb->get_col("SELECT comment_ID FROM {$wpdb->comments} WHERE comment_type = 'action_log'");

                if ( $comment_ids ) {
                    foreach ( $comment_ids as $comment_id ) {
                        wp_delete_comment( $comment_id, true );
                    }
                }

            }

            do_action( 'fue_settings_system_save', $_POST );
        } else {
            do_action( 'fue_settings_save', $_POST );
        }

        wp_redirect("admin.php?page=followup-emails-settings&tab=$section&settings_updated=1$imported");
        exit;
    }

    /**
     * Add/Remove emails from the Excluded List
     */
    static function manage_optout() {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        $post       = stripslashes_deep($_POST);

        if ( isset($post['button_add']) && $post['button_add'] == 'Add' ) {
            // add an email address to the excludes list
            $email = $post['email_address'];

            // make sure it is a valid email address
            if ( !is_email($email) ) {
                wp_redirect( 'admin.php?page=followup-emails-optouts&error='. urlencode(__('The email address is invalid', 'follow_up_emails') ) );
                exit;
            }

            fue_exclude_email_address( $email );

            wp_redirect( 'admin.php?page=followup-emails-optouts&added='. urlencode($email) );
            exit;
        } elseif ( isset($post['button_restore']) && $post['button_restore'] == 'Apply' ) {
            $emails     = $post['email'];
            $email_ids  = '';

            if ( is_array($emails) && !empty($emails) ) {
                $email_ids = "'". implode("','", $emails) ."'";
            }

            if (! empty($email_ids) ) {
                $wpdb->query("DELETE FROM {$wpdb->prefix}followup_email_excludes WHERE id IN($email_ids)");
            }

            wp_redirect( 'admin.php?page=followup-emails-optouts&restored='. count($emails) );
            exit;
        }

        wp_redirect( 'admin.php?page=followup-emails-optouts' );
        exit;
    }

    /**
     * Add/Remove emails from the subscribers table
     */
    static function manage_subscribers() {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        $post = stripslashes_deep($_POST);

        if ( !empty($post['button_add']) ) {
            // add an email address to the excludes list
            $email = sanitize_email( $post['email'] );

            $status = fue_add_subscriber( $email );

            if ( is_wp_error( $status ) ) {
                wp_redirect( 'admin.php?page=followup-emails-subscribers&error=' . urlencode( $status->get_error_message() ) );
                exit;
            }

            wp_redirect( 'admin.php?page=followup-emails-subscribers&added=' . urlencode( $email ) );
            exit;
        } elseif ( !empty($post['button_csv']) ) {
            // process importing request
            if ( isset($_FILES['csv']) && is_uploaded_file($_FILES['csv']['tmp_name']) ) {
                ini_set("auto_detect_line_endings", true);

                $fh     = @fopen( $_FILES['csv']['tmp_name'], 'r' );
                $i      = 0;
                $added  = 0;

                while ( $row = fgetcsv($fh) ) {
                    $i++;

                    if ( empty( $row ) || empty( $row[0] ) ) {
                        continue;
                    }

                    $status = fue_add_subscriber( $row[0] );

                    if ( !is_wp_error( $status ) ) {
                        $added++;
                    }

                }

                wp_redirect( 'admin.php?page=followup-emails-subscribers&imported='. $added );
                exit;

            }
        } elseif ( !empty($post['button_delete']) ) {
            $emails     = $post['email'];
            $email_ids  = '';

            if ( is_array($emails) && !empty($emails) ) {
                $email_ids = "'". implode( "','", array_map( 'absint', $emails ) ) ."'";
            }

            if (! empty($email_ids) ) {
                $wpdb->query("DELETE FROM {$wpdb->prefix}followup_subscribers WHERE id IN($email_ids)");
            }

            wp_redirect( 'admin.php?page=followup-emails-subscribers&deleted='. count($emails) );
            exit;
        }

        wp_redirect( 'admin.php?page=followup-emails-optouts' );
        exit;
    }

    /**
     * reset_reports() method
     */
    static function reset_reports() {

        $data = $_POST;

        FUE_Reports::reset($data);

        wp_redirect( 'admin.php?page=followup-emails-reports&cleared=1' );
        exit;

    }

    /**
     * Process queue updates and removal of bulk items from Queue List Table
     */
    public static function process_queue_bulk_action() {
        $current_action = false;

        if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] ) {
            $current_action = $_REQUEST['action2'];
        }

        if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] ) {
            $current_action = $_REQUEST['action'];
        }

        if ( $current_action === false || ! isset( $_GET['_wpnonce'] ) ) {
            return;
        }

        if ( empty( $_GET['queue'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-items' ) ) {
            wp_die( __( 'Bulk edit failed. Invalid Nonce.', 'follow_up_emails' ) );
        }

        $scheduler      = Follow_Up_Emails::instance()->scheduler;
        $items          = $_GET['queue'];

        $messages       = array();
        $error_messages = array();
        $query_args     = array();

        if ( in_array( $current_action, array( 'send', 'activate', 'suspend', 'delete' ) ) ) {

            $item_count     = count( $items );
            $error_count    = 0;
            $scheduler      = Follow_Up_Emails::instance()->scheduler;

            foreach ( $items as $idx => $queue_id ) {
                $item = new FUE_Sending_Queue_Item( $queue_id );

                switch ( $current_action ) {

                    case 'send':
                        $sent = Follow_Up_Emails::instance()->mailer->send_queue_item( $item );
                        if ( is_wp_error( $sent ) ) {
                            $error_messages[] = sprintf( __('Queue #%d: %s', 'follow_up_emails'), $item->id, $sent->get_error_message() );
                        } else {
                            $messages[] = sprintf( __('Queue #%d: Scheduled email sent manually', 'follow_up_emails' ), $item->id );
                        }
                        break;

                    case 'activate':
                        $item->status = 1;
                        $item->save();
                        $scheduler->schedule_email( $queue_id, $item->send_on );
                        break;

                    case 'suspend':
                        $item->status = 0;
                        $item->save();
                        $scheduler->unschedule_email( $queue_id );
                        break;

                    case 'delete':
                        $scheduler->delete_item( $queue_id );
                        break;

                    default :
                        $error_messages[] = __( 'Error: Unknown action.', 'follow_up_emails' );
                        break;

                }

            }

            if ( $item_count > 0 ) {
                switch ( $current_action ) {
                    case 'activate' :
                        $messages[] = sprintf( _n('%d email has been activated', '%s emails have been activated', $item_count, 'follow_up_emails'), $item_count );
                        break;
                    case 'suspend' :
                        $messages[] = sprintf( _n('%d email has been suspended', '%s emails have been suspended', $item_count, 'follow_up_emails'), $item_count );
                        break;
                    case 'deleted' :
                        $messages[] = sprintf( _n('%d email has been deleted', '%s emails have been deleted', $item_count, 'follow_up_emails'), $item_count );
                        break;
                }
            }

            if ( ! empty( $messages ) || ! empty( $error_messages ) ) {
                $message_nonce = wp_create_nonce( __FILE__ );
                set_transient( '_fue_messages_' . $message_nonce, array( 'messages' => $messages, 'error_messages' => $error_messages ), 60 * 60 );
            }

            // Filter by a given customer or product?
            if ( isset( $_GET['_customer_user'] ) || isset( $_GET['_product_id'] ) ) {

                if ( ! empty( $_GET['_customer_user'] ) ) {
                    $user_id = intval( $_GET['_customer_user'] );
                    $user    = get_user_by( 'id', absint( $_GET['_customer_user'] ) );

                    if ( false === $user ) {
                        wp_die( __( 'Action failed. Invalid user ID.', 'follow_up_emails' ) );
                    }

                    $query_args['_customer_user'] = $user_id;
                }

                if ( ! empty( $_GET['_product_id'] ) ) {
                    $product_id = intval( $_GET['_product_id'] );
                    $product    = get_product( $product_id );

                    if ( false === $product ) {
                        wp_die( __( 'Action failed. Invalid product ID.', 'follow_up_emails' ) );
                    }

                    $query_args['_product_id'] = $product_id;
                }

            }

            $query_args['status'] = ( isset( $_GET['status'] ) ) ? $_GET['status'] : 'all';

            if ( ! empty( $messages ) || ! empty( $error_messages ) ) {
                $query_args['message'] = $message_nonce;
            }

            if ( isset( $_GET['paged'] ) ) {
                $query_args['paged'] = $_GET['paged'];
            }

            $search_query = _admin_search_query();

            if ( ! empty( $search_query ) ) {
                $query_args['s'] = $search_query;
            }

            $redirect_to = add_query_arg( $query_args, admin_url( 'admin.php?page=followup-emails-queue' ) );

            // Redirect to avoid performning actions on a page refresh
            wp_safe_redirect( $redirect_to );
            exit;
        }
    }

    /**
     * Change a queue item's status and redirect the browser back to the scheduled emails page after
     */
    public static function update_queue_item_status() {
        check_admin_referer( 'update_queue_status' );

        $scheduler  = Follow_Up_Emails::instance()->scheduler;
        $item       = new FUE_Sending_Queue_Item( absint($_GET['id']) );

        $item->status = absint( $_GET['status'] );
        $item->save();

        if ( $item->status == 1 ) {
            $scheduler->schedule_email( $item->id, $item->send_on );
        } elseif ( $item->status == 0 ) {
            $scheduler->unschedule_email( $item->id );
        }

        $messages       = array( __('Scheduled email updated successfully', 'follow_up_emails' ) );
        $message_nonce  = wp_create_nonce( __FILE__ );
        set_transient( '_fue_messages_' . $message_nonce, array( 'messages' => $messages ), 60 * 60 );

        // redirect back to scheduled emails
        wp_redirect( add_query_arg( 'message', $message_nonce , 'admin.php?page=followup-emails-queue' ) );
        exit;
    }

    /**
     * Delete an item from the queue and redirect back to the scheduled emails page
     */
    public static function delete_queue_item() {
        check_admin_referer( 'delete_queue_item' );

        Follow_Up_Emails::instance()->scheduler->delete_item( absint( $_GET['id'] ) );

        $messages       = array( __('Scheduled email deleted', 'follow_up_emails' ) );
        $message_nonce  = wp_create_nonce( __FILE__ );
        set_transient( '_fue_messages_' . $message_nonce, array( 'messages' => $messages ), 60 * 60 );

        // redirect back to scheduled emails
        wp_redirect( add_query_arg( 'message', $message_nonce , 'admin.php?page=followup-emails-queue' ) );
        exit;

    }

    /**
     * Manually send a specific queue item
     */
    public static function send_queue_item() {
        check_admin_referer( 'send_queue_item' );

        $queue  = new FUE_Sending_Queue_Item( $_GET['id'] );
        $sent   = Follow_Up_Emails::instance()->mailer->send_queue_item( $queue );

        $message_nonce  = wp_create_nonce( __FILE__ );
        $messages       = array( 'messages' => array(), 'error_messages' => array() );

        if ( is_wp_error( $sent ) ) {
            $message = $sent->get_error_message();
            $queue->add_note( $message );
            $messages['error_messages'][] = $message;
        } else {
            $message = __('Scheduled email sent manually', 'follow_up_emails' );
            $queue->add_note( $message );
            $messages['messages'][] = $message;
        }

        set_transient( '_fue_messages_' . $message_nonce, $messages, 60 * 60 );

        // redirect back to scheduled emails
        wp_redirect( add_query_arg( 'message', $message_nonce , 'admin.php?page=followup-emails-queue' ) );
        exit;

    }

    /**
     * Generate and serve the settings in a CSV format
     */
    static function backup_settings() {
        check_admin_referer('fue_backup');

        $contents = '';

        $headers = array('meta_key', 'meta_value');
        $contents .= self::array_to_csv($headers);

        $wpdb = Follow_Up_Emails::instance()->wpdb;

        $options = $wpdb->get_results(
            "SELECT option_name, option_value
            FROM {$wpdb->options}
            WHERE option_name LIKE 'fue%'"
        );

        foreach ( $options as $option ) {
            $row = array( $option->option_name, $option->option_value );
            $contents .= self::array_to_csv($row);
        }

        header('Content-Type: application/csv');
        header('Content-Disposition:attachment;filename=follow_up_settings.csv');
        header('Pragma: no-cache');

        echo $contents;
        exit;
    }

    /**
     * Formats an array into a CSV line
     * @param array $fields
     * @param string $delimiter
     * @param string $enclosure
     * @param bool $encloseAll
     * @param bool $nullToMysqlNull
     *
     * @return string
     */
    private static function array_to_csv( $fields = array(), $delimiter = ',', $enclosure = '"', $encloseAll = false, $nullToMysqlNull = false ) {
        $delimiter_esc = preg_quote($delimiter, '/');
        $enclosure_esc = preg_quote($enclosure, '/');

        $output = array();
        foreach ( $fields as $field ) {
            if ($field === null && $nullToMysqlNull) {
                $output[] = 'NULL';
                continue;
            }

            // Enclose fields containing $delimiter, $enclosure or whitespace
            if ( $encloseAll || preg_match( "/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field ) ) {
                $output[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure;
            }
            else {
                $output[] = $field;
            }
        }

        return implode( $delimiter, $output ) ."\n";
    }

}