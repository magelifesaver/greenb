<?php
/**
 * Filepath: sfwf/helpers/forecast-column-definitions.php
 * ---------------------------------------------------------------------------
 * Central list of all forecast product meta fields used in grids/reports.
 * Defines visibility, sorting, filtering, formatting, and labels.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SFWF_Column_Definitions {

    public static function get_columns() {
        return [

            // 🔢 Forecast calculations
            'forecast_stock_qty' => [
                'label'      => 'Stock Qty',
                'type'       => 'number',
                'sortable'   => true,
                'filterable' => false,
                // Columns are grouped to allow UI toggling in the forecast grid. See views/forecast-dashboard.php
                'group'      => 'Inventory',
            ],
            'forecast_total_units_sold' => [
                'label'      => 'Total Units Sold',
                'type'       => 'number',
                'sortable'   => true,
                'filterable' => false,
                'group'      => 'Sales',
            ],
            'forecast_sales_day' => [
                'label'      => 'Sales/Day (Window)',
                'type'       => 'text',
                'sortable'   => false,
                'filterable' => false,
                'group'      => 'Sales',
            ],
            'forecast_sales_day_lifetime' => [
                'label'      => 'Sales/Day (Lifetime)',
                'type'       => 'text',
                'sortable'   => false,
                'filterable' => false,
                'group'      => 'Sales',
            ],
            'forecast_sales_month' => [
                'label'      => 'Sales/Month',
                'type'       => 'number',
                'sortable'   => true,
                'filterable' => true,
                'format'     => 'decimal',
                'group'      => 'Sales',
            ],
            'forecast_oos_date' => [
                'label'      => 'OOS Projected',
                'type'       => 'date',
                'sortable'   => true,
                'filterable' => true,
                'group'      => 'Forecast',
            ],
            'forecast_reorder_date' => [
                'label'      => 'Reorder Date',
                'type'       => 'date',
                'sortable'   => true,
                'filterable' => true,
                'group'      => 'Forecast',
            ],
            'forecast_margin_percent' => [
                'label'      => 'Margin (%)',
                'type'       => 'percent',
                'sortable'   => true,
                'filterable' => false,
                'group'      => 'Financial',
            ],
            'forecast_frozen_capital' => [
                'label'      => 'Frozen Capital',
                'type'       => 'currency',
                'sortable'   => true,
                'filterable' => false,
                'group'      => 'Financial',
            ],
            'forecast_po_priority_score' => [
                'label'      => 'PO Priority',
                'type'       => 'number',
                'sortable'   => true,
                'filterable' => true,
                'group'      => 'Forecast',
            ],

            // 🛒 Editable controls
            'forecast_lead_time_days' => [
                'label'      => 'Lead Time (days)',
                'type'       => 'number',
                'sortable'   => true,
                'filterable' => true,
                'group'      => 'Forecast',
            ],
            'forecast_minimum_order_qty' => [
                'label'      => 'Min Order Qty',
                'type'       => 'number',
                'sortable'   => true,
                'filterable' => true,
                'group'      => 'Forecast',
            ],
            'forecast_sales_window_days' => [
                'label'      => 'Best Sold By (Days)',
                'type'       => 'number',
                'sortable'   => false,
                'filterable' => false,
                'group'      => 'Forecast',
            ],
            'forecast_cost_override' => [
                'label'      => 'Cost Override (%)',
                'type'       => 'percent',
                'sortable'   => false,
                'filterable' => false,
                'group'      => 'Forecast',
            ],
            'forecast_product_class' => [
                'label'      => 'Product Class',
                'type'       => 'text',
                'sortable'   => true,
                'filterable' => true,
                'group'      => 'Forecast',
            ],

            // ✅ Flags
            'forecast_enable_reorder' => [
                'label'      => 'Enabled?',
                'type'       => 'boolean',
                'sortable'   => true,
                'filterable' => true,
                'group'      => 'Flags',
            ],
            'forecast_do_not_reorder' => [
                'label'      => 'Do Not Reorder',
                'type'       => 'boolean',
                'sortable'   => true,
                'filterable' => true,
                'group'      => 'Flags',
            ],
            'forecast_is_must_stock' => [
                'label'      => 'Must Stock',
                'type'       => 'boolean',
                'sortable'   => true,
                'filterable' => true,
                'group'      => 'Inventory',
            ],
            'forecast_force_reorder' => [
                'label'      => 'Force Reorder',
                'type'       => 'boolean',
                'sortable'   => false,
                'filterable' => true,
                'group'      => 'Flags',
            ],
            'forecast_flag_for_review' => [
                'label'      => 'Flagged for Review',
                'type'       => 'boolean',
                'sortable'   => false,
                'filterable' => true,
                'group'      => 'Flags',
            ],
            'forecast_is_not_moving' => [
                'label'      => 'Not Moving',
                'type'       => 'boolean',
                'sortable'   => false,
                'filterable' => true,
                'group'      => 'Forecast',
            ],
            'forecast_is_new_product' => [
                'label'      => 'New Product',
                'type'       => 'boolean',
                'sortable'   => false,
                'filterable' => true,
                'group'      => 'Forecast',
            ],
            'forecast_sales_status' => [
                'label'      => 'Sales Status',
                'type'       => 'text',
                'sortable'   => true,
                'filterable' => true,
                'group'      => 'Sales',
            ],
            'forecast_is_out_of_stock' => [
                'label'      => 'Is Out of Stock',
                'type'       => 'boolean',
                'sortable'   => false,
                'filterable' => true,
                'group'      => 'Inventory',
            ],
            'forecast_is_stale_inventory' => [
                'label'      => 'Is Stale Inventory',
                'type'       => 'boolean',
                'sortable'   => false,
                'filterable' => true,
                'group'      => 'Inventory',
            ],
            'forecast_reorder_note' => [
                'label'      => 'Reorder Note',
                'type'       => 'text',
                'sortable'   => false,
                'filterable' => false,
                'group'      => 'Flags',
            ],

            // 📅 Lifecycle
            'forecast_first_sold_date' => [
                'label'      => 'First Sold',
                'type'       => 'date',
                'sortable'   => true,
                'filterable' => false,
                'group'      => 'Sales',
            ],
            'forecast_last_sold_date' => [
                'label'      => 'Last Sold',
                'type'       => 'date',
                'sortable'   => true,
                'filterable' => false,
                'group'      => 'Sales',
            ],
            'forecast_first_purchased' => [
                'label'      => 'First Purchased',
                'type'       => 'date',
                'sortable'   => true,
                'filterable' => false,
                'group'      => 'Lifecycle',
            ],
            'forecast_last_purchased' => [
                'label'      => 'Last Purchased',
                'type'       => 'date',
                'sortable'   => true,
                'filterable' => false,
                'group'      => 'Lifecycle',
            ],
        ];
    }
}
