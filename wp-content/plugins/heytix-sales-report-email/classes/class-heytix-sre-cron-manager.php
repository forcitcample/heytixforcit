<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Heytix_SRE_Cron_Manager {

	const CRON_HOOK = 'heytix_sre_send';

	/**
	 * Remove the Cron
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function remove_cron() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Setup the Cron
	 * @access public
	 * @since  1.0.0
	 */
	public function setup_cron() {

		// Add the count words cronjob
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {

			// Create a Date Time object when the cron should run for the first time
			$first_cron = new DateTime( date( 'Y-m-d' ) . '03:00:00', new DateTimeZone( wc_timezone_string() ) );
			$first_cron->modify( '+1 day' );
			wp_schedule_event( $first_cron->format( 'U' ) + $first_cron->getOffset(), 'daily', self::CRON_HOOK );
		}

	}
}
