<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

class DDD_DT_Migrator {
    public static function activate() {
        self::ensure_defaults();

        // Migrate legacy WP options (from older standalone plugins) into aaa_oc_options.
        self::migrate_wp_option( 'ddd_url_cleaner_settings' );
        self::migrate_wp_option( 'ddd_pagination_redirect_settings' );

        // Migrate legacy Page Click Manager constants (best-effort).
        self::migrate_pcm_from_legacy_file();
    }

    private static function ensure_defaults() {
        self::ensure_option( 'ddd_dt_general', [
            'debug_enabled'      => 0,
            'mirror_error_log'   => 0,
            'log_max_mb'         => 5,
            'log_retention_days' => 7,
        ] );

        self::ensure_option( 'ddd_url_cleaner_settings', [
            'enabled'         => 0,
            'only_on_404'     => 0,
            'mode'            => 'redirect', // redirect|log_only
            'redirect_code'   => 301,
            'strip_all'       => 0,
            'strip_exact'     => [ 'add-to-cart', 'quantity', 'variation_id', 'min_price', 'max_price', 'orderby', 'per_page', 'product-page' ],
            'strip_prefixes'  => [ 'filter_', 'query_type_', 'attribute_' ],
            'preserve_exact'  => [ 'gclid', 'fbclid' ],
            'preserve_prefix' => [ 'utm_' ],
            'debug_enabled'   => 0,
        ] );

        self::ensure_option( 'ddd_pagination_redirect_settings', [
            'enabled'        => 0,
            'only_on_404'    => 1,
            'mode'           => 'redirect', // redirect|log_only
            'redirect_code'  => 301,
            'preserve_query' => 1,
            'debug_enabled'  => 0,
        ] );

        self::ensure_option( 'ddd_page_click_manager_settings', [
            'enabled'                 => 0,
            'token'                   => wp_generate_password( 18, false, false ),
            'email_enabled'           => 0,
            'email_to'                => '',
            'email_subject'           => 'Wrong-site navigation click detected',
            'email_cooldown_seconds'  => 3600,
            'anonymize_ip'            => 0,
            'debug_enabled'           => 0,
        ] );

        self::ensure_option( 'ddd_troubleshooter_settings', [
            'enabled'       => 0,
            'debug_enabled' => 0,
        ] );

        self::ensure_option( 'ddd_atum_log_viewer_settings', [
            'enabled'            => 0,
            'use_datatables_cdn' => 0,
            'max_rows'           => 500,
            'debug_enabled'      => 0,
        ] );

        self::ensure_option( 'ddd_debug_log_manager_settings', [
            'enabled'           => 0,
            'download_chunk_mb' => 2,
            'debug_enabled'     => 0,
        ] );

        self::ensure_option( 'ddd_product_debugger_settings', [
            'enabled'       => 0,
            'debug_enabled' => 0,
        ] );

        self::ensure_option( 'ddd_order_debugger_settings', [
            'enabled'       => 0,
            'debug_enabled' => 0,
        ] );
    }

    private static function ensure_option( string $key, array $defaults ) {
        $existing = DDD_DT_Options::get( $key, null, 'global' );
        if ( $existing === null ) {
            DDD_DT_Options::set( $key, $defaults, 'global', 0 );
        }
    }

    private static function migrate_wp_option( string $key ) {
        $legacy = get_option( $key, null );
        if ( ! is_array( $legacy ) ) {
            return;
        }
        $current = DDD_DT_Options::get( $key, null, 'global' );
        if ( $current === null ) {
            DDD_DT_Options::set( $key, $legacy, 'global', 0 );
        }
    }

    private static function migrate_pcm_from_legacy_file() {
        $legacy_file = WP_PLUGIN_DIR . '/ddd-page-click-manager/ddd-page-click-manager.php';
        if ( ! is_readable( $legacy_file ) ) {
            return;
        }

        $src = (string) file_get_contents( $legacy_file );
        if ( $src === '' ) {
            return;
        }

        $token = self::parse_define_string( $src, 'DDD_PCM_TOKEN' );
        $email_enabled = self::parse_define_bool( $src, 'DDD_PCM_EMAIL_ENABLED' );
        $cooldown = self::parse_define_int( $src, 'DDD_PCM_EMAIL_COOLDOWN_SECONDS' );
        $subject = self::parse_define_string( $src, 'DDD_PCM_EMAIL_SUBJECT' );

        $s = DDD_DT_Options::get( 'ddd_page_click_manager_settings', [], 'global' );
        if ( ! is_array( $s ) ) {
            $s = [];
        }
        if ( $token ) {
            $s['token'] = $token;
        }
        if ( $email_enabled !== null ) {
            $s['email_enabled'] = $email_enabled ? 1 : 0;
        }
        if ( $cooldown !== null ) {
            $s['email_cooldown_seconds'] = max( 60, (int) $cooldown );
        }
        if ( $subject ) {
            $s['email_subject'] = $subject;
        }

        DDD_DT_Options::set( 'ddd_page_click_manager_settings', $s, 'global', 0 );
    }

    private static function parse_define_string( string $src, string $name ): string {
        if ( preg_match( "/define\(\s*'{$name}'\s*,\s*'([^']+)'\s*\)/", $src, $m ) ) {
            return sanitize_text_field( $m[1] );
        }
        return '';
    }

    private static function parse_define_int( string $src, string $name ) {
        if ( preg_match( "/define\(\s*'{$name}'\s*,\s*([0-9]+)\s*\)/", $src, $m ) ) {
            return (int) $m[1];
        }
        return null;
    }

    private static function parse_define_bool( string $src, string $name ) {
        if ( preg_match( "/define\(\s*'{$name}'\s*,\s*(true|false)\s*\)/i", $src, $m ) ) {
            return strtolower( $m[1] ) === 'true';
        }
        return null;
    }
}
