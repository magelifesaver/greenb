<?php
/**
 * Filepath: sfwf/index/class-wf-sfwf-forecast-status.php
 * ---------------------------------------------------------------------------
 * Calculates status flags based on product lifecycle and velocity:
 * - forecast_sales_status (tiered)
 * - forecast_is_stale_inventory
 * - forecast_is_out_of_stock
 * - forecast_is_new_product (optional)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WF_SFWF_Forecast_Status {

    /**
     * Evaluate all sales-related status flags for this product.
     *
     * @param WC_Product $product
     * @param array $input {
     *     @type int    $stock
     *     @type int    $total_units_sold
     *     @type float  $sales_day
     *     @type string $first_sold (Y-m-d)
     *     @type string $last_sold  (Y-m-d)
     *     @type string $last_purchased (Y-m-d)
     *     @type int    $sales_window_days (shelf life)
     *     @type int    $minimum_stock
     * }
     * @return array
     */
    public static function evaluate( $product, $input ) {
        $now = current_time('timestamp');

        $stock        = $input['stock'];
        $sales_total  = $input['total_units_sold'];
        $sales_day    = $input['sales_day'];
        $first_sold   = $input['first_sold'];
        $last_sold    = $input['last_sold'];
        $last_po      = $input['last_purchased'];
        $shelf_life   = $input['sales_window_days'];
        $min_stock    = $input['minimum_stock'];

        $status_t1 = WF_SFWF_Settings::get('not_moving_t1_days', 14);
        $status_t2 = WF_SFWF_Settings::get('not_moving_t2_days', 30);
        $status_t3 = WF_SFWF_Settings::get('not_moving_t3_after_best_sold_by', 15);

        $sales_status = 'active';

        // Out of stock flag
        $is_oos = ($stock <= 0) ? 'yes' : 'no';

        // Not moving tiers
        $days_since_sale = $last_sold ? floor( ($now - strtotime($last_sold)) / DAY_IN_SECONDS ) : null;

        if ( $sales_total == 0 ) {
            $sales_status = 'not_moving_t1';
        } elseif ( $days_since_sale !== null ) {
            if ( $days_since_sale >= $status_t2 ) {
                $sales_status = 'not_moving_t2';
            } elseif ( $days_since_sale >= $status_t1 ) {
                $sales_status = 'not_moving_t1';
            }

            // Tier 3: expired shelf life after last purchase
            if ( $last_po ) {
                $expire_at = strtotime("+{$shelf_life} days", strtotime($last_po));
                $expire_with_buffer = strtotime("+{$status_t3} days", $expire_at);
                if ( $now > $expire_with_buffer && $stock > $min_stock ) {
                    $sales_status = 'not_moving_t3';
                }
            }
        }

        // Stale inventory flag (same expiration check)
        $is_stale = 'no';
        if ( $last_po && $stock > $min_stock ) {
            $expire_at = strtotime("+{$shelf_life} days", strtotime($last_po));
            $expire_with_buffer = strtotime("+{$status_t3} days", $expire_at);
            if ( $now > $expire_with_buffer ) {
                $is_stale = 'yes';
            }
        }

        // Optional: new product flag (if first sale/purchase is recent)
        $is_new = 'no';
        if ( $first_sold ) {
            $days_since_created = floor( ( $now - strtotime($first_sold) ) / DAY_IN_SECONDS );
            if ( $days_since_created <= 7 && $sales_total <= 1 ) {
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
