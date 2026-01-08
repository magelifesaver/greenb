<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forcast/index/class-aaa-oc-product-forcast-table-installer.php
 * Purpose: Handles the creation and update of the product forcast table.
 * Version: 0.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Class AAA_OC_Product_Forcast_Table_Installer
 *
 * Responsible for installing and updating the `aaa_oc_product_forcast` table.
 */
class AAA_OC_Product_Forcast_Table_Installer {

    /**
     * Bootstraps installation hooks.
     */
    public static function init() {
        add_action( 'plugins_loaded', [ __CLASS__, 'maybe_install_table' ], 1 );
    }

    /**
     * Checks and installs/updates the custom table as needed.
     */
    public static function maybe_install_table() {
        global $wpdb;
        $table_name = AAA_OC_PRODUCT_FORCAST_TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        // Example SQL; adapt fields as necessary when implementing.
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            min_qty BIGINT(20) UNSIGNED DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY product_id (product_id)
        ) $charset_collate;";

        // TODO: Use dbDelta() to execute the SQL when implementing.
    }
}