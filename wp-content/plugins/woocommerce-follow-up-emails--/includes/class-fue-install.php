<?php
/**
 * Installation related functions and actions.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( !class_exists('FUE_Install') ):
class FUE_Install {

    public function __construct() {

        // update notice
        add_action( 'admin_print_styles', array( $this, 'add_notices' ) );

        // install
        register_activation_hook( FUE_FILE, array( $this, 'install' ) );

        // deactivate
        register_deactivation_hook( FUE_FILE, array( $this, 'deactivate' ) );

        if ( get_option( 'fue_init_daily_summary', false ) ) {
            add_action( 'init', array( $this, 'init_daily_summary' ) );
        }

        add_action( 'admin_init', array( $this, 'check_version' ), 5 );
        add_action( 'admin_init', array( $this, 'actions' )  );

    }

    /**
     * Register admin notices
     */
    public function add_notices() {
        if ( get_option( 'fue_needs_update' ) == 1 ) {
            add_action( 'admin_notices', array( $this, 'install_notice' ) );
        }

        if ( !empty($_GET['fue-updated']) ) {
            add_action( 'admin_notices', array( $this, 'updated_notice' ) );
        }
    }

    /**
     * Display a notice requiring a data update
     */
    public function install_notice() {
        // If we need to update, include a message with the update button
        if ( get_option( 'fue_needs_update' ) == 1 ) {
            ?>
            <div id="message" class="updated">
                <p><?php _e( '<strong>Follow-Up Emails Data Update Required</strong>', 'follow_up_emails' ); ?></p>
                <p class="submit"><a href="<?php echo add_query_arg( 'fue_update', 'true', admin_url( 'admin.php?page=followup-emails' ) ); ?>" class="fue-update-now button-primary"><?php _e( 'Run the updater', 'follow_up_emails' ); ?></a></p>
            </div>
            <script type="text/javascript">
                jQuery('.fue-update-now').click('click', function(){
                    var answer = confirm( '<?php _e( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'follow_up_emails' ); ?>' );
                    return answer;
                });
            </script>
            <?php
        }
    }

    /**
     * Display a notice after the FUE data has been updated
     */
    public function updated_notice() {
        ?>
        <div id="message" class="updated">
            <p><?php _e('Data update have been successfully applied!', 'follow_up_emails'); ?></p>
        </div>
        <?php
    }

    /**
     * Checks for changes in the version and prompt to update if necessary
     */
    public function check_version() {
        $db_version = get_option( 'fue_db_version' );
        if ( ! defined( 'IFRAME_REQUEST' ) && $db_version != Follow_Up_Emails::$db_version ) {
            $this->install();

            do_action( 'fue_updated' );
        }
    }

    /**
     * Listens for button actions such as clicking on the 'Update Data' button
     */
    public function actions() {

        if ( ! empty( $_GET['fue_update'] ) ) {
            $this->update();

            // Update complete
            delete_option( 'fue_needs_update' );

            // redirect
            wp_redirect( admin_url( 'index.php?page=followup-emails&fue-updated=true' ) );
            exit;
        }

    }

    /**
     * The install method that is ran when Follow_Up_Emails is activated
     */
    public function install() {
        require_once FUE_INC_DIR . '/class-follow-up-emails.php';
        require_once FUE_INC_DIR . '/fue-functions.php';

        $this->create_options();
        $this->create_tables();
        $this->create_role();

        // delete the pages if they exist
        $this->delete_pages();

        // setup the daily summary emails on the next page load
        update_option( 'fue_init_daily_summary', true );

        // Queue upgrades
        $current_version    = get_option( 'fue_version', null );
        $current_db_version = get_option( 'fue_db_version', null );

        flush_rewrite_rules();

        if ( version_compare( $current_db_version, '7.6', '<' ) && null !== $current_db_version ) {
            update_option( 'fue_needs_update', 1 );
        } else {
            update_option( 'fue_db_version', Follow_Up_Emails::$db_version );
        }

        // update version
        update_option( 'fue_version', FUE_VERSION );

        do_action( 'fue_install' );
    }

    /**
     * Update scripts
     */
    public function update() {
        // Do updates
        $db_version = get_option( 'fue_db_version' );

        if ( version_compare( $db_version, '7.0', '<' ) ) {
            include( 'updates/update-7.0.php' );
            update_option( 'fue_db_version', '7.0' );
        }

        if ( version_compare( $db_version, '7.2', '<' ) ) {
            include( 'updates/update-7.1.php' );
            update_option( 'fue_db_version', '7.1' );
        }

        if ( version_compare( $db_version, '7.3', '<' ) ) {
            include( 'updates/update-7.3.php' );
            update_option( 'fue_db_version', '7.3' );
        }

        if ( version_compare( $db_version, '7.4', '<' ) ) {
            include( 'updates/update-7.4.php' );
            update_option( 'fue_db_version', '7.4' );
        }

        if ( version_compare( $db_version, '7.5', '<' ) ) {
            include( 'updates/update-7.5.php' );
            update_option( 'fue_db_version', '7.5' );
        }

        if ( version_compare( $db_version, '7.6', '<' ) ) {
            include( 'updates/update-7.6.php' );
            update_option( 'fue_db_version', '7.6' );
        }

    }

    /**
     * Triggered when FUE is deactivated. Remove the scheduled action for sending emails
     */
    public function deactivate() {
        wp_clear_scheduled_hook('sfn_followup_emails');

        do_action( 'fue_uninstall' );
    }

    /**
     * Install the default options
     */
    private function create_options() {

    }

    /**
     * Schedule the daily summary recurring emails
     */
    public function init_daily_summary() {
        if ( !function_exists('wc_next_scheduled_action') ) {
            return;
        }

        if ( wc_next_scheduled_action( 'fue_send_summary' ) ) {
            wc_unschedule_action( 'fue_send_summary' );
        }

        FUE_Sending_Scheduler::queue_daily_summary_email();

        delete_option( 'fue_init_daily_summary' );

    }
    /**
     * Create the database tables used by FUE
     *
     * @return void
     */
    private function create_tables() {
        global $wpdb;

        $wpdb->hide_errors();

        $collate = '';

        if ( $wpdb->has_cap( 'collation' ) ) {
            if ( ! empty($wpdb->charset ) ) {
                $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
            }
            if ( ! empty($wpdb->collate ) ) {
                $collate .= " COLLATE $wpdb->collate";
            }
        }

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $fue_tables = "
        CREATE TABLE {$wpdb->prefix}followup_email_excludes (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          email_id bigint(20) NOT NULL DEFAULT 0,
          order_id bigint(20) NOT NULL DEFAULT 0,
          email_name varchar(255) NOT NULL,
          email varchar(100) NOT NULL,
          date_added DATETIME NOT NULL,
          KEY email (email),
          KEY email_id (email_id),
          KEY order_id (order_id),
          PRIMARY KEY  (id)
        ) $collate;
        CREATE TABLE {$wpdb->prefix}followup_email_orders (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          user_id bigint(20) NOT NULL,
          user_email varchar(255) NOT NULL,
          order_id bigint(20) NOT NULL,
          product_id bigint(20) NOT NULL,
          email_id varchar(100) NOT NULL,
          send_on bigint(20) NOT NULL,
          is_cart int(1) DEFAULT 0 NOT NULL,
          is_sent int(1) DEFAULT 0 NOT NULL,
          date_sent datetime NOT NULL,
          email_trigger varchar(100) NOT NULL,
          meta TEXT NOT NULL,
          status INT(1) DEFAULT 1 NOT NULL,
          KEY user_id (user_id),
          KEY user_email (user_email),
          KEY order_id (order_id),
          KEY is_sent (is_sent),
          KEY date_sent (date_sent),
          KEY status (status),
          PRIMARY KEY  (id)
        ) $collate;
        CREATE TABLE {$wpdb->prefix}followup_email_order_coupons (
          email_order_id bigint(20) NOT NULL,
          coupon_name varchar(100) NOT NULL,
          coupon_code varchar(20) NOT NULL,
          KEY emil_order_id (email_order_id)
        ) $collate;
        CREATE TABLE {$wpdb->prefix}followup_coupon_logs (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          coupon_id bigint(20) NOT NULL,
          coupon_name varchar(100) NOT NULL,
          email_name varchar(100) NOT NULL,
          email_address varchar(255) NOT NULL,
          coupon_code varchar(100) NOT NULL,
          coupon_used INT(1) DEFAULT 0 NOT NULL,
          date_sent datetime NOT NULL,
          date_used datetime NOT NULL,
          KEY coupon_id (coupon_id),
          KEY date_sent (date_sent),
          PRIMARY KEY  (id)
        ) $collate;
        CREATE TABLE {$wpdb->prefix}followup_email_coupons (
            email_id bigint(20) NOT NULL,
            send_coupon int(1) DEFAULT 0 NOT NULL,
            coupon_id bigint(20) DEFAULT 0 NOT NULL,
            KEY email_id (email_id)
        ) $collate;
        CREATE TABLE {$wpdb->prefix}followup_coupons (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          coupon_name varchar(100) NOT NULL,
          coupon_type varchar(25) default 0 NOT NULL,
          coupon_prefix varchar(25) default '' NOT NULL,
          amount double(12,2) default 0.00 NOT NULL,
          individual int(1) default 0 NOT NULL,
          exclude_sale_items int(1) default 0 NOT NULL,
          before_tax int(1) default 0 NOT NULL,
          free_shipping int(1) default 0 NOT NULL,
          usage_count bigint(20) default 0 NOT NULL,
          expiry_value varchar(3) NOT NULL DEFAULT 0,
          expiry_type varchar(25) NOT NULL DEFAULT '',
          product_ids varchar(255) NOT NULL DEFAULT '',
          exclude_product_ids varchar(255) NOT NULL DEFAULT '',
          product_categories TEXT,
          exclude_product_categories TEXT,
          minimum_amount varchar(50) NOT NULL DEFAULT '',
          maximum_amount varchar(50) NOT NULL DEFAULT '',
          usage_limit varchar(3) NOT NULL DEFAULT '',
          usage_limit_per_user varchar(3) NOT NULL DEFAULT '',
          KEY coupon_name (coupon_name),
          KEY usage_count (usage_count),
          PRIMARY KEY  (id)
        ) $collate;
        CREATE TABLE {$wpdb->prefix}followup_email_tracking (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          event_type varchar(20) NOT NULL,
          email_order_id bigint(20) DEFAULT 0 NOT NULL,
          email_id bigint(20) NOT NULL,
          user_id bigint(20) DEFAULT 0 NOT NULL,
          user_email varchar(255) NOT NULL,
          target_url varchar(255) NOT NULL,
          date_added datetime NOT NULL,
          KEY email_id (email_id),
          KEY user_id (user_id),
          KEY user_email (user_email),
          KEY date_added (date_added),
          KEY event_type (event_type),
          PRIMARY KEY  (id)
        ) $collate;
        CREATE TABLE {$wpdb->prefix}followup_email_logs (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          email_order_id bigint(20) DEFAULT 0 NOT NULL,
          email_id bigint(20) NOT NULL,
          user_id bigint(20) DEFAULT 0 NOT NULL,
          email_name varchar(100) NOT NULL,
          customer_name varchar(255) NOT NULL,
          email_address varchar(255) NOT NULL,
          date_sent datetime NOT NULL,
          order_id bigint(20) NOT NULL,
          product_id bigint(20) NOT NULL,
          email_trigger varchar(100) NOT NULL,
          KEY email_name (email_name),
          KEY user_id (user_id),
          KEY date_sent (date_sent),
          PRIMARY KEY  (id)
        ) $collate;
        CREATE TABLE {$wpdb->prefix}followup_customers (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          user_id bigint(20) NOT NULL,
          email_address varchar(255) NOT NULL,
          total_purchase_price double(10,2) DEFAULT 0 NOT NULL,
          total_orders int(11) DEFAULT 0 NOT NULL,
          KEY user_id (user_id),
          KEY email_address (email_address),
          KEY total_purchase_price (total_purchase_price),
          KEY total_orders (total_orders),
          PRIMARY KEY  (id)
        ) $collate;
        CREATE TABLE {$wpdb->prefix}followup_customer_orders (
          followup_customer_id bigint(20) NOT NULL,
          order_id bigint(20) NOT NULL,
          price double(10, 2) NOT NULL,
          KEY followup_customer_id (followup_customer_id),
          KEY order_id (order_id)
        ) $collate;
        CREATE TABLE {$wpdb->prefix}followup_order_items (
          order_id bigint(20) NOT NULL,
          product_id bigint(20) NOT NULL,
          variation_id bigint(20) NOT NULL,
          KEY order_id (order_id),
          KEY product_id (product_id),
          KEY variation_id (variation_id)
        ) $collate;
        CREATE TABLE {$wpdb->prefix}followup_order_categories (
          order_id bigint(20) NOT NULL,
          category_id bigint(20) NOT NULL,
          KEY order_id (order_id),
          KEY category_id (category_id)
        ) $collate;
        CREATE TABLE {$wpdb->prefix}followup_subscribers (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          email varchar(100) NOT NULL,
          date_added DATETIME NOT NULL,
          KEY email (email),
          KEY date_added (date_added),
          PRIMARY KEY  (id)
        ) $collate;
        ";

        dbDelta( $fue_tables );

        update_option( 'fue_installed_tables', true );

    }

    /**
     * Create frontend pages that FUE uses
     * @return void
     */
    public function create_pages() {
        $this->create_my_subscriptions_page();
        $this->create_unsubscribe_page();
    }

    /**
     * Delete the created pages
     */
    public function delete_pages() {
        $page_id = fue_get_page_id('followup_unsubscribe');

        if ( $page_id ) {
            wp_delete_post( $page_id, true );
            delete_option( 'fue_followup_unsubscribe_page_id' );
        }

        $page_id = fue_get_page_id('followup_my_subscriptions');

        if ( $page_id ) {
            wp_delete_post( $page_id, true );
            delete_option( 'fue_followup_my_subscriptions_page_id' );
        }
    }

    /**
     * Add a new 'fue_manager' role and give it the 'manage_follow_up_emails' capability
     */
    public function create_role() {
        global $wp_roles;

        //if ( get_role( 'fue_manager' ) !== null )
        //    return;

        if ( class_exists( 'WP_Roles' ) ) {
            if ( ! isset( $wp_roles ) ) {
                $wp_roles = new WP_Roles();
            }
        }

        add_role( 'fue_manager', __('Follow-Up Emails Manager', 'follow_up_emails'), array(
            'level_9'                => true,
            'level_8'                => true,
            'level_7'                => true,
            'level_6'                => true,
            'level_5'                => true,
            'level_4'                => true,
            'level_3'                => true,
            'level_2'                => true,
            'level_1'                => true,
            'level_0'                => true,
            'read'                   => true,
            'read_private_pages'     => true,
            'read_private_posts'     => true,
            'edit_users'             => true,
            'edit_posts'             => true,
            'edit_pages'             => true,
            'edit_published_posts'   => true,
            'edit_published_pages'   => true,
            'edit_private_pages'     => true,
            'edit_private_posts'     => true,
            'edit_others_posts'      => true,
            'edit_others_pages'      => true,
            'publish_posts'          => true,
            'publish_pages'          => true,
            'delete_posts'           => true,
            'delete_pages'           => true,
            'delete_private_pages'   => true,
            'delete_private_posts'   => true,
            'delete_published_pages' => true,
            'delete_published_posts' => true,
            'delete_others_posts'    => true,
            'delete_others_pages'    => true,
            'manage_categories'      => true,
            'manage_links'           => true,
            'moderate_comments'      => true,
            'unfiltered_html'        => true,
            'upload_files'           => true,
            'export'                 => true,
            'import'                 => true,
            'list_users'             => true
        ) );

        $capabilities = self::get_core_capabilities();

        foreach ( $capabilities as $cap_group ) {
            foreach ( $cap_group as $cap ) {
                $wp_roles->add_cap( 'fue_manager', $cap );
                $wp_roles->add_cap( 'administrator', $cap );
            }
        }
    }

    /**
     * Delete the roles and capabilities created by FUE
     */
    public function remove_roles() {
        global $wp_roles;

        if ( class_exists( 'WP_Roles' ) ) {
            if ( ! isset( $wp_roles ) ) {
                $wp_roles = new WP_Roles();
            }
        }

        $capabilities = self::get_core_capabilities();

        foreach ( $capabilities as $cap_group ) {
            foreach ( $cap_group as $cap ) {
                $wp_roles->remove_cap( 'fue_manager', $cap );
                $wp_roles->remove_cap( 'administrator', $cap );
            }
        }

        remove_role( 'fue_manager' );

    }

    /**
     * Get capabilities - these are assigned to admin/fue manager during installation or reset
     *
     * @return array
     */
    private static function get_core_capabilities() {
        $capabilities = array();

        $capabilities['core'] = array(
            'manage_follow_up_emails'
        );

        $capability_types = array( 'follow_up_email' );

        foreach ( $capability_types as $capability_type ) {

            $capabilities[ $capability_type ] = array(
                // Post type
                "edit_{$capability_type}",
                "read_{$capability_type}",
                "delete_{$capability_type}",
                "edit_{$capability_type}s",
                "edit_others_{$capability_type}s",
                "publish_{$capability_type}s",
                "read_private_{$capability_type}s",
                "delete_{$capability_type}s",
                "delete_private_{$capability_type}s",
                "delete_published_{$capability_type}s",
                "delete_others_{$capability_type}s",
                "edit_private_{$capability_type}s",
                "edit_published_{$capability_type}s",

                // Terms
                "manage_{$capability_type}_terms",
                "edit_{$capability_type}_terms",
                "delete_{$capability_type}_terms",
                "assign_{$capability_type}_terms"
            );
        }

        return $capabilities;
    }

}
endif;

return new FUE_Install();