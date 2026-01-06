<?php
/**
 * Helper functions shared across the AAA Remove Sorting Options plugin.
 *
 * These functions provide abstractions for retrieving available sort keys,
 * supported page types, context detection and reading/writing settings.
 * Keeping helpers here avoids code duplication in core and settings files.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'aaa_rso_get_available_sort_keys' ) ) {
    /**
     * Returns the supported sorting keys and their human‑readable labels. This plugin
     * intentionally limits the available keys to the most common defaults: custom
     * menu order, popularity, rating, date, price (low to high) and price (high
     * to low). If your site introduces additional keys via other plugins or
     * custom code, they won't be visible in the admin screen but remain
     * untouched by this plugin unless they share the same key names.
     *
     * @return array<string,string> Associative array of key => label.
     */
    function aaa_rso_get_available_sort_keys() {
        return array(
            'menu_order' => __( 'Default sorting (custom ordering + name)', 'woocommerce' ),
            'popularity' => __( 'Sort by popularity', 'woocommerce' ),
            'rating'     => __( 'Sort by average rating', 'woocommerce' ),
            'date'       => __( 'Sort by latest', 'woocommerce' ),
            'price'      => __( 'Sort by price: low to high', 'woocommerce' ),
            'price-desc' => __( 'Sort by price: high to low', 'woocommerce' ),
        );
    }
}

if ( ! function_exists( 'aaa_rso_get_page_types' ) ) {
    /**
     * Returns a mapping of internal page type slugs to their display labels. These
     * slugs are used to reference settings for each context. You can add more
     * types here if your theme uses custom taxonomies or archive pages.
     *
     * @return array<string,string> Associative array of slug => label.
     */
    function aaa_rso_get_page_types() {
        return array(
            'shop'     => __( 'Shop page', 'aaa-rso' ),
            'search'   => __( 'Search results', 'aaa-rso' ),
            'category' => __( 'Product categories', 'aaa-rso' ),
            'tag'      => __( 'Product tags', 'aaa-rso' ),
            'attribute'=> __( 'Product attributes', 'aaa-rso' ),
            'brand'    => __( 'Brand archives', 'aaa-rso' ),
        );
    }
}

if ( ! function_exists( 'aaa_rso_get_context' ) ) {
    /**
     * Determines the current catalog context. This helper inspects the WooCommerce
     * conditional functions and taxonomy slugs to map the current archive page to
     * one of the defined page types. If no match is found, an empty string is
     * returned and the plugin falls back to WooCommerce defaults.
     *
     * @return string One of the keys from aaa_rso_get_page_types() or '' if unknown.
     */
    function aaa_rso_get_context() {
        // Shop page: is_shop() returns true on the main shop archive.
        if ( function_exists( 'is_shop' ) && is_shop() ) {
            return 'shop';
        }
        // Product search results (search must come before category/tag checks).
        if ( is_search() ) {
            return 'search';
        }
        // Product category archive.
        if ( function_exists( 'is_product_category' ) && is_product_category() ) {
            return 'category';
        }
        // Product tag archive.
        if ( function_exists( 'is_product_tag' ) && is_product_tag() ) {
            return 'tag';
        }
        // Generic taxonomy archive. We differentiate attributes and brands by slug.
        if ( is_tax() ) {
            $obj = get_queried_object();
            $taxonomy = isset( $obj->taxonomy ) ? $obj->taxonomy : '';
            // Product attribute taxonomies are prefixed with pa_.
            if ( is_string( $taxonomy ) && 0 === strpos( $taxonomy, 'pa_' ) ) {
                return 'attribute';
            }
            // Many branding plugins register a product_brand taxonomy. Adjust slug here
            // if your brand taxonomy differs.
            if ( 'product_brand' === $taxonomy || 'brand' === $taxonomy ) {
                return 'brand';
            }
            // Fallback: other taxonomies are treated like categories.
            return 'category';
        }
        return '';
    }
}

if ( ! function_exists( 'aaa_rso_get_settings' ) ) {
    /**
     * Retrieves the plugin settings from the WordPress options table and merges
     * them with defaults for any missing sections or keys. Each page type has
     * three properties:
     * - enabled (bool): whether the sort dropdown is shown on that page.
     * - options (array of string): the allowed sort keys.
     * - default (string): the default sort key when none is selected.
     *
     * @return array<string, array<string,mixed>> Nested settings keyed by page type.
     */
    function aaa_rso_get_settings() {
        $stored = get_option( 'aaa_rso_settings' );
        $page_types = aaa_rso_get_page_types();
        $available_options = array_keys( aaa_rso_get_available_sort_keys() );
        $defaults = array();
        foreach ( $page_types as $page => $label ) {
            $defaults[ $page ] = array(
                'enabled' => true,
                'options' => $available_options,
                'default' => 'menu_order',
            );
        }
        // If nothing stored, return defaults.
        if ( ! is_array( $stored ) ) {
            return $defaults;
        }
        // Merge stored settings with defaults to preserve new keys.
        foreach ( $defaults as $page => $def ) {
            if ( ! isset( $stored[ $page ] ) || ! is_array( $stored[ $page ] ) ) {
                $stored[ $page ] = $def;
            } else {
                $stored[ $page ] = wp_parse_args( $stored[ $page ], $def );
            }
        }
        return $stored;
    }
}