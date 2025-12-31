<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/hooks/class-aaa-oc-core-hooks.php
 * Purpose: Core-wide WooCommerce hooks that trigger indexing (no UI/module dependency)
 * Version: 1.0.2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'AAA_OC_Core_Hooks' ) ) return;

class AAA_OC_Core_Hooks {

	/** Per-file debug toggle (dev default: true) */
	const DEBUG_THIS_FILE = true;

	/** One-time init guard */
	private static $did = false;

	public static function init() {
		if ( self::$did ) return;
		self::$did = true;

		// New order: ensure related rows exist early (e.g., payment row)
		add_action( 'woocommerce_new_order', [ __CLASS__, 'handle_new_order' ], 10, 1 );

		// Reindex after order object is saved/updated
		add_action( 'woocommerce_after_order_object_save', [ __CLASS__, 'handle_order_save' ], 20, 1 );
		add_action( 'woocommerce_update_order',            [ __CLASS__, 'handle_order_save' ], 10, 1 );

		// On status events that matter
		add_action( 'woocommerce_order_status_cancelled',  [ __CLASS__, 'handle_order_status_event' ], 10, 1 );
		add_action( 'woocommerce_order_status_completed',  [ __CLASS__, 'handle_order_status_event' ], 10, 1 );
		add_action( 'woocommerce_order_status_processing', [ __CLASS__, 'handle_order_status_event' ], 10, 1 );

		// Cleanup on delete
		add_action( 'before_delete_post', [ __CLASS__, 'handle_order_delete' ], 10, 1 );

		self::log('[CoreHooks] init done.');
	}

	/** Ensure payment row exists when a new order is placed */
	public static function handle_new_order( $order_id ) {
		if ( class_exists( 'AAA_OC_Payment_Fields' ) ) {
			AAA_OC_Payment_Fields::ensure_payment_row_exists( $order_id );
			self::log("[CoreHooks] ensured payment row for order #{$order_id}");
		}
	}

	/** Reindex order after save/update */
	public static function handle_order_save( $order ) {
		if ( is_a( $order, 'WC_Order' ) && class_exists( 'AAA_OC_Indexing' ) ) {
			(new AAA_OC_Indexing())->index_order( $order->get_id() );
			self::log('[CoreHooks] reindexed on save: #' . $order->get_id());
		}
	}

	/** Reindex on selected status transitions */
	public static function handle_order_status_event( $order_id ) {
		if ( class_exists( 'AAA_OC_Indexing' ) ) {
			(new AAA_OC_Indexing())->index_order( $order_id );
			self::log('[CoreHooks] reindexed on status event: #' . $order_id);
		}
	}

	/** Remove index row when order is deleted */
	public static function handle_order_delete( $post_id ) {
		if ( get_post_type( $post_id ) !== 'shop_order' ) return;

		global $wpdb;
		$table = $wpdb->prefix . 'aaa_oc_order_index';
		$wpdb->delete( $table, [ 'order_id' => $post_id ], [ '%d' ] );

		self::log("[CoreHooks] deleted index row for order #{$post_id}");
	}

	private static function log( $msg ) {
		if ( self::DEBUG_THIS_FILE && function_exists('aaa_oc_log') ) {
			aaa_oc_log( $msg );
		}
	}
}

// Boot
AAA_OC_Core_Hooks::init();
