<?php

/**
 * Class FUE_Admin_Controller
 *
 * Controller for the Admin Panel
 */
class FUE_Admin_Controller {

    /**
     * Register the menu items
     */
    public static function add_menu() {
        add_menu_page( __('Follow-Up Emails', 'follow_up_emails'), __('Follow-Up Emails', 'follow_up_emails'), 'manage_follow_up_emails', 'followup-emails', 'FUE_Admin_Controller::admin_controller', 'dashicons-email-alt', '54.51' );
        add_submenu_page( 'followup-emails', __('Follow-Up Emails', 'follow_up_emails'), __('Emails', 'follow_up_emails'), 'manage_follow_up_emails', 'followup-emails', 'FUE_Admin_Controller::admin_controller' );
        add_submenu_page( 'followup-emails', __('Campaigns', 'follow_up_emails'), __('Campaigns', 'follow_up_emails'), 'manage_follow_up_emails', 'fue_campaigns', 'FUE_Admin_Controller::admin_controller' );

        add_submenu_page( 'followup-emails', __('New Email', 'follow_up_emails'), __('New Email', 'follow_up_emails'), 'manage_follow_up_emails', 'fue_post_email', 'FUE_Admin_Controller::admin_controller' );

        do_action( 'fue_menu' );

        add_submenu_page( 'followup-emails', __('Scheduled Emails', 'follow_up_emails'), __('Scheduled Emails', 'follow_up_emails'), 'manage_follow_up_emails', 'followup-emails-queue', 'FUE_Admin_Controller::queue_table' );
        add_submenu_page( 'followup-emails', __('Subscribers', 'follow_up_emails'), __('Subscribers', 'follow_up_emails'), 'manage_follow_up_emails', 'followup-emails-subscribers', 'FUE_Admin_Controller::subscribers_table' );
        add_submenu_page( 'followup-emails', __('Manage Opt-outs', 'follow_up_emails'), __('Opt-outs', 'follow_up_emails'), 'manage_follow_up_emails', 'followup-emails-optouts', 'FUE_Admin_Controller::optout_table' );
        add_submenu_page( 'followup-emails', __('Follow-Up Emails Settings', 'follow_up_emails'), __('Settings', 'follow_up_emails'), 'manage_follow_up_emails', 'followup-emails-settings', 'FUE_Admin_Controller::settings' );
        add_submenu_page( 'followup-emails', __('Follow-Up Emails Add-ons', 'follow_up_emails'), __('Add-ons', 'follow_up_emails'), 'manage_follow_up_emails', 'followup-emails-addons', 'FUE_Admin_Controller::addons' );
    }

    /**
     * Replace the placeholder URL we're using for the Email Form page with the actual URL.
     *
     * @param $url
     * @param $original_url
     * @param $_context
     *
     * @return string|void
     */
    public static function replace_email_form_url($url, $original_url, $_context) {
        if ( $url == 'admin.php?page=fue_post_email' ){
            //remove_filter('clean_url', 'FUE_Admin_Controller::replace_email_form_url', 0);
            return admin_url('post-new.php?post_type=follow_up_email');
        } elseif ( $url == 'admin.php?page=fue_campaigns' ) {
            //remove_filter( 'clean_url', 'FUE_Admin_Controller::replace_email_form_url', 0 );
            return admin_url( 'edit-tags.php?taxonomy=follow_up_email_campaign' );
        } elseif ( strpos($url, 'edit.php?follow_up_email_campaign=') !== false ) {
            $parts = array();
            parse_str( $url, $parts );
            $terms = array_values( $parts );

            //remove_filter( 'clean_url', 'FUE_Admin_Controller::replace_email_form_url', 0 );
            return esc_url( 'admin.php?page=followup-emails&campaign='. $terms[0] );
        }

        return $url;
    }

    /**
     * Set the current submenu item in the admin nav menu
     * @param string $parent_file
     * @return string
     */
    public static function set_active_submenu( $parent_file ) {
        global $submenu_file, $plugin_page;

        if ( $parent_file == 'edit.php?post_type=follow_up_email') {
            $parent_file = 'followup-emails';
            $submenu_file = null;
        } elseif ( $submenu_file == 'edit-tags.php?taxonomy=follow_up_email_campaign' ) {
            $parent_file = 'followup-emails';
            $submenu_file = 'fue_campaigns';
        }

        return $parent_file;
    }

