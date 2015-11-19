<?php

/*
 * Plugin Name: Heytix Stripe Connect
 * Description: Heytix Stripe Connect plugin.
 * Version: 1.0.0
 * Author: Heytix
 * Author URI: http://www.heytix.com
 * License: GPL V3
 */
class HeytixStripeConnect
{

    private static $_instance = null;

    private static $_stripeConnectAuthorizeUrl = 'https://connect.stripe.com/oauth/authorize';

    private static $_stripeConnectDeauthorizeUrl = 'https://connect.stripe.com/oauth/deauthorize';

    private static $_stripeConnectTokenUrl = 'https://connect.stripe.com/oauth/token';

    private static $_stripeLinkStateTimeout = '+3 hour';

    private $_pluginPath;

    private $_pluginUrl;

    private $_pluginBasename;

    private $_pluginName;

    private $_stripeConnectGateway;

    /**
     * Creates or returns an instance of this class.
     */
    public static function get_instance()
    {
        // If an instance hasn't been created and set to $instance create an instance and set it to $instance.
        if (null == self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Initializes the plugin by setting localization, hooks, filters, and administrative functions.
     */
    private function __construct()
    {
        define('HEYTIX_STRIPE_CONNECT_VERSION', '1.0.0');
        $this->_pluginPath = plugin_dir_path(__FILE__);
        $this->_pluginUrl = plugin_dir_url(__FILE__);
        $this->_pluginBasename = plugin_basename(__FILE__);
        $this->_pluginName = 'heytix_gateway_stripe_connect';

        // Actions
        add_action('plugins_loaded', array(
            $this,
            'pluginsLoaded'
        ));
        add_action('wp_loaded', array(
            $this,
            'wpLoaded'
        ));
        add_action('template_redirect', array(
            $this,
            'pageTemplateRedirect'
        ));

        add_action('admin_menu', array(
            $this,
            'adminMenu'
        ));

        add_action('admin_notices', array(
            $this, 'adminNotice'
        ));

        add_action('woocommerce_cart_calculate_fees', array(
            $this,
            'calcFee'
        ));

        // Filters
        add_filter('plugin_action_links_' . $this->_pluginBasename, array(
            $this,
            'pluginActionLinksFilter'
        ));
        add_filter('woocommerce_payment_gateways', array(
            $this,
            'woocommercePaymentGatewaysFilter'
        ));
        add_filter('query_vars', array(
            $this,
            'addQueryVarsFilter'
        ));
        add_filter('generate_rewrite_rules', array(
            $this,
            'rewriteRulesFilter'
        ));

        // Registers
        register_activation_hook(__FILE__, array(
            $this,
            'activation'
        ));
        register_deactivation_hook(__FILE__, array(
            $this,
            'deactivation'
        ));

        $this->run_plugin();
    }

    public function getPluginUrl()
    {
        return $this->_pluginUrl;
    }

    public function getPluginPath()
    {
        return $this->_pluginPath;
    }

    public function getPluginBasename()
    {
        return $this->_pluginBasename;
    }

    public function getPluginName()
    {
        return $this->_pluginName;
    }

    /**
     * Add relevant links to plugins page
     *
     * @param array $links
     * @return array
     */
    public function pluginActionLinksFilter($links)
    {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $this->_pluginName) . '">' . __('Settings', $this->_pluginName) . '</a>'
        );
        return array_merge($plugin_links, $links);
    }

    /**
     * Register the gateway for use
     */
    public function woocommercePaymentGatewaysFilter($methods)
    {
        $methods[] = 'Heytix_Gateway_Stripe_Connect';
        return $methods;
    }

    public function addQueryVarsFilter($qVars)
    {
        $qVars[] = 'stripe-connect';
        return $qVars;
    }

    public function rewriteRulesFilter($wp_rewrite)
    {
        $newRules = array();
        $newRules['stripe-connect/authorize/?$'] = 'index.php?stripe-connect=authorize';
        $newRules['stripe-connect/deauthorize/?$'] = 'index.php?stripe-connect=deauthorize';
        $wp_rewrite->rules = $newRules + $wp_rewrite->rules;
        return $wp_rewrite->rules;
    }

