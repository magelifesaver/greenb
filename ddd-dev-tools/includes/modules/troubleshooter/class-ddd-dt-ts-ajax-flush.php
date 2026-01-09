<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

class DDD_DT_TS_Ajax_Flush {
    public static function handle() {
        check_ajax_referer( 'ddd_dt_ts_flush', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }
        if ( empty( DDD_DT_Troubleshooter::settings()['enabled'] ) ) {
            wp_send_json_error( 'Troubleshooter module is disabled.' );
        }

        $what = isset( $_POST['what'] ) ? sanitize_key( wp_unslash( $_POST['what'] ) ) : 'both';
        $done = [];

        switch ( $what ) {
            case 'cache':
                if ( function_exists( 'wp_cache_flush' ) ) {
                    wp_cache_flush();
                    $done[] = 'object_cache_flushed';
                }
                break;
            case 'rewrite':
                flush_rewrite_rules();
                $done[] = 'rewrite_rules_flushed';
                break;
            case 'both':
            default:
                if ( function_exists( 'wp_cache_flush' ) ) {
                    wp_cache_flush();
                    $done[] = 'object_cache_flushed';
                }
                flush_rewrite_rules();
                $done[] = 'rewrite_rules_flushed';
                break;
        }

        DDD_DT_Logger::write( 'troubleshooter', 'flush', [ 'what' => $what, 'done' => $done ] );
        wp_send_json_success( [ 'what' => $what, 'done' => $done ] );
    }
}
