<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
    <a href="admin.php?page=followup-emails-settings&amp;tab=system" class="nav-tab <?php if ($tab == 'system') echo 'nav-tab-active'; ?>"><?php _e('General Settings', 'follow_up_emails'); ?></a>
    <a href="admin.php?page=followup-emails-settings&amp;tab=email" class="nav-tab <?php if ($tab == 'email') echo 'nav-tab-active'; ?>"><?php _e('Permissions and Styling', 'follow_up_emails'); ?></a>
    <a href="admin.php?page=followup-emails-settings&amp;tab=crm" class="nav-tab <?php if ($tab == 'crm') echo 'nav-tab-active'; ?>"><?php _e('Email Settings', 'follow_up_emails'); ?></a>
    <a href="admin.php?page=followup-emails-settings&amp;tab=documentation" class="nav-tab <?php if ($tab == 'documentation') echo 'nav-tab-active'; ?>"><?php _e('Documentation', 'follow_up_emails'); ?></a>
    <a href="admin.php?page=followup-emails-settings&amp;tab=integration" class="nav-tab <?php if ($tab == 'integration') echo 'nav-tab-active'; ?>"><?php _e('Integrations', 'follow_up_emails'); ?></a>
    <?php do_action( 'fue_settings_tabs' ); ?>
</h2>