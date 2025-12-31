<?php
/**
 * File Path: /aaa-order-workflow/includes/helpers/class-aaa-oc-map-created-via.php
 *
 * Purpose:
 * Legacy mapper for `_created_via` values. Superseded by AAA_OC_Map_Order_Source,
 * but kept for backward compatibility.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Map_Created_Via {
    public static function to_code( $raw ) {
        if ( ! $raw ) return 'UNKNOWN';
        $flat = strtolower( trim( $raw ) );

        $map = [
            'phone'                => 'PH',
            'weedmaps'             => 'WM',
            'checkout'             => 'WEB',
            'store-api'            => 'WEB',
            'wc/store'             => 'WEB',
            'admin'                => 'ADMIN',
            'aaa-order-creator-v4' => 'OC',
        ];

        return $map[ $flat ] ?? strtoupper( str_replace( '_', ' ', $flat ) );
    }
}
