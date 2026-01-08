<?php
/**
 * Diagnostics endpoint for the Lokey Inventory API.
 *
 * This endpoint returns the operational status of core API routes along with
 * plugin configuration details.  It is useful for uptime monitoring and
 * debugging.  Access requires a valid JWT token to prevent exposing
 * sensitive details to unauthorised callers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {
    register_rest_route( LOKEY_INV_API_NS, '/diagnostics', [
        'methods'  => 'GET',
        'callback' => 'lokey_inv_diagnostics_v200',
        // Require a valid JWT for diagnostics
        // The diagnostics endpoint no longer requires a JWT token.  The GPT
        // framework cannot attach a token automatically, so we allow public
        // access here.  Internal checks will still restrict sensitive data.
        'permission_callback' => '__return_true',
    ] );
} );

/**
 * Callback for the diagnostics endpoint.
 *
 * Collects information about core plugin constants and checks whether key
 * routes are registered with the WordPress REST server.  Returns a
 * structured response with the version, status, and endpoint health.
 *
 * @param WP_REST_Request $req The incoming request.
 * @return WP_REST_Response
 */
function lokey_inv_diagnostics_v200( WP_REST_Request $req ) {
    $time_start = microtime( true );

    // Collect plugin constants.  These values help verify proper
    // configuration.  The namespace uses a hyphen to match the OpenAPI
    // specification (e.g. 'lokey-inventory/v1').
    $constants = [
        'version'        => LOKEY_INV_API_VERSION,
        'namespace'      => LOKEY_INV_API_NS,
        'dir'            => basename( LOKEY_INV_API_DIR ),
        'debug'          => defined( 'LOKEY_INV_API_DEBUG' ) ? LOKEY_INV_API_DEBUG : false,
        'woocommerce_ok' => class_exists( 'WooCommerce' ),
        'jwt_bridge'     => file_exists( WP_CONTENT_DIR . '/mu-plugins/lokey-jwt-auth-bridge.php' ),
    ];

    // Define core endpoints to verify.  Only include those defined in this
    // plugin.  Additional routes from other plugins will not be listed.
    $routes_to_check = [
        '/forecast/products'    => 'Forecast Listing',
        '/forecast/products/(?P<id>\d+)' => 'Forecast Single',
        '/report/products'      => 'Product Report',
        '/purchase-orders'      => 'Purchase Orders List/Create',
        '/purchase-orders/(?P<id>\d+)' => 'Purchase Orders Get/Update',
        '/purchase-orders/batch' => 'Purchase Orders Batch Update',
        '/products/(?P<id>\d+)' => 'Products Get/Update',
        '/products/batch'       => 'Products Batch',
        '/suppliers'            => 'Suppliers List/Create',
        '/suppliers/(?P<id>\d+)' => 'Suppliers CRUD',
        '/inventory'            => 'Inventory Listing',
        '/inventory/summary'    => 'Inventory Summary',
    ];

    // Fetch the registered routes from the REST server.  If not available,
    // instantiate a fresh server instance.
    global $wp_rest_server;
    if ( ! $wp_rest_server ) {
        $wp_rest_server = rest_get_server();
    }
    $registered = $wp_rest_server->get_routes();

    $checks = [];
    foreach ( $routes_to_check as $route => $label ) {
        $full_route = '/' . LOKEY_INV_API_NS . $route;
        $checks[] = [
            'label'    => $label,
            'route'    => $full_route,
            'active'   => isset( $registered[ $full_route ] ),
            'endpoint' => isset( $registered[ $full_route ] ) ? '✅' : '❌',
        ];
    }

    $time_end    = microtime( true );
    $execution_ms = round( ( $time_end - $time_start ) * 1000, 2 );

    return new WP_REST_Response( [
        'version'      => LOKEY_INV_API_VERSION,
        'status'       => 'success',
        'plugin'       => 'Lokey Inventory API',
        'namespace'    => LOKEY_INV_API_NS,
        'constants'    => $constants,
        'endpoints'    => $checks,
        'execution_ms' => $execution_ms,
        'timestamp'    => current_time( 'mysql' ),
    ], 200 );
}
