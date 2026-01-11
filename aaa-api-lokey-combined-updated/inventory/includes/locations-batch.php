<?php
/**
 * ATUM Product Locations batch create (taxonomy: atum_location).
 *
 * Route:
 *   - POST /lokey-inventory/v1/locations/batch
 *
 * Body:
 *   - { "locations": [ { name, slug?, parent?, description? } ] }
 *   - { "create":    [ ... ] } (alias)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {

    register_rest_route( LOKEY_INV_API_NS, '/locations/batch', [
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $req ) {

            $err = function ( $code, $msg, $extra = [] ) {
                return new WP_REST_Response( array_merge( [
                    'version'   => LOKEY_INV_API_VERSION,
                    'status'    => 'error',
                    'code'      => $code,
                    'message'   => $msg,
                    'timestamp' => current_time( 'mysql' ),
                ], $extra ), $code );
            };

            $taxonomy = 'atum_location';
            if ( ! taxonomy_exists( $taxonomy ) ) {
                return $err( 501, 'ATUM locations taxonomy not available (atum_location).' );
            }

            $body = $req->get_json_params();
            $list = [];

            if ( is_array( $body ) && isset( $body['locations'] ) && is_array( $body['locations'] ) ) {
                $list = $body['locations'];
            } elseif ( is_array( $body ) && isset( $body['create'] ) && is_array( $body['create'] ) ) {
                $list = $body['create'];
            } elseif ( is_array( $body ) && array_keys( $body ) === range( 0, count( $body ) - 1 ) ) {
                $list = $body;
            }

            if ( empty( $list ) ) {
                return $err( 400, 'Body must include locations[] (or create[]) array.' );
            }
            if ( count( $list ) > 100 ) {
                return $err( 413, 'Too many locations in one request (max 100).' );
            }

            $created = [];
            $errors  = [];

            foreach ( $list as $i => $row ) {
                if ( ! is_array( $row ) ) {
                    $errors[] = [ 'index' => $i, 'message' => 'Item must be an object.' ];
                    continue;
                }

                $name = isset( $row['name'] ) ? sanitize_text_field( wp_unslash( $row['name'] ) ) : '';
                if ( '' === $name ) {
                    $errors[] = [ 'index' => $i, 'message' => 'Missing required field: name.' ];
                    continue;
                }

                $slug   = isset( $row['slug'] ) ? sanitize_title( wp_unslash( $row['slug'] ) ) : '';
                $parent = isset( $row['parent'] ) ? absint( $row['parent'] ) : 0;
                $desc   = isset( $row['description'] ) ? wp_kses_post( wp_unslash( $row['description'] ) ) : '';

                if ( $parent ) {
                    $p = get_term( $parent, $taxonomy );
                    if ( ! $p || is_wp_error( $p ) ) {
                        $errors[] = [ 'index' => $i, 'message' => 'Invalid parent term ID.', 'parent' => $parent ];
                        continue;
                    }
                }

                $ex = term_exists( $slug ?: $name, $taxonomy );
                $existing_id = is_array( $ex ) ? absint( $ex['term_id'] ?? 0 ) : absint( $ex );

                if ( $existing_id ) {
                    $t = get_term( $existing_id, $taxonomy );
                    $created[] = [
                        'action' => 'exists',
                        'id'     => (int) $t->term_id,
                        'name'   => (string) $t->name,
                        'slug'   => (string) $t->slug,
                        'parent' => (int) $t->parent,
                    ];
                    continue;
                }

                $args = [];
                if ( $slug ) {
                    $args['slug'] = $slug;
                }
                if ( $parent ) {
                    $args['parent'] = $parent;
                }
                if ( '' !== $desc ) {
                    $args['description'] = $desc;
                }

                $ins = wp_insert_term( $name, $taxonomy, $args );
                if ( is_wp_error( $ins ) ) {
                    $errors[] = [ 'index' => $i, 'message' => $ins->get_error_message() ];
                    continue;
                }

                $term_id = absint( is_array( $ins ) ? ( $ins['term_id'] ?? 0 ) : $ins );
                $t       = $term_id ? get_term( $term_id, $taxonomy ) : null;

                if ( ! $t || is_wp_error( $t ) ) {
                    $errors[] = [ 'index' => $i, 'message' => 'Location created but could not be loaded.' ];
                    continue;
                }

                $created[] = [
                    'action' => 'created',
                    'id'     => (int) $t->term_id,
                    'name'   => (string) $t->name,
                    'slug'   => (string) $t->slug,
                    'parent' => (int) $t->parent,
                ];
            }

            $status = empty( $errors ) ? 'success' : ( empty( $created ) ? 'error' : 'partial' );

            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => $status,
                'code'      => 200,
                'taxonomy'  => $taxonomy,
                'count'     => count( $created ),
                'created'   => $created,
                'errors'    => $errors,
                'timestamp' => current_time( 'mysql' ),
            ], 200 );
        },
    ] );

} );
