<?php
/**
 * Lightweight taxonomy/term discovery for GPT.
 *
 * Route:
 *   - GET /lokey-inventory/v1/terms
 *
 * Why:
 * - Replace large static taxonomy dumps with paged, searchable output.
 * - Returns a small payload (id, name, slug, parent).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {

    register_rest_route( LOKEY_INV_API_NS, '/terms', [
        'methods'  => 'GET',
        'callback' => function ( WP_REST_Request $req ) {

            $supported = [
                'product_cat'    => 'WooCommerce Categories',
                'berocket_brand' => 'BeRocket Brands',
                'atum_location'  => 'ATUM Product Locations',
            ];

            $taxonomy = $req->get_param( 'taxonomy' );
            $taxonomy = is_string( $taxonomy ) ? sanitize_key( $taxonomy ) : '';

            if ( empty( $taxonomy ) ) {
                return new WP_REST_Response( [
                    'version'    => LOKEY_INV_API_VERSION,
                    'status'     => 'success',
                    'supported'  => $supported,
                    'timestamp'  => current_time( 'mysql' ),
                ], 200 );
            }

            if ( ! isset( $supported[ $taxonomy ] ) ) {
                return new WP_REST_Response( [
                    'version'   => LOKEY_INV_API_VERSION,
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Unsupported taxonomy. Use one of the supported values.',
                    'supported' => $supported,
                    'timestamp' => current_time( 'mysql' ),
                ], 400 );
            }

            if ( ! taxonomy_exists( $taxonomy ) ) {
                return new WP_REST_Response( [
                    'version'   => LOKEY_INV_API_VERSION,
                    'status'    => 'error',
                    'code'      => 404,
                    'message'   => 'Taxonomy not registered on this site.',
                    'taxonomy'  => $taxonomy,
                    'timestamp' => current_time( 'mysql' ),
                ], 404 );
            }

            $search   = $req->get_param( 'search' );
            $search   = is_string( $search ) ? sanitize_text_field( $search ) : '';
            $page     = max( 1, absint( $req->get_param( 'page' ) ?: 1 ) );
            $per_page = absint( $req->get_param( 'per_page' ) ?: 50 );
            $per_page = min( max( 1, $per_page ), 100 );
            $offset   = ( $page - 1 ) * $per_page;

            $args = [
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'number'     => $per_page,
                'offset'     => $offset,
            ];
            if ( $search !== '' ) {
                $args['search'] = $search;
            }

            $terms = get_terms( $args );
            if ( is_wp_error( $terms ) ) {
                return new WP_REST_Response( [
                    'version'   => LOKEY_INV_API_VERSION,
                    'status'    => 'error',
                    'code'      => 500,
                    'message'   => $terms->get_error_message(),
                    'timestamp' => current_time( 'mysql' ),
                ], 500 );
            }

            $rows = [];
            foreach ( $terms as $t ) {
                $rows[] = [
                    'id'     => (int) $t->term_id,
                    'name'   => $t->name,
                    'slug'   => $t->slug,
                    'parent' => (int) $t->parent,
                ];
            }

            return new WP_REST_Response( [
                'version'    => LOKEY_INV_API_VERSION,
                'status'     => 'success',
                'taxonomy'   => $taxonomy,
                'label'      => $supported[ $taxonomy ],
                'search'     => $search,
                'page'       => $page,
                'per_page'   => $per_page,
                'count'      => count( $rows ),
                'rows'       => $rows,
                'timestamp'  => current_time( 'mysql' ),
            ], 200 );
        },
        'permission_callback' => '__return_true',
        'args' => [
            'taxonomy' => [ 'type' => 'string' ],
            'search'   => [ 'type' => 'string' ],
            'page'     => [ 'type' => 'integer', 'default' => 1 ],
            'per_page' => [ 'type' => 'integer', 'default' => 50 ],
        ],
    ] );

} );
