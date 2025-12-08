<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/inc/matcher/helpers/class-aaa-oc-pc-score.php
 * Purpose: Candidate collection + scoring (amount + name + date + payer-alias customers).
 */
if ( ! defined('ABSPATH') ) { exit; }

class AAA_OC_PC_Score {

	/** Collect candidate order IDs using amount window + recent + name + payer-alias customers */
	public static function collect_candidates( array $f ) {
		global $wpdb;
		$IN   = AAA_OC_PC_Status::sql_status_in();
		$paid = ( $f['amount'] !== '' ) ? (float) $f['amount'] : null;
		$name = trim( (string) ( $f['account_name'] ?? '' ) );
		$sent = ( $f['sent_on'] ?? '' ) ?: null;

		$pool = [];

		// exact totals via meta
		if ( $paid !== null ) {
			$pool = array_merge( $pool, AAA_OC_PC_Lookups::find_orders_by_total( $paid ) );

			// recent window (status filtered); narrower if sent_on available
			$limit     = 250;
			$date_cond = '';
			if ( $sent ) {
				[ $from, $to ] = AAA_OC_PC_Window::window_dates( $sent );
				if ( $from && $to ) {
					$date_cond = $wpdb->prepare( ' AND p.post_date_gmt BETWEEN %s AND %s ', $from, $to );
					$limit     = 200;
				}
			}
			$recent = $wpdb->get_col( "
				SELECT p.ID FROM {$wpdb->posts} p
				WHERE p.post_type='shop_order'
				  AND p.post_status IN ($IN)
				  {$date_cond}
				ORDER BY p.ID DESC LIMIT {$limit}
			" );
			$pool = array_merge( $pool, array_map( 'intval', $recent ?: [] ) );
		}

		// name pool
		if ( $name !== '' ) {
			$pool = array_merge( $pool, AAA_OC_PC_Lookups::find_orders_by_name( $name, $sent ) );

			// payer-alias → customer orders (e.g., Jocelyn pays for Fernando)
			$uids = AAA_OC_PC_Lookups::find_users_by_pay_account( $name );
			if ( ! empty( $uids ) ) {
				$pool = array_merge( $pool, AAA_OC_PC_Lookups::find_orders_by_customer_ids( $uids, $sent ) );
			}
		}

		return array_values( array_unique( array_map( 'intval', $pool ) ) );
	}

	/** Score and rank candidate orders */
	public static function rank_candidates( array $order_ids, array $f ) {
		$paid = ( $f['amount'] !== '' ) ? (float) $f['amount'] : null;
		$name = strtolower( trim( (string) ( $f['account_name'] ?? '' ) ) );
		$sent = ( $f['sent_on'] ?? '' ) ?: null;

		$out = [];
		foreach ( $order_ids as $oid ) {
			$score   = 0;
			$reasons = [];

			// amount (exact or reasonable overpay)
			$tot = (float) get_post_meta( $oid, '_order_total', true );
			if ( $paid !== null ) {
				if ( abs( $tot - $paid ) < 0.005 ) { $score += 60; $reasons[] = 'amount=exact'; }
				else {
					$delta = $paid - $tot;
					if ( $delta >= 0 && $delta <= 25 && $delta <= 0.5 * $tot ) {
						$score += 55; $reasons[] = 'amount=overpay';
					}
				}
			}

			// name token overlap (billing/shipping indexes)
			if ( $name !== '' ) {
				$bi = strtolower( (string) get_post_meta( $oid, '_billing_address_index',  true ) );
				$si = strtolower( (string) get_post_meta( $oid, '_shipping_address_index', true ) );
				$idx     = $bi . ' ' . $si;
				$tokens  = preg_split( '/\s+/', preg_replace( '/[^a-z\s]/', '', $name ) );
				$matches = 0;
				foreach ( $tokens as $t ) { if ( $t && strpos( $idx, $t ) !== false ) { $matches++; } }
				if ( $matches ) {
					$pts = min( 30, 15 * $matches );
					$score += $pts; $reasons[] = "name_tokens={$matches}";
				}
			}

			// date proximity (±3 days)
			if ( $sent ) {
				$ts  = strtotime( $sent );
				$pg  = get_post_field( 'post_date_gmt', $oid );
				$ots = $pg ? strtotime( $pg ) : 0;
				if ( $ts && $ots && abs( $ots - $ts ) <= 3 * DAY_IN_SECONDS ) {
					$score += 10; $reasons[] = 'date≈';
				}
			}

			$out[] = [ 'order_id' => (int) $oid, 'score' => (float) $score, 'reasons' => implode( ',', $reasons ) ];
		}

		usort( $out, fn( $a, $b ) => $b['score'] <=> $a['score'] );
		return $out;
	}
}
