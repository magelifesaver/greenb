<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/index/class-aaa-oc-forecast-queue-installer.php
 * Purpose: Creates and updates the queue tables used by the forecast module.
 *          There are two queues: one for forecast jobs and another for
 *          purchase order preparations. Each queue holds lightweight rows
 *          referencing product IDs and tracks status, user and timestamps.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Queue_Installer {

    /**
     * Hook into plugins_loaded early to ensure the queue tables exist.
     */
    public static function init(): void {
        add_action( 'plugins_loaded', [ __CLASS__, 'maybe_install_tables' ], 1 );
    }

    /**
     * Creates or upgrades the forecast and PO queue tables via dbDelta().
     */
    public static function maybe_install_tables(): void {
        global $wpdb;
        $forecast_table = AAA_OC_FORECAST_QUEUE_TABLE;
        $po_table       = AAA_OC_FORECAST_PO_QUEUE_TABLE;
        $charset        = $wpdb->get_charset_collate();

        // Forecast queue: holds products awaiting recalculation. The status
        // column can be pending, processing or done. Attempts counts retries.
        $forecast_sql = "CREATE TABLE {$forecast_table} (\n" .
            "id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
            "product_id BIGINT(20) UNSIGNED NOT NULL,\n" .
            "status VARCHAR(20) NOT NULL DEFAULT 'pending',\n" .
            "user_id BIGINT(20) UNSIGNED NULL,\n" .
            "attempts INT UNSIGNED NOT NULL DEFAULT 0,\n" .
            "created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
            "updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n" .
            "PRIMARY KEY (id),\n" .
            "KEY product_id (product_id),\n" .
            "KEY status (status)\n" .
            ") {$charset};";

        // PO queue: holds products a user plans to include on a purchase order.
        $po_sql = "CREATE TABLE {$po_table} (\n" .
            "id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
            "product_id BIGINT(20) UNSIGNED NOT NULL,\n" .
            "quantity BIGINT(20) UNSIGNED NOT NULL DEFAULT 1,\n" .
            "price DECIMAL(20,4) NULL,\n" .
            "status VARCHAR(20) NOT NULL DEFAULT 'pending',\n" .
            "user_id BIGINT(20) UNSIGNED NULL,\n" .
            "created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
            "updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n" .
            "PRIMARY KEY (id),\n" .
            "KEY product_id (product_id),\n" .
            "KEY status (status)\n" .
            ") {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $forecast_sql );
        dbDelta( $po_sql );
    }
}
