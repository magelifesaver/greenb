<?php
/**
 * Purpose: DB lookups for txn, exact totals, alias-based candidates; supports "Posts" or "Order Index" modes.
 */
if ( ! defined('ABSPATH') ) { exit; }

class AAA_OC_PC_Lookups {

	private static function use_oi() : bool {
		$src = function_exists('aaa_oc_get_option')
			? (string) aaa_oc_get_option('payconfirm_match_source','modules','posts')
			: (string) get_option('payconfirm_match_source','posts');
		return $src === 'order_index';
	}

	/* ---------- TXN lookups (postmeta, unchanged) ---------- */
	public static function find_order_by_txn( $raw, $digits ) {
		// TXN is historically stored in postmeta; keep this path (pre‑match).
		global $wpdb; $key = '_aaa_pm_reference';
		$IN = AAA_OC_PC_Status::sql_status_in();

		if ( $raw !== '' ) {
			$id = $wpdb->get_var( $wpdb->prepare(
				"SELECT pm.post_id FROM {$wpdb->postmeta} pm
				 JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key=%s AND pm.meta_value=%s
				   AND p.post_type='shop_order' AND p.post_status IN ($IN)
				 LIMIT 1", $key, $raw
			) );
			if ( $id ) return (int) $id;
		}

		if ( $digits !== '' ) {
			$id = $wpdb->get_var( $wpdb->prepare(
				"SELECT pm.post_id FROM {$wpdb->postmeta} pm
				 JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key=%s AND pm.meta_value=%s
				   AND p.post_type='shop_order' AND p.post_status IN ($IN)
				 LIMIT 1", $key, $digits
			) );
			if ( $id ) return (int) $id;

			$like = '%' . $wpdb->esc_like( $digits ) . '%';
			$id = $wpdb->get_var( $wpdb->prepare(
				"SELECT pm.post_id FROM {$wpdb->postmeta} pm
				 JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key=%s AND pm.meta_value LIKE %s
				   AND p.post_type='shop_order' AND p.post_status IN ($IN)
				 ORDER BY pm.post_id DESC LIMIT 1", $key, $like
			) );
			if ( $id ) return (int) $id;
		}
		return 0;
	}

	/** Order number in memo (post-based) */
	public static function extract_order_id_from_text( $text ) {
		if ( preg_match( '/(?:order\s*#?\s*)?(\d{4,8})/i', (string) $text, $m ) ) {
			return absint( $m[1] );
		}
		return 0;
	}

	/* ---------- Amount lookups ---------- */

