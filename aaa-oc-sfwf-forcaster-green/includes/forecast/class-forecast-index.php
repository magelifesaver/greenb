<?php
/**
 * Version: 1.4.0 (2026-01-06)
 *
 * Filepath: includes/forecast/class-forecast-index.php
 * ---------------------------------------------------------------------------
 * Manages writing and updating rows in the custom forecast index table.  Each
 * product has a single row keyed by `product_id`.  This class converts the
 * forecast meta fields (numbers, dates, flags) into typed columns and stores
 * additional context such as product title, SKU, category and brand to
 * support fast filtering and sorting in the admin grid.  The summary field
 * remains in post meta for AI search but reporting should reference this
 * table exclusively.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WF_SFWF_Forecast_Index {

    /**
     * Returns the forecast index table name.
     *
     * @return string
     */
    protected static function table() {
        return WF_SFWF_Forecast_Index_Table::get_table_name();
    }

    /**
     * Upserts a row for the given product ID.  Accepts the raw $fields array
     * saved by the forecast runner and derives additional data (title, sku,
     * categories, brands).  Values are cast to appropriate types and missing
     * or malformed values become NULL.  Flags are normalised to 0/1.
     *
     * @param int   $product_id Product identifier.
     * @param array $fields     Meta fields from the forecast runner.
     */
    public static function update_index( $product_id, array $fields ) {
        global $wpdb;

        // Always ensure table exists before writes.
        WF_SFWF_Forecast_Index_Table::maybe_install();

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return;
        }

        // Basic product data
        $title = $product->get_name();
        $sku   = $product->get_sku();

        // Categories: comma separated names
        $categories = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'names' ] );
        $category_str = implode( ', ', (array) $categories );

        // Brand: attempt configured slug then fallbacks
        $brand_names = [];
        $brand_slug  = null;
        if ( class_exists( 'WF_SFWF_Settings' ) ) {
            $brand_slug = WF_SFWF_Settings::get( 'brand_taxonomy_slug', '' );
        }
        if ( ! empty( $brand_slug ) ) {
            $brands = get_the_terms( $product_id, $brand_slug );
        } else {
            $brands = null;
        }
        if ( empty( $brands ) || is_wp_error( $brands ) ) {
            // fallback slugs
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

        // Helper to normalise yes/no values to 0/1.
        $yn = function ( $v ) {
            return ( $v === 'yes' || $v === 1 || $v === true ) ? 1 : 0;
        };

        // Cast numbers with fallback to null
        $to_int = function ( $v ) {
            return ( $v !== '' && $v !== null && is_numeric( $v ) ) ? intval( $v ) : null;
        };
        $to_float = function ( $v ) {
            return ( $v !== '' && $v !== null && is_numeric( $v ) ) ? floatval( $v ) : null;
        };
        $to_date = function ( $v ) {
            return ( ! empty( $v ) ) ? date( 'Y-m-d', strtotime( $v ) ) : null;
        };

        // Derive values from fields.  All keys must exist in the table schema.
        $row = [
            'product_id'                   => $product_id,
            'product_title'                => $title,
            'product_sku'                  => $sku,
            'product_category'             => $category_str,
            'product_brand'                => $brand_str,
            'forecast_stock_qty'           => $to_int( $fields['forecast_stock_qty'] ?? null ),
            'forecast_total_units_sold'    => $to_int( $fields['forecast_total_units_sold'] ?? null ),
            'forecast_sales_day'           => $to_float( preg_replace( '/[^0-9\.\-]/', '', $fields['forecast_sales_day'] ?? '' ) ),
            'forecast_sales_day_lifetime'  => $to_float( preg_replace( '/[^0-9\.\-]/', '', $fields['forecast_sales_day_lifetime'] ?? '' ) ),
            'forecast_sales_month'         => $to_float( $fields['forecast_sales_month'] ?? null ),
            'forecast_oos_date'            => $to_date( $fields['forecast_oos_date'] ?? null ),
            'forecast_reorder_date'        => $to_date( $fields['forecast_reorder_date'] ?? null ),
            'forecast_margin_percent'      => $to_float( $fields['forecast_margin_percent'] ?? null ),
            'forecast_frozen_capital'      => $to_float( $fields['forecast_frozen_capital'] ?? null ),
            'forecast_po_priority_score'   => $to_float( $fields['forecast_po_priority_score'] ?? null ),
            'forecast_lead_time_days'      => $to_int( $fields['forecast_lead_time_days'] ?? null ),
            'forecast_minimum_order_qty'   => $to_int( $fields['forecast_minimum_order_qty'] ?? null ),
            'forecast_sales_window_days'   => $to_int( $fields['forecast_sales_window_days'] ?? null ),
            'forecast_cost_override'       => $to_float( $fields['forecast_cost_override'] ?? null ),
            'forecast_product_class'       => isset( $fields['forecast_product_class'] ) ? sanitize_text_field( $fields['forecast_product_class'] ) : null,
            'forecast_enable_reorder'      => $yn( $fields['forecast_enable_reorder'] ?? null ),
            'forecast_do_not_reorder'      => $yn( $fields['forecast_do_not_reorder'] ?? null ),
            'forecast_is_must_stock'       => $yn( $fields['forecast_is_must_stock'] ?? null ),
            'forecast_force_reorder'       => $yn( $fields['forecast_force_reorder'] ?? null ),
            'forecast_flag_for_review'     => $yn( $fields['forecast_flag_for_review'] ?? null ),
            'forecast_is_not_moving'       => $yn( $fields['forecast_is_not_moving'] ?? null ),
            'forecast_is_new_product'      => $yn( $fields['forecast_is_new_product'] ?? null ),
            'forecast_sales_status'        => isset( $fields['forecast_sales_status'] ) ? sanitize_text_field( $fields['forecast_sales_status'] ) : null,
            'forecast_is_out_of_stock'     => $yn( $fields['forecast_is_out_of_stock'] ?? null ),
            'forecast_is_stale_inventory'  => $yn( $fields['forecast_is_stale_inventory'] ?? null ),
            'forecast_reorder_note'        => isset( $fields['forecast_reorder_note'] ) ? wp_kses_post( $fields['forecast_reorder_note'] ) : null,
            'forecast_first_sold_date'     => $to_date( $fields['forecast_first_sold_date'] ?? null ),
            'forecast_last_sold_date'      => $to_date( $fields['forecast_last_sold_date'] ?? null ),
            'forecast_first_purchased'     => $to_date( $fields['forecast_first_purchased'] ?? null ),
            'forecast_last_purchased'      => $to_date( $fields['forecast_last_purchased'] ?? null ),
            'forecast_mark_for_clearance'  => $yn( $fields['forecast_mark_for_clearance'] ?? null ),
            'forecast_mark_for_removal'    => $yn( $fields['forecast_mark_for_removal'] ?? null ),
        ];

        // Use $wpdb->replace to insert or update a single row identified by product_id.
        $wpdb->replace( self::table(), $row );
    }

    /**
     * Updates the timestamp for a product without changing other data.  Used when
     * skipping forecasting due to high stock or new product thresholds.
     *
     * @param int $product_id
     */
    public static function update_product_index( $product_id ) {
        global $wpdb;
        WF_SFWF_Forecast_Index_Table::maybe_install();
        $wpdb->update(
            self::table(),
            [ 'updated_at' => current_time( 'mysql' ) ],
            [ 'product_id' => $product_id ],
            [ '%s' ],
            [ '%d' ]
        );
    }

    /**
     * Retrieves the last processed timestamp for the given product.  Returns a
     * MySQL datetime string or null if not found.  Used by the grid view.
     *
     * @param int $product_id
     * @return string|null
     */
    public static function get_last_processed( $product_id ) {
        global $wpdb;
        WF_SFWF_Forecast_Index_Table::maybe_install();
        $result = $wpdb->get_var( $wpdb->prepare( "SELECT updated_at FROM " . self::table() . " WHERE product_id = %d", $product_id ) );
        return $result ?: null;
    }

    /**
     * Query all rows from the forecast index table.  This helper returns an
     * array of associative arrays keyed by the column names.  Use this in
     * report views instead of reading from post meta.
     *
     * @return array
     */
    public static function get_all_rows() {
        global $wpdb;
        WF_SFWF_Forecast_Index_Table::maybe_install();
        $sql = "SELECT * FROM " . self::table();
        return $wpdb->get_results( $sql, ARRAY_A );
    }

    /**
     * Backwards compatible wrapper for table creation.  The loader calls
     * `WF_SFWF_Forecast_Index::create_table()` on activation.  Provide this
     * method to install the index table via the table class.  See
     * WF_SFWF_Forecast_Index_Table::install() for the schema definition.
     */
    public static function create_table() {
        WF_SFWF_Forecast_Index_Table::install();
    }
}