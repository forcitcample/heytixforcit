<?php

/*
 * Add custom fields to event edit page.
 */
if ( !defined('ABSPATH') ) { die('-1'); }

 add_filter('tribe_events_venue_meta_box_template', 'htmh_venue_metabox_template');
 function htmh_venue_metabox_template(){
     return get_stylesheet_directory() . '/inc/venue-meta-box.php';
 }

// Add artists info
add_action('tribe_events_eventform_top', 'aym_events_buttons_settings');

add_action( 'add_meta_boxes', 'htmh_event_mobile_featured_image' );
function htmh_event_mobile_featured_image(){
    add_meta_box('postmobimagediv', __('Mobile Featured Image'), 'htmh_event_mobile_thumbnail_meta_box', TribeEvents::POSTTYPE, 'side', 'low');
}


function aym_events_buttons_settings($postId){
    $mh_mobile_artist_name = get_post_meta($postId, 'htmh_event_mobile_artist_name', true);
    $mh_mobile_venue_name = get_post_meta($postId, 'htmh_event_mobile_venue_name', true);
    $mh_tickets_enabled = get_post_meta($postId, 'htmh_event_ticket_enabled', true);
    $mh_guest_list_enabled = get_post_meta($postId, 'htmh_event_guest_list_enabled', true);
    $mh_bottle_sevice_enabled = get_post_meta($postId, 'htmh_event_bottle_service_enabled', true);
    // $mh_all_access_enabled = get_post_meta($postId, 'htmh_event_all_access_enabled', true);
    $mh_ht_featured_event = get_post_meta($postId, '_ht_featured_event', true);
	$htmh_event_glrf_amount = get_post_meta($postId, 'htmh_event_glrf_amount', true);
    
    if(!$mh_mobile_venue_name){
        $mh_mobile_venue_name = get_post_meta(get_post_meta($postId,'_EventVenueID', true), '_VenueVenue', true);
    }
?> 
<table id="event_artist" class="eventtable">
    <tr>
            <td style="width:172px;"><label for="htmh_event_mobile_artist_name"><?php _e('Artist Name','tribe-events-calendar'); ?></label></td>
            <td><input type="text" class="widefat" id="htmh_event_mobile_artist_name" name="htmh_event_mobile_artist_name" value="<?php echo esc_attr($mh_mobile_artist_name); ?>" /></td>
    </tr>
    <tr>
            <td style="width:172px;"><label for="htmh_event_mobile_venue_name"><?php _e('Venue Name','tribe-events-calendar'); ?></label></td>
            <td><input type="text" class="widefat" id="htmh_event_mobile_venue_name" name="htmh_event_mobile_venue_name" value="<?php echo esc_attr($mh_mobile_venue_name); ?>" /></td>
    </tr>
    <tr>
            <td style="width:172px;"><label for="_ht_featured_event"><?php _e('Featured Event','tribe-events-calendar'); ?></label></td>
            <td><input type="checkbox" id="_ht_featured_event" name="_ht_featured_event" value="1"  <?php checked($mh_ht_featured_event, true, true); ?>/></td>
    </tr>
    <tr>
            <td colspan="2" class="tribe_sectionheader" ><h4><?php _e('Events Tabs', 'tribe-events-calendar'); ?></h4></td>
    </tr>
    <tr>
            <td style="width:172px;"><label for="htmh_event_ticket_enabled"><?php _e('Tickets','tribe-events-calendar'); ?></label></td>
            <td><input type="checkbox" id="htmh_event_ticket_enabled" name="htmh_event_ticket_enabled" value="1"  <?php checked($mh_tickets_enabled, true, true); ?>/></td>
    </tr>
    <tr>
            <td style="width:172px;"><label for="htmh_event_guest_list_enabled"><?php _e('Guest List ','tribe-events-calendar'); ?></label></td>
            <td><input type="checkbox" id="htmh_event_guest_list_enabled" name="htmh_event_guest_list_enabled" value="1"  <?php checked($mh_guest_list_enabled, true, true); ?>/></td>
    </tr>
    <tr>
            <td style="width:172px;"><label for="htmh_event_bottle_service_enabled"><?php _e('Bottle Service','tribe-events-calendar'); ?></label></td>
            <td><input type="checkbox" id="htmh_event_bottle_service_enabled" name="htmh_event_bottle_service_enabled" value="1"  <?php checked($mh_bottle_sevice_enabled, true, true); ?>/></td>
    </tr>
<!-- 
    <tr>
            <td style="width:172px;"><label for="htmh_event_bottle_service_enabled"><?php _e('All Access','tribe-events-calendar'); ?></label></td>
            <td><input type="checkbox" id="htmh_event_bottle_service_enabled" name="htmh_event_all_access_enabled" value="1"  <?php checked($mh_all_access_enabled, true, true); ?>/></td>
    </tr>
 -->
    <tr>
            <td style="width:172px;"><label for="htmh_event_glrf_amount"><?php _e('Guest List Referral Amount','tribe-events-calendar'); ?></label></td>
			<td><input type="text" size="6" id="htmh_event_glrf_amount" name="htmh_event_glrf_amount" value="<?php echo esc_attr($htmh_event_glrf_amount); ?>" /></td>
    </tr>
</table>
<?php
}

