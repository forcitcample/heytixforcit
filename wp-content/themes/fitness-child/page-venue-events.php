<?php
/**
 * Single page template
 *
 * @package wpv
 * @subpackage fitness-wellness
 */

get_header('venue-events');

$venue_id = (array_key_exists('venue_id', $_GET) && !empty($_GET['venue_id'])) ? $_GET['venue_id'] : null;

$args=array(
    'meta_key' => '_EventStartDate',
    'meta_query' => array(
        array('key' => '_EventStartDate'),
        array(
            'key' => '_EventVenueID',
            'value' => $venue_id,
        ),
    ),
    'post_type' => 'tribe_events',
    'orderby' => 'meta_value',
    'order' => 'ASC',
);
$my_query = new WP_Query($args);
?>
<script type="text/javascript" src="<?php echo get_stylesheet_directory_uri(); ?>/js/iframeResizer.contentWindow.min.js"></script>
<div class="row page-wrapper">
    <?php WpvTemplates::left_sidebar() ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class(WpvTemplates::get_layout()); ?>>
        <?php
        global $wpv_has_header_sidebars;
        if( $wpv_has_header_sidebars) {
            WpvTemplates::header_sidebars();
        }
        ?>
        <section class="wpv-tribe-vertical-events">
            <h2 class="text-divider-double"><?php echo get_the_title(); ?></h2>
<?php

if( $my_query->have_posts() ) {
    while ($my_query->have_posts()) : $my_query->the_post();
        $venue_image_id = get_post_meta($venue_id, 'tribe_venue_feature-image-venue_thumbnail_id', true);
        $upload_dir_data = wp_upload_dir();
        $venue_image_meta = get_post_meta(
            $venue_image_id,
            '_wp_attached_file',
            true
        );

        $date = explode(' ', date('D d M', strtotime(get_post_meta(get_the_ID(), '_EventStartDate', true))));
        $artist_image = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'single-post-thumbnail' );
        $venue_image = $upload_dir_data['baseurl'] . '/' . $venue_image_meta;

        ?>
        <div class="wpv-event-row style-dark layout-vertical ">
            <div class="mh-event-row-inner">
                <div class="mh-evnet-inner-trow">
                    <div class="date cell">
                        <div class="day_abb"><?php echo $date[0];?></div>
                        <div class="day"><?php echo $date[1];?></div>
                        <div class="month"><?php echo $date[2];?></div>
                    </div>
                    <div class="feat-image cell">
                        <div class="tribe-events-event-image"><img width="300" height="300" src="<?php echo $artist_image[0]; ?>" class="attachment-full wp-post-image" alt="Tiesto"></div>                    </div>
                    <h5 class="title cell">
                        <a title="Tickets" href="<?php the_permalink() ?>">
                            <span class="htmh-artist-name"><?php echo substr(get_the_title(), 0, strpos(get_the_title(), ' at ')+3);?></span><br><img src="<?php echo $venue_image; ?>">                        </a>
                    </h5>

                    <div class="cell mh-events-tab-buttons clearfix">

                        <div class="mh-event-tab-button">
                            <a title="" href="<?php the_permalink() ?>#tab-1-0-get-tickets">                                <div class="mhhtd-desktop">
                                    <i class="fa fa-ticket fa-2x"></i>
                                    <span>Get Tickets</span>
                                </div>
                                <div class="mhhtd-mobile">
                                    <i class="fa fa-ticket fa-2x"></i>
                                    <span>Tickets</span>
                                </div>

                            </a>                        </div>
                        <div class="mh-event-tab-button">
                            <a title="" href="<?php the_permalink() ?>#tab-1-1-guest-list">                                <div class="mhhtd-desktop">
                                    <i class="fa fa-list-ul fa-2x"></i>
                                    <span>Guest List</span>
                                </div>
                                <div class="mhhtd-mobile">
                                    <i class="fa fa-list-alt fa-2x"></i>
                                    <span>Guest List</span>
                                </div>
                            </a>                        </div>
                        <div class="mh-event-tab-button">
                            <a title="" href="<?php the_permalink() ?>#tab-1-2-bottle-service">                                <div class="mhhtd-desktop">
                                    <i class="fa fa-star fa-2x"></i>
                                    <span>Get a Table</span>
                                </div>
                                <div class="mhhtd-mobile">
                                    <i class="fa fa-star fa-2x"></i>
                                    <span>Table</span>
                                </div>
                            </a>                        </div>

                    </div>
                </div>
            </div>
        </div>
        <?php
    endwhile;
}
wp_reset_query();
?>
			</section>