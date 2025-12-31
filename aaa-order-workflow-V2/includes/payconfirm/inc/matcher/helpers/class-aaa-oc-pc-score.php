<?php
/**
 * Purpose: Candidate collection + scoring.
 * In "Order Index" mode, reads amounts/aliases/timestamps from aaa_oc_order_index to avoid postmeta IO.
 * Version: 1.3.0
 */
if ( ! defined('ABSPATH') ) { exit; }

class AAA_OC_PC_Score {

	/** Collect candidate order IDs */
	public static function collect_candidates( array $f ) {
		$use_oi = class_exists('AAA_OC_PC_Settings') ? AAA_OC_PC_Settings::use_order_index() : false;

		$paid = ( $f['amount'] !== '' ) ? (float) $f['amount'] : null;
		$name = trim( (string) ( $f['account_name'] ?? '' ) );
		$sent = ( $f['sent_on'] ?? '' ) ?: null;

		$pool = [];

		// Amount exact (index-aware)
		if ( $paid !== null ) {
			$pool = array_merge( $pool, AAA_OC_PC_Lookups::find_orders_by_total( $paid ) );

			if ( $use_oi ) {
				// Approximate recency from OI (IDs DESC)
				$pool = array_merge( $pool, AAA_OC_PC_Lookups::find_recent_orders_oi( 200 ) );
			} else {
				// Original post-based "recent window"
				global $wpdb;
				$IN        = AAA_OC_PC_Status::sql_status_in();
				$limit     = 250;
				$date_cond = '';
				if ( $sent ) {
					[ $from, $to ] = AAA_OC_PC_Window::window_dates( $sent );
					if ( $from && $to ) { $date_cond = $wpdb->prepare( ' AND p.post_date_gmt BETWEEN %s AND %s ', $from, $to ); $limit = 200; }
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
		}

		// Name/alias derived candidates
		if ( $name !== '' ) {
			$pool = array_merge( $pool, AAA_OC_PC_Lookups::find_orders_by_name( $name, $sent ) );

			if ( ! $use_oi ) {
				$uids = AAA_OC_PC_Lookups::find_users_by_pay_account( $name );
				if ( ! empty( $uids ) ) {
					$pool = array_merge( $pool, AAA_OC_PC_Lookups::find_orders_by_customer_ids( $uids, $sent ) );
				}
				$pool = array_merge( $pool, AAA_OC_PC_Lookups::find_orders_by_alias_order_meta( $name, $sent ) );
			}
		}

		return array_values( array_unique( array_map( 'intval', $pool ) ) );
	}

	/** Score and rank candidate orders */
	public static function rank_candidates( array $order_ids, array $f ) {
		$use_oi = class_exists('AAA_OC_PC_Settings') ? AAA_OC_PC_Settings::use_order_index() : false;

		$paid = ( $f['amount'] !== '' ) ? (float) $f['amount'] : null;
		$name = strtolower( trim( (string) ( $f['account_name'] ?? '' ) ) );
		$sent = ( $f['sent_on'] ?? '' ) ?: null;

		$oi_map = $use_oi ? self::prefetch_oi_rows( $order_ids ) : [];

		$out = [];
		foreach ( $order_ids as $oid ) {
			$score   = 0;
			$reasons = [];

			// Amount (exact/overpay)
			$tot = $use_oi
				? (float) ( $oi_map[ $oid ]['aaa_oc_order_total'] ?? 0 )
				: (float) get_post_meta( $oid, '_order_total', true );

			if ( $paid !== null ) {
				if ( abs( $tot - $paid ) < 0.005 ) { $score += 60; $reasons[] = 'amount=exact'; }
				else {
					$delta = $paid - $tot;
					if ( $delta >= 0 && $delta <= 25 && $delta <= 0.5 * $tot ) {
						$score += 55; $reasons[] = 'amount=overpay';
					}
				}
			}

			// Name tokens / alias presence
			if ( $name !== '' ) {
				if ( $use_oi ) {
					$alias_hit = false;
					$json = (string) ( $oi_map[ $oid ]['pc_aliases'] ?? '' );
					if ( $json !== '' ) {
						$map = json_decode( $json, true );
						if ( is_array( $map ) ) {
							foreach ( $map as $method => $aliases ) {
								foreach ( (array) $aliases as $a ) {
									$sa = strtolower( (string) $a );
									if ( $sa !== '' && ( strpos( $name, $sa ) !== false || strpos( $sa, $name ) !== false ) ) {
										$alias_hit = true; break 2;
									}
								}
							}
						}
					}
					if ( $alias_hit ) { $score += 45; $reasons[] = 'alias_order_index'; }
				} else {
					$bi = strtolower( (string) get_post_meta( $oid, '_billing_address_index',  true ) );
					$si = strtolower( (string) get_post_meta( $oid, '_shipping_address_index', true ) );
					$idx = $bi . ' ' . $si;
					$tokens = preg_split( '/\s+/', preg_replace( '/[^a-z\s]/', '', $name ) );
					$matches = 0;
					foreach ( $tokens as $t ) { if ( $t && strpos( $idx, $t ) !== false ) { $matches++; } }
					if ( $matches ) { $score += min( 30, 15 * $matches ); $reasons[] = "name_tokens={$matches}"; }
					// snapshot-on-order (posts mode)
					$alias_map = get_post_meta( $oid, 'aaa_oc_pay_accounts', true );
					if ( is_array( $alias_map ) ) {
						$hit = false;
						foreach ( $alias_map as $method => $aliases ) {
							foreach ( (array) $aliases as $a ) {
								$sa = strtolower( (string) $a );
								if ( $sa !== '' && ( strpos( $name, $sa ) !== false || strpos( $sa, $name ) !== false ) ) { $hit = true; break 2; }
							}
						}
						if ( $hit ) { $score += 45; $reasons[] = 'alias_order_meta'; }
					}
				}
			}

			// Date proximity: OI uses alias snapshot ts; Posts uses order post_date_gmt
			if ( $sent ) {
				$ts = strtotime( $sent );
				if ( $use_oi ) {
					$ots = isset( $oi_map[ $oid ]['pc_alias_snapshot_ts'] ) && $oi_map[ $oid ]['pc_alias_snapshot_ts'] !== null
						? strtotime( $oi_map[ $oid ]['pc_alias_snapshot_ts'] ) : 0;
				} else {
					$pg  = get_post_field( 'post_date_gmt', $oid );
					$ots = $pg ? strtotime( $pg ) : 0;
				}
				if ( $ts && $ots ) {
					$diff = abs( $ts - $ots ) / DAY_IN_SECONDS;
					if ( $diff <= 3 ) { $score += 25; $reasons[] = 'date≈'; }
				}
			}

			$out[] = [ 'order_id' => (int) $oid, 'score' => (float) $score, 'reasons' => implode( ',', $reasons ) ];
		}

		usort( $out, function( $a, $b ){ return $b['score'] <=> $a['score']; } );
		return $out;
	}

	/** Prefetch needed OI rows for ranking */
	private static function prefetch_oi_rows( array $order_ids ) : array {
		global $wpdb;
		$order_ids = array_values( array_unique( array_filter( array_map( 'intval', $order_ids ) ) ) );
		if ( empty( $order_ids ) ) return [];
		$tbl = $wpdb->prefix . 'aaa_oc_order_index';
		$placeholders = implode( ',', array_fill( 0, count( $order_ids ), '%d' ) );
		$sql = "SELECT order_id, aaa_oc_order_total, pc_aliases, pc_alias_snapshot_ts
		        FROM {$tbl} WHERE order_id IN ($placeholders)";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$order_ids ), ARRAY_A ) ?: [];
		$map  = [];
		foreach ( $rows as $r ) { $map[ (int) $r['order_id'] ] = $r; }
		return $map;
	}
}
