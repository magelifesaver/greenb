<?php
/**
 * /wp-content/plugins/aaa-the-printer/includes/print-envelope.php
 *
 * This file registers and handles the "Print Envelope" bulk action for WooCommerce orders.
 * It collects order data, generates an envelope group ID, updates index tables,
 * renders the envelope HTML, converts to PDF, and sends to PrintNode.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register the bulk action
add_filter( 'bulk_actions-edit-shop_order', function( $actions ) {
    $actions['aaa_print_envelope'] = 'Print Envelope';
    return $actions;
} );

// Handle the action
add_filter( 'handle_bulk_actions-edit-shop_order', function( $redirect, $action, $order_ids ) {
    if ( $action !== 'aaa_print_envelope' ) {
        return $redirect;
    }

    if ( empty( $order_ids ) ) {
        return add_query_arg( 'aaa_envelope_result', 'no_orders', $redirect );
    }

    $envelope_id   = 'ENV-' . date( 'Ymd-His' ) . '-' . wp_rand( 100, 999 );
    $driver_name   = 'Unassigned'; // You can override this later
    $delivery_date = date( 'F j, Y' );
    $orders        = array();

    foreach ( $order_ids as $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            continue;
        }

        // Save to post meta and update indexes
        update_post_meta( $order_id, 'aaa_envelope_id', $envelope_id );

        global $wpdb;
        $wpdb->update( 'aaa_oc_order_index',   [ 'envelope_id' => $envelope_id ], [ 'order_id' => $order_id ] );
        $wpdb->update( 'aaa_oc_payment_index', [ 'envelope_id' => $envelope_id ], [ 'order_id' => $order_id ] );

        $orders[] = $order;
    }

    if ( empty( $orders ) ) {
        return add_query_arg( 'aaa_envelope_result', 'no_valid_orders', $redirect );
    }

    // Load the template function
    require_once plugin_dir_path( __FILE__ ) . '../templates/envelope/template.php';

    $html = aaa_lpm_get_envelope_template_html( $orders, $envelope_id, $driver_name, $delivery_date );

    // Convert to PDF
    if ( ! class_exists( 'Dompdf\\Dompdf' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../vendor/autoload.php';
    }

    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml( $html );
    $dompdf->setPaper( 'A4', 'portrait' );
    $dompdf->render();
    $pdf_data = $dompdf->output();

    // Send to PrintNode
    $printer_id = YOUR_ENVELOPE_PRINTER_ID; // Replace with actual ID
    aaa_lpm_printnode_send_pdf( $pdf_data, 'Envelope Batch: ' . $envelope_id, $printer_id );

    return add_query_arg( 'aaa_envelope_result', 'success', $redirect );
}, 10, 3 );
