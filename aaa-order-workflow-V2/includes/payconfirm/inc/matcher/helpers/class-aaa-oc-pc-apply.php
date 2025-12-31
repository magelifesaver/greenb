<?php
/**
 * Purpose: Apply a match + mirrors + index upserts + inbox upsert + logs.
 */
if ( ! defined('ABSPATH') ) { exit; }

class AAA_OC_PC_Apply {

	public static function apply( $order_id, $post_id, array $f, $conf, $why ) {
		$pd_gmt = get_post_field( 'post_date_gmt', $post_id );
		if ( $pd_gmt ) { $f['sent_on'] = gmdate( 'Y-m-d H:i:s', strtotime( $pd_gmt ) ); }

		// Persist on PC post
		self::pm( $post_id, [
			'_pc_payment_method' => $f['payment_method']        ?? '',
			'_pc_account_name'   => $f['account_name']          ?? '',
			'_pc_amount'         => $f['amount']                ?? '',
			'_pc_sent_on'        => $f['sent_on']               ?? '',
			'_pc_txn'            => $f['transaction_number']    ?? '',
			'_pc_memo'           => $f['memo']                  ?? '',
		] );
		update_post_meta( $post_id, '_pc_matched_order_id',   $order_id );
		update_post_meta( $post_id, '_pc_match_confidence',   $conf );
		update_post_meta( $post_id, '_pc_match_method',       $why );

		// Link to order + copy audit fields
		self::pm( $order_id, [
			'_pc_payconfirm_post_id' => $post_id,
			'_pc_payment_method'     => $f['payment_method']     ?? '',
			'_pc_account_name'       => $f['account_name']       ?? '',
			'_pc_amount'             => $f['amount']             ?? '',
			'_pc_sent_on'            => $f['sent_on']            ?? '',
			'_pc_txn'                => $f['transaction_number'] ?? '',
			'_pc_memo'               => $f['memo']               ?? '',
		] );

		// Remember payer alias(es) per user + REFRESH the snapshot on the order (so OI mode stays hot)
		if ( $order = ( function_exists('wc_get_order') ? wc_get_order( $order_id ) : null ) ) {
			if ( $uid = $order->get_user_id() ) {
				$map = get_user_meta( $uid, 'aaa_oc_pay_accounts', true );
				if ( ! is_array( $map ) ) { $map = []; }

				$method_key = strtolower( preg_replace( '/[^a-z0-9]+/i', '', (string) ( $f['payment_method'] ?? '' ) ) );
				$alias      = trim( (string) ( $f['account_name'] ?? '' ) );

				if ( $method_key && $alias !== '' ) {
					$list = isset( $map[ $method_key ] ) ? (array) $map[ $method_key ] : [];
					$list[] = sanitize_text_field( $alias );
					$map[ $method_key ] = array_values( array_unique( array_filter( array_map( 'strval', $list ) ) ) );
				}
				update_user_meta( $uid, 'aaa_oc_pay_accounts', $map );

				// NEW: write snapshot + ts to the order (then re-index)
				update_post_meta( $order_id, 'aaa_oc_pay_accounts', $map );
				update_post_meta( $order_id, '_pc_alias_snapshot_ts', current_time( 'mysql', true ) );
			}
		}

		// (Remaining totals/status mirrors — unchanged)
		$pm  = strtolower( (string) ( $f['payment_method'] ?? '' ) );
		$amt = (float) ( $f['amount'] ?? 0 );
		$slots = [
			'aaa_oc_cash_amount'       => 0.00,
			'aaa_oc_zelle_amount'      => 0.00,
			'aaa_oc_venmo_amount'      => 0.00,
			'aaa_oc_applepay_amount'   => 0.00,
			'aaa_oc_cashapp_amount'    => 0.00,
			'aaa_oc_creditcard_amount' => 0.00,
		];
		if      ( strpos( $pm, 'zelle'    ) !== false ) $slots['aaa_oc_zelle_amount']      = $amt;
		elseif  ( strpos( $pm, 'venmo'    ) !== false ) $slots['aaa_oc_venmo_amount']      = $amt;
		elseif  ( strpos( $pm, 'apple'    ) !== false ) $slots['aaa_oc_applepay_amount']   = $amt;
		elseif  ( strpos( $pm, 'cashapp'  ) !== false ) $slots['aaa_oc_cashapp_amount']    = $amt;
		elseif  ( strpos( $pm, 'credit'   ) !== false ) $slots['aaa_oc_creditcard_amount'] = $amt;
		elseif  ( strpos( $pm, 'cash'     ) !== false ) $slots['aaa_oc_cash_amount']       = $amt;
		else                                            $slots['aaa_oc_zelle_amount']      = $amt;

		$order_total   = (float) get_post_meta( $order_id, '_order_total', true );
		$orig_tip      = (float) get_post_meta( $order_id, '_wpslash_tip', true );
		$epaymentTotal = $slots['aaa_oc_zelle_amount'] + $slots['aaa_oc_venmo_amount'] + $slots['aaa_oc_applepay_amount']
		               + $slots['aaa_oc_creditcard_amount'] + $slots['aaa_oc_cashapp_amount'];
		$payrecTotal   = $slots['aaa_oc_cash_amount'] + $epaymentTotal;
		$epaymentTip   = max( 0, $epaymentTotal - $order_total );
		$totalOrderTip = $epaymentTip + $orig_tip;
		$balance       = max( 0, $order_total - $payrecTotal );
		$status        = ( $payrecTotal === 0 ) ? 'unpaid' : ( $balance <= 0.01 ? 'paid' : 'partial' );
		$real_method   = self::real_method_from_slots( $slots, wc_get_order( $order_id ) );

		update_post_meta( $order_id, 'aaa_oc_payment_status', $status );
		update_post_meta( $order_id, 'aaa_oc_payrec_total',   number_format( $payrecTotal,   2, '.', '' ) );
		update_post_meta( $order_id, 'aaa_oc_epayment_total', number_format( $epaymentTotal, 2, '.', '' ) );
		update_post_meta( $order_id, 'aaa_oc_order_balance',  number_format( $balance,       2, '.', '' ) );
		update_post_meta( $order_id, 'epayment_tip',          number_format( $epaymentTip,   2, '.', '' ) );
		update_post_meta( $order_id, 'total_order_tip',       number_format( $totalOrderTip, 2, '.', '' ) );
		update_post_meta( $order_id, 'real_payment_method',   $real_method );

		foreach ( $slots as $k => $v ) {
			update_post_meta( $order_id, $k, number_format( $v, 2, '.', '' ) );
		}

		// payment index upsert (+ optional sync + re-index order)
		$ok = self::upsert_payment_index( $order_id, $f );
		if ( $ok && class_exists( 'AAA_OC_Payment_Indexer' ) ) { AAA_OC_Payment_Indexer::sync_payment_totals( $order_id ); }
		if ( class_exists( 'AAA_OC_Indexing' ) )               { ( new AAA_OC_Indexing() )->index_order( $order_id ); }

		// order/admin notes
		if ( function_exists( 'wc_get_order' ) && ( $order = wc_get_order( $order_id ) ) ) {
			$note = sprintf(
				'PayConfirm linked (%s). Amount: %s; Method: %s; Txn: %s; Memo: %s; Post #%d',
				$why,
				isset( $f['amount'] ) ? '$' . number_format( (float) $f['amount'], 2 ) : '-',
				(string) ( $f['payment_method']     ?? '' ),
				(string) ( $f['transaction_number'] ?? '' ),
				$stringMemo = (string) ( $f['memo'] ?? '' ),
				(int) $post_id
			);
			$order->add_order_note( $note, false );

			$admin_note = sprintf( '[%s] %s', gmdate( 'Y-m-d H:i:s' ), $note );
			$existing   = get_post_meta( $order_id, 'payment_admin_notes', true );
			$joined     = $existing ? ( rtrim( $existing ) . "\n" . $admin_note ) : $admin_note;
			update_post_meta( $order_id, 'payment_admin_notes', $joined );
		}

		// NEW: Upsert inbox row as matched
		self::upsert_pc_inbox( $post_id, $f, 'matched', (int) $order_id, (float) $conf );

		return [ 'matched' => true, 'order_id' => (int) $order_id, 'confidence' => (float) $conf, 'method' => (string) $why ];
	}

