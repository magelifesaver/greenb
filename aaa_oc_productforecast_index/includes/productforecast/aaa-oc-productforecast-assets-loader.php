<?php
/**
 * File: includes/productforecast/aaa-oc-productforecast-assets-loader.php
 * Purpose: Enqueues scripts and styles for the ProductForecast module in the admin.
 * This loader ensures the appropriate assets are loaded on both the settings
 * and grid pages.  It uses DataTables from a CDN to provide sorting and
 * filtering capabilities on the forecast grid.
 *
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'AAA_OC_PRODUCTFORECAST_ASSETS_LOADER_READY' ) ) {
    return;
}
define( 'AAA_OC_PRODUCTFORECAST_ASSETS_LOADER_READY', true );

if ( ! class_exists( 'AAA_OC_ProductForecast_Assets_Loader' ) ) {

    class AAA_OC_ProductForecast_Assets_Loader {

        public static function init() {
            add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin' ] );
        }

        /**
         * Enqueue admin scripts and styles for settings and grid pages.
         *
         * @param string $hook Admin page hook.
         */
        public static function enqueue_admin( $hook ) {
            if ( empty( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                return;
            }

            $page = sanitize_key( wp_unslash( $_GET['page'] ) );
            $base = plugin_dir_url( __FILE__ ) . 'assets/';

            // Settings page assets.
            if ( $page === 'aaa-oc-productforecast' ) {
                wp_enqueue_style(
                    'aaa-oc-productforecast-admin',
                    $base . 'css/aaa-oc-productforecast.css',
                    [],
                    defined( 'AAA_OC_PFI_VERSION' ) ? AAA_OC_PFI_VERSION : '1.0.0'
                );

                wp_enqueue_script(
                    'aaa-oc-productforecast-admin',
                    $base . 'js/aaa-oc-productforecast.js',
                    [ 'jquery' ],
                    defined( 'AAA_OC_PFI_VERSION' ) ? AAA_OC_PFI_VERSION : '1.0.0',
                    true
                );
            }

            // Forecast grid page assets.
            if ( $page === 'aaa-oc-productforecast-grid' ) {
                // DataTables core CSS and JS from CDN.
                wp_enqueue_style(
                    'datatables-css',
                    'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css',
                    [],
                    '1.13.6'
                );
                wp_enqueue_script(
                    'datatables-js',
                    'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',
                    [ 'jquery' ],
                    '1.13.6',
                    true
                );
                // FixedHeader extension to keep headers visible during scroll.
                wp_enqueue_script(
                    'datatables-fixedheader-js',
                    'https://cdn.datatables.net/fixedheader/3.2.4/js/dataTables.fixedHeader.min.js',
                    [ 'datatables-js' ],
                    '3.2.4',
                    true
                );

                // Our grid CSS (reuse module CSS).
                wp_enqueue_style(
                    'aaa-oc-productforecast-grid',
                    $base . 'css/aaa-oc-productforecast.css',
                    [ 'datatables-css' ],
                    defined( 'AAA_OC_PFI_VERSION' ) ? AAA_OC_PFI_VERSION : '1.0.0'
                );

                // Our grid JS (reuse module JS for now).
                wp_enqueue_script(
                    'aaa-oc-productforecast-grid',
                    $base . 'js/aaa-oc-productforecast.js',
                    [ 'jquery', 'datatables-js', 'datatables-fixedheader-js' ],
                    defined( 'AAA_OC_PFI_VERSION' ) ? AAA_OC_PFI_VERSION : '1.0.0',
                    true
                );
            }
        }
    }

    AAA_OC_ProductForecast_Assets_Loader::init();
}
