<?php
    $user_email = isset($_REQUEST['gl_email'])?$_REQUEST['gl_email']:'';
?>
<div class="glfb-email-form glform-wrapper">
    <form action="" method="POST">
        <input type="hidden" name="gl_ticketid" value="<?php echo $guestlist_ticket_id; ?>"/>
        <input type="hidden" name="gl_eventid" value="<?php echo $guestlist_event_id; ?>"/>
        <input type="hidden" name="glaction" value="send_confirmation"/>
        <?php if(isset($_REQUEST['identifier'])){ ?>
        <input type="hidden" name="identifier" value="<?php echo $_REQUEST['identifier']; ?>"/>
        <?php }?>
        <?php wp_nonce_field('gl_confirm_' . $guestlist_event_id, '_glnonce'); ?>
        <p>Please enter your preferred email address to receive your guest list confirmation link.</p>
        <p><input type="email" name="gl_email" value="<?php echo $user_email; ?>"/></p>
        <p><button class="button vamtam-button button-border accent1 hover-accent1" type="submit"><span class="btext">SEND CONFIRMATION</span></button></p>
    </form>
</div>
