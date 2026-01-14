<?php
/**
 * Forecast module assets loader.
 *
 * Registers and enqueues any JavaScript or CSS assets used by the
 * forecast module. Keeping asset loading separate from PHP classes
 * ensures a clear separation of concerns and follows the module's
 * wide & thin architecture guidelines. Assets live in the
 * includes/forecast/assets/ directory and are optional; this loader
 * checks for their presence before enqueuing.
 *
 * @package AAA_Order_Workflow
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AAA_OC_Forecast_Assets_Loader
 *
 * Handles registration and enqueueing of script and style assets for
 * the forecast module. All methods are static for ease of use. The
 * assets are versioned based on AAA_OC_FORECAST_VERSION to ensure
 * browser cache busting when the module is updated.
 */
final class AAA_OC_Forecast_Assets_Loader {
    /**
     * Initialise asset loading by hooking into admin enqueue.
     */
    public static function init(): void {
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    /**
     * Enqueue JavaScript and CSS assets if they exist. Assets should be
     * placed in the includes/forecast/assets directory with names
     * forecast.js and forecast.css. Scripts are loaded in the admin
     * context only.
     */
    public static function enqueue_assets(): void {
        $base_dir = __DIR__ . '/assets';
        $plugin_base = dirname( dirname( __DIR__ ) ); // Path to the plugin root.
        // JavaScript asset.
        $js_path  = $base_dir . '/forecast.js';
        if ( file_exists( $js_path ) ) {
            $js_url = plugins_url( 'includes/forecast/assets/forecast.js', $plugin_base . '/aaa-order-workflow.php' );
            wp_enqueue_script( 'aaa-oc-forecast-js', $js_url, [ 'jquery' ], AAA_OC_FORECAST_VERSION, true );
        }
        // CSS asset.
        $css_path = $base_dir . '/forecast.css';
        if ( file_exists( $css_path ) ) {
            $css_url = plugins_url( 'includes/forecast/assets/forecast.css', $plugin_base . '/aaa-order-workflow.php' );
            wp_enqueue_style( 'aaa-oc-forecast-css', $css_url, [], AAA_OC_FORECAST_VERSION );
        }
    }
}

// Initialise the assets loader immediately.
AAA_OC_Forecast_Assets_Loader::init();