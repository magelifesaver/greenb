<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/index/class-aaa-oc-payconfirm-table-installer.php
 * Purpose: Create inbox table used by PayConfirm/WFPAY flows.
 * Style:   dbDelta-safe; InnoDB; guarded to avoid multiple runs per request; consistent logging.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_OC_PayConfirm_Table_Installer {

	/** Per-file debug toggle */
	private const DEBUG_THIS_FILE = false;

	/** One-per-request guard */
	private static $did = false;

	public static function install() : void {
		if ( self::$did ) return;
		self::$did = true;

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$table   = $wpdb->prefix . 'aaa_oc_payconfirm_inbox';

		// dbDelta requires consistent formatting and no inline comments inside SQL.
		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			email_id VARCHAR(191) DEFAULT NULL,
			amount DECIMAL(12,2) DEFAULT NULL,
			txn_id VARCHAR(191) DEFAULT NULL,
			payer_name VARCHAR(191) DEFAULT NULL,
			memo TEXT NULL,
			payment_method VARCHAR(50) DEFAULT NULL,
			payment_date DATETIME NULL,
			matched_order_id BIGINT UNSIGNED DEFAULT NULL,
			match_confidence TINYINT UNSIGNED DEFAULT 0,
			status VARCHAR(32) DEFAULT 'new',
			raw MEDIUMTEXT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_email_id (email_id),
			KEY idx_txn_id (txn_id),
			KEY idx_matched_order_id (matched_order_id),
			KEY idx_status (status)
		) ENGINE=InnoDB {$charset};";

		dbDelta( $sql );

		if ( self::DEBUG_THIS_FILE ) {
			$msg = "[PayConfirm][Installer] ensured: {$table}";
			if ( function_exists('aaa_oc_log') ) { aaa_oc_log($msg); } else { error_log($msg); }
		}
	}

	public static function maybe_install() : void {
		self::install();
	}
}
