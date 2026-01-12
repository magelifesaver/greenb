<?php
/**
 * Filepath: sfwf/helpers/class-wf-sfwf-product-fields.php
 * ---------------------------------------------------------------------------
 * Adds forecasting fields to WooCommerce product edit screen.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WF_SFWF_Product_Fields {

    public static function init() {
        add_action( 'woocommerce_product_options_general_product_data', [ __CLASS__, 'add_fields' ] );
        add_action( 'woocommerce_process_product_meta', [ __CLASS__, 'save_fields' ] );
    }

    public static function add_fields() {
        echo '<div class="options_group"><hr/><h4>Stock Forecasting</h4>';

        woocommerce_wp_text_input([
            'id'          => 'forecast_lead_time_days',
            'label'       => 'Lead Time (days)',
            'type'        => 'number',
            'description' => 'Fallback to global if empty',
        ]);

        woocommerce_wp_text_input([
            'id'          => 'forecast_minimum_order_qty',
            'label'       => 'Minimum Order Quantity',
            'type'        => 'number',
        ]);

                // Tier Thresholds
        woocommerce_wp_text_input([
            'id'                => 'forecast_tier_threshold_1',
            'label'             => 'Tier 1 Threshold (Days)',
            'type'              => 'number',
            'custom_attributes' => ['step' => '1', 'min' => '0'],
            'desc_tip'          => true,
            'description'       => 'Number of days beyond expected interval to trigger Tier 1 warning.',
        ]);

        woocommerce_wp_text_input([
            'id'                => 'forecast_tier_threshold_2',
            'label'             => 'Tier 2 Threshold (Days)',
            'type'              => 'number',
            'custom_attributes' => ['step' => '1', 'min' => '0'],
            'desc_tip'          => true,
            'description'       => 'Number of days to trigger Tier 2 (moderate concern).',
        ]);

        woocommerce_wp_text_input([
            'id'                => 'forecast_tier_threshold_3',
            'label'             => 'Tier 3 Threshold (Days)',
            'type'              => 'number',
            'custom_attributes' => ['step' => '1', 'min' => '0'],
            'desc_tip'          => true,
            'description'       => 'Maximum allowed delay before product is flagged as unsellable.',
        ]);

	woocommerce_wp_text_input([
            'id'          => 'forecast_sales_window_days',
            'label'       => 'Sales Window (days)',
            'type'        => 'number',
        ]);

	woocommerce_wp_text_input([
	    'id'          => 'forecast_cost_override',
	    'label'       => 'Cost Override (%)',
	    'type'        => 'number',
	    'description' => 'Only used if ATUM or Woo COGS is missing. Overrides global cost %.',
	    'custom_attributes' => ['step' => '0.01'],
	]);

        woocommerce_wp_select([
            'id'          => 'forecast_product_class',
            'label'       => 'Product Class',
            'options'     => [
                'regular'  => 'Regular',
                'seasonal' => 'Seasonal',
                'limited'  => 'Limited',
                'disposable' => 'Disposable',
            ]
        ]);

        woocommerce_wp_checkbox([
            'id'    => 'forecast_enable_reorder',
            'label' => 'Enable Reorder',
        ]);

        woocommerce_wp_checkbox([
            'id'    => 'forecast_do_not_reorder',
            'label' => 'Do Not Reorder',
        ]);

        woocommerce_wp_checkbox([
            'id'    => 'forecast_is_must_stock',
            'label' => 'Must Stock',
        ]);

        woocommerce_wp_checkbox([
            'id'    => 'forecast_force_reorder',
            'label' => 'Force Reorder',
        ]);

        woocommerce_wp_checkbox([
            'id'    => 'forecast_flag_for_review',
            'label' => 'Flag for Review',
        ]);

        woocommerce_wp_textarea_input([
            'id'          => 'forecast_reorder_note',
            'label'       => 'Reorder Note',
            'description' => 'Optional internal note for PO or exclusion reasons.',
        ]);

        echo '</div>';
    }

	public static function save_fields( $post_id ) {
	    $fields = Forecast_Meta_Registry::get_keys();

	    foreach ( $fields as $key => $default ) {
	        if ( isset($_POST[$key]) && $_POST[$key] !== '' ) {
	            update_post_meta( $post_id, $key, wc_clean($_POST[$key]) );
	        } else {
	            if ( in_array($key, [
	                'forecast_enable_reorder',
	                'forecast_do_not_reorder',
	                'forecast_is_must_stock',
	                'forecast_force_reorder',
	                'forecast_flag_for_review',
	            ]) ) {
	                update_post_meta( $post_id, $key, 'no' );
	            } else {
	                delete_post_meta( $post_id, $key );
	            }
	        }
	    }
	}
}

WF_SFWF_Product_Fields::init();
