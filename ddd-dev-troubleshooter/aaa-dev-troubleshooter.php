<?php
/**
 * Plugin Name: DDD Dev Troubleshooter
 * Plugin URI:  https://example.com
 * Description: Admin tool to scan active plugin files for include/require dependencies,
 *              with options to exclude folders, toggle inclusion of empty results,
 *              expanding dynamic module loops, path assignments, detecting glob-based loaders,
 *              catching AAA_WF_PLUGIN_DIR includes, reporting line numbers, numbering,
 *              and appending a styled total count.
 * Version:     1.8.0
 * Author:      Your Name
 * Author URI:  https://example.com
 * Text Domain: dev-troubleshooter
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Dev_Troubleshooter {
    private static $instance;

    public static function init() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_page' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_ajax_dt_scan_plugin', [ $this, 'ajax_scan_plugin' ] );
    }

    public function add_admin_page() {
        add_menu_page(
            __( 'Dev Troubleshooter', 'dev-troubleshooter' ),
            __( 'Dev Troubleshooter', 'dev-troubleshooter' ),
            'manage_options',
            'dev-troubleshooter',
            [ $this, 'render_admin_page' ],
            'dashicons-admin-tools',
            80
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( 'toplevel_page_dev-troubleshooter' !== $hook ) {
            return;
        }
        wp_enqueue_script(
            'dt-script',
            plugin_dir_url( __FILE__ ) . 'assets/js/dev-troubleshooter.js',
            [ 'jquery' ],
            '1.0.0',
            true
        );
        wp_localize_script( 'dt-script', 'DT_Ajax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'dt-scan-nonce' ),
        ] );
        wp_enqueue_style(
            'dt-style',
            plugin_dir_url( __FILE__ ) . 'assets/css/dev-troubleshooter.css',
            [],
            '1.0.0'
        );
    }

    public function render_admin_page() {
        $self       = get_plugin_data( __FILE__, false, false );
        $version    = ! empty( $self['Version'] ) ? $self['Version'] : 'N/A';
        $active_site = is_multisite() ? get_site_option( 'active_sitewide_plugins', [] ) : [];
        $sitewide   = array_keys( $active_site );
        $site_active = get_option( 'active_plugins', [] );
        $all_plugins = array_unique( array_merge( $site_active, $sitewide ) );
        sort( $all_plugins );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Dev Troubleshooter', 'dev-troubleshooter' ); ?></h1>
            <p><strong><?php esc_html_e( 'Version:', 'dev-troubleshooter' ); ?></strong> <?php echo esc_html( $version ); ?></p>
            <form id="dt-form">
                <p>
                    <label for="dt-exclude-folders"><?php esc_html_e( 'Exclude Folders (comma separated):', 'dev-troubleshooter' ); ?></label><br>
                    <input type="text" id="dt-exclude-folders" name="exclude_folders" value="vendor, logs" style="width:300px;" placeholder="vendor,tests" />
                </p>
                <p>
                    <label><input type="checkbox" id="dt-include-empty" name="include_empty" /> <?php esc_html_e( 'Include files with no dependencies', 'dev-troubleshooter' ); ?></label>
                </p>
                <p>
                    <label for="dt-plugin-select"><?php esc_html_e( 'Select Active Plugin:', 'dev-troubleshooter' ); ?></label><br>
                    <select id="dt-plugin-select" name="plugin_file">
                        <option value=""><?php esc_html_e( '-- Choose --', 'dev-troubleshooter' ); ?></option>
                        <?php foreach ( $all_plugins as $file ) {
                            $path = WP_PLUGIN_DIR . '/' . $file;
                            if ( file_exists( $path ) ) {
                                $data = get_plugin_data( $path, false, false );
                                $name = ! empty( $data['Name'] ) ? $data['Name'] : $file;
                                printf( '<option value="%s">%s</option>', esc_attr( $file ), esc_html( "$name ($file)" ) );
                            }
                        } ?>
                    </select>
                </p>
            </form>
            <div id="dt-results"></div>
        </div>
        <?php
    }

    public function ajax_scan_plugin() {
        check_ajax_referer( 'dt-scan-nonce', 'nonce' );
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
        $map = $this->scan_directory( $base_dir, $excludes, $include_empty );
        wp_send_json_success( $map );
    }

    private function scan_directory( $dir, $excludes, $include_empty ) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS )
        );
        $map = [];

        foreach ( $iterator as $file ) {
            if ( ! $file->isFile() || 'php' !== $file->getExtension() ) {
                continue;
            }
            $path     = $file->getPathname();
            $relative = str_replace( WP_PLUGIN_DIR . '/', '', $path );
            foreach ( $excludes as $ex ) {
                if ( strpos( $relative, $ex . '/' ) === 0 || false !== strpos( $relative, '/' . $ex . '/' ) ) {
                    continue 2;
                }
            }
            $content = file_get_contents( $path );
            $deps    = [];
            $get_ln  = function( $off ) use ( $content ) {
                return substr_count( substr( $content, 0, $off ), "\n" ) + 1;
            };

            // Static includes/requires
            preg_match_all(
                '/\b(require|require_once|include|include_once)\s*\(?\s*["\'](.+?\.php)["\']\s*\)?/i',
                $content,
                $static_matches,
                PREG_SET_ORDER | PREG_OFFSET_CAPTURE
            );
            foreach ( $static_matches as $m ) {
                $deps[] = sprintf( '%s (line %d)', $m[2][0], $get_ln( $m[2][1] ) );
            }

            // Dynamic includes via constants/dirs
            preg_match_all(
                '/\b(require|require_once|include|include_once)\s*[^;]*(?:__DIR__|dirname\(\s*__FILE__\s*\)|plugin_dir_path\(\s*__FILE__\s*\)|AAA_WF_PLUGIN_DIR)\s*\.\s*["\'](.+?\.php)["\']/i',
                $content,
                $dyn_matches,
                PREG_SET_ORDER | PREG_OFFSET_CAPTURE
            );
            foreach ( $dyn_matches as $m ) {
                $deps[] = sprintf( '%s (line %d)', $m[2][0], $get_ln( $m[2][1] ) );
            }

            // Path-only assignments
            preg_match_all(
                '/(?:__DIR__|dirname\(\s*__FILE__\s*\)|plugin_dir_path\(\s*__FILE__\s*\))\s*\.\s*["\'](.+?\.php)["\']/i',
                $content,
                $assign_matches,
                PREG_SET_ORDER | PREG_OFFSET_CAPTURE
            );
            foreach ( $assign_matches as $m ) {
                $deps[] = sprintf( '%s (line %d)', $m[1][0], $get_ln( $m[1][1] ) );
            }

            // Glob-based includes
            preg_match_all(
                '/glob\s*\(\s*(?:__DIR__|dirname\(\s*__FILE__\s*\)|plugin_dir_path\(\s*__FILE__\s*\))\s*\.\s*["\'](.+?\*\.php)["\']/',
                $content,
                $glob_matches,
                PREG_SET_ORDER | PREG_OFFSET_CAPTURE
            );
            foreach ( $glob_matches as $gm ) {
                $pattern = $gm[1][0];
                $off     = $gm[1][1];
                $full    = dirname( $path ) . '/' . $pattern;
                foreach ( glob( $full ) as $gfile ) {
                    $deps[] = sprintf( '%s (glob, line %d)', str_replace( WP_PLUGIN_DIR . '/', '', $gfile ), $get_ln( $off ) );
                }
            }

            if ( empty( $deps ) && ! $include_empty ) {
                continue;
            }

            // Number and collect
            $items = [];
            if ( ! empty( $deps ) ) {
                $unique = array_unique( $deps );
                $i      = 1;
                foreach ( $unique as $dep ) {
                    $items[] = sprintf( '%d. %s', $i++, $dep );
                }
                $total = count( $items );
            } else {
                $items = [ 'None Found' ];
                $total = 0;
            }

            // Styled total
            $items[] = sprintf(
                '<strong>Total:</strong> <span style="color:red; font-size:1.2em;">%d</span>',
                $total
            );

            $map[ $relative ] = $items;
        }

        return $map;
    }
}

Dev_Troubleshooter::init();
