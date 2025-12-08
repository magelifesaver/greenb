<?php
/**
 * File: includes/email-export-filters.php
 * Description: Registers PDF and Excel generators for reuse in email and download.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

add_filter( 'aaa_generate_daily_pdf', function( $date, $orders ) {
    require_once plugin_dir_path( __FILE__ ) . 'admin-product-pdf.php';
    $pdf_content = aaa_render_pdf_report( $orders, $date );
    $file = tempnam( sys_get_temp_dir(), "aaa-report-{$date}" );
    if ( $file ) file_put_contents( $file, $pdf_content );
    return $file;
}, 10, 2 );

add_filter( 'aaa_generate_daily_xlsx', function( $date, $orders ) {
    $spreadsheet = new Spreadsheet();
    $spreadsheet->getProperties()->setCreator('AAA')->setTitle("Report {$date}");

    $sheet = $spreadsheet->setActiveSheetIndex(0);
    $sheet->setTitle('Orders');
    $sheet->fromArray([ [ 'Order ID', 'Customer', 'Total' ] ], null, 'A1');

    $row = 2;
    foreach ( $orders as $order ) {
        $sheet->fromArray([
            $order->get_id(),
            $order->get_formatted_billing_full_name(),
            $order->get_total()
        ], null, 'A' . $row);
        $row++;
    }

    $file = wp_tempnam( "aaa-report-{$date}.xlsx" );
    if ( $file ) {
        ( new Xlsx( $spreadsheet ) )->save( $file );
    }
    return $file;
}, 10, 2 );
