<?php
require_once get_stylesheet_directory() . '/inc/Mobile_Detect.php';
$ht_ua_detect = new Mobile_Detect;
?>
<section class="wpv-tribe-vertical-events">
    <h2 class="text-divider-double"><?php _e( 'Upcoming Events', 'fitness-wellness' ) ?></h2>
    <div class="sep"></div>
    <?php 
        global $wpdb;

        $user_ID = get_current_user_id();
       
        $data = $wpdb->get_row('select * from '.$wpdb->usermeta. ' where meta_key = "wp_capabilities" and user_id = '.$user_ID);
        $role = unserialize($data->meta_value);
      //  echo "<pre>"; print_r($role); echo "</pre>";
       
    ?>
    <?php foreach($events as $event):
        $mh_mobile_artist_name = get_post_meta($event->ID, 'htmh_event_mobile_artist_name', true);
        $mh_mobile_venue_name = get_post_meta($event->ID, 'htmh_event_mobile_venue_name', true);
        $mob_thumbnail_id = get_post_meta( $event->ID, '_mob_thumbnail_id', true );
        $mh_tickets_enabled = get_post_meta($event->ID, 'htmh_event_ticket_enabled', true);
        $mh_guest_list_enabled = get_post_meta($event->ID, 'htmh_event_guest_list_enabled', true);
        $mh_get_tables_enabled = get_post_meta($event->ID, 'htmh_event_bottle_service_enabled', true);
        $mh_all_access_enabled = get_post_meta($event->ID, 'htmh_event_all_access_enabled', true);
        $mh_ht_featured_event = get_post_meta($event->ID, '_ht_featured_event', true);

        if(class_exists('TribeWooTickets')){
            $wootickets = TribeWooTickets::get_instance();
            $ticket_ids = $wootickets->get_Tickets_ids( $event->ID );
            if ( empty($ticket_ids) ) {
                $mh_tickets_enabled = false;
            }
        }else{
            $mh_tickets_enabled = false;
        }
        $tab_number = 0;
        ?>
        <div class="wpv-event-row style-<?php echo esc_attr( $style ) ?> layout-<?php echo esc_attr( $layout ) ?> <?php if($mh_ht_featured_event){echo 'ht-featured-event';} ?>">
            <?php
            $start = strtotime($event->EventStartDate);
            $day_abbreb = date('D', $start);
            $day = date('d', $start);
            $month = date_i18n('M', $start);
            ?>
            <div class="mh-event-row-inner" >
                <div class="mh-evnet-inner-trow">
                    <div class="date cell">
                        <div class="day_abb"><?php echo $day_abbreb ?></div>
                        <div class="day"><?php echo $day ?></div>
                        <div class="month"><?php echo $month ?></div>
                    </div>

                    <div class="feat-image cell">
                        <?php

                        if($mob_thumbnail_id && $ht_ua_detect->isMobile()){
                            $image_src      = wp_get_attachment_image_src( $mob_thumbnail_id, 'full' );
                            echo '<div class="tribe-events-event-image"><img src="' . $image_src[0] . '" title="' . get_the_title($event->ID) . '" /></div>';
                        }else{
                            echo tribe_event_featured_image($event->ID, 'full', false);
                        }
                        ?>
                    </div>


                    <h5 class="title cell">
                        <a title="<?php esc_attr_e('Tickets', 'fitness-wellness') ?>" href="<?php tribe_event_link($event) ?>">
                            <?php
                            /* From Here */
                            $venue_id = get_post_meta($event->ID, '_EventVenueID', true);
                            $venue_image_id = get_post_meta($venue_id, 'tribe_venue_feature-image-venue_thumbnail_id', true);
                            $upload_dir_data = wp_upload_dir();
                            $venue_image_meta = get_post_meta(
                                $venue_image_id,
                                '_wp_attached_file',
                                true
                            );

                            if((isset($mh_mobile_artist_name) && !empty($mh_mobile_artist_name)) && (isset($venue_image_meta) && !empty($venue_image_meta))){
                                echo '<span class="htmh-artist-name">'. $mh_mobile_artist_name . ' at</span>';
                                if(isset($mh_mobile_venue_name) && !empty($mh_mobile_venue_name)){

                                    if(isset($venue_image_meta) && !empty($venue_image_meta)) {
                                        echo '<br/><img src="' . $upload_dir_data['baseurl'] . '/' . $venue_image_meta . '" />';
                                    }
                                }

                                /*to here*/
                            }else {
                                if($mh_mobile_artist_name && $ht_ua_detect->isMobile()){
                                    echo '<span class="htmh-artist-name">' . $mh_mobile_artist_name . '</span>';
                                    if ($mh_mobile_venue_name) {
                                        echo '<br/><span class="htmh-venue-name">at ' . $mh_mobile_venue_name . '</span>';
                                    }

                                } else {
                                    echo get_the_title($event->ID);
                                }
                            }
                            ?>
                        </a>
                    </h5>

                    <div class="cell mh-events-tab-buttons clearfix">
                    <?php 
                    if(isset($role['venue_manager']) && $role['venue_manager'] == '1'){ ?>
                    <div class="mh-event-tab-button">
                   <?php // $ticket_ids = $wootickets->get_Tickets_ids( $event->ID ); 
                  // echo '<pre>'; print_r($ticket_ids); echo '</pre>';

                  ?>
                        <div >
                            <i class="fa fa-ticket fa-2x"></i>
                            <span><a href="<?php echo site_url(); ?>/sendticket?ename=<?php echo $mh_mobile_artist_name.' at '. $mh_mobile_venue_name; ?>&event_id=<?php echo $event->ID; ?>&user_id=<?php echo get_current_user_id(); ?>">Send Ticket</a></span>
                        </div>

                    </div>
                    <?php } ?>
                        <div class="mh-event-tab-button">
                            <?php if($mh_tickets_enabled){ ?><a title="" href="<?php tribe_event_link($event);?>#tab-1-<?php echo $tab_number; ?>-get-tickets"><?php $tab_number++; } ?>
                                <div class="mhhtd-desktop">
                                    <i class="fa fa-ticket fa-2x"></i>
                                    <span>Get Tickets</span>
                                </div>
                                <div class="mhhtd-mobile">
                                    <i class="fa fa-ticket fa-2x"></i>
                                    <span>Tickets</span>
                                </div>

                                <?php if($mh_tickets_enabled){ ?></a><?php } ?>
                        </div>
                        <div class="mh-event-tab-button">
                            <?php if($mh_guest_list_enabled){ ?><a title="" href="<?php tribe_event_link($event);?>#tab-1-<?php echo $tab_number; ?>-guest-list"><?php $tab_number++; } ?>
                                <div class="mhhtd-desktop">
                                    <i class="fa fa-list-ul fa-2x"></i>
                                    <span>Guest List</span>
                                </div>
                                <div class="mhhtd-mobile">
                                    <i class="fa fa-list-alt fa-2x"></i>
                                    <span>Guest List</span>
                                </div>
                                <?php if($mh_guest_list_enabled){ ?></a><?php } ?>
                        </div>
                        <div class="mh-event-tab-button">
                            <?php if($mh_get_tables_enabled){ ?><a title="" href="<?php tribe_event_link($event);?>#tab-1-<?php echo $tab_number; ?>-bottle-service"><?php $tab_number++; } ?>
                                <div class="mhhtd-desktop">
                                    <i class="fa fa-star fa-2x"></i>
                                    <span>Get a Table</span>
                                </div>
                                <div class="mhhtd-mobile">
                                    <i class="fa fa-star fa-2x"></i>
                                    <span>Table</span>
                                </div>
                                <?php if($mh_get_tables_enabled){ ?></a><?php } ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <header class="view-all">
        <a href="<?php echo tribe_get_events_link() ?>" title="<?php esc_attr_e( 'Upcoming Events', 'fitness-wellness' ); ?>"><?php _e( 'View Full Calendar &rarr;', 'fitness-wellness' ); ?></a>
    </header>
</section>