    /**
     * Init localisations and files
     */
    public function pluginsLoaded()
    {
        if (! class_exists('WC_Payment_Gateway')) {
            return;
        }

        // Includes
        include_once ('includes/class-heytix-gateway-stripe-connect.php');
    }

    public function wpLoaded()
    {
        $this->_stripeConnectGateway = Heytix_Gateway_Stripe_Connect::instance();
        $this->_stripeConnectGateway->metaPrefix = $this->_stripeConnectGateway->metaPrefix;
        $rules = get_option('rewrite_rules');
        if (true || ! isset($rules['stripe-connect/authorize/?$']) || ! isset($rules['stripe-connect/deauthorize/?$'])) {
            global $wp_rewrite;
            $wp_rewrite->flush_rules();
        }
    }

    public function pageTemplateRedirect()
    {
        $stripeConnectVar = get_query_var('stripe-connect');
        if (! empty($stripeConnectVar)) {
            if ($this->_stripeConnectGateway->enabled == 'yes') {
                if (! empty($_GET['state'])) {
                    if ($stripeConnectVar === 'authorize' && ! empty($_GET['code'])) {
                        try {
                            $this->authorizeStripeConnect($_GET['state'], $_GET['code']);
                        } catch (Exception $e) {
                            wp_die('<h1>Error</h1>' . '<h3>' . $e->getMessage() . '</h3>' . '<a href="' . home_url() . '">Home</a>', 'Error', array(
                                'response' => 401
                            ));
                        }
                    } elseif ($stripeConnectVar === 'deauthorize') {
                        try {
                            $this->deAuthorizeStripeConnect(@$_GET['state'], @$_GET['force']);
                        } catch (Exception $e) {
                            wp_die('<h1>Error</h1>' . '<h3>' . $e->getMessage() . '</h3>' . '<a href="' . home_url() . '">Home</a>', 'Error', array(
                                'response' => 401
                            ));
                        }
                    }
                }
            }
            wp_redirect(home_url());
        }
    }

    public function adminMenu()
    {
        if ($this->_stripeConnectGateway->enabled !== 'yes') {
            return;
        }
        // Venue stripe connect section
        add_action('admin_enqueue_scripts', array(
            $this,
            'adminEnqueueScripts'
        ));
        if (! empty($this->_stripeConnectGateway->post_type)) {
            add_meta_box($this->_pluginName . '_post_connect', 'Stripe Connect', array(
                $this,
                'stripeConnectMetaBox'
            ), $this->_stripeConnectGateway->post_type, 'side');
        }

        // Product to Venue relation
        add_meta_box($this->_pluginName . '_product_via', 'Stripe Connect Charge Via', array(
            $this,
            'chargeViaMetaBox'
        ), 'product', 'side');
        add_action('save_post', array(
            $this,
            'chargeViaMetaBoxSave'
        ));

        // Order to strip connect charge section
        add_action('add_meta_boxes_shop_order', array(
            $this,
            'addingOrderChargesMetaBox'
        ));

        // Service fee configuration section
        add_action('product_cat_edit_form_fields', array(
            $this,
            'productCatEditServiceFeeField'
        ), 10, 2);
        add_action('edited_product_cat', array(
            $this,
            'saveProductCatServiceFeeField'
        ), 10, 2);

        add_action('manage_posts_custom_column', array(
            $this,
            'displayStripeConnectColumn'
        ), 10, 2);
        add_filter('manage_posts_columns', array(
            $this,
            'addStripeConnectColumn'
        ), 10, 2);

        // Venue commission setting
        add_meta_box($this->_pluginName . '_venue_commission', 'Venue Commission Fee', array(
            $this,
            'venueCommissionMetaBox'
        ), 'tribe_venue', 'side');
        add_action('save_post', array(
            $this,
            'venueCommissionMetaBoxSave'
        ));


        // Events commission setting
        add_meta_box($this->_pluginName . '_event_commission', 'Event Commission Fee', array(
            $this,
            'eventCommissionMetaBox'
        ), 'tribe_events', 'side');
        add_action('save_post', array(
            $this,
            'eventCommissionMetaBoxSave'
        ));
    }

