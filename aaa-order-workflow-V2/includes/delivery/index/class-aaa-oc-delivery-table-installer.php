<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/delivery/index/class-aaa-oc-delivery-table-installer.php
 * Purpose: Create/upgrade Delivery table (aaa_oc_delivery) to persist delivery-specific data
 *          collected during/after checkout (address snapshot, coords, driver, schedule flags, etc.).
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class AAA_OC_Delivery_Table_Installer {

    const SCHEMA_VERSION = '1.0.0';

    public static function maybe_install() : void {
        global $wpdb;
        $table   = $wpdb->prefix . 'aaa_oc_delivery';
        $opt_key = 'aaa_oc_delivery_schema';

        $installed = get_option( $opt_key );
        if ( $installed === self::SCHEMA_VERSION && self::table_exists( $table ) ) {
            return;
        }
        self::install();
        if ( self::table_exists( $table ) ) {
            update_option( $opt_key, self::SCHEMA_VERSION, true );
        }
    }

    public static function install() : void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table   = $wpdb->prefix . 'aaa_oc_delivery';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT(20) UNSIGNED NOT NULL,

            -- delivery address snapshot (from checkout at the time of delivery selection)
            address_1 VARCHAR(255) DEFAULT '',
            address_2 VARCHAR(255) DEFAULT '',
            city VARCHAR(100) DEFAULT '',
            state VARCHAR(100) DEFAULT '',
            postcode VARCHAR(40) DEFAULT '',
            country VARCHAR(2) DEFAULT '',

            -- coordinates and travel metrics (from SZBD / other sources)
            latitude DECIMAL(10,6) DEFAULT NULL,
            longitude DECIMAL(10,6) DEFAULT NULL,
            travel_time_seconds INT DEFAULT NULL,
            travel_distance_meters INT DEFAULT NULL,

            -- scheduling data (multiple representations to avoid re-parsing)
            delivery_mode VARCHAR(40) DEFAULT '',         -- e.g. 'delivery', 'pickup'
            is_scheduled TINYINT(1) DEFAULT 0,
            is_same_day TINYINT(1) DEFAULT 0,
            is_asap TINYINT(1) DEFAULT 0,

            delivery_date_ts BIGINT(20) DEFAULT NULL,     -- UTC ts from picker/bridge
            delivery_date_ymd DATE DEFAULT NULL,          -- 'YYYY-MM-DD'
            delivery_date_locale VARCHAR(255) DEFAULT NULL,-- 'October 29, 2025'

            delivery_time VARCHAR(40) DEFAULT NULL,       -- '11:00 am'
            delivery_time_range VARCHAR(100) DEFAULT NULL,-- 'From 11:00 am to 11:45 am'

            -- shipping fee at time of checkout (delivery fee)
            delivery_fee DECIMAL(10,2) DEFAULT NULL,

            -- driver assignment (official)
            driver_id BIGINT(20) DEFAULT NULL,

            -- provenance
            source VARCHAR(50) DEFAULT NULL,              -- e.g. 'tpfw', 'bridge', 'manual'
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            UNIQUE KEY uq_order_id (order_id),
            KEY idx_driver (driver_id),
            KEY idx_date (delivery_date_ymd),
            KEY idx_ts (delivery_date_ts)
        ) ENGINE=InnoDB {$charset};";

        dbDelta( $sql );
    }

    private static function table_exists( string $table ) : bool {
        global $wpdb;
        $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
        return ( $exists === $table );
    }
}
