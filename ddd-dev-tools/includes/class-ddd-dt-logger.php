<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

class DDD_DT_Logger {
    private const CRON_HOOK = 'ddd_dt_prune_logs';

    public static function init() {
        add_action( self::CRON_HOOK, [ __CLASS__, 'prune_logs' ] );
    }

    public static function write( string $module, string $message, array $context = [], string $level = 'info' ): bool {
        if ( ! self::is_debug_enabled( $module ) ) {
            return false;
        }

        $dir = self::dir();
        if ( ! $dir ) {
            return false;
        }

        $file = $dir . '/' . sanitize_key( $module ) . '-' . gmdate( 'Y-m-d' ) . '.log';
        self::rotate_if_needed( $file );

        $row = [
            'ts'      => gmdate( 'c' ),
            'level'   => sanitize_key( $level ),
            'message' => $message,
            'context' => $context,
        ];

        $line = wp_json_encode( $row ) . PHP_EOL;
        $ok = ( false !== @file_put_contents( $file, $line, FILE_APPEND | LOCK_EX ) );

        if ( $ok && ! empty( self::general_settings()['mirror_error_log'] ) ) {
            error_log( '[DDD_DT][' . $module . '] ' . $message . ' ' . wp_json_encode( $context ) );
        }

        return $ok;
    }

    public static function dir(): string {
        $u = wp_upload_dir();
        if ( empty( $u['basedir'] ) ) {
            return '';
        }
        $dir = trailingslashit( $u['basedir'] ) . 'ddd-dev-tools/logs';
        if ( ! is_dir( $dir ) ) {
            wp_mkdir_p( $dir );
        }
        return is_dir( $dir ) ? untrailingslashit( $dir ) : '';
    }

    public static function list_log_files(): array {
        $dir = self::dir();
        if ( ! $dir ) {
            return [];
        }
        $files = glob( $dir . '/*.log' ) ?: [];
        rsort( $files, SORT_STRING );
        return array_values( $files );
    }

    public static function tail( string $abs_file, int $lines = 200 ): array {
        $dir = self::dir();
        $abs = realpath( $abs_file );
        if ( ! $dir || ! $abs || strpos( str_replace( '\\', '/', $abs ), str_replace( '\\', '/', $dir ) ) !== 0 ) {
            return [];
        }
        $lines = max( 1, min( 1000, $lines ) );
        $data = @file( $abs, FILE_IGNORE_NEW_LINES );
        if ( ! is_array( $data ) ) {
            return [];
        }
        return array_slice( $data, -$lines );
    }

    public static function schedule_prune() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
        }
    }

    public static function unschedule_prune() {
        $ts = wp_next_scheduled( self::CRON_HOOK );
        if ( $ts ) {
            wp_unschedule_event( $ts, self::CRON_HOOK );
        }
    }

    public static function prune_logs() {
        $dir = self::dir();
        if ( ! $dir ) {
            return;
        }
        $days = max( 1, min( 365, absint( self::general_settings()['log_retention_days'] ?? 7 ) ) );
        $cut = time() - ( $days * DAY_IN_SECONDS );
        foreach ( glob( $dir . '/*' ) ?: [] as $f ) {
            if ( is_file( $f ) && filemtime( $f ) < $cut ) {
                @unlink( $f );
            }
        }
    }

    private static function is_debug_enabled( string $module ): bool {
        $g = self::general_settings();
        if ( empty( $g['debug_enabled'] ) ) {
            return false;
        }
        $key = self::module_option_key( $module );
        if ( ! $key ) {
            return true;
        }
        $s = DDD_DT_Options::get( $key, [], 'global' );
        return ! empty( $s['debug_enabled'] );
    }

    private static function general_settings(): array {
        $d = [ 'debug_enabled' => 0, 'mirror_error_log' => 0, 'log_max_mb' => 5, 'log_retention_days' => 7 ];
        $s = DDD_DT_Options::get( 'ddd_dt_general', [], 'global' );
        return is_array( $s ) ? array_merge( $d, $s ) : $d;
    }

    private static function module_option_key( string $module ): string {
        $m = sanitize_key( $module );
        if ( $m === 'url_cleaner' ) return 'ddd_url_cleaner_settings';
        if ( $m === 'pagination_redirect' ) return 'ddd_pagination_redirect_settings';
        if ( $m === 'page_click_manager' ) return 'ddd_page_click_manager_settings';
        if ( $m === 'troubleshooter' ) return 'ddd_troubleshooter_settings';
        if ( $m === 'atum_log_viewer' ) return 'ddd_atum_log_viewer_settings';
        if ( $m === 'debug_log_manager' ) return 'ddd_debug_log_manager_settings';
        if ( $m === 'product_debugger' ) return 'ddd_product_debugger_settings';
        if ( $m === 'order_debugger' ) return 'ddd_order_debugger_settings';
        return '';
    }

    private static function rotate_if_needed( string $file ) {
        $max = max( 1, absint( self::general_settings()['log_max_mb'] ?? 5 ) ) * 1024 * 1024;
        if ( is_file( $file ) && filesize( $file ) > $max ) {
            $bak = $file . '.' . gmdate( 'Ymd-His' ) . '.bak';
            @rename( $file, $bak );
        }
    }
}
