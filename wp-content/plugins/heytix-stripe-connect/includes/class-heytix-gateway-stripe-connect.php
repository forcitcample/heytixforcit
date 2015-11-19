<?php
if (! defined('ABSPATH')) {
    exit();
}

/**
 * Heytix_Gateway_Stripe_Connect class.
 *
 * @extends WC_Payment_Gateway
 */
class Heytix_Gateway_Stripe_Connect extends WC_Payment_Gateway
{

    protected static $_instance = null;

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        require_once (dirname(__FILE__) . '/stripe-php/init.php');

        $this->id = 'heytix-stripe-connect';
        $this->method_title = __('Stripe Connect', 'heytix-gateway-stripe-connect');
        $this->method_description = __('Stripe works by adding credit card fields on the checkout and then sending the details to Stripe for verification.', 'heytix-gateway-stripe-connect');
        $this->has_fields = false;
        $this->api_endpoint = 'https://api.stripe.com/';
        $this->supports = array(
            'products',
            'refunds'
        );

        // Icon
        $icon = WC()->countries->get_base_country() == 'US' ? 'cards.png' : 'eu_cards.png';
        $this->icon = apply_filters('heytix_stripe_connect_icon', plugins_url('/assets/images/' . $icon, dirname(__FILE__)));

        // Load the form fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Get setting values
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->post_type = $this->get_option('post_type');

        $this->testmode = $this->get_option('testmode') === 'yes' ? true : false;
        $this->chargedirect = $this->get_option('chargemethod') === 'direct' ? true : false;
        $this->metaPrefix = $this->testmode ? '_test' : '_';
        $this->client_id = $this->testmode ? $this->get_option('development_client_id') : $this->get_option('production_client_id');
        $this->secret_key = $this->testmode ? $this->get_option('test_secret_key') : $this->get_option('secret_key');
        $this->publishable_key = $this->testmode ? $this->get_option('test_publishable_key') : $this->get_option('publishable_key');
        $this->capture = $this->get_option('capture', 'yes') === 'yes' ? true : false;
        $this->stripeFeePercent = is_nan($this->get_option('stripeFeePercent')) ? 2.9 : $this->get_option('stripeFeePercent');
        $this->stripeFeeAddAmount = is_nan($this->get_option('stripeFeeAddAmount')) ? 30 : $this->get_option('stripeFeeAddAmount');

        $this->view_transaction_url = $this->testmode ? 'https://dashboard.stripe.com/test/payments/%s' : 'https://dashboard.stripe.com/payments/%s';

        $this->order_button_text = __('Continue to payment', 'heytix-gateway-stripe-connect');

        if ($this->testmode) {
            $this->description .= ' ' . sprintf(__('TEST MODE ENABLED. In test mode, you can use the card number 4242424242424242 with any CVC and a valid expiration date or check the documentation "<a href="%s">Testing Stripe</a>" for more card numbers.', 'heytix-gateway-stripe-connect'), 'https://stripe.com/docs/testing');
            $this->description = trim($this->description);
        }

