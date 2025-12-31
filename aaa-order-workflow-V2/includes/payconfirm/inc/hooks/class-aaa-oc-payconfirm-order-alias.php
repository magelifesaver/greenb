<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/inc/hooks/class-aaa-oc-payconfirm-order-alias.php
 * Purpose: Snapshot user payer-alias map onto each new order for fast local matching.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_PayConfirm_Order_Alias {

	public static function init() {
		add_action( 'woocommerce_checkout_create_order', [ __CLASS__, 'on_create' ], 20, 2 );
		add_action( 'woocommerce_checkout_update_order_meta', [ __CLASS__, 'on_update_meta' ], 20, 2 );
		add_action( 'woocommerce_new_order', [ __CLASS__, 'on_new_order' ], 20, 2 );
	}

	public static function on_create( $order, $data ) {
		if ( is_object( $order ) && method_exists( $order, 'get_id' ) ) {
			self::snapshot( (int) $order->get_id(), $order );
		}
	}

	public static function on_update_meta( $order_id, $data ) {
		self::snapshot( (int) $order_id );
	}

	public static function on_new_order( $order_id, $order = null ) {
		self::snapshot( (int) $order_id, $order );
	}

	private static function snapshot( $order_id, $order_obj = null ) {
		$order = $order_obj ?: ( function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null );
		if ( ! $order ) return;

		$uid = (int) $order->get_user_id();
		if ( ! $uid ) return;

		$map = get_user_meta( $uid, 'aaa_oc_pay_accounts', true );
		if ( is_array( $map ) && ! empty( $map ) ) {
			update_post_meta( $order_id, 'aaa_oc_pay_accounts', $map );
			update_post_meta( $order_id, '_pc_alias_snapshot_ts', current_time( 'mysql', true ) );
		}
	}
}
