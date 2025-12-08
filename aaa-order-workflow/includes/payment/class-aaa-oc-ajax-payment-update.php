<?php
/**
 * File Path: /aaa-order-workflow/includes/payment/class-aaa-oc-ajax-payment-update.php
 *
 * Purpose:
 * Handles AJAX submission when a user saves payment data from the frontend modal.
 * Saves payment info to the `aaa_oc_payment_index` table and logs the result.
 * Then re-triggers the reindexing process to sync into the `aaa_oc_order_index`.
 * Purpose: Handles AJAX submission when a user saves payment data.
 * Updates payment_index, logs the result, and reindexes order_index.
 * Mirrors key payment fields into post meta for ACP and other tools.
 * Adds a WooCommerce order note recording who saved the payment and when.
 * Mirrors a Dispatch Note into the payment_index table so it appears in the expanded card.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Ajax_Payment_Update {

    /**
     * Hook registration.
     */
    public static function init() {
        add_action( 'wp_ajax_aaa_oc_update_payment_index', [ __CLASS__, 'handle_payment_update' ] );
    }

    /**
     * Handle the payment save request.
     */
    public static function handle_payment_update() {
        global $wpdb;
        $order_id = absint( $_POST['order_id'] ?? 0 );
        if ( ! $order_id || ! current_user_can( 'edit_shop_order', $order_id ) ) {
            wp_send_json_error( 'Invalid or unauthorized request.' );
        }

        $payment_table = $wpdb->prefix . 'aaa_oc_payment_index';
        $order_index   = $wpdb->prefix . 'aaa_oc_order_index';

        // Get existing notes & status
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT payment_admin_notes, aaa_oc_payment_status FROM {$payment_table} WHERE order_id = %d",
                $order_id
            ),
            ARRAY_A
        );
        $old_notes  = $existing['payment_admin_notes'] ?? '';
        $old_status = $existing['aaa_oc_payment_status'] ?? '';

	// Append any manual admin note
	$new_note_input = sanitize_textarea_field( $_POST['new_admin_note'] ?? '' );
	if ( $new_note_input !== '' ) {
	    $timestamp = current_time( 'Y-m-d H:i:s' );
	    $user      = wp_get_current_user()->display_name;
	    $entry     = sprintf( "[%s] %s: %s", $timestamp, $user, $new_note_input );
	    $updated_notes = $old_notes ? "{$old_notes}\n{$entry}" : $entry;

	    // — Save Dispatch Note as a WooCommerce Order Note —
	    $order = wc_get_order( $order_id );
	    if ( $order ) {
	        $order->add_order_note(
	            sprintf(
	                'Dispatch note added by %s at %s: %s',
	                $user,
	                $timestamp,
	                $new_note_input
	            )
	        );
	    }
	} else {
	    $updated_notes = $old_notes;
	}

        // Payment data payload
        $tip_meta = (float) get_post_meta( $order_id, '_wpslash_tip', true );
        $data = [
            'aaa_oc_cash_amount'        => floatval( $_POST['aaa_oc_cash_amount']     ?? 0 ),
            'aaa_oc_zelle_amount'       => floatval( $_POST['aaa_oc_zelle_amount']    ?? 0 ),
            'aaa_oc_venmo_amount'       => floatval( $_POST['aaa_oc_venmo_amount']    ?? 0 ),
            'aaa_oc_applepay_amount'    => floatval( $_POST['aaa_oc_applepay_amount'] ?? 0 ),
            'aaa_oc_creditcard_amount'  => floatval( $_POST['aaa_oc_creditcard_amount'] ?? 0 ),
            'aaa_oc_cashapp_amount'     => floatval( $_POST['aaa_oc_cashapp_amount']  ?? 0 ),
            'aaa_oc_epayment_total'     => floatval( $_POST['aaa_oc_epayment_total']  ?? 0 ),
            'aaa_oc_tip_total'          => $tip_meta,
            'aaa_oc_payrec_total'       => floatval( $_POST['aaa_oc_payrec_total']    ?? 0 ),
            'aaa_oc_order_balance'      => floatval( $_POST['aaa_oc_order_balance']   ?? 0 ),
            'epayment_tip'              => floatval( $_POST['epayment_tip']           ?? 0 ),
            'total_order_tip'           => floatval( $_POST['total_order_tip']        ?? 0 ),
            'aaa_oc_payment_status'     => sanitize_text_field( $_POST['aaa_oc_payment_status'] ?? 'unpaid' ),
            'payment_admin_notes'       => $updated_notes,
            'processing_fee'            => floatval( $_POST['processing_fee']         ?? 0 ),
            'envelope_id'               => sanitize_text_field( $_POST['envelope_id'] ?? '' ),
            'route_id'                  => sanitize_text_field( $_POST['route_id']    ?? '' ),
            'cleared'                   => isset( $_POST['cleared'] ) ? 1 : 0,
            'envelope_outstanding'      => intval( $_POST['envelope_outstanding'] ?? 0 ),
            'last_updated_by'           => wp_get_current_user()->user_login,
        ];
	// Determine real payment method from the just-submitted amounts
	$amount_map = [
	    'Zelle'       => $data['aaa_oc_zelle_amount'],
	    'Cash'        => $data['aaa_oc_cash_amount'],
	    'Venmo'       => $data['aaa_oc_venmo_amount'],
	    'ApplePay'    => $data['aaa_oc_applepay_amount'],
	    'CashApp'     => $data['aaa_oc_cashapp_amount'],
	    'Credit Card' => $data['aaa_oc_creditcard_amount'],
	];

	$nonzero = array_filter( $amount_map, static function( $v ) { return $v > 0; } );

	if ( count( $nonzero ) === 1 ) {
	    $real_payment_method = array_key_first( $nonzero );
	} elseif ( count( $nonzero ) > 1 ) {
	    $max = max( $nonzero );
	    $real_payment_method = array_search( $max, $nonzero, true );
	} else {
	    $order = isset($order) && $order ? $order : wc_get_order( $order_id );
	    $real_payment_method = $order ? (string) $order->get_payment_method() : '';
	}

	$data['real_payment_method'] = $real_payment_method;

        // Insert or update payment_index
        $exists = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$payment_table} WHERE order_id = %d", $order_id
        ) );
        if ( $exists ) {
            $wpdb->update( $payment_table, $data, [ 'order_id' => $order_id ] );
        } else {
            $order = wc_get_order( $order_id );
            $data['order_id']                = $order_id;
            $data['aaa_oc_order_total']      = $order ? (float) $order->get_total()    : 0.00;
            $data['subtotal']                = $order ? (float) $order->get_subtotal() : 0.00;
            $data['original_payment_method'] = $order ? sanitize_text_field( $order->get_payment_method() ) : '';
            $wpdb->insert( $payment_table, $data );
        }

	// Mirror key fields to post meta (force update even if 0)
        foreach ( [
            'aaa_oc_cash_amount',
            'aaa_oc_zelle_amount',
            'aaa_oc_venmo_amount',
            'aaa_oc_cashapp_amount',
            'aaa_oc_applepay_amount',
            'aaa_oc_creditcard_amount',
            'aaa_oc_epayment_total',
            'aaa_oc_payrec_total',
            'aaa_oc_order_balance',
            'epayment_tip',
            'total_order_tip',
            'aaa_oc_payment_status',
            'payment_admin_notes',
   	    'envelope_outstanding',
	    'cleared',              // and ensure cleared behaves the same
        ] as $key ) {
    update_post_meta( $order_id, $key, $data[ $key ] ?? '' );
	}

        // --- Sync WooCommerce paid state with Workflow payment status ---
	$order = wc_get_order( $order_id );
	if ( $order ) {
	    $payment_status = $data['aaa_oc_payment_status'] ?? 'unpaid';
	    $payrec_total   = $data['aaa_oc_payrec_total'] ?? 0;

	    if ( $payment_status === 'paid' ) {
	        // Mark Woo order as paid
	        $order->set_date_paid( current_time( 'mysql' ) );
	        $order->update_meta_data( 'aaa_oc_payrec_total', $payrec_total ); // ensure mirrored
	        $order->save();
	    } else {
	        // Not fully paid → clear Woo paid date
	        $order->set_date_paid( null );
	        $order->save();
	    }
	}

	// Log the action
        $log_table = $wpdb->prefix . 'aaa_oc_payment_log';
        $wpdb->insert( $log_table, [
            'order_id'   => $order_id,
            'changes'    => wp_json_encode( $data ),
            'created_by' => wp_get_current_user()->user_login,
            'type'       => 'manual',
        ] );
        $log_id = $wpdb->insert_id ?: null;
        if ( $exists && $log_id ) {
            $wpdb->update( $payment_table, [ 'change_log_id' => $log_id ], [ 'order_id' => $order_id ] );
        }

        // Reindex the order
        if ( class_exists( 'AAA_OC_Payment_Indexer' ) ) {
            AAA_OC_Payment_Indexer::sync_payment_totals( $order_id );
        }
        if ( class_exists( 'AAA_OC_Indexing' ) ) {
            (new AAA_OC_Indexing())->index_order( $order_id );
        }

