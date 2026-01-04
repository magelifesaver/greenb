<?php
/**
 * Filepath: index/class-wf-sfwf-purchase-order-handler.php
 * ---------------------------------------------------------------------------
 * Handles the admin-post action for adding selected products to a purchase
 * order from the forecast grid.  This implementation collects product IDs
 * submitted via a hidden field, performs basic permission and nonce
 * verification, and then loops through each ID to perform a placeholder
 * operation.  For now, the handler records the intent by updating a
 * `forecast_added_to_po` meta flag on each product.  Future integration
 * with a full purchase order system (e.g. ATUM or custom PO logic) can
 * replace this stub with actual purchase order creation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WF_SFWF_Purchase_Order_Handler {

    /**
     * Registers the admin-post action on init.
     */
    public static function init() {
        add_action( 'admin_post_sfwf_add_to_po', [ __CLASS__, 'handle_add_to_po' ] );
    }

    /**
     * Handles the form submission for adding selected products to a purchase order.
     *
     * The handler verifies the current user can manage WooCommerce and that the
     * nonce is valid.  It sanitises and parses the list of product IDs,
     * iterating over each one to perform a basic operation: mark the product
     * with a `forecast_added_to_po` meta key set to `yes`.  This serves as a
     * placeholder and allows us to track which items have been queued for a
     * purchase order.  A future implementation may replace this with actual
     * PO creation logic.
     */
    public static function handle_add_to_po() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'aaa-wf-sfwf' ) );
        }
        if ( ! isset( $_POST['sfwf_add_to_po_nonce'] ) || ! wp_verify_nonce( $_POST['sfwf_add_to_po_nonce'], 'sfwf_add_to_po' ) ) {
            wp_die( esc_html__( 'Invalid request.', 'aaa-wf-sfwf' ) );
        }
        $ids_str = isset( $_POST['product_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['product_ids'] ) ) : '';
        $ids     = [];
        if ( ! empty( $ids_str ) ) {
            $ids = array_filter( array_map( 'absint', explode( ',', $ids_str ) ) );
        }
        $processed = 0;
        if ( ! empty( $ids ) ) {
            foreach ( $ids as $pid ) {
                // As a stub, mark each product with a meta flag indicating it has been added to a PO queue.
                update_post_meta( $pid, 'forecast_added_to_po', 'yes' );
                do_action( 'sfwf_product_added_to_po', $pid );
                $processed++;
            }
        }
        // Redirect back to the referring page with a notice of how many products were processed.
        $redirect = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=sfwf-forecast-grid' );
        $redirect = add_query_arg( 'sfwf_po_added', $processed, $redirect );
        wp_safe_redirect( $redirect );
        exit;
    }
}

// Initialise the handler immediately.
WF_SFWF_Purchase_Order_Handler::init();