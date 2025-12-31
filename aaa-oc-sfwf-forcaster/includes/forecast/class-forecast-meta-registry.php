<?php
/**
 * Forecast meta registry and Global Cart ignore logic.
 *
 * Place this file in wp-content/mu-plugins/ (or include it early in your plugin)
 * so it is loaded before the Global Cart product synchronization runs.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Centralised registry of product meta keys used in forecasting.
 */
class Forecast_Meta_Registry {
    /**
     * Return all forecast meta keys with default values.
     */
    public static function get_keys() {
        return [
            // Forecast calculations
            'forecast_sales_month'      => 0,
            'forecast_sales_day'        => 0,
            'forecast_sales_day_lifetime' => 0, // lifetime/day metric
            'forecast_stock_qty'        => 0,
            'forecast_oos_date'         => '',
            'forecast_reorder_date'     => '',
            'forecast_margin_percent'   => 0,
            'forecast_frozen_capital'   => 0,
            'forecast_daily_sales_rate' => 0,

            // Purchase logic
            'forecast_lead_time_days'    => '',
            'forecast_minimum_order_qty' => '',
            'forecast_sales_window_days' => '',
            'forecast_cost_override'     => '',
            'forecast_minimum_stock'     => '',
            'forecast_tier_threshold_1'  => '',
            'forecast_tier_threshold_2'  => '',
            'forecast_tier_threshold_3'  => '',

            // Lifecycle tracking
            'forecast_first_sold_date'  => '',
            'forecast_last_sold_date'   => '',
            'forecast_first_purchased'  => '',
            'forecast_last_purchased'   => '',

            // Control flags
            'forecast_enable_reorder'     => 'yes',
            'forecast_po_priority_score'   => 0,
            'forecast_product_class'       => 'regular',
            'forecast_do_not_reorder'      => 'no',
            'forecast_is_not_moving'       => 'no',
            'forecast_is_must_stock'       => 'no',
            'forecast_is_new_product'      => 'no',
            'forecast_force_reorder'       => 'no',
            'forecast_flag_for_review'     => 'no',
            'forecast_mark_for_clearance'  => 'no',
            'forecast_mark_for_removal'    => 'no',
            'forecast_reorder_note'        => '',

            // Maintenance
            'forecast_updated_at'   => '',

            // Tiered product sales movement classification
            'forecast_sales_status' => 'active',
        ];
    }
}

/**
 * Ignore forecasting metadata during Global Cart product synchronisation.
 */
add_filter( 'woogc/ps/synchronize_product/ignore_meta_key', 'sfwf_ignore_forecasting_meta', 10, 6 );
function sfwf_ignore_forecasting_meta( $ignore, $prop_title, $prop_value, $child_product, $main_product_data, $origin_blog_id ) {
    // Bundled forecast metas (arrays) to skip.
    $bundled_keys = [
        '_sfwf_forecast_flags',
        '_sfwf_forecast_settings',
        '_sfwf_forecast_metrics',
    ];

    // Individual forecast meta keys to skip.
    $forecast_keys = [
        // Sales & stock metrics
        'forecast_stock_qty',
        'forecast_total_units_sold',
        'forecast_sales_day',
        'forecast_sales_day_lifetime',
        'forecast_sales_month',
        'forecast_margin_percent',
        'forecast_frozen_capital',
        'forecast_daily_sales_rate',
        'forecast_po_priority_score',
        // Dates
        'forecast_oos_date',
        'forecast_reorder_date',
        'forecast_first_sold_date',
        'forecast_last_sold_date',
        'forecast_first_purchased',
        'forecast_last_purchased',
        // Purchase logic
        'forecast_lead_time_days',
        'forecast_minimum_order_qty',
        'forecast_sales_window_days',
        'forecast_cost_override',
        'forecast_minimum_stock',
        'forecast_tier_threshold_1',
        'forecast_tier_threshold_2',
        'forecast_tier_threshold_3',
        // Product settings & flags
        'forecast_product_class',
        'forecast_enable_reorder',
        'forecast_do_not_reorder',
        'forecast_is_must_stock',
        'forecast_force_reorder',
        'forecast_flag_for_review',
        'forecast_is_not_moving',
        'forecast_is_new_product',
        'forecast_is_out_of_stock',
        'forecast_is_stale_inventory',
        'forecast_mark_for_clearance',
        'forecast_mark_for_removal',
        'forecast_sales_status',
        'forecast_reorder_note',
        // Maintenance
        'forecast_updated_at',
    ];

    // Skip aggregated bundles.
    if ( in_array( $prop_title, $bundled_keys, true ) ) {
        return true;
    }

    // Skip individual forecast fields.
    if ( in_array( $prop_title, $forecast_keys, true ) ) {
        return true;
    }

    // Fallback: skip any meta key beginning with 'forecast_'.
    if ( strpos( $prop_title, 'forecast_' ) === 0 ) {
        return true;
    }

    return $ignore;
}
