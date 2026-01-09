<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

class DDD_DT_Debug_Log_Snapshot {
    private $log_path;

    public function __construct( string $log_path ) {
        $this->log_path = $log_path;
        add_action( 'wp_ajax_ddd_dt_dbg_snapshot', [ $this, 'ajax_snapshot' ] );
        add_action( 'wp_ajax_ddd_dt_dbg_clear_snapshot', [ $this, 'ajax_clear_snapshot' ] );
        add_action( 'wp_ajax_ddd_dt_dbg_download_snapshot', [ $this, 'ajax_download_snapshot' ] );
    }

    private function snapshot_dir(): string {
        $u = wp_upload_dir();
        $dir = trailingslashit( $u['basedir'] ) . 'ddd-dev-tools/debug-log';
        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );
        }
        return $dir;
    }

    private function snapshot_path(): string {
        return trailingslashit( $this->snapshot_dir() ) . 'debug-snapshot.log';
    }

    public function exists(): bool {
        return file_exists( $this->snapshot_path() );
    }

    public function modified_ts(): int {
        return $this->exists() ? (int) filemtime( $this->snapshot_path() ) : 0;
    }

    public function ajax_snapshot() {
        check_ajax_referer( 'ddd_dt_dbg_snapshot', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }
        if ( empty( DDD_DT_Debug_Log_Manager::settings()['enabled'] ) ) {
            wp_send_json_error( 'Debug Log module is disabled.' );
        }
        if ( ! file_exists( $this->log_path ) || ! is_readable( $this->log_path ) ) {
            wp_send_json_error( 'debug.log not found or not readable.' );
        }

        $lines = file( $this->log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file
        $unique = array_values( array_unique( $lines ) );
        $snap = $this->snapshot_path();
        $ok = file_put_contents( $snap, implode( "\n", $unique ) . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_write_file_put_contents
        if ( $ok === false ) {
            wp_send_json_error( 'Failed to write snapshot.' );
        }

        DDD_DT_Logger::write( 'debug_log_manager', 'snapshot', [ 'lines_in' => count( $lines ), 'lines_out' => count( $unique ) ] );
        wp_send_json_success( [ 'snapshot' => basename( $snap ), 'lines' => count( $unique ) ] );
    }

    public function ajax_clear_snapshot() {
        check_ajax_referer( 'ddd_dt_dbg_clear_snapshot', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }
        if ( empty( DDD_DT_Debug_Log_Manager::settings()['enabled'] ) ) {
            wp_send_json_error( 'Debug Log module is disabled.' );
        }

        $snap = $this->snapshot_path();
        if ( file_exists( $snap ) ) {
            @unlink( $snap );
        }
        DDD_DT_Logger::write( 'debug_log_manager', 'clear_snapshot', [] );
        wp_send_json_success( true );
    }

    public function ajax_download_snapshot() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }
        if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'ddd_dt_dbg_download_snapshot' ) ) {
            wp_die( 'Invalid nonce.' );
        }
        if ( empty( DDD_DT_Debug_Log_Manager::settings()['enabled'] ) ) {
            wp_die( 'Debug Log module is disabled.' );
        }

        $snap = $this->snapshot_path();
        if ( ! file_exists( $snap ) ) {
            wp_die( 'Snapshot not found.' );
        }

        header( 'Content-Type: text/plain' );
        header( 'Content-Disposition: attachment; filename="debug-snapshot.log"' );
        header( 'Content-Length: ' . filesize( $snap ) );
        readfile( $snap ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile
        exit;
    }
}
