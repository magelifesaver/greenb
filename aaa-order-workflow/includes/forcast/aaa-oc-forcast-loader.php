<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forcast/aaa-oc-forcast-loader.php
 * Purpose: Loads the Forcast module (indexers, API, AJAX, helpers) for the Order Workflow plugin.
 * Version: 0.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Guard so an older/duplicate loader can’t re-run.
 */
if ( defined( 'AAA_OC_FORCAST_LOADER_READY' ) ) { return; }
define( 'AAA_OC_FORCAST_LOADER_READY', true );

/**
 * Module debug and version constants.
 */
if ( ! defined( 'AAA_OC_FORCAST_DEBUG' ) )   define( 'AAA_OC_FORCAST_DEBUG', false );
if ( ! defined( 'AAA_OC_FORCAST_VERSION' ) ) define( 'AAA_OC_FORCAST_VERSION', '0.1.0' );

/**
 * Define the product forcast table name constant if not already defined.
 */
if ( ! defined( 'AAA_OC_PRODUCT_FORCAST_TABLE' ) ) {
    global $wpdb;
    define( 'AAA_OC_PRODUCT_FORCAST_TABLE', $wpdb->prefix . 'aaa_oc_product_forcast' );
}

/**
 * Include index, API, helper, AJAX and core classes.
 */
require_once __DIR__ . '/index/class-aaa-oc-product-forcast-table-installer.php';
require_once __DIR__ . '/index/class-aaa-oc-product-forcast-indexer.php';
require_once __DIR__ . '/inc/class-aaa-oc-forcast.php';
require_once __DIR__ . '/api/class-aaa-oc-forcast-rest.php';
require_once __DIR__ . '/helpers/class-aaa-oc-forcast-helper.php';
require_once __DIR__ . '/ajax/class-aaa-oc-forcast-ajax.php';

/**
 * Admin-specific classes can be required here when needed.
 */
if ( is_admin() ) {
    // Placeholder for future admin-only includes.
}

/**
 * Init after all classes are present.
 */
add_action( 'plugins_loaded', function () {
    if ( AAA_OC_FORCAST_DEBUG ) {
        error_log( '[Forcast][Loader] Booting v' . AAA_OC_FORCAST_VERSION );
    }
    // Initialize core classes.
    AAA_OC_Product_Forcast_Table_Installer::init();
    AAA_OC_Product_Forcast_Indexer::init();
    AAA_OC_Forcast::init();
    AAA_OC_Forcast_REST::init();
    AAA_OC_Forcast_Helper::init();
    AAA_OC_Forcast_Ajax::init();
} );