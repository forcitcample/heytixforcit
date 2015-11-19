<?php if (isset($_GET['settings_updated'])): ?>
    <div id="message" class="updated"><p><?php _e('Settings updated', 'follow_up_emails'); ?></p></div>
<?php endif; ?>

<?php if (isset($_GET['imported'])): ?>
    <div id="message" class="updated"><p><?php _e('Data imported successfully', 'follow_up_emails'); ?></p></div>
<?php endif; ?>