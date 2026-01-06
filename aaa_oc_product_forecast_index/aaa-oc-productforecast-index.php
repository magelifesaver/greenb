<?php
/**
 * Plugin Name: AAA OC ProductForecast Index
 * Description: Product forecast indexing module (standalone). Creates typed index tables and keeps them synced from product forecast meta for fast filtering/sorting.
 * Version: 1.0.0
 * Author: Webmaster Workflow
 * Text Domain: aaa-oc-productforecast
 *
 * File: /wp-content/plugins/aaa_oc_product_forecast_index/aaa-oc-productforecast-index.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Prevent double-load.
if ( defined( 'AAA_OC_PRODUCTFORECAST_INDEX_PLUGIN_READY' ) ) {
    return;
}
define( 'AAA_OC_PRODUCTFORECAST_INDEX_PLUGIN_READY', true );

define( 'AAA_OC_PF_VERSION', '1.0.0' );
define( 'AAA_OC_PF_PLUGIN_FILE', __FILE__ );
define( 'AAA_OC_PF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AAA_OC_PF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'AAA_OC_PF_MODULE_DIR', AAA_OC_PF_PLUGIN_DIR . 'includes/productforecast/' );

/**
 * Loader files only (module pattern).
 * Mirrors AAA Order Workflow module structure: one loader + one assets loader.
 */
require_once AAA_OC_PF_MODULE_DIR . 'aaa-oc-productforecast-loader.php';
require_once AAA_OC_PF_MODULE_DIR . 'aaa-oc-productforecast-assets-loader.php';
