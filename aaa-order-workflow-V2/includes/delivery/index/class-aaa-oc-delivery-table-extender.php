<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/delivery/index/class-aaa-oc-delivery-table-extender.php
 * Purpose: Extend core aaa_oc_order_index with Delivery fields used by the board/UI.
 * Version: 1.1.0
 *
 * Changes in 1.1.0:
 * - Add columns: delivery_latitude, delivery_longitude, delivery_address_line,
 *   travel_time_seconds, travel_distance_meters
 * - Add reschedule/ASAP columns: was_rescheduled, reschedule_count, last_rescheduled_at,
 *   original_delivery_ts, reschedule_reason, rescheduled_by, is_asap_zone, asap_zone_id,
 *   asap_eta_minutes, asap_eta_computed_at, asap_fee
 * - Add index: idx_delivery_geo (delivery_latitude, delivery_longitude)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class AAA_OC_Delivery_Table_Extender {

	public static function declared_columns(): array {
		// Existing delivery slice
		$base = [
			'delivery_date_ts','delivery_date_formatted','delivery_date_locale',
			'delivery_time','delivery_time_range','driver_id',
			'is_scheduled','is_same_day','is_asap',
		];
		// Coords + address + travel
		$geo  = [
			'delivery_latitude','delivery_longitude','delivery_address_line',
			'travel_time_seconds','travel_distance_meters',
		];
		// Reschedule + ASAP flags/meta
		$res  = [
			'was_rescheduled','reschedule_count','last_rescheduled_at',
			'original_delivery_ts','reschedule_reason','rescheduled_by',
			'is_asap_zone','asap_zone_id','asap_eta_minutes','asap_eta_computed_at','asap_fee',
		];
		return array_merge( $base, $geo, $res );
	}

	public static function maybe_install() : void {
		global $wpdb;
		$table = $wpdb->prefix . 'aaa_oc_order_index';

		// Bail if table missing
		$like = $wpdb->esc_like( $table );
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$like}'" ) !== $table ) {
			error_log('[DeliveryExt] OI table missing; skip install');
			return;
		}

		foreach ( self::declared_columns() as $col ) {
			switch ( $col ) {
				// Existing
				case 'delivery_date_ts':        self::add_col_if_missing( $table, "delivery_date_ts BIGINT(20) DEFAULT NULL" ); break;
				case 'delivery_date_formatted': self::add_col_if_missing( $table, "delivery_date_formatted DATE DEFAULT NULL" ); break;
				case 'delivery_date_locale':    self::add_col_if_missing( $table, "delivery_date_locale VARCHAR(255) DEFAULT NULL" ); break;
				case 'delivery_time':           self::add_col_if_missing( $table, "delivery_time VARCHAR(40) DEFAULT NULL" ); break;
				case 'delivery_time_range':     self::add_col_if_missing( $table, "delivery_time_range VARCHAR(100) DEFAULT NULL" ); break;
				case 'driver_id':               self::add_col_if_missing( $table, "driver_id BIGINT(20) DEFAULT NULL" ); break;
				case 'is_scheduled':            self::add_col_if_missing( $table, "is_scheduled TINYINT(1) DEFAULT 0" ); break;
				case 'is_same_day':             self::add_col_if_missing( $table, "is_same_day TINYINT(1) DEFAULT 0" ); break;
				case 'is_asap':                 self::add_col_if_missing( $table, "is_asap TINYINT(1) DEFAULT 0" ); break;
				// New: coords + address + travel
				case 'delivery_latitude':       self::add_col_if_missing( $table, "delivery_latitude DECIMAL(10,6) DEFAULT NULL" ); break;
				case 'delivery_longitude':      self::add_col_if_missing( $table, "delivery_longitude DECIMAL(10,6) DEFAULT NULL" ); break;
				case 'delivery_address_line':   self::add_col_if_missing( $table, "delivery_address_line VARCHAR(255) DEFAULT NULL" ); break;
				case 'travel_time_seconds':     self::add_col_if_missing( $table, "travel_time_seconds INT DEFAULT NULL" ); break;
				case 'travel_distance_meters':  self::add_col_if_missing( $table, "travel_distance_meters INT DEFAULT NULL" ); break;

				// New: reschedule + ASAP fields
				case 'was_rescheduled':         self::add_col_if_missing( $table, "was_rescheduled TINYINT(1) DEFAULT 0" ); break;
				case 'reschedule_count':        self::add_col_if_missing( $table, "reschedule_count INT DEFAULT 0" ); break;
				case 'last_rescheduled_at':     self::add_col_if_missing( $table, "last_rescheduled_at DATETIME DEFAULT NULL" ); break;
				case 'original_delivery_ts':    self::add_col_if_missing( $table, "original_delivery_ts BIGINT(20) DEFAULT NULL" ); break;
				case 'reschedule_reason':       self::add_col_if_missing( $table, "reschedule_reason VARCHAR(255) DEFAULT NULL" ); break;
				case 'rescheduled_by':          self::add_col_if_missing( $table, "rescheduled_by VARCHAR(64) DEFAULT NULL" ); break;
				case 'is_asap_zone':            self::add_col_if_missing( $table, "is_asap_zone TINYINT(1) DEFAULT 0" ); break;
				case 'asap_zone_id':            self::add_col_if_missing( $table, "asap_zone_id VARCHAR(64) DEFAULT NULL" ); break;
				case 'asap_eta_minutes':        self::add_col_if_missing( $table, "asap_eta_minutes INT DEFAULT NULL" ); break;
				case 'asap_eta_computed_at':    self::add_col_if_missing( $table, "asap_eta_computed_at DATETIME DEFAULT NULL" ); break;
				case 'asap_fee':                self::add_col_if_missing( $table, "asap_fee DECIMAL(10,2) DEFAULT NULL" ); break;
			}
		}

		// Existing helpful indexes
		self::add_index_if_missing( $table, 'idx_delivery_date', '(delivery_date_formatted)' );
		self::add_index_if_missing( $table, 'idx_driver',        '(driver_id)' );
		// New geo index
		self::add_index_if_missing( $table, 'idx_delivery_geo',  '(delivery_latitude, delivery_longitude)' );
	}

	private static function add_col_if_missing( string $table, string $col_def ) : void {
		global $wpdb;
		$parts = preg_split('/\s+/', trim($col_def));
		$col   = $parts[0] ?? '';
		if ( $col === '' ) return;

		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COLUMN_NAME
				   FROM INFORMATION_SCHEMA.COLUMNS
				  WHERE TABLE_SCHEMA = DATABASE()
				    AND TABLE_NAME   = %s
				    AND COLUMN_NAME  = %s
				  LIMIT 1",
				$table, $col
			)
		);
		if ( $exists ) return;

		$wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN {$col_def}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private static function add_index_if_missing( string $table, string $idx_name, string $idx_cols_sql ) : void {
		global $wpdb;

		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT INDEX_NAME
				   FROM INFORMATION_SCHEMA.STATISTICS
				  WHERE TABLE_SCHEMA = DATABASE()
				    AND TABLE_NAME   = %s
				    AND INDEX_NAME   = %s
				  LIMIT 1",
				$table, $idx_name
			)
		);
		if ( $exists ) return;

		$cols = self::parse_index_columns( $idx_cols_sql );
		if ( empty( $cols ) ) {
			error_log("[DeliveryExt] skip index {$idx_name}: empty cols spec");
			return;
		}
		foreach ( $cols as $c ) {
			$present = $wpdb->get_var( $wpdb->prepare(
				"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = %s LIMIT 1",
				$table, $c
			) );
			if ( $present !== $c ) {
				error_log("[DeliveryExt] skip index {$idx_name}: missing column {$c}");
				return;
			}
		}
		$backticked = '`' . implode('`,`', $cols) . '`';
		$wpdb->query( "ALTER TABLE `{$table}` ADD KEY `{$idx_name}` ({$backticked})" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private static function parse_index_columns( string $cols_sql ): array {
		$cols_sql = trim($cols_sql);
		if ( $cols_sql !== '' && $cols_sql[0] === '(' && substr($cols_sql, -1) === ')' ) {
			$cols_sql = substr($cols_sql, 1, -1);
		}
		$parts = array_map( 'trim', explode( ',', $cols_sql ) );
		$out   = [];
		foreach ( $parts as $p ) {
			$p = trim( $p, " \t\n\r\0\x0B`" );
			if ( $p !== '' ) $out[] = $p;
		}
		return $out;
	}
}