    /**
     * Routes the request to the correct page/file
     */
    public static function admin_controller() {
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'list';

        switch ( $tab ) {

            case 'list':
                self::list_emails_page();
                break;

            case 'edit':
                self::email_form( 1, $_GET['id'] );
                break;

            case 'send':
                $id    = $_GET['id'];
                $email = new FUE_Email( $id );

                if ( ! $email->exists() ) {
                    wp_die( "The requested data could not be found!" );
                }

                self::send_manual_form( $email );
                break;

            case 'send_manual_emails':
                self::send_manual_emails();
                break;

            case 'updater':
                self::updater_page();
                break;

            default:
                // allow add-ons to add tabs
                do_action( 'fue_admin_controller', $tab );
                break;

        }

    }

    /**
     * FUE Dashboard Widget
     */
    public static function dashboard_widget() {
        wp_add_dashboard_widget( 'fue-dashboard', __('Follow-Up Emails', 'follow_up_emails'), array('FUE_Report_Dashboard_Widget', 'display'));
    }

    /**
     * Page that lists all FUE_Emails
     */
    public static function list_emails_page() {
        $types          = Follow_Up_Emails::get_email_types();
        $campaigns      = get_terms( 'follow_up_email_campaign', array('hide_empty' => false) );
        $bccs           = get_option('fue_bcc_types', false);
        $from_addresses = get_option('fue_from_email_types', false);

        include FUE_TEMPLATES_DIR .'/email-list/email-list.php';
    }

    /**
     * Send Manual Email form
     *
     * @param $email
     */
    public static function send_manual_form( $email ) {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        include FUE_TEMPLATES_DIR .'/send_manual_form.php';
    }

    /**
     * Send manual emails in batches
     */
    public static function send_manual_emails() {
        include FUE_TEMPLATES_DIR .'/send_manual_emails.php';
    }

    /**
     * Admin interface for managing subscribers
     */
    public static function subscribers_table() {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        include FUE_TEMPLATES_DIR .'/subscribers_table.php';
    }

    /**
     * Admin interface for managing opt-outs
     */
    public static function optout_table() {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        include FUE_TEMPLATES_DIR .'/optout_table.php';
    }

    /**
     * Admin Updater interface
     */
    public static function updater_page() {
        global $wpdb;

        include FUE_TEMPLATES_DIR .'/updater.php';
    }

    /**
     * Settings Interface
     */
    public static function settings() {
        global $wpdb;

        $pages                  = get_pages();
        $emails                 = get_option( 'fue_daily_emails' );
        $bcc                    = get_option( 'fue_bcc', '' );
        $from                   = get_option( 'fue_from_email', '' );
        $from_name              = get_option( 'fue_from_name', get_bloginfo('name') );
        $email_batches          = get_option( 'fue_email_batches', 0 );
        $disable_logging        = get_option( 'fue_disable_action_scheduler_logging', 1 );
        $api_enabled            = get_option( 'fue_api_enabled', 'yes' );
        $emails_per_batch       = get_option( 'fue_emails_per_batch', 100 );
        $email_batch_interval   = get_option( 'fue_batch_interval', 10 );
        $tab                    = (isset($_GET['tab'])) ? $_GET['tab'] : 'system';

        include FUE_TEMPLATES_DIR .'/settings/settings.php';
    }

    /**
     * Render the add-ons page
     */
    public static function addons() {
        add_thickbox();
        include FUE_TEMPLATES_DIR .'/add-ons/add-ons.php';
    }

