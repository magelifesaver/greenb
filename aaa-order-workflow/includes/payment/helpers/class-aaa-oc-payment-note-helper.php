<?php
/**
 * Class: AAA_OC_Payment_Note_Helper
 * File Path: /aaa-order-workflow/includes/payment/helpers/class-aaa-oc-payment-note-helper.php
 *
 * Purpose:
 * Provides a static method to generate a concise, diff‑based summary note
 * whenever payment information for an order is updated. The method compares
 * the new payment data against the previous snapshot (from the payment log),
 * builds a one‑line human‑readable string of changes, adds that as a
 * WooCommerce order note, and appends the same entry to the `payment_admin_notes`
 * column in the payment index table as well as the corresponding post meta.
 * This helper is loaded on demand by the AJAX handler.
 *
 * Version: 1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Payment_Note_Helper {
    /**
     * Build and append a summary note for a payment update.
     *
     * @param int   $order_id     The WooCommerce order ID.
     * @param array $current_data The current payment data array (full payload from AJAX handler).
     * @param array $existing_row The existing payment_index row (used as fallback when log is absent).
     */
    public static function add_summary_note( $order_id, array $current_data, array $existing_row = [] ) {
        global $wpdb;
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }
        $user = wp_get_current_user()->display_name;
        $time = current_time( 'M j, g:i a' );
        // Map payment keys to short labels for the note.
        $labels = [
            'aaa_oc_cash_amount'       => 'Cash',
            'aaa_oc_zelle_amount'      => 'Zelle',
            'aaa_oc_venmo_amount'      => 'Venmo',
            'aaa_oc_applepay_amount'   => 'APAY',
            'aaa_oc_creditcard_amount' => 'CC',
            'aaa_oc_cashapp_amount'    => 'CAPP',
            'aaa_oc_tip_total'         => 'wTIP',
            'epayment_tip'             => 'eTIPS',
            'total_order_tip'          => 'Total Tips',
            'aaa_oc_epayment_total'    => 'EPAY Total',
            'aaa_oc_payrec_total'      => 'PAY Total',
            'aaa_oc_payment_status'    => 'Status',
            'real_payment_method'      => 'Real Payment',
            'cleared'                  => 'Cleared',
            'last_updated_by'          => 'Updated By',
        ];
        // Fields we never log in the summary note.
        $skip_keys = [ 'payment_admin_notes', 'change_log_id' ];
        // Fetch the previous snapshot from the payment log. Skip the most recent entry (the one we just wrote).
        $log_table = $wpdb->prefix . 'aaa_oc_payment_log';
        $last_json = $wpdb->get_var( $wpdb->prepare(
            "SELECT changes FROM {$log_table} WHERE order_id = %d ORDER BY id DESC LIMIT 1 OFFSET 1",
            $order_id
        ) );
        $last_snapshot = $last_json ? json_decode( $last_json, true ) : $existing_row;
        $changes = [];
        foreach ( $current_data as $key => $new_val ) {
            if ( in_array( $key, $skip_keys, true ) ) {
                continue;
            }
            $old_val = $last_snapshot[ $key ] ?? null;
            if ( $new_val != $old_val ) {
                $label = $labels[ $key ] ?? ucwords( str_replace( [ 'aaa_oc_', '_' ], [ '', ' ' ], $key ) );
                $old_disp = ( $old_val === null || $old_val === '' ) ? '0' : $old_val;
                $new_disp = ( $new_val === null || $new_val === '' ) ? '0' : $new_val;
                $changes[] = "{$label}: {$old_disp} → {$new_disp}";
            }
        }
        if ( empty( $changes ) ) {
            return;
        }
        $note = sprintf( '[%s] %s updated payment: %s', $time, $user, implode( '; ', $changes ) );
        // Add WooCommerce order note.
        $order->add_order_note( $note );
        // Append to payment_admin_notes in the index and post meta.
        $payment_table = $wpdb->prefix . 'aaa_oc_payment_index';
        $current_notes = $wpdb->get_var( $wpdb->prepare( "SELECT payment_admin_notes FROM {$payment_table} WHERE order_id = %d", $order_id ) );
        $updated_notes = $current_notes ? ( $current_notes . "\n" . $note ) : $note;
        $wpdb->update( $payment_table, [ 'payment_admin_notes' => $updated_notes ], [ 'order_id' => $order_id ], [ '%s' ], [ '%d' ] );
        update_post_meta( $order_id, 'payment_admin_notes', $updated_notes );
    }
}