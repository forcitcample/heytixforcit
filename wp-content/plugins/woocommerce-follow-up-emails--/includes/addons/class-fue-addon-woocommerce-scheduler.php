<?php

/**
 * Class FUE_Addon_Woocommerce_Scheduler
 */
class FUE_Addon_Woocommerce_Scheduler {

    /**
     * @var FUE_Addon_Woocommerce
     */
    private $fue_wc;

    /**
     * Class constructor
     *
     * @param FUE_Addon_Woocommerce $wc
     */
    public function __construct( $wc ) {
        $this->fue_wc = $wc;

        $this->register_hooks();
    }

    /**
     * Register hooks
     */
    private function register_hooks() {
        // refunds
        add_action( 'woocommerce_refund_created', array($this, 'refund_manual') );
        add_action( 'woocommerce_refund_processed', array($this, 'refund_processed'), 10, 2 );

        // @since 2.2.1 support custom order statuses
        add_action( 'init', array($this, 'hook_statuses') );
        add_action( 'woocommerce_checkout_order_processed', array($this, 'order_status_updated') );
        add_action( 'woocommerce_order_status_changed', array($this, 'unqueue_status_emails'), 10, 3 );

        // subscriptions
        add_action( 'woocommerce_subscriptions_renewal_order_created', array($this, 'reschedule_last_purchase_emails'), 11, 3 );

        add_filter( 'fue_insert_email_order', array($this, 'get_correct_email') );

        add_filter( 'fue_queue_item_filter_conditions', array($this, 'check_item_conditions'), 10, 2 );
    }

    /**
     * Delete all unsent cart emails for the given customer
     * @param int $customer_id
     */
    public function delete_unsent_cart_emails( $customer_id ) {
        $cart_queue = Follow_Up_Emails::instance()->scheduler->get_items( array(
            'is_cart'   => 1,
            'is_sent'   => 0,
            'user_id'   => $customer_id
        ) );

        foreach ( $cart_queue as $queue_item ) {
            Follow_Up_Emails::instance()->scheduler->delete_item( $queue_item->id );
        }

        update_user_meta( $customer_id, '_wcfue_cart_emails', array() );
    }

    /**
     * Schedule emails after a refund has been manually processed
     *
     * Checks for the existence of $_POST['api_refund'] to make sure
     * that the request came from the admin edit order screen
     *
     * @param int $refund_id
     */
    public function refund_manual( $refund_id ) {

        if ( !isset($_POST['api_refund']) || $_POST['api_refund'] === 'true' )
            return;

        $refund = WC_FUE_Compatibility::wc_get_order( $refund_id );

        $emails = fue_get_emails( 'any', FUE_Email::STATUS_ACTIVE, array(
            'meta_query' => array(
                array(
                    'key'   => '_interval_type',
                    'value' => 'refund_manual'
                )
            )
        ) );

        foreach ( $emails as $email ) {
            $insert = array(
                'order_id'  => $refund->post->post_parent,
                'meta'      => array(
                    'refund_id'     => $refund_id,
                    'refund_amount' => get_post_meta( $refund_id, '_refund_amount', true ),
                    'refund_reason' => $refund->reason
                )
            );
            FUE_Sending_Scheduler::queue_email( $insert, $email );
        }

    }

    /**
     * Schedule emails after a refund has been processed by a payment gateway
     * @param bool $successful Status returned by the payment gateway if the refund have been successful or not
     * @param WC_Order_Refund $refund
     */
    public function refund_processed( $refund, $successful ) {

        if ( $successful )
            $trigger = 'refund_successful';
        else
            $trigger = 'refund_failed';

        $emails = fue_get_emails( 'any', FUE_Email::STATUS_ACTIVE, array(
            'meta_query' => array(
                array(
                    'key'   => '_interval_type',
                    'value' => $trigger
                )
            )
        ) );

        foreach ( $emails as $email ) {
            $insert = array(
                'order_id'  => $refund->post->post_parent,
                'meta'      => array(
                    'refund_id'     => $refund->id,
                    'refund_amount' => get_post_meta( $refund->id, '_refund_amount', true ),
                    'refund_reason' => $refund->reason
                )
            );
            FUE_Sending_Scheduler::queue_email( $insert, $email );
        }
    }

    /**
     * Register order statuses to trigger follow-up emails
     */
    public function hook_statuses() {
        $statuses = $this->fue_wc->get_order_statuses();

        foreach ( $statuses as $status ) {
            add_action('woocommerce_order_status_'. $status, array($this, 'order_status_updated'), 100);
        }

    }

    /**
     * When an order gets updated, queue emails that match the new status
     *
     * @param int $order_id
     */
    public function order_status_updated( $order_id ) {

        $order = WC_FUE_Compatibility::wc_get_order($order_id);

        FUE_Addon_Woocommerce::record_order( $order );

        $queued         = array();
        $triggers       = $this->get_order_triggers( $order, Follow_Up_Emails::get_email_type( 'storewide' ) );

        $product_emails = $this->get_matching_product_emails( $order, $triggers, false );
        $queued         = array_merge( $queued, $this->queue_product_emails( $product_emails, $order ) );

        $product_always_send_emails = $this->get_matching_product_emails( $order, $triggers, true );
        $queued         = array_merge( $queued, $this->queue_always_send_product_emails( $product_always_send_emails, $order ) );

        $category_emails    = $this->get_matching_category_emails( $order, $triggers, false );
        $queued             = array_merge( $queued, $this->queue_category_emails( $category_emails, $order ) );

        $category_always_send_emails    = $this->get_matching_category_emails( $order, $triggers, true );
        $queued         = array_merge( $queued, $this->queue_always_send_category_emails( $category_always_send_emails, $order ) );

        $storewide_always_send_emails   = $this->get_matching_storewide_emails( $order, $triggers, true );
        $queued         = array_merge( $queued, $this->queue_storewide_emails( $storewide_always_send_emails, $order ) );

        if ( count( $queued ) == 0 ) {
            $storewide_emails = $this->get_matching_storewide_emails( $order, $triggers );
            $queued = array_merge( $queued, $this->queue_storewide_emails( $storewide_emails, $order ) );
        }

        $order_status = WC_FUE_Compatibility::get_order_status( $order );

        if ( $order_status == 'processing' || $order_status == 'completed' ) {
            // only queue date and customer emails once per order
            if ( get_post_meta( $order_id, '_order_status_emails_queued', true ) != true ) {

                $queued = array_merge( $queued, $this->queue_date_emails( $order ) );
                $queued = array_merge( $queued, $this->queue_reminder_emails( $order ) );
                $queued = array_merge( $queued, $this->queue_customer_emails( $order ) );

            }
        }

        $this->add_order_notes_to_queued_emails( $queued );

        // remove signup emails that have the 'remove_signup_emails_on_purchase' option enabled
        $this->remove_signup_emails_on_purchase( $order );

    }

