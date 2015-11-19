<?php

/**
 * View for passing variables to javascript
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

?>

<script type="text/javascript">var rp_wcdpd_vars = <?php echo json_encode($this->to_javascript); ?></script>