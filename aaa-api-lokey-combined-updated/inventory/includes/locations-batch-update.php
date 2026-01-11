<?php
/**
 * ATUM Product Locations batch update (taxonomy: atum_location).
 *
 * Route:
 *   - PUT /lokey-inventory/v1/locations/batch
 *
 * Body:
 *   - { "update":  [ { id, name?, slug?, parent?, description? } ] }
 *   - { "updates": [ ... ] } (alias)
 *   - [ { id, ... }, ... ] (raw list)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {

    register_rest_route( LOKEY_INV_API_NS, '/locations/batch', [
        'methods'             => 'PUT',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $req ) {

            $err = function ( $code, $msg, $extra = [] ) {
                return new WP_REST_Response( array_merge( [
                    'version'   => LOKEY_INV_API_VERSION,
                    'status'    => 'error',
                    'code'      => (int) $code,
                    'message'   => (string) $msg,
                    'timestamp' => current_time( 'mysql' ),
                ], $extra ), (int) $code );
            };

            $taxonomy = 'atum_location';
            if ( ! taxonomy_exists( $taxonomy ) ) {
                return $err( 501, 'ATUM locations taxonomy not available (atum_location).' );
            }

            $body = $req->get_json_params();
            $list = [];

            if ( is_array( $body ) && isset( $body['update'] ) && is_array( $body['update'] ) ) {
                $list = $body['update'];
            } elseif ( is_array( $body ) && isset( $body['updates'] ) && is_array( $body['updates'] ) ) {
                $list = $body['updates'];
            } elseif ( is_array( $body ) && array_keys( $body ) === range( 0, count( $body ) - 1 ) ) {
                $list = $body;
            }

            if ( empty( $list ) ) {
                return $err( 400, 'Body must include update[] (or updates[]) array.' );
            }
            if ( count( $list ) > 100 ) {
                return $err( 413, 'Too many locations in one request (max 100).' );
            }

            $updated = [];
            $errors  = [];

            foreach ( $list as $i => $row ) {
                if ( ! is_array( $row ) ) {
                    $errors[] = [ 'index' => $i, 'message' => 'Item must be an object.' ];
                    continue;
                }

                $id = isset( $row['id'] ) ? absint( $row['id'] ) : 0;
                if ( $id <= 0 ) {
                    $errors[] = [ 'index' => $i, 'message' => 'Missing required field: id.' ];
                    continue;
                }

                $t = get_term( $id, $taxonomy );
                if ( ! $t || is_wp_error( $t ) ) {
                    $errors[] = [ 'index' => $i, 'id' => $id, 'message' => 'Location not found.' ];
                    continue;
                }

                $args = [];
                if ( array_key_exists( 'name', $row ) ) {
                    $args['name'] = sanitize_text_field( wp_unslash( (string) $row['name'] ) );
                }
                if ( array_key_exists( 'slug', $row ) ) {
                    $args['slug'] = sanitize_title( wp_unslash( (string) $row['slug'] ) );
                }
                if ( array_key_exists( 'description', $row ) ) {
                    $args['description'] = wp_kses_post( wp_unslash( (string) $row['description'] ) );
                }
                if ( array_key_exists( 'parent', $row ) ) {
                    $parent = absint( $row['parent'] );
                    if ( $parent === $id ) {
                        $errors[] = [ 'index' => $i, 'id' => $id, 'message' => 'Parent cannot be the same as the term ID.' ];
                        continue;
                    }
                    if ( $parent ) {
                        $p = get_term( $parent, $taxonomy );
                        if ( ! $p || is_wp_error( $p ) ) {
                            $errors[] = [ 'index' => $i, 'id' => $id, 'message' => 'Invalid parent term ID.', 'parent' => $parent ];
                            continue;
                        }
                    }
                    $args['parent'] = $parent; // allow 0
                }

                if ( empty( $args ) ) {
                    $errors[] = [ 'index' => $i, 'id' => $id, 'message' => 'No updatable fields provided.' ];
                    continue;
                }

                $up = wp_update_term( $id, $taxonomy, $args );
                if ( is_wp_error( $up ) ) {
                    $errors[] = [ 'index' => $i, 'id' => $id, 'message' => $up->get_error_message() ];
                    continue;
                }

                $t2 = get_term( $id, $taxonomy );
                $updated[] = [
                    'action' => 'updated',
                    'id'     => (int) $id,
                    'name'   => (string) ( $t2->name ?? $t->name ),
                    'slug'   => (string) ( $t2->slug ?? $t->slug ),
                    'parent' => (int) ( $t2->parent ?? $t->parent ),
                ];
            }

            $status = empty( $errors ) ? 'success' : ( empty( $updated ) ? 'error' : 'partial' );

            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => $status,
                'code'      => 200,
                'taxonomy'  => $taxonomy,
                'count'     => count( $updated ),
                'updated'   => $updated,
                'errors'    => $errors,
                'timestamp' => current_time( 'mysql' ),
            ], 200 );
        },
    ] );

} );
