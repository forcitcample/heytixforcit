<?php

/**
 * Product page - variable pricing table sceleton
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

?>

<div id="rp_wcdpd_pricing_table_variation_container" class="rp_wcdpd_pricing_table_variation_container">
    <?php foreach ($variation_table_data as $current_id => $table_data): ?>
        <div id="rp_wcdpd_pricing_table_variation_<?php echo $current_id; ?>" class="rp_wcdpd_pricing_table_variation">
            <?php require RP_WCDPD_PLUGIN_PATH . 'includes/views/frontend/table-' . $this->opt['settings']['display_table'] . '-' . $this->opt['settings']['pricing_table_style'] . '.php'; ?>
        </div>
    <?php endforeach; ?>
</div>