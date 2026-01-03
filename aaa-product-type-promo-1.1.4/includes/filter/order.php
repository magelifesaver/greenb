<?php
/**
 * Ordering adjustments for promo products.  When sorting by menu_order (the
 * default WooCommerce sorting), promo banners with a negative menu_order will
 * float to the top.  You can further customise order by implementing
 * additional logic here.  Currently this module simply ensures that the
 * default catalog ordering includes menu_order.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ensure the default catalog ordering uses menu_order so our negative values
 * take effect.  WooCommerce usually orders by menu_order and then title for
 * the "Default sorting" option; this filter simply preserves that behaviour.
 */
add_filter( 'woocommerce_default_catalog_orderby', function ( $sort ) {
    // Force default sorting to 'menu_order', which respects negative values.
    return 'menu_order';
} );