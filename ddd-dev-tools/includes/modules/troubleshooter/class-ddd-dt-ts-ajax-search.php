<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

require_once DDD_DT_DIR . 'includes/modules/troubleshooter/class-ddd-dt-ts-search.php';

class DDD_DT_TS_Ajax_Search {
    public static function available_engines(): array {
        return DDD_DT_TS_Search::available_engines();
    }

    public static function ui_defaults(): array {
        return DDD_DT_TS_Search::ui_defaults();
    }

    public static function handle() {
        check_ajax_referer( 'ddd_dt_ts_search', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }
        if ( empty( DDD_DT_Troubleshooter::settings()['enabled'] ) ) {
            wp_send_json_error( 'Troubleshooter module is disabled.' );
        }

        $scope = isset( $_POST['scope'] ) ? sanitize_key( wp_unslash( $_POST['scope'] ) ) : 'plugin';
        $plugin = isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : '';
        $mu_plugin = isset( $_POST['mu_plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['mu_plugin'] ) ) : '';
        $mode = ( isset( $_POST['mode'] ) && wp_unslash( $_POST['mode'] ) === 'filename' ) ? 'filename' : 'content';
        $term = isset( $_POST['term'] ) ? (string) sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
        $term = substr( $term, 0, 500 );

        $defaults = self::ui_defaults();
        $engine = isset( $_POST['engine'] ) ? sanitize_key( wp_unslash( $_POST['engine'] ) ) : 'php';
        $extensions = isset( $_POST['extensions'] ) ? wp_unslash( $_POST['extensions'] ) : $defaults['extensions'];
        $exclude_dirs = isset( $_POST['exclude_dirs'] ) ? wp_unslash( $_POST['exclude_dirs'] ) : $defaults['exclude_dirs'];

        $args = [
            'scope'        => $scope,
            'plugin'       => $plugin,
            'mu_plugin'    => $mu_plugin,
            'mode'         => $mode,
            'term'         => $term,
            'engine'       => $engine,
            'ignore_case'  => ddd_dt_ts_bool_from_post( 'ignore_case', true ),
            'whole_word'   => ddd_dt_ts_bool_from_post( 'whole_word', false ),
            'regex'        => ddd_dt_ts_bool_from_post( 'regex', false ),
            'files_only'   => ddd_dt_ts_bool_from_post( 'files_only', false ),
            'extensions'   => ddd_dt_ts_extensions_from_csv( $extensions ),
            'exclude_dirs' => ddd_dt_ts_exclude_dirs_from_csv( $exclude_dirs ),
            'max_results'  => max( 1, min( 2000, absint( $_POST['max_results'] ?? $defaults['max_results'] ) ) ),
            'max_file_kb'  => max( 1, min( 1024 * 1024 * 20, absint( $_POST['max_file_kb'] ?? $defaults['max_file_kb'] ) ) ),
            'max_ms'       => max( 1000, min( 20000, absint( $_POST['max_ms'] ?? $defaults['max_ms'] ) ) ),
        ];

        $result = DDD_DT_TS_Search::run( $args );
        if ( empty( $result['ok'] ) ) {
            wp_send_json_error( $result['error'] ?? 'Search failed.' );
        }

        DDD_DT_Logger::write(
            'troubleshooter',
            'search',
            [
                'scope'        => $result['meta']['scope'] ?? $scope,
                'mode'         => $result['meta']['mode'] ?? $mode,
                'engine_used'  => $result['meta']['engine_used'] ?? '',
                'matches'      => $result['meta']['matches'] ?? 0,
                'matched_files'=> $result['meta']['matched_files'] ?? 0,
                'duration_ms'  => $result['meta']['duration_ms'] ?? 0,
            ]
        );

        wp_send_json_success( $result );
    }
}
