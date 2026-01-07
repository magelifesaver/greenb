<?php
/**
 * File: includes/productforecast/aaa-oc-productforecast-loader.php
 * Purpose: ProductForecast module bootstrap — loads core and legacy forecasting
 * classes, registers admin pages, and hooks into WordPress.  This loader
 * consolidates the legacy forecasting functionality with the new custom index
 * table and grid.
 *
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Prevent double loading.
if ( defined( 'AAA_OC_PRODUCTFORECAST_LOADER_READY' ) ) {
    return;
}
define( 'AAA_OC_PRODUCTFORECAST_LOADER_READY', true );

// Allow debug override via wp-config.php.
if ( ! defined( 'AAA_OC_PRODUCTFORECAST_DEBUG' ) ) {
    define( 'AAA_OC_PRODUCTFORECAST_DEBUG', false );
}

$BASE = __DIR__;

// Prefer Workflow's shared loader util if present (standalone safe fallback).
$util = dirname( $BASE, 2 ) . '/core/options/helpers/class-aaa-oc-loader-util.php';
if ( ! class_exists( 'AAA_OC_Loader_Util' ) && file_exists( $util ) ) {
    require_once $util;
}

/**
 * Conditionally require a file.  Uses the shared Loader Util when available
 * to log missing files and avoid hard failures.  Falls back to a simple
 * require_once otherwise.
 *
 * @param string $file Absolute path to require.
 */
$req = function( $file ) {
    if ( class_exists( 'AAA_OC_Loader_Util' ) ) {
        AAA_OC_Loader_Util::require_or_log( $file, false, 'productforecast' );
        return;
    }
    if ( file_exists( $file ) ) {
        require_once $file;
        return;
    }
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[AAA_OC_PRODUCTFORECAST] Missing file: ' . $file );
    }
};

// Load core module files.
$req( $BASE . '/helpers/class-aaa-oc-productforecast-helpers.php' );
$req( $BASE . '/index/class-aaa-oc-productforecast-table-installer.php' );
$req( $BASE . '/index/class-aaa-oc-productforecast-row-builder.php' );
$req( $BASE . '/index/class-aaa-oc-productforecast-table-indexer.php' );
$req( $BASE . '/index/aaa-oc-productforecast-declarations.php' );
$req( $BASE . '/hooks/class-aaa-oc-productforecast-hooks-products.php' );

// Load settings class from legacy plugin to manage global options.
$req( $BASE . '/admin/tabs/class-wf-sfwf-settings.php' );

// Load legacy forecasting classes.  These preserve the WF_SFWF_ prefix for
// backwards compatibility and provide forecasting calculations, runners,
// overrides, bulk actions, flag handlers, etc.
$legacy_files = [
    '/index/class-wf-sfwf-forecast-projections.php',
    '/index/class-wf-sfwf-forecast-product-fields.php',
    '/index/class-wf-sfwf-forecast-overrides.php',
    '/index/class-wf-sfwf-forecast-meta-updater.php',
    '/index/class-wf-sfwf-forecast-index.php',
    '/index/class-wf-sfwf-forecast-index-table.php',
    '/index/class-wf-sfwf-forecast-timeline.php',
    '/index/class-wf-sfwf-forecast-stock.php',
    '/index/class-wf-sfwf-forecast-status.php',
    '/index/class-wf-sfwf-forecast-scheduler.php',
    '/index/class-wf-sfwf-forecast-sales-metrics.php',
    '/index/class-wf-sfwf-forecast-runner.php',
    '/index/class-wf-sfwf-flag-handler.php',
    '/index/class-wf-sfwf-forecast-bulk-actions.php',
    '/index/class-wf-sfwf-forecast-selected-handler.php',
    '/index/class-wf-sfwf-purchase-order-handler.php',
    '/index/class-wf-sfwf-forecast-product-fields.php'
];
foreach ( $legacy_files as $rel ) {
    $req( $BASE . $rel );
}

// Admin menus: register settings page and forecast grid.
add_action( 'admin_menu', function() use ( $BASE ) {
    // Settings / index maintenance page.
    add_submenu_page(
        'woocommerce',
        __( 'Product Forecast Settings', 'aaa-oc-productforecast' ),
        __( 'Forecast Settings', 'aaa-oc-productforecast' ),
        'manage_woocommerce',
        'aaa-oc-productforecast',
        function() use ( $BASE ) {
            $tab = $BASE . '/admin/tabs/aaa-oc-productforecast.php';
            if ( file_exists( $tab ) ) {
                require $tab;
            } else {
                echo '<div class="notice notice-error"><p>Missing settings tab file.</p></div>';
            }
        }
    );

    // Forecast grid page.
    add_submenu_page(
        'woocommerce',
        __( 'Product Forecast Grid', 'aaa-oc-productforecast' ),
        __( 'Forecast Grid', 'aaa-oc-productforecast' ),
        'manage_woocommerce',
        'aaa-oc-productforecast-grid',
        function() use ( $BASE ) {
            $grid = $BASE . '/admin/tabs/aaa-oc-productforecast-grid.php';
            if ( file_exists( $grid ) ) {
                require $grid;
            } else {
                echo '<div class="notice notice-error"><p>Missing grid file.</p></div>';
            }
        }
    );
}, 30 );

// Ensure tables exist on admin init.
add_action( 'admin_init', function() {
    if ( class_exists( 'AAA_OC_ProductForecast_Table_Installer' ) ) {
        AAA_OC_ProductForecast_Table_Installer::maybe_install();
    }
}, 5 );

// Boot hooks and handlers once plugins are loaded.
add_action( 'plugins_loaded', function() {
    // Boot product meta fields (lead time, min order, flags, etc.)
    if ( class_exists( 'AAA_OC_ProductForecast_Hooks_Products' ) ) {
        AAA_OC_ProductForecast_Hooks_Products::boot();
    }
    // Legacy product fields for additional forecasting fields.
    if ( class_exists( 'WF_SFWF_Product_Fields' ) ) {
        WF_SFWF_Product_Fields::init();
    }
    // Legacy minimum stock buffer field.
    if ( class_exists( 'WF_SFWF_Forecast_Product_Fields' ) ) {
        WF_SFWF_Forecast_Product_Fields::init();
    }
    // Boot flag handler to toggle flags via AJAX.
    if ( class_exists( 'WF_SFWF_Flag_Handler' ) ) {
        WF_SFWF_Flag_Handler::init();
    }
    // Bulk actions for products list.
    if ( class_exists( 'WF_SFWF_Forecast_Bulk_Actions' ) ) {
        WF_SFWF_Forecast_Bulk_Actions::init();
    }
    // Selected handler for grid selections.
    if ( class_exists( 'WF_SFWF_Forecast_Selected_Handler' ) ) {
        WF_SFWF_Forecast_Selected_Handler::init();
    }
    // Purchase order handler.
    if ( class_exists( 'WF_SFWF_Purchase_Order_Handler' ) ) {
        WF_SFWF_Purchase_Order_Handler::init();
    }
}, 20 );
