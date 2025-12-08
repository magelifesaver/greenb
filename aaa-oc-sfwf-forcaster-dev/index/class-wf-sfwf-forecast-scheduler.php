<?php
/**
 * Filepath: sfwf/index/class-wf-sfwf-forecast-scheduler.php
 * ---------------------------------------------------------------------------
 * Provides an asynchronous scheduler for running the forecast process.
 * Instead of executing the heavy update synchronously on page load, the
 * scheduler uses WordPress cron to defer the task to the next cron run. An
 * admin UI button posts to the admin-post handler defined here, which
 * schedules a single event. When the event fires, the runner updates all
 * reorder-enabled products.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WF_SFWF_Forecast_Scheduler {

    /**
     * Registers hooks for admin actions and cron event.
     */
    public static function init() {
        // Handle form submission from the admin UI to schedule the forecast.
        add_action( 'admin_post_sfwf_run_forecast', [ __CLASS__, 'handle_admin_post' ] );
        // Cron event callback when the forecast should run.
        add_action( 'sfwf_run_forecast_event', [ __CLASS__, 'run_forecast' ] );
    }

    /**
     * Handles the admin-post request to schedule the forecast.
     *
     * This method verifies permissions and nonces, schedules a single cron
     * event if none is pending, and redirects back to the referring page
     * with a query flag indicating that the forecast was scheduled.
     */
    public static function handle_admin_post() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to run the forecast.', 'aaa-wf-sfwf' ) );
        }

        // Validate nonce. The field name must match the one used in the form.
        if ( ! isset( $_POST['sfwf_run_forecast_nonce'] ) || ! wp_verify_nonce( $_POST['sfwf_run_forecast_nonce'], 'sfwf_run_forecast' ) ) {
            wp_die( esc_html__( 'Invalid request.', 'aaa-wf-sfwf' ) );
        }

        // Schedule the forecast to run in 30 seconds if no pending event exists.
        if ( ! wp_next_scheduled( 'sfwf_run_forecast_event' ) ) {
            wp_schedule_single_event( time() + 30, 'sfwf_run_forecast_event' );
        }

        // Redirect back to the referrer with a flag to show a notice.
        $redirect = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=sfwf-forecast-grid' );
        $redirect = add_query_arg( 'forecast_scheduled', '1', $redirect );
        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Executes the heavy forecast update.
     *
     * When the cron event fires, call into the runner to update all products.
     */
    public static function run_forecast() {
        // Double check that WooCommerce functions exist before running.
        if ( ! class_exists( 'WF_SFWF_Forecast_Runner' ) ) {
            return;
        }
        WF_SFWF_Forecast_Runner::update_all_products();
    }
}

// Immediately register hooks when this class is loaded.
WF_SFWF_Forecast_Scheduler::init();