<?php
/**
 * Version: 1.3.0 (2025-12-31)
 * Added AI summary field and updated documentation.  This version works in
 * concert with the enhanced forecast grid (views/forecast-dashboard.php).
 *
 * Filepath: includes/forecast/class-forecast-meta-registry.php
 * ---------------------------------------------------------------------------
 * Centralized registry of product meta keys used in forecasting.
 * Default values and key names defined in one place to ensure consistency.
 *
 * This version has been extended to include an additional meta field
 * `aip_forecast_summary`.  The summary key stores a JSON‑encoded object
 * containing key attributes (stock quantity, stock status, sales status and
 * flags) which can be used for machine learning or AI workflows.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Forecast_Meta_Registry {

    /**
     * Get all forecast meta keys with default values.
     *
     * @return array<string,mixed> Array of meta keys and default values.
     */
    public static function get_keys() {
        return [
            // Forecasting calculations
            'forecast_sales_month'         => 0,
            'forecast_sales_day'           => 0,
            'forecast_stock_qty'           => 0,
            'forecast_oos_date'            => '',
            'forecast_reorder_date'        => '',
            'forecast_margin_percent'      => 0,
            'forecast_frozen_capital'      => 0,
            'forecast_daily_sales_rate'    => 0,

            // Purchase logic
            'forecast_lead_time_days'      => '',
            'forecast_minimum_order_qty'   => '',
            'forecast_sales_window_days'   => '',
            'forecast_cost_override'       => '',

            // Lifecycle tracking
            'forecast_first_sold_date'     => '',
            'forecast_last_sold_date'      => '',
            'forecast_first_purchased'     => '',
            'forecast_last_purchased'      => '',

            // Control flags
            'forecast_enable_reorder'      => 'yes',
            'forecast_po_priority_score'   => 0,
            'forecast_product_class'       => 'regular',
            'forecast_do_not_reorder'      => 'no',
            'forecast_is_not_moving'       => 'no',
            'forecast_is_must_stock'       => 'no',
            'forecast_is_new_product'      => 'no',
            'forecast_force_reorder'       => 'no',
            'forecast_flag_for_review'     => 'no',
            'forecast_reorder_note'        => '',

            // Maintenance
            'forecast_updated_at'          => '',

            // Sales movement classification
            'forecast_sales_status'        => 'active',

            // AI / summary support
            // This JSON‑encoded string contains a summary of relevant forecast
            // data: stock quantity, stock status, sales status and flags.  It
            // enables downstream systems to quickly filter and process forecast
            // information without needing to read individual meta keys.
            'aip_forecast_summary'         => '',
        ];
    }
}