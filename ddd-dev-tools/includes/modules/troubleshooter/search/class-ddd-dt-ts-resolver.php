<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

class DDD_DT_TS_Resolver {
    public static function normalize_scope( $scope ): string {
        $scope = sanitize_key( (string) $scope );
        $allowed = [ 'plugin', 'plugins_active', 'plugins_inactive', 'plugins_all', 'mu_plugins', 'themes_active', 'wp_content' ];
        return in_array( $scope, $allowed, true ) ? $scope : 'plugin';
    }

    public static function resolve_roots( $scope, $plugin_file, $mu_rel ): array {
        $scope = self::normalize_scope( $scope );
        switch ( $scope ) {
            case 'plugins_active':
                return self::resolve_plugins_state( 'active' );
            case 'plugins_inactive':
                return self::resolve_plugins_state( 'inactive' );
            case 'plugins_all':
                return self::ok( [ WP_PLUGIN_DIR ] );
            case 'mu_plugins':
                return self::resolve_mu( $mu_rel );
            case 'themes_active':
                return self::resolve_themes();
            case 'wp_content':
                return self::ok( [ WP_CONTENT_DIR ] );
            case 'plugin':
            default:
                return self::resolve_plugin( $plugin_file );
        }
    }

    private static function ensure_plugins_api() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
    }

    private static function resolve_plugins_state( string $state ): array {
        self::ensure_plugins_api();
        $all = (array) get_plugins();
        $active = (array) get_option( 'active_plugins', [] );
        $sitewide = is_multisite() ? array_keys( (array) get_site_option( 'active_sitewide_plugins', [] ) ) : [];
        $active_map = array_fill_keys( array_merge( $active, $sitewide ), true );

        $roots = [];
        foreach ( $all as $file => $data ) {
            $is_active = isset( $active_map[ $file ] );
            $want = ( $state === 'active' ) ? $is_active : ! $is_active;
            if ( ! $want ) {
                continue;
            }

            $dir = dirname( (string) $file );
            $candidate = ( $dir === '.' ) ? ( WP_PLUGIN_DIR . '/' . $file ) : ( WP_PLUGIN_DIR . '/' . $dir );
            $abs = ddd_dt_ts_realpath( $candidate );
            if ( ! $abs || ( ! is_dir( $abs ) && ! is_file( $abs ) ) ) {
                continue;
            }
            if ( ! ddd_dt_ts_path_is_within( $abs, WP_PLUGIN_DIR ) ) {
                continue;
            }
            $roots[ $abs ] = true;
        }

        if ( empty( $roots ) ) {
            return self::err( $state === 'active' ? 'No active plugins found.' : 'No inactive plugins found.' );
        }
        return self::ok( array_keys( $roots ) );
    }

    private static function resolve_plugin( $plugin_file ): array {
        self::ensure_plugins_api();
        $plugin_file = (string) $plugin_file;
        $all = get_plugins();
        if ( $plugin_file === '' || ! isset( $all[ $plugin_file ] ) ) {
            return self::err( 'Please select a valid plugin.' );
        }

        $abs = ddd_dt_ts_realpath( WP_PLUGIN_DIR . '/' . $plugin_file );
        if ( $abs === '' || ! file_exists( $abs ) ) {
            return self::err( 'Plugin file not found on disk.' );
        }

        $dir = dirname( $plugin_file );
        if ( $dir === '.' ) {
            return self::ok( [ $abs ] );
        }

        $root = ddd_dt_ts_realpath( WP_PLUGIN_DIR . '/' . $dir );
        return ( $root && is_dir( $root ) ) ? self::ok( [ $root ] ) : self::ok( [ $abs ] );
    }

    private static function resolve_mu( $mu_rel ): array {
        $mu_dir = defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : WP_CONTENT_DIR . '/mu-plugins';
        $mu_dir = ddd_dt_ts_realpath( $mu_dir );
        if ( $mu_dir === '' || ! is_dir( $mu_dir ) ) {
            return self::err( 'mu-plugins directory not found.' );
        }

        $mu_rel = trim( (string) $mu_rel );
        if ( $mu_rel === '' ) {
            return self::ok( [ $mu_dir ] );
        }
        if ( strpos( $mu_rel, '..' ) !== false ) {
            return self::err( 'Invalid mu-plugin path.' );
        }

        $abs = ddd_dt_ts_realpath( WP_CONTENT_DIR . '/' . ltrim( $mu_rel, '/\\' ) );
        if ( $abs && ddd_dt_ts_path_is_within( $abs, $mu_dir ) ) {
            return self::ok( [ $abs ] );
        }
        return self::ok( [ $mu_dir ] );
    }

    private static function resolve_themes(): array {
        $roots = [];
        $style = ddd_dt_ts_realpath( get_stylesheet_directory() );
        $template = ddd_dt_ts_realpath( get_template_directory() );
        if ( $style ) {
            $roots[] = $style;
        }
        if ( $template && $template !== $style ) {
            $roots[] = $template;
        }
        return empty( $roots ) ? self::err( 'Theme directories not found.' ) : self::ok( $roots );
    }

    private static function ok( $roots ): array {
        return [ 'ok' => true, 'roots' => array_values( array_filter( (array) $roots ) ) ];
    }

    private static function err( $msg ): array {
        return [ 'ok' => false, 'error' => (string) $msg, 'roots' => [] ];
    }
}
