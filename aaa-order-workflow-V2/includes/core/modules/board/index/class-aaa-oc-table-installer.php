<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/index/class-aaa-oc-table-installer.php
 * Purpose: Create the base aaa_oc_order_index table (idempotent, versioned).
 * Style:   Prefer direct CREATE TABLE on first install; skip dbDelta when table exists.
 *          If CREATE fails, fallback to sanitized dbDelta once.
 * Version: 1.0.7
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_OC_Tree_Shake { // tiny helper to sanitize CREATE for dbDelta fallback
	public static function sanitize_create_sql( $sql ) {
		$lines = preg_split('/\R/', $sql);
		$out   = [];
		foreach ( $lines as $ln ) {
			$trim = trim($ln);
			$isKey = preg_match('/^(UNIQUE\s+KEY|PRIMARY\s+KEY|KEY)\b/i', $trim);
			$isPri = preg_match('/^PRIMARY\s+KEY\s*\(/i', $trim);
			$hasN  = preg_match('/^(UNIQUE\s+KEY|KEY)\s+`?([A-Za-z0-9_]+)`?\s*\(/i', $trim);
			$hasC  = preg_match('/\([^)]*\)/', $trim);
			if ($isKey && ! $isPri && (! $hasN || ! $hasC)) { continue; }
			$out[] = $ln;
		}
		$san = implode("\n", $out);
		$san = preg_replace('/,\s*\)\s*;?$/m', ")\n", $san);
		return $san;
	}
}

class AAA_OC_Table_Installer {

	/** Bump when schema changes (no schema change vs 1.0.6) */
	const SCHEMA_VERSION = '1.0.6';

	/** One-per-request guard */
	private static $did = false;

	private static function debug_on(): bool {
		if ( function_exists('aaa_oc_get_option') ) {
			return (bool) aaa_oc_get_option( 'board_debug', 'modules', 0 );
		}
		return false;
	}

	public static function ensure_installed(): void {
		if ( self::$did ) return;
		self::$did = true;

		global $wpdb;
		$table   = $wpdb->prefix . 'aaa_oc_order_index';
		$opt_key = 'aaa_oc_order_index_schema';

		self::create_index_table(); // only creates when missing

		if ( self::table_exists( $table ) ) {
			update_option( $opt_key, self::SCHEMA_VERSION, true );
			if ( self::debug_on() ) {
				$msg = "[OrderIndex][Installer] version set to ".self::SCHEMA_VERSION;
				if ( function_exists('aaa_oc_log') ) { aaa_oc_log($msg); } else { error_log($msg); }
			}
		}
	}

	private static function table_exists( string $table ): bool {
		global $wpdb;
		$exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
		return ( $exists === $table );
	}

