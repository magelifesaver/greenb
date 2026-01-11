<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forcast/aaa-oc-forcast-loader.php
 * Purpose: Bootstrap the Forecast module for the AAA Order Workflow plugin. This loader
 *          defines module constants, includes all required classes and wires
 *          WordPress actions to initialise the module. It mirrors the
 *          structure of other OWF module loaders (e.g., PayConfirm, ProductSearch)
 *          to maintain consistency.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Prevent multiple loads of this module. If defined, another loader has already run.
if ( defined( 'AAA_OC_FORCAST_LOADER_READY' ) ) {
    return;
}
define( 'AAA_OC_FORCAST_LOADER_READY', true );

// Module debug flag; set via wp-config.php if needed.
if ( ! defined( 'AAA_OC_FORCAST_DEBUG' ) ) {
    define( 'AAA_OC_FORCAST_DEBUG', false );
}

// Module version. Bump this when significant changes occur.
if ( ! defined( 'AAA_OC_FORCAST_VERSION' ) ) {
    define( 'AAA_OC_FORCAST_VERSION', '0.1.0' );
}

// Define the fully-qualified forecast table constant if not already present.
if ( ! defined( 'AAA_OC_PRODUCT_FORCAST_TABLE' ) ) {
    global $wpdb;
    define( 'AAA_OC_PRODUCT_FORCAST_TABLE', $wpdb->prefix . 'aaa_oc_product_forcast' );
}

// -----------------------------------------------------------------------------
// Includes
// -----------------------------------------------------------------------------

// Index and installer classes.
require_once __DIR__ . '/index/class-aaa-oc-product-forcast-table-installer.php';
require_once __DIR__ . '/index/class-aaa-oc-product-forcast-indexer.php';

// Core logic.
require_once __DIR__ . '/inc/class-aaa-oc-forcast.php';

// REST endpoints.
require_once __DIR__ . '/api/class-aaa-oc-forcast-rest.php';

// Helper functions.
require_once __DIR__ . '/helpers/class-aaa-oc-forcast-helper.php';

// AJAX handlers.
require_once __DIR__ . '/ajax/class-aaa-oc-forcast-ajax.php';

// Admin-only UI classes. These are conditionally loaded to avoid frontend bloat.
if ( is_admin() ) {
    $admin_dir = __DIR__ . '/admin';
    if ( file_exists( $admin_dir . '/class-aaa-oc-forcast-bulk.php' ) ) {
        require_once $admin_dir . '/class-aaa-oc-forcast-bulk.php';
    }
    if ( file_exists( $admin_dir . '/class-aaa-oc-forcast-menu.php' ) ) {
        require_once $admin_dir . '/class-aaa-oc-forcast-menu.php';
    }
}

// -----------------------------------------------------------------------------
// Initialisation
// -----------------------------------------------------------------------------

add_action( 'plugins_loaded', function () {
    // Initialise table installer: hooks admin_init/module_install.
    if ( class_exists( 'AAA_OC_Product_Forcast_Table_Installer' ) ) {
        AAA_OC_Product_Forcast_Table_Installer::init();
    }

    // Indexer: currently a stub. Only call init if the class implements it.
    if ( class_exists( 'AAA_OC_Product_Forcast_Indexer' ) && method_exists( 'AAA_OC_Product_Forcast_Indexer', 'init' ) ) {
        AAA_OC_Product_Forcast_Indexer::init();
    }

    // Core module initialisation.
    if ( class_exists( 'AAA_OC_Forcast' ) ) {
        AAA_OC_Forcast::init();
    }
    if ( class_exists( 'AAA_OC_Forcast_REST' ) ) {
        AAA_OC_Forcast_REST::init();
    }
    if ( class_exists( 'AAA_OC_Forcast_Helper' ) ) {
        AAA_OC_Forcast_Helper::init();
    }
    if ( class_exists( 'AAA_OC_Forcast_Ajax' ) ) {
        AAA_OC_Forcast_Ajax::init();
    }

    // Admin-specific initialisation.
    if ( is_admin() ) {
        if ( class_exists( 'AAA_OC_Forcast_Bulk' ) ) {
            AAA_OC_Forcast_Bulk::init();
        }
        if ( class_exists( 'AAA_OC_Forcast_Menu' ) ) {
            AAA_OC_Forcast_Menu::init();
        }
    }
} );