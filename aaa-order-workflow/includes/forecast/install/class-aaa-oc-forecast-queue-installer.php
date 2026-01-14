<?php
/**
 * Forecast and PO queue table installer.
 *
 * Handles the creation and upgrading of the database tables used by
 * the forecast and PO queues. WordPress's dbDelta function is used
 * to perform incremental schema migrations in a safe manner. This
 * installer can be run multiple times without harming existing data.
 *
 * @package AAA_Order_Workflow
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AAA_OC_Forecast_Queue_Installer
 *
 * Provides static methods for creating the forecast and PO queue tables.
 */
class AAA_OC_Forecast_Queue_Installer {
    /**
     * Entry point used by the plugin activation hook. Simply calls
     * maybe_install_tables(). Keeping this separate allows manual
     * invocation without passing activation context.
     */
    public static function install(): void {
        self::maybe_install_tables();
    }

    /**
     * Create or update the forecast and PO queue tables. dbDelta
     * compares the existing schema to the provided SQL and performs
     * incremental changes as needed.
     */
    public static function maybe_install_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();
        $table1          = AAA_OC_FORECAST_QUEUE_TABLE;
        $table2          = AAA_OC_FORECAST_PO_QUEUE_TABLE;
        // Forecast queue table schema. Tracks product processing for the
        // forecast runner. Attempts and user_id are optional to support
        // historic installations where those columns did not exist.
        $sql1 = "CREATE TABLE {$table1} (
            id bigint(20) unsigned NOT NULL auto_increment,
            product_id bigint(20) unsigned NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            attempts int(11) NOT NULL DEFAULT 0,
            user_id bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY product_status (product_id,status)
        ) {$charset_collate};";
        // PO queue table schema. Tracks purchase order creation and
        // includes a quantity column. Attempts and user_id mirror the
        // forecast table design.
        $sql2 = "CREATE TABLE {$table2} (
            id bigint(20) unsigned NOT NULL auto_increment,
            product_id bigint(20) unsigned NOT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            status varchar(20) NOT NULL DEFAULT 'pending',
            attempts int(11) NOT NULL DEFAULT 0,
            user_id bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY product_status (product_id,status)
        ) {$charset_collate};";
        dbDelta( $sql1 );
        dbDelta( $sql2 );
    }
}