<?php
/**
 * File: /wp-content/plugins/aaa_oc_product_forecast_index/includes/productforecast/aaa-oc-productforecast-assets-loader.php
 * Purpose: Assets loader for ProductForecast module (admin only).
 * Version: 1.0.0
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
         * Admin assets – only on our settings page.
         */
        public static function enqueue_admin( $hook ) {
            if ( empty( $_GET['page'] ) || $_GET['page'] !== 'aaa-oc-productforecast' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                return;
            }

            $base = plugin_dir_url( __FILE__ ) . 'assets/';

            wp_enqueue_style(
                'aaa-oc-productforecast-admin',
                $base . 'css/aaa-oc-productforecast.css',
                [],
                defined( 'AAA_OC_PF_VERSION' ) ? AAA_OC_PF_VERSION : '1.0.0'
            );

            wp_enqueue_script(
                'aaa-oc-productforecast-admin',
                $base . 'js/aaa-oc-productforecast.js',
                [ 'jquery' ],
                defined( 'AAA_OC_PF_VERSION' ) ? AAA_OC_PF_VERSION : '1.0.0',
                true
            );
        }
    }

    AAA_OC_ProductForecast_Assets_Loader::init();
}
