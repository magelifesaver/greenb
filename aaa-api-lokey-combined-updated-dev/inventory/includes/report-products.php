<?php
/**
 * Product Sales Report route registration.
 *
 * Declares the REST route for generating a sales report based on current
 * stock levels and recent sales.  The actual implementation lives in
 * report-products-callback.php to keep this file focused and under the
 * 150‑line guideline.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {
    register_rest_route( LOKEY_INV_API_NS, '/report/products', [
        'methods'  => 'GET',
        'callback' => 'lokey_inv_report_products_v200',
        // Make the product report endpoint publicly accessible for GPT actions.
        'permission_callback' => '__return_true',
    ] );
} );
