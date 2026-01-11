<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forcast/index/class-aaa-oc-product-forcast-table-installer.php
 * Purpose: Create or update the product forecast table. This installer ensures
 *          the `aaa_oc_product_forcast` table exists using dbDelta. It
 *          registers hooks on `admin_init` and `aaa_oc_module_install` so the
 *          table can be created on demand or during plugin updates.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AAA_OC_Product_Forcast_Table_Installer
 *
 * Handles installation and upgrades of the forecast table. Mirrored after
 * other AAA Order Workflow installers (e.g., ProductSearch) to ensure
 * consistency and safe, idempotent execution.
 */
class AAA_OC_Product_Forcast_Table_Installer {

    /**
     * Guards against multiple installs in the same request.
     *
     * @var bool
     */
    private static $did = false;

    /**
     * Register hooks for installation on admin_init and module install events.
     * Should be called during plugins_loaded to wire the installer.
     */
    public static function init() : void {
        add_action( 'admin_init', [ __CLASS__, 'maybe_install' ], 5 );
        add_action( 'aaa_oc_module_install', [ __CLASS__, 'install' ], 10 );
    }

    /**
     * Ensure the forecast table exists. This method is a thin wrapper around
     * install() to mirror upstream installers which expose maybe_install().
     */
    public static function maybe_install() : void {
        self::install();
    }

    /**
     * Create or update the forecast table. Uses dbDelta under the hood so
     * repeated calls are safe. Early returns if already run in this request.
     */
    public static function install() : void {
        if ( self::$did ) {
            return;
        }
        self::$did = true;

        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table          = AAA_OC_PRODUCT_FORCAST_TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        // Core table structure: id, product reference, quantity, supplier reference and timestamp.
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            qty BIGINT UNSIGNED NOT NULL DEFAULT 1,
            supplier_id BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_product_id (product_id),
            KEY idx_supplier_id (supplier_id)
        ) ENGINE=InnoDB {$charset_collate};";

        dbDelta( $sql );

        // Optional debug logging.
        if ( defined( 'AAA_OC_FORCAST_DEBUG' ) && AAA_OC_FORCAST_DEBUG ) {
            $msg = '[FORCAST][INSTALLER] ensured forecast table: ' . $table;
            if ( function_exists( 'aaa_oc_log' ) ) {
                aaa_oc_log( $msg );
            } else {
                error_log( $msg );
            }
        }
    }
}