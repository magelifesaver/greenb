<?php
/**
 * Plugin Name: AAA Callback Functions
 * Description: Central plugin to store all callback functions for automatic SKU → WC Product ID linking in lkd_wm_fields.
 * Version:     1.1
 * Author:      Webmaster
 * Text Domain: aaa-callbacks
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * 1) Auto-populate lkd_woo_pid when lkd_wm_og_sku is saved or changed via Meta Box.
 *    - Runs each time the 'lkd_wm_og_sku' field is updated in lkd_wm_fields.
 */
add_action( 'rwmb_after_save_field', 'aaa_auto_update_woo_pid_on_save', 10, 5 );
function aaa_auto_update_woo_pid_on_save( $field, $new_value, $old_value, $object_id, $object_type ) {
    // Only run if the updated field is "lkd_wm_og_sku"
    if ( isset( $field['id'] ) && 'lkd_wm_og_sku' === $field['id'] ) {
        $sku = trim( $new_value );

        // 1) Find the WooCommerce product by SKU
        $product_id = wc_get_product_id_by_sku( $sku );
        if ( $product_id ) {
            global $wpdb;
            // 2) Update the same record in lkd_wm_fields, setting lkd_woo_pid
            $wpdb->update(
                'lkd_wm_fields',             // table name (no prefix)
                [ 'lkd_woo_pid' => $product_id ], // SET lkd_woo_pid=...
                [ 'ID' => (int) $object_id ] // WHERE ID = ...
            );
        }
    }
}

/**
 * 2) Bulk update function for existing rows in lkd_wm_fields.
 *    - Looks for non-empty 'lkd_wm_og_sku', calls wc_get_product_id_by_sku(),
 *      and stores the ID in 'lkd_woo_pid' if found.
 *    - No echo or print => no "unexpected output" errors.
 */
function aaa_bulk_update_woo_pid_silent() {
    global $wpdb;

    $table_name = 'lkd_wm_fields'; // exactly matching your custom table name

    // Get all rows that have a non-empty SKU in lkd_wm_og_sku
    $rows = $wpdb->get_results("
        SELECT ID, lkd_wm_og_sku AS sku
          FROM {$table_name}
         WHERE lkd_wm_og_sku IS NOT NULL
           AND lkd_wm_og_sku != ''
    ");

    if ( ! $rows ) {
        return; // no rows found
    }

    foreach ( $rows as $row ) {
        $record_id = (int) $row->ID;
        $sku       = trim( $row->sku );

        $product_id = wc_get_product_id_by_sku( $sku );
        if ( $product_id ) {
            // Update that row's lkd_woo_pid
            $wpdb->update(
                $table_name,
                [ 'lkd_woo_pid' => $product_id ],
                [ 'ID' => $record_id ]
            );
        }
    }
}

/**
 * 3) On plugin activation, run the bulk update once (silently).
 */
register_activation_hook( __FILE__, 'aaa_run_bulk_update_on_activation' );
function aaa_run_bulk_update_on_activation() {
    aaa_bulk_update_woo_pid_silent();
}
