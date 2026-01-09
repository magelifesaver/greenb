<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/index/class-aaa-oc-forecast-runner.php
 * Purpose: Coordinates forecasting calculations for WooCommerce products.
 *          This runner orchestrates calls to timeline, sales metrics, stock
 *          metrics, projections, status evaluation and manual override
 *          retrieval. It then persists the results to post meta and
 *          updates the forecast index table. Designed to be called by
 *          queue processors or manually for bulk rebuilding.
 *
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Runner {
    /**
     * Iterate over all published products and update forecast metrics for
     * those with reorder enabled. Use with care on large stores.
     */
    public static function update_all_products() : void {
        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => -1,
        ];
        $product_ids = get_posts( $args );
        foreach ( $product_ids as $product_id ) {
            $enabled = get_post_meta( $product_id, 'forecast_enable_reorder', true );
            if ( $enabled === 'yes' || $enabled === 1 ) {
                self::update_single_product( $product_id );
            }
        }
    }

    /**
     * Perform forecast calculations for a single product.
     *
     * @param int $product_id
     */
    public static function update_single_product( int $product_id ) : void {
        $product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
        if ( ! $product ) {
            return;
        }
        // Gather timeline information
        $timeline = AAA_OC_Forecast_Timeline::get_timeline( $product_id );
        $first_sold = $timeline['forecast_first_sold_date'];
        $last_sold  = $timeline['forecast_last_sold_date'];
        $last_po    = $timeline['forecast_last_purchased'];

        // Load configuration for window and buffer
        $grid_window = function_exists( 'aaa_oc_get_option' ) ? absint( aaa_oc_get_option( 'grid_sales_window_days', 'forecast', 180 ) ) : 180;
        $min_stock   = function_exists( 'aaa_oc_get_option' ) ? absint( aaa_oc_get_option( 'global_minimum_stock_buffer', 'forecast', 0 ) ) : 0;

        // Compute sales metrics
        $sales = AAA_OC_Forecast_Sales_Metrics::calculate( $product_id, $first_sold, $last_sold, $grid_window );
        // Extract numeric daily rate from formatted string
        $sales_day_clean = floatval( preg_replace( '/[^0-9\.]/', '', $sales['forecast_sales_day'] ) );

        // Stock metrics
        $stock = AAA_OC_Forecast_Stock::calculate( $product );

        // Projections based on sales rate and inventory
        $projections = AAA_OC_Forecast_Projections::calculate( $product, $sales_day_clean );

        // Evaluate status flags
        $status = AAA_OC_Forecast_Status::evaluate( $product, [
            'stock'             => $stock['forecast_stock_qty'],
            'total_units_sold'  => $sales['forecast_total_units_sold'] ?? 0,
            'sales_day'         => $sales['forecast_sales_day'],
            'first_sold'        => $first_sold,
            'last_sold'         => $last_sold,
            'last_purchased'    => $last_po,
            'sales_window_days' => $grid_window,
            'minimum_stock'     => $min_stock,
        ] );

        // Manual overrides
        $flags = AAA_OC_Forecast_Overrides::get_flags( $product_id );

        // Merge everything into a single array for persistence
        $fields = array_merge( $timeline, $sales, $stock, $projections, $status, $flags );
        foreach ( $fields as $key => $value ) {
            update_post_meta( $product_id, $key, $value );
        }
        // Mark update time
        update_post_meta( $product_id, 'forecast_updated_at', current_time( 'mysql' ) );
        // Upsert into index table
        if ( class_exists( 'AAA_OC_Forecast_Indexer' ) ) {
            AAA_OC_Forecast_Indexer::upsert_now( $product_id, 'runner' );
        }
    }
}