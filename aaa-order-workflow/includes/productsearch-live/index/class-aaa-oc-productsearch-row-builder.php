<?php
/**
 * ProductSearch row builder.
 *
 * Extracted from the DEV indexer to keep files small and maintain the
 * "wide & thin" architecture. This helper builds a single index row for
 * a WooCommerce product, including SKU, pricing, slug and image URL.
 *
 * Version: 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_ProductSearch_Row_Builder {

    /**
     * Build or replace the index row for a product.
     *
     * @param int $product_id WooCommerce product ID.
     */
    public static function build_row( int $product_id ) {
        global $wpdb;

        $p = wc_get_product( $product_id );
        if ( ! $p ) {
            return;
        }

        // Decode HTML entities so the index is readable and searchable.
        $title_raw = get_the_title( $product_id );
        $title_dec = html_entity_decode( wp_strip_all_tags( (string) $title_raw ), ENT_QUOTES, get_bloginfo( 'charset' ) );
        if ( '' === $title_dec ) {
            $title_dec = (string) $title_raw;
        }

        $title      = $title_dec;
        $title_norm = strtolower( remove_accents( $title_dec ) );

        // Brand: first assigned term from the brand taxonomy.
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

                $ancestors = get_ancestors( $c->term_id, 'product_cat' );
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

        // Display fields from the DEV branch.
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
            'brand_slug'    => $brand_term ? $brand_term->slug : null,
            'brand_name'    => $brand_term ? $brand_term->name : null,
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

        $wpdb->replace( $wpdb->prefix . AAA_OC_ProductSearch_Table_Installer::T_INDEX, $row, $formats );
    }
}
