<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/index/class-aaa-oc-forecast-projections.php
 * Purpose: Calculates dates when a product will run out of stock and when
 *          to reorder based on daily sales velocity. Uses per-product lead
 *          time and minimum stock buffer or global fallbacks.
 *
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Projections {
    /**
     * Compute out-of-stock and reorder dates.
     *
     * @param WC_Product $product Product instance.
     * @param float      $sales_day Clean numeric sales per day rate.
     * @return array<string,string>    Dates keyed by meta key.
     */
    public static function calculate( WC_Product $product, float $sales_day ) : array {
        $now   = current_time( 'timestamp' );
        $lead  = self::get_lead_time( $product );
        $stock = intval( $product->get_stock_quantity() );
        $buffer = self::get_minimum_stock( $product );
        $usable = max( 0, $stock - $buffer );
        if ( $sales_day > 0 && $usable > 0 ) {
            $days_left = round( $usable / $sales_day, 2 );
            $days_left = min( $days_left, 730 );
            $oos_date      = date( 'Y-m-d', strtotime( "+{$days_left} days", $now ) );
            $reorder_delay = max( 0, $days_left - $lead );
            $reorder_date  = date( 'Y-m-d', strtotime( "+{$reorder_delay} days", $now ) );
        } else {
            $oos_date = date( 'Y-m-d', $now );
            $reorder_date = $oos_date;
        }
        return [
            'forecast_oos_date'     => $oos_date,
            'forecast_reorder_date' => $reorder_date,
        ];
    }

    /**
     * Fetch per-product lead time in days or global fallback.
     *
     * @param WC_Product $product
     * @return int
     */
    protected static function get_lead_time( WC_Product $product ) : int {
        $value = get_post_meta( $product->get_id(), 'forecast_lead_time_days', true );
        if ( $value === '' ) {
            $value = function_exists( 'aaa_oc_get_option' ) ? aaa_oc_get_option( 'global_lead_time_days', 'forecast', 7 ) : 7;
        }
        return intval( $value );
    }

    /**
     * Fetch per-product minimum stock buffer or global fallback.
     *
     * @param WC_Product $product
     * @return int
     */
    protected static function get_minimum_stock( WC_Product $product ) : int {
        $value = get_post_meta( $product->get_id(), 'forecast_minimum_stock', true );
        if ( $value === '' ) {
            $value = function_exists( 'aaa_oc_get_option' ) ? aaa_oc_get_option( 'global_minimum_stock_buffer', 'forecast', 0 ) : 0;
        }
        return intval( $value );
    }
}