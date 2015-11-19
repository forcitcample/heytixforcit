<?php

/**
 * Class FUE_Addon_Points_And_Rewards
 */
class FUE_Addon_Points_And_Rewards {

    /**
     * class constructor
     */
    public function __construct() {

        if (self::is_installed()) {
            add_filter( 'fue_email_types', array($this, 'register_email_type') );

            // reports
            add_filter( 'fue_trigger_str', array($this, 'trigger_string'), 10, 2 );

            add_action( 'fue_email_form_interval_meta', array($this, 'add_interval_meta') );
            add_action( 'fue_email_form_scripts', array($this, 'email_form_script') );

            add_action( 'wc_points_rewards_after_increase_points', array($this, 'after_points_increased'), 10, 5 );
            add_action( 'fue_email_variables_list', array($this, 'email_variables_list') );

            add_action( 'fue_before_variable_replacements', array($this, 'register_variable_replacements'), 10, 4 );

        }
    }

    /**
     * Check if the plugin is active
     *
     * @return bool
     */
    public static function is_installed() {
        return class_exists('WC_Points_Rewards');
    }

    /**
     * Register custom email type
     *
     * @param array $types
     * @return array
     */
    public function register_email_type( $types ) {
        $triggers = array(
            'points_earned'         => __('After: Points Earned', 'wc_followup_emails'),
            'points_greater_than'   => __('Earned Points is greater than', 'wc_followup_emails')
        );
        $props = array(
            'label'                 => __('Points and Rewards Emails', 'follow_up_emails'),
            'singular_label'        => __('Points and Rewards Email', 'follow_up_emails'),
            'triggers'              => $triggers,
            'durations'             => Follow_Up_Emails::$durations,
            'long_description'      => __('Points and Rewards emails will send to a user based upon the point earnings status you define when creating your emails. Below are the existing Points and Rewards emails set up for your store. Use the priorities to define which emails are most important. These emails are selected first when sending the email to the customer if more than one criteria is met by multiple emails. Only one email is sent out to the customer (unless you enable the Always Send option when creating your emails), so prioritizing the emails for occasions where multiple criteria are met ensures you send the right email to the right customer at the time you choose. <a href="admin.php?page=followup-emails-settings&tab=documentation">Learn More</a>', 'follow_up_emails'),
            'short_description'     => __('Points and Rewards emails will send to a user based upon the point earnings status you define when creating your emails.', 'follow_up_emails')
        );
        $types[] = new FUE_Email_Type( 'points_and_rewards', $props );

        return $types;
    }

    /**
     * Email form field
     *
     * @param FUE_Email $email
     */
    public function add_interval_meta( $email ) {
        ?>
        <span class="points-greater-than-meta" style="display:none;">
            <input type="text" style="width: 50px" name="meta[points_greater_than]" value="<?php if (isset($email->meta['points_greater_than'])) echo $email->meta['points_greater_than']; ?>" />
        </span>
        <?php
    }

    /**
     * JS for email form
     */
    public function email_form_script() {
        wp_enqueue_script( 'fue-form-points-and-rewards', FUE_TEMPLATES_URL .'/js/email-form-points-and-rewards.js' );
    }

