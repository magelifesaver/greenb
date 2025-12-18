<?php
/**
 * ============================================================================
 * File Path: /wp-content/mu-plugins/lokey-sales-reports/gpt-proxy/gpt-sales-summary.php
 * Plugin Name: GPT Sales Summary Endpoint (v3.0)
 * Description: JWT-protected GPT proxy that returns Lokey Sales Summary directly via internal handler.
 * Version: 3.0
 * Updated: 2025-12-01
 * Author: Lokey Delivery DevOps
 * ============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('rest_api_init', function () {
	register_rest_route('gpt/v1', '/sales/summary', [
		'methods'             => 'GET',
		'callback'            => 'lokey_gpt_sales_summary_by_date',
		'permission_callback' => 'lokey_require_jwt_auth', // ✅ JWT required
		'args'                => [
			'from' => [
				'description' => 'Start date in YYYY-MM-DD format.',
				'type'        => 'string',
				'required'    => true,
			],
			'to' => [
				'description' => 'End date in YYYY-MM-DD format (optional).',
				'type'        => 'string',
				'required'    => false,
			],
			'group_by' => [
				'description' => 'Optional grouping (none, day, week, month).',
				'type'        => 'string',
				'required'    => false,
			],
			'statuses' => [
				'description' => 'Optional order statuses (comma-separated).',
				'type'        => 'string',
				'required'    => false,
			],
		],
	]);
});

/**
 * --------------------------------------------------------------------------
 * Proxy handler for GPT Sales Summary by date range.
 * Uses internal PHP function, no external REST call.
 * --------------------------------------------------------------------------
 */
function lokey_gpt_sales_summary_by_date( WP_REST_Request $request ) {

	$from      = sanitize_text_field( $request->get_param('from') );
	$to        = sanitize_text_field( $request->get_param('to') ?: $from );
	$group_by  = sanitize_text_field( $request->get_param('group_by') ?: 'day' );
	$statuses  = sanitize_text_field( $request->get_param('statuses') ?: '' );

	// 🧭 Validate date formats
	if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) ) {
		return new WP_REST_Response([ 'error' => 'Invalid date format. Use YYYY-MM-DD.' ], 400);
	}

	// ✅ Build a sub-request internally to LokeyReports summary handler
	if ( ! function_exists( 'lokey_reports_handle_sales_summary' ) ) {
		return new WP_REST_Response([ 'error' => 'Lokey Reports handler not found.' ], 500);
	}

	$internal_request = new WP_REST_Request( 'GET', '/lokeyreports/v1/sales/summary' );
	$internal_request->set_param( 'from', $from );
	$internal_request->set_param( 'to', $to );
	$internal_request->set_param( 'group_by', $group_by );
	if ( ! empty( $statuses ) ) {
		$internal_request->set_param( 'statuses', $statuses );
	}

	// 🧩 Call the internal handler directly (no HTTP)
	$response = lokey_reports_handle_sales_summary( $internal_request );

	// 🧾 Return a clean GPT-safe response
	if ( is_wp_error( $response ) ) {
		return new WP_REST_Response([
			'ok'    => false,
			'error' => $response->get_error_message(),
		], 500);
	}

	$data = $response instanceof WP_REST_Response ? $response->get_data() : $response;

	return new WP_REST_Response([
		'ok'     => true,
		'from'   => $from,
		'to'     => $to,
		'group_by' => $group_by,
		'data'   => $data,
	], 200);
}
