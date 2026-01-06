<?php
/**
 * Version: 1.4.0 (2026-01-06)
 *
 * Filepath: includes/forecast/class-forecast-index-table.php
 * ---------------------------------------------------------------------------
 * Provides creation and management of the custom forecast index table.  This
 * table holds a denormalised snapshot of key forecast metrics for each
 * WooCommerce product.  Storing forecast data in a dedicated table avoids
 * repeated meta queries, allows typed columns (numeric, date), and makes the
 * grid fast and filterable.  A summary field is still written to post meta
 * for AI search but all reporting should read from this table.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WF_SFWF_Forecast_Index_Table {

    /**
     * Returns the fully qualified table name including the WordPress table
     * prefix.  Use this helper whenever building SQL queries.
     *
     * @return string
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'aaa_sfwf_forecast_index';
    }

    /**
     * Creates the forecast index table if it does not exist.  Uses
     * `dbDelta` to allow future schema changes without dropping data.  The
     * schema defines typed columns for each forecast metric, flags and
     * metadata.  Numeric columns use appropriate types (INT, DECIMAL) and
     * boolean values are stored as tinyint(1) for efficient filtering.
     *
     * The primary key is `product_id` so each product has a single row.
     */
    public static function install() {
        global $wpdb;

        $table_name      = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        // Build SQL with explicit column types.  Keep this aligned with
        // Forecast meta fields defined in forecast-column-definitions.php.
        $sql = "CREATE TABLE {$table_name} (
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
            forecast_cost_override DECIMAL(10,4) DEFAULT NULL,
            forecast_product_class VARCHAR(50) DEFAULT NULL,
            forecast_enable_reorder TINYINT(1) DEFAULT 0,
            forecast_do_not_reorder TINYINT(1) DEFAULT 0,
            forecast_is_must_stock TINYINT(1) DEFAULT 0,
            forecast_force_reorder TINYINT(1) DEFAULT 0,
            forecast_flag_for_review TINYINT(1) DEFAULT 0,
            forecast_is_not_moving TINYINT(1) DEFAULT 0,
            forecast_is_new_product TINYINT(1) DEFAULT 0,
            forecast_sales_status VARCHAR(50) DEFAULT NULL,
            forecast_is_out_of_stock TINYINT(1) DEFAULT 0,
            forecast_is_stale_inventory TINYINT(1) DEFAULT 0,
            forecast_reorder_note TEXT DEFAULT NULL,
            forecast_first_sold_date DATE DEFAULT NULL,
            forecast_last_sold_date DATE DEFAULT NULL,
            forecast_first_purchased DATE DEFAULT NULL,
            forecast_last_purchased DATE DEFAULT NULL,
            forecast_mark_for_clearance TINYINT(1) DEFAULT 0,
            forecast_mark_for_removal TINYINT(1) DEFAULT 0,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (product_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Ensures the forecast index table exists.  Should be called on plugin
     * load.  Uses an option flag to avoid repeated calls in the same request.
     */
    public static function maybe_install() {
        static $installed;
        if ( isset( $installed ) ) {
            return;
        }
        $installed = true;
        self::install();
    }
}