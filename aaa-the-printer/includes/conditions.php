<?php
/**
 * File: /wp-content/plugins/aaa-the-printer/includes/conditions.php
 * Automatic printing on order events (non-blocking version).
 * 
 * CURRENT BEHAVIOR:
 * - checkout-draft → pending  →  1× receipt (Dispatch)
 * - pending → processing       →  1× pick-list + 1× receipt (Inventory)
 * - processing → lkd-packed-ready → 1× receipt (Inventory)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ---------------------------------------------------------------- *
 * Utility: Mark order as printed and return meta key
 * ---------------------------------------------------------------- */
function aaa_lpm_mark_printed( $order, $template, $context ) {
    if ( ! $order instanceof WC_Order ) {
        return false;
    }

    $meta_key = '_aaa_lpm_printed_' . $template . '_' . $context;

    // Already printed?
    if ( $order->get_meta( $meta_key ) ) {
        return false;
    }

    // Apply flag
    $order->update_meta_data( $meta_key, 1 );
    $order->save();
    return $meta_key;
}

/* ---------------------------------------------------------------- *
 * 1) New Order: checkout-draft → pending
 *    Print 1× receipt to Dispatch
 * ---------------------------------------------------------------- */
add_action( 'transition_post_status', function ( $new, $old, $post ) {
    if ( 'shop_order' !== $post->post_type ) {
        return;
    }

    $old = str_replace( 'wc-', '', $old );
    $new = str_replace( 'wc-', '', $new );

    if ( in_array( $old, [ 'draft', 'checkout-draft' ], true ) && 'pending' === $new ) {
        $order_id = $post->ID;
        register_shutdown_function( function () use ( $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                return;
            }

            $meta_key = aaa_lpm_mark_printed( $order, 'receipt', 'draft_to_pending' );
            if ( ! $meta_key ) {
                return;
            }

            $steps   = [];
            $steps[] = "Auto-print triggered (New Order Draft → Pending):";
            $steps[] = "- Meta flag applied: {$meta_key}";

            $html = aaa_lpm_get_order_receipt_html( $order );
            aaa_lpm_printnode_send_pdf(
                $html,
                'New Order Receipt',
                AAA_DISPATCH_PRINTER_ID,
                $order_id,
                'receipt'
            );
            $steps[] = "- Receipt sent to Dispatch printer";

            $order->add_order_note( implode( "\n", $steps ) );
        } );
    }
}, 10, 3 );

/* ---------------------------------------------------------------- *
 * 2) pending → processing → 1× Picklist + 1× Receipt (Inventory)
 * ---------------------------------------------------------------- */
add_action( 'woocommerce_order_status_pending_to_processing', function ( $order_id ) {
    register_shutdown_function( function () use ( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $meta_key = aaa_lpm_mark_printed( $order, 'combo', 'pending_to_processing' );
        if ( ! $meta_key ) {
            return;
        }

        $steps   = [];
        $steps[] = "Auto-print triggered (Pending → Processing):";
        $steps[] = "- Meta flag applied: {$meta_key}";

        // Print Picklist
        $picklist_html = aaa_lpm_get_picklist_html( $order );
        aaa_lpm_printnode_send_pdf(
            $picklist_html,
            'Picklist (Pending→Processing)',
            AAA_INVENTORY_PRINTER_ID,
            $order_id,
            'picklist'
        );
        $steps[] = "- Picklist sent to Inventory printer";

        // Print Receipt
        $receipt_html = aaa_lpm_get_order_receipt_html( $order );
        aaa_lpm_printnode_send_pdf(
            $receipt_html,
            'Receipt (Pending→Processing)',
            AAA_INVENTORY_PRINTER_ID,
            $order_id,
            'receipt'
        );
        $steps[] = "- Receipt sent to Inventory printer";

        $order->add_order_note( implode( "\n", $steps ) );
    } );
}, 10, 1 );

/* ---------------------------------------------------------------- *
 * 3) processing → lkd-packed-ready → 1× Receipt (Inventory)
 * ---------------------------------------------------------------- */
add_action( 'transition_post_status', function ( $new, $old, $post ) {
    if ( 'shop_order' !== $post->post_type ) {
        return;
    }

    $old = str_replace( 'wc-', '', $old );
    $new = str_replace( 'wc-', '', $new );
    $order_id = $post->ID;

    if ( 'processing' === $old && 'lkd-packed-ready' === $new ) {
        register_shutdown_function( function () use ( $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                return;
            }

            $meta_key = aaa_lpm_mark_printed( $order, 'receipt', 'processing_to_ready' );
            if ( ! $meta_key ) {
                return; // already printed
            }

            $steps   = [];
            $steps[] = "Auto-print triggered (Processing → Packed & Ready):";
            $steps[] = "- Meta flag applied: {$meta_key}";

            $html = aaa_lpm_get_order_receipt_html( $order );

            aaa_lpm_printnode_send_pdf(
                $html,
                'Receipt (Processing→Ready)',
                AAA_INVENTORY_PRINTER_ID,
                $order_id,
                'receipt'
            );
            $steps[] = "- 1× Receipt sent to Inventory printer";

            $order->add_order_note( implode( "\n", $steps ) );
        } );
    }
}, 10, 3 );

/* ---------------------------------------------------------------- *
 * 4) Utility: render HTML → PDF and send to PrintNode
 * ---------------------------------------------------------------- */
function aaa_lpm_printnode_send_pdf( $html, $title = 'Order Print', $printer_id = '74147216', $order_id = '', $template = '' ) {
    require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

    $dompdf = new Dompdf\Dompdf();
    $dompdf->set_option( 'isRemoteEnabled', true );
    $dompdf->set_option( 'dpi', 72 );
    $dompdf->setPaper( [ 0, 0, 80 * 2.83465, 1000 ], 'portrait' );
    $dompdf->loadHtml( '<style>@page{margin:0}body{margin:0;font-family:Arial}</style>' . $html );
    $dompdf->render();

    $temp = tempnam( sys_get_temp_dir(), 'aaa_print_' ) . '.pdf';
    file_put_contents( $temp, $dompdf->output() );

    $api_key  = 'FEaYhNyYZRRa_ZkmrAknNjjyDhYxrCFGQYmOR_NLGNU';
    $pdf_data = base64_encode( file_get_contents( $temp ) );
    $site     = get_bloginfo( 'name' );
    $type     = ( 'picklist' === $template ) ? 'Picklist' : 'Order Receipt';

    $payload = [
        'printerId'   => $printer_id,
        'title'       => "Site: {$site} | Order #{$order_id} | {$type}",
        'contentType' => 'pdf_base64',
        'content'     => $pdf_data,
        'source'      => 'AAA The Printer'
    ];

    $ch = curl_init( 'https://api.printnode.com/printjobs' );
    curl_setopt_array( $ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_USERPWD        => $api_key . ':',
        CURLOPT_HTTPHEADER     => [ 'Content-Type: application/json' ],
        CURLOPT_POSTFIELDS     => json_encode( $payload ),
    ] );
    $result = curl_exec( $ch );

    if ( curl_errno( $ch ) ) {
        aaa_lpm_log( 'PrintNode Error: ' . curl_error( $ch ) );
    } else {
        aaa_lpm_log( 'PrintNode OK: ' . $result );
    }
    curl_close( $ch );
    unlink( $temp );
}
