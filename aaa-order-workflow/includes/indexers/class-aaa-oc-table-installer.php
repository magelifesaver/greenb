<?php
/**
 * Class: AAA_OC_Table_Installer
 * File Path: /plugins/aaa-order-workflow/includes/indexers/class-aaa-oc-table-installer.php
 * Purpose: Creates or updates the `aaa_oc_order_index` table used for full order indexing.
 * Notes:
 *   - Safe to run multiple times due to use of `dbDelta`
 *   - Includes all aggregated payment, customer, and metadata columns
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Table_Installer {

    public static function create_index_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . 'aaa_oc_order_index';

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL UNIQUE,

            -- Payment-related fields
            aaa_oc_payment_status VARCHAR(20) DEFAULT 'unpaid',
            aaa_oc_cash_amount DECIMAL(10,2) DEFAULT 0.00,
            aaa_oc_zelle_amount DECIMAL(10,2) DEFAULT 0.00,
            aaa_oc_venmo_amount DECIMAL(10,2) DEFAULT 0.00,
            aaa_oc_applepay_amount DECIMAL(10,2) DEFAULT 0.00,
            aaa_oc_cashapp_amount DECIMAL(10,2) DEFAULT 0.00,
            aaa_oc_creditcard_amount DECIMAL(10,2) DEFAULT 0.00,
            aaa_oc_tip_total DECIMAL(10,2) DEFAULT 0.00,
            aaa_oc_payrec_total DECIMAL(10,2) DEFAULT 0.00,
            aaa_oc_epayment_total DECIMAL(10,2) DEFAULT 0.00,
            aaa_oc_order_balance DECIMAL(10,2) DEFAULT 0.00,
            epayment_tip DECIMAL(10,2) DEFAULT 0.00,
            total_order_tip DECIMAL(10,2) DEFAULT 0.00,
	    envelope_outstanding TINYINT(1) NOT NULL DEFAULT 0,

            -- Customer info
            customer_name VARCHAR(255) NOT NULL,
            customer_email VARCHAR(255) DEFAULT NULL,
            customer_phone VARCHAR(50) DEFAULT NULL,
            customer_note TEXT DEFAULT NULL,
            daily_order_number INT DEFAULT 0,
            customer_completed_orders INT DEFAULT 0,
            average_order_amount DECIMAL(10,2) DEFAULT 0,
            lifetime_spend DECIMAL(10,2) DEFAULT 0,

            -- Customer flags
            customer_warnings_text LONGTEXT DEFAULT NULL,
            customer_banned TINYINT(1) DEFAULT 0,
            customer_ban_lenght VARCHAR(50) DEFAULT NULL,
            customer_special_needs_text LONGTEXT DEFAULT NULL,

            -- Uploaded docs
            lkd_upload_med TEXT DEFAULT NULL,
            lkd_upload_selfie TEXT DEFAULT NULL,
            lkd_upload_id TEXT DEFAULT NULL,
            lkd_birthday DATE DEFAULT NULL,
            lkd_dl_exp DATE DEFAULT NULL,
            lkd_dln VARCHAR(255) DEFAULT NULL,

            -- Order status
            status VARCHAR(50) NOT NULL,
            order_number VARCHAR(50) NOT NULL,
            time_published DATETIME NOT NULL,
            time_in_status DATETIME NOT NULL,

            -- Totals
            total_amount DECIMAL(10,2) NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
            shipping_total DECIMAL(10,2) NOT NULL DEFAULT 0,
            tax_total DECIMAL(10,2) NOT NULL DEFAULT 0,
            discount_total DECIMAL(10,2) NOT NULL DEFAULT 0,
            tip_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            currency VARCHAR(10) DEFAULT '',

            -- Fulfillment
            driver_id INT DEFAULT NULL,
            shipping_method VARCHAR(255) DEFAULT NULL,
            brand_list TEXT DEFAULT NULL,
            items LONGTEXT DEFAULT NULL,
            coupons LONGTEXT DEFAULT NULL,
            billing_json LONGTEXT DEFAULT NULL,
            fees_json LONGTEXT DEFAULT NULL,
            delivery_time TEXT DEFAULT NULL,
            fulfillment_data LONGTEXT DEFAULT NULL,
            delivery_time_range VARCHAR(255) DEFAULT NULL,
            delivery_date_formatted VARCHAR(255) DEFAULT NULL,
            lddfw_delivery_date VARCHAR(255) DEFAULT NULL,
            lddfw_delivery_time VARCHAR(255) DEFAULT NULL,
            lddfw_driverid INT DEFAULT NULL,
            usbs_order_fulfillment_data LONGTEXT DEFAULT NULL,

            -- Woo + Meta fields
            _cart_discount DECIMAL(10,2) DEFAULT 0,
            _created_via VARCHAR(255) DEFAULT NULL,
            _customer_user BIGINT DEFAULT 0,
            _funds_removed DECIMAL(10,2) DEFAULT 0,
            _funds_used DECIMAL(10,2) DEFAULT 0,
            _lkd_first_order_status_updated DATETIME DEFAULT NULL,
            _order_total DECIMAL(10,2) DEFAULT 0,
            _payment_method_title VARCHAR(255) DEFAULT NULL,
            _recorded_sales DECIMAL(10,2) DEFAULT 0,
            _wc_order_attribution_source_type VARCHAR(255) DEFAULT NULL,
            _wpslash_tip DECIMAL(10,2) DEFAULT 0,

            -- Picking + Fulfillment status
            fulfillment_status VARCHAR(20) DEFAULT 'not_picked',
            picked_items LONGTEXT DEFAULT NULL,

            -- NEW: Shipping columns + verification flags
            shipping_address_1 VARCHAR(255) DEFAULT '',
            shipping_address_2 VARCHAR(255) DEFAULT '',
            shipping_city      VARCHAR(100) DEFAULT '',
            shipping_state     VARCHAR(100) DEFAULT '',
            shipping_postcode  VARCHAR(40)  DEFAULT '',
            shipping_country   VARCHAR(2)   DEFAULT '',
            shipping_verified  TINYINT(1)   DEFAULT 0,
            billing_verified   TINYINT(1)   DEFAULT 0,

            -- Update timestamp
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        error_log('[AAA_OC_Table_Installer] ✅ Index table created or updated.');
    }
}
