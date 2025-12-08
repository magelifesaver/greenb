<?php
/**
 * File: ajax/ajax-ddd-cfc-fetch-children.php
 * Description: AJAX handler to return immediate child folders and files under a given
 *              plugins-relative or MU-plugins-relative path, with folders first then files.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register AJAX hooks.
 */
add_action( 'wp_ajax_ddd_cfc_fetch_children', 'ddd_cfc_fetch_children_callback' );

/**
 * Callback: Fetch immediate subfolders and files of a given relative directory.
 *
 * Accepts a "parent" POST parameter such as:
 *   - "my-plugin"
 *   - "my-plugin/includes"
 *   - "mu-plugins/my-mu-plugin"
 */
function ddd_cfc_fetch_children_callback() {
    // Permissions check.
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Insufficient permissions.' );
    }

    // Grab parent path from POST.
    $parent = isset( $_POST['parent'] ) ? sanitize_text_field( wp_unslash( $_POST['parent'] ) ) : '';
    if ( '' === $parent ) {
        wp_send_json_error( 'No parent specified.' );
    }

    // Determine base dir: normal plugins vs MU-plugins.
    if ( 0 === strpos( $parent, 'mu-plugins/' ) ) {
        $sub      = substr( $parent, strlen( 'mu-plugins/' ) );
        $base_dir = defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : WP_CONTENT_DIR . '/mu-plugins';
        $full_path = trailingslashit( $base_dir ) . $sub;
    } else {
        $base_dir  = defined( 'CFC_PLUGINS_BASE_DIR' ) ? CFC_PLUGINS_BASE_DIR : WP_CONTENT_DIR . '/plugins';
        $full_path = trailingslashit( $base_dir ) . $parent;
    }

    if ( ! is_dir( $full_path ) ) {
        wp_send_json_error( 'Directory does not exist: ' . esc_html( $parent ) );
    }

    $folders = [];
    $files   = [];

    $items = @scandir( $full_path );
    if ( ! $items ) {
        wp_send_json_error( 'Unable to scan directory: ' . esc_html( $parent ) );
    }

    foreach ( $items as $item ) {
        if ( in_array( $item, [ '.', '..' ], true ) ) {
            continue;
        }

        $child_full = $full_path . '/' . $item;

        if ( is_dir( $child_full ) ) {
            // Determine if this subfolder has any child folders or files.
            $has_children = false;
            $sub_items    = @scandir( $child_full );
            if ( $sub_items ) {
                foreach ( $sub_items as $sub_item ) {
                    if ( in_array( $sub_item, [ '.', '..' ], true ) ) {
                        continue;
                    }
                    if ( is_dir( $child_full . '/' . $sub_item ) || is_file( $child_full . '/' . $sub_item ) ) {
                        $has_children = true;
                        break;
                    }
                }
            }
            $folders[] = [
                'type'        => 'folder',
                'name'        => $item,
                'hasChildren' => $has_children,
            ];
        } elseif ( is_file( $child_full ) ) {
            $files[] = [
                'type' => 'file',
                'name' => $item,
            ];
        }
    }

    // Sort folders and files (natural case-insensitive).
    usort(
        $folders,
        static function ( $a, $b ) {
            return strnatcasecmp( $a['name'], $b['name'] );
        }
    );
    usort(
        $files,
        static function ( $a, $b ) {
            return strnatcasecmp( $a['name'], $b['name'] );
        }
    );

    wp_send_json_success( array_merge( $folders, $files ) );
}
