<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/helpers/class-aaa-oc-forecast-row-builder.php
 * Purpose: Builds a single row for the forecast index table.  Reads product
 *          meta based on the column definitions and casts values to the
 *          appropriate SQL type.  This helper isolates all type casting and
 *          normalisation logic to a single location so both the indexer and
 *          queue processor can reuse it.  All rows contain basic product
 *          context (title, SKU, categories, brands) in addition to the
 *          forecast fields defined in AAA_OC_Forecast_Columns.
 *
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Row_Builder {

    /**
     * Build a row array ready for insertion into the forecast index table.
     *
     * @param int $product_id The WooCommerce product ID.
     * @return array Associative array of column => value or empty array on error.
     */
    public static function build_row( int $product_id ) : array {
        // Ensure WooCommerce functions are available.
        if ( ! function_exists( 'wc_get_product' ) ) {
            return [];
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return [];
        }

        // Basic product context
        $title = $product->get_name();
        $sku   = $product->get_sku();

        // Categories: comma separated names
        $cats = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'names' ] );
        $category_str = implode( ', ', (array) $cats );

        // Brand: attempt configured brand taxonomy slug via settings or fallbacks
        $brand_names = [];
        $brand_slug  = null;
        // If the workflow settings class exists, attempt to read a configured brand taxonomy slug
        if ( class_exists( 'AAA_OC_Settings' ) ) {
            $brand_slug = get_option( 'aaa_oc_brand_taxonomy_slug', '' );
        }
        if ( ! empty( $brand_slug ) ) {
            $brands = get_the_terms( $product_id, $brand_slug );
        } else {
            $brands = null;
        }
        if ( empty( $brands ) || is_wp_error( $brands ) ) {
            // fallback slugs commonly used in WooCommerce stores
            $brands = get_the_terms( $product_id, 'pwb-brand' );
            if ( empty( $brands ) || is_wp_error( $brands ) ) {
                $brands = get_the_terms( $product_id, 'product_brand' );
                if ( empty( $brands ) || is_wp_error( $brands ) ) {
                    $brands = [];
                }
            }
        }
        foreach ( (array) $brands as $b ) {
            if ( is_object( $b ) ) {
                $brand_names[] = $b->name;
            }
        }
        $brand_str = implode( ', ', $brand_names );

        // Helper functions to normalise values
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

        // Read the column definitions
        $columns = AAA_OC_Forecast_Columns::get_columns();

        // Fetch meta values once to avoid repeated get_post_meta calls
        $meta_cache = [];
        foreach ( array_keys( $columns ) as $meta_key ) {
            $meta_cache[ $meta_key ] = get_post_meta( $product_id, $meta_key, true );
        }

        // Build row
        $row = [
            'product_id'     => $product_id,
            'product_title'  => $title,
            'product_sku'    => $sku ?: null,
            'product_category' => $category_str ?: null,
            'product_brand'  => $brand_str ?: null,
        ];

        // Loop through each forecast meta and cast according to type
        foreach ( $columns as $key => $def ) {
            $value = $meta_cache[ $key ] ?? null;
            switch ( $def['type'] ) {
                case 'number':
                    $row[ $key ] = $to_int( $value );
                    break;
                case 'currency':
                case 'percent':
                    $row[ $key ] = $to_float( $value );
                    break;
                case 'boolean':
                    $row[ $key ] = $yn( $value );
                    break;
                case 'date':
                    $row[ $key ] = $to_date( $value );
                    break;
                case 'text':
                default:
                    $row[ $key ] = ( $value !== '' ) ? sanitize_text_field( $value ) : null;
                    break;
            }
        }

        /*
         * Derive status flags (not moving / stale) based on the last sold date and
         * user‑configured thresholds. These flags are stored as boolean
         * columns in the index table but are not saved back to post meta. If
         * there is no last sold date the product is considered new and
         * remains unflagged.
         */
        $last_sold_date = $row['forecast_last_sold_date'] ?? null;
        $days_since_last = null;
        if ( $last_sold_date ) {
            $last_ts = strtotime( $last_sold_date );
            if ( $last_ts ) {
                $days_since_last = floor( ( current_time( 'timestamp' ) - $last_ts ) / DAY_IN_SECONDS );
            }
        }
        // Load thresholds from options if available
        $not_moving_threshold = 30;
        $stale_threshold      = 60;
        if ( function_exists( 'aaa_oc_get_option' ) ) {
            $not_moving_threshold = absint( aaa_oc_get_option( 'forecast_not_moving_days', 'forecast', 30 ) );
            $stale_threshold      = absint( aaa_oc_get_option( 'forecast_stale_days', 'forecast', 60 ) );
        }
        $row['forecast_is_not_moving'] = ( $days_since_last !== null && $days_since_last >= $not_moving_threshold ) ? 1 : 0;
        $row['forecast_is_stale']      = ( $days_since_last !== null && $days_since_last >= $stale_threshold ) ? 1 : 0;

        // Timestamp for index updates
        $row['updated_at'] = current_time( 'mysql' );

        return $row;
    }
}