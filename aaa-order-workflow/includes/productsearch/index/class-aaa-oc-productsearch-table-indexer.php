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

    /* === Row builder === */

    /**
     * Build or replace a row in the search index for the given product.
     * This method collects all relevant information (title, brand, categories,
     * pricing, slug and image) and stores it in the index table. It also
     * normalises the title and lowercases/strips accents to improve search
     * matches.
     *
     * @param int $product_id The WooCommerce product ID.
     */
    public static function build_row( int $product_id ) {
        global $wpdb;
        $p = wc_get_product( $product_id );
        if ( ! $p ) {
            return;
        }

        // Decode HTML entities so index is readable and searchable (no &#8211; etc).
        $title_raw = get_the_title( $product_id );
        $title_dec = html_entity_decode( wp_strip_all_tags( (string) $title_raw ), ENT_QUOTES, get_bloginfo( 'charset' ) );
        if ( '' === $title_dec ) {
            $title_dec = (string) $title_raw;
        }
        $title      = $title_dec;
        $title_norm = strtolower( remove_accents( $title_dec ) );

        // Brand: first assigned term (common for brand taxonomy).
        $brand_term = null;
        $brands     = wp_get_object_terms( $product_id, 'berocket_brand' );
        if ( ! is_wp_error( $brands ) && ! empty( $brands ) ) {
            $brand_term = $brands[0];
        }

        // Categories: include assigned child terms AND all ancestors.
        $cats      = wp_get_object_terms( $product_id, 'product_cat' );
        $cat_slugs = [];
        $cat_ids   = [];
        if ( ! is_wp_error( $cats ) ) {
            foreach ( $cats as $c ) {
                $cat_slugs[] = $c->slug;
                $cat_ids[]   = (int) $c->term_id;
                $ancestors   = get_ancestors( $c->term_id, 'product_cat' );
                if ( ! empty( $ancestors ) ) {
                    foreach ( $ancestors as $aid ) {
                        $ancestor = get_term( $aid, 'product_cat' );
                        if ( $ancestor && ! is_wp_error( $ancestor ) ) {
                            $cat_slugs[] = $ancestor->slug;
                            $cat_ids[]   = (int) $ancestor->term_id;
                        }
                    }
                }
            }
        }
        $cat_slugs = array_values( array_unique( $cat_slugs ) );
        $cat_ids   = array_values( array_unique( $cat_ids ) );

        $in_stock = $p->is_in_stock() ? 1 : 0;

        // Display/aux fields.
        $sku = $p->get_sku();
        $price_regular = $p->get_regular_price();
        $price_sale    = $p->get_sale_price();
        $price_active  = $p->get_price();
        if ( function_exists( 'wc_format_decimal' ) ) {
            $price_regular = ( '' !== $price_regular ) ? wc_format_decimal( $price_regular, 6 ) : null;
            $price_sale    = ( '' !== $price_sale )    ? wc_format_decimal( $price_sale,    6 ) : null;
            $price_active  = ( '' !== $price_active )  ? wc_format_decimal( $price_active,  6 ) : null;
        }
        $product_slug = method_exists( $p, 'get_slug' ) ? $p->get_slug() : '';
        $image_id     = $p->get_image_id();
        $image_url    = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : '';
        if ( ! $image_url && function_exists( 'wc_placeholder_img_src' ) ) {
            $image_url = wc_placeholder_img_src();
        }

        $row = [
            'product_id'    => $product_id,
            'in_stock'      => $in_stock,
            'title'         => $title,
            'title_norm'    => $title_norm,
            'brand_term_id' => $brand_term ? (int) $brand_term->term_id : null,
            'brand_slug'    => $brand_term ? $brand_term->slug   : null,
            'brand_name'    => $brand_term ? $brand_term->name   : null,
            'cat_term_ids'  => json_encode( $cat_ids ),
            'cat_slugs'     => implode( ' ', $cat_slugs ),
            'sku'           => $sku ?: null,
            'price_regular' => $price_regular,
            'price_sale'    => $price_sale,
            'price_active'  => $price_active,
            'product_slug'  => $product_slug ?: null,
            'image_url'     => $image_url ?: null,
            'updated_at'    => current_time( 'mysql' ),
        ];

        $formats = [
            '%d', // product_id
            '%d', // in_stock
            '%s', // title
            '%s', // title_norm
            '%d', // brand_term_id
            '%s', // brand_slug
            '%s', // brand_name
            '%s', // cat_term_ids
            '%s', // cat_slugs
            '%s', // sku
            '%f', // price_regular
            '%f', // price_sale
            '%f', // price_active
            '%s', // product_slug
            '%s', // image_url
            '%s', // updated_at
        ];
        $wpdb->replace( self::table(), $row, $formats );
    }
}