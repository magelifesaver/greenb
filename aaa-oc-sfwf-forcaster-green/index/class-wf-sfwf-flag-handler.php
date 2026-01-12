<?php
/**
 * Filepath: sfwf/index/class-wf-sfwf-flag-handler.php
 * ---------------------------------------------------------------------------
 * Handles AJAX updates for manual forecasting flags (do-not-reorder, must-stock,
 * force-reorder). Provides an endpoint to toggle these flags via the forecast
 * grid. The request must include a valid nonce and the current user must have
 * permission to edit products. After updating the product meta, a JSON
 * response is returned.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WF_SFWF_Flag_Handler {

    /**
     * Register AJAX action handler.
     */
    public static function init() {
        // Only run in admin context
        if ( is_admin() ) {
            add_action( 'wp_ajax_sfwf_toggle_flag', [ __CLASS__, 'toggle_flag' ] );
        }
    }

    /**
     * Handles AJAX request to toggle a forecast flag on a product.
     * Expects $_POST['product_id'], $_POST['meta_key'], $_POST['value'], and
     * $_POST['security'] (nonce). Only users with edit_products capability
     * (shop manager or admin) may modify product meta.
     */
    public static function toggle_flag() {
        // Verify nonce
        $nonce = isset( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'sfwf_toggle_flag_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Invalid nonce' ], 403 );
        }

        // Check capabilities
        if ( ! current_user_can( 'edit_products' ) ) {
            wp_send_json_error( [ 'message' => 'Insufficient permissions' ], 403 );
        }

        $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
        $meta_key   = isset( $_POST['meta_key'] ) ? sanitize_key( wp_unslash( $_POST['meta_key'] ) ) : '';
        $value      = isset( $_POST['value'] ) && $_POST['value'] === 'yes' ? 'yes' : 'no';

        if ( $product_id <= 0 || empty( $meta_key ) ) {
            wp_send_json_error( [ 'message' => 'Missing parameters' ], 400 );
        }

        // Update meta
        update_post_meta( $product_id, $meta_key, $value );

        wp_send_json_success( [ 'message' => 'Updated' ] );
    }
}

// Register handler
WF_SFWF_Flag_Handler::init();