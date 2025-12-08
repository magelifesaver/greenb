<?php
/**
 * File: includes/helpers/payment/class-aaa-oc-tip-cleanup.php
 * Purpose: Ensures _wpslash_tip is removed if the "Tip" fee is deleted from the order.
 * Context: Prevents outdated tip meta from causing index/reporting mismatches.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Tip_Cleanup {

    public static function init() {
        add_action( 'save_post_shop_order', [ __CLASS__, 'maybe_remove_tip_meta' ], 15 );
    }

    public static function maybe_remove_tip_meta( $post_id ) {
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        $order = wc_get_order( $post_id );
        if ( ! $order ) return;

        $has_tip_fee = false;
        foreach ( $order->get_fees() as $fee ) {
            if ( strtolower( $fee->get_name() ) === 'tip' ) {
                $has_tip_fee = true;
                break;
            }
        }

        if ( ! $has_tip_fee ) {
            delete_post_meta( $post_id, '_wpslash_tip' );
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log("[TIP CLEANUP] Tip fee missing. Deleted _wpslash_tip for order #$post_id");
            }
        }
    }
}

AAA_OC_Tip_Cleanup::init();
