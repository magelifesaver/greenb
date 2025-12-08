<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/index/class-aaa-paim-ai-flag-table-installer.php
 * Purpose: Create/maintain processing run tables (PAIM): aaa_paim_runs + aaa_paim_run_items.
 * Version: 1.0.2
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! defined( 'AAA_PAIM_AIFLAG_DEBUG' ) ) {
	define( 'AAA_PAIM_AIFLAG_DEBUG', true );
}

if ( ! class_exists( 'AAA_PAIM_AI_Flag_Table_Installer' ) ) :
class AAA_PAIM_AI_Flag_Table_Installer {

	const OPT_KEY = 'aaa_paim_aiflag_db_version';
	const VER     = '1.0.2';

	public static function maybe_install() {
		$installed = get_site_option( self::OPT_KEY );
		if ( $installed === self::VER ) {
			return;
		}
		self::install();
	}

	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset   = $wpdb->get_charset_collate();
		$tbl_runs  = $wpdb->prefix . 'aaa_paim_runs';
		$tbl_items = $wpdb->prefix . 'aaa_paim_run_items';

		$sql_runs = "CREATE TABLE {$tbl_runs} (
			run_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			attribute_set_id BIGINT UNSIGNED NOT NULL,
			category_term_id BIGINT UNSIGNED NOT NULL,
			status VARCHAR(16) NOT NULL DEFAULT 'queued',
			requested_by BIGINT UNSIGNED NOT NULL,
			requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			started_at DATETIME NULL,
			finished_at DATETIME NULL,
			total_products INT UNSIGNED NOT NULL DEFAULT 0,
			processed_ok INT UNSIGNED NOT NULL DEFAULT 0,
			processed_err INT UNSIGNED NOT NULL DEFAULT 0,
			ai_model_used VARCHAR(64) NULL,
			source_used VARCHAR(64) NULL,
			dry_run TINYINT(1) NOT NULL DEFAULT 1,
			notes TEXT NULL,
			PRIMARY KEY  (run_id),
			KEY status_idx (status),
			KEY set_idx (attribute_set_id),
			KEY cat_idx (category_term_id),
			KEY requested_at_idx (requested_at)
		) {$charset};";

		$sql_items = "CREATE TABLE {$tbl_items} (
			item_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			run_id BIGINT UNSIGNED NOT NULL,
			product_id BIGINT UNSIGNED NOT NULL,
			action_flags SET('attrs_updated','desc_updated','short_desc_updated','skipped_no_match','error') NOT NULL,
			changes_json LONGTEXT NULL,
			error_message TEXT NULL,
			processed_at DATETIME NULL,
			PRIMARY KEY  (item_id),
			KEY run_idx (run_id),
			KEY product_idx (product_id)
		) {$charset};";

		dbDelta( $sql_runs );
		dbDelta( $sql_items );

		// Sanity check & log
		$have_runs  = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $tbl_runs ) ) === $tbl_runs;
		$have_items = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $tbl_items ) ) === $tbl_items;

		if ( $have_runs && $have_items ) {
			update_site_option( self::OPT_KEY, self::VER );
			if ( AAA_PAIM_AIFLAG_DEBUG && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "[PAIM][AIFlagInstaller] ensured {$tbl_runs} & {$tbl_items} (v" . self::VER . ")" );
			}
		} else {
			if ( AAA_PAIM_AIFLAG_DEBUG && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "[PAIM][AIFlagInstaller] dbDelta issue: runs=" . ($have_runs?'yes':'no') . " items=" . ($have_items?'yes':'no') . " last_error=" . $wpdb->last_error );
			}
		}
	}
}
add_action( 'plugins_loaded', ['AAA_PAIM_AI_Flag_Table_Installer', 'maybe_install' ], 20 );
endif;
