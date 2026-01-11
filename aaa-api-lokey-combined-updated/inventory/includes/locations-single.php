<?php
/**
 * ATUM locations get/update/delete (taxonomy: atum_location).
 *
 * Routes:
 *   - GET    /lokey-inventory/v1/locations/{id}
 *   - PUT    /lokey-inventory/v1/locations/{id}
 *   - DELETE /lokey-inventory/v1/locations/{id}
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

    register_rest_route( LOKEY_INV_API_NS, '/locations/(?P<id>\d+)', [
        'methods'             => [ 'GET', 'PUT', 'DELETE' ],
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $req ) use ( $taxonomy, $err, $to_row ) {

            if ( ! taxonomy_exists( $taxonomy ) ) {
                return $err( 501, 'ATUM locations taxonomy not available (atum_location).' );
            }

            $id = absint( $req['id'] );
            if ( $id <= 0 ) {
                return $err( 400, 'Invalid location ID.' );
            }

            $t = get_term( $id, $taxonomy );
            if ( ! $t || is_wp_error( $t ) ) {
                return $err( 404, 'Location not found.', [ 'id' => $id ] );
            }

            $method = $req->get_method();

            if ( 'GET' === $method ) {
                return new WP_REST_Response( $to_row( $t ), 200 );
            }

            if ( 'PUT' === $method ) {
                $body = $req->get_json_params() ?: [];
                $args = [];

                if ( array_key_exists( 'name', $body ) ) {
                    $args['name'] = sanitize_text_field( wp_unslash( (string) $body['name'] ) );
                }
                if ( array_key_exists( 'slug', $body ) ) {
                    $args['slug'] = sanitize_title( wp_unslash( (string) $body['slug'] ) );
                }
                if ( array_key_exists( 'description', $body ) ) {
                    $args['description'] = wp_kses_post( wp_unslash( (string) $body['description'] ) );
                }
                if ( array_key_exists( 'parent', $body ) ) {
                    $parent = absint( $body['parent'] );
                    if ( $parent === $id ) {
                        return $err( 400, 'Parent cannot be the same as the term ID.' );
                    }
                    if ( $parent ) {
                        $p = get_term( $parent, $taxonomy );
                        if ( ! $p || is_wp_error( $p ) ) {
                            return $err( 400, 'Invalid parent term ID.', [ 'parent' => $parent ] );
                        }
                    }
                    $args['parent'] = $parent; // allow 0
                }

                if ( empty( $args ) ) {
                    return $err( 400, 'No updatable fields provided.' );
                }

                $up = wp_update_term( $id, $taxonomy, $args );
                if ( is_wp_error( $up ) ) {
                    return $err( 400, $up->get_error_message() );
                }

                $t2 = get_term( $id, $taxonomy );
                if ( ! $t2 || is_wp_error( $t2 ) ) {
                    return $err( 500, 'Location updated but could not be loaded.' );
                }

                return new WP_REST_Response( $to_row( $t2 ), 200 );
            }

            // DELETE
            $del = wp_delete_term( $id, $taxonomy );
            if ( is_wp_error( $del ) || ! $del ) {
                return $err( 500, is_wp_error( $del ) ? $del->get_error_message() : 'Location could not be deleted.' );
            }

            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => 'success',
                'id'        => $id,
                'message'   => 'Location deleted.',
                'timestamp' => current_time( 'mysql' ),
            ], 200 );
        },
        'args' => [
            'force' => [ 'type' => 'boolean' ],
        ],
    ] );

} );
