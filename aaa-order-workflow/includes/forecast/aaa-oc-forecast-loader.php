<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/aaa-oc-forecast-loader.php
 * Purpose: Bootstraps the Forecast module for Order Workflow. Defines constants,
 *          loads helper classes, installs custom tables and registers queue
 *          processing hooks. Designed to coexist with the legacy forecaster
 *          without overwriting its keys. All new functionality lives under
 *          the forecast namespace and does not rename existing meta keys.
 * Version: 0.1.2
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Prevent double-loading this module.
if ( defined( 'AAA_OC_FORECAST_LOADER_READY' ) ) { return; }
define( 'AAA_OC_FORECAST_LOADER_READY', true );

// Local debug toggle for this loader.
if ( ! defined( 'AAA_OC_FORECAST_DEBUG' ) ) {
    define( 'AAA_OC_FORECAST_DEBUG', false );
}

// Define module version.
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

// Load required files.
require_once __DIR__ . '/helpers/class-aaa-oc-forecast-columns.php';
require_once __DIR__ . '/helpers/class-aaa-oc-forecast-meta-registry.php';
require_once __DIR__ . '/index/class-aaa-oc-forecast-table-installer.php';
require_once __DIR__ . '/index/class-aaa-oc-forecast-queue-installer.php';
require_once __DIR__ . '/index/class-aaa-oc-forecast-indexer.php';
require_once __DIR__ . '/index/class-aaa-oc-forecast-sales-metrics.php';
require_once __DIR__ . '/index/class-aaa-oc-forecast-stock.php';
require_once __DIR__ . '/index/class-aaa-oc-forecast-projections.php';
require_once __DIR__ . '/index/class-aaa-oc-forecast-status.php';
require_once __DIR__ . '/index/class-aaa-oc-forecast-timeline.php';
require_once __DIR__ . '/index/class-aaa-oc-forecast-overrides.php';
require_once __DIR__ . '/index/class-aaa-oc-forecast-runner.php';
require_once __DIR__ . '/index/class-aaa-oc-forecast-po-processor.php';
require_once __DIR__ . '/class-aaa-oc-forecast-queue.php';
require_once __DIR__ . '/helpers/class-aaa-oc-forecast-row-builder.php';
require_once __DIR__ . '/admin/class-aaa-oc-forecast-grid.php';
require_once __DIR__ . '/admin/class-aaa-oc-forecast-settings.php';
require_once __DIR__ . '/admin/class-aaa-oc-forecast-product-fields.php';
require_once __DIR__ . '/admin/class-aaa-oc-forecast-admin-actions.php';
require_once __DIR__ . '/admin/class-aaa-oc-forecast-product-list.php';
require_once __DIR__ . '/index/class-aaa-oc-forecast-nightly.php';

/**
 * On plugins_loaded we ensure tables exist and register hooks. This timing
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
    if ( class_exists( 'AAA_OC_Forecast_Indexer' ) ) {
        AAA_OC_Forecast_Indexer::init();
        // Run an initial index for existing products if table is empty.
        global $wpdb;
        $table = AAA_OC_FORECAST_INDEX_TABLE;
        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
        if ( $count === 0 ) {
            AAA_OC_Forecast_Indexer::index_all_products();
        }
    }

    /*
     * Fallback queue processing.  If there are pending forecast jobs and no
     * cron event is scheduled, process them immediately.  In some
     * environments (e.g. where WP Cron is disabled or insufficient traffic
     * triggers cron) scheduled events may never run.  This check runs on
     * every page load but only processes items when needed.
     */
    if ( class_exists( 'AAA_OC_Forecast_Queue' ) ) {
        global $wpdb;
        $pending = 0;
        $queue_table = AAA_OC_FORECAST_QUEUE_TABLE;
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $queue_table ) ) === $queue_table ) {
            $pending = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $queue_table WHERE status = 'pending'" );
        }
        if ( $pending > 0 && ! wp_next_scheduled( 'aaa_oc_process_forecast_queue' ) ) {
            // Schedule processing in the background if no cron is scheduled.
            AAA_OC_Forecast_Queue::schedule_process_queue( MINUTE_IN_SECONDS );
        }
    }

    if ( class_exists( 'AAA_OC_Forecast_Queue' ) ) {
        global $wpdb;
        $po_pending = 0;
        $po_table = AAA_OC_FORECAST_PO_QUEUE_TABLE;
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $po_table ) ) === $po_table ) {
            $po_pending = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $po_table WHERE status = 'pending'" );
        }
        if ( $po_pending > 0 && ! wp_next_scheduled( 'aaa_oc_process_po_queue' ) ) {
            AAA_OC_Forecast_Queue::schedule_po_run( MINUTE_IN_SECONDS );
        }
    }

    if ( class_exists( 'AAA_OC_Forecast_Queue' ) ) {
        AAA_OC_Forecast_Queue::init();
    }

    // Nightly enqueue (optional). Controlled by Forecast Settings tab.
    if ( class_exists( 'AAA_OC_Forecast_Nightly' ) ) {
        AAA_OC_Forecast_Nightly::init();
    }

    // Register admin grid and settings when in the dashboard.
    if ( is_admin() ) {
        if ( class_exists( 'AAA_OC_Forecast_Grid_Admin' ) ) {
            AAA_OC_Forecast_Grid_Admin::init();
        }
        if ( class_exists( 'AAA_OC_Forecast_Settings' ) ) {
            AAA_OC_Forecast_Settings::init();
        }
        // Register product meta fields UI.
        if ( class_exists( 'AAA_OC_Forecast_Product_Fields' ) ) {
            AAA_OC_Forecast_Product_Fields::init();
        }
        if ( class_exists( 'AAA_OC_Forecast_Admin_Actions' ) ) {
            AAA_OC_Forecast_Admin_Actions::init();
        }
        if ( class_exists( 'AAA_OC_Forecast_Product_List' ) ) {
            AAA_OC_Forecast_Product_List::init();
        }
    }
} );