    /**
     * Remove unsent emails with triggers matching the old order status from the queue
     *
     * @param int $order_id
     * @param string $old_status
     * @param string $new_status
     */
    public function unqueue_status_emails( $order_id, $old_status, $new_status ) {
        $order      = WC_FUE_Compatibility::wc_get_order( $order_id );
        $scheduler  = Follow_Up_Emails::instance()->scheduler;
        $filter     = array(
            'meta_query'    => array(
                array(
                    'key'       => '_interval_type',
                    'value'     => $old_status
                )
            )
        );

        $emails     = fue_get_emails( 'storewide', '', $filter );
        $email_ids  = array();

        foreach ( $emails as $email ) {
            if ( !empty( $email->meta['remove_email_status_change'] ) && $email->meta['remove_email_status_change'] == 'yes' ) {
                $email_ids[] = $email->id;
            }
        }

        $queue = $scheduler->get_items( array(
            'is_sent'   => 0,
            'order_id'  => $order_id,
            'email_id'  => $email_ids
        ) );

        foreach ( $queue as $item ) {
            $email_name = get_the_title( $item->email_id );
            $order->add_order_note( sprintf( __('The email &quot;%s&quot; has been removed due to an order status change', 'follow_up_emails'), $email_name ) );
            $scheduler->delete_item( $item->id );
        }
    }

    /**
     * Adjust last_purchase emails after a renewal order have been created
     * @param WC_Order $renewal_order
     * @param WC_Order $original_order
     * @param int $product_id
     */
    public function reschedule_last_purchase_emails( $renewal_order, $original_order, $product_id ) {
        //$this->queue_customer_last_purchased_emails( $original_order );
    }