        // Hooks
        add_action('wp_enqueue_scripts', array(
            $this,
            'payment_scripts'
        ));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
            $this,
            'process_admin_options'
        ));
        // Load the form fields
        add_action('wp_loaded', array(
            $this,
            'init_form_fields'
        ));
        add_action('woocommerce_email_after_order_table', array(
            $this,
            'addAdminMessageToEmail'
        ), 10, 2);

        $this->is_available();
    }

    /**
     * Check if this gateway is enabled
     */
    public function is_available()
    {
        if ($this->enabled == "yes") {
            if (! is_ssl() && ! $this->testmode) {
                return false;
            }
            // Required fields check
            if (! $this->client_id || ! $this->secret_key || ! $this->publishable_key) {
                return false;
            }
            try {
                \Stripe\Stripe::setApiKey($this->secret_key);
                $this->platformStripeAccount = \Stripe\Account::retrieve();
            } catch (Exception $e) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        $this->form_fields = apply_filters('heytix_stripe_connect_settings', array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'heytix-gateway-stripe-connect'),
                'label' => __('Enable Stripe Connect', 'heytix-gateway-stripe-connect'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'heytix-gateway-stripe-connect'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'heytix-gateway-stripe-connect'),
                'default' => __('Credit card (Stripe Connect)', 'heytix-gateway-stripe-connect')
            ),
            'description' => array(
                'title' => __('Description', 'heytix-gateway-stripe-connect'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'heytix-gateway-stripe-connect'),
                'default' => __('Pay with your credit card via Stripe Connect.', 'heytix-gateway-stripe-connect')
            ),
            'testmode' => array(
                'title' => __('Test mode', 'heytix-gateway-stripe-connect'),
                'label' => __('Enable Test Mode', 'heytix-gateway-stripe-connect'),
                'type' => 'checkbox',
                'description' => __('Place the payment gateway in test mode using test API keys.', 'heytix-gateway-stripe-connect'),
                'default' => 'yes'
            ),
            'chargemethod' => array(
                'title' => __('Charge method', 'heytix-gateway-stripe-connect'),
                'type' => 'select',
                'description' => __('Ways to create a charge on behalf of a connected account.', 'heytix-gateway-stripe-connect'),
                'options' => array(
                    'direct' => 'Charging directly',
                    'platfrom' => 'Charging through the platform'
                ),
                'default' => 'direct'
            ),
            'development_client_id' => array(
                'title' => __('Development Client Id', 'heytix-gateway-stripe-connect'),
                'type' => 'text',
                'description' => __('Get your Connect Platform client id from your stripe account.', 'heytix-gateway-stripe-connect'),
                'default' => ''
            ),
            'production_client_id' => array(
                'title' => __('Production Client Id', 'heytix-gateway-stripe-connect'),
                'type' => 'text',
                'description' => __('Get your Connect Platform client id from your stripe account.', 'heytix-gateway-stripe-connect'),
                'default' => ''
            ),
            'secret_key' => array(
                'title' => __('Live Secret Key', 'heytix-gateway-stripe-connect'),
                'type' => 'text',
                'description' => __('Get your API keys from your stripe account.', 'heytix-gateway-stripe-connect'),
                'default' => ''
            ),
            'publishable_key' => array(
                'title' => __('Live Publishable Key', 'heytix-gateway-stripe-connect'),
                'type' => 'text',
                'description' => __('Get your API keys from your stripe account.', 'heytix-gateway-stripe-connect'),
                'default' => ''
            ),
            'test_secret_key' => array(
                'title' => __('Test Secret Key', 'heytix-gateway-stripe-connect'),
                'type' => 'text',
                'description' => __('Get your API keys from your stripe account.', 'heytix-gateway-stripe-connect'),
                'default' => ''
            ),
            'test_publishable_key' => array(
                'title' => __('Test Publishable Key', 'heytix-gateway-stripe-connect'),
                'type' => 'text',
                'description' => __('Get your API keys from your stripe account.', 'heytix-gateway-stripe-connect'),
                'default' => ''
            ),
            'post_type' => array(
                'title' => __('Post Type', 'heytix-gateway-stripe-connect'),
                'type' => 'select',
                'description' => __('Which post type should the stripe connect button appear in post edit page. And used to charge the product.', 'heytix-gateway-stripe-connect'),
                'options' => get_post_types()
            ),
            'capture' => array(
                'title' => __('Capture', 'heytix-gateway-stripe-connect'),
                'label' => __('Capture charge immediately', 'heytix-gateway-stripe-connect'),
                'type' => 'checkbox',
                'description' => __('Whether or not to immediately capture the charge. When unchecked, the charge issues an authorization and will need to be captured later. Uncaptured charges expire in 7 days.', 'heytix-gateway-stripe-connect'),
                'default' => 'yes'
            ),
            'stripeFeePercent' => array(
                'title' => __('% Stripe Fee', 'heytix-gateway-stripe-connect'),
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => 'any'
                ),
                'description' => __('Percent of stripe fee', 'heytix-gateway-stripe-connect'),
                'default' => 2.9
            ),
            'stripeFeeAddAmount' => array(
                'title' => __('Additional Stripe Fee Amount', 'heytix-gateway-stripe-connect'),
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => 'any'
                ),
                'description' => __('Additional amount of stripe fee (in cent)', 'heytix-gateway-stripe-connect'),
                'default' => 30
            )
        ));
    }

    /**
     * payment_scripts function.
     *
     * Outputs scripts used for stripe payment
     *
     * @access public
     */
    public function payment_scripts()
    {
        if (! is_checkout()) {
            return;
        }

        wp_enqueue_script('stripe', 'https://checkout.stripe.com/v2/checkout.js', '', '2.0', true);
        wp_enqueue_script('heytix_stripe_connect', plugins_url('assets/js/stripe_connect_checkout.js', dirname(__FILE__)), array(
            'stripe'
        ), HEYTIX_STRIPE_CONNECT_VERSION, true);

        $stripe_params = array(
            'key' => $this->publishable_key,
            'i18n_terms' => __('Please accept the terms and conditions first', 'heytix-gateway-stripe-connect'),
            'i18n_required_fields' => __('Please fill in required checkout fields first', 'heytix-gateway-stripe-connect')
        );

        wp_localize_script('heytix_stripe_connect', 'heytix_stripe_connect_params', $stripe_params);
    }

    /**
     * Process the payment
     */
    public function process_payment($order_id, $retry = true)
    {
        try {
            // compose common stripe charge args
            $chargeArgs = array();
            $order = new WC_Order($order_id);

            $stripeOrders = $this->groupOrderByConnectProductStripeConnectPostId($order);
            $stripe_token = isset($_POST['stripe_token']) ? wc_clean($_POST['stripe_token']) : false;
            $card_id = isset($_POST['stripe_card_id']) ? wc_clean($_POST['stripe_card_id']) : false;
            $customer_id = is_user_logged_in() ? get_user_meta(get_current_user_id(), '_stripe_customer_id', true) : false;

            if (! $customer_id || ! is_string($customer_id)) {
                $customer_id = false;
            }

            if (! $card_id || ! $customer_id) {
                // Pay using a saved card!
                if (empty($stripe_token)) {
                    // If not using a saved card, we need a token
                    $error_msg = __('Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'woocommerce-gateway-stripe');
                    if ($this->testmode) {
                        $error_msg .= ' ' . __('Developers: Please make sure that you are including jQuery and there are no JavaScript errors on the page.', 'woocommerce-gateway-stripe');
                    }
                    throw new Exception($error_msg);
                } else {
                    // Use token
                    if ($customer_id) {
                        try {
                            $customer = \Stripe\Customer::retrieve($customer_id);
                            $card = $customer->sources->create(array(
                                'source' => $stripe_token
                            ));
                            $customer_id = $customer->id;
                            $card_id = $card->id;
                        } catch (\Stripe\Error\InvalidRequest $e) {
                            delete_user_meta(get_current_user_id(), '_stripe_customer_id');
                            $customer_id = false;
                        }
                    }
                    if (! $customer_id) {
                        $customer = Stripe\Customer::create(array(
                            'email' => $order->billing_email,
                            'description' => 'Customer: ' . $order->billing_first_name . ' ' . $order->billing_last_name,
                            'source' => $stripe_token
                        ));
                        $customer_id = $customer->id;
                        $card_id = $customer->default_source;
                    }
                }
            }

            // Other charge data
            $chargeArgs['currency'] = strtolower($order->get_order_currency() ? $order->get_order_currency() : get_woocommerce_currency());
            $chargeArgs['capture'] = $this->capture ? 'true' : 'false';

            // Make the request for each stripe orders
            $stripeConnectChargeMetadata = array(
                'testmode' => $this->testmode,
                'charge_direct' => $this->chargedirect
            );
            $chargeIds = array();
            $adminOrderMessages = false;
            foreach ($stripeOrders as $key => $stripeOrder) {
                $chargeArgs['amount'] = $this->get_stripe_amount($stripeOrder['charge_amount'], $chargeArgs['currency']);
                $chargeArgs['description'] = sprintf(__('%s - Order %s', 'woocommerce-gateway-stripe'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES), $order->get_order_number());
                unset($chargeArgs['destination']);
                unset($chargeArgs['customer']);
                unset($chargeArgs['source']);
                unset($chargeArgs['application_fee']);
                $stripeConnectMeta = $stripeOrder['connect_meta'];
                if (! empty($stripeConnectMeta)) {
                    $chargeArgs['description'] = sprintf(__('%s - %s - Order %s', 'woocommerce-gateway-stripe'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES), wp_specialchars_decode($stripeConnectMeta['title'], ENT_QUOTES), $order->get_order_number());
                    if (! empty($stripeOrder['application_fee'])) {
                        $chargeArgs['application_fee'] = $this->get_stripe_amount($stripeOrder['application_fee'], $chargeArgs['currency']);
                    }
                    if ($this->chargedirect) {
                        // Create a Token from the existing customer on the platform's account
                        $token = \Stripe\Token::create([
                            'customer' => $customer_id,
                            'card' => $card_id
                        ], $stripeConnectMeta['access_token']);
                        $chargeArgs['source'] = $token->id;
                        $charge = Stripe\Charge::create($chargeArgs, [
                            'stripe_account' => $stripeConnectMeta['stripe_user_id']
                        ]);
                        $balanceTransaction = \Stripe\BalanceTransaction::retrieve($charge->balance_transaction, $stripeConnectMeta['access_token']);
                    } else {
                        $chargeArgs['customer'] = $customer_id;
                        $chargeArgs['source'] = $card_id;
                        $chargeArgs['destination'] = $stripeConnectMeta['stripe_user_id'];
                        $charge = Stripe\Charge::create($chargeArgs);
                        $balanceTransaction = \Stripe\BalanceTransaction::retrieve($charge->balance_transaction);
                    }
                    update_post_meta($order->id, 'Stripe Connect Account', $stripeConnectMeta['stripe_user_id']);
                } else {
                    $chargeArgs['customer'] = $customer_id;
                    $chargeArgs['source'] = $card_id;
                    $charge = Stripe\Charge::create($chargeArgs);
                    $balanceTransaction = \Stripe\BalanceTransaction::retrieve($charge->balance_transaction);
                }
                $chargeIds[] = $charge->id;
                $chargeFee = 0;
                foreach ($balanceTransaction->fee_details as $feeDetail) {
                    if ($feeDetail->type == 'stripe_fee') {
                        $chargeFee = number_format($feeDetail->amount / 100, 2, '.', '');
                    }
                }
                $applicationFee = 0;
                if (! empty($charge->application_fee)) {
                    $feeDetail = \Stripe\ApplicationFee::retrieve($charge->application_fee);
                    $applicationFee = number_format($feeDetail->amount / 100, 2, '.', '');
                }
                $stripeConnectChargeMetadata['transaction'][$key] = [
                    'connect_account' => ! empty($stripeConnectMeta['stripe_user_id']) ? $stripeConnectMeta['stripe_user_id'] : null,
                    'charge_id' => $charge->id,
                    'transfer_id' => $charge->transfer,
                    'application_fee_id' => @$charge->application_fee,
                    'customer_id' => $customer_id,
                    'card_id' => $card_id,
                    'amount' => number_format($balanceTransaction->amount / 100, 2, '.', ''),
                    'application_fee' => $applicationFee,
                    'charge_fee' => $chargeFee,
                    'net' => number_format($balanceTransaction->net / 100, 2, '.', ''),
                    'service_fee' => number_format($stripeOrder['service_fee'], 2, '.', ''),
                    'commission_fee' => number_format($stripeOrder['commission_fee'], 2, '.', ''),
                    'captured' => $charge->captured ? 'yes' : 'no'
                ];
                if ($chargeArgs['application_fee'] > $applicationFee) {
                    $adminOrderMessages[] = 'Commission Fee amount too large detected, its value have been trimmed down to applicable charge amount';
                }
            }
            // Store charge Metadata
            update_post_meta($order->id, '_stripe_connect_charge', $stripeConnectChargeMetadata);
            if ($charge->captured) {
                // Add order note
                $order->add_order_note(sprintf(__('Stripe charge complete (Charge ID: %s)', 'woocommerce-gateway-stripe'), implode(', ', $chargeIds)));
            } else {
                // Store captured value
                // Mark as on-hold
                $order->update_status('on-hold', sprintf(__('Stripe charge authorized (Charge ID: %s). Process order to take payment, or cancel to remove the pre-authorization.', 'woocommerce-gateway-stripe'), implode(',', $chargeIds)));
                // Reduce stock levels
                $order->reduce_order_stock();
            }
            if (! empty($adminOrderMessages)) {
                foreach ($adminOrderMessages as $message) {
                    $order->add_order_note($message);
                }
                set_transient($this->id . '::adminOrderMessages', $adminOrderMessages);
            }

            // Remove cart
            WC()->cart->empty_cart();
            // Return thank you page redirect
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } catch (Exception $e) {
            $order_note = $e->getMessage();
            $order->update_status('failed', $order_note);
            wc_add_notice($e->getMessage(), 'error');
            return;
        }
    }

    /**
     * Get Stripe amount to pay
     *
     * @return float
     */
    public function get_stripe_amount($total, $currency = '')
    {
        if (! $currency) {
            $currency = get_woocommerce_currency();
        }
        switch (strtoupper($currency)) {
            // Zero decimal currencies
            case 'BIF':
            case 'CLP':
            case 'DJF':
            case 'GNF':
            case 'JPY':
            case 'KMF':
            case 'KRW':
            case 'MGA':
            case 'PYG':
            case 'RWF':
            case 'VND':
            case 'VUV':
            case 'XAF':
            case 'XOF':
            case 'XPF':
                $total = absint($total);
                break;
            default:
                $total = round($total, 2) * 100; // In cents
                break;
        }
        return $total;
    }

    private function getStripeConnectMeta($stripeConnectPostId)
    {
        $stripeConnectMeta = false;
        $stripeConnectUserId = get_post_meta($stripeConnectPostId, $this->metaPrefix . 'StripeConnectUserId', true);
        $stripeConnectAccessToken = get_post_meta($stripeConnectPostId, $this->metaPrefix . 'StripeConnectAccessToken', true);
        if (! empty($stripeConnectUserId) && ! empty($stripeConnectAccessToken)) {
            $post = get_post($stripeConnectPostId);
            $stripeConnectRefreshToken = get_post_meta($stripeConnectPostId, $this->metaPrefix . 'StripeConnectRefreshToken', true);
            $stripeConnectPubKey = get_post_meta($stripeConnectPostId, $this->metaPrefix . 'StripeConnectPubKey', true);
            $stripeConnectMeta = [
                'title' => $post->post_title,
                'stripe_user_id' => $stripeConnectUserId,
                'access_token' => $stripeConnectAccessToken,
                'refresh_token' => $stripeConnectRefreshToken,
                'stripe_publishable_key' => $stripeConnectPubKey
            ];
        }
        return $stripeConnectMeta;
    }

    /**
     *
     * @param WC_Order $order
     * @return array
     */
    private function groupOrderByConnectProductStripeConnectPostId($order)
    {
        $orderItems = $order->get_items();
        $ordersByStripeConnect = array();
        foreach ($orderItems as $item_id => $orderItem) {
            $product = $order->get_product_from_item($orderItem);
            // Check if the product exists.
            if (is_object($product)) {
                $stripeConnectMeta = null;
                $productStripeConnectPostId = get_post_meta($product->post->ID, '_ProductStripeConnectPostId', true);
                if (empty($productStripeConnectPostId)) {
                    $productStripeConnectPostId = 0;
                } else {
                    $stripeConnectMeta = $this->getStripeConnectMeta($productStripeConnectPostId);
                    if (empty($stripeConnectMeta) || $this->platformStripeAccount->id == $stripeConnectMeta['stripe_user_id']) {
                        $productStripeConnectPostId = 0;
                        $stripeConnectMeta = null;
                    }
                }
                $orderItemServiceFeeAmount = $this->productCatServiceFee($product->id) * $orderItem['qty'];
                $orderItemCommissionFeeAmount = $this->productCommissionFee($product->id) * $orderItem['qty'];
                $ordersByStripeConnect[$productStripeConnectPostId]['items'][] = $orderItem;
                $ordersByStripeConnect[$productStripeConnectPostId]['line_total'] += $product->get_price() * $orderItem['qty'];
                $ordersByStripeConnect[$productStripeConnectPostId]['service_fee'] += $orderItemServiceFeeAmount;
                $ordersByStripeConnect[$productStripeConnectPostId]['commission_fee'] += $orderItemCommissionFeeAmount;
                $ordersByStripeConnect[$productStripeConnectPostId]['connect_meta'] = $stripeConnectMeta;
            }
        }
        foreach ($ordersByStripeConnect as $key => $orderByStripeConnect) {
            $ordersByStripeConnect[$key]['sub_total'] = $orderByStripeConnect['line_total'] + $orderByStripeConnect['service_fee'];
            $ordersByStripeConnect[$key]['processing_fee'] = $this->calcProcessingFee($ordersByStripeConnect[$key]['sub_total']);
            $ordersByStripeConnect[$key]['charge_amount'] = $ordersByStripeConnect[$key]['sub_total'] + $ordersByStripeConnect[$key]['processing_fee'];
            if ($this->chargedirect) {
                $applicationFee = $ordersByStripeConnect[$key]['service_fee'] + $ordersByStripeConnect[$key]['commission_fee'];
            } else {
                $applicationFee = $ordersByStripeConnect[$key]['service_fee'] + $ordersByStripeConnect[$key]['commission_fee'] + $ordersByStripeConnect[$key]['processing_fee'];
            }
            if ($applicationFee < 0) {
                $applicationFee = 0;
            }
            $ordersByStripeConnect[$key]['application_fee'] = $applicationFee;
        }
        return $ordersByStripeConnect;
    }

    /**
     *
     * @param WC_Cart $wcCart
     */
    private function cartItemsByStripeConnectPostId($wcCart)
    {
        $productsByStripeConnect = array();
        $cartItems = $wcCart->get_cart();
        foreach ($cartItems as $cartItem) {
            $cartItemStripeConnectPostId = get_post_meta($cartItem['product_id'], '_ProductStripeConnectPostId', true);
            if (! empty($cartItemStripeConnectPostId)) {
                $productsByStripeConnect[$cartItemStripeConnectPostId][] = $cartItem;
            } else {
                $productsByStripeConnect[0][] = $cartItem;
            }
        }
        return $productsByStripeConnect;
    }

    /**
     *
     * @param WC_Cart $wcCart
     */
    private function calcCartStripeConnectProcessingFee($wcCart, $totalAmount)
    {
        // Ref: https://support.stripe.com/questions/can-i-charge-my-stripe-fees-to-my-customers
        $cartItemsByStripeConnect = $this->cartItemsByStripeConnectPostId($wcCart);
        $totalStripeConnectProcessingFee = ($totalAmount + count($cartItemsByStripeConnect) * $this->stripeFeeAddAmount / 100) / (1 - $this->stripeFeePercent / 100) - $totalAmount;
        return $totalStripeConnectProcessingFee;
    }

    private function calcProcessingFee($amount)
    {
        $feeAmount = ($amount + $this->stripeFeeAddAmount / 100) / (1 - $this->stripeFeePercent / 100) - $amount;
        return $feeAmount;
    }

    /**
     *
     * @param int $productId
     * @return float
     */
    public function productCatServiceFee($productId)
    {
        $serviceFee = 0;
        $productCats = get_the_terms($productId, 'product_cat');
        if (is_array($productCats)) {
            foreach ($productCats as $productCat) {
                $termMeta = get_option('taxonomy_' . $productCat->term_id);
                if ($serviceFee < @$termMeta['service_fee']) {
                    $serviceFee = $termMeta['service_fee'];
                }
            }
        }
        if (! is_numeric($serviceFee)) {
            $serviceFee = 0;
        } else {
            $serviceFee = floatval($serviceFee);
        }
        return $serviceFee;
    }

    /**
     * Get product event commission fee
     *
     * @param int $productId
     * @return float
     */
    public function productEventCommissionFee($productId)
    {
        $commissionFee = 0;
        // Event based Commission Fee
        $wooTicketInstance = Tribe__Events__Tickets__Woo__Main::get_instance();
        $event = $wooTicketInstance->get_event_for_ticket($productId);
        if (! empty($event)) {
            $eventCommissionFeeEnabled = get_post_meta($event->ID, '_EventStripeConnectCommissionFeeEnabled', true) == 'yes';
            if ($eventCommissionFeeEnabled) {
                $commissionFee = get_post_meta($event->ID, '_EventStripeConnectCommissionFee', true);
            }
        }
        if (! is_numeric($commissionFee)) {
            $commissionFee = 0;
        } else {
            $commissionFee = floatval($commissionFee);
        }
        return $commissionFee;
    }

    /**
     * Get product venue commission fee
     *
     * @param int $productId
     * @return float
     */
    public function productVenueCommissionFee($productId)
    {
        $commissionFee = 0;
        $wooTicketInstance = Tribe__Events__Tickets__Woo__Main::get_instance();
        $event = $wooTicketInstance->get_event_for_ticket($productId);
        if (! empty($event)) {
            // Venue based Commission Fee
            $venueId = get_post_meta($event->ID, '_EventVenueID', true);
            if (! empty($venueId)) {
                $venueCommissionFeeEnabled = get_post_meta($venueId, '_VenueStripeConnectCommissionFeeEnabled', true) == 'yes';
                if ($venueCommissionFeeEnabled) {
                    $commissionFee = get_post_meta($venueId, '_VenueStripeConnectCommissionFee', true);
                }
            }
        }
        if (! is_numeric($commissionFee)) {
            $commissionFee = 0;
        } else {
            $commissionFee = floatval($commissionFee);
        }
        return $commissionFee;
    }

    /**
     * Get product commission fee
     *
     * @param int $productId
     * @return float
     */
    public function productCommissionFee($productId)
    {
        $eventCommissionFee = $this->productEventCommissionFee($productId);
        if (empty($eventCommissionFee)) {
            $commissionFee = $this->productVenueCommissionFee($productId);
        } else {
            $commissionFee = $eventCommissionFee;
        }
        return $commissionFee;
    }

    /**
     *
     * @param WC_Cart $wcCart
     */
    public function calcFee($wcCart)
    {
        $serviceFees = 0;
        // Itereate through each item
        $cartItems = $wcCart->get_cart();
        foreach ($cartItems as $cartItem) {
            $serviceFees += $this->productCatServiceFee($cartItem['product_id']) * $cartItem['quantity'];
        }
        // If there is any service fees for tickets then add it to cart.
        if ($serviceFees) {
            $wcCart->add_fee('Service Fee', $serviceFees, false, '');
        }
        $paymentMethod = WC()->session->chosen_payment_method;
        if (empty($paymentMethod)) {
            $availableGateways = WC()->payment_gateways->get_available_payment_gateways();
            if (! empty($availableGateways)) {
                $paymentMethod = current($availableGateways)->id;
            }
        }
        if ((is_checkout() || defined('WOOCOMMERCE_CHECKOUT')) && $paymentMethod == 'heytix-stripe-connect') {
            if (! defined('STRIPE_CONNECT_PROCESSING_FEE')) {
                define('STRIPE_CONNECT_PROCESSING_FEE', true);
                $totalAmount = $wcCart->subtotal + $serviceFees;
                $processingFee = $this->calcCartStripeConnectProcessingFee($wcCart, $totalAmount);
                if ($processingFee) {
                    $wcCart->add_fee('Credit Card Processing', $processingFee, false, '');
                }
            }
        }
    }

    public function addAdminMessageToEmail($order, $is_admin)
    {
        // Only for admin emails
        if (! $is_admin) {
            return;
        }
        $adminOrderMessages = get_transient($this->id . '::adminOrderMessages');
        delete_transient($this->id . '::adminOrderMessages');
        if (! empty($adminOrderMessages)) {
            foreach ($adminOrderMessages as $message) {
                echo '<p>' . $message . '</p>';
            }
        }
    }
}
