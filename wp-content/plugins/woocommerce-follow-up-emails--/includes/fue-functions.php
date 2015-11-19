<?php

/**
 * Get all installed templates including ones inside the active theme
 * @return array
 */
function fue_get_installed_templates() {
    $fue_pattern    = FUE_TEMPLATES_DIR .'/emails/*.html';
    $theme_pattern  = get_stylesheet_directory() .'/follow-up-emails/emails/*.html';

    $fue_templates   = glob( $fue_pattern );
    $theme_templates = glob( $theme_pattern );

    if ( false === $fue_templates ) {
        $fue_templates = array();
    }

    if ( false === $theme_templates ) {
        $theme_templates = array();
    }

    $templates = array_filter( array_merge( $fue_templates, $theme_templates ) );

    sort( $templates );

    return apply_filters( 'fue_installed_templates', $templates );
}

/**
 * Get the different conditions available for $email.
 *
 * Add-ons can hook to the fue_trigger_conditions filter to register custom conditions
 *
 * @param FUE_Email $email
 * @return array
 */
function fue_get_trigger_conditions( $email = null ) {
    return apply_filters( 'fue_trigger_conditions', array(), $email );
}

/**
 * Get the proper string representation of a duration depending on the value
 *
 * @deprecated
 * @param string    $duration
 * @param int       $value
 * @return string
 */
function fue_get_duration_string( $duration, $value = 0 ) {
    _deprecated_function( 'fue_get_duration_string', '4.0', 'Follow_Up_Emails::get_duration()' );
    return Follow_Up_Emails::get_duration( $duration, $value );
}

/**
 * Create a new FUE_Email. @see fue_save_email()
 *
 * @param array $args Optional array of arguments
 * @return int|WP_Error The new email ID or WP_Error on error
 */
function fue_create_email( $args ) {
    return fue_save_email( $args );
}

/**
 * Update an existing FUE_Email
 *
 * The ID of the email to update must be passed to the $args parameter.
 * The rest of the keys are similar to @see fue_create_email(). Only pass
 * the data that needs updating.
 *
 * @param array $args
 * @return int|WP_Error Returns the email ID on success, WP_Error on error
 */
function fue_update_email( $args ) {

    if ( isset( $args['id'] ) ) {
        $args['ID'] = $args['id'];
    }

    if ( !isset( $args['ID'] ) || empty( $args['ID'] ) ) {
        return new WP_Error( 'update_email', __('Cannot update email without the ID', 'follow_up_email') );
    }

    return fue_save_email( $args );
}

/**
 * Create a new, or update an existing FUE_Email. If ID is passed, it will update
 * the email with the matching ID.
 *
 * $args
 *  - name: Name of the email
 *  - type (e.g. storewide, subscription, etc)
 *  - subject: Email Subject
 *  - message: Email Content
 *  - status (default: fue-inactive)
 *  - priority
 *
 * Other keys can be passed to $args and they will be saved as postmeta
 *
 * @param array $args Optional array of arguments
 * @return int|WP_Error The new email ID or WP_Error on error
 */
