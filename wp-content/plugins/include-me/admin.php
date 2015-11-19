<?php
function includeme_admin_head() {
    if (!isset($_GET['page']))
        return;
    if (strpos($_GET['page'], 'include-me/') === 0) {
        echo '<link type="text/css" rel="stylesheet" href="' . plugins_url('admin.css', __FILE__) . '">';
    }
}
add_action('admin_head', 'includeme_admin_head');

function includeme_admin_menu() {
    add_options_page('Include Me', 'Include Me', 'manage_options', 'include-me/options.php');
}
add_action('admin_menu', 'includeme_admin_menu');