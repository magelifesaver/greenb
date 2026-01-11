<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/class-aaa-oc-forecast-queue.php
 * Purpose: Manages the forecast queue table and cron processing. Products are
 *          queued for forecasting in small batches to avoid timeouts. The queue
 *          supports both forecast indexing and purchase order queueing. This
 *          class also provides wrappers for backward compatibility.
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
     * Ensures a scheduled event exists for processing the forecast queue. Runs
     * hourly by default. This hook runs on every page load but schedules
     * an event only when none is pending.
     */

    /**
     * Remove any previously scheduled recurring events for the forecast queue.
     * We use single events scheduled dynamically when items are queued. This
     * avoids cron buildup and makes queue processing traffic-friendly.
     */
    public static function clear_legacy_schedule(): void {
        // Only clear legacy *recurring* schedules. Do not clear single events that drive queue processing.
        $event = wp_get_scheduled_event( 'aaa_oc_process_forecast_queue' );
        if ( $event && ! empty( $event->schedule ) ) {
            wp_clear_scheduled_hook( 'aaa_oc_process_forecast_queue' );
        }
    }

    /**
     * Backwards compatibility wrapper. Enqueue a single product for forecasting.
     * This method proxies to queue_products_for_forecast() with a single-item
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
     * This proxies to queue_products_for_po() with a single-item array. Some
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
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE product_id = %d AND status IN ('pending','processing') LIMIT 1", $pid ) );
            if ( $exists ) {
                continue;
            }
            $wpdb->insert(
                $table,
                [
                    'product_id' => $pid,
                    'status'     => 'pending',
                    'created_at' => current_time( 'mysql' ),
                    'created_by' => $user,
                ],
                [ '%d', '%s', '%s', '%d' ]
            );
        }
        // After enqueuing items schedule a single processing event soon. The delay
        // allows batch inserts to finish before processing begins. If a
        // processing event is already scheduled it will not be duplicated.
        self::schedule_next_run( MINUTE_IN_SECONDS );
    }

    /**
     * Adds an array of product IDs to the purchase order queue table.
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
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE product_id = %d AND status IN ('pending','processing') LIMIT 1", $pid ) );
            if ( $exists ) {
                continue;
            }
            $wpdb->insert(
                $table,
                [
                    'product_id' => $pid,
                    'status'     => 'pending',
                    'created_at' => current_time( 'mysql' ),
                    'created_by' => $user,
                ],
                [ '%d', '%s', '%s', '%d' ]
            );
        }
    }

    /**
     * Process a batch of queued products. This method is triggered by cron or by
     * fallback execution. It will mark jobs processing, run the forecast runner,
     * then mark them done. Processes up to 5 per run.
     */
    public static function process_forecast_queue(): void {
        global $wpdb;

        $table = AAA_OC_FORECAST_QUEUE_TABLE;
        $rows  = $wpdb->get_results( "SELECT * FROM $table WHERE status = 'pending' ORDER BY id ASC LIMIT 5", ARRAY_A );
        if ( empty( $rows ) ) {
            return;
        }

        foreach ( $rows as $row ) {
            $id  = absint( $row['id'] );
            $pid = absint( $row['product_id'] );

            $wpdb->update(
                $table,
                [
                    'status'     => 'processing',
                    'updated_at' => current_time( 'mysql' ),
                    'updated_by' => get_current_user_id(),
                ],
                [ 'id' => $id ],
                [ '%s', '%s', '%d' ],
                [ '%d' ]
            );

            if ( $pid && class_exists( 'AAA_OC_Forecast_Runner' ) ) {
                AAA_OC_Forecast_Runner::update_single_product( $pid );
            }

            $wpdb->update(
                $table,
                [
                    'status'     => 'done',
                    'updated_at' => current_time( 'mysql' ),
                    'updated_by' => get_current_user_id(),
                ],
                [ 'id' => $id ],
                [ '%s', '%s', '%d' ],
                [ '%d' ]
            );
        }

        $more = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'pending'" );
        if ( $more > 0 ) {
            self::schedule_next_run( 5 * MINUTE_IN_SECONDS );
        }
    }

    /**
     * Schedule a single cron event for processing the forecast queue after a delay.
     * If an event is already scheduled, this call does nothing. This ensures
     * we never stack duplicate cron events.
     *
     * @param int $delay Seconds until the next run.
     */
    private static function schedule_next_run( int $delay ): void {
        if ( ! wp_next_scheduled( 'aaa_oc_process_forecast_queue' ) ) {
            wp_schedule_single_event( time() + $delay, 'aaa_oc_process_forecast_queue' );
        }
    }
}
