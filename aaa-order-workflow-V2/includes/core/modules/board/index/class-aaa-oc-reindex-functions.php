<?php
/**
 * Filepath: /aaa-order-workflow/includes/indexers/class-aaa-oc-reindex-functions.php
 *
 * Purpose:
 * This file hooks into WooCommerce’s order save lifecycle
 * and triggers reindexing into our custom order index table
 * by calling the AAA_OC_Indexer class method.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'woocommerce_after_order_object_save', 'aaa_oc_reindex_on_order_save' );

function aaa_oc_reindex_on_order_save( $order ) {
	if ( is_a( $order, 'WC_Order' ) ) {
		$order_id = $order->get_id();
		if ( class_exists( 'AAA_OC_Indexing' ) ) {
			$indexer = new AAA_OC_Indexing();
			$indexer->index_order( $order_id );
		}
	}
}
