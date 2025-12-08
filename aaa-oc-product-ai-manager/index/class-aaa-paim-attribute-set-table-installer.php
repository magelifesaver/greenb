<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/index/class-aaa-paim-attribute-set-table-installer.php
 * Purpose: Create/maintain the Attribute Set table for Product Attribute Manager (PAIM).
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Per-file debug toggle.
 * Enable with: define('AAA_PAIM_ATTRSET_DEBUG', true);
 */
if ( ! defined( 'AAA_PAIM_ATTRSET_DEBUG' ) ) {
	define( 'AAA_PAIM_ATTRSET_DEBUG', false );
}

if ( ! class_exists( 'AAA_PAIM_Attribute_Set_Table_Installer' ) ) :

class AAA_PAIM_Attribute_Set_Table_Installer {

	const OPT_KEY = 'aaa_paim_attrset_db_version';
	const VER     = '1.0.0';

	public static function maybe_install() {
		$installed = get_site_option( self::OPT_KEY );
		if ( $installed === self::VER ) return;
		self::install();
	}

	private static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$tbl     = $wpdb->prefix . 'aaa_paim_attribute_sets';

		$sql = "CREATE TABLE {$tbl} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(190) NOT NULL,
			category_term_id BIGINT UNSIGNED NOT NULL,
			settings_json LONGTEXT NULL,
			desc_template LONGTEXT NULL,
			short_desc_template LONGTEXT NULL,
			system_prompt LONGTEXT NULL,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY cat_idx (category_term_id),
			KEY name_idx (name)
		) {$charset};";

		dbDelta( $sql );
		update_site_option( self::OPT_KEY, self::VER );

		if ( AAA_PAIM_ATTRSET_DEBUG ) {
			error_log('[PAIM][AttrSetInstaller] ensured table aaa_paim_attribute_sets v' . self::VER);
		}
	}
}

add_action( 'plugins_loaded', ['AAA_PAIM_Attribute_Set_Table_Installer', 'maybe_install'], 20 );

endif;
