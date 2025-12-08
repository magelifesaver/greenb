<?php
/**
 * Filepath: sfwf/index/class-wf-sfwf-forecast-overrides.php
 * ---------------------------------------------------------------------------
 * Retrieves all manually controlled flags and override fields:
 * - forecast_force_reorder
 * - forecast_flag_for_review
 * - forecast_do_not_reorder
 * - forecast_is_must_stock
 * - forecast_mark_for_clearance
 * - forecast_mark_for_removal
 * - forecast_reorder_note
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WF_SFWF_Forecast_Overrides {

    /**
     * Returns all override fields and manual flags for a product.
     *
     * @param int $product_id
     * @return array
     */
    public static function get_flags( $product_id ) {
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
     * Gets a normalized yes/no flag (defaults to 'no').
     *
     * @param int $product_id
     * @param string $key
     * @return string 'yes' or 'no'
     */
    protected static function get_flag( $product_id, $key ) {
        $val = get_post_meta( $product_id, $key, true );
        return ($val === 'yes') ? 'yes' : 'no';
    }
}