function htmh_event_mobile_thumbnail_meta_box($post){
    global $content_width, $_wp_additional_image_sizes;
    $thumbnail_id = get_post_meta( $post->ID, '_mob_thumbnail_id', true );

    $post = get_post( $post );
    $ajax_nonce = wp_create_nonce( 'set_post_mob_thumbnail-' . $post->ID );
    $set_thumbnail_link = '<p class="hide-if-no-js"><a title="' . esc_attr__( 'Set mobile featured image' ) . '" href="#" id="set-post-mob-thumbnail">%s</a></p>';
    $content = sprintf( $set_thumbnail_link, esc_html__( 'Set mobile featured image' ) );

    if ( $thumbnail_id && get_post( $thumbnail_id ) ) {
        $old_content_width = $content_width;
        $content_width = 266;
        if ( !isset( $_wp_additional_image_sizes['post-thumbnail'] ) )
                $thumbnail_html = wp_get_attachment_image( $thumbnail_id, array( $content_width, $content_width ) );
        else
                $thumbnail_html = wp_get_attachment_image( $thumbnail_id, 'post-thumbnail' );
        if ( !empty( $thumbnail_html ) ) {
                $content = sprintf( $set_thumbnail_link, $thumbnail_html );
                $content .= '<p class="hide-if-no-js"><a href="#" id="remove-post-mob-thumbnail">' . esc_html__( 'Remove featured image' ) . '</a></p>';
        }
        $content_width = $old_content_width;
    }else{
        $content .= '<p class="hide-if-no-js"><a href="#" style="display:none;" id="remove-post-mob-thumbnail">' . esc_html__( 'Remove featured image' ) . '</a></p>';
    }
    
    echo $content;
?>
<style type="text/css">
#postmobimagediv .inside img {
    height: auto;
    max-width: 100%;
}</style>
<script type="text/javascript">
    (function($){
        $(document).ready(function(){
            $('body').on('click', '#set-post-mob-thumbnail', function(e){
                e.preventDefault();
                if ( typeof file_frame !="undefined" ) {
                    file_frame.open();
                    return;
                }
                file_frame = wp.media.frames.downloadable_file = wp.media({
                    title: '<?php _e( 'Choose an image' ); ?>',
                    button: {
                        text: '<?php _e( 'Use image' ); ?>',
                    },
                    multiple: false
                });
                file_frame.on( 'select', function() {
                    attachment = file_frame.state().get('selection').first().toJSON();
                    
                    $.post(
                        '<?php echo admin_url('admin-ajax.php'); ?>', 
                        {action: 'htmh_set_mobile_image', subaction:'update', _htnonce: '<?php echo $ajax_nonce; ?>', eID: <?php echo $post->ID;?>, aID: attachment.id },
                        function(data){
                            if(data != "0"){
                                $('#set-post-mob-thumbnail').html(data);
                                $('#remove-post-mob-thumbnail').show();
                            }
                        }
                    );
                });
            });
            $('body').on('click', '#remove-post-mob-thumbnail', function(e){
                e.preventDefault();
                $.post(
                    '<?php echo admin_url('admin-ajax.php'); ?>', 
                    {action: 'htmh_set_mobile_image', subaction:'remove', _htnonce: '<?php echo $ajax_nonce; ?>', eID: <?php echo $post->ID;?> },
                    function(data){
                        if(data=='OK'){
                            $('#set-post-mob-thumbnail').html('<?php echo esc_html__( 'Set mobile featured image' ); ?>');
                            $('#remove-post-mob-thumbnail').hide();
                        }
                    }
                );
            });
        });
    })(jQuery);
</script>
<?php
}

add_action('wp_ajax_htmh_set_mobile_image', 'htmh_set_mobile_image');

