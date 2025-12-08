<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/admin/post/aaa-paim-start-run.php
 * Purpose: Admin-post handler to start a (dry/live) processing run for an Attribute Set and redirect to the Run Report — PAIM naming.
 * Version: 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Per-file debug toggle (dev default true).
 * Enable with: define('AAA_PAIM_START_RUN_DEBUG', true);
 */
if ( ! defined( 'AAA_PAIM_START_RUN_DEBUG' ) ) {
	define( 'AAA_PAIM_START_RUN_DEBUG', true );
}

/**
 * Entry: /wp-admin/admin-post.php?action=aaa_paim_start_run&set_id=###&nonce=...&dry_run=1
 * Optionally include &cat=TERM_ID to skip lookup.
 */
function aaa_paim_admin_post_start_run() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'aaa-paim' ) );
	}

	$nonce = isset( $_REQUEST['nonce'] ) ? wp_unslash( $_REQUEST['nonce'] ) : '';
	if ( ! wp_verify_nonce( $nonce, 'aaa_paim_process_set' ) ) {
		wp_die( esc_html__( 'Invalid request (nonce).', 'aaa-paim' ) );
	}

	$set_id  = isset( $_REQUEST['set_id'] ) ? intval( $_REQUEST['set_id'] ) : 0;
	$dry_run = isset( $_REQUEST['dry_run'] ) ? ( intval( $_REQUEST['dry_run'] ) ? 1 : 0 ) : 1;
	$cat_id  = isset( $_REQUEST['cat'] ) ? intval( $_REQUEST['cat'] ) : 0;

	if ( $set_id <= 0 ) {
		wp_die( esc_html__( 'Missing attribute set.', 'aaa-paim' ) );
	}

	// Try to resolve category if not provided.
	if ( $cat_id <= 0 ) {
		$cat_id = aaa_paim_try_resolve_category_for_set( $set_id );
	}

	if ( $cat_id <= 0 ) {
		wp_die( esc_html__( 'Could not resolve category for this attribute set.', 'aaa-paim' ) );
	}

	$run_id = aaa_paim_create_run_row( $set_id, $cat_id, $dry_run );

	if ( ! $run_id ) {
		wp_die( esc_html__( 'Failed to create run.', 'aaa-paim' ) );
	}

	// Precompute total_products for visibility on the report header.
	$total = aaa_paim_count_products_in_category( $cat_id );
	aaa_paim_update_run_totals( $run_id, array( 'total_products' => $total ) );

	// Redirect to the PAIM Run Report page.
	$dest = add_query_arg(
		array(
			'page'   => 'aaa-paim-run',
			'run_id' => $run_id,
		),
		admin_url( 'admin.php' )
	);

	if ( AAA_PAIM_START_RUN_DEBUG ) {
		error_log( sprintf( '[PAIM][StartRun] set=%d cat=%d dry=%d run=%d total=%d', $set_id, $cat_id, $dry_run, $run_id, $total ) );
	}

	wp_safe_redirect( $dest );
	exit;
}
add_action( 'admin_post_aaa_paim_start_run', 'aaa_paim_admin_post_start_run' );

/**
 * Create a run row.
 */
function aaa_paim_create_run_row( int $set_id, int $cat_id, int $dry_run = 1 ) {
	global $wpdb;

	$tbl = $wpdb->prefix . 'aaa_paim_runs';
	$data = array(
		'attribute_set_id' => $set_id,
		'category_term_id' => $cat_id,
		'status'           => 'queued',
		'requested_by'     => get_current_user_id(),
		'requested_at'     => current_time( 'mysql' ),
		'started_at'       => null,
		'finished_at'      => null,
		'total_products'   => 0,
		'processed_ok'     => 0,
		'processed_err'    => 0,
		'ai_model_used'    => aaa_paim_get_global_ai_model_slug(),
		'source_used'      => aaa_paim_get_global_source_slug(),
		'dry_run'          => $dry_run ? 1 : 0,
		'notes'            => '',
	);

	$ok = $wpdb->insert( $tbl, $data, array(
		'%d','%d','%s','%d','%s','%s','%s','%s','%d','%d','%d','%s','%s','%d','%s'
	) );

	if ( ! $ok ) {
		if ( AAA_PAIM_START_RUN_DEBUG ) {
			error_log( '[PAIM][StartRun] DB insert failed: ' . $wpdb->last_error );
		}
		return 0;
	}
	return intval( $wpdb->insert_id );
}

/**
 * Update totals for a run.
 */
function aaa_paim_update_run_totals( int $run_id, array $fields ) {
	global $wpdb;
	$tbl = $wpdb->prefix . 'aaa_paim_runs';

	$sets = array();
	foreach ( $fields as $k => $v ) {
		$k_safe = preg_replace( '/[^a-z0-9_]/i', '', $k );
		$sets[] = $wpdb->prepare( "{$k_safe} = %d", intval( $v ) );
	}
	if ( empty( $sets ) ) return;

	$sql = "UPDATE {$tbl} SET " . implode( ', ', $sets ) . " WHERE run_id = %d";
	$wpdb->query( $wpdb->prepare( $sql, $run_id ) );
}

/**
 * Attempt to resolve the category term ID for an attribute set.
 * We support a few storage patterns to stay compatible with your current code base.
 */
function aaa_paim_try_resolve_category_for_set( int $set_id ) : int {
	global $wpdb;

	// 1) Custom table pattern: wp_*_aaa_paim_attribute_sets (category_term_id column)
	$maybe_table = $wpdb->prefix . 'aaa_paim_attribute_sets';
	$has_table   = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $maybe_table ) ) === $maybe_table;
	if ( $has_table ) {
		$term_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT category_term_id FROM {$maybe_table} WHERE id = %d LIMIT 1", $set_id
		) );
		if ( $term_id ) return intval( $term_id );
	}

	// 2) CPT/meta pattern: post meta _paim_category_term_id
	$meta_term = get_post_meta( $set_id, '_paim_category_term_id', true );
	if ( $meta_term ) return intval( $meta_term );

	return 0;
}

/**
 * Count products in a WooCommerce category term.
 */
function aaa_paim_count_products_in_category( int $term_id ) : int {
	global $wpdb;

	$sql = "
		SELECT COUNT(DISTINCT p.ID)
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
		INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		WHERE tt.taxonomy = 'product_cat'
		  AND tt.term_id = %d
		  AND p.post_type = 'product'
		  AND p.post_status IN ('publish')
	";
	$cnt = $wpdb->get_var( $wpdb->prepare( $sql, $term_id ) );
	return intval( $cnt );
}

/**
 * Pull AI model + source from Global settings (graceful fallback).
 * Replace these with your real getters if they already exist.
 */
function aaa_paim_get_global_ai_model_slug() : string {
	$slug = get_option( 'aaa_paim_global_ai_model', '' );
	return is_string( $slug ) ? $slug : '';
}
function aaa_paim_get_global_source_slug() : string {
	$slug = get_option( 'aaa_paim_global_source', '' );
	return is_string( $slug ) ? $slug : '';
}
