<?php
/**
 * ProductSearch module bootstrap.
 *
 * Ensures installers, helpers, indexer and hooks are loaded. This
 * variant requires the row builder separately to keep files under
 * 150 lines and merges the DEV indexer improvements with the LIVE
 * stability. When the loader runs it defines version constants and
 * prevents double loading.
 *
 * Version: 1.1.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Prevent double loading.
if ( defined( 'AAA_OC_PRODUCTSEARCH_LOADER_READY' ) ) {
    return;
}
define( 'AAA_OC_PRODUCTSEARCH_LOADER_READY', true );

/** Debug/Version constants. */
if ( ! defined( 'AAA_OC_PRODUCTSEARCH_DEBUG' ) ) {
    define( 'AAA_OC_PRODUCTSEARCH_DEBUG', true );
}
if ( ! defined( 'AAA_OC_PRODUCTSEARCH_VERSION' ) ) {
    define( 'AAA_OC_PRODUCTSEARCH_VERSION', '1.1.3' );
}

/**
 * Includes: helpers, installers, indexers, row builder, search hooks.
 */
require_once __DIR__ . '/helpers/class-aaa-oc-productsearch-helpers.php';
require_once __DIR__ . '/index/class-aaa-oc-productsearch-table-installer.php';
require_once __DIR__ . '/index/class-aaa-oc-productsearch-row-builder.php';
require_once __DIR__ . '/index/class-aaa-oc-productsearch-table-indexer.php';
require_once __DIR__ . '/hooks/class-aaa-oc-productsearch-search-hooks.php';

/**
 * Optional logging helper for the loader itself.
 *
 * @param string $msg The message to log.
 */
function aaa_oc_productsearch_log( $msg ) {
    if ( defined( 'AAA_OC_PRODUCTSEARCH_DEBUG' ) && AAA_OC_PRODUCTSEARCH_DEBUG ) {
        if ( function_exists( 'aaa_oc_log' ) ) {
            aaa_oc_log( '[PRODUCTSEARCH][LOADER] ' . $msg );
        } else {
            error_log( '[PRODUCTSEARCH][LOADER] ' . $msg );
        }
    }
}
