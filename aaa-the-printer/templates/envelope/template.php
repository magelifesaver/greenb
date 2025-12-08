<?php
/**
 * /wp-content/plugins/aaa-the-printer/templates/envelope/template.php
 * 
 * This defines two functions:
 * - aaa_lpm_get_envelope_html( $order )
 * - aaa_lpm_get_envelope_template_html( $orders, $envelope_id, $driver_name, $delivery_date )
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'aaa_lpm_get_envelope_html' ) ) {
    function aaa_lpm_get_envelope_html( $order ) {
        $order_number     = $order->get_order_number();
        $customer_name    = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
        $customer_address = $order->get_billing_address_1();
        $shipping_city    = $order->get_shipping_city();

        $payment_status   = strtoupper( $order->get_meta( 'aaa_oc_payment_status' ) );
        $payment_balance  = $order->get_meta( 'aaa_oc_order_balance' );
        $order_total      = $order->get_meta( 'aaa_oc_order_total' );
        $admin_note       = $order->get_meta( 'aaa_oc_admin_note' );
        $order_note       = $order->get_meta( 'aaa_oc_order_note' );

        if ( empty( $payment_status ) ) {
            $payment_status = strtoupper( $order->get_status() );
        }

        if ( ! $order_total ) {
            $order_total = $order->get_total();
        }

        ob_start();
        ?>
        <div style="border:1px dotted #000; margin:10px 0; padding:10px; font-size:14px; width:100%;">
            <div style="font-weight:bold;"><?php echo esc_html( $customer_name ); ?></div>
            <div><?php echo esc_html( $customer_address ); ?></div>
            <div><?php echo esc_html( $shipping_city ); ?></div>
            <div>Total: <?php echo wc_price( $order_total ); ?></div>
            <div>Status: <?php echo esc_html( $payment_status ); ?></div>
            <div>Balance: <?php echo wc_price( $payment_balance ); ?></div>

            <?php if ( $admin_note ) : ?>
                <div>Note: <?php echo esc_html( $admin_note ); ?></div>
            <?php endif; ?>

            <?php if ( $order_note ) : ?>
                <div>Instructions: <?php echo esc_html( $order_note ); ?></div>
            <?php endif; ?>

            <div style="margin-top:8px;">
                <img src="https://barcode.tec-it.com/barcode.ashx?data=<?php echo rawurlencode( $order_number ); ?>&code=Code128"
                     alt="Barcode" style="height:50px;" />
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

if ( ! function_exists( 'aaa_lpm_get_envelope_template_html' ) ) {
    function aaa_lpm_get_envelope_template_html( $orders, $envelope_id, $driver_name = '', $delivery_date = '' ) {
        $print_date = date( 'F j, Y g:i A' );

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8" /><title>Envelope Printout</title></head>
        <body style="margin:0; padding:20px; font-family: Arial, sans-serif; writing-mode: vertical-rl;">
        <div style="font-size:16px; font-weight:bold; margin-bottom:20px; border-bottom:2px solid #000;">
            Envelope ID: <?php echo esc_html( $envelope_id ); ?><br />
            Print Date: <?php echo esc_html( $print_date ); ?><br />
            <?php if ( $driver_name ) : ?>Driver: <?php echo esc_html( $driver_name ); ?><br /><?php endif; ?>
            <?php if ( $delivery_date ) : ?>Delivery Date: <?php echo esc_html( $delivery_date ); ?><?php endif; ?>
        </div>

        <?php foreach ( $orders as $order ) : ?>
            <?php echo aaa_lpm_get_envelope_html( $order ); ?>
        <?php endforeach; ?>

        <div style="margin-top:40px; text-align:center;">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?php echo rawurlencode( $envelope_id ); ?>"
                 alt="QR Code" style="width:100px; height:100px;" />
        </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
