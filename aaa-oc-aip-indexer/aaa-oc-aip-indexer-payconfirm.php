<?php
/**
 * Payment confirmation meta synchronisation for the AAA OC AIP Indexer Bridge.
 *
 * Duplicates selected private payment confirmation meta keys to public keys
 * so they appear in the AIP plugin’s indexing interface.  Placed in a
 * separate module to keep the core bridge file compact.  Only runs
 * when a payment confirmation post is saved or updated.
 *
 * File: /wp-content/plugins/aaa-oc-aip-indexer-payconfirm.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'AAA_OC_AIP_INDEXER_PAYCONFIRM_LOADED' ) ) {
    return;
}
define( 'AAA_OC_AIP_INDEXER_PAYCONFIRM_LOADED', true );

if ( ! defined( 'AAA_OC_AIP_INDEXER_PAYCONFIRM_DEBUG' ) ) {
    define( 'AAA_OC_AIP_INDEXER_PAYCONFIRM_DEBUG', true );
}

/**
 * Handles copying private payment confirmation meta to public meta keys.
 */
class AAA_OC_AIP_Indexer_PayConfirm_Sync {

    /**
     * Bootstraps the sync by hooking into the save action.
     */
    public static function init() {
        add_action( 'save_post_payment-confirmation', [ __CLASS__, 'copy_meta' ], 20, 3 );
    }

    /**
     * Copies selected private meta keys to public counterparts.
     *
     * @param int      $post_id Post ID being saved.
     * @param \WP_Post $post    Post object.
     * @param bool     $update  Whether this is an update.
     */
    public static function copy_meta( $post_id, $post, $update ) {
        // Skip autosaves and ensure correct post type.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( $post->post_type !== 'payment-confirmation' ) {
            return;
        }
        $keys = [
            '_pc_payment_method',
            '_pc_account_name',
            '_pc_amount',
            '_pc_sent_on',
            '_pc_txn',
            '_pc_memo',
            '_pc_matched_order_id',
            '_pc_match_confidence',
            '_pc_match_method',
            '_pc_match_status',
            '_pc_match_reason',
            '_pc_last_match_result',
        ];
        foreach ( $keys as $private ) {
            $value = get_post_meta( $post_id, $private, true );
            if ( empty( $value ) || is_array( $value ) || is_object( $value ) ) {
                continue;
            }
            update_post_meta( $post_id, ltrim( $private, '_' ), $value );
        }
        if ( AAA_OC_AIP_INDEXER_PAYCONFIRM_DEBUG ) {
            error_log( '[AIP PayConfirm] Synced meta for post ' . $post_id );
        }
    }
}

AAA_OC_AIP_Indexer_PayConfirm_Sync::init();