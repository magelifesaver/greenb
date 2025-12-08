<?php
/**
 * Filepath: sfwf/index/class-wf-sfwf-forecast-product-fields.php
 * ---------------------------------------------------------------------------
 * Renders product-level forecast meta fields in the product edit page.
 * Adds: Minimum Stock Buffer (forecast_minimum_stock)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WF_SFWF_Forecast_Product_Fields {

    public static function init() {
        add_action( 'woocommerce_product_options_general_product_data', [ __CLASS__, 'render_fields' ] );
        add_action( 'woocommerce_process_product_meta', [ __CLASS__, 'save_fields' ] );
    }

    public static function render_fields() {
        global $post;

        echo '<div class="options_group">';
        echo '<h3>Stock Forecast Settings</h3>';

        // Minimum Stock Buffer
        woocommerce_wp_text_input([
            'id'                => 'forecast_minimum_stock',
            'label'             => 'Minimum Stock Buffer',
            'description'       => 'Stock buffer before reorder is triggered. Used to calculate Reorder Date.',
            'type'              => 'number',
            'custom_attributes' => ['step' => '1', 'min' => '0'],
            'desc_tip'          => true,
        ]);

        echo '</div>';
    }

    public static function save_fields( $post_id ) {
        $min_stock = wc_clean( $_POST['forecast_minimum_stock'] ?? '' );
        if ( $min_stock !== '' ) {
            update_post_meta( $post_id, 'forecast_minimum_stock', $min_stock );
        } else {
            delete_post_meta( $post_id, 'forecast_minimum_stock' );
        }
    }
}

WF_SFWF_Forecast_Product_Fields::init();