    public function adminEnqueueScripts()
    {
        wp_enqueue_style($this->_pluginName . '_stylesheet', $this->_pluginUrl . '/public/stylesheet.css');
    }

    /**
     * Place code that runs at plugin activation here.
     */
    public function activation()
    {}

    /**
     * Place code that runs at plugin deactivation here.
     */
    public function deactivation()
    {}

    /**
     * Place code for your plugin's functionality here.
     */
    private function run_plugin()
    {}

    /**
     * Adds a stripe connect post to the write post page
     *
     * @param Post $post
     * @return void
     */
    public function chargeViaMetaBox($post)
    {
        $offset = 0;
        $args = array(
            'posts_per_page' => 1,
            'offset' => $offset,
            'post_type' => $this->_stripeConnectGateway->post_type
        );
        $stripeConnectPosts = get_posts($args);
        // The Loop
        if (! empty($stripeConnectPosts)) {
            $selectedStripeConnectPostId = get_post_meta($post->ID, '_ProductStripeConnectPostId', true);
            wp_nonce_field(basename(__FILE__), '_ProductStripeConnectPostId_nonce');
            echo '<select name="_ProductStripeConnectPostId" id="product_meta_box_stripe_connect_post_id">';
            echo '<option>[Select Stripe Connect Post]</option>';
            while (! empty($stripeConnectPosts)) {
                foreach ($stripeConnectPosts as $stripeConnectPost) {
                    echo '<option ' . ($stripeConnectPost->ID == $selectedStripeConnectPostId ? 'selected="selected"' : '') . 'value="' . $stripeConnectPost->ID . '">' . $stripeConnectPost->post_title . '</option>';
                }
                $args['offset'] ++;
                $stripeConnectPosts = get_posts($args);
            }
            echo '</select>';
        } else {
            echo 'No Stripe Connect Post Found';
        }
    }

    public function chargeViaMetaBoxSave($post_id)
    {
        // Checks save status
        $is_autosave = wp_is_post_autosave($post_id);
        $is_revision = wp_is_post_revision($post_id);
        $is_valid_nonce = (isset($_POST['_ProductStripeConnectPostId_nonce']) && wp_verify_nonce($_POST['_ProductStripeConnectPostId_nonce'], basename(__FILE__))) ? true : false;

        // Exits script depending on save status
        if ($is_autosave || $is_revision || ! $is_valid_nonce) {
            return;
        }

        // Checks for input and sanitizes/saves if needed
        if (isset($_POST['_ProductStripeConnectPostId'])) {
            update_post_meta($post_id, '_ProductStripeConnectPostId', $_POST['_ProductStripeConnectPostId']);
        }
    }

    /**
     * Adds venue commission setting metabox
     *
     * @param Post $post
     * @return void
     */
    public function venueCommissionMetaBox($post)
    {

        $venueStripeConnectCommissionFee = get_post_meta($post->ID, '_VenueStripeConnectCommissionFee', true);
        wp_nonce_field(basename(__FILE__), '_VenueStripeConnectCommissionFee_nonce');
        echo '<input type="number" name="_VenueStripeConnectCommissionFee" size="25" step="any" value="' . $venueStripeConnectCommissionFee . '">';
        echo '<p class="description">' . __('Commission Fee ($)', $this->_pluginName) . '</p>';
        $venueStripeConnectCommissionFeeEnabled = get_post_meta($post->ID, '_VenueStripeConnectCommissionFeeEnabled', true);
        echo '<label class="selectit"><input value="yes" type="checkbox"' . ( $venueStripeConnectCommissionFeeEnabled == 'yes' ? ' checked="true"' : ' ') . ' name="_VenueStripeConnectCommissionFeeEnabled">' . __( 'Enable Commission Fee', $this->_pluginName ) . '</label>';
    }

