<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/ajax/class-aaa-oc-update-order-status.php
 * Purpose: Provide a dedicated AJAX handler for updating an order's status on the
 * Workflow Board. The development version of the plugin does not currently
 * register a `aaa_oc_update_order_status` action, which causes the Next/Prev
 * buttons on the board to silently fail. This class mirrors the logic of
 * version 1's implementation by validating permissions, updating the order via
 * WooCommerce, and synchronising the custom order index table. It hooks
 * directly into the WordPress AJAX API when loaded.
 *
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AAA_OC_Update_Order_Status' ) ) {

    final class AAA_OC_Update_Order_Status {

        /**
         * Attach our AJAX callback. Called immediately when the file is loaded.
         */
        public static function init() : void {
            // This action is intentionally not registered for non‑privileged users
            // because changing an order’s status requires manage_shop_orders capability.
            add_action( 'wp_ajax_aaa_oc_update_order_status', [ __CLASS__, 'handle' ] );
            // Log the fact that our handler has been registered.  This will write
            // to aaa_oc.log when the global logger is available.  Without the
            // function_exists() check the call would throw an error if the
            // plugin’s bootstrap hasn’t defined the logger yet.
            if ( function_exists( 'aaa_oc_log' ) ) {
                aaa_oc_log('[UPDATE_ORDER_STATUS] init registered wp_ajax handler');
            }
        }

        /**
         * Main handler for updating an order status.
         *
         * Expects POST parameters:
         * - order_id  : integer ID of the order
         * - new_status: slug without the "wc-" prefix (e.g. "processing")
         *
         * Will update the WooCommerce order and the custom aaa_oc_order_index table.
         */
        public static function handle() : void {
            // Validate nonce. Use the same nonce key as board.js (aaa_oc_ajax_nonce).
            check_ajax_referer( 'aaa_oc_ajax_nonce', '_ajax_nonce' );

            // Verify capabilities. Accept both manage_woocommerce and edit_shop_orders.
            if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'edit_shop_orders' ) ) {
                wp_send_json_error( [ 'message' => 'Not allowed.' ], 403 );
            }

            $order_id   = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
            $new_status = isset( $_POST['new_status'] ) ? sanitize_text_field( $_POST['new_status'] ) : '';

            // Emit a debug log entry capturing the inbound request.  Logging is
            // optional and only occurs when aaa_oc_log() is defined.  We log the
            // raw order_id and status slug to aid troubleshooting on the dev
            // site.  See the plugin options → Debug tab to enable logging.
            if ( function_exists( 'aaa_oc_log' ) ) {
                aaa_oc_log('[UPDATE_ORDER_STATUS] handle called with order_id=' . $order_id . ', new_status=' . $new_status);
            }

            if ( ! $order_id || $new_status === '' ) {
                wp_send_json_error( [ 'message' => 'Missing order_id or new_status.' ], 400 );
            }

            // Normalise the desired status to ensure it does not include the wc- prefix twice.
            $desired_status_full = 'wc-' . ltrim( $new_status, 'wc-' );

            // If the post already has the desired status, just update the index table and return.
            $current_post_status = get_post_field( 'post_status', $order_id );
            if ( $current_post_status === $desired_status_full ) {
                self::update_index_status( $order_id, $new_status );
                wp_send_json_success( [ 'message' => 'Order already in desired status.' ] );
            }

            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                wp_send_json_error( [ 'message' => 'Order not found.' ], 400 );
            }

             try {
                 // Use WooCommerce’s API to update the status. The second parameter records a note
                 // so administrators can see that the change originated from the Workflow Board.
                 $order->update_status( $new_status, 'Status changed via Workflow Board', true );
                 if ( function_exists( 'aaa_oc_log' ) ) {
                     aaa_oc_log('[UPDATE_ORDER_STATUS] order_id=' . $order_id . ' status updated to ' . $new_status);
                 }
             } catch ( Exception $e ) {
                 if ( function_exists( 'aaa_oc_log' ) ) {
                     aaa_oc_log('[UPDATE_ORDER_STATUS] error updating order ' . $order_id . ': ' . $e->getMessage());
                 }
                 wp_send_json_error( [ 'message' => 'Error updating order status: ' . $e->getMessage() ], 500 );
             }

             // Synchronise our custom order index table. The index stores the status without the wc- prefix.
             self::update_index_status( $order_id, $new_status );
             if ( function_exists( 'aaa_oc_log' ) ) {
                 aaa_oc_log('[UPDATE_ORDER_STATUS] index table updated for order_id=' . $order_id . ' new_status=' . $new_status);
             }

             wp_send_json_success( [ 'message' => 'Order status updated successfully.' ] );
        }

        /**
         * Update the row in the aaa_oc_order_index table after a status change.
         *
         * @param int    $order_id The order ID.
         * @param string $slug_no_wc The desired status slug without the wc- prefix.
         */
        private static function update_index_status( int $order_id, string $slug_no_wc ) : void {
            global $wpdb;
            $tbl = $wpdb->prefix . 'aaa_oc_order_index';
            $wpdb->update(
                $tbl,
                [
                    'status'         => $slug_no_wc,
                    'time_in_status' => current_time( 'mysql' ),
                ],
                [ 'order_id' => $order_id ],
                [ '%s', '%s' ],
                [ '%d' ]
            );
        }
    }

    // Kick off our handler immediately.
    AAA_OC_Update_Order_Status::init();
}