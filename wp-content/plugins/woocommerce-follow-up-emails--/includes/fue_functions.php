<?php

function fue_format_send_datetime( $email ) {
    $meta = maybe_unserialize($email->meta);

    return (!empty($email->send_date_hour)) ? $email->send_date .' '. fue_zero_pad_time( $email->send_date_hour ) .':'. fue_zero_pad_time( $email->send_date_minute ) .' '. $meta['send_date_ampm'] : $email->send_date;
}

function fue_zero_pad_time( $time ) {
    if ( intval( $time ) < 10 )
        $time = '0' . $time;

    return $time;
}

function fue_add_custom_fields( $matches ) {
    if ( empty($matches) ) return '';

    $id = $matches[1];
    $cf = $matches[2];

    $meta = get_post_meta( $id, $cf, true );

    if ($meta) {

        if ( $cf == '_downloadable_files' ) {
            if ( count($meta) == 1 ) {
                $file = array_pop($meta);
                $meta = '<a href="'. $file['file'] .'">'. $file['name'] .'</a>';
            } else {

                $list = '<ul>';
                foreach ( $meta as $file ) {
                    $list .= '<li><a href="'. $file['file'] .'">'. $file['name'] .'</a></li>';
                }
                $list .= '</ul>';

                $meta = $list;
            }
        }

        return $meta;
    }
    return '';
}

function fue_add_post( $matches ) {
    if ( empty($matches) ) return '';
    if (! isset($matches[1]) || empty($matches[1]) ) return '';

    $id = $matches[1];

    $post = get_post( $id );

    if ( isset($post->post_excerpt) )
        return $post->post_excerpt;
    else
        return '';
}

function fue_get_page_id( $page ) {
    $page = get_option('fue_' . $page . '_page_id');
    return ( $page ) ? $page : -1;
}

if (! function_exists('sfn_get_product') ) {
    function sfn_get_product( $product_id ) {
        return WC_FUE_Compatibility::wc_get_product( $product_id );
    }
}

function fue_add_logger_class() {
    return "FUE_ActionScheduler_Logger";
    //return "ActionScheduler_wpCommentLogger";
}