<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/class-aaa-oc-forecast-queue.php
 * Purpose: Manages forecasting and purchase order queues. Provides methods to
 *          enqueue products, process queues via WP‑Cron and handle bulk
 *          actions from the admin grid. Forecast processing defers heavy
 *          computations to the runner class when available.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Queue {

    /**
     * Register hooks for cron scheduling and admin handlers.
     */
    public static function init(): void {
        // Clear any legacy recurring schedules on plugin init. Prior versions
        // scheduled an hourly recurring event; we now schedule single runs
        // dynamically when items are enqueued.
        add_action( 'init', [ __CLASS__, 'clear_legacy_schedule' ] );
        // Cron callback for processing queued items.
        add_action( 'aaa_oc_process_forecast_queue', [ __CLASS__, 'process_forecast_queue' ] );
        // Admin bulk actions are handled by the grid class. Do not register
        // another handler here to avoid duplicate processing.
    }

    /**
     * Backwards compatibility wrapper. Enqueue a single product for forecasting.
     * This method proxies to queue_products_for_forecast() with a single‑item
     * array. Some existing code in the indexer and grid still calls
     * enqueue_product(); adding this wrapper avoids fatal errors.
     *
     * @param int $product_id The product ID to enqueue.
     */
    public static function enqueue_product( int $product_id ): void {
        $pid = absint( $product_id );
        if ( $pid ) {
            self::queue_products_for_forecast( [ $pid ] );
        }
    }

    /**
     * Backwards compatibility wrapper. Enqueue a single product for purchase order.
     * This proxies to queue_products_for_po() with a single‑item array. Some
     * existing grid code references enqueue_po_product(); this wrapper
     * preserves the API while delegating to the new method.
     *
     * @param int $product_id The product ID to enqueue for PO.
     */
    public static function enqueue_po_product( int $product_id ): void {
        $pid = absint( $product_id );
        if ( $pid ) {
            self::queue_products_for_po( [ $pid ] );
        }
    }

    /**
     * Ensures a scheduled event exists for processing the forecast queue. Runs
     * hourly by default. This hook runs on every page load but schedules
     * only once.
     */
    /**
     * Remove any previously scheduled recurring events for the forecast queue.
     * We use single events scheduled dynamically when items are queued. This
     * method is executed on init and ensures no leftover hourly events run.
     */
    public static function clear_legacy_schedule(): void {
        wp_clear_scheduled_hook( 'aaa_oc_process_forecast_queue' );
    }

    /**
     * Adds an array of product IDs to the forecast queue. Each ID is
     * inserted as a separate row with status pending. Duplicate entries
     * are ignored via a simple lookup.
     *
     * @param array $product_ids
     */
    public static function queue_products_for_forecast( array $product_ids ): void {
        global $wpdb;
        if ( empty( $product_ids ) ) {
            return;
        }
        $table = AAA_OC_FORECAST_QUEUE_TABLE;
        $user  = get_current_user_id();
        foreach ( $product_ids as $pid ) {
            $pid = absint( $pid );
            if ( ! $pid ) {
                continue;
            }
            // Check for existing pending/processing rows to avoid duplicates.
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE product_id = %d AND status IN ('pending','processing') LIMIT 1", $pid ) );
            if ( $exists ) {
                continue;
            }
            $wpdb->insert( $table, [
                'product_id' => $pid,
                'status'     => 'pending',
                'user_id'    => $user,
            ], [ '%d','%s','%d' ] );
        }

        // After enqueuing items schedule a single processing event soon. The delay
        // ensures heavy processing does not run during the same request. If a
        // processing event is already scheduled it will not be duplicated.
        self::schedule_next_run( MINUTE_IN_SECONDS );
    }

    /**
     * Adds products to the purchase order queue. Quantity and price are
     * optional. Duplicate pending rows are ignored. User ID is recorded
     * for accountability.
     *
     * @param array $product_ids
     */
    public static function queue_products_for_po( array $product_ids ): void {
        global $wpdb;
        if ( empty( $product_ids ) ) {
            return;
        }
        $table = AAA_OC_FORECAST_PO_QUEUE_TABLE;
        $user  = get_current_user_id();
        foreach ( $product_ids as $pid ) {
            $pid = absint( $pid );
            if ( ! $pid ) {
                continue;
            }
            // Avoid duplicate pending entries.
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE product_id = %d AND status = 'pending' LIMIT 1", $pid ) );
            if ( $exists ) {
                continue;
            }
            $wpdb->insert( $table, [
                'product_id' => $pid,
                'quantity'   => 1,
                'status'     => 'pending',
                'user_id'    => $user,
            ], [ '%d','%d','%s','%d' ] );
        }
    }

    /**
     * Cron callback: processes a subset of the forecast queue. Fetches
     * up to 5 pending rows, marks them as processing, runs the forecast
     * recalculation and then marks them as done. If the forecast runner
     * class is unavailable, rows are simply marked as done to avoid
     * indefinite retries.
     */
    public static function process_forecast_queue(): void {
        global $wpdb;
        $table = AAA_OC_FORECAST_QUEUE_TABLE;
        // Fetch the oldest pending rows (limit to prevent timeouts).
        $rows = $wpdb->get_results( "SELECT id, product_id FROM {$table} WHERE status = 'pending' ORDER BY created_at ASC LIMIT 5", ARRAY_A );
        if ( empty( $rows ) ) {
            // Nothing to process; bail early without scheduling next run.
            return;
        }
        foreach ( $rows as $row ) {
            $id  = intval( $row['id'] );
            $pid = intval( $row['product_id'] );
            // Mark as processing and increment attempts. Use direct SQL for performance.
            $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = %s, attempts = attempts + 1 WHERE id = %d", 'processing', $id ) );
            // Run forecast update via our own runner only. Legacy support has been removed.
            if ( class_exists( 'AAA_OC_Forecast_Runner' ) ) {
                try {
                    AAA_OC_Forecast_Runner::update_single_product( $pid );
                } catch ( Exception $e ) {
                    // Suppress exceptions and continue. Log in debug mode if enabled.
                    if ( defined( 'AAA_OC_FORECAST_DEBUG' ) && AAA_OC_FORECAST_DEBUG ) {
                        error_log( '[Forecast][Queue] Runner update failed for product ' . $pid . ': ' . $e->getMessage() );
                    }
                }
            }
            // Mark as done regardless of outcome.
            $wpdb->update( $table, [ 'status' => 'done' ], [ 'id' => $id ], [ '%s' ], [ '%d' ] );
        }
        // Determine if more pending rows remain and schedule next run if needed.
        $remaining = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'pending'" );
        if ( $remaining > 0 ) {
            // Schedule next run 5 minutes later. This uses a single event.
            self::schedule_next_run( 5 * MINUTE_IN_SECONDS );
        }
    }

    /**
     * Handles the admin bulk action from the forecast grid. Expects a
     * comma‑separated list of product IDs, a nonce and an action type
     * (forecast|po). Redirects back with the number of queued items.
     */
    public static function handle_bulk_action(): void {
        // Capability check.
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'aaa-oc' ) );
        }
        // Verify nonce.
        $nonce = $_POST['aaa_oc_forecast_nonce'] ?? '';
        if ( ! wp_verify_nonce( $nonce, 'aaa_oc_forecast_action' ) ) {
            wp_die( esc_html__( 'Invalid request.', 'aaa-oc' ) );
        }
        // Parse product IDs.
        $ids_str = isset( $_POST['product_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['product_ids'] ) ) : '';
        $action  = isset( $_POST['bulk_action'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) ) : '';
        $ids     = [];
        if ( $ids_str ) {
            $ids = array_filter( array_map( 'absint', explode( ',', $ids_str ) ) );
        }
        $queued = 0;
        if ( ! empty( $ids ) ) {
            if ( $action === 'forecast' ) {
                self::queue_products_for_forecast( $ids );
                $queued = count( $ids );
            } elseif ( $action === 'po' ) {
                self::queue_products_for_po( $ids );
                $queued = count( $ids );
            }
        }
        // Redirect back with queued count.
        $redirect = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=aaa-oc-forecast-grid' );
        $redirect = add_query_arg( 'queued', $queued, $redirect );
        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Schedule a single cron event for processing the forecast queue after a delay.
     * If an event is already scheduled, this call does nothing. This ensures
     * that only one processing event runs at a time. The delay is in
     * seconds; e.g. MINUTE_IN_SECONDS for immediate runs or 5*MINUTE_IN_SECONDS
     * for follow‑up processing.
     *
     * @param int $delay Seconds to wait before executing the event.
     */
    private static function schedule_next_run( int $delay ): void {
        // If there is no upcoming scheduled event for this hook, schedule one.
        if ( ! wp_next_scheduled( 'aaa_oc_process_forecast_queue' ) ) {
            wp_schedule_single_event( time() + $delay, 'aaa_oc_process_forecast_queue' );
        }
    }
}
