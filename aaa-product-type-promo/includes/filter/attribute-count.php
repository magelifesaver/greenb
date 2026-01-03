<?php
/**
 * Adjust the layered navigation counts so that promo products do not inflate
 * attribute term counts.  Without this filter, assigning attributes to a
 * promo banner causes the layered nav widget to show counts as if there are
 * actual purchasable products with those attributes, confusing users.  We
 * exclude promos at the SQL level by adding a meta join to the counts query.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_get_filtered_term_product_counts_query', function ( array $query ) {
    global $wpdb;
    // Join postmeta to access the _product_type meta.  We alias the join so
    // multiple joins don't conflict if other plugins do similar things.
    $join_key   = 'promo_type_meta';
    $query['join']  .= " LEFT JOIN {$wpdb->postmeta} AS {$join_key} ON {$wpdb->posts}.ID = {$join_key}.post_id AND {$join_key}.meta_key = '_product_type'";
    // Exclude products where _product_type = 'promo'.  The IS NULL check
    // handles products that don't have a _product_type meta row (regular products).
    $query['where'] .= " AND ( {$join_key}.meta_value IS NULL OR {$join_key}.meta_value <> 'promo' )";
    return $query;
} );
