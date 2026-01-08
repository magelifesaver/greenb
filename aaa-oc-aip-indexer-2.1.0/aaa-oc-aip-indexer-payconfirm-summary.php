<?php
/**
 * Payment confirmation summary for the AAA OC AIP Indexer Bridge.
 *
 * Generates a plain‑text summary for each payment confirmation post.
 * The summary includes the matched order number (if available),
 * payment amount, method, sent date and time, match status,
 * transaction ID and alias information.  A link back to the order
 * is provided by including the order number in the summary.  The
 * summary is saved as the `aip_paymentconfirmation_summary` meta key
 * whenever a payment confirmation is processed or saved.
 *
 * File: /wp-content/plugins/aaa-oc-aip-indexer-payconfirm-summary.php
 */

// Abort if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Prevent double loading.
if ( defined( 'AAA_OC_AIP_INDEXER_PAYCONFIRM_SUMMARY_LOADED' ) ) {
    return;
}
define( 'AAA_OC_AIP_INDEXER_PAYCONFIRM_SUMMARY_LOADED', true );

/**
 * Class responsible for building AI summaries for payment confirmations.
 *
 * Hooks into both the PayConfirm processor action and the generic
 * save_post hook to ensure the summary is created whenever meta
 * information is available.  The summary is stored as a public
 * meta key so the AIP plugin can index it.
 */
class AAA_OC_AIP_Indexer_PayConfirm_Summary {

    /**
     * Registers hooks for summary generation.
     */
    public static function init() {
        // After the PayConfirm parser/matcher runs, generate the summary.
        add_action( 'aaa_oc_pc_process_post', [ __CLASS__, 'generate_summary' ], 30, 1 );
        // Fallback: when a payment confirmation is saved or updated.
        add_action( 'save_post_payment-confirmation', [ __CLASS__, 'generate_summary' ], 30, 3 );
    }

    /**
     * Creates or updates the summary meta for a payment confirmation.
     *
     * @param int      $post_id Post ID being processed or saved.
     * @param \WP_Post $post    Post object (unused when called via aaa_oc_pc_process_post).
     * @param bool     $update  Whether this is an update (unused).
     */
    public static function generate_summary( $post_id, $post = null, $update = null ) {
        // Ensure we have a valid post ID.
        $post_id = (int) $post_id;
        if ( ! $post_id ) {
            return;
        }
        // Confirm this is a payment confirmation.
        $post_obj = $post ?: get_post( $post_id );
        if ( ! $post_obj || $post_obj->post_type !== 'payment-confirmation' ) {
            return;
        }

        // Retrieve meta fields.
        $payment_method = get_post_meta( $post_id, '_pc_payment_method', true );
        $amount         = get_post_meta( $post_id, '_pc_amount', true );
        $sent_on        = get_post_meta( $post_id, '_pc_sent_on', true );
        $txn            = get_post_meta( $post_id, '_pc_txn', true );
        $memo           = get_post_meta( $post_id, '_pc_memo', true );
        $match_status   = get_post_meta( $post_id, '_pc_match_status', true );
        $order_id       = get_post_meta( $post_id, '_pc_matched_order_id', true );
        $account_name   = get_post_meta( $post_id, '_pc_account_name', true );

        // Format numeric amount as currency.  Default to two decimals.
        $amount_num = is_numeric( $amount ) ? (float) $amount : 0.0;
        // Use WooCommerce currency symbol if an order is matched, else default to '$'.
        $currency_sym = '$';
        $order_number = '';
        if ( $order_id ) {
            $order = wc_get_order( $order_id );
            if ( $order instanceof \WC_Order ) {
                $order_number = $order->get_order_number();
                $cur  = $order->get_currency();
                if ( function_exists( 'get_woocommerce_currency_symbol' ) ) {
                    $currency_sym = get_woocommerce_currency_symbol( $cur );
                }
            }
        }
        $amount_str = $currency_sym . number_format( $amount_num, 2 );

        // Format sent date/time.  Attempt to parse _pc_sent_on or fall back to post date.
        $sent_str = '';
        if ( ! empty( $sent_on ) && strtotime( $sent_on ) ) {
            $sent_str = date( 'Y-m-d H:i:s', strtotime( $sent_on ) );
        } else {
            $post_date = get_post_field( 'post_date', $post_id );
            if ( $post_date ) {
                $sent_str = date( 'Y-m-d H:i:s', strtotime( $post_date ) );
            }
        }

        // Build the summary parts.
        $parts = [];
        if ( $order_number ) {
            $parts[] = 'Payment confirmation for Order #' . $order_number;
        } else {
            $parts[] = 'Payment confirmation (unmatched)';
        }
        // Amount and method.
        $parts[] = 'Amount: ' . $amount_str;
        if ( $payment_method ) {
            $parts[] = 'Method: ' . ucfirst( $payment_method );
        }
        if ( $sent_str ) {
            $parts[] = 'Sent: ' . $sent_str;
        }
        if ( $match_status ) {
            $parts[] = 'Status: ' . ucfirst( $match_status );
        }
        if ( $txn ) {
            $parts[] = 'Txn: ' . $txn;
        }
        if ( $account_name ) {
            $parts[] = 'Account: ' . $account_name;
        }
        if ( ! empty( $memo ) ) {
            // Only include memo if not excessively long.
            $memo_trim = is_string( $memo ) ? trim( $memo ) : '';
            if ( $memo_trim !== '' && strlen( $memo_trim ) < 200 ) {
                $parts[] = 'Memo: ' . $memo_trim;
            }
        }
        $summary = implode( ' | ', $parts );
        // Save the summary as public meta.
        update_post_meta( $post_id, 'aip_paymentconfirmation_summary', $summary );
    }
}

// Initialise the payment confirmation summary module.
AAA_OC_AIP_Indexer_PayConfirm_Summary::init();