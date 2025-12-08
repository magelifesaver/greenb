<?php
/**
 * Plugin Name: DDD Combine File Contents (Directory Tree) V3.5 (live)
 * Plugin URI:  /wp-admin/admin.php?page=ddd-cfc-settings
 * Description: Recursively combine and preview/download contents of selected files and folders under the plugins directory.
 * Version:     3.5
 * Author:      WebMaster
 * License:     GPL2
 */

defined( 'ABSPATH' ) || exit;

// Ensure we know where WP’s plugins directory lives
if ( ! defined( 'CFC_PLUGINS_BASE_DIR' ) ) {
    define( 'CFC_PLUGINS_BASE_DIR', WP_CONTENT_DIR . '/plugins' );
}

// Define our own plugin file constant for activation hooks
if ( ! defined( 'DDD_CFC_PLUGIN_FILE' ) ) {
    define( 'DDD_CFC_PLUGIN_FILE', __FILE__ );
}

// Include Utility Class (recursive file scanner, path helper)
require_once plugin_dir_path( __FILE__ ) . 'includes/class-ddd-cfc-utils.php';

require_once plugin_dir_path( __FILE__ ) . 'includes/class-ddd-cfc-exclusions.php';

// Load core classes and assets loader
require_once plugin_dir_path( __FILE__ ) . 'assets/aaa-cfc-assets-loader.php';

// 1) Table installer
require_once plugin_dir_path( __FILE__ ) . 'includes/class-ddd-cfc-table-installer.php';
DDD_CFC_Table_Installer::init();

// Include Settings Layout (registers menu, renders the directory‐tree shell, buttons, preview area)
// Note: this class no longer enqueues assets, so we’ll enqueue in this main file.
require_once plugin_dir_path( __FILE__ ) . 'admin/class-ddd-cfc-settings-layout.php';

// 3) Load & init the indexer
require_once plugin_dir_path( __FILE__ ) . 'includes/class-ddd-cfc-indexer.php';

// 3) Live-Search Index page
require_once plugin_dir_path( __FILE__ ) . 'admin/class-ddd-cfc-index-page.php';
DDD_CFC_Index_Page::init();

// 4) REST API endpoints
require_once plugin_dir_path( __FILE__ ) . 'includes/class-ddd-cfc-rest-controller.php';
DDD_CFC_REST_Controller::init();

// Include Download Handler (handles admin_post_cfc_v2_download → streams combined TXT)
require_once plugin_dir_path( __FILE__ ) . 'includes/class-ddd-cfc-download-handler.php';

// Include AJAX Fetcher (handles wp_ajax_ddd_cfc_fetch_children → returns child folders/files)
require_once plugin_dir_path( __FILE__ ) . 'ajax/ajax-ddd-cfc-fetch-children.php';

// Initialize the settings layout (registers menu and renders page shell)
DDD_CFC_Settings_Layout::init();

/**
 * Add “Settings” and “Combine Now” links to the plugin row on Plugins screen.
 */
add_filter(
    'plugin_action_links_' . plugin_basename( __FILE__ ),
    'ddd_cfc_plugin_action_links'
);
function ddd_cfc_plugin_action_links( $links ) {
    // Settings page link
    $settings_url = esc_url( admin_url( 'admin.php?page=cfc-live-search-index' ) );
    $links[] = '<a href="' . $settings_url . '">Settings</a>';

    // Combine Now page link
    $combine_url = esc_url( admin_url( 'admin.php?page=ddd-cfc-settings' ) );
    $links[] = '<a href="' . $combine_url . '">Combine Now</a>';

    return $links;
}
