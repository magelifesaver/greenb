<?php
/**
 * Brands list endpoint (berocket_brand taxonomy).
 *
 * Route:
 *   - GET /lokey-inventory/v1/brands
 *
 * Mirrors the behavior of terms.php but restricted to the berocket_brand taxonomy.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {
    register_rest_route( LOKEY_INV_API_NS, '/brands', [
        'methods'  => 'GET',
        'callback' => function ( WP_REST_Request $req ) {

            $taxonomy = 'berocket_brand';
            if ( ! taxonomy_exists( $taxonomy ) ) {
                return new WP_REST_Response( [
                    'version'   => LOKEY_INV_API_VERSION,
                    'status'    => 'error',
                    'code'      => 501,
                    'message'   => 'BeRocket brand taxonomy not registered (berocket_brand).',
                    'timestamp' => current_time( 'mysql' ),
                ], 501 );
            }

            $search   = $req->get_param( 'search' );
            $search   = is_string( $search ) ? sanitize_text_field( $search ) : '';
            $page     = max( 1, absint( $req->get_param( 'page' ) ?: 1 ) );
            $per_page = absint( $req->get_param( 'per_page' ) ?: 50 );
            $per_page = min( max( 1, $per_page ), 100 );

            $args = [
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'number'     => $per_page,
                'offset'     => ( $page - 1 ) * $per_page,
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
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => 'success',
                'taxonomy'  => $taxonomy,
                'count'     => count( $rows ),
                'rows'      => $rows,
                'timestamp' => current_time( 'mysql' ),
            ], 200 );
        },
        'permission_callback' => '__return_true',
        'args' => [
            'search'   => [ 'type' => 'string' ],
            'page'     => [ 'type' => 'integer', 'default' => 1 ],
            'per_page' => [ 'type' => 'integer', 'default' => 50 ],
        ],
    ] );
} );
