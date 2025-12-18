<?php
/**
 * Plugin Name: AAA API Lokey Sales Reports (XHV98-API)
 * Plugin URI:  https://lokey.delivery
 * Description: Unified REST reporting and GPT proxy system for Lokey Delivery.  This regular plugin registers its REST endpoints only when the WordPress REST API is in use.  It replaces the MU plugin version and avoids loading heavy code on normal front‑end page loads.
 * Version:     1.0.0
 * Author:      Lokey Delivery DevOps
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Ensure Lokey JWT Bridge is loaded before registering routes.
 */
add_action('plugins_loaded', function() {
    if (file_exists(WP_CONTENT_DIR . '/mu-plugins/lokey-jwt-auth-bridge.php')) {
        include_once WP_CONTENT_DIR . '/mu-plugins/lokey-jwt-auth-bridge.php';
    }
}, 1);

/**
 * -------------------------------------------------------------------------
 * Configuration
 * -------------------------------------------------------------------------
 */

// Define directory and version constants only if not already defined.  These
// constants allow other plugins to refer to this plugin’s path and version.
if ( ! defined( 'AAA_API_LOKEY_SALES_DIR' ) ) {
    define( 'AAA_API_LOKEY_SALES_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'AAA_API_LOKEY_SALES_VERSION' ) ) {
    define( 'AAA_API_LOKEY_SALES_VERSION', '1.0.0' );
}

// Optional debug flag.  When true, the plugin will emit error_log messages
// as it loads components.  Disable this in production.
if ( ! defined( 'AAA_API_LOKEY_SALES_DEBUG' ) ) {
    define( 'AAA_API_LOKEY_SALES_DEBUG', false );
}

// Shared WooCommerce/ATUM credentials.  These keys are used by the helper
// functions to authenticate proxy requests.  You can override them via
// wp-config.php or another plugin if needed.
if ( ! defined( 'LOKEY_ATUM_CK' ) ) {
    define( 'LOKEY_ATUM_CK', 'ck_dd31fdb6262a021f2d74bdc487b22b7c81776bbf' );
}
if ( ! defined( 'LOKEY_ATUM_CS' ) ) {
    define( 'LOKEY_ATUM_CS', 'cs_e5422dca649d60c50872d9aed1424315a1691622' );
}

/**
 * -------------------------------------------------------------------------
 * Autoload components
 * -------------------------------------------------------------------------
 *
 * This helper loads helper, route and GPT proxy files.  It runs once per
 * request (REST or otherwise) and sets a static flag to avoid re‑loading
 * files on subsequent calls.  Debug messages are emitted only if
 * AAA_API_LOKEY_SALES_DEBUG is true *and* the lokey_reports_debug helper
 * exists (defined in helpers-debug.php).
 */
// Conditionally define the autoloader to avoid redeclaration if another copy
// of this plugin (for example, a leftover MU version) has already defined it.
if ( ! function_exists( 'aaa_api_lokey_sales_reports_autoload' ) ) {
function aaa_api_lokey_sales_reports_autoload() {
    static $loaded = false;
    if ( $loaded ) {
        return;
    }
    $loaded = true;

    // ✅ The JWT bridge must be loaded BEFORE any routes.  We rely on
    // the mu‑plugins copy (wp-content/mu-plugins/lokey-jwt-auth-bridge.php) so
    // that authentication is always available regardless of whether this plugin
    // is active.  Do not load a second copy from this plugin directory.
    // If the MU bridge has already run, the lokey_require_jwt_auth() function
    // will be available.  Otherwise, do nothing here – WordPress will load
    // the MU bridge automatically on init.

    // Load helpers
    $helpers_dir = AAA_API_LOKEY_SALES_DIR . 'helpers';
    if ( is_dir( $helpers_dir ) ) {
        foreach ( glob( $helpers_dir . '/*.php' ) as $helper_file ) {
            require_once $helper_file;
        }
    }

    // Load routes
    $routes_dir = AAA_API_LOKEY_SALES_DIR . 'routes';
    if ( is_dir( $routes_dir ) ) {
        foreach ( glob( $routes_dir . '/*.php' ) as $route_file ) {
            require_once $route_file;
        }
    }

    // Load GPT proxy routes
    $proxy_dir = AAA_API_LOKEY_SALES_DIR . 'gpt-proxy';
    if ( is_dir( $proxy_dir ) ) {
        foreach ( glob( $proxy_dir . '/*.php' ) as $proxy_file ) {
            require_once $proxy_file;
        }
    }
}
} // end conditional wrapper

/**
 * -------------------------------------------------------------------------
 * REST API bootstrap
 * -------------------------------------------------------------------------
 *
 * When WordPress initialises the REST API (`rest_api_init`), this callback
 * checks for WooCommerce and then autoloads all components (helpers,
 * routes, GPT proxies).  Because the route files register their own
 * callbacks with `add_action( 'rest_api_init', ... )`, including them
 * here will make the REST endpoints available immediately.
 */
function aaa_api_lokey_sales_reports_rest_init() {
    // Only proceed if WooCommerce is active.  Without WooCommerce, there’s
    // nothing useful to report.  We bail early to avoid unnecessary work.
    if ( ! class_exists( 'WooCommerce' ) ) {
        if ( AAA_API_LOKEY_SALES_DEBUG && function_exists( 'lokey_reports_debug' ) ) {
            lokey_reports_debug( 'WooCommerce not active; skipping sales REST routes.', 'bootstrap' );
        }
        return;
    }
    aaa_api_lokey_sales_reports_autoload();
    if ( AAA_API_LOKEY_SALES_DEBUG && function_exists( 'lokey_reports_debug' ) ) {
        lokey_reports_debug( 'AAA API Lokey Sales Reports routes initialised.', 'bootstrap' );
    }
    // Fire a custom action so other code can hook when this plugin loads.
    do_action( 'aaa_api_lokey_sales_reports_loaded' );
}
add_action( 'rest_api_init', 'aaa_api_lokey_sales_reports_rest_init' );
/**
 * ============================================================================
 * LokeyReports Unified Permission Callback
 * ============================================================================
 * Used for all LokeyReports REST routes. Validates either:
 *   - JWT Bearer Authorization header, or
 *   - ?token= query parameter, or
 *   - Logged-in WordPress user (admin).
 */
if ( ! function_exists( 'lokey_reports_permission_check' ) ) {
	function lokey_reports_permission_check() {

		// ✅ Allow logged-in admin sessions
		if ( is_user_logged_in() ) {
			return current_user_can('manage_woocommerce') ||
			       current_user_can('view_woocommerce_reports') ||
			       current_user_can('administrator');
		}

		// ✅ Allow JWT tokens (via header or ?token=)
		if ( function_exists( 'lokey_require_jwt_auth' ) ) {
			$result = lokey_require_jwt_auth();

			if ( true === $result ) {
				return true;
			}

			// If JWT returned WP_Error, bubble up cleanly
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'Access denied (JWT or admin required).', 'lokey-reports' ),
			[ 'status' => 401 ]
		);
	}
}