// ————————————————
// Only auto‐note when this wasn’t a manual dispatch note
if ( empty( $new_note_input ) ) {
    $order = wc_get_order( $order_id );
    if ( $order ) {
        $user = wp_get_current_user()->display_name;
        $time = current_time( 'M j, g:i a' );

        // Map field keys to short labels
        $label_map = [
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

        // Skip these keys from diffs
        $skip_keys = [ 'payment_admin_notes', 'change_log_id' ];

        // Get last snapshot from payment log (skip newest)
        $log_table = $wpdb->prefix . 'aaa_oc_payment_log';
        $last_snapshot_json = $wpdb->get_var( $wpdb->prepare(
            "SELECT changes FROM {$log_table} WHERE order_id = %d ORDER BY id DESC LIMIT 1 OFFSET 1",
            $order_id
        ));
        $last_snapshot = $last_snapshot_json ? json_decode( $last_snapshot_json, true ) : $existing;
	// Keys we do NOT want to track in notes
	$skip_keys = [ 'payment_admin_notes', 'change_log_id' ];

        // Build diffs vs last snapshot
        $changes = [];
        foreach ( $data as $key => $new_value ) {
            if ( in_array( $key, $skip_keys, true ) ) {
                continue;
            }
            $old_value = $last_snapshot[$key] ?? null;
            if ( $new_value != $old_value ) {
                $label = $label_map[$key] ?? ucwords( str_replace( ['aaa_oc_', '_'], ['', ' '], $key ) );
                $old_disp = ($old_value === null || $old_value === '') ? '0' : $old_value;
                $new_disp = ($new_value === null || $new_value === '') ? '0' : $new_value;
                $changes[] = "{$label}: {$old_disp} → {$new_disp}";
            }
        }

        if ( ! empty( $changes ) ) {
            // One-line WooCommerce note
            $note = sprintf("[%s] %s updated payment: %s",
                $time,
                $user,
                implode('; ', $changes)
            );
            $order->add_order_note( $note );

            // Mirror into Dispatch Notes
            $dispatch_entry = $note;
            $current = $wpdb->get_var( $wpdb->prepare(
                "SELECT payment_admin_notes FROM {$payment_table} WHERE order_id = %d",
                $order_id
            ) );
            $updated = $current ? "{$current}\n{$dispatch_entry}" : $dispatch_entry;

            $wpdb->update(
                $payment_table,
                [ 'payment_admin_notes' => $updated ],
                [ 'order_id'            => $order_id ],
                [ '%s' ],
                [ '%d' ]
            );
            update_post_meta( $order_id, 'payment_admin_notes', $updated );
        }
    }
}
        error_log('[AAA_OC] ✅ handle_payment_update() complete');
        wp_send_json_success();
    }
}

AAA_OC_Ajax_Payment_Update::init();
