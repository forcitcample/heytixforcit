<?php

// front styles
add_action( 'wp_enqueue_scripts',   'Follow_Up_Emails::front_css' );

// dashboard widget
add_action('wp_dashboard_setup',    'FUE_Admin_Controller::dashboard_widget');

// menu
add_action('admin_menu',            'FUE_Admin_Controller::add_menu', 20);

// replace custom menu URLs with their actual values
add_filter('clean_url',             'FUE_Admin_Controller::replace_email_form_url', 0, 3);

// highlight the correct submenu item in the admin nav menu
add_filter('parent_file',           'FUE_Admin_Controller::set_active_submenu' );

// settings styles and scripts
add_action('admin_enqueue_scripts', 'FUE_Admin_Controller::register_scripts', 9);
add_action('admin_enqueue_scripts', 'FUE_Admin_Controller::settings_scripts', 11);

// load addons
add_action('plugins_loaded',        'Follow_Up_Emails::load_addons');

// after user signs up
add_action('user_register',         array( 'FUE_Sending_Scheduler', 'queue_signup_emails' ) );

// cron action
add_action('sfn_followup_emails',   array( 'FUE_Sending_Scheduler', 'send_scheduled_emails' ) );

// usage report
add_action('sfn_send_usage_report', 'FUE_Reports::send_usage_data');

// daily summary requeuing
add_action( 'fue_adhoc_email_sent', array( 'FUE_Sending_Scheduler', 'queue_daily_summary_email' ) );

// send manual emails
add_action( 'admin_post_fue_followup_send_manual',      array( 'FUE_Admin_Actions', 'send_manual' ) );

// Emails
add_action( 'admin_post_fue_followup_form',             array('FUE_Admin_Actions', 'process_email_form' ) );

add_action( 'admin_post_fue_followup_delete',           array('FUE_Admin_Actions', 'delete_email') );
add_action( 'admin_post_fue_followup_save_list',        array('FUE_Admin_Actions', 'save_list') );

// FUE Settings
add_action( 'admin_post_fue_followup_save_settings',    array('FUE_Admin_Actions', 'update_settings') );

// Restore optout email
add_action( 'admin_post_fue_optout_manage',             array('FUE_Admin_Actions', 'manage_optout') );

// subscribers
add_action( 'admin_post_fue_subscribers_manage',        array('FUE_Admin_Actions', 'manage_subscribers') );

// reset report data
add_action('admin_post_fue_reset_reports',              array('FUE_Admin_Actions', 'reset_reports') );

// backup and restore
add_action('admin_post_fue_backup_settings',            array('FUE_Admin_Actions', 'backup_settings') );

// queue actions
add_action('admin_post_fue_update_queue_status',        array('FUE_Admin_Actions', 'update_queue_item_status') );
add_action('admin_post_fue_delete_queue',               array('FUE_Admin_Actions', 'delete_queue_item') );
add_action('admin_post_fue_send_queue_item',            array('FUE_Admin_Actions', 'send_queue_item') );
add_action('admin_init',                                array('FUE_Admin_Actions', 'process_queue_bulk_action') );

// Register our own Logger class to stop Action Scheduler from posting comments as logs
add_filter( 'action_scheduler_logger_class', 'fue_add_logger_class' );

global $fue_key;
$fue_key = base64_decode(FUE_KEY.'A=');