<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/productsearch/hooks/class-aaa-oc-productsearch-search-hooks.php
 * Purpose: Hook standard Woo searches; resolve to product IDs from index table and hand to Woo.
 * Version: 1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAA_OC_ProductSearch_Search_Hooks {

	public static function init() {
		add_action( 'pre_get_posts', array( __CLASS__, 'pre_get_posts' ), 99 );
		add_filter(
			'woocommerce_product_data_store_cpt_get_products_query',
			array( __CLASS__, 'wc_datastore' ),
			99,
			2
		);
	}

	public static function pre_get_posts( $q ) {
		if ( is_admin() || ! $q->is_main_query() ) {
			return;
		}

		$s = (string) $q->get( 's' );
		if ( '' === $s ) {
			return;
		}

		// Resolve via ProductSearch index first.
		$ids = AAA_OC_ProductSearch_Helpers::search_index( $s );
		if ( empty( $ids ) ) {
			// Let WordPress do its normal search when our index has nothing.
			return;
		}

		// Apply your redirect rule: only if all results share the same brand/category.
		AAA_OC_ProductSearch_Helpers::maybe_redirect_by_results( $s, $ids );

		// No redirect → constrain query to our product IDs.
		$q->set( 'post_type', array( 'product' ) );
		$q->set( 'post__in', $ids );
		$q->set( 'orderby', 'post__in' );

		// Avoid WP's broad text search after resolving IDs.
		$q->set( 's', '' );
	}

	public static function wc_datastore( $wp_args, $wc_query ) {
		$s = $wp_args['s'] ?? ( $wp_args['search'] ?? '' );
		if ( ! $s ) {
			return $wp_args;
		}

		// Only adjust frontend/product contexts; avoid surprising admin screens.
		if ( is_admin() ) {
			return $wp_args;
		}

		$ids = AAA_OC_ProductSearch_Helpers::search_index( $s );
		if ( empty( $ids ) ) {
			return $wp_args;
		}

		AAA_OC_ProductSearch_Helpers::maybe_redirect_by_results( $s, $ids );

		$wp_args['post__in'] = $ids;
		$wp_args['orderby']  = 'post__in';
		unset( $wp_args['s'], $wp_args['search'] );

		return $wp_args;
	}
}
