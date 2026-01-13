<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/index/class-aaa-oc-forecast-runner.php
 * Purpose: Coordinates forecasting calculations for WooCommerce products.
 *          This runner orchestrates calls to timeline, sales metrics, stock
 *          metrics, projections, status evaluation and manual override
 *          retrieval. It then persists the results to post meta and
 *          updates the forecast index table. Designed to be called by
 *          queue processors or manually for bulk rebuilding.
 *
 * Version: 0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Runner {
    /**
     * Iterate over all published products and update forecast metrics for
     * those with reorder enabled. Use with care on large stores.
     */
    public static function update_all_products() : void {
        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => -1,
        ];
        $product_ids = get_posts( $args );
        foreach ( $product_ids as $product_id ) {
            $enabled = get_post_meta( $product_id, 'forecast_enable_reorder', true );
            if ( $enabled === 'yes' || $enabled === 1 ) {
                self::update_single_product( $product_id );
            }
        }
    }

    /**
     * Perform forecast calculations for a single product.
     *
     * @param int $product_id
     */
    public static function update_single_product( int $product_id ) : void {
        $product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
        if ( ! $product ) {
            return;
        }
        // Gather timeline information
        $timeline = AAA_OC_Forecast_Timeline::get_timeline( $product_id );
        $first_sold = $timeline['forecast_first_sold_date'];
        $last_sold  = $timeline['forecast_last_sold_date'];
        $last_po    = $timeline['forecast_last_purchased'];

        // Load configuration for window and buffer
        $grid_window = function_exists( 'aaa_oc_get_option' ) ? absint( aaa_oc_get_option( 'grid_sales_window_days', 'forecast', 180 ) ) : 180;
        if ( function_exists( 'aaa_oc_get_option' ) ) {
            $min_stock = aaa_oc_get_option( 'global_minimum_stock', 'forecast', 0 );
            if ( $min_stock === '' || $min_stock === null ) {
                $min_stock = aaa_oc_get_option( 'global_minimum_stock_buffer', 'forecast', 0 );
            }
            $min_stock = absint( $min_stock );
        } else {
            $min_stock = 0;
        }
        $shelf_life  = self::get_global_or_product_value( $product_id, 'forecast_sales_window_days', 'global_sales_window_days', 90 );

        // Compute sales metrics
        $sales = AAA_OC_Forecast_Sales_Metrics::calculate( $product_id, $first_sold, $last_sold, $grid_window );
        // Extract numeric daily rate from formatted string
        $sales_day_clean = floatval( preg_replace( '/[^0-9\.]/', '', $sales['forecast_sales_day'] ) );

        // Stock metrics
        $stock = AAA_OC_Forecast_Stock::calculate( $product );

        // Projections based on sales rate and inventory
        $projections = AAA_OC_Forecast_Projections::calculate( $product, $sales_day_clean );

        // Evaluate status flags
        $status = AAA_OC_Forecast_Status::evaluate( $product, [
            'stock'             => $stock['forecast_stock_qty'],
            'total_units_sold'  => $sales['forecast_total_units_sold'] ?? 0,
            'sales_day'         => $sales['forecast_sales_day'],
            'first_sold'        => $first_sold,
            'last_sold'         => $last_sold,
            'last_purchased'    => $last_po,
            'sales_window_days' => $shelf_life,
            'minimum_stock'     => $min_stock,
        ] );

        // Manual overrides
        $flags = AAA_OC_Forecast_Overrides::get_flags( $product_id );

        // Merge everything into a single array for persistence
        $fields = array_merge( $timeline, $sales, $stock, $projections, $status, $flags );
        $fields['forecast_lead_time_days'] = self::get_global_or_product_value( $product_id, 'forecast_lead_time_days', 'global_lead_time_days', 7 );
        $fields['forecast_minimum_order_qty'] = self::get_global_or_product_value( $product_id, 'forecast_minimum_order_qty', 'global_minimum_order_qty', 1 );
        $fields['forecast_sales_window_days'] = $shelf_life;

        $summary_fields = self::build_summaries( $product, $fields );
        $fields = array_merge( $fields, $summary_fields );

        $mirror_keys = self::get_mirror_keys();
        foreach ( $fields as $key => $value ) {
            if ( in_array( $key, $mirror_keys, true ) ) {
                update_post_meta( $product_id, $key, $value );
            }
        }
        // Mark update time
        $updated_at = current_time( 'mysql' );
        update_post_meta( $product_id, 'forecast_updated_at', $updated_at );
        $fields['forecast_updated_at'] = $updated_at;
        // Upsert into index table
        if ( class_exists( 'AAA_OC_Forecast_Indexer' ) ) {
            AAA_OC_Forecast_Indexer::upsert_now( $product_id, 'runner', $fields );
        }
    }

    private static function get_global_or_product_value( int $product_id, string $meta_key, string $option_key, $default ) {
        $value = get_post_meta( $product_id, $meta_key, true );
        if ( $value === '' && function_exists( 'aaa_oc_get_option' ) ) {
            $value = aaa_oc_get_option( $option_key, 'forecast', $default );
        }
        return is_numeric( $value ) ? ( 0 + $value ) : $value;
    }

    private static function get_field_settings(): array {
        if ( ! function_exists( 'aaa_oc_get_option' ) ) {
            return [];
        }
        $settings = aaa_oc_get_option( 'forecast_field_settings', 'forecast', [] );
        return is_array( $settings ) ? $settings : [];
    }

    private static function get_mirror_keys(): array {
        $settings = self::get_field_settings();
        if ( empty( $settings ) ) {
            return array_keys( AAA_OC_Forecast_Meta_Registry::get_keys() );
        }
        $keys = [];
        foreach ( $settings as $key => $cfg ) {
            if ( ! array_key_exists( 'mirror', $cfg ) || ! empty( $cfg['mirror'] ) ) {
                $keys[] = $key;
            }
        }
        $keys[] = 'forecast_updated_at';
        return array_values( array_unique( $keys ) );
    }

    private static function build_summaries( WC_Product $product, array $fields ): array {
        $summaries = [
            'aip_product_summary'   => [],
            'aip_inventory_summary' => [],
            'aip_sales_summary'     => [],
            'aip_forecast_summary'  => [],
        ];

        $settings = self::get_field_settings();
        $has_config = false;
        foreach ( $settings as $cfg ) {
            if ( ! empty( $cfg['summary_groups'] ) ) {
                $has_config = true;
                break;
            }
        }

        $categories = wp_get_post_terms( $product->get_id(), 'product_cat', [ 'fields' => 'names' ] );
        $brand_slug = function_exists( 'aaa_oc_get_option' ) ? aaa_oc_get_option( 'brand_taxonomy_slug', 'forecast', 'pwb-brand' ) : 'pwb-brand';
        $brands = get_the_terms( $product->get_id(), $brand_slug );
        $brand_names = [];
        if ( is_array( $brands ) ) {
            foreach ( $brands as $brand ) {
                if ( is_object( $brand ) ) {
                    $brand_names[] = $brand->name;
                }
            }
        }

        $base_context = [
            'product_id'       => $product->get_id(),
            'product_title'    => $product->get_name(),
            'product_sku'      => $product->get_sku(),
            'product_category' => implode( ', ', (array) $categories ),
            'product_brand'    => implode( ', ', $brand_names ),
        ];

        if ( $has_config ) {
            foreach ( $settings as $key => $cfg ) {
                $groups = $cfg['summary_groups'] ?? [];
                foreach ( (array) $groups as $group ) {
                    switch ( $group ) {
                        case 'product':
                            $summaries['aip_product_summary'][ $key ] = $fields[ $key ] ?? null;
                            break;
                        case 'inventory':
                            $summaries['aip_inventory_summary'][ $key ] = $fields[ $key ] ?? null;
                            break;
                        case 'sales':
                            $summaries['aip_sales_summary'][ $key ] = $fields[ $key ] ?? null;
                            break;
                        case 'forecast':
                            $summaries['aip_forecast_summary'][ $key ] = $fields[ $key ] ?? null;
                            break;
                    }
                }
            }
        } else {
            $summaries['aip_product_summary'] = array_merge( $base_context, [
                'product_class' => $fields['forecast_product_class'] ?? '',
                'sales_status'  => $fields['forecast_sales_status'] ?? '',
            ] );
            $summaries['aip_inventory_summary'] = array_merge( $base_context, [
                'stock_qty'       => $fields['forecast_stock_qty'] ?? 0,
                'is_out_of_stock' => $fields['forecast_is_out_of_stock'] ?? 'no',
                'is_stale'        => $fields['forecast_is_stale_inventory'] ?? 'no',
                'is_new_product'  => $fields['forecast_is_new_product'] ?? 'no',
            ] );
            $summaries['aip_sales_summary'] = array_merge( $base_context, [
                'total_units_sold' => $fields['forecast_total_units_sold'] ?? 0,
                'sales_per_day'    => $fields['forecast_sales_day'] ?? '',
                'sales_per_month'  => $fields['forecast_sales_month'] ?? 0,
                'first_sold'       => $fields['forecast_first_sold_date'] ?? '',
                'last_sold'        => $fields['forecast_last_sold_date'] ?? '',
            ] );
            $summaries['aip_forecast_summary'] = array_merge( $base_context, [
                'oos_date'       => $fields['forecast_oos_date'] ?? '',
                'reorder_date'   => $fields['forecast_reorder_date'] ?? '',
                'lead_time_days' => $fields['forecast_lead_time_days'] ?? '',
                'min_order_qty'  => $fields['forecast_minimum_order_qty'] ?? '',
                'margin_percent' => $fields['forecast_margin_percent'] ?? '',
            ] );
        }

        return $summaries;
    }
}
