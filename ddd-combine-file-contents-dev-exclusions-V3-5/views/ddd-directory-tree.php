<?php
/**
 * File Path: views/ddd-directory-tree.php
 * View: Directory Tree for Combine File Contents.
 *
 * Expects:
 *   - $view_selected_items (array): list of plugins-relative paths that came in via POST.
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $view_selected_items ) || ! is_array( $view_selected_items ) ) {
    $view_selected_items = array();
}

// How should the tree behave: realtime vs indexed.
$tree_source = get_option( 'cfc_tree_source', 'realtime' );

// In indexed mode, use the exclusion list to hide plugins from the tree.
$excluded_slugs = array();
if ( 'indexed' === $tree_source && class_exists( 'DDD_CFC_Exclusions' ) ) {
    $list = DDD_CFC_Exclusions::get_list();
    if ( is_array( $list ) ) {
        $excluded_slugs = $list;
    }
}

// Normal plugins root.
$root = defined( 'CFC_PLUGINS_BASE_DIR' ) ? CFC_PLUGINS_BASE_DIR : WP_CONTENT_DIR . '/plugins';
$items = @scandir( $root );

echo '<ul class="cfc-v2-tree">';

if ( $items ) {
    $folders = array();
    $files   = array();

    foreach ( $items as $item ) {
        if ( '.' === $item || '..' === $item ) {
            continue;
        }
        $path = $root . '/' . $item;
        if ( is_dir( $path ) ) {
            $folders[] = $item;
        } elseif ( is_file( $path ) ) {
            $files[] = $item;
        }
    }

    natcasesort( $folders );
    natcasesort( $files );

    // Top-level plugin folders.
    foreach ( $folders as $item ) {
        // In indexed mode, hide any plugin slugs that are in the exclusion list.
        if ( 'indexed' === $tree_source && in_array( $item, $excluded_slugs, true ) ) {
            continue;
        }

        $rel          = $item;
        $path         = $root . '/' . $item;
        $scan_result  = @scandir( $path );
        $has_children = $scan_result ? count( array_diff( $scan_result, array( '.', '..' ) ) ) > 0 : false;
        $checked      = in_array( $rel, $view_selected_items, true );

        echo '<li>';
        echo '<label>';
        echo '<input type="checkbox" class="cfc-v2-checkbox" name="cfc_items[]" value="' . esc_attr( $rel ) . '"' . checked( $checked, true, false ) . '>';
        echo '</label> ';
        echo '<span class="cfc-v2-folder-name" data-rel="' . esc_attr( $rel ) . '">' . esc_html( $item ) . '</span>';
        if ( $has_children ) {
            echo ' <span class="cfc-v2-expand-icon">▶</span><ul class="cfc-v2-tree" style="display:none;"></ul>';
        }
        echo '</li>';
    }

    // Any loose files directly under wp-content/plugins/.
    foreach ( $files as $item ) {
        $rel     = $item;
        $checked = in_array( $rel, $view_selected_items, true );
        echo '<li>';
        echo '<label>';
        echo '<input type="checkbox" class="cfc-v2-checkbox" name="cfc_items[]" value="' . esc_attr( $rel ) . '"' . checked( $checked, true, false ) . '>';
        echo '<span class="cfc-v2-file-name" data-rel="' . esc_attr( $rel ) . '">' . esc_html( $item ) . '</span>';
        echo '</label>';
        echo '</li>';
    }
} else {
    echo '<li>' . esc_html__( 'Unable to read plugins directory.', 'ddd-cfc' ) . '</li>';
}

// Optional MU-plugins section (global include flag).
$include_mu = get_option( 'cfc_include_mu_plugins', 'no' );

if ( 'yes' === $include_mu && defined( 'WPMU_PLUGIN_DIR' ) && is_dir( WPMU_PLUGIN_DIR ) ) {
    $mu_root  = WPMU_PLUGIN_DIR;
    $mu_items = @scandir( $mu_root );

    if ( $mu_items ) {
        $mu_folders = array();
        $mu_files   = array();

        foreach ( $mu_items as $item ) {
            if ( '.' === $item || '..' === $item ) {
                continue;
            }
            $path = $mu_root . '/' . $item;
            if ( is_dir( $path ) ) {
                $mu_folders[] = $item;
            } elseif ( is_file( $path ) ) {
                $mu_files[] = $item;
            }
        }

        natcasesort( $mu_folders );
        natcasesort( $mu_files );

        echo '<li><strong>' . esc_html__( 'MU Plugins (wp-content/mu-plugins)', 'ddd-cfc' ) . '</strong></li>';

        foreach ( $mu_folders as $item ) {
            $rel          = 'mu-plugins/' . $item;
            $path         = $mu_root . '/' . $item;
            $scan_result  = @scandir( $path );
            $has_children = $scan_result ? count( array_diff( $scan_result, array( '.', '..' ) ) ) > 0 : false;
            $checked      = in_array( $rel, $view_selected_items, true );

            echo '<li>';
            echo '<label>';
            echo '<input type="checkbox" class="cfc-v2-checkbox" name="cfc_items[]" value="' . esc_attr( $rel ) . '"' . checked( $checked, true, false ) . '>';
            echo '</label> ';
            echo '<span class="cfc-v2-folder-name" data-rel="' . esc_attr( $rel ) . '">' . esc_html( $item ) . '</span>';
            if ( $has_children ) {
                echo ' <span class="cfc-v2-expand-icon">▶</span><ul class="cfc-v2-tree" style="display:none;"></ul>';
            }
            echo '</li>';
        }

        foreach ( $mu_files as $item ) {
            $rel     = 'mu-plugins/' . $item;
            $checked = in_array( $rel, $view_selected_items, true );
            echo '<li>';
            echo '<label>';
            echo '<input type="checkbox" class="cfc-v2-checkbox" name="cfc_items[]" value="' . esc_attr( $rel ) . '"' . checked( $checked, true, false ) . '>';
            echo '<span class="cfc-v2-file-name" data-rel="' . esc_attr( $rel ) . '">' . esc_html( $item ) . '</span>';
            echo '</label>';
            echo '</li>';
        }
    }
}

echo '</ul>';
