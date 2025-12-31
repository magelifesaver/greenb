<?php
if ( ! defined('ABSPATH') ) exit;

final class AAA_OC_Payment_OI_Mirror {
	private static bool $guard = false;

	public static function init() : void {
		// 1) If your payment indexer fires a post-upsert action, hook it
		add_action('aaa_oc_payment_index_after_upsert', [__CLASS__, 'mirror_from_pi'], 10, 2);

		// 2) Meta-based fallback: if any of these change, mirror to OI immediately
		add_action('updated_post_meta', [__CLASS__, 'watch_payment_meta'], 10, 4);
		add_action('added_post_meta',   [__CLASS__, 'watch_payment_meta'], 10, 4);
	}

	/**
	 * Mirror when payment indexer upserts PI.
	 * @param int   $order_id
	 * @param array $pi_row   (assoc array of the PI row)
	 */
	public static function mirror_from_pi(int $order_id, array $pi_row = []) : void {
		if ( self::$guard || $order_id <= 0 ) return;
		self::$guard = true;

		global $wpdb;
		$oi_table = $wpdb->prefix . 'aaa_oc_order_index';

		$data = [];
		$formats = [];

		$map = [
			'aaa_oc_order_total'   => 'aaa_oc_order_total',
			'aaa_oc_order_balance' => 'aaa_oc_order_balance',
			'aaa_oc_tip_total'     => 'aaa_oc_tip_total',
			'epayment_tip'         => 'epayment_tip',
			'total_order_tip'      => 'total_order_tip',
			'real_payment_method'  => 'real_payment_method',
			'aaa_oc_payment_status'=> 'aaa_oc_payment_status',
			'wc_transaction_id'    => 'wc_transaction_id',
			'gateway_transaction_id' => 'gateway_transaction_id',
			'last_payment_at'      => 'last_payment_at',
		];

		foreach ($map as $pi_key => $oi_key) {
			if ( array_key_exists($pi_key, $pi_row) ) {
				$data[$oi_key] = $pi_row[$pi_key];
				$formats[] = is_numeric($pi_row[$pi_key]) ? '%f' : '%s';
			}
		}

		if ( ! empty($data) ) {
			$wpdb->update(
				$oi_table,
				$data,
				['order_id' => $order_id],
				$formats,
				['%d']
			);
		}

		self::$guard = false;
	}

	/**
	 * Meta fallback watcher: when key payment metas change, push to OI.
	 */
	public static function watch_payment_meta($meta_id, $object_id, $meta_key, $_meta_value) : void {
		if ( self::$guard ) return;

		// Only for orders
		$post_type = get_post_type($object_id);
		if ( $post_type !== 'shop_order' && $post_type !== 'shop_order_placehold' ) return;

		$keys = [
			'aaa_oc_order_total', 'aaa_oc_order_balance',
			'aaa_oc_tip_total', 'epayment_tip', 'total_order_tip',
			'real_payment_method', 'aaa_oc_payment_status',
			'wc_transaction_id', 'gateway_transaction_id',
			'last_payment_at',
		];
		if ( ! in_array($meta_key, $keys, true) ) return;

		// Build a minimal PI-like array from meta and reuse the same mirror method
		$pi_like = [];
		foreach ($keys as $k) {
			$v = get_post_meta($object_id, $k, true);
			if ( $v !== '' ) $pi_like[$k] = $v;
		}
		self::mirror_from_pi((int)$object_id, $pi_like);
	}
}
AAA_OC_Payment_OI_Mirror::init();
