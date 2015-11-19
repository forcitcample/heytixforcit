<?php
function guestlist_signup_shortcode($args) {
    if(!is_array($args) || (!array_key_exists('event_id', $args) || empty($args['event_id']))) return 'Error: You must provide [guestlist_signup] with an event_id argument.';
    $template = (array_key_exists('template', $args) && !empty($args['template'])) ? 'templates/shortcodes/guestlist_signup/templates/'.$args['template'].'.php' : 'templates/shortcodes/guestlist_signup/templates/default.php';

    $body = HT_Guest_List::get_instance()->ticket($args['event_id'], $template);

    return $body;

}
