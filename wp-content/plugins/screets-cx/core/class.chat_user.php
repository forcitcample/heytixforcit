<?php
/**
 * SCREETS © 2014
 *
 * User class
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */

class CX_user extends CX_base {
	
	protected $user_id;

	/**
	 * Update current user data
	 *
	 * @access public
	 * @return void
	 */
	public function update() {
		global $wpdb;

		// Prepare data
		$data = array( 'last_activity' => CX_NOW );
		
		$wpdb->update( 
			CX_PX . 'online', 
			$data,
			array( 'user_id' => $this->user_id ),
			array( '%d' )
		);

	}
	
}