<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/index/class-aaa-paim-table-installer.php
 * Purpose: Create PAIM tables for attribute sets, items, and product AI flags.
 * Version: 0.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'AAA_PAIM_DEBUG_INSTALLER' ) ) { define( 'AAA_PAIM_DEBUG_INSTALLER', true ); }

class AAA_Paim_Table_Installer {
	const DB_VERSION = '0.2.0';

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$sets    = $wpdb->prefix . 'aaa_paim_attribute_sets';
		$attrs   = $wpdb->prefix . 'aaa_paim_set_attributes';
		$flags   = $wpdb->prefix . 'aaa_paim_product_ai_flags';

		$sql_sets = "CREATE TABLE {$sets} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			set_name VARCHAR(190) NOT NULL,
			category_term_id BIGINT UNSIGNED NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			notes TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY category_term_id (category_term_id),
			KEY status (status)
		) {$charset};";

		$sql_attrs = "CREATE TABLE {$attrs} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			set_id BIGINT UNSIGNED NOT NULL,
			object_type VARCHAR(20) NOT NULL,
			object_key VARCHAR(190) NOT NULL,
			label VARCHAR(190) NULL,
			ui_order INT NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY set_id (set_id),
			KEY object_type (object_type),
			KEY object_key (object_key)
		) {$charset};";

		$sql_flags = "CREATE TABLE {$flags} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			product_id BIGINT UNSIGNED NOT NULL,
			set_id BIGINT UNSIGNED NOT NULL,
			object_type VARCHAR(20) NOT NULL,
			object_key VARCHAR(190) NOT NULL,
			ai_requested TINYINT(1) NOT NULL DEFAULT 0,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY uniq (product_id, set_id, object_type, object_key),
			KEY product_id (product_id),
			KEY set_id (set_id)
		) {$charset};";

		dbDelta( $sql_sets );
		dbDelta( $sql_attrs );
		dbDelta( $sql_flags );

		update_option( 'aaa_paim_db_version', self::DB_VERSION );
		aaa_paim_log( 'Tables installed/updated v0.2.0', 'INSTALLER' );
	}
}