function fue_save_email( $args ) {

    $post_args  = array( 'post_type' => 'follow_up_email' );
    $updating   = false;

    // support both id and ID
    if ( isset( $args['id'] ) ) {
        $args['ID'] = $args['id'];
        unset( $args['id'] );
    }

    if ( !empty( $args['ID'] ) ) {
        // update email
        $post_args['ID']    = $args['ID'];
        $updating           = true;
        $email_id           = $args['ID'];
    } else {
        $args['ID'] = 0;
    }

    // only save if the email type is valid
    if ( !empty( $args['type'] ) ) {
        $email_type = Follow_Up_Emails::get_email_type( $args['type'] );

        if ( $email_type === false ) {
            return new WP_Error( 'fue_save_email', sprintf( __('Invalid email type passed (%s)', 'follow_up_emails'), $args['type'] ) );
        }
    }

    $args = apply_filters('fue_email_pre_save', $args, $args['ID']);

    if ( isset( $args['name'] ) ) {
        $post_args['post_title'] = $args['name'];
    }

    if ( isset( $args['subject'] ) ) {
        $post_args['post_excerpt'] = $args['subject'];
    }

    if ( isset( $args['message'] ) ) {
        $post_args['post_content'] = $args['message'];
    }

    if ( isset( $args['status'] ) ) {
        $post_args['post_status'] = $args['status'];
    }

    if ( isset( $args['priority'] ) ) {
        $post_args['menu_order'] = $args['priority'];
    }

    if ( $updating ) {
        wp_update_post( $post_args );
        $email_id = $args['ID'];
    } else {
        $email_id = wp_insert_post( $post_args );
    }

    if ( is_wp_error( $email_id ) ) {
        return $email_id;
    }

    // set email type
    if ( isset( $args['type'] ) ) {
        $type = $args['type'];

        wp_set_object_terms( $email_id, $type, 'follow_up_email_type', false );

        // data cleanup
        switch ( $args['type'] ) {

            case 'customer':
                $args['product_id']    = 0;
                $args['category_id']   = 0;
                break;

            case 'signup':
                $args['product_id']     = 0;
                $args['category_id']    = 0;
                $args['always_send']    = 1;
                $args['interval_type']  = 'signup';
                break;

            case 'manual':
                $args['interval_type']      = 'manual';
                $args['interval_duration']  = 0;
                break;

            case 'reminder':
                $args['always_send']    = 1;
                break;

        }

    }

    // update campaigns
    if ( isset( $args['campaign'] ) ) {

        if ( empty( $args['campaign'] ) ) {
            // trying to remove campaigns
            wp_set_object_terms( $email_id, null, 'follow_up_email_campaign' );
        } else {
            if ( is_array( $args['campaign' ] ) ) {
                $campaigns = $args['campaign'];
            } else {
                $campaigns = array_filter( array_map( 'trim', explode( ',', $args['campaign'] ) ) );
            }

            wp_set_object_terms( $email_id, $campaigns, 'follow_up_email_campaign' );
        }

    }

    // Always Send always defaults to 0
    if ( isset($args['always_send'] ) && empty($args['always_send']) ) {
        $args['always_send'] = 0;
    }

    // empty product and category IDs must always be 0
    if ( isset( $args['product_id'] ) && empty( $args['product_id'] ) ) {
        $args['product_id'] = 0;
    }

    if ( isset( $args['category_id'] ) && empty( $args['category_id'] ) ) {
        $args['category_id'] = 0;
    }

    // unset the already processed keys and store the remaining keys as postmeta
    unset(
        $args['name'], $args['subject'], $args['message'], $args['status'], $args['type'],
        $args['ID'], $args['priority']
    );

    if ( isset($args['tracking_on']) && $args['tracking_on'] == 0 ) {
        $args['tracking_code'] = '';
    }

    if ( isset($args['interval_duration']) && $args['interval_duration'] == 'date' ) {
        $args['interval_type'] = 'date';
    }

    foreach ( $args as $meta_key => $meta_value ) {

        // merge the meta field
        if ( $meta_key == 'meta' ) {
            $meta_value = maybe_unserialize( $meta_value );

            $old_meta = get_post_meta( $email_id, '_meta', true );

            if (! is_array($old_meta) )
                $old_meta = maybe_unserialize( $old_meta );

            if ( is_array( $old_meta ) )
                $meta_value = array_merge( $old_meta, $meta_value );
        }

        update_post_meta( $email_id, '_'. $meta_key, $meta_value );
    }

    if ( $updating ) {
        do_action('fue_email_updated', $email_id, $args);
    } else {
        do_action('fue_email_created', $email_id, $args);
    }

    return $email_id;

}

/**
 * Clone an existing FUE_Email
 *
 * @param $id
 * @param $new_name
 *
 * @return int|WP_Error
 */
