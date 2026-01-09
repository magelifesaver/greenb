<?php
// @version 2.0.0
defined( 'ABSPATH' ) || exit;

class DDD_DT_URL_Cleaner {
    public static function init() {
        add_action( 'template_redirect', [ __CLASS__, 'maybe_redirect' ], 1 );
    }

    public static function settings(): array {
        $d = [
            'enabled' => 0, 'only_on_404' => 0, 'mode' => 'redirect', 'redirect_code' => 301,
            'strip_all' => 0,
            'strip_exact' => [ 'add-to-cart', 'quantity', 'variation_id', 'min_price', 'max_price', 'orderby', 'per_page', 'product-page' ],
            'strip_prefixes' => [ 'filter_', 'query_type_', 'attribute_' ],
            'preserve_exact' => [ 'gclid', 'fbclid' ],
            'preserve_prefix' => [ 'utm_' ],
            'debug_enabled' => 0,
        ];
        $s = DDD_DT_Options::get( 'ddd_url_cleaner_settings', [], 'global' );
        return is_array( $s ) ? array_merge( $d, $s ) : $d;
    }

    public static function maybe_redirect() {
        $s = self::settings();
        if ( empty( $s['enabled'] ) ) return;

        if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) return;
        $m = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ( ! in_array( $m, [ 'GET', 'HEAD' ], true ) ) return;
        if ( ! empty( $s['only_on_404'] ) && ! is_404() ) return;
        if ( empty( $_GET ) ) return;

        $path = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
        if ( ! is_string( $path ) || $path === '' ) return;

        $kept = []; $removed = [];
        foreach ( (array) $_GET as $k => $v ) {
            $k = sanitize_key( (string) $k );
            if ( $k === '' ) continue;

            $strip = ! empty( $s['strip_all'] ) ? ! self::is_preserved( $k, $s ) : self::should_strip( $k, $s );
            if ( $strip ) { $removed[] = $k; continue; }
            $kept[ $k ] = is_scalar( $v ) ? sanitize_text_field( (string) $v ) : $v;
        }
        if ( empty( $removed ) ) return;

        $clean_path = user_trailingslashit( $path );
        $target = home_url( $clean_path );
        if ( ! empty( $kept ) ) {
            $target .= '?' . http_build_query( $kept, '', '&', PHP_QUERY_RFC3986 );
        }

        DDD_DT_Logger::write( 'url_cleaner', 'URL cleaned', [
            'from' => (string) ( $_SERVER['REQUEST_URI'] ?? '' ),
            'to' => $target,
            'removed' => $removed,
            'mode' => (string) $s['mode'],
        ] );

        if ( ( $s['mode'] ?? 'redirect' ) === 'log_only' ) return;

        $code = in_array( (int) ( $s['redirect_code'] ?? 301 ), [ 301, 302 ], true ) ? (int) $s['redirect_code'] : 301;
        wp_safe_redirect( $target, $code );
        exit;
    }

    private static function should_strip( string $key, array $s ): bool {
        foreach ( (array) ( $s['strip_exact'] ?? [] ) as $x ) {
            if ( $key === sanitize_key( (string) $x ) ) return true;
        }
        foreach ( (array) ( $s['strip_prefixes'] ?? [] ) as $p ) {
            $p = sanitize_key( (string) $p );
            if ( $p !== '' && strpos( $key, $p ) === 0 ) return true;
        }
        return false;
    }

    private static function is_preserved( string $key, array $s ): bool {
        foreach ( (array) ( $s['preserve_exact'] ?? [] ) as $x ) {
            if ( $key === sanitize_key( (string) $x ) ) return true;
        }
        foreach ( (array) ( $s['preserve_prefix'] ?? [] ) as $p ) {
            $p = sanitize_key( (string) $p );
            if ( $p !== '' && strpos( $key, $p ) === 0 ) return true;
        }
        return false;
    }
}
