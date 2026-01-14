<?php
/**
 * Admin post handlers for forecast and PO queues.
 *
 * These handlers respond to form submissions on the Forecast tab. Each
 * method verifies permissions and nonces before scheduling the
 * appropriate background job. Redirects back to the referring page
 * with a query flag so the UI can display a notice. Keeping each
 * action in a dedicated method improves readability and makes it
 * easier to modify individual behaviours.
 * Version: 0.1.4
 * @package AAA_Order_Workflow
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Forecast_Admin_Actions {
    /**
     * Register admin_post actions. Called by the loader.
     */
    public static function init(): void {
        add_action( 'admin_post_aaa_oc_forecast_queue_all_enabled', [ __CLASS__, 'queue_all_enabled' ] );
        add_action( 'admin_post_aaa_oc_forecast_process_queue_now', [ __CLASS__, 'process_queue_now' ] );
        add_action( 'admin_post_aaa_oc_forecast_process_po_queue', [ __CLASS__, 'process_po_queue' ] );
        add_action( 'admin_post_aaa_oc_forecast_repair_queue_tables', [ __CLASS__, 'repair_tables' ] );
    }

    /**
     * Schedule queueing of all enabled products. Requires manage
     * WooCommerce capability and a valid nonce.
     */
    public static function queue_all_enabled(): void {
        self::require_manage_woo();
        check_admin_referer( 'aaa_oc_forecast_queue_all_enabled' );
        if ( class_exists( 'AAA_OC_Forecast_Queue' ) ) {
            AAA_OC_Forecast_Queue::schedule_queue_all_enabled();
        }
        self::redirect_back( [ 'aaa_oc_forecast_queue_scheduled' => 1 ] );
    }

    /**
     * Schedule immediate processing of the forecast queue. Uses a
     * 10‑second delay so the event runs shortly after the request ends.
     */
    public static function process_queue_now(): void {
        self::require_manage_woo();
        check_admin_referer( 'aaa_oc_forecast_process_queue_now' );
        if ( class_exists( 'AAA_OC_Forecast_Queue' ) ) {
            AAA_OC_Forecast_Queue::schedule_process( 10 );
        }
        self::redirect_back( [ 'aaa_oc_forecast_process_scheduled' => 1 ] );
    }

    /**
     * Schedule immediate processing of the PO queue. Uses a short delay
     * to allow the redirect to complete before cron fires.
     */
    public static function process_po_queue(): void {
        self::require_manage_woo();
        check_admin_referer( 'aaa_oc_forecast_process_po_queue' );
        if ( class_exists( 'AAA_OC_Forecast_PO_Queue' ) ) {
            AAA_OC_Forecast_PO_Queue::schedule_process( 10 );
        }
        self::redirect_back( [ 'aaa_oc_forecast_po_process_scheduled' => 1 ] );
    }

    /**
     * Repair (install or update) the forecast and PO queue tables.
     */
    public static function repair_tables(): void {
        self::require_manage_woo();
        check_admin_referer( 'aaa_oc_forecast_repair_queue_tables' );
        if ( class_exists( 'AAA_OC_Forecast_Queue_Installer' ) ) {
            AAA_OC_Forecast_Queue_Installer::maybe_install_tables();
        }
        self::redirect_back( [ 'aaa_oc_forecast_repaired' => 1 ] );
    }

    /**
     * Verify the current user has the manage_woocommerce capability.
     */
    private static function require_manage_woo(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'aaa-oc' ) );
        }
    }

    /**
     * Redirect back to the referring page, appending query args.
     *
     * @param array<string,int> $args Query args to append.
     */
    private static function redirect_back( array $args = [] ): void {
        $ref = wp_get_referer();
        if ( ! $ref ) {
            $ref = admin_url( 'admin.php?page=aaa-oc-core-settings&tab=aaa-oc-forecast-queue' );
        }
        if ( ! empty( $args ) ) {
            $ref = add_query_arg( $args, $ref );
        }
        wp_safe_redirect( $ref );
        exit;
    }
}