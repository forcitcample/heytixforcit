<?php
if(htgl_show_siggnup_links($post_id)){
    //Only logged in user can get ticket
    //Let the visitor to choose the login
    if(is_user_logged_in()){
        $account_redirect = add_query_arg(array('glevent_id' => $post_id, 'glticket_id'=>$guestlist_ticket_id, 'glaction'=>'add'), $guest_list_page_url );
    }else{
        $account_redirect   = add_query_arg(array('glevent_id' => $post_id, 'glticket_id'=>$guestlist_ticket_id, 'glaction'=>'login_form'), $guest_list_page_url );
    }
    ?>
    <div class="htgl-signup-box">
        <p style="text-align: center;">DEFAULT Sign up for the Guest List</p>
        <?php echo do_shortcode('[userpro_social_connect2 event_id="' . $post_id . '" ticket_id="' . $guestlist_ticket_id . '" facebook_redirect="' . esc_url($guest_list_page_url) . '" width="250px"]'); ?>
        <p style="text-align: center;">Don't have a Facebook account. Sign up with your <a href="<?php echo esc_url($account_redirect); ?>" title="">HeyTix account</a></p>
        <?php if(!is_user_logged_in()){ ?>
            <p style="text-align: center;">Need a HeyTix account? <a href="<?php echo esc_url( add_query_arg(array('glevent_id' => $post_id, 'glticket_id'=>$guestlist_ticket_id, 'glaction'=>'signup_form'), $guest_list_page_url ) ); ?>" title="">Sign-up here</a></p>
        <?php } ?>
    </div>
    <?php
}else{
    //User is already signed up for the event guest list.
    ?>
    <p style="text-align: center;">You are already signed up for this guest list. Enjoy the event.</p>
    <?php
}
