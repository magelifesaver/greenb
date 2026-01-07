<?php
/**
 * Filepath: sfwf/index/class-wf-sfwf-forecast-projections.php
 * ---------------------------------------------------------------------------
 * Calculates projection dates based on current sales velocity:
 * - forecast_oos_date
 * - forecast_reorder_date
 * Logs all dev calculations to logs/sfwf-forecast-debug.log
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WF_SFWF_Forecast_Projections {

    public static function calculate( $product, $sales_day ) {
        $now    = current_time('timestamp');
        $lead   = self::get_lead_time( $product );
        $sales_day = floatval( preg_replace('/[^0-9\.]/', '', $sales_day) );

        $stock   = intval( $product->get_stock_quantity() );
        $buffer  = self::get_minimum_stock( $product );
        $usable  = max( 0, $stock - $buffer );

        $log = [
            'product_id'     => $product->get_id(),
            'product_name'   => $product->get_name(),
            'stock_qty'      => $stock,
            'buffer'         => $buffer,
            'usable_stock'   => $usable,
            'sales_day'      => $sales_day,
            'lead_time_days' => $lead,
        ];

        if ( $sales_day > 0 && $usable > 0 ) {
            $days_left = round($usable / $sales_day, 2);
            $days_left = min($days_left, 730); // max cap

            $oos_date = date('Y-m-d', strtotime("+{$days_left} days", $now));
            $reorder_day = max(0, $days_left - $lead);
            $reorder_date = date('Y-m-d', strtotime("+{$reorder_day} days", $now));

            $log['days_left'] = $days_left;
            $log['oos_date'] = $oos_date;
            $log['reorder_date'] = $reorder_date;
        } else {
            $oos_date = date('Y-m-d', $now);
            $reorder_date = $oos_date;

            $log['days_left'] = 0;
            $log['oos_date'] = '[NOW]';
            $log['reorder_date'] = '[NOW]';
        }

        self::write_log($log);

        return [
            'forecast_oos_date'     => $oos_date,
            'forecast_reorder_date' => $reorder_date,
        ];
    }

    protected static function get_lead_time( $product ) {
        $value = get_post_meta( $product->get_id(), 'forecast_lead_time_days', true );
        if ( $value === '' ) {
            $value = WF_SFWF_Settings::get('global_lead_time_days', 7);
        }
        return intval($value);
    }

    protected static function get_minimum_stock( $product ) {
        $value = get_post_meta( $product->get_id(), 'forecast_minimum_stock', true );
        if ( $value === '' ) {
            $value = WF_SFWF_Settings::get('global_minimum_stock_buffer', 0);
        }
        return intval($value);
    }

    protected static function write_log( $data ) {
        $dir  = plugin_dir_path(__FILE__) . '../logs/';
        $file = $dir . 'sfwf-forecast-debug.log';

        if ( ! file_exists($dir) ) {
            mkdir($dir, 0755, true);
        }

        $entry = '[' . date('Y-m-d H:i:s') . '] ';
        $entry .= 'Forecast: ' . json_encode($data) . PHP_EOL;

        file_put_contents($file, $entry, FILE_APPEND);
    }
}
