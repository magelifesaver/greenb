<?php
/*
Plugin Name: AAA The Printer (XHV98-WF)
Plugin URI:  https://yourwebsite.com/aaa-the-printer
Description: A WooCommerce receipt printing plugin that renders an HTML template, converts it to PDF for 80mm thermal printers, and integrates with PrintNode.
Version:     1.0
Author:      WebMaster
Author URI:  https://yourwebsite.com
License:     GPL2
Text Domain: aaa-the-printer
*/

// Define PrintNode printer IDs as constants
define( 'AAA_DISPATCH_PRINTER_ID', '73958350' );
define( 'AAA_INVENTORY_PRINTER_ID', '73958405' );

// Logging function: appends messages to a log file in the plugin root.
if ( ! function_exists( 'aaa_lpm_log' ) ) {
    function aaa_lpm_log( $message ) {
        $log_file = plugin_dir_path( __FILE__ ) . 'aaa-the-printer.log';
        $timestamp = date( 'Y-m-d H:i:s' );
        file_put_contents( $log_file, "[$timestamp] $message\n", FILE_APPEND );
    }
}

// Include additional plugin files
require_once plugin_dir_path( __FILE__ ) . 'includes/button.php';
require_once plugin_dir_path( __FILE__ ) . 'templates/receipt/template.php';
require_once plugin_dir_path( __FILE__ ) . 'templates/picklist/template.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/conditions.php';

// Enqueue admin JavaScript for the order edit screen.
function aaa_lpm_enqueue_admin_scripts( $hook ) {
    if ( 'post.php' !== $hook ) {
        return;
    }
    global $post;
    if ( ! isset( $post ) || 'shop_order' !== $post->post_type ) {
        return;
    }
    wp_enqueue_script(
        'aaa-lpm-admin',
        plugins_url( 'assets/js/aaa-lpm-admin.js', __FILE__ ),
        array( 'jquery' ),
        '1.0',
        true
    );
    wp_localize_script( 'aaa-lpm-admin', 'aaaLpmAjax', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' )
    ) );
}
add_action( 'admin_enqueue_scripts', 'aaa_lpm_enqueue_admin_scripts' );

// AJAX endpoint: Preview receipt/picklist as raw HTML
function aaa_lpm_preview_html() {
    check_ajax_referer( 'aaa_lpm_nonce', '_wpnonce' );
    $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
    $template = isset( $_GET['template'] ) ? sanitize_text_field( $_GET['template'] ) : '';
    if ( ! $order_id || ! $template ) {
        wp_send_json_error( 'Invalid parameters: missing order_id or template' );
    }
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        wp_send_json_error( 'Order not found' );
    }
    if ( 'picklist' === $template && function_exists( 'aaa_lpm_get_picklist_html' ) ) {
        echo aaa_lpm_get_picklist_html( $order );
    } else {
        if ( function_exists( 'aaa_lpm_get_order_receipt_html' ) ) {
            echo aaa_lpm_get_order_receipt_html( $order );
        } else {
            wp_send_json_error( 'Receipt function not found' );
        }
    }
    wp_die();
}
add_action( 'wp_ajax_aaa_lpm_preview_html', 'aaa_lpm_preview_html' );

// AJAX endpoint: Preview receipt/picklist as PDF
function aaa_lpm_preview_pdf() {
    check_ajax_referer( 'aaa_lpm_nonce', '_wpnonce' );
    $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
    $template = isset( $_GET['template'] ) ? sanitize_text_field( $_GET['template'] ) : 'receipt';
    if ( ! $order_id ) {
        wp_send_json_error( 'Invalid order ID' );
    }
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        wp_send_json_error( 'Order not found' );
    }
    if ( 'picklist' === $template && function_exists( 'aaa_lpm_get_picklist_html' ) ) {
        $html = aaa_lpm_get_picklist_html( $order );
    } else {
        if ( function_exists( 'aaa_lpm_get_order_receipt_html' ) ) {
            $html = aaa_lpm_get_order_receipt_html( $order );
        } else {
            wp_send_json_error( 'Receipt function not found' );
        }
    }
    if ( ! class_exists( 'Dompdf\\Dompdf', false ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/vendor/autoload.php';
}
    $dompdf = new Dompdf\Dompdf();
    $dompdf->set_option( 'isRemoteEnabled', true );
    $dompdf->set_option( 'dpi', 72 );
    $widthPoints  = 80 * 2.83465;
    $heightPoints = 1000;
    $dompdf->setPaper( [0, 0, $widthPoints, $heightPoints], 'portrait' );
    $noMarginCSS = '<style>
        @page { margin: 0; padding: 0; }
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
        table { border-collapse: collapse; }
    </style>';
    $dompdf->loadHtml( $noMarginCSS . $html );
    $dompdf->render();
    header( 'Content-type: application/pdf' );
    echo $dompdf->output();
    wp_die();
}
add_action( 'wp_ajax_aaa_lpm_preview_pdf', 'aaa_lpm_preview_pdf' );

