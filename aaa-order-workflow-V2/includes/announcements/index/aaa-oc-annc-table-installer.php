<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/announcements/index/aaa-oc-annc-table-installer.php
 * Purpose: Create/upgrade tables for workflow announcements + user acknowledgements (dbDelta-safe).
 * Style:   dbDelta-safe; InnoDB; WFCP-driven debug; one-per-request guard.
 * Version: 1.3.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_OC_Announcements_Table_Installer {

	/** One-per-request guard */
	private static $did = false;

	/** WFCP-driven debug (modules scope → key: annc_debug) */
	private static function debug_on() : bool {
		if ( function_exists('aaa_oc_get_option') ) {
			return (bool) aaa_oc_get_option( 'annc_debug', 'modules', 0 );
		}
		return false;
	}

	public static function maybe_install() : void {
		if ( self::$did ) return;
		self::$did = true;

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$ann_tbl = $wpdb->prefix . 'aaa_oc_announcements';
		$usr_tbl = $wpdb->prefix . 'aaa_oc_announcement_user';

		$sql1 = "CREATE TABLE {$ann_tbl} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title VARCHAR(255) NOT NULL,
			content LONGTEXT NOT NULL,
			start_at DATETIME DEFAULT NULL,
			end_at DATETIME DEFAULT NULL,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			created_by BIGINT UNSIGNED DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_active (is_active),
			KEY idx_start (start_at),
			KEY idx_end (end_at),
			KEY idx_created_by (created_by)
		) ENGINE=InnoDB {$charset};";

		$sql2 = "CREATE TABLE {$usr_tbl} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			announcement_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			seen_at DATETIME DEFAULT NULL,
			accepted_at DATETIME DEFAULT NULL,
			accepted TINYINT(1) NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_announcement_user (announcement_id, user_id),
			KEY idx_user (user_id),
			KEY idx_accept (accepted)
		) ENGINE=InnoDB {$charset};";

		dbDelta( $sql1 );
		dbDelta( $sql2 );

		if ( self::debug_on() ) {
			$msg = "[ANN][Installer] ensured: {$ann_tbl}, {$usr_tbl}";
			if ( function_exists('aaa_oc_log') ) { aaa_oc_log($msg); } else { error_log($msg); }
		}
	}
}
