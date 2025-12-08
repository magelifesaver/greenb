<?php
/**
 * File: includes/class-ddd-cfc-utils.php
 * Description: Utility methods for recursively scanning files and converting file paths
 *              to plugin-relative or MU-plugin-relative paths.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DDD_CFC_Utils' ) ) :

class DDD_CFC_Utils {

    /**
     * Recursively gather all files under a given directory.
     *
     * @param string $dir Absolute path to a directory.
     * @return array An array of absolute file paths found under $dir.
     */
    public static function get_files_recursively( $dir ) {
        $all_files = [];

        // Ensure the directory exists and is readable.
        if ( ! is_dir( $dir ) || ! is_readable( $dir ) ) {
            return $all_files;
        }

        $items = scandir( $dir );
        if ( false === $items ) {
            return $all_files;
        }

        foreach ( $items as $item ) {
            if ( in_array( $item, [ '.', '..' ], true ) ) {
                continue;
            }

            $path = $dir . '/' . $item;

            if ( is_dir( $path ) ) {
                // Recurse into subdirectory.
                $all_files = array_merge( $all_files, self::get_files_recursively( $path ) );
            } elseif ( is_file( $path ) ) {
                $all_files[] = $path;
            }
        }

        return $all_files;
    }

    /**
     * Convert an absolute file path to a plugins-relative path.
     *
     * Examples:
     *   /var/www/html/wp-content/plugins/my-plugin/includes/foo.php
     *     => my-plugin/includes/foo.php
     *
     *   /var/www/html/wp-content/mu-plugins/my-mu/file.php
     *     => mu-plugins/my-mu/file.php
     *
     * @param string $file_path Absolute path to a file.
     * @return string Relative path. If the input path does not live under a known
     *                plugins directory, returns basename($file_path).
     */
    public static function strip_to_plugins_subpath( $file_path ) {
        // Normalize directory separators.
        $normalized = str_replace( '\\', '/', $file_path );

        // 1) Standard plugins dir: wp-content/plugins/.
        $needle_plugins = 'wp-content/plugins/';
        $pos_plugins    = strpos( $normalized, $needle_plugins );
        if ( false !== $pos_plugins ) {
            return substr( $normalized, $pos_plugins + strlen( $needle_plugins ) );
        }

        // 2) MU-plugins dir: wp-content/mu-plugins/.
        $needle_mu = 'wp-content/mu-plugins/';
        $pos_mu    = strpos( $normalized, $needle_mu );
        if ( false !== $pos_mu ) {
            return 'mu-plugins/' . substr( $normalized, $pos_mu + strlen( $needle_mu ) );
        }

        // 3) Fallback to just the basename.
        return basename( $file_path );
    }
}

endif;
