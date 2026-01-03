<?php
/**
 * ============================================================================
 * File Path: /wp-content/plugins/aaa-workflow-ai-reports/includes/api/lokey-client.php
 * Description: Internal LokeyReports client with recursion-safe header.
 * Version: 1.5.0
 * Updated: 2025-12-28
 * Author: AAA Workflow DevOps
 * ============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get WooCommerce sales summary from LokeyReports safely.
 *
 * @param string $from Start date (YYYY-MM-DD)
 * @param string $to   End date (YYYY-MM-DD)
 * @return array
 */
function aaa_wf_ai_get_sales_summary( $from, $to ) {
    // Attempt to call the LokeyReports endpoint via the internal REST API first to
    // avoid outbound HTTP requests to the same site.  If rest_do_request is
    // unavailable or fails, fall back to wp_remote_get below.
    if ( class_exists( '\\WP_REST_Request' ) && function_exists( 'rest_do_request' ) ) {
        try {
            $req = new \WP_REST_Request( 'GET', '/lokeyreports/v1/sales/summary' );
            $req->set_param( 'from', $from );
            $req->set_param( 'to', $to );
            $req->set_header( 'X-Lokey-Internal', '1' );
            /** @var \WP_REST_Response|\WP_Error $resp */
            $resp = rest_do_request( $req );
            if ( is_wp_error( $resp ) ) {
                aaa_wf_ai_debug( 'Lokey API internal error: ' . $resp->get_error_message(), basename( __FILE__ ), 'lokey-client' );
            } elseif ( $resp instanceof \WP_REST_Response ) {
                $status = $resp->get_status();
                if ( $status === 200 ) {
                    $data = $resp->get_data();
                    if ( is_array( $data ) && ! empty( $data['totals'] ) ) {
                        aaa_wf_ai_debug( "✅ Retrieved Lokey summary internally for {$from}→{$to}", basename( __FILE__ ), 'lokey-client' );
                        return $data;
                    }
                    aaa_wf_ai_debug( "LokeyReports internal call returned empty or malformed data for {$from}→{$to}", basename( __FILE__ ), 'lokey-client' );
                } else {
                    aaa_wf_ai_debug( "LokeyReports internal call returned HTTP {$status} for {$from}→{$to}", basename( __FILE__ ), 'lokey-client' );
                }
            }
        } catch ( \Exception $e ) {
            aaa_wf_ai_debug( 'LokeyReports internal call exception: ' . $e->getMessage(), basename( __FILE__ ), 'lokey-client' );
        }
    }

    // Fallback: perform an outbound HTTP request to the REST URL.  Set a
    // reasonable timeout and send the X-Lokey-Internal header for authorization.
    $url = rest_url( sprintf( 'lokeyreports/v1/sales/summary?from=%s&to=%s', $from, $to ) );
    $response = wp_remote_get( $url, [
        'timeout' => 30,
        'headers' => [
            'X-Lokey-Internal' => '1',
        ],
    ] );
    if ( is_wp_error( $response ) ) {
        aaa_wf_ai_debug( 'Lokey API error: ' . $response->get_error_message(), basename( __FILE__ ), 'lokey-client' );
        return [];
    }
    $code = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    if ( $code !== 200 ) {
        aaa_wf_ai_debug( "LokeyReports HTTP {$code} for {$from}→{$to}", basename( __FILE__ ), 'lokey-client' );
        return [];
    }
    if ( empty( $data ) || ! isset( $data['totals'] ) ) {
        aaa_wf_ai_debug( "LokeyReports empty response {$from}→{$to}", basename( __FILE__ ), 'lokey-client' );
        return [];
    }
    aaa_wf_ai_debug( "✅ LokeyReports summary retrieved via HTTP {$from}→{$to}", basename( __FILE__ ), 'lokey-client' );
    return $data;
}

/**
 * Fetch top-selling products for the given date range.
 *
 * This helper calls the `/sales/products` endpoint with a specified limit
 * and sorts results by net sales in descending order.  It returns the
 * decoded JSON response from LokeyReports or an empty array on failure.
 *
 * @param string $from   Start date (YYYY-MM-DD)
 * @param string $to     End date (YYYY-MM-DD)
 * @param int    $limit  How many top products to return (default 5)
 * @return array
 */
function aaa_wf_ai_get_top_products( $from, $to, $limit = 5 ) {
    $limit = max( 1, (int) $limit );
    $url = rest_url( sprintf( 'lokeyreports/v1/sales/products?from=%s&to=%s&limit=%d&order_by=net_sales&order=desc', $from, $to, $limit ) );

    $response = wp_remote_get( $url, [
        'timeout' => 30,
        'headers' => [
            'X-Lokey-Internal' => '1',
        ],
    ] );

    if ( is_wp_error( $response ) ) {
        aaa_wf_ai_debug( 'Top products API error: ' . $response->get_error_message(), basename( __FILE__ ), 'lokey-client' );
        return [];
    }

    $code = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( $code !== 200 || empty( $data ) || ! isset( $data['rows'] ) ) {
        aaa_wf_ai_debug( "Failed to fetch top products ({$from}→{$to}) HTTP {$code}", basename( __FILE__ ), 'lokey-client' );
        return [];
    }

    aaa_wf_ai_debug( sprintf( '✅ Top %d products retrieved for %s→%s', $limit, $from, $to ), basename( __FILE__ ), 'lokey-client' );
    return $data;
}

