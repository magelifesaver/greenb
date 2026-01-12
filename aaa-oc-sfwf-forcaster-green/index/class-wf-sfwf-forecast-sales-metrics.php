<?php
/**
 * Filepath: sfwf/index/class-wf-sfwf-forecast-sales-metrics.php
 * ---------------------------------------------------------------------------
 * Calculates product-level sales metrics:
 * - forecast_total_units_sold
 * - forecast_sales_day
 * - forecast_sales_month
 * - forecast_daily_sales_rate (removed)
 * - forecast_sales_day_lifetime
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WF_SFWF_Forecast_Sales_Metrics {

    public static function calculate( $product_id, $first_sold, $last_sold, $grid_window_days ) {
        $now = current_time('timestamp');

        // Total units sold (window-based)
        $total_units_sold = self::get_sales_total( $product_id, $grid_window_days );

        // Adjusted first_sold (trimmed to window if needed)
        $earliest_allowed = strtotime("-{$grid_window_days} days", $now);
        $first_sold_ts = $first_sold ? strtotime($first_sold) : null;

        if ( $first_sold_ts && $first_sold_ts < $earliest_allowed ) {
            $adjusted_first = $earliest_allowed;
        } else {
            $adjusted_first = $first_sold_ts;
        }

        $last_sold_ts = $last_sold ? strtotime($last_sold) : null;

        // Windowed rate
        $window_days = ($adjusted_first && $last_sold_ts)
            ? max(1, round(($last_sold_ts - $adjusted_first) / DAY_IN_SECONDS))
            : 1;

        $sales_day_window = round($total_units_sold / $window_days, 2);
        $sales_month = round($sales_day_window * 30, 2);

        // Lifetime rate
        $lifetime_days = ($first_sold_ts && $last_sold_ts)
            ? max(1, round(($last_sold_ts - $first_sold_ts) / DAY_IN_SECONDS))
            : 1;

        $total_units_lifetime = self::get_sales_total( $product_id, 99999 ); // no cap
        $sales_day_lifetime = round($total_units_lifetime / $lifetime_days, 2);

        return [
            'forecast_total_units_sold'      => $total_units_sold,
            'forecast_sales_day'             => self::format_rate_with_days($sales_day_window, $window_days),
            'forecast_sales_month'           => $sales_month,
            'forecast_sales_day_lifetime'    => self::format_rate_with_days($sales_day_lifetime, $lifetime_days),
        ];
    }

    protected static function get_sales_total( $product_id, $days ) {
        global $wpdb;

        $start = date('Y-m-d H:i:s', strtotime("-$days days"));

        $sql = $wpdb->prepare("
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
        return intval($qty);
    }

    protected static function format_rate_with_days( $rate, $days ) {
        $rate = number_format((float) $rate, 2, '.', '');
        if ( strpos($rate, '.') === 0 ) {
            $rate = '0' . $rate;
        }
        return $rate . ' (' . $days . ' Days)';
    }
}