    /**
     * Display the queue items
     */
    public static function queue_table() {
        $table = new FUE_Sending_Queue_List_Table();
        $table->prepare_items();
        $table->messages();
        ?>
        <style>
            span.trash a {
                color: #a00 !important;
            }

        </style>
        <div class="wrap">
            <h2><?php _e( 'Scheduled Emails', 'follow_up_emails' ); ?></h2>

            <form id="queue-filter" action="" method="get">
                <?php $table->display(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register the scripts early so other addons can use them
     */
    public static function register_scripts() {
        if (! wp_script_is( 'jquery-tiptip', 'registered' ) ) {
            wp_register_script( 'jquery-tiptip', FUE_URL .'/templates/js/jquery.tipTip.min.js', array( 'jquery' ), FUE_VERSION, true );
        }

        // blockUI
        if (! wp_script_is('jquery-blockui', 'registered') ) {
            wp_register_script( 'jquery-blockui', FUE_URL . '/templates/js/jquery-blockui/jquery.blockUI.min.js', array( 'jquery' ), FUE_VERSION, true );
        }

        // select2
        if (! wp_script_is('select2', 'registered') ) {
            wp_register_script( 'select2', '//cdnjs.cloudflare.com/ajax/libs/select2/3.5.2/select2.min.js', array( 'jquery' ), '3.5.2' );
            wp_register_style( 'select2', '//cdnjs.cloudflare.com/ajax/libs/select2/3.5.2/select2.css' );
            wp_register_style( 'select2-fue', plugins_url( 'templates/select2.css', FUE_FILE ), array(), '3.5.2' );
        }
    }

    /**
     * Load the necessary scripts
     */
    public static function settings_scripts() {

        $screen = get_current_screen();

        $page = isset($_GET['page']) ? $_GET['page'] : '';
        $fue_pages = array(
            'followup-emails', 'followup-emails-form', 'followup-emails-reports', 'followup-emails-queue'
        );

        if ( in_array( $page, $fue_pages ) || $screen->post_type == 'follow_up_email' ) {
            wp_enqueue_script('jquery-blockui');
            wp_enqueue_script('media-upload');
            wp_enqueue_script('thickbox');
            wp_enqueue_script('editor');

            wp_enqueue_style('thickbox');

            wp_enqueue_script( 'jquery-tiptip' );
            wp_enqueue_script( 'jquery-ui-core', null, array('jquery') );
            wp_enqueue_script( 'jquery-ui-datepicker', null, array('jquery-ui-core') );
            wp_enqueue_script( 'jquery-ui-sortable', null, array('jquery-ui-core') );
            wp_enqueue_script( 'fue-list', plugins_url( 'templates/js/email-list.js', FUE_FILE ), array('jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable'), FUE_VERSION );

            wp_enqueue_style( 'jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.21/themes/base/jquery-ui.css' );
            wp_enqueue_style( 'fue_email_form', plugins_url( 'templates/email-form.css', FUE_FILE ) );

            $translate = apply_filters( 'fue_script_locale', array(
                'email_name'            => __('Email Name', 'follow_up_emails'),
                'processing_request'    => __('Processing request...', 'follow_up_emails'),
                'dupe'                  => __('A follow-up email with the same settings already exists. Do you want to create it anyway?', 'follow_up_emails'),
                'similar'               => __('A similar follow-up email already exists. Do you wish to continue?', 'follow_up_emails'),
                'save'                  => isset($_GET['mode']) ? __('Save', 'follow_up_emails') : __('Build your email', 'follow_up_emails'),
                'ajax_loader'           => plugins_url() .'/woocommerce-follow-up-emails/templates/images/ajax-loader.gif'
            ) );
            wp_localize_script( 'fue-list', 'FUE', $translate );

        }

        if ( in_array( $screen->id, array( 'dashboard' ) ) ) {
            wp_enqueue_style( 'fue_admin_dashboard_styles', plugins_url('templates/dashboard.css', FUE_FILE ), array(), FUE_VERSION );
        }

        if ( $page == 'followup-emails-settings' || $page == 'followup-emails' ) {
            wp_enqueue_script( 'select2' );
            wp_enqueue_style( 'select2' );
            wp_enqueue_style( 'select2-fue' );

            if ( !empty( $_GET['tab'] ) && $_GET['tab'] == 'send_manual_emails')
            wp_enqueue_script( 'fue_manual_send', FUE_TEMPLATES_URL .'/js/manual_send.js', array('jquery', 'jquery-ui-progressbar'), FUE_VERSION );
        }

        if ( $page == 'followup-emails-addons' ) {
            wp_enqueue_style( 'fue-addons', FUE_TEMPLATES_URL .'/add-ons/add-ons.css' );
        }

    }

}