    /**
     * Action fired after points have been increased
     *
     * @param int       $user_id
     * @param int       $points
     * @param string    $event_type
     * @param array     $data
     * @param int       $order_id
     */
    public function after_points_increased( $user_id, $points, $event_type, $data = null, $order_id = 0 ) {
        $emails = fue_get_emails( 'points_and_rewards', FUE_Email::STATUS_ACTIVE, array(
            'meta_query'    => array(
                array(
                    'key'       => '_interval_type',
                    'value'     => array( 'points_earned', 'points_greater_than' ),
                    'compare'   => 'IN'
                )
            )
        ) );

        foreach ( $emails as $email ) {

            if ( $email->interval_type == 'points_greater_than' ) {
                $meta = maybe_unserialize( $email->meta );
                if ( $points < $meta['points_greater_than'] ) continue;
            }

            $insert = array(
                'send_on'       => $email->get_send_timestamp(),
                'email_id'      => $email->id,
                'user_id'       => $user_id,
                'order_id'      => $order_id,
                'is_cart'       => 0
            );

            $email_order_id = FUE_Sending_Scheduler::queue_email( $insert, $email );

            if ( !is_wp_error( $email_order_id ) ) {
                $data = array(
                    'user_id'       => $user_id,
                    'points'        => $points,
                    'event_type'    => $event_type
                );
                update_option( 'fue_email_order_'. $email_order_id, $data );

                // Tell FUE that an email order has been created
                // to stop it from sending storewide emails
                if (! defined('FUE_ORDER_CREATED'))
                    define('FUE_ORDER_CREATED', true);
            }

        }
    }

    /**
     * Available variables
     * @param FUE_Email $email
     */
    public function email_variables_list( $email ) {
        global $woocommerce;

        if ( $email->type != 'points_and_rewards' ) {
            return;
        }
        ?>
        <li class="var hideable var_points_and_rewards"><strong>{points_earned}</strong> <img class="help_tip" title="<?php _e('The number of points earned', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_points_and_rewards"><strong>{reward_event_description}</strong> <img class="help_tip" title="<?php _e('The description of the action', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <?php
    }

    /**
     * Register variables for replacement
     *
     * @param FUE_Sending_Email_Variables   $var
     * @param array                 $email_data
     * @param FUE_Email             $email
     * @param object                $queue_item
     */
    public function register_variable_replacements( $var, $email_data, $email, $queue_item ) {
        $variables = array( 'points_earned', 'reward_event_description' );

        // use test data if the test flag is set
        if ( isset( $email_data['test'] ) && $email_data['test'] ) {
            $variables = $this->add_test_variable_replacements( $variables, $email_data, $email );
        } else {
            $variables = $this->add_variable_replacements( $variables, $email_data, $queue_item, $email );
        }

        $var->register( $variables );
    }

    /**
     * Scan through the keys of $variables and apply the replacement if one is found
     * @param array     $variables
     * @param array     $email_data
     * @param object    $queue_item
     * @param FUE_Email $email
     * @return array
     */
    protected function add_variable_replacements( $variables, $email_data, $queue_item, $email ) {

        $event_data = get_option( 'fue_email_order_'. $queue_item->id, false );

        if (! $event_data ) {
            $event_data = array(
                'user_id'       => 0,
                'points'        => 0,
                'event_type'    => ''
            );
        }

        $points         = $event_data['points'];
        $description    = WC_Points_Rewards_Manager::event_type_description($event_data['event_type']);

        $variables['points_earned'] = $points;
        $variables['reward_event_description']  = $description;

        return $variables;
    }

    /**
     * Add variable replacements for test emails
     *
     * @param array     $variables
     * @param array     $email_data
     * @param FUE_Email $email
     *
     * @return array
     */
    protected function add_test_variable_replacements( $variables, $email_data, $email ) {
        $variables['points_earned']             = 50;
        $variables['reward_event_description']  = 'Test event description';

        return $variables;
    }

    /**
     * Format the trigger string that is displayed in the email reports
     *
     * @param string    $string
     * @param FUE_Email $email
     *
     * @return string
     */
    public function trigger_string( $string, $email ) {
        if ( $email->trigger == 'points_greater_than' ) {
            $email_type = $email->get_email_type();
            $meta = maybe_unserialize( $email->meta );
            $string = sprintf(
                __('%d %s %s %d'),
                $email->interval,
                Follow_Up_Emails::get_duration( $email->duration, $email->interval ),
                $email_type->get_trigger_name( $email->trigger ),
                $meta['points_greater_than']
            );
        }

        return $string;
    }

}

$GLOBALS['fue_points_and_rewards'] = new FUE_Addon_Points_And_Rewards();
