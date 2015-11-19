<div class="wrap">
    <div class="icon32"><img src="<?php echo FUE_TEMPLATES_URL; ?>/images/send_mail.png" /></div>
    <h2>
        <?php _e('Follow-Up Emails &mdash; Settings', 'follow_up_emails'); ?>
    </h2>

    <?php if (isset($_GET['settings_updated'])): ?>
        <div id="message" class="updated"><p><?php _e('Settings updated', 'follow_up_emails'); ?></p></div>
    <?php endif; ?>

    <?php if (isset($_GET['imported'])): ?>
        <div id="message" class="updated"><p><?php _e('Data imported successfully', 'follow_up_emails'); ?></p></div>
    <?php endif; ?>

    <?php
    if (isset($_GET['switched_scheduler'])):
        if ( $_GET['switched_scheduler'] == 'as' )
            $msg = __('Scheduler changed to Action Scheduler', 'follow_up_emails');
        else
            $msg = __('Scheduler changed to WP Cron', 'follow_up_emails');
    ?>
        <div id="message" class="updated"><p><?php echo $msg ?></p></div>
    <?php endif; ?>

    <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
        <a href="admin.php?page=followup-emails-settings&amp;tab=email" class="nav-tab <?php if ($tab == 'email') echo 'nav-tab-active'; ?>"><?php _e('Email Settings', 'follow_up_emails'); ?></a>
        <a href="admin.php?page=followup-emails-settings&amp;tab=crm" class="nav-tab <?php if ($tab == 'crm') echo 'nav-tab-active'; ?>"><?php _e('CRM', 'follow_up_emails'); ?></a>
        <a href="admin.php?page=followup-emails-settings&amp;tab=system" class="nav-tab <?php if ($tab == 'system') echo 'nav-tab-active'; ?>"><?php _e('System Performance', 'follow_up_emails'); ?></a>
        <a href="admin.php?page=followup-emails-settings&amp;tab=documentation" class="nav-tab <?php if ($tab == 'documentation') echo 'nav-tab-active'; ?>"><?php _e('Documentation', 'follow_up_emails'); ?></a>
        <?php do_action( 'fue_settings_tabs' ); ?>
    </h2>

    <?php
    switch ( $tab ) {

        case 'email':
            include 'settings-email.php';
            break;

        case 'crm':
            include 'settings-crm.php';
            break;

        case 'system':
            include 'settings-system.php';
            break;

        case 'documentation':
            include 'settings-documentation.php';
            break;

        default:
            do_action( "fue_settings_{$tab}" );
            break;

    }

    do_action('fue_settings_form');

    ?>

</div>
