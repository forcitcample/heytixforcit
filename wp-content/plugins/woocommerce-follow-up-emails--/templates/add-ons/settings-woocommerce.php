<h3><?php _e('Remove WooCommerce Email Styles', 'follow_up_emails'); ?></h3>

<p><?php _e('You can easily remove WooCommerce email styles to quickly be able to add full HTML to your emails directly in the email editor. Simply check this box, and the default WooCommerce styling will be removed from the emails you send via Follow-up Emails. Conversely, you can create your own templates and choose them instead of the default WooCommerce template.', 'follow_up_emails'); ?></p>

<table class="form-table">
    <tr>
        <th>
            <label for="disable_email_wrapping">
                <input type="checkbox" name="disable_email_wrapping" id="disable_email_wrapping" value="1" <?php if (1 == get_option('fue_disable_wrapping', 0)) echo 'checked'; ?> />
                <?php _e('Click here to disable the wrapping of styles in the WooCommerce email templates.', 'follow_up_emails'); ?>
            </label>
        </th>
    </tr>
</table>