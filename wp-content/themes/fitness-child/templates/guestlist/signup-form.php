<?php
$gl_firstname = !empty($_POST['gl_firstname'])?$_POST['gl_firstname']:'';
$gl_lastname= !empty($_POST['gl_lastname'])?$_POST['gl_lastname']:'';
$gl_email = !empty($_POST['gl_email'])?$_POST['gl_email']:'';
if(!$guestlist_ticket_id){
    $guestlist_ticket_id = !empty($_REQUEST['gl_ticketid'])?(int)$_REQUEST['gl_ticketid']:0;
}
if(!$guestlist_event_id){
    $guestlist_event_id = !empty($_REQUEST['gl_eventid'])?(int)$_REQUEST['gl_eventid']:0;
}
?>
<div class="gl-login-form glform-wrapper">
    <form action="" method="POST">
        <input type="hidden" name="gl_ticketid" value="<?php echo $guestlist_ticket_id; ?>"/>
        <input type="hidden" name="gl_eventid" value="<?php echo $guestlist_event_id; ?>"/>
        <input type="hidden" name="glaction" value="signup"/>
        <?php wp_nonce_field('gl_sinup_'.$guestlist_ticket_id.$guestlist_event_id, '_glnonce'); ?>
        <label for="gl_firstname">Name<span>*</span></label>
        <p class="gl-field-first gl-half">
            <input type="text" id="gl_firstname" name="gl_firstname" value="<?php echo $gl_firstname; ?>"/>
            <span>First</span>
        </p>
        <p class="gl-field-last gl-half">
            <input type="text" id="gl_lastname" name="gl_lastname" value="<?php echo $gl_lastname; ?>"/>
            <span>Last</span>
        </p>
        <p class="gl-clear-before">
            <label for="gl_email">Email<span>*</span></label>
            <input type="email" id="gl_email" name="gl_email" value="<?php echo $gl_email; ?>"/>
        </p>
        <p><button class="button vamtam-button button-border accent1 hover-accent1" type="submit"><span class="btext">Become a Member</span></button></p>
        <p>After signing up you will be emailed a confirmation for the guest list.</p>
    </form>
</div>

