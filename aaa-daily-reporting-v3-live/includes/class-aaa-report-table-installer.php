<?php
/**
 * File: includes/class-aaa-report-table-installer.php
 * Description: Handles database table installation for AAA Daily Reporting plugin.
 * Version: 1.0.3 · 2025-07-31
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_Report_Table_Installer {

    /**
     * Register activation hook for installing/updating tables.
     */
    public static function init() {
        $main_file = plugin_dir_path( __DIR__ ) . 'aaa-daily-reporting.php';
        register_activation_hook( $main_file, [ __CLASS__, 'maybe_install_tables' ] );
    }

    /**
     * Run all table installation methods.
     */
    public static function maybe_install_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        error_log( '[AAA Report Installer] Starting table installation' );

        self::install_daily_report_table();
        self::install_orders_table();
        self::install_product_sales_table();
        self::install_product_summary_table();
        self::install_brand_summary_table();
        self::install_category_summary_table();
        self::install_customer_summary_table();
        self::install_payment_summary_table();
        self::install_refunds_table();
        self::install_delivery_city_summary_table();

        error_log( '[AAA Report Installer] Table installation completed' );
    }

    /**
     * Create the master report table.
     */
    private static function install_daily_report_table() {
        global $wpdb;
        $table   = $wpdb->prefix . 'aaa_daily_report';
        $charset = $wpdb->get_charset_collate();
        $sql     = "CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_date DATE NOT NULL,
            created_at DATETIME NOT NULL,
            total_orders BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            total_revenue DECIMAL(14,2) NOT NULL DEFAULT 0,
            total_qty BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            total_discounts DECIMAL(14,2) NOT NULL DEFAULT 0,
            total_tips DECIMAL(14,2) NOT NULL DEFAULT 0,
            total_store_credit DECIMAL(14,2) NOT NULL DEFAULT 0,
            total_cogs DECIMAL(14,2) NOT NULL DEFAULT 0,
            gross_profit DECIMAL(14,2) NOT NULL DEFAULT 0,
            avg_order_value DECIMAL(14,2) NOT NULL DEFAULT 0,
            new_customers BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            returning_customers BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            total_customers BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY report_date (report_date)
        ) $charset;";
        error_log( '[AAA Report Installer] Running dbDelta for table: ' . $table );
        error_log( '[AAA Report Installer] SQL: ' . $sql );
        dbDelta( $sql );
        error_log( "[AAA Report Installer] Installed table: $table" );
    }

    /**
     * Create detail orders table matching report-orders-v3 columns.
     */
    private static function install_orders_table() {
        global $wpdb;
        $table   = $wpdb->prefix . 'aaa_report_orders';
        $charset = $wpdb->get_charset_collate();
        $sql     = "CREATE TABLE $table (
            id                       BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_id                BIGINT(20) UNSIGNED NOT NULL,
            order_date               DATETIME            NOT NULL,
            status                   VARCHAR(20)         NULL,
            order_id                 BIGINT(20) UNSIGNED NOT NULL,
            external_order_number    VARCHAR(100)        NULL,
            customer                 VARCHAR(100)        NULL,
            source                   VARCHAR(50)         NULL,
            subtotal                 DECIMAL(14,2)       NOT NULL,
            total                    DECIMAL(14,2)       NOT NULL,
            discount                 DECIMAL(14,2)       NOT NULL,
            percent_discount         DECIMAL(5,2)        NOT NULL,
            website_tip              DECIMAL(14,2)       NOT NULL,
            epayment_tip             DECIMAL(14,2)       NOT NULL,
            shipping                 DECIMAL(14,2)       NOT NULL,
            net_sales                DECIMAL(14,2)       NOT NULL,
            cogs                     DECIMAL(14,2)       NOT NULL,
            profit                   DECIMAL(14,2)       NOT NULL,
            items_count              INT(10) UNSIGNED    NOT NULL,
            unique_items_count       INT(10) UNSIGNED    NOT NULL,
            store_credit             DECIMAL(14,2)       NOT NULL,
            payment_method           VARCHAR(100)        NULL,
            real_payment             TEXT                NULL,
            city                     VARCHAR(100)        NULL,
            `time`                   VARCHAR(10)         NULL,
            PRIMARY KEY  (id),
            KEY report_id (report_id),
            KEY order_id  (order_id)
        ) $charset;";

        error_log( '[AAA Report Installer] Running dbDelta for table: ' . $table );
        error_log( '[AAA Report Installer] SQL: ' . $sql );
        dbDelta( $sql );
        error_log( "[AAA Report Installer] Installed table: $table" );
    }
    /** Create product sales detail table. */
    private static function install_product_sales_table() {
        global $wpdb;
        $table   = $wpdb->prefix . 'aaa_report_product_sales';
        $charset = $wpdb->get_charset_collate();
        $sql     = "CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_id BIGINT(20) UNSIGNED NOT NULL,
            order_item_id BIGINT(20) UNSIGNED NOT NULL,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            brand_id BIGINT(20) UNSIGNED NULL,
            category_id BIGINT(20) UNSIGNED NULL,
            quantity INT(10) UNSIGNED NOT NULL,
            revenue DECIMAL(14,2) NOT NULL,
            cost DECIMAL(14,2) NOT NULL,
            profit DECIMAL(14,2) NOT NULL,
            PRIMARY KEY  (id),
            KEY report_id (report_id),
            KEY product_id (product_id)
        ) $charset;";
        error_log( '[AAA Report Installer] Running dbDelta for table: ' . $table );
        error_log( '[AAA Report Installer] SQL: ' . $sql );
        dbDelta( $sql );
        error_log( "[AAA Report Installer] Installed table: $table" );
    }

    /** Create aggregated product summary. */
    private static function install_product_summary_table() {
        global $wpdb;
        $table   = $wpdb->prefix . 'aaa_report_product_summary';
        $charset = $wpdb->get_charset_collate();
        $sql     = "CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_id BIGINT(20) UNSIGNED NOT NULL,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            total_qty BIGINT(20) UNSIGNED NOT NULL,
            total_revenue DECIMAL(14,2) NOT NULL,
            total_cost DECIMAL(14,2) NOT NULL,
            total_profit DECIMAL(14,2) NOT NULL,
            total_orders BIGINT(20) UNSIGNED NOT NULL,
            PRIMARY KEY  (id),
            KEY report_id (report_id),
            KEY product_id (product_id)
        ) $charset;";
        error_log( '[AAA Report Installer] Running dbDelta for table: ' . $table );
        error_log( '[AAA Report Installer] SQL: ' . $sql );
        dbDelta( $sql );
        error_log( "[AAA Report Installer] Installed table: $table" );
    }

    /** Create aggregated brand summary. */
    private static function install_brand_summary_table() {
        global $wpdb;
        $table   = $wpdb->prefix . 'aaa_report_brand_summary';
        $charset = $wpdb->get_charset_collate();
        $sql     = "CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_id BIGINT(20) UNSIGNED NOT NULL,
            brand_id BIGINT(20) UNSIGNED NOT NULL,
            total_qty BIGINT(20) UNSIGNED NOT NULL,
            total_revenue DECIMAL(14,2) NOT NULL,
            total_cost DECIMAL(14,2) NOT NULL,
            total_profit DECIMAL(14,2) NOT NULL,
            total_orders BIGINT(20) UNSIGNED NOT NULL,
            PRIMARY KEY  (id),
            KEY report_id (report_id),
            KEY brand_id (brand_id)
        ) $charset;";
        error_log( '[AAA Report Installer] Running dbDelta for table: ' . $table );
        error_log( '[AAA Report Installer] SQL: ' . $sql );
        dbDelta( $sql );
        error_log( "[AAA Report Installer] Installed table: $table" );
    }

    /** Create aggregated category summary. */
    private static function install_category_summary_table() {
        global $wpdb;
        $table   = $wpdb->prefix . 'aaa_report_category_summary';
        $charset = $wpdb->get_charset_collate();
        $sql     = "CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            total_qty BIGINT(20) UNSIGNED NOT NULL,
            total_revenue DECIMAL(14,2) NOT NULL,
            total_cost DECIMAL(14,2) NOT NULL,
            total_profit DECIMAL(14,2) NOT NULL,
            total_orders BIGINT(20) UNSIGNED NOT NULL,
            PRIMARY KEY  (id),
            KEY report_id (report_id),
            KEY category_id (category_id)
        ) $charset;";
        error_log( '[AAA Report Installer] Running dbDelta for table: ' . $table );
        error_log( '[AAA Report Installer] SQL: ' . $sql );
        dbDelta( $sql );
        error_log( "[AAA Report Installer] Installed table: $table" );
    }

    /** Create customer summary table. */
    private static function install_customer_summary_table() {
        global $wpdb;
        $table   = $wpdb->prefix . 'aaa_report_customer_summary';
        $charset = $wpdb->get_charset_collate();
        $sql     = "CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_id BIGINT(20) UNSIGNED NOT NULL,
            customer_id BIGINT(20) UNSIGNED NOT NULL,
            lifetime_orders BIGINT(20) UNSIGNED NOT NULL,
            lifetime_spent DECIMAL(14,2) NOT NULL,
            avg_order_value DECIMAL(14,2) NOT NULL,
            first_order_date DATE NULL,
            last_order_date DATE NULL,
            billing_city VARCHAR(100) NULL,
            PRIMARY KEY  (id),
            KEY report_id (report_id),
            KEY customer_id (customer_id)
        ) $charset;";
        error_log( '[AAA Report Installer] Running dbDelta for table: ' . $table );
        error_log( '[AAA Report Installer] SQL: ' . $sql );
        dbDelta( $sql );
        error_log( "[AAA Report Installer] Installed table: $table" );
    }

    /** Create payment method summary table. */
    private static function install_payment_summary_table() {
        global $wpdb;
        $table   = $wpdb->prefix . 'aaa_report_payment_summary';
        $charset = $wpdb->get_charset_collate();
        $sql     = "CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_id BIGINT(20) UNSIGNED NOT NULL,
            payment_method VARCHAR(100) NOT NULL,
            order_count BIGINT(20) UNSIGNED NOT NULL,
            total_revenue DECIMAL(14,2) NOT NULL,
            PRIMARY KEY  (id),
            KEY report_id (report_id)
        ) $charset;";
        error_log( '[AAA Report Installer] Running dbDelta for table: ' . $table );
        error_log( '[AAA Report Installer] SQL: ' . $sql );
        dbDelta( $sql );
        error_log( "[AAA Report Installer] Installed table: $table" );
    }

    /** Create refunds detail table. */
    private static function install_refunds_table() {
        global $wpdb;
        $table   = $wpdb->prefix . 'aaa_report_refunds';
        $charset = $wpdb->get_charset_collate();
        $sql     = "CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_id BIGINT(20) UNSIGNED NOT NULL,
            refund_id BIGINT(20) UNSIGNED NOT NULL,
            order_id BIGINT(20) UNSIGNED NOT NULL,
            amount DECIMAL(14,2) NOT NULL,
            reason TEXT NULL,
            admin_id BIGINT(20) UNSIGNED NULL,
            refunded_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY report_id (report_id),
            KEY refund_id (refund_id)
        ) $charset;";
        error_log( '[AAA Report Installer] Running dbDelta for table: ' . $table );
        error_log( '[AAA Report Installer] SQL: ' . $sql );
        dbDelta( $sql );
        error_log( "[AAA Report Installer] Installed table: $table" );
    }

    /** Create delivery city summary table. */
    private static function install_delivery_city_summary_table() {
        global $wpdb;
        $table   = $wpdb->prefix . 'aaa_report_delivery_city_summary';
        $charset = $wpdb->get_charset_collate();
        $sql     = "CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_id BIGINT(20) UNSIGNED NOT NULL,
            city VARCHAR(100) NOT NULL,
            order_count BIGINT(20) UNSIGNED NOT NULL,
            total_revenue DECIMAL(14,2) NOT NULL,
            PRIMARY KEY  (id),
            KEY report_id (report_id)
        ) $charset;";
        error_log( '[AAA Report Installer] Running dbDelta for table: ' . $table );
        error_log( '[AAA Report Installer] SQL: ' . $sql );
        dbDelta( $sql );
        error_log( "[AAA Report Installer] Installed table: $table" );
    }

}

// Initialize installer
AAA_Report_Table_Installer::init();