// AJAX endpoint: Print via PrintNode (MANUAL PRINT)
function aaa_lpm_manual_print() {
    check_ajax_referer( 'aaa_lpm_nonce', '_wpnonce' );
    $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
    $template = isset( $_POST['template'] ) ? sanitize_text_field( $_POST['template'] ) : 'receipt';
    
    // Log the raw printer parameter from POST
    $raw_printer = isset( $_POST['printer'] ) ? $_POST['printer'] : '';
    aaa_lpm_log("Raw printer parameter received: '{$raw_printer}'");

    // Process printer value: trim, lowercase
    $printer = strtolower(trim(sanitize_text_field( $raw_printer ))) ?: 'dispatch';
    
    if ( ! $order_id ) {
        wp_send_json_error( 'Invalid order ID' );
    }
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        wp_send_json_error( 'Order not found' );
    }
    if ( 'picklist' === $template && function_exists( 'aaa_lpm_get_picklist_html' ) ) {
        $html = aaa_lpm_get_picklist_html( $order );
    } else {
        if ( function_exists( 'aaa_lpm_get_order_receipt_html' ) ) {
            $html = aaa_lpm_get_order_receipt_html( $order );
        } else {
            wp_send_json_error( 'Receipt function not found' );
        }
    }
    // Determine printer based on processed value
    switch ( $printer ) {
        case 'inventory':
            $printer_id   = AAA_INVENTORY_PRINTER_ID;
            $printer_name = 'Inventory';
            break;
        case 'dispatch':
        default:
            $printer_id   = AAA_DISPATCH_PRINTER_ID;
            $printer_name = 'Dispatch';
            break;
    }
    // Log manual print request details.
    // Log + add order note BEFORE sending
    $order->add_order_note(
        sprintf( 'Manual print requested: %s → %s printer.', ucfirst($template), $printer_name )
    );
    aaa_lpm_log("Manual Print Request: Order #{$order_id}, Template: {$template}, Printer parameter: '{$printer}', using {$printer_name} ({$printer_id}).");

    if ( ! class_exists( 'Dompdf\\Dompdf', false ) ) {
	    require_once plugin_dir_path( __FILE__ ) . 'includes/vendor/autoload.php';
	}
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
    $temp_pdf   = tempnam( sys_get_temp_dir(), 'receipt_' ) . '.pdf';
    file_put_contents( $temp_pdf, $pdf_output );
    $printnode_api_key = 'FEaYhNyYZRRa_ZkmrAknNjjyDhYxrCFGQYmOR_NLGNU';
    $pdf_data          = base64_encode( file_get_contents( $temp_pdf ) );
    $data = array(
        'printerId'   => $printer_id,
        'title'       => ( 'picklist' === $template ) ? 'Picklist' : 'Order Receipt',
        'contentType' => 'pdf_base64',
        'content'     => $pdf_data,
        'source'      => 'AAA The Printer'
    );
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, 'https://api.printnode.com/printjobs' );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_POST, true );
    curl_setopt( $ch, CURLOPT_USERPWD, $printnode_api_key . ':' );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
    $result = curl_exec( $ch );
    if ( curl_errno( $ch ) ) {
        $error_msg = curl_error( $ch );
        aaa_lpm_log("PrintNode Error for Order #{$order_id}: " . $error_msg);
    } else {
        aaa_lpm_log("PrintNode Response for Order #{$order_id}: " . $result);
    }
    curl_close( $ch );
    unlink( $temp_pdf );
    $response_message = 'Print job sent successfully to ' . $printer_name . '.';
    aaa_lpm_log("Manual Print Completed: Order #{$order_id} - " . $response_message);
    wp_send_json_success( $response_message );
}
add_action( 'wp_ajax_aaa_lpm_manual_print', 'aaa_lpm_manual_print' );
