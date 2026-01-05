<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/productsearch/hooks/class-aaa-oc-productsearch-search-hooks.php
 * Purpose: Hook standard WooCommerce searches; resolve to product IDs from the
 *          index table and hand control back to WooCommerce. Also preserves the
 *          original search term for UI (titles/breadcrumbs) without breaking
 *          get_search_query() calls in headers.
 * Version: 1.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class AAA_OC_ProductSearch_Search_Hooks {

    /**
     * Store the original search query before we clear it from the main query.
     * This prevents "Search results for:" headings from going blank.
     */
    private static $original_search_query = '';

    private static function log( $msg ) {
        if ( ! defined( 'DEBUG_THIS_FILE' ) || ! DEBUG_THIS_FILE ) {
            return;
        }
        $msg = sanitize_text_field( (string) $msg );
        if ( function_exists( 'aaa_oc_log' ) ) {
            aaa_oc_log( '[PRODUCTSEARCH][SEARCH_HOOKS] ' . $msg );
        } else {
            error_log( '[PRODUCTSEARCH][SEARCH_HOOKS] ' . $msg );
        }
    }

    /**
     * Initialise hooks. Called by the module loader.
     */
    public static function init() {
        add_action( 'pre_get_posts', array( __CLASS__, 'pre_get_posts' ), 99 );
        add_filter(
            'woocommerce_product_data_store_cpt_get_products_query',
            array( __CLASS__, 'wc_datastore' ),
            99,
            2
        );

        // IMPORTANT: WordPress applies this filter with 1 argument. Do not declare 2 required args.
        add_filter( 'get_search_query', array( __CLASS__, 'filter_get_search_query' ), 10, 1 );
    }

    /**
     * Preserve the original search term for UI helpers like headings/breadcrumbs.
     * WordPress calls this filter with a single argument ($query).
     */
    public static function filter_get_search_query( $query ) {
        if ( '' !== self::$original_search_query ) {
            return self::$original_search_query;
        }
        return $query;
    }

    /**
     * Intercept the main query when it is a search and replace results with our index-based IDs.
     *
     * @param WP_Query $q The query instance (passed by reference).
     */
    public static function pre_get_posts( $q ) {
        if ( is_admin() || ! $q->is_main_query() ) {
            return;
        }

        $s = (string) $q->get( 's' );
        if ( '' === $s ) {
            return;
        }

        // Capture the original term before we clear it.
        self::$original_search_query = $s;

        // Resolve via ProductSearch index first.
        $ids = AAA_OC_ProductSearch_Helpers::search_index( $s );
        if ( empty( $ids ) ) {
            // Let WordPress do its normal search when our index has nothing.
            return;
        }

        // Apply redirect rule: only if all results share the same brand/category.
        AAA_OC_ProductSearch_Helpers::maybe_redirect_by_results( $s, $ids );

        // Constrain query to our product IDs.
        $q->set( 'post_type', array( 'product' ) );
        $q->set( 'post__in', $ids );
        $q->set( 'orderby', 'post__in' );

        // Avoid WP broad text search after resolving IDs.
        $q->set( 's', '' );
    }

    /**
     * Intercept the WooCommerce datastore search (frontend only).
     */
    public static function wc_datastore( $wp_args, $wc_query ) {
        $s = $wp_args['s'] ?? ( $wp_args['search'] ?? '' );
        if ( ! $s ) {
            return $wp_args;
        }

        if ( is_admin() ) {
            return $wp_args;
        }

        // Preserve original term (covers WC queries that bypass main WP query).
        if ( '' === self::$original_search_query ) {
            self::$original_search_query = (string) $s;
        }

        $ids = AAA_OC_ProductSearch_Helpers::search_index( (string) $s );
        if ( empty( $ids ) ) {
            return $wp_args;
        }

        AAA_OC_ProductSearch_Helpers::maybe_redirect_by_results( (string) $s, $ids );

        $wp_args['post__in'] = $ids;
        $wp_args['orderby']  = 'post__in';
        unset( $wp_args['s'], $wp_args['search'] );

        return $wp_args;
    }
}
