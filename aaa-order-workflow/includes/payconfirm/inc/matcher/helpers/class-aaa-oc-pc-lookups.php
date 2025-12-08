<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/inc/matcher/helpers/class-aaa-oc-pc-lookups.php
 * Purpose: DB lookups for txn, order-id-in-text, name matching, exact totals, + payer-alias helpers.
 */
if ( ! defined('ABSPATH') ) { exit; }

class AAA_OC_PC_Lookups {

	public static function find_order_by_txn( $raw, $digits ) {
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
			$id   = $wpdb->get_var( $wpdb->prepare(
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

	public static function extract_order_id_from_text( $text ) {
		if ( preg_match( '/(?:order\s*#?\s*)?(\d{4,8})/i', (string) $text, $m ) ) {
			return absint( $m[1] );
		}
		return 0;
	}

	public static function find_orders_by_name( $name, $sent_on_mysql = null ) {
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

	public static function find_orders_by_total( $total ) {
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

	/* -------- payer-alias helpers ---------- */

	/** Find user IDs whose aaa_oc_pay_accounts list contains a given payer name (serialized LIKE). */
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

	/** Find recent orders for a list of user IDs (via _customer_user) within the window anchored to sent_on. */
	public static function find_orders_by_customer_ids( array $user_ids, $sent_on_mysql = null ) {
		if ( empty( $user_ids ) ) return [];
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
}
