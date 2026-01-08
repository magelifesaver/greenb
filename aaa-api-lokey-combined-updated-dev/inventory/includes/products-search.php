<?php
/**
 * Lightweight product search for GPT.
 *
 * Route:
 *   - GET /lokey-inventory/v1/products/search
 *
 * Purpose:
 * - Prevent large WooCommerce product payloads when the agent only needs
 *   IDs + a few identifiers (name, sku, status).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {

    register_rest_route( LOKEY_INV_API_NS, '/products/search', [
        'methods'  => 'GET',
        'callback' => function ( WP_REST_Request $req ) {

            if ( ! function_exists( 'wc_get_products' ) ) {
                return new WP_REST_Response( [
                    'version'   => LOKEY_INV_API_VERSION,
                    'status'    => 'error',
                    'code'      => 500,
                    'message'   => 'WooCommerce is not available.',
                    'timestamp' => current_time( 'mysql' ),
                ], 500 );
            }

            $search   = $req->get_param( 'search' );
            $search   = is_string( $search ) ? sanitize_text_field( $search ) : '';

            $sku      = $req->get_param( 'sku' );
            $sku      = is_string( $sku ) ? sanitize_text_field( $sku ) : '';

            $page     = max( 1, absint( $req->get_param( 'page' ) ?: 1 ) );
            $per_page = absint( $req->get_param( 'per_page' ) ?: 20 );
            $per_page = min( max( 1, $per_page ), 50 );

            $args = [
                'status' => [ 'publish', 'private', 'draft', 'pending' ],
                'type'   => [ 'simple', 'variable', 'variation' ],
                'limit'  => $per_page,
                'page'   => $page,
                'return' => 'ids',
            ];

            if ( $search !== '' ) {
                $args['search'] = $search;
            }
            if ( $sku !== '' ) {
                $args['sku'] = $sku;
            }

            $ids = wc_get_products( $args );
            $rows = [];

            foreach ( $ids as $id ) {
                $p = wc_get_product( $id );
                if ( ! $p ) {
                    continue;
                }

                $brand_ids = [];
                if ( taxonomy_exists( 'berocket_brand' ) ) {
                    $brand_ids = wp_get_object_terms( $id, 'berocket_brand', [ 'fields' => 'ids' ] );
                    $brand_ids = is_array( $brand_ids ) ? array_map( 'intval', $brand_ids ) : [];
                }

                $rows[] = [
                    'id'             => (int) $id,
                    'name'           => $p->get_name(),
                    'sku'            => $p->get_sku(),
                    'type'           => $p->get_type(),
                    'status'         => $p->get_status(),
                    'stock_status'   => $p->get_stock_status(),
                    'stock_quantity' => $p->managing_stock() ? (int) $p->get_stock_quantity() : null,
                    'regular_price'  => $p->get_regular_price(),
                    'sale_price'     => $p->get_sale_price(),
                    'category_ids'   => method_exists( $p, 'get_category_ids' ) ? $p->get_category_ids() : [],
                    'brand_ids'      => $brand_ids,
                ];
            }

            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => 'success',
                'search'    => $search,
                'sku'       => $sku,
                'page'      => $page,
                'per_page'  => $per_page,
                'count'     => count( $rows ),
                'rows'      => $rows,
                'timestamp' => current_time( 'mysql' ),
            ], 200 );
        },
        'permission_callback' => '__return_true',
        'args' => [
            'search'   => [ 'type' => 'string' ],
            'sku'      => [ 'type' => 'string' ],
            'page'     => [ 'type' => 'integer', 'default' => 1 ],
            'per_page' => [ 'type' => 'integer', 'default' => 20 ],
        ],
    ] );

} );
