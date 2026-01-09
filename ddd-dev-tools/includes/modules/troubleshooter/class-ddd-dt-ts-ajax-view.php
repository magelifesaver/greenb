<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

require_once DDD_DT_DIR . 'includes/modules/troubleshooter/search/helpers.php';
require_once DDD_DT_DIR . 'includes/modules/troubleshooter/search/class-ddd-dt-ts-resolver.php';

class DDD_DT_TS_Ajax_View {
    public static function handle() {
        check_ajax_referer( 'ddd_dt_ts_view', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }
        if ( empty( DDD_DT_Troubleshooter::settings()['enabled'] ) ) {
            wp_send_json_error( 'Troubleshooter module is disabled.' );
        }

        $scope = isset( $_POST['scope'] ) ? sanitize_key( wp_unslash( $_POST['scope'] ) ) : 'plugin';
        $plugin = isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : '';
        $mu_plugin = isset( $_POST['mu_plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['mu_plugin'] ) ) : '';
        $file = isset( $_POST['file'] ) ? sanitize_text_field( wp_unslash( $_POST['file'] ) ) : '';
        $line = isset( $_POST['line'] ) ? absint( wp_unslash( $_POST['line'] ) ) : 0;
        $context = isset( $_POST['context'] ) ? absint( wp_unslash( $_POST['context'] ) ) : 20;
        $context = max( 0, min( 200, $context ) );

        if ( $file === '' || strpos( $file, '..' ) !== false ) {
            wp_send_json_error( 'Invalid file.' );
        }

        $resolved = DDD_DT_TS_Resolver::resolve_roots( $scope, $plugin, $mu_plugin );
        if ( ! $resolved['ok'] ) {
            wp_send_json_error( $resolved['error'] );
        }

        $abs = ddd_dt_ts_realpath( WP_CONTENT_DIR . '/' . ltrim( $file, '/\\' ) );
        if ( ! $abs || ! is_file( $abs ) || ! is_readable( $abs ) || ! ddd_dt_ts_path_is_within( $abs, WP_CONTENT_DIR ) ) {
            wp_send_json_error( 'File not accessible.' );
        }

        if ( ! self::file_in_roots( $abs, (array) $resolved['roots'] ) ) {
            wp_send_json_error( 'File not within selected scope.' );
        }

        $excerpt = self::read_excerpt( $abs, $line, $context );
        wp_send_json_success( $excerpt );
    }

    private static function file_in_roots( $file_abs, $roots ): bool {
        foreach ( (array) $roots as $root ) {
            $root = ddd_dt_ts_realpath( $root );
            if ( ! $root ) {
                continue;
            }
            if ( is_file( $root ) && $root === $file_abs ) {
                return true;
            }
            if ( is_dir( $root ) && ddd_dt_ts_path_is_within( $file_abs, $root ) ) {
                return true;
            }
        }
        return false;
    }

    private static function read_excerpt( $abs, $line, $context ): array {
        $line = max( 1, (int) $line );
        $start = max( 1, $line - $context );
        $end = $line + $context;

        $fh = new SplFileObject( $abs, 'r' );
        $fh->setFlags( SplFileObject::DROP_NEW_LINE );
        $fh->seek( $start - 1 );

        $out = [];
        while ( ! $fh->eof() ) {
            $current = (int) $fh->key() + 1;
            if ( $current > $end ) {
                break;
            }
            $text = $fh->current();
            $out[] = [ 'line' => $current, 'text' => esc_html( (string) $text ) ];
            $fh->next();
        }

        return [
            'file'       => ddd_dt_ts_relpath_from_content( $abs ),
            'start_line' => $start,
            'end_line'   => $end,
            'focus_line' => $line,
            'lines'      => $out,
        ];
    }
}
