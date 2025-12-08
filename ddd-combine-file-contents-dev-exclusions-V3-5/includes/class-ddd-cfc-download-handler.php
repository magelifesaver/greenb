<?php
/**
 * File: includes/class-ddd-cfc-download-handler.php
 * Description: Hooks into admin_post_cfc_v2_download to generate and stream the combined .txt file.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DDD_CFC_Download_Handler' ) ) :

class DDD_CFC_Download_Handler {

    /**
     * Hook registration.
     */
    public static function init() {
        add_action( 'admin_post_cfc_v2_download', [ __CLASS__, 'handle_download' ] );
    }

    /**
     * Perform the download: read $_POST['cfc_items'], combine contents, and force a .txt download.
     */
    public static function handle_download() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions.' );
        }

        // Verify nonce.
        check_admin_referer( 'cfc_v2_form_action', 'cfc_v2_form_nonce' );

        // Grab the array of checked items (files or folders).
        $items = isset( $_POST['cfc_items'] ) ? (array) $_POST['cfc_items'] : [];
        if ( empty( $items ) ) {
            wp_die( 'No files or folders selected.' );
        }

        // Collect every absolute file path in $all_files.
        $all_files = [];

        foreach ( $items as $rel ) {
            $rel_sanitized = sanitize_text_field( wp_unslash( $rel ) );

            // Decide base path: normal plugins vs MU-plugins.
            if ( 0 === strpos( $rel_sanitized, 'mu-plugins/' ) ) {
                $sub      = substr( $rel_sanitized, strlen( 'mu-plugins/' ) );
                $base_dir = defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : WP_CONTENT_DIR . '/mu-plugins';
                $abs_path = trailingslashit( $base_dir ) . $sub;
            } else {
                $base_dir = defined( 'CFC_PLUGINS_BASE_DIR' ) ? CFC_PLUGINS_BASE_DIR : WP_CONTENT_DIR . '/plugins';
                $abs_path = trailingslashit( $base_dir ) . $rel_sanitized;
            }

            if ( is_file( $abs_path ) ) {
                $all_files[] = $abs_path;
            } elseif ( is_dir( $abs_path ) ) {
                // Use our utility function to get every file under this folder.
                $all_files = array_merge(
                    $all_files,
                    DDD_CFC_Utils::get_files_recursively( $abs_path )
                );
            }
        }

        // Remove duplicates (just in case).
        $all_files = array_unique( $all_files );
        if ( empty( $all_files ) ) {
            wp_die( 'No files found to combine.' );
        }

        // Build combined content with per-file headers.
        $plugin_header_lines = [];
        foreach ( $all_files as $file ) {
            $rel         = DDD_CFC_Utils::strip_to_plugins_subpath( $file );
            $plugin_root = strtok( $rel, '/' );
            $plugin_header_lines[] = $plugin_root . ' - ' . $rel;
        }

        $header  = "Directory Structure:\n";
        $header .= implode( "\n", $plugin_header_lines ) . "\n\n";

        $body = '';
        foreach ( $all_files as $file ) {
            $rel           = DDD_CFC_Utils::strip_to_plugins_subpath( $file );
            $plugin_root   = strtok( $rel, '/' );
            $file_contents = @file_get_contents( $file );
            if ( false === $file_contents ) {
                $file_contents = '';
            }
            $line_count = substr_count( $file_contents, "\n" ) + 1;

            preg_match_all(
                '/function\s+([a-zA-Z0-9_]+)\s*\(/',
                $file_contents,
                $matches
            );
            $functions = array_unique( $matches[1] ?? [] );
            $func_list = $functions ? implode( ', ', $functions ) : 'None';

            $body .= str_repeat( '=', 10 ) . " [ $plugin_root ] $rel " . str_repeat( '=', 10 ) . "\n";
            $body .= str_repeat( '=', 10 ) . " [ Total Lines in File: $line_count ] " . str_repeat( '=', 10 ) . "\n";
            $body .= str_repeat( '=', 10 ) . " [ Functions: $func_list ] " . str_repeat( '=', 10 ) . "\n";
            $body .= $file_contents . "\n";
        }

        $combined_full        = $header . $body;
        $total_lines_combined = substr_count( $combined_full, "\n" ) + 1;
        $combined_full        = $header
                              . "Total Lines in Combined File: $total_lines_combined\n\n"
                              . $body;

        // Send headers for download.
        nocache_headers();
        header( 'Content-Type: text/plain; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="combined-files.txt"' );
        header( 'Content-Length: ' . strlen( $combined_full ) );

        echo $combined_full; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }
}

DDD_CFC_Download_Handler::init();

endif;
