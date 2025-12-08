<?php
/**
 * File Path: /aaa-order-workflow/includes/helpers/payment/class-aaa-oc-payment-status-label.php
 *
 * Purpose:
 * Map payment status values (unpaid, partial, paid) to styled pill HTML.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Payment_Status_Label {

    public static function get( $status_key ) {
        if ( empty( $status_key ) ) {
            $status_key = 'unpaid';
        }

        $status = strtolower( trim( (string) $status_key ) );
        switch ( $status ) {
            case 'paid':
                return '<span style="background:green; color:#fff; padding:5px 10px; border-radius:4px;">PAID</span>';
            case 'partial':
                return '<span style="background:orange; color:#fff; padding:5px 10px; border-radius:4px;">PARTIAL</span>';
            case 'unpaid':
            default:
                return '<span style="background:red; color:#fff; padding:5px 10px; border-radius:4px;">UNPAID</span>';
        }
    }
}