    public function venueCommissionMetaBoxSave($post_id)
    {
        // Checks save status
        $is_autosave = wp_is_post_autosave($post_id);
        $is_revision = wp_is_post_revision($post_id);
        $is_valid_nonce = (isset($_POST['_VenueStripeConnectCommissionFee_nonce']) && wp_verify_nonce($_POST['_VenueStripeConnectCommissionFee_nonce'], basename(__FILE__))) ? true : false;

        // Exits script depending on save status
        if ($is_autosave || $is_revision || ! $is_valid_nonce) {
            return;
        }

        // Checks for input and sanitizes/saves if needed
        if (isset($_POST['_VenueStripeConnectCommissionFee'])) {
            if($_POST['_VenueStripeConnectCommissionFee'] < 0) {
                $this->addAdminMessage('Venue commission fee can\'t be negative', 'error');
                return;
            }
            update_post_meta($post_id, '_VenueStripeConnectCommissionFee', $_POST['_VenueStripeConnectCommissionFee']);
        }
        if (isset($_POST['_VenueStripeConnectCommissionFeeEnabled'])) {
            update_post_meta($post_id, '_VenueStripeConnectCommissionFeeEnabled', 'yes');
        } else {
            delete_post_meta($post_id, '_VenueStripeConnectCommissionFeeEnabled');
        }
    }

    /**
     * Adds event commission setting metabox
     *
     * @param Post $post
     * @return void
     */
    public function eventCommissionMetaBox($post)
    {

        $eventStripeConnectCommissionFee = get_post_meta($post->ID, '_EventStripeConnectCommissionFee', true);
        wp_nonce_field(basename(__FILE__), '_EventStripeConnectCommissionFee_nonce');
        echo '<input type="number" name="_EventStripeConnectCommissionFee" size="25" step="any" value="' . $eventStripeConnectCommissionFee . '">';
        echo '<p class="description">' . __('Commission Fee ($)', $this->_pluginName) . '</p>';
        $eventStripeConnectCommissionFeeEnabled = get_post_meta($post->ID, '_EventStripeConnectCommissionFeeEnabled', true);
        echo '<label class="selectit"><input value="yes" type="checkbox"' . ( $eventStripeConnectCommissionFeeEnabled == 'yes' ? ' checked="true"' : ' ') . 'name="_EventStripeConnectCommissionFeeEnabled">' . __( 'Enable Commission Fee', $this->_pluginName ) . '</label>';
    }

    public function eventCommissionMetaBoxSave($post_id)
    {
        // Checks save status
        $is_autosave = wp_is_post_autosave($post_id);
        $is_revision = wp_is_post_revision($post_id);
        $is_valid_nonce = (isset($_POST['_EventStripeConnectCommissionFee_nonce']) && wp_verify_nonce($_POST['_EventStripeConnectCommissionFee_nonce'], basename(__FILE__))) ? true : false;

        // Exits script depending on save status
        if ($is_autosave || $is_revision || ! $is_valid_nonce) {
            return;
        }

        // Checks for input and sanitizes/saves if needed
        if (isset($_POST['_EventStripeConnectCommissionFee'])) {
            if($_POST['_EventStripeConnectCommissionFee'] < 0) {
                $this->addAdminMessage('Event commission fee can\'t be negative', 'error');
                return;
            }
            update_post_meta($post_id, '_EventStripeConnectCommissionFee', $_POST['_EventStripeConnectCommissionFee']);
        }
        if (isset($_POST['_EventStripeConnectCommissionFeeEnabled'])) {
            update_post_meta($post_id, '_EventStripeConnectCommissionFeeEnabled', 'yes');
        } else {
            delete_post_meta($post_id, '_EventStripeConnectCommissionFeeEnabled');
        }
    }

    /**
     * Adds a stripe connect post to the write post page
     *
     * @param Post $post
     * @return void
     */
    public function addingOrderChargesMetaBox($post)
    {
        $stripeConnectCharges = get_post_meta($post->ID, '_stripe_connect_charge', true);
        if (is_array($stripeConnectCharges)) {
            add_meta_box($this->_pluginName . '_order_charges', 'Stripe Connect Charges', array(
                $this,
                'orderChargesMetaBox'
            ), 'shop_order', 'normal', 'core');
        }
    }

