<?php
/**
 * File: /aaa-oc-product-attribute-visibility/aaa-oc-product-attribute-visibility.php
 * Purpose: Main plugin bootstrap for OC Product Attribute Visibility.  
 *
 * This plugin exposes a settings page under Products where you can see a report
 * of product attribute visibility, and run a bulk “Fix all” operation.  
 * Operations are batched to avoid timeouts and heavy resource usage.  
 * Bulk actions on the Products list include a “Fix Attribute Visibility”
 * option which either runs immediately (for small selections) or schedules a
 * cron job for larger sets.  
 *
 * @package AAA_OC_AttrVis
 *
 * Plugin Name: AAA OC Product Attribute Visibility
 * Description: Fixes taxonomy‑based WooCommerce attribute visibility in _product_attributes. Provides a report and bulk actions via UI, plus background cron processing for large sets.
 * Author: Lokey Delivery
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
if ( ! defined( 'AAA_OC_ATTRVIS_VERSION' ) ) {
    define( 'AAA_OC_ATTRVIS_VERSION', '1.0.0' );
}

if ( ! defined( 'AAA_OC_ATTRVIS_SLUG' ) ) {
    // Slug used for the admin page and option keys.  
    define( 'AAA_OC_ATTRVIS_SLUG', 'aaa-oc-attrvis' );
}

// Enable per‑file debugging. Set to false in production.
if ( ! defined( 'AAA_OC_ATTRVIS_DEBUG_MAIN' ) ) {
    define( 'AAA_OC_ATTRVIS_DEBUG_MAIN', true );
}

if ( AAA_OC_ATTRVIS_DEBUG_MAIN ) {
    error_log( '[AAA_OC_ATTRVIS][main] loaded v' . AAA_OC_ATTRVIS_VERSION );
}

// Autoload and bootstrap the plugin.
require_once __DIR__ . '/includes/aaa-oc-attrvis-loader.php';

/**
 * Add a Settings link in the Plugins list table to jump directly to our
 * report page.  
 * The link points to Products → Attribute Visibility.
 *
 * @param array $links Existing plugin action links.
 * @return array
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
    $url = admin_url( 'edit.php?post_type=product&page=' . AAA_OC_ATTRVIS_SLUG );
    $links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'aaa-oc-attrvis' ) . '</a>';
    return $links;
} );