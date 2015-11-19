<?php
/**
 * SCREETS © 2014
 *
 * Shortcode functions
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */

/**
 * Open chat box
 *
 * @access public
 * @return string
 */
 
function cx_shortcode_open_chatbox( $atts = null, $content = '' ) {
	
	return '<a href="javascript:void(0);" class="cx-open-chatbox">' . $content . '</a>';
	
}