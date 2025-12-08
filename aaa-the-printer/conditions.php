<?php
/**
 * conditions.php
 *
 */

/**
 * 1) Print Receipt when a new order is created (to Dispatch printer)
 */
add_action( 'woocommerce_checkout_order_processed', 'aaa_lpm_auto_print_receipt_on_new_order', 10, 1 );
function aaa_lpm_auto_print_receipt_on_new_order( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;
    $html = aaa_lpm_get_order_receipt_html( $order );
    aaa_lpm_printnode_send_pdf( $html, 'New Order Receipt', AAA_DISPATCH_PRINTER_ID );
}

/**
 * 2) Print logic for status changes
 */
add_action( 'woocommerce_order_status_changed', 'aaa_lpm_auto_print_on_status_change', 10, 4 );
function aaa_lpm_auto_print_on_status_change( $order_id, $old_status, $new_status, $order ) {

    if ( ! $order ) {
        $order = wc_get_order( $order_id );
    }
    if ( ! $order ) return;

    // (a) Print Receipt -> INVENTORY when going from 'processing' -> 'lkd-packed-ready' (TWO COPIES)
    if ( 'processing' === $old_status && 'lkd-packed-ready' === $new_status ) {
        $html = aaa_lpm_get_order_receipt_html( $order );
        
        for ($i = 0; $i < 2; $i++) { // Print two copies
            aaa_lpm_log("Auto Print: Receipt Copy #" . ($i + 1) . " for Order #{$order_id} on status change (processing -> lkd-packed-ready) sent to Inventory (" . AAA_INVENTORY_PRINTER_ID . ").");
            aaa_lpm_printnode_send_pdf( $html, 'Receipt (Processing->lkd-packed-ready)', AAA_INVENTORY_PRINTER_ID );
        }
    }

    // (b) Print Picklist -> INVENTORY when going from 'pending' -> 'processing'
    if ( 'pending' === $old_status && 'processing' === $new_status ) {
        $html = aaa_lpm_get_picklist_html( $order );
        aaa_lpm_log("Auto Print: Picklist for Order #{$order_id} on status change (pending -> processing) sent to Inventory (" . AAA_INVENTORY_PRINTER_ID . ").");
        aaa_lpm_printnode_send_pdf( $html, 'Picklist (Pending->Processing)', AAA_INVENTORY_PRINTER_ID );
    }
}

/**
 * Render HTML as PDF and send to PrintNode.
 *
 * @param string $html        The complete HTML content
 * @param string $title       A title for the print job
 * @param string $printer_id  Which printer to use (fallback is '74147216')
 */
function aaa_lpm_printnode_send_pdf( $html, $title = 'Order Print', $printer_id = '74147216' ) {

    require_once plugin_dir_path( __FILE__ ) . 'includes/vendor/autoload.php';
    $dompdf = new Dompdf\Dompdf();
    $dompdf->set_option( 'isRemoteEnabled', true );
    $dompdf->set_option( 'dpi', 72 );

    $widthPoints  = 80 * 2.83465;
    $heightPoints = 1000;
    $dompdf->setPaper( [0, 0, $widthPoints, $heightPoints], 'portrait' );

    $noMarginCSS = '<style>
        @page { margin:0; padding:0; }
        body { margin:0; padding:0; font-family: Arial, sans-serif; }
        table { border-collapse: collapse; }
    </style>';

    $dompdf->loadHtml( $noMarginCSS . $html );
    $dompdf->render();

    $pdf_output = $dompdf->output();
    $temp_pdf   = tempnam( sys_get_temp_dir(), 'aaa_print_' ) . '.pdf';
    file_put_contents( $temp_pdf, $pdf_output );

    // PrintNode
    $printnode_api_key = 'FEaYhNyYZRRa_ZkmrAknNjjyDhYxrCFGQYmOR_NLGNU';
    $pdf_data          = base64_encode( file_get_contents( $temp_pdf ) );

    $data = array(
        'printerId'   => $printer_id,
        'title'       => $title,
        'contentType' => 'pdf_base64',
        'content'     => $pdf_data,
        'source'      => 'AAA The Printer - Automatic Conditions',
    );

    $ch = curl_init('https://api.printnode.com/printjobs');
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_POST, true );
    curl_setopt( $ch, CURLOPT_USERPWD, $printnode_api_key . ':' );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
    $result = curl_exec( $ch );

    if ( curl_errno( $ch ) ) {
        $error_msg = curl_error( $ch );
        aaa_lpm_log("PrintNode Error (Auto): " . $error_msg);
    } else {
        aaa_lpm_log("PrintNode Response (Auto): " . $result);
    }
    curl_close( $ch );
    unlink( $temp_pdf );
}
