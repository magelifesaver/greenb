<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/inc/class-aaa-oc-payconfirm-hook.php
 * Purpose: Listen to payment-confirmation saves; parse, retitle, try match, update index; then publish if draft.
 * Version: 1.0.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_PayConfirm_Hook {
    /**
     * Initialize the save_post hook.
     */
    public static function init() {
        add_action( 'save_post', [ __CLASS__, 'on_save' ], 20, 3 );
    }

    /**
     * Handle save_post for payment confirmations.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     * @param bool    $update  Whether it is an update or not.
     */
    public static function on_save( $post_id, $post, $update ) {
        if ( wp_is_post_revision( $post_id ) || 'payment-confirmation' !== $post->post_type ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $subject = (string) $post->post_title;
        $content = (string) $post->post_content;
        $looks_payment = ( stripos( $subject . $content, 'zelle' ) !== false ) || ( stripos( $subject, 'payment' ) !== false );
        if ( ! $looks_payment ) {
            return;
        }

        $f = AAA_OC_PayConfirm_Parser::parse( $content, $subject );

        // Save parsed metas
        update_post_meta( $post_id, '_pc_payment_method', $f['payment_method'] );
        update_post_meta( $post_id, '_pc_account_name',   $f['account_name'] );
        update_post_meta( $post_id, '_pc_amount',         $f['amount'] );
        update_post_meta( $post_id, '_pc_sent_on',        $f['sent_on'] );
        update_post_meta( $post_id, '_pc_txn',            $f['transaction_number'] );
        update_post_meta( $post_id, '_pc_memo',           $f['memo'] );

        // Retitle
        $new_title = AAA_OC_PayConfirm_Parser::title( $f );
        remove_action( 'save_post', [ __CLASS__, 'on_save' ], 20 );
        wp_update_post( [ 'ID' => $post_id, 'post_title' => $new_title ] );
        add_action( 'save_post', [ __CLASS__, 'on_save' ], 20, 3 );

        // Attempt match
        $result = AAA_OC_PayConfirm_Matcher::attempt( $post_id, $f );
        update_post_meta( $post_id, '_pc_last_match_result', $result );

        // Normalize status/reason/confidence
        $matched    = ! empty( $result['matched'] );
        $candidates = isset( $result['candidates'] ) && is_array( $result['candidates'] ) && count( $result['candidates'] ) > 0;
        $status     = $matched ? 'matched' : ( $candidates ? 'partial' : 'unmatched' );
        $reason     = isset( $result['method'] ) ? (string) $result['method'] : ( $candidates ? 'amount_multi' : 'name_fuzzy' );
        $confidence = isset( $result['confidence'] ) ? (float) $result['confidence'] : ( $matched ? 1.0 : ( $candidates ? 0.6 : 0.4 ) );

        update_post_meta( $post_id, '_pc_match_status',     $status );
        update_post_meta( $post_id, '_pc_match_reason',     $reason );
        update_post_meta( $post_id, '_pc_match_confidence', $confidence );
        if ( $matched && ! empty( $result['order_id'] ) ) {
            update_post_meta( $post_id, '_pc_matched_order_id', (int) $result['order_id'] );
        }
        // Keep _pc_match_method in sync for backwards compatibility
        update_post_meta( $post_id, '_pc_match_method', $reason );

        // Force private after processing (never public)
        if ( ! in_array( $post->post_status, [ 'trash', 'auto-draft' ], true ) && 'private' !== $post->post_status ) {
            remove_action( 'save_post', [ __CLASS__, 'on_save' ], 20 );
            wp_update_post( [ 'ID' => $post_id, 'post_status' => 'private' ] );
            add_action( 'save_post', [ __CLASS__, 'on_save' ], 20, 3 );
        }

        if ( defined( 'AAA_OC_PAYCONFIRM_DEBUG' ) && AAA_OC_PAYCONFIRM_DEBUG ) {
            error_log( '[PayConfirm] save status=' . $status . ' reason=' . $reason . ' conf=' . $confidence );
        }
    }
}