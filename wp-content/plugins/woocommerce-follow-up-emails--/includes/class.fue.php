<?php

require_once 'class.fue_link_replacement.php';

class FUE {

    const STATUS_ARCHIVED   = -1;
    const STATUS_INACTIVE   = 0;
    const STATUS_ACTIVE     = 1;

    public static function get_emails($type = 'generic', $status = null) {
        global $wpdb;

        $params = array();

        $sql = "SELECT * FROM {$wpdb->prefix}followup_emails WHERE `email_type` = %s";
        $params[] = $type;

        if ( !is_null($status) ) {

            if ( is_array($status) ) {
                $sql .= " AND `status` IN ( ". implode( ',', esc_sql( $status ) ) ." ) ";
            } else {
                $sql .= " AND `status` = %d ";
                $params[] = $status;
            }

        }

        $sql .= "ORDER BY `priority` ASC, `name` ASC";

        $sql = apply_filters( 'fue_get_emails_query', $wpdb->prepare($sql, $params), $type );

        return $wpdb->get_results( $sql );

    }

    public static function exclude_email_address( $email_address, $email_id = 0 ) {
        global $wpdb;

        $email_name = '-';
        if ( $email_id > 0 )
            $email_name = $wpdb->get_var( $wpdb->prepare("SELECT `name` FROM `{$wpdb->prefix}followup_emails` WHERE `id` = %d", $email_id) );

        $wpdb->query( $wpdb->prepare("INSERT INTO `{$wpdb->prefix}followup_email_excludes` (`email_id`, `email_name`, `email`, `date_added`) VALUES (%d, %s, %s, NOW())", $email_id, $email_name, $email_address) );

        return true;

    }

    public static function save_email( $data, $id = '' ) {
        global $wpdb;

        if ( isset($data['email_type']) ) {

            switch ( $data['email_type'] ) {

                case 'generic':
                case 'customer':
                    $data['product_id']    = 0;
                    $data['category_id']   = 0;
                    break;

                case 'signup':
                    $data['product_id']     = 0;
                    $data['category_id']    = 0;
                    $data['always_send']    = 1;
                    $data['interval_type']  = 'signup';
                    break;

                case 'manual':
                    $data['interval_type']      = 'manual';
                    $data['interval_duration']  = 0;
                    break;

                case 'reminder':
                    $data['always_send']    = 1;
                    break;

            }

        }

        if ( isset($data['tracking_on']) && $data['tracking_on'] == 0 ) {
            $data['tracking_code'] = '';
        }
        unset($data['tracking_on']);

        if ( isset($data['interval_duration']) && $data['interval_duration'] == 'date' ) {
            $data['interval_type'] = 'date';
        }

        if ( isset($data['meta']) ) {
            $data['meta'] = serialize($data['meta']);
        }

        $data = apply_filters('fue_email_pre_save', $data, $id);

        // save coupon last
        $coupon = array(
            'email_id'      => 0,
            'send_coupon'   => (isset($data['send_coupon'])) ? $data['send_coupon'] : 0,
            'coupon_id'     => (isset($data['coupon_id'])) ? $data['coupon_id'] : 0
        );
        unset($data['send_coupon'], $data['coupon_id']);

        if ( empty($id) ) {
            $priority_types = apply_filters('fue_priority_types', array('generic', 'signup', 'manual'));

            if ( in_array($data['email_type'], $priority_types) ) {
                $priority = $wpdb->get_var( $wpdb->prepare("SELECT `priority` FROM {$wpdb->prefix}followup_emails WHERE email_type = %s ORDER BY priority DESC LIMIT 1", $data['email_type']) );
            } elseif (isset($data['product_id']) && $data['product_id'] > 0) {
                $priority = $wpdb->get_var("SELECT `priority` FROM {$wpdb->prefix}followup_emails WHERE email_type = 'normal' AND product_id > 0 ORDER BY priority DESC LIMIT 1" );
            } else {
                $priority = $wpdb->get_var("SELECT `priority` FROM {$wpdb->prefix}followup_emails WHERE email_type = 'normal' AND category_id > 0 ORDER BY priority DESC LIMIT 1");
            }

            if (! $priority)
                $priority = 0;

            $priority++;

            $wpdb->insert( $wpdb->prefix .'followup_emails', $data );
            $id = $wpdb->insert_id;

            // save coupon
            $coupon['email_id'] = $id;
            //$wpdb->insert( $wpdb->prefix .'followup_email_coupons', $coupon );

            do_action('fue_email_created', $id, $data);
        } else {
            // merge the meta field
            if ( isset($data['meta']) ) {
                $data['meta'] = unserialize($data['meta']);

                $meta = $wpdb->get_var( $wpdb->prepare("SELECT meta FROM {$wpdb->prefix}followup_emails WHERE id = %d", $id) );
                $meta = maybe_unserialize( $meta );

                if (! is_array($meta) ) $meta = array();

                $data['meta'] = serialize(array_merge( $meta, $data['meta'] ));
            }

            $wpdb->update( $wpdb->prefix .'followup_emails', $data, array('id' => $id) );

            // save coupon
            unset($coupon['email_id']);
            //$wpdb->update( $wpdb->prefix .'followup_email_coupons', $coupon, array('email_id' => $id) );

            do_action('fue_email_updated', $id, $data);
        }

        return $id;

    }

