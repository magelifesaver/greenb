<?php
/**
 * FilePath: /aaa-order-workflow/includes/fulfillment/index/class-aaa-oc-fulfillment-table-installer.php
 *
 * Purpose:
 *   Creates the fulfillment logs table (aaa_oc_fulfillment_logs).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Fulfillment_Table_Installer {

    /**
     * Ensure the fulfillment logs table exists.
     */
    public static function create_table() {
        global $wpdb;
        $table   = $wpdb->get_blog_prefix() . 'aaa_oc_fulfillment_logs';
        $charset = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            scanned_count INT NOT NULL DEFAULT 0,
            manual_count INT NOT NULL DEFAULT 0,
            fulfillment_status VARCHAR(32) NOT NULL DEFAULT 'not_picked',
            picked_json LONGTEXT NULL,
            started_at DATETIME NULL,
            completed_at DATETIME NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            notes TEXT DEFAULT NULL,
            KEY order_id (order_id),
            KEY user_id (user_id),
            KEY fulfillment_status (fulfillment_status)
        ) ENGINE=InnoDB {$charset};";

        dbDelta( $sql );
    }
}
