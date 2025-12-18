<?php
/**
 * File handling for the Dev Debug Manager plugin.
 *
 * Provides methods to determine the debug.log location, return new log content
 * for live tailing, clear the log, and output a zipped archive of the log
 * split into parts. Duplicate filtering can be requested by passing a flag
 * via AJAX. All public actions are protected with capability and nonce checks.
 *
 * @package DDD_Dev_Debug_Manager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDD_Dev_Debug_Manager_File {
    /**
     * Constructor registers AJAX handlers for log tailing, download, and clearing.
     */
    public function __construct() {
        add_action( 'wp_ajax_ddd_debug_manager_tail', array( $this, 'ajax_tail' ) );
        add_action( 'wp_ajax_ddd_debug_manager_download', array( $this, 'ajax_download' ) );
        add_action( 'wp_ajax_ddd_debug_manager_clear_log', array( $this, 'ajax_clear_log' ) );
        // Snapshot actions are handled in a separate class.
    }

    /**
     * Determine the absolute path to the debug.log file.
     *
     * If WP_DEBUG_LOG is set to a custom path, that path is used. Otherwise
     * default to WP_CONTENT_DIR/debug.log. No existence or readability
     * checks are performed here.
     *
     * @return string Absolute path to the log file.
     */
    public static function get_log_path() {
        if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG && ! ( true === WP_DEBUG_LOG ) ) {
            return WP_DEBUG_LOG;
        }
        return trailingslashit( WP_CONTENT_DIR ) . 'debug.log';
    }

    /**
     * Serve new log content to the browser for live tailing.
     *
     * Reads bytes from the log file after the given offset and returns the new
     * content along with the updated offset. An optional `unique` flag
     * instructs the method to filter duplicate lines within the fetched chunk.
     *
     * @return void
     */
    public function ajax_tail() {
        check_ajax_referer( 'ddd_debug_manager_tail', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorized', 'ddd-dev-debug-manager' ) );
        }
        $log_file = self::get_log_path();
        if ( ! is_readable( $log_file ) ) {
            wp_send_json_error( __( 'Log file not readable', 'ddd-dev-debug-manager' ) );
        }
        $offset = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
        $size   = filesize( $log_file );
        if ( $offset < 0 || $offset > $size ) {
            $offset = 0;
        }
        $content = '';
        $fp      = fopen( $log_file, 'rb' );
        if ( $fp ) {
            fseek( $fp, $offset );
            $content = stream_get_contents( $fp );
            fclose( $fp );
        }
        // Optionally filter duplicate lines within this chunk.
        $unique = isset( $_POST['unique'] ) && '1' === $_POST['unique'];
        if ( $unique && $content ) {
            $lines  = explode( "\n", $content );
            $lines  = array_unique( $lines );
            $content = implode( "\n", $lines );
        }
        wp_send_json_success( array(
            'offset'  => $size,
            'content' => $content,
        ) );
    }

    /**
     * Output a zipped version of the debug.log file split into 2MB parts.
     *
     * A nonce in the query string is required for security. Each part within
     * the archive is named sequentially (debug-part-1.log, debug-part-2.log, …).
     *
     * @return void
     */
    public function ajax_download() {
        $nonce = isset( $_GET['nonce'] ) ? $_GET['nonce'] : '';
        if ( ! wp_verify_nonce( $nonce, 'ddd_debug_manager_download' ) ) {
            wp_die( esc_html__( 'Invalid nonce', 'ddd-dev-debug-manager' ) );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'ddd-dev-debug-manager' ) );
        }
        $log_file = self::get_log_path();
        if ( ! is_readable( $log_file ) ) {
            wp_die( esc_html__( 'Log file not readable', 'ddd-dev-debug-manager' ) );
        }
        $chunk_size = 2 * 1024 * 1024; // 2 MB
        $temp       = tempnam( sys_get_temp_dir(), 'ddd-debug-' );
        $zip_path   = $temp . '.zip';
        $zip        = new ZipArchive();
        if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
            wp_die( esc_html__( 'Could not create zip archive.', 'ddd-dev-debug-manager' ) );
        }
        $fp    = fopen( $log_file, 'rb' );
        $index = 1;
        while ( ! feof( $fp ) ) {
            $data = fread( $fp, $chunk_size );
            $zip->addFromString( 'debug-part-' . $index . '.log', $data );
            $index++;
        }
        fclose( $fp );
        $zip->close();
        // Stream the zip to the browser.
        header( 'Content-Type: application/zip' );
        header( 'Content-Disposition: attachment; filename="debug-log.zip"' );
        header( 'Content-Length: ' . filesize( $zip_path ) );
        readfile( $zip_path );
        unlink( $zip_path );
        exit;
    }

    /**
     * Clear the contents of the debug.log file.
     *
     * This action truncates the log file to zero bytes. It does not remove
     * the file itself. A nonce and capability check guard the operation.
     *
     * @return void
     */
    public function ajax_clear_log() {
        $nonce = isset( $_GET['nonce'] ) ? $_GET['nonce'] : '';
        if ( ! wp_verify_nonce( $nonce, 'ddd_debug_manager_clear_log' ) ) {
            wp_die( esc_html__( 'Invalid nonce', 'ddd-dev-debug-manager' ) );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'ddd-dev-debug-manager' ) );
        }
        $log_file = self::get_log_path();
        // If file does not exist or is not writable, abort.
        if ( ! file_exists( $log_file ) || ! is_writable( $log_file ) ) {
            wp_send_json_error( array( 'message' => __( 'Log file cannot be cleared.', 'ddd-dev-debug-manager' ) ) );
        }
        // Attempt to truncate the file.
        $fp = fopen( $log_file, 'w' );
        if ( false === $fp ) {
            wp_send_json_error( array( 'message' => __( 'Unable to open log file.', 'ddd-dev-debug-manager' ) ) );
        }
        // Writing nothing to the file truncates it.
        ftruncate( $fp, 0 );
        fclose( $fp );
        wp_send_json_success( array( 'message' => __( 'Log cleared.', 'ddd-dev-debug-manager' ) ) );
    }
}