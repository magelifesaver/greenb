<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/fulfillment/index/class-aaa-oc-fulfillment-table-installer.php
 * Purpose: Create/upgrade the fulfillment logs table (aaa_oc_fulfillment_logs) with dbDelta.
 * Style:   dbDelta-safe, InnoDB, WFCP-driven debug, one-per-request guard.
 * Version: 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_OC_Fulfillment_Table_Installer {

	/** One-per-request guard */
	private static $did = false;

	/** WFCP-driven debug (modules scope → key: fulfillment_debug) */
	private static function debug_on() : bool {
		if ( function_exists('aaa_oc_get_option') ) {
			return (bool) aaa_oc_get_option( 'fulfillment_debug', 'modules', 0 );
		}
		return false;
	}

	/**
	 * Ensure the fulfillment logs table exists.
	 */
	public static function create_table() {
		if ( self::$did ) return;
		self::$did = true;

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = $wpdb->prefix . 'aaa_oc_fulfillment_logs';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			scanned_count INT NOT NULL DEFAULT 0,
			manual_count INT NOT NULL DEFAULT 0,
			fulfillment_status VARCHAR(32) NOT NULL DEFAULT 'not_picked',
			picked_json LONGTEXT NULL,
			started_at DATETIME NULL,
			completed_at DATETIME NULL,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			notes TEXT DEFAULT NULL,
			PRIMARY KEY (id),
			KEY idx_order (order_id),
			KEY idx_user (user_id),
			KEY idx_status (fulfillment_status)
		) ENGINE=InnoDB {$charset};";

		dbDelta( $sql );

		if ( self::debug_on() ) {
			$msg = '[AAA_OC_Fulfillment_Table_Installer] ensured: ' . $table;
			if ( function_exists('aaa_oc_log') ) { aaa_oc_log($msg); } else { error_log($msg); }
		}
	}
}
