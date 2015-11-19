<?php

/**
 * Class FUE_Addon_Woocommerce_Admin
 */
class FUE_Addon_Woocommerce_Admin {

    /**
     * @var FUE_Addon_Woocommerce
     */
    private $fue_wc;

    /**
     * Class constructor
     */
    public function __construct( $fue_wc ) {
        $this->fue_wc = $fue_wc;

        $this->register_hooks();
    }

    /**
     * Register hooks
     */
    private function register_hooks() {
        // initial order import
        add_action( 'admin_notices', array($this, 'order_import_check') );
        add_action( 'fue_admin_controller', array($this, 'order_import_page') );

        add_action( 'admin_enqueue_scripts', array($this, 'admin_scripts') );
        add_filter( 'fue_script_locale', array($this, 'create_product_search_nonce') );

        // email list - import order action link
        add_action( 'fue_table_status_actions', array($this, 'add_import_action_link') );
        add_action( 'fue_active_bulk_actions', array($this, 'add_bulk_import_option') );

        add_action( 'fue_execute_bulk_action', array($this, 'execute_bulk_import'), 10, 2 );

        // email forms
        add_action( 'fue_email_form_scripts', array($this, 'email_form_scripts') );

        add_action( 'fue_email_form_settings', array($this, 'signup_email_form_option') );
        add_action( 'fue_email_form_settings', array($this, 'import_orders_form_option') );
        add_action( 'fue_email_form_settings', array($this, 'unqueue_emails_form_option') );
        add_action( 'fue_email_form_after_interval', array($this, 'email_form'), 9, 3 );
        add_action( 'fue_email_form_interval_meta', array($this, 'email_interval_meta') );

        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 20 );

        add_filter( 'fue_email_details_tabs', array( $this, 'inject_email_details_tabs'), 10, 2 );
        add_action( 'fue_email_form_email_details', array( $this, 'email_form_custom_fields_panel' ) );
        add_action( 'fue_email_form_email_details', array( $this, 'email_form_exclusions_panel' ) );

        // conditions
        add_action( 'fue_email_form_conditions_meta', array( $this, 'email_form_conditions'), 10, 2  );

        // excluded categories for storewide emails
        add_action( 'fue_email_form_after_interval', array($this, 'excluded_categories_form'), 10 );
        add_action( 'fue_email_form_after_interval', array($this, 'exclude_customers_form'), 10 );

        // email form custom fields
        add_action( 'fue_email_form_before_message', array($this, 'custom_fields_form') );

        // clean up products before saving the email
        add_filter( 'fue_email_pre_save', array($this, 'cleanup_product_ids'), 10, 2 );

        // importing of existing orders to the email queue
        add_filter( 'fue_after_save_email', array($this, 'schedule_email_order_import') );

        add_action( 'fue_email_created', array($this, 'fix_storewide_type_meta') );
        add_action( 'fue_email_updated', array($this, 'fix_storewide_type_meta') );

        add_action( 'fue_email_created', array($this, 'fix_storewide_type_meta') );
        add_action( 'fue_email_updated', array($this, 'fix_storewide_type_meta') );

        // settings page
        add_action( 'fue_settings_integration', array($this, 'addon_settings') );
        add_action( 'fue_settings_save', array($this, 'addon_save_settings') );
        // link from settings page
        add_action( 'fue_settings_email', array($this, 'link_to_addon_settings') );

        // test email field
        add_action( 'fue_test_email_fields', array($this, 'test_email_form') );

        // email form variables list
        add_action( 'fue_email_variables_list', array($this, 'storewide_variables') );
        add_action( 'fue_email_variables_list', array($this, 'product_variables') );
        add_action( 'fue_email_variables_list', array($this, 'customer_variables') );
        add_action( 'fue_email_variables_list', array($this, 'reminder_variables') );

