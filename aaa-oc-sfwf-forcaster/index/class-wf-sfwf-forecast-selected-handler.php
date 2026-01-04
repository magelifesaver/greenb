<?php
/**
 * Filepath: index/class-wf-sfwf-forecast-selected-handler.php
 * ---------------------------------------------------------------------------
 * Provides an admin-post handler to run the forecast on a set of selected
 * products from the forecast grid.  A form on the forecast dashboard posts
 * the selected product IDs to this handler.  It verifies permissions and
 * nonces, loops through each product ID and calls the forecast runner for
 * each one, then redirects back with a count of processed items.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WF_SFWF_Forecast_Selected_Handler {

    /**
     * Registers the admin-post handler on init.
     */
    public static function init() {
        add_action( 'admin_post_sfwf_run_selected', [ __CLASS__, 'handle_run_selected' ] );
    }

    /**
     * Handles the form submission for updating selected products.
     */
    public static function handle_run_selected() {
        // Only users with appropriate capability can run forecasts.
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to run the forecast.', 'aaa-wf-sfwf' ) );
        }
        // Verify the nonce to protect against CSRF.
        if ( ! isset( $_POST['sfwf_run_selected_nonce'] ) || ! wp_verify_nonce( $_POST['sfwf_run_selected_nonce'], 'sfwf_run_selected' ) ) {
            wp_die( esc_html__( 'Invalid request.', 'aaa-wf-sfwf' ) );
        }
        $ids_str = isset( $_POST['product_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['product_ids'] ) ) : '';
        $ids     = [];
        if ( ! empty( $ids_str ) ) {
            $ids = array_filter( array_map( 'absint', explode( ',', $ids_str ) ) );
        }
        $processed = 0;
        if ( ! empty( $ids ) && class_exists( 'WF_SFWF_Forecast_Runner' ) ) {
            foreach ( $ids as $pid ) {
                WF_SFWF_Forecast_Runner::update_single_product( $pid );
                $processed++;
            }
        }
        // Redirect back to the forecast grid with a flag to show a notice.
        $redirect = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=sfwf-forecast-grid' );
        $redirect = add_query_arg( 'sfwf_run_selected_done', $processed, $redirect );
        wp_safe_redirect( $redirect );
        exit;
    }
}

// Initialize the handler when this file is loaded.
WF_SFWF_Forecast_Selected_Handler::init();