function htmh_set_mobile_image(){
    if(empty($_POST['_htnonce']) || empty($_POST['eID']) || !wp_verify_nonce($_POST['_htnonce'],'set_post_mob_thumbnail-' . $_POST['eID'])){
        die( '0' );
        return;
    }
    if (!get_post( $_POST['eID'] ) ) {
        die( '0' );
        return;
    }
    
    $action = isset($_POST['subaction'])?$_POST['subaction']:'update';
    if('update' == $action){
        if(empty($_POST['aID']) || !get_post($_POST['aID'])){
            die( '0' );
            return;
        }
        global $content_width, $_wp_additional_image_sizes;
        $old_content_width = $content_width;
        $content_width = 266;
        if ( !isset( $_wp_additional_image_sizes['post-thumbnail'] ) )
                $thumbnail_html = wp_get_attachment_image( $_POST['aID'], array( $content_width, $content_width ) );
        else
                $thumbnail_html = wp_get_attachment_image( $_POST['aID'], 'post-thumbnail' );
        if ( !empty( $thumbnail_html ) ) {
            update_post_meta($_POST['eID'], '_mob_thumbnail_id', (int)$_POST['aID']);
            echo $thumbnail_html;
            die();
            return;
        }
        $content_width = $old_content_width;
    }elseif('remove' == $action){
        delete_post_meta($_POST['eID'], '_mob_thumbnail_id');
        echo 'OK';
        die();
    }
    die( '0' );
}


//Save Extended Metabox
add_action('tribe_events_update_meta', 'htmh_save_meta_extended', 10, 2);
function htmh_save_meta_extended($event_id, $data){
    // All data are processed by the plugin. we don't need to worry about it.
    // Just save it.
    // save Mobile Title
    if(!empty($data['htmh_event_mobile_artist_name'])){
        update_post_meta( $event_id, 'htmh_event_mobile_artist_name', $data['htmh_event_mobile_artist_name'] );
    }else{
        delete_post_meta($event_id, 'htmh_event_mobile_artist_name');
    }
    // save Mobile Title
    if(!empty($data['htmh_event_mobile_venue_name'])){
        update_post_meta( $event_id, 'htmh_event_mobile_venue_name', $data['htmh_event_mobile_venue_name'] );
    }else{
        delete_post_meta($event_id, 'htmh_event_mobile_venue_name');
    }
    // save Tickets Enabled
    if(!empty($data['htmh_event_ticket_enabled'])){
        update_post_meta( $event_id, 'htmh_event_ticket_enabled', 1 );
    }else{
        delete_post_meta($event_id, 'htmh_event_ticket_enabled');
    }
    // save Guest List Enabled
    if(!empty($data['htmh_event_guest_list_enabled'])){
        update_post_meta( $event_id, 'htmh_event_guest_list_enabled', 1 );
    }else{
        delete_post_meta($event_id, 'htmh_event_guest_list_enabled');
    }
    // save Bottle Service Enabled
    if(!empty($data['htmh_event_bottle_service_enabled'])){
        update_post_meta( $event_id, 'htmh_event_bottle_service_enabled', 1 );
    }else{
        delete_post_meta($event_id, 'htmh_event_bottle_service_enabled');
    }
    // save Bottle Featured Enabled htmh_event_glrf_amount
    if(!empty($data['_ht_featured_event'])){
        update_post_meta( $event_id, '_ht_featured_event', 1 );
    }else{
        delete_post_meta($event_id, '_ht_featured_event');
    }
    // save Referral Amount
    if(!empty($data['htmh_event_glrf_amount'])){
        update_post_meta( $event_id, 'htmh_event_glrf_amount', (float)$data['htmh_event_glrf_amount'] );
    }else{
        delete_post_meta($event_id, 'htmh_event_glrf_amount');
    }
}
//Save Emails for Venues
add_action('tribe_events_venue_updated', 'htmh_save_venue_emails', 10, 2);
function htmh_save_venue_emails($venue_id, $data){
    // All data are processed by the plugin. we don't need to worry about it.
    // Just save it.
    // save Mobile Title
    if(isset($data['VenueEmailAddresses'])){
        delete_post_meta($venue_id, '_VenueEmailAddresses');
    }
    if(!is_array($data['VenueEmailAddresses'])){
        return false;
    }
    $emils = array_values($data['VenueEmailAddresses']);
    $emils = array_unique($emils);
    foreach( $emils as $email){
        if(!$email) continue;
        add_post_meta($venue_id, '_VenueEmailAddresses', $email);
    }
}