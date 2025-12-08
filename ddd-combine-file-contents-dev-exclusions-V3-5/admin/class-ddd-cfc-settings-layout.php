<?php
/**
 * File Path: admin/class-ddd-cfc-settings-layout.php
 * Purpose : Registers the Combine File Contents page and handles combine/preview logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'DDD_CFC_Settings_Layout' ) ) :

class DDD_CFC_Settings_Layout {

    /**
     * Per-file debug toggle.
     */
    protected static $debug = true;

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
    }

    public static function register_admin_menu() {
        add_menu_page(
            'Combine File Contents',
            'Combine File Tree V3.0',
            'manage_options',
            'ddd-cfc-settings',
            array( __CLASS__, 'render_settings_page' ),
            'dashicons-admin-tools',
            80
        );
    }

    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $selected_items = isset( $_POST['cfc_items'] ) && is_array( $_POST['cfc_items'] )
            ? array_map( 'sanitize_text_field', wp_unslash( $_POST['cfc_items'] ) )
            : array();

        $error         = '';
        $combined_full = '';

        // === Handle "Combine Files" submit ==================================
        if (
            isset( $_POST['cfc_v2_submit'], $_POST['cfc_v2_form_nonce'] ) &&
            'combine' === $_POST['cfc_v2_submit']
        ) {
            check_admin_referer( 'cfc_v2_form_action', 'cfc_v2_form_nonce' );

            if ( empty( $selected_items ) ) {
                $error = 'Please select at least one file or folder.';
            } else {
                $all_files = array();

                if ( self::$debug ) {
                    error_log( '[ddd-cfc] Combine requested. Selected items: ' . wp_json_encode( $selected_items ) );
                }

                foreach ( $selected_items as $rel ) {
                    $rel = sanitize_text_field( $rel );

                    // Decide base dir: normal plugins vs MU-plugins.
                    if ( 0 === strpos( $rel, 'mu-plugins/' ) ) {
                        $sub      = substr( $rel, strlen( 'mu-plugins/' ) );
                        $base_dir = defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : WP_CONTENT_DIR . '/mu-plugins';
                        $abs_path = trailingslashit( $base_dir ) . $sub;
                    } else {
                        $base_dir = defined( 'CFC_PLUGINS_BASE_DIR' ) ? CFC_PLUGINS_BASE_DIR : WP_CONTENT_DIR . '/plugins';
                        $abs_path = trailingslashit( $base_dir ) . $rel;
                    }

                    if ( self::$debug ) {
                        error_log(
                            '[ddd-cfc] Resolve item: rel=' . $rel .
                            ' base=' . $base_dir .
                            ' abs=' . $abs_path .
                            ' is_file=' . ( is_file( $abs_path ) ? '1' : '0' ) .
                            ' is_dir=' . ( is_dir( $abs_path ) ? '1' : '0' )
                        );
                    }

                    if ( is_file( $abs_path ) ) {
                        $all_files[] = $abs_path;
                    } elseif ( is_dir( $abs_path ) ) {
                        $files = DDD_CFC_Utils::get_files_recursively( $abs_path );
                        if ( self::$debug ) {
                            error_log( '[ddd-cfc] Directory ' . $abs_path . ' yielded ' . count( $files ) . ' files.' );
                        }
                        $all_files = array_merge( $all_files, $files );
                    }
                }

                $all_files = array_unique( $all_files );

                if ( self::$debug ) {
                    error_log( '[ddd-cfc] Total files to combine: ' . count( $all_files ) );
                }

                if ( empty( $all_files ) ) {
                    $error = 'No files found in the selected items.';
                } else {
                    $relative_paths = array_map(
                        array( 'DDD_CFC_Utils', 'strip_to_plugins_subpath' ),
                        $all_files
                    );

                    $header  = "Directory Structure:\n";
                    foreach ( $relative_paths as $rel_path ) {
                        $header .= $rel_path . "\n";
                    }
                    $header .= "\nTotal Files: " . count( $all_files ) . "\n\n";

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
                        $functions = array_unique( $matches[1] ?? array() );
                        $func_list = $functions ? implode( ', ', $functions ) : 'None';

                        $body .= str_repeat( '=', 10 ) . " [ $plugin_root ] $rel " . str_repeat( '=', 10 ) . "\n";
                        $body .= str_repeat( '=', 10 ) . " [ Total Lines in File: $line_count ] " . str_repeat( '=', 10 ) . "\n";
                        $body .= str_repeat( '=', 10 ) . " [ Functions: $func_list ] " . str_repeat( '=', 10 ) . "\n";
                        $body .= $file_contents . "\n";
                    }

                    $combined_full        = $header . $body;
                    $total_lines_combined = substr_count( $combined_full, "\n" ) + 1;

                    $combined_full = $header .
                        'Total Lines in Combined File: ' . $total_lines_combined . "\n\n" .
                        $body;
                }
            }
        }

        // === Render page shell ===============================================
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Combine File Contents', 'ddd-cfc' ); ?></h1>

            <form
                method="post"
                action="<?php echo esc_url( admin_url( 'admin.php?page=ddd-cfc-settings' ) ); ?>"
                id="cfc-v2-combine-form"
            >
                <?php wp_nonce_field( 'cfc_v2_form_action', 'cfc_v2_form_nonce' ); ?>

                <div id="cfc-directory-tree-container">
                    <?php
                    $view_selected_items = $selected_items;
                    include plugin_dir_path( __FILE__ ) . '../views/ddd-directory-tree.php';
                    ?>
                </div>

                <div id="cfc-buttons-container" style="margin-top:20px;">
                    <button
                        type="submit"
                        class="button button-primary"
                        id="cfc-combine-btn"
                        name="cfc_v2_submit"
                        value="combine"
                    >
                        <?php esc_html_e( 'Combine Files', 'ddd-cfc' ); ?>
                    </button>

                    <button type="button" class="button" id="cfc-clear-btn">
                        <?php esc_html_e( 'Clear Selections', 'ddd-cfc' ); ?>
                    </button>
                </div>
            </form>

            <?php if ( empty( $error ) && ! empty( $combined_full ) ) : ?>
                <form
                    method="post"
                    action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
                    id="cfc-v2-download-form"
                    style="margin-top:20px;"
                >
                    <?php wp_nonce_field( 'cfc_v2_form_action', 'cfc_v2_form_nonce' ); ?>
                    <input type="hidden" name="action" value="cfc_v2_download">
                    <?php
                    foreach ( $selected_items as $item ) {
                        echo '<input type="hidden" name="cfc_items[]" value="' . esc_attr( $item ) . '">';
                    }
                    ?>
                    <button type="submit" class="button" id="cfc-download-btn">
                        <?php esc_html_e( 'Download TXT', 'ddd-cfc' ); ?>
                    </button>
                </form>
            <?php endif; ?>

            <div id="cfc-preview-container" style="margin-top:20px;">
                <?php
                $view_error         = $error;
                $view_combined_full = $combined_full;
                include plugin_dir_path( __FILE__ ) . '../views/ddd-preview.php';
                ?>
            </div>
        </div>
        <?php
    }
}

DDD_CFC_Settings_Layout::init();

endif;
