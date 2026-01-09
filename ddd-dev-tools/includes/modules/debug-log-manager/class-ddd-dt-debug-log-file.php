<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

class DDD_DT_Debug_Log_File {
    private $path;

    public function __construct() {
        $this->path = WP_CONTENT_DIR . '/debug.log';
        add_action( 'wp_ajax_ddd_dt_dbg_tail', [ $this, 'ajax_tail' ] );
        add_action( 'wp_ajax_ddd_dt_dbg_download', [ $this, 'ajax_download' ] );
    }

    public function get_path(): string {
        return $this->path;
    }

    public function exists(): bool {
        return file_exists( $this->path );
    }

    public function readable(): bool {
        return is_readable( $this->path );
    }

    public function size_bytes(): int {
        return $this->exists() ? (int) filesize( $this->path ) : 0;
    }

    public function modified_ts(): int {
        return $this->exists() ? (int) filemtime( $this->path ) : 0;
    }

    public function ajax_tail() {
        check_ajax_referer( 'ddd_dt_dbg_tail', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }
        if ( empty( DDD_DT_Debug_Log_Manager::settings()['enabled'] ) ) {
            wp_send_json_error( 'Debug Log module is disabled.' );
        }
        if ( ! $this->exists() || ! $this->readable() ) {
            wp_send_json_success( [ 'offset' => 0, 'content' => '' ] );
        }

        $offset = isset( $_POST['offset'] ) ? absint( wp_unslash( $_POST['offset'] ) ) : 0;
        $max = 32768;
        $size = $this->size_bytes();
        if ( $offset > $size ) {
            $offset = 0;
        }

        $fh = @fopen( $this->path, 'rb' );
        if ( ! $fh ) {
            wp_send_json_error( 'Unable to read debug.log.' );
        }
        fseek( $fh, $offset );
        $content = (string) fread( $fh, $max );
        $new_offset = ftell( $fh );
        fclose( $fh );

        wp_send_json_success( [ 'offset' => (int) $new_offset, 'content' => $content ] );
    }

    public function ajax_download() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }
        if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'ddd_dt_dbg_download' ) ) {
            wp_die( 'Invalid nonce.' );
        }
        if ( empty( DDD_DT_Debug_Log_Manager::settings()['enabled'] ) ) {
            wp_die( 'Debug Log module is disabled.' );
        }
        if ( ! $this->exists() || ! $this->readable() ) {
            wp_die( 'debug.log not found or not readable.' );
        }

        $chunk_mb = (int) ( DDD_DT_Debug_Log_Manager::settings()['download_chunk_mb'] ?? 2 );
        $chunk_mb = max( 1, min( 25, $chunk_mb ) );

        $this->download_debug_log( $chunk_mb );
        exit;
    }

    private function download_debug_log( int $chunk_size_mb ) {
        if ( ! class_exists( 'ZipArchive' ) ) {
            wp_die( 'ZipArchive not available on this server.' );
        }
        $zip = new ZipArchive();
        $tmp = wp_tempnam( 'ddd-debug-log.zip' );
        if ( ! $tmp || $zip->open( $tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
            wp_die( 'Unable to create zip file.' );
        }

        $size = $this->size_bytes();
        $chunk_bytes = $chunk_size_mb * 1024 * 1024;
        $fh = fopen( $this->path, 'rb' );
        if ( ! $fh ) {
            $zip->close();
            wp_die( 'Unable to read debug.log.' );
        }

        $part = 1;
        while ( ! feof( $fh ) ) {
            $data = fread( $fh, $chunk_bytes );
            if ( $data === '' ) {
                break;
            }
            $zip->addFromString( 'debug-log-part-' . $part . '.log', $data );
            $part++;
        }

        fclose( $fh );
        $zip->close();

        DDD_DT_Logger::write( 'debug_log_manager', 'download', [ 'size' => $size, 'chunk_mb' => $chunk_size_mb, 'parts' => $part - 1 ] );

        header( 'Content-Type: application/zip' );
        header( 'Content-Disposition: attachment; filename="debug-log.zip"' );
        header( 'Content-Length: ' . filesize( $tmp ) );
        readfile( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile
        @unlink( $tmp );
        exit;
    }
}
