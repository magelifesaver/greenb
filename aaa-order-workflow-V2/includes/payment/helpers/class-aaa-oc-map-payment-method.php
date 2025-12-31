<?php
/**
 * File Path: /aaa-order-workflow/includes/helpers/payment/class-aaa-oc-map-payment-method.php
 *
 * Purpose:
 * Normalize inconsistent payment method labels into consistent short codes (used in pills, UI, etc.).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Map_Payment_Method {

    /**
     * Convert a full payment method label into a short, uppercase code.
     */
    public static function to_code( $method_title ) {
        if ( ! $method_title ) return 'UNKNOWN';

        $title = strtolower( trim( $method_title ) );
        $flat  = str_replace( [ '-', '_', ' ' ], '', $title );

        if ( strpos( $flat, 'cashapp' ) !== false || strpos( $title, 'cash app' ) !== false ) {
            return 'CASH APP';
        }
        if ( strpos( $title, 'cash on delivery' ) !== false || $flat === 'cash' ) {
            return 'CASH';
        }
        if ( strpos( $flat, 'zelle' ) !== false ) {
            return 'ZELLE';
        }
        if ( strpos( $flat, 'applepay' ) !== false ) {
            return 'APPLE PAY';
        }
        if ( strpos( $flat, 'venmo' ) !== false ) {
            return 'VENMO';
        }
        if ( strpos( $flat, 'creditcard' ) !== false || strpos( $title, 'credit card' ) !== false ) {
            return 'CC';
        }
        if ( strpos( $flat, 'accountfunds' ) !== false || strpos( $title, 'account funds' ) !== false ) {
            return 'FUNDS';
        }
        if ( strpos( $flat, 'storecredit' ) !== false || strpos( $title, 'store credit' ) !== false ) {
            return 'STORE CREDIT';
        }
        return 'OTHER';
    }
}
