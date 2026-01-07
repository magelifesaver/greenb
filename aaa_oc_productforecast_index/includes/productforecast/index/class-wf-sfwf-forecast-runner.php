<?php
/**
 * Version: 1.5.0 (2026-01-06)
 * Enhanced to populate the AI summary meta field (aip_forecast_summary) and
 * updated comments.  Works with forecast grid version 1.3.0.
 *
 * Filepath: index/class-wf-sfwf-forecast-runner.php
 * ---------------------------------------------------------------------------
 * Main forecast runner that coordinates all forecast modules.
 *
 * This version extends the original by adding support for a consolidated
 * `aip_forecast_summary` meta field.  After computing all forecast metrics,
 * the runner assembles a summary of key metrics (stock quantity, sales
 * status, stock status and flags) and stores it as a JSON‑encoded string.
 *
 * The original file lives in the parent plugin; this copy has been
 * modified to include additional data points in the summary.  Specifically
 * it now includes daily and total sales metrics, projected dates, and
 * extended flags.  These extra fields make the summary more useful for AI
 * applications that need a fuller picture of a product’s sales and stock
 * performance.  The added fields are backwards‑compatible: if a field is
 * unavailable it will be omitted from the summary.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WF_SFWF_Forecast_Runner {

    /**
     * Updates forecast for all reorder-enabled products.
     */
    public static function update_all_products() {
        $args = [
            'status' => ['publish'],
            // Only include simple products in the forecast. Variations are not processed here.
            'type'   => ['simple'],
            'limit'  => -1,
            'return' => 'ids'
        ];

        $products = wc_get_products($args);

        foreach ( $products as $product_id ) {
            $enable_reorder = get_post_meta( $product_id, 'forecast_enable_reorder', true );
            $do_not_reorder = get_post_meta( $product_id, 'forecast_do_not_reorder', true );
            $must_stock     = get_post_meta( $product_id, 'forecast_is_must_stock', true );
            $force_reorder  = get_post_meta( $product_id, 'forecast_force_reorder', true );
            $override       = ( $must_stock === 'yes' || $force_reorder === 'yes' );
            // Skip if product is marked do-not-reorder and has no overrides
            if ( $do_not_reorder === 'yes' && ! $override ) {
                continue;
            }
            // Skip if enable_reorder is not yes and no overrides
            if ( $enable_reorder !== 'yes' && ! $override ) {
                continue;
            }
            self::update_single_product( $product_id );
        }
    }

    /**
     * Updates forecast for a single product.
     *
     * @param int $product_id
     */
    public static function update_single_product( $product_id ) {
        $product = wc_get_product( $product_id );
        if ( ! $product || ! $product->managing_stock() ) {
            return;
        }

        // Skip forecasting based on settings: high stock and new product thresholds
        if ( class_exists( 'WF_SFWF_Settings' ) ) {
            // Stock threshold
            $enable_stock = WF_SFWF_Settings::get( 'enable_stock_threshold', 'no' ) === 'yes';
            $stock_threshold = intval( WF_SFWF_Settings::get( 'stock_threshold_qty', 0 ) );
            if ( $enable_stock && $stock_threshold > 0 ) {
                // Use get_stock_quantity() which returns null for unlimited stock
                $qty = $product->get_stock_quantity();
                if ( ! is_null( $qty ) && $qty >= $stock_threshold ) {
                    // Mark product as processed in index and skip
                    if ( class_exists( 'WF_SFWF_Forecast_Index' ) ) {
                        WF_SFWF_Forecast_Index::update_product_index( $product_id );
                    }
                    return;
                }
            }

            // New product threshold
            $enable_new = WF_SFWF_Settings::get( 'enable_new_product_threshold', 'no' ) === 'yes';
            $new_days    = intval( WF_SFWF_Settings::get( 'new_product_days_threshold', 7 ) );
            if ( $enable_new && $new_days > 0 ) {
                // Determine the earliest known date: use first sold date meta if available; otherwise compute timeline
                $first_sold_date = get_post_meta( $product_id, 'forecast_first_sold_date', true );
                if ( empty( $first_sold_date ) ) {
                    if ( class_exists( 'WF_SFWF_Forecast_Timeline' ) ) {
                        $timeline = WF_SFWF_Forecast_Timeline::get_timeline( $product_id );
                        if ( isset( $timeline['forecast_first_sold_date'] ) ) {
                            $first_sold_date = $timeline['forecast_first_sold_date'];
                        }
                    }
                }
                if ( $first_sold_date ) {
                    $days_since = floor( ( current_time( 'timestamp' ) - strtotime( $first_sold_date ) ) / DAY_IN_SECONDS );
                    if ( $days_since <= $new_days ) {
                        // Mark product as processed and skip
                        if ( class_exists( 'WF_SFWF_Forecast_Index' ) ) {
                            WF_SFWF_Forecast_Index::update_product_index( $product_id );
                        }
                        return;
                    }
                }
            }
        }
        $now = current_time('timestamp');
        $grid_window = WF_SFWF_Settings::get('grid_sales_window_days', 180);
        $min_stock   = WF_SFWF_Settings::get('global_minimum_stock', 0);

        // Step 1: Timeline
        $timeline = WF_SFWF_Forecast_Timeline::get_timeline( $product_id );
        $first_sold = $timeline['forecast_first_sold_date'];
        $last_sold  = $timeline['forecast_last_sold_date'];
        $last_po    = $timeline['forecast_last_purchased'];

        // Step 2: Sales Metrics
        $sales = WF_SFWF_Forecast_Sales_Metrics::calculate(
            $product_id,
            $first_sold,
            $last_sold,
            $grid_window
        );

        // Step 3: Stock Metrics
        $stock = WF_SFWF_Forecast_Stock::calculate( $product );

        // Step 4: Projection Dates
        $sales_day_clean = floatval( preg_replace('/[^0-9\.]/', '', $sales['forecast_sales_day']) );
        $projections = WF_SFWF_Forecast_Projections::calculate( $product, $sales_day_clean );

        // Step 5: Sales Status
        $status = WF_SFWF_Forecast_Status::evaluate( $product, [
            'stock'              => $stock['forecast_stock_qty'],
            'total_units_sold'   => $sales['forecast_total_units_sold'] ?? 0,
            'sales_day'          => $sales['forecast_sales_day'],
            'first_sold'         => $first_sold,
            'last_sold'          => $last_sold,
            'last_purchased'     => $last_po,
            'sales_window_days'  => self::get_shelf_life( $product ),
            'minimum_stock'      => $min_stock,
        ]);

        // Step 6: Manual Overrides
        $flags = WF_SFWF_Forecast_Overrides::get_flags( $product_id );

        // Final output to save
        $fields = array_merge(
            $timeline,
            $sales,
            $stock,
            $projections,
            $status,
            $flags
        );

        /*
         * Compose a summary of forecast information for AI/ML use.  The summary
         * consolidates key metrics into a single JSON object.  In this version
         * we extend the original summary to include additional sales and
         * projection data as well as extra flags.  The goal is to provide
         * downstream agents with a richer snapshot of the product state while
         * still keeping the payload concise.  Where data is unavailable, the
         * field is omitted rather than set to an empty string.
         */
        // Build a richer AI summary for the product.  The intent is to capture a
        // broad snapshot of sales velocity, inventory, lifecycle, and manual
        // overrides in a single object.  Downstream AI agents can use this data
        // to answer common questions such as “when will this product be out of
        // stock?”, “how fast is it selling?”, or “is it flagged for clearance?”.
        //
        // We intentionally map the projection keys to simplified names (e.g.
        // `oos_date`) to keep the JSON concise.  Numeric values that are empty
        // are represented as null so that json_encode omits them when filtered.
        $summary_data = [
            'stock_qty'        => $stock['forecast_stock_qty'],
            'stock_status'     => $product->get_stock_status(),
            'sales_status'     => $status['forecast_sales_status'] ?? 'active',
            // Sales velocity
            'total_units_sold' => $sales['forecast_total_units_sold'] ?? null,
            'sales_per_day'    => $sales['forecast_sales_day'] ?? null,
            'sales_per_month'  => $sales['forecast_sales_month'] ?? null,
            // Projection dates
            'oos_date'         => $projections['forecast_oos_date'] ?? null,
            'reorder_date'     => $projections['forecast_reorder_date'] ?? null,
            // Lifecycle
            'last_sold_date'   => $last_sold ?? null,
            'last_purchased'   => $last_po ?? null,
            // Manual flags (include clearance and removal)
            'flags'            => [
                'do_not_reorder'     => $flags['forecast_do_not_reorder'] ?? 'no',
                'must_stock'         => $flags['forecast_is_must_stock'] ?? 'no',
                'force_reorder'      => $flags['forecast_force_reorder'] ?? 'no',
                'flag_for_review'    => $flags['forecast_flag_for_review'] ?? 'no',
                'mark_for_clearance' => $flags['forecast_mark_for_clearance'] ?? 'no',
                'mark_for_removal'   => $flags['forecast_mark_for_removal'] ?? 'no',
            ],
            // Additional metadata
            'lead_time_days'    => $fields['forecast_lead_time_days'] ?? null,
            'min_order_qty'     => $fields['forecast_minimum_order_qty'] ?? null,
            'sales_window_days' => $fields['forecast_sales_window_days'] ?? null,
            'minimum_stock'     => $fields['forecast_minimum_stock'] ?? null,
        ];
        // Include reorder note if present
        if ( ! empty( $flags['forecast_reorder_note'] ) ) {
            $summary_data['reorder_note'] = $flags['forecast_reorder_note'];
        }
        // Filter out any null or empty values to keep the JSON lean
        $filtered_summary = array_filter( $summary_data, function( $value ) {
            // Preserve numeric zero and the flags array
            if ( is_array( $value ) ) {
                return ! empty( $value );
            }
            return $value !== null && $value !== '';
        } );
        $fields['aip_forecast_summary'] = wp_json_encode( $filtered_summary );

        // Persist all forecast meta fields.  This writes the individual meta keys
        // back to the product so existing functionality continues to work.  It
        // also stores the AI summary meta field under `aip_forecast_summary`.
        WF_SFWF_Forecast_Meta_Updater::write( $product_id, $fields );

        /*
         * Additionally write the forecast data into the custom index table.  The
         * custom table allows fast, typed queries for reporting and grid
         * filtering.  Only the summary remains in post meta for AI search.
         * Guard the call with class_exists() so this file can still be used
         * without the custom table present.
         */
        if ( class_exists( 'WF_SFWF_Forecast_Index' ) ) {
            WF_SFWF_Forecast_Index::update_index( $product_id, $fields );
        }
    }

    /**
     * Returns shelf-life days (best sold by window)
     *
     * @param WC_Product $product
     * @return int
     */
    protected static function get_shelf_life( $product ) {
        $days = get_post_meta( $product->get_id(), 'forecast_sales_window_days', true );
        if ( $days === '' ) {
            $days = WF_SFWF_Settings::get('global_sales_window_days', 90);
        }
        return intval($days);
    }
}