<?php
/**
 * Admin post handler for PayConfirm.
 *
 * This class merges the DEV "Force" capability with the LIVE
 * requirement that payment‑confirmation posts are never public. It
 * handles two routes:
 *   1) `admin_post_aaa_oc_payconfirm_update_and_match` for saving edited
 *      fields and re‑running the matcher.
 *   2) `admin_post_aaa_oc_payconfirm_force` for forcing a match to a
 *      specific order.
 *
 * Posts are always set to `private` after processing so that they are
 * inaccessible to the front‑end, while still being available through
 * the REST fallback route.
 *
 * Version: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_PayConfirm_AdminPost {

    /**
     * Wire up our handlers. Called from the loader on admin_init.
     */
    public static function init() {
        add_action( 'admin_post_aaa_oc_payconfirm_update_and_match', [ __CLASS__, 'handle' ] );
        // Expose the Force action (borrowed from the DEV branch).
        add_action( 'admin_post_aaa_oc_payconfirm_force',            [ __CLASS__, 'force' ] );
    }

    /**
     * Handle updates from the PayConfirm metabox.
     *
     * Saves the edited fields, normalizes the sent_on timestamp to the
     * original email time, re‑runs the matcher, updates match metas and
     * forces the post to private status. Redirects back to the edit
     * screen with a success flag.
     */
    public static function handle() {
        $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_die( 'No permission' );
        }
        if ( ! isset( $_POST['aaa_oc_pc_nonce'] ) || ! wp_verify_nonce( $_POST['aaa_oc_pc_nonce'], 'aaa_oc_pc_save' ) ) {
            wp_die( 'Nonce check failed' );
        }

        // Gather and sanitize submitted fields.
        $f = [
            'payment_method'     => sanitize_text_field( $_POST['pc_payment_method'] ?? '' ),
            'account_name'       => sanitize_text_field( $_POST['pc_account_name'] ?? '' ),
            'amount'             => (float) ( $_POST['pc_amount'] ?? 0 ),
            'sent_on'            => trim( (string) ( $_POST['pc_sent_on'] ?? '' ) ),
            'transaction_number' => trim( (string) ( $_POST['pc_txn'] ?? '' ) ),
            'memo'               => sanitize_text_field( $_POST['pc_memo'] ?? '' ),
        ];

        // Persist the metas on the post.
        update_post_meta( $post_id, '_pc_payment_method', $f['payment_method'] );
        update_post_meta( $post_id, '_pc_account_name',   $f['account_name']   );
        update_post_meta( $post_id, '_pc_amount',         $f['amount']         );
        update_post_meta( $post_id, '_pc_sent_on',        $f['sent_on']        );
        update_post_meta( $post_id, '_pc_txn',            $f['transaction_number'] );
        update_post_meta( $post_id, '_pc_memo',           $f['memo']           );

        // Keep the post title tidy.
        wp_update_post( [
            'ID'         => $post_id,
            'post_title' => AAA_OC_PayConfirm_Parser::title( $f ),
        ] );

        // Normalize sent_on to the original email time if available.
        $pd_gmt = get_post_field( 'post_date_gmt', $post_id );
        if ( $pd_gmt ) {
            $f['sent_on'] = gmdate( 'Y-m-d H:i:s', strtotime( $pd_gmt ) );
            update_post_meta( $post_id, '_pc_sent_on', $f['sent_on'] );
        }

        // Re‑run the matcher using the new fields.
        $result = AAA_OC_PayConfirm_Matcher::attempt( $post_id, $f );
        update_post_meta( $post_id, '_pc_last_match_result', $result );

        // Normalize status/reason/confidence for UI.
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
        // Keep the legacy _pc_match_method meta in sync for back‑compat.
        update_post_meta( $post_id, '_pc_match_method', $reason );

        // Ensure the post is private (never publish). Do not touch trash or auto‑draft.
        $p = get_post( $post_id );
        if ( $p && ! in_array( $p->post_status, [ 'trash', 'auto-draft' ], true ) && 'private' !== $p->post_status ) {
            wp_update_post( [ 'ID' => $post_id, 'post_status' => 'private' ] );
        }

        // Redirect back to the editor with a flag so notices can be shown.
        wp_safe_redirect( add_query_arg( [ 'pc_updated' => 1 ], wp_get_referer() ?: admin_url( 'post.php?post=' . $post_id . '&action=edit' ) ) );
        exit;
    }

    /**
     * Force a specific order match on a PayConfirm post.
     *
     * This handler replicates the DEV branch’s capability to force a
     * payment confirmation to match a given order. After applying the
     * forced match it updates the relevant metas and ensures the post
     * remains private.
     */
    public static function force() {
        $post_id  = isset( $_GET['post_id'] )  ? (int) $_GET['post_id']  : 0;
        $order_id = isset( $_GET['order_id'] ) ? (int) $_GET['order_id'] : 0;
        if ( ! $post_id || ! $order_id ) {
            wp_die( 'Missing parameters' );
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            wp_die( 'No permission' );
        }
        $nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
        if ( ! wp_verify_nonce( $nonce, 'aaa_oc_pc_force_' . $post_id ) ) {
            wp_die( 'Bad nonce' );
        }

        // Build the field array from stored metas.
        $f = [
            'payment_method'     => (string) get_post_meta( $post_id, '_pc_payment_method', true ),
            'account_name'       => (string) get_post_meta( $post_id, '_pc_account_name',   true ),
            'amount'             => (float)  get_post_meta( $post_id, '_pc_amount',         true ),
            'sent_on'            => (string) get_post_meta( $post_id, '_pc_sent_on',        true ),
            'transaction_number' => (string) get_post_meta( $post_id, '_pc_txn',            true ),
            'memo'               => (string) get_post_meta( $post_id, '_pc_memo',           true ),
        ];
        // Normalize sent_on to email time if available.
        $pd_gmt = get_post_field( 'post_date_gmt', $post_id );
        if ( $pd_gmt ) {
            $f['sent_on'] = gmdate( 'Y-m-d H:i:s', strtotime( $pd_gmt ) );
            update_post_meta( $post_id, '_pc_sent_on', $f['sent_on'] );
        }

        // Apply the forced match via the matcher helper. Score = 1.0, method = 'forced'.
        $res = AAA_OC_PC_Apply::apply( $order_id, $post_id, $f, 1.0, 'forced' );

        // Update metas to reflect forced state.
        update_post_meta( $post_id, '_pc_last_match_result', $res );
        update_post_meta( $post_id, '_pc_match_status',      'matched' );
        update_post_meta( $post_id, '_pc_match_reason',      'forced' );
        update_post_meta( $post_id, '_pc_match_confidence',  1.0 );
        update_post_meta( $post_id, '_pc_matched_order_id',  (int) $order_id );
        update_post_meta( $post_id, '_pc_match_method',      'forced' );

        // Ensure the post is private (never public). Do not alter trash/auto‑draft.
        $p = get_post( $post_id );
        if ( $p && ! in_array( $p->post_status, [ 'trash', 'auto-draft' ], true ) && 'private' !== $p->post_status ) {
            wp_update_post( [ 'ID' => $post_id, 'post_status' => 'private' ] );
        }

        // Redirect back to the referring page with a flag.
        wp_safe_redirect( add_query_arg( [ 'pc_forced' => $order_id ], wp_get_referer() ?: admin_url( 'post.php?post=' . $post_id . '&action=edit' ) ) );
        exit;
    }
}
