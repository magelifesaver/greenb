<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/index/class-aaa-oc-forecast-status.php
 * Purpose: Evaluates product sales status flags based on inventory and
 *          lifecycle data. Flags include tiered not-moving statuses,
 *          out-of-stock, stale inventory and new product indicators.
 *
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Status {
    /**
     * Evaluate status flags for a product.
     *
     * @param WC_Product $product Product instance.
     * @param array      $input   Context including stock, sales and date info.
     * @return array<string,string> Flags keyed by meta key.
     */
    public static function evaluate( WC_Product $product, array $input ) : array {
        $now = current_time( 'timestamp' );
        $stock       = $input['stock'];
        $total_units = $input['total_units_sold'];
        $sales_day   = $input['sales_day'];
        $first_sold  = $input['first_sold'];
        $last_sold   = $input['last_sold'];
        $last_po     = $input['last_purchased'];
        $shelf_life  = $input['sales_window_days'];
        $min_stock   = $input['minimum_stock'];

        // Thresholds for not-moving tiers and stale determination
        $t1 = function_exists( 'aaa_oc_get_option' ) ? absint( aaa_oc_get_option( 'not_moving_t1_days', 'forecast', 14 ) ) : 14;
        $t2 = function_exists( 'aaa_oc_get_option' ) ? absint( aaa_oc_get_option( 'not_moving_t2_days', 'forecast', 30 ) ) : 30;
        $t3 = function_exists( 'aaa_oc_get_option' ) ? absint( aaa_oc_get_option( 'not_moving_t3_after_best_sold_by', 'forecast', 15 ) ) : 15;

        $sales_status = 'active';
        $is_oos   = ( $stock <= 0 ) ? 'yes' : 'no';
        $days_since_sale = null;
        if ( $last_sold ) {
            $days_since_sale = floor( ( $now - strtotime( $last_sold ) ) / DAY_IN_SECONDS );
        }
        if ( $total_units == 0 ) {
            $sales_status = 'not_moving_t1';
        } elseif ( $days_since_sale !== null ) {
            if ( $days_since_sale >= $t2 ) {
                $sales_status = 'not_moving_t2';
            } elseif ( $days_since_sale >= $t1 ) {
                $sales_status = 'not_moving_t1';
            }
            if ( $last_po ) {
                $expire_at = strtotime( '+' . $shelf_life . ' days', strtotime( $last_po ) );
                $expire_with_buffer = strtotime( '+' . $t3 . ' days', $expire_at );
                if ( $now > $expire_with_buffer && $stock > $min_stock ) {
                    $sales_status = 'not_moving_t3';
                }
            }
        }
        $is_stale = 'no';
        if ( $last_po && $stock > $min_stock ) {
            $expire_at = strtotime( '+' . $shelf_life . ' days', strtotime( $last_po ) );
            $expire_with_buffer = strtotime( '+' . $t3 . ' days', $expire_at );
            if ( $now > $expire_with_buffer ) {
                $is_stale = 'yes';
            }
        }
        // New product flag
        $is_new = 'no';
        $new_enabled = false;
        $new_days = 7;
        if ( function_exists( 'aaa_oc_get_option' ) ) {
            $new_enabled = ( aaa_oc_get_option( 'enable_new_product_threshold', 'forecast', 'no' ) === 'yes' );
            $new_days    = absint( aaa_oc_get_option( 'new_product_days_threshold', 'forecast', 7 ) );
        }
        if ( $first_sold ) {
            $days_since_created = floor( ( $now - strtotime( $first_sold ) ) / DAY_IN_SECONDS );
            if ( $new_enabled && $days_since_created <= $new_days ) {
                $is_new = 'yes';
            }
        }
        return [
            'forecast_sales_status'       => $sales_status,
            'forecast_is_out_of_stock'    => $is_oos,
            'forecast_is_stale_inventory' => $is_stale,
            'forecast_is_new_product'     => $is_new,
        ];
    }
}