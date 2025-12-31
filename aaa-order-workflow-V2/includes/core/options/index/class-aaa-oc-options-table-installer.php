<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/options/index/class-aaa-oc-options-table-installer.php
 * Purpose: Creates and maintains the aaa_oc_options table for plugin settings.
 * Style:   dbDelta-safe CREATE TABLE; consistent logging; one-per-request guard. Includes SQL sanitizer.
 * Version: 1.2.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** Local per-file debug toggle (can be overridden earlier) */
if ( ! defined( 'AAA_OC_OPTIONS_DEBUG' ) ) {
	define( 'AAA_OC_OPTIONS_DEBUG', false );
}

class AAA_OC_Options_Table_Installer {

	const TABLE = 'aaa_oc_options';

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

	/**
	 * Check and create the table if it doesn't exist.
	 */
	public static function maybe_install() {
		if ( self::$did ) return;
		self::$did = true;

		global $wpdb;

		$table   = $wpdb->prefix . self::TABLE;
		$charset = $wpdb->get_charset_collate();

		// dbDelta requires consistent formatting, no inline comments inside SQL.
		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			option_key VARCHAR(191) NOT NULL,
			option_value LONGTEXT NULL,
			scope VARCHAR(50) DEFAULT 'global',
			autoload TINYINT(1) DEFAULT 0,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY option_scope (option_key, scope)
		) ENGINE=InnoDB {$charset};";

		self::run_dbdelta( $sql );

		if ( AAA_OC_OPTIONS_DEBUG ) {
			$msg = '[AAA_OC_Options_Table_Installer] ensured: ' . $table;
			if ( function_exists('aaa_oc_log') ) { aaa_oc_log($msg); } else { error_log($msg); }
		}
	}
}

if ( AAA_OC_OPTIONS_DEBUG ) {
	if ( function_exists('aaa_oc_log') ) { aaa_oc_log('[AAA_OC_Options_Table_Installer] Loaded'); }
	else { error_log('[AAA_OC_Options_Table_Installer] Loaded'); }
}