function fue_clone_email($id, $new_name) {
    global $wpdb;

    $original_email = new FUE_Email( $id );
    $email_row      = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE ID = %d",
        $id
    ), ARRAY_A );

    if ( $email_row ) {
        unset(
            $email_row['ID'], $email_row['post_date'], $email_row['post_date_gmt'],
            $email_row['post_modified'], $email_row['post_modified_gmt'], $email_row['guid'],
            $email_row['post_name']
        );

        $email_row['post_title'] = $new_name;

        $wpdb->insert( $wpdb->posts, $email_row );
        $new_id = $wpdb->insert_id;

        // email type
        $type = $original_email->type;
        wp_set_object_terms( $new_id, $type, 'follow_up_email_type' );

        // campaign
        $campaigns = wp_get_object_terms( $id, 'follow_up_email_campaign' );

        if ( !is_wp_error( $campaigns ) ) {
            $campaign_slugs = array();
            foreach ( $campaigns as $campaign ) {
                $campaign_slugs[] = $campaign->slug;
            }

            if ( !empty( $campaign_slugs ) ) {
                wp_set_object_terms( $new_id, $campaign_slugs, 'follow_up_email_campaign' );
            }
        }

        // copy the meta
        $meta = get_post_meta( $id );

        foreach ( $meta as $key => $value ) {
            update_post_meta( $new_id, $key, $value[0] );
        }

        // set the usage count to 0
        update_post_meta( $new_id, '_usage_count', 0 );

        do_action('fue_email_cloned', $new_id, $id);

        return $new_id;
    } else {
        return new WP_Error( sprintf(__('Email (%d) could not be found', 'follow_up_emails'), $id) );
    }

}

/**
 * Quickly get an FUE_Email's type without instantiating FUE_Email
 *
 * @param int $id
 * @return string String representation of the email type
 */
function fue_get_email_type( $id ) {

    $terms  = get_the_terms( $id, 'follow_up_email_type' );
    $type   = ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'storewide';

    return $type;
}

/**
 * Get FUE_Emails based on type and status
 *
 * @param string        $type The email type (e.g. storewide, product, etc). Use 'any' to return all email types
 * @param string|array  $status
 * @param array         $filters Additional filters (<a href="http://codex.wordpress.org/Class_Reference/WP_Query#Parameters">CodEx</a>)
 *
 * @return array
 */
function fue_get_emails( $type = 'any', $status = '', $filters = array() ) {
    $args = array(
        'nopaging'  => true,
        'orderby'   => 'menu_order',
        'order'     => 'ASC',
        'post_type' => 'follow_up_email'
    );

    if ( !empty( $status ) ) {
        $args['post_status'] = $status;
    } else {
        $args['post_status'] = array( FUE_Email::STATUS_ACTIVE, FUE_Email::STATUS_INACTIVE, FUE_Email::STATUS_ARCHIVED );
    }

    if ( $type != 'any' ) {
        $args['tax_query'][] = array(
            'taxonomy'  => 'follow_up_email_type',
            'terms'     => $type,
            'field'     => 'slug'
        );
    }

    if ( !isset( $args['orderby'] ) ) {
        $args['orderby']    = 'menu_order';
        $args['order']      = 'ASC';
    }

    // apply the custom filters
    if ( !empty( $filters ) )
        $args = array_merge( $args, $filters );

    $args = apply_filters( 'fue_get_emails_args', $args, $type );

    $rows   = get_posts( $args );
    $emails = array();

    if ( !empty( $args['fields'] ) && $args['fields'] == 'ids' ) {
        return $rows;
    }

    foreach ( $rows as $row ) {
        $emails[] = new FUE_Email( $row->ID );
    }

    return $emails;

}

/**
 * Add an email address to the list of exclusions
 *
 * @param string $email_address
 * @param int $email_id The email ID that triggered this unsubscription
 * @param int $order_id Limit the unsubscription to this order ID only
 *
 * @return int The inserted ID
 */
function fue_exclude_email_address( $email_address, $email_id = 0, $order_id = 0 ) {
    $wpdb = Follow_Up_Emails::instance()->wpdb;

    $email_name = '-';
    if ( $email_id > 0 )
        $email_name = get_the_title( $email_id );

    $wpdb->insert(
        $wpdb->prefix .'followup_email_excludes',
        array(
            'email_id'      => $email_id,
            'order_id'      => $order_id,
            'email_name'    => $email_name,
            'email'         => $email_address,
            'date_added'    => current_time( 'mysql' )
        )
    );

    return $wpdb->insert_id;

}

