<?php
/**
 * File: /index/class-aaa-afci-table-manager.php
 * Purpose: DB helpers for sessions/events.
 * Version: 1.3.2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_AFCI_Table_Manager {

	/**
	 * Basic insert (no de-dup).
	 */
	public static function insert_event( $session_key, $type, $payload = [], $user_id = 0 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aaa_checkout_sessions';

		$row = [
			'session_key'   => sanitize_text_field( $session_key ),
			'user_id'       => absint( $user_id ),
			'event_type'    => sanitize_text_field( $type ),
			'event_key'     => null,
			'event_payload' => wp_json_encode( $payload ),
			'ip_address'    => $_SERVER['REMOTE_ADDR']     ?? '',
			'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? '',
			'created_at'    => current_time( 'mysql', true ),
			'updated_at'    => current_time( 'mysql', true ),
		];

		$wpdb->insert( $table, $row );
		return $wpdb->insert_id;
	}

	/**
	 * Insert or bump repeat_count for noisy events (fetch/wc_fetch) using a stable event_key.
	 * Dedup window: ~60 seconds (avoids table explosion on checkout idle).
	 */
	public static function insert_or_bump_event( $session_key, $type, $payload = [], $user_id = 0 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aaa_checkout_sessions';

		$is_noisy = in_array( $type, [ 'fetch', 'wc_fetch' ], true );
		$url      = is_array($payload) ? ( $payload['url'] ?? '' ) : '';
		$ekey     = $is_noisy ? md5( $session_key . '|' . $type . '|' . substr( (string) $url, 0, 180 ) ) : null;

		if ( $is_noisy && $ekey ) {
			// Find the latest row with same event_key for this session
			$existing = $wpdb->get_row( $wpdb->prepare(
				"SELECT id, repeat_count, created_at FROM {$table}
				 WHERE session_key = %s AND event_key = %s
				 ORDER BY id DESC LIMIT 1",
				$session_key, $ekey
			));

			$now_gmt      = current_time( 'mysql', true );
			$dedup_window = 60; // seconds
			$can_bump     = false;

			if ( $existing && ! empty( $existing->created_at ) ) {
				$then = strtotime( $existing->created_at );
				$now  = strtotime( $now_gmt );
				if ( $then && $now && ($now - $then) <= $dedup_window ) {
					$can_bump = true;
				}
			}

			if ( $can_bump ) {
				$wpdb->update(
					$table,
					[
						'repeat_count' => absint( $existing->repeat_count ) + 1,
						'updated_at'   => $now_gmt,
						// Keep latest payload (helps troubleshoot)
						'event_payload'=> wp_json_encode( $payload ),
						'user_id'      => absint( $user_id ),
						'ip_address'   => $_SERVER['REMOTE_ADDR']     ?? '',
						'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? '',
					],
					[ 'id' => absint($existing->id) ],
					[ '%d','%s','%s','%d','%s','%s' ],
					[ '%d' ]
				);
				return $existing->id;
			}

			// Insert new noisy row (first occurrence or outside window)
			$row = [
				'session_key'   => sanitize_text_field( $session_key ),
				'user_id'       => absint( $user_id ),
				'event_type'    => sanitize_text_field( $type ),
				'event_key'     => $ekey,
				'event_payload' => wp_json_encode( $payload ),
				'ip_address'    => $_SERVER['REMOTE_ADDR']     ?? '',
				'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? '',
				'created_at'    => $now_gmt,
				'updated_at'    => $now_gmt,
			];
			$wpdb->insert( $table, $row );
			return $wpdb->insert_id;
		}

		// Default: non-noisy
		return self::insert_event( $session_key, $type, $payload, $user_id );
	}

	/**
	 * Sessions summary (one row per session_key)
	 */
	public static function get_sessions_summary( $limit = 100, $errors_only = false ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aaa_checkout_sessions';

		// Aggregate errors by event_type bucket
		$sql = "
			SELECT
				session_key,
				MAX(created_at) as last_at,
				MIN(created_at) as first_at,
				COUNT(*) as event_count,
				SUM(CASE WHEN event_type IN ('js_error','js_unhandled','block_validation','block_notice') THEN 1 ELSE 0 END) as err_count,
				MAX(user_id) as user_id,
				MAX(ip_address) as ip_address
			FROM {$table}
			GROUP BY session_key
			ORDER BY last_at DESC
			LIMIT %d
		";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, absint( $limit ) ) );
		if ( $errors_only ) {
			$rows = array_values( array_filter( $rows, function( $r ){ return intval($r->err_count) > 0; } ) );
		}
		return $rows;
	}

	public static function get_events_by_session( $session_key, $limit = 500 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aaa_checkout_sessions';
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE session_key = %s ORDER BY created_at ASC LIMIT %d",
				$session_key, absint($limit)
			)
		);
	}

	public static function get_event( $event_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aaa_checkout_sessions';
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", absint($event_id) )
		);
	}
}
