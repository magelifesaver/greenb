<?php
/**
 * ATUM locations tree (taxonomy: atum_location).
 *
 * Route:
 *   - GET /lokey-inventory/v1/locations/tree
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

    $node = function ( $t ) {
        return [
            'id'          => (int) $t->term_id,
            'name'        => (string) $t->name,
            'slug'        => (string) $t->slug,
            'parent'      => (int) $t->parent,
            'description' => (string) ( $t->description ?? '' ),
            'children'    => [],
        ];
    };

    register_rest_route( LOKEY_INV_API_NS, '/locations/tree', [
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function () use ( $taxonomy, $err, $node ) {

            if ( ! taxonomy_exists( $taxonomy ) ) {
                return $err( 501, 'ATUM locations taxonomy not available (atum_location).' );
            }

            $terms = get_terms( [
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
            ] );
            if ( is_wp_error( $terms ) ) {
                return $err( 500, $terms->get_error_message() );
            }

            $nodes = [];
            $kids  = [];
            foreach ( $terms as $t ) {
                $nodes[ $t->term_id ] = $node( $t );
                $kids[ (int) $t->parent ][] = (int) $t->term_id;
            }

            $build = function ( $parent, $seen = [] ) use ( &$build, $kids, $nodes ) {
                $out = [];
                foreach ( $kids[ (int) $parent ] ?? [] as $id ) {
                    if ( isset( $seen[ $id ] ) || ! isset( $nodes[ $id ] ) ) {
                        continue;
                    }
                    $seen2 = $seen;
                    $seen2[ $id ] = 1;
                    $n = $nodes[ $id ];
                    $n['children'] = $build( $id, $seen2 );
                    if ( empty( $n['children'] ) ) {
                        unset( $n['children'] );
                    }
                    $out[] = $n;
                }
                return $out;
            };

            return new WP_REST_Response( $build( 0 ), 200 );
        },
    ] );

} );
