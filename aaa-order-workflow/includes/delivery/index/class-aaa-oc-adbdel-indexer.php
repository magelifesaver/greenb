<?php
/**
 * File Path: /aaa-order-workflow/includes/delivery/index/aaa-oc-adbdel-indexer.php
 * Module: Delivery — ADBDEL
 *
 * Purpose
 * =======
 * Keep the delivery tables in sync with WooCommerce orders:
 *   - Header: {$wpdb->prefix}aaa_oc_delivery_route
 *   - Map   : {$wpdb->prefix}aaa_oc_delivery_route_order
 *
 * Behavior
 * --------
 * - Runs on order save/update *only after* a delivery date exists.
 * - Upserts the header keyed by `envelope_id` (REPLACE semantics).
 * - Guarantees a *single* map row per (route_id, order_id).
 * - Preserves formats arrays for SQL type safety.
 * - Uses `current_time()` for `env-YYYYmmddHis` generation (site-local time).
 *
 * Hooks
 * -----
 * - save_post_shop_order
 * - woocommerce_update_order
 * - woocommerce_after_order_object_save
 *
 * Versioning & Logging
 * --------------------
 * - AAA_OC_ADBDEL_VERSION: bump when schema/logic changes.
 * - AAA_OC_ADBDEL_ENABLE_LOG: set to false to silence logs.
 *
 * @package AAA_OC_Delivery
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'AAA_OC_ADBDEL_VERSION' ) ) {
	define( 'AAA_OC_ADBDEL_VERSION', '0.1.0-dev' );
}
if ( ! defined( 'AAA_OC_ADBDEL_ENABLE_LOG' ) ) {
	define( 'AAA_OC_ADBDEL_ENABLE_LOG', true );
}
if ( ! function_exists( 'aaa_oc_adbdel_log' ) ) {
	function aaa_oc_adbdel_log( $msg ) {
		if ( AAA_OC_ADBDEL_ENABLE_LOG ) {
			if ( is_array( $msg ) || is_object( $msg ) ) {
				$msg = wp_json_encode( $msg );
			}
			error_log( '[ADBDEL indexer] ' . $msg );
		}
	}
}

if ( ! class_exists( 'AAA_OC_AdbDel_Indexer' ) ) :

class AAA_OC_AdbDel_Indexer {

	public static function init() {
		add_action( 'save_post_shop_order',                array( __CLASS__, 'index_delivery_data' ), 20, 1 );
		add_action( 'woocommerce_update_order',            array( __CLASS__, 'index_delivery_data' ), 20, 1 );
		add_action( 'woocommerce_after_order_object_save', array( __CLASS__, 'index_delivery_data' ), 25, 1 );
	}

	/**
	 * Sync header & map tables for a given order, but only when a delivery date exists.
	 *
	 * @param int|WC_Order $order_or_id
	 */
	public static function index_delivery_data( $order_or_id ) {
		// Resolve order
		if ( is_int( $order_or_id ) ) {
			if ( wp_is_post_revision( $order_or_id ) || wp_is_post_autosave( $order_or_id ) ) {
				return;
			}
			$order = wc_get_order( $order_or_id );
		} elseif ( is_a( $order_or_id, 'WC_Order' ) ) {
			$order = $order_or_id;
		} else {
			return;
		}
		if ( ! $order ) {
			return;
		}

		$order_id = $order->get_id();

		// Only index once we have a delivery date/meta
		$delivery_date = get_post_meta( $order_id, '_lddfw_delivery_date', true );
		if ( empty( $delivery_date ) ) {
			return;
		}

		global $wpdb;
		$route_table = $wpdb->prefix . 'aaa_oc_delivery_route';
		$map_table   = $wpdb->prefix . 'aaa_oc_delivery_route_order';

		// 1) Upsert route header — everyone with the same envelope_id shares a header row

		$driver_id          = get_post_meta( $order_id, 'lddfw_driverid', true ) ?: null;
		$delivery_time      = get_post_meta( $order_id, '_lddfw_delivery_time', true ) ?: null;
		$time_range         = get_post_meta( $order_id, 'delivery_time_range', true );
		$delivery_note      = get_post_meta( $order_id, 'delivery_note', true );
		$route_sort         = (int) get_post_meta( $order_id, 'lddfw_order_sort', true );
		$route_status       = get_post_meta( $order_id, 'route_status', true );
		$route_note         = get_post_meta( $order_id, 'route_note', true );
		$delivery_started   = get_post_meta( $order_id, 'delivery_started', true );
		$delivery_completed = get_post_meta( $order_id, 'delivery_completed', true );
		$task_info          = get_post_meta( $order_id, 'task_info', true );
		$printed_at         = get_post_meta( $order_id, 'printed_at', true );

		$wpdb->replace(
			$route_table,
			array(
				'order_id'               => $order_id,
				'envelope_id'            => $envelope_id,
				'driver_id'              => $driver_id,
				'delivery_date'          => $delivery_date,
				'delivery_time'          => $delivery_time,
				'delivery_time_range'    => is_array( $time_range ) ? implode( ', ', $time_range ) : $time_range,
				'delivery_note'          => $delivery_note,
				'route_sort'             => $route_sort,
				'route_status'           => $route_status,
				'route_note'             => $route_note,
				'dispatched_by_user_id'  => get_current_user_id(),
				'delivery_started'       => $delivery_started,
				'delivery_completed'     => $delivery_completed,
				'task_info'              => $task_info,
				'printed_at'             => $printed_at,
			),
			array(
				'%d','%s','%d','%s','%s','%s','%s','%d','%s','%s',
				'%d','%s','%s','%s','%s','%s','%s'
			)
		);

		// get route_id
		$route_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT route_id FROM {$route_table} WHERE envelope_id = %s LIMIT 1",
			$envelope_id
		) );
		if ( ! $route_id ) {
			return;
		}

		// 2) Upsert map (ensure single row per (route_id, order_id))
		$shipping_address = $order->get_formatted_shipping_address();
		$total_amount     = (float) $order->get_total();
		$payment_method   = $order->get_payment_method_title();
		$payment_status   = get_post_meta( $order_id, '_payment_status', true ) ?: $order->get_status();
		$account_status   = get_post_meta( $order_id, '_account_status', true );
		$warnings_flag    = get_post_meta( $order_id, 'customer_warnings_text', true ) ? 1 : 0;
		$band             = get_post_meta( $order_id, '_customer_band', true );
		$band_length      = get_post_meta( $order_id, '_customer_ban_length', true );
		$special_needs    = get_post_meta( $order_id, 'customer_special_needs_text', true );
		$customer_warnings= get_post_meta( $order_id, 'customer_warnings_text', true );
		$customer_banned  = get_post_meta( $order_id, 'customer_banned', true );
		$customer_ban_len = get_post_meta( $order_id, 'customer_ban_length', true );
		$order_note       = $order->get_customer_note();

		$sort_index       = $route_sort ?: 0;

		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$map_table} WHERE route_id = %d AND order_id = %d LIMIT 1",
			$route_id, $order_id
		) );

		$data_map = array(
			'sort_index'             => $sort_index,
			'shipping_address'       => $shipping_address,
			'total_amount'           => $total_amount,
			'payment_method'         => $payment_method,
			'payment_status'         => $payment_status,
			'account_status'         => $account_status,
			'warning_flag'           => $warnings_flag,
			'band'                   => $band,
			'band_length'            => $band_length,
			'special_needs_text'     => $special_needs,
			'customer_warnings_text' => $customer_warnings,
			'customer_banned'        => $customer_banned,
			'customer_ban_length'    => $customer_ban_len,
			'order_note'             => $order_note,
		);

		$formats_map = array(
			'%d','%s','%f','%s','%s','%s','%d',
			'%s','%s','%s','%s','%d','%s'
		);

		if ( $exists ) {
			$wpdb->update( $map_table, $data_map, array( 'id' => $exists ), $formats_map, array( '%d' ) );
		} else {
			$wpdb->insert(
				$map_table,
				array_merge(
					array(
						'route_id' => $route_id,
						'order_id' => $order_id,
					),
					$data_map
				),
				array_merge( array( '%d','%d' ), $formats_map )
			);
		}
	}
}

endif;

// Back-compat class name
if ( ! class_exists( 'AAA_OC_Delivery_Indexer' ) && class_exists( 'AAA_OC_AdbDel_Indexer' ) ) {
	class_alias( 'AAA_OC_AdbDel_Indexer', 'AAA_OC_Delivery_Indexer' );
}

// Bootstrap
AAA_OC_AdbDel_Indexer::init();