/**
 * Check the provided $email_address if it is in the list of exclusions
 *
 * @param string    $email_address
 * @param int       $email_id
 * @param int       $order_id
 * @return bool
 */
function fue_is_email_excluded( $email_address, $email_id = 0, $order_id = 0 ) {
    $wpdb       = Follow_Up_Emails::instance()->wpdb;
    $excluded   = false;
    $params     = array( $email_address );

    $sql = "SELECT COUNT(*)
            FROM {$wpdb->prefix}followup_email_excludes
            WHERE email = %s";

    if ( $email_id > 0 ) {
        $sql .= " AND email_id = %d";
        $params[] = $email_id;
    }

    if ( $order_id > 0 ) {
        $sql .= " AND order_id = %d";
        $params[] = $order_id;
    } else {
        $sql .= " AND order_id = 0";
    }

    if ( !empty( $params ) ) {
        $sql = $wpdb->prepare( $sql, $params );
    }

    $rows = $wpdb->get_var( $sql );

    if ( $rows > 0 )
        $excluded = true;

    return apply_filters( 'fue_is_email_excluded', $excluded, $email_address, $email_id );
}

/**
 * Set a user to not receive follow-up emails in general or only order-specific emails
 * @param int $user_id
 * @param int $order_id
 */
function fue_add_user_opt_out( $user_id, $order_id = null ) {
    if ( is_null( $order_id ) ) {
        update_user_meta( $user_id, 'fue_opted_out', true );
    } else {
        $opt_out_orders = get_user_meta( $user_id, 'fue_opted_out_orders', true );

        if ( !$opt_out_orders ) {
            $opt_out_orders = array();
        }

        $opt_out_orders[ $order_id ] = current_time( 'mysql', true );

        update_user_meta( $user_id, 'fue_opted_out_orders', $opt_out_orders );
    }
}

/**
 * Set the user to receive follow-up emails again in general or only order-specific emails
 * @param int $user_id
 * @param int $order_id
 */
function fue_remove_user_opt_out( $user_id, $order_id = null ) {
    if ( is_null( $order_id ) ) {
        update_user_meta( $user_id, 'fue_opted_out', false );
    } else {
        $opt_out_orders = get_user_meta( $user_id, 'fue_opted_out_orders', true );

        if ( !$opt_out_orders ) {
            $opt_out_orders = array();
        }

        unset( $opt_out_orders[ $order_id ] );

        update_user_meta( $user_id, 'fue_opted_out_orders', $opt_out_orders );
    }
}

/**
 * Check if a registered user has chosen to not receive follow-up emails
 *
 * @param int $user_id
 * @param int $order_id
 * @return bool
 */
function fue_user_opted_out( $user_id, $order_id = null ) {
    $opt_out = get_user_meta( $user_id, 'fue_opted_out', true );

    $opt_out = ( $opt_out != true ) ? false : true;

    if ( !$opt_out && !is_null( $order_id ) ) {
        $opt_out_orders = get_user_meta( $user_id, 'fue_opted_out_orders', true );

        if ( !$opt_out_orders ) {
            $opt_out_orders = array();
        }

        if ( array_key_exists( $order_id, $opt_out_orders ) !== false ) {
            $opt_out = true;
        }
    }

    return apply_filters( 'fue_user_opt_out', $opt_out, $user_id );
}

/**
 * Create a new FUE Coupon
 *
 * @param array $args
 * @return int|WP_Error The coupon ID on success or WP_Error on failure
 */
