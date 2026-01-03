<?php
/**
 * Plugin Name: A Product Type Promo Banner (Standalone)(workflow)(live)
 * Description: Registers a custom WooCommerce product type "promo" with a banner image field and frontend banner display.  Cleans up the admin UI so the promo type only exposes relevant fields (categories, brands, etc.) and prevents promo products from polluting attribute filter counts.
 * Version: 1.1.2
 * Author: Workflow Delivery
 *
 * This is the main loader file for the Promo Banner product type.  It includes
 * individual modules (wide & thin) that register the product type, add the
 * banner meta box, adjust the admin UI, render the banner on the frontend,
 * tweak the layered nav counts, and set sensible ordering.  Each module lives
 * in its own file to keep complexity down and ease maintenance.  When adding
 * new features make a new module rather than patching existing ones.
 */

defined( 'ABSPATH' ) || exit;

/*
 * Do not bail early if WooCommerce isn't yet loaded.  WooCommerce may be loaded
 * after this plugin because plugin load order is alphabetical.  Register
 * filters and actions regardless; callbacks will only run once WooCommerce is
 * available.
 */

/**
 * Register the custom product type in the selector list.
 */
add_filter( 'product_type_selector', function ( array $types ) {
    $types['promo'] = __( 'Promo Banner', 'aaa' );
    return $types;
} );

/**
 * Map the "promo" product type to our custom class.
 */
add_filter( 'woocommerce_product_class', function ( $classname, $product_type ) {
    return ( 'promo' === $product_type ) ? 'WC_Product_Promo' : $classname;
}, 10, 2 );

// Include modular files.  We guard with file_exists in case the plugin is
// partially updated or files are missing.
// Define modules to include immediately.  The product class itself is loaded later
// on the `woocommerce_loaded` hook to guarantee that WooCommerce and the
// `WC_Product` base class exist.  Do not include the class file here.
$modules = [
    'includes/admin/banner-field.php',      // Registers the banner image meta box without dependencies.
    'includes/admin/admin-ui.php',          // Cleans up the product edit screen.
    'includes/frontend/display-banner.php', // Renders the banner in loops.
    'includes/filter/attribute-count.php',  // Excludes promos from attribute counts.
    'includes/filter/order.php',            // Adjusts ordering and save actions.
];
foreach ( $modules as $module ) {
    $path = plugin_dir_path( __FILE__ ) . $module;
    if ( file_exists( $path ) ) {
        require_once $path;
    }
}

// Defer loading the custom product class until WooCommerce has fully loaded.
add_action( 'woocommerce_loaded', function () {
    $class_file = plugin_dir_path( __FILE__ ) . 'includes/class-wc-product-promo.php';
    if ( file_exists( $class_file ) ) {
        require_once $class_file;
    }
} );
