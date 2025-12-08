<?php
/**
 * File: /index/class-aaa-afci-table-installer.php
 * Purpose: Create/upgrade AFCI tables (sessions + event details).
 * Version: 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_AFCI_Table_Installer {

	public static function install() {
		global $wpdb;

		$table   = $wpdb->prefix . 'aaa_checkout_sessions';
		$charset = $wpdb->get_charset_collate();

		// Main sessions/events table (one row per event or aggregated event)
		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_key VARCHAR(64) NOT NULL,
			user_id BIGINT UNSIGNED DEFAULT 0,
			event_type VARCHAR(50) DEFAULT 'summary',
			event_key VARCHAR(190) NULL,
			event_payload LONGTEXT NULL,
			ip_address VARCHAR(64) NULL,
			user_agent TEXT NULL,
			status VARCHAR(30) DEFAULT 'active',
			repeat_count INT UNSIGNED NOT NULL DEFAULT 1,
			order_id BIGINT UNSIGNED DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY session_key (session_key),
			KEY user_id (user_id),
			KEY event_type (event_type),
			KEY event_key (event_key),
			KEY created_at (created_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Ensure companion detail table exists
		if ( ! class_exists( 'AAA_AFCI_Detail_Manager' ) ) {
			if ( defined( 'AAA_FCI_PATH' ) ) {
				$detail_file = trailingslashit( AAA_FCI_PATH ) . 'index/class-aaa-afci-detail-manager.php';
				if ( file_exists( $detail_file ) ) {
					require_once $detail_file;
				}
			}
		}
		if ( class_exists( 'AAA_AFCI_Detail_Manager' ) ) {
			AAA_AFCI_Detail_Manager::install();
		}

		if ( function_exists( 'aaa_fci_debug_log' ) ) {
			aaa_fci_debug_log( 'Tables ensured', [ 'table' => $table ] );
		}
		error_log('[AFCI] Tables ensured: ' . $table . ' + details');
	}
}

register_activation_hook(
	dirname(__DIR__) . '/aaa-frontend-checkout-intervention.php',
	[ 'AAA_AFCI_Table_Installer', 'install' ]
);
