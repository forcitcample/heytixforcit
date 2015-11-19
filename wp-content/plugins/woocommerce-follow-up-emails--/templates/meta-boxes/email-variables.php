<?php
if ( !$email->type ):
?>
<div id="fue-email-variables-notice">
    <p class="meta-box-notice"><?php _e('Please set the email type first', 'follow_up_emails'); ?></p>
</div>
<?php else: ?>
<ul id="fue-email-variables-list">
    <?php do_action('fue_email_variables_list', $email); ?>
    <li class="var hideable var_customer_username"><strong>{customer_username}</strong> <img class="help_tip" title="<?php _e('The username of the customer who purchased from your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
    <li class="var hideable var_customer_first_name"><strong>{customer_first_name}</strong> <img class="help_tip" title="<?php _e('The first name of the customer who purchased from your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
    <li class="var hideable var_customer_name"><strong>{customer_name}</strong> <img class="help_tip" title="<?php _e('The full name of the customer who purchased from your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
    <li class="var hideable var_customer_email"><strong>{customer_email}</strong> <img class="help_tip" title="<?php _e('The email address of the customer who purchased from your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
    <li class="var hideable var_store_url"><strong>{store_url}</strong> <img class="help_tip" title="<?php _e('The URL/Address of your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
    <li class="var hideable var_store_url_secure"><strong>{store_url_secure}</strong> <img class="help_tip" title="<?php _e('The secure URL/Address of your store (HTTPS).', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
    <li class="var hideable var_store_url_path"><strong>{store_url=path}</strong> <img class="help_tip" title="<?php _e('The URL/Address of your store with path added at the end. Ex. {store_url=/categories}', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
    <li class="var hideable var_store_name"><strong>{store_name}</strong> <img class="help_tip" title="<?php _e('The name of your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
    <li class="var hideable var_order var_order_number not-cart non-signup"><strong>{order_number}</strong> <img class="help_tip" title="<?php _e('The generated Order Number for the puchase', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
    <li class="var hideable var_order var_dollar_spent_order not-cart non-signup"><strong>{dollars_spent_order}</strong> <img class="help_tip" title="_e('The the amount spent on an order', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
    <li class="var hideable var_order var_order_date not-cart non-signup"><strong>{order_date}</strong> <img class="help_tip" title="<?php _e('The date that the order was made', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
    <li class="var hideable var_order var_order_datetime not-cart non-signup"><strong>{order_datetime}</strong> <img class="help_tip" title="<?php _e('The date and time that the order was made', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
    <li class="var hideable var_order var_order_billing_address not-cart non-signup"><strong>{order_billing_address}</strong> <img class="help_tip" title="<?php _e('The billing address of the order', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
    <li class="var hideable var_order var_order_shipping_address not-cart non-signup"><strong>{order_shipping_address}</strong> <img class="help_tip" title="<?php _e('The shipping address of the order', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
    <li class="var hideable var_unsubscribe_url"><strong>{unsubscribe_url}</strong> <img class="help_tip" title="<?php _e('URL where users will be able to opt-out of the email list.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
    <li class="var hideable var_post_id"><strong>{post_id=xx}</strong> <img class="help_tip" title="<?php _e('Include the excerpt of the specified Post ID.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
</ul>
<?php endif; ?>