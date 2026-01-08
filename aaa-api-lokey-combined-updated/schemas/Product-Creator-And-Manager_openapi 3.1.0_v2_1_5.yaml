<?php
/**
 * Plugin Name: AAA API Lokey Combined (XHV98-API)
 * Plugin URI:  https://lokey.delivery
 * Description: Consolidates Lokey Sales Reports and Inventory API into a single plugin.
 *              This plugin registers all LokeyReports routes (sales, brands, categories,
 *              orders, customers, attributes) alongside inventory, purchase order,
 *              forecast and product management endpoints.  GPT proxy routes are
 *              handled internally to avoid external HTTP calls and require
 *              JWT authentication.  The plugin relies on the Lokey JWT
 *              bridge (MU plugin) for token validation.  Each endpoint file is
 *              loaded from the sales/ and inventory/ folders to keep code
 *              organised and under the 150‑line guideline.
 * Version:     1.0.0
 * Author:      Lokey Delivery DevOps
 * License:     GPL‑2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Abort if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * -------------------------------------------------------------------------
 * Constants & Configuration
 *
 * Define the plugin directory and version constants.  These values allow
 * other components to reference paths relative to this plugin.  We also
 * define the Lokey Inventory namespace constants here so that the
 * underlying includes know which namespace to register routes under.  If
 * these constants are already defined by another plugin, we leave them
 * untouched to avoid conflicts.
 */
if ( ! defined( 'AAA_API_LOKEY_COMBINED_DIR' ) ) {
    define( 'AAA_API_LOKEY_COMBINED_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'AAA_API_LOKEY_COMBINED_VERSION' ) ) {
    define( 'AAA_API_LOKEY_COMBINED_VERSION', '1.0.0' );
}

// Define the inventory API namespace and version if not set elsewhere.
if ( ! defined( 'LOKEY_INV_API_NS' ) ) {
    define( 'LOKEY_INV_API_NS', 'lokey-inventory/v1' );
}
if ( ! defined( 'LOKEY_INV_API_VERSION' ) ) {
    define( 'LOKEY_INV_API_VERSION', '2.0.0' );
}
if ( ! defined( 'LOKEY_INV_API_DIR' ) ) {
    define( 'LOKEY_INV_API_DIR', AAA_API_LOKEY_COMBINED_DIR . 'inventory/' );
}

// Define WooCommerce/ATUM API credentials if not already provided.
if ( ! defined( 'LOKEY_ATUM_CK' ) ) {
    define( 'LOKEY_ATUM_CK', 'ck_dd31fdb6262a021f2d74bdc487b22b7c81776bbf' );
}
if ( ! defined( 'LOKEY_ATUM_CS' ) ) {
    define( 'LOKEY_ATUM_CS', 'cs_e5422dca649d60c50872d9aed1424315a1691622' );
}

/*
 * -------------------------------------------------------------------------
 * Ensure the Lokey JWT bridge is loaded
 *
 * The JWT bridge is provided as an MU plugin.  We include it here to make
 * sure that lokey_require_jwt_auth() and related helpers are available
 * before any routes register.  If the file does not exist, the plugin
 * gracefully continues; WordPress will load the bridge if present.
 */
add_action( 'plugins_loaded', function() {
    $bridge = WP_CONTENT_DIR . '/mu-plugins/lokey-jwt-auth-bridge.php';
    if ( file_exists( $bridge ) ) {
        include_once $bridge;
    }
}, 1 );

/*
 * -------------------------------------------------------------------------
 * Autoload Sales and Inventory Components
 *
 * This function loads all helper and route files from the sales and
 * inventory subdirectories.  It runs only once per request and ensures
 * that routes are only registered when WooCommerce is active.  GPT proxy
 * routes are implemented in the inventory includes (gpt-sales-summary.php,
 * gpt-sales-top.php, gpt-sales-overview.php) and are not loaded from
 * the legacy sales gpt‑proxy directory to avoid token issues.
 */
if ( ! function_exists( 'aaa_api_lokey_combined_autoload' ) ) {
    function aaa_api_lokey_combined_autoload() {
        static $loaded = false;
        // If we've already successfully loaded endpoints, bail early.
        if ( $loaded ) {
            return;
        }

        // Only proceed when WooCommerce is available.  If WooCommerce
        // hasn't loaded yet, return without marking as loaded so the
        // autoloader can run again on the woocommerce_init hook.
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        // Mark as loaded so we don't re-run on subsequent calls.
        $loaded = true;

        // --- Sales helpers and routes ---
        $sales_dir = AAA_API_LOKEY_COMBINED_DIR . 'sales/';
        // Load helper files
        $helpers = glob( $sales_dir . 'helpers/*.php' );
        if ( $helpers ) {
            foreach ( $helpers as $helper ) {
                require_once $helper;
            }
        }
        // Load the central permission checker
        $perm_file = $sales_dir . 'lokey-reports-permissions.php';
        if ( file_exists( $perm_file ) ) {
            require_once $perm_file;
        }
        // Load sales routes
        $routes = glob( $sales_dir . 'routes/*.php' );
        if ( $routes ) {
            foreach ( $routes as $route ) {
                require_once $route;
            }
        }
        // Optionally load GPT diagnostics from sales if present
        $diag_file = $sales_dir . 'gpt-proxy/gpt-sales-diagnostics.php';
        if ( file_exists( $diag_file ) ) {
            require_once $diag_file;
        }

        // --- Inventory helpers and includes ---
        $inv_dir = AAA_API_LOKEY_COMBINED_DIR . 'inventory/includes/';
        // Load helpers first
        $inv_helpers = $inv_dir . 'helpers.php';
        if ( file_exists( $inv_helpers ) ) {
            require_once $inv_helpers;
        }
        // Load all inventory endpoint definitions
        $inv_files = glob( $inv_dir . '*.php' );
        if ( $inv_files ) {
            foreach ( $inv_files as $file ) {
                // skip helpers since it's already loaded
                if ( basename( $file ) === 'helpers.php' ) continue;
                require_once $file;
            }
        }
    }
}

/*
 * -------------------------------------------------------------------------
 * REST API Bootstrap
 *
 * Hook into rest_api_init to autoload all routes.  This ensures that
 * sales and inventory endpoints register at the appropriate time.  The
 * action priority is kept at default to load after the JWT bridge.
 */
add_action( 'rest_api_init', 'aaa_api_lokey_combined_autoload' );
// Also register autoload on woocommerce_init so that endpoints load after
// WooCommerce has fully initialised.  The autoloader will skip loading if
// WooCommerce is still inactive and will only run once per request.
add_action( 'woocommerce_init', 'aaa_api_lokey_combined_autoload' );

/*
 * -------------------------------------------------------------------------
 * Defaults and Timezone
 *
 * Ensure a default timezone is set if none is configured.  Many date
 * calculations rely on having a timezone available.
 */
add_action( 'init', function() {
    if ( ! get_option( 'timezone_string' ) ) {
        update_option( 'timezone_string', 'America/Los_Angeles' );
    }
    // Set default brand taxonomy for product aggregation in sales reports.  The
    // BeRocket brands taxonomy is used unless overridden via filter.
    add_filter( 'lokey_reports_brand_taxonomy', fn() => 'berocket_brand' );
}, 1 );
