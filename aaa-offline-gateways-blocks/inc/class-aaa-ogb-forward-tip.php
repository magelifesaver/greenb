<?php
/**
 * File: wp-content/plugins/aaa-offline-gateways-blocks/includes/class-aaa-ogb-forward-tip.php
 * Purpose: Ensure tip from Blocks checkout is saved as _wpslash_tip meta.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'AAA_OGB_Forward_Tip' ) ) :

class AAA_OGB_Forward_Tip {

    const DEBUG_THIS_FILE = true;

    public static function init() {
        add_filter( 'woocommerce_store_api_checkout_update_order_meta', [ __CLASS__, 'forward' ], 10, 3 );
    }

    protected static function log( $m, $d = null ) {
        if ( ! self::DEBUG_THIS_FILE ) return;
        error_log( '[AAA-OGB][ForwardTip] ' . $m . ( $d !== null ? ' :: ' . ( is_scalar( $d ) ? $d : wp_json_encode( $d ) ) : '' ) );
    }

    protected static function normalize( $raw ) {
        if ( $raw === null || $raw === '' ) return null;
        $num = preg_replace( '/[^\d\.\-]/', '', (string) $raw );
        if ( $num === '' || ! is_numeric( $num ) ) return null;
        return round( (float) $num, 2 );
    }

    public static function forward( $metadata, $route, $request ) {
        if ( ! $request || ! method_exists( $request, 'get_json_params' ) ) return $metadata;
        $json = $request->get_json_params();

        $candidates = [];
        if ( isset( $json['extensions']['aaa_offline'] ) && is_array( $json['extensions']['aaa_offline'] ) ) {
            $ns = $json['extensions']['aaa_offline'];
            foreach ( [ '_wpslash_tip', 'tip', 'aaa_tip' ] as $k ) {
                if ( array_key_exists( $k, $ns ) ) $candidates[] = $ns[ $k ];
            }
        }
        foreach ( [ '_wpslash_tip', 'tip', 'aaa_tip', 'payment_tip' ] as $k ) {
            if ( array_key_exists( $k, $json ) ) $candidates[] = $json[ $k ];
        }

        foreach ( $candidates as $raw ) {
            $tip = self::normalize( $raw );
            if ( $tip !== null ) {
                $metadata[] = [ 'key' => '_wpslash_tip', 'value' => $tip ];
                self::log( 'Forwarded Blocks tip to _wpslash_tip', [ 'tip' => $tip ] );
                break;
            }
        }
        return $metadata;
    }
}

add_action( 'plugins_loaded', [ 'AAA_OGB_Forward_Tip', 'init' ] );
endif;