    /**
     * Adds a stripe connect post to the write post page
     *
     * @param Post $post
     * @return void
     */
    public function orderChargesMetaBox($post)
    {
        $stripeConnectCharge = get_post_meta($post->ID, '_stripe_connect_charge', true);
        // Initialize output
        $output = '';
        $output .= '<div class="products-header spacing-wrapper clearfix"></div>';
        $output .= '<div class="spacing-wrapper clearfix">';

        // Include a header
        $chargeMethod = $stripeConnectCharge['charge_direct'] ? 'Charges made directly to connected stripe account' : 'Charges made through stripe connect platform';
        $output .= sprintf('<small>%s</small>', __($chargeMethod, $this->_pluginName));

        // Output purhcase history table
        $output .= '<table style="width:100%; border:1px solid #eee;" cellpadding="0" cellspacing="0" border="0">';
        $output .= '<tr>';
        $output .= '<th style="background:#333; color:#fff; text-align:left; padding:10px;">' . __('Paid to', $this->_pluginName) . '</th>';
        $output .= '<th style="background:#333; color:#fff; text-align:left; padding:10px;">' . __('ChargeId', $this->_pluginName) . '</th>';
        $output .= '<th style="background:#333; color:#fff; text-align:left; padding:10px;">' . __('Charge Amount', $this->_pluginName) . '</th>';
        $output .= '<th style="background:#333; color:#fff; text-align:left; padding:10px;">' . __('Application Fee', $this->_pluginName) . '</th>';
        $output .= '<th style="background:#333; color:#fff; text-align: right; padding:10px;">' . __('Charge Fee', $this->_pluginName) . '</th>';
        $output .= '<th style="background:#333; color:#fff; text-align: right; padding:10px;">' . __('Service Fee', $this->_pluginName) . '</th>';
        $output .= '<th style="background:#333; color:#fff; text-align: right; padding:10px;">' . __('Commission Fee', $this->_pluginName) . '</th>';
        $output .= '<th style="background:#333; color:#fff; text-align: right; padding:10px;">' . __('Net Amount', $this->_pluginName) . '</th>';
        $output .= '</tr>';
        if (! empty($stripeConnectCharge['transaction'])) {
            $idx = 0;
            $totalServiceFeeRevenue = 0;
            $totalCommissionFeeRevenue = 0;
            foreach ($stripeConnectCharge['transaction'] as $stripeAccountpostId => $chargeMetadata) {
                if ($stripeConnectCharge['charge_direct'] && ! empty($stripeAccountpostId)) {
                    $netAmount = $chargeMetadata['net'];
                } else {
                    $commissionFee = $chargeMetadata['commission_fee'];
                    $platformFee = $chargeMetadata['service_fee'] + $chargeMetadata['commission_fee'];
                    if ($platformFee > $chargeMetadata['amount']) {
                        $commissionFee = $platformFee - $chargeMetadata['amount'];
                    }
                }
                $totalServiceFeeRevenue += $chargeMetadata['service_fee'];
                $totalCommissionFeeRevenue += $commissionFee;
                $alt = $idx % 2 ? ' style="background: #f7f7f7;"' : '';
                $output .= '<tr>';
                if (empty($stripeAccountpostId)) {
                    $output .= '<td style="text-align:left; padding:10px;">' . ($idx + 1) . '. Inhouse</td>';
                } else {
                    $accountPost = get_post($stripeAccountpostId);
                    $output .= '<td style="text-align:left; padding:10px;">' . ($idx + 1) . '. <a href="' . get_edit_post_link($stripeAccountpostId) . '" target="_blank">' . $accountPost->post_title . '</a></td>';
                }
                if ($stripeConnectCharge['charge_direct'] && ! empty($stripeAccountpostId)) {
                    $output .= '<td style="text-align:left; padding:10px;">' . $chargeMetadata['charge_id'] . '</a></td>';
                } else {
                    $output .= '<td style="text-align:left; padding:10px;"><a href="' . sprintf($this->_stripeConnectGateway->view_transaction_url, $chargeMetadata['charge_id']) . '" target="_blank">' . $chargeMetadata['charge_id'] . '</a></td>';
                }
                $output .= '<td style="text-align:right; padding:10px;">' . number_format($chargeMetadata['amount'], 2) . '</td>';
                $output .= '<td style="text-align:right; padding:10px;">' . number_format($chargeMetadata['application_fee'], 2) . '</td>';
                $output .= '<td style="text-align:right; padding:10px;">' . number_format($chargeMetadata['charge_fee'], 2) . '</td>';
                $output .= '<td style="text-align:right; padding:10px;">' . number_format($chargeMetadata['service_fee'], 2) . '</td>';
                $output .= '<td style="text-align:right; padding:10px;">';
                if ($chargeMetadata['commission_fee'] > $commissionFee ) {
                    $output .= '<strike>' . number_format($chargeMetadata['commission_fee'], 2) . '</strike> ';
                }
                $output .= number_format($commissionFee, 2);

                $output .= '</td>';
                $output .= '<td style="text-align:right; padding:10px;">' . number_format($netAmount, 2) . '</td>';
                $output .= '</tr>';
                $idx ++;
            }
        }
        $output .= '</table>';

        // Output total service revenue value
        $output .= '<p>';
        $output .= sprintf(__('<strong>Total Service Fee Revenue:</strong> %s', $this->_pluginName), '<span style="color:#7EB03B; font-size:1.2em; font-weight:bold;">' . woocommerce_price($totalServiceFeeRevenue) . '</span>');
        $output .= '</p>';

        // Output total commission revenue value
        $output .= '<p>';
        $output .= sprintf(__('<strong>Total Commission Fee Revenue:</strong> %s', $this->_pluginName), '<span style="color:#7EB03B; font-size:1.2em; font-weight:bold;">' . woocommerce_price($totalCommissionFeeRevenue) . '</span>');
        $output .= '</p>';

        // Output total revenue
        $output .= '<p>';
        $output .= sprintf(__('<strong>Total Revenue:</strong> %s', $this->_pluginName), '<span style="color:#7EB03B; font-size:1.2em; font-weight:bold;">' . woocommerce_price($totalServiceFeeRevenue + $totalCommissionFeeRevenue) . '</span>');
        $output .= '</p>';

        // Close out the container
        $output .= '</div>';

        echo $output;
    }