function fue_insert_coupon( $args = array() ) {
    global $wpdb;

    $defaults = array(
        'coupon_name'   => '',
        'coupon_prefix' => '',
        'coupon_type'   => '',
        'amount'        => 0.0,
        'individual'    => 0,
        'before_tax'    => 0,
        'exclude_sale_items'    => 0,
        'free_shipping' => 0,
        'minimum_amount'=> '',
        'maximum_amount'=> '',
        'usage_limit'   => '',
        'usage_limit_per_user'   => '',
        'expiry_value'  => '',
        'expiry_type'   => ''
    );

    $args   = wp_parse_args( $args, $defaults );

    if ( isset( $args['id'] ) ) {
        // updating
        $id = $args['id'];

        unset( $args['id'] );

        $wpdb->update(
            $wpdb->prefix .'followup_coupons',
            $args,
            array( 'id' => $id )
        );
    } else {
        // new coupon
        $wpdb->insert( $wpdb->prefix .'followup_coupons', $args );

        $id = $wpdb->insert_id;
    }

    return $id;
}

function fue_create_coupon( $args ) {
    return fue_insert_coupon( $args );
}

/**
 * Update an existing FUE Coupon
 *
 * @param array $args
 * @return int|WP_Error
 */
function fue_update_coupon( $args ) {
    if ( !isset( $args['id'] ) || empty( $args['id'] ) ) {
        return new WP_Error( 'update_email', __('Cannot update coupon without the ID', 'follow_up_email') );
    }

    return fue_insert_coupon( $args );
}

/**
 * Delete the specified coupon from the DB
 * @param int $id
 */
function fue_delete_coupon( $id ) {
    $wpdb = Follow_Up_Emails::instance()->wpdb;
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}followup_coupons WHERE id = %d", $id));
}

/**
 * Timezone - helper to retrieve the timezone string for a site until
 * a WP core method exists (see http://core.trac.wordpress.org/ticket/24730)
 *
 * Adapted from http://www.php.net/manual/en/function.timezone-name-from-abbr.php#89155
 *
 * @since 4.1
 * @return string a valid PHP timezone string for the site
 */
function fue_timezone_string() {

    // if site timezone string exists, return it
    if ( $timezone = get_option( 'timezone_string' ) ) {
        return $timezone;
    }

    // get UTC offset, if it isn't set then return UTC
    if ( 0 === ( $utc_offset = get_option( 'gmt_offset', 0 ) ) ) {
        return 'UTC';
    }

    // adjust UTC offset from hours to seconds
    $utc_offset *= 3600;

    // attempt to guess the timezone string from the UTC offset
    $timezone = timezone_name_from_abbr( '', $utc_offset );

    // last try, guess timezone string manually
    if ( false === $timezone ) {

        $is_dst = date( 'I' );

        foreach ( timezone_abbreviations_list() as $abbr ) {
            foreach ( $abbr as $city ) {

                if ( $city['dst'] == $is_dst && $city['offset'] == $utc_offset ) {
                    return $city['timezone_id'];
                }
            }
        }
    }

    // fallback to UTC
    return 'UTC';
}

/**
 * Format the date/time by zero-padding values that are < 10
 * @param object $email Email Object
 *
 * @return string
 */
function fue_format_send_datetime( $email ) {
    $meta = maybe_unserialize($email->meta);

    return (!empty($email->send_date_hour)) ? $email->send_date .' '. fue_zero_pad_time( $email->send_date_hour ) .':'. fue_zero_pad_time( $email->send_date_minute ) .' '. $meta['send_date_ampm'] : $email->send_date;
}

/**
 * Zero-pad a number if it is less than 10.
 *
 * @param int $number
 * @return string
 */
function fue_zero_pad_time( $number ) {
    if ( intval( $number ) < 10 )
        $number = '0' . $number;

    return $number;
}

/**
 * Return the custom field value that matches a preg test.
 *
 * This is a callback function for preg_replace_callback - @see FUE_Mailer::send_email_order()
 *
 * @param array $matches
 *
 * @return string
 */
function fue_add_custom_fields( $matches ) {

    if ( empty($matches) ) return '';

    $post_id    = $matches[1];
    $field_key  = $matches[2];

    $meta = get_post_meta( $post_id, $field_key, true );

    if ($meta) {

        if ( $field_key == '_downloadable_files' ) {
            // link download URLs and enclose in <li> tags if there's more than one
            if ( count($meta) == 1 ) {
                $file = array_pop($meta);
                $meta = '<a href="'. $file['file'] .'">'. $file['name'] .'</a>';
            } else {
                $list = '<ul>';
                foreach ( $meta as $file ) {
                    $list .= '<li><a href="'. $file['file'] .'">'. $file['name'] .'</a></li>';
                }
                $list .= '</ul>';

                $meta = $list;
            }
        }

        return $meta;
    }
    return '';
}

