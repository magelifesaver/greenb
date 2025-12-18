<?php
/**
 * File Path: /wp-content/plugins/aaa-geo-business-mapper/includes/assets/class-aaa-gbm-assets.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_GBM_Assets {

    const DEBUG_THIS_FILE = true;

    public static function init() {
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
    }

    public static function enqueue( $hook ) {
        if ( 'tools_page_aaa-gbm' !== $hook ) {
            return;
        }

        $browser_key = get_option( AAA_GBM_Admin::OPT_BROWSER_KEY, '' );

        wp_enqueue_style( 'aaa-gbm-admin', AAA_GBM_URL . 'assets/css/aaa-gbm-admin.css', array(), AAA_GBM_VER );

        if ( $browser_key ) {
            wp_enqueue_script(
                'google-maps-js',
                'https://maps.googleapis.com/maps/api/js?key=' . rawurlencode( $browser_key ) . '&libraries=drawing,geometry&v=weekly',
                array(),
                null,
                true
            );
        }

        wp_register_script( 'aaa-gbm-map',  AAA_GBM_URL . 'assets/js/aaa-gbm-map-init.js', array(), AAA_GBM_VER, true );
        wp_register_script( 'aaa-gbm-grid', AAA_GBM_URL . 'assets/js/aaa-gbm-grid.js', array( 'aaa-gbm-map' ), AAA_GBM_VER, true );
        wp_register_script( 'aaa-gbm-api',  AAA_GBM_URL . 'assets/js/aaa-gbm-api.js', array( 'aaa-gbm-grid' ), AAA_GBM_VER, true );
        wp_register_script( 'aaa-gbm-ui',   AAA_GBM_URL . 'assets/js/aaa-gbm-ui.js', array( 'aaa-gbm-api' ), AAA_GBM_VER, true );
        wp_register_script( 'aaa-gbm-scan', AAA_GBM_URL . 'assets/js/aaa-gbm-scan.js', array( 'aaa-gbm-ui' ), AAA_GBM_VER, true );
        wp_register_script( 'aaa-gbm-analysis', AAA_GBM_URL . 'assets/js/aaa-gbm-analysis.js', array( 'aaa-gbm-scan' ), AAA_GBM_VER, true );

        wp_localize_script( 'aaa-gbm-map', 'AAA_GBM_CFG', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'aaa_gbm_nonce' ),
        ) );

        wp_enqueue_script( 'aaa-gbm-map' );
        wp_enqueue_script( 'aaa-gbm-grid' );
        wp_enqueue_script( 'aaa-gbm-api' );
        wp_enqueue_script( 'aaa-gbm-ui' );
        wp_enqueue_script( 'aaa-gbm-scan' );
        wp_enqueue_script( 'aaa-gbm-analysis' );

        wp_enqueue_script( 'markerclusterer', 'https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js', array(), null, true );

        if ( self::DEBUG_THIS_FILE ) {
            AAA_GBM_Logger::log( 'Assets enqueued', array( 'hook' => $hook, 'has_browser_key' => (bool) $browser_key ) );
        }
    }
}
