<?php
/**
 * Filepath: sfwf/helpers/class-wf-sfwf-product-fields.php (modified for simple products only)
 * ---------------------------------------------------------------------------
 * Adds forecasting fields to WooCommerce product edit screen.  This version
 * contains surgical changes to ensure that the forecasting custom fields apply
 * only to WooCommerce *simple* products.  All class names and function names
 * remain unchanged; only the logic inside is amended.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WF_SFWF_Product_Fields {

    public static function init() {
        add_action( 'woocommerce_product_options_general_product_data', [ __CLASS__, 'add_fields' ] );
        add_action( 'woocommerce_process_product_meta', [ __CLASS__, 'save_fields' ] );
    }

    /**
     * Add forecasting fields to the product edit screen.
     *
     * This implementation checks the product type and only outputs fields
     * when editing a simple product.  If the current product is not a
     * ``simple`` product, the function returns early, leaving the
     * rest of the page unchanged.
     */
    public static function add_fields() {
        // Determine the current product in the admin.  WooCommerce exposes
        // ``$product_object`` as a global when editing a product.  Fallback
        // to wc_get_product(get_the_ID()) if the global isn't set.
        $product = null;
        if ( isset( $GLOBALS['product_object'] ) && $GLOBALS['product_object'] instanceof WC_Product ) {
            $product = $GLOBALS['product_object'];
        } elseif ( function_exists( 'wc_get_product' ) ) {
            $post_id = get_the_ID();
            if ( $post_id ) {
                $product = wc_get_product( $post_id );
            }
        }

        // Bail out if we don't have a product or if it's not a simple type.
        // Use WC_Product::is_type() to check product types【539457720013971†L29-L58】.
        if ( ! $product || ! $product->is_type( 'simple' ) ) {
            return;
        }

        echo '<div class="options_group"><hr/><h4>Stock Forecasting</h4>';

        // Lead Time (days)
        woocommerce_wp_text_input([
            'id'            => 'forecast_lead_time_days',
            'label'         => 'Lead Time (days)',
            'type'          => 'number',
            'description'   => 'Fallback to global if empty',
            // Only display for simple products【142306055597918†L185-L193】.
            'wrapper_class' => 'show_if_simple',
        ]);

        // Minimum Order Quantity
        woocommerce_wp_text_input([
            'id'            => 'forecast_minimum_order_qty',
            'label'         => 'Minimum Order Quantity',
            'type'          => 'number',
            'wrapper_class' => 'show_if_simple',
        ]);

        // Tier Thresholds
        woocommerce_wp_text_input([
            'id'                => 'forecast_tier_threshold_1',
            'label'             => 'Tier 1 Threshold (Days)',
            'type'              => 'number',
            'custom_attributes' => [ 'step' => '1', 'min' => '0' ],
            'desc_tip'          => true,
            'description'       => 'Number of days beyond expected interval to trigger Tier 1 warning.',
            'wrapper_class'     => 'show_if_simple',
        ]);
        woocommerce_wp_text_input([
            'id'                => 'forecast_tier_threshold_2',
            'label'             => 'Tier 2 Threshold (Days)',
            'type'              => 'number',
            'custom_attributes' => [ 'step' => '1', 'min' => '0' ],
            'desc_tip'          => true,
            'description'       => 'Number of days to trigger Tier 2 (moderate concern).',
            'wrapper_class'     => 'show_if_simple',
        ]);
        woocommerce_wp_text_input([
            'id'                => 'forecast_tier_threshold_3',
            'label'             => 'Tier 3 Threshold (Days)',
            'type'              => 'number',
            'custom_attributes' => [ 'step' => '1', 'min' => '0' ],
            'desc_tip'          => true,
            'description'       => 'Maximum allowed delay before product is flagged as unsellable.',
            'wrapper_class'     => 'show_if_simple',
        ]);

        // Sales Window (days)
        woocommerce_wp_text_input([
            'id'            => 'forecast_sales_window_days',
            'label'         => 'Sales Window (days)',
            'type'          => 'number',
            'wrapper_class' => 'show_if_simple',
        ]);

        // Cost Override (%)
        woocommerce_wp_text_input([
            'id'                => 'forecast_cost_override',
            'label'             => 'Cost Override (%)',
            'type'              => 'number',
            'description'       => 'Only used if ATUM or Woo COGS is missing. Overrides global cost %.',
            'custom_attributes' => [ 'step' => '0.01' ],
            'wrapper_class'     => 'show_if_simple',
        ]);

        // Product Class
        woocommerce_wp_select([
            'id'            => 'forecast_product_class',
            'label'         => 'Product Class',
            'options'       => [
                'regular'    => 'Regular',
                'seasonal'   => 'Seasonal',
                'limited'    => 'Limited',
                'disposable' => 'Disposable',
            ],
            'wrapper_class' => 'show_if_simple',
        ]);

        // Checkboxes
        woocommerce_wp_checkbox([
            'id'            => 'forecast_enable_reorder',
            'label'         => 'Enable Reorder',
            'wrapper_class' => 'show_if_simple',
        ]);
        woocommerce_wp_checkbox([
            'id'            => 'forecast_do_not_reorder',
            'label'         => 'Do Not Reorder',
            'wrapper_class' => 'show_if_simple',
        ]);
        woocommerce_wp_checkbox([
            'id'            => 'forecast_is_must_stock',
            'label'         => 'Must Stock',
            'wrapper_class' => 'show_if_simple',
        ]);
        woocommerce_wp_checkbox([
            'id'            => 'forecast_force_reorder',
            'label'         => 'Force Reorder',
            'wrapper_class' => 'show_if_simple',
        ]);
        woocommerce_wp_checkbox([
            'id'            => 'forecast_flag_for_review',
            'label'         => 'Flag for Review',
            'wrapper_class' => 'show_if_simple',
        ]);

        // Reorder Note
        woocommerce_wp_textarea_input([
            'id'            => 'forecast_reorder_note',
            'label'         => 'Reorder Note',
            'description'   => 'Optional internal note for PO or exclusion reasons.',
            'wrapper_class' => 'show_if_simple',
        ]);

        echo '</div>';
    }

    /**
     * Save forecasting fields when the product is saved.
     *
     * This implementation only processes forecast meta for simple products.
     * If the current product is not simple, it returns early without
     * touching any forecast meta keys.
     *
     * @param int $post_id Product ID
     */
    public static function save_fields( $post_id ) {
        // Load the WC_Product and ensure it's a simple product.  Bail if not.
        $product = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : null;
        if ( ! $product || ! $product->is_type( 'simple' ) ) {
            return;
        }

        $fields = Forecast_Meta_Registry::get_keys();

        foreach ( $fields as $key => $default ) {
            if ( isset( $_POST[ $key ] ) && $_POST[ $key ] !== '' ) {
                update_post_meta( $post_id, $key, wc_clean( $_POST[ $key ] ) );
            } else {
                if ( in_array( $key, [
                    'forecast_enable_reorder',
                    'forecast_do_not_reorder',
                    'forecast_is_must_stock',
                    'forecast_force_reorder',
                    'forecast_flag_for_review',
                ], true ) ) {
                    update_post_meta( $post_id, $key, 'no' );
                } else {
                    delete_post_meta( $post_id, $key );
                }
            }
        }
    }
}

WF_SFWF_Product_Fields::init();