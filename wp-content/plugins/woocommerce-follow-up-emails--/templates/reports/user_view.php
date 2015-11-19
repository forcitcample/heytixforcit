<?php
/**
 * @var array $reports
 * @var Wpdb $wpdb
 */

if ( empty($reports) ) {
    $heading = sprintf(__('Report for %s', 'wc_folloup_emails'), $email);
} else {
    $report = $reports[0];
    $heading = sprintf(__('Report for %s (%s)', 'wc_folloup_emails'), $report->customer_name, $report->email_address);
}

if ( $user_id ):
?>
<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
    <a href="<?php echo get_edit_user_link( $user_id ); ?>" class="nav-tab"><?php _e('Personal Options', 'follow_up_emails'); ?></a>
    <a href="#" class="nav-tab nav-tab-active"><?php _e('Email Reports', 'follow_up_emails'); ?></a>
</h2>
<?php endif; ?>

<h3><?php echo $heading; ?></h3>
<table class="wp-list-table widefat fixed posts">
    <thead>
    <tr>
        <th scope="col" id="type" class="manage-column column-type" style=""><?php _e('Customer Name', 'follow_up_emails'); ?></th>
        <th scope="col" id="user_email" class="manage-column column-user_email" style=""><?php _e('Customer Email', 'follow_up_emails'); ?></th>
        <th scope="col" id="product" class="manage-column column-product" style=""><?php _e('Product', 'wc_folloup_emails'); ?></th>
        <th scope="col" id="email_name" class="manage-column column-email_name" style=""><?php _e('Email', 'wc_folloup_emails'); ?></th>
        <th scope="col" id="trigger" class="manage-column column-trigger" style=""><?php _e('Trigger', 'wc_folloup_emails'); ?></th>
        <th scope="col" id="date_sent" class="manage-column column-date_sent" style=""><?php _e('Date Sent', 'follow_up_emails'); ?></th>
        <th scope="col" id="order" class="manage-column column-order" style="">&nbsp;</th>
    </tr>
    </thead>
    <tbody id="the_list">
    <?php
    if ( empty($reports) ):
    ?>
        <tr scope="row">
            <th colspan="7"><?php _e('No reports available', 'follow_up_emails'); ?></th>
        </tr>
    <?php
    else:
        foreach ($reports as $report):
    ?>
        <tr scope="row">
            <td class="post-title column-title">
                <?php
                if ($report->user_id != 0) {
                    echo '<strong><a href="edit.php?post_status=all&post_type=shop_order&_customer_user='. $report->user_id .'">'. stripslashes($report->customer_name) .'</a></strong>';
                } else {
                    echo '<strong>'. stripslashes($report->customer_name) .'</strong>';
                }
                ?>
            </td>
            <td><?php echo stripslashes($report->email_address); ?></td>
            <td>
                <?php
                if ( $report->product_id != 0 ) {
                    echo '<a href="'. get_permalink($report->product_id) .'">'. get_the_title($report->product_id) .'</a>';
                }
                ?>
            </td>
            <td><?php echo $report->email_name; ?></td>
            <td><?php echo $report->email_trigger; ?></td>
            <td><?php echo date( get_option('date_format') .' '. get_option('time_format') , strtotime($report->date_sent)); ?></td>
            <td>
                <?php
                if ($report->order_id != 0) {
                    echo '<a href="post.php?post='. $report->order_id .'&action=edit">View Order</a>';
                } else {
                    echo '-';
                }
                ?>
            </td>

        </tr>
    <?php
        endforeach;
    endif;
    ?>
    </tbody>
</table>

<h3><?php _e('Queued Emails', 'follow_up_emails'); ?></h3>

