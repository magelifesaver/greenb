<?php
/**
 * File: /wp-content/plugins/aaa_oc_product_forecast_index/includes/productforecast/index/class-aaa-oc-productforecast-row-builder.php
 * Purpose: Build a single row for aaa_oc_productforecast_index from product + forecast meta.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_ProductForecast_Row_Builder {

    /**
     * Build a row array (column => value) ready for wpdb->replace.
     */
    public static function build_row( int $product_id ) : array {
        $product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
        if ( ! $product ) {
            return [];
        }

        // Basic product context.
        $title = $product->get_name();
        $sku   = $product->get_sku();

        $cats = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'names' ] );
        $category_str = implode( ', ', (array) $cats );

        // Brand taxonomy: prefer berocket_brand (your known brand slug).
        $brand_names = [];
        $brands = get_the_terms( $product_id, 'berocket_brand' );
        if ( empty( $brands ) || is_wp_error( $brands ) ) {
            $brands = [];
        }
        foreach ( (array) $brands as $b ) {
            if ( is_object( $b ) ) {
                $brand_names[] = $b->name;
            }
        }
        $brand_str = implode( ', ', $brand_names );

        // Helpers.
        $yn = static function( $v ) : int {
            return ( $v === 'yes' || $v === 1 || $v === true || $v === '1' ) ? 1 : 0;
        };
        $to_int = static function( $v ) {
            return ( $v !== '' && $v !== null && is_numeric( $v ) ) ? intval( $v ) : null;
        };
        $to_float = static function( $v ) {
            return ( $v !== '' && $v !== null && is_numeric( $v ) ) ? floatval( $v ) : null;
        };
        $to_date = static function( $v ) {
            return ( ! empty( $v ) ) ? date( 'Y-m-d', strtotime( $v ) ) : null;
        };
        $num_from_string = static function( $v ) {
            if ( $v === '' || $v === null ) return null;
            if ( is_numeric( $v ) ) return floatval( $v );
            if ( preg_match( '/-?\d+(?:\.\d+)?/', (string) $v, $m ) ) {
                return floatval( $m[0] );
            }
            return null;
        };

        // Read forecast meta keys.
        $m = static function( $key ) use ( $product_id ) {
            return get_post_meta( $product_id, $key, true );
        };

        $row = [
            'product_id'                 => $product_id,
            'product_title'              => $title,
            'product_sku'                => $sku ?: null,
            'product_category'           => $category_str ?: null,
            'product_brand'              => $brand_str ?: null,

            'forecast_stock_qty'         => $to_int( $m('forecast_stock_qty') ),
            'forecast_total_units_sold'  => $to_int( $m('forecast_total_units_sold') ),
            'forecast_sales_day'         => $to_float( $num_from_string( $m('forecast_sales_day') ) ),
            'forecast_sales_day_lifetime'=> $to_float( $num_from_string( $m('forecast_sales_day_lifetime') ) ),
            'forecast_sales_month'       => $to_float( $m('forecast_sales_month') ),

            'forecast_oos_date'          => $to_date( $m('forecast_oos_date') ),
            'forecast_reorder_date'      => $to_date( $m('forecast_reorder_date') ),

            'forecast_margin_percent'    => $to_float( $num_from_string( $m('forecast_margin_percent') ) ),
            'forecast_frozen_capital'    => $to_float( $num_from_string( $m('forecast_frozen_capital') ) ),
            'forecast_po_priority_score' => $to_float( $num_from_string( $m('forecast_po_priority_score') ) ),

            'forecast_lead_time_days'    => $to_int( $m('forecast_lead_time_days') ),
            'forecast_minimum_order_qty' => $to_int( $m('forecast_minimum_order_qty') ),
            'forecast_sales_window_days' => $to_int( $m('forecast_sales_window_days') ),
            'forecast_minimum_stock'     => $to_int( $m('forecast_minimum_stock') ),
            'forecast_cost_override'     => $to_float( $num_from_string( $m('forecast_cost_override') ) ),
            'forecast_product_class'     => ( $m('forecast_product_class') !== '' ) ? sanitize_text_field( $m('forecast_product_class') ) : null,

            // IMPORTANT: meta uses yes/no. table uses 1/0.
            'forecast_enable_reorder'    => $yn( $m('forecast_enable_reorder') ),
            'forecast_do_not_reorder'    => $yn( $m('forecast_do_not_reorder') ),
            'forecast_is_must_stock'     => $yn( $m('forecast_is_must_stock') ),
            'forecast_force_reorder'     => $yn( $m('forecast_force_reorder') ),
            'forecast_flag_for_review'   => $yn( $m('forecast_flag_for_review') ),
            'forecast_is_not_moving'     => $yn( $m('forecast_is_not_moving') ),
            'forecast_is_new_product'    => $yn( $m('forecast_is_new_product') ),
            'forecast_is_out_of_stock'   => $yn( $m('forecast_is_out_of_stock') ),
            'forecast_is_stale_inventory'=> $yn( $m('forecast_is_stale_inventory') ),
            'forecast_mark_for_clearance'=> $yn( $m('forecast_mark_for_clearance') ),
            'forecast_mark_for_removal'  => $yn( $m('forecast_mark_for_removal') ),

            'forecast_sales_status'      => ( $m('forecast_sales_status') !== '' ) ? sanitize_text_field( $m('forecast_sales_status') ) : null,
            'forecast_reorder_note'      => ( $m('forecast_reorder_note') !== '' ) ? wp_kses_post( $m('forecast_reorder_note') ) : null,

            'forecast_first_sold_date'   => $to_date( $m('forecast_first_sold_date') ),
            'forecast_last_sold_date'    => $to_date( $m('forecast_last_sold_date') ),
            'forecast_first_purchased'   => $to_date( $m('forecast_first_purchased') ),
            'forecast_last_purchased'    => $to_date( $m('forecast_last_purchased') ),

            // AI summaries (JSON strings). These can be built by another plugin; we just store whatever is present.
            'aip_forecast_summary'       => ( $m('aip_forecast_summary') !== '' ) ? (string) $m('aip_forecast_summary') : null,
            'aip_historical_summary'     => ( $m('aip_historical_summary') !== '' ) ? (string) $m('aip_historical_summary') : null,

            'updated_at'                 => current_time( 'mysql' ),
        ];

        return $row;
    }
}
