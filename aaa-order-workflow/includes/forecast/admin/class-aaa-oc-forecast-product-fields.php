<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/admin/class-aaa-oc-forecast-product-fields.php
 * Purpose: Adds forecast meta fields to the WooCommerce product edit screen and
 *          saves them. This class exposes inputs for lead time, minimum
 *          order quantity, tier thresholds, sales window, cost override,
 *          product class and a series of manual flags. Only simple
 *          products show these fields. Values are stored as post meta
 *          and consumed by the forecast runner and indexer.
 *
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Product_Fields {
    /**
     * Hook our fields into WooCommerce product pages.
     */
    public static function init() : void {
        add_action( 'woocommerce_product_options_general_product_data', [ __CLASS__, 'add_fields' ] );
        add_action( 'woocommerce_process_product_meta', [ __CLASS__, 'save_fields' ] );
    }

    /**
     * Output custom forecasting fields in the product edit screen.
     */
    public static function add_fields() : void {
        // Determine current product context. WooCommerce stores the product
        // object globally during editing; otherwise fall back to wc_get_product().
        $product = null;
        if ( isset( $GLOBALS['product_object'] ) && $GLOBALS['product_object'] instanceof WC_Product ) {
            $product = $GLOBALS['product_object'];
        } elseif ( function_exists( 'wc_get_product' ) ) {
            $post_id = get_the_ID();
            if ( $post_id ) {
                $product = wc_get_product( $post_id );
            }
        }
        if ( ! $product || ! $product->is_type( 'simple' ) ) {
            return;
        }
        echo '<div class="options_group"><hr/><h4>' . esc_html__( 'Stock Forecasting', 'aaa-oc' ) . '</h4>';

        // Lead time and minimum order quantity fields
        woocommerce_wp_text_input( [
            'id'            => 'forecast_lead_time_days',
            'label'         => __( 'Lead Time (days)', 'aaa-oc' ),
            'type'          => 'number',
            'wrapper_class' => 'show_if_simple',
            'description'   => __( 'Days to receive a purchase order; fallback to global if empty.', 'aaa-oc' ),
        ] );
        woocommerce_wp_text_input( [
            'id'            => 'forecast_minimum_order_qty',
            'label'         => __( 'Minimum Order Quantity', 'aaa-oc' ),
            'type'          => 'number',
            'wrapper_class' => 'show_if_simple',
        ] );

        // Tier thresholds (legacy not‑moving tiers)
        woocommerce_wp_text_input( [
            'id'                => 'forecast_tier_threshold_1',
            'label'             => __( 'Tier 1 Threshold (Days)', 'aaa-oc' ),
            'type'              => 'number',
            'custom_attributes' => [ 'step' => '1', 'min' => '0' ],
            'wrapper_class'     => 'show_if_simple',
        ] );
        woocommerce_wp_text_input( [
            'id'                => 'forecast_tier_threshold_2',
            'label'             => __( 'Tier 2 Threshold (Days)', 'aaa-oc' ),
            'type'              => 'number',
            'custom_attributes' => [ 'step' => '1', 'min' => '0' ],
            'wrapper_class'     => 'show_if_simple',
        ] );
        woocommerce_wp_text_input( [
            'id'                => 'forecast_tier_threshold_3',
            'label'             => __( 'Tier 3 Threshold (Days)', 'aaa-oc' ),
            'type'              => 'number',
            'custom_attributes' => [ 'step' => '1', 'min' => '0' ],
            'wrapper_class'     => 'show_if_simple',
        ] );

        // Sales window and cost override
        woocommerce_wp_text_input( [
            'id'            => 'forecast_sales_window_days',
            'label'         => __( 'Sales Window (days)', 'aaa-oc' ),
            'type'          => 'number',
            'wrapper_class' => 'show_if_simple',
        ] );
        woocommerce_wp_text_input( [
            'id'                => 'forecast_cost_override',
            'label'             => __( 'Cost Override (%)', 'aaa-oc' ),
            'type'              => 'number',
            'custom_attributes' => [ 'step' => '0.01' ],
            'wrapper_class'     => 'show_if_simple',
        ] );

        // Product class select
        woocommerce_wp_select( [
            'id'            => 'forecast_product_class',
            'label'         => __( 'Product Class', 'aaa-oc' ),
            'options'       => [
                'regular'    => __( 'Regular', 'aaa-oc' ),
                'seasonal'   => __( 'Seasonal', 'aaa-oc' ),
                'limited'    => __( 'Limited', 'aaa-oc' ),
                'disposable' => __( 'Disposable', 'aaa-oc' ),
            ],
            'wrapper_class' => 'show_if_simple',
        ] );

        // Checkbox flags
        $checkboxes = [
            'forecast_enable_reorder'     => __( 'Enable Reorder', 'aaa-oc' ),
            'forecast_do_not_reorder'     => __( 'Do Not Reorder', 'aaa-oc' ),
            'forecast_is_must_stock'      => __( 'Must Stock', 'aaa-oc' ),
            'forecast_force_reorder'      => __( 'Force Reorder', 'aaa-oc' ),
            'forecast_flag_for_review'    => __( 'Flag for Review', 'aaa-oc' ),
            'forecast_mark_for_clearance' => __( 'Mark for Clearance', 'aaa-oc' ),
            'forecast_mark_for_removal'   => __( 'Mark for Removal', 'aaa-oc' ),
        ];
        foreach ( $checkboxes as $id => $label ) {
            woocommerce_wp_checkbox( [
                'id'            => $id,
                'label'         => $label,
                'wrapper_class' => 'show_if_simple',
            ] );
        }

        // Reorder note
        woocommerce_wp_textarea_input( [
            'id'            => 'forecast_reorder_note',
            'label'         => __( 'Reorder Note', 'aaa-oc' ),
            'wrapper_class' => 'show_if_simple',
        ] );

        echo '</div>';
    }

    /**
     * Save forecasting fields to post meta when a product is saved.
     *
     * @param int $post_id Product ID
     */
    public static function save_fields( int $post_id ) : void {
        $product = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : null;
        if ( ! $product || ! $product->is_type( 'simple' ) ) {
            return;
        }
        // Grab list of all forecast meta keys
        $fields = AAA_OC_Forecast_Meta_Registry::get_keys();
        // Keys that represent checkboxes; unchecked boxes should be stored as "no".
        $checkboxes = [
            'forecast_enable_reorder',
            'forecast_do_not_reorder',
            'forecast_is_must_stock',
            'forecast_force_reorder',
            'forecast_flag_for_review',
            'forecast_mark_for_clearance',
            'forecast_mark_for_removal',
        ];
        foreach ( $fields as $key => $default ) {
            if ( isset( $_POST[ $key ] ) && $_POST[ $key ] !== '' ) {
                update_post_meta( $post_id, $key, wc_clean( wp_unslash( $_POST[ $key ] ) ) );
            } else {
                if ( in_array( $key, $checkboxes, true ) ) {
                    update_post_meta( $post_id, $key, 'no' );
                } else {
                    delete_post_meta( $post_id, $key );
                }
            }
        }

        /*
         * Immediately persist forecast data to the index.  Users of this plugin are
         * non‑coders and expect the grid to update as soon as a product is saved.
         * After updating meta, call the indexer and runner to rebuild the row
         * for this product.  This avoids requiring the user to trigger a
         * background cron or bulk action.  Wrap in checks to avoid fatal
         * errors if classes are unavailable.
         */
        // Only update forecast when reorder is enabled.
        $enabled = get_post_meta( $post_id, 'forecast_enable_reorder', true );
        if ( $enabled === 'yes' || $enabled === 1 ) {
            if ( class_exists( 'AAA_OC_Forecast_Runner' ) ) {
                try {
                    AAA_OC_Forecast_Runner::update_single_product( $post_id );
                } catch ( Exception $e ) {
                    // Suppress exceptions; error_log if debugging enabled.
                    if ( defined( 'AAA_OC_FORECAST_DEBUG' ) && AAA_OC_FORECAST_DEBUG ) {
                        error_log( '[Forecast][ProductFields] Runner update failed: ' . $e->getMessage() );
                    }
                }
            }
            // Also upsert into the index table directly.
            if ( class_exists( 'AAA_OC_Forecast_Indexer' ) ) {
                AAA_OC_Forecast_Indexer::upsert_now( $post_id, 'save_fields' );
            }
        } else {
            // If reorder disabled, ensure any existing index row is removed.
            if ( class_exists( 'AAA_OC_Forecast_Indexer' ) ) {
                AAA_OC_Forecast_Indexer::delete_row( $post_id );
            }
        }
    }
}