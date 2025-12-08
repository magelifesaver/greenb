<?php
/**
 * File: /wp-content/plugins/aaa-openia-order-creation-v4/includes/class-aaa-v4-parser-table.php
 * Purpose: Install/upgrade the parser index table without dbDelta key conflicts.
 * Version: 1.1.0 (fix PRIMARY KEY & UNIQUE definitions for dbDelta)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_V4_Parser_Table {

    const TABLE = 'aaa_wf_v4_parser_index';

    public static function create_table() {
        global $wpdb;

        $table            = $wpdb->prefix . self::TABLE;
        $charset_collate  = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Important for dbDelta:
        // - Do NOT declare "PRIMARY KEY" inline on the column.
        // - Declare keys at the bottom as "PRIMARY KEY (...)" and "UNIQUE/KEY ...".
        // This prevents dbDelta from issuing ALTERs that re-add a primary key.
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT,
            wm_id VARCHAR(50),
            wm_external_id VARCHAR(50) NOT NULL,
            wm_product_id VARCHAR(50),
            wm_og_name TEXT,
            wm_og_slug TEXT,
            wm_og_body TEXT,
            wm_category_raw TEXT,
            wm_og_brand_id TEXT,
            wm_og_brand_name TEXT,
            wm_strain_id VARCHAR(50),
            wm_genetics VARCHAR(100),
            wm_thc_percentage VARCHAR(20),
            wm_cbd_percentage VARCHAR(20),
            wm_license_type VARCHAR(50),
            wm_price_currency VARCHAR(10),
            wm_unit_price DECIMAL(10,2),
            wm_sale_price DECIMAL(10,2),
            wm_discount_type VARCHAR(20),
            wm_discount_value VARCHAR(20),
            wm_online_orderable TINYINT(1),
            wm_published TINYINT(1),
            wm_created_at DATETIME,
            wm_updated_at DATETIME,
            was_created TINYINT(1) DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY wm_external_id (wm_external_id)
        ) {$charset_collate};";

        dbDelta( $sql );
    }
}
