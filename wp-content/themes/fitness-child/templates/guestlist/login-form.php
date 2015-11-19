<?php
$username = !empty($_REQUEST['gl_username'])?$_REQUEST['gl_username']:'';
if(!$guestlist_ticket_id){
    $guestlist_ticket_id = !empty($_REQUEST['gl_ticketid'])?(int)$_REQUEST['gl_ticketid']:0;
}
if(!$guestlist_event_id){
    $guestlist_event_id = !empty($_REQUEST['gl_eventid'])?(int)$_REQUEST['gl_eventid']:0;
}
$lost_password_redirect = add_query_arg(array('glevent_id' => $guestlist_event_id, 'glticket_id'=>$guestlist_ticket_id, 'glaction'=>'login_form', 'gl_username' => $username), $this->page_url() );
?>
<div class="gl-login-form glform-wrapper">
    <form action="" method="POST">
        <input type="hidden" name="gl_ticketid" value="<?php echo $guestlist_ticket_id; ?>"/>
        <input type="hidden" name="gl_eventid" value="<?php echo $guestlist_event_id; ?>"/>
        <input type="hidden" name="glaction" value="login"/>
        <?php wp_nonce_field('gl_login_'.$guestlist_ticket_id.$guestlist_event_id, '_glnonce'); ?>
        <p>
            <label for="gl_username">Username<span>*</span></label>
            <input type="text" id="gl_username" name="gl_username" value="<?php echo $username; ?>"/>
        </p>
        <p>
            <label for="gl_password">Password<span>*</span></label>
            <input type="password" id="gl_password" name="gl_password" value=""/>
        </p>
        <p><button class="button vamtam-button button-border accent1 hover-accent1" type="submit"><span class="btext">Join the Guest List</span></button></p>
        <p><a href="<?php echo esc_url( wp_lostpassword_url($lost_password_redirect) ); ?>" title="<?php esc_attr_e( 'Password Lost and Found' ) ?>"><?php _e( 'Lost your password?' ); ?></a></p>
    </form>
</div>

