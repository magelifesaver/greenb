<?php
/**
 * File Path: /wp-content/plugins/aaa-geo-business-mapper/aaa-geo-business-mapper.php
 * Plugin Name: AAA Geo Business Mapper
 * Description: Admin map tool to scan Places (New) in a drawn area, keep multi-layer pins, and score best clusters.
 * Version: 1.1.0
 * Author: Webmaster Workflow
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
    AAA_GBM_Assets::init();
    AAA_GBM_Ajax::init();
    AAA_GBM_Admin::init();
} );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
    $url = admin_url( 'tools.php?page=aaa-gbm' );
    array_unshift( $links, '<a href="' . esc_url( $url ) . '">Settings</a>' );
    return $links;
} );
