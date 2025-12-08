<?php
/**
 * File: includes/helpers/class-aaa-oc-save-driver.php
 * Purpose: Save driver selection without reindexing the entire order row.
 *          This avoids overwriting fulfillment_status/picked_items in aaa_oc_order_index.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Save_Driver {

    public static function init() {
        add_action( 'wp_ajax_aaa_oc_save_driver', [ __CLASS__, 'handle' ] );
    }

    public static function handle() {
        // Basic guardrails & logging
        error_log('[DRIVER AJAX] START');

        $order_id  = absint( $_POST['order_id'] ?? 0 );
        $driver_id = sanitize_text_field( $_POST['driver_id'] ?? '' );

        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ], 403 );
        }

        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'aaa_oc_ajax_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Invalid nonce.' ], 403 );
        }

        if ( ! $order_id || $driver_id === '' ) {
            wp_send_json_error( [ 'message' => 'Missing data.' ], 400 );
        }

        // 1) Save to WooCommerce order meta (used by LDDFW etc.)
        update_post_meta( $order_id, 'lddfw_driverid', $driver_id );

        // 2) Update ONLY the driver_id in the order index (do not reindex whole row)
        global $wpdb;
        $tbl = $wpdb->prefix . 'aaa_oc_order_index';
        $wpdb->update(
            $tbl,
            [ 'driver_id' => (int) $driver_id ],
            [ 'order_id'  => (int) $order_id ],
            [ '%d' ],
            [ '%d' ]
        );

        // Optional: note for traceability
        $order = wc_get_order( $order_id );
        if ( $order ) {
            $drv_user = get_user_by( 'id', (int) $driver_id );
            $label    = $drv_user ? $drv_user->display_name : '#' . $driver_id;
            $order->add_order_note( sprintf( 'Driver assigned: %s', $label ) );
        }

        error_log("[DRIVER AJAX] Saved driver only (no reindex) for order #{$order_id} → {$driver_id}");
        wp_send_json_success( [ 'message' => 'Driver saved.' ] );
    }
}

AAA_OC_Save_Driver::init();
