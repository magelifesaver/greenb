<?php
/**
 * Filepath: index/class-wf-sfwf-forecast-bulk-actions.php
 * ---------------------------------------------------------------------------
 * Adds a custom bulk action to the WooCommerce product list that allows
 * administrators to run the forecast for selected products.  When the bulk
 * action is executed, the forecast runner updates each selected product
 * individually.  Upon completion the admin is redirected back to the
 * products page with a query flag indicating how many products were
 * processed so a success notice can be displayed.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WF_SFWF_Forecast_Bulk_Actions {

    /**
     * Register hooks for adding and handling the bulk action.
     */
    public static function init() {
        // Add the bulk action option to the Products list table.
        add_filter( 'bulk_actions-edit-product', [ __CLASS__, 'register_bulk_action' ] );
        // Handle the bulk action when it is triggered.
        add_filter( 'handle_bulk_actions-edit-product', [ __CLASS__, 'handle_bulk_action' ], 10, 3 );
        // Display a notice after processing the bulk action.
        add_action( 'admin_notices', [ __CLASS__, 'show_admin_notice' ] );
    }

    /**
     * Adds the custom bulk action to the dropdown menu.
     *
     * @param array $bulk_actions Existing bulk actions.
     * @return array Modified bulk actions including our custom action.
     */
    public static function register_bulk_action( $bulk_actions ) {
        // Label shown in the bulk actions dropdown. Keep translation consistent.
        $bulk_actions['sfwf_run_forecast'] = __( 'Run Forecast', 'aaa-wf-sfwf' );
        return $bulk_actions;
    }

    /**
     * Handles the custom bulk action when triggered from the Products list.
     *
     * @param string $redirect_to URL to redirect to after handling.
     * @param string $doaction    Action being performed.
     * @param array  $product_ids Selected product IDs.
     * @return string URL to redirect back to.
     */
    public static function handle_bulk_action( $redirect_to, $doaction, $product_ids ) {
        if ( $doaction !== 'sfwf_run_forecast' ) {
            return $redirect_to;
        }
        // Ensure the user has permission to run the forecast.
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return $redirect_to;
        }
        $processed = 0;
        if ( class_exists( 'WF_SFWF_Forecast_Runner' ) ) {
            foreach ( $product_ids as $pid ) {
                // Cast to integer to prevent unexpected values.
                $pid = absint( $pid );
                if ( $pid <= 0 ) {
                    continue;
                }
                WF_SFWF_Forecast_Runner::update_single_product( $pid );
                $processed++;
            }
        }
        // Append a query argument so we can show a notice later.
        $redirect_to = add_query_arg( 'sfwf_forecast_updated', $processed, $redirect_to );
        return $redirect_to;
    }

    /**
     * Displays an admin notice after the bulk action completes.
     */
    public static function show_admin_notice() {
        if ( ! isset( $_GET['sfwf_forecast_updated'] ) ) {
            return;
        }
        $count = intval( $_GET['sfwf_forecast_updated'] );
        if ( $count <= 0 ) {
            return;
        }
        // Use plural form based on number processed.
        $message = sprintf( _n( '%d product forecast updated.', '%d products forecast updated.', $count, 'aaa-wf-sfwf' ), $count );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
    }
}

// Immediately initialize the bulk actions when this file is loaded.
WF_SFWF_Forecast_Bulk_Actions::init();