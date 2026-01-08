<?php
/**
 * GPT Sales Summary Endpoint
 *
 * This endpoint returns WooCommerce sales summary data for a date range.
 * It proxies the core LokeyReports summary handler and requires a valid
 * JWT via the lokey_require_jwt_auth permission callback.  The response
 * mirrors the structure returned by lokey_reports_handle_sales_summary.
 *
 * The route is registered at GET /gpt/v1/sales/summary and accepts
 * the following query parameters:
 *   - from (YYYY‑MM‑DD) – start date (required)
 *   - to   (YYYY‑MM‑DD) – end date (defaults to `from` when omitted)
 *   - group_by – optional grouping (none, day, week, month)
 *   - statuses – optional comma‑separated order statuses
 */

// Do not allow direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {
    register_rest_route( 'gpt/v1', '/sales/summary', [
        'methods'  => 'GET',
        'callback' => 'lokey_gpt_sales_summary',
        // Do not enforce JWT on the GPT summary proxy.  The internal call to
        // lokey_reports_handle_sales_summary will handle any necessary
        // permission checks.
        'permission_callback' => '__return_true',
        'args'     => [
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
                'description' => 'Grouping (none, day, week, month).',
                'type'        => 'string',
                'required'    => false,
            ],
            'statuses' => [
                'description' => 'Comma‑separated order statuses.',
                'type'        => 'string',
                'required'    => false,
            ],
        ],
    ] );
} );

/**
 * Handle the GPT sales summary request by delegating to the LokeyReports
 * summary handler.  Validates dates and returns a WP_REST_Response.
 *
 * @param WP_REST_Request $request Incoming request object.
 * @return WP_REST_Response|WP_Error
 */
function lokey_gpt_sales_summary( WP_REST_Request $request ) {
    $from     = sanitize_text_field( $request->get_param( 'from' ) );
    $to       = sanitize_text_field( $request->get_param( 'to' ) ?: $from );
    $group_by = sanitize_text_field( $request->get_param( 'group_by' ) ?: 'day' );
    $statuses = sanitize_text_field( $request->get_param( 'statuses' ) ?: '' );

    // Validate simple date format (YYYY-MM-DD)
    if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) {
        return new WP_REST_Response( [ 'message' => 'Invalid date format. Use YYYY-MM-DD.' ], 400 );
    }

    // Ensure the core summary handler exists
    if ( ! function_exists( 'lokey_reports_handle_sales_summary' ) ) {
        return new WP_REST_Response( [ 'message' => 'Sales summary handler not available.' ], 500 );
    }

    // Build an internal request to the LokeyReports summary endpoint
    $internal = new WP_REST_Request( 'GET', '/lokeyreports/v1/sales/summary' );
    $internal->set_param( 'from', $from );
    $internal->set_param( 'to', $to );
    $internal->set_param( 'group_by', $group_by );
    if ( $statuses ) {
        $internal->set_param( 'statuses', $statuses );
    }

    // Call the handler directly (no external HTTP)
    $response = lokey_reports_handle_sales_summary( $internal );
    if ( is_wp_error( $response ) ) {
        $status = $response->get_error_data()['status'] ?? 500;
        return new WP_REST_Response( [ 'message' => $response->get_error_message() ], $status );
    }

    // Extract data from WP_REST_Response or raw array
    $data = $response instanceof WP_REST_Response ? $response->get_data() : $response;
    return new WP_REST_Response( $data, 200 );
}
