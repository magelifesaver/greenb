<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/index/class-aaa-oc-forecast-overrides.php
 * Purpose: Retrieve manual override flags and notes for a product. These
 *          fields are entered by merchants to influence forecast logic
 *          beyond the automated calculations.
 *
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Overrides {
    /**
     * Get all override flags and notes for a product.
     *
     * @param int $product_id
     * @return array<string,string>
     */
    public static function get_flags( int $product_id ) : array {
        return [
            'forecast_force_reorder'       => self::get_flag( $product_id, 'forecast_force_reorder' ),
            'forecast_flag_for_review'     => self::get_flag( $product_id, 'forecast_flag_for_review' ),
            'forecast_do_not_reorder'      => self::get_flag( $product_id, 'forecast_do_not_reorder' ),
            'forecast_is_must_stock'       => self::get_flag( $product_id, 'forecast_is_must_stock' ),
            'forecast_mark_for_clearance'  => self::get_flag( $product_id, 'forecast_mark_for_clearance' ),
            'forecast_mark_for_removal'    => self::get_flag( $product_id, 'forecast_mark_for_removal' ),
            'forecast_reorder_note'        => get_post_meta( $product_id, 'forecast_reorder_note', true ) ?: '',
        ];
    }

    /**
     * Normalise a yes/no flag value. Defaults to 'no'.
     *
     * @param int    $product_id
     * @param string $key
     * @return string
     */
    protected static function get_flag( int $product_id, string $key ) : string {
        $val = get_post_meta( $product_id, $key, true );
        return ( $val === 'yes' ) ? 'yes' : 'no';
    }
}