	public static function find_orders_by_total( $total ) {
		if ( self::use_oi() ) return self::find_orders_by_total_oi( $total );

		global $wpdb; $IN = AAA_OC_PC_Status::sql_status_in();
		$n = number_format( (float) $total, 2, '.', '' );
		$ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT p.ID
			 FROM {$wpdb->posts} p
			 JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
			 WHERE p.post_type='shop_order'
			   AND p.post_status IN ($IN)
			   AND m.meta_key='_order_total'
			   AND CAST(m.meta_value AS DECIMAL(10,2)) = %s
			 ORDER BY p.ID DESC
			 LIMIT 250", $n
		) );
		return array_map( 'intval', $ids ?: [] );
	}

	private static function find_orders_by_total_oi( $total ) {
		global $wpdb;
		$tbl = $wpdb->prefix . 'aaa_oc_order_index';
		$n   = number_format( (float) $total, 2, '.', '' );
		$ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT order_id FROM {$tbl}
			 WHERE CAST(aaa_oc_order_total AS DECIMAL(10,2)) = %s
			 ORDER BY order_id DESC
			 LIMIT 250", $n
		) );
		return array_map( 'intval', $ids ?: [] );
	}

	/* ---------- Name / alias lookups ---------- */

	public static function find_orders_by_name( $name, $sent_on_mysql = null ) {
		if ( self::use_oi() ) {
			return self::find_orders_by_alias_order_index( $name, $sent_on_mysql );
		}
		// Original post-based name search (billing/shipping indexes)
		global $wpdb;
		$IN        = AAA_OC_PC_Status::sql_status_in();
		$name_like = '%' . $wpdb->esc_like( trim( $name ) ) . '%';

		$date_cond = '';
		if ( $sent_on_mysql ) {
			[ $from, $to ] = AAA_OC_PC_Window::window_dates( $sent_on_mysql );
			if ( $from && $to ) {
				$date_cond = $wpdb->prepare( ' AND p.post_date_gmt BETWEEN %s AND %s ', $from, $to );
			}
		}

		$sql = "
			SELECT DISTINCT p.ID
			FROM {$wpdb->posts} p
			JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
			WHERE p.post_type='shop_order'
			  AND p.post_status IN ($IN)
			  AND (
			    (m.meta_key='_billing_address_index'  AND m.meta_value LIKE %s) OR
			    (m.meta_key='_shipping_address_index' AND m.meta_value LIKE %s)
			  )
			  {$date_cond}
			ORDER BY p.ID DESC
			LIMIT 250
		";
		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( $sql, $name_like, $name_like ) ) ?: [] );
	}

	/** New: alias search on the order_index JSON */
	public static function find_orders_by_alias_order_index( $alias, $sent_on_mysql = null ) {
		global $wpdb;
		$tbl   = $wpdb->prefix . 'aaa_oc_order_index';
		$alias = trim( (string) $alias );
		if ( $alias === '' ) return [];
		$needle = '%' . $wpdb->esc_like( $alias ) . '%';

		$date_cond = '';
		if ( $sent_on_mysql ) {
			[ $from, $to ] = AAA_OC_PC_Window::window_dates( $sent_on_mysql );
			if ( $from && $to ) {
				$date_cond = $wpdb->prepare( ' AND pc_alias_snapshot_ts BETWEEN %s AND %s ', $from, $to );
			}
		}

		$sql = "SELECT DISTINCT order_id FROM {$tbl}
		        WHERE pc_aliases LIKE %s {$date_cond}
		        ORDER BY order_id DESC
		        LIMIT 250";
		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( $sql, $needle ) ) ?: [] );
	}

	/** Old helper: usermeta → orders (post-based). Kept for Posts mode. */
	public static function find_users_by_pay_account( $payer_name ) {
		global $wpdb;
		$needle = '%' . $wpdb->esc_like( trim( (string) $payer_name ) ) . '%';
		$uids = $wpdb->get_col( $wpdb->prepare(
			"SELECT user_id FROM {$wpdb->usermeta}
			 WHERE meta_key='aaa_oc_pay_accounts' AND meta_value LIKE %s
			 LIMIT 250", $needle
		) );
		return array_map( 'intval', $uids ?: [] );
	}

	public static function find_orders_by_customer_ids( array $user_ids, $sent_on_mysql = null ) {
		if ( empty( $user_ids ) ) return [];
		if ( self::use_oi() ) {
			// In OI mode we avoid joining posts; return empty to rely on alias JSON + amount.
			return [];
		}
		global $wpdb;
		$IN = AAA_OC_PC_Status::sql_status_in();

		$date_cond = '';
		if ( $sent_on_mysql ) {
			[ $from, $to ] = AAA_OC_PC_Window::window_dates( $sent_on_mysql );
			if ( $from && $to ) {
				$date_cond = $wpdb->prepare( ' AND p.post_date_gmt BETWEEN %s AND %s ', $from, $to );
			}
		}

		$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
		$sql = "
			SELECT DISTINCT p.ID
			FROM {$wpdb->posts} p
			JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
			WHERE p.post_type='shop_order'
			  AND p.post_status IN ($IN)
			  AND m.meta_key='_customer_user'
			  AND CAST(m.meta_value AS UNSIGNED) IN ($placeholders)
			  {$date_cond}
			ORDER BY p.ID DESC
			LIMIT 250
		";

		$ids = $wpdb->get_col( $wpdb->prepare( $sql, ...$user_ids ) );
		return array_map( 'intval', $ids ?: [] );
	}

	/** New: "recent" pool from OI when amount is present (no post date available → use last IDs as proxy) */
	public static function find_recent_orders_oi( $limit = 200 ) {
		global $wpdb;
		$tbl = $wpdb->prefix . 'aaa_oc_order_index';
		$limit = max( 1, (int) $limit );
		$ids = $wpdb->get_col( "SELECT order_id FROM {$tbl} ORDER BY order_id DESC LIMIT {$limit}" );
		return array_map( 'intval', $ids ?: [] );
	}

	/** Old: orders whose order-meta alias snapshot contains the alias (Posts mode). */
	public static function find_orders_by_alias_order_meta( $alias, $sent_on_mysql = null ) {
		global $wpdb;
		$IN     = AAA_OC_PC_Status::sql_status_in();
		$needle = '%' . $wpdb->esc_like( trim( (string) $alias ) ) . '%';

		$date_cond = '';
		if ( $sent_on_mysql ) {
			[ $from, $to ] = AAA_OC_PC_Window::window_dates( $sent_on_mysql );
			if ( $from && $to ) {
				$date_cond = $wpdb->prepare( ' AND p.post_date_gmt BETWEEN %s AND %s ', $from, $to );
			}
		}

		$sql = "
			SELECT DISTINCT p.ID
			FROM {$wpdb->posts} p
			JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
			WHERE p.post_type='shop_order'
			  AND p.post_status IN ($IN)
			  AND m.meta_key='aaa_oc_pay_accounts'
			  AND m.meta_value LIKE %s
			  {$date_cond}
			ORDER BY p.ID DESC
			LIMIT 250
		";
		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( $sql, $needle ) ) ?: [] );
	}
}