	public static function create_index_table(): void {
		global $wpdb;

		$table   = $wpdb->prefix . 'aaa_oc_order_index';
		$charset = $wpdb->get_charset_collate();

		// If the table already exists, DO NOT call dbDelta again.
		if ( self::table_exists( $table ) ) {
			if ( self::debug_on() ) {
				$msg = "[OrderIndex][Installer] ensured: {$table}";
				if ( function_exists('aaa_oc_log') ) { aaa_oc_log($msg); } else { error_log($msg); }
			}
			return;
		}

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id BIGINT(20) UNSIGNED NOT NULL,

			paid_date DATETIME DEFAULT NULL,
			_completed_date DATETIME DEFAULT NULL,
			_paid_date DATETIME DEFAULT NULL,
			_payment_method_title VARCHAR(255) DEFAULT NULL,
			payment_status VARCHAR(20) DEFAULT 'unpaid',

			status VARCHAR(50) NOT NULL,
			order_number VARCHAR(50) NOT NULL,
			time_published DATETIME NOT NULL,
			time_in_status DATETIME NOT NULL,

			total_amount DECIMAL(10,2) NOT NULL,
			subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
			shipping_total DECIMAL(10,2) NOT NULL DEFAULT 0,
			tax_total DECIMAL(10,2) NOT NULL DEFAULT 0,
			discount_total DECIMAL(10,2) NOT NULL DEFAULT 0,
			_cart_discount DECIMAL(10,2) DEFAULT 0,

			fees_json LONGTEXT DEFAULT NULL,
			currency VARCHAR(10) DEFAULT '',

			customer_name VARCHAR(255) NOT NULL,
			customer_email VARCHAR(255) DEFAULT NULL,
			customer_phone VARCHAR(50) DEFAULT NULL,
			customer_note TEXT DEFAULT NULL,

			daily_order_number INT DEFAULT 0,
			customer_completed_orders INT DEFAULT 0,
			average_order_amount DECIMAL(10,2) DEFAULT 0,
			lifetime_spend DECIMAL(10,2) DEFAULT 0,

			brand_list TEXT DEFAULT NULL,
			items LONGTEXT DEFAULT NULL,
			coupons LONGTEXT DEFAULT NULL,

			billing_json LONGTEXT DEFAULT NULL,
			shipping_json LONGTEXT DEFAULT NULL,
			shipping_method VARCHAR(255) DEFAULT NULL,

			shipping_address_1 VARCHAR(255) DEFAULT '',
			shipping_address_2 VARCHAR(255) DEFAULT '',
			shipping_city VARCHAR(100) DEFAULT '',
			shipping_state VARCHAR(100) DEFAULT '',
			shipping_postcode VARCHAR(40) DEFAULT '',
			shipping_country VARCHAR(2) NOT NULL DEFAULT '',

			billing_address_1 VARCHAR(255) DEFAULT '',
			billing_address_2 VARCHAR(255) DEFAULT '',
			billing_city VARCHAR(100) DEFAULT '',
			billing_state VARCHAR(100) DEFAULT '',
			billing_postcode VARCHAR(40) DEFAULT '',
			billing_country VARCHAR(2) NOT NULL DEFAULT '',

			_created_via VARCHAR(255) DEFAULT NULL,
			_customer_user BIGINT(20) DEFAULT 0,
			_order_total DECIMAL(10,2) DEFAULT 0,
			_recorded_sales DECIMAL(10,2) DEFAULT 0,
			_wc_order_attribution_source_type VARCHAR(255) DEFAULT NULL,
			_wpslash_tip DECIMAL(10,2) DEFAULT 0,

			wc_transaction_id VARCHAR(100) DEFAULT NULL,
			gateway_transaction_id VARCHAR(100) DEFAULT NULL,

			last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

			PRIMARY KEY (id),
			UNIQUE KEY uq_order_id (order_id),
			KEY idx_status (status),
			KEY idx_time_published (time_published),
			KEY idx_time_in_status (time_in_status),
			KEY idx_status_time (status, time_published),
			KEY idx_payment_status (payment_status),
			KEY idx_order_number (order_number),
			KEY idx_customer_email (customer_email),
			KEY idx_wc_txn (wc_transaction_id),
			KEY idx_gateway_txn (gateway_transaction_id)
		) ENGINE=InnoDB {$charset};";

		// First try a plain CREATE (no dbDelta parser involved)
		$ok = $wpdb->query( $sql ); // returns 0 on "already exists", >0 on created, false on error

		if ( $ok === false ) {
			// As a last resort, run a single sanitized dbDelta to cope with hosts that demand it.
			if ( function_exists( 'aaa_oc_log' ) ) { aaa_oc_log('[OrderIndex][Installer] direct CREATE failed, attempting sanitized dbDelta…'); }
			else { error_log('[OrderIndex][Installer] direct CREATE failed, attempting sanitized dbDelta…'); }

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$clean = AAA_OC_Tree_Shake::sanitize_create_sql( str_replace('IF NOT EXISTS ', '', $sql) );
			dbDelta( $clean );
		}

		if ( self::debug_on() ) {
			$msg = "[OrderIndex][Installer] ensured: {$table}";
			if ( function_exists('aaa_oc_log') ) { aaa_oc_log($msg); } else { error_log($msg); }
		}
	}
}
