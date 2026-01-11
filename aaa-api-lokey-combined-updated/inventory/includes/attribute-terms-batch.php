<?php
/**
 * WooCommerce attribute terms batch (proxy).
 *
 * Route:
 *   - POST /lokey-inventory/v1/attributes/{id}/terms/batch
 *
 * Proxies to Woo endpoint:
 *   - POST /wc/v3/products/attributes/{id}/terms/batch
 *
 * Request body supports either:
 *   - Woo batch shape: { create: [...], update: [...], delete: [...] }
 *   - Convenience alias: { terms: [ ... ] } (treated as create list)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {

    register_rest_route( LOKEY_INV_API_NS, '/attributes/(?P<id>\d+)/terms/batch', [
        'methods'  => 'POST',
        'callback' => function ( WP_REST_Request $req ) {

            $id = absint( $req['id'] );
            if ( $id <= 0 ) {
                return new WP_REST_Response( [
                    'status'  => 'error',
                    'code'    => 400,
                    'message' => 'Invalid attribute ID.',
                ], 400 );
            }

            $body = $req->get_json_params() ?: [];
            if ( ! is_array( $body ) ) {
                $body = [];
            }

            // Convenience alias: { terms: [...] } => { create: [...] }
            if ( isset( $body['terms'] ) && is_array( $body['terms'] ) && ! isset( $body['create'], $body['update'], $body['delete'] ) ) {
                $body = [ 'create' => $body['terms'] ];
            }

            // Require at least one batch directive.
            $has_directives = false;
            foreach ( [ 'create', 'update', 'delete' ] as $k ) {
                if ( array_key_exists( $k, $body ) ) {
                    $has_directives = true;
                    break;
                }
            }
            if ( ! $has_directives ) {
                return new WP_REST_Response( [
                    'version'   => LOKEY_INV_API_VERSION,
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Missing batch directives. Provide {terms:[...]} or {create/update/delete}.',
                    'timestamp' => current_time( 'mysql' ),
                ], 400 );
            }

            // Soft guardrail: keep batches small (Woo often defaults to 100 max).
            $max = 100;
            if ( isset( $body['create'] ) && is_array( $body['create'] ) && count( $body['create'] ) > $max ) {
                return new WP_REST_Response( [
                    'version'   => LOKEY_INV_API_VERSION,
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Too many terms in create batch (max ' . $max . ').',
                    'timestamp' => current_time( 'mysql' ),
                ], 400 );
            }

            $res  = lokey_inv_request( "products/attributes/{$id}/terms/batch", 'POST', $body );
            $code = $res['code'] ?? 500;

            if ( $code >= 400 ) {
                return new WP_REST_Response( [
                    'version'   => LOKEY_INV_API_VERSION,
                    'status'    => 'error',
                    'code'      => $code,
                    'message'   => 'Attribute terms batch request failed.',
                    'data'      => $res['body'] ?? null,
                    'timestamp' => current_time( 'mysql' ),
                ], $code );
            }

            return new WP_REST_Response( [
                'version'      => LOKEY_INV_API_VERSION,
                'status'       => 'success',
                'action'       => 'batch',
                'attribute_id' => $id,
                'data'         => $res['body'] ?? null,
                'timestamp'    => current_time( 'mysql' ),
            ], 200 );
        },
        'permission_callback' => '__return_true',
    ] );

} );
