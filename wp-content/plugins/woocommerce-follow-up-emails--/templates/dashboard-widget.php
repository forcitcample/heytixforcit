<div class="main">
    <ul>
        <li>
            <span class="dashicons dashicons-email"></span>
            <strong>
                <?php echo $stats['total_emails_sent']; ?>
            </strong>
            <?php _e('total emails sent', 'follow_up_emails'); ?>
        </li>
        <li>
            <span class="dashicons dashicons-calendar-alt"></span>
            <strong><?php echo $stats['emails_sent_today']; ?></strong>
            <?php _e('emails sent today', 'follow_up_emails'); ?>
        </li>
        <li>
            <span class="dashicons dashicons-clock"></span>
            <strong><?php echo $stats['emails_scheduled_total']; ?></strong>
            <?php _e('scheduled emails total', 'follow_up_emails'); ?>
        </li>
    </ul>
</div>