        // Send Manual
        add_action( 'fue_manual_types', array($this, 'manual_types') );
        add_action( 'fue_manual_type_actions', array($this, 'manual_type_actions') );
        add_action( 'fue_manual_js', array($this, 'manual_js') );

        // Add import link to the email update message
        add_filter( 'fue_update_messages', array( $this, 'add_import_link_to_post_message' ) );
    }

    /**
     * Check if we need to import orders
     */
    public function order_import_check() {
        if (
            !get_option('fue_orders_imported', false)   &&
            !get_transient( 'fue_importing_orders')     &&
            !get_option('fue_disable_order_scan', false)&&
            (empty($_GET['tab']) || $_GET['tab'] != 'order_import')
        ) {
            ?>
            <div id="message" class="updated">
                <p><?php _e( '<strong>Initial order scanning is required to accurately send conditional emails</strong>', 'follow_up_emails' ); ?></p>
                <p class="submit">
                    <a href="<?php echo add_query_arg( 'tab', 'order_import', admin_url( 'admin.php?page=followup-emails' ) ); ?>" class="fue-update-now button-primary"><?php _e( 'Scan Orders', 'follow_up_emails' ); ?></a>
                    <?php _e('or', 'follow_up_emails'); ?> <a href="#" class="fue-disable-scan"><?php _e("don't show this again", 'follow_up_emails'); ?></a>
                </p>
            </div>
            <script type="text/javascript">
                jQuery('.fue-update-now').click(function(){
                    var answer = confirm( '<?php _e( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the scanner now?', 'follow_up_emails' ); ?>' );
                    return answer;
                });

                jQuery('.fue-disable-scan').click(function() {
                    var container   = jQuery(this).parents("div#message");
                    var message     = '<?php _e('Not scanning your existing orders may cause customer and conditional emails to not work accurately. Do you wish to continue?', 'follow_up_emails'); ?>';

                    if ( confirm( message ) ) {
                        post = {
                            action: "fue_wc_disable_order_scan"
                        };
                        jQuery.getJSON( ajaxurl, post, function(resp) {
                            if ( resp && resp.status == 'success' ) {
                                container.remove();
                            }
                        } );
                    }

                    return false;
                });
            </script>
        <?php
        }
    }

    /**
     * UI for importing existing orders via AJAX to avoid script timeout
     * @param string $tab
     */
    public function order_import_page( $tab ) {
        if ( $tab == 'order_import' ) {
            include FUE_TEMPLATES_DIR .'/order-import.php';
        }
    }

    /**
     * Register styles and scripts used in rendering the Admin UI
     */
    public function admin_scripts() {

        $page = isset($_GET['page']) ? $_GET['page'] : '';

        if ( $page == 'followup-emails' || $page == 'followup-emails-settings' || $page == 'followup-emails-queue' ) {
            if (WC_FUE_Compatibility::is_wc_version_gt('2.1')) {
                $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
                wp_register_script( 'ajax-chosen', WC()->plugin_url() . '/assets/js/chosen/ajax-chosen.jquery' . $suffix . '.js', array('jquery', 'chosen'), WC()->version );
                wp_register_script( 'chosen', WC()->plugin_url() . '/assets/js/chosen/chosen.jquery' . $suffix . '.js', array('jquery'), WC()->version );
            } else {
                // For WC < 2.1
                woocommerce_admin_scripts();
            }

            wp_enqueue_script( 'fue-queue', FUE_TEMPLATES_URL .'/js/queue.js', array('jquery', 'chosen'), FUE_VERSION );

            wp_enqueue_script( 'woocommerce_admin' );
            wp_enqueue_script('farbtastic');
            wp_enqueue_script( 'ajax-chosen' );
            wp_enqueue_script( 'chosen' );
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'jquery-ui-autocomplete', null, array('jquery-ui-core') );

            ?>
            <style type="text/css">
                .chzn-choices li.search-field .default {
                    width: auto !important;
                }
                select option[disabled] {display:none;}
            </style>
            <?php

            wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css' );

            if ( !empty( $_GET['tab'] ) && $_GET['tab'] == 'order_import' ) {
                wp_enqueue_script( 'jquery-ui-progressbar', false, array( 'jquery', 'jquery-ui' ) );
                wp_enqueue_script( 'fue_wc_order_import', FUE_TEMPLATES_URL .'/js/wc_order_import.js', array('jquery', 'jquery-ui-progressbar'), FUE_VERSION );
            }

        } elseif ( $page == 'followup-emails-form' || $page == 'followup-emails-reports' ) {

            if ( $page == 'followup-emails-form' ) {
                wp_enqueue_script( 'fue-form-woocommerce', plugins_url( 'templates/js/email-form-woocommerce.js', FUE_FILE ), array('jquery'), FUE_VERSION );
            }

            wp_enqueue_script( 'select2' );
            wp_enqueue_style( 'select2' );

            wp_enqueue_script( 'woocommerce_admin' );
            wp_enqueue_script('farbtastic');
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'jquery-ui-core', null, array('jquery') );
            wp_enqueue_script( 'jquery-ui-datepicker', null, array('jquery-ui-core') );
            wp_enqueue_script( 'jquery-ui-autocomplete', null, array('jquery-ui-core') );

            wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css' );
            wp_enqueue_style( 'jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.21/themes/base/jquery-ui.css' );
        }

        $screen = get_current_screen();
        if ( $screen->id == 'follow_up_email' ) {
            wp_enqueue_script( 'fue-form-woocommerce', plugins_url( 'templates/js/email-form-woocommerce.js', FUE_FILE ), array('jquery'), FUE_VERSION );
            wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css' );
        }

        wp_enqueue_script( 'wc-product-search', plugins_url( 'templates/js/fue-select.js', FUE_FILE ), array( 'jquery', 'select2' ), FUE_VERSION );
    }

    /**
     * Generate an nonce to be injected to the FUE JS variable for searching products
     *
     * @param array $translation
     * @return array
     */
    public function create_product_search_nonce( $translation ) {
        $translation['search_customers_nonce']  = wp_create_nonce('search-customers');
        $translation['nonce']                   = wp_create_nonce("search-products");

        return $translation;
    }

    /**
     * add_import_action_link method
     * @param FUE_Email $email
     */
    public function add_import_action_link( $email ) {
        if ( $email->import_order_flag == 1 ) {
            echo '| <small><a href="'. $this->get_email_import_url( $email->id ) .'" class="email-import-orders" data-id="'. $email->id .'">'. __('Import Orders', 'follow_up_emails') .'</a></small>';
        }
    }

    /**
     * Get the URL of the import page for the $email
     *
     * @param int|array $email_id
     * @return string
     */
    public function get_email_import_url( $email_id ) {
        $args = array(
            'tab'   => 'order_import',
            'email' => $email_id,
            'ref'   => urlencode( admin_url( 'admin.php?page=followup-emails' ) )
        );

        return add_query_arg( $args, admin_url( 'admin.php?page=followup-emails' ) );
    }

    /**
     * Add the 'Import Orders' action in the bulk update dropdown for WC email types
     *
     * @param FUE_Email_Type $type
     * @since 4.0
     */
    public function add_bulk_import_option( $type ) {

        $supported_types = apply_filters( 'fue_import_orders_supported_types', array('storewide', 'customer' ) );

        if ( !in_array( $type->id, $supported_types ) ) {
            return;
        }

        ?>
        <option value="import"><?php _e('Import Orders', 'follow_up_emails'); ?></option>
    <?php
    }

    /**
     * Execute bulk import on the selected emails
     *
     * @param string    $action
     * @param array     $emails
     * @since 4.0
     */
    public function execute_bulk_import( $action, $emails ) {

        if ( $action != 'import' ) {
            return;
        }

        $url = $this->get_email_import_url( $emails );

        wp_redirect( $url );
        exit;

    }

    /**
     * Enqueue styles and scripts for the email form
     * @since 4.1
     */
    public function email_form_scripts() {
        wp_enqueue_script( 'fue-form-woocommerce', plugins_url( 'templates/js/email-form-woocommerce.js', FUE_FILE ), array('jquery'), FUE_VERSION );
    }

    /**
     * Signup email form option
     * @param FUE_Email $email
     */
    public function signup_email_form_option( $email ) {
        if ( $email->type != 'signup' )
            return;

        include FUE_TEMPLATES_DIR .'/email-form/woocommerce/signup-options.php';
    }

    /**
     * Option to allow admin to import existing orders that matches the email's criteria
     * @param FUE_Email $email
     */
    public function import_orders_form_option( $email ) {
        $supported_types = apply_filters( 'fue_import_orders_supported_types', array('storewide', 'customer' ) );

        if ( !in_array( $email->type, $supported_types ) ) {
            return;
        }

        include FUE_TEMPLATES_DIR .'/email-form/woocommerce/import-orders-option.php';
    }

    /**
     * Add the 'Remove emails on status change' option
     * @param FUE_Email $email
     */
    public function unqueue_emails_form_option( $email ) {
        $supported_types = apply_filters( 'fue_import_orders_supported_types', array('storewide', 'customer' ) );

        if ( !in_array( $email->type, $supported_types ) ) {
            return;
        }

        include FUE_TEMPLATES_DIR .'/email-form/woocommerce/remove-emails-status-option.php';
    }

    /**
     * Convert the CSV of product IDs into an array
     * @param array $data
     * @param int $email_id
     * @return array
     */
    public function cleanup_product_ids( $data, $email_id ) {
        if ( !empty( $data['meta']['excluded_customers_products'] ) ) {
            $data['meta']['excluded_customers_products'] = array_filter( array_map( 'intval', explode( ',', $data['meta']['excluded_customers_products'] ) ) );
        }

        return $data;
    }

    /**
     * Set a flag to import existing orders that match the email after the email is created/activated
     *
     * The flag will be set if the following conditions are met:
     * - $data['meta']['import_order'] is set to 'yes'
     * - FUE_Email->meta['import_order'] is not 'yes'
     * - postmeta _imported_order is not 'yes'
     *
     * @param array $data
     * @param array $post
     * @return array
     */
    public function schedule_email_order_import( $data ) {

        if ( empty( $data['meta']['import_orders'] ) || $data['meta']['import_orders'] != 'yes' ) {
            return $data;
        }

        $email = new FUE_Email( $data['ID'] );

        if ( !$email->exists() ) {
            return $data;
        }

        if ( !empty( $email->imported_order ) && $email->imported_order == 'yes' ) {
            return $data;
        }

        update_post_meta( $email->id, '_import_order_flag', true );

        return $data;

    }

    /**
     * Toggle the correct value of the 'storewide_type' meta depending on whether or not
     * a product or category ID has been selected
     *
     * @since 4.0
     * @param int   $email_id
     */
    public function fix_storewide_type_meta( $email_id ) {
        $email  = new FUE_Email( $email_id );
        $type   = 'all';

        if ( !empty( $email->product_id ) ) {
            $type = 'products';
        } elseif ( !empty( $email->category_id ) ) {
            $type = 'categories';
        }

        $meta = $email->meta;

        if ( !$meta ) {
            $meta = array();
        }

        $meta['storewide_type'] = $type;

        update_post_meta( $email_id, '_meta', $meta );
    }

    /**
     * Insert WC fields into the email form
     *
     * @param FUE_Email $email
     */
    public function email_form( $email ) {
        $types = array('storewide', 'reminder');
        if ( !in_array($email->type, $types) ) {
            return;
        }

        // load the categories
        $categories     = get_terms( 'product_cat', array( 'order_by' => 'name', 'order' => 'ASC' ) );
        $has_variations = (!empty($email->product_id) && FUE_Addon_Woocommerce::product_has_children($email->product_id)) ? true : false;
        $storewide_type = (!empty($email->meta['storewide_type'])) ? $email->meta['storewide_type'] : 'all';

        include FUE_TEMPLATES_DIR .'/email-form/woocommerce/email-form.php';

    }

    /**
     * Insert interval fields that are unique to WC emails
     * @param FUE_Email $email
     */
    public function email_interval_meta( $email ) {
        include FUE_TEMPLATES_DIR .'/email-form/woocommerce/interval-fields.php';
    }

    /**
     * Add WC-specific meta boxes to the Email Form
     */
    public function add_meta_boxes() {
        add_meta_box( 'fue-email-products', __( 'Enable For', 'follow-up-email' ), 'FUE_Addon_Woocommerce_Admin::email_form_product_meta_box', 'follow_up_email', 'side', 'high' );
    }

    /**
     * Product/Category selector metabox
     */
    public static function email_form_product_meta_box() {
        global $post;

        $email = new FUE_Email( $post->ID );

        // load the categories
        $categories     = get_terms( 'product_cat', array( 'order_by' => 'name', 'order' => 'ASC' ) );
        $has_variations = (!empty($email->product_id) && FUE_Addon_Woocommerce::product_has_children($email->product_id)) ? true : false;
        $storewide_type = (!empty($email->meta['storewide_type'])) ? $email->meta['storewide_type'] : 'all';

        include FUE_TEMPLATES_DIR .'/email-form/woocommerce/email-form.php';

    }

    /**
     * Add the Exclusions tab to the Email Details panel
     * @param array $tabs
     * @param FUE_Email $email
     *
     * @return array
     */
    public function inject_email_details_tabs( $tabs, $email ) {

        $insert = array (
            'custom_fields' => array(
                'label'  => __( 'Custom Fields', 'follow_up_emails' ),
                'target' => 'custom_fields_details',
                'class'  => array('custom-fields'),
            ),
            'coupons' => array(
                'label'  => __( 'Coupons', 'follow_up_emails' ),
                'target' => 'coupons_details',
                'class'  => array('wc-coupons'),
            )
        );

        if ( $email->type == 'storewide' || $email->type == 'wc_bookings' ) {
            $insert['exclusions'] = array(
                'label'  => __( 'Exclusions', 'follow_up_emails' ),
                'target' => 'exclusions_details',
                'class'  => array('show-if-storewide'),
            );
        }

        array_splice( $tabs, 2, 0, $insert );

        return $tabs;
    }

    /**
     * Render the Exclusion panel in the email form
     * @param FUE_Email $email
     */
    public function email_form_exclusions_panel( $email ) {
        ?>
        <div id="exclusions_details" class="panel fue_panel">
            <?php $this->excluded_categories_form( $email ); ?>
            <?php $this->exclude_customers_form( $email ); ?>
        </div>
        <?php
    }

    /**
     * Render the Custom Fields panel in the email form
     * @param FUE_Email $email
     */
    public function email_form_custom_fields_panel( $email ) {
        ?>
        <div id="custom_fields_details" class="panel fue_panel">
            <?php $this->custom_fields_form( $email ); ?>
        </div>
        <?php
    }

    /**
     * Inject additional form fields into the conditions section
     *
     * @param FUE_Email $email
     * @param int $idx
     */
    public function email_form_conditions( $email, $idx ) {
        $conditions = $email->conditions;
        $categories = $categories = get_terms( 'product_cat', array( 'order_by' => 'name', 'order' => 'ASC' ) );
        include FUE_TEMPLATES_DIR .'/email-form/woocommerce/conditions.php';
    }

    /**
     * Select box for selecting excluded categories
     *
     * @param FUE_Email $email
     */
    public function excluded_categories_form( $email ) {

        // load the categories
        $categories = get_terms( 'product_cat', array( 'order_by' => 'name', 'order' => 'ASC' ) );

        include FUE_TEMPLATES_DIR .'/email-form/woocommerce/excluded-categories.php';
    }

    /**
     * Form to exclude sending email to customers who have previously purchased the
     * selected products or from the selected categories
     *
     * @param FUE_Email $email
     */
    public function exclude_customers_form( $email ) {
        // load the categories
        $categories = get_terms( 'product_cat', array( 'order_by' => 'name', 'order' => 'ASC' ) );

        include FUE_TEMPLATES_DIR .'/email-form/woocommerce/excluded-customers.php';
    }

    /**
     * Custom post meta selector
     *
     * @param FUE_Email $email
     */
    public function custom_fields_form( $email ) {

        if ( $email->type == 'storewide' ):
            $use_custom_field = (isset($email->meta['use_custom_field'])) ? $email->meta['use_custom_field'] : 0;

            include FUE_TEMPLATES_DIR .'/email-form/woocommerce/custom-fields.php';
        endif;
    }

    /**
     * Add a link in the Permissions and Styling tab to the new WC addon settings
     */
    public function link_to_addon_settings() {
        ?>
        <h3><?php _e('WooCommerce Email Settings', 'follow_up_email'); ?></h3>
        <a href="admin.php?page=followup-emails-settings&tab=integration"><?php _e('Click here to manage your WooCommerce email styles', 'follow_up_emails'); ?></a>
        <?php
    }

    /**
     * Add a settings block specifically for FUE WC
     */
    public function addon_settings() {
        include FUE_TEMPLATES_DIR .'/add-ons/settings-woocommerce.php';
    }

    /**
     * Save addon settings
     */
    public function addon_save_settings() {
        $post = $_POST;

        if ( $post['section'] == 'integration' ) {
            // disable email wrapping
            $disable = (isset($_POST['disable_email_wrapping'])) ? (int)$_POST['disable_email_wrapping'] : 0;
            update_option( 'fue_disable_wrapping', $disable );
        }

    }

    /**
     * Allow admin to simulate an email using real orders or products
     * @param FUE_Email $email
     */
    public function test_email_form( $email ) {
        if ($email->type == 'storewide') {
            include FUE_TEMPLATES_DIR .'/email-form/woocommerce/test-fields-storewide.php';
        }
    }

    /**
     * Storewide Email Variables
     * @param FUE_Email $email
     */
    public function storewide_variables( $email ) {
        if ($email->type !== 'storewide') return;

        ?>
        <li class=""><strong>{item_names}</strong> <img class="help_tip" title="<?php _e('Displays a list of purchased items.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class=""><strong>{item_names_list}</strong> <img class="help_tip" title="<?php _e('Displays a comma-separated list of purchased items.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class=""><strong>{item_categories}</strong> <img class="help_tip" title="<?php _e('The list of categories where the purchased items are under.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class=""><strong>{dollars_spent_order}</strong> <img class="help_tip" title="<?php _e('The the amount spent on an order', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <?php

        if ( in_array( $email->trigger, array('refund_manual', 'refund_successful', 'refund_failed') ) ):
            ?>
            <li class=""><strong>{refund_amount}</strong> <img class="help_tip" title="<?php _e('The amount of the refund', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
            <li class=""><strong>{refund_reason}</strong> <img class="help_tip" title="<?php _e('The reason for the refund', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <?php
        endif;
    }

    /**
     * Product Variables
     * @param FUE_Email $email
     */
    public function product_variables( $email ) {
        if ( $email->type !== 'storewide' || empty($email->product_id) ) {
            return;
        }

        ?>
        <li class=""><strong>{item_name}</strong> <img class="help_tip" title="<?php _e('The name of the purchased item.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class=""><strong>{item_category}</strong> <img class="help_tip" title="<?php _e('The list of categories where the purchased item is under.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class=""><strong>{item_price}</strong> <img class="help_tip" title="<?php _e('The price of the purchased item.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class=""><strong>{item_quantity}</strong> <img class="help_tip" title="<?php _e('The quantity of the purchased item.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class=""><strong>{dollars_spent_order}</strong> <img class="help_tip" title="<?php _e('The the amount spent on an order', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
    <?php
    }

    /**
     * Customer Variables
     * @param FUE_Email $email
     */
    public function customer_variables( $email ) {
        if ($email->type !== 'customer') return;

        ?>
        <li class=""><strong>{amount_spent_order}</strong> <img class="help_tip" title="<?php _e('The the amount spent on an order', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class=""><strong>{amount_spent_total}</strong> <img class="help_tip" title="<?php _e('Total amount spent by the customer', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class=""><strong>{number_orders}</strong> <img class="help_tip" title="<?php _e('Total amount spent by the customer', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class=""><strong>{last_purchase_date}</strong> <img class="help_tip" title="<?php _e('The date the customer last ordered', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
    <?php
    }

    /**
     * Reminder Variables
     * @param FUE_Email $email
     */
    public function reminder_variables( $email ) {
        if ($email->type !== 'reminder') return;
        ?>
        <li class=""><strong>{first_email}...{/first_email}</strong> <img class="help_tip" title="<?php _e('The first email description...', 'wc_followup_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class=""><strong>{quantity_email}...{/quantity_email}</strong> <img class="help_tip" title="<?php _e('The quantity email description...', 'wc_followup_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class=""><strong>{final_email}...{/final_email}</strong> <img class="help_tip" title="<?php _e('The last email description...', 'wc_followup_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
    <?php
    }

    /**
     * Additional recipient options for manual emails
     */
    public function manual_types() {
        $options = array(
            'users'     => __('All Users', 'follow_up_emails'),
            'storewide' => __('All Customers', 'follow_up_emails'),
            'customer'  => __('This Customer', 'follow_up_emails'),
            'product'   => __('Customers who bought these products', 'follow_up_emails'),
            'category'  => __('Customers who bought from these categories', 'follow_up_emails'),
            'timeframe' => __('Customers who bought between these dates', 'follow_up_emails')
        );

        include FUE_TEMPLATES_DIR .'/email-form/woocommerce/manual-email-types.php';

    }

    /**
     * The actions for the additional manual email options
     * @param FUE_Email $email
     */
    public function manual_type_actions($email) {
        $categories = get_terms( 'product_cat', array( 'order_by' => 'name', 'order' => 'ASC' ) );

        include FUE_TEMPLATES_DIR .'/email-form/woocommerce/manual-email-actions.php';
    }

    /**
     * Inline JS for sending manual emails
     */
    public function manual_js() {
        ?>
        jQuery("#send_type").change(function() {
            switch (jQuery(this).val()) {
                case "customer":
                    jQuery(".send-type-customer").show();
                    break;

                case "product":
                    jQuery(".send-type-product").show();
                    break;

                case "category":
                    jQuery(".send-type-category").show();
                    break;

                case "timeframe":
                    jQuery(".send-type-timeframe").show();
                    break;
            }
        });

        init_fue_product_search();
        init_fue_select();
        init_fue_customer_search();
    <?php
    }

    /**
     * Add a link to the import page after an email is saved with the import flag checked
     *
     * @param array $messages
     * @return array
     * @since 4.0
     */
    public function add_import_link_to_post_message( $messages ) {
        $post = get_post();

        if ( $post->post_type != 'follow_up_email' ) {
            return $messages;
        }

        $import = get_post_meta( $post->ID, '_import_order_flag', true );

        if ( $import ) {
            $url = $this->get_email_import_url( $post->ID );

            $messages[1] = __( 'Email saved. <a href="'. $url .'">Import existing orders</a>.', 'follow_up_emails' );
        }

        return $messages;
    }
    
}