    /**
     * Adds a stripe connect button to the write post page
     *
     * @param Post $post
     * @return void
     */
    public function stripeConnectMetaBox($post)
    {
        echo $this->postStripeConnectLink($post, 'stripe-connect');
    }

    public function productCatEditServiceFeeField($tag)
    {
        $termMeta = get_option('taxonomy_' . $tag->term_id);
        echo '<tr class="form-field">';
        echo '<th scope="row"><label for="service_fee">' . __('Service Fee') . '</label></th>';
        echo '<td>';
        echo '<input type="number" name="term_meta[service_fee]" id="term_meta[service_fee]" size="25" step="any" value="' . @$termMeta['service_fee'] . '">';
        echo '<p class="description">' . __('Service Fee per item') . '</p>';
        echo '</td>';
        echo '</tr>';
    }

    public function saveProductCatServiceFeeField($termId)
    {
        if (isset($_POST['term_meta'])) {
            $termMeta = get_option('taxonomy_' . $termId);
            $catKeys = array_keys($_POST['term_meta']);
            foreach ($catKeys as $key) {
                if($key == 'service_fee' && $_POST['term_meta'][$key] < 0) {
                    $this->addAdminMessage('Service fee can\'t be negative', 'error');
                } else {
                    $termMeta[$key] = $_POST['term_meta'][$key];
                }
            }
            // save the option array
            update_option('taxonomy_' . $termId, $termMeta);
        }
    }

    public function calcFee($wcCart)
    {
        $this->_stripeConnectGateway->calcFee($wcCart);
    }

    public function displayStripeConnectColumn($column, $post_id)
    {
        if ($column == 'stripe_connect') {
            $post = get_post($post_id);
            echo $this->postStripeConnectLink($post, 'stripe-connect');
        }
    }
    function addStripeConnectColumn($columns)
    {
        if ($_REQUEST['post_type'] == $this->_stripeConnectGateway->post_type) {
            $columns = array_merge($columns, array(
                'stripe_connect' => __('Stripe Connect', $this->_pluginName)
            ));
        }
        return $columns;
    }

