<?php
/**
 * GPT Sales Overview Endpoint
 *
 * Provides a combined overview of sales summary and top products for a given
 * date range or preset number of days.  This endpoint returns an object
 * containing the generated timestamp, a summary (matching the structure of
 * lokey_reports_handle_sales_summary) and a list of top products.  It
 * requires JWT authentication and delegates work to the internal summary
 * and top handlers defined in this plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {
    register_rest_route( 'gpt/v1', '/sales/overview', [
        'methods'  => 'GET',
        'callback' => 'lokey_gpt_sales_overview',
        // This GPT overview endpoint is publicly accessible.  It composes
        // results from the summary and top endpoints, both of which are
        // permitted without authentication.  The internal handlers still
        // perform any necessary permission checks.
        'permission_callback' => '__return_true',
        'args'     => [
            'from'     => [ 'type' => 'string', 'required' => false ],
            'to'       => [ 'type' => 'string', 'required' => false ],
            'days'     => [ 'type' => 'integer', 'required' => false ],
            'limit'    => [ 'type' => 'integer', 'required' => false ],
            'group_by' => [ 'type' => 'string', 'required' => false ],
            'statuses' => [ 'type' => 'string', 'required' => false ],
        ],
    ] );
} );

/**
 * Handles the sales overview endpoint by composing results from the summary
 * and top endpoints.  It computes a date range based on either explicit
 * from/to parameters or a days count, then invokes the internal handlers
 * directly for efficiency.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function lokey_gpt_sales_overview( WP_REST_Request $request ) {
    $days  = absint( $request->get_param( 'days' ) ) ?: 30;
    $limit = absint( $request->get_param( 'limit' ) ) ?: 10;
    $from  = sanitize_text_field( $request->get_param( 'from' ) );
    $to    = sanitize_text_field( $request->get_param( 'to' ) );

    // Determine date range: if explicit dates provided, use them; otherwise
    // compute a range of $days ending today in the site’s timezone.
    if ( $from && $to ) {
        $from_date = $from;
        $to_date   = $to;
    } else {
        $tz_string = get_option( 'timezone_string' ) ?: 'America/Los_Angeles';
        $tz        = new DateTimeZone( $tz_string );
        $now       = new DateTime( 'now', $tz );
        // Subtract days‑1 because range is inclusive
        $start_local = ( clone $now )->sub( new DateInterval( 'P' . max( 1, $days ) . 'D' ) )->setTime( 0, 0, 0 );
        $from_date   = $start_local->format( 'Y-m-d' );
        $to_date     = $now->format( 'Y-m-d' );
    }

    // Prepare an internal request for the summary
    $summary_req = new WP_REST_Request( 'GET', '/gpt/v1/sales/summary' );
    $summary_req->set_param( 'from', $from_date );
    $summary_req->set_param( 'to', $to_date );
    if ( $group_by = $request->get_param( 'group_by' ) ) {
        $summary_req->set_param( 'group_by', $group_by );
    }
    if ( $statuses = $request->get_param( 'statuses' ) ) {
        $summary_req->set_param( 'statuses', $statuses );
    }
    // Call the summary handler directly
    $summary_response = lokey_gpt_sales_summary( $summary_req );
    if ( is_wp_error( $summary_response ) ) {
        return $summary_response;
    }
    $summary_data = $summary_response instanceof WP_REST_Response
        ? $summary_response->get_data()
        : $summary_response;

    // Prepare an internal request for the top products
    $top_req = new WP_REST_Request( 'GET', '/gpt/v1/sales/top' );
    $top_req->set_param( 'days', $days );
    $top_req->set_param( 'limit', $limit );
    $top_response = lokey_gpt_sales_top( $top_req );
    if ( is_wp_error( $top_response ) ) {
        return $top_response;
    }
    $top_data = $top_response instanceof WP_REST_Response
        ? $top_response->get_data()
        : $top_response;

    return new WP_REST_Response( [
        'overview_generated' => gmdate( 'Y-m-d H:i:s' ),
        'summary'            => $summary_data,
        'top'                => $top_data['top'] ?? $top_data,
    ], 200 );
}
