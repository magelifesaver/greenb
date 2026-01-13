<?php
/**
 * Payment confirmation summary meta for AIP indexing.
 *
 * File: /wp-content/plugins/aaa-oc-aip-indexer-2.1.0/aaa-oc-aip-indexer-payconfirm-summary.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'AAA_OC_AIP_INDEXER_PAYCONFIRM_SUMMARY_LOADED' ) ) {
    return;
}
define( 'AAA_OC_AIP_INDEXER_PAYCONFIRM_SUMMARY_LOADED', true );

class AAA_OC_AIP_Indexer_PayConfirm_Summary {

    public static function init() {
        add_action( 'aaa_oc_pc_process_post', [ __CLASS__, 'generate_summary' ], 30, 1 );
        add_action( 'save_post_payment-confirmation', [ __CLASS__, 'generate_summary' ], 30, 3 );
    }

    public static function generate_summary( $post_id, $post = null, $update = null ) {
        $post_id = (int) $post_id;
        if ( ! $post_id ) {
            return;
        }

        $post_obj = $post ?: get_post( $post_id );
        if ( ! $post_obj || $post_obj->post_type !== 'payment-confirmation' ) {
            return;
        }

        $payment_method = get_post_meta( $post_id, '_pc_payment_method', true );
        $amount         = get_post_meta( $post_id, '_pc_amount', true );
        $sent_on        = get_post_meta( $post_id, '_pc_sent_on', true );
        $txn            = get_post_meta( $post_id, '_pc_txn', true );
        $memo           = get_post_meta( $post_id, '_pc_memo', true );
        $match_status   = get_post_meta( $post_id, '_pc_match_status', true );
        $order_id       = get_post_meta( $post_id, '_pc_matched_order_id', true );
        $account_name   = get_post_meta( $post_id, '_pc_account_name', true );

        $amount_num   = is_numeric( $amount ) ? (float) $amount : 0.0;
        $currency_sym = '$';
        $order_number = '';

        if ( $order_id ) {
            $order = wc_get_order( $order_id );
            if ( $order instanceof WC_Order ) {
                $order_number = $order->get_order_number();
                $cur = $order->get_currency();
                $currency_sym = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol( $cur ) : '$';
            }
        }

        $amount_str = $currency_sym . number_format( $amount_num, 2 );

        $sent_str = '';
        if ( $sent_on && strtotime( $sent_on ) ) {
            $sent_str = date( 'Y-m-d H:i:s', strtotime( $sent_on ) );
        } else {
            $post_date = get_post_field( 'post_date', $post_id );
            if ( $post_date ) {
                $sent_str = date( 'Y-m-d H:i:s', strtotime( $post_date ) );
            }
        }

        $parts = [];
        $parts[] = $order_number ? 'Payment confirmation for Order #' . $order_number : 'Payment confirmation (unmatched)';
        $parts[] = 'Amount: ' . $amount_str;
        if ( $payment_method ) { $parts[] = 'Method: ' . ucfirst( $payment_method ); }
        if ( $sent_str ) { $parts[] = 'Sent: ' . $sent_str; }
        if ( $match_status ) { $parts[] = 'Status: ' . ucfirst( $match_status ); }
        if ( $txn ) { $parts[] = 'Txn: ' . $txn; }
        if ( $account_name ) { $parts[] = 'Account: ' . $account_name; }

        $memo_trim = is_string( $memo ) ? trim( $memo ) : '';
        if ( $memo_trim !== '' && strlen( $memo_trim ) < 200 ) {
            $parts[] = 'Memo: ' . $memo_trim;
        }

        $summary  = implode( ' | ', $parts );
        $existing = (string) get_post_meta( $post_id, 'aip_paymentconfirmation_summary', true );

        if ( $existing !== $summary ) {
            update_post_meta( $post_id, 'aip_paymentconfirmation_summary', $summary );
        }
    }
}

AAA_OC_AIP_Indexer_PayConfirm_Summary::init();
