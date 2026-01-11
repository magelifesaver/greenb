<?php
/**
 * ATUM locations list + create (taxonomy: atum_location).
 *
 * Routes:
 *   - GET  /lokey-inventory/v1/locations
 *   - POST /lokey-inventory/v1/locations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {

    $taxonomy = 'atum_location';

    $err = function ( $code, $msg, $extra = [] ) {
        return new WP_REST_Response( array_merge( [
            'version'   => LOKEY_INV_API_VERSION,
            'status'    => 'error',
            'code'      => (int) $code,
            'message'   => (string) $msg,
            'timestamp' => current_time( 'mysql' ),
        ], $extra ), (int) $code );
    };

    $to_row = function ( $t ) {
        return [
            'id'          => (int) $t->term_id,
            'name'        => (string) $t->name,
            'slug'        => (string) $t->slug,
            'parent'      => (int) $t->parent,
            'description' => (string) ( $t->description ?? '' ),
        ];
    };

    register_rest_route( LOKEY_INV_API_NS, '/locations', [
        [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => function ( WP_REST_Request $req ) use ( $taxonomy, $err, $to_row ) {

                if ( ! taxonomy_exists( $taxonomy ) ) {
                    return $err( 501, 'ATUM locations taxonomy not available (atum_location).' );
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
                    return $err( 500, $terms->get_error_message() );
                }

                $rows = [];
                foreach ( $terms as $t ) {
                    $rows[] = $to_row( $t );
                }

                return new WP_REST_Response( [
                    'version'   => LOKEY_INV_API_VERSION,
                    'status'    => 'success',
                    'taxonomy'  => $taxonomy,
                    'search'    => $search,
                    'page'      => $page,
                    'per_page'  => $per_page,
                    'count'     => count( $rows ),
                    'rows'      => $rows,
                    'timestamp' => current_time( 'mysql' ),
                ], 200 );
            },
        ],
        [
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'callback'            => function ( WP_REST_Request $req ) use ( $taxonomy, $err, $to_row ) {

                if ( ! taxonomy_exists( $taxonomy ) ) {
                    return $err( 501, 'ATUM locations taxonomy not available (atum_location).' );
                }

                $body = $req->get_json_params() ?: [];
                $name = isset( $body['name'] ) ? sanitize_text_field( wp_unslash( $body['name'] ) ) : '';
                if ( $name === '' ) {
                    return $err( 400, 'Missing required field: name.' );
                }

                $slug   = isset( $body['slug'] ) ? sanitize_title( wp_unslash( $body['slug'] ) ) : '';
                $desc   = isset( $body['description'] ) ? wp_kses_post( wp_unslash( $body['description'] ) ) : '';
                $parent = isset( $body['parent'] ) ? absint( $body['parent'] ) : 0;

                if ( $parent ) {
                    $p = get_term( $parent, $taxonomy );
                    if ( ! $p || is_wp_error( $p ) ) {
                        return $err( 400, 'Invalid parent term ID.', [ 'parent' => $parent ] );
                    }
                }

                $args = [];
                if ( $slug ) {
                    $args['slug'] = $slug;
                }
                if ( $parent ) {
                    $args['parent'] = $parent;
                }
                if ( $desc !== '' ) {
                    $args['description'] = $desc;
                }

                $ins = wp_insert_term( $name, $taxonomy, $args );
                if ( is_wp_error( $ins ) ) {
                    return $err( 400, $ins->get_error_message() );
                }

                $term_id = absint( is_array( $ins ) ? ( $ins['term_id'] ?? 0 ) : $ins );
                $t       = $term_id ? get_term( $term_id, $taxonomy ) : null;
                if ( ! $t || is_wp_error( $t ) ) {
                    return $err( 500, 'Location created but could not be loaded.' );
                }

                return new WP_REST_Response( $to_row( $t ), 200 );
            },
        ],
    ] );

} );
