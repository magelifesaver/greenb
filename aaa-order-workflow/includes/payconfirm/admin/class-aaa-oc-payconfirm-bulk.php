<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/admin/class-aaa-oc-payconfirm-bulk.php
 * Purpose: Bulk actions: Parse & Publish, and Re-run Match (use existing metas).
 * Version: 1.1.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_PayConfirm_Bulk {
    /**
     * Initialize bulk action filters and notices.
     */
    public static function init() {
        add_filter( 'bulk_actions-edit-payment-confirmation', [ __CLASS__, 'register_action' ] );
        add_filter( 'handle_bulk_actions-edit-payment-confirmation', [ __CLASS__, 'handle_action' ], 10, 3 );
        add_action( 'admin_notices', [ __CLASS__, 'admin_notice' ] );
    }

    /**
     * Register bulk actions for the payment confirmation list table.
     *
     * @param array $actions Existing bulk actions.
     *
     * @return array Modified bulk actions.
     */
    public static function register_action( $actions ) {
        $actions['aaa_oc_payconfirm_parse_publish'] = 'Parse & Publish (PayConfirm)';
        $actions['aaa_oc_payconfirm_rerun_match']   = 'Re-run Match (Use Edited Meta)';
        return $actions;
    }

    /**
     * Handle the custom bulk actions.
     *
     * @param string $redirect_url URL to redirect to.
     * @param string $action       Action name.
     * @param array  $post_ids     Array of selected post IDs.
     *
     * @return string Modified redirect URL.
     */
    public static function handle_action( $redirect_url, $action, $post_ids ) {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return add_query_arg( [ 'pc_bulk_err' => count( (array) $post_ids ) ], $redirect_url );
        }

        $c_ok = 0;
        $c_partial = 0;
        $c_unmatched = 0;
        $c_err = 0;

        foreach ( (array) $post_ids as $post_id ) {
            $post = get_post( $post_id );
            if ( ! $post || 'payment-confirmation' !== $post->post_type ) {
                $c_err++;
                continue;
            }

            if ( 'aaa_oc_payconfirm_parse_publish' === $action ) {
                $f = AAA_OC_PayConfirm_Parser::parse( (string) $post->post_content, (string) $post->post_title );

                // Force SENT ON to the email's original time (Postie post_date_gmt)
                $pd_gmt = get_post_field( 'post_date_gmt', $post_id );
                if ( $pd_gmt ) {
                    $f['sent_on'] = gmdate( 'Y-m-d H:i:s', strtotime( $pd_gmt ) );
                }

                update_post_meta( $post_id, '_pc_payment_method', $f['payment_method'] );
                update_post_meta( $post_id, '_pc_account_name',   $f['account_name'] );
                update_post_meta( $post_id, '_pc_amount',         $f['amount'] );
                update_post_meta( $post_id, '_pc_sent_on',        $f['sent_on'] );
                update_post_meta( $post_id, '_pc_txn',            $f['transaction_number'] );
                update_post_meta( $post_id, '_pc_memo',           $f['memo'] );

                wp_update_post( [ 'ID' => $post_id, 'post_title' => AAA_OC_PayConfirm_Parser::title( $f ) ] );

            } else {
                $f = [
                    'payment_method'     => trim( (string) get_post_meta( $post_id, '_pc_payment_method', true ) ),
                    'account_name'       => trim( (string) get_post_meta( $post_id, '_pc_account_name',   true ) ),
                    'amount'             => (float) get_post_meta( $post_id, '_pc_amount',               true ),
                    'sent_on'            => trim( (string) get_post_meta( $post_id, '_pc_sent_on',        true ) ),
                    'transaction_number' => trim( (string) get_post_meta( $post_id, '_pc_txn',            true ) ),
                    'memo'               => trim( (string) get_post_meta( $post_id, '_pc_memo',           true ) ),
                ];

                $pd_gmt = get_post_field( 'post_date_gmt', $post_id );
                if ( $pd_gmt ) {
                    $f['sent_on'] = gmdate( 'Y-m-d H:i:s', strtotime( $pd_gmt ) );
                }
            }

            $result = AAA_OC_PayConfirm_Matcher::attempt( $post_id, $f );
            update_post_meta( $post_id, '_pc_last_match_result', $result );

            $matched    = ! empty( $result['matched'] );
            $candidates = ! empty( $result['candidates'] );
            $status     = $matched ? 'matched' : ( $candidates ? 'partial' : 'unmatched' );
            $reason     = $result['method'] ?? ( $candidates ? 'amount_multi' : 'name_fuzzy' );
            $confidence = $result['confidence'] ?? ( $matched ? 1.0 : ( $candidates ? 0.6 : 0.4 ) );

            update_post_meta( $post_id, '_pc_match_status',     $status );
            update_post_meta( $post_id, '_pc_match_reason',     $reason );
            update_post_meta( $post_id, '_pc_match_confidence', $confidence );
            if ( $matched && ! empty( $result['order_id'] ) ) {
                update_post_meta( $post_id, '_pc_matched_order_id', (int) $result['order_id'] );
            }
            update_post_meta( $post_id, '_pc_match_method', $reason ); // back-compat mirror

            // Force private (never public)
            $p = get_post( $post_id );
            if ( $p && ! in_array( $p->post_status, [ 'trash', 'auto-draft' ], true ) && 'private' !== $p->post_status ) {
                wp_update_post( [ 'ID' => $post_id, 'post_status' => 'private' ] );
            }

            if ( $matched ) {
                $c_ok++;
            } elseif ( $candidates ) {
                $c_partial++;
            } else {
                $c_unmatched++;
            }
        }

        return add_query_arg( [
            'pc_bulk_ok'      => $c_ok,
            'pc_bulk_partial' => $c_partial,
            'pc_bulk_unmatch' => $c_unmatched,
            'pc_bulk_err'     => $c_err,
        ], $redirect_url );
    }

    /**
     * Display admin notice after bulk operations.
     */
    public static function admin_notice() {
        $screen = get_current_screen();
        if ( ! $screen || 'edit-payment-confirmation' !== $screen->id ) {
            return;
        }

        $ok = (int) ( $_GET['pc_bulk_ok']      ?? 0 );
        $p  = (int) ( $_GET['pc_bulk_partial'] ?? 0 );
        $u  = (int) ( $_GET['pc_bulk_unmatch' ] ?? 0 );
        $e  = (int) ( $_GET['pc_bulk_err']     ?? 0 );

        if ( $ok || $p || $u || $e ) {
            printf(
                '<div class="notice notice-success is-dismissible"><p><strong>PayConfirm:</strong> Matched: %d, Partial: %d, Unmatched: %d, Errors: %d.</p></div>',
                $ok,
                $p,
                $u,
                $e
            );
        }
    }
}