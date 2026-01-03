<?php
/**
 * Loader: activation, DB setup, admin page, assets bootstrap.
 *
 * Registers plugin activation hooks, loads helper files and API clients,
 * and registers the admin menu.  This file intentionally contains only
 * lightweight bootstrap logic to keep plugin loading fast.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// -----------------------------------------------------------------------------
// Activation hook – prepare default options.  We rely on the standard
// WordPress options API rather than creating our own table here; the
// custom options table is used by the helper functions to store
// arbitrary keys and values.
// -----------------------------------------------------------------------------
register_activation_hook( __FILE__, function() {
    // Set sensible defaults if they do not already exist.
    add_option( 'aaa_wf_ai_openai_key', '' );
    add_option( 'aaa_wf_ai_allowed_roles', [ 'administrator', 'shop_manager' ] );
    add_option( 'aaa_wf_ai_prompt_template', "You are an AI data analyst. Given this WooCommerce sales summary JSON, produce a concise, clear narrative report including total orders, revenue, average order value, any trends and best‑selling products. If totals exist, describe them. If not, state that there were no sales. Respond in professional human‑readable format.\n\nJSON:\n\n" );
    add_option( 'aaa_wf_ai_temperature', 0.3 );
    add_option( 'aaa_wf_ai_max_tokens', 800 );

    // Ensure a default WP option for debug flag exists. 0 = disabled by default.
    add_option( 'aaa_wf_ai_debug_enabled', 0 );
} );

// -----------------------------------------------------------------------------
// Load core helpers and API clients.  All functions are prefixed to avoid
// collisions.  The debug helper is loaded first so that other files can
// write to the log immediately.
// -----------------------------------------------------------------------------
require_once __DIR__ . '/aaa-workflow-ai-reports-debug.php';
require_once __DIR__ . '/includes/helpers/options-helpers.php';
require_once __DIR__ . '/includes/api/openai-client.php';
require_once __DIR__ . '/includes/api/lokey-client.php';
require_once __DIR__ . '/admin/ajax.php';
require_once __DIR__ . '/admin/admin-page.php';

// -----------------------------------------------------------------------------
// Admin menu registration.  Only users with manage_woocommerce capability can
// access the AI reports dashboard.
// -----------------------------------------------------------------------------
add_action( 'admin_menu', function() {
    add_menu_page(
        'AAA Workflow AI Reports',   // page title
        'AI Reports',                // menu title
        'manage_woocommerce',        // capability
        'aaa-workflow-ai-reports',   // slug
        'aaa_wf_ai_admin_page_render', // callback to render page
        'dashicons-chart-bar',        // icon
        55
    );
});

/**
 * -----------------------------------------------------------------------------
 * Safe option fallback
 *
 * In certain cases (e.g. before activation on a fresh site) the custom
 * options table created by this plugin may not yet exist.  To avoid fatal
 * errors when retrieving options through aaa_wf_ai_get_option(), we provide
 * a wrapper that checks for the table and falls back to the core options API.
 *
 * @param string $key     Option key to fetch.
 * @param mixed  $default Default value if nothing is found.
 * @param string $scope   Optional scope for custom options (unused when
 *                        falling back to core).
 * @return mixed          Stored value or the default.
 */
if ( ! function_exists( 'aaa_wf_ai_safe_option_fallback' ) ) {
    function aaa_wf_ai_safe_option_fallback( $key, $default = null, $scope = 'global' ) {
        global $wpdb;
        // Determine the name of the custom options table (matches helper)
        $table = $wpdb->prefix . 'aaa_oc_options';
        // If the table doesn't exist yet, defer to WordPress core
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
            return get_option( $key, $default );
        }
        // Otherwise, use the custom helper to fetch from our scoped table
        return aaa_wf_ai_get_option( $key, $default, $scope );
    }
}