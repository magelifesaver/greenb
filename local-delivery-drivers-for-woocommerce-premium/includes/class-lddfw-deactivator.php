<?php
/**
 * Fired during plugin activation
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class LDDFW_Deactivator {


	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				// Find out when the last event was scheduled.
				$timestamp = wp_next_scheduled( 'lddfw_daily_event' );
				// Unschedule previous event if any.
				wp_unschedule_event( $timestamp, 'lddfw_daily_event' );
			}
		}
	}
}
