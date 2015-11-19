<?php
/**
 * SCREETS © 2014
 *
 * Base chat class
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */

class CX_base {
	
	/**
	 * Contructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct( array $opts ) {
		
		// Declare options as parameter
		foreach( $opts as $k => $v ){
			
			if( isset( $this->$k ) )
				$this->$k = $v;
				
		}
	}
	
}