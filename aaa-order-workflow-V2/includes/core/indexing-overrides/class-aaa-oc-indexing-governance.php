<?php
if ( ! defined('ABSPATH') ) exit;

final class AAA_OC_Indexing_Governance {
	public static function init() : void {
		// Ensure payment status fields on OI reflect OUR truth and not Woo's guess.
		add_filter('aaa_oc_indexer_base_row', [__CLASS__, 'enforce_payment_truth'], 20, 2);
	}

	/**
	 * Align payment status and paid timestamps from our truth.
	 * @param array    $row   Base row prepared by core indexer.
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	public static function enforce_payment_truth(array $row, $order) : array {
		global $wpdb;
		if ( ! $order || ! method_exists($order, 'get_id') ) return $row;
		$order_id = (int) $order->get_id();

		$oi_table = $wpdb->prefix . 'aaa_oc_order_index';
		$pi_table = $wpdb->prefix . 'aaa_oc_payment_index';

		// 1) Read existing OI truth (if any) so we never regress it
		$existing_oi = $wpdb->get_row(
			$wpdb->prepare("SELECT aaa_oc_payment_status, payment_status, paid_date FROM {$oi_table} WHERE order_id=%d LIMIT 1", $order_id),
			ARRAY_A
		) ?: [];

		// 2) Take current truth for status:
		//    - prefer OI.aaa_oc_payment_status (set by payconfirm/manual/JS workflow)
		//    - else fall back to post meta (if your JS wrote it there)
		$current_truth_status = '';
		if ( ! empty($existing_oi['aaa_oc_payment_status']) ) {
			$current_truth_status = (string) $existing_oi['aaa_oc_payment_status'];
		} else {
			$current_truth_status = (string) get_post_meta($order_id, 'aaa_oc_payment_status', true);
		}
		$current_truth_status = $current_truth_status ? strtolower($current_truth_status) : 'unpaid';

		// 3) Align both "payment_status" and "aaa_oc_payment_status" to the truth
		$row['aaa_oc_payment_status'] = $current_truth_status;
		$row['payment_status']        = $current_truth_status; // keeps legacy readers happy

		// 4) Govern paid_date based on truth only
		$is_paid_like = in_array($current_truth_status, ['paid','cleared'], true);

		// Pull PI.last_payment_at if available
		$pi_row = $wpdb->get_row(
			$wpdb->prepare("SELECT last_payment_at FROM {$pi_table} WHERE order_id=%d LIMIT 1", $order_id),
			ARRAY_A
		);

		$pi_last_payment_at = ! empty($pi_row['last_payment_at']) ? (string) $pi_row['last_payment_at'] : '';

		if ( $is_paid_like ) {
			// Choose: existing OI.paid_date > PI.last_payment_at > leave empty (manual can fill)
			if ( ! empty($existing_oi['paid_date']) ) {
				$row['paid_date'] = $existing_oi['paid_date'];
			} elseif ( $pi_last_payment_at ) {
				$row['paid_date'] = $pi_last_payment_at;
			} else {
				// keep unset; the payconfirm/manual flow can stamp it explicitly later
				unset($row['paid_date']);
			}
			// Also mirror a convenience copy to "_paid_date" column if your schema has it
			if ( isset($row['paid_date']) ) {
				$row['_paid_date'] = $row['paid_date'];
			} else {
				unset($row['_paid_date']);
			}
		} else {
			// Not paid: explicitly clear any paid timestamps in OI
			$row['paid_date']  = null;
			$row['_paid_date'] = null;
		}

		return $row;
	}
}
AAA_OC_Indexing_Governance::init();
