<?php
/**
 * Filepath: sfwf/index/class-wf-sfwf-forecast-meta-updater.php
 * ---------------------------------------------------------------------------
 * Handles writing all forecast fields to product meta.
 * Expects an array of keys => values, and applies update_post_meta.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WF_SFWF_Forecast_Meta_Updater {

    /**
     * Saves all provided forecast fields to post_meta.
     *
     * @param int   $product_id
     * @param array $fields
     */
    public static function write( $product_id, $fields ) {
        if ( ! $product_id || empty( $fields ) ) {
            return;
        }

        foreach ( $fields as $key => $value ) {
            if ( is_scalar($value) ) {
                update_post_meta( $product_id, $key, $value );
            }
        }

        update_post_meta( $product_id, 'forecast_updated_at', current_time('mysql') );
    }
}
