<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/productsearch/index/class-aaa-oc-productsearch-table-installer.php
 * Purpose: Create or upgrade tables for the ProductSearch module (searchable
 *          index plus synonyms). This class mirrors the upstream installer
 *          used in versions 1.3.x of the AAA Order Workflow plugin. It
 *          exposes the same static API (`install()` and `maybe_install()`)
 *          used by the loader and other modules.
 *
 * Version: 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_ProductSearch_Table_Installer {
    // Table suffixes (prefixed by $wpdb->prefix at runtime).
    const T_INDEX    = 'aaa_oc_productsearch_index';
    const T_SYNONYMS = 'aaa_oc_productsearch_synonyms';

    /** One-per-request guard */
    private static $did = false;

    /**
     * Toggle debug output based on the productsearch_debug option. When
     * enabled messages will be sent to aaa_oc_log() or error_log().
     */
    private static function debug_on() : bool {
        if ( function_exists( 'aaa_oc_get_option' ) ) {
            return (bool) aaa_oc_get_option( 'productsearch_debug', 'modules', 0 );
        }
        return false;
    }

    /**
     * Install or update ProductSearch tables (dbDelta safe). Calling this
     * method multiple times in the same request has no effect thanks to
     * the `$did` guard.
     */
    public static function install() : void {
        if ( self::$did ) {
            return;
        }
        self::$did = true;

        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $index_table = $wpdb->prefix . self::T_INDEX;
        $syn_table   = $wpdb->prefix . self::T_SYNONYMS;

        // Searchable Product Index – display-ready (pricing, image, slug).
        $sql1 = "CREATE TABLE {$index_table} (
                product_id     BIGINT UNSIGNED NOT NULL,
                in_stock       TINYINT(1) NOT NULL DEFAULT 0,
                title          VARCHAR(255) NOT NULL DEFAULT '',
                title_norm     TEXT NULL,
                brand_term_id  BIGINT UNSIGNED NULL,
                brand_slug     VARCHAR(200) NULL,
                brand_name     VARCHAR(200) NULL,
                cat_term_ids   TEXT NULL,
                cat_slugs      TEXT NULL,
                sku            VARCHAR(100) NULL,
                price_regular  DECIMAL(18,6) NULL,
                price_sale     DECIMAL(18,6) NULL,
                price_active   DECIMAL(18,6) NULL,
                product_slug   VARCHAR(200) NULL,
                image_url      VARCHAR(255) NULL,
                updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (product_id),
                KEY idx_in_stock (in_stock),
                KEY idx_brand_term (brand_term_id),
                KEY idx_brand_slug (brand_slug(50))
        ) ENGINE=InnoDB {$charset_collate};";

        // Synonyms Table
        $sql2 = "CREATE TABLE {$syn_table} (
                id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                scope       ENUM('brand','category','global') NOT NULL DEFAULT 'global',
                term_id     BIGINT UNSIGNED NULL,
                synonym     VARCHAR(190) NOT NULL,
                bidi        TINYINT(1) NOT NULL DEFAULT 0,
                active      TINYINT(1) NOT NULL DEFAULT 1,
                updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_scope_term (scope, term_id),
                KEY idx_synonym (synonym)
        ) ENGINE=InnoDB {$charset_collate};";

        dbDelta( $sql1 );
        dbDelta( $sql2 );

        if ( self::debug_on() ) {
            $msg = "[ProductSearch][Installer] ensured: {$index_table}, {$syn_table}";
            if ( function_exists( 'aaa_oc_log' ) ) {
                aaa_oc_log( $msg );
            } else {
                error_log( $msg );
            }
        }
    }

    /**
     * Backwards compatible shim that simply calls install().
     */
    public static function maybe_install() : void {
        self::install();
    }
}