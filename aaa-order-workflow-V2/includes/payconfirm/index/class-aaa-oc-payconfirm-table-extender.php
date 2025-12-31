<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/index/class-aaa-oc-payconfirm-table-extender.php
 * Purpose: Extend order_index with PayConfirm columns (incl. payer-alias snapshot) + soft pointer on payment_index.
 * Version: 1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_OC_PayConfirm_Table_Extender {
	private static $did = false;

	private static function debug_on() : bool {
		if ( function_exists('aaa_oc_get_option') ) {
			return (bool) aaa_oc_get_option( 'payconfirm_debug', 'modules', 0 );
		}
		return false;
	}

	public static function install() : void {
		if ( self::$did ) return;
		self::$did = true;

		global $wpdb;

		$oi = $wpdb->prefix . 'aaa_oc_order_index';
		$want_oi = [
			'pc_post_id'           => "BIGINT UNSIGNED NULL",
			'pc_matched_order_id'  => "BIGINT UNSIGNED NOT NULL DEFAULT 0",
			'pc_txn'               => "VARCHAR(191) NULL",
			'pc_amount'            => "DECIMAL(10,2) NULL",
			'pc_match_status'      => "VARCHAR(32) NOT NULL DEFAULT 'unmatched'",
			/** NEW: alias snapshot for index-only matching */
			'pc_aliases'           => "LONGTEXT NULL",
			'pc_alias_snapshot_ts' => "DATETIME NULL",
		];

		self::maybe_add_columns( $oi, $want_oi );
		self::ensure_index( $oi, 'idx_pc_match_status',      '(pc_match_status)' );
		self::ensure_index( $oi, 'idx_pc_txn',               '(pc_txn)' );
		self::ensure_index( $oi, 'idx_pc_post_id',           '(pc_post_id)' );
		self::ensure_index( $oi, 'idx_pc_alias_snapshot_ts', '(pc_alias_snapshot_ts)' );

		// --- payment_index soft pointer (for long-term joins) ---
		$pi = $wpdb->prefix . 'aaa_oc_payment_index';
		if ( $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s",
			$pi
		) ) ) {
			self::maybe_add_columns( $pi, [ 'pc_matched_order_id' => "BIGINT UNSIGNED NOT NULL DEFAULT 0" ] );
			self::ensure_index( $pi, 'idx_pi_pc_matched_order_id', '(pc_matched_order_id)' );
		}
	}

	private static function maybe_add_columns( string $table, array $want ) : void {
		global $wpdb;
		$existing = $wpdb->get_col( "SHOW COLUMNS FROM {$table}", 0 );
		if ( ! is_array( $existing ) ) $existing = [];

		$alters = [];
		foreach ( $want as $col => $ddl ) {
			if ( ! in_array( $col, $existing, true ) ) $alters[] = "ADD COLUMN {$col} {$ddl}";
		}

		if ( $alters ) {
			$sql = "ALTER TABLE {$table} " . implode( ', ', $alters );
			$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( self::debug_on() ) {
				$msg = '[AAA_OC_PayConfirm_Table_Extender] ALTER: ' . $sql;
				if ( function_exists('aaa_oc_log') ) { aaa_oc_log($msg); } else { error_log($msg); }
			}
		}
	}

	private static function ensure_index( string $table, string $name, string $cols_expr ) : void {
		global $wpdb;
		$name = trim( $name ); $cols_expr = trim( $cols_expr );
		if ( $name === '' || $cols_expr === '' ) return;

		$has = $wpdb->get_var( $wpdb->prepare(
			"SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
			 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND INDEX_NAME = %s",
			$table, $name
		) );
		if ( $has ) return;

		$wpdb->query( "ALTER TABLE {$table} ADD INDEX `{$name}` {$cols_expr}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( self::debug_on() ) {
			$msg = "[AAA_OC_PayConfirm_Table_Extender] Added index {$name} on {$table}";
			if ( function_exists('aaa_oc_log') ) { aaa_oc_log($msg); } else { error_log($msg); }
		}
	}
}
