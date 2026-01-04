<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/productsearch/index/class-aaa-oc-productsearch-table-indexer.php
 * Purpose: Seed/refresh the ProductSearch index and update single rows when
 *          products change. This implementation is identical to version 1.3.0
 *          of the upstream plugin. All methods remain static and names have
 *          been preserved for compatibility.
 *
 * Version: 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'AAA_OC_PS_INDEXER_DEBUG' ) ) {
    define( 'AAA_OC_PS_INDEXER_DEBUG', false );
}

class AAA_OC_ProductSearch_Table_Indexer {
    /**
     * Get the fully qualified index table name.
     */
    private static function table() {
        global $wpdb;
        return $wpdb->prefix . AAA_OC_ProductSearch_Table_Installer::T_INDEX;
    }

    /**
     * Log debug messages when AAA_OC_PS_INDEXER_DEBUG is true.
     */
    private static function log( $msg ) {
        if ( ! AAA_OC_PS_INDEXER_DEBUG ) {
            return;
        }
        if ( function_exists( 'aaa_oc_log' ) ) {
            aaa_oc_log( '[PRODUCTSEARCH][INDEXER] ' . $msg );
        } else {
            error_log( '[PRODUCTSEARCH][INDEXER] ' . $msg );
        }
    }

    /* === Admin API (called from settings tab buttons) === */

    /**
     * Clear the entire index and rebuild it from scratch. This is useful
     * when troubleshooting incorrect search results. It truncates the
     * index table then delegates to rebuild_all().
     */
    public static function clear_and_rebuild() {
        global $wpdb;
        $wpdb->query( 'TRUNCATE TABLE ' . self::table() );
        self::log( 'Index truncated via clear_and_rebuild().' );
        self::rebuild_all();
    }

    /**
     * Rebuild the index without truncating first. This simply invokes
     * rebuild_all().
     */
    public static function refresh_all() {
        self::log( 'Full rebuild triggered via refresh_all().' );
        self::rebuild_all();
    }

    /**
     * Iterate over every product and build its index row. The query is
     * limited to published or private products. If no products are found
     * the method returns early.
     */
    private static function rebuild_all() {
        $q = new WP_Query( [
            'post_type'      => 'product',
            'post_status'    => [ 'publish', 'private' ],
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
        ] );

        if ( empty( $q->posts ) ) {
            self::log( 'No products found for rebuild_all().' );
            return;
        }

        foreach ( $q->posts as $pid ) {
            self::build_row( (int) $pid );
        }
        self::log( 'Rebuild complete for ' . count( $q->posts ) . ' products.' );
    }

    /* === Event hooks === */

    /**
     * Update a single row's stock status when WooCommerce fires the
     * woocommerce_product_set_stock_status action. If no row exists for
     * the product the row will be created via build_row().
     */
    public static function on_stock_status( $product_id, $status, $product ) {
        global $wpdb;
        $in = ( 'instock' === $status ) ? 1 : 0;
        $wpdb->update(
            self::table(),
            [
                'in_stock'   => $in,
                'updated_at' => current_time( 'mysql' ),
            ],
            [
                'product_id' => (int) $product_id,
            ],
            [
                '%d',
                '%s',
            ],
            [ '%d' ]
        );
        if ( ! $wpdb->rows_affected ) {
            // Ensure row exists for products we touch.
            self::build_row( (int) $product_id );
        }
    }

    /**
     * Rebuild a row when a product is saved.
     */
    public static function on_product_save( $post_id, $post ) {
        if ( wp_is_post_revision( $post_id ) || 'product' !== $post->post_type ) {
            return;
        }
        self::build_row( (int) $post_id );
    }

    /**
     * Rebuild a row when a product's categories or brand terms change.
     */
    public static function on_terms_set( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
        if ( 'product' !== get_post_type( $object_id ) ) {
            return;
        }
        if ( 'product_cat' !== $taxonomy && 'berocket_brand' !== $taxonomy ) {
            return;
        }
        self::build_row( (int) $object_id );
    }

    /**
     * Build a single index row. Delegates to the row builder.
     */
    public static function build_row( int $product_id ) {
        if ( ! class_exists( 'AAA_OC_ProductSearch_Row_Builder' ) ) {
            // The row builder should be loaded via the module loader, but include as a fallback.
            require_once __DIR__ . '/class-aaa-oc-productsearch-row-builder.php';
        }
        AAA_OC_ProductSearch_Row_Builder::build_row( $product_id );
    }
}
