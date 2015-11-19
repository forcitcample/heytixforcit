<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle frontend forms
 */
class FUE_Form_Handler {

    public static function init() {
        // catch unsubscribe request
        add_action( 'wp', 'FUE_Form_Handler::process_unsubscribe_request' );
        add_action( 'template_redirect', 'FUE_Form_Handler::process_optout_request' );

        // fue subscriptions
        add_action( 'wp', 'FUE_Form_Handler::process_subscription_request' );
    }

    /**
     * Process unsubscribe request. Add the submitted email address to the Excluded Emails list
     */
    public static function process_unsubscribe_request() {
        global $wpdb;

        if (isset($_POST['fue_action']) && $_POST['fue_action'] == 'fue_unsubscribe') {
            $email      = str_replace( ' ', '+', $_POST['fue_email'] );
            $email_id   = $_POST['fue_eid'];
            $error      = '';

            if ( empty( $email ) || !is_email( $email ) ) {
                $error = urlencode( __('Please enter a valid email address', 'follow_up_emails') );
            }

            $order_id    = (!empty( $_POST['unsubscribe_order_id'] ) ) ? absint( $_POST['unsubscribe_order_id'] ) : 0;
            $unsubscribe = (!empty( $_POST['unsubscribe_all'] ) && $_POST['unsubscribe_all'] == 'yes' ) ? true : false;

            if ( fue_is_email_excluded( $email, 0, $order_id ) ) {
                if ( $order_id > 0 ) {
                    $error = sprintf( __('The email (%s) is already unsubscribed from receiving emails regarding Order %d', 'follow_up_emails'), $email, $order_id );
                } else {
                    $error = sprintf( __('The email (%s) is already unsubscribed from receiving emails', 'follow_up_emails'), $email );
                }
            }

            if ( !empty( $error ) ) {
                $url = add_query_arg( array(
                    'fueid' => $_POST['fue_eid'],
                    'qid'   => (!empty($_POST['fue_qid'])) ? $_POST['fue_qid'] : '',
                    'error' => urlencode( $error )
                ), fue_get_unsubscribe_url());

                wp_redirect( $url );
                exit;
            }

            if ( $unsubscribe ) {
                fue_exclude_email_address( $email, $email_id, 0 );

                if ( isset($_GET['fue']) ) {
                    do_action('fue_user_unsubscribed', $_GET['fue']);
                }

            } elseif ( $order_id > 0 ) {
                fue_exclude_email_address( $email, $email_id, $order_id );

                $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}followup_email_orders WHERE user_email = %s AND order_id = %d AND is_sent = 0", $email, $order_id) );
            }

            wp_redirect( add_query_arg( 'fue_unsubscribed', 1, Follow_Up_Emails::get_account_url() ) );
            exit;

        } elseif (isset($_GET['fue_unsubscribed'])) {
            Follow_Up_Emails::show_message( __('Thank you. Your email settings have been saved.', 'follow_up_emails') );
        }
    }

    /**
     * Handle opt-in and opt-out requests
     */
    public static function process_optout_request() {

        if (isset($_POST['fue_action']) && $_POST['fue_action'] == 'fue_save_myaccount') {
            $opted_out  = (isset($_POST['fue_opt_out']) && $_POST['fue_opt_out'] == 1) ? true : false;
            $user       = wp_get_current_user();

            if ( $opted_out ) {
                // unsubscribe this user using his/her email
                fue_add_user_opt_out( $user->ID );
            } else {
                fue_remove_user_opt_out( $user->ID );
            }

            wp_redirect( add_query_arg('fue_updated', 1, Follow_Up_Emails::get_account_url()) );
            exit;
        } elseif (isset($_GET['fue_updated'])) {
            Follow_Up_Emails::show_message(__('Account updated', 'follow_up_emails'));
        }
    }

    public static function process_subscription_request() {
        if ( empty( $_POST['fue_action'] ) || $_POST['fue_action'] != 'subscribe' ) {
            return;
        }

        if (
            ! isset( $_POST['_wpnonce'] )
            || ! wp_verify_nonce( $_POST['_wpnonce'], 'fue_subscribe' )
        ) {
            wp_die('Sorry, your browser submitted an invalid request. Please try again.');
        }

        $back   = $_POST['_wp_http_referer'];
        $email  = !empty( $_POST['fue_subscriber_email'] ) ? $_POST['fue_subscriber_email'] : '';
        $id     = fue_add_subscriber( $email );



        if ( is_wp_error( $id ) ) {
            $args = array(
                'error' => urlencode( $id->get_error_message() ),
                'email' => urlencode( $email )
            );
        } else {
            $args = array(
                'error'             => '',
                'fue_subscribed'    => 'yes'
            );
        }

        wp_redirect( add_query_arg( $args, $back ) );
        exit;
    }

}

FUE_Form_Handler::init();