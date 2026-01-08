<?php
/**
 * ============================================================================
 * Lokey Reports Permission Helper
 * ============================================================================
 * Provides a centralized permission callback for LokeyReports routes.
 * Returns true for internal requests, valid JWT tokens, WooCommerce consumer
 * key/secret fallback, or logged-in administrators/managers. Returns a WP_Error
 * with status 401 otherwise.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'lokey_reports_permission_check' ) ) {
    /**
     * Checks whether the current request has permission to access a LokeyReports route.
     *
     * @return true|WP_Error
     */
    function lokey_reports_permission_check() {
        // ✅ Internal calls (cron, CLI, AJAX, or internal header)
        if ( defined('DOING_CRON') || php_sapi_name() === 'cli' || wp_doing_ajax()
            || ( isset($_SERVER['HTTP_X_LOKEY_INTERNAL']) && $_SERVER['HTTP_X_LOKEY_INTERNAL'] === '1' ) ) {
            return true;
        }

        // ✅ JWT Authentication
        if ( function_exists('lokey_require_jwt_auth') ) {
            $jwt_auth = lokey_require_jwt_auth();
            if ( $jwt_auth === true || ( is_object( $jwt_auth ) && ! is_wp_error( $jwt_auth ) ) ) {
                return true;
            }
        }

        // ✅ WooCommerce Consumer Key / Secret fallback
        $ck = isset($_GET['consumer_key']) ? sanitize_text_field($_GET['consumer_key']) : '';
        $cs = isset($_GET['consumer_secret']) ? sanitize_text_field($_GET['consumer_secret']) : '';

        $valid_ck = defined('LOKEY_API_KEY')    ? LOKEY_API_KEY    : 'ck_dd31fdb6262a021f2d74bdc487b22b7c81776bbf';
        $valid_cs = defined('LOKEY_API_SECRET') ? LOKEY_API_SECRET : 'cs_e5422dca649d60c50872d9aed1424315a1691622';

        if ( $ck && $cs && $ck === $valid_ck && $cs === $valid_cs ) {
            return true;
        }

        // ✅ Logged-in Admin or Manager
        if ( is_user_logged_in() && (
            current_user_can('manage_woocommerce') ||
            current_user_can('view_woocommerce_reports') ||
            current_user_can('administrator')
        ) ) {
            return true;
        }

        // ❌ Deny everything else
        return new WP_Error(
            'rest_forbidden',
            __( 'Access denied.', 'lokey-reports' ),
            [ 'status' => 401 ]
        );
    }
}
