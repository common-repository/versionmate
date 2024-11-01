<?php

class Versionmate_Scheduler {

	const PERIOD = 150;
	const PERIOD_NAME = 'threeminutes';
	const PERIOD_DESCRIPTION = 'Every three minutes';

	const CRON_ONE_TIME = 'versionmate_single_cron';
	const CRON = 'versionmate_scheduler';

	/**
	 * Hook runs when plugin is activated, adds cron scheduler
	 */
	public static function run_on_activate() {
		self::schedule_cron();
	}

	/**
	 * Schedule the wp-cron to ping Versionmate
	 */
	public static function schedule_cron() {
		if ( ! wp_next_scheduled( self::CRON ) ) {
			wp_schedule_event( time(), self::PERIOD_NAME, self::CRON );
		}
	}

	/**
	 * Hook runs when plugin is de-activated, removes cron scheduler
	 */
	public static function run_on_deactivate() {
		wp_clear_scheduled_hook( self::CRON );

		$next = wp_next_scheduled( self::CRON_ONE_TIME );
		if ( $next ) {
			wp_unschedule_event( $next, self::CRON_ONE_TIME );
		}
	}

	/**
	 * Schedule one time trigger
	 */
	public static function schedule_trigger() {
		wp_schedule_single_event( time(), self::CRON_ONE_TIME );
	}

	/**
	 * Add our wp-cron interval to the intervals
	 *
	 * @param $array
	 *
	 * @return mixed
	 */
	public static function add_cron_interval( $array ) {
		$array[ self::PERIOD_NAME ] = array(
			'interval' => self::PERIOD,
			'display'  => self::PERIOD_DESCRIPTION
		);

		return $array;
	}


}