<table class="wp-list-table widefat fixed posts">
    <thead>
    <tr>
        <th scope="col" id="email_order_id" class="manage-column column-email_order_id" style="" width="70"><?php _e('Queue ID', 'follow_up_emails'); ?></th>
        <th scope="col" id="email_name" class="manage-column column-email_name" style=""><?php _e('Follow-Up Email', 'wc_folloup_emails'); ?></th>
        <th scope="col" id="user_email" class="manage-column column-user_email" style=""><?php _e('Email', 'follow_up_emails'); ?></th>
        <th scope="col" id="product" class="manage-column column-product" style=""><?php _e('Product', 'wc_folloup_emails'); ?></th>
        <th scope="col" id="order" class="manage-column column-product" style="" width="60"><?php _e('Order', 'wc_folloup_emails'); ?></th>
        <th scope="col" id="trigger" class="manage-column column-trigger" style=""><?php _e('Trigger', 'wc_folloup_emails'); ?></th>
        <th scope="col" id="date_sent" class="manage-column column-date_sent" style="" width="180"><?php _e('Date Scheduled', 'follow_up_emails'); ?></th>
        <th scope="col" id="status" class="manage-column column-status" style=""><?php _e('Status', 'follow_up_emails'); ?></th>
    </tr>
    </thead>
    <tbody id="the_list">
    <?php if ( empty($queue) ): ?>
        <tr>
            <td colspan="8"><?php _e('No emails on queue', 'follow_up_emails'); ?></td>
        </tr>
    <?php
    else:
        $email_rows     = array();
        $date_format    = get_option('date_format') .' '. get_option('time_format');
        foreach ( $queue as $row ):
            if (! isset($email_rows[$row->email_id]) ) {
                $email_row = new FUE_Email( $row->email_id );
                $email_rows[$row->email_id] = $email_row;
            }
            $email_name = $email_rows[$row->email_id]->name;
            $email = $email_rows[$row->email_id];

            if (! $email->exists() ) {
                continue;
            }
    ?>
        <tr>
            <td><?php _e($row->id); ?></td>
            <td><?php echo $email_name; ?></td>
            <td><?php echo $row->user_email; ?></td>
            <td>
                <?php
                if ( $row->product_id > 0 ) {
                    echo '<a href="post.php?post='. $row->product_id .'&action=edit">'. get_the_title($row->product_id) .'</a>';
                } else {
                    echo '-';
                }
                ?>
            </td>
            <td>
                <?php
                if ( $row->order_id > 0 && ($order = WC_FUE_Compatibility::wc_get_order($row->order_id)) ) {
                    echo '<a href="post.php?post='. $row->order_id .'&action=edit">'. $order->get_order_number() .'</a>';
                } else {
                    echo '-';
                }
                ?>
            </td>
            <td>
                <?php
                $email_trigger = '-';

                if ( $email ) {
                    $email_trigger = $email->get_trigger_string();
                }

                echo apply_filters( 'fue_interval_str', $email_trigger, $email );
                ?>
            </td>
            <td>
                <?php echo date( $date_format, $row->send_on ); ?>
            </td>
            <td class="status">
                <?php
                if ( $row->status == 1 ) {
                    echo __('Queued', 'follow_up_emails');
                    echo '<br/><small><a href="#" class="queue-toggle" data-status="queued" data-id="'. $row->id .'">'. __('Do not send', 'follow_up_emails') .'</a></small>';
                } else {
                    echo __('Suspended', 'follow_up_emails');
                    echo '<br/><small><a href="#" class="queue-toggle" data-status="paused" data-id="'. $row->id .'">'. __('Re-enable', 'follow_up_emails') .'</a></small>';
                }
                ?>
            </td>
        </tr>
    <?php
         endforeach;
    endif;
    ?>
    </tbody>
</table>

<h3><?php _e('Unsubscriptions', 'follow_up_emails'); ?></h3>

<table class="wp-list-table widefat fixed posts" style="width:600px !important;">
    <thead>
    <tr>
        <th scope="col" id="exclude_user_email" class="manage-column column-user_email" style=""><?php _e('Email', 'follow_up_emails'); ?></th>
        <th scope="col" id="exclude_order" class="manage-column column-order" style="" width="60"><?php _e('Order', 'wc_folloup_emails'); ?></th>
        <th scope="col" id="exclude_date_added" class="manage-column column-date_added" style="" width="180"><?php _e('Date Added', 'follow_up_emails'); ?></th>
    </tr>
    </thead>
    <tbody id="the_list">
    <?php if ( empty($excludes) ): ?>
        <tr>
            <td colspan="8"><?php _e('No unsubscriptions found', 'follow_up_emails'); ?></td>
        </tr>
    <?php
    else:
        $date_format    = get_option('date_format') .' '. get_option('time_format');
        foreach ( $excludes as $row ):
            $order_str = ( empty( $row->order_id ) ) ? __('All emails', 'follow_up_emails') : apply_filters( 'woocommerce_order_number', $row->order_id );
            ?>
            <tr>
                <td><?php echo $row->email; ?></td>
                <td><?php echo $order_str; ?></td>
                <td>
                    <?php echo date( $date_format, strtotime($row->date_added) ); ?>
                </td>
            </tr>
        <?php
        endforeach;
    endif;
    ?>
    </tbody>
</table>

<script type="text/javascript">
    jQuery(window).load(function(){
        jQuery(".queue-toggle").live("click", function(e) {
            e.preventDefault();

            var that    = this;
            var parent  = jQuery(this).parents("table");
            var status  = jQuery(this).data("status")
            var id      = jQuery(this).data("id");
            var data    = {
                action: 'fue_toggle_queue_status',
                status: status,
                id: id
            };

            jQuery(parent).block({ message: null, overlayCSS: { background: '#fff url('+ FUE.ajax_loader +') no-repeat center', opacity: 0.6 } });

            jQuery.post(ajaxurl, data, function(resp) {
                resp = jQuery.parseJSON(resp);
                if (resp.ack != "OK") {
                    alert(resp.error);
                } else {
                    var td = jQuery(that).parents("td.status").eq(0);
                    jQuery(td).html(resp.new_status + '<br/><small><a href="#" class="queue-toggle" data-id="'+ id +'">'+ resp.new_action +'</a></small>');
                }
                jQuery(parent).unblock();
            });
        });
    });
</script>