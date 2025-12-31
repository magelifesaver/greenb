<?php
/**
 * File Path: /aaa-order-workflow/includes/helpers/class-aaa-oc-map-order-source.php
 *
 * Purpose:
 * Simplified mapping for order Source pills.
 * Only shows WEB, PHONE, WM, ADMIN, or OTHER for admin board at a glance.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Map_Order_Source {

    /**
     * Return normalized source code for pills.
     *
     * @param string $created_via_raw  Raw `_created_via` value.
     * @param string $source_type_raw  Raw `_wc_order_attribution_source_type` (ignored for pills).
     * @return array { source: string }
     */
    public static function map( $created_via_raw, $source_type_raw = '' ) {
        $created = strtolower( trim( (string) $created_via_raw ) );

        $out = [ 'source' => 'OTHER' ];

        // Website Checkout (classic, blocks, or store-api)
        if ( in_array( $created, [ 'checkout', 'store-api', 'wc/store', 'wc/checkout-blocks' ], true ) ) {
            $out['source'] = 'WEB';
            return $out;
        }

        // Order Creator
        if ( $created === 'aaa-order-creator-v4' || $created === 'phone' ) {
            $out['source'] = 'PHONE';
            return $out;
        }
        if ( $created === 'weedmaps' ) {
            $out['source'] = 'WM';
            return $out;
        }

        // Woo Admin
        if ( $created === 'admin' ) {
            $out['source'] = 'ADMIN';
            return $out;
        }

        return $out;
    }
}
