<?php
/**
 * Front‑end filtering logic for the AAA Remove Sorting Options plugin.
 *
 * This file hooks into WooCommerce ordering filters to adjust the available
 * sort options and defaults based on the administrator’s configuration. It
 * respects per‑page settings defined in the plugin settings. If a page type is
 * disabled or has no enabled options, the sort dropdown is removed entirely.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Make sure helper functions are available.
require_once __DIR__ . '/helpers.php';

if ( ! function_exists( 'aaa_rso_filter_sort_options' ) ) {
    /**
     * Filters the available sort options based on the current page context. If
     * the context is disabled, an empty array is returned which causes
     * WooCommerce to skip rendering the dropdown. Otherwise, only the keys
     * explicitly enabled for that context are retained.
     *
     * @param array<string,string> $sortby Associative array of key => label.
     * @return array<string,string>
     */
    function aaa_rso_filter_sort_options( $sortby ) {
        $context = aaa_rso_get_context();
        $settings = aaa_rso_get_settings();
        if ( ! $context || ! isset( $settings[ $context ] ) ) {
            return $sortby;
        }
        $conf = $settings[ $context ];
        // If disabled or options array is empty, return an empty array to hide dropdown.
        if ( empty( $conf['enabled'] ) || empty( $conf['options'] ) ) {
            return array();
        }
        $allowed = $conf['options'];
        foreach ( $sortby as $key => $label ) {
            if ( ! in_array( $key, $allowed, true ) ) {
                unset( $sortby[ $key ] );
            }
        }
        return $sortby;
    }
}

if ( ! function_exists( 'aaa_rso_default_orderby' ) ) {
    /**
     * Sets the default orderby key for the current context. If the context is
     * configured and enabled, the saved default is returned instead of the
     * WooCommerce default. This does not override a manually chosen orderby
     * value via query string.
     *
     * @param string $default Current default orderby key.
     * @return string
     */
    function aaa_rso_default_orderby( $default ) {
        $context = aaa_rso_get_context();
        $settings = aaa_rso_get_settings();
        if ( $context && isset( $settings[ $context ] ) ) {
            $conf = $settings[ $context ];
            if ( ! empty( $conf['enabled'] ) && ! empty( $conf['default'] ) ) {
                $default = sanitize_text_field( $conf['default'] );
            }
        }
        return $default;
    }
}

if ( ! function_exists( 'aaa_rso_block_manual_orderby' ) ) {
    /**
     * Prevents a user from forcing an orderby parameter that is not allowed for
     * the current context. If the user manually appends ?orderby=price to the
     * URL but price is disabled for that page type, the orderby argument is
     * removed from the query args and WooCommerce falls back to the context’s
     * default ordering.
     *
     * @param array<string,mixed> $args Ordering arguments passed to WC_Query.
     * @return array<string,mixed>
     */
    function aaa_rso_block_manual_orderby( $args ) {
        if ( empty( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return $args;
        }
        $orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $context = aaa_rso_get_context();
        $settings = aaa_rso_get_settings();
        if ( ! $context || ! isset( $settings[ $context ] ) ) {
            return $args;
        }
        $conf = $settings[ $context ];
        if ( empty( $conf['enabled'] ) || empty( $conf['options'] ) ) {
            // Sorting disabled; remove forced orderby to avoid showing dropdown.
            unset( $args['orderby'] );
            return $args;
        }
        if ( ! in_array( $orderby, (array) $conf['options'], true ) ) {
            unset( $args['orderby'] );
        }
        return $args;
    }
}

// Register our filters once WooCommerce is loaded to avoid conflicts when WC is inactive.
add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }
    // Filter both the list of sort options and the default list.
    add_filter( 'woocommerce_catalog_orderby', 'aaa_rso_filter_sort_options', 20 );
    add_filter( 'woocommerce_default_catalog_orderby_options', 'aaa_rso_filter_sort_options', 20 );
    // Change the default orderby for the current context.
    add_filter( 'woocommerce_default_catalog_orderby', 'aaa_rso_default_orderby', 20 );
    // Block manual usage of disabled orderby keys.
    add_filter( 'woocommerce_get_catalog_ordering_args', 'aaa_rso_block_manual_orderby', 20 );
}, 20 );