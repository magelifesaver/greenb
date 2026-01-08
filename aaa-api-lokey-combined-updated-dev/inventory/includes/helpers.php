<?php
/**
 * Helper functions for the Lokey Inventory API.
 *
 * These helpers centralise how the plugin communicates with the ATUM and
 * WooCommerce REST APIs and how numeric query parameters are sanitised.
 * Consumer keys and secrets are defined in the main plugin loader.  All
 * downstream endpoint files call these helpers to ensure consistent
 * authentication and request handling.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Import JWT classes when available.  The JWT Pro plugin bundles these.
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Performs a request to the ATUM/WooCommerce REST API.
 *
 * Requests automatically append the consumer key and secret defined in
 * LOKEY_ATUM_CK and LOKEY_ATUM_CS.  An optional body array will be JSON‑
 * encoded for POST/PUT requests.
 *
 * @param string     $path   Relative path to the ATUM or WooCommerce resource
 *                           (e.g. "atum/purchase-orders" or "products/123").
 * @param string     $method HTTP method (GET, POST, PUT, DELETE).
 * @param array|null $body   Optional associative array to send as JSON.
 * @return array    Associative array with keys 'code' and 'body'.
 */
function lokey_inv_request( $path, $method = 'GET', $body = null ) {
    // Build the base URL for the WooCommerce/ATUM API request.  We always
    // attach the consumer key and secret for authentication.  home_url()
    // ensures the correct site base path.
    $url = add_query_arg(
        [
            'consumer_key'    => LOKEY_ATUM_CK,
            'consumer_secret' => LOKEY_ATUM_CS,
        ],
        home_url( "/wp-json/wc/v3/{$path}" )
    );

    $args = [
        'method'  => $method,
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ],
        'timeout' => 20,
    ];

    // Forward the Authorization header if present in the current request.  This
    // allows proxied requests to other Lokey endpoints to reuse the JWT token.
    $auth_header = '';
    if ( ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif ( ! empty( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
        $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } else {
        $h = function_exists( 'getallheaders' ) ? getallheaders() : [];
        if ( isset( $h['Authorization'] ) ) {
            $auth_header = $h['Authorization'];
        }
    }
    if ( $auth_header ) {
        $args['headers']['Authorization'] = $auth_header;
    }
    if ( $body ) {
        $args['body'] = wp_json_encode( $body );
    }

    $response = wp_remote_request( $url, $args );
    return [
        'code' => wp_remote_retrieve_response_code( $response ),
        'body' => json_decode( wp_remote_retrieve_body( $response ), true ),
    ];
}

/**
 * Sanitises an integer query parameter, applying default and maximum values.
 *
 * When building REST queries we often need to bound user‑supplied integers
 * (e.g. per_page, page).  This helper ensures values are positive and do
 * not exceed the supplied maximum.  If an invalid value is provided, the
 * function returns the default instead.
 *
 * @param mixed $value   Raw input value from request.
 * @param int   $default Default to use when input is empty or invalid.
 * @param int   $max     Maximum allowed value.  Defaults to 100.
 * @return int  Sanitised integer between 1 and $max.
 */
function lokey_inv_sanitize_int( $value, $default = 20, $max = 100 ) {
    $v = absint( $value );
    if ( $v <= 0 ) {
        return $default;
    }
    return min( $v, $max );
}

/*
 * Enforces JWT authentication.  Accepts a token from the Authorization header
 * (Bearer prefix) or the `token` query param.  If a user is already logged
 * in, no token is needed.  Returns true on success or WP_Error on failure.
 */
if ( ! function_exists( 'lokey_require_jwt_auth' ) ) {
    function lokey_require_jwt_auth() {
        if ( is_user_logged_in() ) {
            return true;
        }
        // Gather headers: HTTP_AUTHORIZATION, REDIRECT_HTTP_AUTHORIZATION or getallheaders().
        $headers = function_exists( 'getallheaders' ) ? getallheaders() : [];
        $auth    = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ( $headers['Authorization'] ?? '' );
        $token_q = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
        if ( ! $auth && ! $token_q ) {
            return new WP_Error( 'jwt_required', 'Authorization header or token parameter missing.', [ 'status' => 401 ] );
        }
        $token      = $token_q ?: trim( preg_replace( '/^Bearer\s+/i', '', $auth ) );
        $secret_key = defined( 'JWT_AUTH_SECRET_KEY' ) ? JWT_AUTH_SECRET_KEY : '';
        if ( ! $secret_key ) {
            return new WP_Error( 'jwt_secret_missing', 'JWT secret key not defined.', [ 'status' => 401 ] );
        }
        try {
            $decoded = JWT::decode( $token, new Key( $secret_key, 'HS256' ) );
            if ( empty( $decoded->data->user->id ) ) {
                return new WP_Error( 'jwt_invalid', 'Token payload invalid.', [ 'status' => 401 ] );
            }
            if ( isset( $decoded->exp ) && time() >= $decoded->exp ) {
                return new WP_Error( 'jwt_expired', 'JWT token expired.', [ 'status' => 401 ] );
            }
            wp_set_current_user( (int) $decoded->data->user->id );
            do_action( 'lokey_jwt_auth_success', $decoded->data->user->id );
            return true;
        } catch ( Exception $e ) {
            return new WP_Error( 'jwt_invalid', 'JWT validation failed: ' . $e->getMessage(), [ 'status' => 401 ] );
        }
    }
}
