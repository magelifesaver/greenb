<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/options/index/class-aaa-oc-options-table-extender.php
 * Purpose: Core extender utilities for options-driven schema changes.
 *          - Add driver_id to order_index and payment_index when admin enables the option.
 * Style:   db-safe ALTERs with guards; consistent logging; no blank/invalid indexes.
 * Version: 1.1.1 (guarded index/column ops; no schema changes)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_OC_Options_Table_Extender {

	/** Per-file debug toggle (default true during development) */
	private const DEBUG = true;

	/** One-per-request guard */
	private static $did = false;

	/** Public entry — extend both index tables with driver_id. */
	public static function ensure_driver_columns() : void {
		if ( self::$did ) return;
		self::$did = true;

		global $wpdb;

		$oi = $wpdb->prefix . 'aaa_oc_order_index';
		$pi = $wpdb->prefix . 'aaa_oc_payment_index';

		// Order Index driver_id
		if ( self::table_exists( $oi ) ) {
			self::ensure_column( $oi, 'driver_id', 'BIGINT UNSIGNED NULL' );
			self::ensure_index(  $oi, 'idx_driver_id', ['driver_id'] );
		} else {
			self::log("Order index table not found: {$oi}");
		}

		// Payment Index driver_id (kept in sync)
		if ( self::table_exists( $pi ) ) {
			self::ensure_column( $pi, 'driver_id', 'BIGINT UNSIGNED NULL' );
			self::ensure_index(  $pi, 'idx_driver_id', ['driver_id'] );
		} else {
			self::log("Payment index table not found: {$pi}");
		}
	}

	/* ======================================================================
	 * Internals (do not change names or visibility)
	 * ==================================================================== */

	private static function log( string $msg ) : void {
		if ( ! self::DEBUG ) return;
		if ( function_exists( 'aaa_oc_log' ) ) { aaa_oc_log( "[OptionsExt] {$msg}" ); }
		else { error_log( "[OptionsExt] {$msg}" ); }
	}

	private static function table_exists( string $table ) : bool {
		global $wpdb;
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		return ( $found === $table );
	}

	private static function column_exists( string $table, string $column ) : bool {
		global $wpdb;
		$col = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COLUMN_NAME
				   FROM INFORMATION_SCHEMA.COLUMNS
				  WHERE TABLE_SCHEMA = DATABASE()
				    AND TABLE_NAME   = %s
				    AND COLUMN_NAME  = %s
				  LIMIT 1",
				$table, $column
			)
		);
		return ( $col === $column );
	}

	private static function ensure_column( string $table, string $column, string $definition ) : void {
		global $wpdb;
		if ( ! self::table_exists( $table ) ) {
			self::log("ensure_column: table missing {$table}");
			return;
		}
		if ( self::column_exists( $table, $column ) ) {
			self::log("Column exists: {$table}.{$column}");
			return;
		}
		$sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}";
		$res = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		self::log( $res !== false ? "Added {$table}.{$column}" : "FAILED to add {$table}.{$column} — {$wpdb->last_error}" );
	}

	private static function index_exists( string $table, string $index_name ) : bool {
		global $wpdb;
		$idx = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT INDEX_NAME
				   FROM INFORMATION_SCHEMA.STATISTICS
				  WHERE TABLE_SCHEMA = DATABASE()
				    AND TABLE_NAME   = %s
				    AND INDEX_NAME   = %s
				  LIMIT 1",
				$table, $index_name
			)
		);
		return ( $idx === $index_name );
	}

	private static function ensure_index( string $table, string $index_name, array $columns ) : void {
		global $wpdb;

		// Normalize and guard against blanks (prevents db errors like ADD `` (``))
		$columns   = array_values( array_filter( array_map( 'trim', $columns ) ) );
		$index_name = trim( $index_name );

		if ( $index_name === '' || empty( $columns ) ) {
			self::log("Skipped invalid index for {$table} (name/columns missing)");
			return;
		}

		if ( ! self::table_exists( $table ) ) {
			self::log("ensure_index: table missing {$table}");
			return;
		}

		// Guard: ensure all columns exist before attempting to index
		foreach ( $columns as $c ) {
			if ( ! self::column_exists( $table, $c ) ) {
				self::log("ensure_index: column missing {$table}.{$c} — skip {$index_name}");
				return;
			}
		}

		if ( self::index_exists( $table, $index_name ) ) {
			self::log("Index exists: {$table}.{$index_name}");
			return;
		}

		$cols_sql = '`' . implode( '`, `', $columns ) . '`';
		$sql      = "ALTER TABLE `{$table}` ADD INDEX `{$index_name}` ({$cols_sql})";
		$res      = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		self::log( $res !== false ? "Added index {$table}.{$index_name}" : "FAILED to add index {$table}.{$index_name} — {$wpdb->last_error}" );
	}
}
