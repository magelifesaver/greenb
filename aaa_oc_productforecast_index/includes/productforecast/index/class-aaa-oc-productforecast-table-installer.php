<?php
/**
 * File: /wp-content/plugins/aaa_oc_product_forecast_index/includes/productforecast/index/class-aaa-oc-productforecast-table-installer.php
 * Purpose: Create/upgrade tables for ProductForecast Index (index table + optional log table).
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_ProductForecast_Table_Installer {

    const T_INDEX = 'aaa_oc_productforecast_index';
    const T_LOG   = 'aaa_oc_productforecast_log';

    private static $did = false;

    public static function install() : void {
        if ( self::$did ) {
            return;
        }
        self::$did = true;

        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $t_index = $wpdb->prefix . self::T_INDEX;
        $t_log   = $wpdb->prefix . self::T_LOG;

        // Main index table: one row per product_id. Typed columns for correct SQL sorting/filtering.
        $sql_index = "CREATE TABLE {$t_index} (
            product_id BIGINT(20) UNSIGNED NOT NULL,
            product_title TEXT NOT NULL,
            product_sku VARCHAR(100) DEFAULT NULL,
            product_category TEXT DEFAULT NULL,
            product_brand TEXT DEFAULT NULL,

            forecast_stock_qty INT(11) DEFAULT NULL,
            forecast_total_units_sold INT(11) DEFAULT NULL,
            forecast_sales_day DECIMAL(15,4) DEFAULT NULL,
            forecast_sales_day_lifetime DECIMAL(15,4) DEFAULT NULL,
            forecast_sales_month DECIMAL(15,4) DEFAULT NULL,

            forecast_oos_date DATE DEFAULT NULL,
            forecast_reorder_date DATE DEFAULT NULL,

            forecast_margin_percent DECIMAL(10,4) DEFAULT NULL,
            forecast_frozen_capital DECIMAL(15,4) DEFAULT NULL,
            forecast_po_priority_score DECIMAL(15,4) DEFAULT NULL,

            forecast_lead_time_days INT(11) DEFAULT NULL,
            forecast_minimum_order_qty INT(11) DEFAULT NULL,
            forecast_sales_window_days INT(11) DEFAULT NULL,
            forecast_minimum_stock INT(11) DEFAULT NULL,
            forecast_cost_override DECIMAL(10,4) DEFAULT NULL,
            forecast_product_class VARCHAR(50) DEFAULT NULL,

            forecast_enable_reorder TINYINT(1) DEFAULT 0,
            forecast_do_not_reorder TINYINT(1) DEFAULT 0,
            forecast_is_must_stock TINYINT(1) DEFAULT 0,
            forecast_force_reorder TINYINT(1) DEFAULT 0,
            forecast_flag_for_review TINYINT(1) DEFAULT 0,
            forecast_is_not_moving TINYINT(1) DEFAULT 0,
            forecast_is_new_product TINYINT(1) DEFAULT 0,
            forecast_is_out_of_stock TINYINT(1) DEFAULT 0,
            forecast_is_stale_inventory TINYINT(1) DEFAULT 0,
            forecast_mark_for_clearance TINYINT(1) DEFAULT 0,
            forecast_mark_for_removal TINYINT(1) DEFAULT 0,

            forecast_sales_status VARCHAR(50) DEFAULT NULL,
            forecast_reorder_note TEXT DEFAULT NULL,

            forecast_first_sold_date DATE DEFAULT NULL,
            forecast_last_sold_date DATE DEFAULT NULL,
            forecast_first_purchased DATE DEFAULT NULL,
            forecast_last_purchased DATE DEFAULT NULL,

            aip_forecast_summary LONGTEXT DEFAULT NULL,
            aip_historical_summary LONGTEXT DEFAULT NULL,

            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (product_id),
            KEY idx_enabled (forecast_enable_reorder),
            KEY idx_oos (forecast_oos_date),
            KEY idx_reorder (forecast_reorder_date),
            KEY idx_sales_month (forecast_sales_month),
            KEY idx_stock_qty (forecast_stock_qty)
        ) ENGINE=InnoDB {$charset_collate};";

        // Optional log table: tracks index updates for auditing.
        $sql_log = "CREATE TABLE {$t_log} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            action VARCHAR(50) NOT NULL DEFAULT 'update',
            message TEXT DEFAULT NULL,
            context LONGTEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_product (product_id),
            KEY idx_action (action)
        ) ENGINE=InnoDB {$charset_collate};";

        /*
         * Use maybe_create_table instead of dbDelta to avoid syntax errors when
         * altering an existing table.  dbDelta attempts to compute column
         * differences and can produce malformed ALTER statements if the
         * CREATE definition changes in unexpected ways (for example, when
         * comments or trailing commas are present).  maybe_create_table
         * simply executes the CREATE statement if the table does not exist.
         */
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        maybe_create_table( $t_index, $sql_index );
        maybe_create_table( $t_log,   $sql_log );

        if ( class_exists( 'AAA_OC_ProductForecast_Helpers' ) ) {
            AAA_OC_ProductForecast_Helpers::log( 'Tables ensured: ' . $t_index . ' and ' . $t_log );
        }
    }

    public static function maybe_install() : void {
        self::install();
    }
}
