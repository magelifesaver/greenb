<?php
// @version 2.0.0
defined( 'ABSPATH' ) || exit;

class DDD_DT_Pagination_Redirect {
    public static function init() {
        add_action( 'template_redirect', [ __CLASS__, 'maybe_redirect' ], 1 );
    }

    public static function settings(): array {
        $d = [
            'enabled' => 0, 'only_on_404' => 1, 'mode' => 'redirect',
            'redirect_code' => 301, 'preserve_query' => 1, 'debug_enabled' => 0,
        ];
        $s = DDD_DT_Options::get( 'ddd_pagination_redirect_settings', [], 'global' );
        return is_array( $s ) ? array_merge( $d, $s ) : $d;
    }

    public static function maybe_redirect() {
        $s = self::settings();
        if ( empty( $s['enabled'] ) ) return;

        if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) return;
        $m = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ( ! in_array( $m, [ 'GET', 'HEAD' ], true ) ) return;
        if ( ! empty( $s['only_on_404'] ) && ! is_404() ) return;

        $path = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
        if ( ! is_string( $path ) || $path === '' ) return;

        if ( ! preg_match( '~^/(.+?)/page/([0-9]+)/?$~', $path, $mm ) ) return;

        $base = user_trailingslashit( '/' . ltrim( $mm[1], '/' ) );
        if ( $base === $path ) return;

        $target = home_url( $base );
        if ( ! empty( $s['preserve_query'] ) ) {
            $q = $_SERVER['QUERY_STRING'] ?? '';
            if ( is_string( $q ) && $q !== '' ) {
                $target .= '?' . $q;
            }
        }

        DDD_DT_Logger::write( 'pagination_redirect', 'Pagination redirect matched', [
            'from' => (string) ( $_SERVER['REQUEST_URI'] ?? '' ),
            'to' => $target,
            'mode' => (string) $s['mode'],
        ] );

        if ( ( $s['mode'] ?? 'redirect' ) === 'log_only' ) return;

        $code = in_array( (int) ( $s['redirect_code'] ?? 301 ), [ 301, 302 ], true ) ? (int) $s['redirect_code'] : 301;
        wp_safe_redirect( $target, $code );
        exit;
    }
}
