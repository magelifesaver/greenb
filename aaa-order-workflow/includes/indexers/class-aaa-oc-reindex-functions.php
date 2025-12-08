<?php
/**
 * Filepath: /wp-content/plugins/aaa-order-workflow/includes/indexers/class-aaa-oc-reindex-functions.php
 *
 * Purpose:
 * 1) Legacy hook: listen to WooCommerce's order save lifecycle and (optionally)
 *    trigger reindexing via AAA_OC_Indexer (older class name, kept for BC).
 * 2) NEW: Listen for _order_total meta updates on shop_order posts and trigger
 *    AAA_OC_Indexing::index_order() so orders created via custom tools
 *    (e.g. Order Creator) get a fresh, complete snapshot in aaa_oc_order_index.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Legacy hook (no-op today because AAA_OC_Indexer class does not exist).
// Left in place to avoid surprises for any code relying on it.
// -----------------------------------------------------------------------------

// 🪝 Hook: After WooCommerce order save (WC_Order object)
add_action( 'woocommerce_after_order_object_save', 'aaa_oc_reindex_on_order_save' );

/**
 * Reindex this order when Woo saves the order object.
 * This originally targeted AAA_OC_Indexer (old class name).
 *
 * @param WC_Order $order
 */
function aaa_oc_reindex_on_order_save( $order ) {
	if ( is_a( $order, 'WC_Order' ) ) {
		$order_id = $order->get_id();

		// Kept for backward compatibility; currently does nothing because
		// AAA_OC_Indexer class is not defined in V1.
		if ( class_exists( 'AAA_OC_Indexer' ) ) {
			$indexer = new AAA_OC_Indexer();
			$indexer->index_order( $order_id );
		}
	}
}

// -----------------------------------------------------------------------------
// NEW: Meta-based reindex trigger for orders created via custom tools
// -----------------------------------------------------------------------------
//
// Many non-checkout flows (like the Order Creator plugin) build orders by
// calling wp_insert_post() + update_post_meta() directly, without saving a
// WC_Order object after all meta + line items are in place. In those cases,
// our existing hooks can index too early, producing rows with:
//   - total_amount = 0
//   - subtotal / tax / discount = 0
//   - empty billing/shipping fields
//
// To fix this, we watch for the _order_total meta being added/updated. That
// usually happens near the end of order construction, after totals and most
// relevant meta have been set. When that happens for a shop_order, we call
// AAA_OC_Indexing::index_order() again to refresh the snapshot.

/**
 * When _order_total changes on a shop_order, reindex the order.
 *
 * @param int    $meta_id    Meta row ID (unused).
 * @param int    $object_id  Post ID (order ID).
 * @param string $meta_key   Meta key being updated.
 * @param mixed  $meta_value New value.
 */
function aaa_oc_reindex_on_order_total_meta( $meta_id, $object_id, $meta_key, $meta_value ) {
	// Only care about _order_total, everything else can be ignored.
	if ( '_order_total' !== $meta_key ) {
		return;
	}

	// Ensure this is a WooCommerce order.
	if ( 'shop_order' !== get_post_type( $object_id ) ) {
		return;
	}

	// Our indexer class must exist.
	if ( ! class_exists( 'AAA_OC_Indexing' ) ) {
		return;
	}

	// Log for debugging so you can see this trigger in aaa_oc.log.
	if ( function_exists( 'aaa_oc_log' ) ) {
		aaa_oc_log( "[Reindex][Meta] Triggered by _order_total meta update for order #{$object_id}" );
	}

	$indexer = new AAA_OC_Indexing();
	$indexer->index_order( (int) $object_id );
}

// Hook for both new and updated meta.
add_action( 'updated_post_meta', 'aaa_oc_reindex_on_order_total_meta', 10, 4 );
add_action( 'added_post_meta',   'aaa_oc_reindex_on_order_total_meta', 10, 4 );
