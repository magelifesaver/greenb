<?php
/**
 * Plugin Name: AAA WF PWF – WM NEW Product Importer(UPDATE)(live)V1
 * Plugin URI:  /wp-admin/admin.php?page=wf-pwf-import
 * Description: Imports Weedmaps CSV products into WooCommerce (matching by SKU or custom ID) and indexes all fields in a single table.
 * Version:     1.0.0
 * Author:      WebMaster
 * Author URI:  https://example.com
 * Text Domain: aaa-wf-pwf
 * Domain Path: /languages
 *
 * File Path: aaa-wf-pwf.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// ------------------------------------------------------------------------------------------------
// Define plugin constants
// ------------------------------------------------------------------------------------------------

define( 'AAA_WF_PWF_VERSION', '1.0.0' );
define( 'AAA_WF_PWF_PATH', plugin_dir_path( __FILE__ ) );
define( 'AAA_WF_PWF_URL',  plugin_dir_url( __FILE__ ) );

// ------------------------------------------------------------------------------------------------
// Include core class files
// ------------------------------------------------------------------------------------------------

require_once AAA_WF_PWF_PATH . 'includes/modules/pwf/class-aaa-wf-pwf-loader.php';
require_once AAA_WF_PWF_PATH . 'includes/modules/pwf/class-aaa-wf-pwf-index.php';
require_once AAA_WF_PWF_PATH . 'includes/modules/pwf/class-aaa-wf-pwf-importer.php';

// ------------------------------------------------------------------------------------------------
// Register activation hook (create database table)
// ------------------------------------------------------------------------------------------------

register_activation_hook( __FILE__, [ 'AAA_WF_PWF_Loader', 'activate' ] );

// ------------------------------------------------------------------------------------------------
// Hook into plugins_loaded to initialize everything
// ------------------------------------------------------------------------------------------------

add_action( 'plugins_loaded', [ 'AAA_WF_PWF_Loader', 'init' ] );
