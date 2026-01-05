<?php
/**
 * File: /wp-content/plugins/aaa-awdr-discount-bar-on-archives/aaa-awdr-discount-bar-on-archives.php
 * Plugin Name: AAA - AWDR Discount Bar on Archives
 * Description: Displays the Advanced Woo Discount Rules discount bar on product listing cards and provides a shortcode for builders.
 * Version: 1.0.0
 * Author: Webmaster Workflow
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AAA_AWDR_DEBUG_THIS_FILE', true );

function aaa_awdr_log( $message ) {
    if ( ! defined( 'AAA_AWDR_DEBUG_THIS_FILE' ) || ! AAA_AWDR_DEBUG_THIS_FILE ) {
        return;
    }
    error_log( '[AAA_AWDR_DISCOUNT_BAR] ' . $message );
}

/**
 * Display the discount bar in WooCommerce product loops.
 * No qualification logic is applied here; the discount rules plugin decides visibility.
 */
function aaa_awdr_render_discount_bar_in_loop() {
    if ( is_admin() ) {
        return;
    }

    global $product;

    // Only requirement: we must have a valid product object to pass to the action.
    if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
        return;
    }

    aaa_awdr_log( 'Render bar for product_id=' . $product->get_id() );

    echo '<div class="aaa-awdr-discount-bar-wrap">';
    do_action( 'advanced_woo_discount_rules_load_discount_bar', $product );
    echo '</div>';
}

/**
 * Default placement: under the title/price area on classic archive cards.
 * If your theme/block layout doesn't fire this hook, use the shortcode below.
 */
add_action( 'woocommerce_after_shop_loop_item_title', 'aaa_awdr_render_discount_bar_in_loop', 25 );

/**
 * Shortcode fallback for block/builder templates:
 * Use: [aaa_awdr_discount_bar]
 */
function aaa_awdr_discount_bar_shortcode() {
    if ( is_admin() ) {
        return '';
    }

    global $product;

    if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
        return '';
    }

    ob_start();
    echo '<div class="aaa-awdr-discount-bar-wrap">';
    do_action( 'advanced_woo_discount_rules_load_discount_bar', $product );
    echo '</div>';
    return ob_get_clean();
}
add_shortcode( 'aaa_awdr_discount_bar', 'aaa_awdr_discount_bar_shortcode' );

/**
 * Settings link in Plugins list (points to Discount Rules settings page).
 */
function aaa_awdr_plugin_action_links( $links ) {
    $url     = admin_url( 'admin.php?page=woo_discount_rules' );
    $links[] = '<a href="' . esc_url( $url ) . '">Discount Rules</a>';
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'aaa_awdr_plugin_action_links' );
