<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/index/class-aaa-oc-forecast-indexer.php
 * Purpose: Provides insert/update/delete operations for the forecast index table
 *          and hooks into WooCommerce stock events to enqueue products for
 *          forecasting.  The indexer relies on the row builder to cast
 *          meta values and uses wpdb->replace for efficient upsert.  When
 *          products are queued, the queue processor will call upsert_now()
 *          to write the latest forecast data.  This class keeps its
 *          responsibilities limited to database operations and enqueuing.
 *
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Indexer {

    /**
     * Initialise hooks for queueing products when stock or settings change.
     */
    public static function init(): void {
        // Queue products when stock is reduced after an order is processed
        add_action( 'woocommerce_reduce_order_stock', [ __CLASS__, 'handle_order_stock_reduction' ], 10, 1 );
        // Queue products when manual stock adjustment occurs
        add_action( 'woocommerce_product_set_stock', [ __CLASS__, 'handle_product_set_stock' ], 10, 1 );
        // When reorder enable flag toggled, queue for forecasting
        add_action( 'updated_post_meta', [ __CLASS__, 'handle_updated_post_meta' ], 10, 4 );
    }

    /**
     * Upsert (insert or update) a forecast row for the given product.
     *
     * @param int    $product_id Product ID.
     * @param string $src        Optional source label for logging/debug.
     */
    public static function upsert_now( int $product_id, string $src = '' ) : void {
        global $wpdb;
        // Ensure table exists
        if ( class_exists( 'AAA_OC_Forecast_Table_Installer' ) ) {
            AAA_OC_Forecast_Table_Installer::init();
        }
        // Build row
        $row = AAA_OC_Forecast_Row_Builder::build_row( $product_id );
        if ( empty( $row ) ) {
            return;
        }
        // Build format string for wpdb->replace
        $formats = [];
        foreach ( $row as $value ) {
            if ( is_int( $value ) ) {
                $formats[] = '%d';
            } elseif ( is_float( $value ) ) {
                $formats[] = '%f';
            } else {
                $formats[] = '%s';
            }
        }
        // Replace into table
        $wpdb->replace( AAA_OC_FORECAST_INDEX_TABLE, $row, $formats );
    }

    /**
     * Delete a product row from the forecast index.  Used when reorder is
     * disabled and the product should no longer appear in the grid.
     *
     * @param int $product_id Product ID.
     */
    public static function delete_row( int $product_id ) : void {
        global $wpdb;
        $wpdb->delete( AAA_OC_FORECAST_INDEX_TABLE, [ 'product_id' => $product_id ], [ '%d' ] );
    }

    /**
     * Rebuild the entire forecast index for all products that have reorder enabled.
     * This should be run once on activation or when the index table is empty.
     */
    public static function index_all_products() : void {
        // Only run in an admin context to avoid unnecessary work on front end.
        if ( ! is_admin() ) {
            return;
        }
        // Fetch all published products. Use IDs only to minimise memory usage.
        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];
        $product_ids = get_posts( $args );
        if ( empty( $product_ids ) ) {
            return;
        }
        foreach ( $product_ids as $pid ) {
            $enabled = get_post_meta( $pid, 'forecast_enable_reorder', true );
            if ( $enabled === 'yes' || $enabled === 1 ) {
                self::upsert_now( $pid, 'initial' );
            } else {
                // Ensure rows for disabled products are removed.
                self::delete_row( $pid );
            }
        }
    }
    /**
     * Return all rows from the forecast index table.  Used by the admin grid.
     *
     * @return array[] Array of associative rows.
     */
    public static function get_all_rows() : array {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM " . AAA_OC_FORECAST_INDEX_TABLE, ARRAY_A );
    }

    /* ---------------------------------------------------------------------
     * Hooks for queueing products
     *
     * These handlers detect relevant events and enqueue products for
     * forecasting.  They do not perform the forecasting themselves.
     */

    /**
     * Handle order stock reduction.  Receives the order ID and loops over
     * each order item to enqueue the associated product for forecasting.
     *
     * @param int|WC_Order $order The order object or order ID.
     */
    public static function handle_order_stock_reduction( $order ) : void {
        // Accept both order objects and IDs
        if ( is_numeric( $order ) ) {
            $order = wc_get_order( $order );
        }
        if ( ! $order || ! $order instanceof WC_Order ) {
            return;
        }
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            if ( $product_id ) {
                // Only queue if reorder is enabled
                $enabled = get_post_meta( $product_id, 'forecast_enable_reorder', true );
                if ( $enabled === 'yes' || $enabled === 1 ) {
                    AAA_OC_Forecast_Queue::enqueue_product( $product_id );
                }
            }
        }
    }

    /**
     * Handle manual stock adjustments via wc_product set_stock().  Enqueue
     * the product for forecasting.
     *
     * @param WC_Product $product Product object.
     */
    public static function handle_product_set_stock( WC_Product $product ) : void {
        $product_id = $product->get_id();
        if ( $product_id ) {
            $enabled = get_post_meta( $product_id, 'forecast_enable_reorder', true );
            if ( $enabled === 'yes' || $enabled === 1 ) {
                AAA_OC_Forecast_Queue::enqueue_product( $product_id );
            }
        }
    }

    /**
     * When a post meta is updated, check if reorder enable field changed.
     * If toggled on, queue the product; if toggled off, remove row.
     *
     * @param int    $meta_id    Meta ID.
     * @param int    $post_id    Post ID.
     * @param string $meta_key   Meta key.
     * @param mixed  $_meta_value New value.
     */
    public static function handle_updated_post_meta( int $meta_id, int $post_id, string $meta_key, $_meta_value ) : void {
        if ( $meta_key !== 'forecast_enable_reorder' ) {
            return;
        }
        // Only act on products
        if ( get_post_type( $post_id ) !== 'product' ) {
            return;
        }
        $new = $_meta_value;
        if ( $new === 'yes' || $new === 1 ) {
            // Reorder enabled, queue product for indexing
            AAA_OC_Forecast_Queue::enqueue_product( $post_id );
        } else {
            // Reorder disabled, remove row from index
            self::delete_row( $post_id );
        }
    }
}