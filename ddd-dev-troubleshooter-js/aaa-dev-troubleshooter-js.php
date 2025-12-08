<?php
/**
 * Plugin Name: DDD Dev Troubleshooting JS
 * Plugin URI:  https://example.com
 * Description: Admin tool to scan active plugin files for wp_enqueue_script and wp_enqueue_style calls,
 *              listing each enqueue per file with numbering, totals, and ability to exclude folders,
 *              and optionally hide files with zero enqueues.
 * Version:     1.2.0
 * Author:      Your Name
 * Author URI:  https://example.com
 * Text Domain: ddd-dev-troubleshooting-js
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDD_Dev_Troubleshooting_JS {
    private static $instance;

    public static function init() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_page' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_ddd_scan_js', [ $this, 'ajax_scan_js' ] );
    }

    public function add_admin_page() {
        add_menu_page(
            'DDD JS Scan',
            'DDD JS Scan',
            'manage_options',
            'ddd-js-scan',
            [ $this, 'render_admin_page' ],
            'dashicons-editor-code',
            81
        );
    }

    public function enqueue_assets( $hook ) {
        if ( 'toplevel_page_ddd-js-scan' !== $hook ) {
            return;
        }
        wp_enqueue_script(
            'ddd-js-script',
            plugin_dir_url( __FILE__ ) . 'assets/js/ddd-js-troubleshoot.js',
            [ 'jquery' ],
            '1.2.0',
            true
        );
        wp_localize_script(
            'ddd-js-script',
            'DDD_JS_Ajax',
            [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'ddd-js-scan-nonce' ),
            ]
        );
        wp_enqueue_style(
            'ddd-js-style',
            plugin_dir_url( __FILE__ ) . 'assets/css/ddd-js-troubleshoot.css',
            [],
            '1.0.0'
        );
    }

    public function render_admin_page() {
        $self = get_plugin_data( __FILE__, false, false );
        $ver  = ! empty( $self['Version'] ) ? $self['Version'] : 'N/A';

        $active_site = is_multisite() ? get_site_option( 'active_sitewide_plugins', [] ) : [];
        $sitewide    = array_keys( $active_site );
        $site_active = get_option( 'active_plugins', [] );
        $all         = array_unique( array_merge( $site_active, $sitewide ) );
        sort( $all );
        ?>
        <div class="wrap">
            <h1>DDD Dev Troubleshooting JS</h1>
            <p><strong>Version:</strong> <?php echo esc_html( $ver ); ?></p>
            <form id="ddd-js-form">
                <p>
                    <label for="ddd-js-exclude">Exclude Folders (comma separated):</label><br>
                    <input type="text" id="ddd-js-exclude" name="exclude_folders" value="vendor, logs" style="width:300px;" placeholder="e.g. vendor,tests" />
                </p>
                <p>
                    <label><input type="checkbox" id="ddd-js-include-empty" name="include_empty" /> Include files with no enqueues</label>
                </p>
                <p>
                    <label for="ddd-js-plugin-select">Select Active Plugin:</label><br>
                    <select id="ddd-js-plugin-select" name="plugin_file">
                        <option value="">-- Choose --</option>
                        <?php foreach ( $all as $file ) {
                            $path = WP_PLUGIN_DIR . '/' . $file;
                            if ( file_exists( $path ) ) {
                                $data = get_plugin_data( $path, false, false );
                                $name = ! empty( $data['Name'] ) ? $data['Name'] : $file;
                                printf(
                                    '<option value="%s">%s</option>',
                                    esc_attr( $file ),
                                    esc_html( "$name ($file)" )
                                );
                            }
                        } ?>
                    </select>
                </p>
            </form>
            <div id="ddd-js-results"></div>
        </div>
        <?php
    }

    public function ajax_scan_js() {
        check_ajax_referer( 'ddd-js-scan-nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }
        $plugin_file   = sanitize_text_field( $_POST['plugin_file'] ?? '' );
        $excludes      = isset( $_POST['exclude_folders'] )
                          ? array_filter( array_map( 'trim', explode( ',', sanitize_text_field( $_POST['exclude_folders'] ) ) ) )
                          : [];
        $include_empty = ! empty( $_POST['include_empty'] );

        if ( empty( $plugin_file ) ) {
            wp_send_json_error( 'No plugin selected' );
        }
        $base_dir = WP_PLUGIN_DIR . '/' . dirname( $plugin_file );
        if ( ! is_dir( $base_dir ) ) {
            wp_send_json_error( 'Plugin directory not found: ' . esc_html( $base_dir ) );
        }
        $map = $this->scan_js( $base_dir, $excludes, $include_empty );
        wp_send_json_success( $map );
    }

    private function scan_js( $dir, $excludes, $include_empty ) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS )
        );
        $map = [];

        foreach ( $iterator as $file ) {
            if ( ! $file->isFile() ) {
                continue;
            }
            $path     = $file->getPathname();
            $relative = str_replace( WP_PLUGIN_DIR . '/', '', $path );
            foreach ( $excludes as $ex ) {
                if ( strpos( $relative, $ex . '/' ) === 0 || strpos( $relative, '/' . $ex . '/' ) !== false ) {
                    continue 2;
                }
            }
            $content = file_get_contents( $path );
            $enqs    = [];
            $get_ln  = function( $off ) use ( $content ) {
                return substr_count( substr( $content, 0, $off ), "\n" ) + 1;
            };

            preg_match_all(
                '/\bwp_enqueue_(script|style)\s*\(\s*["\']([^"\']+)["\']/',
                $content,
                $matches,
                PREG_SET_ORDER | PREG_OFFSET_CAPTURE
            );
            $index = 1;
            foreach ( $matches as $m ) {
                $type   = $m[1][0];
                $handle = $m[2][0];
                $offset = $m[0][1];
                $line   = $get_ln( $offset );
                $enqs[] = sprintf( '%d. %s: %s (line %d)', $index++, $type, $handle, $line );
            }

            $count = count( $enqs );
            if ( 0 === $count && ! $include_empty ) {
                continue;
            }

            $items = $count ? $enqs : [ 'None Found' ];
            $items[] = sprintf(
                '<strong>Total enqueues:</strong> <span style="color:green; font-size:1.2em;">%d</span>',
                $count
            );
            $map[ $relative ] = $items;
        }
        return $map;
    }
}

DDD_Dev_Troubleshooting_JS::init();