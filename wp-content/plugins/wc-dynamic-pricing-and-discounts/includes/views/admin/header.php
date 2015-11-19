<?php

/**
 * Admin settings page header view
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="rp_wcdpd_tabs_container">
    <h2 class="nav-tab-wrapper">
        <?php foreach($this->settings_page_tabs as $tab_key => $tab): ?>
            <a class="nav-tab <?php echo ($tab_key == $current_tab ? ' nav-tab-active' : ''); ?>" href="?page=wc_pricing_and_discounts&tab=<?php echo $tab_key; ?>">
                <i class="fa fa-<?php echo $tab['icon']; ?>" style="font-size: 0.8em;"></i> &nbsp;<?php echo $tab['title']; ?>
            </a>
        <?php endforeach; ?>

        <!--<a class="nav-tab <?php echo ('help' == $current_tab ? ' nav-tab-active' : ''); ?>" href="?page=wc_pricing_and_discounts&tab=help">
            <i class="fa fa-question" style="font-size: 1em;"></i>
        </a>-->
    </h2>
</div>