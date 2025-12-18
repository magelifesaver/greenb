<?php
/**
 * Plugin Name: AAA Geo Business Mapper
 * Description: Admin map tool to scan Google Places (New) by type/text in a drawn area, keep multi‑layer pins, and score the densest clusters. Includes verbose logging and a reactive UI to aid troubleshooting.
 * Version: 1.1.0
 * Author: Webmaster Workflow
 *
 * This file is the main loader for the plugin. It defines constants, loads
 * all of the required classes and registers the settings link in the plugin
 * listing. The plugin is intentionally wide and thin—each class lives in
 * its own file to keep individual modules easy to read and test. Never
 * directly modify core WordPress files; instead hook into the appropriate
 * actions and filters defined here.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AAA_GBM_VER', '1.1.0' );
define( 'AAA_GBM_DIR', plugin_dir_path( __FILE__ ) );
define( 'AAA_GBM_URL', plugin_dir_url( __FILE__ ) );

require_once AAA_GBM_DIR . 'includes/class-aaa-gbm-logger.php';
require_once AAA_GBM_DIR . 'includes/assets/class-aaa-gbm-assets.php';
require_once AAA_GBM_DIR . 'includes/ajax/class-aaa-gbm-ajax.php';
require_once AAA_GBM_DIR . 'includes/admin/class-aaa-gbm-admin.php';

add_action( 'plugins_loaded', function () {
    // Initialise modules. Each module is responsible for its own hooks.
    AAA_GBM_Assets::init();
    AAA_GBM_Ajax::init();
    AAA_GBM_Admin::init();
} );

// Add settings link in the plugin listing so the page is easy to reach.
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
    $url = admin_url( 'tools.php?page=aaa-gbm' );
    array_unshift( $links, '<a href="' . esc_url( $url ) . '">Settings</a>' );
    return $links;
} );