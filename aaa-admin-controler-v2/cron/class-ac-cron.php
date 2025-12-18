<?php
/**
 * File: /wp-content/plugins/aaa-admin-controler-v2/cron/class-ac-cron.php
 * Purpose: Scheduled forced session endings based on per-user "ac_force_times".
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AC_Cron {

	public static function init(){
		add_filter('cron_schedules', [__CLASS__,'schedules']);
		add_action('init', [__CLASS__,'ensure_schedule']); // keep schedule aligned to setting
		add_action('aaa_ac_check_force_times', [__CLASS__,'check_force_times']);
	}

	/** Add "minutely" and "quarterhourly" schedules */
	public static function schedules( $schedules ){
		$schedules['minutely'] = [
			'interval' => 60,
			'display'  => __('Every Minute','aaa-ac'),
		];
		$schedules['quarterhourly'] = [
			'interval' => 15 * 60,
			'display'  => __('Every 15 Minutes','aaa-ac'),
		];
		return $schedules;
	}

	/** Current mode: 'dev' (minutely) or 'live' (quarterhourly) */
	protected static function mode(){
		$mode = get_site_option( 'aaa_ac_cron_mode', 'dev' );
		return in_array($mode, ['dev','live'], true) ? $mode : 'dev';
	}

	/** Interval key for wp-cron based on mode */
	protected static function interval_key(){
		return self::mode()==='live' ? 'quarterhourly' : 'minutely';
	}

	/** Make sure only the main site schedules the event, and with the chosen interval */
	public static function ensure_schedule(){
		if ( ! is_main_site() ) return;

		$event = 'aaa_ac_check_force_times';
		$want  = self::interval_key();
		$have  = get_site_option('aaa_ac_cron_current', '');

		// If not scheduled, schedule it.
		if ( ! wp_next_scheduled( $event ) ){
			wp_schedule_event( time(), $want, $event );
			update_site_option('aaa_ac_cron_current', $want );
			return;
		}

		// If scheduled with a different interval, reschedule
		if ( $have !== $want ){
			self::unschedule_all( $event );
			wp_schedule_event( time(), $want, $event );
			update_site_option('aaa_ac_cron_current', $want );
		}
	}

	/** Public entry point the settings page calls after saving mode */
	public static function reschedule(){
		if ( ! is_main_site() ) return;
		$event = 'aaa_ac_check_force_times';
		self::unschedule_all( $event );
		$want = self::interval_key();
		wp_schedule_event( time(), $want, $event );
		update_site_option('aaa_ac_cron_current', $want );
	}

	/** Remove all scheduled instances of an event */
	protected static function unschedule_all( $hook ){
		while ( $ts = wp_next_scheduled( $hook ) ){
			wp_unschedule_event( $ts, $hook );
		}
	}

	/** Core: end sessions when now (HH:MM, site tz) matches any user's ac_force_times */
	public static function check_force_times(){
		$now = wp_date('H:i'); // site timezone (main site), DST-safe
		if ( function_exists('aaa_ac_log') ) aaa_ac_log('CRON tick', ['now'=>$now, 'mode'=>self::mode()]);

		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT user_id, meta_value FROM {$wpdb->usermeta}
			 WHERE meta_key = 'ac_force_times' AND meta_value <> ''"
		);

		if ( empty($rows) ) return;

		foreach( $rows as $row ){
			$times = array_map('trim', explode(',', (string)$row->meta_value));
			if ( in_array( $now, $times, true ) ){
				WP_Session_Tokens::get_instance( (int)$row->user_id )->destroy_all();
				if ( class_exists('AC_Logger') ) { AC_Logger::mark_ended_all( (int)$row->user_id, 'scheduled', 1, 'none' ); }
				if ( function_exists('aaa_ac_log') ){
					aaa_ac_log('FORCED logout (scheduled)', ['user_id'=>(int)$row->user_id,'time'=>$now]);
				}
			}
		}
	}
}