/**
 * Return an excerpt of a post that matches a preg test.
 *
 * This is a callback function for preg_replace_callback - @see FUE_Mailer::send_email_order()
 *
 * @param array $matches
 *
 * @return string
 */
function fue_add_post( $matches ) {
    if ( empty($matches) ) return '';
    if (! isset($matches[1]) || empty($matches[1]) ) return '';

    $id = $matches[1];

    $post = get_post( $id );

    if ( isset($post->post_excerpt) )
        return $post->post_excerpt;
    else
        return '';
}

/**
 * Retrieve Page IDs
 * @param string $page
 *
 * @return int|mixed|void
 */
function fue_get_page_id( $page ) {
    $page = get_option('fue_' . $page . '_page_id');

    return ( $page ) ? $page : -1;
}

/**
 * Get the FUE Customer based on user ID or email address used in the $order
 *
 * @param int|WC_Order $order
 * @return object
 * @since 4.1
 */
function fue_get_customer_from_order( $order ) {

    if ( is_numeric( $order ) ) {
        $order = WC_FUE_Compatibility::wc_get_order( $order );
    }

    return fue_get_customer( WC_FUE_Compatibility::get_order_user_id( $order ), $order->billing_email );

}

/**
 * Get an FUE Customer matching either the user ID or email address
 *
 * @param int       $user_id
 * @param string    $user_email
 *
 * @return object|null
 */
function fue_get_customer( $user_id = 0, $user_email = '' ) {
    global $wpdb;

    if ( empty( $user_id ) && empty( $user_email ) ) {
        return null;
    }

    $vars   = array();
    $sql    = "SELECT *
              FROM {$wpdb->prefix}followup_customers
              WHERE 1=1";

    if ( $user_id > 0 ) {
        $sql .= " AND user_id = %d";
        $vars[] = $user_id;
    } elseif ( !empty( $user_email ) ) {
        $sql .= " AND email_address = %s";
        $vars[] = $user_email;
    }

    if ( !empty($vars) ) {
        $sql = $wpdb->prepare( $sql, $vars );
    }

    return $wpdb->get_row( $sql );
}

function fue_get_subscribers() {
    $wpdb = Follow_Up_Emails::instance()->wpdb;

    return $wpdb->get_col("SELECT email FROM {$wpdb->prefix}followup_subscribers");
}

/**
 * Add an entry to the followup_subscribers table
 * @param string $email
 * @return int|WP_Error
 */
function fue_add_subscriber( $email ) {
    $email = sanitize_email( $email );

    if ( !is_email( $email ) ) {
        return new WP_Error( 'fue_add_subscriber', __('Please enter a valid email address', 'follow_up_emails') );
    }

    if ( fue_subscriber_email_exists( $email ) ) {
        return new WP_Error( 'fue_add_subscriber', __('The email address is already in use', 'follow_up_emails') );
    }

    $wpdb = Follow_Up_Emails::instance()->wpdb;

    $insert = array(
        'email'         => $email,
        'date_added'    => current_time( 'mysql' )
    );
    $wpdb->insert( $wpdb->prefix .'followup_subscribers', $insert );

    return $wpdb->insert_id;
}

/**
 * Check if the given email address is already a subscriber
 * @param string $email
 * @return bool
 */
function fue_subscriber_email_exists( $email ) {
    $wpdb = Follow_Up_Emails::instance()->wpdb;

    $count = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*)
        FROM {$wpdb->prefix}followup_subscribers
        WHERE email = %s"
        , $email
    ) );

    return $count > 0;
}

/**
 * Get the URL to the unsubscribe endpoint
 */
function fue_get_unsubscribe_url() {
    return apply_filters( 'fue_email_unsubscribe_url', site_url( '/unsubscribe/' ) );
}