    public static function create_order_from_signup( $user_id, $triggers ) {
        global $wpdb;

        $user = new WP_User( $user_id );

        if ( is_wp_error($user) ) return;

        $trigger = '';
        foreach ( $triggers as $t ) {
            $trigger .= "'". esc_sql($t) ."',";
        }
        $trigger = rtrim($trigger, ',');

        if ( empty($trigger) ) $trigger = "''";

        $emails = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `email_type` = 'signup' AND `status` = %d", FUE::STATUS_ACTIVE) );

        foreach ( $emails as $email ) {
            // look for dupes
            $count = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}followup_email_orders WHERE email_id = %d AND user_id = %d", $email->id, $user_id) );

            if ($count == 0) {
                FUE::queue_email( array('user_id' => $user_id), $email );
                continue;
            }

        }
    }

    public static function insert_email_order( $data ) {
        global $wpdb;

        $defaults = array(
            'user_id'       => 0,
            'user_email'    => '',
            'order_id'      => 0,
            'product_id'    => 0,
            'email_id'      => '',
            'send_on'       => 0,
            'is_cart'       => 0,
            'is_sent'       => 0,
            'date_sent'     => '',
            'email_trigger' => '',
            'meta'          => ''
        );

        $insert = array_merge( $defaults, $data );

        // get the correct email address
        if ( $insert['user_id'] > 0 ) {
            $user = new WP_User( $insert['user_id'] );
            $insert['user_email'] = $user->user_email;
        }

        $insert = apply_filters( 'fue_insert_email_order', $insert );

        $email_meta     = $wpdb->get_var( $wpdb->prepare("SELECT meta FROM {$wpdb->prefix}followup_emails WHERE id = %d", $insert['email_id'])  );
        $adjust_date    = false;

        if ( !empty($email_meta) ) {
            $email_meta = maybe_unserialize( $email_meta );

            if ( isset($email_meta['adjust_date']) && $email_meta['adjust_date'] == 'yes' ) {
                $adjust_date = true;
            }

            // send email only once
            if ( isset($email_meta['one_time']) && $email_meta['one_time'] == 'yes' ) {
                $count_sent = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}followup_email_orders WHERE `user_email` = %s AND `email_id` = %d", $insert['user_email'], $insert['email_id']) );

                if ( $count_sent > 0 ) {
                    // do not send more of the same emails to this user
                    return 0;
                }
            }
        }

        if ( isset($insert['meta']) && is_array($insert['meta']) ) {
            $insert['meta'] = serialize($insert['meta']);
        }

        if ( $adjust_date ) {
            // check for similar existing and unsent email orders
            // and adjust the date to send instead of inserting a duplicate row
            $sql = "SELECT id FROM {$wpdb->prefix}followup_email_orders
                    WHERE email_id = %d
                    AND user_id = %d
                    AND product_id = %d
                    AND is_cart = %d
                    AND is_sent = 0";
            $similar_email = $wpdb->get_row( $wpdb->prepare($sql, $insert['email_id'], $insert['user_id'], $insert['product_id'], $insert['is_cart']) );

            if ( $similar_email ) {
                $update = array(
                    'send_on'   => $insert['send_on']
                );
                $wpdb->update($wpdb->prefix .'followup_email_orders', $update, array('id' => $similar_email->id));

                // remove the existing schedule and save the new one
                $param = array('email_order_id' => $similar_email->id);

                wc_unschedule_action( 'sfn_followup_emails', $param, 'fue' );

                // because ActionScheduler uses GMT for scheduling events, convert the send date to GMT
                $send_on_gmt = strtotime( get_gmt_from_date( date( 'Y-m-d H:i:s', $insert['send_on'] ) ) );

                wc_schedule_single_action( $send_on_gmt, 'sfn_followup_emails', $param, 'fue' );

                return $similar_email->id;
            } else {
                return self::schedule_email($insert);
            }
        } else {

            return self::schedule_email($insert);

        }

    }

    public static function schedule_email( $data ) {
        global $wpdb;

        $wpdb->insert( $wpdb->prefix .'followup_email_orders', $data );
        $job_id = $wpdb->insert_id;

        $param = array(
            'email_order_id'    => $job_id
        );

        // because ActionScheduler uses GMT for scheduling events, convert the send date to GMT
        $send_on_gmt = strtotime( get_gmt_from_date( date( 'Y-m-d H:i:s', $data['send_on'] ) ) );

        wc_schedule_single_action( $send_on_gmt, 'sfn_followup_emails', $param, 'fue' );

        return $job_id;
    }

    /**
     * Send emails that are in the email queue
     */
    public static function send_emails( $email_order_id = 0 ) {
        global $wpdb, $fue;

        if ( true == get_transient( 'fue_importing' ) )
            return;

        // if $email_order_id is set, send only the specified order
        if ( $email_order_id > 0 ) {
            $email_order = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_email_orders WHERE id = %d AND `is_sent` = 0 AND status = 1", $email_order_id) );

            if ( $email_order )
                self::send_email_order( $email_order );
        } else {

            // get start and end times
            $to         = current_time('timestamp');
            $results    = $wpdb->get_results( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_email_orders` WHERE `is_sent` = 0 AND `send_on` <= %s AND status = 1", $to) );

            foreach ( $results as $email_order ) {
                self::send_email_order( $email_order );
            }

        }

    }

    public static function send_email_order( $email_order ) {
        global $wpdb, $fue;

        if ( !$email_order->email_id || $email_order->email_id == 0 ) {
            $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}followup_email_orders WHERE id = %d", $email_order->id) );
            return;
        }

        // only process unsent email orders
        if ( $email_order->is_sent != 0 )
            return;

        $email = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_emails` WHERE `id` = '%d'", $email_order->email_id) );

        // make sure that the email is not disabled
        if ( $email->email_type != 'manual' && $email->status != FUE::STATUS_ACTIVE )
            return;

        // email cannot be active and have empty subject and message
        if ( empty($email->subject) && empty($email->message) ) {
            // set the status to inactive
            FUE::update_email_status( $email->id, FUE::STATUS_INACTIVE );
            return;
        }

        // if the email order is cron locked, reschedule to avoid duplicate emails being sent
        if ( self::is_email_order_locked( $email_order ) ) {
            self::reschedule_email_order( $email_order );
            return;
        }

        // place a cron lock to prevent duplicates
        self::lock_email_order( $email_order );

        $email_meta = (isset($email->meta) && !empty($email->meta)) ? maybe_unserialize( $email->meta ) : array();
        $email_data = array(
            'username'  => '',
            'user_id'   => '',
            'email_to'  => '',
            'meta'      => array()
        );

        if ( $email_order->user_id == 0 && $email->email_type == 'manual' ) {
            $email_data['username'] = '';
            $email_data['meta']     = maybe_unserialize( $email_order->meta );
            $email_data['email_to'] = $email_data['meta']['email_address'];
            $email_data['order']    = false;
            $email_data['cname']    = '';
        } else {
            $email_data['order']        = false;
            $email_data['user_id']      = $email_order->user_id;

            $wp_user    = new WP_User( $email_order->user_id );

            $email_data['username'] = $wp_user->user_login;

            // use the customer's billing data
            $billing_email      = get_user_meta( $email_order->user_id, 'billing_email', true );
            $billing_first_name = get_user_meta( $email_order->user_id, 'billing_first_name', true );
            $billing_last_name  = get_user_meta( $email_order->user_id, 'billing_last_name', true );

            // if the customer's billing data are empty, fallback to using data from WP_User
            $email_data['email_to']     = (empty($billing_email)) ? $wp_user->user_email : $billing_email;
            $email_data['first_name']   = (empty($billing_first_name)) ? $wp_user->first_name : $billing_first_name;
            $email_data['last_name']    = (empty($billing_last_name)) ? $wp_user->last_name : $billing_last_name;

            // customer's complete name
            $email_data['cname'] = $email_data['first_name'] .' '. $email_data['last_name'];

            // if the name is still empty, last resort is to use the display_name
            if ( empty($email_data['first_name']) && empty($email_data['last_name']) ) {
                $email_data['first_name'] = $wp_user->display_name;
                $email_data['cname']      = $wp_user->display_name;
            }

            // non-order related email. make sure user is not opted-out
            $opt_out = get_user_meta( $email_order->user_id, 'wcfu_opted_out', true );
            $opt_out = apply_filters( 'fue_user_opt_out', $opt_out, $email_order->user_id );

            if ( $opt_out )  {
                // user opted out, delete this email_order
                $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}followup_email_orders WHERE `id` = %d", $email_order->id) );
                return;
            }
        }

        $email_data = apply_filters( 'fue_send_email_data', $email_data, $email_order, $email );

        // check if the email address is on the excludes list
        $sql = $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_excludes` WHERE `email` = '%s'", $email_data['email_to'] );

        if ($wpdb->get_var( $sql ) > 0) {
            // delete and go to the next entry
            do_action( 'fue_email_excluded', $email_data['email_to'], $email_order->id );
            $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}followup_email_orders WHERE `id` = %d", $email_order->id) );
            return;
        }

        // allow other extensions to "skip" sending this email
        $skip = apply_filters( 'fue_skip_email_sending', false, $email, $email_order );

        if ( $skip )
            return;

        // process variable replacements
        $tracking   = $email->tracking_code;
        $codes      = array();
        $campaigns  = array();

        if ( !empty($tracking) ) {
            parse_str( $tracking, $codes );

            foreach ( $codes as $key => $val ) {
                $codes[$key] = urlencode($val);

                // GA Tracking for Mandrill
                if ( $key == 'utm_campaign' && class_exists('wpMandrill') ) {
                    $campaigns[] = $val;
                }
            }
        }

        $email_order_meta = maybe_unserialize( $email_order->meta );

        if ( isset( $email_order_meta['codes']) && !empty( $email_order_meta['codes'] ) ) {
            foreach ( $email_order_meta['codes'] as $key => $val ) {
                $codes[$key] = urlencode($val);

                // GA Tracking for Mandrill
                if ( $key == 'utm_campaign' && class_exists('wpMandrill') ) {
                    $campaigns[] = $val;
                }
            }
        }

        if ( !empty( $campaigns ) )
            define( 'FUE_GA_CAMPAIGN', json_encode( array_unique( $campaigns ) ) );

        $store_url      = home_url();
        $store_name     = get_bloginfo('name');
        $page_id        = fue_get_page_id('followup_unsubscribe');
        $unsubscribe    = add_query_arg('fue', $email_data['email_to'], get_permalink($page_id));

        // convert urls
        $store_url      = self::create_email_url( $email_order->id, $email->id, $email_data['user_id'], $email_data['email_to'], $store_url );
        $unsubscribe    = self::create_email_url( $email_order->id, $email->id, $email_data['user_id'], $email_data['email_to'], $unsubscribe );

        if (! empty($codes) ) {
            $store_url      = add_query_arg($codes, $store_url);
            $unsubscribe    = add_query_arg($codes, $unsubscribe);
        }

        $subject    = $email->subject;
        $message    = $email->message;

        if ( $email->email_type == 'signup' ) {
            $vars   = apply_filters( 'fue_email_signup_variables', array('{store_url}', '{store_name}', '{customer_username}', '{customer_first_name}', '{customer_name}', '{customer_email}', '{unsubscribe_url}'), $email_data, $email_order, $email );
            $reps   = apply_filters( 'fue_email_signup_replacements', array(
                    $store_url,
                    $store_name,
                    $email_data['username'],
                    $email_data['first_name'],
                    $email_data['cname'],
                    $email_data['email_to'],
                    $unsubscribe
                ), $email_data, $email_order, $email );
        } elseif ( $email->email_type == 'manual' ) {
            $meta           = maybe_unserialize( $email_order->meta );
            $store_url      = home_url();
            $store_name     = get_bloginfo('name');
            $page_id        = fue_get_page_id('followup_unsubscribe');
            $unsubscribe    = add_query_arg('fue', $email_data['email_to'], get_permalink($page_id));

            // convert urls
            $store_url      = self::create_email_url( $email_order->id, $email->id, $email_order->user_id, $email_data['email_to'], $store_url );
            $unsubscribe    = self::create_email_url( $email_order->id, $email->id, $email_order->user_id, $email_data['email_to'], $unsubscribe );

            if (! empty($codes) ) {
                $store_url      = add_query_arg($codes, $store_url);
                $unsubscribe    = add_query_arg($codes, $unsubscribe);
            }

            if ( $email_order->user_id > 0 ) {
                $first_name = get_user_meta( $email_order->user_id, 'billing_first_name', true );
            } else {
                // try to guess the first name
                $names = explode(' ', $meta['user_name']);
                $first_name = ( isset($names[0]) ) ? $names[0] : $meta['user_name'];
            }

            $username = (isset($meta['username'])) ? $meta['username'] : '';

            $vars   = apply_filters('fue_email_manual_variables', array('{store_url}', '{store_name}', '{customer_username}', '{customer_first_name}', '{customer_name}', '{customer_email}', '{unsubscribe_url}'), $email_data, $email_order, $email );
            $reps   = apply_filters('fue_email_manual_replacements', array(
                    $store_url,
                    $store_name,
                    $username,
                    $first_name,
                    $meta['user_name'],
                    $email_data['email_to'],
                    $unsubscribe
                ), $email_data, $email_order, $email );

            $subject = $meta['subject'];
            $message = $meta['message'];
        } else {
            $vars   = apply_filters( 'fue_email_'. $email->email_type .'_variables', array('{store_url}', '{store_name}', '{unsubscribe_url}'), $email_data, $email_order, $email );
            $reps   = apply_filters( 'fue_email_'. $email->email_type .'_replacements', array($store_url, $store_name, $unsubscribe), $email_data, $email_order, $email );
        }

        $subject    = apply_filters('fue_email_subject', $subject, $email, $email_order);
        $message    = apply_filters('fue_email_message', $message, $email, $email_order);

        $subject    = strip_tags(str_replace($vars, $reps, $subject));
        $message    = str_replace($vars, $reps, $message);

        $message    = do_shortcode($message);

        // hook to variable replacement
        $subject    = apply_filters( 'fue_send_email_subject', $subject, $email_order );
        $message    = apply_filters( 'fue_send_email_message', $message, $email_order );

        // look for custom fields
        $message    = preg_replace_callback('|\{cf ([0-9]+) ([^}]*)\}|', 'fue_add_custom_fields', $message);

        // look for post id
        $message    = preg_replace_callback('|\{post_id=([^}]+)\}|', 'fue_add_post', $message);

        // look for links
        $replacer   = new FUE_Link_Replacement( $email_order->id, $email->id, $email_data['user_id'], $email_data['email_to'] );
        $message    = preg_replace_callback('|\{link url=([^}]+)\}|', array($replacer, 'replace'), $message);

        // look for store_url with path
        $fue->link_meta = array(
            'email_order_id'    => $email_order->id,
            'email_id'          => $email->id,
            'user_id'           => $email_data['user_id'],
            'user_email'        => $email_data['email_to'],
            'codes'             => $codes
        );
        $message    = preg_replace_callback('|\{store_url=([^}]+)\}|', 'FUE::add_store_url', $message);

        $headers    = array();

        $global_bcc = get_option('fue_bcc', '');
        $types_bcc  = get_option('fue_bcc_types', array());
        $email_bcc  = (isset($email_meta['bcc']) && is_email($email_meta['bcc'])) ? $email_meta['bcc'] : false;

        if ( $email_bcc ) {
            $headers[] = "Bcc: $email_bcc\r\n";
        } elseif ( isset($types_bcc[$email->email_type]) && !empty($types_bcc[$email->email_type]) ) {
            $bcc = $types_bcc[$email->email_type];
            $headers[] = "Bcc: $bcc\r\n";
        } elseif ( !empty($global_bcc) && is_email( $global_bcc ) ) {
            $headers[] = "Bcc: $global_bcc\r\n";
        }

        // send the email
        do_action( 'fue_before_email_send', $subject, $message, $headers, $email_order );

        self::mail( $email_data['email_to'], $subject, $message, $headers );

        do_action( 'fue_after_email_sent', $subject, $message, $headers, $email_order );

        // log this email
        if ( $email->email_type == 'manual' ) {
            $email_trigger = __('Manual Email', 'follow_up_emails');
        } else {
            if ( $email->interval_type == 'date' ) {
                $email_trigger = sprintf( __('Send on %s'), fue_format_send_datetime( $email ) );
            } elseif ( $email->interval_type == 'signup' ) {
                $email_trigger = sprintf( __('%d %s after user signs up', 'follow_up_emails'), $email->interval_num, $email->interval_duration );
            } else {
                $email_trigger = sprintf( __('%d %s %s'), $email->interval_num, $email->interval_duration, FollowUpEmails::get_trigger_name( $email->interval_type ) );
            }
        }
        $email_trigger = apply_filters( 'fue_interval_str', $email_trigger, $email );

        do_action( 'fue_after_email_sent', $subject, $message, $email_order );
        do_action( 'fue_email_sent_details', $email_order, $email_order->user_id, $email, $email_data['email_to'], $email_data['cname'], $email_trigger );

        // increment usage count
        $wpdb->query( $wpdb->prepare("UPDATE `{$wpdb->prefix}followup_emails` SET `usage_count` = `usage_count` + 1 WHERE `id` = %d", $email->id) );

        // update the email order
        $now = date('Y-m-d H:i:s');
        $wpdb->query( $wpdb->prepare("UPDATE `{$wpdb->prefix}followup_email_orders` SET `is_sent` = 1, `date_sent` = %s, `email_trigger` = %s WHERE `id` = %d", $now, $email_trigger, $email_order->id) );

        do_action( 'fue_email_order_sent', $email_order->id );

        self::remove_email_order_lock( $email_order );
        self::maybe_archive_email( $email );
    }

    public static function send_manual_emails( $args = array() ) {
        global $wpdb;

        ignore_user_abort( true );
        set_time_limit( 0 );

        $args = wp_parse_args( $args, array(
                'email_id'          => 0,
                'recipients'        => array(),
                'subject'           => '',
                'message'           => '',
                'tracking'          => '',
                'send_again'        => false,
                'interval'          => '',
                'interval_duration' => ''
            )
        );
        extract($args);

        if ( empty($recipients) ) return;

        // process variable replacements
        $codes = array();

        if ( !empty($tracking) ) {
            parse_str( $tracking, $codes );

            foreach ( $codes as $key => $val ) {
                $codes[$key] = urlencode($val);
            }
        }

        $store_url      = home_url();
        $store_name     = get_bloginfo('name');
        $page_id        = fue_get_page_id('followup_unsubscribe');
        $orig_message   = $message;
        $orig_subject   = $subject;
        $recipient_num  = 0;
        $send_time      = current_time( 'timestamp' );

        $email_batch_enabled    = get_option( 'fue_email_batches', 0 );
        $emails_per_batch       = get_option( 'fue_emails_per_batch', 100 );
        $email_batch_interval   = get_option( 'fue_batch_interval', 10 );

        foreach ( $recipients as $recipient ) {
            $recipient_num++;

            // determine when to send this email
            if ( 1 == $email_batch_enabled ) {
                if ( $recipient_num == $emails_per_batch ) {
                    $send_time += ( $email_batch_interval * 60 );
                    $recipient_num = 0;
                }
            }

            // create an email order
            $user_id        = $recipient[0];
            $email_address  = $recipient[1];
            $user_name      = $recipient[2];
            $unsubscribe    = add_query_arg('fue', $email_address, get_permalink($page_id));

            $_message        = $orig_message;
            $_subject        = $orig_subject;

            $meta = array(
                'recipient'     => $recipient,
                'user_id'       => $recipient[0],
                'email_address' => $recipient[1],
                'user_name'     => $recipient[2],
                'subject'       => $_subject,
                'message'       => $_message,
                'codes'         => $codes
            );

            $insert = array(
                'user_id'       => $user_id,
                'order_id'      => 0,
                'product_id'    => 0,
                'email_id'      => $email_id,
                'user_email'    => $email_address,
                'send_on'       => $send_time,
                'is_cart'       => 0,
                'is_sent'       => 0,
                'email_trigger' => 'Manual Email',
                'meta'          => serialize($meta)
            );
            $email_order_id = FUE::schedule_email( $insert );

            if ( $send_again && !empty($interval) && $interval > 0 ) {
                $now = current_time( 'timestamp' );
                $add = self::get_time_to_add( $interval, $interval_duration );

                // create an email order
                $email_data = array(
                    'user_id'       => $user_id,
                    'order_id'      => 0,
                    'product_id'    => 0,
                    'email_id'      => $email_id,
                    'user_email'    => $email_address,
                    'send_on'       => $now + $add,
                    'is_cart'       => 0,
                    'is_sent'       => 0,
                    'email_trigger' => 'Manual Email',
					'meta'          => serialize($meta)
                );

                FUE::schedule_email( $email_data );
            }
        }

        // send the emails now
        do_action('sfn_followup_emails');
    }

    public static function send_test_email() {
        global $wpdb;

        $_POST      = array_map('stripslashes_deep', $_POST);

        $id         = $_POST['id'];
        $email      = $_POST['email'];
        $message    = $_POST['message'];
        $codes      = array();
        $data       = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE id = %d", $id) );
        $type       = $data->email_type;

        if ( !empty($data->tracking_code) ) {
            parse_str( $data->tracking_code, $codes );

            foreach ( $codes as $key => $val ) {
                $codes[$key] = urlencode($val);
            }

        }

        // process variable replacements
        $store_url      = (empty($codes)) ? site_url() : add_query_arg($codes, site_url());
        $store_name     = get_bloginfo('name');
        $page_id        = fue_get_page_id('followup_unsubscribe');
        $unsubscribe    = (empty($codes)) ? add_query_arg('wcfu', $email, get_permalink($page_id)) : add_query_arg($codes, add_query_arg('wcfu', $email, get_permalink($page_id)));

        if ( $type == 'signup' ) {
            $vars   = apply_filters( 'fue_email_signup_test_variables', array('{store_url}', '{store_name}', '{customer_username}', '{customer_first_name}', '{customer_name}', '{customer_email}', '{unsubscribe_url}') );
            $reps   = apply_filters( 'fue_email_signup_test_replacements', array(
                $store_url,
                $store_name,
                wp_get_current_user()->user_login,
                'John',
                'John Doe',
                'john@example.org',
                $unsubscribe
            ) );
        } elseif ( $type == 'manual' ) {
            $vars   = apply_filters( 'fue_email_manual_test_variables', array('{store_url}', '{store_name}', '{customer_username}', '{customer_first_name}', '{customer_name}', '{customer_email}', '{unsubscribe_url}') );
            $reps   = apply_filters( 'fue_email_manual_test_replacements', array(
                $store_url,
                $store_name,
                wp_get_current_user()->user_login,
                'John',
                'John Doe',
                'john@example.org',
                $unsubscribe
            ) );
        } else {
            $vars   = apply_filters( 'fue_email_'. $data->email_type .'_test_variables', array('{store_url}', '{store_name}', '{unsubscribe_url}'), $_POST );
            $reps   = apply_filters( 'fue_email_'. $data->email_type .'_test_replacements', array($store_url, $store_name, $unsubscribe), $_POST );
        }


        $subject    = strip_tags(str_replace($vars, $reps, $data->subject));
        $message    = str_replace($vars, $reps, $message);
        $message    = do_shortcode($message);

        // hook to variable replacement
        $subject    = apply_filters( 'fue_send_test_email_subject', $subject );
        $message    = apply_filters( 'fue_send_test_email_message', $message );

        // look for custom fields
        $message    = preg_replace_callback('|\{cf ([0-9]+) ([^}]*)\}|', 'fue_add_custom_fields', $message);

        // look for post id
        $message    = preg_replace_callback('|\{post_id=([^}]+)\}|', 'fue_add_post', $message);

        // look for links
        //$replacer   = new FUE_Link_Replacement( $email_order->id, $email->id, $user_id, $email_to );
        $message    = preg_replace('|\{link url=([^}]+)\}|', '$1', $message);

        // look for store_url with path
        $message    = preg_replace_callback('|\{store_url=([^}]+)\}|', 'FUE::add_test_store_url', $message);

        do_action( 'fue_before_test_email_send', $subject, $message );

        self::mail( $email, $subject, $message );

        do_action( 'fue_after_test_email_sent', $subject, $message );

        die("OK");
    }

    public static function mail($to, $subject, $message, $headers = '', $attachments = '') {

        // inject CSS rules for text and image alignment
        $css = self::get_html_email_css();
        $message = $css . $message;

        if ( FollowUpEmails::is_woocommerce_installed() ) {
            global $woocommerce;

            // send the email
            $disable_wrap   = get_option('fue_disable_wrapping', 0);
            $mailer         = $woocommerce->mailer();

            if (! $disable_wrap ) {
                $message = $mailer->wrap_message( $subject, $message );
            } else {
                $message = wpautop( wptexturize( $message ) );
            }

            // Send through Mandrill if WP Mandrill is installed
            if ( class_exists('wpMandrill') ) {
                $site_url_parts = parse_url( get_bloginfo('url') );
                $ga_campaign    = (defined('FUE_GA_CAMPAIGN')) ? FUE_GA_CAMPAIGN : '';
                $ga_domain      = ($ga_campaign) ? array($site_url_parts['host']) : array();
                $settings       = get_option( 'wpmandrill', array() );

                if (! empty( $ga_campaign ) ) {
                    $ga_campaign = json_decode( $ga_campaign );
                }

                wpMandrill::mail(
                    $to, $subject, $message, $headers, $attachments,
                    array(), // tags
                    $settings['from_name'], // from_name
                    $settings['from_username'], // from_email
                    '', // template_name
                    true, // track_opens
                    true, // track_clicks
                    false, // url_strip_qs
                    true, // merge
                    array(), // global_merge_vars
                    array(), // merge_vars
                    $ga_domain,
                    $ga_campaign
                );
            } else {
                $mailer->send($to, $subject, $message, $headers, $attachments);
            }

        } else {
            add_filter( 'wp_mail_content_type', 'FUE::set_html_content_type' );
            wp_mail($to, $subject, $message, $headers, $attachments);
            remove_filter( 'wp_mail_content_type', 'FUE::set_html_content_type' );
        }

    }

    public static function set_html_content_type() {
        return 'text/html';
    }

    public static function get_html_email_css() {
        $css = '<style type="text/css"> .alignleft{float:left;margin:5px 20px 5px 0;}.alignright{float:right;margin:5px 0 5px 20px;}.aligncenter{display:block;margin:5px auto;}img.alignnone{margin:5px 0;}'.
                'blockquote,q{quotes:none;}blockquote:before,blockquote:after,q:before,q:after{content:"";content:none;}'.
                'blockquote{font-size:24px;font-style:italic;font-weight:300;margin:24px 40px;}'.
                'blockquote blockquote{margin-right:0;}blockquote cite,blockquote small{font-size:14px;font-weight:normal;text-transform:uppercase;}'.
                'cite{border-bottom:0;}abbr[title]{border-bottom:1px dotted;}address{font-style:italic;margin:0 0 24px;}'.
                'del{color:#333;}ins{background:#fff9c0;border:none;color:#333;text-decoration:none;}'.
                'sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline;}'.
                'sup{top:-0.5em;}sub{bottom:-0.25em;}</style>';

        return $css;
    }

    /**
     * Checks if the send date of the email is in the past
     * @param object $email
     * @return bool
     */
    public static function send_date_passed( $email ) {
        global $wpdb;

        if ( is_numeric($email) ) {
            $email = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE id = %d", $email) );
        }

        $meta = maybe_unserialize( $email->meta );

        if ( !empty($email->send_date_hour) && !empty($email->send_date_minute) ) {
            $send_on = strtotime($email->send_date .' '. $email->send_date_hour .':'. $email->send_date_minute .' '. $meta['send_date_ampm']);

            if ( false === $send_on ) {
                // fallback to only using the date
                $send_on = strtotime($email->send_date);
            }
        } else {
            $send_on = strtotime($email->send_date);
        }

        if ( $send_on > time() ) {
            // Send date is in the future
            return false;
        }

        return true;
    }

    public static function get_email_send_timestamp( $email ) {
        global $wpdb;

        if (! is_object($email) ) {
            $email = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE id = %d", $email) );
        }

        $interval   = (int)$email->interval_num;
        $send_on    = current_time( 'timestamp' );

        if ( $email->interval_type == 'date' ) {
            $meta = maybe_unserialize( $email->meta );

            if ( !empty($email->send_date_hour) && !empty($email->send_date_minute) ) {
                $send_on = strtotime($email->send_date .' '. $email->send_date_hour .':'. $email->send_date_minute .' '. $meta['send_date_ampm']);

                if ( false === $send_on ) {
                    // fallback to only using the date
                    $send_on = strtotime($email->send_date);
                }
            } else {
                $send_on = strtotime($email->send_date);
            }
        } else {
            $add        = FUE::get_time_to_add( $interval, $email->interval_duration );
            $send_on    = current_time('timestamp') + $add;
        }

        return $send_on;
    }

    public static function maybe_archive_email( $email ) {
        global $wpdb;

        if ( $email->interval_type != 'date' || $email->email_type == 'manual' ) {
            return;
        }

        // if there are no more unsent emails in the queue, archive this email
        $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}followup_email_orders WHERE email_id = %d AND is_sent = 0", $email->id ) );

        if ( $count == 0 ) {
            FUE::update_email_status( $email->id, FUE::STATUS_ARCHIVED );
        }
    }

    public static function update_email_status( $id, $status ) {
        global $wpdb;

        $wpdb->update( $wpdb->prefix .'followup_emails', array('status' => $status), array('id' => $id) );

        return true;
    }

    public static function queue_email( $values, $email ) {
        $defaults = array(
            'user_id'       => '',
            'user_email'    => '',
            'is_cart'       => 0,
            'meta'          => ''
        );

        $values = wp_parse_args( $values, $defaults );

        $values['send_on']  = self::get_email_send_timestamp($email);
        $values['email_id'] = $email->id;

        FUE::insert_email_order( $values );
    }

    public static function get_time_to_add( $interval, $duration ) {
        $add = 0;
        switch ($duration) {
            case 'minutes':
                $add = $interval * 60;
                break;

            case 'hours':
                $add = $interval * (60*60);
                break;

            case 'days':
                $add = $interval * 86400;
                break;

            case 'weeks':
                $add = $interval * (7 * 86400);
                break;

            case 'months':
                $add = $interval * (30 * 86400);
                break;

            case 'years':
                $add = $interval * (365 * 86400);
                break;
        }

        return apply_filters('fue_get_time_to_add', $add, $duration, $interval);
    }

    public static function create_email_url( $email_order_id, $email_id, $user_id = 0, $user_email, $target_page ) {
        $args = apply_filters('fue_create_email_url', array(
            'oid'           => $email_order_id,
            'eid'           => $email_id,
            'user_id'       => $user_id,
            'user_email'    => $user_email,
            'next'          => $target_page
        ));

        $payload    = base64_encode(http_build_query($args, '', '&'));

        return add_query_arg( 'sfn_payload', $payload, add_query_arg( 'sfn_trk', 1, get_bloginfo( 'wpurl' ) ) );
    }

    public static function add_store_url( $matches ) {
        global $fue;

        if ( empty($matches) ) return '';

        $store_url  = home_url( $matches[1] );
        $meta       = $fue->link_meta;

        // convert urls
        $store_url  = self::create_email_url( $meta['email_order_id'], $meta['email_id'], $meta['user_id'], $meta['user_email'], $store_url );

        if (! empty($meta['codes']) ) {
            $store_url  = add_query_arg($meta['codes'], $store_url);
        }

        return $store_url;
    }

    public static function add_test_store_url( $matches ) {
        global $fue;

        if ( empty($matches) ) return '';

        $store_url  = home_url( $matches[1] );

        return $store_url;
    }

    public static function clone_email($id, $new_name) {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE id = %d", $id), ARRAY_A);

        if ($row) {
            unset($row['id']);

            $row['name']        = $new_name;
            $row['usage_count'] = 0;

            $wpdb->insert($wpdb->prefix .'followup_emails', $row);
            $new_id = $wpdb->insert_id;

            do_action('fue_email_cloned', $new_id, $id);

            return $new_id;
        } else {
            return new WP_Error( sprintf(__('Email (%d) could not be found', 'follow_up_emails'), $id) );
        }

    }

    public static function send_usage_data() {
        global $wpdb;

        $disabled = get_option('fue_disable_usage_data', false);

        if ( $disabled == 1 )
            return;

        $site_id = self::get_site_id();

        $wp_version = get_bloginfo('version');

        $site_data  = array(
            'site_id'       => $site_id,
            'fue_version'   => FUE_VERSION,
            'wp_version'    => $wp_version,
            'scheduler'     => FollowUpEmails::$scheduling_system
        );

        if ( FollowUpEmails::$is_woocommerce ) {
            $wc_version = (defined('WC_VERSION')) ? WC_VERSION : WOOCOMMERCE_VERSION;
            $site_data['wc_version'] = $wc_version;
        }

        self::api_call( 'register_site', $site_data );

        self::import_usage_data();

        // number of emails sent since the last run
        $last_check = get_option( 'fue_usage_last_report', 0 );

        $sent_data  = array();

        $sql = $wpdb->prepare(
            "SELECT e.email_type
            FROM {$wpdb->prefix}followup_emails e, {$wpdb->prefix}followup_email_orders eo
            WHERE e.id = eo.email_id
            AND eo.is_sent = 1
            AND eo.date_sent >= %s",
            date('Y-m-d H:i:s', $last_check)
        );
        $sent_emails    = $wpdb->get_results( $sql );

        foreach ( $sent_emails as $sent_email ) {
            $type = $sent_email->email_type;

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

    public static function import_usage_data() {
        global $wpdb;

        $disabled   = get_option('fue_disable_usage_data', false);
        $imported   = get_option('fue_imported_previous_usage', false);

        if ( $disabled == 1 )
            return;

        if ( $imported )
            return;

        $emails = $wpdb->get_results("
            SELECT e.email_type, eo.date_sent
            FROM {$wpdb->prefix}followup_emails e, {$wpdb->prefix}followup_email_orders eo
            WHERE e.id = eo.email_id
            AND eo.is_sent = 1");

        $sent_data = array();
        foreach ( $emails as $email ) {
            $type = $email->email_type;
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

    private static function get_site_id() {
        $site_id    = get_option( 'fue_site_id', false );

        if (! $site_id ) {
            $site_id = md5(get_bloginfo('url'));

            update_option( 'fue_site_id', $site_id );
        }

        return $site_id;
    }

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
            return json_encode($response);
        } else {
            if (! empty($resp['body']) ) {
                return json_decode( $resp['body'] );
            }
        }

        return true;
    }

    public static function get_email_order_lock_key( $email_order ) {
        return 'fue_lock_'. $email_order->id;
    }

    /**
     * Place a temporary 5-minute lock into an email order to give enough time for the current
     * sending process to complete.
     *
     * @param array $email_order A row from the followup_email_orders table
     */
    public static function lock_email_order( $email_order ) {
        global $wpdb;

        $key        = self::get_email_order_lock_key( $email_order );
        $lock_until = (int)gmdate('U') + 300;

        $wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name = %s", $key) );
        $wpdb->insert( $wpdb->options, array( 'option_name' => $key, 'option_value' => $lock_until, 'autoload' => 'no' ), array( '%s', '%s', '%s' ) );
    }

    public static function remove_email_order_lock( $email_order ) {
        global $wpdb;

        $key = self::get_email_order_lock_key( $email_order );
        $wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name = %s", $key) );
    }

    public static function is_email_order_locked( $email_order ) {
        global $wpdb;

        $locked         = false;
        $key            = self::get_email_order_lock_key( $email_order );
        $current_time   = gmdate('U');

        $lock = $wpdb->get_var( $wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s", $key) );

        // no lock row found
        if ( !$lock )
            return $locked;

        if ( $lock > $current_time ) {
            $locked = true;
        } else {
            // lock time has passed, delete the lock row
            $wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name = %s", $key) );
        }

        return $locked;
    }

    public static function reschedule_email_order( $email_order ) {

        $param = array(
            'email_order_id'    => $email_order->id
        );

        wc_unschedule_action( 'sfn_followup_emails', $param, 'fue' );

        // because ActionScheduler uses GMT for scheduling events, convert the send date to GMT
        $send_on_gmt = $email_order->send_on + 300;

        wc_schedule_single_action( $send_on_gmt, 'sfn_followup_emails', $param, 'fue' );

    }

}