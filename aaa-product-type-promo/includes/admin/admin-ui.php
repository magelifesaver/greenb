<?php
/**
 * Admin UI adjustments for Promo products.  This module hides unnecessary
 * product data tabs and fields (price, shipping, inventory, etc.) when editing
 * a promo product, and ensures the _product_type meta and menu order are
 * stored correctly when saving.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Save the correct _product_type meta and adjust menu order when a product is
 * saved.  We also force a negative menu_order for promo products so they
 * appear before regular products when ordering by menu order.
 */
add_action( 'save_post_product', function ( $post_id ) {
    // Ensure we only run on product post type.
    if ( get_post_type( $post_id ) !== 'product' ) {
        return;
    }

    // Check assigned terms for the product type taxonomy.
    $terms = wp_get_post_terms( $post_id, 'product_type', [ 'fields' => 'slugs' ] );
    if ( in_array( 'promo', $terms, true ) ) {
        update_post_meta( $post_id, '_product_type', 'promo' );
        // Force negative menu order if not already set.  This makes promo
        // products float to the top when sorting by menu_order.
        $current_order = get_post_field( 'menu_order', $post_id );
        if ( (int) $current_order >= 0 ) {
            wp_update_post( [ 'ID' => $post_id, 'menu_order' => -1 ] );
        }
    }
} );

/**
 * Remove unnecessary product data tabs for promo products.  We unhook tabs
 * entirely rather than hiding them with CSS for better accessibility.
 *
 * @param array $tabs Existing tabs.
 * @return array Adjusted tabs.
 */
add_filter( 'woocommerce_product_data_tabs', function ( array $tabs ) {
    // Determine current screen and product type.
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || 'product' !== $screen->post_type ) {
        return $tabs;
    }
    // Determine product type from query or post meta.
    $product_type = '';
    if ( isset( $_GET['product_type'] ) && ! empty( $_GET['product_type'] ) ) {
        $product_type = wc_clean( wp_unslash( $_GET['product_type'] ) );
    } elseif ( isset( $_GET['post'] ) ) {
        $pid          = absint( $_GET['post'] );
        $product_type = get_post_meta( $pid, '_product_type', true );
    }
    if ( 'promo' !== $product_type ) {
        return $tabs;
    }
    // Unset tabs we don't need for a promo banner.
    foreach ( [ 'general', 'inventory', 'shipping', 'linked_product', 'attribute', 'advanced', 'variations' ] as $remove_tab ) {
        if ( isset( $tabs[ $remove_tab ] ) ) {
            unset( $tabs[ $remove_tab ] );
        }
    }
    return $tabs;
} );

/**
 * Hide specific fields within the remaining tabs for promos.  Because some
 * elements are hardcoded in WooCommerce templates, we use a small jQuery
 * script injected into the admin footer.  The script removes price, tax and
 * dimension fields and collapses panels we already unset above.
 */
add_action( 'admin_footer', function () {
    // Only run on product edit screens.
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || 'product' !== $screen->post_type ) {
        return;
    }
    // Determine product type again.
    $product_type = '';
    if ( isset( $_GET['product_type'] ) && ! empty( $_GET['product_type'] ) ) {
        $product_type = wc_clean( wp_unslash( $_GET['product_type'] ) );
    } elseif ( isset( $_GET['post'] ) ) {
        $pid          = absint( $_GET['post'] );
        $product_type = get_post_meta( $pid, '_product_type', true );
    }
    if ( 'promo' !== $product_type ) {
        return;
    }
    ?>
    <script>
    jQuery(function($){
        // Remove price and tax fields inside general tab (if present).
        $('#general_product_data .pricing, #general_product_data ._regular_price_field, #general_product_data ._sale_price_field, #general_product_data ._tax_status_field, #general_product_data ._tax_class_field').remove();
        // Hide weight and dimensions.
        $('#general_product_data ._weight_field, #general_product_data .dimensions_field').remove();
        // Hide virtual/downloadable toggles – promo is always virtual.
        $('#general_product_data ._virtual_field, #general_product_data ._downloadable_field').remove();
    });
    </script>
    <?php
} );
