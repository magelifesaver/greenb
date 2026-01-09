<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/aaa-oc-forecast-loader.php
 * Purpose: Bootstraps the Forecast module for Order Workflow. Defines constants,
 *          loads helper classes, installs custom tables and registers queue
 *          processing hooks. Designed to coexist with the legacy forecaster
 *          without overwriting its keys. All new functionality lives under
 *          the forecast namespace and does not rename existing meta keys.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Prevent double‑loading this module.
if ( defined( 'AAA_OC_FORECAST_LOADER_READY' ) ) { return; }
define( 'AAA_OC_FORECAST_LOADER_READY', true );

// Local debug toggle and version constant.
if ( ! defined( 'AAA_OC_FORECAST_DEBUG' ) ) {
    define( 'AAA_OC_FORECAST_DEBUG', false );
}
if ( ! defined( 'AAA_OC_FORECAST_VERSION' ) ) {
    define( 'AAA_OC_FORECAST_VERSION', '0.1.0' );
}

/* -------------------------------------------------------------------------
 * Table name constants
 *
 * These constants define the names of the custom tables used by the forecast
 * module. They are defined only if not already present to allow advanced
 * integrations to override them ahead of time. The tables are prefixed with
 * the blog prefix for multisite compatibility.
 */
if ( ! defined( 'AAA_OC_FORECAST_INDEX_TABLE' ) ) {
    global $wpdb;
    define( 'AAA_OC_FORECAST_INDEX_TABLE', $wpdb->prefix . 'aaa_oc_product_forecast' );
}
if ( ! defined( 'AAA_OC_FORECAST_QUEUE_TABLE' ) ) {
    global $wpdb;
    define( 'AAA_OC_FORECAST_QUEUE_TABLE', $wpdb->prefix . 'aaa_oc_forecast_queue' );
}
if ( ! defined( 'AAA_OC_FORECAST_PO_QUEUE_TABLE' ) ) {
    global $wpdb;
    define( 'AAA_OC_FORECAST_PO_QUEUE_TABLE', $wpdb->prefix . 'aaa_oc_po_queue' );
}

/* -------------------------------------------------------------------------
 * Includes
 *
 * Load helper classes and installers. These files are kept small (<150 lines)
 * and grouped by responsibility: helpers for column definitions, installers
 * for the index and queue tables, queue logic and the admin grid.
 */
require_once __DIR__ . '/helpers/class-aaa-oc-forecast-columns.php';
require_once __DIR__ . '/index/class-aaa-oc-forecast-table-installer.php';
require_once __DIR__ . '/index/class-aaa-oc-forecast-queue-installer.php';
require_once __DIR__ . '/index/class-aaa-oc-forecast-indexer.php';
require_once __DIR__ . '/class-aaa-oc-forecast-queue.php';
require_once __DIR__ . '/helpers/class-aaa-oc-forecast-row-builder.php';
require_once __DIR__ . '/admin/class-aaa-oc-forecast-grid.php';
require_once __DIR__ . '/admin/class-aaa-oc-forecast-settings.php';

/* -------------------------------------------------------------------------
 * Module initialization
 *
 * On the plugins_loaded hook we kick off table installation, queue setup
 * and admin UI registration. This runs after WooCommerce is available and
 * ensures our tables exist before any indexing or queueing occurs.
 */
add_action( 'plugins_loaded', function () {
    // Optionally log boot messages.
    if ( AAA_OC_FORECAST_DEBUG ) {
        error_log( '[Forecast][Loader] Initialising module v' . AAA_OC_FORECAST_VERSION );
    }
    // Ensure the index and queue tables exist on every load. Do not hook into plugins_loaded again,
    // because this callback runs after plugins_loaded has already fired.
    if ( class_exists( 'AAA_OC_Forecast_Table_Installer' ) ) {
        AAA_OC_Forecast_Table_Installer::maybe_install_table();
    }
    if ( class_exists( 'AAA_OC_Forecast_Queue_Installer' ) ) {
        AAA_OC_Forecast_Queue_Installer::maybe_install_tables();
    }
    // Initialise the queue processing and indexer hooks.
    if ( class_exists( 'AAA_OC_Forecast_Queue' ) ) {
        AAA_OC_Forecast_Queue::init();
    }
    if ( class_exists( 'AAA_OC_Forecast_Indexer' ) ) {
        AAA_OC_Forecast_Indexer::init();
        // If the index table is empty, perform a one‑time initial index of all products.
        global $wpdb;
        if ( (int) $wpdb->get_var( "SHOW TABLES LIKE '" . $wpdb->esc_like( AAA_OC_FORECAST_INDEX_TABLE ) . "'" ) ) {
            $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . AAA_OC_FORECAST_INDEX_TABLE );
            if ( $count === 0 ) {
                AAA_OC_Forecast_Indexer::index_all_products();
            }
        }
    }
    // Register admin grid and settings when in the dashboard.
    if ( is_admin() ) {
        if ( class_exists( 'AAA_OC_Forecast_Grid_Admin' ) ) {
            AAA_OC_Forecast_Grid_Admin::init();
        }
        if ( class_exists( 'AAA_OC_Forecast_Settings' ) ) {
            AAA_OC_Forecast_Settings::init();
        }
    }
} );
