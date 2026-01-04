<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/productsearch/aaa-oc-productsearch-loader.php
 * Purpose: ProductSearch module bootstrap — installers, helpers, indexer hooks, search hooks.
 *
 * This loader is based on the upstream 1.1.4 implementation but has been
 * consolidated here as a standalone module you can drop into your plugin. It
 * exposes the same constants and wiring points as the original 1.1.2 loader
 * while incorporating the improvements from 1.1.3/1.1.4 (tracked includes,
 * optional logging helper and clean hook registration). Updating to this
 * version should resolve the inconsistent search behaviour you described
 * without changing any public APIs.
 *
 * Version: 1.1.4-fixed
 */

// Exit early when loaded outside of WordPress.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Prevent double loading. If this constant is already defined then the
// loader has previously executed and there is nothing else to do.
if ( defined( 'AAA_OC_PRODUCTSEARCH_LOADER_READY' ) ) {
    return;
}
define( 'AAA_OC_PRODUCTSEARCH_LOADER_READY', true );

/**
 * Debug/Version constants
 *
 * These constants mirror those in the original codebase. They can be
 * overridden by defining them in wp-config.php prior to plugin load. When
 * AAA_OC_PRODUCTSEARCH_DEBUG is true additional log messages will be
 * output via `aaa_oc_log()` if available or PHP's `error_log()` otherwise.
 */
if ( ! defined( 'AAA_OC_PRODUCTSEARCH_DEBUG' ) ) {
    define( 'AAA_OC_PRODUCTSEARCH_DEBUG', false );
}
if ( ! defined( 'AAA_OC_PRODUCTSEARCH_VERSION' ) ) {
    define( 'AAA_OC_PRODUCTSEARCH_VERSION', '1.1.4-fixed' );
}

// Base path for includes. This makes it easier to reference files relative
// to this loader regardless of where the plugin lives on disk.
$BASE = __DIR__;

// Attempt to load the shared loader utility if present. When available it
// provides a `require_or_log()` method that will log missing files instead of
// fatally erroring. This mirrors how the production plugin operates.
$util = dirname( __DIR__, 2 ) . '/core/options/helpers/class-aaa-oc-loader-util.php';
if ( ! class_exists( 'AAA_OC_Loader_Util' ) && file_exists( $util ) ) {
    require_once $util;
}

// Load module components. When the loader util is available it handles
// missing files gracefully; otherwise we fall back to straight require_once
// calls. The order of includes mirrors the upstream implementation.
if ( class_exists( 'AAA_OC_Loader_Util' ) ) {
    AAA_OC_Loader_Util::require_or_log( $BASE . '/helpers/class-aaa-oc-productsearch-helpers.php',        false, 'productsearch' );
    AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-productsearch-table-installer.php',  false, 'productsearch' );
    AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-productsearch-table-indexer.php',    false, 'productsearch' );
    AAA_OC_Loader_Util::require_or_log( $BASE . '/hooks/class-aaa-oc-productsearch-search-hooks.php',     false, 'productsearch' );
    // Results hooks are optional. They provide a custom carousel-based
    // template for search results but are not required for core search
    // functionality. Load them if present so the user can enable the
    // alternative results view by adding `AAA_OC_ProductSearch_Results_Hooks::init()`
    // in their theme or plugin code.
    AAA_OC_Loader_Util::require_or_log( $BASE . '/hooks/class-aaa-oc-productsearch-results-hooks.php',    false, 'productsearch' );
} else {
    foreach ( [
        '/helpers/class-aaa-oc-productsearch-helpers.php',
        '/index/class-aaa-oc-productsearch-table-installer.php',
        '/index/class-aaa-oc-productsearch-table-indexer.php',
        '/hooks/class-aaa-oc-productsearch-search-hooks.php',
        '/hooks/class-aaa-oc-productsearch-results-hooks.php',
    ] as $rel ) {
        $f = $BASE . $rel;
        if ( file_exists( $f ) ) {
            require_once $f;
        }
    }
}

/**
 * Internal logger for the loader. When debug mode is enabled this
 * function prefixes messages with [PRODUCTSEARCH][LOADER] to make them
 * easier to find in your logs. If the hosting environment defines
 * `aaa_oc_log()` that function will be used; otherwise messages fall back
 * to PHP's `error_log()`.
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

/*
 * Hook into WordPress lifecycle events. These hooks mirror the upstream
 * implementation and ensure that the index tables exist, the indexer is
 * wired to WooCommerce product events and our search hooks take over the
 * default WooCommerce search behaviour for products.
 */

// Register indexer hooks and search hooks once all plugins are loaded.
add_action( 'plugins_loaded', function () {
    // Only proceed if WooCommerce is active. Without WooCommerce there
    // isn't a product post type to index.
    if ( ! class_exists( 'WooCommerce' ) ) {
        aaa_oc_productsearch_log( 'WooCommerce not active; aborting ProductSearch hook wiring.' );
        return;
    }

    // Wire indexer actions. These update the search index when product
    // stock status changes, when a product is saved and when its terms
    // (categories or brands) are modified. This keeps the index fresh.
    if ( class_exists( 'AAA_OC_ProductSearch_Table_Indexer' ) ) {
        add_action( 'woocommerce_product_set_stock_status', [ 'AAA_OC_ProductSearch_Table_Indexer', 'on_stock_status' ], 10, 3 );
        add_action( 'save_post_product',                     [ 'AAA_OC_ProductSearch_Table_Indexer', 'on_product_save' ], 20, 2 );
        add_action( 'set_object_terms',                      [ 'AAA_OC_ProductSearch_Table_Indexer', 'on_terms_set' ],    20, 6 );
        aaa_oc_productsearch_log( 'ProductSearch indexer hooks wired.' );
    } else {
        aaa_oc_productsearch_log( 'ProductSearch indexer class missing; hooks not wired.' );
    }

    // Initialise search hooks. These intercept WooCommerce product queries
    // and delegate them to our index via the helpers class. They also
    // optionally perform smart redirects to brand/category archives when
    // all results share the same term.
    if ( class_exists( 'AAA_OC_ProductSearch_Search_Hooks' ) && method_exists( 'AAA_OC_ProductSearch_Search_Hooks', 'init' ) ) {
        AAA_OC_ProductSearch_Search_Hooks::init();
        aaa_oc_productsearch_log( 'ProductSearch search hooks initialised.' );
    } else {
        aaa_oc_productsearch_log( 'ProductSearch search hooks class missing; search not overridden.' );
    }
}, 20 );

// Ensure the index and synonyms tables exist on admin_init. This uses
// dbDelta under the hood so it is safe to call repeatedly.
add_action( 'admin_init', function () {
    if ( class_exists( 'AAA_OC_ProductSearch_Table_Installer' ) ) {
        AAA_OC_ProductSearch_Table_Installer::maybe_install();
        aaa_oc_productsearch_log( 'ProductSearch tables ensured via maybe_install().' );
    } else {
        aaa_oc_productsearch_log( 'ProductSearch installer class missing on admin_init.' );
    }
}, 5 );

// Optional: register a hook that allows other modules to install the
// ProductSearch tables on demand (mirrors upstream behaviour). This can
// be triggered via do_action( 'aaa_oc_module_install' ).
add_action( 'aaa_oc_module_install', function () {
    if ( class_exists( 'AAA_OC_ProductSearch_Table_Installer' ) ) {
        AAA_OC_ProductSearch_Table_Installer::install();
        aaa_oc_productsearch_log( 'ProductSearch tables created via aaa_oc_module_install.' );
    }
}, 10 );