	public static function pm( $pid, $arr ) { foreach ( $arr as $k => $v ) { update_post_meta( $pid, $k, $v ); } }

	public static function real_method_from_slots( $s, $o ) {
		$nz = array_filter( $s, fn( $v ) => $v > 0 );
		if ( count( $nz ) === 1 ) {
			$m = [
				'aaa_oc_zelle_amount'      => 'Zelle',
				'aaa_oc_cash_amount'       => 'Cash',
				'aaa_oc_venmo_amount'      => 'Venmo',
				'aaa_oc_applepay_amount'   => 'ApplePay',
				'aaa_oc_cashapp_amount'    => 'CashApp',
				'aaa_oc_creditcard_amount' => 'Credit Card',
			];
			$key = array_key_first( $nz );
			return $m[ $key ] ?? ( $o ? (string) $o->get_payment_method() : '' );
		}
		if ( count( $nz ) > 1 ) {
			$max = max( $nz );
			return array_search( $max, $nz, true );
		}
		return $o ? (string) $o->get_payment_method() : '';
	}

	/** Upsert to aaa_oc_payment_index (unchanged behavior) */
	public static function upsert_payment_index( $order_id, array $f ) {
		global $wpdb;
		$tbl = $wpdb->prefix . 'aaa_oc_payment_index';
		$order = wc_get_order( $order_id );
		if ( ! $order ) return false;

		$order_total = (float) $order->get_total();
		$pm          = strtolower( (string) ( $f['payment_method'] ?? '' ) );
		$amount      = (float) ( $f['amount'] ?? 0 );

		$slots = [
			'aaa_oc_cash_amount'       => 0,
			'aaa_oc_zelle_amount'      => 0,
			'aaa_oc_venmo_amount'      => 0,
			'aaa_oc_applepay_amount'   => 0,
			'aaa_oc_cashapp_amount'    => 0,
			'aaa_oc_creditcard_amount' => 0,
		];
		if      ( strpos( $pm, 'zelle'   ) !== false ) $slots['aaa_oc_zelle_amount']      = $amount;
		elseif  ( strpos( $pm, 'venmo'   ) !== false ) $slots['aaa_oc_venmo_amount']      = $amount;
		elseif  ( strpos( $pm, 'apple'   ) !== false ) $slots['aaa_oc_applepay_amount']   = $amount;
		elseif  ( strpos( $pm, 'cashapp' ) !== false ) $slots['aaa_oc_cashapp_amount']    = $amount;
		elseif  ( strpos( $pm, 'credit'  ) !== false ) $slots['aaa_oc_creditcard_amount'] = $amount;
		elseif  ( strpos( $pm, 'cash'    ) !== false ) $slots['aaa_oc_cash_amount']       = $amount;
		else                                            $slots['aaa_oc_zelle_amount']      = $amount;

		$epay_total   = $slots['aaa_oc_zelle_amount'] + $slots['aaa_oc_venmo_amount'] + $slots['aaa_oc_applepay_amount'] + $slots['aaa_oc_creditcard_amount'] + $slots['aaa_oc_cashapp_amount'];
		$payrec_total = $slots['aaa_oc_cash_amount'] + $epay_total;
		$epay_tip     = max( 0, $epay_total - $order_total );
		$status       = ( $payrec_total === 0 ) ? 'unpaid' : ( $payrec_total >= $order_total - 0.01 ? 'paid' : 'partial' );

		$row = $slots + [
			'aaa_oc_epayment_total'  => $epay_total,
			'aaa_oc_payrec_total'    => $payrec_total,
			'aaa_oc_order_total'     => $order_total,
			'aaa_oc_order_balance'   => max( 0, $order_total - $payrec_total ),
			'epayment_tip'           => $epay_tip,
			'aaa_oc_tip_total'       => (float) get_post_meta( $order_id, '_wpslash_tip', true ),
			'total_order_tip'        => $epay_tip + (float) get_post_meta( $order_id, '_wpslash_tip', true ),
			'aaa_oc_payment_status'  => $status,
			'real_payment_method'    => self::real_method_from_slots( $slots, $order ),
			'last_updated_by'        => 'system',
			'last_updated'           => current_time( 'mysql', true ),
		];

		$wpdb->query( 'START TRANSACTION' );
		$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tbl} WHERE order_id=%d", $order_id ) );
		$ok = true;
		if ( $exists ) {
			$ok = $wpdb->update( $tbl, $row, [ 'order_id' => $order_id ] ) !== false;
		} else {
			$row['order_id']               = $order_id;
			$row['subtotal']               = (float) $order->get_subtotal();
			$row['original_payment_method']= (string) $order->get_payment_method();
			$ok = $wpdb->insert( $tbl, $row ) !== false;
		}
		$wpdb->query( $ok ? 'COMMIT' : 'ROLLBACK' );
		return $ok;
	}

	/** NEW: Upsert/Update PayConfirm inbox table (normalized history) */
	public static function upsert_pc_inbox( $post_id, array $f, $status = 'new', $matched_order_id = null, $conf = null ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aaa_oc_payconfirm_inbox';

		$email_id = 'pc:' . (int) $post_id; // stable id for this post
		$raw_html = (string) get_post_field( 'post_content', $post_id );

		$row = [
			'email_id'         => $email_id,
			'amount'           => ( $f['amount'] !== '' ? number_format( (float) $f['amount'], 2, '.', '' ) : null ),
			'txn_id'           => (string) ( $f['transaction_number'] ?? '' ),
			'payer_name'       => (string) ( $f['account_name'] ?? '' ),
			'memo'             => (string) ( $f['memo'] ?? '' ),
			'payment_method'   => (string) ( $f['payment_method'] ?? '' ),
			'payment_date'     => ( $f['sent_on'] ?? '' ) ?: null,
			'matched_order_id' => $matched_order_id ? (int) $matched_order_id : null,
			'match_confidence' => is_numeric( $conf ) ? (int) round( 100 * (float) $conf ) : null,
			'status'           => (string) $status,
			'raw'              => $raw_html,
		];

		$existing_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE email_id=%s LIMIT 1", $email_id ) );

		$data = $row;
		// Keep NULLs as NULL (don't drop zeros)
		$filterNulls = function( $v ) { return ! is_null( $v ); };

		if ( $existing_id ) {
			$wpdb->update( $table, array_filter( $data, $filterNulls ), [ 'id' => $existing_id ] );
		} else {
			$wpdb->insert( $table, array_filter( $data, $filterNulls ) );
		}
	}

	/** Logger */
	public static function log( $msg, $post_id = 0, $fields = null ) {
		if ( defined( 'AAA_OC_PAYCONFIRM_DEBUG' ) && AAA_OC_PAYCONFIRM_DEBUG ) {
			$ctx = $post_id ? " post={$post_id}" : "";
			error_log( '[PayConfirm][MATCH]' . $ctx . ' ' . $msg . ( $fields !== null ? ' :: ' . wp_json_encode( $fields ) : '' ) );
		}
	}
}
