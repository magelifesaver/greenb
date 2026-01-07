<?php
/**
 * Plugin Name: AAA OC ProductForecast Index
 * Description: Combines the stock forecast functionality with custom index table and grid.
 * Version: 1.1.0
 * Author: AI Generated
 * File: /wp-content/plugins/aaa_oc_product_forecast_index/aaa-oc-productforecast-index.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Define constants for plugin directories
if ( ! defined( 'AAA_OC_PFI_PLUGIN_FILE' ) ) {
    define( 'AAA_OC_PFI_PLUGIN_FILE', __FILE__ );
    define( 'AAA_OC_PFI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
    define( 'AAA_OC_PFI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    define( 'AAA_OC_PFI_VERSION', '1.0.0' );
}

// Load module loader and assets loader
require_once AAA_OC_PFI_PLUGIN_DIR . 'includes/productforecast/aaa-oc-productforecast-loader.php';
require_once AAA_OC_PFI_PLUGIN_DIR . 'includes/productforecast/aaa-oc-productforecast-assets-loader.php';

