<?php
/**
 * Class: AAA_OC_Delivery_Index
 * File Path: /aaa-order-workflow/includes/delivery/index/class-aaa-oc-delivery-index.php
 * Purpose: Pulls delivery meta and the new envelope/route fields into the main order index.
 *
 * Hooks into order save/update to copy down both WooCommerce delivery meta
 * and our custom aaa_oc_delivery_route table columns into aaa_oc_order_index.
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
			error_log( '[ADBDEL index] ' . $msg );
		}
	}
}

if ( ! class_exists( 'AAA_OC_AdbDel_Index' ) ) :

class AAA_OC_AdbDel_Index {

	public static function init() {
		add_action( 'woocommerce_after_order_object_save', array( __CLASS__, 'update_delivery_data' ), 25, 1 );
		add_action( 'woocommerce_update_order',            array( __CLASS__, 'update_delivery_data' ), 25, 1 );
	}

	public static function update_delivery_data( $order_or_id ) {
		global $wpdb;

		$order = is_a( $order_or_id, 'WC_Order' ) ? $order_or_id : wc_get_order( $order_or_id );
		if ( ! $order ) {
			return;
		}

		$order_id = $order->get_id();

		// 1) WooCommerce meta
		$daily_order_number  = (int) get_post_meta( $order_id, '_daily_order_number', true );
		$driver_meta         = get_post_meta( $order_id, 'lddfw_driverid', true );

		$shipping_methods    = $order->get_shipping_methods();
		$shipping_method     = '';
		if ( is_array( $shipping_methods ) && ! empty( $shipping_methods ) ) {
			$titles = array();
			foreach ( $shipping_methods as $sm ) {
				if ( is_object( $sm ) && method_exists( $sm, 'get_method_title' ) ) {
					$titles[] = $sm->get_method_title();
				}
			}
			$shipping_method = implode( ', ', $titles );
		}

		$addr = $order->get_address( 'shipping' );
		$ship1     = isset( $addr['address_1'] ) ? $addr['address_1'] : '';
		$ship2     = isset( $addr['address_2'] ) ? $addr['address_2'] : '';
		$ship_city = isset( $addr['city'] )      ? $addr['city']      : '';
		$ship_state= isset( $addr['state'] )     ? $addr['state']     : '';
		$ship_pc   = isset( $addr['postcode'] )  ? $addr['postcode']  : '';
		$ship_ctry = isset( $addr['country'] )   ? $addr['country']   : '';

		$delivery_time        = get_post_meta( $order_id, 'delivery_time', true );
		$delivery_time_range  = get_post_meta( $order_id, 'delivery_time_range', true );
		if ( is_array( $delivery_time_range ) ) {
			$delivery_time_range = implode( ', ', $delivery_time_range );
		}
		$delivery_date_fmt    = get_post_meta( $order_id, 'delivery_date_formatted', true );
		$lddfw_date           = get_post_meta( $order_id, '_lddfw_delivery_date', true );
		$lddfw_time           = get_post_meta( $order_id, '_lddfw_delivery_time', true );
		$lddfw_driverid       = get_post_meta( $order_id, 'lddfw_driverid', true );

		// 2) Custom route header
		$route_table = $wpdb->prefix . 'aaa_oc_delivery_route';
		$route_row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT envelope_id,
						route_sort,
						route_status,
						route_note,
						dispatched_by_user_id,
						delivery_completed AS delivered_at,
						task_info
				   FROM {$route_table}
				  WHERE order_id = %d
				  LIMIT 1",
				$order_id
			),
			ARRAY_A
		);
		if ( ! is_array( $route_row ) ) {
			$route_row = array();
		}

		// 3) Build full data array (order index must have these columns)
		$data = array(
			'daily_order_number'      => $daily_order_number,
			'shipping_method'         => $shipping_method,
			'driver_id'               => $driver_meta ? (int) $driver_meta : null,

			'shipping_address_1'      => $ship1,
			'shipping_address_2'      => $ship2,
			'shipping_city'           => $ship_city,
			'shipping_state'          => $ship_state,
			'shipping_postcode'       => $ship_pc,
			'shipping_country'        => $ship_ctry,

			'delivery_time'           => $delivery_time,
			'delivery_time_range'     => $delivery_time_range,
			'delivery_date_formatted' => $delivery_date_fmt,
			'lddfw_delivery_date'     => $lddfw_date,
			'lddfw_delivery_time'     => $lddfw_time,
			'lddfw_driverid'          => is_numeric( $lddfw_driverid ) ? (int) $lddfw_driverid : null,

			'envelope_id'             => isset( $route_row['envelope_id'] )            ? $route_row['envelope_id']            : null,
			'route_sort'              => isset( $route_row['route_sort'] )             ? $route_row['route_sort']             : null,
			'route_status'            => isset( $route_row['route_status'] )           ? $route_row['route_status']           : null,
			'route_note'              => isset( $route_row['route_note'] )             ? $route_row['route_note']             : null,
			'dispatched_by_user_id'   => isset( $route_row['dispatched_by_user_id'] )  ? $route_row['dispatched_by_user_id']  : null,
			'delivered_at'            => isset( $route_row['delivered_at'] )           ? $route_row['delivered_at']           : null,
			'task_info'               => isset( $route_row['task_info'] )              ? $route_row['task_info']              : null,
		);

		// 4) Formats array must align with $data keys
		$formats = array(
			'%d',  // daily_order_number
			'%s',  // shipping_method
			'%d',  // driver_id

			// shipping address
			'%s','%s','%s','%s','%s','%s',

			// delivery fields
			'%s','%s','%s','%s','%s','%d',

			// route header
			'%s','%d','%s','%s','%d','%s','%s',
		);

		$wpdb->update(
			$wpdb->prefix . 'aaa_oc_order_index',
			$data,
			array( 'order_id' => $order_id ),
			$formats,
			array( '%d' )
		);
	}
}

endif;

// Back-compat class name
if ( ! class_exists( 'AAA_OC_Delivery_Index' ) && class_exists( 'AAA_OC_AdbDel_Index' ) ) {
	class_alias( 'AAA_OC_AdbDel_Index', 'AAA_OC_Delivery_Index' );
}

// Bootstrap
AAA_OC_AdbDel_Index::init();
