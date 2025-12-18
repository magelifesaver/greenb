<?php
/**
 * Enqueue CSS and JavaScript assets for AAA Geo Business Mapper. All scripts
 * are registered individually to allow fine‑grained dependencies. A single
 * localisation object (AAA_GBM_CFG) is provided to the client for nonce,
 * AJAX URL and any server‑side configuration.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_GBM_Assets {

    /**
     * Enable verbose logging from this class. When false, no logs are emitted.
     */
    const DEBUG_THIS_FILE = true;

    /**
     * Register hooks. Called during plugins_loaded.
     */
    public static function init() {
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
    }

    /**
     * Enqueue scripts and styles only on our settings page. We defer loading
     * Google Maps until we have a browser key; otherwise the map will not
     * initialise. All plugin scripts depend on each other explicitly.
     *
     * @param string $hook Current admin page hook.
     */
    public static function enqueue( $hook ) {
        if ( 'tools_page_aaa-gbm' !== $hook ) {
            return;
        }

        $browser_key = get_option( AAA_GBM_Admin::OPT_BROWSER_KEY, '' );

        // CSS: includes sticky map styles and general layout.
        wp_enqueue_style( 'aaa-gbm-admin', AAA_GBM_URL . 'assets/css/aaa-gbm-admin.css', array(), AAA_GBM_VER );

        // Register JS modules. Each file should remain under 150 lines.
        wp_register_script( 'aaa-gbm-map',   AAA_GBM_URL . 'assets/js/aaa-gbm-map-init.js', array(), AAA_GBM_VER, true );
        wp_register_script( 'aaa-gbm-grid',  AAA_GBM_URL . 'assets/js/aaa-gbm-grid.js', array( 'aaa-gbm-map' ), AAA_GBM_VER, true );
        wp_register_script( 'aaa-gbm-api',   AAA_GBM_URL . 'assets/js/aaa-gbm-api.js', array(), AAA_GBM_VER, true );
        wp_register_script( 'aaa-gbm-layers',AAA_GBM_URL . 'assets/js/aaa-gbm-layers.js', array( 'aaa-gbm-map', 'aaa-gbm-api', 'aaa-gbm-grid' ), AAA_GBM_VER, true );
        wp_register_script( 'aaa-gbm-scan',  AAA_GBM_URL . 'assets/js/aaa-gbm-scan.js', array( 'aaa-gbm-layers' ), AAA_GBM_VER, true );
        wp_register_script( 'aaa-gbm-analysis', AAA_GBM_URL . 'assets/js/aaa-gbm-analysis.js', array( 'aaa-gbm-grid', 'aaa-gbm-layers' ), AAA_GBM_VER, true );
        wp_register_script( 'aaa-gbm-heat', AAA_GBM_URL . 'assets/js/aaa-gbm-heatmap.js', array( 'aaa-gbm-analysis' ), AAA_GBM_VER, true );

        // Localise configuration for scripts.
        wp_localize_script( 'aaa-gbm-api', 'AAA_GBM_CFG', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'aaa_gbm_nonce' ),
        ) );

        // Load Google Maps JS only when browser key exists. We specify libraries
        // needed: drawing, geometry, places. The async attribute is not used
        // because WordPress enqueues scripts synchronously; this is acceptable.
        if ( $browser_key ) {
            wp_enqueue_script(
                'google-maps-js',
                'https://maps.googleapis.com/maps/api/js?key=' . rawurlencode( $browser_key ) . '&libraries=drawing,geometry,places&v=weekly',
                array(),
                null,
                true
            );
        }

        // Marker clusterer for grouping markers.
        wp_enqueue_script( 'markerclusterer', 'https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js', array(), null, true );
        // Heatmap.js for density visualisation.
        wp_enqueue_script( 'heatmapjs', 'https://unpkg.com/heatmap.js@2.0.5/build/heatmap.min.js', array(), null, true );

        // Enqueue our plugin scripts after Google scripts.
        wp_enqueue_script( 'aaa-gbm-map' );
        wp_enqueue_script( 'aaa-gbm-grid' );
        wp_enqueue_script( 'aaa-gbm-api' );
        wp_enqueue_script( 'aaa-gbm-layers' );
        wp_enqueue_script( 'aaa-gbm-scan' );
        wp_enqueue_script( 'aaa-gbm-analysis' );
        wp_enqueue_script( 'aaa-gbm-heat' );

        if ( self::DEBUG_THIS_FILE ) {
            AAA_GBM_Logger::log( 'Assets enqueued', array( 'hook' => $hook, 'has_browser_key' => (bool) $browser_key ) );
        }
    }
}