<?php
/**
 * File Path: /aaa-order-workflow/includes/payment/class-aaa-oc-payment-setup.php
 * Purpose: Creates and updates the custom payment tables used for logging
 *          and indexing real-time payment information in the workflow system.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Payment_Setup {

    /**
     * Install or update all payment-related tables.
     * Called during plugin activation.
     */
    public static function install() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $payment_table = $wpdb->prefix . 'aaa_oc_payment_index';
        $log_table     = $wpdb->prefix . 'aaa_oc_payment_log';

        // Payment Index Table
        $sql1 = "CREATE TABLE {$payment_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NOT NULL UNIQUE,

            aaa_oc_cash_amount DECIMAL(10,2) DEFAULT 0.00,
            aaa_oc_zelle_amount DECIMAL(10,2) DEFAULT 0.00,
            aaa_oc_venmo_amount DECIMAL(10,2) DEFAULT 0.00,
            aaa_oc_applepay_amount DECIMAL(10,2) DEFAULT 0.00,
            aaa_oc_cashapp_amount DECIMAL(10,2) DEFAULT 0.00,
            aaa_oc_creditcard_amount DECIMAL(10,2) DEFAULT 0.00,
            aaa_oc_epayment_total DECIMAL(10,2) DEFAULT 0.00,

            driver_id BIGINT UNSIGNED NULL,
            epayment_tip DECIMAL(10,2) DEFAULT 0.00,
            total_order_tip DECIMAL(10,2) DEFAULT 0.00,
            aaa_oc_tip_total DECIMAL(10,2) DEFAULT 0.00,
            aaa_oc_payrec_total DECIMAL(10,2) DEFAULT 0.00,
            aaa_oc_order_balance DECIMAL(10,2) DEFAULT NULL,
            aaa_oc_order_total DECIMAL(10,2) DEFAULT 0.00,
            subtotal DECIMAL(10,2) DEFAULT 0.00,
            processing_fee DECIMAL(10,2) DEFAULT 0.00,

            original_payment_method VARCHAR(100) NULL,
	    real_payment_method VARCHAR(100) NULL,
            payment_admin_notes TEXT NULL,
            envelope_id VARCHAR(100) NULL,
            route_id VARCHAR(100) NULL,
            aaa_oc_payment_status VARCHAR(20) DEFAULT 'unpaid',
            cleared TINYINT(1) NOT NULL DEFAULT 0,
            envelope_outstanding TINYINT(1) NOT NULL DEFAULT 0,

            epayment_detail TEXT NULL,

            last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_updated_by VARCHAR(100) NULL,
            notes_summary TEXT NULL,
            change_log_id BIGINT DEFAULT NULL,

            PRIMARY KEY (id)
        ) ENGINE=InnoDB {$charset_collate};";

        // Payment Change Log Table
        $sql2 = "CREATE TABLE {$log_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED  NOT NULL,
            changes TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_by VARCHAR(100) NOT NULL,
            type ENUM('manual','system','driver') DEFAULT 'manual',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql1 );
        dbDelta( $sql2 );
    }
}
