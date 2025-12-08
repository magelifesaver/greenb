<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/options/index/class-aaa-oc-options-table-installer.php
 * Purpose: Creates and maintains the aaa_oc_options table for plugin settings.
 * Version: 1.1.0 (adds local debug toggle)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Local per-file debug toggle.
 * Define globally before including this file to override (e.g. define('AAA_OC_OPTIONS_DEBUG', true)).
 */
if ( ! defined( 'AAA_OC_OPTIONS_DEBUG' ) ) {
	define( 'AAA_OC_OPTIONS_DEBUG', false );
}

class AAA_OC_Options_Table_Installer {

	const TABLE = 'aaa_oc_options';

	/**
	 * Check and create the table if it doesn't exist.
	 */
	public static function maybe_install() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		$charset_collate = $wpdb->get_charset_collate();
		$sql = "
		CREATE TABLE IF NOT EXISTS $table (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			option_key VARCHAR(191) NOT NULL,
			option_value LONGTEXT NULL,
			scope VARCHAR(50) DEFAULT 'global',
			autoload TINYINT(1) DEFAULT 0,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY option_scope (option_key, scope)
		) $charset_collate;
		";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		if ( AAA_OC_OPTIONS_DEBUG ) {
			error_log( '[AAA_OC_Options_Table_Installer] Table checked/created: ' . $table );
		}
	}
}

if ( AAA_OC_OPTIONS_DEBUG ) {
	error_log( '[AAA_OC_Options_Table_Installer] Loaded.' );
}
