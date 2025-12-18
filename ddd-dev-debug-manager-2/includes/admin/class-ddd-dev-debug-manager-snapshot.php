<?php
/**
 * Snapshot handling for the Dev Debug Manager plugin.
 *
 * Creates a snapshot copy of the current debug.log file and serves it for
 * download. The snapshot is cached on disk and can be cleared via an
 * AJAX action. Duplicate lines can be removed on request. A separate
 * handler in DDD_Dev_Debug_Manager_File is responsible for clearing the
 * live log itself.
 *
 * @package DDD_Dev_Debug_Manager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDD_Dev_Debug_Manager_Snapshot {
    /**
     * Constructor registers AJAX handlers for snapshot creation and clearing.
     */
    public function __construct() {
        add_action( 'wp_ajax_ddd_debug_manager_snapshot', array( $this, 'ajax_snapshot' ) );
        add_action( 'wp_ajax_ddd_debug_manager_clear_snapshot', array( $this, 'ajax_clear_snapshot' ) );
    }

    /**
     * Determine where the snapshot file should be stored.
     *
     * Uses the WordPress uploads directory and ensures the plugin folder exists.
     *
     * @return string Absolute path to the snapshot file.
     */
    private function get_snapshot_path() {
        $upload_dir = wp_upload_dir();
        $dir        = trailingslashit( $upload_dir['basedir'] ) . 'ddd-dev-debug-manager/';
        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );
        }
        return $dir . 'debug-snapshot.log';
    }

    /**
     * Create a fresh snapshot of the debug.log file and serve it for download.
     *
     * Optional parameter `exclude_duplicates` removes duplicate lines from the
     * output. The snapshot is written to disk for caching and can be cleared
     * later via the clear action.
     *
     * @return void
     */
    public function ajax_snapshot() {
        $nonce = isset( $_GET['nonce'] ) ? $_GET['nonce'] : '';
        if ( ! wp_verify_nonce( $nonce, 'ddd_debug_manager_snapshot' ) ) {
            wp_die( esc_html__( 'Invalid nonce', 'ddd-dev-debug-manager' ) );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'ddd-dev-debug-manager' ) );
        }
        $exclude  = isset( $_GET['exclude_duplicates'] ) && '1' === $_GET['exclude_duplicates'];
        $log_file = DDD_Dev_Debug_Manager_File::get_log_path();
        if ( ! is_readable( $log_file ) ) {
            wp_die( esc_html__( 'Log file not readable', 'ddd-dev-debug-manager' ) );
        }
        $content = file_get_contents( $log_file );
        if ( false === $content ) {
            wp_die( esc_html__( 'Unable to read log file', 'ddd-dev-debug-manager' ) );
        }
        if ( $exclude ) {
            $lines  = explode( "\n", $content );
            $unique = array_unique( $lines );
            $content = implode( "\n", $unique );
        }
        $path = $this->get_snapshot_path();
        file_put_contents( $path, $content );
        header( 'Content-Type: text/plain' );
        header( 'Content-Disposition: attachment; filename="debug-snapshot.log"' );
        header( 'Content-Length: ' . strlen( $content ) );
        echo $content;
        exit;
    }

    /**
     * Delete the cached snapshot file.
     *
     * @return void
     */
    public function ajax_clear_snapshot() {
        $nonce = isset( $_GET['nonce'] ) ? $_GET['nonce'] : '';
        if ( ! wp_verify_nonce( $nonce, 'ddd_debug_manager_clear_snapshot' ) ) {
            wp_die( esc_html__( 'Invalid nonce', 'ddd-dev-debug-manager' ) );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'ddd-dev-debug-manager' ) );
        }
        $path = $this->get_snapshot_path();
        if ( file_exists( $path ) ) {
            unlink( $path );
        }
        wp_send_json_success( array( 'message' => __( 'Snapshot cache cleared.', 'ddd-dev-debug-manager' ) ) );
    }
}