    private function checkStripeConnectTimestamp($postId, $refresh = false)
    {
        $postStripeConnectTimestamp = get_post_meta($postId, $this->_stripeConnectGateway->metaPrefix . 'StripeConnectTimestamp', true);
        $updateTimestamp = empty($postStripeConnectTimestamp) || $postStripeConnectTimestamp < time();
        if ($refresh || $updateTimestamp) {
            $postStripeConnectTimestamp = strtotime(self::$_stripeLinkStateTimeout);
            update_post_meta($postId, $this->_stripeConnectGateway->metaPrefix . 'StripeConnectTimestamp', $postStripeConnectTimestamp);
        }
        return $postStripeConnectTimestamp;
    }

    private function authorizeStripeConnect($state, $code)
    {
        $statePart = explode('/', $state, 2);
        if (count($statePart) !== 2) {
            throw new Exception('Malformed Request');
        }
        $post = get_post($statePart[0]);
        if (empty($post)) {
            throw new Exception('Unknown Post');
        }
        if ($post->post_type !== $this->_stripeConnectGateway->post_type) {
            throw new Exception('This Post Type has been disabled');
        }
        $postStripeConnectTimestamp = $this->checkStripeConnectTimestamp($post->ID);
        $stripeConnectState = wp_hash($post->ID . '/' . $postStripeConnectTimestamp, 'stripe_authorize');
        if ($stripeConnectState !== $statePart[1]) {
            throw new Exception('Link Expired');
        }
        $response = wp_remote_post(self::$_stripeConnectTokenUrl, array(
            'method' => 'POST',
            'body' => array(
                'grant_type' => 'authorization_code',
                'client_id' => $this->_stripeConnectGateway->client_id,
                'code' => $code,
                'client_secret' => $this->_stripeConnectGateway->secret_key
            )
        ));
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            throw new Exception($error_message);
        }
        $jsonResponse = json_decode($response['body'], true);
        if (! empty($jsonResponse['error_description'])) {
            throw new Exception($jsonResponse['error_description']);
        }
        update_post_meta($post->ID, $this->_stripeConnectGateway->metaPrefix . 'StripeConnectUserId', $jsonResponse['stripe_user_id']);
        update_post_meta($post->ID, $this->_stripeConnectGateway->metaPrefix . 'StripeConnectAccessToken', $jsonResponse['access_token']);
        update_post_meta($post->ID, $this->_stripeConnectGateway->metaPrefix . 'StripeConnectRefreshToken', $jsonResponse['refresh_token']);
        update_post_meta($post->ID, $this->_stripeConnectGateway->metaPrefix . 'StripeConnectPubKey', $jsonResponse['stripe_publishable_key']);
        $this->checkStripeConnectTimestamp($post->ID, true);
        wp_die('<h1>Success</h1>' . '<h3>Congratulation your stripe account have been connected.</h3>' . '<ul><li><a href="' . get_permalink($post->ID) . '">View post</a></li>' . '<li><a href="' . get_edit_post_link($post->ID) . '">Edit post</a></li></ul>', 'Success', array(
            'response' => 200
        ));
    }

    private function deAuthorizeStripeConnect($state, $force = false)
    {
        if ( ! current_user_can('administrator') ) {
            throw new Exception('Unauthorized');
        }
        $statePart = explode('/', $state, 2);
        if (count($statePart) !== 2) {
            throw new Exception('Malformed Request');
        }
        $post = get_post($statePart[0]);
        if (empty($post)) {
            throw new Exception('Unknown Post');
        }
        $postStripeConnectTimestamp = $this->checkStripeConnectTimestamp($post->ID);
        $stripeConnectState = wp_hash($post->ID . '/' . $postStripeConnectTimestamp, 'stripe_deauthorize');
        if ($stripeConnectState !== $statePart[1]) {
            throw new Exception('Link Expired');
        }
        $postStripeConnectUserId = get_post_meta($post->ID, $this->_stripeConnectGateway->metaPrefix . 'StripeConnectUserId', true);
        if (empty($postStripeConnectUserId)) {
            throw new Exception('Has been revoked');
        }
        $response = wp_remote_post(self::$_stripeConnectDeauthorizeUrl, array(
            'method' => 'POST',
            'body' => array(
                'client_id' => $this->_stripeConnectGateway->client_id,
                'stripe_user_id' => $postStripeConnectUserId,
                'client_secret' => $this->_stripeConnectGateway->secret_key
            )
        ));
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            throw new Exception($error_message);
        }
        $jsonResponse = json_decode($response['body'], true);
        if (! $force && ! empty($jsonResponse['error_description'])) {
            throw new Exception($jsonResponse['error_description']);
        }
        delete_post_meta($post->ID, $this->_stripeConnectGateway->metaPrefix . 'StripeConnectUserId');
        delete_post_meta($post->ID, $this->_stripeConnectGateway->metaPrefix . 'StripeConnectAccessToken');
        delete_post_meta($post->ID, $this->_stripeConnectGateway->metaPrefix . 'StripeConnectRefreshToken');
        delete_post_meta($post->ID, $this->_stripeConnectGateway->metaPrefix . 'StripeConnectPubKey');
        $this->checkStripeConnectTimestamp($post->ID, true);
        $referer = wp_get_referer();
        if ( $referer )
        {
            wp_die('<h1>Success</h1>' . '<h3>Your stripe account have been revoked.</h3>' . '<ul><li><a href="' . $referer . '">Back</a></li></ul>', 'Success', array(
                'response' => 200
            ));

        }
        else
        {
            wp_die('<h1>Success</h1>' . '<h3>Your stripe account have been revoked.</h3>' . '<ul><li><a href="' . get_edit_post_link($post->ID) . '">Edit post</a></li></ul>', 'Success', array(
                'response' => 200
            ));
        }
    }

    private function postStripeConnectLink($post, $class = '')
    {
        $postId = $post->ID;
        $postStripeConnectUserId = get_post_meta($postId, $this->_stripeConnectGateway->metaPrefix . 'StripeConnectUserId', true);
        $postStripeConnectAccessToken = get_post_meta($postId, $this->_stripeConnectGateway->metaPrefix . 'StripeConnectAccessToken', true);
        $stripeConnectClientId = $this->_stripeConnectGateway->client_id;
        $postStripeConnectTimestamp = $this->checkStripeConnectTimestamp($postId);
        if (! empty($stripeConnectClientId) && (empty($postStripeConnectUserId) || empty($postStripeConnectAccessToken))) {
            $stripeConnectState = $postId . '/' . wp_hash($postId . '/' . $postStripeConnectTimestamp, 'stripe_authorize');
            $authorizeRequestQuery = array(
                'response_type' => 'code',
                'scope' => 'read_write',
                'client_id' => $stripeConnectClientId,
                'state' => $stripeConnectState
            );
            $authUrl = self::$_stripeConnectAuthorizeUrl . '?' . http_build_query($authorizeRequestQuery);
            $link = '<a href="' . $authUrl . '" target="_blank" class="' . $class . '"><span>Connect with Stripe</span></a>';
        } else {
            $stripeConnectState = $postId . '/' . wp_hash($postId . '/' . $postStripeConnectTimestamp, 'stripe_deauthorize');
            $deAuthRequestQuery = array(
                'state' => $stripeConnectState
            );
            $deAuthUrl = home_url('stripe-connect/deauthorize') . '?' . http_build_query($deAuthRequestQuery);
            $link = '<a href="' . $deAuthUrl . '" class="' . $class . ' revoke"><span>Revoke from Stripe</span></a>';
        }
        return $link;
    }

    private function addAdminMessage($message, $type = 'success')
    {
        $adminMessages = get_transient($this->_pluginName . '::adminMessages');
        $adminMessages[] = [
            'type' => $type,
            'message' => $message
        ];
        set_transient($this->_pluginName . '::adminMessages', $adminMessages);
    }

    public function adminNotice()
    {
        $adminMessages = get_transient($this->_pluginName . '::adminMessages');
        if (!empty($adminMessages) && is_array($adminMessages)) {
            foreach ($adminMessages as $adminMessage) {
                echo '<div class="' . $adminMessage['type']. '"><p>' . $adminMessage['message'] . '</p></div>';
            }
        }
        delete_transient($this->_pluginName . '::adminMessages');
    }
}

HeytixStripeConnect::get_instance();
