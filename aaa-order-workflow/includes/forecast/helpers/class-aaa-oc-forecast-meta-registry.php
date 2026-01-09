<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/helpers/class-aaa-oc-forecast-meta-registry.php
 * Purpose: Defines the list of forecast meta keys and default values. This
 *          registry centralises all meta key names used by the forecast
 *          module. By keeping the list in one place we ensure that
 *          calculations, UI and database code remain in sync.
 *
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Meta_Registry {
    /**
     * Return an associative array of all meta keys used by the forecast
     * module along with sensible default values. Keys must remain stable
     * across revisions unless the user explicitly approves changes.
     *
     * @return array<string,mixed>
     */
    public static function get_keys() : array {
        return [
            // Core metrics
            'forecast_sales_month'        => 0,
            'forecast_sales_day'          => 0,
            'forecast_stock_qty'          => 0,
            'forecast_oos_date'           => '',
            'forecast_reorder_date'       => '',
            'forecast_margin_percent'     => 0,
            'forecast_frozen_capital'     => 0,
            'forecast_total_units_sold'   => 0,
            'forecast_sales_day_lifetime' => 0,

            // Purchase logic inputs
            'forecast_lead_time_days'     => '',
            'forecast_minimum_order_qty'  => '',
            'forecast_sales_window_days'  => '',
            'forecast_cost_override'      => '',

            // Lifecycle timestamps
            'forecast_first_sold_date'    => '',
            'forecast_last_sold_date'     => '',
            'forecast_first_purchased'    => '',
            'forecast_last_purchased'     => '',

            // Manual flags and controls
            'forecast_enable_reorder'     => 'yes',
            'forecast_po_priority_score'  => 0,
            'forecast_product_class'      => 'regular',
            'forecast_do_not_reorder'     => 'no',
            'forecast_is_not_moving'      => 'no',
            'forecast_is_must_stock'      => 'no',
            'forecast_is_new_product'     => 'no',
            'forecast_force_reorder'      => 'no',
            'forecast_flag_for_review'    => 'no',
            'forecast_mark_for_clearance' => 'no',
            'forecast_mark_for_removal'   => 'no',
            'forecast_reorder_note'       => '',

            // Housekeeping
            'forecast_updated_at'         => '',
            'forecast_sales_status'       => 'active',

            // AI / summary
            'aip_forecast_summary'        => '',
        ];
    }
}