/**
 * -------------------------------------------------------------------------
 * Performance Metrics Helpers
 * -------------------------------------------------------------------------
 * These helpers allow each REST route to measure execution time and memory
 * usage.  When used in a route callback, the route can include two headers
 * (`X-Lokey-Exec-Time` and `X-Lokey-Memory`) in the response to indicate
 * how long the request took and how much memory was consumed.  This allows
 * administrators and developers to gauge resource usage during debugging.
 */

if ( ! function_exists( 'lokey_reports_metrics_start' ) ) {
    /**
     * Capture the starting metrics for a request.
     *
     * @return array { time: float, memory: int }
     */
    function lokey_reports_metrics_start() {
        return [ 'time' => microtime( true ), 'memory' => memory_get_usage() ];
    }
}

if ( ! function_exists( 'lokey_reports_metrics_end' ) ) {
    /**
     * Compute the elapsed time and memory usage since the given start metrics.
     *
     * @param array $start Start metrics as returned by lokey_reports_metrics_start().
     * @return array { time: float, memory: int }
     */
    function lokey_reports_metrics_end( array $start ) {
        return [
            'time'   => microtime( true ) - (float) ( $start['time'] ?? 0 ),
            'memory' => memory_get_usage() - (int) ( $start['memory'] ?? 0 ),
        ];
    }
}

/**
 * -------------------------------------------------------------------------
 * Defaults
 * -------------------------------------------------------------------------
 *
 * Provide a default brand taxonomy for the product aggregation routines and
 * ensure the site timezone is set.  These actions mirror the MU loader.
 */
function aaa_api_lokey_sales_reports_defaults() {
    // Default brand taxonomy (BeRocket brands).
    add_filter( 'lokey_reports_brand_taxonomy', fn() => 'berocket_brand' );

    // Set a default timezone if one isn’t configured.  This helps
    // ensure date calculations work consistently.
    if ( ! get_option( 'timezone_string' ) ) {
        update_option( 'timezone_string', 'America/Los_Angeles' );
    }
}
add_action( 'init', 'aaa_api_lokey_sales_reports_defaults', 1 );
add_action('woocommerce_init', 'aaa_api_lokey_sales_reports_autoload');