/**
 * Get the URL to the email-subscriptions endpoint
 */
function fue_get_email_subscriptions_url() {
    return apply_filters( 'fue_email_unsubscribe_url', site_url( '/my-account/email-subscriptions/' ) );
}

/**
 * Get the URL to the FUE REST API
 *
 * @since 4.1
 * @param string $path an endpoint to include in the URL
 * @return string the URL
 */
function fue_get_api_url( $path ) {

    $version = defined( 'FUE_API_REQUEST_VERSION' ) ? FUE_API_REQUEST_VERSION : FUE_API::VERSION;

    $url = get_home_url( null, "fue-api/v{$version}/", is_ssl() ? 'https' : 'http' );

    if ( ! empty( $path ) && is_string( $path ) ) {
        $url .= ltrim( $path, '/' );
    }

    return $url;
}

/**
 * Get other templates passing attributes and including the file.
 *
 * @param string $template_name
 * @param array $args (default: array())
 * @param string $template_path (default: '')
 * @param string $default_path (default: '')
 * @return void
 */
function fue_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
    if ( $args && is_array( $args ) ) {
        extract( $args );
    }

    $located = fue_locate_template( $template_name, $template_path, $default_path );

    // Allow 3rd party plugin filter template file from their plugin
    $located = apply_filters( 'fue_get_template', $located, $template_name, $args, $template_path, $default_path );

    if ( ! file_exists( $located ) ) {
        _doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '2.1' );
        return;
    }

    do_action( 'fue_before_template_part', $template_name, $template_path, $located, $args );

    include( $located );

    do_action( 'fue_after_template_part', $template_name, $template_path, $located, $args );
}

/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 *		yourtheme		/	$template_path	/	$template_name
 *		yourtheme		/	$template_name
 *		$default_path	/	$template_name
 *
 * @param string $template_name
 * @param string $template_path (default: '')
 * @param string $default_path (default: '')
 * @return string
 */
function fue_locate_template( $template_name, $template_path = '', $default_path = '' ) {
    if ( ! $template_path ) {
        $template_path = 'follow-up-emails/';
    }

    if ( ! $default_path ) {
        $default_path = trailingslashit(FUE_TEMPLATES_DIR);
    }

    // Look within passed path within the theme - this is priority
    $template = locate_template(
        array(
            trailingslashit( $template_path ) . $template_name,
            $template_name
        )
    );

    // Get default template
    if ( ! $template ) {
        $template = $default_path . $template_name;
    }

    // Return what we found
    return apply_filters( 'fue_locate_template', $template, $template_name, $template_path );
}

/**
 * Search for and extract a portion of a string
 *
 * @param string    $start
 * @param string    $end
 * @param string    $string
 * @param bool      $borders
 *
 * @return mixed
 */
function fue_str_search($start, $end, $string, $borders = false) {
    $reg = "!".preg_quote ($start)."(.*?)".preg_quote ($end)."!is";
    preg_match_all ($reg, $string, $matches);
    if ($borders) {
        return $matches[0];
    } else {
        return $matches[1];
    }
}

/**
 * A backwards-compatible way of getting a product
 */
if (! function_exists('sfn_get_product') ) {
    function sfn_get_product( $product_id ) {
        _deprecated_function( 'sfn_get_product', '3.7', 'WC_FUE_Compatibility::wc_get_product' );

        return WC_FUE_Compatibility::wc_get_product( $product_id );
    }
}

/**
 * Override the default Logger class of Action-Scheduler to stop logging FUE actions
 *
 * @param string Logger class name
 * @return string
 */
function fue_add_logger_class( $class ) {
    if ( get_option( 'fue_disable_action_scheduler_logging', true ) ) {
        $class = "FUE_ActionScheduler_Logger";
    }

    return $class;
}

if ( ! version_compare( get_option('woocommerce_version'), '2.1', '>=' ) && !function_exists('WC') ) {
    /**
     * Get the WooCommerce instance without using the $wocommerce global variable
     * @return WooCommerce
     */
    function WC() {
        return WooCommerce::instance();
    }
}