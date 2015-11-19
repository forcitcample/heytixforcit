<?php
$coupons = FUE_Coupons::get_coupons();
?>
<div class="wrap woocommerce">
    <div class="icon32"><img src="<?php echo FUE_TEMPLATES_URL .'/images/send_mail.png'; ?>" /></div>
    <h2>
        <?php _e('Follow-Up Emails &raquo; Email Coupons', 'follow_up_emails'); ?>
        <a href="admin.php?page=followup-emails-coupons&action=new-coupon" class="add-new-h2"><?php _e('Add Coupon', 'wc_followup_emalis'); ?></a>
    </h2>

    <?php include 'notifications.php'; ?>

    <form action="admin-post.php" method="post">
        <table class="wp-list-table widefat fixed posts">
            <thead>
            <tr>
                <th scope="col" id="name" class="manage-column column-name" style=""><?php _e('Name', 'follow_up_emails'); ?></th>
                <th scope="col" id="type" class="manage-column column-type" style=""><?php _e('Type', 'follow_up_emails'); ?></th>
                <th scope="col" id="amount" class="manage-column column-amount" style=""><?php _e('Amount', 'follow_up_emails'); ?></th>
                <th scope="col" id="usage_count" class="manage-column column-usage_count" style=""><?php _e('Sent', 'follow_up_emails'); ?></th>
            </tr>
            </thead>
            <tbody id="the_list">
            <?php
            if (empty($coupons)):
            ?>
            <tr scope="row">
                <th colspan="4"><?php _e('No coupons available', 'follow_up_emails'); ?></th>
            </tr>
            <?php
            else:
                foreach ($coupons as $coupon):
            ?>
            <tr scope="row">
                <td class="post-title column-title">
                    <strong><a class="row-title" href="admin.php?page=followup-emails-coupons&action=edit-coupon&id=<?php echo $coupon->id; ?>"><?php echo stripslashes($coupon->coupon_name); ?></a></strong>
                    <div class="row-actions">
                        <span class="edit"><a href="admin.php?page=followup-emails-coupons&action=edit-coupon&id=<?php echo $coupon->id; ?>"><?php _e('Edit', 'follow_up_emails'); ?></a></span>
                        |
                        <span class="trash"><a onclick="return confirm('<?php _e('Really delete this entry?', 'follow_up_emails'); ?>');" href="admin-post.php?action=fue_delete_coupon&id=<?php echo $coupon->id; ?>"><?php _e('Delete', 'follow_up_emails'); ?></a></span>
                    </div>
                </td>
                <td><?php echo FUE_Coupons::get_discount_type($coupon->coupon_type); ?></td>
                <td><?php echo floatval($coupon->amount); ?></td>
                <td><?php echo $coupon->usage_count; ?></td>
            </tr>
            <?php
                endforeach;
            endif;
            ?>
            </tbody>
        </table>
    </form>
</div>