<?php
/**
 * File: includes/email-scheduler.php
 * Description: Sets up daily report cron task and handles email delivery with PDF & Excel attachments
 * Version: 3.3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure our order‐fetch function is loaded before we use it
require_once plugin_dir_path( __FILE__ ) . 'report-orders-v3.php';

require_once plugin_dir_path( __FILE__ ) . '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

add_action( 'init', function() {
    if ( ! wp_next_scheduled( 'aaa_send_daily_report_email' ) ) {
        wp_schedule_event( strtotime( 'tomorrow 00:00' ), 'hourly', 'aaa_send_daily_report_email' );
    }
});

// 2) Hook our handler
add_action( 'aaa_send_daily_report_email', 'aaa_handle_daily_report_email' );

// 3) Main email handler
function aaa_handle_daily_report_email() {
    error_log('[AAA Email Cron] Triggered at ' . current_time('Y-m-d H:i:s'));

    // Recipients
    $recipients = get_option( 'aaa_report_email_recipients', get_bloginfo( 'admin_email' ) );
    $emails     = array_filter( array_map( 'trim', explode( ',', $recipients ) ) );

    // If the option is empty AND the admin email isn’t set, fall back to legacy list
    if ( empty( $emails ) ) {
        $emails = [
            'webmaster@lokeydelivery.com',
        ];
    }

    // Today's date & orders
    $today  = wp_date( 'Y-m-d' );
    $orders = aaa_get_orders_for_date( $today );
    error_log('[AAA Email Cron] Order count for ' . $today . ': ' . count($orders));

    if ( empty( $orders ) ) {
        return;
    }

    // Build the HTML body
    ob_start();
        echo '<h1>AAA Daily Report – ' . wp_date( 'F j, Y' ) . '</h1>';
        aaa_render_report_summary(           $orders );
        aaa_render_top_summary_section(      $orders );
        aaa_render_orders_section_v3(        $orders );
        aaa_render_product_breakdown(        $orders );
        aaa_render_product_summary_table(    $orders );
        aaa_render_customer_summary_v2(      $orders );
        aaa_render_payment_summary_v2(       $orders );
        aaa_render_brands_categories_summary_v2( $orders );
        aaa_render_delivery_city_report(     $orders );
        aaa_render_refunds_and_cancels_v2(   $today );
    $html = ob_get_clean();

    // ---  Attachments  ---
    $attachments = [];

    // 4) Generate PDF
    require_once plugin_dir_path( __FILE__ ) . 'admin-product-pdf.php';
    $pdf_content = aaa_render_pdf_report( $orders, $today );
    $temp_pdf = tempnam( sys_get_temp_dir(), "aaa-report-{$today}" );
    if ( $temp_pdf ) {
        file_put_contents( $temp_pdf, $pdf_content );
        $attachments[] = $temp_pdf;

        // Log
        if ( function_exists( 'wc_get_logger' ) ) {
            wc_get_logger()->info( "PDF generated: {$temp_pdf}", [ 'source' => 'aaa-daily-report' ] );
        } else {
            error_log( "[AAA Daily Report] PDF generated: {$temp_pdf}" );
        }
    }

    // 5) Generate Excel

    $spreadsheet = new Spreadsheet();
    $spreadsheet->getProperties()
        ->setCreator( 'AAA Daily Reporting' )
        ->setTitle( "Report {$today}" );

    // -- Sheet 1: Top Metrics --
    $sheet = $spreadsheet->setActiveSheetIndex( 0 );
    $sheet->setTitle( 'Top Metrics' );

    // Headers
    $sheet->fromArray( [
        [ 'Metric',      'Value' ],
        [ 'Total Orders',         count( $orders ) ],
        // ... you can add more metrics here to match your export ...
    ], null, 'A1' );

    // -- Sheet 2: Orders Detail --
    $sheet2 = $spreadsheet->createSheet();
    $sheet2->setTitle( 'Orders Detail' );
    $headers = [
        'Date','Order ID','Customer','Total','Discount','COGS','Profit',
        '# Items','Unique Items','Payment','City','Time to Complete'
    ];
    $sheet2->fromArray( $headers, null, 'A1' );

    $row = 2;
    foreach ( $orders as $order ) {
        $start   = $order->get_date_created()?->getTimestamp() ?? 0;
        $end     = $order->get_date_completed()?->getTimestamp()
                 ?: ( $order->get_date_paid()?->getTimestamp() ?? 0 );
        $minutes = ( $start && $end ) ? round( ( $end - $start ) / 60 ) : '';

        // Quantity and unique
        $items = $order->get_items();
        $qty   = 0;
        foreach ( $items as $item ) {
            $qty += $item->get_quantity();
        }
        $uniq     = count( $items );
        // Placeholder for COGS logic
        $cogs   = 0;
        $profit   = $order->get_total() - $order->get_discount_total() - $cogs;

        $data = [
            $order->get_date_created()?->date_i18n( 'Y-m-d H:i' ) ?? '',
            $order->get_id(),
            $order->get_formatted_billing_full_name(),
            $order->get_total(),
            $order->get_discount_total(),
            $cogs,
            $profit,
            $qty,
            $uniq,
            $order->get_payment_method_title(),
            $order->get_shipping_city(),
            $minutes,
        ];
        $sheet2->fromArray( $data, null, 'A' . $row);
        $row++;
    }

    $temp_xlsx = wp_tempnam( "aaa-report-{$today}.xlsx" );
    if ( $temp_xlsx ) {
        ( new Xlsx( $spreadsheet ) )->save( $temp_xlsx );
        $attachments[] = $temp_xlsx;
        if ( function_exists( 'wc_get_logger' ) ) {
            wc_get_logger()->info( "XLSX generated: {$temp_xlsx}", [ 'source' => 'aaa-daily-report' ] );
        } else {
            error_log( "[AAA Daily Report] XLSX generated: {$temp_xlsx}" );
        }
    }

    // 6) Send the email
    $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
    wp_mail( $emails,
             'AAA Daily Report for ' . wp_date( 'F j, Y' ),
             $html,
             $headers,
             $attachments
    );

    // 7) Clean up
    foreach ( $attachments as $file ) {
        @unlink( $file );
    }
}
