<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forcast/aaa-oc-forcast-assets-loader.php
 * Purpose: Enqueue admin assets for the Forcast module.
 * Version: 0.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Respect the module debug constant defined in the main loader.
 */
if ( ! defined( 'AAA_OC_FORCAST_DEBUG' ) ) {
    define( 'AAA_OC_FORCAST_DEBUG', false );
}

/**
 * Handles asset enqueueing for the Forcast module.
 */
class AAA_OC_Forcast_Assets {

    /**
     * Bootstraps asset hooks.
     */
    public static function init() {
        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
        }
    }

    /**
     * Enqueue scripts and styles on the workflow board screen.
     *
     * @param string $hook Current admin hook suffix.
     */
    public static function enqueue( $hook ) {
        $page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
        if ( $page !== 'aaa-oc-workflow-board' ) {
            return;
        }

        $rel_js  = 'assets/js/forcast-admin.js';
        $rel_css = 'assets/css/forcast-admin.css';

        $fs_js  = trailingslashit( dirname( __FILE__ ) ) . $rel_js;
        $fs_css = trailingslashit( dirname( __FILE__ ) ) . $rel_css;

        $ver_js  = file_exists( $fs_js )  ? (string) filemtime( $fs_js )  : ( defined( 'AAA_OC_FORCAST_VERSION' ) ? AAA_OC_FORCAST_VERSION : '1.0.0' );
        $ver_css = file_exists( $fs_css ) ? (string) filemtime( $fs_css ) : ( defined( 'AAA_OC_FORCAST_VERSION' ) ? AAA_OC_FORCAST_VERSION : '1.0.0' );

        $script_url = plugins_url( $rel_js, __FILE__ );
        $style_url  = plugins_url( $rel_css, __FILE__ );

        wp_enqueue_script( 'aaa-oc-forcast-js', $script_url, [ 'jquery' ], $ver_js, true );
        wp_enqueue_style(  'aaa-oc-forcast-css', $style_url,  [],        $ver_css );

        wp_localize_script( 'aaa-oc-forcast-js', 'AAA_OC_Forcast', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'aaa_oc_ajax_nonce' ),
            'version' => $ver_js,
        ] );

        if ( defined( 'AAA_OC_FORCAST_DEBUG' ) && AAA_OC_FORCAST_DEBUG ) {
            error_log( '[Forcast][Assets] Enqueued assets v' . $ver_js );
        }
    }
}

AAA_OC_Forcast_Assets::init();