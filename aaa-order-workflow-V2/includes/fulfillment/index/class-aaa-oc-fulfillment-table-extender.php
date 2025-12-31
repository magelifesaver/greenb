<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/fulfillment/index/class-aaa-oc-fulfillment-table-extender.php
 * Purpose: Extend aaa_oc_order_index with fulfillment-related columns and indexes used by board/UI.
 * Style:   Guarded ALTERs, safe index creation, WFCP-driven debug, one-per-request guard.
 * Version: 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_OC_Fulfillment_Table_Extender {

	/** One-per-request guard */
	private static $did = false;

	/** WFCP-driven debug (modules scope → key: fulfillment_debug) */
	private static function debug_on() : bool {
		if ( function_exists('aaa_oc_get_option') ) {
			return (bool) aaa_oc_get_option( 'fulfillment_debug', 'modules', 0 );
		}
		return false;
	}

	public static function install() : void {
		if ( self::$did ) return;
		self::$did = true;

		global $wpdb;
		$oi = $wpdb->prefix . 'aaa_oc_order_index';

		$want = [
			'fulfillment_status'          => "VARCHAR(32) NOT NULL DEFAULT 'not_picked'",
			'picked_items'                => "LONGTEXT NULL",
			'usbs_order_fulfillment_data' => "LONGTEXT NULL",
		];

		$existing = $wpdb->get_col( "SHOW COLUMNS FROM {$oi}", 0 );
		if ( ! is_array( $existing ) ) $existing = [];

		$alters = [];
		foreach ( $want as $col => $ddl ) {
			if ( ! in_array( $col, $existing, true ) ) {
				$alters[] = "ADD COLUMN {$col} {$ddl}";
			}
		}

		if ( ! empty( $alters ) ) {
			$sql = "ALTER TABLE {$oi} " . implode( ', ', $alters );
			$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( self::debug_on() ) {
				$msg = '[AAA_OC_Fulfillment_Table_Extender] ALTER: ' . $sql;
				if ( function_exists('aaa_oc_log') ) { aaa_oc_log($msg); } else { error_log($msg); }
			}
		}

		self::ensure_index( $oi, 'idx_fulfillment_status',  '(`fulfillment_status`)' );
	}

	private static function ensure_index( string $table, string $name, string $cols_expr ) : void {
		global $wpdb;
		$name = trim( $name );
		$cols_expr = trim( $cols_expr );

		if ( $name === '' || $cols_expr === '' ) {
			if ( self::debug_on() ) {
				$msg = "[AAA_OC_Fulfillment_Table_Extender] Skipped invalid index creation for {$table}";
				if ( function_exists('aaa_oc_log') ) { aaa_oc_log($msg); } else { error_log($msg); }
			}
			return;
		}

		$has = $wpdb->get_var( $wpdb->prepare(
			"SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
			 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND INDEX_NAME = %s",
			$table, $name
		) );

		if ( $has ) return;

		$wpdb->query( "ALTER TABLE {$table} ADD INDEX `{$name}` {$cols_expr}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( self::debug_on() ) {
			$msg = "[AAA_OC_Fulfillment_Table_Extender] Added index {$name} on {$table}";
			if ( function_exists('aaa_oc_log') ) { aaa_oc_log($msg); } else { error_log($msg); }
		}
	}
}
