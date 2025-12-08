<?php
/**
 * Filepath: sfwf/index/class-wf-sfwf-forecast-runner.php
 * ---------------------------------------------------------------------------
 * Main forecast runner that coordinates all forecast modules.
 * This replaces the previous monolithic update_product_forecast().
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
	    'status' => ['publish', 'private'],
            'type'   => ['simple', 'variation'],
            'limit'  => -1,
            'return' => 'ids'
        ];

        $products = wc_get_products($args);

        foreach ( $products as $product_id ) {
            $enabled = get_post_meta( $product_id, 'forecast_enable_reorder', true );
            if ( $enabled !== 'yes' ) continue;

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
        if ( ! $product || ! $product->managing_stock() ) return;

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
            'total_units_sold'   => $sales['forecast_total_units_sold'],
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

        WF_SFWF_Forecast_Meta_Updater::write( $product_id, $fields );
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
