<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payment/index/class-aaa-oc-payment-table-extender.php
 * Purpose: Extend aaa_oc_order_index with payment columns and keep it synced from aaa_oc_payment_index.
 * Notes:
 *  - Safe, guarded ALTERs (only when base table exists)
 *  - Index creation uses information_schema and SHOW TABLES LIKE guards
 *  - One-per-request guard for install()
 *  - Debug via class constant and WFCP option (modules scope: payment_debug)
 * Version: 1.3.2 (sanitized index creation; no schema changes)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_OC_Payment_Table_Extender {

	/** Local file debug toggle (dev default can be false; WFCP still respected) */
	private const DEBUG_THIS_FILE = false;

	/** One-per-request guard for install() */
	private static $did_install = false;

	private static function log( string $m ): void {
		$on = self::DEBUG_THIS_FILE;
		if ( ! $on && function_exists('aaa_oc_get_option') ) {
			$on = (bool) aaa_oc_get_option( 'payment_debug', 'modules', 0 );
		}
		if ( $on ) {
			if ( function_exists('aaa_oc_log') ) { aaa_oc_log('[PayExt] ' . $m); }
			else { error_log('[PayExt] ' . $m); }
		}
	}

	public static function install(): void {
		if ( self::$did_install ) return;
		self::$did_install = true;

		global $wpdb;
		$table = $wpdb->prefix . 'aaa_oc_order_index';

		// --- Guard: base table must exist (attempt self-heal once) ---
		$like   = $wpdb->esc_like( $table );
		$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$like}'" );
		if ( $exists !== $table ) {
			if ( class_exists( 'AAA_OC_Table_Installer' ) && method_exists( 'AAA_OC_Table_Installer', 'create_index_table' ) ) {
				self::log("Base table missing: {$table}. Attempting self-heal in install().");
				AAA_OC_Table_Installer::create_index_table();
				$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$like}'" );
			}
			if ( $exists !== $table ) {
				self::log("Base table still missing after self-heal. Skipping extender install.");
				return;
			}
		}

		// --- Desired columns (only add if missing) ---
		$want = [
			'aaa_oc_payment_status' => "VARCHAR(20)  DEFAULT 'unpaid'",
			'aaa_oc_epayment_total' => "DECIMAL(10,2) DEFAULT 0.00",
			'aaa_oc_payrec_total'   => "DECIMAL(10,2) DEFAULT 0.00",
			'aaa_oc_order_balance'  => "DECIMAL(10,2) DEFAULT 0.00",
			'aaa_oc_order_total'    => "DECIMAL(10,2) DEFAULT 0.00",
			'aaa_oc_tip_total'      => "DECIMAL(10,2) DEFAULT 0.00",
			'epayment_tip'          => "DECIMAL(10,2) DEFAULT 0.00",
			'total_order_tip'       => "DECIMAL(10,2) DEFAULT 0.00",
			'real_payment_method'   => "VARCHAR(100) NULL",
			'epayment_detail'       => "TEXT NULL",
			'envelope_outstanding'  => "TINYINT(1) NOT NULL DEFAULT 0",
			'cleared'               => "TINYINT(1) NOT NULL DEFAULT 0",
			'driver_id'             => "BIGINT UNSIGNED NULL",
			'envelope_id'           => "VARCHAR(100) NULL",
			'route_id'              => "VARCHAR(100) NULL",
			'last_payment_at'       => "DATETIME NULL",
		];

		$existing = (array) $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`", 0 );
		$alters   = [];
		foreach ( $want as $col => $def ) {
			if ( ! in_array( $col, $existing, true ) ) {
				$alters[] = "ADD COLUMN `{$col}` {$def}";
			}
		}
		if ( $alters ) {
			$sql = "ALTER TABLE `{$table}` " . implode( ', ', $alters );
			$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			self::log('Added columns: ' . implode(', ', array_keys($want)));
		}

		// --- Indexes (safe add) ---
		self::ensure_index( $table, 'idx_pay_status',       '(`aaa_oc_payment_status`)' );
		self::ensure_index( $table, 'idx_pay_method',       '(`real_payment_method`)' );
		self::ensure_index( $table, 'idx_pay_env_out',      '(`envelope_outstanding`)' );
		self::ensure_index( $table, 'idx_pay_cleared',      '(`cleared`)' );
		self::ensure_index( $table, 'idx_pay_last_payment', '(`last_payment_at`)' );
	}

	/**
	 * Ensure a secondary index exists on the base order index table.
	 * Bails (no-op) if base table is missing; may attempt self-heal once.
	 * NOTE: $cols_sql may be in the form "(col)" or "(`col`,`col2`)" — we sanitize it.
	 */
	public static function ensure_index( string $table, string $index_name, string $cols_sql ): bool {
		global $wpdb;

		// Guard: base table must exist first
		$like   = $wpdb->esc_like( $table );
		$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$like}'" );
		if ( $exists !== $table ) {
			if ( class_exists( 'AAA_OC_Table_Installer' ) && method_exists( 'AAA_OC_Table_Installer', 'create_index_table' ) ) {
				self::log("Base table missing: {$table}. Attempting self-heal in ensure_index().");
				AAA_OC_Table_Installer::create_index_table();
				$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$like}'" );
			}
			if ( $exists !== $table ) {
				self::log("Base table still missing; skip index {$index_name} {$cols_sql}.");
				return false;
			}
		}

		// Sanitize columns string → array of names → backticked list
		$cols = self::parse_index_columns($cols_sql);

		if ( empty($index_name) || empty($cols) ) {
			self::log("ensure_index: invalid index spec (name/cols) for {$table}");
			return false;
		}

		// Ensure columns exist
		foreach ( $cols as $c ) {
			$present = $wpdb->get_var( $wpdb->prepare(
				"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = %s LIMIT 1",
				$table, $c
			) );
			if ( $present !== $c ) {
				self::log("ensure_index: missing column {$table}.{$c} — skip {$index_name}");
				return false;
			}
		}

		// Already exists?
		$idx = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(1)
			   FROM information_schema.statistics
			  WHERE table_schema = DATABASE()
			    AND table_name   = %s
			    AND index_name   = %s",
			$table, $index_name
		) );
		if ( $idx && intval( $idx ) > 0 ) {
			self::log("Index exists: {$index_name} on {$table}");
			return true;
		}

		$backticked = '`' . implode('`,`', $cols) . '`';
		$sql = "ALTER TABLE `{$table}` ADD INDEX `{$index_name}` ({$backticked})";
		$res = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( false === $res ) {
			self::log("Failed to add index {$index_name} on {$table}. SQL: {$sql}. MySQL: {$wpdb->last_error}");
			return false;
		}
		self::log("Added index {$index_name} on {$table} with ({$backticked})");
		return true;
	}

	/** Parse "(col, `col2` )" or "(`col`)" into ['col','col2'] safely */
	private static function parse_index_columns( string $cols_sql ): array {
		$cols_sql = trim($cols_sql);
		// Strip wrapping parens
		if ( strlen($cols_sql) && $cols_sql[0] === '(' && substr($cols_sql, -1) === ')' ) {
			$cols_sql = substr($cols_sql, 1, -1);
		}
		// Split and normalize
		$parts = array_map( 'trim', explode( ',', $cols_sql ) );
		$out   = [];
		foreach ( $parts as $p ) {
			$p = trim( $p, " \t\n\r\0\x0B`" );
			if ( $p !== '' ) $out[] = $p;
		}
		return $out;
	}

	/**
	 * Copy the latest values from aaa_oc_payment_index to aaa_oc_order_index for a given order.
	 * Does not overwrite driver_id unless present in the payment row.
	 */
	public static function sync_for_order( int $order_id ): void {
		if ( $order_id <= 0 ) return;

		global $wpdb;
		$oi = $wpdb->prefix . 'aaa_oc_order_index';
		$pi = $wpdb->prefix . 'aaa_oc_payment_index';

		$like = $wpdb->esc_like( $oi );
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$like}'" ) !== $oi ) {
			self::log("sync_for_order: base OI table missing; skip for order {$order_id}");
			return;
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					aaa_oc_payment_status,
					aaa_oc_epayment_total,
					aaa_oc_payrec_total,
					aaa_oc_order_balance,
					aaa_oc_order_total,
					aaa_oc_tip_total,
					epayment_tip,
					total_order_tip,
					real_payment_method,
					epayment_detail,
					envelope_outstanding,
					cleared,
					driver_id,
					envelope_id,
					route_id,
					last_payment_at
				 FROM `{$pi}`
				 WHERE order_id = %d
				 ORDER BY id DESC
				 LIMIT 1",
				$order_id
			),
			ARRAY_A
		);

		if ( ! $row ) {
			self::log("sync_for_order: no payment row for order {$order_id}");
			return;
		}

		$tip_canonical = (float) ( $row['aaa_oc_tip_total'] ?? 0 );
		if ( $tip_canonical <= 0 ) {
			$tip_new    = (float) get_post_meta( $order_id, 'aaa_oc_tip_total', true );
			$tip_legacy = (float) get_post_meta( $order_id, '_wpslash_tip', true );
			$tip_canonical = $tip_new > 0 ? $tip_new : $tip_legacy;
		}

		$total_order_tip = isset( $row['total_order_tip'] )
			? (float) $row['total_order_tip']
			: ( $tip_canonical + (float) ( $row['epayment_tip'] ?? 0 ) );

		$real = (string) ( $row['real_payment_method'] ?? '' );
		if ( $real === '' || ( strtoupper( $real ) === $real && strlen( $real ) <= 3 ) ) {
			$gw_title = (string) get_post_meta( $order_id, '_payment_method_title', true );
			$real = $gw_title ?: $real;
		}

		$data = [
			'aaa_oc_payment_status' => (string) ( $row['aaa_oc_payment_status'] ?? 'unpaid' ),
			'aaa_oc_epayment_total' => (float)  ( $row['aaa_oc_epayment_total'] ?? 0 ),
			'aaa_oc_payrec_total'   => (float)  ( $row['aaa_oc_payrec_total'] ?? 0 ),
			'aaa_oc_order_balance'  => (float)  ( $row['aaa_oc_order_balance'] ?? 0 ),
			'aaa_oc_order_total'    => (float)  ( $row['aaa_oc_order_total'] ?? 0 ),
			'aaa_oc_tip_total'      => (float)  $tip_canonical,
			'epayment_tip'          => (float)  ( $row['epayment_tip'] ?? 0 ),
			'total_order_tip'       => (float)  $total_order_tip,
			'real_payment_method'   => (string) $real,
			'epayment_detail'       => (string) ( $row['epayment_detail'] ?? '' ),
			'envelope_outstanding'  => (int)    ( $row['envelope_outstanding'] ?? 0 ),
			'cleared'               => (int)    ( $row['cleared'] ?? 0 ),
			'envelope_id'           => (string) ( $row['envelope_id'] ?? '' ),
			'route_id'              => (string) ( $row['route_id'] ?? '' ),
			'last_payment_at'       => (string) ( $row['last_payment_at'] ?? null ),
		];
		$formats = [
			'%s','%f','%f','%f','%f','%f','%f','%f','%s','%s','%d','%d','%s','%s','%s'
		];

		if ( array_key_exists('driver_id', $row) && $row['driver_id'] !== null && $row['driver_id'] !== '' ) {
			$data['driver_id'] = (int) $row['driver_id'];
			$formats[] = '%d';
		}

		$wpdb->update(
			$oi,
			$data,
			[ 'order_id' => $order_id ],
			$formats,
			[ '%d' ]
		);

		self::log("sync_for_order: updated OI for order {$order_id}");
	}
}