    /**
     * Add cart emails to the queue
     * @param array $cart
     * @param WP_User $user
     */
    public function queue_cart_emails( $cart, $user = null ) {

        if ( is_null( $user ) ) {
            $user = wp_get_current_user();
        }

        // only works for logged in customers
        if ( $user->ID == 0 ) {
            return;
        }

        $cart_session = $this->fue_wc->get_user_cart_session( $user->ID );

        $cart_emails    = array();
        $always_prods   = array();
        $always_cats    = array();
        $email_created  = false;

        foreach ( $cart as $item_key => $item ) {

            // look for cart emails matching the current cart item
            $emails = $this->get_cart_emails( FUE_Email::STATUS_ACTIVE, array(
                'product_id' => $item['product_id']
            ) );

            if ( count( $emails ) == 0 ) {
                continue;
            }

            $email = current( $emails );

            if ( $email ) {
                $queue_check = Follow_Up_Emails::instance()->scheduler->get_items( array(
                    'is_sent'       => 0,
                    'order_id'      => 0,
                    'product_id'    => $item['product_id'],
                    'email_id'      => $email->id,
                    'user_id'       => $user->ID,
                    'is_cart'       => 1
                ) );

                if (
                    count( $queue_check ) == 0 &&
                    !in_array( $email->id .'_'. $item['product_id'], $cart_session )
                ) {
                    $cart_session[] = $email->id .'_'. $item['product_id'];
                    $cart_emails[]  = array(
                        'id'        => $email->id,
                        'item'      => $item['product_id'],
                        'priority'  => $email->priority
                    );
                }
            }

            // always_send product matches
            $emails = $this->get_cart_emails( FUE_Email::STATUS_ACTIVE, array(
                'product_id' => $item['product_id'],
                'always_send'   => 1
            ) );

            foreach ( $emails as $email ) {
                $check = Follow_Up_Emails::instance()->scheduler->get_items( array(
                    'is_sent'       => 0,
                    'order_id'      => 0,
                    'product_id'    => $item['product_id'],
                    'email_id'      => $email->id,
                    'user_id'       => $user->ID,
                    'is_cart'       => 1
                ) );

                if ( count( $check ) == 0 && !in_array( $email->id .'_'. $item['product_id'], $cart_session ) ) {
                    $cart_session[] = $email->id .'_'. $item['product_id'];
                    $always_prods[] = array(
                        'id'    => $email->id,
                        'item'  => $item['product_id']
                    );
                }
            }

            // always_send category matches
            $cat_ids  = wp_get_object_terms( $item['product_id'], 'product_cat', array('fields' => 'ids') );

            $emails = $this->get_cart_emails( FUE_Email::STATUS_ACTIVE, array(
                'always_send'   => 1,
                'category_id'   => $cat_ids
            ) );

            foreach ( $emails as $email ) {
                $check = Follow_Up_Emails::instance()->scheduler->get_items( array(
                    'is_sent'       => 0,
                    'order_id'      => 0,
                    'product_id'    => $item['product_id'],
                    'email_id'      => $email->id,
                    'user_id'       => $user->ID,
                    'is_cart'       => 1
                ) );

                if ( count( $check ) == 0 && !in_array($email->id .'_'. $item['product_id'], $cart_session) ) {
                    $cart_session[] = $email->id .'_'. $item['product_id'];
                    $always_cats[] = array(
                        'id'    => $email->id,
                        'item'  => $item['product_id']
                    );
                }
            }
        }

        if ( !empty($always_prods) ) {
            foreach ( $always_prods as $row ) {
                $email = new FUE_Email( $row['id'] );

                $insert = array(
                    'product_id'=> $row['item'],
                    'is_cart'   => 1,
                    'user_id'   => $user->ID
                );
                FUE_Sending_Scheduler::queue_email( $insert, $email );
            }
        }

        if ( !empty($always_cats) ) {
            foreach ( $always_cats as $row ) {
                $email = new FUE_Email( $row['id'] );

                $insert = array(
                    'product_id'=> $row['item'],
                    'is_cart'   => 1,
                    'user_id'   => $user->ID
                );
                FUE_Sending_Scheduler::queue_email( $insert, $email );

            }
        }

        // product matches
        if ( !empty($cart_emails) ) {
            // find the one with the highest priority
            $top        = false;
            $highest    = 1000;
            foreach ( $cart_emails as $email ) {
                if ( $email['priority'] < $highest ) {
                    $highest    = $email['priority'];
                    $top        = $email;
                }
            }

            if ( $top !== false ) {
                $email = new FUE_Email( $top['id'] );

                $insert = array(
                    'product_id'=> $top['item'],
                    'is_cart'   => 1,
                    'user_id'   => $user->ID
                );
                if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $email ) ) ) {
                    $email_created = true;
                }
            }
        }

        // find a category match
        if ( !$email_created ) {
            $emails = array();
            foreach ( $cart as $item_key => $item ) {
                $cat_ids    = wp_get_object_terms( $item['product_id'], 'product_cat', array('fields' => 'ids') );

                $rows = $this->get_cart_emails( FUE_Email::STATUS_ACTIVE, array(
                    'category_id' => $cat_ids
                ) );

                foreach ( $rows as $email ) {
                    $check = Follow_Up_Emails::instance()->scheduler->get_items( array(
                        'is_sent'       => 0,
                        'order_id'      => 0,
                        'product_id'    => $item['product_id'],
                        'email_id'      => $email->id,
                        'user_id'       => $user->ID,
                        'is_cart'       => 1
                    ) );

                    if ( count( $check ) == 0 && !in_array($email->id .'_'. $item['product_id'], $cart_session) ) {
                        $cart_session[] = $email->id .'_'. $item['product_id'];
                        $emails[] = array('id' => $email->id, 'item' => $item['product_id'], 'priority' => $email->priority);
                    }
                }
            }

            if ( !empty($emails) ) {
                // find the one with the highest priority
                $top        = false;
                $highest    = 1000;
                foreach ( $emails as $email ) {
                    if ( $email['priority'] < $highest ) {
                        $highest    = $email['priority'];
                        $top        = $email;
                    }
                }

                if ( $top !== false ) {
                    $email = new FUE_Email( $top['id'] );

                    $insert = array(
                        'product_id'=> $top['item'],
                        'is_cart'   => 1,
                        'user_id'   => $user->ID
                    );
                    if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $email ) ) ) {
                        $email_created = true;
                    }
                }
            }
        }

        if ( !$email_created ) {
            // find a storewide mailer
            $emails = $this->get_cart_emails( FUE_Email::STATUS_ACTIVE );

            foreach ( $emails as $email ) {
                $check = Follow_Up_Emails::instance()->scheduler->get_items( array(
                    'is_sent'       => 0,
                    'order_id'      => 0,
                    'product_id'    => 0,
                    'email_id'      => $email->id,
                    'user_id'       => $user->ID,
                    'is_cart'       => 1
                ) );

                if ( count( $check ) > 0 || in_array($email->id .'_0', $cart_session) ) continue;
                $cart_session[] = $email->id .'_0';

                $insert = array(
                    'is_cart'   => 1,
                    'user_id'   => $user->ID
                );
                FUE_Sending_Scheduler::queue_email( $insert, $email );

            }
        }

        update_user_meta( $user->ID, '_wcfue_cart_emails', $cart_session );
    }

    /**
     * Queue product emails that match the $order's status
     * @param array $emails Array of FUE_Emails to queue
     * @param WC_Order $order
     * @return array Array of emails added to the queue
     */
    protected function queue_product_emails( $emails, $order ) {
        $queued     = array();

        if ( !empty( $emails ) ) {
            $top_email = reset( $emails );

            if ( $top_email ) {

                $insert = array(
                    'send_on'       => $top_email->get_send_timestamp(),
                    'email_id'      => $top_email->id,
                    'product_id'    => $top_email->product_id,
                    'order_id'      => $order->id
                );

                if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $top_email ) ) ) {
                    $queued[] = $insert;
                }

                // look for other emails with the same product id
                foreach ( $emails as $product_email ) {
                    if ( $product_email->id == $top_email->id ) continue;

                    if ( $product_email->product_id == $top_email->product_id ) {

                        $insert = array(
                            'send_on'       => $product_email->get_send_timestamp(),
                            'email_id'      => $product_email->id,
                            'product_id'    => $product_email->product_id,
                            'order_id'      => $order->id
                        );

                        if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $product_email ) ) ) {
                            $queued[] = $insert;
                        }
                    } else {
                        // if schedule is within 60 minutes, add to queue
                        $interval   = (int)$product_email->interval;

                        if ( $product_email->interval_type == 'date' ) {
                            continue;
                        } else {
                            $add = FUE_Sending_Scheduler::get_time_to_add( $interval, $product_email->interval_duration );

                            if ( $add > 3600 ) continue;

                            // less than 60 minutes, add to queue
                            $send_on = current_time('timestamp') + $add;
                        }

                        $insert = array(
                            'send_on'       => $send_on,
                            'email_id'      => $product_email->id,
                            'product_id'    => $product_email->product_id,
                            'order_id'      => $order->id
                        );
                        if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $product_email ) ) ) {
                            $queued[] = $insert;
                        }
                    }
                }
            }
        }

        return $queued;

    }

    /**
     * Queue always_send product emails that match the $order's status
     * @param array $emails Array of FUE_Emails to queue
     * @param WC_Order $order
     * @return array Array of emails added to the queue
     */
    protected function queue_always_send_product_emails( $emails, $order ) {
        $queued = array();

        foreach ( $emails as $email ) {
            $skip = apply_filters( 'fue_create_order_always_send', false, $email, $order );

            if (! $skip ) {

                $insert = array(
                    'send_on'       => $email->get_send_timestamp(),
                    'email_id'      => $email->id,
                    'product_id'    => $email->product_id,
                    'order_id'      => $order->id
                );
                if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $email ) ) ) {
                    $queued[] = $insert;
                }
            }
        }

        return $queued;

    }

    /**
     * Queue category emails that match the $order's status
     * @param array $emails Array of FUE_Emails to queue
     * @param WC_Order $order
     * @return array Array of emails added to the queue
     */
    protected function queue_category_emails( $emails, $order ) {
        $queued     = array();

        if ( !empty( $emails ) ) {
            $top_email = reset( $emails );

            if ( $top_email !== false ) {

                $insert = array(
                    'send_on'       => $top_email->get_send_timestamp(),
                    'email_id'      => $top_email->id,
                    'product_id'    => $top_email->product_id,
                    'order_id'      => $order->id
                );

                if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $top_email ) ) ) {
                    $queued[] = $insert;
                }

                // look for other emails with the same category id
                foreach ( $emails as $cat_email ) {
                    if ( $cat_email->id == $top_email->id )
                        continue;

                    if ( $cat_email->category_id == $top_email->category_id ) {

                        $insert = array(
                            'send_on'       => $cat_email->get_send_timestamp(),
                            'email_id'      => $cat_email->id,
                            'product_id'    => $cat_email->product_id,
                            'order_id'      => $order->id
                        );

                        if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $cat_email ) ) ) {
                            $queued[] = $insert;
                        }

                    } else {
                        // if schedule is within 60 minutes, add to queue
                        $interval   = (int)$cat_email->interval;

                        if ( $cat_email->interval_type == 'date' ) {
                            continue;
                        }

                        $add = FUE_Sending_Scheduler::get_time_to_add( $interval, $cat_email->interval_duration );

                        if ( $add > 3600 ) {
                            continue;
                        }

                        // less than 60 minutes, add to queue
                        $send_on = current_time('timestamp') + $add;

                        $insert = array(
                            'send_on'       => $send_on,
                            'email_id'      => $cat_email->id,
                            'product_id'    => $cat_email->product_id,
                            'order_id'      => $order->id
                        );
                        if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $cat_email ) ) ) {
                            $queued[] = $insert;
                        }
                    }
                }
            }
        }

        return $queued;
    }

    /**
     * Queue always_send category emails that match the $order's status
     * @param array $emails Array of FUE_Emails to queue
     * @param WC_Order $order
     * @return array Array of emails added to the queue
     */
    protected function queue_always_send_category_emails( $emails, $order ) {
        $queued = array();

        foreach ( $emails as $email ) {
            $interval   = (int)$email->interval;

            $skip = apply_filters( 'fue_create_order_always_send', false, $email, $order );

            if ( ! $skip ) {

                $insert = array(
                    'send_on'       => $email->get_send_timestamp(),
                    'email_id'      => $email->id,
                    'order_id'      => $order->id,
                    'product_id'    => $email->product_id
                );
                if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $email ) ) ) {
                    $queued[] = $insert;
                }
            }
        }

        return $queued;
    }

    /**
     * Add storewide emails to the queue
     *
     * @param array     $emails
     * @param WC_Order  $order
     * @return array
     */
    protected function queue_storewide_emails( $emails, $order ) {
        $queued = array();

        foreach ( $emails as $email ) {
            $insert = array(
                'send_on'       => $email->get_send_timestamp(),
                'email_id'      => $email->id,
                'order_id'      => $order->id
            );
            if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $email ) ) ) {
                $queued[] = $insert;
            }

        }

        return $queued;

    }

    /**
     * Queue customer emails
     * @param WC_Order $order
     * @return array
     */
    protected function queue_customer_emails( $order ) {
        $wpdb       = Follow_Up_Emails::instance()->wpdb;
        $queued     = array();
        $user_id    = WC_FUE_Compatibility::get_order_user_id( $order );

        if ( $user_id > 0 ) {
            $fue_customer = fue_get_customer( $user_id );

            if ( !$fue_customer ) {
                FUE_Addon_Woocommerce::record_order( $order );
                $fue_customer = fue_get_customer( $user_id );
            }

            $fue_customer_id    = $fue_customer->id;
        } else {
            $fue_customer       = fue_get_customer( 0, $order->billing_email );
            $fue_customer_id    = $fue_customer->id;
        }

        if ( $fue_customer_id ) {
            /**
             * Look for and queue first_purchase and product_purchase_above_one emails
             * for the 'storewide' email type
             */
            $product_ids = $this->get_product_ids_from_order( $order );

            foreach ( $product_ids as $product_id ) {

                // number of time this customer have purchased the current item
                $num_product_purchases = $this->fue_wc->count_customer_purchases( $fue_customer_id, $product_id['product_id'] );

                if ( $num_product_purchases == 1 ) {
                    // First Purchase emails
                    $queued = array_merge( $queued, $this->queue_first_purchase_emails( $product_id['product_id'], 0, $order ) );
                } elseif ( $num_product_purchases > 1 ) {
                    // Purchase Above One emails
                    $queued = array_merge( $queued, $this->queue_purchase_above_one_emails( $product_id['product_id'], 0, $order ) );
                }

                // category match
                $cat_ids = wp_get_post_terms( $product_id['product_id'], 'product_cat', array('fields' => 'ids') );

                if ( $cat_ids ) {
                    foreach ( $cat_ids as $cat_id ) {

                        $num_category_purchases = $this->fue_wc->count_customer_purchases( $fue_customer_id, 0, $cat_id );

                        if ( $num_category_purchases == 1 ) {
                            // first time purchasing from this category
                            $queued = array_merge( $queued, $this->queue_first_purchase_emails( $product_id['product_id'], $cat_id, $order ) );

                        } elseif ( $num_category_purchases > 1 ) {
                            // purchased from this category more than once
                            $queued = array_merge( $queued, $this->queue_purchase_above_one_emails( $product_id['product_id'], $cat_id, $order ) );
                        }

                    }
                }
                // end category match

            }

            // storewide first purchase
            $num_storewide_purchases = $this->fue_wc->count_customer_purchases( $fue_customer_id );

            if ( $num_storewide_purchases == 1 ) {
                // first time ordering
                $queued = array_merge( $queued, $this->queue_first_purchase_emails( 0, 0, $order ) );
            }
        }

        // look for customer emails
        // check for order_total
        $triggers = array('order_total_above', 'order_total_below', 'total_orders', 'total_purchases' );
        $emails = fue_get_emails( 'customer', FUE_Email::STATUS_ACTIVE, array(
            'meta_query' => array(
                'relation'  => 'AND',
                array(
                    'key'       => '_interval_type',
                    'value'     => $triggers,
                    'compare'   => 'IN'
                )
            )
        ) );

        foreach ( $emails as $email ) {
            if ( !$this->customer_email_matches_order( $email, $order ) ) {
                continue;
            }

            $insert = array(
                'send_on'       => $email->get_send_timestamp(),
                'email_id'      => $email->id,
                'order_id'      => $order->id
            );
            if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $email ) ) ) {
                $queued[] = $insert;
            }
        }

        // special trigger: last purchased
        $queued = array_merge( $queued, $this->queue_customer_last_purchased_emails( $order ) );

        return $queued;
    }

    /**
     * Run the $email through several checks and return true if it passes the conditional validation
     *
     * @param FUE_Email $email
     * @param WC_Order  $order
     * @return bool
     */
    protected function customer_email_matches_order( $email, $order ) {
        $wpdb       = Follow_Up_Emails::instance()->wpdb;
        $meta       = maybe_unserialize( $email->meta );

        // check for order total triggers first and
        // filter out emails that doesn't match the trigger conditions
        if ( $email->trigger == 'order_total_above' ) {

            if (
                !isset($meta['order_total_above']) ||
                $order->order_total < $meta['order_total_above']
            ) {
                return false;
            }

        } elseif ( $email->trigger == 'order_total_below' ) {

            if (
                !isset($meta['order_total_below']) ||
                $order->order_total > $meta['order_total_below']
            ) {
                return false;
            }

        } elseif ( $email->trigger == 'total_orders' ) {
            $mode           = $meta['total_orders_mode'];
            $requirement    = $meta['total_orders'];

            if ( isset($meta['one_time']) && $meta['one_time'] == 'yes' ) {
                // get the correct email address
                if ( WC_FUE_Compatibility::get_order_user_id( $order ) > 0 ) {
                    $user = new WP_User( WC_FUE_Compatibility::get_order_user_id( $order ) );
                    $user_email = $user->user_email;
                } else {
                    $user_email = $order->billing_email;
                }

                $search = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*)
                    FROM {$wpdb->prefix}followup_email_orders
                    WHERE email_id = %d
                    AND user_email = %s",
                    $email->id,
                    $user_email
                ) );

                if ( $search > 0 ) {
                    return false;
                }
            }

            // get user's total number of orders
            $customer   = fue_get_customer( WC_FUE_Compatibility::get_order_user_id( $order ), $order->billing_email );
            $num_orders = 0;

            if ( $customer ) {
                $num_orders = $customer->total_orders;
            }

            if ( $mode == 'less than' && $num_orders >= $requirement ) {
                return false;
            } elseif ( $mode == 'equal to' && $num_orders != $requirement ) {
                return false;
            } elseif ( $mode == 'greater than' && $num_orders <= $requirement ) {
                return false;
            }
        } elseif ( $email->trigger == 'total_purchases' ) {
            $mode           = $meta['total_purchases_mode'];
            $requirement    = $meta['total_purchases'];

            if ( isset($meta['one_time']) && $meta['one_time'] == 'yes' ) {
                // get the correct email address
                if ( WC_FUE_Compatibility::get_order_user_id( $order ) > 0 ) {
                    $user = new WP_User( WC_FUE_Compatibility::get_order_user_id( $order ) );
                    $user_email = $user->user_email;
                } else {
                    $user_email = $order->billing_email;
                }

                $search = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*)
                            FROM {$wpdb->prefix}followup_email_orders
                            WHERE email_id = %d
                            AND user_email = %s",
                    $email->id,
                    $user_email
                ) );

                if ( $search > 0 ) {
                    return false;
                }
            }

            // get user's total amount of purchases
            if ( WC_FUE_Compatibility::get_order_user_id( $order ) > 0 ) {
                $purchases = $wpdb->get_var( $wpdb->prepare("SELECT total_purchase_price FROM {$wpdb->prefix}followup_customers WHERE user_id = %d", WC_FUE_Compatibility::get_order_user_id( $order ) ) );
            } else {
                $purchases = $wpdb->get_var( $wpdb->prepare("SELECT total_purchase_price FROM {$wpdb->prefix}followup_customers WHERE email_address = %s", $order->billing_email) );
            }

            if ( $mode == 'less than' && $purchases >= $requirement ) {
                return false;
            } elseif ( $mode == 'equal to' && $purchases != $requirement ) {
                return false;
            } elseif ( $mode == 'greater than' && $purchases <= $requirement ) {
                return false;
            }
        } elseif ( $email->interval_type == 'purchase_above_one' ) {
            // look for duplicate emails
            if ( WC_FUE_Compatibility::get_order_user_id( $order ) > 0 ) {
                $wp_user = new WP_User( WC_FUE_Compatibility::get_order_user_id( $order ) );
                $user_email = $wp_user->user_email;
            } else {
                $user_email = $order->billing_email;
            }

            $num = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$wpdb->prefix}followup_email_orders
                WHERE email_id = %d
                AND user_email = %s",
                $email->id,
                $user_email
            ) );

            if ( $num > 0 ) {
                return false;
            }
        }

        return true;

    }

    /**
     * Get and queue emails matching the product and category
     *
     * @param int $product_id
     * @param int $category_id
     * @param WC_Order $order
     * @return array
     */
    protected function queue_first_purchase_emails( $product_id = 0, $category_id = 0, $order ) {
        $queued = array();

        $args = array(
            'meta_query' => array(
                'relation'  => 'AND',
                array(
                    'key'   => '_interval_type',
                    'value' => 'first_purchase',
                )
            )
        );

        if ( $category_id == 0 ) {
            $args['meta_query'][] = array(
                'key'   => '_category_id',
                'value' => array( '', '0' ),
                'compare' => 'IN'
            );
        } else {
            $args['meta_query'][] = array(
                'key'   => '_category_id',
                'value' => $category_id
            );
        }

        if ( $product_id == 0 ) {
            $args['meta_query'][] = array(
                'key'   => '_product_id',
                'value' => array( '', '0' ),
                'compare' => 'IN'
            );
        } else {
            $args['meta_query'][] = array(
                'key'   => '_product_id',
                'value' => $product_id
            );
        }

        $emails = fue_get_emails( 'any', FUE_Email::STATUS_ACTIVE, $args );

        if ( $emails ) {
            foreach ( $emails as $email ) {
                // first time purchasing this item
                $insert = array(
                    'send_on'       => $email->get_send_timestamp(),
                    'email_id'      => $email->id,
                    'order_id'      => $order->id
                );

                if ( $product_id ) {
                    $insert['product_id'] = $product_id;
                }

                if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $email ) ) ) {
                    $queued[] = $insert;
                }
            }
        }

        return $queued;
    }

    /**
     * Get and queue emails that have the 'purchase_above_one' trigger
     *
     * @param int $product_id
     * @param int $category_id
     * @param WC_Order $order
     * @return array
     */
    protected function queue_purchase_above_one_emails( $product_id = 0, $category_id = 0, $order ) {
        $queued = array();

        $args = array(
            'meta_query' => array(
                'relation'  => 'AND',
                array(
                    'key'   => '_interval_type',
                    'value' => 'product_purchase_above_one',
                )
            )
        );

        if ( $category_id ) {
            $args['meta_query'][] = array(
                'key'   => '_category_id',
                'value' => $category_id
            );
        } elseif ( $product_id ) {
            $args['meta_query'][] = array(
                'key'   => '_product_id',
                'value' => $product_id
            );
        }

        $emails = fue_get_emails( 'any', FUE_Email::STATUS_ACTIVE, $args );

        if ( $emails ) {
            foreach ( $emails as $email ) {
                $insert = array(
                    'send_on'       => $email->get_send_timestamp(),
                    'email_id'      => $email->id,
                    'order_id'      => $order->id
                );

                if ( $product_id ) {
                    $insert['product_id'] = $product_id;
                }

                if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $email ) ) ) {
                    $queued[] = $insert;
                }
            }
        }

        return $queued;
    }

    /**
     * Get all matching product emails against the provided $order and $triggers and sort by priority
     *
     * @param WC_Order  $order
     * @param array     $triggers
     * @param bool      $always_send
     * @return array    Array of matched FUE_Email
     */
    protected function get_matching_product_emails( $order, $triggers, $always_send = false ) {
        $item_ids       = $this->get_product_ids_from_order( $order );
        $product_ids    = array();
        $variation_ids  = array();

        foreach ( $item_ids as $item_id ) {
            $product_ids[] = $item_id['product_id'];

            if ( $item_id['variation_id'] ) {
                $variation_ids[] = $item_id['variation_id'];
            }
        }

        $product_ids    = array_unique( $product_ids );
        $variation_ids  = array_unique( $variation_ids );

        // product match
        $always_send_value = ( $always_send ) ? array(1) : array(0,'');
        $args = array(
            'meta_query'    => array(
                'relation'  => 'AND',
                array(
                    'key'       => '_interval_type',
                    'value'     => $triggers,
                    'compare'   => 'IN'
                ),
                array(
                    'key'       => '_product_id',
                    'value'     => 0,
                    'compare'   => '!='
                ),
                array(
                    'key'       => '_product_id',
                    'value'     => array_merge( $product_ids, $variation_ids ),
                    'compare'   => 'IN'
                ),
                array(
                    'key'       => '_always_send',
                    'value'     => $always_send_value,
                    'compare'   => 'IN'
                )
            )
        );

        $product_emails = fue_get_emails( 'storewide', FUE_Email::STATUS_ACTIVE, $args );

        // Loop through the product matches and queue the top result
        $matched_product_emails = array();
        foreach ( $product_emails as $email ) {

            $meta               = maybe_unserialize($email->meta);
            $include_variations = isset($meta['include_variations']) && $meta['include_variations'] == 'yes';

            if ( $this->exclude_customer_based_on_purchase_history( fue_get_customer_from_order( $order ), $email ) ) {
                continue;
            }

            // exact product match
            if ( in_array( $email->product_id, $product_ids ) || in_array( $email->product_id, $variation_ids ) ) {
                $matched_product_emails[] = $email;
            } elseif ( $include_variations && in_array( $email->product_id, $variation_ids ) ) {
                $matched_product_emails[] = $email;
            }

        }

        return $matched_product_emails;
    }

    /**
     * Get all matching category emails against the provided $order and $triggers and sort by priority
     *
     * @param WC_Order  $order
     * @param array     $triggers
     * @param bool      $always_send
     * @return array    Array of matched FUE_Email
     */
    protected function get_matching_category_emails( $order, $triggers, $always_send = false ) {
        $matching_emails    = array();
        $category_ids       = $this->get_category_ids_from_order( $order );

        $always_send_value = ( $always_send ) ? array(1) : array(0,'');
        $args = array(
            'meta_query'    => array(
                'relation'  => 'AND',
                array(
                    'key'       => '_interval_type',
                    'value'     => $triggers,
                    'compare'   => 'IN'
                ),
                array(
                    'key'       => '_category_id',
                    'value'     => 0,
                    'compare'   => '!='
                ),
                array(
                    'key'       => '_category_id',
                    'value'     => $category_ids,
                    'compare'   => 'IN'
                ),
                array(
                    'key'       => '_always_send',
                    'value'     => $always_send_value,
                    'compare'   => 'IN'
                )
            )
        );

        $category_emails = fue_get_emails( 'storewide', FUE_Email::STATUS_ACTIVE, $args );

        foreach ( $category_emails as $email ) {

            if ( $this->exclude_customer_based_on_purchase_history( fue_get_customer_from_order( $order ), $email ) ) {
                continue;
            }

            $matching_emails[] = $email;

        }

        return $matching_emails;
    }

    /**
     * Add to queue all 'last purchased' emails
     *
     * @param WC_Order $order
     * @return array
     */
    protected function queue_customer_last_purchased_emails( $order ) {
        $wpdb           = Follow_Up_Emails::instance()->wpdb;
        $scheduler      = Follow_Up_Emails::instance()->scheduler;
        $order_status   = WC_FUE_Compatibility::get_order_status( $order );
        $queued         = array();

        // If the order is a renewal order, switch to the parent order
        if ( $this->is_subscription_renewal_order( $order ) ) {
            $order = WC_FUE_Compatibility::wc_get_order( $order->post->post_parent );
        }

        if ( $order && ( $order_status == 'processing' || $order_status == 'completed' ) ) {
            $order_user_id = WC_FUE_Compatibility::get_order_user_id( $order );
            $recipient = ($order_user_id > 0) ? $order_user_id : $order->billing_email;

            // if there are any "last purchased" emails, automatically add this order to the queue
            $emails = fue_get_emails( 'customer', FUE_Email::STATUS_ACTIVE, array(
                'meta_query' => array(
                    array(
                        'key'   => '_interval_type',
                        'value' => 'after_last_purchase',
                    )
                )
            ) );

            foreach ( $emails as $email ) {

                // look for unsent emails in the queue with the same email ID
                $queued_emails = $scheduler->get_items( array(
                    'is_sent'   => 0,
                    'email_id'  => $email->id
                ) );

                // loop through the queue and delete unsent entries with identical customers
                foreach ( $queued_emails as $queue ) {
                    if ( $queue->user_id > 0 && $order_user_id > 0 && $queue->user_id == $order_user_id ) {
                        $scheduler->delete_item( $queue->id );
                    } elseif ( $order_user_id > 0 && $queue->order_id > 0 ) {
                        $queue_order_id = get_post_meta( $queue->order_id, '_customer_user', true );

                        if ( $queue_order_id == $order_user_id ) {
                            $scheduler->delete_item( $queue->id );
                        }
                    } else {
                        // try to match the email address
                        $email_address = get_post_meta( $queue->order_id, '_billing_email', true );

                        if ( $email_address == $order->billing_email ) {
                            $scheduler->delete_item( $queue->id );
                        }
                    }
                }

                // add this email to the queue
                $insert = array(
                    'send_on'       => $email->get_send_timestamp(),
                    'email_id'      => $email->id,
                    'product_id'    => 0,
                    'order_id'      => $order->id
                );
                if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $email ) ) ) {
                    $queued[] = $insert;
                }
            }
        }

        return $queued;
    }

    /**
     * Check if the given order is a renewal order
     * @param WC_Order $order
     * @return bool
     */
    protected function is_subscription_renewal_order( $order ) {
        if ( $order->post->post_parent > 0 && $order->original_order == $order->post->post_parent ) {
            return true;
        }

        return false;
    }

    /**
     * Get all matching storewide emails against the provided $order and $triggers and sort by priority
     *
     * @param WC_Order  $order
     * @param array     $triggers
     * @param bool      $always_send
     * @return array    Array of matched FUE_Email
     */
    protected function get_matching_storewide_emails( $order, $triggers, $always_send = false ) {

        $matched_emails = array();
        $category_ids   = $this->get_category_ids_from_order( $order );

        $emails = fue_get_emails( 'storewide', FUE_Email::STATUS_ACTIVE, array(
            'meta_query' => array(
                'relation'  => 'AND',
                array(
                    'key'       => '_interval_type',
                    'value'     => $triggers,
                    'compare'   => 'IN'
                ),
                array(
                    'key'       => '_product_id',
                    'value'     => 0
                ),
                array(
                    'key'       => '_category_id',
                    'value'     => 0
                )
            )
        ) );

        foreach ( $emails as $email ) {
            // excluded categories
            $meta = maybe_unserialize($email->meta);
            $excludes = (isset($meta['excluded_categories'])) ? $meta['excluded_categories'] : array();

            if ( count($excludes) > 0 ) {
                foreach ( $category_ids as $cat_id ) {
                    if ( in_array( $cat_id, $excludes ) )
                        continue 2;
                }
            }

            if ( $this->exclude_customer_based_on_purchase_history( fue_get_customer_from_order( $order ), $email ) ) {
                continue;
            }

            $matched_emails[] = $email;

        }

        return $matched_emails;
    }

    /**
     * Queue date-based emails
     * @param WC_Order $order
     * @return array
     */
    public function queue_date_emails( $order ) {
        $queued     = array();
        $triggers   = $this->get_order_triggers( $order );

        $emails = fue_get_emails( 'any', FUE_Email::STATUS_ACTIVE, array(
            'meta_query' => array(
                array(
                    'key'   => '_interval_type',
                    'value' => 'date'
                )
            )
        ) );

        foreach ( $emails as $email ) {
            // skip date emails that have passed
            if ( FUE_Sending_Scheduler::send_date_passed( $email->id ) ) {
                continue;
            }

            $insert = array(
                'send_on'       => $email->get_send_timestamp(),
                'email_id'      => $email->id,
                'product_id'    => $email->product_id,
                'order_id'      => $order->id
            );
            if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $email ) ) ) {
                $queued[] = $insert;
            }

        }

        return $queued;
    }

    /**
     * Queue reminder emails
     *
     * @param WC_Order $order
     * @return array
     */
    public function queue_reminder_emails( $order ) {
        $queued         = array();
        $item_ids       = $this->get_product_ids_from_order( $order );
        $triggers       = $this->get_order_triggers( $order, Follow_Up_Emails::get_email_type( 'reminder' ) );
        $product_ids    = array();
        $variation_ids  = array();

        foreach ( $item_ids as $item_id ) {
            $product_ids[] = $item_id['product_id'];

            if ( $item_id['variation_id'] ) {
                $variation_ids[] = $item_id['variation_id'];
            }
        }

        $product_ids    = array_merge( array_unique( $product_ids ), array_unique( $variation_ids ) );

        $args = array(
            'meta_query'    => array(
                'relation'  => 'AND',
                array(
                    'key'       => '_interval_type',
                    'value'     => $triggers,
                    'compare'   => 'IN'
                )
            )
        );

        $emails = fue_get_emails( 'reminder', FUE_Email::STATUS_ACTIVE, $args );

        foreach ( $emails as $email ) {

            if ( $email->product_id > 0 && !in_array( $email->product_id, $product_ids ) ) {
                // Product ID does not match
                continue;
            }

            $queue_items = Follow_Up_Emails::instance()->scheduler->get_items( array(
                'order_id'  => $order->id,
                'email_id'  => $email->id
            ) );

            // only queue reminders once per order and email
            if ( count( $queue_items ) == 0 ) {
                $interval           = $email->interval;
                $interval_duration  = $email->interval_duration;

                // get the item's quantity
                $qty            = 0;
                $num_products   = false;

                foreach ( $order->get_items() as $item ) {
                    $variation_id   = $item['variation_id'];
                    $item_id        = $item['product_id'];

                    if ($email->product_id == 0 || ( $item_id == $email->product_id || $variation_id == $email->product_id ) ) {
                        $qty = $item['qty'];

                        if ( isset($item['item_meta']) && !empty($item['item_meta']) ) {
                            foreach ( $item['item_meta'] as $meta_key => $meta_value ) {

                                if ( $meta_key == 'Filters/Case' ) {
                                    $num_products = $meta_value[0];
                                    break;
                                }

                            }
                        }

                    }
                }

                // look for a lifespan product variable
                $lifespan = get_post_meta( $email->product_id, 'filter_lifespan', true );

                if ( $lifespan && $lifespan > 0 ) {
                    $interval = (int)$lifespan;
                    $interval_duration = 'months';
                }

                if ( $num_products !== false && $num_products > 0 ) {
                    $qty = $qty * $num_products;
                }

                if ( $qty == 1 ) {
                    // only send the first email
                    $add        = FUE_Sending_Scheduler::get_time_to_add( $interval, $interval_duration );
                    $send_on    = current_time('timestamp') + $add;

                    $insert = array(
                        'send_on'       => $send_on,
                        'email_id'      => $email->id,
                        'product_id'    => $email->product_id,
                        'order_id'      => $order->id
                    );
                    if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $email ) ) ) {
                        $queued[] = $insert;
                    }
                } elseif ( $qty == 2 ) {
                    // only send the first and last emails
                    $add        = FUE_Sending_Scheduler::get_time_to_add( $interval, $interval_duration );
                    $send_on    = current_time('timestamp')+ $add;

                    $insert = array(
                        'send_on'       => $send_on,
                        'email_id'      => $email->id,
                        'product_id'    => $email->product_id,
                        'order_id'      => $order->id
                    );
                    if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $email ) ) ) {
                        $queued[] = $insert;
                    }


                    $last       = FUE_Sending_Scheduler::get_time_to_add( $interval, $interval_duration );
                    $send_on    = current_time('timestamp') + $add + $last;

                    $insert = array(
                        'send_on'       => $send_on,
                        'email_id'      => $email->id,
                        'product_id'    => $email->product_id,
                        'order_id'      => $order->id
                    );
                    if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $email ) ) ) {
                        $queued[] = $insert;
                    }
                } else {
                    // send all emails
                    $add    = FUE_Sending_Scheduler::get_time_to_add( $interval, $interval_duration );
                    $last   = 0;
                    for ($x = 1; $x <= $qty; $x++) {
                        $send_on    = current_time('timestamp') + $add + $last;
                        $last       += $add;

                        $insert = array(
                            'send_on'       => $send_on,
                            'email_id'      => $email->id,
                            'product_id'    => $email->product_id,
                            'order_id'      => $order->id
                        );
                        if ( !is_wp_error( FUE_Sending_Scheduler::queue_email( $insert, $email ) ) ) {
                            $queued[] = $insert;
                        }
                    }
                }

            }

        }

        return $queued;

    }

    /**
     * Check if the email must be skipped from sending to a customer based on the
     * customer's purchase history
     *
     * @param Object    $fue_customer Use fue_get_customer() or fue_get_customer_from_order() to get the customer object
     * @param FUE_Email $email
     * @return bool
     */
    public function exclude_customer_based_on_purchase_history( $fue_customer, $email ) {
        $wpdb = Follow_Up_Emails::instance()->wpdb;
        $skip = false;
        $meta = maybe_unserialize( $email->meta );

        if ( ! $fue_customer ) {
            return false;
        }

        if ( !empty( $meta['excluded_customers_products'] ) ) {
            if ( !is_array( $meta['excluded_customers_products'] ) ) {
                $meta['excluded_customers_products'] = array( $meta['excluded_customers_products'] );
            }

            $product_ids = implode( ',', $meta['excluded_customers_products'] );
            $sql = "SELECT COUNT(*)
                    FROM {$wpdb->prefix}followup_order_items i, {$wpdb->prefix}followup_customer_orders o
                    WHERE o.followup_customer_id = %d
                    AND o.order_id = i.order_id
                    AND (
                        i.product_id IN ( $product_ids )
                        OR
                        i.variation_id IN ( $product_ids )
                    )";
            $found = $wpdb->get_var( $wpdb->prepare( $sql, $fue_customer->id ) );

            if ( $found > 0 ) {
                $skip = true;
            }
        }

        if ( !$skip && !empty( $meta['excluded_customers_categories'] ) ) {
            if ( !is_array( $meta['excluded_customers_categories'] ) ) {
                $meta['excluded_customers_categories'] = array( $meta['excluded_customers_categories'] );
            }

            $category_ids = implode( ',', $meta['excluded_customers_categories'] );
            $sql = "SELECT COUNT(*)
                    FROM {$wpdb->prefix}followup_order_categories c, {$wpdb->prefix}followup_customer_orders o
                    WHERE o.followup_customer_id = %d
                    AND o.order_id = c.order_id
                    AND c.category_id IN ( $category_ids )";
            $found = $wpdb->get_var( $wpdb->prepare( $sql, $fue_customer->id ) );

            if ( $found > 0 ) {
                $skip = true;
            }
        }

        return apply_filters( 'fue_exclude_customer_on_purchase_history', $skip, $fue_customer, $email );
    }

    /**
     * Look for orders that match the email trigger and other conditions
     * @param FUE_Email $email
     * @return array
     */
    protected function get_matching_orders_for_email( $email ) {
        $wpdb       = Follow_Up_Emails::instance()->wpdb;
        $trigger    = $email->trigger;
        $orders     = array();

        if ( $email->type == 'storewide' ) {
            //$orders = $this->get_matching_storewide_orders( $email );
        } elseif ( $email->type == 'customer' ) {

        }

        $orders = apply_filters( 'fue_get_matching_orders_for_email', $orders, $email );

        return $orders;
    }

    /**
     * Override the default email address to use the order's billing address
     *
     * @param array $data
     *
     * @return array
     */
    public function get_correct_email( $data ) {
        if ( !empty( $data['order_id'] ) ) {
            $order = WC_FUE_Compatibility::wc_get_order( $data['order_id'] );
            $data['user_email'] = $order->billing_email;
        }

        return $data;
    }

    /**
     * Run WC-related conditions on the $item and see if it passes
     *
     * @param bool|WP_Error $passed
     * @param FUE_Sending_Queue_Item $item
     * @return bool|WP_Error
     */
    public function check_item_conditions( $passed, $item ) {

        // only storewide for now
        if ( fue_get_email_type( $item->email_id ) != 'storewide' ) {
            return $passed;
        }

        if ( is_wp_error( $passed ) ) {
            return $passed;
        }

        $conditions         = $this->fue_wc->wc_conditions->get_conditions();
        $email              = new FUE_Email( $item->email_id );
        $email_conditions   = !empty($email->conditions) ? $email->conditions : array();

        foreach ( $email_conditions as $email_condition ) {

            if ( array_key_exists( $email_condition['condition'], $conditions ) ) {
                // this is a WC condition
                $passed = $this->fue_wc->wc_conditions->test_condition( $email_condition, $item );

                if ( is_wp_error( $passed ) ) {
                    // immediately return errors
                    return $passed;
                }
            }

        }

        return true;
    }

    /**
     * If a queued email is linked to an order, add an order note that contains
     * the email name, trigger and schedule
     *
     * @param array $queued
     */
    protected function add_order_notes_to_queued_emails( $queued ) {
        if (! is_array( $queued ) ) {
            return;
        }

        foreach ( $queued as $row ) {
            if ( isset($row['order_id']) && $row['order_id'] > 0 ) {
                $_order = WC_FUE_Compatibility::wc_get_order($row['order_id']);

                $email = new FUE_Email( $row['email_id'] );

                $email_trigger  = apply_filters( 'fue_interval_str', $email->get_trigger_string(), $email );
                $send_date      = date( get_option('date_format') .' '. get_option('time_format'), $row['send_on'] );

                $note = sprintf(
                    __('Email queued: %s scheduled on %s<br/>Trigger: %s', 'follow_up_emails'),
                    $email->name,
                    $send_date,
                    $email_trigger
                );

                $_order->add_order_note( $note );
            }
        }
    }

    /**
     * Unsubscribe customers from signup emails after they have made their first purchase
     * @param WC_Order $order
     */
    protected function remove_signup_emails_on_purchase( $order ) {
        $user_id        = $order->customer_user;
        $signup_emails  = fue_get_emails( 'signup', FUE_Email::STATUS_ACTIVE );
        $email_ids      = array();

        if ( empty( $user_id ) ) {
            return;
        }

        foreach ( $signup_emails as $signup_email ) {
            if (
                !empty( $signup_email->meta['remove_signup_emails_on_purchase'] ) &&
                $signup_email->meta['remove_signup_emails_on_purchase'] == 'yes'
            ) {
                $email_ids[] = $signup_email->id;
            }
        }

        if ( !empty( $email_ids ) ) {
            $wpdb           = Follow_Up_Emails::instance()->wpdb;
            $email_ids_csv  = implode( ',', $email_ids );

            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}followup_email_orders
                WHERE user_id = %d
                AND email_id IN ( $email_ids_csv )",
                $user_id
            ) );
        }
    }

    /**
     * Get the triggers available for the given order based
     * on its status and the email type
     *
     * @param int|WC_Order      $order
     * @param FUE_Email_Type    $email_type
     * @return array
     */
    protected function get_order_triggers( $order, $email_type = null ) {
        if ( is_numeric( $order ) ) {
            $order = WC_FUE_Compatibility::wc_get_order( $order );
        }

        $order_status   = WC_FUE_Compatibility::get_order_status( $order );
        $triggers       = array( $order_status );

        $triggers = apply_filters( 'fue_order_triggers', $triggers, $order->id, $email_type );

        return $triggers;
    }

    /**
     * Get an array of Product IDs and Variation IDs included in the given $order
     * @param int|WC_Order  $order
     * @return array
     */
    protected function get_product_ids_from_order( $order ) {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        if ( is_numeric( $order ) ) {
            $order = WC_FUE_Compatibility::wc_get_order( $order );
        }

        if ( 1 != get_post_meta( $order->id, '_fue_recorded', true ) ) {
            FUE_Addon_Woocommerce::record_order( $order );
        }

        $product_ids = $wpdb->get_results( $wpdb->prepare(
            "SELECT product_id, variation_id
            FROM {$wpdb->prefix}followup_order_items
            WHERE order_id = %d",
            $order->id
        ), ARRAY_A );

        return $product_ids;
    }

    /**
     * Get an array of Category IDs included in the given $order
     * @param int|WC_Order  $order
     * @return array
     */
    protected function get_category_ids_from_order( $order ) {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        if ( is_numeric( $order ) ) {
            $order = WC_FUE_Compatibility::wc_get_order( $order );
        }

        if ( 1 != get_post_meta( $order->id, '_fue_recorded', true ) ) {
            FUE_Addon_Woocommerce::record_order( $order );
        }

        $category_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT category_id
            FROM {$wpdb->prefix}followup_order_categories
            WHERE order_id = %d",
            $order->id
        ) );

        return array_unique( $category_ids );

    }

    /**
     * @param string    $status Filter emails by status (e.g. FUE_Email::STATUS_ACTIVE
     * @param array     $args
     * @return array
     */
    protected function get_cart_emails( $status = '', $args = array() ) {
        $query = array(

            'meta_query' => array(
                array(
                    'key'   => '_interval_type',
                    'value' => 'cart'
                )
            )
        );

        if ( isset( $args['product_id'] ) ) {
            $query['meta_query'][] = array(
                    'key'   => '_product_id',
                    'value' => absint( $args['product_id'] )
            );
        }

        if ( isset( $args['category_id'] ) ) {
            $query['meta_query'][] = array(
                'key'       => '_category_id',
                'value'     => $args['category_id'],
                'compare'   => 'IN'
            );
        }

        if ( isset( $args['always_send'] ) ) {
            $query['meta_query'][] = array(
                'key'   => '_always_send',
                'value' => $args['always_send']
            );
        }

        //$args = array_merge( $query, $args );

        return fue_get_emails( 'any', $status, $query );
    }
    
}