<?php
/**
 * File: /wp-content/plugins/aaa_oc_product_forecast_index/includes/productforecast/aaa-oc-productforecast-loader.php
 * Purpose: ProductForecast Index module bootstrap — installer, indexer hooks, admin tab.
 * Version: 1.0.0
 *
 * Module Structure Reference:
 * - Loader + Assets Loader (only)
 * - index/ (table-installer, table-indexer, row-builder, declarations)
 * - helpers/, hooks/, admin/tabs/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'AAA_OC_PRODUCTFORECAST_LOADER_READY' ) ) {
    return;
}
define( 'AAA_OC_PRODUCTFORECAST_LOADER_READY', true );

if ( ! defined( 'AAA_OC_PRODUCTFORECAST_DEBUG' ) ) {
    // Can be overridden via wp-config.php.
    define( 'AAA_OC_PRODUCTFORECAST_DEBUG', false );
}

$BASE = __DIR__;

// Prefer Workflow's shared loader util if present (standalone safe fallback).
$util = dirname( $BASE, 2 ) . '/core/options/helpers/class-aaa-oc-loader-util.php';
if ( ! class_exists( 'AAA_OC_Loader_Util' ) && file_exists( $util ) ) {
    require_once $util;
}

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

// Core module files.
$req( $BASE . '/helpers/class-aaa-oc-productforecast-helpers.php' );
$req( $BASE . '/index/class-aaa-oc-productforecast-table-installer.php' );
$req( $BASE . '/index/class-aaa-oc-productforecast-row-builder.php' );
$req( $BASE . '/index/class-aaa-oc-productforecast-table-indexer.php' );
$req( $BASE . '/index/aaa-oc-productforecast-declarations.php' );
$req( $BASE . '/hooks/class-aaa-oc-productforecast-hooks-products.php' );

// Admin tab registration (intended for AAA Order Workflow Settings page; standalone also adds submenu).
add_action( 'admin_menu', function() use ( $BASE ) {
    // Standalone submenu under WooCommerce for quick access.
    add_submenu_page(
        'woocommerce',
        'Product Forecast Index',
        'Product Forecast Index',
        'manage_woocommerce',
        'aaa-oc-productforecast',
        function() use ( $BASE ) {
            $tab = $BASE . '/admin/tabs/aaa-oc-productforecast.php';
            if ( file_exists( $tab ) ) {
                require $tab;
            } else {
                echo '<div class="notice notice-error"><p>Missing admin tab file.</p></div>';
            }
        }
    );
}, 30 );

// Ensure tables exist on admin_init (idempotent).
add_action( 'admin_init', function() {
    if ( class_exists( 'AAA_OC_ProductForecast_Table_Installer' ) ) {
        AAA_OC_ProductForecast_Table_Installer::maybe_install();
    }
}, 5 );

// Boot hooks.
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'AAA_OC_ProductForecast_Hooks_Products' ) ) {
        AAA_OC_ProductForecast_Hooks_Products::boot();
    }
}, 12 );