/**
 * Fetch top categories for the given date range.
 *
 * Calls the `/sales/categories` endpoint and orders results by net sales in
 * descending order.  Returns the decoded JSON response or an empty array on
 * failure.
 *
 * @param string $from  Start date (YYYY-MM-DD)
 * @param string $to    End date (YYYY-MM-DD)
 * @param int    $limit How many top categories to return (default 5)
 * @return array
 */
function aaa_wf_ai_get_category_sales( $from, $to, $limit = 5 ) {
    $limit = max( 1, (int) $limit );
    // Attempt internal REST call first
    if ( class_exists( '\\WP_REST_Request' ) && function_exists( 'rest_do_request' ) ) {
        try {
            $req = new \WP_REST_Request( 'GET', '/lokeyreports/v1/sales/categories' );
            $req->set_param( 'from', $from );
            $req->set_param( 'to', $to );
            $req->set_param( 'limit', $limit );
            $req->set_param( 'order_by', 'net_sales' );
            $req->set_param( 'order', 'desc' );
            $req->set_header( 'X-Lokey-Internal', '1' );
            $resp = rest_do_request( $req );
            if ( ! is_wp_error( $resp ) && $resp instanceof \WP_REST_Response && $resp->get_status() === 200 ) {
                $data = $resp->get_data();
                if ( is_array( $data ) && isset( $data['rows'] ) ) {
                    aaa_wf_ai_debug( sprintf( '✅ Top %d categories retrieved internally for %s→%s', $limit, $from, $to ), basename( __FILE__ ), 'lokey-client' );
                    return $data;
                }
            }
        } catch ( \Exception $e ) {
            aaa_wf_ai_debug( 'Categories internal call exception: ' . $e->getMessage(), basename( __FILE__ ), 'lokey-client' );
        }
    }
    // Fallback HTTP call
    $url = rest_url( sprintf( 'lokeyreports/v1/sales/categories?from=%s&to=%s&limit=%d&order_by=net_sales&order=desc', $from, $to, $limit ) );
    $response = wp_remote_get( $url, [
        'timeout' => 30,
        'headers' => [ 'X-Lokey-Internal' => '1' ],
    ] );
    if ( is_wp_error( $response ) ) {
        aaa_wf_ai_debug( 'Top categories API error: ' . $response->get_error_message(), basename( __FILE__ ), 'lokey-client' );
        return [];
    }
    $code = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    if ( $code !== 200 || empty( $data ) || ! isset( $data['rows'] ) ) {
        aaa_wf_ai_debug( sprintf( 'Failed to fetch categories (%s→%s) HTTP %d', $from, $to, $code ), basename( __FILE__ ), 'lokey-client' );
        return [];
    }
    aaa_wf_ai_debug( sprintf( '✅ Top %d categories retrieved via HTTP for %s→%s', $limit, $from, $to ), basename( __FILE__ ), 'lokey-client' );
    return $data;
}

/**
 * Fetch inventory summary across suppliers.
 *
 * Calls the `/inventory/summary` endpoint from the Lokey inventory API.  This
 * endpoint does not require a date range.  Returns the decoded JSON or an
 * empty array on failure.
 *
 * @return array
 */
function aaa_wf_ai_get_inventory_summary() {
    // Try internal call first
    if ( class_exists( '\\WP_REST_Request' ) && function_exists( 'rest_do_request' ) ) {
        try {
            $req = new \WP_REST_Request( 'GET', '/lokey-inventory/v1/inventory/summary' );
            $req->set_header( 'X-Lokey-Internal', '1' );
            $resp = rest_do_request( $req );
            if ( ! is_wp_error( $resp ) && $resp instanceof \WP_REST_Response && $resp->get_status() === 200 ) {
                $data = $resp->get_data();
                if ( is_array( $data ) && isset( $data['totals'] ) ) {
                    aaa_wf_ai_debug( '✅ Inventory summary retrieved internally.', basename( __FILE__ ), 'lokey-client' );
                    return $data;
                }
            }
        } catch ( \Exception $e ) {
            aaa_wf_ai_debug( 'Inventory internal call exception: ' . $e->getMessage(), basename( __FILE__ ), 'lokey-client' );
        }
    }
    // Fallback HTTP call
    $url = rest_url( 'lokey-inventory/v1/inventory/summary' );
    $response = wp_remote_get( $url, [
        'timeout' => 30,
        'headers' => [ 'X-Lokey-Internal' => '1' ],
    ] );
    if ( is_wp_error( $response ) ) {
        aaa_wf_ai_debug( 'Inventory API error: ' . $response->get_error_message(), basename( __FILE__ ), 'lokey-client' );
        return [];
    }
    $code = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    if ( $code !== 200 || empty( $data ) || ! isset( $data['totals'] ) ) {
        aaa_wf_ai_debug( sprintf( 'Failed to fetch inventory summary (HTTP %d)', $code ), basename( __FILE__ ), 'lokey-client' );
        return [];
    }
    aaa_wf_ai_debug( '✅ Inventory summary retrieved via HTTP.', basename( __FILE__ ), 'lokey-client' );
    return $data;
}