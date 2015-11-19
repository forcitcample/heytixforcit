<?php
/**
 * Header search button
 *
 * @package wpv
 * @subpackage fitness-wellness
 */


if(!wpv_get_option('enable-header-search')) return;

?>

<button class="header-search icon wpv-overlay-search-trigger"><?php wpv_icon('search1') ?></button>