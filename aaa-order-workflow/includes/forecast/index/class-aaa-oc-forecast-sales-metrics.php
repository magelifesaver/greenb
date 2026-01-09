<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/index/class-aaa-oc-forecast-sales-metrics.php
 * Purpose: Computes sales metrics: total units sold, sales per day and per month,
 *          and lifetime sales rate. These calculations are based on the
 *          WooCommerce order tables and aggregate data over a configurable
 *          window. Values returned by this class are used by the forecast
 *          runner and saved to product meta and the index table.
 *
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Sales_Metrics {
    /**
     * Calculate windowed and lifetime sales metrics for a product.
     *
     * @param int         $product_id   Product ID to evaluate.
     * @param string|null $first_sold   First sold date (Y-m-d) or null.
     * @param string|null $last_sold    Last sold date (Y-m-d) or null.
     * @param int         $window_days  Number of days to include in window.
     * @return array<string,mixed>      Metric values keyed by meta key.
     */
    public static function calculate( $product_id, $first_sold, $last_sold, $window_days ) : array {
        $now = current_time( 'timestamp' );
        // Total units sold within the analysis window
        $total_units_sold = self::get_sales_total( $product_id, $window_days );
        // Determine adjusted first sold timestamp within window
        $earliest_allowed = strtotime( "-{$window_days} days", $now );
        $first_ts = $first_sold ? strtotime( $first_sold ) : null;
        $adj_first = ( $first_ts && $first_ts < $earliest_allowed ) ? $earliest_allowed : $first_ts;
        $last_ts  = $last_sold ? strtotime( $last_sold ) : null;
        // Compute days in window for rate calculation
        $window_days_calc = ( $adj_first && $last_ts ) ? max( 1, round( ( $last_ts - $adj_first ) / DAY_IN_SECONDS ) ) : 1;
        $sales_day_window = round( $total_units_sold / $window_days_calc, 2 );
        $sales_month = round( $sales_day_window * 30, 2 );
        // Lifetime units and rate (uncapped window)
        $lifetime_total  = self::get_sales_total( $product_id, 99999 );
        $lifetime_days   = ( $first_ts && $last_ts ) ? max( 1, round( ( $last_ts - $first_ts ) / DAY_IN_SECONDS ) ) : 1;
        $sales_day_life  = round( $lifetime_total / $lifetime_days, 2 );
        return [
            'forecast_total_units_sold'    => $total_units_sold,
            'forecast_sales_day'           => self::format_rate_with_days( $sales_day_window, $window_days_calc ),
            'forecast_sales_month'         => $sales_month,
            'forecast_sales_day_lifetime'  => self::format_rate_with_days( $sales_day_life, $lifetime_days ),
        ];
    }

    /**
     * Count total quantity sold for a product within a number of days.
     *
     * @param int $product_id Product ID.
     * @param int $days       Days to look back.
     * @return int            Number of units sold.
     */
    protected static function get_sales_total( $product_id, $days ) : int {
        global $wpdb;
        $start = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
        $sql = $wpdb->prepare( "
            SELECT SUM(qty.meta_value)
            FROM {$wpdb->posts} p
            JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta pid ON oi.order_item_id = pid.order_item_id
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta qty ON oi.order_item_id = qty.order_item_id
            WHERE p.post_type = 'shop_order'
              AND p.post_status IN ('wc-completed','wc-processing')
              AND p.post_date >= %s
              AND pid.meta_key = '_product_id'
              AND pid.meta_value = %d
              AND qty.meta_key = '_qty'
        ", $start, $product_id );
        $qty = $wpdb->get_var( $sql );
        return intval( $qty );
    }

    /**
     * Render a rate as a string with days included for readability.
     *
     * @param float $rate Rate value.
     * @param int   $days Number of days used in calculation.
     * @return string     Formatted string (e.g. "0.25 (15 Days)").
     */
    protected static function format_rate_with_days( $rate, $days ) : string {
        $rate = number_format( (float) $rate, 2, '.', '' );
        if ( strpos( $rate, '.' ) === 0 ) {
            $rate = '0' . $rate;
        }
        return $rate . ' (' . $days . ' Days)';
    }
}