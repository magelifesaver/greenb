<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/helpers/class-aaa-oc-forecast-columns.php
 * Purpose: Provides a canonical list of forecast meta fields used by the
 *          forecasting module. Each entry defines the label, data type,
 *          and whether the column is sortable or filterable. This helper is
 *          used by the table installer to build typed SQL columns and by
 *          the admin grid to render human‑readable headers.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Columns {

    /**
     * Returns an associative array of forecast columns. Keys are meta keys;
     * values define label, type and UI hints. Keep this list concise (<150 lines).
     *
     * Supported types: number, text, date, percent, currency, boolean.
     * These map to SQL types in the table installer.
     *
     * @return array
     */
    public static function get_columns(): array {
        /*
         * Define all forecast columns.  Each entry specifies a human label,
         * data type and whether the column should be sortable or filterable in
         * the admin UI.  Types map to SQL types in the table installer.
         */
        return [
            // Inventory & sales metrics
            'forecast_stock_qty' => [ 'label' => 'Stock Qty', 'type' => 'number', 'sortable' => true,  'filterable' => false ],
            'forecast_total_units_sold' => [ 'label' => 'Total Units Sold', 'type' => 'number', 'sortable' => true,  'filterable' => false ],
            'forecast_sales_month' => [ 'label' => 'Sales/Month', 'type' => 'number', 'sortable' => true,  'filterable' => true ],
            'forecast_sales_day' => [ 'label' => 'Sales/Day', 'type' => 'number', 'sortable' => true,  'filterable' => true ],
            'forecast_sales_day_lifetime' => [ 'label' => 'Sales/Day (Lifetime)', 'type' => 'number', 'sortable' => true, 'filterable' => true ],
            'forecast_oos_date' => [ 'label' => 'OOS Projected', 'type' => 'date', 'sortable' => true,  'filterable' => true ],
            'forecast_reorder_date' => [ 'label' => 'Reorder Date', 'type' => 'date', 'sortable' => true,  'filterable' => true ],
            'forecast_margin_percent' => [ 'label' => 'Margin (%)', 'type' => 'percent', 'sortable' => true,  'filterable' => false ],
            'forecast_frozen_capital' => [ 'label' => 'Frozen Capital', 'type' => 'currency', 'sortable' => true,  'filterable' => false ],
            'forecast_po_priority_score' => [ 'label' => 'PO Score', 'type' => 'number', 'sortable' => true, 'filterable' => true ],

            // User‑editable controls
            'forecast_lead_time_days' => [ 'label' => 'Lead Time (days)', 'type' => 'number', 'sortable' => true,  'filterable' => true ],
            'forecast_minimum_order_qty' => [ 'label' => 'Min Order Qty', 'type' => 'number', 'sortable' => true,  'filterable' => true ],
            'forecast_sales_window_days' => [ 'label' => 'Sales Window (days)', 'type' => 'number', 'sortable' => false, 'filterable' => false ],
            'forecast_cost_override' => [ 'label' => 'Cost Override (%)', 'type' => 'percent', 'sortable' => false, 'filterable' => false ],
            'forecast_tier_threshold_1' => [ 'label' => 'Tier 1 (Days)', 'type' => 'number', 'sortable' => false, 'filterable' => false ],
            'forecast_tier_threshold_2' => [ 'label' => 'Tier 2 (Days)', 'type' => 'number', 'sortable' => false, 'filterable' => false ],
            'forecast_tier_threshold_3' => [ 'label' => 'Tier 3 (Days)', 'type' => 'number', 'sortable' => false, 'filterable' => false ],
            'forecast_product_class' => [ 'label' => 'Product Class', 'type' => 'text', 'sortable' => true,  'filterable' => true ],

            // Flags (user toggles)
            'forecast_enable_reorder'     => [ 'label' => 'Enable Reorder',     'type' => 'boolean', 'sortable' => true,  'filterable' => true ],
            'forecast_do_not_reorder'     => [ 'label' => 'Do Not Reorder',     'type' => 'boolean', 'sortable' => true,  'filterable' => true ],
            'forecast_is_must_stock'      => [ 'label' => 'Must Stock',         'type' => 'boolean', 'sortable' => true,  'filterable' => true ],
            'forecast_force_reorder'      => [ 'label' => 'Force Reorder',      'type' => 'boolean', 'sortable' => false, 'filterable' => true ],
            'forecast_flag_for_review'    => [ 'label' => 'Flag for Review',    'type' => 'boolean', 'sortable' => false, 'filterable' => true ],
            'forecast_mark_for_clearance' => [ 'label' => 'Clearance',          'type' => 'boolean', 'sortable' => false, 'filterable' => true ],
            'forecast_mark_for_removal'   => [ 'label' => 'Removal',            'type' => 'boolean', 'sortable' => false, 'filterable' => true ],
            'forecast_reorder_note'       => [ 'label' => 'Reorder Note',       'type' => 'text',    'sortable' => false, 'filterable' => false ],

            // Derived flags (calculated post‑save)
            'forecast_is_not_moving'      => [ 'label' => 'Not Moving',         'type' => 'boolean', 'sortable' => true,  'filterable' => true ],
            'forecast_is_stale_inventory' => [ 'label' => 'Stale Inventory',    'type' => 'boolean', 'sortable' => true,  'filterable' => true ],
            'forecast_is_out_of_stock'    => [ 'label' => 'Out of Stock',       'type' => 'boolean', 'sortable' => true,  'filterable' => true ],
            'forecast_is_new_product'     => [ 'label' => 'New Product',        'type' => 'boolean', 'sortable' => true,  'filterable' => true ],
            'forecast_sales_status'       => [ 'label' => 'Sales Status',       'type' => 'text',    'sortable' => true,  'filterable' => true ],

            // Timeline
            'forecast_first_sold_date' => [ 'label' => 'First Sold', 'type' => 'date', 'sortable' => true,  'filterable' => false ],
            'forecast_last_sold_date'  => [ 'label' => 'Last Sold',  'type' => 'date', 'sortable' => true,  'filterable' => false ],
            'forecast_first_purchased' => [ 'label' => 'First Purchased', 'type' => 'date', 'sortable' => true, 'filterable' => false ],
            'forecast_last_purchased'  => [ 'label' => 'Last Purchased',  'type' => 'date', 'sortable' => true,  'filterable' => false ],

            // AI summary
            'aip_forecast_summary' => [ 'label' => 'AI Summary', 'type' => 'text', 'sortable' => false, 'filterable' => false ],
        ];
    }
}
