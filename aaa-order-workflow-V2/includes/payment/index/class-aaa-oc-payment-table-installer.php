<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payment/index/class-aaa-oc-payment-table-installer.php
 * Purpose: Create/upgrade Payment Index + Change Log tables (dbDelta-safe).
 * Style:   Consistent formatting for dbDelta, InnoDB + charset, one-per-request guard, debug logging.
 * Version: 1.3.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_OC_Payment_Setup {

	/** Per-file debug toggle */
	private const DEBUG_THIS_FILE = false;

	/** One-per-request guard */
	private static $did = false;

	private static function run_dbdelta( string $sql ): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$clean = self::sanitize_create_sql( $sql );
		dbDelta( $clean );
	}

	private static function sanitize_create_sql( string $sql ): string {
		$lines = preg_split('/\R/', $sql);
		$out   = [];
		foreach ( $lines as $ln ) {
			$trim = trim($ln);
			$is_key     = preg_match('/^(UNIQUE\s+KEY|PRIMARY\s+KEY|KEY)\b/i', $trim);
			$is_primary = preg_match('/^PRIMARY\s+KEY\s*\(/i', $trim);
			$has_name   = preg_match('/^(UNIQUE\s+KEY|KEY)\s+`?([A-Za-z0-9_]+)`?\s*\(/i', $trim);
			$has_cols   = preg_match('/\(([^\)]+)\)/', $trim);
			if ( $is_key && ! $is_primary && ( ! $has_name || ! $has_cols ) ) {
				continue;
			}
			$out[] = $ln;
		}
		$sanitized = implode("\n", $out);
		$sanitized = preg_replace('/,\s*\)\s*ENGINE=/m', ') ENGINE=', $sanitized);
		return $sanitized;
	}

	public static function install() {
		if ( self::$did ) return;
		self::$did = true;

		global $wpdb;

		$charset       = $wpdb->get_charset_collate();
		$payment_table = $wpdb->prefix . 'aaa_oc_payment_index';
		$log_table     = $wpdb->prefix . 'aaa_oc_payment_log';

		$sql1 = "CREATE TABLE {$payment_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id BIGINT UNSIGNED NOT NULL,
			aaa_oc_cash_amount       DECIMAL(10,2) DEFAULT 0.00,
			aaa_oc_zelle_amount      DECIMAL(10,2) DEFAULT 0.00,
			aaa_oc_venmo_amount      DECIMAL(10,2) DEFAULT 0.00,
			aaa_oc_applepay_amount   DECIMAL(10,2) DEFAULT 0.00,
			aaa_oc_cashapp_amount    DECIMAL(10,2) DEFAULT 0.00,
			aaa_oc_creditcard_amount DECIMAL(10,2) DEFAULT 0.00,
			aaa_oc_epayment_total    DECIMAL(10,2) DEFAULT 0.00,
			epayment_tip         DECIMAL(10,2) DEFAULT 0.00,
			total_order_tip      DECIMAL(10,2) DEFAULT 0.00,
			aaa_oc_tip_total     DECIMAL(10,2) DEFAULT 0.00,
			aaa_oc_payrec_total  DECIMAL(10,2) DEFAULT 0.00,
			aaa_oc_order_balance DECIMAL(10,2) DEFAULT NULL,
			aaa_oc_order_total   DECIMAL(10,2) DEFAULT 0.00,
			subtotal             DECIMAL(10,2) DEFAULT 0.00,
			processing_fee       DECIMAL(10,2) DEFAULT 0.00,
			original_payment_method VARCHAR(100) NULL,
			real_payment_method     VARCHAR(100) NULL,
			aaa_oc_payment_status   VARCHAR(20)  NOT NULL DEFAULT 'unpaid',
			driver_id               BIGINT UNSIGNED NULL,
			envelope_id             VARCHAR(100) NULL,
			route_id                VARCHAR(100) NULL,
			cleared                 TINYINT(1) NOT NULL DEFAULT 0,
			envelope_outstanding    TINYINT(1) NOT NULL DEFAULT 0,
			wc_transaction_id       VARCHAR(100) NULL,
			gateway_transaction_id  VARCHAR(100) NULL,
			epayment_detail     TEXT NULL,
			payment_admin_notes TEXT NULL,
			notes_summary       TEXT NULL,
			change_log_id       BIGINT NULL,
			last_payment_at     DATETIME NULL,
			last_updated    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			last_updated_by VARCHAR(100) NULL,
			PRIMARY KEY (id),
			UNIQUE KEY uq_order (order_id),
			KEY idx_status (aaa_oc_payment_status),
			KEY idx_method (real_payment_method),
			KEY idx_driver (driver_id),
			KEY idx_env (envelope_outstanding),
			KEY idx_cleared (cleared),
			KEY idx_lastpay (last_payment_at),
			KEY idx_wc_txn (wc_transaction_id),
			KEY idx_gateway_txn (gateway_transaction_id),
			KEY idx_updated (last_updated)
		) ENGINE=InnoDB {$charset};";

		$sql2 = "CREATE TABLE {$log_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id BIGINT UNSIGNED NOT NULL,
			changes    TEXT NOT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			created_by VARCHAR(100) NOT NULL,
			type ENUM('manual','system','driver') DEFAULT 'manual',
			PRIMARY KEY (id),
			KEY idx_order (order_id),
			KEY idx_created (created_at),
			KEY idx_type (type)
		) ENGINE=InnoDB {$charset};";

		self::run_dbdelta( $sql1 );
		self::run_dbdelta( $sql2 );

		if ( self::DEBUG_THIS_FILE ) {
			$msg = "[PaymentSetup][Installer] ensured: {$payment_table}, {$log_table}";
			if ( function_exists('aaa_oc_log') ) { aaa_oc_log($msg); } else { error_log($msg); }
		}
	}

	public static function maybe_install() {
		self::install();
	}
}
