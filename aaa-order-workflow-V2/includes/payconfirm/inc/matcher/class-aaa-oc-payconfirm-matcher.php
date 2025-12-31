<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/inc/class-aaa-oc-payconfirm-matcher.php
 * Purpose: Public API for PayConfirm matching. Delegates to helper classes.
 * Version: AAA_OC_VERSION
 */
if ( ! defined('ABSPATH') ) { exit; }

/* Load helpers (no loader changes needed) */
require_once __DIR__ . '/helpers/class-aaa-oc-pc-status.php';
require_once __DIR__ . '/helpers/class-aaa-oc-pc-window.php';
require_once __DIR__ . '/helpers/class-aaa-oc-pc-lookups.php';
require_once __DIR__ . '/helpers/class-aaa-oc-pc-score.php';
require_once __DIR__ . '/helpers/class-aaa-oc-pc-apply.php';

class AAA_OC_PayConfirm_Matcher {

	/**
	 * Attempt to match a Payment Confirmation post to an order.
	 * Strategy (unchanged): txn → memo# → score (amount+name+date) → unique amount
	 *
	 * @param int   $post_id Payment Confirmation post ID
	 * @param array $f       Parsed fields: payment_method, account_name, amount, sent_on, transaction_number, memo
	 * @return array { matched(bool), order_id?, candidates?, confidence(float), method(string) }
	 */
	public static function attempt( $post_id, array $f ) {
		if ( ! class_exists( 'WC_Order' ) ) {
			AAA_OC_PC_Apply::log( 'ABORT: WC_Order missing', $post_id, $f );
			return [ 'matched' => false ];
		}
		AAA_OC_PC_Apply::log( 'START attempt', $post_id, $f );

		// 1) Transaction number branch (raw or digits)
		$txn_raw    = trim( (string) ( $f['transaction_number'] ?? '' ) );
		$txn_digits = preg_replace( '/\D+/', '', $txn_raw );
		if ( $txn_raw !== '' || $txn_digits !== '' ) {
			if ( $oid = AAA_OC_PC_Lookups::find_order_by_txn( $txn_raw, $txn_digits ) ) {
				return AAA_OC_PC_Apply::apply( $oid, $post_id, $f, 1.0, 'txn' );
			}
		}

		// 2) Order ID inside memo
		$memo_order = AAA_OC_PC_Lookups::extract_order_id_from_text( (string) ( $f['memo'] ?? '' ) );
		if ( $memo_order && get_post_type( $memo_order ) === 'shop_order' ) {
			return AAA_OC_PC_Apply::apply( $memo_order, $post_id, $f, 1.0, 'memo_orderid' );
		}

		// 3) Scoring (amount + name + date); also saves ranked candidates to post meta
		$candidates = AAA_OC_PC_Score::collect_candidates( $f );
		$ranked     = AAA_OC_PC_Score::rank_candidates( $candidates, $f );
		update_post_meta( $post_id, '_pc_candidate_orders', $ranked );

		if ( ! empty( $ranked ) ) {
			$top    = $ranked[0];
			$second = $ranked[1] ?? null;
			$gap    = $second ? ( $top['score'] - $second['score'] ) : 999;

			if ( $top['score'] >= 90 || $gap >= 20 ) {
				return AAA_OC_PC_Apply::apply( (int) $top['order_id'], $post_id, $f, 1.0, 'scored' );
			}
			if ( count( $ranked ) === 1 && $top['score'] >= 75 ) {
				return AAA_OC_PC_Apply::apply( (int) $top['order_id'], $post_id, $f, 1.0, 'scored_one' );
			}
			return [ 'matched' => false, 'candidates' => array_column( $ranked, 'order_id' ), 'confidence' => 0.6, 'method' => 'amount_multi' ];
		}

		// 4) Unique-amount fallback (exact total)
		if ( ( $f['amount'] ?? '' ) !== '' ) {
			$ids = AAA_OC_PC_Lookups::find_orders_by_total( (float) $f['amount'] );
			if ( count( $ids ) === 1 ) {
				return AAA_OC_PC_Apply::apply( (int) $ids[0], $post_id, $f, 1.0, 'amount_unique' );
			}
			if ( count( $ids ) > 1 ) {
				return [ 'matched' => false, 'candidates' => array_map( 'intval', $ids ), 'confidence' => 0.6, 'method' => 'amount_multi' ];
			}
		}

		// 5) No match
		return [ 'matched' => false, 'confidence' => 0.4, 'method' => 'name_fuzzy' ];
	}
}
