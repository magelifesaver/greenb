<?php
/**
 * ProductSearch indexer.
 *
 * This version delegates the row building to a separate helper class
 * (AAA_OC_ProductSearch_Row_Builder) to keep the file under the
 * recommended 150 lines. It merges the DEV improvements while
 * preserving LIVE stability. The indexer seeds and refreshes the
 * product search index and updates rows in response to stock changes,
 * product saves and taxonomy changes.
 *
 * Version: 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_ProductSearch_Table_Indexer {

    /**
     * Determine the full table name for the index.
     */
    private static function table() {
        global $wpdb;
        return $wpdb->prefix . AAA_OC_ProductSearch_Table_Installer::T_INDEX;
    }

    /**
     * Simple logger that honours the module debug flag.
     */
    private static function log( $msg ) {
        if ( defined( 'AAA_OC_PS_INDEXER_DEBUG' ) && AAA_OC_PS_INDEXER_DEBUG ) {
            if ( function_exists( 'aaa_oc_log' ) ) {
                aaa_oc_log( '[PRODUCTSEARCH][INDEXER] ' . $msg );
            } else {
                error_log( '[PRODUCTSEARCH][INDEXER] ' . $msg );
            }
        }
    }

    /* === Admin API (called from settings tab buttons) === */

    /**
     * Clear the entire index and rebuild all rows.
     */
    public static function clear_and_rebuild() {
        global $wpdb;
        $wpdb->query( 'TRUNCATE TABLE ' . self::table() );
        self::log( 'Index truncated via clear_and_rebuild().' );
        self::rebuild_all();
    }

    /**
     * Refresh the index by rebuilding every row.
     */
    public static function refresh_all() {
        self::log( 'Full rebuild triggered via refresh_all().' );
        self::rebuild_all();
    }

    /**
     * Internal helper to rebuild all product rows. Queries all
     * products (published or private) and delegates row creation.
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
     * Keep the index in sync when stock status flips via wc product hooks.
     *
     * @param int              $product_id Product ID.
     * @param string           $status     New stock status (eg. instock/outofstock).
     * @param WC_Product       $product    Product object.
     */
    public static function on_stock_status( $product_id, $status, $product ) {
        global $wpdb;
        $in = ( 'instock' === $status ) ? 1 : 0;
        $wpdb->update( self::table(), [
            'in_stock'   => $in,
            'updated_at' => current_time( 'mysql' ),
        ], [
            'product_id' => (int) $product_id,
        ], [ '%d', '%s' ], [ '%d' ] );

        if ( ! $wpdb->rows_affected ) {
            // Ensure a row exists for products we touch.
            self::build_row( (int) $product_id );
        }
    }

    /**
     * When a product is saved, refresh its index row.
     */
    public static function on_product_save( $post_id, $post ) {
        if ( wp_is_post_revision( $post_id ) || 'product' !== $post->post_type ) {
            return;
        }
        self::build_row( (int) $post_id );
    }

    /**
     * When brand or category terms are assigned, refresh the row.
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
